<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-shell">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Reports</p>
            <h1>Inventory Report</h1>
            <p class="page-subtitle">Review beginning, purchase, usage, spoiled, and ending balances in a cleaner ledger view.</p>
        </div>
    </div>

    <form method="get" action="<?= site_url('batchlist') ?>" class="toolbar-card report-toolbar">
        <div class="report-filter-group report-filter-search">
            <label for="report-search">Search Product</label>
            <input id="report-search" type="text" name="search" value="<?= esc($search) ?>" placeholder="Search product name">
        </div>

        <div class="report-filter-group">
            <label for="report-type">Product Type</label>
            <select id="report-type" name="type_id">
                <option value="0">All Product Types</option>
                <?php foreach (($productTypes ?? []) as $pType): ?>
                    <option value="<?= (int) $pType['type_id'] ?>" <?= (int) ($typeId ?? 0) === (int) $pType['type_id'] ? 'selected' : '' ?>>
                        <?= esc($pType['type']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="report-filter-group">
            <label for="report-month">Month</label>
            <select id="report-month" name="month">
                <?php foreach (['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Aug','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dec'] as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $month === $value ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="report-filter-group">
            <label for="report-year">Year</label>
            <select id="report-year" name="year">
                <?php for ($y = 2020; $y <= (int) date('Y'); $y++): ?>
                    <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="report-filter-actions">
            <button type="submit" style="color: white;">Apply Filter</button>
        </div>

    </form>

    <?php foreach (($groupedRows ?? []) as $productType => $typeRows): ?>
        <div class="panel-card table-card report-table-card report-table-card-squared">
            <div class="report-table-title"><?= esc($productType) ?></div>
            <table class="data-table report-table report-table-squared">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Stock No</th>
                        <th>Product</th>
                        <th colspan="4">BEGINNING</th>
                        <th colspan="3">PURCHASE</th>
                        <th colspan="3">USED</th>
                        <th colspan="3">SPOILED</th>
                        <th colspan="3">ENDING</th>
                    </tr>
                    <tr>
                        <th></th><th></th><th></th>
                        <th>Qty</th><th>Unit</th><th>Cost</th><th>Amount</th>
                        <th>Qty</th><th>Cost</th><th>Amount</th>
                        <th>Qty</th><th>Cost</th><th>Amount</th>
                        <th>Qty</th><th>Cost</th><th>Amount</th>
                        <th>Qty</th><th>Cost</th><th>Amount</th>
                    </tr>
                </thead>
                    <tbody>
                    <?php
                        $fmtQty = function($v) { $v = (float) $v; return $v == (int) $v ? (string)(int) $v : rtrim(number_format($v, 2, '.', ''), '0'); };
                    ?>
                    <?php foreach ($typeRows as $row): ?>
                        <tr data-product-id="<?= (int) $row['product_id'] ?>">
                            <td><?= $row['counter'] ?></td>
                            <td><?= esc($row['stock_no']) ?></td>
                            <td><?= esc($row['item']) ?></td>
                            <!-- BEGINNING -->
                            <td><?= $fmtQty($row['begin_qty']) ?></td>
                            <td><?= esc($row['unit_name']) ?></td>
                            <td><?= number_format($row['begin_cost'], 2) ?></td>
                            <td><?= number_format($row['begin_qty'] * $row['begin_cost'], 2) ?></td>
                            <!-- PURCHASE -->
                            <td><?= $fmtQty($row['purchase_qty']) ?></td>
                            <td><?= number_format($row['purchase_cost'], 2) ?></td>
                            <td><?= number_format($row['purchase_total'], 2) ?></td>
                            <!-- USED -->
                            <td><?= $fmtQty($row['used_qty']) ?></td>
                            <td><?= number_format($row['used_cost'], 2) ?></td>
                            <td><?= number_format($row['used_total'], 2) ?></td>
                            <!-- SPOILED -->
                            <td><?= $fmtQty($row['spoiled_qty']) ?></td>
                            <td><?= number_format($row['spoiled_cost'], 2) ?></td>
                            <td><?= number_format($row['spoiled_total'], 2) ?></td>
                            <!-- ENDING -->
                            <td><?= $fmtQty($row['ending_qty']) ?></td>
                            <td><?= number_format($row['ending_cost'], 2) ?></td>
                            <td><?= number_format($row['ending_qty'] * $row['ending_cost'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>

