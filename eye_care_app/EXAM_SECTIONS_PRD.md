# Exam Sections PRD — Mobile App
## Sections: C/O · K/C/O & H/O · Vision · PG · ST · NCT · O/E · Fundus
### Source-of-truth: `primary.blade.php` + `secondary.blade.php` (eye-saas-hms web project)

---

## 0. Shared Principles

- **All data is dynamic from masters.** Nothing is hardcoded. Every dropdown list comes from the API's `/masters/detail/{type}` endpoint.
- **Master types used in these 4 sections:** `complaints`, `kcos`, `hno`, `vn`, `pnvn`, `nrvn`, `sph_cyl`, `axis`
- **Favourites:** every master item has `is_favourite: bool`. Favourites are shown separately (pills/top section) for quick access. The user can toggle favourites via `POST /{slug}/masters/detail/{type}/{id}/toggle-favourite`.
- **API payload key:** `exam_data` (a JSON object). All fields nest under it.

---

## 1. C/O — Chief Complaints

### 1.1 Web Behaviour (source of truth)

**Layout:**
- A **search bar** at the top of the C/O modal.
- Above the search bar: **⭐ Favourite Pills** — amber-coloured pill buttons, one per favourite complaint. Clicking a pill instantly adds that complaint as a new row. Each pill has a ★ button to un-favourite.
- Below the search bar: a **custom dropdown** (not a `<select>`) that shows non-favourite complaints matching the typed query. Each item has a ☆ button to toggle favourite.
- A **`+ Add` button** next to the search bar adds whatever is typed/selected.
- Selecting an item from the dropdown OR clicking a favourite pill OR pressing Enter on the search bar → adds a new **row** to the table.

**Table columns per C/O row:**
| Column | Field key | Widget | Values |
|--------|-----------|--------|--------|
| C/O (complaint) | `complaint` | Text input (also autocomplete via dropdown) | Free text, from `complaints` master |
| Since | `since` | Number dropdown | `""` (blank), 1–10 |
| Duration | `unit` | Dropdown | Days / Weeks / Months / Years / Longtime |
| Eye | `eye` | Dropdown | `""` (blank/−), RE (Right), LE (Left), Both, OU |
| Comment | `comment` | Text input | Free text |
| (delete) | — | × button | Removes the row |

**Favourite toggle API:**
`POST /{slug}/masters/detail/complaints/{id}/toggle-favourite`
Returns `{ is_favourite: bool }`. Refresh pill list and dropdown on response.

### 1.2 API Payload

```json
{
  "exam_data": {
    "co_rows": [
      {
        "complaint": "Blurring of Vision",
        "since": "3",
        "unit": "Months",
        "eye": "RE",
        "comment": ""
      }
    ]
  }
}
```

- `since` is a string number ("1"–"10") or `""` if not set.
- `unit` default: "Days".
- `eye` default: `""` (blank).
- Rows with empty `complaint` are filtered out before saving.

### 1.3 Mobile Implementation Requirements

1. **Masters:** fetch `complaints` list → `ExamMasterItem.value = item.complaint`, `is_favourite`.
2. **Favourite section:** show favourite items as tappable chips above the search bar. Each chip has a small ★ button to un-favourite (calls toggle-favourite API).
3. **Search bar:** text field that filters the dropdown list in real-time (case-insensitive substring match).
4. **Dropdown:** `showModalBottomSheet` or inline list showing non-favourite items with a ☆ button on each to toggle favourite. Selecting an item → adds a new row.
5. **Row widget:** a card/list tile with:
   - Complaint text field (pre-filled, editable)
   - Since: dropdown (blank + 1–10)
   - Unit: dropdown (Days / Weeks / Months / Years / Longtime)
   - Eye: dropdown (blank / RE / LE / Both / OU)
   - Comment: text field
   - Delete button
6. **`+ Add` button:** adds a new blank row that the user can fill.
7. Empty complaint rows must be excluded from the payload.

### 1.4 Current Gap in Mobile

The mobile app's C/O rows are **missing the `eye` field** (`RE / LE / Both / OU`). This must be added. The eye field must be submitted as `exam_data.co_rows[N].eye`.

---

## 2. K/C/O & H/O — Known Conditions & History of

### 2.1 Web Behaviour

This is one modal with two sub-sections.

#### K/C/O Sub-section

Same UX pattern as C/O but uses the `kcos` master and has **no eye column**.

