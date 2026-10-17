# Mobile Record Card Unification Plan

**Scope: `eye_care_app` (mobile) only.** The tablet app (`eye_care_tab`) already
has its own, separate redesign doctrine — every OT desk screen there is a
full-page **table** (`ScaledDataTable`), not a card list. Nothing in this
document applies to the tablet app; do not port this card pattern there.

## 1. Why this document exists

We just redesigned `lib/screens/report_channel_screen.dart`'s patient card
(the Walk-in / Phone / OT-Appointment report list) through 3 iterations based
on live feedback:

1. **v1** — one full-width `Row` per field (date, age, phone, doctor, case
   type, receptionist, location), each with its own icon, each on its own
   line. Wasted vertical space (a row costs a full line even for one short
   value) and gave no way to tell fields apart at a glance. Also let long
   names wrap across 2 heavy bold lines instead of truncating.
2. **v2** — packed every field into a single `Wrap` of small pills, and
   colored each pill (background tint + icon + text) by field type. Fixed
   the space problem, but reading 6-7 fully-colored pills side by side
   looked like a "rainbow" of badges — too loud.
3. **v3 (current, kept)** — inspired by a user-supplied reference image (a
   property-management record card — used only as inspiration, not copied
   literally): header row (bold name + a trailing pill), a small
   UHID + date subline, a divider, then a **2-column grid** where each cell
   is a small colored icon badge + a tiny grey field-name **label** + a bold
   value underneath. Color now lives only on the small icon badge — the
   label text is what actually identifies the field, so the card reads as
   calm and structured rather than either a wall of monochrome rows or a
   pile of colored chips.

The user now wants this v3 pattern applied consistently everywhere in the
mobile app that shows a similar dense, per-record data card — **but only
where it's a genuine fit**, not forced onto every card in the app. This
document is the survey + decision record + migration checklist for that
work, so a multi-screen change doesn't lose track of what's in scope, what's
deliberately excluded and why, and what the shared widget's contract is —
written before touching more screens, per standing instruction to plan big
changes in a doc first.

## 2. The shared widget: evolve `AppRecordCard`, don't create a parallel one

`lib/widgets/app_record_card.dart` **already exists** and is already a
generic "record card" shell — built earlier for OT Reports' dynamic
columns. It currently renders the *pre-icon-badge* style: bold title +
2-column grid of plain `"Label: Value"` text pairs, no icon, no color. It is
currently used by exactly one screen (`ot_report_viewer_screen.dart`).

**Decision: extend this existing widget in place to support the v3
icon-badge cell style, rather than adding a second, competing generic card
widget.** Having two "generic record card" components side by side (an old
plain one and a new icon-badge one) is exactly the kind of duplication this
effort is supposed to remove. `AppRecordField` gains two new **optional**
fields:

```dart
class AppRecordField {
  final String label;
  final String value;
  final IconData? icon;   // NEW — when set, renders the v3 icon-badge cell
  final Color? color;     // NEW — icon-badge tint; ignored if icon is null
  const AppRecordField(this.label, this.value, {this.icon, this.color});
}
```

Rendering rule inside `AppRecordCard._field()`: if `icon != null`, render the
v3 cell (20×20 rounded-square icon badge at `color.withValues(alpha: 0.12)`,
icon at full `color`, tiny grey uppercase-tracking label, bold dark value
below). If `icon == null`, keep today's plain `"Label: Value"` `RichText`
row unchanged. **This is a strictly additive, backward-compatible change** —
every existing `AppRecordField(...)` call site (today: only
`ot_report_viewer_screen.dart`) keeps working with zero visual change unless
it opts in by passing `icon`/`color`.

