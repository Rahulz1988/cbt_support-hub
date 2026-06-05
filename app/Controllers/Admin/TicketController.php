<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TicketModel;
use App\Models\TicketStatusLogModel;

/**
 * TicketController (Admin)
 *
 * Fixes applied:
 *  H2 — serveAttachment() uses readfile() + streaming instead of file_get_contents().
 *  L1 — index() and tickets() use paginate(50) to avoid loading all tickets at once.
 */
class TicketController extends BaseController
{
    protected TicketModel $model;
    private const VALID_STATUSES    = ['open', 'in_progress', 'resolved', 'closed'];
    private const VALID_URGENCIES   = ['P1', 'P2', 'P3'];
    private const VALID_ISSUE_TYPES = ['technical', 'operational'];

    public function __construct()
    {
        $this->model = new TicketModel();
    }

    public function index()
    {
        $status    = $this->request->getGet('status');
        $urgency   = $this->request->getGet('urgency');
        $issueType = $this->request->getGet('issue_type');
        $projectId = (int)($this->request->getGet('project_id') ?? 0) ?: null;
        $q         = trim($this->request->getGet('q') ?? '');

        $status    = in_array($status,    self::VALID_STATUSES,    true) ? $status    : '';
        $urgency   = in_array($urgency,   self::VALID_URGENCIES,   true) ? $urgency   : '';
        $issueType = in_array($issueType, self::VALID_ISSUE_TYPES, true) ? $issueType : '';

        $builder = $this->model->getTicketsQuery();
        if ($status)    $builder->where('t.status', $status);
        if ($urgency)   $builder->where('t.urgency', $urgency);
        if ($issueType) $builder->where('t.issue_type', $issueType);
        if ($projectId) $builder->where('t.project_id', $projectId);
        if ($q)         $builder->groupStart()->like('t.ticket_number', $q)->orLike('t.subject', $q)->orLike('c.center_code', $q)->orLike('p.name', $q)->groupEnd();

        // FIX (L1): Paginate instead of loading all tickets
        $builder->orderBy('t.created_at', 'DESC');
        $tickets = $this->model->paginateQuery($builder, 50);

        return view('admin/tickets/index', [
            'title'   => 'All Tickets',
            'tickets' => $tickets,
            'pager'   => $this->model->pager,
            'filters' => compact('status', 'urgency', 'issueType', 'projectId', 'q'),
        ]);
    }

    public function view($id)
    {
        $id     = (int) $id;
        $ticket = $this->model->getTicketDetail($id);
        if (! $ticket) return redirect()->to(site_url('admin/tickets'))->with('error', 'Ticket not found.');

        $logModel = new TicketStatusLogModel();
        return view('admin/tickets/view', [
            'title'       => 'Ticket #' . $ticket['ticket_number'],
            'ticket'      => $ticket,
            'attachments' => $this->model->getAttachments($id),
            'logs'        => $logModel->where('ticket_id', $id)->orderBy('changed_at', 'ASC')->findAll(),
        ]);
    }

