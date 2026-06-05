<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProjectModel;
use App\Models\ProjectCenterModel;
use App\Models\CenterModel;
use App\Models\TicketModel;

/**
 * ProjectController (Admin)
 *
 * OTP strategy:
 *  - `otp`      — plain text, stored for admin display only (admin-only panel, not exposed publicly)
 *  - `otp_hash` — bcrypt hash, used by AuthController::loginProcess() via password_verify()
 *
 * Both columns are written together on every create/regenerate so they are always in sync.
 * The OTP page always shows the current plain OTP — no flashdata, no "save it now" pressure.
 *
 * BUG1 FIX — Projects auto-expire: syncProjectStatuses() runs on index() to keep
 *            is_active accurate without needing a cron job.
 */
class ProjectController extends BaseController
{
    protected ProjectModel $model;

    public function __construct()
    {
        $this->model = new ProjectModel();
    }

    // ── Status sync (BUG1 fix) ────────────────────────────────────

    /**
     * Deactivates projects past their end_date and activates ones whose range just started.
     * Lightweight — only touches rows that actually need changing.
     */
    private function syncProjectStatuses(): void
    {
        $today = date('Y-m-d');
        $db    = \Config\Database::connect();

        $db->query(
            "UPDATE projects SET is_active = 0
             WHERE is_active = 1 AND end_date < ?",
            [$today]
        );

        $db->query(
            "UPDATE projects SET is_active = 1
             WHERE is_active = 0 AND manually_disabled = 0
               AND start_date <= ? AND end_date >= ?",
            [$today, $today]
        );
    }

    // ── CRUD ─────────────────────────────────────────────────────

    public function index()
    {
        $this->syncProjectStatuses();

        $filter  = $this->request->getGet('filter') ?? 'all';
        $builder = $this->model->orderBy('start_date', 'DESC');
        if ($filter === 'active')   $builder->where('is_active', 1);
        if ($filter === 'inactive') $builder->where('is_active', 0);

        return view('admin/projects/index', [
            'title'         => 'Projects',
            'projects'      => $builder->findAll(),
            'filter'        => $filter,
            'allCount'      => $this->model->countAll(),
            'activeCount'   => $this->model->where('is_active', 1)->countAllResults(),
            'inactiveCount' => $this->model->where('is_active', 0)->countAllResults(),
        ]);
    }

    public function create()
    {
        return view('admin/projects/form', ['title' => 'Create Project', 'project' => null, 'assignedCenters' => []]);
    }

