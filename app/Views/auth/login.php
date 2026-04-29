<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BSU Inventory</title>
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
            
            <h1>BSU Integrated Inventory Monitoring System</h1>

            <div class="login-feature-list">
                <div class="login-feature-item">Track product movement and stock levels with less clutter.</div>
                <div class="login-feature-item">Review dashboard alerts for low stock and expiring items.</div>
                <div class="login-feature-item">Manage products, reports, and settings in one consistent system.</div>
            </div>
        </section>

        <section class="login-card">
            <div class="login-card-section">
                <h2>Sign In</h2>
                <p>Access the dashboard and continue managing inventory.</p>

                <?php if (session()->has('success')): ?>
                    <div class="flash-message flash-success"><?= esc(session('success')) ?></div>
                <?php endif; ?>
                <?php if (session()->has('error')): ?>
                    <div class="flash-message flash-error"><?= esc(session('error')) ?></div>
                <?php endif; ?>

                <form class="login-form" method="post" action="<?= site_url('login') ?>">
                    <?= csrf_field() ?>
                    <label>Username or Email</label>
                    <input type="text" name="username" value="" placeholder="Enter username or email" required>

                    <label>Password</label>
                    <input type="password" name="password" value="" placeholder="Enter password" required>

                    <button type="submit">Login</button>
                </form>
                <div class="login-inline-actions">
                    <a href="<?= site_url('register') ?>" class="login-secondary-link">Create a new account</a>
                </div>
                <p class="login-hint">Existing plaintext passwords are automatically upgraded to secure hashes on successful login.</p>
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
