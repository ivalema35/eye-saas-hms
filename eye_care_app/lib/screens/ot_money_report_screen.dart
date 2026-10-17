import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/ot_accountant_models.dart';
import '../services/ot_accountant_service.dart';
import '../utils/app_date_picker.dart';
import '../utils/currency_format.dart';
import '../utils/date_format.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_refresh_indicator.dart';
import '../widgets/skeleton.dart';

/// Web's accountant collection summary opens this: collected vs refunded,
/// then the payment rows and the refund rows.
class OtMoneyReportScreen extends StatefulWidget {
  const OtMoneyReportScreen({super.key});

  @override
  State<OtMoneyReportScreen> createState() => _OtMoneyReportScreenState();
}

class _OtMoneyReportScreenState extends State<OtMoneyReportScreen> {
  OtMoneyReport? _report;
  bool _loading = true;
  String? _error;
  bool _customRange = false;
  DateTime _start = DateTime.now();
  DateTime _end = DateTime.now();
  // Which section's Export button is currently downloading — 'payments',
  // 'refunds', or null. Only one section's icon shows a spinner at a time,
  // matching web's two independent Export buttons.
  String? _exportingSection;

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
      final report = await OtAccountantService.instance.fetchMoneyReport(
        startDate: _customRange ? toIsoDate(_start) : null,
        endDate: _customRange ? toIsoDate(_end) : null,
      );
      if (!mounted) return;
      setState(() {
        _report = report;
        final start = DateTime.tryParse(report.startDate);
        final end = DateTime.tryParse(report.endDate);
        if (start != null) _start = start;
        if (end != null) _end = end;
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

  Future<void> _export(String section) async {
    setState(() => _exportingSection = section);
    try {
      await OtAccountantService.instance.downloadMoneyExport(
        section: section,
        startDate: _customRange ? toIsoDate(_start) : null,
        endDate: _customRange ? toIsoDate(_end) : null,
      );
    } catch (e) {
      if (mounted) {
        showAppSnackBar(
          context,
          e.toString().replaceFirst('Exception: ', ''),
          isError: true,
        );
      }
    } finally {
      if (mounted) setState(() => _exportingSection = null);
    }
  }

  Future<void> _pickRange() async {
    final now = DateTime.now();
    final picked = await showAppDateRangePicker(
      context: context,
      firstDate: DateTime(now.year - 3),
      lastDate: DateTime(now.year + 1, 12, 31),
      initialDateRange: DateTimeRange(start: _start, end: _end),
    );
    if (picked == null) return;
    setState(() {
      _customRange = true;
      _start = picked.start;
      _end = picked.end;
    });
    _load();
  }

  String get _rangeLabel {
    final same =
        _start.year == _end.year &&
        _start.month == _end.month &&
        _start.day == _end.day;
    return same
        ? toDisplayDate(_start)
        : '${toDisplayDate(_start)} – ${toDisplayDate(_end)}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      body: Column(
        children: [
          _header(),
          Expanded(
            child: _loading
                ? const AppSkeletonList()
                : _error != null
                ? AppErrorState(message: _error!, onRetry: _load)
                : _body(_report!),
          ),
        ],
      ),
    );
  }

  Widget _header() {
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
          padding: const EdgeInsets.fromLTRB(8, 10, 8, 14),
          child: Row(
            children: [
              IconButton(
                icon: const Icon(
                  Icons.arrow_back_ios_new_rounded,
                  color: Colors.white,
                  size: 20,
                ),
                onPressed: () => Navigator.pop(context),
              ),
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(
                  Icons.trending_up_rounded,
                  color: Colors.white,
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'OT Money Report',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                        letterSpacing: -0.2,
                      ),
                    ),
                    Text(
                      'Collections and refunds · $_rangeLabel',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.8),
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
              TextButton(
                onPressed: _customRange
                    ? () {
                        setState(() => _customRange = false);
                        _load();
                      }
                    : null,
                style: TextButton.styleFrom(
                  foregroundColor: Colors.white,
                  disabledForegroundColor: Colors.white54,
                  visualDensity: VisualDensity.compact,
                ),
                child: const Text(
                  'Today',
                  style: TextStyle(fontWeight: FontWeight.w800, fontSize: 13),
                ),
              ),
              IconButton(
                icon: const Icon(Icons.date_range_rounded, color: Colors.white),
                tooltip: 'Date range',
                onPressed: _pickRange,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _body(OtMoneyReport report) {
    final symbol = currentCurrencySymbol();
    return AppRefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 24),
        children: [
          Row(
            children: [
              Expanded(
                child: _stat(
                  'Collected',
                  '$symbol${report.collected.toStringAsFixed(0)}',
                  const Color(0xFF0D6949),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _stat(
                  'Returned',
                  '$symbol${report.refunded.toStringAsFixed(0)}',
                  const Color(0xFFB45309),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _stat(
                  'Net',
                  '$symbol${report.net.toStringAsFixed(0)}',
                  AppColors.primary,
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          _section(
            'Payments in range',
            'payments',
            report.payments,
            'No payments',
          ),
          const SizedBox(height: 14),
          _section('Refunds in range', 'refunds', report.refunds, 'No refunds'),
        ],
      ),
    );
  }

  Widget _stat(String label, String value, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: color.withValues(alpha: 0.18)),
      ),
      child: Column(
        children: [
          Text(
            value,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w900,
              color: color,
            ),
          ),
          Text(
            label,
            style: const TextStyle(
              fontSize: 10,
              color: AppColors.textSecondary,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }

  Widget _section(
    String title,
    String sectionKey,
    List<OtMoneyLine> rows,
    String empty,
  ) {
    final symbol = currentCurrencySymbol();
    final exporting = _exportingSection == sectionKey;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                title,
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w800,
                  color: AppColors.darkNavy,
                ),
              ),
            ),
            exporting
                ? const Padding(
                    padding: EdgeInsets.all(8),
                    child: SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    ),
                  )
                : IconButton(
                    onPressed: rows.isEmpty ? null : () => _export(sectionKey),
                    tooltip: 'Export',
                    visualDensity: VisualDensity.compact,
                    icon: Icon(
                      Icons.file_download_outlined,
                      size: 20,
                      color: AppColors.primary,
                    ),
                  ),
          ],
        ),
        const SizedBox(height: 8),
        if (rows.isEmpty)
          AppEmptyState(message: empty, icon: Icons.receipt_long_outlined)
        else
          for (var i = 0; i < rows.length; i++)
            AnimatedListItem(
              index: i,
              child: Container(
                margin: const EdgeInsets.only(bottom: 8),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(
                    color: AppColors.primary.withValues(alpha: 0.08),
                  ),
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            rows[i].patient,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w700,
                              color: AppColors.darkNavy,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            '${formatDateTimeString(rows[i].at)} · ${rows[i].mode.isEmpty ? '—' : rows[i].mode}',
                            style: const TextStyle(
                              fontSize: 11.5,
                              color: AppColors.textSecondary,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ),
                    Text(
                      '$symbol${rows[i].amount.toStringAsFixed(2)}',
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        color: AppColors.darkNavy,
                      ),
                    ),
                  ],
                ),
              ),
            ),
      ],
    );
  }
}
