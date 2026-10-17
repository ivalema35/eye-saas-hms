import 'dart:async';
import '../widgets/app_error_state.dart';
import 'package:flutter/material.dart';
import '../widgets/app_refresh_indicator.dart';
import '../widgets/app_animations.dart';
import '../widgets/dash_tab_switch.dart';
import '../widgets/app_empty_state.dart';
import '../utils/app_route.dart';
import '../models/auth_models.dart';
import '../models/doctor_dashboard_models.dart';
import '../models/patient_models.dart';
import '../services/base_service.dart';
import '../services/doctor_dashboard_service.dart';
import '../utils/refreshable.dart';
import '../widgets/skeleton.dart';
import 'doctor_ot_list_screen.dart';
import 'latest_exam_view_screen.dart';
import 'primary_exam_screen.dart';
import 'secondary_exam_screen.dart';
import 'profile_screen.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/permissions.dart';
import '../services/permission_service.dart';

class DoctorDashboardScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  /// Web parity: doctor has no drawer at all — its "menu" is the top-right
  /// avatar dropdown (My Profile / Logout), matching
  /// `hospital/layouts/app.blade.php`'s `.user-dropdown` and the tablet
  /// app's equivalent `_DoctorAvatarMenu`. This screen navigates to
  /// ProfileScreen itself; [onLogout] is the one action it can't own
  /// (HomeScreen already holds the canonical logout — cache clearing +
  /// navigation to LoginScreen — no reason to duplicate that here).
  final VoidCallback? onLogout;

  const DoctorDashboardScreen({
    super.key,
    required this.user,
    required this.hospital,
    this.onLogout,
  });

  @override
  State<DoctorDashboardScreen> createState() => _DoctorDashboardScreenState();
}

class _DoctorDashboardScreenState extends State<DoctorDashboardScreen> implements Refreshable {
  DoctorDashboardData? _data;
  bool _loading = true;
  String? _error;
  bool _noPermissions = false;
  int? _viewingDoctorId;
  Timer? _tickTimer;

  // Web parity: doctoredashboard.blade.php's Primary/Secondary Patient
  // tables each have their own "Show entries"/search/pagination controls
  // (~lines 900-990) — web's own markup for these is static HTML with no
  // JS wired to it (the Previous/Next buttons are literally `disabled`),
  // so this makes them actually work client-side over the already-fetched
  // queue list instead of reproducing web's inert placeholders.
  String _primarySearch = '';
  String _secondarySearch = '';
  int _primaryPageSize = 10;
  int _secondaryPageSize = 10;
  int _primaryPage = 1;
  int _secondaryPage = 1;

  // OPD/OT dashboard tab (web pull 2026-10-13, doctoredashboard.blade.php's
  // `.dash-tab-bar`) — OPD selected by default, same page/data already
  // loaded, just switches which sections show. Same client-side
  // search/pagination convention as the primary/secondary tables above.
  String _activeDashTab = 'opd';
  // Ward panel shows Ward Management (default) or, via its header button,
  // the patients this doctor handed to an OT assistant.
  bool _showOtAssistant = false;
  String _otPendingSearch = '';
  String _otCompletedSearch = '';
  int _otPendingPageSize = 10;
  int _otCompletedPageSize = 10;
  int _otPendingPage = 1;
  int _otCompletedPage = 1;

