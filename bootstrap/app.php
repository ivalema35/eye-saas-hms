<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Middleware aliases — route me naam se use karo
        $middleware->alias([
            'identify.tenant'      => \App\Http\Middleware\IdentifyTenant::class,
            'set.tenant.scope'     => \App\Http\Middleware\SetTenantScope::class,
            'auth.hospital'        => \App\Http\Middleware\HospitalAuth::class,
            'auth.superadmin'      => \App\Http\Middleware\SuperAdminAuth::class,
            'subscription.active'  => \App\Http\Middleware\CheckSubscriptionActive::class,
            'grace.check'          => \App\Http\Middleware\CheckGracePeriod::class,
            'redirect.inactive'    => \App\Http\Middleware\RedirectIfInactive::class,
            'permission'           => \App\Http\Middleware\CheckPermission::class,
        ]);

        // Razorpay webhook ko CSRF se exempt karo
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
