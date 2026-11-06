<?php

namespace App\Http\Middleware;

use App\Models\Profile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResidentAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $residentId = $request->session()->get('resident_id');

        if (auth()->check() && (auth()->user()->role !== 'resident' || auth()->id() !== $residentId)) {
            $request->session()->forget('resident_id');
            $residentId = null;
        }

        if (! $residentId) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated resident session.'], 401);
            }

            return redirect()->route('warga.login')->with('error', 'Silakan masuk terlebih dahulu untuk mengakses dashboard warga.');
        }

        $resident = Profile::where('id', $residentId)
            ->where('role', 'resident')
            ->first();

        if (! $resident || ! $resident->tenant_id) {
            $request->session()->forget('resident_id');

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Akun nasabah tidak valid.'], 401);
            }

            return redirect()->route('warga.login')->with('error', 'Sesi nasabah telah kedaluwarsa. Silakan masuk kembali.');
        }

        $request->attributes->set('resident', $resident);
        view()->share('currentResident', $resident);

        return $next($request);
    }
}
