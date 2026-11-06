<?php

namespace App\Http\Controllers;

use App\Actions\RecordWithdrawalAction;
use App\Models\AuditLog;
use App\Models\Profile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResidentController extends Controller
{
    public function index(Request $request)
    {
        $query = Profile::forTenant(auth()->user()->tenant_id)->where('role', 'resident');

        if ($request->filled('q')) {
            $q = $request->q;
            $cleanQ = preg_replace('/\D/', '', $q);

            $query->where(function ($sub) use ($q, $cleanQ) {
                $sub->where('full_name', 'ilike', "%{$q}%")
                    ->orWhere('house_number', 'ilike', "%{$q}%")
                    ->orWhere('phone_number', 'like', "%{$q}%");

                if ($cleanQ !== '') {
                    $variations = Profile::getPhoneVariations($cleanQ);
                    $sub->orWhereIn('phone_number', $variations);
                }
            });
        }

        $residents = $query->orderBy('full_name')->paginate(15)->withQueryString();

        return view('admin.warga', compact('residents'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        return DB::transaction(function () use ($request): RedirectResponse|JsonResponse {
            $cleanPhone = Profile::normalizePhoneNumber($request->input('phone_number', ''));
            $pin = trim($request->input('pin', ''));

            if ($pin && ! preg_match('/^\d{4}$/', $pin)) {
                return back()->with('error', 'PIN awal harus berupa 4 digit angka jika diisi.')->withInput();
            }

            $request->merge(['phone_number' => $cleanPhone]);

            $request->validate([
                'full_name' => 'required|string|max:255',
                'phone_number' => 'required|string|unique:profiles,phone_number',
                'rt' => 'required|string|max:10',
                'rw' => 'required|string|max:10',
                'house_number' => 'required|string|max:50',
            ]);

            $tenantId = auth()->user()->tenant_id;

            $resident = Profile::create([
                'id' => Str::uuid(),
                'tenant_id' => $tenantId,
                'full_name' => $request->full_name,
                'phone_number' => $cleanPhone,
                'rt' => $request->rt,
                'rw' => $request->rw,
                'house_number' => $request->house_number,
                'role' => 'resident',
                'balance' => 0,
                'pin_hash' => $pin ? Hash::make($pin) : null,
            ]);

            $activationUrl = $resident->pin_hash ? null : route('register', ['token' => $resident->issueActivationToken()]);

            AuditLog::create([
                'id' => Str::uuid(),
                'actor_id' => auth()->id(),
                'tenant_id' => auth()->user()->tenant_id,
                'actor_name' => auth()->user()->full_name,
                'action' => 'CREATE_RESIDENT',
                'target_type' => 'profile',
                'target_id' => $resident->id,
                'details' => "Pendaftaran nasabah baru: {$resident->full_name} ({$resident->household_code})",
                'ip_address' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Warga {$resident->full_name} berhasil didaftarkan.",
                    'data' => $resident,
                    'activation_url' => $activationUrl,
                ]);
            }

            return back()->with('success', "Warga {$resident->full_name} berhasil didaftarkan sebagai nasabah.")->with('activation_url', $activationUrl);
        });
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        return DB::transaction(function () use ($request, $id): RedirectResponse {
            $resident = Profile::forTenant(auth()->user()->tenant_id)->where('id', $id)->where('role', 'resident')->lockForUpdate()->firstOrFail();

            $cleanPhone = Profile::normalizePhoneNumber($request->input('phone_number', ''));
            $request->merge(['phone_number' => $cleanPhone]);

            $validated = $request->validate([
                'full_name' => 'required|string|max:255',
                'phone_number' => 'required|string|unique:profiles,phone_number,'.$resident->id,
                'rt' => 'required|string|max:10',
                'rw' => 'required|string|max:10',
                'house_number' => 'required|string|max:50',
            ]);

            $oldName = $resident->full_name;
            $resident->update($validated);

            AuditLog::create([
                'id' => Str::uuid(),
                'actor_id' => auth()->id(),
                'tenant_id' => auth()->user()->tenant_id,
                'actor_name' => auth()->user()->full_name,
                'action' => 'UPDATE_RESIDENT',
                'target_type' => 'profile',
                'target_id' => $resident->id,
                'details' => "Memperbarui profil warga {$oldName} -> {$resident->full_name}",
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', "Data warga {$resident->full_name} berhasil diperbarui.");
        });
    }

    public function resetPin(Request $request, string $id): RedirectResponse
    {
        return DB::transaction(function () use ($request, $id): RedirectResponse {
            $resident = Profile::forTenant(auth()->user()->tenant_id)->where('id', $id)->where('role', 'resident')->lockForUpdate()->firstOrFail();
            $newPin = trim($request->input('new_pin', ''));

            if ($newPin !== '') {
                if (! preg_match('/^\d{4}$/', $newPin)) {
                    return back()->with('error', 'PIN baru harus 4 digit angka.');
                }
                $resident->update(['pin_hash' => Hash::make($newPin), 'activation_token_hash' => null, 'activation_expires_at' => null]);
                $msg = "PIN nasabah {$resident->full_name} berhasil diatur ulang.";
            } else {
                $resident->update(['pin_hash' => null]);
                $msg = "PIN nasabah {$resident->full_name} berhasil di-reset. Bagikan undangan aktivasi baru kepada warga.";
            }

            AuditLog::create([
                'id' => Str::uuid(),
                'actor_id' => auth()->id(),
                'tenant_id' => auth()->user()->tenant_id,
                'actor_name' => auth()->user()->full_name,
                'action' => 'RESET_RESIDENT_PIN',
                'target_type' => 'profile',
                'target_id' => $resident->id,
                'details' => "Reset PIN warga {$resident->full_name} oleh admin",
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', $msg)->with('activation_url', $newPin === '' ? route('register', ['token' => $resident->issueActivationToken()]) : null);
        });
    }

    public function invite(string $id): RedirectResponse
    {
        return DB::transaction(function () use ($id): RedirectResponse {
            $resident = Profile::forTenant(auth()->user()->tenant_id)->where('role', 'resident')->lockForUpdate()->findOrFail($id);
            if ($resident->pin_hash) {
                return back()->with('error', 'PIN sudah aktif. Gunakan reset PIN jika diperlukan.');
            }

            $token = $resident->issueActivationToken();
            AuditLog::create([
                'actor_id' => auth()->id(), 'tenant_id' => $resident->tenant_id,
                'actor_name' => auth()->user()->full_name, 'action' => 'RESIDENT_INVITED',
                'target_type' => 'profile', 'target_id' => $resident->id,
                'details' => 'Pengurus menerbitkan ulang undangan aktivasi nasabah.',
                'ip_address' => request()->ip(),
            ]);

            return back()->with('success', 'Undangan berlaku dua hari dan hanya dapat digunakan sekali.')
                ->with('activation_url', route('register', ['token' => $token]));
        });
    }

    public function history(string $id)
    {
        $resident = Profile::forTenant(auth()->user()->tenant_id)->where('id', $id)->where('role', 'resident')->firstOrFail();
        $transactions = $resident->transactions()
            ->with(['items.category', 'officer'])
            ->orderByDesc('created_at')
            ->take(30)
            ->get();

        return response()->json([
            'success' => true,
            'resident' => [
                'id' => $resident->id,
                'full_name' => $resident->full_name,
                'phone_number' => $resident->phone_number,
                'household_code' => $resident->household_code,
                'balance' => $resident->balance,
                'has_pin' => ! empty($resident->pin_hash),
            ],
            'transactions' => $transactions,
        ]);
    }

    public function withdraw(Request $request, RecordWithdrawalAction $action)
    {
        $request->validate([
            'resident_id' => 'required|uuid',
            'request_id' => 'required|uuid',
            'amount' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
            'authorization_code' => 'required|string|size:12|alpha_num:ascii',
            'admin_password' => 'required|string|max:255',
        ]);

        try {
            $result = $action->execute(
                $request->resident_id,
                auth()->id(),
                $request->amount,
                $request->authorization_code,
                $request->admin_password,
                $request->notes,
                $request->request_id
            );

            $request->session()->forget(['success', 'info']);
            $channelLabel = $result['receipt']['channelLabel'] ?? 'Tunai di Posko RT';
            $message = $result['replayed']
                ? 'Transaksi '.$result['transaction_id'].' sudah diproses sebelumnya. Jangan lakukan penyaluran dana lagi.'
                : 'Pencairan Rp '.number_format($result['withdrawn_amount'], 0, ',', '.')." ({$channelLabel}) berhasil dicatat. ID transaksi: ".$result['transaction_id'].'. Selesaikan penyaluran dana sesuai metode yang disetujui.';

            return back()->with($result['replayed'] ? 'info' : 'success', $message)
                ->with('withdrawal_receipt', $result['receipt']);
        } catch (UniqueConstraintViolationException $exception) {
            return back()->with('error', 'Identitas transaksi sudah digunakan. Muat ulang formulir.');
        } catch (ModelNotFoundException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
