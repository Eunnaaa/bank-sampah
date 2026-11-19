<?php

namespace App\Services;

use App\Models\OfftakerSaleItem;
use App\Models\TransactionItem;
use App\Models\WasteCategory;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * Mengambil ringkasan stok gudang per komoditas untuk tenant tertentu.
     */
    public function getInventorySummary(string $tenantId): Collection
    {
        $categories = WasteCategory::forTenant($tenantId)
            ->orderBy('name')
            ->get();

        // 1. Total timbangan masuk dari warga (deposit)
        $inflowAggregates = TransactionItem::forTenant($tenantId)
            ->whereHas('transaction', fn ($q) => $q->where('type', 'deposit'))
            ->groupBy('category_id')
            ->selectRaw('category_id, coalesce(sum(weight), 0) as total_weight')
            ->get()
            ->keyBy('category_id');

        // 2. Total timbangan keluar ke pengepul (offtaker sale)
        $outflowAggregates = OfftakerSaleItem::forTenant($tenantId)
            ->groupBy('category_id')
            ->selectRaw('category_id, coalesce(sum(weight), 0) as total_weight, coalesce(sum(subtotal), 0) as total_revenue, coalesce(sum(profit_subtotal), 0) as total_profit')
            ->get()
            ->keyBy('category_id');

        return $categories->map(function (WasteCategory $cat) use ($inflowAggregates, $outflowAggregates) {
            $in = $inflowAggregates->get($cat->id);
            $out = $outflowAggregates->get($cat->id);

            $inWeight = round((float) ($in?->total_weight ?? 0), 2);
            $outWeight = round((float) ($out?->total_weight ?? 0), 2);
            $currentStock = round($inWeight - $outWeight, 2);

            return [
                'category_id' => $cat->id,
                'name' => $cat->name,
                'unit' => $cat->unit,
                'is_active' => $cat->is_active,
                'buying_price' => $cat->price_per_unit,
                'in_weight' => $inWeight,
                'out_weight' => $outWeight,
                'stock_weight' => $currentStock,
                'estimated_stock_value' => (int) round(max(0, $currentStock) * $cat->price_per_unit),
                'total_revenue' => (int) ($out?->total_revenue ?? 0),
                'total_profit' => (int) ($out?->total_profit ?? 0),
            ];
        });
    }

    /**
     * Mengambil metrik global inventaris dan laba operasional pengepul.
     */
    public function getInventoryMetrics(Collection $summary): array
    {
        $totalStockKg = $summary->where('unit', 'kg')->sum(fn (array $item) => max(0, $item['stock_weight']));
        $totalStockValue = $summary->sum('estimated_stock_value');
        $totalRevenue = $summary->sum('total_revenue');
        $totalProfit = $summary->sum('total_profit');

        return [
            'total_stock_kg' => round($totalStockKg, 2),
            'total_stock_value' => $totalStockValue,
            'total_offtaker_revenue' => $totalRevenue,
            'total_gross_profit' => $totalProfit,
        ];
    }

    /**
     * Allocate the oldest recorded deposits first. The caller holds the category lock.
     *
     * @param  list<int>  $weights  Hundredths of the category unit.
     * @return list<array{cost: int, unit_price: int}>
     */
    public function saleCosts(WasteCategory $category, array $weights): array
    {
        $sold = OfftakerSaleItem::forTenant($category->tenant_id)
            ->where('category_id', $category->id)
            ->sum('weight');
        $skip = (int) round((float) $sold * 100);
        $deposits = TransactionItem::forTenant($category->tenant_id)
            ->where('category_id', $category->id)
            ->whereHas('transaction', fn ($query) => $query->where('type', 'deposit'))
            ->orderByRaw('inventory_sequence is not null')
            ->orderBy('inventory_sequence')
            ->orderBy('created_at')
            ->orderBy('id')
            ->lazy();

        $allocations = array_fill(0, count($weights), ['cost' => 0, 'unit_price' => 0]);
        $originalWeights = $weights;
        $line = 0;

        foreach ($deposits as $deposit) {
            $lot = (int) round((float) $deposit->weight * 100);
            $used = min($skip, $lot);
            $skip -= $used;

            while ($used < $lot) {
                $portion = min($lot - $used, $weights[$line]);
                $price = $deposit->price_per_unit_at_transaction;
                // Round at each lot boundary so splitting a lot preserves its total cost.
                $allocations[$line]['cost'] += self::priceForWeight($used + $portion, $price) - self::priceForWeight($used, $price);
                $allocations[$line]['unit_price'] += ($portion / $originalWeights[$line]) * $price;
                $used += $portion;
                $weights[$line] -= $portion;

                if ($weights[$line] === 0) {
                    $allocations[$line]['unit_price'] = (int) round($allocations[$line]['unit_price']);

                    if (++$line === count($weights)) {
                        return $allocations;
                    }
                }
            }
        }

        throw ValidationException::withMessages(['items' => "Stok {$category->name} tidak mencukupi."]);
    }

    public static function priceForWeight(int $hundredths, int $price): int
    {
        return intdiv($hundredths, 100) * $price
            + intdiv(($hundredths % 100) * $price + 50, 100);
    }
}