`AppRecordCard` itself also gains 3 new optional parameters to cover what
every migration target below actually needs, all backward compatible
(default `null`/absent = today's exact behavior):

```dart
class AppRecordCard extends StatelessWidget {
  final String title;
  final String? subtitle;      // NEW — small subline under the title (e.g. "KASH0011  ·  📅 17 Sep 2026")
  final List<AppRecordField> fields;
  final Widget? trailing;      // existing — top-right of header (fee pill / status pill)
  final Widget? actions;       // NEW — rendered below the field grid, after its own divider
  final VoidCallback? onTap;   // NEW — wraps the whole card in InkWell when set
  ...
}
```

No new files, no new widget names, no model unification. Each screen keeps
its own model (`OtBookingSummary`, `Patient`, `OtAppointmentItem`,
`QueuePatient`, `HistoryPatient`, `ReportPatient` — 6 distinct model classes
exist across these screens and unifying them is **out of scope**, far too
invasive for what this is). Each screen's own card-building code just maps
its model's fields into a `List<AppRecordField>` and hands it to
`AppRecordCard`.

## 3. Canonical field-color convention

So the same *kind* of field always reads as the same color on every screen
(not re-picked ad hoc per screen, which is exactly the kind of drift this
effort exists to prevent), add a small convention table as static getters in
`app_record_card.dart`, right next to the widget it configures:

```dart
/// Canonical icon-badge colors for common field types across every
/// AppRecordCard/AppRecordField call site — pass one of these as
/// AppRecordField.color so "phone" (say) is always green everywhere,
/// rather than each screen inventing its own mapping. A convention list,
/// not enforced by the type system: adding a genuinely new field type
/// should add a line here, not a one-off inline color.
abstract class RecordFieldColors {
  static Color get date         => AppColors.blue;
  static Color get age          => AppColors.purple;
  static Color get phone        => AppColors.green;
  static Color get doctor       => AppColors.teal;
  static Color get assistant    => AppColors.teal;      // same bucket as doctor — both clinical staff
  static Color get caseType     => AppColors.orange;    // case type / surgery type / diagnosis-ish "category" fields
  static Color get receptionist => AppColors.primary;   // non-clinical staff
  static Color get location     => AppColors.redDark;
  static Color get package      => AppColors.green;     // amounts/fees — matches the existing fee-pill green
}
```

**Important distinction — do not conflate this with status-badge colors.**
`paymentStatusColor(status)` and `otStatusColor(status)` (already shared
helpers) encode what a *value means* (pending/paid/refused/etc.) and stay
exactly as they are today, rendered as the header's trailing pill — not as a
grid cell. `RecordFieldColors` encodes what a *field is* (identity), always
constant regardless of value. Never route a status value through
`RecordFieldColors`, and never put a semantic status pill inside the field
grid.

## 4. Migration tiers

**Status (2026-09-19): §2/§3 done, all of Tier 1 done except item 6 (lowest
priority within the tier — see its own note), Tier 4 done, plus the plan's
own step 5.4 (`report_channel_screen.dart` migrated onto the shared widget
too) — see the "DONE" marker on each item below. Tier 2/3 not started
(Tier 3 is intentionally permanent — nothing to "start").**

### Tier 1 — full migration (header + subline + divider + field grid + actions/onTap)

These have a genuinely dense, undifferentiated field list today (plain
icon+text rows or a `Wrap` of uncolored chips) — the exact problem v3 was
built to solve. Concrete field → icon/color mapping for each, from the
current code:

1. **DONE — `ot_counsellor_dashboard_screen.dart` → `_bookingCard`**
   Model: `OtBookingSummary`. Header: patient name, trailing: existing
   status pill (Recommended/Paid/history status — keep as-is, it's a status
   pill, not a grid field). Subtitle: `patientCode` + eye. Grid fields:
   Phone (`Icons.call_outlined`, `RecordFieldColors.phone`), OT Date
   (`Icons.calendar_today_outlined`, `RecordFieldColors.date`), Doctor/
   Assistant (`Icons.medical_services_outlined` / `support_agent_rounded`,
   `RecordFieldColors.doctor`), Surgery Type (`Icons.label_outline_rounded`,
   `RecordFieldColors.caseType`); Payment-Status card variant: Package
   Amount (`Icons.currency_rupee_rounded` or local currency icon,
   `RecordFieldColors.package`) instead of doctor/type. `onTap` opens the
   counselling form or the view dialog (unchanged logic) — no separate
   action buttons needed.

2. **DONE — `share_history_screen.dart` → `_HistoryPatientCard`**
   Model: `HistoryPatient`. Header: patient name, trailing:
   `_HospBadge` (own/shared pill — keep as-is). Subtitle: `patientCode`.
   Grid fields: Doctor (`RecordFieldColors.doctor`), Appointment Date
   (`RecordFieldColors.date`), Phone (`RecordFieldColors.phone`), Age
   (`RecordFieldColors.age`). `actions`: the single trailing "View"
   `TextButton` (unchanged).

3. **DONE — `ot_appointment_list_screen.dart`'s inline card**
   Model: `OtAppointmentItem`. Header: patient name, trailing:
   `StatusBadge.otAppointmentStage` (keep as-is). Subtitle:
   `appointmentNumber` + `mobileNo`. Grid fields: Type
   (`Icons.category_outlined`, `RecordFieldColors.caseType`), Appointment
   Date (`RecordFieldColors.date`), Doctor (`RecordFieldColors.doctor`).
   `actions`: existing conditional Confirm/Cancel/Edit row, unchanged.

4. **DONE — `ot_assistant_dashboard_screen.dart`'s inline card**
   Model: `OtBookingSummary`. Header: patient name, trailing: OT-status pill
   (keep as-is). Subtitle: `patientCode` + eye. Grid fields: replace the
   current `_infoChip` `Wrap` 1-for-1 — OT Type (`RecordFieldColors.
   caseType`), Package Amount (`RecordFieldColors.package`), Surgeon (only
   when `_seeAll`, `RecordFieldColors.doctor`), Payment Status — **this one
   stays a status pill via `paymentStatusColor()`, not a grid cell** (see
   §3), so keep it as a small trailing pill inside the grid's row *or*
   move it up next to the OT-status pill in the header — pick whichever
   reads cleaner when you actually build it, it doesn't have to be a grid
   field just because the others are. `actions`: existing View + conditional
   Operate row, unchanged.

5. **DONE — `clinical_queue_screen.dart`'s 3 cards** (`_PrimaryPatientCard`,
   `_DilationPatientCard`, `_SecondaryPatientCard`)
   Model: `QueuePatient`. Header: patient name, trailing: age/gender badge
   (keep as-is). Subtitle: `patientCode` (as "MRD: {code}" — matches this
   screen's existing wording, doesn't need to match the reports screen's
   wording verbatim). Grid fields per card: Contact
   (`RecordFieldColors.phone`), Case Type / findings
   (`RecordFieldColors.caseType`), plus whatever timestamp fields that
   specific card already shows (Registered At / Primary-Done At / dilation
   minutes) using `RecordFieldColors.date`. **Caveat, must be preserved
   exactly**: the live-updating wait-time pills (`WaitPill`) and
   `_DilationCountdown` are stateful, tick every second, and must stay
   OUTSIDE the static field grid, in their existing footer position —
   do not try to force them into a grid cell. `actions`: existing
   Start Primary/Secondary button (or the `_DilationPatientCard`'s
   double-tap gesture) stays exactly as today, just placed in the `actions`
   slot alongside the wait pills.

