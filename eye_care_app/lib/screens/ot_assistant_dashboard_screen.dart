import 'package:flutter/material.dart';
import '../widgets/app_refresh_indicator.dart';
import '../constants/app_colors.dart';
import '../constants/permissions.dart';
import '../models/auth_models.dart';
import '../models/ot_accountant_models.dart';
import '../models/ot_booking_models.dart';
import '../models/ot_ward_models.dart';
import '../services/ot_assistant_service.dart';
import '../services/permission_service.dart';
import '../utils/app_route.dart';
import '../utils/currency_format.dart';
import '../utils/date_format.dart';
import '../utils/ot_status_style.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_record_card.dart';
import '../widgets/skeleton.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_pagination_bar.dart';
import '../widgets/ot_desk_filter_bar.dart';
import 'ot_surgery_record_screen.dart';

/// Round 3 Phase 4 — Assistant queue (ready-for-surgery bookings). Single
/// "Assistant Dashboard" nav entry, gated by the combined
/// `otLensRecord`/`otLensImplant`/`otPatientList`/`otSurgeryReady`/`otSurgeryRecord`
/// permission set — matches web's one merged nav item (the old separate
/// "Doctor Dashboard (OT)" entry was removed; the backend only ever had one
/// combined queue, `bookings()`, so it showed identical data anyway).
///
/// Matches web exactly (OT_WEB_PARITY_FIX_PRD.md §5.1): the web dashboard
/// shows only a Surgery Queue with a single "Operate" action — lens
/// recording is deliberately hidden from this screen (web code comment:
/// "Lens workflow UI hidden — counselling already captures planned lens").
/// Lens is instead reachable from the Surgery Record screen once a surgery
/// is saved, not from this queue.
class OtAssistantDashboardScreen extends StatefulWidget {
  final UserInfo user;

  const OtAssistantDashboardScreen({super.key, required this.user});

  @override
  State<OtAssistantDashboardScreen> createState() =>
      _OtAssistantDashboardScreenState();
}

