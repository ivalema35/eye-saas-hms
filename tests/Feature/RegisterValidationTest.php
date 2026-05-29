<?php

namespace Tests\Feature;

use App\Models\Hospital\HospitalUser;
use App\Models\Platform\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_rejects_duplicate_admin_email_against_existing_tenant(): void
    {
        Tenant::create([
            'name' => 'Existing Hospital',
            'slug' => 'existing-hospital',
            'admin_name' => 'Existing Admin',
            'admin_email' => 'existing@hospital.com',
            'admin_phone' => '9876543210',
            'status' => 'trial',
            'is_setup_done' => false,
        ]);

        $response = $this->post(route('register.store'), $this->validPayload([
            'admin_email' => 'existing@hospital.com',
        ]));

        $response->assertSessionHasErrors(['admin_email']);
    }

    public function test_registration_rejects_duplicate_admin_email_against_existing_staff(): void
    {
        $tenant = Tenant::create([
            'name' => 'Existing Hospital',
            'slug' => 'existing-hospital',
            'admin_name' => 'Existing Admin',
            'admin_email' => 'owner@hospital.com',
            'admin_phone' => '9876543210',
            'status' => 'trial',
            'is_setup_done' => false,
        ]);

        HospitalUser::create([
            'tenant_id' => $tenant->id,
            'name' => 'Staff User',
            'email' => 'staff@hospital.com',
            'password' => 'password',
            'contact' => '9123456789',
            'status' => 'active',
        ]);

        $response = $this->post(route('register.store'), $this->validPayload([
            'admin_email' => 'staff@hospital.com',
        ]));

        $response->assertSessionHasErrors(['admin_email']);
    }

    public function test_registration_rejects_duplicate_admin_phone_against_existing_tenant(): void
    {
        Tenant::create([
            'name' => 'Existing Hospital',
            'slug' => 'existing-hospital',
            'admin_name' => 'Existing Admin',
            'admin_email' => 'existing@hospital.com',
            'admin_phone' => '9876543210',
            'status' => 'trial',
            'is_setup_done' => false,
        ]);

        $response = $this->post(route('register.store'), $this->validPayload([
            'admin_phone' => '9876543210',
        ]));

        $response->assertSessionHasErrors(['admin_phone']);
    }

    public function test_registration_rejects_duplicate_admin_phone_against_existing_staff(): void
    {
        $tenant = Tenant::create([
            'name' => 'Existing Hospital',
            'slug' => 'existing-hospital',
            'admin_name' => 'Existing Admin',
            'admin_email' => 'existing@hospital.com',
            'admin_phone' => '9876543210',
            'status' => 'trial',
            'is_setup_done' => false,
        ]);

        HospitalUser::create([
            'tenant_id' => $tenant->id,
            'name' => 'Staff User',
            'email' => 'staff@hospital.com',
            'password' => 'password',
            'contact' => '9123456789',
            'status' => 'active',
        ]);

        $response = $this->post(route('register.store'), $this->validPayload([
            'admin_phone' => '9123456789',
        ]));

        $response->assertSessionHasErrors(['admin_phone']);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'hospital_name' => 'Vision Eye Centre',
            'slug' => 'vision-eye-centre',
            'admin_name' => 'Dr. John Smith',
            'admin_email' => 'admin@vision.com',
            'admin_phone' => '9998887776',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'plan' => 'monthly',
            'start_trial' => '1',
        ], $overrides);
    }
}
