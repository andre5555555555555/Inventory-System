<?php

namespace App\Models;

use CodeIgniter\Model;

class StockoutModel extends Model
{
    protected $table      = 'temp_stockout';
    protected $primaryKey = 'temp_stockout_id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'user_id', 'status', 'created_at',
        'approved_by', 'approved_at', 'user_office_id',
    ];

    /**
     * Get or create the current draft stockout request for a user.
     */
    public function getOrCreateDraft(int $userId, ?int $userOfficeId): array
    {
        $draft = $this->where('user_id', $userId)
            ->where('status', 'draft')
            ->first();

        if ($draft) {
            return $draft;
        }

        $this->insert([
            'user_id'        => $userId,
            'status'         => 'draft',
            'created_at'     => date('Y-m-d H:i:s'),
            'user_office_id' => $userOfficeId,
        ]);

        return $this->find($this->getInsertID());
    }

    /**
     * Get items in a temp stockout request.
     */
    public function getItems(int $tempStockoutId): array
    {
        return $this->db->table('temp_stockout_item AS tsi')
            ->select('tsi.*, p.product AS item_name')
            ->join('product_table p', 'tsi.product_id = p.product_id', 'left')
            ->where('tsi.temp_stockout_id', $tempStockoutId)
            ->orderBy('tsi.temp_stockout_item_id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get items in a request, grouped/summed by product_id.
     */
    public function getItemsSummed(int $tempStockoutId): array
    {
        return $this->db->table('temp_stockout_item AS tsi')
            ->select('tsi.product_id, p.product AS item_name, tsi.unit, tsi.description,
                      SUM(tsi.quantity) AS quantity, MIN(tsi.status) AS status,
                      GROUP_CONCAT(tsi.temp_stockout_item_id) AS item_ids,
                      COALESCE((
                          SELECT SUM(b.current_qty)
                          FROM batch_table b
                          WHERE b.product_id = tsi.product_id
                      ), 0) AS current_stock')
            ->join('product_table p', 'tsi.product_id = p.product_id', 'left')
            ->where('tsi.temp_stockout_id', $tempStockoutId)
            ->groupBy('tsi.product_id, p.product, tsi.unit, tsi.description')
            ->orderBy('p.product', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Add a product to a draft stockout.
     */
    public function addItem(int $tempStockoutId, array $data): void
    {
        $this->db->table('temp_stockout_item')->insert([
            'temp_stockout_id' => $tempStockoutId,
            'product_id'       => (int) $data['product_id'],
            'quantity'         => (int) $data['quantity'],
            'unit'             => trim((string) ($data['unit'] ?? '')),
            'description'      => trim((string) ($data['description'] ?? '')),
            'status'           => 'pending',
        ]);
    }

    /**
     * Update an item in a draft stockout.
     */
    public function updateItem(int $itemId, array $data): void
    {
        $update = [];
        if (isset($data['quantity'])) {
            $update['quantity'] = (int) $data['quantity'];
        }
        if (isset($data['description'])) {
            $update['description'] = trim((string) $data['description']);
        }
        if (isset($data['unit'])) {
            $update['unit'] = trim((string) $data['unit']);
        }

        if ($update) {
            $this->db->table('temp_stockout_item')
                ->where('temp_stockout_item_id', $itemId)
                ->update($update);
        }
    }

    /**
     * Remove an item from a draft stockout.
     */
    public function removeItem(int $itemId): void
    {
        $this->db->table('temp_stockout_item')
            ->where('temp_stockout_item_id', $itemId)
            ->delete();
    }

    /**
     * Submit a draft for approval — merges duplicate product_id rows.
     */
    public function submitForApproval(int $tempStockoutId): void
    {
        $items = $this->db->table('temp_stockout_item')
            ->where('temp_stockout_id', $tempStockoutId)
            ->orderBy('temp_stockout_item_id', 'ASC')
            ->get()
            ->getResultArray();

        $grouped = [];
        foreach ($items as $item) {
            $key = (int) $item['product_id'];
            if (! isset($grouped[$key])) {
                $grouped[$key] = $item;
            } else {
                $grouped[$key]['quantity'] = (int) $grouped[$key]['quantity'] + (int) $item['quantity'];
                if (trim((string) $item['description']) !== '') {
                    $grouped[$key]['description'] = $item['description'];
                }
                if (trim((string) $item['unit']) !== '') {
                    $grouped[$key]['unit'] = $item['unit'];
                }
                $this->db->table('temp_stockout_item')
                    ->where('temp_stockout_item_id', $item['temp_stockout_item_id'])
                    ->delete();
            }
        }

        foreach ($grouped as $item) {
            $this->db->table('temp_stockout_item')
                ->where('temp_stockout_item_id', $item['temp_stockout_item_id'])
                ->update([
                    'quantity'    => (int) $item['quantity'],
                    'description' => $item['description'],
                    'unit'        => $item['unit'],
                ]);
        }

        $this->update($tempStockoutId, ['status' => 'pending']);
    }

    /**
     * Get pending stockout requests.
     */
    public function pendingRequests(int $userOfficeId = 0, int $levelId = 2): array
    {
        $builder = $this->db->table('temp_stockout AS ts')
            ->select('ts.*, u.username AS requester_name,
                      COALESCE(uot.user_office_name, "N/A") AS office_name')
            ->join('user_table u', 'ts.user_id = u.user_id', 'left')
            ->join('user_office_table uot', 'ts.user_office_id = uot.user_office_id', 'left')
            ->where('ts.status', 'pending')
            ->orderBy('ts.created_at', 'ASC');

        if ($levelId < 4 && $userOfficeId > 0) {
            $builder->where('ts.user_office_id', $userOfficeId);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Approve a single item and deduct from batch_table.current_qty.
     */
    public function approveItem(int $tempStockoutItemId, int $approvedByUserId): bool
    {
        $item = $this->db->table('temp_stockout_item')
            ->where('temp_stockout_item_id', $tempStockoutItemId)
            ->get()
            ->getRowArray();

        if (! $item || $item['status'] !== 'pending') {
            return false;
        }

        $header = $this->find((int) $item['temp_stockout_id']);
        if (! $header) {
            return false;
        }

        $userOfficeId = (int) ($header['user_office_id'] ?? 0);

        // Deduct stock from batches (FIFO)
        $this->deductStock((int) $item['product_id'], (int) $item['quantity'], $userOfficeId);

        // Create transaction record
        $this->createStockoutTransaction(
            (int) $item['product_id'],
            (int) $item['quantity'],
            $userOfficeId,
            $approvedByUserId
        );

        // Mark item approved
        $this->db->table('temp_stockout_item')
            ->where('temp_stockout_item_id', $tempStockoutItemId)
            ->update(['status' => 'approved']);

        $this->update($header['temp_stockout_id'], [
            'approved_by' => $approvedByUserId,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);
        $this->checkAndFinalizeRequest((int) $item['temp_stockout_id']);

        return true;
    }

    /**
     * Approve all pending items in a request.
     */
    public function approveAll(int $tempStockoutId, int $approvedByUserId): bool
    {
        $header = $this->find($tempStockoutId);
        if (! $header) {
            return false;
        }

        $userOfficeId = (int) ($header['user_office_id'] ?? 0);

        $items = $this->db->table('temp_stockout_item')
            ->where('temp_stockout_id', $tempStockoutId)
            ->where('status', 'pending')
            ->get()
            ->getResultArray();

        foreach ($items as $item) {
            $this->deductStock((int) $item['product_id'], (int) $item['quantity'], $userOfficeId);
            $this->createStockoutTransaction(
                (int) $item['product_id'],
                (int) $item['quantity'],
                $userOfficeId,
                $approvedByUserId
            );
            $this->db->table('temp_stockout_item')
                ->where('temp_stockout_item_id', $item['temp_stockout_item_id'])
                ->update(['status' => 'approved']);
        }

        $this->update($tempStockoutId, [
            'status'      => 'approved',
            'approved_by' => $approvedByUserId,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /**
     * Reject a single item.
     */
    public function rejectItem(int $tempStockoutItemId): bool
    {
        $item = $this->db->table('temp_stockout_item')
            ->where('temp_stockout_item_id', $tempStockoutItemId)
            ->get()
            ->getRowArray();

        if (! $item || $item['status'] !== 'pending') {
            return false;
        }

        $this->db->table('temp_stockout_item')
            ->where('temp_stockout_item_id', $tempStockoutItemId)
            ->update(['status' => 'rejected']);

        $this->checkAndFinalizeRequest((int) $item['temp_stockout_id']);

        return true;
    }

    /**
     * Deduct stock from batches using FIFO (oldest first by date_received).
     */
    private function deductStock(int $productId, int $quantity, int $userOfficeId = 0): void
    {
        $builder = $this->db->table('batch_table')
            ->where('product_id', $productId)
            ->where('current_qty >', 0);

        if ($userOfficeId > 0) {
            $builder->where('user_office_id', $userOfficeId);
        }

        $batches = $builder->orderBy('date_received', 'ASC')
            ->orderBy('batch_id', 'ASC')
            ->get()
            ->getResultArray();

        $remaining = $quantity;

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }
            $deduct = min($remaining, (int) $batch['current_qty']);
            $this->db->table('batch_table')
                ->where('batch_id', $batch['batch_id'])
                ->update([
                    'current_qty' => (int) $batch['current_qty'] - $deduct,
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
            $remaining -= $deduct;
        }
    }

    /**
     * Create a transaction record for a stock-out approval.
     */
    private function createStockoutTransaction(int $productId, int $quantity, int $userOfficeId, int $userId): void
    {
        // Get the oldest batch with stock for this product
        $batch = $this->db->table('batch_table')
            ->where('product_id', $productId)
            ->where('user_office_id', $userOfficeId)
            ->orderBy('date_received', 'ASC')
            ->orderBy('batch_id', 'ASC')
            ->get(1)
            ->getRowArray();

        $batchId = $batch ? (int) $batch['batch_id'] : null;

        $this->db->table('transaction_table')->insert([
            'transaction_type_id'   => 2, // issue
            'transaction_qty'       => $quantity,
            'transaction_unit_cost' => 0,
            'transaction_date'      => date('Y-m-d H:i:s'),
            'batch_id'              => $batchId,
            'reference_id'          => null,
            'office_id'             => null,
            'user_id'               => $userId,
            'user_office_id'        => $userOfficeId,
            'adjustment_reason_id'  => null,
            'created_at'            => date('Y-m-d H:i:s'),
            'updated_at'            => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Check if all items processed and finalize the request.
     */
    private function checkAndFinalizeRequest(int $tempStockoutId): void
    {
        $pendingCount = (int) $this->db->table('temp_stockout_item')
            ->where('temp_stockout_id', $tempStockoutId)
            ->where('status', 'pending')
            ->countAllResults();

        if ($pendingCount === 0) {
            $approvedCount = (int) $this->db->table('temp_stockout_item')
                ->where('temp_stockout_id', $tempStockoutId)
                ->where('status', 'approved')
                ->countAllResults();

            $status = $approvedCount > 0 ? 'approved' : 'rejected';
            $this->update($tempStockoutId, ['status' => $status]);
        }
    }

    /**
     * Get available products for stock-out (products with stock) for an office.
     */
    public function availableItems(int $userOfficeId = 0): array
    {
        $params = [];

        if ($userOfficeId > 0) {
            $sql = 'SELECT p.product_id, p.product, p.product_description AS description,
                           ut.unit AS unit_name,
                           COALESCE(SUM(b.current_qty), 0) AS current_stock
                    FROM product_table p
                    LEFT JOIN unit_table ut ON p.unit_id = ut.unit_id
                    LEFT JOIN batch_table b ON p.product_id = b.product_id
                                           AND b.user_office_id = ?
                    WHERE p.user_office_id = ?
                    GROUP BY p.product_id, p.product, p.product_description, ut.unit
                    ORDER BY p.product ASC';
            $params = [$userOfficeId, $userOfficeId];
        } else {
            $sql = 'SELECT p.product_id, p.product, p.product_description AS description,
                           ut.unit AS unit_name,
                           COALESCE(SUM(b.current_qty), 0) AS current_stock
                    FROM product_table p
                    LEFT JOIN unit_table ut ON p.unit_id = ut.unit_id
                    LEFT JOIN batch_table b ON p.product_id = b.product_id
                    GROUP BY p.product_id, p.product, p.product_description, ut.unit
                    ORDER BY p.product ASC';
        }

        return $this->db->query($sql, $params)->getResultArray();
    }
}
