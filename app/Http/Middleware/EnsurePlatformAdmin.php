<?php

namespace App\Http\Middleware;

use App\Models\Platform\PlatformAdmin;
use Closure;
use Illuminate\Http\Request;

class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->user() instanceof PlatformAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}
