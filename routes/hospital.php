<?php

/**
 * hospital.php — Hospital Application Routes (Path-Based Multi-Tenant)
 *
 * Ye routes har hospital ke liye dynamically load hoti hain.
 * URL pattern: hmssaas.com/{slug}/...
 *
 * Middleware Stack:
 *   1. identify.tenant    → Slug se tenant identify karo (DB lookup)
 *   2. set.tenant.scope   → BelongsToTenant ke liye config set karo
 *   3. auth.hospital      → Login check (Admin/Doctor/Reception/OtStaff)
 *   4. subscription.active → Subscription valid hai?
 *   5. grace.check        → Grace period warning flag
 *
 * IMPORTANT: {slug} ek URL parameter hai, subdomain NAHI.
 *
 * RULE 1: BAD:  aakasheye.hmssaas.com/patients
 *         GOOD: hmssaas.com/aakasheye/patients
 */

use App\Http\Controllers\Hospital\Auth\LoginController;
use App\Http\Controllers\Hospital\Auth\PasswordResetController;
use App\Http\Controllers\Hospital\Dashboard\DashboardController;
use App\Http\Controllers\Hospital\Dashboard\AdminCollectionController;
use App\Http\Controllers\Hospital\Dashboard\AdminDashboardPatientsController;
use App\Http\Controllers\Hospital\Dashboard\AssistantOtListController;
use App\Http\Controllers\Hospital\Dashboard\DoctorOtListController;
use App\Http\Controllers\Hospital\Dashboard\OtAppointmentListController;
use App\Http\Controllers\Hospital\Dashboard\ReceptionistTotalPatientsController;
use App\Http\Controllers\Hospital\Examination\ClinicalQueueController;
use App\Http\Controllers\Hospital\Examination\PrimaryExamController;
use App\Http\Controllers\Hospital\Examination\SecondaryExamController;
use App\Http\Controllers\Hospital\Master\BasicMasterController;
use App\Http\Controllers\Hospital\Master\DetailMasterController;
use App\Http\Controllers\Hospital\Master\OT\OtChargeHeadController;
use App\Http\Controllers\Hospital\Master\OT\OtLensOptionController;
use App\Http\Controllers\Hospital\Master\OT\OtPackageMasterController;
use App\Http\Controllers\Hospital\Master\OT\LensInventoryController;
use App\Http\Controllers\Hospital\Master\OT\OtLensPowerController;
use App\Http\Controllers\Hospital\Master\OT\OtSlotController;
use App\Http\Controllers\Hospital\Master\OT\OtSurgeryTypeController;
use App\Http\Controllers\Hospital\Master\OT\OtTypeController;
use App\Http\Controllers\Hospital\Medicine\MedicineCategoryController;
use App\Http\Controllers\Hospital\Medicine\MedicineController;
use App\Http\Controllers\Hospital\Medicine\MedicineDosageController;
use App\Http\Controllers\Hospital\Medicine\MedicineGroupController;
use App\Http\Controllers\Hospital\Medicine\MedicineInstructionController;
use App\Http\Controllers\Hospital\Medicine\MedicineRouteController;
use App\Http\Controllers\Hospital\Medicine\MedicineTypeController;
use App\Http\Controllers\Hospital\OT\OtAccountantController;
use App\Http\Controllers\Hospital\OT\OtAppointmentController;
use App\Http\Controllers\Hospital\OT\OtAssistantController;
use App\Http\Controllers\Hospital\OT\OtBookingController;
use App\Http\Controllers\Hospital\OT\OtCounsellorController;
use App\Http\Controllers\Hospital\OT\OtDischargeController;
use App\Http\Controllers\Hospital\OT\OtInvoiceController;
use App\Http\Controllers\Hospital\OT\OtReceptionistController;
use App\Http\Controllers\Hospital\OT\OtWardController;
use App\Http\Controllers\Hospital\Patient\PatientController;
use App\Http\Controllers\Hospital\Patient\PatientHistoryController;
use App\Http\Controllers\Hospital\Report\OtReportController;
use App\Http\Controllers\Hospital\Report\ReportController;
use App\Http\Controllers\Hospital\Role\RoleController;
use App\Http\Controllers\Hospital\Setting\DoctorProfileController;
use App\Http\Controllers\Hospital\Setting\HospitalSettingController;
use App\Http\Controllers\Hospital\Setting\SetupWizardController;
use App\Http\Controllers\Hospital\Setting\TimezoneController;
use App\Http\Controllers\Hospital\Subscription\SubscriptionController;
use App\Http\Controllers\Hospital\User\HospitalUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('{slug}')
    ->middleware(['identify.tenant', 'set.tenant.scope'])
    ->name('hospital.')
    ->group(function () {

        // ================================================================
        // Login / Logout — no hospital auth required
        // ================================================================

        // Hospital login page: hmssaas.com/{slug}/login
        Route::get('/login', [LoginController::class, 'show'])
            ->middleware('redirect.inactive')
            ->name('login');
        Route::post('/login', [LoginController::class, 'login'])
            ->middleware('redirect.inactive')
            ->name('login.post');
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

        // ================================================================
        // Forgot / Reset Password — no hospital auth required
        // ================================================================
        Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
        Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
        Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');

        // ================================================================
        // Subscription / Billing — accessible during grace (no subscription.active)
        // ================================================================
        Route::middleware(['auth.hospital'])->group(function () {
            Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
            Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
            Route::post('/subscription/confirm', [SubscriptionController::class, 'confirm'])->name('subscription.confirm');
            Route::get('/subscription/invoice/{payment}', [SubscriptionController::class, 'downloadInvoice'])
                ->name('subscription.invoice')
                ->whereNumber('payment');
        });

        // ================================================================
        // Authenticated Hospital Routes
        // ================================================================

        Route::middleware(['auth.hospital', 'subscription.active', 'grace.check'])
            ->group(function () {

                // Dashboard — role ke hisaab se alag content return karega
                Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
                Route::get('/receptionist/total-patients', [ReceptionistTotalPatientsController::class, 'index'])
                    ->name('receptionist.total-patients')
                    ->middleware('permission:patient_view');
                Route::get('/dashboard/admin-patients', [AdminDashboardPatientsController::class, 'index'])
                    ->name('dashboard.admin-patients')
                    ->middleware('permission:patient_view|report_view');
                Route::get('/dashboard/admin-patients/export', [AdminDashboardPatientsController::class, 'export'])
                    ->name('dashboard.admin-patients.export')
                    ->middleware('permission:patient_view|report_view|report_export');
                Route::get('/dashboard/collection', [AdminCollectionController::class, 'index'])
                    ->name('dashboard.collection')
                    ->middleware('permission:patient_view|report_view|opd_reports_view');
                Route::get('/dashboard/collection/{reception}', [AdminCollectionController::class, 'show'])
                    ->whereNumber('reception')
                    ->name('dashboard.collection.show')
                    ->middleware('permission:patient_view|report_view|opd_reports_view');
                Route::get('/dashboard/ot-appointments', [OtAppointmentListController::class, 'index'])
                    ->name('dashboard.ot-appointments')
                    ->middleware('permission:ot_appointment_view|ot_patient_list');
                Route::get('/dashboard/doctor-ot', [DoctorOtListController::class, 'index'])
                    ->name('dashboard.doctor-ot')
                    ->middleware('permission:exam_secondary|ot_patient_list|ot_surgery_recommend');
                Route::post('/dashboard/doctor-ot/{bookingId}/assign-assistant', [DoctorOtListController::class, 'assignAssistant'])
                    ->name('dashboard.doctor-ot.assign-assistant')
                    ->whereNumber('bookingId')
                    ->middleware('permission:exam_secondary|ot_patient_list|ot_surgery_recommend');
                Route::post('/dashboard/doctor-ot/{bookingId}/refuse', [DoctorOtListController::class, 'refuseSurgery'])
                    ->name('dashboard.doctor-ot.refuse')
                    ->whereNumber('bookingId')
                    ->middleware('permission:exam_secondary|ot_patient_list|ot_surgery_recommend');
                Route::get('/dashboard/assistant-ot', [AssistantOtListController::class, 'index'])
                    ->name('dashboard.assistant-ot')
                    ->middleware('permission:ot_patient_list|ot_surgery_record|ot_lens_record|ot_surgery_ready');

                // ============================================================
                // Patient Management
                // ============================================================
                Route::prefix('patients')->name('patients.')->group(function () {
                    Route::get('/', [PatientController::class, 'index'])->name('index')->middleware('permission:patient_view');
                    Route::get('/create', [PatientController::class, 'create'])->name('create')->middleware('permission:patient_register');
                    Route::get('/phone-history', [PatientController::class, 'phoneHistory'])->name('phone-history')->middleware('permission:patient_register_phone');
                    Route::get('/search-by-contact', [PatientController::class, 'searchByContact'])->name('search-by-contact')->middleware('permission:patient_register');
                    Route::post('/', [PatientController::class, 'store'])->name('store')->middleware('permission:patient_register');
                    Route::get('/{patient}', [PatientController::class, 'show'])->name('show')->middleware('permission:patient_view');
                    Route::get('/{patient}/edit', [PatientController::class, 'edit'])->name('edit')->middleware('permission:patient_edit');
                    Route::put('/{patient}', [PatientController::class, 'update'])->name('update')->middleware('permission:patient_edit');
                    Route::patch('/{patient}/quick-name', [PatientController::class, 'quickUpdateName'])->name('quick-name')->middleware('permission:patient_edit');
                    Route::patch('/{patient}/quick-personal', [PatientController::class, 'quickUpdatePersonal'])->name('quick-personal')->middleware('permission:patient_edit');
                    Route::delete('/{patient}', [PatientController::class, 'destroy'])->name('destroy')->middleware('permission:patient_delete');
                    Route::get('/phone/create', [PatientController::class, 'createPhone'])->name('create-phone')->middleware('permission:patient_register_phone');
                    Route::post('/phone', [PatientController::class, 'storePhone'])->name('store-phone')->middleware('permission:patient_register_phone');
                    Route::get('/{patient}/print', [PatientController::class, 'print'])->name('print')->middleware('permission:bill_print');
                    Route::get('/{patient}/bill-pdf', [PatientController::class, 'downloadBill'])->name('bill-pdf')->middleware('permission:bill_print');
                    Route::get('/{patient}/checkin', [PatientController::class, 'checkinForm'])->name('checkin')->middleware('permission:patient_register');
                    Route::post('/{patient}/checkin', [PatientController::class, 'checkin'])->name('checkin.store')->middleware('permission:patient_register');
                });

                Route::get('patient-history', [PatientHistoryController::class, 'index'])->name('patients.history')->middleware('permission:exam_history');
                Route::get('patient-history/{patient}/print', [PatientHistoryController::class, 'print'])->name('patients.history.print')->middleware('permission:exam_history');
                Route::get('doctor-history', [DashboardController::class, 'history'])->name('doctor.history')->middleware('permission:exam_history');
                Route::get('hospital-history', [DashboardController::class, 'hospitalHistory'])->name('hospital.history')->middleware('permission:exam_history');

                // Shared patient history (cross-tenant, partner hospitals only)
                Route::get('shared-patient-history', [DashboardController::class, 'sharedPatientHistory'])
                    ->name('shared.patient.history')
                    ->middleware('permission:exam_history');

                // Partner hospital's full patient history list
                Route::get('partner-history/{partnerTenantId}', [DashboardController::class, 'partnerHistory'])
                    ->name('partner.history')->whereNumber('partnerTenantId')->middleware('permission:exam_history');

                // Hospital Share Requests
                Route::post('hospital-share-requests/{toTenantId}', [DashboardController::class, 'sendShareRequest'])
                    ->name('hospital.share.send')->whereNumber('toTenantId')->middleware('permission:exam_history');
                Route::post('hospital-share-requests/{requestId}/accept', [DashboardController::class, 'acceptShareRequest'])
                    ->name('hospital.share.accept')->whereNumber('requestId')->middleware('permission:exam_history');
                Route::delete('hospital-share-requests/{requestId}', [DashboardController::class, 'removeShareRequest'])
                    ->name('hospital.share.remove')->whereNumber('requestId')->middleware('permission:exam_history');

                // ============================================================
                // Clinical Queue Dashboard
                // ============================================================
                Route::get('clinical-queue', [ClinicalQueueController::class, 'index'])
                    ->name('clinical.queue')
                    ->middleware('permission:exam_primary|exam_secondary');

                // ============================================================
                // Eye Examination — Phase 5 Clinical Module
                // ============================================================
                Route::get('exam/primary/{id}', [PrimaryExamController::class, 'show'])
                    ->name('exam.primary.show')
                    ->middleware('permission:exam_primary');
                Route::post('exam/primary/{id}', [PrimaryExamController::class, 'save'])
                    ->name('exam.primary.save')
                    ->middleware('permission:exam_primary');
                Route::get('exam/primary/{id}/print', [PrimaryExamController::class, 'printRx'])
                    ->name('exam.primary.print')
                    ->middleware('permission:prescription_print');
                Route::get('exam/primary/{id}/hud', [PrimaryExamController::class, 'compactView'])
                    ->name('exam.primary.hud')
                    ->middleware('permission:prescription_print');
                Route::get('exam/secondary/{id}', [SecondaryExamController::class, 'show'])
                    ->name('exam.secondary.show')
                    ->middleware('permission:exam_secondary');
                Route::post('exam/secondary/{id}', [SecondaryExamController::class, 'save'])
                    ->name('exam.secondary.save')
                    ->middleware('permission:exam_secondary');
                Route::get('exam/secondary/{id}/print', [SecondaryExamController::class, 'printRx'])
                    ->name('exam.secondary.print')
                    ->middleware('permission:prescription_print');

                // AJAX: Hospital details for modal
                Route::get('ajax/hospital-details/{id}', [DashboardController::class, 'getHospitalDetails'])
                    ->name('ajax.hospital.details')
                    ->whereNumber('id');

                // AJAX: Patients by phone — returns JSON list for patient_id disambiguation
                Route::get('ajax/patients-by-phone', [PatientHistoryController::class, 'getPatientsByPhone'])
                    ->name('ajax.patients.by-phone')
                    ->middleware('permission:exam_history');

                // AJAX helpers for exam form
                Route::post('ajax/complaint', [PrimaryExamController::class, 'ajaxAddComplaint'])
                    ->name('ajax.complaint.add')
                    ->middleware('permission:exam_primary');
                Route::post('ajax/diagnosis', [PrimaryExamController::class, 'ajaxAddDiagnosis'])
                    ->name('ajax.diagnosis.add')
                    ->middleware('permission:exam_primary');
                Route::post('ajax/advice', [PrimaryExamController::class, 'ajaxAddAdvice'])
                    ->name('ajax.advice.add')
                    ->middleware('permission:exam_primary');
                Route::get('ajax/medicines', [PrimaryExamController::class, 'ajaxSearchMedicines'])
                    ->name('ajax.medicines.search')
                    ->middleware('permission:exam_primary|exam_secondary');
                Route::get('ajax/medicine-group/{id}', [PrimaryExamController::class, 'ajaxGetMedicineGroup'])
                    ->name('ajax.medicine-group.get')
                    ->middleware('permission:exam_primary|exam_secondary');

                // Medicine Master
                // ============================================================
                Route::prefix('medicines')->name('medicines.')->group(function () {
                    Route::get('/', [MedicineController::class, 'index'])->name('index')->middleware('permission:medicine_view');
                    Route::get('/create', [MedicineController::class, 'create'])->name('create')->middleware('permission:medicine_add');
                    Route::post('/', [MedicineController::class, 'store'])->name('store')->middleware('permission:medicine_add');
                    Route::post('/import', [MedicineController::class, 'import'])->name('import')->middleware('permission:medicine_add');
                    Route::get('/import/sample', [MedicineController::class, 'downloadSample'])->name('import.sample')->middleware('permission:medicine_add');
                    Route::get('/{medicine}/edit', [MedicineController::class, 'edit'])->name('edit')->middleware('permission:medicine_edit');
                    Route::put('/{medicine}', [MedicineController::class, 'update'])->name('update')->middleware('permission:medicine_edit');
                    Route::delete('/{medicine}', [MedicineController::class, 'destroy'])->name('destroy')->middleware('permission:medicine_delete');
                });

                Route::get('medicine-routes', [MedicineRouteController::class, 'index'])->name('medicine-routes.index')->middleware('permission:medicine_view');
                Route::post('medicine-routes', [MedicineRouteController::class, 'store'])->name('medicine-routes.store')->middleware('permission:medicine_add');
                Route::put('medicine-routes/{id}', [MedicineRouteController::class, 'update'])->name('medicine-routes.update')->whereNumber('id')->middleware('permission:medicine_edit');
                Route::delete('medicine-routes/{id}', [MedicineRouteController::class, 'destroy'])->name('medicine-routes.destroy')->whereNumber('id')->middleware('permission:medicine_delete');

                Route::get('medicine-categories', [MedicineCategoryController::class, 'index'])->name('medicine-categories.index')->middleware('permission:medicine_view');
                Route::post('medicine-categories', [MedicineCategoryController::class, 'store'])->name('medicine-categories.store')->middleware('permission:medicine_add');
                Route::put('medicine-categories/{id}', [MedicineCategoryController::class, 'update'])->name('medicine-categories.update')->whereNumber('id')->middleware('permission:medicine_edit');
                Route::delete('medicine-categories/{id}', [MedicineCategoryController::class, 'destroy'])->name('medicine-categories.destroy')->whereNumber('id')->middleware('permission:medicine_delete');

                Route::get('medicine-types', [MedicineTypeController::class, 'index'])->name('medicine-types.index')->middleware('permission:medicine_view');
                Route::post('medicine-types', [MedicineTypeController::class, 'store'])->name('medicine-types.store')->middleware('permission:medicine_add');
                Route::put('medicine-types/{id}', [MedicineTypeController::class, 'update'])->name('medicine-types.update')->whereNumber('id')->middleware('permission:medicine_edit');
                Route::delete('medicine-types/{id}', [MedicineTypeController::class, 'destroy'])->name('medicine-types.destroy')->whereNumber('id')->middleware('permission:medicine_delete');

                Route::get('medicine-dosages', [MedicineDosageController::class, 'index'])->name('medicine-dosages.index')->middleware('permission:medicine_view');
                Route::post('medicine-dosages', [MedicineDosageController::class, 'store'])->name('medicine-dosages.store')->middleware('permission:medicine_add');
                Route::put('medicine-dosages/{id}', [MedicineDosageController::class, 'update'])->name('medicine-dosages.update')->whereNumber('id')->middleware('permission:medicine_edit');
                Route::delete('medicine-dosages/{id}', [MedicineDosageController::class, 'destroy'])->name('medicine-dosages.destroy')->whereNumber('id')->middleware('permission:medicine_delete');

                Route::get('medicine-instructions', [MedicineInstructionController::class, 'index'])->name('medicine_instructions.index')->middleware('permission:medicine_view');
                Route::post('medicine-instructions', [MedicineInstructionController::class, 'store'])->name('medicine_instructions.store')->middleware('permission:medicine_add');
                Route::put('medicine-instructions/{id}', [MedicineInstructionController::class, 'update'])->name('medicine_instructions.update')->whereNumber('id')->middleware('permission:medicine_edit');
                Route::delete('medicine-instructions/{id}', [MedicineInstructionController::class, 'destroy'])->name('medicine_instructions.destroy')->whereNumber('id')->middleware('permission:medicine_delete');

                Route::prefix('medicine-groups')->name('medicine-groups.')->group(function () {
                    Route::get('/', [MedicineGroupController::class, 'index'])->name('index')->middleware('permission:medicine_view');
                    Route::get('/create', [MedicineGroupController::class, 'create'])->name('create')->middleware('permission:medicine_add');
                    Route::post('/', [MedicineGroupController::class, 'store'])->name('store')->middleware('permission:medicine_add');
                    Route::get('/{medicine_group}', [MedicineGroupController::class, 'show'])->name('show')->middleware('permission:medicine_view');
                    Route::get('/{medicine_group}/edit', [MedicineGroupController::class, 'edit'])->name('edit')->middleware('permission:medicine_edit');
                    Route::put('/{medicine_group}', [MedicineGroupController::class, 'update'])->name('update')->middleware('permission:medicine_edit');
                    Route::delete('/{medicine_group}', [MedicineGroupController::class, 'destroy'])->name('destroy')->middleware('permission:medicine_delete');
                });

                // ============================================================
                // Reports (Phase 6 me implement hoga)
                // ============================================================
                Route::prefix('reports')->name('reports.')->group(function () {
                    Route::get('/', [ReportController::class, 'index'])->name('index')->middleware('permission:report_view');
                    Route::get('/export/excel', [ReportController::class, 'exportExcel'])->name('export.excel')->middleware('permission:report_export');
                    Route::get('/export/pdf', [ReportController::class, 'exportPdf'])->name('export.pdf')->middleware('permission:report_export');
                    Route::get('/channel/{channel}', [ReportController::class, 'showChannel'])->name('channel.show')->middleware('permission:report_view');

                    // OT Reports — Phase 8 of OT Workflow Upgrade (docs/OT_WORKFLOW_UPGRADE_PRD.md §8)
                    Route::prefix('ot')->name('ot.')->group(function () {
                        Route::get('/', [OtReportController::class, 'index'])->name('index')->middleware('permission:report_view');
                        Route::get('/{type}', [OtReportController::class, 'show'])->name('show')->middleware('permission:report_view');
                        Route::get('/{type}/export', [OtReportController::class, 'export'])->name('export')->middleware('permission:report_export');
                        Route::get('/{type}/export-pdf', [OtReportController::class, 'exportPdf'])->name('export.pdf')->middleware('permission:report_export');
                        Route::get('/prescription/{patient}/pdf', [OtReportController::class, 'patientPrescriptionPdf'])->name('prescription.pdf')->middleware('permission:report_view');
                    });
                });

                // ============================================================
                // Masters Management — Basic & Detail (Phase 6)
                // ============================================================
                Route::prefix('masters')->name('masters.')->group(function () {
                    Route::get('/', [BasicMasterController::class, 'landing'])->name('index')->middleware('permission:casetype_manage|eye_exam_master_manage|location_manage|ot_slot_manage|ot_type_manage|ot_charge_manage|ot_inventory_manage|ot_lens_option_manage|ot_lens_power_manage|ot_package_master_manage');

                    // Basic Masters: type-level auth in BasicMasterController::authorizeBasicType()
                    Route::prefix('basic')->name('basic.')
                        ->middleware('permission:casetype_manage|location_manage')
                        ->group(function () {
                            Route::get('{type}', [BasicMasterController::class, 'index'])->name('index');
                            Route::post('{type}', [BasicMasterController::class, 'store'])->name('store');
                            // AJAX create for masters from forms (e.g. add city inline)
                            Route::post('{type}/ajax', [BasicMasterController::class, 'ajaxStore'])
                                ->withoutMiddleware('permission:casetype_manage|location_manage')
                                ->name('ajax.store');
                            Route::put('{type}/{id}', [BasicMasterController::class, 'update'])->name('update')->whereNumber('id');
                            Route::delete('{type}/{id}', [BasicMasterController::class, 'destroy'])->name('destroy')->whereNumber('id');
                        });

                    // Detail (Eye-Exam) Masters: vn, pnvn, sph_cyl, axis, complaints, etc.
                    Route::prefix('detail')->name('detail.')
                        ->middleware('permission:eye_exam_master_manage')
                        ->group(function () {
                            Route::get('{type}', [DetailMasterController::class, 'index'])->name('index');
                            Route::post('{type}', [DetailMasterController::class, 'store'])->middleware('permission:eye_exam_master_manage')->name('store');
                            Route::post('{type}/sync-by-diagnosis', [DetailMasterController::class, 'syncByDiagnosis'])->middleware('permission:eye_exam_master_manage')->name('sync-by-diagnosis');
                            Route::put('{type}/{id}', [DetailMasterController::class, 'update'])->middleware('permission:eye_exam_master_manage')->name('update')->whereNumber('id');
                            Route::post('{type}/{id}/toggle-favourite', [DetailMasterController::class, 'toggleFavourite'])->middleware('permission:eye_exam_master_manage')->name('toggle-favourite')->whereNumber('id');
                            Route::delete('{type}/{id}', [DetailMasterController::class, 'destroy'])->middleware('permission:eye_exam_master_manage')->name('destroy')->whereNumber('id');
                        });

                    Route::prefix('ot')->name('ot.')->group(function () {
                        Route::get('lens-options', [OtLensOptionController::class, 'index'])->name('lens-options.index')->middleware('permission:'.\App\Services\Auth\PermissionMatrix::anyCrud('ot_lens_option'));
                        Route::post('lens-options', [OtLensOptionController::class, 'store'])->name('lens-options.store')->middleware('permission:ot_lens_option_add');
                        Route::put('lens-options/{id}', [OtLensOptionController::class, 'update'])->name('lens-options.update')->whereNumber('id')->middleware('permission:ot_lens_option_edit');
                        Route::delete('lens-options/{id}', [OtLensOptionController::class, 'destroy'])->name('lens-options.destroy')->whereNumber('id')->middleware('permission:ot_lens_option_delete');

                        Route::get('slots', [OtSlotController::class, 'index'])->name('slots.index')->middleware('permission:'.\App\Services\Auth\PermissionMatrix::anyCrud('ot_slot'));
                        Route::post('slots', [OtSlotController::class, 'store'])->name('slots.store')->middleware('permission:ot_slot_add');
                        Route::put('slots/{id}', [OtSlotController::class, 'update'])->name('slots.update')->whereNumber('id')->middleware('permission:ot_slot_edit');
                        Route::delete('slots/{id}', [OtSlotController::class, 'destroy'])->name('slots.destroy')->whereNumber('id')->middleware('permission:ot_slot_delete');

                        Route::get('types', [OtTypeController::class, 'index'])->name('types.index')->middleware('permission:'.\App\Services\Auth\PermissionMatrix::anyCrud('ot_type'));
                        Route::post('types', [OtTypeController::class, 'store'])->name('types.store')->middleware('permission:ot_type_add');
                        Route::put('types/{id}', [OtTypeController::class, 'update'])->name('types.update')->whereNumber('id')->middleware('permission:ot_type_edit');
                        Route::delete('types/{id}', [OtTypeController::class, 'destroy'])->name('types.destroy')->whereNumber('id')->middleware('permission:ot_type_delete');

                        Route::get('surgery-types', [OtSurgeryTypeController::class, 'index'])->name('surgery-types.index')->middleware('permission:'.\App\Services\Auth\PermissionMatrix::anyCrud('ot_type'));
                        Route::post('surgery-types', [OtSurgeryTypeController::class, 'store'])->name('surgery-types.store')->middleware('permission:ot_type_add');
                        Route::put('surgery-types/{id}', [OtSurgeryTypeController::class, 'update'])->name('surgery-types.update')->whereNumber('id')->middleware('permission:ot_type_edit');
                        Route::delete('surgery-types/{id}', [OtSurgeryTypeController::class, 'destroy'])->name('surgery-types.destroy')->whereNumber('id')->middleware('permission:ot_type_delete');

                        Route::get('charge-heads', [OtChargeHeadController::class, 'index'])->name('charge-heads.index')->middleware('permission:'.\App\Services\Auth\PermissionMatrix::anyCrud('ot_charge'));
                        Route::post('charge-heads', [OtChargeHeadController::class, 'store'])->name('charge-heads.store')->middleware('permission:ot_charge_add');
                        Route::put('charge-heads/{id}', [OtChargeHeadController::class, 'update'])->name('charge-heads.update')->whereNumber('id')->middleware('permission:ot_charge_edit');
                        Route::delete('charge-heads/{id}', [OtChargeHeadController::class, 'destroy'])->name('charge-heads.destroy')->whereNumber('id')->middleware('permission:ot_charge_delete');

                        Route::get('packages', [OtPackageMasterController::class, 'index'])->name('packages.index')->middleware('permission:'.\App\Services\Auth\PermissionMatrix::anyCrud('ot_package_master'));
                        Route::post('packages', [OtPackageMasterController::class, 'store'])->name('packages.store')->middleware('permission:ot_package_master_add');
                        Route::put('packages/{id}', [OtPackageMasterController::class, 'update'])->name('packages.update')->whereNumber('id')->middleware('permission:ot_package_master_edit');
                        Route::delete('packages/{id}', [OtPackageMasterController::class, 'destroy'])->name('packages.destroy')->whereNumber('id')->middleware('permission:ot_package_master_delete');

                        // Lens Power master — Phase 4 of OT Workflow Upgrade (docs/OT_WORKFLOW_UPGRADE_PRD.md §4)
                        Route::get('lens-powers', [OtLensPowerController::class, 'index'])->name('lens-powers.index')->middleware('permission:'.\App\Services\Auth\PermissionMatrix::anyCrud('ot_lens_power'));
                        Route::post('lens-powers', [OtLensPowerController::class, 'store'])->name('lens-powers.store')->middleware('permission:ot_lens_power_add');
                        Route::put('lens-powers/{id}', [OtLensPowerController::class, 'update'])->name('lens-powers.update')->whereNumber('id')->middleware('permission:ot_lens_power_edit');
                        Route::delete('lens-powers/{id}', [OtLensPowerController::class, 'destroy'])->name('lens-powers.destroy')->whereNumber('id')->middleware('permission:ot_lens_power_delete');

                        // Lens Inventory (stock) master — Phase 7 of OT Workflow Upgrade (docs/OT_WORKFLOW_UPGRADE_PRD.md §7)
                        Route::get('lens-inventory', [LensInventoryController::class, 'index'])->name('lens-inventory.index')->middleware('permission:'.\App\Services\Auth\PermissionMatrix::anyCrud('ot_inventory'));
                        Route::post('lens-inventory', [LensInventoryController::class, 'store'])->name('lens-inventory.store')->middleware('permission:ot_inventory_add');
                        Route::put('lens-inventory/{id}', [LensInventoryController::class, 'update'])->name('lens-inventory.update')->whereNumber('id')->middleware('permission:ot_inventory_edit');
                        Route::delete('lens-inventory/{id}', [LensInventoryController::class, 'destroy'])->name('lens-inventory.destroy')->whereNumber('id')->middleware('permission:ot_inventory_delete');
                    });
                });

                // ============================================================
                // My Profile (self-edit for any logged-in user)
                // ============================================================
                Route::get('profile', [DoctorProfileController::class, 'show'])->name('profile.show');
                Route::put('profile', [DoctorProfileController::class, 'update'])->name('profile.update');

                // ============================================================
                // Settings
                // ============================================================
                Route::prefix('settings')->name('settings.')->group(function () {
                    Route::get('/', [HospitalSettingController::class, 'index'])->name('index')->middleware('permission:setting_hospital');
                    Route::put('/', [HospitalSettingController::class, 'update'])->name('update')->middleware('permission:setting_hospital');

                    // Timezone management
                    Route::prefix('timezone')->name('timezone.')->group(function () {
                        Route::get('/', [TimezoneController::class, 'index'])->name('index');
                        Route::put('/', [TimezoneController::class, 'update'])->name('update');
                        Route::post('/reset', [TimezoneController::class, 'reset'])->name('reset');
                    });
                });

                // ============================================================
                // Roles & Permissions (Hospital Admin)
                // ============================================================
                Route::prefix('roles')->name('roles.')->middleware('permission:role_manage')->group(function () {
                    Route::get('/', [RoleController::class, 'index'])->name('index');
                    Route::get('/create', [RoleController::class, 'create'])->name('create')->middleware('permission:role_manage');
                    Route::post('/', [RoleController::class, 'store'])->name('store')->middleware('permission:role_manage');
                    Route::get('/{id}/edit', [RoleController::class, 'edit'])->whereNumber('id')->name('edit')->middleware('permission:role_manage');
                    Route::put('/{id}', [RoleController::class, 'update'])->whereNumber('id')->name('update')->middleware('permission:role_manage');
                    Route::delete('/{id}', [RoleController::class, 'destroy'])->whereNumber('id')->name('destroy')->middleware('permission:role_manage');
                });

                // ============================================================
                // User Management (Hospital Admin)
                // ============================================================
                Route::prefix('users')->name('users.')->group(function () {
                    Route::get('/', [HospitalUserController::class, 'index'])->name('index')->middleware('permission:user_doctor_manage|user_reception_manage|user_ot_staff_manage');
                    Route::get('/create', [HospitalUserController::class, 'create'])->name('create')->middleware('permission:user_doctor_manage|user_reception_manage|user_ot_staff_manage');
                    Route::post('/', [HospitalUserController::class, 'store'])->name('store')->middleware('permission:user_doctor_manage|user_reception_manage|user_ot_staff_manage');
                    Route::get('/{id}/edit', [HospitalUserController::class, 'edit'])->whereNumber('id')->name('edit')->middleware('permission:user_doctor_manage|user_reception_manage|user_ot_staff_manage');
                    Route::put('/{id}', [HospitalUserController::class, 'update'])->whereNumber('id')->name('update')->middleware('permission:user_doctor_manage|user_reception_manage|user_ot_staff_manage');
                    Route::delete('/{id}', [HospitalUserController::class, 'destroy'])->whereNumber('id')->name('destroy')->middleware('permission:user_doctor_manage|user_reception_manage|user_ot_staff_manage');
                });

                // ============================================================
                // OT Module Routes — Phase 9A Receptionist + Booking Flow
                // ============================================================
                Route::prefix('ot')->name('ot.')->middleware(['auth.hospital'])->group(function () {
                    Route::get('/', function () {
                        return redirect()->route('hospital.ot.dashboard', ['slug' => request()->route('slug')]);
                    })->name('index')->middleware('permission:ot_patient_list|ot_appointment_view|ot_counselling_fill');

                    // ========================================================
                    // OT Appointments — Phase 2 of OT Workflow Upgrade
                    // (docs/OT_WORKFLOW_UPGRADE_PRD.md §2)
                    // ========================================================
                    Route::prefix('appointments')->name('appointments.')->group(function () {
                        Route::get('/', [OtAppointmentController::class, 'index'])
                            ->name('index')
                            ->middleware('permission:ot_appointment_view');

                        Route::get('/create', [OtAppointmentController::class, 'create'])
                            ->name('create')
                            ->middleware('permission:ot_appointment_create');

                        Route::post('/', [OtAppointmentController::class, 'store'])
                            ->name('store')
                            ->middleware('permission:ot_appointment_create');

                        Route::get('/{id}/edit', [OtAppointmentController::class, 'edit'])
                            ->name('edit')
                            ->whereNumber('id')
                            ->middleware('permission:ot_appointment_edit');

                        Route::put('/{id}', [OtAppointmentController::class, 'update'])
                            ->name('update')
                            ->whereNumber('id')
                            ->middleware('permission:ot_appointment_edit');

                        Route::post('/{id}/confirm', [OtAppointmentController::class, 'confirm'])
                            ->name('confirm')
                            ->whereNumber('id')
                            ->middleware('permission:ot_appointment_confirm');

                        Route::post('/{id}/cancel', [OtAppointmentController::class, 'cancel'])
                            ->name('cancel')
                            ->whereNumber('id')
                            ->middleware('permission:ot_appointment_cancel');

                        // Reception check-in also uses this (search by UHID/name/mobile/appointment
                        // number) — allow either OT staff or OPD reception to hit it.
                        Route::get('/search', [OtAppointmentController::class, 'search'])
                            ->name('search')
                            ->middleware('permission:ot_appointment_view|patient_register');

                        Route::get('/slot-appointments', [OtAppointmentController::class, 'slotAppointments'])
                            ->name('slot-appointments')
                            ->middleware('permission:ot_appointment_view|ot_appointment_create');

                        Route::get('/doctor-slot-load', [OtAppointmentController::class, 'doctorSlotLoad'])
                            ->name('doctor-slot-load')
                            ->middleware('permission:ot_appointment_view|ot_appointment_create|ot_appointment_edit|ot_appointment_confirm|ot_appointment_cancel');
                    });

                    Route::get('/ward', [OtAccountantController::class, 'wardIndex'])
                        ->name('ward.index')
                        ->middleware('permission:ot_ward_entry');

                    Route::post('/ward/{booking}/ready', [OtAccountantController::class, 'markReadyForOt'])
                        ->name('ward.ready')
                        ->whereNumber('booking')
                        ->middleware('permission:ot_ward_entry');

                    // ========================================================
                    // Ward vitals + eye-drop register — Phase 3 of OT Workflow Upgrade
                    // (docs/OT_WORKFLOW_UPGRADE_PRD.md §3)
                    // ========================================================
                    Route::get('/ward/{booking}', [OtWardController::class, 'show'])
                        ->name('ward.show')
                        ->whereNumber('booking')
                        ->middleware('permission:ot_ward_entry');

                    Route::post('/ward/{booking}/vitals', [OtWardController::class, 'storeVitals'])
                        ->name('ward.vitals.store')
                        ->whereNumber('booking')
                        ->middleware('permission:ot_preop_entry');

                    Route::post('/ward/{booking}/eye-drops', [OtWardController::class, 'addEyeDrop'])
                        ->name('ward.eye-drops.store')
                        ->whereNumber('booking')
                        ->middleware('permission:ot_dilation_track');

                    Route::get('/dashboard', [OtReceptionistController::class, 'dashboard'])
                        ->name('dashboard')
                        ->middleware('permission:ot_patient_list');

                    Route::get('/bookings', [OtBookingController::class, 'index'])
                        ->name('bookings.index')
                        ->middleware('permission:ot_patient_list');

                    Route::get('/bookings/create', [OtBookingController::class, 'create'])
                        ->name('bookings.create')
                        ->middleware('permission:ot_booking_create');

                    Route::post('/bookings', [OtBookingController::class, 'store'])
                        ->name('bookings.store')
                        ->middleware('permission:ot_booking_create');

                    // Phase A1 — Doctor Exam → Surgery Recommended (docs/OT_1.0_REMAINING_PRD.md)
                    Route::post('/recommend-surgery/{patientId}', [OtBookingController::class, 'recommendSurgery'])
                        ->name('recommend-surgery')
                        ->whereNumber('patientId')
                        ->middleware('permission:ot_surgery_recommend');

                    Route::prefix('accountant')->middleware('permission:ot_payment_record')->group(function () {
                        Route::get('/dashboard', [OtAccountantController::class, 'dashboard'])
                            ->name('accountant.dashboard');

                        Route::get('/money', [OtAccountantController::class, 'moneyReport'])
                            ->name('accountant.money');

                        Route::get('/payments/{bookingId}/create', [OtAccountantController::class, 'createPayment'])
                            ->name('payments.create')
                            ->whereNumber('bookingId');

                        Route::post('/payments/{bookingId}', [OtAccountantController::class, 'storePayment'])
                            ->name('payments.store')
                            ->whereNumber('bookingId');

                        Route::get('/payments/{paymentId}/receipt', [OtAccountantController::class, 'receiptPrint'])
                            ->name('payments.receipt')
                            ->whereNumber('paymentId');

                        Route::get('/refunds/{bookingId}/create', [OtAccountantController::class, 'createRefund'])
                            ->name('refunds.create')
                            ->whereNumber('bookingId');

                        Route::post('/refunds/{bookingId}', [OtAccountantController::class, 'storeRefund'])
                            ->name('refunds.store')
                            ->whereNumber('bookingId');
                    });

                    // ========================================================
                    // OT Counsellor — Phase 1 of OT Workflow Upgrade
                    // (docs/OT_WORKFLOW_UPGRADE_PRD.md §1)
                    // ========================================================
                    Route::prefix('counsellor')->middleware('permission:ot_counselling_fill')->group(function () {
                        Route::get('/dashboard', [OtCounsellorController::class, 'dashboard'])
                            ->name('counsellor.dashboard');

                        Route::get('/package-lookup', [OtCounsellorController::class, 'lookupPackage'])
                            ->name('counsellor.package-lookup');

                        Route::get('/booking/{bookingId}', [OtCounsellorController::class, 'show'])
                            ->name('counsellor.form')
                            ->whereNumber('bookingId');

                        Route::post('/booking/{bookingId}/counselling', [OtCounsellorController::class, 'storeCounselling'])
                            ->name('counsellor.counselling.store')
                            ->whereNumber('bookingId');

                        Route::post('/booking/{bookingId}/consent', [OtCounsellorController::class, 'storeConsent'])
                            ->name('counsellor.consent.store')
                            ->whereNumber('bookingId')
                            ->middleware('permission:ot_consent_capture');

                        Route::post('/booking/{bookingId}/send-to-billing', [OtCounsellorController::class, 'sendToBilling'])
                            ->name('counsellor.send-to-billing')
                            ->whereNumber('bookingId');
                    });

                    // OT Assistant — absorbs the old ot_doctor role's surgery recording
                    // (docs/tulsi.md §5) alongside its own lens-recording job.
                    Route::prefix('assistant')->middleware('permission:ot_lens_record|ot_lens_implant|ot_surgery_ready|ot_surgery_record|ot_patient_list')->group(function () {
                        Route::get('/dashboard', [OtAssistantController::class, 'dashboard'])
                            ->name('assistant.dashboard');

                        Route::get('/surgery/{bookingId}/create', [OtAssistantController::class, 'createSurgery'])
                            ->name('surgery.create')
                            ->whereNumber('bookingId')
                            ->middleware('permission:ot_surgery_record');

                        Route::post('/surgery/{bookingId}', [OtAssistantController::class, 'storeSurgery'])
                            ->name('surgery.store')
                            ->whereNumber('bookingId')
                            ->middleware('permission:ot_surgery_record');

                        Route::get('/lens/{bookingId}/edit', [OtAssistantController::class, 'editLens'])
                            ->name('assistant.lens.edit')
                            ->whereNumber('bookingId');

                        Route::post('/lens/{bookingId}/store', [OtAssistantController::class, 'storeLens'])
                            ->name('assistant.lens.store')
                            ->whereNumber('bookingId');
                    });

                    Route::prefix('billing')->middleware('permission:ot_billing_manage')->group(function () {
                        Route::get('/', [OtInvoiceController::class, 'index'])
                            ->name('billing.index');

                        Route::post('/invoice/{bookingId}/generate', [OtInvoiceController::class, 'generate'])
                            ->name('invoice.generate')
                            ->whereNumber('bookingId');

                        Route::get('/invoice/{bookingId}/print', [OtInvoiceController::class, 'print'])
                            ->name('invoice.print')
                            ->whereNumber('bookingId');

                        Route::get('/discharge/{bookingId}/print', [OtDischargeController::class, 'print'])
                            ->name('discharge.print')
                            ->whereNumber('bookingId');

                        Route::get('/summary-bill/{bookingId}/print', [OtInvoiceController::class, 'summaryBillPrint'])
                            ->name('summary-bill.print')
                            ->whereNumber('bookingId');

                        Route::get('/certificate/{bookingId}/print', [OtDischargeController::class, 'certificatePrint'])
                            ->name('certificate.print')
                            ->whereNumber('bookingId');

                        Route::get('/medicine-slip/{bookingId}/print', [OtDischargeController::class, 'medicineSlipPrint'])
                            ->name('medicine-slip.print')
                            ->whereNumber('bookingId');

                        // Phase 6 — Discharge Module document completion (docs/OT_WORKFLOW_UPGRADE_PRD.md §6)
                        Route::get('/prescription/{bookingId}/print', [OtDischargeController::class, 'prescriptionPrint'])
                            ->name('prescription.print')
                            ->whereNumber('bookingId');

                        Route::get('/lens-slip/{bookingId}/print', [OtDischargeController::class, 'lensSlipPrint'])
                            ->name('lens-slip.print')
                            ->whereNumber('bookingId');

                        Route::get('/followup-slip/{bookingId}/print', [OtDischargeController::class, 'followupSlipPrint'])
                            ->name('followup-slip.print')
                            ->whereNumber('bookingId');

                        Route::get('/print-all/{bookingId}', [OtDischargeController::class, 'printAllBundle'])
                            ->name('print-all')
                            ->whereNumber('bookingId');
                    });
                });

                // ============================================================
                // Setup Wizard (First-Login)
                // ============================================================
                Route::get('setup/{step}', [SetupWizardController::class, 'show'])->name('setup.show')
                    ->where('step', '[1-4]');
                Route::post('setup/{step}', [SetupWizardController::class, 'store'])->name('setup.store')
                    ->where('step', '[1-4]');
                Route::post('setup/{step}/skip', [SetupWizardController::class, 'skip'])->name('setup.skip')
                    ->where('step', '[1-4]');

            }); // end authenticated group

    }); // end {slug} prefix
