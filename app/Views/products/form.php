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
        <input type="number" name="product_reorder_point" min="0" value="<?= esc((string) old('product_reorder_point', $product['product_reorder_point'] ?? 0)) ?>" required>

        <label>Entity:</label>
        <select name="entity_id" required>
            <option value="">Select entity</option>
            <?php foreach ($entities as $entity): ?>
                <option value="<?= (int) $entity['entity_id'] ?>" <?= (string) old('entity_id', $product['entity_id'] ?? '') === (string) $entity['entity_id'] ? 'selected' : '' ?>>
                    <?= esc($entity['entity']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Unit:</label>
        <select name="unit_id" required>
            <option value="">Select unit</option>
            <?php foreach ($units as $unit): ?>
                <option value="<?= (int) $unit['unit_id'] ?>" <?= (string) old('unit_id', $product['unit_id'] ?? '') === (string) $unit['unit_id'] ? 'selected' : '' ?>>
                    <?= esc($unit['unit']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Product Type:</label>
        <select name="type_id" required>
            <option value="">Select product type</option>
            <?php foreach ($productTypes as $pType): ?>
                <option value="<?= (int) $pType['type_id'] ?>" <?= (string) old('type_id', $product['type_id'] ?? '') === (string) $pType['type_id'] ? 'selected' : '' ?>>
                    <?= esc($pType['type']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit"><?= $product['product_id'] ? 'Update' : 'Save Product' ?></button>
    </form>
</div>
<?= $this->endSection() ?>
