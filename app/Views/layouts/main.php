<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="<?= csrf_header() ?>" content="<?= csrf_hash() ?>">
    <title><?= esc($title ?? 'BSU Inventory') ?></title>
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
<body>

<?php
    $levelId = (int) (session('user')['level_id'] ?? 0);
?>

<button class="menu-toggle" type="button" data-menu-toggle>Menu</button>

<div class="navbar">
    <div class="nav-logo">BSU INVENTORY</div>
    <?php
        // ── Fetch notification counts for badges ──
        $db = db_connect();
        $userOfficeId = (int) (session('user')['user_office_id'] ?? 0);
        $navBadgePending = 0;
        $navBadgeAlerts = 0;
        $navBadgePendingUsers = 0;

        // Pending stockout requests (Level 2/3)
        if ($levelId >= 2 && $levelId <= 3) {
            $pendingBuilder = $db->table('temp_stockout')->where('status', 'pending');
            if ($userOfficeId > 0) {
                $pendingBuilder->where('user_office_id', $userOfficeId);
            }
            $navBadgePending = (int) $pendingBuilder->countAllResults();
        }

        // Pending user activations (Level 3+)
        if ($levelId >= 3) {
            $userBuilder = $db->table('user_table')->where('user_activity_id', 3);
            if ($levelId < 4 && $userOfficeId > 0) {
                $userBuilder->where('user_office_id', $userOfficeId);
            }
            $navBadgePendingUsers = (int) $userBuilder->countAllResults();
        }

        // Dashboard alerts: low stock + expiring (Level 1-3)
        if ($levelId >= 1 && $levelId <= 3) {
            $officeFilter = $userOfficeId > 0 ? ' AND p.user_office_id = ' . (int) $userOfficeId : '';
            $lowStockCount = (int) ($db->query(
                'SELECT COUNT(*) AS cnt FROM (
                    SELECT p.product_id
                    FROM product_table p
                    LEFT JOIN batch_table b ON p.product_id = b.product_id
                    WHERE 1=1' . $officeFilter . '
                    GROUP BY p.product_id, p.product_reorder_point
                    HAVING COALESCE(SUM(b.current_qty), 0) <= COALESCE(p.product_reorder_point, 0)
                       AND COALESCE(SUM(b.current_qty), 0) > 0
                       AND COALESCE(p.product_reorder_point, 0) > 0
                ) AS sub'
            )->getRowArray()['cnt'] ?? 0);
            $expiringCount = (int) ($db->query(
                'SELECT COUNT(*) AS cnt FROM batch_table b
                 INNER JOIN product_table p ON b.product_id = p.product_id
                 WHERE b.current_qty > 0
                   AND b.expiration_date IS NOT NULL
                   AND b.expiration_date >= CURDATE()
                   AND DATEDIFF(b.expiration_date, CURDATE()) <= 30' . $officeFilter
            )->getRowArray()['cnt'] ?? 0);
            $navBadgeAlerts = $lowStockCount + $expiringCount;
        }
    ?>
    <ul class="nav-links">
        <?php if ($levelId < 4): ?>
            <li>
                <a href="<?= site_url('/') ?>" class="nav-badge-wrapper">
                    HOME
                    <?php if ($navBadgeAlerts > 0): ?>
                        <span class="nav-badge"><?= $navBadgeAlerts ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endif; ?>

        <?php if ($levelId >= 2 && $levelId <= 3): ?>
            <!-- Level 2, 3: Full stock management -->
            <li class="has-submenu">
                <a href="<?= site_url('stockcard') ?>">STOCK</a>
                <ul class="submenu">
                    <li><a href="<?= site_url('stockcard') ?>">Stockcard</a></li>
                    <li><a href="<?= site_url('stock/add') ?>">Stock In/Out</a></li>
                </ul>
            </li>
            <li class="has-submenu">
                <a href="<?= site_url('products') ?>">PRODUCTS</a>
                <ul class="submenu">
                    <li><a href="<?= site_url('products') ?>">Product List</a></li>
                    <li><a href="<?= site_url('batchlist') ?>">Per Batch List</a></li>
                    <li><a href="<?= site_url('products/create') ?>">Product In</a></li>
                </ul>
            </li>
        <?php elseif ($levelId === 1): ?>
            <!-- Level 1: Read-only product list + stock-out -->
            <li><a href="<?= site_url('products') ?>">ITEMS</a></li>
            <li class="has-submenu">
                <a href="<?= site_url('stockout') ?>">STOCK OUT</a>
                <ul class="submenu">
                    <li><a href="<?= site_url('stockout') ?>">Stock Out</a></li>
                    <li><a href="<?= site_url('stockout/temp') ?>">My Temp List</a></li>
                </ul>
            </li>
        <?php endif; ?>
        <!-- Level 4: No stock menus at all -->

        <?php if ($levelId >= 2 && $levelId <= 3): ?>
            <!-- Level 2, 3: Temp stockout approval -->
            <li>
                <a href="<?= site_url('stockout/pending') ?>" class="nav-badge-wrapper">
                    REQUESTS
                    <?php if ($navBadgePending > 0): ?>
                        <span class="nav-badge"><?= $navBadgePending ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endif; ?>

        <?php if ($levelId >= 2 && $levelId !== 4): ?>
            <li>
                <a href="<?= site_url('settings') ?>" class="nav-badge-wrapper">
                    OTHER
                    <?php if ($navBadgePendingUsers > 0): ?>
                        <span class="nav-badge"><?= $navBadgePendingUsers ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endif; ?>

         <?php if ($levelId == 4): ?>
            <li>
                <a href="<?= site_url('/') ?>" class="nav-badge-wrapper">
                    HOME
                    <?php if ($navBadgePendingUsers > 0): ?>
                        <span class="nav-badge"><?= $navBadgePendingUsers ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endif; ?>

        <li class="login"><a href="<?= site_url('logout') ?>">LOGOUT</a></li>
    </ul>
</div>

<div class="app-shell">
    <?php if (session()->has('success')): ?>
        <div class="flash-message flash-success" data-auto-close="1500"><?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->has('error')): ?>
        <div class="flash-message flash-error" data-auto-close="1500"><?= esc(session('error')) ?></div>
    <?php endif; ?>

    <?= $this->renderSection('content') ?>
</div>

<button type="button" class="theme-toggle" data-theme-toggle aria-label="Switch color theme">
    <span class="theme-toggle-icon" aria-hidden="true"></span>
    <span class="sr-only" data-theme-label>Switch color theme</span>
</button>

<script>
window.appConfig = {
    baseUrl: <?= json_encode(base_url()) ?>,
    csrfHeader: <?= json_encode(csrf_header()) ?>,
    csrfTokenName: <?= json_encode(csrf_token()) ?>,
    csrfHash: <?= json_encode(csrf_hash()) ?>,
    levelId: <?= $levelId ?>
};
</script>
<script src="<?= base_url('assets/script.js?v=' . filemtime(FCPATH . 'assets/script.js')) ?>"></script>
</body>
</html>
