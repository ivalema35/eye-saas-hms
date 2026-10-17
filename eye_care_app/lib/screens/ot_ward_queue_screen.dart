import 'dart:async';
import 'package:flutter/material.dart';
import '../widgets/app_refresh_indicator.dart';
import '../constants/app_colors.dart';
import '../models/ot_accountant_models.dart';
import '../models/ot_booking_models.dart';
import '../models/ot_ward_models.dart';
import '../services/ot_ward_service.dart';
import '../utils/currency_format.dart';
import '../utils/date_format.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_record_card.dart';
import '../widgets/skeleton.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_pagination_bar.dart';
import '../widgets/ot_desk_filter_bar.dart';
import 'ot_ward_screen.dart';

/// Ward Entry Queue — matches web's `ward.blade.php`
/// (`OtAccountantController::wardIndex()`). Replaces the earlier manual
/// booking-ID lookup stopgap now that a real queue endpoint exists (see
/// OT_WEB_PARITY_FIX_PRD.md §4).
class OtWardQueueScreen extends StatefulWidget {
  const OtWardQueueScreen({super.key});

  @override
  State<OtWardQueueScreen> createState() => _OtWardQueueScreenState();
}

class _OtWardQueueScreenState extends State<OtWardQueueScreen> {
  List<OtBookingSummary> _items = [];
  OtPaginationMeta? _meta;
  bool _loading = true;
  String? _error;
  int _page = 1;

  // Web pull 2026-09-19: Queue/All toggle — 'queue' (Ward Entry Queue) or
  // 'history' (Ward History, date-ranged).
  String _filter = 'queue';
  DateTime _fromDate = DateTime.now();
  DateTime _toDate = DateTime.now();

  // Web parity (`ward.blade.php`'s Dilate column) — a live mm:ss countdown
  // to each row's dilation-unlock time, ticking every second via a plain
  // rebuild (cheap — no refetch) rather than a per-row Timer.
  Timer? _dilateTicker;

  @override
  void initState() {
    super.initState();
    _load();
    _dilateTicker = Timer.periodic(const Duration(seconds: 1), (_) {
      if (mounted) setState(() {});
    });
  }

