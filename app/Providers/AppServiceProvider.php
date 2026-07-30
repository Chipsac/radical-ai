<?php

namespace App\Providers;

use App\Support\TenantContext;
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

        // Enterprise password policy, applied everywhere Password::defaults() is used.
        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(12)->letters()->mixedCase()->numbers()->symbols()->uncompromised()
            : Password::min(8));
    }
}
