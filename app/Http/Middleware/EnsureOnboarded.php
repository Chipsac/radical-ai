<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Funnels a freshly signed-up organisation through the setup wizard before
 * it can reach the app. Deliberately permissive about which routes are
 * allowed through, so the user can still log out or get help mid-setup.
 */
class EnsureOnboarded
{
    private const ALLOWED = [
        'onboarding.*',
        'logout',
        'profile.*',
        'verification.*',
        'help.*',
        'two-factor.*',
        'invitations.*',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $org = $user?->organization;

        if (! $org || $org->hasCompletedOnboarding()) {
            return $next($request);
        }

        foreach (self::ALLOWED as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        // Don't redirect background/AJAX calls — they'd get confusing HTML back.
        if ($request->expectsJson()) {
            return $next($request);
        }

        return redirect()->route('onboarding.show');
    }
}
