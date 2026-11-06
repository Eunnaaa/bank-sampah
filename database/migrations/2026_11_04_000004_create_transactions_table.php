<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('resident_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignUuid('officer_id')->nullable()->constrained('profiles')->nullOnDelete();
            $table->enum('type', ['deposit', 'withdrawal']);
            $table->bigInteger('total_amount');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['resident_id', 'created_at']);
        });

        Schema::create('transaction_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('waste_categories')->restrictOnDelete();
            $table->decimal('weight', 10, 2);
            $table->integer('price_per_unit_at_transaction');
            $table->bigInteger('subtotal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');
    }
};
