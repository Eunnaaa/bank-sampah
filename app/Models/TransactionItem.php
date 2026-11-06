<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    #[Scope]
    protected function forTenant(Builder $query, string $tenantId): Builder
    {
        return $query->whereHas('transaction.resident', fn (Builder $resident): Builder => $resident->where('tenant_id', $tenantId));
    }

    use HasUuids;

    protected $table = 'transaction_items';

    protected $fillable = [
        'transaction_id', 'category_id', 'weight',
        'price_per_unit_at_transaction', 'subtotal',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'price_per_unit_at_transaction' => 'integer',
        'subtotal' => 'integer',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function category()
    {
        return $this->belongsTo(WasteCategory::class, 'category_id');
    }
}
