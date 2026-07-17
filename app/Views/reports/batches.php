<?php $this->extend('layouts/main') ?>

<?php $this->section('content') ?>
<div class="page-shell">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Reports</p>
            <h1>Batch Inventory</h1>
            <p class="page-subtitle">All stock batches with their unique barcodes. Use the Print button to generate a barcode label.</p>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar-card">
        <form method="get" action="<?= site_url('batches') ?>" class="searchbar">
            <input type="text" name="search" placeholder="Search product or batch no..." value="<?= esc($search) ?>">
            <button type="submit">Search</button>
            <label class="show-empty-label">
                <input type="checkbox" name="show_empty" value="1" <?= $showEmpty ? 'checked' : '' ?> onchange="this.form.submit()">
                Show empty batches
            </label>
        </form>
    </div>

    <!-- Batch table -->
    <div class="panel-card table-card">
        <table class="data-table" id="batchInventoryTable">
            <thead>
                <tr>
                    <th>Batch No</th>
                    <th>Product</th>
                    <th>Unit</th>
                    <th>Qty</th>
                    <th>Expiry</th>
                    <th>Received</th>
                    <th>Barcode</th>
                    <th style="width:1%;white-space:nowrap;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($batches)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">No batches found<?= $search !== '' ? ' for "' . esc($search) . '"' : '' ?>.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($batches as $batch): ?>
                        <?php
                            $barcodeValue = $batch['barcode_value'] ?? '';
                            $svgFile      = FCPATH . 'barcodes/' . preg_replace('/[^A-Za-z0-9\-_]/', '_', $barcodeValue) . '.svg';
                            $barcodeUrl   = $barcodeValue !== '' && file_exists($svgFile)
                                ? base_url('barcodes/' . preg_replace('/[^A-Za-z0-9\-_]/', '_', $barcodeValue) . '.svg')
                                : site_url('barcode/batch/' . (int) $batch['batch_id']);
                        ?>
                        <tr>
                            <td><?= esc($batch['batch_no']) ?></td>
                            <td class="item-link">
                                <a href="<?= site_url('stockcard?item_id=' . (int) $batch['product_id']) ?>">
                                    <?= esc($batch['product']) ?>
                                </a>
                            </td>
                            <td><?= esc($batch['unit_name']) ?></td>
                            <td><?= (int) $batch['current_qty'] ?></td>
                            <td><?= $batch['expiration_date'] ? esc($batch['expiration_date']) : '—' ?></td>
                            <td><?= $batch['date_received'] ? esc($batch['date_received']) : '—' ?></td>
                            <td class="barcode-cell">
                                <?php if ($barcodeValue !== ''): ?>
                                    <img
                                        src="<?= esc($barcodeUrl) ?>"
                                        alt="Barcode <?= esc($barcodeValue) ?>"
                                        class="barcode-img"
                                        loading="lazy"
                                    >
                                    <div class="barcode-label"><?= esc($barcodeValue) ?></div>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space:nowrap;">
                                <?php if ($barcodeValue !== ''): ?>
                                    <a
                                        href="<?= esc($barcodeUrl) ?>"
                                        download="<?= esc(preg_replace('/[^A-Za-z0-9\-_]/', '_', $barcodeValue)) ?>.svg"
                                        class="action-btn print-barcode-btn"
                                        title="Download Barcode SVG"
                                    >⬇ Download</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- (no print frame needed) -->


<style>
.barcode-cell {
    text-align: center;
    padding: 6px;
}
.barcode-img {
    display: block;
    margin: 0 auto 2px;
    height: 52px;
    width: auto;
    max-width: 180px;
}
.barcode-label {
    font-size: 11px;
    color: #555;
    letter-spacing: 0.04em;
    text-align: center;
}
.show-empty-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    cursor: pointer;
    white-space: nowrap;
}
.show-empty-label input { cursor: pointer; }

.print-barcode-btn {
    background: #0f766e;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 5px 10px;
    font-size: 13px;
    cursor: pointer;
    box-shadow: none;
}
.print-barcode-btn:hover { background: #115e59; }
</style>


<?= $this->endSection() ?>
