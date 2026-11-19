<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfftakerSale extends Model
{
    use HasUuids;

    protected $table = 'offtaker_sales';

    protected $fillable = [
        'tenant_id',
        'officer_id',
        'buyer_name',
        'sale_date',
        'total_weight',
        'total_revenue',
        'total_cost',
        'gross_profit',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'total_weight' => 'decimal:2',
        'total_revenue' => 'integer',
        'total_cost' => 'integer',
        'gross_profit' => 'integer',
    ];

    #[Scope]
    protected function forTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'officer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OfftakerSaleItem::class, 'offtaker_sale_id');
    }
}
