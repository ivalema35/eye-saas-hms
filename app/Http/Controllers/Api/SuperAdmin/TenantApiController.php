<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Platform\Tenant;
use Illuminate\Http\JsonResponse;

/**
 * Tenant Management API Controller (SuperAdmin)
 *
 * SuperAdmin-only API for tenant listing/management.
 */
class TenantApiController extends Controller
{
    public function index(): JsonResponse
    {
        $tenants = Tenant::latest()->paginate(25);

        return response()->json(['success' => true, 'data' => $tenants]);
    }

    public function show(int $id): JsonResponse
    {
        $tenant = Tenant::with('subscriptions')->findOrFail($id);

        return response()->json(['success' => true, 'data' => $tenant]);
    }
}