    public function updateStatus($id)
    {
        $id           = (int) $id;
        $ticket       = $this->model->find($id);
        $newStatus    = $this->request->getPost('status');
        $note         = substr(trim($this->request->getPost('admin_notes') ?? ''), 0, 1000);
        $newStaffName = substr(trim($this->request->getPost('support_staff_new') ?? ''), 0, 150);

        if (! $ticket) return redirect()->back()->with('error', 'Ticket not found.');
        if (! in_array($newStatus, self::VALID_STATUSES, true)) return redirect()->back()->with('error', 'Invalid status.');

        // Admin cannot set status to closed manually — it auto-closes after 2 hours
        if ($newStatus === 'closed') {
            return redirect()->back()->with('error', 'Tickets close automatically 2 hours after resolving.');
        }

        // Once resolved/closed, admin cannot downgrade — only center can reopen.
        $lockedStatuses = ['resolved', 'closed'];
        if (in_array($ticket['status'], $lockedStatuses, true) && $newStatus !== $ticket['status']) {
            return redirect()->back()->with('error', 'This ticket has been resolved. Only the center can reopen it if the issue persists.');
        }

        // support_staff_new is always required
        if ($newStaffName === '') {
            return redirect()->back()->with('error', 'Support staff name is required before updating the status.');
        }

        $existingInitial = trim($ticket['support_staff_initial'] ?? '');

        if ($existingInitial === '') {
            // First assignment — becomes both initial and current
            $updateData = [
                'status'                => $newStatus,
                'admin_notes'           => $note,
                'support_staff'         => $newStaffName,
                'support_staff_initial' => $newStaffName,
            ];
        } else {
            // Append new name to existing list (avoid duplicates)
            $parts = array_map('trim', explode(',', $ticket['support_staff'] ?? ''));
            if (! in_array($newStaffName, $parts, true)) {
                $parts[] = $newStaffName;
            }
            $updateData = [
                'status'        => $newStatus,
                'admin_notes'   => $note,
                'support_staff' => implode(', ', $parts),
            ];
        }

        if ($newStatus === 'in_progress' && $ticket['status'] !== 'in_progress') {
            // Set in_progress_at only on first transition to in_progress
            // Use isset() guard in case column doesn't exist yet in older DBs
            if (empty($ticket['in_progress_at'] ?? null)) {
                $updateData['in_progress_at'] = new \CodeIgniter\Database\RawSql('NOW()');
            }
        }

        if ($newStatus === 'resolved') {
            // Use SQL NOW() for resolved_at so it is always in DB/server timezone.
            // PHP date() can differ from MySQL NOW() if timezones are mismatched,
            // which would break the DATE_SUB(NOW(), INTERVAL 2 HOUR) auto-close check.
            $updateData['resolved_at'] = new \CodeIgniter\Database\RawSql('NOW()');
        }

        $this->model->update($id, $updateData);

        if ($ticket['status'] !== $newStatus) {
            $logModel = new TicketStatusLogModel();
            $logModel->insert([
                'ticket_id'        => $id,
                'changed_by_admin' => session()->get('user_id'),
                'old_status'       => $ticket['status'],
                'new_status'       => $newStatus,
                'note'             => $note ?: null,
            ]);
        }

        return redirect()->to(site_url('admin/tickets/' . $id))->with('success', 'Ticket updated.');
    }

    public function retag($id)
    {
        $id      = (int) $id;
        $ticket  = $this->model->find($id);
        $newType = $this->request->getPost('issue_type');

        if (! $ticket) return redirect()->back()->with('error', 'Ticket not found.');
        if (! in_array($newType, self::VALID_ISSUE_TYPES, true)) return redirect()->back()->with('error', 'Invalid type.');

        $this->model->update($id, ['issue_type' => $newType]);
        return redirect()->to(site_url('admin/tickets/' . $id))->with('success', 'Issue type updated.');
    }

    /**
     * FIX (H2): Stream file using readfile() instead of loading entire file into memory
     * with file_get_contents(). Handles large files without PHP memory exhaustion.
     * Also adds X-Content-Type-Options: nosniff to prevent MIME sniffing of uploaded files.
     */
    public function serveAttachment(int $id)
    {
        $id         = (int) $id;
        $attachment = (new \App\Models\TicketAttachmentModel())->find($id);

        if (! $attachment) {
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

        // Stream the file efficiently — no memory spike for large files
        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . addslashes($attachment['file_name']) . '"');
        header('Content-Length: ' . filesize($absPath));
        header('X-Content-Type-Options: nosniff');  // FIX (H2): prevent MIME sniffing
        header('Cache-Control: private, max-age=3600');
        readfile($absPath);
        exit;
    }

    public function search()
    {
        $q = trim($this->request->getGet('q') ?? '');
        if (strlen($q) < 2) return $this->response->setJSON([]);
        $tickets = $this->model->getTicketsQuery()
            ->groupStart()->like('t.ticket_number', $q)->orLike('t.subject', $q)->groupEnd()
            ->limit(20)->get()->getResultArray();
        return $this->response->setJSON($tickets);
    }

