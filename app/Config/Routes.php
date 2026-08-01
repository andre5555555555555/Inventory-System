<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'DashboardController::index', ['filter' => 'auth']);

$routes->get('login', 'AuthController::index');
$routes->post('login', 'AuthController::attempt');
$routes->get('register', 'AuthController::createAccount');
$routes->post('register', 'AuthController::register');
$routes->get('logout', 'AuthController::logout', ['filter' => 'auth']);
$routes->get('change-password', 'AuthController::changePasswordView', ['filter' => 'auth']);
$routes->post('change-password', 'AuthController::changePassword', ['filter' => 'auth']);

// ── Forgot / Reset password (public – no auth required) ──
$routes->get('forgot-password', 'AuthController::forgotPasswordView');
$routes->post('forgot-password', 'AuthController::forgotPassword');
$routes->get('verify-code', 'AuthController::verifyCodeView');
$routes->post('verify-code', 'AuthController::verifyCodeAndReset');

$routes->group('', ['filter' => 'auth'], static function ($routes): void {

    // ── SMTP setup (level 4 only, enforced in controller) ────────────────
    $routes->get('setup-smtp', 'AuthController::setupSmtpView');
    $routes->post('setup-smtp', 'AuthController::setupSmtp');

    // ── Dashboard ────────────────────────────────────────────────────────────
    // (already covered by the root '/' route above)

    // ── Products — level 1 can view list; level 2+ can mutate ─────────────
    $routes->get('products', 'ProductsController::index', ['filter' => 'level:1']);
    $routes->match(['get', 'post'], 'products/create', 'ProductsController::create', ['filter' => 'level:2']);
    $routes->match(['get', 'post'], 'products/edit/(:num)', 'ProductsController::edit/$1', ['filter' => 'level:2']);
    $routes->post('products/delete/(:num)', 'ProductsController::delete/$1', ['filter' => 'level:2']);

    // ── Batch inventory list + barcode — level 2+ only ────────────────────
    $routes->get('batches', 'ReportsController::batches', ['filter' => 'level:2']);
    $routes->get('barcode/product/(:num)', 'BarcodeController::product/$1', ['filter' => 'level:2']);
    $routes->get('barcode/batch/(:num)', 'BarcodeController::batch/$1', ['filter' => 'level:2']);
    $routes->get('barcode/lookup', 'BarcodeController::lookupByValue', ['filter' => 'level:2']);

    // ── Stockcard — level 2+ only ────────────────────────────────────────────
    $routes->get('stockcard', 'InventoryController::stockcard', ['filter' => 'level:2']);

    // ── Stock mutations — level 2+ ───────────────────────────────────────────
    $routes->match(['get', 'post'], 'stock/add', 'InventoryController::addStock', ['filter' => 'level:2']);
    $routes->post('stock/edit-transaction', 'InventoryController::editTransaction', ['filter' => 'level:2']);
    $routes->post('stock/delete-transaction', 'InventoryController::deleteTransaction', ['filter' => 'level:2']);
    $routes->post('stock/edit-report-cost', 'InventoryController::editReportCost', ['filter' => 'level:2']);
    $routes->get('batchlist', 'ReportsController::batchlist', ['filter' => 'level:2']);

    // ── Exports — level 2+ ───────────────────────────────────────────────────
    $routes->get('export/stockcard',  'ExportController::stockcardForm', ['filter' => 'level:2']);
    $routes->post('export/stockcard', 'ExportController::stockcardDownload', ['filter' => 'level:2']);
    $routes->get('export/summary',    'ExportController::summaryForm', ['filter' => 'level:2']);
    $routes->post('export/summary',   'ExportController::summaryDownload', ['filter' => 'level:2']);

    // ── Settings — level 2+ ──────────────────────────────────────────────────
    $routes->get('settings', 'SettingsController::index', ['filter' => 'level:2']);
    $routes->get('settings/fetch/(:segment)/(:num)', 'SettingsController::fetch/$1/$2', ['filter' => 'level:2']);
    $routes->post('settings/save/(:segment)', 'SettingsController::save/$1', ['filter' => 'level:2']);
    $routes->post('settings/delete/(:segment)/(:num)', 'SettingsController::delete/$1/$2', ['filter' => 'level:2']);
    $routes->post('settings/activate/(:num)', 'SettingsController::activate/$1', ['filter' => 'level:3']);
    $routes->post('settings/deactivate/(:num)', 'SettingsController::deactivate/$1', ['filter' => 'level:3']);

    // ── Backup system — level 2+ ─────────────────────────────────────────────
    $routes->get('settings/backup/list',            'BackupController::index',       ['filter' => 'level:2']);
    $routes->post('settings/backup/run',            'BackupController::run',         ['filter' => 'level:2']);
    $routes->post('settings/backup/auto',           'BackupController::autoBackup',  ['filter' => 'level:2']);
    $routes->get('settings/backup/download/(:num)', 'BackupController::download/$1', ['filter' => 'level:2']);
    $routes->post('settings/backup/restore',        'BackupController::restore',     ['filter' => 'level:2']);
    $routes->post('settings/backup/config',         'BackupController::saveConfig',  ['filter' => 'level:3']);

    // ── Stock-out — staff (level 1) can submit; level 2+ can approve ────────
    $routes->get('stockout', 'StockoutController::index', ['filter' => 'level:1']);
    $routes->get('stockout/temp', 'StockoutController::tempList', ['filter' => 'level:1']);
    $routes->post('stockout/add-temp', 'StockoutController::addToTemp', ['filter' => 'level:1']);
    $routes->post('stockout/edit-temp/(:num)', 'StockoutController::editTemp/$1', ['filter' => 'level:1']);
    $routes->post('stockout/remove-temp/(:num)', 'StockoutController::removeFromTemp/$1', ['filter' => 'level:1']);
    $routes->post('stockout/submit', 'StockoutController::submitForApproval', ['filter' => 'level:1']);
    // Approval & management — level 2+ only
    $routes->get('stockout/pending', 'StockoutController::pendingRequests', ['filter' => 'level:2']);
    $routes->post('stockout/approve-item/(:num)', 'StockoutController::approveItem/$1', ['filter' => 'level:2']);
    $routes->post('stockout/approve-all/(:num)', 'StockoutController::approveAll/$1', ['filter' => 'level:2']);
    $routes->post('stockout/reject-item/(:num)', 'StockoutController::rejectItem/$1', ['filter' => 'level:2']);
    $routes->post('stockout/edit-pending/(:num)', 'StockoutController::editPendingItem/$1', ['filter' => 'level:2']);
});
