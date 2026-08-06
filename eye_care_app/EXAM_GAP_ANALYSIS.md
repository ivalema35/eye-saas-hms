# Examination Screen — Master Fix Plan
> Step-by-step. Give me one step at a time and I will implement it fully before moving to the next.
> Web project: J:\laragon\www\eye-saas-hms
> Mobile app:  J:\all_folder_of_C_drive\eye_care_whole\eye_care_app

---

## HOW TO USE THIS DOCUMENT
- Each step is self-contained — you can give me "Step X karo" and I will implement it completely.
- Steps are ordered by priority. Steps 0 → 5 must be done before anything else (data integrity).
- From Step 6 onwards, order can be flexible.

---

## STEP 0 — Run Master Seeders on Backend Database
**Type:** Backend (run in terminal — NOT a code change)
**Why:** All dropdown pickers in the mobile app fall back to plain text fields when master tables are empty. This step seeds the data. Once done, all existing picker code instantly works.

Run in the Laravel backend terminal at `J:\laragon\www\eye-saas-hms`:
```bash
php artisan db:seed --class=MasterVnSeeder
php artisan db:seed --class=MasterPnvnSeeder
php artisan db:seed --class=MasterNrvnSeeder
php artisan db:seed --class=MasterSphCylSeeder
php artisan db:seed --class=MasterAxisSeeder
php artisan db:seed --class=MasterNctSeeder
php artisan db:seed --class=MasterSacSeeder
php artisan db:seed --class=MasterLidSeeder
php artisan db:seed --class=MasterConjSeeder
php artisan db:seed --class=MasterCorneaSeeder
php artisan db:seed --class=MasterAcSeeder
php artisan db:seed --class=MasterIrisSeeder
php artisan db:seed --class=MasterPupilSeeder
php artisan db:seed --class=MasterLensSeeder
php artisan db:seed --class=MasterEmSeeder
php artisan db:seed --class=MasterCoverTestSeeder
php artisan db:seed --class=MasterDiscSeeder
php artisan db:seed --class=MasterFrSeeder
php artisan db:seed --class=ChiefComplaintSeeder
php artisan db:seed --class=KcoSeeder
```
After running: cold-restart the Flutter app. All dropdown pickers should now show values.

**Files changed:** None (database only)

---

## STEP 1 — Fix ST Near Keys (nc / na) Not Sent in Payload
**Type:** Flutter — Data integrity bug
**Files:** `lib/screens/primary_exam_screen.dart`, `lib/screens/secondary_exam_screen.dart`

**Problem:** Web ST near section has CYL (`nc`) and Axis (`na`) columns that mirror the distance CYL and Axis values. Mobile never writes `nc` or `na` keys to the payload. When a web user opens a mobile-saved exam, the ST near CYL and Axis appear blank.

**What to fix:** In `_buildPayload()` inside the ST section, before saving, set:
- `st[re][nc] = st[re][dc]` (near CYL = distance CYL for RE)
- `st[re][na] = st[re][ax]` (near Axis = distance Axis for RE)
- Same for `le`

Also add these as read-only display cells in the ST near row UI so the doctor can see the mirrored values.

**Expected result:** ST near CYL and Axis are always sent and match the distance values.

---

## STEP 2 — Fix Medicine Duration: Send as Integer not String
**Type:** Flutter — Data type bug
**Files:** `lib/screens/primary_exam_screen.dart`, `lib/screens/secondary_exam_screen.dart`

**Problem:** Web expects `medicines[n].duration` as a number (integer days). Mobile's `_PrescRow.toJson()` sends `duration` as a string (e.g., `"7"` instead of `7`).

**What to fix:** In `_PrescRow.toJson()`, change:
```dart
'duration': duration.isEmpty ? null : duration,
```
to:
```dart
'duration': duration.isEmpty ? null : int.tryParse(duration),
```

Also change the duration `TextFormField` `keyboardType` to `TextInputType.number`.

**Expected result:** Duration is sent as an integer. Web displays the value correctly.

---

## STEP 3 — Fix K/C/O: Add Comment Field + Fix Default Unit
**Type:** Flutter — Missing UI field + wrong default
**Files:** `lib/screens/primary_exam_screen.dart`, `lib/screens/secondary_exam_screen.dart`

**Problem 1:** Web K/C/O table has a Comment column. The `_KcoRow` class has a `comment` field and `toJson()` includes it, but the `_kcoRow()` widget never renders a comment input. Doctors cannot enter K/C/O comments on mobile.

