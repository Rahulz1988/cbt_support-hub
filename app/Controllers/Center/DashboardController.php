<?php
namespace App\Controllers\Center;
use App\Controllers\BaseController;
use App\Models\TicketModel;
use App\Models\CenterModel;
use App\Models\ProjectModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $centerId  = (int) session()->get('center_id');
        $projectId = (int) session()->get('project_id');

        $centerModel  = new CenterModel();
        $projectModel = new ProjectModel();
        $ticketModel  = new TicketModel();

        $center  = $centerModel->find($centerId);
        $project = $projectModel->find($projectId);

        // Ticket counts for this center+project — single query
        $db = \Config\Database::connect();
        $rows = $db->query(
            "SELECT status, COUNT(*) as cnt FROM tickets WHERE center_id = ? AND project_id = ? GROUP BY status",
            [$centerId, $projectId]
        )->getResultArray();
        $counts = ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];
        foreach ($rows as $row) {
            if (isset($counts[$row['status']])) $counts[$row['status']] = (int)$row['cnt'];
        }

        return view('center/dashboard', [
            'title'   => 'Center Dashboard',
            'center'  => $center,
            'project' => $project,
            'counts'  => $counts,
        ]);
    }
}
