# OT Role Re-Architecture — PRD (Phase-wise & Module-wise)

**Version:** 1.0 | **Source:** Client walkthrough (Tulsi) + full read of current codebase (`app/Http/Controllers/Hospital/OT/*`, `database/seeders/SystemRolesSeeder.php`, `resources/views/hospital/layouts/app.blade.php`).

**Purpose:** The client wants to consolidate the OT workflow's 8 roles down to 7 — merging three roles into Reception, splitting the current `ot_assistant` role into two (Ward Management + an expanded OT Assistant), retiring `ot_doctor` into OT Assistant, and carving out a new Discharge Counter role. This PRD breaks that into deployable phases, each scoped to one module, following the same format as `docs/OT_WORKFLOW_UPGRADE_PRD.md`.

> **RULE FOR AI AGENTS / DEVS:** Har phase apne aap me deployable hai. Phase ke andar "Action" column me diya gaya har item ek self-contained task hai — seeder/migration → permission → route → controller → view — is order me implement karo. Nothing in this document has been built yet.

---

## 0. Executive Summary — Role Mapping

| # | Role (target) | Status | Replaces / absorbs |
|---|---|---|---|
| 1 | Hospital Admin | ✅ Unchanged | — |
| 2 | Doctor | ✅ Unchanged | — |
| 3 | **Reception** | 🟡 **CHANGE (expand)** | `receptionist` + `ot_receptionist` + `ot_counsellor` |
| 4 | Accountant | 🟡 **CHANGE (behavior only)** | — |
| 5 | **Ward Management** | ❌ **ADD (new role)** | Ward half of `ot_assistant` |
| 6 | **OT Assistant** | 🟡 **CHANGE (expand)** | Lens half of `ot_assistant` + all of `ot_doctor` |
| 7 | **Discharge Counter** | ❌ **ADD (new role)** | Discharge/invoice slice of `ot_doctor` (+ possibly `accountant`) |

**Roles removed entirely:** `ot_receptionist`, `ot_counsellor`, `ot_doctor`.

Target patient flow, one line each (detail is in the phase tables below):

```
Reception (register + OT appointment) → Doctor (primary/secondary exam, recommend surgery)
→ Reception (counselling, same screen as today) → Accountant (payment)
→ [auto] Ward Management (vitals + eye-drop register) → [Ready for OT, auto] OT Assistant (surgery recording)
→ [auto] Discharge Counter (Discharge & Invoices)
```

---

## 1. Phase 1 — Role & Permission Foundation

**Why first:** every other phase depends on the new roles existing and the old ones being safely retired. This phase must land before any controller/view work in Phases 2–6, and it's the one phase that touches *existing* hospital staff logins, so it needs a data migration, not just a seeder change.

| Item | Action | Detail |
|---|---|---|
| `database/seeders/SystemRolesSeeder.php` | **CHANGE** | Remove role entries + `$defaultPermissions` blocks for `ot_receptionist`, `ot_counsellor`, `ot_doctor`. Expand `receptionist`'s permission array (see Phase 2). Expand `ot_assistant`'s permission array (see Phase 5). Add two new role entries: `ward_management` ("Ward Management") and `discharge_counter` ("Discharge Counter") — slugs TBD, confirm with client. |
| **Data migration for existing users** | **ADD** | New one-off migration/command: any `hospital_users` row with `role_id` pointing at `ot_receptionist` or `ot_counsellor` → reassign to `receptionist`; any row on `ot_doctor` → reassign to `ot_assistant`. Must run **before** the old role rows are deleted, or existing staff logins break with a dangling `role_id`. **Open question §8.7 — confirm client is OK with automatic bulk reassignment.** |
| `routes/hospital.php` | **CHANGE** | Every `middleware('permission:...')` gate that currently only matches a permission held exclusively by a removed role needs re-pointing — done piecemeal in Phases 2–6 below, but the removal of the 3 roles must land first so nothing silently 403s mid-migration. |
| Sidebar menu (`resources/views/hospital/layouts/app.blade.php`) | **CHANGE** | Menu label/permission changes are described per-module in Phases 2–6; tracked here as a single checkpoint since it's one file touched by every phase. |

---

