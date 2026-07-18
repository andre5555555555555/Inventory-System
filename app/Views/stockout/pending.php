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
                        <th>Qty Requested</th>
                        <th>Stock Available</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($request['items'] as $item): ?>
                        <?php
                            $itemIds      = $item['item_ids'] ?? '';
                            $firstItemId  = $itemIds ? (int) explode(',', $itemIds)[0] : 0;
                            $currentStock = (int) ($item['current_stock'] ?? 0);
                            $quantity     = (int) $item['quantity'];
                            $isPending    = ($item['status'] ?? '') === 'pending';
                        ?>
                        <tr id="stockout-item-<?= $firstItemId ?>">
                            <td><?= esc($item['item_name'] ?? '') ?></td>
                            <td><?= esc($item['unit'] ?? '') ?></td>
                            <td><?= esc($item['description'] ?? '') ?></td>

                            <!-- Quantity cell: shows value, toggled to input on Edit -->
                            <td class="qty-cell" id="qty-cell-<?= $firstItemId ?>">
                                <span class="qty-display" id="qty-display-<?= $firstItemId ?>"><?= $quantity ?></span>
                                <?php if ($isPending && $levelId >= 2): ?>
                                <form class="qty-edit-form" id="qty-form-<?= $firstItemId ?>"
                                      style="display:none;"
                                      onsubmit="saveQty(event, <?= $firstItemId ?>, <?= $currentStock ?>)">
                                    <input type="number"
                                           name="quantity"
                                           id="qty-input-<?= $firstItemId ?>"
                                           value="<?= $quantity ?>"
                                           min="1"
                                           max="<?= $currentStock ?>"
                                           class="inline-input"
                                           required>
                                    <button type="submit" class="action-btn activate-btn" title="Save">✓</button>
                                    <button type="button" class="action-btn" title="Cancel"
                                            onclick="cancelEdit(<?= $firstItemId ?>, <?= $quantity ?>)">✕</button>
                                </form>
                                <?php endif; ?>
                            </td>

                            <!-- Current stock with warning badge if over-requested -->
                            <td>
                                <?php if ($currentStock <= 0): ?>
                                    <span class="stock-badge stock-zero">0 — No stock</span>
                                <?php elseif ($quantity > $currentStock): ?>
                                    <span class="stock-badge stock-over"><?= $currentStock ?> — Over!</span>
                                <?php elseif ($currentStock <= 5): ?>
                                    <span class="stock-badge stock-low"><?= $currentStock ?></span>
                                <?php else: ?>
                                    <span class="stock-badge stock-ok"><?= $currentStock ?></span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="status-badge status-<?= strtolower(esc($item['status'] ?? 'pending')) ?>">
                                    <?= esc(ucfirst($item['status'] ?? 'pending')) ?>
                                </span>
                            </td>

                            <td class="action-cell" id="action-cell-<?= $firstItemId ?>">
                                <?php if ($isPending): ?>
                                    <?php if ($levelId >= 2): ?>
                                        <button class="action-btn edit-btn"
                                                onclick="startEdit(<?= $firstItemId ?>)"
                                                id="edit-btn-<?= $firstItemId ?>">Edit</button>
                                    <?php endif; ?>
                                    <button class="action-btn activate-btn"
                                            onclick="approveStockoutItem(<?= $firstItemId ?>)"
                                            id="approve-btn-<?= $firstItemId ?>">Accept</button>
                                    <button class="action-btn delete-btn"
                                            onclick="rejectStockoutItem(<?= $firstItemId ?>)">Reject</button>
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

<style>
.qty-cell { white-space: nowrap; }
.qty-display { font-weight: 600; font-size: 15px; }
.qty-edit-form { display: inline-flex; align-items: center; gap: 4px; }
.stock-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.stock-ok   { background: #dcfce7; color: #15803d; }
.stock-low  { background: #fef3c7; color: #b45309; }
.stock-zero { background: #fee2e2; color: #b91c1c; }
.stock-over { background: #fef08a; color: #b45309; border: 1px solid #fde047; }
</style>

<script>
// ── Edit / Cancel helpers ─────────────────────────────────────────────────
function startEdit(itemId) {
    document.getElementById('qty-display-' + itemId).style.display = 'none';
    document.getElementById('qty-form-'    + itemId).style.display = '';
    document.getElementById('edit-btn-'    + itemId).style.display = 'none';
    document.getElementById('approve-btn-' + itemId).style.display = 'none';
    document.getElementById('qty-input-'   + itemId).focus();
}

function cancelEdit(itemId, originalQty) {
    const input = document.getElementById('qty-input-' + itemId);
    input.value = originalQty;
    input.style.borderColor = '';

    document.getElementById('qty-display-' + itemId).style.display = '';
    document.getElementById('qty-form-'    + itemId).style.display = 'none';
    document.getElementById('edit-btn-'    + itemId).style.display = '';
    document.getElementById('approve-btn-' + itemId).style.display = '';
}

// ── Save quantity via AJAX ────────────────────────────────────────────────
function saveQty(event, itemId, maxStock) {
    event.preventDefault();
    const form      = event.target;
    if (form.dataset.submitting === 'true') return;

    const input     = document.getElementById('qty-input-' + itemId);
    const saveBtn   = form.querySelector('button[type="submit"]');
    const cancelBtn = form.querySelector('button[type="button"]');
    const newQty    = parseInt(input.value, 10);

    // Validate against available stock
    if (!newQty || newQty < 1) {
        input.style.borderColor = '#ef4444';
        if (typeof showToast === 'function') showToast('Quantity must be at least 1.', 'error');
        return;
    }
    if (newQty > maxStock) {
        input.style.borderColor = '#ef4444';
        if (typeof showToast === 'function') {
            showToast(`Cannot exceed available stock (${maxStock}).`, 'error');
        } else {
            alert(`Cannot exceed available stock (${maxStock}).`);
        }
        input.value = maxStock;
        return;
    }

    // Lock UI
    form.dataset.submitting = 'true';
    input.disabled = true;
    if (saveBtn)   { saveBtn.disabled   = true; saveBtn.textContent   = '…'; }
    if (cancelBtn) { cancelBtn.disabled = true; }

    const headers = { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' };
    const csrfMeta = document.querySelector(`meta[name="${window.appConfig?.csrfHeader ?? ''}"]`);
    if (csrfMeta && window.appConfig?.csrfHeader) {
        headers[window.appConfig.csrfHeader] = csrfMeta.content;
    }

    fetch(`${window.appConfig.baseUrl}stockout/edit-pending/${itemId}`, {
        method: 'POST',
        headers,
        body: new URLSearchParams({ quantity: newQty }),
    })
    .then(async r => {
        const data = await r.json();
        if (!r.ok) throw new Error(data.message ?? 'Update failed.');

        // Update the display span and close the edit mode
        document.getElementById('qty-display-' + itemId).textContent = newQty;
        cancelEdit(itemId, newQty);

        if (typeof showToast === 'function') showToast('Quantity updated', 'success');
    })
    .catch(err => {
        input.style.borderColor = '#ef4444';
        setTimeout(() => input.style.borderColor = '', 2000);
        if (typeof showToast === 'function') {
            showToast(err.message ?? 'Failed to update quantity.', 'error');
        } else {
            alert(err.message ?? 'Failed to update quantity.');
        }
    })
    .finally(() => {
        form.dataset.submitting = 'false';
        input.disabled = false;
        if (saveBtn)   { saveBtn.disabled   = false; saveBtn.textContent   = '✓'; }
        if (cancelBtn) { cancelBtn.disabled = false; }
    });
}
</script>
<?= $this->endSection() ?>
