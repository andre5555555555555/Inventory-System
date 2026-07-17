<?php $this->extend('layouts/main') ?>

<?php $this->section('content') ?>
<div class="page-shell stockout-page">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Stock Out</p>
            <h1>Request Stock Out</h1>
            <p class="page-subtitle">Scan a barcode or select a product manually to add to your temporary stock-out list.</p>
        </div>
        <a href="<?= site_url('stockout/temp') ?>" class="btn-primary">View My Temp List</a>
    </div>

    <!-- ── BARCODE SCANNER SECTION ─────────────────────────────────────────── -->
    <div class="section-card scanner-card">
        <h2>📷 Barcode Scanner</h2>
        <p class="scanner-hint">Focus the field below and scan a barcode, or type a barcode value and press Enter.</p>

        <div class="scanner-input-row">
            <input
                type="text"
                id="barcodeInput"
                class="scanner-input"
                placeholder="Scan or type barcode..."
                autocomplete="off"
                autofocus
                data-lookup-url="<?= site_url('barcode/lookup') ?>"
            >
            <button type="button" id="barcodeLookupBtn" class="btn-primary">Look Up</button>
            <button type="button" id="barcodeClearBtn" class="btn-secondary" style="display:none">Clear</button>
        </div>

        <!-- Result card — hidden until a successful lookup -->
        <div id="barcodeResult" class="barcode-result-card" style="display:none">
            <div class="barcode-result-header">
                <span id="brProduct" class="barcode-result-product"></span>
                <span id="brBatch"   class="barcode-result-batch"></span>
            </div>
            <div class="barcode-result-body">
                <div class="barcode-result-stat">
                    <span class="stat-label">Unit</span>
                    <span id="brUnit" class="stat-value"></span>
                </div>
                <div class="barcode-result-stat">
                    <span class="stat-label">Remaining Qty</span>
                    <span id="brQty" class="stat-value barcode-qty"></span>
                </div>
                <div class="barcode-result-stat">
                    <span class="stat-label">Expiry Date</span>
                    <span id="brExpiry" class="stat-value"></span>
                </div>
                <div class="barcode-result-stat">
                    <span class="stat-label">Date Received</span>
                    <span id="brReceived" class="stat-value"></span>
                </div>
            </div>
            <div class="barcode-result-footer">
                <button type="button" id="brFillFormBtn" class="btn-primary">
                    ↓ Use this product in the form below
                </button>
            </div>
        </div>

        <!-- Error message -->
        <div id="barcodeError" class="barcode-error" style="display:none"></div>
    </div>

    <!-- ── MANUAL FORM SECTION ─────────────────────────────────────────────── -->
    <div class="section-card" id="manualFormSection">
        <div class="stockout-form-card">
            <h2>Add Product to Stock-Out</h2>
            <form method="post" action="<?= site_url('stockout/add-temp') ?>" class="stockout-form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="product_id">Product</label>
                    <select name="product_id" id="product_id" required onchange="fillProductDetails(this)">
                        <option value="">Select Product</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= (int) $item['product_id'] ?>"
                                    data-unit="<?= esc($item['unit_name'] ?? '') ?>"
                                    data-desc="<?= esc($item['product_description'] ?? $item['description'] ?? '') ?>"
                                    data-stock="<?= (int) $item['current_stock'] ?>">
                                <?= esc($item['product']) ?> (Stock: <?= (int) $item['current_stock'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="unit">Unit</label>
                        <input type="text" name="unit" id="unit" placeholder="e.g. box, pcs" readonly>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" name="quantity" id="quantity" min="1" placeholder="Enter quantity" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" name="description" id="description" placeholder="Product description" readonly>
                </div>

                <button type="submit" class="btn-primary">Add to Temp List</button>
            </form>
        </div>
    </div>

    <!-- ── AVAILABLE PRODUCTS TABLE ───────────────────────────────────────── -->
    <div class="section-card">
        <h2>Available Products</h2>
        <input type="text" class="search-input" id="stockoutSearch" placeholder="Search products..." onkeyup="filterStockoutTable()">
        <table class="data-table" id="stockoutItemsTable">
            <tr>
                <th>Product</th>
                <th>Unit</th>
                <th>Description</th>
                <th>Current Stock</th>
            </tr>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= esc($item['product']) ?></td>
                    <td><?= esc($item['unit_name'] ?? '') ?></td>
                    <td><?= esc($item['product_description'] ?? $item['description'] ?? '') ?></td>
                    <td><?= (int) $item['current_stock'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<style>
/* ── Scanner card ──────────────────────────────────────────────────────── */
.scanner-card {
    border: 2px dashed #0f766e;
}
.scanner-hint {
    font-size: 13px;
    color: #64748b;
    margin: 0 0 12px;
}
.scanner-input-row {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}
.scanner-input {
    flex: 1;
    min-width: 200px;
    height: 46px;
    padding: 0 14px;
    font-size: 15px;
    border-radius: 8px;
    border: 2px solid #0f766e;
    outline: none;
    letter-spacing: 0.04em;
    transition: box-shadow 0.18s;
}
.scanner-input:focus {
    box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.14);
}

/* ── Barcode result card ────────────────────────────────────────────────── */
.barcode-result-card {
    margin-top: 16px;
    border: 1.5px solid #0f766e;
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(15,118,110,0.04) 0%, rgba(255,255,255,0) 100%);
    overflow: hidden;
    animation: fadeSlideIn 0.22s ease;
}
@keyframes fadeSlideIn {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}
.barcode-result-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 18px;
    background: #0f766e;
    color: #fff;
}
.barcode-result-product { font-size: 15px; font-weight: 700; }
.barcode-result-batch   { font-size: 12px; opacity: 0.8; letter-spacing: 0.05em; }
.barcode-result-body {
    display: flex;
    gap: 0;
    flex-wrap: wrap;
    padding: 14px 18px;
}
.barcode-result-stat {
    flex: 1;
    min-width: 130px;
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 8px 12px;
    border-right: 1px solid #e2e8f0;
}
.barcode-result-stat:last-child { border-right: none; }
.stat-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
}
.stat-value   { font-size: 16px; font-weight: 600; color: #0f3d3e; }
.barcode-qty  { color: #0f766e; font-size: 20px; }
.barcode-result-footer {
    padding: 10px 18px 14px;
    border-top: 1px solid #e2e8f0;
}

/* ── Error message ─────────────────────────────────────────────────────── */
.barcode-error {
    margin-top: 12px;
    padding: 10px 16px;
    border-radius: 8px;
    background: #fee2e2;
    color: #991b1b;
    font-size: 13px;
    font-weight: 600;
    border: 1px solid #fecaca;
}
</style>

<script>
// ── Existing stockout helpers (unchanged) ───────────────────────────────────
function fillProductDetails(select) {
    const option = select.options[select.selectedIndex];
    document.getElementById('unit').value = option.dataset.unit || '';
    document.getElementById('description').value = option.dataset.desc || '';
    const maxStock = parseInt(option.dataset.stock) || 0;
    document.getElementById('quantity').max = maxStock;
}

function filterStockoutTable() {
    const query = document.getElementById('stockoutSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#stockoutItemsTable tr');
    rows.forEach((row, i) => {
        if (i === 0) return;
        row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
}

// ── Barcode scanner logic ───────────────────────────────────────────────────
(function () {
    const input      = document.getElementById('barcodeInput');
    const lookupBtn  = document.getElementById('barcodeLookupBtn');
    const clearBtn   = document.getElementById('barcodeClearBtn');
    const resultCard = document.getElementById('barcodeResult');
    const errorDiv   = document.getElementById('barcodeError');
    const lookupUrl  = input?.dataset.lookupUrl ?? '';

    // Cached batch data for the "fill form" action
    let lastResult = null;

    function hideResult() {
        resultCard.style.display = 'none';
        errorDiv.style.display   = 'none';
        clearBtn.style.display   = 'none';
        lastResult = null;
    }

    function showError(msg) {
        resultCard.style.display = 'none';
        errorDiv.textContent     = msg;
        errorDiv.style.display   = '';
        clearBtn.style.display   = '';
    }

    function showResult(data) {
        lastResult = data;

        document.getElementById('brProduct').textContent  = data.product;
        document.getElementById('brBatch').textContent    = 'Batch: ' + data.batch_no;
        document.getElementById('brUnit').textContent     = data.unit_name;
        document.getElementById('brQty').textContent      = data.current_qty;
        document.getElementById('brExpiry').textContent   = data.expiration_date || '—';
        document.getElementById('brReceived').textContent = data.date_received || '—';

        resultCard.style.display = '';
        errorDiv.style.display   = 'none';
        clearBtn.style.display   = '';
    }

    async function doLookup() {
        const value = input.value.trim();
        if (! value) return;

        lookupBtn.disabled = true;
        lookupBtn.textContent = '…';

        try {
            const res  = await fetch(lookupUrl + '?value=' + encodeURIComponent(value), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();

            if (! res.ok) {
                showError(data.error ?? 'Barcode not found.');
            } else {
                showResult(data);
            }
        } catch {
            showError('Network error. Please try again.');
        } finally {
            lookupBtn.disabled = false;
            lookupBtn.textContent = 'Look Up';
        }
    }

    // Trigger lookup on Enter key (barcode scanners send Enter)
    input?.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            doLookup();
        }
    });

    lookupBtn?.addEventListener('click', doLookup);

    clearBtn?.addEventListener('click', () => {
        input.value = '';
        hideResult();
        input.focus();
    });

    // Pre-fill the manual form from scanner result
    document.getElementById('brFillFormBtn')?.addEventListener('click', () => {
        if (! lastResult) return;

        const select = document.getElementById('product_id');
        // Try to find the matching option
        for (const option of select.options) {
            if (parseInt(option.value) === lastResult.product_id) {
                select.value = option.value;
                fillProductDetails(select);
                // Pre-set quantity field max
                document.getElementById('quantity').max = lastResult.current_qty;
                break;
            }
        }

        // Scroll to the manual form
        document.getElementById('manualFormSection')?.scrollIntoView({ behavior: 'smooth' });
    });
})();
</script>
<?= $this->endSection() ?>