## 2. Phase 2 — Reception Module (absorbs OT Appointment + OT Counselling)

**Why:** the client wants one desk (Reception) to handle registration, OT appointment booking, and counselling — today these are three separate roles/logins.

| Item | Action | Detail |
|---|---|---|
| Walk-in / Phone registration | ✅ **No change** | Existing `opd.patient.register`, `opd.patient.register_phone` — unchanged screens, unchanged controller (`PatientController`). |
| OT Appointment booking | **ADD to Reception** | Grant `ot.appointment.view`, `ot.appointment.create`, `ot.appointment.edit` to `receptionist`. No changes to `OtAppointmentController` or its views/fields at all — same screen, same fields, just a new role can reach it. |
| OT Counselling | **ADD to Reception** | Grant `ot.counselling.fill`, `ot.consent.capture` to `receptionist`. No changes to `OtCounsellorController` (dashboard/show/storeCounselling/storeConsent/sendToBilling) or its views — same screen, same name ("OT Counselling" in the sidebar), just reachable by Reception instead of a separate Counsellor login. |
| Booking permissions | **ADD to Reception** | Grant `ot.booking.create`, `ot.booking.modify`, `ot.patient.list`, `ot.package.set`, `ot.invoice.view`, `ot.bill.print` — the full counselling-adjacent permission set `ot_counsellor` has today. |
| `ot.booking.cancel` | **Open question** | Only `ot_receptionist` has this today (not `ot_counsellor`). Decide whether merged Reception gets it — see §8.3. |
| `ot.payment.record` / `ot.payment.export` | **Open question** | `ot_receptionist` can record OT payments today; the target workflow puts payment recording with Accountant only. Recommend **dropping** this from Reception unless client confirms otherwise — see §8.2. |
| Counselling list status | **ADD** | `OtCounsellorController::dashboard()` — the counselling list needs a visible **"Complete"** status per booking once Accountant has fully verified payment (see Phase 3), so Reception can see at a glance which cases Accounts has finished with. Likely a computed badge, not a new column (derive from `ot_status`). |
| Sidebar menu | **CHANGE** | "OT Appointments" and "OT Counselling" entries move from being gated by `ot_receptionist`/`ot_counsellor`-only permissions to permissions `receptionist` now also holds — no label changes needed, they already say "OT Appointments" / "OT Counselling". |

---

## 3. Phase 3 — Payment Auto-Advance (Accountant Module)

**Why:** today, a fully-paid booking sits at `STATUS_PAID` waiting for a manual "confirm payment" click from the Counsellor before Ward can act. The client wants this automatic.

| Item | Action | Detail |
|---|---|---|
| `OtAccountantController::storePayment()` | **CHANGE** | When cumulative payments reach `package_amount` (`$isFullyPaid`), don't stop at `STATUS_PAID` — immediately also perform what `OtCounsellorController::verifyPayment()` does today (advance to `STATUS_PAYMENT_VERIFIED`). This removes the manual confirm step entirely. |
| "Payment Verification Queue" | **CHANGE (simplify)** | `resources/views/hospital/ot/counsellor/dashboard.blade.php` — the block titled "Payment Verification Queue" / "Fully paid bookings — confirm payment before sending to Ward" becomes a **read-only status display** ("Paid") instead of an action queue, since there's no manual step left to perform. `OtCounsellorController::verifyPayment()` route/method can likely be removed once nothing calls it. |
| Paid/unpaid visibility | **ADD** | Client wants paid/unpaid status also visible to **OT Assistant** (and by extension **Ward Management**, since Ward receives the patient right after payment). Needs a permission check — reuse `ot.patient.list` (both new/expanded roles already need it) or introduce a dedicated read permission. See §8.6. |
| Accountant's own permissions | ✅ **No change** | `ot.payment.record`, `ot.payment.export`, `ot.invoice.view`, `ot.invoice.edit`, `ot.billing.manage`, `ot.bill.print` stay as-is — only the *behavior* after full payment changes, not what Accountant can access. |

---

## 4. Phase 4 — Ward Management Module (new role)

**Why:** today `ot_assistant` does both ward work (vitals/eye-drops) and lens work — the client wants these split into two roles so ward staff and OT-assistant staff can be different logins.

