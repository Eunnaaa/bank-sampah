<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\WasteCategory;
use App\Services\DisbursementService;
use App\Services\QrCodeService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResidentDashboardController extends Controller
{
    private function getResident(Request $request): Profile
    {
        /** @var Profile $resident */
        $resident = $request->attributes->get('resident')
            ?? Profile::findOrFail($request->session()->get('resident_id'));

        return $resident;
    }

    public function dashboard(Request $request): View
    {
        $resident = $this->getResident($request);

        $recentTransactions = $resident->transactions()
            ->with(['items.category', 'officer'])
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $categoryBreakdown = $resident->category_breakdown;

        // QR Code Payload & SVG
        $qrPayload = json_encode([
            'system' => 'SI_BANK_SAMPAH',
            'id' => $resident->id,
            'name' => $resident->full_name,
            'phone' => $resident->phone_number,
            'zone' => $resident->household_code,
        ], JSON_UNESCAPED_SLASHES);

        $qrCodeSvg = QrCodeService::generateSvg("BS:{$resident->phone_number}", 5, '#173f35', '#ffffff');

        // Peringkat Warga di Lingkungan RT (kueri kalkulasi langsung tanpa memuat array seluruh id)
        $residentRank = Profile::forTenant($resident->tenant_id)->where('role', 'resident')
            ->where('balance', '>', $resident->balance)
            ->count() + 1;
        $totalResidents = Profile::forTenant($resident->tenant_id)->where('role', 'resident')
            ->count();

        // Tren Penimbangan 7 Hari Terakhir Nasabah (2 kueri berindeks)
        $timezone = config('app.business_timezone');
        $now = Carbon::now($timezone);
        $start7Days = $now->copy()->subDays(6)->startOfDay();

        $weekDeposits = $resident->transactions()
            ->where('type', 'deposit')
            ->where('created_at', '>=', $start7Days->utc())
            ->with(['items' => fn ($q) => $q->select('transaction_id', 'weight')])
            ->get(['id', 'total_amount', 'created_at']);

        $dailyWeights = [];
        foreach ($weekDeposits as $tx) {
            $dayKey = $tx->created_at->timezone($timezone)->format('Y-m-d');
            $dailyWeights[$dayKey] = ($dailyWeights[$dayKey] ?? 0.0) + (float) $tx->items->sum('weight');
        }

        $chartDays = collect(range(6, 0))->map(function ($daysAgo) use ($now, $dailyWeights) {
            $day = $now->copy()->subDays($daysAgo);
            $date = $day->format('Y-m-d');
            $label = $day->translatedFormat('D, d/m');

            return [
                'date' => $date,
                'label' => $label,
                'weight' => round($dailyWeights[$date] ?? 0.0, 1),
            ];
        });

        // Arus Kas Tabungan Keluarga (1 kueri agregasi)
        $cashflow = $resident->transactions()
            ->selectRaw("
                coalesce(sum(case when type = 'deposit' then total_amount else 0 end), 0) as deposit_amount,
                coalesce(sum(case when type = 'withdrawal' then total_amount else 0 end), 0) as withdrawal_amount,
                count(case when type = 'deposit' then 1 end) as deposit_count,
                count(case when type = 'withdrawal' then 1 end) as withdrawal_count
            ")
            ->first();

        $totalDepositAmount = (int) ($cashflow->deposit_amount ?? 0);
        $totalWithdrawalAmount = (int) ($cashflow->withdrawal_amount ?? 0);
        $totalDepositCount = (int) ($cashflow->deposit_count ?? 0);
        $totalWithdrawalCount = (int) ($cashflow->withdrawal_count ?? 0);

        // Komoditas Aktif & Harga Terkini
        $activeCategories = WasteCategory::forTenant($resident->tenant_id)->where('is_active', true)
            ->orderBy('name')
            ->take(6)
            ->get();

        return view('warga.dashboard', [
            'resident' => $resident,
            'recentTransactions' => $recentTransactions,
            'categoryBreakdown' => $categoryBreakdown,
            'qrCodeSvg' => $qrCodeSvg,
            'qrPayload' => $qrPayload,
            'residentRank' => $residentRank,
            'totalResidents' => $totalResidents,
            'chartDays' => $chartDays,
            'totalDepositAmount' => $totalDepositAmount,
            'totalWithdrawalAmount' => $totalWithdrawalAmount,
            'totalDepositCount' => $totalDepositCount,
            'totalWithdrawalCount' => $totalWithdrawalCount,
            'activeCategories' => $activeCategories,
        ]);
    }

    public function showTransaction(Request $request, string $id): JsonResponse
    {
        $resident = $this->getResident($request);

        $tx = $resident->transactions()
            ->with(['resident', 'officer', 'items.category'])
            ->findOrFail($id);

        $data = $tx->receiptData();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function riwayat(Request $request): View
    {
        $request->validate([
            'period' => 'nullable|in:all,month,3months,year',
            'type' => 'nullable|in:all,deposit,withdrawal',
            'q' => 'nullable|string|max:255',
        ]);

        $resident = $this->getResident($request);

        $type = (string) $request->input('type', 'all');
        $period = (string) $request->input('period', 'all');
        $query = trim((string) $request->input('q', ''));

        $txQuery = $resident->transactions()->with(['items.category', 'officer']);

        if (in_array($type, ['deposit', 'withdrawal'])) {
            $txQuery->where('type', $type);
        }

        $txQuery->inPeriod($period);

        if (! empty($query)) {
            $txQuery->where(function ($q) use ($query) {
                $q->where('notes', 'ilike', "%{$query}%")
                    ->orWhereHas('items.category', function ($cq) use ($query) {
                        $cq->where('name', 'ilike', "%{$query}%");
                    });
            });
        }

        $summaryQuery = clone $txQuery;
        $transactions = $txQuery->orderByDesc('created_at')->paginate(10)->withQueryString();
        $totalDeposited = (int) (clone $summaryQuery)->where('type', 'deposit')->sum('total_amount');
        $totalWithdrawn = (int) (clone $summaryQuery)->where('type', 'withdrawal')->sum('total_amount');

        return view('warga.riwayat', [
            'resident' => $resident,
            'transactions' => $transactions,
            'type' => $type,
            'period' => $period,
            'query' => $query,
            'totalDeposited' => $totalDeposited,
            'totalWithdrawn' => $totalWithdrawn,
        ]);
    }

    public function katalog(Request $request): View
    {
        $resident = $this->getResident($request);

        $categories = WasteCategory::forTenant($resident->tenant_id)->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('warga.katalog', [
            'resident' => $resident,
            'categories' => $categories,
        ]);
    }

    public function profil(Request $request): View
    {
        $resident = $this->getResident($request);

        $qrData = [
            'system' => 'SI_BANK_SAMPAH',
            'resident_id' => $resident->id,
            'full_name' => $resident->full_name,
            'phone' => $resident->phone_number,
            'address' => $resident->household_code,
            'balance' => $resident->balance,
            'status' => 'ACTIVE',
        ];

        $qrCodeSvg = QrCodeService::generateSvg("BS:{$resident->phone_number}", 6, '#173f35', '#ffffff');

        return view('warga.profil', [
            'resident' => $resident,
            'qrCodeSvg' => $qrCodeSvg,
            'qrPayload' => json_encode($qrData, JSON_UNESCAPED_SLASHES),
            'qrData' => $qrData,
            'channels' => DisbursementService::CHANNELS,
            'banks' => DisbursementService::BANKS,
            'ewallets' => DisbursementService::EWALLETS,
        ]);
    }

    public function authorizeWithdrawal(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
            'current_pin' => 'required|digits:4',
            'channel' => 'nullable|in:tunai,transfer_bank,ewallet',
            'provider' => 'nullable|required_if:channel,transfer_bank,ewallet|string|max:50',
            'account_number' => 'nullable|required_if:channel,transfer_bank,ewallet|string|max:50',
            'account_holder' => 'nullable|required_if:channel,transfer_bank,ewallet|string|max:100',
        ]);

        $channel = $validated['channel'] ?? 'tunai';
        $provider = $validated['provider'] ?? null;
        $accountNumber = $validated['account_number'] ?? null;
        $accountHolder = $validated['account_holder'] ?? null;

        $channelLabel = DisbursementService::formatChannelLabel($channel, $provider, $accountNumber, $accountHolder);

        $code = DB::transaction(function () use ($request, $validated, $channel, $provider, $accountNumber, $accountHolder, $channelLabel): string {
            $resident = Profile::where('role', 'resident')->lockForUpdate()->findOrFail($this->getResident($request)->id);
            if (! $resident->pin_hash || ! Hash::check($validated['current_pin'], $resident->pin_hash)) {
                throw ValidationException::withMessages(['current_pin' => 'PIN saat ini tidak sesuai.']);
            }
            if ($validated['amount'] > $resident->balance) {
                throw ValidationException::withMessages(['amount' => 'Saldo tidak mencukupi.']);
            }

            $code = strtoupper(Str::random(12));
            $resident->withdrawal_approval = [
                'code_hash' => hash('sha256', $code),
                'amount' => (int) $validated['amount'],
                'channel' => $channel,
                'provider' => $provider,
                'account_number' => $accountNumber,
                'account_holder' => $accountHolder,
                'channel_label' => $channelLabel,
                'approved_at' => now()->toIso8601String(),
                'expires_at' => now()->addMinutes(10)->toIso8601String(),
            ];
            $resident->save();

            AuditLog::create([
                'actor_id' => $resident->id,
                'tenant_id' => $resident->tenant_id,
                'actor_name' => $resident->full_name,
                'action' => 'AUTHORIZE_WITHDRAWAL',
                'target_type' => 'profile',
                'target_id' => $resident->id,
                'details' => 'Warga menyetujui pencairan Rp '.number_format((int) $validated['amount'], 0, ',', '.')." via {$channelLabel} dengan kode sekali pakai berlaku 10 menit.",
                'ip_address' => $request->ip(),
            ]);

            return $code;
        });

        return back()
            ->with('withdrawal_code', $code)
            ->with('withdrawal_amount', (int) $validated['amount'])
            ->with('withdrawal_channel_label', $channelLabel)
            ->with('withdrawal_channel', $channel);
    }

    public function changePin(Request $request): RedirectResponse
    {
        return DB::transaction(function () use ($request): RedirectResponse {
            $resident = Profile::where('role', 'resident')->lockForUpdate()->findOrFail($this->getResident($request)->id);

            $request->validate([
                'current_pin' => 'required|digits:4',
                'new_pin' => 'required|digits:4|confirmed',
            ], [
                'current_pin.digits' => 'PIN saat ini harus terdiri dari 4 digit.',
                'new_pin.digits' => 'PIN baru harus terdiri dari 4 digit.',
                'new_pin.confirmed' => 'Konfirmasi PIN baru tidak cocok.',
            ]);

            if (! Hash::check((string) $request->input('current_pin'), (string) $resident->pin_hash)) {
                return back()->with('error', 'PIN saat ini yang Anda masukkan salah.');
            }

            $resident->update([
                'pin_hash' => Hash::make((string) $request->input('new_pin')),
            ]);

            AuditLog::create([
                'id' => Str::uuid(),
                'actor_id' => $resident->id,
                'tenant_id' => $resident->tenant_id,
                'actor_name' => $resident->full_name,
                'action' => 'RESIDENT_CHANGE_PIN',
                'target_type' => 'profile',
                'target_id' => $resident->id,
                'details' => 'Nasabah mengganti PIN keamanan akun secara mandiri.',
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', 'PIN keamanan Anda berhasil diperbarui!');
        });
    }
}
