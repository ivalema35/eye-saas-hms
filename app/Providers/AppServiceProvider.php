<?php

namespace App\Providers;

use App\Services\Auth\RolePermissionService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
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
        // Proxy/CDN ke pichhal deploy hone par bhi route()/url() https generate kare,
        // taaki https page se ajax/fetch calls http:// URL par mixed-content block na ho
        if (!$this->app->environment('local')) {
            URL::forceScheme('https');
        }

        // Custom Blade directive: @haspermission('opd.patient.view') ... @endhaspermission
        Blade::if('haspermission', function (string $permissionKey): bool {
            return auth('hospital_user')->user()?->role?->is_super
                || app(RolePermissionService::class)->can($permissionKey);
        });

        Paginator::defaultView('vendor.pagination.hms');

        View::composer('hospital.*', function ($view): void {
            $hospitalSettings = hospital_settings();
            $hospitalName = $hospitalSettings['hospital_name'] ?? (app('tenant')?->name ?? config('app.name'));
            $hospitalFullAddress = $hospitalSettings['hospital_address'] ?? '';
            $hospitalOfficialEmail = $hospitalSettings['hospital_email'] ?? (app('tenant')?->admin_email ?? '');
            $hospitalContactNumber = $hospitalSettings['hospital_phone'] ?? (app('tenant')?->admin_phone ?? '');
            $hospitalLogoPath = $hospitalSettings['hospital_logo'] ?? null;
            $hospitalLogoNobgPath = $hospitalSettings['hospital_logo_nobg'] ?? null;
            $logoSidebarStyle = $hospitalSettings['logo_sidebar_style'] ?? 'white';

            // Sidebar uses bg-removed version when white style + nobg exists — that
            // variant is only legible on the sidebar's dark background, so it must
            // stay out of hospitalLogoUrl (used everywhere else: login, print
            // documents, topbar) or the logo goes invisible on white backgrounds.
            $sidebarLogoPath = ($logoSidebarStyle === 'white' && $hospitalLogoNobgPath)
                ? $hospitalLogoNobgPath
                : $hospitalLogoPath;

            $view->with([
                'hospitalSettings' => $hospitalSettings,
                'hospitalName' => $hospitalName,
                'hospitalFullAddress' => $hospitalFullAddress,
                'hospitalOfficialEmail' => $hospitalOfficialEmail,
                'hospitalContactNumber' => $hospitalContactNumber,
                'hospitalLogo' => $hospitalLogoPath,
                'hospitalLogoUrl' => $hospitalLogoPath
                    ? asset('storage/' . $hospitalLogoPath)
                    : platform_logo_url(),
                'hospitalSidebarLogoUrl' => $sidebarLogoPath
                    ? asset('storage/' . $sidebarLogoPath)
                    : platform_logo_url(),
                'hospitalLogoSidebarStyle' => $logoSidebarStyle,
            ]);
        });
    }
}
