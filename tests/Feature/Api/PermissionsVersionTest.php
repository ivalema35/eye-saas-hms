<?php

namespace Tests\Feature\Api;

use App\Models\Hospital\HospitalUser;
use App\Models\Role\Role;
use App\Services\Auth\PermissionsVersion;
use App\Services\Auth\RolePermissionService;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PermissionsVersionTest extends TestCase
{
    public function test_version_is_stable_for_same_state_and_ignores_key_order(): void
    {
        $user = $this->user();

        $a = $this->versions(['patient_view', 'report_view'])->for($user);
        $b = $this->versions(['report_view', 'patient_view'])->for($user);

        $this->assertSame($a, $b);
        $this->assertSame(12, strlen($a));
    }

    public function test_version_changes_when_permissions_role_super_or_status_change(): void
    {
        $base = $this->versions(['patient_view'])->for($this->user());

        $this->assertNotSame($base, $this->versions(['patient_view', 'patient_edit'])->for($this->user()));
        $this->assertNotSame($base, $this->versions(['patient_view'])->for($this->user(roleId: 9)));
        $this->assertNotSame($base, $this->versions(['patient_view'])->for($this->user(isSuper: true)));
        $this->assertNotSame($base, $this->versions(['patient_view'])->for($this->user(status: 'inactive')));
    }

    public function test_modules_follow_granted_keys_and_super_gets_all(): void
    {
        $modules = collect($this->versions(['patient_view'])->modules($this->user()))->pluck('key');

        $this->assertContains('opd', $modules);
        $this->assertNotContains('ot', $modules);

        $superModules = collect($this->versions([])->modules($this->user(isSuper: true)))->pluck('key');
        $this->assertContains('opd', $superModules);
        $this->assertContains('ot', $superModules);
        $this->assertContains('master', $superModules);
    }

    public function test_scope_describes_the_hospital_user(): void
    {
        $scope = $this->versions([])->scope($this->user());

        $this->assertSame('hospital', $scope['type']);
        $this->assertSame(7, $scope['user_id']);
        $this->assertSame(3, $scope['role_id']);
        $this->assertSame('receptionist', $scope['role_slug']);
        $this->assertFalse($scope['is_super']);
    }

    public function test_authenticated_api_group_sends_version_header_and_exposes_modules(): void
    {
        foreach (['api.v1.hospital.auth.me', 'api.v1.hospital.modules', 'api.v1.hospital.masters.detail.index'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} was not registered.");
            $this->assertContains('permissions.version', $route->gatherMiddleware());
        }

        $modules = Route::getRoutes()->getByName('api.v1.hospital.modules');
        foreach ($modules->gatherMiddleware() as $middleware) {
            $this->assertStringStartsNotWith('permission:', $middleware);
        }
    }

    /**
     * @param  list<string>  $keys
     */
    private function versions(array $keys): PermissionsVersion
    {
        $permissions = $this->createMock(RolePermissionService::class);
        $permissions->method('getGrantedPermissionKeys')->willReturn($keys);

        return new PermissionsVersion($permissions);
    }

    private function user(int $roleId = 3, bool $isSuper = false, string $status = 'active'): HospitalUser
    {
        $role = (new Role)->forceFill(['id' => $roleId, 'slug' => 'receptionist', 'is_super' => $isSuper]);

        $user = (new HospitalUser)->forceFill([
            'id' => 7,
            'tenant_id' => 1,
            'role_id' => $roleId,
            'status' => $status,
        ]);
        $user->setRelation('role', $role);

        return $user;
    }
}
