<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use Illuminate\Database\Seeder;

class SupplierAndProcurementSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('code', 'APEX-PHARMA')->firstOrFail();
        $warehouse = Warehouse::where('code', 'RET-DISP-01')->firstOrFail();
        $purchaser = User::where('email', 'purchase@apexpharma.com')->firstOrFail();
        $tabUnit = Unit::where('code', 'TAB')->firstOrFail();
        $dolo = Product::where('code', 'MED-DOLO-650')->firstOrFail();
        $augmentin = Product::where('code', 'MED-AUG-625')->firstOrFail();

        // 1. Create Suppliers
        $sup1 = Supplier::firstOrCreate(
            ['code' => 'SUP-MEDPLUS-01', 'organization_id' => $org->id],
            [
                'name' => 'MedPlus Wholesalers & Distributors Ltd',
                'contact_person' => 'Ramesh Chandran',
                'email' => 'orders@medpluswholesale.in',
                'phone' => '+91 80 4455 6677',
                'gstin' => '29AAACM1234F1Z1',
                'pan' => 'AAACM1234F',
                'dl_number_20b' => 'KA-BNG-20B-77112',
                'dl_number_21b' => 'KA-BNG-21B-77113',
                'address' => 'Warehouse Complex #4, Yeshwanthpur',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'postal_code' => '560022',
                'credit_period_days' => 30,
                'credit_limit' => 500000.00,
                'status' => 'active',
            ]
        );

        $sup2 = Supplier::firstOrCreate(
            ['code' => 'SUP-APOLLO-02', 'organization_id' => $org->id],
            [
                'name' => 'Apollo Pharma Distribution Logistics',
                'contact_person' => 'Vikram Joshi',
                'email' => 'supply@apollodist.com',
                'phone' => '+91 80 4999 8888',
                'gstin' => '29AABCA5678B1Z2',
                'pan' => 'AABCA5678B',
                'address' => 'Electronic City Phase 1',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'postal_code' => '560100',
                'credit_period_days' => 45,
                'credit_limit' => 1000000.00,
                'status' => 'active',
            ]
        );

        // 2. Create Sample Purchase Order
        $po = PurchaseOrder::firstOrCreate(
            ['po_number' => 'PO-DEMO-2026-001', 'organization_id' => $org->id],
            [
                'branch_id' => $warehouse->branch_id,
                'supplier_id' => $sup1->id,
                'order_date' => now()->subDays(5)->toDateString(),
                'expected_delivery_date' => now()->addDays(2)->toDateString(),
                'subtotal' => 10000.00,
                'tax_amount' => 1200.00,
                'total_amount' => 11200.00,
                'status' => 'approved',
                'notes' => 'Urgent replenishment for seasonal fever medications',
                'created_by' => $purchaser->id,
                'approved_by' => $purchaser->id,
                'approved_at' => now()->subDays(4),
            ]
        );

        $po->items()->firstOrCreate(
            ['product_id' => $dolo->id],
            [
                'unit_id' => $tabUnit->id,
                'quantity_ordered' => 300,
                'quantity_received' => 0,
                'unit_cost' => 22.00,
                'tax_rate' => 12.00,
                'tax_amount' => 792.00,
                'total_amount' => 7392.00,
            ]
        );

        // 3. Post a Sample Supplier Invoice via PurchaseService
        /** @var PurchaseService $purchaseService */
        $purchaseService = app(PurchaseService::class);

        $invoiceNumber = 'BILL-MEDPLUS-1008';
        $existingInvoice = \App\Models\PurchaseInvoice::where('invoice_number', $invoiceNumber)->first();

        if (!$existingInvoice) {
            $purchaseService->postSupplierInvoice([
                'supplier_id' => $sup1->id,
                'warehouse_id' => $warehouse->id,
                'purchase_order_id' => $po->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => now()->subDays(2)->toDateString(),
                'additional_charges' => 150.00, // Freight
                'notes' => 'Received in good condition via BlueDart',
                'items' => [
                    [
                        'product_id' => $dolo->id,
                        'batch_number' => 'DL-SUP-2026-N1',
                        'expiry_date' => now()->addMonths(24)->toDateString(),
                        'manufacturing_date' => now()->subMonths(1)->toDateString(),
                        'quantity' => 100,
                        'free_quantity' => 10, // Buy 100 get 10 free promotional scheme
                        'mrp' => 35.00,
                        'unit_cost' => 22.00,
                        'sale_price' => 31.50,
                        'wholesale_price' => 25.00,
                        'discount_percent' => 5.00,
                        'gst_rate' => 12.00,
                    ]
                ]
            ], $purchaser);
        }
    }
}
