# Super Admin Module — Design Specification

**Companion to:** `SUPER_ADMIN_APP_PRD.md` (functional/API spec — read that for *what* each screen does; this file is *how it looks and flows*).
**App:** `eye_care_app` (Flutter). This file is self-contained enough to hand to a designer or design tool on its own.

> **v2 note:** This version replaces the first draft, which invented a separate dark-navy "Platform" visual identity (`PlatformColors`, a solid `#0F172A` command-center look). That was wrong — it didn't match the app that's actually built. Every value and pattern below is pulled directly from the real, shipped code (`lib/constants/app_colors.dart`, `app_theme.dart`, `home_screen.dart`, `app_drawer.dart`, `dashboard_screen.dart`, `roles_screen.dart`, `app_stat_card.dart`, `generic_master_screen.dart`, etc.). Super Admin screens should look like they were built by the same team, same week, as the rest of this app — because they are.

---

## 1. Design Principles

1. **Reuse the app's real design system exactly — don't invent a new one.** Same navy AppBar, same white rounded cards with the same soft shadow, same frosted-glass bottom nav pill, same drawer chrome, same stat-card recipe, same empty/error/pagination widgets already shipped in this app. The only thing that should look "new" about Super Admin screens is the content and nav labels — never the color palette, shape language, or component style.
2. **No separate color identity — and none is needed.** `AppColors.primary` (`#1B4F72`, navy) is only ever changed away from this default by `AppColors.applyTheme()`, which is called exactly once, on a **hospital** login (`AuthService._applyHospitalTheme`). A Super Admin never logs into a hospital, so as long as Platform login never calls `applyTheme()`, `AppColors` simply stays at its true default navy — which already reads as "the app," not a random hospital's white-label color. One small addition is needed for safety: add `AppColors.resetToDefault()` and call it when entering Platform mode (and on Platform logout), so a *stale* theme from an earlier hospital session in the same app run can never bleed into Super Admin screens.
3. **Command-center in *content density*, not in color.** The hospital app's tone is clinical/operational (queues, timers, exam forms). Super Admin's tone is oversight — more stat cards, more list/table screens, fewer multi-step wizards — but it's built from the exact same white-card-on-light-blue-background, navy-AppBar visual vocabulary as everything else in this app.
4. **Confirm before consequence.** Every status-changing action (Suspend, Archive, Delete, cascade-affecting edits) uses the app's existing `AlertDialog` confirm pattern (see `roles_screen.dart`'s delete dialog, `generic_master_screen.dart`'s delete dialog) — rounded corners, plain title + body text, Cancel + destructive-colored action button. Nothing new to build here.
5. **Reuse screens, don't rebuild them, where the shape already exists.** `GenericMasterScreen` (`lib/screens/generic_master_screen.dart`) already implements exactly the CRUD-list + bottom-sheet-form + toggle-favourite/active + delete-confirm pattern that most of Location Master and Medicine Master need. Parameterize and reuse it (or a very close variant) instead of designing new screens from scratch for those flat lookup tables.

---

## 2. Design Tokens — all reused as-is, nothing new

No new constants file. Every Super Admin screen imports the exact same four files every other screen imports: `app_colors.dart`, `app_radius.dart`, `app_spacing.dart`, `app_text_styles.dart`.

### 2.1 Colors (`lib/constants/app_colors.dart`)

| Token | Value | Where it's used today (and how Super Admin should use it) |
|---|---|---|
| `AppColors.primary` | `#1B4F72` (navy) — theme-overridable, but stays at this default in Platform mode (see Principle 2) | AppBar background, primary buttons, active nav/tab states, section-header accent bar |
| `AppColors.primaryLight` | `#2471A3` | Gradient end color for "hero" cards (e.g. a Dashboard greeting/summary card, mirroring `_buildGreetingCard`) |
| `AppColors.primaryDark` | `#154360` | Gradient end for darker accent cards (mirrors `_buildRevenueCard`) |
| `AppColors.secondary` | `#2E86C1` | Links, "View All →" chips, info accents |
| `AppColors.background` | `#EBF5FB` (very light blue) — also theme-overridable, stays default in Platform mode | `Scaffold.backgroundColor` for every Platform screen |
| `AppColors.green` | `#27AE60` | Status: Active / Success / Sent |
| `AppColors.orange` | `#E67E22` | Status: Grace / Pending / warning banners |
| `AppColors.red` | `#DC3545` | Status: Suspended / Failed / destructive actions |
| `AppColors.purple` | `#8E44AD` | Stat-card icon accent (misc KPI, e.g. "Total Hospitals") |
| `AppColors.teal` | `#1ABC9C` | Stat-card icon accent (secondary KPI) |
| `AppColors.textPrimary` | `#1E293B` | Body/heading text |
| `AppColors.textSecondary` | `#64748B` | Captions, labels, muted text |
| `AppColors.textDisabled` | `#94A3B8` | Inactive nav icons/labels |
| `AppColors.surface` | `#FFFFFF` | Card backgrounds |
| `AppColors.drawerBackground` | `#F3F4F7` | Drawer background |
| `AppColors.navBarBg` | `#F0FFFFFF` (translucent white) | Frosted-glass bottom nav fill |
| Alpha helpers | `AppColors.primaryA05` … `primaryA75` | Tinted borders/shadows/fills at various opacities — use these instead of ad hoc `.withValues(alpha:)` on primary |

