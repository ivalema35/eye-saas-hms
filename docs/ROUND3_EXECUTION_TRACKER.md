# Round 3 — Execution Tracker (Web Sync + Mobile API Build)

**Companion to:** `docs/ROUND3_OT_MOBILE_API_PRD_PLAN.md` (the plan — phases, endpoints, permissions). This file is the **execution log** — what was actually done, when, and exactly what changed, in enough technical depth that the Flutter mobile/tablet app can be updated from this file alone without re-reading the whole web diff.

**How to use this file:** each phase gets one dated log entry below, appended as work happens. Each entry says: what was done, exact files touched, exact new/changed contracts (DB schema, permissions, endpoints with request/response shape), and anything the mobile app team needs to know or change. Status flows `In Progress` → `Done` (component-level detail lives here; checkbox tracking lives in the PRD/plan doc).

---

## Phase 0 — Sync `jeel` with `origin/main` — **DONE** (2026-08-04)

### What was done

1. **Fast-forwarded `jeel` branch** from `fc21125d` → `ddc3e2fc` (`git merge --ff-only origin/main`). Zero conflicts — `jeel` was a pure ancestor of `origin/main`, so this was a straight fast-forward, not a 3-way merge. 225 files changed, +23,315/−3,012 lines. Full file list: see `docs/ROUND3_OT_MOBILE_API_PRD_PLAN.md` §2 or `git log fc21125d..ddc3e2fc --stat`.
2. Verified `composer.json`/`composer.lock` unchanged by the merge — no `composer install` needed for new packages (ran it anyway, no-op, confirmed clean).
3. Cleared config/route/view caches (`php artisan config:clear && route:clear && view:clear`).
4. Verified `php artisan route:list --path=api` — **198 `api/v1/*` routes**, all registered cleanly, no collisions. Confirms the merge did not silently break any existing mobile API route.
5. Ran `php artisan migrate:status` — found and resolved a **pre-existing, unrelated DB bookkeeping gap** (see below), then ran the 25 new OT migrations.

### DB bookkeeping issue found & fixed (not caused by this merge — pre-existing on this local DB)

The local dev DB (`eye_hms_saas_new`) was originally provisioned in a way that didn't populate Laravel's `migrations` tracking table for 14 of the earliest migrations (`2025_01_02_000002` through `2025_01_02_000015` — the original schema bootstrap: `roles`, `hospital_users`, `patients`, `ot_bookings`, etc.). Their tables already existed with real data; only the tracking rows were missing, which made `php artisan migrate` try to `CREATE TABLE` on tables that already existed and fail on the very first one (`roles`).

Investigated each of these 14 migrations individually before touching anything:
- **10 of them** — every table they create already existed → safe to mark as already-applied (bookkeeping only, zero schema change).
- **`2025_01_02_000009_create_master_tables`** — creates 27 tables; 23 already existed, 4 (`tbl_chief_complaints`, `tbl_kcos`, `tbl_diagnoses`, `tbl_advices`) did not. Traced why: an already-applied later migration, `2026_03_30_122738_drop_unused_duplicate_master_tables`, **intentionally dropped** those 4 in favor of renamed replacements (`chief_complaints`, `kcos`, `diagnoses`, `master_advice`). Confirmed correct to leave them dropped, not recreate them.
- **`2025_01_02_000003/000006/000007/000008`** (`hospital_admins`, `doctors`, `receptions`, `ot_staff`) — none of these 4 tables existed. Traced why: already-applied migrations `2026_03_25_130151_fix_role_slugs_and_drop_legacy_tables` and `2026_03_25_150100_backfill_hospital_users_from_legacy_tables` had already migrated their data into the unified `hospital_users` + `roles` model and dropped these legacy per-role tables. Confirmed correct to leave them dropped, not recreate them.

**Action taken:** inserted bookkeeping rows (batch 1) into the `migrations` table for all 14 filenames — **no `CREATE TABLE` / `DROP TABLE` / data statements were executed**, this only corrected Laravel's own tracking metadata to match DB reality. Then ran `php artisan migrate --force` normally, which applied the real 25 pending OT-workflow migrations cleanly.

**If this same "table already exists" error shows up again on a different machine/DB** (e.g. `eye_care_new_clone_3`'s DB, or a teammate's local DB), it's this same pre-existing bookkeeping gap, not a new problem — same fix applies (verify table existence per migration before marking, don't blindly skip).

### New DB schema now live (relevant to mobile API work in later phases)

25 migrations applied — new/changed tables and columns the mobile API will read/write against:

| Table | Change | Relevant to |
|---|---|---|
| `ot_bookings` | new columns: payment-verified status, `ot_assistant_id`, `ot_doctor_id` now nullable | Phase 4, 5 |
| `ot_counselling` | expanded (package/room/charges breakdown fields) | Phase 1 |
| `ot_consents` (new table) | consent + signature capture | Phase 1 |
| `ot_appointments` (new table) | full appointment record incl. name parts, `referrer_id`, WhatsApp/mobile | Phase 2 |
| `ot_pre_op`, `ot_dilation_entries` | expanded (these existed as unused stub tables before — now actually wired to `OtWardController`) | Phase 3 |
| `ot_verifications` (new table) | 4-item pre-surgery checklist | Phase 4 |
| `ot_lens_details` | expanded (manufacturer, lens_name, lens_type, axis, batch/serial/expiry/mrp, `lens_inventory_id` FK) | Phase 4 |
| `ot_lens_powers` (new table) | lens power master | Phase 4, 7 |
| `lens_inventory` (new table) | stock master, `available_stock` auto-decremented on implant | Phase 7 |
| `ot_surgeries` | expanded | Phase 4 |
| `ot_surgery_medicines` (new table, tenant-scoped) | replaces old freeform `ward_medicines` array | Phase 4 |
| `ot_invoices` | + `follow_up_date` | Phase 5, 6 |
| `ot_package_masters` (new table) | package pricing master | Phase 1, 7 |
| `medicine_groups` | + `usage_scope` column (OT vs OPD) | Phase 4 |
| `exam_prescriptions` | + `quantity`, `route` | out of this round's API scope, informational |
| `master_countries`, `tenants` | + currency, country_code, FX fields | out of this round's API scope, informational |

### New permissions live (from `PermissionsSeeder`)

