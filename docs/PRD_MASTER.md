# Eye SaaS HMS — Master PRD & Codebase Map
**Version:** 1.0 | **Last Updated:** 2026-07-01 | **Purpose:** This is the single source of truth for any human or AI agent working on this codebase. Read this file FIRST before touching any code. It tells you exactly which file to open and which function to edit for any given change request — without needing to re-scan the entire repository.

> **RULE FOR AI AGENTS:** When the user asks for a change, search this document for the relevant Module section, find the exact file path + function name in the tables below, and go DIRECTLY there. Do not grep/search the whole codebase unless this PRD is missing the info (and if so, please update this PRD after finding it).

---

## 1. PROJECT OVERVIEW

**Product Name:** Eye Care HMS SaaS (multi-tenant Hospital/Clinic Management System for Eye Hospitals)

**What it does:** A SaaS platform where multiple independent eye-hospitals ("tenants") sign up, each getting their own isolated data space, staff logins, OPD (walk-in patient) workflow, eye examination (Primary + Secondary) workflow, medicine prescription, OT (Operation Theatre / Surgery) booking-to-discharge workflow, and reporting — all inside ONE Laravel codebase and ONE database, separated logically by `tenant_id`.

**Three separate "sides" of the app:**
1. **Platform / Landing (public)** — marketing site, pricing, hospital self-registration, unified login.
2. **Hospital Panel** — the actual HMS product used by each hospital's staff (Admin, Doctor, Receptionist, OT staff, Accountant). URL pattern: `hmssaas.com/{slug}/...`
3. **SuperAdmin Panel** — Anthropic-internal-style platform-owner panel to manage all tenant hospitals, subscriptions, payments, plans, notifications. URL: `hmssaas.com/superadmin/...`

**Routing Strategy:** PATH-BASED multi-tenancy, NOT subdomain-based.
- ✅ Correct: `hmssaas.com/aakasheye/patients`
- ❌ Wrong: `aakasheye.hmssaas.com/patients`
- The `{slug}` is a literal Laravel route parameter (first URL segment), resolved to a `Tenant` row.

---

## 2. TECH STACK

| Layer | Technology | Version | Notes |
|---|---|---|---|
| Backend Framework | Laravel | ^13.0 | PHP ^8.3 |
| Auth | Laravel Sessions + Sanctum | — | 3 guards, see §5 |
| DB | MySQL (via Laragon) | — | Config in `config/database.php`, `.env` |
| Cache/Queue | Redis (Predis) | ^3.4 | `predis/predis` — used for permission caching |
| PDF Generation | barryvdh/laravel-dompdf | ^3.1 | Invoices, prescriptions, OT documents |
| Excel Export/Import | maatwebsite/excel | ^3.1 | Reports export, Location master import |
| Frontend Build | Vite | ^7.0.7 | `vite.config.js` |
| CSS Framework | Bootstrap 5.3.3 + Tailwind 4 (via `@tailwindcss/vite`) | — | See §9 for which CSS file governs which panel |
| Icons | Bootstrap Icons (`bi bi-*`) ONLY | — | Font Awesome is being actively removed — NEVER add new `fa-*` icons (see TODO.md) |
| Templating | Blade | — | No Vue/React/Inertia — server-rendered Blade views |
| Dev tooling | Laravel Boost, Telescope, Pail, Pint | — | `laravel/boost` gives AI agents extra Laravel-aware tools |
| Debug/monitoring | Laravel Telescope | ^5.18 | Table: `telescope_entries` |

**No SPA framework.** No Vue/React components are used for the main app (some JS libraries like `lucide-react`/`recharts` you may see referenced elsewhere are NOT part of this repo — ignore any React assumptions).

---

## 3. HIGH-LEVEL ARCHITECTURE

### 3.1 Multi-Tenancy Model
- **Single database, shared schema, `tenant_id` column on every hospital-scoped table.**
- The `BelongsToTenant` trait (`app/Traits/BelongsToTenant.php`) auto-injects a global Eloquent scope `WHERE tenant_id = X` on every query AND auto-fills `tenant_id` on `creating()`.
- Tenant is resolved from the URL slug by `IdentifyTenant` middleware, then bound into the service container as `app('tenant')`.
- To bypass tenant scoping (SuperAdmin cross-tenant queries), call `Model::withoutTenantScope()`.
- **Platform-level models do NOT use `BelongsToTenant`:** `Tenant`, `PlatformAdmin`, `Subscription`, `Payment`, `PlatformSetting`, `PlatformNotification`, `AuditLog`, `HospitalShareRequest`, Location master tables (`MasterCountry/State/District/City`).

### 3.2 Request Lifecycle for a Hospital Page (e.g. `/aakasheye/patients`)
```
Request → routes/hospital.php matches Route::prefix('{slug}')
   → Middleware: identify.tenant (IdentifyTenant.php)   → resolves slug → binds app('tenant')
   → Middleware: set.tenant.scope (SetTenantScope.php)  → sets pagination_limit, hospital timezone
   → Middleware: auth.hospital (HospitalAuth.php)        → checks guard 'hospital_user' session
   → Middleware: subscription.active (CheckSubscriptionActive.php) → blocks if tenant status = inactive/suspended
   → Middleware: grace.check (CheckGracePeriod.php)      → sets session flag if status = grace
   → Middleware: permission:xxx (CheckPermission.php)    → per-route permission key check (see §6)
   → Controller method executes (BelongsToTenant auto-filters all queries)
   → Blade view rendered
```

### 3.3 The Three Auth Guards (config/auth.php)
| Guard | Provider / Model | Used For | Login Route |
|---|---|---|---|
| `superadmin` | `App\Models\Platform\PlatformAdmin` | SuperAdmin panel | `/superadmin/login` |
| `hospital_user` | `App\Models\Hospital\HospitalUser` (UNIFIED — one model for ALL hospital staff roles) | Hospital panel (session, web) | `/{slug}/login` |
| `api` (Sanctum) | `App\Models\Hospital\HospitalUser` | Mobile app / REST API | `POST /api/v1/{slug}/auth/login` |

⚠️ **IMPORTANT:** There is only ONE hospital user model/table (`hospital_users`), NOT separate `Doctor`/`Reception`/`OtStaff` models. Role is determined by `hospital_users.role_id` → `roles.slug`. Never re-introduce separate role-specific models — this was a deliberate refactor (see `HospitalUser.php` docblock: "REPLACES: HospitalAdmin + Doctor + Reception + OtStaff (4 models)").

### 3.4 Permission System (RBAC, per-tenant, checkbox-based — NOT auto-seeded)
- `roles` table — tenant-scoped. `is_super=true` role (always "Hospital Admin") bypasses ALL permission checks.
- `permissions` table — platform-level master list, format `module.submodule.action` e.g. `opd.patient.register`, `ot.booking.create`.
- `role_permissions` pivot — `is_granted` boolean. Hospital Admin manually checks/unchecks permissions per role via the Roles UI. **No auto-grant on role creation.**
- Route-level enforcement: `->middleware('permission:opd.patient.view')` or OR-logic `->middleware('permission:opd.exam.primary|opd.exam.secondary')`.
- Logic lives in: `App\Http\Middleware\CheckPermission.php` + `App\Services\Auth\RolePermissionService.php` (Redis-cached permission lookups).
- Module/action master list (used to render the permission checkbox grid UI) lives in **`config/hospital_modules.php`** — ⚠️ never remove a key from this file, only append, or you'll orphan DB rows.

