<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            PharmacyDemoSeeder::class,
            CatalogAndInventorySeeder::class,
            SupplierAndProcurementSeeder::class,
            SalesAndPosSeeder::class,
            AccountingAndGstSeeder::class,
        ]);
    }
}
