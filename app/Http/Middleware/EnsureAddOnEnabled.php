<?php

namespace App\Http\Middleware;

use App\Support\Modules;
use Closure;
use Illuminate\Http\Request;

/**
 * Gates a route group behind a paid add-on, applied as `addon:assistant`.
 *
 * Deliberately separate from EnsureModuleAccess. That one answers "should this
 * person see this part of the product"; this one answers "has this customer
 * bought it". Collapsing them into one check would mean either that granting a
 * user the module quietly bypasses billing, or that buying the add-on hands it
 * to everyone in the workspace. Both gates apply, in that order — a user
 * without the module gets the ordinary 403 whether or not the org has paid, so
 * the error message never leaks the customer's billing state to staff who
 * aren't entitled to see it.
 */
class EnsureAddOnEnabled
{
    public function handle(Request $request, Closure $next, string $addOn)
    {
        $user = $request->user();

        abort_unless($user !== null, 403);

        $organization = $user->organization;

        abort_unless($organization !== null, 403);

        if ($addOn === Modules::ASSISTANT) {
            abort_unless($organization->hasAssistant(), 402, sprintf(
                'The %s add-on is not enabled for this workspace. An admin can enable it from Settings.',
                Modules::label($addOn)
            ));

            // Cost ceiling. Refuse rather than degrade: silently switching to a
            // cheaper model or truncating history would make the assistant
            // quietly worse with no explanation, which is harder to diagnose
            // than an explicit stop.
            abort_if($organization->assistantCapReached(), 429,
                'This workspace has reached its assistant usage limit for this month. '.
                'It resets on the 1st, or an admin can raise the cap in Settings.'
            );
        }

        return $next($request);
    }
}
