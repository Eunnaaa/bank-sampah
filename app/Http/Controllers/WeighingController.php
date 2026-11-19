<?php

namespace App\Http\Controllers;

use App\Actions\RecordDepositAction;
use App\Http\Requests\StoreWeighingRequest;
use App\Models\Profile;
use App\Models\Transaction;
use App\Models\WasteCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;

class WeighingController extends Controller
{
    public function index()
    {
        $residents = Profile::forTenant(auth()->user()->tenant_id)
            ->where('role', 'resident')
            ->select('id', 'full_name', 'phone_number', 'rt', 'rw', 'house_number')
            ->orderBy('full_name')
            ->get();
        $categories = WasteCategory::forTenant(auth()->user()->tenant_id)
            ->where('is_active', true)
            ->select('id', 'name', 'unit', 'price_per_unit')
            ->orderBy('name')
            ->get();

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

    public function store(StoreWeighingRequest $request, RecordDepositAction $action)
    {
        $validated = $request->validated();

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
