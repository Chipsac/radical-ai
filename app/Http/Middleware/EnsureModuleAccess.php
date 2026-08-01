<?php

namespace App\Http\Middleware;

use App\Support\Modules;
use Closure;
use Illuminate\Http\Request;

/**
 * Gates a route group behind a product module.
 *
 * Applied as `module:crm`. Enforcement lives here rather than in the views,
 * so hiding a nav link is presentation and this is the actual control — a
 * user who guesses the URL still gets a 403.
 */
class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $module)
    {
        $user = $request->user();

        abort_unless($user !== null, 403);

        if (! $user->hasModule($module)) {
            abort(403, sprintf(
                'You do not have access to %s. Ask an admin or manager to grant it.',
                Modules::label($module)
            ));
        }

        return $next($request);
    }
}
