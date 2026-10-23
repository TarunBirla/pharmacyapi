<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Units of Measurement
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 50); // Tablet, Strip, Bottle, Box, Vial, Ampoule, Tube
            $table->string('code', 20); // TAB, STR, BTL, BOX, VIAL, AMP, TUBE, ML, GM
            $table->boolean('is_fractional_allowed')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        // 2. Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
        });

        // 3. Manufacturers / Pharma Companies
        Schema::create('manufacturers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->string('country')->default('India');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'name']);
        });

        // 4. Brands (e.g. Cipla, Sun Pharma, Dr. Reddy's, Abbott)
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('manufacturer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->index(['organization_id', 'name']);
        });

        // 5. Generic Compositions (e.g. Paracetamol 650mg, Amoxicillin + Clavulanic Acid)
        Schema::create('generic_compositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generic_compositions');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('manufacturers');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('units');
    }
};
