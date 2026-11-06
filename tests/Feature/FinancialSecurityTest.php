<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\WasteCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class FinancialSecurityTest extends TestCase
{
    public function test_password_reset_revokes_the_officers_existing_session(): void
    {
        $officer = Profile::where('role', 'officer')->firstOrFail();
        $this->post('/login', ['phone' => $officer->phone_number, 'password' => 'petugas123456'])->assertRedirect();
        $oldSession = session()->all();
        $this->actingAs(Profile::where('role', 'admin')->firstOrFail())
            ->post('/admin/petugas/'.$officer->id.'/reset-password', ['new_password' => 'changed-password'])
            ->assertSessionHas('success');
        Auth::forgetGuards();

        $this->withSession($oldSession)->getJson('/admin/warga')->assertUnauthorized();

        $this->assertGuest();
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_pin_reset_revokes_authenticated_and_legacy_resident_sessions(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $this->post('/login', ['phone' => $resident->phone_number, 'pin' => '1234'])->assertRedirect();
        $oldSession = session()->all();
        $this->actingAs(Profile::where('role', 'admin')->firstOrFail())
            ->post('/admin/warga/'.$resident->id.'/reset-pin', ['new_pin' => '5678'])->assertSessionHas('success');
        Auth::forgetGuards();

        $this->withSession($oldSession)->getJson('/api/warga/ringkasan')->assertUnauthorized();
        $this->withSession(['resident_id' => $resident->id])->getJson('/api/warga/ringkasan')->assertUnauthorized();
        $this->post('/login', ['phone' => $resident->phone_number, 'pin' => '5678'])->assertRedirect();
        $this->getJson('/api/warga/ringkasan')->assertOk();
    }

    public function test_public_pin_change_is_audited_and_cancels_old_sessions_and_approval(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $this->approveWithdrawal($resident, 1000);
        $this->post('/login', ['phone' => $resident->phone_number, 'pin' => '1234'])->assertRedirect();
        $oldSession = session()->all();

        $this->post('/cek-saldo/ubah-pin', ['resident_id' => $resident->id, 'current_pin' => '1234', 'new_pin' => '5678'])
            ->assertSessionHas('success');

        $this->assertNull($resident->fresh()->withdrawal_approval);
        $this->assertDatabaseHas('audit_logs', ['action' => 'RESIDENT_CHANGE_PIN', 'target_id' => $resident->id]);
        Auth::forgetGuards();
        $this->withSession($oldSession)->getJson('/api/warga/ringkasan')->assertUnauthorized();
    }

    public function test_resident_authorizes_exact_amount_with_a_hashed_expiring_code(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['balance' => 10000]);
        $this->actingAs($resident)->withSession(['resident_id' => $resident->id]);

        $this->post('/warga/profil/persetujuan-penarikan', ['amount' => 1500, 'current_pin' => '1234'])
            ->assertSessionHas('withdrawal_code')->assertSessionHas('withdrawal_amount', 1500);

        $code = session('withdrawal_code');
        $approval = $resident->fresh()->withdrawal_approval;
        $this->assertSame(12, strlen($code));
        $this->assertSame(hash('sha256', $code), $approval['code_hash']);
        $this->assertSame(1500, $approval['amount']);
        $this->assertArrayNotHasKey('withdrawal_approval', $resident->fresh()->toArray());
        $this->assertStringNotContainsString($code, AuditLog::where('action', 'AUTHORIZE_WITHDRAWAL')->sole()->details);
        $this->get('/warga/profil')->assertOk()->assertSee($code)->assertSee('Rp 1.500');
    }

    #[TestWith([0, '1234', 'amount'])]
    #[TestWith([10001, '1234', 'amount'])]
    #[TestWith([1000, '9999', 'current_pin'])]
    public function test_invalid_approval_does_not_issue_a_code(int $amount, string $pin, string $field): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['balance' => 10000]);
        $this->actingAs($resident)->withSession(['resident_id' => $resident->id]);

        $this->postJson('/warga/profil/persetujuan-penarikan', ['amount' => $amount, 'current_pin' => $pin])
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertNull($resident->fresh()->withdrawal_approval);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_staff_cannot_issue_a_resident_approval(): void
    {
        $this->actingAs(Profile::where('role', 'admin')->firstOrFail());

        $this->postJson('/warga/profil/persetujuan-penarikan', ['amount' => 1000, 'current_pin' => '1234'])
            ->assertUnauthorized();

        $this->assertDatabaseCount('audit_logs', 0);
    }

    #[TestWith(['password'])]
    #[TestWith(['code'])]
    #[TestWith(['amount'])]
    #[TestWith(['expired'])]
    #[TestWith(['missing'])]
    public function test_invalid_withdrawal_proof_leaves_money_and_approval_unchanged(string $failure): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['balance' => 10000]);
        $code = $this->approveWithdrawal($resident, 1000);
        $request = ['request_id' => (string) Str::uuid(), 'resident_id' => $resident->id, 'amount' => 1000,
            'authorization_code' => $code, 'admin_password' => 'admin123456'];
        if ($failure === 'password') {
            $request['admin_password'] = 'wrong-password';
        } elseif ($failure === 'code') {
            $request['authorization_code'] = 'INVALID12345';
        } elseif ($failure === 'amount') {
            $request['amount'] = 1001;
        } elseif ($failure === 'expired') {
            $this->travel(10)->minutes();
        } else {
            $resident->withdrawal_approval = null;
            $resident->save();
        }
        $approval = $resident->fresh()->withdrawal_approval;
        $this->actingAs(Profile::where('role', 'admin')->firstOrFail());

        $this->post('/admin/warga/tarik', $request)->assertSessionHas('error');

        $this->assertSame(10000, (int) $resident->fresh()->balance);
        $this->assertSame($approval, $resident->fresh()->withdrawal_approval);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_withdrawal_consumes_approval_and_persists_receipt_and_cannot_reuse_code(): void
    {
        $this->freezeTime();
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['balance' => 10000]);
        $code = $this->approveWithdrawal($resident, 1000);
        $this->actingAs(Profile::where('role', 'admin')->firstOrFail());
        $request = ['request_id' => (string) Str::uuid(), 'resident_id' => $resident->id, 'amount' => 1000,
            'authorization_code' => $code, 'admin_password' => 'admin123456'];

        $this->post('/admin/warga/tarik', $request)->assertSessionHas('success')->assertSessionHas('withdrawal_receipt');

        $transaction = Transaction::sole();
        $this->assertSame(now()->toIso8601String(), $transaction->resident_approved_at->toIso8601String());
        $this->assertSame($transaction->id, session('withdrawal_receipt.id'));
        $this->assertNull($resident->fresh()->withdrawal_approval);
        $this->get('/admin/warga')->assertOk()->assertSee($transaction->id)->assertSee('Bukti Pencairan Tunai');
        $request['request_id'] = (string) Str::uuid();
        $this->post('/admin/warga/tarik', $request)->assertSessionHas('error');
        $this->assertSame(9000, (int) $resident->fresh()->balance);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame(1, AuditLog::where('action', 'RECORD_WITHDRAWAL')->count());
    }

    public function test_withdrawal_requires_both_resident_code_and_admin_password(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $this->actingAs(Profile::where('role', 'admin')->firstOrFail());

        $this->postJson('/admin/warga/tarik', ['request_id' => (string) Str::uuid(), 'resident_id' => $resident->id, 'amount' => 1000])
            ->assertUnprocessable()->assertJsonValidationErrors(['authorization_code', 'admin_password']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_public_pages_do_not_expose_resident_identity_or_phone_matches(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();

        $this->get('/')->assertOk()->assertDontSee($resident->full_name)->assertDontSee('Papan Peringkat');
        $this->get('/warga?q='.$resident->phone_number)->assertOk()->assertDontSee($resident->full_name);
        $this->actingAs(Profile::where('role', 'admin')->firstOrFail())
            ->get('/warga?q='.$resident->phone_number)->assertOk()->assertDontSee($resident->full_name);
    }

    public function test_used_category_unit_cannot_change_but_unused_category_unit_can(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $category = WasteCategory::firstOrFail();
        $this->actingAs(Profile::where('role', 'admin')->firstOrFail());
        $this->postJson('/admin/timbang', ['request_id' => (string) Str::uuid(), 'resident_id' => $resident->id,
            'items' => [['category_id' => $category->id, 'weight' => 1]]])->assertOk();
        $newUnit = $category->unit === 'kg' ? 'liter' : 'kg';

        $this->putJson('/admin/kategori/'.$category->id, ['name' => $category->name, 'unit' => $newUnit, 'price_per_unit' => $category->price_per_unit])
            ->assertUnprocessable()->assertJsonValidationErrors('unit');

        $this->assertSame($category->unit, $category->fresh()->unit);
        $unused = WasteCategory::where('id', '!=', $category->id)->firstOrFail();
        $this->put('/admin/kategori/'.$unused->id, ['name' => $unused->name, 'unit' => $newUnit, 'price_per_unit' => $unused->price_per_unit])
            ->assertSessionHas('success');
        $this->assertSame($newUnit, $unused->fresh()->unit);
    }

    public function test_same_category_weights_are_combined_before_rounding(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $category = WasteCategory::firstOrFail();
        $category->update(['price_per_unit' => 3450]);
        $this->actingAs(Profile::where('role', 'officer')->firstOrFail());

        $this->postJson('/admin/timbang', ['request_id' => (string) Str::uuid(), 'resident_id' => $resident->id,
            'items' => [['category_id' => $category->id, 'weight' => '0.01'], ['category_id' => $category->id, 'weight' => '0.01']]])
            ->assertOk()->assertJsonPath('data.total_amount', 69)->assertJsonPath('data.receipt.items.0.subtotal', 69);

        $this->assertSame(69, (int) $resident->fresh()->balance);
        $this->assertDatabaseCount('transaction_items', 1);
        $this->assertDatabaseHas('transaction_items', ['weight' => '0.02', 'subtotal' => 69]);
    }

    public function test_disabling_used_category_writes_an_audit_record(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $category = WasteCategory::firstOrFail();
        $this->actingAs(Profile::where('role', 'admin')->firstOrFail());
        $this->postJson('/admin/timbang', ['request_id' => (string) Str::uuid(), 'resident_id' => $resident->id,
            'items' => [['category_id' => $category->id, 'weight' => 1]]])->assertOk();

        $this->delete('/admin/kategori/'.$category->id)->assertSessionHas('success');

        $this->assertFalse($category->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'DISABLE_CATEGORY', 'target_id' => $category->id]);
    }

    public function test_price_update_rolls_back_when_audit_write_fails(): void
    {
        $category = WasteCategory::firstOrFail();
        $oldPrice = $category->price_per_unit;
        $this->actingAs(Profile::where('role', 'admin')->firstOrFail());
        $this->failAuditWrites();

        $this->putJson('/admin/kategori/'.$category->id.'/harga', ['price_per_unit' => 9876])->assertServerError();

        $this->assertSame($oldPrice, $category->fresh()->price_per_unit);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_withdrawal_rolls_back_money_and_keeps_approval_when_audit_write_fails(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['balance' => 10000]);
        $code = $this->approveWithdrawal($resident, 1000);
        $approval = $resident->withdrawal_approval;
        $this->actingAs(Profile::where('role', 'admin')->firstOrFail());
        $this->failAuditWrites();

        $this->postJson('/admin/warga/tarik', ['request_id' => (string) Str::uuid(), 'resident_id' => $resident->id, 'amount' => 1000,
            'authorization_code' => $code, 'admin_password' => 'admin123456'])->assertServerError();

        $this->assertSame(10000, (int) $resident->fresh()->balance);
        $this->assertSame($approval, $resident->fresh()->withdrawal_approval);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_pin_update_rolls_back_credentials_and_session_version_when_audit_write_fails(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $hash = $resident->pin_hash;
        $this->failAuditWrites();

        $this->postJson('/cek-saldo/ubah-pin', ['resident_id' => $resident->id, 'current_pin' => '1234', 'new_pin' => '5678'])->assertServerError();

        $this->assertSame($hash, $resident->fresh()->pin_hash);
        $this->assertNull($resident->fresh()->session_version);
        $this->assertTrue(Hash::check('1234', $resident->fresh()->pin_hash));
    }

    public function test_new_approval_replaces_the_old_code_and_ignores_another_resident_id(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['balance' => 10000]);
        $oldCode = $this->approveWithdrawal($resident, 1000);
        $this->actingAs($resident)->withSession(['resident_id' => $resident->id]);

        $this->post('/warga/profil/persetujuan-penarikan', ['amount' => 1000, 'current_pin' => '1234', 'resident_id' => (string) Str::uuid()])
            ->assertSessionHas('withdrawal_code');

        $newCode = session('withdrawal_code');
        $this->assertNotSame($oldCode, $newCode);
        $this->assertSame(hash('sha256', $newCode), $resident->fresh()->withdrawal_approval['code_hash']);
        $this->actingAs(Profile::where('role', 'admin')->firstOrFail())->withSession(['session_version' => null]);
        $this->post('/admin/warga/tarik', ['request_id' => (string) Str::uuid(), 'resident_id' => $resident->id, 'amount' => 1000,
            'authorization_code' => $oldCode, 'admin_password' => 'admin123456'])->assertSessionHas('error');
        $this->assertSame(10000, (int) $resident->fresh()->balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    #[TestWith(['officer'])]
    #[TestWith(['resident'])]
    public function test_only_admin_can_record_a_withdrawal(string $role): void
    {
        $actor = Profile::where('role', $role)->firstOrFail();
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['balance' => 10000]);
        $this->actingAs($actor);

        $this->postJson('/admin/warga/tarik', ['request_id' => (string) Str::uuid(), 'resident_id' => $resident->id,
            'amount' => 1000, 'authorization_code' => $this->approveWithdrawal($resident, 1000), 'admin_password' => 'admin123456'])
            ->assertForbidden();

        $this->assertSame(10000, (int) $resident->fresh()->balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_resident_directory_is_limited_to_the_logged_in_tenant(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $otherTenant = Tenant::create(['code' => 'private-rt', 'name' => 'RT Lain', 'rt' => '99', 'rw' => '99']);
        $otherResident = $resident->replicate();
        $otherResident->tenant_id = $otherTenant->id;
        $otherResident->phone_number = '081900001999';
        $otherResident->full_name = 'Identitas RT Lain';
        $otherResident->save();

        $this->actingAs($resident)->get('/warga')->assertOk()->assertSee($resident->full_name)->assertDontSee($otherResident->full_name);
    }

    public function test_failed_approval_audit_does_not_leave_a_usable_code(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $resident->update(['balance' => 10000]);
        $this->actingAs($resident)->withSession(['resident_id' => $resident->id]);
        $this->failAuditWrites();

        $this->postJson('/warga/profil/persetujuan-penarikan', ['amount' => 1000, 'current_pin' => '1234'])->assertServerError();

        $this->assertNull($resident->fresh()->withdrawal_approval);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_grouped_weight_overflow_does_not_change_money(): void
    {
        $resident = Profile::where('role', 'resident')->firstOrFail();
        $category = WasteCategory::firstOrFail();
        $this->actingAs(Profile::where('role', 'officer')->firstOrFail());

        $this->postJson('/admin/timbang', ['request_id' => (string) Str::uuid(), 'resident_id' => $resident->id,
            'items' => [['category_id' => $category->id, 'weight' => '99999999.99'], ['category_id' => $category->id, 'weight' => '0.01']]])
            ->assertUnprocessable();

        $this->assertSame(0, (int) $resident->fresh()->balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    #[TestWith(['081234567890', 'admin123456'])]
    #[TestWith(['081999888777', '1234'])]
    public function test_login_does_not_create_a_session_when_audit_write_fails(string $phone, string $secret): void
    {
        $this->failAuditWrites();

        $this->postJson('/login', ['phone' => $phone, 'password' => $secret])->assertServerError();

        $this->assertGuest();
        $this->assertNull(session('resident_id'));
        $this->assertDatabaseCount('audit_logs', 0);
    }

    private function failAuditWrites(): void
    {
        $dispatcher = AuditLog::getEventDispatcher();
        AuditLog::setEventDispatcher(clone $dispatcher);
        AuditLog::creating(function (AuditLog $log): void {
            throw new \RuntimeException('Simulated audit persistence failure.');
        });
        $this->beforeApplicationDestroyed(function () use ($dispatcher): void {
            AuditLog::setEventDispatcher($dispatcher);
        });
    }
}