6. **NOT STARTED — `patients_screen.dart` → `_PatientDetailSheet`** (the bottom sheet, NOT
   the list card — see Tier 3 for why the list card itself is excluded)
   This sheet already lays out Appointment/Doctor/Case/Fee and
   Personal/Contact info in ad hoc icon+label+value cells very close in
   spirit to v3 already. Lower priority than 1-5 (it's a bottom sheet, not
   a list, so the "cards ma space bachavvani" pressure that motivated this
   whole effort doesn't apply here) — migrate for visual family consistency
   once 1-5 are done and proven, not urgently.

### Tier 2 — header/subline alignment only (optional, low priority)

These screens are dominated by **action buttons**, not data fields — there
usually isn't enough distinct field content to justify a 2-column grid, and
forcing one would just add empty-looking cells or an oddly short single-row
grid. Only align the header shape (bold name + trailing status pill + small
UHID/date subline) for visual family resemblance with everything else, if
and when convenient — do **not** restructure their action rows or force a
grid onto 1-2 leftover fields:

- `ot_accountant_dashboard_screen.dart` → `_buildRow` (dominated by the
  View/Refund/Add-Payment/Receipt button `Wrap`)
- `ot_ward_queue_screen.dart` inline card (only 2 status pills' worth of
  real data; dominated by the Vitals/Send-to-Assistant action row)
- `ot_discharge_dashboard_screen.dart` → `_bookingCard` (only a status pill
  + an invoice-generated pill; whole card is a single tap target)
- `doctor_ot_list_screen.dart` inline card (one derived status *sentence*,
  not a field list; dominated by the conditional Assign/Refuse action row)

### Tier 3 — explicitly excluded, do not migrate

Listed here specifically so nothing gets "missed" by accident later — these
were considered and rejected on purpose, with the reason on record:

- **`patients_screen.dart` → `_PatientCard`** (the main Patients list card
  itself). Dominated by a row of up to 7 conditional action icon-buttons
  (View/Primary Exam/Secondary Exam/History/Print/Edit/Check-in) — the data
  portion is only 3 compact bits (doctor, MRD, fee) that already read fine
  in a single `Wrap`. A 2-column grid here would fight the action row for
  space and wouldn't meaningfully improve scannability.
- **`dashboard_screen.dart`** — `_TodayPatientsWidget`'s patient/OT rows and
  the Primary Queue's `_queueItem`. These are deliberately compact
  `ListView.separated` **rows** (not individually-bordered cards) embedded
  inside a larger dashboard widget with limited vertical budget. Multiple
  fields are intentionally packed onto one `·`-joined text line to keep the
  whole dashboard scannable at a glance. Expanding these into full record
  cards with a 2-column grid would significantly inflate the dashboard's
  height for no real readability gain in that context.
- **`clinical_queue_screen.dart` → `_AllDoctorsCard` / `_DoctorCard`**. These
  are horizontal-scroll doctor-filter tiles (name + assigned-count + 2
  count-pills) — a dashboard/filter-tile pattern, not a per-record data
  card, and explicitly out of scope per the original ask (no dashboard
  summary/stat cards).

### Tier 4 — DONE — `ot_report_viewer_screen.dart` (OT Reports' dynamic columns)

The user explicitly wants OT Reports included too. This screen already uses
`AppRecordCard`/`AppRecordField` (the plain, no-icon style) for its 16
report types, each with **backend-defined, arbitrary column headings** — so
there is no fixed, meaningful field→icon mapping the way there is for
"Phone" or "Doctor" elsewhere. Treatment: give each field a **rotating**
icon/color pair by column index (cycle through a small fixed palette — e.g.
`[blue, purple, teal, orange, green, redDark]` paired with a small set of
generic icons like `Icons.dashboard_outlined`/`table_rows_outlined`/etc.)
purely so adjacent dynamic fields look visually distinct and the card
matches the rest of the app's visual language — not because the color
carries semantic meaning here. This is a deliberately different rule from
`RecordFieldColors` (which IS semantic) — document that distinction inline
in the code when this migration happens so a future reader doesn't assume
the rotation means something. This is the last migration to do, once the
widget API has already been proven out on Tier 1 screens.

## 5. Suggested order of work

1. DONE — Extend `AppRecordCard`/`AppRecordField` per §2, add
   `RecordFieldColors` per §3, plus a `leading` slot (added during
   migration for Clinical Queue's `PulseDot` indicator, not originally
   planned in §2 but a natural small extension of the same header Row).
   Verified `ot_report_viewer_screen.dart` (the one existing caller at the
   time) rendered identically before any screen opted into the new fields —
   confirmed a true no-op.
2. DONE — Migrated Tier 1, screens 1-4 (Counsellor, Share History, OT
   Appointment List, OT Assistant).
3. DONE — Migrated Tier 1, screen 5 (Clinical Queue's 3 cards) — the live
   countdown/`WaitPill` widgets were moved into the `actions` slot,
   unchanged internally. `_cardDeco()` and `_InfoChip` (both now fully
   unused after this) were deleted from clinical_queue_screen.dart rather
   than left as dead code.
4. DONE — Revisited `report_channel_screen.dart` itself: migrated its
   `_PatientCard` onto the shared `AppRecordCard`, and deleted its private
   `_FieldGrid`/`_FieldData`/`_FieldCell` classes (now fully superseded) —
   there is now exactly one implementation of this pattern in the codebase.
   One small, deliberate visual simplification: the subtitle's inline
   calendar icon before the date was dropped, since `AppRecordCard.subtitle`
   is a plain `String` (kept that way rather than adding a second
   `Widget`-typed subtitle slot just for one icon — not worth the API
   surface for a single caller).
5. NOT STARTED — Tier 1, screen 6 (`patients_screen.dart`'s
   `_PatientDetailSheet`) — lowest priority within Tier 1, see its note
   above for why.
6. DONE — Tier 4 (OT Reports dynamic columns) — `_buildList()` now assigns
   each field an icon/color by rotating through a small fixed palette
   (`_rotIcons`/`_rotColors`) keyed on column index, since these 16 report
   types have backend-defined arbitrary headings with no fixed semantic
   mapping the way "Phone"/"Doctor" have elsewhere. Explicitly NOT routed
   through `RecordFieldColors` — that table is semantic, this rotation is
   purely for visual distinction between adjacent columns.
7. NOT STARTED — Tier 2 header-alignment touch-ups — opportunistic, low
   priority, not required.
8. After each screen: `flutter analyze` — must stay at the current 0-error
   baseline (82 issues total, confirmed unchanged after every step above —
   all pre-existing info/warning-level lints unrelated to this work).

## 6. Non-goals (explicit, so scope doesn't creep)

- No changes to the tablet app.
- No unification of the 6 different model classes (`OtBookingSummary`,
  `Patient`, `OtAppointmentItem`, `QueuePatient`, `HistoryPatient`,
  `ReportPatient`) into one shared shape — each screen keeps its own model
  and just adapts it into `List<AppRecordField>` locally.
- No new generic widget files — everything lives in the existing
  `app_record_card.dart`.
- No forced migration of Tier 2/3 screens' action-button layouts.
- No change to any status-badge color helper (`paymentStatusColor`,
  `otStatusColor`) — those stay exactly as they are, used as header pills,
  not folded into `RecordFieldColors` or the field grid.
