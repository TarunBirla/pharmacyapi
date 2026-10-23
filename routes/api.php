<?php

use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\OrganizationController;
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

        // Audit Logs
        Route::get('audit-logs', [AuditLogController::class, 'index'])
            ->middleware('permission:audit_logs.view');
    });
});
