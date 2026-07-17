<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Jobs\SeedTenantDefaultMasters;
use App\Models\Platform\AuditLog;
use App\Models\Platform\Tenant;
use App\Services\Platform\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlatformHospitalApiController extends Controller
{
    public function __construct(protected TenantService $tenantService) {}

    // GET /api/v1/super/hospitals
    public function index(Request $request): JsonResponse
    {
        $query = Tenant::withTrashed();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('admin_email', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $paginated = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => [
                'hospitals' => collect($paginated->items())->map(fn($t) => $this->formatTenantSummary($t))->values(),
                'meta'      => [
                    'total'        => $paginated->total(),
                    'last_page'    => $paginated->lastPage(),
                    'current_page' => $paginated->currentPage(),
                ],
            ],
        ]);
    }

    // GET /api/v1/super/hospitals/{id}
    public function show(int $id): JsonResponse
    {
        $tenant = Tenant::withTrashed()->with(['subscriptions', 'payments'])->find($id);

        if (! $tenant) {
            return response()->json(['success' => false, 'message' => 'Hospital not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatTenantDetail($tenant),
        ]);
    }

    // POST /api/v1/super/hospitals
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hospital_name' => ['required', 'string', 'min:3', 'max:100'],
            'slug' => [
                'required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9\-]+$/',
                'unique:tenants,slug',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $reserved = ['superadmin', 'admin', 'register', 'login', 'pricing', 'api', 'health', 'webhook'];
                    if (in_array(strtolower((string) $value), $reserved, true)) {
                        $fail('This slug is reserved.');
                    }
                },
            ],
            'admin_name'  => ['required', 'string', 'max:100'],
            'admin_email' => [
                'required', 'email', 'unique:tenants,admin_email',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (DB::table('hospital_users')->where('email', $value)->exists()) {
                        $fail('This email is already registered to a staff account.');
                    }
                },
            ],
            'admin_phone' => [
                'required', 'string', 'regex:/^[0-9]{10}$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (Tenant::where('admin_phone', $value)->exists()) {
                        $fail('This phone is already registered to another hospital.');
                    }
                    if (DB::table('hospital_users')->where('contact', $value)->exists()) {
                        $fail('This phone is already registered to a staff account.');
                    }
                },
            ],
            'password' => ['required', 'string', 'min:8'],
            'city'     => ['nullable', 'string', 'max:100'],
            'state'    => ['nullable', 'string', 'max:100'],
            'plan'     => ['nullable', 'in:monthly,quarterly,yearly'],
        ]);

        // Auto-generate hospital_code — not collected from mobile UI
        $validated['hospital_code'] = $this->generateHospitalCode($validated['hospital_name']);
        $validated['plan']          = $validated['plan'] ?? 'monthly';
        $validated['start_trial']   = '1';

        $tenant = $this->tenantService->createTenant($validated);

        $this->auditLog($request, 'hospital.created.manual', $tenant->id, "Hospital manually created: {$tenant->slug}");

        return response()->json([
            'success' => true,
            'message' => "Hospital '{$tenant->name}' created successfully.",
            'data'    => $this->formatTenantSummary($tenant),
        ], 201);
    }

    // PUT /api/v1/super/hospitals/{id}
    public function update(Request $request, int $id): JsonResponse
    {
        $tenant = Tenant::find($id);
        if (! $tenant) {
            return response()->json(['success' => false, 'message' => 'Hospital not found.'], 404);
        }

        $validated = $request->validate([
            'name'        => ['required', 'string', 'min:3', 'max:100'],
            'slug'        => [
                'required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9\-]+$/',
                'unique:tenants,slug,' . $tenant->id,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $reserved = ['superadmin', 'admin', 'register', 'login', 'pricing', 'api', 'health', 'webhook'];
                    if (in_array(strtolower((string) $value), $reserved, true)) {
                        $fail('This slug is reserved.');
                    }
                },
            ],
            'admin_name'  => ['required', 'string', 'max:100'],
            'admin_phone' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'city'        => ['nullable', 'string', 'max:100'],
            'state'       => ['nullable', 'string', 'max:100'],
        ]);

        $old = $tenant->only(['name', 'slug', 'admin_name', 'admin_phone', 'city', 'state']);
        $tenant->update($validated);

        $this->auditLog($request, 'hospital.updated', $tenant->id, 'Hospital info updated', $old,
            $tenant->fresh()->only(['name', 'slug', 'admin_name', 'admin_phone', 'city', 'state']));

        return response()->json([
            'success' => true,
            'message' => 'Hospital updated successfully.',
            'data'    => $this->formatTenantSummary($tenant->fresh()),
        ]);
    }

    // DELETE /api/v1/super/hospitals/{id}
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenant = Tenant::find($id);
        if (! $tenant) {
            return response()->json(['success' => false, 'message' => 'Hospital not found.'], 404);
        }

        $tenant->delete();
        $this->auditLog($request, 'hospital.archived', $id, 'Hospital archived. Data retained for 30 days.');

        return response()->json(['success' => true, 'message' => "'{$tenant->name}' archived. Data safe for 30 days."]);
    }

    // POST /api/v1/super/hospitals/{id}/activate
    public function activate(Request $request, int $id): JsonResponse
    {
        $tenant = Tenant::find($id);
        if (! $tenant) return response()->json(['success' => false, 'message' => 'Not found.'], 404);

        $this->tenantService->activate($tenant);
        $this->auditLog($request, 'hospital.activated', $id, "Hospital activated: {$tenant->slug}");

        return response()->json(['success' => true, 'message' => "'{$tenant->name}' activated."]);
    }

    // POST /api/v1/super/hospitals/{id}/suspend
    public function suspend(Request $request, int $id): JsonResponse
    {
        $tenant = Tenant::find($id);
        if (! $tenant) return response()->json(['success' => false, 'message' => 'Not found.'], 404);

        $this->tenantService->suspend($tenant);
        $this->auditLog($request, 'hospital.suspended', $id, "Hospital suspended: {$tenant->slug}");

        return response()->json(['success' => true, 'message' => "'{$tenant->name}' suspended."]);
    }

    // POST /api/v1/super/hospitals/{id}/reactivate
    public function reactivate(Request $request, int $id): JsonResponse
    {
        $tenant = Tenant::find($id);
        if (! $tenant) return response()->json(['success' => false, 'message' => 'Not found.'], 404);

        $this->tenantService->activate($tenant);
        $this->auditLog($request, 'hospital.reactivated', $id, "Hospital reactivated: {$tenant->slug}");

        return response()->json(['success' => true, 'message' => "'{$tenant->name}' reactivated."]);
    }

    // POST /api/v1/super/hospitals/{id}/extend
    public function extend(Request $request, int $id): JsonResponse
    {
        $tenant = Tenant::find($id);
        if (! $tenant) return response()->json(['success' => false, 'message' => 'Not found.'], 404);

        $days = $request->validate(['days' => ['required', 'integer', 'min:1', 'max:90']])['days'];
        $this->tenantService->extendGrace($tenant, $days);
        $this->auditLog($request, 'hospital.grace.extended', $id, "Grace extended by {$days} days", [], ['days' => $days]);

        return response()->json(['success' => true, 'message' => "Grace period extended by {$days} days."]);
    }

    // POST /api/v1/super/hospitals/{id}/reseed-masters
    public function reseedMasters(Request $request, int $id): JsonResponse
    {
        $tenant = Tenant::find($id);
        if (! $tenant) return response()->json(['success' => false, 'message' => 'Not found.'], 404);

        SeedTenantDefaultMasters::dispatch($tenant->id);
        $this->auditLog($request, 'hospital.masters.reseeded', $id, 'Default masters re-seeded by platform admin');

        return response()->json(['success' => true, 'message' => "Default masters seeding started for '{$tenant->name}'."]);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function generateHospitalCode(string $name): string
    {
        $alpha = preg_replace('/[^a-zA-Z]/', '', $name);
        $base  = strtoupper(substr($alpha, 0, 3));
        if (strlen($base) < 3) {
            $base = str_pad($base, 3, 'X');
        }

        $code = $base;
        $i    = 1;
        while (Tenant::where('hospital_code', $code)->exists()) {
            $code = substr($base, 0, 2) . $i;
            $i++;
        }

        return $code;
    }

    private function auditLog(Request $request, string $action, int $tenantId, string $description, array $old = [], array $new = []): void
    {
        AuditLog::create([
            'admin_id'    => $request->user()->id,
            'tenant_id'   => $tenantId,
            'action'      => $action,
            'description' => $description,
            'ip_address'  => $request->ip(),
            'old_values'  => ! empty($old) ? $old : null,
            'new_values'  => ! empty($new) ? $new : null,
        ]);
    }

    private function formatTenantSummary(Tenant $t): array
    {
        return [
            'id'            => $t->id,
            'name'          => $t->name,
            'slug'          => $t->slug,
            'hospital_code' => $t->hospital_code,
            'admin_name'    => $t->admin_name,
            'admin_email'   => $t->admin_email,
            'admin_phone'   => $t->admin_phone,
            'city'          => $t->city,
            'state'         => $t->state,
            'status'        => $t->status,
            'trial_ends_at' => $t->trial_ends_at,
            'is_setup_done' => $t->is_setup_done,
            'created_at'    => $t->created_at,
            'deleted_at'    => $t->deleted_at,
        ];
    }

    private function formatTenantDetail(Tenant $t): array
    {
        $summary = $this->formatTenantSummary($t);

        $summary['country']            = $t->country;
        $summary['timezone']           = $t->timezone;
        $summary['setup_completed_at'] = $t->setup_completed_at;
        $summary['subscriptions']      = $t->subscriptions->map(fn($s) => [
            'id'        => $s->id,
            'cycle'     => $s->cycle,
            'price'     => (float) $s->price,
            'status'    => $s->status,
            'starts_at' => $s->starts_at,
            'ends_at'   => $s->ends_at,
        ])->values();
        $summary['payments'] = $t->payments->map(fn($p) => [
            'id'             => $p->id,
            'amount'         => (float) $p->amount,
            'cycle'          => $p->cycle,
            'method'         => $p->method,
            'status'         => $p->status,
            'transaction_id' => $p->transaction_id,
            'paid_at'        => $p->paid_at,
        ])->values();

        return $summary;
    }
}
