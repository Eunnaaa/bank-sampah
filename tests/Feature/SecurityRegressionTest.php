<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\WasteCategory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    #[TestWith(['/aktivasi-pin'])]
    #[TestWith(['/warga/aktivasi'])]
    public function test_activation_cannot_overwrite_an_active_pin(string $path): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $originalHash = $resident->pin_hash;

        $this->post($path, [
            'phone' => $resident->phone_number,
            'pin' => '9876',
            'house_number' => $resident->house_number,
        ])->assertSessionHas('error');

        $this->assertSame($originalHash, $resident->fresh()->pin_hash);
        $this->assertGuest();
        $this->assertDatabaseCount('audit_logs', 0);
    }

    #[TestWith(['/aktivasi-pin', '1'])]
    #[TestWith(['/aktivasi-pin', '!!!'])]
    #[TestWith(['/warga/aktivasi', '1'])]
    #[TestWith(['/warga/aktivasi', '!!!'])]
    #[TestWith(['/cek-saldo', '1'])]
    #[TestWith(['/cek-saldo', '!!!'])]
    public function test_activation_rejects_partial_or_empty_normalized_addresses(string $path, string $houseNumber): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['pin_hash' => null]);

        $this->post($path, [
            'phone' => $resident->phone_number,
            'pin' => '9876',
            'house_number' => $houseNumber,
        ]);

        $this->assertNull($resident->fresh()->pin_hash);
        $this->assertGuest();
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_login_limit_is_shared_across_aliases_and_phone_formats(): void
    {
        foreach (['/login', '/masuk', '/petugas', '/warga/masuk', '/login'] as $path) {
            $this->post($path, ['phone' => '081999888777', 'pin' => '9999'])
                ->assertSessionHas('error');
        }

        $this->post('/masuk', ['phone' => '+62 81999888777', 'pin' => '1234'])
            ->assertStatus(429)->assertHeader('Retry-After');
        $this->assertGuest();
    }

    #[TestWith(['/aktivasi-pin'])]
    #[TestWith(['/warga/aktivasi'])]
    #[TestWith(['/cek-saldo'])]
    public function test_pin_endpoints_limit_attempts(string $path): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $originalHash = $resident->pin_hash;
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $response = $this->post($path, ['phone' => $resident->phone_number, 'pin' => '9999']);
        }

        $response->assertStatus(429);
        $this->assertSame($originalHash, $resident->fresh()->pin_hash);
    }

    public function test_pin_change_limit_cannot_be_bypassed_with_a_different_phone(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $originalHash = $resident->pin_hash;
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $response = $this->post('/cek-saldo/ubah-pin', [
                'phone' => '0810000000'.$attempt, 'resident_id' => $resident->id,
                'current_pin' => '9999', 'new_pin' => '2222',
            ]);
        }

        $response->assertStatus(429);
        $this->assertSame($originalHash, $resident->fresh()->pin_hash);
    }

    public function test_first_activation_requires_a_one_time_invitation(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['pin_hash' => null]);

        $token = $resident->issueActivationToken();
        $this->post('/aktivasi-pin', [
            'phone' => $resident->phone_number, 'pin' => '9876',
            'token' => $token,
        ])->assertRedirect(route('warga.dashboard'));

        $this->assertTrue(Hash::check('9876', $resident->fresh()->pin_hash));
        $this->assertAuthenticatedAs($resident);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $resident->tenant_id, 'action' => 'RESIDENT_PIN_ACTIVATED',
        ]);
    }

    public function test_resident_catalog_only_returns_categories_from_their_tenant(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        [$otherResident, $otherCategory] = $this->otherTenantRecords();

        $this->withSession(['resident_id' => $resident->id])
            ->getJson('/api/warga/katalog')->assertOk()->assertJsonMissing(['id' => $otherCategory->id]);
        $this->get('/warga/katalog')->assertOk()->assertDontSee($otherCategory->name);
    }

    public function test_staff_cannot_read_or_modify_another_tenants_data(): void
    {
        $admin = Profile::where('role', 'admin')->firstOrFail();
        [$resident, $category] = $this->otherTenantRecords();
        $transaction = Transaction::create([
            'resident_id' => $resident->id, 'type' => 'deposit', 'total_amount' => 1000,
        ]);
        $this->actingAs($admin);

        $this->getJson("/admin/warga/{$resident->id}/riwayat")->assertNotFound();
        $this->post("/admin/warga/{$resident->id}/reset-pin", ['new_pin' => '2222'])->assertNotFound();
        $this->put("/admin/warga/{$resident->id}", [])->assertNotFound();
        $this->getJson("/admin/transaksi/{$transaction->id}")->assertNotFound();
        $this->put("/admin/kategori/{$category->id}/harga", ['price_per_unit' => 99])->assertNotFound();
        $this->delete("/admin/kategori/{$category->id}")->assertNotFound();
        $this->get('/admin/warga')->assertOk()->assertDontSee($resident->full_name);
        $this->get('/admin/kategori')->assertOk()->assertDontSee($category->name);
        $this->get('/admin')->assertOk()->assertDontSee($resident->full_name)->assertDontSee($category->name);
        $this->get('/admin/laporan')->assertOk()->assertDontSee($resident->full_name)->assertDontSee($category->name);
        $csv = $this->get('/admin/laporan/export-csv');
        $csv->assertOk();
        $this->assertStringNotContainsString($category->name, $csv->streamedContent());
        $ledger = $this->get('/admin/laporan/export-ledger');
        $ledger->assertOk();
        $this->assertStringNotContainsString($resident->full_name, $ledger->streamedContent());
        $this->assertSame(10000, (int) $resident->fresh()->balance);
        $this->assertSame(1000, $category->fresh()->price_per_unit);
    }

    public function test_cross_tenant_deposit_and_withdrawal_leave_money_unchanged(): void
    {
        $admin = Profile::where('role', 'admin')->firstOrFail();
        [$resident, $category] = $this->otherTenantRecords();
        $ownResident = Profile::where('role', 'resident')->where('tenant_id', $admin->tenant_id)->firstOrFail();
        $this->actingAs($admin);

        foreach ([$resident, $ownResident] as $target) {
            $this->postJson('/admin/timbang', [
                'request_id' => (string) Str::uuid(),
                'resident_id' => $target->id,
                'items' => [['category_id' => $category->id, 'weight' => 1]],
            ])->assertUnprocessable();
        }
        $this->post('/admin/warga/tarik', ['request_id' => (string) Str::uuid(), 'resident_id' => $resident->id, 'amount' => 1000, 'authorization_code' => 'APPROVAL1234', 'admin_password' => 'admin123456'])
            ->assertSessionHas('error');

        $this->assertSame(10000, (int) $resident->fresh()->balance);
        $this->assertSame(0, (int) $ownResident->fresh()->balance);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_items', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_audit_logs_keep_their_tenant_after_the_officer_is_deleted(): void
    {
        $admin = Profile::where('role', 'admin')->firstOrFail();
        $officer = Profile::where('role', 'officer')->firstOrFail();
        $log = AuditLog::create([
            'tenant_id' => $admin->tenant_id, 'actor_id' => $officer->id,
            'actor_name' => $officer->full_name, 'action' => 'STAFF_LOGIN',
            'target_type' => 'auth', 'target_id' => $officer->id, 'details' => 'Riwayat petugas lama',
        ]);
        $officer->delete();

        $this->actingAs($admin)->get('/admin/audit-log')->assertOk()->assertSee($log->details);
    }

    public function test_audit_migration_backfills_known_actors_and_keeps_unknown_actors_unassigned(): void
    {
        $admin = Profile::where('role', 'admin')->firstOrFail();
        $migration = require database_path('migrations/2026_11_05_105858_add_tenant_id_to_audit_logs_table.php');
        $migration->down();
        $log = AuditLog::create([
            'actor_id' => $admin->id, 'action' => 'STAFF_LOGIN',
            'target_type' => 'auth', 'target_id' => $admin->id,
        ]);
        $unknown = AuditLog::create([
            'actor_id' => 'deleted-actor', 'action' => 'STAFF_LOGIN',
            'target_type' => 'auth', 'target_id' => 'deleted-actor',
        ]);

        $migration->up();

        $this->assertSame($admin->tenant_id, $log->fresh()->tenant_id);
        $this->assertNull($unknown->fresh()->tenant_id);
    }

    /** @return array{Profile, WasteCategory} */
    private function otherTenantRecords(): array
    {
        $tenant = Tenant::create(['code' => 'other-rt', 'name' => 'RT Lain', 'rt' => '06', 'rw' => '08']);
        $resident = Profile::create([
            'tenant_id' => $tenant->id, 'full_name' => 'Nasabah Rahasia RT Lain',
            'phone_number' => '081777000111', 'rt' => '06', 'rw' => '08',
            'house_number' => 'Z99', 'role' => 'resident', 'balance' => 10000,
            'pin_hash' => Hash::make('1234'),
        ]);
        $category = WasteCategory::create([
            'tenant_id' => $tenant->id, 'name' => 'Kategori Rahasia RT Lain',
            'unit' => 'kg', 'price_per_unit' => 1000, 'is_active' => true,
        ]);

        return [$resident, $category];
    }
}
