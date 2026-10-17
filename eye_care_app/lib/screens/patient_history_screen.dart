import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../utils/app_route.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_refresh_indicator.dart';
import '../widgets/app_error_state.dart';
import '../widgets/exam_clinical_data_view.dart';
import '../widgets/skeleton.dart';
import '../constants/permissions.dart';
import '../models/auth_models.dart';
import '../models/patient_models.dart';
import '../models/patient_history_models.dart';
import '../services/patient_history_service.dart';
import '../services/permission_service.dart';
import '../utils/date_format.dart';
import 'prescription_print_screen.dart';
import 'patient_history_print_screen.dart';
import 'primary_exam_screen.dart';
import 'secondary_exam_screen.dart';


// ─── Screen ────────────────────────────────────────────────────────────────────

class PatientHistoryScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final Patient patient;
  // Share History's connected-hospital patients aren't fetchable through the
  // normal tenant-scoped history endpoint — this lets that one call site
  // swap in ShareHistoryService.fetchPartnerPatientHistory() instead, while
  // every other call site keeps using the default (PatientHistoryService).
  final Future<ExamHistoryData> Function(int patientId)? historyLoader;

  const PatientHistoryScreen({
    super.key,
    required this.user,
    required this.hospital,
    required this.patient,
    this.historyLoader,
  });

  @override
  State<PatientHistoryScreen> createState() => _PatientHistoryScreenState();
}

class _PatientHistoryScreenState extends State<PatientHistoryScreen> {
  ExamHistoryData? _history;
  bool _loading = true;
  String? _error;

  // UX pull 2026-10-05 (tablet parity): clinical data is now always shown
  // inline (no collapse) — this instead tracks which exam IDs are showing
  // the FULL exam form rather than the default "filled fields only" view.
  final Set<int>    _fullExam   = {};
  final Set<String> _partnerFull = {};

  @override
  void initState() {
    super.initState();
    _loadHistory();
  }

