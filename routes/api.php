<?php

/**
 * api.php — REST API Routes (Path-Based Multi-Tenant)
 *
 * URL Pattern: hmssaas.com/api/v1/{slug}/...
 * Authentication: Laravel Sanctum Token
 *
 * IMPORTANT: Ye routes mobile/external API access ke liye hain.
 * Har request me Authorization: Bearer {token} header required hai.
 *
 * API Response Format:
 *   { "success": true, "data": {...}, "message": "..." }
 *
 * Platform (slug-less) endpoints: /api/v1/super/...
 */

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExamApiController;
use App\Http\Controllers\Api\MasterApiController;
use App\Http\Controllers\Api\MastersApiController;
use App\Http\Controllers\Api\ProfileApiController;
use App\Http\Controllers\Api\SettingsApiController;
use App\Http\Controllers\Api\OtApiController;
use App\Http\Controllers\Api\PatientApiController;
use App\Http\Controllers\Api\ClinicalQueueApiController;
use App\Http\Controllers\Api\MedicineApiController;
use App\Http\Controllers\Api\MedicineGroupApiController;
use App\Http\Controllers\Api\MedicineMasterApiController;
use App\Http\Controllers\Api\ReportsApiController;
use App\Http\Controllers\Api\PatientHistoryApiController;
use App\Http\Controllers\Api\ShareHistoryApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\FocApiController;
use App\Http\Controllers\Api\RoleApiController;
use App\Http\Controllers\Api\DoctorDashboardApiController;
use App\Http\Controllers\Api\SuperAdmin\TenantApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ====================================================================
// Health Check — no auth required
// ====================================================================
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'status' => 'ok',
        'version' => '1.0.0',
        'time' => now()->toISOString(),
    ]);
});

