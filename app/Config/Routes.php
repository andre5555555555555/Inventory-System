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

$routes->group('', ['filter' => 'auth'], static function ($routes): void {
    $routes->get('products', 'ProductsController::index');
    $routes->match(['get', 'post'], 'products/create', 'ProductsController::create');
    $routes->match(['get', 'post'], 'products/edit/(:num)', 'ProductsController::edit/$1');
    $routes->post('products/delete/(:num)', 'ProductsController::delete/$1');

    // ── Batch inventory list + barcode endpoints ──────────────────────────────
    $routes->get('batches', 'ReportsController::batches');
    $routes->get('barcode/product/(:num)', 'BarcodeController::product/$1');
    $routes->get('barcode/batch/(:num)', 'BarcodeController::batch/$1');
    $routes->get('barcode/lookup', 'BarcodeController::lookupByValue');

    $routes->get('stockcard', 'InventoryController::stockcard');
    $routes->match(['get', 'post'], 'stock/add', 'InventoryController::addStock');
    $routes->post('stock/edit-transaction', 'InventoryController::editTransaction');
    $routes->post('stock/delete-transaction', 'InventoryController::deleteTransaction');
    $routes->get('batchlist', 'ReportsController::batchlist');
    $routes->post('stock/edit-report-cost', 'InventoryController::editReportCost');

    // ── Stockcard export ──────────────────────────────────────────────────────
    $routes->get('export/stockcard',  'ExportController::stockcardForm');
    $routes->post('export/stockcard', 'ExportController::stockcardDownload');

    // ── Summary Report export ─────────────────────────────────────────────────
    $routes->get('export/summary',  'ExportController::summaryForm');
    $routes->post('export/summary', 'ExportController::summaryDownload');

    $routes->get('settings', 'SettingsController::index');
    $routes->get('settings/fetch/(:segment)/(:num)', 'SettingsController::fetch/$1/$2');
    $routes->post('settings/save/(:segment)', 'SettingsController::save/$1');
    $routes->post('settings/delete/(:segment)/(:num)', 'SettingsController::delete/$1/$2');
    $routes->post('settings/activate/(:num)', 'SettingsController::activate/$1');
    $routes->post('settings/deactivate/(:num)', 'SettingsController::deactivate/$1');

    // ── Stock-out system ──
    $routes->get('stockout', 'StockoutController::index');
    $routes->get('stockout/temp', 'StockoutController::tempList');
    $routes->post('stockout/add-temp', 'StockoutController::addToTemp');
    $routes->post('stockout/edit-temp/(:num)', 'StockoutController::editTemp/$1');
    $routes->post('stockout/remove-temp/(:num)', 'StockoutController::removeFromTemp/$1');
    $routes->post('stockout/submit', 'StockoutController::submitForApproval');
    $routes->get('stockout/pending', 'StockoutController::pendingRequests');
    $routes->post('stockout/approve-item/(:num)', 'StockoutController::approveItem/$1');
    $routes->post('stockout/approve-all/(:num)', 'StockoutController::approveAll/$1');
    $routes->post('stockout/reject-item/(:num)', 'StockoutController::rejectItem/$1');
    $routes->post('stockout/edit-pending/(:num)', 'StockoutController::editPendingItem/$1');
});
