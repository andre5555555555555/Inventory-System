<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-shell stockout-page">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Temporary List</p>
            <h1>My Stock-Out List</h1>
            <p class="page-subtitle">Review and edit items before submitting for approval.</p>
        </div>
        <a href="<?= site_url('stockout') ?>" class="btn-secondary">+ Add More Items</a>
    </div>

    <?php $isDraft = ($draft['status'] ?? '') === 'draft'; ?>

    <?php if (empty($items)): ?>
        <div class="section-card">
            <div class="empty-state">
                <h3>No items in your list yet</h3>
                <p>Go to the <a href="<?= site_url('stockout') ?>">Stock Out page</a> to add items.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="section-card">
            <table class="data-table">
                <tr>
                    <th>Item</th>
                    <th>Unit</th>
                    <th>Description</th>
                    <th>Qty Requested</th>
                    <th>Stock Available</th>
                    <th>Status</th>
                    <?php if ($isDraft): ?>
                        <th>Action</th>
                    <?php endif; ?>
                </tr>
                <?php foreach ($items as $item):
                    $itemId       = (int) $item['temp_stockout_item_id'];
                    $quantity     = (int) $item['quantity'];
                    $currentStock = (int) ($item['current_stock'] ?? 0);
                ?>
                    <tr id="temp-item-<?= $itemId ?>">
                        <td><?= esc($item['item_name'] ?? '') ?></td>
                        <td><?= esc($item['unit'] ?? '') ?></td>
                        <td><?= esc($item['description'] ?? '') ?></td>

                        <!-- Quantity cell: shows value, toggled to input on Edit -->
                        <td class="qty-cell" id="qty-cell-<?= $itemId ?>">
                            <span class="qty-display" id="qty-display-<?= $itemId ?>"><?= $quantity ?></span>
                            <?php if ($isDraft): ?>
                            <form class="qty-edit-form" id="qty-form-<?= $itemId ?>"
                                  style="display:none;"
                                  onsubmit="saveTempQty(event, <?= $itemId ?>, <?= $currentStock ?>, '<?= esc($item['unit'] ?? '') ?>', '<?= esc(addslashes($item['description'] ?? '')) ?>')">
                                <input type="number"
                                       name="quantity"
                                       id="qty-input-<?= $itemId ?>"
                                       value="<?= $quantity ?>"
                                       min="1"
                                       max="<?= $currentStock > 0 ? $currentStock : 9999 ?>"
                                       class="inline-input"
                                       required>
                                <button type="submit" class="action-btn activate-btn" title="Save">✓</button>
                                <button type="button" class="action-btn" title="Cancel"
                                        onclick="cancelTempEdit(<?= $itemId ?>, <?= $quantity ?>)">✕</button>
                            </form>
                            <?php endif; ?>
                        </td>

                        <!-- Current stock with colour badge -->
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

                        <!-- Status -->
                        <td>
                            <span class="status-badge status-<?= strtolower(esc($item['status'] ?? 'pending')) ?>">
                                <?= esc(ucfirst($item['status'] ?? 'pending')) ?>
                            </span>
                        </td>

                        <?php if ($isDraft): ?>
                            <td class="action-cell" id="action-cell-<?= $itemId ?>">
                                <button class="action-btn edit-btn"
                                        onclick="startTempEdit(<?= $itemId ?>)"
                                        id="edit-btn-<?= $itemId ?>">Edit</button>
                                <button class="action-btn delete-btn"
                                        id="remove-btn-<?= $itemId ?>"
                                        onclick="removeTempItem(<?= $itemId ?>)">Remove</button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </table>

            <?php if ($isDraft): ?>
                <div class="submit-bar">
                    <form method="post" action="<?= site_url('stockout/submit') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-primary btn-submit-approval"
                                onclick="return confirm('Submit this list for approval? You won\'t be able to edit after submission.')">
                            Submit for Approval
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="submit-bar">
                    <span class="status-badge status-<?= strtolower(esc($draft['status'] ?? '')) ?>">
                        Request Status: <?= esc(ucfirst($draft['status'] ?? '')) ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
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
function startTempEdit(itemId) {
    document.getElementById('qty-display-' + itemId).style.display = 'none';
    document.getElementById('qty-form-'    + itemId).style.display = '';
    document.getElementById('edit-btn-'    + itemId).style.display = 'none';
    document.getElementById('remove-btn-'  + itemId).style.display = 'none';
    document.getElementById('qty-input-'   + itemId).focus();
}

