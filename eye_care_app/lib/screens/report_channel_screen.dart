import 'package:flutter/material.dart';
import '../widgets/app_refresh_indicator.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../utils/date_format.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_pagination_bar.dart';
import '../widgets/app_record_card.dart';
import '../widgets/skeleton.dart';
import '../constants/permissions.dart';
import '../models/report_models.dart';
import '../services/permission_service.dart';
import '../services/report_service.dart';
import '../utils/currency_format.dart';
import '../utils/app_date_picker.dart';

// ═════════════════════════════════════════════════════════════════════════════
// PER-CHANNEL PATIENT LIST — web's channel.blade.php (Report Filters card,
// export buttons, and the patient table for one channel).
// ═════════════════════════════════════════════════════════════════════════════

class ReportChannelScreen extends StatefulWidget {
  final String channel; // 'ot_appointment' | 'walkin' | 'phone'
  final String label;

  const ReportChannelScreen({super.key, required this.channel, required this.label});

  @override
  State<ReportChannelScreen> createState() => _ReportChannelScreenState();
}

class _ReportChannelScreenState extends State<ReportChannelScreen> {
  ReportChannelResult? _result;
  bool _loading = false;
  bool _exporting = false;
  String? _error;

  ReportFilter _filter = const ReportFilter();
  int _page = 1;
  bool _filterOpen = false;

  // Temporary filter state (in-panel, uncommitted)
  ReportFilter _pendingFilter = const ReportFilter();

