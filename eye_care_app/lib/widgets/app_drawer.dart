import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/permissions.dart';
import '../models/auth_models.dart';
import '../services/onboarding_service.dart';
import '../services/permission_service.dart';
import '../utils/app_nav_label.dart';
import 'secret_tap_area.dart';

class AppDrawer extends StatelessWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final VoidCallback onLogout;
  final void Function(String label)? onNavigate;

  const AppDrawer({
    super.key,
    required this.user,
    required this.hospital,
    required this.onLogout,
    this.onNavigate,
  });


  @override
  Widget build(BuildContext context) {
    final p = PermissionService.instance;
    final isDoctor = user.role?.slug == 'doctor';

    final opdItems = [
      if (p.can(Perm.opdPatientView)) ...[
        const _Item(Icons.people_outline_rounded, NavLabel.patients),
        const _Item(Icons.share_outlined, NavLabel.shareHistory),
      ],
      if (p.can(Perm.opdFocCreate) || p.can(Perm.opdFocAccept))
        const _Item(Icons.receipt_long_rounded, NavLabel.focRequests),
    ];

    final clinicalItems = [
      if (p.can(Perm.opdExamPrimary) || p.can(Perm.opdExamSecondary))
        const _Item(Icons.queue_rounded, NavLabel.queueDashboard),
    ];

    final otItems = [
      if (p.can(Perm.otAppointmentView))
        const _Item(Icons.event_note_rounded, NavLabel.otAppointments),
      if (p.can(Perm.otCounsellingFill))
        const _Item(Icons.support_agent_rounded, NavLabel.otCounsellor),
      if (p.can(Perm.otPaymentRecord))
        const _Item(Icons.account_balance_wallet_rounded, NavLabel.accountantBilling),
      if (p.can(Perm.otWardEntry))
        const _Item(Icons.bed_rounded, NavLabel.wardManagement),
      // Web has one merged "OT Assistant Dashboard" nav entry gated by
      // lens.record/lens.implant/surgery.ready/surgery.record combined — no
      // separate "Doctor Dashboard" (the old OT Doctor role's surgery
      // recording was absorbed into OT Assistant, see
      // ot_assistant_dashboard_screen.dart).
      if (p.can(Perm.otLensRecord) || p.can(Perm.otLensImplant) || p.can(Perm.otPatientList) || p.can(Perm.otSurgeryReady) || p.can(Perm.otSurgeryRecord))
        const _Item(Icons.handyman_rounded, NavLabel.assistantDashboard),
      if (p.can(Perm.otBillingManage))
        const _Item(Icons.receipt_long_rounded, NavLabel.dischargeInvoices),
    ];

    final reportsItems = [
      if (!isDoctor && p.can(Perm.reportsView))
        const _Item(Icons.bar_chart_rounded, NavLabel.reports),
    ];

    final medicineItems = [
      if (p.can(Perm.masterMedicines)) ...[
        const _Item(Icons.local_pharmacy_rounded, NavLabel.medicines),
        const _Item(Icons.label_rounded, NavLabel.medicineTypes),
        const _Item(Icons.folder_rounded, NavLabel.medicineCategories),
        const _Item(Icons.alt_route_rounded, NavLabel.routeOfAdmin),
        const _Item(Icons.science_rounded, NavLabel.dosages),
      ],
    ];

    final configItems = [
      if (p.can(Perm.masterCaseTypes) || p.can(Perm.masterEyeExam) || p.can(Perm.masterLocations))
        const _Item(Icons.tune_rounded, NavLabel.masters),
      if (!isDoctor && p.can(Perm.settingsHospital))
        const _Item(Icons.settings_outlined, NavLabel.settings),
      if (!isDoctor && p.can(Perm.masterRoles))
        const _Item(Icons.admin_panel_settings_rounded, NavLabel.rolesAndPerms),
      if (!isDoctor && (p.can(Perm.masterDoctors) || p.can(Perm.masterReceptions) || p.can(Perm.masterOtStaff)))
        const _Item(Icons.manage_accounts_rounded, NavLabel.users),
    ];

    return Drawer(
      width: 296,
      backgroundColor: AppColors.drawerBackground,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.only(
          topRight: Radius.circular(24),
          bottomRight: Radius.circular(24),
        ),
      ),
      child: Column(
        children: [
          _buildHeader(context),
          Expanded(
            child: ListView(
              padding: const EdgeInsets.fromLTRB(14, 14, 14, 8),
              children: [
                _buildDashboardTile(context),
                // OPD + Clinical side-by-side
                if (opdItems.isNotEmpty || clinicalItems.isNotEmpty) ...[
                  const SizedBox(height: 20),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (opdItems.isNotEmpty)
                        Expanded(
                          child: _SmallAccordion(
                            title: 'OPD',
                            items: opdItems,
                            onNavigate: (l) => _go(context, l),
                          ),
                        ),
                      if (opdItems.isNotEmpty && clinicalItems.isNotEmpty)
                        const SizedBox(width: 10),
                      if (clinicalItems.isNotEmpty)
                        Expanded(
                          child: _SmallAccordion(
                            title: 'Clinical',
                            items: clinicalItems,
                            onNavigate: (l) => _go(context, l),
                          ),
                        ),
                    ],
                  ),
                ],
                // OT / Surgery
                if (otItems.isNotEmpty) ...[
                  const SizedBox(height: 10),
                  _FullAccordion(
                    icon: Icons.calendar_month_rounded,
                    label: 'Department Hub',
                    sublabel: 'OT / SURGERY',
                    items: otItems,
                    onNavigate: (l) => _go(context, l),
                  ),
                ],
                // Reports
                if (reportsItems.isNotEmpty) ...[
                  const SizedBox(height: 10),
                  _FullAccordion(
                    icon: Icons.analytics_rounded,
                    label: NavLabel.reports,
                    items: reportsItems,
                    onNavigate: (l) => _go(context, l),
                  ),
                ],
                // Medicines
                if (medicineItems.isNotEmpty) ...[
                  const SizedBox(height: 10),
                  _FullAccordion(
                    icon: Icons.medication_rounded,
                    label: NavLabel.medicines,
                    items: medicineItems,
                    onNavigate: (l) => _go(context, l),
                  ),
                ],
                // Config
                if (configItems.isNotEmpty) ...[
                  const SizedBox(height: 10),
                  _FullAccordion(
                    icon: Icons.settings_rounded,
                    label: 'Config',
                    items: configItems,
                    onNavigate: (l) => _go(context, l),
                  ),
                ],
                const SizedBox(height: 12),
              ],
            ),
          ),
          _buildFooter(context),
        ],
      ),
    );
  }

  void _go(BuildContext context, String label) {
    Navigator.pop(context);
    if (onNavigate != null) {
      onNavigate!(label);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('$label — Coming Soon'),
          behavior: SnackBarBehavior.floating,
          backgroundColor: AppColors.primary,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
          duration: const Duration(seconds: 2),
        ),
      );
    }
  }

  // ── Header ────────────────────────────────────────────────────────────────

  Widget _buildHeader(BuildContext context) {
    final initials = hospital.name.isNotEmpty
        ? hospital.name.trim().split(RegExp(r'\s+')).map((w) => w[0]).take(2).join().toUpperCase()
        : '?';
    // Hidden dev entry: tap the header 5x within 2s to reset onboarding —
    // same secret-gesture pattern as the login screen's logo, so it works
    // in release builds without ever being a visibly discoverable button.
    return SecretTapArea(
      onTrigger: () async {
        await OnboardingService.instance.resetForTesting();
        if (!context.mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Onboarding will show again on next app open.')));
      },
      child: Container(
      color: AppColors.primary,
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 20),
          child: Row(
            children: [
              Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.20),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: Colors.white.withValues(alpha: 0.12)),
                ),
                alignment: Alignment.center,
                child: Text(
                  initials,
                  style: const TextStyle(
                    fontWeight: FontWeight.w900,
                    fontSize: 14,
                    color: Colors.white,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      hospital.name.toUpperCase(),
                      style: const TextStyle(
                        fontWeight: FontWeight.w900,
                        fontSize: 15,
                        color: Colors.white,
                        letterSpacing: -0.2,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 3),
                    Text(
                      'HOSPITAL WORKSPACE',
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 9,
                        color: Colors.white.withValues(alpha: 0.60),
                        letterSpacing: 1.8,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
      ),
    );
  }

  // ── Dashboard active tile ─────────────────────────────────────────────────

  Widget _buildDashboardTile(BuildContext context) {
    return Container(
      decoration: _clusterDeco(),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () {
          Navigator.pop(context);
          onNavigate?.call(NavLabel.dashboard);
        },
        child: Padding(
          padding: const EdgeInsets.all(8),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.10),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Row(
              children: [
                Icon(Icons.grid_view_rounded, color: AppColors.primary, size: 20),
                SizedBox(width: 12),
                Text(
                  'Dashboard',
                  style: TextStyle(
                    fontWeight: FontWeight.w900,
                    fontSize: 14,
                    color: AppColors.primary,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // ── Footer ────────────────────────────────────────────────────────────────

  Widget _buildFooter(BuildContext context) {
    final isAdmin = user.role?.slug == 'hospital_admin';

    // Compute user initials for the profile row avatar
    final nameParts = user.name.trim().split(RegExp(r'\s+')).where((p) => p.isNotEmpty).toList();
    final words     = nameParts.where((p) => !(p.length <= 4 && p.endsWith('.'))).toList();
    final effective = words.isEmpty ? nameParts : words;
    final initials  = effective.isEmpty
        ? '?'
        : effective.length == 1
            ? effective[0].substring(0, effective[0].length >= 2 ? 2 : 1).toUpperCase()
            : '${effective[0][0]}${effective[1][0]}'.toUpperCase();

    return Container(
      color: const Color(0xFFEDEEF1),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 20),
          child: Column(
            children: [
              // My Profile row — hidden for hospital admin (all info is in Settings)
              if (!isAdmin)
                Container(
                  margin: const EdgeInsets.only(bottom: 10),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: AppColors.primary.withValues(alpha: 0.07)),
                    boxShadow: [
                      BoxShadow(
                        color: AppColors.primary.withValues(alpha: 0.04),
                        blurRadius: 8,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: InkWell(
                    onTap: () {
                      Navigator.pop(context);
                      onNavigate?.call(NavLabel.myProfile);
                    },
                    borderRadius: BorderRadius.circular(14),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
                      child: Row(
                        children: [
                          // Avatar with initials
                          Container(
                            width: 36, height: 36,
                            alignment: Alignment.center,
                            decoration: BoxDecoration(
                              color: AppColors.primary.withValues(alpha: 0.10),
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: AppColors.primary.withValues(alpha: 0.10)),
                            ),
                            child: Text(
                              initials,
                              style: TextStyle(
                                fontWeight: FontWeight.w900,
                                fontSize: 13,
                                color: AppColors.primary,
                              ),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  user.name,
                                  style: TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w700,
                                    color: AppColors.primary,
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                ),
                                if (user.role != null)
                                  Text(
                                    user.role!.name,
                                    style: TextStyle(
                                      fontSize: 11,
                                      fontWeight: FontWeight.w600,
                                      color: AppColors.textSecondary.withValues(alpha: 0.80),
                                    ),
                                  ),
                              ],
                            ),
                          ),
                          Icon(Icons.arrow_forward_ios_rounded,
                              size: 12,
                              color: AppColors.textSecondary.withValues(alpha: 0.40)),
                        ],
                      ),
                    ),
                  ),
                ),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () {
                    Navigator.pop(context);
                    onLogout();
                  },
                  icon: const Icon(Icons.logout_rounded, size: 18),
                  label: const Text(
                    'LOGOUT ACCOUNT',
                    style: TextStyle(
                      fontWeight: FontWeight.w900,
                      fontSize: 13,
                      letterSpacing: 0.5,
                    ),
                  ),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.primary,
                    side: BorderSide(color: AppColors.primary.withValues(alpha: 0.15)),
                    backgroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(18),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 12),
              Text(
                'EYE-SAAS V2.4.0-STABLE',
                style: TextStyle(
                  fontSize: 9.5,
                  fontWeight: FontWeight.w700,
                  color: AppColors.textSecondary.withValues(alpha: 0.55),
                  letterSpacing: 2,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ── Cluster card decoration ───────────────────────────────────────────────────

BoxDecoration _clusterDeco() => BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(AppRadius.xl),
      border: Border.all(
        color: AppColors.primaryA08,
      ),
      boxShadow: [
        BoxShadow(
          color: AppColors.primaryA05,
          blurRadius: 12,
          offset: const Offset(0, 4),
        ),
      ],
    );

// ── Nav item data ─────────────────────────────────────────────────────────────

class _Item {
  final IconData icon;
  final String label;
  const _Item(this.icon, this.label);
}

// ── Small 2-column accordion (OPD & Clinical) ─────────────────────────────────

class _SmallAccordion extends StatefulWidget {
  final String title;
  final List<_Item> items;
  final void Function(String) onNavigate;

  const _SmallAccordion({
    required this.title,
    required this.items,
    required this.onNavigate,
  });

  @override
  State<_SmallAccordion> createState() => _SmallAccordionState();
}

class _SmallAccordionState extends State<_SmallAccordion> {
  bool _open = false;


  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: _clusterDeco(),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header row — taps to toggle
          InkWell(
            onTap: () => setState(() => _open = !_open),
            child: Padding(
              padding: const EdgeInsets.fromLTRB(12, 12, 12, 10),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    widget.title,
                    style: TextStyle(
                      fontWeight: FontWeight.w900,
                      fontSize: 12,
                      letterSpacing: 1.5,
                      color: AppColors.primary,
                    ),
                  ),
                  AnimatedRotation(
                    turns: _open ? 0.5 : 0,
                    duration: const Duration(milliseconds: 250),
                    child: Icon(Icons.expand_more_rounded, size: 18, color: Colors.grey[400]),
                  ),
                ],
              ),
            ),
          ),
          // Sub-items
          AnimatedCrossFade(
            firstChild: const SizedBox.shrink(),
            secondChild: Padding(
              padding: const EdgeInsets.fromLTRB(0, 0, 0, 8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: widget.items
                    .map(
                      (item) => InkWell(
                        onTap: () => widget.onNavigate(item.label),
                        child: Padding(
                          padding: const EdgeInsets.fromLTRB(12, 6, 12, 6),
                          child: Row(
                            children: [
                              Icon(item.icon, size: 14, color: Colors.grey[400]),
                              const SizedBox(width: 6),
                              Expanded(
                                child: Text(
                                  item.label,
                                  style: const TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w700,
                                    color: AppColors.textSecondary,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    )
                    .toList(),
              ),
            ),
            crossFadeState: _open ? CrossFadeState.showSecond : CrossFadeState.showFirst,
            duration: const Duration(milliseconds: 250),
          ),
        ],
      ),
    );
  }
}

// ── Full-width accordion ───────────────────────────────────────────────────────

class _FullAccordion extends StatefulWidget {
  final IconData icon;
  final String label;
  final String? sublabel;
  final List<_Item> items;
  final void Function(String) onNavigate;

  const _FullAccordion({
    required this.icon,
    required this.label,
    this.sublabel,
    required this.items,
    required this.onNavigate,
  });

  @override
  State<_FullAccordion> createState() => _FullAccordionState();
}

class _FullAccordionState extends State<_FullAccordion> {
  bool _open = false;


  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: _clusterDeco(),
      clipBehavior: Clip.antiAlias,
      child: Column(
        children: [
          // Header — taps to toggle
          InkWell(
            onTap: () => setState(() => _open = !_open),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              child: Row(
                children: [
                  Icon(
                    widget.icon,
                    size: 22,
                    color: AppColors.textSecondary.withValues(alpha: 0.75),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: widget.sublabel != null
                        ? Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                widget.sublabel!,
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w900,
                                  letterSpacing: 1.2,
                                  color: AppColors.textSecondary.withValues(alpha: 0.65),
                                ),
                              ),
                              const SizedBox(height: 1),
                              Text(
                                widget.label,
                                style: TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w900,
                                  color: AppColors.primary,
                                ),
                              ),
                            ],
                          )
                        : Text(
                            widget.label.toUpperCase(),
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w900,
                              color: AppColors.primary,
                            ),
                          ),
                  ),
                  AnimatedRotation(
                    turns: _open ? 0.5 : 0,
                    duration: const Duration(milliseconds: 250),
                    child: Icon(
                      Icons.expand_more_rounded,
                      size: 18,
                      color: Colors.grey[400],
                    ),
                  ),
                ],
              ),
            ),
          ),
          // Sub-items panel
          AnimatedCrossFade(
            firstChild: const SizedBox.shrink(),
            secondChild: Container(
              decoration: BoxDecoration(
                border: Border(
                  top: BorderSide(
                    color: AppColors.primaryA07,
                  ),
                ),
                color: const Color(0xFFFAFAFC),
              ),
              child: Column(
                children: widget.items.map(_buildSubItem).toList(),
              ),
            ),
            crossFadeState: _open ? CrossFadeState.showSecond : CrossFadeState.showFirst,
            duration: const Duration(milliseconds: 280),
          ),
        ],
      ),
    );
  }

  Widget _buildSubItem(_Item item) {
    return InkWell(
      onTap: () => widget.onNavigate(item.label),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(22, 10, 16, 10),
        child: Row(
          children: [
            Container(
              width: 6,
              height: 6,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.grey[300],
              ),
            ),
            const SizedBox(width: 12),
            Icon(item.icon, size: 15, color: Colors.grey[400]),
            const SizedBox(width: 8),
            Text(
              item.label,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w700,
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
