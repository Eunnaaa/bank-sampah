<?php

namespace App\Providers;

use App\Models\Profile;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        RateLimiter::for('credentials', function (Request $request): array {
            $account = Profile::normalizePhoneNumber((string) $request->input('phone', $request->input('phone_number', '')));
            if ($request->routeIs('balance.change-pin')) {
                $account = (string) $request->input('resident_id', '');
            } elseif (in_array($request->route()->getActionMethod(), ['changePin', 'authorizeWithdrawal'], true)) {
                $account = (string) ($request->user()?->id ?? $request->session()->get('resident_id', ''));
            }

            return [
                Limit::perMinute(30)->by('ip:'.$request->ip()),
                Limit::perMinute(5)->by('account:'.$account),
            ];
        });

        RateLimiter::for('withdrawals', fn (Request $request): Limit => Limit::perMinute(10)->by('withdrawal:'.$request->user()->id));

        Gate::define('admin-only', function (Profile $user) {
            return $user->role === 'admin';
        });
    }
}
