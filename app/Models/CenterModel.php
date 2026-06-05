<?php

namespace App\Models;

use CodeIgniter\Model;

class CenterModel extends Model
{
    protected $table         = 'centers';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['center_code', 'center_name', 'city', 'state', 'contact_name', 'contact_phone', 'is_active'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
