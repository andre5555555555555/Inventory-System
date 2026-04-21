<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="stockcard-layout" data-stockcard-layout>
    <button type="button" class="stockcard-sidebar-toggle" data-stockcard-toggle>Items</button>
    <div class="stockcard-sidebar-backdrop" id="stockcardSidebarBackdrop" data-stockcard-backdrop></div>

    <aside class="sidebar sidebar-flat stockcard-sidebar" id="itemSidebar">
        <div class="stockcard-sidebar-head">
            <h3>ITEM LIST</h3>
            <button type="button" class="stockcard-sidebar-close" data-stockcard-toggle>Close</button>
        </div>
        <div class="searchbar stockcard-sidebar-search">
            <input type="text" data-stockcard-search placeholder="Search item list">
        </div>
        <ul>
            <?php foreach ($items as $item): ?>
                <?php $itemLabel = trim($item['item'] . ' #' . ($item['item_no'] ?? '') . (($item['description'] ?? '') !== '' ? ' - ' . $item['description'] : '')); ?>
                <li data-stockcard-item data-label="<?= esc(strtolower($itemLabel), 'attr') ?>">
                    <a href="<?= site_url('stockcard?item_id=' . (int) $item['item_id']) ?>"><?= esc($item['item']) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <div class="main-content-stockcard">
        <div class="stockcard-shell">
            <div class="page-header">
                <div>
                    <p class="page-eyebrow">Inventory</p>
                    <h1>Stockcard</h1>
                    <p class="page-subtitle">Filter movement, review balances, and inspect transaction history in one flat workspace.</p>
                </div>
            </div>

            <div class="toolbar-card stockcard-toolbar">
                <div class="searchbar">
                    <form method="get" action="<?= site_url('stockcard') ?>">
                        <input type="hidden" name="item_id" value="<?= (int) $itemId ?>">
                        
                        <a href="<?= site_url('stock/add?item_id=' . (int) $itemId) ?>" class="add-btn">+/- Stock</a>
                        <button type="button" class="filter-btn" data-filter-open>Filter</button>
                    </form>
                </div>
            </div>

            <div id="filterModal" class="filter-modal" data-filter-modal>
                <div class="filter-content">
                    <button type="button" class="close-btn" data-filter-close>&times;</button>
                    <form method="get" action="<?= site_url('stockcard') ?>">
                        <input type="hidden" name="item_id" value="<?= (int) $itemId ?>">
                        <label>Order:</label>
                        <select name="filter_type">
                            <option value="latest" <?= $filterType === 'latest' ? 'selected' : '' ?>>Latest</option>
                            <option value="oldest" <?= $filterType === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                        </select>

                        <label>Month:</label>
                        <select name="month">
                            <?php foreach (['' => 'All Months', '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'] as $value => $label): ?>
                                <option value="<?= esc($value) ?>" <?= $selectedMonth === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label>Year:</label>
                        <select name="year">
                            <option value="0" <?= (int) $selectedYear === 0 ? 'selected' : '' ?>>All Years</option>
                            <?php foreach (range(2000, (int) date('Y')) as $yearOption): ?>
                                <option value="<?= $yearOption ?>" <?= (int) $selectedYear === (int) $yearOption ? 'selected' : '' ?>><?= $yearOption ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">Apply</button>
                    </form>
                </div>
            </div>

            <div class="info info-flat stockcard-info">
                <div class="info-header">
                    <div class="info-left">
                        <p><strong>PRODUCT:</strong> <?= esc($itemInfo['item_name'] ?? '') ?></p>
                        <p><strong>DESCRIPTION:</strong> <?= esc($itemInfo['description'] ?? '') ?></p>
                        <p><strong>UNIT:</strong> <?= esc($itemInfo['unit_name'] ?? '') ?></p>
                    </div>
                    <div class="info-right">
                        <p><strong>Stock No:</strong> <?= esc($itemInfo['stockcard_no'] ?? '') ?></p>
                        <p><strong>Re-order:</strong> <?= esc((string) ($itemInfo['re_order_point'] ?? '')) ?></p>
                        <p><strong>Entity:</strong> <?= esc($itemInfo['entity_name'] ?? '') ?></p>
                        <p><strong>Fund Cluster:</strong> <?= esc($itemInfo['fund_cluster'] ?? '') ?></p>
                    </div>
                </div>

                <?php if ($stockcard): ?>
                    <div class="info-table">
                        <table class="data-table">
                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Receipt</th>
                                <th>Issue</th>
                                <th>Office</th>
                                <th>Balance</th>
                            </tr>
                            <?php foreach ($stockcard as $row): ?>
                                <tr>
                                    <td><?= esc(date('m/d/Y', strtotime($row['date']))) ?></td>
                                    <td><?= esc($row['reference']) ?></td>
                                    <td><?= esc((string) $row['receipt_qty']) ?></td>
                                    <td><?= esc((string) $row['issue_qty']) ?></td>
                                    <td><?= esc($row['office']) ?></td>
                                    <td><?= esc((string) $row['balance']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>

                    <div class="pagination">
                        <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                            <?php
                            $query = http_build_query([
                                'item_id' => $itemId,
                                'page' => $pageNumber,
                                'search' => $search,
                                'filter_type' => $filterType,
                                'month' => $selectedMonth,
                                'year' => $selectedYear,
                            ]);
                            ?>
                            <a href="<?= site_url('stockcard?' . $query) ?>" class="<?= $pageNumber === (int) $page ? 'active-page' : '' ?>"><?= $pageNumber ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
