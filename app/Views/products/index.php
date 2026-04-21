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
            <form method="get" action="<?= site_url('products') ?>">
                <input type="text" name="search" placeholder="Search item..." value="<?= esc($search) ?>">
                <button type="submit">Search</button>
            </form>
        </div>
        <?php if ((int) (session('user')['level_id'] ?? 0) >= 2): ?>
            <a href="<?= site_url('products/create') ?>" class="btn-add">+ Add Product</a>
        <?php endif; ?>
    </div>

    <div class="panel-card table-card">
        <table class="data-table">
            <tr>
                <th>Item No</th>
                <th>Code</th>
                <th>Item</th>
                <th>Unit</th>
                <th>Stock</th>
                <?php if ((int) (session('user')['level_id'] ?? 0) >= 2): ?>
                    <th>Action</th>
                <?php endif; ?>
            </tr>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= esc((string) $product['item_no']) ?></td>
                    <td><?= esc((string) $product['stockcard_no']) ?></td>
                    <td class="item-link"><a href="<?= site_url('stockcard?item_id=' . (int) $product['item_id']) ?>"><?= esc($product['item']) ?></a></td>
                    <td><?= esc((string) $product['unit_name']) ?></td>
                    <td><?= esc((string) $product['total_stock']) ?></td>
                    <?php if ((int) (session('user')['level_id'] ?? 0) >= 2): ?>
                        <td><a class="action-btn edit-btn" href="<?= site_url('products/edit/' . (int) $product['item_id']) ?>">Edit</a></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
