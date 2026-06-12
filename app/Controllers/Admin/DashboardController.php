<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TicketModel;
use App\Models\ProjectModel;
use App\Models\CenterModel;

/**
 * DashboardController (Admin)
 *
 * Fixes applied:
 *  H6 — Replaced N+1 per-project query loop with a single aggregated GROUP BY query.
 *  M3 — autoCloseResolved() is called here on every admin dashboard load (low-scale option).
 */
class DashboardController extends BaseController
{
    public function index()
    {
        $ticketModel  = new TicketModel();
        $projectModel = new ProjectModel();
        $centerModel  = new CenterModel();

        // FIX (M3): Piggyback auto-close on dashboard load so resolved tickets
        // get closed after 2 hours even without a dedicated cron job.
        // Pass debounce=true so this runs at most once per 15 min — prevents
        // a ticket from auto-closing immediately after the admin resolves it.
        $ticketModel->autoCloseResolved(true);

        $today = date('Y-m-d');
        $activeProjects = $projectModel
            ->where('is_active', 1)
            ->where('end_date >=', $today)
            ->orderBy('start_date', 'ASC')
            ->findAll();

        // FIX (H6): Single aggregated query instead of N+1 per-project queries.
        $projectIds = array_column($activeProjects, 'id');
        $countMap   = [];

        if ($projectIds) {
            $db     = \Config\Database::connect();
            $counts = $db->query(
                "SELECT project_id, status, COUNT(*) AS cnt
                 FROM tickets
                 WHERE project_id IN (" . implode(',', array_map('intval', $projectIds)) . ")
                 GROUP BY project_id, status"
            )->getResultArray();

            foreach ($counts as $row) {
                $countMap[(int)$row['project_id']][$row['status']] = (int)$row['cnt'];
            }
        }

        foreach ($activeProjects as &$p) {
            $pid = (int) $p['id'];
            $p['count_open']        = $countMap[$pid]['open']        ?? 0;
            $p['count_in_progress'] = $countMap[$pid]['in_progress'] ?? 0;
            $p['count_resolved']    = $countMap[$pid]['resolved']    ?? 0;
            $p['count_closed']      = $countMap[$pid]['closed']      ?? 0;
            $p['count_total']       = array_sum($countMap[$pid] ?? []);
        }
        unset($p);

        $data = [
            'title'            => 'Admin Dashboard',
            'total_tickets'    => $ticketModel->countAll(),
            'open_tickets'     => $ticketModel->where('status', 'open')->countAllResults(),
            'in_progress'      => $ticketModel->where('status', 'in_progress')->countAllResults(),
            'resolved_tickets' => $ticketModel->where('status', 'resolved')->countAllResults(),
            'active_projects'  => count($activeProjects),
            'total_centers'    => $centerModel->countAll(),
            'project_tiles'    => $activeProjects,
        ];

        return view('admin/dashboard', $data);
    }

    /**
     * Silent keep-alive ping — does NOT update last_activity.
     * Used by the frontend idle-timeout system.
     */
    public function ping()
    {
        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Lightweight JSON endpoint for background tab-title polling.
     * Returns the current count of open tickets.
     * Called by the browser every 15 s even when the tab is hidden.
     */
    public function openTicketCount()
    {
        $db        = \Config\Database::connect();
        $openCount = (int) $db->query(
            "SELECT COUNT(*) AS cnt FROM tickets WHERE status = 'open'"
        )->getRow()->cnt;

        return $this->response->setJSON(['open' => $openCount]);
    }
}