**Status-badge color mapping** (uses the app's existing "tinted pill" recipe — see §2.3 — not a new component):

| Domain | Value → Color |
|---|---|
| Hospital status | `trial`→secondary blue · `active`→green · `grace`→orange · `suspended`→red · `inactive`→textDisabled gray |
| Payment status | `success`→green · `pending`→orange · `failed`→red |
| Notification status | `sent`→green · `failed`→red · `pending`→orange |
| Subscription status | `active`→green · `expired`/`cancelled`→red/gray |

### 2.2 Radius, Spacing, Text (unchanged, reused verbatim)
- `AppRadius`: `xs 4 · sm 8 · md 12 · lg 16 · xl 20 · xxl 28 · full 100`
- `AppSpacing`: `xs 4 · sm 8 · md 12 · lg 16 · xl 20 · xxl 24`, plus `pagePadding` (horizontal 16), `cardPadding` (all 16), `pageWithBottom` (16,12,16,110 — the 110 reserves room for the floating bottom nav).
- `AppTextStyles`: `headingLarge 20/700` · `headingMedium 16/700` · `headingSmall 14/700` · `sectionLabel 14/800 navy, letterSpacing 0.2` · `bodyLarge 14/400` · `bodyMedium 13/400` · `bodySmall 12/400 gray` · `labelMedium 12/600 gray` · `statNumber 22/800` · `cardTitle 14/600` · `cardSubtitle 12/400 gray`.
- `AppTheme.light`: Material 3, `scaffoldBackgroundColor: AppColors.background`, floating navy snackbar (rounded `md`), filled white inputs with rounded `md` borders (`primaryA12` normal, `primary` 1.5px focused, `red` error) — every `TextField`/`TextFormField` on Super Admin screens gets this automatically for free, no per-screen styling needed.

### 2.3 The two recurring visual "recipes" every screen reuses
These aren't named components in the codebase — they're patterns repeated inline across screens. Reuse them exactly:

**Soft white card:** `color: Colors.white`, `borderRadius: BorderRadius.circular(AppRadius.lg)` (16), `boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.06), blurRadius: 10, offset: Offset(0,3))]` — this is the *only* shadow style used anywhere (dashboard cards, stat cards, drawer clusters). Never use a heavier/darker shadow.

**Tinted status pill:** `padding: symmetric(h:8-10, v:3-4)`, `decoration: BoxDecoration(color: statusColor.withValues(alpha: 0.10-0.13), borderRadius: BorderRadius.circular(AppRadius.xl))`, `child: Text(label, style: TextStyle(fontSize: 9-11, fontWeight: FontWeight.w700, color: statusColor))` — used for stat-card badges, wait-time pills, and should be used for every Hospital/Payment/Subscription/Notification status badge in this module too. (Worth extracting into a small shared `StatusBadge(label, color)` widget in `lib/widgets/` since Super Admin introduces ~4 more status enums that all want this exact same look — the recipe already exists in 3+ places, this just gives it one name.)

---

## 3. Components — what's already reusable vs. the few genuinely new pieces

### 3.1 Reused directly, zero changes
`AppSearchBar`, `AppSectionHeader`, `AppStatCard`, `AppEmptyState`, `AppErrorState`-equivalent (the dashboard's `_buildError()` pattern — wifi-off icon, tinted primary, retry button — is the nicer of the two error-state variants in the app; standardize on it), `AppPaginationBar`, `Skeleton`, `showAppSnackBar`, `PressScaleWrapper`, `AnimatedListItem`, `appRoute()` page transition, the `AlertDialog` confirm pattern, the `showModalBottomSheet(isScrollControlled: true, backgroundColor: Colors.white, shape: roundedTop(24))` bottom-sheet pattern.

### 3.2 Reused with light extension
- **`GenericMasterScreen`** — already takes `title`, `apiPath`, `accentColor`, `icon`, `hasFavourite`. Use it as-is (new `apiPath`s, new `accentColor`s) for every flat Medicine Master tab (Dosages, Types, Categories, Routes) and the simplest Location Master data if it had no hierarchy. For the hierarchical Location tabs (States filtered by Country, Districts by State, Cities by State+District) and the richer Medicines tab (Type + Dosage dropdowns, price, composition), build a close sibling — same list/sheet/toggle/delete shell, with added cascading-filter dropdowns above the list and a richer form inside the sheet. Don't reinvent the shell.
- **Bottom nav / App shell** — reuse `HomeScreen`'s exact structure (frosted-glass floating pill, `AppDrawer`-style accordion drawer) for a new `PlatformHomeScreen`, swapping only the nav items and drawer sections.

### 3.3 Genuinely new (small)
- **`StatusBadge(label, color)`** widget — formalizes the tinted-pill recipe (§2.3) as one reusable widget instead of a fourth inline copy.
- **Chart cards** — the Dashboard needs 4 charts (bar/donut/line/pie) the app has never rendered before. Add `fl_chart` as a new dependency; wrap each chart in the same "soft white card" recipe (§2.3) with an `AppSectionHeader` above it — no new card component needed, just a chart inside the existing card shell.
- **Cascading-filter master screen variant** (§3.2) for Location Master's hierarchy.

That's the entire new-build surface — everything else is assembly of existing pieces.

---

## 4. Navigation Structure

**Entry:** `LoginScreen` → a small `TextButton` below the existing Login button, muted gray, low visual weight: *"Platform Super Admin? Login here"* → `PlatformLoginScreen`.

**`PlatformLoginScreen`:** same visual family as the existing `LoginScreen` (same background, same white rounded card, same entrance choreography if you want the polish — logo scale-in, staggered fade/slide) but simpler content: just Email + Password (no hospital-discovery step, since a Super Admin isn't tenant-scoped) and a small "Super Admin" label near the logo to set context. A back link returns to the normal `LoginScreen`.

**Shell — `PlatformHomeScreen`:** built exactly like `HomeScreen` (§5.1):
- AppBar: solid `AppColors.primary`, hamburger leading icon opening the drawer, title Column (bold white title + smaller white-70%-alpha subtitle — same two-line pattern as `dashboard_screen.dart`'s AppBar), a circular initials avatar on the right (same as the hospital dashboard's AppBar avatar).
- Bottom nav: the same floating frosted-glass pill (`BackdropFilter` blur, `navBarBg`, rounded `xxl`, `AppRadius.lg`-highlighted active icon) — 4 items:

| Tab | Icon (Material, `_rounded` family) | Screens |
|---|---|---|
| Dashboard | `Icons.grid_view_rounded` | `PlatformDashboardScreen` |
| Hospitals | `Icons.local_hospital_rounded` | `PlatformHospitalsScreen` → detail → create/edit |
| Billing | `Icons.receipt_long_rounded` | `PlatformPaymentsScreen` / `PlatformSubscriptionsScreen` (segmented toggle between the two) |
| More | `Icons.menu_rounded` (opens drawer, doesn't switch tab — matches how "More" areas already work via the drawer elsewhere in this app) | — |

**Drawer:** same structure as `AppDrawer` — navy header block (avatar-with-initials + title + small caps subtitle, but reading "SUPER ADMIN" + "PLATFORM CONSOLE" instead of a hospital name), accordion clusters on `drawerBackground`, footer with a white "My Profile" row card + outlined "LOGOUT ACCOUNT" button + tiny version caption. Sections: Audit Logs, Notifications, Plans, Location Master, Medicine Master, Settings, My Profile, Logout.

---

## 5. Screen-by-Screen Specification

### 5.1 `PlatformLoginScreen`
Full-screen `AppColors.background` (or the login screen's existing background treatment if it differs), centered white rounded card (`AppRadius.xl`, soft shadow). Small logo, "Super Admin" caption, Email field, Password field (obscure toggle — reuse the existing `LoginScreen`'s eye-icon pattern), primary navy submit button, back-link to staff login. Error state: existing shake-card + red inline error text pattern from `LoginScreen`.

### 5.2 `PlatformHomeScreen` (shell)
As described in §4 — navy AppBar with two-line title, frosted-glass bottom nav, `AppDrawer`-style drawer. `PopScope` returns to Dashboard tab on back press (same as `HomeScreen`).

### 5.3 `PlatformDashboardScreen`
Mirrors `dashboard_screen.dart`'s structure closely:
- AppBar: "Super Admin" / "Platform Control" two-line title, hamburger leading, avatar action.
- Optional gradient "hero" card at top (primary→primaryLight, same recipe as `_buildGreetingCard`) — e.g. "Good Morning" + admin's name + a couple of pill chips (today's date, total active hospitals).
- `AppSectionHeader("Overview")` then two horizontally-scrollable rows of `AppStatCard`/inline-stat-card widgets (152px wide, white, left 4px accent strip, icon box, `statNumber`-styled big number, `cardSubtitle` label) — Total Hospitals / Active / Trial / Grace, then MRR / This Month / Expiring This Week / (spare slot).
- 4 chart cards (§3.3), each a soft white card with an `AppSectionHeader` above it: Revenue Trend (bar), Status Distribution (donut + custom legend row, same "colored dot + label" legend style as elsewhere in this app), New Registrations (line/area), Subscription Cycles (pie + legend).
- "Recently Registered Hospitals" — reuse the primary-queue-list visual pattern from `_buildPrimaryQueue` (white card, header row with a count pill, divider, list rows with avatar-initials-circle + name/subtitle + trailing chevron) for the 8 most recent hospitals.
- Loading: same skeleton-block pattern as `_buildSkeleton()` (light gray rounded rectangles). Error: same `_buildError()` pattern (wifi-off icon, tinted primary, "Retry" button).

### 5.4 `PlatformHospitalsScreen`
Same shell as `roles_screen.dart`/list screens generally: navy AppBar (title "Hospitals" + hospital-count subtitle), `AppSearchBar` below it, a horizontal filter-chip row (status), a list of soft white cards (hospital name bold, slug gray subtitle, admin+city small text, `StatusBadge` top-right, chevron), `FloatingActionButton.extended` (navy bg, white icon+label, "New Hospital"), `AppPaginationBar` at the bottom, `AppEmptyState` when filters match nothing.

### 5.5 `PlatformHospitalFormScreen` (create & edit)
Standard form screen: navy AppBar with back arrow, scrollable white-card-grouped form (reusing the app's themed `TextField`s — filled white, rounded `md`, no manual styling needed since `AppTheme.light` already provides it). Create: Name → auto-suggested editable Slug (green check / red "x" availability hint) → Admin Name/Email/Phone → Password → City/State → Plan (a 3-option segmented control, or simple `ChoiceChip` row matching the app's existing chip styling). Edit: same minus Password/Plan, Email field disabled/grayed, an orange-tinted warning banner (same recipe as the app's existing subscription-expiry banner, `#FEF3C7` bg / `#F59E0B` border) shown when the Slug field is touched.

### 5.6 `PlatformHospitalDetailScreen`
Navy AppBar (hospital name as title), edit-pencil action. Stat strip: 4 compact `AppStatCard`-style tiles (Status badge / Trial Ends / Subscriptions count / Total Paid). "Hospital Information" soft white card (label/value rows). "Quick Actions" soft white card with a `Wrap` of pill-shaped `OutlinedButton`s (colored per action: green Activate, red Suspend, orange Extend Grace w/ inline stepper, navy Re-seed, red-filled Archive) — destructive ones route through the standard `AlertDialog` confirm. Outlined "Open Portal ↗" button (external link icon) below. Subscription History and Payment History as stacked soft-white mini-cards (not `DataTable` — matches the app's card-list convention everywhere else, e.g. `_buildReceptionTable`'s row style, just without the literal table grid).

### 5.7 `PlatformPaymentsScreen`
Navy AppBar, 3 `AppStatCard`s (Total Revenue / This Month / Pending), filter-chip row (status, method) + date-range button, list of soft-white payment-row cards (hospital name, big bold amount, cycle+method badges, `StatusBadge`, trailing PDF icon on success rows), `FloatingActionButton.extended` "Record Payment" opening the standard bottom sheet (rounded-top-24 white sheet) with the offline-payment form.

### 5.8 `PlatformSubscriptionsScreen`
Read-only variant of the same list pattern — 3 `AppStatCard`s, soft-white rows (hospital, cycle badge, `StatusBadge`, date range), no FAB.

### 5.9 `PlatformAuditLogsScreen`
Navy AppBar, filter row (search + hospital dropdown + date range), soft-white log-row cards (colored action badge using the tinted-pill recipe, description, hospital chip or "Platform" tag, admin name, monospace IP, timestamp), `AppPaginationBar`.

### 5.10 `PlatformNotificationsScreen`
Navy AppBar, top segmented control (Compose / History — reuse whatever segmented-control widget style the app already uses for similar binary toggles, or a simple `ToggleButtons`/two-chip row styled with `AppColors.primary`). Compose: white card form (Subject, Message textarea with char counter, Recipients segmented, conditional multi-select chips, live "Sending to: N" info banner styled like the existing subscription-warning banner but in secondary blue instead of orange, Send button). History: soft-white rows (hospital, subject, `StatusBadge`, sent date).

### 5.11 `PlatformSettingsScreen`
Navy AppBar, 4 collapsible `ExpansionTile`-style sections inside soft-white cards (General / Razorpay / Email-SMTP / Pricing), themed `TextField`s throughout (secrets use obscure-toggle password fields with "leave blank to keep existing" hint text), single full-width navy "Save Changes" button at the end.

### 5.12 `PlatformPlansScreen`
Navy AppBar, thin info banner (Trial/Grace days), 3 pricing soft-white cards (Monthly / Quarterly-highlighted-with-secondary-blue-tint-and-"Most Popular" `StatusBadge`-style tag / Yearly-"Best Value") each with a checklist of features (small green check icons). "Edit Pricing" → full-screen form (not a dialog): themed number fields + a Features repeater (each row = themed text field + red "x" remove icon, "+ Add Feature" text button below, matching the app's existing `TextButton.icon` style).

### 5.13 `PlatformLocationMasterScreen`
Navy AppBar, a `TabBar` (4 tabs: Countries/States/Districts/Cities) styled with `AppColors.primary` indicator on white background (matches the app's navy-on-white visual language; no dark tab bar). Each tab reuses the `GenericMasterScreen`-style shell (§3.2): search bar, cascading filter dropdowns where relevant, soft-white list rows with a toggle switch (`AppColors.green` when on) + edit/delete icon-buttons, `FloatingActionButton.extended` "Add [Country/State/...]" opening the standard bottom sheet. Delete confirms use the standard `AlertDialog` pattern with type-specific warning copy (cascade-deletes children, or "unassigns" cities for District deletes). A disabled/grayed "Import from Excel" button with "web only for now" subtext stays visible for discoverability.

### 5.14 `PlatformMedicineMasterScreen`
Same `TabBar` + `GenericMasterScreen`-reuse pattern as Location Master, 5 tabs (Dosages/Types/Categories/Routes/Medicines). Dosages/Types/Categories/Routes: use `GenericMasterScreen` directly (name + active toggle + edit/delete). Medicines tab: richer bottom-sheet form (Type dropdown, Dosage dropdown, Name, Duration, Qty, Composition multiline, Company, Price) inside the same shell. Delete-confirm copy for the first 4 tabs notes it won't retroactively remove the item from hospitals already using it.

### 5.15 `PlatformProfileScreen`
Navy AppBar "My Profile". Two soft-white cards: Account Info (centered navy circular avatar with initials, Name/Email themed fields, read-only Role `StatusBadge`, "Last login" caption, Save button) and Change Password (Current/New/Confirm fields with obscure toggles matching the existing `ProfileScreen`'s pattern, policy hint caption, Save button). Outlined red "Log Out" button below both cards.

---

## 6. Interaction & Motion

Unchanged from the app's existing conventions — nothing new to design here:
- Page transitions: `appRoute()` (fade + slight scale/slide, ~220–280ms).
- Tap feedback: `PressScaleWrapper` on cards/buttons/list rows.
- List entrance: `AnimatedListItem` stagger on first load of list screens (Hospitals, Payments, Audit Logs).
- Toasts: `showAppSnackBar` (navy background, floating, rounded `md`) for every success/error/info message.
- Stat numbers: count-up animation on load (`AnimationController` + `CurvedAnimation`, same as `dashboard_screen.dart`'s `_countCtrl`/`_countAnim`) — reuse verbatim for the Platform dashboard's stat cards.
- Confirm dialogs: default `AlertDialog` transition — deliberately unadorned, "stop and think," matching how destructive confirms already behave elsewhere in the app.

---

## 7. Accessibility & Responsive Notes

- Status badges always carry a text label, never color alone.
- Minimum 44×44 tap target for icon-only actions (toggle switches, edit/delete icons in list rows).
- Forms use visible labels (the app's themed `InputDecoration` already renders labels, not placeholder-only) with inline error text below the field.
- Subscription/Payment history on Hospital Detail: stacked soft-white mini-cards, not a horizontally-scrolling `DataTable` — consistent with how the rest of the app avoids `DataTable`s.
- Navy (`#1B4F72`) against white text comfortably clears WCAG AA; double-check `AppColors.secondary` (`#2E86C1`) text on white backgrounds specifically (it's used for links/chips) before shipping, since it's a lighter blue than primary.

---

## 8. Screen-by-Screen AI Generation Prompts

Each box is **self-contained** — copy just that one box into your AI design tool. Every prompt repeats the real style system on purpose (navy `#1B4F72`, light blue background `#EBF5FB`, white soft-shadow cards, frosted-glass bottom nav) since the tool won't have read the rest of this document.

**Base style system (repeated in every prompt below):**
Primary navy `#1B4F72`, primary-light `#2471A3` (used in gradients), secondary blue `#2E86C1`, background light blue `#EBF5FB`, surface white `#FFFFFF`, text primary `#1E293B`, text secondary/gray `#64748B`, success green `#27AE60`, warning orange `#E67E22`, danger red `#DC3545`, accent purple `#8E44AD`, accent teal `#1ABC9C`. Cards: white, corner radius 16px, soft shadow (6% black opacity, 10px blur, offset 0/3 — very subtle, not heavy). Buttons/inputs: corner radius 12px. Status/info pills: corner radius fully-rounded (pill shape), background = status color at ~10-13% opacity, text = solid status color, bold, small (9-11px). Typography: clean sans-serif, bold headings (700-900 weight), Material "rounded" icon style (icon names ending in "_rounded"). Mobile portrait, 375×812pt frame, status bar visible.

---

### 8.1 — Platform Login Screen

```
Design a mobile login screen, portrait 375×812pt, for the "Super Admin" section of a
healthcare SaaS platform app called Eye Care HMS. Background: light blue #EBF5FB (soft,
airy — not dark). Centered content:

1. A small rounded-square logo mark (64x64, navy #1B4F72 background, white icon) at the
   top, with the app name below it in navy, bold.
2. Below that, small caption text "SUPER ADMIN" in navy, bold, letter-spaced, uppercase,
   12px.
3. A white rounded card (radius 20px, soft shadow — very subtle, 6% black opacity blur)
   containing:
   - Heading "Welcome back" (navy, bold, 22px)
   - Subtext "Sign in to manage the platform" (gray #64748B, 14px)
   - "Email" labeled input, white fill, rounded 12px border in light navy-tinted gray,
     navy border + slightly thicker when focused
   - "Password" labeled input, same style, with an eye icon to toggle visibility
   - A full-width solid navy #1B4F72 button, white bold text "Login", rounded 12px,
     48px height
4. Below the card, a small muted-gray text link: "← Back to Hospital Staff Login"

Show an error-state variant too: the card shifted slightly (shake gesture cue), a red
#DC3545 bordered password field, small red error text "Invalid email or password" below
it.

Mood: same clean, professional, navy-and-white healthcare-SaaS look as the rest of this
app — not a separate dark "admin console" theme, just a simpler version of the same
login screen.
```

---

### 8.2 — App Shell / Bottom Navigation (`PlatformHomeScreen`)

```
Design a mobile app shell frame, portrait 375×812pt, for the "Super Admin" section of a
healthcare SaaS app (Eye Care HMS). Background: light blue #EBF5FB.

Top: a solid navy #1B4F72 app bar, no elevation/shadow, white content. Left: a hamburger
menu icon (24px, white). Title area: two lines, left-aligned — bold white "Super Admin"
(15px) above smaller white-70%-opacity "Platform Control" (11px). Right: a white circular
avatar (36px) with navy initials "SA" on a 20%-opacity white fill.

Bottom: a floating pill-shaped navigation bar — NOT edge-to-edge, inset ~16px from both
sides and ~12px from the bottom, height 68px, fully rounded corners (28px radius),
translucent frosted-glass white background (blurred backdrop effect), soft shadow
underneath. Inside, 4 evenly-spaced nav items, each an icon above a small label:
1. Grid icon — "Dashboard" — ACTIVE state: icon and label in navy #1B4F72, bold, sitting
   inside a small rounded-pill highlight (light navy tint background, ~12% opacity)
2. Hospital/building icon — "Hospitals" — inactive: icon and label in muted gray #94A3B8
3. Receipt icon — "Billing" — inactive
4. Menu/hamburger icon — "More" — inactive

Body: light blue background, empty/placeholder (generic light gray rounded rectangles
suggesting card content).

Also show the drawer variant: sliding panel from the left, white-ish light gray
background (#F3F4F7), top block solid navy #1B4F72 with a white circular avatar
(initials) + "SUPER ADMIN" bold white title + tiny "PLATFORM CONSOLE" caption below it
in white 60%-opacity letter-spaced text. Below the header: rounded white card sections
(soft shadow) listing menu rows with icons — Audit Logs, Notifications, Plans, Location
Master, Medicine Master, Settings, My Profile. At the bottom: a light gray footer strip
with a white rounded "profile" card row (avatar + name + role + chevron) and, below it,
a full-width outlined button (white fill, navy border and text) reading "LOGOUT ACCOUNT".

Mood: identical visual language to the rest of this healthcare app — soft, light,
navy-accented, rounded, approachable — not a separate dark "admin" skin.
```

---

### 8.3 — Dashboard (`PlatformDashboardScreen`)

```
Design a mobile dashboard screen, portrait 375×812pt, for the "Super Admin" section of a
healthcare SaaS app. Background light blue #EBF5FB. Top app bar solid navy #1B4F72, two
line title "Super Admin" / "Platform Control", hamburger left, white avatar circle right.

Scrollable content, top to bottom:

1. A gradient hero card (navy #1B4F72 to lighter navy-blue #2471A3, diagonal gradient,
   rounded 20px corners, soft navy-tinted shadow) — "Good Morning" in small white 70%-
   opacity text, admin's name large bold white below it, then 2 small pill chips (white
   15%-opacity fill, rounded) showing today's date and "128 hospitals". To the right, a
   translucent white box showing a big white number (e.g. "5") and small caption "Needs
   Attention" (expiring-soon count).

2. Section header: a small 3px navy vertical accent bar + bold navy label "Overview".

3. A horizontally-scrollable row of 4 white stat cards (152px wide, rounded 16px, subtle
   shadow, left edge has a 4px colored accent strip): each card has a small icon in a
   tinted rounded box top-left, a big bold number (26px, navy or dark), and a small gray
   label below. Cards: "Total Hospitals 128" (purple accent), "Active 94" (green accent),
   "On Trial 21" (blue accent), "Grace Period 8" (orange accent).

4. A second horizontally-scrollable row, same card style: "Monthly Revenue ₹8.4L" (green),
   "This Month ₹1.2L" (teal), "Expiring This Week 5" (red, with a small red dot/badge).

5. Section header "Revenue Trend" above a white rounded card (soft shadow) containing a
   simple bar chart — 6 navy-blue bars for the last 6 months, light gray gridlines, ₹
   values on the y-axis.

6. Section header "Hospital Status" above a white card with a donut chart (thick ring)
   showing 5 colored segments (green=Active, blue=Trial, orange=Grace, red=Suspended,
   gray=Inactive) and a small legend row below with colored dots + labels + counts.

7. Section header "New Registrations" above a white card with a smooth line chart and a
   soft navy gradient fill beneath the line, 6 months.

8. Section header "Subscription Cycles" above a white card with a small pie chart
   (navy/teal/purple slices for Monthly/Quarterly/Yearly) + legend.

9. Section header "Recently Registered Hospitals" above a white rounded card: a header
   row with a small green "8 new" pill, then a divided list of compact rows — each with
   a colored circular initials avatar, hospital name (bold) + slug (gray, smaller) below
   it, a status pill on the right, and the row is tappable.

Also show a loading-state variant: same layout, all cards replaced with plain light-gray
rounded-rectangle skeleton blocks of matching size.

Mood: warm, light, professional healthcare-SaaS — navy accents on white cards over a
soft light-blue backdrop, not a dark "command center."
```

---

### 8.4 — Hospitals List (`PlatformHospitalsScreen`)

```
Design a mobile list screen, portrait 375×812pt, titled "Hospitals", part of a healthcare
SaaS Super Admin app. Background light blue #EBF5FB. Top app bar solid navy #1B4F72,
title "Hospitals" with a small gray-white subtitle "128 total", back/hamburger icon left.

Below the app bar: a white rounded search input (radius 12px, light navy-tinted border),
gray magnifying-glass icon prefix, placeholder "Search by name, slug, email, city...".

Below that: a horizontal scrollable row of filter chips — "All" (selected: solid navy
fill, white text), "Trial" (outlined blue), "Active" (outlined green), "Grace" (outlined
orange), "Suspended" (outlined red), "Inactive" (outlined gray) — unselected chips are
white with a thin colored border and colored text.

Below that: a vertical list of white rounded cards (16px radius, soft subtle shadow,
12px gap between cards), each showing:
- Hospital name (bold navy, 16px) e.g. "Aakash Eye Hospital"
- Slug below in gray smaller text, e.g. "aakasheye"
- A row with admin name + city in small gray text
- A colored status pill top-right (small, rounded-full, tinted background + solid
  colored text) e.g. green "Active" or orange "Grace"
- A small gray chevron-right icon at the far right

Show 5 example cards with different statuses.

Bottom-right: a floating action button — navy #1B4F72 pill/stadium shape, white "+" icon
and "New Hospital" label, soft shadow, sitting above the (not-shown-here) bottom nav.

At the bottom: a simple pagination control — two small rounded-square icon buttons
(chevron-left / chevron-right, light navy-tinted background) flanking centered text
"2 / 7".

Also show an empty-state variant: centered on the page, a large light-navy-tinted outline
icon (inbox or building), gray text "No hospitals found" below it, and a small "Refresh"
text button in navy.

Mood: clean, light, scannable — same visual family as a patient list screen in this app,
just showing hospital accounts instead of patients.
```

---

### 8.5 — Hospital Create / Edit Form (`PlatformHospitalFormScreen`)

```
Design a mobile form screen, portrait 375×812pt, titled "New Hospital", part of a
healthcare SaaS Super Admin app used to onboard a new hospital tenant. Background light
blue #EBF5FB. Top app bar solid navy #1B4F72, white back arrow, centered white title
"New Hospital".

Scrollable form: white rounded card containers (16px radius, soft shadow) grouping
fields, each field has a visible label above a rounded-12px white input with a subtle
light-navy border (border turns solid navy when focused — same input style as the rest
of the app):

1. "Hospital Name" text input
2. "Hospital Slug" text input, auto-filled as the name is typed, with a small green
   checkmark + "Available" text below in green, helper caption "Used in the hospital's
   login URL" in gray
3. "Admin Full Name" text input
4. "Admin Email" text input with a small mail icon
5. "Admin Phone" text input with a "+91" prefix chip
6. "Password" input with an eye-toggle icon, helper text below in gray
7. "City" and "State" — two inputs side by side
8. "Plan" — a row of 3 selectable chips: "Monthly" (selected: solid navy fill, white
   text), "Quarterly", "Yearly" (unselected: white with navy border/text) — small gray
   helper text below: "14-day free trial applies regardless"

Bottom: a full-width solid navy button "Create Hospital", rounded 12px, 48px tall, with
a light-gray disabled-state variant shown too (grayed fill and text).

Also show the "Edit Hospital" variant: same layout, title "Edit Hospital", no Password or
Plan fields, Email field shown disabled/grayed with a small lock icon, and — appearing
only once the Slug field is edited — a thin orange-tinted warning banner (light amber
background #FEF3C7, amber border) reading "Changing this changes the hospital's login
URL."

Mood: same clean form style as the rest of this healthcare app — nothing separate or
"more serious" looking, just another form screen.
```

---

### 8.6 — Hospital Detail (`PlatformHospitalDetailScreen`)

```
Design a mobile detail screen, portrait 375×812pt, showing one hospital's account — part
of a healthcare SaaS Super Admin app. Background light blue #EBF5FB. Top app bar solid
navy #1B4F72, back arrow, hospital name as title ("Aakash Eye Hospital"), small edit-
pencil icon action on the right.

1. A row of 4 compact white stat tiles just below the app bar (rounded 12px, soft
   shadow, small gray label above a bold value): "Status" (green "Active" pill),
   "Trial Ends" ("12 Aug 2026"), "Subscriptions" ("3"), "Total Paid" ("₹24,970").

2. White rounded card "Hospital Information": label/value rows in two columns — Name,
   Slug/URL (with a small copy icon), Admin Name, Admin Email, Admin Phone, City/State,
   Registered On. Labels small gray, values bold dark.

3. White rounded card "Quick Actions": a wrapped row of pill-shaped outlined buttons —
   "Activate" (green outline+text), "Suspend" (red outline+text), "Extend Grace" (orange
   outline — tapping reveals a small inline "7 days" stepper), "Re-seed Masters" (navy
   outline), "Archive" (solid red fill, more visually weighted than the others since
   it's the most destructive).

4. A navy-outlined full-width button "Open Portal ↗" with an external-link icon.

5. Section header "Subscription History" above a white card containing 2 stacked mini-
   rows (not a grid table): each shows a small cycle pill ("Quarterly"), bold price
   "₹2,427", a date range in gray, and a status pill.

6. Section header "Payment History" above a white card with similarly stacked rows:
   bold amount, method icon (wifi=online blue circle / cash=offline orange circle),
   transaction ID in gray monospace, status pill, paid date.

Also show a confirm-dialog overlay: a centered white rounded modal over a dimmed
background, warning icon, bold title "Suspend this hospital?", plain body text, and two
buttons — gray-outlined "Cancel" and solid red "Suspend".

Mood: information-dense but calm — white cards, soft shadows, navy accents, exactly like
a patient-detail or booking-detail screen elsewhere in this app.
```

---

### 8.7 — Payments (`PlatformPaymentsScreen`)

```
Design a mobile list screen, portrait 375×812pt, titled "Payments" — part of a healthcare
SaaS Super Admin app. Background light blue #EBF5FB. Top app bar solid navy #1B4F72.

Below app bar: 3 compact white stat cards side by side (rounded 12px, soft shadow, left
accent strip): "Total Revenue ₹8.4L" (green), "This Month ₹1.2L" (teal), "Pending 3"
(orange).

Below that: filter chips row — "Success" (green outline), "Pending" (orange outline),
"Failed" (red outline) — plus a small "Date Range" button with a calendar icon.

Below that: a vertical list of white rounded cards, each row: hospital name bold top-
left, large bold amount top-right (e.g. "₹2,427"), a second row with a gray cycle pill
("Quarterly") and a small circular method icon (blue wifi icon = Online / orange cash
icon = Offline), then a status pill and paid date in gray, small text. A PDF/download
icon shown on the far right only for successful payments.

Bottom-right: navy floating action button "Record Payment" with a "+" icon.

Also show the "Record Payment" bottom sheet: a white sheet sliding up from the bottom
with rounded top corners (24px) and a small gray drag-handle bar, containing: "Hospital"
searchable dropdown, "Amount (₹)" number input, "Billing Cycle" 3-chip selector, optional
"Transaction/Cheque #" input, optional "Notes" input, a light-blue info banner ("This will
also activate the hospital if not already active"), and a full-width navy "Record
Payment" submit button.

Mood: precise and financial but still warm/light — same navy-and-white card language as
everything else, green/orange/red only used for status meaning, not decoration.
```

---

### 8.8 — Subscriptions (`PlatformSubscriptionsScreen`)

```
Design a mobile read-only list screen, portrait 375×812pt, titled "Subscriptions" — part
of a healthcare SaaS Super Admin app. Background light blue #EBF5FB. Top navy app bar.

3 compact white stat cards below the app bar: "Total 142", "Active 94" (green accent),
"Expired 12" (red accent).

A vertical list of white rounded cards (no floating action button — view-only screen),
each row: hospital name bold + slug gray subtitle, a cycle/plan pill on the right
("Monthly" blue / "Quarterly" purple / "Yearly" green), below it a status pill ("Active"
green / "Expired" red / "Cancelled" gray) and a gray date-range caption, with a trailing
chevron.

Mood: calm, read-only report list — same card language as the rest of the app, no
destructive or create affordances anywhere on this screen.
```

---

### 8.9 — Audit Logs (`PlatformAuditLogsScreen`)

```
Design a mobile read-only list screen, portrait 375×812pt, titled "Audit Logs" — an
immutable action history in a healthcare SaaS Super Admin app. Background light blue
#EBF5FB. Top navy app bar with a filter icon action.

A filter row below the app bar: a search input, a "Hospital" dropdown, a "Date Range"
button.

A vertical list of compact white rounded cards, each row: a small colored tinted-pill
action badge at the top ("hospital.activated" blue, "hospital.suspended" red,
"hospital.created.manual" green, "hospital.archived" orange), a plain description line
below it in dark text, then a second row with a small hospital-name chip (or a gray
"Platform" tag if not hospital-specific), the admin's name, and a monospace gray IP
address, with a timestamp in the bottom-right corner.

Show 5 rows covering different action colors.

Mood: terse and log-like but still using the app's soft white-card-on-light-blue style —
not a raw monospace terminal look.
```

---

### 8.10 — Notifications (`PlatformNotificationsScreen`)

```
Design a mobile screen, portrait 375×812pt, titled "Notifications" — a platform-wide
broadcast tool in a healthcare SaaS Super Admin app. Background light blue #EBF5FB. Top
navy app bar.

A 2-option segmented toggle below the app bar: "Compose" (selected — solid navy fill,
white text) and "History" (unselected — white with navy text).

Compose view: a white rounded card form — "Subject" input, "Message" multiline text area
(6 rows) with a small gray character counter bottom-right, "Recipients" 2-chip toggle
("All Hospitals" selected / "Specific Hospitals"), when Specific is chosen show a
searchable field with a couple of small removable navy-tinted chips already selected
(hospital names with an "x"), a light-blue info banner "Sending to: 128 hospital(s)", and
a full-width navy "Send Notification" button.

History view (separate frame): a vertical list of white cards — hospital name bold,
subject truncated gray text, small recipient email caption, a status pill (green "Sent" /
red "Failed" with a small info icon / orange "Pending"), sent date bottom-right.

Also show a confirm dialog: centered white modal, title "Send this notification?", body
"This will email 128 hospital admin(s) immediately.", Cancel / Send buttons.

Mood: same light, navy-accented, white-card visual system as the rest of the app.
```

---

### 8.11 — Platform Settings (`PlatformSettingsScreen`)

```
Design a mobile settings screen, portrait 375×812pt, titled "Settings" — platform-wide
configuration in a healthcare SaaS Super Admin app. Background light blue #EBF5FB. Top
navy app bar.

4 collapsible white rounded-card sections (each a header row with an icon + title +
chevron toggle; "General" expanded by default, the rest collapsed):
1. "General" (gear icon, expanded): Platform Name, Support Email, Trial Days — themed
   white rounded inputs.
2. "Razorpay Configuration" (card icon, collapsed) — describe its content too: Key ID,
   Secret (password-style with eye toggle, placeholder "Leave blank to keep existing"),
   Webhook Secret (same pattern), small lock-icon note "Stored encrypted".
3. "Email / SMTP Configuration" (mail icon, collapsed): Host, Port, Username, Password
   (same blank-keeps-existing pattern), From Name, From Email.
4. "Subscription Pricing" (rupee-tag icon, collapsed): Monthly Base Price, Quarterly
   Discount %, Yearly Discount %.

At the bottom: a full-width solid navy "Save Changes" button.

Mood: same input styling and card language as a hospital-settings screen elsewhere in
this app — orderly, sectioned, not overwhelming despite many fields.
```

---

### 8.12 — Plans / Pricing (`PlatformPlansScreen`)

```
Design a mobile screen, portrait 375×812pt, titled "Plans" — the platform's subscription
pricing tiers, in a healthcare SaaS Super Admin app. Background light blue #EBF5FB. Top
navy app bar with a small edit-pencil action.

A thin info strip below the app bar: "Trial: 14 days · Grace period: 7 days" in gray.

3 stacked white rounded pricing cards (soft shadow):
1. "Monthly" — ₹999/month — plain white card
2. "Quarterly" — ₹2,427 (₹2,697 struck through) — small green "10% OFF" pill, a "Most
   Popular" navy tag in the top-right corner, this card has a light navy-blue tint or
   border to stand out from the other two
3. "Yearly" — ₹9,590 (₹11,988 struck through) — green "20% OFF" pill, "Best Value" tag

Each card lists 4-5 feature bullets with small green checkmark icons (e.g. "Unlimited
patients", "OT module included", "Priority support").

Also show the "Edit Pricing" full-screen form: back arrow, title "Edit Pricing", themed
inputs for Monthly Price / Quarterly Discount % / Yearly Discount % / Trial Days / Grace
Period Days, then a "Features" list where each row is a text input with a small red "x"
remove icon, and a "+ Add Feature" text button (navy) below the list. Full-width navy
"Save Pricing" button at the bottom.

Mood: mostly the same navy/white system as the rest of the app, with slightly more
warmth/color allowed on the pricing-card row specifically (this is the one screen closest
to a "marketing" moment) — but still clearly the same app, same fonts, same card shapes.
```

---

### 8.13 — Location Master (`PlatformLocationMasterScreen`)

```
Design a mobile screen, portrait 375×812pt, titled "Location Master" — a global Country →
State → District → City manager in a healthcare SaaS Super Admin app. Background light
blue #EBF5FB. Top navy app bar.

Below the app bar: a horizontal tab row (Countries / States / Districts / Cities) — white
background, active tab has a navy underline and bold navy text, inactive tabs gray text.

Countries tab: a search input, then a vertical list of white rounded cards — country
name bold, a small gray timezone pill ("Asia/Kolkata"), a "12 states" count pill, an
active/inactive toggle switch (green when on) on the right, small edit-pencil and delete-
trash icon buttons. A secondary, slightly grayed-out "Import from Excel" button near the
top with a small "web only for now" subtitle. Bottom-right navy floating "+" button
labeled "Add Country".

Add/Edit Country bottom sheet (separate frame): white sheet, rounded top corners, drag
handle, "Country Name" input, "Default Timezone" searchable dropdown (grouped by region),
and — edit mode only — an orange-tinted warning banner about cascading timezone changes
to hospitals. Full-width navy "Save" button.

Delete confirm dialog (separate frame): centered white modal, warning icon, "Delete this
country?", body text warning it deletes all states/districts/cities too, gray "Cancel"
and solid red "Delete" buttons.

Mood: utilitarian master-data management, but drawn in the exact same navy/white/rounded
visual language as every other screen in this app — not a separate "admin tool" skin.
```

---

### 8.14 — Medicine Master (`PlatformMedicineMasterScreen`)

```
Design a mobile screen, portrait 375×812pt, titled "Medicine Master" — the platform's
global medicine catalog manager in a healthcare SaaS Super Admin app (every hospital's own
medicine list is seeded from this). Background light blue #EBF5FB. Top navy app bar.

Below the app bar: a horizontal scrollable tab row — Dosages / Types / Categories /
Routes / Medicines — active tab (e.g. "Medicines") shown with a navy underline and bold
text.

Medicines tab: search input, a secondary grayed "Import from Excel (web only)" button,
then a vertical list of white rounded cards — medicine name bold (e.g. "Moxifloxacin Eye
Drops"), a row of small gray pills for type + dosage ("Antibiotic" / "0.5%"), company
name in small gray text, bold price on the right ("₹85"), an active toggle switch, and
edit/delete icon buttons. Bottom-right navy floating "+" button "Add Medicine".

Add/Edit Medicine bottom sheet: white rounded-top sheet with Type dropdown, Dosage
dropdown, Name, Duration, Quantity, Composition (multiline), Company, Price (₹) — all
themed white rounded inputs. Full-width navy "Save Medicine" button.

Also show one compact example of a simpler tab (e.g. Dosages): same card-list pattern but
just a name + toggle + edit/delete icons per row, and a delete-confirm dialog reading
"This won't remove it from hospitals already using it."

Mood: identical visual system to Location Master and the rest of the app — same tabs,
same cards, same bottom-sheet forms.
```

---

### 8.15 — Profile (`PlatformProfileScreen`)

```
Design a mobile profile screen, portrait 375×812pt, titled "My Profile" — the platform
admin's own account settings in a healthcare SaaS Super Admin app. Background light blue
#EBF5FB. Top navy app bar.

1. White rounded card "Account Information": a centered circular avatar (solid navy
   #1B4F72 fill, white bold initials "SA"), below it a "Name" input (prefilled "Super
   Admin"), an "Email" input (prefilled "admin@hmssaas.com"), a read-only gray "Role"
   pill ("super_admin"), a small gray meta line "Last login: 2 hours ago from
   103.21.44.12", and a full-width navy "Save Changes" button.

2. White rounded card "Change Password": "Current Password" input (eye-toggle icon),
   "New Password" input (eye-toggle icon) with a small gray helper caption below it
   ("Minimum 8 characters, mixed case, at least one number"), "Confirm New Password"
   input (eye-toggle icon), full-width navy "Update Password" button.

3. Below both cards: a full-width outlined button, red text and border, "Log Out", with
   a logout icon.

Mood: same calm, light, navy-and-white system as the rest of the app — simple personal-
settings page, generous whitespace.
```

---

*End of design spec.*
