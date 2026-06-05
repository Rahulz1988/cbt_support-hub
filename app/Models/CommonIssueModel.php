<?php
namespace App\Models;
use CodeIgniter\Model;

class CommonIssueModel extends Model
{
    protected $table         = 'common_issues';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['issue_text', 'is_active', 'sort_order'];
    protected $useTimestamps = false;

    public function getActive(): array
    {
        return $this->where('is_active', 1)
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('issue_text', 'ASC')
                    ->findAll();
    }
}
