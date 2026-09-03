<?php

/**
 * CheckSubscriptionActive.php
 *
 * PURPOSE: Hospital routes par subscription validity check karta hai.
 *          Status 'inactive' ya 'suspended' wale tenants ko block karta hai.
 *          Trial, active, grace — sab allowed hain.
 *
 * APPLIED IN: routes/hospital.php (authenticated routes)
 * REDIRECT: /{slug}/subscription-expired (Phase 3 me banegi)
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        // Tenant identify nahi hua — IdentifyTenant middleware pehle lagana chahiye tha
        if (!$tenant) {
            abort(404);
        }

        if ($tenant->status === 'suspended' || ! $tenant->hasAccess()) {
            $tenant->refresh();
            $tenant->markExpiredIfNeeded();
            $tenant->refresh();

            $message = match ($tenant->status) {
                'pending' => 'Your hospital registration is waiting for Eyenosis approval.',
                'suspended' => 'This hospital is suspended. Please contact support.',
                default => 'Your hospital plan has expired. Please contact the administrator.',
            };

            if ($request->expectsJson()) {
                return response()->json(['error' => $message], 403);
            }

            return redirect()
                ->route('hospital.login', ['slug' => $tenant->slug])
                ->with('error', $message);
        }

        return $next($request);
    }
}
