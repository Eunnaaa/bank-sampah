<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfficerRequest;
use App\Models\AuditLog;
use App\Models\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OfficerController extends Controller
{
    public function index(): View
    {
        $officers = Profile::forTenant(auth()->user()->tenant_id)->where('role', 'officer')
            ->orderBy('full_name')
            ->get();

        return view('admin.petugas', compact('officers'));
    }

    public function store(StoreOfficerRequest $request): RedirectResponse
    {
        return DB::transaction(function () use ($request): RedirectResponse {
            $validated = $request->validated();
            $tenantId = auth()->user()->tenant_id;

            // Akun petugas HANYA bisa dibuat oleh RT dan perannya dikunci 'officer'
            $officer = Profile::create([
                'id' => Str::uuid(),
                'tenant_id' => $tenantId,
                'full_name' => $validated['full_name'],
                'phone_number' => $validated['phone_number'],
                'rt' => $request->input('rt', auth()->user()->rt ?? '05'),
                'rw' => $request->input('rw', auth()->user()->rw ?? '08'),
                'house_number' => $validated['house_number'],
                'role' => 'officer', // STRICTLY OFFICER - RT TIDAK DAPAT MEMBUAT ADMIN BARU
                'balance' => 0,
                'password_hash' => Hash::make($validated['password']),
            ]);

            AuditLog::create([
                'id' => Str::uuid(),
                'actor_id' => auth()->id(),
                'tenant_id' => auth()->user()->tenant_id,
                'actor_name' => auth()->user()->full_name,
                'action' => 'CREATE_OFFICER',
                'target_type' => 'profile',
                'target_id' => $officer->id,
                'details' => "Pengurus RT mendaftarkan petugas baru: {$officer->full_name} ({$validated['phone_number']})",
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', "Akun petugas {$officer->full_name} berhasil dibuat. Petugas dapat masuk menggunakan nomor WhatsApp dan kata sandi yang telah ditentukan.");
        });
    }

    public function resetPassword(Request $request, string $id): RedirectResponse
    {
        return DB::transaction(function () use ($request, $id): RedirectResponse {
            $officer = Profile::forTenant(auth()->user()->tenant_id)->where('id', $id)->where('role', 'officer')->lockForUpdate()->firstOrFail();

            $request->validate([
                'new_password' => 'required|string|min:6',
            ]);

            $officer->update([
                'password_hash' => Hash::make($request->new_password),
            ]);

            AuditLog::create([
                'id' => Str::uuid(),
                'actor_id' => auth()->id(),
                'tenant_id' => auth()->user()->tenant_id,
                'actor_name' => auth()->user()->full_name,
                'action' => 'RESET_OFFICER_PASSWORD',
                'target_type' => 'profile',
                'target_id' => $officer->id,
                'details' => "Pengurus RT me-reset kata sandi petugas: {$officer->full_name}",
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', "Kata sandi petugas {$officer->full_name} berhasil diperbarui.");
        });
    }

    public function destroy(string $id): RedirectResponse
    {
        return DB::transaction(function () use ($id): RedirectResponse {
            $officer = Profile::forTenant(auth()->user()->tenant_id)->where('id', $id)->where('role', 'officer')->lockForUpdate()->firstOrFail();

            // Jangan hapus jika ada riwayat transaksi penimbangan agar data audit tetap utuh
            if ($officer->officerTransactions()->exists()) {
                return back()->with('error', "Petugas {$officer->full_name} memiliki riwayat transaksi penimbangan dan tidak dapat dihapus demi integritas data.");
            }

            $officerName = $officer->full_name;
            $officer->delete();

            AuditLog::create([
                'id' => Str::uuid(),
                'actor_id' => auth()->id(),
                'tenant_id' => auth()->user()->tenant_id,
                'actor_name' => auth()->user()->full_name,
                'action' => 'DELETE_OFFICER',
                'target_type' => 'profile',
                'target_id' => $id,
                'details' => "Pengurus RT menghapus akun petugas: {$officerName}",
                'ip_address' => request()->ip(),
            ]);

            return back()->with('success', "Akun petugas {$officerName} berhasil dihapus.");
        });
    }
}
