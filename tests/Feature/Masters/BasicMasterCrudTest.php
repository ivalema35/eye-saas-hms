<?php

namespace Tests\Feature\Masters;

use App\Models\Hospital;
use App\Models\Hospital\CaseType;
use App\Models\Hospital\HospitalUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasicMasterCrudTest extends TestCase
{
    use RefreshDatabase;

    private Hospital $hospital;

    private HospitalUser $user;

    private string $slug;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a hospital
        $this->hospital = Hospital::factory()->create();
        $this->slug = $this->hospital->slug;

        // Create a hospital user
        $this->user = HospitalUser::factory()->create([
            'hospital_id' => $this->hospital->id,
        ]);
    }

    public function test_can_view_basic_masters_index(): void
    {
        $response = $this->actingAs($this->user, 'hospital_user')
            ->get("/hospitals/{$this->slug}/masters/basic/cases");

        $response->assertStatus(200);
        $response->assertViewIs('hospital.masters.dynamic_index');
    }

    public function test_can_store_new_case_master(): void
    {
        $response = $this->actingAs($this->user, 'hospital_user')
            ->post("/hospitals/{$this->slug}/masters/basic/cases", [
                'case_type' => 'Emergency Case',
                'case_fee' => '5000',
            ]);

        $this->assertDatabaseHas('tbl_cases', [
            'tenant_id' => $this->hospital->id,
            'case_type' => 'Emergency Case',
            'case_fee' => '5000',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_can_update_case_master(): void
    {
        $case = CaseType::factory()->create([
            'tenant_id' => $this->hospital->id,
            'case_type' => 'Old Name',
            'case_fee' => '1000',
        ]);

        $response = $this->actingAs($this->user, 'hospital_user')
            ->put("/hospitals/{$this->slug}/masters/basic/cases/{$case->id}", [
                'case_type' => 'Updated Name',
                'case_fee' => '2000',
            ]);

        $this->assertDatabaseHas('tbl_cases', [
            'id' => $case->id,
            'case_type' => 'Updated Name',
            'case_fee' => '2000',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_can_delete_case_master(): void
    {
        $case = CaseType::factory()->create([
            'tenant_id' => $this->hospital->id,
        ]);

        $response = $this->actingAs($this->user, 'hospital_user')
            ->delete("/hospitals/{$this->slug}/masters/basic/cases/{$case->id}");

        $this->assertSoftDeleted('tbl_cases', ['id' => $case->id]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_validates_required_fields_on_store(): void
    {
        $response = $this->actingAs($this->user, 'hospital_user')
            ->post("/hospitals/{$this->slug}/masters/basic/cases", [
                'case_type' => '', // Empty
                'case_fee' => '', // Empty
            ]);

        $response->assertSessionHasErrors(['case_type', 'case_fee']);
    }

    public function test_store_validates_max_length(): void
    {
        $response = $this->actingAs($this->user, 'hospital_user')
            ->post("/hospitals/{$this->slug}/masters/basic/cases", [
                'case_type' => str_repeat('a', 256), // Over 255 chars
                'case_fee' => str_repeat('5', 256),
            ]);

        $response->assertSessionHasErrors(['case_type', 'case_fee']);
    }
}
