<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    #[Scope]
    protected function forTenant(Builder $query, string $tenantId): Builder
    {
        return $query->whereHas('resident', fn (Builder $resident): Builder => $resident->where('tenant_id', $tenantId));
    }

    use HasUuids;

    protected $table = 'transactions';

    protected $fillable = [
        'resident_id', 'officer_id', 'type', 'total_amount', 'notes',
        'request_id', 'request_hash', 'balance_after', 'receipt_snapshot',
        'resident_approved_at',
    ];

    protected $hidden = ['request_hash'];

    protected $casts = [
        'total_amount' => 'integer',
        'balance_after' => 'integer',
        'receipt_snapshot' => 'array',
        'resident_approved_at' => 'datetime',
    ];

    #[Scope]
    protected function inPeriod(Builder $query, string $period): Builder
    {
        $now = CarbonImmutable::now(config('app.business_timezone'));
        $start = match ($period) {
            'today' => $now->startOfDay(),
            'month' => $now->startOfMonth(),
            '3months' => $now->subMonthsNoOverflow(3)->startOfDay(),
            'year' => $now->startOfYear(),
            default => null,
        };

        return $start ? $query->where($query->getModel()->qualifyColumn('created_at'), '>=', $start->utc()) : $query;
    }

    #[Scope]
    protected function onLocalDay(Builder $query, CarbonInterface $day): Builder
    {
        $start = CarbonImmutable::instance($day)->setTimezone(config('app.business_timezone'))->startOfDay();
        $column = $query->getModel()->qualifyColumn('created_at');

        return $query->where($column, '>=', $start->utc())->where($column, '<', $start->addDay()->utc());
    }

    public static function replayRequest(?string $requestId, string $requestHash): ?self
    {
        if ($requestId === null) {
            return null;
        }

        $transaction = static::where('request_id', $requestId)->first();
        if ($transaction && ! hash_equals($transaction->request_hash, $requestHash)) {
            throw new \InvalidArgumentException('Identitas transaksi sudah digunakan untuk permintaan berbeda. Muat ulang formulir.');
        }

        return $transaction;
    }

    /** @return array<string, mixed> */
    public function receiptData(): array
    {
        if ($this->receipt_snapshot !== null) {
            return $this->receipt_snapshot;
        }

        $this->loadMissing(['resident', 'officer', 'items.category']);

        return [
            'id' => $this->id, 'type' => $this->type,
            'residentName' => $this->resident?->full_name ?? '-',
            'residentPhone' => $this->resident?->phone_number ?? '',
            'householdCode' => $this->resident?->household_code ?? '-',
            'officerName' => $this->officer?->full_name ?? 'Petugas RT',
            'items' => $this->items->map(fn (TransactionItem $item): array => [
                'name' => $item->category?->name ?? 'Komoditas',
                'weight' => (float) $item->weight, 'unit' => $item->category?->unit ?? 'kg',
                'price' => (int) $item->price_per_unit_at_transaction, 'subtotal' => (int) $item->subtotal,
            ])->all(),
            'totalAmount' => $this->total_amount, 'newBalance' => $this->balance_after,
            'notes' => $this->notes,
            'residentApprovedAt' => $this->resident_approved_at?->toIso8601String(),
            'date' => $this->created_at->timezone(config('app.business_timezone'))->translatedFormat('d F Y, H:i'),
        ];
    }

    public function resident()
    {
        return $this->belongsTo(Profile::class, 'resident_id');
    }

    public function officer()
    {
        return $this->belongsTo(Profile::class, 'officer_id');
    }

    public function items()
    {
        return $this->hasMany(TransactionItem::class, 'transaction_id');
    }
}
