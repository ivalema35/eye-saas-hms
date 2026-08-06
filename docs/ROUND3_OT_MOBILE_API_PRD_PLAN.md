# Round 3 — OT Workflow Upgrade: Web Sync + Mobile API Build

**Status:** Planning complete (deep repo study done — see §2 for verified facts). Execution not started. Nothing in this document has been built yet.

**Purpose:** This is a combined PRD + execution plan, phase/module-wise, for two things:
1. Bringing the web developer's latest pushed changes (`origin/main`, currently sitting in `J:\laragon\www\eye_care_new_clone_3\eye-saas-hms`) into the working project (`J:\laragon\www\eye_care_new_clone\eye-saas-hms`, branch `jeel`) without breaking anything.
2. Building the mobile/tablet REST API layer for the new OT (Operation Theatre) workflow that shipped in that web update — currently **zero** mobile API coverage exists for it.

Each phase below is self-contained and independently trackable (status column), same discipline as `docs/MOBILE_API_MERGE_PLAN.md` (Round 1) and `docs/ROUND2_MERGE_PLAN.md` (Round 2) — read those first if context is needed on how the mobile API layer has evolved so far.

**Scope discipline (non-negotiable, carried over from Round 1 & 2):** No changes to web-facing blade views, `Hospital\*`/`SuperAdmin\*`/`Platform\*` controllers, or feature behavior. This work is confined to `app/Http/Controllers/Api/**`, `routes/api.php`, and (only if strictly required) a new permission-gate helper. The web developer's code and mental model of it must stay untouched.

---

## 1. Background — this is Round 3

- **Round 1** (`docs/MOBILE_API_MERGE_PLAN.md`): merged initial mobile API layer into web code as of PR #79. Result: branch `merge/mobile-api` (merge commit `b20a890`).
- **Round 2** (`docs/ROUND2_MERGE_PLAN.md`): synced web dev's PR #80/#81 (Medicine Master feature + dashboard rework) into `merge/mobile-api`.
- Since then, `merge/mobile-api` → merged into `jeel`, and `jeel` picked up ~36 more commits (more PRs from `tulsi`/`dev`/`jeel` branches), landing at `fc21125d` ("role base login and permission base data") — **which itself got merged into `origin/main` via PR #97.**
- Meanwhile the web developer shipped a large new feature directly to `origin/main`: the **OT Workflow Upgrade** (9 commits after `fc21125d`, ending at `ddc3e2fc`). This is what `eye_care_new_clone_3` (fresh clone) now has and `jeel` doesn't.

**Round 3 = sync that OT Workflow Upgrade into `jeel`, then build its mobile API.**

---

## 2. Current Git State (verified 2026-08-04)

| Ref | Location | Points at | Notes |
|---|---|---|---|
| `jeel` (current branch) | `eye_care_new_clone` | `fc21125d` | Working tree clean. **0 commits unique to jeel** — it is a pure ancestor of `origin/main`. |
| `origin/main` | fetched into `eye_care_new_clone` | `ddc3e2fc` | Same commit as `eye_care_new_clone_3`'s `main` (fresh clone, clean tree). |
| Merge relationship | — | — | `git rev-list --left-right --count jeel...origin/main` → `0  9`. **Pure fast-forward, not a 3-way merge.** Unlike Round 1/2, no conflicts are expected because `jeel` contributed nothing after `fc21125d` — all its work is already inside `origin/main`. |

**The 9 new commits touch 225 files, +23,315/−3,012 lines.** Verified: **zero** of those files are `routes/api.php` or anything under `app/Http/Controllers/Api/**`. The entire OT Workflow Upgrade is web-only today.

