<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-shell">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Products</p>
            <h1>Finished Product Barcodes</h1>
            <p class="page-subtitle">Generated barcodes for finished products are saved here. Generate new ones or download existing SVGs.</p>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar-card">
        <form method="get" action="<?= site_url('products/barcodes') ?>" class="searchbar">
            <input type="text" name="search" placeholder="Search product name, stock no..." value="<?= esc($search) ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <!-- Table -->
    <div class="panel-card table-card">
        <table class="data-table" id="finishedBarcodesTable">
            <thead>
                <tr>
                    <th>Product No</th>
                    <th>Stock No</th>
                    <th>Product</th>
                    <th>Unit</th>
                    <th>Barcode</th>
                    <th style="width:1%;white-space:nowrap;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            No finished products found<?= $search !== '' ? ' for "' . esc($search) . '"' : '' ?>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= esc((string) $product['product_no']) ?></td>
                            <td><?= esc((string) $product['stock_no']) ?></td>
                            <td><?= esc($product['product']) ?></td>
                            <td><?= esc($product['unit_name']) ?></td>
                            <td class="fp-barcode-cell">
                                <?php if ($product['barcode_url'] !== null): ?>
                                    <img
                                        src="<?= esc($product['barcode_url']) ?>"
                                        alt="Barcode <?= esc($product['barcode_value']) ?>"
                                        class="fp-barcode-img"
                                        loading="lazy"
                                    >
                                    <div class="fp-barcode-label"><?= esc($product['barcode_value']) ?></div>
                                <?php else: ?>
                                    <span class="text-muted">Not yet generated</span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space:nowrap;">
                                <?php if ($product['barcode_url'] !== null): ?>
                                    <a
                                        href="<?= esc($product['barcode_url']) ?>"
                                        download="<?= esc(preg_replace('/[^A-Za-z0-9\-_]/', '_', $product['barcode_value'])) ?>.svg"
                                        class="action-btn fp-download-btn"
                                        title="Download Barcode SVG"
                                    >⬇ Download</a>
                                <?php else: ?>
                                    <form method="post" action="<?= site_url('products/barcodes/generate') ?>" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= (int) $product['product_id'] ?>">
                                        <button type="submit" class="action-btn fp-generate-btn">Generate</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.fp-barcode-cell {
    text-align: center;
    padding: 6px;
}
.fp-barcode-img {
    display: block;
    margin: 0 auto 2px;
    height: 52px;
    width: auto;
    max-width: 200px;
}
.fp-barcode-label {
    font-size: 11px;
    color: #555;
    letter-spacing: 0.04em;
    text-align: center;
}
.fp-download-btn {
    background: #0f766e;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 5px 10px;
    font-size: 13px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}
.fp-download-btn:hover { background: #115e59; }
.fp-generate-btn {
    background: #1d4ed8;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 5px 10px;
    font-size: 13px;
    cursor: pointer;
}
.fp-generate-btn:hover { background: #1e40af; }
</style>

<?= $this->endSection() ?>

