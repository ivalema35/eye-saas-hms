# OT 1.0 Web Remaining Work — PRD (Phase-wise & Module-wise)

**Version:** 3.1 | **Date:** 2026-07-23  
**Scope:** **Web only** (`resources/views/hospital/ot/*`, hospital routes, controllers, migrations)  
**Source:** Client PDF `OT 1.0.pdf` + current `eye-saas` codebase  
**Companion (mostly shipped):** `docs/OT_WORKFLOW_UPGRADE_PRD.md` (v1)

**Purpose:** PDF OT workflow vs current **web** project — **shu KEEP**, **shu ADD**, **shu CHANGE**, **shu REMOVE**. Aa PRD remaining **web** work mate che. Shipped modules ne from-scratch rewrite nathi.

**Phase A status:** ✅ Implemented (2026-07-23) — Doctor Recommend Surgery, Ward `payment_verified` gate, Ward statuses Preparing/Ready/Hold/Complicated.  
**Phase B status:** ✅ Implemented (2026-07-23) — `ot_surgery_medicines` pivot + Registration Register.  
**Phase C status:** ✅ Implemented (2026-07-23) — A5 invoice/medicine slip + seeders/`ot_counsellor` sync.  
**Final leftover status:** ✅ Implemented (2026-07-23) — dropped `ward_medicines` JSON; counsellor `ot.booking.*`; payment receipt print; lens low-stock/expiry alerts; reception OT check-in copy.

---

## 0. OUT OF SCOPE (do not build)

| Item | Reason |
| --- | --- |
| **Mobile OT API** | Client — **hal web j**. `OtApiController` stub touch/expand **mat karo**. Mobile PRDs ignore. |
| **SMS / WhatsApp messages** | Client — **hal nathi**. `whatsapp_no` store rahe; send/gateway **mat banavo**. |
| Medicine stock inventory | PDF only Lens inventory (already shipped). |
| Department master on appointments | YAGNI unless client re-asks. |
| Rebuilding Counsellor / Ward / Reports / Lens inventory / Discharge from scratch | Already shipped on web. |

---

## 0.1 UI / Design rule (mandatory)

Badha naya ane changed screens **existing hospital OT design ane color theme follow karo**. Navi theme, nava colors, nava card systems **mat invent karo**.

| Follow | Reference |
| --- | --- |
| Theme CSS vars | `var(--color-primary)`, `var(--color-secondary)`, `var(--color-surface)` (existing hospital theme) |
| OT screen patterns | `resources/views/hospital/ot/counsellor/*`, `doctor/*`, `ward/*`, `accountant/*`, `assistant/*` |
| Layout / nav | `resources/views/hospital/layouts/app.blade.php` — OT sidebar group |
| Forms / inputs | Existing `.hms-input`, `.clinical-input`, Select2, Bootstrap badges already used in OT blades |
| Buttons / headers | Match sibling OT pages (gradient headers, primary/secondary buttons already on OT forms) |
| Prints | Existing `hospital/ot/billing/*` print blades + A5 CSS — extend, don’t redesign from zero |

**Rule for agents/devs:** New UI = copy nearest existing OT blade structure + same CSS variables. Pixel-perfect new design system = out of scope.

---

## 1. Executive Summary

### Target workflow (PDF → web)

```
OT Appointment Booked
→ Reception Check-in (OPD fee)
→ Doctor Exam → Surgery Recommended
→ Counsellor (package / lens / consent) → Awaiting Payment
→ Billing → Payment Completed
→ Counsellor Payment Verify → Sent to Ward
→ Ward (vitals + eye drops) → Preparing / Ready / Hold / Complicated
→ OT (verify → surgery → lens → OT meds) → Completed
→ Discharge (A5 prints) → Discharged
```

### Current web status chain

```
booked → surgery_recommended → counselled → paid → payment_verified
→ in_ward → dilated → ready → operated → discharged
```

### Module matrix (web remaining)

| # | Module | Status | Remaining |
| --- | --- | --- | --- |
| 1 | Appointment | ✅ Done | **KEEP** |
| 2 | Reception check-in | ✅ Done | **KEEP** (optional copy polish) |
| 3 | Doctor → Surgery Recommended | 🟡 Constant only | **ADD** |
| 4 | Counsellor + Consent | ✅ Done | **CHANGE** queue filter |
| 5 | Billing | ✅ Done | **KEEP** |
| 6 | Payment verify → Ward | 🟡 Gate leak | **CHANGE / FIX** |
| 7 | Ward vitals + drops | ✅ Partial statuses | **CHANGE** |
| 8 | OT surgery + meds | 🟡 JSON meds | **ADD** pivot + **REMOVE** JSON later |
| 9 | Discharge p
rints | ✅ Done | **CHANGE** A5 polish |
| 10 | Lens inventory | ✅ Done | **ADD** optional alerts |
| 11 | Reports + dashboard | 🟡 Missing 1 report | **ADD** Registration Register |

