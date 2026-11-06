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
        $totalResidents = Profile::forTenant(auth()->user()->tenant_id)->where('role', 'resident')->count();
        $totalSystemBalance = Profile::forTenant(auth()->user()->tenant_id)->where('role', 'resident')->sum('balance');
        $totalWasteKg = TransactionItem::forTenant(auth()->user()->tenant_id)->whereHas('transaction', fn ($q) => $q->where('type', 'deposit'))->sum('weight');
        $co2ReductionKg = round($totalWasteKg * 2.9, 1);

        // Kas & Arus Keuangan
        $totalDepositAmount = Transaction::forTenant(auth()->user()->tenant_id)->where('type', 'deposit')->sum('total_amount');
        $totalWithdrawalAmount = Transaction::forTenant(auth()->user()->tenant_id)->where('type', 'withdrawal')->sum('total_amount');
        $totalDepositCount = Transaction::forTenant(auth()->user()->tenant_id)->where('type', 'deposit')->count();
        $totalWithdrawalCount = Transaction::forTenant(auth()->user()->tenant_id)->where('type', 'withdrawal')->count();

        // Komoditas Aktif & Rekapitulasi per Komoditas (Katalog Overview)
        $categories = WasteCategory::forTenant(auth()->user()->tenant_id)->where('is_active', true)->orderBy('name')->get();
        $commoditiesSummary = $categories->map(function ($cat) {
            $totalWeight = TransactionItem::forTenant(auth()->user()->tenant_id)->where('category_id', $cat->id)
                ->whereHas('transaction', fn ($q) => $q->where('type', 'deposit'))
                ->sum('weight');
            $totalAmount = TransactionItem::forTenant(auth()->user()->tenant_id)->where('category_id', $cat->id)
                ->whereHas('transaction', fn ($q) => $q->where('type', 'deposit'))
                ->sum('subtotal');

            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'unit' => $cat->unit,
                'price_per_unit' => $cat->price_per_unit,
                'total_weight' => round($totalWeight, 1),
                'total_amount' => $totalAmount,
            ];
        });

        // Warga & Nasabah Teratas (Leaderboard & Status)
        $topResidents = Profile::forTenant(auth()->user()->tenant_id)->where('role', 'resident')
            ->orderByDesc('balance')
            ->take(4)
            ->get();
        $activatedPinsCount = Profile::forTenant(auth()->user()->tenant_id)->where('role', 'resident')->whereNotNull('pin_hash')->count();
        $unactivatedPinsCount = Profile::forTenant(auth()->user()->tenant_id)->where('role', 'resident')->whereNull('pin_hash')->count();

        // Petugas Lapangan
        $officersCount = Profile::forTenant(auth()->user()->tenant_id)->where('role', 'officer')->count();

        // Log Audit Terkini (5 Aksi Terakhir)
        $recentAuditLogs = AuditLog::forTenant(auth()->user()->tenant_id)->orderByDesc('created_at')->take(5)->get();

        // Transaksi Setoran Terkini
        $recentTransactions = Transaction::forTenant(auth()->user()->tenant_id)->with(['resident', 'officer', 'items.category'])
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        // Data Chart Mingguan (7 Hari Terakhir)
        $chartDays = collect(range(6, 0))->map(function ($daysAgo) {
            $day = Carbon::now(config('app.business_timezone'))->subDays($daysAgo);
            $date = $day->toDateString();
            $label = $day->translatedFormat('D, d M');
            $weight = TransactionItem::forTenant(auth()->user()->tenant_id)
                ->whereHas('transaction', fn ($q) => $q->where('type', 'deposit')->onLocalDay($day))
                ->sum('weight');
            $amount = Transaction::forTenant(auth()->user()->tenant_id)->onLocalDay($day)
                ->where('type', 'deposit')
                ->sum('total_amount');

            return [
                'label' => $label,
                'weight' => round($weight, 1),
                'amount' => $amount,
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
