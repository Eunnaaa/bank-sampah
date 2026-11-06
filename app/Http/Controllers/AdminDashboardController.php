<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\WasteCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $residentStats = Profile::forTenant($tenantId)->where('role', 'resident')
            ->selectRaw('count(*) as count, coalesce(sum(balance), 0) as balance, count(pin_hash) as activated_pins')
            ->first();
        $totalResidents = (int) ($residentStats->count ?? 0);
        $totalSystemBalance = (int) ($residentStats->balance ?? 0);
        $activatedPinsCount = (int) ($residentStats->activated_pins ?? 0);
        $unactivatedPinsCount = $totalResidents - $activatedPinsCount;

        $totalWasteKg = (float) TransactionItem::forTenant($tenantId)->whereHas('transaction', fn ($q) => $q->where('type', 'deposit'))->sum('weight');
        $co2ReductionKg = round($totalWasteKg * 2.9, 1);

        // Kas & Arus Keuangan (agregasi tunggal)
        $txStats = Transaction::forTenant($tenantId)
            ->selectRaw("
                coalesce(sum(case when type = 'deposit' then total_amount else 0 end), 0) as deposit_amount,
                coalesce(sum(case when type = 'withdrawal' then total_amount else 0 end), 0) as withdrawal_amount,
                count(case when type = 'deposit' then 1 end) as deposit_count,
                count(case when type = 'withdrawal' then 1 end) as withdrawal_count
            ")
            ->first();
        $totalDepositAmount = (int) ($txStats->deposit_amount ?? 0);
        $totalWithdrawalAmount = (int) ($txStats->withdrawal_amount ?? 0);
        $totalDepositCount = (int) ($txStats->deposit_count ?? 0);
        $totalWithdrawalCount = (int) ($txStats->withdrawal_count ?? 0);

        // Komoditas Aktif & Rekapitulasi per Komoditas (1 kueri terkelompok)
        $categories = WasteCategory::forTenant($tenantId)->where('is_active', true)->orderBy('name')->get();
        $itemsAggregates = TransactionItem::forTenant($tenantId)
            ->whereHas('transaction', fn ($q) => $q->where('type', 'deposit'))
            ->groupBy('category_id')
            ->selectRaw('category_id, sum(weight) as total_weight, sum(subtotal) as total_amount')
            ->get()
            ->keyBy('category_id');

        $commoditiesSummary = $categories->map(function ($cat) use ($itemsAggregates) {
            $agg = $itemsAggregates->get($cat->id);

            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'unit' => $cat->unit,
                'price_per_unit' => $cat->price_per_unit,
                'total_weight' => round((float) ($agg?->total_weight ?? 0), 1),
                'total_amount' => (int) ($agg?->total_amount ?? 0),
            ];
        });

        // Warga & Nasabah Teratas (Leaderboard & Status)
        $topResidents = Profile::forTenant($tenantId)->where('role', 'resident')
            ->orderByDesc('balance')
            ->take(4)
            ->get();

        // Petugas Lapangan
        $officersCount = Profile::forTenant($tenantId)->where('role', 'officer')->count();

        // Log Audit Terkini (5 Aksi Terakhir)
        $recentAuditLogs = AuditLog::forTenant($tenantId)->orderByDesc('created_at')->take(5)->get();

        // Transaksi Setoran Terkini
        $recentTransactions = Transaction::forTenant($tenantId)->with(['resident', 'officer', 'items.category'])
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        // Data Chart Mingguan (7 Hari Terakhir - 2 kueri berindeks)
        $timezone = config('app.business_timezone');
        $now = Carbon::now($timezone);
        $start7Days = $now->copy()->subDays(6)->startOfDay();

        $weekDeposits = Transaction::forTenant($tenantId)
            ->where('type', 'deposit')
            ->where('created_at', '>=', $start7Days->utc())
            ->with(['items' => fn ($q) => $q->select('transaction_id', 'weight')])
            ->get(['id', 'total_amount', 'created_at']);

        $dailyAmounts = [];
        $dailyWeights = [];
        foreach ($weekDeposits as $tx) {
            $dayKey = $tx->created_at->timezone($timezone)->format('Y-m-d');
            $dailyAmounts[$dayKey] = ($dailyAmounts[$dayKey] ?? 0) + $tx->total_amount;
            $dailyWeights[$dayKey] = ($dailyWeights[$dayKey] ?? 0.0) + (float) $tx->items->sum('weight');
        }

        $chartDays = collect(range(6, 0))->map(function ($daysAgo) use ($now, $dailyAmounts, $dailyWeights) {
            $day = $now->copy()->subDays($daysAgo);
            $dayKey = $day->format('Y-m-d');
            $label = $day->translatedFormat('D, d M');

            return [
                'label' => $label,
                'weight' => round($dailyWeights[$dayKey] ?? 0.0, 1),
                'amount' => (int) ($dailyAmounts[$dayKey] ?? 0),
            ];
        });

        return view('admin.dashboard', compact(
            'totalResidents',
            'totalSystemBalance',
            'totalWasteKg',
            'co2ReductionKg',
            'totalDepositAmount',
            'totalWithdrawalAmount',
            'totalDepositCount',
            'totalWithdrawalCount',
            'commoditiesSummary',
            'topResidents',
            'activatedPinsCount',
            'unactivatedPinsCount',
            'officersCount',
            'recentAuditLogs',
            'recentTransactions',
            'chartDays'
        ));
    }
}
