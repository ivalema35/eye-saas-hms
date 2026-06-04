<?php

namespace App\Http\Controllers\Hospital\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hospital\User\HospitalUserStoreRequest;
use App\Http\Requests\Hospital\User\HospitalUserUpdateRequest;
use App\Models\Hospital\HospitalUser;
use App\Models\Role\Role;
use App\Services\Auth\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class HospitalUserController extends Controller
{
    public function __construct(private readonly RolePermissionService $permissionService) {}

    public function index(): View
    {
        $this->authorizeUserManagement();

        $slug = request()->route('slug');
        $users = HospitalUser::with('role')->latest()->paginate((int) config('app.pagination_limit', 25));
        $roles = Role::query()
            ->whereNull('deleted_at')
            ->where('slug', '!=', 'hospital_admin')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('hospital.users.index', compact('users', 'slug', 'roles'));
    }

    public function create(): View
    {
        $this->authorizeUserManagement();

        $slug = request()->route('slug');
        $roles = Role::query()
            ->whereNull('deleted_at')
            ->where('slug', '!=', 'hospital_admin')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('hospital.users.create', compact('slug', 'roles'));
    }

    public function store(HospitalUserStoreRequest $request): RedirectResponse
    {
        $this->authorizeUserManagement();

        $slug = $request->route('slug');
        $data = $request->validated();

        $role = Role::query()->findOrFail((int) $data['role_id']);
        $rolePermissionKeys = $role->getGrantedPermissionKeys();
        $canPerformClinicalExams = in_array('opd.exam.primary', $rolePermissionKeys, true)
            || in_array('opd.exam.secondary', $rolePermissionKeys, true);

        HospitalUser::create([
            'tenant_id' => app('tenant')->id,
            'role_id' => $role->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'contact' => $data['contact'] ?? null,
            'password' => $data['password'],
            'status' => $data['status'],
            'doctor_type'   => $canPerformClinicalExams ? ($data['doctor_type'] ?? null) : null,
            'doctor_prefix' => $canPerformClinicalExams ? (strtoupper($data['doctor_prefix'] ?? '') ?: null) : null,
            'foc_permission' => $canPerformClinicalExams ? (bool) ($data['foc_permission'] ?? false) : false,
        ]);

        return redirect()
            ->route('hospital.users.index', ['slug' => $slug])
            ->with('success', 'User added successfully.');
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

        $roles = Role::query()
            ->whereNull('deleted_at')
            ->where('slug', '!=', 'hospital_admin')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

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
        $rolePermissionKeys = $role->getGrantedPermissionKeys();
        $canPerformClinicalExams = in_array('opd.exam.primary', $rolePermissionKeys, true)
            || in_array('opd.exam.secondary', $rolePermissionKeys, true);

        $updateData = [
            'role_id' => $role->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'contact' => $data['contact'] ?? null,
            'status' => $data['status'],
            'doctor_type'   => $canPerformClinicalExams ? ($data['doctor_type'] ?? null) : null,
            'doctor_prefix' => $canPerformClinicalExams ? (strtoupper($data['doctor_prefix'] ?? '') ?: null) : null,
            'foc_permission' => $canPerformClinicalExams ? (bool) ($data['foc_permission'] ?? false) : false,
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        return redirect()
            ->route('hospital.users.index', ['slug' => $slug])
            ->with('success', 'User updated successfully.');
    }

    public function destroy(string $slug, string $id): RedirectResponse
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
            ->route('hospital.users.index', ['slug' => $slug])
            ->with('success', 'User deleted successfully.');
    }
}
