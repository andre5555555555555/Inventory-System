<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * TransactionModel — replaces old StockcardModel.
 * Tracks stock through transaction_table + batch_table.
 */
class TransactionModel extends Model
{
    protected $table                 = 'transaction_table';
    protected $primaryKey            = 'transaction_id';
    protected $returnType            = 'array';
    protected $allowedFields         = [
        'transaction_type_id', 'transaction_qty', 'transaction_unit_cost',
        'transaction_date', 'batch_id', 'reference_id', 'office_id',
        'user_id', 'user_office_id', 'adjustment_reason_id',
        'created_at', 'updated_at',
    ];
    protected bool $allowEmptyInserts = false;

    /**
     * Get current stock for a product (sum of current_qty in batch_table).
     */
    public function currentStock(int $productId, int $userOfficeId = 0): int
    {
        $builder = $this->db->table('batch_table')
            ->selectSum('current_qty', 'stock')
            ->where('product_id', $productId);
        if ($userOfficeId > 0) {
            $builder->where('user_office_id', $userOfficeId);
        }
        $row = $builder->get()->getRowArray();
        return (int) ($row['stock'] ?? 0);
    }

    /**
     * Paginated transaction history for a product (stockcard-style view).
     */
    public function paginatedHistory(
        int $productId,
        string $filterType,
        int $page,
        int $limit,
        int $year = 0,
        string $month = '',
        string $search = '',
        int $userOfficeId = 0
    ): array {
        $order  = $filterType === 'oldest' ? 'ASC' : 'DESC';
        $offset = ($page - 1) * $limit;
        $params = [$productId];

        $officeFilter = '';
        $dateFilter   = '';
        $searchFilter = '';

        if ($userOfficeId > 0) {
            $officeFilter = ' AND t.user_office_id = ?';
            $params[]     = $userOfficeId;
        }

        if ($year > 0 && preg_match('/^\d{1,2}$/', $month)) {
            $dateFilter = " AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?";
            $params[]   = sprintf('%04d-%02d', $year, (int) $month);
        }

        if ($search !== '') {
            $searchFilter = ' AND (p.product LIKE ? OR p.product_description LIKE ? OR p.product_no LIKE ? OR ot.office_name LIKE ? OR r.reference LIKE ?)';
            $wild         = '%' . $search . '%';
            array_push($params, $wild, $wild, $wild, $wild, $wild);
        }

        // Running balance sub-query using window function
        // Returns receipt_qty / issue_qty / date to match the original stockcard table layout
        $rows = $this->db->query(
            "SELECT * FROM (
                SELECT
                    t.transaction_id,
                    t.batch_id,
                    t.transaction_date                                          AS date,
                    CASE WHEN tt.transaction_type IN ('receipt', 'return')
                         THEN t.transaction_qty ELSE 0 END                     AS receipt_qty,
                    CASE WHEN tt.transaction_type IN ('issue','adjust_out','borrow')
                         THEN t.transaction_qty ELSE 0 END                     AS issue_qty,
                    t.transaction_unit_cost,
                    t.transaction_type_id,
                    p.product AS item_name,
                    p.product_description AS description,
                    p.product_no,
                    p.stock_no,
                    COALESCE(ut.unit, 'Deleted Unit') AS unit_name,
                    COALESCE(ot.office_name, '') AS office,
                    COALESCE(r.reference, 'N/A') AS reference,
                    COALESCE(et.entity, 'Deleted Entity') AS entity_name,
                    COALESCE(et.fund_cluster, '-') AS fund_cluster,
                    tt.transaction_type,
                    b.product_id,
                    SUM(CASE WHEN tt.transaction_type IN ('receipt', 'return') THEN t.transaction_qty
                             ELSE -t.transaction_qty END)
                        OVER (PARTITION BY b.product_id ORDER BY t.transaction_date ASC, t.transaction_id ASC) AS balance
                FROM transaction_table t
                INNER JOIN transaction_type_table tt ON t.transaction_type_id = tt.transaction_type_id
                INNER JOIN batch_table b ON t.batch_id = b.batch_id
                INNER JOIN product_table p ON b.product_id = p.product_id
                LEFT JOIN unit_table ut ON p.unit_id = ut.unit_id
                LEFT JOIN office_table ot ON t.office_id = ot.office_id
                LEFT JOIN reference_table r ON t.reference_id = r.reference_id
                LEFT JOIN entity_table et ON p.entity_id = et.entity_id
                WHERE b.product_id = ? {$officeFilter}
            ) AS base
            WHERE transaction_type IN ('receipt','issue','adjust_out','borrow','return') {$searchFilter} {$dateFilter}
            ORDER BY date {$order}, transaction_id {$order}
            LIMIT {$limit} OFFSET {$offset}",
            $params
        )->getResultArray();

        // Count total for pagination
        $totalParams = [$productId];
        $totalSql = "SELECT COUNT(*) AS total
                     FROM transaction_table t
                     INNER JOIN batch_table b ON t.batch_id = b.batch_id
                     INNER JOIN transaction_type_table tt ON t.transaction_type_id = tt.transaction_type_id
                     WHERE b.product_id = ?
                       AND tt.transaction_type IN ('receipt','issue','adjust_out','borrow','return')";

        if ($userOfficeId > 0) {
            $totalSql   .= ' AND t.user_office_id = ?';
            $totalParams[] = $userOfficeId;
        }

        if ($year > 0 && preg_match('/^\d{1,2}$/', $month)) {
            $totalSql   .= " AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?";
            $totalParams[] = sprintf('%04d-%02d', $year, (int) $month);
        }

        $total = (int) ($this->db->query($totalSql, $totalParams)->getRowArray()['total'] ?? 0);

        return [
            'rows'  => $rows,
            'total' => $total,
        ];
    }
}




