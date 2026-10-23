<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Tests\TestCase;

class InventoryAndFefoTest extends TestCase
{
    protected string $token;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::withoutGlobalScopes()->where('email', 'pharmacist@apexpharma.com')->first();
        $this->token = $this->user->createToken('InventoryTest')->plainTextToken;
    }

    public function test_fefo_engine_selects_earliest_expiring_batches_first(): void
    {
        $dolo = Product::where('code', 'MED-DOLO-650')->firstOrFail();
        $warehouse = Warehouse::where('code', 'RET-DISP-01')->firstOrFail();

        // Request 40 units:
        // Expected: 30 from Batch DL-2024-EXP20 (expiring in 20 days) + 10 from Batch DL-2026-F18M (expiring in 18 months)
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/inventory/fefo-allocations', [
                'product_id' => $dolo->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 40.0000,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'requested_quantity' => 40,
                ]
            ]);

        $allocations = $response->json('data.allocations');
        $this->assertCount(2, $allocations);
        $this->assertEquals('DL-2024-EXP20', $allocations[0]['batch_number']);
        $this->assertEquals(30, $allocations[0]['allocated_quantity']);
        $this->assertEquals('DL-2026-F18M', $allocations[1]['batch_number']);
        $this->assertEquals(10, $allocations[1]['allocated_quantity']);
    }

    public function test_concurrency_safe_stock_deduction_and_ledger_movement(): void
    {
        $batch = ProductBatch::where('batch_number', 'DL-2026-F18M')->firstOrFail();
        $initialStock = (float)$batch->quantity_available;

        /** @var InventoryService $inventoryService */
        $inventoryService = app(InventoryService::class);

        $movement = $inventoryService->deductStock(
            batchId: $batch->id,
            quantity: 5.0000,
            movementType: 'sale_deduction',
            remarks: 'POS Checkout #TEST-1001'
        );

        $this->assertInstanceOf(StockMovement::class, $movement);
        $this->assertEquals(-5.0000, (float)$movement->quantity);
        $this->assertEquals($initialStock, (float)$movement->balance_before);
        $this->assertEquals($initialStock - 5.0000, (float)$movement->balance_after);

        $batch->refresh();
        $this->assertEquals($initialStock - 5.0000, (float)$batch->quantity_available);
    }

    public function test_prevents_deduction_from_expired_batch(): void
    {
        $warehouse = Warehouse::where('code', 'RET-DISP-01')->firstOrFail();
        $dolo = Product::where('code', 'MED-DOLO-650')->firstOrFail();

        // Create an expired batch with unique batch number
        $expiredBatch = ProductBatch::create([
            'organization_id' => $this->user->organization_id,
            'branch_id' => $warehouse->branch_id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $dolo->id,
            'batch_number' => 'EXPIRED-BATCH-' . uniqid(),
            'expiry_date' => now()->subDays(5)->toDateString(),
            'mrp' => 30.00,
            'purchase_rate' => 20.00,
            'sale_price' => 28.00,
            'quantity_received' => 50.0000,
            'quantity_available' => 50.0000,
            'status' => 'active',
        ]);

        /** @var InventoryService $inventoryService */
        $inventoryService = app(InventoryService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Batch expired/');

        $inventoryService->deductStock(
            batchId: $expiredBatch->id,
            quantity: 1.0000,
            movementType: 'sale_deduction'
        );
    }

    public function test_stock_adjustment_creates_compensating_ledger_entry(): void
    {
        $owner = User::withoutGlobalScopes()->where('email', 'owner@apexpharma.com')->first();
        $ownerToken = $owner->createToken('OwnerAdjustTest')->plainTextToken;

        $batch = ProductBatch::where('batch_number', 'CP-2026-001')->firstOrFail();
        $currentQty = (float)$batch->quantity_available;
        $physicalCount = $currentQty - 2.0000; // 2 damaged / missing

        $response = $this->withHeader('Authorization', 'Bearer ' . $ownerToken)
            ->postJson('/api/v1/inventory/adjustments', [
                'warehouse_id' => $batch->warehouse_id,
                'reason' => 'breakage',
                'notes' => 'Damaged in rack during shelving',
                'items' => [
                    [
                        'product_batch_id' => $batch->id,
                        'physical_quantity' => $physicalCount,
                        'remarks' => '2 ampoules broken',
                    ]
                ]
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Stock adjustment approved and movements posted to ledger',
            ]);

        $batch->refresh();
        $this->assertEquals($physicalCount, (float)$batch->quantity_available);

        $this->assertDatabaseHas('stock_movements', [
            'product_batch_id' => $batch->id,
            'movement_type' => 'stock_adjustment',
            'quantity' => -2.0000,
        ]);
    }

    public function test_expiring_alerts_detects_near_expiry_batches(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/inventory/expiring?days=30');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');
        $batchNumbers = collect($data)->pluck('batch_number')->all();

        // DL-2024-EXP20 expires in 20 days, must be caught in 30-day alert
        $this->assertContains('DL-2024-EXP20', $batchNumbers);
        // DL-2026-F18M expires in 18 months, must NOT be in 30-day alert
        $this->assertNotContains('DL-2026-F18M', $batchNumbers);
    }

    public function test_low_stock_alerts_detects_products_below_reorder_level(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/inventory/low-stock');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');
        $productCodes = collect($data)->pluck('code')->all();

        // Ciplox 500 has stock 10 with reorder_level 50 -> MUST trigger alert
        $this->assertContains('MED-CIPLOX-500', $productCodes);
    }
}
