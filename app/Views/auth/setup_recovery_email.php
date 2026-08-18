<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Recovery Email - BSU Inventory</title>
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
            <p class="login-eyebrow">Account Security</p>
            <h1>Recovery Email</h1>
            <p class="login-copy">Set the personal email address you'll use to recover your account if you ever forget your password. This is <strong>separate</strong> from the system's SMTP sending email.</p>

            <div class="login-feature-list">
                <div class="login-feature-item">Use your own personal email — not the system Gmail.</div>
                <div class="login-feature-item">A 6-digit code will be sent here when you request a password reset.</div>
                <div class="login-feature-item">You can update this later from your account settings.</div>
            </div>
        </section>

        <section class="login-card">
            <div class="login-card-section">
                <h2>Set Recovery Email</h2>
                <p>Enter the email address you want to use for password recovery.</p>

                <?php if (session()->has('success')): ?>
                    <div class="flash-message flash-success"><?= esc(session('success')) ?></div>
                <?php endif; ?>
                <?php if (session()->has('error')): ?>
                    <div class="flash-message flash-error"><?= esc(session('error')) ?></div>
                <?php endif; ?>

                <form class="login-form" method="post" action="<?= site_url('setup-recovery-email') ?>">
                    <?= csrf_field() ?>

                    <label for="recovery_email">Personal Recovery Email</label>
                    <input
                        type="email"
                        id="recovery_email"
                        name="recovery_email"
                        value="<?= esc(old('recovery_email', session('user')['email'] ?? '')) ?>"
                        placeholder="e.g. yourname@gmail.com"
                        required
                        autocomplete="email"
                    >
                    <p class="login-hint" style="margin-top:4px;">
                        This email address is only for your account's password recovery and is kept private.
                    </p>

                    <button type="submit" id="btn-save-recovery-email">Save Recovery Email</button>
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
