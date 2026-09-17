<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-shell">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Products</p>
            <h1>Product List</h1>
            <p class="page-subtitle">Review stock-ready items, browse codes, and jump directly to stockcards.</p>
        </div>
    </div>

    <div class="toolbar-card">
        <div class="searchbar">
            <form method="get" action="<?= site_url('products') ?>" class="report-toolbar product-filter-toolbar">
                <div class="report-filter-group report-filter-search">
                    <label for="product-search">Search Product</label>
                    <input id="product-search" type="text" name="search" placeholder="Search product..." value="<?= esc($search) ?>">
                </div>
                <div class="report-filter-group">
                    <label for="product-type">Product Type</label>
                    <select id="product-type" name="type_id">
                        <option value="0">All Product Types</option>
                        <?php foreach (($productTypes ?? []) as $pType): ?>
                            <option value="<?= (int) $pType['type_id'] ?>" <?= (int) ($typeId ?? 0) === (int) $pType['type_id'] ? 'selected' : '' ?>>
                                <?= esc($pType['type']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="report-filter-actions">
                    <button type="submit">Apply Filter</button>
                </div>
            </form>
        </div>
        <?php if ((int) (session('user')['level_id'] ?? 0) >= 2): ?>
            <a href="<?= site_url('products/create') ?>" class="btn-add">+ Add Product</a>
        <?php endif; ?>
    </div>

    <div class="panel-card table-card">
        <table class="data-table">
            <tr>
                <th>Product No</th>
                <th>Stock No</th>
                <th>Product</th>
                <th>Product Type</th>
                <th>Measurement / Unit</th>
                <th>Stock</th>
                <?php if ((int) (session('user')['level_id'] ?? 0) >= 2): ?>
                    <th style="width:1%;white-space:nowrap;">Action</th>
                <?php endif; ?>
            </tr>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= esc((string) $product['product_no']) ?></td>
                    <td><?= esc((string) $product['stock_no']) ?></td>
                    <td><?= esc($product['product']) ?></td>
                    <td><?= esc((string) ($product['type_name'] ?? '')) ?></td>
                    <td><?php
                        $m = trim((string) ($product['measurement'] ?? ''));
                        $u = esc((string) $product['unit_name']);
                        echo $m !== '' ? esc($m) . ' / ' . $u : $u;
                    ?></td>
                    <td><?= esc((string) $product['total_stock']) ?></td>
                    <?php if ((int) (session('user')['level_id'] ?? 0) >= 2): ?>
                        <td style="width:1%;white-space:nowrap;">
                            <a class="action-btn edit-btn" href="<?= site_url('products/edit/' . (int) $product['product_id']) ?>">Edit</a>
                            <?php if (($product['type_name'] ?? '') !== 'Finished Product'): ?>
                                <button
                                    class="action-btn delete-btn"
                                    data-delete-url="<?= site_url('products/delete/' . (int) $product['product_id']) ?>"
                                    data-product-name="<?= esc($product['product']) ?>"
                                >Delete</button>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
