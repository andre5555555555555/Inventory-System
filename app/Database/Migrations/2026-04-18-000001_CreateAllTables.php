<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAllTables extends Migration
{
    public function up()
    {
        // ── 1. user_office (parent for multi-office isolation) ──
        $this->forge->addField([
            'user_office_id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'user_office'    => ['type' => 'VARCHAR', 'constraint' => 255],
        ]);
        $this->forge->addPrimaryKey('user_office_id');
        $this->forge->createTable('user_office', true);

        // ── 2. adjustment_reason (global lookup) ──
        $this->forge->addField([
            'reason_id'   => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'reason_name' => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->forge->addPrimaryKey('reason_id');
        $this->forge->createTable('adjustment_reason', true);

        // ── 3. transaction_type (global lookup) ──
        $this->forge->addField([
            'transaction_type_id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'type_name'           => ['type' => 'VARCHAR', 'constraint' => 50],
        ]);
        $this->forge->addPrimaryKey('transaction_type_id');
        $this->forge->createTable('transaction_type', true);

        // ── 4. level_of_access (global lookup) ──
        $this->forge->addField([
            'level_id'     => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'access_level' => ['type' => 'VARCHAR', 'constraint' => 1000],
        ]);
        $this->forge->addPrimaryKey('level_id');
        $this->forge->createTable('level_of_access', true);

        // ── 5. user_activity (global lookup) ──
        $this->forge->addField([
            'user_activity_id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'user_activity'    => ['type' => 'VARCHAR', 'constraint' => 50],
        ]);
        $this->forge->addPrimaryKey('user_activity_id');
        $this->forge->createTable('user_activity', true);

        // ── 6. entity ──
        $this->forge->addField([
            'entity_id'      => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'entity_name'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'fund_cluster'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_office_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('entity_id');
        $this->forge->addKey('user_office_id');
        $this->forge->addForeignKey('user_office_id', 'user_office', 'user_office_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('entity', true);

        // ── 7. unit ──
        $this->forge->addField([
            'unit_id'        => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'unit'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_office_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('unit_id');
        $this->forge->addKey('user_office_id');
        $this->forge->addForeignKey('user_office_id', 'user_office', 'user_office_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('unit', true);

        // ── 8. item_type ──
        $this->forge->addField([
            'item_type_id'   => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'item_type'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_office_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('item_type_id');
        $this->forge->addKey('user_office_id');
        $this->forge->addForeignKey('user_office_id', 'user_office', 'user_office_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('item_type', true);

        // ── 9. item_category ──
        $this->forge->addField([
            'item_category_id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'item_category'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_office_id'   => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('item_category_id');
        $this->forge->addKey('user_office_id');
        $this->forge->addForeignKey('user_office_id', 'user_office', 'user_office_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('item_category', true);

        // ── 10. reference ──
        $this->forge->addField([
            'reference_id'   => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'reference'      => ['type' => 'VARCHAR', 'constraint' => 11],
            'user_office_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('reference_id');
        $this->forge->addKey('user_office_id');
        $this->forge->addForeignKey('user_office_id', 'user_office', 'user_office_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('reference', true);

        // ── 11. roles ──
        $this->forge->addField([
            'role_id'        => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'role_name'      => ['type' => 'VARCHAR', 'constraint' => 50],
            'user_office_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'level_id'       => ['type' => 'INT', 'constraint' => 11],
        ]);
        $this->forge->addPrimaryKey('role_id');
        $this->forge->addKey('user_office_id');
        $this->forge->addKey('level_id');
        $this->forge->addForeignKey('user_office_id', 'user_office', 'user_office_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('level_id', 'level_of_access', 'level_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('roles', true);

        // ── 12. office ──
        $this->forge->addField([
            'office_id'      => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'office'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_office_id' => ['type' => 'INT', 'constraint' => 11],
        ]);
        $this->forge->addPrimaryKey('office_id');
        $this->forge->addForeignKey('user_office_id', 'user_office', 'user_office_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('office', true);

        // ── 13. item ──
        $this->forge->addField([
            'item_id'          => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'item_no'          => ['type' => 'INT', 'constraint' => 11],
            'item'             => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'      => ['type' => 'VARCHAR', 'constraint' => 1000],
            're_order_point'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'entity_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'unit_id'          => ['type' => 'INT', 'constraint' => 11],
            'item_type_id'     => ['type' => 'INT', 'constraint' => 11],
            'item_category_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'user_office_id'   => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('item_id');
        $this->forge->addKey('unit_id');
        $this->forge->addKey('item_category_id');
        $this->forge->addKey('item_type_id');
        $this->forge->addKey('user_office_id');
        $this->forge->addKey('entity_id');
        $this->forge->addForeignKey('unit_id', 'unit', 'unit_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('item_category_id', 'item_category', 'item_category_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('item_type_id', 'item_type', 'item_type_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('entity_id', 'entity', 'entity_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('item', true);

        // ── 14. users ──
        $this->forge->addField([
            'user_id'              => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'username'             => ['type' => 'VARCHAR', 'constraint' => 50],
            'password'             => ['type' => 'VARCHAR', 'constraint' => 255],
            'email'                => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
            'role'                 => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => 'staff'],
            'user_office_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'role_id'              => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'user_activity_id'     => ['type' => 'INT', 'constraint' => 11, 'default' => 3],
            'must_change_password' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('user_id');
        $this->forge->addUniqueKey('username');
        $this->forge->addKey('role_id');
        $this->forge->addKey('user_office_id');
        $this->forge->addKey('user_activity_id');
        $this->forge->addForeignKey('user_office_id', 'user_office', 'user_office_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('role_id', 'roles', 'role_id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('user_activity_id', 'user_activity', 'user_activity_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('users', true);

        // ── 15. transaction ──
        $this->forge->addField([
            'transaction_id'       => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'date'                 => ['type' => 'DATETIME', 'null' => true],
            'receipt_qty'          => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'issue_qty'            => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'day_consume'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'expiration_date'      => ['type' => 'DATE', 'null' => true],
            'adjustment_reason_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'user_office_id'       => ['type' => 'INT', 'constraint' => 11],
        ]);
        $this->forge->addPrimaryKey('transaction_id');
        $this->forge->addKey('adjustment_reason_id');
        $this->forge->addKey('user_office_id');
        $this->forge->addForeignKey('adjustment_reason_id', 'adjustment_reason', 'reason_id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('user_office_id', 'user_office', 'user_office_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('transaction', true);

        // ── 16. stockcard ──
        $this->forge->addField([
            'stockcard_id'        => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'transaction_id'      => ['type' => 'INT', 'constraint' => 11],
            'reference_id'        => ['type' => 'INT', 'constraint' => 11],
            'office_id'           => ['type' => 'INT', 'constraint' => 11],
            'item_id'             => ['type' => 'INT', 'constraint' => 11],
            'transaction_type_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'user_office_id'      => ['type' => 'INT', 'constraint' => 11],
        ]);
        $this->forge->addPrimaryKey('stockcard_id');
        $this->forge->addKey('item_id');
        $this->forge->addKey('office_id');
        $this->forge->addKey('reference_id');
        $this->forge->addKey('transaction_id');
        $this->forge->addKey('transaction_type_id');
        $this->forge->addKey('user_office_id');
        $this->forge->addForeignKey('item_id', 'item', 'item_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('office_id', 'office', 'office_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('reference_id', 'reference', 'reference_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('transaction_id', 'transaction', 'transaction_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('transaction_type_id', 'transaction_type', 'transaction_type_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_office_id', 'user_office', 'user_office_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('stockcard', true);

        // ── 17. batch ──
        $this->forge->addField([
            'batch_id'        => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'transaction_id'  => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'item_id'         => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'expiration_date' => ['type' => 'DATE', 'null' => true],
            'quantity'        => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'remaining_qty'   => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'unit_cost'       => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'user_office_id'  => ['type' => 'INT', 'constraint' => 11],
        ]);
        $this->forge->addPrimaryKey('batch_id');
        $this->forge->addKey('transaction_id');
        $this->forge->addKey(['item_id', 'remaining_qty']);
        $this->forge->addKey('item_id');
        $this->forge->addForeignKey('transaction_id', 'transaction', 'transaction_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('item_id', 'item', 'item_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('batch', true);

        // ── 18. temp_stockout (stock-out request header) ──
        $this->forge->addField([
            'temp_stockout_id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT', 'constraint' => 11],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'approved_by'      => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'approved_at'      => ['type' => 'DATETIME', 'null' => true],
            'user_office_id'   => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('temp_stockout_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('user_office_id');
        $this->forge->addForeignKey('user_id', 'users', 'user_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_office_id', 'user_office', 'user_office_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('temp_stockout', true);

        // ── 19. temp_stockout_item (line items for stock-out request) ──
        $this->forge->addField([
            'temp_stockout_item_id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'temp_stockout_id'      => ['type' => 'INT', 'constraint' => 11],
            'item_id'               => ['type' => 'INT', 'constraint' => 11],
            'quantity'              => ['type' => 'INT', 'constraint' => 11],
            'unit'                  => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'           => ['type' => 'VARCHAR', 'constraint' => 1000, 'default' => ''],
            'status'                => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
        ]);
        $this->forge->addPrimaryKey('temp_stockout_item_id');
        $this->forge->addKey('temp_stockout_id');
        $this->forge->addKey('item_id');
        $this->forge->addForeignKey('temp_stockout_id', 'temp_stockout', 'temp_stockout_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('item_id', 'item', 'item_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('temp_stockout_item', true);
    }

    public function down()
    {
        $this->forge->dropTable('temp_stockout_item', true);
        $this->forge->dropTable('temp_stockout', true);
        $this->forge->dropTable('batch', true);
        $this->forge->dropTable('stockcard', true);
        $this->forge->dropTable('transaction', true);
        $this->forge->dropTable('users', true);
        $this->forge->dropTable('item', true);
        $this->forge->dropTable('office', true);
        $this->forge->dropTable('roles', true);
        $this->forge->dropTable('reference', true);
        $this->forge->dropTable('item_category', true);
        $this->forge->dropTable('item_type', true);
        $this->forge->dropTable('unit', true);
        $this->forge->dropTable('entity', true);
        $this->forge->dropTable('user_activity', true);
        $this->forge->dropTable('level_of_access', true);
        $this->forge->dropTable('transaction_type', true);
        $this->forge->dropTable('adjustment_reason', true);
        $this->forge->dropTable('user_office', true);
    }
}
