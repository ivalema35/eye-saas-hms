import 'package:flutter/material.dart';
import '../widgets/app_refresh_indicator.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/ot_accountant_models.dart';
import '../models/ot_booking_models.dart';
import '../utils/currency_format.dart';
import '../utils/date_format.dart';
import '../widgets/skeleton.dart';
import '../services/ot_counsellor_service.dart';
import '../utils/app_route.dart';
import '../utils/ot_status_style.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_pagination_bar.dart';
import '../widgets/app_record_card.dart';
import '../utils/app_date_picker.dart';
import 'ot_counselling_form_screen.dart';

Future<void> showOtCounsellorBookingView(
  BuildContext context,
  OtBookingSummary item,
) => _OtCounsellorDashboardScreenState.openView(context, item);

/// Round 3 Phase 1 — Counsellor Dashboard. Web has **two** tables on this
/// screen (OT_WEB_PARITY_FIX_PRD.md §3): "Awaiting Counselling" (tappable,
/// this API's own queue) and read-only "Payment Status" (bookings from
/// `paid` onward — informational only, no action, since auto-verification
/// is server-side). Payment Status uses `filter=payments` on the counsellor
/// bookings API, the same list the Counselling role is allowed to see.
class OtCounsellorDashboardScreen extends StatefulWidget {
  const OtCounsellorDashboardScreen({super.key});

  @override
  State<OtCounsellorDashboardScreen> createState() =>
      _OtCounsellorDashboardScreenState();
}

