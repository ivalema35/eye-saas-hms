# Eye-SaaS HMS Mobile App — Full Optimization PRD

**Version:** 3.0 (Final — Triple-Audited Against Actual Files)
**Date:** 2026-07-13
**Scope:** `lib/` — all 78 Dart files (36 screens, 27 services, 3 widgets, models, utils, config)
**Audit method:** File-by-file grep of every screen + service + widget + main.dart

---

## Critical Rule (Read Before Anything)

> Every phase is **additive first**.
> Create new file → confirm it compiles → migrate ONE screen → hot restart → visually verify → move to next.
> Old code is NOT deleted until the replacement is confirmed working.
> No API calls, no business logic, no widget tree structure is changed. Only visual code.

---

## Audit Summary — Actual Numbers

| Problem | Count | Severity |
|---|---|---|
| Screens with hardcoded `Color(0xFF...)` | **33 of 33 screen files** | Critical |
| Total navy `Color(0xFF1B4F72)` occurrences | **82+** | Critical |
| Unique color hex values with no shared constant | **15+ unique colors** | Critical |
| `static final` alpha variants (can't be const) per screen | up to **30+ per file** | Critical |
| Raw `showSnackBar()` calls (screens only) | **52 across 25 files** | High |
| `BoxDecoration(` occurrences | **416 across 36 files** | High |
| `BorderRadius.circular(` occurrences | **648 across 34 files** | High |
| Services with copy-paste `get _headers` | **17 of 27 services** | High |
| Screens missing empty-state widget | **12 screens** | Medium |
| Screens missing error-state widget | **12 screens** | Medium |
| Pagination implementations (should be 1) | **3 separate** | Medium |
| Filter card implementations (should be 1) | **2 separate** | Medium |
| Stat card implementations (should be 1) | **3 separate** | Medium |
| Shared widgets that should exist but don't | **~12** | Medium |
| `ThemeData` properties configured in `main.dart` | **2 of 15+** | Low |
| Constant files (colors, spacing, etc.) | **0** (only `permissions.dart`) | Missing |

---

## All Colors Found in the Codebase

This is every unique hex color used anywhere in the project. Every single one must be in `AppColors`.

| Token Name | Hex | Where Used | Notes |
|---|---|---|---|
| `primary` | `0xFF1B4F72` | 36 files, 82 occurrences | Main brand navy |
| `primaryLight` | `0xFF2471A3` | dashboard gradient | Lighter navy |
| `primaryDark` | `0xFF154360` | splash, pressed states | Darker navy |
| `darkNavy` | `0xFF1A3A52` | ot_surgery_type_master_screen | Even darker navy variant |
| `secondary` | `0xFF2E86C1` | dashboard | Mid-blue |
| `blue` | `0xFF006497` | login `_secondary`, patients `blue12` base | Deep blue |
| `blueLight` | `0xFF2980B9` | patient_form, patient_checkin | Medium blue |
| `teal` | `0xFF1ABC9C` | patients, clinical_queue, reports | Teal accent |
| `tealDark` | `0xFF0E9E82` | patients_screen `tealDark` | Darker teal |
| `green` | `0xFF27AE60` | patients, doctor_dashboard, settings | **ONE canonical green** |
| `orange` | `0xFFE67E22` | patients, dashboard, masters | Warning orange |
| `purple` | `0xFF8E44AD` | patients, clinical_queue | Purple |
| `red` | `0xFFDC3545` | patients, generic_master, OT screens | Danger/error red |
| `redDark` | `0xFFC0392B` | clinical_queue, patients alt | Darker red variant |
| `background` | `0xFFEBF5FB` | home_screen, dashboard, splash | **ONE canonical background** |
| `backgroundAlt` | `0xFFEAF2F8` | case_type_master, ot screens `_bg` | Slightly different bg |
| `surfaceFill` | `0xFFF0F6FB` | ot_surgery_type_master input fields | Very light blue fill |
| `drawerBackground` | `0xFFF3F4F7` | app_drawer `_bgColor` | Drawer sidebar background |
| `surface` | `0xFFFFFFFF` | all cards | Card surface |
| `textPrimary` | `0xFF1E293B` | dashboard quick action labels | Main body text |
| `textSecondary` | `0xFF64748B` | many screens `_textSub` | Muted/secondary text |
| `textDisabled` | `0xFF94A3B8` | home_screen inactive nav | Disabled / inactive |
| `textOnPrimary` | `0xFFFFFFFF` | all white-on-navy text | Text on colored surfaces |
| `navBarBg` | `0xF0FFFFFF` | home_screen `_white94` | Frosted nav bar |
| `black10` | `0x1A000000` | home_screen nav shadow | Drop shadow |
| `splashAccent` | `0xFF9DCBF4` | splash_screen "HMS" headline | Pale blue accent on splash |
| `skeletonBase` | `0xFFE2E8F0` | skeleton.dart shimmer base | Shimmer animation base |
| `skeletonShine` | `0xFFF8FAFC` | skeleton.dart shimmer shine | Shimmer animation highlight |

---

## Phase 0 — Foundation Files (Zero Breaking Risk)

> **What:** Create all new constant/theme files. Zero existing files touched. Zero risk.
> **Done when:** All new files compile. `flutter analyze` clean. App runs identically.

### 0.1 — `lib/constants/app_colors.dart`

**Critical note on `const` vs `final`:**
- Base colors → `static const Color` (compile-time hex values)
- Alpha variants → `static final Color` (computed via `.withValues()` at startup, cannot be const)

```dart
import 'package:flutter/material.dart';

abstract final class AppColors {

  // ── Brand Primary ─────────────────────────────────────────────────────────
  static const Color primary      = Color(0xFF1B4F72);
  static const Color primaryLight = Color(0xFF2471A3);
  static const Color primaryDark  = Color(0xFF154360);
  static const Color darkNavy     = Color(0xFF1A3A52);  // ot_surgery_type_master

  // ── Blues ─────────────────────────────────────────────────────────────────
  static const Color secondary    = Color(0xFF2E86C1);
  static const Color blue         = Color(0xFF006497);  // login + patients blue12
  static const Color blueLight    = Color(0xFF2980B9);  // patient_form, patient_checkin

  // ── Accent Colors ─────────────────────────────────────────────────────────
  static const Color teal         = Color(0xFF1ABC9C);
  static const Color tealDark     = Color(0xFF0E9E82);
  static const Color green        = Color(0xFF27AE60);  // ONE canonical green
  static const Color orange       = Color(0xFFE67E22);
  static const Color purple       = Color(0xFF8E44AD);
  static const Color red          = Color(0xFFDC3545);
  static const Color redDark      = Color(0xFFC0392B);

  // ── Backgrounds ───────────────────────────────────────────────────────────
  static const Color background       = Color(0xFFEBF5FB);  // main app bg
  static const Color backgroundAlt    = Color(0xFFEAF2F8);  // master screens bg
  static const Color surfaceFill      = Color(0xFFF0F6FB);  // OT input fields
  static const Color drawerBackground = Color(0xFFF3F4F7);  // app_drawer sidebar
  static const Color surface          = Color(0xFFFFFFFF);  // card surface

  // ── Text ──────────────────────────────────────────────────────────────────
  static const Color textPrimary    = Color(0xFF1E293B);
  static const Color textSecondary  = Color(0xFF64748B);
  static const Color textDisabled   = Color(0xFF94A3B8);
  static const Color textOnPrimary  = Color(0xFFFFFFFF);

  // ── Utility ───────────────────────────────────────────────────────────────
  static const Color navBarBg = Color(0xF0FFFFFF);  // frosted nav bar
  static const Color black10  = Color(0x1A000000);  // nav bar shadow

  // ── Primary alpha variants (static final — not const) ────────────────────
  static final Color primaryA04 = primary.withValues(alpha: 0.04);
  static final Color primaryA05 = primary.withValues(alpha: 0.05);
  static final Color primaryA06 = primary.withValues(alpha: 0.06);
  static final Color primaryA07 = primary.withValues(alpha: 0.07);
  static final Color primaryA08 = primary.withValues(alpha: 0.08);
  static final Color primaryA10 = primary.withValues(alpha: 0.10);
  static final Color primaryA12 = primary.withValues(alpha: 0.12);
  static final Color primaryA13 = primary.withValues(alpha: 0.13);
  static final Color primaryA14 = primary.withValues(alpha: 0.14);
  static final Color primaryA15 = primary.withValues(alpha: 0.15);
  static final Color primaryA18 = primary.withValues(alpha: 0.18);
  static final Color primaryA20 = primary.withValues(alpha: 0.20);
  static final Color primaryA22 = primary.withValues(alpha: 0.22);
  static final Color primaryA25 = primary.withValues(alpha: 0.25);
  static final Color primaryA28 = primary.withValues(alpha: 0.28);
  static final Color primaryA35 = primary.withValues(alpha: 0.35);
  static final Color primaryA40 = primary.withValues(alpha: 0.40);
  static final Color primaryA45 = primary.withValues(alpha: 0.45);
  static final Color primaryA50 = primary.withValues(alpha: 0.50);
  static final Color primaryA55 = primary.withValues(alpha: 0.55);
  static final Color primaryA58 = primary.withValues(alpha: 0.58);
  static final Color primaryA60 = primary.withValues(alpha: 0.60);
  static final Color primaryA70 = primary.withValues(alpha: 0.70);

  // ── Other alpha variants ───────────────────────────────────────────────────
  static final Color tealA06    = teal.withValues(alpha: 0.06);
  static final Color tealA10    = teal.withValues(alpha: 0.10);
  static final Color tealA12    = teal.withValues(alpha: 0.12);
  static final Color tealA13    = teal.withValues(alpha: 0.13);
  static final Color orangeA12  = orange.withValues(alpha: 0.12);
  static final Color orangeA13  = orange.withValues(alpha: 0.13);
  static final Color orangeA14  = orange.withValues(alpha: 0.14);
  static final Color purpleA12  = purple.withValues(alpha: 0.12);
  static final Color redA70     = red.withValues(alpha: 0.70);
  static final Color greenA12   = green.withValues(alpha: 0.12);
  static final Color greyA10    = Colors.grey.withValues(alpha: 0.10);
  static final Color blueA12    = blue.withValues(alpha: 0.12);

  // ── Skeleton shimmer colors ────────────────────────────────────────────────
  static const Color skeletonBase  = Color(0xFFE2E8F0);  // shimmer base color
  static const Color skeletonShine = Color(0xFFF8FAFC);  // shimmer highlight

  // ── Splash accent ──────────────────────────────────────────────────────────
  static const Color splashAccent = Color(0xFF9DCBF4);   // "HMS" headline on splash
}
```

---

### 0.2 — `lib/constants/app_radius.dart`

```dart
abstract final class AppRadius {
  static const double xs   = 4;
  static const double sm   = 8;
  static const double md   = 12;   // default card corner
  static const double lg   = 16;   // large card / bottom sheet
  static const double xl   = 20;
  static const double xxl  = 28;   // nav bar pill
  static const double full = 100;  // fully-rounded pill/badge
}
```

---

### 0.3 — `lib/constants/app_spacing.dart`

```dart
abstract final class AppSpacing {
  static const double xs  = 4;
  static const double sm  = 8;
  static const double md  = 12;
  static const double lg  = 16;   // standard horizontal page margin
  static const double xl  = 20;
  static const double xxl = 24;

  // Clears the floating bottom nav bar — the most-repeated magic number in the project
  static const double bottomNavClearance = 110.0;

  // Pre-built EdgeInsets for the most common patterns
  static const EdgeInsets pagePadding    = EdgeInsets.symmetric(horizontal: lg);
  static const EdgeInsets pageWithBottom = EdgeInsets.fromLTRB(lg, md, lg, bottomNavClearance);
  static const EdgeInsets cardPadding    = EdgeInsets.all(lg);
  static const EdgeInsets chipPadding    = EdgeInsets.symmetric(horizontal: md, vertical: 6);
}
```

---

### 0.4 — `lib/constants/app_text_styles.dart`

> **Phase 7 warning:** In Phase 7, `AppColors.primary` converts from `static const` to a getter. When that happens, `static const TextStyle sectionLabel` (which uses `AppColors.primary`) will no longer compile because const fields cannot reference non-const getters. At that point, convert all `static const TextStyle` to `static TextStyle get` (lazy getters that read AppColors at runtime). The Phase 7 section covers this migration.

```dart
import 'package:flutter/material.dart';
import 'app_colors.dart';

abstract final class AppTextStyles {
  // NOTE: These are static const in Phase 0-6 (AppColors.primary is const).
  // In Phase 7, after AppColors converts to getters, change these to:
  //   static TextStyle get headingLarge => TextStyle(...)
  static const TextStyle headingLarge  = TextStyle(fontSize: 20, fontWeight: FontWeight.w700, color: AppColors.textPrimary);
  static const TextStyle headingMedium = TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.textPrimary);
  static const TextStyle headingSmall  = TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.textPrimary);
  static const TextStyle sectionLabel  = TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: AppColors.primary, letterSpacing: 0.2);
  static const TextStyle bodyLarge     = TextStyle(fontSize: 14, fontWeight: FontWeight.w400, color: AppColors.textPrimary);
  static const TextStyle bodyMedium    = TextStyle(fontSize: 13, fontWeight: FontWeight.w400, color: AppColors.textPrimary);
  static const TextStyle bodySmall     = TextStyle(fontSize: 12, fontWeight: FontWeight.w400, color: AppColors.textSecondary);
  static const TextStyle labelMedium   = TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.textSecondary);
  static const TextStyle labelSmall    = TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: AppColors.textPrimary);
  static const TextStyle navLabel      = TextStyle(fontSize: 10, fontWeight: FontWeight.w700);
  static const TextStyle statNumber    = TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: AppColors.textPrimary);
  static const TextStyle cardTitle     = TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.textPrimary);
  static const TextStyle cardSubtitle  = TextStyle(fontSize: 12, fontWeight: FontWeight.w400, color: AppColors.textSecondary);
}
```

---

### 0.5 — `lib/theme/app_theme.dart`

> **Note:** The `lib/theme/` directory does not exist yet. Create it before creating the file.

```dart
import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';

abstract final class AppTheme {
  static ThemeData get light => ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(
      seedColor: AppColors.primary,
      brightness: Brightness.light,
    ).copyWith(
      primary:   AppColors.primary,
      secondary: AppColors.secondary,
      surface:   AppColors.surface,
      error:     AppColors.red,
    ),
    scaffoldBackgroundColor: AppColors.background,
    snackBarTheme: SnackBarThemeData(
      behavior: SnackBarBehavior.floating,
      backgroundColor: AppColors.primary,
      contentTextStyle: const TextStyle(color: AppColors.textOnPrimary),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: AppColors.surface,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(AppRadius.md),
        borderSide: BorderSide(color: AppColors.primaryA12),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(AppRadius.md),
        borderSide: BorderSide(color: AppColors.primaryA12),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(AppRadius.md),
        borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
      ),
    ),
  );
}
```

---

### 0.6 — `lib/utils/app_decorations.dart`

Replaces 416 manual `BoxDecoration` instances:

```dart
import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';

abstract final class AppDecorations {
  // Standard white card with subtle shadow — the most-repeated pattern in the project
  static BoxDecoration card({double radius = AppRadius.md}) => BoxDecoration(
    color: AppColors.surface,
    borderRadius: BorderRadius.circular(radius),
    border: Border.all(color: AppColors.primaryA08, width: 0.5),
    boxShadow: [BoxShadow(color: AppColors.primaryA06, blurRadius: 8, offset: const Offset(0, 2))],
  );

  // Card with left color accent strip (stat cards, queue items)
  static BoxDecoration accentCard({double radius = AppRadius.md}) => BoxDecoration(
    color: AppColors.surface,
    borderRadius: BorderRadius.circular(radius),
    boxShadow: [BoxShadow(color: AppColors.primaryA06, blurRadius: 8, offset: const Offset(0, 2))],
  );

  // Colored pill/badge background
  static BoxDecoration pill({required Color color, double opacity = 0.12}) => BoxDecoration(
    color: color.withValues(alpha: opacity),
    borderRadius: BorderRadius.circular(AppRadius.full),
  );

  // Icon container (colored square with rounded corners)
  static BoxDecoration iconBox({required Color color, double radius = AppRadius.sm}) => BoxDecoration(
    color: color.withValues(alpha: 0.12),
    borderRadius: BorderRadius.circular(radius),
  );
}
```

---

### Phase 0 Checklist

- [ ] `lib/constants/app_colors.dart` — compiles, zero lint errors
- [ ] `lib/constants/app_radius.dart` — compiles
- [ ] `lib/constants/app_spacing.dart` — compiles
- [ ] `lib/constants/app_text_styles.dart` — compiles
- [ ] `lib/theme/app_theme.dart` — compiles
- [ ] `lib/utils/app_decorations.dart` — compiles
- [ ] `flutter analyze` — zero new errors
- [ ] **App runs identically — zero visual change**

---

## Phase 1 — Wire ThemeData (One Line, Zero Risk)

> **What:** Update `main.dart` to use `AppTheme.light`. No screens change, no visual effect yet.

Edit `lib/main.dart`:

**Remove:**
```dart
theme: ThemeData(
  colorScheme: ColorScheme.fromSeed(
    seedColor: const Color(0xFF1B4F72),
    brightness: Brightness.light,
  ),
  useMaterial3: true,
),
```

**Replace with:**
```dart
theme: AppTheme.light,
```

Add import: `import 'theme/app_theme.dart';`

**Test:** Hot restart. App must look **identical**. Since no screen uses `Theme.of(context)`, this is a no-op visually but sets up the foundation for Phase 7.

---

## Phase 2 — Shared Widgets Library (New Files Only, Zero Risk)

> **What:** Create all missing shared widgets. No existing screens touched.

---

### 2.1 — `lib/widgets/app_section_header.dart`

Replaces private `_sectionLabel()` in: `dashboard_screen`, `medicines_screen`, `share_history_screen`, `patient_form_screen`, `user_form_screen`, `patient_checkin_screen`.

```dart
import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_text_styles.dart';

class AppSectionHeader extends StatelessWidget {
  final String title;
  final Widget? trailing;

  const AppSectionHeader({super.key, required this.title, this.trailing});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        children: [
          Container(
            width: 3, height: 17,
            decoration: BoxDecoration(
              color: AppColors.primary,
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          const SizedBox(width: 9),
          Text(title, style: AppTextStyles.sectionLabel),
          if (trailing != null) ...[const Spacer(), trailing!],
        ],
      ),
    );
  }
}
```

---

### 2.2 — `lib/widgets/app_empty_state.dart`

Replaces 12 private empty-state widgets across 12 screens.

```dart
import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_text_styles.dart';

class AppEmptyState extends StatelessWidget {
  final String message;
  final IconData icon;
  final VoidCallback? onRefresh;

  const AppEmptyState({
    super.key,
    required this.message,
    this.icon = Icons.inbox_rounded,
    this.onRefresh,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 52, color: AppColors.primaryA20),
          const SizedBox(height: 14),
          Text(message, style: AppTextStyles.bodyMedium, textAlign: TextAlign.center),
          if (onRefresh != null) ...[
            const SizedBox(height: 16),
            TextButton.icon(
              onPressed: onRefresh,
              icon: const Icon(Icons.refresh_rounded, size: 16),
              label: const Text('Refresh'),
            ),
          ],
        ],
      ),
    );
  }
}
```

---

### 2.3 — `lib/widgets/app_error_state.dart`

Replaces 12 private error-state widgets.

```dart
import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_text_styles.dart';
import '../constants/app_radius.dart';
import '../constants/app_spacing.dart';

class AppErrorState extends StatelessWidget {
  final String message;
  final VoidCallback? onRetry;

  const AppErrorState({super.key, required this.message, this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.xl),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 56, height: 56,
              decoration: BoxDecoration(
                color: AppColors.red.withValues(alpha: 0.10),
                borderRadius: BorderRadius.circular(AppRadius.lg),
              ),
              child: const Icon(Icons.wifi_off_rounded, color: AppColors.red, size: 28),
            ),
            const SizedBox(height: 14),
            Text(message, style: AppTextStyles.bodyMedium, textAlign: TextAlign.center),
            if (onRetry != null) ...[
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: onRetry,
                icon: const Icon(Icons.refresh_rounded, size: 16),
                label: const Text('Try Again'),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
```

---

### 2.4 — `lib/widgets/app_stat_card.dart`

Replaces 3 separate implementations in `dashboard_screen`, `patients_screen`, `doctor_dashboard_screen`.

```dart
import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/app_text_styles.dart';
import '../utils/app_decorations.dart';

class AppStatCard extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final Color accentColor;

  const AppStatCard({
    super.key,
    required this.label,
    required this.value,
    required this.icon,
    required this.accentColor,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: AppDecorations.accentCard(),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(AppRadius.md),
        child: IntrinsicHeight(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(width: 4, color: accentColor),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  child: Row(
                    children: [
                      Container(
                        width: 36, height: 36,
                        decoration: AppDecorations.iconBox(color: accentColor),
                        child: Icon(icon, color: accentColor, size: 18),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(value, style: AppTextStyles.statNumber),
                            Text(label, style: AppTextStyles.cardSubtitle),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
```

---

### 2.5 — `lib/widgets/app_search_bar.dart`

Replaces inline search fields in `patients_screen`, `clinical_queue_screen`, `reports_screen`, `share_history_screen`.

```dart
import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_text_styles.dart';

class AppSearchBar extends StatelessWidget {
  final TextEditingController controller;
  final String hint;
  final ValueChanged<String>? onChanged;
  final VoidCallback? onClear;

  const AppSearchBar({
    super.key,
    required this.controller,
    required this.hint,
    this.onChanged,
    this.onClear,
  });

  @override
  Widget build(BuildContext context) {
    // IMPORTANT: ValueListenableBuilder wraps only the suffixIcon, NOT the whole
    // TextField. Wrapping the whole TextField causes focus loss on every keystroke.
    return TextField(
      controller: controller,
      onChanged: onChanged,
      style: AppTextStyles.bodyMedium,
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: AppTextStyles.bodySmall,
        prefixIcon: const Icon(Icons.search_rounded, color: AppColors.textSecondary, size: 20),
        suffixIcon: ValueListenableBuilder<TextEditingValue>(
          valueListenable: controller,
          builder: (_, value, __) => value.text.isNotEmpty
              ? IconButton(
                  icon: const Icon(Icons.close_rounded, size: 18),
                  onPressed: () { controller.clear(); onClear?.call(); },
                )
              : const SizedBox.shrink(),
        ),
      ),
    );
  }
}
```

---

### 2.6 — `lib/widgets/app_wait_pill.dart`

Move the existing `WaitPill` from `clinical_queue_screen.dart` here. Copy verbatim — do NOT delete from source file yet.

```dart
// Copy the existing WaitPill class from clinical_queue_screen.dart
// Update imports to use AppColors instead of local consts
// Keep original WaitPill in clinical_queue_screen.dart until Phase 3 migration
```

---

### 2.7 — `lib/widgets/app_pagination_bar.dart`

Replaces 3 separate implementations in `patients_screen`, `share_history_screen`, `reports_screen`.

```dart
import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/app_text_styles.dart';

class AppPaginationBar extends StatelessWidget {
  final int currentPage;
  final int totalPages;
  final ValueChanged<int> onPageChange;

  const AppPaginationBar({
    super.key,
    required this.currentPage,
    required this.totalPages,
    required this.onPageChange,
  });

  @override
  Widget build(BuildContext context) {
    if (totalPages <= 1) return const SizedBox.shrink();
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        _PagBtn(
          icon: Icons.chevron_left_rounded,
          enabled: currentPage > 1,
          onTap: () => onPageChange(currentPage - 1),
        ),
        const SizedBox(width: 8),
        Text('$currentPage / $totalPages', style: AppTextStyles.labelMedium),
        const SizedBox(width: 8),
        _PagBtn(
          icon: Icons.chevron_right_rounded,
          enabled: currentPage < totalPages,
          onTap: () => onPageChange(currentPage + 1),
        ),
      ],
    );
  }
}

class _PagBtn extends StatelessWidget {
  final IconData icon;
  final bool enabled;
  final VoidCallback onTap;
  const _PagBtn({required this.icon, required this.enabled, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: enabled ? onTap : null,
      child: Container(
        width: 32, height: 32,
        decoration: BoxDecoration(
          color: enabled ? AppColors.primaryA12 : AppColors.primaryA06,
          borderRadius: BorderRadius.circular(AppRadius.sm),
        ),
        child: Icon(icon,
          size: 18,
          color: enabled ? AppColors.primary : AppColors.textDisabled,
        ),
      ),
    );
  }
}
```

---

### 2.8 — Update `lib/widgets/app_animations.dart`

The actual `showAppSnackBar` in this file uses `Colors.red.shade700`, `Colors.green.shade600`, `Colors.orange.shade700` — **not** `Color(0xFF1B4F72)`. Update it to use AppColors and AppRadius:

```dart
// BEFORE (actual current code):
void showAppSnackBar(
  BuildContext context,
  String message, {
  bool isError = false,
  bool isSuccess = false,
  Duration duration = const Duration(seconds: 3),
}) {
  final color = isError
      ? Colors.red.shade700
      : isSuccess
          ? Colors.green.shade600
          : Colors.orange.shade700;
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(
      content: Text(message),
      backgroundColor: color,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      duration: duration,
    ),
  );
}

// AFTER:
void showAppSnackBar(
  BuildContext context,
  String message, {
  bool isError = false,
  bool isSuccess = false,
  Duration duration = const Duration(seconds: 3),
}) {
  final color = isError
      ? AppColors.red
      : isSuccess
          ? AppColors.green
          : AppColors.orange;
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(
      content: Text(message, style: const TextStyle(color: AppColors.textOnPrimary)),
      backgroundColor: color,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
      duration: duration,
    ),
  );
}
```

Add imports at top of `app_animations.dart`:
```dart
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
```

**`showAppSnackBar` signature reference** (for use when migrating the 52 raw `showSnackBar` calls):
```dart
showAppSnackBar(context, 'Record saved.');             // default (orange)
showAppSnackBar(context, 'Not found.', isError: true); // red
showAppSnackBar(context, 'Done!', isSuccess: true);    // green
```

---

### 2.9 — Update `lib/widgets/skeleton.dart`

`skeleton.dart` uses 2 hardcoded shimmer colors not in AppColors. Update `_SkeletonTile`:

```dart
// BEFORE:
const base  = Color(0xFFE2E8F0);
const shine = Color(0xFFF8FAFC);

// AFTER:
const base  = AppColors.skeletonBase;
const shine = AppColors.skeletonShine;
```

Add import: `import '../constants/app_colors.dart';`

---

### 2.10 — `lib/widgets/widgets.dart` (barrel export)

```dart
export 'app_animations.dart';
export 'app_drawer.dart';
export 'app_empty_state.dart';
export 'app_error_state.dart';
export 'app_pagination_bar.dart';
export 'app_search_bar.dart';
export 'app_section_header.dart';
export 'app_stat_card.dart';
export 'app_wait_pill.dart';
export 'skeleton.dart';
```

---

### Phase 2 Checklist

- [ ] All new widget files compile
- [ ] `app_animations.dart` — updated (red/green/orange colors use AppColors)
- [ ] `skeleton.dart` — updated (shimmer uses AppColors.skeletonBase/skeletonShine)
- [ ] `flutter analyze` passes clean
- [ ] **Zero screen files changed**
- [ ] App runs identically — skeletons and snackbars look the same

---

## Phase 3 — Screen Migration (One at a Time)

> **What:** Migrate every screen to `AppColors` + `AppTextStyles` + shared widgets.
> **Rule:** One screen per session. Hot restart + visual check before moving on.
> **Do NOT rename or delete old color consts until the new references work.**

### Full Migration List — ALL 34 Files

This is every file with hardcoded colors: 32 screens + `app_drawer.dart` (widget, in `lib/widgets/`) + `home_screen.dart` + `dashboard_screen.dart`. Ordered simple → complex.

| # | Screen File | Hardcoded Colors | Key Pattern | Complexity |
|---|---|---|---|---|
| 1 | `splash_screen.dart` | ~8 occurrences | Inline colors only | Simple |
| 2 | `prescription_print_screen.dart` | 1 occurrence | Minimal | Simple |
| 3 | `profile_screen.dart` | ~10 occurrences | State-class static consts | Simple |
| 4 | `generic_master_screen.dart` | ~5 occurrences | 1 static const | Simple |
| 5 | `user_form_screen.dart` | 8 occurrences | State-class `_navy`, `_blue` | Simple |
| 6 | `location_master_screen.dart` | 10 occurrences | State-class consts | Simple |
| 7 | `patient_checkin_screen.dart` | 11 occurrences | State-class `_navy`, `_blue`, `_green` | Simple |
| 8 | `patient_history_screen.dart` | 11 occurrences | State-class consts | Simple |
| 9 | `opd_bill_screen.dart` | ~12 occurrences | `abstract final class _C` | Medium |
| 10 | `foc_screen.dart` | ~15 occurrences | `abstract final class _C` | Medium |
| 11 | `roles_screen.dart` | ~15 occurrences | `abstract final class _C` | Medium |
| 12 | `masters_screen.dart` | ~15 occurrences | `abstract final class _K` | Medium |
| 13 | `settings_screen.dart` | 26 occurrences | File-top + state-class consts | Medium |
| 14 | `patient_form_screen.dart` | 19 occurrences | State-class `_navy`, `_blue` | Medium |
| 15 | `login_screen.dart` | ~10 occurrences | State-class `_navy`, `_secondary`, `_softBg` | Medium |
| 16 | `referrer_master_screen.dart` | 17 occurrences | State-class consts | Medium |
| 17 | `case_type_master_screen.dart` | 18 occurrences | **NESTED CLASS PATTERN** (see note) | Medium |
| 18 | `ot_slot_master_screen.dart` | 20 occurrences | State-class consts | Medium |
| 19 | `ot_charge_head_master_screen.dart` | 21 occurrences | State-class consts | Medium |
| 20 | `ot_surgery_type_master_screen.dart` | 26 occurrences | **NESTED CLASS PATTERN** + `darkNavy` | Medium |
| 21 | `reports_screen.dart` | ~20 occurrences | `_C` class + private `_FilterCard` + private pagination | Medium |
| 22 | `share_history_screen.dart` | ~20 occurrences | `_C` class + 2× `_showSnack()` duplication + `_FilterCard` | Medium |
| 23 | `medicines_screen.dart` | ~20 occurrences | `abstract final class _C` | Medium |
| 24 | `medicine_group_detail_screen.dart` | ~15 occurrences | `_C` class | Medium |
| 25 | `medicine_group_form_screen.dart` | ~15 occurrences | `_C` class | Medium |
| 26 | `users_screen.dart` | 17 consts | 14 file-top + 3 state-class (redundant) | Medium |
| 27 | `secondary_exam_screen.dart` | ~15 occurrences | Consts in state class | Complex |
| 28 | `primary_exam_screen.dart` | ~15 occurrences | Consts in state class | Complex |
| 29 | `doctor_dashboard_screen.dart` | ~20 occurrences | State consts + private `_statCard()` + `_waitPill()` | Complex |
| 30 | `clinical_queue_screen.dart` | ~25 occurrences | `_C` class + `WaitPill` → `AppWaitPill` | Complex |
| 31 | `patients_screen.dart` | 63 occurrences | **MASSIVE `_C` CLASS** (30+ alpha variants) + stat card + pagination | Complex |
| 32 | `app_drawer.dart` ⚠️ **Widget file** — in `lib/widgets/`, NOT `lib/screens/`. Import path is `'../constants/app_colors.dart'` (not `'../../constants/'`) | 3× `_navy` in nested classes | Nested: `AppDrawer` + `_SmallAccordion` + `_FullAccordion` | Complex |
| 33 | `home_screen.dart` | 4 consts incl. 1 inline in build() | Inline const in `_NavItemState.build()` | Complex |
| 34 | `dashboard_screen.dart` | 11 consts | `_statCard()` + `_sectionLabel()` + `_buildSkeleton()` | Most Complex |

---

### Special Migration Notes

#### Nested Class Pattern (`case_type_master_screen.dart`, `ot_surgery_type_master_screen.dart`, `app_drawer.dart`)

Some files contain multiple class definitions, each with their own `_navy` declaration:

```
// case_type_master_screen.dart
_CaseTypeMasterScreenState  → static const _navy = Color(0xFF1B4F72)
_CaseTypeSheet              → static const _navy = Color(0xFF1B4F72)  ← ALSO HERE
_CaseTypeSheetState         → static const _navy = Color(0xFF1B4F72)  ← AND HERE
```

When migrating these files, search ALL nested classes — not just the outer screen state class.

#### `patients_screen.dart` — The Most Complex Migration

The `_C` class has:
- 7 base `static const` colors
- 30+ `static final` alpha variants

Replacement mapping:

| Old | New |
|---|---|
| `_C.navy` | `AppColors.primary` |
| `_C.teal` | `AppColors.teal` |
| `_C.tealDark` | `AppColors.tealDark` |
| `_C.orange` | `AppColors.orange` |
| `_C.purple` | `AppColors.purple` |
| `_C.red` | `AppColors.red` |
| `_C.green` | `AppColors.green` |
| `_C.navyA04` → `_C.navyA70` | `AppColors.primaryA04` → `AppColors.primaryA70` |
| `_C.tealA06` → `_C.tealA13` | `AppColors.tealA06` → `AppColors.tealA13` |
| `_C.orangeA12`, `_C.orangeA13`, `_C.orangeA14` | `AppColors.orangeA12`, etc. |
| `_C.purpleA12` | `AppColors.purpleA12` |
| `_C.redA70` | `AppColors.redA70` |
| `_C.greenA12` | `AppColors.greenA12` |
| `_C.greyA10` | `AppColors.greyA10` |
| `_C.blue12` | `AppColors.blueA12` |
| Private `_StatCard` widget | `AppStatCard(...)` |
| Private `_PagArrow`/`_PagNum`/`_PagDot` | `AppPaginationBar(...)` |

#### `login_screen.dart` — Google Fonts Decision

`login_screen.dart` imports `package:google_fonts/google_fonts.dart` for Poppins on the form heading. Every other screen uses the system font. When migrating:

- **Option A (Recommended):** Remove `google_fonts` — standardize to system font. Makes login consistent with all other screens.
- **Option B:** Keep Poppins intentionally as a design choice — document it in a comment.

Pick one and apply consistently. Do NOT leave it as accidental inconsistency.

#### Color Standardization During Migration

Some screens use slightly different background shades. During migration, standardize:

| Old value | Standardize to |
|---|---|
| `Color(0xFFEAF2F8)` (`_bg` in master screens) | `AppColors.backgroundAlt` |
| `Color(0xFFEBF5FB)` (main bg) | `AppColors.background` |
| `Color(0xFFF0F6FB)` (input fill in OT screens) | `AppColors.surfaceFill` |
| `Color(0xFFF3F4F7)` (drawer bg) | `AppColors.drawerBackground` |
| `Color(0xFF27AE60)` / `Color(0xFF1F9D55)` / `Color(0xFF16A34A)` | `AppColors.green` |
| `Color(0xFFDC3545)` / `Color(0xFFC0392B)` | `AppColors.red` or `AppColors.redDark` based on use |

---

### Per-Screen Migration Checklist

For EACH screen, in order:

1. Add imports at top of file:
   ```dart
   import '../constants/app_colors.dart';
   import '../constants/app_radius.dart';
   import '../constants/app_spacing.dart';
   import '../constants/app_text_styles.dart';
   import '../utils/app_decorations.dart';
   import '../widgets/widgets.dart';
   ```
   > `app_drawer.dart` (step 32) uses `'../constants/'` not `'../../constants/'` — it is already in `lib/widgets/`.

2. Replace every color reference **while the old color block still exists** (see mapping table):
   - `_navy` / `_C.navy` → `AppColors.primary`
   - `_textSub` / `Color(0xFF64748B)` → `AppColors.textSecondary`
   - `_bg` / `Color(0xFFEBF5FB)` / `Color(0xFFEAF2F8)` → `AppColors.background` or `AppColors.backgroundAlt`
   - `_C.navyA04` → `_C.navyA70` → `AppColors.primaryA04` → `AppColors.primaryA70`
   - All other color mappings from the patients_screen table above

3. **AFTER all references are replaced**, delete the local color class/block (`abstract final class _C`, `static const _navy = ...`, etc.). Do NOT delete first — that causes undefined references.

4. Replace `BorderRadius.circular(12)` → `BorderRadius.circular(AppRadius.md)` etc.

5. Replace private empty state → `AppEmptyState(...)`

6. Replace private error state → `AppErrorState(...)`

7. Replace raw `ScaffoldMessenger.of(context).showSnackBar(SnackBar(...))` → `showAppSnackBar(context, ...)`
   - 52 raw calls across 25 files (not counting `app_drawer.dart` and `home_screen.dart`)

8. Replace private section labels → `AppSectionHeader(...)`

9. Replace private stat cards → `AppStatCard(...)` (screens 29, 31, 34)

10. Replace private pagination → `AppPaginationBar(...)` (screens 21, 22, 31)

11. Replace private search field → `AppSearchBar(...)` (screens 21, 22, 31, 30)

12. Hot restart → open the screen → visual check

13. `flutter analyze` — zero new warnings before moving on

---

## Phase 4 — Service Layer Normalization

> **What:** Eliminate 17 copy-paste `get _headers` implementations.
> **Risk:** Low-Medium — test each service's API call after migration.

---

### 4.1 — `lib/services/base_service.dart`

**Critical:** `simple_master_service.dart` has a slightly different header pattern — no `if (token != null)` guard. The mixin uses the guarded version (correct for auth flows):

```dart
import 'auth_service.dart';

mixin AuthenticatedService {
  Future<Map<String, String>> get headers async {
    final token = await AuthService.instance.getStoredToken();
    return {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }
}
```

---

### 4.2 — Services with `get _headers` (all 17)

| Service | Notes |
|---|---|
| `roles_service.dart` | Standard pattern |
| `foc_service.dart` | Standard pattern |
| `patient_history_service.dart` | Standard pattern |
| `exam_masters_service.dart` | Standard pattern |
| `exam_service.dart` | Standard pattern |
| `patient_service.dart` | Standard pattern |
| `ot_surgery_type_service.dart` | Standard pattern |
| `ot_charge_head_service.dart` | Standard pattern |
| `ot_slot_service.dart` | Standard pattern |
| `referrer_service.dart` | Standard pattern |
| `case_type_service.dart` | Standard pattern |
| `simple_master_service.dart` | **Different** — no `if (token != null)` guard, has `_parse()` helper. Verify API still works after migration. |
| `medicine_service.dart` | Standard pattern |
| `report_service.dart` | Standard pattern |
| `clinical_queue_service.dart` | Standard pattern |
| `share_history_service.dart` | Standard pattern |
| `masters_service.dart` | Standard pattern |

**Services NOT migrated** (10 — don't have `get _headers`):
`auth_service`, `cache_service`, `dashboard_service`, `doctor_dashboard_service`, `location_service`, `permission_service`, `prescription_service`, `profile_service`, `settings_service`, `user_service`

---

### 4.3 — Per-Service Migration Steps

1. Add `with AuthenticatedService` to class declaration
2. Delete local `get _headers` method
3. Change all `_headers` → `headers` (mixin getter is non-private)
4. **Test the actual API call this service handles** (load the screen that uses it, confirm data loads)

---

### 4.4 — Shared Response Parser (Optional Enhancement)

Currently only `simple_master_service.dart` has a `_parse()` helper. Creating a shared parser:

```dart
// lib/services/api_response.dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class ApiException implements Exception {
  final int statusCode;
  final String message;
  const ApiException({required this.statusCode, required this.message});
  @override String toString() => 'ApiException($statusCode): $message';
}

Map<String, dynamic> parseApiResponse(http.Response response) {
  final body = jsonDecode(response.body) as Map<String, dynamic>;
  if (response.statusCode >= 400) {
    throw ApiException(
      statusCode: response.statusCode,
      message: body['message'] as String? ?? 'Request failed (${response.statusCode})',
    );
  }
  return body;
}
```

Migrate services to use this one at a time.

---

## Phase 5 — Dead Code Removal (Only After Full Migration)

> **Rule:** Only delete something after `flutter analyze` confirms zero references to it.

| Dead Code | Remove After |
|---|---|
| All 33 local color class/const blocks (`_C`, `_K`, `static const _navy`) | Phase 3 migration of each screen |
| `WaitPill` in `clinical_queue_screen.dart` | After `AppWaitPill` is wired in both `clinical_queue` and `doctor_dashboard` |
| Private `_statCard()` in `dashboard_screen` | After `AppStatCard` wired in `dashboard_screen` |
| Private `_StatCard` in `patients_screen` | After `AppStatCard` wired in `patients_screen` |
| Private `_statCard()` in `doctor_dashboard_screen` | After `AppStatCard` wired in `doctor_dashboard_screen` |
| Private `_FilterCard` in `reports_screen` | After shared filter card is created and wired |
| Private `_FilterCard` in `share_history_screen` | Same |
| Private pagination in 3 screens | After `AppPaginationBar` wired in all 3 |
| Private empty/error state in 12 screens | After `AppEmptyState`/`AppErrorState` wired |
| 2× `_showSnack()` in `share_history_screen` | After `showAppSnackBar` replaces all 3 calls in that file |
| Duplicate `_navy` in `app_drawer.dart` nested classes | After migration of `AppDrawer` |

---

## Phase 6 — Navigation Decoupling (Optional, Low Priority)

> **What:** Replace fragile string-matching in `home_screen.dart` with constants.

### 6.1 — `lib/utils/app_nav_label.dart`

```dart
abstract final class NavLabel {
  // ── OPD ───────────────────────────────────────────────────────────────────
  static const String dashboard      = 'Dashboard';
  static const String patients       = 'Patients';
  static const String shareHistory   = 'Share History';
  static const String focRequests    = 'FOC Requests';

  // ── Clinical ──────────────────────────────────────────────────────────────
  static const String queueDashboard = 'Queue Dashboard';

  // ── OT / Surgery (currently "Coming Soon" in home_screen.dart switch) ─────
  static const String otBookings          = 'OT Bookings';
  static const String accountantBilling   = 'Accountant / Billing';
  static const String wardManagement      = 'Ward Management';
  static const String doctorDashboard     = 'Doctor Dashboard';   // OT doctor, not role-based
  static const String assistantDashboard  = 'Assistant Dashboard';
  static const String dischargeInvoices   = 'Discharge & Invoices';

  // ── Reports ───────────────────────────────────────────────────────────────
  static const String reports        = 'Reports';

  // ── Medicines ─────────────────────────────────────────────────────────────
  static const String medicines          = 'Medicines';
  static const String medicineTypes      = 'Medicine Types';
  static const String medicineCategories = 'Medicine Categories';
  static const String routeOfAdmin       = 'Route of Admin.';
  static const String dosages            = 'Dosages';
  // Note: 'Medicine Groups' is in home_screen switch but NOT in the drawer —
  // likely dead navigation. Keep the constant for safety.
  static const String medicineGroups     = 'Medicine Groups';

  // ── Config ────────────────────────────────────────────────────────────────
  static const String masters       = 'Masters';
  static const String settings      = 'Settings';
  static const String rolesAndPerms = 'Roles & Permissions';
  static const String users         = 'Users';
  static const String myProfile     = 'My Profile';
}
```

### 6.2 — `lib/utils/medicines_tab.dart`

```dart
abstract final class MedicinesTab {
  static const int dosages    = 0;
  static const int types      = 1;
  static const int categories = 2;
  static const int routeAdmin = 3;
  static const int medicines  = 4;
  static const int groups     = 5;
}
```

Prevents reordering `MedicinesScreen` tabs from silently breaking 6 navigation cases in `home_screen.dart`.

---

## Phase 7 — Per-Client Theme Support

> **Prerequisite:** ALL of Phase 3 must be complete. Client themes only work if colors come from `AppColors`, not hardcoded.

### 7.1 — `lib/config/client_theme.dart`

```dart
import 'package:flutter/material.dart';

class ClientTheme {
  final Color primary;
  final Color primaryLight;
  final Color secondary;
  final Color background;

  const ClientTheme({
    required this.primary,
    required this.primaryLight,
    required this.secondary,
    required this.background,
  });
}
```

### 7.2 — Runtime override in `AppColors`

Convert `AppColors` from `abstract final class` (pure static) to a class with runtime override support:

```dart
class AppColors {
  // Private backing fields
  static Color _primary      = const Color(0xFF1B4F72);
  static Color _primaryLight = const Color(0xFF2471A3);
  static Color _secondary    = const Color(0xFF2E86C1);
  static Color _background   = const Color(0xFFEBF5FB);

  // Public getters
  static Color get primary      => _primary;
  static Color get primaryLight => _primaryLight;
  static Color get secondary    => _secondary;
  static Color get background   => _background;

  // Apply client theme (called at login)
  static void applyTheme(ClientTheme theme) {
    _primary      = theme.primary;
    _primaryLight = theme.primaryLight;
    _secondary    = theme.secondary;
    _background   = theme.background;
    // Recompute alpha variants
    _primaryA12   = _primary.withValues(alpha: 0.12);
    // ... all alpha variants
  }
  // ... same const colors and alpha variants as Phase 0
}
```

### 7.3 — Migrate `AppTextStyles` from `const` to getters

When `AppColors.primary` becomes a getter (not const), any `static const TextStyle` that references `AppColors.primary` or other non-const colors will fail to compile. Convert `AppTextStyles` to use lazy getters:

```dart
// BEFORE (Phase 0 — works while AppColors.primary is static const):
static const TextStyle sectionLabel = TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: AppColors.primary);

// AFTER (Phase 7 — required when AppColors.primary is a getter):
static TextStyle get sectionLabel => const TextStyle(fontSize: 14, fontWeight: FontWeight.w800).copyWith(color: AppColors.primary);
```

Apply this conversion to all `AppTextStyles` that use `AppColors.primary`, `AppColors.secondary`, `AppColors.background` (the theme-overridable colors). TextStyles that use `AppColors.textPrimary`, `AppColors.textSecondary`, `AppColors.textDisabled`, `AppColors.textOnPrimary` only need conversion if those colors are also theme-overridable.

Do this conversion in one pass across `AppTextStyles` — it does not affect any screen or widget files because the API (`AppTextStyles.sectionLabel`) stays identical.

---

### 7.4 — Call `applyTheme` at login

In `AuthService` or `_LoginScreenState._doLogin()`, after getting `HospitalInfo`. The field names (`primaryColor` etc.) depend on how the backend sends theme data — add these fields to `HospitalInfo` model first:

```dart
if (hospital.primaryColor != null) {
  AppColors.applyTheme(ClientTheme(
    primary:      Color(hospital.primaryColor!),
    primaryLight: Color(hospital.primaryLightColor ?? 0xFF2471A3),
    secondary:    Color(hospital.secondaryColor ?? 0xFF2E86C1),
    background:   Color(hospital.backgroundColor ?? 0xFFEBF5FB),
  ));
}
```

This makes the entire app switch to client brand colors on login with zero screen-level changes.

---

## Timeline & Priority

| Phase | Est. Effort | Risk | Priority |
|---|---|---|---|
| Phase 0 — Foundation files (6 new files, create `lib/theme/` dir) | 2–3 hours | **Zero** | Start immediately |
| Phase 1 — ThemeData wire-up | 15 min | Near-zero | Right after Phase 0 |
| Phase 2 — Shared widgets (10 new files + 2 existing file updates) | 3–5 hours | **Near-zero** | Before touching any screen |
| Phase 3 — Screen migration (34 files) | 2–3 days at ~20 min/file | Low per file | Core work |
| Phase 4 — Service layer (17 services) | 2–3 hours | Low-Medium | After Phase 3 stable |
| Phase 5 — Dead code removal | 1–2 hours | **Zero** (only after confirmed) | Final cleanup |
| Phase 6 — Nav decoupling | 1 hour | Low | Optional |
| Phase 7 — Per-client theme + AppTextStyles migration | 3–4 hours | Low-Medium | Final |

---

## What Will NEVER Break (Safety Guarantees)

1. **No API calls are touched** in Phases 0–3. Zero.
2. **No business logic changes** — only colors, styles, widget code.
3. **No widget tree restructuring** — `AppStatCard` wraps the same visual output as the old `_statCard()`.
4. **One screen per session** — if one migration causes a visual issue, only that screen is affected.
5. **Old code stays until replacement is confirmed** — nothing is deleted pre-emptively.
6. **`flutter analyze` after every file** — type errors caught immediately, not at app launch.
7. **Existing widgets stay intact** — `AppDrawer`, `AppSkeletonList`, `PressScaleWrapper` are imported, not rewritten.

---

## End State

After all 7 phases:

| Metric | Before | After |
|---|---|---|
| Files to edit for a brand color change | 36 | **1** (`app_colors.dart`) |
| Files to edit for a card radius change | 34 | **1** (`app_radius.dart`) |
| Local color constant blocks in screens | 34 | **0** |
| Hardcoded shimmer colors in skeleton.dart | 2 | **0** |
| Hardcoded colors in showAppSnackBar | 3 | **0** |
| Raw `showSnackBar` calls | 52 | **0** |
| Missing colors in AppColors (Phase 0 baseline) | N/A | **27 tokens** (base + alpha + semantic) |
| Shared widget files | 3 | **13** |
| Service `get _headers` copies | 17 | **1** (mixin) |
| NavLabel strings that can silently mismatch | All | **0** (all typed constants) |
| Per-client theme support | None | **Yes** (via `AppColors.applyTheme()`) |
| `flutter analyze` warnings from color/style | Many | **0** |
