<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeStockcardNullable extends Migration
{
    public function up()
    {
        // Make reference_id nullable so stock-out approvals can omit it
        $this->forge->modifyColumn('stockcard', [
            'reference_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'office_id'    => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('stockcard', [
            'reference_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'office_id'    => ['type' => 'INT', 'constraint' => 11, 'null' => false],
        ]);
    }
}
