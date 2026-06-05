<?php

namespace App\Models;

use CodeIgniter\Model;

class ProjectCenterModel extends Model
{
    protected $table         = 'project_centers';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['project_id', 'center_id'];
    protected $useTimestamps = false;

    /**
     * Get all centers assigned to a project, with full center details.
     */
    public function getCentersForProject(int $projectId): array
    {
        return $this->db->table('project_centers pc')
            ->select('c.id, c.center_code, c.center_name, c.city, c.state, c.contact_name, c.contact_phone')
            ->join('centers c', 'c.id = pc.center_id')
            ->where('pc.project_id', $projectId)
            ->orderBy('c.center_code', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Assign an array of center IDs to a project (ignores duplicates).
     */
    public function assignCenters(int $projectId, array $centerIds): void
    {
        $unique = array_unique($centerIds);
        if (empty($unique)) return;

        // Fetch already-assigned center IDs in one query
        $existing = array_column(
            $this->where('project_id', $projectId)->findAll(),
            'center_id'
        );

        $toInsert = [];
        foreach ($unique as $centerId) {
            if (! in_array($centerId, $existing)) {
                $toInsert[] = ['project_id' => $projectId, 'center_id' => (int)$centerId];
            }
        }
        if ($toInsert) {
            $this->insertBatch($toInsert);
        }
    }

    /**
     * Replace all center assignments for a project.
     */
    public function replaceAssignments(int $projectId, array $centerIds): void
    {
        $this->where('project_id', $projectId)->delete();
        foreach (array_unique($centerIds) as $centerId) {
            $this->insert(['project_id' => $projectId, 'center_id' => $centerId]);
        }
    }
}