  @override
  void dispose() {
    _dilateTicker?.cancel();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      // Web pull 2026-10-05 (commit 65865858) — both tabs default to today
      // and accept the same date filter, not just History.
      final result = await OtWardService.instance.fetchBookings(
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

  void _openVitals(OtBookingSummary item) {
    final name = item.patient?.fullName ?? 'Patient';
    openOtWardEntryPane(
      context,
      bookingId: item.id,
      patientName: name,
      initialOtStatus: item.otStatus,
    ).then((_) => _load());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      body: Column(
        children: [
          _buildHeader(),
          Expanded(
            child: _loading
                ? const AppSkeletonList()
                : _error != null
                ? AppErrorState(message: _error!, onRetry: _load)
                : _buildBody(),
          ),
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

  Widget _buildHeader() {
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
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(8, 10, 20, 14),
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
                      Icons.bed_rounded,
                      color: Colors.white,
                      size: 20,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      _filter == 'history'
                          ? 'Ward History'
                          : 'Ward Entry Queue',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            // Same OtDeskFilterBar every other OT desk screen uses, at the
            // same left inset (14px) — see its doc comment for why this
            // screen embeds it as a plain child instead of AppBar.bottom
            // (this header is a gradient Container, not a real AppBar).
            OtDeskFilterBar(
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
          ],
        ),
      ),
    );
  }

  // Client request (2026-10-03): this full screen's rows were a plain
  // name/phone text block + full-width text buttons, while the exact same
  // data already got the icon-tagged AppRecordCard treatment on the
  // dashboard's compact Ward desk panel (`WardDeskPanel._row` in
  // `desk_home_panels.dart`) — mirrored here so "Open full page" doesn't
  // feel like a step down in polish from the panel it opened from.
  Widget _buildBody() {
    if (_items.isEmpty)
      return AppEmptyState(
        message: 'No records available for ward workflow.',
        icon: Icons.bed_rounded,
        onRefresh: _load,
      );

    return AppRefreshIndicator(
      color: AppColors.primary,
      onRefresh: _load,
      child: ListView.separated(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 20),
        itemCount: _items.length,
        separatorBuilder: (_, _) => const SizedBox(height: 8),
        itemBuilder: (_, i) {
          final item = _items[i];
          final (stageLabel, stageColor) = _wardStageStyle(item.otStatus);
          final ps = item.paymentStatus;
          final dilateUnlock = _dilateUnlockAt(item);
          return AnimatedListItem(
            index: i,
            child: AppRecordCard(
              leading: PatientAvatar(name: item.patient?.fullName ?? 'Patient'),
              title: item.patient?.fullName ?? 'Patient',
              subtitle: item.patient?.patientCode,
              trailing: AppStatusPill(label: stageLabel, color: stageColor),
              // Web parity (`accountant/ward.blade.php`'s list row): Phone +
              // Dilate + Payment — no OT Date column on this list (the detail
              // view dialog below still shows it, matching `_view-modal.blade.php`).
              fields: [
                AppRecordField(
                  'Phone',
                  item.patient?.contactNo ?? '—',
                  icon: Icons.phone_outlined,
                  color: RecordFieldColors.phone,
                ),
                if (dilateUnlock != null)
                  AppRecordField(
                    'Dilate',
                    _dilateCountdownLabel(dilateUnlock),
                    icon: Icons.water_drop_outlined,
                    color: dilateUnlock.isAfter(DateTime.now()) ? AppColors.orange : AppColors.green,
                  ),
                if (ps != null)
                  AppRecordField(
                    'Payment',
                    paymentStatusLabel(ps),
                    icon: Icons.currency_rupee_rounded,
                    color: paymentStatusColor(ps),
                  ),
              ],
              // Web parity (web pull 2026-09-30, `ward.blade.php`): View is
              // always rendered, plus "Vitals & Eye Drops" only on Queue rows
              // (`@unless($isHistory)`). "Send to OT Assistant" only exists
              // inside the detail pane's own Patient Status card, never as a
              // third button here.
              actions: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  viewIconButton(
                    () => showOtWardBookingViewDialog(context, item),
                  ),
                  if (_filter != 'history')
                    FilledButton.icon(
                      onPressed: () => _openVitals(item),
                      style: miniFilledStyle(AppColors.primary),
                      icon: const Icon(Icons.favorite_rounded, size: 15),
                      label: const Text('Vitals & Eye Drops'),
                    ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

// Web parity (`ward.blade.php`'s Dilate column) — the latest logged dose
// with dilate='Yes' and a dilation_time set, resolved to the actual
// wall-clock moment it unlocks (administered_at + dilation_time minutes).
// Null when no such dose has been logged.
DateTime? _dilateUnlockAt(OtBookingSummary item) {
  OtEyeDropEntry? latest;
  DateTime? latestAt;
  for (final d in item.dilationEntries) {
    if (d.dilate != 'Yes' || d.dilationTime == null) continue;
    final at = DateTime.tryParse(d.administeredAt ?? '');
    if (at == null) continue;
    if (latestAt == null || at.isAfter(latestAt)) {
      latest = d;
      latestAt = at;
    }
  }
  if (latest == null || latestAt == null) return null;
  return latestAt.add(Duration(minutes: latest.dilationTime!));
}

String _dilateCountdownLabel(DateTime unlockAt) {
  final diff = unlockAt.difference(DateTime.now());
  if (diff.isNegative) return 'Ready';
  final m = diff.inMinutes;
  final s = diff.inSeconds.remainder(60);
  return '${m.toString().padLeft(2, '0')}:${s.toString().padLeft(2, '0')}';
}

String _wardDrName(String? name) {
  final n = name?.trim();
  if (n == null || n.isEmpty) return '';
  return RegExp(r'^dr\b\.?', caseSensitive: false).hasMatch(n) ? n : 'Dr. $n';
}

(String, Color) _wardStageStyle(String otStatus) => switch (otStatus) {
  OtStatus.paymentVerified => ('Awaiting Ward Entry', AppColors.orange),
  OtStatus.inWard => ('In Ward', AppColors.blue),
  OtStatus.dilated => ('Dilated', AppColors.purple),
  OtStatus.ready => ('Ready for OT', AppColors.primary),
  OtStatus.operated => ('Operated', AppColors.green),
  OtStatus.discharged => ('Discharged', AppColors.textSecondary),
  _ => (OtStatus.label(otStatus), AppColors.textSecondary),
};

Color _wardPreOpToneColor(String? status) => switch (status) {
  'ready_for_surgery' => AppColors.green,
  'preparing' => AppColors.blue,
  'hold' => AppColors.orange,
  'complicated' || 'not_fit' => AppColors.red,
  _ => AppColors.textDisabled,
};

/// Web parity (web pull 2026-09-30, `_view-modal.blade.php`) — extracted so
/// the dashboard's compact Ward desk panel (see
/// `lib/widgets/desk_home_panels.dart`) shows the exact same rich read-only
/// detail as this full desk screen, instead of a second, shallower copy.
/// Replaces the old 5-fact dialog (Patient/Phone/OT Date/Status/Payment)
/// with web's full Pre-Op Vitals / Patient Details / Surgery & OT / Eye Drop
/// Register.
// Redesigned onto the shared showAppDetailSheet/AppDetailSectionCard/
// AppFieldGrid bottom-sheet pattern — see OtCounsellorDashboardScreen.openView
// for the full rationale (was a centered AlertDialog with a flat label/value
// list, no grouping, no drag/close affordance).
Future<void> showOtWardBookingViewDialog(
  BuildContext context,
  OtBookingSummary item,
) async {
  final p = item.patient;
  final (stageLabel, stageColor) = _wardStageStyle(item.otStatus);
  final ps = item.paymentStatus;
  final payLabel = ps != null ? paymentStatusLabel(ps) : 'Payment Pending';
  final payColor = paymentStatusColor(ps ?? 'pending');
  final cur = currentCurrencySymbol();
  final preOp = item.preOp;

  final lens = [
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
    if (_wardDrName(p?.doctor?.name).isNotEmpty)
      AppRecordField(
        'Consulting Doctor',
        _wardDrName(p?.doctor?.name),
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

  final otFields = <AppRecordField>[
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
    if (lens.isNotEmpty)
      AppRecordField(
        'Lens',
        lens,
        icon: Icons.lens_outlined,
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
    if (_wardDrName(item.otDoctor?.name).isNotEmpty)
      AppRecordField(
        'OT Doctor',
        _wardDrName(item.otDoctor?.name),
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
    if (formatPlainDate(item.surgeryDate) != null)
      AppRecordField(
        'OT Date',
        formatPlainDate(item.surgeryDate)!,
        icon: Icons.event_outlined,
        color: RecordFieldColors.date,
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
                  color: _wardPreOpToneColor(preOp.preOpStatus),
                ),
              ),
            if (vitalsMissing)
              const Text(
                'Vitals not recorded yet.',
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
      AppDetailSectionCard(
        title: 'Surgery & OT',
        icon: Icons.local_hospital_outlined,
        child: AppFieldGrid(fields: otFields),
      ),
      AppDetailSectionCard(
        title: 'Eye Drop Register',
        icon: Icons.opacity_rounded,
        child: item.dilationEntries.isEmpty
            ? const Text(
                'No eye drops given yet.',
                style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
              )
            : Column(
                // API returns doses oldest-first (administered_at asc,
                // matching web); reversed so the most recent dose shows
                // first instead of scrolled furthest away.
                children: item.dilationEntries.reversed
                    .map(
                      (d) => Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    '${d.medicineName} · ${d.eye} · Dose #${d.doseNumber}',
                                    style: const TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                  Text(
                                    '${d.administeredAt != null ? formatDateTimeString(d.administeredAt) : '-'} · ${d.administeredBy?.name ?? '-'}',
                                    style: const TextStyle(
                                      fontSize: 10.5,
                                      color: AppColors.textSecondary,
                                    ),
                                  ),
                                  if (d.dilate == 'Yes')
                                    Text(
                                      'Dilated${d.dilationTime != null ? ' · ${d.dilationTime} mins' : ''}',
                                      style: const TextStyle(
                                        fontSize: 10.5,
                                        fontWeight: FontWeight.w700,
                                        color: AppColors.tealDark,
                                      ),
                                    ),
                                ],
                              ),
                            ),
                            if (d.dropsCount != null)
                              Text(
                                '${d.dropsCount} drops',
                                style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w700,
                                  color: AppColors.primary,
                                ),
                              ),
                          ],
                        ),
                      ),
                    )
                    .toList(),
              ),
      ),
    ],
  );
}
