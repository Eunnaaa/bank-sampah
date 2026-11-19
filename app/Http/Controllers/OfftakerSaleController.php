<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\OfftakerSale;
use App\Models\OfftakerSaleItem;
use App\Models\WasteCategory;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OfftakerSaleController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Tampilkan ringkasan stok gudang dan riwayat penjualan ke pengepul.
     */
    public function index(): View
    {
        $tenantId = auth()->user()->tenant_id;

        $inventorySummary = $this->inventoryService->getInventorySummary($tenantId);
        $metrics = $this->inventoryService->getInventoryMetrics($inventorySummary);

        $sales = OfftakerSale::forTenant($tenantId)
            ->with(['officer', 'items.category'])
            ->orderByDesc('sale_date')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.penjualan.index', compact('inventorySummary', 'metrics', 'sales'));
    }

    /**
     * Tampilkan form pencatatan penjualan ke pengepul.
     */
    public function create(): View
    {
        $tenantId = auth()->user()->tenant_id;
        $inventorySummary = $this->inventoryService->getInventorySummary($tenantId)
            ->where('stock_weight', '>', 0)->values();

        return view('admin.penjualan.create', compact('inventorySummary'));
    }

    /**
     * Simpan transaksi penjualan ke pengepul.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $officerId = auth()->id();

        $validated = $request->validate([
            'buyer_name' => ['required', 'string', 'max:255'],
            'sale_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'items.*.category_id' => ['required', 'uuid'],
            'items.*.weight' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'items.*.selling_price_per_unit' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ], [
            'buyer_name.required' => 'Nama pembeli / pengepul wajib diisi.',
            'sale_date.required' => 'Tanggal penjualan wajib ditentukan.',
            'items.required' => 'Minimal satu jenis komoditas sampah wajib dipilih.',
            'items.*.weight.min' => 'Bobot penjualan minimal 0,01 satuan.',
            'items.*.selling_price_per_unit.min' => 'Harga jual ke pengepul tidak boleh negatif.',
        ]);

        $sale = DB::transaction(function () use ($tenantId, $officerId, $validated): OfftakerSale {
            $itemsByCategory = collect($validated['items'])->groupBy(fn (array $item): string => strtolower($item['category_id']));
            $categories = WasteCategory::forTenant($tenantId)
                ->whereIn('id', $itemsByCategory->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($categories->count() !== $itemsByCategory->count()) {
                throw ValidationException::withMessages(['items' => 'Semua komoditas harus terdaftar di RT Anda.']);
            }

            $totalHundredths = 0;
            $totalRevenue = 0;
            $totalCost = 0;
            $preparedItems = [];

            foreach ($itemsByCategory as $categoryId => $items) {
                $weights = $items->map(fn (array $item): int => (int) round((float) $item['weight'] * 100))->all();
                $totalHundredths += array_sum($weights);

                if ($totalHundredths > 9999999999) {
                    throw ValidationException::withMessages(['items' => 'Total bobot penjualan melebihi batas penyimpanan.']);
                }

                $allocations = $this->inventoryService->saleCosts($categories[$categoryId], $weights);

                foreach ($items as $index => $item) {
                    $weight = $weights[$index];
                    $sellingPrice = (int) $item['selling_price_per_unit'];
                    $subtotal = InventoryService::priceForWeight($weight, $sellingPrice);
                    $cost = $allocations[$index]['cost'];
                    $totalRevenue += $subtotal;
                    $totalCost += $cost;

                    $preparedItems[] = [
                        'category_id' => $categoryId,
                        'weight' => $weight / 100,
                        'selling_price_per_unit' => $sellingPrice,
                        'buying_price_per_unit' => $allocations[$index]['unit_price'],
                        'subtotal' => $subtotal,
                        'cost_subtotal' => $cost,
                        'profit_subtotal' => $subtotal - $cost,
                    ];
                }
            }

            $totalWeight = $totalHundredths / 100;
            $grossProfit = $totalRevenue - $totalCost;
            $sale = OfftakerSale::create([
                'id' => Str::uuid(),
                'tenant_id' => $tenantId,
                'officer_id' => $officerId,
                'buyer_name' => $validated['buyer_name'],
                'sale_date' => $validated['sale_date'],
                'total_weight' => round($totalWeight, 2),
                'total_revenue' => $totalRevenue,
                'total_cost' => $totalCost,
                'gross_profit' => $grossProfit,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($preparedItems as $pItem) {
                OfftakerSaleItem::create($pItem + ['offtaker_sale_id' => $sale->id]);
            }

            AuditLog::create([
                'id' => Str::uuid(),
                'actor_id' => $officerId,
                'tenant_id' => $tenantId,
                'actor_name' => auth()->user()?->full_name ?? 'Petugas Lapangan',
                'action' => 'RECORD_OFFTAKER_SALE',
                'target_type' => 'offtaker_sale',
                'target_id' => $sale->id,
                'details' => 'Penjualan ke pengepul '.$validated['buyer_name'].': Pendapatan Rp '.number_format($totalRevenue, 0, ',', '.').', Laba Kotor Rp '.number_format($grossProfit, 0, ',', '.'),
                'ip_address' => request()->ip(),
            ]);

            return $sale;
        });

        return redirect()->route('admin.sales.show', $sale->id)
            ->with('success', 'Penjualan ke pengepul berhasil dicatat! Laba kotor: Rp '.number_format($sale->gross_profit, 0, ',', '.'));
    }

    /**
     * Tampilkan detail nota penjualan ke pengepul.
     */
    public function show(string $id): View
    {
        $tenantId = auth()->user()->tenant_id;

        $sale = OfftakerSale::forTenant($tenantId)
            ->with(['officer', 'items.category'])
            ->findOrFail($id);

        return view('admin.penjualan.show', compact('sale'));
    }
}
