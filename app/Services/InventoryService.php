<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class InventoryService
{
    /**
     * Deduct stock from a specific batch with concurrency-safe pessimistic locking.
     */
    public function deductStock(
        int $batchId,
        float $quantity,
        string $movementType,
        ?Model $reference = null,
        ?string $remarks = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("Deduction quantity must be greater than zero.");
        }

        return DB::transaction(function () use ($batchId, $quantity, $movementType, $reference, $remarks) {
            /** @var ProductBatch $batch */
            $batch = ProductBatch::where('id', $batchId)->lockForUpdate()->firstOrFail();

            // Validate batch status and expiry
            if ($batch->status !== 'active') {
                throw new RuntimeException("Cannot deduct from batch {$batch->batch_number}: Status is {$batch->status}.");
            }

            if ($batch->isExpired()) {
                throw new RuntimeException("Cannot deduct from batch {$batch->batch_number}: Batch expired on {$batch->expiry_date->format('Y-m-d')}.");
            }

            if ((float)$batch->quantity_available < $quantity) {
                throw new RuntimeException("Insufficient stock in batch {$batch->batch_number}. Available: {$batch->quantity_available}, Requested: {$quantity}.");
            }

            $balanceBefore = (float)$batch->quantity_available;
            $balanceAfter = $balanceBefore - $quantity;

            $batch->quantity_available = $balanceAfter;
            $batch->save();

            // Append immutable ledger movement
            return StockMovement::create([
                'organization_id' => $batch->organization_id,
                'branch_id' => $batch->branch_id,
                'warehouse_id' => $batch->warehouse_id,
                'product_id' => $batch->product_id,
                'product_batch_id' => $batch->id,
                'movement_type' => $movementType,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
                'quantity' => -$quantity, // Negative for outflow
                'unit_cost' => $batch->purchase_rate,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'remarks' => $remarks ?? "Deduction via {$movementType}",
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Add stock to a batch with concurrency safety.
     */
    public function addStock(
        int $batchId,
        float $quantity,
        string $movementType,
        ?Model $reference = null,
        ?string $remarks = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("Added quantity must be greater than zero.");
        }

        return DB::transaction(function () use ($batchId, $quantity, $movementType, $reference, $remarks) {
            /** @var ProductBatch $batch */
            $batch = ProductBatch::where('id', $batchId)->lockForUpdate()->firstOrFail();

            $balanceBefore = (float)$batch->quantity_available;
            $balanceAfter = $balanceBefore + $quantity;

            $batch->quantity_available = $balanceAfter;
            $batch->quantity_received += $quantity;
            $batch->save();

            return StockMovement::create([
                'organization_id' => $batch->organization_id,
                'branch_id' => $batch->branch_id,
                'warehouse_id' => $batch->warehouse_id,
                'product_id' => $batch->product_id,
                'product_batch_id' => $batch->id,
                'movement_type' => $movementType,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
                'quantity' => $quantity, // Positive for inflow
                'unit_cost' => $batch->purchase_rate,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'remarks' => $remarks ?? "Inflow via {$movementType}",
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);
        });
    }

    /**
     * FEFO (First Expiry, First Out) batch selector.
     * Automatically picks eligible batches in order of earliest expiry to fulfill requested quantity.
     */
    public function selectBatchesFefo(int $productId, int $warehouseId, float $requiredQuantity): array
    {
        $eligibleBatches = ProductBatch::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->eligibleForSale()
            ->get();

        $allocations = [];
        $remaining = $requiredQuantity;

        foreach ($eligibleBatches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $avail = (float)$batch->quantity_available;
            $take = min($avail, $remaining);

            $allocations[] = [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'expiry_date' => $batch->expiry_date->format('Y-m-d'),
                'mrp' => (float)$batch->mrp,
                'sale_price' => (float)$batch->sale_price,
                'wholesale_price' => (float)$batch->wholesale_price,
                'available_quantity' => $avail,
                'allocated_quantity' => $take,
            ];

            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new RuntimeException("Insufficient eligible stock to fulfill {$requiredQuantity}. Shortfall: {$remaining}.");
        }

        return $allocations;
    }

    /**
     * Process stock adjustment physical discrepancy reconciliation.
     */
    public function processStockAdjustment(StockAdjustment $adjustment, array $items): StockAdjustment
    {
        return DB::transaction(function () use ($adjustment, $items) {
            $adjustment->status = 'approved';
            $adjustment->approved_by = auth()->id();
            $adjustment->approved_at = now();
            $adjustment->save();

            foreach ($items as $itemData) {
                /** @var ProductBatch $batch */
                $batch = ProductBatch::where('id', $itemData['product_batch_id'])->lockForUpdate()->firstOrFail();

                $systemQty = (float)$batch->quantity_available;
                $physicalQty = (float)$itemData['physical_quantity'];
                $diffQty = $physicalQty - $systemQty;

                $adjustment->items()->create([
                    'product_id' => $batch->product_id,
                    'product_batch_id' => $batch->id,
                    'system_quantity' => $systemQty,
                    'physical_quantity' => $physicalQty,
                    'difference_quantity' => $diffQty,
                    'unit_cost' => $batch->purchase_rate,
                    'remarks' => $itemData['remarks'] ?? null,
                ]);

                // Update batch stock
                $batch->quantity_available = $physicalQty;
                $batch->save();

                // Post compensating movement
                StockMovement::create([
                    'organization_id' => $adjustment->organization_id,
                    'branch_id' => $adjustment->branch_id,
                    'warehouse_id' => $adjustment->warehouse_id,
                    'product_id' => $batch->product_id,
                    'product_batch_id' => $batch->id,
                    'movement_type' => 'stock_adjustment',
                    'reference_type' => StockAdjustment::class,
                    'reference_id' => $adjustment->id,
                    'quantity' => $diffQty,
                    'unit_cost' => $batch->purchase_rate,
                    'balance_before' => $systemQty,
                    'balance_after' => $physicalQty,
                    'remarks' => "Stock adjustment #{$adjustment->adjustment_number}: " . ($itemData['remarks'] ?? 'Physical count variance'),
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                ]);
            }

            return $adjustment->load('items.product', 'items.batch');
        });
    }

    /**
     * Get expiring batches within $days for a branch.
     */
    public function getExpiringBatches(int $branchId, int $days = 90)
    {
        return ProductBatch::where('branch_id', $branchId)
            ->expiringWithinDays($days)
            ->with(['product.primaryUnit', 'warehouse'])
            ->get();
    }

    /**
     * Get products running low on stock against reorder_level.
     */
    public function getLowStockProducts(int $branchId)
    {
        return Product::whereRaw('(
                SELECT COALESCE(SUM(quantity_available), 0)
                FROM product_batches
                WHERE product_batches.product_id = products.id
                  AND product_batches.branch_id = ?
                  AND product_batches.status = "active"
                  AND product_batches.expiry_date > CURDATE()
            ) <= products.reorder_level', [$branchId])
            ->where('is_active', true)
            ->with(['brand', 'primaryUnit'])
            ->withSum(['batches as current_stock' => function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->where('status', 'active')
                  ->where('expiry_date', '>', now()->toDateString());
            }], 'quantity_available')
            ->get();
    }
}
