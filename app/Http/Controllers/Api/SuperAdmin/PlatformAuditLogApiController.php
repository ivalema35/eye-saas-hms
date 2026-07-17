<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Platform\AuditLog;
use App\Models\Platform\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformAuditLogApiController extends Controller
{
    // GET /api/v1/super/audit-logs
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with([
            'admin:id,name',
            'tenant:id,name,slug',
        ])->latest();

        if ($action = $request->query('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        if ($tenantId = $request->query('tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->paginate(25);

        // Tenant list for the filter dropdown
        $tenants = Tenant::orderBy('name')->get(['id', 'name', 'slug']);

        return response()->json([
            'success' => true,
            'data'    => [
                'logs'    => $logs->map(fn($l) => $this->formatLog($l))->values(),
                'tenants' => $tenants->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'slug' => $t->slug])->values(),
                'meta'    => [
                    'total'        => $logs->total(),
                    'last_page'    => $logs->lastPage(),
                    'current_page' => $logs->currentPage(),
                ],
            ],
        ]);
    }

    private function formatLog(AuditLog $l): array
    {
        return [
            'id'          => $l->id,
            'action'      => $l->action,
            'description' => $l->description,
            'tenant_id'   => $l->tenant_id,
            'tenant_name' => $l->tenant?->name,
            'tenant_slug' => $l->tenant?->slug,
            'admin_id'    => $l->admin_id,
            'admin_name'  => $l->admin?->name,
            'ip_address'  => $l->ip_address,
            'old_values'  => $l->old_values,
            'new_values'  => $l->new_values,
            'created_at'  => $l->created_at?->toIso8601String(),
        ];
    }
}
