# Examination Screen Fix Phases
> Based on deep web project analysis. Fix phases one by one.

---

## Root Cause Summary

The web loads ~20 master lists (dropdown data) when the exam screen opens.
The mobile loads ZERO exam masters — only dosages and routes.
Every "dropdown" on the web is a plain text field on mobile.

**No backend changes needed for any phase** — all API endpoints already exist.

---

## All API Endpoints Used by Exam Forms

Base URL: `GET /api/v1/{slug}/masters/detail/{type}`
Response: `{ "success": true, "data": [{ "id": 1, "value": "6/6", "is_favourite": true }, ...] }`

| Type key | Used where |
|---|---|
| `vn` | Vision VN (dist), PG/ST VN dist column |
| `pnvn` | Vision Pinhole VN |
| `nrvn` | Vision Near VN, PG/ST near VN column |
| `sph_cyl` | PG/ST SPH and CYL columns (signed values like −2.50, +1.75, Plano) |
| `axis` | PG/ST AXIS column |
| `nct` | NCT/IOP field (grid picker) |
| `disc` | Fundus Disc / CDR |
| `fr` | Fundus Foveal Reflex |
| `sac` | OE → SAC |
| `lid` | OE → Lid |
| `conj` | OE → Conjunctiva |
| `cornea` | OE → Cornea |
| `ac` | OE → Ant. Chamber |
| `iris` | OE → Iris |
| `pupil` | OE → Pupil |
| `lens` | OE → Lens |
| `em` | OE → Ext. Mov. |
| `covertest` | OE → Cover Test |
| `chief-complaints` | C/O complaint autocomplete |
| `kcos` | K/CO condition autocomplete |
| `hno` | H/O history chips |
| `diagnoses` | Diagnosis multi-select (stores int IDs) |

Medicine endpoints (already partially working):
- `GET /{slug}/medicines?search={q}&per_page=20` → medicine search
- `GET /{slug}/medicine-dosages` → dosage dropdown ✅ already done
- `GET /{slug}/medicine-routes` → route dropdown ✅ already done

---

## Phase 1 — Create ExamMastersService

**Files to create/edit:**
- NEW: `lib/services/exam_masters_service.dart`
- EDIT: `lib/services/exam_service.dart` — use ExamMastersService inside `loadFormData()`

**What exactly:**
- `ExamMasterItem` model: `{ int id, String value, bool isFavourite }`
- `ExamMastersData` class holding all lists
- `ExamMastersService.fetchAll()` — calls all ~22 master endpoints in one `Future.wait()`
- In-memory cache so re-opening exam screen is instant
- Extend `ExamFormData` to include all master lists OR create separate `ExamMastersData`

**Why first:** Every other phase depends on having master data available.

---

## Phase 2 — Fix Diagnosis Schema (CRITICAL DATA BUG)

**Files to edit:**
- `lib/screens/primary_exam_screen.dart`
- `lib/screens/secondary_exam_screen.dart`

**What exactly:**
- Change `List<TextEditingController> _diagnoses` → `List<int> _selectedDiagnosisIds`
- Load diagnosis master list from Phase 1 service
- Show multi-select chips (diagnosis name, tap to toggle selected/unselected)
- In `_buildPayload()`: emit `'diagnoses': _selectedDiagnosisIds` (list of ints)
- In `_prefill()`: parse `ed['diagnoses']` as `List<int>` not `List<String>`
- Add search/filter bar above chip list

**Why critical:** Web stores diagnosis IDs (integers). Mobile stores text strings. This corrupts the database for any patient examined on both platforms.

---

## Phase 3 — Vision Tab Dropdowns (VN, Pinhole, Near VN, NCT IOP)

**Files to edit:**
- `lib/screens/primary_exam_screen.dart` → `_buildVisionTab()`, `_visionEyeRow()`
- `lib/screens/secondary_exam_screen.dart` → same methods

**What exactly:**

**Visual Acuity table (vn_re, vn_le, pnvn_re, pnvn_le, nrvn_re, nrvn_le):**
- Replace bare `_visionCell(ctrl)` with a tappable field
- Tapping opens a bottom-sheet list showing master values (favourites at top with ★)
- User can also type manually (free text fallback)
- Stored as plain string (matches web)

**NCT/IOP fields (iop_re, iop_le):**
- Replace bare `_examField` with tappable field
- Tapping opens a grid bottom-sheet of NCT master values (5-column grid of numbers)
- Tap selects value; shows "— mmHg" suffix when selected

---

## Phase 4 — Findings Tab: OE Dropdowns (All 10 Fields)

**Files to edit:**
- `lib/screens/primary_exam_screen.dart` → `_oeTable()`
- `lib/screens/secondary_exam_screen.dart` → `_oeTable()`

