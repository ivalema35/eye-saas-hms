<?php

namespace App\Http\Middleware;

use App\Models\Hospital\HospitalUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveHospitalApiUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof HospitalUser || ! $user->isActive() || $user->trashed()) {
            if ($user instanceof HospitalUser) {
                $user->tokens()->delete();
            }

            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact the hospital administrator.',
            ], 401);
        }

        return $next($request);
    }
}
