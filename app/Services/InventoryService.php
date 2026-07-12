<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

class InventoryService
{
    public function __construct(private readonly BaseConnection $db)
    {
    }

    public function currentReorderPoint(int $productId): int
    {
        $row = $this->db->query(
            'SELECT COALESCE(product_reorder_point, 0) AS product_reorder_point
             FROM product_table
             WHERE product_id = ?
             LIMIT 1',
            [$productId],
        )->getRowArray();

        return (int) ($row['product_reorder_point'] ?? 0);
    }

    public function currentEntityId(int $productId): ?int
    {
        $row = $this->db->query(
            'SELECT entity_id FROM product_table WHERE product_id = ? LIMIT 1',
            [$productId],
        )->getRowArray();

        if (! $row || $row['entity_id'] === null) {
            return null;
        }

        return (int) $row['entity_id'];
    }

    /**
     * Current stock for a product = SUM of batch_table.current_qty.
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
     * Save a stock-in or stock-out transaction.
     * Stock-in  → creates a new batch_table row + transaction_table (receipt).
     * Stock-out → depletes existing batches FIFO + transaction_table (issue).
     */
    public function saveStock(array $payload): void
    {
        $this->db->transException(true)->transStart();

        $productId    = (int) $payload['product_id'];
        $qty          = (int) $payload['quantity'];
        $type         = $payload['adjust_type']; // 'IN' or 'OUT'
        $unitCost     = (float) ($payload['unit_cost'] ?? 0);
        $officeId     = (int) $payload['office_id'];
        $referenceId  = $this->resolveReferenceId($payload);
        $entityId     = $this->currentEntityId($productId);
        $dateTime     = $this->dateTime($payload['date'] ?? date('Y-m-d'));
        $expDate      = ($payload['expiration_date'] ?? '') ?: null;
        $dateReceived = $payload['date'] ?? date('Y-m-d');
        $userOfficeId = (int) ($payload['user_office_id'] ?? 0);
        $userId       = (int) ($payload['user_id'] ?? 0);

        // Get user_office_name for stock_no generation
        $officeRow = $this->db->table('user_office_table')
            ->where('user_office_id', $userOfficeId)
            ->get(1)->getRowArray();
        $userOfficeName = $officeRow['user_office_name'] ?? '';

        if ($entityId === null || $entityId <= 0) {
            throw new DomainException('Set an entity in Product setup before saving stock.');
        }

        if ($qty <= 0) {
            throw new DomainException('Quantity must be greater than 0.');
        }

        $currentStock = $this->currentStock($productId, $userOfficeId);
        if ($type === 'OUT' && $qty > $currentStock) {
            throw new DomainException('Not enough stock.');
        }

        if ($type === 'IN' && $unitCost <= 0) {
            throw new DomainException('Unit cost is required for stock-in.');
        }

        if ($type === 'IN') {
            // Generate batch_no
            $batchNo = 'B-' . strtoupper($userOfficeName) . '-' . date('Ymd') . '-' . str_pad((string) $productId, 4, '0', STR_PAD_LEFT);

            // Create batch
            $this->db->table('batch_table')->insert([
                'batch_no'            => $batchNo,
                'product_id'          => $productId,
                'expiration_date'     => $expDate,
                'user_office_id'      => $userOfficeId,
                'reference_id'        => $referenceId,
                'office_id'           => $officeId,
                'current_qty'         => $qty,
                'date_received'       => $dateReceived,
                'created_at'          => $dateTime,
                'updated_at'          => $dateTime,
            ]);
            $batchId = (int) $this->db->insertID();

            // Ensure product has stock_no
            $product = $this->db->table('product_table')->where('product_id', $productId)->get(1)->getRowArray();
            if (($product['stock_no'] ?? '') === '' && $userOfficeName !== '') {
                $stockNo = strtoupper($userOfficeName) . '-' . str_pad((string) ($product['product_no'] ?? $productId), 4, '0', STR_PAD_LEFT);
                $this->db->table('product_table')->where('product_id', $productId)->update(['stock_no' => $stockNo]);
            }

            // Create transaction
            $this->db->table('transaction_table')->insert([
                'transaction_type_id'   => 1, // receipt
                'transaction_qty'       => $qty,
                'transaction_unit_cost' => $unitCost,
                'transaction_date'      => $dateTime,
                'batch_id'              => $batchId,
                'reference_id'          => $referenceId,
                'office_id'             => $officeId,
                'user_id'               => $userId ?: null,
                'user_office_id'        => $userOfficeId,
                'adjustment_reason_id'  => null,
                'created_at'            => $dateTime,
                'updated_at'            => $dateTime,
            ]);
        } else {
            // Stock-out: deplete batches FIFO and create issue transaction per batch
            $this->depleteBatches($productId, $qty, $userOfficeId, $officeId, $referenceId, $userId, $dateTime);
        }

        $this->db->transComplete();
    }