// ====================================================================
// API v1
// ====================================================================
Route::prefix('v1')->name('api.v1.')->group(function () {

    // ----------------------------------------------------------------
    // Platform SuperAdmin API (No slug needed)
    // ----------------------------------------------------------------
    Route::prefix('super')
        ->name('super.')
        ->middleware('auth:sanctum')
        ->group(function () {

            Route::get('/tenants', [TenantApiController::class, 'index'])->name('tenants.index');
            Route::get('/tenants/{id}', [TenantApiController::class, 'show'])->name('tenants.show');

        });

    // ----------------------------------------------------------------
    // Public — no slug, no auth needed
    // ----------------------------------------------------------------
    Route::get('/find-hospital', [AuthController::class, 'findHospital'])->name('find.hospital');

    // ----------------------------------------------------------------
    // Hospital Tenant API (Slug se tenant identify hoga)
    // ----------------------------------------------------------------
    Route::prefix('{slug}')
        ->name('hospital.')
        ->middleware(['identify.tenant', 'set.tenant.scope'])
        ->group(function () {

            // Auth — no token required
            Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
            Route::post('/auth/logout', [AuthController::class, 'logout'])
                ->middleware('auth:sanctum')
                ->name('auth.logout');

            // Authenticated endpoints
            Route::middleware(['auth:sanctum', 'subscription.active'])
                ->group(function () {

                    // Dashboard
                    Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard'])
                        ->name('admin.dashboard');
                    Route::get('/dashboard/doctor', [DoctorDashboardApiController::class, 'dashboard'])
                        ->name('dashboard.doctor')
                        ->middleware('permission:opd.exam.primary|opd.exam.secondary');

                    // Current user
                    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

                    // My Profile (self-edit)
                    Route::get('profile', [ProfileApiController::class, 'profileShow']);
                    Route::put('profile', [ProfileApiController::class, 'profileUpdate']);

                    // Hospital Settings
                    Route::get('settings', [SettingsApiController::class, 'settingsShow'])
                        ->middleware('permission:settings.hospital');
                    Route::put('settings', [SettingsApiController::class, 'settingsUpdate'])
                        ->middleware('permission:settings.hospital');
                    Route::post('settings/logo', [SettingsApiController::class, 'logoUpload'])
                        ->middleware('permission:settings.hospital');
                    Route::post('settings/change-password', [SettingsApiController::class, 'changePassword']);

                    // Patients
                    Route::get('patients/next-mrd', [PatientApiController::class, 'nextMrd'])
                        ->name('patients.next-mrd');
                    Route::get('patients/search-by-contact', [PatientApiController::class, 'searchByContact'])
                        ->name('patients.search-by-contact');
                    Route::get('patients', [PatientApiController::class, 'index'])
                        ->name('patients.index')
                        ->middleware('permission:opd.patient.view');
                    Route::get('patients/{patient}', [PatientApiController::class, 'show'])
                        ->name('patients.show')
                        ->middleware('permission:opd.patient.view');
                    Route::get('patients/{patient}/history', [PatientHistoryApiController::class, 'history'])
                        ->name('patients.history')
                        ->middleware('permission:opd.exam.history');
                    Route::post('patients', [PatientApiController::class, 'store'])
                        ->name('patients.store')
                        ->middleware('permission:opd.patient.register');
                    Route::post('patients/phone', [PatientApiController::class, 'storePhone'])
                        ->name('patients.store-phone')
                        ->middleware('permission:opd.patient.register');
                    Route::put('patients/{patient}', [PatientApiController::class, 'update'])
                        ->name('patients.update')
                        ->middleware('permission:opd.patient.edit');
                    Route::delete('patients/{patient}', [PatientApiController::class, 'destroy'])
                        ->name('patients.destroy')
                        ->middleware('permission:opd.patient.delete');
                    Route::post('patients/{patient}/checkin', [PatientApiController::class, 'checkin'])
                        ->name('patients.checkin')
                        ->middleware('permission:opd.patient.register');

                    // Location cascade — for Settings location pickers
                    Route::get('masters/location/countries',  [SettingsApiController::class, 'locationCountries']);
                    Route::get('masters/location/states',     [SettingsApiController::class, 'locationStates']);
                    Route::get('masters/location/districts',  [SettingsApiController::class, 'locationDistricts']);
                    Route::get('masters/location/cities',     [SettingsApiController::class, 'locationCities']);
                    Route::get('masters/location/timezones',  [SettingsApiController::class, 'locationTimezones']);

                    // Masters (dropdowns for forms)
                    Route::get('masters/cases', [MastersApiController::class, 'cases'])->name('masters.cases');
                    Route::get('masters/doctors', [MastersApiController::class, 'doctors'])->name('masters.doctors');
                    Route::get('masters/locations', [MastersApiController::class, 'locations'])->name('masters.locations');
                    Route::post('masters/locations', [MastersApiController::class, 'storeLocation'])->name('masters.locations.store');
                    Route::get('masters/slots', [MastersApiController::class, 'slots'])->name('masters.slots');
                    Route::get('masters/referrers', [MastersApiController::class, 'referrers'])->name('masters.referrers');

                    // Masters — detail CRUD (eye exam masters)
                    Route::get('masters/detail/{type}',                        [MasterApiController::class, 'detailIndex']);
                    Route::post('masters/detail/{type}',                       [MasterApiController::class, 'detailStore']);
                    Route::put('masters/detail/{type}/{id}',                   [MasterApiController::class, 'detailUpdate']);
                    Route::delete('masters/detail/{type}/{id}',                [MasterApiController::class, 'detailDestroy']);
                    Route::post('masters/detail/{type}/{id}/toggle-favourite', [MasterApiController::class, 'detailToggleFavourite']);

                    // Masters — Case Types CRUD
                    Route::get('masters/case-types',      [MasterApiController::class, 'caseTypeIndex']);
                    Route::post('masters/case-types',     [MasterApiController::class, 'caseTypeStore']);
                    Route::put('masters/case-types/{id}', [MasterApiController::class, 'caseTypeUpdate']);
                    Route::delete('masters/case-types/{id}', [MasterApiController::class, 'caseTypeDestroy']);

                    // Masters — Referrers CRUD (separate from dropdown GET masters/referrers)
                    Route::get('masters/referrers-crud',      [MasterApiController::class, 'referrerIndex']);
                    Route::post('masters/referrers-crud',     [MasterApiController::class, 'referrerStore']);
                    Route::put('masters/referrers-crud/{id}', [MasterApiController::class, 'referrerUpdate']);
                    Route::delete('masters/referrers-crud/{id}', [MasterApiController::class, 'referrerDestroy']);

                    // Masters — OT Slots CRUD
                    Route::get('masters/ot-slots',      [MasterApiController::class, 'otSlotIndex']);
                    Route::post('masters/ot-slots',     [MasterApiController::class, 'otSlotStore']);
                    Route::put('masters/ot-slots/{id}', [MasterApiController::class, 'otSlotUpdate']);
                    Route::delete('masters/ot-slots/{id}', [MasterApiController::class, 'otSlotDestroy']);

                    // Masters — OT Charge Heads CRUD
                    Route::get('masters/ot-charge-heads',      [MasterApiController::class, 'otChargeHeadIndex']);
                    Route::post('masters/ot-charge-heads',     [MasterApiController::class, 'otChargeHeadStore']);
                    Route::put('masters/ot-charge-heads/{id}', [MasterApiController::class, 'otChargeHeadUpdate']);
                    Route::delete('masters/ot-charge-heads/{id}', [MasterApiController::class, 'otChargeHeadDestroy']);

                    // Masters — OT Surgery Types CRUD
                    Route::get('masters/ot-types-list',             [MasterApiController::class, 'otTypesList']);
                    Route::get('masters/ot-surgery-types',          [MasterApiController::class, 'otSurgeryTypeIndex']);
                    Route::post('masters/ot-surgery-types',         [MasterApiController::class, 'otSurgeryTypeStore']);
                    Route::put('masters/ot-surgery-types/{id}',     [MasterApiController::class, 'otSurgeryTypeUpdate']);
                    Route::delete('masters/ot-surgery-types/{id}',  [MasterApiController::class, 'otSurgeryTypeDestroy']);

                    // Examinations
                    Route::get('exams/primary/{patientId}', [ExamApiController::class, 'showPrimary'])
                        ->name('exams.primary')
                        ->middleware('permission:opd.exam.primary');
                    Route::post('exams/primary/{patientId}', [ExamApiController::class, 'savePrimary'])
                        ->name('exams.primary.save')
                        ->middleware('permission:opd.exam.primary');
                    Route::get('exams/secondary/{patientId}', [ExamApiController::class, 'showSecondary'])
                        ->name('exams.secondary')
                        ->middleware('permission:opd.exam.secondary');
                    Route::post('exams/secondary/{patientId}', [ExamApiController::class, 'saveSecondary'])
                        ->name('exams.secondary.save')
                        ->middleware('permission:opd.exam.secondary');

                    // OT
                    Route::get('ot/bookings', [OtApiController::class, 'bookings'])
                        ->name('ot.bookings')
                        ->middleware('permission:ot.patient.list');
                    Route::post('ot/bookings', [OtApiController::class, 'book'])
                        ->name('ot.book')
                        ->middleware('permission:ot.booking.create');
                    Route::put('ot/bookings/{id}/status', [OtApiController::class, 'updateStatus'])
                        ->name('ot.status')
                        ->middleware('permission:ot.booking.modify');

                    // Clinical Queue
                    Route::get('clinical-queue', [ClinicalQueueApiController::class, 'index'])
                        ->name('clinical-queue');

                    // Medicines
                    Route::prefix('medicines')->name('medicines.')->group(function () {
                        Route::get('/', [MedicineApiController::class, 'index'])->name('index');
                        Route::post('/', [MedicineApiController::class, 'store'])->name('store');
                        Route::put('/{id}', [MedicineApiController::class, 'update'])->name('update');
                        Route::delete('/{id}', [MedicineApiController::class, 'destroy'])->name('destroy');
                    });

                    // Medicine Groups
                    Route::prefix('medicine-groups')->name('medicine-groups.')->group(function () {
                        Route::get('/', [MedicineGroupApiController::class, 'index'])->name('index');
                        Route::get('/form-data', [MedicineGroupApiController::class, 'formData'])->name('form-data');
                        Route::get('/{id}', [MedicineGroupApiController::class, 'show'])->name('show')->where('id', '[0-9]+');
                        Route::post('/', [MedicineGroupApiController::class, 'store'])->name('store');
                        Route::put('/{id}', [MedicineGroupApiController::class, 'update'])->name('update')->where('id', '[0-9]+');
                        Route::delete('/{id}', [MedicineGroupApiController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
                    });

                    // Medicine Masters (dosages, types, categories, routes)
                    Route::prefix('medicine-dosages')->name('medicine-dosages.')->group(function () {
                        Route::get('/', [MedicineMasterApiController::class, 'dosages'])->name('index');
                        Route::post('/', [MedicineMasterApiController::class, 'storeDosage'])->name('store');
                        Route::put('/{id}', [MedicineMasterApiController::class, 'updateDosage'])->name('update');
                        Route::delete('/{id}', [MedicineMasterApiController::class, 'destroyDosage'])->name('destroy');
                    });
                    Route::prefix('medicine-types')->name('medicine-types.')->group(function () {
                        Route::get('/', [MedicineMasterApiController::class, 'types'])->name('index');
                        Route::post('/', [MedicineMasterApiController::class, 'storeType'])->name('store');
                        Route::put('/{id}', [MedicineMasterApiController::class, 'updateType'])->name('update');
                        Route::delete('/{id}', [MedicineMasterApiController::class, 'destroyType'])->name('destroy');
                    });
                    Route::prefix('medicine-categories')->name('medicine-categories.')->group(function () {
                        Route::get('/', [MedicineMasterApiController::class, 'categories'])->name('index');
                        Route::post('/', [MedicineMasterApiController::class, 'storeCategory'])->name('store');
                        Route::put('/{id}', [MedicineMasterApiController::class, 'updateCategory'])->name('update');
                        Route::delete('/{id}', [MedicineMasterApiController::class, 'destroyCategory'])->name('destroy');
                    });
                    Route::prefix('medicine-routes')->name('medicine-routes.')->group(function () {
                        Route::get('/', [MedicineMasterApiController::class, 'medicineRoutes'])->name('index');
                        Route::post('/', [MedicineMasterApiController::class, 'storeMedicineRoute'])->name('store');
                        Route::put('/{id}', [MedicineMasterApiController::class, 'updateMedicineRoute'])->name('update');
                        Route::delete('/{id}', [MedicineMasterApiController::class, 'destroyMedicineRoute'])->name('destroy');
                    });

                    // Reports
                    Route::prefix('reports')->name('reports.')->group(function () {
                        Route::get('/', [ReportsApiController::class, 'index'])->name('index');
                        Route::get('filter-data', [ReportsApiController::class, 'filterData'])->name('filter-data');
                        Route::get('export/excel', [ReportsApiController::class, 'exportExcel'])->name('export.excel');
                        Route::get('export/pdf', [ReportsApiController::class, 'exportPdf'])->name('export.pdf');
                    });

                    // Config — Users (CRUD)
                    Route::get('config/users/form-data', [UserApiController::class, 'formData']);
                    Route::get('config/users', [UserApiController::class, 'index'])
                        ->middleware('permission:master.doctors|master.receptions|master.ot_staff');
                    Route::post('config/users', [UserApiController::class, 'store']);
                    Route::get('config/users/{id}', [UserApiController::class, 'show'])
                        ->middleware('permission:master.doctors|master.receptions|master.ot_staff')
                        ->where('id', '[0-9]+');
                    Route::post('config/users/{id}', [UserApiController::class, 'update'])
                        ->where('id', '[0-9]+');
                    Route::delete('config/users/{id}', [UserApiController::class, 'destroy'])
                        ->middleware('permission:master.doctors|master.receptions|master.ot_staff')
                        ->where('id', '[0-9]+');
                    Route::patch('config/users/{id}/toggle-status', [UserApiController::class, 'toggleStatus'])
                        ->where('id', '[0-9]+');

                    // FOC (Free of Charge)
                    Route::prefix('foc')->name('foc.')->group(function () {
                        Route::get('/', [FocApiController::class, 'index'])->name('index')
                            ->middleware('permission:opd.foc.create|opd.foc.accept');
                        Route::post('/', [FocApiController::class, 'store'])->name('store')
                            ->middleware('permission:opd.foc.create');
                        Route::post('/{id}/accept', [FocApiController::class, 'accept'])->name('accept')
                            ->middleware('permission:opd.foc.accept')
                            ->where('id', '[0-9]+');
                        Route::post('/{id}/reject', [FocApiController::class, 'reject'])->name('reject')
                            ->middleware('permission:opd.foc.accept')
                            ->where('id', '[0-9]+');
                    });

                    // Roles & Permissions
                    Route::prefix('config/roles')->name('config.roles.')->group(function () {
                        Route::get('/permissions', [RoleApiController::class, 'permissions'])->name('permissions')
                            ->middleware('permission:master.roles');
                        Route::get('/', [RoleApiController::class, 'index'])->name('index')
                            ->middleware('permission:master.roles');
                        Route::post('/', [RoleApiController::class, 'store'])->name('store')
                            ->middleware('permission:master.roles');
                        Route::get('/{id}', [RoleApiController::class, 'show'])->name('show')
                            ->middleware('permission:master.roles')
                            ->where('id', '[0-9]+');
                        Route::put('/{id}', [RoleApiController::class, 'update'])->name('update')
                            ->middleware('permission:master.roles')
                            ->where('id', '[0-9]+');
                        Route::delete('/{id}', [RoleApiController::class, 'destroy'])->name('destroy')
                            ->middleware('permission:master.roles')
                            ->where('id', '[0-9]+');
                    });

                    // Share History
                    Route::prefix('share-history')->name('share-history.')->group(function () {
                        Route::get('patients', [ShareHistoryApiController::class, 'patients'])
                            ->name('patients');
                        Route::get('hospitals', [ShareHistoryApiController::class, 'hospitals'])
                            ->name('hospitals');
                        Route::get('hospitals/{hospitalId}', [ShareHistoryApiController::class, 'hospitalDetail'])
                            ->name('hospitals.detail');
                        Route::get('connections', [ShareHistoryApiController::class, 'connections'])
                            ->name('connections');
                        Route::post('requests', [ShareHistoryApiController::class, 'sendRequest'])
                            ->name('requests.send');
                        Route::post('requests/{requestId}/accept', [ShareHistoryApiController::class, 'acceptRequest'])
                            ->name('requests.accept');
                        Route::delete('requests/{requestId}', [ShareHistoryApiController::class, 'removeRequest'])
                            ->name('requests.remove');
                        Route::get('partner/{partnerTenantId}/patients', [ShareHistoryApiController::class, 'partnerPatients'])
                            ->name('partner.patients');
                    });

                }); // end authenticated

        }); // end {slug}

}); // end v1
