# Mobile API Merge Plan

**Purpose:** Merge the mobile-app API work (built in `J:\laragon\www\eye-saas-hms`) into the
latest web codebase (cloned from GitHub into `J:\laragon\www\eye_care_new_clone\eye-saas-hms`)
without breaking any existing web functionality and without losing any mobile API functionality.

**Status:** Planning complete. Execution not started yet.

---

## 1. Background

Two people worked on the same GitHub repo (`ivalema35/eye-saas-hms`) in parallel, in two
separate local folders, without syncing with each other:

| Folder | Who | What | Git state |
|---|---|---|---|
| `J:\laragon\www\eye-saas-hms` | You (mobile dev) | Built REST APIs for the Flutter app | `main` @ `e41aa1d` (PR #62), + a large set of **uncommitted** local changes, never pushed |
| `J:\laragon\www\eye_care_new_clone\eye-saas-hms` | Web developer, then cloned by you | Client-requested web changes, pushed to GitHub | `main` @ `2e09e01` (PR #79), clean tree |

**Key fact that makes this mergeable cleanly:** `e41aa1d` (the commit your mobile folder is
based on) is a direct ancestor of `2e09e01` (the web folder's current HEAD). Verified with:

```
git merge-base --is-ancestor e41aa1d743224d69f2e8067770e59baabaae652a HEAD   # → true, in the new-clone repo
```

So this is a straightforward "one side committed 17 PRs, the other side has uncommitted local
work on an older base" situation — a normal 3-way git merge will work, not a manual file-by-file
reconciliation.

---

## 2. Full inventory of your mobile-side changes (uncommitted, in `eye-saas-hms`)

### 2.1 Modified tracked files (10)

| File | What changed | Touches web flow? | Verdict |
|---|---|---|---|
| `app/Http/Controllers/Api/AuthController.php` | +177/-? lines, API auth logic | No | Safe — pure API namespace |
| `app/Http/Controllers/Api/ExamApiController.php` | +10/-? | No | Safe |
| `app/Http/Controllers/Api/PatientApiController.php` | +341 lines | No | Safe |
| `app/Http/Middleware/CheckPermission.php` | Added Sanctum-token fallback **after** the existing session-guard check; original check untouched | Shared middleware, but additive-only | **Safe** — only activates when session guard has no user (never true for web session logins) |
| `app/Http/Requests/Hospital/Examination/StoreSecondaryExamRequest.php` | Added `'advice' => ['nullable','string','max:2000']` | Shared form request | **Safe** — nullable, backward compatible |
| `app/Services/Hospital/ExaminationService.php` | Changed `savePrimary`/`saveSecondaryExam` from **overwrite** to **array_merge** of `exam_data` with existing record | Shared service — web's `PrimaryExamController`/`SecondaryExamController` call the exact same methods | **Needs testing** (see §5.2) — safe in the common case (web form submits full exam_data every time), but if any section is conditionally omitted from a web submission, its old value will now persist instead of being cleared |
| `config/database.php` | Added `PDO::ATTR_TIMEOUT => 5` to mysql options | No (global, additive) | Safe |
| `database/seeders/DatabaseSeeder.php` | Added ~24 new seeder classes to the `$this->call([...])` array | **Real conflict** — web dev also added seeders to the same array | Manual merge required (see §4.1) |
| `resources/views/hospital/patients/index.blade.php` | Changed History link from `search=$p->patient_code` to `patient_ids=$p->id` | Web-facing blade view | **Safe** — confirmed `PatientHistoryController` (in both base and web dev's latest) already supports `patient_ids` as a param; this is a valid, already-supported improvement |
| `routes/api.php` | +193 lines, new route registrations | No | Safe |

### 2.2 New untracked files to bring over

**API controllers** (`app/Http/Controllers/Api/`): `ClinicalQueueApiController.php`,
`DashboardController.php`, `MasterApiController.php`, `MastersApiController.php`,
`MedicineApiController.php`, `MedicineGroupApiController.php`, `MedicineMasterApiController.php`,
`PatientHistoryApiController.php`, `ProfileApiController.php`, `ReportsApiController.php`,
`SettingsApiController.php`, `ShareHistoryApiController.php`, `UserApiController.php`

> Note: `MasterApiController` (singular) and `MastersApiController` (plural) are **both real,
> both used** — not a duplicate. Confirmed via `routes/api.php`: `Masters...` handles
> `masters/cases`, `masters/doctors`, `masters/locations`, `masters/slots`, `masters/referrers`;
> `Master...` (singular) handles `masters/detail/*`, `masters/case-types`, `masters/referrers-crud`,
> `masters/ot-slots`, `masters/ot-charge-heads`, `masters/ot-surgery-types`. Just a confusing name,
> not a bug.

**Models** (`app/Models/Hospital/OT/`): `OtChargeHead.php`, `OtSlot.php`, `OtSurgeryType.php`,
`OtType.php` — confirmed **actively used** in `MasterApiController.php` for OT master CRUD via the
API. The underlying DB tables (`ot_charge_heads`, `ot_slots`, etc.) already exist as of the common
base commit, so these models just need to be added, no new migrations required.

**Docs**: `docs/MOBILE_APP_PRD.md`, `docs/PRD_MASTER.md`, `docs/text.md` — plain documentation, no
code risk.

**Already present in both repos, no action needed**: `app/Http/Controllers/Api/OtApiController.php`
and `app/Http/Controllers/Api/SuperAdmin/TenantApiController.php` — these are tracked and
unchanged, part of the common base already.

### 2.3 Discard

- `first()` — an empty, oddly-named untracked file at the repo root. Almost certainly a stray
  artifact (e.g. an accidental shell redirect). **Delete, do not carry into the merge.**

---

## 3. What the web developer changed (`e41aa1d..2e09e01`, PR #63–#79)

139 files total. Breakdown:
- 104 blade view files — large rewrites of patients, exam, OT masters, dashboard, medicines,
  login/register pages, etc.
- 35 backend files — Hospital/Platform/SuperAdmin controllers, `Patient` model/service, new
  migrations (`add_hospital_code_to_tenants_table`, `fix_patients_location_id_fk...`,
  `widen_hospital_code_column...`), and new seeders (`SystemRolesSeeder` (modified),
  `DosageSeeder`, `MasterHnoSeeder`, `MedicineCategorySeeder`, `MedicineRouteSeeder`,
  `MedicineTypeSeeder`).

**Confirmed zero file-level overlap** with your changes except the two files listed in §4.

---

## 4. Real conflicts to resolve manually

### 4.1 `database/seeders/DatabaseSeeder.php`
Both sides added entries to the same `$this->call([...])` array.
- Your additions: `SystemRolesSeeder`, plus ~20 clinical/vision/anterior-segment master seeders
  (`ChiefComplaintSeeder`, `KcoSeeder`, `MasterVnSeeder`, `MasterSacSeeder`, etc.)
- Web dev's additions: `SystemRolesSeeder` (also!), `DosageSeeder`, `MasterHnoSeeder`,
  `MedicineCategorySeeder`, `MedicineRouteSeeder`, `MedicineTypeSeeder`
- **Action:** combine into one array, de-duplicate `SystemRolesSeeder` (appears on both sides —
  keep one), preserve the grouping/ordering comments, and keep seeders that have FK dependencies
  on each other in a safe order (e.g. anything referencing `hospital_users`/roles after
  `SystemRolesSeeder`/`PermissionsSeeder`).

### 4.2 `resources/views/hospital/patients/index.blade.php`
Your change is a 1-line link update inside a file the web dev rewrote extensively (2533 lines
changed). Git may or may not auto-merge this depending on how close the line is to rewritten
sections.
- **Action:** if git flags a conflict, manually reapply just the `patient_ids=$p->id` link change
  into the new version of the file. If git auto-merges it cleanly, just verify the line survived.

---

## 5. Step-by-step execution plan

### 5.1 Prep
1. In `eye-saas-hms`, delete the stray `first()` file.
2. In `eye-saas-hms`, create a branch from current HEAD and commit the mobile work onto it:
   ```
   git checkout -b mobile-api-work
   git add -A
   git commit -m "Mobile app API layer: controllers, OT models, shared-service fixes"
   ```

### 5.2 Merge into the new clone
3. In `eye_care_new_clone\eye-saas-hms`:
   ```
   git checkout -b merge/mobile-api
   git fetch "J:\laragon\www\eye-saas-hms" mobile-api-work:mobile-api-work
   git merge mobile-api-work
   ```
4. Resolve the two conflicts from §4 by hand.
5. Review (not necessarily rewrite) the large diffs in `AuthController.php` (177 lines) and
   `PatientApiController.php` (341 lines) once, just to sanity-check nothing unexpected slipped in.

### 5.3 Post-merge environment steps
6. `composer install` (in case any new package was referenced — unlikely, but check
   `composer.json`/`composer.lock` diffs between the two folders first).
7. `php artisan config:clear && php artisan route:clear && php artisan view:clear`
8. `php artisan migrate:status` — confirm no pending/missing migrations before testing.
9. `php artisan route:list --path=api` — sanity check all new mobile routes registered without
   collisions.

---

## 6. Testing checklist (must pass before this is considered done)

### 6.1 Mobile API smoke test (via Postman/curl, using a real hospital_user token)
- [ ] Auth: login, token issuance, `CheckPermission` fallback works for a Sanctum-authenticated
      request with no session (this is the whole point of the middleware change)
- [ ] Patient API: list, create, update, history
- [ ] Exam API: primary + secondary exam save via API, **then reload and confirm all previously
      saved sections are still present** (this is the direct test of the `ExaminationService`
      merge-logic change working as intended)
- [ ] Clinical queue, dashboard, master(s), medicine(s), profile, reports, settings, share-history,
      user endpoints — at least one request per controller to confirm routing + no fatal errors
- [ ] OT masters via API: OT types, OT slots, OT charge heads, OT surgery types — full CRUD,
      confirms the 4 new OT models work against the real tables

### 6.2 Web regression test (critical — this is the "did we break the web app" check)
- [ ] Patients index page loads, "History" link opens the correct patient's history
      (`patient_ids` param)
- [ ] **Primary exam save/edit on web:** save a primary exam with all sections filled, then edit
      it and remove/clear one section, save again, reload — confirm the cleared section is
      actually cleared (this is the specific edge case introduced by the merge-logic change in
      §2.1)
- [ ] **Secondary exam save/edit on web:** same test as above for secondary exams + advice field
- [ ] OT masters pages (charge heads, lens options, slots, surgery types, types) — since web dev
      heavily modified these views recently, confirm they still work after the merge
- [ ] Dashboard, medicine masters, patient history/print pages — quick smoke pass since these were
      also heavily touched by the web dev in PR #63–#79
- [ ] Run `php artisan migrate:fresh --seed` on a throwaway/test DB to confirm the merged
      `DatabaseSeeder.php` runs start-to-finish with no FK/order errors

---

## 7. Rollback plan

Everything happens on `merge/mobile-api`, a fresh branch — `main` in both folders is untouched
until you explicitly decide to merge/push. If anything goes wrong, just delete the branch and
start over; no risk to the working `main` branches.

---

## 8. Push checklist (only after §6 passes, and only when you say go)

- [ ] Final review of the diff on `merge/mobile-api` vs `main`
- [ ] Merge `merge/mobile-api` into `main` (in the new-clone folder)
- [ ] Push `main` to `origin`
- [ ] **Rotate the GitHub Personal Access Token** currently embedded in the `origin` remote URL
      (`https://ghp_...@github.com/...`) — it's in plaintext in git config and got displayed in
      a terminal session; revoke it on GitHub and reconfigure git to use a credential manager
      instead. Unrelated to the merge itself, but do this regardless.
