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
        <label>Stock No:</label>
        <input type="number" name="item_no" min="1" value="<?= esc((string) old('item_no', $product['item_no'])) ?>" required>

        <label>Product Name:</label>
        <input type="text" name="item" value="<?= esc(old('item', $product['item'])) ?>" required>

        <label>Description:</label>
        <textarea name="description"><?= esc(old('description', $product['description'])) ?></textarea>

        <label>Re-order Point:</label>
        <input type="number" name="re_order_point" min="0" value="<?= esc((string) old('re_order_point', $product['re_order_point'] ?? 0)) ?>" required>

        <label>Entity:</label>
        <select name="entity_id" required>
            <option value="">Select entity</option>
            <?php foreach ($entities as $entity): ?>
                <option value="<?= (int) $entity['entity_id'] ?>" <?= (string) old('entity_id', $product['entity_id'] ?? '') === (string) $entity['entity_id'] ? 'selected' : '' ?>>
                    <?= esc($entity['entity_name']) ?>
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

        <label>Item Type:</label>
        <select name="item_type_id" required>
            <option value="">Select item type</option>
            <?php foreach ($itemTypes as $itemType): ?>
                <option value="<?= (int) $itemType['item_type_id'] ?>" <?= (string) old('item_type_id', $product['item_type_id'] ?? '') === (string) $itemType['item_type_id'] ? 'selected' : '' ?>>
                    <?= esc($itemType['item_type']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Category:</label>
        <select name="item_category_id" required>
            <option value="">Select category</option>
            <?php foreach ($itemCategories as $itemCategory): ?>
                <option value="<?= (int) $itemCategory['item_category_id'] ?>" <?= (string) old('item_category_id', $product['item_category_id'] ?? '') === (string) $itemCategory['item_category_id'] ? 'selected' : '' ?>>
                    <?= esc($itemCategory['item_category']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit"><?= $product['item_id'] ? 'Update' : 'Save Product' ?></button>
    </form>
</div>
<?= $this->endSection() ?>
