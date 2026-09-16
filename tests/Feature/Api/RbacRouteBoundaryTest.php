<?php

namespace Tests\Feature\Api;

use App\Services\Auth\PermissionMatrix;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RbacRouteBoundaryTest extends TestCase
{
    public function test_eye_exam_detail_routes_use_per_type_verb_boundaries(): void
    {
        $view = PermissionMatrix::eyeExamDetailVerb('view');
        $add = PermissionMatrix::eyeExamDetailVerb('add');
        $edit = PermissionMatrix::eyeExamDetailVerb('edit');
        $delete = PermissionMatrix::eyeExamDetailVerb('delete');

        $this->assertStringContainsString('eye_exam_chief_complaints_view', $view);
        $this->assertStringContainsString('eye_exam_fr_view', $view);
        $this->assertRouteHasMiddleware('api.v1.hospital.masters.detail.index', "permission:{$view}|exam_primary|exam_secondary");
        $this->assertRouteHasMiddleware('api.v1.hospital.masters.detail.store', "permission:{$add}");
        $this->assertRouteHasMiddleware('api.v1.hospital.masters.detail.update', "permission:{$edit}");
        $this->assertRouteHasMiddleware('api.v1.hospital.masters.detail.destroy', "permission:{$delete}");
        $this->assertRouteHasMiddleware('api.v1.hospital.masters.detail.toggle-favourite', "permission:{$edit}");
    }

    public function test_report_routes_match_web_or_permissions(): void
    {
        $this->assertRouteHasMiddleware('api.v1.hospital.reports.index', 'permission:report_view|opd_reports_view');
        $this->assertRouteHasMiddleware('api.v1.hospital.reports.export.excel', 'permission:report_export|opd_reports_export');
        $this->assertRouteHasMiddleware('api.v1.hospital.reports.ot.index', 'permission:report_view|ot_reports_view');
        $this->assertRouteHasMiddleware('api.v1.hospital.reports.ot.export', 'permission:report_export|ot_reports_export');
    }

    public function test_ot_billing_documents_have_granular_boundaries(): void
    {
        $this->assertRouteHasMiddleware('api.v1.hospital.ot.invoice.generate', 'permission:ot_discharge_patient|ot_billing_manage');
        $this->assertRouteHasMiddleware('api.v1.hospital.ot.invoice.detail', 'permission:ot_invoice_view|ot_invoice_edit|ot_billing_manage');
        $this->assertRouteHasMiddleware('api.v1.hospital.ot.print.invoice', 'permission:ot_bill_print|ot_billing_manage');
        $this->assertRouteHasMiddleware('api.v1.hospital.ot.print.discharge', 'permission:ot_discharge_generate|ot_billing_manage');
        $this->assertRouteHasMiddleware('api.v1.hospital.ot.print.certificate', 'permission:ot_certificate_print|ot_billing_manage');
    }

    public function test_ward_handoff_keeps_web_canonical_permission(): void
    {
        $this->assertRouteHasMiddleware('api.v1.hospital.ot.ward.mark-ready', 'permission:ot_ward_entry');
    }

    public function test_authenticated_hospital_api_rejects_inactive_users(): void
    {
        $this->assertRouteHasMiddleware('api.v1.hospital.auth.me', 'hospital.user.active');
    }

    public function test_user_form_data_accepts_only_write_permissions(): void
    {
        $this->assertRouteHasMiddleware(
            'api.v1.hospital.config.users.form-data',
            'permission:user_doctor_add|user_doctor_edit|user_reception_add|user_reception_edit|user_ot_staff_add|user_ot_staff_edit'
        );
    }

    private function assertRouteHasMiddleware(string $name, string $middleware): void
    {
        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route, "Route {$name} was not registered.");
        $this->assertContains($middleware, $route->gatherMiddleware());
    }
}
