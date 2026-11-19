<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('savings_type')->default('regular')->after('role');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('collection_schedule')->nullable()->after('status');
            $table->date('next_collection_date')->nullable()->after('collection_schedule');
            $table->string('collection_note')->nullable()->after('next_collection_date');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('savings_type');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['collection_schedule', 'next_collection_date', 'collection_note']);
        });
    }
};