class _OtCounsellorDashboardScreenState
    extends State<OtCounsellorDashboardScreen> {
  List<OtBookingSummary> _pendingBookings = [];
  List<OtBookingSummary> _completeBookings = [];
  List<OtBookingSummary> _paymentStatus = [];
  bool _loading = true;
  String? _error;

  // All three lists are independently paginated — the service/API already
  // supported page/per_page + meta (matching the other 4 OT desk screens),
  // this screen just never wired it up, so anything past the first page
  // silently never showed.
  OtPaginationMeta? _bookingMeta;
  int _bookingPage = 1;
  OtPaginationMeta? _completeMeta;
  int _completePage = 1;
  OtPaginationMeta? _psMeta;
  int _psPage = 1;

  // Web pull 2026-10-05: the Queue/All toggle was removed — three sections
  // now show simultaneously (Booking Patient / Complete Patient / Payment
  // Status), each with its own independent date range (defaults to today,
  // widenable). Matches `OtCounsellorController::dashboard()`'s
  // `booking_from_date`/`booking_to_date`, `from_date`/`to_date`,
  // `pay_from_date`/`pay_to_date` exactly.
  DateTime _bookingFromDate = DateTime.now();
  DateTime _bookingToDate = DateTime.now();
  DateTime _completeFromDate = DateTime.now();
  DateTime _completeToDate = DateTime.now();
  DateTime _payFromDate = DateTime.now();
  DateTime _payToDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    _load();
  }

  void _setBookingDateRange(DateTimeRange r) {
    setState(() {
      _bookingFromDate = r.start;
      _bookingToDate = r.end;
      _bookingPage = 1;
    });
    _load();
  }

  void _setCompleteDateRange(DateTimeRange r) {
    setState(() {
      _completeFromDate = r.start;
      _completeToDate = r.end;
      _completePage = 1;
    });
    _load();
  }

  void _setPayDateRange(DateTimeRange r) {
    setState(() {
      _payFromDate = r.start;
      _payToDate = r.end;
      _psPage = 1;
    });
    _load();
  }

  void _setBookingPage(int p) {
    setState(() => _bookingPage = p);
    _load();
  }

  void _setCompletePage(int p) {
    setState(() => _completePage = p);
    _load();
  }

  void _setPsPage(int p) {
    setState(() => _psPage = p);
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final booking = await OtCounsellorService.instance.fetchBookings(
        filter: 'queue',
        fromDate: toIsoDate(_bookingFromDate),
        toDate: toIsoDate(_bookingToDate),
        page: _bookingPage,
      );
      final complete = await OtCounsellorService.instance.fetchBookings(
        filter: 'history',
        fromDate: toIsoDate(_completeFromDate),
        toDate: toIsoDate(_completeToDate),
        page: _completePage,
      );
      List<OtBookingSummary> paymentStatus = [];
      OtPaginationMeta? psMeta;
      try {
        final payments = await OtCounsellorService.instance.fetchBookings(
          filter: 'payments',
          fromDate: toIsoDate(_payFromDate),
          toDate: toIsoDate(_payToDate),
          page: _psPage,
        );
        if (payments.filter == 'payments') {
          paymentStatus = payments.items;
          psMeta = payments.meta;
        }
      } catch (_) {
        paymentStatus = [];
      }
      if (mounted) {
        setState(() {
          _pendingBookings = booking.items;
          _bookingMeta = booking.meta;
          _completeBookings = complete.items;
          _completeMeta = complete.meta;
          _paymentStatus = paymentStatus;
          _psMeta = psMeta;
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted)
        setState(() {
          _error = e.toString().replaceFirst('Exception: ', '');
          _loading = false;
        });
    }
  }

  void _counsel(OtBookingSummary item) {
    final name = item.patient?.fullName ?? 'Patient';
    Navigator.of(context)
        .push(
          appRoute(
            OtCounsellingFormScreen(bookingId: item.id, patientName: name),
          ),
        )
        .then((_) => _load());
  }

  static String _drName(String? name) {
    final n = name?.trim();
    if (n == null || n.isEmpty) return '';
    return RegExp(r'^dr\b\.?', caseSensitive: false).hasMatch(n) ? n : 'Dr. $n';
  }

  // Web: `'at' => $patient?->primary_done_at ?? $primary?->examined_at`; the
  // meta line only ever renders once `done` is true (`primary_done_at`
  // non-null), so `doneAt` is what actually gets shown.
  static String? _examMeta(DateTime? doneAt, String? doctorName) {
    if (doneAt == null) return null;
    final atText = toDisplayDateTime(doneAt);
    final by = _drName(doctorName);
    return by.isEmpty ? atText : '$atText · $by';
  }

  // Web parity (web pull 2026-09-30, `_patient-view-modal.blade.php`) — the
  // "View" button's read-only detail: Examination progress, full
  // Registration Details, and Surgery — replaces the old 7-row flat list
  // (which included OT Doctor/OT Date, since removed from web's own list).
  Future<void> _showBookingView(OtBookingSummary item) =>
      showOtCounsellorBookingView(context, item);

  // Redesigned as a bottom sheet — a centered AlertDialog with a plain
  // label/value list read as a settings screen, not a patient record, and
  // gave every one of 14 fields equal visual weight with no grouping beyond
  // an uppercase caption. 2026 mobile guidance for a "view more detail"
  // surface favors a draggable bottom sheet (thumb-reachable, dismissible by
  // swipe, a drag handle + explicit close icon instead of a buried text
  // link) with content broken into clearly bounded card sections and
  // scannable icon-tagged fields rather than a flat list — the same
  // AppFieldGrid/AppRecordField language the rest of the app's record lists
  // already use, so this detail view doesn't look like a different app.
  static Future<void> openView(
    BuildContext context,
    OtBookingSummary item,
  ) async {
    final p = item.patient;

    Widget examTile(String label, String? doneAtText) {
      final done = doneAtText != null;
      return Container(
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(
          color: done
              ? AppColors.green.withValues(alpha: 0.08)
              : AppColors.surfaceFill,
          borderRadius: BorderRadius.circular(AppRadius.sm),
          border: Border.all(
            color: done
                ? AppColors.green.withValues(alpha: 0.25)
                : AppColors.primaryA10,
          ),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(
              done ? Icons.check_circle_rounded : Icons.access_time_rounded,
              size: 16,
              color: done ? AppColors.green : AppColors.textDisabled,
            ),
            const SizedBox(width: 7),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    done ? doneAtText : 'Not done yet',
                    style: TextStyle(
                      fontSize: 10,
                      color: done
                          ? AppColors.textSecondary
                          : AppColors.textDisabled,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
          ],
        ),
      );
    }

    final typeLabel = switch (p?.type) {
      'phone' => 'Phone Booking',
      'walkin' => 'Walk-in',
      _ =>
        (p?.type ?? '').isEmpty
            ? '—'
            : p!.type[0].toUpperCase() + p.type.substring(1),
    };
    final ageGender = [
      if (p?.age != null) '${p!.age}y',
      if (p?.gender != null) p!.genderDisplay,
    ].join(' / ');

    final registrationFields = <AppRecordField>[
      AppRecordField(
        'MRD No.',
        (p?.patientCode.isNotEmpty ?? false) ? p!.patientCode : '—',
        icon: Icons.badge_outlined,
        color: AppColors.primary,
      ),
      if (p?.contactNo != null)
        AppRecordField(
          'Phone',
          p!.contactNo!,
          icon: Icons.call_outlined,
          color: RecordFieldColors.phone,
        ),
      if (p?.whatsappNo != null)
        AppRecordField(
          'WhatsApp',
          p!.whatsappNo!,
          icon: Icons.chat_outlined,
          color: RecordFieldColors.phone,
        ),
      if (ageGender.isNotEmpty)
        AppRecordField(
          'Age / Gender',
          ageGender,
          icon: Icons.cake_outlined,
          color: RecordFieldColors.age,
        ),
      if (p?.location?.city != null)
        AppRecordField(
          'City',
          p!.location!.city,
          icon: Icons.location_on_outlined,
          color: RecordFieldColors.location,
        ),
      if (p?.occupation != null)
        AppRecordField(
          'Occupation',
          p!.occupation!,
          icon: Icons.work_outline_rounded,
          color: AppColors.textSecondary,
        ),
      AppRecordField(
        'Visit Type',
        typeLabel + ((p?.isOldPatient ?? false) ? ' · Old patient' : ''),
        icon: Icons.call_outlined,
        color: RecordFieldColors.caseType,
      ),
      if (formatDateString(p?.appointmentDate, fallback: '').isNotEmpty)
        AppRecordField(
          'Visit Date',
          formatDateString(p?.appointmentDate, fallback: '—'),
          icon: Icons.event_outlined,
          color: RecordFieldColors.date,
        ),
      if (_drName(p?.doctor?.name).isNotEmpty)
        AppRecordField(
          'Consulting Doctor',
          _drName(p?.doctor?.name),
          icon: Icons.person_outline_rounded,
          color: RecordFieldColors.doctor,
        ),
      if (p?.caseType?.caseType != null)
        AppRecordField(
          'Case Type',
          p!.caseType!.caseType,
          icon: Icons.category_outlined,
          color: RecordFieldColors.caseType,
        ),
      if (p?.caseFee != null)
        AppRecordField(
          'Case Fee',
          '${currentCurrencySymbol()}${p!.caseFee!.toStringAsFixed(2)}',
          icon: Icons.currency_rupee_rounded,
          color: RecordFieldColors.package,
        ),
      if (p?.referrer?.name != null)
        AppRecordField(
          'Referred By',
          p!.referrer!.name,
          icon: Icons.person_add_alt_1_outlined,
          color: AppColors.textSecondary,
        ),
      if (p?.receptionistName != null)
        AppRecordField(
          'Registered By',
          p!.receptionistName!,
          icon: Icons.how_to_reg_outlined,
          color: RecordFieldColors.receptionist,
        ),
      if (p?.createdAt != null)
        AppRecordField(
          'Registered At',
          toDisplayDateTime(p!.createdAt!),
          icon: Icons.schedule_rounded,
          color: RecordFieldColors.date,
        ),
    ];
    final surgeryFields = <AppRecordField>[
      if (item.eye != null)
        AppRecordField(
          'Eye',
          item.eye!,
          icon: Icons.remove_red_eye_outlined,
          color: RecordFieldColors.caseType,
        ),
      if (item.otType != null)
        AppRecordField(
          'Surgery Type',
          item.otType!,
          icon: Icons.medical_services_outlined,
          color: RecordFieldColors.caseType,
        ),
    ];

    await showAppDetailSheet(
      context,
      name: p?.fullName ?? 'Patient',
      code: p?.patientCode,
      sections: [
        AppDetailSectionCard(
          title: 'Examination',
          icon: Icons.fact_check_outlined,
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: examTile(
                  'Primary Exam',
                  _examMeta(
                    p?.primaryDoneAt,
                    p?.primaryExamination?.doctorName,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: examTile(
                  'Secondary Exam',
                  _examMeta(
                    p?.secondaryDoneAt,
                    p?.secondaryExamination?.doctorName,
                  ),
                ),
              ),
            ],
          ),
        ),
        AppDetailSectionCard(
          title: 'Registration Details',
          icon: Icons.badge_outlined,
          child: AppFieldGrid(fields: registrationFields),
        ),
        if (surgeryFields.isNotEmpty)
          AppDetailSectionCard(
            title: 'Surgery',
            icon: Icons.local_hospital_outlined,
            child: AppFieldGrid(fields: surgeryFields),
          ),
      ],
    );
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
          'OT Counsellor',
          style: TextStyle(
            color: Colors.white,
            fontSize: 18,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.2,
          ),
        ),
      ),
      body: _loading
          ? const AppSkeletonList()
          : _error != null
          ? AppErrorState(message: _error!, onRetry: _load)
          : AppRefreshIndicator(
              color: AppColors.primary,
              onRefresh: _load,
              child: _buildBody(),
            ),
    );
  }

  Widget _buildBody() {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 20),
      children: [
        _SectionLabel(
          'Booking Patient',
          dateFrom: _bookingFromDate,
          dateTo: _bookingToDate,
          onDateRangeChanged: _setBookingDateRange,
        ),
        const SizedBox(height: 8),
        if (_pendingBookings.isEmpty)
          const _EmptyRow('No bookings awaiting counselling in this date range.')
        else
          ..._pendingBookings.map((item) => _bookingCard(item, section: _CounsellorSection.booking)),
        if (_bookingMeta != null)
          Padding(
            padding: const EdgeInsets.only(top: 8),
            child: AppPaginationBar(
              currentPage: _bookingMeta!.currentPage,
              totalPages: _bookingMeta!.lastPage,
              onPageChange: _setBookingPage,
            ),
          ),
        const SizedBox(height: 20),
        _SectionLabel(
          'Complete Patient',
          dateFrom: _completeFromDate,
          dateTo: _completeToDate,
          onDateRangeChanged: _setCompleteDateRange,
        ),
        const SizedBox(height: 8),
        if (_completeBookings.isEmpty)
          const _EmptyRow('No bookings in this date range.')
        else
          ..._completeBookings.map((item) => _bookingCard(item, section: _CounsellorSection.complete)),
        if (_completeMeta != null)
          Padding(
            padding: const EdgeInsets.only(top: 8),
            child: AppPaginationBar(
              currentPage: _completeMeta!.currentPage,
              totalPages: _completeMeta!.lastPage,
              onPageChange: _setCompletePage,
            ),
          ),
        const SizedBox(height: 20),
        _SectionLabel(
          'Payment Status',
          subtitle: 'Bookings billing has taken payment on',
          dateFrom: _payFromDate,
          dateTo: _payToDate,
          onDateRangeChanged: _setPayDateRange,
        ),
        const SizedBox(height: 8),
        if (_paymentStatus.isEmpty)
          const _EmptyRow('No bookings paid yet.')
        else
          ..._paymentStatus.map((item) => _bookingCard(item, section: _CounsellorSection.payments)),
        if (_psMeta != null)
          Padding(
            padding: const EdgeInsets.only(top: 8),
            child: AppPaginationBar(
              currentPage: _psMeta!.currentPage,
              totalPages: _psMeta!.lastPage,
              onPageChange: _setPsPage,
            ),
          ),
      ],
    );
  }

  // Migrated onto the shared AppRecordCard/AppRecordField icon-badge grid —
  // see RECORD_CARD_UNIFICATION_PLAN.md Tier 1 §1. Web pull 2026-10-05
  // removed the Queue/All toggle — "Booking Patient" (pending) now always
  // shows BOTH View and Counsel actions together (no longer conditional on
  // an active tab), "Complete Patient" is View-only, and "Payment Status"
  // stays read-only.
  Widget _bookingCard(OtBookingSummary item, {required _CounsellorSection section}) {
    final recommended = item.otStatus == OtStatus.surgeryRecommended;
    final payments = section == _CounsellorSection.payments;
    final booking = section == _CounsellorSection.booking;

    Widget statusPill(String label, Color color) => Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(AppRadius.sm),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w700,
          color: color,
        ),
      ),
    );

    final trailing = payments
        // This is the read-only "Payment Status" table — was comparing
        // `item.otStatus` (the booking's OT-stage status) instead of
        // `item.paymentStatus` (unpriced/pending/partially_paid/paid), and
        // showed a made-up word "Complete" that doesn't exist on web at all
        // (web always says "Paid"/"Partially Paid"/"Pending").
        ? statusPill(
            paymentStatusLabel(item.paymentStatus ?? 'pending'),
            paymentStatusColor(item.paymentStatus ?? 'pending'),
          )
        : booking && recommended
        ? statusPill('Recommended', AppColors.orange)
        : statusPill(OtStatus.label(item.otStatus), otStatusColor(item.otStatus));

    // Web parity (web pull 2026-10-05, `counsellor/dashboard.blade.php`) —
    // OT Doctor and a Date column were removed from web's list entirely
    // (Booking Patient, Complete Patient, and the Payment Status table).
    final fields = <AppRecordField>[
      if (item.patient?.contactNo != null)
        AppRecordField(
          'Phone',
          item.patient!.contactNo!,
          icon: Icons.call_outlined,
          color: RecordFieldColors.phone,
        ),
      if (!payments) ...[
        if (item.otType != null)
          AppRecordField(
            'Surgery Type',
            item.otType!,
            icon: Icons.label_outline_rounded,
            color: RecordFieldColors.caseType,
          ),
      ] else if (item.packageAmount != null)
        AppRecordField(
          'Package Amount',
          item.packageAmount!.toStringAsFixed(0),
          icon: Icons.currency_rupee_rounded,
          color: RecordFieldColors.package,
        ),
    ];

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AppRecordCard(
        title: item.patient?.fullName ?? 'Patient',
        subtitle:
            '${item.patient?.patientCode ?? ''}${item.eye != null ? '  ·  ${item.eye}' : ''}',
        trailing: trailing,
        fields: fields,
        borderColor: booking && recommended
            ? AppColors.orange.withValues(alpha: 0.35)
            : null,
        actions: payments
            ? null
            : Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  viewIconButton(() => _showBookingView(item)),
                  if (booking)
                    FilledButton.icon(
                      onPressed: () => _counsel(item),
                      style: miniFilledStyle(AppColors.primary),
                      icon: const Icon(Icons.forum_rounded, size: 15),
                      label: const Text('Counsel'),
                    ),
                ],
              ),
      ),
    );
  }
}

