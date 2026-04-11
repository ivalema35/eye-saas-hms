<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth('hospital_user')->user();
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
