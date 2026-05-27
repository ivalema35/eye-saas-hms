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

// Slug availability check (AJAX, rate limited)
Route::get('/check-slug', [RegisterController::class, 'checkSlug'])
    ->name('check-slug')
    ->middleware('throttle:30,1');

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

        // Plan Management (stub routes — controller built in T2.1)
        Route::resource('plans', PlanController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    });
});

// Hospital routes — path-based slug routing
require __DIR__.'/hospital.php';
