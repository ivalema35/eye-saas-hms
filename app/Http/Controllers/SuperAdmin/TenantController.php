<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Platform\AuditLog;
use App\Models\Platform\Tenant;
use App\Services\Platform\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Tenant Management Controller (SuperAdmin)
 *
 * CRUD + activate/suspend/extend operations for hospital tenants.
 * Accessible at: /superadmin/hospitals
 */
class TenantController extends Controller
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    public function index(): View
    {
        $query = Tenant::withTrashed();

        // Search filter
        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('admin_email', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $tenants = $query->latest()->paginate(20)->appends(request()->query());

        return view('superadmin.tenants.index', compact('tenants'));
    }

    /**
     * create() — Manual hospital creation form.
     */
    public function create(): View
    {
        return view('superadmin.tenants.create');
    }

    /**
     * store() — SuperAdmin manually creates a hospital tenant.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hospital_name' => ['required', 'string', 'min:3', 'max:100'],
            'slug' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9\-]+$/',
                'unique:tenants,slug',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $reserved = ['superadmin', 'admin', 'register', 'login', 'pricing', 'api', 'health', 'webhook'];
                    if (in_array(strtolower((string) $value), $reserved, true)) {
                        $fail('This slug is reserved.');
                    }
                },
            ],
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_email' => [
                'required',
                'email',
                'unique:tenants,admin_email',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $existsInUsers = DB::table('hospital_users')
                        ->where('email', $value)
                        ->exists();
                    if ($existsInUsers) {
                        $fail('This email address is already registered to a staff account on the platform.');
                    }
                },
            ],
            'admin_phone' => [
                'required',
                'string',
                'regex:/^[0-9]{10}$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $existsInTenants = Tenant::where('admin_phone', $value)->exists();
                    if ($existsInTenants) {
                        $fail('This phone number is already registered to another hospital account.');
                    }

                    $existsInUsers = DB::table('hospital_users')
                        ->where('contact', $value)
                        ->exists();
                    if ($existsInUsers) {
                        $fail('This phone number is already registered to a staff account on the platform.');
                    }
                },
            ],
            'password' => ['required', 'string', 'min:8'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'plan' => ['nullable', 'in:monthly,quarterly,yearly'],
            'start_trial' => ['nullable', 'in:1'],
        ]);

        $validated['plan'] = $validated['plan'] ?? 'monthly';
        $validated['start_trial'] = '1';

        $tenant = $this->tenantService->createTenant($validated);

        $this->auditLog(
            'hospital.created.manual',
            $tenant->id,
            "Hospital manually created by SuperAdmin: {$tenant->slug}"
        );

        return redirect()
            ->route('superadmin.hospitals.show', $tenant)
            ->with('success', "Hospital '{$tenant->name}' created! Login URL: ".url($tenant->slug.'/login'));
    }

    public function show(Tenant $tenant): View
    {
        $tenant->load('subscriptions', 'payments');

        return view('superadmin.tenants.show', compact('tenant'));
    }

    /**
     * edit() — Edit hospital details form.
     */
    public function edit(Tenant $tenant): View
    {
        return view('superadmin.tenants.edit', compact('tenant'));
    }

    /**
     * update() — Update hospital details.
     */
    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'slug' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9\-]+$/',
                'unique:tenants,slug,'.$tenant->id,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $reserved = ['superadmin', 'admin', 'register', 'login', 'pricing', 'api', 'health', 'webhook'];
                    if (in_array(strtolower((string) $value), $reserved, true)) {
                        $fail('This slug is reserved.');
                    }
                },
            ],
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_phone' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
        ]);

        $oldValues = $tenant->only(['name', 'slug', 'admin_name', 'admin_phone', 'city', 'state']);
        $tenant->update($validated);

        $this->auditLog(
            'hospital.updated',
            $tenant->id,
            'Hospital info updated',
            $oldValues,
            $tenant->fresh()->only(['name', 'slug', 'admin_name', 'admin_phone', 'city', 'state'])
        );

        return back()->with('success', 'Hospital updated successfully.');
    }

    /**
     * Activate a suspended/inactive tenant
     */
    public function activate(Tenant $tenant): RedirectResponse
    {
        $this->tenantService->activate($tenant);
        $this->auditLog('hospital.activated', $tenant->id, "Hospital activated by SuperAdmin: {$tenant->slug}");

        return back()->with('success', "Hospital '{$tenant->name}' activated successfully.");
    }

    /**
     * Suspend an active tenant
     */
    public function suspend(Tenant $tenant): RedirectResponse
    {
        $this->tenantService->suspend($tenant);
        $this->auditLog('hospital.suspended', $tenant->id, "Hospital suspended by SuperAdmin: {$tenant->slug}");

        return back()->with('success', "Hospital '{$tenant->name}' suspended.");
    }

    /**
     * Extend subscription grace period
     */
    public function extend(Tenant $tenant): RedirectResponse
    {
        $days = request()->validate(['days' => ['required', 'integer', 'min:1', 'max:90']])['days'];
        $this->tenantService->extendGrace($tenant, $days);
        $this->auditLog(
            'hospital.grace.extended',
            $tenant->id,
            "Grace period extended by {$days} days",
            [],
            ['days' => $days]
        );

        return back()->with('success', "Grace period extended by {$days} days.");
    }

    /**
     * reactivate() — Reactivate suspended tenant.
     */
    public function reactivate(Tenant $tenant): RedirectResponse
    {
        $this->tenantService->activate($tenant);
        $this->auditLog('hospital.reactivated', $tenant->id, 'Hospital reactivated by SuperAdmin');

        return back()->with('success', "'{$tenant->name}' reactivated.");
    }

    /**
     * destroy() — Archive (soft delete) tenant.
     */
    public function destroy(Tenant $tenant): RedirectResponse
    {
        $tenant->delete();

        $this->auditLog('hospital.archived', $tenant->id, 'Hospital soft-deleted. Data retained for 30 days.');

        return redirect()
            ->route('superadmin.hospitals.index')
            ->with('success', "'{$tenant->name}' archived. Data safe for 30 days.");
    }

    /**
     * auditLog() — Record action in audit_logs.
     */
    private function auditLog(
        string $action,
        ?int $tenantId,
        string $description,
        array $oldValues = [],
        array $newValues = []
    ): void {
        AuditLog::create([
            'admin_id' => auth('superadmin')->id(),
            'tenant_id' => $tenantId,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'old_values' => ! empty($oldValues) ? $oldValues : null,
            'new_values' => ! empty($newValues) ? $newValues : null,
        ]);
    }
}
