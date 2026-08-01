<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - BSU Inventory</title>
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
            <p class="login-eyebrow">Account Recovery</p>
            <h1>Forgot Your Password?</h1>
            <p class="login-copy">Enter the email address associated with your account and we'll send you a 6-digit verification code to reset your password.</p>

            <div class="login-feature-list">
                <div class="login-feature-item">Check your inbox (and spam folder) for the 6-digit code.</div>
                <div class="login-feature-item">The code expires in 15 minutes for security.</div>
            </div>
        </section>

        <section class="login-card">
            <div class="login-card-section">
                <h2>Password Recovery</h2>
                <p>We'll send a 6-digit verification code to your email address.</p>

                <?php if (session()->has('success')): ?>
                    <div class="flash-message flash-success"><?= esc(session('success')) ?></div>
                <?php endif; ?>
                <?php if (session()->has('error')): ?>
                    <div class="flash-message flash-error"><?= esc(session('error')) ?></div>
                <?php endif; ?>

                <form class="login-form" method="post" action="<?= site_url('forgot-password') ?>">
                    <?= csrf_field() ?>

                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= esc(old('email')) ?>" placeholder="Enter your registered email" required>

                    <button type="submit">Send Verification Code</button>
                </form>
                <div class="login-inline-actions">
                    <a href="<?= site_url('login') ?>" class="login-secondary-link">Back to login</a>
                </div>
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
