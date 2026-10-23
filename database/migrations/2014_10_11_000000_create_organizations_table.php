<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('drug_license_number')->nullable();
            $table->string('gstin')->nullable();
            $table->string('pan')->nullable();
            $table->string('email');
            $table->string('phone');
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('India');
            $table->string('logo_url')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
