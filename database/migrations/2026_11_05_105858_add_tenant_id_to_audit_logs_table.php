<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->index(['tenant_id', 'created_at']);
        });

        DB::table('audit_logs')->update([
            'tenant_id' => DB::table('profiles')->select('tenant_id')
                ->whereRaw('CAST(profiles.id AS VARCHAR) = audit_logs.actor_id')
                ->limit(1),
        ]);
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'created_at']);
            $table->dropConstrainedForeignId('tenant_id');
        });
    }
};
