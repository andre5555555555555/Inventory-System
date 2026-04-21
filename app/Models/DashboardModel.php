<?php

namespace App\Models;

use CodeIgniter\Model;

class DashboardModel extends Model
{
    protected $table = 'item';

    public function overview(int $userOfficeId = 0): array
    {
        $lowStock = $this->lowStock($userOfficeId);
        $expiring = $this->expiringSoon($userOfficeId);

        return [
            'lowStock'           => $lowStock,
            'expiring'           => $expiring,
            'outOfStock'         => $this->outOfStock($userOfficeId),
            'recentTransactions' => $this->recentTransactions($userOfficeId),
            'summary'            => [
                'totalItems'     => $this->totalItems($userOfficeId),
                'lowStockCount'  => count($lowStock),
                'expiringCount'  => count($expiring),
            ],
        ];
    }

    public function lowStock(int $userOfficeId = 0): array
    {
        $officeFilter = $userOfficeId > 0 ? ' AND item.user_office_id = ' . (int) $userOfficeId : '';

        return $this->db->query(
            'SELECT item.item,
                    COALESCE(SUM(batch.remaining_qty), 0) AS stock_left,
                    COALESCE(item.re_order_point, 0) AS re_order_point
             FROM item
             LEFT JOIN batch ON item.item_id = batch.item_id
             WHERE 1=1' . $officeFilter . '
             GROUP BY item.item_id, item.item, item.re_order_point
             HAVING stock_left <= re_order_point
                AND stock_left > 0
                AND re_order_point > 0'
        )->getResultArray();
    }

    public function expiringSoon(int $userOfficeId = 0): array
    {
        $officeFilter = $userOfficeId > 0 ? ' AND item.user_office_id = ' . (int) $userOfficeId : '';

        return $this->db->query(
            'SELECT batch.batch_id, item.item, batch.expiration_date, batch.remaining_qty,
                    DATEDIFF(batch.expiration_date, CURDATE()) AS days_left
             FROM batch
             INNER JOIN item ON batch.item_id = item.item_id
             WHERE batch.remaining_qty > 0
               AND batch.expiration_date IS NOT NULL
               AND batch.expiration_date >= CURDATE()
               AND DATEDIFF(batch.expiration_date, CURDATE()) <= 30' . $officeFilter . '
             ORDER BY batch.expiration_date ASC'
        )->getResultArray();
    }

    public function outOfStock(int $userOfficeId = 0): array
    {
        $officeFilter = $userOfficeId > 0 ? ' AND item.user_office_id = ' . (int) $userOfficeId : '';

        return $this->db->query(
            'SELECT item.item
             FROM item
             LEFT JOIN batch ON item.item_id = batch.item_id
             WHERE 1=1' . $officeFilter . '
             GROUP BY item.item_id, item.item
             HAVING COALESCE(SUM(batch.remaining_qty), 0) = 0'
        )->getResultArray();
    }

    public function recentTransactions(int $userOfficeId = 0): array
    {
        $officeFilter = $userOfficeId > 0 ? ' AND stockcard.user_office_id = ' . (int) $userOfficeId : '';

        return $this->db->query(
            'SELECT item.item, transaction.receipt_qty, transaction.issue_qty, transaction.date
             FROM transaction
             INNER JOIN stockcard ON transaction.transaction_id = stockcard.transaction_id
             INNER JOIN item ON stockcard.item_id = item.item_id
             WHERE 1=1' . $officeFilter . '
             ORDER BY transaction.date DESC
             LIMIT 5'
        )->getResultArray();
    }

    public function totalItems(int $userOfficeId = 0): int
    {
        $builder = $this->db->table('item')->selectCount('item_id', 'total');
        if ($userOfficeId > 0) {
            $builder->where('user_office_id', $userOfficeId);
        }
        return (int) ($builder->get()->getRowArray()['total'] ?? 0);
    }
}
