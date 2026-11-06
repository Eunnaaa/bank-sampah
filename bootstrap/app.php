<?php

use App\Http\Middleware\EnsureCredentialsAreCurrent;
use App\Http\Middleware\ResidentAuthMiddleware;
use App\Http\Middleware\StaffAuthMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [EnsureCredentialsAreCurrent::class]);
        $middleware->alias([
            'resident.auth' => ResidentAuthMiddleware::class,
            'staff.auth' => StaffAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['pin', 'pin_confirmation', 'current_pin', 'new_pin', 'new_pin_confirmation', 'token', 'authorization_code', 'admin_password', 'new_password']);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
