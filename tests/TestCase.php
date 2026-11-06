<?php

namespace Tests;

use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function approveWithdrawal(Profile $resident, int $amount): string
    {
        $code = 'APPROVAL1234';
        $resident->withdrawal_approval = [
            'code_hash' => hash('sha256', $code), 'amount' => $amount,
            'approved_at' => now()->toIso8601String(), 'expires_at' => now()->addMinutes(10)->toIso8601String(),
        ];
        $resident->save();

        return $code;
    }

    protected function beforeRefreshingDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($database !== 'bank_sampah_testing' && ! ($connection === 'sqlite' && $database === ':memory:')) {
            throw new \RuntimeException('Tests require an isolated bank_sampah_testing database.');
        }
    }
}
