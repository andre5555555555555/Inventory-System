<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php /* Hidden CSRF token for AJAX calls */ ?>
<input type="hidden" id="sc-csrf-name" value="<?= csrf_token() ?>">
<input type="hidden" id="sc-csrf-hash" value="<?= csrf_hash() ?>">

<div class="stockcard-layout" data-stockcard-layout>
    <button type="button" class="stockcard-sidebar-toggle" data-stockcard-toggle>Items</button>
    <div class="stockcard-sidebar-backdrop" id="stockcardSidebarBackdrop" data-stockcard-backdrop></div>

    <aside class="sidebar sidebar-flat stockcard-sidebar" id="itemSidebar">
        <div class="stockcard-sidebar-head">
            <h3>ITEM LIST</h3>
            <button type="button" class="stockcard-sidebar-close" data-stockcard-toggle>Close</button>
        </div>
        <div class="searchbar stockcard-sidebar-search">
            <input type="text" data-stockcard-search placeholder="Search item list">
        </div>
        <ul>
            <?php foreach ($items as $item): ?>
                <?php $itemLabel = trim($item['product'] . ' #' . ($item['product_no'] ?? '') . (($item['product_description'] ?? '') !== '' ? ' - ' . $item['product_description'] : '')); ?>
                <li data-stockcard-item data-label="<?= esc(strtolower($itemLabel), 'attr') ?>">
                    <a href="<?= site_url('stockcard?item_id=' . (int) $item['product_id']) ?>"><?= esc($item['product']) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <div class="main-content-stockcard">
        <div class="stockcard-shell">
            <div class="page-header">
                <div>
                    <p class="page-eyebrow">Inventory</p>
                    <h1>Stockcard</h1>
                    <p class="page-subtitle">Filter movement, review balances, and inspect transaction history in one flat workspace.</p>
                </div>
            </div>

            <!-- Inline filter bar — matches report page style -->
            <form method="get" action="<?= site_url('stockcard') ?>" class="toolbar-card report-toolbar stockcard-filter-bar">
                <input type="hidden" name="item_id" value="<?= (int) $itemId ?>">

                <div class="report-filter-group">
                    <label for="sc-order">Order</label>
                    <select id="sc-order" name="filter_type">
                        <option value="latest" <?= $filterType === 'latest' ? 'selected' : '' ?>>↓ Descending (Newest First)</option>
                        <option value="oldest" <?= $filterType === 'oldest' ? 'selected' : '' ?>>↑ Ascending (Oldest First)</option>
                    </select>
                </div>

                <div class="report-filter-group">
                    <label for="sc-month">Month</label>
                    <select id="sc-month" name="month">
                        <?php foreach (['' => 'All Months', '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'] as $value => $label): ?>
                            <option value="<?= esc($value) ?>" <?= $selectedMonth === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="report-filter-group">
                    <label for="sc-year">Year</label>
                    <select id="sc-year" name="year">
                        <option value="0" <?= (int) $selectedYear === 0 ? 'selected' : '' ?>>All Years</option>
                        <?php foreach (range(2000, (int) date('Y')) as $yearOption): ?>
                            <option value="<?= $yearOption ?>" <?= (int) $selectedYear === (int) $yearOption ? 'selected' : '' ?>><?= $yearOption ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="report-filter-actions sc-filter-actions">
                    <a href="<?= site_url('stock/add?item_id=' . (int) $itemId) ?>" class="add-btn sc-add-btn">+/- Stock</a>
                    <button type="submit" style="color:white;">Apply Filter</button>
                    <?php if ($stockcard): ?>
                    <button type="button" id="editModeToggle" class="sc-edit-mode-btn" aria-pressed="false">
                        <span>✏ Edit Mode</span>
                        <span class="sc-edit-badge">OFF</span>
                    </button>
                    <button type="button" id="scSaveAllBtn" class="sc-save-all-btn" style="display:none">
                        Save Changes
                    </button>
                    <?php endif; ?>
                </div>
            </form>


            <div class="info info-flat stockcard-info">
                <div class="info-header">
                    <div class="info-left">
                        <p><strong>PRODUCT:</strong> <?= esc($itemInfo['item_name'] ?? '') ?></p>
                        <p><strong>DESCRIPTION:</strong> <?= esc($itemInfo['description'] ?? '') ?></p>
                        <p><strong>UNIT:</strong> <?= esc($itemInfo['unit_name'] ?? '') ?></p>
                    </div>
                    <div class="info-right">
                        <p><strong>Stock No:</strong> <?= esc($itemInfo['stock_no'] ?? '') ?></p>
                        <p><strong>Re-order:</strong> <?= esc((string) ($itemInfo['re_order_point'] ?? '')) ?></p>
                        <p><strong>Entity:</strong> <?= esc($itemInfo['entity_name'] ?? '') ?></p>
                        <p><strong>Fund Cluster:</strong> <?= esc($itemInfo['fund_cluster'] ?? '') ?></p>
                    </div>
                </div>

                <?php if ($itemId > 0): ?>
                    <!-- Status bar shown while saving in edit mode -->
                    <div id="scEditStatus" class="sc-edit-status" style="display:none" aria-live="polite"></div>

                    <div class="info-table">
                        <table class="data-table sc-table" id="stockcard-table">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="sc-col-date">Date</th>
                                    <th rowspan="2" class="sc-col-ref">Reference</th>
                                    <th class="sc-col-group" style="text-align:center">Receipt</th>
                                    <th class="sc-col-group" colspan="2" style="text-align:center">Issue</th>
                                    <th rowspan="2" class="sc-col-bal">Balance</th>
                                    <th rowspan="2" class="sc-edit-col sc-col-act" style="display:none">Action</th>
                                </tr>
                                <tr>
                                    <th class="sc-sub">Qty</th>
                                    <th class="sc-sub">Qty</th>
                                    <th class="sc-sub">Office</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($stockcard)): ?>
                                    <tr class="sc-empty-row">
                                        <td colspan="7" class="sc-empty-cell">
                                            <div class="sc-empty-state">
                                                <span class="sc-empty-icon">📋</span>
                                                <p>No records found for the selected filter.</p>
                                                <a href="<?= site_url('stockcard?item_id=' . (int) $itemId) ?>">Clear filters</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($stockcard as $i => $row): ?>
                                        <?php
                                            $isReceipt = (int) $row['receipt_qty'] > 0;
                                            $origQty   = $isReceipt ? (int) $row['receipt_qty'] : (int) $row['issue_qty'];
                                        ?>
                                        <tr class="sc-data-row"
                                            data-transaction-id="<?= (int) $row['transaction_id'] ?>"
                                            data-is-receipt="<?= $isReceipt ? '1' : '0' ?>"
                                            data-orig-type="<?= $isReceipt ? '1' : '2' ?>"
                                            data-orig-qty="<?= $origQty ?>">
                                            <td class="sc-col-date"><?= esc(date('m/d/Y', strtotime($row['date']))) ?></td>
                                            <td class="sc-col-ref"><?= esc($row['reference']) ?></td>
                                            <!-- Receipt qty: editable only for receipt rows -->
                                            <td class="sc-receipt-cell sc-qty-cell">
                                                <span class="sc-qty-text"><?= $isReceipt ? $origQty : '' ?></span>
                                                <input type="number" min="1"
                                                    class="sc-qty-input sc-receipt-input"
                                                    value="<?= $isReceipt ? $origQty : '' ?>"
                                                    style="display:none"
                                                    <?= !$isReceipt ? 'disabled' : '' ?>>
                                            </td>
                                            <!-- Issue qty: editable only for issue rows -->
                                            <td class="sc-issue-cell sc-qty-cell">
                                                <span class="sc-qty-text"><?= !$isReceipt ? $origQty : '' ?></span>
                                                <input type="number" min="1"
                                                    class="sc-qty-input sc-issue-input"
                                                    value="<?= !$isReceipt ? $origQty : '' ?>"
                                                    style="display:none"
                                                    <?= $isReceipt ? 'disabled' : '' ?>>
                                            </td>
                                            <!-- Office: shown for any row that has one -->
                                            <td class="sc-office-cell">
                                                <?= esc($row['office'] ?? '') ?>
                                            </td>
                                            <td class="sc-balance-cell sc-col-bal"><?= esc((string) $row['balance']) ?></td>
                                            <!-- Action cell: visible only in edit mode -->
                                            <td class="sc-edit-col sc-col-act" style="display:none">
                                                <button type="button" class="sc-delete-btn">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($stockcard)): ?>
                    <div class="pagination">
                        <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                            <?php
                            $query = http_build_query([
                                'item_id'     => $itemId,
                                'page'        => $pageNumber,
                                'search'      => $search,
                                'filter_type' => $filterType,
                                'month'       => $selectedMonth,
                                'year'        => $selectedYear,
                            ]);
                            ?>
                            <a href="<?= site_url('stockcard?' . $query) ?>" class="<?= $pageNumber === (int) $page ? 'active-page' : '' ?>"><?= $pageNumber ?></a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<style>
