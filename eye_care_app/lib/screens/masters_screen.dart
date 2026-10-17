import 'package:flutter/material.dart';
import '../utils/app_route.dart';
import '../constants/app_colors.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_item_grid.dart';
import '../widgets/app_section_card.dart';
import '../widgets/permission_guard.dart';
import '../constants/permissions.dart';
import '../models/auth_models.dart';
import '../services/permission_service.dart';
import 'case_type_master_screen.dart';
import 'duration_master_screen.dart';
import 'generic_master_screen.dart';
import 'location_master_screen.dart';
import 'ot_charge_head_master_screen.dart';
import 'ot_lens_inventory_master_screen.dart';
import 'ot_lens_option_master_screen.dart';
import 'ot_lens_power_master_screen.dart';
import 'ot_package_master_screen.dart';
import 'ot_slot_master_screen.dart';
import 'ot_surgery_type_master_screen.dart';
import 'ot_type_master_screen.dart';
import 'referrer_master_screen.dart';


// ── Data model ────────────────────────────────────────────────────────────────
class _Item {
  final IconData icon;
  final String label;
  final Color color;
  /// API type slug — null means screen not yet built (shows "coming soon").
  final String? slug;
  final String feature;
  final bool hasFavourite;
  final bool hasSeeded;

  const _Item(this.icon, this.label, this.color, {
    this.slug,
    required this.feature,
    this.hasFavourite = false,
    this.hasSeeded = false,
  });
}

// ── Items ─────────────────────────────────────────────────────────────────────
final _basic = [
  _Item(Icons.folder_open_rounded, 'Case Types', AppColors.primary, slug: 'case-types', feature: 'casetype'),
  _Item(Icons.location_on_rounded, 'Locations', AppColors.green, slug: 'locations', feature: 'location'),
  _Item(Icons.people_alt_rounded, 'Referrers', AppColors.orange, slug: 'referrers-crud', feature: 'referrer'),
  _Item(Icons.hourglass_bottom_rounded, 'Durations', Color(0xFF7F8C8D), slug: 'durations', feature: 'duration'),
];

final _ot = [
  _Item(Icons.remove_red_eye_rounded, 'Lens Options', Color(0xFF2C3E50), slug: 'lens-options', feature: 'ot_lens_option'),
  _Item(Icons.schedule_rounded, 'OT Slots', AppColors.primary, slug: 'ot-slots', feature: 'ot_slot'),
  _Item(Icons.label_rounded, 'OT Types', AppColors.green, slug: 'ot-types', feature: 'ot_type'),
  _Item(Icons.cut_rounded, 'Surgery Types', AppColors.orange, slug: 'ot-surgery-types', feature: 'ot_surgery_type'),
  _Item(Icons.payments_rounded, 'Charge Heads', AppColors.teal, slug: 'ot-charge-heads', feature: 'ot_charge'),
  _Item(Icons.inventory_2_rounded, 'Lens Inventory', AppColors.blue, slug: 'ot-lens-inventory', feature: 'ot_inventory'),
  _Item(Icons.lens_rounded, 'Lens Powers', AppColors.purple, slug: 'ot-lens-powers', feature: 'ot_lens_power'),
  _Item(Icons.card_giftcard_rounded, 'Packages', AppColors.tealDark, slug: 'ot-packages', feature: 'ot_package_master'),
];

final _clinical = [
  _Item(Icons.assignment_rounded, 'Chief Complaints', Color(0xFFE74C3C), slug: 'complaints', feature: Perm.eyeExamChiefComplaints, hasFavourite: true),
  _Item(Icons.favorite_rounded, 'K/C/O', AppColors.orange, slug: 'kcos', feature: Perm.eyeExamKco, hasFavourite: true),
  _Item(Icons.history_rounded, 'H/O', AppColors.teal, slug: 'hno', feature: Perm.eyeExamHno, hasFavourite: true),
  _Item(Icons.verified_rounded, 'Diagnoses', AppColors.green, slug: 'diagnoses', feature: Perm.eyeExamDiagnosis, hasFavourite: true),
  _Item(Icons.chat_rounded, 'Advice', AppColors.primary, slug: 'advices', feature: Perm.eyeExamAdvice, hasFavourite: true),
];

