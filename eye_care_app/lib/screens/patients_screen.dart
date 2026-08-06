import 'dart:async';
import 'package:flutter/material.dart';
import '../utils/app_route.dart';
import '../widgets/skeleton.dart';
import '../widgets/app_animations.dart';
import '../constants/permissions.dart';
import '../models/auth_models.dart';
import '../models/patient_models.dart';
import '../services/permission_service.dart';
import '../services/exam_masters_service.dart';
import '../services/patient_service.dart';
import 'opd_bill_screen.dart';
import 'patient_checkin_screen.dart';
import 'patient_form_screen.dart';
import 'patient_history_screen.dart';
import 'primary_exam_screen.dart';
import 'secondary_exam_screen.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';


// ─────────────────────────────────────────────────────────────────────────────
// Outer shell — owns only the AppBar + TabBar + FAB
// Each tab page manages its own data independently
// ─────────────────────────────────────────────────────────────────────────────

class PatientsScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final VoidCallback? onMenuTap;

  const PatientsScreen({
    super.key,
    required this.user,
    required this.hospital,
    this.onMenuTap,
  });

  @override
  State<PatientsScreen> createState() => _PatientsScreenState();
}

class _PatientsScreenState extends State<PatientsScreen>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;
  final _todayPageKey = GlobalKey<_PatientTabPageState>();
  final _allPageKey = GlobalKey<_PatientTabPageState>();

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    // Delay prewarm so it doesn't race with the patient list fetch on open.
    Future.delayed(const Duration(seconds: 3), () {
      if (mounted) ExamMastersService.instance.prewarm();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFEBF5FB),
      appBar: _buildAppBar(),
      body: TabBarView(
        controller: _tabController,
        children: [
          _PatientTabPage(
            key: _todayPageKey,
            showAll: false,
            user: widget.user,
            hospital: widget.hospital,
          ),
          _PatientTabPage(
            key: _allPageKey,
            showAll: true,
            user: widget.user,
            hospital: widget.hospital,
          ),
        ],
      ),
    );
  }

  AppBar _buildAppBar() {
    return AppBar(
      backgroundColor: AppColors.primary,
      elevation: 0,
      leading: IconButton(
        icon: const Icon(Icons.menu_rounded, color: Colors.white),
        onPressed: widget.onMenuTap,
      ),
      title: const Text(
        'Patients',
        style: TextStyle(
            color: Colors.white, fontSize: 20, fontWeight: FontWeight.w800),
      ),
      actions: [
        if (PermissionService.instance.can(Perm.opdPatientRegister))
          IconButton(
            icon: const Icon(Icons.person_add_alt_1_rounded, color: Colors.white),
            tooltip: 'Walk-in',
            onPressed: () => _openAdd(context, PatientFormMode.addWalkIn),
          ),
        if (PermissionService.instance.can(Perm.opdPatientRegisterPhone))
          IconButton(
            icon: const Icon(Icons.phone_in_talk_rounded, color: Colors.white),
            tooltip: 'Phone Appt',
            onPressed: () => _openAdd(context, PatientFormMode.addPhone),
          ),
      ],
      bottom: TabBar(
        controller: _tabController,
        labelColor: Colors.white,
        unselectedLabelColor: Colors.white54,
        indicatorColor: AppColors.teal,
        indicatorWeight: 3,
        labelStyle:
            const TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
        tabs: const [Tab(text: 'Today'), Tab(text: 'All Patients')],
      ),
    );
  }

  Future<void> _openAdd(BuildContext context, PatientFormMode mode) async {
    final result = await Navigator.of(context).push<bool>(
      appRoute(PatientFormScreen(
        mode: mode,
        user: widget.user,
        hospital: widget.hospital,
      )),
    );
    if (result == true && mounted) {
      _todayPageKey.currentState?.refresh();
      _allPageKey.currentState?.refresh();
    }
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Tab page — fully independent state, keeps itself alive via
// AutomaticKeepAliveClientMixin so scroll position + data is preserved
// ─────────────────────────────────────────────────────────────────────────────

class _PatientTabPage extends StatefulWidget {
  final bool showAll;
  final UserInfo user;
  final HospitalInfo hospital;

  const _PatientTabPage({
    super.key,
    required this.showAll,
    required this.user,
    required this.hospital,
  });

  @override
  State<_PatientTabPage> createState() => _PatientTabPageState();
}

class _PatientTabPageState extends State<_PatientTabPage>
    with AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;

  final _searchCtrl = TextEditingController();
  final _scrollCtrl = ScrollController();
  Timer? _debounce;
  String _search = '';

  List<Patient> _patients = [];
  PatientStats? _stats;
  PatientMeta? _meta;

  bool _isLoading = false;
  bool _refreshing = false;
  String? _error;
  int _currentPage = 1;

  @override
  void initState() {
    super.initState();
    _goToPage(1);
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _scrollCtrl.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  // ── Data loading ────────────────────────────────────────────────────────────

  Future<void> _goToPage(int page, {bool scrollTop = false}) async {
    if (_isLoading) return;

    // For initial page-1 load, show cached data instantly
    if (page == 1 && _patients.isEmpty && _search.isEmpty) {
      final cached = await PatientService.instance
          .getCachedPatients(showAll: widget.showAll);
      if (cached != null && mounted) {
        setState(() {
          _patients = cached.patients;
          _stats = cached.stats;
          _meta = cached.meta;
        });
      }
    }

    final hasData = _patients.isNotEmpty;
    setState(() {
      _isLoading = !hasData;
      _refreshing = hasData;
      _error = null;
      _currentPage = page;
    });
    try {
      final result = await PatientService.instance.fetchPatients(
        showAll: widget.showAll,
        search: _search,
        page: page,
      );
      if (!mounted) return;
      setState(() {
        _patients = result.patients;
        _stats = result.stats;
        _meta = result.meta;
      });
      if (scrollTop && _scrollCtrl.hasClients) {
        _scrollCtrl.animateTo(0,
            duration: const Duration(milliseconds: 300),
            curve: Curves.easeOut);
      }
    } catch (e) {
      if (mounted && _patients.isEmpty) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() { _isLoading = false; _refreshing = false; });
    }
  }

  void refresh() => _goToPage(1);

  void _onSearchChanged(String val) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () {
      if (_search != val) {
        setState(() => _search = val);
        _goToPage(1);
      }
    });
  }

  // ── Navigation ──────────────────────────────────────────────────────────────

  Future<void> _openEdit(Patient p) async {
    final result = await Navigator.of(context).push<bool>(
      appRoute(PatientFormScreen(
        mode: PatientFormMode.edit,
        patient: p,
        user: widget.user,
        hospital: widget.hospital,
      )),
    );
    if (result == true && mounted) _goToPage(_currentPage);
  }

  void _viewDetail(Patient p) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _PatientDetailSheet(patient: p, onEdit: () {
        Navigator.pop(context);
        _openEdit(p);
      }),
    );
  }

  Future<void> _deletePatient(Patient p) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Patient?'),
        content: Text(
            'Delete ${p.fullName}? This cannot be undone.'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancel')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: TextButton.styleFrom(
                foregroundColor: const Color(0xFFDC3545)),
            child: const Text('Delete'),
          ),
        ],
      ),
    );
    if (confirm != true || !mounted) return;

    try {
      await PatientService.instance.deletePatient(p.id);
      if (mounted) {
        showAppSnackBar(context, 'Patient deleted.', isSuccess: true);
        _goToPage(_currentPage);
      }
    } catch (e) {
      if (mounted) {
        showAppSnackBar(context, e.toString(), isError: true);
      }
    }
  }

  Future<void> _openPrimaryExam(Patient p) async {
    final result = await Navigator.of(context).push<bool>(
      appRoute(PrimaryExamScreen(
        user: widget.user,
        hospital: widget.hospital,
        patient: p,
      )),
    );
    if (result == true && mounted) { _goToPage(_currentPage); }
  }

  Future<void> _openSecondaryExam(Patient p) async {
    final result = await Navigator.of(context).push<bool>(
      appRoute(SecondaryExamScreen(
        user: widget.user,
        hospital: widget.hospital,
        patient: p,
      )),
    );
    if (result == true && mounted) { _goToPage(_currentPage); }
  }

  void _openHistory(Patient p) {
    Navigator.of(context).push(
      appRoute(PatientHistoryScreen(
        user: widget.user,
        hospital: widget.hospital,
        patient: p,
      )),
    );
  }

  void _openPrint(Patient p) {
    Navigator.of(context).push(
      appRoute(OpdBillScreen(
        user: widget.user,
        hospital: widget.hospital,
        patient: p,
      )),
    );
  }

  Future<void> _openCheckIn(Patient p) async {
    final result = await Navigator.of(context).push<bool>(
      appRoute(PatientCheckinScreen(
        user: widget.user,
        hospital: widget.hospital,
        patient: p,
      )),
    );
    if (result == true && mounted) { _goToPage(_currentPage); }
  }

  // ── Build ────────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    super.build(context); // required for AutomaticKeepAliveClientMixin

    return Stack(
      children: [
        RefreshIndicator(
          color: AppColors.primary,
          onRefresh: () => _goToPage(1),
          child: CustomScrollView(
            controller: _scrollCtrl,
            // ignore: deprecated_member_use
            cacheExtent: 600,
            physics: const BouncingScrollPhysics(
                parent: AlwaysScrollableScrollPhysics()),
            slivers: [
              SliverToBoxAdapter(child: _buildStatCards()),
              SliverToBoxAdapter(child: _buildSearchBar()),
              ..._buildContent(),
            ],
          ),
        ),
        if (_refreshing)
          Positioned(
            top: 0, left: 0, right: 0,
            child: LinearProgressIndicator(
              minHeight: 2,
              backgroundColor: Colors.transparent,
              color: AppColors.primary,
            ),
          ),
      ],
    );
  }

  List<Widget> _buildContent() {
    if (_isLoading) {
      return [const SliverToBoxAdapter(child: AppSkeletonList())];
    }
    if (_error != null) {
      return [SliverFillRemaining(hasScrollBody: false, child: _buildError())];
    }
    if (_patients.isEmpty) {
      return [SliverFillRemaining(hasScrollBody: false, child: _buildEmpty())];
    }
    return [
      SliverPadding(
        padding: const EdgeInsets.fromLTRB(12, 4, 12, 4),
        sliver: SliverList(
          delegate: SliverChildBuilderDelegate(
            (_, i) {
              final p = _patients[i];
              return AnimatedListItem(
                index: i,
                child: RepaintBoundary(
                  child: _PatientCard(
                    key: ValueKey(p.id),
                    patient: p,
                    onView: () => _viewDetail(p),
                    onEdit: () => _openEdit(p),
                    onDelete: () => _deletePatient(p),
                    onCheckIn: () => _openCheckIn(p),
                    onPrimaryExam: () => _openPrimaryExam(p),
                    onSecondaryExam: () => _openSecondaryExam(p),
                    onHistory: () => _openHistory(p),
                    onPrint: () => _openPrint(p),
                  ),
                ),
              );
            },
            childCount: _patients.length,
            addAutomaticKeepAlives: false,
            addRepaintBoundaries: false,
          ),
        ),
      ),
      SliverToBoxAdapter(child: _buildPaginationBar()),
    ];
  }

  Widget _buildPaginationBar() {
    final meta = _meta;
    if (meta == null) return const SizedBox(height: 110);

    final lastPage = meta.lastPage;
    final total = meta.total;
    final from = total == 0 ? 0 : ((_currentPage - 1) * meta.perPage) + 1;
    final to = (_currentPage * meta.perPage).clamp(0, total);

    // Build page number list — show at most 5 page buttons around current page
    final List<int> pages = [];
    final start = (_currentPage - 2).clamp(1, lastPage);
    final end = (_currentPage + 2).clamp(1, lastPage);
    for (int i = start; i <= end; i++) { pages.add(i); }

    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 6, 12, 110),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
              color: AppColors.primaryA12),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // ── Info row ─────────────────────────────────────────
            Row(
              children: [
                Text(
                  '$total patient${total == 1 ? '' : 's'}',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: AppColors.primary,
                  ),
                ),
                if (total > 0) ...[
                  Text(
                    '  ·  $from–$to',
                    style: TextStyle(
                      fontSize: 12,
                      color: AppColors.primaryA45,
                    ),
                  ),
                ],
                const Spacer(),
                if (lastPage > 1)
                  Text(
                    'Page $_currentPage / $lastPage',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: AppColors.primaryA50,
                    ),
                  ),
              ],
            ),

            if (lastPage > 1) ...[
              const SizedBox(height: 10),

              // ── Page controls ─────────────────────────────────
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  // ← Prev
                  _PagArrow(
                    icon: Icons.chevron_left_rounded,
                    enabled: _currentPage > 1,
                    onTap: () => _goToPage(_currentPage - 1, scrollTop: true),
                  ),
                  const SizedBox(width: 6),

                  // First + ellipsis
                  if (start > 1) ...[
                    _PagNum(page: 1, active: _currentPage == 1,
                        onTap: () => _goToPage(1, scrollTop: true)),
                    if (start > 2)
                      _PagDot(),
                  ],

                  // Middle pages
                  for (final p in pages) ...[
                    _PagNum(
                      page: p,
                      active: p == _currentPage,
                      onTap: () => _goToPage(p, scrollTop: true),
                    ),
                  ],

                  // Ellipsis + last
                  if (end < lastPage) ...[
                    if (end < lastPage - 1)
                      _PagDot(),
                    _PagNum(
                        page: lastPage,
                        active: _currentPage == lastPage,
                        onTap: () => _goToPage(lastPage, scrollTop: true)),
                  ],

                  const SizedBox(width: 6),
                  // → Next
                  _PagArrow(
                    icon: Icons.chevron_right_rounded,
                    enabled: _currentPage < lastPage,
                    onTap: () => _goToPage(_currentPage + 1, scrollTop: true),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildStatCards() {
    final s = _stats;
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 0),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              Expanded(
                child: _StatCard(
                  label: 'Total',
                  value: s?.total ?? 0,
                  color: AppColors.primary,
                  iconBg: AppColors.primaryA12,
                  icon: Icons.groups_rounded,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _StatCard(
                  label: 'Waiting',
                  value: s?.waiting ?? 0,
                  color: const Color(0xFFE67E22),
                  iconBg: AppColors.orangeA12,
                  icon: Icons.hourglass_top_rounded,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _StatCard(
                  label: 'Primary',
                  value: s?.primaryDone ?? 0,
                  color: const Color(0xFF006497),
                  iconBg: AppColors.blueA12,
                  icon: Icons.assignment_turned_in_outlined,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _StatCard(
                  label: 'Completed',
                  value: s?.completed ?? 0,
                  color: const Color(0xFF27AE60),
                  iconBg: AppColors.greenA12,
                  icon: Icons.task_alt_rounded,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSearchBar() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 6),
      child: TextField(
        controller: _searchCtrl,
        onChanged: _onSearchChanged,
        decoration: InputDecoration(
          hintText: 'Search by name, MRD or contact...',
          hintStyle: TextStyle(
              fontSize: 13,
              color: AppColors.primaryA45),
          prefixIcon: Icon(Icons.search_rounded,
              color: AppColors.primaryA55,
              size: 20),
          suffixIcon: _searchCtrl.text.isNotEmpty
              ? IconButton(
                  icon: Icon(Icons.close_rounded,
                      color: AppColors.primaryA55,
                      size: 18),
                  onPressed: () {
                    _searchCtrl.clear();
                    _onSearchChanged('');
                  },
                )
              : null,
          filled: true,
          fillColor: Colors.white,
          contentPadding:
              const EdgeInsets.symmetric(vertical: 10, horizontal: 14),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppRadius.md),
            borderSide: BorderSide(
                color: AppColors.primaryA18),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppRadius.md),
            borderSide: BorderSide(
                color: AppColors.primaryA18),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppRadius.md),
            borderSide: BorderSide(color: AppColors.primary, width: 1.5),
          ),
        ),
      ),
    );
  }

  Widget _buildEmpty() {
    final msg = _search.isNotEmpty
        ? 'No results match your search.'
        : widget.showAll
            ? 'No patients registered yet.'
            : 'No patients registered today.';
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(40),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.inbox_rounded,
                size: 64,
                color: AppColors.primaryA22),
            const SizedBox(height: 14),
            Text('No patients found',
                style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: AppColors.primaryA70)),
            const SizedBox(height: 6),
            Text(msg,
                textAlign: TextAlign.center,
                style: TextStyle(
                    fontSize: 13,
                    color: AppColors.primaryA45)),
          ],
        ),
      ),
    );
  }

  Widget _buildError() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.wifi_off_rounded,
                size: 48, color: Color(0xFFDC3545)),
            const SizedBox(height: 12),
            Text(_error ?? 'Something went wrong.',
                textAlign: TextAlign.center,
                style: const TextStyle(color: Color(0xFF666666))),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: () => _goToPage(_currentPage),
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('Retry'),
              style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary),
            ),
          ],
        ),
      ),
    );
  }

}

