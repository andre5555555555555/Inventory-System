<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
// Pre-compute description & unit for the currently selected product
$selectedDesc = '';
$selectedUnit = '';
foreach ($items as $_item) {
    if ((int) $itemId === (int) $_item['product_id']) {
        $selectedDesc = $_item['product_description'] ?? '';
        $selectedUnit = $_item['unit'] ?? '';
        break;
    }
}
?>
<div class="main-content-stockcard stock-form-page">
    <div class="stock-form-shell">
        <div class="stock-form-hero">
            <div>
                <p class="stock-form-eyebrow">Inventory Transaction</p>
                <h1><?= esc($title) ?></h1>
                <p class="stock-form-subtitle">
                    Record stock movement using your transaction types and adjustment reasons.
                </p>
            </div>

            <div class="stock-form-stock-chip" id="stockChip">
                <span>Available Stock</span>
                <strong id="stockChipValue" style="color:white;"><?= (int) $currentStock ?></strong>
            </div>
        </div>

        <form method="post" class="stock-form">
            <?= csrf_field() ?>

            <div class="stock-form-grid stock-form-full">

                <!-- PRODUCT DETAILS -->
                <section class="stock-form-panel">
                    <div class="stock-form-panel-head">
                        <h2>Product Details</h2>
                        <p>Choose the product and where this transaction belongs.</p>
                    </div>

                    <label for="productSearch">Product</label>
                    <div style="position: relative;">
                        <input
                            type="text"
                            id="productSearch"
                            class="styled-datalist-input"
                            list="stockProductsList"
                            placeholder="Select product or scan barcode..."
                            autocomplete="off"
                            required
                        >
                        <datalist id="stockProductsList">
                            <?php foreach ($items as $item): ?>
                                <option value="<?= esc($item['product']) ?>"
                                        data-id="<?= (int) $item['product_id'] ?>"
                                        data-unit="<?= esc($item['unit'] ?? '') ?>"
                                        data-desc="<?= esc($item['product_description'] ?? '') ?>"
                                        data-stock="<?= (int) ($stockMap[(int)$item['product_id']] ?? 0) ?>">
                                </option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <input type="hidden" name="product_id" id="product_id_hidden" value="<?= (int) $itemId > 0 ? (int) $itemId : '' ?>" required>
                    <div id="barcodeError" style="display:none; color: #ef4444; font-size: 13px; margin-top: 4px;"></div>

                    <div class="stock-form-inline">
                        <div>
                            <label>Description</label>
                            <input type="text" id="descriptionInput" value="<?= esc($selectedDesc) ?>" readonly style="background: var(--sidebar-bg, #f9fafb); color: var(--text-muted, #6b7280); cursor: not-allowed; border-color: var(--border-color, #e5e7eb);" placeholder="—">
                        </div>
                        <div>
                            <label>Unit</label>
                            <input type="text" id="unitInput" value="<?= esc($selectedUnit) ?>" readonly style="background: var(--sidebar-bg, #f9fafb); color: var(--text-muted, #6b7280); cursor: not-allowed; border-color: var(--border-color, #e5e7eb);" placeholder="—">
                        </div>
                    </div>

                    <label>Date</label>
                    <input type="date" name="date" value="<?= esc(old('date', date('Y-m-d'))) ?>" required>

                    <label>Expiration Date</label>
                    <input type="date" name="expiration_date" value="<?= esc((string) old('expiration_date')) ?>">

                    <label>Office</label>
                    <input
                        type="text"
                        name="office"
                        list="office-options"
                        value="<?= esc((string) old('office')) ?>"
                        placeholder="Select or type an office"
                    >

                    <datalist id="office-options">
                        <?php foreach ($offices as $office): ?>
                            <option value="<?= esc($office['office_name']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </section>

                <!-- TRANSACTION DETAILS -->
                <section class="stock-form-panel">
                    <div class="stock-form-panel-head">
                        <h2>Transaction Details</h2>
                        <p>Set the transaction type, quantity, cost, and reference.</p>
                    </div>

                    <label>Reference</label>
                    <input
                        type="text"
                        name="reference"
                        list="reference-options"
                        value="<?= esc((string) old('reference')) ?>"
                        placeholder="Select or type a reference"
                    >

                    <small class="stock-form-help">
                        You can reuse an existing reference or type a new one.
                    </small>

                    <datalist id="reference-options">
                        <?php foreach ($references as $reference): ?>
                            <option value="<?= esc($reference['reference']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>

                    <label>Transaction Type</label>
                    <select name="transaction_type_id" id="transactionTypeSelect" required>
                        <?php foreach ($transactionTypes as $transactionType): ?>
                            <option value="<?= (int) $transactionType['transaction_type_id'] ?>"
                                    data-type="<?= esc(strtolower($transactionType['transaction_type'])) ?>"
                                    <?= (string) old('transaction_type_id', '1') === (string) $transactionType['transaction_type_id'] ? 'selected' : '' ?>>
                                <?= esc(ucfirst($transactionType['transaction_type'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div id="adjustmentReasonGroup" style="display:none">
                        <label>Adjustment Reason</label>
                        <select name="adjustment_reason_id" id="adjustmentReasonSelect">
                            <option value="">No reason</option>
                            <?php foreach ($adjustmentReasons as $reason): ?>
                                <option value="<?= (int) $reason['adjustment_reason_id'] ?>" <?= (string) old('adjustment_reason_id') === (string) $reason['adjustment_reason_id'] ? 'selected' : '' ?>>
                                    <?= esc($reason['adjustment_reason']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="stock-form-inline">
                        <div>
                            <label>Qty</label>
                            <input type="number" name="quantity" min="1" value="<?= esc((string) old('quantity')) ?>" required>
                        </div>

                        <div id="unitCostGroup">
                            <label>Unit Cost</label>
                            <input type="number" id="unitCostInput" name="unit_cost" step="0.01" min="0.01" placeholder="0.00" required value="<?= esc((string) old('unit_cost')) ?>">
                        </div>
                    </div>
                </section>

            </div>

            <div class="stock-form-actions stock-form-full">
                <button type="submit" id="saveTransactionBtn">Save Transaction</button>
            </div>

        </form>
    </div>
</div>
<!-- Expiry confirmation modal -->
<div id="expiryModal" style="display:none;" aria-modal="true" role="dialog" aria-labelledby="expiryModalTitle">
    <div class="expiry-modal-backdrop"></div>
    <div class="expiry-modal-box">
        <div class="expiry-modal-icon">⚠️</div>
        <h3 id="expiryModalTitle">No Expiration Date</h3>
        <p>You haven't set an expiration date for this transaction. Products without an expiry date may be harder to track.<br><br>Are you sure you want to save without one?</p>
        <div class="expiry-modal-actions">
            <button type="button" id="expiryModalCancel" class="expiry-btn-cancel">Cancel</button>
            <button type="button" id="expiryModalProceed" class="expiry-btn-proceed">Yes, Proceed</button>
        </div>
    </div>
</div>

<style>
/* ── Expiry confirmation modal ───────────────────────────────────────────── */
#expiryModal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}
.expiry-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    backdrop-filter: blur(3px);
    animation: fadeIn 0.18s ease;
}
.expiry-modal-box {
    position: relative;
    z-index: 1;
    background: var(--card-bg, #fff);
    color: var(--text-primary, #111);
    border-radius: 16px;
    padding: 36px 32px 28px;
    max-width: 420px;
    width: 90%;
    text-align: center;
    box-shadow: 0 24px 60px rgba(0,0,0,0.22);
    animation: slideUp 0.22s cubic-bezier(.34,1.56,.64,1);
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(24px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes fadeIn {
    from { opacity: 0; } to { opacity: 1; }
}
.expiry-modal-icon { font-size: 2.5rem; margin-bottom: 10px; }
.expiry-modal-box h3 {
    margin: 0 0 10px;
    font-size: 1.2rem;
    font-weight: 700;
}
.expiry-modal-box p {
    font-size: 0.92rem;
    line-height: 1.6;
    color: var(--text-secondary, #555);
    margin: 0 0 24px;
}
.expiry-modal-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
}
.expiry-btn-cancel {
    padding: 10px 24px;
    border-radius: 8px;
    border: 1.5px solid var(--border-color, #d1d5db);
    background: transparent;
    color: var(--text-primary, #374151);
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s;
}
.expiry-btn-cancel:hover { background: var(--hover-bg, #f3f4f6); }
.expiry-btn-proceed {
    padding: 10px 24px;
    border-radius: 8px;
    border: none;
    background: #b45309;
    color: #fff;
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s;
}
.expiry-btn-proceed:hover { background: #92400e; }

/* ── Styled Native Datalist ────────────────────────────────────────────── */
.styled-datalist-input {
    width: 100%;
    padding: 10px 36px 10px 14px;
    border: 1.5px solid var(--border-color, #d1d5db);
    border-radius: 10px;
    font-size: 14px;
    font-family: inherit;
    background-color: var(--input-bg, #fff);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.25em 1.25em;
    color: var(--text-primary, #111827);
    box-sizing: border-box;
    transition: border-color 0.15s, box-shadow 0.15s;
    outline: none;
    cursor: pointer;
    margin-bottom: 12px;
}
.styled-datalist-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
    cursor: text;
}
.styled-datalist-input.has-value {
    border-color: #0f766e;
    background-color: rgba(15,118,110,0.04);
}
/* Hide the native datalist arrow in webkit since we provide our own SVG */
.styled-datalist-input::-webkit-calendar-picker-indicator {
    opacity: 0;
}
</style>

<script>
// ── Native Datalist Autofill & Barcode Logic ─────────────────────────────────
(function () {
    const searchInput  = document.getElementById('productSearch');
    const hiddenInput  = document.getElementById('product_id_hidden');
    const descInput    = document.getElementById('descriptionInput');
    const unitInput    = document.getElementById('unitInput');
    const datalist     = document.getElementById('stockProductsList');
    const form         = document.querySelector('.stock-form');

    // OUT-type detection — check by transaction_type name, not hardcoded ID
    // Names that reduce stock: issue, adjust_out, borrow
    const OUT_TYPE_NAMES = ['issue', 'adjust_out', 'borrow'];

    function isOutTypeId(typeSelectEl) {
        const opt = typeSelectEl ? typeSelectEl.options[typeSelectEl.selectedIndex] : null;
        return opt ? OUT_TYPE_NAMES.includes((opt.dataset.type || '').toLowerCase()) : false;
    }

    // Stock chip element
    const stockChipValue = document.getElementById('stockChipValue');

    function updateChipStock(stock) {
        if (stockChipValue) stockChipValue.textContent = stock;
    }

    // Build dictionary for fast lookup (includes data-stock)
    const itemsDict = {};
    let initialItem = null;
    const initialId = parseInt(hiddenInput.value) || 0;

    for (const option of datalist.options) {
        const id   = parseInt(option.dataset.id);
        const item = {
            id:    id,
            name:  option.value,
            unit:  option.dataset.unit,
            desc:  option.dataset.desc,
            stock: parseInt(option.dataset.stock ?? '0') || 0,
        };
        itemsDict[option.value] = item;
        if (id === initialId) initialItem = item;
    }

    function selectItem(item) {
        hiddenInput.value = item.id;
        searchInput.value = item.name;
        searchInput.classList.add('has-value');
        descInput.value = item.desc;
        unitInput.value = item.unit;
        const stock = parseInt(item.stock ?? '0') || 0;
        updateChipStock(stock);
        document.dispatchEvent(new CustomEvent('stockform:productchanged', { detail: { stock } }));
    }

    function clearSelection() {
        hiddenInput.value = '';
        searchInput.classList.remove('has-value');
        descInput.value = '';
        unitInput.value = '';
        document.dispatchEvent(new CustomEvent('stockform:productchanged', { detail: { stock: null } }));
    }

    // Pre-fill if there's an initial value (e.g. from validation error or redirect)
    // Use selectItem so the chip and validator are seeded correctly.
    if (initialItem) {
        selectItem(initialItem);
    }

    // Triggered on type or select
    searchInput.addEventListener('input', () => {
        const val = searchInput.value;
        if (itemsDict[val]) {
            selectItem(itemsDict[val]);
        } else {
            clearSelection();
        }
    });

    // Handle Enter key for Barcode scanning
    searchInput.addEventListener('keydown', async e => {
        if (e.key === 'Enter') {
            e.preventDefault(); // prevent form submit
            if (hiddenInput.value) return; // already a valid product

            const value = searchInput.value.trim();
            if (value) {
                // We use the same lookup endpoint as stockout
                await doBarcodeLookup(value);
            }
        }
    });

    async function doBarcodeLookup(value) {
        const errorDiv = document.getElementById('barcodeError');
        const lookupUrl = searchInput.getAttribute('data-lookup-url') || '<?= site_url('barcode/lookup') ?>';
        errorDiv.style.display = 'none';
        
        try {
            const res = await fetch(lookupUrl + '?value=' + encodeURIComponent(value), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            
            if (!res.ok) {
                errorDiv.textContent = data.error ?? 'Barcode not found.';
                errorDiv.style.display = '';
            } else if (data.product_id) {
                const item = Object.values(itemsDict).find(i => i.id === data.product_id);
                if (item) {
                    selectItem(item);
                } else {
                    errorDiv.textContent = 'Product associated with barcode not found.';
                    errorDiv.style.display = '';
                }
            }
        } catch (e) {
            errorDiv.textContent = 'Network error. Please try again.';
            errorDiv.style.display = '';
        }
    }

    // Prevent manual form submit if not matched
    form?.addEventListener('submit', e => {
        if (!hiddenInput.value) {
            e.preventDefault();
            searchInput.focus();
            searchInput.style.borderColor = '#ef4444';
            setTimeout(() => searchInput.style.borderColor = '', 1200);
        }
    });

    // Expose for second IIFE
    window._stockForm = {
        isOutTypeId,
        getInitialStock() { return <?= (int) $currentStock ?>; },
    };
})();

(function () {
    const typeSelect   = document.getElementById('transactionTypeSelect');
    const reasonGroup  = document.getElementById('adjustmentReasonGroup');
    const reasonSelect = document.getElementById('adjustmentReasonSelect');
    const submitBtn    = document.getElementById('saveTransactionBtn');
    if (!typeSelect || !reasonGroup) return;

    // -- Zero-stock warning banner ----------------------------------------
    let zeroStockBanner = document.getElementById('zeroStockBanner');
    if (!zeroStockBanner) {
        zeroStockBanner = document.createElement('div');
        zeroStockBanner.id = 'zeroStockBanner';
        Object.assign(zeroStockBanner.style, {
            display:         'none',   // hidden by default
            background:      'linear-gradient(135deg,#fef2f2,#fee2e2)',
            border:          '1.5px solid #fca5a5',
            borderRadius:    '10px',
            padding:         '12px 18px',
            marginBottom:    '12px',
            color:           '#991b1b',
            fontSize:        '0.9rem',
            fontWeight:      '600',
            alignItems:      'center',
            gap:             '10px',
        });
        zeroStockBanner.innerHTML = '<span style="font-size:1.3rem">⛔</span><span id="zeroStockBannerMsg">Cannot perform this transaction — stock is 0.</span>';
        const actionsDiv = document.querySelector('.stock-form-actions');
        if (actionsDiv) actionsDiv.insertAdjacentElement('beforebegin', zeroStockBanner);
    }
    const bannerMsg = document.getElementById('zeroStockBannerMsg');

    // Use the name-based helper shared from the first IIFE — works regardless of DB IDs
    const _isOutTypeId = (window._stockForm && window._stockForm.isOutTypeId) || function () { return false; };

    let currentProductStock = (window._stockForm && window._stockForm.getInitialStock) ? window._stockForm.getInitialStock() : 0;

    function isOutType() {
        return _isOutTypeId(typeSelect);
    }

    function getTypeName() {
        const opt = typeSelect.options[typeSelect.selectedIndex];
        return opt ? opt.text.trim() : 'this transaction type';
    }

    function validate() {
        if (isOutType() && currentProductStock === 0) {
            const typeName = getTypeName();
            if (bannerMsg) bannerMsg.textContent = `Cannot perform "${typeName}" — Available Stock is 0.`;
            zeroStockBanner.style.display = 'flex';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
                submitBtn.style.cursor  = 'not-allowed';
            }
        } else {
            zeroStockBanner.style.display = 'none';
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '';
                submitBtn.style.cursor  = '';
            }
        }
    }

    function isAdjustOut() {
        const selectedOption = typeSelect.options[typeSelect.selectedIndex];
        return selectedOption
            ? (selectedOption.dataset.type || '').toLowerCase() === 'adjust_out'
            : false;
    }

    function isReceipt() {
        const selectedOption = typeSelect.options[typeSelect.selectedIndex];
        return selectedOption
            ? (selectedOption.dataset.type || '').toLowerCase() === 'receipt'
            : false;
    }

    // Unit cost: always visible, but only required for Receipt
    const unitCostGroup = document.getElementById('unitCostGroup');
    const unitCostInput = document.getElementById('unitCostInput');
    function syncUnitCost() {
        if (!unitCostInput) return;
        if (isReceipt()) {
            unitCostInput.required = true;
            unitCostInput.min = '0.01';
        } else {
            unitCostInput.required = false;
            unitCostInput.removeAttribute('min');
        }
    }

    function syncReason() {
        const show = isAdjustOut();
        reasonGroup.style.display = show ? '' : 'none';
        if (!show && reasonSelect) {
            reasonSelect.value = '';
        }
    }

    typeSelect.addEventListener('change', () => {
        syncReason();
        syncUnitCost();
        validate();
    });
    syncReason();
    syncUnitCost();
    validate(); // run on page load

    // React to product selection changes (dispatched by first IIFE)
    document.addEventListener('stockform:productchanged', (e) => {
        currentProductStock = (e.detail && e.detail.stock !== null) ? (e.detail.stock ?? 0) : 0;
        validate();
    });

    // ── Expiry date confirmation ──────────────────────────────────────────
    const form        = document.querySelector('.stock-form');
    const expiryInput = form?.querySelector('[name="expiration_date"]');
    const modal       = document.getElementById('expiryModal');
    const cancelBtn   = document.getElementById('expiryModalCancel');
    const proceedBtn  = document.getElementById('expiryModalProceed');

    let confirmed = false; // flag: user already confirmed, skip modal

    form?.addEventListener('submit', function (e) {
        // Block if zero-stock violation
        if (isOutType() && currentProductStock === 0) {
            e.preventDefault();
            return;
        }
        if (confirmed) return; // already confirmed — let it submit
        if (expiryInput && expiryInput.value.trim() === '') {
            e.preventDefault();
            modal.style.display = 'flex';
        }
    });

    cancelBtn?.addEventListener('click', () => {
        modal.style.display = 'none';
        confirmed = false;
    });

    proceedBtn?.addEventListener('click', () => {
        modal.style.display = 'none';
        confirmed = true;
        form?.requestSubmit(); // submit without re-triggering our listener check
    });

    // Close on backdrop click
    modal?.querySelector('.expiry-modal-backdrop')?.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    // Close on Escape key
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && modal?.style.display !== 'none') {
            modal.style.display = 'none';
        }
    });
})();
</script>
<?= $this->endSection() ?>


