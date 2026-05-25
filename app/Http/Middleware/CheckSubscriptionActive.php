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
        if (! $tenant) {
            abort(404);
        }

        $blockedStatuses = ['inactive', 'suspended'];

        if (in_array($tenant->status, $blockedStatuses)) {
            // Ajax request ke liye JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Subscription inactive. Please renew to continue.',
                ], 403);
            }

            // Grace period me hain — warning dikhao lekin allow karo
            // Inactive/suspended — block karo
            return redirect()
                ->route('hospital.login', ['slug' => $tenant->slug])
                ->with('error', 'Your subscription has expired. Please contact support.');
        }

        return $next($request);
    }
}