// ─── Stat Card ────────────────────────────────────────────────────────────────

class _StatCard extends StatelessWidget {
  final String label;
  final int value;
  final Color color;
  final Color iconBg;
  final IconData icon;

  const _StatCard({
    required this.label,
    required this.value,
    required this.color,
    required this.iconBg,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.xl),
        border: Border.all(
            color: AppColors.primaryA12),
        boxShadow: [
          BoxShadow(
            color: AppColors.primaryA06,
            blurRadius: 20,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
                color: iconBg, borderRadius: BorderRadius.circular(13)),
            child: Icon(icon, color: color, size: 22),
          ),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(value.toString(),
                  style: TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.w900,
                      color: color,
                      height: 1.1)),
              const SizedBox(height: 2),
              Text(label.toUpperCase(),
                  style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 0.6,
                      color: AppColors.primaryA55)),
            ],
          ),
        ],
      ),
    );
  }
}

// ─── Patient Card — StatelessWidget (no rebuilds during scroll) ───────────────

class _PatientCard extends StatelessWidget {
  final Patient patient;
  final VoidCallback onView;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final VoidCallback onCheckIn;
  final VoidCallback onPrimaryExam;
  final VoidCallback onSecondaryExam;
  final VoidCallback onHistory;
  final VoidCallback onPrint;

