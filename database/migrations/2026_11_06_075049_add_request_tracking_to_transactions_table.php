<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->uuid('request_id')->nullable()->unique();
            $table->string('request_hash', 64)->nullable();
            $table->bigInteger('balance_after')->nullable();
            $table->json('receipt_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropUnique(['request_id']);
            $table->dropColumn(['request_id', 'request_hash', 'balance_after', 'receipt_snapshot']);
        });
    }
};
