<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    #[Scope]
    protected function forTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    use HasUuids;

    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $casts = ['created_at' => 'datetime'];

    protected $fillable = [
        'tenant_id', 'actor_id', 'actor_name', 'action', 'target_type', 'target_id', 'details', 'ip_address',
    ];
}
