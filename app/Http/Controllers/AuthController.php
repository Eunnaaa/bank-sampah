<?php

namespace App\Http\Controllers;

use App\Actions\ActivateResidentAction;
use App\Models\AuditLog;
use App\Models\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        // Jika sudah login sebagai RT atau Petugas
        if (Auth::check()) {
            if (in_array(Auth::user()->role, ['admin', 'officer'])) {
                return redirect()->route('admin.dashboard');
            }

            if (Auth::user()->role === 'resident') {
                return redirect()->route('warga.dashboard');
            }
        }

        // Jika memiliki sesi login warga
        if ($request->session()->has('resident_id')) {
            $resident = Profile::where('id', $request->session()->get('resident_id'))
                ->where('role', 'resident')
                ->first();

            if ($resident) {
                return redirect()->route('warga.dashboard');
            }
        }

        return view('auth.login', [
            'activationRequired' => false,
            'phone' => old('phone', ''),
            'pin' => '',
        ]);
    }

    public function login(Request $request): View|RedirectResponse
    {
        $phoneInput = (string) $request->input('phone', $request->input('phone_number', ''));
        $cleanPhone = preg_replace('/\D/', '', $phoneInput);
        $secret = trim((string) $request->input('password', $request->input('pin', '')));

        if (strlen($cleanPhone) < 8) {
            return back()->with('error', 'Masukkan nomor WhatsApp yang valid.')->withInput($request->only('phone', 'phone_number'));
        }

        if (empty($secret)) {
            return back()->with('error', 'Masukkan kata sandi atau PIN keamanan akun Anda.')->withInput($request->only('phone', 'phone_number'));
        }

        $variations = Profile::getPhoneVariations($phoneInput);
        $user = Profile::whereIn('phone_number', $variations)->first();

        if (! $user) {
            return back()->with('error', 'Nomor WhatsApp belum terdaftar di sistem. Hubungi pengurus RT untuk mendapatkan undangan pendaftaran.')->withInput($request->only('phone', 'phone_number'));
        }

        // Skenario 1: Akun Pengurus RT (admin) atau Petugas Lapangan (officer)
        if (in_array($user->role, ['admin', 'officer'])) {
            if (! $user->password_hash || ! Hash::check($secret, $user->password_hash)) {
                return back()->with('error', 'Kata sandi pengurus / petugas tidak sesuai.')->withInput($request->only('phone', 'phone_number'));
            }

            if (app()->isProduction() && in_array($secret, ['admin123456', 'petugas123456'], true)) {
                return back()->with('error', 'Akun demo tidak dapat digunakan di produksi. Hubungi pengurus untuk mengganti kata sandi.');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            AuditLog::create([
                'id' => Str::uuid(),
                'actor_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'actor_name' => $user->full_name,
                'action' => 'STAFF_LOGIN',
                'target_type' => 'auth',
                'target_id' => $user->id,
                'details' => "Pengurus/Petugas {$user->full_name} ({$user->role}) berhasil masuk ke sistem.",
                'ip_address' => $request->ip(),
            ]);

            Auth::login($user);
            $request->session()->put('session_version', $user->session_version);

            return redirect()->intended(route('admin.dashboard'))->with('success', "Selamat datang, {$user->full_name}!");
        }

        // Skenario 2: Akun Nasabah Warga (resident)
        if ($user->role === 'resident') {
            // Jika akun warga belum diaktivasi PIN perdana
            if (! $user->pin_hash) {
                if (! preg_match('/^\d{4}$/', $secret)) {
                    return back()->with('error', 'PIN keamanan harus terdiri dari 4 digit angka.');
                }

                return view('auth.login', [
                    'activationRequired' => true,
                    'phone' => $user->phone_number,
                    'pin' => $secret,
                    'resident' => $user,
                    'message' => 'Akun nasabah warga ditemukan. Masukkan kode undangan sekali pakai dari pengurus RT untuk mengunci PIN perdana.',
                ]);
            }

            // Periksa PIN atau Password nasabah
            $isValidPin = Hash::check($secret, $user->pin_hash);
            $isValidPassword = $user->password_hash && Hash::check($secret, $user->password_hash);

            if (! $isValidPin && ! $isValidPassword) {
                return back()->with('error', 'PIN keamanan nasabah salah. Hubungi pengurus RT jika Anda lupa PIN.')->withInput($request->only('phone', 'phone_number'));
            }

            AuditLog::create([
                'id' => Str::uuid(),
                'actor_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'actor_name' => $user->full_name,
                'action' => 'RESIDENT_PORTAL_LOGIN',
                'target_type' => 'profile',
                'target_id' => $user->id,
                'details' => "Nasabah warga {$user->full_name} berhasil masuk dashboard mandiri.",
                'ip_address' => $request->ip(),
            ]);

            $this->loginResident($request, $user);

            return redirect()->intended(route('warga.dashboard'))->with('success', "Selamat datang kembali, {$user->full_name}!");
        }

        return back()->with('error', 'Peran akun tidak dikenali oleh sistem.')->withInput($request->only('phone', 'phone_number'));
    }

    public function showRegister(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            if (in_array(Auth::user()->role, ['admin', 'officer'])) {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('warga.dashboard');
        }

        if ($request->session()->has('resident_id')) {
            return redirect()->route('warga.dashboard');
        }

        return view('auth.register', ['token' => (string) $request->query('token', '')]);
    }

    public function register(Request $request, ActivateResidentAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|size:64',
            'pin' => 'required|digits:4|confirmed',
        ]);
        $resident = $action->execute($validated['token'], $validated['pin']);
        $this->loginResident($request, $resident);

        return redirect()->route('warga.dashboard')->with('success', 'Pendaftaran berhasil! Rekening tabungan Anda telah aktif.');
    }

    public function activatePin(Request $request, ActivateResidentAction $action): RedirectResponse
    {
        $request->validate(['pin' => 'required|digits:4']);
        try {
            $resident = $action->execute((string) $request->input('token', ''), (string) $request->pin, (string) $request->input('phone', ''));
        } catch (ValidationException $exception) {
            return back()->with('error', $exception->getMessage());
        }
        $this->loginResident($request, $resident);

        return redirect()->route('warga.dashboard')->with('success', 'PIN keamanan berhasil diaktifkan.');
    }

    private function loginResident(Request $request, Profile $resident): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('resident_id', $resident->id);
        Auth::login($resident);
        $request->session()->put('session_version', $resident->session_version);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->forget('resident_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah berhasil keluar dari sistem.');
    }
}
