<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\SmtpSettingsModel;

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

        // ── Check if user must change password (first login) ──
        if ((int) ($user['must_change_password'] ?? 0) === 1) {
            session()->set('must_change_password', true);
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
            'name'             => 'required|min_length[2]|max_length[150]',
            'username'         => 'required|min_length[3]|max_length[50]|is_unique[user_table.username]',
            'email'            => 'required|valid_email|max_length[255]|is_unique[user_table.email]',
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

    // ════════════════════════════════════════════════════════════════
    //  CHANGE PASSWORD (first-login forced + general)
    // ════════════════════════════════════════════════════════════════

    public function changePasswordView()
    {
        return view('auth/change_password');
    }

    public function changePassword()
    {
        $rules = [
            'password'         => 'required|min_length[6]|max_length[255]',
            'confirm_password' => 'required|matches[password]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $password = (string) $this->request->getPost('password');

        // ── Strong password checks ──
        if (! preg_match('/[A-Z]/', $password)) {
            return redirect()->back()->with('error', 'Password must contain at least one uppercase letter.');
        }
        if (! preg_match('/[a-z]/', $password)) {
            return redirect()->back()->with('error', 'Password must contain at least one lowercase letter.');
        }
        if (! preg_match('/[0-9]/', $password)) {
            return redirect()->back()->with('error', 'Password must contain at least one number.');
        }
        if (preg_match('/(?:0(?=1)|1(?=2)|2(?=3)|3(?=4)|4(?=5)|5(?=6)|6(?=7)|7(?=8)|8(?=9)){2}/', $password)) {
            return redirect()->back()->with('error', 'Password must not contain sequential numbers (e.g. 123, 456).');
        }

        $userId = (int) session('user')['id'];
        $model  = new UserModel();
        $model->update($userId, [
            'password'             => password_hash($password, PASSWORD_DEFAULT),
            'must_change_password' => 0,
        ]);

        $isFirstLogin = session('must_change_password');
        session()->remove('must_change_password');

        // If this was a first-login password change for level 4, redirect to SMTP setup
        $levelId = (int) (session('user')['level_id'] ?? 0);
        if ($isFirstLogin && $levelId >= 4) {
            // Check if SMTP is already configured
            $smtpModel = new SmtpSettingsModel();
            $existing  = $smtpModel->getActive();
            if (! $existing) {
                session()->set('must_setup_smtp', true);
                return redirect()->to(site_url('setup-smtp'))->with('success', 'Password changed successfully. Now please configure the email settings for password recovery.');
            }
        }

        return redirect()->to(site_url('/'))->with('success', 'Password changed successfully.');
    }

    // ════════════════════════════════════════════════════════════════
    //  SMTP SETUP (admin_tech first-login step 2)
    // ════════════════════════════════════════════════════════════════

    public function setupSmtpView()
    {
        $levelId = (int) (session('user')['level_id'] ?? 0);
        if ($levelId < 4) {
            return redirect()->to(site_url('/'))->with('error', 'You do not have permission to access that page.');
        }

        return view('auth/setup_smtp');
    }

    public function setupSmtp()
    {
        $levelId = (int) (session('user')['level_id'] ?? 0);
        if ($levelId < 4) {
            return redirect()->to(site_url('/'))->with('error', 'You do not have permission to access that page.');
        }

        $rules = [
            'smtp_email'    => 'required|valid_email|max_length[255]',
            'smtp_password' => 'required|min_length[8]|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $encrypter     = service('encrypter');
        $smtpEmail     = trim((string) $this->request->getPost('smtp_email'));
        $smtpPassword  = (string) $this->request->getPost('smtp_password');

        $smtpModel = new SmtpSettingsModel();

        // Upsert: update existing or create new
        $existing = $smtpModel->getActive();
        $data = [
            'smtp_email'    => $smtpEmail,
            'smtp_password' => base64_encode($encrypter->encrypt($smtpPassword)),
            'configured_by' => (int) session('user')['id'],
        ];

        if ($existing) {
            $smtpModel->update($existing['id'], $data);
        } else {
            $smtpModel->insert($data);
        }

        session()->remove('must_setup_smtp');

        // ── Step 3: ensure the admin has a personal recovery email set ──
        $userId    = (int) session('user')['id'];
        $userModel = new UserModel();
        $adminUser = $userModel->find($userId);

        if (empty(trim((string) ($adminUser['email'] ?? '')))) {
            session()->set('must_setup_recovery_email', true);
            return redirect()->to(site_url('setup-recovery-email'))->with('success', 'Email settings configured. Now set your personal recovery email.');
        }

        return redirect()->to(site_url('/'))->with('success', 'Email settings configured successfully. Password recovery is now available.');
    }

    // ════════════════════════════════════════════════════════════════
    //  RECOVERY EMAIL SETUP (admin_tech first-login step 3)
    // ════════════════════════════════════════════════════════════════

    public function setupRecoveryEmailView()
    {
        $levelId = (int) (session('user')['level_id'] ?? 0);
        if ($levelId < 4) {
            return redirect()->to(site_url('/'))->with('error', 'You do not have permission to access that page.');
        }

        return view('auth/setup_recovery_email');
    }

    public function setupRecoveryEmail()
    {
        $levelId = (int) (session('user')['level_id'] ?? 0);
        if ($levelId < 4) {
            return redirect()->to(site_url('/'))->with('error', 'You do not have permission to access that page.');
        }

        $rules = [
            'recovery_email' => 'required|valid_email|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $recoveryEmail = trim((string) $this->request->getPost('recovery_email'));
        $userId        = (int) session('user')['id'];
        $userModel     = new UserModel();

        // Ensure this email is not already taken by another user
        $existing = $userModel->where('email', $recoveryEmail)->where('user_id !=', $userId)->first();
        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'That email address is already used by another account. Please use a different email.');
        }

        $userModel->update($userId, ['email' => $recoveryEmail]);

        // Update email in active session
        $sessionUser = session('user');
        $sessionUser['email'] = $recoveryEmail;
        session()->set('user', $sessionUser);

        session()->remove('must_setup_recovery_email');

        return redirect()->to(site_url('/'))->with('success', 'Recovery email saved. Your account setup is complete!');
    }

    // ════════════════════════════════════════════════════════════════
    //  FORGOT PASSWORD (public — 6-digit code via email)
    // ════════════════════════════════════════════════════════════════

    public function forgotPasswordView()
    {
        if (session()->has('user')) {
            return redirect()->to(site_url('/'));
        }
        return view('auth/forgot_password');
    }

    public function forgotPassword()
    {
        $rules = ['email' => 'required|valid_email'];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please enter a valid email address.');
        }

        $email = trim((string) $this->request->getPost('email'));
        $model = new UserModel();
        $user  = $model->findByEmail($email);

        // Always show success to prevent email enumeration
        $successMsg = 'If an account with that email exists, a 6-digit verification code has been sent.';

        if (! $user) {
            return redirect()->to(site_url('verify-code'))->with('success', $successMsg);
        }

        // Check if SMTP is configured
        $smtpModel  = new SmtpSettingsModel();
        $smtpConfig = $smtpModel->getActive();

        if (! $smtpConfig) {
            return redirect()->back()->with('error', 'Password recovery is not available yet. Please contact the system administrator.');
        }

        // Generate 6-digit code
        $code    = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $model->update($user['user_id'], [
            'password_reset_token'   => hash('sha256', $code),
            'password_reset_expires' => $expires,
        ]);

        // Send email via SMTP
        try {
            $encrypter    = service('encrypter');
            $smtpPassword = $encrypter->decrypt(base64_decode($smtpConfig['smtp_password']));

            $emailService = \Config\Services::email();
            $emailService->initialize([
                'protocol'   => 'smtp',
                'SMTPHost'   => 'smtp.gmail.com',
                'SMTPUser'   => $smtpConfig['smtp_email'],
                'SMTPPass'   => $smtpPassword,
                'SMTPPort'   => 587,
                'SMTPCrypto' => 'tls',
                'mailType'   => 'html',
            ]);

            $emailService->setFrom($smtpConfig['smtp_email'], 'BSU Inventory System');
            $emailService->setTo($email);
            $emailService->setSubject('Password Reset Code - BSU Inventory');
            $emailService->setMessage(
                '<div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;padding:32px;background:#f8fffd;border-radius:16px;">' .
                '<h2 style="color:#0f3d3e;margin-bottom:16px;">Password Reset Code</h2>' .
                '<p style="color:#475569;line-height:1.6;">You requested a password reset for your BSU Inventory account. Use the verification code below:</p>' .
                '<div style="text-align:center;margin:28px 0;">' .
                '<div style="display:inline-block;padding:18px 40px;background:linear-gradient(135deg,#0f766e,#115e59);color:#fff;border-radius:14px;font-size:32px;font-weight:700;letter-spacing:8px;">' . esc($code) . '</div>' .
                '</div>' .
                '<p style="color:#94a3b8;font-size:13px;">This code will expire in 15 minutes. If you did not request this, you can safely ignore this email.</p>' .
                '<hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0;">' .
                '<p style="color:#cbd5e1;font-size:12px;">BSU Integrated Inventory Monitoring System</p>' .
                '</div>'
            );

            $emailService->send();
        } catch (\Throwable $e) {
            log_message('error', 'Password reset email failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to send the reset email. Please try again later or contact the administrator.');
        }

        // Store email in session for the verify step
        session()->setFlashdata('reset_email', $email);

        return redirect()->to(site_url('verify-code'))->with('success', $successMsg);
    }

    // ════════════════════════════════════════════════════════════════
    //  VERIFY CODE + RESET PASSWORD (public)
    // ════════════════════════════════════════════════════════════════

    public function verifyCodeView()
    {
        if (session()->has('user')) {
            return redirect()->to(site_url('/'));
        }
        return view('auth/verify_code');
    }

    public function verifyCodeAndReset()
    {
        $rules = [
            'email'            => 'required|valid_email',
            'code'             => 'required|exact_length[6]|numeric',
            'password'         => 'required|min_length[6]|max_length[255]',
            'confirm_password' => 'required|matches[password]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $email = trim((string) $this->request->getPost('email'));
        $code  = trim((string) $this->request->getPost('code'));

        $model    = new UserModel();
        $codeHash = hash('sha256', $code);
        $user     = $model->where('email', $email)
                          ->where('password_reset_token', $codeHash)
                          ->where('password_reset_expires >', date('Y-m-d H:i:s'))
                          ->first();

        if (! $user) {
            return redirect()->back()->withInput()->with('error', 'Invalid or expired verification code. Please try again.');
        }

        $password = (string) $this->request->getPost('password');

        // ── Strong password checks ──
        if (! preg_match('/[A-Z]/', $password)) {
            return redirect()->back()->withInput()->with('error', 'Password must contain at least one uppercase letter.');
        }
        if (! preg_match('/[a-z]/', $password)) {
            return redirect()->back()->withInput()->with('error', 'Password must contain at least one lowercase letter.');
        }
        if (! preg_match('/[0-9]/', $password)) {
            return redirect()->back()->withInput()->with('error', 'Password must contain at least one number.');
        }
        if (preg_match('/(?:0(?=1)|1(?=2)|2(?=3)|3(?=4)|4(?=5)|5(?=6)|6(?=7)|7(?=8)|8(?=9)){2}/', $password)) {
            return redirect()->back()->withInput()->with('error', 'Password must not contain sequential numbers (e.g. 123, 456).');
        }

        $model->update($user['user_id'], [
            'password'               => password_hash($password, PASSWORD_DEFAULT),
            'password_reset_token'   => null,
            'password_reset_expires' => null,
        ]);

        return redirect()->to(site_url('login'))->with('success', 'Password has been reset successfully. You can now log in with your new password.');
    }

    private function passwordMatches(string $input, string $stored): bool
    {
        if (password_get_info($stored)['algo']) {
            return password_verify($input, $stored);
        }
        return hash_equals($stored, $input);
    }
}
