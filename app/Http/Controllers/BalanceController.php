<?php

namespace App\Http\Controllers;

use App\Actions\ActivateResidentAction;
use App\Models\AuditLog;
use App\Models\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class BalanceController extends Controller
{
    public function index()
    {
        return view('cek-saldo', ['result' => null, 'activationRequired' => false]);
    }

    public function search(Request $request, ActivateResidentAction $action)
    {
        $phoneInput = $request->input('phone', '');
        $cleanPhone = preg_replace('/\D/', '', $phoneInput);
        $pin = trim($request->input('pin', ''));

        if (strlen($cleanPhone) < 8) {
            return back()->with('error', 'Masukkan nomor WhatsApp yang terdaftar.')->withInput($request->only('phone'));
        }

        if (! preg_match('/^\d{4}$/', $pin)) {
            return back()->with('error', 'PIN keamanan harus berupa 4 digit angka.')->withInput($request->only('phone'));
        }

        $variations = Profile::getPhoneVariations($phoneInput);

        $resident = Profile::whereIn('phone_number', $variations)
            ->where('role', 'resident')
            ->first();

        if (! $resident) {
            return back()->with('error', 'Nomor WhatsApp belum terdaftar. Periksa kembali atau hubungi pengurus RT.')->withInput($request->only('phone'));
        }

        if (! $resident->pin_hash) {
            if (! $request->filled('token')) {
                return view('cek-saldo', [
                    'result' => null, 'activationRequired' => true,
                    'phone' => $cleanPhone, 'pin' => $pin,
                    'message' => 'Masukkan kode undangan dari pengurus RT untuk aktivasi PIN.',
                ]);
            }
            try {
                $resident = $action->execute((string) $request->token, $pin, $phoneInput);
            } catch (ValidationException $exception) {
                return back()->with('error', $exception->getMessage());
            }
        } elseif (! Hash::check($pin, $resident->pin_hash)) {
            return back()->with('error', 'PIN keamanan salah. Silakan coba kembali atau hubungi pengurus jika lupa.');
        }

        // Ambil riwayat mutasi transaksi
        $transactions = $resident->transactions()
            ->with(['items.category', 'officer'])
            ->orderByDesc('created_at')
            ->take(15)
            ->get();

        return view('cek-saldo', [
            'result' => $resident,
            'transactions' => $transactions,
            'activationRequired' => false,
        ]);
    }

    public function changePin(Request $request): RedirectResponse
    {
        return DB::transaction(function () use ($request): RedirectResponse {
            $request->validate([
                'resident_id' => 'required|uuid',
                'current_pin' => 'required|digits:4',
                'new_pin' => 'required|digits:4|different:current_pin',
            ]);

            $resident = Profile::where('id', $request->resident_id)->where('role', 'resident')->lockForUpdate()->firstOrFail();

            if (! $resident->pin_hash || ! Hash::check($request->current_pin, $resident->pin_hash)) {
                return back()->with('error', 'PIN lama Anda tidak sesuai.');
            }

            $resident->update(['pin_hash' => Hash::make($request->new_pin)]);

            AuditLog::create([
                'actor_id' => $resident->id, 'tenant_id' => $resident->tenant_id,
                'actor_name' => $resident->full_name, 'action' => 'RESIDENT_CHANGE_PIN',
                'target_type' => 'profile', 'target_id' => $resident->id,
                'details' => 'Nasabah mengganti PIN melalui halaman cek saldo. Seluruh sesi lama dicabut.',
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', 'PIN keamanan 4 digit berhasil diperbarui.');
        });
    }
}