**Table columns per K/C/O row:**
| Column | Field key | Widget | Values |
|--------|-----------|--------|--------|
| K/C/O (condition) | `condition` | Text input + autocomplete from `kcos` master | Free text, `item.kco` is the value |
| Since | `since` | Number dropdown | `""`, 1–10 |
| Duration | `unit` | Dropdown | Days / Weeks / Months / Years / Longtime |
| Comment | `comment` | Text input | Free text |
| (delete) | — | × button | — |

- Default unit is **Years** (not Days — different from C/O!).
- Favourite pills show above the search bar (same pattern as C/O).
- Favourite toggle API: `POST /{slug}/masters/detail/kcos/{id}/toggle-favourite`
- Note: master item field is `kco` (not `complaint`): `{ id, kco, is_favourite }`.

#### H/O Sub-section

**Layout:**
- A **search bar** that autocompletes from `hno` master.
- Favourite pills above the search bar.
- Selecting an item adds it as a **chip badge** (not a table row).
- Chips are displayed in a horizontal wrap. Each chip has an × to remove.
- **All H/O values stored as a single comma-separated string.**

**API Payload field:** `exam_data.history` = `"Diabetes, Hypertension, Thyroid"`

### 2.2 API Payload

```json
{
  "exam_data": {
    "kco_rows": [
      {
        "condition": "Arthritis",
        "since": "2",
        "unit": "Years",
        "comment": "test"
      }
    ],
    "history": "Diabetes, Hypertension"
  }
}
```

### 2.3 Mobile Implementation Requirements

**K/C/O:**
1. Same UX as C/O but:
   - Master key: `kcos`, master field: `kco` (use `item.value` mapped from `item.kco`)
   - No eye field
   - Default unit: **Years**
2. Favourite toggle API: `POST /{slug}/masters/detail/kcos/{id}/toggle-favourite`

**H/O:**
1. Masters key: `hno`
2. Search bar → filters `hno` master in real-time
3. Selecting or pressing Add → appends item as a **chip badge** (not a row)
4. Chip has × to remove itself
5. On save: join all chip values with `", "` → `exam_data.history`
6. On load: split `exam_data.history` by `,`, trim each → display as chips

### 2.4 Current Gap in Mobile

- K/C/O unit default should be `Years` (not `Days`) — fix the default
- Both screens (primary + secondary) — verify comment field is wired
- H/O: currently a multi-line text area; must be **chip-based** to match web

---

## 3. Vision — Visual Acuity

### 3.1 Web Behaviour

**Layout:** A modal with a 3-column table per eye (RE and LE shown separately).

**Columns:**
| Column | Label | Master key | Data field (RE) | Data field (LE) |
|--------|-------|------------|-----------------|-----------------|
| 1 | VN (Distance Vision) | `vn` | `vn_re` | `vn_le` |
| 2 | PnVn (Pinhole) | `pnvn` | `pnvn_re` | `pnvn_le` |
| 3 | NrVn (Near Vision) | `nrvn` | `nrvn_re` | `nrvn_le` |

**Cell behaviour:**
- Each cell is a **text input with a chevron (▼) icon**.
- **Tapping the input OR the chevron** opens a **dropdown list** of values from the corresponding master (`vn`, `pnvn`, or `nrvn`).
- The dropdown is a scrollable list (not a grid). Shows all master values. No section headers.
- **User can also type freely** — the input accepts manual text entry AND opens the dropdown to filter.
- Selecting from dropdown → sets the value → closes dropdown.
- The master values for VN/PnVN/NrVN are strings like `"6/6"`, `"6/9"`, `"CF"`, `"HM"`, `"PL"`, `"NPL"`.

### 3.2 API Payload

```json
{
  "exam_data": {
    "vision": {
      "vn_re": "6/6",
      "vn_le": "6/9",
      "pnvn_re": "6/6",
      "pnvn_le": "6/6",
      "nrvn_re": "N6",
      "nrvn_le": "N6"
    }
  }
}
```

### 3.3 Mobile Implementation Requirements

1. Show two RE/LE sections, each with 3 cells: VN, PnVN, NrVN.
2. Each cell = `TextFormField` with `suffixIcon: IconButton(chevron)`.
3. Tapping field OR chevron opens a bottom sheet list of master values.
4. Field is **NOT readOnly** — user can type freely. Dropdown appears on focus/tap and filters as user types.
5. Masters already fetched by `ExamMastersService`: `vn`, `pnvn`, `nrvn`.
6. On load: populate from `exam_data.vision.{field}`.
7. On save: `exam_data.vision.vn_re`, `.vn_le`, `.pnvn_re`, `.pnvn_le`, `.nrvn_re`, `.nrvn_le`.