**What exactly:**
- For each OE field (`sac`, `lid`, `conj`, `cornea`, `ac`, `iris`, `pupil`, `lens`, `em`, `covertest`): replace `_visionCell(ctrl)` with tappable field → bottom-sheet picker from the field's master list
- `other` field: remains plain `TextFormField` (no master — free text only)
- Lens field: after selecting a value, optionally show sub-fields for Pseudophakia (operation type: Block/Phaco, expense, hospital name) — this is secondary priority; the basic dropdown is Phase 4, pseudophakia detail is bonus

**Master key per field:** sac→`sac`, lid→`lid`, conj→`conj`, cornea→`cornea`, ac→`ac`, iris→`iris`, pupil→`pupil`, lens→`lens`, em→`em`, covertest→`covertest`

---

## Phase 5 — Findings Tab: Fundus Dropdowns (Disc and FR)

**Files to edit:**
- `lib/screens/primary_exam_screen.dart` → `_fundusTable()`
- `lib/screens/secondary_exam_screen.dart` → `_fundusTable()`

**What exactly:**
- `disc_re` and `disc_le`: tappable field → bottom-sheet picker from `disc` master
- `fr_re` and `fr_le`: tappable field → bottom-sheet picker from `fr` master
- `comment_re` and `comment_le`: remain plain multiline TextFormField (no master)

---

## Phase 6 — Refraction Tab: PG/ST SPH, CYL, AXIS, VN Pickers

**Files to edit:**
- `lib/screens/primary_exam_screen.dart` → `_pgTable()`, `_stDistTable()`
- `lib/screens/secondary_exam_screen.dart` → same

**What exactly:**

**SPH and CYL columns (for both PG and ST):**
- Tappable cell → opens bottom sheet with:
  - Sign toggle at top: `−` (negative) | `Plano` | `+` (positive) buttons
  - Grid/list of `sph_cyl` master values
  - Tapping applies sign + value (e.g., tap `−` then `2.50` → stores `"-2.50"`)
- Stored as signed string like `"-2.50"`, `"+1.75"`, `"Plano"`

**AXIS column:**
- Tappable → bottom-sheet list from `axis` master (0–180 degrees)
- Stored as string like `"90"`, `"180"`

**VN column in PG (distance):**
- Tappable → picker from `vn` master (same list as Vision tab distance VN)

**VN column in PG (near / near_vn key):**
- Tappable → picker from `nrvn` master

**ST distance VN:** same as PG distance VN
**ADD in ST near section:** tappable → picker from `sph_cyl` master (with + sign, since ADD is always positive)

---

## Phase 7 — H & C/O Tab: Complaint and K/CO Autocomplete

**Files to edit:**
- `lib/screens/primary_exam_screen.dart` → `_coRow()`, `_kcoRow()`
- `lib/screens/secondary_exam_screen.dart` → same

**What exactly:**

**Complaint field in C/O rows:**
- Replace plain `_miniField` with `_MasterSearchField` (similar to existing `_MedicineSearchField`)
- Filters from `chief-complaints` master as user types
- Shows favourites at top (those with `isFavourite: true`)
- Tap selects value; user can also type custom value not in master
- Still stores as plain string in `co_rows[].complaint`

**Condition field in K/CO rows:**
- Same pattern, uses `kcos` master

---

## Phase 8 — H & C/O Tab: History Chips

**Files to edit:**
- `lib/screens/primary_exam_screen.dart` → `_buildComplaintsTab()` history section
- `lib/screens/secondary_exam_screen.dart` → same

**What exactly:**
- Replace single `_historyCtrl` multiline field with:
  - Search field that filters `hno` master items as you type
  - Selected items show as dismissible chips above the search field
  - Favourites shown as quick-tap pills (no typing needed)
- Internal state: `List<String> _historyChips`
- `_buildPayload()`: joins chips as `_historyChips.join(', ')` → stored in `exam_data.history`
- `_prefill()`: splits `ed['history']` by `, ` back into chips

---

## Phase 9 — Rx & Plan Tab: Show Secondary Exam Badge + Diagnoses Multi-Select Polish

**Files to edit:**
- `lib/screens/secondary_exam_screen.dart` — `_buildPlanTab()` header

