<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', \App\Http\Middleware\SecurityHeaders::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\DemoAutoLogin::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureOnboarded::class);

        // Cloud Run sits behind Google's front end; trust its forwarding
        // headers so scheme/host detection and rate limiting see real client IPs.
        $middleware->trustProxies(at: '*');

        $middleware->priority([
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\DemoAutoLogin::class,
            \Illuminate\Auth\Middleware\Authenticate::class,
        ]);

        $middleware->alias([
            'module' => \App\Http\Middleware\EnsureModuleAccess::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
