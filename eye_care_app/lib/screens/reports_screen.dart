import 'dart:async';
import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../widgets/app_animations.dart';
import '../constants/permissions.dart';
import '../models/auth_models.dart';
import '../models/report_models.dart';
import '../services/permission_service.dart';
import '../services/report_service.dart';

// ═════════════════════════════════════════════════════════════════════════════
// SCREEN
// ═════════════════════════════════════════════════════════════════════════════

class ReportsScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final VoidCallback? onMenuTap;

  const ReportsScreen({
    super.key,
    required this.user,
    required this.hospital,
    this.onMenuTap,
  });

  @override
  State<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends State<ReportsScreen> {
  ReportResult? _result;
  ReportFilterOptions? _filterOptions;
  bool _loading = false;
  bool _filterOptionsLoading = false;
  String? _error;

  ReportFilter _filter = const ReportFilter();
  int _page = 1;
  bool _filterOpen = false;

  // Temporary filter state (in-panel, uncommitted)
  ReportFilter _pendingFilter = const ReportFilter();

  @override
  void initState() {
    super.initState();
    _load();
    _loadFilterOptions();
  }

  Future<void> _load({int? page}) async {
    final p = page ?? _page;
    setState(() { _loading = true; _error = null; });
    try {
      final result = await ReportService.instance.fetchReport(_filter, page: p);
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

  Future<void> _loadFilterOptions() async {
    setState(() => _filterOptionsLoading = true);
    try {
      final opts = await ReportService.instance.fetchFilterData();
      if (mounted) setState(() { _filterOptions = opts; _filterOptionsLoading = false; });
    } catch (_) {
      if (mounted) setState(() => _filterOptionsLoading = false);
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
    showAppSnackBar(context, 'Preparing ${format.toUpperCase()} export…', duration: const Duration(seconds: 2));
    try {
      await ReportService.instance.downloadAndOpenExport(
        format: format,
        filter: _filter,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).hideCurrentSnackBar();
      showAppSnackBar(context, '${format == 'excel' ? 'Excel' : 'PDF'} opened successfully', isSuccess: true, duration: const Duration(seconds: 2));
    } catch (e) {
      if (!mounted) return;
      showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  Future<void> _pickDateRange() async {
    final now = DateTime.now();
    final range = await showDateRangePicker(
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

  String _fmtDate(DateTime d) =>
      '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

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

  bool get _isReceptionistSelected => _pendingFilter.receptionistId != null;

  @override
  Widget build(BuildContext context) {
    if (!PermissionService.instance.can(Perm.reportsView)) {
      return Scaffold(
        backgroundColor: Color(0xFFF6FAFD),
        appBar: _buildAppBar(),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 80, horizontal: 32),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.lock_outline_rounded, size: 56,
                    color: AppColors.primary.withValues(alpha: 0.25)),
                const SizedBox(height: 16),
                Text('No Access',
                    style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700,
                        color: AppColors.primary.withValues(alpha: 0.50))),
                const SizedBox(height: 6),
                Text('You do not have permission to view reports.',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 13, color: Color(0xFF6B7D93).withValues(alpha: 0.60))),
              ],
            ),
          ),
        ),
      );
    }
    return Scaffold(
      backgroundColor: Color(0xFFF6FAFD),
      appBar: _buildAppBar(),
      body: _buildBody(),
    );
  }

  AppBar _buildAppBar() => AppBar(
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
          'Reports',
          style: TextStyle(fontWeight: FontWeight.w800, fontSize: 18),
        ),
        actions: [
          if (PermissionService.instance.can(Perm.reportsExport)) ...[
            IconButton(
              tooltip: 'Export Excel',
              onPressed: () => _export('excel'),
              icon: const Icon(Icons.table_chart_outlined, size: 22),
            ),
            IconButton(
              tooltip: 'Export PDF',
              onPressed: () => _export('pdf'),
              icon: const Icon(Icons.picture_as_pdf_outlined, size: 22),
            ),
          ],
          IconButton(
            tooltip: 'Refresh',
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

  Widget _buildBody() {
    final result = _result;
    return RefreshIndicator(
      onRefresh: () => _load(page: _page),
      color: AppColors.primary,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(bottom: 90),
        children: [
          // Banner — always visible
          _CollectionBanner(
            total: result?.totalCollection ?? 0.0,
            isLoading: _loading && result == null,
          ),
          // Filter card — always visible
          _FilterCard(
            isOpen: _filterOpen,
            filter: _pendingFilter,
            filterOptions: _filterOptions,
            filterOptionsLoading: _filterOptionsLoading,
            onToggle: () => setState(() {
              _filterOpen = !_filterOpen;
              if (_filterOpen) _pendingFilter = _filter;
            }),
            onApply: _applyFilter,
            onClear: _clearFilter,
            onPickDateRange: _pickDateRange,
            onReceptionistChanged: (id) => setState(
                () => _pendingFilter = _pendingFilter.copyWith(receptionistId: id)),
            onDoctorChanged: (id) => setState(
                () => _pendingFilter = _pendingFilter.copyWith(doctorId: id)),
            onLocationChanged: (id) => setState(
                () => _pendingFilter = _pendingFilter.copyWith(locationId: id)),
            onCaseChanged: (id) => setState(
                () => _pendingFilter = _pendingFilter.copyWith(caseId: id)),
            onTypeChanged: (t) => setState(
                () => _pendingFilter = _pendingFilter.copyWith(type: t)),
            isReceptionistLocked: _isReceptionistSelected,
            displayDateRange: _displayDateRange(_pendingFilter.dateRange),
          ),
          // Content states
          if (_loading && result == null)
            SizedBox(
              height: 260,
              child: Center(child: CircularProgressIndicator(color: AppColors.primary)),
            )
          else if (_error != null && result == null)
            SizedBox(
              height: 260,
              child: Center(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.wifi_off_rounded, size: 52, color: Color(0xFF6B7D93).withValues(alpha: 0.60)),
                      const SizedBox(height: 16),
                      Text(
                        _error!,
                        textAlign: TextAlign.center,
                        style: const TextStyle(color: Color(0xFF6B7D93), fontSize: 14),
                      ),
                      const SizedBox(height: 20),
                      ElevatedButton.icon(
                        onPressed: _load,
                        icon: const Icon(Icons.refresh_rounded, size: 18),
                        label: const Text('Retry'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(14)),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
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
                      Icon(Icons.bar_chart_rounded, size: 48, color: Color(0xFF6B7D93).withValues(alpha: 0.60)),
                      const SizedBox(height: 12),
                      Text(
                        'No records found',
                        style: TextStyle(
                            color: Color(0xFF6B7D93).withValues(alpha: 0.60),
                            fontSize: 15,
                            fontWeight: FontWeight.w600),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'Try adjusting your filters',
                        style: TextStyle(color: Color(0xFF6B7D93).withValues(alpha: 0.60), fontSize: 13),
                      ),
                    ],
                  ),
                ),
              )
            else ...[
              ...result.patients.map(
                (p) => Padding(
                  padding: const EdgeInsets.fromLTRB(14, 0, 14, 10),
                  child: _PatientCard(patient: p),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
                child: _PaginationBar(
                  meta: result.meta,
                  loading: _loading,
                  onPrev: () => _load(page: _page - 1),
                  onNext: () => _load(page: _page + 1),
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
// TOTAL COLLECTION BANNER
// ═════════════════════════════════════════════════════════════════════════════

class _CollectionBanner extends StatelessWidget {
  final double total;
  final bool isLoading;

  const _CollectionBanner({required this.total, required this.isLoading});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.fromLTRB(14, 10, 14, 0),
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [Color(0xFF1F9D55), const Color(0xFF27AE60)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(AppRadius.lg),
        boxShadow: [
          BoxShadow(
            color: Color(0xFF1F9D55).withValues(alpha: 0.28),
            blurRadius: 14,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.20),
              borderRadius: BorderRadius.circular(AppRadius.md),
            ),
            child: const Icon(Icons.account_balance_wallet_rounded, color: Colors.white, size: 22),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Walk-in Collection',
                  style: TextStyle(color: Colors.white70, fontSize: 11, fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 2),
                isLoading
                    ? const SizedBox(
                        width: 80,
                        height: 18,
                        child: LinearProgressIndicator(
                          backgroundColor: Colors.white24,
                          color: Colors.white,
                        ),
                      )
                    : Text(
                        '₹${total.toStringAsFixed(2)}',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 22,
                          fontWeight: FontWeight.w900,
                          letterSpacing: -0.5,
                        ),
                      ),
              ],
            ),
          ),
          Text(
            'Current page',
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.65),
              fontSize: 10,
              fontWeight: FontWeight.w600,
            ),
          ),
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
  final bool filterOptionsLoading;
  final VoidCallback onToggle;
  final VoidCallback onApply;
  final VoidCallback onClear;
  final VoidCallback onPickDateRange;
  final ValueChanged<int?> onReceptionistChanged;
  final ValueChanged<int?> onDoctorChanged;
  final ValueChanged<int?> onLocationChanged;
  final ValueChanged<int?> onCaseChanged;
  final ValueChanged<String?> onTypeChanged;
  final bool isReceptionistLocked;
  final String displayDateRange;

  const _FilterCard({
    required this.isOpen,
    required this.filter,
    required this.filterOptions,
    required this.filterOptionsLoading,
    required this.onToggle,
    required this.onApply,
    required this.onClear,
    required this.onPickDateRange,
    required this.onReceptionistChanged,
    required this.onDoctorChanged,
    required this.onLocationChanged,
    required this.onCaseChanged,
    required this.onTypeChanged,
    required this.isReceptionistLocked,
    required this.displayDateRange,
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
          // Header row
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
                    color: hasActive ? AppColors.primary : Color(0xFF6B7D93),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      hasActive ? 'Filters Applied' : 'Filter Report',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: hasActive ? AppColors.primary : Color(0xFF18324A),
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
                    color: Color(0xFF6B7D93),
                    size: 20,
                  ),
                ],
              ),
            ),
          ),
          // Expanded filter panel
          if (isOpen) ...[
            Container(height: 1, color: AppColors.primaryA10),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
              child: filterOptionsLoading
                  ? Center(
                      child: Padding(
                        padding: EdgeInsets.all(12),
                        child: CircularProgressIndicator(color: AppColors.primary, strokeWidth: 2),
                      ),
                    )
                  : _buildFilterForm(),
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
    if (filter.type != null) c++;
    return c;
  }

  Widget _buildFilterForm() {
    final opts = filterOptions;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Date range
        _FilterLabel('Date Range'),
        const SizedBox(height: 6),
        GestureDetector(
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
                      color: filter.dateRange.isNotEmpty ? AppColors.primary : Color(0xFF6B7D93),
                    ),
                  ),
                ),
                if (filter.dateRange.isNotEmpty)
                  GestureDetector(
                    onTap: () => onReceptionistChanged(null), // triggers nothing, date clear is below
                    child: const Icon(Icons.close_rounded, size: 16, color: Color(0xFF6B7D93)),
                  ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 14),

        // Receptionist
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

        // Doctor
        _FilterLabel('Doctor'),
        const SizedBox(height: 6),
        _DropdownField<int>(
          hint: 'All Doctors',
          value: filter.doctorId,
          items: (opts?.doctors ?? [])
              .map((d) => DropdownMenuItem(value: d.id, child: Text(d.name)))
              .toList(),
          onChanged: onDoctorChanged,
        ),
        const SizedBox(height: 14),

        // City / Location (locked when receptionist selected)
        Row(
          children: [
            _FilterLabel('City'),
            if (isReceptionistLocked) ...[
              const SizedBox(width: 6),
              const Icon(Icons.lock_outline_rounded, size: 13, color: Color(0xFF6B7D93)),
            ],
          ],
        ),
        const SizedBox(height: 6),
        IgnorePointer(
          ignoring: isReceptionistLocked,
          child: Opacity(
            opacity: isReceptionistLocked ? 0.42 : 1.0,
            child: _DropdownField<int>(
              hint: 'All Cities',
              value: filter.locationId,
              items: (opts?.locations ?? [])
                  .map((l) => DropdownMenuItem(value: l.id, child: Text(l.name)))
                  .toList(),
              onChanged: onLocationChanged,
            ),
          ),
        ),
        const SizedBox(height: 14),

        // Case Type (locked when receptionist selected)
        Row(
          children: [
            _FilterLabel('Case Type'),
            if (isReceptionistLocked) ...[
              const SizedBox(width: 6),
              const Icon(Icons.lock_outline_rounded, size: 13, color: Color(0xFF6B7D93)),
            ],
          ],
        ),
        const SizedBox(height: 6),
        IgnorePointer(
          ignoring: isReceptionistLocked,
          child: Opacity(
            opacity: isReceptionistLocked ? 0.42 : 1.0,
            child: _DropdownField<int>(
              hint: 'All Case Types',
              value: filter.caseId,
              items: (opts?.caseTypes ?? [])
                  .map((c) => DropdownMenuItem(value: c.id, child: Text(c.name)))
                  .toList(),
              onChanged: onCaseChanged,
            ),
          ),
        ),
        const SizedBox(height: 14),

        // Patient Type
        _FilterLabel('Patient Type'),
        const SizedBox(height: 6),
        _DropdownField<String>(
          hint: 'All Types',
          value: filter.type,
          items: const [
            DropdownMenuItem(value: 'walkin', child: Text('Walk-in')),
            DropdownMenuItem(value: 'phone', child: Text('Phone')),
          ],
          onChanged: onTypeChanged,
        ),
        const SizedBox(height: 18),

        // Action buttons
        Row(
          children: [
            Expanded(
              child: OutlinedButton.icon(
                onPressed: onClear,
                icon: const Icon(Icons.clear_rounded, size: 16),
                label: const Text('Clear'),
                style: OutlinedButton.styleFrom(
                  foregroundColor: Color(0xFF6B7D93),
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
                label: const Text('Apply Filter'),
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

class _PatientCard extends StatelessWidget {
  final ReportPatient patient;

  const _PatientCard({required this.patient});

  @override
  Widget build(BuildContext context) {
    final isWalkin = patient.typeLabel == 'Walk-in';
    final tagColor = isWalkin ? Color(0xFF1F9D55) : AppColors.primary;
    final tagBg = isWalkin ? Color(0xFF1F9D55).withValues(alpha: 0.12) : AppColors.primaryA12;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.primaryA10),
        boxShadow: [BoxShadow(color: AppColors.primaryA06, blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Row 1: Code + Name + Type tag
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      patient.patientCode,
                      style: const TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF6B7D93),
                        letterSpacing: 0.5,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      patient.fullName,
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF18324A),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                decoration: BoxDecoration(
                  color: tagBg,
                  borderRadius: BorderRadius.circular(AppRadius.xl),
                ),
                child: Text(
                  patient.typeLabel,
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w800,
                    color: tagColor,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          // Row 2: Date + Age
          Row(
            children: [
              _Chip(icon: Icons.calendar_today_outlined, text: patient.appointmentDate),
              const SizedBox(width: 12),
              if (patient.age != null)
                _Chip(icon: Icons.person_outline_rounded, text: '${patient.age} yrs'),
            ],
          ),
          const SizedBox(height: 7),
          // Row 3: Doctor + Case type
          Row(
            children: [
              if (patient.doctorName != null)
                Expanded(
                  child: _Chip(
                    icon: Icons.medical_services_outlined,
                    text: patient.doctorName!,
                    maxWidth: true,
                  ),
                ),
              if (patient.doctorName != null && patient.caseTypeLabel != '-')
                const SizedBox(width: 12),
              if (patient.caseTypeLabel != '-')
                _Chip(icon: Icons.label_outline_rounded, text: patient.caseTypeLabel),
            ],
          ),
          const SizedBox(height: 7),
          // Row 4: Receptionist + Location
          Row(
            children: [
              if (patient.receptionistName != null)
                Expanded(
                  child: _Chip(
                    icon: Icons.support_agent_rounded,
                    text: patient.receptionistName!,
                    maxWidth: true,
                  ),
                ),
              if (patient.receptionistName != null && patient.locationCity != null)
                const SizedBox(width: 12),
              if (patient.locationCity != null)
                _Chip(icon: Icons.location_on_outlined, text: patient.locationCity!),
            ],
          ),
          // Row 5: Fee (only if walk-in)
          if (isWalkin && patient.caseFee > 0) ...[
            const SizedBox(height: 10),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: Color(0xFF1F9D55).withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(AppRadius.sm),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.currency_rupee_rounded, size: 13, color: Color(0xFF1F9D55)),
                  const SizedBox(width: 3),
                  Text(
                    patient.caseFee.toStringAsFixed(2),
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF1F9D55),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _Chip extends StatelessWidget {
  final IconData icon;
  final String text;
  final bool maxWidth;

  const _Chip({required this.icon, required this.text, this.maxWidth = false});

  @override
  Widget build(BuildContext context) {
    final row = Row(
      mainAxisSize: maxWidth ? MainAxisSize.max : MainAxisSize.min,
      children: [
        Icon(icon, size: 12, color: Color(0xFF6B7D93)),
        const SizedBox(width: 4),
        Flexible(
          child: Text(
            text,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF6B7D93)),
          ),
        ),
      ],
    );
    return row;
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// PAGINATION BAR
// ═════════════════════════════════════════════════════════════════════════════

class _PaginationBar extends StatelessWidget {
  final ReportMeta meta;
  final bool loading;
  final VoidCallback onPrev;
  final VoidCallback onNext;

  const _PaginationBar({
    required this.meta,
    required this.loading,
    required this.onPrev,
    required this.onNext,
  });

  @override
  Widget build(BuildContext context) {
    if (meta.lastPage <= 1) return const SizedBox.shrink();

    return Row(
      children: [
        Expanded(
          child: OutlinedButton.icon(
            onPressed: (meta.hasPrev && !loading) ? onPrev : null,
            icon: const Icon(Icons.arrow_back_ios_rounded, size: 14),
            label: const Text('Prev'),
            style: OutlinedButton.styleFrom(
              foregroundColor: AppColors.primary,
              side: BorderSide(color: AppColors.primaryA10),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 14),
          child: loading
              ? SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(color: AppColors.primary, strokeWidth: 2),
                )
              : Text(
                  '${meta.currentPage} / ${meta.lastPage}',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w800,
                    color: AppColors.primary,
                  ),
                ),
        ),
        Expanded(
          child: OutlinedButton.icon(
            onPressed: (meta.hasNext && !loading) ? onNext : null,
            icon: const Icon(Icons.arrow_forward_ios_rounded, size: 14),
            label: const Text('Next'),
            iconAlignment: IconAlignment.end,
            style: OutlinedButton.styleFrom(
              foregroundColor: AppColors.primary,
              side: BorderSide(color: AppColors.primaryA10),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
          ),
        ),
      ],
    );
  }
}