  bool get _isOtAppointment => widget.channel == 'ot_appointment';

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load({int? page}) async {
    final p = page ?? _page;
    setState(() { _loading = true; _error = null; });
    try {
      final result = await ReportService.instance.fetchChannel(widget.channel, _filter, page: p);
      if (mounted) setState(() { _result = result; _page = p; _loading = false; });
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString().replaceFirst('Exception: ', '');
          _loading = false;
        });
      }
    }
  }

  void _applyFilter() {
    setState(() {
      _filter = _pendingFilter;
      _filterOpen = false;
      _page = 1;
      _result = null;
    });
    _load(page: 1);
  }

  void _clearFilter() {
    setState(() {
      _pendingFilter = const ReportFilter();
      _filter = const ReportFilter();
      _filterOpen = false;
      _page = 1;
      _result = null;
    });
    _load(page: 1);
  }

  Future<void> _export(String format) async {
    setState(() => _exporting = true);
    showAppSnackBar(context, 'Preparing ${format.toUpperCase()} export…', duration: const Duration(seconds: 2));
    try {
      // Web's export buttons on the channel page carry the channel + every
      // applied filter (channel.blade.php:100-105) — mirror that by adding
      // `type` alongside the rest of the committed filter.
      await ReportService.instance.downloadAndOpenExport(
        format: format,
        filter: _filter.copyWith(type: widget.channel == 'ot_appointment' ? 'ot_appointment' : widget.channel),
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).hideCurrentSnackBar();
      showAppSnackBar(context, '${format == 'excel' ? 'Excel' : 'PDF'} opened successfully', isSuccess: true, duration: const Duration(seconds: 2));
    } catch (e) {
      if (!mounted) return;
      showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    } finally {
      if (mounted) setState(() => _exporting = false);
    }
  }

  Future<void> _pickDateRange() async {
    final now = DateTime.now();
    final range = await showAppDateRangePicker(
      context: context,
      firstDate: DateTime(2024),
      lastDate: now.add(const Duration(days: 1)),
      initialDateRange: _pendingFilter.dateRange.isNotEmpty
          ? _parseDateRange(_pendingFilter.dateRange)
          : null,
      builder: (ctx, child) => Theme(
        data: ThemeData.light().copyWith(
          colorScheme: ColorScheme.light(primary: AppColors.primary),
        ),
        child: child!,
      ),
    );
    if (range != null) {
      final s = _fmtDate(range.start);
      final e = _fmtDate(range.end);
      setState(() {
        _pendingFilter = _pendingFilter.copyWith(
          dateRange: s == e ? s : '$s to $e',
        );
      });
    }
  }

  void _clearDateRange() => setState(() => _pendingFilter = _pendingFilter.copyWith(dateRange: ''));

  DateTimeRange? _parseDateRange(String dr) {
    final parts = dr.split(' to ');
    if (parts.length == 2) {
      final s = DateTime.tryParse(parts[0]);
      final e = DateTime.tryParse(parts[1]);
      if (s != null && e != null) return DateTimeRange(start: s, end: e);
    } else if (parts.length == 1) {
      final d = DateTime.tryParse(parts[0]);
      if (d != null) return DateTimeRange(start: d, end: d);
    }
    return null;
  }

  String _fmtDate(DateTime d) => toIsoDate(d);

  String _displayDateRange(String dr) {
    if (dr.isEmpty) return 'All Dates';
    final parts = dr.split(' to ');
    if (parts.length == 2 && parts[0] == parts[1]) return _prettyDate(parts[0]);
    if (parts.length == 2) return '${_prettyDate(parts[0])} – ${_prettyDate(parts[1])}';
    return _prettyDate(dr);
  }

  String _prettyDate(String iso) {
    final d = DateTime.tryParse(iso);
    if (d == null) return iso;
    const m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return '${d.day} ${m[d.month - 1]} ${d.year}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF6FAFD),
      appBar: _buildAppBar(),
      body: _buildBody(),
    );
  }

  AppBar _buildAppBar() => AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: Text(
          widget.label,
          style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16),
        ),
        actions: [
          if (PermissionService.instance.can(Perm.reportsExport) ||
              PermissionService.instance.can(Perm.opdReportsExport)) ...[
            IconButton(
              tooltip: 'Export Excel',
              onPressed: _exporting ? null : () => _export('excel'),
              icon: const Icon(Icons.table_chart_outlined, size: 22),
            ),
            IconButton(
              tooltip: 'Export PDF',
              onPressed: _exporting ? null : () => _export('pdf'),
              icon: const Icon(Icons.picture_as_pdf_outlined, size: 22),
            ),
          ],
          const SizedBox(width: 4),
        ],
      );

  Widget _buildBody() {
    final result = _result;
    return AppRefreshIndicator(
      onRefresh: () => _load(page: _page),
      color: AppColors.primary,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(bottom: 90),
        children: [
          _FilterCard(
            isOpen: _filterOpen,
            filter: _pendingFilter,
            filterOptions: result?.filterOptions,
            isOtAppointment: _isOtAppointment,
            onToggle: () => setState(() {
              _filterOpen = !_filterOpen;
              if (_filterOpen) _pendingFilter = _filter;
            }),
            onApply: _applyFilter,
            onClear: _clearFilter,
            onPickDateRange: _pickDateRange,
            onClearDateRange: _clearDateRange,
            onReceptionistChanged: (id) => setState(
                () => _pendingFilter = _pendingFilter.copyWith(receptionistId: id)),
            onDoctorChanged: (id) => setState(
                () => _pendingFilter = _pendingFilter.copyWith(doctorId: id)),
            onLocationChanged: (id) => setState(
                () => _pendingFilter = _pendingFilter.copyWith(locationId: id)),
            onCaseChanged: (id) => setState(
                () => _pendingFilter = _pendingFilter.copyWith(caseId: id)),
            displayDateRange: _displayDateRange(_pendingFilter.dateRange),
            errorMessage: result == null ? _error : null,
            onRetry: () => _load(),
          ),
          if (_loading && result == null)
            const SizedBox(
              height: 260,
              child: AppSkeletonList(count: 1, itemHeight: 220, padding: EdgeInsets.zero),
            )
          else if (_error != null && result == null)
            SizedBox(
              height: 260,
              child: AppErrorState(message: _error!, onRetry: () => _load()),
            )
          else if (result != null) ...[
            Padding(
              padding: const EdgeInsets.fromLTRB(14, 10, 14, 6),
              child: _SummaryRow(meta: result.meta),
            ),
            if (result.patients.isEmpty)
              SizedBox(
                height: 220,
                child: Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.bar_chart_rounded, size: 48, color: const Color(0xFF6B7D93).withValues(alpha: 0.60)),
                      const SizedBox(height: 12),
                      Text(
                        'No patients found for this channel',
                        style: TextStyle(
                            color: const Color(0xFF6B7D93).withValues(alpha: 0.60),
                            fontSize: 15,
                            fontWeight: FontWeight.w600),
                      ),
                      const SizedBox(height: 6),
                      const Text(
                        'Try adjusting your filters',
                        style: TextStyle(color: Color(0xFF6B7D93), fontSize: 13),
                      ),
                    ],
                  ),
                ),
              )
            else ...[
              ...result.patients.map(
                (p) => Padding(
                  padding: const EdgeInsets.fromLTRB(14, 0, 14, 10),
                  child: _PatientCard(patient: p, isOtAppointment: _isOtAppointment),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
                child: AppPaginationBar(
                  currentPage: result.meta.currentPage,
                  totalPages: result.meta.lastPage,
                  onPageChange: (page) => _load(page: page),
                ),
              ),
            ],
          ],
        ],
      ),
    );
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// FILTER CARD
// ═════════════════════════════════════════════════════════════════════════════

