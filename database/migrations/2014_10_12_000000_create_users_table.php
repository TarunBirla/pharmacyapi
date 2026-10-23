<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 25)->nullable();
            $table->string('pharmacist_reg_number', 50)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('branch_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_user');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('users');
    }
};