### 3.5 Subscription Lifecycle
Tenant `status` field drives access: `trial` → `active` → `grace` → `suspended`/`inactive`.
- `CheckSubscriptionActive` middleware blocks `inactive`/`suspended`.
- `CheckGracePeriod` middleware just sets a session banner flag for `grace` status (doesn't block).
- `app/Console/Commands/CheckSubscriptionExpiry.php` — scheduled command, flips status when `subscriptions.ends_at` passes.
- `app/Console/Commands/SendSubscriptionReminders.php` — scheduled email reminders.
- `app/Services/Platform/SubscriptionService.php` — core business logic for subscription state changes.
- `app/Services/Platform/InvoiceService.php` — generates payment invoices (PDF).

---

## 4. FULL FOLDER MAP (What Lives Where)

```
eye-saas-hms/
├── app/
│   ├── Console/Commands/          → Scheduled/Artisan commands (see §11)
│   ├── Exports/                   → Excel export classes (Maatwebsite\Excel)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/               → REST API controllers (mobile app), mirrors Hospital/ features
│   │   │   │   └── SuperAdmin/    → API for superadmin (TenantApiController only)
│   │   │   ├── Auth/              → SuperAdminAuthController only
│   │   │   ├── Hospital/          → ALL hospital-panel web controllers (grouped by feature, see §6)
│   │   │   ├── Platform/          → Landing page, Register, Unified Login, Razorpay Webhook
│   │   │   └── SuperAdmin/        → SuperAdmin panel web controllers
│   │   ├── Middleware/            → All custom middleware (see §3.2, §6)
│   │   └── Requests/               → FormRequest validation classes (Auth/, Hospital/, Patient/)
│   ├── Imports/                   → Excel import classes (LocationMasterImport)
│   ├── Jobs/                      → Queued jobs (tenant creation side-effects, emails)
│   ├── Mail/                      → Mailable classes (NotificationMail, SubscriptionMail)
│   ├── Models/
│   │   ├── Hospital/              → ALL tenant-scoped models (Patient, Medicine, Examinations, etc.) + Hospital/OT/ subfolder
│   │   ├── Platform/              → ALL platform-level models (Tenant, Subscription, Payment, etc.)
│   │   ├── Role/                  → Role, Permission, RolePermission
│   │   └── User.php               → Laravel's default `users` table model — LEGACY, NOT used for hospital or superadmin auth. Barely used.
│   ├── Providers/                 → AppServiceProvider, TelescopeServiceProvider
│   ├── Services/
│   │   ├── Auth/                  → RolePermissionService (permission check engine)
│   │   ├── Hospital/               → PatientService (MRD generation, walk-in/phone registration), ExaminationService
│   │   └── Platform/                → TenantService, SubscriptionService, InvoiceService, TimezoneService
│   ├── Support/helpers.php        → Global helper functions: hospital_settings(), hospital_name(), hospital_logo_url(), etc.
│   └── Traits/BelongsToTenant.php → THE multi-tenancy trait — read §3.1
│
├── config/
│   ├── auth.php                   → 3 guards defined here (§3.3)
│   └── hospital_modules.php       → Permission module/action master list (§3.4)
│
├── database/
│   ├── migrations/                → Flat list, chronological (see §7 for schema grouping)
│   └── seeders/                   → Master data seeders (eye-exam dropdown values), Permissions, System Roles
│
├── docs/
│   ├── PRD_MASTER.md              → THIS FILE
│   └── MOBILE_APP_PRD.md          → Separate PRD for the Android/mobile app (API consumer)
│
├── public/
│   ├── css/                       → design-system.css, hospital.css, superadmin.css, landing.css, premium-theme.css (§9)
│   └── js/                        → bootstrap.bundle.min.js, landing-animations.js
│
├── resources/
│   ├── js/                        → app.js, bootstrap.js (Vite entry — minimal, most JS is inline in Blade views)
│   └── views/
│       ├── components/            → Shared Blade components (x-card, x-hms-table, x-empty-state, etc.) — see §9
│       ├── emails/                → Mailable Blade templates
│       ├── hospital/               → ALL hospital-panel views (mirrors Http/Controllers/Hospital structure)
│       ├── landing/                → Public marketing site + register + unified login views
│       ├── pdfs/                   → DomPDF Blade templates (invoice.blade.php, opd-bill.blade.php)
│       ├── platform/                → mostly unused/.gitkeep — platform logic renders via landing/ views instead
│       └── superadmin/             → ALL superadmin panel views
│
├── routes/
│   ├── web.php                    → Public + SuperAdmin routes (§3, includes hospital.php)
│   ├── hospital.php                → ALL hospital panel routes ({slug} prefix) — THE most important routing file
│   ├── api.php                     → REST API routes (/api/v1/{slug}/...)
│   └── console.php                 → Artisan schedule definitions
│
├── TODO.md                        → Active UI/design-alignment TODO for SuperAdmin panel (Font Awesome → Bootstrap Icons migration, etc.)
└── todotask.md                    → Higher-level SuperAdmin feature todo list + design token reference
```

---

## 5. DATABASE SCHEMA — GROUPED BY DOMAIN

> Full migration file list is in `database/migrations/` (flat, chronological). Below is the **logical grouping** so you know which table governs which feature. When changing a column, find the LATEST migration that touches that table (search filenames chronologically — later migrations may have altered earlier ones).

### 5.1 Platform-level tables (NOT tenant-scoped)
| Table | Model | Migration (creation) | Purpose |
|---|---|---|---|
| `tenants` | `Tenant` | `2025_01_01_000001_create_tenants_table.php` | Every registered hospital. Key fields: `slug`, `status`, `trial_ends_at`, `timezone`, `country/state/district/city` |
| `platform_admins` | `PlatformAdmin` | `2025_01_01_000002_create_platform_admins_table.php` | SuperAdmin login accounts |
| `subscriptions` | `Subscription` | `2025_01_01_000003_create_subscriptions_table.php` | Per-tenant subscription cycles, Razorpay IDs |
| `payments` | `Payment` | `2025_01_01_000004_create_payments_table.php` | Payment transactions (Razorpay + offline) |
| `platform_settings` | `PlatformSetting` | `2025_01_01_000005_create_platform_settings_table.php` | Key-value store — Razorpay keys, mail config, **Plan pricing** (Plans feature stores data here, NOT a dedicated `plans` table) |
| `platform_notifications` | `PlatformNotification` | `2025_01_01_000006_create_platform_notifications_table.php` | SuperAdmin → Hospital broadcast notifications |
| `audit_logs` | `AuditLog` | `2025_01_01_000007_create_audit_logs_table.php` | Platform-wide audit trail |
| `permissions` | `Permission` | `2025_01_02_000001_create_permissions_table.php` | Master permission list (global, not per-tenant) |
| `master_countries/states/districts/cities` | `MasterCountry/State/District/City` | `2026_06_17_000001_create_location_hierarchy_tables.php` | Platform-managed location dropdown data (SuperAdmin → Locations page) |
| `hospital_share_requests` | `HospitalShareRequest` | `2026_06_12_000002_create_hospital_share_requests_table.php` | Cross-tenant patient-history sharing between partner hospitals |

### 5.2 Hospital/Tenant-scoped tables (all have `tenant_id`, use `BelongsToTenant`)
| Table | Model | Purpose |
|---|---|---|
| `hospital_users` | `HospitalUser` | UNIFIED staff table — all roles (admin/doctor/reception/OT staff/accountant). See `2026_03_25_150100_backfill_hospital_users_from_legacy_tables.php` for the migration history of the old 4-table → 1-table refactor. |
| `roles` | `Role` | Per-tenant custom + system roles |
| `role_permissions` | `RolePermission` | Pivot: role ↔ permission with `is_granted` |
| `hospital_settings` | `HospitalSetting` | Per-tenant key-value settings (name, address, logo, pagination_limit, etc.) — read via `hospital_setting()` helper |
| `patients` | `Patient` | OPD patient registry. Key fields: `patient_code` (MRD, e.g. AEH0001), `doctor_patient_no`, `type` (walk-in/phone), `checked_in_at`, `primary_done_at`, `secondary_done_at` |
| `primary_examinations` | `PrimaryExamination` | First-stage eye exam (Vision, Refraction) |
| `secondary_examinations` | `SecondaryExamination` | Second-stage eye exam (Slit lamp, Fundus, Diagnosis) |
| `exam_prescriptions` | `ExamPrescription` | Medicines prescribed during exam |
| `patient_prescriptions` | `PatientPrescription` | Standalone prescription records |
| `focs` | `Foc` | Free-of-Charge visit requests (Doctor creates → Reception accepts/rejects) |
| `medicines`, `medicine_groups`, `medicine_group_items`, `medicine_categories`, `medicine_types`, `medicine_routes`, dosages | Various `Medicine*` models | Medicine master data |
| `tbl_master_*` (vn, pnvn, sph_cyl, axis, nct, sac, lid, conj, cornea, ac, iris, pupil, lens, em, covertest, disc, fr, hno, vngl, vnst, advice, diagnosis) | `Master*` models | Dozens of small "eye exam dropdown" master tables — each is a simple lookup list with `is_favourite` flag for quick-pick UI |
| `chief_complaints`, `kcos` | `ChiefComplaint`, `Kco` | Exam dropdown masters |
| `tbl_cases` | `CaseType` | OPD case/fee types |
| `tbl_locations` | `Location` | Patient's home city/area (linked to `master_cities`) |
| `tbl_slots` | `Slot` | Appointment time slots (auto-synced from OT slots — see `PatientController::loadPatientSlots()`) |
| `tbl_referrers` | `Referrer` | Referring doctors |
| `ot_bookings` | `OtBooking` | Core OT surgery booking record. Status enum: `booked → paid → in_ward → dilated → ready → operated → discharged` |
| `ot_payments` | `OtPayment` | OT payment installments |
| `ot_surgeries` | `OtSurgery` | Surgery details recorded by OT Doctor |
| `ot_lens_details` | `OtLensDetail` | Lens implant details (OT Assistant) |
| `ot_discharge_summaries` | `OtDischargeSummary` | Discharge documentation |
| `ot_types`, `ot_surgery_types`, `ot_slots`, `ot_charge_heads`, `ot_lens_options` | OT master models | OT configuration masters (Hospital Admin managed) |
| `doctors`, `receptions`, `ot_staff` tables | — LEGACY, migrated away from | Old per-role tables, replaced by unified `hospital_users`. Do NOT use — kept only for historical migration reference. |

**Naming inconsistency to be aware of:** Some tables use `tbl_` prefix (legacy naming: `tbl_cases`, `tbl_locations`, `tbl_slots`, `tbl_referrers`, `tbl_master_*`) while newer tables don't (`patients`, `ot_bookings`, `hospital_users`). This is historical — don't "fix" it without a dedicated migration task, as Blade views and raw `DB::table()` calls hardcode these names in many places (e.g. `PatientController@loadPatientSlots` uses `DB::table('tbl_slots')` directly, not the `Slot` Eloquent model).

---

## 6. MODULE-BY-MODULE BREAKDOWN
**How to use this section:** Find the feature you need to change → get the exact Controller file + method, Route name, Model(s), and View file(s). This is the "don't waste time searching" index.

### 6.1 Platform / Public Site
| Feature | Route (name) | Controller / Method | View |
|---|---|---|---|
| Landing page | `GET /` (`home`) | `Platform\LandingController@index` | `resources/views/landing/index.blade.php` |
| Pricing page | `GET /pricing` (`pricing`) | `Platform\LandingController@pricing` | `resources/views/landing/pricing.blade.php` |
| Hospital self-registration | `GET/POST /register` (`register.show`/`register.store`) | `Platform\RegisterController@show`/`@store` | `resources/views/landing/register.blade.php` |
| Slug availability check (AJAX) | `GET /check-slug` | `Platform\RegisterController@checkSlug` | — JSON |
| Location cascade (register form) | `GET /location/states,districts,cities` | `Platform\RegisterController@getStates/getDistricts/getCities` | — JSON |
| Unified login (all hospital staff, one page) | `GET/POST /login` (`login`/`login.post`) | `Platform\UnifiedLoginController@show`/`@login` | `resources/views/landing/auth/login.blade.php` |
| Razorpay webhook | `POST /webhooks/razorpay` | `Platform\WebhookController@handle` | — (CSRF-exempt) |

### 6.2 Hospital Panel — Authentication & Dashboard
| Feature | Route | Controller / Method | Model(s) | View |
|---|---|---|---|---|
| Hospital login | `GET/POST /{slug}/login` | `Hospital\Auth\LoginController@show`/`@login` | `HospitalUser` | `hospital/auth/login.blade.php` |
| Logout | `POST /{slug}/logout` | `Hospital\Auth\LoginController@logout` | — | — |
| Forgot/Reset password | `/{slug}/forgot-password`, `/{slug}/reset-password/{token}` | `Hospital\Auth\PasswordResetController` | `HospitalUser` | `hospital/auth/forgot-password.blade.php`, `reset-password.blade.php` |
| Dashboard (role-aware) | `GET /{slug}/` (`hospital.dashboard`) | `Hospital\Dashboard\DashboardController@index` | `Patient`, `HospitalUser` | `hospital/dashboard/index.blade.php` (+ role variants: `admin.blade.php`, `doctor.blade.php`, `receptionist.blade.php`) |
| Doctor/Hospital/Partner history views | `/{slug}/doctor-history`, `/hospital-history`, `/shared-patient-history`, `/partner-history/{id}` | `DashboardController@history/@hospitalHistory/@sharedPatientHistory/@partnerHistory` | `Patient` | `hospital/dashboard/doctor_history.blade.php`, `hospital_history.blade.php`, `partner_history.blade.php` |
| Hospital share requests (partner linking) | `/{slug}/hospital-share-requests/*` | `DashboardController@sendShareRequest/@acceptShareRequest/@removeShareRequest` | `HospitalShareRequest` | inline in dashboard views |
| AJAX hospital details modal | `/{slug}/ajax/hospital-details/{id}` | `DashboardController@getHospitalDetails` | `Tenant` | — JSON |

### 6.3 Patient Management (OPD Core)
| Feature | Route | Controller / Method | Permission | View |
|---|---|---|---|---|
| Patient list | `GET /{slug}/patients` | `Hospital\Patient\PatientController@index` | `opd.patient.view` | `hospital/patients/index.blade.php` |
| Register walk-in patient (form) | `GET /{slug}/patients/create` | `PatientController@create` | `opd.patient.register` | `hospital/patients/create.blade.php` |
| Store walk-in patient | `POST /{slug}/patients` | `PatientController@store` → uses `PatientService@registerWalkIn` | `opd.patient.register` | redirects to print |
| Register phone appointment (form) | `GET /{slug}/patients/phone/create` | `PatientController@createPhone` | `opd.patient.register_phone` | `hospital/patients/create-phone.blade.php` |
| Store phone appointment | `POST /{slug}/patients/phone` | `PatientController@storePhone` → `PatientService@registerPhone` | `opd.patient.register_phone` | redirects to print |
| Phone appointment history list | `GET /{slug}/patients/phone-history` | `PatientController@phoneHistory` | `opd.patient.register_phone` | `hospital/patients/phone-history.blade.php` |
| Search patient by contact (AJAX autofill) | `GET /{slug}/patients/search-by-contact` | `PatientController@searchByContact` (also searches PARTNER hospitals via `HospitalShareRequest`) | `opd.patient.register` | — JSON |
| View patient | `GET /{slug}/patients/{patient}` | `PatientController@show` | `opd.patient.view` | `hospital/patients/show.blade.php` |
| Edit patient | `GET/PUT /{slug}/patients/{patient}/edit` | `PatientController@edit`/`@update` | `opd.patient.edit` | `hospital/patients/edit.blade.php` |
| Delete patient | `DELETE /{slug}/patients/{patient}` | `PatientController@destroy` | `opd.patient.delete` | — |
| Check-in phone patient (convert to walk-in-like) | `GET/POST /{slug}/patients/{patient}/checkin` | `PatientController@checkinForm`/`@checkin` → `PatientService@assignDoctorSerial` | `opd.patient.register` | `hospital/patients/checkin.blade.php` |
| Print OPD slip | `GET /{slug}/patients/{patient}/print` | `PatientController@print` | `opd.bill.print` | `hospital/patients/print.blade.php` |
| Download OPD bill PDF | `GET /{slug}/patients/{patient}/bill-pdf` | `PatientController@downloadBill` (DomPDF, A5 portrait) | `opd.bill.print` | `resources/views/pdfs/opd-bill.blade.php` |
| Patient clinical history (list + detail timeline) | `GET /{slug}/patient-history` | `Hospital\Patient\PatientHistoryController@index` | `opd.exam.history` | `hospital/patient/history.blade.php` |
| Print patient history | `GET /{slug}/patient-history/{patient}/print` | `PatientHistoryController@print` | `opd.exam.history` | `hospital/patient/history-print.blade.php` |
| AJAX: patients by phone | `GET /{slug}/ajax/patients-by-phone` | `PatientHistoryController@getPatientsByPhone` | `opd.exam.history` | — JSON |
| **Core service logic** | — | **`app/Services/Hospital/PatientService.php`** — `registerWalkIn()`, `registerPhone()`, `peekNextMrd()`, `assignDoctorSerial()` — THIS is where MRD-number generation and doctor-serial-number logic lives, not the controller | — | — |

⚠️ Note: `app/Http/Controllers/Hospital/Patient/PatientController.php.tmp` exists — a stray/leftover file, NOT wired into routes. Ignore or clean up, don't confuse with the real `PatientController.php`.

### 6.4 Clinical / Eye Examination Module
| Feature | Route | Controller / Method | Permission | View |
|---|---|---|---|---|
| Clinical queue dashboard (waiting patients) | `GET /{slug}/clinical-queue` | `Hospital\Examination\ClinicalQueueController@index` | `opd.exam.primary\|opd.exam.secondary` | `hospital/exam/queue.blade.php` |
| Primary exam — view/save | `GET/POST /{slug}/exam/primary/{id}` | `Hospital\Examination\PrimaryExamController@show`/`@save` | `opd.exam.primary` | `hospital/exam/primary.blade.php` |
| Primary exam print (prescription) | `GET /{slug}/exam/primary/{id}/print` | `PrimaryExamController@printRx` | `opd.prescription.print` | `hospital/exam/print.blade.php` |
| Primary exam compact view (HUD) | `GET /{slug}/exam/primary/{id}/hud` | `PrimaryExamController@compactView` | `opd.prescription.print` | `hospital/exam/compact_view.blade.php` |
| Secondary exam — view/save | `GET/POST /{slug}/exam/secondary/{id}` | `Hospital\Examination\SecondaryExamController@show`/`@save` | `opd.exam.secondary` | `hospital/exam/secondary.blade.php` |
| AJAX: add complaint/diagnosis/advice inline | `POST /{slug}/ajax/complaint,diagnosis,advice` | `PrimaryExamController@ajaxAddComplaint/@ajaxAddDiagnosis/@ajaxAddAdvice` | `opd.exam.primary` | — JSON |
| AJAX: search medicines / get medicine group | `GET /{slug}/ajax/medicines`, `/ajax/medicine-group/{id}` | `PrimaryExamController@ajaxSearchMedicines/@ajaxGetMedicineGroup` | exam permissions | — JSON |
| **Core service logic** | — | **`app/Services/Hospital/ExaminationService.php`** | — | — |
| Validation | — | `app/Http/Requests/Hospital/Examination/StorePrimaryExamRequest.php`, `StoreSecondaryExamRequest.php` | — | — |
| Models | — | `PrimaryExamination`, `SecondaryExamination`, `ExamPrescription` (app/Models/Hospital/) | — | — |

### 6.5 FOC (Free of Charge visits)
| Feature | Route | Controller / Method | Permission |
|---|---|---|---|
| List | `GET /{slug}/foc` | `Hospital\Foc\FocController@index` | `opd.foc.create\|opd.foc.accept` |
| Create (Doctor requests) | `GET/POST /{slug}/foc/create`, `/{slug}/foc` | `FocController@create`/`@store` | `opd.foc.create` |
| Show | `GET /{slug}/foc/{foc}` | `FocController@show` | both |
| Accept (Reception) | `POST /{slug}/foc/{id}/accept` | `FocController@accept` | `opd.foc.accept` |
| Approve/Reject | `PATCH /{slug}/foc/{foc}/approve,/reject` | `FocController@approve`/`@reject` | `opd.foc.accept` |
| Validation | — | `app/Http/Requests/Hospital/Foc/StoreFocRequest.php` | — |
| Model / Views | — | `Foc` model, `hospital/foc/{index,create,show}.blade.php` | — |

### 6.6 Medicine Master (all under `permission:master.medicines`)
| Feature | Controller | Views |
|---|---|---|
| Medicines CRUD | `Hospital\Medicine\MedicineController` | `hospital/medicines/{index,create,edit}.blade.php` |
| Medicine Groups (bundles) CRUD | `Hospital\Medicine\MedicineGroupController` | `hospital/medicine_groups/{index,create,edit,show}.blade.php`, `_group_modal.blade.php` |
| Medicine Routes CRUD | `Hospital\Medicine\MedicineRouteController` | `hospital/medicine_routes/index.blade.php` |
| Medicine Categories CRUD | `Hospital\Medicine\MedicineCategoryController` | `hospital/medicine_categories/index.blade.php` |
| Medicine Types CRUD | `Hospital\Medicine\MedicineTypeController` | `hospital/medicine_types/index.blade.php` |
| Medicine Dosages CRUD | `Hospital\Medicine\MedicineDosageController` | `hospital/medicine_dosages/index.blade.php` |
| Medicine Instructions CRUD | `Hospital\Medicine\MedicineInstructionController` | `hospital/medicine_instructions/index.blade.php` (route name uses underscore: `medicine_instructions.index`) |
| Models | `Medicine`, `MedicineGroup`, `MedicineGroupItem`, `MedicineCategory`, `MedicineType`, `MedicineRoute`, `Dosage` | (all `app/Models/Hospital/`) |

### 6.7 Masters (Basic + Detail Eye-Exam Masters)
| Feature | Route pattern | Controller | Permission |
|---|---|---|---|
| Landing page (module picker) | `GET /{slug}/masters` | `Hospital\Master\BasicMasterController@landing` | `master.case_types\|master.eye_exam` |
| Basic masters (cases, locations, referrers, durations) generic CRUD | `/{slug}/masters/basic/{type}` | `Hospital\Master\BasicMasterController@index/@store/@update/@destroy/@ajaxStore` | `master.case_types` |
| Detail (eye-exam) masters generic CRUD — vn, pnvn, sph_cyl, axis, etc. | `/{slug}/masters/detail/{type}` | `Hospital\Master\DetailMasterController@index/@store/@update/@destroy/@toggleFavourite/@syncByDiagnosis` | `master.eye_exam` |
| OT masters (lens options, slots, types, surgery types, charge heads) | `/{slug}/masters/ot/*` — restricted to `role:admin` (not permission-based, uses `CheckRole` middleware) | `Hospital\Master\OT\Ot{LensOption,Slot,Type,SurgeryType,ChargeHead}Controller` | `role:admin` |
| **Pattern to know:** Both `BasicMasterController` and `DetailMasterController` are GENERIC/dynamic controllers driven by a `{type}` route parameter — the SAME controller handles many different master tables based on the `type` string. If asked to "add a new master table", check how `{type}` is dispatched inside these two controllers FIRST before creating a new controller. | | | |
| Views | `hospital/masters/dynamic_index.blade.php` (generic), `hospital/masters/index.blade.php` (landing), `hospital/masters/ot/*.blade.php` | | |

### 6.8 Reports
| Feature | Route | Controller / Method |
|---|---|---|
| Reports index | `GET /{slug}/reports` | `Hospital\Report\ReportController@index` |
| Export Excel | `GET /{slug}/reports/export/excel` | `ReportController@exportExcel` → `app/Exports/PatientReportExport.php` |
| Export PDF | `GET /{slug}/reports/export/pdf` | `ReportController@exportPdf` |
| Views | — | `hospital/patients/reports/{index,pdf}.blade.php` |

### 6.9 Roles & Users (Hospital Admin)
| Feature | Route | Controller | Permission |
|---|---|---|---|
| Roles CRUD | `/{slug}/roles/*` | `Hospital\Role\RoleController` | `master.roles` |
| Users CRUD | `/{slug}/users/*` | `Hospital\User\HospitalUserController` | `master.doctors\|master.receptions\|master.ot_staff` |
| Validation | — | `app/Http/Requests/Hospital/Role/{RoleStoreRequest,RoleUpdateRequest}.php`, `Hospital/User/{HospitalUserStoreRequest,HospitalUserUpdateRequest}.php` |
| Views | — | `hospital/roles/{index,create,edit}.blade.php`, `hospital/users/{index,create,edit,_user_modal}.blade.php` |

### 6.10 Settings / Profile / Setup Wizard
| Feature | Route | Controller |
|---|---|---|
| Hospital settings (name, address, logo, pagination) | `/{slug}/settings` | `Hospital\Setting\HospitalSettingController` (`permission:settings.hospital`) |
| Timezone settings | `/{slug}/settings/timezone` | `Hospital\Setting\TimezoneController` |
| My Profile (any logged-in user, self-edit) | `/{slug}/profile` | `Hospital\Setting\DoctorProfileController` |
| First-login Setup Wizard (4 steps) | `/{slug}/setup/{step}` (`[1-4]`) | `Hospital\Setting\SetupWizardController@show/@store/@skip` |
| Views | — | `hospital/settings/{index,timezone}.blade.php`, `hospital/profile/index.blade.php`, `hospital/setup/{layout,step1-4}.blade.php` |

### 6.11 OT (Operation Theatre) Module — the most complex module
**Booking status flow:** `booked → paid → in_ward → dilated → ready → operated → discharged` (constants defined in `OtBooking` model).

| Sub-role | Feature | Route prefix | Controller | Permission |
|---|---|---|---|---|
| Receptionist | OT dashboard | `/{slug}/ot/dashboard` | `Hospital\OT\OtReceptionistController@dashboard` | `ot.patient.list` |
| Receptionist | Booking list/create | `/{slug}/ot/bookings*` | `Hospital\OT\OtBookingController@index/@create/@store` | `ot.patient.list`, `ot.booking.create` |
| Accountant | Ward entry (mark ready-for-OT) | `/{slug}/ot/ward*` | `Hospital\OT\OtAccountantController@wardIndex/@markReadyForOt` | `ot.ward.entry` |
| Accountant | Accountant dashboard + payments | `/{slug}/ot/accountant/*` | `OtAccountantController@dashboard/@createPayment/@storePayment` | `ot.payment.record` |
| OT Doctor | Doctor dashboard + surgery recording | `/{slug}/ot/doctor/*` | `Hospital\OT\OtDoctorController@dashboard/@createSurgery/@storeSurgery` | `ot.surgery.record` |
| OT Assistant | Assistant dashboard + lens details | `/{slug}/ot/assistant/*` | `Hospital\OT\OtAssistantController@dashboard/@editLens/@storeLens` | `ot.lens.record\|ot.lens.implant\|ot.patient.list` |
| Billing | Invoice generation & all print documents | `/{slug}/ot/billing/*` | `Hospital\OT\OtInvoiceController`, `Hospital\OT\OtDischargeController` | `ot.billing.manage` |
| — | Invoice print | `/ot/billing/invoice/{id}/print` | `OtInvoiceController@print` | |
| — | Discharge print | `/ot/billing/discharge/{id}/print` | `OtDischargeController@print` | |
| — | Summary bill print | `/ot/billing/summary-bill/{id}/print` | `OtInvoiceController@summaryBillPrint` | |
| — | Certificate print | `/ot/billing/certificate/{id}/print` | `OtDischargeController@certificatePrint` | |
| — | Medicine slip print | `/ot/billing/medicine-slip/{id}/print` | `OtDischargeController@medicineSlipPrint` | |

**OT Models:** `OtBooking` (core), `OtPayment`, `OtSurgery`, `OtLensDetail`, `OtDischargeSummary` — all in `app/Models/Hospital/OT/`.
**OT Master Models/Controllers:** `OtType`, `OtSurgeryType`, `OtSlot`, `OtChargeHead`, `OtLensOption` — managed via `app/Http/Controllers/Hospital/Master/OT/`.
**OT Views:** `hospital/ot/{dashboard,accountant/*,assistant/*,billing/*,bookings/*,doctor/*}.blade.php`.

### 6.12 SuperAdmin Panel
| Feature | Route (prefix `superadmin.`) | Controller | View |
|---|---|---|---|
| Login | `/superadmin/login` | `Auth\SuperAdminAuthController` | `superadmin/auth/login.blade.php` |
| Dashboard | `/superadmin/dashboard` | `SuperAdmin\SuperAdminDashboardController@index` | `superadmin/dashboard.blade.php` |
| Hospital (Tenant) CRUD | `/superadmin/hospitals` (resource, param name `tenant`) | `SuperAdmin\TenantController` | `superadmin/tenants/{index,create,edit,show}.blade.php`, `_form.blade.php` |
| Hospital activate/suspend/extend/reactivate | `POST /superadmin/hospitals/{tenant}/{activate,suspend,extend,reactivate}` | `TenantController@activate/@suspend/@extend/@reactivate` | — |
| Payments | `/superadmin/payments` | `SuperAdmin\PaymentController@index/@storeOffline/@downloadInvoice` | `superadmin/payments/index.blade.php` |
| Audit logs | `/superadmin/audit-logs` | `SuperAdmin\AuditLogController@index` | `superadmin/audit-logs/index.blade.php` |
| Notifications (send to hospitals) | `/superadmin/notifications` | `SuperAdmin\NotificationController@index/@send` | `superadmin/notifications/index.blade.php` |
| Subscriptions | `/superadmin/subscriptions` | `SuperAdmin\SubscriptionController@index` | `superadmin/subscriptions/index.blade.php` |
| Platform Settings | `/superadmin/settings` | `SuperAdmin\SettingsController@index/@update` | `superadmin/settings/index.blade.php` |
| SuperAdmin Profile | `/superadmin/profile` | `SuperAdmin\ProfileController@show/@update/@updatePassword` | `superadmin/profile/index.blade.php` |
| **Plan Management** (pricing tiers — stored as key-value in `platform_settings`, NOT a `plans` table) | `/superadmin/plans` (resource) | `SuperAdmin\PlanController` | `superadmin/plans/index.blade.php` |
| Timezone Master (view-only) | `/superadmin/timezones` | `SuperAdmin\TimezoneMasterController@index` | `superadmin/timezones/index.blade.php` |
| Location Master (Country/State/District/City CRUD + import) | `/superadmin/locations/*` | `SuperAdmin\LocationMasterController` (many methods — one per level, see routes/web.php lines ~130-165) | `superadmin/locations/index.blade.php` |

**⚠️ ACTIVE WORK IN PROGRESS on SuperAdmin panel** — see `TODO.md` and `todotask.md` in project root:
- Phase 1: Font Awesome (`fa-*`) icons are being replaced with Bootstrap Icons (`bi bi-*`) across all SuperAdmin views. **RULE: never add a new `fa-*` icon anywhere in the SuperAdmin panel — always use `bi bi-*`.** Full mapping table is in `TODO.md`.
- Phase 2: Plans page is being migrated from custom `sa-premium-*` CSS classes to the shared `hms-*` design system classes (`hms-btn`, `hms-card`, `hms-table`) used by the Hospital panel — for visual consistency.
- The SuperAdmin sidebar theme was recently changed from dark navy (`#0D2137`) to white glassmorphism to match the Hospital panel's `#1B4F72` deep-healthcare-blue theme (see Design Token Reference table in `todotask.md`).
- **Before touching any SuperAdmin blade file, check `TODO.md` first** — there may be an open task for exactly that file.

### 6.13 REST API (Mobile App)
Full details are in **`docs/MOBILE_APP_PRD.md`** — read that file for the mobile app's own requirements. Quick map:
- Base: `/api/v1/{slug}/...`, auth via Sanctum Bearer token, guard `api` → `HospitalUser` model.
- `App\Http\Controllers\Api\*` controllers largely mirror the Hospital web controllers' business logic (often reusing the same Services — `PatientService`, `ExaminationService`, `RolePermissionService`) but return JSON (`{ success, data, message }` format) instead of Blade views.
- `Api\SuperAdmin\TenantApiController` — read-only tenant list/detail for platform API consumers, under `/api/v1/super/*`.
- If changing PATIENT/EXAM business logic, check BOTH the web controller (`Hospital\Patient\PatientController`) AND the API controller (`Api\PatientApiController`) — they may both call the same underlying Service, or may have diverged. Always verify by reading both before assuming parity.

---

## 7. MIDDLEWARE REFERENCE (app/Http/Middleware/)
| Middleware alias | File | Purpose | Applied Where |
|---|---|---|---|
| `identify.tenant` | `IdentifyTenant.php` | Resolves `{slug}` → `Tenant`, binds `app('tenant')`, sets `Config::set('app.tenant_id', ...)`. Reserved slugs list here (`superadmin`, `admin`, `register`, `login`, `pricing`, `api`, `health`, `webhook`, `static`, `assets`, `storage`) — a hospital can NEVER register with these slugs. | `routes/hospital.php`, `routes/api.php` |
| `set.tenant.scope` | `SetTenantScope.php` | Reads `hospital_settings.pagination_limit` and hospital timezone, sets into `Config` + PHP `date_default_timezone_set()`. Must run AFTER `identify.tenant`. | Same as above |
| `auth.hospital` | `HospitalAuth.php` | Checks `hospital_user` guard session; also double-checks `user->tenant_id === current tenant->id` (prevents session hijack across tenants) | Authenticated hospital routes |
| `subscription.active` | `CheckSubscriptionActive.php` | Blocks if `tenant->status` in `['inactive','suspended']` | Authenticated hospital routes |
| `grace.check` | `CheckGracePeriod.php` | Sets `session(['show_grace_warning' => true])` if status = `grace` (layout reads this to show a banner) | Authenticated hospital routes |
| `redirect.inactive` | `RedirectIfInactive.php` | Blocks LOGIN attempt if `status === 'suspended'` | Login routes (before auth attempt) |
| `permission:xxx` | `CheckPermission.php` | Checks a specific permission key (or `key1\|key2` OR-logic) via `RolePermissionService`. SuperAdmin guard always bypasses. `role->is_super` always bypasses. | Most feature routes — see §6 tables |
| `role:xxx` | `CheckRole.php` | Checks `hospital_users.role.slug` directly (role-name based, not permission-based) — used ONLY for OT masters (`role:admin`) | `routes/hospital.php` masters/ot/* |
| `auth:superadmin` | Laravel built-in + `SuperAdminAuth.php` | Session guard check for SuperAdmin panel | `routes/web.php` superadmin group |

**Correct middleware order for hospital authenticated routes** (already set up in `routes/hospital.php`, don't reorder): `identify.tenant → set.tenant.scope → auth.hospital → subscription.active → grace.check → permission:xxx`

---

## 8. NAMING CONVENTIONS & PATTERNS

1. **Controllers** are grouped by Http-verb-area, not flat: `Hospital\Patient\PatientController`, `Hospital\OT\OtBookingController`, `SuperAdmin\TenantController`, `Api\PatientApiController`. Match this nesting when adding new controllers.
2. **Route names** always prefixed by area: `hospital.patients.index`, `superadmin.hospitals.index`, `api.v1.hospital.patients.index`.
3. **Permission keys** format: `{module}.{submodule}.{action}` e.g. `opd.patient.register`, `ot.billing.manage`, `master.medicines`. Full master list: `config/hospital_modules.php` (note: this file's `key` format like `patients.view` doesn't 1:1 match the route middleware format `opd.patient.view` in some places — cross-check against the `permissions` table seeder `database/seeders/PermissionsSeeder.php` for the actual canonical list used at runtime).
4. **Model namespacing**: `App\Models\Hospital\*` = tenant-scoped, `App\Models\Platform\*` = platform-level, `App\Models\Role\*` = RBAC. This is the fastest way to know if a model needs `BelongsToTenant`.
5. **Blade view folders mirror controller folders**, mostly — but NOT always 1:1 (e.g. `Hospital\Master\BasicMasterController` → view is `hospital/masters/dynamic_index.blade.php`, not `hospital/master/`). Always confirm the actual `view()` call string inside the controller method rather than assuming from folder name.
6. **`tbl_` prefix** on some tables = legacy naming, kept for historical/compatibility reasons (see §5.2 note). New tables should NOT use this prefix.
7. **Icons:** Hospital panel + SuperAdmin panel → Bootstrap Icons `bi bi-*` ONLY. Never introduce Font Awesome (`fa-solid`, `fa-regular`, `fa-brands`) — active migration away from FA is underway (TODO.md).
8. **Comments in the codebase are written in Hinglish** (Hindi+English mixed, Latin script) — this is the established documentation style throughout `app/`. Match this style when adding new docblocks/comments if you want consistency with the existing codebase author's convention (though plain English is also acceptable).
9. **Generic/dynamic controllers**: `BasicMasterController` and `DetailMasterController` both use a `{type}` route wildcard to serve MANY different master-data tables from ONE controller class. Before creating a new single-purpose master CRUD controller, check whether the new master table fits into one of these generic patterns instead.
10. **DomPDF** used for all PDF generation (`Barryvdh\DomPDF\Facade\Pdf::loadView(...)->download()`). Templates live in `resources/views/pdfs/`. OT print pages (invoice, discharge, certificate) are regular Blade views styled for print (`@media print` CSS), NOT DomPDF — check the specific controller method to know which technique is used for which document.

---

## 9. FRONTEND / CSS ARCHITECTURE
| CSS File | Governs | Notes |
|---|---|---|
| `public/css/design-system.css` (44KB) | Shared components used across Hospital + SuperAdmin: `.hms-card`, `.hms-table`, `.hms-btn`, form groups, badges, alerts | THE base design system — check here first for any shared component style |
| `public/css/hospital.css` (44KB) | Hospital panel shell — sidebar (white glassmorphism `rgba(255,255,255,0.78)` + `backdrop-filter: blur(12px)`), navbar, layout | Primary color: `#1B4F72` (Deep Healthcare Blue), Accent: `#27AE60` |
| `public/css/superadmin.css` (12KB) | SuperAdmin-specific overrides — being actively aligned to match hospital.css theme (see TODO.md) | Should NOT duplicate design-system.css — check for redundancy before adding new classes |
| `public/css/premium-theme.css` (12.75KB) | "Premium" card/table styling variant, used by some SuperAdmin pages (`sa-premium-*` classes) — Phase 2 of TODO.md is migrating these OUT in favor of `hms-*` classes | Being deprecated — don't add new `sa-premium-*` usage |
| `public/css/landing.css` (144KB — largest) | Public marketing site only | Independent of the app's design system |
| `public/css/hospital-responsive.css` | Currently EMPTY (0 B) | Placeholder for future responsive overrides |

**Blade Components** (`resources/views/components/`) — reusable across Hospital + SuperAdmin:
`action-btn`, `alert`, `badge`, `card`, `coming-soon`, `empty-state`, `form-group`, `hms-table`, `page-header`, `stat-card`.
Usage: `<x-empty-state ... />`, `<x-hms-table ... />` etc. **Always prefer these over inline duplicate markup** — e.g. TODO.md flags several pages still using inline empty-states instead of `<x-empty-state>`.

**Layouts:**
- `resources/views/hospital/layouts/app.blade.php` — main Hospital panel shell
- `resources/views/superadmin/layouts/app.blade.php` — main SuperAdmin panel shell (has sidebar nav groups: MANAGEMENT, SYSTEM)
- `resources/views/landing/layouts/` — public site layout

**JS:** No SPA framework. `resources/js/app.js` + `bootstrap.js` are Vite entry points (minimal). Most interactivity is inline `<script>` in Blade views or vanilla JS. `public/js/bootstrap.bundle.min.js` (Bootstrap 5 JS) + `landing-animations.js` are loaded directly (not via Vite) for the public site.

---

## 10. SERVICES LAYER (Business Logic — check here before controllers for "how does X actually work")
| Service | Location | Responsibility |
|---|---|---|
| `RolePermissionService` | `app/Services/Auth/` | Permission checking engine (`can()`, `canAny()`), Redis-cached |
| `PatientService` | `app/Services/Hospital/` | `registerWalkIn()`, `registerPhone()`, `peekNextMrd()` (MRD number generation logic), `assignDoctorSerial()` (per-doctor daily serial numbers, uses `doctor_prefix`) |
| `ExaminationService` | `app/Services/Hospital/` | Primary/Secondary exam save logic |
| `TenantService` | `app/Services/Platform/` | Tenant creation/lifecycle (used by RegisterController + SuperAdmin TenantController) |
| `SubscriptionService` | `app/Services/Platform/` | Subscription state transitions, activation/suspension logic |
| `InvoiceService` | `app/Services/Platform/` | PDF invoice generation for payments |
| `TimezoneService` | `app/Services/Platform/` | Timezone resolution logic (used by SetTenantScope middleware and Settings) |

---

## 11. SCHEDULED JOBS / ARTISAN COMMANDS (app/Console/Commands/)
| Command | Purpose |
|---|---|
| `CheckSubscriptionExpiry` | Scheduled — flips tenant status when subscription period ends |
| `SendSubscriptionReminders` | Scheduled — sends reminder emails before expiry |
| `CleanInactiveTenantData` | Cleanup job for long-inactive tenants |
| `FixHospitalAdminRoles` | One-off/repair command — fixes `is_super` role assignment issues |
| `ReseedTenantData` | Re-runs master data seeders for a specific tenant (useful after adding new master data) |
| `SyncTenantOTData` | Syncs/repairs OT-related data for a tenant |

**Queued Jobs** (`app/Jobs/`): `CreateTenantRolesAndPermissions` (runs on tenant registration), `SeedTenantDefaultMasters` (seeds eye-exam master dropdown data for new tenant), `SendSubscriptionEmail`, `SendWelcomeEmail`.

---

## 12. "WHERE DO I CHANGE X?" — QUICK LOOKUP CHEAT SHEET

| I need to... | Go to... |
|---|---|
| Change how MRD number (patient code) is generated | `app/Services/Hospital/PatientService.php` → `peekNextMrd()` |
| Change doctor's daily serial number logic | `app/Services/Hospital/PatientService.php` → `assignDoctorSerial()` |
| Add a new field to Patient registration form | 1) Migration in `database/migrations/` 2) `Patient.php` `$fillable` 3) `PatientStoreRequest.php`/`PatientUpdateRequest.php` validation 4) `PatientController@create/@edit` pass to view 5) `hospital/patients/create.blade.php` & `edit.blade.php` |
| Add a new permission | 1) `database/seeders/PermissionsSeeder.php` (add to master list) 2) `config/hospital_modules.php` if it needs a checkbox in the Roles UI 3) apply `->middleware('permission:new.key')` on the route |
| Add a new eye-exam master dropdown (e.g. new "Master XYZ") | Check if it fits `DetailMasterController`'s generic `{type}` pattern first (§6.7) — likely just needs migration + seeder, NOT a new controller |
| Change OT booking status flow | `app/Models/Hospital/OT/OtBooking.php` (STATUS_* constants) + relevant controller in `app/Http/Controllers/Hospital/OT/` for the specific stage transition |
| Fix a SuperAdmin icon (FA → BI) | Check `TODO.md` FIRST — exact file/line may already be documented | 
| Change Plan pricing logic | `app/Http/Controllers/SuperAdmin/PlanController.php` + `platform_settings` table (NOT a dedicated `plans` table) |
| Change subscription expiry behavior | `app/Console/Commands/CheckSubscriptionExpiry.php` + `app/Services/Platform/SubscriptionService.php` |
| Change tenant activation/suspension | `app/Http/Controllers/SuperAdmin/TenantController.php` (`@activate/@suspend/@extend/@reactivate`) + `TenantService` |
| Add a new hospital staff role capability | 1) `config/hospital_modules.php` 2) `PermissionsSeeder.php` 3) route middleware 4) Roles UI (`hospital/roles/`) auto-picks it up from config |
| Change PDF invoice/bill layout | `resources/views/pdfs/{invoice,opd-bill}.blade.php` |
| Change OT print documents (discharge/certificate/medicine-slip) | `resources/views/hospital/ot/billing/*_print.blade.php` — these are print-styled Blade, NOT DomPDF |
| Fix multi-tenant data leak / add tenant scoping to new model | Add `use App\Traits\BelongsToTenant;` + `use BelongsToTenant;` to the model — see §3.1 |
| Change unified login logic (all staff, one login page) | `app/Http/Controllers/Platform/UnifiedLoginController.php` |
| Change hospital registration flow | `app/Http/Controllers/Platform/RegisterController.php` + `app/Jobs/CreateTenantRolesAndPermissions.php` + `SeedTenantDefaultMasters.php` |
| Change location dropdown data (Country/State/District/City) | SuperAdmin: `app/Http/Controllers/SuperAdmin/LocationMasterController.php`; Models: `app/Models/Platform/MasterCountry/State/District/City.php` |
| Change cross-hospital patient-sharing feature | `app/Models/Platform/HospitalShareRequest.php` + `DashboardController@sendShareRequest/@acceptShareRequest` + `PatientController@searchByContact` (searches partner hospitals too) |
| Change mobile API behavior | `docs/MOBILE_APP_PRD.md` + `app/Http/Controllers/Api/*` — remember to check if web controller uses the same Service and needs parallel updates |

---

## 13. KNOWN GOTCHAS / THINGS TO BE CAREFUL ABOUT

1. **`PatientController.php.tmp`** exists in `app/Http/Controllers/Hospital/Patient/` — a stray temp file, not routed. Don't confuse with real `PatientController.php`.
2. **`SystemRolesSeeder.phpbkp`** exists in `database/seeders/` — a backup file, not the active seeder. Active one is `SystemRolesSeeder.php`.
3. **Legacy tables** `doctors`, `receptions`, `ot_staff` still exist in migration history (`2025_01_02_000005/6/7`) but are DEPRECATED — everything now uses unified `hospital_users`. A migration (`2026_03_25_150100_backfill_hospital_users_from_legacy_tables.php`) did the data migration. Never write new code against the old tables.
4. **Mixed `tbl_` prefix** — see §5.2. `DB::table('tbl_slots')`, `DB::table('tbl_cases')` etc. are called directly via raw query builder in some controllers (e.g. `PatientController`) rather than through Eloquent models — be aware both patterns coexist for the same conceptual entities.
5. **Plans are NOT a dedicated table** — pricing tiers are stored as key-value rows in `platform_settings` (see `PlanController` + `Subscription/index.blade.php`). If asked to "query the plans table", there isn't one — check `platform_settings` with the relevant `group`.
6. **Route name inconsistency**: `medicine_instructions.index` uses underscore while sibling routes use hyphen (`medicine-groups.index`, `medicine-categories.index`). This is a pre-existing inconsistency — don't "fix" without checking all Blade `route()` calls referencing it.
7. **SuperAdmin panel is mid-redesign** (see TODO.md/todotask.md) — before making UI changes there, always check these two files for already-planned/in-progress work to avoid duplicate or conflicting effort.
8. **`config/hospital_modules.php` keys must never be removed**, only appended — removing orphans existing `role_permissions` rows referencing that permission.
9. **Middleware order matters** for hospital routes — `identify.tenant` MUST run before `set.tenant.scope`, which MUST run before anything using `app('tenant')`.
10. **`BelongsToTenant::withoutTenantScope()`** is the ONLY safe way to intentionally query across tenants (e.g. partner-hospital patient search) — always use this explicitly rather than trying to disable the trait.
11. **The `User.php`** model at `app/Models/User.php` (Laravel's default) is essentially VESTIGIAL — not used for any of the 3 real auth guards (`superadmin`, `hospital_user`, `api`). Don't assume it's involved in hospital or superadmin authentication.
12. **Doctor-specific fields** (`doctor_type`, `doctor_prefix`, `foc_permission`, `registration_no`, `experience_years`, `signature_path`) live on the SAME `hospital_users` table as every other role — they're just nullable for non-doctor roles. Don't expect a separate `doctors` table.

---

## 14. HOW TO USE THIS PRD FOR A CHANGE REQUEST (Instructions for AI Agents)

When the user gives a change request, follow this exact sequence:
1. **Identify the module** — match the request to a section in §6 (Module-by-Module Breakdown).
2. **Get exact file paths** — from the tables, note the Controller file, Model file(s), View file(s), and Route name.
3. **Check §13 (Known Gotchas)** for anything relevant to that area before editing.
4. **Check §8 (Naming Conventions)** to keep new code consistent.
5. **If touching a tenant-scoped model**, confirm `BelongsToTenant` trait is present (§3.1) — don't accidentally create a tenant data leak.
6. **If touching permissions**, update BOTH `config/hospital_modules.php` (UI) AND `database/seeders/PermissionsSeeder.php` (master list) AND the route middleware — all three must stay in sync.
7. **If touching SuperAdmin views**, check `TODO.md` first for already-documented issues in that exact file.
8. **Make the change directly** in the identified file(s) — do not re-explore the whole codebase structure again; this PRD already has the map.
9. **If this PRD was missing information you had to discover by exploring**, please append/update the relevant section of this file so future requests don't need to re-discover it.

---

*End of PRD. This document should be kept up to date — treat it as living documentation, not a one-time snapshot.*
