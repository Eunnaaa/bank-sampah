<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\WasteCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function home()
    {
        $categories = WasteCategory::where('is_active', true)->orderBy('name')->get();

        $totalAmountMonth = Transaction::where('type', 'deposit')
            ->inPeriod('month')
            ->sum('total_amount');

        $totalWeightMonth = TransactionItem::whereHas('transaction', function ($q) {
            $q->where('type', 'deposit')->inPeriod('month');
        })->sum('weight');

        $report = [
            'totalAmount' => $totalAmountMonth,
            'totalWeightKg' => $totalWeightMonth,
            'periodLabel' => Carbon::now(config('app.business_timezone'))->translatedFormat('F Y'),
        ];

        return view('home', compact('categories', 'report'));
    }

    public function warga(Request $request)
    {
        $search = $request->query('q');
        $query = Profile::where('role', 'resident');
        $tenantId = $request->user()?->tenant_id;
        $query->when($tenantId, fn (Builder $query): Builder => $query->forTenant($tenantId))
            ->when(! $tenantId, fn (Builder $query): Builder => $query->whereRaw('1 = 0'));

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'ilike', "%{$search}%")
                    ->orWhere('house_number', 'ilike', "%{$search}%");
            });
        }

        $residents = $query->orderBy('full_name')->paginate(12)->withQueryString();

        return view('warga', compact('residents', 'search'));
    }

    public function terms()
    {
        return view('syarat-ketentuan');
    }
}
