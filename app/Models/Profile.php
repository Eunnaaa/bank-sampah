<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Profile extends Authenticatable
{
    #[Scope]
    protected function forTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    use HasUuids, Notifiable;

    protected $table = 'profiles';

    protected $fillable = [
        'tenant_id', 'full_name', 'phone_number', 'rt', 'rw',
        'house_number', 'role', 'balance', 'password_hash', 'pin_hash', 'activation_token_hash', 'activation_expires_at',
    ];

    protected $hidden = [
        'password_hash', 'pin_hash', 'remember_token', 'activation_token_hash', 'activation_expires_at',
        'session_version', 'withdrawal_approval',
    ];

    protected $casts = ['activation_expires_at' => 'datetime', 'withdrawal_approval' => 'array'];

    protected static function booted(): void
    {
        static::updating(function (Profile $profile): void {
            if ($profile->isDirty(['password_hash', 'pin_hash'])) {
                $profile->session_version = (string) Str::uuid();
                $profile->withdrawal_approval = null;
            }
        });
    }

    public function issueActivationToken(): string
    {
        $token = Str::random(64);
        $this->update([
            'activation_token_hash' => hash('sha256', $token),
            'activation_expires_at' => now()->addDays(2),
        ]);

        return $token;
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'resident_id');
    }

    public function officerTransactions()
    {
        return $this->hasMany(Transaction::class, 'officer_id');
    }

    public function getHouseholdCodeAttribute(): string
    {
        return "Blok/No. {$this->house_number} (RT {$this->rt}/RW {$this->rw})";
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOfficer(): bool
    {
        return in_array($this->role, ['admin', 'officer']);
    }

    public function getTotalWasteWeightKgAttribute(): float
    {
        return (float) TransactionItem::whereHas('transaction', function ($q) {
            $q->where('resident_id', $this->id)->where('type', 'deposit');
        })->sum('weight');
    }

    public function getTotalCo2SavedKgAttribute(): float
    {
        return round($this->total_waste_weight_kg * 2.9, 1);
    }

    public function getDepositCountAttribute(): int
    {
        return $this->transactions()->where('type', 'deposit')->count();
    }

    public function getWithdrawalCountAttribute(): int
    {
        return $this->transactions()->where('type', 'withdrawal')->count();
    }

    /**
     * @return array<int, array{name: string, unit: string, weight: float, subtotal: int}>
     */
    public function getCategoryBreakdownAttribute(): array
    {
        $items = TransactionItem::with('category')
            ->whereHas('transaction', function ($q) {
                $q->where('resident_id', $this->id)->where('type', 'deposit');
            })
            ->get();

        $grouped = [];
        foreach ($items as $item) {
            $catName = $item->category?->name ?? 'Komoditas Lainnya';
            $unit = $item->category?->unit ?? 'kg';
            if (! isset($grouped[$catName])) {
                $grouped[$catName] = [
                    'name' => $catName,
                    'unit' => $unit,
                    'weight' => 0.0,
                    'subtotal' => 0,
                ];
            }
            $grouped[$catName]['weight'] += (float) $item->weight;
            $grouped[$catName]['subtotal'] += (int) $item->subtotal;
        }

        return array_values($grouped);
    }

    public static function normalizePhoneNumber(?string $phone): string
    {
        if (! $phone) {
            return '';
        }

        $clean = preg_replace('/\D/', '', $phone);
        if (str_starts_with($clean, '62')) {
            return '0'.substr($clean, 2);
        }
        if (! str_starts_with($clean, '0') && strlen($clean) >= 8) {
            return '0'.$clean;
        }

        return $clean;
    }

    /**
     * @return array<int, string>
     */
    public static function getPhoneVariations(?string $phone): array
    {
        if (! $phone) {
            return [];
        }

        $clean = preg_replace('/\D/', '', $phone);
        if ($clean === '') {
            return [];
        }

        $variations = [$clean];
        if (str_starts_with($clean, '62')) {
            $variations[] = '0'.substr($clean, 2);
            $variations[] = substr($clean, 2);
        } elseif (str_starts_with($clean, '0')) {
            $variations[] = substr($clean, 1);
            $variations[] = '62'.substr($clean, 1);
        } else {
            $variations[] = '0'.$clean;
            $variations[] = '62'.$clean;
        }

        return array_values(array_unique(array_filter($variations)));
    }
}
