<?php

namespace App\Http\Controllers;

use App\Actions\RecordDepositAction;
use App\Models\Profile;
use App\Models\Transaction;
use App\Models\WasteCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class WeighingController extends Controller
{
    public function index()
    {
        $residents = Profile::forTenant(auth()->user()->tenant_id)->where('role', 'resident')->orderBy('full_name')->get();
        $categories = WasteCategory::forTenant(auth()->user()->tenant_id)->where('is_active', true)->orderBy('name')->get();

        return view('admin.timbang', compact('residents', 'categories'));
    }

    public function show(string $id)
    {
        $tx = Transaction::forTenant(auth()->user()->tenant_id)->with(['resident', 'officer', 'items.category'])->findOrFail($id);

        $data = $tx->receiptData();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function store(Request $request, RecordDepositAction $action)
    {
        $validated = $request->validate([
            'resident_id' => 'required|uuid',
            'request_id' => 'required|uuid',
            'items' => 'required|array|min:1|max:100',
            'items.*.category_id' => 'required|uuid',
            'items.*.weight' => 'required|numeric|min:0.01|max:99999999.99|decimal:0,2',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $result = $action->execute(
                $validated['resident_id'],
                auth()->id(),
                $validated['items'],
                $validated['notes'] ?? null,
                $validated['request_id']
            );

            return response()->json([
                'success' => true,
                'message' => 'Setoran berhasil dicatat ke saldo tabungan warga.',
                'data' => $result,
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            return response()->json(['success' => false, 'message' => 'Identitas transaksi sudah digunakan. Muat ulang formulir.'], 409);
        } catch (ModelNotFoundException|\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
