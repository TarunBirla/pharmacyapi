<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_demand_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('forecast_date');
            $table->decimal('current_stock', 12, 4)->default(0);
            $table->decimal('sales_velocity_daily', 10, 4)->default(0);
            $table->integer('estimated_days_stock_remaining')->default(999);
            $table->decimal('recommended_reorder_quantity', 12, 4)->default(0);
            $table->decimal('confidence_score', 5, 2)->default(90.00);
            $table->enum('stockout_risk_level', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->timestamps();

            $table->unique(['branch_id', 'product_id', 'forecast_date']);
            $table->index(['organization_id', 'stockout_risk_level']);
        });

        Schema::create('ai_expiry_risk_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_batch_id')->constrained('product_batches')->cascadeOnDelete();
            $table->string('batch_number', 60);
            $table->date('expiry_date');
            $table->integer('days_to_expiry');
            $table->decimal('quantity_at_risk', 12, 4);
            $table->decimal('financial_exposure', 15, 2);
            $table->enum('recommended_action', ['return_to_supplier', 'liquidate_discount', 'quarantine', 'write_off']);
            $table->decimal('discount_suggestion_percent', 5, 2)->nullable();
            $table->text('action_rationale')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'recommended_action']);
            $table->index(['branch_id', 'days_to_expiry']);
        });

        Schema::create('patient_refill_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('patient_name', 150);
            $table->string('patient_phone', 30)->nullable();
            $table->foreignId('sales_invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('purchase_date');
            $table->decimal('quantity_supplied', 10, 2);
            $table->decimal('dosage_frequency_per_day', 5, 2)->default(1.0);
            $table->date('predicted_refill_date');
            $table->enum('status', ['pending', 'contacted', 'refilled', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status', 'predicted_refill_date'], 'idx_refill_status_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_refill_reminders');
        Schema::dropIfExists('ai_expiry_risk_analyses');
        Schema::dropIfExists('ai_demand_forecasts');
    }
};
