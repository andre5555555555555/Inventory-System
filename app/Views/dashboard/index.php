<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $borrowedOutTotal = (int) array_sum(array_map('intval', array_column($activeBorrows, 'net_borrowed')));
?>
<div class="dashboard-shell">
    <section class="dashboard-hero">
        <div>
            <p class="dashboard-eyebrow">Inventory Overview</p>
            <h1>Welcome Back</h1>
            <p class="dashboard-subtitle">Track stock health, spot urgent risks, and review recent activity from one cleaner control center.</p>
        </div>
        <div class="dashboard-hero-note">
            <span>Focus Today</span>
            <strong class="text-white" style="color:white"><?= (int) $summary['lowStockCount'] + (int) $summary['expiringCount'] + (int) ($summary['outOfStockCount'] ?? count($outOfStock)) ?></strong>
            <small>alerts need attention</small>
        </div>
    </section>

    <section class="dashboard-summary">
        <article class="summary-card">
            <span>Total Products</span>
            <strong><?= (int) $summary['totalItems'] ?></strong>
        </article>
        <button
            type="button"
            class="summary-card summary-card-action"
            data-dashboard-toggle="low-stock"
            aria-expanded="false"
            aria-controls="dashboard-detail-low-stock"
            <?= empty($lowStock) ? 'disabled' : '' ?>
        >
            <span>Low Stock Alerts</span>
            <strong><?= (int) $summary['lowStockCount'] ?></strong>
            <small><?= empty($lowStock) ? 'No details to show' : 'Click to view table' ?></small>
        </button>
        <button
            type="button"
            class="summary-card summary-card-action"
            data-dashboard-toggle="out-of-stock"
            aria-expanded="false"
            aria-controls="dashboard-detail-out-of-stock"
            <?= empty($outOfStock) ? 'disabled' : '' ?>
        >
            <span>Out of Stock</span>
            <strong><?= (int) ($summary['outOfStockCount'] ?? count($outOfStock)) ?></strong>
            <small><?= empty($outOfStock) ? 'No details to show' : 'Click to view table' ?></small>
        </button>
        <button
            type="button"
            class="summary-card summary-card-action"
            data-dashboard-toggle="expiring"
            aria-expanded="false"
            aria-controls="dashboard-detail-expiring"
            <?= empty($expiring) ? 'disabled' : '' ?>
        >
            <span>Expiring Soon</span>
            <strong><?= (int) $summary['expiringCount'] ?></strong>
            <small><?= empty($expiring) ? 'No details to show' : 'Click to view table' ?></small>
        </button>
        <button
            type="button"
            class="summary-card summary-card-action summary-card-borrow"
            data-dashboard-toggle="borrowed"
            aria-expanded="false"
            aria-controls="dashboard-detail-borrowed"
            <?= empty($activeBorrows) ? 'disabled' : '' ?>
        >
            <span>Items Out (Borrowed)</span>
            <strong><?= $borrowedOutTotal ?></strong>
            <small><?= empty($activeBorrows) ? 'No details to show' : 'Click to view table' ?></small>
        </button>
    </section>

    <section class="dashboard-detail-panels" aria-live="polite">
        <div class="dashboard-detail-panel" id="dashboard-detail-low-stock" data-dashboard-panel="low-stock" hidden>
            <div class="dashboard-card-head">
                <h2>Low Stock Details</h2>
                <span><?= (int) $summary['lowStockCount'] ?> item<?= (int) $summary['lowStockCount'] === 1 ? '' : 's' ?></span>
            </div>
            <div class="dashboard-table-wrap">
                <table class="dashboard-detail-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Current Stock</th>
                            <th>Reorder Point</th>
                            <th>Short By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lowStock as $row): ?>
                            <?php
                                $stockLeft = (int) $row['stock_left'];
                                $reorderPoint = (int) $row['re_order_point'];
                            ?>
                            <tr>
                                <td><?= esc($row['item']) ?></td>
                                <td><?= $stockLeft ?></td>
                                <td><?= $reorderPoint ?></td>
                                <td><?= max(0, $reorderPoint - $stockLeft) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-detail-panel" id="dashboard-detail-out-of-stock" data-dashboard-panel="out-of-stock" hidden>
            <div class="dashboard-card-head">
                <h2>Out of Stock Details</h2>
                <span><?= (int) ($summary['outOfStockCount'] ?? count($outOfStock)) ?> item<?= (int) ($summary['outOfStockCount'] ?? count($outOfStock)) === 1 ? '' : 's' ?></span>
            </div>
            <div class="dashboard-table-wrap">
                <table class="dashboard-detail-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Status</th>
                            <th>Action Needed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($outOfStock as $row): ?>
                            <tr>
                                <td><?= esc($row['item']) ?></td>
                                <td>Unavailable</td>
                                <td>Restock immediately</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-detail-panel" id="dashboard-detail-expiring" data-dashboard-panel="expiring" hidden>
            <div class="dashboard-card-head">
                <h2>Expiring Soon Details</h2>
                <span><?= (int) $summary['expiringCount'] ?> batch<?= (int) $summary['expiringCount'] === 1 ? '' : 'es' ?></span>
            </div>
            <div class="dashboard-table-wrap">
                <table class="dashboard-detail-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Batch ID</th>
                            <th>Expiration Date</th>
                            <th>Days Left</th>
                            <th>Qty Remaining</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expiring as $row): ?>
                            <tr>
                                <td><?= esc($row['item']) ?></td>
                                <td><?= (int) $row['batch_id'] ?></td>
                                <td><?= esc(date('M d, Y', strtotime($row['expiration_date']))) ?></td>
                                <td><?= (int) $row['days_left'] ?></td>
                                <td><?= (int) $row['remaining_qty'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-detail-panel" id="dashboard-detail-borrowed" data-dashboard-panel="borrowed" hidden>
            <div class="dashboard-card-head">
                <h2>Borrowed Items Details</h2>
                <span><?= $borrowedOutTotal ?> total out</span>
            </div>
            <div class="dashboard-table-wrap">
                <table class="dashboard-detail-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Office</th>
                            <th>Borrowed</th>
                            <th>Returned</th>
                            <th>Still Out</th>
                            <th>Last Movement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeBorrows as $row): ?>
                            <tr>
                                <td><?= esc($row['item']) ?></td>
                                <td><?= esc($row['office'] ?: 'Unassigned') ?></td>
                                <td><?= (int) $row['total_borrowed'] ?></td>
                                <td><?= (int) $row['total_returned'] ?></td>
                                <td><?= (int) $row['net_borrowed'] ?></td>
                                <td><?= esc(date('M d, Y', strtotime($row['last_borrowed']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="dashboard-detail-actions">
                <a href="<?= site_url('stock/add') ?>">Record a Return</a>
            </div>
        </div>
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
                <span>Per-product threshold</span>
            </div>
            <?php if ($expiring): ?>
                <?php foreach ($expiring as $row): ?>
                    <div class="dashboard-list-item <?= (int) $row['days_left'] <= (int) ($row['expiry_danger_days'] ?? 7) ? 'is-danger' : 'is-caution' ?>">
                        <strong><?= esc($row['item']) ?></strong>
                        <span><?= (int) $row['days_left'] ?> days left</span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="dashboard-empty">No items expiring soon.</div>
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
            <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--border-color,#e5e7eb);text-align:right;">
                <a href="<?= site_url('transactions') ?>"
                   style="font-size:12.5px;font-weight:600;color:#0f766e;text-decoration:none;">
                    See More →
                </a>
            </div>
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
