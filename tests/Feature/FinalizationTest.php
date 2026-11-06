<?php

namespace Tests\Feature;

use App\Actions\RecordWithdrawalAction;
use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\WasteCategory;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class FinalizationTest extends TestCase
{
    public function test_production_hides_demo_accounts_and_rejects_existing_demo_credentials(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        $this->get('/login')->assertOk()->assertDontSee('admin123456')->assertDontSee('Akun Demo');
        $token = csrf_token();
        $this->withSession(['_token' => $token])->post('/login', ['_token' => $token, 'phone' => '081234567890', 'password' => 'admin123456'])
            ->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_demo_seeder_is_forbidden_in_production(): void
    {
        $this->app['env'] = 'production';
        $this->expectException(\LogicException::class);

        app(DatabaseSeeder::class)->run();
    }

    public function test_staff_login_clears_previous_resident_identity(): void
    {
        $this->post('/login', ['phone' => '081999888777', 'pin' => '1234'])->assertRedirect();
        $this->post('/login', ['phone' => '081234567890', 'password' => 'admin123456'])->assertRedirect();

        $this->assertAuthenticatedAs(Profile::where('role', 'admin')->firstOrFail());
        $this->getJson('/api/warga/ringkasan')->assertUnauthorized();
        $this->get('/warga/dashboard')->assertRedirect(route('warga.login'));
        $this->assertNull(session('resident_id'));
    }

    public function test_mismatched_legacy_session_is_rejected(): void
    {
        $admin = Profile::where('role', 'admin')->firstOrFail();
        $resident = Profile::where('role', 'resident')->firstOrFail();

        $this->actingAs($admin)->withSession(['resident_id' => $resident->id])
            ->getJson('/api/warga/riwayat')->assertUnauthorized();
    }

    public function test_registration_requires_an_invitation_and_preserves_invited_tenant_and_identity(): void
    {
        $tenant = Tenant::create(['code' => 'rt99', 'name' => 'RT 99', 'rt' => '99', 'rw' => '99']);
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['tenant_id' => $tenant->id, 'pin_hash' => null]);
        $token = $resident->issueActivationToken();

        $this->postJson('/daftar', ['pin' => '4567', 'pin_confirmation' => '4567'])
            ->assertUnprocessable()->assertJsonValidationErrors('token');
        $this->post('/daftar', [
            'token' => $token, 'pin' => '4567', 'pin_confirmation' => '4567',
            'tenant_id' => Tenant::where('code', 'bs-rt05-rw08')->value('id'),
            'role' => 'admin', 'full_name' => 'Identitas Palsu', 'phone_number' => '081000000000',
        ])->assertRedirect(route('warga.dashboard'));

        $this->assertSame($tenant->id, $resident->fresh()->tenant_id);
        $this->assertSame('resident', $resident->fresh()->role);
        $this->assertSame('Keluarga Budi Santoso', $resident->fresh()->full_name);
        $this->assertDatabaseCount('profiles', 3);
        $this->assertTrue(Hash::check('4567', $resident->fresh()->pin_hash));
        $this->assertNull($resident->fresh()->activation_token_hash);
        $this->assertAuthenticatedAs($resident);
    }

    public function test_expired_and_used_invitations_cannot_activate_an_account(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['pin_hash' => null]);
        $expired = $resident->issueActivationToken();
        $resident->update(['activation_expires_at' => now()->subMinute()]);

        $this->postJson('/daftar', ['token' => $expired, 'pin' => '4567', 'pin_confirmation' => '4567'])
            ->assertUnprocessable()->assertJsonValidationErrors('token');
        $this->assertNull($resident->fresh()->pin_hash);
        $valid = $resident->issueActivationToken();
        $this->assertNotSame($valid, $resident->fresh()->activation_token_hash);
        $this->post('/daftar', ['token' => $valid, 'pin' => '4567', 'pin_confirmation' => '4567'])->assertRedirect();
        $hash = $resident->fresh()->pin_hash;
        $this->postJson('/daftar', ['token' => $valid, 'pin' => '9876', 'pin_confirmation' => '9876'])
            ->assertUnprocessable();
        $this->assertSame($hash, $resident->fresh()->pin_hash);
        $this->assertSame(1, AuditLog::where('action', 'RESIDENT_PIN_ACTIVATED')->count());
    }

    #[TestWith(['/aktivasi-pin'])]
    #[TestWith(['/warga/aktivasi'])]
    #[TestWith(['/cek-saldo'])]
    public function test_public_address_is_never_sufficient_to_activate_an_account(string $path): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['pin_hash' => null]);
        $resident->issueActivationToken();

        $this->post($path, ['phone' => $resident->phone_number, 'house_number' => $resident->house_number, 'pin' => '4567']);

        $this->assertNull($resident->fresh()->pin_hash);
        $this->assertGuest();
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_activation_token_is_bound_to_the_invited_phone(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['pin_hash' => null]);
        $token = $resident->issueActivationToken();

        $this->post('/aktivasi-pin', ['phone' => '081000000000', 'token' => $token, 'pin' => '4567'])
            ->assertSessionHas('error');
        $this->assertNull($resident->fresh()->pin_hash);
        $this->assertNotNull($resident->fresh()->activation_token_hash);
    }

    public function test_admin_can_issue_an_invitation_but_other_tenants_and_officers_cannot(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['pin_hash' => null]);
        $admin = Profile::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin)->post('/admin/warga/'.$resident->id.'/undangan')
            ->assertSessionHas('activation_url');
        $firstHash = $resident->fresh()->activation_token_hash;
        $this->post('/admin/warga/'.$resident->id.'/undangan')->assertSessionHas('activation_url');
        $this->assertNotSame($firstHash, $resident->fresh()->activation_token_hash);
        $this->assertArrayNotHasKey('activation_token_hash', $resident->fresh()->toArray());
        $other = Tenant::create(['code' => 'other', 'name' => 'RT Lain', 'rt' => '88', 'rw' => '88']);
        $resident->update(['tenant_id' => $other->id]);
        $this->postJson('/admin/warga/'.$resident->id.'/undangan')->assertNotFound();
        $this->actingAs(Profile::where('role', 'officer')->firstOrFail())
            ->postJson('/admin/warga/'.$resident->id.'/undangan')->assertForbidden();
    }

    public function test_deposit_retry_returns_original_receipt_without_another_balance_mutation(): void
    {
        $admin = Profile::where('role', 'admin')->firstOrFail();
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $category = WasteCategory::firstOrFail();
        $category->update(['price_per_unit' => 3500]);
        $request = ['request_id' => (string) Str::uuid(), 'resident_id' => $resident->id,
            'items' => [['category_id' => $category->id, 'weight' => 1]]];
        $this->actingAs($admin);

        $first = $this->postJson('/admin/timbang', $request)->assertOk();
        $this->postJson('/admin/timbang', $request)->assertOk()->assertExactJson($first->json());
        $request['request_id'] = (string) Str::uuid();
        $this->postJson('/admin/timbang', $request)->assertOk();
        $request['request_id'] = Transaction::findOrFail($first->json('data.transaction_id'))->request_id;
        $this->postJson('/admin/timbang', $request)->assertOk()->assertJsonPath('data.new_balance', 3500);

        $this->assertSame(7000, (int) $resident->fresh()->balance);
        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseCount('transaction_items', 2);
        $this->assertSame(2, AuditLog::where('action', 'RECORD_DEPOSIT')->count());
    }

    public function test_withdrawal_retry_does_not_pay_out_twice_and_reused_id_cannot_change_amount(): void
    {
        $admin = Profile::where('role', 'admin')->firstOrFail();
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['balance' => 10000]);
        $request = ['request_id' => (string) Str::uuid(), 'resident_id' => $resident->id, 'amount' => 1000];
        $request['authorization_code'] = $this->approveWithdrawal($resident, 1000);
        $request['admin_password'] = 'admin123456';
        $this->actingAs($admin);

        $this->post('/admin/warga/tarik', $request)->assertSessionHas('success');
        $this->post('/admin/warga/tarik', $request)->assertSessionHas('info')->assertSessionMissing('success');
        $request['amount'] = 2000;
        $this->post('/admin/warga/tarik', $request)->assertSessionHas('error');

        $this->assertSame(9000, (int) $resident->fresh()->balance);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame(1, AuditLog::where('action', 'RECORD_WITHDRAWAL')->count());
    }

    public function test_deposit_rejects_missing_request_id_and_extra_precision_without_writing_money(): void
    {
        $admin = Profile::where('role', 'admin')->firstOrFail();
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $category = WasteCategory::firstOrFail();
        $request = ['resident_id' => $resident->id, 'items' => [['category_id' => $category->id, 'weight' => 1]]];
        $this->actingAs($admin)->postJson('/admin/timbang', $request)
            ->assertUnprocessable()->assertJsonValidationErrors('request_id');
        $request['request_id'] = (string) Str::uuid();
        $request['items'][0]['weight'] = 0.014;
        $this->postJson('/admin/timbang', $request)->assertUnprocessable()->assertJsonValidationErrors('items.0.weight');

        $this->assertSame(0, (int) $resident->fresh()->balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_receipt_uses_persisted_prices_and_stays_unchanged_after_new_transactions(): void
    {
        $admin = Profile::where('role', 'admin')->firstOrFail();
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $category = WasteCategory::firstOrFail();
        $category->update(['price_per_unit' => 4000]);
        $this->actingAs($admin);
        $response = $this->postJson('/admin/timbang', ['request_id' => (string) Str::uuid(),
            'resident_id' => $resident->id, 'items' => [['category_id' => $category->id, 'weight' => 2]]])
            ->assertOk()->assertJsonPath('data.receipt.items.0.price', 4000)
            ->assertJsonPath('data.receipt.items.0.subtotal', 8000)->assertJsonPath('data.receipt.newBalance', 8000);
        $id = $response->json('data.transaction_id');
        $original = $response->json('data.receipt');
        app(RecordWithdrawalAction::class)->execute($resident->id, $admin->id, 1000, $this->approveWithdrawal($resident, 1000), 'admin123456');
        $category->update(['price_per_unit' => 9999, 'name' => 'Nama Baru']);
        $resident->update(['full_name' => 'Nama Warga Baru']);

        $this->getJson('/admin/transaksi/'.$id)->assertOk()->assertJsonPath('data', $original);
        $this->assertSame(7000, (int) $resident->fresh()->balance);
    }

    public function test_old_transactions_do_not_invent_a_historical_balance(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $tx = Transaction::create(['resident_id' => $resident->id, 'type' => 'deposit', 'total_amount' => 1000]);

        $this->withSession(['resident_id' => $resident->id])->getJson('/warga/transaksi/'.$tx->id)
            ->assertOk()->assertJsonPath('data.newBalance', null);
    }

    #[TestWith(['=1+1'])]
    #[TestWith(['+1+1'])]
    #[TestWith(['-1+1'])]
    #[TestWith(['@SUM(1)'])]
    #[TestWith(["\t=1+1"])]
    #[TestWith(['  =1+1'])]
    public function test_exported_text_is_not_interpreted_as_a_formula(string $text): void
    {
        $admin = Profile::where('role', 'admin')->firstOrFail();
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['full_name' => $text]);
        Transaction::create(['resident_id' => $resident->id, 'officer_id' => $admin->id,
            'type' => 'deposit', 'total_amount' => 1000, 'notes' => $text]);
        $csv = $this->actingAs($admin)->get('/admin/laporan/export-ledger?period=all')->assertOk()->streamedContent();
        $file = fopen('php://memory', 'r+');
        fwrite($file, $csv);
        rewind($file);
        fgetcsv($file, 0, ',', '"', '');
        $row = fgetcsv($file, 0, ',', '"', '');
        fclose($file);

        $this->assertSame("'".$text, $row[3]);
        $this->assertSame("'".$text, $row[7]);
        $this->assertSame('1000', $row[5]);
    }

    public function test_current_day_month_charts_and_exports_use_jakarta_boundaries(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-30 17:30:00', 'UTC'));
        $admin = Profile::where('role', 'admin')->firstOrFail();
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $category = WasteCategory::firstOrFail();
        foreach ([['2026-11-30 16:59:59', 9000, 'SEBELUM_BATAS', 1], ['2026-11-30 17:00:00', 1000, 'SESUDAH_BATAS', 2]] as [$time, $amount, $notes, $weight]) {
            $tx = Transaction::create(['resident_id' => $resident->id, 'type' => 'deposit', 'total_amount' => $amount, 'notes' => $notes]);
            $tx->forceFill(['created_at' => $time])->save();
            $tx->items()->create(['category_id' => $category->id, 'weight' => $weight, 'subtotal' => $amount, 'price_per_unit_at_transaction' => $amount]);
        }
        $this->get('/')->assertOk()->assertViewHas('report', fn ($report) => $report['totalAmount'] == 1000);
        $this->actingAs($admin)->get('/admin/laporan?period=month')->assertOk()->assertViewHas('totalDeposit', 1000);
        $this->get('/admin/laporan?period=today')->assertOk()->assertViewHas('totalDeposit', 1000);
        $this->get('/admin')->assertOk()->assertViewHas('chartDays', fn ($days) => $days->last()['weight'] == 2);
        $csv = $this->get('/admin/laporan/export-ledger?period=today')->assertOk()->streamedContent();

        $this->assertStringContainsString('SESUDAH_BATAS', $csv);
        $this->assertStringContainsString('2026-12-01 00:00:00', $csv);
        $this->assertStringNotContainsString('SEBELUM_BATAS', $csv);
    }

    #[TestWith(['3months'])]
    #[TestWith(['year'])]
    #[TestWith(['month'])]
    public function test_history_summary_uses_the_same_period_and_search_as_the_list(string $period): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-06 05:00:00', 'UTC'));
        $resident = Profile::where('role', 'resident')->firstOrFail();
        foreach ([['2024-01-01', 9000, 'dicari'], ['2026-11-05', 1000, 'dicari'], ['2026-11-05', 7000, 'lain']] as [$time, $amount, $notes]) {
            $tx = Transaction::create(['resident_id' => $resident->id, 'type' => 'deposit', 'total_amount' => $amount, 'notes' => $notes]);
            $tx->forceFill(['created_at' => $time])->save();
        }

        $this->withSession(['resident_id' => $resident->id])->get('/warga/riwayat?period='.$period.'&q=dicari')
            ->assertOk()->assertViewHas('totalDeposited', 1000)
            ->assertViewHas('transactions', fn ($transactions) => $transactions->total() === 1);
    }

    public function test_export_filters_match_the_ledger_screen(): void
    {
        $admin = Profile::where('role', 'admin')->firstOrFail();
        $resident = Profile::where('role', 'resident')->firstOrFail();
        foreach ([['deposit', 1000, 'SETORAN_LAMA'], ['withdrawal', 500, 'PENARIKAN_BARU']] as [$type, $amount, $notes]) {
            $tx = Transaction::create(['resident_id' => $resident->id, 'type' => $type, 'total_amount' => $amount, 'notes' => $notes]);
            if ($type === 'deposit') {
                $tx->forceFill(['created_at' => now()->subYears(2)])->save();
            }
        }
        $this->actingAs($admin)->get('/admin/laporan?period=today&type=withdrawal')
            ->assertOk()->assertSee('PENARIKAN_BARU')->assertDontSee('SETORAN_LAMA');
        $csv = $this->get('/admin/laporan/export-ledger?period=today&type=withdrawal')->assertOk()->streamedContent();
        $this->assertStringContainsString('PENARIKAN_BARU', $csv);
        $this->assertStringNotContainsString('SETORAN_LAMA', $csv);
    }

    public function test_non_numeric_pin_is_rejected_without_changing_the_old_pin(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $hash = $resident->pin_hash;

        $this->withSession(['resident_id' => $resident->id])->post('/warga/profil/ganti-pin', [
            'current_pin' => '1234', 'new_pin' => 'abcd', 'new_pin_confirmation' => 'abcd',
        ])->assertSessionHasErrors('new_pin');

        $this->assertSame($hash, $resident->fresh()->pin_hash);
    }

    public function test_receipt_safely_preserves_an_apostrophe_in_the_staff_name(): void
    {
        $admin = Profile::where('role', 'admin')->firstOrFail();
        $admin->update(['full_name' => "Pak O'Connor"]);
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $category = WasteCategory::firstOrFail();

        $this->actingAs($admin)->postJson('/admin/timbang', [
            'request_id' => (string) Str::uuid(), 'resident_id' => $resident->id,
            'items' => [['category_id' => $category->id, 'weight' => 1]],
        ])->assertOk()->assertJsonPath('data.receipt.officerName', "Pak O'Connor");
    }

    public function test_audit_timestamp_uses_utc_storage_and_jakarta_display(): void
    {
        $admin = Profile::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin);
        $before = now()->subSeconds(2);
        $log = AuditLog::create([
            'actor_id' => $admin->id, 'tenant_id' => $admin->tenant_id,
            'actor_name' => $admin->full_name, 'action' => 'AUDIT_TIME_CHECK',
            'target_type' => 'auth', 'target_id' => $admin->id,
        ])->fresh();

        $this->assertTrue($log->created_at->between($before, now()->addSeconds(2)));
        $localTimestamp = $log->created_at->timezone(config('app.business_timezone'))->translatedFormat('d M Y, H:i:s');
        $this->get('/admin/audit-log')->assertOk()->assertSee($localTimestamp);
    }

    public function test_commodity_export_uses_the_same_period_as_the_screen(): void
    {
        $admin = Profile::where('role', 'admin')->firstOrFail();
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $category = WasteCategory::firstOrFail();
        foreach ([[9000, now()->subYears(2)], [1000, now()]] as [$amount, $time]) {
            $tx = Transaction::create(['resident_id' => $resident->id, 'type' => 'deposit', 'total_amount' => $amount]);
            $tx->forceFill(['created_at' => $time])->save();
            $tx->items()->create(['category_id' => $category->id, 'weight' => 1, 'subtotal' => $amount, 'price_per_unit_at_transaction' => $amount]);
        }

        $csv = $this->actingAs($admin)->get('/admin/laporan/export-csv?period=today')->assertOk()->streamedContent();
        $this->assertStringContainsString('1000', $csv);
        $this->assertStringNotContainsString('10000', $csv);
        $this->get('/admin/laporan?period=today')->assertOk()->assertViewHas('totalDeposit', 1000);
    }
}
