<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php $levelId = (int) ($levelId ?? session('user')['level_id'] ?? 0); ?>
<div class="page-shell stockout-page">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Approval</p>
            <h1>Pending Stock-Out Requests</h1>
            <p class="page-subtitle">Review and approve or reject stock-out requests from staff members.<?php if ($levelId >= 2): ?> You can adjust quantities before approving.<?php endif; ?></p>
        </div>
    </div>

    <?php if (empty($requests)): ?>
        <div class="section-card">
            <div class="empty-state">
                <h3>No pending requests</h3>
                <p>All stock-out requests have been processed.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($requests as $request): ?>
            <div class="section-card pending-request-card">
                <div class="request-header">
                    <div>
                        <h2>Request #<?= (int) $request['temp_stockout_id'] ?></h2>
                        <span class="request-meta">
                            By <strong><?= esc($request['requester_name'] ?? 'Unknown') ?></strong>
                            &middot; <?= esc($request['office_name'] ?? '') ?>
                            &middot; <?= esc($request['created_at'] ?? '') ?>
                        </span>
                    </div>
                    <button type="button" class="btn-primary btn-approve-all"
                            onclick="approveAllRequest(<?= (int) $request['temp_stockout_id'] ?>)">
                        Accept All
                    </button>
                </div>

                <table class="data-table">
                    <tr>
                        <th>Item</th>
                        <th>Unit</th>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($request['items'] as $item): ?>
                        <?php
                            // For summed items, item_ids is a comma-separated list of the underlying IDs
                            $itemIds = $item['item_ids'] ?? '';
                            $firstItemId = $itemIds ? (int) explode(',', $itemIds)[0] : 0;
                        ?>
                        <tr id="stockout-item-<?= $firstItemId ?>">
                            <td><?= esc($item['item_name'] ?? '') ?></td>
                            <td><?= esc($item['unit'] ?? '') ?></td>
                            <td><?= esc($item['description'] ?? '') ?></td>
                            <td>
                                <?php if (($item['status'] ?? '') === 'pending' && $levelId >= 2): ?>
                                    <form class="inline-edit-form" onsubmit="editPendingQuantity(event, <?= $firstItemId ?>)">
                                        <input type="number" name="quantity" value="<?= (int) $item['quantity'] ?>" min="1" class="inline-input" required>
                                        <button type="submit" class="action-btn edit-btn" title="Save">✓</button>
                                    </form>
                                <?php else: ?>
                                    <?= (int) $item['quantity'] ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?= strtolower(esc($item['status'] ?? 'pending')) ?>">
                                    <?= esc(ucfirst($item['status'] ?? 'pending')) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (($item['status'] ?? '') === 'pending'): ?>
                                    <button class="action-btn activate-btn" onclick="approveStockoutItem(<?= $firstItemId ?>)">Accept</button>
                                    <button class="action-btn delete-btn" onclick="rejectStockoutItem(<?= $firstItemId ?>)">Reject</button>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function editPendingQuantity(event, itemId) {
    event.preventDefault();
    const form = event.target;
    const quantity = form.querySelector('input[name="quantity"]').value;
    const headers = {
        'X-Requested-With': 'XMLHttpRequest',
    };
    const csrfMeta = document.querySelector(`meta[name="${window.appConfig?.csrfHeader ?? ''}"]`);
    if (csrfMeta && window.appConfig?.csrfHeader) {
        headers[window.appConfig.csrfHeader] = csrfMeta.content;
    }

    fetch(`${window.appConfig.baseUrl}stockout/edit-pending/${itemId}`, {
        method: 'POST',
        headers: headers,
        body: new URLSearchParams({ quantity: quantity }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.message) {
            // Show a brief toast
            const toast = document.createElement('div');
            toast.className = 'flash-message flash-success';
            toast.textContent = data.message;
            document.body.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 200); }, 1500);
        }
    })
    .catch(() => {
        alert('Failed to update quantity.');
    });
}
</script>
<?= $this->endSection() ?>