**Problem 2:** Web K/C/O duration defaults to "Years" (because known conditions are chronic). Mobile `_KcoRow` constructor defaults to `unit = 'Days'`.

**What to fix:**
1. In `_kcoRow()` widget, add a `TextFormField` for the comment field (below the main row, similar to how the C/O row shows a comment field).
2. Change `_KcoRow` constructor from `this.unit = 'Days'` to `this.unit = 'Years'`.

**Expected result:** Doctors can enter K/C/O comments. New K/C/O rows default to "Years" instead of "Days".

---

## STEP 4 — Add Dilate Section to Secondary Exam Screen
**Type:** Flutter — Missing feature on secondary screen
**Files:** `lib/screens/secondary_exam_screen.dart`

**Problem:** Web secondary exam has the same Dilate modal as primary — doctors can decide to re-dilate during secondary examination. The mobile secondary screen Tab 5 (Rx & Plan) has no Dilate section at all.

**Web fields for Dilate:**
- `exam_data[dilate]` = "Yes" or "No" (radio / toggle)
- `dilation_time` = integer minutes (top-level payload field, NOT inside exam_data) — only when "Yes"

**What to fix:** Add a Dilate section to the secondary screen's Rx & Plan tab (Tab 5), identical to the primary screen's Dilate section:
- Yes/No ChoiceChip toggle
- When "Yes" selected: show a numeric minutes input
- In `_buildPayload()`: include `dilate` in examData AND `dilation_time` at top level when "Yes"

State to add:
```dart
String _dilate = 'No';
final _dilationTimeCtrl = TextEditingController();
```

**Expected result:** Secondary exam can trigger re-dilation. The D/ND wait timer on the web secondary screen updates correctly.

---

## STEP 5 — Decide and Fix Orphaned Keys: special_advice / followup_date / followup_duration
**Type:** Decision + Flutter fix OR Web fix
**Files:** `lib/screens/primary_exam_screen.dart`, `lib/screens/secondary_exam_screen.dart` OR web views

**Problem:** Mobile sends three keys that the web does NOT have anywhere:
- `exam_data.special_advice` → web has no field for this
- `exam_data.followup_date` → web has no field for this  
- `exam_data.followup_duration` → web has no field for this

These are stored in the database but never displayed on web. This creates a split — mobile doctors enter data that web users can never see.

**Two options:**
- **Option A (recommended):** Add these three fields to the web secondary exam view (after Advice section). They are clinically useful.
- **Option B:** Remove them from mobile and merge into the main `advice` text field.

**What to fix (Option A — web):** Add three fields to `resources/views/hospital/exam/secondary.blade.php` in the Advice modal — a "Follow-up Date" date input, a "Follow-up Duration" text input, and a "Special Advice" textarea. Store as `exam_data[special_advice]`, `exam_data[followup_date]`, `exam_data[followup_duration]`.

**What to fix (Option B — mobile):** Remove `_specialAdviceCtrl`, `_followupDate`, `_followupDurationCtrl` from both screens. Remove those fields from `_buildPayload()`. Remove them from the Tab 5 UI.

---

## STEP 6 — Fix Tab Names to Match Web Step Names
**Type:** Flutter — UI labels
**Files:** `lib/screens/primary_exam_screen.dart`, `lib/screens/secondary_exam_screen.dart`

**Problem:** Mobile tab names don't match web step names. Doctors familiar with the web get confused.

**Current mobile tabs → What they should be:**

| Current Tab Name | New Tab Name | Reason |
|---|---|---|
| H & C/O | C/O & H/O | Web order: C/O first, H/O second |
| Vision | Vision & NCT | NCT is a separate step on web |
| Refraction | PG & ST | These are the exact web step names |
| Findings | O/E & Fundus | Web calls them O/E and Fundus |
| Rx & Plan | Rx & Plan | OK — keep same |

**What to fix:** Change the 5 tab labels in both `TabBar` widgets.

**Expected result:** Tab names match the web exactly. Doctors immediately know where each section is.

---

## STEP 7 — Fix ST Section UI: Show NEAR as Dist Mirror + ADD→NS Auto-Calc
**Type:** Flutter — UI enhancement + clinical accuracy
**Files:** `lib/screens/primary_exam_screen.dart`, `lib/screens/secondary_exam_screen.dart`

**Problem 1:** Web ST near section shows CYL and Axis as read-only cells labelled "= Dist" mirroring the distance values. Mobile near section only shows ADD and Near SPH inputs — the mirrored CYL and Axis are invisible.

**Problem 2:** Web auto-calculates Near SPH = Distance SPH + ADD when the doctor enters ADD. Mobile has ADD and Near SPH as two completely independent fields.

