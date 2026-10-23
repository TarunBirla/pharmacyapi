<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Sales Orders (Wholesale Orders & Institutional Contracts)
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('order_number', 50);
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2)->default(0);
            $table->enum('status', [
                'quotation',
                'confirmed',
                'allocated',
                'dispatched',
                'completed',
                'cancelled'
            ])->default('confirmed');
            $table->string('transport_name', 100)->nullable();
            $table->string('lr_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'order_number']);
            $table->index(['customer_id', 'order_date']);
        });

        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity_ordered', 12, 4);
            $table->decimal('quantity_allocated', 12, 4)->default(0);
            $table->decimal('quantity_invoiced', 12, 4)->default(0);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(12.00);
            $table->decimal('total_amount', 14, 2);
            $table->timestamps();
        });

        // 2. Sales Invoices (POS Retail Invoices & Wholesale Invoices)
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('prescription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cashier_shift_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number', 50);
            $table->enum('invoice_type', ['retail', 'wholesale'])->default('retail');
            $table->date('invoice_date');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('item_discount_amount', 14, 2)->default(0);
            $table->decimal('bill_discount_percent', 5, 2)->default(0);
            $table->decimal('bill_discount_amount', 14, 2)->default(0);
            $table->decimal('taxable_amount', 14, 2);
            $table->decimal('cgst_amount', 14, 2)->default(0);
            $table->decimal('sgst_amount', 14, 2)->default(0);
            $table->decimal('igst_amount', 14, 2)->default(0);
            $table->decimal('total_tax', 14, 2)->default(0);
            $table->decimal('round_off', 6, 2)->default(0);
            $table->decimal('net_amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('due_amount', 14, 2)->default(0);
            $table->enum('payment_status', ['paid', 'partially_paid', 'unpaid', 'credit'])->default('paid');
            $table->enum('status', ['posted', 'cancelled', 'returned'])->default('posted');
            $table->string('patient_name', 150)->nullable();
            $table->string('doctor_name', 150)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'invoice_number']);
            $table->index(['customer_id', 'invoice_date']);
            $table->index(['branch_id', 'invoice_type', 'invoice_date']);
        });

        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_batch_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number', 60);
            $table->date('expiry_date');
            $table->decimal('quantity', 12, 4);
            $table->decimal('free_quantity', 12, 4)->default(0);
            $table->decimal('mrp', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('taxable_value', 14, 2);
            $table->decimal('gst_rate', 5, 2);
            $table->decimal('cgst_amount', 12, 2)->default(0);
            $table->decimal('sgst_amount', 12, 2)->default(0);
            $table->decimal('igst_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 14, 2);
            $table->timestamps();
        });

        // 3. Customer Payments (Split Payment Support: Cash, UPI, Card, Credit)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_number', 50);
            $table->date('payment_date');
            $table->decimal('amount', 14, 2);
            $table->enum('payment_mode', ['cash', 'card', 'upi', 'bank_transfer', 'credit_note']);
            $table->string('transaction_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'payment_number']);
            $table->index(['sales_invoice_id', 'payment_mode']);
        });

        // 4. Sales Returns & Credit Notes
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_invoice_id')->constrained()->cascadeOnDelete();
            $table->string('credit_note_number', 50);
            $table->date('return_date');
            $table->string('reason');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2);
            $table->enum('refund_mode', ['credit_note', 'cash_refund'])->default('cash_refund');
            $table->enum('refund_status', ['refunded', 'credited'])->default('refunded');
            $table->enum('status', ['approved', 'cancelled'])->default('approved');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['branch_id', 'credit_note_number']);
            $table->index(['sales_invoice_id', 'return_date']);
        });

        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_invoice_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_batch_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity_returned', 12, 4);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 14, 2);
            $table->enum('condition', ['restockable', 'damaged', 'expired'])->default('restockable');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_items');
        Schema::dropIfExists('sales_returns');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sales_invoice_items');
        Schema::dropIfExists('sales_invoices');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
    }
};
