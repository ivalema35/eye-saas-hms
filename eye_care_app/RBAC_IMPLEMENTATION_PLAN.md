# Role-Based Access Control (RBAC) — Implementation Plan
## Eye-SaaS HMS Mobile App

> **Status:** Planning only — NO code changes yet  
> **Scope:** Backend API change + Full Flutter RBAC implementation  
> **Goal:** Non-admin roles see only screens/features their role has permission for

---

## Table of Contents

1. [Current State Analysis](#1-current-state-analysis)
2. [Backend Architecture (Web)](#2-backend-architecture-web)
3. [The Gap — What's Missing](#3-the-gap--whats-missing)
4. [Session & Token Handling Problem](#4-session--token-handling-problem)
5. [Full Permission List](#5-full-permission-list)
6. [Default Role → Permission Mapping](#6-default-role--permission-mapping)
7. [Screen → Permission Mapping](#7-screen--permission-mapping)
8. [Implementation Plan (Phased)](#8-implementation-plan-phased)
9. [Files to Create / Modify](#9-files-to-create--modify)
10. [Detailed Phase Breakdown](#10-detailed-phase-breakdown)
11. [Edge Cases & Decisions](#11-edge-cases--decisions)

---

## 1. Current State Analysis

### What the mobile app currently stores after login

```
UserInfo {
  id, name, email, contact, status, doctorPrefix, profilePhotoUrl
  role: RoleInfo {
    id, name, slug, color, isSuper   ← NO permissions here
  }
}
```

### What the login API currently returns

```json
{
  "success": true,
  "data": {
    "token": "...",
    "user": {
      "id": 1, "name": "Dr. Shah", "email": "...",
      "role": {
        "id": 2, "name": "Doctor", "slug": "doctor",
        "color": "#2980B9", "is_super": false
        // ← NO permissions array
      }
    },
    "hospital": { "name": "...", "slug": "..." }
  }
}
```

### What `GET /auth/me` currently returns

Same structure as login — role info only, no permissions. The endpoint already exists at line 96 of `api.php`.

### Current auth flow (SplashScreen)

```
App Start
  └─ restoreSession()  → just reloads slug + hospital name from SharedPreferences
  └─ if token + user + hospital exist in SharedPreferences → go to HomeScreen
  └─ else → go to LoginScreen
  // ← NO token validation against server
  // ← NO permissions loaded
```

### Current session problem

- Token is stored in `SharedPreferences` permanently
- `_clearAll()` only runs on explicit logout
- If admin revokes token from web dashboard → mobile app has NO idea
- If token expires → next API call gets `401` → currently **unhandled** (app shows error, doesn't redirect to login)
- No global 401 interceptor exists anywhere

---

## 2. Backend Architecture (Web)

### Tables involved

| Table | Purpose |
|---|---|
| `permissions` | Platform-wide master list of all permission keys |
| `roles` | Tenant-scoped roles (one set per hospital) |
| `role_permissions` | Many-to-many: role ↔ permission with `is_granted` boolean |

### Key Role fields

| Field | Meaning |
|---|---|
| `is_super = true` | Hospital Admin — bypasses ALL permission checks |
| `is_system = true` | Cannot be deleted (but permissions can be edited) |
| `is_system = false` | Custom role — can be deleted if no users assigned |

### How permission check works on backend

The `permission:{action}` middleware (used on API routes) does:
1. Get authenticated user's role
2. If `role.is_super == true` → **allow everything, skip check**
3. Else → check `role_permissions` table for `is_granted = true` for that action
4. If not granted → return `403 Forbidden`

### `Role::grantedPermissions()` relationship

```php
// Returns only permissions where is_granted = true
public function grantedPermissions() {
    return $this->belongsToMany(Permission::class, 'role_permissions', ...)
        ->wherePivot('is_granted', true);
}

// Helper to get just the action strings
public function getGrantedPermissionKeys(): array {
    return $this->grantedPermissions()->pluck('action')->toArray();
}
```

### `GET /auth/me` endpoint (already exists)

```
GET /api/v1/{slug}/auth/me
Headers: Authorization: Bearer {token}
→ Returns same user + hospital structure as login
→ Currently does NOT include permissions (same gap as login)
```

---

## 3. The Gap — What's Missing

### Backend gap

`AuthController::formatUser()` loads `role` relation but NOT `role.grantedPermissions`.  
Neither `login` nor `me` endpoint returns a `permissions` array.

**Fix needed:** Add `permissions` array to `formatUser()` output.

### Flutter gap

1. `RoleInfo` model has no `permissions` field
2. No `PermissionService` exists
3. No permission constants file exists
4. No screen-level or widget-level gating exists
5. No global 401 handler exists
6. `SplashScreen` does not validate token against server on app start

---

## 4. Session & Token Handling Problem

### Web vs Mobile session difference

| | Web (Laravel) | Mobile (current) |
|---|---|---|
| Session storage | Server-side PHP session / cookie | `SharedPreferences` (device) |
| Session expiry | Server controls (config/session.php) | Token lives forever until explicit logout |
| Auto-logout on expiry | Yes — redirect to login | **No** — nothing happens |
| Token revoked by admin | Session gone → redirect | **Nothing** — app still works until next API call |
| 401 handling | Laravel redirects to login | **Unhandled** — app shows error |

### What needs to be added

**Problem 1 — No 401 global handler:**  
Any API call returning 401 should clear storage and navigate to login.  
Currently each service throws an exception but nothing catches it globally to redirect.

**Problem 2 — No token validation on app start:**  
`SplashScreen._goToLogin()` checks if token exists in `SharedPreferences` but never validates it against the server. A revoked or expired token will pass this check and the user will be sent to `HomeScreen` — then fail on the first real API call.

**Problem 3 — Permissions never refreshed:**  
Even after we add permissions to login response, if admin changes a user's permissions while they are logged in, the mobile app will have stale permissions until next login.

### Recommended solution (two-part)

**Part A — On app start (SplashScreen):**  
Instead of just checking if token exists in storage, call `GET /auth/me` to validate the token AND get fresh user + permissions. If 401 → clear storage → go to login. If network error → use cached data (offline grace).

**Part B — On app resume (AppLifecycleState.resumed):**  
When user brings app back from background, silently call `GET /auth/me` in background. If 200 → update `PermissionService` in-memory (no UI disruption). If 401 → clear storage → navigate to login. This handles the "admin changed permissions while user was logged in" case.

**Part C — Global 401 handler:**  
In `AuthService`, add a method `handleUnauthorized()` that clears storage and triggers navigation to login. Every API service should call this on 401 response.

---

## 5. Full Permission List

All 52 permissions (51 from `PermissionsSeeder.php` + 1 added via migration `2026_04_11_200000_add_ot_billing_manage_permission.php`):

> **Note on count:** OPD=14, OT=23, Masters=11, Settings=2, Reports=2 → 52 total. The original PRD count of 45 was a miscounting error.

### OPD Module (14 permissions)

| Action Key | Label |
|---|---|
| `opd.patient.register` | Register Walk-in Patient |
| `opd.patient.register_phone` | Register Phone Appointment |
| `opd.patient.view` | View Today's OPD Patients |
| `opd.patient.edit` | Edit Patient Record (pre-exam) |
| `opd.patient.delete` | Soft Delete Patient |
| `opd.exam.primary` | Perform Primary Eye Exam |
| `opd.exam.secondary` | Perform Secondary Eye Exam |
| `opd.exam.history` | View Patient Exam History |
| `opd.bill.print` | Print OPD Bill |
| `opd.prescription.print` | Print Prescription |
| `opd.foc.create` | Create FOC (Free Of Charge) |
| `opd.foc.accept` | Accept FOC |
| `opd.reports.view` | View OPD Reports |
| `opd.reports.export` | Export OPD Data to Excel |

### OT Module (23 permissions)

| Action Key | Label |
|---|---|
| `ot.booking.create` | Book OT Appointment |
| `ot.booking.modify` | Modify OT Booking |
| `ot.booking.cancel` | Cancel OT Booking |
| `ot.counselling.fill` | Fill OT Counselling Form |
| `ot.patient.list` | View OT Patient List |
| `ot.package.set` | Set Package Amount |
| `ot.payment.record` | Record Payment and Receipt |
| `ot.payment.export` | Excel Export (OT Payments) |
| `ot.ward.entry` | Ward Admission (Attend/Postpone) |
| `ot.preop.entry` | Pre-Op Vitals Entry (BP/RBS etc.) |
| `ot.dilation.track` | Dilation Drop Tracking |
| `ot.surgery.ready` | Mark Ready to Operate |
| `ot.surgery.record` | Record Surgery Details |
| `ot.lens.record` | Record Lens Details |
| `ot.lens.implant` | Mark Lens Implanted |
| `ot.meds.takehome` | Add Take-Home Medicines |
| `ot.invoice.view` | View Auto-Generated Invoice |
| `ot.invoice.edit` | Edit Invoice Line Items |
| `ot.discharge.generate` | Generate Discharge Documents |
| `ot.discharge.patient` | Discharge Patient (Final) |
| `ot.certificate.print` | Print Operation Certificate |
| `ot.bill.print` | Print Bill of Summary |
| `ot.billing.manage` | Manage OT Billing (invoice, discharge, summary bill, certificate) — added via migration, not in original seeder |

### Masters Module (11 permissions)

| Action Key | Label |
|---|---|
| `master.case_types` | Case Types CRUD |
| `master.doctors` | Doctor Accounts CRUD |
| `master.receptions` | Receptionist Accounts CRUD |
| `master.ot_staff` | OT Staff Accounts CRUD |
| `master.roles` | Custom Role Management |
| `master.locations` | Locations Master CRUD |
| `master.medicines` | Medicine Master CRUD |
| `master.eye_exam` | Eye Exam Masters CRUD |
| `master.ot_slots` | OT Slot Master CRUD |
| `master.ot_types` | OT Type Master CRUD |
| `master.ot_charges` | OT Charge Heads CRUD |

### Settings (2 permissions)

| Action Key | Label |
|---|---|
| `settings.hospital` | Hospital Profile Settings |
| `settings.subscription` | Subscription / Billing Info |

### Reports (2 permissions)

| Action Key | Label |
|---|---|
| `reports.view` | View All Reports |
| `reports.export` | Export All Reports |

---

## 6. Default Role → Permission Mapping

From `SystemRolesSeeder.php`:

### Hospital Admin (`is_super = true`)
→ **All permissions granted. No check needed. `isSuper` flag bypasses everything.**

### Doctor
```
opd.patient.view, opd.exam.primary, opd.exam.secondary,
opd.exam.history, opd.prescription.print, opd.foc.create, reports.view
```

### Receptionist
```
opd.patient.register, opd.patient.register_phone, opd.patient.view,
opd.patient.edit, opd.patient.delete, opd.bill.print,
opd.foc.accept, reports.view, master.receptions
```

### OT Receptionist
```
ot.booking.create, ot.booking.modify, ot.booking.cancel,
ot.counselling.fill, ot.patient.list, ot.package.set,
ot.payment.record, ot.payment.export, ot.invoice.view,
ot.bill.print, reports.view
```

### Accountant
```
ot.patient.list, ot.payment.record, ot.payment.export,
ot.invoice.view, ot.invoice.edit, ot.bill.print,
reports.view, reports.export
```

### OT Doctor
```
ot.patient.list, ot.surgery.ready, ot.surgery.record,
ot.lens.record, ot.lens.implant, ot.meds.takehome,
ot.invoice.view, ot.discharge.generate, ot.discharge.patient,
ot.certificate.print, reports.view
```

### OT Assistant
```
ot.patient.list, ot.ward.entry, ot.preop.entry,
ot.dilation.track, ot.surgery.ready, ot.lens.record, ot.meds.takehome
```

> **Note:** These are DEFAULT permissions. Admin can change any role's permissions at any time from the web dashboard. The mobile app must always use the server-returned permissions, never hardcode these defaults.

---

## 7. Screen → Permission Mapping

### Bottom Nav Tabs

| Tab | Screen | Required Permission | Notes |
|---|---|---|---|
| Home (0) | `DashboardScreen` | None — always visible | Dashboard is public to all logged-in users |
| Patients (1) | `PatientsScreen` | `opd.patient.view` | Hide tab if no permission |
| Masters (2) | `MastersScreen` | `master.case_types` OR `master.eye_exam` | Hide tab if neither permission present |
| Reports (3) | `ReportsScreen` | `reports.view` | Hide tab if no permission |
| Queue (4) | `ClinicalQueueScreen` | `opd.exam.primary` OR `opd.exam.secondary` | Doctors/examiners only — matches web `permission:opd.exam.primary\|opd.exam.secondary` |

### Drawer Items

| Drawer Item | Required Permission |
|---|---|
| Dashboard | None |
| Patients | `opd.patient.view` |
| Share History | `opd.patient.view` |
| Queue Dashboard | `opd.exam.primary` OR `opd.exam.secondary` |
| OT Bookings | `ot.booking.create` OR `ot.patient.list` |
| Accountant / Billing | `ot.payment.record` |
| Ward Management | `ot.ward.entry` |
| Doctor Dashboard | `ot.surgery.record` |
| Assistant Dashboard | `ot.lens.record` OR `ot.lens.implant` OR `ot.patient.list` |
| Discharge & Invoices | `ot.billing.manage` |
| Reports | `reports.view` |
| Medicines | `master.medicines` |
| Medicine Types/Categories/etc. | `master.medicines` |
| Masters | `master.case_types` OR `master.eye_exam` |
| OT Masters (slots, types, surgery-types, charge-heads, lens-options) | `isSuper = true` only — web uses `role:admin` middleware, not a permission key |
| Settings | `settings.hospital` |
| Roles & Permissions | `master.roles` |
| Users | `master.doctors` OR `master.receptions` OR `master.ot_staff` |

### Screen-Level Actions / Buttons

| Screen | Action/Button | Required Permission |
|---|---|---|
| `PatientsScreen` | Register Walk-in button | `opd.patient.register` |
| `PatientsScreen` | Register Phone button | `opd.patient.register_phone` |
| `PatientsScreen` | Edit patient | `opd.patient.edit` |
| `PatientsScreen` | Delete patient | `opd.patient.delete` |
| `PatientsScreen` | Primary Exam button | `opd.exam.primary` |
| `PatientsScreen` | Secondary Exam button | `opd.exam.secondary` |
| `PatientsScreen` | Exam History button | `opd.exam.history` |
| `PatientsScreen` | Print Bill button | `opd.bill.print` |
| `PatientsScreen` | Print Prescription button | `opd.prescription.print` |
| `PatientsScreen` | Create FOC button | `opd.foc.create` |
| `PatientsScreen` | Accept FOC button | `opd.foc.accept` |
| `MastersScreen` | Each master sub-section | Respective `master.*` permission |
| `UsersScreen` | View/Edit/Delete users | `master.doctors` OR `master.receptions` OR `master.ot_staff` |
| `SettingsScreen` | Hospital settings | `settings.hospital` |

---

## 8. Implementation Plan (Phased)

### Phase 1 — Backend: Add permissions to API response

**File:** `app/Http/Controllers/Api/AuthController.php`  
**Method:** `formatUser()`

Change:
```php
// BEFORE
'role' => $user->role ? [
    'id'       => $user->role->id,
    'name'     => $user->role->name,
    'slug'     => $user->role->slug,
    'color'    => $user->role->color,
    'is_super' => (bool) $user->role->is_super,
] : null,
```

To:
```php
// AFTER
'role' => $user->role ? [
    'id'          => $user->role->id,
    'name'        => $user->role->name,
    'slug'        => $user->role->slug,
    'color'       => $user->role->color,
    'is_super'    => (bool) $user->role->is_super,
    'permissions' => $user->role->is_super
        ? ['*']   // super admin gets wildcard — mobile checks isSuper first
        : $user->role->getGrantedPermissionKeys(),
] : null,
```

Three places in `AuthController.php` need updating:

**1. `login()` — eager-load with query:**
```php
// BEFORE
->with('role')

// AFTER
->with(['role.grantedPermissions'])
```

**2. `me()` — loadMissing:**
```php
// BEFORE
$user = $request->user()->loadMissing('role');

// AFTER
$user = $request->user()->loadMissing(['role.grantedPermissions']);
```

**3. `formatUser()` internal safety load (line 156) — also needs updating:**
```php
// BEFORE
$user->loadMissing('role');

// AFTER
$user->loadMissing(['role.grantedPermissions']);
```

> All three must change. `formatUser()` does its own `loadMissing('role')` as a safety net. If only `login()` and `me()` are updated, `formatUser()` will re-load just `role` and `getGrantedPermissionKeys()` will fire an extra N+1 query for `grantedPermissions`.

**Result:** Both `POST /auth/login` and `GET /auth/me` will now return:
```json
{
  "role": {
    "id": 2, "name": "Doctor", "slug": "doctor",
    "is_super": false,
    "permissions": ["opd.patient.view", "opd.exam.primary", "opd.exam.secondary", ...]
  }
}
```

---

### Phase 2 — Flutter: Update `RoleInfo` model

**File:** `lib/models/auth_models.dart`

Add `permissions: List<String>` to `RoleInfo`:

```dart
class RoleInfo {
  final int id;
  final String name;
  final String slug;
  final String color;
  final bool isSuper;
  final List<String> permissions;  // ← NEW

  // fromJson: parse permissions array, default to []
  // toJson: include permissions in serialization (for SharedPreferences storage)
}
```

`UserInfo` and `HospitalInfo` — no changes needed.

---

### Phase 3 — Flutter: Create `PermissionService`

**New file:** `lib/services/permission_service.dart`

Singleton service, similar pattern to `ExamMastersService`.

```dart
class PermissionService {
  static final PermissionService instance = PermissionService._();
  PermissionService._();

  List<String> _permissions = [];
  bool _isSuper = false;
  bool _loaded = false;

  // Load from RoleInfo (called after login and after /auth/me refresh)
  void load(RoleInfo? role) {
    _isSuper = role?.isSuper ?? false;
    _permissions = role?.permissions ?? [];
    _loaded = true;
  }

  // Load from stored user (called on app start before /auth/me returns)
  Future<void> loadFromStorage() async {
    final user = await AuthService.instance.getStoredUser();
    load(user?.role);
  }

  // Core check
  bool can(String permission) {
    if (!_loaded) return false;
    if (_isSuper) return true;
    return _permissions.contains(permission);
  }

  // Check any of multiple permissions
  bool canAny(List<String> permissions) => permissions.any(can);

  // Check all of multiple permissions
  bool canAll(List<String> permissions) => permissions.every(can);

  // Check if user has any permission in a module (e.g., 'master')
  bool canModule(String module) {
    if (_isSuper) return true;
    return _permissions.any((p) => p.startsWith('$module.'));
  }

  bool get isLoaded => _loaded;
  bool get isSuper => _isSuper;

  void clear() {
    _permissions = [];
    _isSuper = false;
    _loaded = false;
  }
}
```

---

### Phase 4 — Flutter: Create permission constants

**New file:** `lib/constants/permissions.dart`

```dart
/// All permission action keys — single source of truth.
/// Matches PermissionsSeeder.php exactly.
abstract class Perm {
  // OPD — Patient
  static const opdPatientRegister      = 'opd.patient.register';
  static const opdPatientRegisterPhone = 'opd.patient.register_phone';
  static const opdPatientView          = 'opd.patient.view';
  static const opdPatientEdit          = 'opd.patient.edit';
  static const opdPatientDelete        = 'opd.patient.delete';

  // OPD — Exam
  static const opdExamPrimary          = 'opd.exam.primary';
  static const opdExamSecondary        = 'opd.exam.secondary';
  static const opdExamHistory          = 'opd.exam.history';

  // OPD — Billing / Prescription
  static const opdBillPrint            = 'opd.bill.print';
  static const opdPrescriptionPrint    = 'opd.prescription.print';

  // OPD — FOC
  static const opdFocCreate            = 'opd.foc.create';
  static const opdFocAccept            = 'opd.foc.accept';

  // OPD — Reports
  static const opdReportsView          = 'opd.reports.view';
  static const opdReportsExport        = 'opd.reports.export';

  // OT — Booking
  static const otBookingCreate         = 'ot.booking.create';
  static const otBookingModify         = 'ot.booking.modify';
  static const otBookingCancel         = 'ot.booking.cancel';
  static const otCounsellingFill       = 'ot.counselling.fill';
  static const otPatientList           = 'ot.patient.list';
  static const otPackageSet            = 'ot.package.set';

  // OT — Payment
  static const otPaymentRecord         = 'ot.payment.record';
  static const otPaymentExport         = 'ot.payment.export';

  // OT — Ward / Pre-Op
  static const otWardEntry             = 'ot.ward.entry';
  static const otPreopEntry            = 'ot.preop.entry';
  static const otDilationTrack         = 'ot.dilation.track';

  // OT — Surgery
  static const otSurgeryReady          = 'ot.surgery.ready';
  static const otSurgeryRecord         = 'ot.surgery.record';

  // OT — Lens
  static const otLensRecord            = 'ot.lens.record';
  static const otLensImplant           = 'ot.lens.implant';
  static const otMedsTakehome          = 'ot.meds.takehome';

  // OT — Invoice / Discharge
  static const otInvoiceView           = 'ot.invoice.view';
  static const otInvoiceEdit           = 'ot.invoice.edit';
  static const otBillingManage         = 'ot.billing.manage';
  static const otDischargeGenerate     = 'ot.discharge.generate';
  static const otDischargePatient      = 'ot.discharge.patient';
  static const otCertificatePrint      = 'ot.certificate.print';
  static const otBillPrint             = 'ot.bill.print';

  // Masters
  static const masterCaseTypes         = 'master.case_types';
  static const masterDoctors           = 'master.doctors';
  static const masterReceptions        = 'master.receptions';
  static const masterOtStaff           = 'master.ot_staff';
  static const masterRoles             = 'master.roles';
  static const masterLocations         = 'master.locations';
  static const masterMedicines         = 'master.medicines';
  static const masterEyeExam           = 'master.eye_exam';
  static const masterOtSlots           = 'master.ot_slots';
  static const masterOtTypes           = 'master.ot_types';
  static const masterOtCharges         = 'master.ot_charges';

  // Settings
  static const settingsHospital        = 'settings.hospital';
  static const settingsSubscription    = 'settings.subscription';

  // Reports
  static const reportsView             = 'reports.view';
  static const reportsExport           = 'reports.export';
}
```

---

### Phase 5 — Flutter: Fix auth flow (SplashScreen + 401 handling)

#### 5a. Update `AuthService` — add `refreshSession()` and `handleUnauthorized()`

**File:** `lib/services/auth_service.dart`

Add:
```dart
// Validate token + get fresh user+permissions from server
// Returns null on 401 (token invalid), throws on network error
Future<UserInfo?> refreshSession() async {
  final token = await getStoredToken();
  if (token == null) return null;

  final uri = Uri.parse('${AppConfig.hospitalApiUrl}/auth/me');
  final response = await http.get(uri, headers: _authHeaders(token))
      .timeout(AppConfig.requestTimeout);

  if (response.statusCode == 200) {
    final body = jsonDecode(response.body) as Map<String, dynamic>;
    if (body['success'] == true) {
      final data = body['data'] as Map<String, dynamic>;
      final user = UserInfo.fromJson(data['user'] as Map<String, dynamic>);
      await _saveUser(user);  // update stored user with fresh permissions
      return user;
    }
  } else if (response.statusCode == 401) {
    await _clearAll();
    return null;  // signal: go to login
  }
  throw Exception('Session refresh failed: ${response.statusCode}');
}
```

#### 5b. Update `SplashScreen`

**Timing problem to fix:** Current code waits 3 seconds (animation), THEN calls `_goToLogin()` which calls `/auth/me`. Total startup time = 3s + API call time.

**Fix:** Start the `/auth/me` call immediately in `initState()`, in parallel with the 3-second animation. Navigate only when BOTH are done (whichever finishes last). This means the API call overlaps with the animation — total startup time = `max(3s, API call time)` instead of `3s + API call time`.

```dart
// In initState():
_progressController.forward();
_sessionFuture = _loadSession();  // start immediately, parallel to animation
Future.delayed(const Duration(milliseconds: 3000), _onSplashDone);

// _loadSession() — runs in parallel with animation
Future<void> _loadSession() async {
  await AuthService.instance.restoreSession();
  final token = await AuthService.instance.getStoredToken();
  if (token == null) { _sessionResult = null; return; }

  try {
    _sessionResult = await AuthService.instance.refreshSession();
    // refreshSession() returns null on 401, UserInfo on success
  } catch (_) {
    // Network error — fall back to cached user
    _sessionResult = await AuthService.instance.getStoredUser();
  }
}

// _onSplashDone() — called after 3 second animation
Future<void> _onSplashDone() async {
  await _sessionFuture;  // wait for session load if still running
  if (!mounted) return;

  final hospital = await AuthService.instance.getStoredHospital();
  if (_sessionResult != null && hospital != null) {
    PermissionService.instance.load(_sessionResult!.role);
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => HomeScreen(user: _sessionResult!, hospital: hospital)),
    );
  } else {
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
    );
  }
}
```

> `_navigateToLogin()` and `_navigateToHome()` in pseudocode above are just inline `Navigator.of(context).pushReplacement(...)` calls — no separate helper methods exist in the current code.

#### 5c. Update `LoginScreen` — load permissions after successful login

```dart
// After successful login:
PermissionService.instance.load(result.user!.role);
```

#### 5d. Add AppLifecycleState observer for background refresh

In `HomeScreen` (or a top-level widget), add `WidgetsBindingObserver`:
```dart
@override
void didChangeAppLifecycleState(AppLifecycleState state) {
  if (state == AppLifecycleState.resumed) {
    _silentRefresh();
  }
}

Future<void> _silentRefresh() async {
  try {
    final user = await AuthService.instance.refreshSession();
    if (user == null) {
      // Token revoked — force logout
      await AuthService.instance.logout();
      if (mounted) Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const LoginScreen()),
      );
      return;
    }
    // Update permissions in-memory (no UI disruption)
    PermissionService.instance.load(user.role);
  } catch (_) {
    // Network error — keep existing permissions, don't disrupt user
  }
}
```

---

### Phase 6 — Flutter: Gate the HomeScreen bottom nav

**File:** `lib/screens/home_screen.dart`

The bottom nav currently always shows 5 tabs. After RBAC, tabs should be hidden if the user has no relevant permissions.

```dart
// Build visible nav items dynamically
List<_NavConfig> get _visibleNavItems {
  final p = PermissionService.instance;
  return [
    _NavConfig(0, Icons.home_rounded, 'Home', true),  // always visible
    if (p.can(Perm.opdPatientView))
      _NavConfig(1, Icons.people_alt_rounded, 'Patients', ...),
    if (p.can(Perm.masterCaseTypes) || p.can(Perm.masterEyeExam))
      _NavConfig(2, Icons.tune_rounded, 'Masters', ...),
    if (p.can(Perm.reportsView))
      _NavConfig(3, Icons.bar_chart_rounded, 'Reports', ...),
    if (p.can(Perm.opdExamPrimary) || p.can(Perm.opdExamSecondary))
      _NavConfig(4, Icons.queue_rounded, 'Queue', ...),
  ];
}
```

> **Note:** Index mapping changes when tabs are hidden. The `_currentIndex` logic needs to map to actual screen indices, not tab positions.

---

### Phase 7 — Flutter: Gate the AppDrawer

**File:** `lib/widgets/app_drawer.dart`

Pass `PermissionService.instance` into the drawer and conditionally show/hide sections:

```dart
// OPD section — only if can view patients
if (p.can(Perm.opdPatientView)) ...[
  _SmallAccordion(title: 'OPD', items: [...]),
]

// Queue Dashboard — only for doctors/examiners (matches web: opd.exam.primary|opd.exam.secondary)
if (p.can(Perm.opdExamPrimary) || p.can(Perm.opdExamSecondary)) ...[
  _DrawerItem(label: 'Queue Dashboard', ...),
]

// OT section — only if has any OT permission
if (p.canModule('ot')) ...[
  // OT Bookings: ot.booking.create OR ot.patient.list
  if (p.can(Perm.otBookingCreate) || p.can(Perm.otPatientList))
    _DrawerItem(label: 'OT Bookings', ...),

  // Accountant/Billing: ot.payment.record
  if (p.can(Perm.otPaymentRecord))
    _DrawerItem(label: 'Accountant / Billing', ...),

  // Ward Management: ot.ward.entry
  if (p.can(Perm.otWardEntry))
    _DrawerItem(label: 'Ward Management', ...),

  // Doctor Dashboard: ot.surgery.record (web uses permission:ot.surgery.record)
  if (p.can(Perm.otSurgeryRecord))
    _DrawerItem(label: 'Doctor Dashboard', ...),

  // Assistant Dashboard: ot.lens.record OR ot.lens.implant OR ot.patient.list
  //   (web uses permission:ot.lens.record|ot.lens.implant|ot.patient.list)
  if (p.can(Perm.otLensRecord) || p.can(Perm.otLensImplant) || p.can(Perm.otPatientList))
    _DrawerItem(label: 'Assistant Dashboard', ...),

  // Discharge & Invoices: ot.billing.manage (web uses permission:ot.billing.manage)
  if (p.can(Perm.otBillingManage))
    _DrawerItem(label: 'Discharge & Invoices', ...),
]

// Reports — only if can view reports
if (p.can(Perm.reportsView)) ...[
  _FullAccordion(label: 'Reports', ...),
]

// Config — only show items user has access to
// Masters: master.case_types OR master.eye_exam (web landing page: permission:master.case_types|master.eye_exam)
// OT Masters (slots/types/surgery-types/charge-heads): isSuper only (web uses role:admin middleware)
// Settings: settings.hospital
// Roles & Permissions: master.roles
// Users: master.doctors OR master.receptions OR master.ot_staff
```

---

### Phase 8 — Flutter: Gate screen-level actions

**File:** `lib/screens/patients_screen.dart`

Hide/show action buttons based on permissions:

```dart
// Register button — only show if can register
if (PermissionService.instance.can(Perm.opdPatientRegister))
  FloatingActionButton(onPressed: _registerPatient, ...),

// Per-patient action buttons
if (PermissionService.instance.can(Perm.opdExamPrimary))
  _ActionButton('Primary Exam', onTap: _openPrimaryExam),

if (PermissionService.instance.can(Perm.opdExamSecondary))
  _ActionButton('Secondary Exam', onTap: _openSecondaryExam),

if (PermissionService.instance.can(Perm.opdBillPrint))
  _ActionButton('Print Bill', onTap: _printBill),
```

**File:** `lib/screens/masters_screen.dart`

Each master sub-section tile should only appear if user has that `master.*` permission:

```dart
if (PermissionService.instance.can(Perm.masterEyeExam))
  _MasterTile('Eye Exam Masters', ...),

if (PermissionService.instance.can(Perm.masterCaseTypes))
  _MasterTile('Case Types', ...),
// etc.
```

---

## 9. Files to Create / Modify

### Backend (Laravel)

| File | Change |
|---|---|
| `app/Http/Controllers/Api/AuthController.php` | Add `permissions` to `formatUser()`, eager-load `role.grantedPermissions` in `login()` and `me()` |

### Flutter (Mobile App)

| File | Type | Change |
|---|---|---|
| `lib/models/auth_models.dart` | Modify | Add `permissions: List<String>` to `RoleInfo` |
| `lib/services/auth_service.dart` | Modify | Add `refreshSession()` method |
| `lib/services/permission_service.dart` | **New** | Singleton permission checker |
| `lib/constants/permissions.dart` | **New** | All permission key constants |
| `lib/screens/splash_screen.dart` | Modify | Call `refreshSession()` + load `PermissionService` |
| `lib/screens/login_screen.dart` | Modify | Load `PermissionService` after successful login |
| `lib/screens/home_screen.dart` | Modify | Dynamic bottom nav + AppLifecycleState observer |
| `lib/widgets/app_drawer.dart` | Modify | Conditional drawer items |
| `lib/screens/patients_screen.dart` | Modify | Gate action buttons |
| `lib/screens/masters_screen.dart` | Modify | Gate master sub-sections |
| `lib/screens/users_screen.dart` | Modify | Gate if no user management permission |
| `lib/screens/settings_screen.dart` | Modify | Gate if no `settings.hospital` permission |
| `lib/screens/reports_screen.dart` | Modify | Gate if no `reports.view` permission |

**Total: 1 backend file + 13 Flutter files**

---

## 10. Detailed Phase Breakdown

### Execution Order

```
Phase 1  →  Backend: permissions in API response
Phase 2  →  Flutter: RoleInfo model update
Phase 3  →  Flutter: PermissionService (new file)
Phase 4  →  Flutter: Permissions constants (new file)
Phase 5  →  Flutter: Auth flow fix (SplashScreen + LoginScreen + AuthService)
Phase 6  →  Flutter: HomeScreen bottom nav gating
Phase 7  →  Flutter: AppDrawer gating
Phase 8  →  Flutter: PatientsScreen action button gating
Phase 9  →  Flutter: MastersScreen sub-section gating
Phase 10 →  Flutter: UsersScreen, SettingsScreen, ReportsScreen gating
```

### Why this order matters

- Phase 1 must come first — Flutter phases 2-10 depend on permissions being in the API response
- Phase 2 must come before Phase 3 — `PermissionService.load()` takes a `RoleInfo`
- Phase 3+4 must come before Phase 5 — splash needs `PermissionService`
- Phase 5 must come before Phase 6-10 — gating only works if permissions are loaded

### Testing each phase

| Phase | How to test |
|---|---|
| 1 | Call `POST /auth/login` via Postman — verify `permissions` array in response |
| 2 | Login in app — check `SharedPreferences` stored user JSON has `permissions` array |
| 3+4 | Add debug print: `PermissionService.instance.can('opd.exam.primary')` |
| 5 | Kill app, revoke token from web, reopen app — should go to login |
| 6 | Login as Doctor — Patients tab visible, Masters tab hidden |
| 7 | Login as Receptionist — OT section hidden in drawer |
| 8 | Login as Doctor — no Register button on patients screen |
| 9 | Login as Receptionist — only `master.receptions` tile visible in Masters |
| 10 | Login as Doctor — Settings and Users not accessible |

---

## 11. Edge Cases & Decisions

### Edge Case 1: User has NO permissions at all

If a custom role has zero permissions granted, the user will see only the Dashboard tab and an empty drawer. This is correct behavior — admin should assign at least some permissions before assigning the role to a user.

**Decision:** Show a friendly message on Dashboard: *"Your role has no permissions configured. Please contact your administrator."*

---

### Edge Case 2: Permissions change while user is logged in

**Scenario:** Admin changes Doctor's permissions on web dashboard. Doctor is currently using the mobile app.

**Behavior with our implementation:**
- In-memory `PermissionService` still has old permissions
- Next API call that requires the removed permission → server returns `403`
- On next app resume → `_silentRefresh()` calls `GET /auth/me` → gets fresh permissions → `PermissionService` updated
- UI re-renders with new permissions (if using `setState` or `ValueNotifier`)

**Decision:** Accept this behavior. Permissions are refreshed on every app resume. For immediate effect, user can background and foreground the app, or logout and login again.

---

### Edge Case 3: Network error during splash refresh

**Scenario:** User opens app with no internet connection.

**Behavior:**
- `refreshSession()` throws `SocketException`
- Catch block uses cached `SharedPreferences` user + permissions
- User proceeds to HomeScreen with last-known permissions
- Any API calls will fail with network error (existing behavior)

**Decision:** Offline grace — use cached permissions. This is correct for a hospital app where staff may be in areas with poor connectivity.

---

### Edge Case 4: `is_super = true` user

**Behavior:**
- Backend sends `"permissions": ["*"]` in response
- `RoleInfo.fromJson` stores `["*"]` in permissions list
- `PermissionService.load()` sets `_isSuper = true`
- `PermissionService.can()` returns `true` for everything without checking the list
- All tabs, all drawer items, all buttons visible

**Decision:** `isSuper` flag is the primary check. The `["*"]` array is just a signal — the actual bypass is via `_isSuper = true`.

---

### Edge Case 5: Role is null (user has no role assigned)

**Scenario:** A user account exists but has no role assigned (shouldn't happen but possible).

**Behavior:**
- `UserInfo.role` is `null`
- `PermissionService.load(null)` → `_isSuper = false`, `_permissions = []`
- User sees only Dashboard
- All API calls requiring permissions → `403`

**Decision:** Same as Edge Case 1 — show "no permissions" message on Dashboard.

---

### Edge Case 6: Bottom nav index shift when tabs are hidden

**Problem:** Currently `_currentIndex` maps directly to tab position (0=Home, 1=Patients, etc.). If Patients tab is hidden, the index mapping breaks.

**Solution:** Use a `List<_NavConfig>` where each config carries both the display index AND the screen index. Navigation uses screen index, not tab position.

```dart
class _NavConfig {
  final int screenIndex;  // which screen to show (0-4)
  final int tabIndex;     // position in visible tab bar
  final IconData icon;
  final String label;
}
```

---

### Edge Case 7: Deep link / direct navigation to gated screen

**Scenario:** Some code does `Navigator.push(PrimaryExamScreen(...))` directly without checking permissions.

**Solution:** Each gated screen should also check permission in its own `initState` and show an "Access Denied" widget if the user doesn't have permission. This is a secondary defense — the primary defense is hiding the navigation entry points.

```dart
@override
void initState() {
  super.initState();
  if (!PermissionService.instance.can(Perm.opdExamPrimary)) {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Navigator.of(context).pop();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Access denied: Primary Exam')),
      );
    });
  }
}
```

---

### Decision: `PermissionService` as singleton vs passed via constructor

**Options:**
- A) Singleton (like `ExamMastersService`) — `PermissionService.instance.can(...)`
- B) Passed via constructor to each screen
- C) `InheritedWidget` / `Provider`

**Decision: Singleton (Option A)**

Reasons:
- Consistent with existing `ExamMastersService` and `AuthService` patterns in this codebase
- No need to thread it through every widget constructor
- Permissions don't change frequently — no need for reactive rebuild on every permission check
- Simple, readable: `PermissionService.instance.can(Perm.opdExamPrimary)`

For the AppLifecycleState refresh case where we need UI to rebuild after permissions change, we can use a `ValueNotifier<int>` counter that increments on refresh, and wrap `HomeScreen` body in a `ValueListenableBuilder`.

---

### Decision: What to show when a tab/screen is accessed without permission

**Options:**
- A) Hide the tab/button entirely (user never sees it)
- B) Show the tab but display "Access Denied" screen when tapped
- C) Show the tab grayed out with a lock icon

**Decision: Option A (hide entirely)**

Reasons:
- Cleaner UX — non-admin staff shouldn't see features they can't use
- Consistent with web dashboard behavior (menu items are hidden, not disabled)
- Simpler implementation

Exception: If a user navigates directly (deep link or code bug), show a brief snackbar "Access denied" and pop back.

---

## Summary

| Item | Count |
|---|---|
| Backend files to change | 1 |
| New Flutter files | 2 (`permission_service.dart`, `permissions.dart`) |
| Flutter files to modify | 11 |
| Total permissions in system | 52 |
| System roles | 7 (1 super + 6 regular) |
| Implementation phases | 10 |

**The single most important change:** Adding `permissions` array to `AuthController::formatUser()` on the backend. Everything else in Flutter depends on this.

**The single most important Flutter change:** `PermissionService` singleton — once this exists, all gating becomes one-liners: `if (PermissionService.instance.can(Perm.opdExamPrimary))`.