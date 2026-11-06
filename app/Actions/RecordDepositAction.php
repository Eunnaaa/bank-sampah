<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\Transaction;
use App\Models\WasteCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordDepositAction
{
    public function execute(string $residentId, string $officerId, array $items, ?string $notes = null, ?string $requestId = null): array
    {
        return DB::transaction(function () use ($residentId, $officerId, $items, $notes, $requestId): array {
            // 1. Lock saldo warga (Pencegahan Race Condition)
            $actor = Profile::whereIn('role', ['admin', 'officer'])->findOrFail($officerId);
            abort_unless($actor->tenant_id, 403);

            $resident = Profile::forTenant($actor->tenant_id)->where('id', $residentId)
                ->where('role', 'resident')
                ->lockForUpdate()
                ->firstOrFail();

            $requestHash = hash('sha256', json_encode(['deposit', $residentId, $officerId, $items, $notes], JSON_THROW_ON_ERROR));
            if ($existing = Transaction::replayRequest($requestId, $requestHash)) {
                return ['transaction_id' => $existing->id, 'total_amount' => $existing->total_amount, 'new_balance' => $existing->balance_after, 'receipt' => $existing->receiptData()];
            }

            $totalAmount = 0;
            $preparedItems = [];
            $weights = [];

            foreach ($items as $item) {
                $hundredths = (int) round((float) $item['weight'] * 100);
                if ($hundredths <= 0) {
                    throw new \InvalidArgumentException('Bobot setoran harus lebih dari nol.');
                }
                $weights[$item['category_id']] = ($weights[$item['category_id']] ?? 0) + $hundredths;
                if ($weights[$item['category_id']] > 9999999999) {
                    throw new \InvalidArgumentException('Total bobot satu komoditas melebihi batas penyimpanan.');
                }
            }

            // 2. Snapshot harga saat timbang
            foreach ($weights as $categoryId => $hundredths) {
                $category = WasteCategory::forTenant($actor->tenant_id)->where('id', $categoryId)
                    ->where('is_active', true)
                    ->sharedLock()
                    ->firstOrFail();

                $weight = $hundredths / 100;
                $subtotal = intdiv($hundredths, 100) * $category->price_per_unit
                    + intdiv(($hundredths % 100) * $category->price_per_unit + 50, 100);

                if ($subtotal <= 0) {
                    throw new \InvalidArgumentException("Nilai setoran {$category->name} terlalu kecil (Rp 0). Tambahkan bobot timbangannya.");
                }

                if ($totalAmount > PHP_INT_MAX - $subtotal) {
                    throw new \InvalidArgumentException('Total setoran melebihi batas penyimpanan.');
                }
                $totalAmount += $subtotal;
                $preparedItems[] = [
                    'id' => Str::uuid(),
                    'category_id' => $category->id,
                    'weight' => $weight,
                    'price_per_unit_at_transaction' => $category->price_per_unit,
                    'subtotal' => $subtotal,
                ];
            }

            if ($resident->balance > PHP_INT_MAX - $totalAmount) {
                throw new \InvalidArgumentException('Saldo baru melebihi batas penyimpanan.');
            }

            // 3. Simpan header transaksi
            $tx = Transaction::create([
                'id' => Str::uuid(),
                'resident_id' => $resident->id,
                'request_id' => $requestId,
                'request_hash' => $requestHash,
                'officer_id' => $officerId,
                'type' => 'deposit',
                'total_amount' => $totalAmount,
                'notes' => $notes,
            ]);

            // 4. Simpan snapshot detail item
            foreach ($preparedItems as $prep) {
                $tx->items()->create($prep);
            }

            // 5. Mutasi penambahan saldo
            $resident->increment('balance', $totalAmount);
            $tx->update(['balance_after' => $resident->balance]);
            $tx->update(['receipt_snapshot' => $tx->receiptData()]);

            // 6. Log Audit
            AuditLog::create([
                'id' => Str::uuid(),
                'actor_id' => $officerId,
                'tenant_id' => $actor->tenant_id,
                'actor_name' => auth()->user()?->full_name ?? 'Petugas Lapangan',
                'action' => 'RECORD_DEPOSIT',
                'target_type' => 'transaction',
                'target_id' => $tx->id,
                'details' => 'Pencatatan setoran sampah Rp '.number_format($totalAmount, 0, ',', '.').' (Saldo baru: Rp '.number_format($resident->balance, 0, ',', '.').')',
                'ip_address' => request()->ip(),
            ]);

            return [
                'transaction_id' => $tx->id,
                'total_amount' => $totalAmount,
                'new_balance' => $resident->balance,
                'receipt' => $tx->receiptData(),
            ];
        });
    }
}