/* ── Toolbar layout ─────────────────────────────────────── */
.stockcard-filter-bar.report-toolbar {
    flex-wrap: wrap;
    align-items: flex-end;
}
.sc-filter-actions {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    flex-wrap: wrap;
}
.sc-add-btn {
    height: 48px;
    display: inline-flex;
    align-items: center;
}


/* ── Stockcard table ────────────────────────────────────── */
.sc-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}
.sc-table th, .sc-table td {
    padding: 10px 12px;
    white-space: nowrap;
}
.sc-col-date  { width: 110px; }
.sc-col-ref   { width: 160px; white-space: normal; min-width: 120px; }
.sc-col-group { text-align: center; }
.sc-sub       { font-size: 0.8rem; letter-spacing: .04em; }
.sc-col-bal   { font-weight: 700; text-align: right; }
.sc-col-act   { width: 90px; text-align: center; }
.sc-qty-cell  { text-align: center; }
.sc-office-cell { font-size: 0.85rem; color: #476a6c; max-width: 180px; white-space: normal; }
.sc-balance-cell { font-weight: 700; text-align: right; }

/* Edit mode: amber row tint */
#stockcard-table.edit-mode-on .sc-data-row {
    background: rgba(251, 191, 36, 0.06) !important;
    outline: none;
    transition: background .15s;
}
#stockcard-table.edit-mode-on .sc-data-row:hover {
    background: rgba(251, 191, 36, 0.12) !important;
}

