<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Role\Role;
use App\Support\EmailRules;
use App\Support\PhoneRules;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserApiController extends Controller
{
    // ── GET /config/users ──────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $query = HospitalUser::with('role')
            ->where(function ($q) {
                // Include users with no role, or users with non-admin roles
                $q->whereDoesntHave('role')
                  ->orWhereHas('role', fn ($rq) => $rq->where('slug', '!=', 'hospital_admin'));
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('contact', 'like', "%{$search}%")
            );
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->integer('role_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('name')->paginate(25);
        $viewerIsAdmin = (bool) $request->user()->role?->is_super;

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users->map(fn ($u) => $this->formatUser($u, viewerIsAdmin: $viewerIsAdmin)),
                'meta'  => [
                    'current_page' => $users->currentPage(),
                    'last_page'    => $users->lastPage(),
                    'per_page'     => $users->perPage(),
                    'total'        => $users->total(),
                ],
            ],
        ]);
    }

    // ── GET /config/users/form-data ────────────────────────────────────────────

    public function formData(): JsonResponse
    {
        $roles = Role::where('slug', '!=', 'hospital_admin')
            ->orderBy('name')
            ->with('grantedPermissions')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'roles' => $roles->map(fn ($r) => [
                    'id'             => $r->id,
                    'name'           => $r->name,
                    'slug'           => $r->slug,
                    'color'          => $r->color ?? '#1B4F72',
                    'is_super'       => (bool) $r->is_super,
                    'is_doctor_role' => $this->isDoctorRole($r),
                ]),
                'doctor_types' => [
                    ['value' => 'primary',   'label' => 'Primary (Ophthalmologist)'],
                    ['value' => 'secondary', 'label' => 'Secondary (Optometrist)'],
                ],
            ],
        ]);
    }

    // ── GET /config/users/{id} ─────────────────────────────────────────────────

    public function show(Request $request, string $slug, string $id): JsonResponse
    {
        $user = HospitalUser::with('role.grantedPermissions')->findOrFail($id);
        $viewerIsAdmin = (bool) $request->user()->role?->is_super;

        return response()->json([
            'success' => true,
            'data'    => $this->formatUser($user, full: true, viewerIsAdmin: $viewerIsAdmin),
        ]);
    }

    // ── POST /config/users ─────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->role?->is_super) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => [...EmailRules::required(), 'unique:hospital_users,email'],
            'contact'          => PhoneRules::nullable(),
            'role_id'          => ['required', 'integer', 'exists:roles,id'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'status'           => ['required', 'in:active,inactive'],
            // Doctor fields
            'doctor_type'      => ['nullable', 'in:primary,secondary'],
            'doctor_prefix'    => ['nullable', 'string', 'min:2', 'max:5', 'alpha'],
            'registration_no'  => ['nullable', 'string', 'max:50'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'foc_permission'   => ['nullable', 'boolean'],
            // Files
            'signature'        => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:20'],
            'profile_photo'    => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:20'],
        ], array_merge(EmailRules::messages('email'), PhoneRules::messages('contact')));

        $tenantId = (int) config('app.tenant_id');

        $signaturePath   = $this->storeFile($request, 'signature', $tenantId);
        $profilePhotoPath = $this->storeFile($request, 'profile_photo', $tenantId);

        $user = HospitalUser::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'contact'           => $validated['contact'] ?? null,
            'role_id'           => $validated['role_id'],
            'password'          => $validated['password'],
            'original_password' => $validated['password'],
            'status'            => $validated['status'],
            'doctor_type'       => $validated['doctor_type'] ?? null,
            'doctor_prefix'     => isset($validated['doctor_prefix'])
                                    ? strtoupper($validated['doctor_prefix'])
                                    : null,
            'registration_no'   => $validated['registration_no'] ?? null,
            'experience_years'  => $validated['experience_years'] ?? null,
            'foc_permission'    => $validated['foc_permission'] ?? false,
            'signature_path'    => $signaturePath,
            'profile_photo_path'=> $profilePhotoPath,
        ]);

        $user->load('role.grantedPermissions');

        return response()->json([
            'success' => true,
            'data'    => $this->formatUser($user, full: true, viewerIsAdmin: true),
            'message' => 'User created successfully.',
        ], 201);
    }

    // ── POST /config/users/{id} (update, accepts multipart) ───────────────────

    public function update(Request $request, string $slug, string $id): JsonResponse
    {
        if (! $request->user()->role?->is_super) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $user = HospitalUser::findOrFail($id);

        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => [...EmailRules::required(), Rule::unique('hospital_users', 'email')->ignore($user->id)],
            'contact'          => PhoneRules::nullable(),
            'role_id'          => ['required', 'integer', 'exists:roles,id'],
            'password'         => ['nullable', 'string', 'min:8', 'confirmed'],
            'status'           => ['required', 'in:active,inactive'],
            // Doctor fields
            'doctor_type'      => ['nullable', 'in:primary,secondary'],
            'doctor_prefix'    => ['nullable', 'string', 'min:2', 'max:5', 'alpha'],
            'registration_no'  => ['nullable', 'string', 'max:50'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'foc_permission'   => ['nullable', 'boolean'],
            // Files
            'signature'        => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:20'],
            'profile_photo'    => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:20'],
            // Explicit clear flags (mobile sends "1" to remove existing file)
            'clear_signature'    => ['nullable', 'boolean'],
            'clear_profile_photo'=> ['nullable', 'boolean'],
            'expected_updated_at'=> ['nullable', 'date'],
        ], array_merge(EmailRules::messages('email'), PhoneRules::messages('contact')));

        // Optimistic concurrency check — reject a save if the record changed
        // elsewhere (web/another platform) since the client last fetched it.
        // See ACCESS_CONTROL_AND_DATA_SYNC_PLAN.md Phase 1 Task 1.2.
        if (! empty($validated['expected_updated_at'])
            && ! $user->updated_at->equalTo(Carbon::parse($validated['expected_updated_at']))) {
            return response()->json([
                'error' => 'This record was changed elsewhere. Please reload before saving.',
                'code'  => 'stale_record',
            ], 409);
        }

        $tenantId = (int) config('app.tenant_id');

        // Handle signature
        if ($request->hasFile('signature')) {
            $this->deleteFile($user->signature_path);
            $signaturePath = $this->storeFile($request, 'signature', $tenantId);
        } elseif ($request->boolean('clear_signature')) {
            $this->deleteFile($user->signature_path);
            $signaturePath = null;
        } else {
            $signaturePath = $user->signature_path;
        }

        // Handle profile photo
        if ($request->hasFile('profile_photo')) {
            $this->deleteFile($user->profile_photo_path);
            $profilePhotoPath = $this->storeFile($request, 'profile_photo', $tenantId);
        } elseif ($request->boolean('clear_profile_photo')) {
            $this->deleteFile($user->profile_photo_path);
            $profilePhotoPath = null;
        } else {
            $profilePhotoPath = $user->profile_photo_path;
        }

        $user->update([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'contact'           => $validated['contact'] ?? null,
            'role_id'           => $validated['role_id'],
            'status'            => $validated['status'],
            'doctor_type'       => $validated['doctor_type'] ?? null,
            'doctor_prefix'     => isset($validated['doctor_prefix'])
                                    ? strtoupper($validated['doctor_prefix'])
                                    : null,
            'registration_no'   => $validated['registration_no'] ?? null,
            'experience_years'  => $validated['experience_years'] ?? null,
            'foc_permission'    => $validated['foc_permission'] ?? false,
            'signature_path'    => $signaturePath,
            'profile_photo_path'=> $profilePhotoPath,
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
            $user->original_password = $validated['password'];
            $user->save();
        }

        $user->load('role.grantedPermissions');

        return response()->json([
            'success' => true,
            'data'    => $this->formatUser($user, full: true, viewerIsAdmin: true),
            'message' => 'User updated successfully.',
        ]);
    }

    // ── DELETE /config/users/{id} ──────────────────────────────────────────────

    public function destroy(Request $request, string $slug, string $id): JsonResponse
    {
        $user = HospitalUser::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        $user->delete();

        return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
    }

    // ── PATCH /config/users/{id}/toggle-status ─────────────────────────────────

    public function toggleStatus(Request $request, string $slug, string $id): JsonResponse
    {
        if (! $request->user()->role?->is_super) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $user = HospitalUser::findOrFail($id);
        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        return response()->json([
            'success' => true,
            'data'    => ['status' => $user->status],
            'message' => 'Status updated.',
        ]);
    }

    // ── Private Helpers ────────────────────────────────────────────────────────

    private function formatUser(HospitalUser $user, bool $full = false, bool $viewerIsAdmin = false): array
    {
        $base = [
            'id'       => $user->id,
            'name'     => $user->name,
            'email'    => $user->email,
            'contact'  => $user->contact,
            'status'   => $user->status,
            'role'     => $user->role ? [
                'id'             => $user->role->id,
                'name'           => $user->role->name,
                'slug'           => $user->role->slug,
                'color'          => $user->role->color ?? '#1B4F72',
                'is_super'       => (bool) $user->role->is_super,
                'is_doctor_role' => $this->isDoctorRole($user->role),
            ] : null,
            'foc_permission'  => (bool) $user->foc_permission,
            'last_login_at'   => $user->last_login_at?->toISOString(),
            // Round-tripped by the app as `expected_updated_at` on the next
            // edit — see ACCESS_CONTROL_AND_DATA_SYNC_PLAN.md Phase 5.
            'updated_at'      => $user->updated_at?->toISOString(),
        ];

        // Plain-text password, shown only to Hospital Admins — mirrors
        // web's $showUserPasswords gate exactly. See
        // USER_PASSWORD_VISIBILITY_PARITY_PLAN.md.
        if ($viewerIsAdmin) {
            $base['original_password'] = $user->original_password;
        }

        if ($full) {
            $base['doctor_type']       = $user->doctor_type;
            $base['doctor_prefix']     = $user->doctor_prefix;
            $base['registration_no']   = $user->registration_no;
            $base['experience_years']  = $user->experience_years;
            $base['signature_url']     = $user->signature_path
                ? Storage::disk('public')->url($user->signature_path)
                : null;
            $base['profile_photo_url'] = $user->profile_photo_path
                ? Storage::disk('public')->url($user->profile_photo_path)
                : null;
        }

        return $base;
    }

    private function isDoctorRole(Role $role): bool
    {
        // Role is considered a "doctor role" if it has clinical exam permissions
        $keys = $role->relationLoaded('grantedPermissions')
            ? $role->grantedPermissions->pluck('action')->toArray()
            : $role->getGrantedPermissionKeys();

        return in_array('opd.exam.primary', $keys) || in_array('opd.exam.secondary', $keys);
    }

    private function storeFile(Request $request, string $field, int $tenantId): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        $file     = $request->file($field);
        $filename = $field . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        return $file->storeAs("tenants/{$tenantId}/staff", $filename, 'public');
    }

    private function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
