<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table                 = 'users';
    protected $primaryKey            = 'user_id';
    protected $returnType            = 'array';
    protected $allowedFields         = [
        'username', 'password', 'email', 'role',
        'user_office_id', 'role_id',
        'user_activity_id', 'must_change_password',
    ];
    protected bool $allowEmptyInserts = false;

    /**
     * Fetch a user with their level_id resolved through the roles table.
     * Accepts username or email.
     */
    public function findWithLevel(string $usernameOrEmail): ?array
    {
        return $this->db->query(
            'SELECT u.*, COALESCE(r.level_id, 0) AS level_id
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.role_id
             WHERE u.username = ? OR u.email = ?',
            [$usernameOrEmail, $usernameOrEmail]
        )->getRowArray();
    }
}
