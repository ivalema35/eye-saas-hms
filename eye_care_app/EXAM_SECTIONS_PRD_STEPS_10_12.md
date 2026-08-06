# Exam Sections PRD — Steps 10, 11, 12 (Secondary Screen Only)
## Sections: Diagnosis · Medicine (Rx) · Advice / Follow-up
### Source-of-truth: `secondary.blade.php` (eye-saas-hms web project)

---

## 0. Shared Context for These Three Steps

These steps exist **only on the Secondary Exam screen** (`secondary_exam_screen.dart`).
They correspond to `_stepNames` indices 9, 10, 11 (step labels "Diagnosis", "Medicine", "Advice").

**Masters needed (beyond what already exists in `ExamMastersData`):**
- `advices` — `GET /{slug}/masters/detail/advices` → `{id, value, is_favourite}`.
  This list is **not yet** in `ExamMastersData` and must be added.

**Favourite toggle API (same pattern as O/E, Fundus):**
- `POST /{slug}/masters/detail/{type}/{id}/toggle-favourite`
- Returns `{ "data": { "is_favourite": true|false } }`.
- Types that support toggle: `diagnoses`, `advices`.

**Per-step save model (same as every other step):**
- `POST /api/v1/{slug}/exams/secondary/{patientId}`
- Body always includes full `exam_data` object + optional `medicines` array.
- Backend does `array_merge(existing, new)` — a per-step save never wipes other steps.

---

## 10. Diagnosis

### 10.1 Web Behaviour (source of truth)

**UI — Modal `#modalDiagnosis`:**

- Header: "Diagnosis" title with a red counter badge showing number selected (hidden when 0).
- **Search bar** at top — real-time substring filter over `d.diagnosis` (case-insensitive).
- **Pill grid** (`d-flex flex-wrap gap-2`): one pill per diagnosis from `$masters['diagnoses']`.
  - Each pill is a `<label>` styled as `btn btn-outline-danger rounded-pill`.
  - Backed by a hidden `<input type="checkbox" name="exam_data[diagnoses][]" value="{id}">`.
  - Selected pill: turns solid danger (red background, white text).
  - Each pill shows two optional badges inline:
    - **G badge** (blue): count of medicine groups linked to that diagnosis (`med_groups` where `diagnosis_id == d.id`).
    - **A badge** (green): count of advices linked to that diagnosis (`advices` where `d.id ∈ advice.diagnosis_ids`).
  - Tapping a pill toggles its `checked` state.
- Footer: "N diagnosis selected" hint text + **Done** button (dismisses modal).
- No explicit "Clear all" button on web (user unchecks individually), but no technical blocker.

**Auto-suggestions triggered when a diagnosis is checked/unchecked (JS events):**
1. **Suggested Groups panel** renders inside the Medicine modal (`#dxSuggestedGroups`):
   - Shows medicine groups whose `diagnosis_id` is in the set of selected diagnosis IDs.
   - Each group has a "Load" button — clicking calls `GET /{slug}/ajax/medicine-group/{id}` and inserts all items as prescription rows.
2. **Suggested Advices panel** renders inside the Advice modal (`#dxSuggestedAdvices`):
   - Shows advices whose `diagnosis_ids` overlaps the selected diagnosis IDs.
   - Click → appends that advice text to the advice textarea.
3. When a new diagnosis is **checked** (not unchecked): its linked advices are auto-appended to the advice textarea.

**No favourite toggle button on diagnosis pills in the web UI.** The `diagnoses` master does support `is_favourite` and the toggle API, but the web form doesn't expose a toggle button on the diagnosis grid. The mobile showing ★ prefix on favourites is an intentional UX improvement.

### 10.2 API Payload

```json
{
  "exam_data": {
    "diagnoses": [12, 47, 83]
  }
}
```

- `diagnoses` = array of selected diagnosis IDs (integers).
- Empty array `[]` is valid (no diagnosis selected).
- On prefill: `ed['diagnoses']` is the stored array; read on load and re-populate `_selectedDiagnosisIds`.

### 10.3 Mobile Implementation Requirements

1. **Display:** search bar + `Wrap` of pill chips — exactly as already implemented.
2. **Fav-first sort:** show `isFavourite == true` chips first with `★` prefix — already implemented.
3. **Selection badge:** show "N selected" counter badge + "Clear all" text button — already implemented.
4. **G badge and A badge on each chip:** requires two computed counts per diagnosis:
   - Groups count: number of medicine groups linked to `d.id` (needs `med_groups` data on mobile).
   - Advice count: number of advices that include `d.id` in their `diagnosis_ids` (needs `advices` with `diagnosis_ids`).
   - Needed only for display; does not affect payload.
