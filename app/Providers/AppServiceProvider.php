<?php

namespace App\Providers;

use App\Services\Auth\RolePermissionService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Custom Blade directive: @haspermission('opd.patient.view') ... @endhaspermission
        Blade::if('haspermission', function (string $permissionKey): bool {
            return auth('hospital_user')->user()?->role?->is_super
                || app(RolePermissionService::class)->can($permissionKey);
        });
    }
}