### 3.4 Current State

The `_masterVisionCell` widget was updated to remove the `GestureDetector` bug. The fields now use `TextFormField.onTap` + `readOnly: items.isNotEmpty`. However, they should NOT be readOnly — Vision fields must allow free-text typing AND dropdown selection (same as web).

**Fix required:** In `_masterVisionCell`, change `readOnly: items.isNotEmpty` to `readOnly: false`. The dropdown still opens via `onTap` + suffix `IconButton`.

---

## 4. PG — Prescription Glass

### 4.1 Web Behaviour — Complete Description

**Layout:** A modal with RE and LE sections. Each section has a table with 2 rows (DISTANCE, NEAR) × 5 columns (row label, SPH, CYL, Axis, VN C GL).

#### SPH field (Distance SPH = `ds`, Near SPH = `ns`)

Layout: `[− btn]  [readonly input]  [+ btn]`

- The `[−]` button is red (danger). The `[+]` button is green (success). Both are 32×32px.
- The input in the middle is **readonly** — user cannot type directly. It only shows the selected value.
- Clicking `[−]` or `[+]`:
  1. **Closes** the parent PG modal
  2. **Opens** a separate full-screen "PG Picker" modal
  3. The picker title changes to "− Negative Values" or "+ Positive Values"
- The picker shows a **grid** (8 columns) of values from `sph_cyl` master:
  - All master values that parse to `num > 0` (positive numbers only, strips sign from master)
  - For `+` picker: displayed as `+0.25`, `+0.50`, ... `+10.00`
  - For `−` picker: displayed as `-0.25`, `-0.50`, ... `-10.00`
  - **`0.00` is always shown as the last chip** (same for both + and − pickers)
- **Currently selected value** is highlighted with inverted style (white background, navy border)
- Bottom bar of picker:
  - **SELECTED** label with the current value shown in a navy pill
  - **Clear** button (red outline) — sets value to `""` (empty)
  - **CUSTOM** text input (right side) + **Apply** button — user types any value and clicks Apply
- Clicking a grid chip → sets value → closes picker → **reopens PG modal**
- Clicking Apply → sets custom value → closes picker → reopens PG modal
- Clicking Clear → clears value → closes picker → reopens PG modal

**Important:** `sph_cyl` master values may already contain signs (e.g. `"+0.25"`). The web strips the sign and uses only the absolute number, then prepends the chosen sign.

#### CYL field (Distance CYL = `dc`, Near CYL = `nc`)

- **Exact same layout and picker as SPH** (`[−][readonly][+]`, same picker modal)
- Uses same `sph_cyl` master
- **Side effect:** when CYL is set to a non-zero value → Axis field becomes **enabled**. When CYL is `0.00` or empty → Axis is **disabled** (grayed, not interactive)

#### Axis field (Distance Axis = `ax`, Near Axis = `na`)

Layout: `[editable text input] [▼ chevron]`

- The input is **editable** (user can type directly, e.g. `90`, `180`)
- Tapping the chevron (or focus on input) opens a **dropdown list** (not modal) from `axis` master
- `axis` master values: the web strips `+/-` prefix — values are plain numbers like `"90"`, `"180"`, `"0"` etc.
- The axis field is **disabled** when CYL is empty/zero
- Axis is enabled only when CYL has a value

#### VN C GL field (Distance VN = `vn`, Near VN = `near_vn`)

Layout: `[editable text input] [▼ chevron]`

- The input is **editable** (user can type)
- Dropdown from master:
  - DISTANCE row → uses `vn` master
  - NEAR row → uses `nrvn` master
- Values like `"6/6"`, `"6/9"`, `"CF"` etc.

### 4.2 API Payload

```json
{
  "exam_data": {
    "pg": {
      "re": {
        "ds": "+1.50",
        "dc": "-0.50",
        "ax": "90",
        "vn": "6/9",
        "ns": "+3.50",
        "nc": "-0.50",
        "na": "90",
        "near_vn": "N6"
      },
      "le": {
        "ds": "+1.25",
        "dc": "-0.25",
        "ax": "180",
        "vn": "6/6",
        "ns": "+3.25",
        "nc": "-0.25",
        "na": "180",
        "near_vn": "N6"
      }
    }
  }
}
```

Values are stored as-is (e.g. `"+1.50"`, `"-0.50"`, `"0.00"`, `""`).

### 4.3 Mobile Implementation Requirements

#### SPH / CYL Picker (replace current implementation)

