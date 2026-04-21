<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportModel extends Model
{
    protected $table = 'transaction';

    public function batchLedger(string $search, int $year, string $month, int $itemTypeId, int $userOfficeId = 0): array
    {
        $monthStart = sprintf('%04d-%02d-01', $year, (int) $month);
        $nextMonth = date('Y-m-d', strtotime($monthStart . ' +1 month'));

        $openingRows = $this->historyRowsBefore($monthStart, $userOfficeId);
        $periodRows = $this->historyRowsWithin($monthStart, $nextMonth, $search, $itemTypeId, $userOfficeId);

        $batchMemory = [];
        $runningQty = [];
        $runningValue = [];

        foreach ($openingRows as $row) {
            $this->applyRowToRunningBalance($row, $batchMemory, $runningQty, $runningValue);
        }

        $ledgerRows = [];
        $counter = 1;

        foreach ($periodRows as $row) {
            $itemId = (int) $row['item_id'];
            $batchMemory[$itemId] ??= [];
            $runningQty[$itemId] ??= 0;
            $runningValue[$itemId] ??= 0.0;

            $beginQty = $runningQty[$itemId];
            $beginCost = $beginQty > 0 ? $runningValue[$itemId] / $beginQty : 0.0;

            [$purchaseQty, $purchaseCost, $purchaseTotal] = $this->purchaseValues($row, $batchMemory[$itemId], $runningQty[$itemId], $runningValue[$itemId]);
            [$usedQty, $usedCost, $usedTotal] = $this->issueValues($row, 0, $batchMemory[$itemId], $runningQty[$itemId], $runningValue[$itemId]);
            [$spoiledQty, $spoiledCost, $spoiledTotal] = $this->issueValues($row, 4, $batchMemory[$itemId], $runningQty[$itemId], $runningValue[$itemId]);

            $endingQty = $runningQty[$itemId];
            $endingCost = $endingQty > 0 ? $runningValue[$itemId] / $endingQty : 0.0;

            $ledgerRows[] = [
                'counter'        => $counter++,
                'item_type'      => $row['item_type'] ?: 'Uncategorized',
                'stockcard_no'   => $row['stockcard_no'],
                'item'           => $row['item'],
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
            $groupedRows[$row['item_type']][] = $row;
        }

        return [
            'rows'        => $ledgerRows,
            'groupedRows' => $groupedRows,
        ];
    }

    public function orderedItemTypes(int $userOfficeId = 0): array
    {
        $builder = $this->db->table('item_type')
            ->orderBy('item_type', 'ASC');
        if ($userOfficeId > 0) {
            $builder->where('user_office_id', $userOfficeId);
        }
        return $builder->get()->getResultArray();
    }

    private function historyRowsBefore(string $monthStart, int $userOfficeId = 0): array
    {
        $officeFilter = $userOfficeId > 0 ? ' AND sc.user_office_id = ' . (int) $userOfficeId : '';

        return $this->db->query(
            'SELECT sc.item_id, t.receipt_qty, t.issue_qty, sc.transaction_type_id,
                    COALESCE(batch.unit_cost, 0) AS unit_cost
             FROM transaction t
             INNER JOIN stockcard sc ON sc.transaction_id = t.transaction_id
             LEFT JOIN batch ON batch.transaction_id = t.transaction_id
             WHERE t.date < ?' . $officeFilter . '
             ORDER BY sc.item_id ASC, t.date ASC, t.transaction_id ASC',
            [$monthStart]
        )->getResultArray();
    }

    private function historyRowsWithin(string $monthStart, string $nextMonth, string $search, int $itemTypeId, int $userOfficeId = 0): array
    {
        $builder = $this->db->table('transaction t');
        $builder->select([
            'item.item_id',
            'item.item',
            'CONCAT(COALESCE(ic.item_category, "Uncategorized"), "-", item.item_no) AS stockcard_no',
            'COALESCE(it.item_type, "Uncategorized") AS item_type',
            'COALESCE(unit.unit, "Deleted Unit") AS unit_name',
            't.receipt_qty',
            't.issue_qty',
            'sc.transaction_type_id',
            'COALESCE(batch.unit_cost, 0) AS unit_cost',
        ]);
        $builder->join('stockcard sc', 't.transaction_id = sc.transaction_id');
        $builder->join('item', 'sc.item_id = item.item_id');
        $builder->join('unit', 'item.unit_id = unit.unit_id', 'left');
        $builder->join('item_type it', 'item.item_type_id = it.item_type_id', 'left');
        $builder->join('item_category ic', 'item.item_category_id = ic.item_category_id', 'left');
        $builder->join('batch', 'batch.transaction_id = t.transaction_id', 'left');
        $builder->where('t.date >=', $monthStart);
        $builder->where('t.date <', $nextMonth);

        if ($userOfficeId > 0) {
            $builder->where('sc.user_office_id', $userOfficeId);
        }

        if ($search !== '') {
            $builder->like('item.item', $search);
        }

        if ($itemTypeId > 0) {
            $builder->where('item.item_type_id', $itemTypeId);
        }

        return $builder
            ->orderBy('it.item_type', 'ASC')
            ->orderBy('item.item_id', 'ASC')
            ->orderBy('t.date', 'ASC')
            ->orderBy('t.transaction_id', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function applyRowToRunningBalance(array $row, array &$batchMemory, array &$runningQty, array &$runningValue): void
    {
        $itemId = (int) $row['item_id'];
        $batchMemory[$itemId] ??= [];
        $runningQty[$itemId] ??= 0;
        $runningValue[$itemId] ??= 0.0;

        $receiptQty = (int) ($row['receipt_qty'] ?? 0);
        if ($receiptQty > 0) {
            $unitCost = (float) ($row['unit_cost'] ?? 0);
            $batchMemory[$itemId][] = ['qty' => $receiptQty, 'cost' => $unitCost];
            $runningQty[$itemId] += $receiptQty;
            $runningValue[$itemId] += $receiptQty * $unitCost;
        }

        $issueQty = (int) ($row['issue_qty'] ?? 0);
        if ($issueQty > 0) {
            $issuedCost = $this->fifoIssue($batchMemory[$itemId], $issueQty);
            $runningQty[$itemId] -= $issueQty;
            $runningValue[$itemId] -= $issuedCost;
        }
    }

    private function purchaseValues(array $row, array &$batches, int &$runningQty, float &$runningValue): array
    {
        $purchaseQty = (int) ($row['receipt_qty'] ?? 0);
        if ($purchaseQty <= 0) {
            return [0, 0.0, 0.0];
        }

        $purchaseCost = (float) ($row['unit_cost'] ?? 0);
        $purchaseTotal = $purchaseQty * $purchaseCost;

        $batches[] = ['qty' => $purchaseQty, 'cost' => $purchaseCost];
        $runningQty += $purchaseQty;
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
        $issueQty = (int) ($row['issue_qty'] ?? 0);
        $transactionTypeId = (int) ($row['transaction_type_id'] ?? 0);

        if ($issueQty <= 0) {
            return [0, 0.0, 0.0];
        }

        if ($spoiledTypeId === 4 && $transactionTypeId !== 4) {
            return [0, 0.0, 0.0];
        }

        if ($spoiledTypeId === 0 && $transactionTypeId === 4) {
            return [0, 0.0, 0.0];
        }

        $issueTotal = $this->fifoIssue($batches, $issueQty);
        $issueCost = $issueQty > 0 ? $issueTotal / $issueQty : 0.0;

        $runningQty -= $issueQty;
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

            $take = min($batch['qty'], $qty);
            $totalCost += $take * $batch['cost'];
            $batch['qty'] -= $take;
            $qty -= $take;
        }

        return $totalCost;
    }
}
