<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table                 = 'user_table';
    protected $primaryKey            = 'user_id';
    protected $returnType            = 'array';
    protected $allowedFields         = [
        'name', 'username', 'password', 'email',
        'user_office_id', 'lvl_of_access_id',
        'user_activity_id',
    ];
    protected bool $allowEmptyInserts = false;

    /**
     * Fetch a user with their lvl_of_access resolved through the level_of_access table.
     * Accepts username or email.
     */
    public function findWithLevel(string $usernameOrEmail): ?array
    {
        return $this->db->query(
            'SELECT u.*, COALESCE(loa.lvl_of_access, 0) AS level_id, COALESCE(loa.role, "") AS role
             FROM user_table u
             LEFT JOIN level_of_access loa ON u.lvl_of_access_id = loa.lvl_of_access_id
             WHERE u.username = ? OR u.email = ?',
            [$usernameOrEmail, $usernameOrEmail]
        )->getRowArray();
    }
}