  const _PatientCard({
    super.key,
    required this.patient,
    required this.onView,
    required this.onEdit,
    required this.onDelete,
    required this.onCheckIn,
    required this.onPrimaryExam,
    required this.onSecondaryExam,
    required this.onHistory,
    required this.onPrint,
  });

  // Static decorations — allocated once, shared across all card instances.
  static final _cardDeco = BoxDecoration(
    color: Colors.white,
    borderRadius: BorderRadius.circular(AppRadius.lg),
    border: Border.all(color: AppColors.primaryA10),
  );
  static final _avatarDeco =
      BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.circular(10));
  static final _mrdDeco =
      BoxDecoration(color: AppColors.primaryA07, borderRadius: BorderRadius.circular(6));
  static final _typeDeco = BoxDecoration(
    border: Border.all(color: AppColors.primaryA28),
    borderRadius: BorderRadius.circular(AppRadius.xl),
  );
  static final _completedDeco =
      BoxDecoration(color: AppColors.tealA13, borderRadius: BorderRadius.circular(AppRadius.xl));
  static final _primaryDoneDeco =
      BoxDecoration(color: AppColors.primaryA13, borderRadius: BorderRadius.circular(AppRadius.xl));
  static final _waitingDeco =
      BoxDecoration(color: AppColors.orangeA13, borderRadius: BorderRadius.circular(AppRadius.xl));
  static final _vDiv = Container(
    width: 1,
    height: 22,
    color: AppColors.primaryA10,
    margin: const EdgeInsets.symmetric(horizontal: 2),
  );

  @override
  Widget build(BuildContext context) {
    final p = patient;
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: _cardDeco,
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Header: avatar + name + status ─────────────────
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _avatar(p.firstName),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(p.fullName,
                          style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              color: AppColors.primary)),
                      const SizedBox(height: 3),
                      Text(
                        _subtitle(p),
                        style: TextStyle(fontSize: 12, color: AppColors.primaryA58),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 6),
                _statusBadge(p.status),
              ],
            ),

            const SizedBox(height: 10),

            // ── Meta: doctor + MRD + type + fee ────────────────
            Row(
              children: [
                if (p.doctor != null) ...[
                  Icon(Icons.person_outline_rounded,
                      size: 13, color: AppColors.primaryA50),
                  const SizedBox(width: 3),
                  Flexible(
                    child: Text(p.doctor!.name,
                        style: TextStyle(fontSize: 12, color: AppColors.primaryA60),
                        overflow: TextOverflow.ellipsis),
                  ),
                  const SizedBox(width: 8),
                ],
                _mrdChip(p.patientCode),
                const SizedBox(width: 6),
                _typeBadge(p.type),
                const Spacer(),
                if (p.caseFee != null)
                  Text('₹${_fmt(p.caseFee!)}',
                      style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w800,
                          color: AppColors.primary)),
              ],
            ),

            const SizedBox(height: 10),
            Container(height: 1, color: AppColors.primaryA08),
            const SizedBox(height: 8),

            // ── Actions ─────────────────────────────────────────
            Builder(builder: (context) {
              final perm          = PermissionService.instance;
              final showPrimary   = perm.can(Perm.opdExamPrimary);
              final showSecondary = perm.can(Perm.opdExamSecondary);
              final showHistory   = perm.can(Perm.opdExamHistory);
              final showPrint     = perm.can(Perm.opdBillPrint);
              final showEdit      = perm.can(Perm.opdPatientEdit);
              final showDelete    = perm.can(Perm.opdPatientDelete);
              final showCheckIn   = perm.can(Perm.opdPatientRegister);

              // Secondary exam widget — same logic, referenced twice below.
              Widget secondaryWidget() => p.unlockTimeMs != null
                  // Dilation timer is isolated in its own StatefulWidget so
                  // only it rebuilds every second — the card itself stays stable.
                  ? _DilationTimer(
                      unlockTimeMs: p.unlockTimeMs!,
                      onExpired: _ActionBtn(
                        icon: Icons.remove_red_eye_outlined,
                        bg: p.secondaryDoneAt != null
                            ? AppColors.tealA10
                            : p.primaryDoneAt == null
                                ? AppColors.greyA10
                                : AppColors.purpleA12,
                        fg: p.secondaryDoneAt != null
                            ? AppColors.tealDark
                            : p.primaryDoneAt == null
                                ? Colors.grey
                                : AppColors.purple,
                        onTap: (p.secondaryDoneAt != null || p.primaryDoneAt == null)
                            ? null
                            : onSecondaryExam,
                        tooltip: 'Secondary Exam',
                      ),
                    )
                  : _ActionBtn(
                      icon: Icons.remove_red_eye_outlined,
                      bg: p.secondaryDoneAt != null
                          ? AppColors.tealA10
                          : p.primaryDoneAt == null
                              ? AppColors.greyA10
                              : AppColors.purpleA12,
                      fg: p.secondaryDoneAt != null
                          ? AppColors.tealDark
                          : p.primaryDoneAt == null
                              ? Colors.grey
                              : AppColors.purple,
                      onTap: (p.secondaryDoneAt != null || p.primaryDoneAt == null)
                          ? null
                          : onSecondaryExam,
                      tooltip: 'Secondary Exam',
                    );

              return Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  _ActionBtn(
                    icon: Icons.info_outline_rounded,
                    bg: AppColors.tealA12,
                    fg: AppColors.tealDark,
                    onTap: onView,
                    tooltip: 'View',
                  ),
                  if (showPrimary || showSecondary) _vDiv,
                  if (showPrimary)
                    _ActionBtn(
                      icon: Icons.assignment_outlined,
                      bg: p.primaryDoneAt != null ? AppColors.tealA10 : AppColors.primaryA10,
                      fg: p.primaryDoneAt != null ? AppColors.tealDark : AppColors.primary,
                      onTap: p.primaryDoneAt != null ? null : onPrimaryExam,
                      tooltip: 'Primary Exam',
                    ),
                  if (showSecondary) secondaryWidget(),
                  if (showHistory)
                    _ActionBtn(
                      icon: Icons.history_rounded,
                      bg: Colors.transparent,
                      fg: AppColors.primaryA50,
                      onTap: onHistory,
                      tooltip: 'History',
                    ),
                  if (showPrint)
                    _ActionBtn(
                      icon: Icons.print_outlined,
                      bg: Colors.transparent,
                      fg: AppColors.primaryA50,
                      onTap: onPrint,
                      tooltip: 'Print',
                    ),
                  if (showEdit)
                    _ActionBtn(
                      icon: Icons.edit_outlined,
                      bg: Colors.transparent,
                      fg: AppColors.primaryA50,
                      onTap: onEdit,
                      tooltip: 'Edit',
                    ),
                  if (showDelete)
                    _ActionBtn(
                      icon: Icons.delete_outline_rounded,
                      bg: Colors.transparent,
                      fg: AppColors.redA70,
                      onTap: onDelete,
                      tooltip: 'Delete',
                    ),
                  if (showCheckIn && p.type == 'phone' && p.caseId == null) ...[
                    _vDiv,
                    _ActionBtn(
                      icon: Icons.how_to_reg_rounded,
                      bg: AppColors.greenA12,
                      fg: AppColors.green,
                      onTap: onCheckIn,
                      tooltip: 'Check-in',
                    ),
                  ],
                ],
              );
            }),
          ],
        ),
      ),
    );
  }

  static String _subtitle(Patient p) => [
        if (p.age != null) '${p.age}y',
        if (p.gender != null) p.genderDisplay,
        if (p.contactNo != null) p.contactNo!,
      ].join(' · ');

  Widget _avatar(String name) => Container(
        width: 36,
        height: 36,
        decoration: _avatarDeco,
        alignment: Alignment.center,
        child: Text(
          name.isNotEmpty ? name[0].toUpperCase() : '?',
          style: const TextStyle(
              color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16),
        ),
      );

  Widget _statusBadge(PatientStatus status) {
    final (label, icon, deco, fg) = switch (status) {
      PatientStatus.completed => (
          'Completed',
          Icons.check_circle_outline_rounded,
          _completedDeco,
          AppColors.tealDark,
        ),
      PatientStatus.primaryDone => (
          'Primary Done',
          Icons.assignment_turned_in_outlined,
          _primaryDoneDeco,
          AppColors.primary,
        ),
      PatientStatus.waiting => (
          'Waiting',
          Icons.hourglass_top_rounded,
          _waitingDeco,
          AppColors.orange,
        ),
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
      decoration: deco,
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 11, color: fg),
          const SizedBox(width: 3),
          Text(label,
              style: TextStyle(
                  fontSize: 10, fontWeight: FontWeight.w700, color: fg)),
        ],
      ),
    );
  }

  Widget _mrdChip(String code) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
        decoration: _mrdDeco,
        child: Text(code,
            style: TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w700,
                fontFamily: 'monospace',
                color: AppColors.primary)),
      );

  Widget _typeBadge(String type) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
        decoration: _typeDeco,
        child: Text(
          type == 'walkin' ? 'Walk-in' : 'Phone',
          style:
              TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: AppColors.primaryA70),
        ),
      );

  static String _fmt(double v) =>
      v == v.toInt() ? v.toInt().toString() : v.toStringAsFixed(2);
}

