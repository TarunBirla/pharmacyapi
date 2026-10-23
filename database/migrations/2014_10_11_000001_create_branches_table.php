<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->enum('business_type', ['retail', 'wholesale', 'both'])->default('both');
            $table->string('dl_number_20b')->nullable()->comment('Retail Drug License');
            $table->string('dl_number_21b')->nullable()->comment('Wholesale Drug License');
            $table->string('gstin')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('invoice_prefix', 10)->default('INV');
            $table->unsignedInteger('next_invoice_number')->default(1);
            $table->boolean('is_main_branch')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
