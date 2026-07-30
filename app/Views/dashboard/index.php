<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="dashboard-shell">
    <section class="dashboard-hero">
        <div>
            <p class="dashboard-eyebrow">Inventory Overview</p>
            <h1>Welcome Back</h1>
            <p class="dashboard-subtitle">Track stock health, spot urgent risks, and review recent activity from one cleaner control center.</p>
        </div>
        <div class="dashboard-hero-note">
            <span>Focus Today</span>
            <strong class="text-white" style="color:white"><?= (int) $summary['lowStockCount'] + (int) $summary['expiringCount'] ?></strong>
            <small>alerts need attention</small>
        </div>
    </section>

    <section class="dashboard-summary">
        <article class="summary-card">
            <span>Total Products</span>
            <strong><?= (int) $summary['totalItems'] ?></strong>
        </article>
        <article class="summary-card">
            <span>Low Stock Alerts</span>
            <strong><?= (int) $summary['lowStockCount'] ?></strong>
        </article>
        <article class="summary-card">
            <span>Expiring Soon</span>
            <strong><?= (int) $summary['expiringCount'] ?></strong>
        </article>
        <article class="summary-card summary-card-borrow">
            <span>Items Out (Borrowed)</span>
            <strong><?= array_sum(array_column($activeBorrows, 'net_borrowed')) ?></strong>
        </article>
    </section>

    <section class="dashboard-grid">
        <div class="dashboard-card">
            <div class="dashboard-card-head">
                <h2>Low Stock</h2>
                <span>Based on reorder point</span>
            </div>
            <?php if ($lowStock): ?>
                <?php foreach ($lowStock as $row): ?>
                    <div class="dashboard-list-item is-warning">
                        <strong><?= esc($row['item']) ?></strong>
                        <span>Only <?= (int) $row['stock_left'] ?> left, reorder at <?= (int) $row['re_order_point'] ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="dashboard-empty">No low stock items right now.</div>
            <?php endif; ?>
        </div>

        <div class="dashboard-card">
            <div class="dashboard-card-head">
                <h2>Expiring Soon</h2>
                <span>Within 30 days</span>
            </div>
            <?php if ($expiring): ?>
                <?php foreach ($expiring as $row): ?>
                    <div class="dashboard-list-item <?= (int) $row['days_left'] <= 7 ? 'is-danger' : 'is-caution' ?>">
                        <strong><?= esc($row['item']) ?></strong>
                        <span><?= (int) $row['days_left'] ?> days left</span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="dashboard-empty">No expiring items in the next 30 days.</div>
            <?php endif; ?>
        </div>

        <div class="dashboard-card">
            <div class="dashboard-card-head">
                <h2>Out of Stock</h2>
                <span>Immediate restock</span>
            </div>
            <?php if ($outOfStock): ?>
                <?php foreach ($outOfStock as $row): ?>
                    <div class="dashboard-list-item is-danger">
                        <strong><?= esc($row['item']) ?></strong>
                        <span>Currently unavailable</span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="dashboard-empty">All tracked products still have stock.</div>
            <?php endif; ?>
        </div>

        <div class="dashboard-card">
            <div class="dashboard-card-head">
                <h2>Recent Transactions</h2>
                <span>Latest movement</span>
            </div>
            <?php if ($recentTransactions): ?>
                <?php foreach ($recentTransactions as $row): ?>
                    <div class="dashboard-list-item is-info">
                        <strong><?= esc($row['item']) ?></strong>
                        <span>
                            <?= esc(ucfirst($row['transaction_type'] ?? '')) ?>: <?= (int) $row['transaction_qty'] ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="dashboard-empty">No recent transactions yet.</div>
            <?php endif; ?>
        </div>

        <div class="dashboard-card dashboard-card-borrow">
            <div class="dashboard-card-head">
                <h2>&#128260; Active Borrows</h2>
                <span>Net items currently out</span>
            </div>
            <?php if (!empty($activeBorrows)): ?>
                <?php foreach ($activeBorrows as $row): ?>
                    <div class="dashboard-list-item is-borrow">
                        <div style="flex:1;min-width:0;">
                            <strong style="display:block;"><?= esc($row['item']) ?></strong>
                            <span style="font-size:12px;color:var(--text-muted,#6b7280);">
                                <?php if (!empty($row['office'])): ?>
                                    <?= esc($row['office']) ?> &mdash;
                                <?php endif; ?>
                                Last: <?= esc(date('M d, Y', strtotime($row['last_borrowed']))) ?>
                            </span>
                        </div>
                        <div style="text-align:right;flex-shrink:0;margin-left:10px;">
                            <span style="display:block;font-size:13px;font-weight:700;color:#92400e;">
                                <?= (int) $row['net_borrowed'] ?> out
                            </span>
                            <span style="font-size:11px;color:var(--text-muted,#6b7280);">
                                <?= (int) $row['total_returned'] ?> returned
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--border-color,#e5e7eb);">
                    <a href="<?= site_url('stock/add') ?>"
                       style="font-size:12.5px;font-weight:600;color:#0f766e;text-decoration:none;">
                        + Record a Return
                    </a>
                </div>
            <?php else: ?>
                <div class="dashboard-empty">No items currently borrowed.</div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
