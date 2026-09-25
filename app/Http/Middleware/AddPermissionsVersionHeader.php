<?php

namespace App\Http\Middleware;

use App\Models\Hospital\HospitalUser;
use App\Services\Auth\PermissionsVersion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Must sit right after auth:sanctum so it wraps every inner response,
 * including 403s from the `permission:` middleware.
 */
class AddPermissionsVersionHeader
{
    public const HEADER = 'X-Permissions-Version';

    public function __construct(private readonly PermissionsVersion $versions) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if ($user instanceof HospitalUser && ! $response->headers->has(self::HEADER)) {
            try {
                $response->headers->set(self::HEADER, $this->versions->for($user));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $response;
    }
}
