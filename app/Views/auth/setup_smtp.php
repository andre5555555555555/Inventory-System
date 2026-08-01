<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configure Email Settings - BSU Inventory</title>
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
            <p class="login-eyebrow">Configuration</p>
            <h1>Email Setup</h1>
            <p class="login-copy">Configure your Gmail and Google App Password so the system can send password‑reset emails to users who forget their credentials.</p>

            <div class="login-feature-list">
                <div class="login-feature-item">Use a Gmail address dedicated to this system.</div>
                <div class="login-feature-item">Generate an App Password from your Google Account security settings.</div>
                <div class="login-feature-item">Credentials are encrypted and stored securely.</div>
            </div>
        </section>

        <section class="login-card">
            <div class="login-card-section">
                <h2>SMTP Credentials</h2>
                <p>Enter the Gmail address and its corresponding Google App Password.</p>

                <?php if (session()->has('success')): ?>
                    <div class="flash-message flash-success"><?= esc(session('success')) ?></div>
                <?php endif; ?>
                <?php if (session()->has('error')): ?>
                    <div class="flash-message flash-error"><?= esc(session('error')) ?></div>
                <?php endif; ?>

                <form class="login-form" method="post" action="<?= site_url('setup-smtp') ?>">
                    <?= csrf_field() ?>

                    <label>Gmail Address</label>
                    <input type="email" name="smtp_email" value="<?= esc(old('smtp_email')) ?>" placeholder="e.g. inventory@gmail.com" required>

                    <label>Google App Password</label>
                    <input type="password" name="smtp_password" placeholder="16-character app password" required minlength="8">
                    <p class="login-hint" style="margin-top:4px;">
                        Go to <strong>Google Account → Security → 2-Step Verification → App passwords</strong> to generate one.
                    </p>

                    <button type="submit">Save Email Settings</button>
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
