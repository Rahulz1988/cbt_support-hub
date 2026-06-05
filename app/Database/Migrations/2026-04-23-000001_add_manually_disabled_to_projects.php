<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddManuallyDisabledToProjects extends Migration
{
    public function up(): void
    {
        $this->db->query("
            ALTER TABLE projects
            ADD COLUMN manually_disabled TINYINT(1) NOT NULL DEFAULT 0
                COMMENT '1 = admin explicitly cancelled/disabled; auto-activation will never override this'
            AFTER is_active
        ");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE projects DROP COLUMN manually_disabled");
    }
}