**What the current implementation does wrong:**
- The current `_showSphCylPicker` shows a `DraggableScrollableSheet` with a favourites/non-favourites list — this does NOT match the web's grid picker.
- The web shows a **full-screen grid** (not bottom sheet) with **8 columns**, values formatted with the forced sign.

**What it must do:**

1. Tapping `[−]` or `[+]` button opens a **`showDialog` or `showModalBottomSheet`** (full-height, not a small sheet) that:
   - Has a title header: "+ Positive Values" or "− Negative Values"
   - Shows a **grid** (4 or 5 columns on mobile — adapt to screen width): all `sph_cyl` master values parsed as absolute positive numbers, then prefixed with the chosen sign
   - Each chip: navy background, white text, 14px bold
   - Currently selected chip: white background, navy border and text
   - Last chip: always `"0.00"` (no sign prefix, grey-ish)
   - Tapping a chip: sets value, closes dialog
2. **Bottom bar** inside the picker:
   - "SELECTED" label + current value (navy pill)
   - "Clear" button → sets `""`, closes
   - "CUSTOM" `TextField` (numeric, step 0.25) + "Apply" `ElevatedButton` → sets typed value, closes
3. After picker closes → parent PG screen remains open (no modal to reopen in Flutter since we use a screen/card, not a modal)

#### AXIS field

- `TextFormField` with `readOnly: false` (editable)
- Suffix: `IconButton` with chevron → opens bottom sheet list from `axis` master
- Bottom sheet list: simple scrollable list, items are plain numbers (no sign prefix)
- **Disabled** when the corresponding CYL value is empty/zero
- `keyboardType: TextInputType.number`

#### VN C GL field

- `TextFormField` with `readOnly: false` (editable)
- Suffix: `IconButton` with chevron → opens bottom sheet list
- DISTANCE row: list from `vn` master
- NEAR row: list from `nrvn` master

#### Axis enable/disable logic

After applying a CYL value:
```dart
// In setState after CYL picker returns:
final isZero = cylVal.isEmpty || cylVal == '0.00' || double.tryParse(cylVal.replaceAll('+','')) == 0;
_axisEnabled = !isZero;  // a bool flag per eye×row
```
The Axis `TextFormField` should use `enabled: _axisEnabled` and visually grey out when disabled.

### 4.4 Current Gaps in Mobile

| Field | Current State | Required Fix |
|-------|--------------|--------------|
| SPH/CYL picker | Shows bottom sheet with fav/non-fav list | Rewrite as full-screen grid picker with sign-filtered values + custom input |
| Axis disabled state | Not implemented | Add CYL→Axis disable logic |
| AXIS | Editable + dropdown — OK after latest fix | Verify dropdown shows plain numbers (no sign) |
| VN C GL | Editable + dropdown — OK after latest fix | Verify correct master (vn vs nrvn per row) |

---

---

## 5. ST — Subjective Test

### 5.1 Web Behaviour

**Layout:** Same RE/LE table structure as PG — same 5-column table (row label, SPH, CYL, Axis, VN C ST). Two rows: DISTANCE and NEAR.

**DISTANCE row** — identical to PG distance row in every way:
- SPH (`ds`): `[−][readonly input][+]` → opens SPH/CYL grid picker
- CYL (`dc`): `[−][readonly input][+]` → opens SPH/CYL grid picker; disables Axis when zero
- Axis (`ax`): editable text input + chevron → dropdown from `axis` master; disabled when CYL zero
- VN C ST (`vn`): editable text input + chevron → dropdown from `vn` master

**NEAR row** — KEY DIFFERENCES from PG:
- **SPH (NS, key `ns`):** Same `[−][input][+]` picker. Below the input, shows "ADD: +2.50" label (the add value). The hidden form also carries the `add` field.
- **CYL (`nc`):** READONLY display — mirrors the Distance CYL value exactly. Shows "= Distance" sub-label. **Not independently editable.**
- **Axis (`na`):** READONLY display — mirrors the Distance Axis value exactly. Shows "= Distance" sub-label. **Not independently editable.**
- **VN C ST:** Shows "—" (dash). No input for near VN in ST.

**ADD field (`add`):** Entered alongside near SPH. In the web it is a separate hidden field (carries the near addition value). In the web payload, `ns` and `add` are both stored separately for each eye.

**Checkboxes** (below both eye tables, in a row):
| Key | Label |
|-----|-------|
| `bifocal` | Bifocal |
| `nd_separate` | Near & Distance Separate |
| `progressive` | Progressive |
| `computer_uses` | Computer Uses |

### 5.2 API Payload