  @override
  void initState() {
    super.initState();
    _load();
    // 1-second tick: updates wait pills + dilation countdown
    _tickTimer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (mounted) setState(() {});
    });
  }

  @override
  void dispose() {
    _tickTimer?.cancel();
    super.dispose();
  }

  @override
  Future<void> refreshSilently() => _load();

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; _noPermissions = false; });
    try {
      final data = await DoctorDashboardService.instance.fetchDashboard(
        viewDoctorId: _viewingDoctorId,
      );
      if (mounted) setState(() { _data = data; _loading = false; });
    } on PermissionDeniedException {
      // A calm "no permissions" state, not a hard error — matches the
      // regular Dashboard's treatment of the same zero-permission
      // situation. See DOCTOR_DASHBOARD_FIX_PLAN.md Phase 3.
      if (mounted) setState(() { _noPermissions = true; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString(); _loading = false; });
    }
  }

  void _selectDoctor(int? doctorId) {
    setState(() => _viewingDoctorId = doctorId);
    _load();
  }

  // ── Build ─────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: _buildAppBar(),
      body: _loading && _data == null
          ? const AppSkeletonList()
          : _noPermissions
              ? _buildNoPermissionsState()
              : _error != null && _data == null
                  ? _buildError()
                  : AppRefreshIndicator(
                      onRefresh: _load,
                      color: AppColors.primary,
                      child: _buildBody(),
                    ),
    );
  }

  Widget _buildNoPermissionsState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 24),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: const Color(0xFFFEF3C7),
              borderRadius: BorderRadius.circular(AppRadius.md),
              border: Border.all(color: const Color(0xFFF59E0B).withValues(alpha: 0.40)),
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
                        style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Color(0xFF92400E)),
                      ),
                      SizedBox(height: 3),
                      Text(
                        'Your role has no feature permissions assigned. Please contact your administrator.',
                        style: TextStyle(fontSize: 11, color: Color(0xFF92400E), height: 1.4),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          const AppEmptyState(message: 'No dashboard data to show'),
        ]),
      ),
    );
  }

  PreferredSizeWidget _buildAppBar() {
    final subtitle = (_viewingDoctorId != null && _data?.viewingDoctor != null)
        ? 'Viewing: ${_data!.viewingDoctor!.name}'
        : widget.user.name;
    final tp = _data == null
        ? null
        : (_activeDashTab == 'ot' ? _data!.todayOtAppointmentsAdded : _data!.tenantTodayPatients);

    return AppBar(
      backgroundColor: AppColors.primary,
      elevation: 0,
      leadingWidth: 56,
      leading: Center(child: _buildAvatarMenu()),
      title: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        const Text('Dashboard',
            style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 17)),
        Text(tp == null ? subtitle : '$subtitle  ·  TP: $tp',
            style: const TextStyle(color: Colors.white70, fontSize: 11), maxLines: 1, overflow: TextOverflow.ellipsis),
      ]),
      actions: [
        Center(
          child: DashTabSwitch(
            value: _activeDashTab,
            onChanged: (t) => setState(() => _activeDashTab = t),
          ),
        ),
        IconButton(
          icon: const Icon(Icons.refresh_rounded, color: Colors.white),
          onPressed: _load,
          tooltip: 'Refresh',
        ),
      ],
    );
  }

  // Web parity: My Profile + Logout, nothing else — matches
  // `.user-dropdown` in hospital/layouts/app.blade.php exactly.
  Widget _buildAvatarMenu() {
    return PopupMenuButton<String>(
      tooltip: '',
      offset: const Offset(0, 44),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
      onSelected: (v) {
        if (v == 'profile') {
          Navigator.push(context, appRoute(ProfileScreen(user: widget.user, hospital: widget.hospital)));
        } else if (v == 'logout') {
          widget.onLogout?.call();
        }
      },
      itemBuilder: (context) => [
        PopupMenuItem(
          value: 'profile',
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            Icon(Icons.person_outline_rounded, size: 18, color: AppColors.primary),
            const SizedBox(width: 10),
            const Text('My Profile'),
          ]),
        ),
        const PopupMenuDivider(height: 1),
        PopupMenuItem(
          value: 'logout',
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            Icon(Icons.logout_rounded, size: 18, color: AppColors.red),
            const SizedBox(width: 10),
            Text('Logout', style: TextStyle(color: AppColors.red, fontWeight: FontWeight.w700)),
          ]),
        ),
      ],
      child: Container(
        width: 32,
        height: 32,
        decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.2), shape: BoxShape.circle),
        alignment: Alignment.center,
        child: const Icon(Icons.person_rounded, color: Colors.white, size: 18),
      ),
    );
  }

  Widget _buildError() {
    // Show the real backend reason (e.g. "Access denied." for a
    // permission-denied 403, now meaningful since
    // ROLES_PERMISSIONS_ROUND3_FIX_PLAN.md Phase 1) instead of a hardcoded
    // literal that made every failure — permission, network, server error —
    // look identical. See ROLES_PERMISSIONS_DEEP_AUDIT_ROUND3.md.
    final message = (_error != null && _error!.trim().isNotEmpty)
        ? _error!
        : 'Failed to load dashboard';
    return AppErrorState(message: message, onRetry: _load);
  }

  Widget _buildBody() {
    final data = _data!;
    return ListView(
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 100),
      children: [
        _buildDoctorOverviewPanel(data),
        if (_viewingDoctorId != null && data.viewingDoctor != null) ...[
          const SizedBox(height: 10),
          _buildViewingBanner(data.viewingDoctor!),
        ],
        const SizedBox(height: 14),
        if (_activeDashTab == 'opd') ...[
          _buildQueueTable(isPrimary: true, patients: data.primaryQueue, secondary: const []),
          const SizedBox(height: 14),
          _buildQueueTable(isPrimary: false, patients: const [], secondary: data.secondaryQueue),
        ] else ...[
          _buildOtQueueTable(isPending: true, items: data.otPaymentPendingQueue),
          const SizedBox(height: 14),
          _buildOtQueueTable(isPending: false, items: _showOtAssistant ? data.otAssistantQueue : data.otWardQueue),
        ],
      ],
    );
  }

  // ── Doctor Overview ──────────────────────────────────────────────────────────
  //
  // Web parity: `doctoredashboard.blade.php`'s single `.doc-overview-panel` —
  // a featured card for whichever doctor's dashboard is open (self, or a
  // doctor switched to via `?view_doctor=`), stacking hospital-wide /
  // this-doctor's-OPD / this-doctor's-OT counts as three bars, plus an
  // "All Doctor List" of every OTHER doctor with OPD+OT stats merged into
  // ONE card each. Replaces the app's previous 4 separate sections (a
  // standalone stat-card row, a hospital-today bar, an OT-only doctor strip
  // and an OPD-only doctor strip) that don't exist as separate pieces on
  // web — web has no "Reports" link and no standalone stat cards here at all.

  Widget _buildDoctorOverviewPanel(DoctorDashboardData data) {
    final viewingName = data.viewingDoctor?.name ?? widget.user.name;
    final otById = {for (final o in data.otDoctorCards) o.id: o};
    final others = data.doctorCards.where((d) => !d.isSelf).toList();

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFF4F8FB),
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.08)),
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        _buildFeaturedDoctorCard(data, viewingName),
        const SizedBox(height: 10),
        if (others.isEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 8),
            child: Text('No other doctors on duty right now.', style: TextStyle(fontSize: 13, color: AppColors.textSecondary)),
          )
        else
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: others.map((d) => Padding(
                padding: const EdgeInsets.only(right: 10),
                child: _buildCombinedDoctorCard(d, otById[d.id]),
              )).toList(),
            ),
          ),
      ]),
    );
  }

  Widget _buildFeaturedDoctorCard(DoctorDashboardData data, String name) {
    return _SelectedPulse(
      active: true,
      radius: 10,
      child: Container(
        padding: const EdgeInsets.all(6),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: AppColors.primary.withValues(alpha: 0.45)),
        ),
        child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          // Web pull 2026-10-13 (same OPD/OT tab-switch pull): the OPD-tab
          // top bar dropped its 4th "OT" segment (old $otPipelineCount) —
          // now just C/PC/SC. Web's controller still computes the value
          // (kept for later reuse, unused anywhere in the blade now), so
          // the API keeps sending tenantTodayOtPipeline too; only the
          // display here stops showing it, matching web exactly.
          if (_activeDashTab == 'opd')
            _overviewBar(const Color(0xFF0F4F86), [
              ('C', '${data.tenantTodayPatients}'),
              ('PC', '${data.tenantTodayPrimary}'),
              ('SC', '${data.tenantTodaySecondary}'),
            ])
          else
            _overviewBar(const Color(0xFF0F4F86), [
              ('OT', '${data.todayOtCounterPending}'),
              ('PD', '${data.todayPaymentComplete}'),
              ('OC', '${data.todayDischarged}'),
            ]),
          const SizedBox(height: 4),
          Row(children: [
            Container(
              width: 26, height: 26,
              alignment: Alignment.center,
              decoration: const BoxDecoration(color: AppColors.teal, shape: BoxShape.circle),
              child: const Icon(Icons.badge_rounded, color: Colors.white, size: 14),
            ),
            const SizedBox(width: 6),
            Expanded(
              child: Text(name,
                  style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: AppColors.primary),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis),
            ),
            if (_viewingDoctorId != null)
              TextButton(
                onPressed: () => _selectDoctor(null),
                style: TextButton.styleFrom(
                  minimumSize: Size.zero,
                  padding: const EdgeInsets.symmetric(horizontal: 4),
                  tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                ),
                child: Text('← Back', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: AppColors.primary)),
              ),
          ]),
          const SizedBox(height: 4),
          // Web parity: `.doc-ref-btns opd-only` vs `.doc-ref-btns--ot
          // ot-only` — only one of these two bars shows at a time, matching
          // whichever dashboard tab is active, not both stacked. "Pending"
          // is plain text now (web removed its onclick link in the same
          // pull) — the new OT Pending/Completed tables below are the
          // entry point into DoctorOtListScreen now.
          if (_activeDashTab == 'opd')
            _overviewBar(const Color(0xFF0A2F52), [
              ('Patient', '${data.stats.assignedToday}'),
              ('P', '${data.stats.primaryDone}'),
              ('S', '${data.stats.secondaryDone}'),
            ])
          else
            Builder(builder: (_) {
              final activeId = _viewingDoctorId ?? widget.user.id;
              final ot = data.otDoctorCards.where((o) => o.id == activeId).firstOrNull;
              return _overviewBar(
                const Color(0xFF0D9488),
                [('OT', '${ot?.otCounterPending ?? 0}'), ('PD', '${ot?.otPaymentComplete ?? 0}'), ('OC', '${ot?.otDischarged ?? 0}')],
              );
            }),
        ]),
      ),
    );
  }

  Widget _overviewBar(Color bg, List<(String, String)> segments, {VoidCallback? onMidTap}) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(6),
      child: ColoredBox(
        color: bg,
        child: Row(children: [
          for (int i = 0; i < segments.length; i++) ...[
            if (i > 0) Container(width: 1, height: 16, color: Colors.white24),
            Expanded(child: _overviewSeg(segments[i].$1, segments[i].$2, onTap: i == 1 ? onMidTap : null)),
          ],
        ]),
      ),
    );
  }

  Widget _overviewSeg(String label, String value, {VoidCallback? onTap}) {
    final tappable = onTap != null;
    final body = Padding(
      padding: const EdgeInsets.symmetric(vertical: 5, horizontal: 2),
      child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
        Text(label, style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w700, decoration: tappable ? TextDecoration.underline : null, decorationColor: Colors.white)),
        const SizedBox(width: 4),
        Text(value, style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w800, height: 1)),
        if (tappable) ...[
          const SizedBox(width: 2),
          const Icon(Icons.north_east_rounded, size: 12, color: Colors.white),
        ],
      ]),
    );
    if (!tappable) return body;
    return Material(
      color: Colors.white.withValues(alpha: 0.20),
      child: InkWell(onTap: onTap, child: body),
    );
  }

  Widget _buildCombinedDoctorCard(DoctorCardInfo doc, OtDoctorCardInfo? ot) {
    final isSelected = _viewingDoctorId == doc.id;
    return _SelectedPulse(
      active: isSelected,
      radius: 10,
      child: PressScaleWrapper(
        onTap: () => _selectDoctor(doc.id),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 220),
          width: 200,
          padding: const EdgeInsets.fromLTRB(8, 8, 8, 8),
          decoration: BoxDecoration(
            color: isSelected ? const Color(0xFFE8F4FC) : Colors.white,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: isSelected ? AppColors.primary : AppColors.primary.withValues(alpha: 0.16), width: isSelected ? 1.5 : 1),
          ),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              Icon(Icons.badge_rounded, size: 15, color: AppColors.teal),
              const SizedBox(width: 5),
              Expanded(
                child: Text(doc.name,
                    style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w800, color: AppColors.primary),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis),
              ),
            ]),
            const SizedBox(height: 8),
            // Web parity: opd-only vs ot-only — one row per active tab, not
            // both stacked. "Pending" (OP) is plain text now, not a link —
            // web removed its onclick in the same pull; see the featured
            // card's OT bar above for where the entry point moved to.
            if (_activeDashTab == 'opd')
              Row(children: [
                _refBtn('Pt', '${doc.assignedToday}', const Color(0xFF0A2F52)),
                const SizedBox(width: 4),
                _refBtn('P', '${doc.primaryCount}', const Color(0xFF0A2F52)),
                const SizedBox(width: 4),
                _refBtn('S', '${doc.secondaryCount}', const Color(0xFF0A2F52)),
              ])
            else
              Row(children: [
                _refBtn('OT', '${ot?.otCounterPending ?? 0}', const Color(0xFF0D9488)),
                const SizedBox(width: 4),
                _refBtn('PD', '${ot?.otPaymentComplete ?? 0}', const Color(0xFF0D9488)),
                const SizedBox(width: 4),
                _refBtn('OC', '${ot?.otDischarged ?? 0}', const Color(0xFF0D9488)),
              ]),
          ]),
        ),
      ),
    );
  }

  Widget _refBtn(String label, String value, Color color, {VoidCallback? onTap}) {
    final tappable = onTap != null;
    return Expanded(
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(5),
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 5, horizontal: 2),
            decoration: BoxDecoration(
              color: tappable ? Colors.white : color,
              borderRadius: BorderRadius.circular(5),
              border: Border.all(color: color, width: tappable ? 1.4 : 0),
            ),
            child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
              Text(label, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: tappable ? color : Colors.white.withValues(alpha: 0.92), decoration: tappable ? TextDecoration.underline : null, decorationColor: color)),
              const SizedBox(width: 3),
              Text(value, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: tappable ? color : Colors.white, height: 1)),
              if (tappable) ...[const SizedBox(width: 1), Icon(Icons.north_east_rounded, size: 11, color: color)],
            ]),
          ),
        ),
      ),
    );
  }

  // ── Viewing Banner ──────────────────────────────────────────────────────────

  Widget _buildViewingBanner(DoctorCardInfo doctor) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF8E1),
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: const Color(0xFFFFC107)),
      ),
      child: Row(children: [
        const Icon(Icons.visibility_rounded, size: 15, color: Color(0xFFB45309)),
        const SizedBox(width: 8),
        Expanded(
          child: Text("Viewing Dr. ${doctor.name}'s patients",
              style: const TextStyle(
                  fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF92400E))),
        ),
        TextButton(
          onPressed: () => _selectDoctor(null),
          style: TextButton.styleFrom(
            foregroundColor: AppColors.primary,
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            minimumSize: Size.zero,
            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
          ),
          child: const Text('Back to Mine',
              style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800)),
        ),
      ]),
    );
  }

  // ── Queue Table ─────────────────────────────────────────────────────────────

  // Web parity: `doctoredashboard.blade.php`'s Primary/Secondary Patient
  // cards (~lines 900-990) — a "Show entries"/search row, a bordered table
  // with # / Patient Name / Gender / Age / City / Waiting / Action columns,
  // and a "Showing X to Y of Z entries" + Previous/Next footer. Web's own
  // markup for the entries dropdown, search box and pagination buttons is
  // static HTML with nothing wired to it (the buttons are literally
  // `disabled`) — this makes the same controls actually filter/paginate
  // the already-fetched queue list client-side instead of reproducing
  // web's inert placeholders. Mobile gets the full table width to itself
  // (Primary/Secondary stack vertically here, unlike tablet's side-by-side
  // halves) — but a phone still can't fit 7 real columns (including an
  // "Examine"/"View" action button) on one screen without either
  // squeezing text into illegible truncation or, worse, squeezing a
  // *button's* text so hard it wraps character-by-character (Expanded's
  // flex columns did exactly that to the Action column — a Wrap given a
  // tight ~39px width forces its child button's Text to wrap the same
  // way the header words did). Fix: give every column its natural,
  // comfortable fixed width and let the table scroll horizontally inside
  // its card instead of fighting for space — the header/toolbar/footer
  // stay full-width and only the column area scrolls.

  static const double _colNoW = 30;
  static const double _colNameW = 120;
  static const double _colGenderW = 60;
  static const double _colAgeW = 34;
  static const double _colCityW = 84;
  static const double _colWaitW = 76;
  static const double _colActionW = 110;
  static const double _colGap = 6;
  static const double _rowHPad = 10;

  static double get _tableContentWidth =>
      _colNoW + _colNameW + _colGenderW + _colAgeW + _colCityW + _colWaitW + _colActionW + _colGap * 6 + _rowHPad * 2;

  Widget _tableRow(List<Widget> cells) {
    return Row(crossAxisAlignment: CrossAxisAlignment.center, children: [
      SizedBox(width: _colNoW, child: cells[0]),
      const SizedBox(width: _colGap),
      SizedBox(width: _colNameW, child: cells[1]),
      const SizedBox(width: _colGap),
      SizedBox(width: _colGenderW, child: cells[2]),
      const SizedBox(width: _colGap),
      SizedBox(width: _colAgeW, child: cells[3]),
      const SizedBox(width: _colGap),
      SizedBox(width: _colCityW, child: cells[4]),
      const SizedBox(width: _colGap),
      SizedBox(width: _colWaitW, child: cells[5]),
      const SizedBox(width: _colGap),
      SizedBox(width: _colActionW, child: cells[6]),
    ]);
  }

  Widget _tableHeaderRow() {
    // maxLines/overflow/softWrap are load-bearing here — without them, a
    // header word that doesn't fit its column (e.g. "Patient Name" or
    // "Action" on a narrow phone) wraps character-by-character instead of
    // truncating, which is what made this row unreadable before.
    Widget h(String label, {bool left = false}) => Text(
          label,
          textAlign: left ? TextAlign.left : TextAlign.center,
          maxLines: 1,
          softWrap: false,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: AppColors.textSecondary),
        );
    return Container(
      color: const Color(0xFFF4F8FB),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      child: _tableRow([h('#'), h('Patient Name', left: true), h('Gender'), h('Age'), h('City'), h('Waiting'), h('Action')]),
    );
  }

  static String _capitalize(String s) => s.isEmpty ? s : s[0].toUpperCase() + s.substring(1).toLowerCase();

  Widget _dataRow({
    required String drIndex,
    required String name,
    required int? age,
    required String? gender,
    required String city,
    required List<Widget> waitPills,
    required Widget actionWidget,
    Widget? viewButton,
  }) {
    return Container(
      decoration: BoxDecoration(border: Border(bottom: BorderSide(color: const Color(0xFFCDE5F5).withValues(alpha: 0.5)))),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
      child: _tableRow([
        Text(drIndex, textAlign: TextAlign.center, maxLines: 1, softWrap: false, overflow: TextOverflow.ellipsis, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: AppColors.primary)),
        Text(name, style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: AppColors.primary), maxLines: 1, softWrap: false, overflow: TextOverflow.ellipsis),
        Text(gender != null && gender.isNotEmpty ? _capitalize(gender) : '—', textAlign: TextAlign.center, maxLines: 1, softWrap: false, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
        Text(age != null ? '$age' : '—', textAlign: TextAlign.center, maxLines: 1, softWrap: false, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
        Text(city.isNotEmpty ? city : '-', textAlign: TextAlign.center, maxLines: 1, softWrap: false, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
        Wrap(alignment: WrapAlignment.center, spacing: 4, runSpacing: 2, children: waitPills),
        Wrap(alignment: WrapAlignment.center, spacing: 6, runSpacing: 4, children: [
          actionWidget,
          ?viewButton,
        ]),
      ]),
    );
  }

  Widget _buildQueueTable({
    required bool isPrimary,
    required List<PrimaryPatient> patients,
    required List<SecondaryPatient> secondary,
  }) {
    final title = isPrimary ? 'Primary Patient' : 'Secondary Patient';
    final search = (isPrimary ? _primarySearch : _secondarySearch).trim().toLowerCase();
    final pageSize = isPrimary ? _primaryPageSize : _secondaryPageSize;
    final page = isPrimary ? _primaryPage : _secondaryPage;

    final filteredPrimary = isPrimary ? patients.where((p) => search.isEmpty || p.patientName.toLowerCase().contains(search)).toList() : const <PrimaryPatient>[];
    final filteredSecondary = !isPrimary ? secondary.where((p) => search.isEmpty || p.patientName.toLowerCase().contains(search)).toList() : const <SecondaryPatient>[];
    final totalCount = isPrimary ? filteredPrimary.length : filteredSecondary.length;
    final totalPages = totalCount == 0 ? 1 : (totalCount / pageSize).ceil();
    final clampedPage = page.clamp(1, totalPages);
    final start = ((clampedPage - 1) * pageSize).clamp(0, totalCount);
    final end = (start + pageSize).clamp(0, totalCount);
    final pagePrimary = isPrimary ? filteredPrimary.sublist(start, end) : const <PrimaryPatient>[];
    final pageSecondary = !isPrimary ? filteredSecondary.sublist(start, end) : const <SecondaryPatient>[];

    void setPage(int p) => setState(() {
          if (isPrimary) {
            _primaryPage = p;
          } else {
            _secondaryPage = p;
          }
        });

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: Color(0xFFCDE5F5)),
        boxShadow: [
          BoxShadow(
              color: AppColors.primary.withValues(alpha: 0.07),
              blurRadius: 10,
              offset: const Offset(0, 3)),
        ],
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 13),
          color: AppColors.primary,
          child: Row(children: [
            Text(title.toUpperCase(),
                style: const TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 0.8)),
            const Spacer(),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(AppRadius.xl),
              ),
              child: Text('${isPrimary ? patients.length : secondary.length}',
                  style: const TextStyle(
                      color: Colors.white, fontSize: 11, fontWeight: FontWeight.w800)),
            ),
          ]),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(14, 10, 14, 8),
          child: Row(children: [
            Text('Show entries', style: TextStyle(fontSize: 11, color: AppColors.textSecondary)),
            const SizedBox(width: 6),
            DropdownButton<int>(
              value: pageSize,
              underline: const SizedBox.shrink(),
              isDense: true,
              style: TextStyle(fontSize: 11, color: AppColors.primary, fontWeight: FontWeight.w700),
              items: const [10, 20, 30].map((n) => DropdownMenuItem(value: n, child: Text('$n'))).toList(),
              onChanged: (v) {
                if (v == null) return;
                setState(() {
                  if (isPrimary) {
                    _primaryPageSize = v;
                    _primaryPage = 1;
                  } else {
                    _secondaryPageSize = v;
                    _secondaryPage = 1;
                  }
                });
              },
            ),
            const Spacer(),
            Text('search', style: TextStyle(fontSize: 11, color: AppColors.textSecondary)),
            const SizedBox(width: 6),
            SizedBox(
              width: 120,
              height: 30,
              child: TextField(
                style: const TextStyle(fontSize: 11.5),
                decoration: InputDecoration(
                  isDense: true,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(6), borderSide: const BorderSide(color: Color(0xFFCBD5E1))),
                ),
                onChanged: (v) => setState(() {
                  if (isPrimary) {
                    _primarySearch = v;
                    _primaryPage = 1;
                  } else {
                    _secondarySearch = v;
                    _secondaryPage = 1;
                  }
                }),
              ),
            ),
          ]),
        ),
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: SizedBox(
            width: _tableContentWidth,
            child: Column(children: [
              _tableHeaderRow(),
              if (totalCount == 0)
                const Padding(padding: EdgeInsets.symmetric(vertical: 24), child: Center(child: Text('No Data Found', style: TextStyle(fontSize: 12, color: AppColors.textSecondary))))
              else if (isPrimary)
                ...pagePrimary.map(_buildPrimaryRow)
              else
                ...pageSecondary.map(_buildSecondaryRow),
            ]),
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(14, 10, 14, 12),
          child: Row(children: [
            Text(
              totalCount == 0 ? 'Showing 0 of 0 entries' : 'Showing ${start + 1} to $end of $totalCount entries',
              style: TextStyle(fontSize: 10.5, color: AppColors.textSecondary),
            ),
            const Spacer(),
            OutlinedButton(
              onPressed: clampedPage > 1 ? () => setPage(clampedPage - 1) : null,
              style: OutlinedButton.styleFrom(minimumSize: Size.zero, padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4), tapTargetSize: MaterialTapTargetSize.shrinkWrap),
              child: const Text('Previous', style: TextStyle(fontSize: 10.5)),
            ),
            const SizedBox(width: 6),
            OutlinedButton(
              onPressed: clampedPage < totalPages ? () => setPage(clampedPage + 1) : null,
              style: OutlinedButton.styleFrom(minimumSize: Size.zero, padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4), tapTargetSize: MaterialTapTargetSize.shrinkWrap),
              child: const Text('Next', style: TextStyle(fontSize: 10.5)),
            ),
          ]),
        ),
      ]),
    );
  }

  Widget _buildPrimaryRow(PrimaryPatient p) {
    final perm = PermissionService.instance;
    final canExam = perm.can(Perm.opdExamPrimary);
    final canHistory = perm.can(Perm.opdExamHistory);
    return _dataRow(
      drIndex: p.drIndexNo,
      name: p.patientName,
      age: p.age,
      gender: p.gender,
      city: p.city,
      waitPills: [_waitPill('R', p.registeredAt, _rTh)],
      actionWidget: canExam
          ? _examBtn('Examine', () => _openPrimaryExam(p))
          : const SizedBox.shrink(),
      viewButton: p.hasHistory && canHistory ? _viewBtn(() => _openLatestExam(p)) : null,
    );
  }

  Widget _buildSecondaryRow(SecondaryPatient p) {
    final perm = PermissionService.instance;
    final canExam = perm.can(Perm.opdExamSecondary);
    final pills = <Widget>[_waitPill('R', p.registeredAt, _rTh)];
    if (p.primaryDoneAt != null) {
      pills.add(_waitPill(p.isDilated ? 'D' : 'ND', p.primaryDoneAt!,
          p.isDilated ? _dTh : _ndTh));
    }

    return _dataRow(
      drIndex: p.drIndexNo,
      name: p.patientName,
      age: p.age,
      gender: p.gender,
      city: p.city,
      waitPills: pills,
      actionWidget: canExam ? _secondaryActionBtn(p) : const SizedBox.shrink(),
      // Web shows the eye whenever this visit already has a primary exam.
      // The queue itself is "primary done, secondary not done", so the last
      // exam the module opens is that primary.
      viewButton: p.primaryDoneAt != null && canExam && perm.can(Perm.opdExamHistory)
          ? _eyeBtn(() => _openLatestExam(p))
          : null,
    );
  }

  // ── OT Queue Table (OT tab) ───────────────────────────────────────────────
  //
  // Web parity: doctoredashboard.blade.php's new "OT Pending Patients" /
  // "OT Completed Patients" cards (`.row.g-4.ot-only`) — # / Patient Name /
  // Gender / Age / City / Status / Action, with the same client-side
  // search+pagination convention as the OPD tables above (web's own markup
  // for these is static/inert too). Review/View simply opens the existing
  // DoctorOtListScreen, matching web's Review/View links which just
  // navigate to the `doctor-ot` page — no duplicate assign/refuse UI here.

  static const double _colOtStatusW = 110;
  static const double _colOtActionW = 90;

  static double get _otTableContentWidth =>
      _colNoW + _colNameW + _colGenderW + _colAgeW + _colCityW + _colOtStatusW + _colOtActionW + _colGap * 6 + _rowHPad * 2;

  Widget _otTableRow(List<Widget> cells) {
    return Row(crossAxisAlignment: CrossAxisAlignment.center, children: [
      SizedBox(width: _colNoW, child: cells[0]),
      const SizedBox(width: _colGap),
      SizedBox(width: _colNameW, child: cells[1]),
      const SizedBox(width: _colGap),
      SizedBox(width: _colGenderW, child: cells[2]),
      const SizedBox(width: _colGap),
      SizedBox(width: _colAgeW, child: cells[3]),
      const SizedBox(width: _colGap),
      SizedBox(width: _colCityW, child: cells[4]),
      const SizedBox(width: _colGap),
      SizedBox(width: _colOtStatusW, child: cells[5]),
      const SizedBox(width: _colGap),
      SizedBox(width: _colOtActionW, child: cells[6]),
    ]);
  }

  Widget _otTableHeaderRow() {
    Widget h(String label, {bool left = false}) => Text(
          label,
          textAlign: left ? TextAlign.left : TextAlign.center,
          maxLines: 1,
          softWrap: false,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: AppColors.textSecondary),
        );
    return Container(
      color: const Color(0xFFF4F8FB),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      child: _otTableRow([h('#'), h('Patient Name', left: true), h('Gender'), h('Age'), h('City'), h('Status'), h('Action')]),
    );
  }

  Color _hexToColor(String hex) => Color(int.parse('FF${hex.replaceFirst('#', '')}', radix: 16));

  void _openOtList() {
    Navigator.push(context, appRoute(DoctorOtListScreen(doctorId: _viewingDoctorId ?? widget.user.id)));
  }

  Widget _otDataRow(int index, OtQueueItem item) {
    return Container(
      decoration: BoxDecoration(border: Border(bottom: BorderSide(color: const Color(0xFFCDE5F5).withValues(alpha: 0.5)))),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
      child: _otTableRow([
        Text('${index + 1}', textAlign: TextAlign.center, maxLines: 1, softWrap: false, overflow: TextOverflow.ellipsis, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: AppColors.primary)),
        Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisSize: MainAxisSize.min, children: [
          Text(item.patientName, style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: AppColors.primary), maxLines: 1, softWrap: false, overflow: TextOverflow.ellipsis),
          if (item.assistantName != null && item.assistantName!.isNotEmpty)
            Text('Asst: ${item.assistantName}', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: Color(0xFF0D9488)), maxLines: 1, softWrap: false, overflow: TextOverflow.ellipsis),
        ]),
        Text(item.gender != null && item.gender!.isNotEmpty ? _capitalize(item.gender!) : '—', textAlign: TextAlign.center, maxLines: 1, softWrap: false, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
        Text(item.age != null ? '${item.age}' : '—', textAlign: TextAlign.center, maxLines: 1, softWrap: false, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
        Text(item.city != null && item.city!.isNotEmpty ? item.city! : '-', textAlign: TextAlign.center, maxLines: 1, softWrap: false, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
        Center(
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(color: _hexToColor(item.statusTone), borderRadius: BorderRadius.circular(AppRadius.full)),
            child: Text(item.statusLabel, maxLines: 1, softWrap: false, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w700)),
          ),
        ),
        Center(child: item.isConsultPending ? _examBtn('Review', _openOtList) : _viewBtn(_openOtList)),
      ]),
    );
  }

  Widget _buildOtQueueTable({required bool isPending, required List<OtQueueItem> items}) {
    final title = isPending ? 'Payment Pending Patients' : (_showOtAssistant ? 'OT Assistant Patients' : 'Ward Management Patients');
    final search = (isPending ? _otPendingSearch : _otCompletedSearch).trim().toLowerCase();
    final pageSize = isPending ? _otPendingPageSize : _otCompletedPageSize;
    final page = isPending ? _otPendingPage : _otCompletedPage;

    final filtered = items.where((i) => search.isEmpty || i.patientName.toLowerCase().contains(search)).toList();
    final totalCount = filtered.length;
    final totalPages = totalCount == 0 ? 1 : (totalCount / pageSize).ceil();
    final clampedPage = page.clamp(1, totalPages);
    final start = ((clampedPage - 1) * pageSize).clamp(0, totalCount);
    final end = (start + pageSize).clamp(0, totalCount);
    final pageItems = filtered.sublist(start, end);

    void setPage(int p) => setState(() {
          if (isPending) {
            _otPendingPage = p;
          } else {
            _otCompletedPage = p;
          }
        });

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: const Color(0xFFCDE5F5)),
        boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.07), blurRadius: 10, offset: const Offset(0, 3))],
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 13),
          color: isPending ? AppColors.primary : const Color(0xFF0D9488),
          child: Row(children: [
            Text(title.toUpperCase(), style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w900, letterSpacing: 0.8)),
            const Spacer(),
            if (!isPending) ...[
              PressScaleWrapper(
                onTap: () => setState(() {
                  _showOtAssistant = !_showOtAssistant;
                  _otCompletedPage = 1;
                  _otCompletedSearch = '';
                }),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.xl)),
                  child: Text(
                    _showOtAssistant
                        ? 'Ward (${_data?.otWardQueue.length ?? 0})'
                        : 'OT Asst (${_data?.otAssistantQueue.length ?? 0})',
                    style: const TextStyle(color: Color(0xFF0D9488), fontSize: 11, fontWeight: FontWeight.w800),
                  ),
                ),
              ),
              const SizedBox(width: 8),
            ],
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
              decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.15), borderRadius: BorderRadius.circular(AppRadius.xl)),
              child: Text('${items.length}', style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w800)),
            ),
          ]),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(14, 10, 14, 8),
          child: Row(children: [
            Text('Show entries', style: TextStyle(fontSize: 11, color: AppColors.textSecondary)),
            const SizedBox(width: 6),
            DropdownButton<int>(
              value: pageSize,
              underline: const SizedBox.shrink(),
              isDense: true,
              style: TextStyle(fontSize: 11, color: AppColors.primary, fontWeight: FontWeight.w700),
              items: const [10, 20, 30].map((n) => DropdownMenuItem(value: n, child: Text('$n'))).toList(),
              onChanged: (v) {
                if (v == null) return;
                setState(() {
                  if (isPending) {
                    _otPendingPageSize = v;
                    _otPendingPage = 1;
                  } else {
                    _otCompletedPageSize = v;
                    _otCompletedPage = 1;
                  }
                });
              },
            ),
            const Spacer(),
            Text('search', style: TextStyle(fontSize: 11, color: AppColors.textSecondary)),
            const SizedBox(width: 6),
            SizedBox(
              width: 120,
              height: 30,
              child: TextField(
                style: const TextStyle(fontSize: 11.5),
                decoration: InputDecoration(
                  isDense: true,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(6), borderSide: const BorderSide(color: Color(0xFFCBD5E1))),
                ),
                onChanged: (v) => setState(() {
                  if (isPending) {
                    _otPendingSearch = v;
                    _otPendingPage = 1;
                  } else {
                    _otCompletedSearch = v;
                    _otCompletedPage = 1;
                  }
                }),
              ),
            ),
          ]),
        ),
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: SizedBox(
            width: _otTableContentWidth,
            child: Column(children: [
              _otTableHeaderRow(),
              if (totalCount == 0)
                const Padding(padding: EdgeInsets.symmetric(vertical: 24), child: Center(child: Text('No Data Found', style: TextStyle(fontSize: 12, color: AppColors.textSecondary))))
              else
                ...pageItems.asMap().entries.map((e) => _otDataRow(start + e.key, e.value)),
            ]),
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(14, 10, 14, 12),
          child: Row(children: [
            Text(
              totalCount == 0 ? 'Showing 0 of 0 entries' : 'Showing ${start + 1} to $end of $totalCount entries',
              style: TextStyle(fontSize: 10.5, color: AppColors.textSecondary),
            ),
            const Spacer(),
            OutlinedButton(
              onPressed: clampedPage > 1 ? () => setPage(clampedPage - 1) : null,
              style: OutlinedButton.styleFrom(minimumSize: Size.zero, padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4), tapTargetSize: MaterialTapTargetSize.shrinkWrap),
              child: const Text('Previous', style: TextStyle(fontSize: 10.5)),
            ),
            const SizedBox(width: 6),
            OutlinedButton(
              onPressed: clampedPage < totalPages ? () => setPage(clampedPage + 1) : null,
              style: OutlinedButton.styleFrom(minimumSize: Size.zero, padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4), tapTargetSize: MaterialTapTargetSize.shrinkWrap),
              child: const Text('Next', style: TextStyle(fontSize: 10.5)),
            ),
          ]),
        ),
      ]),
    );
  }

  // ── Wait Pills ──────────────────────────────────────────────────────────────

  // [green_max_min, orange_max_min, red_max_min] — anything past the 3rd
  // value is "fire". Hospital-configurable via wait_thresholds on the API
  // response, not hardcoded — see DOCTOR_DASHBOARD_FIX_PLAN.md Phase 4.
  WaitThresholds get _wt => _data?.waitThresholds ?? WaitThresholds.defaults;
  List<int> get _rTh  => [_wt.rGreen, _wt.rOrange, _wt.rRed];
  List<int> get _dTh  => [_wt.dGreen, _wt.dOrange, _wt.dRed];
  List<int> get _ndTh => [_wt.ndGreen, _wt.ndOrange, _wt.ndRed];

  Widget _waitPill(String label, DateTime since, List<int> th) {
    final mins = DateTime.now().difference(since).inMinutes.clamp(0, 99999);
    final isFire   = mins > th[2];
    final isRed    = mins > th[1];
    final isOrange = mins > th[0];

    Color bg;
    if (isFire) {
      bg = DateTime.now().second % 2 == 0 ? AppColors.waitRed : AppColors.waitRed.withValues(alpha: 0.55);
    } else if (isRed) {
      bg = AppColors.waitRed;
    } else if (isOrange) {
      bg = AppColors.waitOrange;
    } else {
      bg = AppColors.waitGreen;
    }

    final waitText = mins < 60
        ? '${mins}m'
        : '${mins ~/ 60}h${mins % 60 > 0 ? '${mins % 60}m' : ''}';

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(6)),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        Text(label,
            style: const TextStyle(
                color: Colors.white, fontSize: 9, fontWeight: FontWeight.w900)),
        const SizedBox(width: 3),
        Text(waitText,
            style: const TextStyle(
                color: Colors.white, fontSize: 9, fontWeight: FontWeight.w700)),
      ]),
    );
  }

  // ── Action Buttons ──────────────────────────────────────────────────────────

  Widget _examBtn(String label, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
        decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.circular(AppRadius.sm)),
        child: Text(label,
            maxLines: 1, softWrap: false, overflow: TextOverflow.ellipsis,
            style: const TextStyle(
                color: Colors.white, fontSize: 11, fontWeight: FontWeight.w800)),
      ),
    );
  }

  Widget _viewBtn(VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
        decoration: BoxDecoration(color: const Color(0xFF0D9488), borderRadius: BorderRadius.circular(AppRadius.sm)),
        child: const Text('View',
            maxLines: 1, softWrap: false, overflow: TextOverflow.ellipsis,
            style: TextStyle(
                color: Colors.white, fontSize: 11, fontWeight: FontWeight.w800)),
      ),
    );
  }

  // Web secondary queue: 30px eye chip (`pe-view-btn`), not a history link.
  Widget _eyeBtn(VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 30,
        height: 30,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: const Color(0xFFEBF5FB),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: AppColors.primary.withValues(alpha: 0.25)),
        ),
        child: Icon(Icons.visibility_rounded, size: 16, color: AppColors.primary),
      ),
    );
  }

  Widget _secondaryActionBtn(SecondaryPatient p) {
    if (p.isDilated && p.primaryDoneAt != null) {
      final lockExpires =
          p.primaryDoneAt!.add(Duration(minutes: p.dilationLockMinutes));
      final remaining = lockExpires.difference(DateTime.now());
      if (remaining.inSeconds > 0) {
        final mm = remaining.inMinutes.toString().padLeft(2, '0');
        final ss = (remaining.inSeconds % 60).toString().padLeft(2, '0');
        return GestureDetector(
          onDoubleTap: () => _showOverrideDialog(p),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
            decoration: BoxDecoration(
              color: const Color(0xFFFFF3E0),
              borderRadius: BorderRadius.circular(AppRadius.sm),
              border: Border.all(color: const Color(0xFFFF9800)),
            ),
            child: Row(mainAxisSize: MainAxisSize.min, children: [
              const Icon(Icons.hourglass_bottom_rounded,
                  size: 12, color: Color(0xFFE65100)),
              const SizedBox(width: 4),
              Text('$mm:$ss',
                  style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFFE65100))),
            ]),
          ),
        );
      }
    }
    return _examBtn('Examine', () => _openSecondaryExam(p));
  }

  // ── Navigation / Dialogs ────────────────────────────────────────────────────

  Patient _minimalPatient(PrimaryPatient p) => Patient(
        id: p.id,
        patientCode: p.patientCode,
        firstName: p.patientName,
        lastName: '',
        fullName: p.patientName,
        age: p.age,
        doctor: p.doctorId != null
            ? PatientDoctor(id: p.doctorId!, name: '')
            : null,
      );

  void _openPrimaryExam(PrimaryPatient p) {
    Navigator.push(
      context,
      appRoute(PrimaryExamScreen(
        user: widget.user,
        hospital: widget.hospital,
        patient: _minimalPatient(p),
      )),
    ).then((refreshed) { if (refreshed == true) _load(); });
  }

  void _openSecondaryExam(SecondaryPatient p) {
    Navigator.push(
      context,
      appRoute(SecondaryExamScreen(
        user: widget.user,
        hospital: widget.hospital,
        patient: _minimalPatient(p),
      )),
    ).then((refreshed) { if (refreshed == true) _load(); });
  }

  void _openLatestExam(PrimaryPatient p) {
    showLatestExamView(context, _minimalPatient(p));
  }

  void _showOverrideDialog(SecondaryPatient p) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Dilation In Progress',
            style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
        content: Text(
            '${p.patientName} is currently dilating.\nOverride and examine now?'),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(ctx);
              _openSecondaryExam(p);
            },
            style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary, foregroundColor: Colors.white),
            child: const Text('Proceed'),
          ),
        ],
      ),
    );
  }
}

