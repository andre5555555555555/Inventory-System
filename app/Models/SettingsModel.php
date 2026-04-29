<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Model;

class SettingsModel extends Model
{
    protected $table = 'users';

    /**
     * Return definitions filtered by the caller's access level.
     * Level 2: inventory settings only (no users)
     * Level 3: inventory settings + users (within office)
     * Level 4: everything + user_office management
     */
    public function definitions(int $levelId = 2): array
    {
        $allDefinitions = $this->allDefinitions();

        // Level 4 (Technical Staff): only user + user_office management
        if ($levelId >= 4) {
            return [
                'users' => $allDefinitions['users'],
                'user_office' => $allDefinitions['user_office'],
            ];
        }

        $defs = [
            'entity' => $allDefinitions['entity'],
            'unit' => $allDefinitions['unit'],
            'roles' => $allDefinitions['roles'],
            'reference' => $allDefinitions['reference'],
            'item_type' => $allDefinitions['item_type'],
            'item_category' => $allDefinitions['item_category'],
            'office' => $allDefinitions['office'],
        ];

        // Level 3: add user management
        if ($levelId >= 3) {
            $defs = array_merge(
                ['users' => $allDefinitions['users']],
                $defs
            );
        }

        return $defs;
    }

    private function allDefinitions(): array
    {
        return [
            'users' => [
                'table'  => 'users',
                'pk'     => 'user_id',
                'fields' => ['username', 'email', 'password', 'role', 'user_office_id'],
                'labels' => ['username' => 'Username', 'email' => 'Email', 'password' => 'Password', 'role' => 'Role', 'user_office_id' => 'User Office'],
            ],
            'entity' => [
                'table'  => 'entity',
                'pk'     => 'entity_id',
                'fields' => ['entity_name', 'fund_cluster'],
                'labels' => ['entity_name' => 'Entity Name', 'fund_cluster' => 'Fund Cluster'],
            ],
            'unit' => [
                'table'  => 'unit',
                'pk'     => 'unit_id',
                'fields' => ['unit'],
                'labels' => ['unit' => 'Unit'],
            ],
            'roles' => [
                'table'  => 'roles',
                'pk'     => 'role_id',
                'fields' => ['role_name', 'level_id'],
                'labels' => ['role_name' => 'Role Name', 'level_id' => 'Level of Access'],
            ],
            'reference' => [
                'table'  => 'reference',
                'pk'     => 'reference_id',
                'fields' => ['reference'],
                'labels' => ['reference' => 'Reference'],
            ],
            'item_type' => [
                'table'  => 'item_type',
                'pk'     => 'item_type_id',
                'fields' => ['item_type'],
                'labels' => ['item_type' => 'Item Type'],
            ],
            'item_category' => [
                'table'  => 'item_category',
                'pk'     => 'item_category_id',
                'fields' => ['item_category'],
                'labels' => ['item_category' => 'Category'],
            ],
            'office' => [
                'table'  => 'office',
                'pk'     => 'office_id',
                'fields' => ['office'],
                'labels' => ['office' => 'Office Name'],
            ],
            'user_office' => [
                'table'  => 'user_office',
                'pk'     => 'user_office_id',
                'fields' => ['user_office'],
                'labels' => ['user_office' => 'User Office Name'],
            ],
        ];
    }

    public function indexData(int $userOfficeId = 0, int $levelId = 2): array
    {
        $definitions = $this->definitions($levelId);

        $records = [];
        foreach (array_keys($definitions) as $type) {
            if ($type === 'users') {
                $records['users'] = $this->userRecords($userOfficeId, $levelId);
            } elseif ($type === 'user_office') {
                $records['user_office'] = $this->db->table('user_office')->orderBy('user_office', 'ASC')->get()->getResultArray();
            } elseif ($type === 'roles') {
                $records['roles'] = $this->rolesWithLevel($userOfficeId);
            } else {
                $orderCol = match ($type) {
                    'entity'        => 'entity_name',
                    'unit'          => 'unit',
                    'reference'     => 'reference',
                    'item_type'     => 'item_type',
                    'item_category' => 'item_category',
                    'office'        => 'office',
                    default         => $definitions[$type]['pk'],
                };
                $records[$type] = $this->orderedRecords($type, $orderCol, $userOfficeId);
            }
        }

        // Pending users for Level 3 (within office) and Level 4 (all)
        $pendingUsers = [];
        if ($levelId >= 3) {
            $pendingUsers = $this->pendingUsers($userOfficeId, $levelId);
        }

        return [
            'definitions'  => $definitions,
            'records'      => $records,
            'roles'        => $this->rolesWithLevel($userOfficeId),
            'offices'      => $this->orderedRecords('office', 'office', $userOfficeId),
            'userOffices'  => $this->db->table('user_office')->orderBy('user_office', 'ASC')->get()->getResultArray(),
            'levels'       => $this->db->table('level_of_access')->orderBy('level_id', 'ASC')->get()->getResultArray(),
            'pendingUsers' => $pendingUsers,
            'levelId'      => $levelId,
        ];
    }

