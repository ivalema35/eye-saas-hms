# OT Workflow Upgrade — Mobile API PRD

**Version:** 1.0 | **Date:** 2026-07-22 | **Companion to:** `docs/OT_WORKFLOW_UPGRADE_PRD.md` (web/backend PRD) and `docs/MOBILE_APP_PRD.md` (existing mobile parity PRD, §7.15 "OT Module — Critical Gap", `FR-OT-01` … `FR-OT-17`)

**Purpose:** `docs/MOBILE_APP_PRD.md` §7.15 already scopes mobile APIs for the **current (v1)** OT workflow. This document scopes the **additional/changed** mobile API surface needed for the **new PDF-driven OT workflow** (Counsellor, Ward vitals/eye-drops, verification checklist, expanded lens/inventory, extra discharge docs, appointments). Numbering continues from `FR-OT-17` to avoid clashing with the existing document — use **`FR-OT-18` onward**.

**Conventions (unchanged from `MOBILE_APP_PRD.md` §3.3 — reuse, don't redefine):**
- Base path: `/api/v1/{slug}/...`
- Auth: Sanctum bearer token, guard `api` → `HospitalUser`
- Response envelope: `{ "success": bool, "data": {...}, "message": "..." }`
- Errors: `success: false` + appropriate HTTP status (401/403/404/422/501)
- Every endpoint is permission-gated — permission keys match the web-side keys added in `OT_WORKFLOW_UPGRADE_PRD.md` §9.2

---

## Phase 1 — Counsellor Module APIs

#### FR-OT-18: Counsellor Dashboard
- **Permission:** `ot.counselling.view`
- `GET /api/v1/{slug}/ot/counsellor/bookings?status=surgery_recommended`
- Returns bookings referred by doctor, awaiting counselling.

#### FR-OT-19: Counselling Form (Get/Save)
- **Permission:** `ot.counselling.record`
- `GET /api/v1/{slug}/ot/bookings/{id}/counselling` — returns diagnosis (from exam), lens options, package master data for the picker.
- `PUT /api/v1/{slug}/ot/bookings/{id}/counselling` — body: `diagnosis, surgery_type, eye, lens_category, lens_company, lens_model, lens_type, estimated_power, lens_cost, package_name, room_category, ot_charges, surgeon_charges, nursing_charges, consumables_charges, mediclaim, blood_reports_verified, blood_reports_normal`.
- Response includes server-computed `total_estimate`.

#### FR-OT-20: Consent Capture
- **Permission:** `ot.consent.capture`
- `POST /api/v1/{slug}/ot/bookings/{id}/consent` — body: `consent_given (bool), patient_signature (base64 PNG), guardian_signature (base64 PNG, nullable), witness_name`.
- Server stores signature images (see backend PRD §1) and returns signed URLs.
- **Mobile-specific note:** requires a signature-pad component (canvas capture) — flag to mobile dev team as new UI component, not a simple form field.

#### FR-OT-21: Send to Billing
- **Permission:** `ot.counselling.record`
- `POST /api/v1/{slug}/ot/bookings/{id}/send-to-billing` — transitions booking to `STATUS_COUNSELLED`, requires consent already captured (422 if not).

---

## Phase 2 — Appointment Module APIs

#### FR-OT-22: Appointment List
- **Permission:** `ot.appointment.view`
- `GET /api/v1/{slug}/ot/appointments?date=&status=&type=`

#### FR-OT-23: Create Appointment
- **Permission:** `ot.appointment.create`
- `POST /api/v1/{slug}/ot/appointments` — body: `appointment_type (phone/walk-in/online/referral), appointment_date, appointment_time, doctor_id, patient_name, mobile_no, whatsapp_no, age, gender, location_id`.
- Triggers SMS/WhatsApp confirmation server-side (async job) once `NotificationService` (backend PRD §2) exists — mobile just fires the create call, no client-side messaging logic.

#### FR-OT-24: Update / Cancel / Confirm Appointment
- **Permission:** `ot.appointment.edit`
- `PUT /api/v1/{slug}/ot/appointments/{id}`
- `POST /api/v1/{slug}/ot/appointments/{id}/cancel`
- `POST /api/v1/{slug}/ot/appointments/{id}/confirm`

#### FR-OT-25: Search by Appointment Number (Reception Check-in)
- **Permission:** `opd.patient.register`
- `GET /api/v1/{slug}/ot/appointments/search?q={uhid|name|mobile|appointment_no}` — extends existing `PatientApiController` contact-search (`MOBILE_APP_PRD.md` — Patient module) to also match appointment records, per backend PRD §2 reception check-in change.

---

## Phase 3 — Ward Module APIs (replaces stub `FR-OT-08`/`FR-OT-09` behavior)

> `MOBILE_APP_PRD.md` FR-OT-08 (Pre-Op Vitals) and FR-OT-09 (Dilation Tracking) already reserved these URL shapes but marked the underlying tables unused/stub. These are now the CONCRETE contracts once backend PRD Phase 3 ships.

#### FR-OT-26: Record Vitals (supersedes stub FR-OT-08)
- **Permission:** `ot.ward.entry`
- `POST /api/v1/{slug}/ot/bookings/{id}/vitals` — body: `bp, pulse, blood_sugar, temperature, spo2, ward_status (ready_for_surgery/not_fit)`.
- `GET /api/v1/{slug}/ot/bookings/{id}/vitals` — latest + history.

#### FR-OT-27: Eye Drop Register (supersedes stub FR-OT-09)
- **Permission:** `ot.dilation.track`
- `POST /api/v1/{slug}/ot/bookings/{id}/eye-drops` — body: `medicine_name, eye (RE/LE), dose_number, administered_at, remarks`. `administered_by` set server-side from authenticated user.
- `GET /api/v1/{slug}/ot/bookings/{id}/eye-drops` — full dose log, ordered by dose number, for the mobile nurse checklist UI.

#### FR-OT-28: Patient Verification Header
- **Permission:** `ot.ward.entry`
- `GET /api/v1/{slug}/ot/bookings/{id}/verification-header` — returns UHID, name, surgery type, eye — read-only convenience endpoint so mobile doesn't need to stitch together 2 calls for the header card.

---

## Phase 4 — OT Module APIs (verification checklist + expanded lens/medicine/notes)

#### FR-OT-29: Pre-Surgery Verification Checklist
- **Permission:** `ot.surgery.record`
- `POST /api/v1/{slug}/ot/bookings/{id}/verify` — body: `identity_verified, consent_verified, payment_verified, correct_eye_verified` (all bool). Returns 422 if any is `false` and doctor tries to proceed to surgery record.

#### FR-OT-30: Surgery Record (extends existing FR-OT-11)
- **Permission:** `ot.surgery.record`
- `POST /api/v1/{slug}/ot/bookings/{id}/surgery` — **body extended** vs. existing contract: add `assistant_id, ot_room, start_time, end_time, blood_loss`, plus `medicine_group_id` + `medicines[]` (replacing the old freeform `ward_medicines` array — see backend PRD §4).
- **Breaking change flag:** if the mobile app already implemented FR-OT-11 against the old contract, this is a payload-shape change, not purely additive — coordinate release with mobile team.

#### FR-OT-31: OT-Scoped Medicine Groups
- **Permission:** `ot.surgery.record`
- `GET /api/v1/{slug}/ot/medicine-groups?scope=ot` — reuses existing medicine-group endpoint with new `scope` query param (backend PRD §4 `usage_scope` column) so OT groups don't leak into OPD prescription pickers and vice versa.

#### FR-OT-32: Lens Details (extends existing FR-OT-12)
- **Permission:** `ot.lens.record`, `ot.lens.implant`
- `GET|POST /api/v1/{slug}/ot/bookings/{id}/lens` — **body extended**: `manufacturer, lens_name, lens_type (Accommodating/Aspheric/EDOF/Monofocal/Multifocal/Spherical/Toric/Trifocal), lens_power (from master dropdown), axis, batch_number, serial_number, expiry_date, mrp, implant_status`.
- On `implant_status = true`, server auto-decrements `lens_inventory.available_stock` (backend PRD §7) — no separate mobile call needed.

---

## Phase 5 — Billing / Payment Verification APIs

#### FR-OT-33: Payment Status
- **Permission:** `ot.invoice.view`
- `GET /api/v1/{slug}/ot/bookings/{id}/payment-status` — returns computed `Paid / Partially Paid / Pending` (derived, not stored — backend PRD §5) + full payment history list.

#### FR-OT-34: Counsellor Payment Verification (loop-back gate)
- **Permission:** `ot.counselling.record`
- `POST /api/v1/{slug}/ot/bookings/{id}/verify-payment` — Counsellor confirms payment before Ward can act. Transitions booking to `STATUS_PAYMENT_VERIFIED`. Ward list endpoint (`FR-OT-07`) must filter on this new status, not `paid` directly — **mobile Ward dashboard query param changes accordingly**.

---

## Phase 6 — Discharge Document APIs (extends existing FR-OT-15)

#### FR-OT-35: Additional Print Documents
- **Permission:** `ot.discharge.generate`
- `GET /api/v1/{slug}/ot/bookings/{id}/print/prescription`
- `GET /api/v1/{slug}/ot/bookings/{id}/print/lens-slip`
- `GET /api/v1/{slug}/ot/bookings/{id}/print/followup-slip`
- All return a PDF blob (same pattern as existing invoice/discharge/certificate print endpoints under FR-OT-15) — mobile opens in an in-app PDF viewer or triggers OS share sheet, same as current print handling.

#### FR-OT-36: Print-All Bundle
- **Permission:** `ot.discharge.generate`
- `GET /api/v1/{slug}/ot/bookings/{id}/print/discharge-bundle` — single merged PDF of all 7 discharge documents, for one-tap print at the discharge counter (PDF Step 9 requirement).

---

## Phase 7 — Inventory (Lens Master) APIs

#### FR-OT-37: Lens Inventory CRUD
- **Permission:** `ot.inventory.manage`
- `GET /api/v1/{slug}/masters/ot/lens-inventory`
- `POST /api/v1/{slug}/masters/ot/lens-inventory`
- `PUT /api/v1/{slug}/masters/ot/lens-inventory/{id}`
- `DELETE /api/v1/{slug}/masters/ot/lens-inventory/{id}`
- Fields match backend PRD §7 `lens_inventory` table.

#### FR-OT-38: Lens Search/Pick (used by Counselling + OT lens form)
- **Permission:** `ot.counselling.record` or `ot.lens.record` (either)
- `GET /api/v1/{slug}/ot/lens-inventory/search?q=&type=&power=` — typeahead endpoint feeding FR-OT-19 and FR-OT-32 pickers, returns only in-stock (`available_stock > 0`) items by default with a `?include_out_of_stock=1` override.

---

## Phase 8 — Reports & Dashboard APIs

#### FR-OT-39: OT Report Endpoints
- **Permission:** `ot_reports` (existing key) / `reports.view`
- `GET /api/v1/{slug}/reports/ot/{register}` where `{register}` ∈ `appointments, registration, doctor-consultation, counselling, billing, ot, discharge`.
- `GET /api/v1/{slug}/reports/ot/clinical/{report}` where `{report}` ∈ `surgery-wise, doctor-wise, lens-usage, complications`.
- `GET /api/v1/{slug}/reports/ot/financial/{report}` where `{report}` ∈ `daily-collection, monthly-revenue, package-wise, pending-payments`.
- All support `?export=excel|pdf` matching the existing `ReportController` export pattern.

#### FR-OT-40: Management Dashboard Widgets
- **Permission:** Hospital Admin (`is_super` role) or `reports.view`
- `GET /api/v1/{slug}/dashboard/ot-summary` — returns `total_patients, surgeries_completed, ot_utilization_pct, avg_waiting_time_minutes, revenue_trend[], lens_consumption[], patient_turnaround_time_minutes`.

---

## Mobile Rollout Notes

1. **Do not build Phase 8 mobile screens first** — same dependency order as the backend PRD (§10 there). Reports/dashboards consume data these other phases produce.
2. **FR-OT-30 and the FR-OT-08/09 supersessions (Phase 3) are payload/contract changes**, not pure additions — if the mobile app team has already started against the old `MOBILE_APP_PRD.md` §7.15 stubs, treat these as breaking-change tickets, not new-feature tickets.
3. **Signature capture (FR-OT-20)** and **PDF bundle viewing (FR-OT-36)** are the two genuinely new *mobile UI component* asks in this whole set — flag early to mobile design/dev, everything else is standard form + list screens matching existing OT screen patterns already scoped in `MOBILE_APP_PRD.md`.
4. **SMS/WhatsApp (FR-OT-23 side-effect)** depends on the backend vendor decision flagged in `OT_WORKFLOW_UPGRADE_PRD.md` §9.4 — mobile has no work here beyond firing the existing create call; do not build client-side SMS/WhatsApp logic.

---

*Keep this file's `FR-OT-*` numbering in sync with `docs/MOBILE_APP_PRD.md` §7.15 if that document is renumbered — always grep for the highest existing `FR-OT-NN` before adding a new one.*