`ot.appointment.view`, `ot.appointment.create`, `ot.appointment.edit`, `ot.consent.capture`, `ot.surgery.recommend`, `ot.billing.manage` — all seeded. (Full permission-key-to-endpoint mapping, including 7 corrections found vs. the web-shipped mobile PRD doc, is in `docs/ROUND3_OT_MOBILE_API_PRD_PLAN.md` §12 — use that table as the source of truth when building each phase's controller, not the shipped `OT_WORKFLOW_UPGRADE_MOBILE_API_PRD.md` directly.)

### Verification performed
- [x] `route:list --path=api` — 198 routes, no collisions
- [x] `migrate:status` — 0 pending
- [x] No composer dependency drift
- [ ] Full web click-through regression (booking/accountant/counsellor/ward/assistant/billing/discharge screens) — **not yet done, recommend before building on top of this**
- [ ] Existing mobile API smoke test (Round 1/2 endpoints — patients, exam, dashboard, masters, medicine, etc.) — **not yet done**

### For the mobile/tablet app team
Nothing to change yet from this phase alone — this was purely a web-side sync, zero mobile API contract changes. **Phases 1–8 (next entries in this file) are what actually add/change mobile-facing contracts.**

---

## Phase 1 — Counsellor Module API — **DONE** (2026-08-04)

### What was built
New file: `app/Http/Controllers/Api/OtCounsellorApiController.php` — mirrors `app/Http/Controllers/Hospital/OT/OtCounsellorController.php` method-for-method, same validation rules, same DB writes (via the same `OtCounselling`/`OtConsent`/`OtBooking` models), only the response type changes (JSON instead of view/redirect). Zero changes to the web controller or any blade view.

Routes added to `routes/api.php` (inside the existing `{slug}` + `auth:sanctum` group, right after the existing stub `ot/bookings/{id}/status` route):

| Method | Path | Controller method | Permission |
|---|---|---|---|
| GET | `/api/v1/{slug}/ot/counsellor/bookings` | `bookings()` | `ot.counselling.fill` |
| GET | `/api/v1/{slug}/ot/counsellor/package-lookup` | `lookupPackage()` | `ot.counselling.fill` |
| GET | `/api/v1/{slug}/ot/bookings/{id}/counselling` | `show()` | `ot.counselling.fill` |
| POST | `/api/v1/{slug}/ot/bookings/{id}/counselling` | `storeCounselling()` | `ot.counselling.fill` |
| POST | `/api/v1/{slug}/ot/bookings/{id}/consent` | `storeConsent()` | `ot.consent.capture` |
| POST | `/api/v1/{slug}/ot/bookings/{id}/send-to-billing` | `sendToBilling()` | `ot.counselling.fill` |

(Route names: `api.v1.hospital.ot.counsellor.bookings`, `...package-lookup`, `...ot.counselling.show`, `...ot.counselling.store`, `...ot.consent.store`, `...ot.send-to-billing`.)

### Request/response contracts (for the Flutter app)

**`GET .../ot/counsellor/bookings?per_page=25`**
Response: `{ success: true, data: <Laravel paginator> }` — `data.data[]` items are `OtBooking` rows with `patient` (`id, patient_code, first_name, middle_name, last_name, contact_no`) and `otDoctor` (`id, name`) eager-loaded. Filtered to `ot_status` in `booked`/`surgery_recommended`, recommended cases sorted first.

**`GET .../ot/counsellor/package-lookup?lens_cost=&room_category=general|private`**
Response: `{ success: true, data: { found: bool, package?: { package_name, ot_charges, surgeon_charges, nursing_charges, consumables_charges } } }`

**`GET .../ot/bookings/{id}/counselling`**
Response: `{ success: true, data: { booking, counselling, consent, lens_options: [{id,name}], package_cost_options: [{id,package_name,lens_cost,room_category,ot_charges,surgeon_charges,nursing_charges,consumables_charges}] } }` — `counselling`/`consent` are `null` if not yet filled.

**`POST .../ot/bookings/{id}/counselling`**
Body: `diagnosis, eye (RE|LE|Both, required), surgery_type_confirmed, mediclaim (required bool), lens_option (must match an active ot_lens_options.name), lens_category (standard|premium), lens_company, lens_model, lens_type, estimated_power, lens_cost, package_name, room_category (general|private), ot_charges, surgeon_charges, nursing_charges, consumables_charges, payment_mode (mediclaim if mediclaim=true, else cash|online), report_ok, blood_reports_verified, blood_reports_normal, notes`.
Response: `{ success: true, message, data: { total_estimate, counselling } }`. **Note:** `total_estimate` is currently just `lens_cost` rounded — same as the web version (this is existing web behavior, not something the API changed).

**`POST .../ot/bookings/{id}/consent`**
Body: `consent_given (required bool), patient_signature (base64 PNG data URI, optional), guardian_signature (base64 PNG data URI, optional), witness_name`.
Response: `{ success: true, message, data: { consent_given, patient_signature_url, guardian_signature_url, witness_name, consent_date } }` — signature URLs are full public storage URLs (`Storage::disk('public')->url()`), ready to display directly. **Mobile team: build the signature-pad as a canvas → base64 PNG data URI, same as the shipped PRD flagged.**

**`POST .../ot/bookings/{id}/send-to-billing`**
No body. Response: `{ success: true, message, data: { ot_status } }` on success. Returns `422` with `message` if counselling not yet saved, consent not given, or package amount is 0.

### Deviations from `docs/ROUND3_OT_MOBILE_API_PRD_PLAN.md` §5
None on permissions/behavior (plan was already corrected in §12 before this was built). One path deviation from the *original shipped* `OT_WORKFLOW_UPGRADE_MOBILE_API_PRD.md` (not from our own plan): consent/send-to-billing use `ot/bookings/{id}/...` (booking-centric), matching FR-OT-20/21 as written there — no change needed.

### Verification performed
- [x] `php -l` on new controller + `routes/api.php` — no syntax errors
- [x] `php artisan route:list` — all 6 routes registered, correct permission middleware, no name/path collisions with the existing web `hospital.ot.counsellor.*` routes
- [x] Ran the actual Eloquent queries (bookings list, active packages, active lens options) via tinker against the real tenant (`eye-care-aeh`, tenant_id=1) — executed cleanly, 0 SQL/column errors. (Row counts are 0 — no OT counselling data seeded yet on this dev tenant, expected.)
- [ ] Live HTTP smoke test with a real Sanctum token — **not done yet**, needs a hospital_user login + token generation, recommend doing this as part of the eventual combined smoke-test pass (§14.1 of the plan doc) rather than one-off per phase, unless you want it now.

### For the mobile/tablet app team
This is entirely new API surface — nothing existed before (`OtApiController` stub had no counselling support at all). Build against the contracts above. Signature capture is a new UI component (canvas/signature-pad), flagged in the plan doc §5 already.

---

## Phase 2 — Appointment Module API — **DONE** (2026-08-04)

### What was built
New file: `app/Http/Controllers/Api/OtAppointmentApiController.php` — mirrors `Hospital\OT\OtAppointmentController` method-for-method (same validation, same `OtAppointment` model writes). One addition beyond the original plan/PRD: a `formData()` endpoint (doctors/locations/referrers/slots/next-appointment-number), since the mobile app can't build the create/edit form without it — web gets this from `create()`/`edit()`'s blade data, mobile needs it as JSON.

Routes added to `routes/api.php` (new `ot/appointments` group, right after the Phase 1 counsellor block):

| Method | Path | Controller method | Permission |
|---|---|---|---|
| GET | `/api/v1/{slug}/ot/appointments?status=&date=&search=&per_page=` | `index()` | `ot.appointment.view` |
| GET | `/api/v1/{slug}/ot/appointments/form-data` | `formData()` **(new, not in original PRD)** | `ot.appointment.view` OR `ot.appointment.create` |
| POST | `/api/v1/{slug}/ot/appointments` | `store()` | `ot.appointment.create` |
| GET | `/api/v1/{slug}/ot/appointments/search?q=` | `search()` | `ot.appointment.view` OR `opd.patient.register` (matches web exactly — reception check-in reuses this) |
| GET | `/api/v1/{slug}/ot/appointments/slot-appointments?date=&time=&exclude_id=` | `slotAppointments()` | `ot.appointment.view` OR `ot.appointment.create` |
| PUT | `/api/v1/{slug}/ot/appointments/{id}` | `update()` | `ot.appointment.edit` |
| POST | `/api/v1/{slug}/ot/appointments/{id}/confirm` | `confirm()` | `ot.appointment.edit` |
| POST | `/api/v1/{slug}/ot/appointments/{id}/cancel` | `cancel()` | `ot.appointment.edit` |

(8 endpoints total — 7 from the plan + `form-data`.)

### Request/response contracts (for the Flutter app)

**`GET .../ot/appointments?status=all|booked|confirmed|cancelled|completed&date=YYYY-MM-DD&search=`**
`{ success: true, data: <paginator> }` — items have `doctor:{id,name}` and `location:{id,name}` eager-loaded, plus the `appointment_number` accessor (`APT-000123`).

**`GET .../ot/appointments/form-data`**
`{ success: true, data: { doctors: [{id,name}], locations: [{id,name,district,state}] (filtered to the hospital's own country, same as OPD walk-in form), referrers: [...], slots: [...], next_appointment_number: "APT-000123" } }`

**`POST .../ot/appointments`**
Body: `appointment_type (phone|walk_in|online|referral, required), appointment_date (required, >= today), appointment_time (H:i), doctor_id (required, must belong to tenant), patient_name (required), middle_name, surname (required), mobile_no (required, phone-format validated), whatsapp_no, age (required), gender (required: male|female|other), occupation, referrer_id, location_id (required), notes`.
`201 { success: true, message, data: <appointment> }`. Status is always set server-side to `booked`.

**`PUT .../ot/appointments/{id}`** — same body shape as store (minus the `after_or_equal:today` constraint on the date, matching web). `{ success, message, data: <appointment with doctor/location loaded> }`.

**`POST .../ot/appointments/{id}/confirm`** and **`/cancel`** — no body. `{ success: true, message, data: { status } }`.

**`GET .../ot/appointments/search?q=`** — matches by `APT-NNNNNN` number, mobile, or name. Used by Reception check-in to pre-fill OPD registration. `{ success, data: { found: bool, appointments: [...] } }`, only `booked`/`confirmed` status appointments returned.

**`GET .../ot/appointments/slot-appointments?date=&time=&exclude_id=`** — double-booking check for the create/edit form. `{ success, data: { appointments: [{id,name,status}] } }`.

### Verification performed
- [x] `php -l` clean on controller + routes file
- [x] `route:list` — 8 new routes registered, no collisions with the 6 existing web `hospital.ot.appointments.*` routes
- [x] Tinker: ran `OtAppointment` count query, doctor-role lookup, `OtSlot` list, and the next-appointment-number generation against the real tenant — no SQL/column errors
- [ ] Live HTTP smoke test with real token — pending, same as Phase 1

### For the mobile/tablet app team
Entirely new surface (no appointment API existed before this round). Use `form-data` once per screen-load to populate the create/edit form's dropdowns, then `POST`/`PUT` with just the field values.

---

## Phase 3 — Ward Module API — **DONE** (2026-08-04)

### What was built
New file: `app/Http/Controllers/Api/OtWardApiController.php` — mirrors `Hospital\OT\OtWardController`. Same `WARD_ALLOWED_STATUSES` gate (`payment_verified`, `in_ward`, `dilated`, `ready`), same `storeVitals` staff-assignment sub-flow (`assign_staff=true` body flag also sets `ot_doctor_id`/`ot_assistant_id`), same auto-advance of `payment_verified` → `in_ward` on first vitals save. Split into the 5 endpoints the plan doc specified rather than one combined `show()` like the web version.

| Method | Path | Controller method | Permission |
|---|---|---|---|
| GET | `/api/v1/{slug}/ot/bookings/{id}/vitals` | `vitals()` | `ot.ward.entry` |
| POST | `/api/v1/{slug}/ot/bookings/{id}/vitals` | `storeVitals()` | `ot.preop.entry` |
| GET | `/api/v1/{slug}/ot/bookings/{id}/eye-drops` | `eyeDrops()` | `ot.ward.entry` |
| POST | `/api/v1/{slug}/ot/bookings/{id}/eye-drops` | `addEyeDrop()` | `ot.dilation.track` |
| GET | `/api/v1/{slug}/ot/bookings/{id}/verification-header` | `verificationHeader()` | `ot.ward.entry` |

### Important data-model correction (not in the plan doc, found while building)
**`ot_pre_op` has no history** — the table stores exactly one row per booking (`updateOrCreate` every save, no separate log/audit table). The plan doc's §7 phrased `GET .../vitals` as "latest + history" based on the shipped PRD's wording — there is no history to return. The response is `{ data: { latest: <single row or null> } }` only. If the client actually needs a vitals history/audit trail, that's a **new backend feature**, not something this API phase can expose — flag to the web/backend side if the mobile team needs it, don't expect it from this endpoint.

### Bug caught and fixed before shipping
Initial draft of `storeVitals()`'s response called `$booking->fresh()->load('preOp')` — `OtBooking` has **no `preOp` relation defined** (verified: only `patient`, `otDoctor`, `otAssistant`, `bookedBy`, `counselling`, `consent`, `payments` exist on the model). That would have thrown `BadMethodCallException` on every vitals save. Caught during review before any test ran; fixed to just return `{ ot_status }`.

### Request/response contracts (for the Flutter app)

**`GET .../vitals`** → `{ success: true, data: { latest: <OtPreOp row with enteredBy:{id,name}, or null> } }`

**`POST .../vitals`**
Body: `bp, pulse, rbs (0-999.9), temperature (0-99.9), spo2 (0-100), hba1c (0-99.9), pre_op_status (required: preparing|ready_for_surgery|hold|complicated|not_fit)`. Optional staff-assignment sub-flow: add `assign_staff=true` + (`ot_doctor_id` required unless status is `ready_for_surgery`) + (`ot_assistant_id` required when status IS `ready_for_surgery`).
`{ success: true, message, data: { ot_status } }` — `422` if booking isn't in an allowed ward status.

**`GET .../eye-drops`** → `{ success: true, data: { eye_drops: [<OtDilationEntry with administeredBy:{id,name}>, ...] } }` ordered by administered_at then dose_number.

**`POST .../eye-drops`**
Body: `medicine_name (required), eye (required: RE|LE), dose_number (required, 1-20), administered_at (optional, defaults now), remarks`. `administered_by` set server-side from the authenticated token user.
`201 { success: true, message, data: <entry with administeredBy> }`

**`GET .../verification-header`** → `{ success: true, data: { uhid, patient_name, surgery_type, eye } }` — read-only, saves mobile from stitching booking+patient calls together.

### Verification performed
- [x] `php -l` clean on controller + routes
- [x] `route:list` — 5 new routes, no collision with existing `ot/bookings/*` routes (Phase 1's counselling/consent routes, or the web's `hospital.ot.bookings.*`)
- [x] Tinker: `OtPreOp`/`OtDilationEntry` count queries against real tenant — clean
- [x] Caught and fixed the undefined-relation bug above (this is exactly why every phase runs a tinker/route sanity pass before being marked done, not just `php -l`)
- [ ] Live HTTP smoke test with real token — pending, same as prior phases

### For the mobile/tablet app team
Same permission split as web: whoever can view the ward screen (`ot.ward.entry`) is not necessarily who can save vitals (`ot.preop.entry`) or log an eye-drop (`ot.dilation.track`) — these can be three different roles/logins on a shared tablet, gate the UI accordingly, not just the API calls.

---

## Phase 4 — Surgery + Lens API — **DONE** (2026-08-04)

### What was built
New file: `app/Http/Controllers/Api/OtAssistantApiController.php` — mirrors `Hospital\OT\OtAssistantController`. Also modified one **existing** file: `app/Http/Controllers/Api/MedicineGroupApiController.php::index()` — added an optional `?scope=` query param (additive, backward-compatible: no `scope` = old unfiltered behavior, unchanged for existing OPD callers).

| Method | Path | Controller method | Permission |
|---|---|---|---|
| GET | `/api/v1/{slug}/ot/assistant/bookings` | `bookings()` | `ot.lens.record\|ot.lens.implant\|ot.surgery.ready\|ot.surgery.record\|ot.patient.list` (OR-group, matches web) |
| GET | `/api/v1/{slug}/ot/medicine-groups` (add `?scope=ot`) | `MedicineGroupApiController::index()` (reused, not duplicated) | `ot.surgery.record` |
| GET | `/api/v1/{slug}/ot/bookings/{id}/surgery-form-data` | `surgeryFormData()` **(new, not in original PRD)** | `ot.surgery.record` |
| POST | `/api/v1/{slug}/ot/bookings/{id}/surgery` | `storeSurgery()` | `ot.surgery.record` |
| GET | `/api/v1/{slug}/ot/bookings/{id}/lens` | `lens()` | `ot.lens.record\|ot.lens.implant` |
| POST | `/api/v1/{slug}/ot/bookings/{id}/lens` | `storeLens()` | `ot.lens.record\|ot.lens.implant` |

### Two real deviations from the plan doc (found while building, both are "actual web behavior differs from the shipped PRD's wording", not API design choices)

1. **No separate "ready-for-lens" queue.** The plan doc's §8 (`FR-OT-29`) description assumed `bookings()` returns both a ready-for-surgery and a ready-for-lens queue (based on the original shipped `OT_WORKFLOW_UPGRADE_MOBILE_API_PRD.md` wording). The **actual current web `dashboard()`** only returns the ready-for-surgery queue — its own code comment says *"Lens workflow UI hidden — counselling already captures planned lens; routes/controllers kept for optional direct access / future use."* The API mirrors this actual current behavior — only one queue.
2. **No standalone `/verify` endpoint.** `FR-OT-29` in the plan doc specified `POST .../verify` as a separate pre-surgery-checklist endpoint. On the web side, there is no separate verify action — `identity_verified`/`consent_verified`/`payment_verified`/`correct_eye_verified` are just `accepted`-rule fields validated and written (`OtVerification::updateOrCreate`) **inside the same `storeSurgery()` transaction**, atomically with the surgery record. Building a standalone verify endpoint would mean inventing new backend behavior that doesn't exist — out of scope for an API-mirroring round. The checklist fields are part of the `POST .../surgery` body instead (see contract below).

### Request/response contracts (for the Flutter app)

**`GET .../ot/assistant/bookings?per_page=`** → `{ success, data: <paginator of ot_status=ready bookings> }`. Non-admin users only see bookings where `ot_assistant_id` = their own user id; Hospital Admin / super users see all.

**`GET .../ot/medicine-groups?scope=ot`** → same shape as the existing `medicine-groups` endpoint, filtered to `usage_scope` in (`ot`, `both`) when `scope` is passed.

**`GET .../ot/bookings/{id}/surgery-form-data`** → `{ success, data: { booking, counselling, verification, surgery_types: [{id,surgery_name}], medicines: [{name}], medicine_groups: [...with items.medicine] } }`

**`POST .../ot/bookings/{id}/surgery`**
Body: `identity_verified, consent_verified, payment_verified, correct_eye_verified (all must be true/"accepted" — 422 if any missing), surgery_name (required), ot_room, eye_operated (required: RE|LE|Both), start_time, end_time (after start_time), complication_status (required: none|minor|major), complication_notes, blood_loss, medicine_group_id, ot_medicines[] ({medicine, dose})`.
Extra safety check (matches web): if booking's booked eye and `eye_operated` are both specific (not "Both") and don't match, returns `422` with a clear message. On success, booking → `operated`, `201 { success, message, data: <surgery> }`.

**`GET .../ot/bookings/{id}/lens`** → `{ success, data: { booking, lens_detail, lens_types: [8 fixed values], lens_powers, lens_inventory (in-stock + currently-selected item even if 0 stock) } }`

**`POST .../ot/bookings/{id}/lens`**
Body: `lens_inventory_id, lens_name (required), manufacturer, lens_type (required, one of the 8 fixed types), lens_power (required, -99.99..99.99), axis (0-180), lens_mrp (required), batch_number, serial_number, expiry_date, implanted (bool)`.
On `implanted: true` transitioning from `false`, server auto-decrements `lens_inventory.available_stock` via the **same `LensInventoryService`** the web uses (no reimplementation) — never double-decrements on re-saves. `422` if booking is `discharged` or in a non-active-OT status.

### Verification performed
- [x] `php -l` clean on both new/modified controllers + routes
- [x] `route:list` — 12 new/reused routes registered, no collisions
- [x] Tinker: ready-bookings count, OT-scoped medicine groups, lens powers, lens inventory, surgery types — all queried cleanly against real tenant
- [ ] Live HTTP smoke test — pending, same as prior phases
- [ ] **Not tested:** the stock-decrement transition itself (needs real `lens_inventory` + `ot_lens_details` rows, none seeded yet) — logic is a direct reuse of `LensInventoryService`, already proven on the web side, but flagging that the *mobile call path specifically* hasn't been exercised end-to-end

### For the mobile/tablet app team
`POST .../surgery` is one combined "verify + record" screen, not two steps — build it as a single form/submit with the 4 checklist toggles plus the surgery fields, matching the web UX exactly (don't split it into a separate confirm-then-record flow, the backend has no state for a partial verify).

---

## Phase 5 — Billing / Payment Verification API — **DONE** (2026-08-04)

### What was built
New file: `app/Http/Controllers/Api/OtAccountantApiController.php` — mirrors `Hospital\OT\OtAccountantController`.

| Method | Path | Controller method | Permission |
|---|---|---|---|
| GET | `/api/v1/{slug}/ot/accountant/bookings?filter=today\|completed` | `bookings()` **(new — see below)** | `ot.payment.record` |
| GET | `/api/v1/{slug}/ot/bookings/{id}/payment-status` | `paymentStatus()` | `ot.invoice.view` |
| GET | `/api/v1/{slug}/ot/bookings/{id}/payment-form-data` | `paymentFormData()` **(new — see below)** | `ot.payment.record` |
| POST | `/api/v1/{slug}/ot/bookings/{id}/payments` | `storePayment()` **(new — see below)** | `ot.payment.record` |
| GET | `/api/v1/{slug}/ot/payments/{paymentId}/receipt` | `receipt()` **(new — see below)** | `ot.payment.record` |

### Two important corrections vs. the plan doc

1. **FR-OT-34 was never actually blocked — it's already automatic, no separate endpoint needed.** The plan doc (§9) said the counsellor payment-verification step was blocked because no `OtCounsellorController::verifyPayment()` exists. Reading `OtAccountantController::storePayment()` more closely: the auto-advance to `payment_verified` **already happens automatically**, the instant cumulative payments reach the package amount — no separate confirm action exists *because none is needed*. This matches the exact same pattern found in Phase 4 (verification checklist folded into `storeSurgery()`, not a separate call). Handled inside `storePayment()` below.
2. **The plan doc only listed a read-only `payment-status` endpoint — it missed that Accountant also needs to record payments from mobile/tablet.** Added `bookings()` (Accountant's own queue, mirrors web `dashboard()`), `paymentFormData()` (mirrors `createPayment()` — lazily creates the OT invoice on first visit, same as web), `storePayment()` (mirrors `storePayment()` exactly, including the `Schema::hasColumn()` defensive checks for `export_amount`/`accountant_id` — **confirmed via tinker these columns genuinely don't exist on this DB**, so keeping those guards was correct, not just copied defensively for no reason), and `receipt()` (mirrors `receiptPrint()`, returns the same data as JSON instead of a printable view).

### Request/response contracts (for the Flutter app)

**`GET .../ot/bookings/{id}/payment-status`** → `{ success, data: { payment_status: unpriced|pending|partially_paid|paid, required_total, total_paid, remaining_balance, payments: [...] } }` — all derived from the `OtBooking` model's own accessors, never a stored/stale column.

**`GET .../ot/accountant/bookings?filter=today|completed`** → `{ success, data: <paginator>, meta: { filter } }` — `today` = pending-payment queue (status `counselled`/`paid`, today's surgery date); `completed` = payment done onward.

**`GET .../ot/bookings/{id}/payment-form-data`** → `{ success, data: { booking, counselling, invoice (auto-created if missing), default_package_amount, total_paid_so_far, required_total, auto_receipt_number, default_payment_mode } }`

**`POST .../ot/bookings/{id}/payments`**
Body: `package_amount (required, 0.01..remaining balance), receipt_number (optional, auto-generated if blank), payment_mode (required: cash|online|mediclaim)`.
`201 { success, message, data: { payment_id, ot_status, is_fully_paid, remaining_balance } }` — `ot_status` flips to `payment_verified` automatically when `is_fully_paid` is true, same call, no second step. `422` if booking isn't in `counselled`/`paid` status, package amount is 0, or already fully paid.

**`GET .../ot/payments/{paymentId}/receipt`** → `{ success, data: { payment (with booking.patient, recordedBy), total_paid, required_total } }`

### Verification performed
- [x] `php -l` clean
- [x] `route:list` — 5 new routes, no collisions
- [x] Tinker: confirmed `ot_payments.export_amount`/`accountant_id` genuinely don't exist on this DB (validates keeping the `Schema::hasColumn()` guards, not stripping them as dead code) and `ot_invoices.is_finalized` does exist
- [ ] Live HTTP smoke test — pending, same as prior phases
- [ ] Not tested: the full-payment → auto-verify transition itself (no `ot_bookings` rows with `counselled` status seeded yet to test against)

### For the mobile/tablet app team
`storePayment` is the single call that both records payment AND (when it completes the package amount) advances the booking to Ward — there is no separate "verify payment" call to make, don't build one client-side.

---

## Phase 6 — Discharge Document API — **DONE** (2026-08-04)

### What was built
New file: `app/Http/Controllers/Api/OtDischargeApiController.php` — mirrors `Hospital\OT\OtInvoiceController` + `Hospital\OT\OtDischargeController`. PDFs are generated by loading the **exact same blade views the web prints from** through `Barryvdh\DomPDF\Facade\Pdf::loadView(...)->download(...)` — the same pattern already used by `Api\ReportsApiController::exportPdf`, confirmed via tinker that the Pdf facade class resolves correctly. No new templates were created.

| Method | Path | Controller method | Notes |
|---|---|---|---|
| GET | `/api/v1/{slug}/ot/billing/bookings` | `bookings()` **(new)** | operated/discharged queue |
| POST | `/api/v1/{slug}/ot/bookings/{id}/invoice/generate` | `generateInvoice()` **(new)** | see below |
| GET | `/api/v1/{slug}/ot/bookings/{id}/print/invoice` | `invoicePrint()` | PDF download |
| GET | `/api/v1/{slug}/ot/bookings/{id}/print/summary-bill` | `summaryBillPrint()` | PDF download |
| GET | `/api/v1/{slug}/ot/bookings/{id}/print/discharge` | `dischargePrint()` | PDF download |
| GET | `/api/v1/{slug}/ot/bookings/{id}/print/certificate` | `certificatePrint()` | PDF download, `?rest_days=` (1-90) |
| GET | `/api/v1/{slug}/ot/bookings/{id}/print/medicine-slip` | `medicineSlipPrint()` | PDF download |
| GET | `/api/v1/{slug}/ot/bookings/{id}/print/prescription` | `prescriptionPrint()` | PDF download |
| GET | `/api/v1/{slug}/ot/bookings/{id}/print/lens-slip` | `lensSlipPrint()` | PDF download |
| GET | `/api/v1/{slug}/ot/bookings/{id}/print/followup-slip` | `followupSlipPrint()` | PDF download, 404 if invoice not generated yet |
| GET | `/api/v1/{slug}/ot/bookings/{id}/print/discharge-bundle` | `printAllBundle()` | **JSON manifest, not a PDF — see deviation below** |

All 11 gated by single permission `ot.billing.manage` (verified — the shipped PRD's finer-grained `ot.invoice.view` per-endpoint split doesn't exist in the real route gating, same finding as documented back in the plan doc §12).

### Two things added beyond the plan doc, and one real deviation

1. **Added `generateInvoice()` and `bookings()`.** The plan doc only listed the 9 read-only print endpoints. Without `generateInvoice()` (mirrors `OtInvoiceController::generate()` exactly — same line-item breakdown logic: prefers the Counsellor's itemized costs when present, falls back to charge-head percentage split, rounds to match package amount), there would be no invoice for any of the print endpoints to print, and no way for mobile to mark a booking `discharged`. `bookings()` mirrors `OtInvoiceController::index()`'s operated/discharged queue.
2. **`printAllBundle()` deviates from the plan doc's "single merged PDF" framing.** The web version (`hospital.ot.billing.print_all` blade) is a **browser-only page that iframes each individual print route** for one-by-one printing — it is not a merged PDF on the web side either. dompdf can't execute iframes/JS, and building real server-side PDF merging would be genuinely new backend behavior, not a mirror of anything that exists — out of scope for this round. Instead, this endpoint returns `{ success, data: { documents: [{label, download_url}, ...] } }`, a manifest of all 8 individual PDF URLs (built via Laravel's own `route()` helper against the routes above, so they can't drift out of sync). **Flag to the user:** if a true single merged PDF file is wanted for the discharge counter's "one-tap print," that needs a PDF-merge package (e.g. `setasign/fpdi`) added as a new dependency — a real scope decision, not something to add silently.

### Request/response contracts (for the Flutter app)

**`POST .../ot/bookings/{id}/invoice/generate`**
Body: `tax_amount, discount, follow_up_date (optional, defaults to existing or +7 days)`.
`201 { success, message, data: { invoice_number, total_amount, net_amount } }` — booking flips to `discharged` in this same call.

**All 8 `GET .../print/*` endpoints** (except `discharge-bundle`) return a **binary PDF file download** (`Content-Type: application/pdf`), not JSON — client should handle these as file downloads / open in a PDF viewer, same as any other file-download API call, not parse as JSON.

**`GET .../print/discharge-bundle`** → JSON manifest (see deviation above), `422` if no invoice generated yet.

### Verification performed
- [x] `php -l` clean
- [x] `route:list` — 11 new routes, no collisions
- [x] Confirmed all 8 referenced blade view files exist on disk (`hospital/ot/billing/*_print.blade.php`)
- [x] Confirmed `Barryvdh\DomPDF\Facade\Pdf` class resolves (dompdf is a real, already-used dependency — no new package needed for the 8 individual PDFs)
- [x] Tinker: operated/discharged booking count and active charge-heads queries ran clean against real tenant
- [ ] **Not tested: actually rendering a PDF end-to-end** (needs a real booking with counselling + invoice + surgery data, none seeded yet) — the query/view-binding logic is a direct line-for-line port of the web controllers, but dompdf rendering quirks (fonts, page breaks) can only be confirmed by generating a real PDF once test data exists
- [ ] Live HTTP smoke test — pending, same as prior phases

### For the mobile/tablet app team
The 8 print endpoints return raw PDF bytes — download and open in an in-app PDF viewer or hand off to the OS share sheet, same as the plan doc's original guidance. `discharge-bundle` is the one exception: it's JSON with 8 URLs, not a file — fetch each and either open sequentially or merge client-side if the app has that capability; there is no server-side merged file today.

---

## Phase 7 — Lens Inventory Master API — **DONE** (2026-08-04)

### What was built
New file: `app/Http/Controllers/Api/OtInventoryApiController.php` — mirrors `Hospital\Master\OT\LensInventoryController` + `OtLensPowerController` + `OtPackageMasterController`. **Also required a new migration + seeder entry** — this is the permission-gate decision flagged back in the original plan doc §11/§12.

### The permission-gate decision (resolved)
On the web, `masters/ot/{lens-inventory,lens-powers,packages}` are gated by `middleware('role:admin')`, not a `permission:` key. `CheckRole` (`app/Http/Middleware/CheckRole.php`) hardcodes `auth('hospital_user')` — the **session guard only** — so it cannot authenticate a Sanctum bearer-token mobile request at all; reusing it on API routes would 403 every mobile call, 100% of the time, regardless of who's logged in.

**Went with option (a) from the plan doc:** added a new permission key `master.ot_inventory` (matches the existing `master.ot_slots`/`master.ot_types`/`master.ot_charges` naming convention exactly) via:
- `database/seeders/PermissionsSeeder.php` — added the permission definition (for brand-new tenants provisioned from now on).
- `database/migrations/2026_08_04_120000_add_and_grant_ot_inventory_manage_permission.php` — backfills the permission row and grants it to `hospital_admin` for **existing** tenants, using the **exact same pattern** already proven in `2026_07_23_130000_grant_ot_billing_manage_to_roles.php`. Ran successfully — verified via tinker that all 34 tenants' `hospital_admin` roles now have this permission granted.

**Side finding while verifying:** it turns out `hospital_admin` is an `is_super` role, and `CheckPermission` middleware bypasses **all** permission checks entirely for `is_super` users (confirmed by reading the middleware) — so `hospital_admin` would have passed through `permission:master.ot_inventory` even without this migration. The migration was still worth doing: it makes the permission visible/auditable in the role-management UI, and means the client can later grant this to a *non-admin* role (e.g. a dedicated inventory manager) with a UI toggle instead of needing a new migration. **New routes are additive only — the web route's `role:admin` gate is completely untouched, zero risk to web behavior.**

| Method | Path | Controller method |
|---|---|---|
| GET/POST | `/api/v1/{slug}/masters/ot/lens-inventory` | `lensInventoryIndex()` / `lensInventoryStore()` |
| PUT/DELETE | `/api/v1/{slug}/masters/ot/lens-inventory/{id}` | `lensInventoryUpdate()` / `lensInventoryDestroy()` |
| GET/POST | `/api/v1/{slug}/masters/ot/lens-powers` | `lensPowerIndex()` / `lensPowerStore()` |
| PUT/DELETE | `/api/v1/{slug}/masters/ot/lens-powers/{id}` | `lensPowerUpdate()` / `lensPowerDestroy()` |
| GET/POST | `/api/v1/{slug}/masters/ot/packages` | `packageIndex()` / `packageStore()` |
| PUT/DELETE | `/api/v1/{slug}/masters/ot/packages/{id}` | `packageUpdate()` / `packageDestroy()` |
| GET | `/api/v1/{slug}/ot/lens-inventory/search?q=&type=&power=&include_out_of_stock=` | `lensInventorySearch()` — typeahead for Phase 1 (counselling) + Phase 4 (lens form) pickers |

First 12 gated by `permission:master.ot_inventory`. Search endpoint gated by `ot.counselling.fill|ot.lens.record|ot.lens.implant` (whoever can reach the pickers that use it, not admin-only).

### Verification performed
- [x] `php -l` clean
- [x] `route:list` — 13 new routes, no collisions with existing flat `masters/ot-slots` etc. routes
- [x] Migration ran successfully; tinker-confirmed permission row exists and is granted to all 34 tenants' `hospital_admin` roles
- [x] Tinker: `LensInventory`/`OtLensPower` queries against real tenant — clean
- [ ] Live HTTP smoke test — pending, same as prior phases

### For the mobile/tablet app team
Only Hospital Admin can manage these masters (matches web exactly). The search/typeahead endpoint is separately permissioned so counsellors and OT assistants can use the *picker*, without needing master-data-edit rights.

---

## Phase 8 — Reports & Dashboard API — **DONE** (2026-08-04)

### What was built
New file: `app/Http/Controllers/Api/OtReportApiController.php`, which **extends** `Hospital\Report\OtReportController` rather than duplicating it — the parent's ~400-line, 14-report-type `buildReport()` engine is reused directly. Required 3 visibility-only changes to the parent (zero behavior change, verified):
- `OtReportController::REPORTS` — `private const` → `protected const`
- `OtReportController::resolveDateRange()` — `private function` → `protected function`
- `OtReportController::buildReport()` — `private function` → `protected function`

**Bug hit and fixed during this phase:** first attempt named the new methods `index()`/`show()` to match the parent — PHP fatal-errored at route registration (`Declaration ... must be compatible with ... OtReportController::index(): View`) because a JSON-returning override isn't covariant with the parent's `View` return type. Fixed by naming them `apiIndex()`/`apiShow()` instead — different names sidestep the override-compatibility rule entirely while still calling the inherited `buildReport()` etc. **`export()` and `exportPdf()` are not overridden at all — inherited unchanged**, since their `(Request, string $slug, string $type)` signature and file-download return type already work as-is for the API route.

| Method | Path | Controller method | Permission |
|---|---|---|---|
| GET | `/api/v1/{slug}/reports/ot` | `apiIndex()` | `reports.view` |
| GET | `/api/v1/{slug}/reports/ot/{type}?from=&to=` | `apiShow()` | `reports.view` |
| GET | `/api/v1/{slug}/reports/ot/{type}/export` | `export()` (inherited, unchanged) | `reports.export` |
| GET | `/api/v1/{slug}/reports/ot/{type}/export-pdf` | `exportPdf()` (inherited, unchanged) | `reports.export` |
| GET | `/api/v1/{slug}/dashboard/ot-summary?from=&to=` | `dashboardSummary()` **(new)** | `reports.view` |

`{type}` is one of the 16 report keys in `OtReportController::REPORTS` (appointments, registration, doctor-consultation, counselling, billing, ot, discharge, hospital-report, surgery-wise, doctor-wise, lens-usage, complications, daily-collection, monthly-revenue, package-wise, pending-payments).

### FR-OT-40 dashboard widget — one metric deliberately omitted, not fabricated
Built `total_patients`, `surgeries_completed`, `avg_waiting_time_minutes` (ward attend → operated), `patient_turnaround_time_minutes` (ward attend → discharge), `revenue_trend` (daily payment sums), `lens_consumption` (implants in range) — all backed by real timestamp/aggregate columns that already exist. **`ot_utilization_pct` from the plan doc was left out** — it needs an OT room/slot capacity figure (rooms × operating hours) that doesn't exist anywhere in the current schema; returning a fabricated percentage would be worse than not returning one. Flag to the user/backend team if this is actually wanted — it needs a new capacity-config concept added to the schema, not just a query, out of scope for an API-mirroring round.

### Request/response contracts (for the Flutter app)

**`GET .../reports/ot`** → `{ success, data: { "Operational Registers": [{group,label,key}, ...], "Clinical Reports": [...], "Financial Reports": [...] } }`

**`GET .../reports/ot/{type}?from=YYYY-MM-DD&to=YYYY-MM-DD`** (defaults: from = start of this month, to = today) → `{ success, data: { type, label, headings: [...], rows: [[...], ...], from, to } }`

**`GET .../reports/ot/{type}/export`** and **`/export-pdf`** → binary file download (xlsx / pdf), same as Phase 6's print endpoints — not JSON.

**`GET .../dashboard/ot-summary?from=&to=`** → `{ success, data: { from, to, total_patients, surgeries_completed, avg_waiting_time_minutes, patient_turnaround_time_minutes, revenue_trend: [{date,total}], lens_consumption } }` — the two `*_minutes` fields are `null` when there's no qualifying data in range (not `0`, to distinguish "no data" from "zero minutes").

### Verification performed
- [x] `php -l` clean on both the new controller and the modified parent
- [x] `route:list` — 5 new routes, no collisions; confirmed `export`/`export-pdf` correctly resolve to the **inherited** parent methods
- [x] Caught and fixed the return-type covariance fatal error before it could reach a live request
- [x] **Functionally invoked both new methods end-to-end via tinker** (not just query sanity-checks like every prior phase) — `apiShow($req, $slug, 'daily-collection')` → HTTP 200, `dashboardSummary($req)` → HTTP 200 with correctly-shaped JSON (`{"success":true,"data":{"from":"2026-08-01","to":"2026-08-04","total_patients":0,...}}`) — this is the strongest verification any phase in this round got, since the report engine covers 14 different query paths through one shared method
- [ ] Live HTTP smoke test through actual routes (vs. direct controller invocation via tinker) — pending, same caveat as all prior phases
- [ ] Only tested with `daily-collection`; the other 13 report types share the same `buildReport()` dispatcher but weren't each individually invoked

### For the mobile/tablet app team
Build this screen last (matches the plan doc's own sequencing note) — it visualizes data every other phase produces, so it's the natural integration-test surface once real OT data exists. `ot_utilization_pct` is not available — don't build a UI element for it until backend adds OT capacity config.

---

## Round 3 — All 8 Phases Complete (2026-08-04)

Summary: Phase 0 (web sync) + 8 mobile API phases, **~64 new API endpoints** across 8 new controllers (`OtCounsellorApiController`, `OtAppointmentApiController`, `OtWardApiController`, `OtAssistantApiController`, `OtAccountantApiController`, `OtDischargeApiController`, `OtInventoryApiController`, `OtReportApiController`), 2 small additive edits to existing files (`MedicineGroupApiController::index()` scope filter, `OtReportController` visibility changes), 1 new permission + migration (`master.ot_inventory`). Zero changes to any web-facing blade view, `Hospital\*` controller logic, or existing mobile API behavior — purely additive, matching the scope discipline carried over from Round 1/2.

---

## Round 3.5 — Full App-Wide Parity Audit + Gap-Fill (2026-08-04)

**Trigger:** user's stated goal is complete web/mobile parity, not just the OT Workflow Upgrade. Ran a systematic audit — compared all 66 distinct web controllers against all 40 (pre-audit) API controllers via `php artisan route:list --json`, module by module.

### Findings
- **High priority, workflow-breaking:** `OtBookingController::recommendSurgery()` — the Doctor's "Recommend Surgery" action from the exam screen, which *creates* the `OtBooking` row. Without it, nothing built in Phases 1-8 was reachable from mobile (no booking to counsel/ward/operate on). `OtReceptionistController::dashboard()` (OT stats: today's count, pending counselling, ready-for-surgery) — related, minor.
- **Medium, master-data gaps:**
  - `OtLensOptionController` / `OtTypeController` (broad OT category, e.g. Cataract/Squint) — turned out to be **partially** reachable already via the generic `masters/detail/{type}` mechanism (`MasterApiController::detailMap()` already had `'lens-options'` and `'ot-types'` entries), but gated by `permission:master.eye_exam` — the **wrong permission**. Web gates both under `role:admin` (the same OT-admin group as everything in Phase 7). This is a real access-control mismatch: any doctor with `master.eye_exam` could edit OT master data via that route, while a dedicated OT admin without `master.eye_exam` would be wrongly blocked. Fixed with dedicated, correctly-permissioned methods rather than touching the generic route (which stays as-is, zero risk to whatever already depends on it).
  - `MedicineInstructionController` — completely missing, zero API coverage, fixed.
- **Lower priority (not built this round, flagged only):** 6 new-with-the-OT-upgrade dashboard drill-down widgets (`AdminCollectionController`, `AdminDashboardPatientsController`, `OtAppointmentListController`, `DoctorOtListController`, `AssistantOtListController`, `ReceptionistTotalPatientsController`) and `ReportController::showChannel()` (channel-wise patient report). User chose High+Medium only this round.
- **Confirmed NOT gaps:** `DoctorProfileController` — core fields (name/email/password/doctor registration_no/experience_years) already covered by `Api\ProfileApiController`; only signature/photo upload is web-only, and that was already an explicit, pre-existing, documented decision in that file's own code comment, not something this audit needs to fix.
- **Confirmed genuinely out of mobile scope (not flagged as gaps):** `PasswordResetController` (web email-link flow — mobile would likely want OTP instead, a design decision not an API-parity gap), `SetupWizardController` (one-time web onboarding), `Platform\{Landing,Register,UnifiedLogin,Webhook}Controller` (public/pre-auth pages), `TimezoneMasterController` (superadmin-only platform master).

### What was built

| New/changed file | What |
|---|---|
| `app/Http/Controllers/Api/OtBookingApiController.php` (new) | `recommendSurgery()`, `receptionistDashboard()` |
| `app/Http/Controllers/Api/MasterApiController.php` (extended) | `otLensOptionIndex/Store/Update/Destroy`, `otTypeIndex/Store/Update/Destroy` |
| `app/Http/Controllers/Api/MedicineMasterApiController.php` (extended) | `instructions()`, `storeInstruction()`, `updateInstruction()`, `destroyInstruction()` |
| `routes/api.php` | +14 routes for the above |

| Method | Path | Permission |
|---|---|---|
| POST | `/api/v1/{slug}/ot/recommend-surgery/{patientId}` | `ot.surgery.recommend` |
| GET | `/api/v1/{slug}/dashboard/ot-receptionist` | `ot.patient.list` |
| GET/POST | `/api/v1/{slug}/masters/ot-lens-options` | `master.ot_inventory` |
| PUT/DELETE | `/api/v1/{slug}/masters/ot-lens-options/{id}` | `master.ot_inventory` |
| GET/POST | `/api/v1/{slug}/masters/ot-type` | `master.ot_types` |
| PUT/DELETE | `/api/v1/{slug}/masters/ot-type/{id}` | `master.ot_types` |
| GET/POST | `/api/v1/{slug}/medicine-instructions` | `master.medicines` |
| PUT/DELETE | `/api/v1/{slug}/medicine-instructions/{id}` | `master.medicines` |

**`recommendSurgery` request/response** (for mobile team — this is the most important new contract in this round): body `eye (required: RE|LE|Both), surgery_date (required, >= today), slot_id (required, must exist), ot_surgery_type_id (required, must exist), diagnosis_hint (optional)`. `201 { success, message, data: { booking_id, ot_status } }`. `422` if patient already has an active non-updatable booking. Calling this twice on a still-`surgery_recommended`/`booked` patient **updates** the existing booking rather than creating a duplicate (matches web exactly).

### Verification performed
- [x] `php -l` clean on all 3 files
- [x] `route:list` — 14 new routes, no collisions (271 total API routes now, up from 257)
- [x] Tinker: sanity queries for `ot_surgery_types`, `ot_types`, `ot_lens_options`, `medicine_instructions` against real tenant — clean
- [ ] Live HTTP smoke test — pending, same caveat as every phase in this round

---

---

## Round 3.6 — Lower-Priority Gap-Fill (2026-08-04)

User asked to close out the remaining "lower priority" items from the Round 3.5 audit too, for full parity.

### What was built

| New/changed file | What |
|---|---|
| `app/Http/Controllers/Api/DashboardDrillDownApiController.php` (new) | 6 dashboard drill-down widgets, all one file (mirrors Phase 7's approach of grouping several small controllers together) |
| `app/Http/Controllers/Api/ReportsApiController.php` (extended) | `channelCounts()`, `showChannel()` + a `buildQuery()` fix (see below) |

| Method | Path | Mirrors (web) | Permission |
|---|---|---|---|
| `adminCollectionIndex/Show` | `/dashboard/collection`, `/dashboard/collection/{reception}` | `AdminCollectionController` | `opd.patient.view\|reports.view\|opd.reports.view` |
| `adminPatientsIndex/Export` | `/dashboard/admin-patients(/export)` | `AdminDashboardPatientsController` | `opd.patient.view\|reports.view` (+`reports.export` for export) |
| `otAppointmentsIndex` | `/dashboard/ot-appointments` | `OtAppointmentListController` | `ot.appointment.view\|ot.patient.list` |
| `doctorOtIndex` | `/dashboard/doctor-ot` | `DoctorOtListController` | `opd.exam.secondary\|ot.patient.list\|ot.surgery.recommend` |
| `assistantOtIndex` | `/dashboard/assistant-ot` | `AssistantOtListController` | `ot.patient.list\|ot.surgery.record\|ot.lens.record\|ot.surgery.ready` |
| `receptionistTotalPatients` | `/receptionist/total-patients` | `ReceptionistTotalPatientsController` | `opd.patient.view` |
| `channelCounts` | `/reports/channel-counts` | `ReportController::index()`'s card counts | `reports.view` |
| `showChannel` | `/reports/channel/{channel}` | `ReportController::showChannel()` | `reports.view` |

All permissions copied verbatim from `routes/hospital.php`'s existing OR-groups for each route — no new permissions needed this round, unlike Phase 7.

### One real behavior fix, flagged clearly (not silent)
`ReportsApiController::buildQuery()` (pre-existing, from Round 1/2) had **drifted out of sync** with the web `ReportController::buildQuery()` after the OT Workflow Upgrade shipped: the web version was updated to add an `ot_appointment` channel branch and to exclude `otAppointmentSource`-linked patients from the `walkin` bucket; the API's hand-duplicated copy was never updated to match. Fixed by mirroring the web version exactly. **Behavior change for existing callers:** any existing mobile client calling `GET .../reports?type=walkin` will now get a **narrower** result set — patients that originated from an OT Appointment and were typed `walkin` are now excluded, matching what the web report has already been showing. This is a correctness fix (mobile was silently disagreeing with web on what counts as "walk-in"), not a new feature, but it does change existing endpoint output — flagging explicitly rather than treating it as purely additive like everything else in this round.

### Verification performed
- [x] `php -l` clean on both files
- [x] `route:list` — 10 new routes, no collisions (**281 total API routes** now)
- [x] Confirmed `Patient::otAppointmentSource()` relation and `HospitalUser::scopeActive()` exist (both referenced, neither newly added by this round)
- [x] **Functionally invoked 6 of 8 new methods end-to-end via tinker** — all returned HTTP 200 with correctly-shaped JSON (`adminCollectionIndex`, `otAppointmentsIndex`, `doctorOtIndex`, `receptionistTotalPatients`, `channelCounts`, `showChannel('walkin')`)
- [x] **Edge cases tested**: `assistantOtIndex` with no authenticated user → clean `401` (not a crash); `adminCollectionShow` with a non-existent reception ID → clean `404`
- [ ] Live HTTP smoke test through actual routes — pending, same caveat as the rest of this round

### For the mobile/tablet app team
If any mobile client already built against `GET .../reports?type=walkin`, re-check its expected counts after this update — see the behavior-fix note above.

---

**Not yet done (needs the user, not something to do silently):**
1. Live HTTP smoke test with a real Sanctum token against a running server (every phase's tinker/query checks confirm no SQL/syntax errors, but no phase has been hit over real HTTP yet).
2. Web regression click-through (booking → counsellor → ward → assistant → billing → discharge screens) — recommended before this branch merges anywhere.
3. `php artisan migrate:fresh --seed` on a throwaway DB to confirm the full seeder chain (including the new `master.ot_inventory` permission) still runs clean start-to-finish.
4. Everything currently lives on branch `feature/ot-mobile-api` (off `jeel`) — not merged into `jeel`, not pushed to `origin`, per the Round 1/2 rollback discipline.

**Full app-wide parity status as of Round 3.6:** every web controller now has API coverage except the deliberately-excluded ones (§Round 3.5: `PasswordResetController`, `SetupWizardController`, public `Platform\*` pages, `TimezoneMasterController`). 281 total API routes.
