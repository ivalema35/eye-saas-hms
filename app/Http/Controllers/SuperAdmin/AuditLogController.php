<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Platform\AuditLog;
use App\Models\Platform\Tenant;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        $query = AuditLog::with(['admin', 'tenant'])->latest();

        if ($action = request('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        if ($tenantId = request('tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        if ($from = request('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = request('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->paginate(25)->appends(request()->query());
        $tenants = Tenant::orderBy('name')->get(['id', 'name', 'slug']);

        return view('superadmin.audit-logs.index', compact('logs', 'tenants'));
    }
}
