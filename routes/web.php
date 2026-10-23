<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return response()->json([
        'status' => 'online',
        'application' => 'Advanced Pharmacy ERP API',
        'version' => '1.0.0',
        'documentation' => '/api/documentation',
        'setup_url' => url('/setup-database'),
    ]);
});

/**
 * Live Server Database Setup Route
 * Usage:
 *   - Normal Migrate & Seed: http://pharmacy.nimadtech.in/setup-database
 *   - Fresh Wipe & Recreate: http://pharmacy.nimadtech.in/setup-database?fresh=1
 */
Route::get('/setup-database', function () {
    // Prevent request timeout on slower live server environments
    set_time_limit(300);

    $isFresh = request()->query('fresh') === '1' || request()->query('fresh') === 'true';

    $outputLog = [];
    $hasError = false;

    // 1. Verify Database Connection
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

    // 2. Clear Caches
    try {
        Artisan::call('optimize:clear');
        $outputLog[] = " [CACHE CLEAR] " . trim(Artisan::output());
    } catch (\Throwable $e) {
        $outputLog[] = " [WARN] Cache clear: " . $e->getMessage();
    }

    // 3. Run Migrations
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

    // 4. Run Seeders
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

    // Return HTML view for browser hit
    return response(view('setup_result', [
        'success' => !$hasError,
        'message' => $hasError 
            ? 'Setup encountered errors. Review the logs below.' 
            : 'Database created and seeded successfully!',
        'logs' => $outputLog,
        'isFresh' => $isFresh,
    ]), $statusCode);
});