/* Pending delete: red strikethrough */
.sc-data-row.sc-pending-delete {
    opacity: 0.42;
    text-decoration: line-through;
    background: rgba(239, 68, 68, 0.07) !important;
}

/* ── Qty input ──────────────────────────────────────────── */
.sc-qty-input {
    width: 72px;
    padding: 5px 7px;
    border-radius: 6px;
    border: 1.5px solid #f59e0b;
    background: rgba(255,255,255,0.9);
    color: #0f3d3e;
    font-size: 0.9rem;
    text-align: center;
}

/* ── Delete button ──────────────────────────────────────── */
.sc-delete-btn {
    padding: 5px 10px;
    border-radius: 6px;
    border: 1px solid #ef4444;
    background: transparent;
    color: #ef4444;
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: 600;
    transition: background .15s, color .15s;
}
.sc-delete-btn:hover { background: #ef4444; color: #fff; }
.sc-pending-delete .sc-delete-btn {
    border-color: #9ca3af;
    color: #6b7280;
}

/* ── Empty state ────────────────────────────────────────── */
.sc-empty-cell { padding: 40px 20px !important; }
.sc-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    text-align: center;
    color: #476a6c;
}
.sc-empty-icon { font-size: 2.2rem; line-height: 1; }
.sc-empty-state p { margin: 0; font-size: 0.95rem; font-weight: 500; }
.sc-empty-state a {
    font-size: 0.85rem;
    color: #0f766e;
    text-decoration: underline;
    cursor: pointer;
}

/* ── Edit Mode button ───────────────────────────────────── */

