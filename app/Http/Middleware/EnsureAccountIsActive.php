<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out anyone whose account an Admin has deactivated, on their very next request.
 * (Deleted accounts need no check: the soft-delete scope means the session's user is no
 * longer found, so they're already signed out.)
 */
class EnsureAccountIsActive
{
    public const MESSAGE = 'Your account has been deactivated. Please contact your administrator.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => self::MESSAGE], 401)
                : redirect()->route('login')->with('error', self::MESSAGE);
        }

        return $next($request);
    }
}
