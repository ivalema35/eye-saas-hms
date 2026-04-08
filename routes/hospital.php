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
use App\Http\Controllers\Hospital\Patient\PatientController;
use App\Http\Controllers\Hospital\Patient\PatientHistoryController;
use App\Http\Controllers\Hospital\Examination\PrimaryExamController;
use App\Http\Controllers\Hospital\Examination\SecondaryExamController;
use App\Http\Controllers\Hospital\Foc\FocController;
use App\Http\Controllers\Hospital\Medicine\MedicineController;
use App\Http\Controllers\Hospital\Medicine\MedicineDosageController;
use App\Http\Controllers\Hospital\Medicine\MedicineGroupController;
use App\Http\Controllers\Hospital\Medicine\MedicineTypeController;
use App\Http\Controllers\Hospital\Report\ReportController;
use App\Http\Controllers\Hospital\Master\BasicMasterController;
use App\Http\Controllers\Hospital\Master\DetailMasterController;
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
          });

      Route::get('patient-history', [PatientHistoryController::class, 'index'])->name('patients.history')->middleware('permission:opd.exam.history');


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

        // AJAX helpers for exam form
        Route::post('ajax/complaint', [PrimaryExamController::class, 'ajaxAddComplaint'])
             ->name('ajax.complaint.add')
             ->middleware('permission:opd.exam.primary');
        Route::post('ajax/diagnosis', [PrimaryExamController::class, 'ajaxAddDiagnosis'])
             ->name('ajax.diagnosis.add')
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

          Route::get('medicine-types', [MedicineTypeController::class, 'index'])->name('medicine-types.index')->middleware('permission:master.medicines');
          Route::post('medicine-types', [MedicineTypeController::class, 'store'])->name('medicine-types.store')->middleware('permission:master.medicines');
          Route::put('medicine-types/{id}', [MedicineTypeController::class, 'update'])->name('medicine-types.update')->whereNumber('id')->middleware('permission:master.medicines');
          Route::delete('medicine-types/{id}', [MedicineTypeController::class, 'destroy'])->name('medicine-types.destroy')->whereNumber('id')->middleware('permission:master.medicines');

      Route::get('medicine-dosages', [MedicineDosageController::class, 'index'])->name('medicine-dosages.index')->middleware('permission:master.medicines');
      Route::post('medicine-dosages', [MedicineDosageController::class, 'store'])->name('medicine-dosages.store')->middleware('permission:master.medicines');
      Route::put('medicine-dosages/{id}', [MedicineDosageController::class, 'update'])->name('medicine-dosages.update')->whereNumber('id')->middleware('permission:master.medicines');
      Route::delete('medicine-dosages/{id}', [MedicineDosageController::class, 'destroy'])->name('medicine-dosages.destroy')->whereNumber('id')->middleware('permission:master.medicines');

      Route::get('medicine-instructions', [\App\Http\Controllers\Hospital\Medicine\MedicineInstructionController::class, 'index'])->name('medicine_instructions.index')->middleware('permission:master.medicines');
      Route::post('medicine-instructions', [\App\Http\Controllers\Hospital\Medicine\MedicineInstructionController::class, 'store'])->name('medicine_instructions.store')->middleware('permission:master.medicines');
      Route::put('medicine-instructions/{id}', [\App\Http\Controllers\Hospital\Medicine\MedicineInstructionController::class, 'update'])->name('medicine_instructions.update')->whereNumber('id')->middleware('permission:master.medicines');
      Route::delete('medicine-instructions/{id}', [\App\Http\Controllers\Hospital\Medicine\MedicineInstructionController::class, 'destroy'])->name('medicine_instructions.destroy')->whereNumber('id')->middleware('permission:master.medicines');

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
                     Route::get('{type}',         [BasicMasterController::class, 'index'])  ->name('index');
                          Route::post('{type}',        [BasicMasterController::class, 'store'])  ->middleware('permission:master.case_types')->name('store');
                          Route::put('{type}/{id}',    [BasicMasterController::class, 'update']) ->middleware('permission:master.case_types')->name('update')->whereNumber('id');
                          Route::delete('{type}/{id}', [BasicMasterController::class, 'destroy'])->middleware('permission:master.case_types')->name('destroy')->whereNumber('id');
                 });

            // Detail (Eye-Exam) Masters: vn, pnvn, sph_cyl, axis, complaints, etc.
            Route::prefix('detail')->name('detail.')
                     ->middleware('permission:master.eye_exam')
                 ->group(function () {
                     Route::get('{type}',         [DetailMasterController::class, 'index'])  ->name('index');
                          Route::post('{type}',        [DetailMasterController::class, 'store'])  ->middleware('permission:master.eye_exam')->name('store');
                          Route::put('{type}/{id}',    [DetailMasterController::class, 'update']) ->middleware('permission:master.eye_exam')->name('update')->whereNumber('id');
                          Route::delete('{type}/{id}', [DetailMasterController::class, 'destroy'])->middleware('permission:master.eye_exam')->name('destroy')->whereNumber('id');
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
        // OT Module Routes (Phase me implement honge — stubs)
        // ============================================================
        Route::prefix('ot')->name('ot.')->group(function () {
            Route::get('/', function () {
                return redirect()->route('hospital.dashboard', ['slug' => request()->segment(1)]);
               })->name('index')->middleware('permission:ot.patient.list');
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
