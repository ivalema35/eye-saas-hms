import '../utils/date_format.dart';
import 'dart:async';
import 'package:flutter/material.dart';
import '../widgets/app_refresh_indicator.dart';
import '../utils/app_route.dart';
import '../widgets/skeleton.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_record_card.dart';
import '../models/auth_models.dart';
import '../models/clinical_queue_models.dart';
import '../models/patient_models.dart';
import '../services/clinical_queue_service.dart';
import '../utils/refreshable.dart';
import '../widgets/exam/dilation_lock.dart';
import 'primary_exam_screen.dart';
import 'secondary_exam_screen.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../utils/app_date_picker.dart';

// ── Date / string helpers ─────────────────────────────────────────────────────

String _hhmm(DateTime? dt) {
  if (dt == null) return '--:--';
  final l = toHospitalTime(dt);
  return '${l.hour.toString().padLeft(2, '0')}:${l.minute.toString().padLeft(2, '0')}';
}

String _chipDate(DateTime dt) {
  const months = [
    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
  ];
  return '${dt.day} ${months[dt.month - 1]} ${dt.year}';
}

String _apiDate(DateTime dt) =>
    '${dt.year}-${dt.month.toString().padLeft(2, '0')}-${dt.day.toString().padLeft(2, '0')}';

String _cap(String s) => s.isEmpty ? s : s[0].toUpperCase() + s.substring(1);

// ── Shared 1-second ticker ────────────────────────────────────────────────────
// A single Timer.periodic drives all _DilationCountdown widgets in this file.
// ValueListenableBuilder means only the countdown subtree rebuilds — not the
// enclosing card — and N dilating patients cost ONE timer instead of N timers.

final _queueTick = ValueNotifier<int>(0);
Timer? _queueTickTimer;

void _startQueueTicker() =>
    _queueTickTimer ??= Timer.periodic(
      const Duration(seconds: 1),
      (_) => _queueTick.value++,
    );

void _stopQueueTicker() {
  _queueTickTimer?.cancel();
  _queueTickTimer = null;
}

// ═════════════════════════════════════════════════════════════════════════════
// SCREEN
// ═════════════════════════════════════════════════════════════════════════════

class ClinicalQueueScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final VoidCallback? onMenuTap;

  const ClinicalQueueScreen({
    super.key,
    required this.user,
    required this.hospital,
    this.onMenuTap,
  });

  @override
  State<ClinicalQueueScreen> createState() => _ClinicalQueueScreenState();
}

