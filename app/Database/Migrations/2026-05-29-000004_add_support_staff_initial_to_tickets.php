<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSupportStaffInitialToTickets extends Migration
{
    public function up()
    {
        $columns = $this->db->query("SHOW COLUMNS FROM tickets LIKE 'support_staff_initial'")->getResultArray();
        if (empty($columns)) {
            $this->forge->addColumn('tickets', [
                'support_staff_initial' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'support_staff',
                ],
            ]);
        }
    }

    public function down()
    {
        $columns = $this->db->query("SHOW COLUMNS FROM tickets LIKE 'support_staff_initial'")->getResultArray();
        if (! empty($columns)) {
            $this->forge->dropColumn('tickets', 'support_staff_initial');
        }
    }
}
