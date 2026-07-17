<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
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

                    <label>Product</label>
                    <select name="product_id" id="stockItemSelect" data-stock-item-select data-target-url="<?= site_url('stock/add') ?>" required>
                        <option value="">Select product</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= (int) $item['product_id'] ?>" <?= (int) $itemId === (int) $item['product_id'] ? 'selected' : '' ?>>
                                <?= esc($item['product']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

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
</style>

<script>
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


