<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

/**
 * TicketModel
 *
 * Fixes applied:
 *  M3 — autoCloseResolved() is called from DashboardController on every admin load.
 *       A cron job calling spark tickets:autoclose is the recommended production approach.
 *  L1 — paginateQuery() helper added so controllers can paginate raw BaseBuilder queries.
 */
class TicketModel extends Model
{
    protected $table         = 'tickets';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'ticket_number', 'project_id', 'center_id',
        'subject', 'issue_id', 'issue_type', 'description',
        'urgency', 'status', 'admin_notes', 'support_staff', 'support_staff_initial',
        'resolved_at', 'in_progress_at', 'remote_access', 'anydesk_id', 'mobile_number',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getTicketsQuery(): BaseBuilder
    {
        return $this->db->table('tickets t')
            ->select('t.*, c.center_code, c.center_name, c.contact_name, c.contact_phone,
                      p.name AS project_name,
                      ci.issue_text AS issue_label')
            ->join('centers c',        't.center_id = c.id',  'left')
            ->join('projects p',       't.project_id = p.id', 'left')
            ->join('common_issues ci', 't.issue_id = ci.id',  'left');
    }

    public function getTicketDetail(int $id): ?array
    {
        return $this->getTicketsQuery()->where('t.id', $id)->get()->getRowArray() ?: null;
    }

    public function getAttachments(int $ticketId): array
    {
        return $this->db->table('ticket_attachments')
            ->where('ticket_id', $ticketId)
            ->orderBy('uploaded_at', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * FIX (L1): Paginate a raw BaseBuilder query for use in list views.
     * CI4's built-in paginate() only works on Model queries, not BaseBuilder.
     * This wraps the builder with LIMIT/OFFSET and sets $this->pager.
     */
    public function paginateQuery(BaseBuilder $builder, int $perPage = 50): array
    {
        $page   = (int) ($_GET['page'] ?? 1);
        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $totalResult = clone $builder;
        $total       = $totalResult->countAllResults(false);

        $pager = \Config\Services::pager();
        $pager->makeLinks($page, $perPage, $total);
        $this->pager = $pager;

        return $builder->limit($perPage, $offset)->get()->getResultArray();
    }

    /**
     * Auto-close resolved tickets that have not been reopened within 2 hours.
     *
     * Pass $debounce = true when calling from web requests (e.g. dashboard load)
     * so the check runs at most once every 15 minutes. This prevents a ticket
     * from being closed the instant admin visits the dashboard after resolving it.
     *
     * The cron job calls without debounce (default false = always run).
     *
     * Cron (recommended, every 15 min):
     *   php /path/to/spark tickets:autoclose
     */
    public function autoCloseResolved(bool $debounce = false): void
    {
        if ($debounce) {
            $cache   = \Config\Services::cache();
            $lastRun = $cache->get('autoclose_last_run');
            if ($lastRun !== null && (time() - (int)$lastRun) < 900) {
                return; // ran within the last 15 minutes — skip
            }
            $cache->save('autoclose_last_run', time(), 900);
        }

        $this->db->query("
            UPDATE tickets
            SET    status     = 'closed',
                   updated_at = NOW()
            WHERE  status     = 'resolved'
              AND  resolved_at IS NOT NULL
              AND  resolved_at <= DATE_SUB(NOW(), INTERVAL 2 HOUR)
        ");
    }
}
