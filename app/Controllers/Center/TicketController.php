<?php

namespace App\Controllers\Center;

use App\Controllers\BaseController;
use App\Models\TicketModel;
use App\Models\TicketAttachmentModel;
use App\Models\TicketStatusLogModel;
use App\Models\ProjectModel;
use App\Models\CommonIssueModel;

/**
 * TicketController (Center)
 *
 * Fixes applied:
 *  H2 — serveAttachment() streams with readfile() instead of file_get_contents().
 *  M5 — File uploads capped at 5 files per ticket submission.
 */
class TicketController extends BaseController
{
    protected TicketModel $ticketModel;

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
    ];
    private const MAX_FILE_SIZE  = 30 * 1024 * 1024; // 30 MB
    private const MAX_FILE_COUNT = 5;               // FIX (M5): max attachments per ticket

    public function __construct()
    {
        $this->ticketModel = new TicketModel();
    }

    public function raiseForm()
    {
        $issueModel = new CommonIssueModel();
        return view('center/raise_ticket', [
            'title'    => 'Raise Support Ticket',
            'project'  => ['name' => session()->get('project_name'), 'id' => session()->get('project_id')],
            'center'   => ['center_code' => session()->get('center_code'), 'center_name' => session()->get('center_name')],
            'issues'   => $issueModel->getActive(),
            'max_files' => self::MAX_FILE_COUNT,
        ]);
    }

    public function raiseStore()
    {
        $centerId  = (int) session()->get('center_id');
        $projectId = (int) session()->get('project_id');

        $issueIdRaw   = $this->request->getPost('issue_id');
        $description  = trim($this->request->getPost('description') ?? '');
        $issueType    = $this->request->getPost('issue_type');
        $remoteAccess = $this->request->getPost('remote_access');

        $rules = [
            'subject'        => 'required|min_length[5]|max_length[255]',
            'urgency'        => 'required|in_list[P1,P2,P3]',
            'issue_type'     => 'required|in_list[technical,operational]',
            'remote_access'  => 'required|in_list[yes,no]',
            'mobile_number'  => 'required|exact_length[10]|regex_match[/^[0-9]{10}$/]',
        ];

        if ($remoteAccess === 'yes') {
            $rules['anydesk_id'] = 'required|min_length[5]|max_length[20]';
        }

        if (! $issueIdRaw || $issueIdRaw === 'other') {
            $rules['description'] = 'required|min_length[10]|max_length[5000]';
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $issueId = null;
        if ($issueIdRaw && $issueIdRaw !== 'other') {
            $issueId = (int) $issueIdRaw;
            if (! $description) {
                $issueModel  = new CommonIssueModel();
                $issue       = $issueModel->find($issueId);
                $description = $issue['issue_text'] ?? '';
            }
        }

        if (! $description) {
            return redirect()->back()->withInput()->with('errors', ['description' => 'Please describe the issue.']);
        }

        $ticketId = $this->ticketModel->insert([
            'ticket_number' => 'TMP-' . strtoupper(bin2hex(random_bytes(8))),
            'project_id'    => $projectId,
            'center_id'     => $centerId,
            'subject'       => trim($this->request->getPost('subject')),
            'issue_id'      => $issueId,
            'issue_type'    => $issueType,
            'description'   => $description,
            'urgency'       => $this->request->getPost('urgency'),
            'remote_access' => $remoteAccess,
            'anydesk_id'    => ($remoteAccess === 'yes') ? trim($this->request->getPost('anydesk_id') ?? '') : null,
            'mobile_number' => trim($this->request->getPost('mobile_number') ?? ''),
            'status'        => 'open',
        ]);

        if (! $ticketId) {
            return redirect()->back()->withInput()->with('error', 'Failed to create ticket. Please try again.');
        }

        $ticketNumber = 'TKT-' . date('Ymd') . '-' . str_pad($ticketId, 5, '0', STR_PAD_LEFT);
        $this->ticketModel->update($ticketId, ['ticket_number' => $ticketNumber]);

        // Handle attachments — FIX (M5): cap at MAX_FILE_COUNT files
        $files = $this->request->getFileMultiple('attachments') ?? [];
        // Filter out the empty phantom file CI adds when no file is selected
        $files = array_filter($files, fn($f) => $f->getSize() > 0 || $f->getError() !== UPLOAD_ERR_NO_FILE);
        $files = array_slice(array_values($files), 0, self::MAX_FILE_COUNT);

        if ($files) {
            $mb = self::MAX_FILE_SIZE / 1024 / 1024;

            foreach ($files as $file) {
                // PHP itself rejected the file (e.g. exceeded post_max_size / upload_max_filesize)
                // UPLOAD_ERR_INI_SIZE (1) or UPLOAD_ERR_FORM_SIZE (2) means file was too large
                if (in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                    $this->ticketModel->delete($ticketId, true);
                    return redirect()->back()->withInput()->with('error',
                        "File \"{$file->getClientName()}\" is too large and was rejected by the server. Maximum allowed size is {$mb}MB."
                    );
                }

                // App-level size check (catches files PHP allowed through but exceed our limit)
                if ($file->getSize() > self::MAX_FILE_SIZE) {
                    $this->ticketModel->delete($ticketId, true);
                    return redirect()->back()->withInput()->with('error',
                        "File \"{$file->getClientName()}\" ({$this->formatBytes($file->getSize())}) exceeds the {$mb}MB limit. Please compress or resize it before uploading."
                    );
                }

                // Any other upload error — reject cleanly
                if (! $file->isValid()) {
                    $this->ticketModel->delete($ticketId, true);
                    return redirect()->back()->withInput()->with('error',
                        "File \"{$file->getClientName()}\" could not be uploaded (error code {$file->getError()}). Please try again."
                    );
                }
            }

            $attachModel = new TicketAttachmentModel();
            foreach ($files as $file) {
                if ($file->hasMoved()) continue;
                $mimeType = $file->getMimeType(); // capture BEFORE move()
                if (! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) continue;
                $newName = $file->getRandomName();
                $file->move(WRITEPATH . 'uploads/tickets', $newName);
                $attachModel->insert([
                    'ticket_id' => $ticketId,
                    'file_name' => $file->getClientName(),
                    'file_path' => 'writable/uploads/tickets/' . $newName,
                    'file_type' => $mimeType,
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        return redirect()->to(site_url('center/tickets'))->with('success', "Ticket {$ticketNumber} raised successfully.");
    }

    /**
     * FIX (H2): Stream file using readfile() — no memory spike for large files.
     * IDOR protection: verify attachment belongs to this center's ticket.
     */
    public function serveAttachment(int $id)
    {
        $id         = (int) $id;
        $centerId   = (int) session()->get('center_id');
        $attachment = (new \App\Models\TicketAttachmentModel())->find($id);

        if (! $attachment) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $ticket = $this->ticketModel->find($attachment['ticket_id']);
        if (! $ticket || (int) $ticket['center_id'] !== $centerId) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $absPath     = realpath(ROOTPATH . $attachment['file_path']);
        $allowedBase = realpath(ROOTPATH . 'writable/uploads/tickets');

        if (! $absPath || ! $allowedBase || strncmp($absPath, $allowedBase, strlen($allowedBase)) !== 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (! is_file($absPath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $mime = mime_content_type($absPath) ?: 'application/octet-stream';

        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . addslashes($attachment['file_name']) . '"');
        header('Content-Length: ' . filesize($absPath));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=3600');
        readfile($absPath);
        exit;
    }

    public function myTickets()
    {
        $centerId  = (int) session()->get('center_id');
        $projectId = (int) session()->get('project_id');
        $status    = $this->request->getGet('status') ?? '';

        $builder = $this->ticketModel->getTicketsQuery()
            ->where('t.center_id', $centerId)
            ->where('t.project_id', $projectId);

        if (in_array($status, ['open', 'in_progress', 'resolved', 'closed'], true)) {
            $builder->where('t.status', $status);
        }

        return view('center/my_tickets', [
            'title'   => 'My Tickets',
            'tickets' => $builder->orderBy('t.created_at', 'DESC')->get()->getResultArray(),
            'status'  => $status,
        ]);
    }

    public function view($id)
    {
        $id       = (int) $id;
        $centerId = (int) session()->get('center_id');

        $ticket = $this->ticketModel->getTicketDetail($id);
        if (! $ticket || (int) $ticket['center_id'] !== $centerId) {
            return redirect()->to(site_url('center/tickets'))->with('error', 'Ticket not found.');
        }

        $projectModel   = new ProjectModel();
        $project        = $projectModel->find($ticket['project_id']);
        $project_active = $project && $project['is_active'] == 1;

        return view('center/ticket_view', [
            'title'          => 'Ticket #' . $ticket['ticket_number'],
            'ticket'         => $ticket,
            'attachments'    => $this->ticketModel->getAttachments($id),
            'project_active' => $project_active,
        ]);
    }

    /**
     * Silent keep-alive ping used by the auto-refresh polling.
     * Does NOT update last_activity — AuthFilter skips that for this route.
     * Returns minimal JSON so the frontend can detect session expiry (401).
     */
    public function pingSession()
    {
        return $this->response->setJSON(['ok' => true]);
    }

    public function ticketStatus($id)
    {
        $id       = (int) $id;
        $centerId = (int) session()->get('center_id');

        if (! $centerId) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthenticated']);
        }

        $ticket = $this->ticketModel->find($id);
        if (! $ticket || (int) $ticket['center_id'] !== $centerId) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Not found']);
        }

        return $this->response->setJSON([
            'id'          => $ticket['id'],
            'status'      => $ticket['status'],
            'admin_notes' => $ticket['admin_notes'],
            'resolved_at' => $ticket['resolved_at'],
            'updated_at'  => $ticket['updated_at'],
        ]);
    }

    public function reopen($id)
    {
        $id       = (int) $id;
        $centerId = (int) session()->get('center_id');

        $ticket = $this->ticketModel->find($id);
        if (! $ticket || (int) $ticket['center_id'] !== $centerId) {
            return redirect()->to(site_url('center/tickets'))->with('error', 'Ticket not found.');
        }
        if ($ticket['status'] !== 'resolved') {
            return redirect()->to(site_url('center/tickets/' . $id))->with('error', 'Only resolved tickets can be reopened by the center.');
        }
        $projectModel = new ProjectModel();
        $project      = $projectModel->find($ticket['project_id']);
        if (! $project || ! $project['is_active']) {
            return redirect()->to(site_url('center/tickets/' . $id))->with('error', 'Cannot reopen — project is no longer active.');
        }

        $this->ticketModel->update($id, ['status' => 'open', 'resolved_at' => null]);

        $logModel = new TicketStatusLogModel();
        $logModel->insert([
            'ticket_id'         => $id,
            'changed_by_center' => $centerId,
            'old_status'        => 'resolved',
            'new_status'        => 'open',
            'note'              => 'Reopened by center.',
        ]);

        return redirect()->to(site_url('center/tickets/' . $id))->with('success', 'Ticket reopened successfully.');
    }

    /**
     * Download this center's tickets as an Excel-compatible CSV.
     * Includes an "Issue Identified" column from the dropdown label or free-text description.
     */
    public function downloadReport()
    {
        $centerId  = (int) session()->get('center_id');
        $projectId = (int) session()->get('project_id');
        $status    = $this->request->getGet('status') ?? '';
        $dateFrom  = trim($this->request->getGet('date_from') ?? '');
        $dateTo    = trim($this->request->getGet('date_to')   ?? '');

        // Validate date inputs (YYYY-MM-DD)
        $dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ? $dateFrom : '';
        $dateTo   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)   ? $dateTo   : '';

        $builder = $this->ticketModel->getTicketsQuery()
            ->where('t.center_id', $centerId)
            ->where('t.project_id', $projectId);

        if (in_array($status, ['open', 'in_progress', 'resolved', 'closed'], true)) {
            $builder->where('t.status', $status);
        }
        if ($dateFrom) $builder->where('DATE(t.created_at) >=', $dateFrom);
        if ($dateTo)   $builder->where('DATE(t.created_at) <=', $dateTo);

        $tickets = $builder->orderBy('t.created_at', 'DESC')->get()->getResultArray();

        $rangeSuffix = '';
        if ($dateFrom || $dateTo) {
            $rangeSuffix = '_' . ($dateFrom ?: 'start') . '_to_' . ($dateTo ?: 'today');
        }
        $filename = 'my_tickets' . $rangeSuffix . '_' . date('Ymd_His') . '.csv';

        if (ob_get_level()) ob_end_clean();

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');

        // UTF-8 BOM so Excel auto-detects encoding
        fputs($out, "\xEF\xBB\xBF");

        fputcsv($out, [
            'Ticket #',
            'Project',
            'Center Code',
            'Center Name',
            'Mobile No.',
            'Subject',
            'Issue Identified',
            'Issue Type',
            'Urgency',
            'Status',
            'Remote Access',
            'AnyDesk ID',
            'Support Staff',
            'Admin Notes',
            'Raised At',
            'In Progress At',
            'Resolved At',
        ]);

        foreach ($tickets as $t) {
            $issueIdentified = '';
            if (!empty($t['issue_label'])) {
                $issueIdentified = $t['issue_label'];
            } elseif (!empty($t['description'])) {
                $issueIdentified = $t['description'];
            }

            fputcsv($out, [
                $t['ticket_number']   ?? '',
                $t['project_name']    ?? '',
                $t['center_code']     ?? '',
                $t['center_name']     ?? '',
                $t['mobile_number']   ?? '',
                $t['subject']         ?? '',
                $issueIdentified,
                $t['issue_type']      ?? '',
                $t['urgency']         ?? '',
                ucfirst(str_replace('_', ' ', $t['status'] ?? '')),
                $t['remote_access']   ?? '',
                $t['anydesk_id']      ?? '',
                $t['support_staff']   ?? '',
                $t['admin_notes']     ?? '',
                $t['created_at']      ?? '',
                $t['in_progress_at']  ?? '',
                $t['resolved_at']     ?? '',
            ]);
        }

        fclose($out);
        exit;
    }
    /**
     * Format bytes into a human-readable string for error messages.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 1) . ' MB';
        }
        return round($bytes / 1024, 0) . ' KB';
    }

}
