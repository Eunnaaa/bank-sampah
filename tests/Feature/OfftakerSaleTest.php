<?php

namespace Tests\Feature;

use App\Actions\RecordDepositAction;
use App\Models\AuditLog;
use App\Models\OfftakerSale;
use App\Models\Profile;
use App\Models\Tenant;
use App\Models\WasteCategory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OfftakerSaleTest extends TestCase
{
    protected function getAdmin(): Profile
    {
        return Profile::where('role', 'admin')->firstOrFail();
    }

    public function test_unauthenticated_user_cannot_access_penjualan(): void
    {
        $this->get('/admin/penjualan')->assertRedirect('/login');
    }

    public function test_admin_can_view_penjualan_index_and_inventory_summary(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get('/admin/penjualan');

        $response->assertOk()
            ->assertSee('Gudang &amp; Penjualan ke Pengepul', false)
            ->assertSee('Inventaris Komoditas di Gudang RT');
    }

    public function test_admin_can_view_penjualan_create_form(): void
    {
        $admin = $this->getAdmin();

        $this->actingAs($admin)->get('/admin/penjualan/buat')
            ->assertOk()->assertSee('Belum ada komoditas dengan stok minimal');

        $this->deposit($admin, $this->category($admin), 1);
        $response = $this->get('/admin/penjualan/buat');

        $response->assertOk()
            ->assertSee('Catat Penjualan ke Pengepul')
            ->assertSee('Nama Pengepul / Perusahaan Daur Ulang');
    }

    public function test_admin_can_record_offtaker_sale_and_see_correct_profit_and_audit(): void
    {
        $admin = $this->getAdmin();
        $this->freezeSecond();
        $category = $this->category($admin);
        $originalPrice = $category->price_per_unit;
        $this->deposit($admin, $category, 12);
        $category->update(['price_per_unit' => $originalPrice + 500]);
        $this->deposit($admin, $category, 12);
        $category->update(['price_per_unit' => $originalPrice + 1000]);

        $payload = $this->salePayload($category, 20);
        $response = $this->actingAs($admin)->post('/admin/penjualan', $payload);

        $sale = OfftakerSale::where('buyer_name', 'CV Maju Daur Ulang')->first();
        $this->assertNotNull($sale);
        $response->assertRedirect(route('admin.sales.show', $sale->id));

        $expectedCost = 12 * $originalPrice + 8 * ($originalPrice + 500);
        $expectedRevenue = 20 * $payload['items'][0]['selling_price_per_unit'];
        $expectedProfit = $expectedRevenue - $expectedCost;

        $this->assertEquals($expectedRevenue, $sale->total_revenue);
        $this->assertEquals($expectedCost, $sale->total_cost);
        $this->assertEquals($expectedProfit, $sale->gross_profit);

        // Verifikasi audit log
        $this->assertTrue(
            AuditLog::where('action', 'RECORD_OFFTAKER_SALE')
                ->where('target_id', $sale->id)
                ->exists()
        );

        // Verifikasi halaman detail nota
        $detailResponse = $this->actingAs($admin)->get(route('admin.sales.show', $sale->id));
        $detailResponse->assertOk()
            ->assertSee('CV Maju Daur Ulang')
            ->assertSee(number_format($expectedProfit, 0, ',', '.'));

        $this->post(route('admin.sales.store'), $this->salePayload($category, 4) + ['notes' => 'Sisa stok'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('offtaker_sales', [
            'notes' => 'Sisa stok',
            'total_cost' => 4 * ($originalPrice + 500),
        ]);
    }

    public function test_sale_checks_combined_stock_and_leaves_no_partial_sale(): void
    {
        $admin = $this->getAdmin();
        $category = $this->category($admin);
        $payload = $this->salePayload($category, 3);

        $this->actingAs($admin)->post(route('admin.sales.store'), $payload)->assertSessionHasErrors('items');

        $this->deposit($admin, $category, 5);
        $payload['items'][] = $payload['items'][0];
        $this->post(route('admin.sales.store'), $payload)->assertSessionHasErrors('items');
        $this->assertDatabaseCount('offtaker_sales', 0);

        $payload['items'][1]['weight'] = 2;
        $this->post(route('admin.sales.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('admin.sales.store'), $this->salePayload($category, 0.1))->assertSessionHasErrors('items');
        $this->assertDatabaseCount('offtaker_sales', 1);
        $this->assertDatabaseCount('offtaker_sale_items', 2);
    }

    public function test_sale_rejects_the_entire_request_if_a_category_belongs_to_another_rt(): void
    {
        $admin = $this->getAdmin();
        $category = $this->category($admin);
        $this->deposit($admin, $category, 3);
        $otherTenant = Tenant::create(['code' => 'offtaker-other', 'name' => 'RT Lain', 'rt' => '98', 'rw' => '98']);
        $foreignCategory = $category->replicate()->fill(['tenant_id' => $otherTenant->id]);
        $foreignCategory->save();

        $payload = $this->salePayload($category, 1);
        $payload['items'][] = $this->salePayload($foreignCategory, 1)['items'][0];
        $this->actingAs($admin)->post(route('admin.sales.store'), $payload)->assertSessionHasErrors('items');
        $this->assertDatabaseCount('offtaker_sales', 0);
    }

    public function test_inactive_category_keeps_stock_revenue_and_profit_visible(): void
    {
        $admin = $this->getAdmin();
        $category = $this->category($admin);
        $this->deposit($admin, $category, 5);

        $this->actingAs($admin)->post(route('admin.sales.store'), $this->salePayload($category, 2))
            ->assertSessionHasNoErrors();
        $sale = OfftakerSale::sole();
        $category->update(['is_active' => false]);

        $this->get(route('admin.sales.index'))->assertOk()
            ->assertViewHas('inventorySummary', fn ($summary): bool => $summary->firstWhere('category_id', $category->id)['stock_weight'] === 3.0)
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['total_offtaker_revenue'] === $sale->total_revenue
                && $metrics['total_gross_profit'] === $sale->gross_profit);
        $this->get(route('admin.sales.create'))->assertOk()
            ->assertViewHas('inventorySummary', fn ($summary): bool => $summary->contains('category_id', $category->id));
    }

    public function test_sale_holds_a_lock_that_blocks_a_second_connection_during_the_write(): void
    {
        $admin = $this->getAdmin();
        $category = $this->category($admin);
        $resident = Profile::forTenant($admin->tenant_id)->where('role', 'resident')->firstOrFail();
        $deposit = $resident->transactions()->create([
            'officer_id' => $admin->id,
            'type' => 'deposit',
            'total_amount' => 5 * $category->price_per_unit,
        ]);
        $deposit->items()->create([
            'category_id' => $category->id,
            'weight' => 5,
            'price_per_unit_at_transaction' => $category->price_per_unit,
            'subtotal' => 5 * $category->price_per_unit,
        ]);

        $connection = DB::connectUsing('offtaker_lock_test', DB::connection()->getConfig());
        $categoryLock = $connection->table('waste_categories')->where('id', $category->id)->lock('for share nowait');
        $checked = false;

        Event::listen('eloquent.creating: '.OfftakerSale::class, function () use ($categoryLock, &$checked): void {
            try {
                $categoryLock->first();
                $this->fail('The sale did not hold an exclusive category lock.');
            } catch (QueryException $exception) {
                $this->assertSame('55P03', $exception->getCode());
                $checked = true;
            }
        });

        try {
            $this->assertNotNull($categoryLock->first());
            $this->actingAs($admin)->post(route('admin.sales.store'), $this->salePayload($category, 5))
                ->assertSessionHasNoErrors();
            $this->assertTrue($checked);
        } finally {
            DB::purge($connection->getName());
        }
    }

    private function category(Profile $admin): WasteCategory
    {
        return WasteCategory::forTenant($admin->tenant_id)->where('is_active', true)->firstOrFail();
    }

    private function deposit(Profile $admin, WasteCategory $category, int $weight): void
    {
        $resident = Profile::forTenant($admin->tenant_id)->where('role', 'resident')->firstOrFail();
        app(RecordDepositAction::class)->execute($resident->id, $admin->id, [
            ['category_id' => $category->id, 'weight' => $weight],
        ]);
    }

    private function salePayload(WasteCategory $category, float $weight): array
    {
        return [
            'buyer_name' => 'CV Maju Daur Ulang',
            'sale_date' => now()->toDateString(),
            'items' => [[
                'category_id' => $category->id,
                'weight' => $weight,
                'selling_price_per_unit' => $category->price_per_unit + 1000,
            ]],
        ];
    }
}