**Biggest remaining gap:** OPD Doctor exam → `surgery_recommended` handoff (web UI + controller).

---

## 2. Phase A — Workflow glue (FIRST)

### A1. Doctor Exam → Surgery Recommended — **ADD**

| What | Action | Detail |
| --- | --- | --- |
| Gap | `OtBooking::STATUS_SURGERY_RECOMMENDED` exists; exam UI never sets it | — |
| Exam UI | **ADD** | Button/action: **Recommend Surgery / Refer to Counsellor** on primary/secondary exam screen |
| Backend | **ADD** | Create/update `OtBooking` (eye, surgeon, diagnosis hint); set `ot_status = surgery_recommended` |
| Counsellor queue | **CHANGE** | Prefer `surgery_recommended` on dashboard (today also shows `booked`) |
| Permission | **ADD** | `ot.surgery.recommend` (or reuse `ot.booking.create`) for `doctor` / `ot_doctor` |
| UI theme | **FOLLOW** | Same button/card styles as existing exam + OT counsellor pages — no new palette |

### A2. Ward only after `payment_verified` — **CHANGE / FIX**

| What | Action | Detail |
| --- | --- | --- |
| Gap | PDF Step 6 — Ward after counsellor verifies payment | Views still allow `STATUS_PAID` |
| Ward list/show/ready | **CHANGE** | Require `payment_verified` only |
| Files | **CHANGE** | `ot/ward/show.blade.php`, `ot/accountant/ward.blade.php`, `OtAccountantController::wardIndex` |

### A3. Ward statuses match PDF — **CHANGE**

| PDF | Current | Action |
| --- | --- | --- |
| Preparing | — | **ADD** `preparing` |
| Ready for OT | `ready_for_surgery` | **KEEP** |
| Hold | — | **ADD** `hold` |
| Complicated | inside `not_fit` | **ADD** `complicated` |

| What | Action | Detail |
| --- | --- | --- |
| Migration / model | **CHANGE** | `ot_pre_op.pre_op_status` + `OtPreOp` constants |
| Ward form radios | **CHANGE** | `ot/ward/show.blade.php` — same radio/badge styles as today |

**Phase A done:** Doctor recommends surgery from exam; Ward blocked until verified; statuses = Preparing / Ready / Hold / Complicated; UI matches existing OT theme.

---

## 3. Phase B — Clinical data + report

### B1. OT surgery medicines pivot — **ADD** then **REMOVE** JSON

| What | Action | Detail |
| --- | --- | --- |
| Already OK | **KEEP** | `medicine_groups.usage_scope` (`opd` / `ot` / `both`) + surgery group picker UI |
| Gap | Medicines in `ward_medicines` JSON | — |
| Table | **ADD** | `ot_surgery_medicines` (`ot_surgery_id`, `medicine_id`, `quantity`, `dose`) |
| `OtDoctorController@storeSurgery` | **CHANGE** | Write pivot |
| Discharge prints | **CHANGE** | Read pivot (`OtDischargeController`) |
| `ot_surgeries.ward_medicines` | **REMOVE** | After backfill — drop column or stop reading |
| UI | **FOLLOW** | Keep existing surgery form medicine rows look (`ot/doctor/surgery.blade.php`) |

### B2. Registration Register — **ADD**

| What | Action | Detail |
| --- | --- | --- |
| Gap | PDF Operational → Registration Register missing | — |
| Report | **ADD** | Key `registration` in `OtReportController` + export |
| UI | **FOLLOW** | Same OT reports index/show layout as other registers (`hospital/reports/ot/*`) |

**Phase B done:** Medicines relational; Registration Register live on web reports page.

---

## 4. Phase C — Web polish & housekeeping

### C1. A5 discharge prints — **CHANGE**

| What | Action | Detail |
| --- | --- | --- |
| Gap | PDF: A5 vertical, letter-pad / auto header | — |
| Prints | **CHANGE** | Bill Summary, Discharge, Certificate CSS — extend existing billing print blades |
| Print-all | **KEEP** | Existing bundle routes |

### C2. Lens low-stock / expiry — **ADD (optional)**

| What | Action | Detail |
| --- | --- | --- |
| Priority | Optional — confirm before build | — |
| Web widget / command | **ADD** | Dashboard widget (existing dashboard OT KPI style) + optional `CheckLensExpiryStock` command |
| UI | **FOLLOW** | `hospital/dashboard/index.blade.php` OT overview cards — same colors |

### C3. Seeders — **CHANGE / FIX**

| What | Action | Detail |
| --- | --- | --- |
| `ot.billing.manage` | **CHANGE** | Ensure in `PermissionsSeeder` |
| `SyncTenantOTData` | **CHANGE** | Include `ot_counsellor` in default OT roles |

**Phase C done:** A5 prints usable on letterhead; seeders consistent; optional stock alerts if approved.

---

## 5. Module-wise — KEEP / ADD / CHANGE / REMOVE (web only)

