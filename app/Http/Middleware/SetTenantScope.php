<?php

/**
 * SetTenantScope.php
 *
 * PURPOSE: IdentifyTenant ke baad tenant_id ko config me ensure karta hai.
 *          BelongsToTenant trait ke liye global tenant context set karta hai.
 *
 * NOTE: Ye middleware IdentifyTenant KE BAAD apply hona chahiye.
 *       Routes me order: ['identify.tenant', 'set.tenant.scope']
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class SetTenantScope
{
    public function handle(Request $request, Closure $next): Response
    {
        // IdentifyTenant ne pehle se tenant set kar diya hoga
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        if ($tenant) {
            Config::set('app.tenant_id', $tenant->id);

            $paginationLimit = DB::table('hospital_settings')
                ->where('tenant_id', $tenant->id)
                ->where('key', 'pagination_limit')
                ->value('value');

            Config::set(
                'app.pagination_limit',
                in_array((int) $paginationLimit, [10, 25, 50, 100], true)
                    ? (int) $paginationLimit
                    : (int) config('app.pagination_limit', 25)
            );
        }

        return $next($request);
    }
}