Reference docs already shipped with the web update (read in full during planning):
- `docs/OT_WORKFLOW_UPGRADE_PRD.md` — backend PRD (what was built, phase by phase). **Already implemented** — this is not a future plan.
- `docs/OT_WORKFLOW_UPGRADE_MOBILE_API_PRD.md` — a mobile API spec (`FR-OT-18` → `FR-OT-40`) written *for* this update, but **not yet built**. This plan verifies/corrects and operationalizes that spec (see §12 for corrections found).
- `docs/tulsi.md` — a **separate, not-yet-built** proposal to consolidate OT's 8 roles into 7. Explicitly says "nothing in this document has been built yet." Do not confuse with the OT Workflow Upgrade itself, which *is* built. Not in scope for this round.

---

## 3. Executive Summary — What's Done, What's Needed

| Module | Web (blade/controller) | Mobile API today | Mobile API needed this round |
|---|---|---|---|
| OT Appointments | ✅ Built (`OtAppointmentController`) | ❌ None | ✅ Phase 2 |
| OT Counsellor (counselling form + consent) | ✅ Built (`OtCounsellorController`) | ❌ None | ✅ Phase 1 |
| Ward (vitals + eye-drops) | ✅ Built (`OtWardController`) | ❌ Stub only (`FR-OT-08/09` never implemented) | ✅ Phase 3 |
| Pre-surgery verification + extended surgery record | ✅ Built (`OtAssistantController`) | 🟡 Old `OtApiController` is a stub (`bookings/book/updateStatus`, all 501) | ✅ Phase 4 |
| Lens details (extended fields, stock auto-decrement) | ✅ Built (`OtAssistantController::storeLens`, `LensInventoryService`) | ❌ None | ✅ Phase 4 |
| Payment status / verification | ✅ Built (`OtAccountantController`) | ❌ None | ✅ Phase 5 |
| Discharge documents (7 print docs + bundle) | ✅ Built (`OtDischargeController`, `OtInvoiceController`) | ❌ None | ✅ Phase 6 |
| Lens inventory master (stock CRUD) | ✅ Built (`LensInventoryController`, gated `role:admin`) | ❌ None | ✅ Phase 7 |
| OT Reports & dashboard widgets | ✅ Built (`OtReportController`) | ❌ None | ✅ Phase 8 |

---

## 4. Phase 0 — Sync `jeel` with `origin/main`

**Goal:** bring the 9 new web commits into `jeel`, zero risk to existing mobile API.

| Step | Action | Status |
|---|---|---|
| 0.1 | Re-verify `git status` clean + re-fetch `origin` (state may have moved since this doc was written) | ☐ Not started |
| 0.2 | `git merge --ff-only origin/main` on `jeel` (fast-forward; if this fails, state has diverged from §2 and this plan needs re-verification before continuing — **do not** force a 3-way merge blindly) | ☐ Not started |
| 0.3 | `composer install` (check `composer.json`/lock diff first — none expected, no new packages in this update per the PRD) | ☐ Not started |
| 0.4 | `php artisan migrate:status` — confirm the ~25 new OT migrations show as pending, nothing else broken | ☐ Not started |
| 0.5 | `php artisan config:clear && php artisan route:clear && php artisan view:clear` | ☐ Not started |
| 0.6 | `php artisan route:list --path=api` — confirm all existing mobile routes still register, no collisions introduced | ☐ Not started |
| 0.7 | Web regression smoke pass: dashboard, patients, exam, existing OT screens (booking/accountant/old lens flow) load without error | ☐ Not started |
| 0.8 | **Do not run migrations against real data yet** — confirm with user before `php artisan migrate` on any DB that isn't a throwaway/test copy | ☐ Not started |

---

## 5. Phase 1 — Counsellor Module API

Web reference: `app/Http/Controllers/Hospital/OT/OtCounsellorController.php` (`dashboard`, `show`, `lookupPackage`, `storeCounselling`, `storeConsent`, `sendToBilling`).

