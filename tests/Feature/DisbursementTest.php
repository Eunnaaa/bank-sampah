<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Services\DisbursementService;
use Illuminate\Support\Str;
use Tests\TestCase;

class DisbursementTest extends TestCase
{
    public function test_resident_can_authorize_bank_transfer_withdrawal_and_admin_can_disburse(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['balance' => 50000]);

        $this->actingAs($resident)->withSession(['resident_id' => $resident->id]);

        $response = $this->post('/warga/profil/persetujuan-penarikan', [
            'amount' => 20000,
            'current_pin' => '1234',
            'channel' => 'transfer_bank',
            'provider' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Budi Santoso',
        ]);

        $response->assertSessionHas('withdrawal_code')
            ->assertSessionHas('withdrawal_amount', 20000)
            ->assertSessionHas('withdrawal_channel_label', 'Transfer BCA (1234567890) a.n Budi Santoso');

        $code = session('withdrawal_code');
        $approval = $resident->fresh()->withdrawal_approval;

        $this->assertSame(20000, $approval['amount']);
        $this->assertSame('transfer_bank', $approval['channel']);
        $this->assertSame('Transfer BCA (1234567890) a.n Budi Santoso', $approval['channel_label']);

        // Check profil view contains the generated code and method
        $this->get('/warga/profil')
            ->assertOk()
            ->assertSee($code)
            ->assertSee('Transfer BCA (1234567890) a.n Budi Santoso')
            ->assertSee('Rp 20.000');

        // Admin processes withdrawal
        $admin = Profile::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin);

        $withdrawResponse = $this->post('/admin/warga/tarik', [
            'request_id' => (string) Str::uuid(),
            'resident_id' => $resident->id,
            'amount' => 20000,
            'authorization_code' => $code,
            'admin_password' => 'admin123456',
            'notes' => 'Pencairan saldo via transfer bank',
        ]);

        $withdrawResponse->assertSessionHas('success')
            ->assertSessionHas('withdrawal_receipt');

        $receipt = session('withdrawal_receipt');
        $this->assertSame('Transfer BCA (1234567890) a.n Budi Santoso', $receipt['channelLabel']);
        $this->assertNotEmpty($receipt['whatsappUrl']);
        $this->assertStringContainsString('https://wa.me/', $receipt['whatsappUrl']);

        // Admin view displays the WhatsApp proof button and channel label
        $this->get('/admin/warga')
            ->assertOk()
            ->assertSee('Bukti Pencairan Tunai')
            ->assertSee('Transfer BCA (1234567890) a.n Budi Santoso')
            ->assertSee('Kirim Bukti via WhatsApp');

        $this->assertSame(30000, (int) $resident->fresh()->balance);
    }

    public function test_resident_can_authorize_ewallet_withdrawal(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['balance' => 50000]);

        $this->actingAs($resident)->withSession(['resident_id' => $resident->id]);

        $this->post('/warga/profil/persetujuan-penarikan', [
            'amount' => 15000,
            'current_pin' => '1234',
            'channel' => 'ewallet',
            'provider' => 'GoPay',
            'account_number' => '081234567890',
            'account_holder' => 'Budi Santoso',
        ])->assertSessionHas('withdrawal_channel_label', 'GoPay (081234567890) a.n Budi Santoso');

        $approval = $resident->fresh()->withdrawal_approval;
        $this->assertSame('ewallet', $approval['channel']);
        $this->assertSame('GoPay (081234567890) a.n Budi Santoso', $approval['channel_label']);
    }

    public function test_validation_requires_provider_and_account_for_non_cash_channels(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['balance' => 50000]);

        $this->actingAs($resident)->withSession(['resident_id' => $resident->id]);

        $this->post('/warga/profil/persetujuan-penarikan', [
            'amount' => 15000,
            'current_pin' => '1234',
            'channel' => 'transfer_bank',
        ])->assertSessionHasErrors(['provider', 'account_number', 'account_holder']);

        $this->post('/warga/profil/persetujuan-penarikan', [
            'amount' => 15000,
            'current_pin' => '1234',
            'channel' => 'ewallet',
        ])->assertSessionHasErrors(['provider', 'account_number', 'account_holder']);
    }

    public function test_disbursement_service_whatsapp_formatting(): void
    {
        $receipt = [
            'id' => '12345678-abcd-ef01-2345-6789abcdef01',
            'residentName' => 'Siti Aminah',
            'totalAmount' => 75000,
            'newBalance' => 25000,
            'channelLabel' => 'Transfer BCA (1234567890) a.n Siti Aminah',
            'date' => '06 November 2026, 19:30',
            'officerName' => 'Pak Budi RT',
            'type' => 'withdrawal',
        ];

        $url = DisbursementService::generateWhatsAppUrl('081234567890', $receipt);

        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $url);
        $this->assertStringContainsString('Siti%20Aminah', $url);
        $this->assertStringContainsString('75.000', $url);
        $this->assertStringContainsString('Transfer%20BCA', $url);
    }
}