    /**
     * Download all tickets (with current filters) as an Excel-compatible CSV file.
     * Includes an "Issue Identified" column derived from the common_issues dropdown
     * or the free-text description entered while raising the ticket.
     */
    public function downloadReport()
    {
        $status    = $this->request->getGet('status');
        $urgency   = $this->request->getGet('urgency');
        $issueType = $this->request->getGet('issue_type');
        $projectId = (int)($this->request->getGet('project_id') ?? 0) ?: null;
        $q         = trim($this->request->getGet('q') ?? '');
        $dateFrom  = trim($this->request->getGet('date_from') ?? '');
        $dateTo    = trim($this->request->getGet('date_to')   ?? '');

        $status    = in_array($status,    self::VALID_STATUSES,    true) ? $status    : '';
        $urgency   = in_array($urgency,   self::VALID_URGENCIES,   true) ? $urgency   : '';
        $issueType = in_array($issueType, self::VALID_ISSUE_TYPES, true) ? $issueType : '';

        // Validate date inputs (YYYY-MM-DD)
        $dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ? $dateFrom : '';
        $dateTo   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)   ? $dateTo   : '';

        $builder = $this->model->getTicketsQuery();
        if ($status)    $builder->where('t.status', $status);
        if ($urgency)   $builder->where('t.urgency', $urgency);
        if ($issueType) $builder->where('t.issue_type', $issueType);
        if ($projectId) $builder->where('t.project_id', $projectId);
        if ($q)         $builder->groupStart()->like('t.ticket_number', $q)->orLike('t.subject', $q)->orLike('c.center_code', $q)->orLike('p.name', $q)->groupEnd();
        if ($dateFrom)  $builder->where('DATE(t.created_at) >=', $dateFrom);
        if ($dateTo)    $builder->where('DATE(t.created_at) <=', $dateTo);

        $tickets = $builder->orderBy('t.created_at', 'DESC')->get()->getResultArray();

        // Build a descriptive filename
        $rangeSuffix = '';
        if ($dateFrom || $dateTo) {
            $rangeSuffix = '_' . ($dateFrom ?: 'start') . '_to_' . ($dateTo ?: 'today');
        }
        $filename = 'tickets_report' . $rangeSuffix . '_' . date('Ymd_His') . '.csv';

        if (ob_get_level()) ob_end_clean();

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');

        // UTF-8 BOM so Excel auto-detects encoding
        fputs($out, "\xEF\xBB\xBF");

        // Header row
        fputcsv($out, [
            'Ticket #',
            'Project',
            'Center Code',
            'Center Name',
            'Contact Name',
            'Mobile No.',
            'Subject',
            'Issue Identified',
            'Issue Type',
            'Urgency',
            'Status',
            'Remote Access',
            'AnyDesk ID',
            'Support Staff (Initial)',
            'Support Staff',
            'Admin Notes',
            'Raised At',
            'In Progress At',
            'Resolved At',
        ]);

        foreach ($tickets as $t) {
            // "Issue Identified": prefer the dropdown label; fall back to free-text description
            $issueIdentified = '';
            if (!empty($t['issue_label'])) {
                $issueIdentified = $t['issue_label'];
            } elseif (!empty($t['description'])) {
                $issueIdentified = $t['description'];
            }

            fputcsv($out, [
                $t['ticket_number']        ?? '',
                $t['project_name']         ?? '',
                $t['center_code']          ?? '',
                $t['center_name']          ?? '',
                $t['contact_name']         ?? '',
                $t['mobile_number']        ?? '',
                $t['subject']              ?? '',
                $issueIdentified,
                $t['issue_type']           ?? '',
                $t['urgency']              ?? '',
                ucfirst(str_replace('_', ' ', $t['status'] ?? '')),
                $t['remote_access']        ?? '',
                $t['anydesk_id']           ?? '',
                $t['support_staff_initial'] ?? '',
                $t['support_staff']        ?? '',
                $t['admin_notes']          ?? '',
                $t['created_at']           ?? '',
                $t['in_progress_at']       ?? '',
                $t['resolved_at']          ?? '',
            ]);
        }

        fclose($out);
        exit;
    }
}