.sc-edit-mode-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: 2px solid #94a3b8;
    border-radius: 8px;
    background: transparent;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: border-color .2s, color .2s;
}
.sc-edit-mode-btn .sc-edit-badge {
    padding: 2px 8px;
    border-radius: 20px;
    background: #cbd5e1;
    color: #334155;
    font-size: 0.72rem;
    letter-spacing: .06em;
    font-weight: 700;
    transition: background .2s, color .2s;
}
.sc-edit-mode-btn.is-active {
    border-color: #f59e0b;
    color: #b45309;
}
.sc-edit-mode-btn.is-active .sc-edit-badge {
    background: #f59e0b;
    color: #fff;
}

/* ── Save All button ────────────────────────────────────── */
.sc-save-all-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 18px;
    border: none;
    border-radius: 8px;
    background: #f59e0b;
    color: #fff;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    transition: background .2s;
}
.sc-save-all-btn:hover { background: #d97706; }
.sc-save-all-btn:disabled { opacity: .6; cursor: not-allowed; }

/* ── Edit mode table highlight ──────────────────────────── */
#stockcard-table.edit-mode-on .sc-data-row {
    background: rgba(251, 191, 36, 0.07);
}

/* ── Qty input inside cells ─────────────────────────────── */
.sc-qty-input {
    width: 72px;
    padding: 4px 6px;
    border-radius: 5px;
    border: 1.5px solid #f59e0b;
    background: rgba(255,255,255,0.8);
    color: #0f3d3e;
    font-size: 0.9rem;
}

/* ── Type select ────────────────────────────────────────── */
.sc-type-select {
    padding: 4px 6px;
    border-radius: 5px;
    border: 1.5px solid #0f766e;
    background: rgba(255,255,255,0.9);
    color: #0f3d3e;
    font-size: 0.82rem;
    font-weight: 600;
}

