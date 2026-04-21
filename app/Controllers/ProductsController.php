<?php

namespace App\Controllers;

use App\Models\EntityModel;
use App\Models\ItemCategoryModel;
use App\Models\ItemModel;
use App\Models\ItemTypeModel;
use App\Models\UnitModel;

class ProductsController extends BaseController
{
    private function userOfficeId(): int
    {
        return (int) (session('user')['user_office_id'] ?? 0);
    }

    public function index()
    {
        $search = trim((string) $this->request->getGet('search'));
        $itemModel = new ItemModel();
        $userOfficeId = $this->userOfficeId();

        return view('products/index', [
            'search'   => $search,
            'products' => $itemModel->searchProducts($search, $userOfficeId),
        ]);
    }

    public function create()
    {
        return $this->upsert();
    }

    public function edit(int $id)
    {
        return $this->upsert($id);
    }

    private function upsert(?int $id = null)
    {
        $itemModel = new ItemModel();
        $entityModel = new EntityModel();
        $unitModel = new UnitModel();
        $itemTypeModel = new ItemTypeModel();
        $itemCategoryModel = new ItemCategoryModel();
        $userOfficeId = $this->userOfficeId();

        $product = [
            'item_id'          => null,
            'item_no'          => '',
            'item'             => '',
            'description'      => '',
            're_order_point'   => 0,
            'entity_id'        => '',
            'unit_id'          => '',
            'item_type_id'     => '',
            'item_category_id' => '',
        ];

        if ($id !== null) {
            $product = $itemModel->findProduct($id) ?? $product;
            if (! $product['item_id']) {
                throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
            }
        }

        if ($this->request->getMethod() === 'POST') {
            $rules = [
                'item_no'          => 'required|integer|greater_than[0]',
                'item'             => 'required|min_length[2]|max_length[255]',
                'description'      => 'permit_empty|max_length[1000]',
                're_order_point'   => 'required|integer|greater_than_equal_to[0]',
                'entity_id'        => 'required|integer|greater_than[0]',
                'unit_id'          => 'required|integer',
                'item_type_id'     => 'required|integer',
                'item_category_id' => 'required|integer',
            ];

            if (! $this->validate($rules)) {
                return redirect()->back()->withInput()->with('error', 'Please correct the product form.');
            }

            $payload = [
                'item_no'          => (int) $this->request->getPost('item_no'),
                'item'             => trim((string) $this->request->getPost('item')),
                'description'      => trim((string) $this->request->getPost('description')),
                're_order_point'   => (int) $this->request->getPost('re_order_point'),
                'entity_id'        => (int) $this->request->getPost('entity_id'),
                'unit_id'          => (int) $this->request->getPost('unit_id'),
                'item_type_id'     => (int) $this->request->getPost('item_type_id'),
                'item_category_id' => (int) $this->request->getPost('item_category_id'),
                'user_office_id'   => $userOfficeId,
            ];

            if ($id === null) {
                $itemModel->insert($payload);
                return redirect()->to(site_url('products'))->with('success', 'Product added successfully.');
            }

            $itemModel->update($id, $payload);
            return redirect()->to(site_url('products'))->with('success', 'Product updated successfully.');
        }

        return view('products/form', [
            'title'          => $id === null ? 'Add Product' : 'Edit Product',
            'product'        => $product,
            'entities'       => $entityModel->orderedList($userOfficeId),
            'units'          => $unitModel->orderedList($userOfficeId),
            'itemTypes'      => $itemTypeModel->orderedList($userOfficeId),
            'itemCategories' => $itemCategoryModel->orderedList($userOfficeId),
        ]);
    }
}