5. **Suggested Groups in Medicine step:** when `_selectedDiagnosisIds` changes, recompute which medicine groups are linked. Show them as "Quick Load" buttons inside `_buildMedicineStep()`.
6. **Suggested Advices in Advice step:** when `_selectedDiagnosisIds` changes, recompute which advices are linked. Show them as "Append" buttons inside `_buildAdviceStep()`.
7. **Fav toggle:** no toggle button needed on diagnosis chips (web parity — web doesn't have it either).

### 10.4 Current Gaps in Mobile

| # | Gap | Priority |
|---|-----|----------|
| G1 | No **G badge** (linked groups count) on diagnosis chips | Medium |
| G2 | No **A badge** (linked advices count) on diagnosis chips | Medium |
| G3 | No **Suggested Groups** panel in Medicine step when diagnosis selected | High |
| G4 | No **Suggested Advices** panel in Advice step when diagnosis selected | High |
| G5 | Auto-append linked advice to textarea when diagnosis is first checked | Medium |

**Implementation note for G3–G5:** Requires fetching `med_groups` (a new API endpoint) and enriched `advices` master (with `diagnosis_ids` array per advice item) on screen load. The current `ExamMasterItem` model only has `{id, value, is_favourite}` — a new model is needed for advices with diagnosis linkage.

---

## 11. Medicine (Rx)

### 11.1 Web Behaviour (source of truth)

**UI — Modal `#modalRx`:**

**Toolbar:**
- **Group selector** dropdown (left): lists all medicine groups from `$masters['med_groups']`. Selecting a group fires AJAX `GET /{slug}/ajax/medicine-group/{id}` and loads all items as new prescription rows. Resets to `-- Load Group --` after loading.
- **+ Add Medicine** button (right): appends one blank row to the table.

**Suggested Groups panel** (`#dxSuggestedGroups`, inside Medicine modal):
- Shown/hidden by JS when diagnoses are selected/deselected.
- Renders linked groups as "load" buttons.
- When no diagnosis is selected: shows prompt "Select a Diagnosis first — linked medicine groups will appear here".
- When diagnosis selected but no linked groups: shows "No medicine groups linked...".

**Prescription table columns:**

| Column | Field name | Widget | Notes |
|--------|-----------|--------|-------|
| Medicine Name | `medicines[N][name]` | Text input with live search autocomplete | Shows brand_name; hidden `[medicine_id]` input alongside |
| Dosage | `medicines[N][dosage_id]` | `<select>` from `$masters['dosages']` | Auto-filled from medicine master |
| Days | `medicines[N][duration]` | Number input + "D" suffix label | Auto-filled from medicine master; min 1 |
| QTY | `medicines[N][quantity]` | Number input | min 1 |
| Route of Administration | `medicines[N][route_id]` | `<select>` from `$masters['routes']` | |
| (delete) | — | × button | Removes the row |

**Medicine autocomplete suggestion row shows:**
- Primary: brand name (bold)
- Badge: dosage label in a blue chip
- Meta line: "N days · Qty: N" (from medicine master defaults)
- Selecting a suggestion: auto-fills `[name]`, hidden `[medicine_id]`, `[dosage_id]`, `[duration]`, `[quantity]`.

**Empty state:** "No medicines added yet — click + Add Medicine" message with capsule icon.

### 11.2 API Payload

```json
{
  "medicines": [
    {
      "medicine_id": 15,
      "name": "Moxifloxacin 0.5% Eye Drops",
      "dosage_id": 3,
      "duration": "7",
      "quantity": 1,
      "route_id": 2
    }
  ]
}
```

- `medicines` is at the **top level** of the payload (not inside `exam_data`).
- `medicine_id` is nullable — if the user typed a name not in the master, `medicine_id` is null and `name` is saved as typed.
- `duration` is a string (e.g. "7") — number of days.
- Rows with empty `name` are filtered out by the backend.
- Backend also accepts `eye` and `instructions` but these are legacy fields — neither web nor mobile currently sends them.

**Backend behaviour for `quantity`:**
- `StoreSecondaryExamRequest` validates `medicines.*.quantity` as `nullable|integer|min:1`.
- Mobile `_PrescRow.toJson()` already includes `'quantity': quantity` — this is correct.

### 11.3 Mobile Implementation Requirements

1. **Row widget `_prescRow`:** keep existing layout — Medicine search + Dosage + Route + Duration + Qty + Delete — all fields already implemented.
2. **"D" suffix label** on Duration field: add a small "D" suffix text widget next to the duration input (cosmetic, matches web).
3. **Medicine search autocomplete enhancement:**
   - Currently shows: medicine `name` + `medicineTypeName`.
   - Should also show: `dosageText` (as a small blue badge chip) + `duration`/`qty` as grey meta text below the name — matches web suggestion row.
4. **Medicine Group batch-load:**
   - Add a "Group" dropdown above the list (from `_formData?.medGroups ?? []`).
   - On select: `GET /{slug}/ajax/medicine-group/{id}` → parse `group.items` → add `_PrescRow` for each item.
   - Requires adding `medGroups` to the `SecondaryFormData` model or fetching separately on screen load.
5. **Suggested Groups panel:** show above the list when `_selectedDiagnosisIds` is non-empty, filtered to groups whose `diagnosis_id` is in the set. Tap to load items.
6. **Empty state:** "Tap + to add a prescription" — already implemented.

### 11.4 Current Gaps in Mobile

| # | Gap | Priority |
|---|-----|----------|
| G1 | Medicine suggestion row doesn't show dosage badge or duration/qty hints | Low |
| G2 | No **"D" days suffix** label in Duration field | Low |
| G3 | No **Medicine Group batch-load** (Group dropdown) | High |
| G4 | No **Suggested Groups** panel (from selected diagnosis) | High |

---

## 12. Advice / Follow-up

### 12.1 Web Behaviour (source of truth)

**UI — Modal `#modalAdvice`:**

The Advice modal has three sub-sections stacked vertically:

**Sub-section 1 — Diagnosis-linked advices (`#dxSuggestedAdvices`):**
- JS-rendered. Empty until a diagnosis is selected.
- Shows advices whose `diagnosis_ids` overlaps `_selectedDiagnosisIds`.
- Each advice is a green-outlined "Append" button. Clicking appends the text to the main advice textarea (with a newline separator).
- When no diagnosis selected: shows "Select a Diagnosis — linked advices will appear here."
- When diagnosis selected but no linked advices: shows "No advices linked...".

**Sub-section 2 — Quick Add (`#adviceChipsWrap`):**
- Section label: "Quick Add — click any pill to append" (lightning icon, uppercase, small).
- **Favourite pills:** one per `favAdvices` (where `is_favourite == true`). Clicking appends that advice text to the textarea. Each pill has `★` icon (amber).
- **"More" button** (grid icon): opens a dropdown (below the More button, `data-bs-auto-close="outside"`).
  - **Search bar** at top of dropdown: typed query filters the list in real-time.
  - **List of all advices** (scrollable, max 240px height): each item has:
    - Advice text (click → appends to textarea, closes dropdown not required).
    - **☆/★ toggle button** at the right — calls `POST /{slug}/masters/detail/advices/{id}/toggle-favourite`, updates icon + opacity inline.
  - **"+ Add" button** next to search bar: saves the typed text as a new advice master item (AJAX `POST /{slug}/masters/detail/advices`, body `{value: "<text>"}`), appends to textarea, appends new pill to #adviceChipsWrap.
  - "No match — click + Add to create" empty state.

**Sub-section 3 — Advice Textarea:**
- `name="exam_data[advice]"`, `rows=8`, `maxlength=2000`.
- Char counter label: "N / 2000" (updates on each keypress).
- Placeholder: "Clinical advice, post-operative care, follow-up instructions..."
- Appending via pills concatenates with a newline: `existing + (existing ? '\n' : '') + new`.

**Footer:**
- "Clear" button: empties the textarea, resets char counter.
- "Done" button: dismisses the modal.

**Follow-up date, follow-up duration, and special advice:**
These three fields exist in the backend validation (`StoreSecondaryExamRequest`) and are stored in `exam_data`:
- `exam_data.followup_date` — ISO date string
- `exam_data.followup_duration` — free text (e.g. "2 weeks", "1 month")
- `exam_data.special_advice` — short text (max 500 chars)

**These fields are NOT in the current web UI.** The web form only has the `exam_data[advice]` textarea. The backend supports them and the mobile already implements all three. This means **mobile is ahead of web for this step** — the mobile implementation is the reference for these fields.

### 12.2 API Payload

```json
{
  "exam_data": {
    "advice": "Instill drops 4 times daily.\nWear sunglasses outdoors.\nReturn if redness increases.",
    "followup_date": "2026-08-15",
    "followup_duration": "6 weeks",
    "special_advice": "Avoid swimming for 2 weeks."
  }
}
```

- `exam_data.advice` — long-form advice text (newlines preserved). Nullable, max 2000 chars.
- `exam_data.followup_date` — nullable date string `YYYY-MM-DD`. Backend validates as `date`.
- `exam_data.followup_duration` — nullable string, max 50 chars.
- `exam_data.special_advice` — nullable string, max 500 chars.
- All four fields live inside `exam_data` (not at top level). Mobile sends them correctly.

**Important note on `advice` storage:** The backend's `saveSecondaryExam` method accepts `advice` as both:
- `$validated['advice']` (top-level) — used by web form
- `$examData['advice']` — fallback (used by mobile when sent inside `exam_data`)

Mobile currently sends it inside `exam_data` (fallback path). Both paths save to `SecondaryExamination.advice` column (not inside the JSON blob). This is fine and correct.

### 12.3 Mobile Implementation Requirements

1. **Advice textarea** (already implemented):
   - Keep `_adviceCtrl` and `_multilineField`.
   - Add **char counter** label showing `"N / 2000"` that updates on every keystroke.
   - Set `maxLength: 2000` on the TextFormField.

2. **Favourite advice pills — Quick Add section** (missing):
   - Fetch `advices` master from `GET /{slug}/masters/detail/advices` on screen load.
   - Store as `List<AdviceItem>` where `AdviceItem = { id, value, is_favourite }`.
   - Render favourite advices as tappable pill chips: tap → append text to `_adviceCtrl` (with `\n` separator if textarea is non-empty).
   - Show pills in a `Wrap` widget above the advice textarea.

3. **"More" bottom sheet / dialog** (missing):
   - A "More" button (grid icon) opens a modal bottom sheet.
   - Top: search bar to filter advices by text.
   - List: all advices, each with advice text (tap to append) + ☆/★ toggle button (calls toggle-favourite API, updates list in-place).
   - Use `StatefulBuilder` for live search + toggle, same pattern as O/E and Fundus pickers.
   - Bottom: "+ Add" button (or row): saves typed text as new advice via API, appends to textarea, adds to local list.

4. **Diagnosis-linked advice suggestions** (missing — depends on Step 10 G4 implementation):
   - When `_selectedDiagnosisIds` is non-empty and matched advices exist, show a "Suggested Advices" section above the Quick Add pills.
   - Each suggestion: tap → append to `_adviceCtrl`.
   - Requires enriched advice data with `diagnosis_ids` list per advice.

5. **Follow-up section** (already implemented):
   - Date picker → `_followupDate` → formats as `YYYY-MM-DD` in payload. ✓
   - Duration text field → `_followupDurationCtrl`. ✓

6. **Special Advice textarea** (already implemented):
   - `_specialAdviceCtrl` → `exam_data.special_advice`. ✓

### 12.4 Current Gaps in Mobile

| # | Gap | Priority |
|---|-----|----------|
| G1 | No **favourite advice pills** (Quick Add section) — missing entirely | High |
| G2 | No **"More" picker** (bottom sheet with all advices + search + fav toggle) | High |
| G3 | No way to **toggle advice favourite** on mobile | High |
| G4 | No **"+ Add new advice"** to create a new master item | Medium |
| G5 | No **Diagnosis-linked advice suggestions** panel | Medium |
| G6 | No **char counter** on advice textarea (no maxLength set) | Low |

---

## Summary Table

| Step | Step Name | Step Index | Screen | Current Mobile Status | Highest-Priority Gap |
|------|-----------|------------|--------|-----------------------|---------------------|
| 10 | Diagnosis | 9 | Secondary | ✅ Core multi-select works | G/A badges + linked suggestions |
| 11 | Medicine (Rx) | 10 | Secondary | ✅ Core prescription table works | Medicine Group batch-load |
| 12 | Advice | 11 | Secondary | ⚠️ Textarea + follow-up works, quick-add missing | Favourite pills + More picker |

---

## Data Model Changes Required

### New model: `AdviceItem`
```dart
class AdviceItem {
  final int id;
  final String value;
  final bool isFavourite;
  // For diagnosis linkage (optional, future):
  // final List<int> diagnosisIds;
}
```

### Add `advices` to `ExamMastersData`
```dart
// In ExamMastersData fields:
final List<AdviceItem> advices;

// In fetchAll() — add to types list:
'advices',   // index 22

// In ExamMastersData constructor + factory + toJson/fromDisk
```

### Medicine Groups (for Step 11 G3 + Step 10 G3)
Medicine groups are fetched from `GET /{slug}/ajax/medicine-group/{id}` (one group at a time).
The group list for the selector comes from the existing `SecondaryFormData.medGroups`.
Verify `SecondaryFormData` already includes `medGroups: List<MedMasterItem>` — check `exam_service.dart` `SecondaryFormData` class.

---

## API Endpoints Reference

| Endpoint | Method | Used by step | Purpose |
|----------|--------|--------------|---------|
| `/api/v1/{slug}/masters/detail/advices` | GET | Step 12 | Fetch all advice items with `is_favourite` |
| `/api/v1/{slug}/masters/detail/advices/{id}/toggle-favourite` | POST | Step 12 | Toggle favourite on advice item |
| `/api/v1/{slug}/masters/detail/advices` | POST `{value: "..."}` | Step 12 | Create new advice master item |
| `/{slug}/ajax/medicine-group/{id}` | GET | Step 11 | Load a medicine group's items into Rx rows |
| `/api/v1/{slug}/exams/secondary/{patientId}` | POST | Steps 10–12 | Save Diagnosis + Medicine + Advice |
