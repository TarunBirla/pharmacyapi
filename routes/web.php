<?php

use App\Http\Controllers\Web\AiAnalyticsWebController;
use App\Http\Controllers\Web\AuditWebController;
use App\Http\Controllers\Web\InventoryWebController;
use App\Http\Controllers\Web\PosWebController;
use App\Http\Controllers\Web\ProfileWebController;
use App\Http\Controllers\Web\ProcurementWebController;
use App\Http\Controllers\Web\ReportWebController;
use App\Http\Controllers\Web\SalesWebController;
use App\Http\Controllers\Web\WebAuthController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Native Blade White Theme ERP)
|--------------------------------------------------------------------------
*/

// Live Server Database Migration & Seeding Hook
Route::get('/setup-database', function () {
    set_time_limit(300);
    $isFresh = request()->query('fresh') === '1' || request()->query('fresh') === 'true';
    $outputLog = [];
    $hasError = false;

    try {
        DB::connection()->getPdo();
        $dbName = DB::connection()->getDatabaseName();
        $outputLog[] = " [DB CONNECT] Connected successfully to database: '{$dbName}'";
    } catch (\Throwable $e) {
        return response()->view('setup_result', [
            'success' => false,
            'message' => 'Database connection failed. Please check your live server .env credentials.',
            'error' => $e->getMessage(),
            'logs' => $outputLog,
        ], 500);
    }

    try {
        Artisan::call('optimize:clear');
        $outputLog[] = " [CACHE CLEAR] " . trim(Artisan::output());
    } catch (\Throwable $e) {
        $outputLog[] = " [WARN] Cache clear: " . $e->getMessage();
    }

    try {
        if ($isFresh) {
            $outputLog[] = " [MIGRATE FRESH] Dropping all tables and re-running all migrations...";
            Artisan::call('migrate:fresh', ['--force' => true]);
        } else {
            $outputLog[] = " [MIGRATE] Running pending database migrations...";
            Artisan::call('migrate', ['--force' => true]);
        }
        $outputLog[] = trim(Artisan::output());
    } catch (\Throwable $e) {
        $hasError = true;
        $outputLog[] = " [ERROR IN MIGRATION] " . $e->getMessage();
    }

    if (!$hasError) {
        try {
            $outputLog[] = " [SEEDING] Seeding default roles, demo pharmacy, catalog, suppliers, and accounts...";
            Artisan::call('db:seed', ['--force' => true]);
            $outputLog[] = trim(Artisan::output());
        } catch (\Throwable $e) {
            $hasError = true;
            $outputLog[] = " [ERROR IN SEEDING] " . $e->getMessage();
        }
    }

    $statusCode = $hasError ? 500 : 200;

    return response(view('setup_result', [
        'success' => !$hasError,
        'message' => $hasError 
            ? 'Setup encountered errors. Review the logs below.' 
            : 'Database created and seeded successfully!',
        'logs' => $outputLog,
        'isFresh' => $isFresh,
    ]), $statusCode);
});

// Public Landing Page
Route::get('/', function () {
    if (Illuminate\Support\Facades\Auth::check()) {
        return redirect()->route('pos.index');
    }
    return view('welcome');
})->name('landing');

// Authentication Routes
Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.post');
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

// Authenticated ERP Web Application
Route::middleware(['auth'])->group(function () {
    Route::post('/switch-branch', [WebAuthController::class, 'switchBranch'])->name('branch.switch');

    // POS Billing Counter
    Route::get('/pos', [PosWebController::class, 'index'])->name('pos.index');
    Route::post('/pos/checkout', [PosWebController::class, 'checkout'])->name('pos.checkout');
    Route::get('/pos/receipt/{invoice}', [PosWebController::class, 'receipt'])->name('pos.receipt');
    Route::post('/pos/hold', [PosWebController::class, 'holdBill'])->name('pos.hold');
    Route::post('/pos/resume/{code}', [PosWebController::class, 'resumeBill'])->name('pos.resume');
    Route::post('/pos/shifts/start', [PosWebController::class, 'startShift'])->name('pos.shifts.start');
    Route::post('/pos/shifts/close', [PosWebController::class, 'closeShift'])->name('pos.shifts.close');

    // Medicine Master & Batch Inventory
    Route::get('/inventory', [InventoryWebController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/products', [InventoryWebController::class, 'store'])->name('inventory.products.store');
    Route::get('/inventory/products/{product}/substitutes', [InventoryWebController::class, 'substitutes'])->name('inventory.products.substitutes');

    // Suppliers & Purchasing
    Route::get('/procurement', [ProcurementWebController::class, 'index'])->name('procurement.index');
    Route::post('/procurement/suppliers', [ProcurementWebController::class, 'storeSupplier'])->name('procurement.suppliers.store');
    Route::post('/procurement/invoices', [ProcurementWebController::class, 'storeInvoice'])->name('procurement.invoices.store');

    // Sales Records & Invoices
    Route::get('/sales', [SalesWebController::class, 'index'])->name('sales.index');
    Route::get('/sales/{invoice}', [SalesWebController::class, 'show'])->name('sales.show');
    Route::post('/sales/{invoice}/return', [SalesWebController::class, 'returnSale'])->name('sales.return');

    // GST & Compliance Reports
    Route::get('/reports/gst', [ReportWebController::class, 'gst'])->name('reports.gst');

    // Financial Statements (Trial Balance, P&L, Balance Sheet)
    Route::get('/reports/financials', [ReportWebController::class, 'financials'])->name('reports.financials');

    // AI Predictive Analytics
    Route::get('/ai', [AiAnalyticsWebController::class, 'index'])->name('ai.index');
    Route::post('/ai/refills/{id}/status', [AiAnalyticsWebController::class, 'updateRefillStatus'])->name('ai.refills.status');

    // System Security & Audit Trail
    Route::get('/audit', [AuditWebController::class, 'index'])->name('audit.index');

    // User Role Profile & PWA App
    Route::get('/profile', [ProfileWebController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileWebController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileWebController::class, 'updatePassword'])->name('profile.password');
});