/* ── Delete button ──────────────────────────────────────── */
.sc-delete-btn {
    padding: 3px 8px;
    border-radius: 5px;
    border: 1px solid #ef4444;
    background: transparent;
    color: #ef4444;
    cursor: pointer;
    font-size: 0.9rem;
    transition: background .15s, color .15s;
}
.sc-delete-btn:hover { background: #ef4444; color: #fff; }

/* ── Pending delete row ─────────────────────────────────── */
.sc-data-row.sc-pending-delete {
    opacity: 0.45;
    text-decoration: line-through;
    background: rgba(239, 68, 68, 0.08) !important;
}
.sc-data-row.sc-pending-delete .sc-delete-btn {
    border-color: #6b7280;
    color: #6b7280;
}

/* ── Status bar ─────────────────────────────────────────── */
.sc-edit-status {
    padding: 8px 14px;
    border-radius: 8px;
    margin-bottom: 10px;
    font-size: 0.88rem;
    font-weight: 600;
    border: 1px solid transparent;
}
.sc-edit-status.is-success {
    background: #dcfce7;
    border-color: #86efac;
    color: #166534;
}
.sc-edit-status.is-error {
    background: #fee2e2;
    border-color: #fca5a5;
    color: #991b1b;
}
</style>

<script>
(function () {
    const editToggle  = document.getElementById('editModeToggle');
    const saveAllBtn  = document.getElementById('scSaveAllBtn');
    const statusBar   = document.getElementById('scEditStatus');
    const table       = document.getElementById('stockcard-table');
    const editUrl     = '<?= site_url('stock/edit-transaction') ?>';
    let editMode      = false;

    function getCsrf() {
        return {
            name:  document.getElementById('sc-csrf-name')?.value ?? '',
            hash:  document.getElementById('sc-csrf-hash')?.value ?? '',
        };
    }

    function showStatus(msg, type) {
        statusBar.textContent  = msg;
        statusBar.className    = 'sc-edit-status is-' + type;
        statusBar.style.display = '';
        if (type === 'success') {
            setTimeout(() => { statusBar.style.display = 'none'; }, 3000);
        }
    }

    const deleteUrl = '<?= site_url('stock/delete-transaction') ?>';
    const pendingDeletes = new Set(); // transaction IDs marked for deletion

    // ── Edit Mode toggle ──────────────────────────────────
    if (editToggle) {
        editToggle.addEventListener('click', function () {
            editMode = !editMode;
            const badge = editToggle.querySelector('.sc-edit-badge');
            badge.textContent = editMode ? 'ON' : 'OFF';
            editToggle.classList.toggle('is-active', editMode);
            editToggle.setAttribute('aria-pressed', String(editMode));
            saveAllBtn.style.display = editMode ? '' : 'none';
            table?.classList.toggle('edit-mode-on', editMode);
            statusBar.style.display = 'none';

            // Show/hide edit columns
            document.querySelectorAll('.sc-edit-col').forEach(el => {
                el.style.display = editMode ? '' : 'none';
            });

            document.querySelectorAll('.sc-data-row').forEach(row => {
                // toggle text vs input
                row.querySelectorAll('.sc-qty-text').forEach(s => s.style.display = editMode ? 'none' : '');
                // Only reveal the input that belongs to this row's type (not disabled)
                row.querySelectorAll('.sc-qty-input:not([disabled])').forEach(i => i.style.display = editMode ? '' : 'none');
                // reset on toggle off
                if (!editMode) {
                    row.querySelectorAll('.sc-qty-input:not([disabled])').forEach(i => i.value = row.dataset.origQty);
                    row.classList.remove('sc-pending-delete');
                    pendingDeletes.delete(row.dataset.transactionId);
                }
            });
        });
    }

    // ── Delete button ─────────────────────────────────────
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.sc-delete-btn');
        if (!btn) return;
        const row = btn.closest('.sc-data-row');
        const tid = row.dataset.transactionId;
        if (pendingDeletes.has(tid)) {
            pendingDeletes.delete(tid);
            row.classList.remove('sc-pending-delete');
            btn.textContent = '🗑';
            btn.title = 'Delete this transaction';
        } else {
            pendingDeletes.add(tid);
            row.classList.add('sc-pending-delete');
            btn.textContent = '↩';
            btn.title = 'Undo — keep this row';
        }
    });

    // ── Save All ──────────────────────────────────────────
    if (saveAllBtn) {
        saveAllBtn.addEventListener('click', async function () {
            const csrf = getCsrf();
            let errors = [];
            let ops    = 0;

            // ① Process deletions first
            for (const tid of pendingDeletes) {
                ops++;
                const data = new FormData();
                data.append('transaction_id', tid);
                if (csrf.name) data.append(csrf.name, csrf.hash);
                try {
                    const res  = await fetch(deleteUrl, { method: 'POST', body: data });
                    const json = await res.json();
                    if (!json.ok) errors.push('Delete #' + tid + ': ' + (json.error ?? 'Error'));
                } catch {
                    errors.push('Network error deleting #' + tid);
                }
            }

            // ② Process qty changes
            document.querySelectorAll('.sc-data-row:not(.sc-pending-delete)').forEach(row => {
                const origQty  = parseInt(row.dataset.origQty, 10);
                const input    = row.querySelector('.sc-qty-input:not([style*="none"])')
                              ?? row.querySelector('.sc-receipt-input');
                const newQty   = parseInt(input?.value ?? origQty, 10);

                if (newQty === origQty) return;
                if (isNaN(newQty) || newQty <= 0) { errors.push('Invalid qty in a row'); return; }

                ops++;
                row._pendingSave = { transactionId: row.dataset.transactionId, newQty };
            });

            const editRows = [...document.querySelectorAll('.sc-data-row[data-transaction-id]')]
                .filter(r => r._pendingSave);

            for (const row of editRows) {
                const { transactionId, newQty } = row._pendingSave;
                const data = new FormData();
                data.append('transaction_id', transactionId);
                data.append('new_qty', newQty);
                if (csrf.name) data.append(csrf.name, csrf.hash);
                try {
                    const res  = await fetch(editUrl, { method: 'POST', body: data });
                    const json = await res.json();
                    if (!json.ok) errors.push(json.error ?? 'Edit error');
                } catch {
                    errors.push('Network error editing #' + transactionId);
                }
            }

            if (ops === 0) { showStatus('No changes detected.', 'error'); return; }

            if (errors.length === 0) {
                showStatus('✓ Saved. Reloading…', 'success');
                setTimeout(() => location.reload(), 900);
            } else {
                showStatus('Errors: ' + errors.join(' | '), 'error');
            }
        });
    }
})();
</script>
<?= $this->endSection() ?>
