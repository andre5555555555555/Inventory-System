<?php

namespace App\Controllers;

use App\Services\BarcodeService;

class BarcodeController extends BaseController
{
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

    public function batch(int $batchId)
    {
        $db    = db_connect();
        $batch = $db->table('batch_table')
            ->select('batch_id, batch_no')
            ->where('batch_id', $batchId)
            ->get(1)
            ->getRowArray();

        if (! $batch) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $value = $batch['batch_no'] ?: 'B-' . str_pad((string) $batchId, 6, '0', STR_PAD_LEFT);

        return $this->response
            ->setHeader('Content-Type', 'image/svg+xml')
            ->setBody((new BarcodeService())->svg($value));
    }
}
