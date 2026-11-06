<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->index(['tenant_id', 'role']);
            $table->index(['tenant_id', 'role', 'balance']);
        });

        Schema::table('waste_categories', function (Blueprint $table) {
            $table->index(['tenant_id', 'is_active']);
            $table->index('is_active');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['type', 'created_at']);
            $table->index(['resident_id', 'type']);
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->index('transaction_id');
            $table->index('category_id');
            $table->index(['transaction_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropIndex(['transaction_id', 'category_id']);
            $table->dropIndex(['transaction_id']);
            $table->dropIndex(['category_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['type', 'created_at']);
            $table->dropIndex(['resident_id', 'type']);
        });

        Schema::table('waste_categories', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'is_active']);
            $table->dropIndex(['is_active']);
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'role', 'balance']);
            $table->dropIndex(['tenant_id', 'role']);
            $table->dropIndex(['tenant_id']);
        });
    }
};
