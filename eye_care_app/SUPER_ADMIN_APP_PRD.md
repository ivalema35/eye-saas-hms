# Product Requirements Document — Super Admin Module (Mobile App)

**Document Version:** 1.0
**Date:** 2026-07-16
**App:** `eye_care_app` (Flutter, path: `J:\all_folder_of_C_drive\eye_care_whole\eye_care_app`)
**Backend:** `eye-saas-hms` (Laravel 13, path: `J:\laragon\www\eye_care_new_clone\eye-saas-hms`)
**Companion document:** `SUPER_ADMIN_APP_DESIGN.md` — screen-by-screen design spec (hand this file alone to a designer/design-AI).

> **Source-of-truth note:** This PRD is built from a fresh, line-by-line read of the current backend code (routes, controllers, models, migrations, blade views) and the current Flutter app code — not from the `.md` planning docs already sitting in either repo (`RBAC_IMPLEMENTATION_PLAN.md`, `OPTIMIZATION_PRD.md`, `EXAM_*`, `MOBILE_API_MERGE_PLAN.md`, etc.). Those are historical working notes, not treated as authoritative here.

---

## 0. CRITICAL CLARIFICATION — READ FIRST

There are **two completely different "admin" concepts** in this system. Do not conflate them:

| | **Hospital Admin** | **Super Admin** (this PRD) |
|---|---|---|
| Scope | One single hospital (tenant) | The entire platform, across ALL hospitals |
| Backend model | `App\Models\Hospital\HospitalUser` with `role.is_super = true` | `App\Models\Platform\PlatformAdmin` |
| Backend guard | `hospital_user` (web) / `api` Sanctum (mobile) | `superadmin` (web, session-only today) |
| Login URL (web) | `/{slug}/login` | `/superadmin/login` |
| Already in mobile app? | **Yes** — fully implemented (`RoleInfo.isSuper`, `PermissionService`, gates the existing app's nav) | **No — does not exist in the mobile app at all today.** This is 100% new work. |
| What it manages | Patients, exams, OT, staff, roles *within one hospital* | Hospitals themselves (tenants), billing, subscriptions, platform-wide settings, cross-tenant masters |

The existing mobile app's `isSuper` flag, `PermissionService`, and `Perm` constants are **entirely unrelated** to this PRD — they gate the Hospital Admin's in-tenant permissions. Nothing in this document reuses or extends that system. **A user can be a Hospital Admin, a Super Admin, or both — they are different accounts in different tables.**

---

## 1. Executive Summary & Goals

Build a **Super Admin section inside the existing `eye_care_app` Flutter app** that gives the platform owner (you) the same capabilities as the web SuperAdmin panel (`hmssaas.com/superadmin/...`), from a phone: onboarding new hospitals, activating/suspending them, tracking payments and subscriptions, sending platform-wide notifications, managing global masters (locations, medicines), and configuring platform settings.

**Goals:**
1. **Full parity** with every SuperAdmin web feature (§6.12 / full detail in §7 of this doc).
2. **Integrated, not a separate app** — added to the same `eye_care_app` codebase, reachable via a distinct login path, using the app's existing design system (`AppColors`/`AppRadius`/`AppSpacing`/`AppTextStyles`/shared widgets) but visually distinct so it's never confused with a hospital's tenant-themed session.
3. **New backend API surface** — today only `GET /api/v1/super/tenants` (list) and `GET /api/v1/super/tenants/{id}` (show) exist, and even those authenticate against the *wrong* model (see §9.1). Everything else is web/session-only and must be built from scratch.
4. **Clean session isolation** — a Super Admin session and a Hospital Staff session must never leak into each other (different token storage keys, different theme source, different API base paths).

**Non-goals (v1):**
- Public hospital self-registration flow (web-only, out of scope).
- Two-factor auth / IP allow-listing (not present on web either — flagged as a future hardening item, not blocking).
- Offline mode — Super Admin screens are online-only, same as the rest of the app today.
- Excel import for Location Master / Medicine Master (web-only for v1 — flagged as a P2, see §12).

---

## 2. Current State Analysis

### 2.1 Backend (web) — what exists today
The web SuperAdmin panel (`app/Http/Controllers/SuperAdmin/*`, guard `superadmin`, model `PlatformAdmin`) is a **fully built, session-based Blade panel** covering: Dashboard (KPIs + 4 charts), Hospitals (Tenants) CRUD + lifecycle actions, Payments (+ manual offline recording + PDF invoices), Subscriptions (read-only), Audit Logs (read-only), Notifications (broadcast composer), Platform Settings, Plans (pricing), Profile, Location Master (Country→State→District→City, 4-tab CRUD), Medicine Master (global catalog, 5-tab CRUD that cascades into every tenant's own medicine list). Full inventory with exact fields/validation is in §7 and §9.

### 2.2 Backend (API) — what exists today
Only this, in `routes/api.php`:
```
GET /api/v1/super/tenants        → Api\SuperAdmin\TenantApiController@index
GET /api/v1/super/tenants/{id}   → Api\SuperAdmin\TenantApiController@show
```
Both endpoints authenticate via `auth('sanctum')->user()` and authorize by checking `$user->role?->is_super` — i.e. they check a **hospital-side** `HospitalUser`'s role flag, not `PlatformAdmin` at all. There is currently **no way for a `PlatformAdmin` to obtain an API token** — the `superadmin` guard is `driver: session` only (`config/auth.php`). This is the single biggest backend gap and is addressed in §9.1.

### 2.3 Mobile app — what exists today
Nothing. Confirmed by exhaustive grep: no `super_admin`/`platform_admin`/`tenant` screens, services, or models exist anywhere in `lib/`. The only "super" concept in the app is `RoleInfo.isSuper` (Hospital Admin, tenant-scoped — see §0). The app's whole config model (`AppConfig.slug`, `AppConfig.hospitalApiUrl`) assumes exactly one active hospital context, which a platform-wide Super Admin session must NOT depend on.

### 2.4 What this means
This is **greenfield work on both ends** — new Laravel API controllers/routes/middleware, and new Flutter screens/services/models — using the web panel as the exact functional spec.

---

## 3. Personas & Roles

`platform_admins.role` enum: `super_admin` (default), `support`.

| Role | v1 behavior |
|---|---|
| `super_admin` | Full access to every module in this PRD. |
| `support` | **Backend currently does NOT differentiate `support` from `super_admin` anywhere** — no controller checks the `role` column. For v1, treat both identically (ship what the backend enforces). Flagged in §11 as a real gap: if you want `support` to be read-only or restricted, that authorization logic needs to be added on the backend first — the mobile app should not invent client-side-only restrictions that the API doesn't also enforce, since that's a false sense of security. |

Only one persona type exists for this whole module — unlike the Hospital side there is no granular permission-checkbox system for Super Admin.

---

## 4. Architecture Decisions

### 4.1 Integrated into the same app, isolated session
- Add a **"Super Admin Login"** entry point reachable from the existing `LoginScreen` (a small text link, e.g. bottom of the form: "Platform Super Admin? Login here").
- New screens live under a **separate shell**, `PlatformHomeScreen`, with its own bottom nav / drawer — structurally parallel to today's `HomeScreen`, not a tab bolted onto it (a Super Admin is never "inside" a hospital's tenant context).
- **Separate local storage keys** so a device can't cross-contaminate sessions: `platform_admin_token`, `cached_platform_admin`, distinct from the existing `auth_token`/`cached_user`/`hospital_slug`. Logging out of one session must not touch the other's stored keys.
- **No per-hospital theming.** The existing `AppColors.applyTheme(ClientTheme)` mechanism is tenant-specific (reads `hospital.primaryColor` etc. from a hospital login response) and must never run in Platform mode. Platform screens use a **fixed, separate palette** (`PlatformColors` — see Design doc) so a Super Admin's UI never inherits a leftover hospital's theme override, and vice versa.

### 4.2 New API namespace
All new endpoints live under the existing `/api/v1/super/*` prefix (already established for `TenantApiController`), reusing the name but **fixing the auth** (see §9.1) rather than inventing a new prefix. Full catalog in §9.

### 4.3 File/folder additions (follow the existing layer-first convention exactly — no new `lib/platform/` subtree)
Every new file gets a `platform_` prefix to avoid collisions with existing hospital-scoped files of similar names (e.g. there's already a `medicine_models.dart` — platform's global medicine catalog is `platform_medicine_master_models.dart`).

```
lib/models/
  platform_admin_models.dart        (PlatformAdmin, LoginResult)
  platform_tenant_models.dart       (Tenant, TenantSummary, TenantDetail)
  platform_dashboard_models.dart
  platform_payment_models.dart
  platform_subscription_models.dart
  platform_audit_log_models.dart
  platform_notification_models.dart
  platform_settings_models.dart
  platform_plan_models.dart
  platform_location_models.dart     (Country/State/District/City)
  platform_medicine_master_models.dart

lib/services/
  platform_auth_service.dart
  platform_dashboard_service.dart
  platform_tenant_service.dart
  platform_payment_service.dart
  platform_subscription_service.dart
  platform_audit_log_service.dart
  platform_notification_service.dart
  platform_settings_service.dart
  platform_plan_service.dart
  platform_location_service.dart
  platform_medicine_master_service.dart

lib/screens/
  platform_login_screen.dart
  platform_home_screen.dart          (shell: bottom nav + drawer)
  platform_dashboard_screen.dart
  platform_hospitals_screen.dart     (list)
  platform_hospital_form_screen.dart (create + edit, one file, mode param)
  platform_hospital_detail_screen.dart
  platform_payments_screen.dart
  platform_subscriptions_screen.dart
  platform_audit_logs_screen.dart
  platform_notifications_screen.dart
  platform_settings_screen.dart
  platform_plans_screen.dart
  platform_location_master_screen.dart  (4-tab)
  platform_medicine_master_screen.dart  (5-tab)
  platform_profile_screen.dart

lib/constants/
  platform_colors.dart               (fixed palette, NOT theme-overridable)

lib/config/
  app_config.dart  (EXTEND — add platformApiUrl getter, see §4.4)
```

New service classes follow the exact existing convention: private constructor singleton (`X._()` / `static final X instance = X._()`), `with AuthenticatedService`-equivalent mixin for headers — except the token source differs (Platform token, not hospital token), so add a small `PlatformAuthenticatedService` mixin in `platform_auth_service.dart` mirroring `base_service.dart`'s `AuthenticatedService` but reading `platform_admin_token`.

### 4.4 `AppConfig` extension
```dart
// app_config.dart — add:
static const String platformApiUrl = '$apiBaseUrl/super';
```
Platform screens call `AppConfig.platformApiUrl` (no `{slug}` segment — a Super Admin is not tenant-scoped), exactly parallel to how hospital screens call `AppConfig.hospitalApiUrl`.

### 4.5 New third-party dependency needed
The web Dashboard renders 4 Chart.js charts (bar, doughnut, line/area, pie). The Flutter app currently has **no charting package**. Add **`fl_chart`** (well-maintained, no other new transitive deps) to `pubspec.yaml`. This is the only new dependency required for full parity; Excel import (Location/Medicine Master) is deliberately deferred (§12) so `file_picker`/multipart upload isn't needed for v1.

---

## 5. Information Architecture

**Entry:** `LoginScreen` → "Platform Super Admin? Login here" link → `PlatformLoginScreen` (email + password only — no "find hospital" step, since a Super Admin isn't tenant-scoped).

**Shell:** `PlatformHomeScreen` — bottom nav (4 items, mirrors the web sidebar's top-level grouping) + drawer for the rest:

| Bottom tab | Screens reachable |
|---|---|
| **Dashboard** | `PlatformDashboardScreen` |
| **Hospitals** | `PlatformHospitalsScreen` → detail → edit/create |
| **Billing** | `PlatformPaymentsScreen`, `PlatformSubscriptionsScreen` (segmented control between the two) |
| **More** | Drawer: Audit Logs, Notifications, Plans, Location Master, Medicine Master, Settings, My Profile, Logout |

This mirrors the web sidebar's 4 groups (Dashboard / Management / Masters / System) collapsed into a mobile-appropriate 4-tab + drawer pattern, matching how the existing Hospital app already uses a "More" tab for lower-frequency screens (see `home_screen.dart`'s `_visibleNavItems`).

---

## 6. Data Model Reference (backend, authoritative — mirror exactly in Dart models)

### `Tenant` (`tenants` table)
`id, name, slug, hospital_code(4), admin_name, admin_email, admin_phone, city, district, state, country, timezone, is_timezone_override, logo_path, status[trial|active|grace|inactive|suspended], trial_ends_at, setup_completed_at, is_setup_done, created_at, updated_at, deleted_at`

### `PlatformAdmin` (`platform_admins` table)
`id, name, email, role[super_admin|support], last_login_at, last_login_ip, created_at, updated_at` (password hidden)

### `Subscription` (`subscriptions` table)
`id, tenant_id, cycle[monthly|quarterly|yearly], price, original_price, starts_at, ends_at, grace_ends_at, razorpay_sub_id, razorpay_plan_id, status[active|cancelled|expired]`

### `Payment` (`payments` table)
`id, tenant_id, amount, cycle[monthly|quarterly|yearly], method[online|offline], gateway, transaction_id, razorpay_order_id, razorpay_signature, status[pending|success|failed], paid_at, invoice_path, notes`

### `AuditLog` (`audit_logs` table, immutable — no `updated_at`)
`id, admin_id, tenant_id(nullable), action, description, ip_address, old_values(json), new_values(json), created_at`
Known `action` values: `hospital.created.manual`, `hospital.updated`, `hospital.activated`, `hospital.suspended`, `hospital.grace.extended`, `hospital.masters.reseeded`, `hospital.reactivated`, `hospital.archived`, `payment.offline.recorded`.

### `PlatformSetting` (`platform_settings` table, key-value)
Known keys: `platform_name, support_email, trial_days, razorpay_key(enc), razorpay_secret(enc), razorpay_webhook_secret(enc), mail_host, mail_port, mail_username, mail_password(enc), mail_from_name, mail_from_email, monthly_price, quarterly_discount, yearly_discount, grace_days, plan_features(json array)`

### `PlatformNotification` (`platform_notifications` table)
`id, tenant_id(nullable), type, subject, recipient_email, status[sent|failed|pending], sent_at, error_message`

### Location hierarchy (`tbl_master_countries/states/districts/cities`)
Country: `id, name, default_timezone, is_active`
State: `id, country_id, name, is_active`
District: `id, state_id, name, is_active`
City: `id, state_id, district_id(nullable), name, is_active`

### Medicine master (`tbl_master_dosages/medicine_types/medicine_categories/medicine_routes/medicines`)
Dosage/Type/Category/Route: `id, name (or 'dosage' for dosage), is_active`
Medicine: `id, master_medicine_type_id, master_dosage_id, name, duration, qty, composition, company, price, is_active`

---

## 7. Detailed Functional Requirements

Each module below: **web behavior** (source of truth, already built) → **mobile screen(s)** → **API endpoint(s) needed** (new unless marked existing). Full endpoint catalog with request/response shapes is in §9.

### 7.1 Authentication

| # | Requirement |
|---|---|
| FR-PA-01 | Login: email + password → `PlatformLoginScreen`. **No hospital-discovery step.** API: `POST /api/v1/super/auth/login`. On success: store token as `platform_admin_token`, `PlatformAdmin` as `cached_platform_admin`, navigate to `PlatformHomeScreen`. |
| FR-PA-02 | Session restore: on app start, if a `platform_admin_token` exists (and the app was last in Platform mode), call `GET /api/v1/super/auth/me` to refresh; fall back to cached admin on network failure, same offline-grace pattern as the existing `AuthService.restoreSession()`. |
| FR-PA-03 | Logout: `POST /api/v1/super/auth/logout` (revokes token server-side), clear only the `platform_admin_*` keys, return to `LoginScreen`. |
| FR-PA-04 | 401 handling: any Platform API call returning 401 forces logout + redirect to `PlatformLoginScreen` (same as hospital side's stated intent — this is a good place to actually add the centralized interceptor missing on the hospital side, scoped just to this new mixin). |
| FR-PA-05 (NEW backend + app) | Forgot password: web has zero implementation despite a configured password broker (§9.5 gap). **Not in v1 scope** — flag to backend team; add later once the web side builds it. |

### 7.2 Dashboard
Mirrors `SuperAdminDashboardController@index` exactly:

| # | Requirement |
|---|---|
| FR-PD-01 | 4 stat cards, row 1: Total Hospitals, Active, On Trial, Grace Period. Row 2: Inactive+Suspended combined, Monthly Recurring Revenue, Revenue This Month (web shows this as a literal duplicate of MRR — reproduce as-is for parity, don't "fix" silently), Expiring This Week (badge if >0, "Needs attention"). |
| FR-PD-02 | Revenue Trend chart — bar, last 6 months, ₹ formatted (`en-IN` grouping). |
| FR-PD-03 | Status Distribution chart — donut, hospital counts by status, with a custom legend row below (not the chart library's built-in legend) matching web. |
| FR-PD-04 | New Registrations chart — line/area, last 6 months. |
| FR-PD-05 | Subscription Cycles chart — pie, active subscriptions by cycle. |
| FR-PD-06 | Recently Registered Hospitals — list of 8 most recent, tap → `PlatformHospitalDetailScreen`. |
| FR-PD-07 | Pull-to-refresh; cache-first paint pattern like the existing `dashboard_screen.dart` (instant paint from local cache, then background refresh) is a reasonable UX carry-over, not mandatory for v1. |

### 7.3 Hospitals (Tenants) — the core module
Mirrors `TenantController` fully.

| # | Requirement |
|---|---|
| FR-PH-01 | List: search (name/slug/email/city) + status filter (trial/active/grace/inactive/suspended), paginated 20/page. |
| FR-PH-02 | Create hospital — fields & validation (must match server exactly, client-side mirror only, server is authoritative): `hospital_name` (required, 3–100), `slug` (required, 3–30, `^[a-z0-9\-]+$`, must not be a reserved word: `superadmin, admin, register, login, pricing, api, health, webhook`), `admin_name` (required, ≤100), `admin_email` (required, valid email), `admin_phone` (required, exactly 10 digits), `password` (required, min 8 — this sets the auto-created Hospital Admin's login password), `city`/`state` (optional), `plan` (monthly/quarterly/yearly — informational only, does not affect billing at creation; every new hospital gets a 14-day trial regardless). Auto-generate slug suggestion from name (lowercase, hyphenate) but keep it editable. |
| FR-PH-02a | **⚠️ Known backend bug to resolve before/alongside building this screen:** `TenantController@store` server-side validation requires `hospital_code` (3-letter code), but the web create form doesn't even collect it — meaning hospital creation is currently broken via the form's literal field set, or `hospital_code` is being supplied some other way not visible in this exploration. **Confirm with the backend owner how `hospital_code` should be generated/entered before finalizing this screen** — options: (a) auto-generate server-side and drop it from client validation, or (b) add a `hospital_code` field to this mobile form (3 letters, regex `^[A-Za-z]{3}$`, globally unique) and request the web form + validation be reconciled together. Do not silently work around this without confirming — it affects whether hospital creation succeeds at all. |
| FR-PH-03 | Hospital detail: mini stat bar (Status, Trial Ends, Subscriptions count, Total Paid), Hospital Info card, Quick Actions (Activate / Suspend / Reactivate / Re-seed Masters / Extend Grace / Archive — each conditionally shown based on current `status`, matching web's exact conditions in §6.9 of the research), Subscription History table, Payment History table, "Open Portal" action (opens `{slug}` hospital login in an in-app browser/webview or copies the URL — decide based on whether Super Admins need to actually operate inside a hospital account from their phone; recommend copy-link + "open in browser" rather than embedding a webview, simpler and avoids session conflicts with the Platform token). |
| FR-PH-04 | Edit hospital: `name`, `slug` (warn: changing it changes the tenant's login URL), `admin_name`, `admin_phone`, `city`, `state`. `admin_email` is **read-only** (matches web — no email-change flow exists anywhere in the system yet). |
| FR-PH-05 | Activate / Suspend / Reactivate — single-tap actions with confirm dialogs (Suspend needs a stronger confirm, matches web's JS `confirm()`). |
| FR-PH-06 | Extend Grace — number input `days` (1–90, default 7). |
| FR-PH-07 | Re-seed Default Masters — confirm dialog, dispatches a queued job server-side (fire-and-forget from the app's perspective; show a success toast, not a wait-for-completion spinner). |
| FR-PH-08 | Archive (soft delete) — strong confirm ("data retained 30 days"), removes from active list. |
| FR-PH-09 | Every mutating action on this screen writes an `AuditLog` server-side automatically (no client action needed — just be aware detail/audit screens will reflect these). |

### 7.4 Payments

| # | Requirement |
|---|---|
| FR-PP-01 | List: 3 stat cards (Total Revenue, This Month, Pending Count), filters (status: success/pending/failed; method: online/offline; date range), paginated 25/page. |
| FR-PP-02 | Record Offline Payment (bottom sheet form): Hospital picker (non-suspended tenants only), Amount (₹, min 1, max 999999), Cycle (monthly/quarterly/yearly), Transaction/Cheque # (optional), Notes (optional, ≤500). On success this **also activates the hospital** server-side (`TenantService::activate()`) — surface this clearly in the UI ("Recording this payment will also activate the hospital if it isn't already active"). |
| FR-PP-03 | Invoice download/share — PDF, generated on-demand if missing; use the app's existing PDF-viewer pattern (`pdf`/`printing` packages already in `pubspec.yaml`) for in-app preview + share sheet. |
| FR-PP-04 | **Known inconsistency to be aware of, not to silently "fix" client-side:** the cycle price labels shown in the web "Record Offline Payment" modal (₹999/₹2,427/₹9,590) are hardcoded and do **not** reflect whatever the Super Admin has set on the Plans screen — reproduce this same static display for parity, but don't wire it to `platform_settings` unless the backend is also fixed to do so (see §11 gap #3). |

### 7.5 Subscriptions
Read-only. List with 3 stat cards (Total/Active/Expired), table (Hospital, Plan/Cycle, Status, Starts, Ends), tap row → `PlatformHospitalDetailScreen`. No create/renew/cancel actions exist on the web SuperAdmin side either (renewal is system/webhook-driven) — do not add write actions here that the backend doesn't support.

### 7.6 Audit Logs
Read-only. Filters: action (text search), hospital dropdown, date range. List shows action (color-coded by keyword: created=green, activated=blue, suspended=red, archived=orange, payment=green, default=gray), description, hospital (or "Platform" if null), admin name, IP, timestamp. Paginated 25/page.

### 7.7 Notifications (Broadcast)

| # | Requirement |
|---|---|
| FR-PN-01 | Compose: Subject (required, ≤255), Message (required, ≤5000, multiline), Recipients (All / Specific), if Specific → multi-select of tenants (non-suspended, has admin_email), live "Sending to: N hospital(s)" counter, confirm before send. |
| FR-PN-02 | History: last 50 broadcast notifications — hospital, subject, recipient email, status (Sent/Failed w/ error/Pending), sent at. |
| FR-PN-03 | Note for awareness: this history intentionally excludes the 10 subscription-lifecycle email types (`trial_welcome`, `reminder_7d`, etc.) — those are system-triggered, not Super-Admin-composed, and the web itself doesn't show them here either. Don't add them without a corresponding backend change and explicit product decision. |

### 7.8 Platform Settings
Single form, 4 groups: General (platform name, support email, trial days), Razorpay (key/secret/webhook secret — password-style fields, blank = keep existing, values are encrypted server-side), Email/SMTP (host/port/username/password/from name/from email — password blank = keep existing), Subscription Pricing (monthly base price, quarterly/yearly discount %). Reproduce the web's blank-means-unchanged semantics precisely for the 4 secret fields — do not send empty string as a value that would clobber a stored secret.

### 7.9 Plans (Pricing)
Live preview of 3 pricing tiers (computed from settings) + editable form: Monthly Price, Quarterly Discount %, Yearly Discount %, Trial Days, Grace Period Days, and a **dynamic Features list** (add/remove rows — the one repeater-style input in the whole module). This writes to the *same* `platform_settings` keys as §7.8's pricing block — be aware both screens can independently edit overlapping data (a pre-existing web inconsistency, §11 gap #3); don't try to "reconcile" them into one screen without a product decision, since that changes scope beyond parity.

### 7.10 Profile
Two sections: Account Info (name, email, read-only role, "last login X ago from IP" if present — though note §11 gap #5: this field is never actually written by the backend today, so it will likely always be empty), Change Password (current + new + confirm, new password policy: min 8, mixed case, numbers).

### 7.11 Location Master
4 tabs: Countries, States, Districts, Cities — cascading hierarchy, each a full CRUD list (paginate 10/page) + Add/Edit forms + active/inactive toggle (instant, no confirm) + Delete (confirm — warn cascade deletes children for Country/State; District delete instead nulls out `district_id` on its cities rather than deleting them, so word that delete-confirm differently for District). Country form includes a searchable Timezone picker (grouped by region) — editing a country's default timezone cascades to every tenant using that country whose `is_timezone_override = false` (warn about this in the edit confirm). Cross-filtering (State list filterable by Country, etc.) via cascading dropdowns.

**Excel import is web-only for v1** (see §4.5) — show a "not available on mobile yet, use the web panel" message rather than omitting the feature silently, so it's discoverable.

### 7.12 Medicine Master (Global Catalog)
5 tabs: Dosages, Medicine Types, Medicine Categories, Routes, Medicines. Same CRUD+toggle+delete-confirm pattern as Location Master. **Important behavior to preserve:** creating/renaming a Dosage/Type/Category/Route **cascades down into every tenant's own copy** of that master (so hospital admins immediately see it) — deletes do **not** cascade (a hospital medicine may already reference the row) — surface this in the delete confirm copy ("This won't remove it from hospitals already using it"). Medicine CRUD fields: Type (dropdown), Dosage (dropdown), Name, Duration, Qty, Composition, Company, Price. Excel import/sample-download: web-only for v1, same as §7.11.

---

## 8. Non-Functional Requirements

| Area | Requirement |
|---|---|
| Performance | List endpoints < 2s on 4G; dashboard chart data < 3s |
| Security | Token in the same storage mechanism the app already uses (`SharedPreferences` — consistent with existing app, though note: the existing app itself doesn't use secure storage either; if you want to raise the bar, do it for both sessions at once, not just this one, to avoid an inconsistent security posture across the app) |
| Session isolation | Platform and Hospital sessions must coexist on one device without collision (§4.1) |
| Localization | English only, matches rest of app |
| Platform support | Same targets as existing app (iOS 15+, Android API 26+) |
| Error handling | Reuse `showAppSnackBar` pattern; add the missing centralized 401→logout interceptor for this module (§7.1 FR-PA-04) |

---

## 9. API Specification — Backend Work Required

### 9.1 Auth — the foundational fix (blocks everything else)

**Problem:** `superadmin` guard is `driver: session` (`config/auth.php`). `PlatformAdmin` does not use `HasApiTokens`. The one existing `/api/v1/super/*` pair authenticates against `hospital_users`/`role.is_super`, not `PlatformAdmin` — architecturally wrong for this use case.

**Required backend changes:**
1. Add `use Laravel\Sanctum\HasApiTokens;` to `App\Models\Platform\PlatformAdmin`.
2. New controller `App\Http\Controllers\Api\SuperAdmin\AuthController`:
   - `POST /api/v1/super/auth/login` — validate `email`, `password`; `PlatformAdmin::where('email',...)->first()`; `Hash::check`; issue token via `$admin->createToken('mobile-token')`; update `last_login_at`/`last_login_ip` (closing gap #5 from the research); return `{token, admin}`.
   - `POST /api/v1/super/auth/logout` — `$request->user()->currentAccessToken()->delete()`.
   - `GET /api/v1/super/auth/me` — return current `PlatformAdmin`.
3. New middleware (or inline check) ensuring `$request->user() instanceof \App\Models\Platform\PlatformAdmin` on every `/api/v1/super/*` route below — Sanctum's `auth:sanctum` alone isn't enough to distinguish a hospital-issued token from a platform-issued one, since both are rows in the same `personal_access_tokens` table. Apply this guard middleware to the entire route group.
4. **Fix `TenantApiController`** to use this same check instead of `hospital_users.role.is_super` (its current logic is checking the wrong actor entirely) — or fold its two endpoints into the new `HospitalController` below for consistency.

### 9.2 Full endpoint catalog to build (all under `/api/v1/super/*`, all requiring the `PlatformAdmin` guard from §9.1)

| Module | Method | Path | Mirrors web controller |
|---|---|---|---|
| Auth | POST | `/auth/login` | `SuperAdminAuthController` |
| Auth | POST | `/auth/logout` | " |
| Auth | GET | `/auth/me` | " |
| Dashboard | GET | `/dashboard` | `SuperAdminDashboardController@index` |
| Hospitals | GET | `/hospitals` | `TenantController@index` |
| Hospitals | POST | `/hospitals` | `@store` |
| Hospitals | GET | `/hospitals/{id}` | `@show` |
| Hospitals | PUT | `/hospitals/{id}` | `@update` |
| Hospitals | DELETE | `/hospitals/{id}` | `@destroy` |
| Hospitals | POST | `/hospitals/{id}/activate` | `@activate` |
| Hospitals | POST | `/hospitals/{id}/suspend` | `@suspend` |
| Hospitals | POST | `/hospitals/{id}/extend` | `@extend` |
| Hospitals | POST | `/hospitals/{id}/reactivate` | `@reactivate` |
| Hospitals | POST | `/hospitals/{id}/reseed-masters` | `@reseedMasters` |
| Payments | GET | `/payments` | `PaymentController@index` |
| Payments | POST | `/payments/offline` | `@storeOffline` |
| Payments | GET | `/payments/{id}/invoice` | `@downloadInvoice` |
| Subscriptions | GET | `/subscriptions` | `SubscriptionController@index` |
| Audit Logs | GET | `/audit-logs` | `AuditLogController@index` |
| Notifications | GET | `/notifications` | `NotificationController@index` |
| Notifications | POST | `/notifications/send` | `@send` |
| Settings | GET | `/settings` | `SettingsController@index` |
| Settings | PUT | `/settings` | `@update` |
| Plans | GET | `/plans` | `PlanController@index` |
| Plans | PUT | `/plans/{plan}` | `@update` |
| Profile | GET | `/profile` | `ProfileController@show` |
| Profile | PUT | `/profile` | `@update` |
| Profile | PUT | `/profile/password` | `@updatePassword` |
| Locations | GET | `/locations?tab=` | `LocationMasterController@index` |
| Locations | GET | `/locations/ajax/states` | `@ajaxStates` |
| Locations | GET | `/locations/ajax/districts` | `@ajaxDistricts` |
| Locations | POST/PUT/DELETE/PATCH | `/locations/countries[/{id}[/toggle]]` | `@*Country` |
| Locations | POST/PUT/DELETE/PATCH | `/locations/states[/{id}[/toggle]]` | `@*State` |
| Locations | POST/PUT/DELETE/PATCH | `/locations/districts[/{id}[/toggle]]` | `@*District` |
| Locations | POST/PUT/DELETE/PATCH | `/locations/cities[/{id}[/toggle]]` | `@*City` |
| Medicine Master | GET | `/medicine-master?tab=` | `MedicineMasterController@index` |
| Medicine Master | POST/PUT/DELETE/PATCH | `/medicine-master/dosages[/{id}[/toggle]]` | `@*Dosage` |
| Medicine Master | POST/PUT/DELETE/PATCH | `/medicine-master/types[/{id}[/toggle]]` | `@*Type` |
| Medicine Master | POST/PUT/DELETE/PATCH | `/medicine-master/categories[/{id}[/toggle]]` | `@*Category` |
| Medicine Master | POST/PUT/DELETE/PATCH | `/medicine-master/routes[/{id}[/toggle]]` | `@*Route` |
| Medicine Master | POST/PUT/DELETE/PATCH | `/medicine-master/medicines[/{id}[/toggle]]` | `@*Medicine` |

All validation rules for each endpoint = the exact server-side rules documented in the research (reproduced per-module in §7 above) — the new API controllers should reuse the **same `FormRequest`/inline validation rules already written for the web controllers**, just returning JSON (`{success, data, message}`) instead of redirects/views, consistent with how `Api\PatientApiController` etc. already mirror their Hospital web counterparts elsewhere in this codebase.

Excluded from v1 API (per §7.11/§7.12): Excel import endpoints (`/locations/import`, `/medicine-master/medicines/import`, `/medicine-master/medicines/sample`) — build later if mobile import becomes a real requirement.

---

## 10. Phased Delivery Plan

**Phase 1 — Foundation + Hospitals (core value, ~3–4 weeks)**
- Backend: §9.1 auth fix, Auth endpoints, Hospitals endpoints, Dashboard endpoint.
- App: `PlatformLoginScreen`, `PlatformHomeScreen` shell, `platform_auth_service`, `PlatformDashboardScreen` (numbers only, charts stubbed or basic), `PlatformHospitalsScreen` + detail + create/edit + lifecycle actions.
- Resolve the `hospital_code` bug (§7.3 FR-PH-02a) before shipping Create Hospital.

**Phase 2 — Billing & Oversight (~2–3 weeks)**
- Backend: Payments, Subscriptions, Audit Logs, Notifications endpoints.
- App: `PlatformPaymentsScreen` (+ offline record + invoice PDF), `PlatformSubscriptionsScreen`, `PlatformAuditLogsScreen`, `PlatformNotificationsScreen`.
- Dashboard charts (add `fl_chart`).

**Phase 3 — Platform Configuration (~2 weeks)**
- Backend: Settings, Plans, Profile endpoints.
- App: `PlatformSettingsScreen`, `PlatformPlansScreen`, `PlatformProfileScreen`.

**Phase 4 — Global Masters (~2–3 weeks)**
- Backend: Location Master + Medicine Master endpoints (CRUD + toggle, no import).
- App: `PlatformLocationMasterScreen` (4-tab), `PlatformMedicineMasterScreen` (5-tab).

**Deferred / not in v1:** Excel import (locations, medicines), forgot-password, in-app "Open Portal" webview (use external browser link instead), `support` role restrictions (needs backend authorization work first).

---

## 11. Known Backend Gaps / Inconsistencies (carried over from code audit — resolve or consciously accept before/while building)

1. **No token auth for `PlatformAdmin`** — blocks everything, addressed as Phase 1 priority #1 (§9.1).
2. **`hospital_code` validation/UI mismatch** on hospital creation — confirm resolution before building Create Hospital (§7.3).
3. **Pricing source-of-truth split** — `PlanController`/`SettingsController` write dynamic pricing to `platform_settings`, but `SubscriptionService::calculatePrice()` and the offline-payment cycle-price labels are hardcoded and won't reflect edits. Reproduce as-is for parity; flag to product owner if this should be unified.
4. **Trial-days source-of-truth split** — tenant creation reads `env('SUBSCRIPTION_TRIAL_DAYS')`, not the editable `platform_settings.trial_days`.
5. **`PlatformAdmin.last_login_at/ip`** never written today — fixed as part of §9.1's new login endpoint.
6. **No "Forgot Password"** flow despite configured broker — deferred (§10).
7. **`HospitalShareRequest`** (cross-hospital patient sharing) has zero SuperAdmin UI on web — out of scope for this PRD entirely (it's a hospital-side feature already covered by the existing mobile app's share-history screens).
8. **`SuperAdminAuth.php` middleware** unused dead code on web — not relevant to mobile, no action needed.
9. **Subscriptions screen is read-only** on web — reproduce as read-only, do not add write actions.
10. **Notification history omits subscription-lifecycle emails** — reproduce as-is (§7.7 FR-PN-03).
11. **Admin email is not editable** anywhere in the system — reproduce as read-only in Edit Hospital and Profile.

---

## 12. Testing Requirements

- Laravel Feature tests for every new `/api/v1/super/*` endpoint, reusing the same validation-rule assertions as the existing web `FormRequest` tests where they exist.
- Manual E2E checklist: login as `super_admin` → create hospital → verify it appears in web panel too (shared DB) → activate/suspend/extend/reactivate → record offline payment → verify hospital auto-activates → send broadcast notification → verify audit log entries for every mutating action → edit platform settings (confirm blank secret fields don't clobber existing ones) → toggle a location master row and confirm change is visible on web Location Master page.
- Session isolation test: log into a hospital account AND a Super Admin account on the same device (sequentially), confirm switching between them never shows the wrong session's data, and logging out of one never logs out the other.

---

## 13. Design

See **`SUPER_ADMIN_APP_DESIGN.md`** for the full screen-by-screen design specification (navigation, layout, components, states) — kept as a separate file so it can be handed to a designer or design tool on its own.

---

*End of PRD.*
