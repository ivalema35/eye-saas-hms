import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/platform_admin_models.dart';
import '../services/platform_auth_service.dart';
import '../utils/app_route.dart';
import '../widgets/app_animations.dart';
import 'login_screen.dart';
import 'platform_dashboard_screen.dart';
import 'platform_hospitals_screen.dart';
import 'platform_billing_screen.dart';
import 'platform_audit_logs_screen.dart';
import 'platform_notifications_screen.dart';
import 'platform_plans_screen.dart';
import 'platform_location_master_screen.dart';
import 'platform_medicine_master_screen.dart';
import 'platform_settings_screen.dart';
import 'platform_profile_screen.dart';

class PlatformHomeScreen extends StatefulWidget {
  final PlatformAdmin admin;

  const PlatformHomeScreen({super.key, required this.admin});

  @override
  State<PlatformHomeScreen> createState() => _PlatformHomeScreenState();
}

class _PlatformHomeScreenState extends State<PlatformHomeScreen>
    with TickerProviderStateMixin {
  int _currentIndex = 0;
  final _scaffoldKey = GlobalKey<ScaffoldState>();

  // 3 real tab pages (index 3 = "More" opens drawer, no page)
  static const _pageCount = 3;

  final Map<int, Widget> _builtPages   = {};
  final Set<int>         _visitedPages = {0};

  late final List<AnimationController> _pageCtrl;

  @override
  void initState() {
    super.initState();
    _pageCtrl = List.generate(_pageCount, (i) => AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 280),
      value: i == 0 ? 1.0 : 0.0,
    ));
  }

  @override
  void dispose() {
    for (final c in _pageCtrl) {
      c.dispose();
    }
    super.dispose();
  }

  void _goTo(int index) {
    if (index == _currentIndex) return;
    _pageCtrl[_currentIndex].reverse();
    _pageCtrl[index].forward();
    setState(() {
      _visitedPages.add(index);
      _currentIndex = index;
    });
  }

  Widget _buildPage(int i) {
    switch (i) {
      case 0: return PlatformDashboardScreen(admin: widget.admin, onMenuTap: () => _scaffoldKey.currentState?.openDrawer());
      case 1: return PlatformHospitalsScreen(admin: widget.admin, onMenuTap: () => _scaffoldKey.currentState?.openDrawer());
      case 2: return PlatformBillingScreen(admin: widget.admin,   onMenuTap: () => _scaffoldKey.currentState?.openDrawer());
      default: return const SizedBox.shrink();
    }
  }

  Future<void> _logout() async {
    await PlatformAuthService.instance.logout();
    if (!mounted) return;
    Navigator.of(context).pushReplacement(appRoute(const LoginScreen()));
  }

  void _onDrawerNavigate(String label) {
    Navigator.of(context).pop(); // close drawer
    switch (label) {
      case 'Audit Logs':
        Navigator.push(context, appRoute(PlatformAuditLogsScreen(admin: widget.admin)));
      case 'Notifications':
        Navigator.push(context, appRoute(PlatformNotificationsScreen(admin: widget.admin)));
      case 'Plans':
        Navigator.push(context, appRoute(PlatformPlansScreen(admin: widget.admin)));
      case 'Location Master':
        Navigator.push(context, appRoute(PlatformLocationMasterScreen(admin: widget.admin)));
      case 'Medicine Master':
        Navigator.push(context, appRoute(PlatformMedicineMasterScreen(admin: widget.admin)));
      case 'Settings':
        Navigator.push(context, appRoute(PlatformSettingsScreen(admin: widget.admin)));
      case 'Profile':
        Navigator.push(context, appRoute(PlatformProfileScreen(admin: widget.admin)));
    }
  }

  static const _navItems = [
    (Icons.grid_view_rounded,       'Dashboard'),
    (Icons.local_hospital_rounded,  'Hospitals'),
    (Icons.receipt_long_rounded,    'Billing'),
    (Icons.menu_rounded,            'More'),
  ];

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
        drawer: _buildDrawer(),
        body: Stack(
          children: List.generate(_pageCount, (i) {
            if (!_visitedPages.contains(i)) return const SizedBox.shrink();
            final page = _builtPages.putIfAbsent(i, () => _buildPage(i));
            return AnimatedBuilder(
              animation: _pageCtrl[i],
              child: page,
              builder: (_, child) {
                final curved = Curves.easeOutCubic.transform(_pageCtrl[i].value);
                return Opacity(
                  opacity: curved.clamp(0.0, 1.0),
                  child: Transform.scale(
                    scale: 0.96 + 0.04 * curved,
                    alignment: Alignment.topCenter,
                    child: IgnorePointer(ignoring: i != _currentIndex, child: child),
                  ),
                );
              },
            );
          }),
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
                    boxShadow: [BoxShadow(color: AppColors.black10, blurRadius: 24, offset: const Offset(0, 4))],
                  ),
                  child: Row(
                    children: List.generate(_navItems.length, (i) {
                      final item   = _navItems[i];
                      final active = i < _pageCount && _currentIndex == i;
                      return _NavItem(
                        icon:   item.$1,
                        label:  item.$2,
                        active: active,
                        onTap: () {
                          if (i == 3) {
                            _scaffoldKey.currentState?.openDrawer();
                          } else {
                            _goTo(i);
                          }
                        },
                      );
                    }),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildDrawer() {
    final admin = widget.admin;

    return Drawer(
      backgroundColor: AppColors.drawerBackground,
      child: Column(
        children: [
          // Header
          Container(
            width: double.infinity,
            padding: EdgeInsets.only(top: MediaQuery.of(context).padding.top + 20, bottom: 24, left: 20, right: 20),
            decoration: BoxDecoration(color: AppColors.primary),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 52, height: 52,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.15),
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white.withValues(alpha: 0.3), width: 1.5),
                  ),
                  child: Center(
                    child: Text(admin.initials, style: GoogleFonts.poppins(fontSize: 18, fontWeight: FontWeight.w800, color: Colors.white)),
                  ),
                ),
                const SizedBox(height: 10),
                Text('SUPER ADMIN', style: GoogleFonts.poppins(fontSize: 14, fontWeight: FontWeight.w900, color: Colors.white)),
                const SizedBox(height: 2),
                Text('PLATFORM CONSOLE', style: GoogleFonts.poppins(fontSize: 9, fontWeight: FontWeight.w700, color: Colors.white.withValues(alpha: 0.55), letterSpacing: 2.0)),
              ],
            ),
          ),

          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Cluster: Oversight
                  _drawerSectionLabel('OVERSIGHT'),
                  _drawerItem(Icons.history_rounded,   'Audit Logs'),
                  _drawerItem(Icons.campaign_rounded,  'Notifications'),
                  const SizedBox(height: 8),

                  // Cluster: Masters
                  _drawerSectionLabel('PLATFORM MASTERS'),
                  _drawerItem(Icons.workspace_premium_rounded, 'Plans'),
                  _drawerItem(Icons.public_rounded,            'Location Master'),
                  _drawerItem(Icons.medication_rounded,        'Medicine Master'),
                  const SizedBox(height: 8),

                  // Cluster: System
                  _drawerSectionLabel('SYSTEM'),
                  _drawerItem(Icons.settings_rounded, 'Settings'),
                ],
              ),
            ),
          ),

          // Footer — profile + logout
          const Divider(height: 1),
          Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              children: [
                // Profile tile
                PressScaleWrapper(
                  onTap: () => _onDrawerNavigate('Profile'),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(AppRadius.lg),
                      boxShadow: [BoxShadow(color: AppColors.black10, blurRadius: 8, offset: const Offset(0, 2))],
                    ),
                    child: Row(
                      children: [
                        Container(
                          width: 36, height: 36,
                          decoration: BoxDecoration(color: AppColors.primaryA12, shape: BoxShape.circle),
                          child: Center(
                            child: Text(admin.initials, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: AppColors.primary)),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(admin.name, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.textPrimary), overflow: TextOverflow.ellipsis),
                              Text(admin.role.replaceAll('_', ' ').toUpperCase(), style: TextStyle(fontSize: 9, fontWeight: FontWeight.w700, color: AppColors.secondary, letterSpacing: 0.5)),
                            ],
                          ),
                        ),
                        Icon(Icons.chevron_right_rounded, color: AppColors.textDisabled, size: 20),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 8),

                // Logout
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    onPressed: _logout,
                    icon: const Icon(Icons.logout_rounded, size: 17),
                    label: Text('LOGOUT ACCOUNT', style: GoogleFonts.poppins(fontSize: 12, fontWeight: FontWeight.w800, letterSpacing: 0.5)),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppColors.red,
                      side: BorderSide(color: AppColors.red.withValues(alpha: 0.35)),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                  ),
                ),
              ],
            ),
          ),
          SizedBox(height: MediaQuery.of(context).padding.bottom),
        ],
      ),
    );
  }

  Widget _drawerSectionLabel(String label) {
    return Padding(
      padding: const EdgeInsets.only(left: 8, bottom: 6, top: 4),
      child: Text(
        label,
        style: TextStyle(fontSize: 9, fontWeight: FontWeight.w800, color: AppColors.textDisabled, letterSpacing: 1.2),
      ),
    );
  }

  Widget _drawerItem(IconData icon, String label) {
    return PressScaleWrapper(
      onTap: () => _onDrawerNavigate(label),
      child: Container(
        margin: const EdgeInsets.only(bottom: 2),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(AppRadius.lg),
        ),
        child: Row(
          children: [
            Icon(icon, color: AppColors.primary, size: 20),
            const SizedBox(width: 12),
            Text(label, style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.textPrimary)),
          ],
        ),
      ),
    );
  }
}

// ── Bottom nav item (same pattern as home_screen.dart) ────────────────────────
class _NavItem extends StatefulWidget {
  final IconData icon;
  final String label;
  final bool active;
  final VoidCallback onTap;

  const _NavItem({required this.icon, required this.label, required this.active, required this.onTap});

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
        onTapDown:  (_) => setState(() => _pressed = true),
        onTapUp:    (_) { setState(() => _pressed = false); widget.onTap(); },
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
                child: Icon(widget.icon, size: 22, color: widget.active ? AppColors.primary : AppColors.textDisabled),
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
