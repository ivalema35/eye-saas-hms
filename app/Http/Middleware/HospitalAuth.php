<?php

/**
 * HospitalAuth.php
 *
 * PURPOSE: Hospital routes authenticate karna.
 *          UNIFIED — sirf ek guard check karta hai: hospital_user
 *
 * UPDATED: 4 guards → 1 unified guard
 * REDIRECT: /{slug}/login
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HospitalAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // Single guard check — hospital_user
        if (auth('hospital_user')->check()) {
            $user = auth('hospital_user')->user();

            // Double-check: user must belong to current tenant
            $tenant = app()->bound('tenant') ? app('tenant') : null;
            if ($tenant && $user->tenant_id !== $tenant->id) {
                auth('hospital_user')->logout();
                // Fall through to redirect below
            } elseif ($tenant && ! $tenant->hasAccess()) {
                $tenant->markExpiredIfNeeded();
                auth('hospital_user')->logout();

                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Plan expired.'], 403);
                }

                $slug = $request->segment(1) ?? '';

                return redirect()
                    ->route('hospital.login', ['slug' => $slug])
                    ->with('error', $tenant->status === 'pending'
                        ? 'Your hospital registration is waiting for SuperAdmin approval.'
                        : 'Your hospital plan has expired. Please contact the administrator.');
            } else {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $slug = $request->segment(1) ?? '';

        return redirect()
            ->route('hospital.login', ['slug' => $slug])
            ->with('error', 'Please login to continue.');
    }
}
