<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ProjectModel
 *
 * `otp_encrypted` — AES-256 encrypted OTP, stored for admin display
 * `otp_hash` — bcrypt hash, used by AuthController for center login verification
 * Both are always written together and kept in sync.
 */
class ProjectModel extends Model
{
    protected $table         = 'projects';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'name', 'description', 'start_date', 'end_date',
        'otp_encrypted',    // encrypted OTP — admin display
        'otp_hash',         // bcrypt hash — login verification
        'is_active', 'manually_disabled', 'created_by',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
