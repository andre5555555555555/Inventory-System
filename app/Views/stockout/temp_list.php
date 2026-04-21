<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-shell stockout-page">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Temporary List</p>
            <h1>My Stock-Out List</h1>
            <p class="page-subtitle">Review and edit items before submitting for approval.</p>
        </div>
        <a href="<?= site_url('stockout') ?>" class="btn-secondary">+ Add More Items</a>
    </div>

    <?php $isDraft = ($draft['status'] ?? '') === 'draft'; ?>

    <?php if (empty($items)): ?>
        <div class="section-card">
            <div class="empty-state">
                <h3>No items in your list yet</h3>
                <p>Go to the <a href="<?= site_url('stockout') ?>">Stock Out page</a> to add items.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="section-card">
            <table class="data-table">
                <tr>
                    <th>Item</th>
                    <th>Unit</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <?php if ($isDraft): ?>
                        <th>Action</th>
                    <?php else: ?>
                        <th>Status</th>
                    <?php endif; ?>
                </tr>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= esc($item['item_name'] ?? '') ?></td>
                        <td><?= esc($item['unit'] ?? '') ?></td>
                        <td><?= esc($item['description'] ?? '') ?></td>
                        <td>
                            <?php if ($isDraft): ?>
                                <form method="post" action="<?= site_url('stockout/edit-temp/' . (int) $item['temp_stockout_item_id']) ?>" class="inline-edit-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="unit" value="<?= esc($item['unit'] ?? '') ?>">
                                    <input type="hidden" name="description" value="<?= esc($item['description'] ?? '') ?>">
                                    <input type="number" name="quantity" value="<?= (int) $item['quantity'] ?>" min="1" class="inline-input" required>
                                    <button type="submit" class="action-btn edit-btn" title="Save">✓</button>
                                </form>
                            <?php else: ?>
                                <?= (int) $item['quantity'] ?>
                            <?php endif; ?>
                        </td>
                        <?php if ($isDraft): ?>
                            <td>
                                <form method="post" action="<?= site_url('stockout/remove-temp/' . (int) $item['temp_stockout_item_id']) ?>" class="inline-form">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="action-btn delete-btn" onclick="return confirm('Remove this item?')">Remove</button>
                                </form>
                            </td>
                        <?php else: ?>
                            <td>
                                <span class="status-badge status-<?= strtolower(esc($item['status'] ?? 'pending')) ?>"><?= esc(ucfirst($item['status'] ?? 'pending')) ?></span>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </table>

            <?php if ($isDraft): ?>
                <div class="submit-bar">
                    <form method="post" action="<?= site_url('stockout/submit') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-primary btn-submit-approval" onclick="return confirm('Submit this list for approval? You won\'t be able to edit after submission.')">
                            Submit for Approval
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="submit-bar">
                    <span class="status-badge status-<?= strtolower(esc($draft['status'] ?? '')) ?>">
                        Request Status: <?= esc(ucfirst($draft['status'] ?? '')) ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