// ─── Dilation countdown — only this widget rebuilds every second ──────────────

class _DilationTimer extends StatefulWidget {
  final int unlockTimeMs;
  final Widget onExpired;
  const _DilationTimer({required this.unlockTimeMs, required this.onExpired});

  @override
  State<_DilationTimer> createState() => _DilationTimerState();
}

class _DilationTimerState extends State<_DilationTimer> {
  Timer? _timer;
  String _text = '';
  bool _expired = false;

  @override
  void initState() {
    super.initState();
    _tick();
    _timer = Timer.periodic(const Duration(seconds: 1), (_) => _tick());
  }

  void _tick() {
    final r = widget.unlockTimeMs - DateTime.now().millisecondsSinceEpoch;
    if (r <= 0) {
      _timer?.cancel();
      if (mounted) setState(() => _expired = true);
    } else {
      final mins = r ~/ 60000;
      final secs = (r % 60000) ~/ 1000;
      final txt  = '$mins:${secs.toString().padLeft(2, '0')}';
      if (mounted && txt != _text) setState(() => _text = txt);
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) =>
      _expired ? widget.onExpired : _DilationBtn(timerText: _text);
}

// ─── Small reusable action button ────────────────────────────────────────────

class _ActionBtn extends StatelessWidget {
  final IconData icon;
  final Color bg;
  final Color fg;
  final VoidCallback? onTap;
  final String tooltip;