class _FilterCard extends StatelessWidget {
  final bool isOpen;
  final ReportFilter filter;
  final ReportFilterOptions? filterOptions;
  final bool isOtAppointment;
  final VoidCallback onToggle;
  final VoidCallback onApply;
  final VoidCallback onClear;
  final VoidCallback onPickDateRange;
  final VoidCallback onClearDateRange;
  final ValueChanged<int?> onReceptionistChanged;
  final ValueChanged<int?> onDoctorChanged;
  final ValueChanged<int?> onLocationChanged;
  final ValueChanged<int?> onCaseChanged;
  final String displayDateRange;
  final String? errorMessage;
  final VoidCallback onRetry;

  const _FilterCard({
    required this.isOpen,
    required this.filter,
    required this.filterOptions,
    required this.isOtAppointment,
    required this.onToggle,
    required this.onApply,
    required this.onClear,
    required this.onPickDateRange,
    required this.onClearDateRange,
    required this.onReceptionistChanged,
    required this.onDoctorChanged,
    required this.onLocationChanged,
    required this.onCaseChanged,
    required this.displayDateRange,
    required this.errorMessage,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    final hasActive = !filter.isEmpty;

    return Container(
      margin: const EdgeInsets.fromLTRB(14, 10, 14, 0),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: AppColors.primaryA10),
        boxShadow: [BoxShadow(color: AppColors.primaryA06, blurRadius: 10, offset: const Offset(0, 3))],
      ),
      child: Column(
        children: [
          InkWell(
            onTap: onToggle,
            borderRadius: BorderRadius.circular(AppRadius.lg),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 13),
              child: Row(
                children: [
                  Icon(
                    Icons.filter_list_rounded,
                    size: 18,
                    color: hasActive ? AppColors.primary : const Color(0xFF6B7D93),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      hasActive ? 'Filters Applied' : 'Report Filters',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: hasActive ? AppColors.primary : const Color(0xFF18324A),
                      ),
                    ),
                  ),
                  if (hasActive)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: AppColors.primaryA12,
                        borderRadius: BorderRadius.circular(AppRadius.xl),
                      ),
                      child: Text(
                        _activeCount.toString(),
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w800,
                          color: AppColors.primary,
                        ),
                      ),
                    ),
                  const SizedBox(width: 6),
                  Icon(
                    isOpen ? Icons.keyboard_arrow_up_rounded : Icons.keyboard_arrow_down_rounded,
                    color: const Color(0xFF6B7D93),
                    size: 20,
                  ),
                ],
              ),
            ),
          ),
          if (isOpen) ...[
            Container(height: 1, color: AppColors.primaryA10),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
              child: filterOptions != null
                  ? _buildFilterForm()
                  : (errorMessage != null ? _buildFilterError() : _buildFilterLoading()),
            ),
          ],
        ],
      ),
    );
  }

  int get _activeCount {
    int c = 0;
    if (filter.dateRange.isNotEmpty) c++;
    if (filter.receptionistId != null) c++;
    if (filter.doctorId != null) c++;
    if (filter.locationId != null) c++;
    if (filter.caseId != null) c++;
    return c;
  }

  // Filter options ride on the same fetch as the report data — if that
  // fetch fails, filterOptions stays null forever and this card used to
  // spin indefinitely with no way out. Show the real error + a retry
  // instead of an infinite loader.
  Widget _buildFilterLoading() =>
      const AppSkeletonList(count: 3, itemHeight: 60);

  Widget _buildFilterError() => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.error_outline_rounded, size: 26, color: const Color(0xFF6B7D93)),
          const SizedBox(height: 10),
          Text(
            errorMessage ?? 'Failed to load filters',
            style: const TextStyle(fontSize: 13, color: Color(0xFF6B7D93)),
          ),
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh_rounded, size: 16),
              label: const Text('Retry'),
              style: OutlinedButton.styleFrom(
                foregroundColor: AppColors.primary,
                side: BorderSide(color: AppColors.primaryA10),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
          ),
        ],
      );

  Widget _buildFilterForm() {
    final opts = filterOptions;
    final doctorOrAssistantOptions = isOtAppointment ? (opts?.assistants ?? []) : (opts?.doctors ?? []);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _FilterLabel('Date Range'),
        const SizedBox(height: 6),
        PressScaleWrapper(
          onTap: onPickDateRange,
          child: Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: BoxDecoration(
              border: Border.all(color: AppColors.primaryA10),
              borderRadius: BorderRadius.circular(10),
              color: AppColors.primaryA06,
            ),
            child: Row(
              children: [
                const Icon(Icons.date_range_rounded, size: 16, color: Color(0xFF6B7D93)),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    displayDateRange,
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: filter.dateRange.isNotEmpty ? AppColors.primary : const Color(0xFF6B7D93),
                    ),
                  ),
                ),
                if (filter.dateRange.isNotEmpty)
                  GestureDetector(
                    onTap: onClearDateRange,
                    child: const Icon(Icons.close_rounded, size: 16, color: Color(0xFF6B7D93)),
                  ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 14),

        _FilterLabel('Receptionist'),
        const SizedBox(height: 6),
        _DropdownField<int>(
          hint: 'All Receptionists',
          value: filter.receptionistId,
          items: (opts?.receptionists ?? [])
              .map((r) => DropdownMenuItem(value: r.id, child: Text(r.name)))
              .toList(),
          onChanged: onReceptionistChanged,
        ),
        const SizedBox(height: 14),

        _FilterLabel(isOtAppointment ? 'Assistant' : 'Doctor'),
        const SizedBox(height: 6),
        _DropdownField<int>(
          hint: isOtAppointment ? 'All Assistants' : 'All Doctors',
          value: filter.doctorId,
          items: doctorOrAssistantOptions
              .map((d) => DropdownMenuItem(value: d.id, child: Text(d.name)))
              .toList(),
          onChanged: onDoctorChanged,
        ),
        const SizedBox(height: 14),

        _FilterLabel('City / Location'),
        const SizedBox(height: 6),
        _DropdownField<int>(
          hint: 'All Cities',
          value: filter.locationId,
          items: (opts?.locations ?? [])
              .map((l) => DropdownMenuItem(value: l.id, child: Text(l.name)))
              .toList(),
          onChanged: onLocationChanged,
        ),
        const SizedBox(height: 14),

        _FilterLabel('Case Type'),
        const SizedBox(height: 6),
        _DropdownField<int>(
          hint: 'All Case Types',
          value: filter.caseId,
          items: (opts?.caseTypes ?? [])
              .map((c) => DropdownMenuItem(value: c.id, child: Text(c.name)))
              .toList(),
          onChanged: onCaseChanged,
        ),
        const SizedBox(height: 18),

        Row(
          children: [
            Expanded(
              child: OutlinedButton.icon(
                onPressed: onClear,
                icon: const Icon(Icons.clear_rounded, size: 16),
                label: const Text('Clear'),
                style: OutlinedButton.styleFrom(
                  foregroundColor: const Color(0xFF6B7D93),
                  side: BorderSide(color: AppColors.primaryA10),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  padding: const EdgeInsets.symmetric(vertical: 11),
                ),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              flex: 2,
              child: ElevatedButton.icon(
                onPressed: onApply,
                icon: const Icon(Icons.search_rounded, size: 16),
                label: const Text('Generate Report'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  padding: const EdgeInsets.symmetric(vertical: 11),
                  elevation: 0,
                ),
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class _FilterLabel extends StatelessWidget {
  final String text;
  const _FilterLabel(this.text);

  @override
  Widget build(BuildContext context) => Text(
        text,
        style: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: Color(0xFF6B7D93),
          letterSpacing: 0.3,
        ),
      );
}

class _DropdownField<T> extends StatelessWidget {
  final String hint;
  final T? value;
  final List<DropdownMenuItem<T>> items;
  final ValueChanged<T?> onChanged;

  const _DropdownField({
    required this.hint,
    required this.value,
    required this.items,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
      decoration: BoxDecoration(
        border: Border.all(color: AppColors.primaryA10),
        borderRadius: BorderRadius.circular(10),
        color: AppColors.primaryA06,
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<T>(
          value: value,
          hint: Text(hint, style: const TextStyle(fontSize: 13, color: Color(0xFF6B7D93))),
          isExpanded: true,
          dropdownColor: Colors.white,
          borderRadius: BorderRadius.circular(AppRadius.md),
          icon: const Icon(Icons.keyboard_arrow_down_rounded, size: 18, color: Color(0xFF6B7D93)),
          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Color(0xFF18324A)),
          items: [
            DropdownMenuItem<T>(
              value: null,
              child: Text(hint, style: const TextStyle(color: Color(0xFF6B7D93), fontSize: 13)),
            ),
            ...items,
          ],
          onChanged: onChanged,
        ),
      ),
    );
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// SUMMARY ROW
// ═════════════════════════════════════════════════════════════════════════════

class _SummaryRow extends StatelessWidget {
  final ReportMeta meta;

  const _SummaryRow({required this.meta});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Text(
          '${meta.total} record${meta.total == 1 ? '' : 's'}',
          style: const TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: Color(0xFF18324A),
          ),
        ),
        const Spacer(),
        Text(
          'Page ${meta.currentPage} / ${meta.lastPage}',
          style: const TextStyle(fontSize: 12, color: Color(0xFF6B7D93), fontWeight: FontWeight.w600),
        ),
      ],
    );
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// PATIENT CARD
// ═════════════════════════════════════════════════════════════════════════════

/// This card is the ORIGIN of the icon-badge grid pattern (3 design
/// iterations landed here — see RECORD_CARD_UNIFICATION_PLAN.md intro for
/// the full history: plain rows wasted space, fully-colored pills looked
/// like a "rainbow", landed on color living only on a small icon badge with
/// the field's label doing the actual identification work). Now migrated
/// onto the shared `AppRecordCard`/`AppRecordField` (plan §5 step 4) so
/// there's exactly one implementation of this pattern in the codebase
/// instead of this screen's private copy plus the shared one every other
/// screen uses.
class _PatientCard extends StatelessWidget {
  final ReportPatient patient;
  final bool isOtAppointment;

  const _PatientCard({required this.patient, required this.isOtAppointment});

  @override
  Widget build(BuildContext context) {
    return AppRecordCard(
      title: patient.fullName,
      subtitle: '${patient.patientCode}  ·  ${patient.appointmentDate}',
      // Case fees shown for every row on this channel screen, per web's
      // channel.blade.php (unconditional `money()` column) — preserves
      // the amount even when it's zero, matching web exactly.
      trailing: Container(
        padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
        decoration: BoxDecoration(color: const Color(0xFF1F9D55).withValues(alpha: 0.12), borderRadius: BorderRadius.circular(AppRadius.xl)),
        child: Text(
          '${currentCurrencySymbol()}${patient.caseFee.toStringAsFixed(2)}',
          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF1F9D55)),
        ),
      ),
      fields: [
        if (patient.age != null) AppRecordField('Age', '${patient.age} yrs', icon: Icons.cake_outlined, color: RecordFieldColors.age),
        if (patient.contactNo != null) AppRecordField('Phone', patient.contactNo!, icon: Icons.call_outlined, color: RecordFieldColors.phone),
        if (patient.doctorName != null)
          AppRecordField(
            isOtAppointment ? 'Assistant' : 'Doctor',
            patient.doctorName!,
            icon: isOtAppointment ? Icons.support_agent_rounded : Icons.medical_services_outlined,
            color: RecordFieldColors.doctor,
          ),
        if (patient.caseTypeLabel != '-') AppRecordField('Case Type', patient.caseTypeLabel, icon: Icons.label_outline_rounded, color: RecordFieldColors.caseType),
        if (patient.receptionistName != null) AppRecordField('Receptionist', patient.receptionistName!, icon: Icons.badge_outlined, color: RecordFieldColors.receptionist),
        if (patient.locationCity != null) AppRecordField('City', patient.locationCity!, icon: Icons.location_on_outlined, color: RecordFieldColors.location),
      ],
    );
  }
}