| Item | Action | Detail |
|---|---|---|
| New role `ward_management` | **ADD** | Permissions: `ot.ward.entry`, `ot.preop.entry`, `ot.dilation.track` (moved off `ot_assistant`) + read access to paid/unpaid status (§3, §8.6). |
| Ward screen | ✅ **No change** | `OtWardController::show/storeVitals/addEyeDrop` and `resources/views/hospital/ot/ward/show.blade.php` (vitals form, eye-drop register, Patient Status section) — unchanged, just re-gated to `ward_management` instead of `ot_assistant`. |
| Incoming queue | **CHANGE** | Booking arrives in Ward Management's queue automatically once `STATUS_PAYMENT_VERIFIED` is set (Phase 3) — this already works today via `OtWardController::show`'s `WARD_ALLOWED_STATUSES` guard; just needs the permission gate moved to `ward_management`. |
| "Ready for OT" action | **CHANGE (re-gate + wire as the forward trigger)** | `OtAccountantController::markReadyForOt()` (route `hospital.ot.ward.ready`) already sets `ot_status = STATUS_READY` and is already a separate action from `storeVitals`. Re-gate its permission from `ot.ward.entry` to whatever `ward_management` ends up with. This is the trigger that forwards the patient to OT Assistant's queue (Phase 5) — no new logic needed, `STATUS_READY` already does this; just confirm the "Send to OT Doctor" button/label on the Ward screen gets relabeled now that it's going to OT Assistant, not a doctor. |
| Known pre-existing gap | **Flag only, not required** | `ot_status` never actually reaches `STATUS_DILATED` anywhere in the current codebase — `addEyeDrop()` doesn't change status. Worth fixing while this controller is being touched, but out of scope unless client asks. |

---

## 5. Phase 5 — OT Assistant Module (absorbs OT Doctor's surgery recording)

**Why:** the client is removing the `ot_doctor` role; its one indispensable function (the Surgery Recording Form) needs a home, and OT Assistant is where it's going.

| Item | Action | Detail |
|---|---|---|
| `ot_assistant` permissions | **CHANGE** | Keep `ot.lens.record`, `ot.lens.implant`, `ot.meds.takehome`, `ot.patient.list`. **Lose** `ot.ward.entry`, `ot.preop.entry`, `ot.dilation.track` (→ Ward Management, Phase 4). **Gain** `ot.surgery.ready`, `ot.surgery.record` (from `ot_doctor`). |
| Surgery Recording Form | ✅ **No change to the form itself** | `OtDoctorController::createSurgery/storeSurgery` (validation, 4-item pre-surgery checklist, `OtSurgery`/`OtVerification`/`OtSurgeryMedicine` writes, advance to `STATUS_OPERATED`) — same form, same fields, just re-gated to `ot_assistant`. |
| Dashboard merge | **CHANGE** | `OtDoctorController::dashboard()` (queue of `STATUS_READY` bookings) merges into `OtAssistantController::dashboard()` (currently queues `STATUS_OPERATED` bookings for lens work) — OT Assistant's dashboard needs to show **both** queues (Ready-for-surgery and Ready-for-lens), or two tabs on one screen. Sidebar's separate "OT Doctor Dashboard" entry goes away; "OT Assistant Dashboard" becomes the one entry point. |
| Payment status visibility | **ADD** | Per §3 — OT Assistant needs to see paid/unpaid on the bookings in its queue. |
| Discharge/billing permissions currently on `ot_doctor` | **Open question — do NOT auto-merge into `ot_assistant`** | `ot_doctor` today also has `ot.invoice.view`, `ot.billing.manage`, `ot.discharge.generate`, `ot.discharge.patient`, `ot.certificate.print`. These conceptually belong to the new Discharge Counter role (Phase 6), not OT Assistant — flagged for confirmation, see §8.4. |

---

## 6. Phase 6 — Discharge Counter Module (new role)

**Why:** the client wants a dedicated desk for "Discharge & Invoices", separate from both the surgeon/assistant and the general Accountant.

