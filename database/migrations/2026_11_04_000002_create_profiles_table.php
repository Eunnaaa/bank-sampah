<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('full_name', 255);
            $table->string('phone_number', 50)->unique();
            $table->string('rt', 10);
            $table->string('rw', 10);
            $table->string('house_number', 50);
            $table->enum('role', ['admin', 'officer', 'resident'])->default('resident');
            $table->bigInteger('balance')->default(0);
            $table->string('password_hash', 255)->nullable();
            $table->string('pin_hash', 255)->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['phone_number', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
