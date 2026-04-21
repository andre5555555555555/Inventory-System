<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - BSU Inventory</title>
    <link rel="stylesheet" href="<?= base_url('assets/style.css') ?>">
</head>
<body class="login-page">
    <div class="login-shell login-shell-register">
        <section class="login-brand-panel">
            <h1>Set up a new inventory user</h1>
            <div class="login-feature-list">
                <div class="login-feature-item">Fill up the form.</div>
                <div class="login-feature-item">Assign each account to an office for organized stock movement.</div>
                <div class="login-feature-item">An administrator will review and activate your account.</div>
            </div>
        </section>

        <section class="login-card">
            <div class="login-card-section">
                <h2>Create Account</h2>
                <p>Create a new user. Your account will be pending until an admin activates it.</p>

                <?php if (session()->has('error')): ?>
                    <div class="flash-message flash-error"><?= esc(session('error')) ?></div>
                <?php endif; ?>

                <form class="login-form login-form-register" method="post" action="<?= site_url('register') ?>">
                    <?= csrf_field() ?>
                    <label>Username</label>
                    <input type="text" name="username" value="<?= esc(old('username')) ?>" placeholder="Choose username" required>

                    <label>Email</label>
                    <input type="email" name="email" value="<?= esc(old('email')) ?>" placeholder="Enter email address" required>

                    <label>Password</label>
                    <input type="password" name="password" placeholder="Create password" required>

                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm password" required>

                    <label>Role</label>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <?php foreach (($roles ?? []) as $role): ?>
                            <option value="<?= esc($role['role_name']) ?>" <?= old('role') === $role['role_name'] ? 'selected' : '' ?>>
                                <?= esc($role['role_name']) ?> (Level <?= (int) $role['level_id'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>User Office</label>
                    <select name="user_office_id" required>
                        <option value="">Select User Office</option>
                        <?php foreach (($userOffices ?? []) as $uo): ?>
                            <option value="<?= (int) $uo['user_office_id'] ?>" <?= (string) old('user_office_id') === (string) $uo['user_office_id'] ? 'selected' : '' ?>>
                                <?= esc($uo['user_office']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit">Create Account</button>
                </form>

                <div class="login-inline-actions">
                    <a href="<?= site_url('login') ?>" class="login-secondary-link">Back to login</a>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