  Future<void> _loadHistory() async {
    setState(() { _loading = true; _error = null; });
    try {
      final loader = widget.historyLoader ?? PatientHistoryService.instance.fetchHistory;
      final data = await loader(widget.patient.id);
      if (mounted) setState(() { _history = data; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString(); _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          _buildAppBar(context),
          Expanded(
            child: AppRefreshIndicator(
              color: AppColors.primary,
              onRefresh: _loadHistory,
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 90),
                children: [
                  _buildStatusBadge(),
                  const SizedBox(height: 14),
                  _buildInfoGrid(),
                  const SizedBox(height: 14),
                  _buildTimeline(),
                  if (_history != null)
                    ..._history!.partnerHospitals.asMap().entries.map((e) => Column(
                      children: [
                        const SizedBox(height: 14),
                        _buildPartnerSection(e.key, e.value),
                      ],
                    )),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ── AppBar ─────────────────────────────────────────────────────────────────

  Widget _buildAppBar(BuildContext context) {
    final p = widget.patient;
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [AppColors.primary, AppColors.blueLight],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(8, 8, 16, 16),
          child: Row(
            children: [
              IconButton(
                icon: const Icon(Icons.arrow_back_rounded, color: Colors.white),
                onPressed: () => Navigator.of(context).pop(),
              ),
              const SizedBox(width: 4),
              Container(
                width: 44, height: 44,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(AppRadius.md),
                ),
                child: const Icon(Icons.person_rounded, color: Colors.white, size: 22),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(p.fullName,
                        style: const TextStyle(
                            color: Colors.white, fontSize: 17,
                            fontWeight: FontWeight.w800)),
                    const SizedBox(height: 2),
                    Text(
                      'MRD: ${p.patientCode}  ·  '
                      '${p.age != null ? "${p.age}y / " : ""}'
                      '${p.genderDisplay}',
                      style: TextStyle(
                          color: Colors.white.withValues(alpha: 0.75),
                          fontSize: 11),
                    ),
                  ],
                ),
              ),
              // Print Full History — mirrors web's printer icon next to the
              // Clinical Timeline heading. See PATIENT_HISTORY_PRINT_PLAN.md.
              if (_history != null && _history!.exams.isNotEmpty)
                IconButton(
                  icon: const Icon(Icons.print_outlined, color: Colors.white),
                  tooltip: 'Print History',
                  onPressed: () => Navigator.push(
                    context,
                    appRoute(PatientHistoryPrintScreen(
                      patient: _history!.patient,
                      exams: _history!.exams,
                      diagnosisMasters: _history!.diagnosisMasters,
                      hospital: widget.hospital,
                    )),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  // ── Status badge ───────────────────────────────────────────────────────────

  Widget _buildStatusBadge() {
    final p = widget.patient;
    final isCheckedIn = p.caseId != null;
    final bgColor     = isCheckedIn ? const Color(0xFFD5F5E3) : const Color(0xFFFFF3CD);
    final borderColor = isCheckedIn ? const Color(0xFF82E0AA) : const Color(0xFFFFD57F);
    final textColor   = isCheckedIn ? const Color(0xFF1E8449) : const Color(0xFF856404);
    final label       = isCheckedIn ? 'Checked In' : 'Pending Check-In';
    final icon        = isCheckedIn ? Icons.check_circle_rounded : Icons.schedule_rounded;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: borderColor),
      ),
      child: Row(
        children: [
          Icon(icon, color: textColor, size: 18),
          const SizedBox(width: 8),
          Text(label,
              style: TextStyle(
                  fontSize: 13, fontWeight: FontWeight.w700, color: textColor)),
          const Spacer(),
          if (p.doctorPatientNo != null)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: AppColors.primaryA08,
                borderRadius: BorderRadius.circular(AppRadius.xl),
                border: Border.all(color: AppColors.primaryA18),
              ),
              child: Text(
                'Serial #${p.doctorPatientNo!.toString().padLeft(3, '0')}',
                style: TextStyle(
                    fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.primary),
              ),
            ),
        ],
      ),
    );
  }

  // ── Info grid ──────────────────────────────────────────────────────────────

  Widget _buildInfoGrid() {
    final p = widget.patient;
    return Column(
      children: [
        Row(
          children: [
            Expanded(child: _infoCard(
              icon: Icons.phone_rounded,
              title: 'Contact',
              rows: [
                ('Mobile',    p.contactNo ?? '—'),
                ('WhatsApp',  p.whatsappNo ?? 'Same as mobile'),
                ('City',      p.location?.city ?? '—'),
              ],
            )),
            const SizedBox(width: 12),
            Expanded(child: _infoCard(
              icon: Icons.calendar_today_rounded,
              title: 'Appointment',
              rows: [
                ('Date',   _fmtDate(p.appointmentDate)),
                ('Doctor', p.doctor?.name ?? '—'),
                ('Type',   p.type == 'walkin' ? 'Walk-in' : 'Phone Appt'),
              ],
            )),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(child: _infoCard(
              icon: Icons.receipt_long_rounded,
              title: 'Case Details',
              rows: [
                ('Case Type',   p.caseType?.caseType ?? '—'),
                ('Case Fee',    p.caseFee != null ? '${widget.hospital.currencySymbol}${_fmtFee(p.caseFee!)}' : '—'),
                ('Referred By', p.referrer?.name ?? '—'),
              ],
            )),
            const SizedBox(width: 12),
            Expanded(child: _infoCard(
              icon: Icons.info_rounded,
              title: 'Registration',
              rows: [
                ('MRD',        p.patientCode),
                ('Occupation', p.occupation ?? '—'),
                ('Registered', p.createdAt != null ? _fmtDateTime(p.createdAt!) : '—'),
              ],
            )),
          ],
        ),
      ],
    );
  }

  Widget _infoCard({
    required IconData icon,
    required String title,
    required List<(String, String)> rows,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.primaryA12),
        boxShadow: [BoxShadow(color: AppColors.primaryA08, blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, color: AppColors.primary, size: 14),
              const SizedBox(width: 6),
              Text(title.toUpperCase(),
                  style: TextStyle(
                      fontSize: 9, fontWeight: FontWeight.w800,
                      letterSpacing: 0.8, color: AppColors.primaryA55)),
            ],
          ),
          const SizedBox(height: 10),
          ...rows.map((r) => Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(r.$1,
                    style: TextStyle(
                        fontSize: 9, fontWeight: FontWeight.w600, color: AppColors.primaryA55)),
                const SizedBox(height: 2),
                Text(r.$2.isEmpty ? '—' : r.$2,
                    style: const TextStyle(
                        fontSize: 12, fontWeight: FontWeight.w600,
                        color: Color(0xFF1A202C))),
              ],
            ),
          )),
        ],
      ),
    );
  }

  // ── Clinical timeline ──────────────────────────────────────────────────────

  Widget _buildTimeline() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: AppColors.primaryA12),
        boxShadow: [BoxShadow(color: AppColors.primaryA08, blurRadius: 12, offset: const Offset(0, 3))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Container(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
            decoration: BoxDecoration(
              color: AppColors.primaryA06,
              borderRadius: const BorderRadius.vertical(top: Radius.circular(AppRadius.lg)),
              border: Border(bottom: BorderSide(color: AppColors.primaryA10)),
            ),
            child: Row(
              children: [
                Icon(Icons.history_rounded, size: 16, color: AppColors.primary),
                const SizedBox(width: 8),
                Text('CLINICAL TIMELINE',
                    style: TextStyle(
                        fontSize: 11, fontWeight: FontWeight.w800,
                        letterSpacing: 0.8, color: AppColors.primary)),
                const Spacer(),
                if (_history != null)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                    decoration: BoxDecoration(
                      color: AppColors.primary,
                      borderRadius: BorderRadius.circular(AppRadius.xl),
                    ),
                    child: Text(
                      '${_history!.patient.visitDays} visit${_history!.patient.visitDays == 1 ? '' : 's'}',
                      style: const TextStyle(
                          fontSize: 10, fontWeight: FontWeight.w700, color: Colors.white),
                    ),
                  ),
              ],
            ),
          ),

          // Body
          Padding(
            padding: const EdgeInsets.all(16),
            child: _buildTimelineBody(),
          ),
        ],
      ),
    );
  }

  Widget _buildTimelineBody() {
    if (_loading) {
      return const AppSkeletonList();
    }

    if (_error != null) {
      return AppErrorState(message: _error!, onRetry: _loadHistory);
    }

    if (_history == null || _history!.exams.isEmpty) {
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: 32),
        child: Center(
          child: Column(
            children: [
              Icon(Icons.folder_open_rounded, size: 38, color: AppColors.primaryA50),
              const SizedBox(height: 12),
              Text('No examination records found.',
                  style: TextStyle(
                      fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.primaryA62)),
            ],
          ),
        ),
      );
    }

    final grouped = _history!.byDate;
    return Column(
      children: grouped.entries
          .map((entry) => _buildDateGroup(entry.key, entry.value))
          .toList(),
    );
  }

  Widget _buildDateGroup(String date, List<ExamRecord> exams) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Date divider
        Padding(
          padding: const EdgeInsets.only(bottom: 10, top: 4),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                decoration: BoxDecoration(
                  color: AppColors.primary,
                  borderRadius: BorderRadius.circular(AppRadius.xl),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.calendar_today_rounded,
                        size: 11, color: Colors.white),
                    const SizedBox(width: 5),
                    Text(date,
                        style: const TextStyle(
                            fontSize: 11, fontWeight: FontWeight.w800,
                            color: Colors.white)),
                  ],
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Container(
                  height: 2,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(colors: [
                      AppColors.primaryA24,
                      AppColors.primaryA06,
                    ]),
                    borderRadius: BorderRadius.circular(1),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Text('${exams.length} exam${exams.length > 1 ? 's' : ''}',
                  style: TextStyle(
                      fontSize: 11, fontWeight: FontWeight.w600, color: AppColors.primaryA50)),
            ],
          ),
        ),
        // Exam cards
        ...exams.map((e) => _buildExamCard(e)),
        const SizedBox(height: 8),
      ],
    );
  }

  Widget _buildExamCard(ExamRecord exam) {
    final isPrimary  = exam.isPrimary;
    final showFull   = _fullExam.contains(exam.id);
    final headerBg   = isPrimary ? AppColors.primaryA08 : const Color(0xFFF3F4F6);
    final headerFg   = isPrimary ? AppColors.primary : Color(0xFF6B7280);
    final label      = isPrimary ? 'Primary Exam' : 'Secondary Exam';
    final icon       = isPrimary ? Icons.assignment_outlined : Icons.remove_red_eye_outlined;

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.primaryA12),
        boxShadow: [BoxShadow(color: AppColors.primaryA06, blurRadius: 6, offset: const Offset(0, 2))],
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        children: [
          // Card header
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            color: headerBg,
            child: Row(
              children: [
                Icon(icon, size: 16, color: headerFg),
                const SizedBox(width: 8),
                Text(label,
                    style: TextStyle(
                        fontSize: 13, fontWeight: FontWeight.w800, color: headerFg)),
                const Spacer(),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(AppRadius.xl),
                    border: Border.all(color: AppColors.primaryA12),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.access_time_rounded, size: 11, color: AppColors.primaryA62),
                      const SizedBox(width: 3),
                      Text(_fmtTime(exam.examinedAt),
                          style: TextStyle(
                              fontSize: 10, fontWeight: FontWeight.w700, color: AppColors.primaryA62)),
                    ],
                  ),
                ),
              ],
            ),
          ),

          // Card body
          Container(
            color: Colors.white,
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Doctor
                if (exam.doctorName != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: Row(
                      children: [
                        Icon(Icons.person_outline_rounded, size: 13, color: AppColors.primaryA50),
                        const SizedBox(width: 4),
                        Text('Examined by: ',
                            style: TextStyle(fontSize: 12, color: AppColors.primaryA50)),
                        Text('Dr. ${exam.doctorName}',
                            style: TextStyle(
                                fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.primary)),
                      ],
                    ),
                  ),

                // Print/Edit buttons row — clinical data itself is always
                // shown below (filled-fields-only by default); the toggle
                // to see the full exam form sits on the data's own header.
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    if (exam.prescriptions.isNotEmpty &&
                        PermissionService.instance.can(Perm.opdPrescriptionPrint)) ...[
                      PressScaleWrapper(
                        onTap: () => Navigator.push(
                          context,
                          appRoute(PrescriptionPrintScreen(
                            exam: exam,
                            patient: _history!.patient,
                            hospital: widget.hospital,
                          )),
                        ),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(AppRadius.sm),
                            border: Border.all(color: AppColors.primaryA18),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.print_outlined, size: 14, color: AppColors.primary),
                              SizedBox(width: 6),
                              Text('Print Rx',
                                  style: TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w700,
                                      color: AppColors.primary)),
                            ],
                          ),
                        ),
                      ),
                    ],
                    // Edit — reopens the same exam form pre-filled with the
                    // saved data, matching web parity. Gated by the same
                    // permission that already guards fresh entry.
                    if (PermissionService.instance.can(
                        isPrimary ? Perm.opdExamPrimary : Perm.opdExamSecondary)) ...[
                      PressScaleWrapper(
                        onTap: () async {
                          final result = await Navigator.push<bool>(
                            context,
                            appRoute(isPrimary
                                ? PrimaryExamScreen(
                                    user: widget.user,
                                    hospital: widget.hospital,
                                    patient: widget.patient,
                                  )
                                : SecondaryExamScreen(
                                    user: widget.user,
                                    hospital: widget.hospital,
                                    patient: widget.patient,
                                  )),
                          );
                          if (result == true && mounted) _loadHistory();
                        },
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(AppRadius.sm),
                            border: Border.all(color: AppColors.primaryA18),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.edit_outlined, size: 14, color: AppColors.primary),
                              const SizedBox(width: 6),
                              Text('Edit',
                                  style: TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w700,
                                      color: AppColors.primary)),
                            ],
                          ),
                        ),
                      ),
                    ],
                    // Filled-only (default, concise) ↔ Full exam form —
                    // tablet parity: clinical data is always shown below,
                    // this just controls how much of it.
                    PressScaleWrapper(
                      onTap: () => setState(() {
                        if (showFull) {
                          _fullExam.remove(exam.id);
                        } else {
                          _fullExam.add(exam.id);
                        }
                      }),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
                        decoration: BoxDecoration(
                          color: showFull ? AppColors.primaryA08 : Colors.white,
                          borderRadius: BorderRadius.circular(AppRadius.sm),
                          border: Border.all(
                              color: showFull ? AppColors.primaryA24 : AppColors.primaryA18),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(
                              showFull ? Icons.compress_rounded : Icons.open_in_full_rounded,
                              size: 14, color: AppColors.primary,
                            ),
                            const SizedBox(width: 6),
                            Text(
                              showFull ? 'Filled Only' : 'Full Exam',
                              style: TextStyle(
                                  fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.primary),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),

                // Clinical data — always shown, density controlled above.
                Padding(
                  padding: const EdgeInsets.only(top: 12),
                  child: buildExamClinicalData(exam, _history!.diagnosisMasters, onlyFilled: !showFull),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }


  // Hospital-timezone-aware (not raw UTC / device-local) — see
  // PATIENT_DATE_TIME_DISPLAY_AUDIT.md.
  String _fmtDate(String? s) => formatDateString(s);

  String _fmtDateTime(DateTime dt) => toDisplayDateTime(dt);

  String _fmtTime(DateTime dt) {
    final local = toHospitalTime(dt);
    final h   = local.hour;
    final min = local.minute.toString().padLeft(2, '0');
    final p   = h >= 12 ? 'PM' : 'AM';
    final h12 = h > 12 ? h - 12 : (h == 0 ? 12 : h);
    return '$h12:$min $p';
  }

  String _fmtFee(double v) =>
      v == v.toInt() ? v.toInt().toString() : v.toStringAsFixed(2);

  // ── Partner hospital history ────────────────────────────────────────────────

  Widget _buildPartnerSection(int idx, PartnerHospitalHistory partner) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: AppColors.primaryA18),
        boxShadow: [BoxShadow(color: AppColors.primaryA06, blurRadius: 8, offset: const Offset(0, 2))],
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Partner hospital header
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: BoxDecoration(
              color: const Color(0xFFEEF3FB),
              border: Border(bottom: BorderSide(color: AppColors.primaryA12)),
            ),
            child: Row(
              children: [
                Container(
                  width: 40, height: 40,
                  decoration: BoxDecoration(
                    color: AppColors.primary,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.business_rounded, color: Colors.white, size: 20),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(partner.hospitalName,
                          style: TextStyle(
                              fontSize: 14, fontWeight: FontWeight.w800, color: AppColors.primary)),
                      if (partner.locationLine.isNotEmpty)
                        Text(partner.locationLine,
                            style: TextStyle(fontSize: 11, color: AppColors.primaryA62)),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppColors.primary,
                    borderRadius: BorderRadius.circular(AppRadius.xl),
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.link_rounded, size: 11, color: Colors.white),
                      SizedBox(width: 4),
                      Text('PARTNER HOSPITAL',
                          style: TextStyle(
                              fontSize: 9, fontWeight: FontWeight.w800, color: Colors.white)),
                    ],
                  ),
                ),
              ],
            ),
          ),
          // Partner exams
          Padding(
            padding: const EdgeInsets.all(12),
            child: partner.exams.isEmpty
                ? Padding(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    child: Center(
                      child: Text('No examination records at this hospital',
                          style: TextStyle(fontSize: 13, color: AppColors.primaryA50)),
                    ),
                  )
                : Column(
                    children: partner.byDate.entries
                        .map((e) => _buildPartnerDateGroup(idx, e.key, e.value, partner.diagnosisMasters))
                        .toList(),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildPartnerDateGroup(int partnerIdx, String date, List<ExamRecord> exams, Map<int, String> dm) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(bottom: 10, top: 4),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                decoration: BoxDecoration(
                  color: AppColors.primary,
                  borderRadius: BorderRadius.circular(AppRadius.xl),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.calendar_today_rounded, size: 11, color: Colors.white),
                    const SizedBox(width: 5),
                    Text(date,
                        style: const TextStyle(
                            fontSize: 11, fontWeight: FontWeight.w800, color: Colors.white)),
                  ],
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Container(
                  height: 2,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(colors: [AppColors.primaryA24, AppColors.primaryA06]),
                    borderRadius: BorderRadius.circular(1),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Text('${exams.length} exam${exams.length > 1 ? 's' : ''}',
                  style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: AppColors.primaryA50)),
            ],
          ),
        ),
        ...exams.map((e) => _buildPartnerExamCard(partnerIdx, e, dm)),
        const SizedBox(height: 8),
      ],
    );
  }

  Widget _buildPartnerExamCard(int partnerIdx, ExamRecord exam, Map<int, String> dm) {
    final isPrimary   = exam.isPrimary;
    final expandKey   = '${partnerIdx}_${exam.id}';
    final showFull    = _partnerFull.contains(expandKey);
    final headerBg    = isPrimary ? AppColors.primaryA08 : const Color(0xFFF3F4F6);
    final headerFg    = isPrimary ? AppColors.primary : Color(0xFF6B7280);
    final label       = isPrimary ? 'Primary Exam' : 'Secondary Exam';
    final icon        = isPrimary ? Icons.assignment_outlined : Icons.remove_red_eye_outlined;

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.primaryA12),
        boxShadow: [BoxShadow(color: AppColors.primaryA06, blurRadius: 6, offset: const Offset(0, 2))],
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            color: headerBg,
            child: Row(
              children: [
                Icon(icon, size: 16, color: headerFg),
                const SizedBox(width: 8),
                Text(label,
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: headerFg)),
                const Spacer(),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(AppRadius.xl),
                    border: Border.all(color: AppColors.primaryA12),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.access_time_rounded, size: 11, color: AppColors.primaryA62),
                      const SizedBox(width: 3),
                      Text(_fmtTime(exam.examinedAt),
                          style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: AppColors.primaryA62)),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Container(
            color: Colors.white,
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (exam.doctorName != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: Row(
                      children: [
                        Icon(Icons.person_outline_rounded, size: 13, color: AppColors.primaryA50),
                        const SizedBox(width: 4),
                        Text('Examined by: ',
                            style: TextStyle(fontSize: 12, color: AppColors.primaryA50)),
                        Text('Dr. ${exam.doctorName}',
                            style: TextStyle(
                                fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.primary)),
                      ],
                    ),
                  ),
                Align(
                  alignment: Alignment.centerRight,
                  child: PressScaleWrapper(
                    onTap: () => setState(() {
                      if (showFull) {
                        _partnerFull.remove(expandKey);
                      } else {
                        _partnerFull.add(expandKey);
                      }
                    }),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
                      decoration: BoxDecoration(
                        color: showFull ? AppColors.primaryA08 : Colors.white,
                        borderRadius: BorderRadius.circular(AppRadius.sm),
                        border: Border.all(color: showFull ? AppColors.primaryA24 : AppColors.primaryA18),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            showFull ? Icons.compress_rounded : Icons.open_in_full_rounded,
                            size: 14, color: AppColors.primary,
                          ),
                          const SizedBox(width: 6),
                          Text(
                            showFull ? 'Filled Only' : 'Full Exam',
                            style: TextStyle(
                                fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.primary),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.only(top: 12),
                  child: buildExamClinicalData(exam, dm, onlyFilled: !showFull),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
