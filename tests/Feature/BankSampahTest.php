<?php

namespace Tests\Feature;

use App\Actions\RecordDepositAction;
use App\Actions\RecordWithdrawalAction;
use App\Models\Profile;
use App\Models\Tenant;
use App\Models\WasteCategory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class BankSampahTest extends TestCase
{
    public function test_home_page_renders_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Si-Bank');
        $response->assertSee('Kalkulator Potensi Tabungan');
    }

    public function test_warga_portal_page_renders(): void
    {
        $response = $this->get('/warga');
        $response->assertStatus(200);
        $response->assertSee('Direktori Warga');
    }

    public function test_terms_and_conditions_page_renders(): void
    {
        $response = $this->get('/syarat-ketentuan');
        $response->assertStatus(200);
        $response->assertSee('Syarat & Ketentuan');
        $response->assertSee('Standar Penimbangan');
    }

    public function test_staff_login_page_renders(): void
    {
        $response = $this->get('/petugas');
        $response->assertStatus(200);
        $response->assertSee('Akses Pengurus RT');
    }

    public function test_admin_can_login_with_phone_variations_and_access_dashboard(): void
    {
        $admin = Profile::where('phone_number', '081234567890')->first();
        $this->assertNotNull($admin);

        // Login dengan awalan 62...
        $response = $this->post('/petugas', [
            'phone' => '6281234567890',
            'password' => 'admin123456',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);

        $dashboardResponse = $this->get('/admin');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Saldo Warga Terkelola');
        $dashboardResponse->assertSee('Tren Timbangan 7 Hari Terakhir');
    }

    public function test_record_deposit_and_withdrawal_actions(): void
    {
        $tenant = Tenant::first();
        $admin = Profile::where('role', 'admin')->first();
        $category = WasteCategory::where('is_active', true)->first();

        // 1. Buat nasabah uji dengan nomor unik
        $uniquePhone = '081'.rand(10000000, 99999999);
        $resident = Profile::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenant->id,
            'full_name' => 'Keluarga Budi Santoso',
            'phone_number' => $uniquePhone,
            'rt' => '05',
            'rw' => '08',
            'house_number' => 'Blok C No. 05',
            'role' => 'resident',
            'balance' => 0,
            'pin_hash' => Hash::make('1234'),
        ]);

        // 2. Setor sampah 10 kg
        $depositAction = app(RecordDepositAction::class);
        $depositResult = $depositAction->execute(
            $resident->id,
            $admin->id,
            [
                ['category_id' => $category->id, 'weight' => 10],
            ],
            'Setoran perdana'
        );

        $expectedTotal = 10 * $category->price_per_unit;
        $this->assertEquals($expectedTotal, $depositResult['total_amount']);
        $this->assertEquals($expectedTotal, $depositResult['new_balance']);

        // 3. Tarik tunai sebagian
        $withdrawAction = app(RecordWithdrawalAction::class);
        $withdrawAmount = intval($expectedTotal / 2);
        $withdrawResult = $withdrawAction->execute(
            $resident->id,
            $admin->id,
            $withdrawAmount,
            $this->approveWithdrawal($resident, $withdrawAmount),
            'admin123456',
            'Tarik tunai'
        );

        $this->assertEquals($expectedTotal - $withdrawAmount, $withdrawResult['remaining_balance']);

        // 4. Coba tarik melebihi saldo (harus gagal)
        $this->expectException(\InvalidArgumentException::class);
        $withdrawAction->execute(
            $resident->id,
            $admin->id,
            $expectedTotal * 2,
            $this->approveWithdrawal($resident, $expectedTotal * 2),
            'admin123456',
            'Tarik melebihi saldo'
        );
    }

    public function test_resident_balance_search_and_pin_activation(): void
    {
        $tenant = Tenant::first();
        $phone = '085'.rand(10000000, 99999999);

        // Nasabah baru belum punya PIN
        $resident = Profile::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenant->id,
            'full_name' => 'Keluarga Siti Aminah',
            'phone_number' => $phone,
            'rt' => '05',
            'rw' => '08',
            'house_number' => 'Blok D No. 12',
            'role' => 'resident',
            'balance' => 150000,
            'pin_hash' => null,
        ]);

        // Langkah 1: Coba cek saldo -> sistem minta verifikasi nomor rumah
        $resp = $this->post('/cek-saldo', [
            'phone' => $phone,
            'pin' => '4321',
        ]);
        $resp->assertStatus(200);
        $resp->assertSee('Aktivasi PIN Perdana Nasabah');

        $token = $resident->issueActivationToken();

        // Aktivasi hanya dengan undangan pengurus.
        $respActivate = $this->post('/cek-saldo', [
            'phone' => $phone,
            'pin' => '4321',
            'token' => $token,
        ]);
        $respActivate->assertStatus(200);
        $respActivate->assertSee('Saldo Tabungan Tersedia');
        $respActivate->assertSee('150.000');

        $resident->refresh();
        $this->assertNotNull($resident->pin_hash);
        $this->assertTrue(Hash::check('4321', $resident->pin_hash));

        // Langkah 3: Ubah PIN mandiri
        $respChangePin = $this->post('/cek-saldo/ubah-pin', [
            'resident_id' => $resident->id,
            'current_pin' => '4321',
            'new_pin' => '8888',
        ]);
        $respChangePin->assertSessionHas('success');

        $resident->refresh();
        $this->assertTrue(Hash::check('8888', $resident->pin_hash));
    }

    public function test_admin_resident_crud_and_reset_pin(): void
    {
        $admin = Profile::where('role', 'admin')->first();
        $this->actingAs($admin);

        $newPhone = '087'.rand(10000000, 99999999);

        // 1. Pendaftaran Warga via AJAX / Form
        $createResp = $this->postJson('/admin/warga', [
            'full_name' => 'Warga Baru Uji',
            'phone_number' => $newPhone,
            'rt' => '05',
            'rw' => '08',
            'house_number' => 'Blok E No. 01',
            'pin' => '1122',
        ]);
        $createResp->assertStatus(200);
        $residentId = $createResp->json('data.id');

        // 2. Update Warga
        $updateResp = $this->put("/admin/warga/{$residentId}", [
            'full_name' => 'Warga Baru Diperbarui',
            'phone_number' => $newPhone,
            'rt' => '05',
            'rw' => '08',
            'house_number' => 'Blok E No. 02',
        ]);
        $updateResp->assertSessionHas('success');

        // 3. Reset PIN oleh Admin
        $resetResp = $this->post("/admin/warga/{$residentId}/reset-pin", [
            'new_pin' => '9999',
        ]);
        $resetResp->assertSessionHas('success');

        $updatedResident = Profile::findOrFail($residentId);
        $this->assertEquals('Warga Baru Diperbarui', $updatedResident->full_name);
        $this->assertTrue(Hash::check('9999', $updatedResident->pin_hash));

        // 4. Cek Riwayat Mutasi Nasabah Endpoint
        $historyResp = $this->getJson("/admin/warga/{$residentId}/riwayat");
        $historyResp->assertStatus(200);
        $historyResp->assertJsonFragment(['full_name' => 'Warga Baru Diperbarui']);
    }

    public function test_category_crud_and_toggle_status(): void
    {
        $admin = Profile::where('role', 'admin')->first();
        $this->actingAs($admin);

        // 1. Tambah Komoditas Baru
        $catResp = $this->post('/admin/kategori', [
            'name' => 'Aluminium Kaleng Minuman',
            'unit' => 'kg',
            'price_per_unit' => 12500,
        ]);
        $catResp->assertSessionHas('success');

        $cat = WasteCategory::where('name', 'Aluminium Kaleng Minuman')->first();
        $this->assertNotNull($cat);
        $this->assertEquals(12500, $cat->price_per_unit);

        // 2. Update Komoditas
        $updateResp = $this->put("/admin/kategori/{$cat->id}", [
            'name' => 'Aluminium & Seng Khusus',
            'unit' => 'kg',
            'price_per_unit' => 14000,
        ]);
        $updateResp->assertSessionHas('success');

        $cat->refresh();
        $this->assertEquals('Aluminium & Seng Khusus', $cat->name);
        $this->assertEquals(14000, $cat->price_per_unit);

        // 3. Toggle Status Aktif / Non-Aktif
        $toggleResp = $this->put("/admin/kategori/{$cat->id}/toggle");
        $toggleResp->assertSessionHas('success');
        $cat->refresh();
        $this->assertFalse($cat->is_active);

        // 4. Hapus Komoditas (belum ada transaksi)
        $delResp = $this->delete("/admin/kategori/{$cat->id}");
        $delResp->assertSessionHas('success');
        $this->assertNull(WasteCategory::find($cat->id));
    }

    public function test_weighing_store_and_show_receipt_api(): void
    {
        $admin = Profile::where('role', 'admin')->first();
        $resident = Profile::where('role', 'resident')->first();
        $category = WasteCategory::where('is_active', true)->first();
        $this->actingAs($admin);

        // Catat timbangan via API POST
        $weighResp = $this->postJson('/admin/timbang', [
            'request_id' => (string) Str::uuid(),
            'resident_id' => $resident->id,
            'items' => [
                ['category_id' => $category->id, 'weight' => 5.5],
            ],
            'notes' => 'Timbangan sore hari',
        ]);

        $weighResp->assertStatus(200);
        $txId = $weighResp->json('data.transaction_id');

        // Ambil detail struk via API GET
        $showResp = $this->getJson("/admin/transaksi/{$txId}");
        $showResp->assertStatus(200);
        $showResp->assertJsonFragment(['residentName' => $resident->full_name]);
        $showResp->assertJsonFragment(['notes' => 'Timbangan sore hari']);
    }

    public function test_audit_log_and_reports_access(): void
    {
        $admin = Profile::where('role', 'admin')->first();
        $this->actingAs($admin);

        // Halaman Laporan
        $reportResp = $this->get('/admin/laporan');
        $reportResp->assertStatus(200);
        $reportResp->assertSee('Buku Besar Mutasi Kas Transaksi');

        // Ekspor CSV Laporan
        $csvResp = $this->get('/admin/laporan/export-csv');
        $csvResp->assertStatus(200);
        $csvResp->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // Ekspor Ledger CSV
        $ledgerResp = $this->get('/admin/laporan/export-ledger');
        $ledgerResp->assertStatus(200);
        $ledgerResp->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // Halaman Audit Log
        $auditResp = $this->get('/admin/audit-log');
        $auditResp->assertStatus(200);
        $auditResp->assertSee('Log Audit & Kepatuhan Sistem');
    }
}
