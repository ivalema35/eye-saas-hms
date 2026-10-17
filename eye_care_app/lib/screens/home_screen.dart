import 'dart:ui';
import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../widgets/app_animations.dart';
import '../constants/permissions.dart';
import '../models/auth_models.dart';
import '../models/permission_matrix.dart';
import '../services/auth_service.dart';
import '../services/onboarding_service.dart';
import '../services/patient_service.dart';
import '../services/permission_service.dart';
import '../utils/app_nav_label.dart';
import '../utils/medicines_tab.dart';
import '../utils/refreshable.dart';
import '../widgets/app_drawer.dart';
import '../widgets/permission_guard.dart';
import 'dashboard_screen.dart';
import 'login_screen.dart';
import 'patients_screen.dart';
import 'clinical_queue_screen.dart';
import 'masters_screen.dart';
import 'reports_screen.dart';
import 'share_history_screen.dart';
import 'medicines_screen.dart';
import 'profile_screen.dart';
import 'settings_screen.dart';
import 'users_screen.dart';
// FOC Requests disabled at client's request — see foc_screen.dart.
// import 'foc_screen.dart';
import 'roles_screen.dart';
import 'doctor_dashboard_screen.dart';
import 'onboarding_screen.dart';
import 'ot_appointment_list_screen.dart';
import 'ot_counsellor_dashboard_screen.dart';
import 'ot_accountant_dashboard_screen.dart';
import 'ot_discharge_dashboard_screen.dart';
import 'ot_assistant_dashboard_screen.dart';
import 'ot_ward_queue_screen.dart';
import 'ot_reports_screen.dart';
import '../utils/app_route.dart';
import '../widgets/receptionist_quick_bubble.dart';


class HomeScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;

  const HomeScreen({super.key, required this.user, required this.hospital});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen>
    with WidgetsBindingObserver, TickerProviderStateMixin {
  late UserInfo _activeUser;
  int _currentIndex = 0;
  final _scaffoldKey = GlobalKey<ScaffoldState>();

  final Map<int, Widget> _builtPages   = {};
  final Set<int>         _visitedPages = {0};

  // One key per tab — lets _silentRefresh() reach the currently visible
  // module's own reload method on app resume without knowing its type.
  // See ACCESS_CONTROL_AND_DATA_SYNC_PLAN.md Phase 4.
  final List<GlobalKey> _pageKeys = List.generate(5, (_) => GlobalKey());

  // One controller per page — drives per-page cross-fade + scale.
  // Forward = page coming in (fade 0→1, scale 0.96→1.0).
  // Reverse = page going out (fade 1→0, scale 1.0→0.96).
  late final List<AnimationController> _pageCtrl;

  @override
  void initState() {
    super.initState();
    _activeUser = widget.user;
    receptionBubbleRefresh = () async {
      final pageState = _pageKeys[_currentIndex].currentState;
      if (pageState is Refreshable) await (pageState as Refreshable).refreshSilently();
    };
    WidgetsBinding.instance.addObserver(this);

    _pageCtrl = List.generate(5, (i) => AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 280),
      value: i == 0 ? 1.0 : 0.0,   // page 0 starts fully visible
    ));

    PermissionService.instance.addListener(_onPermissionsChanged);
    WidgetsBinding.instance.addPostFrameCallback((_) => _maybeShowOnboarding());
  }

  Future<void> _maybeShowOnboarding() async {
    if (!await OnboardingService.instance.shouldShow()) return;
    final slides = OnboardingService.instance.slidesForCurrentUser(roleSlug: _activeUser.role?.slug);
    if (slides == null || !mounted) return;
    Navigator.of(context).push(
      PageRouteBuilder(
        pageBuilder: (_, _, _) => OnboardingScreen(slides: slides, onDone: () => Navigator.of(context).pop()),
        transitionsBuilder: (_, animation, _, child) => FadeTransition(opacity: animation, child: child),
        transitionDuration: const Duration(milliseconds: 300),
      ),
    );
  }

  @override
  void dispose() {
    receptionBubbleRefresh = null;
    PermissionService.instance.removeListener(_onPermissionsChanged);
    for (final c in _pageCtrl) c.dispose();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    // Throttled + single-flight inside AuthService. A lost session is routed
    // to login by AccessGuard; a network error keeps the current permissions.
    if (state == AppLifecycleState.resumed) {
      AuthService.instance.refreshMe(fromResume: true);
    }
  }

  /// Runs whenever PermissionService actually changes (resume, a newer
  /// X-Permissions-Version header, or a 403) — rebuilds nav + pages in place.
  void _onPermissionsChanged() {
    if (!mounted || !PermissionService.instance.isLoaded) return;
    final user = AuthService.instance.activeUser;
    if (user != null) {
      if (_activeUser.role?.slug != user.role?.slug) _resetShell();
      _activeUser = user;
    }
    final visible = _visibleNavItems.map((c) => c.screenIndex).toSet();
    if (!visible.contains(_currentIndex)) _showPageInstantly(0);

    // Cached page widgets are reused as-is, so setState alone would not
    // re-run their permission checks.
    setState(() {});
    rebuildPermissionSubtree(context);

    final pageState = _pageKeys[_currentIndex].currentState;
    if (pageState is Refreshable) (pageState as Refreshable).refreshSilently();
  }

  /// Doctor and non-doctor shells build different pages per index, so a role
  /// switch between them drops every cached page (fresh keys avoid reusing a
  /// GlobalKey across two different widget types).
  void _resetShell() {
    _builtPages.clear();
    _visitedPages
      ..clear()
      ..add(0);
    for (var i = 0; i < _pageKeys.length; i++) {
      _pageKeys[i] = GlobalKey();
    }
    _showPageInstantly(0);
  }

  void _showPageInstantly(int index) {
    for (var i = 0; i < _pageCtrl.length; i++) {
      _pageCtrl[i].value = i == index ? 1.0 : 0.0;
    }
    _visitedPages.add(index);
    _currentIndex = index;
  }

  bool get _isDoctorUser => _activeUser.role?.slug == 'doctor';

  List<_NavConfig> get _visibleNavItems {
    final p = PermissionService.instance;
    // Web doctor shell (hospital/layouts/app.blade.php's `.black-menu-bar`,
    // confirmed 2026-09-21) is a top navbar: Dashboards | Diagnosis Master |
    // Medicine Master | History. There's no phone-width equivalent of a
    // horizontal top navbar, so — same content, mobile-idiomatic
    // presentation — these 4 live as bottom-nav tabs instead of a drawer
    // (which is removed entirely for doctor; see `drawer:` in build() and
    // DoctorDashboardScreen's avatar menu for where Profile/Logout moved
    // to). Short single-word labels only — "Diagnosis Master"/"Medicine
    // Master" don't fit this bar's label slot at 4 tabs wide. Permission
    // gates mirror AppDrawer._buildDoctorDrawer's `showDiagnosis`/
    // medicineItems/showHistory exactly — same doctor menu, same rules,
    // just relocated to the bottom bar.
    if (_isDoctorUser) {
      final showDiagnosis = p.canAccessMasters &&
          p.canAny([
            for (final feature in PermissionMatrix.eyeExamFeatures)
              ...[
                Perm.view(feature),
                Perm.add(feature),
                Perm.edit(feature),
                Perm.delete(feature),
              ],
          ]);
      return [
        const _NavConfig(0, Icons.home_rounded, 'Home'),
        if (showDiagnosis) const _NavConfig(2, Icons.remove_red_eye_rounded, 'Diagnosis'),
        if (p.canFeature('master', 'medicine')) const _NavConfig(3, Icons.local_pharmacy_rounded, 'Medicines'),
        if (p.can(Perm.opdExamHistory)) const _NavConfig(4, Icons.history_rounded, 'History'),
      ];
    }
    return [
      const _NavConfig(0, Icons.home_rounded, 'Home'),
      // Web gates this by opd.patient.view ALONE — see
      // ROLES_PERMISSIONS_DEEP_AUDIT_ROUND2.md Part A.
      if (p.can(Perm.opdPatientView))
        const _NavConfig(1, Icons.people_alt_rounded, 'Patients'),
      if (p.canAccessMasters)
        const _NavConfig(2, Icons.tune_rounded, 'Masters'),
      if (p.can(Perm.reportsView) || p.can(Perm.opdReportsView))
        const _NavConfig(3, Icons.bar_chart_rounded, 'Reports'),
      if (p.can(Perm.opdExamPrimary) || p.can(Perm.opdExamSecondary))
        const _NavConfig(4, Icons.queue_rounded, 'Queue'),
    ];
  }

  void _goTo(int index) {
    if (index == _currentIndex) return;
    _pageCtrl[_currentIndex].reverse();   // old page: fade out + scale back
    _pageCtrl[index].forward();           // new page: fade in + scale forward
    setState(() {
      _visitedPages.add(index);
      _currentIndex = index;
    });
  }

  Widget _buildPage(int i) {
    switch (i) {
      case 0:
        if (_isDoctorUser) {
          return DoctorDashboardScreen(
            key: _pageKeys[i],
            user: _activeUser,
            hospital: widget.hospital,
            onLogout: _logout,
          );
        }
        return DashboardScreen(
          key: _pageKeys[i],
          user: _activeUser,
          hospital: widget.hospital,
          onMenuTap: () => _scaffoldKey.currentState?.openDrawer(),
          onNavigateToTab: _goTo,
        );
      case 1:
        return PatientsScreen(
          key: _pageKeys[i],
          user: _activeUser,
          hospital: widget.hospital,
          onMenuTap: () => _scaffoldKey.currentState?.openDrawer(),
        );
      case 2:
        // Doctor's "Diagnosis" tab — same MastersScreen, restricted to the
        // 3 sub-groups web's own doctor navbar links to (see
        // MastersScreen.eyeExamOnly's doc comment). No onMenuTap: doctor
        // has no drawer to open.
        if (_isDoctorUser) {
          return MastersScreen(key: _pageKeys[i], user: _activeUser, hospital: widget.hospital, eyeExamOnly: true);
        }
        return MastersScreen(
          key: _pageKeys[i],
          user: _activeUser,
          hospital: widget.hospital,
          onMenuTap: () => _scaffoldKey.currentState?.openDrawer(),
        );
      case 3:
        // Doctor's "Medicines" tab — MedicinesScreen already tabs across
        // all 6 web sub-items (Dosage/Medicine Type/Medicine
        // Category/Mode/Medicines/Medicine Groups) internally, so this
        // needs no separate sub-nav, same pattern as "Diagnosis" above.
        if (_isDoctorUser) {
          return MedicinesScreen(key: _pageKeys[i], user: _activeUser, hospital: widget.hospital);
        }
        return ReportsScreen(
          key: _pageKeys[i],
          user: _activeUser,
          hospital: widget.hospital,
          onMenuTap: () => _scaffoldKey.currentState?.openDrawer(),
        );
      case 4:
        // Doctor's "History" tab — was a drawer→pushed route; now embedded
        // directly like every other tab. Same screen/permission/default
        // tab (Patient History) the drawer already used.
        if (_isDoctorUser) {
          return ShareHistoryScreen(key: _pageKeys[i], user: _activeUser, hospital: widget.hospital);
        }
        return ClinicalQueueScreen(
          key: _pageKeys[i],
          user: _activeUser,
          hospital: widget.hospital,
          onMenuTap: () => _scaffoldKey.currentState?.openDrawer(),
        );
      default:
        return const SizedBox.shrink();
    }
  }

  Future<void> _logout() async {
    PermissionService.instance.clear();
    // Best-effort — don't let a stale patients cache linger for whichever
    // account logs in next on this device. See
    // ROLES_PERMISSIONS_DEEP_AUDIT_ROUND2.md Part B.
    await PatientService.instance.clearCache(
      PatientService.scopeFor(hospitalSlug: widget.hospital.slug, userId: _activeUser.id),
    );
    await AuthService.instance.logout();
    if (!mounted) return;
    Navigator.of(context).pushReplacement(
      appRoute(const LoginScreen()),
    );
  }

  /// Pushes a drawer destination behind the same gate that made its drawer
  /// entry visible (see AppDrawer), so a live permission refresh that removes
  /// that access pops the screen back here.
  void _pushGuarded(Widget screen, bool Function(PermissionService p) allowed) {
    Navigator.push(context, appRoute(PermissionGuard(allowed: allowed, child: screen)));
  }

  static bool _canMedicines(PermissionService p) => p.canFeature('master', 'medicine');

  void _openMedicines(int tab) => _pushGuarded(
        MedicinesScreen(user: _activeUser, hospital: widget.hospital, initialTab: tab),
        _canMedicines,
      );

  void _onDrawerNavigate(String label) {
    switch (label) {
      case NavLabel.dashboard:
        _goTo(0);
      case NavLabel.patients:
        _goTo(1);
      case NavLabel.shareHistory:
      case NavLabel.history:
        _pushGuarded(
          ShareHistoryScreen(user: _activeUser, hospital: widget.hospital),
          (p) => p.can(Perm.opdExamHistory),
        );
      case NavLabel.queueDashboard:
        _goTo(4);
      case NavLabel.reports:
        _goTo(3);
      case NavLabel.otReports:
        _pushGuarded(const OtReportsScreen(), (p) => p.can(Perm.reportsView) || p.can(Perm.otReportsView));
      case NavLabel.medicines:
      case NavLabel.medicineMaster:
        _openMedicines(MedicinesTab.medicines);
      case NavLabel.medicineGroups:
        _openMedicines(MedicinesTab.groups);
      case NavLabel.wardMedicine:
        _openMedicines(MedicinesTab.ward);
      case NavLabel.medicineTypes:
        _openMedicines(MedicinesTab.types);
      case NavLabel.medicineCategories:
        _openMedicines(MedicinesTab.categories);
      case NavLabel.routeOfAdmin:
        _openMedicines(MedicinesTab.routeAdmin);
      case NavLabel.dosages:
        _openMedicines(MedicinesTab.dosages);
      case NavLabel.myProfile:
        Navigator.push(context, appRoute(ProfileScreen(user: _activeUser, hospital: widget.hospital)));
      case NavLabel.settings:
        _pushGuarded(
          SettingsScreen(user: _activeUser, hospital: widget.hospital),
          (p) => p.can(Perm.settingsHospital),
        );
      case NavLabel.masters:
      case NavLabel.diagnosisMaster:
        _pushGuarded(
          MastersScreen(user: _activeUser, hospital: widget.hospital),
          (p) => p.canAccessMasters,
        );
      case NavLabel.users:
        _pushGuarded(
          UsersScreen(user: _activeUser, hospital: widget.hospital),
          (p) =>
              p.canFeature('master', 'user_doctor') ||
              p.canFeature('master', 'user_reception') ||
              p.canFeature('master', 'user_ot_staff'),
        );
      // FOC Requests disabled at client's request — see foc_screen.dart.
      // case NavLabel.focRequests:
      //   Navigator.push(context, appRoute(FocScreen(user: widget.user, hospital: widget.hospital)));
      case NavLabel.rolesAndPerms:
        _pushGuarded(
          RolesScreen(user: _activeUser, hospital: widget.hospital),
          (p) => p.can(Perm.masterRoles),
        );
      case NavLabel.otAppointments:
        _pushGuarded(const OtAppointmentListScreen(), (p) => p.can(Perm.otAppointmentView));
      case NavLabel.otCounsellor:
        _pushGuarded(const OtCounsellorDashboardScreen(), (p) => p.can(Perm.otCounsellingFill));
      case NavLabel.wardManagement:
        _pushGuarded(const OtWardQueueScreen(), (p) => p.can(Perm.otWardEntry));
      case NavLabel.assistantDashboard:
        _pushGuarded(
          OtAssistantDashboardScreen(user: _activeUser),
          (p) => p.canAny([Perm.otLensRecord, Perm.otLensImplant, Perm.otSurgeryReady, Perm.otSurgeryRecord]),
        );
      case NavLabel.accountantBilling:
        _pushGuarded(const OtAccountantDashboardScreen(), (p) => p.can(Perm.otPaymentRecord));
      case NavLabel.dischargeInvoices:
        _pushGuarded(
          const OtDischargeDashboardScreen(),
          (p) => p.canAny([Perm.otDischargeGenerate, Perm.otBillingManage, Perm.otBillPrint, Perm.otCertificatePrint]),
        );
      default:
        showAppSnackBar(context, '$label — Coming Soon', duration: const Duration(seconds: 2));
    }
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: _currentIndex == 0,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) _goTo(0);
      },
      child: Scaffold(
      key: _scaffoldKey,
      extendBody: true,
      backgroundColor: AppColors.background,
      // Doctor has no drawer at all — its 4 web-navbar items now live as
      // bottom-nav tabs (see _visibleNavItems/_buildPage) and Profile/
      // Logout moved to DoctorDashboardScreen's own avatar menu.
      drawer: _isDoctorUser
          ? null
          : AppDrawer(
              user: _activeUser,
              hospital: widget.hospital,
              onLogout: _logout,
              onNavigate: _onDrawerNavigate,
            ),
      body: MediaQuery(
        data: MediaQuery.of(context).copyWith(
          padding: MediaQuery.of(context).padding.copyWith(
            bottom: MediaQuery.of(context).padding.bottom + 92,
          ),
        ),
        child: Stack(
        children: List.generate(5, (i) {
          if (!_visitedPages.contains(i)) return const SizedBox.shrink();
          final page = _builtPages.putIfAbsent(i, () => _buildPage(i));
          return AnimatedBuilder(
            animation: _pageCtrl[i],
            child: page,
            builder: (context, child) {
              final v = _pageCtrl[i].value;
              // Ease the raw controller value so scale feels springy
              final curved = Curves.easeOutCubic.transform(v);
              return Opacity(
                opacity: curved.clamp(0.0, 1.0),
                child: Transform.scale(
                  scale: 0.96 + 0.04 * curved,
                  alignment: Alignment.topCenter,
                  child: IgnorePointer(
                    ignoring: i != _currentIndex,
                    child: child,
                  ),
                ),
              );
            },
          );
        }),
        ),
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(AppRadius.xxl),
            child: BackdropFilter(
              filter: ImageFilter.blur(sigmaX: 12, sigmaY: 12),
              child: Container(
                height: 68,
                decoration: BoxDecoration(
                  color: AppColors.navBarBg,
                  borderRadius: BorderRadius.circular(AppRadius.xxl),
                  boxShadow: [
                    BoxShadow(
                      color: AppColors.black10,
                      blurRadius: 24,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Row(
                  children: _visibleNavItems
                      .map((c) => _NavItem(
                            icon: c.icon,
                            label: c.label,
                            active: _currentIndex == c.screenIndex,
                            onTap: () => _goTo(c.screenIndex),
                          ))
                      .toList(),
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    );
  }
}

// ── Bottom nav config (screen index + display metadata) ──────────────────────

class _NavConfig {
  final int screenIndex;
  final IconData icon;
  final String label;
  const _NavConfig(this.screenIndex, this.icon, this.label);
}

// ── Bottom nav item ───────────────────────────────────────────────────────────

class _NavItem extends StatefulWidget {
  final IconData icon;
  final String label;
  final bool active;
  final VoidCallback onTap;

  const _NavItem({
    required this.icon,
    required this.label,
    required this.active,
    required this.onTap,
  });

  @override
  State<_NavItem> createState() => _NavItemState();
}

class _NavItemState extends State<_NavItem> {
  bool _pressed = false;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTapDown: (_) => setState(() => _pressed = true),
        onTapUp: (_) {
          setState(() => _pressed = false);
          widget.onTap();
        },
        onTapCancel: () => setState(() => _pressed = false),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            AnimatedScale(
              scale: _pressed ? 0.82 : 1.0,
              duration: const Duration(milliseconds: 120),
              curve: Curves.easeOut,
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 250),
                curve: Curves.easeOutBack,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                decoration: BoxDecoration(
                  color: widget.active ? AppColors.primaryA12 : Colors.transparent,
                  borderRadius: BorderRadius.circular(AppRadius.lg),
                ),
                child: Icon(
                  widget.icon,
                  size: 22,
                  color: widget.active ? AppColors.primary : AppColors.textDisabled,
                ),
              ),
            ),
            const SizedBox(height: 2),
            Text(
              widget.label,
              style: TextStyle(
                fontSize: 10,
                fontWeight: widget.active ? FontWeight.w700 : FontWeight.w500,
                color: widget.active ? AppColors.primary : AppColors.textDisabled,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
