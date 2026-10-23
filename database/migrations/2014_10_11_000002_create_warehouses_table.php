<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30);
            $table->enum('type', [
                'retail_dispensary',
                'main_godown',
                'cold_storage',
                'quarantine',
                'damaged'
            ])->default('retail_dispensary');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'code']);
            $table->index(['organization_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
