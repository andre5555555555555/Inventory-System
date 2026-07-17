<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-shell narrow-shell">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Products</p>
            <h1><?= esc($title) ?></h1>
            <p class="page-subtitle">Keep product setup tidy with a flatter, easier-to-scan form.</p>
        </div>
    </div>

    <form method="post" class="stock-form flat-form-card">
        <?= csrf_field() ?>
        <label>Product No:</label>
        <input type="number" name="product_no" min="1" value="<?= esc((string) old('product_no', $product['product_no'])) ?>" required>

        <label>Product Name:</label>
        <input type="text" name="product" value="<?= esc(old('product', $product['product'])) ?>" required>

        <label>Description:</label>
        <textarea name="product_description"><?= esc(old('product_description', $product['product_description'])) ?></textarea>

        <label>Re-order Point:</label>
        <input type="number" name="product_reorder_point" min="0" value="<?= esc((string) old('product_reorder_point', $product['product_reorder_point'] ?? 10)) ?>" required>

        <label>Entity:</label>
        <div class="hover-dropdown">
            <input type="text" name="entity_name" class="hover-input" autocomplete="off" placeholder="Select or type entity" value="<?= esc(old('entity_name', $product['entity_name'] ?? '')) ?>" required>
            <div class="hover-dropdown-content">
                <?php foreach ($entities as $entity): ?>
                    <div class="hover-option"><?= esc($entity['entity']) ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <label>Unit:</label>
        <div class="hover-dropdown">
            <input type="text" name="unit_name" class="hover-input" autocomplete="off" placeholder="Select or type unit" value="<?= esc(old('unit_name', $product['unit_name'] ?? '')) ?>" required>
            <div class="hover-dropdown-content">
                <?php foreach ($units as $unit): ?>
                    <div class="hover-option"><?= esc($unit['unit']) ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <label>Product Type:</label>
        <div class="hover-dropdown">
            <input type="text" name="type_name" class="hover-input" autocomplete="off" placeholder="Select or type product type" value="<?= esc(old('type_name', $product['type_name'] ?? '')) ?>" required>
            <div class="hover-dropdown-content">
                <?php foreach ($productTypes as $pType): ?>
                    <div class="hover-option"><?= esc($pType['type']) ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit"><?= $product['product_id'] ? 'Update' : 'Save Product' ?></button>
    </form>
</div>
<?= $this->endSection() ?>
