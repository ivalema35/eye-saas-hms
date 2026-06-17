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
use App\Http\Controllers\Hospital\Examination\ClinicalQueueController;
use App\Http\Controllers\Hospital\Examination\PrimaryExamController;
use App\Http\Controllers\Hospital\Examination\SecondaryExamController;
use App\Http\Controllers\Hospital\Foc\FocController;
use App\Http\Controllers\Hospital\Master\BasicMasterController;
use App\Http\Controllers\Hospital\Master\DetailMasterController;
use App\Http\Controllers\Hospital\Master\OT\OtChargeHeadController;
use App\Http\Controllers\Hospital\Master\OT\OtLensOptionController;
use App\Http\Controllers\Hospital\Master\OT\OtSlotController;
use App\Http\Controllers\Hospital\Master\OT\OtSurgeryTypeController;
use App\Http\Controllers\Hospital\Master\OT\OtTypeController;
use App\Http\Controllers\Hospital\Medicine\MedicineController;
use App\Http\Controllers\Hospital\Medicine\MedicineDosageController;
use App\Http\Controllers\Hospital\Medicine\MedicineGroupController;
use App\Http\Controllers\Hospital\Medicine\MedicineInstructionController;
use App\Http\Controllers\Hospital\Medicine\MedicineCategoryController;
use App\Http\Controllers\Hospital\Medicine\MedicineRouteController;
use App\Http\Controllers\Hospital\Medicine\MedicineTypeController;
use App\Http\Controllers\Hospital\OT\OtAccountantController;
use App\Http\Controllers\Hospital\OT\OtAssistantController;
use App\Http\Controllers\Hospital\OT\OtBookingController;
use App\Http\Controllers\Hospital\OT\OtDischargeController;
use App\Http\Controllers\Hospital\OT\OtDoctorController;
use App\Http\Controllers\Hospital\OT\OtInvoiceController;
use App\Http\Controllers\Hospital\OT\OtReceptionistController;
use App\Http\Controllers\Hospital\Patient\PatientController;
use App\Http\Controllers\Hospital\Patient\PatientHistoryController;
use App\Http\Controllers\Hospital\Report\ReportController;
use App\Http\Controllers\Hospital\Role\RoleController;
use App\Http\Controllers\Hospital\Setting\HospitalSettingController;
use App\Http\Controllers\Hospital\Setting\SetupWizardController;
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
        // Authenticated Hospital Routes
        // ================================================================

        Route::middleware(['auth.hospital', 'subscription.active', 'grace.check'])
            ->group(function () {

                // Dashboard — role ke hisaab se alag content return karega
                Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

                // ============================================================
                // Patient Management
                // ============================================================
                Route::prefix('patients')->name('patients.')->group(function () {
                    Route::get('/', [PatientController::class, 'index'])->name('index')->middleware('permission:opd.patient.view');
                    Route::get('/create', [PatientController::class, 'create'])->name('create')->middleware('permission:opd.patient.register');
                    Route::get('/phone-history', [PatientController::class, 'phoneHistory'])->name('phone-history')->middleware('permission:opd.patient.register_phone');
                    Route::get('/search-by-contact', [PatientController::class, 'searchByContact'])->name('search-by-contact')->middleware('permission:opd.patient.register');
                    Route::post('/', [PatientController::class, 'store'])->name('store')->middleware('permission:opd.patient.register');
                    Route::get('/{patient}', [PatientController::class, 'show'])->name('show')->middleware('permission:opd.patient.view');
                    Route::get('/{patient}/edit', [PatientController::class, 'edit'])->name('edit')->middleware('permission:opd.patient.edit');
                    Route::put('/{patient}', [PatientController::class, 'update'])->name('update')->middleware('permission:opd.patient.edit');
                    Route::delete('/{patient}', [PatientController::class, 'destroy'])->name('destroy')->middleware('permission:opd.patient.delete');
                    Route::get('/phone/create', [PatientController::class, 'createPhone'])->name('create-phone')->middleware('permission:opd.patient.register_phone');
                    Route::post('/phone', [PatientController::class, 'storePhone'])->name('store-phone')->middleware('permission:opd.patient.register_phone');
                    Route::get('/{patient}/print', [PatientController::class, 'print'])->name('print')->middleware('permission:opd.bill.print');
                    Route::get('/{patient}/bill-pdf', [PatientController::class, 'downloadBill'])->name('bill-pdf')->middleware('permission:opd.bill.print');
                    Route::get('/{patient}/checkin', [PatientController::class, 'checkinForm'])->name('checkin')->middleware('permission:opd.patient.register');
                    Route::post('/{patient}/checkin', [PatientController::class, 'checkin'])->name('checkin.store')->middleware('permission:opd.patient.register');
                });

                Route::get('patient-history', [PatientHistoryController::class, 'index'])->name('patients.history')->middleware('permission:opd.exam.history');
                Route::get('patient-history/{patient}/print', [PatientHistoryController::class, 'print'])->name('patients.history.print')->middleware('permission:opd.exam.history');
                Route::get('doctor-history', [DashboardController::class, 'history'])->name('doctor.history');
                Route::get('hospital-history', [DashboardController::class, 'hospitalHistory'])->name('hospital.history');

                // Shared patient history (cross-tenant, partner hospitals only)
                Route::get('shared-patient-history', [DashboardController::class, 'sharedPatientHistory'])
                    ->name('shared.patient.history');

                // Partner hospital's full patient history list
                Route::get('partner-history/{partnerTenantId}', [DashboardController::class, 'partnerHistory'])
                    ->name('partner.history')->whereNumber('partnerTenantId');

                // Hospital Share Requests
                Route::post('hospital-share-requests/{toTenantId}', [DashboardController::class, 'sendShareRequest'])
                    ->name('hospital.share.send')->whereNumber('toTenantId');
                Route::post('hospital-share-requests/{requestId}/accept', [DashboardController::class, 'acceptShareRequest'])
                    ->name('hospital.share.accept')->whereNumber('requestId');
                Route::delete('hospital-share-requests/{requestId}', [DashboardController::class, 'removeShareRequest'])
                    ->name('hospital.share.remove')->whereNumber('requestId');

                // ============================================================
                // Clinical Queue Dashboard
                // ============================================================
                Route::get('clinical-queue', [ClinicalQueueController::class, 'index'])
                    ->name('clinical.queue')
                    ->middleware('permission:opd.exam.primary|opd.exam.secondary');

                // ============================================================
                // Eye Examination — Phase 5 Clinical Module
                // ============================================================
                Route::get('exam/primary/{id}', [PrimaryExamController::class, 'show'])
                    ->name('exam.primary.show')
                    ->middleware('permission:opd.exam.primary');
                Route::post('exam/primary/{id}', [PrimaryExamController::class, 'save'])
                    ->name('exam.primary.save')
                    ->middleware('permission:opd.exam.primary');
                Route::get('exam/primary/{id}/print', [PrimaryExamController::class, 'printRx'])
                    ->name('exam.primary.print')
                    ->middleware('permission:opd.prescription.print');
                Route::get('exam/primary/{id}/hud', [PrimaryExamController::class, 'compactView'])
                    ->name('exam.primary.hud')
                    ->middleware('permission:opd.prescription.print');
                Route::get('exam/secondary/{id}', [SecondaryExamController::class, 'show'])
                    ->name('exam.secondary.show')
                    ->middleware('permission:opd.exam.secondary');
                Route::post('exam/secondary/{id}', [SecondaryExamController::class, 'save'])
                    ->name('exam.secondary.save')
                    ->middleware('permission:opd.exam.secondary');

                // AJAX: Hospital details for modal
                Route::get('ajax/hospital-details/{id}', [DashboardController::class, 'getHospitalDetails'])
                    ->name('ajax.hospital.details')
                    ->whereNumber('id');

                // AJAX: Patients by phone — returns JSON list for patient_id disambiguation
                Route::get('ajax/patients-by-phone', [PatientHistoryController::class, 'getPatientsByPhone'])
                    ->name('ajax.patients.by-phone')
                    ->middleware('permission:opd.exam.history');

                // AJAX helpers for exam form
                Route::post('ajax/complaint', [PrimaryExamController::class, 'ajaxAddComplaint'])
                    ->name('ajax.complaint.add')
                    ->middleware('permission:opd.exam.primary');
                Route::post('ajax/diagnosis', [PrimaryExamController::class, 'ajaxAddDiagnosis'])
                    ->name('ajax.diagnosis.add')
                    ->middleware('permission:opd.exam.primary');
                Route::post('ajax/advice', [PrimaryExamController::class, 'ajaxAddAdvice'])
                    ->name('ajax.advice.add')
                    ->middleware('permission:opd.exam.primary');
                Route::get('ajax/medicines', [PrimaryExamController::class, 'ajaxSearchMedicines'])
                    ->name('ajax.medicines.search')
                    ->middleware('permission:opd.exam.primary|opd.exam.secondary');
                Route::get('ajax/medicine-group/{id}', [PrimaryExamController::class, 'ajaxGetMedicineGroup'])
                    ->name('ajax.medicine-group.get')
                    ->middleware('permission:opd.exam.primary|opd.exam.secondary');

                // ============================================================
                // FOC Management
                // ============================================================
                Route::prefix('foc')->name('foc.')->group(function () {
                    Route::get('/', [FocController::class, 'index'])->name('index')->middleware('permission:opd.foc.create|opd.foc.accept');
                    Route::get('/create', [FocController::class, 'create'])->name('create')->middleware('permission:opd.foc.create');
                    Route::post('/', [FocController::class, 'store'])->name('store')->middleware('permission:opd.foc.create');
                    Route::post('/request', [FocController::class, 'store'])->name('request')->middleware('permission:opd.foc.create');
                    Route::get('/{foc}', [FocController::class, 'show'])->name('show')->middleware('permission:opd.foc.create|opd.foc.accept');
                    Route::post('/{id}/accept', [FocController::class, 'accept'])->name('accept')->middleware('permission:opd.foc.accept');
                    Route::patch('/{foc}/approve', [FocController::class, 'approve'])->name('approve')->middleware('permission:opd.foc.accept');
                    Route::patch('/{foc}/reject', [FocController::class, 'reject'])->name('reject')->middleware('permission:opd.foc.accept');
                });

                // ============================================================
                // Medicine Master
                // ============================================================
                Route::prefix('medicines')->name('medicines.')->middleware('permission:master.medicines')->group(function () {
                    Route::get('/', [MedicineController::class, 'index'])->name('index');
                    Route::get('/create', [MedicineController::class, 'create'])->name('create');
                    Route::post('/', [MedicineController::class, 'store'])->name('store');
                    Route::get('/{medicine}/edit', [MedicineController::class, 'edit'])->name('edit');
                    Route::put('/{medicine}', [MedicineController::class, 'update'])->name('update');
                    Route::delete('/{medicine}', [MedicineController::class, 'destroy'])->name('destroy');
                });

                Route::get('medicine-routes', [MedicineRouteController::class, 'index'])->name('medicine-routes.index')->middleware('permission:master.medicines');
                Route::post('medicine-routes', [MedicineRouteController::class, 'store'])->name('medicine-routes.store')->middleware('permission:master.medicines');
                Route::put('medicine-routes/{id}', [MedicineRouteController::class, 'update'])->name('medicine-routes.update')->whereNumber('id')->middleware('permission:master.medicines');
                Route::delete('medicine-routes/{id}', [MedicineRouteController::class, 'destroy'])->name('medicine-routes.destroy')->whereNumber('id')->middleware('permission:master.medicines');

                Route::get('medicine-categories', [MedicineCategoryController::class, 'index'])->name('medicine-categories.index')->middleware('permission:master.medicines');
                Route::post('medicine-categories', [MedicineCategoryController::class, 'store'])->name('medicine-categories.store')->middleware('permission:master.medicines');
                Route::put('medicine-categories/{id}', [MedicineCategoryController::class, 'update'])->name('medicine-categories.update')->whereNumber('id')->middleware('permission:master.medicines');
                Route::delete('medicine-categories/{id}', [MedicineCategoryController::class, 'destroy'])->name('medicine-categories.destroy')->whereNumber('id')->middleware('permission:master.medicines');

                Route::get('medicine-types', [MedicineTypeController::class, 'index'])->name('medicine-types.index')->middleware('permission:master.medicines');
                Route::post('medicine-types', [MedicineTypeController::class, 'store'])->name('medicine-types.store')->middleware('permission:master.medicines');
                Route::put('medicine-types/{id}', [MedicineTypeController::class, 'update'])->name('medicine-types.update')->whereNumber('id')->middleware('permission:master.medicines');
                Route::delete('medicine-types/{id}', [MedicineTypeController::class, 'destroy'])->name('medicine-types.destroy')->whereNumber('id')->middleware('permission:master.medicines');

                Route::get('medicine-dosages', [MedicineDosageController::class, 'index'])->name('medicine-dosages.index')->middleware('permission:master.medicines');
                Route::post('medicine-dosages', [MedicineDosageController::class, 'store'])->name('medicine-dosages.store')->middleware('permission:master.medicines');
                Route::put('medicine-dosages/{id}', [MedicineDosageController::class, 'update'])->name('medicine-dosages.update')->whereNumber('id')->middleware('permission:master.medicines');
                Route::delete('medicine-dosages/{id}', [MedicineDosageController::class, 'destroy'])->name('medicine-dosages.destroy')->whereNumber('id')->middleware('permission:master.medicines');

                Route::get('medicine-instructions', [MedicineInstructionController::class, 'index'])->name('medicine_instructions.index')->middleware('permission:master.medicines');
                Route::post('medicine-instructions', [MedicineInstructionController::class, 'store'])->name('medicine_instructions.store')->middleware('permission:master.medicines');
                Route::put('medicine-instructions/{id}', [MedicineInstructionController::class, 'update'])->name('medicine_instructions.update')->whereNumber('id')->middleware('permission:master.medicines');
                Route::delete('medicine-instructions/{id}', [MedicineInstructionController::class, 'destroy'])->name('medicine_instructions.destroy')->whereNumber('id')->middleware('permission:master.medicines');

                Route::prefix('medicine-groups')->name('medicine-groups.')->middleware('permission:master.medicines')->group(function () {
                    Route::get('/', [MedicineGroupController::class, 'index'])->name('index');
                    Route::get('/create', [MedicineGroupController::class, 'create'])->name('create');
                    Route::post('/', [MedicineGroupController::class, 'store'])->name('store');
                    Route::get('/{medicine_group}', [MedicineGroupController::class, 'show'])->name('show');
                    Route::get('/{medicine_group}/edit', [MedicineGroupController::class, 'edit'])->name('edit');
                    Route::put('/{medicine_group}', [MedicineGroupController::class, 'update'])->name('update');
                    Route::delete('/{medicine_group}', [MedicineGroupController::class, 'destroy'])->name('destroy');
                });

                // ============================================================
                // Reports (Phase 6 me implement hoga)
                // ============================================================
                Route::prefix('reports')->name('reports.')->group(function () {
                    Route::get('/', [ReportController::class, 'index'])->name('index')->middleware('permission:reports.view');
                    Route::get('/export/excel', [ReportController::class, 'exportExcel'])->name('export.excel')->middleware('permission:reports.export');
                    Route::get('/export/pdf', [ReportController::class, 'exportPdf'])->name('export.pdf')->middleware('permission:reports.export');
                });

                // ============================================================
                // Masters Management — Basic & Detail (Phase 6)
                // ============================================================
                Route::prefix('masters')->name('masters.')->group(function () {
                    Route::get('/', [BasicMasterController::class, 'landing'])->name('index')->middleware('permission:master.case_types|master.eye_exam');

                    // Basic Masters: cases, locations, referrers, durations
                    Route::prefix('basic')->name('basic.')
                        ->middleware('permission:master.case_types')
                        ->group(function () {
                            Route::get('{type}', [BasicMasterController::class, 'index'])->name('index');
                            Route::post('{type}', [BasicMasterController::class, 'store'])->middleware('permission:master.case_types')->name('store');
                            // AJAX create for masters from forms (e.g. add city inline)
                            Route::post('{type}/ajax', [BasicMasterController::class, 'ajaxStore'])
                                ->withoutMiddleware('permission:master.case_types')
                                ->name('ajax.store');
                            Route::put('{type}/{id}', [BasicMasterController::class, 'update'])->middleware('permission:master.case_types')->name('update')->whereNumber('id');
                            Route::delete('{type}/{id}', [BasicMasterController::class, 'destroy'])->middleware('permission:master.case_types')->name('destroy')->whereNumber('id');
                        });

                    // Detail (Eye-Exam) Masters: vn, pnvn, sph_cyl, axis, complaints, etc.
                    Route::prefix('detail')->name('detail.')
                        ->middleware('permission:master.eye_exam')
                        ->group(function () {
                            Route::get('{type}', [DetailMasterController::class, 'index'])->name('index');
                            Route::post('{type}', [DetailMasterController::class, 'store'])->middleware('permission:master.eye_exam')->name('store');
                            Route::post('{type}/sync-by-diagnosis', [DetailMasterController::class, 'syncByDiagnosis'])->middleware('permission:master.eye_exam')->name('sync-by-diagnosis');
                            Route::put('{type}/{id}', [DetailMasterController::class, 'update'])->middleware('permission:master.eye_exam')->name('update')->whereNumber('id');
                            Route::post('{type}/{id}/toggle-favourite', [DetailMasterController::class, 'toggleFavourite'])->middleware('permission:master.eye_exam')->name('toggle-favourite')->whereNumber('id');
                            Route::delete('{type}/{id}', [DetailMasterController::class, 'destroy'])->middleware('permission:master.eye_exam')->name('destroy')->whereNumber('id');
                        });

                    Route::prefix('ot')->name('ot.')->middleware('role:admin')->group(function () {
                        Route::get('lens-options', [OtLensOptionController::class, 'index'])->name('lens-options.index');
                        Route::post('lens-options', [OtLensOptionController::class, 'store'])->name('lens-options.store');
                        Route::put('lens-options/{id}', [OtLensOptionController::class, 'update'])->name('lens-options.update')->whereNumber('id');
                        Route::delete('lens-options/{id}', [OtLensOptionController::class, 'destroy'])->name('lens-options.destroy')->whereNumber('id');

                        Route::get('slots', [OtSlotController::class, 'index'])->name('slots.index');
                        Route::post('slots', [OtSlotController::class, 'store'])->name('slots.store');
                        Route::put('slots/{id}', [OtSlotController::class, 'update'])->name('slots.update')->whereNumber('id');
                        Route::delete('slots/{id}', [OtSlotController::class, 'destroy'])->name('slots.destroy')->whereNumber('id');

                        Route::get('types', [OtTypeController::class, 'index'])->name('types.index');
                        Route::post('types', [OtTypeController::class, 'store'])->name('types.store');
                        Route::put('types/{id}', [OtTypeController::class, 'update'])->name('types.update')->whereNumber('id');
                        Route::delete('types/{id}', [OtTypeController::class, 'destroy'])->name('types.destroy')->whereNumber('id');

                        Route::get('surgery-types', [OtSurgeryTypeController::class, 'index'])->name('surgery-types.index');
                        Route::post('surgery-types', [OtSurgeryTypeController::class, 'store'])->name('surgery-types.store');
                        Route::put('surgery-types/{id}', [OtSurgeryTypeController::class, 'update'])->name('surgery-types.update')->whereNumber('id');
                        Route::delete('surgery-types/{id}', [OtSurgeryTypeController::class, 'destroy'])->name('surgery-types.destroy')->whereNumber('id');

                        Route::get('charge-heads', [OtChargeHeadController::class, 'index'])->name('charge-heads.index');
                        Route::post('charge-heads', [OtChargeHeadController::class, 'store'])->name('charge-heads.store');
                        Route::put('charge-heads/{id}', [OtChargeHeadController::class, 'update'])->name('charge-heads.update')->whereNumber('id');
                        Route::delete('charge-heads/{id}', [OtChargeHeadController::class, 'destroy'])->name('charge-heads.destroy')->whereNumber('id');
                    });
                });

                // ============================================================
                // Settings
                // ============================================================
                Route::prefix('settings')->name('settings.')->group(function () {
                    Route::get('/', [HospitalSettingController::class, 'index'])->name('index')->middleware('permission:settings.hospital');
                    Route::put('/', [HospitalSettingController::class, 'update'])->name('update')->middleware('permission:settings.hospital');
                });

                // ============================================================
                // Roles & Permissions (Hospital Admin)
                // ============================================================
                Route::prefix('roles')->name('roles.')->middleware('permission:master.roles')->group(function () {
                    Route::get('/', [RoleController::class, 'index'])->name('index');
                    Route::get('/create', [RoleController::class, 'create'])->name('create')->middleware('permission:master.roles');
                    Route::post('/', [RoleController::class, 'store'])->name('store')->middleware('permission:master.roles');
                    Route::get('/{id}/edit', [RoleController::class, 'edit'])->whereNumber('id')->name('edit')->middleware('permission:master.roles');
                    Route::put('/{id}', [RoleController::class, 'update'])->whereNumber('id')->name('update')->middleware('permission:master.roles');
                    Route::delete('/{id}', [RoleController::class, 'destroy'])->whereNumber('id')->name('destroy')->middleware('permission:master.roles');
                });

                // ============================================================
                // User Management (Hospital Admin)
                // ============================================================
                Route::prefix('users')->name('users.')->group(function () {
                    Route::get('/', [HospitalUserController::class, 'index'])->name('index')->middleware('permission:master.doctors|master.receptions|master.ot_staff');
                    Route::get('/create', [HospitalUserController::class, 'create'])->name('create')->middleware('permission:master.doctors|master.receptions|master.ot_staff');
                    Route::post('/', [HospitalUserController::class, 'store'])->name('store')->middleware('permission:master.doctors|master.receptions|master.ot_staff');
                    Route::get('/{id}/edit', [HospitalUserController::class, 'edit'])->whereNumber('id')->name('edit')->middleware('permission:master.doctors|master.receptions|master.ot_staff');
                    Route::put('/{id}', [HospitalUserController::class, 'update'])->whereNumber('id')->name('update')->middleware('permission:master.doctors|master.receptions|master.ot_staff');
                    Route::delete('/{id}', [HospitalUserController::class, 'destroy'])->whereNumber('id')->name('destroy')->middleware('permission:master.doctors|master.receptions|master.ot_staff');
                });

                // ============================================================
                // OT Module Routes — Phase 9A Receptionist + Booking Flow
                // ============================================================
                Route::prefix('ot')->name('ot.')->middleware(['auth.hospital'])->group(function () {
                    Route::get('/', function () {
                        return redirect()->route('hospital.ot.dashboard', ['slug' => request()->route('slug')]);
                    })->name('index')->middleware('permission:ot.patient.list');

                    Route::get('/ward', [OtAccountantController::class, 'wardIndex'])
                        ->name('ward.index')
                        ->middleware('permission:ot.ward.entry');

                    Route::post('/ward/{booking}/ready', [OtAccountantController::class, 'markReadyForOt'])
                        ->name('ward.ready')
                        ->whereNumber('booking')
                        ->middleware('permission:ot.ward.entry');

                    Route::get('/dashboard', [OtReceptionistController::class, 'dashboard'])
                        ->name('dashboard')
                        ->middleware('permission:ot.patient.list');

                    Route::get('/bookings', [OtBookingController::class, 'index'])
                        ->name('bookings.index')
                        ->middleware('permission:ot.patient.list');

                    Route::get('/bookings/create', [OtBookingController::class, 'create'])
                        ->name('bookings.create')
                        ->middleware('permission:ot.booking.create');

                    Route::post('/bookings', [OtBookingController::class, 'store'])
                        ->name('bookings.store')
                        ->middleware('permission:ot.booking.create');

                    Route::prefix('accountant')->middleware('permission:ot.payment.record')->group(function () {
                        Route::get('/dashboard', [OtAccountantController::class, 'dashboard'])
                            ->name('accountant.dashboard');

                        Route::get('/payments/{bookingId}/create', [OtAccountantController::class, 'createPayment'])
                            ->name('payments.create')
                            ->whereNumber('bookingId');

                        Route::post('/payments/{bookingId}', [OtAccountantController::class, 'storePayment'])
                            ->name('payments.store')
                            ->whereNumber('bookingId');
                    });

                    Route::prefix('doctor')->middleware('permission:ot.surgery.record')->group(function () {
                        Route::get('/dashboard', [OtDoctorController::class, 'dashboard'])
                            ->name('doctor.dashboard');

                        Route::get('/surgery/{bookingId}/create', [OtDoctorController::class, 'createSurgery'])
                            ->name('surgery.create')
                            ->whereNumber('bookingId');

                        Route::post('/surgery/{bookingId}', [OtDoctorController::class, 'storeSurgery'])
                            ->name('surgery.store')
                            ->whereNumber('bookingId');
                    });

                    Route::prefix('assistant')->middleware('permission:ot.lens.record|ot.lens.implant|ot.patient.list')->group(function () {
                        Route::get('/dashboard', [OtAssistantController::class, 'dashboard'])
                            ->name('assistant.dashboard');

                        Route::get('/lens/{bookingId}/edit', [OtAssistantController::class, 'editLens'])
                            ->name('assistant.lens.edit')
                            ->whereNumber('bookingId');

                        Route::post('/lens/{bookingId}/store', [OtAssistantController::class, 'storeLens'])
                            ->name('assistant.lens.store')
                            ->whereNumber('bookingId');
                    });

                    Route::prefix('billing')->middleware('permission:ot.billing.manage')->group(function () {
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
