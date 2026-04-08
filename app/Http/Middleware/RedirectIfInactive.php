<?php

/**
 * RedirectIfInactive.php
 *
 * PURPOSE: Inactive/suspended tenant ko login ke baad redirect karta hai.
 *          Status check: agar tenant inactive hay to login nahi karne deta.
 *
 * APPLIED IN: Login routes (before authentication attempt)
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfInactive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        if ($tenant && $tenant->status === 'suspended') {
            return redirect()->back()
                             ->with('error', 'This hospital account has been suspended. Please contact support.');
        }

        return $next($request);
    }
}
