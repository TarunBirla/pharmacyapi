<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Batch Inventory
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number', 60);
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date');
            $table->decimal('mrp', 12, 2);
            $table->decimal('purchase_rate', 12, 2);
            $table->decimal('sale_price', 12, 2);
            $table->decimal('wholesale_price', 12, 2)->default(0);

            // Stock quantities in primary units (e.g. Tablets)
            $table->decimal('quantity_received', 12, 4)->default(0);
            $table->decimal('quantity_available', 12, 4)->default(0);
            $table->decimal('quantity_reserved', 12, 4)->default(0);
            $table->decimal('quantity_quarantined', 12, 4)->default(0);
            $table->decimal('quantity_damaged', 12, 4)->default(0);
            $table->decimal('quantity_expired', 12, 4)->default(0);

            $table->enum('status', [
                'active',
                'quarantine',
                'expired',
                'recalled',
                'damaged'
            ])->default('active');

            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id', 'batch_number'], 'batch_warehouse_prod_unique');
            $table->index(['product_id', 'status', 'expiry_date'], 'batch_fefo_idx');
            $table->index(['organization_id', 'branch_id', 'expiry_date'], 'batch_org_expiry_idx');
        });

        // 2. Immutable Stock Movement Ledger
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_batch_id')->constrained()->cascadeOnDelete();

            $table->enum('movement_type', [
                'opening_stock',
                'purchase_receipt',
                'sale_deduction',
                'sale_return',
                'purchase_return',
                'warehouse_transfer_in',
                'warehouse_transfer_out',
                'stock_adjustment',
                'damage_write_off',
                'expiry_write_off',
                'reservation',
                'release_reservation'
            ]);

            $table->nullableMorphs('reference'); // e.g. SaleInvoice, PurchaseInvoice, StockAdjustment
            $table->decimal('quantity', 12, 4); // + for inward, - for outward
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('balance_before', 12, 4);
            $table->decimal('balance_after', 12, 4);
            $table->string('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['product_batch_id', 'created_at'], 'movement_batch_idx');
            $table->index(['product_id', 'warehouse_id', 'created_at'], 'movement_prod_wh_idx');
            $table->index(['organization_id', 'movement_type', 'created_at'], 'movement_org_type_idx');
        });

        // 3. Stock Adjustments
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('adjustment_number', 50);
            $table->enum('reason', [
                'physical_count_mismatch',
                'breakage',
                'damaged',
                'expired',
                'theft',
                'correction'
            ]);
            $table->enum('status', ['draft', 'approved', 'cancelled'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'adjustment_number']);
        });

        // 4. Stock Adjustment Line Items
        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_batch_id')->constrained()->cascadeOnDelete();
            $table->decimal('system_quantity', 12, 4);
            $table->decimal('physical_quantity', 12, 4);
            $table->decimal('difference_quantity', 12, 4); // physical - system
            $table->decimal('unit_cost', 12, 2);
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('product_batches');
    }
};