    public function store()
    {
        $rules = [
            'name'       => 'required|min_length[3]|max_length[200]',
            'start_date' => 'required|valid_date[Y-m-d]',
            'end_date'   => 'required|valid_date[Y-m-d]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $startDate = $this->request->getPost('start_date');
        $endDate   = $this->request->getPost('end_date');

        if ($endDate < $startDate) {
            return redirect()->back()->withInput()->with('errors', ['end_date' => 'End date must be on or after start date.']);
        }

        $today    = date('Y-m-d');
        $isActive = ($today >= $startDate && $today <= $endDate) ? 1 : 0;

        [$plainOtp, $otpHash] = $this->makeOtp();

        $encrypter = \Config\Services::encrypter();

        $this->model->insert([
            'name'          => trim($this->request->getPost('name')),
            'description'   => trim($this->request->getPost('description') ?? ''),
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'otp_encrypted' => $encrypter->encrypt($plainOtp),
            'otp_hash'      => $otpHash,    // bcrypt — for center login verification
            'is_active'   => $isActive,
            'created_by'  => session()->get('user_id'),
        ]);

        $projectId = $this->model->getInsertID();
        $this->processCenterAssignments($projectId);

        return redirect()->to(site_url('admin/projects/' . $projectId . '/otp'))
            ->with('success', 'Project created successfully.');
    }

    public function edit($id)
    {
        $id = (int) $id;
        $project = $this->model->find($id);
        if (! $project) return redirect()->to(site_url('admin/projects'))->with('error', 'Not found.');

        // Block editing of naturally expired projects (end_date has passed, not manually disabled)
        if (! $project['is_active'] && ! $project['manually_disabled'] && $project['end_date'] < date('Y-m-d')) {
            return redirect()->to(site_url('admin/projects'))->with('error', 'This project has expired and cannot be edited.');
        }

        $pcModel         = new ProjectCenterModel();
        $assignedCenters = $pcModel->getCentersForProject($id);

        return view('admin/projects/form', ['title' => 'Edit Project', 'project' => $project, 'assignedCenters' => $assignedCenters]);
    }

    public function update($id)
    {
        $id = (int) $id;
        $project = $this->model->find($id);
        if (! $project) return redirect()->to(site_url('admin/projects'))->with('error', 'Not found.');

        // Block updates of naturally expired projects
        if (! $project['is_active'] && ! $project['manually_disabled'] && $project['end_date'] < date('Y-m-d')) {
            return redirect()->to(site_url('admin/projects'))->with('error', 'This project has expired and cannot be updated.');
        }

        $rules = [
            'name'       => 'required|min_length[3]|max_length[200]',
            'start_date' => 'required|valid_date[Y-m-d]',
            'end_date'   => 'required|valid_date[Y-m-d]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $startDate = $this->request->getPost('start_date');
        $endDate   = $this->request->getPost('end_date');

        if ($endDate < $startDate) {
            return redirect()->back()->withInput()->with('errors', ['end_date' => 'End date must be on or after start date.']);
        }

        $today    = date('Y-m-d');
        $isActive = (! $project['manually_disabled'] && $today >= $startDate && $today <= $endDate) ? 1 : 0;

        $updated = $this->model->where('id', $id)->set([
            'name'        => trim($this->request->getPost('name')),
            'description' => trim($this->request->getPost('description') ?? ''),
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'is_active'   => $isActive,
        ])->update();

        if ($updated === false) {
            return redirect()->back()->withInput()->with('error', 'Failed to update project. Please try again.');
        }

        $file        = $this->request->getFile('centers_csv');
        $manualCodes = trim($this->request->getPost('center_codes') ?? '');
        $hasFile     = $file && $file->isValid() && !$file->hasMoved() && $file->getSize() > 0;
        if ($hasFile || $manualCodes) {
            $this->processCenterAssignments($id);
        }

        return redirect()->to(site_url('admin/projects/' . $id . '/otp'))->with('success', 'Project updated successfully.');
    }

    public function showOtp($id)
    {
        $id      = (int) $id;
        $project = $this->model->find($id);
        if (! $project) return redirect()->to(site_url('admin/projects'))->with('error', 'Not found.');

        $pcModel = new ProjectCenterModel();
        $centers = $pcModel->getCentersForProject($id);

        return view('admin/projects/otp', [
            'title'   => 'Project OTP — ' . $project['name'],
            'project' => $project,  // $project['otp'] is always the current plain OTP
            'centers' => $centers,
        ]);
    }

    public function regenerateOtp($id)
    {
        $id      = (int) $id;
        $project = $this->model->find($id);
        if (! $project) return redirect()->to(site_url('admin/projects'))->with('error', 'Not found.');

        [$plainOtp, $otpHash] = $this->makeOtp();

        $encrypter = \Config\Services::encrypter();

        // Update both columns atomically so they are always in sync
        $this->model->update($id, [
            'otp_encrypted' => $encrypter->encrypt($plainOtp),
            'otp_hash'      => $otpHash,
        ]);

        return redirect()->to(site_url('admin/projects/' . $id . '/otp'))
            ->with('success', 'OTP regenerated successfully.');
    }

    public function toggle($id)
    {
        $id      = (int) $id;
        $project = $this->model->find($id);
        if (! $project) {
            return redirect()->to(site_url('admin/projects'))->with('error', 'Project not found.');
        }

        if ($project['manually_disabled']) {
            $today    = date('Y-m-d');
            $isActive = ($today >= $project['start_date'] && $today <= $project['end_date']) ? 1 : 0;
            $this->model->update($id, ['manually_disabled' => 0, 'is_active' => $isActive]);
            $msg = 'Project re-enabled. It will activate automatically within its date range.';
        } else {
            $this->model->update($id, ['manually_disabled' => 1, 'is_active' => 0]);
            $msg = 'Project disabled. Centers cannot log in until you re-enable it.';
        }

        return redirect()->to(site_url('admin/projects'))->with('success', $msg);
    }

    public function tickets($id)
    {
        $id      = (int) $id;
        $project = $this->model->find($id);
        if (! $project) return redirect()->to(site_url('admin/projects'))->with('error', 'Not found.');

        $ticketModel = new TicketModel();
        $status      = $this->request->getGet('status');
        $urgency     = $this->request->getGet('urgency');
        $q           = trim($this->request->getGet('q') ?? '');

        $validStatuses  = ['open', 'in_progress', 'resolved', 'closed'];
        $validUrgencies = ['P1', 'P2', 'P3'];
        $status  = in_array($status,  $validStatuses,  true) ? $status  : '';
        $urgency = in_array($urgency, $validUrgencies, true) ? $urgency : '';

        $builder = $ticketModel->getTicketsQuery()->where('t.project_id', $id);
        if ($status)  $builder->where('t.status', $status);
        if ($urgency) $builder->where('t.urgency', $urgency);
        if ($q)       $builder->groupStart()->like('t.ticket_number', $q)->orLike('t.subject', $q)->orLike('c.center_code', $q)->groupEnd();

        $builder->orderBy('t.created_at', 'DESC');
        $tickets = $ticketModel->paginateQuery($builder, 50);
        $stats   = ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];
        foreach ($tickets as $t) { if (isset($stats[$t['status']])) $stats[$t['status']]++; }

        return view('admin/projects/tickets', [
            'title'   => $project['name'] . ' — Tickets',
            'project' => $project,
            'tickets' => $tickets,
            'pager'   => $ticketModel->pager,
            'stats'   => $stats,
            'filters' => compact('status', 'urgency', 'q'),
        ]);
    }

    // ── Private helpers ──────────────────────────────────────────

    /**
     * Generate a new OTP and return both the plain text and its bcrypt hash.
     * @return array{0: string, 1: string}  [plainOtp, otpHash]
     */
    private function makeOtp(): array
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $plain = '';
        for ($i = 0; $i < 6; $i++) {
            $plain .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return [$plain, password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12])];
    }

    private function processCenterAssignments(int $projectId): void
    {
        $centerModel = new CenterModel();
        $pcModel     = new ProjectCenterModel();
        $centerIds   = [];

        /**
         * BUG FIX + Feature 6:
         * Previously, this only looked up existing centers and silently skipped new ones.
         * Now, if a center_code does not exist in the DB, it is auto-created from CSV columns.
         * This means uploading a CSV while creating/editing a project is the source of truth
         * for centers — no need for a separate "Add Center" workflow.
         */

        $file = $this->request->getFile('centers_csv');
        if ($file && $file->isValid() && $file->getClientExtension() === 'csv') {
            $handle = fopen($file->getTempName(), 'r');
            $header = fgetcsv($handle); // skip header row
            while (($row = fgetcsv($handle)) !== false) {
                // CSV columns: center_code, center_name, city, state, contact_name, contact_phone
                [$code, $name, $city, $state, $contactName, $contactPhone] = array_pad($row, 6, '');
                $code = strtoupper(trim($code));
                if (! $code) continue;

                $center = $centerModel->where('center_code', $code)->first();
                if ($center) {
                    // Update existing center details from CSV if provided
                    $updateData = [];
                    if (trim($name)        && $center['center_name']   !== trim($name))        $updateData['center_name']   = trim($name);
                    if (trim($city)        && $center['city']          !== trim($city))        $updateData['city']          = trim($city);
                    if (trim($state)       && $center['state']         !== trim($state))       $updateData['state']         = trim($state);
                    if (trim($contactName) && $center['contact_name']  !== trim($contactName)) $updateData['contact_name']  = trim($contactName);
                    if (trim($contactPhone)&& $center['contact_phone'] !== trim($contactPhone))$updateData['contact_phone'] = trim($contactPhone);
                    if ($updateData) $centerModel->update($center['id'], $updateData);
                    $centerIds[] = $center['id'];
                } else {
                    // Auto-create center from CSV row
                    $centerModel->insert([
                        'center_code'   => $code,
                        'center_name'   => trim($name) ?: $code,
                        'city'          => trim($city),
                        'state'         => trim($state),
                        'contact_name'  => trim($contactName),
                        'contact_phone' => trim($contactPhone),
                        'is_active'     => 1,
                    ]);
                    $centerIds[] = $centerModel->getInsertID();
                }
            }
            fclose($handle);
        }

        $manualCodes = $this->request->getPost('center_codes') ?? '';
        if ($manualCodes) {
            foreach (preg_split('/[\r\n,]+/', $manualCodes) as $rawLine) {
                // Support "CODE | Center Name" or just "CODE" format for manual entry
                $parts = explode('|', $rawLine, 2);
                $code  = strtoupper(trim($parts[0]));
                $name  = isset($parts[1]) ? trim($parts[1]) : '';
                if (! $code) continue;

                $center = $centerModel->where('center_code', $code)->first();
                if ($center) {
                    if ($name && $center['center_name'] !== $name) {
                        $centerModel->update($center['id'], ['center_name' => $name]);
                    }
                    $centerIds[] = $center['id'];
                } else {
                    // Auto-create center from manual code entry
                    $centerModel->insert([
                        'center_code' => $code,
                        'center_name' => $name ?: $code,
                        'is_active'   => 1,
                    ]);
                    $centerIds[] = $centerModel->getInsertID();
                }
            }
        }

        if ($centerIds) {
            $pcModel->assignCenters($projectId, array_unique($centerIds));
        }
    }
}
