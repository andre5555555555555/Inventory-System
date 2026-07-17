<?php

namespace App\Controllers;

use App\Models\AdjustmentReasonModel;
use App\Models\OfficeModel;
use App\Models\ProductModel;
use App\Models\ReferenceModel;
use App\Models\TransactionModel;
use App\Services\InventoryService;
use DomainException;

class InventoryController extends BaseController
{
    private function userOfficeId(): int
    {
        return (int) (session('user')['user_office_id'] ?? 0);
    }

    private function userId(): int
    {
        return (int) (session('user')['id'] ?? 0);
    }

    public function stockcard()
    {
        $limit            = 10;
        $page             = max(1, (int) ($this->request->getGet('page') ?? 1));
        $productModel     = new ProductModel();
        $transactionModel = new TransactionModel();
        $userOfficeId     = $this->userOfficeId();

        $productId = (int) ($this->request->getGet('item_id') ?? 0);
        if ($productId === 0) {
            $productId = $productModel->firstProductId($userOfficeId);
        }

        $filterType = $this->request->getGet('filter_type') === 'oldest' ? 'oldest' : 'latest';
        $year       = (int) ($this->request->getGet('year') ?? 0);
        $month      = trim((string) ($this->request->getGet('month') ?? ''));
        $search     = trim((string) ($this->request->getGet('search') ?? ''));

        $itemInfo   = [];
        $stockcard  = [];
        $totalPages = 1;

        if ($productId > 0) {
            $itemInfo = $productModel->stockcardInfo($productId);
            $history  = $transactionModel->paginatedHistory($productId, $filterType, $page, $limit, $year, $month, $search, $userOfficeId);
            $stockcard   = $history['rows'];
            $totalPages  = max(1, (int) ceil($history['total'] / $limit));
        }

        return view('inventory/stockcard', [
            'itemId'        => $productId,
            'items'         => $productModel->listForSelect($userOfficeId),
            'itemInfo'      => $itemInfo,
            'stockcard'     => $stockcard,
            'search'        => $search,
            'filterType'    => $filterType,
            'selectedYear'  => $year,
            'selectedMonth' => $month,
            'page'          => $page,
            'totalPages'    => $totalPages,
        ]);
    }