/// Soft repeating glow on the doctor who is currently open, and on that
/// same doctor in the list when another doctor's dashboard is selected.
class _SelectedPulse extends StatefulWidget {
  final bool active;
  final double radius;
  final Widget child;

  const _SelectedPulse({required this.active, required this.radius, required this.child});

  @override
  State<_SelectedPulse> createState() => _SelectedPulseState();
}

class _SelectedPulseState extends State<_SelectedPulse> with SingleTickerProviderStateMixin {
  late final AnimationController _c = AnimationController(vsync: this, duration: const Duration(milliseconds: 1100));

  @override
  void initState() {
    super.initState();
    if (widget.active) _c.repeat(reverse: true);
  }

  @override
  void didUpdateWidget(_SelectedPulse old) {
    super.didUpdateWidget(old);
    if (widget.active && !_c.isAnimating) _c.repeat(reverse: true);
    if (!widget.active && _c.isAnimating) {
      _c.stop();
      _c.value = 0;
    }
  }

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (!widget.active) return widget.child;
    return AnimatedBuilder(
      animation: _c,
      builder: (_, child) {
        final t = Curves.easeInOut.transform(_c.value);
        return DecoratedBox(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(widget.radius),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFF0F4F86).withValues(alpha: 0.10 + 0.22 * t),
                blurRadius: 3 + 7 * t,
                spreadRadius: 0,
              ),
            ],
          ),
          child: child,
        );
      },
      child: widget.child,
    );
  }
}