| Item | Action | Detail |
|---|---|---|
| New role `discharge_counter` | **ADD** | Permissions: `ot.invoice.view`, `ot.invoice.edit`, `ot.billing.manage`, `ot.discharge.generate`, `ot.discharge.patient`, `ot.certificate.print`, `ot.bill.print` — the full set currently split across `ot_doctor` and `accountant`. |
| Discharge & Invoices screens | ✅ **No change** | `OtInvoiceController` (index, generate, print, summaryBillPrint) and `OtDischargeController` (print, certificatePrint, medicineSlipPrint, prescriptionPrint, lensSlipPrint, followupSlipPrint, printAllBundle) — every document (Invoice, Discharge Summary, Bill of Summary, Surgery Certificate, Prescription, Lens Implant Details, Take-Home Medicine Slip, Follow-up Slip, Print-All bundle) stays exactly as-is, just re-gated from `ot.billing.manage` (today shared by `accountant`+`ot_doctor`) to the new role. |
| Auto-arrival at Discharge Counter | **CHANGE** | Today, generating the invoice (`OtInvoiceController::generate()`) *is* what sets `ot_status = STATUS_DISCHARGED` — i.e. the booking doesn't "arrive" anywhere beforehand, Discharge Counter's queue should simply be bookings at `STATUS_OPERATED` (same list `OtInvoiceController::index()` already builds). No new transition logic needed — just confirm Discharge Counter's index query matches what the client expects as "arrived after surgery." |
| Should Accountant keep any of this? | **Open question** | See §8.5 — does Accountant retain `ot.billing.manage` for financial oversight, or does Discharge Counter become the sole owner? |
| Sidebar menu | **CHANGE** | "Discharge & Invoices" entry re-gated from `ot.billing.manage`-via-`accountant`/`ot_doctor` to `discharge_counter`. |

---

## 7. Phase 7 — Cleanup & Verification

| Item | Action | Detail |
|---|---|---|
| Remove dead code paths | **CHANGE** | Once Phases 1–6 are live, `OtDoctorController` and `OtCounsellorController::verifyPayment()` may become fully redundant (their logic absorbed elsewhere) — confirm no other route still points at them, then remove. |
| Permission catalogue tidy-up | **CHANGE** | `config` / `PermissionsSeeder.php` — several permissions already exist but aren't assigned to any non-super role today (`master.ot_staff`, `master.ot_slots`, `master.ot_types`, `master.ot_charges`, `settings.subscription`, `opd.reports.view`, `opd.reports.export`). Not required by this re-architecture, but worth a pass while touching the seeder — confirm with client whether any of these should attach to the new roles. |
| Regression pass | **ADD** | Full click-through of the new flow end-to-end (Reception → Doctor → Reception/Counselling → Accountant → Ward Management → OT Assistant → Discharge Counter) with one test booking per hospital tenant, before rollout. |

---

## 8. Open Questions (need client confirmation before implementation)

1. **Role slugs** for the two new roles — proposed `ward_management` and `discharge_counter`; confirm names/labels.
2. **`ot.payment.record`** — should Reception (post-merge) keep the ability to record OT payments itself (as `ot_receptionist` can today), or should payment recording be Accountant-only going forward?
3. **`ot.booking.cancel`** — currently only `ot_receptionist` has this (not `ot_counsellor`). Should merged Reception get it?
4. **Discharge/billing permissions currently on `ot_doctor`** (`ot.invoice.view`, `ot.billing.manage`, `ot.discharge.generate`, `ot.discharge.patient`, `ot.certificate.print`) — confirmed going to Discharge Counter in this doc; confirm nothing should instead go to OT Assistant.
5. **Should Accountant keep any Discharge & Invoices access** after Discharge Counter is introduced, for financial oversight/reporting? Or does Discharge Counter become the sole owner?
6. **What permission lets Ward Management and OT Assistant see paid/unpaid status?** Reuse `ot.patient.list` (both already need it for their queues) or introduce something more specific?
7. **Existing hospital staff currently in the roles being removed** — confirm it's fine to bulk-reassign them (`ot_receptionist`/`ot_counsellor` → `receptionist`, `ot_doctor` → `ot_assistant`) as part of the Phase 1 migration, rather than requiring hospital admins to manually reassign each user.
