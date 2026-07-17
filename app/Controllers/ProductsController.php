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

            // Ensure product_no is unique per office (excluding current product on edit)
            $inputProductNo = (int) $this->request->getPost('product_no');
            $duplicate = db_connect()->table('product_table')
                ->where('product_no', $inputProductNo)
                ->where('user_office_id', $userOfficeId);
            if ($id !== null) {
                $duplicate->where('product_id !=', $id);
            }
            if ($duplicate->countAllResults() > 0) {
                return redirect()->back()->withInput()
                    ->with('error', 'Product No ' . $inputProductNo . ' is already used by another product in your office.');
            }

            // Generate stock_no: user_office_name + product_no
            $officeRow   = db_connect()->table('user_office_table')->where('user_office_id', $userOfficeId)->get(1)->getRowArray();
            $officeName  = $officeRow['user_office_name'] ?? '';
            $stockNo     = $officeName !== '' ? strtoupper($officeName) . '-' . str_pad((string) $inputProductNo, 4, '0', STR_PAD_LEFT) : '';

            $entityName = trim((string) $this->request->getPost('entity_name'));
            $unitName   = trim((string) $this->request->getPost('unit_name'));
            $typeName   = trim((string) $this->request->getPost('type_name'));

            $entityId = $entityModel->firstOrCreate($entityName, $userOfficeId);
            $unitId   = $unitModel->firstOrCreate($unitName, $userOfficeId);
            $typeId   = $productTypeModel->firstOrCreate($typeName, $userOfficeId);

            $payload = [
                'product_no'            => $inputProductNo,
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

    public function delete(int $id)
    {
        if ((int) (session('user')['level_id'] ?? 0) < 2) {
            return $this->response->setStatusCode(403)->setJSON(['message' => 'Unauthorized']);
        }

        $productModel = new ProductModel();
        $userOfficeId = $this->userOfficeId();

        // Verify the product belongs to this office
        $product = $productModel->where('product_id', $id)
            ->where('user_office_id', $userOfficeId)
            ->first();

        if (! $product) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Product not found.']);
        }

        // Block deletion if the product still has stock in any batch
        $remainingStock = (int) db_connect()
            ->table('batch_table')
            ->selectSum('current_qty')
            ->where('product_id', $id)
            ->get()
            ->getRowArray()['current_qty'];

        if ($remainingStock > 0) {
            return $this->response->setStatusCode(409)->setJSON([
                'message' => "Cannot delete: this product still has {$remainingStock} unit(s) in stock. Deplete all stock before deleting.",
            ]);
        }

        try {
            $productModel->delete($id);
            return $this->response->setJSON(['message' => 'Product deleted successfully.']);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'foreign key') !== false || stripos($msg, 'constraint') !== false) {
                return $this->response->setStatusCode(409)
                    ->setJSON(['message' => 'Cannot delete: this product has existing transactions.']);
            }
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Delete failed.']);
        }
    }
}
