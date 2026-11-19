<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\Tenant;
use Tests\TestCase;

class WasteBankEnhancementsTest extends TestCase
{
    protected function getAdmin(): Profile
    {
        return Profile::where('role', 'admin')->firstOrFail();
    }

    protected function getResident(): Profile
    {
        return Profile::where('role', 'resident')->firstOrFail();
    }

    public function test_admin_can_register_resident_with_specific_savings_type(): void
    {
        $admin = $this->getAdmin();

        $payload = [
            'full_name' => 'Keluarga Ahmad Dahlan',
            'phone_number' => '087811223344',
            'rt' => '05',
            'rw' => '08',
            'house_number' => 'Blok D No. 15',
            'savings_type' => 'charity',
            'pin' => '5678',
        ];

        $response = $this->actingAs($admin)->post('/admin/warga', $payload);
        $response->assertSessionHasNoErrors();

        $resident = Profile::where('phone_number', '087811223344')->first();
        $this->assertNotNull($resident);
        $this->assertEquals('charity', $resident->savings_type);
        $this->assertEquals('Minat Sedekah Sampah (saldo warga)', $resident->savings_type_label);
    }

    public function test_admin_can_update_resident_savings_type(): void
    {
        $admin = $this->getAdmin();
        $resident = $this->getResident();

        $payload = [
            'full_name' => $resident->full_name,
            'phone_number' => $resident->phone_number,
            'rt' => $resident->rt,
            'rw' => $resident->rw,
            'house_number' => $resident->house_number,
            'savings_type' => 'eid',
        ];

        $response = $this->actingAs($admin)->put("/admin/warga/{$resident->id}", $payload);
        $response->assertSessionHasNoErrors();

        $resident->refresh();
        $this->assertEquals('eid', $resident->savings_type);
        $this->assertEquals('Tabungan Hari Raya', $resident->savings_type_label);
    }

    public function test_admin_can_update_operational_schedule_and_audit_log_recorded(): void
    {
        $admin = $this->getAdmin();
        $tenant = Tenant::findOrFail($admin->tenant_id);

        $payload = [
            'collection_schedule' => 'Setiap Minggu ke-1 & ke-3',
            'next_collection_date' => now()->addDays(5)->format('Y-m-d'),
            'collection_note' => 'Posko RT buka jam 08:00 - 11:00 WIB',
        ];

        $response = $this->actingAs($admin)->post('/admin/jadwal-operasional', $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $tenant->refresh();
        $this->assertEquals('Setiap Minggu ke-1 & ke-3', $tenant->collection_schedule);
        $this->assertEquals('Posko RT buka jam 08:00 - 11:00 WIB', $tenant->collection_note);

        $this->assertTrue(
            AuditLog::where('action', 'UPDATE_SCHEDULE')
                ->where('target_id', $tenant->id)
                ->exists()
        );
    }

    public function test_public_home_and_resident_dashboard_display_schedule(): void
    {
        $admin = $this->getAdmin();
        $tenant = Tenant::findOrFail($admin->tenant_id);
        $tenant->update([
            'collection_schedule' => 'Minggu Pertama Setiap Bulan',
            'next_collection_date' => now()->addDays(3)->format('Y-m-d'),
            'collection_note' => 'Bawa sampah anorganik dalam kondisi bersih',
        ]);

        // Beranda publik
        $this->get('/')
            ->assertOk()
            ->assertSee('Jadwal Penimbangan Sampah Berikutnya')
            ->assertSee('Minggu Pertama Setiap Bulan')
            ->assertSee('Pos Timbang Buka');

        // Portal Warga
        $resident = $this->getResident();
        $this->withSession(['resident_id' => $resident->id])
            ->get('/warga/dashboard')
            ->assertOk()
            ->assertSee('Jadwal Penimbangan Sampah Berikutnya di Pos RT')
            ->assertSee('Minggu Pertama Setiap Bulan');
    }
}
