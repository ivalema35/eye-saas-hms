import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/permissions.dart';
import '../models/ot_report_models.dart';
import '../services/auth_service.dart';
import '../utils/app_route.dart';
import '../utils/date_format.dart';
import '../services/ot_report_service.dart';
import '../services/permission_service.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_refresh_indicator.dart';
import '../widgets/skeleton.dart';
import '../widgets/app_record_card.dart';
import 'prescription_print_screen.dart';
import '../utils/app_date_picker.dart';

class OtReportViewerScreen extends StatefulWidget {
  final String type;
  final String label;

  const OtReportViewerScreen({
    super.key,
    required this.type,
    required this.label,
  });

  @override
  State<OtReportViewerScreen> createState() => _OtReportViewerScreenState();
}

class _OtReportViewerScreenState extends State<OtReportViewerScreen> {
  bool _loading = true;
  String? _error;
  OtReportResult? _result;
  bool _exporting = false;
  int? _printingPatientId;

  bool get _isHospitalReport => widget.type == 'hospital-report';

  // Web's server default (resolveDateRange()) is start-of-month → today —
  // mirrored here so the initial view matches, and so the picker has a
  // sensible starting point. See OT_WEB_PARITY_FIX_PRD.md §9.4.
  late DateTime _from = DateTime(DateTime.now().year, DateTime.now().month, 1);
  late DateTime _to = DateTime.now();

