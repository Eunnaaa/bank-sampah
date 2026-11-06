<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\WasteCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filteredTransactions($request);
        $period = ($request->input('period') ?: 'month');
        $type = $request->input('type');

        $totalDeposit = (clone $query)->where('type', 'deposit')->sum('total_amount');
        $totalWithdrawal = (clone $query)->where('type', 'withdrawal')->sum('total_amount');
        $totalDepositCount = (clone $query)->where('type', 'deposit')->count();

        // Rekapitulasi per komoditas
        $categoriesSummary = WasteCategory::forTenant(auth()->user()->tenant_id)->get()->map(function ($cat) use ($query) {
            $itemsQuery = TransactionItem::where('category_id', $cat->id)
                ->whereIn('transaction_id', (clone $query)->select('transactions.id'));

            return [
                'name' => $cat->name,
                'unit' => $cat->unit,
                'total_weight' => round($itemsQuery->sum('weight'), 2),
                'total_subtotal' => $itemsQuery->sum('subtotal'),
            ];
        });

        $txQuery = (clone $query)->with(['resident', 'officer', 'items.category']);
        $transactions = $txQuery->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('admin.laporan', compact(
            'period',
            'type',
            'totalDeposit',
            'totalWithdrawal',
            'totalDepositCount',
            'categoriesSummary',
            'transactions'
        ));
    }

    public function auditLog(Request $request)
    {
        $query = AuditLog::forTenant(auth()->user()->tenant_id);

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('actor_name', 'ilike', "%{$q}%")
                    ->orWhere('details', 'ilike', "%{$q}%");
            });
        }

        $logs = $query->orderByDesc('created_at')->paginate(25)->withQueryString();
        $distinctActions = AuditLog::forTenant(auth()->user()->tenant_id)->select('action')->distinct()->orderBy('action')->pluck('action');

        return view('admin.audit-log', compact('logs', 'distinctActions'));
    }

    public function exportCsv(Request $request)
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="rekap_komoditas_'.now(config('app.business_timezone'))->format('Ymd_His').'.csv"',
        ];

        $query = $this->filteredTransactions($request);
        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
            $this->writeCsvRow($file, ['Nama Komoditas', 'Satuan', 'Total Bobot/Volume', 'Total Nilai (Rp)']);

            $categories = WasteCategory::forTenant(auth()->user()->tenant_id)->get();
            foreach ($categories as $cat) {
                $weight = TransactionItem::where('category_id', $cat->id)->whereIn('transaction_id', (clone $query)->select('transactions.id'))->sum('weight');
                $subtotal = TransactionItem::where('category_id', $cat->id)->whereIn('transaction_id', (clone $query)->select('transactions.id'))->sum('subtotal');
                $this->writeCsvRow($file, [$cat->name, $cat->unit, number_format($weight, 2, ',', '.'), $subtotal]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportLedger(Request $request)
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="buku_besar_transaksi_'.now(config('app.business_timezone'))->format('Ymd_His').'.csv"',
        ];

        $query = $this->filteredTransactions($request);
        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            $this->writeCsvRow($file, ['ID Transaksi', 'Tanggal', 'Jenis', 'Nama Nasabah', 'Alamat/Rumah', 'Nominal (Rp)', 'Petugas', 'Catatan']);

            $transactions = $query->with(['resident', 'officer'])->orderBy('created_at')->orderBy('id')->lazy(200);
            foreach ($transactions as $tx) {
                $this->writeCsvRow($file, [
                    $tx->id,
                    $tx->created_at->timezone(config('app.business_timezone'))->format('Y-m-d H:i:s'),
                    $tx->type === 'deposit' ? 'SETORAN' : 'PENCAIRAN_TUNAI',
                    $tx->resident?->full_name ?? '-',
                    $tx->resident?->household_code ?? '-',
                    $tx->total_amount,
                    $tx->officer?->full_name ?? '-',
                    $tx->notes ?? '-',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function filteredTransactions(Request $request): Builder
    {
        $request->validate([
            'period' => 'nullable|in:today,month,all',
            'type' => 'nullable|in:deposit,withdrawal',
        ]);

        return Transaction::forTenant($request->user()->tenant_id)
            ->inPeriod(($request->input('period') ?: 'month'))
            ->when($request->filled('type'), fn (Builder $query): Builder => $query->where('type', $request->type));
    }

    /**
     * @param  resource  $file
     * @param  array<int, string|int|float|null>  $row
     */
    private function writeCsvRow($file, array $row): void
    {
        $cells = array_map(static function (string|int|float|null $value): string|int|float|null {
            if (is_string($value) && preg_match('/^(?:[\x00-\x20]*[=+\-@]|[\t\r\n])/u', $value)) {
                return "'".$value;
            }

            return $value;
        }, $row);
        fputcsv($file, $cells, ',', '"', '');
    }
}
