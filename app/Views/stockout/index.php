<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-shell stockout-page">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Stock Out</p>
            <h1>Request Stock Out</h1>
            <p class="page-subtitle">Select products and quantities to add to your temporary stock-out list for approval.</p>
        </div>
        <a href="<?= site_url('stockout/temp') ?>" class="btn-primary">View My Temp List</a>
    </div>

    <div class="section-card">
        <div class="stockout-form-card">
            <h2>Add Product to Stock-Out</h2>
            <form method="post" action="<?= site_url('stockout/add-temp') ?>" class="stockout-form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="product_id">Product</label>
                    <select name="product_id" id="product_id" required onchange="fillProductDetails(this)">
                        <option value="">Select Product</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= (int) $item['product_id'] ?>"
                                    data-unit="<?= esc($item['unit_name'] ?? '') ?>"
                                    data-desc="<?= esc($item['product_description'] ?? $item['description'] ?? '') ?>"
                                    data-stock="<?= (int) $item['current_stock'] ?>">
                                <?= esc($item['product']) ?> (Stock: <?= (int) $item['current_stock'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="unit">Unit</label>
                        <input type="text" name="unit" id="unit" placeholder="e.g. box, pcs" readonly>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" name="quantity" id="quantity" min="1" placeholder="Enter quantity" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" name="description" id="description" placeholder="Product description" readonly>
                </div>

                <button type="submit" class="btn-primary">Add to Temp List</button>
            </form>
        </div>
    </div>

    <div class="section-card">
        <h2>Available Products</h2>
        <input type="text" class="search-input" id="stockoutSearch" placeholder="Search products..." onkeyup="filterStockoutTable()">
        <table class="data-table" id="stockoutItemsTable">
            <tr>
                <th>Product</th>
                <th>Unit</th>
                <th>Description</th>
                <th>Current Stock</th>
            </tr>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= esc($item['product']) ?></td>
                    <td><?= esc($item['unit_name'] ?? '') ?></td>
                    <td><?= esc($item['product_description'] ?? $item['description'] ?? '') ?></td>
                    <td><?= (int) $item['current_stock'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<script>
function fillProductDetails(select) {
    const option = select.options[select.selectedIndex];
    document.getElementById('unit').value = option.dataset.unit || '';
    document.getElementById('description').value = option.dataset.desc || '';
    const maxStock = parseInt(option.dataset.stock) || 0;
    document.getElementById('quantity').max = maxStock;
}

function filterStockoutTable() {
    const query = document.getElementById('stockoutSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#stockoutItemsTable tr');
    rows.forEach((row, i) => {
        if (i === 0) return;
        row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
}
</script>
<?= $this->endSection() ?>