  const _ActionBtn({
    required this.icon,
    required this.bg,
    required this.fg,
    required this.onTap,
    required this.tooltip,
  });

  @override
  Widget build(BuildContext context) {
    final effectiveFg = onTap == null ? fg.withValues(alpha: 0.35) : fg;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 34,
        height: 34,
        margin: const EdgeInsets.symmetric(horizontal: 1),
        decoration:
            BoxDecoration(color: bg, borderRadius: BorderRadius.circular(AppRadius.sm)),
        child: Icon(icon, size: 18, color: effectiveFg),
      ),
    );
  }
}

// ─── Dilation timer ───────────────────────────────────────────────────────────

class _DilationBtn extends StatelessWidget {
  final String timerText;
  const _DilationBtn({required this.timerText});

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 34,
      margin: const EdgeInsets.symmetric(horizontal: 1),
      padding: const EdgeInsets.symmetric(horizontal: 8),
      decoration: BoxDecoration(
        color: AppColors.orangeA14,
        borderRadius: BorderRadius.circular(AppRadius.sm),
      ),
      alignment: Alignment.center,
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.timer_outlined,
              size: 13, color: Color(0xFFE67E22)),
          const SizedBox(width: 3),
          Text(timerText,
              style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFFE67E22))),
        ],
      ),
    );
  }
}

