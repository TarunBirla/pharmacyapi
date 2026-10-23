<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Services\AccountingService;
use Illuminate\Database\Seeder;

class AccountingAndGstSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::first();
        if ($org) {
            $accountingService = app(AccountingService::class);
            $accountingService->seedDefaultChartOfAccounts($org);
            $accountingService->getOrCreateFinancialYear($org->id, date('Y-m-d'));
        }
    }
}
