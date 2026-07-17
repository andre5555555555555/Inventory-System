<?php

namespace App\Controllers;

use App\Models\EntityModel;
use App\Models\ProductModel;
use App\Models\ProductTypeModel;
use App\Models\UnitModel;

class ProductsController extends BaseController
{
    private function userOfficeId(): int
    {
        return (int) (session('user')['user_office_id'] ?? 0);
    }

    public function index()
    {
        $search       = trim((string) $this->request->getGet('search'));
        $productModel = new ProductModel();
        $userOfficeId = $this->userOfficeId();

        return view('products/index', [
            'search'   => $search,
            'products' => $productModel->searchProducts($search, $userOfficeId),
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
        $productModel    = new ProductModel();
        $entityModel     = new EntityModel();
        $unitModel       = new UnitModel();
        $productTypeModel = new ProductTypeModel();
        $userOfficeId    = $this->userOfficeId();

        $product = [
            'product_id'            => null,
            'product_no'            => '',
            'product'               => '',
            'product_description'   => '',
            'product_reorder_point' => 10,
            'entity_name'           => '',
            'unit_name'             => '',
            'type_name'             => '',
        ];

        if ($id !== null) {
            $product = $productModel->findProduct($id) ?? $product;
            if (! $product['product_id']) {
                throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
            }
        }

        if ($this->request->getMethod() === 'POST') {
            $rules = [
                'product_no'            => 'required|integer|greater_than[0]',
                'product'               => 'required|min_length[2]|max_length[255]',
                'product_description'   => 'permit_empty|max_length[1000]',
                'product_reorder_point' => 'required|integer|greater_than_equal_to[0]',
                'entity_name'           => 'required|max_length[255]',
                'unit_name'             => 'required|max_length[255]',
                'type_name'             => 'required|max_length[255]',
            ];

            if (! $this->validate($rules)) {
                return redirect()->back()->withInput()->with('error', 'Please correct the product form.');
            }

            $productNo = (int) $this->request->getPost('product_no');

            // Generate stock_no: user_office_name + product_no
            $officeRow   = db_connect()->table('user_office_table')->where('user_office_id', $userOfficeId)->get(1)->getRowArray();
            $officeName  = $officeRow['user_office_name'] ?? '';
            $stockNo     = $officeName !== '' ? strtoupper($officeName) . '-' . str_pad((string) $productNo, 4, '0', STR_PAD_LEFT) : '';

            $entityName = trim((string) $this->request->getPost('entity_name'));
            $unitName   = trim((string) $this->request->getPost('unit_name'));
            $typeName   = trim((string) $this->request->getPost('type_name'));

            $entityId = $entityModel->firstOrCreate($entityName, $userOfficeId);
            $unitId   = $unitModel->firstOrCreate($unitName, $userOfficeId);
            $typeId   = $productTypeModel->firstOrCreate($typeName, $userOfficeId);

            $payload = [
                'product_no'            => $productNo,
                'product'               => trim((string) $this->request->getPost('product')),
                'product_description'   => trim((string) $this->request->getPost('product_description')),
                'product_reorder_point' => (int) $this->request->getPost('product_reorder_point'),
                'entity_id'             => $entityId,
                'unit_id'               => $unitId,
                'type_id'               => $typeId,
                'user_office_id'        => $userOfficeId,
                'stock_no'              => $stockNo,
            ];

            if ($id === null) {
                $productModel->insert($payload);
                return redirect()->to(site_url('products'))->with('success', 'Product added successfully.');
            }

            $productModel->update($id, $payload);
            return redirect()->to(site_url('products'))->with('success', 'Product updated successfully.');
        }

        return view('products/form', [
            'title'        => $id === null ? 'Add Product' : 'Edit Product',
            'product'      => $product,
            'entities'     => $entityModel->orderedList($userOfficeId),
            'units'        => $unitModel->orderedList($userOfficeId),
            'productTypes' => $productTypeModel->orderedList($userOfficeId),
        ]);
    }
}
