<?php

namespace App\Controllers;

use App\Models\AdjustmentReasonModel;
use App\Models\ItemModel;
use App\Models\OfficeModel;
use App\Models\ReferenceModel;
use App\Models\StockcardModel;
use App\Services\InventoryService;
use DomainException;

class InventoryController extends BaseController
{
    private function userOfficeId(): int
    {
        return (int) (session('user')['user_office_id'] ?? 0);
    }

    public function stockcard()
    {
        $limit = 10;
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $itemModel = new ItemModel();
        $stockcardModel = new StockcardModel();
        $userOfficeId = $this->userOfficeId();

        $itemId = (int) ($this->request->getGet('item_id') ?? 0);
        if ($itemId === 0) {
            $itemId = $itemModel->firstItemId($userOfficeId);
        }

        $filterType = $this->request->getGet('filter_type') === 'oldest' ? 'oldest' : 'latest';
        $year = (int) ($this->request->getGet('year') ?? 0);
        $month = trim((string) ($this->request->getGet('month') ?? ''));
        $search = trim((string) ($this->request->getGet('search') ?? ''));

        $itemInfo = [];
        $stockcard = [];
        $totalPages = 1;

        if ($itemId > 0) {
            $itemInfo = $itemModel->stockcardInfo($itemId);
            $history = $stockcardModel->paginatedHistory($itemId, $filterType, $page, $limit, $year, $month, $search, $userOfficeId);
            $stockcard = $history['rows'];
            $totalPages = max(1, (int) ceil($history['total'] / $limit));
        }

        return view('inventory/stockcard', [
            'itemId'        => $itemId,
            'items'         => $itemModel->listForSelect($userOfficeId),
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
        $db = db_connect();
        $service = new InventoryService($db);
        $itemModel = new ItemModel();
        $officeModel = new OfficeModel();
        $referenceModel = new ReferenceModel();
        $stockcardModel = new StockcardModel();
        $userOfficeId = $this->userOfficeId();
        $itemId = (int) ($this->request->getGet('item_id') ?? $this->request->getPost('item_id') ?? 0);

        if ($itemId === 0) {
            $itemId = $itemModel->firstItemId($userOfficeId);
        }

        if ($this->request->getMethod() === 'POST') {
            try {
                $payload = $this->request->getPost();
                $payload['user_office_id'] = $userOfficeId;
                $service->saveStock($payload);
                return redirect()->to(site_url('stockcard?item_id=' . (int) $this->request->getPost('item_id')))
                    ->with('success', 'Stock transaction saved successfully.');
            } catch (DomainException $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }

        return view('inventory/stock_form', [
            'title'        => 'Add Stock',
            'itemId'       => $itemId,
            'currentStock' => $itemId > 0 ? $stockcardModel->currentStock($itemId, $userOfficeId) : 0,
            'items'        => $itemModel->listForSelect($userOfficeId),
            'offices'      => $officeModel->orderedList($userOfficeId),
            'references'   => $referenceModel->orderedList($userOfficeId),
        ]);
    }

    public function adjustStock()
    {
        $db = db_connect();
        $service = new InventoryService($db);
        $itemModel = new ItemModel();
        $officeModel = new OfficeModel();
        $referenceModel = new ReferenceModel();
        $reasonModel = new AdjustmentReasonModel();
        $stockcardModel = new StockcardModel();
        $userOfficeId = $this->userOfficeId();
        $itemId = (int) ($this->request->getGet('item_id') ?? $this->request->getPost('item_id') ?? 0);

        if ($this->request->getMethod() === 'POST') {
            try {
                $payload = $this->request->getPost();
                $payload['user_office_id'] = $userOfficeId;
                $service->adjustStock($payload);
                return redirect()->to(site_url('stockcard?item_id=' . (int) $this->request->getPost('item_id')))
                    ->with('success', 'Adjustment saved successfully.');
            } catch (DomainException $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }

        return view('inventory/adjust_form', [
            'title'        => 'Adjust Stock',
            'itemId'       => $itemId,
            'currentStock' => $itemId > 0 ? $stockcardModel->currentStock($itemId, $userOfficeId) : 0,
            'items'        => $itemModel->listForSelect($userOfficeId),
            'offices'      => $officeModel->orderedList($userOfficeId),
            'references'   => $referenceModel->orderedList($userOfficeId),
            'reasons'      => $reasonModel->orderedList(),
        ]);
    }
}