// ─── Pagination arrow (prev / next) ──────────────────────────────────────────

class _PagArrow extends StatelessWidget {
  final IconData icon;
  final bool enabled;
  final VoidCallback onTap;
  const _PagArrow({required this.icon, required this.enabled, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final color = enabled
        ? AppColors.primary
        : AppColors.primaryA22;
    return GestureDetector(
      onTap: enabled ? onTap : null,
      child: Container(
        width: 30,
        height: 30,
        decoration: BoxDecoration(
          color: enabled
              ? AppColors.primaryA07
              : Colors.transparent,
          borderRadius: BorderRadius.circular(AppRadius.sm),
          border: Border.all(
              color: AppColors.primaryA15),
        ),
        child: Icon(icon, size: 18, color: color),
      ),
    );
  }
}

// ─── Pagination page number chip ─────────────────────────────────────────────

class _PagNum extends StatelessWidget {
  final int page;
  final bool active;
  final VoidCallback onTap;
  const _PagNum({required this.page, required this.active, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: active ? null : onTap,
      child: Container(
        constraints: const BoxConstraints(minWidth: 30),
        height: 30,
        padding: const EdgeInsets.symmetric(horizontal: 8),
        margin: const EdgeInsets.symmetric(horizontal: 2),
        decoration: BoxDecoration(
          color: active
              ? AppColors.primary
              : Colors.transparent,
          borderRadius: BorderRadius.circular(AppRadius.sm),
          border: Border.all(
              color: active
                  ? AppColors.primary
                  : AppColors.primaryA18),
        ),
        alignment: Alignment.center,
        child: Text(
          '$page',
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w700,
            color: active ? Colors.white : AppColors.primary,
          ),
        ),
      ),
    );
  }
}

