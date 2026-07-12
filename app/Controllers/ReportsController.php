<?php

namespace App\Controllers;

use App\Models\ReportModel;

class ReportsController extends BaseController
{
    public function __construct(
        private readonly ReportModel $reportModel = new ReportModel(),
    ) {
    }

    public function batchlist()
    {
        $userOfficeId = (int) (session('user')['user_office_id'] ?? 0);
        $search       = trim((string) ($this->request->getGet('search') ?? ''));
        $year         = (int) ($this->request->getGet('year') ?? date('Y'));
        $month        = str_pad((string) ($this->request->getGet('month') ?? date('m')), 2, '0', STR_PAD_LEFT);
        $typeId       = (int) ($this->request->getGet('type_id') ?? 0);
        $report       = $this->reportModel->batchLedger($search, $year, $month, $typeId, $userOfficeId);

        return view('reports/batchlist', [
            ...$report,
            'search'       => $search,
            'year'         => $year,
            'month'        => $month,
            'typeId'       => $typeId,
            'productTypes' => $this->reportModel->orderedProductTypes($userOfficeId),
        ]);
    }
}