final _vision = [
  _Item(Icons.visibility_rounded, 'V/N', AppColors.teal, slug: 'vn', feature: Perm.eyeExamVn),
  _Item(Icons.table_rows_rounded, 'Vn C GL', AppColors.primary, slug: 'vngl', feature: Perm.eyeExamVngl),
  _Item(Icons.view_list_rounded, 'Vn C ST', AppColors.primary, slug: 'vnst', feature: Perm.eyeExamVnst),
  _Item(Icons.visibility_off_rounded, 'PH NV/N', AppColors.teal, slug: 'pnvn', feature: Perm.eyeExamPnvn),
  _Item(Icons.center_focus_strong_rounded, 'NR V/N', AppColors.teal, slug: 'nrvn', feature: Perm.eyeExamNrvn),
  _Item(Icons.lens_rounded, 'SPH / CYL', AppColors.primary, slug: 'sph_cyl', feature: Perm.eyeExamSphCyl, hasSeeded: true),
  _Item(Icons.open_with_rounded, 'Axis', Color(0xFF7F8C8D), slug: 'axis', feature: Perm.eyeExamAxis),
  _Item(Icons.timeline_rounded, 'NCT (IOP)', AppColors.orange, slug: 'nct', feature: Perm.eyeExamNct),
];

final _anterior = [
  _Item(Icons.water_drop_rounded, 'SAC', AppColors.teal, slug: 'sac', feature: Perm.eyeExamSac, hasFavourite: true),
  _Item(Icons.visibility_off_rounded, 'Lid', Color(0xFF7F8C8D), slug: 'lid', feature: Perm.eyeExamLid, hasFavourite: true),
  _Item(Icons.circle_rounded, 'Conjunctiva', Color(0xFFE74C3C), slug: 'conj', feature: Perm.eyeExamConj, hasFavourite: true),
  _Item(Icons.radio_button_checked_rounded, 'Cornea', AppColors.primary, slug: 'cornea', feature: Perm.eyeExamCornea, hasFavourite: true),
  _Item(Icons.layers_rounded, 'A/C', AppColors.teal, slug: 'ac', feature: Perm.eyeExamAc, hasFavourite: true),
  _Item(Icons.adjust_rounded, 'Iris', AppColors.orange, slug: 'iris', feature: Perm.eyeExamIris, hasFavourite: true),
  _Item(Icons.fiber_manual_record_rounded, 'Pupil', Color(0xFF2C3E50), slug: 'pupil', feature: Perm.eyeExamPupil, hasFavourite: true),
  _Item(Icons.camera_rounded, 'Lens', AppColors.green, slug: 'lens', feature: Perm.eyeExamLens, hasFavourite: true),
  _Item(Icons.open_in_full_rounded, 'E/M', Color(0xFF7F8C8D), slug: 'em', feature: Perm.eyeExamEm, hasFavourite: true),
  _Item(Icons.verified_user_rounded, 'Cover Test', AppColors.green, slug: 'covertest', feature: Perm.eyeExamCovertest, hasFavourite: true),
];

const _posterior = [
  _Item(Icons.panorama_fish_eye_rounded, 'Disc', Color(0xFF7F8C8D), slug: 'disc', feature: Perm.eyeExamDisc, hasFavourite: true),
  _Item(Icons.signal_cellular_alt_rounded, 'F/R', Color(0xFF7F8C8D), slug: 'fr', feature: Perm.eyeExamFr, hasFavourite: true),
];

