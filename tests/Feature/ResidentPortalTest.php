<?php

namespace Tests\Feature;

use App\Actions\RecordDepositAction;
use App\Models\Profile;
use App\Models\Tenant;
use App\Models\WasteCategory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResidentPortalTest extends TestCase
{
    protected Profile $resident;

    protected Profile $officer;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::first() ?? Tenant::create([
            'id' => Str::uuid(),
            'code' => 'bs-test',
            'name' => 'Bank Sampah Test',
            'rt' => '05',
            'rw' => '08',
            'status' => 'active',
        ]);

        $this->officer = Profile::where('role', 'admin')->first() ?? Profile::create([
            'id' => Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'full_name' => 'Petugas Lapangan',
            'phone_number' => '081299990001',
            'rt' => '05',
            'rw' => '08',
            'house_number' => 'Pos RT',
            'role' => 'officer',
            'balance' => 0,
            'password_hash' => Hash::make('password123'),
        ]);

        $this->resident = Profile::create([
            'id' => Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'full_name' => 'Ibu Siti Aminah',
            'phone_number' => '081555444333',
            'rt' => '05',
            'rw' => '08',
            'house_number' => 'Blok D No. 12',
            'role' => 'resident',
            'balance' => 50000,
            'pin_hash' => Hash::make('4321'),
        ]);
    }

    public function test_unauthenticated_resident_is_redirected_to_warga_login(): void
    {
        $response = $this->get('/warga/dashboard');
        $response->assertRedirect(route('warga.login'));

        $riwayatResponse = $this->get('/warga/riwayat');
        $riwayatResponse->assertRedirect(route('warga.login'));

        $katalogResponse = $this->get('/warga/katalog');
        $katalogResponse->assertRedirect(route('warga.login'));

        $profilResponse = $this->get('/warga/profil');
        $profilResponse->assertRedirect(route('warga.login'));
    }

    public function test_resident_login_page_renders_successfully(): void
    {
        $response = $this->get('/warga/masuk');
        $response->assertStatus(200);
        $response->assertSee('Masuk Akun Nasabah');
        $response->assertSee('PIN Keamanan (4 Digit)');
    }

    public function test_resident_can_login_with_valid_whatsapp_and_pin(): void
    {
        $response = $this->post('/warga/masuk', [
            'phone' => '081555444333',
            'pin' => '4321',
        ]);

        $response->assertRedirect(route('warga.dashboard'));
        $this->assertEquals($this->resident->id, session('resident_id'));
    }

    public function test_resident_cannot_login_with_invalid_pin(): void
    {
        $response = $this->post('/warga/masuk', [
            'phone' => '081555444333',
            'pin' => '9999',
        ]);

        $response->assertSessionHas('error');
        $this->assertNull(session('resident_id'));
    }

    public function test_resident_without_pin_is_prompted_for_first_time_activation(): void
    {
        $newResident = Profile::create([
            'id' => Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'full_name' => 'Warga Baru Belum Aktivasi',
            'phone_number' => '081777888999',
            'rt' => '05',
            'rw' => '08',
            'house_number' => 'Blok E No. 01',
            'role' => 'resident',
            'balance' => 0,
            'pin_hash' => null,
        ]);

        $response = $this->post('/warga/masuk', [
            'phone' => '081777888999',
            'pin' => '5678',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Aktivasi PIN Nasabah');
        $response->assertSee('Kode Undangan dari Pengurus');
    }

    public function test_resident_can_activate_pin_with_one_time_invitation(): void
    {
        $newResident = Profile::create([
            'id' => Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'full_name' => 'Warga Verifikasi Sukses',
            'phone_number' => '081888111222',
            'rt' => '05',
            'rw' => '08',
            'house_number' => 'Blok F No. 09',
            'role' => 'resident',
            'balance' => 0,
            'pin_hash' => null,
        ]);

        $token = $newResident->issueActivationToken();
        $response = $this->post('/warga/aktivasi', [
            'token' => $token,
            'phone' => '081888111222',
            'pin' => '7788',
            'house_number' => 'Blok F No. 09',
        ]);

        $response->assertRedirect(route('warga.dashboard'));
        $this->assertEquals($newResident->id, session('resident_id'));

        $newResident->refresh();
        $this->assertTrue(Hash::check('7788', $newResident->pin_hash));
    }

    public function test_resident_cannot_activate_pin_using_only_a_house_number(): void
    {
        $newResident = Profile::create([
            'id' => Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'full_name' => 'Warga Salah Blok',
            'phone_number' => '081999333444',
            'rt' => '05',
            'rw' => '08',
            'house_number' => 'Blok G No. 10',
            'role' => 'resident',
            'balance' => 0,
            'pin_hash' => null,
        ]);

        $response = $this->post('/warga/aktivasi', [
            'phone' => '081999333444',
            'pin' => '7788',
            'house_number' => 'Blok Z No. 99', // salah
        ]);

        $response->assertSessionHas('error');
        $newResident->refresh();
        $this->assertNull($newResident->pin_hash);
    }

    public function test_resident_can_view_dashboard_with_accurate_balance_and_weight(): void
    {
        $category = WasteCategory::first() ?? WasteCategory::create([
            'id' => Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'name' => 'Kardus Bersih',
            'unit' => 'kg',
            'price_per_unit' => 2000,
            'is_active' => true,
        ]);

        // Simulasikan setoran timbangan
        $action = new RecordDepositAction;
        $action->execute(
            residentId: $this->resident->id,
            officerId: $this->officer->id,
            items: [
                ['category_id' => $category->id, 'weight' => 12.5],
            ],
            notes: 'Kardus kering rapi'
        );

        $response = $this->withSession(['resident_id' => $this->resident->id])
            ->get('/warga/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Halo,');
        $response->assertSee('Ibu Siti Aminah');
        $response->assertSee('12.5'); // berat sampah kg
        $response->assertSee('KARTU DIGITAL NASABAH');
    }

    public function test_resident_can_view_transaction_history_with_filters(): void
    {
        $response = $this->withSession(['resident_id' => $this->resident->id])
            ->get('/warga/riwayat?type=deposit&period=all');

        $response->assertStatus(200);
        $response->assertSee('Riwayat Setoran & Penarikan');
        $response->assertSee('Akumulasi Setoran Periode Ini');
    }

    public function test_resident_can_view_price_catalog(): void
    {
        $response = $this->withSession(['resident_id' => $this->resident->id])
            ->get('/warga/katalog');

        $response->assertStatus(200);
        $response->assertSee('Harga Beli Komoditas Hari Ini');
        $response->assertSee('Kalkulator Estimasi Tabungan');
    }

    public function test_resident_can_view_profile_and_digital_qr_card(): void
    {
        $response = $this->withSession(['resident_id' => $this->resident->id])
            ->get('/warga/profil');

        $response->assertStatus(200);
        $response->assertSee('KARTU NASABAH RESMI');
        $response->assertSee('Uji Pindai QR Code (Scanner Simulasi)');
        $response->assertSee('Ganti PIN Keamanan (4 Digit)');
    }

    public function test_resident_can_change_pin_successfully(): void
    {
        $response = $this->withSession(['resident_id' => $this->resident->id])
            ->post('/warga/profil/ganti-pin', [
                'current_pin' => '4321',
                'new_pin' => '9876',
                'new_pin_confirmation' => '9876',
            ]);

        $response->assertSessionHas('success');
        $this->resident->refresh();
        $this->assertTrue(Hash::check('9876', $this->resident->pin_hash));
    }

    public function test_resident_can_view_transaction_receipt_json(): void
    {
        $category = WasteCategory::first() ?? WasteCategory::create([
            'id' => Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'name' => 'Kardus Bersih',
            'unit' => 'kg',
            'price_per_unit' => 2000,
            'is_active' => true,
        ]);

        $action = new RecordDepositAction;
        $depositResult = $action->execute(
            residentId: $this->resident->id,
            officerId: $this->officer->id,
            items: [
                ['category_id' => $category->id, 'weight' => 5.0],
            ],
            notes: 'Struk setoran digital test'
        );

        $txId = $depositResult['transaction_id'];

        $response = $this->withSession(['resident_id' => $this->resident->id])
            ->getJson("/warga/transaksi/{$txId}");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.residentName', $this->resident->full_name);
        $response->assertJsonPath('data.totalAmount', (int) ($category->price_per_unit * 5.0));
    }

    public function test_resident_cannot_change_pin_with_wrong_current_pin(): void
    {
        $response = $this->withSession(['resident_id' => $this->resident->id])
            ->post('/warga/profil/ganti-pin', [
                'current_pin' => '0000', // salah
                'new_pin' => '9876',
                'new_pin_confirmation' => '9876',
            ]);

        $response->assertSessionHas('error');
        $this->resident->refresh();
        $this->assertTrue(Hash::check('4321', $this->resident->pin_hash));
    }

    public function test_resident_can_logout(): void
    {
        $response = $this->withSession(['resident_id' => $this->resident->id])
            ->post('/warga/keluar');

        $response->assertRedirect(route('warga.login'));
        $this->assertNull(session('resident_id'));
    }

    public function test_resident_api_endpoints_return_valid_data(): void
    {
        $summaryResp = $this->withSession(['resident_id' => $this->resident->id])
            ->getJson('/api/warga/ringkasan');

        $summaryResp->assertStatus(200);
        $summaryResp->assertJsonPath('success', true);
        $summaryResp->assertJsonPath('data.name', 'Ibu Siti Aminah');

        $qrResp = $this->withSession(['resident_id' => $this->resident->id])
            ->getJson('/api/warga/qrcode');

        $qrResp->assertStatus(200);
        $qrResp->assertJsonPath('success', true);
        $this->assertStringContainsString('<svg', $qrResp->json('qr_code_svg'));

        $catResp = $this->getJson('/api/warga/katalog');
        $catResp->assertStatus(200);
        $catResp->assertJsonPath('success', true);
    }

    public function test_resident_api_rejects_unauthenticated_requests(): void
    {
        $unauthResp = $this->getJson("/api/warga/ringkasan?resident_id={$this->resident->id}");
        $unauthResp->assertStatus(401);
        $unauthResp->assertJsonPath('success', false);

        $unauthQr = $this->withHeaders(['X-Resident-Id' => $this->resident->id])
            ->getJson('/api/warga/qrcode');
        $unauthQr->assertStatus(401);
        $unauthQr->assertJsonPath('success', false);
    }
}
