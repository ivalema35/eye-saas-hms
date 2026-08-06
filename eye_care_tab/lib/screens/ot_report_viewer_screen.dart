import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/ot_report_models.dart';
import '../services/ot_report_service.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_error_state.dart';

/// Tablet OT report viewer — full pushed route. Ported from
/// eye_care_app/lib/screens/ot_report_viewer_screen.dart.
class OtReportViewerScreen extends StatefulWidget {
  final String type;
  final String label;

  const OtReportViewerScreen({super.key, required this.type, required this.label});

  @override
  State<OtReportViewerScreen> createState() => _OtReportViewerScreenState();
}

class _OtReportViewerScreenState extends State<OtReportViewerScreen> {
  bool _loading = true;
  String? _error;
  OtReportResult? _result;
  bool _exporting = false;

  // Web's server default (resolveDateRange()) is start-of-month → today —
  // mirrored here so the initial view matches, and so the picker has a
  // sensible starting point. See OT_WEB_PARITY_FIX_PRD.md §9.4.
  late DateTime _from = DateTime(DateTime.now().year, DateTime.now().month, 1);
  late DateTime _to = DateTime.now();

  String _fmt(DateTime d) => '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final r = await OtReportService.instance.fetchReport(widget.type, from: _fmt(_from), to: _fmt(_to));
      if (mounted) setState(() { _result = r; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  Future<void> _pickDateRange() async {
    final picked = await showDateRangePicker(
      context: context,
      initialDateRange: DateTimeRange(start: _from, end: _to),
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      builder: (ctx, child) => Theme(data: ThemeData.light().copyWith(colorScheme: ColorScheme.light(primary: AppColors.primary)), child: child!),
    );
    if (picked != null) {
      setState(() { _from = picked.start; _to = picked.end; });
      _load();
    }
  }

  Future<void> _export(String format) async {
    setState(() => _exporting = true);
    try {
      await OtReportService.instance.exportReport(widget.type, format: format, from: _fmt(_from), to: _fmt(_to));
      if (mounted) showAppSnackBar(context, '${format.toUpperCase()} opened', isSuccess: true);
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    } finally {
      if (mounted) setState(() => _exporting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFEBF5FB),
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        elevation: 0,
        leading: IconButton(icon: const Icon(Icons.arrow_back_rounded, color: Colors.white), onPressed: () => Navigator.of(context).pop()),
        title: Text(widget.label, style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800)),
        actions: [
          IconButton(icon: const Icon(Icons.grid_on_rounded, color: Colors.white), tooltip: 'Export Excel', onPressed: _exporting ? null : () => _export('excel')),
          IconButton(icon: const Icon(Icons.picture_as_pdf_outlined, color: Colors.white), tooltip: 'Export PDF', onPressed: _exporting ? null : () => _export('pdf')),
        ],
      ),
      body: Column(children: [
        _buildDateRangeBar(),
        Expanded(
          child: _loading
              ? Center(child: CircularProgressIndicator(color: AppColors.primary))
              : _error != null
                  ? AppErrorState(message: _error!, onRetry: _load)
                  : _buildTable(),
        ),
      ]),
    );
  }

  Widget _buildDateRangeBar() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 14, 20, 0),
      child: InkWell(
        onTap: _pickDateRange,
        borderRadius: BorderRadius.circular(AppRadius.md),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primaryA08)),
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

  Widget _buildTable() {
    final r = _result!;
    if (r.rows.isEmpty) {
      return Center(child: Text('No data for ${r.from} – ${r.to}', style: const TextStyle(color: AppColors.textSecondary)));
    }
    return Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
      Padding(
        padding: const EdgeInsets.fromLTRB(20, 10, 20, 0),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
          decoration: BoxDecoration(color: AppColors.primaryA08, borderRadius: BorderRadius.circular(AppRadius.full)),
          child: Text('${r.rows.length} rows', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.primary)),
        ),
      ),
      Expanded(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          scrollDirection: Axis.horizontal,
          child: DataTable(
            headingRowColor: WidgetStateProperty.all(AppColors.primaryA06),
            columns: r.headings.map((h) => DataColumn(label: Text(h, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12)))).toList(),
            rows: r.rows.map((row) => DataRow(cells: row.map((c) => DataCell(Text('$c', style: const TextStyle(fontSize: 12)))).toList())).toList(),
          ),
        ),
      ),
    ]);
  }
}
