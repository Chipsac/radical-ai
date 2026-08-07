<?php

namespace App\Providers;

use App\Support\TenantContext;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        // Cloud Run terminates TLS at the load balancer and forwards HTTP,
        // so links must be forced to https in production or they break.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Every route-model-bound id is an integer key. Without this, a request
        // like /hr/does-not-exist reaches the database and Postgres rejects the
        // comparison outright — "invalid input syntax for type bigint" — which
        // surfaces as a 500 where a 404 belongs. SQLite coerces silently, so it
        // only misbehaves in production. Constraining the segment means the
        // route simply does not match and Laravel 404s before touching the
        // database, which is both correct and one less way to probe for errors.
        foreach (['task', 'deal', 'user', 'team', 'employee', 'payslip', 'leaveRequest'] as $key) {
            Route::pattern($key, '[0-9]+');
        }

        // Enterprise password policy, applied everywhere Password::defaults() is used.
        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(12)->letters()->mixedCase()->numbers()->symbols()->uncompromised()
            : Password::min(8));
    }
}
