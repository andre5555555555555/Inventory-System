<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

class InventoryService
{
    public function __construct(private readonly BaseConnection $db)
    {
    }

    public function currentReorderPoint(int $itemId): int
    {
        $row = $this->db->query(
            'SELECT COALESCE(re_order_point, 0) AS re_order_point
             FROM item
             WHERE item_id = ?
             LIMIT 1',
            [$itemId],
        )->getRowArray();

        return (int) ($row['re_order_point'] ?? 0);
    }

    public function currentEntityId(int $itemId): ?int
    {
        $row = $this->db->query(
            'SELECT entity_id
             FROM item
             WHERE item_id = ?
             LIMIT 1',
            [$itemId],
        )->getRowArray();

        if (! $row || $row['entity_id'] === null) {
            return null;
        }

        return (int) $row['entity_id'];
    }

    public function currentStock(int $itemId, int $userOfficeId = 0): int
    {
        $sql = 'SELECT COALESCE(SUM(t.receipt_qty), 0) - COALESCE(SUM(t.issue_qty), 0) AS stock
             FROM stockcard sc
             INNER JOIN transaction t ON t.transaction_id = sc.transaction_id
             WHERE sc.item_id = ?';
        $params = [$itemId];

        if ($userOfficeId > 0) {
            $sql .= ' AND sc.user_office_id = ?';
            $params[] = $userOfficeId;
        }

        $row = $this->db->query($sql, $params)->getRowArray();

        return (int) ($row['stock'] ?? 0);
    }

    public function saveStock(array $payload): void
    {
        $this->db->transException(true)->transStart();

        $itemId        = (int) $payload['item_id'];
        $qty           = (int) $payload['quantity'];
        $type          = $payload['adjust_type'];
        $unitCost      = (float) ($payload['unit_cost'] ?? 0);
        $referenceId   = $this->resolveReferenceId($payload);
        $officeId      = (int) $payload['office_id'];
        $entityId      = $this->currentEntityId($itemId);
        $dateTime      = $this->dateTime($payload['date'] ?? date('Y-m-d'));
        $expDate       = $payload['expiration_date'] ?: null;
        $userOfficeId  = (int) ($payload['user_office_id'] ?? 0);

        if ($entityId === null || $entityId <= 0) {
            throw new DomainException('Set an entity in Product setup before saving stock.');
        }

        if ($qty <= 0) {
            throw new DomainException('Quantity must be greater than 0.');
        }

        $currentStock = $this->currentStock($itemId, $userOfficeId);
        if ($type === 'OUT' && $qty > $currentStock) {
            throw new DomainException('Not enough stock.');
        }

        if ($type === 'IN' && $unitCost <= 0) {
            throw new DomainException('Unit cost is required for stock-in.');
        }

        $receiptQty = $type === 'IN' ? $qty : 0;
        $issueQty   = $type === 'OUT' ? $qty : 0;

        $this->db->table('transaction')->insert([
            'date'                 => $dateTime,
            'receipt_qty'          => $receiptQty,
            'issue_qty'            => $issueQty,
            'day_consume'          => '',
            'expiration_date'      => $expDate,
            'adjustment_reason_id' => null,
            'user_office_id'       => $userOfficeId,
        ]);

        $transactionId = (int) $this->db->insertID();

        $this->db->table('stockcard')->insert([
            'transaction_id'      => $transactionId,
            'reference_id'        => $referenceId,
            'office_id'           => $officeId,
            'item_id'             => $itemId,
            'transaction_type_id' => $type === 'IN' ? 1 : 2,
            'user_office_id'      => $userOfficeId,
        ]);

        if ($type === 'IN') {
            $this->db->table('batch')->insert([
                'transaction_id'  => $transactionId,
                'item_id'         => $itemId,
                'expiration_date' => $expDate,
                'quantity'        => $qty,
                'remaining_qty'   => $qty,
                'unit_cost'       => $unitCost,
                'user_office_id'  => $userOfficeId,
            ]);
        } else {
            $this->depleteBatches($itemId, $qty, $userOfficeId);
        }

        $this->db->transComplete();
    }

