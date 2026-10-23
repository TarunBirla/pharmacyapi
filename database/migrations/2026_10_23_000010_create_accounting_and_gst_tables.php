<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50); // e.g. FY 2026-2027
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'is_closed']);
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('account_code', 30);
            $table->string('account_name', 150);
            $table->enum('account_type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->string('subtype', 50)->default('general');
            // e.g. cash, bank, accounts_receivable, inventory, accounts_payable, tax_payable, tax_input_credit, sales_revenue, cogs
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->decimal('opening_balance', 15, 2)->default(0.00);
            $table->decimal('current_balance', 15, 2)->default(0.00);
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'account_code']);
            $table->index(['organization_id', 'account_type']);
            $table->index(['organization_id', 'subtype']);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')->nullable()->constrained('financial_years')->nullOnDelete();
            $table->string('entry_number', 50);
            $table->date('entry_date');
            $table->string('source_type', 50)->nullable(); // sales_invoice, sales_return, purchase_invoice, purchase_return, payment, manual_journal
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->text('narration')->nullable();
            $table->decimal('total_debit', 15, 2)->default(0.00);
            $table->decimal('total_credit', 15, 2)->default(0.00);
            $table->boolean('is_posted')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'entry_number']);
            $table->index(['organization_id', 'branch_id', 'entry_date']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['debit', 'credit']);
            $table->decimal('amount', 15, 2);
            $table->string('narration', 255)->nullable();
            $table->timestamps();

            $table->index(['journal_entry_id', 'account_id']);
            $table->index(['account_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('financial_years');
    }
};