// ─── Pagination ellipsis dot ──────────────────────────────────────────────────

class _PagDot extends StatelessWidget {
  const _PagDot();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 3),
      child: Text(
        '···',
        style: TextStyle(
          fontSize: 11,
          letterSpacing: 1,
          color: AppColors.primaryA35,
        ),
      ),
    );
  }
}

// ─── Patient Detail Bottom Sheet ─────────────────────────────────────────────

class _PatientDetailSheet extends StatelessWidget {
  final Patient patient;
  final VoidCallback onEdit;

  const _PatientDetailSheet({
    required this.patient,
    required this.onEdit,
  });

  @override
  Widget build(BuildContext context) {
    final p = patient;
    return DraggableScrollableSheet(
      initialChildSize: 0.65,
      minChildSize: 0.4,
      maxChildSize: 0.95,
      expand: false,
      builder: (ctx, ctrl) => Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.xl)),
        ),
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.only(top: 10),
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                    color: Colors.grey.shade300,
                    borderRadius: BorderRadius.circular(2)),
              ),
            ),
            Expanded(
              child: SingleChildScrollView(
                controller: ctrl,
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _header(p),
                    const SizedBox(height: 14),
                    _apptBar(p),
                    const SizedBox(height: 14),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(child: _personalCard(p)),
                        const SizedBox(width: 10),
                        Expanded(child: _contactCard(p)),
                      ],
                    ),
                    const SizedBox(height: 14),
                    _examCard(p),
                    const SizedBox(height: 20),
                    Row(
                      children: [
                        if (PermissionService.instance.can(Perm.opdPatientEdit)) ...[
                          Expanded(
                            child: ElevatedButton.icon(
                              onPressed: onEdit,
                              icon: const Icon(Icons.edit_outlined, size: 16),
                              label: const Text('Edit Patient'),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppColors.primary,
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(vertical: 12),
                                shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(10)),
                              ),
                            ),
                          ),
                          const SizedBox(width: 10),
                        ],
                        Expanded(
                          child: OutlinedButton(
                            onPressed: () => Navigator.pop(context),
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 12),
                              side: BorderSide(
                                  color: AppColors.primaryA35),
                              shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(10)),
                            ),
                            child: const Text('Close'),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _header(Patient p) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 52,
          height: 52,
          decoration: BoxDecoration(
              color: AppColors.primary, borderRadius: BorderRadius.circular(14)),
          alignment: Alignment.center,
          child: Text(
            p.firstName.isNotEmpty ? p.firstName[0].toUpperCase() : '?',
            style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w900,
                fontSize: 22),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(p.fullName,
                  style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w800,
                      color: AppColors.primary)),
              const SizedBox(height: 4),
              Row(children: [
                _chip(p.patientCode,
                    bg: AppColors.primaryA08, fg: AppColors.primary),
                const SizedBox(width: 6),
                _statusChip(p.status),
              ]),
              if (p.age != null || p.gender != null) ...[
                const SizedBox(height: 4),
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                      color: AppColors.primaryA07,
                      borderRadius: BorderRadius.circular(AppRadius.xl)),
                  child: Text(
                    [
                      if (p.age != null) '${p.age}y',
                      if (p.gender != null) p.genderDisplay,
                    ].join(' · '),
                    style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: AppColors.primary),
                  ),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }

  Widget _apptBar(Patient p) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 10),
      decoration: BoxDecoration(
          color: AppColors.primaryA05,
          borderRadius: BorderRadius.circular(AppRadius.md)),
      child: Row(
        children: [
          _apptItem(Icons.calendar_today_outlined, 'Appointment',
              p.appointmentDate ?? '—'),
          _apptDiv(),
          _apptItem(Icons.person_outline_rounded, 'Doctor',
              p.doctor?.name ?? '—'),
          _apptDiv(),
          _apptItem(Icons.category_outlined, 'Case',
              p.caseType?.caseType ?? '—'),
          _apptDiv(),
          _apptItem(Icons.currency_rupee_rounded, 'Fee',
              p.caseFee != null ? '₹${p.caseFee!.toInt()}' : '—'),
        ],
      ),
    );
  }

  Widget _apptItem(IconData icon, String label, String value) =>
      Expanded(
        child: Column(
          children: [
            Icon(icon, size: 14, color: AppColors.primaryA55),
            const SizedBox(height: 3),
            Text(label,
                style: TextStyle(
                    fontSize: 9,
                    fontWeight: FontWeight.w600,
                    color: AppColors.primaryA55)),
            const SizedBox(height: 2),
            Text(value,
                textAlign: TextAlign.center,
                style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: AppColors.primary)),
          ],
        ),
      );

  Widget _apptDiv() => Container(
      width: 1, height: 36, color: AppColors.primaryA12);

  Widget _personalCard(Patient p) => _infoCard(
        bg: AppColors.primaryA06,
        title: 'Personal',
        items: [
          _InfoRow(Icons.work_outline_rounded, 'Occupation',
              p.occupation ?? '—'),
          _InfoRow(Icons.person_pin_outlined, 'Referrer',
              p.referrer?.name ?? '—'),
          _InfoRow(Icons.schedule_outlined, 'Registered',
              _fmtDate(p.createdAt)),
        ],
      );

  Widget _contactCard(Patient p) => _infoCard(
        bg: AppColors.tealA06,
        title: 'Contact',
        items: [
          _InfoRow(Icons.phone_outlined, 'Phone', p.contactNo ?? '—'),
          _InfoRow(Icons.chat_outlined, 'WhatsApp', p.whatsappNo ?? '—'),
          _InfoRow(Icons.location_on_outlined, 'Location',
              p.location?.display ?? '—'),
        ],
      );

  Widget _infoCard(
      {required Color bg,
      required String title,
      required List<_InfoRow> items}) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
          color: bg, borderRadius: BorderRadius.circular(AppRadius.md)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title.toUpperCase(),
              style: TextStyle(
                  fontSize: 9,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 1.2,
                  color: AppColors.primaryA60)),
          const SizedBox(height: 8),
          ...items.map((r) => Padding(
                padding: const EdgeInsets.only(bottom: 7),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(r.icon,
                        size: 13,
                        color: AppColors.primaryA55),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(r.label,
                              style: TextStyle(
                                  fontSize: 9,
                                  color: AppColors.primaryA50)),
                          Text(r.value,
                              style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                  color: AppColors.primary)),
                        ],
                      ),
                    ),
                  ],
                ),
              )),
        ],
      ),
    );
  }

  Widget _examCard(Patient p) => Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: AppColors.primaryA04,
          borderRadius: BorderRadius.circular(AppRadius.md),
          border: Border.all(color: AppColors.primaryA10),
        ),
        child: Row(
          children: [
            Expanded(
                child: _examStatus(
                    'PRIMARY', Icons.assignment_outlined, p.primaryDoneAt)),
            Container(
                width: 1,
                height: 60,
                color: AppColors.primaryA12),
            Expanded(
                child: _examStatus('SECONDARY',
                    Icons.remove_red_eye_outlined, p.secondaryDoneAt)),
          ],
        ),
      );

  Widget _examStatus(String title, IconData icon, DateTime? doneAt) {
    final done = doneAt != null;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12),
      child: Column(
        children: [
          Icon(
            done ? Icons.check_circle_rounded : Icons.radio_button_unchecked,
            color: done ? AppColors.teal : Colors.grey.shade400,
            size: 28,
          ),
          const SizedBox(height: 4),
          Text(title,
              style: TextStyle(
                  fontSize: 9,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 1,
                  color: AppColors.primaryA60)),
          const SizedBox(height: 2),
          Text(done ? _fmtDate(doneAt) : 'Pending',
              textAlign: TextAlign.center,
              style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.w600,
                  color: done ? AppColors.teal : Colors.grey.shade400)),
        ],
      ),
    );
  }

  Widget _chip(String text, {required Color bg, required Color fg}) =>
      Container(
        padding:
            const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
        decoration: BoxDecoration(
            color: bg, borderRadius: BorderRadius.circular(6)),
        child: Text(text,
            style: TextStyle(
                fontSize: 10, fontWeight: FontWeight.w700, color: fg)),
      );

  Widget _statusChip(PatientStatus status) {
    final (label, bg, fg) = switch (status) {
      PatientStatus.completed => (
          'Completed',
          AppColors.tealA13,
          const Color(0xFF0E9E82),
        ),
      PatientStatus.primaryDone => (
          'Primary Done',
          AppColors.primaryA13,
          AppColors.primary,
        ),
      PatientStatus.waiting => (
          'Waiting',
          AppColors.orangeA13,
          const Color(0xFFE67E22),
        ),
    };
    return _chip(label, bg: bg, fg: fg);
  }

  String _fmtDate(DateTime? dt) {
    if (dt == null) return '—';
    const m = [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ];
    return '${dt.day} ${m[dt.month - 1]} ${dt.year}';
  }
}

class _InfoRow {
  final IconData icon;
  final String label;
  final String value;
  const _InfoRow(this.icon, this.label, this.value);
}
