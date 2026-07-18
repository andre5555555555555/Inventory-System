<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportModel extends Model
{
    protected $table = 'transaction_table';

    public function batchLedger(string $search, int $year, string $month, int $typeId, int $userOfficeId = 0): array
    {
        $monthStart = sprintf('%04d-%02d-01', $year, (int) $month);
        $nextMonth  = date('Y-m-d', strtotime($monthStart . ' +1 month'));

        $openingRows = $this->historyRowsBefore($monthStart, $userOfficeId);
        $periodRows  = $this->historyRowsWithin($monthStart, $nextMonth, $search, $typeId, $userOfficeId);

        $batchMemory  = [];
        $runningQty   = [];
        $runningValue = [];

        foreach ($openingRows as $row) {
            $this->applyRowToRunningBalance($row, $batchMemory, $runningQty, $runningValue);
        }

        $ledgerRows = [];
        $counter    = 1;

        foreach ($periodRows as $row) {
            $productId = (int) $row['product_id'];
            $batchMemory[$productId]  ??= [];
            $runningQty[$productId]   ??= 0;
            $runningValue[$productId] ??= 0.0;

            $beginQty  = $runningQty[$productId];
            $beginCost = $beginQty > 0 ? $runningValue[$productId] / $beginQty : 0.0;

            [$purchaseQty, $purchaseCost, $purchaseTotal] = $this->purchaseValues($row, $batchMemory[$productId], $runningQty[$productId], $runningValue[$productId]);
            [$usedQty, $usedCost, $usedTotal]             = $this->issueValues($row, 0, $batchMemory[$productId], $runningQty[$productId], $runningValue[$productId]);
            [$spoiledQty, $spoiledCost, $spoiledTotal]    = $this->issueValues($row, 3, $batchMemory[$productId], $runningQty[$productId], $runningValue[$productId]);

            $endingQty  = $runningQty[$productId];
            $endingCost = $endingQty > 0 ? $runningValue[$productId] / $endingQty : 0.0;

            $ledgerRows[] = [
                'counter'           => $counter++,
                'product_id'        => $productId,
                'transaction_id'    => (int) ($row['transaction_id'] ?? 0),
                'transaction_type'  => (int) ($row['transaction_type_id'] ?? 0),
                'product_type'      => $row['product_type'] ?: 'Uncategorized',
                'stock_no'          => $row['stock_no'],
                'item'              => $row['product'],
                'unit_name'         => $row['unit_name'],
                'begin_qty'         => $beginQty,
                'begin_cost'        => $beginCost,
                'purchase_qty'      => $purchaseQty,
                'purchase_cost'     => $purchaseCost,
                'purchase_total'    => $purchaseTotal,
                'used_qty'          => $usedQty,
                'used_cost'         => $usedCost,
                'used_total'        => $usedTotal,
                'spoiled_qty'       => $spoiledQty,
                'spoiled_cost'      => $spoiledCost,
                'spoiled_total'     => $spoiledTotal,
                'ending_qty'        => $endingQty,
                'ending_cost'       => $endingCost,
            ];
        }

        $groupedRows = [];
        foreach ($ledgerRows as $row) {
            $groupedRows[$row['product_type']][] = $row;
        }

        return [
            'rows'        => $ledgerRows,
            'groupedRows' => $groupedRows,
        ];
    }

    public function orderedProductTypes(int $userOfficeId = 0): array
    {
        $builder = $this->db->table('type_of_product')->orderBy('type', 'ASC');
        if ($userOfficeId > 0) {
            $builder->where('user_office_id', $userOfficeId);
        }
        return $builder->get()->getResultArray();
    }

    private function historyRowsBefore(string $monthStart, int $userOfficeId = 0): array
    {
        $officeFilter = $userOfficeId > 0 ? ' AND t.user_office_id = ' . (int) $userOfficeId : '';

        return $this->db->query(
            'SELECT b.product_id, t.transaction_id, t.transaction_qty, t.transaction_unit_cost, t.transaction_type_id
             FROM transaction_table t
             INNER JOIN batch_table b ON t.batch_id = b.batch_id
             WHERE t.transaction_date < ?' . $officeFilter . '
             ORDER BY b.product_id ASC, t.transaction_date ASC, t.transaction_id ASC',
            [$monthStart]
        )->getResultArray();
    }

    private function historyRowsWithin(string $monthStart, string $nextMonth, string $search, int $typeId, int $userOfficeId = 0): array
    {
        $builder = $this->db->table('transaction_table t');
        $builder->select([
            'b.product_id',
            'p.product',
            'p.stock_no',
            'COALESCE(pt.type, "Uncategorized") AS product_type',
            'COALESCE(ut.unit, "Deleted Unit") AS unit_name',
            't.transaction_id',
            't.transaction_qty',
            't.transaction_unit_cost',
            't.transaction_type_id',
        ]);
        $builder->join('batch_table b', 't.batch_id = b.batch_id');
        $builder->join('product_table p', 'b.product_id = p.product_id');
        $builder->join('unit_table ut', 'p.unit_id = ut.unit_id', 'left');
        $builder->join('type_of_product pt', 'p.type_id = pt.type_id', 'left');
        $builder->where('t.transaction_date >=', $monthStart);
        $builder->where('t.transaction_date <', $nextMonth);
        $builder->whereIn('t.transaction_type_id', [1, 2, 3]); // include spoiled adjustments

        if ($userOfficeId > 0) {
            $builder->where('t.user_office_id', $userOfficeId);
        }
        if ($search !== '') {
            $builder->like('p.product', $search);
        }
        if ($typeId > 0) {
            $builder->where('p.type_id', $typeId);
        }

        return $builder
            ->orderBy('pt.type', 'ASC')
            ->orderBy('b.product_id', 'ASC')
            ->orderBy('t.transaction_date', 'ASC')
            ->orderBy('t.transaction_id', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function applyRowToRunningBalance(array $row, array &$batchMemory, array &$runningQty, array &$runningValue): void
    {
        $productId = (int) $row['product_id'];
        $batchMemory[$productId]  ??= [];
        $runningQty[$productId]   ??= 0;
        $runningValue[$productId] ??= 0.0;

        $typeId = (int) ($row['transaction_type_id'] ?? 0);

        if ($typeId === 1) { // receipt
            $unitCost              = (float) ($row['transaction_unit_cost'] ?? 0);
            $qty                   = (int) ($row['transaction_qty'] ?? 0);
            $batchMemory[$productId][] = ['qty' => $qty, 'cost' => $unitCost];
            $runningQty[$productId]   += $qty;
            $runningValue[$productId] += $qty * $unitCost;
        }

        if ($typeId === 2 || $typeId === 3) { // issue or adjust_out
            $qty         = (int) ($row['transaction_qty'] ?? 0);
            $issuedCost  = $this->fifoIssue($batchMemory[$productId], $qty);
            $runningQty[$productId]   -= $qty;
            $runningValue[$productId] -= $issuedCost;
        }
    }

    private function purchaseValues(array $row, array &$batches, int &$runningQty, float &$runningValue): array
    {
        $typeId = (int) ($row['transaction_type_id'] ?? 0);
        if ($typeId !== 1) { // not receipt
            return [0, 0.0, 0.0];
        }

        $purchaseQty   = (int) ($row['transaction_qty'] ?? 0);
        $purchaseCost  = (float) ($row['transaction_unit_cost'] ?? 0);
        $purchaseTotal = $purchaseQty * $purchaseCost;

        $batches[]     = ['qty' => $purchaseQty, 'cost' => $purchaseCost];
        $runningQty   += $purchaseQty;
        $runningValue += $purchaseTotal;

        return [$purchaseQty, $purchaseCost, $purchaseTotal];
    }

    private function issueValues(
        array $row,
        int $spoiledTypeId,
        array &$batches,
        int &$runningQty,
        float &$runningValue
    ): array {
        $typeId   = (int) ($row['transaction_type_id'] ?? 0);
        $issueQty = (int) ($row['transaction_qty'] ?? 0);

        if ($issueQty <= 0) {
            return [0, 0.0, 0.0];
        }

        // $spoiledTypeId 3 means adjust_out, 0 means regular issue
        if ($spoiledTypeId === 3 && $typeId !== 3) {
            return [0, 0.0, 0.0];
        }
        if ($spoiledTypeId === 0 && $typeId === 3) {
            return [0, 0.0, 0.0];
        }
        if ($typeId !== 2 && $typeId !== 3) {
            return [0, 0.0, 0.0];
        }

        $storedUnitCost = (float) ($row['transaction_unit_cost'] ?? 0);

        if ($storedUnitCost > 0) {
            // Manually overridden cost — use it directly and drain FIFO silently
            $issueTotal = $storedUnitCost * $issueQty;
            $this->fifoIssue($batches, $issueQty); // drain FIFO so running balance stays consistent
        } else {
            // No manual override — fall back to FIFO
            $issueTotal = $this->fifoIssue($batches, $issueQty);
        }

        $issueCost = $issueQty > 0 ? $issueTotal / $issueQty : 0.0;

        $runningQty   -= $issueQty;
        $runningValue -= $issueTotal;

        return [$issueQty, $issueCost, $issueTotal];
    }

    private function fifoIssue(array &$batches, int $qty): float
    {
        $totalCost = 0.0;

        foreach ($batches as &$batch) {
            if ($qty <= 0) {
                break;
            }
            if ($batch['qty'] <= 0) {
                continue;
            }
            $take       = min($batch['qty'], $qty);
            $totalCost += $take * $batch['cost'];
            $batch['qty'] -= $take;
            $qty -= $take;
        }

        return $totalCost;
    }
    /**
     * Build stockcard ledger rows for an arbitrary date range and optional product filter.
     * Used by the export controller.
     *
     * @param  string $dateFrom     'Y-m-d' — first day of range (inclusive)
     * @param  string $dateTo       'Y-m-d' — first day AFTER range end (exclusive)
     * @param  int    $productId    0 = all products
     * @param  int    $userOfficeId 0 = all offices
     */
    public function stockcardForExport(
        string $dateFrom,
        string $dateTo,
        int $productId = 0,
        int $userOfficeId = 0
    ): array {
        $openingRows = $this->historyRowsBeforeFiltered($dateFrom, $userOfficeId, $productId);
        $periodRows  = $this->historyRowsRange($dateFrom, $dateTo, $userOfficeId, $productId);

        $batchMemory  = [];
        $runningQty   = [];
        $runningValue = [];

        foreach ($openingRows as $row) {
            $this->applyRowToRunningBalance($row, $batchMemory, $runningQty, $runningValue);
        }

        $ledgerRows = [];
        $counter    = 1;

        foreach ($periodRows as $row) {
            $pid = (int) $row['product_id'];
            $batchMemory[$pid]  ??= [];
            $runningQty[$pid]   ??= 0;
            $runningValue[$pid] ??= 0.0;

            $beginQty  = $runningQty[$pid];
            $beginCost = $beginQty > 0 ? $runningValue[$pid] / $beginQty : 0.0;

            [$purchaseQty, $purchaseCost, $purchaseTotal] = $this->purchaseValues($row, $batchMemory[$pid], $runningQty[$pid], $runningValue[$pid]);
            [$usedQty, $usedCost, $usedTotal]             = $this->issueValues($row, 0, $batchMemory[$pid], $runningQty[$pid], $runningValue[$pid]);
            [$spoiledQty, $spoiledCost, $spoiledTotal]    = $this->issueValues($row, 3, $batchMemory[$pid], $runningQty[$pid], $runningValue[$pid]);

            $endingQty  = $runningQty[$pid];
            $endingCost = $endingQty > 0 ? $runningValue[$pid] / $endingQty : 0.0;

            $ledgerRows[] = [
                'counter'        => $counter++,
                'product_id'     => $pid,
                'product_type'   => $row['product_type'] ?: 'Uncategorized',
                'stock_no'       => $row['stock_no'],
                'item'           => $row['product'],
                'unit_name'      => $row['unit_name'],
                'begin_qty'      => $beginQty,
                'begin_cost'     => $beginCost,
                'purchase_qty'   => $purchaseQty,
                'purchase_cost'  => $purchaseCost,
                'purchase_total' => $purchaseTotal,
                'used_qty'       => $usedQty,
                'used_cost'      => $usedCost,
                'used_total'     => $usedTotal,
                'spoiled_qty'    => $spoiledQty,
                'spoiled_cost'   => $spoiledCost,
                'spoiled_total'  => $spoiledTotal,
                'ending_qty'     => $endingQty,
                'ending_cost'    => $endingCost,
            ];
        }

        $groupedRows = [];
        foreach ($ledgerRows as $row) {
            $groupedRows[$row['product_type']][] = $row;
        }

        return [
            'rows'        => $ledgerRows,
            'groupedRows' => $groupedRows,
            'date_from'   => $dateFrom,
            'date_to'     => $dateTo,
        ];
    }

    private function historyRowsBeforeFiltered(string $before, int $userOfficeId = 0, int $productId = 0): array
    {
        $officeFilter  = $userOfficeId > 0 ? ' AND t.user_office_id = ' . (int) $userOfficeId : '';
        $productFilter = $productId > 0    ? ' AND b.product_id = ' . (int) $productId         : '';

        return $this->db->query(
            'SELECT b.product_id, t.transaction_qty, t.transaction_unit_cost, t.transaction_type_id
             FROM transaction_table t
             INNER JOIN batch_table b ON t.batch_id = b.batch_id
             WHERE t.transaction_date < ?' . $officeFilter . $productFilter . '
             ORDER BY b.product_id ASC, t.transaction_date ASC, t.transaction_id ASC',
            [$before]
        )->getResultArray();
    }

    private function historyRowsRange(
        string $dateFrom,
        string $dateTo,
        int $userOfficeId = 0,
        int $productId = 0
    ): array {
        $builder = $this->db->table('transaction_table t');
        $builder->select([
            'b.product_id',
            'p.product',
            'p.stock_no',
            'COALESCE(pt.type, "Uncategorized") AS product_type',
            'COALESCE(ut.unit, "Deleted Unit") AS unit_name',
            't.transaction_qty',
            't.transaction_unit_cost',
            't.transaction_type_id',
        ]);
        $builder->join('batch_table b', 't.batch_id = b.batch_id');
        $builder->join('product_table p', 'b.product_id = p.product_id');
        $builder->join('unit_table ut', 'p.unit_id = ut.unit_id', 'left');
        $builder->join('type_of_product pt', 'p.type_id = pt.type_id', 'left');
        $builder->where('t.transaction_date >=', $dateFrom);
        $builder->where('t.transaction_date <', $dateTo);
        $builder->whereIn('t.transaction_type_id', [1, 2, 3]);

        if ($userOfficeId > 0) {
            $builder->where('t.user_office_id', $userOfficeId);
        }
        if ($productId > 0) {
            $builder->where('b.product_id', $productId);
        }

        return $builder
            ->orderBy('pt.type', 'ASC')
            ->orderBy('b.product_id', 'ASC')
            ->orderBy('t.transaction_date', 'ASC')
            ->orderBy('t.transaction_id', 'ASC')
            ->get()
            ->getResultArray();
    }
}



