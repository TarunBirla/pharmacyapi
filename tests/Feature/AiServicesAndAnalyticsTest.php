<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesService;
use Tests\TestCase;

class AiServicesAndAnalyticsTest extends TestCase
{
    protected User $owner;
    protected string $token;
    protected Organization $org;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::withoutGlobalScopes()->where('email', 'owner@apexpharma.com')->firstOrFail();
        $this->token = $this->owner->createToken('AiAnalyticsTest')->plainTextToken;
        $this->org = Organization::firstOrFail();
        $this->branch = Branch::where('code', 'APEX-RET-01')->firstOrFail();
    }

    public function test_ai_demand_forecasting_generates_velocity_and_reorder_recommendations(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/ai/demand-forecast');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'product_id',
                        'current_stock',
                        'sales_velocity_daily',
                        'estimated_days_stock_remaining',
                        'recommended_reorder_quantity',
                        'stockout_risk_level',
                        'confidence_score',
                    ]
                ]
            ]);

        $forecasts = $response->json('data');
        $this->assertNotEmpty($forecasts);
    }

    public function test_ai_expiry_risk_analyzes_near_expiry_batches_and_suggests_liquidation_action(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/ai/expiry-risk?threshold_days=180');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'product_batch_id',
                        'batch_number',
                        'expiry_date',
                        'days_to_expiry',
                        'quantity_at_risk',
                        'financial_exposure',
                        'recommended_action',
                    ]
                ]
            ]);
    }

    public function test_ai_dead_stock_identifies_stagnant_inventory(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/ai/dead-stock?inactive_days=30');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertIsArray($response->json('data'));
    }

    public function test_ai_ocr_service_parses_supplier_invoice_and_matches_catalog(): void
    {
        $supplier = Supplier::where('code', 'SUP-MEDPLUS-01')->firstOrFail();
        $dolo = Product::where('code', 'MED-DOLO-650')->firstOrFail();

        $scanPayload = [
            'supplier_name' => 'MedPlus Wholesalers',
            'supplier_gstin' => $supplier->gstin,
            'invoice_number' => 'OCR-INV-9921',
            'invoice_date' => date('Y-m-d'),
            'items' => [
                [
                    'code' => 'MED-DOLO-650',
                    'medicine_name' => 'Dolo 650mg Paracetamol Tablets',
                    'batch_number' => 'MED-OCR-B1',
                    'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                    'quantity' => 100,
                    'free_quantity' => 10,
                    'unit_cost' => 21.50,
                    'mrp' => 35.00,
                    'gst_rate' => 12.00,
                ],
                [
                    'medicine_name' => 'Unknown Brand Syrup 200ml',
                    'batch_number' => 'NEW-OCR-B2',
                    'quantity' => 20,
                    'unit_cost' => 85.00,
                    'mrp' => 120.00,
                    'gst_rate' => 18.00,
                ]
            ]
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/ai/ocr/parse-invoice', $scanPayload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_items_parsed' => 2,
                    'matched_items_count' => 1,
                    'supplier' => [
                        'gstin' => $supplier->gstin,
                    ]
                ]
            ]);

        $items = $response->json('data.items');
        $this->assertEquals($dolo->id, $items[0]['matched_product_id']);
        $this->assertTrue($items[0]['is_catalog_match']);
        $this->assertFalse($items[1]['is_catalog_match']);
    }

    public function test_chronic_patient_refill_engine_predicts_refill_dates(): void
    {
        $warehouse = Warehouse::where('code', 'RET-DISP-01')->firstOrFail();
        $batch = ProductBatch::where('batch_number', 'DL-2026-F18M')->firstOrFail();
        $customer = Customer::firstOrFail();

        // Post a retail sale for 2 tablets
        $salesService = app(SalesService::class);
        $saleQty = 2.0000;
        $invoice = $salesService->postSalesInvoice([
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customer->id,
            'invoice_type' => 'retail',
            'patient_name' => $customer->name,
            'items' => [
                [
                    'product_id' => $batch->product_id,
                    'product_batch_id' => $batch->id,
                    'quantity' => $saleQty,
                    'unit_price' => (float)$batch->sale_price,
                ]
            ],
            'payments' => [
                [
                    'amount' => (float)round($saleQty * (float)$batch->sale_price * 1.12),
                    'payment_mode' => 'cash',
                ]
            ]
        ], $this->owner);

        // Fetch AI patient refills
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/ai/patient-refills');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $refills = $response->json('data');
        $this->assertNotEmpty($refills);

        $firstRefill = $refills[0];
        $this->assertNotNull($firstRefill['predicted_refill_date']);

        // Update reminder status
        $statusRes = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/v1/ai/patient-refills/{$firstRefill['id']}/status", [
                'status' => 'contacted',
                'notes' => 'Patient contacted via SMS notification for diabetic refill',
            ]);

        $statusRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'contacted',
                ]
            ]);
    }
}
