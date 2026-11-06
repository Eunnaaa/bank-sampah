<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StaffAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login')->with('error', 'Silakan masuk dengan akun pengurus atau petugas.');
        }

        $user = Auth::user();

        if ($user->role === 'resident') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akses ditolak. Area khusus pengurus dan petugas.'], 403);
            }

            return redirect()->route('warga.dashboard')->with('error', 'Akses ditolak. Halaman tersebut khusus untuk pengurus RT dan petugas.');
        }

        if (! in_array($user->role, ['admin', 'officer'])) {
            abort(403, 'Akses tidak diizinkan.');
        }

        abort_unless($user->tenant_id, 403, 'Akun belum terhubung dengan RT.');

        return $next($request);
    }
}
