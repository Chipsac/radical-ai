<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Local-environment convenience: `?as=admin|manager|employee` logs the
 * request in as the matching seeded demo user. Used for scripted
 * screenshots and quick demos; never active outside APP_ENV=local.
 */
class DemoAutoLogin
{
    public function handle(Request $request, Closure $next)
    {
        $role = $request->query('as');

        if ($role && app()->environment('local') && ! Auth::check()
            && in_array($role, ['admin', 'manager', 'employee'])) {
            $user = User::where('email', $role.'@acme.test')->first();
            if ($user) {
                Auth::login($user);
            }
        }

        return $next($request);
    }
}
