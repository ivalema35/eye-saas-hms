import 'dart:async';
import 'package:flutter/material.dart';
import '../widgets/app_empty_state.dart';
import '../utils/app_route.dart';
import '../models/auth_models.dart';
import '../models/doctor_dashboard_models.dart';
import '../models/patient_models.dart';
import '../services/doctor_dashboard_service.dart';
import 'doctor_ot_list_screen.dart';
import 'patient_history_screen.dart';
import 'primary_exam_screen.dart';
import 'reports_screen.dart';
import 'secondary_exam_screen.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';

class DoctorDashboardScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final VoidCallback? onMenuTap;

  const DoctorDashboardScreen({
    super.key,
    required this.user,
    required this.hospital,
    this.onMenuTap,
  });

  @override
  State<DoctorDashboardScreen> createState() => _DoctorDashboardScreenState();
}

class _DoctorDashboardScreenState extends State<DoctorDashboardScreen> {
  DoctorDashboardData? _data;
  bool _loading = true;
  String? _error;
  int? _viewingDoctorId;
  Timer? _tickTimer;

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

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final data = await DoctorDashboardService.instance.fetchDashboard(
        viewDoctorId: _viewingDoctorId,
      );
      if (mounted) setState(() { _data = data; _loading = false; });
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
          ? Center(child: CircularProgressIndicator(color: AppColors.primary))
          : _error != null && _data == null
              ? _buildError()
              : RefreshIndicator(
                  onRefresh: _load,
                  color: AppColors.primary,
                  child: _buildBody(),
                ),
    );
  }

  PreferredSizeWidget _buildAppBar() {
    final subtitle = (_viewingDoctorId != null && _data?.viewingDoctor != null)
        ? 'Viewing: ${_data!.viewingDoctor!.name}'
        : widget.user.name;

    return AppBar(
      backgroundColor: AppColors.primary,
      elevation: 0,
      leading: IconButton(
        icon: const Icon(Icons.menu_rounded, color: Colors.white),
        onPressed: widget.onMenuTap,
      ),
      title: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        const Text('Dashboard',
            style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 17)),
        Text(subtitle,
            style: const TextStyle(color: Colors.white70, fontSize: 11)),
      ]),
      actions: [
        IconButton(
          icon: const Icon(Icons.refresh_rounded, color: Colors.white),
          onPressed: _load,
          tooltip: 'Refresh',
        ),
      ],
    );
  }

  Widget _buildError() {
    return Center(
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        Icon(Icons.error_outline_rounded, size: 48, color: AppColors.waitRed.withValues(alpha: 0.5)),
        const SizedBox(height: 12),
        const Text('Failed to load dashboard',
            style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
        const SizedBox(height: 8),
        ElevatedButton.icon(
          onPressed: _load,
          icon: const Icon(Icons.refresh),
          label: const Text('Retry'),
          style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary, foregroundColor: Colors.white),
        ),
      ]),
    );
  }

  Widget _buildBody() {
    final data = _data!;
    return ListView(
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 100),
      children: [
        _buildStatCards(data.stats),
        const SizedBox(height: 10),
        _buildOtDoctorStrip(data.otDoctorCards, data.otSummary),
        const SizedBox(height: 14),
        if (data.doctorCards.isNotEmpty) ...[
          _buildDoctorStrip(data.doctorCards),
          const SizedBox(height: 10),
        ],
        if (_viewingDoctorId != null && data.viewingDoctor != null) ...[
          _buildViewingBanner(data.viewingDoctor!),
          const SizedBox(height: 10),
        ],
        _buildQueueTable(isPrimary: true, patients: data.primaryQueue, secondary: const []),
        const SizedBox(height: 14),
        _buildQueueTable(isPrimary: false, patients: const [], secondary: data.secondaryQueue),
      ],
    );
  }

  // ── Stat Cards ─────────────────────────────────────────────────────────────

  Widget _buildStatCards(DoctorDashStats stats) {
    return Row(children: [
      _statCard('Assigned\nToday', stats.assignedToday, Icons.people_alt_rounded, AppColors.primary),
      const SizedBox(width: 8),
      _statCard('Primary\nDone', stats.primaryDone, Icons.check_circle_outline_rounded,
          AppColors.blueLight),
      const SizedBox(width: 8),
      _statCard('Secondary\nDone', stats.secondaryDone, Icons.check_circle_rounded, AppColors.waitGreen),
      const SizedBox(width: 8),
      _reportsCard(),
    ]);
  }

  Widget _statCard(String label, int value, IconData icon, Color color) {
    return Expanded(
      child: _cardBox(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(height: 5),
          Text('$value',
              style: TextStyle(
                  fontSize: 22, fontWeight: FontWeight.w900, color: color, height: 1.1)),
          const SizedBox(height: 3),
          Text(label,
              textAlign: TextAlign.center,
              style: const TextStyle(
                  fontSize: 9, fontWeight: FontWeight.w700, color: AppColors.textSecondary, height: 1.3)),
        ]),
      ),
    );
  }

  /// OT doctor stat-card strip — same roster/layout convention as
  /// `_buildDoctorStrip` (OPD), counts from that doctor's OT bookings.
  /// Supersedes the old "My OT Patients" banner now that real per-doctor
  /// counts are available (web pull 2026-08-07 — see
  /// WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §6 / FIX_PLAN TASK 5.2).
  Widget _buildOtDoctorStrip(List<OtDoctorCardInfo> cards, OtSummary summary) {
    return Container(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 14),
      decoration: BoxDecoration(
        color: const Color(0xFFF8F0FC),
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: const Color(0xFFE9D5FF)),
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Icon(Icons.local_hospital_rounded, color: AppColors.purple, size: 16),
          const SizedBox(width: 6),
          Text('OT',
              style: TextStyle(fontSize: 10, fontWeight: FontWeight.w900, letterSpacing: 1.5, color: AppColors.purple)),
          const Spacer(),
          InkWell(
            onTap: () => Navigator.push(context, appRoute(const DoctorOtListScreen())),
            borderRadius: BorderRadius.circular(20),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: AppColors.purple.withValues(alpha: 0.3)),
              ),
              child: Text('OT ${summary.total} · Pending ${summary.pending} · Complete ${summary.complete}',
                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: AppColors.purple)),
            ),
          ),
        ]),
        const SizedBox(height: 10),
        if (cards.isEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 4),
            child: Text('No doctors found for OT counts.', style: TextStyle(fontSize: 11, color: AppColors.textSecondary)),
          )
        else
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(children: cards.map(_buildOtDoctorCard).toList()),
          ),
      ]),
    );
  }

  Widget _buildOtDoctorCard(OtDoctorCardInfo doc) {
    return GestureDetector(
      onTap: () => Navigator.push(context, appRoute(DoctorOtListScreen(doctorId: doc.isSelf ? null : doc.id))),
      child: Container(
        margin: const EdgeInsets.only(right: 10),
        padding: const EdgeInsets.all(12),
        width: 148,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: const Color(0xFFE9D5FF)),
        ),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Container(
              width: 30, height: 30,
              alignment: Alignment.center,
              decoration: BoxDecoration(color: const Color(0xFFEFE6F8), borderRadius: BorderRadius.circular(AppRadius.sm)),
              child: Text(
                doc.name.isNotEmpty ? doc.name[0].toUpperCase() : '?',
                style: TextStyle(fontWeight: FontWeight.w900, fontSize: 13, color: AppColors.purple),
              ),
            ),
            if (doc.isSelf) ...[
              const SizedBox(width: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                decoration: BoxDecoration(color: AppColors.purple, borderRadius: BorderRadius.circular(5)),
                child: const Text('You', style: TextStyle(fontSize: 8, fontWeight: FontWeight.w900, color: Colors.white)),
              ),
            ],
          ]),
          const SizedBox(height: 8),
          Text(doc.name,
              style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.purple),
              maxLines: 1,
              overflow: TextOverflow.ellipsis),
          const SizedBox(height: 2),
          Text('${doc.otTotal} OT cases', style: const TextStyle(fontSize: 10, color: AppColors.textSecondary)),
          const SizedBox(height: 7),
          Row(children: [
            _miniPill('P:${doc.otPending}', doc.otPending > 0 ? AppColors.orange : const Color(0xFFCBD5E1)),
            const SizedBox(width: 4),
            _miniPill('C:${doc.otComplete}', doc.otComplete > 0 ? AppColors.waitGreen : const Color(0xFFCBD5E1)),
          ]),
        ]),
      ),
    );
  }

  Widget _reportsCard() {
    return Expanded(
      child: GestureDetector(
        onTap: () => Navigator.push(context, appRoute(ReportsScreen(user: widget.user, hospital: widget.hospital))),
        child: _cardBox(
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Icon(Icons.bar_chart_rounded, color: AppColors.primary.withValues(alpha: 0.65), size: 20),
            const SizedBox(height: 5),
            Icon(Icons.arrow_forward_rounded, size: 18, color: AppColors.primary.withValues(alpha: 0.50)),
            const SizedBox(height: 3),
            const Text('Reports',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 9, fontWeight: FontWeight.w700, color: AppColors.textSecondary)),
          ]),
        ),
      ),
    );
  }

  Widget _cardBox({required Widget child}) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Color(0xFFCDE5F5)),
        boxShadow: [
          BoxShadow(
              color: AppColors.primary.withValues(alpha: 0.06), blurRadius: 8, offset: Offset(0, 2)),
        ],
      ),
      child: child,
    );
  }

  // ── Doctor Strip ────────────────────────────────────────────────────────────

  Widget _buildDoctorStrip(List<DoctorCardInfo> cards) {
    return Container(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 14),
      decoration: BoxDecoration(
        color: AppColors.background,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: Color(0xFFCDE5F5)),
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text('DOCTORS TODAY',
            style: TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w900,
                letterSpacing: 1.5,
                color: AppColors.primary)),
        const SizedBox(height: 10),
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: Row(
            children: cards.map(_buildDoctorCard).toList(),
          ),
        ),
      ]),
    );
  }

  Widget _buildDoctorCard(DoctorCardInfo doc) {
    final isSelected = _viewingDoctorId == null ? doc.isSelf : _viewingDoctorId == doc.id;
    return GestureDetector(
      onTap: () => _selectDoctor(doc.isSelf && _viewingDoctorId == null ? null : doc.id),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        margin: const EdgeInsets.only(right: 10),
        padding: const EdgeInsets.all(12),
        width: 148,
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFFDDEEF9) : Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: isSelected ? AppColors.primary : Color(0xFFCDE5F5),
            width: isSelected ? 2 : 1,
          ),
        ),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Container(
              width: 30, height: 30,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: isSelected ? AppColors.primary : Color(0xFFEEF2F6),
                borderRadius: BorderRadius.circular(AppRadius.sm),
              ),
              child: Text(
                doc.name.isNotEmpty ? doc.name[0].toUpperCase() : '?',
                style: TextStyle(
                  fontWeight: FontWeight.w900,
                  fontSize: 13,
                  color: isSelected ? Colors.white : AppColors.textSecondary,
                ),
              ),
            ),
            if (doc.isSelf) ...[
              const SizedBox(width: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                decoration: BoxDecoration(
                    color: AppColors.primary, borderRadius: BorderRadius.circular(5)),
                child: const Text('You',
                    style: TextStyle(
                        fontSize: 8, fontWeight: FontWeight.w900, color: Colors.white)),
              ),
            ],
          ]),
          const SizedBox(height: 8),
          Text(doc.name,
              style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.primary),
              maxLines: 1,
              overflow: TextOverflow.ellipsis),
          const SizedBox(height: 2),
          Text('${doc.assignedToday} patients',
              style: TextStyle(fontSize: 10, color: AppColors.textSecondary)),
          const SizedBox(height: 7),
          Row(children: [
            _miniPill('P:${doc.primaryCount}',
                doc.primaryCount > 0 ? AppColors.primary : Color(0xFFCBD5E1)),
            const SizedBox(width: 4),
            _miniPill('S:${doc.secondaryCount}',
                doc.secondaryCount > 0 ? AppColors.waitGreen : const Color(0xFFCBD5E1)),
          ]),
        ]),
      ),
    );
  }

  Widget _miniPill(String text, Color color) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
        decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(6)),
        child: Text(text,
            style: const TextStyle(
                fontSize: 9, fontWeight: FontWeight.w900, color: Colors.white)),
      );

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

  Widget _buildQueueTable({
    required bool isPrimary,
    required List<PrimaryPatient> patients,
    required List<SecondaryPatient> secondary,
  }) {
    final title = isPrimary ? 'Primary Patient' : 'Secondary Patient';
    final count = isPrimary ? patients.length : secondary.length;

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
              child: Text('$count',
                  style: const TextStyle(
                      color: Colors.white, fontSize: 11, fontWeight: FontWeight.w800)),
            ),
          ]),
        ),
        // Rows
        if (count == 0)
          _buildEmptyQueue()
        else if (isPrimary)
          ...patients.map(_buildPrimaryRow)
        else
          ...secondary.map(_buildSecondaryRow),
      ]),
    );
  }

  Widget _buildEmptyQueue() {
    return const Padding(
      padding: EdgeInsets.symmetric(vertical: 28),
      child: AppEmptyState(message: 'No patients'),
    );
  }

  Widget _buildPrimaryRow(PrimaryPatient p) {
    return _queueRowShell(
      drIndex: p.drIndexNo,
      name: p.patientName,
      age: p.age,
      city: p.city,
      waitPills: [_waitPill('R', p.registeredAt, _rTh)],
      actionWidget: _examBtn('Examine', () => _openPrimaryExam(p)),
      hasHistory: p.hasHistory,
      onHistory: () => _onHistory(p),
    );
  }

  Widget _buildSecondaryRow(SecondaryPatient p) {
    final pills = <Widget>[_waitPill('R', p.registeredAt, _rTh)];
    if (p.primaryDoneAt != null) {
      pills.add(const SizedBox(width: 4));
      pills.add(_waitPill(p.isDilated ? 'D' : 'ND', p.primaryDoneAt!,
          p.isDilated ? _dTh : _ndTh));
    }

    return _queueRowShell(
      drIndex: p.drIndexNo,
      name: p.patientName,
      age: p.age,
      city: p.city,
      waitPills: pills,
      actionWidget: _secondaryActionBtn(p),
      hasHistory: p.hasHistory,
      onHistory: () => _onHistory(p),
    );
  }

  Widget _queueRowShell({
    required String drIndex,
    required String name,
    required int? age,
    required String city,
    required List<Widget> waitPills,
    required Widget actionWidget,
    required bool hasHistory,
    required VoidCallback onHistory,
  }) {
    return Container(
      decoration: BoxDecoration(
          border: Border(bottom: BorderSide(color: Color(0xFFCDE5F5).withValues(alpha: 0.5)))),
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 12),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        SizedBox(
          width: 52,
          child: Text(drIndex,
              style: TextStyle(
                  fontSize: 11, fontWeight: FontWeight.w800, color: AppColors.primary)),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(name,
                style: TextStyle(
                    fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.primary),
                maxLines: 1,
                overflow: TextOverflow.ellipsis),
            const SizedBox(height: 2),
            Text(
              '${age != null ? '${age}y' : '-'} • ${city.isNotEmpty ? city : '-'}',
              style: TextStyle(fontSize: 10, color: AppColors.textSecondary),
            ),
            const SizedBox(height: 6),
            Row(children: waitPills),
          ]),
        ),
        const SizedBox(width: 8),
        Column(crossAxisAlignment: CrossAxisAlignment.end, mainAxisSize: MainAxisSize.min,
            children: [
          actionWidget,
          if (hasHistory) ...[
            const SizedBox(height: 6),
            _histBtn(onHistory),
          ],
        ]),
      ]),
    );
  }

  // ── Wait Pills ──────────────────────────────────────────────────────────────

  // [green_max_min, orange_max_min, fire_min]
  static const _rTh  = [30, 60, 120];   // registration
  static const _dTh  = [40, 90, 9999];  // dilated
  static const _ndTh = [20, 60, 9999];  // not dilated

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
            style: const TextStyle(
                color: Colors.white, fontSize: 11, fontWeight: FontWeight.w800)),
      ),
    );
  }

  Widget _histBtn(VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
        decoration: BoxDecoration(color: Color(0xFF0D9488), borderRadius: BorderRadius.circular(AppRadius.sm)),
        child: const Text('History',
            style: TextStyle(
                color: Colors.white, fontSize: 11, fontWeight: FontWeight.w800)),
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

  void _onHistory(PrimaryPatient p) {
    Navigator.push(
      context,
      appRoute(PatientHistoryScreen(
        user: widget.user,
        hospital: widget.hospital,
        patient: _minimalPatient(p),
      )),
    );
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
