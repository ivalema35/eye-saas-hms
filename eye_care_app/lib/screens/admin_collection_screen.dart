import 'package:flutter/material.dart';
import '../widgets/app_refresh_indicator.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/auth_models.dart';
import '../models/dashboard_models.dart';
import '../services/dashboard_service.dart';
import '../utils/app_route.dart';
import '../utils/currency_format.dart';
import '../utils/date_format.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_record_card.dart';
import '../widgets/skeleton.dart';
import '../utils/app_date_picker.dart';

/// Web/API parity: Hospital Admin's dashboard "Total Collection" card drill-
/// down — `AdminCollectionController::index` / `GET /dashboard/collection`.
/// One row per receptionist (patients seen + fees collected) for the
/// selected date range, defaulting to today.
///
/// Redesigned 2026-09-30 — the previous version (one huge gradient hero
/// card + big custom row widgets) was a one-off layout unlike anything else
/// in this app. Rebuilt onto this app's own standard record-list convention
/// (`AppRecordCard`/`AppRecordField`, the same card shape every other list
/// screen in this app already uses — OT Appointments, Patients, etc.) with
/// a slim single-row stat strip instead of one oversized hero card.
class AdminCollectionScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;

  const AdminCollectionScreen({
    super.key,
    required this.user,
    required this.hospital,
  });

  @override
  State<AdminCollectionScreen> createState() => _AdminCollectionScreenState();
}

class _AdminCollectionScreenState extends State<AdminCollectionScreen> {
  CollectionSummary? _data;
  bool _loading = true;
  String? _error;
  DateTime _start = DateTime.now();
  DateTime _end = DateTime.now();

  String get _symbol => widget.hospital.currencySymbol;

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
      final data = await DashboardService.instance.fetchCollectionSummary(
        startDate: toIsoDate(_start),
        endDate: toIsoDate(_end),
      );
      if (!mounted) return;
      setState(() {
        _data = data;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = e.toString().replaceFirst('Exception: ', '');
      });
    }
  }

  Future<void> _pickDateRange() async {
    final picked = await showAppDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      initialDateRange: DateTimeRange(start: _start, end: _end),
    );
    if (picked == null) return;
    setState(() {
      _start = picked.start;
      _end = picked.end;
    });
    _load();
  }

  String get _rangeLabel {
    final s = toIsoDate(_start);
    final e = toIsoDate(_end);
    return s == e ? (s == toIsoDate(DateTime.now()) ? 'Today' : s) : '$s → $e';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          'Total Collection',
          style: TextStyle(
            fontWeight: FontWeight.w900,
            fontSize: 18,
            color: Colors.white,
          ),
        ),
        actions: [
          IconButton(
            onPressed: _pickDateRange,
            tooltip: 'Change date range',
            icon: const Icon(Icons.date_range_rounded),
          ),
        ],
      ),
      body: _loading
          ? const AppSkeletonList(count: 6, itemHeight: 66)
          : _error != null
          ? AppErrorState(message: _error!, onRetry: _load)
          : AppRefreshIndicator(
              color: AppColors.primary,
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(14, 12, 14, 20),
                children: [
                  _statStrip(),
                  const SizedBox(height: 14),
                  Row(
                    children: [
                      Text(
                        'By Receptionist',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                          color: AppColors.primary,
                        ),
                      ),
                      const Spacer(),
                      Text(
                        _rangeLabel,
                        style: const TextStyle(
                          fontSize: 10.5,
                          color: AppColors.textSecondary,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  if ((_data?.rows ?? []).isEmpty)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 32),
                      child: Center(
                        child: Text(
                          'No collection recorded for this range',
                          style: TextStyle(
                            color: AppColors.textSecondary,
                            fontSize: 13,
                          ),
                        ),
                      ),
                    )
                  else
                    ..._data!.rows.map(_row),
                ],
              ),
            ),
    );
  }

  Widget _statStrip() {
    final b = _data?.breakdown;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Expanded(
            child: _stat(
              'Grand Total',
              formatMoney(_data?.grandTotal ?? 0, _symbol),
              AppColors.primary,
            ),
          ),
          _statDivider(),
          Expanded(
            child: _stat(
              'OPD',
              formatMoney(b?.opd ?? 0, _symbol),
              AppColors.blue,
            ),
          ),
          _statDivider(),
          Expanded(
            child: _stat(
              'OT Net',
              formatMoney(b?.otNet ?? 0, _symbol),
              AppColors.purple,
            ),
          ),
        ],
      ),
    );
  }

  Widget _statDivider() => Container(
    width: 1,
    height: 30,
    color: AppColors.primaryA08,
    margin: const EdgeInsets.symmetric(horizontal: 10),
  );

  Widget _stat(String label, String value, Color color) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label.toUpperCase(),
          style: TextStyle(
            fontSize: 9,
            fontWeight: FontWeight.w800,
            color: AppColors.textSecondary,
            letterSpacing: 0.3,
          ),
        ),
        const SizedBox(height: 3),
        Text(
          value,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: TextStyle(
            fontSize: 13.5,
            fontWeight: FontWeight.w900,
            color: color,
          ),
        ),
      ],
    );
  }

  Widget _row(CollectionRow r) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppRecordCard(
        title: r.name,
        fields: [
          AppRecordField(
            'Patients',
            '${r.count}',
            icon: Icons.people_rounded,
            color: RecordFieldColors.receptionist,
          ),
          AppRecordField(
            'Collection',
            formatMoney(r.total, _symbol),
            icon: Icons.payments_rounded,
            color: RecordFieldColors.package,
          ),
        ],
        trailing: const Icon(
          Icons.chevron_right_rounded,
          color: AppColors.textDisabled,
        ),
        onTap: () => Navigator.of(context).push(
          appRoute(
            AdminCollectionDetailScreen(
              user: widget.user,
              hospital: widget.hospital,
              receptionId: r.id,
              receptionName: r.name,
              start: _start,
              end: _end,
            ),
          ),
        ),
      ),
    );
  }
}