function cancelTempEdit(itemId, originalQty) {
    const input = document.getElementById('qty-input-' + itemId);
    input.value = originalQty;
    input.style.borderColor = '';

    document.getElementById('qty-display-' + itemId).style.display = '';
    document.getElementById('qty-form-'    + itemId).style.display = 'none';
    document.getElementById('edit-btn-'    + itemId).style.display = '';
    document.getElementById('remove-btn-'  + itemId).style.display = '';
}

// ── Save quantity via AJAX ────────────────────────────────────────────────
function saveTempQty(event, itemId, maxStock, unit, description) {
    event.preventDefault();
    const form    = event.target;
    if (form.dataset.submitting === 'true') return;

    const input     = document.getElementById('qty-input-' + itemId);
    const saveBtn   = form.querySelector('button[type="submit"]');
    const cancelBtn = form.querySelector('button[type="button"]');
    const newQty    = parseInt(input.value, 10);

    if (!newQty || newQty < 1) {
        input.style.borderColor = '#ef4444';
        if (typeof showToast === 'function') showToast('Quantity must be at least 1.', 'error');
        return;
    }
    if (maxStock > 0 && newQty > maxStock) {
        input.style.borderColor = '#ef4444';
        const msg = `Cannot exceed available stock (${maxStock}).`;
        if (typeof showToast === 'function') showToast(msg, 'error');
        else alert(msg);
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

    fetch(`${window.appConfig.baseUrl}stockout/edit-temp/${itemId}`, {
        method: 'POST',
        headers,
        body: new URLSearchParams({ quantity: newQty, unit, description }),
    })
    .then(async r => {
        const data = await r.json();
        if (!r.ok) throw new Error(data.message ?? 'Update failed.');

        document.getElementById('qty-display-' + itemId).textContent = newQty;
        cancelTempEdit(itemId, newQty);
        if (typeof showToast === 'function') showToast('Quantity updated.', 'success');
    })
    .catch(err => {
        input.style.borderColor = '#ef4444';
        setTimeout(() => input.style.borderColor = '', 2000);
        if (typeof showToast === 'function') showToast(err.message ?? 'Failed to update.', 'error');
        else alert(err.message ?? 'Failed to update.');
    })
    .finally(() => {
        form.dataset.submitting = 'false';
        input.disabled = false;
        if (saveBtn)   { saveBtn.disabled   = false; saveBtn.textContent   = '✓'; }
        if (cancelBtn) { cancelBtn.disabled = false; }
    });
}

// ── Remove item via AJAX ──────────────────────────────────────────────────
function removeTempItem(itemId) {
    if (!confirm('Remove this item from your list?')) return;

    const btn = document.getElementById('remove-btn-' + itemId);
    if (btn) { btn.disabled = true; btn.textContent = '…'; }

    const headers = { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' };
    const csrfMeta = document.querySelector(`meta[name="${window.appConfig?.csrfHeader ?? ''}"]`);
    if (csrfMeta && window.appConfig?.csrfHeader) {
        headers[window.appConfig.csrfHeader] = csrfMeta.content;
    }

    fetch(`${window.appConfig.baseUrl}stockout/remove-temp/${itemId}`, {
        method: 'POST',
        headers,
        body: '',
    })
    .then(async r => {
        const data = await r.json();
        if (!r.ok) throw new Error(data.message ?? 'Remove failed.');

        // Fade out and remove the row
        const row = document.getElementById('temp-item-' + itemId);
        if (row) {
            row.style.transition = 'opacity .3s';
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 320);
        }
        if (typeof showToast === 'function') showToast('Item removed.', 'success');
    })
    .catch(err => {
        if (btn) { btn.disabled = false; btn.textContent = 'Remove'; }
        if (typeof showToast === 'function') showToast(err.message ?? 'Failed to remove.', 'error');
        else alert(err.message ?? 'Failed to remove.');
    });
}
</script>
<?= $this->endSection() ?>