class _OtAssistantDashboardScreenState
    extends State<OtAssistantDashboardScreen> {
  List<OtBookingSummary> _items = [];
  OtPaginationMeta? _meta;
  bool _loading = true;
  String? _error;
  int _page = 1;

  // Web pull 2026-09-19: Queue/All toggle — 'queue' (Surgery Queue) or
  // 'history' (Surgery History, date-ranged).
  String _filter = 'queue';
  DateTime _fromDate = DateTime.now();
  DateTime _toDate = DateTime.now();

  bool get _canSurgery => PermissionService.instance.can(Perm.otSurgeryRecord);
  // Matches web's `isSuperUser() || role.slug === 'hospital_admin'` exactly
  // (OtAssistantController::dashboard()) — admins see the Surgeon column
  // and every ready booking, not just their own.
  bool get _seeAll =>
      (widget.user.role?.isSuper ?? false) ||
      widget.user.role?.slug == 'hospital_admin';

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
      final result = await OtAssistantService.instance.fetchBookings(
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

  void _openSurgery(OtBookingSummary item) {
    final name = item.patient?.fullName ?? 'Patient';
    Navigator.of(context)
        .push(
          appRoute(
            OtSurgeryRecordScreen(bookingId: item.id, patientName: name),
          ),
        )
        .then((_) => _load());
  }

  Future<void> _viewDetails(OtBookingSummary item) =>
      showOtAssistantBookingViewDialog(context, item, seeAll: _seeAll);

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
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              _filter == 'history' ? 'Surgery History' : 'Surgery Queue',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 18,
                fontWeight: FontWeight.w800,
                letterSpacing: -0.2,
              ),
            ),
            if (_meta != null)
              Text(
                '${_meta!.total} total',
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.75),
                  fontSize: 11,
                ),
              ),
          ],
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
    if (_items.isEmpty)
      return AppEmptyState(
        message: _filter == 'history'
            ? 'No surgery history yet.'
            : 'No bookings ready for surgery.',
        icon: Icons.handyman_rounded,
        onRefresh: _load,
      );

    return AppRefreshIndicator(
      color: AppColors.primary,
      onRefresh: _load,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 20),
        itemCount: _items.length,
        itemBuilder: (_, i) {
          final item = _items[i];
          // Migrated onto the shared AppRecordCard/AppRecordField icon-badge
          // grid — see RECORD_CARD_UNIFICATION_PLAN.md Tier 1 §4. The old
          // `_infoChip` Wrap (uncolored icon+text pills) is exactly the
          // "before" example the new pattern was built to replace. Payment
          // status is a state pill (not a field identity), so it stays a
          // pill — moved up next to the OT-status pill in the header
          // instead of living inside the field grid.
          return AnimatedListItem(
            index: i,
            child: Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: AppRecordCard(
                title: item.patient?.fullName ?? 'Patient',
                subtitle:
                    '${item.patient?.patientCode ?? ''}${item.eye != null ? '  ·  ${item.eye}' : ''}',
                trailing: Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 3,
                      ),
                      decoration: BoxDecoration(
                        color: otStatusColor(
                          item.otStatus,
                        ).withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        item.otStatus.toUpperCase(),
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.w700,
                          color: otStatusColor(item.otStatus),
                        ),
                      ),
                    ),
                    if (item.paymentStatus != null) ...[
                      const SizedBox(height: 4),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 3,
                        ),
                        decoration: BoxDecoration(
                          color: paymentStatusColor(
                            item.paymentStatus!,
                          ).withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: Text(
                          paymentStatusLabel(item.paymentStatus!),
                          style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w700,
                            color: paymentStatusColor(item.paymentStatus!),
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
                fields: [
                  if (_seeAll && item.otDoctor != null)
                    AppRecordField(
                      'Surgeon',
                      'Dr. ${item.otDoctor!.name}',
                      icon: Icons.medical_services_outlined,
                      color: RecordFieldColors.doctor,
                    ),
                  // Web parity (`assistant/dashboard.blade.php`): OT Date
                  // column only exists on the History/All tab, not Queue.
                  if (_filter == 'history' &&
                      formatPlainDate(item.surgeryDate) != null)
                    AppRecordField(
                      'OT Date',
                      formatPlainDate(item.surgeryDate)!,
                      icon: Icons.event_outlined,
                      color: RecordFieldColors.date,
                    ),
                  if (item.otType != null)
                    AppRecordField(
                      'Surgery Type',
                      item.otType!,
                      icon: Icons.local_hospital_outlined,
                      color: RecordFieldColors.caseType,
                    ),
                  if (item.packageAmount != null)
                    AppRecordField(
                      'Package',
                      item.packageAmount!.toStringAsFixed(0),
                      icon: Icons.currency_rupee_rounded,
                      color: RecordFieldColors.package,
                    ),
                ],
                // Client request (2026-10-03): these were full-width
                // `Expanded(OutlinedButton)` pairs each eating half the row —
                // swapped for the same compact View-icon + mini filled
                // action pairing every other OT desk screen already uses
                // (`OtAssistantDeskPanel._row` in `desk_home_panels.dart`).
                actions: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    viewIconButton(() => _viewDetails(item)),
                    if (_filter != 'history' && _canSurgery)
                      FilledButton.icon(
                        onPressed: () => _openSurgery(item),
                        style: miniFilledStyle(AppColors.primary),
                        icon: const Icon(
                          Icons.medical_services_rounded,
                          size: 15,
                        ),
                        label: const Text('Operate'),
                      ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

String _assistantDrName(String? name) {
  final n = name?.trim();
  if (n == null || n.isEmpty) return '';
  return RegExp(r'^dr\b\.?', caseSensitive: false).hasMatch(n) ? n : 'Dr. $n';
}

(String, Color) _assistantStageStyle(String otStatus) => switch (otStatus) {
  OtStatus.ready => ('Ready for OT', AppColors.primary),
  OtStatus.operated => ('Operated', AppColors.green),
  OtStatus.discharged => ('Discharged', AppColors.textSecondary),
  _ => (OtStatus.label(otStatus), AppColors.textSecondary),
};

Color _assistantPreOpToneColor(String? status) => switch (status) {
  'ready_for_surgery' => AppColors.green,
  'preparing' => AppColors.blue,
  'hold' => AppColors.orange,
  'complicated' || 'not_fit' => AppColors.red,
  _ => AppColors.textDisabled,
};

String _assistantTitle(String? v) {
  if (v == null || v.trim().isEmpty) return '';
  return v
      .split('_')
      .map((w) => w.isEmpty ? w : w[0].toUpperCase() + w.substring(1))
      .join(' ');
}

String _assistantFmtNum(num? v) {
  if (v == null) return '';
  final s = v.toStringAsFixed(2);
  return s.replaceFirst(RegExp(r'\.?0+$'), '');
}

String _assistantFmt12h(DateTime d) {
  final h12 = d.hour % 12 == 0 ? 12 : d.hour % 12;
  final period = d.hour >= 12 ? 'PM' : 'AM';
  return '$h12:${d.minute.toString().padLeft(2, '0')} $period';
}

/// Web parity (web pull 2026-09-30, `_view-modal.blade.php`) — extracted so
/// the dashboard's compact OT Assistant desk panel (see
/// `lib/widgets/desk_home_panels.dart`) shows the exact same rich read-only
/// detail as this full desk screen, instead of a second, shallower copy.
/// Replaces the old 8-fact flat dialog with web's full Surgery Record /
/// Implanted Lens / Surgery Plan / Pre-Op Vitals / Patient Details.
/// [seeAll] mirrors `_OtAssistantDashboardScreenState._seeAll`, though the
/// web modal itself always shows OT Doctor/OT Assistant regardless — kept
/// only because callers already pass it and dropping the parameter would
/// ripple both call sites for no behavioral gain.
Future<void> showOtAssistantBookingViewDialog(
  BuildContext context,
  OtBookingSummary item, {
  required bool seeAll,
}) async {
  final p = item.patient;
  final isReady = item.otStatus == OtStatus.ready;
  final (stageLabel, stageColor) = _assistantStageStyle(item.otStatus);
  final ps = item.paymentStatus;
  final payLabel = ps != null ? paymentStatusLabel(ps) : 'Payment Pending';
  final payColor = paymentStatusColor(ps ?? 'pending');
  final cur = currentCurrencySymbol();
  final preOp = item.preOp;
  final surgery = item.surgery;
  final lens = item.lensDetail;

  final plannedLens = [
    if ((item.counselling?.lensCategory ?? '').isNotEmpty)
      item.counselling!.lensCategory![0].toUpperCase() +
          item.counselling!.lensCategory!.substring(1),
    [
      item.counselling?.lensCompany,
      item.counselling?.lensModel,
    ].where((s) => (s ?? '').isNotEmpty).join(' '),
  ].where((s) => s.isNotEmpty).join(' · ');
  final package = [
    item.counselling?.packageName,
    (item.counselling?.roomCategory.isNotEmpty ?? false)
        ? '${item.counselling!.roomCategory[0].toUpperCase()}${item.counselling!.roomCategory.substring(1)} room'
        : null,
  ].where((s) => (s ?? '').isNotEmpty).join(' · ');
  final mediclaim = item.counselling?.mediclaim ?? item.hasMediclaim;
  final paymentLine =
      '$payLabel · $cur${(item.totalPaid ?? 0).toStringAsFixed(2)} / $cur${(item.packageAmount ?? 0).toStringAsFixed(2)}';

  final patientFields = <AppRecordField>[
    if (p?.patientCode.isNotEmpty ?? false)
      AppRecordField(
        'MRD No.',
        p!.patientCode,
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
    if (p?.age != null || p?.gender != null)
      AppRecordField(
        'Age / Gender',
        [
          if (p?.age != null) '${p!.age}y',
          if (p?.gender != null) p!.genderDisplay,
        ].join(' / '),
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
    if (switch (p?.type) {
      'phone' => true,
      'walkin' => true,
      _ => false,
    })
      AppRecordField(
        'Visit Type',
        p?.type == 'phone' ? 'Phone Booking' : 'Walk-in',
        icon: Icons.call_outlined,
        color: RecordFieldColors.caseType,
      ),
    if (_assistantDrName(p?.doctor?.name).isNotEmpty)
      AppRecordField(
        'Consulting Doctor',
        _assistantDrName(p?.doctor?.name),
        icon: Icons.person_outline_rounded,
        color: RecordFieldColors.doctor,
      ),
    if (p?.receptionistName != null)
      AppRecordField(
        'Registered By',
        p!.receptionistName!,
        icon: Icons.how_to_reg_outlined,
        color: RecordFieldColors.receptionist,
      ),
  ];

  final planFields = <AppRecordField>[
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
    if (item.counselling?.diagnosis != null)
      AppRecordField(
        'Diagnosis',
        item.counselling!.diagnosis!,
        icon: Icons.description_outlined,
        color: RecordFieldColors.caseType,
      ),
    if (plannedLens.isNotEmpty)
      AppRecordField(
        'Planned Lens',
        plannedLens,
        icon: Icons.lens_outlined,
        color: AppColors.textSecondary,
      ),
    if (item.counselling?.lensImplantation != null)
      AppRecordField(
        'Lens Implantation',
        item.counselling!.lensImplantation! ? 'Yes' : 'No',
        icon: Icons.check_circle_outline_rounded,
        color: AppColors.textSecondary,
      ),
    if (package.isNotEmpty)
      AppRecordField(
        'Package',
        package,
        icon: Icons.inventory_2_outlined,
        color: RecordFieldColors.package,
      ),
    if (mediclaim != null)
      AppRecordField(
        'Mediclaim',
        mediclaim ? 'Yes' : 'No',
        icon: Icons.health_and_safety_outlined,
        color: AppColors.textSecondary,
      ),
    AppRecordField(
      'Payment',
      paymentLine,
      icon: Icons.currency_rupee_rounded,
      color: RecordFieldColors.package,
    ),
    if (_assistantDrName(item.otDoctor?.name).isNotEmpty)
      AppRecordField(
        'OT Doctor',
        _assistantDrName(item.otDoctor?.name),
        icon: Icons.person_outline_rounded,
        color: RecordFieldColors.doctor,
      ),
    if (item.otAssistant?.name != null)
      AppRecordField(
        'OT Assistant',
        item.otAssistant!.name,
        icon: Icons.support_agent_outlined,
        color: RecordFieldColors.assistant,
      ),
    if (!isReady && formatPlainDate(item.surgeryDate) != null)
      AppRecordField(
        'OT Date',
        formatPlainDate(item.surgeryDate)!,
        icon: Icons.event_outlined,
        color: RecordFieldColors.date,
      ),
  ];

  final duration = surgery?.durationMinutes;
  final surgeryFields = surgery == null
      ? const <AppRecordField>[]
      : <AppRecordField>[
          if (surgery.surgeryName != null)
            AppRecordField(
              'Surgery',
              surgery.surgeryName!,
              icon: Icons.medical_services_outlined,
              color: RecordFieldColors.caseType,
            ),
          if (surgery.eyeOperated != null)
            AppRecordField(
              'Eye Operated',
              surgery.eyeOperated!,
              icon: Icons.remove_red_eye_outlined,
              color: RecordFieldColors.caseType,
            ),
          if (_assistantDrName(surgery.operatedBy?.name).isNotEmpty)
            AppRecordField(
              'Operated By',
              _assistantDrName(surgery.operatedBy?.name),
              icon: Icons.person_outline_rounded,
              color: RecordFieldColors.doctor,
            ),
          if (surgery.otRoom != null)
            AppRecordField(
              'OT Room',
              surgery.otRoom!,
              icon: Icons.meeting_room_outlined,
              color: AppColors.textSecondary,
            ),
          if (surgery.surgeryAt != null || surgery.startTime != null)
            AppRecordField(
              'Date',
              toDisplayDate((surgery.surgeryAt ?? surgery.startTime)!),
              icon: Icons.event_outlined,
              color: RecordFieldColors.date,
            ),
          if (surgery.startTime != null)
            AppRecordField(
              'Time',
              '${_assistantFmt12h(surgery.startTime!)}${surgery.endTime != null ? ' – ${_assistantFmt12h(surgery.endTime!)}' : ''}',
              icon: Icons.schedule_rounded,
              color: RecordFieldColors.date,
            ),
          if (duration != null)
            AppRecordField(
              'Duration',
              '$duration min',
              icon: Icons.timer_outlined,
              color: RecordFieldColors.date,
            ),
          if (_assistantTitle(surgery.surgeryStatus).isNotEmpty)
            AppRecordField(
              'Status',
              _assistantTitle(surgery.surgeryStatus),
              icon: Icons.flag_outlined,
              color: AppColors.textSecondary,
            ),
          if (_assistantTitle(surgery.complicationStatus).isNotEmpty)
            AppRecordField(
              'Complication',
              _assistantTitle(surgery.complicationStatus),
              icon: Icons.warning_amber_outlined,
              color: AppColors.orange,
            ),
          if (surgery.complicationNotes != null)
            AppRecordField(
              'Complication Notes',
              surgery.complicationNotes!,
              icon: Icons.notes_outlined,
              color: AppColors.textSecondary,
            ),
        ];

  final lensFields = lens == null
      ? const <AppRecordField>[]
      : <AppRecordField>[
          if (lens.lensName != null)
            AppRecordField(
              'Lens',
              lens.lensName!,
              icon: Icons.lens_outlined,
              color: AppColors.textSecondary,
            ),
          if (lens.manufacturer != null)
            AppRecordField(
              'Manufacturer',
              lens.manufacturer!,
              icon: Icons.factory_outlined,
              color: AppColors.textSecondary,
            ),
          if (lens.lensType != null)
            AppRecordField(
              'Type',
              lens.lensType!,
              icon: Icons.category_outlined,
              color: RecordFieldColors.caseType,
            ),
          if (lens.lensPower != null)
            AppRecordField(
              'Power',
              '${_assistantFmtNum(lens.lensPower)} D',
              icon: Icons.bolt_outlined,
              color: AppColors.textSecondary,
            ),
          if (lens.axis != null && lens.axis != 0)
            AppRecordField(
              'Axis',
              '${_assistantFmtNum(lens.axis)}°',
              icon: Icons.explore_outlined,
              color: AppColors.textSecondary,
            ),
          if (lens.serialNumber != null)
            AppRecordField(
              'Serial No.',
              lens.serialNumber!,
              icon: Icons.numbers_rounded,
              color: AppColors.textSecondary,
            ),
        ];

  final vitalsMissing =
      preOp == null ||
      [
        preOp.bp,
        preOp.pulse,
        preOp.rbs?.toString(),
        preOp.temperature?.toString(),
        preOp.spo2?.toString(),
        preOp.hba1c?.toString(),
      ].every((v) => v == null || v.isEmpty);
  final vitalsFields = vitalsMissing
      ? const <AppRecordField>[]
      : <AppRecordField>[
          if (preOp.bp != null)
            AppRecordField(
              'BP',
              preOp.bp!,
              icon: Icons.favorite_outline_rounded,
              color: AppColors.red,
            ),
          if (preOp.pulse != null)
            AppRecordField(
              'Pulse',
              preOp.pulse!,
              icon: Icons.monitor_heart_outlined,
              color: AppColors.red,
            ),
          if (preOp.rbs != null)
            AppRecordField(
              'RBS',
              preOp.rbs.toString(),
              icon: Icons.bloodtype_outlined,
              color: AppColors.purple,
            ),
          if (preOp.temperature != null)
            AppRecordField(
              'Temp',
              preOp.temperature.toString(),
              icon: Icons.thermostat_outlined,
              color: AppColors.orange,
            ),
          if (preOp.spo2 != null)
            AppRecordField(
              'SpO2',
              '${preOp.spo2}%',
              icon: Icons.air_outlined,
              color: AppColors.blue,
            ),
          if (preOp.hba1c != null)
            AppRecordField(
              'HbA1c',
              preOp.hba1c.toString(),
              icon: Icons.science_outlined,
              color: AppColors.purple,
            ),
        ];

  await showAppDetailSheet(
    context,
    name: p?.fullName ?? 'Patient',
    code: 'Booking #${item.id}',
    pills: [
      AppStatusPill(label: stageLabel, color: stageColor),
      AppStatusPill(label: payLabel, color: payColor),
    ],
    sections: [
      if (!isReady) ...[
        AppDetailSectionCard(
          title: 'Surgery Record',
          icon: Icons.fact_check_outlined,
          child: surgeryFields.isEmpty
              ? const Text(
                  'Surgery record not found.',
                  style: TextStyle(
                    fontSize: 12,
                    color: AppColors.textSecondary,
                  ),
                )
              : AppFieldGrid(fields: surgeryFields),
        ),
        if (lensFields.isNotEmpty)
          AppDetailSectionCard(
            title: 'Implanted Lens',
            icon: Icons.lens_outlined,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (lens?.isImplanted ?? false)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: AppStatusPill(
                      label: 'Implanted',
                      color: AppColors.green,
                    ),
                  ),
                AppFieldGrid(fields: lensFields),
              ],
            ),
          ),
      ],
      AppDetailSectionCard(
        title: 'Surgery Plan',
        icon: Icons.event_note_outlined,
        child: AppFieldGrid(fields: planFields),
      ),
      AppDetailSectionCard(
        title: 'Pre-Op Vitals',
        icon: Icons.monitor_heart_outlined,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (preOp?.preOpStatus != null)
              Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: AppStatusPill(
                  label: otPreOpStatusLabel(preOp!.preOpStatus),
                  color: _assistantPreOpToneColor(preOp.preOpStatus),
                ),
              ),
            if (vitalsMissing)
              const Text(
                'Vitals not recorded.',
                style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
              )
            else
              AppFieldGrid(fields: vitalsFields),
          ],
        ),
      ),
      AppDetailSectionCard(
        title: 'Patient Details',
        icon: Icons.badge_outlined,
        child: AppFieldGrid(fields: patientFields),
      ),
    ],
  );
}
