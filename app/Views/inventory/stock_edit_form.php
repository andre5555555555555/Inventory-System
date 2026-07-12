<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $isReceipt = (int) ($entry['receipt_qty'] ?? 0) > 0;
    $quantity = $isReceipt ? (int) $entry['receipt_qty'] : (int) $entry['issue_qty'];
?>
<div class="main-content-stockcard stock-form-page">
    <div class="stock-form-shell">
        <div class="stock-form-hero">
            <div>
                <p class="stock-form-eyebrow">Inventory Transaction</p>
                <h1><?= esc($title) ?></h1>
                <p class="stock-form-subtitle">Update a stockcard entry while keeping a backup of the original transaction.</p>
            </div>

            <div class="stock-form-stock-chip">
                <span>Current Stock</span>
                <strong style="color:white;"><?= (int) $currentStock ?></strong>
            </div>
        </div>

        <form method="post" class="stock-form">
            <?= csrf_field() ?>

            <div class="stock-form-grid stock-form-full">
                <section class="stock-form-panel">
                    <div class="stock-form-panel-head">
                        <h2>Item Details</h2>
                        <p>Change the item, date, office, or reference tied to this entry.</p>
                    </div>

                    <label>Item</label>
                    <select name="item_id" required>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= (int) $item['item_id'] ?>" <?= (int) old('item_id', $entry['item_id']) === (int) $item['item_id'] ? 'selected' : '' ?>>
                                <?= esc($item['item']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Date</label>
                    <input type="date" name="date" value="<?= esc(old('date', date('Y-m-d', strtotime($entry['date'])))) ?>" required>

                    <label>Expiration Date</label>
                    <input type="date" name="expiration_date" value="<?= esc((string) old('expiration_date', $entry['expiration_date'] ?? '')) ?>">

                    <label>Office</label>
                    <select name="office_id">
                        <option value="">No office</option>
                        <?php foreach ($offices as $office): ?>
                            <option value="<?= (int) $office['office_id'] ?>" <?= (string) old('office_id', $entry['office_id'] ?? '') === (string) $office['office_id'] ? 'selected' : '' ?>>
                                <?= esc($office['office']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </section>

                <section class="stock-form-panel">
                    <div class="stock-form-panel-head">
                        <h2>Transaction Details</h2>
                        <p>Quantity edits recalculate batch balances after saving.</p>
                    </div>

                    <label>Reference</label>
                    <select name="reference_id">
                        <option value="">No reference</option>
                        <?php foreach ($references as $reference): ?>
                            <option value="<?= (int) $reference['reference_id'] ?>" <?= (string) old('reference_id', $entry['reference_id'] ?? '') === (string) $reference['reference_id'] ? 'selected' : '' ?>>
                                <?= esc($reference['reference']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Type</label>
                    <select name="adjust_type">
                        <option value="IN" <?= old('adjust_type', $isReceipt ? 'IN' : 'OUT') === 'IN' ? 'selected' : '' ?>>RECEIPT</option>
                        <option value="OUT" <?= old('adjust_type', $isReceipt ? 'IN' : 'OUT') === 'OUT' ? 'selected' : '' ?>>ISSUE</option>
                    </select>

                    <label>Transaction Class</label>
                    <select name="transaction_type_id">
                        <option value="1" <?= (int) old('transaction_type_id', $entry['transaction_type_id']) === 1 ? 'selected' : '' ?>>Receipt</option>
                        <option value="2" <?= (int) old('transaction_type_id', $entry['transaction_type_id']) === 2 ? 'selected' : '' ?>>Issue</option>
                        <option value="3" <?= (int) old('transaction_type_id', $entry['transaction_type_id']) === 3 ? 'selected' : '' ?>>Adjustment In</option>
                        <option value="4" <?= (int) old('transaction_type_id', $entry['transaction_type_id']) === 4 ? 'selected' : '' ?>>Adjustment Out / Spoiled</option>
                    </select>

                    <div class="stock-form-inline">
                        <div>
                            <label>Qty</label>
                            <input type="number" name="quantity" min="1" value="<?= esc((string) old('quantity', $quantity)) ?>" required>
                        </div>

                        <div>
                            <label>Unit Cost</label>
                            <input type="number" name="unit_cost" step="0.01" min="0.01" placeholder="0.00" required value="<?= esc((string) old('unit_cost', ($entry['unit_cost'] ?? 0) > 0 ? $entry['unit_cost'] : '')) ?>">
                        </div>
                    </div>

                    <label>Adjustment Reason</label>
                    <select name="reason_id">
                        <option value="">None</option>
                        <?php foreach ($reasons as $reason): ?>
                            <option value="<?= (int) $reason['reason_id'] ?>" <?= (string) old('reason_id', $entry['adjustment_reason_id'] ?? '') === (string) $reason['reason_id'] ? 'selected' : '' ?>>
                                <?= esc($reason['reason_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </section>
            </div>

            <div class="stock-form-actions stock-form-full">
                <a class="action-btn" href="<?= site_url('stockcard?item_id=' . (int) $entry['item_id']) ?>">Cancel</a>
                <button type="submit">Save Edit</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
