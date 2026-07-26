<?php

namespace App\Controllers;

use App\Models\StockoutModel;
use CodeIgniter\HTTP\ResponseInterface;

class StockoutController extends BaseController
{
    private function userOfficeId(): int
    {
        return (int) (session('user')['user_office_id'] ?? 0);
    }

    private function levelId(): int
    {
        return (int) (session('user')['level_id'] ?? 0);
    }

    private function userId(): int
    {
        return (int) (session('user')['id'] ?? 0);
    }

    /**
     * Level 1: Stock-out page — list products with stock.
     */
    public function index()
    {
        $model        = new StockoutModel();
        $userOfficeId = $this->userOfficeId();

        return view('stockout/index', [
            'items' => $model->availableItems($userOfficeId),
        ]);
    }

    /**
     * Level 1: View current temporary stock-out list.
     */
    public function tempList()
    {
        $model        = new StockoutModel();
        $userId       = $this->userId();
        $userOfficeId = $this->userOfficeId();

        $draft = $model->getOrCreateDraft($userId, $userOfficeId ?: null);
        $items = $model->getItems((int) $draft['temp_stockout_id']);

        return view('stockout/temp_list', [
            'draft' => $draft,
            'items' => $items,
        ]);
    }

    /**
     * Level 1: Add a product to the temp stock-out list.
     */
    public function addToTemp(): ResponseInterface
    {
        $model        = new StockoutModel();
        $userId       = $this->userId();
        $userOfficeId = $this->userOfficeId();

        $draft = $model->getOrCreateDraft($userId, $userOfficeId ?: null);

        $productId   = (int) $this->request->getPost('product_id');
        $quantity    = (int) $this->request->getPost('quantity');
        $unit        = trim((string) $this->request->getPost('unit'));
        $description = trim((string) $this->request->getPost('description'));

        if ($productId <= 0 || $quantity <= 0) {
            return redirect()->to(site_url('stockout'))->with('error', 'Please select a product and enter a valid quantity.');
        }

        $model->addItem((int) $draft['temp_stockout_id'], [
            'product_id'  => $productId,
            'quantity'    => $quantity,
            'unit'        => $unit,
            'description' => $description,
        ]);

        return redirect()->to(site_url('stockout/temp'))->with('success', 'Item added to your temporary stock-out list.');
    }

    /**
     * Level 1: Edit an item in the temp stock-out list.
     */
    public function editTemp(int $itemId)
    {
        $model       = new StockoutModel();
        $quantity    = (int) $this->request->getPost('quantity');
        $description = trim((string) $this->request->getPost('description'));
        $unit        = trim((string) $this->request->getPost('unit'));

        if ($quantity <= 0) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['message' => 'Quantity must be greater than 0.']);
            }
            return redirect()->to(site_url('stockout/temp'))->with('error', 'Quantity must be greater than 0.');
        }

        $model->updateItem($itemId, [
            'quantity'    => $quantity,
            'description' => $description,
            'unit'        => $unit,
        ]);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['message' => 'Item updated.']);
        }
        return redirect()->to(site_url('stockout/temp'))->with('success', 'Item updated.');
    }

    /**
     * Level 1: Remove an item from the temp stock-out list.
     */
    public function removeFromTemp(int $itemId)
    {
        $model = new StockoutModel();
        $model->removeItem($itemId);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['message' => 'Item removed.']);
        }
        return redirect()->to(site_url('stockout/temp'))->with('success', 'Item removed from list.');
    }

    /**
     * Level 1: Submit the temp list for approval.
     */
    public function submitForApproval()
    {
        $model        = new StockoutModel();
        $userId       = $this->userId();
        $userOfficeId = $this->userOfficeId();

        $draft = $model->getOrCreateDraft($userId, $userOfficeId ?: null);
        $items = $model->getItems((int) $draft['temp_stockout_id']);

        if (empty($items)) {
            return redirect()->to(site_url('stockout/temp'))->with('error', 'Cannot submit an empty list.');
        }

        $model->submitForApproval((int) $draft['temp_stockout_id']);
        return redirect()->to(site_url('stockout/temp'))->with('success', 'Stock-out request submitted for approval.');
    }

    /**
     * Level 2/3: View pending stock-out requests.
     */
    public function pendingRequests()
    {
        $levelId = $this->levelId();
        if ($levelId < 2) {
            return redirect()->to(site_url('/'));
        }

        $model    = new StockoutModel();
        $requests = $model->pendingRequests($this->userOfficeId(), $levelId);

        foreach ($requests as &$request) {
            $request['items'] = $model->getItemsSummed((int) $request['temp_stockout_id']);
        }

        return view('stockout/pending', [
            'requests' => $requests,
            'levelId'  => $levelId,
        ]);
    }

    /**
     * Level 2/3: Approve a single item.
     */
    public function approveItem(int $itemId): ResponseInterface
    {
        if ($this->levelId() < 2) {
            return $this->response->setStatusCode(403)->setJSON(['message' => 'Access denied.']);
        }

        $model  = new StockoutModel();
        $result = $model->approveItem($itemId, $this->userId());

        if ($result === 'insufficient_stock') {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Cannot approve: requested quantity exceeds available stock.',
            ]);
        }

        if (! $result) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Item could not be approved.']);
        }

        return $this->response->setJSON(['message' => 'Item approved and stock deducted.']);
    }

    /**
     * Level 2/3: Approve all items in a request.
     */
    public function approveAll(int $requestId): ResponseInterface
    {
        if ($this->levelId() < 2) {
            return $this->response->setStatusCode(403)->setJSON(['message' => 'Access denied.']);
        }

        $model  = new StockoutModel();
        $result = $model->approveAll($requestId, $this->userId());

        $approved = $result['approved'];
        $skipped  = $result['skipped'];

        if ($approved === 0 && $skipped > 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => "No items approved — all {$skipped} item(s) have insufficient stock.",
            ]);
        }

        $msg = "{$approved} item(s) approved and stock deducted.";
        if ($skipped > 0) {
            $msg .= " {$skipped} item(s) skipped (insufficient stock).";
        }

        return $this->response->setJSON(['message' => $msg]);
    }

    /**
     * Level 2/3: Reject a single item.
     */
    public function rejectItem(int $itemId): ResponseInterface
    {
        if ($this->levelId() < 2) {
            return $this->response->setStatusCode(403)->setJSON(['message' => 'Access denied.']);
        }

        $model = new StockoutModel();
        $model->rejectItem($itemId);

        return $this->response->setJSON(['message' => 'Item rejected.']);
    }

    /**
     * Level 2/3: Edit quantity of a pending item before approval.
     */
    public function editPendingItem(int $itemId): ResponseInterface
    {
        if ($this->levelId() < 2) {
            return $this->response->setStatusCode(403)->setJSON(['message' => 'Access denied.']);
        }

        $quantity = (int) $this->request->getPost('quantity');
        if ($quantity <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Quantity must be greater than 0.']);
        }

        $model = new StockoutModel();
        $model->updateItem($itemId, ['quantity' => $quantity]);

        return $this->response->setJSON(['message' => 'Quantity updated successfully.']);
    }
}
