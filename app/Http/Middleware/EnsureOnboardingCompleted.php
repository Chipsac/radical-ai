<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasCompletedOnboarding()) {
            if (! $request->is('onboarding*') && ! $request->is('logout')) {
                return redirect()->route('onboarding.index');
            }
        }

        return $next($request);
    }
}
