import 'package:flutter/material.dart';
import '../widgets/app_refresh_indicator.dart';
import '../constants/app_colors.dart';
import '../models/ot_booking_models.dart';
import '../services/ot_discharge_service.dart';
import '../utils/date_format.dart';
import '../utils/app_route.dart';
import '../utils/ot_status_style.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_record_card.dart';
import '../widgets/desk_home_panel.dart';
import '../widgets/skeleton.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_pagination_bar.dart';
import '../widgets/ot_desk_filter_bar.dart';
import '../widgets/status_badge.dart';
import 'ot_discharge_detail_screen.dart';

/// Round 3 Phase 6 — Discharge & Invoices. Operated/discharged queue.
class OtDischargeDashboardScreen extends StatefulWidget {
  const OtDischargeDashboardScreen({super.key});

  @override
  State<OtDischargeDashboardScreen> createState() =>
      _OtDischargeDashboardScreenState();
}

class _OtDischargeDashboardScreenState
    extends State<OtDischargeDashboardScreen> {
  List<OtBookingSummary> _items = [];
  OtPaginationMeta? _meta;
  bool _loading = true;
  String? _error;
  int _page = 1;

  // Web pull 2026-09-19: Queue/All toggle — 'queue' (Billing Desk) or
  // 'history' (Discharged Patients, date-ranged).
  String _filter = 'queue';
  DateTime _fromDate = DateTime.now();
  DateTime _toDate = DateTime.now();

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
      // Web pull 2026-10-05 (commit 65865858) — both tabs default to today
      // and accept the same date filter, not just History.
      final result = await OtDischargeService.instance.fetchBookings(
        filter: _filter,
        fromDate: toIsoDate(_fromDate),
        toDate: toIsoDate(_toDate),
        page: _page,
      );
      if (mounted)
        setState(() {
          _items = result.items;
          _meta = result.meta;
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

  void _setFilter(String f) {
    if (f == _filter) return;
    setState(() {
      _filter = f;
      _page = 1;
    });
    _load();
  }

  void _setDateRange(DateTimeRange r) {
    setState(() {
      _fromDate = r.start;
      _toDate = r.end;
      _page = 1;
    });
    _load();
  }

  void _open(OtBookingSummary item) {
    final name = item.patient?.fullName ?? 'Patient';
    Navigator.of(context)
        .push(
          appRoute(
            OtDischargeDetailScreen(bookingId: item.id, patientName: name),
          ),
        )
        .then((_) => _load());
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
        title: const Text(
          'Discharge & Invoices',
          style: TextStyle(
            color: Colors.white,
            fontSize: 17,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.2,
          ),
        ),
        bottom: OtDeskFilterBar(
          options: const [
            OtFilterOption('queue', 'Queue'),
            OtFilterOption('history', 'All'),
          ],
          selected: _filter,
          onChanged: _setFilter,
          // Web pull 2026-10-05 (commit 65865858) — date range shows on
          // both tabs now, not just All/History.
          showDateRange: true,
          from: _fromDate,
          to: _toDate,
          onDateRangeChanged: _setDateRange,
        ),
      ),
      body: Column(
        children: [
          Expanded(child: _buildBody()),
          if (_meta != null)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: AppPaginationBar(
                currentPage: _meta!.currentPage,
                totalPages: _meta!.lastPage,
                onPageChange: (p) {
                  setState(() => _page = p);
                  _load();
                },
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) return const AppSkeletonList();
    if (_error != null) return AppErrorState(message: _error!, onRetry: _load);

    return Column(
      children: [
        _buildListHeader(),
        Expanded(
          child: _items.isEmpty
              ? AppEmptyState(
                  message: _filter == 'history'
                      ? 'No discharged patients yet.'
                      : 'No records available for billing.',
                  icon: Icons.receipt_long_rounded,
                  onRefresh: _load,
                )
              : AppRefreshIndicator(
                  color: AppColors.primary,
                  onRefresh: _load,
                  child: ListView.builder(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(14, 0, 14, 20),
                    itemCount: _items.length,
                    itemBuilder: (_, i) => AnimatedListItem(
                      index: i,
                      child: _bookingCard(_items[i]),
                    ),
                  ),
                ),
        ),
      ],
    );
  }

  Widget _buildListHeader() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _filter == 'history'
                          ? 'Discharged Patients'
                          : 'Billing Desk',
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w800,
                        color: AppColors.darkNavy,
                      ),
                    ),
                    Text(
                      _filter == 'history'
                          ? 'Completed discharges — view details and reprint documents.'
                          : 'Generate invoices and print discharge documents.',
                      style: const TextStyle(
                        fontSize: 11.5,
                        color: AppColors.textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
              if (_meta != null)
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: AppColors.primary.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    '${_meta!.total} total',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: AppColors.primary,
                    ),
                  ),
                ),
            ],
          ),
        ],
      ),
    );
  }

  // Web pull 2026-09-28 (`billing/index.blade.php`): while an invoice exists
  // but the booking isn't discharged yet, the desk list shows a "PRINTS x/3"
  // progress badge in place of the raw status — the raw `ot_status` would
  // just read "OPERATED" the whole time otherwise, which no longer tells
  // staff anything about how close discharge actually is.
  Widget _statusBadge(OtBookingSummary item) => dischargeQueueStatusBadge(item);

  // Client request (2026-10-03): this full screen's rows were a bare
  // name/MRD/date text line, while the exact same data already got the
  // icon-tagged AppRecordCard treatment on the dashboard's compact Discharge
  // desk panel (`DischargeDeskPanel._row` in `desk_home_panels.dart`) —
  // mirrored here (Phone + OT Date as field-grid cells, patient avatar, a
  // Generated/Pending badge alongside them) so "Open full page" doesn't feel
  // like a step down in polish from the panel it opened from. Tapping the
  // card still drills into the actionable Generate/Print screen; the eye
  // icon stays as the separate read-only quick-view web also has.
  Widget _bookingCard(OtBookingSummary item) {
    final hasInvoice = item.hasInvoice ?? false;
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: AppRecordCard(
        onTap: () => _open(item),
        leading: PatientAvatar(name: item.patient?.fullName ?? 'Patient'),
        title: item.patient?.fullName ?? 'Patient',
        subtitle: item.patient?.patientCode,
        trailing: _statusBadge(item),
        fields: [
          AppRecordField(
            'Phone',
            item.patient?.contactNo ?? '—',
            icon: Icons.phone_outlined,
            color: RecordFieldColors.phone,
          ),
          AppRecordField(
            'OT Date',
            _fmtDate(item.surgeryDate),
            icon: Icons.event_outlined,
            color: RecordFieldColors.date,
          ),
        ],
        actions: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            // Web parity (web pull 2026-09-30) — web's row always has a
            // separate read-only "View" button distinct from the
            // Generate/Print actions reached by opening the card itself.
            viewIconButton(
              () => showOtDischargeBookingViewDialog(context, item),
            ),
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                DeskPanelBadge(
                  label: hasInvoice ? 'Generated' : 'Pending',
                  color: hasInvoice ? AppColors.green : AppColors.orange,
                ),
                const SizedBox(width: 6),
                const Icon(
                  Icons.chevron_right_rounded,
                  color: AppColors.textDisabled,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  String _fmtDate(String? iso) {
    if (iso == null) return '—';
    final d = DateTime.tryParse(iso);
    if (d == null) return '—';
    return toDisplayDate(d);
  }
}

/// Extracted (web pull 2026-09-28's "PRINTS x/3" discharge print-gate badge)
/// so the dashboard's compact Discharge desk panel
/// (`lib/widgets/desk_home_panels.dart`) shows the exact same badge/numbers
/// as this full desk screen's own queue list, instead of recomputing it.
Widget dischargeQueueStatusBadge(OtBookingSummary item) {
  final done = item.dischargePrintsDone;
  final total = item.dischargePrintsTotal;
  final hasInvoice = item.hasInvoice ?? false;
  final showPrintsBadge =
      hasInvoice &&
      done != null &&
      total != null &&
      item.otStatus.toLowerCase() != 'discharged';
  if (showPrintsBadge) {
    return StatusBadge(
      label: 'PRINTS $done/$total',
      color: done >= total ? AppColors.green : AppColors.orange,
    );
  }
  return StatusBadge(
    label: item.otStatus.toUpperCase(),
    color: otStatusColor(item.otStatus),
  );
}
