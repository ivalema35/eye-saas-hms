<?php

use App\Http\Middleware\CheckGracePeriod;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckSubscriptionActive;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\HospitalAuth;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\RedirectIfInactive;
use App\Http\Middleware\SetTenantScope;
use App\Http\Middleware\SuperAdminAuth;
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
            'identify.tenant' => IdentifyTenant::class,
            'set.tenant.scope' => SetTenantScope::class,
            'auth.hospital' => HospitalAuth::class,
            'auth.superadmin' => SuperAdminAuth::class,
            'subscription.active' => CheckSubscriptionActive::class,
            'grace.check' => CheckGracePeriod::class,
            'redirect.inactive' => RedirectIfInactive::class,
            'permission' => CheckPermission::class,
            'role' => CheckRole::class,
            'platform.admin' => EnsurePlatformAdmin::class,
        ]);

        // Razorpay webhook ko CSRF se exempt karo
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
