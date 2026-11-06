<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\WasteCategory;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResidentApiController extends Controller
{
    private function getResident(Request $request): ?Profile
    {
        $residentId = $request->session()->get('resident_id');

        if (! $residentId && auth()->check() && auth()->user()->role === 'resident') {
            $residentId = auth()->id();
        }

        if (! $residentId) {
            return null;
        }

        return Profile::where('id', $residentId)
            ->where('role', 'resident')
            ->first();
    }

    /**
     * Ringkasan Tabungan Warga
     */
    public function summary(Request $request): JsonResponse
    {
        $resident = $this->getResident($request);

        if (! $resident) {
            return response()->json(['success' => false, 'message' => 'Sesi nasabah tidak ditemukan.'], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $resident->id,
                'name' => $resident->full_name,
                'phone' => $resident->phone_number,
                'balance' => (int) $resident->balance,
                'total_weight_kg' => $resident->total_waste_weight_kg,
                'total_co2_kg' => $resident->total_co2_saved_kg,
                'deposit_count' => $resident->deposit_count,
                'withdrawal_count' => $resident->withdrawal_count,
                'category_breakdown' => $resident->category_breakdown,
            ],
        ]);
    }

    /**
     * Riwayat Transaksi Nasabah
     */
    public function transactions(Request $request): JsonResponse
    {
        $resident = $this->getResident($request);

        if (! $resident) {
            return response()->json(['success' => false, 'message' => 'Sesi nasabah tidak ditemukan.'], 401);
        }

        $type = (string) $request->input('type');
        $txQuery = $resident->transactions()->with(['items.category', 'officer']);

        if (in_array($type, ['deposit', 'withdrawal'])) {
            $txQuery->where('type', $type);
        }

        $transactions = $txQuery->orderByDesc('created_at')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Katalog Harga Komoditas Real-time
     */
    public function categories(Request $request): JsonResponse
    {
        $resident = $this->getResident($request);

        if (! $resident || ! $resident->tenant_id) {
            return response()->json(['success' => false, 'message' => 'Sesi nasabah tidak ditemukan.'], 401);
        }

        $categories = WasteCategory::forTenant($resident->tenant_id)->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'price_per_unit']);

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Endpoint Generator QR Code
     */
    public function qrcode(Request $request): JsonResponse
    {
        $resident = $this->getResident($request);

        if (! $resident) {
            return response()->json(['success' => false, 'message' => 'Sesi nasabah tidak ditemukan.'], 401);
        }

        $payload = [
            'system' => 'SI_BANK_SAMPAH',
            'resident_id' => $resident->id,
            'full_name' => $resident->full_name,
            'phone' => $resident->phone_number,
            'address' => $resident->household_code,
            'balance' => $resident->balance,
            'status' => 'ACTIVE',
        ];

        $svg = QrCodeService::generateSvg("BS:{$resident->phone_number}", 6, '#173f35', '#ffffff');

        return response()->json([
            'success' => true,
            'payload' => $payload,
            'qr_code_svg' => $svg,
        ]);
    }
}