```json
{
  "exam_data": {
    "st": {
      "re": {
        "ds": "+1.50",
        "dc": "-0.50",
        "ax": "90",
        "vn": "6/9",
        "add": "+2.00",
        "ns": "+3.50",
        "nc": "-0.50",
        "na": "90"
      },
      "le": {
        "ds": "+1.25",
        "dc": "-0.25",
        "ax": "180",
        "vn": "6/6",
        "add": "+2.00",
        "ns": "+3.25",
        "nc": "-0.25",
        "na": "180"
      },
      "bifocal": true,
      "nd_separate": false,
      "progressive": false,
      "computer_uses": false
    }
  }
}
```

`nc` and `na` must be submitted and equal `dc` and `ax` respectively (they are readonly mirrors on save).

### 5.3 Mobile Implementation Requirements

**Distance row:** Fully reuse `_refractionCell` (same as PG). SPH/CYL picker, axis disabled logic, VN dropdown — all identical to PG.

**NEAR row:**
1. **NS (near SPH):** `_refractionCell` with key `'ns'` → opens same `_showSphCylPicker`. Below the chip, show "ADD: {add value}" as a small hint text.
2. **ADD input:** A small labelled numeric text field (`+2.50`, `keyboardType: decimal`) directly below the NS chip — `_stAddRe` / `_stAddLe` controllers. This allows the user to type the ADD value.
3. **NC (near CYL):** Readonly display showing the DC value. Implemented as a disabled, non-interactive chip that reads from `_st[eye]!['dc']`. **Do not put an editable field here.**
4. **NA (near Axis):** Readonly display showing the AX value. Implemented as a disabled text field that reads from `_st[eye]!['ax']`. Not editable.
5. **Near VN column:** Show "—" (a centred dash `Text('—')`). No input widget.

**On save:** Include `nc = _st[eye]['dc'].text` and `na = _st[eye]['ax'].text` in the payload.

**Checkboxes:** 4 `CheckboxListTile` / `Checkbox` widgets in a Wrap below the two eye tables, keys `bifocal`, `nd_separate`, `progressive`, `computer_uses`.

### 5.4 Current Gaps in Mobile

| Field | Current State | Required Fix |
|-------|--------------|--------------|
| NC (near CYL) | Shown as editable picker (same as distance CYL) | Make READONLY, mirror of dc; show "= Distance" label |
| NA (near Axis) | Shown as editable field | Make READONLY, mirror of ax; show "= Distance" label |
| Near VN | May show VN dropdown | Remove — NEAR row has no VN in ST |
| `nc`/`na` in payload | Not included in `_buildStepPayload` | Add: `nc: _st[eye]['dc'].text`, `na: _st[eye]['ax'].text` |
| ADD display hint | No "ADD: X" hint below NS chip | Add small hint text below NS |

---

## 6. NCT — Non-Contact Tonometry

### 6.1 Web Behaviour

**Layout:** Single table with ONE data row (IOP) and TWO eye columns (RE, LE).

- Row header: "IOP" with sub-label "mmHg"
- Each cell: editable text input + chevron (▼) icon → opens a **5-column grid dropdown** populated from the `nct` master
- The grid shows all `nct` master values (typically integer values 8–40 representing mmHg)
- Selected value is highlighted: navy background, white bold text
- User can also type freely (not readonly)
- On typing: the grid filters to matching values

**IOP legend** shown below the table (informational, no interaction):
| Colour | Range | Meaning |
|--------|-------|---------|
| 🟢 Green | 10–21 mmHg | Normal |
| 🟡 Amber | 22–24 mmHg | Borderline |
| 🔴 Red | ≥25 mmHg | High |

### 6.2 API Payload

```json
{
  "exam_data": {
    "nct": {
      "iop_re": "14",
      "iop_le": "16"
    }
  }
}
```

Values are strings (the text the user typed or selected).

### 6.3 Mobile Implementation Requirements

1. Two cells side by side, labelled "Right Eye (RE)" and "Left Eye (LE)".
2. Each cell: `TextFormField` (editable, `keyboardType: TextInputType.number`) + chevron suffix icon.
3. Tapping field or chevron → opens a bottom sheet showing an **NCT grid picker**:
   - Values from `nct` master, arranged in a grid (4–5 columns)
   - Currently selected value highlighted (navy chip)
   - Tapping a chip selects it and closes the bottom sheet
4. **IOP colour indicator:** Decorate the cell border/background based on entered value:
   - 10–21: green border
   - 22–24: amber border
   - ≥25: red border
   - Empty or unparseable: neutral grey border
5. Below the two cells, show the legend (three coloured dots + labels) as static information.