**What to fix:**
1. In `_stNearSection()`, add two read-only display cells for NC (= DC) and NA (= AX) per eye, visually showing "= Dist" in grey text.
2. Add `onChanged` listeners to both ADD fields and DIST SPH fields: when both have values, auto-set the Near SPH field using `_stNsRe.text = (double.tryParse(ds) + double.tryParse(add)).toStringAsFixed(2)`.

**Expected result:** ST near section looks like web. Near SPH auto-fills when doctor enters ADD.

---

## STEP 8 — Add Pseudophakia Sub-Data for Lens OE Field
**Type:** Flutter — Missing clinical feature
**Files:** `lib/screens/primary_exam_screen.dart`, `lib/screens/secondary_exam_screen.dart`

**Problem:** When the web doctor selects "Pseudophakia" from the Lens OE master, a secondary popup opens with 3 extra fields:
- Operation Type: Block or Phaco (button group)
- Operation Expense: text
- Hospital Name: text (with autocomplete from referrers)

These are stored as `oe.pseudophakia_re.operation_type`, `oe.pseudophakia_re.operation_expense`, `oe.pseudophakia_re.hospital_name` (same for `_le`).

Mobile never shows this popup. This is significant surgical history data that is silently lost.

**What to fix:** In `_masterVisionCell()`, after the user selects a value, check:
```dart
if (picked.toLowerCase().contains('pseudo')) {
  // Show a second bottom-sheet/dialog asking for the 3 sub-fields
}
```
Add state variables for the pseudophakia sub-data:
```dart
Map<String, Map<String, String>> _pseudophakia = {'re': {}, 'le': {}};
```
In `_buildPayload()` OE section, include `pseudophakia_re` and `pseudophakia_le` maps when non-empty.
In `_prefill()`, parse `oe['pseudophakia_re']` back into state.

**Expected result:** Selecting "Pseudophakia" from Lens master opens a sub-form for surgical details. Data is saved and prefilled correctly.

---

## STEP 9 — Add Quick-Add Advice Pills from Master
**Type:** Flutter — Clinical efficiency feature
**Files:** `lib/screens/primary_exam_screen.dart`, `lib/screens/secondary_exam_screen.dart`

**Problem:** Web Advice modal shows favourite advice items as clickable pills. Doctors click a pill and the advice text is appended to the textarea. On mobile, doctors must type everything manually.

**What to add to ExamMastersService:** Add `advices` master list (API type: `advices`). Add to `ExamMastersData` and `fetchAll()`.

**What to fix in exam screens:**
- In the Advice section of Tab 5, add a row of favourite advice pills above the `_adviceCtrl` textarea.
- Tapping a pill appends its text to `_adviceCtrl.text` (with a newline separator if not empty).
- A "More..." button opens a searchable bottom-sheet of all advice master items.

**Expected result:** Doctors can add standard advice in one tap instead of typing. Matches web UX.

---

## STEP 10 — Add IOP Reference Badges Below NCT Fields
**Type:** Flutter — Informational UI
**Files:** `lib/screens/primary_exam_screen.dart`, `lib/screens/secondary_exam_screen.dart`

**Problem:** Web shows 3 reference badges below the NCT table:
- 🟢 Normal: 10–21 mmHg
- 🟡 Borderline: 22–24 mmHg
- 🔴 High: ≥25 mmHg

Mobile shows only the two NCT input fields with no reference.

**What to fix:** Add 3 small `Chip` or `Container` widgets below the NCT grid in the Vision tab, showing the three ranges with appropriate colors. These are static — no logic needed, just display.

Also: if the entered IOP value is numeric, highlight the corresponding badge (the one whose range the value falls into).

**Expected result:** Doctors can see at a glance whether the entered IOP is normal, borderline, or high.

---

## STEP 11 — Add Save Exam Confirmation Dialog
**Type:** Flutter — UX safety
**Files:** `lib/screens/primary_exam_screen.dart`, `lib/screens/secondary_exam_screen.dart`

**Problem:** Web shows a "Save Exam? Are you sure?" confirmation dialog before submitting. Mobile saves immediately on FAB tap with no confirmation — accidental taps can save incomplete exams.

**What to fix:** In the FAB `onPressed` handler, before calling `_save()`, show an `AlertDialog`:
```
Title: "Save Examination"
Content: "Are you sure you want to save this examination for [patient name]?"
Actions: [Cancel] [Save]
```
Only call `_save()` if user taps "Save".

