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
        Schema::table('profiles', function (Blueprint $table): void {
            $table->uuid('session_version')->nullable();
            $table->json('withdrawal_approval')->nullable();
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->timestamp('resident_approved_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropColumn('resident_approved_at');
        });

        Schema::table('profiles', function (Blueprint $table): void {
            $table->dropColumn(['session_version', 'withdrawal_approval']);
        });
    }
};
