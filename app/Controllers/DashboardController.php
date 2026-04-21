<?php

namespace App\Controllers;

use App\Models\DashboardModel;
use App\Models\SettingsModel;

class DashboardController extends BaseController
{
    public function __construct(
        private readonly DashboardModel $dashboardModel = new DashboardModel(),
    ) {
    }

    public function index()
    {
        $levelId = (int) (session('user')['level_id'] ?? 0);
        $userOfficeId = (int) (session('user')['user_office_id'] ?? 0);

        // Level 4 (Technical Staff): User management dashboard
        if ($levelId >= 4) {
            $settingsModel = new SettingsModel();
            return view('dashboard/admin', $settingsModel->indexData($userOfficeId, $levelId));
        }

        return view('dashboard/index', $this->dashboardModel->overview($userOfficeId));
    }
}
