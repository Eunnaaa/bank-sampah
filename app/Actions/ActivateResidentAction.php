<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Profile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ActivateResidentAction
{
    public function execute(string $token, string $pin, ?string $phone = null): Profile
    {
        return DB::transaction(function () use ($token, $pin, $phone): Profile {
            $resident = Profile::where('activation_token_hash', hash('sha256', $token))
                ->where('role', 'resident')->whereNull('pin_hash')
                ->whereNotNull('tenant_id')->where('activation_expires_at', '>', now())
                ->lockForUpdate()->first();

            if (! $resident || ! preg_match('/^\d{4}$/', $pin)
                || ($phone !== null && ! in_array($resident->phone_number, Profile::getPhoneVariations($phone), true))) {
                throw ValidationException::withMessages(['token' => 'Undangan tidak valid, sudah digunakan, atau kedaluwarsa. Hubungi pengurus RT.']);
            }

            $resident->update([
                'pin_hash' => Hash::make($pin),
                'activation_token_hash' => null,
                'activation_expires_at' => null,
            ]);

            AuditLog::create([
                'actor_id' => $resident->id, 'tenant_id' => $resident->tenant_id,
                'actor_name' => $resident->full_name, 'action' => 'RESIDENT_PIN_ACTIVATED',
                'target_type' => 'profile', 'target_id' => $resident->id,
                'details' => 'Aktivasi PIN menggunakan undangan sekali pakai dari pengurus RT.',
                'ip_address' => request()->ip(),
            ]);

            return $resident;
        });
    }
}
