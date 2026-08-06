# Super Admin Module — Full Implementation Plan

**Flutter App:** `J:\all_folder_of_C_drive\eye_care_whole\eye_care_app`
**Backend:** `J:\laragon\www\eye_care_new_clone\eye-saas-hms`
**Companion docs:** `SUPER_ADMIN_APP_PRD.md` · `SUPER_ADMIN_APP_DESIGN.md`

> **Rule:** Only edit backend at `J:\laragon\www\eye_care_new_clone\eye-saas-hms`.  
> Never edit `J:\laragon\www\eye-saas-hms` or `J:\all_folder_of_C_drive\eye_care_whole\eye-saas-hms`.

---

## Current State — What Exists, What's Missing

### Backend gaps confirmed by code read:
| Gap | Where | Status |
|---|---|---|
| `PlatformAdmin` has no `HasApiTokens` | `app/Models/Platform/PlatformAdmin.php` | **Missing — blocks everything** |
| `superadmin` guard is `driver: session` | `config/auth.php` | **Must add Sanctum token provider** |
| No `/api/v1/super/auth/*` endpoints | `routes/api.php` | **Must build** |
| Existing `TenantApiController` checks `role->is_super` on hospital users — wrong model | `app/Http/Controllers/Api/SuperAdmin/TenantApiController.php` | **Must replace entirely** |
| `hospital_code` required in web validation but web form doesn't collect it — must auto-generate in API | `TenantController@store` | **Resolve: auto-generate server-side** |
| No `PlatformAdmin`-only middleware guard | — | **Must create** |
| Zero API endpoints for Dashboard / Payments / Subscriptions / Audit / Notifications / Settings / Plans / Profile / Locations / Medicines | — | **All new** |

### Flutter gaps confirmed:
| Gap | Where |
|---|---|
| No `platformApiUrl` on `AppConfig` | `lib/config/app_config.dart` |
| No `AppColors.resetToDefault()` — needed so hospital theme never bleeds into Platform screens | `lib/constants/app_colors.dart` |
| No `fl_chart` dependency | `pubspec.yaml` |
| No `platform_*` models, services, or screens anywhere | `lib/` |
| No "Platform Super Admin? Login here" link on `LoginScreen` | `lib/screens/login_screen.dart` |
| No `StatusBadge` widget | `lib/widgets/` |

---

## Phase-by-Phase Plan

---

## Phase 1 — Foundation: Auth on Both Ends + App Shell
**Deliverable:** A Super Admin can log in on their phone, see the shell (bottom nav + drawer), and log out. Everything else is stubbed. This is the minimal working skeleton all other phases bolt onto.

---

### Phase 1 — Backend Tasks

#### 1.1 Add `HasApiTokens` to `PlatformAdmin`

**File:** `app/Models/Platform/PlatformAdmin.php`

Add the trait and its import:
```php
use Laravel\Sanctum\HasApiTokens;
// ...
class PlatformAdmin extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;
    // rest unchanged
}
```

---

#### 1.2 Add Sanctum user provider for `platform_admins`

**File:** `config/auth.php`

Inside `providers` array, add:
```php
'platform_admins' => [
    'driver' => 'eloquent',
    'model'  => App\Models\Platform\PlatformAdmin::class,
],
```

Inside `guards` array, add (keep the existing `superadmin` session guard untouched — the web panel uses it):
```php
'platform_api' => [
    'driver'   => 'sanctum',
    'provider' => 'platform_admins',
],
```

> **Note:** The existing `sanctum` guard (for hospital users) stays as-is. Mobile hospital routes use `auth:sanctum`. The new platform routes will use `auth:platform_api`. Sanctum's `tokenCan()` checks still work; the guard name is just how we tell them apart.

---

#### 1.3 Create `EnsurePlatformAdmin` middleware

**New file:** `app/Http/Middleware/EnsurePlatformAdmin.php`

```php
<?php

namespace App\Http\Middleware;

use App\Models\Platform\PlatformAdmin;
use Closure;
use Illuminate\Http\Request;

class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!$request->user() instanceof PlatformAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }
        return $next($request);
    }
}
```

**Register in:** `bootstrap/app.php` (Laravel 11+ style) or `app/Http/Kernel.php` (Laravel 10 style).

```php
// bootstrap/app.php withMiddleware block:
$middleware->alias([
    'platform.admin' => \App\Http\Middleware\EnsurePlatformAdmin::class,
]);
```

---

#### 1.4 Create `PlatformAuthController`

**New file:** `app/Http/Controllers/Api/SuperAdmin/PlatformAuthController.php`

```php
<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Platform\PlatformAdmin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PlatformAuthController extends Controller
{
    // POST /api/v1/super/auth/login
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $admin = PlatformAdmin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        // Update last login metadata (closes PRD gap #5)
        $admin->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $token = $admin->createToken('mobile-platform')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data'    => [
                'token' => $token,
                'admin' => $admin,
            ],
        ]);
    }

    // POST /api/v1/super/auth/logout
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Logged out.']);
    }

    // GET /api/v1/super/auth/me
    public function me(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $request->user()]);
    }
}
```

---

#### 1.5 Register Platform API routes

**File:** `routes/api.php`

Replace the existing `super` group entirely:

```php
use App\Http\Controllers\Api\SuperAdmin\PlatformAuthController;
use App\Http\Controllers\Api\SuperAdmin\PlatformHospitalApiController;
use App\Http\Controllers\Api\SuperAdmin\PlatformDashboardApiController;
// (add more imports as each phase adds controllers)

Route::prefix('super')->name('super.')->group(function () {

    // ── Public: Auth ──────────────────────────────────────────────────
    Route::post('/auth/login',  [PlatformAuthController::class, 'login'])->name('auth.login');

    // ── Protected: require valid PlatformAdmin token ──────────────────
    Route::middleware(['auth:platform_api', 'platform.admin'])->group(function () {
        Route::post('/auth/logout', [PlatformAuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me',      [PlatformAuthController::class, 'me'])->name('auth.me');

        // Phase 2 routes added here — stub them as you go
    });
});
```

