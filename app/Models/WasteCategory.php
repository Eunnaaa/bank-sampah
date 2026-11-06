<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WasteCategory extends Model
{
    #[Scope]
    protected function forTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    use HasUuids;

    protected $table = 'waste_categories';

    protected $fillable = [
        'tenant_id', 'name', 'unit', 'price_per_unit', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price_per_unit' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class, 'category_id');
    }
}
