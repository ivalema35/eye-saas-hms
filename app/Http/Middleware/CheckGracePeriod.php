<?php

/**
 * CheckGracePeriod.php
 *
 * PURPOSE: Grace period me chal rahe tenants ko warning dikhata hai.
 *          Kaam karne deta hai lekin banner show karta hai.
 *
 * APPLIED IN: routes/hospital.php (authenticated routes, after CheckSubscriptionActive)
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckGracePeriod
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        if ($tenant && $tenant->status === 'grace') {
            // Session me grace warning flag set karo
            // Layout me ye check hoga aur banner dikhayega
            session(['show_grace_warning' => true]);
        }

        return $next($request);
    }
}
