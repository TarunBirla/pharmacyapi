<?php

use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DoctorController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\PosBillingController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PurchaseInvoiceController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\PurchaseReturnController;
use App\Http\Controllers\Api\V1\SalesInvoiceController;
use App\Http\Controllers\Api\V1\SalesReturnController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pharmacy ERP REST API v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Public authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
    });

    // Protected routes
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

        // Session & Profile
        Route::prefix('auth')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('switch-branch', [AuthController::class, 'switchBranch']);
        });

        // Organizations
        Route::apiResource('organizations', OrganizationController::class)->except(['destroy']);

        // Branches
        Route::apiResource('branches', BranchController::class)->except(['destroy']);

        // Warehouses
        Route::apiResource('warehouses', WarehouseController::class)->except(['destroy']);

        // Users & Roles
        Route::apiResource('users', UserController::class)->except(['destroy']);

        // Medicine Master / Products
        Route::get('products/{product}/substitutes', [ProductController::class, 'substitutes'])
            ->middleware('permission:products.view');
        Route::apiResource('products', ProductController::class)->except(['destroy']);

        // Batch Inventory & Stock Management
        Route::prefix('inventory')->group(function () {
            Route::get('batches', [InventoryController::class, 'batches'])
                ->middleware('permission:batches.view,inventory.view');
            Route::post('batches', [InventoryController::class, 'createBatch'])
                ->middleware('permission:inventory.adjust,purchase.create');
            Route::post('fefo-allocations', [InventoryController::class, 'fefoAllocations'])
                ->middleware('permission:sales.create,inventory.view');
            Route::post('adjustments', [InventoryController::class, 'adjustStock'])
                ->middleware('permission:inventory.adjust');
            Route::get('ledger', [InventoryController::class, 'stockLedger'])
                ->middleware('permission:inventory.view');
            Route::get('expiring', [InventoryController::class, 'expiring'])
                ->middleware('permission:batches.view,inventory.view');
            Route::get('low-stock', [InventoryController::class, 'lowStock'])
                ->middleware('permission:batches.view,inventory.view');
        });

        // Suppliers & Procurement
        Route::get('suppliers/{supplier}/ledger', [SupplierController::class, 'ledger'])
            ->middleware('permission:suppliers.manage,purchase.view');
        Route::post('suppliers/{supplier}/payments', [SupplierController::class, 'recordPayment'])
            ->middleware('permission:accounts.payment.record');
        Route::apiResource('suppliers', SupplierController::class)->except(['destroy']);

        // Purchase Orders
        Route::apiResource('purchase-orders', PurchaseOrderController::class)->except(['destroy']);

        // Purchase Invoices (Supplier Bills)
        Route::apiResource('purchase-invoices', PurchaseInvoiceController::class)->except(['destroy', 'update']);

        // Purchase Returns (Debit Notes)
        Route::apiResource('purchase-returns', PurchaseReturnController::class)->except(['destroy', 'update']);

        // Customers & Doctors
        Route::get('customers/{customer}/ledger', [CustomerController::class, 'ledger'])
            ->middleware('permission:customers.manage,sales.view');
        Route::apiResource('customers', CustomerController::class)->except(['destroy']);
        Route::apiResource('doctors', DoctorController::class)->except(['destroy']);

        // Retail POS & Fast Checkout
        Route::prefix('pos')->group(function () {
            Route::post('checkout', [PosBillingController::class, 'checkout'])
                ->middleware('permission:sales.create');
            Route::post('hold', [PosBillingController::class, 'holdBill'])
                ->middleware('permission:sales.create');
            Route::get('held-bills', [PosBillingController::class, 'heldBills'])
                ->middleware('permission:sales.create');
            Route::post('resume/{referenceCode}', [PosBillingController::class, 'resumeBill'])
                ->middleware('permission:sales.create');
            Route::post('shifts/start', [PosBillingController::class, 'startShift'])
                ->middleware('permission:sales.create');
            Route::post('shifts/close', [PosBillingController::class, 'closeShift'])
                ->middleware('permission:sales.create');
        });

        // Sales Invoices & Returns
        Route::apiResource('sales/invoices', SalesInvoiceController::class)->only(['index', 'show']);
        Route::apiResource('sales/returns', SalesReturnController::class)->only(['index', 'show', 'store']);

        // Double-Entry Accounting
        Route::get('accounts/{account}/statement', [\App\Http\Controllers\Api\V1\AccountController::class, 'statement'])
            ->middleware('permission:reports.profit.view,accounts.payment.record');
        Route::apiResource('accounts', \App\Http\Controllers\Api\V1\AccountController::class)->only(['index', 'store'])
            ->middleware('permission:reports.profit.view,accounts.payment.record');

        Route::apiResource('journal-entries', \App\Http\Controllers\Api\V1\JournalEntryController::class)->only(['index', 'store', 'show'])
            ->middleware('permission:reports.profit.view,accounts.payment.record');

        // Financial Reports
        Route::prefix('reports/financial')->middleware('permission:reports.profit.view')->group(function () {
            Route::get('trial-balance', [\App\Http\Controllers\Api\V1\FinancialReportController::class, 'trialBalance']);
            Route::get('profit-and-loss', [\App\Http\Controllers\Api\V1\FinancialReportController::class, 'profitAndLoss']);
            Route::get('balance-sheet', [\App\Http\Controllers\Api\V1\FinancialReportController::class, 'balanceSheet']);
        });

        // GST & Tax Compliance Reports
        Route::prefix('reports/gst')->middleware('permission:reports.profit.view,sales.view')->group(function () {
            Route::get('gstr1', [\App\Http\Controllers\Api\V1\GstReportController::class, 'gstr1']);
            Route::get('gstr3b', [\App\Http\Controllers\Api\V1\GstReportController::class, 'gstr3b']);
            Route::get('hsn-summary', [\App\Http\Controllers\Api\V1\GstReportController::class, 'hsnSummary']);
        });

        // AI Services & Business Analytics
        Route::prefix('ai')->middleware('permission:reports.profit.view,inventory.view')->group(function () {
            Route::get('demand-forecast', [\App\Http\Controllers\Api\V1\AiAnalyticsController::class, 'demandForecast']);
            Route::get('expiry-risk', [\App\Http\Controllers\Api\V1\AiAnalyticsController::class, 'expiryRisk']);
            Route::get('dead-stock', [\App\Http\Controllers\Api\V1\AiAnalyticsController::class, 'deadStock']);
            Route::post('ocr/parse-invoice', [\App\Http\Controllers\Api\V1\AiAnalyticsController::class, 'parseInvoiceOcr']);
            Route::get('patient-refills', [\App\Http\Controllers\Api\V1\AiAnalyticsController::class, 'patientRefills']);
            Route::post('patient-refills/{id}/status', [\App\Http\Controllers\Api\V1\AiAnalyticsController::class, 'updateRefillStatus']);
        });

        // Audit Logs
        Route::get('audit-logs', [AuditLogController::class, 'index'])
            ->middleware('permission:audit_logs.view');
    });
});