| # | Endpoint | Mirrors web method | Permission (**verified in code**, not the PRD doc) | Status |
|---|---|---|---|---|
| 1.1 | `GET /api/v1/{slug}/ot/counsellor/bookings` | `dashboard()` | `ot.counselling.fill` | ☐ Not started |
| 1.2 | `GET /api/v1/{slug}/ot/counsellor/package-lookup` | `lookupPackage()` | `ot.counselling.fill` | ☐ Not started |
| 1.3 | `GET /api/v1/{slug}/ot/bookings/{id}/counselling` | `show()` | `ot.counselling.fill` | ☐ Not started |
| 1.4 | `PUT/POST /api/v1/{slug}/ot/bookings/{id}/counselling` | `storeCounselling()` | `ot.counselling.fill` | ☐ Not started |
| 1.5 | `POST /api/v1/{slug}/ot/bookings/{id}/consent` | `storeConsent()` | `ot.consent.capture` | ☐ Not started |
| 1.6 | `POST /api/v1/{slug}/ot/bookings/{id}/send-to-billing` | `sendToBilling()` | `ot.counselling.fill` | ☐ Not started |

**Note (mobile-specific):** consent capture needs signature-pad (base64 PNG) handling — flag to mobile dev team as new UI component, not a plain form field (carried over from the shipped PRD doc, still accurate).

---

## 6. Phase 2 — Appointment Module API

Web reference: `OtAppointmentController` (`index`, `create`/`store`, `edit`/`update`, `confirm`, `cancel`, `search`, `slotAppointments`).

