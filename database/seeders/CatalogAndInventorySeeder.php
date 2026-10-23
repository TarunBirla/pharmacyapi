<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\GenericComposition;
use App\Models\Manufacturer;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductBatch;
use App\Models\ProductComposition;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CatalogAndInventorySeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('code', 'APEX-PHARMA')->firstOrFail();
        $warehouse = Warehouse::where('code', 'RET-DISP-01')->firstOrFail();
        $adminUser = User::where('email', 'pharmacist@apexpharma.com')->firstOrFail();

        // 1. Standard Units
        $units = [
            ['name' => 'Tablet', 'code' => 'TAB', 'is_fractional_allowed' => false],
            ['name' => 'Capsule', 'code' => 'CAP', 'is_fractional_allowed' => false],
            ['name' => 'Strip', 'code' => 'STRIP', 'is_fractional_allowed' => false],
            ['name' => 'Bottle', 'code' => 'BTL', 'is_fractional_allowed' => false],
            ['name' => 'Box', 'code' => 'BOX', 'is_fractional_allowed' => false],
            ['name' => 'Vial', 'code' => 'VIAL', 'is_fractional_allowed' => false],
            ['name' => 'Tube', 'code' => 'TUBE', 'is_fractional_allowed' => false],
            ['name' => 'Milliliter', 'code' => 'ML', 'is_fractional_allowed' => true],
        ];

        $unitMap = [];
        foreach ($units as $u) {
            $unitMap[$u['code']] = Unit::firstOrCreate(
                ['code' => $u['code'], 'organization_id' => null],
                $u
            );
        }

        // 2. Categories
        $categories = [
            ['name' => 'Analgesics & Antipyretics', 'slug' => 'analgesics-antipyretics'],
            ['name' => 'Antibiotics & Antimicrobials', 'slug' => 'antibiotics'],
            ['name' => 'Antidiabetic', 'slug' => 'antidiabetic'],
            ['name' => 'Cardiovascular', 'slug' => 'cardiovascular'],
            ['name' => 'Gastrointestinal', 'slug' => 'gastrointestinal'],
        ];

        $categoryMap = [];
        foreach ($categories as $cat) {
            $categoryMap[$cat['slug']] = Category::firstOrCreate(
                ['slug' => $cat['slug'], 'organization_id' => $org->id],
                $cat
            );
        }

        // 3. Manufacturers
        $m1 = Manufacturer::firstOrCreate(
            ['name' => 'Micro Labs Limited', 'organization_id' => $org->id],
            ['code' => 'MICRO', 'country' => 'India']
        );
        $m2 = Manufacturer::firstOrCreate(
            ['name' => 'GlaxoSmithKline Pharmaceuticals Ltd', 'organization_id' => $org->id],
            ['code' => 'GSK', 'country' => 'India']
        );
        $m3 = Manufacturer::firstOrCreate(
            ['name' => 'Cipla Ltd', 'organization_id' => $org->id],
            ['code' => 'CIPLA', 'country' => 'India']
        );

        // 4. Brands
        $b1 = Brand::firstOrCreate(['name' => 'Dolo', 'organization_id' => $org->id], ['manufacturer_id' => $m1->id]);
        $b2 = Brand::firstOrCreate(['name' => 'Calpol', 'organization_id' => $org->id], ['manufacturer_id' => $m2->id]);
        $b3 = Brand::firstOrCreate(['name' => 'Augmentin', 'organization_id' => $org->id], ['manufacturer_id' => $m2->id]);
        $b4 = Brand::firstOrCreate(['name' => 'Ciplox', 'organization_id' => $org->id], ['manufacturer_id' => $m3->id]);

        // 5. Generic Compositions
        $gcParacetamol650 = GenericComposition::firstOrCreate(
            ['name' => 'Paracetamol 650mg', 'organization_id' => $org->id],
            ['description' => 'Fast acting analgesic and antipyretic']
        );

        $gcAmoxClav = GenericComposition::firstOrCreate(
            ['name' => 'Amoxicillin (500mg) + Clavulanic Acid (125mg)', 'organization_id' => $org->id],
            ['description' => 'Broad-spectrum beta-lactam antibiotic' ]
        );

        // 6. Products
        // Product A: Dolo 650
        $dolo = Product::firstOrCreate(
            ['code' => 'MED-DOLO-650', 'organization_id' => $org->id],
            [
                'name' => 'Dolo 650 Tablet',
                'generic_name' => 'Paracetamol 650mg',
                'generic_composition_id' => $gcParacetamol650->id,
                'brand_id' => $b1->id,
                'manufacturer_id' => $m1->id,
                'category_id' => $categoryMap['analgesics-antipyretics']->id,
                'dosage_form' => 'tablet',
                'strength' => '650 mg',
                'hsn_code' => '30049099',
                'gst_rate' => 12.00,
                'primary_unit_id' => $unitMap['TAB']->id,
                'secondary_unit_id' => $unitMap['STRIP']->id,
                'unit_conversion_factor' => 15.0000,
                'packaging_type' => 'Strip of 15 Tablets',
                'rack_location' => 'Rack A-1',
                'shelf_location' => 'Shelf 2',
                'prescription_type' => 'otc',
                'storage_temperature' => 'room_temperature',
                'reorder_level' => 30.0000,
                'reorder_quantity' => 150.0000,
            ]
        );

        ProductBarcode::firstOrCreate(['barcode' => '8901117001234'], [
            'product_id' => $dolo->id,
            'unit_id' => $unitMap['STRIP']->id,
            'is_primary' => true,
        ]);

        ProductComposition::firstOrCreate(
            ['product_id' => $dolo->id, 'active_ingredient' => 'Paracetamol'],
            ['strength' => 650, 'unit' => 'mg']
        );

        // Product B: Calpol 650 (Medically equivalent substitute to Dolo 650)
        $calpol = Product::firstOrCreate(
            ['code' => 'MED-CALPOL-650', 'organization_id' => $org->id],
            [
                'name' => 'Calpol 650 Tablet',
                'generic_name' => 'Paracetamol 650mg',
                'generic_composition_id' => $gcParacetamol650->id,
                'brand_id' => $b2->id,
                'manufacturer_id' => $m2->id,
                'category_id' => $categoryMap['analgesics-antipyretics']->id,
                'dosage_form' => 'tablet',
                'strength' => '650 mg',
                'hsn_code' => '30049099',
                'gst_rate' => 12.00,
                'primary_unit_id' => $unitMap['TAB']->id,
                'secondary_unit_id' => $unitMap['STRIP']->id,
                'unit_conversion_factor' => 15.0000,
                'packaging_type' => 'Strip of 15 Tablets',
                'rack_location' => 'Rack A-1',
                'shelf_location' => 'Shelf 3',
                'prescription_type' => 'otc',
                'storage_temperature' => 'room_temperature',
                'reorder_level' => 20.0000,
                'reorder_quantity' => 100.0000,
            ]
        );

        ProductBarcode::firstOrCreate(['barcode' => '8901117009876'], [
            'product_id' => $calpol->id,
            'unit_id' => $unitMap['STRIP']->id,
            'is_primary' => true,
        ]);

        // Product C: Augmentin 625 Duo (Schedule H Antibiotic)
        $augmentin = Product::firstOrCreate(
            ['code' => 'MED-AUG-625', 'organization_id' => $org->id],
            [
                'name' => 'Augmentin 625 Duo Tablet',
                'generic_name' => 'Amoxicillin & Potassium Clavulanate',
                'generic_composition_id' => $gcAmoxClav->id,
                'brand_id' => $b3->id,
                'manufacturer_id' => $m2->id,
                'category_id' => $categoryMap['antibiotics']->id,
                'dosage_form' => 'tablet',
                'strength' => '625 mg',
                'hsn_code' => '30041010',
                'gst_rate' => 12.00,
                'primary_unit_id' => $unitMap['TAB']->id,
                'secondary_unit_id' => $unitMap['STRIP']->id,
                'unit_conversion_factor' => 10.0000,
                'packaging_type' => 'Strip of 10 Tablets',
                'rack_location' => 'Rack B-3',
                'shelf_location' => 'Shelf 1',
                'prescription_type' => 'schedule_h',
                'storage_temperature' => 'room_temperature',
                'reorder_level' => 25.0000,
                'reorder_quantity' => 80.0000,
            ]
        );

        // Product D: Low Stock item (Reorder level 50, but stock will be only 10)
        $lowStockProd = Product::firstOrCreate(
            ['code' => 'MED-CIPLOX-500', 'organization_id' => $org->id],
            [
                'name' => 'Ciplox 500 Tablet',
                'generic_name' => 'Ciprofloxacin 500mg',
                'brand_id' => $b4->id,
                'manufacturer_id' => $m3->id,
                'category_id' => $categoryMap['antibiotics']->id,
                'dosage_form' => 'tablet',
                'strength' => '500 mg',
                'hsn_code' => '30042099',
                'gst_rate' => 12.00,
                'primary_unit_id' => $unitMap['TAB']->id,
                'secondary_unit_id' => $unitMap['STRIP']->id,
                'unit_conversion_factor' => 10.0000,
                'packaging_type' => 'Strip of 10 Tablets',
                'rack_location' => 'Rack B-4',
                'shelf_location' => 'Shelf 2',
                'prescription_type' => 'schedule_h',
                'storage_temperature' => 'room_temperature',
                'reorder_level' => 50.0000,
                'reorder_quantity' => 100.0000,
            ]
        );

        // 7. Seed Batches & Stock Movements
        // Dolo Batch 1: Expiring in 20 days (Early FEFO candidate & Expiry alert trigger)
        $batch1 = ProductBatch::firstOrCreate(
            ['warehouse_id' => $warehouse->id, 'product_id' => $dolo->id, 'batch_number' => 'DL-2024-EXP20'],
            [
                'organization_id' => $org->id,
                'branch_id' => $warehouse->branch_id,
                'manufacturing_date' => now()->subMonths(18)->toDateString(),
                'expiry_date' => now()->addDays(20)->toDateString(),
                'mrp' => 34.50,
                'purchase_rate' => 22.00,
                'sale_price' => 31.00,
                'wholesale_price' => 24.50,
                'quantity_received' => 60.0000,
                'quantity_available' => 30.0000,
                'status' => 'active',
            ]
        );

        // Dolo Batch 2: Fresh stock expiring in 18 months
        $batch2 = ProductBatch::firstOrCreate(
            ['warehouse_id' => $warehouse->id, 'product_id' => $dolo->id, 'batch_number' => 'DL-2026-F18M'],
            [
                'organization_id' => $org->id,
                'branch_id' => $warehouse->branch_id,
                'manufacturing_date' => now()->subMonths(2)->toDateString(),
                'expiry_date' => now()->addMonths(18)->toDateString(),
                'mrp' => 35.00,
                'purchase_rate' => 22.50,
                'sale_price' => 31.50,
                'wholesale_price' => 25.00,
                'quantity_received' => 150.0000,
                'quantity_available' => 150.0000,
                'status' => 'active',
            ]
        );

        // Calpol Batch: Active stock
        ProductBatch::firstOrCreate(
            ['warehouse_id' => $warehouse->id, 'product_id' => $calpol->id, 'batch_number' => 'CP-2026-001'],
            [
                'organization_id' => $org->id,
                'branch_id' => $warehouse->branch_id,
                'manufacturing_date' => now()->subMonths(3)->toDateString(),
                'expiry_date' => now()->addMonths(14)->toDateString(),
                'mrp' => 36.00,
                'purchase_rate' => 23.00,
                'sale_price' => 32.00,
                'wholesale_price' => 25.50,
                'quantity_received' => 90.0000,
                'quantity_available' => 90.0000,
                'status' => 'active',
            ]
        );

        // Augmentin 625 Batch
        ProductBatch::firstOrCreate(
            ['warehouse_id' => $warehouse->id, 'product_id' => $augmentin->id, 'batch_number' => 'AG-2026-A1'],
            [
                'organization_id' => $org->id,
                'branch_id' => $warehouse->branch_id,
                'manufacturing_date' => now()->subMonths(1)->toDateString(),
                'expiry_date' => now()->addMonths(16)->toDateString(),
                'mrp' => 220.00,
                'purchase_rate' => 155.00,
                'sale_price' => 200.00,
                'wholesale_price' => 168.00,
                'quantity_received' => 50.0000,
                'quantity_available' => 50.0000,
                'status' => 'active',
            ]
        );

        // Low stock item: Only 10 in stock (reorder level is 50)
        ProductBatch::firstOrCreate(
            ['warehouse_id' => $warehouse->id, 'product_id' => $lowStockProd->id, 'batch_number' => 'CX-LOW-01'],
            [
                'organization_id' => $org->id,
                'branch_id' => $warehouse->branch_id,
                'manufacturing_date' => now()->subMonths(4)->toDateString(),
                'expiry_date' => now()->addMonths(10)->toDateString(),
                'mrp' => 45.00,
                'purchase_rate' => 28.00,
                'sale_price' => 40.00,
                'wholesale_price' => 31.00,
                'quantity_received' => 10.0000,
                'quantity_available' => 10.0000,
                'status' => 'active',
            ]
        );

        // Record Initial Opening Stock Movements
        StockMovement::firstOrCreate(
            ['product_batch_id' => $batch1->id, 'movement_type' => 'opening_stock'],
            [
                'organization_id' => $org->id,
                'branch_id' => $warehouse->branch_id,
                'warehouse_id' => $warehouse->id,
                'product_id' => $dolo->id,
                'quantity' => 30.0000,
                'unit_cost' => 22.00,
                'balance_before' => 0.0000,
                'balance_after' => 30.0000,
                'remarks' => 'Opening stock load',
                'created_by' => $adminUser->id,
                'created_at' => now(),
            ]
        );

        StockMovement::firstOrCreate(
            ['product_batch_id' => $batch2->id, 'movement_type' => 'opening_stock'],
            [
                'organization_id' => $org->id,
                'branch_id' => $warehouse->branch_id,
                'warehouse_id' => $warehouse->id,
                'product_id' => $dolo->id,
                'quantity' => 150.0000,
                'unit_cost' => 22.50,
                'balance_before' => 0.0000,
                'balance_after' => 150.0000,
                'remarks' => 'Opening stock load',
                'created_by' => $adminUser->id,
                'created_at' => now(),
            ]
        );
    }
}
