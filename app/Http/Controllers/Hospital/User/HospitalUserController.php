<?php

namespace App\Http\Controllers\Hospital\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hospital\User\HospitalUserStoreRequest;
use App\Http\Requests\Hospital\User\HospitalUserUpdateRequest;
use App\Models\Hospital\HospitalUser;
use App\Models\Role\Role;
use App\Services\Auth\RolePermissionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HospitalUserController extends Controller
{
    public function __construct(private readonly RolePermissionService $permissionService)
    {
    }

    public function index(Request $request): View
    {
        $this->authorizeUserManagement();

        $slug = $request->route('slug');
        $roleFilter = $this->normalizeRoleFilter($request->query('role'));

        $users = HospitalUser::with('role')
            ->when($roleFilter, function (Builder $query) use ($roleFilter) {
                $query->whereHas('role', fn(Builder $q) => $q->whereIn('slug', $roleFilter['slugs']));
            })
            ->latest()
            ->get();

        $roles = $this->rolesForUserForms();
        $roleFilterKey = $roleFilter['key'] ?? null;
        $roleFilterLabel = $roleFilter['label'] ?? null;

        return view('hospital.users.index', compact('users', 'slug', 'roles', 'roleFilterKey', 'roleFilterLabel'));
    }

    /**
     * @return array{key: string, label: string, slugs: list<string>}|null
     */
    private function normalizeRoleFilter(?string $role): ?array
    {
        return match ($role) {
            'doctor' => [
                'key' => 'doctor',
                'label' => 'Doctors',
                'slugs' => ['doctor', 'ot_doctor'],
            ],
            'receptionist', 'reception' => [
                'key' => 'receptionist',
                'label' => 'Reception',
                'slugs' => ['receptionist', 'receptionist_opd'],
            ],
            'ot' => [
                'key' => 'ot',
                'label' => 'OT Staff',
                'slugs' => ['ot_assistant', 'accountant', 'ward_management', 'discharge_counter'],
            ],
            default => null,
        };
    }

    /**
     * Roles for Add/Edit user forms, with doctor-field flag for the UI toggle.
     *
     * @return Collection<int, Role>
     */
    private function rolesForUserForms(): Collection
    {
        return Role::query()
            ->whereNull('deleted_at')
            ->where('slug', '!=', 'hospital_admin')
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(function (Role $role) {
                $role->setAttribute('shows_doctor_fields', $this->roleShowsDoctorFields($role));

                return $role;
            });
    }

    private function roleShowsDoctorFields(Role $role): bool
    {
        $slug = strtolower((string) $role->slug);
        $name = strtolower((string) $role->name);

        if (in_array($slug, ['doctor', 'ot_doctor'], true) || str_contains($slug, 'doctor') || str_contains($name, 'doctor')) {
            return true;
        }

        $keys = $role->getGrantedPermissionKeys();

        return in_array('opd.exam.primary', $keys, true)
            || in_array('opd.exam.secondary', $keys, true);
    }

    public function create(): View
    {
        $this->authorizeUserManagement();

        $slug = request()->route('slug');
        $roles = $this->rolesForUserForms();

        return view('hospital.users.create', compact('slug', 'roles'));
    }

    public function store(HospitalUserStoreRequest $request): RedirectResponse
    {
        $this->authorizeUserManagement();

        $slug = $request->route('slug');
        $data = $request->validated();

        $role = Role::query()->findOrFail((int) $data['role_id']);
        $canPerformClinicalExams = $this->roleShowsDoctorFields($role);

        $tenantId = app('tenant')->id;

        $signaturePath = $this->storeUserFile($request, 'signature', $tenantId);
        $profilePhotoPath = $this->storeUserFile($request, 'profile_photo', $tenantId);

        HospitalUser::create([
            'tenant_id' => $tenantId,
            'role_id' => $role->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'contact' => $data['contact'] ?? null,
            'password' => $data['password'],
            'original_password' => $data['password'],
            'status' => $data['status'],
            'doctor_type' => $canPerformClinicalExams ? ($data['doctor_type'] ?? null) : null,
            'doctor_prefix' => $canPerformClinicalExams ? (strtoupper($data['doctor_prefix'] ?? '') ?: null) : null,
            'foc_permission' => $canPerformClinicalExams ? (bool) ($data['foc_permission'] ?? false) : false,
            'registration_no' => $canPerformClinicalExams ? ($data['registration_no'] ?? null) : null,
            'experience_years' => $canPerformClinicalExams ? ($data['experience_years'] ?? null) : null,
            'signature_path' => $canPerformClinicalExams ? $signaturePath : null,
            'profile_photo_path' => $canPerformClinicalExams ? $profilePhotoPath : null,
        ]);

        return redirect()
            ->route('hospital.users.index', array_filter([
                'slug' => $slug,
                'role' => $request->input('role_filter'),
            ]))
            ->with('success', 'User added successfully.');
    }

    private function storeUserFile($request, string $field, int $tenantId, ?string $existingPath = null): ?string
    {
        if (!$request->hasFile($field)) {
            return $existingPath;
        }

        if ($existingPath && Storage::disk('public')->exists($existingPath)) {
            Storage::disk('public')->delete($existingPath);
        }

        return $request->file($field)->store("tenants/{$tenantId}/staff", 'public');
    }

    private function authorizeUserManagement(): void
    {
        abort_unless(
            $this->permissionService->canAny([
                'master.doctors',
                'master.receptions',
                'master.ot_staff',
            ]),
            403,
            'Access denied.'
        );
    }

    public function edit(string $slug, string $id): View
    {
        $this->authorizeUserManagement();

        $user = HospitalUser::query()
            ->where('tenant_id', app('tenant')->id)
            ->findOrFail((int) $id);

        $roles = $this->rolesForUserForms();

        return view('hospital.users.edit', compact('slug', 'user', 'roles'));
    }

    public function update(HospitalUserUpdateRequest $request, string $slug, string $id): RedirectResponse
    {
        $this->authorizeUserManagement();

        $user = HospitalUser::query()
            ->where('tenant_id', app('tenant')->id)
            ->findOrFail((int) $id);

        $data = $request->validated();

        $role = Role::query()->findOrFail((int) $data['role_id']);
        $canPerformClinicalExams = $this->roleShowsDoctorFields($role);

        $tenantId = app('tenant')->id;

        $signaturePath = $this->storeUserFile($request, 'signature', $tenantId, $user->signature_path);
        $profilePhotoPath = $this->storeUserFile($request, 'profile_photo', $tenantId, $user->profile_photo_path);

        $updateData = [
            'role_id' => $role->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'contact' => $data['contact'] ?? null,
            'status' => $data['status'],
            'doctor_type' => $canPerformClinicalExams ? ($data['doctor_type'] ?? null) : null,
            'doctor_prefix' => $canPerformClinicalExams ? (strtoupper($data['doctor_prefix'] ?? '') ?: null) : null,
            'foc_permission' => $canPerformClinicalExams ? (bool) ($data['foc_permission'] ?? false) : false,
            'registration_no' => $canPerformClinicalExams ? ($data['registration_no'] ?? null) : null,
            'experience_years' => $canPerformClinicalExams ? ($data['experience_years'] ?? null) : null,
            'signature_path' => $canPerformClinicalExams ? $signaturePath : null,
            'profile_photo_path' => $canPerformClinicalExams ? $profilePhotoPath : null,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = $data['password'];
            $updateData['original_password'] = $data['password'];
        }

        $user->update($updateData);

        return redirect()
            ->route('hospital.users.index', array_filter([
                'slug' => $slug,
                'role' => $request->input('role_filter'),
            ]))
            ->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, string $slug, string $id): RedirectResponse
    {
        $this->authorizeUserManagement();

        $user = HospitalUser::query()
            ->where('tenant_id', app('tenant')->id)
            ->findOrFail((int) $id);

        abort_if(
            $user->id === auth('hospital_user')->id(),
            403,
            'You cannot delete your own account.'
        );

        $user->delete();

        return redirect()
            ->route('hospital.users.index', array_filter([
                'slug' => $slug,
                'role' => $request->input('role') ?? $request->query('role'),
            ]))
            ->with('success', 'User deleted successfully.');
    }
}
