<?php

namespace App\Providers;

use App\Services\Auth\RolePermissionService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $helpersPath = app_path('Support/helpers.php');

        if (is_file($helpersPath)) {
            require_once $helpersPath;
        }
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

        View::composer('hospital.*', function ($view): void {
            $hospitalSettings = hospital_settings();
            $hospitalName = $hospitalSettings['hospital_name'] ?? (app('tenant')?->name ?? config('app.name'));
            $hospitalFullAddress = $hospitalSettings['hospital_address'] ?? '';
            $hospitalOfficialEmail = $hospitalSettings['hospital_email'] ?? '';
            $hospitalContactNumber = $hospitalSettings['hospital_phone'] ?? '';
            $hospitalLogoPath = $hospitalSettings['hospital_logo'] ?? null;

            $view->with([
                'hospitalSettings' => $hospitalSettings,
                'hospitalName' => $hospitalName,
                'hospitalFullAddress' => $hospitalFullAddress,
                'hospitalOfficialEmail' => $hospitalOfficialEmail,
                'hospitalContactNumber' => $hospitalContactNumber,
                'hospitalLogo' => $hospitalLogoPath,
                'hospitalLogoUrl' => $hospitalLogoPath
                    ? asset('storage/'.$hospitalLogoPath)
                    : asset('images/aeh-logo-white.svg'),
            ]);
        });
    }
}