### 6.4 Current Gaps in Mobile

| Gap | Fix |
|-----|-----|
| NCT currently uses a plain list bottom sheet | Rewrite as grid picker from `nct` master |
| No IOP colour coding on the input cell | Add border colour based on mmHg value |
| No IOP legend shown in the step | Add static legend below the cells |

---

## 7. O/E — On Examination

### 7.1 Web Behaviour

**Layout:** 3-column table — Label column | Right Eye (RE) | Left Eye (LE).

**11 rows** (10 named fields + 1 OTHER row at the end):

| # | Key | Short label | Full name | Master type |
|---|-----|-------------|-----------|-------------|
| 1 | `sac` | SAC | Sac | `sac` |
| 2 | `lid` | LID | Lid | `lid` |
| 3 | `conj` | CONJ | Conjunctiva | `conj` |
| 4 | `cornea` | CORNEA | Cornea | `cornea` |
| 5 | `ac` | AC | Anterior Chamber | `ac` |
| 6 | `iris` | IRIS | Iris | `iris` |
| 7 | `pupil` | PUPIL | Pupil | `pupil` |
| 8 | `lens` | LENS | Lens | `lens_master` |
| 9 | `em` | EM | Extraocular Mov. | `em` |
| 10 | `covertest` | COVERTEST | Cover Test | `covertest` |
| 11 | `other` | OTHER | Other findings | *(plain text, no dropdown)* |

**Payload keys:** `{fieldKey}_{eye}` → e.g. `sac_re`, `sac_le`, `lid_re`, `lid_le` etc.

**Cell behaviour (rows 1–10):**
- Editable text input + chevron → opens a sorted dropdown showing:
  - **⭐ Favourites** section (items with `is_favourite: true`, sorted A–Z)
  - **All Options** section (non-favourite items, sorted A–Z)
  - Each item has a ☆/★ button to toggle favourite (AJAX call, re-renders dropdown)
  - Typing in the input filters both sections in real-time
- Selecting an item sets the field value and closes the dropdown

**LENS field — special Pseudophakia sub-modal:**
When the user selects "Pseudophakia" (case-insensitive) from the LENS dropdown, a second modal opens:
- Title: "Pseudophakia — Right Eye / Left Eye"
- **Operation Type:** two toggle buttons — "Block" / "Phaco"
- **Operation Expense:** free text input (amount)
- **Hospital Name:** text input with autocomplete datalist from `referrers` master
- Saved under: `oe.pseudophakia_re` / `oe.pseudophakia_le` (nested object)

**OTHER row (row 11):**
- Plain text input for RE (`other_re`) and LE (`other_le`). No dropdown.

### 7.2 API Payload

```json
{
  "exam_data": {
    "oe": {
      "sac_re": "Clear",          "sac_le": "Clear",
      "lid_re": "Normal",         "lid_le": "Normal",
      "conj_re": "Normal",        "conj_le": "Congested",
      "cornea_re": "Clear",       "cornea_le": "Clear",
      "ac_re": "Deep",            "ac_le": "Deep",
      "iris_re": "Normal",        "iris_le": "Normal",
      "pupil_re": "RAPD",         "pupil_le": "Normal",
      "lens_re": "Pseudophakia",  "lens_le": "Nuclear",
      "em_re": "SAFE",            "em_le": "SAFE",
      "covertest_re": "Orthophoria", "covertest_le": "Orthophoria",
      "other_re": "Pterygium",    "other_le": "",
      "pseudophakia_re": {
        "operation_type": "Phaco",
        "operation_expense": "25000",
        "hospital_name": "City Eye Hospital"
      }
    }
  }
}
```

### 7.3 Mobile Implementation Requirements

1. Show all 11 rows in a scrollable table. Label column fixed, RE and LE columns share remaining width.
2. **Rows 1–10 (named fields):** Each RE/LE cell = `TextFormField` (editable, `readOnly: false`) + chevron suffix. Tapping opens a **bottom sheet** with:
   - A small search text field at top (filters list in real-time)
   - **Favourites** section header + favourite items
   - **All** section header + non-favourite items
   - Each item row: ☆/★ icon button (calls `toggle-favourite` API, updates list) + item text
   - Tapping an item fills the field and closes the sheet
3. **LENS field (row 8):** After selecting "Pseudophakia", show an **inline expansion panel** below the LENS row (not a separate dialog on mobile — cleaner UX):
   - Operation Type: two segmented buttons / chips (Block | Phaco)
   - Operation Expense: `TextFormField` (`keyboardType: number`)
   - Hospital Name: `TextFormField` with autocomplete suggestions from `referrers` master
   - Payload goes into `oe.pseudophakia_re` / `oe.pseudophakia_le`
   - If user changes LENS away from "Pseudophakia", hide this expansion and clear the pseudophakia fields