| # | Endpoint | Mirrors web method | Permission | Status |
|---|---|---|---|---|
| 2.1 | `GET /api/v1/{slug}/ot/appointments?date=&status=&type=` | `index()` | `ot.appointment.view` | ☐ Not started |
| 2.2 | `POST /api/v1/{slug}/ot/appointments` | `store()` | `ot.appointment.create` | ☐ Not started |
| 2.3 | `PUT /api/v1/{slug}/ot/appointments/{id}` | `update()` | `ot.appointment.edit` | ☐ Not started |
| 2.4 | `POST /api/v1/{slug}/ot/appointments/{id}/confirm` | `confirm()` | `ot.appointment.edit` | ☐ Not started |
| 2.5 | `POST /api/v1/{slug}/ot/appointments/{id}/cancel` | `cancel()` | `ot.appointment.edit` | ☐ Not started |
| 2.6 | `GET /api/v1/{slug}/ot/appointments/search?q=` | `search()` | `ot.appointment.view` OR `opd.patient.register` (matches web's OR-gate exactly) | ☐ Not started |
| 2.7 | `GET /api/v1/{slug}/ot/appointments/slot-appointments` | `slotAppointments()` | `ot.appointment.view` OR `ot.appointment.create` | ☐ Not started |

**Note:** SMS/WhatsApp confirmation on create is a backend async job dependency (per `OT_WORKFLOW_UPGRADE_PRD.md` §9.4) — mobile just fires the create call, no client-side messaging logic needed. Confirm current status of that job before assuming it fires (check `app/Services` for a `NotificationService` — not confirmed present as of this doc; verify at implementation time).

---

## 7. Phase 3 — Ward Module API

Web reference: `OtWardController` (`show`, `storeVitals`, `addEyeDrop`).

| # | Endpoint | Mirrors web method | Permission (**verified**) | Status |
|---|---|---|---|---|
| 3.1 | `GET /api/v1/{slug}/ot/bookings/{id}/vitals` (latest + history) | `show()` (vitals portion) | `ot.ward.entry` | ☐ Not started |
| 3.2 | `POST /api/v1/{slug}/ot/bookings/{id}/vitals` | `storeVitals()` | `ot.preop.entry` — **not `ot.ward.entry`**, PRD doc got this wrong | ☐ Not started |
| 3.3 | `GET /api/v1/{slug}/ot/bookings/{id}/eye-drops` (full dose log) | `show()` (eye-drop portion) | `ot.ward.entry` | ☐ Not started |
| 3.4 | `POST /api/v1/{slug}/ot/bookings/{id}/eye-drops` | `addEyeDrop()` | `ot.dilation.track` — **not `ot.ward.entry`**, PRD doc got this wrong | ☐ Not started |
| 3.5 | `GET /api/v1/{slug}/ot/bookings/{id}/verification-header` (UHID/name/surgery type/eye convenience endpoint) | new, composed from `OtBooking` + `Patient` | `ot.ward.entry` | ☐ Not started |

**Known pre-existing gap (carried from `docs/tulsi.md`, informational only):** `ot_status` never reaches `STATUS_DILATED` anywhere in the current web code — `addEyeDrop()` doesn't change status. Not this round's problem to fix, just don't assume the API needs to expose a status transition here that the web side doesn't actually perform.

---

## 8. Phase 4 — OT/Surgery + Lens API

Web reference: `OtAssistantController` (`dashboard`, `createSurgery`, `storeSurgery`, `editLens`, `storeLens`), `LensInventoryService`.

| # | Endpoint | Mirrors web method | Permission (**verified**) | Status |
|---|---|---|---|---|
| 4.1 | `GET /api/v1/{slug}/ot/assistant/bookings` (ready-for-surgery + ready-for-lens queues) | `dashboard()` | `ot.lens.record`\|`ot.lens.implant`\|`ot.surgery.ready`\|`ot.surgery.record`\|`ot.patient.list` (OR-group, matches web) | ☐ Not started |
| 4.2 | `POST /api/v1/{slug}/ot/bookings/{id}/verify` (4-item pre-surgery checklist) | part of `storeSurgery()` validation | `ot.surgery.record` | ☐ Not started |
| 4.3 | `POST /api/v1/{slug}/ot/bookings/{id}/surgery` | `storeSurgery()` — body: `assistant_id, ot_room, start_time, end_time, blood_loss, medicine_group_id, medicines[]` | `ot.surgery.record` | ☐ Not started |
| 4.4 | `GET /api/v1/{slug}/ot/medicine-groups?scope=ot` | existing `MedicineGroupApiController`, extend with `usage_scope` filter | `ot.surgery.record` | ☐ Not started |
| 4.5 | `GET\|POST /api/v1/{slug}/ot/bookings/{id}/lens` | `editLens()` / `storeLens()` — extended fields: `manufacturer, lens_name, lens_type, lens_power, axis, batch_number, serial_number, expiry_date, mrp, implant_status` | `ot.lens.record`, `ot.lens.implant` | ☐ Not started |
| 4.6 | On `implant_status = true` | server auto-decrements `lens_inventory.available_stock` via `LensInventoryService` — reuse the same service, do not reimplement decrement logic in the API controller | n/a | ☐ Not started |

**Breaking-change flag (carried from shipped PRD doc, still valid):** if any mobile client already implemented the *old* `FR-OT-11` surgery-record contract against the stub `OtApiController`, this is a payload-shape change. Given `OtApiController` is currently a 501-stub, this is very unlikely to be a real concern — confirm with the Flutter team before implementation, not assume.

---

## 9. Phase 5 — Billing / Payment Verification API

Web reference: `OtAccountantController` (payment methods), `OtBooking` status constants.

| # | Endpoint | Notes | Permission (**verified**) | Status |
|---|---|---|---|---|
| 5.1 | `GET /api/v1/{slug}/ot/bookings/{id}/payment-status` | computed Paid/Partially Paid/Pending + payment history | `ot.invoice.view` | ☐ Not started |
| 5.2 | Counsellor payment-verification endpoint | ⚠️ **Open question, verify before building:** the shipped `OtCounsellorController` has **no `verifyPayment()` method** in the current codebase (checked — only `dashboard/show/lookupPackage/storeCounselling/storeConsent/sendToBilling` exist). `docs/tulsi.md` §3 describes this as a *planned* auto-advance-on-full-payment change to `OtAccountantController::storePayment()`, not yet built. **Do not build `FR-OT-34` (`verify-payment`) as a mobile endpoint until the web-side equivalent actually exists** — building it now would create an API with no real status transition behind it. | — | ☐ Blocked on web-side (tulsi.md Phase 3), re-check at implementation time |

---

## 10. Phase 6 — Discharge Document API

Web reference: `OtDischargeController` (`print`, `certificatePrint`, `medicineSlipPrint`, `prescriptionPrint`, `lensSlipPrint`, `followupSlipPrint`, `printAllBundle`), `OtInvoiceController` (`print`, `summaryBillPrint`).

| # | Endpoint | Mirrors web method | Permission (**verified**) | Status |
|---|---|---|---|---|
| 6.1 | `GET /api/v1/{slug}/ot/bookings/{id}/print/invoice` | `OtInvoiceController::print()` | `ot.billing.manage` | ☐ Not started |
| 6.2 | `GET /api/v1/{slug}/ot/bookings/{id}/print/discharge` | `OtDischargeController::print()` | `ot.billing.manage` | ☐ Not started |
| 6.3 | `GET /api/v1/{slug}/ot/bookings/{id}/print/summary-bill` | `summaryBillPrint()` | `ot.billing.manage` | ☐ Not started |
| 6.4 | `GET /api/v1/{slug}/ot/bookings/{id}/print/certificate` | `certificatePrint()` | `ot.billing.manage` | ☐ Not started |
| 6.5 | `GET /api/v1/{slug}/ot/bookings/{id}/print/medicine-slip` | `medicineSlipPrint()` | `ot.billing.manage` | ☐ Not started |
| 6.6 | `GET /api/v1/{slug}/ot/bookings/{id}/print/prescription` | `prescriptionPrint()` | `ot.billing.manage` | ☐ Not started |
| 6.7 | `GET /api/v1/{slug}/ot/bookings/{id}/print/lens-slip` | `lensSlipPrint()` | `ot.billing.manage` | ☐ Not started |
| 6.8 | `GET /api/v1/{slug}/ot/bookings/{id}/print/followup-slip` | `followupSlipPrint()` | `ot.billing.manage` | ☐ Not started |
| 6.9 | `GET /api/v1/{slug}/ot/bookings/{id}/print/discharge-bundle` | `printAllBundle()` | `ot.billing.manage` | ☐ Not started |

All 9 return a PDF blob — mobile opens in an in-app PDF viewer or triggers OS share sheet (same pattern as any other existing print endpoint in the app, no new client pattern needed here unlike Phase 1's signature pad).

**Correction vs. shipped PRD doc:** the PRD (`FR-OT-33`) implies a separate `ot.invoice.view` gate per-endpoint; actual web code gates **all** of billing/discharge printing under one permission, `ot.billing.manage`. Match the API to the real single-permission gate, not the PRD's finer-grained (non-existent) split.

---

## 11. Phase 7 — Lens Inventory Master API

Web reference: `LensInventoryController` (`index`, `store`, `update`, `destroy`), `OtLensPowerController`, `OtPackageMasterController`.

| # | Endpoint | Mirrors web method | Permission | Status |
|---|---|---|---|---|
| 7.1 | `GET /api/v1/{slug}/masters/ot/lens-inventory` | `index()` | ⚠️ see gotcha below | ☐ Not started |
| 7.2 | `POST /api/v1/{slug}/masters/ot/lens-inventory` | `store()` | ⚠️ see gotcha below | ☐ Not started |
| 7.3 | `PUT /api/v1/{slug}/masters/ot/lens-inventory/{id}` | `update()` | ⚠️ see gotcha below | ☐ Not started |
| 7.4 | `DELETE /api/v1/{slug}/masters/ot/lens-inventory/{id}` | `destroy()` | ⚠️ see gotcha below | ☐ Not started |
| 7.5 | `GET /api/v1/{slug}/ot/lens-inventory/search?q=&type=&power=` | new, typeahead for Phase 1/4 pickers, in-stock by default | same gate as 7.1 | ☐ Not started |

**Important technical gotcha found during this study (not in the shipped PRD doc at all):** on the web, this whole master group (`packages`, `lens-powers`, `lens-inventory`, plus `lens-options`/`slots`/`types`/`surgery-types`/`charge-heads`) is gated by **`middleware('role:admin')`**, not a `permission:` key (`routes/hospital.php` ~line 348). `CheckRole` middleware (`app/Http/Middleware/CheckRole.php`) hardcodes `auth('hospital_user')` — the **session** guard. It will not authenticate a Sanctum bearer-token (`api` guard) mobile request at all; reusing it as-is on API routes will 403 every mobile call.
**Decision needed before Phase 7 starts:** either (a) introduce a proper `permission:` key for lens-inventory/lens-power/package management and grant it to the admin role in `PermissionsSeeder`/`SystemRolesSeeder` (additive, matches how the rest of the API is gated, and matches how Round 1 handled the analogous `CheckPermission` Sanctum-fallback problem), or (b) write a Sanctum-aware guard check inline. **(a) is strongly recommended** — it's the same pattern already proven safe in Round 1 (§2.1 of `MOBILE_API_MERGE_PLAN.md`: additive-only, activates only when there's no session user, never breaks web logins).

---

## 12. Phase 8 — Reports & Dashboard API

Web reference: `OtReportController` (`index`, `show`, `export`, `exportPdf`, `patientPrescriptionPdf`), route prefix `reports/ot`, permissions `reports.view` / `reports.export`.

| # | Endpoint | Mirrors web method | Permission (**verified**) | Status |
|---|---|---|---|---|
| 8.1 | `GET /api/v1/{slug}/reports/ot` (list report types) | `index()` | `reports.view` | ☐ Not started |
| 8.2 | `GET /api/v1/{slug}/reports/ot/{type}` | `show()` | `reports.view` | ☐ Not started |
| 8.3 | `GET /api/v1/{slug}/reports/ot/{type}/export?format=excel` | `export()` | `reports.export` | ☐ Not started |
| 8.4 | `GET /api/v1/{slug}/reports/ot/{type}/export-pdf` | `exportPdf()` | `reports.export` | ☐ Not started |
| 8.5 | `GET /api/v1/{slug}/dashboard/ot-summary` (widgets: total_patients, surgeries_completed, ot_utilization_pct, avg_waiting_time_minutes, revenue_trend, lens_consumption, patient_turnaround_time_minutes) | new, composed — check whether an equivalent web dashboard widget query already exists to reuse (`Hospital/Dashboard/*` controllers changed in this update — check `AdminCollectionController`, `AdminDashboardPatientsController` first before writing new aggregation queries from scratch) | `reports.view` (Hospital Admin role) | ☐ Not started |

**Build this phase last** — it consumes data that Phases 1–7 produce; building it first means testing against incomplete data.

---

## 13. Corrections Found vs. the Shipped `OT_WORKFLOW_UPGRADE_MOBILE_API_PRD.md`

The web-shipped PRD doc is a good starting skeleton but was written speculatively, not against the final permission keys. Verified mismatches (this document's tables above already use the **correct** values):

| PRD said | Actual code | Where |
|---|---|---|
| `ot.counselling.view` (`FR-OT-18`) | `ot.counselling.fill` | `routes/hospital.php` counsellor group |
| `ot.counselling.record` (`FR-OT-19`, `FR-OT-21`) | `ot.counselling.fill` | same |
| Vitals gated by `ot.ward.entry` (`FR-OT-26`) | `GET` is `ot.ward.entry`, but `POST storeVitals` is `ot.preop.entry` | `routes/hospital.php` ward group |
| Eye-drops gated by `ot.dilation.track` only implied for both read/write (`FR-OT-27`) | `POST addEyeDrop` is `ot.dilation.track`; the `show()`/read side is `ot.ward.entry` | same |
| `ot.inventory.manage` (`FR-OT-37`) | **Does not exist.** Web gates lens-inventory masters via `role:admin` (session-guard only, incompatible with Sanctum as-is) | `routes/hospital.php` masters/ot group — see §11 |
| Per-endpoint `ot.invoice.view` implied for all discharge prints (`FR-OT-33`/`35`/`36`) | Single `ot.billing.manage` gates the entire billing/discharge print group | `routes/hospital.php` billing group |
| `FR-OT-34` payment-verification endpoint | No corresponding web method exists yet (`OtCounsellorController` has no `verifyPayment()`); it's a `docs/tulsi.md`-Phase-3 *proposal*, not shipped | See §9 |

**Lesson for later rounds:** always verify permission keys and method existence against `routes/hospital.php` + the actual controller, not just the companion PRD doc — the PRD doc is a planning artifact, the routes file is ground truth.

---

## 14. Testing Checklist (must pass before any phase is considered done)

### 14.1 Per-phase mobile smoke test
- [ ] Auth token flow still works (Sanctum, guard `api`)
- [ ] Every new endpoint in that phase: happy path returns `{success:true,...}`, permission-denied path returns 403, validation failure returns 422
- [ ] No fatal errors in `storage/logs/laravel.log` after hitting each new route

### 14.2 Web regression (run once after Phase 0, spot-check again after each phase)
- [ ] All existing web OT screens (booking, accountant, counsellor, ward, assistant, billing, discharge, reports) load and function
- [ ] Existing mobile API endpoints (patients, exam, dashboard, masters, medicine, etc. — everything from Round 1/2) still respond correctly — this is the actual "don't break mobile" check, since Round 3 only *adds* new API surface
- [ ] `php artisan migrate:fresh --seed` on a throwaway/test DB completes clean, including the ~25 new OT migrations

---

## 15. Rollback Plan

Same pattern as Round 1/2: do all of this on a fresh branch (e.g. `feature/ot-mobile-api`) off `jeel`, after Phase 0's fast-forward. `main`/`jeel` stay untouched until reviewed and explicitly pushed. If anything goes wrong mid-phase, reset the feature branch — zero risk to the working branch.

---

## 16. Out of Scope for This Round

- `docs/tulsi.md`'s OT role re-architecture (8→7 roles) — not built on the web side yet, nothing to build a mobile API against.
- `FR-OT-34` payment-verification endpoint — blocked on the same web-side gap (see §9).
- Any SMS/WhatsApp client logic (§6 of this doc / `FR-OT-23`) — explicitly a backend-only concern per the shipped PRD.
- Any blade view, web controller logic, or non-OT feature change.

---

## 17. Security Note (carried over from Round 1 & 2, still unresolved)

`origin` remote URL in `eye_care_new_clone` has a GitHub Personal Access Token embedded in plaintext (`https://ghp_...@github.com/...`). Flagged twice before, still not rotated as of this doc. Recommend rotating/revoking on GitHub and switching to a credential manager — unrelated to this merge but worth doing regardless.

---

## 18. Master Progress Tracker

| Phase | Module | Endpoints | Status |
|---|---|---|---|
| 0 | Sync `jeel` ↔ `origin/main` | — | ☐ Not started |
| 1 | Counsellor | 6 | ☐ Not started |
| 2 | Appointments | 7 | ☐ Not started |
| 3 | Ward (vitals/eye-drops) | 5 | ☐ Not started |
| 4 | Surgery + Lens | 6 | ☐ Not started |
| 5 | Billing/Payment status | 1 (+1 blocked) | ☐ Not started |
| 6 | Discharge documents | 9 | ☐ Not started |
| 7 | Lens inventory master | 5 (+ permission-gate decision) | ☐ Not started |
| 8 | Reports & dashboard | 5 | ☐ Not started (build last) |

**How to use this table:** update the Status column as work happens (`Not started` → `In progress` → `Done` → `Tested`). Tell me which phase number to work on and I'll pick up from its section above with full context already loaded.
