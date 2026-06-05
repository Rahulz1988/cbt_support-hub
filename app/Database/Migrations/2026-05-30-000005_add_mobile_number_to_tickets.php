<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMobileNumberToTickets extends Migration
{
    public function up()
    {
        $columns = $this->db->query("SHOW COLUMNS FROM tickets LIKE 'mobile_number'")->getResultArray();
        if (empty($columns)) {
            $this->forge->addColumn('tickets', [
                'mobile_number' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'anydesk_id',
                ],
            ]);
        }
    }

    public function down()
    {
        $columns = $this->db->query("SHOW COLUMNS FROM tickets LIKE 'mobile_number'")->getResultArray();
        if (! empty($columns)) {
            $this->forge->dropColumn('tickets', 'mobile_number');
        }
    }
}