4. **OTHER row (row 11):** Plain `TextFormField` for RE and LE, no dropdown, `maxLines: 1`.
5. Favourite toggle: `POST /{slug}/masters/detail/{masterType}/{id}/toggle-favourite`
   - Master type per row: see table in 7.1 (e.g. LENS uses `lens_master`, not `lens`)

### 7.4 Master Types Reference

| O/E field | Master API type |
|-----------|----------------|
| sac | `sac` |
| lid | `lid` |
| conj | `conj` |
| cornea | `cornea` |
| ac | `ac` |
| iris | `iris` |
| pupil | `pupil` |
| lens | `lens_master` ← note: NOT `lens` |
| em | `em` |
| covertest | `covertest` |
| referrers (hospital list for Pseudophakia) | `referrers` (name-only, no favourite toggle needed) |

### 7.5 Current Gaps in Mobile

| Gap | Fix |
|-----|-----|
| Dropdowns may not show Favourites / All split with ☆ toggle | Rebuild O/E bottom sheet with fav/non-fav grouping + toggle API |
| LENS "Pseudophakia" sub-form missing | Add inline expansion below LENS row with operation_type / expense / hospital |
| `pseudophakia_re` / `pseudophakia_le` not in payload | Add to `_buildStepPayload` step 6 (O/E) |
| OTHER row may be missing | Add `other_re` and `other_le` plain text fields at bottom of table |

---

## 8. Fundus Examination

### 8.1 Web Behaviour

**Layout:** Two separate cards — one for Right Eye (RE), one for Left Eye (LE). Each card has a navy header ("Right Eye (RE)" / "Left Eye (LE)") and a single-row table with 3 columns.

**Columns per eye:**

| Column | Key suffix | Full label | Sub-label | Input type | Master |
|--------|------------|------------|-----------|------------|--------|
| Disc | `disc_{eye}` | Disc | CDR / Appearance | Editable text + chevron dropdown | `disc` |
| FR | `fr_{eye}` | FR | Foveal Reflex | Editable text + chevron dropdown | `fr` |
| Comment | `comment_{eye}` | Comment | Additional findings | `<textarea>` (multi-line, no dropdown) | — |

**Disc & FR dropdown behaviour:**
- Tapping the input (focus) opens a floating dropdown
- Dropdown items sorted: favourites first (A–Z), then non-favourites (A–Z) with "Other" divider
- Each item has a ☆/★ star button to toggle favourite (AJAX call)
- Typing in the input filters the list in real-time
- Selecting an item sets the value and closes the dropdown

**Comment field:**
- Plain `<textarea>` (2 rows)
- No dropdown, no master. Free text entry.

### 8.2 API Payload

```json
{
  "exam_data": {
    "fundus": {
      "disc_re": "0.3 CDR Normal",
      "disc_le": "0.4 CDR",
      "fr_re": "Present",
      "fr_le": "Absent",
      "comment_re": "Normal fundus — no haemorrhage",
      "comment_le": ""
    }
  }
}
```

### 8.3 Mobile Implementation Requirements

1. Two eye cards (RE then LE) stacked vertically, each with a navy header row.
2. Inside each card, three widgets in a row (or stacked on small screens):
   - **Disc:** `TextFormField` (editable) + chevron suffix → bottom sheet:
     - Search filter at top
     - Favourites section + All section, each item has ☆/★ toggle
     - Tapping item selects and closes
   - **FR:** Same pattern as Disc, uses `fr` master
   - **Comment:** `TextFormField(maxLines: 3, minLines: 2)` — no dropdown, full-width on small screens
3. Favourite toggle: `POST /{slug}/masters/detail/disc/{id}/toggle-favourite` and `.../fr/{id}/toggle-favourite`
4. On load: populate from `exam_data.fundus.disc_{eye}`, `fr_{eye}`, `comment_{eye}`.

### 8.4 Current Gaps in Mobile

| Gap | Fix |
|-----|-----|
| Disc/FR dropdowns may not have favourites section with ☆ toggle | Add fav grouping + toggle API call to bottom sheet |
| Comment field may be single-line | Change to `maxLines: 3` textarea |
| No star favourite button in Disc/FR sheet items | Add ☆/★ `IconButton` per item, calls toggle-favourite |

---

## 9. Master API Reference