  String _fmt(DateTime d) => toIsoDate(d);

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final r = await OtReportService.instance.fetchReport(
        widget.type,
        from: _fmt(_from),
        to: _fmt(_to),
      );
      if (mounted)
        setState(() {
          _result = r;
          _loading = false;
        });
    } catch (e) {
      if (mounted)
        setState(() {
          _error = e.toString().replaceFirst('Exception: ', '');
          _loading = false;
        });
    }
  }

  Future<void> _pickDateRange() async {
    final picked = await showAppDateRangePicker(
      context: context,
      initialDateRange: DateTimeRange(start: _from, end: _to),
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      builder: (ctx, child) => Theme(
        data: ThemeData.light().copyWith(
          colorScheme: ColorScheme.light(primary: AppColors.primary),
        ),
        child: child!,
      ),
    );
    if (picked != null) {
      setState(() {
        _from = picked.start;
        _to = picked.end;
      });
      _load();
    }
  }

  Future<void> _export(String format) async {
    setState(() => _exporting = true);
    try {
      await OtReportService.instance.exportReport(
        widget.type,
        format: format,
        from: _fmt(_from),
        to: _fmt(_to),
      );
      if (mounted)
        showAppSnackBar(
          context,
          '${format.toUpperCase()} opened',
          isSuccess: true,
        );
    } catch (e) {
      if (mounted)
        showAppSnackBar(
          context,
          e.toString().replaceFirst('Exception: ', ''),
          isError: true,
        );
    } finally {
      if (mounted) setState(() => _exporting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(
            Icons.arrow_back_ios_new_rounded,
            color: Colors.white,
            size: 20,
          ),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          widget.label,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 16,
            fontWeight: FontWeight.w800,
          ),
        ),
        actions: [
          if (PermissionService.instance.can(Perm.reportsExport) ||
              PermissionService.instance.can(Perm.otReportsExport)) ...[
            IconButton(
              icon: const Icon(Icons.grid_on_rounded, color: Colors.white),
              tooltip: 'Export Excel',
              onPressed: _exporting ? null : () => _export('excel'),
            ),
            IconButton(
              icon: const Icon(
                Icons.picture_as_pdf_outlined,
                color: Colors.white,
              ),
              tooltip: 'Export PDF',
              onPressed: _exporting ? null : () => _export('pdf'),
            ),
          ],
        ],
      ),
      body: Column(
        children: [
          _buildDateRangeBar(),
          Expanded(
            child: _loading
                ? const AppSkeletonList()
                : _error != null
                ? AppErrorState(message: _error!, onRetry: _load)
                : _buildList(),
          ),
        ],
      ),
    );
  }

  Widget _buildDateRangeBar() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 10, 14, 0),
      child: InkWell(
        onTap: _pickDateRange,
        borderRadius: BorderRadius.circular(AppRadius.md),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(AppRadius.md),
            border: Border.all(color: AppColors.primary.withValues(alpha: 0.1)),
          ),
          child: Row(
            children: [
              Icon(
                Icons.date_range_rounded,
                size: 16,
                color: AppColors.primary,
              ),
              const SizedBox(width: 8),
              Text(
                '${_fmt(_from)}  →  ${_fmt(_to)}',
                style: const TextStyle(
                  fontSize: 12.5,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const Spacer(),
              Text(
                'Change',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: AppColors.primary,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  /// Web (`show.blade.php:64-85`): for `hospital-report`, the last raw row
  /// cell is the patient ID (not a real "Patient ID" column to display) —
  /// it's popped off and used to drive a printer-icon button in place of
  /// the last heading, which is relabeled "Prescription". `hasExam` mirrors
  /// web's check on the Primary/Secondary Exam Time cells (indices 6/7).
  Future<void> _printPrescription(int patientId) async {
    final hospital = AuthService.instance.cachedHospital;
    if (hospital == null) {
      showAppSnackBar(
        context,
        'Hospital info not available. Please re-login.',
        isError: true,
      );
      return;
    }
    setState(() => _printingPatientId = patientId);
    try {
      final (patient, exam) = await OtReportService.instance
          .fetchPrescriptionExam(patientId);
      if (mounted) {
        Navigator.push(
          context,
          appRoute(
            PrescriptionPrintScreen(
              exam: exam,
              patient: patient,
              hospital: hospital,
            ),
          ),
        );
      }
    } catch (e) {
      if (mounted)
        showAppSnackBar(
          context,
          e.toString().replaceFirst('Exception: ', ''),
          isError: true,
        );
    } finally {
      if (mounted) setState(() => _printingPatientId = null);
    }
  }

  /// Renders each row as a card instead of a DataTable — on a phone-width
  /// screen a wide table forces heavy left-right scrolling, whereas a card
  /// list (matching report_channel_screen.dart's `_PatientCard` style) reads
  /// top-to-bottom with no horizontal scroll. First column becomes the
  /// card's bold title (usually the patient/entity name); the rest render
  /// as icon-badge grid fields — see RECORD_CARD_UNIFICATION_PLAN.md Tier 4.
  /// `hospital-report`'s trailing patient-id column stays a
  /// print-prescription action instead of a text field, same logic as
  /// before.
  ///
  /// Columns here are backend-defined and arbitrary (16 different report
  /// types, each with its own headings) — there's no fixed "Phone"/"Doctor"
  /// field to map onto `RecordFieldColors`' semantic buckets. Icons/colors
  /// are assigned by rotating through [_rotIcons]/[_rotColors] by column
  /// index instead: purely for visual distinction between adjacent fields
  /// (matching the rest of the app's visual language), not because the
  /// color means anything for a given column — deliberately different from
  /// `RecordFieldColors`, which IS semantic.
  static const _rotIcons = [
    Icons.badge_outlined,
    Icons.event_outlined,
    Icons.call_outlined,
    Icons.favorite_border_rounded,
    Icons.medical_services_outlined,
    Icons.label_outline_rounded,
    Icons.location_on_outlined,
    Icons.currency_rupee_rounded,
  ];
  static final _rotColors = [
    AppColors.blue,
    AppColors.purple,
    AppColors.green,
    AppColors.teal,
    AppColors.orange,
    AppColors.primary,
    AppColors.redDark,
  ];

  AppRecordField _rotField(int col, String label, String value) =>
      AppRecordField(
        label,
        value,
        icon: _rotIcons[col % _rotIcons.length],
        color: _rotColors[col % _rotColors.length],
      );

  Widget _buildList() {
    final r = _result!;
    if (r.rows.isEmpty) {
      return Center(
        child: Text(
          'No data for ${r.from} – ${r.to}',
          style: const TextStyle(color: AppColors.textSecondary),
        ),
      );
    }

    final headings = r.headings;

    Widget cardFor(List<dynamic> row) {
      if (!_isHospitalReport) {
        final fields = [
          for (var i = 1; i < row.length; i++)
            _rotField(i - 1, headings[i], '${row[i]}'),
        ];
        return AppRecordCard(title: '${row[0]}', fields: fields);
      }

      final cells = List<dynamic>.from(row);
      final patientId = cells.isNotEmpty ? cells.removeLast() : null;
      final hasExam =
          (cells.length > 6 && '${cells[6]}' != '-') ||
          (cells.length > 7 && '${cells[7]}' != '-');
      final pid = patientId is int ? patientId : int.tryParse('$patientId');
      final fields = [
        for (var i = 1; i < cells.length; i++)
          _rotField(i - 1, headings[i], '${cells[i]}'),
      ];

      Widget? trailing;
      if (hasExam && pid != null) {
        trailing = _printingPatientId == pid
            ? const SizedBox(
                width: 16,
                height: 16,
                child: CircularProgressIndicator(strokeWidth: 2),
              )
            : IconButton(
                icon: Icon(
                  Icons.print_outlined,
                  size: 18,
                  color: AppColors.primary,
                ),
                tooltip: 'View Prescription',
                onPressed: () => _printPrescription(pid),
              );
      }
      return AppRecordCard(
        title: '${cells[0]}',
        fields: fields,
        trailing: trailing,
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(14, 8, 14, 0),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(AppRadius.full),
            ),
            child: Text(
              '${r.rows.length} rows',
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w700,
                color: AppColors.primary,
              ),
            ),
          ),
        ),
        Expanded(
          child: AppRefreshIndicator(
            onRefresh: _load,
            child: ListView.separated(
              padding: const EdgeInsets.all(14),
              itemCount: r.rows.length,
              separatorBuilder: (_, _) => const SizedBox(height: 10),
              itemBuilder: (_, i) => cardFor(r.rows[i]),
            ),
          ),
        ),
      ],
    );
  }
}
