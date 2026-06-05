<?php

namespace App\Models;

use CodeIgniter\Model;

class TicketStatusLogModel extends Model
{
    protected $table         = 'ticket_status_log';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['ticket_id', 'changed_by_admin', 'changed_by_center', 'old_status', 'new_status', 'note', 'changed_at'];
    protected $useTimestamps = false;

    protected function initialize(): void
    {
        $this->beforeInsert[] = 'setChangedAt';
    }

    protected function setChangedAt(array $data): array
    {
        if (empty($data['data']['changed_at'])) {
            $data['data']['changed_at'] = date('Y-m-d H:i:s');
        }
        return $data;
    }
}
