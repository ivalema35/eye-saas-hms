<?php

/**
 * SuperAdminAuth.php
 *
 * PURPOSE: Super Admin routes protect karta hai.
 *          Sirf 'superadmin' guard se authenticated users allow honge.
 *
 * APPLIED IN: routes/web.php (superadmin prefix group)
 * REDIRECT: /superadmin/login
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth('superadmin')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('superadmin.login')
                             ->with('error', 'Please login to access Super Admin panel.');
        }

        return $next($request);
    }
}
