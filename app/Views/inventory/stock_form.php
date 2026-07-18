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

            <div class="stock-form-stock-chip">
                <span>Available Stock</span>
                <strong style="color:white;"><?= (int) $currentStock ?></strong>
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
                                        data-desc="<?= esc($item['product_description'] ?? '') ?>">
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
                            <option value="<?= (int) $transactionType['transaction_type_id'] ?>" <?= (string) old('transaction_type_id', '1') === (string) $transactionType['transaction_type_id'] ? 'selected' : '' ?>>
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

                        <div>
                            <label>Unit Cost</label>
                            <input type="number" name="unit_cost" step="0.01" min="0.01" placeholder="0.00" required value="<?= esc((string) old('unit_cost')) ?>">
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

    // Build dictionary for fast lookup
    const itemsDict = {};
    let initialItem = null;
    const initialId = parseInt(hiddenInput.value) || 0;

    for (const option of datalist.options) {
        const id = parseInt(option.dataset.id);
        const item = {
            id: id,
            name: option.value,
            unit: option.dataset.unit,
            desc: option.dataset.desc
        };
        itemsDict[option.value] = item;
        
        if (id === initialId) {
            initialItem = item;
        }
    }

    // Pre-fill if there's an initial value (e.g. from validation error or redirect)
    if (initialItem) {
        searchInput.value = initialItem.name;
        searchInput.classList.add('has-value');
        descInput.value = initialItem.desc;
        unitInput.value = initialItem.unit;
    }

    function selectItem(item) {
        hiddenInput.value = item.id;
        searchInput.value = item.name;
        searchInput.classList.add('has-value');
        descInput.value = item.desc;
        unitInput.value = item.unit;
    }

    function clearSelection() {
        hiddenInput.value = '';
        searchInput.classList.remove('has-value');
        descInput.value = '';
        unitInput.value = '';
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
})();

(function () {
    const typeSelect   = document.getElementById('transactionTypeSelect');
    const reasonGroup  = document.getElementById('adjustmentReasonGroup');
    const reasonSelect = document.getElementById('adjustmentReasonSelect');
    if (!typeSelect || !reasonGroup) return;

    function isAdjustOut() {
        const selectedOption = typeSelect.options[typeSelect.selectedIndex];
        return selectedOption
            ? selectedOption.text.toLowerCase().replace(/[\s-]/g, '_').includes('adjust_out')
            : false;
    }

    function syncReason() {
        const show = isAdjustOut();
        reasonGroup.style.display = show ? '' : 'none';
        if (!show && reasonSelect) {
            reasonSelect.value = '';
        }
    }

    typeSelect.addEventListener('change', syncReason);
    syncReason(); // run on page load

    // ── Expiry date confirmation ──────────────────────────────────────────
    const form        = document.querySelector('.stock-form');
    const expiryInput = form?.querySelector('[name="expiration_date"]');
    const modal       = document.getElementById('expiryModal');
    const cancelBtn   = document.getElementById('expiryModalCancel');
    const proceedBtn  = document.getElementById('expiryModalProceed');

    let confirmed = false; // flag: user already confirmed, skip modal

    form?.addEventListener('submit', function (e) {
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


