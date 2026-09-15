<?php

namespace App\Controllers;

use App\Services\BarcodeService;

class BarcodeController extends BaseController
{
    /**
     * Serve an inline Code 39 SVG for a product (used on stockcard / product pages).
     */
    public function product(int $productId)
    {
        $db      = db_connect();
        $product = $db->table('product_table')
            ->select('product_id, stock_no, product_no')
            ->where('product_id', $productId)
            ->get(1)
            ->getRowArray();

        if (! $product) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $value = $product['stock_no'] ?: 'P-' . str_pad((string) $productId, 6, '0', STR_PAD_LEFT);

        return $this->response
            ->setHeader('Content-Type', 'image/svg+xml')
            ->setBody((new BarcodeService())->svg($value));
    }

    /**
     * Serve an inline Code 39 SVG for a batch (fallback / direct embed).
     * Uses barcode_value if available, otherwise batch_no.
     */
    public function batch(int $batchId)
    {
        $db    = db_connect();
        $batch = $db->table('batch_table')
            ->select('batch_id, batch_no, barcode_value')
            ->where('batch_id', $batchId)
            ->get(1)
            ->getRowArray();

        if (! $batch) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Prefer the stored barcode_value; fall back to batch_no → generated id string
        $value = $batch['barcode_value']
            ?: ($batch['batch_no'] ?: 'B-' . str_pad((string) $batchId, 6, '0', STR_PAD_LEFT));

        return $this->response
            ->setHeader('Content-Type', 'image/svg+xml')
            ->setBody((new BarcodeService())->svg($value));
    }

    /**
     * AJAX — look up a batch by its barcode_value.
     * GET /barcode/lookup?value={scanned_string}
     * Returns JSON with product + batch info for the stock-out scanner.
     */
    public function lookupByValue()
    {
        $value = trim((string) ($this->request->getGet('value') ?? ''));

        if ($value === '') {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['error' => 'Barcode value is required.']);
        }

        $db    = db_connect();
        $userOfficeId = (int) (session('user')['user_office_id'] ?? 0);

        $builder = $db->table('batch_table b')
            ->select('
                b.batch_id,
                b.batch_no,
                b.barcode_value,
                b.current_qty,
                b.expiration_date,
                b.date_received,
                p.product_id,
                p.product,
                p.product_description,
                COALESCE(ut.unit, "pcs") AS unit_name
            ')
            ->join('product_table p', 'b.product_id = p.product_id')
            ->join('unit_table ut', 'p.unit_id = ut.unit_id', 'left')
            ->where('b.barcode_value', $value);

        if ($userOfficeId > 0) {
            $builder->where('b.user_office_id', $userOfficeId);
        }

        $batch = $builder->get(1)->getRowArray();

        if (! $batch) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON(['error' => 'No batch found for this barcode.']);
        }

        return $this->response->setJSON([
            'batch_id'        => (int) $batch['batch_id'],
            'batch_no'        => $batch['batch_no'],
            'barcode_value'   => $batch['barcode_value'],
            'product_id'      => (int) $batch['product_id'],
            'product'         => $batch['product'],
            'description'     => $batch['product_description'],
            'unit_name'       => $batch['unit_name'],
            'current_qty'     => (int) $batch['current_qty'],
            'expiration_date' => $batch['expiration_date'] ?? null,
            'date_received'   => $batch['date_received'] ?? null,
        ]);
    }

    /**
     * GET /products/barcodes
     * List all Finished Products for the current office with their saved barcodes.
     */
    public function finishedProducts()
    {
        $db           = db_connect();
        $userOfficeId = (int) (session('user')['user_office_id'] ?? 0);
        $search       = trim((string) ($this->request->getGet('search') ?? ''));

        $builder = $db->table('product_table p')
            ->select('
                p.product_id,
                p.product_no,
                p.stock_no,
                p.product,
                COALESCE(ut.unit, "pcs") AS unit_name,
                tp.type
            ')
            ->join('type_of_product tp', 'p.type_id = tp.type_id')
            ->join('unit_table ut', 'p.unit_id = ut.unit_id', 'left')
            ->where('tp.type', 'Finished Product');

        if ($userOfficeId > 0) {
            $builder->where('p.user_office_id', $userOfficeId);
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('p.product', $search)
                ->orLike('p.stock_no', $search)
                ->orLike('p.product_no', $search)
                ->groupEnd();
        }

        $products = $builder->orderBy('p.product_no', 'ASC')->get()->getResultArray();

        // Attach barcode web paths where the SVG file already exists
        foreach ($products as &$product) {
            $value   = $product['stock_no'] ?: 'P-' . str_pad((string) $product['product_id'], 6, '0', STR_PAD_LEFT);
            $safe    = preg_replace('/[^A-Za-z0-9\-_]/', '_', $value);
            $svgFile = FCPATH . 'barcodes' . DIRECTORY_SEPARATOR . $safe . '.svg';

            $product['barcode_value'] = $value;
            $product['barcode_url']   = file_exists($svgFile) ? base_url('barcodes/' . $safe . '.svg') : null;
        }
        unset($product);

        return view('products/barcodes', [
            'products' => $products,
            'search'   => $search,
        ]);
    }

    /**
     * POST /products/barcodes/generate
     * Generate & save a barcode SVG for a Finished Product.
     */
    public function generateFinishedProductBarcode()
    {
        $db           = db_connect();
        $userOfficeId = (int) (session('user')['user_office_id'] ?? 0);
        $productId    = (int) ($this->request->getPost('product_id') ?? 0);

        // Verify the product exists, belongs to this office, and is a Finished Product
        $product = $db->table('product_table p')
            ->select('p.product_id, p.stock_no, tp.type')
            ->join('type_of_product tp', 'p.type_id = tp.type_id')
            ->where('p.product_id', $productId)
            ->where('tp.type', 'Finished Product')
            ->get(1)
            ->getRowArray();

        if (! $product || ($userOfficeId > 0 && (int) $db->table('product_table')->where('product_id', $productId)->get(1)->getRowArray()['user_office_id'] !== $userOfficeId)) {
            return redirect()->to(site_url('products/barcodes'))->with('error', 'Product not found or access denied.');
        }

        $value = $product['stock_no'] ?: 'P-' . str_pad((string) $productId, 6, '0', STR_PAD_LEFT);

        (new \App\Services\BarcodeService())->saveBatchBarcode($value);

        return redirect()->to(site_url('products/barcodes'))->with('success', 'Barcode generated for product.');
    }
}