class _ClinicalQueueScreenState extends State<ClinicalQueueScreen>
    with SingleTickerProviderStateMixin
    implements Refreshable {

  QueueData? _data;
  bool _loading = false;
  String? _error;
  DateTime _filterDate = toHospitalTime(DateTime.now());
  int? _selectedDoctorId;

  late final TabController _tabCtrl;
  Timer? _autoRefreshTimer;

  @override
  void initState() {
    super.initState();
    _tabCtrl = TabController(length: 3, vsync: this);
    if (widget.user.role?.slug == 'doctor') {
      _selectedDoctorId = widget.user.id;
    }
    _startQueueTicker();
    _load();
    _autoRefreshTimer = Timer.periodic(
      const Duration(minutes: 5),
      (_) { if (mounted) _load(silent: true); },
    );
  }

  @override
  void dispose() {
    _tabCtrl.dispose();
    _autoRefreshTimer?.cancel();
    _stopQueueTicker();
    super.dispose();
  }

  @override
  Future<void> refreshSilently() => _load(silent: true);

  Future<void> _load({bool silent = false}) async {
    if (!silent) setState(() { _loading = true; _error = null; });
    try {
      final result = await ClinicalQueueService.instance.fetchQueue(
        date: _apiDate(_filterDate),
        doctorId: _selectedDoctorId,
      );
      if (mounted) setState(() { _data = result; _loading = false; });
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString().replaceFirst('Exception: ', '');
          _loading = false;
        });
      }
    }
  }

  Future<void> _selectDate() async {
    final picked = await showAppDatePicker(
      context: context,
      initialDate: _filterDate,
      firstDate: DateTime(2024),
      lastDate: DateTime.now().add(const Duration(days: 1)),
      builder: (ctx, child) => Theme(
        data: ThemeData.light().copyWith(
          colorScheme: ColorScheme.light(primary: AppColors.primary),
        ),
        child: child!,
      ),
    );
    if (picked != null && picked != _filterDate) {
      setState(() { _filterDate = picked; });
      _load();
    }
  }

  void _selectDoctor(int? id) {
    if (_selectedDoctorId == id) return;
    setState(() { _selectedDoctorId = id; });
    _load();
  }

  bool get _isToday {
    final n = toHospitalTime(DateTime.now());
    return _filterDate.year == n.year &&
        _filterDate.month == n.month &&
        _filterDate.day == n.day;
  }

  Patient _patientFrom(QueuePatient q) => Patient(
        id: q.id,
        patientCode: q.patientCode,
        firstName: q.firstName,
        lastName: q.lastName,
        fullName: q.fullName,
        age: q.age,
        gender: q.gender,
        contactNo: q.contactNo,
        createdAt: q.createdAt,
        primaryDoneAt: q.primaryDoneAt,
      );

  Future<void> _startPrimaryExam(QueuePatient q) async {
    final result = await Navigator.push<bool>(
      context,
      appRoute(PrimaryExamScreen(
        user: widget.user,
        hospital: widget.hospital,
        patient: _patientFrom(q),
      )),
    );
    if (result == true && mounted) _load(silent: true);
  }

  // Only ever called for a patient the caller already knows is unlockable —
  // the Secondary tab excludes still-dilating patients entirely (they live
  // in the Dilation tab instead), and the Dilation tab's own card gates
  // this behind its double-tap override confirmation. So this always
  // navigates straight through, matching web's plain (non-locked) "Examine"
  // link — no dialog here.
  Future<void> _startSecondaryExam(QueuePatient q) async {
    final result = await Navigator.push<bool>(
      context,
      appRoute(SecondaryExamScreen(
        user: widget.user,
        hospital: widget.hospital,
        patient: _patientFrom(q),
      )),
    );
    if (result == true && mounted) _load(silent: true);
  }

  // ── AppBar ─────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Color(0xFFF6FAFD),
      appBar: _buildAppBar(),
      body: _buildBody(),
    );
  }

  AppBar _buildAppBar() {
    final otherDay = !_isToday;
    return AppBar(
      backgroundColor: AppColors.primary,
      foregroundColor: Colors.white,
      elevation: 0,
      leading: widget.onMenuTap != null
          ? IconButton(
              icon: const Icon(Icons.menu_rounded, color: Colors.white),
              onPressed: widget.onMenuTap,
            )
          : null,
      title: const Text(
        'Clinical Queue',
        style: TextStyle(fontWeight: FontWeight.w800, fontSize: 18),
      ),
      actions: [
        // Date filter chip
        GestureDetector(
          onTap: _selectDate,
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 250),
            margin: const EdgeInsets.symmetric(vertical: 10),
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: otherDay
                  ? Color(0xFFD97706).withValues(alpha: 0.22)
                  : Colors.white.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(AppRadius.xl),
              border: Border.all(
                color: otherDay
                    ? Color(0xFFD97706).withValues(alpha: 0.55)
                    : Colors.white.withValues(alpha: 0.30),
              ),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.calendar_today_rounded, size: 13, color: Colors.white),
                const SizedBox(width: 5),
                Text(
                  _chipDate(_filterDate),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
          ),
        ),
        // Refresh
        IconButton(
          onPressed: _loading ? null : _load,
          icon: _loading
              ? const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white60),
                )
              : const Icon(Icons.refresh_rounded, size: 22),
        ),
        const SizedBox(width: 4),
      ],
    );
  }

  // ── Body ──────────────────────────────────────────────────────────────────

  Widget _buildBody() {
    if (_loading && _data == null) {
      return const AppSkeletonList();
    }
    if (_error != null && _data == null) {
      return AppErrorState(message: _error!, onRetry: _load);
    }

    final data = _data;
    if (data == null) return const SizedBox.shrink();

    return Column(
      children: [
        _DoctorSection(
          data: data,
          selectedId: _selectedDoctorId,
          onSelect: _selectDoctor,
        ),
        Expanded(
          child: _QueuePanel(
            tabCtrl: _tabCtrl,
            data: data,
            onRefresh: _load,
            onStartPrimary:   (p) => _startPrimaryExam(p),
            onStartSecondary: (p) => _startSecondaryExam(p),
          ),
        ),
      ],
    );
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// DOCTOR SECTION
// ═════════════════════════════════════════════════════════════════════════════

class _DoctorSection extends StatelessWidget {
  final QueueData data;
  final int? selectedId;
  final void Function(int? id) onSelect;

  const _DoctorSection({
    required this.data,
    required this.selectedId,
    required this.onSelect,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
          child: Row(
            children: [
              Icon(Icons.medical_services_rounded, size: 15, color: Color(0xFF6B7D93).withValues(alpha: 0.80)),
              const SizedBox(width: 6),
              Text(
                'DOCTOR ASSIGNMENTS',
                style: TextStyle(
                  fontSize: 10.5,
                  fontWeight: FontWeight.w900,
                  letterSpacing: 1.3,
                  color: Color(0xFF6B7D93).withValues(alpha: 0.80),
                ),
              ),
            ],
          ),
        ),
        SizedBox(
          height: 124,
          child: ListView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.fromLTRB(14, 0, 14, 8),
            children: [
              _AllDoctorsCard(
                data: data,
                isActive: selectedId == null,
                onTap: () => onSelect(null),
              ),
              ...data.doctors.map((d) => Padding(
                    padding: const EdgeInsets.only(left: 10),
                    child: _DoctorCard(
                      doctor: d,
                      isActive: selectedId == d.id,
                      onTap: () => onSelect(d.id),
                    ),
                  )),
            ],
          ),
        ),
      ],
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────

class _AllDoctorsCard extends StatelessWidget {
  final QueueData data;
  final bool isActive;
  final VoidCallback onTap;

  const _AllDoctorsCard({
    required this.data,
    required this.isActive,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    // Aggregate from data.doctors (per-doctor summaries, always unfiltered)
    // rather than data.totalPrimary/totalSecondary/totalDilation, which are
    // derived from primaryQueue/dilationQueue/secondaryQueue — lists that
    // are scoped to whichever doctor is currently selected. Using those here
    // made the "All Doctors" card show a specific doctor's counts (often 0)
    // instead of the true grand total whenever a doctor filter was active.
    final primaryN   = data.doctors.fold<int>(0, (sum, d) => sum + d.primaryPending);
    final secondaryN = data.doctors.fold<int>(0, (sum, d) => sum + d.secondaryPending);

    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        width: 148,
        padding: const EdgeInsets.all(11),
        decoration: BoxDecoration(
          color: isActive ? AppColors.primaryA08 : Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(
            color: isActive ? AppColors.primary.withValues(alpha: 0.28) : AppColors.primaryA12,
            width: isActive ? 1.5 : 1,
          ),
          boxShadow: [BoxShadow(color: AppColors.primaryA06, blurRadius: 10, offset: const Offset(0, 3))],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(shape: BoxShape.circle, color: AppColors.primaryA10),
                  alignment: Alignment.center,
                  child: Icon(Icons.groups_rounded, size: 19, color: AppColors.primary),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'All Doctors',
                        style: TextStyle(fontWeight: FontWeight.w800, fontSize: 12, color: Color(0xFF18324A)),
                        overflow: TextOverflow.ellipsis,
                      ),
                      Text(
                        '${primaryN + secondaryN} Total',
                        style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF6B7D93).withValues(alpha: 0.80)),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const Spacer(),
            Wrap(
              spacing: 4,
              runSpacing: 4,
              children: [
                _CountPill(label: 'Primary',   count: primaryN,   filled: primaryN > 0,   color: AppColors.primary),
                _CountPill(label: 'Secondary', count: secondaryN, filled: secondaryN > 0, color: Color(0xFF2563EB)),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────

class _DoctorCard extends StatelessWidget {
  final DoctorSummary doctor;
  final bool isActive;
  final VoidCallback onTap;

  const _DoctorCard({
    required this.doctor,
    required this.isActive,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        width: 148,
        padding: const EdgeInsets.all(11),
        decoration: BoxDecoration(
          color: isActive ? AppColors.primaryA08 : Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(
            color: isActive ? AppColors.primary.withValues(alpha: 0.28) : AppColors.primaryA12,
            width: isActive ? 1.5 : 1,
          ),
          boxShadow: [BoxShadow(color: AppColors.primaryA06, blurRadius: 10, offset: const Offset(0, 3))],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(shape: BoxShape.circle, color: AppColors.primaryA10),
                  alignment: Alignment.center,
                  child: Text(
                    doctor.initials,
                    style: TextStyle(
                      fontWeight: FontWeight.w900,
                      fontSize: 13,
                      color: AppColors.primary,
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        doctor.name,
                        style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 11, color: Color(0xFF18324A)),
                        overflow: TextOverflow.ellipsis,
                        maxLines: 1,
                      ),
                      Text(
                        '${doctor.totalAssigned} Assigned',
                        style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: Color(0xFF6B7D93).withValues(alpha: 0.80)),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const Spacer(),
            if (doctor.isAllClear)
              Row(
                children: [
                  const Icon(Icons.check_circle_rounded, size: 13, color: Color(0xFF1F9D55)),
                  const SizedBox(width: 4),
                  const Text(
                    'All Clear',
                    style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFF1F9D55)),
                  ),
                ],
              )
            else
              Wrap(
                spacing: 4,
                runSpacing: 4,
                children: [
                  _CountPill(label: 'Primary',   count: doctor.primaryPending,   filled: doctor.primaryPending > 0,   color: AppColors.primary),
                  _CountPill(label: 'Secondary', count: doctor.secondaryPending, filled: doctor.secondaryPending > 0, color: Color(0xFF2563EB)),
                ],
              ),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────

class _CountPill extends StatelessWidget {
  final String label;
  final int count;
  final bool filled;
  final Color color;

  const _CountPill({
    required this.label,
    required this.count,
    required this.filled,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: filled ? color : color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(999),
        border: filled ? null : Border.all(color: color.withValues(alpha: 0.20)),
      ),
      child: Text(
        '$label $count',
        style: TextStyle(
          color: filled ? Colors.white : color.withValues(alpha: 0.70),
          fontSize: 9,
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// QUEUE PANEL (custom sticky tab bar + TabBarView)
// ═════════════════════════════════════════════════════════════════════════════

class _QueuePanel extends StatelessWidget {
  final TabController tabCtrl;
  final QueueData data;
  final Future<void> Function() onRefresh;
  final void Function(QueuePatient) onStartPrimary;
  final void Function(QueuePatient) onStartSecondary;

  const _QueuePanel({
    required this.tabCtrl,
    required this.data,
    required this.onRefresh,
    required this.onStartPrimary,
    required this.onStartSecondary,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        _buildTabBar(),
        Expanded(
          child: ListenableBuilder(
            listenable: tabCtrl,
            builder: (_, _) => IndexedStack(
              index: tabCtrl.index,
              children: [
                _PrimaryQueueTab(
                  patients: data.primaryQueue,
                  thresholds: data.thresholds,
                  onRefresh: onRefresh,
                  onStart: onStartPrimary,
                ),
                _DilationWaitingTab(
                  patients: data.dilationQueue,
                  thresholds: data.thresholds,
                  onRefresh: onRefresh,
                  onOverride: onStartSecondary,
                ),
                _SecondaryQueueTab(
                  patients: data.secondaryQueue,
                  thresholds: data.thresholds,
                  onRefresh: onRefresh,
                  onStart: onStartSecondary,
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildTabBar() {
    return Container(
      margin: const EdgeInsets.fromLTRB(14, 6, 14, 6),
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: const Color(0xFFF4F8FB),
        borderRadius: BorderRadius.circular(AppRadius.xl),
        border: Border.all(color: AppColors.primaryA12),
      ),
      child: AnimatedBuilder(
        animation: tabCtrl,
        builder: (_, _) => Row(
          children: [
            _QueueTabBtn(
              icon: Icons.visibility_rounded,
              label: 'Primary',
              count: data.totalPrimary,
              active: tabCtrl.index == 0,
              onTap: () => tabCtrl.animateTo(0),
            ),
            _QueueTabBtn(
              icon: Icons.hourglass_bottom_rounded,
              label: 'Dilation',
              count: data.totalDilation,
              active: tabCtrl.index == 1,
              onTap: () => tabCtrl.animateTo(1),
            ),
            _QueueTabBtn(
              icon: Icons.favorite_rounded,
              label: 'Secondary',
              count: data.totalSecondary,
              active: tabCtrl.index == 2,
              onTap: () => tabCtrl.animateTo(2),
            ),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────

class _QueueTabBtn extends StatelessWidget {
  final IconData icon;
  final String label;
  final int count;
  final bool active;
  final VoidCallback onTap;

  const _QueueTabBtn({
    required this.icon,
    required this.label,
    required this.count,
    required this.active,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 4),
          decoration: BoxDecoration(
            color: active ? Colors.white.withValues(alpha: 0.96) : Colors.transparent,
            borderRadius: BorderRadius.circular(AppRadius.lg),
            boxShadow: active
                ? [BoxShadow(color: AppColors.primaryA08, blurRadius: 18, offset: const Offset(0, 4))]
                : [],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(icon, size: 13, color: active ? AppColors.primary : Color(0xFF6B7D93)),
                  const SizedBox(width: 4),
                  Text(
                    label,
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w800,
                      color: active ? AppColors.primary : Color(0xFF6B7D93),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 3),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 1),
                decoration: BoxDecoration(
                  color: active ? AppColors.primaryA08 : AppColors.primaryA04,
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  '$count',
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w800,
                    color: active ? AppColors.primary : Color(0xFF6B7D93),
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

// ═════════════════════════════════════════════════════════════════════════════
// TAB 1 — PRIMARY QUEUE
// ═════════════════════════════════════════════════════════════════════════════

class _PrimaryQueueTab extends StatelessWidget {
  final List<QueuePatient> patients;
  final AllWaitThresholds thresholds;
  final Future<void> Function() onRefresh;
  final void Function(QueuePatient) onStart;

  const _PrimaryQueueTab({
    required this.patients,
    required this.thresholds,
    required this.onRefresh,
    required this.onStart,
  });

  @override
  Widget build(BuildContext context) {
    return AppRefreshIndicator(
      onRefresh: onRefresh,
      color: AppColors.primary,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 4, 14, 24),
        children: patients.isEmpty
            ? [const _QueueEmptyState(
                icon: Icons.check_circle_outline_rounded,
                title: 'No patients waiting for Primary Exam',
                subtitle: 'All primary exams completed for the selected date',
              )]
            : List.generate(patients.length, (i) {
                final p = patients[i];
                return AnimatedListItem(
                  index: i,
                  child: RepaintBoundary(
                    key: ValueKey('primary_${p.id}'),
                    child: Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: _PrimaryPatientCard(
                        patient: p,
                        thresholds: thresholds,
                        onStart: () => onStart(p),
                      ),
                    ),
                  ),
                );
              }),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────

class _PrimaryPatientCard extends StatelessWidget {
  final QueuePatient patient;
  final AllWaitThresholds thresholds;
  final VoidCallback onStart;

  const _PrimaryPatientCard({
    required this.patient,
    required this.thresholds,
    required this.onStart,
  });

  // Migrated onto the shared AppRecordCard/AppRecordField icon-badge grid —
  // see RECORD_CARD_UNIFICATION_PLAN.md Tier 1 §5. The live WaitPill stays
  // outside the static field grid, in the `actions` slot, unchanged.
  @override
  Widget build(BuildContext context) {
    final p = patient;
    return AppRecordCard(
      leading: const PulseDot(color: AppColors.waitOrange),
      title: p.fullName,
      subtitle: 'MRD: ${p.patientCode}',
      trailing: _AgeGenderBadge(age: p.age, gender: p.gender, bgColor: Color(0xFFD94841).withValues(alpha: 0.10), textColor: Color(0xFFD94841)),
      fields: [
        if (p.contactNo != null && p.contactNo!.isNotEmpty) AppRecordField('Phone', p.contactNo!, icon: Icons.phone_outlined, color: RecordFieldColors.phone),
        if (p.caseType != null) AppRecordField('Case Type', p.caseType!, icon: Icons.label_outline_rounded, color: RecordFieldColors.caseType),
        AppRecordField('Registered', _hhmm(p.createdAt), icon: Icons.schedule_rounded, color: RecordFieldColors.date),
      ],
      actions: Row(
        children: [
          WaitPill(waitFrom: p.createdAt, label: 'R', thresholds: thresholds.r),
          const Spacer(),
          _StartButton(label: 'Start Primary', onTap: onStart),
        ],
      ),
    );
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// TAB 2 — DILATION WAITING
// ═════════════════════════════════════════════════════════════════════════════

class _DilationWaitingTab extends StatelessWidget {
  final List<QueuePatient> patients;
  final AllWaitThresholds thresholds;
  final Future<void> Function() onRefresh;
  final void Function(QueuePatient) onOverride;

  const _DilationWaitingTab({
    required this.patients,
    required this.thresholds,
    required this.onRefresh,
    required this.onOverride,
  });

  @override
  Widget build(BuildContext context) {
    return AppRefreshIndicator(
      onRefresh: onRefresh,
      color: AppColors.primary,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 4, 14, 24),
        children: patients.isEmpty
            ? [const _QueueEmptyState(
                icon: Icons.visibility_outlined,
                title: 'No patients currently dilating',
                subtitle: 'All dilation periods have completed or no dilation requested',
              )]
            : List.generate(patients.length, (i) {
                final p = patients[i];
                return AnimatedListItem(
                  index: i,
                  child: RepaintBoundary(
                    key: ValueKey('dilation_${p.id}'),
                    child: Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: _DilationPatientCard(patient: p, thresholds: thresholds, onOverride: onOverride),
                    ),
                  ),
                );
              }),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────

class _DilationPatientCard extends StatelessWidget {
  final QueuePatient patient;
  final AllWaitThresholds thresholds;
  final void Function(QueuePatient) onOverride;

  const _DilationPatientCard({
    required this.patient,
    required this.thresholds,
    required this.onOverride,
  });

  // Matches web's dilation countdown button exactly: a single tap does
  // nothing — only a double-tap opens the override confirmation.
  Future<void> _handleDoubleTap(BuildContext context, int? unlockMs) async {
    final ok = await canStartSecondaryExam(context, unlockMs);
    if (ok) onOverride(patient);
  }

  // Migrated onto the shared AppRecordCard/AppRecordField icon-badge grid —
  // see RECORD_CARD_UNIFICATION_PLAN.md Tier 1 §5. The live double-tap
  // countdown and WaitPills are stateful/tick every second — kept OUTSIDE
  // the static field grid, in the `actions` slot, exactly as before.
  @override
  Widget build(BuildContext context) {
    final p       = patient;
    final exam    = p.primaryExamination;
    final examAt  = exam?.examinedAt;
    final dilTime = exam?.dilationTime ?? 0;
    final unlockMs = p.unlockTimeMs;

    return AppRecordCard(
      title: p.fullName,
      subtitle: 'MRD: ${p.patientCode}',
      trailing: _AgeGenderBadge(age: p.age, gender: p.gender, bgColor: Color(0xFFD97706).withValues(alpha: 0.10), textColor: Color(0xFFD97706)),
      fields: [
        AppRecordField('Primary Done', _hhmm(examAt), icon: Icons.check_circle_outline_rounded, color: Color(0xFF1F9D55)),
        AppRecordField('Dilation', '$dilTime min', icon: Icons.timer_outlined, color: RecordFieldColors.date),
        if (unlockMs != null)
          AppRecordField('Unlocks', _hhmm(DateTime.fromMillisecondsSinceEpoch(unlockMs)), icon: Icons.notifications_outlined, color: RecordFieldColors.date),
      ],
      actions: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Live countdown — double-tap to override (web parity: "Double
          // click to override and proceed").
          if (unlockMs != null) ...[
            GestureDetector(
              onDoubleTap: () => _handleDoubleTap(context, unlockMs),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _DilationCountdown(
                    key: ValueKey('countdown_${p.id}_$unlockMs'),
                    unlockTimeMs: unlockMs,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Double-tap to override and proceed',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w600, color: Color(0xFF92400E).withValues(alpha: 0.75)),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 10),
          ],
          Row(
            children: [
              WaitPill(waitFrom: p.createdAt, label: 'R', thresholds: thresholds.r),
              if (examAt != null) ...[
                const SizedBox(width: 8),
                WaitPill(waitFrom: examAt, label: 'D', thresholds: thresholds.d),
              ],
            ],
          ),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Subscribes to the file-level _queueTick instead of owning its own timer.
// ValueListenableBuilder rebuilds only this widget's subtree — not the card.

class _DilationCountdown extends StatelessWidget {
  final int unlockTimeMs;

  const _DilationCountdown({super.key, required this.unlockTimeMs});

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<int>(
      valueListenable: _queueTick,
      builder: (_, _, _) {
        final diff = unlockTimeMs - DateTime.now().millisecondsSinceEpoch;
        final done = diff <= 0;
        final col  = done ? Color(0xFF1F9D55) : Color(0xFFD94841);
        final text = done
            ? '00:00'
            : '${(diff ~/ 60000).toString().padLeft(2, '0')}:'
              '${((diff % 60000) ~/ 1000).toString().padLeft(2, '0')}';

        return Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
          decoration: BoxDecoration(
            color: done ? Color(0xFF1F9D55).withValues(alpha: 0.10) : Color(0xFFD94841).withValues(alpha: 0.10),
            borderRadius: BorderRadius.circular(AppRadius.md),
            border: Border.all(color: col.withValues(alpha: 0.22)),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.hourglass_bottom_rounded, size: 16, color: col),
              const SizedBox(width: 8),
              Text(
                text,
                style: TextStyle(
                  fontFamily: 'monospace',
                  fontSize: 16,
                  fontWeight: FontWeight.w900,
                  color: col,
                  letterSpacing: 2,
                ),
              ),
              if (done) ...[
                const SizedBox(width: 8),
                Text(
                  'Ready!',
                  style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: col),
                ),
              ],
            ],
          ),
        );
      },
    );
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// TAB 3 — SECONDARY QUEUE
// ═════════════════════════════════════════════════════════════════════════════

class _SecondaryQueueTab extends StatelessWidget {
  final List<QueuePatient> patients;
  final AllWaitThresholds thresholds;
  final Future<void> Function() onRefresh;
  final void Function(QueuePatient) onStart;

  const _SecondaryQueueTab({
    required this.patients,
    required this.thresholds,
    required this.onRefresh,
    required this.onStart,
  });

  @override
  Widget build(BuildContext context) {
    return AppRefreshIndicator(
      onRefresh: onRefresh,
      color: AppColors.primary,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 4, 14, 24),
        children: patients.isEmpty
            ? [const _QueueEmptyState(
                icon: Icons.favorite_border_rounded,
                title: 'No patients waiting for Secondary Exam',
                subtitle: 'All secondary exams completed for the selected date',
              )]
            : List.generate(patients.length, (i) {
                final p = patients[i];
                return AnimatedListItem(
                  index: i,
                  child: RepaintBoundary(
                    key: ValueKey('secondary_${p.id}'),
                    child: Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: _SecondaryPatientCard(
                        patient: p,
                        thresholds: thresholds,
                        onStart: () => onStart(p),
                      ),
                    ),
                  ),
                );
              }),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────

class _SecondaryPatientCard extends StatelessWidget {
  final QueuePatient patient;
  final AllWaitThresholds thresholds;
  final VoidCallback onStart;

  const _SecondaryPatientCard({
    required this.patient,
    required this.thresholds,
    required this.onStart,
  });

  // Migrated onto the shared AppRecordCard/AppRecordField icon-badge grid —
  // see RECORD_CARD_UNIFICATION_PLAN.md Tier 1 §5.
  @override
  Widget build(BuildContext context) {
    final p        = patient;
    final exam     = p.primaryExamination;
    final examAt   = exam?.examinedAt;
    final isDilated = p.isDilated;

    return AppRecordCard(
      title: p.fullName,
      subtitle: 'MRD: ${p.patientCode}',
      trailing: _AgeGenderBadge(age: p.age, gender: p.gender, bgColor: Color(0xFF2563EB).withValues(alpha: 0.10), textColor: Color(0xFF2563EB)),
      fields: [
        if (p.contactNo != null && p.contactNo!.isNotEmpty) AppRecordField('Phone', p.contactNo!, icon: Icons.phone_outlined, color: RecordFieldColors.phone),
        AppRecordField('Primary Done', _hhmm(examAt), icon: Icons.check_circle_outline_rounded, color: Color(0xFF1F9D55)),
        AppRecordField('Findings', p.diagnosisText, icon: Icons.article_outlined, color: RecordFieldColors.caseType),
      ],
      actions: Row(
        children: [
          WaitPill(waitFrom: p.createdAt, label: 'R', thresholds: thresholds.r),
          if (examAt != null) ...[
            const SizedBox(width: 8),
            WaitPill(
              waitFrom: examAt,
              label: isDilated ? 'D' : 'ND',
              thresholds: isDilated ? thresholds.d : thresholds.nd,
            ),
          ],
          const Spacer(),
          _StartButton(label: 'Start Secondary', onTap: onStart),
        ],
      ),
    );
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// WAIT PILL WIDGET — reusable across all 3 tabs
// ═════════════════════════════════════════════════════════════════════════════

class WaitPill extends StatefulWidget {
  final DateTime waitFrom;
  final String label;           // 'R', 'D', or 'ND'
  final WaitThresholds thresholds;

  const WaitPill({
    super.key,
    required this.waitFrom,
    required this.label,
    required this.thresholds,
  });

  @override
  State<WaitPill> createState() => _WaitPillState();
}

class _WaitPillState extends State<WaitPill>
    with SingleTickerProviderStateMixin {
  late AnimationController _pulseCtrl;
  late Animation<double> _pulseAnim;

  @override
  void initState() {
    super.initState();
    _pulseCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1000),
    )..repeat(reverse: true);
    _pulseAnim = Tween<double>(begin: 0.30, end: 0.70)
        .animate(CurvedAnimation(parent: _pulseCtrl, curve: Curves.easeInOut));
  }

  @override
  void dispose() {
    _pulseCtrl.dispose();
    super.dispose();
  }

  int get _mins => DateTime.now().difference(widget.waitFrom).inMinutes;

  Color _color(int mins) {
    if (mins < widget.thresholds.greenMax) return AppColors.waitGreen;
    if (mins < widget.thresholds.orangeMax) return AppColors.waitOrange;
    return AppColors.waitRed;
  }

  @override
  Widget build(BuildContext context) {
    final mins = _mins;
    final col  = _color(mins);
    final fire = mins >= widget.thresholds.redMax;

    return AnimatedBuilder(
      animation: _pulseAnim,
      builder: (_, _) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(
          color: col.withValues(alpha: 0.10),
          borderRadius: BorderRadius.circular(999),
          border: Border.all(
            color: col.withValues(alpha: fire ? _pulseAnim.value : 0.25),
          ),
          boxShadow: fire
              ? [BoxShadow(
                  color: AppColors.waitOrange.withValues(alpha: _pulseAnim.value * 0.50),
                  blurRadius: 10,
                  spreadRadius: 1,
                )]
              : [],
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 22,
              height: 22,
              decoration: BoxDecoration(shape: BoxShape.circle, color: col),
              alignment: Alignment.center,
              child: Text(
                widget.label,
                style: TextStyle(
                  color: Colors.white,
                  fontSize: widget.label == 'ND' ? 7.5 : 9,
                  fontWeight: FontWeight.w900,
                  letterSpacing: -0.3,
                ),
              ),
            ),
            const SizedBox(width: 5),
            Text(
              '${mins}m',
              style: TextStyle(color: col, fontSize: 12, fontWeight: FontWeight.w700),
            ),
          ],
        ),
      ),
    );
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// SHARED WIDGETS
// ═════════════════════════════════════════════════════════════════════════════

class _QueueEmptyState extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;

  const _QueueEmptyState({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 56),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 82,
            height: 82,
            decoration: BoxDecoration(shape: BoxShape.circle, color: AppColors.primaryA08),
            alignment: Alignment.center,
            child: Icon(icon, size: 40, color: AppColors.primary.withValues(alpha: 0.28)),
          ),
          const SizedBox(height: 18),
          Text(
            title,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Color(0xFF18324A)),
          ),
          const SizedBox(height: 6),
          Text(
            subtitle,
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 12, color: Color(0xFF6B7D93).withValues(alpha: 0.80)),
          ),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────

class _AgeGenderBadge extends StatelessWidget {
  final int age;
  final String gender;
  final Color bgColor;
  final Color textColor;

  const _AgeGenderBadge({
    required this.age,
    required this.gender,
    required this.bgColor,
    required this.textColor,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(color: bgColor, borderRadius: BorderRadius.circular(10)),
      child: Text(
        '$age / ${_cap(gender)}',
        style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: textColor),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────

class _StartButton extends StatelessWidget {
  final String label;
  final VoidCallback onTap;

  const _StartButton({required this.label, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return PressScaleWrapper(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 8),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [AppColors.primary, Color(0xFF2C6E99)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(14),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.arrow_forward_rounded, size: 12, color: Colors.white),
            const SizedBox(width: 5),
            Text(
              label,
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: Colors.white),
            ),
          ],
        ),
      ),
    );
  }
}