    /**
     * Fetch roles joined with level_of_access for display.
     */
    private function rolesWithLevel(int $userOfficeId = 0): array
    {
        $builder = $this->db->table('roles')
            ->select('roles.*, COALESCE(loa.access_level, "Unknown") AS access_level_name')
            ->join('level_of_access loa', 'roles.level_id = loa.level_id', 'left')
            ->orderBy('roles.role_name', 'ASC');

        if ($userOfficeId > 0) {
            $builder->groupStart()
                ->where('roles.user_office_id', $userOfficeId)
                ->orWhere('roles.user_office_id IS NULL')
                ->groupEnd();
        }

        return $builder->get()->getResultArray();
    }

    public function definition(string $type, int $levelId = 2): array
    {
        $definitions = $this->definitions($levelId);

        if (! array_key_exists($type, $definitions)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $definitions[$type];
    }

    public function fetchRecord(string $type, int $id): array
    {
        $allDefs = $this->allDefinitions();
        if (! array_key_exists($type, $allDefs)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $definition = $allDefs[$type];

        $row = $this->db->table($definition['table'])
            ->where($definition['pk'], $id)
            ->get()
            ->getRowArray() ?? [];

        if ($type === 'users') {
            unset($row['password']);
        }

        return $row;
    }

    public function saveRecord(string $type, int $id, array $payload, int $userOfficeId = 0): void
    {
        $allDefs = $this->allDefinitions();
        if (! array_key_exists($type, $allDefs)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $definition = $allDefs[$type];

        // Auto-inject user_office_id for tables that have it (except users, user_office)
        if (! in_array($type, ['users', 'user_office'], true) && $userOfficeId > 0) {
            $payload['user_office_id'] = $userOfficeId;
        }

        if ($id > 0) {
            $this->db->table($definition['table'])
                ->where($definition['pk'], $id)
                ->update($payload);
            return;
        }

        $this->db->table($definition['table'])->insert($payload);
    }

    public function deleteRecord(string $type, int $id): void
    {
        $allDefs = $this->allDefinitions();
        if (! array_key_exists($type, $allDefs)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $definition = $allDefs[$type];

        $this->db->transException(true)->transStart();
        $this->preserveRelationsBeforeDelete($this->db, $type, $id);
        $this->db->table($definition['table'])
            ->where($definition['pk'], $id)
            ->delete();
        $this->db->transComplete();
    }

    /**
     * Fetch pending users.
     * Level 3: only within their office
     * Level 4: all pending users
     */
    public function pendingUsers(int $userOfficeId = 0, int $levelId = 3): array
    {
        $builder = $this->db->table('users')
            ->select('users.*, COALESCE(user_office.user_office, "Global") AS user_office_name')
            ->join('user_office', 'users.user_office_id = user_office.user_office_id', 'left')
            ->where('users.user_activity_id', 3)
            ->orderBy('users.created_at', 'ASC');

        if ($levelId < 4 && $userOfficeId > 0) {
            $builder->where('users.user_office_id', $userOfficeId);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Activate a user (set user_activity_id = 1).
     */
    public function activateUser(int $userId): void
    {
        $this->db->table('users')
            ->where('user_id', $userId)
            ->update(['user_activity_id' => 1]);
    }

    /**
     * Deactivate a user (set user_activity_id = 2).
     */
    public function deactivateUser(int $userId): void
    {
        $this->db->table('users')
            ->where('user_id', $userId)
            ->update(['user_activity_id' => 2]);
    }

    private function orderedRecords(string $table, string $orderBy, int $userOfficeId = 0): array
    {
        $builder = $this->db->table($table)
            ->orderBy($orderBy, 'ASC');

        if ($userOfficeId > 0) {
            $builder->where('user_office_id', $userOfficeId);
        }

        return $builder->get()->getResultArray();
    }

    private function userRecords(int $userOfficeId = 0, int $levelId = 2): array
    {
        $sql = 'SELECT users.user_id, users.username, users.email, users.role, users.user_office_id,
                       users.user_activity_id,
                       COALESCE(user_office.user_office, "Global") AS user_office_name,
                       COALESCE(ua.user_activity, "Unknown") AS activity_status
                FROM users
                LEFT JOIN user_office ON users.user_office_id = user_office.user_office_id
                LEFT JOIN user_activity ua ON users.user_activity_id = ua.user_activity_id';

        $conditions = [];
        $params = [];

        // Level 3: see only users in their office
        // Level 4: see all users
        if ($levelId < 4 && $userOfficeId > 0) {
            $conditions[] = 'users.user_office_id = ?';
            $params[] = $userOfficeId;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY users.username ASC';

        return $this->db->query($sql, $params)->getResultArray();
    }

    private function preserveRelationsBeforeDelete(BaseConnection $db, string $type, int $id): void
    {
        match ($type) {
            'entity' => $db->table('item')->where('entity_id', $id)->set('entity_id', null)->update(),
            'unit' => $db->table('item')->where('unit_id', $id)->set('unit_id', null)->update(),
            'reference' => $db->table('stockcard')->where('reference_id', $id)->set('reference_id', null)->update(),
            'item_type' => $db->table('item')->where('item_type_id', $id)->set('item_type_id', null)->update(),
            'item_category' => $db->table('item')->where('item_category_id', $id)->set('item_category_id', null)->update(),
            'office' => $this->clearOfficeRelations($db, $id),
            default => null,
        };
    }

    private function clearOfficeRelations(BaseConnection $db, int $officeId): void
    {
        $db->table('stockcard')->where('office_id', $officeId)->set('office_id', null)->update();
    }
}
