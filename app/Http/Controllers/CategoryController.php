<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\WasteCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = WasteCategory::forTenant(auth()->user()->tenant_id)->orderBy('name')->get();

        return view('admin.kategori', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        return DB::transaction(function () use ($request): RedirectResponse {
            $request->validate([
                'name' => 'required|string|max:100',
                'unit' => 'required|in:kg,liter,unit',
                'price_per_unit' => 'required|integer|min:1',
            ]);

            $cat = WasteCategory::create([
                'id' => Str::uuid(),
                'tenant_id' => auth()->user()->tenant_id,
                'name' => $request->name,
                'unit' => $request->unit,
                'price_per_unit' => $request->price_per_unit,
                'is_active' => true,
            ]);

            AuditLog::create([
                'id' => Str::uuid(),
                'actor_id' => auth()->id(),
                'tenant_id' => auth()->user()->tenant_id,
                'actor_name' => auth()->user()->full_name,
                'action' => 'CREATE_CATEGORY',
                'target_type' => 'waste_category',
                'target_id' => $cat->id,
                'details' => "Menambahkan kategori sampah: {$cat->name} (Rp {$cat->price_per_unit}/{$cat->unit})",
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', "Komoditas {$cat->name} berhasil ditambahkan ke katalog.");
        });
    }

    public function updatePrice(Request $request, string $id): RedirectResponse
    {
        return DB::transaction(function () use ($request, $id): RedirectResponse {
            $request->validate([
                'price_per_unit' => 'required|integer|min:1',
            ]);

            $cat = WasteCategory::forTenant(auth()->user()->tenant_id)->lockForUpdate()->findOrFail($id);
            $oldPrice = $cat->price_per_unit;
            $cat->update(['price_per_unit' => $request->price_per_unit]);

            AuditLog::create([
                'id' => Str::uuid(),
                'actor_id' => auth()->id(),
                'tenant_id' => auth()->user()->tenant_id,
                'actor_name' => auth()->user()->full_name,
                'action' => 'UPDATE_PRICE',
                'target_type' => 'waste_category',
                'target_id' => $cat->id,
                'details' => "Penyesuaian harga {$cat->name}: Rp {$oldPrice} -> Rp {$cat->price_per_unit}",
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', "Harga {$cat->name} berhasil diperbarui.");
        });
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        return DB::transaction(function () use ($request, $id): RedirectResponse {
            $cat = WasteCategory::forTenant(auth()->user()->tenant_id)->lockForUpdate()->findOrFail($id);
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'unit' => 'required|in:kg,liter,unit',
                'price_per_unit' => 'required|integer|min:1',
            ]);

            if ($validated['unit'] !== $cat->unit && $cat->transactionItems()->exists()) {
                throw ValidationException::withMessages(['unit' => 'Satuan komoditas yang sudah digunakan tidak dapat diubah. Buat komoditas baru untuk satuan berbeda.']);
            }

            $oldName = $cat->name;
            $oldUnit = $cat->unit;
            $oldPrice = $cat->price_per_unit;
            $cat->update($validated);

            AuditLog::create([
                'id' => Str::uuid(),
                'actor_id' => auth()->id(),
                'tenant_id' => auth()->user()->tenant_id,
                'actor_name' => auth()->user()->full_name,
                'action' => 'UPDATE_CATEGORY',
                'target_type' => 'waste_category',
                'target_id' => $cat->id,
                'details' => "Pembaruan komoditas {$oldName} -> {$cat->name}; satuan {$oldUnit} -> {$cat->unit}; harga Rp {$oldPrice} -> Rp {$cat->price_per_unit}",
                'ip_address' => request()->ip(),
            ]);

            return back()->with('success', "Komoditas {$cat->name} berhasil diperbarui.");
        });
    }

    public function destroy(string $id): RedirectResponse
    {
        return DB::transaction(function () use ($id): RedirectResponse {
            $cat = WasteCategory::forTenant(auth()->user()->tenant_id)->lockForUpdate()->findOrFail($id);
            if ($cat->transactionItems()->count() > 0) {
                $cat->update(['is_active' => false]);

                AuditLog::create([
                    'actor_id' => auth()->id(), 'tenant_id' => auth()->user()->tenant_id,
                    'actor_name' => auth()->user()->full_name, 'action' => 'DISABLE_CATEGORY',
                    'target_type' => 'waste_category', 'target_id' => $cat->id,
                    'details' => "Menonaktifkan komoditas {$cat->name} yang memiliki riwayat transaksi.",
                    'ip_address' => request()->ip(),
                ]);

                return back()->with('success', "Komoditas {$cat->name} memiliki riwayat transaksi sehingga dinonaktifkan.");
            }

            $name = $cat->name;
            $cat->delete();

            AuditLog::create([
                'id' => Str::uuid(),
                'actor_id' => auth()->id(),
                'tenant_id' => auth()->user()->tenant_id,
                'actor_name' => auth()->user()->full_name,
                'action' => 'DELETE_CATEGORY',
                'target_type' => 'waste_category',
                'target_id' => $id,
                'details' => "Menghapus komoditas {$name}",
                'ip_address' => request()->ip(),
            ]);

            return back()->with('success', "Komoditas {$name} berhasil dihapus dari katalog.");
        });
    }

    public function toggleStatus(string $id): RedirectResponse
    {
        return DB::transaction(function () use ($id): RedirectResponse {
            $cat = WasteCategory::forTenant(auth()->user()->tenant_id)->lockForUpdate()->findOrFail($id);
            $cat->update(['is_active' => ! $cat->is_active]);

            AuditLog::create([
                'id' => Str::uuid(),
                'actor_id' => auth()->id(),
                'tenant_id' => auth()->user()->tenant_id,
                'actor_name' => auth()->user()->full_name,
                'action' => 'TOGGLE_CATEGORY',
                'target_type' => 'waste_category',
                'target_id' => $cat->id,
                'details' => "Status {$cat->name} diubah menjadi: ".($cat->is_active ? 'Aktif' : 'Non-Aktif'),
                'ip_address' => request()->ip(),
            ]);

            return back()->with('success', "Status komoditas {$cat->name} berhasil diubah.");
        });
    }
}
