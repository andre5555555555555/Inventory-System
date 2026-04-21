<?php

namespace App\Models;

use CodeIgniter\Model;

class StockoutModel extends Model
{
    protected $table = 'temp_stockout';
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
            ->select('tsi.*, item.item AS item_name')
            ->join('item', 'tsi.item_id = item.item_id', 'left')
            ->where('tsi.temp_stockout_id', $tempStockoutId)
            ->orderBy('tsi.temp_stockout_item_id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get items in a request, grouped/summed by item_id.
     */
    public function getItemsSummed(int $tempStockoutId): array
    {
        return $this->db->table('temp_stockout_item AS tsi')
            ->select('tsi.item_id, item.item AS item_name, tsi.unit, tsi.description,
                      SUM(tsi.quantity) AS quantity, MIN(tsi.status) AS status,
                      GROUP_CONCAT(tsi.temp_stockout_item_id) AS item_ids')
            ->join('item', 'tsi.item_id = item.item_id', 'left')
            ->where('tsi.temp_stockout_id', $tempStockoutId)
            ->groupBy('tsi.item_id, item.item, tsi.unit, tsi.description')
            ->orderBy('item.item', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Add an item to a draft stockout.
     */
    public function addItem(int $tempStockoutId, array $data): void
    {
        $this->db->table('temp_stockout_item')->insert([
            'temp_stockout_id' => $tempStockoutId,
            'item_id'          => (int) $data['item_id'],
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
     * Submit a draft for approval.
     * Merges duplicate item_id rows by summing their quantities.
     */
    public function submitForApproval(int $tempStockoutId): void
    {
        // ── Merge same-item duplicates ──
        $items = $this->db->table('temp_stockout_item')
            ->where('temp_stockout_id', $tempStockoutId)
            ->orderBy('temp_stockout_item_id', 'ASC')
            ->get()
            ->getResultArray();

        $grouped = [];
        foreach ($items as $item) {
            $key = (int) $item['item_id'];
            if (! isset($grouped[$key])) {
                $grouped[$key] = $item;
            } else {
                // Sum quantities
                $grouped[$key]['quantity'] = (int) $grouped[$key]['quantity'] + (int) $item['quantity'];
                // Keep latest description/unit if non-empty
                if (trim((string) $item['description']) !== '') {
                    $grouped[$key]['description'] = $item['description'];
                }
                if (trim((string) $item['unit']) !== '') {
                    $grouped[$key]['unit'] = $item['unit'];
                }
                // Delete the duplicate row
                $this->db->table('temp_stockout_item')
                    ->where('temp_stockout_item_id', $item['temp_stockout_item_id'])
                    ->delete();
            }
        }

        // Update the surviving rows with summed quantities
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
     * Get pending stockout requests for an office (Level 2/3 approval view).
     */
    public function pendingRequests(int $userOfficeId = 0, int $levelId = 2): array
    {
        $builder = $this->db->table('temp_stockout AS ts')
            ->select('ts.*, users.username AS requester_name,
                      COALESCE(user_office.user_office, "N/A") AS office_name')
            ->join('users', 'ts.user_id = users.user_id', 'left')
            ->join('user_office', 'ts.user_office_id = user_office.user_office_id', 'left')
            ->where('ts.status', 'pending')
            ->orderBy('ts.created_at', 'ASC');

        if ($levelId < 4 && $userOfficeId > 0) {
            $builder->where('ts.user_office_id', $userOfficeId);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Approve a single item and deduct stock, create transaction + stockcard.
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
        $this->deductStock((int) $item['item_id'], (int) $item['quantity']);

        // Create transaction + stockcard for batchlist report
        $this->createStockoutTransaction(
            (int) $item['item_id'],
            (int) $item['quantity'],
            $userOfficeId
        );

        // Mark item as approved
        $this->db->table('temp_stockout_item')
            ->where('temp_stockout_item_id', $tempStockoutItemId)
            ->update(['status' => 'approved']);

        // Update header: set approved_by and check if all items are done
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
            $this->deductStock((int) $item['item_id'], (int) $item['quantity']);

            // Create transaction + stockcard for batchlist report
            $this->createStockoutTransaction(
                (int) $item['item_id'],
                (int) $item['quantity'],
                $userOfficeId
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
     * Deduct stock from batches using FIFO.
     */
    private function deductStock(int $itemId, int $quantity): void
    {
        $batches = $this->db->table('batch')
            ->where('item_id', $itemId)
            ->where('remaining_qty >', 0)
            ->orderBy('created_at', 'ASC')
            ->orderBy('batch_id', 'ASC')
            ->get()
            ->getResultArray();

        $remaining = $quantity;

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $deduct = min($remaining, (int) $batch['remaining_qty']);
            $this->db->table('batch')
                ->where('batch_id', $batch['batch_id'])
                ->update(['remaining_qty' => (int) $batch['remaining_qty'] - $deduct]);

            $remaining -= $deduct;
        }
    }

    /**
     * Create a transaction + stockcard record so the stock-out appears in the batchlist report.
     */
    private function createStockoutTransaction(int $itemId, int $quantity, int $userOfficeId): void
    {
        // Create the transaction record (issue_qty = stock going out)
        $this->db->table('transaction')->insert([
            'date'                 => date('Y-m-d H:i:s'),
            'receipt_qty'          => 0,
            'issue_qty'            => $quantity,
            'day_consume'          => '',
            'expiration_date'      => null,
            'adjustment_reason_id' => null,
            'user_office_id'       => $userOfficeId,
        ]);

        $transactionId = (int) $this->db->insertID();

        // Create the stockcard record (links transaction to item)
        // reference_id and office_id left null — can be edited later
        $this->db->table('stockcard')->insert([
            'transaction_id'      => $transactionId,
            'reference_id'        => null,
            'office_id'           => null,
            'item_id'             => $itemId,
            'transaction_type_id' => 2, // issue
            'user_office_id'      => $userOfficeId,
        ]);
    }

    /**
     * Check if all items in a request are processed and finalize.
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
     * Get items available for stock-out (items with stock) for an office.
     */
    public function availableItems(int $userOfficeId = 0): array
    {
        $sql = 'SELECT item.item_id, item.item, item.description,
                       unit.unit AS unit_name,
                       COALESCE(SUM(batch.remaining_qty), 0) AS current_stock
                FROM item
                LEFT JOIN unit ON item.unit_id = unit.unit_id
                LEFT JOIN batch ON item.item_id = batch.item_id
                WHERE 1=1';

        $params = [];
        if ($userOfficeId > 0) {
            $sql .= ' AND item.user_office_id = ?';
            $params[] = $userOfficeId;
        }

        $sql .= ' GROUP BY item.item_id, item.item, item.description, unit.unit
                   ORDER BY item.item ASC';

        return $this->db->query($sql, $params)->getResultArray();
    }
}
