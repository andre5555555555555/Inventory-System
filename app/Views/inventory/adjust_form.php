<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-shell narrow-shell">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Inventory</p>
            <h1><?= esc($title) ?></h1>
            <p class="page-subtitle">Directly correct the stock count. Adjustments do not appear in the stockcard or reports.</p>
        </div>
    </div>

    <form method="post" class="stock-form flat-form-card" data-adjust-form data-target-url="<?= site_url('stock/adjust') ?>">
        <?= csrf_field() ?>

        <p><strong>Current Stock:</strong> <?= (int) $currentStock ?></p>

        <label>Search Product</label>
        <input type="text" id="adjustItemSearch" data-adjust-search placeholder="Search product by name, stock no, or description">
        <select name="product_id" id="adjustItemSelect" data-adjust-select required>
            <option value="">Select product</option>
            <?php foreach ($items as $item): ?>
                <?php $label = trim($item['product'] . ' #' . ($item['product_no'] ?? '') . (($item['product_description'] ?? '') !== '' ? ' - ' . $item['product_description'] : '')); ?>
                <option value="<?= (int) $item['product_id'] ?>" data-label="<?= esc(strtolower($label), 'attr') ?>" <?= (int) $itemId === (int) $item['product_id'] ? 'selected' : '' ?>>
                    <?= esc($label) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Adjustment Type</label>
        <select name="adjust_type">
            <option value="IN" <?= old('adjust_type', 'IN') === 'IN' ? 'selected' : '' ?>>ADD (Increase Stock)</option>
            <option value="OUT" <?= old('adjust_type') === 'OUT' ? 'selected' : '' ?>>SUBTRACT (Decrease Stock)</option>
        </select>

        <label>Quantity</label>
        <input type="number" name="quantity" min="1" value="<?= esc((string) old('quantity')) ?>" required>

        <button type="submit">SAVE ADJUSTMENT</button>
    </form>
</div>
<?= $this->endSection() ?>