    /**
     * Save a stock adjustment — silently corrects batch_table.current_qty.
     * No transaction_table record is created; adjustments never appear in
     * the stockcard or reports.
     *
     * IN  → adds qty to the most recent non-zero batch, or creates a
     *        minimal correction batch if none exists.
     * OUT → removes qty from batches using FIFO depletion.
     */
    public function adjustStock(array $payload): void
    {
        $this->db->transException(true)->transStart();

        $productId    = (int) $payload['product_id'];
        $qty          = (int) $payload['quantity'];
        $type         = $payload['adjust_type'];
        $userOfficeId = (int) ($payload['user_office_id'] ?? 0);

        if ($productId <= 0) {
            throw new DomainException('Select a product first.');
        }

        if ($qty <= 0) {
            throw new DomainException('Quantity must be greater than 0.');
        }

        if ($type === 'OUT') {
            $currentStock = $this->currentStock($productId, $userOfficeId);
            if ($qty > $currentStock) {
                throw new DomainException('Not enough stock to subtract.');
            }

            // FIFO depletion — no transaction record
            $batches = $this->db->table('batch_table')
                ->where('product_id', $productId)
                ->where('current_qty >', 0)
                ->where('user_office_id', $userOfficeId)
                ->orderBy('date_received', 'ASC')
                ->orderBy('batch_id', 'ASC')
                ->get()
                ->getResultArray();

            $remaining = $qty;
            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }
                $take = min((int) $batch['current_qty'], $remaining);
                $this->db->table('batch_table')
                    ->where('batch_id', $batch['batch_id'])
                    ->update([
                        'current_qty' => (int) $batch['current_qty'] - $take,
                        'updated_at'  => date('Y-m-d H:i:s'),
                    ]);
                $remaining -= $take;
            }
        } else {
            // IN — add to most recent non-zero batch, or create a correction batch
            $latestBatch = $this->db->table('batch_table')
                ->where('product_id', $productId)
                ->where('user_office_id', $userOfficeId)
                ->where('current_qty >', 0)
                ->orderBy('date_received', 'DESC')
                ->orderBy('batch_id', 'DESC')
                ->get(1)
                ->getRowArray();

            if ($latestBatch) {
                $this->db->table('batch_table')
                    ->where('batch_id', $latestBatch['batch_id'])
                    ->update([
                        'current_qty' => (int) $latestBatch['current_qty'] + $qty,
                        'updated_at'  => date('Y-m-d H:i:s'),
                    ]);
            } else {
                // No existing batch — create a minimal correction entry
                $this->db->table('batch_table')->insert([
                    'batch_no'        => 'ADJ-' . date('YmdHis') . '-P' . $productId,
                    'product_id'      => $productId,
                    'user_office_id'  => $userOfficeId,
                    'current_qty'     => $qty,
                    'date_received'   => date('Y-m-d'),
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->db->transComplete();
    }

    /**
     * Deplete batches FIFO and create transaction records.
     */
    private function depleteBatches(
        int $productId,
        int $qty,
        int $userOfficeId,
        int $officeId,
        int $referenceId,
        int $userId,
        string $dateTime,
        int $reasonId = 0,
        int $transactionTypeId = 2
    ): void {
        $builder = $this->db->table('batch_table')
            ->where('product_id', $productId)
            ->where('current_qty >', 0);

        if ($userOfficeId > 0) {
            $builder->where('user_office_id', $userOfficeId);
        }

        $batches   = $builder->orderBy('date_received', 'ASC')->orderBy('batch_id', 'ASC')->get()->getResultArray();
        $remaining = $qty;

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = min((int) $batch['current_qty'], $remaining);

            $this->db->table('batch_table')
                ->where('batch_id', $batch['batch_id'])
                ->update([
                    'current_qty' => (int) $batch['current_qty'] - $take,
                    'updated_at'  => $dateTime,
                ]);

            $this->db->table('transaction_table')->insert([
                'transaction_type_id'   => $transactionTypeId,
                'transaction_qty'       => $take,
                'transaction_unit_cost' => 0,
                'transaction_date'      => $dateTime,
                'batch_id'              => (int) $batch['batch_id'],
                'reference_id'          => $referenceId ?: null,
                'office_id'             => $officeId ?: null,
                'user_id'               => $userId ?: null,
                'user_office_id'        => $userOfficeId,
                'adjustment_reason_id'  => $reasonId ?: null,
                'created_at'            => $dateTime,
                'updated_at'            => $dateTime,
            ]);

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

        $builder = $this->db->table('reference_table')
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

        $this->db->table('reference_table')->insert($insertData);

        return (int) $this->db->insertID();
    }
}
