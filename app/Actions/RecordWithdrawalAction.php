<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RecordWithdrawalAction
{
    public function execute(string $residentId, string $adminId, int $amount, string $authorizationCode, string $adminPassword, ?string $notes = null, ?string $requestId = null): array
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Nominal penarikan harus lebih dari Rp 0.');
        }

        return DB::transaction(function () use ($residentId, $adminId, $amount, $authorizationCode, $adminPassword, $notes, $requestId): array {
            $actor = Profile::whereIn('role', ['admin'])->findOrFail($adminId);
            abort_unless($actor->tenant_id, 403);
            if (! $actor->password_hash || ! Hash::check($adminPassword, $actor->password_hash)) {
                throw new \InvalidArgumentException('Kata sandi admin tidak sesuai.');
            }

            $resident = Profile::forTenant($actor->tenant_id)->where('id', $residentId)
                ->where('role', 'resident')
                ->lockForUpdate()
                ->firstOrFail();

            $codeHash = hash('sha256', $authorizationCode);
            $requestHash = hash('sha256', json_encode(['withdrawal', $residentId, $adminId, $amount, $notes, $codeHash], JSON_THROW_ON_ERROR));
            if ($existing = Transaction::replayRequest($requestId, $requestHash)) {
                return ['transaction_id' => $existing->id, 'withdrawn_amount' => $existing->total_amount, 'remaining_balance' => $existing->balance_after, 'replayed' => true, 'receipt' => $existing->receiptData()];
            }

            $approval = $resident->withdrawal_approval;
            if (! $approval || ! hash_equals($approval['code_hash'], $codeHash)
                || $approval['amount'] !== $amount || ! CarbonImmutable::parse($approval['expires_at'])->isFuture()) {
                throw new \InvalidArgumentException('Kode persetujuan warga tidak valid, nominal berbeda, sudah digunakan, atau kedaluwarsa.');
            }

            if ($resident->balance < $amount) {
                throw new \InvalidArgumentException('Saldo tidak mencukupi. Saldo saat ini: Rp '.number_format($resident->balance, 0, ',', '.'));
            }

            $tx = Transaction::create([
                'id' => Str::uuid(),
                'resident_id' => $resident->id,
                'request_id' => $requestId,
                'request_hash' => $requestHash,
                'officer_id' => $adminId,
                'type' => 'withdrawal',
                'total_amount' => $amount,
                'notes' => $notes ?: 'Pencairan saldo tabungan sampah',
                'resident_approved_at' => $approval['approved_at'],
            ]);

            $resident->withdrawal_approval = null;
            $resident->save();
            $resident->decrement('balance', $amount);
            $tx->update(['balance_after' => $resident->balance]);
            $tx->update(['receipt_snapshot' => $tx->receiptData()]);

            AuditLog::create([
                'id' => Str::uuid(),
                'actor_id' => $adminId,
                'tenant_id' => $actor->tenant_id,
                'actor_name' => auth()->user()?->full_name ?? 'Administrator',
                'action' => 'RECORD_WITHDRAWAL',
                'target_type' => 'transaction',
                'target_id' => $tx->id,
                'details' => 'Pencairan saldo tunai Rp '.number_format($amount, 0, ',', '.').' (Sisa saldo: Rp '.number_format($resident->balance, 0, ',', '.').')',
                'ip_address' => request()->ip(),
            ]);

            return [
                'transaction_id' => $tx->id,
                'withdrawn_amount' => $amount,
                'remaining_balance' => $resident->balance,
                'replayed' => false,
                'receipt' => $tx->receiptData(),
            ];
        });
    }
}
