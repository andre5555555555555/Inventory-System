<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - BSU Inventory</title>
    <script>
        (function () {
            var savedTheme = localStorage.getItem('inventoryTheme');
            if (savedTheme === 'rpg' || savedTheme === 'BSU') savedTheme = 'bsu';
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            var allowedThemes = ['light', 'dark', 'bsu'];
            document.documentElement.dataset.theme = allowedThemes.indexOf(savedTheme) >= 0 ? savedTheme : (prefersDark ? 'dark' : 'light');
        })();
    </script>
    <link rel="stylesheet" href="<?= base_url('assets/style.css?v=' . filemtime(FCPATH . 'assets/style.css')) ?>">
</head>
<body class="login-page">
    <div class="login-shell">
        <section class="login-brand-panel">
            <p class="login-eyebrow">Security</p>
            <h1>Change Your Password</h1>
            <p class="login-copy">For security, you must change your password before accessing the system for the first time.</p>

            <div class="login-feature-list">
                <div class="login-feature-item">Choose a strong password that you haven't used elsewhere.</div>
                <div class="login-feature-item">You will be redirected to the dashboard after changing your password.</div>
            </div>
        </section>

        <section class="login-card">
            <div class="login-card-section">
                <h2>Set New Password</h2>
                <p>Please enter your new password below.</p>

                <?php if (session()->has('error')): ?>
                    <div class="flash-message flash-error"><?= esc(session('error')) ?></div>
                <?php endif; ?>

                <form class="login-form" method="post" action="<?= site_url('change-password') ?>">
                    <?= csrf_field() ?>
                    <label>New Password</label>
                    <input type="password" name="password" placeholder="Enter new password" required>

                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm new password" required>

                    <button type="submit">Change Password</button>
                </form>
            </div>
        </section>
    </div>
    <button type="button" class="theme-toggle" data-theme-toggle aria-label="Switch color theme">
        <span class="theme-toggle-icon" aria-hidden="true"></span>
        <span class="sr-only" data-theme-label>Switch color theme</span>
    </button>
    <script src="<?= base_url('assets/script.js?v=' . filemtime(FCPATH . 'assets/script.js')) ?>"></script>
</body>
</html>
