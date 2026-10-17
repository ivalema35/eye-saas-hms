import '../utils/date_format.dart';
import 'dart:async';
import 'package:flutter/material.dart';
import '../widgets/app_refresh_indicator.dart';
import '../utils/app_route.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_full_screen_error.dart';
import '../widgets/app_section_header.dart';
import '../constants/permissions.dart';
import '../models/auth_models.dart';
import '../models/dashboard_models.dart';
import '../models/patient_models.dart';
import '../services/auth_service.dart';
import '../services/dashboard_service.dart';
import '../services/permission_service.dart';
import '../utils/currency_format.dart';
import '../utils/refreshable.dart';
import '../widgets/app_record_card.dart';
import '../widgets/retry_network_image.dart';
import '../widgets/skeleton.dart';
import 'ot_appointment_list_screen.dart';
import 'opd_bill_screen.dart';
import 'patient_checkin_screen.dart';
import 'patient_form_screen.dart';
import 'patients_screen.dart';
import 'phone_history_screen.dart';
import 'reports_screen.dart';
import 'users_screen.dart';
import 'share_history_screen.dart';
import 'ot_accountant_dashboard_screen.dart';
import 'ot_assistant_dashboard_screen.dart';
import 'ot_discharge_dashboard_screen.dart';
import 'ot_ward_queue_screen.dart';
import '../widgets/desk_home_panels.dart';
import 'primary_exam_screen.dart';
import 'admin_collection_screen.dart';
import 'subscription_billing_screen.dart';
import 'masters_screen.dart';
import 'medicines_screen.dart';
import 'ot_lens_report_screen.dart';
import 'ot_counsellor_dashboard_screen.dart';
import 'ot_money_report_screen.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';

class DashboardScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final VoidCallback? onMenuTap;
  // Switches the bottom-nav shell's active tab (home_screen.dart's own
  // `_goTo`) — used by Quick Actions entries whose target is already a
  // bottom-nav tab (Patient List, Masters), instead of pushing a second,
  // separate instance of that screen. A pushed instance doesn't belong to
  // the tab-owning shell, so its own hamburger button (which expects
  // `onMenuTap` to open THAT shell's drawer) had nothing to call — see
  // APP_BUGS_ROUND5_AUDIT.md Bug #5.
  final void Function(int index)? onNavigateToTab;

  const DashboardScreen({
    super.key,
    required this.user,
    required this.hospital,
    this.onMenuTap,
    this.onNavigateToTab,
  });

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen>
    with SingleTickerProviderStateMixin
    implements Refreshable {
  DashboardData? _data;
  bool _loading = true;
  bool _refreshing = false;
  String? _error;
  Timer? _timer;
  late final AnimationController _countCtrl;
  late final Animation<double> _countAnim;
  // _TodayPatientsWidget manages its own fetch/state independently — it
  // never re-fetches just because this screen rebuilds, so adding a patient
  // from anywhere (this screen's own Quick Actions, the Patients tab, an OT
  // appointment) left it showing stale data until the app was fully closed
  // and reopened (a fresh mount). This key lets every place that already
  // refreshes the dashboard also poke this widget's own reload.
  final _todayPatientsKey = GlobalKey<_TodayPatientsWidgetState>();
  // The 5 OT-role desk panels (_buildDeskHomePanel) are mutually exclusive —
  // a login only ever matches one — so one shared key reaches whichever one
  // is actually built, the same way _todayPatientsKey reaches the
  // receptionist widget above. Without this, pull-to-refresh (and every
  // other place that already pokes this dashboard) never reached these
  // panels — they only ever loaded once on first mount and after their own
  // internal actions, so a Counselling/Accountant/Ward/Assistant/Discharge
  // login's queue looked permanently stale no matter how the user tried to
  // refresh it.
  final _deskPanelKey = GlobalKey<State>();
  String _deskTab = 'queue';
  final Map<String, int> _deskCounts = {};
  int? _viewAssistantId;
  // Client report (2026-10-07): web's Primary Queue panel has a
  // `data-pq-search`/`data-pq-name` client-side name filter
  // (index.blade.php) that this panel never had. The whole queue is
  // already loaded in `d.primaryQueue`, so this filters in memory —
  // no extra API call, same as web's own JS filter.
  final _primaryQueueSearchCtrl = TextEditingController();
  final _primaryQueueSearchFocus = FocusNode();
  String _primaryQueueSearch = '';
  bool _primaryQueueSearchOpen = false;

  @override
  void initState() {
    super.initState();
    _countCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    );
    _countAnim = CurvedAnimation(parent: _countCtrl, curve: Curves.easeOut);
    _fetch();
    _timer = Timer.periodic(const Duration(minutes: 1), (_) {
      if (mounted && _data != null) setState(() {});
    });
  }

  @override
  void dispose() {
    _countCtrl.dispose();
    _timer?.cancel();
    _primaryQueueSearchCtrl.dispose();
    _primaryQueueSearchFocus.dispose();
    super.dispose();
  }

  @override
  Future<void> refreshSilently() => _fetch();

  Future<void> _fetch() async {
    // Show cached data instantly while fetching fresh in background
    if (_data == null) {
      final cached = await DashboardService.instance.getCached();
      if (cached != null && mounted) {
        setState(() {
          _data = cached;
          _loading = false;
        });
        _countCtrl.forward(from: 0);
      } else if (mounted) {
        setState(() {
          _loading = true;
          _error = null;
        });
      }
    }
    if (mounted)
      setState(() {
        _refreshing = true;
        _error = null;
      });
    try {
      final fresh = await DashboardService.instance.fetchDashboard();
      if (!mounted) return;
      // Only mounted for a receptionist (see _buildBody) — a no-op
      // currentState for every other role.
      _todayPatientsKey.currentState?.refresh();
      (_deskPanelKey.currentState as Refreshable?)?.refreshSilently();
      // Always adopt the freshly-fetched data — a prior version only did
      // this when _isDifferent() flagged a change across ~16 hand-picked
      // counters, so any real change outside that subset (revenue-year,
      // doctor-card details, queue contents/order, OT total/completed
      // counts, etc.) made pull-to-refresh spin and silently keep showing
      // stale data. Only skip the count-up animation when nothing in that
      // subset changed, so the common case still looks calm.
      final changed = _data == null || _isDifferent(_data!, fresh);
      setState(() {
        _data = fresh;
        _loading = false;
        _refreshing = false;
      });
      if (changed) _countCtrl.forward(from: 0);
    } catch (e) {
      if (!mounted) return;
      if (_data == null) {
        setState(() {
          _error = e.toString();
          _loading = false;
          _refreshing = false;
        });
      } else {
        setState(() {
          _refreshing = false;
        });
      }
    }
  }

  bool _isDifferent(DashboardData a, DashboardData b) =>
      a.todayPatients != b.todayPatients ||
      a.pendingExams != b.pendingExams ||
      a.primaryQueueCount != b.primaryQueueCount ||
      a.secondaryQueueCount != b.secondaryQueueCount ||
      a.revenueToday != b.revenueToday ||
      a.revenueMonth != b.revenueMonth ||
      a.primaryQueue.length != b.primaryQueue.length ||
      a.receptionists.length != b.receptionists.length ||
      a.doctorCards.length != b.doctorCards.length ||
      a.pendingShareRequestsCount != b.pendingShareRequestsCount ||
      a.accountantPendingCount != b.accountantPendingCount ||
      a.wardPendingCount != b.wardPendingCount ||
      a.otAssistantPendingCount != b.otAssistantPendingCount ||
      a.dischargePendingCount != b.dischargePendingCount ||
      a.counsellingPendingCount != b.counsellingPendingCount ||
      a.counsellingPaymentCount != b.counsellingPaymentCount;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: _buildAppBar(),
      body: _loading && _data == null
          ? _buildSkeleton()
          : _error != null && _data == null
          ? _buildError()
          : Stack(
              children: [
                AppRefreshIndicator(
                  color: AppColors.primary,
                  onRefresh: _fetch,
                  child: _buildBody(),
                ),
                if (_refreshing)
                  LinearProgressIndicator(
                    minHeight: 2,
                    backgroundColor: Colors.transparent,
                    color: AppColors.primary,
                  ),
              ],
            ),
    );
  }

  // ── AppBar ────────────────────────────────────────────────────────────────

  /// Same brand-mark logic as app_drawer.dart's _buildBrandMark() — kept
  /// deliberately separate (not shared) since this is a temporary
  /// diagnostic placement, not yet a confirmed permanent design decision.
  /// See LOGO_403_INVESTIGATION.md.
  Widget _buildHospitalAvatar(String initials) {
    final live = AuthService.instance.cachedHospital;
    final logoUrl = live?.logoUrl ?? widget.hospital.logoUrl;
    final logoNobgUrl = live?.logoNobgUrl ?? widget.hospital.logoNobgUrl;
    final logoSidebarStyle =
        live?.logoSidebarStyle ?? widget.hospital.logoSidebarStyle;

    if (logoUrl.isEmpty) {
      return CircleAvatar(
        radius: 18,
        backgroundColor: Colors.white.withValues(alpha: 0.20),
        child: Text(
          initials,
          style: const TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w800,
            color: Colors.white,
          ),
        ),
      );
    }

    final useNobg = logoSidebarStyle == 'white' && logoNobgUrl.isNotEmpty;
    final logoSrc = useNobg ? logoNobgUrl : logoUrl;
    // Testing a senior dev's hypothesis: maybe /storage/* isn't fully
    // public and expects the same Bearer token as API calls. See
    // LOGO_403_INVESTIGATION.md.
    final token = AuthService.instance.cachedToken;
    final image = RetryNetworkImage(
      url: logoSrc,
      fit: BoxFit.contain,
      headers: token != null ? {'Authorization': 'Bearer $token'} : null,
      fallbackBuilder: (_) => Text(
        initials,
        style: const TextStyle(
          fontSize: 13,
          fontWeight: FontWeight.w800,
          color: Colors.white,
        ),
      ),
    );

    return CircleAvatar(
      radius: 18,
      backgroundColor: logoSidebarStyle == 'original_blur'
          ? Colors.white.withValues(alpha: 0.22)
          : Colors.transparent,
      child: Padding(
        padding: const EdgeInsets.all(4),
        child: useNobg
            ? ColorFiltered(
                colorFilter: const ColorFilter.mode(
                  Colors.white,
                  BlendMode.srcATop,
                ),
                child: image,
              )
            : image,
      ),
    );
  }

  AppBar _buildAppBar() {
    final name = widget.user.name.trim();
    final initials = name.isNotEmpty
        ? name
              .split(RegExp(r'\s+'))
              .map((w) => w[0])
              .take(2)
              .join()
              .toUpperCase()
        : '?';
    return AppBar(
      backgroundColor: AppColors.primary,
      elevation: 0,
      leading: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: widget.onMenuTap,
        child: const Padding(
          padding: EdgeInsets.only(left: 8),
          child: Icon(Icons.menu_rounded, color: Colors.white),
        ),
      ),
      title: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            widget.hospital.name,
            style: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w800,
              color: Colors.white,
              letterSpacing: -0.3,
            ),
          ),
          Text(
            widget.user.role?.name ?? 'Dashboard',
            style: TextStyle(
              fontSize: 11,
              color: Colors.white.withValues(alpha: 0.65),
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
      actions: [
        Padding(
          padding: const EdgeInsets.only(right: 16),
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              _buildHospitalAvatar(initials),
              if ((_data?.pendingShareRequestsCount ?? 0) > 0)
                Positioned(
                  top: -2,
                  right: -2,
                  child: Container(
                    padding: const EdgeInsets.all(4),
                    decoration: const BoxDecoration(
                      color: Color(0xFFE53E3E),
                      shape: BoxShape.circle,
                    ),
                    child: Text(
                      '${_data!.pendingShareRequestsCount}',
                      style: const TextStyle(
                        fontSize: 9,
                        fontWeight: FontWeight.w800,
                        color: Colors.white,
                        height: 1,
                      ),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ],
    );
  }

  // ── Skeleton (first-ever load, no cache) ─────────────────────────────────

  Widget _buildSkeleton() {
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 110),
      physics: const NeverScrollableScrollPhysics(),
      children: [
        _skeletonBlock(height: 16, width: 130, radius: 6),
        const SizedBox(height: 10),
        _skeletonScrollRow(),
        const SizedBox(height: 10),
        _skeletonScrollRow(),
        const SizedBox(height: 20),
        _skeletonBlock(height: 220, radius: 16),
        const SizedBox(height: 16),
        _skeletonBlock(height: 160, radius: 18),
        const SizedBox(height: 16),
        _skeletonBlock(height: 120, radius: 16),
      ],
    );
  }

  Widget _skeletonScrollRow() {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      physics: const NeverScrollableScrollPhysics(),
      child: Row(
        children: List.generate(
          4,
          (i) => Padding(
            padding: EdgeInsets.only(right: i < 3 ? 10.0 : 0),
            child: _skeletonBlock(width: 152, height: 100),
          ),
        ),
      ),
    );
  }

  Widget _skeletonBlock({
    double? width,
    required double height,
    double radius = 16,
  }) {
    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(
        color: const Color(0xFFE2E8F0),
        borderRadius: BorderRadius.circular(radius),
      ),
    );
  }

  // ── Error state ───────────────────────────────────────────────────────────

  Widget _buildError() {
    // The dashboard is the app's home screen — a failed load here is one of
    // the few genuinely severe, whole-screen moments (see
    // AppFullScreenError's own doc comment), unlike an ordinary in-page
    // section load failure elsewhere, which still uses the small inline
    // AppErrorState.
    return AppFullScreenError(
      message: _error ?? 'Could not load dashboard',
      onRetry: _fetch,
    );
  }

  // ── Main body ─────────────────────────────────────────────────────────────

  // ── Reception quick-actions (Walk In / Phone / OT Appointment) ──────────
  // Web (hospital/layouts/app.blade.php): 3 plain nav links shown only for a
  // receptionist on the dashboard route, each individually permission-gated
  // — `patient_register` / `patient_register_phone` / `ot_appointment_create`
  // — matched here exactly. Pushes the same add-forms already used elsewhere
  // in this app (PatientFormScreen / OtAppointmentFormScreen).

  Widget _buildReceptionQuickActions(DashboardData d) {
    if (!d.isReceptionist) return const SizedBox.shrink();
    final p = PermissionService.instance;
    final canWalkIn = p.can(Perm.opdPatientRegister);
    final canPhone = p.can(Perm.opdPatientRegisterPhone);
    final canOt = p.can(Perm.otAppointmentCreate);
    if (!canWalkIn && !canPhone && !canOt) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Row(
        children: [
          if (canWalkIn) ...[
            Expanded(
              child: _quickActionBtn(
                icon: Icons.person_add_alt_1_rounded,
                label: 'Walk In',
                onTap: _openWalkInQuick,
              ),
            ),
            const SizedBox(width: 10),
          ],
          if (canPhone) ...[
            Expanded(
              child: _quickActionBtn(
                icon: Icons.phone_in_talk_rounded,
                label: 'Phone',
                onTap: _openPhoneQuick,
              ),
            ),
            const SizedBox(width: 10),
          ],
          if (canOt)
            Expanded(
              child: _quickActionBtn(
                icon: Icons.event_note_rounded,
                label: 'OT Appointment',
                onTap: _openOtAppointmentQuick,
              ),
            ),
        ],
      ),
    );
  }

  Widget _quickActionBtn({
    required IconData icon,
    required String label,
    required VoidCallback onTap,
  }) {
    return PressScaleWrapper(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: AppColors.primary,
          borderRadius: BorderRadius.circular(AppRadius.full),
          boxShadow: [
            BoxShadow(
              color: AppColors.primary.withValues(alpha: 0.3),
              blurRadius: 8,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 16, color: Colors.white),
            const SizedBox(width: 6),
            Flexible(
              child: Text(
                label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 12.5,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _openWalkInQuick() async {
    final result = await Navigator.of(context).push<bool>(
      appRoute(
        PatientFormScreen(
          mode: PatientFormMode.addWalkIn,
          user: widget.user,
          hospital: widget.hospital,
        ),
      ),
    );
    if (result == true && mounted) _fetch();
  }

  Future<void> _openPhoneQuick() async {
    final result = await Navigator.of(context).push<bool>(
      appRoute(
        PatientFormScreen(
          mode: PatientFormMode.addPhone,
          user: widget.user,
          hospital: widget.hospital,
        ),
      ),
    );
    if (result == true && mounted) _fetch();
  }

  Future<void> _openOtAppointmentQuick() async {
    await Navigator.of(
      context,
    ).push(appRoute(OtAppointmentFormScreen(item: null, onSaved: _fetch)));
  }

  Widget _buildNoPermissionsBanner() {
    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFFFEF3C7),
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(
          color: const Color(0xFFF59E0B).withValues(alpha: 0.40),
        ),
      ),
      child: const Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.lock_outline_rounded, color: Color(0xFFD97706), size: 22),
          SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'No Permissions Configured',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF92400E),
                  ),
                ),
                SizedBox(height: 3),
                Text(
                  'Your role has no feature permissions assigned. Please contact your administrator.',
                  style: TextStyle(
                    fontSize: 11,
                    color: Color(0xFF92400E),
                    height: 1.4,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ── Greeting hero card ────────────────────────────────────────────────────

  Widget _buildGreetingCard(DashboardData d) {
    final hour = toHospitalTime(DateTime.now()).hour;
    final greeting = hour < 12
        ? 'Good Morning'
        : hour < 17
        ? 'Good Afternoon'
        : 'Good Evening';
    final firstName = widget.user.name.trim().split(RegExp(r'\s+')).first;
    const months = [
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'May',
      'Jun',
      'Jul',
      'Aug',
      'Sep',
      'Oct',
      'Nov',
      'Dec',
    ];
    const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    final now = toHospitalTime(DateTime.now());
    final dateStr =
        '${days[now.weekday - 1]}, ${now.day} ${months[now.month - 1]}';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.fromLTRB(14, 12, 12, 12),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [AppColors.primary, AppColors.primaryLight],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(AppRadius.lg),
        boxShadow: [
          BoxShadow(
            color: AppColors.primary.withValues(alpha: 0.28),
            blurRadius: 14,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text.rich(
                  TextSpan(
                    children: [
                      TextSpan(
                        text: '$greeting, ',
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.white.withValues(alpha: 0.75),
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      TextSpan(
                        text: firstName,
                        style: const TextStyle(
                          fontSize: 16,
                          color: Colors.white,
                          fontWeight: FontWeight.w800,
                          letterSpacing: -0.3,
                        ),
                      ),
                    ],
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 3),
                Text(
                  '$dateStr  ·  ${d.isDoctor ? d.myTodayPatients : d.todayPatients} patients today',
                  style: TextStyle(
                    fontSize: 11,
                    color: Colors.white.withValues(alpha: 0.80),
                    fontWeight: FontWeight.w500,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(AppRadius.md),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                AnimatedBuilder(
                  animation: _countAnim,
                  builder: (_, _) => Text(
                    '${((d.isDoctor ? d.myPrimaryPending : d.primaryQueueCount) * _countAnim.value).round()}',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w900,
                      color: Colors.white,
                      height: 1,
                    ),
                  ),
                ),
                const SizedBox(width: 5),
                Text(
                  'in queue',
                  style: TextStyle(
                    fontSize: 10,
                    color: Colors.white.withValues(alpha: 0.80),
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBody() {
    final d = _data!;
    final p = PermissionService.instance;
    final hasNoPermissions =
        !p.isSuper &&
        !p.canModule('opd') &&
        !p.canModule('ot') &&
        !p.canModule('master') &&
        !p.canModule('setting') &&
        !p.canModule('report');
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 110),
      children: [
        // App-only addition (no web equivalent) — restricted to Hospital
        // Admin per explicit request; other roles go straight to their
        // Overview cards without it.
        if (p.isSuper) _buildGreetingCard(d),
        _buildReceptionQuickActions(d),
        if (hasNoPermissions) _buildNoPermissionsBanner(),
        if (d.subscriptionDaysLeft != null && d.subscriptionDaysLeft! <= 14)
          _buildSubscriptionBanner(d.subscriptionDaysLeft!),
        const AppSectionHeader(title: "Today's Overview"),
        if (p.isSuper) ...[
          _buildHospitalAdminCards(d),
        ] else if (!d.isReceptionist) ...[
          // Web parity: the whole generic bento-grid is suppressed for
          // receptionist (`index.blade.php`'s `@if(!$isReceptionistUser)`,
          // ~line 1215) — receptionist gets its own 6-card row below
          // instead. Without this guard, `_buildStatsRow1`'s
          // `_otSlotCards` falls through to its generic OT-appointment
          // fallback (receptionists have `dashboard_ot` by default) and
          // duplicates the OT Appointment card already in that row.
          _buildStatsRow1(d),
          _buildDeskHomePanel(d),
          const SizedBox(height: 10),
          _buildStatsRow2(d),
        ],
        if (p.can(Perm.dashboardReception) &&
            d.isReceptionist &&
            d.receptionistStats != null) ...[
          const SizedBox(height: 10),
          _buildReceptionistStatsRow(d, d.receptionistStats!),
        ],
        // Web's actual order (index.blade.php): My Primary Queue, then
        // Revenue Overview + staff table, then "Today Added Patients" last
        // (a separate, full-width row below both) — was previously stacked
        // BEFORE Primary Queue here, which doesn't match web's order.
        // Primary Queue — web/API: dashboard_clinical (doctors also allowed).
        // Exclude `isOtDoctor` (the ot_assistant role) — see the matching note
        // on `_buildStatsRow1`'s `canSeeClinical`; without it, OT Assistant
        // gets this card with no permission, unlike web.
        if (p.can(Perm.dashboardClinical) || (d.isDoctor && !d.isOtDoctor)) ...[
          const SizedBox(height: 20),
          RepaintBoundary(child: _buildPrimaryQueue(d)),
        ],
        // Revenue — web/API: dashboard_revenue
        if (p.can(Perm.dashboardRevenue)) ...[
          const SizedBox(height: 16),
          _buildRevenueCard(d),
        ],
        if (p.can(Perm.dashboardStaff) && d.receptionists.isNotEmpty) ...[
          const SizedBox(height: 16),
          RepaintBoundary(child: _buildReceptionTable(d)),
        ],
        if (p.can(Perm.dashboardReception) && d.isReceptionist) ...[
          const SizedBox(height: 20),
          _TodayPatientsWidget(
            key: _todayPatientsKey,
            user: widget.user,
            hospital: widget.hospital,
          ),
        ],
      ],
    );
  }

  // ── Subscription banner ───────────────────────────────────────────────────

  // Web pull parity (2026-09-29) — the "Renew →" text was a dead label with
  // no `onTap` at all; web's own version of this banner links into
  // `hospital.subscription.index` (View Plan History + download invoices —
  // see WEB_VIEW_BUTTONS_PARITY_AUDIT.md finding #1). That page is
  // admin-only on web (`SubscriptionController::ensureHospitalAdmin()`), so
  // only super-role users get a working tap target here; other roles still
  // see the same warning text, just not tappable, rather than tapping into
  // a 403.
  Widget _buildSubscriptionBanner(int days) {
    final isSuper = PermissionService.instance.isSuper;
    return GestureDetector(
      onTap: isSuper
          ? () => Navigator.of(
              context,
            ).push(appRoute(const SubscriptionBillingScreen()))
          : null,
      child: Container(
        margin: const EdgeInsets.only(bottom: 14),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        decoration: BoxDecoration(
          color: const Color(0xFFFEF3C7),
          borderRadius: BorderRadius.circular(AppRadius.md),
          border: Border.all(
            color: const Color(0xFFF59E0B).withValues(alpha: 0.40),
          ),
        ),
        child: Row(
          children: [
            const Icon(
              Icons.warning_amber_rounded,
              color: Color(0xFFD97706),
              size: 20,
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                'Subscription expires in $days day${days == 1 ? '' : 's'}',
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFF92400E),
                ),
              ),
            ),
            if (isSuper)
              const Text(
                'Renew →',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFFD97706),
                ),
              ),
          ],
        ),
      ),
    );
  }

  // ── Stat card rows ────────────────────────────────────────────────────────

  /// Client request (2026-09-28): "Today's Overview" cards should sit in one
  /// single row, scrollable left-to-right, instead of wrapping onto a second
  /// row — matches the tablet app's own `_cardStrip` treatment. Previously a
  /// `Wrap` that computed 2-4 equal-width columns and let cards wrap onto a
  /// second row when they didn't all fit; now every card keeps a fixed
  /// natural width and the row scrolls horizontally instead of wrapping.
  /// `IntrinsicHeight` (not a hardcoded height) keeps this safe for the
  /// occasional taller card (e.g. one with a badge line) sitting next to a
  /// shorter one — all cards in the row match the tallest one's height
  /// instead of the row clipping a taller card at some tablet-specific pixel
  /// value.
  Widget _cardGrid(List<Widget> cards) {
    const spacing = 8.0;
    const cardWidth = 152.0;
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: IntrinsicHeight(
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            for (var i = 0; i < cards.length; i++) ...[
              if (i > 0) const SizedBox(width: spacing),
              SizedBox(width: cardWidth, child: cards[i]),
            ],
          ],
        ),
      ),
    );
  }

  /// Hospital Admin's own 8-card set — web gives the tenant's Hospital
  /// Admin (`is_super`) a completely different, simpler dashboard than
  /// every other role (`index.blade.php:1610-1725`, comment: "Hospital
  /// admin: only these 8 cards"). Mutually exclusive with
  /// `_buildStatsRow1`/`_buildStatsRow2` — see DASHBOARD_PARITY_FIX_PLAN.md
  /// Phase 7.
  // Web pull 2026-09-29 (WEB_ADMIN_RECEPTIONIST_DASHBOARD_AUDIT.md finding
  // #1) — matches web's exact 9-card order (`index.blade.php:1651-1799`):
  // Total Today Patients → Total Collection → Report → Primary/Second → OT
  // Appointment → Incoming Requests → Staff (ALL roles, one card) → Masters
  // → Medicines. Was previously Doctor+Reception as two separate cards (no
  // web equivalent — web shows both counts as a sub-line on one "Staff"
  // card) with no Masters/Medicines cards at all, and "OT Total" sourced
  // from the wrong model (see the `ot_total_today` backend fix).
  Widget _buildHospitalAdminCards(DashboardData d) {
    return _cardGrid([
      // Web/API: plain div, not a link.
      _statCard(
        label: 'Total Today Patients',
        value: '${d.todayPatients}',
        icon: Icons.people_alt_rounded,
        iconBg: AppColors.primary,
        rawCount: d.todayPatients,
      ),
      _statCard(
        label: 'Total Collection',
        value: _fmtRupee(d.revenueToday),
        icon: Icons.account_balance_wallet_rounded,
        iconBg: AppColors.green,
        onTap: () => _push(
          AdminCollectionScreen(user: widget.user, hospital: widget.hospital),
        ),
      ),
      _statCard(
        label: 'Report',
        value: '—',
        icon: Icons.bar_chart_rounded,
        iconBg: AppColors.secondary,
        badge: 'Summary',
        onTap: () =>
            _push(ReportsScreen(user: widget.user, hospital: widget.hospital)),
      ),
      // Web/API: plain div, not a link.
      _statCard(
        label: 'Primary / Second',
        value: '${d.todayPrimary}/${d.todaySecondary}',
        icon: Icons.remove_red_eye_rounded,
        iconBg: AppColors.teal,
      ),
      _statCard(
        label: 'OT Appointment',
        value: '${d.otTotalToday}',
        icon: Icons.calendar_month_rounded,
        iconBg: const Color(0xFFC2185B),
        badge: 'Today',
        rawCount: d.otTotalToday,
        onTap: () => _push(const OtAppointmentListScreen()),
      ),
      if (d.pendingShareRequestsCount != null)
        _statCard(
          label: 'Incoming Requests',
          value: '${d.pendingShareRequestsCount}',
          icon: Icons.send_rounded,
          iconBg: AppColors.secondary,
          rawCount: d.pendingShareRequestsCount,
          onTap: () => _push(
            ShareHistoryScreen(
              user: widget.user,
              hospital: widget.hospital,
              initialTab: 2,
            ),
          ),
        ),
      _statCard(
        label: 'Staff',
        value: '${d.totalUsersAllRoles}',
        icon: Icons.person_add_alt_1_rounded,
        iconBg: AppColors.blue,
        badge: 'Dr: ${d.totalDoctors} & Rec: ${d.totalReceptions}',
        rawCount: d.totalUsersAllRoles,
        onTap: () =>
            _push(UsersScreen(user: widget.user, hospital: widget.hospital)),
      ),
      _statCard(
        label: 'Masters',
        value: '—',
        icon: Icons.tune_rounded,
        iconBg: AppColors.orange,
        badge: 'Case types, OT & more',
        onTap: () =>
            _push(MastersScreen(user: widget.user, hospital: widget.hospital)),
      ),
      _statCard(
        label: 'Medicines',
        value: '${d.totalMedicines}',
        icon: Icons.medication_rounded,
        iconBg: AppColors.purple,
        badge: 'Medicine master list',
        rawCount: d.totalMedicines,
        onTap: () => _push(
          MedicinesScreen(user: widget.user, hospital: widget.hospital),
        ),
      ),
      // Web pull 2026-10-14 — admin's new "Lens Report" quick-link card.
      _statCard(
        label: 'Lens Report',
        value: '${d.totalLensToday}',
        icon: Icons.remove_red_eye_rounded,
        iconBg: AppColors.blue,
        badge: 'Lenses used today',
        rawCount: d.totalLensToday,
        onTap: () => _push(const OtLensReportScreen()),
      ),
    ]);
  }

  Widget _buildStatsRow1(DashboardData d) {
    final p = PermissionService.instance;
    // Web/API: dashboard_clinical (doctors also allowed server-side). `isDoctor`
    // is also `true` for the `ot_assistant` role (API's doctor-card-visibility
    // flag, unrelated to this gate — see Api\DashboardController.php's
    // `$isDoctorRoleForClinicalGate` vs `$isDoctor` distinction), so the
    // role-based bypass here must explicitly exclude `isOtDoctor` or OT
    // Assistant wrongly inherits the OPD doctor's clinical cards with no
    // permission granted — web's index controller never does this (its own
    // bypass checks the retired 'ot_doctor' slug, which no role has any more).
    final canSeeClinical =
        p.can(Perm.dashboardClinical) || (d.isDoctor && !d.isOtDoctor);
    return _cardGrid([
      if (canSeeClinical) ...[
        _statCard(
          label: d.isOtDoctor
              ? 'My OT Today'
              : d.isDoctor
              ? 'My Patients'
              : "Today's Patients",
          value: '${d.isDoctor ? d.myTodayPatients : d.todayPatients}',
          icon: d.isOtDoctor
              ? Icons.medical_services_rounded
              : Icons.person_2_rounded,
          iconBg: AppColors.purple,
          badge: d.isOtDoctor ? 'OT' : 'Assigned to you today',
          rawCount: d.isDoctor ? d.myTodayPatients : d.todayPatients,
        ),
        if (!d.isOtDoctor) ...[
          _statCard(
            label: 'Pending Exams',
            value: '${d.pendingExams}',
            icon: Icons.pending_actions_rounded,
            iconBg: AppColors.orange,
            badge: 'In queue',
            rawCount: d.pendingExams,
          ),
          _opdQueueCard(
            d.isDoctor ? d.myPrimaryPending : d.primaryQueueCount,
            d.isDoctor ? d.mySecondaryPending : d.secondaryQueueCount,
          ),
        ],
      ],
      ..._otSlotCards(d),
    ]);
  }

  /// Web swaps the generic "OT Appointment" card for a role-specific
  /// pending-count card for Accountant/Ward Management/OT Assistant/
  /// Discharge Counter — mirrors `Hospital\DashboardController.php:386-460`
  /// + `index.blade.php`'s `@if/@elseif` chain exactly (each field is null
  /// unless that role is logged in, so this is a plain first-match swap).
  /// See DASHBOARD_PARITY_FIX_PLAN.md Phase 4.
  void _selectDesk(String tab) => setState(() => _deskTab = tab);

  void _onDeskCounts(Map<String, int> counts) {
    if (!mounted) return;
    setState(() {
      _deskCounts
        ..clear()
        ..addAll(counts);
    });
  }

  int _deskCount(String tab, int? fromApi) => _deskCounts[tab] ?? fromApi ?? 0;

  List<Widget> _otSlotCards(DashboardData d) {
    if (d.accountantPendingCount != null) {
      return [
        _statCard(
          label: 'Pending Patients',
          value: '${_deskCount('queue', d.accountantPendingCount)}',
          icon: Icons.hourglass_bottom_rounded,
          iconBg: AppColors.orange,
          badge: 'OT payment',
          rawCount: _deskCount('queue', d.accountantPendingCount),
          selected: _deskTab == 'queue',
          onTap: () => _selectDesk('queue'),
        ),
        _statCard(
          label: 'Refunds',
          value: '${_deskCount('refunds', d.accountantRefundsCount)}',
          icon: Icons.replay_rounded,
          iconBg: const Color(0xFFC0392B),
          badge: 'Surgery refused',
          rawCount: _deskCount('refunds', d.accountantRefundsCount),
          selected: _deskTab == 'refunds',
          onTap: () => _selectDesk('refunds'),
        ),
        _statCard(
          label: 'Completed',
          value: '${_deskCount('history', d.accountantCompletedCount)}',
          icon: Icons.check_circle_rounded,
          iconBg: AppColors.green,
          badge: 'Verified+',
          rawCount: _deskCount('history', d.accountantCompletedCount),
          selected: _deskTab == 'history',
          onTap: () => _selectDesk('history'),
        ),
        _statCard(
          label: 'OT Report',
          value: '—',
          icon: Icons.payments_outlined,
          iconBg: AppColors.teal,
          badge: 'Money',
          onTap: () => _push(const OtMoneyReportScreen()),
        ),
      ];
    }
    if (d.wardPendingCount != null) {
      return [
        _statCard(
          label: 'Pending Patient',
          value: '${_deskCount('queue', d.wardPendingCount)}',
          icon: Icons.hourglass_bottom_rounded,
          iconBg: AppColors.orange,
          badge: 'Ward entry',
          rawCount: _deskCount('queue', d.wardPendingCount),
          selected: _deskTab == 'queue',
          onTap: () => _selectDesk('queue'),
        ),
        _statCard(
          label: 'Completed',
          value: '${_deskCount('history', d.wardCompletedCount)}',
          icon: Icons.check_circle_rounded,
          iconBg: AppColors.green,
          badge: 'Ready onward',
          rawCount: _deskCount('history', d.wardCompletedCount),
          selected: _deskTab == 'history',
          onTap: () => _selectDesk('history'),
        ),
      ];
    }
    if (d.otAssistantPendingCount != null) {
      final viewingId = _viewAssistantId;
      return [
        _statCard(
          label: 'Pending Patient',
          value:
              '${_deskCount('queue', viewingId == null ? d.otAssistantPendingCount : null)}',
          icon: Icons.hourglass_bottom_rounded,
          iconBg: AppColors.orange,
          badge: 'Ready for OT',
          rawCount: _deskCount(
            'queue',
            viewingId == null ? d.otAssistantPendingCount : null,
          ),
          selected: _deskTab == 'queue',
          onTap: () => _selectDesk('queue'),
        ),
        _statCard(
          label: 'Completed',
          value:
              '${_deskCount('history', viewingId == null ? d.otAssistantCompletedCount : null)}',
          icon: Icons.check_circle_rounded,
          iconBg: AppColors.green,
          badge: 'Surgery done',
          rawCount: _deskCount(
            'history',
            viewingId == null ? d.otAssistantCompletedCount : null,
          ),
          selected: _deskTab == 'history',
          onTap: () => _selectDesk('history'),
        ),
        for (final peer in d.otAssistantPeers)
          _statCard(
            label: peer.name,
            value: '${peer.assignedCount}',
            icon: Icons.person_outline_rounded,
            iconBg: AppColors.purple,
            badge: 'Assigned',
            rawCount: peer.assignedCount,
            selected: viewingId == peer.id,
            onTap: () => setState(() {
              _viewAssistantId = peer.id;
              _deskTab = 'queue';
              _deskCounts.clear();
            }),
          ),
      ];
    }
    if (d.dischargePendingCount != null) {
      return [
        _statCard(
          label: 'Pending Patient',
          value: '${_deskCount('queue', d.dischargePendingCount)}',
          icon: Icons.hourglass_bottom_rounded,
          iconBg: AppColors.orange,
          badge: 'Operated',
          rawCount: _deskCount('queue', d.dischargePendingCount),
          selected: _deskTab == 'queue',
          onTap: () => _selectDesk('queue'),
        ),
        _statCard(
          label: 'Completed',
          value: '${_deskCount('history', d.dischargeCompletedCount)}',
          icon: Icons.check_circle_rounded,
          iconBg: AppColors.green,
          badge: 'Discharged',
          rawCount: _deskCount('history', d.dischargeCompletedCount),
          selected: _deskTab == 'history',
          onTap: () => _selectDesk('history'),
        ),
      ];
    }
    if (d.counsellingPendingCount != null) {
      return [
        _statCard(
          label: 'Pending Patient',
          value: '${_deskCount('queue', d.counsellingPendingCount)}',
          icon: Icons.hourglass_bottom_rounded,
          iconBg: AppColors.orange,
          badge: 'Awaiting counselling',
          rawCount: _deskCount('queue', d.counsellingPendingCount),
          selected: _deskTab == 'queue',
          onTap: () => _selectDesk('queue'),
        ),
        _statCard(
          label: 'Completed',
          value: '${_deskCount('history', d.counsellingCompletedCount)}',
          icon: Icons.check_circle_rounded,
          iconBg: AppColors.green,
          badge: 'Counselled today',
          rawCount: _deskCount('history', d.counsellingCompletedCount),
          selected: _deskTab == 'history',
          onTap: () => _selectDesk('history'),
        ),
        // Was a dark slate (0xFF34495E) — fine as a plain icon tint, but once
        // selected this card's border uses the same color at full opacity
        // and 2px wide (see _statCard), which read as an alarming black/navy
        // box rather than "this is the active tab". Teal reads as "payment
        // confirmed" and matches the Paid badges shown in the list below.
        _statCard(
          label: 'Payment Status',
          value: '${_deskCount('payments', d.counsellingPaymentCount)}',
          icon: Icons.shield_outlined,
          iconBg: AppColors.teal,
          badge: 'Package paid & onward',
          rawCount: _deskCount('payments', d.counsellingPaymentCount),
          selected: _deskTab == 'payments',
          onTap: () => _selectDesk('payments'),
        ),
      ];
    }
    // Web pull 2026-10-07 — new dedicated `ot_appointment` role/desk: two
    // cards (today's pending appointments / completed today) replacing the
    // generic single "OT Appointment" fallback card below for this role.
    if (d.otAppointmentPendingCount != null) {
      return [
        _statCard(
          label: 'OT Appointment',
          value: '${_deskCount('queue', d.otAppointmentPendingCount)}',
          icon: Icons.calendar_today_rounded,
          iconBg: const Color(0xFFC2185B),
          badge: 'Appointments today',
          rawCount: _deskCount('queue', d.otAppointmentPendingCount),
          selected: _deskTab == 'queue',
          onTap: () => _selectDesk('queue'),
        ),
        _statCard(
          label: 'Completed',
          value: '${_deskCount('history', d.otAppointmentCompletedCount)}',
          icon: Icons.check_circle_rounded,
          iconBg: AppColors.green,
          badge: 'Checked in today',
          rawCount: _deskCount('history', d.otAppointmentCompletedCount),
          selected: _deskTab == 'history',
          onTap: () => _selectDesk('history'),
        ),
      ];
    }
    if (!PermissionService.instance.can(Perm.dashboardOt)) return const [];
    return [
      _statCard(
        label: 'OT Appointment',
        value: '${d.otToday}',
        icon: Icons.medical_services_rounded,
        iconBg: AppColors.teal,
        badge: '${d.otOperated} confirmed',
        rawCount: d.otToday,
        onTap: () => _push(const OtAppointmentListScreen()),
      ),
    ];
  }

  /// Web pull 2026-09-29: below the same 4 roles' stat-card row (Accountant/
  /// Ward Management/OT Assistant/Discharge Counter — see `_otSlotCards`
  /// just above), web now also shows an inline compact desk panel (queue/
  /// history/refunds tabs + a "View"/key action per row) directly under the
  /// stat cards, in addition to them — same DOM order kept here (cards,
  /// then panel). Detected via the exact same role-slug null-checks
  /// `_otSlotCards` already uses. Each panel does its own independent fetch
  /// of the full row data (the stat cards above only ever get counts from a
  /// separate endpoint) — same "own fetch" pattern as `_TodayPatientsWidget`
  /// further down this file.
  Widget _buildDeskHomePanel(DashboardData d) {
    if (d.accountantPendingCount != null) {
      return AccountantDeskPanel(
        key: _deskPanelKey,
        requestedTab: _deskTab,
        onDeskTab: _selectDesk,
        onCounts: _onDeskCounts,
        onOpenFullPage: () => _push(const OtAccountantDashboardScreen()),
      );
    }
    if (d.wardPendingCount != null) {
      return WardDeskPanel(
        key: _deskPanelKey,
        requestedTab: _deskTab,
        onDeskTab: _selectDesk,
        onCounts: _onDeskCounts,
        onOpenFullPage: () => _push(const OtWardQueueScreen()),
      );
    }
    if (d.otAssistantPendingCount != null) {
      final viewing = d.otAssistantPeers
          .where((p) => p.id == _viewAssistantId)
          .firstOrNull;
      return OtAssistantDeskPanel(
        key: _deskPanelKey,
        user: widget.user,
        requestedTab: _deskTab,
        onDeskTab: _selectDesk,
        onCounts: _onDeskCounts,
        assistantId: _viewAssistantId,
        viewingName: viewing?.name,
        onBackToMine: () => setState(() {
          _viewAssistantId = null;
          _deskTab = 'queue';
          _deskCounts.clear();
        }),
        onOpenFullPage: () =>
            _push(OtAssistantDashboardScreen(user: widget.user)),
      );
    }
    if (d.dischargePendingCount != null) {
      return DischargeDeskPanel(
        key: _deskPanelKey,
        requestedTab: _deskTab,
        onDeskTab: _selectDesk,
        onCounts: _onDeskCounts,
        onOpenFullPage: () => _push(const OtDischargeDashboardScreen()),
      );
    }
    if (d.counsellingPendingCount != null) {
      return CounsellingDeskPanel(
        key: _deskPanelKey,
        requestedTab: _deskTab,
        onDeskTab: _selectDesk,
        onCounts: _onDeskCounts,
        onOpenFullPage: () => _push(const OtCounsellorDashboardScreen()),
      );
    }
    if (d.otAppointmentPendingCount != null) {
      return OtAppointmentDeskPanel(
        key: _deskPanelKey,
        requestedTab: _deskTab,
        onDeskTab: _selectDesk,
        onCounts: _onDeskCounts,
        onOpenFullPage: () => _push(const OtAppointmentListScreen()),
      );
    }
    return const SizedBox.shrink();
  }

  Widget _buildStatsRow2(DashboardData d) {
    final p = PermissionService.instance;
    // Web/API: dashboard_reception for walk-in/phone registration counts.
    final canSeeRegistrations = p.can(Perm.dashboardReception);
    return _cardGrid([
      if (!d.isReceptionist && canSeeRegistrations)
        _statCard(
          label: 'Registrations',
          value: '${d.todayRegistrations}',
          icon: Icons.fact_check_rounded,
          iconBg: AppColors.primary,
          badge: 'Walk-in: ${d.todayWalkin} • Phone: ${d.todayPhone}',
          rawCount: d.todayRegistrations,
        ),
      // Web/API: dashboard_revenue (receptionist gets Today Collection
      // in their own 6-card row instead — see index.blade.php's
      // `$hasRevenue && !$isReceptionistUser` guard).
      if (!d.isReceptionist && p.can(Perm.dashboardRevenue))
        _statCard(
          label: 'Today Revenue',
          value: _fmtRupee(d.revenueToday),
          icon: Icons.currency_rupee_rounded,
          iconBg: AppColors.green,
          badge: 'Month: ${_fmtRupee(d.revenueMonth)}',
        ),
    ]);
  }

  Widget _statCard({
    required String label,
    required String value,
    required IconData icon,
    required Color iconBg,
    String? badge,
    int? rawCount,
    // Web parity: some overview cards are links (e.g. Total Collection,
    // Doctor, Reception, OT Total, Incoming Requests) and some are plain
    // stat divs (e.g. Today's Patients, Pending Exams, Revenue) —
    // index.blade.php's <a> vs <div> per card. When this is set, the card
    // becomes tappable and gets a small chevron + faint tint so it's
    // visually distinct from the non-tappable ones, matching that split.
    bool selected = false,
    VoidCallback? onTap,
  }) {
    final clickable = onTap != null;
    // Redesigned 2026-09-21 — the old layout stacked icon-row/value/label as
    // 3 separate lines plus a left accent strip, running ~90-100dp tall per
    // card. Research on 2026 dashboard-UI KPI cards (Setproduct's "Minimal"
    // density variant: icon + value + label flush in one row) points at a
    // compact horizontal layout instead — icon badge on the left, value+
    // label+badge stacked tight on the right, one thin border instead of a
    // shadow+accent-strip combo. Same data, same tap/badge/rawCount
    // behavior — only the box got shorter and narrower.
    return Material(
      color: clickable ? iconBg.withValues(alpha: 0.05) : Colors.white,
      borderRadius: BorderRadius.circular(AppRadius.md),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppRadius.md),
        child: Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(AppRadius.md),
            border: Border.all(
              color: selected ? iconBg : iconBg.withValues(alpha: 0.16),
              width: selected ? 2 : 1,
            ),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Container(
                width: 28,
                height: 28,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: iconBg.withValues(alpha: 0.13),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(icon, size: 14, color: iconBg),
              ),
              const SizedBox(width: 7),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    rawCount != null
                        ? AnimatedBuilder(
                            animation: _countAnim,
                            builder: (_, _) => Text(
                              '${(rawCount * _countAnim.value).round()}',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w900,
                                color: AppColors.primary,
                                height: 1.1,
                              ),
                            ),
                          )
                        : Text(
                            value,
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w900,
                              color: AppColors.primary,
                              height: 1.1,
                            ),
                            overflow: TextOverflow.ellipsis,
                          ),
                    Row(
                      children: [
                        Flexible(
                          child: Text(
                            label,
                            style: const TextStyle(
                              fontSize: 9.5,
                              color: AppColors.textSecondary,
                              fontWeight: FontWeight.w600,
                            ),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        if (clickable) ...[
                          const SizedBox(width: 1),
                          Icon(
                            Icons.chevron_right_rounded,
                            size: 11,
                            color: iconBg,
                          ),
                        ],
                      ],
                    ),
                    // Client report (2026-10-07): long two-part badges (e.g.
                    // "Confirmed: 5 • Booked: 12") were getting clipped by
                    // the card's fixed 152dp width — ellipsis hid the
                    // second half entirely. FittedBox shrinks the whole
                    // line to fit instead, so it's always fully visible
                    // without touching the card's size.
                    if (badge != null)
                      FittedBox(
                        fit: BoxFit.scaleDown,
                        alignment: Alignment.centerLeft,
                        child: Text(
                          badge,
                          maxLines: 1,
                          style: TextStyle(
                            fontSize: 8,
                            fontWeight: FontWeight.w700,
                            color: iconBg,
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  /// Web renders "OPD Queue" as a distinct two-tone split box (Primary in
  /// teal, Secondary in blue) — not the generic icon/value/badge card,
  /// unlike every other stat card (`index.blade.php`'s custom OPD-QUEUE
  /// markup, ~line 1478).
  Widget _opdQueueCard(int primary, int secondary) {
    Widget half(String value, String label, Color bg, Color fg) => Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 6),
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(10),
        ),
        child: Column(
          children: [
            Text(
              value,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.w800,
                color: fg,
              ),
            ),
            Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: 7.5,
                fontWeight: FontWeight.w700,
                color: fg.withValues(alpha: 0.85),
              ),
            ),
          ],
        ),
      ),
    );
    return Container(
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: AppColors.teal.withValues(alpha: 0.16)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 28,
                height: 28,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: AppColors.teal.withValues(alpha: 0.13),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(
                  Icons.remove_red_eye_rounded,
                  size: 14,
                  color: AppColors.teal,
                ),
              ),
              const SizedBox(width: 7),
              const Expanded(
                child: Text(
                  'OPD QUEUE',
                  style: TextStyle(
                    fontSize: 9.5,
                    fontWeight: FontWeight.w700,
                    color: AppColors.textSecondary,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: 7),
          Row(
            children: [
              half(
                '$primary',
                'PRIMARY',
                const Color(0xFFE8F8F5),
                const Color(0xFF1ABC9C),
              ),
              const SizedBox(width: 6),
              half(
                '$secondary',
                'SECONDARY',
                const Color(0xFFEAF4FB),
                AppColors.primary,
              ),
            ],
          ),
        ],
      ),
    );
  }

  // ── Primary Queue ─────────────────────────────────────────────────────────

  // dashboard_models.dart's QueuePatient carries only what this panel's own
  // columns need (no firstName/lastName split) — PrimaryExamScreen's header
  // and save calls only ever read `.fullName`/`.patientCode`/`.id` off the
  // Patient it's given (verified — nothing here touches firstName/lastName
  // downstream), so a best-effort name split is enough to satisfy Patient's
  // required fields without needing a second API round-trip just to open it.
  Patient _patientFromQueue(QueuePatient q) {
    final parts = q.fullName.trim().split(RegExp(r'\s+'));
    return Patient(
      id: q.id,
      patientCode: q.patientCode,
      firstName: parts.isNotEmpty ? parts.first : '',
      lastName: parts.length > 1 ? parts.sublist(1).join(' ') : '',
      fullName: q.fullName,
      age: q.age,
      gender: q.gender,
      doctorPatientNo: q.doctorPatientNo,
      checkedInAt: q.checkedInAt,
    );
  }

  Future<void> _startPrimaryExam(QueuePatient q) async {
    final result = await Navigator.of(context).push<bool>(
      appRoute(
        PrimaryExamScreen(
          user: widget.user,
          hospital: widget.hospital,
          patient: _patientFromQueue(q),
        ),
      ),
    );
    if (result == true && mounted) _fetch();
  }

  Widget _buildPrimaryQueue(DashboardData d) {
    final query = _primaryQueueSearch.trim().toLowerCase();
    // (originalIndex, patient) pairs, not a plain filtered list — keeps each
    // visible row's rank # at its real queue position, matching web's own
    // filter (a pure CSS show/hide of <tr> rows, which never renumbers).
    final visibleQueue = <(int, QueuePatient)>[
      for (var i = 0; i < d.primaryQueue.length; i++)
        if (query.isEmpty || d.primaryQueue[i].fullName.toLowerCase().contains(query))
          (i, d.primaryQueue[i]),
    ];
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 12, 10),
            child: Row(
              children: [
                // Web still says "My Primary Queue" as of this pull, but the
                // user asked to rename ahead of web's own fix — "Primary
                // Queue" (it already shows every doctor's queue for Hospital
                // Admin, not just "my" own, so the old name was misleading
                // there anyway).
                // Client report (2026-10-07): collapsed to an icon by
                // default, same pattern as "Today Added Patients" below —
                // an always-visible field here was reported as glitchy
                // (a blank unresponsive box over the keyboard on open) and
                // search is a rarely-used action on this panel anyway.
                if (!_primaryQueueSearchOpen) ...[
                  Expanded(
                    child: Text(
                      'Primary Queue',
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 3,
                    ),
                    decoration: BoxDecoration(
                      color: AppColors.green.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(AppRadius.xl),
                    ),
                    child: Text(
                      // Web pull: the waiting badge stays the full queue
                      // total even while a search filters the rows below —
                      // matches web, where #pqSearch only ever hides/shows
                      // table rows.
                      '${d.isDoctor ? d.primaryQueue.length : d.primaryQueueCount} waiting',
                      style: const TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        color: AppColors.green,
                      ),
                    ),
                  ),
                  // Web only shows search once there's something to search
                  // (`@if($primaryQueue->count() > 0)`) — matched here too.
                  if (d.primaryQueue.isNotEmpty) ...[
                    const SizedBox(width: 4),
                    IconButton(
                      icon: const Icon(Icons.search_rounded, size: 18),
                      color: AppColors.primary,
                      tooltip: 'Search by name',
                      visualDensity: VisualDensity.compact,
                      onPressed: () {
                        setState(() => _primaryQueueSearchOpen = true);
                        WidgetsBinding.instance.addPostFrameCallback(
                          (_) => _primaryQueueSearchFocus.requestFocus(),
                        );
                      },
                    ),
                  ],
                ] else
                  Expanded(
                    child: TextField(
                      controller: _primaryQueueSearchCtrl,
                      focusNode: _primaryQueueSearchFocus,
                      onChanged: (v) => setState(() => _primaryQueueSearch = v),
                      autofillHints: const [],
                      enableSuggestions: false,
                      autocorrect: false,
                      decoration: InputDecoration(
                        isDense: true,
                        hintText: 'Search patient name...',
                        prefixIcon: const Icon(Icons.search_rounded, size: 18),
                        suffixIcon: IconButton(
                          icon: const Icon(Icons.close_rounded, size: 18),
                          visualDensity: VisualDensity.compact,
                          onPressed: () {
                            _primaryQueueSearchCtrl.clear();
                            setState(() {
                              _primaryQueueSearch = '';
                              _primaryQueueSearchOpen = false;
                            });
                          },
                        ),
                        contentPadding: const EdgeInsets.symmetric(vertical: 8),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(AppRadius.md),
                        ),
                      ),
                    ),
                  ),
                // Web's "My Primary Queue" panel has no "View All"/drill-through
                // link at all (checked directly against index.blade.php) — it's
                // just this same top-20-rows table with an Action column per
                // row (an "Examine" button gated by @haspermission('exam_primary')).
                // An earlier version of this button was unconditionally shown
                // regardless of permission, which 403'd for a receptionist —
                // this time it's gated the same way web gates it (see
                // _queueItem below), not removed outright.
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFFE2E8F0)),
          if (d.primaryQueue.isEmpty)
            const Padding(
              padding: EdgeInsets.all(24),
              child: Center(
                child: Text(
                  'No patients in queue right now',
                  style: TextStyle(
                    fontSize: 13,
                    color: AppColors.textSecondary,
                  ),
                ),
              ),
            )
          else if (visibleQueue.isEmpty)
            const Padding(
              padding: EdgeInsets.all(24),
              child: Center(
                child: Text(
                  'No patient matches your search.',
                  style: TextStyle(
                    fontSize: 13,
                    color: AppColors.textSecondary,
                  ),
                ),
              ),
            )
          else
            // Plain Column, not a shrinkWrap ListView — a shrink-wrapped
            // ListView nested inside this screen's outer scrollable can fail
            // to shrink its reserved layout space when the item count drops
            // between the cached-data render and the fresh-fetch render
            // (e.g. the queue had more patients a moment ago), leaving a
            // leftover blank gap below the real rows. A plain Column has no
            // scroll/sliver geometry to go stale, so it can't do this.
            Column(
              children: [
                for (var vi = 0; vi < visibleQueue.length; vi++) ...[
                  if (vi > 0) Container(height: 8, color: AppColors.surfaceFill),
                  Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 12,
                    ),
                    child: _queueItem(
                      visibleQueue[vi].$2,
                      visibleQueue[vi].$1 + 1,
                      d.waitThresholds,
                    ),
                  ),
                ],
              ],
            ),
        ],
      ),
    );
  }

  // Same AppRecordCard(flat:true) format used everywhere else patient data
  // shows on this dashboard (see _patientRow's doc comment) — one shared
  // look for "a patient row" across the whole app instead of each list
  // inventing its own. Web's own queue table (index.blade.php) shows: #,
  // MRD, Patient, [Doctor + Receptionist — Hospital Admin only], Age/Gender,
  // arrival Time + wait pill, and the Examine action — no doctor
  // patient-number badge, no history icon, so neither is included in the
  // field grid here.
  Widget _queueItem(QueuePatient p, int rank, WaitThresholds t) {
    final wait = p.waitMinutes();
    final waitColor = _waitColor(wait, t);
    final timeSource = p.checkedInAt ?? p.registeredAt;
    final parts = <String>[];
    if (p.age != null) parts.add('${p.age}y');
    if (p.gender != null) parts.add(_genderShort(p.gender!));
    final ageGender = parts.join('/');

    return AppRecordCard(
      flat: true,
      leading: Container(
        width: 20,
        height: 20,
        decoration: BoxDecoration(
          color: AppColors.primary.withValues(alpha: 0.08),
          shape: BoxShape.circle,
        ),
        alignment: Alignment.center,
        child: Text(
          '$rank',
          style: TextStyle(
            fontSize: 10,
            fontWeight: FontWeight.w800,
            color: AppColors.primary,
          ),
        ),
      ),
      title: p.fullName.isNotEmpty ? p.fullName : '—',
      subtitle: ageGender.isEmpty ? null : ageGender,
      trailing: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Web parity (`index.blade.php`'s queue table Time column):
          // `{{ $qArrivedAt->format('h:i A') }}` alongside the wait pill —
          // this app previously showed ONLY the wait pill ("12m"), never the
          // actual arrival clock time at all.
          if (timeSource != null) ...[
            Text(
              _fmtTime(timeSource),
              style: const TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: AppColors.textSecondary,
              ),
            ),
            const SizedBox(width: 6),
          ],
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(
              color: waitColor.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(AppRadius.xl),
            ),
            child: Text(
              _fmtWait(wait),
              style: TextStyle(
                fontSize: 10.5,
                fontWeight: FontWeight.w700,
                color: waitColor,
              ),
            ),
          ),
          // Web's Action column — an "Examine" button gated by
          // @haspermission('exam_primary'). Compact icon-only here (the
          // dashboard tile has no room for a labelled button); the full
          // "Examine" label still lives on the actual queue screen.
          if (PermissionService.instance.can(Perm.opdExamPrimary))
            InkWell(
              borderRadius: BorderRadius.circular(AppRadius.xl),
              onTap: () => _startPrimaryExam(p),
              child: Padding(
                padding: const EdgeInsets.only(left: 6),
                child: Icon(
                  Icons.medical_services_rounded,
                  size: 17,
                  color: AppColors.primary,
                ),
              ),
            ),
        ],
      ),
      fields: [
        AppRecordField(
          'MRD',
          p.patientCode,
          icon: Icons.badge_outlined,
          color: AppColors.primary,
        ),
        // Web parity (`index.blade.php`'s queue table): Doctor + Receptionist
        // columns only show for Hospital Admin (`$isHospitalAdmin`) — a
        // doctor viewing their own queue already knows whose queue it is.
        if (PermissionService.instance.isSuper && p.doctorName != null)
          AppRecordField(
            'Doctor',
            p.doctorPrefix != null && p.doctorPrefix!.isNotEmpty
                ? '${p.doctorPrefix} ${p.doctorName}'
                : p.doctorName!,
            icon: Icons.person_outline_rounded,
            color: RecordFieldColors.doctor,
          ),
        if (PermissionService.instance.isSuper && p.receptionistName != null)
          AppRecordField(
            'Receptionist',
            p.receptionistName!,
            icon: Icons.how_to_reg_outlined,
            color: RecordFieldColors.receptionist,
          ),
      ],
    );
  }

  // ── Revenue card ──────────────────────────────────────────────────────────

  /// Web parity: `index.blade.php`'s `.bento-card` + `.rev-grid` — a plain
  /// white card (not a colored gradient) with a bordered header and three
  /// equal-weight columns side by side (Today / This Month / This Year),
  /// separated by thin vertical dividers, all three the same navy weight —
  /// web gives "Today" no special highlight over the other two.
  Widget _buildRevenueCard(DashboardData d) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.12)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            decoration: BoxDecoration(
              border: Border(
                bottom: BorderSide(
                  color: AppColors.primary.withValues(alpha: 0.10),
                ),
              ),
            ),
            child: Row(
              children: [
                Icon(
                  Icons.show_chart_rounded,
                  color: AppColors.primary,
                  size: 17,
                ),
                const SizedBox(width: 8),
                Text(
                  'Revenue Overview',
                  style: TextStyle(
                    fontSize: 14.5,
                    fontWeight: FontWeight.w900,
                    color: AppColors.primary,
                    letterSpacing: -0.2,
                  ),
                ),
              ],
            ),
          ),
          IntrinsicHeight(
            child: Row(
              children: [
                Expanded(child: _revenueCol('Today', d.revenueToday)),
                VerticalDivider(
                  width: 1,
                  thickness: 1,
                  color: AppColors.primary.withValues(alpha: 0.12),
                ),
                Expanded(child: _revenueCol('This Month', d.revenueMonth)),
                VerticalDivider(
                  width: 1,
                  thickness: 1,
                  color: AppColors.primary.withValues(alpha: 0.12),
                ),
                Expanded(child: _revenueCol('This Year', d.revenueYear)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _revenueCol(String label, double amount) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 18, horizontal: 8),
      child: Column(
        children: [
          Text(
            label.toUpperCase(),
            style: TextStyle(
              fontSize: 9.5,
              fontWeight: FontWeight.w800,
              color: AppColors.primary.withValues(alpha: 0.70),
              letterSpacing: 1,
            ),
          ),
          const SizedBox(height: 7),
          Text(
            _fmtRupee(amount),
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 17,
              fontWeight: FontWeight.w900,
              color: AppColors.primary,
              letterSpacing: -0.4,
            ),
          ),
        ],
      ),
    );
  }

  // ── Reception performance table ───────────────────────────────────────────

  bool get _canReceptionDetail => PermissionService.instance.canAny([
    Perm.opdPatientView,
    Perm.reportsView,
    Perm.opdReportsView,
  ]);

  Widget _buildReceptionTable(DashboardData d) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: EdgeInsets.fromLTRB(16, 14, 16, 10),
            child: Text(
              'Reception — Today',
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w800,
                color: AppColors.primary,
              ),
            ),
          ),
          const Divider(height: 1, color: Color(0xFFE2E8F0)),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
            child: Row(
              children: [
                const Expanded(flex: 3, child: _TH('Receptionist')),
                const Expanded(flex: 1, child: _TH('Walk-ins', right: true)),
                const Expanded(flex: 2, child: _TH('Net', right: true)),
                // Web pull 2026-09-30 — the "Detail" column
                // (`index.blade.php` "Reception — Today" table, gated by
                // `patient_view|report_view|opd_reports_view`) was missing
                // entirely; was Receptionist/Walk-ins/Net only.
                if (_canReceptionDetail)
                  const Expanded(flex: 2, child: _TH('Detail', right: true)),
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFFE2E8F0)),
          // Plain Column, not a shrinkWrap ListView — see the matching
          // comment in _buildPrimaryQueue above for why.
          Column(
            children: [
              for (var i = 0; i < d.receptionists.length; i++) ...[
                if (i > 0)
                  const Divider(
                    height: 1,
                    color: Color(0xFFE2E8F0),
                    indent: 16,
                  ),
                _receptionRow(d.receptionists[i]),
              ],
            ],
          ),
        ],
      ),
    );
  }

  Widget _receptionRow(ReceptionistStat r) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: Row(
        children: [
          Expanded(
            flex: 3,
            child: Row(
              children: [
                CircleAvatar(
                  radius: 14,
                  backgroundColor: AppColors.primary.withValues(alpha: 0.10),
                  child: Text(
                    r.name.isNotEmpty ? r.name[0].toUpperCase() : '?',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w800,
                      color: AppColors.primary,
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    r.name,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: AppColors.primary,
                    ),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            flex: 1,
            child: Text(
              '${r.todayCount}',
              textAlign: TextAlign.right,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w700,
                color: AppColors.primary,
              ),
            ),
          ),
          Expanded(
            flex: 2,
            child: Text(
              _fmtRupee(r.todayNet),
              textAlign: TextAlign.right,
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: AppColors.green,
              ),
            ),
          ),
          if (_canReceptionDetail)
            Expanded(
              flex: 2,
              child: Align(
                alignment: Alignment.centerRight,
                child: TextButton(
                  // Web hardcodes today's date for this specific link
                  // (`index.blade.php`'s Reception table), regardless of
                  // any other date range the admin might be viewing
                  // elsewhere on this dashboard.
                  onPressed: () => _push(
                    AdminCollectionDetailScreen(
                      user: widget.user,
                      hospital: widget.hospital,
                      receptionId: r.id,
                      receptionName: r.name,
                      start: DateTime.now(),
                      end: DateTime.now(),
                    ),
                  ),
                  style: TextButton.styleFrom(
                    padding: const EdgeInsets.symmetric(horizontal: 4),
                    minimumSize: Size.zero,
                    tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        'Details',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: AppColors.primary,
                        ),
                      ),
                      Icon(
                        Icons.chevron_right_rounded,
                        size: 14,
                        color: AppColors.primary,
                      ),
                    ],
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  // ── Quick actions grid ────────────────────────────────────────────────────

  void _push(Widget screen) {
    Navigator.of(context).push(appRoute(screen));
  }

  // Only caller was the FOC quick action, disabled at client's request.
  // void _comingSoon(String label) {
  //   showAppSnackBar(context, '$label — Coming Soon', duration: const Duration(seconds: 2));
  // }

  // ── Receptionist-specific stats row ──────────────────────────────────────

  /// Web's receptionist-only 6-card row (`index.blade.php`'s `.rec-5row`) —
  /// Today Collection, Total Patients, My Patients, Reports, Phone
  /// Appointments, OT Appointment. This previously showed a different,
  /// made-up 4-card set (My Patients Today/Collection/Pending Phone/My
  /// Walk-ins) that didn't match web's actual cards at all.
  Widget _buildReceptionistStatsRow(DashboardData d, ReceptionistStats rs) {
    final p = PermissionService.instance;
    return _cardGrid([
      // Web pull 2026-09-29 — web links this to the receptionist's own
      // collection drill-down (`hospital.dashboard.collection.show` with
      // `reception` = the logged-in user's own id), same screen the
      // admin's own Total Collection card already uses; was previously a
      // plain, non-tappable div here.
      _statCard(
        label: 'Today Collection',
        value: _fmtRupee(rs.todayCollection),
        icon: Icons.account_balance_wallet_rounded,
        iconBg: AppColors.green,
        onTap: () => _push(
          AdminCollectionDetailScreen(
            user: widget.user,
            hospital: widget.hospital,
            receptionId: widget.user.id,
            receptionName: widget.user.name,
            start: DateTime.now(),
            end: DateTime.now(),
          ),
        ),
      ),
      // Web pull 2026-09-29 (WEB_ADMIN_RECEPTIONIST_DASHBOARD_AUDIT.md
      // finding #5) — web shows "My Patients: X" as a meta sub-line on
      // this same card, not as its own separate card.
      _statCard(
        label: 'Total Patients',
        value: '${d.todayRegistrations}',
        icon: Icons.people_alt_rounded,
        iconBg: AppColors.primary,
        badge: 'My Patients: ${rs.myPatientsToday}',
        rawCount: d.todayRegistrations,
        onTap: () =>
            _push(PatientsScreen(user: widget.user, hospital: widget.hospital)),
      ),
      if (p.can(Perm.reportsView))
        _statCard(
          label: 'Reports',
          value: '—',
          icon: Icons.bar_chart_rounded,
          iconBg: AppColors.secondary,
          onTap: () => _push(
            ReportsScreen(user: widget.user, hospital: widget.hospital),
          ),
        ),
      _statCard(
        label: 'Phone Appt',
        value: '${d.todayPhone}',
        icon: Icons.phone_rounded,
        iconBg: AppColors.teal,
        badge: 'My Patient: ${rs.myPhoneToday}',
        rawCount: d.todayPhone,
        onTap: () => _push(
          PhoneHistoryScreen(user: widget.user, hospital: widget.hospital),
        ),
      ),
      if (p.can(Perm.dashboardOt))
        _statCard(
          label: 'OT Appointment',
          value: '${d.otToday}',
          icon: Icons.medical_services_rounded,
          iconBg: const Color(0xFFC2185B),
          badge: 'Confirmed: ${d.otOperated} • Booked: ${d.otPending}',
          rawCount: d.otToday,
          onTap: () => _push(const OtAppointmentListScreen()),
        ),
      // Web pull 2026-09-29 (WEB_ADMIN_RECEPTIONIST_DASHBOARD_AUDIT.md
      // finding #2) — web's 6th receptionist card, gated by
      // `ot_counselling_fill` (API sends null when the viewer lacks it).
      if (rs.counsellingPending != null)
        _statCard(
          label: 'OT Counselling',
          value: '${rs.counsellingPending}',
          icon: Icons.favorite_rounded,
          iconBg: const Color(0xFFD68910),
          badge: 'Pending counselling',
          rawCount: rs.counsellingPending,
          onTap: () => _push(const OtCounsellorDashboardScreen()),
        ),
    ]);
  }

  // ── Helpers ───────────────────────────────────────────────────────────────

  // Web's `money()` helper never compacts (always full, comma-grouped
  // figures — `index.blade.php` calls `money($revenueToday ?? 0, 0)`
  // everywhere on this dashboard) — this used to go through
  // `formatMoneyCompact` ("10k") instead of the exact value ("9,500") web
  // actually shows for Total/Today Collection and every other money figure
  // on this screen.
  String _fmtRupee(double amount) =>
      formatMoney(amount, widget.hospital.currencySymbol);

  String _fmtWait(int minutes) {
    if (minutes < 1) return 'Just in';
    if (minutes < 60) return '${minutes}m';
    final h = minutes ~/ 60;
    final m = minutes % 60;
    return m > 0 ? '${h}h ${m}m' : '${h}h';
  }

  // Web parity: `$qArrivedAt->format('h:i A')` — 12-hour clock in the
  // hospital's own timezone (never the device's), matching `toHospitalTime`
  // everywhere else this app shows a clock time.
  String _fmtTime(DateTime dt) {
    final local = toHospitalTime(dt);
    final h12 = local.hour % 12 == 0 ? 12 : local.hour % 12;
    final period = local.hour >= 12 ? 'PM' : 'AM';
    return '$h12:${local.minute.toString().padLeft(2, '0')} $period';
  }

  Color _waitColor(int minutes, WaitThresholds t) {
    if (minutes < t.rGreen) return AppColors.waitGreen;
    if (minutes < t.rOrange) return AppColors.waitOrange;
    return AppColors.waitRed;
  }

  String _genderShort(String gender) {
    if (gender.toLowerCase() == 'male') return 'M';
    if (gender.toLowerCase() == 'female') return 'F';
    return gender.isNotEmpty ? gender[0].toUpperCase() : '';
  }
}

// ── Receptionist "Today Added Patients" widget ─────────────────────────────
// Mirrors web's receptionist-dashboard "Today Added Patients" table.
// See WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §9 / FIX_PLAN TASK 5.1.
// Web pull 2026-10-07 — the OT-appointments merge/tab was removed here:
// receptionist lost `ot_appointment_view` to the new dedicated
// `ot_appointment` role, which gets its own desk panel instead
// (see OtAppointmentDeskPanel in widgets/desk_home_panels.dart).

class _TodayPatientsWidget extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;

  const _TodayPatientsWidget({
    super.key,
    required this.user,
    required this.hospital,
  });

  @override
  State<_TodayPatientsWidget> createState() => _TodayPatientsWidgetState();
}

enum _TodayTab { walkin, phone, status }

class _TodayPatientsWidgetState extends State<_TodayPatientsWidget> {
  TodayPatientsData? _data;
  bool _loading = true;
  String? _error;
  final _searchCtrl = TextEditingController();
  final _searchFocus = FocusNode();
  Timer? _debounce;
  _TodayTab _activeTab = _TodayTab.walkin;
  bool _searchOpen = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _searchFocus.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  /// Public entry point for the parent dashboard to poke this widget's own
  /// reload — see [_DashboardScreenState._todayPatientsKey]'s doc comment.
  Future<void> refresh() => _load(searchContact: _searchCtrl.text.trim());

  Future<void> _load({String searchContact = ''}) async {
    if (_data == null) setState(() => _loading = true);
    try {
      final data = await DashboardService.instance.fetchTodayPatients(
        searchContact: searchContact,
      );
      if (!mounted) return;
      setState(() {
        _data = data;
        _loading = false;
        _error = null;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = e.toString();
      });
    }
  }

  void _onSearchChanged(String v) {
    _debounce?.cancel();
    _debounce = Timer(
      const Duration(milliseconds: 400),
      () => _load(searchContact: v.trim()),
    );
  }

  Future<void> _openCheckIn(Patient p) async {
    final result = await Navigator.of(context).push<bool>(
      appRoute(
        PatientCheckinScreen(
          user: widget.user,
          hospital: widget.hospital,
          patient: p,
        ),
      ),
    );
    if (result == true && mounted)
      _load(searchContact: _searchCtrl.text.trim());
  }

  void _openPrint(Patient p) {
    Navigator.of(context).push(
      appRoute(
        OpdBillScreen(user: widget.user, hospital: widget.hospital, patient: p),
      ),
    );
  }

  // ── Tab filter (web pull 2026-10-07 — OT pill removed: receptionist lost
  // ot_appointment_view, so this widget no longer merges OT appointments in
  // at all; now just Walk-in/Phone/Status, pure client-side show/hide of
  // this same already-loaded `_data.rows`, no re-fetch) ───────────────────

  // Previously re-derived from `type`/`isOt` alone, which omitted the
  // done/past-primary exclusion web applies to Walk-in (so a patient who'd
  // finished primary+secondary — or entered the OT pipeline — kept showing
  // in Walk-in here, and a phone patient who'd entered the OT pipeline
  // dropped out of Phone, since web's Phone tab is unconditional on
  // status). The server already computes the exact grouping rule
  // (`tap_groups`, mirrors receptionist-today-patients-table.blade.php) —
  // use it directly, same as `_statusPatients` below already does, instead
  // of re-deriving a second, drifted copy of the same rule.
  List<Patient> get _walkinPatients => (_data?.rows ?? const [])
      .where((r) => !r.isOt && r.patient!.tapGroups.contains('walkin'))
      .map((r) => r.patient!)
      .toList();
  List<Patient> get _phonePatients => (_data?.rows ?? const [])
      .where((r) => !r.isOt && r.patient!.tapGroups.contains('phone'))
      .map((r) => r.patient!)
      .toList();
  // Web pull 2026-09-28 — "Patient Status" 4th tab: patients who have moved
  // past the Primary Exam stage, regardless of walk-in/phone origin. Server
  // already computed this exact grouping (`tap_groups`), matching
  // receptionist-today-patients-table.blade.php's split rule.
  List<Patient> get _statusPatients => (_data?.rows ?? const [])
      .where((r) => !r.isOt && r.patient!.tapGroups.contains('status'))
      .map((r) => r.patient!)
      .toList();

  @override
  Widget build(BuildContext context) {
    final rows = _data?.rows ?? const <TodayPatientRow>[];
    final List<Widget> Function() activeBuilder = switch (_activeTab) {
      _TodayTab.walkin => () => _walkinPatients.map(_patientRow).toList(),
      _TodayTab.phone => () => _phonePatients.map(_patientRow).toList(),
      _TodayTab.status => () => _statusPatients.map(_statusPatientRow).toList(),
    };
    final activeItems = activeBuilder();
    final emptyMessage = switch (_activeTab) {
      _TodayTab.walkin => 'No walk-in patients today.',
      _TodayTab.phone => 'No phone appointments today.',
      _TodayTab.status => 'No patients past Primary Exam yet.',
    };
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 12, 10),
            child: Row(
              children: [
                // Collapsed to an icon by default — a full-width "Search by
                // mobile..." bar was always visible even though search is a
                // rarely-used action here, pushing the actual patient rows
                // further down for no benefit most of the time. Tapping the
                // icon swaps it for a real field and focuses the keyboard;
                // tapping it again (or clearing + losing focus) collapses it.
                if (!_searchOpen) ...[
                  Expanded(
                    child: Text(
                      'Today Added Patients',
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 3,
                    ),
                    decoration: BoxDecoration(
                      color: AppColors.green.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(AppRadius.xl),
                    ),
                    child: Text(
                      '${rows.length}',
                      style: const TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        color: AppColors.green,
                      ),
                    ),
                  ),
                  const SizedBox(width: 4),
                  IconButton(
                    icon: const Icon(Icons.search_rounded, size: 20),
                    color: AppColors.primary,
                    tooltip: 'Search by name or mobile',
                    visualDensity: VisualDensity.compact,
                    onPressed: () {
                      setState(() => _searchOpen = true);
                      WidgetsBinding.instance.addPostFrameCallback(
                        (_) => _searchFocus.requestFocus(),
                      );
                    },
                  ),
                ] else
                  Expanded(
                    child: TextField(
                      controller: _searchCtrl,
                      focusNode: _searchFocus,
                      onChanged: _onSearchChanged,
                      // Client report (2026-10-07): TextInputType.phone
                      // forced the numeric keypad, which made searching by
                      // name impossible to even type — the backend already
                      // matches name too now. Plain text keyboard.
                      //
                      // Also client-reported: a blank, untappable box opening
                      // above the keyboard — Android's autofill suggestion
                      // strip rendering empty because this field gave it no
                      // hint of what it is. autofillHints: const [] tells
                      // Android explicitly not to offer autofill here.
                      autofillHints: const [],
                      enableSuggestions: false,
                      autocorrect: false,
                      decoration: InputDecoration(
                        isDense: true,
                        hintText: 'Search by name or mobile...',
                        prefixIcon: const Icon(Icons.search_rounded, size: 18),
                        suffixIcon: IconButton(
                          icon: const Icon(Icons.close_rounded, size: 18),
                          visualDensity: VisualDensity.compact,
                          onPressed: () {
                            _searchCtrl.clear();
                            _onSearchChanged('');
                            setState(() => _searchOpen = false);
                          },
                        ),
                        contentPadding: const EdgeInsets.symmetric(vertical: 8),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(AppRadius.md),
                        ),
                      ),
                    ),
                  ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  _tabPill('Walk-in', _walkinPatients.length, _TodayTab.walkin),
                  const SizedBox(width: 8),
                  _tabPill('Phone', _phonePatients.length, _TodayTab.phone),
                  const SizedBox(width: 8),
                  _tabPill('Status', _statusPatients.length, _TodayTab.status),
                ],
              ),
            ),
          ),
          const Divider(height: 1, color: Color(0xFFE2E8F0)),
          if (_loading)
            const AppSkeletonList(count: 3, itemHeight: 56)
          else if (_error != null)
            Padding(
              padding: const EdgeInsets.all(20),
              child: Text(
                _error!,
                style: const TextStyle(color: AppColors.red, fontSize: 12),
              ),
            )
          else if (activeItems.isEmpty)
            Padding(
              padding: const EdgeInsets.all(24),
              child: Center(
                child: Text(
                  emptyMessage,
                  style: const TextStyle(
                    fontSize: 13,
                    color: AppColors.textSecondary,
                  ),
                ),
              ),
            )
          else
            // Plain Column, not a shrinkWrap ListView — see the matching
            // comment on _buildPrimaryQueue for why a shrink-wrapped list
            // nested in this screen's outer scrollable can leave a stale
            // blank gap below the real rows.
            //
            // Each entry is flat (no card border/shadow — see _patientRow's
            // doc comment), so a single hairline divider wasn't enough to
            // read as "a new patient" at a glance. A visible soft-tinted gap
            // between entries (not just a thin line) gives that separation
            // real weight without turning these back into individual
            // bordered cards.
            Column(
              children: [
                for (var i = 0; i < activeItems.length; i++) ...[
                  if (i > 0) Container(height: 8, color: AppColors.surfaceFill),
                  activeItems[i],
                ],
              ],
            ),
        ],
      ),
    );
  }

  Widget _tabPill(String label, int count, _TodayTab tab) {
    final active = _activeTab == tab;
    return GestureDetector(
      onTap: () => setState(() => _activeTab = tab),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
        decoration: BoxDecoration(
          color: active ? AppColors.primary : AppColors.background,
          borderRadius: BorderRadius.circular(AppRadius.full),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              label,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: active ? Colors.white : AppColors.textSecondary,
              ),
            ),
            const SizedBox(width: 6),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
              decoration: BoxDecoration(
                color: active
                    ? Colors.white.withValues(alpha: 0.25)
                    : AppColors.primary.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(AppRadius.full),
              ),
              child: Text(
                '$count',
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.w800,
                  color: active ? Colors.white : AppColors.primary,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ── Patient row ──────────────────────────────────────────────────────────
  //
  // Now the exact same record-card format the Patients screen uses (reusing
  // AppRecordCard(flat:true) — the Doctor/MRD/Type/Fee icon-badge field
  // grid) instead of a diverging hand-rolled layout here. flat:true keeps
  // that layout but drops the bordered/shadowed card box, since this widget
  // separates entries with a plain Divider instead of stacking full cards —
  // there isn't room for Patients-screen-sized cards in a dashboard tile,
  // and this list has far fewer action buttons than the Patients screen's
  // bottom action bar, so they move up next to the name instead.
  //
  // Web's table (receptionist-today-patients-rows.blade.php) also keeps
  // "Print" as its own always-present column, gated only by the bill_print
  // permission — separate from the Done/Check-In/wait-pill "Action" column.
  // This row used to nest the print button inside that same status-
  // contingent slot, so it silently disappeared for Done rows.
  Widget _patientRow(Patient p) {
    final wait = _waitMinutes(p);
    final waitColor = _waitColor4(wait, _data!.thresholds);
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      child: AppRecordCard(
        flat: true,
        leading: _squareAvatar(p.fullName),
        title: p.fullName.isNotEmpty ? p.fullName : '—',
        subtitle: [
          if (p.age != null) '${p.age}y',
          p.genderDisplay,
          if (p.contactNo != null) p.contactNo!,
        ].join(' · '),
        trailing: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (p.secondaryDoneAt != null)
              _miniBadge('Done', AppColors.teal)
            else if (p.primaryDoneAt != null)
              _miniBadge('Primary Done', AppColors.primary)
            else
              _miniBadge('Waiting', AppColors.orange),
            const SizedBox(width: 6),
            if (p.secondaryDoneAt != null)
              const Icon(
                Icons.check_circle_outline_rounded,
                size: 17,
                color: AppColors.teal,
              )
            else if (p.type == 'phone' && p.checkedInAt == null)
              InkWell(
                onTap: () => _openCheckIn(p),
                borderRadius: BorderRadius.circular(AppRadius.xl),
                child: Padding(
                  padding: const EdgeInsets.all(2),
                  child: Icon(
                    Icons.login_rounded,
                    size: 17,
                    color: AppColors.primary,
                  ),
                ),
              )
            else
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: waitColor.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(AppRadius.xl),
                ),
                child: Text(
                  _fmtWaitLocal(wait),
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                    color: waitColor,
                  ),
                ),
              ),
            if (PermissionService.instance.can(Perm.opdBillPrint))
              InkWell(
                onTap: () => _openPrint(p),
                borderRadius: BorderRadius.circular(AppRadius.xl),
                child: Padding(
                  padding: const EdgeInsets.only(left: 6),
                  child: Icon(
                    Icons.print_outlined,
                    size: 17,
                    color: AppColors.primaryA50,
                  ),
                ),
              ),
          ],
        ),
        fields: _patientFields(p),
      ),
    );
  }

  // Same field set Patients screen shows (Doctor/MRD/Type/Fee) — web/API
  // parity note in that screen applies equally here.
  List<AppRecordField> _patientFields(Patient p) => [
    if (p.doctor != null)
      AppRecordField(
        'Doctor',
        p.doctor!.name,
        icon: Icons.person_outline_rounded,
        color: RecordFieldColors.doctor,
      ),
    AppRecordField(
      'MRD',
      p.patientCode,
      icon: Icons.badge_outlined,
      color: AppColors.primary,
    ),
    AppRecordField(
      'Type',
      p.type == 'phone' ? 'Phone' : 'Walk-in',
      icon: Icons.call_outlined,
      color: RecordFieldColors.caseType,
    ),
    if (p.caseFee != null)
      AppRecordField(
        'Fee',
        '${currentCurrencySymbol()}${_fmtFee(p.caseFee!)}',
        icon: Icons.currency_rupee_rounded,
        color: RecordFieldColors.package,
      ),
  ];

  String _fmtFee(double v) =>
      v == v.toInt() ? v.toInt().toString() : v.toStringAsFixed(2);

  Widget _squareAvatar(String name) => Container(
    width: 36,
    height: 36,
    decoration: BoxDecoration(
      color: AppColors.primary,
      borderRadius: BorderRadius.circular(10),
    ),
    alignment: Alignment.center,
    child: Text(
      name.trim().isNotEmpty ? name.trim()[0].toUpperCase() : '?',
      style: const TextStyle(
        color: Colors.white,
        fontWeight: FontWeight.w800,
        fontSize: 15,
      ),
    ),
  );

  // ── Status tab row (web pull 2026-09-28) ────────────────────────────────
  // Reuses the server-computed `status_label`/tone (Patient::workflowStage(),
  // already remapped to match receptionist-today-patients-rows.blade.php)
  // instead of the simplified Done/Primary Done/Waiting badge `_patientRow`
  // above uses for the other tabs. No Wait/Check-In trailing logic here —
  // web's Status tab drops the Wait column entirely once a patient has moved
  // past Primary Exam.
  Color _toneColor(String? tone) => switch (tone) {
    'warning' => AppColors.orange,
    'info' => AppColors.blue,
    'purple' => AppColors.purple,
    'teal' => AppColors.teal,
    'success' => AppColors.green,
    'danger' => AppColors.red,
    'primary' => AppColors.primary,
    _ => AppColors.textSecondary,
  };

  // Same AppRecordCard(flat:true) format as _patientRow above — see that
  // doc comment. Web keeps Print as its own always-present column (gated
  // only by bill_print), separate from the status badge — was previously
  // hidden once secondaryDoneAt was set, so "Done" rows lost the print
  // action entirely.
  Widget _statusPatientRow(Patient p) {
    final tone = _toneColor(p.statusTone);
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      child: AppRecordCard(
        flat: true,
        leading: _squareAvatar(p.fullName),
        title: p.fullName.isNotEmpty ? p.fullName : '—',
        subtitle: [
          if (p.age != null) '${p.age}y',
          p.genderDisplay,
          if (p.contactNo != null) p.contactNo!,
        ].join(' · '),
        trailing: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              mainAxisSize: MainAxisSize.min,
              children: [
                _miniBadge(p.statusLabel ?? '—', tone),
                // Web pull 2026-09-29 — `workflowStage()`'s secondary line
                // (e.g. "Payment pending", "In ward", "Dilated").
                if (p.statusSub != null && p.statusSub!.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 3),
                    child: Text(
                      p.statusSub!,
                      style: const TextStyle(
                        fontSize: 9.5,
                        color: AppColors.textSecondary,
                      ),
                    ),
                  ),
              ],
            ),
            if (PermissionService.instance.can(Perm.opdBillPrint))
              InkWell(
                onTap: () => _openPrint(p),
                borderRadius: BorderRadius.circular(AppRadius.xl),
                child: Padding(
                  padding: const EdgeInsets.only(left: 8),
                  child: Icon(
                    Icons.print_outlined,
                    size: 17,
                    color: AppColors.primaryA50,
                  ),
                ),
              ),
          ],
        ),
        fields: _patientFields(p),
      ),
    );
  }

  Widget _miniBadge(String label, Color color) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
    decoration: BoxDecoration(
      color: color.withValues(alpha: 0.12),
      borderRadius: BorderRadius.circular(AppRadius.xl),
    ),
    child: Text(
      label,
      style: TextStyle(fontSize: 9, fontWeight: FontWeight.w700, color: color),
    ),
  );

  int _waitMinutes(Patient p) {
    final ref = p.checkedInAt ?? p.createdAt;
    if (ref == null) return 0;
    return DateTime.now().difference(ref).inMinutes.clamp(0, 99999);
  }

  // 4-tier wait color (green/orange/red/fire) — matches web's wait-fire tier
  // beyond the red threshold, which the shared 3-tier `_waitColor` above
  // (Primary Queue widget) doesn't have.
  Color _waitColor4(int minutes, TodayPatientsThresholds t) {
    if (minutes < t.rGreen) return AppColors.waitGreen;
    if (minutes < t.rOrange) return AppColors.waitOrange;
    if (minutes < t.rRed) return AppColors.waitRed;
    return AppColors.redDark;
  }

  String _fmtWaitLocal(int minutes) {
    if (minutes < 1) return 'Just in';
    if (minutes < 60) return '${minutes}m';
    final h = minutes ~/ 60;
    final m = minutes % 60;
    return m > 0 ? '${h}h ${m}m' : '${h}h';
  }
}

// ── Table header widget ───────────────────────────────────────────────────────

class _TH extends StatelessWidget {
  final String text;
  final bool right;
  const _TH(this.text, {this.right = false});

  @override
  Widget build(BuildContext context) {
    return Text(
      text,
      textAlign: right ? TextAlign.right : TextAlign.left,
      style: const TextStyle(
        fontSize: 11,
        fontWeight: FontWeight.w700,
        color: Color(0xFF64748B),
      ),
    );
  }
}
