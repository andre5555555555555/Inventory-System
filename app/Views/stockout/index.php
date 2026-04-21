<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-shell stockout-page">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Stock Out</p>
            <h1>Request Stock Out</h1>
            <p class="page-subtitle">Select items and quantities to add to your temporary stock-out list for approval.</p>
        </div>
        <a href="<?= site_url('stockout/temp') ?>" class="btn-primary">View My Temp List</a>
    </div>

    <div class="section-card">
        <div class="stockout-form-card">
            <h2>Add Item to Stock-Out</h2>
            <form method="post" action="<?= site_url('stockout/add-temp') ?>" class="stockout-form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="item_id">Item</label>
                    <select name="item_id" id="item_id" required onchange="fillItemDetails(this)">
                        <option value="">Select Item</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= (int) $item['item_id'] ?>"
                                    data-unit="<?= esc($item['unit_name'] ?? '') ?>"
                                    data-desc="<?= esc($item['description'] ?? '') ?>"
                                    data-stock="<?= (int) $item['current_stock'] ?>">
                                <?= esc($item['item']) ?> (Stock: <?= (int) $item['current_stock'] ?>)
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
                    <input type="text" name="description" id="description" placeholder="Item description" readonly>
                </div>

                <button type="submit" class="btn-primary">Add to Temp List</button>
            </form>
        </div>
    </div>

    <div class="section-card">
        <h2>Available Items</h2>
        <input type="text" class="search-input" id="stockoutSearch" placeholder="Search items..." onkeyup="filterStockoutTable()">
        <table class="data-table" id="stockoutItemsTable">
            <tr>
                <th>Item</th>
                <th>Unit</th>
                <th>Description</th>
                <th>Current Stock</th>
            </tr>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= esc($item['item']) ?></td>
                    <td><?= esc($item['unit_name'] ?? '') ?></td>
                    <td><?= esc($item['description'] ?? '') ?></td>
                    <td><?= (int) $item['current_stock'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<script>
function fillItemDetails(select) {
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
