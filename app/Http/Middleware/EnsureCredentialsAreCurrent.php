<?php

namespace App\Http\Middleware;

use App\Models\Profile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCredentialsAreCurrent
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $profileId = Auth::id() ?? $request->session()->get('resident_id');
        if ($profileId) {
            $profile = Profile::find($profileId);
            if (! $profile || $profile->session_version !== $request->session()->get('session_version')) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Kredensial telah berubah. Silakan masuk kembali.'], 401);
                }

                return redirect()->route('login')->with('error', 'Kredensial telah berubah. Silakan masuk kembali.');
            }
        }

        return $next($request);
    }
}
