<?php

namespace App\Models;

use CodeIgniter\Model;

class TicketAttachmentModel extends Model
{
    protected $table         = 'ticket_attachments';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['ticket_id', 'file_name', 'file_path', 'file_type', 'file_size'];
    protected $useTimestamps = false;
}