enum _CounsellorSection { booking, complete, payments }

class _SectionLabel extends StatelessWidget {
  final String title;
  final String? subtitle;
  final DateTime dateFrom;
  final DateTime dateTo;
  final ValueChanged<DateTimeRange> onDateRangeChanged;
  const _SectionLabel(
    this.title, {
    this.subtitle,
    required this.dateFrom,
    required this.dateTo,
    required this.onDateRangeChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w800,
                  color: AppColors.darkNavy,
                ),
              ),
              if (subtitle != null)
                Text(
                  subtitle!,
                  style: const TextStyle(
                    fontSize: 11,
                    color: AppColors.textSecondary,
                  ),
                ),
            ],
          ),
        ),
        const SizedBox(width: 8),
        _LightDateChip(from: dateFrom, to: dateTo, onChanged: onDateRangeChanged),
      ],
    );
  }
}

/// Light-background counterpart to [OtDeskFilterBar]'s white-on-dark date
/// chip — this screen's 3 sections sit on the page's plain `backgroundAlt`
/// body, not a colored AppBar/panel surface, so the chip needs dark text on
/// a tinted fill instead.
class _LightDateChip extends StatelessWidget {
  final DateTime from;
  final DateTime to;
  final ValueChanged<DateTimeRange> onChanged;
  const _LightDateChip({required this.from, required this.to, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    final sameDay = from.year == to.year && from.month == to.month && from.day == to.day;
    final label = sameDay ? toDisplayDate(from) : '${toDisplayDate(from)} – ${toDisplayDate(to)}';
    return Material(
      color: AppColors.primaryA08,
      borderRadius: BorderRadius.circular(AppRadius.sm),
      child: InkWell(
        borderRadius: BorderRadius.circular(AppRadius.sm),
        onTap: () async {
          final now = DateTime.now();
          final picked = await showAppDateRangePicker(
            context: context,
            firstDate: DateTime(now.year - 3),
            lastDate: now.add(const Duration(days: 1)),
            initialDateRange: DateTimeRange(start: from, end: to),
          );
          if (picked != null) onChanged(picked);
        },
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.calendar_today_rounded, size: 12, color: AppColors.primary),
              const SizedBox(width: 6),
              Text(
                label,
                style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700).copyWith(color: AppColors.primary),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _EmptyRow extends StatelessWidget {
  final String message;
  const _EmptyRow(this.message);

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 16),
    child: Center(
      child: Text(
        message,
        style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
      ),
    ),
  );
}
