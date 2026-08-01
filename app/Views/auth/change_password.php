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
    <style>
        /* ── Password strength checklist ── */
        .pw-checklist {
            margin: 6px 0 10px;
            padding: 10px 14px;
            border-radius: 8px;
            background: var(--surface-alt, rgba(0,0,0,.05));
            font-size: .82rem;
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .pw-checklist li {
            display: flex;
            align-items: center;
            gap: 7px;
            color: var(--text-muted, #888);
            transition: color .2s;
        }
        .pw-checklist li::before {
            content: '✗';
            font-size: .9em;
            font-weight: 700;
            color: #e74c3c;
            transition: color .2s;
            min-width: 14px;
            text-align: center;
        }
        .pw-checklist li.ok {
            color: var(--text, inherit);
        }
        .pw-checklist li.ok::before {
            content: '✓';
            color: #27ae60;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-shell">
        <section class="login-brand-panel">
            <p class="login-eyebrow">Security</p>
            <h1>Change Your Password</h1>
            <p class="login-copy">For security, you must change your password before accessing the system for the first time.</p>

            <div class="login-feature-list">
                <div class="login-feature-item">Choose a strong password that you haven't used elsewhere.</div>
                <div class="login-feature-item">You will be redirected to the next step after changing your password.</div>
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
                    <input type="password" id="change-password" name="password" placeholder="Enter new password" required autocomplete="new-password">

                    <!-- Live password strength checklist -->
                    <ul class="pw-checklist" id="pw-checklist" aria-live="polite">
                        <li id="pw-len">At least 6 characters</li>
                        <li id="pw-upper">At least one uppercase letter (A-Z)</li>
                        <li id="pw-lower">At least one lowercase letter (a-z)</li>
                        <li id="pw-num">At least one number (0-9)</li>
                        <li id="pw-seq">No sequential numbers (e.g. 123, 456)</li>
                    </ul>

                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm new password" required autocomplete="new-password">

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
    <script>
        (function () {
            var pwInput    = document.getElementById('change-password');
            var liLen      = document.getElementById('pw-len');
            var liUpper    = document.getElementById('pw-upper');
            var liLower    = document.getElementById('pw-lower');
            var liNum      = document.getElementById('pw-num');
            var liSeq      = document.getElementById('pw-seq');

            function hasSequentialNumbers(val) {
                for (var i = 0; i < val.length - 2; i++) {
                    var a = val.charCodeAt(i);
                    var b = val.charCodeAt(i + 1);
                    var c = val.charCodeAt(i + 2);
                    if (a >= 48 && a <= 57 && b === a + 1 && c === a + 2) {
                        return true;
                    }
                }
                return false;
            }

            function setOk(el, ok) {
                if (ok) { el.classList.add('ok'); } else { el.classList.remove('ok'); }
            }

            pwInput.addEventListener('input', function () {
                var val = pwInput.value;
                setOk(liLen,   val.length >= 6);
                setOk(liUpper, /[A-Z]/.test(val));
                setOk(liLower, /[a-z]/.test(val));
                setOk(liNum,   /[0-9]/.test(val));
                setOk(liSeq,   !hasSequentialNumbers(val));
            });
        })();
    </script>
</body>
</html>
