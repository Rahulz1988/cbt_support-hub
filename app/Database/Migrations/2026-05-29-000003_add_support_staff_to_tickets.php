<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSupportStaffToTickets extends Migration
{
    public function up()
    {
        // Add support_staff column to tickets table if it doesn't exist
        $columns = $this->db->query("SHOW COLUMNS FROM tickets LIKE 'support_staff'")->getResultArray();
        if (empty($columns)) {
            $this->forge->addColumn('tickets', [
                'support_staff' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'admin_notes',
                ],
            ]);
        }
    }

    public function down()
    {
        $columns = $this->db->query("SHOW COLUMNS FROM tickets LIKE 'support_staff'")->getResultArray();
        if (! empty($columns)) {
            $this->forge->dropColumn('tickets', 'support_staff');
        }
    }
}
