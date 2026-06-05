<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CenterModel;

/**
 * CenterController (Admin)
 *
 * Fixes applied:
 *  H5 — CSV formula-injection sanitisation applied to importProcess() and downloadTemplate().
 *  M2 — center_code format validated (^[A-Z0-9_-]+$) in importProcess() before DB insert.
 *  L1 — Admin ticket listing now uses paginate() (applied in TicketController::index()).
 */
class CenterController extends BaseController
{
    protected CenterModel $model;

    public function __construct()
    {
        $this->model = new CenterModel();
    }

    public function index()
    {
        $search = trim($this->request->getGet('q') ?? '');
        $db     = \Config\Database::connect();

        // Join centers with their project names via project_centers pivot
        $builder = $db->table('centers c')
            ->select('c.*, GROUP_CONCAT(p.name ORDER BY p.name ASC SEPARATOR ", ") AS project_names')
            ->join('project_centers pc', 'pc.center_id = c.id', 'left')
            ->join('projects p',         'p.id = pc.project_id', 'left')
            ->groupBy('c.id');

        if ($search) {
            $builder->groupStart()
                ->like('c.center_code', $search)
                ->orLike('c.center_name', $search)
                ->orLike('c.city', $search)
                ->groupEnd();
        }

        $builder->orderBy('c.center_code', 'ASC');

        // Manual pagination -- use raw subquery count to avoid duplicate column 'id' error
        // that CodeIgniter's countAllResults() triggers when joining tables that all have an 'id' column.
        $perPage = 20;
        $page    = (int)($this->request->getGet('page') ?? 1);
        $offset  = ($page - 1) * $perPage;

        if ($search) {
            $safeSearch = $db->escapeLikeString($search);
            $totalSql = "SELECT COUNT(*) AS cnt FROM (
                SELECT c.id FROM centers c
                LEFT JOIN project_centers pc ON pc.center_id = c.id
                LEFT JOIN projects p ON p.id = pc.project_id
                WHERE (c.center_code LIKE '%{$safeSearch}%'
                    OR c.center_name LIKE '%{$safeSearch}%'
                    OR c.city LIKE '%{$safeSearch}%')
                GROUP BY c.id
            ) sub";
        } else {
            $totalSql = "SELECT COUNT(*) AS cnt FROM (
                SELECT c.id FROM centers c
                LEFT JOIN project_centers pc ON pc.center_id = c.id
                LEFT JOIN projects p ON p.id = pc.project_id
                GROUP BY c.id
            ) sub";
        }
        $total = (int)($db->query($totalSql)->getRow()->cnt ?? 0);

        $centers = $builder->limit($perPage, $offset)->get()->getResultArray();

        $pager = \Config\Services::pager();
        $pager->makeLinks($page, $perPage, $total);

        $data = [
            'title'   => 'Centers',
            'centers' => $centers,
            'pager'   => $total > $perPage ? $pager : null,
            'search'  => $search,
        ];
        return view('admin/centers/index', $data);
    }

    public function create()
    {
        return view('admin/centers/form', ['title' => 'Add Center', 'center' => null]);
    }

    public function store()
    {
        $rules = [
            'center_code' => 'required|max_length[30]|is_unique[centers.center_code]|regex_match[/^[A-Z0-9_-]+$/]',
            'center_name' => 'required|max_length[200]',
            'city'        => 'permit_empty|max_length[100]',
            'state'       => 'permit_empty|max_length[100]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->model->insert([
            'center_code'   => strtoupper(trim($this->request->getPost('center_code'))),
            'center_name'   => trim($this->request->getPost('center_name')),
            'city'          => trim($this->request->getPost('city') ?? ''),
            'state'         => trim($this->request->getPost('state') ?? ''),
            'contact_name'  => trim($this->request->getPost('contact_name') ?? ''),
            'contact_phone' => trim($this->request->getPost('contact_phone') ?? ''),
        ]);
        return redirect()->to(site_url('admin/centers'))->with('success', 'Center added successfully.');
    }

    public function edit($id)
    {
        $id = (int) $id;
        $center = $this->model->find($id);
        if (! $center) return redirect()->to(site_url('admin/centers'))->with('error', 'Not found.');
        return view('admin/centers/form', ['title' => 'Edit Center', 'center' => $center]);
    }

    public function update($id)
    {
        $id = (int) $id;
        $center = $this->model->find($id);
        if (! $center) return redirect()->to(site_url('admin/centers'))->with('error', 'Center not found.');

        $rules = [
            'center_name' => 'required|max_length[200]',
            'city'        => 'permit_empty|max_length[100]',
            'state'       => 'permit_empty|max_length[100]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Never update center_code on edit
        $this->model->update($id, [
            'center_name'   => trim($this->request->getPost('center_name')),
            'city'          => trim($this->request->getPost('city') ?? ''),
            'state'         => trim($this->request->getPost('state') ?? ''),
            'contact_name'  => trim($this->request->getPost('contact_name') ?? ''),
            'contact_phone' => trim($this->request->getPost('contact_phone') ?? ''),
        ]);
        return redirect()->to(site_url('admin/centers'))->with('success', 'Center updated.');
    }

    public function importForm()
    {
        return view('admin/centers/import', ['title' => 'Bulk Import Centers']);
    }

    public function importProcess()
    {
        $file = $this->request->getFile('csv_file');

        if (! $file || ! $file->isValid() || $file->getClientExtension() !== 'csv') {
            return redirect()->back()->with('error', 'Please upload a valid CSV file.');
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            return redirect()->back()->with('error', 'File too large. Maximum size is 5MB.');
        }

        $handle   = fopen($file->getTempName(), 'r');
        $header   = fgetcsv($handle);
        $inserted = 0;
        $skipped  = 0;
        $errors   = [];
        $rowNum   = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (count($row) < 2) { $skipped++; continue; }

            [$centerCode, $centerName, $city, $state, $contactName, $contactPhone] = array_pad($row, 6, '');

            $centerCode = strtoupper(trim($centerCode));
            $centerName = trim($centerName);

            if (! $centerCode || ! $centerName) {
                $errors[] = "Row {$rowNum}: Missing required fields — skipped.";
                $skipped++;
                continue;
            }

            // FIX (M2): Validate center_code format before inserting
            if (! preg_match('/^[A-Z0-9_\-]+$/', $centerCode)) {
                $errors[] = "Row {$rowNum}: Invalid code format '{$centerCode}' (only A-Z, 0-9, _ and - allowed) — skipped.";
                $skipped++;
                continue;
            }

            if ($this->model->where('center_code', $centerCode)->first()) {
                $errors[] = "Row {$rowNum}: Duplicate center code '{$centerCode}' — skipped.";
                $skipped++;
                continue;
            }

            $this->model->insert([
                'center_code'   => $centerCode,
                // FIX (H5): Sanitise CSV cells to prevent formula injection
                'center_name'   => $this->sanitizeCsvCell($centerName),
                'city'          => $this->sanitizeCsvCell(trim($city)),
                'state'         => $this->sanitizeCsvCell(trim($state)),
                'contact_name'  => $this->sanitizeCsvCell(trim($contactName)),
                'contact_phone' => $this->sanitizeCsvCell(trim($contactPhone)),
            ]);
            $inserted++;
        }

        fclose($handle);

        session()->setFlashdata('import_errors', $errors);
        return redirect()->to(site_url('admin/centers'))->with('success', "Import complete. {$inserted} centers added, {$skipped} skipped.");
    }

    public function toggle($id)
    {
        $id     = (int) $id;
        $center = $this->model->find($id);
        if (! $center) {
            return redirect()->to(site_url('admin/centers'))->with('error', 'Center not found.');
        }
        $this->model->update($id, ['is_active' => $center['is_active'] ? 0 : 1]);
        $state = $center['is_active'] ? 'deactivated' : 'activated';
        return redirect()->to(site_url('admin/centers'))->with('success', "Center {$state} successfully.");
    }

    public function downloadTemplate()
    {
        // Template now includes all columns so users know what to fill in the project form CSV
        $rows = [
            ['center_code', 'center_name', 'city', 'state', 'contact_name', 'contact_phone'],
            ['CTR001', 'Sunrise Academy', 'Chennai', 'Tamil Nadu', 'Ravi Kumar', '9841001001'],
            ['CTR002', 'Greenwood Institute', 'Coimbatore', 'Tamil Nadu', 'Priya Nair', '9842002002'],
            ['CTR003', 'Bright Future Center', 'Madurai', 'Tamil Nadu', 'Senthil Raj', '9843003003'],
        ];

        $csv = '';
        foreach ($rows as $row) {
            $csv .= implode(',', array_map([$this, 'sanitizeCsvCell'], $row)) . "\n";
        }

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="centers_template.csv"')
            ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->setBody($csv);
    }

    // ── Private helpers ───────────────────────────────────────────

    /**
     * FIX (H5): Prevent CSV formula injection.
     * Prefixes cells starting with =, +, -, @, TAB, CR with a single quote
     * so spreadsheet applications treat them as plain text.
     */
    private function sanitizeCsvCell(string $val): string
    {
        if ($val !== '' && in_array($val[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $val;
        }
        return $val;
    }
}
