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

                    <label>Adjustment Reason</label>
                    <select name="adjustment_reason_id" id="adjustmentReasonSelect">
                        <option value="">No reason</option>
                        <?php foreach ($adjustmentReasons as $reason): ?>
                            <option value="<?= (int) $reason['adjustment_reason_id'] ?>" <?= (string) old('adjustment_reason_id') === (string) $reason['adjustment_reason_id'] ? 'selected' : '' ?>>
                                <?= esc($reason['adjustment_reason']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

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
                <button type="submit">Save Transaction</button>
            </div>

        </form>
    </div>
</div>
<script>
(function () {
    const typeSelect = document.getElementById('transactionTypeSelect');
    const reasonSelect = document.getElementById('adjustmentReasonSelect');
    if (!typeSelect || !reasonSelect) return;

    function syncReason() {
        const isAdjustment = ['3', '4'].includes(typeSelect.value);
        reasonSelect.disabled = !isAdjustment;
        if (!isAdjustment) {
            reasonSelect.value = '';
        }
    }

    typeSelect.addEventListener('change', syncReason);
    syncReason();
})();
</script>
<?= $this->endSection() ?>


