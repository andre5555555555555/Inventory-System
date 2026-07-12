<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-shell">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Reports</p>
            <h1>Inventory Report</h1>
            <p class="page-subtitle">Review beginning, purchase, usage, spoiled, and ending balances in a cleaner ledger view.</p>
        </div>
    </div>

    <form method="get" action="<?= site_url('batchlist') ?>" class="toolbar-card report-toolbar">
        <div class="report-filter-group report-filter-search">
            <label for="report-search">Search Product</label>
            <input id="report-search" type="text" name="search" value="<?= esc($search) ?>" placeholder="Search product name">
        </div>

        <div class="report-filter-group">
            <label for="report-type">Product Type</label>
            <select id="report-type" name="type_id">
                <option value="0">All Product Types</option>
                <?php foreach (($productTypes ?? []) as $pType): ?>
                    <option value="<?= (int) $pType['type_id'] ?>" <?= (int) ($typeId ?? 0) === (int) $pType['type_id'] ? 'selected' : '' ?>>
                        <?= esc($pType['type']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="report-filter-group">
            <label for="report-month">Month</label>
            <select id="report-month" name="month">
                <?php foreach (['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Aug','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dec'] as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $month === $value ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="report-filter-group">
            <label for="report-year">Year</label>
            <select id="report-year" name="year">
                <?php for ($y = 2020; $y <= (int) date('Y'); $y++): ?>
                    <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="report-filter-actions">
            <button type="submit" style="color: white;">Apply Filter</button>
        </div>

        <!-- Edit mode controls (right-aligned) -->
        <div class="report-edit-controls">
            <button type="button" id="rptEditToggle" class="sc-edit-mode-btn" aria-pressed="false">
                <span>✏ Edit Mode</span>
                <span class="sc-edit-badge">OFF</span>
            </button>
            <button type="button" id="rptSaveBtn" class="sc-save-all-btn" style="display:none">Save Changes</button>
        </div>
    </form>

    <!-- CSRF for AJAX cost saves -->
    <input type="hidden" id="rpt-csrf-name" value="<?= csrf_token() ?>">
    <input type="hidden" id="rpt-csrf-hash" value="<?= csrf_hash() ?>">

    <?php foreach (($groupedRows ?? []) as $productType => $typeRows): ?>
        <div class="panel-card table-card report-table-card report-table-card-squared">
            <div class="report-table-title"><?= esc($productType) ?></div>
            <table class="data-table report-table report-table-squared">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Stock No</th>
                        <th>Product</th>
                        <th colspan="4">BEGINNING</th>
                        <th colspan="3">PURCHASE</th>
                        <th colspan="3">USED</th>
                        <th colspan="3">SPOILED</th>
                        <th colspan="3">ENDING</th>
                    </tr>
                    <tr>
                        <th></th><th></th><th></th>
                        <th>Qty</th><th>Unit</th><th>Cost</th><th>Amount</th>
                        <th>Qty</th><th>Cost</th><th>Amount</th>
                        <th>Qty</th><th>Cost</th><th>Amount</th>
                        <th>Qty</th><th>Cost</th><th>Amount</th>
                        <th>Qty</th><th>Cost</th><th>Amount</th>
                    </tr>
                </thead>
                    <tbody>
                    <?php foreach ($typeRows as $row): ?>
                        <tr class="rpt-data-row"
                            data-product-id="<?= (int) $row['product_id'] ?>"
                            data-year="<?= (int) $year ?>"
                            data-month="<?= (int) $month ?>">
                            <td><?= $row['counter'] ?></td>
                            <td><?= esc($row['stock_no']) ?></td>
                            <td><?= esc($row['item']) ?></td>
                            <!-- BEGINNING -->
                            <td><?= $row['begin_qty'] ?></td>
                            <td><?= esc($row['unit_name']) ?></td>
                            <td><?= number_format($row['begin_cost'], 2) ?></td>
                            <td><?= number_format($row['begin_qty'] * $row['begin_cost'], 2) ?></td>
                            <!-- PURCHASE -->
                            <td><?= $row['purchase_qty'] ?></td>
                            <td class="rpt-cost-cell<?= $row['purchase_cost'] > 0 ? ' rpt-editable' : '' ?>" data-cost-type="purchase" data-qty="<?= $row['purchase_qty'] ?>">
                                <span class="rpt-cost-text"><?= number_format($row['purchase_cost'], 2) ?></span>
                                <?php if ($row['purchase_cost'] > 0): ?>
                                <input type="number" min="0.01" step="0.01"
                                    class="rpt-cost-input"
                                    value="<?= number_format($row['purchase_cost'], 2, '.', '') ?>"
                                    style="display:none">
                                <?php endif; ?>
                            </td>
                            <td class="rpt-amount-purchase"><?= number_format($row['purchase_total'], 2) ?></td>
                            <!-- USED -->
                            <td><?= $row['used_qty'] ?></td>
                            <td class="rpt-cost-cell<?= $row['used_cost'] > 0 ? ' rpt-editable' : '' ?>" data-cost-type="used" data-qty="<?= $row['used_qty'] ?>">
                                <span class="rpt-cost-text"><?= number_format($row['used_cost'], 2) ?></span>
                                <?php if ($row['used_cost'] > 0): ?>
                                <input type="number" min="0.01" step="0.01"
                                    class="rpt-cost-input"
                                    value="<?= number_format($row['used_cost'], 2, '.', '') ?>"
                                    style="display:none">
                                <?php endif; ?>
                            </td>
                            <td class="rpt-amount-used"><?= number_format($row['used_total'], 2) ?></td>
                            <!-- SPOILED -->
                            <td><?= $row['spoiled_qty'] ?></td>
                            <td class="rpt-cost-cell<?= $row['spoiled_cost'] > 0 ? ' rpt-editable' : '' ?>" data-cost-type="spoiled" data-qty="<?= $row['spoiled_qty'] ?>">
                                <span class="rpt-cost-text"><?= number_format($row['spoiled_cost'], 2) ?></span>
                                <?php if ($row['spoiled_cost'] > 0): ?>
                                <input type="number" min="0.01" step="0.01"
                                    class="rpt-cost-input"
                                    value="<?= number_format($row['spoiled_cost'], 2, '.', '') ?>"
                                    style="display:none">
                                <?php endif; ?>
                            </td>
                            <td class="rpt-amount-spoiled"><?= number_format($row['spoiled_total'], 2) ?></td>
                            <!-- ENDING -->
                            <td><?= $row['ending_qty'] ?></td>
                            <td><?= number_format($row['ending_cost'], 2) ?></td>
                            <td><?= number_format($row['ending_qty'] * $row['ending_cost'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>

<style>
/* ── Report edit mode controls ───────────────────────────── */
.report-toolbar { flex-wrap: wrap; align-items: flex-end; }
.report-edit-controls {
    margin-left: auto;
    display: flex;
    align-items: flex-end;
    gap: 8px;
    padding-bottom: 2px;
}
/* ── Cost input in report table ──────────────────────────── */
.rpt-cost-input {
    width: 90px;
    padding: 4px 6px;
    border-radius: 6px;
    border: 1.5px solid #f59e0b;
    background: rgba(255,255,255,0.9);
    font-size: 0.88rem;
    text-align: right;
    color: #0f3d3e;
}
/* Edited cell highlight */
.rpt-cost-cell.rpt-edited { background: rgba(251,191,36,0.1); }
/* Status bar */
#rptEditStatus {
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 0.88rem;
    font-weight: 600;
    margin-bottom: 10px;
    display: none;
}
#rptEditStatus.success { background: #dcfce7; color: #166534; }
#rptEditStatus.error   { background: #fee2e2; color: #991b1b; }
</style>

<div id="rptEditStatus"></div>

<script>
(function () {
    const editToggle = document.getElementById('rptEditToggle');
    const saveBtn    = document.getElementById('rptSaveBtn');
    const statusBar  = document.getElementById('rptEditStatus');
    const saveUrl    = '<?= site_url('stock/edit-report-cost') ?>';
    let editMode     = false;

    function getCsrf() {
        return {
            name: document.getElementById('rpt-csrf-name')?.value ?? '',
            hash: document.getElementById('rpt-csrf-hash')?.value ?? '',
        };
    }

    function showStatus(msg, type) {
        statusBar.textContent = msg;
        statusBar.className   = type;
        statusBar.style.display = 'block';
    }

    // ── Edit Mode toggle ──────────────────────────────────
    editToggle?.addEventListener('click', function () {
        editMode = !editMode;
        const badge = editToggle.querySelector('.sc-edit-badge');
        badge.textContent = editMode ? 'ON' : 'OFF';
        editToggle.classList.toggle('is-active', editMode);
        editToggle.setAttribute('aria-pressed', String(editMode));
        saveBtn.style.display = editMode ? '' : 'none';
        statusBar.style.display = 'none';

        document.querySelectorAll('.rpt-cost-cell.rpt-editable').forEach(cell => {
            const text  = cell.querySelector('.rpt-cost-text');
            const input = cell.querySelector('.rpt-cost-input');
            if (text)  text.style.display  = editMode ? 'none' : '';
            if (input) input.style.display = editMode ? '' : 'none';
            if (!editMode) {
                if (input) input.value = parseFloat(text?.textContent.replace(/,/g, '') ?? 0).toFixed(2);
                cell.classList.remove('rpt-edited');
            }
        });
    });

    // ── Live amount recalculation ─────────────────────────
    document.addEventListener('input', function (e) {
        const input = e.target.closest('.rpt-cost-input');
        if (!input || !editMode) return;
        const cell    = input.closest('.rpt-cost-cell');
        const row     = cell.closest('.rpt-data-row');
        const qty     = parseFloat(cell.dataset.qty ?? 0);
        const cost    = parseFloat(input.value ?? 0);
        const type    = cell.dataset.costType;
        cell.classList.add('rpt-edited');

        // Update the amount cell in the same row
        const amountCell = row.querySelector(`.rpt-amount-${type}`);
        if (amountCell) amountCell.textContent = (qty * cost).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    });

    // ── Save Changes ──────────────────────────────────────
    saveBtn?.addEventListener('click', async function () {
        const csrf   = getCsrf();
        const rows   = [...document.querySelectorAll('.rpt-data-row')];
        let ops = 0, errors = [];

        for (const row of rows) {
            const productId = row.dataset.productId;
            const year      = row.dataset.year;
            const month     = row.dataset.month;

            for (const cell of row.querySelectorAll('.rpt-cost-cell.rpt-edited')) {
                const costType = cell.dataset.costType;
                const newCost  = parseFloat(cell.querySelector('.rpt-cost-input')?.value ?? 0);
                ops++;

                const data = new FormData();
                data.append('product_id', productId);
                data.append('cost_type',  costType);
                data.append('new_cost',   newCost);
                data.append('year',       year);
                data.append('month',      month);
                if (csrf.name) data.append(csrf.name, csrf.hash);

                try {
                    const res  = await fetch(saveUrl, { method: 'POST', body: data });
                    const json = await res.json();
                    if (!json.ok) errors.push(json.error ?? 'Error saving ' + costType + ' cost');
                } catch {
                    errors.push('Network error for product ' + productId);
                }
            }
        }

        if (ops === 0) { showStatus('No cost changes detected.', 'error'); return; }

        if (errors.length === 0) {
            showStatus('✓ Costs saved. Reloading…', 'success');
            setTimeout(() => location.reload(), 900);
        } else {
            showStatus('Errors: ' + errors.join(' | '), 'error');
        }
    });

})();
</script>
<?= $this->endSection() ?>
