<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Doctors Directory
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('specialization')->nullable();
            $table->string('registration_number', 50)->nullable();
            $table->string('phone', 25)->nullable();
            $table->string('email')->nullable();
            $table->string('clinic_hospital_name')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'name']);
        });

        // 2. Customers (Retail Walk-in, Registered Patient, Wholesale Dealer, Hospital)
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->enum('type', ['retail', 'wholesale_dealer', 'hospital', 'institutional'])->default('retail');
            $table->string('phone', 25)->nullable();
            $table->string('email')->nullable();
            $table->string('gstin', 20)->nullable();
            $table->string('pan', 20)->nullable();
            $table->string('dl_number', 50)->nullable();
            $table->decimal('credit_limit', 14, 2)->default(0);
            $table->unsignedInteger('credit_period_days')->default(0);
            $table->decimal('current_balance', 14, 2)->default(0)->comment('Positive means customer owes money');
            $table->unsignedInteger('loyalty_points')->default(0);
            $table->enum('status', ['active', 'inactive', 'credit_hold'])->default('active');
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'phone']);
            $table->index(['organization_id', 'type']);
        });

        // 3. Customer Ledger Entries
        Schema::create('customer_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->enum('entry_type', [
                'sales_invoice',
                'credit_note',
                'payment',
                'opening_balance',
                'adjustment'
            ]);
            $table->nullableMorphs('reference');
            $table->decimal('debit_amount', 14, 2)->default(0)->comment('Increases receivable (sales invoice)');
            $table->decimal('credit_amount', 14, 2)->default(0)->comment('Decreases receivable (payments, credit notes)');
            $table->decimal('balance_after', 14, 2);
            $table->string('narration')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'entry_date']);
            $table->index(['organization_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_ledger_entries');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('doctors');
    }
};
