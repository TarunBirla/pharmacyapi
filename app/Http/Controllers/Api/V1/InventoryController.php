<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBatchRequest;
use App\Http\Requests\StockAdjustmentRequest;
use App\Http\Resources\ProductBatchResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementResource;
use App\Models\ProductBatch;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Services\AuditLogService;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * List batches with status, warehouse, and expiry filters.
     */
    public function batches(Request $request): JsonResponse
    {
        $query = ProductBatch::with(['product', 'warehouse']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('eligible_only')) {
            $query->eligibleForSale();
        } else {
            $query->fefo();
        }

        $batches = $query->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => ProductBatchResource::collection($batches)->response()->getData(true),
        ]);
    }

    /**
     * Create opening stock batch.
     */
    public function createBatch(CreateBatchRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $batch = DB::transaction(function () use ($data, $user) {
            $openingQty = (float)$data['opening_quantity'];

            $batch = ProductBatch::create([
                'organization_id' => $user->organization_id,
                'branch_id' => $user->branch_id,
                'warehouse_id' => $data['warehouse_id'],
                'product_id' => $data['product_id'],
                'batch_number' => $data['batch_number'],
                'manufacturing_date' => $data['manufacturing_date'] ?? null,
                'expiry_date' => $data['expiry_date'],
                'mrp' => $data['mrp'],
                'purchase_rate' => $data['purchase_rate'],
                'sale_price' => $data['sale_price'],
                'wholesale_price' => $data['wholesale_price'] ?? 0,
                'quantity_received' => $openingQty,
                'quantity_available' => $openingQty,
                'status' => 'active',
            ]);

            if ($openingQty > 0) {
                StockMovement::create([
                    'organization_id' => $batch->organization_id,
                    'branch_id' => $batch->branch_id,
                    'warehouse_id' => $batch->warehouse_id,
                    'product_id' => $batch->product_id,
                    'product_batch_id' => $batch->id,
                    'movement_type' => 'opening_stock',
                    'quantity' => $openingQty,
                    'unit_cost' => $batch->purchase_rate,
                    'balance_before' => 0,
                    'balance_after' => $openingQty,
                    'remarks' => 'Opening inventory entry',
                    'created_by' => $user->id,
                    'created_at' => now(),
                ]);
            }

            return $batch;
        });

        AuditLogService::log(
            action: 'batch.create',
            description: "Registered batch {$batch->batch_number} for product #{$batch->product_id}",
            entity: $batch
        );

        return response()->json([
            'success' => true,
            'message' => 'Product batch created and stock posted',
            'data' => new ProductBatchResource($batch->load(['product', 'warehouse'])),
        ], 201);
    }

    /**
     * FEFO Allocation calculation preview for POS or Order.
     */
    public function fefoAllocations(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
        ]);

        try {
            $allocations = $this->inventoryService->selectBatchesFefo(
                (int)$request->product_id,
                (int)$request->warehouse_id,
                (float)$request->quantity
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'requested_quantity' => (float)$request->quantity,
                    'allocations' => $allocations,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Perform stock count adjustment reconciliation.
     */
    public function adjustStock(StockAdjustmentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $adjustment = StockAdjustment::create([
            'organization_id' => $user->organization_id,
            'branch_id' => $user->branch_id,
            'warehouse_id' => $data['warehouse_id'],
            'adjustment_number' => 'ADJ-' . strtoupper(Str::random(8)),
            'reason' => $data['reason'],
            'notes' => $data['notes'] ?? null,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $processed = $this->inventoryService->processStockAdjustment($adjustment, $data['items']);

        AuditLogService::log(
            action: 'inventory.adjust',
            description: "Executed stock adjustment #{$adjustment->adjustment_number}",
            entity: $adjustment
        );

        return response()->json([
            'success' => true,
            'message' => 'Stock adjustment approved and movements posted to ledger',
            'data' => $processed,
        ]);
    }

    /**
     * Paginated Stock Movement Ledger.
     */
    public function stockLedger(Request $request): JsonResponse
    {
        $query = StockMovement::with(['product', 'batch', 'warehouse', 'creator'])->latest('id');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        $movements = $query->paginate($request->input('per_page', 30));

        return response()->json([
            'success' => true,
            'data' => StockMovementResource::collection($movements)->response()->getData(true),
        ]);
    }

    /**
     * Expiry alerts (default: within 90 days).
     */
    public function expiring(Request $request): JsonResponse
    {
        $branchId = (int)$request->input('branch_id', $request->user()->branch_id);
        $days = (int)$request->input('days', 90);

        $batches = $this->inventoryService->getExpiringBatches($branchId, $days);

        return response()->json([
            'success' => true,
            'days_threshold' => $days,
            'total_expiring_batches' => $batches->count(),
            'data' => ProductBatchResource::collection($batches),
        ]);
    }

    /**
     * Low stock alerts (at or below reorder_level).
     */
    public function lowStock(Request $request): JsonResponse
    {
        $branchId = (int)$request->input('branch_id', $request->user()->branch_id);

        $products = $this->inventoryService->getLowStockProducts($branchId);

        return response()->json([
            'success' => true,
            'total_low_stock_products' => $products->count(),
            'data' => ProductResource::collection($products),
        ]);
    }
}
