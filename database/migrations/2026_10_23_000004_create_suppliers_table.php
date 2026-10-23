<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 25);
            $table->string('alternate_phone', 25)->nullable();
            $table->string('gstin', 20)->nullable();
            $table->string('pan', 20)->nullable();
            $table->string('dl_number_20b', 50)->nullable();
            $table->string('dl_number_21b', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 50)->default('India');
            $table->unsignedInteger('credit_period_days')->default(30);
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('current_balance', 14, 2)->default(0)->comment('Positive means payable to supplier');
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_ifsc', 20)->nullable();
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'name']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('supplier_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->enum('entry_type', [
                'purchase_invoice',
                'debit_note',
                'payment',
                'opening_balance',
                'adjustment'
            ]);
            $table->nullableMorphs('reference');
            $table->decimal('debit_amount', 14, 2)->default(0)->comment('Reduces payable (payments, debit notes)');
            $table->decimal('credit_amount', 14, 2)->default(0)->comment('Increases payable (purchase invoices)');
            $table->decimal('balance_after', 14, 2);
            $table->string('narration')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'entry_date']);
            $table->index(['organization_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_ledger_entries');
        Schema::dropIfExists('suppliers');
    }
};