// ── Screen ────────────────────────────────────────────────────────────────────
class MastersScreen extends StatelessWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final VoidCallback? onMenuTap;
  /// True when opened from the doctor bottom-nav's "Diagnosis" tab — web's
  /// own doctor navbar (`hospital/layouts/app.blade.php`'s
  /// `.black-menu-bar`, confirmed 2026-09-21) links ONLY to C/O, KCO, H/O,
  /// O/E (Anterior Segment), FUNDUS (Posterior Segment), DIAGNOSIS, ADVICE
  /// — i.e. exactly the "Clinical" + "Anterior Segment (O/E)" +
  /// "Posterior Segment (Fundus)" sub-groups below, and NEVER Basic
  /// Masters, OT Masters, or "Vision Values" (V/N, Vn C GL, Vn C ST,
  /// PH NV/N, NR V/N, SPH/CYL, Axis, NCT) — those aren't links anywhere in
  /// that dropdown, regardless of what the doctor's permissions would
  /// otherwise allow. See eye_care_tab's identical fix + rationale in
  /// RECORD_CARD_UNIFICATION_PLAN.md's sibling doc for the tablet app.
  final bool eyeExamOnly;

  const MastersScreen({
    super.key,
    required this.user,
    required this.hospital,
    this.onMenuTap,
    this.eyeExamOnly = false,
  });

  @override
  Widget build(BuildContext context) {
    final p = PermissionService.instance;

    // Basic masters — any CRUD verb (web landing parity). Never shown in
    // eyeExamOnly mode — web's doctor navbar has no link to these at all.
    final basicVisible = eyeExamOnly
        ? const <_Item>[]
        : _basic.where((item) => p.canFeature('master', item.feature)).toList();
    final showBasic = basicVisible.isNotEmpty;

    // OT masters — same rule as Basic above.
    final otVisible = eyeExamOnly
        ? const <_Item>[]
        : _ot.where((item) => p.canFeature('master', item.feature)).toList();
    final showOt      = otVisible.isNotEmpty;
    final clinicalVisible = _visibleEyeExamItems(_clinical);
    final visionVisible = eyeExamOnly ? const <_Item>[] : _visibleEyeExamItems(_vision);
    final anteriorVisible = _visibleEyeExamItems(_anterior);
    final posteriorVisible = _visibleEyeExamItems(_posterior);
    final showEyeExam = clinicalVisible.isNotEmpty ||
        visionVisible.isNotEmpty ||
        anteriorVisible.isNotEmpty ||
        posteriorVisible.isNotEmpty;

    var visibleCount = 0;
    if (showBasic)   visibleCount += basicVisible.length;
    if (showOt)      visibleCount += otVisible.length;
    if (showEyeExam) {
      visibleCount += clinicalVisible.length +
          visionVisible.length +
          anteriorVisible.length +
          posteriorVisible.length;
    }

    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        elevation: 0,
        // No drawer to open when embedded as the doctor's bottom-nav tab
        // (onMenuTap is null there) — a disabled greyed-out hamburger icon
        // would be worse than no leading icon at all.
        leading: onMenuTap == null
            ? null
            : IconButton(
                icon: const Icon(Icons.menu_rounded, color: Colors.white),
                onPressed: onMenuTap,
              ),
        title: Text(
          eyeExamOnly ? 'Diagnosis Master' : 'Master Data',
          style: const TextStyle(
            color: Colors.white,
            fontSize: 20,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.3,
          ),
        ),
        actions: [
          Container(
            margin: const EdgeInsets.only(right: 12),
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: AppColors.teal,
              borderRadius: BorderRadius.circular(30),
              boxShadow: [
                BoxShadow(
                  color: AppColors.teal.withValues(alpha: 0.38),
                  blurRadius: 8,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Text(
              '$visibleCount items',
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w800,
                fontSize: 11,
              ),
            ),
          ),
        ],
      ),
      body: CustomScrollView(
        physics: const BouncingScrollPhysics(),
        slivers: [
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(14, 14, 14, 110),
            sliver: SliverList(
              delegate: SliverChildListDelegate([
                // ── Basic ────────────────────────────────────────────────
                if (showBasic) ...[
                  AppSectionCard(
                    icon: Icons.grid_3x3_rounded,
                    title: 'Basic Masters',
                    color: AppColors.primary,
                    count: basicVisible.length,
                    child: _grid(context, basicVisible),
                  ),
                  const SizedBox(height: 14),
                ],

                // ── OT ───────────────────────────────────────────────────
                if (showOt) ...[
                  AppSectionCard(
                    icon: Icons.local_hospital_rounded,
                    title: 'OT Masters',
                    color: AppColors.purple,
                    count: otVisible.length,
                    child: _grid(context, otVisible),
                  ),
                  const SizedBox(height: 14),
                ],

                // ── Eye Exam ─────────────────────────────────────────────
                if (showEyeExam) ...[
                  AppSectionCard(
                    icon: Icons.remove_red_eye_rounded,
                    title: 'Eye Exam Masters',
                    color: AppColors.teal,
                    count: clinicalVisible.length + visionVisible.length +
                        anteriorVisible.length + posteriorVisible.length,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (clinicalVisible.isNotEmpty) ...[
                        _SubGroup(
                            icon: Icons.assignment_outlined, label: 'Clinical'),
                        const SizedBox(height: 10),
                        _grid(context, clinicalVisible),
                        ],
                        if (visionVisible.isNotEmpty) ...[
                        if (clinicalVisible.isNotEmpty) _divider(),
                        _SubGroup(
                            icon: Icons.visibility_outlined,
                            label: 'Vision Values'),
                        const SizedBox(height: 10),
                        _grid(context, visionVisible),
                        ],
                        if (anteriorVisible.isNotEmpty) ...[
                        if (clinicalVisible.isNotEmpty || visionVisible.isNotEmpty) _divider(),
                        _SubGroup(
                            icon: Icons.center_focus_weak_rounded,
                            label: 'Anterior Segment (O/E)'),
                        const SizedBox(height: 10),
                        _grid(context, anteriorVisible),
                        ],
                        if (posteriorVisible.isNotEmpty) ...[
                        if (clinicalVisible.isNotEmpty || visionVisible.isNotEmpty || anteriorVisible.isNotEmpty) _divider(),
                        _SubGroup(
                            icon: Icons.panorama_fish_eye_rounded,
                            label: 'Posterior Segment (Fundus)'),
                        const SizedBox(height: 10),
                        _grid(context, posteriorVisible),
                        ],
                      ],
                    ),
                  ),
                ],

                // ── No access ────────────────────────────────────────────
                if (!showBasic && !showOt && !showEyeExam)
                  _buildNoAccess(),
              ]),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildNoAccess() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 80),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.lock_outline_rounded,
                size: 56, color: AppColors.primary.withValues(alpha: 0.25)),
            const SizedBox(height: 16),
            Text(
              'No Access',
              style: TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.w700,
                  color: AppColors.primary.withValues(alpha: 0.50)),
            ),
            const SizedBox(height: 6),
            Text(
              'Your role has no master data permissions.',
              textAlign: TextAlign.center,
              style: TextStyle(
                  fontSize: 13,
                  color: AppColors.primary.withValues(alpha: 0.35)),
            ),
          ],
        ),
      ),
    );
  }

  static Widget _divider() => const Padding(
        padding: EdgeInsets.symmetric(vertical: 14),
        child: Divider(height: 1, thickness: 1, color: Color(0x111B4F72)),
      );

  static void _open(BuildContext ctx, _Item item) {
    if (item.slug == null) {
      showAppSnackBar(ctx, '${item.label} — Coming Soon', duration: const Duration(seconds: 2));
      return;
    }
    final screen = switch (item.slug) {
      'case-types'     => CaseTypeMasterScreen(accentColor: item.color),
      'referrers-crud' => ReferrerMasterScreen(accentColor: item.color),
      'durations'      => DurationMasterScreen(accentColor: item.color),
      'ot-slots'        => OtSlotMasterScreen(accentColor: item.color),
      'ot-charge-heads'   => OtChargeHeadMasterScreen(accentColor: item.color),
      'ot-surgery-types'  => OtSurgeryTypeMasterScreen(accentColor: item.color),
      'lens-options'      => OtLensOptionMasterScreen(accentColor: item.color),
      'ot-types'          => OtTypeMasterScreen(accentColor: item.color),
      'ot-lens-inventory' => OtLensInventoryMasterScreen(accentColor: item.color),
      'ot-lens-powers'    => OtLensPowerMasterScreen(accentColor: item.color),
      'ot-packages'       => OtPackageMasterScreen(accentColor: item.color),
      'locations'         => LocationMasterScreen(accentColor: item.color),
      _ => GenericMasterScreen(
        title: item.label,
        apiPath: 'masters/detail/${item.slug}',
        accentColor: item.color,
        icon: item.icon,
        hasFavourite: item.hasFavourite,
        hasSeeded: item.hasSeeded,
        isAdvice: item.slug == 'advices',
        feature: item.feature,
      ),
    };
    // Same gate as the tile's visibility, so a live permission change either
    // refreshes the screen's add/edit/delete buttons or pops it.
    Navigator.push(ctx, appRoute(PermissionGuard(
      allowed: (p) => p.canFeature('master', item.feature),
      child: screen,
    )));
  }

  static Widget _grid(BuildContext context, List<_Item> items) => AppItemGrid(
        compact: true,
        items: items.map((i) => AppGridItem(icon: i.icon, label: i.label, color: i.color, onTap: () => _open(context, i))).toList(),
      );

  static List<_Item> _visibleEyeExamItems(List<_Item> items) => items
      .where((item) =>
          PermissionService.instance.canFeature('master', item.feature))
      .toList();
}

// ── Sub-group label ───────────────────────────────────────────────────────────
class _SubGroup extends StatelessWidget {
  final IconData icon;
  final String label;
  const _SubGroup({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(10, 7, 14, 7),
      decoration: BoxDecoration(
        color: AppColors.surfaceFill,
        borderRadius: BorderRadius.circular(10),
        border: Border(
          left: BorderSide(color: AppColors.primary, width: 3),
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon,
              size: 13,
              color: AppColors.primaryA70),
          const SizedBox(width: 7),
          Text(
            label,
            style: TextStyle(
                fontSize: 11.5,
                fontWeight: FontWeight.w800,
                color: AppColors.primary.withValues(alpha: 0.80),
                letterSpacing: 0.1),
          ),
        ],
      ),
    );
  }
}
