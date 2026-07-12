<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="main-content-stockcard stock-form-page">
    <div class="stock-form-shell">
        <div class="stock-form-hero">
            <div>
                <p class="stock-form-eyebrow">Inventory Transaction</p>
                <h1><?= esc($title) ?></h1>
                <p class="stock-form-subtitle">
                    Record stock movement with a cleaner desktop workspace and a simple mobile form.
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

                    <label>Type</label>
                    <select name="adjust_type">
                        <option value="IN" <?= old('adjust_type', 'IN') === 'IN' ? 'selected' : '' ?>>RECEIPT</option>
                        <option value="OUT" <?= old('adjust_type') === 'OUT' ? 'selected' : '' ?>>ISSUE</option>
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
<?= $this->endSection() ?>

