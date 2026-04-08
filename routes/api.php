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
use App\Http\Controllers\Api\PatientApiController;
use App\Http\Controllers\Api\ExamApiController;
use App\Http\Controllers\Api\OtApiController;
use App\Http\Controllers\Api\SuperAdmin\TenantApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ====================================================================
// Health Check — no auth required
// ====================================================================
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'status'  => 'ok',
        'version' => '1.0.0',
        'time'    => now()->toISOString(),
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

            // Current user
            Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

            // Patients
               Route::get('patients', [PatientApiController::class, 'index'])
                    ->name('patients.index')
                    ->middleware('permission:opd.patient.view');
               Route::get('patients/{patient}', [PatientApiController::class, 'show'])
                    ->name('patients.show')
                    ->middleware('permission:opd.patient.view');
               Route::post('patients', [PatientApiController::class, 'store'])
                    ->name('patients.store')
                    ->middleware('permission:opd.patient.register');
               Route::put('patients/{patient}', [PatientApiController::class, 'update'])
                    ->name('patients.update')
                    ->middleware('permission:opd.patient.edit');
               Route::delete('patients/{patient}', [PatientApiController::class, 'destroy'])
                    ->name('patients.destroy')
                    ->middleware('permission:opd.patient.delete');

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

        }); // end authenticated

    }); // end {slug}

}); // end v1