**What exactly:**
- Add a visible "SECONDARY EXAMINATION" label/badge at the top of the Rx & Plan tab content (so doctors clearly know they're saving secondary exam data)
- Show a read-only info row: patient name, primary exam done timestamp (if available from `widget.patient.primaryExamination`)
- Polish the diagnosis multi-select from Phase 2: add a visible search bar, show selected count badge

---

## Phase 10 — Performance: Batch Master Loading + Caching

**Files to edit:**
- `lib/services/exam_masters_service.dart` (from Phase 1)
- `lib/screens/primary_exam_screen.dart` → `_loadAll()`
- `lib/screens/secondary_exam_screen.dart` → `_loadAll()`

**What exactly:**
- Add TTL-based cache in `ExamMastersService` (cache for 30 min — masters rarely change)
- On second open of any exam screen, master data loads from cache (no network call)
- Loading spinner only shows for uncached data
- Add error handling: if one master fails, show partial data (don't fail the whole screen)

---

## Quick Reference: What Each Phase Fixes

| Phase | Tab affected | Fields fixed |
|---|---|---|
| 1 | All | Creates master data service (prerequisite) |
| 2 | Rx & Plan | Diagnosis schema (IDs not text) |
| 3 | Vision | VN, Pinhole VN, Near VN, NCT IOP |
| 4 | Findings | All 10 OE fields |
| 5 | Findings | Fundus Disc, Fundus FR |
| 6 | Refraction | PG & ST: SPH, CYL, AXIS, VN, ADD |
| 7 | H & C/O | Complaint autocomplete, K/CO autocomplete |
| 8 | H & C/O | History chip input |
| 9 | Rx & Plan | Secondary exam label, diagnosis UX polish |
| 10 | All | Caching / performance |








1. C/O (Chief Complaints)
Form field: exam_data[co_rows] — array of row objects.

Per row fields:

Field name	Type	Submitted as	Notes
complaint	text input	exam_data[co_rows][N][complaint]	Free-text, searchable from chief_complaints master table. max 255.
since	dropdown (select)	exam_data[co_rows][N][since]	Options: "" (blank, displayed as -), then 1 through 10 (integers as string).
unit	dropdown (select)	exam_data[co_rows][N][unit]	Options: Days, Weeks, Months, Years, Longtime. Default on new row: Days.
eye	dropdown (select)	exam_data[co_rows][N][eye]	Options: "" (label -), RE (label "Right"), LE (label "Left"), Both (label "Both"), OU (label "OU").
comment	text input	exam_data[co_rows][N][comment]	Free-text. max 500.
Validation (from StorePrimaryExamRequest):

unit must be in:Days,Weeks,Months,Years,Longtime
eye must be in:RE,LE,Both,OU, (empty string also valid)
complaint, since, comment are nullable strings
There is also a legacy field exam_data[complaint_duration] (nullable string, max 100) — appears in PHP regex parsing (/^(\d+)\s*(Days?|Weeks?|Months?|Years?)$/i) but is NOT rendered as a form field in the current UI (it was a previous single-field approach).

Stored JSON structure:


"co_rows": [
  {"complaint": "Blurred Vision", "since": "3", "unit": "Months", "eye": "RE", "comment": ""},
  {"complaint": "Watering", "since": "1", "unit": "Weeks", "eye": "Both", "comment": "Excessive"}
]
2. K/C/O & H/O (Known Conditions / History of)
K/C/O form field: exam_data[kco_rows] — array of row objects.

K/C/O per row fields:

Field name	Type	Submitted as	Notes
condition	text input	exam_data[kco_rows][N][condition]	Free-text from kcos master table. max 255.
since	dropdown	exam_data[kco_rows][N][since]	Same as C/O: "" or 1–10.
unit	dropdown	exam_data[kco_rows][N][unit]	Days, Weeks, Months, Years, Longtime. Default: Years (note: different default from C/O which defaults to Days).
comment	text input	exam_data[kco_rows][N][comment]	Free-text. max 500.
Note: K/C/O does NOT have an eye field (unlike C/O).

H/O form field: exam_data[history] — single hidden input, value is comma-separated string.

H/O items are displayed as removable "chip" badges. User searches from tbl_master_hno master table and adds items one by one; they are joined by commas into a single string. max 2000.

Stored JSON structure:


"kco_rows": [
  {"condition": "Diabetes", "since": "5", "unit": "Years", "comment": "On insulin"},
  {"condition": "Hypertension", "since": "2", "unit": "Years", "comment": ""}
],
"history": "Previous cataract surgery, Glaucoma"
3. Vision (Visual Acuity)
Form field: exam_data[vision] — flat key-value object.

Six fields, 3 per eye:

Key	Label	Full Name	Master table
vn_re	VN (RE)	Distance Vision — Right Eye	tbl_master_vn
vn_le	VN (LE)	Distance Vision — Left Eye	tbl_master_vn
pnvn_re	PnVn (RE)	Pinhole — Right Eye	tbl_master_pnvn
pnvn_le	PnVn (LE)	Pinhole — Left Eye	tbl_master_pnvn
nrvn_re	NrVn (RE)	Near Vision — Right Eye	tbl_master_nrvn
nrvn_le	NrVn (LE)	Near Vision — Left Eye	tbl_master_nrvn
Each field is a text input with a chevron-down indicating a dropdown. Values come from master tables (tbl_master_vn, tbl_master_pnvn, tbl_master_nrvn). User can also type freely.

Stored JSON structure:


"vision": {
  "vn_re": "6/6",
  "vn_le": "6/18",
  "pnvn_re": "6/6",
  "pnvn_le": "6/9",
  "nrvn_re": "N6",
  "nrvn_le": "N8"
}
4. PG (Prescription Glass)
Form field: exam_data[pg] — nested: [eye][field].

Structure: 2 eyes × 2 rows × 4 columns

Row label	SPH key	CYL key	Axis key	VN key	VN master
DISTANCE	ds	dc	ax	vn	tbl_master_vn
NEAR	ns	nc	na	near_vn	tbl_master_nrvn
Eyes: re and le.

Full field paths submitted:

exam_data[pg][re][ds], exam_data[pg][re][dc], exam_data[pg][re][ax], exam_data[pg][re][vn]
exam_data[pg][re][ns], exam_data[pg][re][nc], exam_data[pg][re][na], exam_data[pg][re][near_vn]
Same pattern for le.
UI behaviour:

SPH and CYL fields have - (red) and + (green) toggle buttons. They open a picker modal (modalPGPicker) with a grid of values from tbl_master_sph_cyl. Values can also be typed as custom with step 0.25.
Axis field is a text input with dropdown from tbl_master_axis. Values stored without +/- prefix.
VN C GL field (column header) selects from vn or nrvn master depending on row.
Column header reads "VN C GL" (Vision with Corrective Glass).
Stored JSON structure:


"pg": {
  "re": {"ds": "+2.00", "dc": "-0.50", "ax": "90", "vn": "6/6", "ns": "+2.50", "nc": "-0.50", "na": "90", "near_vn": "N6"},
  "le": {"ds": "+1.75", "dc": "0.00", "ax": "0", "vn": "6/9", "ns": "+2.25", "nc": "0.00", "na": "0", "near_vn": "N8"}
}
4. PG (Prescription Glass)
Form field: exam_data[pg] — nested: [eye][field].

Structure: 2 eyes × 2 rows × 4 columns

Row label	SPH key	CYL key	Axis key	VN key	VN master
DISTANCE	ds	dc	ax	vn	tbl_master_vn
NEAR	ns	nc	na	near_vn	tbl_master_nrvn
Eyes: re and le.

Full field paths submitted:

exam_data[pg][re][ds], exam_data[pg][re][dc], exam_data[pg][re][ax], exam_data[pg][re][vn]
exam_data[pg][re][ns], exam_data[pg][re][nc], exam_data[pg][re][na], exam_data[pg][re][near_vn]
Same pattern for le.
UI behaviour:

SPH and CYL fields have - (red) and + (green) toggle buttons. They open a picker modal (modalPGPicker) with a grid of values from tbl_master_sph_cyl. Values can also be typed as custom with step 0.25.
Axis field is a text input with dropdown from tbl_master_axis. Values stored without +/- prefix.
VN C GL field (column header) selects from vn or nrvn master depending on row.
Column header reads "VN C GL" (Vision with Corrective Glass).
Stored JSON structure:


"pg": {
  "re": {"ds": "+2.00", "dc": "-0.50", "ax": "90", "vn": "6/6", "ns": "+2.50", "nc": "-0.50", "na": "90", "near_vn": "N6"},
  "le": {"ds": "+1.75", "dc": "0.00", "ax": "0", "vn": "6/9", "ns": "+2.25", "nc": "0.00", "na": "0", "near_vn": "N8"}
}
5. ST (Subjective Test)
Form field: exam_data[st] — same structure as PG with extra fields.

Structure: 2 eyes × 2 rows × 4 columns

Row	SPH key	CYL key	Axis key	VN key
DISTANCE	ds	dc	ax	vn
NEAR	ns (computed), add	nc (mirrors distance)	na (mirrors distance)	— (no VN for near row)
Key difference from PG:

NEAR row SPH is actually the ADD value. The add field is submitted separately (exam_data[st][re][add]) and ns is the computed near SPH. The display shows "ADD: [value]" below the near SPH field.
NEAR row CYL and Axis are read-only mirrors of the DISTANCE row values (not independently editable).
NEAR row has NO VN column (shows —).
Column header reads "VN C ST" (Vision with Corrective for Subjective Test).
Checkbox fields (flat under exam_data[st]):

Key	Label	Type
bifocal	Bifocal	checkbox, value 1
nd_separate	Near & Distance Separate	checkbox, value 1
progressive	Progressive	checkbox, value 1
computer_uses	Computer Uses	checkbox, value 1
Full field paths submitted:

exam_data[st][re][ds], exam_data[st][re][dc], exam_data[st][re][ax], exam_data[st][re][vn]
exam_data[st][re][add], exam_data[st][re][ns], exam_data[st][re][nc], exam_data[st][re][na]
exam_data[st][bifocal], exam_data[st][nd_separate], exam_data[st][progressive], exam_data[st][computer_uses]
Same eye pattern for le.
Stored JSON structure:


"st": {
  "re": {"ds": "-1.50", "dc": "-0.25", "ax": "180", "vn": "6/6", "add": "+2.00", "ns": "+0.50", "nc": "-0.25", "na": "180"},
  "le": {"ds": "-1.00", "dc": "0.00", "ax": "0", "vn": "6/9", "add": "+2.00", "ns": "+1.00", "nc": "0.00", "na": "0"},
  "bifocal": "1",
  "nd_separate": null,
  "progressive": null,
  "computer_uses": null
}
6. NCT (Non-Contact Tonometry)
Form field: exam_data[nct] — flat key-value.

Fields:

Key	Label	Unit	Master table
iop_re	IOP — Right Eye	mmHg	tbl_master_nct
iop_le	IOP — Left Eye	mmHg	tbl_master_nct
Each is a text input with a dropdown from tbl_master_nct. The UI shows indicator colours: green = 10–21 mmHg (Normal), amber = 22–24 mmHg (Borderline), red = ≥25 mmHg (High).

Stored JSON structure:


"nct": {
  "iop_re": "14",
  "iop_le": "16"
}
7. O/E (On Examination)
Form field: exam_data[oe] — flat key-value, with pattern {rowkey}_{eye}.

10 examination row fields + 1 free-text "OTHER" row, per eye:

Row key	Label	Full Name	Master table
sac	SAC	Sac	tbl_master_sac
lid	LID	Lid	tbl_master_lid
conj	CONJ	Conjunctiva	tbl_master_conj
cornea	CORNEA	Cornea	tbl_master_cornea
ac	AC	Anterior Chamber	tbl_master_ac
iris	IRIS	Iris	tbl_master_iris
pupil	PUPIL	Pupil	tbl_master_pupil
lens	LENS	Lens	tbl_master_lens
em	EM	Extraocular Mov.	tbl_master_em
covertest	COVERTEST	Cover Test	tbl_master_covertest
For each row and each eye, the submitted key is exam_data[oe][{rowkey}_{eye}] (e.g., exam_data[oe][cornea_re], exam_data[oe][lens_le]).

Additionally, an OTHER free-text row:

exam_data[oe][other_re] — plain text input, "Right eye findings..."
exam_data[oe][other_le] — plain text input, "Left eye findings..."
Special Pseudophakia sub-fields (only for LENS row):

When the LENS field value contains "Pseudophakia" (JS-detected), a secondary modal prompts for:

exam_data[oe][pseudophakia_re][operation_type] — "Block" or "Phaco" (two buttons)
exam_data[oe][pseudophakia_re][operation_expense] — text, amount
exam_data[oe][pseudophakia_re][hospital_name] — text, from tbl_referrers datalist
Same 3 fields for le.
Stored JSON structure:


"oe": {
  "sac_re": "NAD", "sac_le": "NAD",
  "lid_re": "Normal", "lid_le": "Normal",
  "conj_re": "Clear", "conj_le": "Congested",
  "cornea_re": "Clear", "cornea_le": "Hazy",
  "ac_re": "Deep", "ac_le": "Deep",
  "iris_re": "Normal", "iris_le": "Normal",
  "pupil_re": "RAPD -ve", "pupil_le": "RAPD -ve",
  "lens_re": "Pseudophakia", "lens_le": "Clear",
  "em_re": "Full", "em_le": "Full",
  "covertest_re": "Orthophoria", "covertest_le": "Orthophoria",
  "other_re": "", "other_le": "",
  "pseudophakia_re": {"operation_type": "Phaco", "operation_expense": "25000", "hospital_name": "City Eye Hospital"},
  "pseudophakia_le": {"operation_type": "", "operation_expense": "", "hospital_name": ""}
}
8. Fundus
Form field: exam_data[fundus] — flat key-value.

3 fields per eye (6 total):

Key	Column	Type	Master table
disc_re / disc_le	Disc (CDR / Appearance)	searchable text input with dropdown	tbl_master_disc
fr_re / fr_le	FR (Foveal Reflex)	searchable text input with dropdown	tbl_master_fr
comment_re / comment_le	Comment (Additional findings)	textarea, 2 rows	free-text
Stored JSON structure:


"fundus": {
  "disc_re": "0.4:1", "disc_le": "0.5:1",
  "fr_re": "Present", "fr_le": "Absent",
  "comment_re": "Normal disc margins", "comment_le": "Mild pallor noted"
}
9. Dilate
Form field: exam_data[dilate] — radio button.

Options: Yes (label "Yes, Dilated") or No (label "No"). Default: No.

Additional field when "Yes" is selected:

dilation_time — integer input, min=1, max=180, units = minutes. This is a top-level form field (NOT inside exam_data). Stored in primary_examinations.dilation_time column (not in the JSON). Validation: nullable|integer|min:1|max:180.
How it works: When dilate=Yes and dilation_time is set, a dilation lock is enforced. Secondary exam access is blocked until primary_examination.updated_at + dilation_time minutes has elapsed (bypassed with ?force=1 query param).

10. Diagnosis
Form field: exam_data[diagnoses][] — array of integer IDs.

How it works: A checkbox-grid of diagnosis pills rendered from tbl_master_diagnosis. Each diagnosis is a checkbox styled as a pill button (btn-check + label.btn.btn-outline-danger.rounded-pill). Multiple diagnoses can be checked.

Each pill shows optional badges:

Blue badge {N}G — N medicine groups linked to that diagnosis
Green badge {N}A — N advice entries linked to that diagnosis
A live search input (#dxSearch) filters the pills by text.

There is also an "+ Add" AJAX endpoint to create new diagnoses on-the-fly (POST /{slug}/exam/ajax-add-diagnosis).

Downstream effects of diagnosis selection:

In the Medicine modal, diagnosis-linked medicine groups are suggested in #dxSuggestedGroups.
In the Advice modal, diagnosis-linked advices are auto-suggested in #dxSuggestedAdvices.
Stored JSON structure:


"diagnoses": [3, 7, 12]
(Array of integer IDs from tbl_master_diagnosis.id)

11. Medicine (Rx)
Form field: medicines[] — top-level array (NOT inside exam_data).

Per-row fields:

Field	Name	Type	Notes
medicines[N][medicine_id]	Medicine ID	hidden integer	Set by JS when user selects from autocomplete. exists:medicines,id.
medicines[N][name]	Medicine Name	text input with autocomplete	Searched from pre-loaded medicines table (brand_name or name). If medicine_id is empty but name is filled, the backend does a lookup by name to find/set the ID.
medicines[N][dosage_id]	Dosage	select	Options from dosages table. exists:dosages,id.
medicines[N][duration]	Days	number input	Duration in days, min=1. Rendered with "D" suffix label.
medicines[N][quantity]	QTY	number input	Quantity, min=1.
medicines[N][route_id]	Route of Administration	select	Options from medicine_routes table. exists:medicine_routes,id.
Note: The instructions field appears in the request rules and the master data (tbl_master_medicine_instructions / instructions_list datalist) but is NOT rendered as a visible column in the table UI. It exists in the ExamPrescription model fillable and in the eye field set to 'Both' as default in service.

Primary exam: Medicines saved to exam_prescriptions table via ExamPrescription model. Fields stored: medicine_id, dosage_id, frequency, duration, eye (default 'Both'), instructions, sort_order.

Secondary exam: Medicines saved in two places:

Embedded in exam_data['rx'] as JSON array
Also saved to patient_prescriptions table via PatientPrescription model
Secondary exam rx stored structure:


"rx": [
  {"medicine_id": 45, "name": "Moxifloxacin Eye Drop", "dosage_id": 2, "duration": "7", "eye": "Both", "instructions": null},
  {"medicine_id": 12, "name": "Lubricating Eye Drop", "dosage_id": 3, "duration": "30", "eye": "RE", "instructions": null}
]
Group loading: A "Group" select dropdown (#rxGroupSelector) lets users load a predefined MedicineGroup via AJAX (GET /{slug}/exam/medicine-group/{id}), which auto-fills multiple medicine rows.

12. Advice
Form field: exam_data[advice] — single textarea (name: exam_data[advice], id: advice_textarea).

Type: multi-line textarea, 8 rows, maxlength=2000, resizable vertically.
Placeholder: "Clinical advice, post-operative care, follow-up instructions..."
A character counter (#adviceCharCount) shows {N} / 2000.
For secondary exam only: The advice is ALSO stored in the top-level secondary_examinations.advice column (separate from exam_data). The controller extracts it with $examData['advice'] ?? null and passes it as the $advice parameter to saveSecondaryExam().

How advice is populated (3 mechanisms):

Favourite pills (Quick Add): Advices marked is_favourite=true in tbl_master_advice appear as pill buttons above the textarea. Clicking a pill appends its text to the textarea (with \n separator).
More dropdown: A "More" dropdown button opens a list of ALL advices with a search box (#newAdviceInput). Clicking any item appends it. Each item has a star button to toggle is_favourite via AJAX.
Diagnosis-linked auto-suggest (#dxSuggestedAdvices): When diagnoses are selected, JS finds advices linked to those diagnoses via diagnosis_ids and shows them as auto-suggest buttons in the advice modal header area.
+ Add new: Users can type a new advice in the search box and click "+ Add" to create it via AJAX (POST /{slug}/exam/ajax-add-advice).
Stored JSON structure:


"advice": "Use eye drops as prescribed.\nAvoid rubbing eyes.\nReview after 1 week.\nAvoid swimming for 2 weeks."
API Payload Format (Complete)
POST /api/v1/{slug}/exams/primary/{patientId}

{
  "doctor_id": 5,
  "dilation_time": 45,
  "exam_data": {
    "co_rows": [
      {"complaint": "Blurred Vision", "since": "3", "unit": "Months", "eye": "RE", "comment": ""},
      {"complaint": "Watering", "since": "1", "unit": "Weeks", "eye": "Both", "comment": ""}
    ],
    "kco_rows": [
      {"condition": "Diabetes", "since": "5", "unit": "Years", "comment": ""}
    ],
    "history": "Previous cataract surgery, Glaucoma",
    "complaint_duration": "3 Months",
    "vision": {
      "vn_re": "6/18", "vn_le": "6/6",
      "pnvn_re": "6/9", "pnvn_le": "6/6",
      "nrvn_re": "N8", "nrvn_le": "N6"
    },
    "pg": {
      "re": {"ds": "+2.00", "dc": "-0.50", "ax": "90", "vn": "6/6", "ns": "+2.50", "nc": "-0.50", "na": "90", "near_vn": "N6"},
      "le": {"ds": "+1.75", "dc": "0.00", "ax": "0", "vn": "6/9", "ns": "+2.25", "nc": "0.00", "na": "0", "near_vn": "N8"}
    },
    "st": {
      "re": {"ds": "-1.50", "dc": "-0.25", "ax": "180", "vn": "6/6", "add": "+2.00", "ns": "+0.50", "nc": "-0.25", "na": "180"},
      "le": {"ds": "-1.00", "dc": "0.00", "ax": "0", "vn": "6/9", "add": "+2.00", "ns": "+1.00", "nc": "0.00", "na": "0"},
      "bifocal": "1",
      "nd_separate": null,
      "progressive": null,
      "computer_uses": null
    },
    "nct": {"iop_re": "14", "iop_le": "16"},
    "oe": {
      "sac_re": "NAD", "sac_le": "NAD",
      "lid_re": "Normal", "lid_le": "Normal",
      "conj_re": "Clear", "conj_le": "Clear",
      "cornea_re": "Clear", "cornea_le": "Clear",
      "ac_re": "Deep", "ac_le": "Deep",
      "iris_re": "Normal", "iris_le": "Normal",
      "pupil_re": "RAPD -ve", "pupil_le": "RAPD -ve",
      "lens_re": "Pseudophakia", "lens_le": "Clear",
      "em_re": "Full", "em_le": "Full",
      "covertest_re": "Orthophoria", "covertest_le": "Orthophoria",
      "other_re": "", "other_le": "",
      "pseudophakia_re": {"operation_type": "Phaco", "operation_expense": "25000", "hospital_name": "City Eye"},
      "pseudophakia_le": {"operation_type": "", "operation_expense": "", "hospital_name": ""}
    },
    "fundus": {
      "disc_re": "0.4:1", "disc_le": "0.5:1",
      "fr_re": "Present", "fr_le": "Present",
      "comment_re": "Normal", "comment_le": "Normal"
    },
    "dilate": "Yes",
    "diagnoses": [3, 7],
    "advice": "Use drops 4 times daily.\nReturn after 1 week.",
    "special_advice": null,
    "followup_date": "2026-07-10",
    "followup_duration": "1 week"
  },
  "medicines": [
    {"medicine_id": 45, "name": "Moxifloxacin Eye Drop", "dosage_id": 2, "duration": "7", "quantity": 1, "route_id": 3},
    {"medicine_id": 12, "name": "Lubricating Eye Drop", "dosage_id": 3, "duration": "30", "quantity": 1, "route_id": null}
  ]
}
POST /api/v1/{slug}/exams/secondary/{patientId}
Same structure as primary, with two additions:

Top-level "advice" field (nullable string, max 2000) — mobile apps can send advice here instead of (or in addition to) exam_data.advice
No dilation_time at top level (though exam_data.dilate still accepted)
API Response Format (GET)
GET /api/v1/{slug}/exams/primary/{patientId}

{
  "success": true,
  "data": {
    "id": 123,
    "tenant_id": 1,
    "patient_id": 456,
    "doctor_id": 5,
    "exam_data": { /* full exam_data object as above */ },
    "dilation_time": 45,
    "examined_at": "2026-07-02T10:30:00.000000Z",
    "created_at": "2026-07-02T10:30:00.000000Z",
    "updated_at": "2026-07-02T10:35:00.000000Z",
    "prescriptions": [
      {
        "id": 10,
        "primary_examination_id": 123,
        "medicine_id": 45,
        "dosage_id": 2,
        "frequency": null,
        "duration": "7",
        "eye": "Both",
        "instructions": null,
        "sort_order": 0,
        "medicine": {"id": 45, "name": "Moxifloxacin", "brand_name": "Moxicip"},
        "dosage": {"id": 2, "dosage": "1-1-1"}
      }
    ]
  },
  "message": "Primary examination retrieved successfully."
}
GET /api/v1/{slug}/exams/secondary/{patientId}

{
  "success": true,
  "data": {
    "id": 78,
    "tenant_id": 1,
    "patient_id": 456,
    "doctor_id": 5,
    "exam_data": { /* full exam_data including rx array */ },
    "advice": "Use drops 4 times daily.",
    "examined_at": "2026-07-02T11:00:00.000000Z",
    "created_at": "...",
    "updated_at": "..."
  },
  "message": "Secondary examination retrieved successfully."
}
Note: Secondary data does NOT eagerly load prescriptions as a separate relation — medicines are embedded in data.exam_data.rx.

Eye Field Options — Complete
Used in C/O eye field:

Stored value	Display label
"" (empty)	-
RE	Right
LE	Left
Both	Both
OU	OU
Used in Medicine eye field (set by service, not a UI input):

Default: 'Both'
The "Since" Field
Type: dropdown select (NOT free text)
Options: "" (displayed as -), then integers 1 through 10
Applies to: both C/O rows and K/C/O rows
The "Unit" Field (Duration Unit)
Type: dropdown select
Options: Days, Weeks, Months, Years, Longtime
Validation rule: in:Days,Weeks,Months,Years,Longtime
Default in C/O: Days
Default in K/C/O: Years
Favourites System
How It Works for C/O (Chief Complaints)
The chief_complaints table has an is_favourite boolean column.
On page load, complaints where is_favourite=true are rendered as yellow pill buttons above the search bar (#coFavPillsWrap). These are the "quick access" favourites.
The main complaint dropdown shows only non-favourite items (favourites are intentionally excluded from the dropdown to avoid duplication).
Each item in the dropdown has a ☆ star button. Clicking it calls POST /{slug}/masters/detail/complaints/{id}/toggle-favourite to flip the is_favourite flag. The complaint then moves from dropdown to pills or vice versa.
Clicking a favourite pill immediately calls addCoRow(complaintName) — adds the complaint as a new row without any further interaction.
Clicking a favourite pill's star (★) calls the same toggle-favourite endpoint to UN-favourite it.
The same favourites system applies identically to:

KCO entries (pills in #kcoFavPillsWrap, items from kcos table, endpoint /{slug}/masters/detail/kcos/{id}/toggle-favourite)
H/O entries (pills in #hnoFavPillsWrap, items from tbl_master_hno)
Favourites for Advice
tbl_master_advice / MasterAdvice model has is_favourite boolean.
Favourite advices appear as pill buttons in the "Quick Add" row.
Non-favourites appear in the "More" dropdown list.
Each advice item in "More" has a star button to toggle favourite via AJAX.
Advice favourites work by appending text to textarea (not by adding rows).
Favourites for O/E
O/E master data (tbl_master_disc, tbl_master_fr, tbl_master_lid, etc.) also has is_favourite on some tables. The dropdown for O/E fields shows is_favourite items first (ordered orderByDesc('is_favourite') in controller queries).

Additional Fields in Request Validator (Not Currently Rendered in UI)
From StorePrimaryExamRequest:

exam_data.followup_date — nullable date
exam_data.followup_duration — nullable string, max 50
exam_data.special_advice — nullable string, max 500
exam_data.kcos — nullable array (legacy field, now replaced by kco_rows)
exam_data.complaints — nullable array (legacy field, now replaced by co_rows)
medicines.*.frequency — not in view but stored in ExamPrescription fillable
medicines.*.instructions — not a visible column but in datalist instructions_list
File Paths Reference
J:\laragon\www\eye-saas-hms\resources\views\hospital\exam\primary.blade.php (4449 lines)
J:\laragon\www\eye-saas-hms\resources\views\hospital\exam\secondary.blade.php (4487 lines)
J:\laragon\www\eye-saas-hms\app\Http\Controllers\Hospital\Examination\PrimaryExamController.php
J:\laragon\www\eye-saas-hms\app\Http\Controllers\Hospital\Examination\SecondaryExamController.php
J:\laragon\www\eye-saas-hms\app\Http\Controllers\Api\ExamApiController.php
J:\laragon\www\eye-saas-hms\app\Services\Hospital\ExaminationService.php
J:\laragon\www\eye-saas-hms\app\Http\Requests\Hospital\Examination\StorePrimaryExamRequest.php
J:\laragon\www\eye-saas-hms\app\Http\Requests\Hospital\Examination\StoreSecondaryExamRequest.php
J:\laragon\www\eye-saas-hms\app\Models\Hospital\PrimaryExamination.php
J:\laragon\www\eye-saas-hms\app\Models\Hospital\SecondaryExamination.php