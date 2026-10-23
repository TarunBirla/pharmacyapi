<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Prescriptions
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('doctor_name')->nullable();
            $table->string('patient_name')->nullable();
            $table->string('prescription_number', 50)->nullable();
            $table->date('prescription_date')->nullable();
            $table->string('image_url')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'verified', 'dispensed', 'expired'])->default('verified');
            $table->timestamps();

            $table->index(['organization_id', 'branch_id']);
        });

        // 2. Cashier Shifts (For POS register tracking & shift closure)
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('shift_code', 50);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->decimal('opening_cash', 12, 2)->default(0);
            $table->decimal('closing_cash', 12, 2)->nullable();
            $table->decimal('expected_cash', 12, 2)->nullable();
            $table->decimal('discrepancy', 12, 2)->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'shift_code']);
            $table->index(['user_id', 'status']);
        });

        // 3. POS Held Bills (For instant hold and resume at checkout counters)
        Schema::create('pos_held_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference_code', 50);
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->json('cart_data');
            $table->decimal('total_amount', 12, 2);
            $table->timestamp('held_at')->useCurrent();
            $table->timestamps();

            $table->unique(['branch_id', 'reference_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_held_bills');
        Schema::dropIfExists('cashier_shifts');
        Schema::dropIfExists('prescriptions');
    }
};
