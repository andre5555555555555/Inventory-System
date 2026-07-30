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

        // Show all levels except Technical Staff (level 4) for self-registration
        $levels = $db->table('level_of_access')
            ->where('lvl_of_access !=', 4)
            ->orderBy('lvl_of_access', 'ASC')
            ->get()
            ->getResultArray();

        return view('auth/register', [
            'levels'      => $levels,
            'userOffices' => $db->table('user_office_table')->orderBy('user_office_name', 'ASC')->get()->getResultArray(),
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
        $user  = $model->findWithLevel($this->request->getPost('username'));

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

        // level_id comes from level_of_access.lvl_of_access (the numeric level)
        $levelId = (int) ($user['level_id'] ?? 0);

        session()->regenerate();
        session()->set('user', [
            'id'             => (int) $user['user_id'],
            'username'       => $user['username'],
            'email'          => $user['email'] ?? '',
            'role'           => $user['role'] ?? '',
            'level_id'       => $levelId,
            'user_office_id' => $user['user_office_id'] ? (int) $user['user_office_id'] : 0,
        ]);
        session()->set('login_time', time()); // for 24-hour auto-logout

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
            'name'             => 'required|min_length[2]|max_length[150]',
            'username'         => 'required|min_length[3]|max_length[50]|is_unique[user_table.username]',
            'email'            => 'required|valid_email|max_length[255]',
            'password'         => 'required|min_length[6]|max_length[255]',
            'confirm_password' => 'required|matches[password]',
            'lvl_of_access_id' => 'required|integer|greater_than[0]',
            'user_office_id'   => 'required|integer|greater_than[0]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to(site_url('register'))->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        // ── Strong password checks ──
        $password = (string) $this->request->getPost('password');

        if (! preg_match('/[A-Z]/', $password)) {
            return redirect()->to(site_url('register'))->withInput()->with('error', 'Password must contain at least one uppercase letter.');
        }
        if (! preg_match('/[a-z]/', $password)) {
            return redirect()->to(site_url('register'))->withInput()->with('error', 'Password must contain at least one lowercase letter.');
        }
        if (! preg_match('/[0-9]/', $password)) {
            return redirect()->to(site_url('register'))->withInput()->with('error', 'Password must contain at least one number.');
        }
        // No sequential numbers (e.g. 123, 234, 345…)
        if (preg_match('/(?:0(?=1)|1(?=2)|2(?=3)|3(?=4)|4(?=5)|5(?=6)|6(?=7)|7(?=8)|8(?=9)){2}/', $password)) {
            return redirect()->to(site_url('register'))->withInput()->with('error', 'Password must not contain sequential numbers (e.g. 123, 456).');
        }

        $model = new UserModel();
        $model->insert([
            'name'              => trim((string) $this->request->getPost('name')),
            'username'          => trim((string) $this->request->getPost('username')),
            'email'             => trim((string) $this->request->getPost('email')),
            'password'          => password_hash($password, PASSWORD_DEFAULT),
            'user_office_id'    => (int) $this->request->getPost('user_office_id'),
            'lvl_of_access_id'  => (int) $this->request->getPost('lvl_of_access_id'),
            'user_activity_id'  => 3, // Pending – must be activated by admin
        ]);

        return redirect()->to(site_url('login'))->with('success', 'Account created successfully. Please wait for an administrator to activate your account.');
    }

    private function passwordMatches(string $input, string $stored): bool
    {
        if (password_get_info($stored)['algo']) {
            return password_verify($input, $stored);
        }
        return hash_equals($stored, $input);
    }
}
