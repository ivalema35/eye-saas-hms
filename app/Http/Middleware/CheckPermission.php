<?php

/**
 * CheckPermission.php
 *
 * PURPOSE: Permission-based access control middleware.
 *          Permission key check karta hai — role name NAHI.
 *
 * USAGE IN ROUTES:
 *   Route::get('/patients', [...])->middleware('permission:opd.patient.view');
 *   Route::post('/exam', [...])->middleware('permission:opd.exam.primary');
 *   Route::delete('/patient/{id}', [...])->middleware('permission:opd.patient.delete');
 *
 * BYPASS RULE:
 *   user->role->is_super === true hoga to ALWAYS bypass (Hospital Admin)
 *   SuperAdmin guard bhi bypass karta hai
 */

namespace App\Http\Middleware;

use App\Services\Auth\RolePermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(
        private readonly RolePermissionService $permissionService
    ) {}

    public function handle(Request $request, Closure $next, string $permissionKey): Response
    {
        // Super Admin platform bypass
        if (auth('superadmin')->check()) {
            return $next($request);
        }

        // Hospital User check — session guard first, then Sanctum token guard for API
        $user = auth('hospital_user')->user();

        if (! $user) {
            $apiUser = $request->user();
            if ($apiUser instanceof \App\Models\Hospital\HospitalUser) {
                $user = $apiUser;
                // Bind onto session guard so RolePermissionService resolves correctly
                auth('hospital_user')->setUser($user);
            }
        }

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('hospital.login', ['slug' => $request->segment(1)]);
        }

        // Role eager load karo — lazy load mein BelongsToTenant scope issue ho sakta hai
        $user->loadMissing('role');

        // is_super role = Hospital Admin — bypass ALL permission checks
        if ($user->role && $user->role->is_super) {
            return $next($request);
        }

        $permissionKeys = array_values(array_filter(array_map('trim', explode('|', $permissionKey))));
        $hasPermission = count($permissionKeys) > 1
            ? $this->permissionService->canAny($permissionKeys)
            : $this->permissionService->can($permissionKeys[0] ?? $permissionKey);

        // Check permission via service (Redis cached)
        if (! $hasPermission) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Access denied.',
                    'permission' => implode('|', $permissionKeys),
                ], 403);
            }

            return redirect()
                ->back()
                ->with('error', 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