    public function addStock()
    {
        $db               = db_connect();
        $service          = new InventoryService($db);
        $productModel     = new ProductModel();
        $officeModel      = new OfficeModel();
                $referenceModel   = new ReferenceModel();
        $reasonModel       = new AdjustmentReasonModel();
        $transactionModel = new TransactionModel();
        $userOfficeId     = $this->userOfficeId();
        $productId        = (int) ($this->request->getGet('item_id') ?? $this->request->getPost('item_id') ?? 0);

        if ($productId === 0) {
            $productId = $productModel->firstProductId($userOfficeId);
        }

        if ($this->request->getMethod() === 'POST') {
            try {
                $payload                   = $this->request->getPost();
                $payload['user_office_id'] = $userOfficeId;
                $payload['user_id']        = $this->userId();
                $service->saveStock($payload);
                return redirect()->to(site_url('stockcard?item_id=' . (int) $this->request->getPost('product_id')))
                    ->with('success', 'Stock transaction saved successfully.');
            } catch (DomainException $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }

        return view('inventory/stock_form', [
            'title'             => 'Add Stock',
            'itemId'            => $productId,
            'currentStock'      => $productId > 0 ? $transactionModel->currentStock($productId, $userOfficeId) : 0,
            'items'             => $productModel->listForSelect($userOfficeId),
            'offices'           => $officeModel->orderedList($userOfficeId),
            'references'        => $referenceModel->orderedList($userOfficeId),
            'transactionTypes'  => db_connect()->table('transaction_type_table')->orderBy('transaction_type_id', 'ASC')->get()->getResultArray(),
            'adjustmentReasons' => $reasonModel->orderedList(),
        ]);
    }

    public function adjustStock()
    {
        $db               = db_connect();
        $service          = new InventoryService($db);
        $productModel     = new ProductModel();
        $transactionModel = new TransactionModel();
        $userOfficeId     = $this->userOfficeId();
        $productId        = (int) ($this->request->getGet('item_id') ?? $this->request->getPost('item_id') ?? 0);

        if ($this->request->getMethod() === 'POST') {
            try {
                $payload                   = $this->request->getPost();
                $payload['user_office_id'] = $userOfficeId;
                $service->adjustStock($payload);
                return redirect()->to(site_url('stockcard?item_id=' . (int) $this->request->getPost('product_id')))
                    ->with('success', 'Adjustment saved successfully.');
            } catch (DomainException $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }

        return redirect()->to(site_url('stockcard?item_id=' . $productId));
    }

    /**
     * AJAX endpoint for inline row adjustment on the stockcard / report pages.
     * Expects POST: product_id, adjust_type (IN|OUT), quantity
     * Returns JSON: { ok: bool, new_stock: int, error?: string }
     */
    public function adjustStockInline()
    {
        $this->response->setContentType('application/json');
        $db           = db_connect();
        $service      = new InventoryService($db);
        $transModel   = new TransactionModel();
        $userOfficeId = $this->userOfficeId();

        try {
            $payload = [
                'product_id'   => $this->request->getPost('product_id'),
                'adjust_type'  => $this->request->getPost('adjust_type'),
                'quantity'     => $this->request->getPost('quantity'),
                'user_office_id' => $userOfficeId,
            ];
            $service->adjustStock($payload);
            $newStock = $transModel->currentStock((int) $payload['product_id'], $userOfficeId);
            return $this->response->setJSON(['ok' => true, 'new_stock' => $newStock]);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * AJAX — correct an existing transaction's qty and/or type.
     * POST: transaction_id, new_qty, new_type (optional: 1=receipt, 2=issue)
     */
    public function editTransaction()
    {
        $this->response->setContentType('application/json');
        $db           = db_connect();
        $transModel   = new TransactionModel();
        $userOfficeId = $this->userOfficeId();

        $transactionId = (int) $this->request->getPost('transaction_id');
        $newQty        = (int) $this->request->getPost('new_qty');
        $newTypeInput  = $this->request->getPost('new_type');
        $newTypeId     = $newTypeInput !== null ? (int) $newTypeInput : null;
        $newOffice     = trim((string) ($this->request->getPost('new_office') ?? ''));
        $newRef        = trim((string) ($this->request->getPost('new_reference') ?? ''));

        if ($transactionId <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'Invalid transaction.']);
        }
        if ($newQty <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'Quantity must be greater than 0.']);
        }

        $txn = $db->table('transaction_table')->where('transaction_id', $transactionId)->get(1)->getRowArray();
        if (! $txn) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'error' => 'Transaction not found.']);
        }

        $oldQty    = (int) $txn['transaction_qty'];
        $batchId   = (int) $txn['batch_id'];
        $oldTypeId = (int) $txn['transaction_type_id'];
        $finalType = $newTypeId ?? $oldTypeId;

        $batch = $db->table('batch_table')->where('batch_id', $batchId)->get(1)->getRowArray();
        if (! $batch) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'error' => 'Linked batch not found.']);
        }

        $currentBatchQty = (int) $batch['current_qty'];
        $oldIsReceipt    = $oldTypeId === 1;
        $newIsReceipt    = $finalType === 1;

        // Undo old transaction effect, then apply new
        // Undo: receipt gave +qty; issue gave -qty
        $undoneQty = $oldIsReceipt ? $currentBatchQty - $oldQty : $currentBatchQty + $oldQty;
        // Apply new qty+type
        $newBatchQty = $newIsReceipt ? $undoneQty + $newQty : $undoneQty - $newQty;

        if ($newBatchQty < 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok'    => false,
                'error' => 'Cannot apply this change — it would leave the batch at ' . $newBatchQty . '. Some stock may already be issued.',
            ]);
        }

        $db->transException(true)->transStart();

        $updateFields = [
            'transaction_qty' => $newQty,
            'updated_at'      => date('Y-m-d H:i:s'),
        ];
        if ($newOffice !== '') {
            $officeQuery = $db->table('office_table')->select('office_id')->where('office_name', $newOffice);
            if ($userOfficeId > 0) {
                $officeQuery->where('user_office_id', $userOfficeId);
            }
            $existingOffice = $officeQuery->get(1)->getRowArray();
            if ($existingOffice) {
                $updateFields['office_id'] = (int) $existingOffice['office_id'];
            } else {
                $officeInsert = ['office_name' => $newOffice];
                if ($userOfficeId > 0) {
                    $officeInsert['user_office_id'] = $userOfficeId;
                }
                $db->table('office_table')->insert($officeInsert);
                $updateFields['office_id'] = (int) $db->insertID();
            }
        } else {
            $updateFields['office_id'] = null;
        }

        if ($newRef !== '') {
            $refQuery = $db->table('reference_table')->select('reference_id')->where('reference', $newRef);
            if ($userOfficeId > 0) {
                $refQuery->where('user_office_id', $userOfficeId);
            }
            $existingRef = $refQuery->get(1)->getRowArray();
            if ($existingRef) {
                $updateFields['reference_id'] = (int) $existingRef['reference_id'];
            } else {
                $refInsert = ['reference' => $newRef];
                if ($userOfficeId > 0) {
                    $refInsert['user_office_id'] = $userOfficeId;
                }
                $db->table('reference_table')->insert($refInsert);
                $updateFields['reference_id'] = (int) $db->insertID();
            }
        } else {
            $updateFields['reference_id'] = null;
        }
        // Map display type (1=receipt, 2=issue) to transaction_type_id
        if ($newTypeId !== null && $newTypeId !== $oldTypeId) {
            $updateFields['transaction_type_id'] = $newTypeId;
        }

        $db->table('transaction_table')->where('transaction_id', $transactionId)->update($updateFields);
        $db->table('batch_table')->where('batch_id', $batchId)->update([
            'current_qty' => $newBatchQty,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        $db->transComplete();

        $newStock = $transModel->currentStock((int) $batch['product_id'], $userOfficeId);

        return $this->response->setJSON([
            'ok'       => true,
            'new_qty'  => $newQty,
            'new_type' => $finalType,
            'new_stock'=> $newStock,
        ]);
    }

    /**
     * AJAX — delete a transaction and reverse its effect on the batch.
     * POST: transaction_id
     */
    public function deleteTransaction()
    {
        $this->response->setContentType('application/json');
        $db           = db_connect();
        $transModel   = new TransactionModel();
        $userOfficeId = $this->userOfficeId();

        $transactionId = (int) $this->request->getPost('transaction_id');

        if ($transactionId <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'Invalid transaction.']);
        }

        $txn = $db->table('transaction_table')->where('transaction_id', $transactionId)->get(1)->getRowArray();
        if (! $txn) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'error' => 'Transaction not found.']);
        }

        $qty       = (int) $txn['transaction_qty'];
        $batchId   = (int) $txn['batch_id'];
        $typeId    = (int) $txn['transaction_type_id'];
        $isReceipt = $typeId === 1;

        $batch = $db->table('batch_table')->where('batch_id', $batchId)->get(1)->getRowArray();
        if (! $batch) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'error' => 'Linked batch not found.']);
        }

        $currentBatchQty = (int) $batch['current_qty'];

        // Reverse the transaction: receipt gave +qty → undo = -qty; issue gave -qty → undo = +qty
        $newBatchQty = $isReceipt ? $currentBatchQty - $qty : $currentBatchQty + $qty;

        if ($newBatchQty < 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok'    => false,
                'error' => 'Cannot delete — this receipt\'s stock has already been (partially) issued.',
            ]);
        }

        $db->transException(true)->transStart();

        $db->table('transaction_table')->where('transaction_id', $transactionId)->delete();
        $db->table('batch_table')->where('batch_id', $batchId)->update([
            'current_qty' => $newBatchQty,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        $db->transComplete();

        $newStock = $transModel->currentStock((int) $batch['product_id'], $userOfficeId);

        return $this->response->setJSON(['ok' => true, 'new_stock' => $newStock]);
    }

    /**
     * POST stock/edit-report-cost
     * Updates transaction_unit_cost for a product in a given month/year for a given type.
     */
    public function editReportCost(): \CodeIgniter\HTTP\ResponseInterface
    {
        $productId = (int) $this->request->getPost('product_id');
        $costType  = (string) ($this->request->getPost('cost_type') ?? '');
        $newCost   = (float) $this->request->getPost('new_cost');
        $year      = (int) $this->request->getPost('year');
        $month     = (int) $this->request->getPost('month');

        // Map cost_type → transaction_type_id
        $typeMap = ['purchase' => 1, 'used' => 2, 'spoiled' => 3];
        $typeId  = $typeMap[$costType] ?? null;

        if (! $typeId || $productId <= 0 || $newCost < 0 || $year <= 0 || $month <= 0) {
            return $this->response->setStatusCode(422)
                ->setJSON(['ok' => false, 'error' => 'Invalid input.']);
        }

        $monthStart = sprintf('%04d-%02d-01', $year, $month);
        $nextMonth  = date('Y-m-d', strtotime($monthStart . ' +1 month'));

        $db = \Config\Database::connect();
        $db->query(
            "UPDATE transaction_table t
             INNER JOIN batch_table b ON t.batch_id = b.batch_id
             SET t.transaction_unit_cost = ?,
                 t.updated_at = NOW()
             WHERE b.product_id = ?
               AND t.transaction_type_id = ?
               AND t.transaction_date >= ?
               AND t.transaction_date < ?",
            [$newCost, $productId, $typeId, $monthStart, $nextMonth]
        );

        return $this->response->setJSON(['ok' => true]);
    }
}














