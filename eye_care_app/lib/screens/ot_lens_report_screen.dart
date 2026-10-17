import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/permissions.dart';
import '../models/ot_report_models.dart';
import '../services/ot_report_service.dart';
import '../services/permission_service.dart';
import '../utils/date_format.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_record_card.dart';
import '../widgets/app_refresh_indicator.dart';
import '../widgets/skeleton.dart';
import '../utils/app_date_picker.dart';

/// Web pull 2026-10-13 — `OtReportController::lensReport()`. A dedicated
/// page (summary cards + lens-power breakdown), not the generic
/// headings/rows `OtReportViewerScreen` the rest of the OT Reports
/// catalogue uses. Default date range is today-today (web's
/// `resolveLensReportDateRange()`), not the generic reports' start-of-month.
class OtLensReportScreen extends StatefulWidget {
  const OtLensReportScreen({super.key});

  @override
  State<OtLensReportScreen> createState() => _OtLensReportScreenState();
}

class _OtLensReportScreenState extends State<OtLensReportScreen> {
  bool _loading = true;
  String? _error;
  OtLensReportData? _data;
  bool _exporting = false;

  late DateTime _from = DateTime.now();
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
      final r = await OtReportService.instance.fetchLensReport(from: _fmt(_from), to: _fmt(_to));
      if (mounted) setState(() { _data = r; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  Future<void> _pickDateRange() async {
    final picked = await showAppDateRangePicker(
      context: context,
      initialDateRange: DateTimeRange(start: _from, end: _to),
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      builder: (ctx, child) => Theme(
        data: ThemeData.light().copyWith(colorScheme: ColorScheme.light(primary: AppColors.primary)),
        child: child!,
      ),
    );
    if (picked != null) {
      setState(() { _from = picked.start; _to = picked.end; });
      _load();
    }
  }

  Future<void> _export() async {
    setState(() => _exporting = true);
    try {
      await OtReportService.instance.exportLensReport(from: _fmt(_from), to: _fmt(_to));
      if (mounted) showAppSnackBar(context, 'Excel opened', isSuccess: true);
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
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
        leading: IconButton(icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20), onPressed: () => Navigator.pop(context)),
        title: const Text('Lens Report', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800)),
        actions: [
          if (PermissionService.instance.can(Perm.reportsExport) || PermissionService.instance.can(Perm.otReportsExport))
            IconButton(
              icon: const Icon(Icons.grid_on_rounded, color: Colors.white),
              tooltip: 'Export Excel',
              onPressed: _exporting ? null : _export,
            ),
        ],
      ),
      body: Column(children: [
        _buildDateRangeBar(),
        Expanded(
          child: _loading
              ? const AppSkeletonList()
              : _error != null
                  ? AppErrorState(message: _error!, onRetry: _load)
                  : _buildBody(),
        ),
      ]),
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
          child: Row(children: [
            Icon(Icons.date_range_rounded, size: 16, color: AppColors.primary),
            const SizedBox(width: 8),
            Text('${_fmt(_from)}  →  ${_fmt(_to)}', style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700)),
            const Spacer(),
            Text('Change', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.primary)),
          ]),
        ),
      ),
    );
  }

  Widget _summaryCard(String label, String value, IconData icon, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 10),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(AppRadius.md),
          border: Border.all(color: color.withValues(alpha: 0.18)),
        ),
        child: Column(children: [
          Container(
            width: 30, height: 30,
            alignment: Alignment.center,
            decoration: BoxDecoration(color: color.withValues(alpha: 0.12), shape: BoxShape.circle),
            child: Icon(icon, size: 16, color: color),
          ),
          const SizedBox(height: 6),
          Text(value, style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: color)),
          const SizedBox(height: 2),
          Text(label, textAlign: TextAlign.center, style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w600, color: AppColors.textSecondary)),
        ]),
      ),
    );
  }

  Widget _buildBody() {
    final d = _data!;
    return AppRefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 24),
        children: [
          Row(children: [
            _summaryCard('Lenses Used', '${d.summary.totalLensesUsed}', Icons.remove_red_eye_rounded, AppColors.purple),
            const SizedBox(width: 10),
            _summaryCard('Surgeries', '${d.summary.totalSurgeries}', Icons.cut_rounded, AppColors.teal),
            const SizedBox(width: 10),
            _summaryCard('Unique Powers', '${d.summary.uniquePowers}', Icons.blur_circular_rounded, AppColors.blue),
          ]),
          if (d.powerSummary.isNotEmpty) ...[
            const SizedBox(height: 14),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(AppRadius.md),
                border: Border.all(color: AppColors.primary.withValues(alpha: 0.1)),
              ),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('Power Breakdown', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: AppColors.primary)),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: d.powerSummary.map((row) => Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                        decoration: BoxDecoration(color: const Color(0xFFF4F8FB), borderRadius: BorderRadius.circular(AppRadius.full)),
                        child: Text('+${row.power}D × ${row.quantity}', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.primary)),
                      )).toList(),
                ),
              ]),
            ),
          ],
          const SizedBox(height: 14),
          Text('Lens Usage Detail', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: AppColors.primary)),
          const SizedBox(height: 8),
          if (d.detailRows.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 24),
              child: Center(child: Text('No data for ${d.from} – ${d.to}', style: const TextStyle(color: AppColors.textSecondary))),
            )
          else
            ...d.detailRows.map(_detailCard),
        ],
      ),
    );
  }

  static const _rotIcons = [
    Icons.badge_outlined,
    Icons.numbers_rounded,
    Icons.remove_red_eye_outlined,
    Icons.label_outline_rounded,
    Icons.factory_outlined,
    Icons.medical_services_outlined,
  ];
  static final _rotColors = [
    AppColors.blue,
    AppColors.green,
    AppColors.purple,
    AppColors.teal,
    AppColors.orange,
    AppColors.primary,
  ];

  // Columns (web's $detailHeadings): Surgery Date, Patient Name, MRD No.,
  // Lens Power, Lens Name, Lens Company, Doctor — index 1 (Patient Name)
  // becomes the card title, the rest render as fields in order.
  Widget _detailCard(List<dynamic> row) {
    final headings = _data!.detailHeadings;
    final fields = <AppRecordField>[];
    var col = 0;
    for (var i = 0; i < row.length; i++) {
      if (i == 1) continue; // title
      fields.add(AppRecordField(headings[i], '${row[i]}', icon: _rotIcons[col % _rotIcons.length], color: _rotColors[col % _rotColors.length]));
      col++;
    }
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppRecordCard(title: row.length > 1 ? '${row[1]}' : '-', fields: fields),
    );
  }
}
