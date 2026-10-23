<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->string('generic_name')->nullable();
            $table->foreignId('generic_composition_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manufacturer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('dosage_form', 50)->default('tablet');
            $table->string('strength', 50)->nullable();
            $table->string('hsn_code', 20)->default('30049099');
            $table->decimal('gst_rate', 5, 2)->default(12.00);

            // Units and Pack Conversions
            $table->foreignId('primary_unit_id')->constrained('units');
            $table->foreignId('secondary_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('unit_conversion_factor', 10, 4)->default(1.0000)
                ->comment('Number of primary units inside one secondary unit');
            $table->string('packaging_type', 100)->nullable();

            // Dispensary Rack & Shelf
            $table->string('rack_location', 50)->nullable();
            $table->string('shelf_location', 50)->nullable();

            // Statutory Prescription Classification
            $table->enum('prescription_type', [
                'otc',
                'schedule_h',
                'schedule_h1',
                'schedule_x',
                'narcotic'
            ])->default('otc');

            // Storage Specs
            $table->enum('storage_temperature', [
                'room_temperature',
                'cold_chain_2_8c',
                'cool_dark_place'
            ])->default('room_temperature');

            // Stock Thresholds & Reorder Alerts
            $table->decimal('min_stock_level', 12, 4)->default(0);
            $table->decimal('max_stock_level', 12, 4)->default(0);
            $table->decimal('reorder_level', 12, 4)->default(10);
            $table->decimal('reorder_quantity', 12, 4)->default(50);

            $table->boolean('is_active')->default(true);
            $table->boolean('is_narcotic')->default(false);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'name']);
            $table->index(['organization_id', 'prescription_type']);
            $table->index(['organization_id', 'generic_composition_id']);
        });

        // Exact composition breakdown for pharmaceutical equivalence
        Schema::create('product_compositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('active_ingredient', 150);
            $table->decimal('strength', 10, 3);
            $table->string('unit', 20)->default('mg');
            $table->timestamps();

            $table->index(['product_id', 'active_ingredient']);
        });

        // Multiple Barcodes per product (e.g. Strip barcode vs Outer Box barcode)
        Schema::create('product_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('barcode', 60);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique('barcode');
            $table->index(['product_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_barcodes');
        Schema::dropIfExists('product_compositions');
        Schema::dropIfExists('products');
    }
};
