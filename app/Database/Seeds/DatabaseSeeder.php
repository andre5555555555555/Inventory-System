<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {

        // ── adjustment_reason ──
        $this->db->table('adjustment_reason')->insertBatch([
            ['reason_name' => 'Correction'],
            ['reason_name' => 'Spoiled'],
            ['reason_name' => 'Damaged'],
            ['reason_name' => 'Lost'],
            ['reason_name' => 'Expired'],
        ]);

        // ── transaction_type ──
        $this->db->table('transaction_type')->insertBatch([
            ['type_name' => 'receipt'],
            ['type_name' => 'issue'],
            ['type_name' => 'adjust_in'],
            ['type_name' => 'adjust_out'],
        ]);

        // ── level_of_access (1=Staff, 2=Custodian, 3=Manager, 4=Technical Staff) ──
        $this->db->table('level_of_access')->insertBatch([
            ['access_level' => 'Staff'],
            ['access_level' => 'Custodian'],
            ['access_level' => 'Manager'],
            ['access_level' => 'Technical Staff'],
        ]);

        // ── user_activity (1=Active, 2=Deactivated, 3=Pending) ──
        $this->db->table('user_activity')->insertBatch([
            ['user_activity' => 'Active'],
            ['user_activity' => 'Deactivated'],
            ['user_activity' => 'Pending'],
        ]);

        // ── roles ──
        $this->db->table('roles')->insertBatch([
            ['role_name' => 'Staff',           'user_office_id' => null, 'level_id' => 1],
            ['role_name' => 'Custodian',       'user_office_id' => null, 'level_id' => 2],
            ['role_name' => 'Manager',         'user_office_id' => null, 'level_id' => 3],
            ['role_name' => 'Technical Staff', 'user_office_id' => null, 'level_id' => 4],
        ]);

        // ── admin@tech.com (Technical Staff – global admin, level 4) ──
        $this->db->table('users')->insert([
            'username'             => 'admin_tech',
            'password'             => password_hash('admin123', PASSWORD_DEFAULT),
            'email'                => 'admin@tech.com',
            'role'                 => 'Technical Staff',
            'user_office_id'       => null,
            'created_at'           => date('Y-m-d H:i:s'),
            'role_id'              => 4,
            'user_activity_id'     => 1,
            'must_change_password' => 1,
        ]);
    }
}