    public function adjustStock(array $payload): void
    {
        $this->db->transException(true)->transStart();

        $itemId        = (int) $payload['item_id'];
        $qty           = (int) $payload['quantity'];
        $type          = $payload['adjust_type'];
        $unitCost      = (float) ($payload['unit_cost'] ?? 0);
        $referenceId   = (int) $payload['reference_id'];
        $officeId      = (int) $payload['office_id'];
        $entityId      = $this->currentEntityId($itemId);
        $reasonId      = (int) $payload['reason_id'];
        $dateTime      = $this->dateTime($payload['date'] ?? date('Y-m-d'));
        $userOfficeId  = (int) ($payload['user_office_id'] ?? 0);

        if ($entityId === null || $entityId <= 0) {
            throw new DomainException('Set an entity in Product setup before saving an adjustment.');
        }

        if ($itemId <= 0) {
            throw new DomainException('Select an item first.');
        }

        if ($qty <= 0) {
            throw new DomainException('Quantity must be greater than 0.');
        }

        $currentStock = $this->currentStock($itemId, $userOfficeId);
        if ($type === 'OUT' && $qty > $currentStock) {
            throw new DomainException('Not enough stock.');
        }

        if ($type === 'IN' && $unitCost <= 0) {
            throw new DomainException('Unit cost is required for adjustment-in.');
        }

        $receiptQty = $type === 'IN' ? $qty : 0;
        $issueQty   = $type === 'OUT' ? $qty : 0;

        $this->db->table('transaction')->insert([
            'date'                 => $dateTime,
            'receipt_qty'          => $receiptQty,
            'issue_qty'            => $issueQty,
            'day_consume'          => '',
            'expiration_date'      => null,
            'adjustment_reason_id' => $reasonId,
            'user_office_id'       => $userOfficeId,
        ]);

        $transactionId = (int) $this->db->insertID();

        $this->db->table('stockcard')->insert([
            'transaction_id'      => $transactionId,
            'reference_id'        => $referenceId,
            'office_id'           => $officeId,
            'item_id'             => $itemId,
            'transaction_type_id' => $type === 'IN' ? 3 : 4,
            'user_office_id'      => $userOfficeId,
        ]);

        if ($type === 'IN') {
            $this->db->table('batch')->insert([
                'transaction_id' => $transactionId,
                'item_id'        => $itemId,
                'quantity'       => $qty,
                'remaining_qty'  => $qty,
                'unit_cost'      => $unitCost,
                'user_office_id' => $userOfficeId,
            ]);
        } else {
            $this->depleteBatches($itemId, $qty, $userOfficeId);
        }

        $this->db->transComplete();
    }

    private function depleteBatches(int $itemId, int $qty, int $userOfficeId = 0): void
    {
        $remaining = $qty;

        $sql = 'SELECT batch_id, remaining_qty
             FROM batch
             WHERE item_id = ? AND remaining_qty > 0';
        $params = [$itemId];

        if ($userOfficeId > 0) {
            $sql .= ' AND user_office_id = ?';
            $params[] = $userOfficeId;
        }

        $sql .= ' ORDER BY created_at ASC, batch_id ASC';

        $batches = $this->db->query($sql, $params)->getResultArray();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = min((int) $batch['remaining_qty'], $remaining);

            $this->db->table('batch')
                ->where('batch_id', $batch['batch_id'])
                ->set('remaining_qty', 'remaining_qty - ' . $take, false)
                ->update();

            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new DomainException('Insufficient batch stock.');
        }
    }

    private function dateTime(string $date): string
    {
        return date('Y-m-d H:i:s', strtotime($date . ' ' . date('H:i:s')));
    }

    private function resolveReferenceId(array $payload): int
    {
        $referenceId = (int) ($payload['reference_id'] ?? 0);
        if ($referenceId > 0) {
            return $referenceId;
        }

        $referenceText = trim((string) ($payload['reference'] ?? ''));
        if ($referenceText === '') {
            throw new DomainException('Reference is required.');
        }

        $userOfficeId = (int) ($payload['user_office_id'] ?? 0);

        $builder = $this->db->table('reference')
            ->select('reference_id')
            ->where('reference', $referenceText);

        if ($userOfficeId > 0) {
            $builder->where('user_office_id', $userOfficeId);
        }

        $existing = $builder->get(1)->getRowArray();

        if ($existing) {
            return (int) $existing['reference_id'];
        }

        $insertData = ['reference' => $referenceText];
        if ($userOfficeId > 0) {
            $insertData['user_office_id'] = $userOfficeId;
        }

        $this->db->table('reference')->insert($insertData);

        return (int) $this->db->insertID();
    }
}
