<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-shell narrow-shell">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Inventory</p>
            <h1><?= esc($title) ?></h1>
            <p class="page-subtitle">Adjust quantities with a cleaner, flatter form layout.</p>
        </div>
    </div>

    <form method="post" class="stock-form flat-form-card" data-adjust-form data-target-url="<?= site_url('stock/adjust') ?>">
        <?= csrf_field() ?>
        <p><strong>Stock:</strong> <?= (int) $currentStock ?></p>

        <label>Search Item</label>
        <input type="text" id="adjustItemSearch" data-adjust-search placeholder="Search item by name, stock no, or description">
        <select name="item_id" id="adjustItemSelect" data-adjust-select required>
            <option value="">Select item</option>
            <?php foreach ($items as $item): ?>
                <?php $label = trim($item['item'] . ' #' . ($item['item_no'] ?? '') . (($item['description'] ?? '') !== '' ? ' - ' . $item['description'] : '')); ?>
                <option value="<?= (int) $item['item_id'] ?>" data-label="<?= esc(strtolower($label), 'attr') ?>" <?= (int) $itemId === (int) $item['item_id'] ? 'selected' : '' ?>>
                    <?= esc($label) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Date</label>
        <input type="date" name="date" value="<?= esc(old('date', date('Y-m-d'))) ?>">

        <label>Office</label>
        <select name="office_id">
            <?php foreach ($offices as $office): ?>
                <option value="<?= (int) $office['office_id'] ?>" <?= (string) old('office_id', $offices[0]['office_id'] ?? '') === (string) $office['office_id'] ? 'selected' : '' ?>>
                    <?= esc($office['office']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Reference</label>
        <select name="reference_id">
            <?php foreach ($references as $reference): ?>
                <option value="<?= (int) $reference['reference_id'] ?>" <?= (string) old('reference_id', $references[0]['reference_id'] ?? '') === (string) $reference['reference_id'] ? 'selected' : '' ?>>
                    <?= esc($reference['reference']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Type</label>
        <select name="adjust_type">
            <option value="IN" <?= old('adjust_type', 'IN') === 'IN' ? 'selected' : '' ?>>IN</option>
            <option value="OUT" <?= old('adjust_type') === 'OUT' ? 'selected' : '' ?>>OUT</option>
        </select>

        <label>Reason</label>
        <select name="reason_id">
            <?php foreach ($reasons as $reason): ?>
                <option value="<?= (int) $reason['reason_id'] ?>" <?= (string) old('reason_id', $reasons[0]['reason_id'] ?? '') === (string) $reason['reason_id'] ? 'selected' : '' ?>>
                    <?= esc($reason['reason_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Qty</label>
        <input type="number" name="quantity" value="<?= esc((string) old('quantity')) ?>">

        <label>Unit Cost</label>
        <input type="number" name="unit_cost" step="0.01" value="<?= esc((string) old('unit_cost')) ?>">

        <button type="submit">SAVE</button>
    </form>
</div>
<?= $this->endSection() ?>