> The old `Route::get('/tenants', ...)` and `Route::get('/tenants/{id}', ...)` should be removed from this group (or left and eventually replaced by `PlatformHospitalApiController` in Phase 2 — since TenantApiController is wrong anyway, replacing them entirely in Phase 2 is fine, but they must NOT run under the new middleware yet or they'll break).

---

### Phase 1 — Flutter Tasks

#### 1.6 Add `platformApiUrl` to `AppConfig`

**File:** `lib/config/app_config.dart`

```dart
class AppConfig {
  AppConfig._();

  static const String apiBaseUrl = 'http://localhost:8080/api/v1';

  static String slug = '';
  static String hospitalName = 'Eye-SaaS HMS';

  static String get hospitalApiUrl => '$apiBaseUrl/$slug';

  // Platform Super Admin — no slug, platform-wide
  static const String platformApiUrl = '$apiBaseUrl/super';

  static const Duration requestTimeout = Duration(seconds: 30);
}
```

---

#### 1.7 Add `AppColors.resetToDefault()`

**File:** `lib/constants/app_colors.dart`

At the bottom of the `applyTheme()` method, add a companion reset. This protects Platform screens from a leftover hospital theme:

```dart
/// Resets overridable colors to their compiled defaults.
/// Call this on Platform login and Platform logout.
static void resetToDefault() {
  _primary      = const Color(0xFF1B4F72);
  _primaryLight = const Color(0xFF2471A3);
  _secondary    = const Color(0xFF2E86C1);
  _background   = const Color(0xFFEBF5FB);
  // Recompute all alpha variants
  _primaryA05 = _primary.withValues(alpha: 0.05);
  _primaryA08 = _primary.withValues(alpha: 0.08);
  _primaryA10 = _primary.withValues(alpha: 0.10);
  _primaryA12 = _primary.withValues(alpha: 0.12);
  _primaryA15 = _primary.withValues(alpha: 0.15);
  _primaryA20 = _primary.withValues(alpha: 0.20);
  _primaryA25 = _primary.withValues(alpha: 0.25);
  _primaryA30 = _primary.withValues(alpha: 0.30);
  _primaryA35 = _primary.withValues(alpha: 0.35);
  _primaryA40 = _primary.withValues(alpha: 0.40);
  _primaryA45 = _primary.withValues(alpha: 0.45);
  _primaryA50 = _primary.withValues(alpha: 0.50);
  _primaryA60 = _primary.withValues(alpha: 0.60);
  _primaryA70 = _primary.withValues(alpha: 0.70);
  _primaryA75 = _primary.withValues(alpha: 0.75);
}
```

> Note: Copy the exact alpha field names that already exist in `app_colors.dart`. The above list must match the private `_primaryAxx` fields as implemented in Phase 7 of `OPTIMIZATION_PRD.md`.

---

#### 1.8 Create `lib/models/platform_admin_models.dart`

```dart
class PlatformAdmin {
  final int id;
  final String name;
  final String email;
  final String role; // 'super_admin' | 'support'
  final DateTime? lastLoginAt;
  final String? lastLoginIp;

  const PlatformAdmin({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    this.lastLoginAt,
    this.lastLoginIp,
  });

  factory PlatformAdmin.fromJson(Map<String, dynamic> json) => PlatformAdmin(
        id:           json['id'] as int,
        name:         json['name'] as String,
        email:        json['email'] as String,
        role:         json['role'] as String,
        lastLoginAt:  json['last_login_at'] != null
            ? DateTime.tryParse(json['last_login_at'] as String)
            : null,
        lastLoginIp:  json['last_login_ip'] as String?,
      );

  Map<String, dynamic> toJson() => {
        'id':            id,
        'name':          name,
        'email':         email,
        'role':          role,
        'last_login_at': lastLoginAt?.toIso8601String(),
        'last_login_ip': lastLoginIp,
      };

  String get initials {
    final parts = name.trim().split(' ');
    if (parts.length >= 2) return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
    return name.isNotEmpty ? name[0].toUpperCase() : 'SA';
  }
}

class PlatformLoginResult {
  final bool success;
  final String message;
  final String? token;
  final PlatformAdmin? admin;

  const PlatformLoginResult({
    required this.success,
    required this.message,
    this.token,
    this.admin,
  });
}
```

---

#### 1.9 Create `lib/services/platform_auth_service.dart`

This file also defines the `PlatformAuthenticatedService` mixin that all other platform services will use.

```dart
import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/app_config.dart';
import '../constants/app_colors.dart';
import '../models/platform_admin_models.dart';

// ── Mixin ─────────────────────────────────────────────────────────────────────
// All platform services use this mixin for auth headers.
// Mirror of AuthenticatedService in base_service.dart, but reads platform token.

mixin PlatformAuthenticatedService {
  Future<Map<String, String>> get headers async {
    final token = await PlatformAuthService.instance.getStoredToken();
    return {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }
}

// ── Service ───────────────────────────────────────────────────────────────────

class PlatformAuthService {
  PlatformAuthService._();
  static final PlatformAuthService instance = PlatformAuthService._();

  static const _tokenKey = 'platform_admin_token';
  static const _adminKey = 'cached_platform_admin';

  String? _tokenCache;

  Map<String, String> get _baseHeaders => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      };

  Map<String, String> _authHeaders(String token) => {
        ..._baseHeaders,
        'Authorization': 'Bearer $token',
      };

  // ── Login ──────────────────────────────────────────────────────────

  Future<PlatformLoginResult> login(String email, String password) async {
    try {
      final uri = Uri.parse('${AppConfig.platformApiUrl}/auth/login');
      debugPrint('[PlatformAuth] POST $uri');

      final response = await http
          .post(uri,
              headers: _baseHeaders,
              body: jsonEncode({'email': email, 'password': password}))
          .timeout(AppConfig.requestTimeout);

      debugPrint('[PlatformAuth] Status: ${response.statusCode}');

      final body = jsonDecode(response.body) as Map<String, dynamic>;

      if (response.statusCode == 200 && body['success'] == true) {
        final data    = body['data'] as Map<String, dynamic>;
        final token   = data['token'] as String;
        final admin   = PlatformAdmin.fromJson(data['admin'] as Map<String, dynamic>);

        await _saveToken(token);
        await _saveAdmin(admin);
        AppColors.resetToDefault(); // ensure no stale hospital theme

        return PlatformLoginResult(success: true, message: 'Login successful.', token: token, admin: admin);
      }

      return PlatformLoginResult(
        success: false,
        message: (body['message'] as String?) ?? 'Invalid credentials.',
      );
    } on TimeoutException {
      return const PlatformLoginResult(success: false, message: 'Request timed out.');
    } on SocketException {
      return const PlatformLoginResult(success: false, message: 'No internet connection.');
    } catch (e) {
      debugPrint('[PlatformAuth] ERROR: $e');
      return const PlatformLoginResult(success: false, message: 'Something went wrong.');
    }
  }

  // ── Refresh session ────────────────────────────────────────────────

  Future<PlatformAdmin?> refreshSession() async {
    final token = await getStoredToken();
    if (token == null) return null;

    try {
      final uri = Uri.parse('${AppConfig.platformApiUrl}/auth/me');
      final response = await http
          .get(uri, headers: _authHeaders(token))
          .timeout(AppConfig.requestTimeout);

      if (response.statusCode == 200) {
        final body = jsonDecode(response.body) as Map<String, dynamic>;
        if (body['success'] == true) {
          final admin = PlatformAdmin.fromJson(body['data'] as Map<String, dynamic>);
          await _saveAdmin(admin);
          AppColors.resetToDefault();
          return admin;
        }
      }
      if (response.statusCode == 401) {
        await _clearAll();
        return null;
      }
    } catch (_) {}

    // Network error — return cached admin for offline grace
    return getStoredAdmin();
  }

  // ── Logout ─────────────────────────────────────────────────────────

  Future<void> logout() async {
    try {
      final token = await getStoredToken();
      if (token != null) {
        final uri = Uri.parse('${AppConfig.platformApiUrl}/auth/logout');
        await http
            .post(uri, headers: _authHeaders(token))
            .timeout(const Duration(seconds: 10));
      }
    } catch (_) {}
    await _clearAll();
    AppColors.resetToDefault(); // safety reset on logout too
  }

  // ── Storage ────────────────────────────────────────────────────────

  Future<String?> getStoredToken() async {
    if (_tokenCache != null) return _tokenCache;
    final prefs = await SharedPreferences.getInstance();
    _tokenCache = prefs.getString(_tokenKey);
    return _tokenCache;
  }

  Future<PlatformAdmin?> getStoredAdmin() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_adminKey);
    if (raw == null) return null;
    return PlatformAdmin.fromJson(jsonDecode(raw) as Map<String, dynamic>);
  }

  Future<bool> get hasSession async => (await getStoredToken()) != null;

  Future<void> _saveToken(String token) async {
    _tokenCache = token;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }

  Future<void> _saveAdmin(PlatformAdmin admin) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_adminKey, jsonEncode(admin.toJson()));
  }

  Future<void> _clearAll() async {
    _tokenCache = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
    await prefs.remove(_adminKey);
    // Do NOT touch auth_token / cached_user / hospital_slug — those are hospital sessions
  }
}
```

---

#### 1.10 Create `lib/widgets/status_badge.dart`

Formalizes the tinted-pill pattern used for hospital status, payment status, notification status:

```dart
import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';

class StatusBadge extends StatelessWidget {
  final String label;
  final Color color;
  final double fontSize;

  const StatusBadge({
    super.key,
    required this.label,
    required this.color,
    this.fontSize = 10,
  });

  // Convenience constructors for hospital status
  factory StatusBadge.hospitalStatus(String status) {
    final color = switch (status) {
      'active'    => AppColors.green,
      'trial'     => AppColors.secondary,
      'grace'     => AppColors.orange,
      'suspended' => AppColors.red,
      _           => AppColors.textDisabled,  // 'inactive'
    };
    return StatusBadge(label: status, color: color);
  }

  factory StatusBadge.paymentStatus(String status) {
    final color = switch (status) {
      'success' => AppColors.green,
      'pending' => AppColors.orange,
      _         => AppColors.red, // 'failed'
    };
    return StatusBadge(label: status, color: color);
  }

  factory StatusBadge.notificationStatus(String status) {
    final color = switch (status) {
      'sent'    => AppColors.green,
      'pending' => AppColors.orange,
      _         => AppColors.red, // 'failed'
    };
    return StatusBadge(label: status, color: color);
  }

  factory StatusBadge.subscriptionStatus(String status) {
    final color = switch (status) {
      'active' => AppColors.green,
      _        => AppColors.red, // 'expired', 'cancelled'
    };
    return StatusBadge(label: status, color: color);
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(AppRadius.full),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: fontSize,
          fontWeight: FontWeight.w700,
          color: color,
        ),
      ),
    );
  }
}
```

---

#### 1.11 Create `lib/screens/platform_login_screen.dart`

Full login screen with email + password fields, error shake animation, back link to hospital login. Pattern: same visual family as `login_screen.dart` (white card, `AppColors.background`, entrance animation) but simpler (no hospital-discovery step).

**Key details:**
- `SingleChildScrollView` → `Column(mainAxisAlignment: center)` → white rounded card (`AppRadius.xl`, soft shadow)
- Logo + "SUPER ADMIN" caption
- Email `TextFormField`, Password `TextFormField` (obscure toggle)
- Navy submit button — calls `PlatformAuthService.instance.login()`
- On success: `AppColors.resetToDefault()` already called by the service, then `Navigator.pushReplacement → PlatformHomeScreen`
- On error: shake animation (same `AnimationController` pattern as `login_screen.dart`)
- Bottom TextButton: "← Back to Hospital Staff Login" → `Navigator.pop()`

**Shake pattern** (copy from `login_screen.dart`):
```dart
late final AnimationController _shakeCtrl = AnimationController(
  vsync: this, duration: const Duration(milliseconds: 500));
late final Animation<double> _shakeAnim = Tween<double>(begin: 0, end: 1)
    .animate(CurvedAnimation(parent: _shakeCtrl, curve: Curves.elasticOut));

// On error:
_shakeCtrl.forward(from: 0);

// In build:
AnimatedBuilder(
  animation: _shakeAnim,
  builder: (_, child) => Transform.translate(
    offset: Offset(sin(_shakeAnim.value * 3 * pi) * 8, 0),
    child: child,
  ),
  child: /* the card */,
)
```

---

#### 1.12 Create `lib/screens/platform_home_screen.dart`

The shell — mirrors `home_screen.dart` exactly in structure:
- `_currentIndex` int state, `PageController`
- Bottom nav: frosted-glass floating pill, 4 items (Dashboard, Hospitals, Billing, More)
- Drawer: same `AppDrawer` style but with Platform nav items
- 4 page slots: `PlatformDashboardScreen`, `PlatformHospitalsScreen`, `PlatformBillingScreen` (placeholder wrapping both payments/subscriptions), `SizedBox.shrink()` (More tab opens drawer, never navigates)

**Bottom nav items:**
```dart
const _navItems = [
  (Icons.grid_view_rounded,       'Dashboard'),
  (Icons.local_hospital_rounded,  'Hospitals'),
  (Icons.receipt_long_rounded,    'Billing'),
  (Icons.menu_rounded,            'More'),
];
```

**Drawer sections:**
- Header: navy block, initials avatar, "SUPER ADMIN" bold white, "PLATFORM CONSOLE" small caption
- Cluster 1: Audit Logs (`Icons.history_rounded`), Notifications (`Icons.campaign_rounded`)
- Cluster 2 (Masters): Plans (`Icons.workspace_premium_rounded`), Location Master (`Icons.public_rounded`), Medicine Master (`Icons.medication_rounded`)
- Cluster 3 (System): Settings (`Icons.settings_rounded`)
- Footer: white card (avatar + name + role pill + chevron → `PlatformProfileScreen`), outlined red "LOGOUT ACCOUNT" button

**`_onMoreNavigate(String label)` switch** (same pattern as `home_screen.dart`'s `_onDrawerNavigate`):
```dart
switch (label) {
  case 'Audit Logs':         Navigator.push(context, appRoute(PlatformAuditLogsScreen(...)));
  case 'Notifications':      Navigator.push(context, appRoute(PlatformNotificationsScreen(...)));
  case 'Plans':              Navigator.push(context, appRoute(PlatformPlansScreen(...)));
  case 'Location Master':    Navigator.push(context, appRoute(PlatformLocationMasterScreen(...)));
  case 'Medicine Master':    Navigator.push(context, appRoute(PlatformMedicineMasterScreen(...)));
  case 'Settings':           Navigator.push(context, appRoute(PlatformSettingsScreen(...)));
}
```

All screens except Dashboard, Hospitals, and Billing are Navigator.push (full-screen push), not tabs — same pattern as the hospital app's drawer navigation.

---

#### 1.13 Wire the entry point in `login_screen.dart`

At the bottom of the existing login card or below the form, add:

```dart
TextButton(
  onPressed: () => Navigator.push(context, appRoute(const PlatformLoginScreen())),
  child: const Text(
    'Platform Super Admin? Login here',
    style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
  ),
),
```

**Location:** After the existing "Login" button and before or below any "Forgot Password" or "Register" links that exist. Low visual weight, muted color — not to distract from the normal hospital staff login flow.

---

#### 1.14 Create stub screens for Phase 1 completion

These are minimal placeholders to satisfy the shell's import requirements. Each is a `Scaffold` with a navy AppBar and a centered "Coming Soon" text. They will be replaced in subsequent phases:

- `lib/screens/platform_dashboard_screen.dart`
- `lib/screens/platform_hospitals_screen.dart`
- `lib/screens/platform_billing_screen.dart` (wraps both payments + subscriptions in a segmented control — Phase 3 implements fully)
- `lib/screens/platform_audit_logs_screen.dart`
- `lib/screens/platform_notifications_screen.dart`
- `lib/screens/platform_plans_screen.dart`
- `lib/screens/platform_location_master_screen.dart`
- `lib/screens/platform_medicine_master_screen.dart`
- `lib/screens/platform_settings_screen.dart`
- `lib/screens/platform_profile_screen.dart`

---

### Phase 1 — Testing Checklist
- [ ] Backend: `POST /api/v1/super/auth/login` with seeded PlatformAdmin credentials returns token
- [ ] Backend: `GET /api/v1/super/auth/me` with valid token returns admin JSON
- [ ] Backend: `GET /api/v1/super/auth/me` with hospital token returns 401 (wrong model guard)
- [ ] Backend: `POST /api/v1/super/auth/logout` revokes token
- [ ] Flutter: "Platform Super Admin? Login here" link visible on login screen
- [ ] Flutter: Login with invalid creds shows shake + red error text
- [ ] Flutter: Login with valid creds navigates to PlatformHomeScreen
- [ ] Flutter: All 4 bottom nav tabs switch correctly; More tab opens drawer
- [ ] Flutter: Drawer logout clears `platform_admin_token` only, leaves `auth_token` untouched
- [ ] Flutter: Logging into a hospital account after Platform logout, and vice versa, works without collision

---

## Phase 2 — Dashboard + Hospitals (Core Value)
**Deliverable:** Full Hospital management (list, create, edit, detail, all lifecycle actions) + Dashboard stat cards. This is the highest-value feature set.

---

### Phase 2 — Backend Tasks

#### 2.1 `PlatformDashboardApiController`

**New file:** `app/Http/Controllers/Api/SuperAdmin/PlatformDashboardApiController.php`

Mirror `SuperAdminDashboardController@index` exactly, returning JSON instead of a view:

```php
// GET /api/v1/super/dashboard
public function index(): JsonResponse
{
    // All the same Carbon + Eloquent queries as the web controller:
    // totalHospitals, activeCount, trialCount, graceCount, suspendedCount,
    // inactiveCount, monthlyRevenue, expiringThisWeek,
    // revenueMonths[], revenueAmounts[],
    // regMonths[], regCounts[],
    // cycleMonthly, cycleQuarterly, cycleYearly,
    // recentHospitals (8 most recent, with id/name/slug/status/created_at)

    return response()->json([
        'success' => true,
        'data' => [
            'stats' => [
                'total_hospitals'  => $totalHospitals,
                'active'           => $activeCount,
                'trial'            => $trialCount,
                'grace'            => $graceCount,
                'suspended'        => $suspendedCount,
                'inactive'         => $inactiveCount,
                'monthly_revenue'  => (float) $monthlyRevenue,
                'this_month_revenue' => (float) $monthlyRevenue,  // web literal duplicate
                'expiring_this_week' => $expiringThisWeek,
            ],
            'revenue_trend' => [
                'months'  => $revenueMonths,
                'amounts' => $revenueAmounts,
            ],
            'registrations_trend' => [
                'months' => $regMonths,
                'counts' => $regCounts,
            ],
            'subscription_cycles' => [
                'monthly'   => $cycleMonthly,
                'quarterly' => $cycleQuarterly,
                'yearly'    => $cycleYearly,
            ],
            'recent_hospitals' => $recentHospitals->map(fn($t) => [
                'id'         => $t->id,
                'name'       => $t->name,
                'slug'       => $t->slug,
                'status'     => $t->status,
                'admin_name' => $t->admin_name,
                'city'       => $t->city,
                'created_at' => $t->created_at,
            ])->values(),
        ],
    ]);
}
```

---

#### 2.2 `PlatformHospitalApiController`

**New file:** `app/Http/Controllers/Api/SuperAdmin/PlatformHospitalApiController.php`

This replaces `TenantApiController`. Mirror all of `SuperAdmin\TenantController`'s methods returning JSON.

**`hospital_code` resolution (PRD gap §7.3 FR-PH-02a):** Auto-generate from the hospital name — take the first 3 alpha characters, uppercase, fallback to random if name is too short:

```php
private function generateHospitalCode(string $name): string
{
    $alpha = preg_replace('/[^a-zA-Z]/', '', $name);
    $base  = strtoupper(substr($alpha, 0, 3));
    if (strlen($base) < 3) $base = str_pad($base, 3, 'X');

    // Ensure uniqueness
    $code = $base;
    $i    = 1;
    while (Tenant::where('hospital_code', $code)->exists()) {
        $code = substr($base, 0, 2) . $i;
        $i++;
    }
    return $code;
}
```

**Methods to implement:**

| Method | Route | Description |
|---|---|---|
| `index` | `GET /hospitals` | List with search + status filter + paginate(20) |
| `show` | `GET /hospitals/{id}` | Detail with `subscriptions`, `payments` eager-loaded |
| `store` | `POST /hospitals` | Create — validate (same rules as web minus `hospital_code` — auto-generate it), call `TenantService::create()` |
| `update` | `PUT /hospitals/{id}` | Edit name/slug/admin_name/admin_phone/city/state. Block email edits. Log `AuditLog::hospital.updated` |
| `destroy` | `DELETE /hospitals/{id}` | Soft delete (archive). Log `AuditLog::hospital.archived` |
| `activate` | `POST /hospitals/{id}/activate` | Call `TenantService::activate()`. Log `AuditLog::hospital.activated` |
| `suspend` | `POST /hospitals/{id}/suspend` | Call `TenantService::suspend()`. Log `AuditLog::hospital.suspended` |
| `reactivate` | `POST /hospitals/{id}/reactivate` | Reactivate. Log `AuditLog::hospital.reactivated` |
| `extend` | `POST /hospitals/{id}/extend` | Validate `days` (1-90), extend grace. Log `AuditLog::hospital.grace.extended` |
| `reseedMasters` | `POST /hospitals/{id}/reseed-masters` | Dispatch `SeedTenantDefaultMasters` job. Log `AuditLog::hospital.masters.reseeded` |

**Response shape for each hospital record:**
```json
{
  "id": 1,
  "name": "Aakash Eye Hospital",
  "slug": "aakasheye",
  "hospital_code": "AAK",
  "admin_name": "Dr. Rahul Shah",
  "admin_email": "rahul@aakasheye.com",
  "admin_phone": "9876543210",
  "city": "Surat",
  "state": "Gujarat",
  "status": "active",
  "trial_ends_at": "2026-07-30T00:00:00.000000Z",
  "is_setup_done": true,
  "setup_completed_at": "...",
  "created_at": "...",
  "subscriptions": [...],
  "payments": [...]
}
```

---

#### 2.3 Register Phase 2 routes

Add to the `middleware(['auth:platform_api', 'platform.admin'])` group in `routes/api.php`:

```php
Route::get('/dashboard', [PlatformDashboardApiController::class, 'index'])->name('dashboard');

Route::prefix('hospitals')->name('hospitals.')->group(function () {
    Route::get('/',                          [PlatformHospitalApiController::class, 'index'])->name('index');
    Route::post('/',                         [PlatformHospitalApiController::class, 'store'])->name('store');
    Route::get('/{id}',                      [PlatformHospitalApiController::class, 'show'])->name('show');
    Route::put('/{id}',                      [PlatformHospitalApiController::class, 'update'])->name('update');
    Route::delete('/{id}',                   [PlatformHospitalApiController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/activate',            [PlatformHospitalApiController::class, 'activate'])->name('activate');
    Route::post('/{id}/suspend',             [PlatformHospitalApiController::class, 'suspend'])->name('suspend');
    Route::post('/{id}/reactivate',          [PlatformHospitalApiController::class, 'reactivate'])->name('reactivate');
    Route::post('/{id}/extend',              [PlatformHospitalApiController::class, 'extend'])->name('extend');
    Route::post('/{id}/reseed-masters',      [PlatformHospitalApiController::class, 'reseedMasters'])->name('reseed');
});
```

---

### Phase 2 — Flutter Tasks

#### 2.4 Create `lib/models/platform_tenant_models.dart`

```dart
class TenantSummary {
  final int id;
  final String name;
  final String slug;
  final String? adminName;
  final String? adminEmail;
  final String? city;
  final String status;  // trial|active|grace|inactive|suspended
  final DateTime? trialEndsAt;
  final DateTime createdAt;

  // ... fromJson, toJson
}

class TenantDetail extends TenantSummary {
  final String? adminPhone;
  final String? state;
  final String? hospitalCode;
  final bool isSetupDone;
  final DateTime? setupCompletedAt;
  final List<SubscriptionItem> subscriptions;
  final List<PaymentItem> payments;

  // ... fromJson
}

class SubscriptionItem { // lightweight, for detail screen only
  final int id;
  final String cycle;
  final double price;
  final String status;
  final DateTime? startsAt;
  final DateTime? endsAt;
}

class PaymentItem { // lightweight, for detail screen only
  final int id;
  final double amount;
  final String cycle;
  final String method;
  final String status;
  final String? transactionId;
  final DateTime? paidAt;
}
```

---

#### 2.5 Create `lib/models/platform_dashboard_models.dart`

```dart
class PlatformDashboardData {
  final PlatformStats stats;
  final ChartSeries revenueTrend;       // bar chart data
  final ChartSeries registrationsTrend; // line chart data
  final CycleData subscriptionCycles;   // pie chart data
  final List<TenantSummary> recentHospitals;
}

class PlatformStats {
  final int totalHospitals;
  final int active, trial, grace, suspended, inactive;
  final double monthlyRevenue, thisMonthRevenue;
  final int expiringThisWeek;
}

class ChartSeries {
  final List<String> labels; // month names
  final List<double> values;
}

class CycleData {
  final int monthly, quarterly, yearly;
}
```

---

#### 2.6 Create `lib/services/platform_dashboard_service.dart`

```dart
import 'package:http/http.dart' as http;
import '../config/app_config.dart';
import '../models/platform_dashboard_models.dart';
import 'platform_auth_service.dart';

class PlatformDashboardService with PlatformAuthenticatedService {
  PlatformDashboardService._();
  static final PlatformDashboardService instance = PlatformDashboardService._();

  Future<PlatformDashboardData?> getDashboard() async {
    final resp = await http.get(
      Uri.parse('${AppConfig.platformApiUrl}/dashboard'),
      headers: await headers,
    ).timeout(AppConfig.requestTimeout);

    if (resp.statusCode == 200) {
      final body = jsonDecode(resp.body);
      if (body['success'] == true) {
        return PlatformDashboardData.fromJson(body['data']);
      }
    }
    return null;
  }
}
```

---

#### 2.7 Create `lib/services/platform_tenant_service.dart`

Methods:
- `Future<({List<TenantSummary> items, int total, int lastPage})> list({String? search, String? status, int page = 1})`
- `Future<TenantDetail?> getDetail(int id)`
- `Future<bool> create(Map<String, dynamic> data)` — returns success bool + error message
- `Future<bool> update(int id, Map<String, dynamic> data)`
- `Future<bool> delete(int id)` — archive
- `Future<bool> activate(int id)`
- `Future<bool> suspend(int id)`
- `Future<bool> reactivate(int id)`
- `Future<bool> extendGrace(int id, int days)`
- `Future<bool> reseedMasters(int id)`

Each method follows the same pattern as existing services: `http.get/post/put/delete`, headers from mixin, parse `{success, data, message}`.

---

#### 2.8 Implement `platform_dashboard_screen.dart` (replace stub)

Structure mirrors `dashboard_screen.dart`:

```
Scaffold(backgroundColor: AppColors.background)
  AppBar: navy, "Super Admin" / "Platform Control", hamburger, initials avatar
  RefreshIndicator
    SingleChildScrollView
      Column:
        _buildGreetingCard()         — gradient hero (primary→primaryLight)
        AppSectionHeader("Overview")
        _buildStatRow1()             — Total/Active/Trial/Grace (horizontal scroll)
        _buildStatRow2()             — MRR/This Month/Expiring (horizontal scroll)
        AppSectionHeader("Revenue Trend")
        _buildRevenueChart()         — Phase 6 stub: "Charts coming soon"
        AppSectionHeader("Hospital Status")
        _buildStatusChart()          — Phase 6 stub
        AppSectionHeader("New Registrations")
        _buildRegistrationsChart()   — Phase 6 stub
        AppSectionHeader("Subscription Cycles")
        _buildCyclesChart()          — Phase 6 stub
        AppSectionHeader("Recently Registered")
        _buildRecentHospitals()
```

**State management:** Same `_loading / _error / _data` triple as `dashboard_screen.dart`. Pull-to-refresh calls `_load()`. Skeleton in loading state.

---

#### 2.9 Implement `platform_hospitals_screen.dart` (replace stub)

```
Scaffold
  AppBar: "Hospitals" + total count subtitle
  Column:
    AppSearchBar (calls _onSearch with 400ms debounce)
    Filter chip row: All | Trial | Active | Grace | Suspended | Inactive
    Expanded:
      RefreshIndicator
        _loading? Skeleton : _error? AppErrorState : _items.isEmpty? AppEmptyState
          ListView.builder(itemCount: _items.length + 1)
            -- each item: PressScaleWrapper → soft-white card
               Hospital name bold, slug gray, admin+city small, StatusBadge top-right, chevron
            -- last item: AppPaginationBar
  FAB: "New Hospital" → PlatformHospitalFormScreen(mode: 'create')
```

---

#### 2.10 Implement `platform_hospital_detail_screen.dart` (replace stub)

```
Scaffold
  AppBar: hospital name, pencil-edit action → PlatformHospitalFormScreen(mode: 'edit', tenant: _tenant)
  SingleChildScrollView
    Column:
      _buildStatStrip()   — 4 compact tiles: Status badge / Trial Ends / Subscriptions / Total Paid
      _buildInfoCard()    — label/value rows: Name/Slug/Admin/Email/Phone/City/State/Registered On
      _buildQuickActions() — Wrap of pill-shaped OutlinedButtons (conditional on status):
                             Active → show Suspend, Extend Grace, Re-seed, Archive
                             Trial  → show Activate, Suspend, Re-seed, Archive
                             Grace  → show Activate, Extend Grace, Re-seed, Archive
                             Suspended → show Reactivate, Archive
                             Inactive  → show Activate, Archive
      OutlinedButton "Open Portal ↗" → launches hospital URL in external browser
      AppSectionHeader("Subscription History")
      _buildSubscriptionsCard() — stacked mini-rows
      AppSectionHeader("Payment History")
      _buildPaymentsCard()     — stacked mini-rows
```

**Lifecycle action pattern:**
```dart
Future<void> _confirmAndAct({
  required String title,
  required String body,
  required Color actionColor,
  required String actionLabel,
  required Future<bool> Function() action,
}) async {
  final confirmed = await showDialog<bool>(context: context, builder: (_) => AlertDialog(
    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
    title: Text(title, style: AppTextStyles.headingSmall),
    content: Text(body, style: AppTextStyles.bodyMedium),
    actions: [
      TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
      TextButton(
        onPressed: () => Navigator.pop(context, true),
        child: Text(actionLabel, style: TextStyle(color: actionColor, fontWeight: FontWeight.w700)),
      ),
    ],
  ));
  if (confirmed == true) {
    final success = await action();
    if (success && mounted) {
      showAppSnackBar(context, '$actionLabel successful.');
      _load(); // refresh
    }
  }
}
```

---

#### 2.11 Implement `platform_hospital_form_screen.dart` (replace stub)

**Mode:** `'create'` or `'edit'`. Passed as a constructor param.

**Create mode fields:**
- Hospital Name (auto-suggests slug as you type — `_slugCtrl.text = _toSlug(name)`)
- Slug (editable, shows "Available" green / "Taken" red based on slug)
- Admin Name, Admin Email, Admin Phone (10 digits)
- Password (min 8, obscure toggle)
- City, State (2 fields side by side)
- Plan (3 ChoiceChip: Monthly / Quarterly / Yearly)
- hospital_code is NOT shown — auto-generated server-side

**Edit mode:** Same minus Password and Plan. Email field disabled. Slug change shows orange warning banner.

**Slug auto-suggestion:**
```dart
String _toSlug(String name) =>
    name.toLowerCase().replaceAll(RegExp(r'[^a-z0-9]+'), '-')
        .replaceAll(RegExp(r'^-+|-+$'), '');
```

**Submit:** Calls `PlatformTenantService.instance.create(data)` or `.update(id, data)`. On success: pop and show snackbar.

---

### Phase 2 — Testing Checklist
- [ ] `GET /api/v1/super/dashboard` returns all stat fields and chart arrays
- [ ] `GET /api/v1/super/hospitals` returns paginated list with search + status filter working
- [ ] `POST /api/v1/super/hospitals` creates a hospital, auto-generates `hospital_code`, returns 201
- [ ] `POST /api/v1/super/hospitals/{id}/suspend` changes status to `suspended`, creates AuditLog
- [ ] Flutter: Hospital list shows 5 example hospitals with correct status badges
- [ ] Flutter: Create hospital form validates all fields client-side
- [ ] Flutter: Detail screen shows Quick Actions correct to the hospital's status
- [ ] Flutter: Suspend dialog appears, requires confirm, then refreshes detail on success

---

## Phase 3 — Billing (Payments + Subscriptions)
**Deliverable:** Full payments list with filters, offline payment recording, invoice PDF, and read-only subscriptions list.

---

### Phase 3 — Backend Tasks

#### 3.1 `PlatformPaymentApiController`

**New file:** `app/Http/Controllers/Api/SuperAdmin/PlatformPaymentApiController.php`

Mirror `SuperAdmin\PaymentController`. Methods:

**`index()`** — `GET /payments`
- Query params: `status` (success/pending/failed), `method` (online/offline), `from`/`to` (date range), `page`
- Eager-load `tenant:id,name,slug`
- Return 3 stat cards + paginated payment rows

**`storeOffline()`** — `POST /payments/offline`
Validation:
```php
$request->validate([
    'tenant_id'      => ['required', 'exists:tenants,id'],
    'amount'         => ['required', 'numeric', 'min:1', 'max:999999'],
    'cycle'          => ['required', 'in:monthly,quarterly,yearly'],
    'transaction_id' => ['nullable', 'string', 'max:100'],
    'notes'          => ['nullable', 'string', 'max:500'],
]);
```
- Create `Payment` record (method='offline', status='success', paid_at=now)
- Call `TenantService::activate()` for the tenant (same as web)
- Log `AuditLog::payment.offline.recorded`
- Return `{success: true, message: 'Payment recorded and hospital activated.'}`

**`downloadInvoice(int $id)`** — `GET /payments/{id}/invoice`
- Same logic as web: generate PDF if `invoice_path` is null, return base64-encoded PDF in JSON or a file response.
- Simplest approach: return the file as a `BinaryFileResponse` with `Content-Type: application/pdf` — the Flutter app's existing `pdf`/`printing` packages can handle the byte stream.

#### 3.2 `PlatformSubscriptionApiController`

**New file:** `app/Http/Controllers/Api/SuperAdmin/PlatformSubscriptionApiController.php`

**`index()`** — `GET /subscriptions`
- Eager-load `tenant:id,name,slug`
- 3 stat cards (total, active, expired) + paginated list
- Read-only — no write endpoints

#### 3.3 Register Phase 3 routes

```php
Route::prefix('payments')->name('payments.')->group(function () {
    Route::get('/',              [PlatformPaymentApiController::class, 'index'])->name('index');
    Route::post('/offline',      [PlatformPaymentApiController::class, 'storeOffline'])->name('offline');
    Route::get('/{id}/invoice',  [PlatformPaymentApiController::class, 'downloadInvoice'])->name('invoice');
});

Route::get('/subscriptions', [PlatformSubscriptionApiController::class, 'index'])->name('subscriptions.index');
```

---

### Phase 3 — Flutter Tasks

#### 3.4 Create `lib/models/platform_payment_models.dart` and `platform_subscription_models.dart`

**Payment:**
```dart
class PlatformPayment {
  final int id;
  final String tenantName, tenantSlug;
  final double amount;
  final String cycle, method, status;
  final String? transactionId, notes, invoicePath;
  final DateTime? paidAt;
}
```

**Subscription:**
```dart
class PlatformSubscription {
  final int id;
  final String tenantName, tenantSlug;
  final String cycle, status;
  final double price;
  final DateTime? startsAt, endsAt;
}
```

#### 3.5 Create services: `platform_payment_service.dart`, `platform_subscription_service.dart`

Each follows the same singleton + `PlatformAuthenticatedService` pattern.

#### 3.6 Implement `platform_billing_screen.dart` (replace stub)

Segmented control at the top (Payments / Subscriptions) that switches between the two list views in-place:

**Payments view:**
- 3 `AppStatCard`s (Total Revenue / This Month / Pending count)
- Filter chips: Success / Pending / Failed + Date Range button
- List of payment cards: hospital name, bold amount, cycle+method badges, StatusBadge, PDF icon
- FAB "Record Payment" → bottom sheet

**Record Payment bottom sheet:**
- Hospital searchable dropdown (all non-suspended tenants)
- Amount (number input, `₹` prefix)
- Billing Cycle (3 ChoiceChips)
- Transaction # (optional)
- Notes (optional, multiline)
- Light-blue info banner: "This will also activate the hospital if not already active."
- Navy "Record Payment" button

**Invoice PDF download:**
```dart
Future<void> _downloadInvoice(int paymentId) async {
  final bytes = await PlatformPaymentService.instance.getInvoiceBytes(paymentId);
  if (bytes == null) { showAppSnackBar(context, 'Failed to load invoice', isError: true); return; }
  await Printing.sharePdf(bytes: bytes, filename: 'invoice_$paymentId.pdf');
}
```

**Subscriptions view (read-only):**
- 3 AppStatCards (Total/Active/Expired)
- List of subscription cards: hospital, cycle badge, StatusBadge, date range, chevron → tap → PlatformHospitalDetailScreen

---

### Phase 3 — Testing Checklist
- [ ] `GET /api/v1/super/payments` returns stats + paginated list; filters work
- [ ] `POST /api/v1/super/payments/offline` records payment, activates hospital, creates audit log
- [ ] `GET /api/v1/super/payments/{id}/invoice` returns valid PDF bytes
- [ ] Flutter: Payment filter chips filter correctly
- [ ] Flutter: "Record Payment" form validates all fields
- [ ] Flutter: Submitting offline payment shows info banner and activates hospital
- [ ] Flutter: PDF icon taps open invoice via share sheet

---

## Phase 4 — Oversight: Audit Logs + Notifications
**Deliverable:** Read-only audit trail + broadcast notification composer and history.

---

### Phase 4 — Backend Tasks

#### 4.1 `PlatformAuditLogApiController`

**`index()`** — `GET /audit-logs`
- Query params: `action` (text search on `action` column), `tenant_id`, `from`/`to`, `page` (25/page)
- Eager-load `tenant:id,name,slug`
- Return paginated list: `id, action, description, tenant_name, tenant_slug, admin_id, admin_name, ip_address, old_values, new_values, created_at`

#### 4.2 `PlatformNotificationApiController`

**`index()`** — `GET /notifications`
- Last 50 `PlatformNotification` records, eager-load `tenant:id,name,slug`

**`send()`** — `POST /notifications/send`
```php
$request->validate([
    'subject'    => ['required', 'string', 'max:255'],
    'message'    => ['required', 'string', 'max:5000'],
    'recipients' => ['required', 'in:all,specific'],
    'tenant_ids' => ['required_if:recipients,specific', 'array'],
    'tenant_ids.*' => ['exists:tenants,id'],
]);
```
- Mirror `SuperAdmin\NotificationController@send` exactly
- For "all": get all non-suspended tenants with admin_email
- For "specific": load only the provided tenant_ids
- Send email to each (queue it), create `PlatformNotification` record per recipient
- Return `{success: true, message: 'Notification sent to N hospital(s).'}`

#### 4.3 Register Phase 4 routes

```php
Route::get('/audit-logs', [PlatformAuditLogApiController::class, 'index'])->name('audit-logs.index');

Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/',      [PlatformNotificationApiController::class, 'index'])->name('index');
    Route::post('/send', [PlatformNotificationApiController::class, 'send'])->name('send');
});
```

---

### Phase 4 — Flutter Tasks

#### 4.4 Create `lib/models/platform_audit_log_models.dart` and `platform_notification_models.dart`

**AuditLog:**
```dart
class PlatformAuditLog {
  final int id;
  final String action;
  final String description;
  final String? tenantName, tenantSlug;
  final String? adminName;
  final String? ipAddress;
  final dynamic oldValues, newValues;
  final DateTime createdAt;

  Color get actionColor {
    if (action.contains('created'))   return AppColors.green;
    if (action.contains('activated')) return AppColors.secondary;
    if (action.contains('suspended')) return AppColors.red;
    if (action.contains('archived'))  return AppColors.orange;
    if (action.contains('payment'))   return AppColors.green;
    return AppColors.textDisabled;
  }
}
```

**Notification:**
```dart
class PlatformNotification {
  final int id;
  final String? tenantName;
  final String subject;
  final String? recipientEmail;
  final String status; // sent|failed|pending
  final String? errorMessage;
  final DateTime? sentAt;
}
```

#### 4.5 Implement `platform_audit_logs_screen.dart` (replace stub)

- Navy AppBar with filter icon (opens filter bottom sheet: action search + hospital dropdown + date range)
- Paginated list of white cards:
  - `StatusBadge(label: log.action, color: log.actionColor)` at top
  - Description text
  - Row: hospital chip / "Platform" gray tag + admin name + monospace IP
  - Timestamp bottom-right
- `AppPaginationBar`

#### 4.6 Implement `platform_notifications_screen.dart` (replace stub)

**Segmented control:** Compose / History tabs.

**Compose view:**
- Subject TextFormField
- Message TextFormField (maxLines: 6, `${_message.length}/5000` counter below)
- Recipients: 2-chip toggle (All Hospitals / Specific Hospitals)
- If Specific: multi-select search widget — shows list of non-suspended tenants, tap to add/remove chips
- Live "Sending to: N hospital(s)" info banner (blue background)
- Confirm dialog before send: "This will email N hospital admin(s) immediately."
- Navy "Send Notification" button

**History view:**
- List of notification cards: hospital name, subject truncated, recipient email, StatusBadge, sent date

---

### Phase 4 — Testing Checklist
- [ ] `GET /api/v1/super/audit-logs` returns paginated logs; filters work
- [ ] `GET /api/v1/super/notifications` returns last 50 records
- [ ] `POST /api/v1/super/notifications/send` (all) queues emails, creates PlatformNotification records
- [ ] `POST /api/v1/super/notifications/send` (specific) only sends to listed tenants
- [ ] Flutter: Audit log action badge colors match spec
- [ ] Flutter: Compose form updates "Sending to: N" counter in real time
- [ ] Flutter: Confirm dialog prevents accidental sends

---

## Phase 5 — Platform Configuration: Settings + Plans + Profile
**Deliverable:** Platform owner can edit settings, pricing, and their own account on the phone.

---

### Phase 5 — Backend Tasks

#### 5.1 `PlatformSettingsApiController`

Mirror `SuperAdmin\SettingsController`.

**`index()`** — `GET /settings`  
Return all `PlatformSetting` key-value pairs as a structured object. **Never return the raw encrypted values** for secret keys — return empty strings. The client gets a "leave blank to keep" UX.

**`update()`** — `PUT /settings`  
Accept only the known keys. For secret fields (razorpay_key, razorpay_secret, razorpay_webhook_secret, mail_password): **only update if the provided value is non-empty**. Encrypt before storing (same as web). Return `{success: true}`.

#### 5.2 `PlatformPlanApiController`

Mirror `SuperAdmin\PlanController`.

**`index()`** — `GET /plans`  
Return computed pricing tiers (monthly_price, quarterly_price w/ discount, yearly_price w/ discount, trial_days, grace_days, features[]).

**`update()`** — `PUT /plans/{plan}` (where `{plan}` is monthly/quarterly/yearly)  
Update the relevant `platform_settings` keys. Accept: `monthly_price`, `quarterly_discount`, `yearly_discount`, `trial_days`, `grace_days`, `features` (array of strings).

#### 5.3 `PlatformProfileApiController`

**`show()`** — `GET /profile` — return `$request->user()`

**`update()`** — `PUT /profile`  
Validate: `name` (required, max:100). Block email changes. Update and return updated admin.

**`updatePassword()`** — `PUT /profile/password`  
Validate: `current_password` (required), `new_password` (required, min:8, confirmed — `new_password_confirmation` field). Check `Hash::check($current, $admin->password)`. If mismatch, return 422.

#### 5.4 Register Phase 5 routes

```php
Route::get('/settings',  [PlatformSettingsApiController::class, 'index'])->name('settings.index');
Route::put('/settings',  [PlatformSettingsApiController::class, 'update'])->name('settings.update');

Route::get('/plans',         [PlatformPlanApiController::class, 'index'])->name('plans.index');
Route::put('/plans/{plan}',  [PlatformPlanApiController::class, 'update'])->name('plans.update');

Route::get('/profile',          [PlatformProfileApiController::class, 'show'])->name('profile.show');
Route::put('/profile',          [PlatformProfileApiController::class, 'update'])->name('profile.update');
Route::put('/profile/password', [PlatformProfileApiController::class, 'updatePassword'])->name('profile.password');
```

---

### Phase 5 — Flutter Tasks

#### 5.5 Create `lib/models/platform_settings_models.dart` and `platform_plan_models.dart`

**Settings:**
```dart
class PlatformSettings {
  final String platformName, supportEmail;
  final int trialDays;
  // Razorpay: razorpayKey, razorpaySecret, razorpayWebhookSecret (always empty from API)
  // SMTP: mailHost, mailPort, mailUsername, mailPassword (always empty), mailFromName, mailFromEmail
  // Pricing: monthlyPrice, quarterlyDiscount, yearlyDiscount, graceDays
}
```

**Plan:**
```dart
class PlatformPlan {
  final String cycle; // monthly|quarterly|yearly
  final double price, originalPrice;
  final double? discountPct;
  final List<String> features;
  final int trialDays, graceDays;
}
```

#### 5.6 Implement `platform_settings_screen.dart` (replace stub)

4 collapsible `ExpansionTile` sections in white cards:

1. **General:** Platform Name, Support Email, Trial Days
2. **Razorpay Configuration:** Key ID, Secret (password field, "Leave blank to keep existing" hint), Webhook Secret (same)
3. **Email / SMTP:** Host, Port, Username, Password (blank=keep), From Name, From Email
4. **Subscription Pricing:** Monthly Base Price (₹), Quarterly Discount %, Yearly Discount %

Single "Save Changes" button at bottom calls `PUT /settings` with only non-blank values (for secret fields).

**Secret field logic:**
```dart
// Only include in the PUT body if the user typed something:
if (_razorpayKeyCtrl.text.isNotEmpty) data['razorpay_key'] = _razorpayKeyCtrl.text;
```

#### 5.7 Implement `platform_plans_screen.dart` (replace stub)

**View mode:** 3 pricing cards (Monthly/Quarterly/Yearly) + feature bullets. Quarterly card gets a light navy tint ("Most Popular"). Yearly gets "Best Value". Pencil icon on AppBar → Edit mode.

**Edit mode:** Full-screen form:
- Monthly Price, Quarterly Discount %, Yearly Discount %, Trial Days, Grace Days
- Features repeater: each row = TextField + red "×" IconButton. "+ Add Feature" TextButton at bottom.
- "Save Pricing" button → `PUT /plans/monthly` (or separate calls per cycle — simplest is one combined endpoint that accepts all pricing fields at once; check what the web controller actually does and mirror it)

#### 5.8 Implement `platform_profile_screen.dart` (replace stub)

Two white cards:

**Account Information:**
- Centered navy avatar with initials
- Name TextFormField (prefilled)
- Email field (disabled/read-only — no email change flow)
- "Role" read-only StatusBadge
- "Last login: X ago from Y.Y.Y.Y" caption (if `lastLoginAt != null`)
- "Save Changes" button → `PUT /profile`

**Change Password:**
- Current Password, New Password (with policy hint), Confirm New Password
- All have eye-toggle icons (same pattern as `login_screen.dart`)
- "Update Password" button → `PUT /profile/password`

**Below both cards:** outlined red "Log Out" button → `PlatformAuthService.instance.logout()` → `Navigator.pushAndRemoveUntil(LoginScreen)`

---

### Phase 5 — Testing Checklist
- [ ] `GET /api/v1/super/settings` returns all settings, secret fields are empty
- [ ] `PUT /api/v1/super/settings` with blank razorpay_key does NOT clobber stored key
- [ ] `PUT /api/v1/super/profile/password` with wrong current password returns 422
- [ ] Flutter: Settings secret fields show "Leave blank to keep existing" hint text
- [ ] Flutter: Plans edit form features repeater can add/remove rows
- [ ] Flutter: Profile password change validates all 3 fields

---

## Phase 6 — Dashboard Charts (fl_chart)
**Deliverable:** All 4 charts on the Platform Dashboard become real.

---

### Phase 6 — Flutter Tasks

#### 6.1 Add `fl_chart` dependency

**File:** `pubspec.yaml`

```yaml
dependencies:
  fl_chart: ^0.69.0  # check pub.dev for latest stable
```

Run `flutter pub get`.

---

#### 6.2 Implement 4 chart widgets in `platform_dashboard_screen.dart`

Replace the 4 "Charts coming soon" stubs:

**Revenue Trend — `_buildRevenueChart()`**  
`BarChart` from `fl_chart`. Data: `data.revenueTrend.amounts` (last 6 months). Navy bars (`AppColors.primary`), light gray grid lines, ₹-formatted Y-axis labels (abbreviated: ₹1.2L, etc.), month names on X-axis. Wrap in soft white card with `AppSectionHeader("Revenue Trend")` above.

**Status Distribution — `_buildStatusChart()`**  
`PieChart` with `donut` variant (centerSpaceRadius). 5 segments: Active=green, Trial=secondary, Grace=orange, Suspended=red, Inactive=textDisabled. Custom legend row below (colored dot + label + count — NOT `fl_chart`'s built-in legend), matching web design.

**New Registrations — `_buildRegistrationsChart()`**  
`LineChart` with `belowBarData` area fill (navy gradient, low opacity). Data: `data.registrationsTrend.counts`. Smooth line (`isCurved: true`). Month labels on X-axis.

**Subscription Cycles — `_buildCyclesChart()`**  
`PieChart` (no donut, full pie). 3 segments: Monthly=primary navy, Quarterly=teal, Yearly=purple. Custom legend row below.

---

### Phase 6 — Testing Checklist
- [ ] `flutter pub get` succeeds with `fl_chart` added
- [ ] All 4 charts render without errors on real device/emulator
- [ ] Charts update on pull-to-refresh
- [ ] Charts show "—" or flat state when all values are zero

---

## Phase 7 — Global Masters: Location + Medicine
**Deliverable:** Full CRUD for Country/State/District/City hierarchy and Medicine global catalog.

---

### Phase 7 — Backend Tasks

#### 7.1 `PlatformLocationApiController`

**New file:** `app/Http/Controllers/Api/SuperAdmin/PlatformLocationApiController.php`

Mirror `SuperAdmin\LocationMasterController`. Separate methods for each entity type. Key endpoints:

```
GET    /locations/countries              — list + search, paginate 10
POST   /locations/countries             — create (name, default_timezone, is_active)
PUT    /locations/countries/{id}        — update
DELETE /locations/countries/{id}        — delete (warn: cascade deletes states/districts/cities)
PATCH  /locations/countries/{id}/toggle — toggle is_active

GET    /locations/states                — list + filter by country_id, paginate 10
POST   /locations/states               — create (country_id, name)
PUT    /locations/states/{id}          — update
DELETE /locations/states/{id}          — delete (cascade warning)
PATCH  /locations/states/{id}/toggle   — toggle

GET    /locations/districts             — list + filter by state_id, paginate 10
POST   /locations/districts            — create (state_id, name)
PUT    /locations/districts/{id}       — update
DELETE /locations/districts/{id}       — delete (nulls district_id on cities, not cascade)
PATCH  /locations/districts/{id}/toggle — toggle

GET    /locations/cities                — list + filter by state_id + district_id, paginate 10
POST   /locations/cities               — create (state_id, district_id nullable, name)
PUT    /locations/cities/{id}          — update
DELETE /locations/cities/{id}          — delete
PATCH  /locations/cities/{id}/toggle   — toggle

GET    /locations/ajax/states           — ?country_id= → [id, name] dropdown helper
GET    /locations/ajax/districts        — ?state_id= → dropdown helper
GET    /locations/ajax/cities           — ?state_id= → dropdown helper
```

**Timezone cascade:** When a Country's `default_timezone` is updated AND `is_timezone_override = false` on a tenant using that country, update those tenants' timezone too — mirror the web controller's behavior exactly.

#### 7.2 `PlatformMedicineMasterApiController`

**New file:** `app/Http/Controllers/Api/SuperAdmin/PlatformMedicineMasterApiController.php`

Mirror `SuperAdmin\MedicineMasterController`. Endpoints for all 5 tabs:

```
Dosages:     GET /medicine-master/dosages        POST /medicine-master/dosages        PUT ./{id}  DELETE ./{id}  PATCH ./{id}/toggle
Types:       GET /medicine-master/types          POST /medicine-master/types          ...same pattern...
Categories:  GET /medicine-master/categories     POST /medicine-master/categories     ...
Routes:      GET /medicine-master/routes         POST /medicine-master/routes         ...
Medicines:   GET /medicine-master/medicines      POST /medicine-master/medicines      PUT ./{id}  DELETE ./{id}  PATCH ./{id}/toggle
```

**Cascade behavior:** Creating/renaming a Dosage/Type/Category/Route cascades the name to all tenant copies (mirror the web controller's `updateTenantCopies()` call or equivalent). Deletes do NOT cascade. Document this in the delete confirm copy on the Flutter side.

**Medicine fields for create/update:** `master_medicine_type_id`, `master_dosage_id`, `name`, `duration`, `qty`, `composition`, `company`, `price`, `is_active`.

#### 7.3 Register Phase 7 routes

```php
Route::prefix('locations')->name('locations.')->group(function () {
    foreach (['countries', 'states', 'districts', 'cities'] as $entity) {
        Route::get("/$entity",         [PlatformLocationApiController::class, "list" . ucfirst($entity)])->name("$entity.index");
        Route::post("/$entity",        [PlatformLocationApiController::class, "create" . ucfirst($entity)])->name("$entity.store");
        Route::put("/$entity/{id}",    [PlatformLocationApiController::class, "update" . ucfirst($entity)])->name("$entity.update");
        Route::delete("/$entity/{id}", [PlatformLocationApiController::class, "delete" . ucfirst($entity)])->name("$entity.destroy");
        Route::patch("/$entity/{id}/toggle", [PlatformLocationApiController::class, "toggle" . ucfirst($entity)])->name("$entity.toggle");
    }
    Route::get('/ajax/states',    [PlatformLocationApiController::class, 'ajaxStates'])->name('ajax.states');
    Route::get('/ajax/districts', [PlatformLocationApiController::class, 'ajaxDistricts'])->name('ajax.districts');
    Route::get('/ajax/cities',    [PlatformLocationApiController::class, 'ajaxCities'])->name('ajax.cities');
});

Route::prefix('medicine-master')->name('medicine-master.')->group(function () {
    foreach (['dosages', 'types', 'categories', 'routes', 'medicines'] as $tab) {
        Route::get("/$tab",         [PlatformMedicineMasterApiController::class, "list" . ucfirst($tab)])->name("$tab.index");
        Route::post("/$tab",        [PlatformMedicineMasterApiController::class, "create" . ucfirst($tab)])->name("$tab.store");
        Route::put("/$tab/{id}",    [PlatformMedicineMasterApiController::class, "update" . ucfirst($tab)])->name("$tab.update");
        Route::delete("/$tab/{id}", [PlatformMedicineMasterApiController::class, "delete" . ucfirst($tab)])->name("$tab.destroy");
        Route::patch("/$tab/{id}/toggle", [PlatformMedicineMasterApiController::class, "toggle" . ucfirst($tab)])->name("$tab.toggle");
    }
});
```

---

### Phase 7 — Flutter Tasks

#### 7.4 Create `lib/models/platform_location_models.dart`

```dart
class Country  { final int id; final String name, defaultTimezone; final bool isActive; final int stateCount; }
class State    { final int id, countryId; final String name; final bool isActive; }
class District { final int id, stateId; final String name; final bool isActive; }
class City     { final int id, stateId; final int? districtId; final String name; final bool isActive; }
```

#### 7.5 Create `lib/models/platform_medicine_master_models.dart`

```dart
class MasterDosage    { final int id; final String dosage; final bool isActive; }
class MasterType      { final int id; final String name; final bool isActive; }
class MasterCategory  { final int id; final String name; final bool isActive; }
class MasterRoute     { final int id; final String name; final bool isActive; }
class MasterMedicine  {
  final int id, masterMedicineTypeId, masterDosageId;
  final String name, duration, qty, composition, company;
  final double price;
  final bool isActive;
}
```

#### 7.6 Create services: `platform_location_service.dart`, `platform_medicine_master_service.dart`

Each manages CRUD + toggle for all its sub-types. The services also expose the ajax helpers (states by country, etc.) for the cascading dropdowns.

#### 7.7 Implement `platform_location_master_screen.dart` (replace stub)

**Shell:** TabBar (Countries / States / Districts / Cities) inside a `DefaultTabController(length: 4)`.

**Each tab reuses `GenericMasterScreen` variant:**
- Search bar
- Cascading filter dropdown where relevant:
  - States tab: "Country" dropdown (fetches countries on init, populates dropdown)
  - Districts tab: "State" dropdown
  - Cities tab: "State" + optional "District" dropdowns
- Soft-white list rows: name, count pill (for countries), active toggle switch (calls PATCH toggle), edit/delete icon buttons
- Disabled "Import from Excel" button with "web only for now" subtext
- FAB: "Add [entity]" → bottom sheet form

**Country bottom sheet form:**
- Country Name TextFormField
- Timezone picker: scrollable list grouped by region (build from a hardcoded list of IANA timezone strings, grouped like "Asia", "America", "Europe" — or use Dart's `DateTime.now().timeZoneName` for just common ones if the full list is too large)
- Edit mode only: orange warning banner about timezone cascade

**Delete confirm dialogs:**
- Country: "Deletes ALL states, districts, and cities in this country."
- State: "Deletes all districts and cities in this state."
- District: "Removes district association from cities — cities themselves are NOT deleted."
- City: "Permanently deletes this city."

#### 7.8 Implement `platform_medicine_master_screen.dart` (replace stub)

**Shell:** TabBar (Dosages / Types / Categories / Routes / Medicines) inside `DefaultTabController(length: 5)`.

**Tabs 1–4 (Dosages/Types/Categories/Routes):** Use `GenericMasterScreen` directly (or its platform variant) with `apiPath` pointing to the relevant platform endpoint. Simple name + toggle + edit/delete.

**Tab 5 (Medicines):** Custom list with richer cards (name, type+dosage pills, company, price) and a richer bottom-sheet form (Type dropdown, Dosage dropdown, Name, Duration, Qty, Composition multiline, Company, Price).

**Delete confirm copy for tabs 1–4:** "This won't remove it from hospitals already using it."

**Disabled Excel import button** on Medicines tab: same "web only for now" pattern as Location Master.

---

### Phase 7 — Testing Checklist
- [ ] `GET /api/v1/super/locations/countries` returns paginated list
- [ ] `POST /api/v1/super/locations/countries` creates country, returns 201
- [ ] `PATCH /api/v1/super/locations/countries/{id}/toggle` flips is_active
- [ ] Updating a country's timezone cascades to non-override tenants
- [ ] `DELETE /api/v1/super/locations/districts/{id}` nulls district_id on cities, does NOT delete cities
- [ ] `POST /api/v1/super/medicine-master/dosages` creates dosage, cascades name to tenant copies
- [ ] `DELETE /api/v1/super/medicine-master/types/{id}` does NOT cascade to tenants
- [ ] Flutter: State tab's Country dropdown populates on open
- [ ] Flutter: Medicine form dropdowns (Type, Dosage) are populated from existing masters
- [ ] Flutter: Delete confirm for District says "cities not deleted"
- [ ] Flutter: Delete confirm for dosage/type says "won't remove from hospitals"

---

## Summary: Files to Create / Edit

### Backend (all under `J:\laragon\www\eye_care_new_clone\eye-saas-hms`)

| Phase | File | Action |
|---|---|---|
| 1 | `app/Models/Platform/PlatformAdmin.php` | Edit — add `HasApiTokens` |
| 1 | `config/auth.php` | Edit — add `platform_admins` provider + `platform_api` guard |
| 1 | `app/Http/Middleware/EnsurePlatformAdmin.php` | Create |
| 1 | `bootstrap/app.php` | Edit — register middleware alias |
| 1 | `app/Http/Controllers/Api/SuperAdmin/PlatformAuthController.php` | Create |
| 1 | `routes/api.php` | Edit — replace super group |
| 2 | `app/Http/Controllers/Api/SuperAdmin/PlatformDashboardApiController.php` | Create |
| 2 | `app/Http/Controllers/Api/SuperAdmin/PlatformHospitalApiController.php` | Create |
| 3 | `app/Http/Controllers/Api/SuperAdmin/PlatformPaymentApiController.php` | Create |
| 3 | `app/Http/Controllers/Api/SuperAdmin/PlatformSubscriptionApiController.php` | Create |
| 4 | `app/Http/Controllers/Api/SuperAdmin/PlatformAuditLogApiController.php` | Create |
| 4 | `app/Http/Controllers/Api/SuperAdmin/PlatformNotificationApiController.php` | Create |
| 5 | `app/Http/Controllers/Api/SuperAdmin/PlatformSettingsApiController.php` | Create |
| 5 | `app/Http/Controllers/Api/SuperAdmin/PlatformPlanApiController.php` | Create |
| 5 | `app/Http/Controllers/Api/SuperAdmin/PlatformProfileApiController.php` | Create |
| 7 | `app/Http/Controllers/Api/SuperAdmin/PlatformLocationApiController.php` | Create |
| 7 | `app/Http/Controllers/Api/SuperAdmin/PlatformMedicineMasterApiController.php` | Create |

### Flutter (all under `J:\all_folder_of_C_drive\eye_care_whole\eye_care_app\lib`)

| Phase | File | Action |
|---|---|---|
| 1 | `config/app_config.dart` | Edit — add `platformApiUrl` |
| 1 | `constants/app_colors.dart` | Edit — add `resetToDefault()` |
| 1 | `pubspec.yaml` | Edit — add `fl_chart` (Phase 6) |
| 1 | `models/platform_admin_models.dart` | Create |
| 1 | `services/platform_auth_service.dart` | Create (also defines `PlatformAuthenticatedService` mixin) |
| 1 | `widgets/status_badge.dart` | Create |
| 1 | `screens/platform_login_screen.dart` | Create |
| 1 | `screens/platform_home_screen.dart` | Create |
| 1 | `screens/login_screen.dart` | Edit — add Platform link |
| 1 | 10 stub screens | Create |
| 2 | `models/platform_tenant_models.dart` | Create |
| 2 | `models/platform_dashboard_models.dart` | Create |
| 2 | `services/platform_dashboard_service.dart` | Create |
| 2 | `services/platform_tenant_service.dart` | Create |
| 2 | `screens/platform_dashboard_screen.dart` | Implement (stub → real) |
| 2 | `screens/platform_hospitals_screen.dart` | Implement |
| 2 | `screens/platform_hospital_detail_screen.dart` | Implement |
| 2 | `screens/platform_hospital_form_screen.dart` | Implement |
| 3 | `models/platform_payment_models.dart` | Create |
| 3 | `models/platform_subscription_models.dart` | Create |
| 3 | `services/platform_payment_service.dart` | Create |
| 3 | `services/platform_subscription_service.dart` | Create |
| 3 | `screens/platform_billing_screen.dart` | Implement |
| 4 | `models/platform_audit_log_models.dart` | Create |
| 4 | `models/platform_notification_models.dart` | Create |
| 4 | `services/platform_audit_log_service.dart` | Create |
| 4 | `services/platform_notification_service.dart` | Create |
| 4 | `screens/platform_audit_logs_screen.dart` | Implement |
| 4 | `screens/platform_notifications_screen.dart` | Implement |
| 5 | `models/platform_settings_models.dart` | Create |
| 5 | `models/platform_plan_models.dart` | Create |
| 5 | `services/platform_settings_service.dart` | Create |
| 5 | `services/platform_plan_service.dart` | Create |
| 5 | `services/platform_profile_service.dart` | Create |
| 5 | `screens/platform_settings_screen.dart` | Implement |
| 5 | `screens/platform_plans_screen.dart` | Implement |
| 5 | `screens/platform_profile_screen.dart` | Implement |
| 6 | `pubspec.yaml` | Edit — add `fl_chart` |
| 6 | `screens/platform_dashboard_screen.dart` | Edit — replace 4 chart stubs |
| 7 | `models/platform_location_models.dart` | Create |
| 7 | `models/platform_medicine_master_models.dart` | Create |
| 7 | `services/platform_location_service.dart` | Create |
| 7 | `services/platform_medicine_master_service.dart` | Create |
| 7 | `screens/platform_location_master_screen.dart` | Implement |
| 7 | `screens/platform_medicine_master_screen.dart` | Implement |

---

## Known Issues / Decisions Made

| Issue | Resolution |
|---|---|
| `hospital_code` required server-side but web form doesn't collect it | **Auto-generate server-side** in `PlatformHospitalApiController@store` — derive from first 3 alpha chars of hospital name, ensure uniqueness. Mobile form does NOT show this field. No web form change needed since the web creates hospitals fine (it must be supplying it somehow, or there's a gap there — but this PRD says do not silently work around; we're making an explicit decision to auto-generate for the API and flagging it to the backend owner) |
| `PlatformAdmin.last_login_at/ip` never written | Fixed in Phase 1 `PlatformAuthController@login` |
| Pricing labels in offline payment modal are hardcoded on web | Reproduce as static labels for parity — do not wire to `platform_settings` (backend doesn't either) |
| Hospital admin email is not editable anywhere | `admin_email` field shown as read-only/disabled in Edit Hospital and Profile screens |
| `AppColors.resetToDefault()` | Added in Phase 1.7 — called on Platform login, Platform logout, and Platform `restoreSession()` |
| Session isolation | `platform_admin_token` / `cached_platform_admin` are completely separate SharedPreferences keys from `auth_token` / `cached_user` / `hospital_slug`. Clearing one never touches the other |
| `support` role | Treated identically to `super_admin` for v1 (backend enforces no difference — do not invent client-side-only restrictions) |

---

*End of implementation plan.*
