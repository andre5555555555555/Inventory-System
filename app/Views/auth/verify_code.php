<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code - BSU Inventory</title>
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
        .otp-input-group {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin: 8px 0 4px;
        }
        .otp-input-group input {
            width: 48px;
            height: 56px;
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0;
            border-radius: 12px;
            border: 2px solid #cbd5e1;
            background: #fff;
            color: #0f3d3e;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .otp-input-group input:focus {
            outline: none;
            border-color: #0f766e;
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.14);
        }
        /* hidden real input */
        .otp-hidden { position: absolute; opacity: 0; width: 0; height: 0; }

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
            <p class="login-eyebrow">Verification</p>
            <h1>Enter Your Code</h1>
            <p class="login-copy">A 6-digit verification code was sent to your email. Enter the code below along with your new password.</p>

            <div class="login-feature-list">
                <div class="login-feature-item">Check your inbox and spam folder for the code.</div>
                <div class="login-feature-item">The code expires in 15 minutes.</div>
                <div class="login-feature-item">Didn't receive it? <a href="<?= site_url('forgot-password') ?>" style="color:#fff;text-decoration:underline;">Request a new code</a></div>
            </div>
        </section>

        <section class="login-card">
            <div class="login-card-section">
                <h2>Reset Password</h2>
                <p>Enter the 6-digit code from your email and choose a new password.</p>

                <?php if (session()->has('success')): ?>
                    <div class="flash-message flash-success"><?= esc(session('success')) ?></div>
                <?php endif; ?>
                <?php if (session()->has('error')): ?>
                    <div class="flash-message flash-error"><?= esc(session('error')) ?></div>
                <?php endif; ?>

                <form class="login-form" method="post" action="<?= site_url('verify-code') ?>" id="verify-form">
                    <?= csrf_field() ?>

                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= esc(old('email') ?: session('reset_email') ?: '') ?>" placeholder="Enter your registered email" required>

                    <label>Verification Code</label>
                    <!-- Visual OTP boxes -->
                    <div class="otp-input-group" id="otp-boxes">
                        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="otp-box" data-idx="0" autocomplete="off">
                        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="otp-box" data-idx="1" autocomplete="off">
                        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="otp-box" data-idx="2" autocomplete="off">
                        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="otp-box" data-idx="3" autocomplete="off">
                        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="otp-box" data-idx="4" autocomplete="off">
                        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="otp-box" data-idx="5" autocomplete="off">
                    </div>
                    <!-- Hidden real input submitted with the form -->
                    <input type="hidden" name="code" id="otp-real" value="<?= esc(old('code') ?: '') ?>" required>

                    <label>New Password</label>
                    <input type="password" id="verify-password" name="password" placeholder="Enter new password" required autocomplete="new-password">

                    <ul class="pw-checklist" id="pw-checklist" aria-live="polite">
                        <li id="pw-len">At least 6 characters</li>
                        <li id="pw-upper">At least one uppercase letter (A-Z)</li>
                        <li id="pw-lower">At least one lowercase letter (a-z)</li>
                        <li id="pw-num">At least one number (0-9)</li>
                        <li id="pw-seq">No sequential numbers (e.g. 123, 456)</li>
                    </ul>

                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm new password" required autocomplete="new-password">

                    <button type="submit">Verify &amp; Reset Password</button>
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
    <script>
        (function () {
            // ── OTP box behavior ──
            var boxes   = document.querySelectorAll('.otp-box');
            var realInp = document.getElementById('otp-real');

            function syncReal() {
                var val = '';
                boxes.forEach(function (b) { val += b.value; });
                realInp.value = val;
            }

            boxes.forEach(function (box, i) {
                box.addEventListener('input', function () {
                    // Only allow digits
                    box.value = box.value.replace(/[^0-9]/g, '');
                    syncReal();
                    if (box.value && i < boxes.length - 1) {
                        boxes[i + 1].focus();
                    }
                });

                box.addEventListener('keydown', function (e) {
                    if (e.key === 'Backspace' && !box.value && i > 0) {
                        boxes[i - 1].focus();
                    }
                });

                // Handle paste into any box
                box.addEventListener('paste', function (e) {
                    e.preventDefault();
                    var paste = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
                    for (var j = 0; j < boxes.length; j++) {
                        boxes[j].value = paste[j] || '';
                    }
                    syncReal();
                    var lastFilled = Math.min(paste.length, boxes.length) - 1;
                    if (lastFilled >= 0) boxes[Math.min(lastFilled + 1, boxes.length - 1)].focus();
                });
            });

            // Pre-fill boxes if old('code') was set
            var existing = realInp.value;
            if (existing) {
                for (var k = 0; k < boxes.length; k++) {
                    boxes[k].value = existing[k] || '';
                }
            }

            // ── Password strength checklist ──
            var pwInput = document.getElementById('verify-password');
            var liLen   = document.getElementById('pw-len');
            var liUpper = document.getElementById('pw-upper');
            var liLower = document.getElementById('pw-lower');
            var liNum   = document.getElementById('pw-num');
            var liSeq   = document.getElementById('pw-seq');

            function hasSequentialNumbers(val) {
                for (var i = 0; i < val.length - 2; i++) {
                    var a = val.charCodeAt(i);
                    var b = val.charCodeAt(i + 1);
                    var c = val.charCodeAt(i + 2);
                    if (a >= 48 && a <= 57 && b === a + 1 && c === a + 2) return true;
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
