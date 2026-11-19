<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfftakerSaleItem extends Model
{
    use HasUuids;

    protected $table = 'offtaker_sale_items';

    protected $fillable = [
        'offtaker_sale_id',
        'category_id',
        'weight',
        'selling_price_per_unit',
        'buying_price_per_unit',
        'subtotal',
        'cost_subtotal',
        'profit_subtotal',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'selling_price_per_unit' => 'integer',
        'buying_price_per_unit' => 'integer',
        'subtotal' => 'integer',
        'cost_subtotal' => 'integer',
        'profit_subtotal' => 'integer',
    ];

    #[Scope]
    protected function forTenant(Builder $query, string $tenantId): Builder
    {
        return $query->whereHas('sale', fn (Builder $sale): Builder => $sale->where('tenant_id', $tenantId));
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(OfftakerSale::class, 'offtaker_sale_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WasteCategory::class, 'category_id');
    }
}