### 5.1 Appointment
| Action | Item |
| --- | --- |
| **KEEP** | CRUD, types, confirm/cancel, search, `whatsapp_no` field storage |
| **REMOVE / don’t add** | SMS/WhatsApp send, Mobile API, department master |

### 5.2 Reception
| Action | Item |
| --- | --- |
| **KEEP** | UHID / name / mobile / appointment search, OPD fee, day visit |
| **CHANGE (optional)** | Clearer “from OT appointment” copy — same form theme |

### 5.3 Doctor Examination
| Action | Item |
| --- | --- |
| **KEEP** | Primary / secondary exam |
| **ADD** | Recommend Surgery → Counsellor (Phase A1) — highest priority |

### 5.4 Counsellor
| Action | Item |
| --- | --- |
| **KEEP** | Diagnosis, lens, package, consent, mediclaim, blood reports, send-to-billing, verify-payment, shared login permissions |
| **CHANGE** | Dashboard prefer `surgery_recommended` after A1 |

### 5.5 Billing
| Action | Item |
| --- | --- |
| **KEEP** | Partial pay, Paid / Partial / Pending, receipt, invoice |
| **REMOVE** | Nothing |

### 5.6 Ward
| Action | Item |
| --- | --- |
| **KEEP** | Verification header, vitals, eye-drop register |
| **CHANGE** | `payment_verified` gate + Preparing / Ready / Hold / Complicated |

### 5.7 Operation Theatre
| Action | Item |
| --- | --- |
| **KEEP** | Verification, surgeon/assistant/room/times, lens + stock decrement, OT medicine groups, notes |
| **ADD** | `ot_surgery_medicines` pivot |
| **REMOVE** | `ward_medicines` JSON (after migrate) |

### 5.8 Discharge
| Action | Item |
| --- | --- |
| **KEEP** | All 7 docs + print-all |
| **CHANGE** | A5 layout polish on existing blades |

### 5.9 Lens Inventory
| Action | Item |
| --- | --- |
| **KEEP** | Master + stock + implant decrement |
| **ADD (optional)** | Low-stock / expiry web alerts |
| **REMOVE / don’t add** | Medicine stock module |

### 5.10 Reports & Dashboard
| Action | Item |
| --- | --- |
| **KEEP** | Existing OT registers, clinical/financial reports, admin OT KPIs |
| **ADD** | Registration Register |
| **REMOVE / don’t add** | Mobile report APIs |

---

## 6. Schema / files summary (remaining web work)

| Artifact | Action | Phase |
| --- | --- | --- |
| Exam → `ot_bookings` handoff | **ADD** | A |
| Ward blades + `wardIndex` query | **CHANGE** | A |
| `ot_pre_op.pre_op_status` | **CHANGE** | A |
| `ot_surgery_medicines` | **ADD** | B |
| `ot_surgeries.ward_medicines` | **REMOVE** | B (after backfill) |
| Registration report (web) | **ADD** | B |
| Print A5 CSS | **CHANGE** | C |
| Lens alert widget (optional) | **ADD** | C |
| PermissionsSeeder / SyncTenantOTData | **CHANGE** | C |

---

## 7. Build order (web only)

```
Phase A  Doctor handoff + Ward gate + Ward statuses     ← FIRST
   ↓
Phase B  Medicine pivot + Registration Register
   ↓
Phase C  A5 print polish + seeders + optional lens alerts

Never in this PRD:
  ✗ Mobile OT API
  ✗ SMS / WhatsApp send
```

---

## 8. Acceptance checklist (web)

- [x] Doctor marks Surgery Recommended from exam; booking on Counsellor dashboard. *(Phase A — done)*
- [x] Ward list/show only after `payment_verified`. *(Phase A — done)*
- [x] Ward statuses: Preparing, Ready for OT, Hold, Complicated. *(Phase A — done)*
- [x] Surgery medicines in pivot; discharge prints use pivot; JSON unused/removed. *(Phase B + final drop of `ward_medicines` column)*
- [x] OT Reports → Registration Register + Excel export. *(Phase B — done)*
- [x] Discharge/certificate/summary A5 vertical with correct header. *(Phase C — done)*
- [x] Seeders include `ot.billing.manage` + `ot_counsellor` sync. *(Phase C — done)*
- [x] Counsellor can create OT surgery booking (`ot.booking.create`). *(Final leftover — done)*
- [x] Dedicated payment receipt print. *(Final leftover — done)*
- [x] Lens low-stock / near-expiry alerts (dashboard + scheduled command). *(Final leftover — done)*
- [x] Reception OT appointment check-in copy. *(Final leftover — done)*
- [x] All new/changed UI uses existing OT color theme.
- [x] **No** Mobile API work.
- [x] **No** SMS/WhatsApp send code.

---

_Companion to `docs/PRD_MASTER.md`. Supersedes remaining-gap narrative of `OT_WORKFLOW_UPGRADE_PRD.md` for post–2026-07-22 **web** work. Do not re-implement v1 shipped phases. Do not use mobile API PRDs for this sprint._