All masters fetched from: `GET /api/v1/{slug}/masters/detail/{type}`

Response: `{ "data": [ { "id": 1, "value": "6/6", "is_favourite": true }, ... ] }`

| Master type | Used in | Notes |
|-------------|---------|-------|
| `complaints` | C/O | `item.value` = complaint text |
| `kcos` | K/C/O | `item.value` = condition text (API returns `kco` field, mapped to `value` in mobile model) |
| `hno` | H/O | `item.value` = history option text |
| `vn` | Vision VN, PG/ST Distance VN | Distance vision values |
| `pnvn` | Vision PnVN | Pinhole vision values |
| `nrvn` | Vision NrVN, PG Near VN | Near vision values (NOT used in ST near VN — ST has no near VN) |
| `sph_cyl` | PG SPH/CYL, ST Distance SPH/CYL, ST Near SPH (NS) | Strip sign; use absolute number |
| `axis` | PG/ST Distance Axis | Strip `+/-` prefix; plain numbers 0–180 |
| `nct` | NCT IOP | Integer mmHg values (8–40) |
| `sac` | O/E SAC | — |
| `lid` | O/E LID | — |
| `conj` | O/E CONJ | — |
| `cornea` | O/E CORNEA | — |
| `ac` | O/E AC | — |
| `iris` | O/E IRIS | — |
| `pupil` | O/E PUPIL | — |
| `lens_master` | O/E LENS | Note: API key is `lens_master`, not `lens` |
| `em` | O/E EM | — |
| `covertest` | O/E COVERTEST | — |
| `disc` | Fundus Disc | Supports favourites |
| `fr` | Fundus FR | Supports favourites |
| `referrers` | Fundus/O/E Pseudophakia hospital name | Name-only; no favourite toggle |

Favourite toggle endpoint: `POST /api/v1/{slug}/masters/detail/{type}/{id}/toggle-favourite`
Returns: `{ "is_favourite": true/false }`

---

## 10. Summary of All Required Mobile Fixes

### C/O
- [ ] Add `eye` field (RE/LE/Both/OU/blank) to each C/O row widget
- [ ] Add `eye` to `_CoRow.toJson()` payload
- [ ] Add favourite pills UI above search bar
- [ ] Favourite toggle API call on pill's ★ button

### K/C/O
- [ ] Fix default unit: `Years` (not `Days`)
- [ ] Favourite pills UI + toggle API

### H/O
- [ ] Replace multi-line textarea with chip-based input
- [ ] Search + dropdown from `hno` master
- [ ] Chips show selected H/O values; × removes them
- [ ] Payload: `exam_data.history` = comma-joined chip values
- [ ] Favourite pills + toggle API

### Vision
- [ ] Change `readOnly: items.isNotEmpty` → `readOnly: false` in `_masterVisionCell`

### PG
- [ ] **Rewrite SPH/CYL picker** — full-screen grid, sign-filtered, 0.00 chip, Custom + Apply, Clear
- [ ] Add **Axis disabled state** (disabled when CYL = empty/zero)
- [ ] Confirm Axis dropdown strips `+/-` from master values
- [ ] VN C GL: `vn` master for Distance row, `nrvn` for Near row

### ST
- [ ] Make NC (near CYL) a READONLY display mirroring DC — not editable
- [ ] Make NA (near Axis) a READONLY display mirroring AX — not editable
- [ ] Remove near VN input (NEAR row shows "—" for VN, not a dropdown)
- [ ] Add small "ADD: {value}" hint text below the NS chip
- [ ] Include `nc` and `na` in the ST save payload (equal to `dc` and `ax`)

### NCT
- [ ] Rebuild IOP input as editable field + **grid picker** from `nct` master (4–5 columns)
- [ ] Add IOP colour coding on the cell border (green/amber/red based on value)
- [ ] Add static IOP legend below cells (Normal 10–21, Borderline 22–24, High ≥25)

### O/E
- [ ] Rebuild all 10 named-field dropdowns with **Favourites / All split** + ☆/★ toggle API
- [ ] Add LENS **Pseudophakia inline expansion** (operation type, expense, hospital)
- [ ] Include `pseudophakia_re` / `pseudophakia_le` in step 6 payload
- [ ] Add **OTHER row** (`other_re`, `other_le`) as plain text fields at bottom

### Fundus
- [ ] Add **Favourites section + ☆/★ toggle** to Disc and FR bottom sheets
- [ ] Change Comment field to multi-line (`maxLines: 3`)
- [ ] Wire favourite toggle API for `disc` and `fr` master types
