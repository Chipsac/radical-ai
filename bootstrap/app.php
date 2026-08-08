<?php

use App\Http\Middleware\DemoAutoLogin;
use App\Http\Middleware\EnsureAddOnEnabled;
use App\Http\Middleware\EnsureModuleAccess;
use App\Http\Middleware\EnsureOnboarded;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\StartSession;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', SecurityHeaders::class);
        $middleware->appendToGroup('web', DemoAutoLogin::class);
        $middleware->appendToGroup('web', EnsureOnboarded::class);

        // Cloud Run sits behind Google's front end; trust its forwarding
        // headers so scheme/host detection and rate limiting see real client IPs.
        $middleware->trustProxies(at: '*');

        $middleware->priority([
            StartSession::class,
            DemoAutoLogin::class,
            Authenticate::class,
        ]);

        $middleware->alias([
            'module' => EnsureModuleAccess::class,
            'addon' => EnsureAddOnEnabled::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
