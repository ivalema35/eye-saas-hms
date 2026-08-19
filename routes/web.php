<?php

/**
 * web.php — Public Platform + Super Admin Routes
 *
 * Ye routes publicly accessible hain ya superadmin guard se protect hain.
 * Hospital-specific routes yahan NAHI hain — wo hospital.php me hain.
 *
 * URL Structure:
 *   hmssaas.com/           → Landing page
 *   hmssaas.com/pricing    → Pricing page
 *   hmssaas.com/register   → Hospital registration
 *   hmssaas.com/superadmin → Super Admin panel
 *
 * RULE: Subdomain routing NAHI — sirf path-based routing.
 */

use App\Http\Controllers\Auth\SuperAdminAuthController;
use App\Http\Controllers\Platform\LandingController;
use App\Http\Controllers\Platform\RegisterController;
use App\Http\Controllers\Platform\UnifiedLoginController;
use App\Http\Controllers\Platform\WebhookController;
use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\NotificationController;
use App\Http\Controllers\SuperAdmin\PaymentController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\ProfileController;
use App\Http\Controllers\SuperAdmin\SettingsController;
use App\Http\Controllers\SuperAdmin\SubscriptionController;
use App\Http\Controllers\SuperAdmin\SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\DiagnosisMasterController;
use App\Http\Controllers\SuperAdmin\LocationMasterController;
use App\Http\Controllers\SuperAdmin\MedicineMasterController;
use App\Http\Controllers\SuperAdmin\TimezoneMasterController;
use App\Http\Controllers\SuperAdmin\TenantController;
use Illuminate\Support\Facades\Route;

// ====================================================================
// Public Platform Pages (koi auth nahi)
// ====================================================================

// Landing page
Route::get('/', [LandingController::class, 'index'])->name('home');

// Pricing page
Route::get('/pricing', [LandingController::class, 'pricing'])->name('pricing');

// Hospital registration
Route::get('/register', [RegisterController::class, 'show'])->name('register.show');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
Route::get('/register/pending/{slug}', [RegisterController::class, 'pending'])->name('register.pending');

// Hospital code availability check (AJAX, rate limited)
Route::get('/check-code', [RegisterController::class, 'checkCode'])
    ->name('check-code')
    ->middleware('throttle:30,1');

// Slug availability check (AJAX, rate limited)
Route::get('/check-slug', [RegisterController::class, 'checkSlug'])
    ->name('check-slug')
    ->middleware('throttle:30,1');

// Location cascade AJAX for public registration form
Route::get('/location/states', [RegisterController::class, 'getStates'])->name('location.states')->middleware('throttle:60,1');
Route::get('/location/districts', [RegisterController::class, 'getDistricts'])->name('location.districts')->middleware('throttle:60,1');
Route::get('/location/cities', [RegisterController::class, 'getCities'])->name('location.cities')->middleware('throttle:60,1');

// Location create AJAX — registration form can add new entries to master tables
Route::post('/location/create-country', [RegisterController::class, 'createCountry'])->name('location.create-country')->middleware('throttle:20,1');
Route::post('/location/create-state', [RegisterController::class, 'createState'])->name('location.create-state')->middleware('throttle:20,1');
Route::post('/location/create-district', [RegisterController::class, 'createDistrict'])->name('location.create-district')->middleware('throttle:20,1');
Route::post('/location/create-city', [RegisterController::class, 'createCity'])->name('location.create-city')->middleware('throttle:20,1');

// Unified Hospital Login — single page for all staff
Route::get('/login', [UnifiedLoginController::class, 'show'])->name('login');
Route::post('/login', [UnifiedLoginController::class, 'login'])->name('login.post')->middleware('throttle:10,1');

// Razorpay Webhook (CSRF exempt — added in VerifyCsrfToken)
Route::post('/webhooks/razorpay', [WebhookController::class, 'handle'])
    ->name('webhooks.razorpay');

// ====================================================================
// Super Admin Routes (prefix: /superadmin)
// ====================================================================

