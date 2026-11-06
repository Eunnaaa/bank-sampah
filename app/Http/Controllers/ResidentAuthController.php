<?php

namespace App\Http\Controllers;

use App\Actions\ActivateResidentAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResidentAuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        return app(AuthController::class)->showLogin($request);
    }

    public function login(Request $request): View|RedirectResponse
    {
        return app(AuthController::class)->login($request);
    }

    public function activatePin(Request $request, ActivateResidentAction $action): RedirectResponse
    {
        return app(AuthController::class)->activatePin($request, $action);
    }

    public function logout(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->forget('resident_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('warga.login')->with('info', 'Anda telah berhasil keluar dari sesi nasabah.');
    }
}
