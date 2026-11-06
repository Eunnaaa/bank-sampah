<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class UnifiedAuthTest extends TestCase
{
    protected Tenant $tenant;

    protected Profile $admin;

    protected Profile $officer;

    protected Profile $resident;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::first() ?? Tenant::create([
            'id' => Str::uuid(),
            'code' => 'bs-rt05',
            'name' => 'Bank Sampah RT 05',
            'rt' => '05',
            'rw' => '08',
            'city' => 'Jakarta',
            'status' => 'active',
        ]);

        $this->admin = Profile::create([
            'id' => Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'full_name' => 'Pak RT Testing',
            'phone_number' => '089911223344',
            'rt' => '05',
            'rw' => '08',
            'house_number' => 'Blok A No. 01',
            'role' => 'admin',
            'balance' => 0,
            'password_hash' => Hash::make('admin123456'),
        ]);

        $this->officer = Profile::create([
            'id' => Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'full_name' => 'Mas Joko Petugas Test',
            'phone_number' => '089955667788',
            'rt' => '05',
            'rw' => '08',
            'house_number' => 'Blok B No. 05',
            'role' => 'officer',
            'balance' => 0,
            'password_hash' => Hash::make('petugas123456'),
        ]);

        $this->resident = Profile::create([
            'id' => Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'full_name' => 'Ibu Siti Nasabah Test',
            'phone_number' => '089977889900',
            'rt' => '05',
            'rw' => '08',
            'house_number' => 'Blok C No. 10',
            'role' => 'resident',
            'balance' => 50000,
            'pin_hash' => Hash::make('1234'),
        ]);
    }

    public function test_login_page_renders_with_unified_layout(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk ke Sistem');
        $response->assertSee('Pintu Masuk Terpadu');
        $response->assertSee('Daftar Nasabah di sini');
    }

    public function test_rt_admin_can_login_via_unified_login_page(): void
    {
        $response = $this->post('/login', [
            'phone' => '089911223344',
            'password' => 'admin123456',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_petugas_officer_can_login_via_unified_login_page(): void
    {
        $response = $this->post('/login', [
            'phone' => '089955667788',
            'password' => 'petugas123456',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->officer);
    }

    public function test_resident_can_login_via_unified_login_page(): void
    {
        $response = $this->post('/login', [
            'phone' => '089977889900',
            'password' => '1234',
        ]);

        $response->assertRedirect(route('warga.dashboard'));
        $response->assertSessionHas('resident_id', $this->resident->id);
    }

    public function test_unregistered_phone_rejected(): void
    {
        $response = $this->post('/login', [
            'phone' => '089999999999',
            'password' => '1234',
        ]);

        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_wrong_password_or_pin_rejected(): void
    {
        $response = $this->post('/login', [
            'phone' => '089911223344',
            'password' => 'salah123',
        ]);

        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_register_page_renders_with_layout(): void
    {
        $response = $this->get('/daftar');

        $response->assertStatus(200);
        $response->assertSee('Buka Tabungan Sampah');
        $response->assertSee('Khusus Nasabah Warga');
    }

    public function test_invited_resident_can_complete_registration(): void
    {
        $this->resident->update(['full_name' => 'Ahmad Warga Baru Test', 'phone_number' => '085712345678', 'house_number' => 'Blok D No. 15', 'pin_hash' => null]);
        $token = $this->resident->issueActivationToken();
        $response = $this->post('/daftar', [
            'token' => $token,
            'full_name' => 'Ahmad Warga Baru Test',
            'phone_number' => '085712345678',
            'rt' => '05',
            'rw' => '08',
            'house_number' => 'Blok D No. 15',
            'pin' => '5678',
            'pin_confirmation' => '5678',
        ]);

        $response->assertRedirect(route('warga.dashboard'));

        $this->assertDatabaseHas('profiles', [
            'phone_number' => '085712345678',
            'full_name' => 'Ahmad Warga Baru Test',
            'role' => 'resident',
            'house_number' => 'Blok D No. 15',
        ]);

        $newResident = Profile::where('phone_number', '085712345678')->first();
        $this->assertNotNull($newResident);
        $this->assertTrue(Hash::check('5678', $newResident->pin_hash));
    }

    public function test_invited_registration_strictly_preserves_resident_role(): void
    {
        $this->resident->update(['full_name' => 'Hacker Admin', 'phone_number' => '089988776655', 'pin_hash' => null]);
        $token = $this->resident->issueActivationToken();
        // Undangan tidak dapat mengubah peran akun.
        $this->post('/daftar', [
            'token' => $token,
            'full_name' => 'Hacker Admin',
            'phone_number' => '089988776655',
            'role' => 'admin',
            'rt' => '05',
            'rw' => '08',
            'house_number' => 'Blok X No. 99',
            'pin' => '9999',
            'pin_confirmation' => '9999',
        ]);

        $created = Profile::where('phone_number', '089988776655')->first();
        $this->assertNotNull($created);
        $this->assertEquals('resident', $created->role, 'Pendaftaran mandiri harus selalu role resident');
    }

    public function test_rt_can_view_officers_list(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/petugas');

        $response->assertStatus(200);
        $response->assertSee('Manajemen Petugas Lapangan');
        $response->assertSee($this->officer->full_name);
    }

    public function test_petugas_cannot_view_officers_management(): void
    {
        // Petugas (officer) dilarang mengakses manajemen petugas
        $response = $this->actingAs($this->officer)->get('/admin/petugas');

        $response->assertStatus(403);
    }

    public function test_rt_can_create_new_officer(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/petugas', [
            'full_name' => 'Budi Santoso Petugas Baru',
            'phone_number' => '081333444555',
            'house_number' => 'Blok B No. 07',
            'password' => 'petugaspass123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('profiles', [
            'phone_number' => '081333444555',
            'full_name' => 'Budi Santoso Petugas Baru',
            'role' => 'officer',
        ]);
    }

    public function test_rt_can_reset_officer_password(): void
    {
        $response = $this->actingAs($this->admin)->post("/admin/petugas/{$this->officer->id}/reset-password", [
            'new_password' => 'passwordbaru123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->officer->refresh();
        $this->assertTrue(Hash::check('passwordbaru123', $this->officer->password_hash));
    }

    public function test_legacy_routes_petugas_and_warga_masuk_still_work(): void
    {
        $response = $this->get('/petugas');
        $response->assertStatus(200);

        $response = $this->get('/warga/masuk');
        $response->assertStatus(200);
    }

    public function test_resident_cannot_access_admin_portal_routes(): void
    {
        $response = $this->actingAs($this->resident)->get('/admin');
        $response->assertRedirect(route('warga.dashboard'));
        $response->assertSessionHas('error');

        $weighResponse = $this->actingAs($this->resident)->get('/admin/timbang');
        $weighResponse->assertRedirect(route('warga.dashboard'));
        $weighResponse->assertSessionHas('error');

        $jsonResponse = $this->actingAs($this->resident)->postJson('/admin/timbang', [
            'request_id' => (string) Str::uuid(),
            'resident_id' => $this->resident->id,
            'items' => [],
        ]);
        $jsonResponse->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_admin_routes(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect(route('login'));
    }
}