Route::prefix('superadmin')->name('superadmin.')->group(function () {

    // Super Admin Login — no auth required
    Route::get('/login', [SuperAdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [SuperAdminAuthController::class, 'login'])->name('login.post');

    // Authenticated superadmin routes
    Route::middleware(['auth:superadmin'])->group(function () {

        Route::post('/logout', [SuperAdminAuthController::class, 'logout'])->name('logout');

        // Dashboard
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])
            ->name('dashboard');

        // Hospital (Tenant) Management
        Route::resource('hospitals', TenantController::class)
            ->parameters(['hospitals' => 'tenant']);
        Route::post('hospitals/{tenant}/activate', [TenantController::class, 'activate'])
            ->name('hospitals.activate');
        Route::post('hospitals/{tenant}/suspend', [TenantController::class, 'suspend'])
            ->name('hospitals.suspend');
        Route::post('hospitals/{tenant}/extend', [TenantController::class, 'extend'])
            ->name('hospitals.extend');
        Route::post('hospitals/{tenant}/reactivate', [TenantController::class, 'reactivate'])
            ->name('hospitals.reactivate');
        Route::post('hospitals/{tenant}/approve', [TenantController::class, 'approve'])
            ->name('hospitals.approve');
        Route::post('hospitals/{tenant}/reject', [TenantController::class, 'reject'])
            ->name('hospitals.reject');
        Route::post('hospitals/{tenant}/reseed-masters', [TenantController::class, 'reseedMasters'])
            ->name('hospitals.reseed-masters');


        // Payments
        Route::get('/payments', [PaymentController::class, 'index'])
            ->name('payments.index');
        Route::post('/payments/offline', [PaymentController::class, 'storeOffline'])
            ->name('payments.offline');
        Route::get('/payments/{payment}/invoice', [PaymentController::class, 'downloadInvoice'])
            ->name('payments.invoice');

        // Audit Logs
        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');

        // Notification Center
        Route::get('/notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::post('/notifications/send', [NotificationController::class, 'send'])
            ->name('notifications.send');

        // Subscription Management
        Route::get('/subscriptions', [SubscriptionController::class, 'index'])
            ->name('subscriptions.index');

        // Platform Settings
        Route::get('/settings', [SettingsController::class, 'index'])
            ->name('settings');
        Route::put('/settings', [SettingsController::class, 'update'])
            ->name('settings.update');

        // SuperAdmin Profile
        Route::get('/profile', [ProfileController::class, 'show'])
            ->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])
            ->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
            ->name('profile.password');

        // Plan Management
        Route::resource('plans', PlanController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::post('/plans/country-price', [PlanController::class, 'saveCountryPrice'])->name('plans.country-price.save');
        Route::delete('/plans/country-price', [PlanController::class, 'deleteCountryPrice'])->name('plans.country-price.delete');

        // Timezone Master — view only
        Route::get('/timezones', [TimezoneMasterController::class, 'index'])->name('timezones.index');

        // Location Master — index (all 4 tabs)
        Route::get('/locations', [LocationMasterController::class, 'index'])->name('locations.index');
        Route::post('/locations/import', [LocationMasterController::class, 'import'])->name('locations.import');

        // AJAX cascade dropdowns
        Route::get('/locations/ajax/states', [LocationMasterController::class, 'ajaxStates'])->name('locations.ajax.states');
        Route::get('/locations/ajax/districts', [LocationMasterController::class, 'ajaxDistricts'])->name('locations.ajax.districts');

        // Countries
        Route::post('/locations/countries', [LocationMasterController::class, 'storeCountry'])->name('locations.countries.store');
        Route::put('/locations/countries/{country}', [LocationMasterController::class, 'updateCountry'])->name('locations.countries.update');
        Route::delete('/locations/countries/{country}', [LocationMasterController::class, 'destroyCountry'])->name('locations.countries.destroy');
        Route::patch('/locations/countries/{country}/toggle', [LocationMasterController::class, 'toggleCountry'])->name('locations.countries.toggle');

        // States
        Route::post('/locations/states', [LocationMasterController::class, 'storeState'])->name('locations.states.store');
        Route::put('/locations/states/{state}', [LocationMasterController::class, 'updateState'])->name('locations.states.update');
        Route::delete('/locations/states/{state}', [LocationMasterController::class, 'destroyState'])->name('locations.states.destroy');
        Route::patch('/locations/states/{state}/toggle', [LocationMasterController::class, 'toggleState'])->name('locations.states.toggle');

        // Districts
        Route::post('/locations/districts', [LocationMasterController::class, 'storeDistrict'])->name('locations.districts.store');
        Route::put('/locations/districts/{district}', [LocationMasterController::class, 'updateDistrict'])->name('locations.districts.update');
        Route::delete('/locations/districts/{district}', [LocationMasterController::class, 'destroyDistrict'])->name('locations.districts.destroy');
        Route::patch('/locations/districts/{district}/toggle', [LocationMasterController::class, 'toggleDistrict'])->name('locations.districts.toggle');

        // Cities
        Route::post('/locations/cities', [LocationMasterController::class, 'storeCity'])->name('locations.cities.store');
        Route::put('/locations/cities/{city}', [LocationMasterController::class, 'updateCity'])->name('locations.cities.update');
        Route::delete('/locations/cities/{city}', [LocationMasterController::class, 'destroyCity'])->name('locations.cities.destroy');
        Route::patch('/locations/cities/{city}/toggle', [LocationMasterController::class, 'toggleCity'])->name('locations.cities.toggle');

        // Medicine Master — global catalog (no medicine groups)
        Route::get('/medicine-master', [MedicineMasterController::class, 'index'])->name('medicine-master.index');

        // Dosages
        Route::post('/medicine-master/dosages', [MedicineMasterController::class, 'storeDosage'])->name('medicine-master.dosages.store');
        Route::put('/medicine-master/dosages/{dosage}', [MedicineMasterController::class, 'updateDosage'])->name('medicine-master.dosages.update');
        Route::delete('/medicine-master/dosages/{dosage}', [MedicineMasterController::class, 'destroyDosage'])->name('medicine-master.dosages.destroy');
        Route::patch('/medicine-master/dosages/{dosage}/toggle', [MedicineMasterController::class, 'toggleDosage'])->name('medicine-master.dosages.toggle');

        // Medicine Types
        Route::post('/medicine-master/types', [MedicineMasterController::class, 'storeType'])->name('medicine-master.types.store');
        Route::put('/medicine-master/types/{type}', [MedicineMasterController::class, 'updateType'])->name('medicine-master.types.update');
        Route::delete('/medicine-master/types/{type}', [MedicineMasterController::class, 'destroyType'])->name('medicine-master.types.destroy');
        Route::patch('/medicine-master/types/{type}/toggle', [MedicineMasterController::class, 'toggleType'])->name('medicine-master.types.toggle');

        // Medicine Categories
        Route::post('/medicine-master/categories', [MedicineMasterController::class, 'storeCategory'])->name('medicine-master.categories.store');
        Route::put('/medicine-master/categories/{category}', [MedicineMasterController::class, 'updateCategory'])->name('medicine-master.categories.update');
        Route::delete('/medicine-master/categories/{category}', [MedicineMasterController::class, 'destroyCategory'])->name('medicine-master.categories.destroy');
        Route::patch('/medicine-master/categories/{category}/toggle', [MedicineMasterController::class, 'toggleCategory'])->name('medicine-master.categories.toggle');

        // Medicine Routes (root of administration)
        Route::post('/medicine-master/routes', [MedicineMasterController::class, 'storeRoute'])->name('medicine-master.routes.store');
        Route::put('/medicine-master/routes/{route}', [MedicineMasterController::class, 'updateRoute'])->name('medicine-master.routes.update');
        Route::delete('/medicine-master/routes/{route}', [MedicineMasterController::class, 'destroyRoute'])->name('medicine-master.routes.destroy');
        Route::patch('/medicine-master/routes/{route}/toggle', [MedicineMasterController::class, 'toggleRoute'])->name('medicine-master.routes.toggle');

        // Medicines
        Route::post('/medicine-master/medicines', [MedicineMasterController::class, 'storeMedicine'])->name('medicine-master.medicines.store');
        Route::put('/medicine-master/medicines/{medicine}', [MedicineMasterController::class, 'updateMedicine'])->name('medicine-master.medicines.update');
        Route::delete('/medicine-master/medicines/{medicine}', [MedicineMasterController::class, 'destroyMedicine'])->name('medicine-master.medicines.destroy');
        Route::patch('/medicine-master/medicines/{medicine}/toggle', [MedicineMasterController::class, 'toggleMedicine'])->name('medicine-master.medicines.toggle');
        Route::post('/medicine-master/medicines/import', [MedicineMasterController::class, 'importMedicines'])->name('medicine-master.medicines.import');
        Route::get('/medicine-master/medicines/sample', [MedicineMasterController::class, 'downloadSampleMedicine'])->name('medicine-master.medicines.sample');

        // Diagnosis Master — global catalog, pushed down into every hospital's own tbl_master_diagnosis
        Route::get('/diagnosis-master', [DiagnosisMasterController::class, 'index'])->name('diagnosis-master.index');
        Route::post('/diagnosis-master', [DiagnosisMasterController::class, 'store'])->name('diagnosis-master.store');
        Route::put('/diagnosis-master/{diagnosis}', [DiagnosisMasterController::class, 'update'])->name('diagnosis-master.update');
        Route::delete('/diagnosis-master/{diagnosis}', [DiagnosisMasterController::class, 'destroy'])->name('diagnosis-master.destroy');
        Route::patch('/diagnosis-master/{diagnosis}/toggle', [DiagnosisMasterController::class, 'toggle'])->name('diagnosis-master.toggle');
    });
});

// Hospital routes — path-based slug routing
require __DIR__ . '/hospital.php';

Route::get('/{slug}/ajax/hospital-details/{id}', [App\Http\Controllers\Hospital\Dashboard\DashboardController::class, 'getHospitalDetails'])
    ->name('hospital.ajax.details');