**Expected result:** Doctors get one confirmation step before data is submitted.

---

## STEP 12 — Add Dilation Wait Timer to Secondary Screen
**Type:** Flutter — Clinical display
**Files:** `lib/screens/secondary_exam_screen.dart`

**Problem:** Web secondary screen shows a "D" (dilated) or "ND" (non-dilated) pill in the patient info bar, showing how long the patient has been waiting since the primary exam was saved. Mobile secondary screen shows no wait time.

**What to add:**
- From the patient data, use `widget.patient.primaryExamination?.updatedAt` to compute elapsed time since primary exam.
- Show a small colored pill: if `dilate == 'Yes'` → "D: X min" (amber), else → "ND: X min" (blue).
- Update the timer every minute using a `Timer.periodic`.

State to add:
```dart
Timer? _waitTimer;
int _waitMinutes = 0;
```

**Expected result:** Secondary screen shows how long the patient has been waiting post-primary exam, matching web behavior.

---

## STEP 13 — Diagnosis-to-Medicine Group Suggestions
**Type:** Flutter — Clinical efficiency (advanced)
**Files:** `lib/screens/primary_exam_screen.dart`, `lib/screens/secondary_exam_screen.dart`

**Problem:** Web: when diagnoses are selected, the Medicine modal shows a "Suggested Groups" panel with medicine groups linked to those diagnoses. Tapping a group bulk-adds all its medicines to the Rx table. Mobile has no such feature.

**What this requires:**
1. New API call: `GET /{slug}/medicine-groups?diagnosis_id={id}` → returns groups linked to a diagnosis
2. In `_buildPlanTab()` Tab 5: after the Diagnosis section, show "Suggested Medicine Groups" chips based on `_selectedDiagnosisIds`
3. Tapping a group chip → fetch group's medicines → bulk-add to `_medicines` list

**Files also affected:** `lib/services/exam_service.dart` — add `fetchMedicineGroupsForDiagnosis(int diagnosisId)` method.

**Expected result:** Selecting diagnoses auto-suggests medicine groups. Doctors can add standard treatment protocols in one tap.

---

## STEP 14 — Diagnosis-to-Advice Suggestions
**Type:** Flutter — Clinical efficiency (advanced)
**Files:** `lib/screens/primary_exam_screen.dart`, `lib/screens/secondary_exam_screen.dart`

**Problem:** Web: when diagnoses are selected, the Advice modal shows suggested advice items linked to those diagnoses. Clicking a suggestion appends it to the advice textarea.

**What this requires:**
1. Each `MasterDiagnosis` on the web has linked advice items. Need to check the backend relationship.
2. When `_selectedDiagnosisIds` changes, fetch linked advice texts.
3. Show them as suggestion chips above the advice textarea in Tab 5.
4. Tapping a chip appends the advice text to `_adviceCtrl`.

**Expected result:** Selecting diagnoses shows standard advice suggestions. One-tap to add.

---

## STEP 15 — Polish: Advice Character Counter + Max Length
**Type:** Flutter — UX polish
**Files:** `lib/screens/primary_exam_screen.dart`, `lib/screens/secondary_exam_screen.dart`

**What to fix:** On the advice `TextFormField`, add:
```dart
maxLength: 2000,
maxLengthEnforcement: MaxLengthEnforcement.enforced,
```
This shows a "0/2000" counter below the field, matching the web.

---

## STEP 16 — Polish: VN Column Labels (VN C GL / VN C ST)
**Type:** Flutter — Label accuracy
**Files:** `lib/screens/primary_exam_screen.dart`, `lib/screens/secondary_exam_screen.dart`

**What to fix:** In the PG table header, the VN column should be labeled "VN C GL" (vision corrected with glasses). In the ST table header, it should be "VN C ST" (vision corrected with subjective test). Currently both show just "VN".

---

## STEP 17 — Polish: Diagnosis Chip List/Set Inconsistency
**Type:** Flutter — Code consistency
**Files:** `lib/screens/primary_exam_screen.dart`, `lib/screens/secondary_exam_screen.dart`

**Problem:** Primary screen uses `List<int> _selectedDiagnosisIds`. Secondary screen uses `Set<int> _selectedDiagnosisIds`. Should be the same type in both.

**What to fix:** Change secondary screen to use `List<int>` (same as primary). Update toggle logic to use `contains/add/remove` instead of Set operations. Or change both to `Set<int>` — either is fine but must be consistent.

---

## QUICK REFERENCE — Step Priority

