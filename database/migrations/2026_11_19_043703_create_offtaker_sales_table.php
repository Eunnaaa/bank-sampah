<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offtaker_sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('officer_id')->nullable()->constrained('profiles')->nullOnDelete();
            $table->string('buyer_name');
            $table->date('sale_date');
            $table->decimal('total_weight', 10, 2);
            $table->bigInteger('total_revenue');
            $table->bigInteger('total_cost');
            $table->bigInteger('gross_profit');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'sale_date']);
        });

        Schema::create('offtaker_sale_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('offtaker_sale_id')->constrained('offtaker_sales')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('waste_categories')->restrictOnDelete();
            $table->decimal('weight', 10, 2);
            $table->integer('selling_price_per_unit');
            $table->integer('buying_price_per_unit');
            $table->bigInteger('subtotal');
            $table->bigInteger('cost_subtotal');
            $table->bigInteger('profit_subtotal');
            $table->timestamps();

            $table->index(['offtaker_sale_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offtaker_sale_items');
        Schema::dropIfExists('offtaker_sales');
    }
};
