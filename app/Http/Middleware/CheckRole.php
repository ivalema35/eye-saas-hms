<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Session guard first (web), then Sanctum token guard for API — same
        // fallback as CheckPermission, needed now that this middleware also
        // runs on API routes (see ROLES_PERMISSIONS_PARITY_AUDIT.md). Without
        // this, every API request was rejected regardless of actual role,
        // since auth('hospital_user') is never populated by session for a
        // token-authenticated request.
        $user = auth('hospital_user')->user();
        if (! $user) {
            $apiUser = $request->user();
            if ($apiUser instanceof \App\Models\Hospital\HospitalUser) {
                $user = $apiUser;
                auth('hospital_user')->setUser($user);
            }
        }
        abort_if(! $user, 403, 'Unauthorized role access.');

        $currentRole = (string) ($user->role?->slug ?? '');
        $normalizedAllowedRoles = collect($roles)
            ->map(function (string $role): string {
                $role = strtolower(trim($role));

                return match ($role) {
                    'admin' => 'hospital_admin',
                    default => $role,
                };
            })
            ->all();

        abort_unless(in_array($currentRole, $normalizedAllowedRoles, true), 403, 'Role not allowed.');

        return $next($request);
    }
}
