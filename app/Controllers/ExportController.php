<?php

namespace App\Controllers;

use App\Models\ProductModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class ExportController extends BaseController
{
    private function userOfficeId(): int
    {
        return (int) (session('user')['user_office_id'] ?? 0);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /export/stockcard
    // ─────────────────────────────────────────────────────────────────────────

    public function stockcardForm()
    {
        $productModel = new ProductModel();
        return view('export/stockcard_form', [
            'products' => $productModel->listForSelect($this->userOfficeId()),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /export/stockcard
    // ─────────────────────────────────────────────────────────────────────────

    public function stockcardDownload()
    {
        $format    = trim((string) ($this->request->getPost('format')     ?? ''));
        $productId = (int)         ($this->request->getPost('product_id') ?? 0);
        $monthFrom = trim((string) ($this->request->getPost('month_from') ?? ''));
        $monthTo   = trim((string) ($this->request->getPost('month_to')   ?? ''));
        $sortOrder = $this->request->getPost('sort_order')  === 'DESC' ? 'DESC' : 'ASC';
        $paperSize = $this->request->getPost('paper_size');

        // Normalise paper size → dompdf paper name + CSS page size
        $paperMap  = [
            'a4'    => ['dompdf' => 'A4',     'css' => 'A4'],
            'long'  => ['dompdf' => 'folio',  'css' => '8.5in 13in'],   // Philippine Long Bond
            'short' => ['dompdf' => 'letter', 'css' => 'letter'],        // 8.5 × 11
        ];
        $paper = $paperMap[$paperSize] ?? $paperMap['a4'];

        if (! in_array($format, ['pdf', 'csv', 'word'], true)) {
            return redirect()->to(site_url('export/stockcard'))->with('error', 'Please select a download format.');
        }
        if ($monthFrom === '' || $monthTo === '') {
            return redirect()->to(site_url('export/stockcard'))->with('error', 'Please select a date range.');
        }

        $dateFrom = $monthFrom . '-01';
        $dateTo   = date('Y-m-d', strtotime($monthTo . '-01 +1 month'));

        if ($dateFrom > $dateTo) {
            return redirect()->to(site_url('export/stockcard'))->with('error', 'From Month must be before To Month.');
        }

        $products = $this->fetchExportData($dateFrom, $dateTo, $productId, $this->userOfficeId(), $sortOrder);
        $filename = 'Stockcard_' . $monthFrom . '_to_' . $monthTo;

        return match ($format) {
            'csv'  => $this->downloadCsv($products, $filename),
            'word' => $this->downloadWord($products, $filename, $paper),
            'pdf'  => $this->downloadPdf($products, $filename, $paper),
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Data: per-transaction rows grouped by product
    // ─────────────────────────────────────────────────────────────────────────

    private function fetchExportData(
        string $dateFrom,
        string $dateTo,
        int $productId,
        int $userOfficeId,
        string $sortOrder
    ): array {
        $db = db_connect();

        $builder = $db->table('transaction_table t')
            ->select([
                't.transaction_id',
                't.transaction_date',
                't.transaction_type_id',
                't.transaction_qty',
                'b.product_id',
                'p.product',
                'p.product_description',
                'p.stock_no',
                'p.product_reorder_point',
                'COALESCE(ut.unit, "pcs") AS unit',
                'COALESCE(r.reference, "") AS reference',
                'COALESCE(o.office_name, COALESCE(uot.user_office_name, "")) AS office',
                'COALESCE(e.entity, "") AS entity_name',
                'COALESCE(e.fund_cluster, "") AS fund_cluster',
            ])
            ->join('batch_table b',         't.batch_id = b.batch_id')
            ->join('product_table p',        'b.product_id = p.product_id')
            ->join('unit_table ut',          'p.unit_id = ut.unit_id',           'left')
            ->join('reference_table r',      't.reference_id = r.reference_id',  'left')
            ->join('office_table o',         't.office_id = o.office_id',         'left')
            ->join('user_office_table uot',  't.user_office_id = uot.user_office_id', 'left')
            ->join('entity_table e',         'p.entity_id = e.entity_id',         'left')
            ->where('t.transaction_date >=', $dateFrom)
            ->where('t.transaction_date <',  $dateTo)
            ->whereIn('t.transaction_type_id', [1, 2, 3]);

        if ($userOfficeId > 0) { $builder->where('t.user_office_id', $userOfficeId); }
        if ($productId    > 0) { $builder->where('b.product_id',     $productId); }

        $transactions = $builder
            ->orderBy('b.product_id',        'ASC')
            ->orderBy('t.transaction_date',   $sortOrder)
            ->orderBy('t.transaction_id',     $sortOrder)
            ->get()->getResultArray();

        // Opening balance per product (everything before dateFrom)
        $productIds      = array_unique(array_column($transactions, 'product_id'));
        $openingBalances = [];

        if (! empty($productIds)) {
            $ph  = implode(',', array_fill(0, count($productIds), '?'));
            $sql = 'SELECT b.product_id,
                           SUM(CASE WHEN t.transaction_type_id = 1     THEN t.transaction_qty ELSE 0 END) AS receipts,
                           SUM(CASE WHEN t.transaction_type_id IN (2,3) THEN t.transaction_qty ELSE 0 END) AS issues
                    FROM transaction_table t
                    INNER JOIN batch_table b ON t.batch_id = b.batch_id
                    WHERE t.transaction_date < ?
                      AND b.product_id IN (' . $ph . ')'
                . ($userOfficeId > 0 ? ' AND t.user_office_id = ' . (int) $userOfficeId : '')
                . ' GROUP BY b.product_id';

            foreach ($db->query($sql, array_merge([$dateFrom], $productIds))->getResultArray() as $row) {
                $openingBalances[(int) $row['product_id']] = (int) $row['receipts'] - (int) $row['issues'];
            }
        }

        $productMap = [];
        foreach ($transactions as $txn) {
            $pid = (int) $txn['product_id'];
            if (! isset($productMap[$pid])) {
                $productMap[$pid] = [
                    'product_id'    => $pid,
                    'product'       => $txn['product'],
                    'description'   => $txn['product_description'],
                    'stock_no'      => $txn['stock_no'],
                    'reorder_point' => $txn['product_reorder_point'],
                    'unit'          => $txn['unit'],
                    'entity_name'   => $txn['entity_name'],
                    'fund_cluster'  => $txn['fund_cluster'],
                    'balance'       => $openingBalances[$pid] ?? 0,
                    'rows'          => [],
                ];
            }

            $qty    = (int) $txn['transaction_qty'];
            $typeId = (int) $txn['transaction_type_id'];

            if ($typeId === 1) {
                $productMap[$pid]['balance'] += $qty;
                $receiptQty = $qty;
                $issueQty   = null;
            } else {
                $productMap[$pid]['balance'] -= $qty;
                $receiptQty = null;
                $issueQty   = $qty;
            }

            $productMap[$pid]['rows'][] = [
                'date'      => $txn['transaction_date'],
                'reference' => $txn['reference'],
                'receipt'   => $receiptQty,
                'issue'     => $issueQty,
                'office'    => $txn['office'],
                'balance'   => $productMap[$pid]['balance'],
            ];
        }

        return array_values($productMap);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CSV
    // ─────────────────────────────────────────────────────────────────────────

    private function downloadCsv(array $products, string $filename): \CodeIgniter\HTTP\Response
    {
        ob_start();
        $fp = fopen('php://output', 'w');
        fwrite($fp, "\xEF\xBB\xBF");

        foreach ($products as $p) {
            fputcsv($fp, ['STOCK CARD']);
            fputcsv($fp, ['Entity Name:', $p['entity_name'], 'Fund Cluster:', $p['fund_cluster']]);
            fputcsv($fp, ['Item:', $p['product'], 'Stock No.:', $p['stock_no']]);
            fputcsv($fp, ['Description:', $p['description'], 'Re-order Point:', $p['reorder_point']]);
            fputcsv($fp, ['Unit of Measurement:', $p['unit']]);
            fputcsv($fp, []);
            fputcsv($fp, ['Date', 'Reference', 'Receipt Qty', 'Issue Qty', 'Office', 'Balance Qty', 'No. of Days to Consume']);
            foreach ($p['rows'] as $row) {
                fputcsv($fp, [
                    $row['date'],
                    $row['reference'],
                    $row['receipt'] ?? '',
                    $row['issue']   ?? '',
                    $row['office'],
                    $row['balance'],
                    '',
                ]);
            }
            fputcsv($fp, []);
        }

        fclose($fp);
        $csv = ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '.csv"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($csv);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Word
    // ─────────────────────────────────────────────────────────────────────────

    private function downloadWord(array $products, string $filename, array $paper): \CodeIgniter\HTTP\Response
    {
        return $this->response
            ->setHeader('Content-Type', 'application/vnd.ms-word')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '.doc"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($this->buildStockcardHtml($products, $paper['css']));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PDF
    // ─────────────────────────────────────────────────────────────────────────

    private function downloadPdf(array $products, string $filename, array $paper): \CodeIgniter\HTTP\Response
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->buildStockcardHtml($products, $paper['css']), 'UTF-8');
        $dompdf->setPaper($paper['dompdf'], 'portrait');
        $dompdf->render();

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($dompdf->output());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HTML template
    // ─────────────────────────────────────────────────────────────────────────

    private function buildStockcardHtml(array $products, string $cssPageSize = 'A4'): string
    {
        $cards = '';

        foreach ($products as $idx => $p) {
            $entity   = htmlspecialchars($p['entity_name']  ?? '');
            $cluster  = htmlspecialchars($p['fund_cluster'] ?? '');
            $item     = htmlspecialchars($p['product']      ?? '');
            $stockNo  = htmlspecialchars($p['stock_no']     ?? '');
            $desc     = htmlspecialchars($p['description']  ?? '');
            $reorder  = htmlspecialchars((string) ($p['reorder_point'] ?? ''));
            $unit     = htmlspecialchars($p['unit']         ?? '');
            $pb       = $idx > 0 ? ' style="page-break-before:always;"' : '';

            $rows = '';
            foreach ($p['rows'] as $row) {
                $date    = $row['date']    ? date('m/d/Y', strtotime($row['date'])) : '';
                $ref     = htmlspecialchars((string) ($row['reference'] ?? ''));
                $receipt = $row['receipt'] !== null ? (int) $row['receipt'] : '';
                $issue   = $row['issue']   !== null ? (int) $row['issue']   : '';
                $office  = htmlspecialchars((string) ($row['office'] ?? ''));
                $balance = (int) $row['balance'];

                $rows .= '<tr>'
                    . "<td class=\"c-date\">{$date}</td>"
                    . "<td class=\"c-ref\">{$ref}</td>"
                    . "<td class=\"c-qty\">{$receipt}</td>"
                    . "<td class=\"c-qty\">{$issue}</td>"
                    . "<td class=\"c-off\">{$office}</td>"
                    . "<td class=\"c-qty\">{$balance}</td>"
                    . '<td class="c-days"></td>'
                    . '</tr>';
            }

            // Pad to 30 rows
            for ($i = count($p['rows']); $i < 30; $i++) {
                $rows .= '<tr><td class="c-date">&nbsp;</td><td class="c-ref"></td>'
                    . '<td class="c-qty"></td><td class="c-qty"></td>'
                    . '<td class="c-off"></td><td class="c-qty"></td>'
                    . '<td class="c-days"></td></tr>';
            }

            $cards .= <<<CARD
<div class="sc-page"{$pb}>

  <div class="sc-appendix">Appendix 58</div>
  <div class="sc-title">STOCK CARD</div>

  <table class="sc-entity-tbl">
    <tr>
      <td class="lbl">Entity Name :</td>
      <td class="entity-val">{$entity}</td>
      <td class="lbl fc-lbl">Fund Cluster :</td>
      <td class="fc-val">{$cluster}</td>
    </tr>
  </table>

  <!-- ONE unified table: info rows + header rows + data rows — columns always align -->
  <table class="sc-data">

    <!-- Info rows: left=cols1-5 (64%) | right=cols6-7 aligned with Balance -->
    <tr>
      <td class="i-left" colspan="5">Item : {$item}</td>
      <td class="i-right" colspan="2">Stock No. : <strong>{$stockNo}</strong></td>
    </tr>
    <tr>
      <td class="i-left" colspan="5">Description : {$desc}</td>
      <td class="i-right" colspan="2">Re-order Point : <strong>{$reorder}</strong></td>
    </tr>
    <tr>
      <td class="i-left" colspan="7">Unit of Measurement : {$unit}</td>
    </tr>

    <!-- ── Column headers ───────────────────────────────────────────── -->
    <tr>
      <th rowspan="2" style="width:15%">Date</th>
      <th rowspan="2" style="width:15%">Reference</th>
      <th style="width:9%">Receipt</th>
      <th colspan="2" style="width:25%">Issue</th>
      <th style="width:18%">Balance</th>
      <th rowspan="2" style="width:18%">No. of Days<br>to Consume</th>
    </tr>
    <tr>
      <th>Qty.</th>
      <th>Qty.</th>
      <th>Office</th>
      <th>Qty.</th>
    </tr>

    <!-- ── Data rows ─────────────────────────────────────────────────── -->
    {$rows}

  </table>

</div>
CARD;
        }

        if ($cards === '') {
            $cards = '<div class="sc-page"><p style="margin-top:40px;text-align:center;">No transactions found.</p></div>';
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Stock Card</title>
<style>
* { margin:0; padding:0; }

body {
    font-family: 'Times New Roman', Times, serif;
    font-size: 12pt;
    color: #000;
    background: #fff;
}

/* ── Page wrapper ──────────────────────────────────────────────────────
   NO width:100% here — block elements auto-fill their container.
   In dompdf's content-box model, padding then correctly SUBTRACTS
   from the available width rather than adding to it.
   This gives proper left/right margins without overflow.            */
.sc-page {
    padding: 8mm 16mm 6mm 16mm;
}

/* Appendix 58 — upper right */
.sc-appendix {
    text-align: right;
    font-style: italic;
    font-size: 10pt;
    margin-bottom: 1mm;
}

/* Title */
.sc-title {
    text-align: center;
    font-size: 15pt;
    font-weight: bold;
    text-decoration: underline;
    letter-spacing: 4px;
    margin-bottom: 5px;
}

/* Entity / Fund Cluster row */
.sc-entity-tbl {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 3px;
    font-size: 12pt;
}
.lbl        { white-space: nowrap; padding-right: 4px; }
.entity-val { font-weight: bold; text-decoration: underline; width: 55%; }
.fc-lbl     { padding-left: 10px; white-space: nowrap; }
.fc-val     { font-weight: bold; text-decoration: underline; width: 60px; text-align: center; }

/* Info rows — inside .sc-data; !important overrides td{text-align:center} */
.sc-data td.i-left  { text-align: left !important; padding: 3px 6px; font-size: 12pt; }
.sc-data td.i-rlbl  { text-align: left !important; padding: 3px 5px; font-size: 11pt; white-space: normal; vertical-align: middle; }
.sc-data td.i-rval  { text-align: center !important; padding: 3px 5px; font-size: 12pt; font-weight: bold; vertical-align: middle; }
.sc-data td.i-right { text-align: left !important; padding: 3px 6px; font-size: 12pt; }

/* Transaction data table */
.sc-data {
    width: 100%;
    border-collapse: collapse;
    margin-top: 5px;
    font-size: 11pt;
    table-layout: fixed;
}
.sc-data th, .sc-data td {
    border: 1px solid #000;
    padding: 2px 3px;
    text-align: center;
    height: 22px;
    overflow: hidden;
    word-break: break-word;
}
.sc-data thead th {
    font-weight: bold;
    font-size: 10pt;
    vertical-align: middle;
}

/* Cell alignment */
.c-date { text-align: left; }
.c-ref  { text-align: center; }
.c-qty  { text-align: right; padding-right: 4px; }
.c-off  { text-align: center; }
.c-days { text-align: center; }

@page { size: {$cssPageSize} portrait; margin: 8mm; }
</style>
</head>
<body>
{$cards}
</body>
</html>
HTML;
    }
}