| Step | Priority | Type | Time estimate |
|---|---|---|---|
| Step 0 | P0 — Do first | Backend DB | 5 min |
| Step 1 | P1 — Data bug | Flutter | 30 min |
| Step 2 | P1 — Data bug | Flutter | 15 min |
| Step 3 | P1 — Missing field | Flutter | 30 min |
| Step 4 | P1 — Missing feature | Flutter | 45 min |
| Step 5 | P1 — Decision needed | Flutter + Web | 1 hr |
| Step 6 | P2 — UI labels | Flutter | 15 min |
| Step 7 | P2 — Clinical accuracy | Flutter | 45 min |
| Step 8 | P2 — Missing clinical data | Flutter | 1 hr |
| Step 9 | P2 — Efficiency | Flutter | 45 min |
| Step 10 | P3 — Informational | Flutter | 20 min |
| Step 11 | P3 — UX safety | Flutter | 20 min |
| Step 12 | P3 — Clinical display | Flutter | 30 min |
| Step 13 | P3 — Advanced feature | Flutter | 2 hr |
| Step 14 | P3 — Advanced feature | Flutter | 1 hr |
| Step 15 | P4 — Polish | Flutter | 5 min |
| Step 16 | P4 — Polish | Flutter | 5 min |
| Step 17 | P4 — Polish | Flutter | 15 min |

---

## WEB vs MOBILE — TAB/STEP MAPPING (Reference)

| # | Web Step | Web Group | Mobile Tab | Gap |
|---|---|---|---|---|
| 1 | C/O | PRIMARY | C/O & H/O (Tab 1) | K/C/O comment missing in UI (Step 3) |
| 2 | K/C/O & H/O | PRIMARY | C/O & H/O (Tab 1) | Comment field not rendered (Step 3) |
| 3 | Vision | PRIMARY | Vision & NCT (Tab 2) | None — pickers ready once Step 0 done |
| 4 | PG | PRIMARY | PG & ST (Tab 3) | None — pickers ready once Step 0 done |
| 5 | ST | PRIMARY | PG & ST (Tab 3) | nc/na missing (Step 1) |
| 6 | NCT | PRIMARY | Vision & NCT (Tab 2) | IOP hint badges missing (Step 10) |
| 7 | O/E | PRIMARY | O/E & Fundus (Tab 4) | Pseudophakia sub-data (Step 8) |
| 8 | Fundus | PRIMARY | O/E & Fundus (Tab 4) | None |
| 9 | Dilate | PRIMARY | Rx & Plan (Tab 5) | Missing on secondary (Step 4) |
| 10 | Diagnosis | SECONDARY | Rx & Plan (Tab 5) | List/Set inconsistency (Step 17) |
| 11 | Medicine | SECONDARY | Rx & Plan (Tab 5) | Duration type bug (Step 2), no groups (Step 13) |
| 12 | Advice | SECONDARY | Rx & Plan (Tab 5) | Orphaned keys (Step 5), no pills (Step 9) |

---

## KEY API ENDPOINTS (Reference)

| Endpoint | Used for |
|---|---|
| `GET /masters/detail/vn` | Vision VN distance picker |
| `GET /masters/detail/pnvn` | Pinhole VN picker |
| `GET /masters/detail/nrvn` | Near VN picker |
| `GET /masters/detail/nct` | NCT IOP picker |
| `GET /masters/detail/sph_cyl` | PG/ST SPH and CYL picker |
| `GET /masters/detail/axis` | PG/ST Axis picker |
| `GET /masters/detail/sac` | OE SAC picker |
| `GET /masters/detail/lid` | OE Lid picker |
| `GET /masters/detail/conj` | OE Conjunctiva picker |
| `GET /masters/detail/cornea` | OE Cornea picker |
| `GET /masters/detail/ac` | OE Ant. Chamber picker |
| `GET /masters/detail/iris` | OE Iris picker |
| `GET /masters/detail/pupil` | OE Pupil picker |
| `GET /masters/detail/lens` | OE Lens picker |
| `GET /masters/detail/em` | OE Ext. Mov. picker |
| `GET /masters/detail/covertest` | OE Cover Test picker |
| `GET /masters/detail/disc` | Fundus Disc picker |
| `GET /masters/detail/fr` | Fundus FR picker |
| `GET /masters/detail/chief-complaints` | C/O complaint autocomplete |
| `GET /masters/detail/kcos` | K/CO autocomplete |
| `GET /masters/detail/hno` | H/O history chips |
| `GET /masters/detail/diagnoses` | Diagnosis multi-select |
| `GET /masters/detail/advices` | Advice quick-add pills (Step 9) |
