import 'dart:ui';
import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../widgets/app_animations.dart';
import '../constants/permissions.dart';
import '../models/auth_models.dart';
import '../services/auth_service.dart';
import '../services/onboarding_service.dart';
import '../services/permission_service.dart';
import '../utils/app_nav_label.dart';
import '../utils/medicines_tab.dart';
import '../widgets/app_drawer.dart';
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
import 'foc_screen.dart';
import 'roles_screen.dart';
import 'doctor_dashboard_screen.dart';
import 'onboarding_screen.dart';
import 'ot_appointment_list_screen.dart';
import 'ot_counsellor_dashboard_screen.dart';
import 'ot_accountant_dashboard_screen.dart';
import 'ot_discharge_dashboard_screen.dart';
import 'ot_assistant_dashboard_screen.dart';
import 'ot_ward_queue_screen.dart';
import '../utils/app_route.dart';


class HomeScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;

  const HomeScreen({super.key, required this.user, required this.hospital});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen>
    with WidgetsBindingObserver, TickerProviderStateMixin {
  int _currentIndex = 0;
  final _scaffoldKey = GlobalKey<ScaffoldState>();

  final Map<int, Widget> _builtPages   = {};
  final Set<int>         _visitedPages = {0};

  // One controller per page — drives per-page cross-fade + scale.
  // Forward = page coming in (fade 0→1, scale 0.96→1.0).
  // Reverse = page going out (fade 1→0, scale 1.0→0.96).
  late final List<AnimationController> _pageCtrl;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);

    _pageCtrl = List.generate(5, (i) => AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 280),
      value: i == 0 ? 1.0 : 0.0,   // page 0 starts fully visible
    ));

    WidgetsBinding.instance.addPostFrameCallback((_) => _maybeShowOnboarding());
  }

  Future<void> _maybeShowOnboarding() async {
    if (!await OnboardingService.instance.shouldShow()) return;
    final slides = OnboardingService.instance.slidesForCurrentUser(roleSlug: widget.user.role?.slug);
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
    for (final c in _pageCtrl) c.dispose();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) _silentRefresh();
  }

  Future<void> _silentRefresh() async {
    try {
      final user = await AuthService.instance.refreshSession();
      if (!mounted) return;
      if (user == null) {
        PermissionService.instance.clear();
        Navigator.of(context).pushReplacement(
          appRoute(const LoginScreen()),
        );
        return;
      }
      PermissionService.instance.load(user.role);
      final visible = _visibleNavItems.map((c) => c.screenIndex).toSet();
      if (!visible.contains(_currentIndex)) {
        setState(() => _currentIndex = 0);
      } else {
        setState(() {});
      }
    } catch (_) {
      // Network error — keep existing permissions, don't disrupt user
    }
  }

  bool get _isDoctorUser => widget.user.role?.slug == 'doctor';

  List<_NavConfig> get _visibleNavItems {
    final p = PermissionService.instance;
    return [
      const _NavConfig(0, Icons.home_rounded, 'Home'),
      if (p.can(Perm.opdPatientView))
        const _NavConfig(1, Icons.people_alt_rounded, 'Patients'),
      if (p.can(Perm.masterCaseTypes) || p.can(Perm.masterEyeExam) || p.can(Perm.masterLocations))
        const _NavConfig(2, Icons.tune_rounded, 'Masters'),
      if (!_isDoctorUser && p.can(Perm.reportsView))
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
            user: widget.user,
            hospital: widget.hospital,
            onMenuTap: () => _scaffoldKey.currentState?.openDrawer(),
          );
        }
        return DashboardScreen(
          user: widget.user,
          hospital: widget.hospital,
          onMenuTap: () => _scaffoldKey.currentState?.openDrawer(),
        );
      case 1:
        return PatientsScreen(
          user: widget.user,
          hospital: widget.hospital,
          onMenuTap: () => _scaffoldKey.currentState?.openDrawer(),
        );
      case 2:
        return MastersScreen(
          user: widget.user,
          hospital: widget.hospital,
          onMenuTap: () => _scaffoldKey.currentState?.openDrawer(),
        );
      case 3:
        return ReportsScreen(
          user: widget.user,
          hospital: widget.hospital,
          onMenuTap: () => _scaffoldKey.currentState?.openDrawer(),
        );
      case 4:
        return ClinicalQueueScreen(
          user: widget.user,
          hospital: widget.hospital,
          onMenuTap: () => _scaffoldKey.currentState?.openDrawer(),
        );
      default:
        return const SizedBox.shrink();
    }
  }

  Future<void> _logout() async {
    PermissionService.instance.clear();
    await AuthService.instance.logout();
    if (!mounted) return;
    Navigator.of(context).pushReplacement(
      appRoute(const LoginScreen()),
    );
  }

  void _onDrawerNavigate(String label) {
    switch (label) {
      case NavLabel.dashboard:
        _goTo(0);
      case NavLabel.patients:
        _goTo(1);
      case NavLabel.shareHistory:
        Navigator.push(
          context,
          appRoute(ShareHistoryScreen(user: widget.user, hospital: widget.hospital)),
        );
      case NavLabel.queueDashboard:
        _goTo(4);
      case NavLabel.reports:
        _goTo(3);
      case NavLabel.medicines:
        Navigator.push(context, appRoute(MedicinesScreen(user: widget.user, hospital: widget.hospital, initialTab: MedicinesTab.medicines)));
      case NavLabel.medicineGroups:
        Navigator.push(context, appRoute(MedicinesScreen(user: widget.user, hospital: widget.hospital, initialTab: MedicinesTab.groups)));
      case NavLabel.medicineTypes:
        Navigator.push(context, appRoute(MedicinesScreen(user: widget.user, hospital: widget.hospital, initialTab: MedicinesTab.types)));
      case NavLabel.medicineCategories:
        Navigator.push(context, appRoute(MedicinesScreen(user: widget.user, hospital: widget.hospital, initialTab: MedicinesTab.categories)));
      case NavLabel.routeOfAdmin:
        Navigator.push(context, appRoute(MedicinesScreen(user: widget.user, hospital: widget.hospital, initialTab: MedicinesTab.routeAdmin)));
      case NavLabel.dosages:
        Navigator.push(context, appRoute(MedicinesScreen(user: widget.user, hospital: widget.hospital, initialTab: MedicinesTab.dosages)));
      case NavLabel.myProfile:
        Navigator.push(context, appRoute(ProfileScreen(user: widget.user, hospital: widget.hospital)));
      case NavLabel.settings:
        Navigator.push(context, appRoute(SettingsScreen(user: widget.user, hospital: widget.hospital)));
      case NavLabel.masters:
        Navigator.push(context, appRoute(MastersScreen(user: widget.user, hospital: widget.hospital)));
      case NavLabel.users:
        Navigator.push(context, appRoute(UsersScreen(user: widget.user, hospital: widget.hospital)));
      case NavLabel.focRequests:
        Navigator.push(context, appRoute(FocScreen(user: widget.user, hospital: widget.hospital)));
      case NavLabel.rolesAndPerms:
        Navigator.push(context, appRoute(RolesScreen(user: widget.user, hospital: widget.hospital)));
      case NavLabel.otAppointments:
        Navigator.push(context, appRoute(const OtAppointmentListScreen()));
      case NavLabel.otCounsellor:
        Navigator.push(context, appRoute(const OtCounsellorDashboardScreen()));
      case NavLabel.wardManagement:
        Navigator.push(context, appRoute(const OtWardQueueScreen()));
      case NavLabel.assistantDashboard:
        Navigator.push(context, appRoute(OtAssistantDashboardScreen(user: widget.user)));
      case NavLabel.accountantBilling:
        Navigator.push(context, appRoute(const OtAccountantDashboardScreen()));
      case NavLabel.dischargeInvoices:
        Navigator.push(context, appRoute(const OtDischargeDashboardScreen()));
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
      drawer: AppDrawer(
        user: widget.user,
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
