<?php

namespace App\Controllers;

use App\Models\UserModel;

class AuthController extends BaseController
{
    public function index()
    {
        if (session()->has('user')) {
            return redirect()->to(site_url('/'));
        }

        return view('auth/login');
    }

    public function createAccount()
    {
        if (session()->has('user')) {
            return redirect()->to(site_url('/'));
        }

        $db = db_connect();

        // Only show the default global roles (not per-office, not Technical Staff)
        $roles = $db->table('roles')
            ->select('roles.*, COALESCE(loa.access_level, "Unknown") AS access_level_name')
            ->join('level_of_access loa', 'roles.level_id = loa.level_id', 'left')
            ->where('roles.user_office_id IS NULL')
            ->where('roles.level_id !=', 4)  // Hide Technical Staff from registration
            ->orderBy('roles.level_id', 'ASC')
            ->get()
            ->getResultArray();

        return view('auth/register', [
            'roles'       => $roles,
            'userOffices' => $db->table('user_office')->orderBy('user_office', 'ASC')->get()->getResultArray(),
        ]);
    }

    public function attempt()
    {
        $rules = [
            'username' => 'required|min_length[3]|max_length[50]',
            'password' => 'required|min_length[3]|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please enter both username and password.');
        }

        $model = new UserModel();
        $user = $model->findWithLevel($this->request->getPost('username'));

        if (! $user || ! $this->passwordMatches($this->request->getPost('password'), $user['password'])) {
            return redirect()->back()->withInput()->with('error', 'Invalid username or password.');
        }

        // ── Check account activation status ──
        $activityId = (int) ($user['user_activity_id'] ?? 3);

        if ($activityId === 3) {
            return redirect()->back()->withInput()->with('error', 'Your account is still pending activation. Please wait for an administrator to activate your account.');
        }

        if ($activityId === 2) {
            return redirect()->back()->withInput()->with('error', 'Your account has been deactivated. Please contact an administrator.');
        }

        // ── Rehash legacy plain-text passwords ──
        if (! password_get_info($user['password'])['algo']) {
            $model->update($user['user_id'], [
                'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            ]);
        }

        $levelId = (int) ($user['level_id'] ?? 0);

        session()->regenerate();
        session()->set('user', [
            'id'                   => (int) $user['user_id'],
            'username'             => $user['username'],
            'email'                => $user['email'] ?? '',
            'role'                 => $user['role'],
            'level_id'             => $levelId,
            'user_office_id'       => $user['user_office_id'] ? (int) $user['user_office_id'] : 0,
            'must_change_password' => (int) ($user['must_change_password'] ?? 0),
        ]);

        // ── Force password change on first login ──
        if ((int) ($user['must_change_password'] ?? 0) === 1) {
            return redirect()->to(site_url('change-password'));
        }

        return redirect()->to(site_url('/'));
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to(site_url('login'));
    }

    public function register()
    {
        $rules = [
            'username'         => 'required|min_length[3]|max_length[50]|is_unique[users.username]',
            'email'            => 'required|valid_email|max_length[255]',
            'password'         => 'required|min_length[3]|max_length[255]',
            'confirm_password' => 'required|matches[password]',
            'role'             => 'required|min_length[2]|max_length[100]',
            'user_office_id'   => 'required|integer|greater_than[0]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to(site_url('register'))->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        // Resolve role_id from role_name
        $db = db_connect();
        $roleName = trim((string) $this->request->getPost('role'));
        $roleRow = $db->table('roles')->where('role_name', $roleName)->get()->getRowArray();
        $roleId = $roleRow ? (int) $roleRow['role_id'] : null;

        $model = new UserModel();
        $model->insert([
            'username'         => trim((string) $this->request->getPost('username')),
            'email'            => trim((string) $this->request->getPost('email')),
            'password'         => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'role'             => $roleName,
            'user_office_id'   => (int) $this->request->getPost('user_office_id'),
            'role_id'          => $roleId,
            'user_activity_id' => 3, // Pending – must be activated by admin
        ]);

        return redirect()->to(site_url('login'))->with('success', 'Account created successfully. Please wait for an administrator to activate your account.');
    }

    // ── Change password (first-login prompt) ──

    public function changePasswordView()
    {
        if (! session()->has('user')) {
            return redirect()->to(site_url('login'));
        }

        return view('auth/change_password');
    }

    public function changePassword()
    {
        if (! session()->has('user')) {
            return redirect()->to(site_url('login'));
        }

        $rules = [
            'password'         => 'required|min_length[3]|max_length[255]',
            'confirm_password' => 'required|matches[password]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('error', 'Passwords do not match or are too short.');
        }

        $userId = (int) session('user')['id'];
        $model = new UserModel();
        $model->update($userId, [
            'password'             => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'must_change_password' => 0,
        ]);

        // Update session flag
        $userData = session('user');
        $userData['must_change_password'] = 0;
        session()->set('user', $userData);

        return redirect()->to(site_url('/'))->with('success', 'Password changed successfully.');
    }

    private function passwordMatches(string $input, string $stored): bool
    {
        if (password_get_info($stored)['algo']) {
            return password_verify($input, $stored);
        }

        return hash_equals($stored, $input);
    }
}