/// Per-receptionist "Registered Patients" list —
/// `AdminCollectionController::show` / `GET /dashboard/collection/{id}`.
/// Redesigned 2026-09-30 alongside [AdminCollectionScreen] — same
/// `AppRecordCard` convention, replacing the previous one-off New/Old/Other
/// bucket cards (which had no web equivalent at all).
class AdminCollectionDetailScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final int receptionId;
  final String receptionName;
  final DateTime start;
  final DateTime end;

  const AdminCollectionDetailScreen({
    super.key,
    required this.user,
    required this.hospital,
    required this.receptionId,
    required this.receptionName,
    required this.start,
    required this.end,
  });

  @override
  State<AdminCollectionDetailScreen> createState() =>
      _AdminCollectionDetailScreenState();
}

class _AdminCollectionDetailScreenState
    extends State<AdminCollectionDetailScreen> {
  CollectionDetail? _data;
  bool _loading = true;
  String? _error;
  bool _exporting = false;
  // Web pull 2026-09-30 — the detail page has its own Date Range picker +
  // "Today" reset (`admin_collection_show.blade.php`'s `.acs-hero-tools`),
  // independent of whatever range the index screen was on. Was previously a
  // one-time `widget.start`/`widget.end` with no way to change it here.
  late DateTime _start = widget.start;
  late DateTime _end = widget.end;

  String get _symbol => widget.hospital.currencySymbol;

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
      final data = await DashboardService.instance.fetchCollectionDetail(
        widget.receptionId,
        startDate: toIsoDate(_start),
        endDate: toIsoDate(_end),
      );
      if (!mounted) return;
      setState(() {
        _data = data;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = e.toString().replaceFirst('Exception: ', '');
      });
    }
  }

  Future<void> _pickDateRange() async {
    final picked = await showAppDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      initialDateRange: DateTimeRange(start: _start, end: _end),
    );
    if (picked == null) return;
    setState(() {
      _start = picked.start;
      _end = picked.end;
    });
    _load();
  }

  void _resetToday() {
    setState(() {
      _start = DateTime.now();
      _end = DateTime.now();
    });
    _load();
  }

  Future<void> _export() async {
    setState(() => _exporting = true);
    try {
      await DashboardService.instance.downloadCollectionExport(
        widget.receptionId,
        startDate: toIsoDate(_start),
        endDate: toIsoDate(_end),
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

  bool get _isSingleDay => toIsoDate(_start) == toIsoDate(_end);

  String get _rangeLabel {
    final s = toIsoDate(_start);
    final e = toIsoDate(_end);
    return s == e ? (s == toIsoDate(DateTime.now()) ? 'Today' : s) : '$s → $e';
  }

  Color _toneColor(String tone) => switch (tone) {
    'warning' => AppColors.orange,
    'info' => AppColors.blue,
    'purple' => AppColors.purple,
    'teal' => AppColors.teal,
    'success' => AppColors.green,
    'danger' => AppColors.red,
    'primary' => AppColors.primary,
    _ => AppColors.textSecondary,
  };

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: Text(
          widget.receptionName,
          style: const TextStyle(
            fontWeight: FontWeight.w900,
            fontSize: 18,
            color: Colors.white,
          ),
        ),
        actions: [
          IconButton(
            onPressed: _pickDateRange,
            tooltip: 'Change date range',
            icon: const Icon(Icons.date_range_rounded),
          ),
          IconButton(
            onPressed: _resetToday,
            tooltip: 'Today',
            icon: const Icon(Icons.today_rounded),
          ),
          _exporting
              ? const Padding(
                  padding: EdgeInsets.all(14),
                  child: SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Colors.white,
                    ),
                  ),
                )
              : IconButton(
                  onPressed: (_data?.count ?? 0) == 0 ? null : _export,
                  tooltip: 'Export',
                  icon: const Icon(Icons.file_download_outlined),
                  disabledColor: Colors.white38,
                ),
        ],
      ),
      body: _loading
          ? const AppSkeletonList(count: 6, itemHeight: 76)
          : _error != null
          ? AppErrorState(message: _error!, onRetry: _load)
          : AppRefreshIndicator(
              color: AppColors.primary,
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(14, 12, 14, 20),
                children: [
                  _headerStrip(),
                  const SizedBox(height: 14),
                  Text(
                    'Registered Patients',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: AppColors.primary,
                    ),
                  ),
                  const SizedBox(height: 8),
                  if ((_data?.patients ?? const []).isEmpty)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 32),
                      child: Center(
                        child: Text(
                          'No patients registered by ${widget.receptionName} for $_rangeLabel.',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            color: AppColors.textSecondary,
                            fontSize: 13,
                          ),
                        ),
                      ),
                    )
                  else
                    ...(_data!.patients.map(_patientCard)),
                ],
              ),
            ),
    );
  }

  Widget _headerStrip() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      decoration: BoxDecoration(
        color: AppColors.primary.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(AppRadius.lg),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'TOTAL COLLECTED',
            style: TextStyle(
              fontSize: 10.5,
              fontWeight: FontWeight.w800,
              color: AppColors.textSecondary,
              letterSpacing: 0.5,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            formatMoney(_data?.total ?? 0, _symbol),
            style: TextStyle(
              fontSize: 30,
              fontWeight: FontWeight.w900,
              color: AppColors.primary,
              height: 1.1,
            ),
          ),
          const SizedBox(height: 6),
          Row(
            children: [
              Icon(
                Icons.event_rounded,
                size: 13,
                color: AppColors.textSecondary,
              ),
              const SizedBox(width: 4),
              Text(
                _rangeLabel,
                style: const TextStyle(
                  fontSize: 11.5,
                  fontWeight: FontWeight.w600,
                  color: AppColors.textSecondary,
                ),
              ),
              const SizedBox(width: 10),
              Icon(
                Icons.people_alt_rounded,
                size: 13,
                color: AppColors.textSecondary,
              ),
              const SizedBox(width: 4),
              Text(
                '${_data?.count ?? 0} ${(_data?.count ?? 0) == 1 ? 'patient' : 'patients'}',
                style: const TextStyle(
                  fontSize: 11.5,
                  fontWeight: FontWeight.w600,
                  color: AppColors.textSecondary,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _patientCard(CollectionDetailPatient p) {
    final tone = _toneColor(p.stageTone);
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppRecordCard(
        title: p.name.isNotEmpty ? p.name : '—',
        subtitle: [
          p.patientCode,
          if (!_isSingleDay && p.appointmentDate != null)
            formatPlainDate(p.appointmentDate),
        ].whereType<String>().join(' · '),
        fields: [
          AppRecordField(
            'Age/Gender',
            '${p.age ?? '—'}${p.gender != null ? ' / ${p.gender![0].toUpperCase()}' : ''}',
            icon: Icons.cake_rounded,
            color: RecordFieldColors.age,
          ),
          AppRecordField(
            'Case Type',
            p.caseType ?? '—',
            icon: Icons.category_rounded,
            color: RecordFieldColors.caseType,
          ),
          AppRecordField(
            'Doctor',
            p.doctor != null ? 'Dr. ${p.doctor}' : '—',
            icon: Icons.medical_services_rounded,
            color: RecordFieldColors.doctor,
          ),
          AppRecordField(
            'Fee',
            formatMoney(p.caseFee, _symbol),
            icon: Icons.payments_rounded,
            color: RecordFieldColors.package,
          ),
        ],
        trailing: Column(
          crossAxisAlignment: CrossAxisAlignment.end,
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
              decoration: BoxDecoration(
                color: tone.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(AppRadius.full),
              ),
              child: Text(
                p.stageLabel,
                style: TextStyle(
                  fontSize: 9.5,
                  fontWeight: FontWeight.w700,
                  color: tone,
                ),
              ),
            ),
            if (p.stageSub != null && p.stageSub!.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 3),
                child: Text(
                  p.stageSub!,
                  style: const TextStyle(
                    fontSize: 9,
                    color: AppColors.textSecondary,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
