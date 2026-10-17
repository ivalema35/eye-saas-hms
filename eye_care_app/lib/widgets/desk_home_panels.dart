import 'dart:async';
import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/permissions.dart';
import 'app_record_card.dart';
import '../models/auth_models.dart';
import '../models/ot_accountant_models.dart';
import '../models/ot_booking_models.dart';
import '../models/ot_ward_models.dart';
import '../models/ot_discharge_models.dart';
import '../screens/ot_accountant_dashboard_screen.dart';
import '../screens/ot_assistant_dashboard_screen.dart';
import '../screens/ot_discharge_dashboard_screen.dart';
import '../screens/ot_discharge_detail_screen.dart';
import '../screens/ot_payment_screen.dart';
import '../screens/ot_surgery_record_screen.dart';
import '../screens/ot_counselling_form_screen.dart';
import '../screens/ot_counsellor_dashboard_screen.dart';
import '../screens/ot_ward_queue_screen.dart';
import '../screens/ot_ward_screen.dart';
import '../models/ot_appointment_models.dart';
import '../screens/ot_appointment_list_screen.dart';
import '../services/ot_accountant_service.dart';
import '../services/ot_appointment_service.dart';
import '../services/ot_assistant_service.dart';
import '../services/ot_discharge_service.dart';
import '../services/ot_counsellor_service.dart';
import '../services/ot_ward_service.dart';
import '../services/permission_service.dart';
import '../utils/app_route.dart';
import '../utils/currency_format.dart';
import '../utils/date_format.dart';
import '../utils/refreshable.dart';
import 'app_animations.dart';
import 'desk_home_panel.dart';
import 'status_badge.dart';

/// The 4 role-specific compact "desk panels" shown inline on the Hospital
/// Dashboard home, directly below the existing stat-card row — mirrors web's
/// `accountant-panel.blade.php` / `ward-panel.blade.php` /
/// `ot-assistant-panel.blade.php` / `discharge-panel.blade.php`. Each panel
/// does its own independent fetch (the dashboard's own stat-card counts come
/// from a separate, count-only endpoint — these need the full row data,
/// which only the dedicated desk services provide), following the same
/// pattern as `_TodayPatientsWidget` elsewhere in `dashboard_screen.dart`.
///
/// Each panel fetches every one of its tabs up front (in parallel, a small
/// page size each) so every tab pill can show a live count immediately,
/// exactly like web — this is a "mini" preview, not a full paginated list;
/// "Open full page" is what the full list is for.
const int kDeskPanelMiniPageSize = 5;

// ── Shared row-building bits (small, role-specific rows are still built per
// role below — see the file-level PRD note in the task: "build small
// per-role adapter functions around the shared panel chrome" rather than one
// giant configurable mega-widget) ──────────────────────────────────────────

// Promoted to `widgets/app_record_card.dart` as `PatientAvatar` so the full
// desk screens can use the same avatar — kept as a local alias here purely
// to avoid a large diff on the call sites below.
class _PatientAvatar extends StatelessWidget {
  final String name;
  const _PatientAvatar({required this.name});

  @override
  Widget build(BuildContext context) => PatientAvatar(name: name);
}

// Material's OutlinedButton/FilledButton enforce a 64x36 minimumSize by
// default regardless of padding — every "View"/"Counsel"/"Operate"/etc.
// button across all 5 desk panels was quietly padded out to that floor even
// though the padding/visualDensity above asked for something much smaller,
// which is why a lone action button could eat as much width as the status
// badge next to it. minimumSize: zero + shrinkWrap tap target let the
// padding actually be the size — 2026 mobile guidance (Material/iOS) puts
// the real minimum comfortable touch target at ~40-44dp, which 6v padding
// plus the 11.5sp label text already clears.
// Promoted to `widgets/app_record_card.dart` as `miniOutlinedStyle` /
// `miniFilledStyle` / `viewIconButton` so the full desk screens (Accountant,
// Ward, Discharge, OT Assistant...) can match this exact compact look too,
// instead of each hand-rolling its own oversized button. Local aliases kept
// here (unchanged call sites below) purely to avoid a large diff.
ButtonStyle _miniOutlinedStyle() => miniOutlinedStyle();
ButtonStyle _miniFilledStyle(Color color) => miniFilledStyle(color);
Widget _viewIconButton(VoidCallback onTap) => viewIconButton(onTap);

/// Web's ward/OT-Assistant status-pill scheme (payment_verified → In-Ward →
/// Dilated → Ready → Operated → Discharged) — the exact tones named in the
/// dashboard-panel spec, distinct from this app's generic `otStatusColor`
/// (used by the full desk screens for their own broader status chain).
({String label, Color color}) _deskStatusVisual(String status) =>
    switch (status) {
      OtStatus.paymentVerified => (
        label: 'Awaiting Ward Entry',
        color: AppColors.orange,
      ),
      OtStatus.inWard => (label: 'In Ward', color: AppColors.blue),
      OtStatus.dilated => (label: 'Dilated', color: AppColors.purple),
      OtStatus.ready => (label: 'Ready for OT', color: AppColors.darkNavy),
      OtStatus.operated => (label: 'Operated', color: AppColors.green),
      OtStatus.discharged => (
        label: 'Discharged',
        color: AppColors.textSecondary,
      ),
      _ => (label: OtStatus.label(status), color: AppColors.textDisabled),
    };

/// Web's Ward/OT-Assistant panel "Payment" column scheme — Paid/Partially
/// Paid/Pending (distinct short labels from the Accountant panel's own
/// Payment-Status pill wording, matching the spec exactly).
({String label, Color color}) _deskPaymentVisual(String? status) =>
    switch (status) {
      'paid' => (label: 'Paid', color: AppColors.green),
      'partially_paid' => (label: 'Partially Paid', color: AppColors.blue),
      _ => (label: 'Pending', color: AppColors.orange),
    };

// ── 1. Accountant ────────────────────────────────────────────────────────

class AccountantDeskPanel extends StatefulWidget {
  final VoidCallback onOpenFullPage;
  final String? requestedTab;
  final ValueChanged<String>? onDeskTab;
  final ValueChanged<Map<String, int>>? onCounts;
  const AccountantDeskPanel({
    super.key,
    required this.onOpenFullPage,
    this.requestedTab,
    this.onDeskTab,
    this.onCounts,
  });

  @override
  State<AccountantDeskPanel> createState() => _AccountantDeskPanelState();
}

class _AccountantDeskPanelState extends State<AccountantDeskPanel>
    implements Refreshable {
  static const _tabValues = ['queue', 'refunds', 'history'];
  static const _tabLabels = {
    'queue': 'Payment Queue',
    'refunds': 'Refund Queue',
    'history': 'Completed Payments',
  };

  String _tab = 'queue';
  bool _loading = true;
  String? _error;
  final Map<String, List<OtBookingSummary>> _items = {};
  final Map<String, int> _totals = {};

  @override
  void initState() {
    super.initState();
    if (widget.requestedTab != null) _tab = widget.requestedTab!;
    _load();
  }

  @override
  void didUpdateWidget(covariant AccountantDeskPanel oldWidget) {
    super.didUpdateWidget(oldWidget);
    final next = widget.requestedTab;
    if (next != null && next != _tab) setState(() => _tab = next);
  }

  @override
  Future<void> refreshSilently() => _load();

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final results = await Future.wait(
        _tabValues.map(
          (f) => OtAccountantService.instance.fetchBookings(
            filter: f,
            perPage: kDeskPanelMiniPageSize,
          ),
        ),
      );
      if (!mounted) return;
      setState(() {
        for (var i = 0; i < _tabValues.length; i++) {
          _items[_tabValues[i]] = results[i].items;
          _totals[_tabValues[i]] = results[i].meta.total;
        }
        _loading = false;
      });
      widget.onCounts?.call(Map<String, int>.from(_totals));
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString().replaceFirst('Exception: ', '');
        _loading = false;
      });
    }
  }

  void _openAddPayment(OtBookingSummary item) =>
      showOtRecordPaymentSheet(context, bookingId: item.id, onSaved: _load);

  void _openRefund(OtBookingSummary item) {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => OtAccountantRefundDialog(bookingId: item.id),
    ).then((_) => _load());
  }

  void _viewDetails(OtBookingSummary item) =>
      showOtAccountantBookingViewDialog(context, item);

  void _viewReceipt(OtBookingSummary item) {
    final paymentId = item.latestPaymentId;
    if (paymentId == null) {
      showAppSnackBar(context, 'No payments recorded yet.');
      return;
    }
    showDialog<void>(
      context: context,
      builder: (_) => OtAccountantReceiptDialog(
        paymentId: paymentId,
        patientName: item.patient?.fullName,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final items = _items[_tab] ?? const [];
    return DeskHomePanel(
      icon: Icons.account_balance_wallet_rounded,
      title: 'Accountant Desk',
      totalCount: _totals[_tab] ?? items.length,
      onOpenFullPage: widget.onOpenFullPage,
      tabs: _tabValues
          .map(
            (v) => DeskPanelTabDef(
              value: v,
              label: _tabLabels[v]!,
              count: _totals[v] ?? 0,
            ),
          )
          .toList(),
      selectedTab: _tab,
      onTabSelected: (v) {
        setState(() => _tab = v);
        widget.onDeskTab?.call(v);
      },
      loading: _loading,
      error: _error,
      onRetry: _load,
      emptyMessage: switch (_tab) {
        'refunds' => 'No refunds pending.',
        'history' => 'No completed payments yet.',
        _ => 'No pending payments.',
      },
      emptyIcon: Icons.account_balance_wallet_outlined,
      rows: items.map((item) => DeskPanelRowCard(child: _row(item))).toList(),
    );
  }

  Widget _row(OtBookingSummary item) {
    final canPaymentRecord = PermissionService.instance.can(
      Perm.otPaymentRecord,
    );
    final canBillPrint = PermissionService.instance.can(Perm.otBillPrint);
    final isRefundsView =
        _tab == 'refunds' || item.otStatus == OtStatus.surgeryRefused;
    final ps = item.paymentStatus ?? 'pending';

    late final String statusLabel;
    late final Color statusColor;
    if (isRefundsView) {
      final refundable = item.refundableBalance ?? 0;
      final refunded = item.totalRefunded ?? 0;
      if (refundable <= 0 && refunded > 0) {
        statusLabel = 'Fully Refunded';
        statusColor = AppColors.green;
      } else if (refundable > 0) {
        statusLabel = 'Refund Pending';
        statusColor = AppColors.red;
      } else {
        statusLabel = 'Surgery Refused';
        statusColor = AppColors.textSecondary;
      }
    } else {
      statusLabel = paymentStatusLabel(ps, verbose: true);
      statusColor = paymentStatusColor(ps);
    }
    final canAddPayment =
        canPaymentRecord && !isRefundsView && ps != 'paid' && ps != 'unpriced';

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _PatientAvatar(name: item.patient?.fullName ?? 'Patient'),
            const SizedBox(width: 8),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item.patient?.fullName ?? 'Patient',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: AppColors.darkNavy,
                    ),
                  ),
                  if (item.patient?.patientCode.isNotEmpty ?? false)
                    Text(
                      item.patient!.patientCode,
                      style: const TextStyle(
                        fontSize: 11,
                        color: AppColors.textSecondary,
                      ),
                    ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            DeskPanelBadge(label: statusLabel, color: statusColor),
          ],
        ),
        const SizedBox(height: 6),
        // AppFieldGrid, not a Wrap of chips — see the comment on the
        // Counselling panel's _row for why (a Wrap drops a chip to a new line
        // the moment it doesn't fit, leaving the rest of that line's width
        // empty instead of actually using it).
        AppFieldGrid(
          fields: [
            AppRecordField(
              'Phone',
              item.patient?.contactNo ?? '—',
              icon: Icons.phone_outlined,
              color: RecordFieldColors.phone,
            ),
            AppRecordField(
              'Package',
              (item.packageAmount ?? 0).toStringAsFixed(0),
              icon: Icons.currency_rupee_rounded,
              color: RecordFieldColors.package,
            ),
          ],
        ),
        if (isRefundsView) ...[
          const SizedBox(height: 6),
          Text(
            'Paid ${currentCurrencySymbol()}${(item.packageAmount ?? 0).toStringAsFixed(0)} · Refunded ${currentCurrencySymbol()}${(item.totalRefunded ?? 0).toStringAsFixed(0)}',
            style: const TextStyle(
              fontSize: 11,
              color: AppColors.textSecondary,
            ),
          ),
          if ((item.refundableBalance ?? 0) > 0)
            Text(
              'To return: ${currentCurrencySymbol()}${item.refundableBalance!.toStringAsFixed(0)}',
              style: const TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w700,
                color: AppColors.orange,
              ),
            ),
        ] else if (ps == 'partially_paid' && item.remainingBalance != null) ...[
          const SizedBox(height: 6),
          Text(
            'Balance: ${currentCurrencySymbol()}${item.remainingBalance!.toStringAsFixed(0)}',
            style: const TextStyle(
              fontSize: 11,
              color: AppColors.textSecondary,
            ),
          ),
        ],
        const SizedBox(height: 10),
        // View pinned left, the real action(s) grouped and pinned right —
        // see the Counselling panel's _row comment for why spaceBetween
        // instead of a cramped left-aligned Wrap.
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            _viewIconButton(() => _viewDetails(item)),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              alignment: WrapAlignment.end,
              children: [
                if (isRefundsView)
                  (item.refundableBalance ?? 0) > 0
                      ? FilledButton.icon(
                          onPressed: () => _openRefund(item),
                          style: _miniFilledStyle(AppColors.red),
                          icon: const Icon(
                            Icons.keyboard_return_rounded,
                            size: 15,
                          ),
                          label: const Text('Full Refund'),
                        )
                      : const Text(
                          'Refund Done',
                          style: TextStyle(
                            fontSize: 11.5,
                            color: AppColors.textSecondary,
                            fontWeight: FontWeight.w600,
                          ),
                        )
                else if (ps == 'unpriced')
                  const Text(
                    'Package Not Set',
                    style: TextStyle(
                      fontSize: 11.5,
                      color: AppColors.textSecondary,
                      fontWeight: FontWeight.w600,
                    ),
                  )
                else if (canAddPayment)
                  FilledButton.icon(
                    onPressed: () => _openAddPayment(item),
                    style: _miniFilledStyle(AppColors.primary),
                    icon: const Icon(Icons.payments_rounded, size: 15),
                    label: Text(
                      ps == 'partially_paid' ? 'Add Balance' : 'Add Payment',
                    ),
                  ),
                if (canBillPrint &&
                    item.latestPaymentId != null &&
                    !isRefundsView)
                  OutlinedButton(
                    onPressed: () => _viewReceipt(item),
                    style: _miniOutlinedStyle(),
                    child: const Text('Receipt'),
                  ),
              ],
            ),
          ],
        ),
      ],
    );
  }
}

// ── 2. Ward Management ──────────────────────────────────────────────────

class WardDeskPanel extends StatefulWidget {
  final VoidCallback onOpenFullPage;
  final String? requestedTab;
  final ValueChanged<String>? onDeskTab;
  final ValueChanged<Map<String, int>>? onCounts;
  const WardDeskPanel({
    super.key,
    required this.onOpenFullPage,
    this.requestedTab,
    this.onDeskTab,
    this.onCounts,
  });

  @override
  State<WardDeskPanel> createState() => _WardDeskPanelState();
}

class _WardDeskPanelState extends State<WardDeskPanel> implements Refreshable {
  static const _tabValues = ['queue', 'history'];
  static const _tabLabels = {
    'queue': 'Ward Entry Queue',
    'history': 'Ward History',
  };

  String _tab = 'queue';
  bool _loading = true;
  String? _error;
  final Map<String, List<OtBookingSummary>> _items = {};
  final Map<String, int> _totals = {};
  Timer? _dilateTicker;

  @override
  void initState() {
    super.initState();
    if (widget.requestedTab != null) _tab = widget.requestedTab!;
    _load();
    // Live mm:ss Dilate countdown — matches web's own 1s setInterval on this
    // same panel (ward-panel.blade.php), and the dedicated Ward Entry Queue
    // screen's own ticker. Cheap: just a rebuild, no refetch.
    _dilateTicker = Timer.periodic(const Duration(seconds: 1), (_) {
      if (mounted) setState(() {});
    });
  }

  @override
  void didUpdateWidget(covariant WardDeskPanel oldWidget) {
    super.didUpdateWidget(oldWidget);
    final next = widget.requestedTab;
    if (next != null && next != _tab) setState(() => _tab = next);
  }

  @override
  void dispose() {
    _dilateTicker?.cancel();
    super.dispose();
  }

  @override
  Future<void> refreshSilently() => _load();

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final results = await Future.wait(
        _tabValues.map(
          (f) => OtWardService.instance.fetchBookings(
            filter: f,
            perPage: kDeskPanelMiniPageSize,
          ),
        ),
      );
      if (!mounted) return;
      setState(() {
        for (var i = 0; i < _tabValues.length; i++) {
          _items[_tabValues[i]] = results[i].items;
          _totals[_tabValues[i]] = results[i].meta.total;
        }
        _loading = false;
      });
      widget.onCounts?.call(Map<String, int>.from(_totals));
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString().replaceFirst('Exception: ', '');
        _loading = false;
      });
    }
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
    final items = _items[_tab] ?? const [];
    return DeskHomePanel(
      icon: Icons.bed_rounded,
      title: 'Ward Management Desk',
      totalCount: _totals[_tab] ?? items.length,
      onOpenFullPage: widget.onOpenFullPage,
      tabs: _tabValues
          .map(
            (v) => DeskPanelTabDef(
              value: v,
              label: _tabLabels[v]!,
              count: _totals[v] ?? 0,
            ),
          )
          .toList(),
      selectedTab: _tab,
      onTabSelected: (v) {
        setState(() => _tab = v);
        widget.onDeskTab?.call(v);
      },
      loading: _loading,
      error: _error,
      onRetry: _load,
      emptyMessage: _tab == 'history'
          ? 'No ward history yet.'
          : 'No patients awaiting ward entry.',
      emptyIcon: Icons.bed_outlined,
      rows: items.map((item) => DeskPanelRowCard(child: _row(item))).toList(),
    );
  }

  Widget _row(OtBookingSummary item) {
    final status = _deskStatusVisual(item.otStatus);
    final payment = _deskPaymentVisual(item.paymentStatus);
    final dilateUnlock = _wardDilateUnlockAt(item);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _PatientAvatar(name: item.patient?.fullName ?? 'Patient'),
            const SizedBox(width: 8),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item.patient?.fullName ?? 'Patient',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: AppColors.darkNavy,
                    ),
                  ),
                  if (item.patient?.patientCode.isNotEmpty ?? false)
                    Text(
                      item.patient!.patientCode,
                      style: const TextStyle(
                        fontSize: 11,
                        color: AppColors.textSecondary,
                      ),
                    ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            DeskPanelBadge(label: status.label, color: status.color),
          ],
        ),
        const SizedBox(height: 6),
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              // Web parity (`accountant/ward.blade.php`'s list row): no OT
              // Date column on the Ward list/panel.
              child: AppFieldGrid(
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
                      _wardDilateCountdownLabel(dilateUnlock),
                      icon: Icons.water_drop_outlined,
                      color: dilateUnlock.isAfter(DateTime.now()) ? AppColors.orange : AppColors.green,
                    ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            DeskPanelBadge(label: payment.label, color: payment.color),
          ],
        ),
        if (item.paymentStatus == 'partially_paid' &&
            item.remainingBalance != null) ...[
          const SizedBox(height: 3),
          Text(
            'Balance: ${currentCurrencySymbol()}${item.remainingBalance!.toStringAsFixed(0)}',
            style: const TextStyle(
              fontSize: 11,
              color: AppColors.textSecondary,
            ),
          ),
        ],
        const SizedBox(height: 10),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            _viewIconButton(() => showOtWardBookingViewDialog(context, item)),
            if (_tab == 'queue')
              FilledButton.icon(
                onPressed: () => _openVitals(item),
                style: _miniFilledStyle(AppColors.primary),
                icon: const Icon(Icons.favorite_rounded, size: 15),
                label: const Text('Vitals & Eye Drops'),
              ),
          ],
        ),
      ],
    );
  }
}

// Web parity (`ward.blade.php`'s Dilate column, also shown on this same
// panel on web — `ward-panel.blade.php`) — the latest logged dose with
// dilate='Yes' and a dilation_time set, resolved to the actual wall-clock
// moment it unlocks. Duplicated from ot_ward_queue_screen.dart's
// file-private helpers of the same name/logic (only the 2nd caller so far —
// worth factoring out to a shared file if a 3rd appears).
DateTime? _wardDilateUnlockAt(OtBookingSummary item) {
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

String _wardDilateCountdownLabel(DateTime unlockAt) {
  final remaining = unlockAt.difference(DateTime.now());
  if (remaining.isNegative) return 'Ready';
  final mm = remaining.inMinutes.toString().padLeft(2, '0');
  final ss = (remaining.inSeconds % 60).toString().padLeft(2, '0');
  return '$mm:$ss';
}

// ── 3. OT Assistant ──────────────────────────────────────────────────────

class OtAssistantDeskPanel extends StatefulWidget {
  final UserInfo user;
  final VoidCallback onOpenFullPage;
  final String? requestedTab;
  final ValueChanged<String>? onDeskTab;
  final ValueChanged<Map<String, int>>? onCounts;
  final int? assistantId;
  final String? viewingName;
  final VoidCallback? onBackToMine;
  const OtAssistantDeskPanel({
    super.key,
    required this.user,
    required this.onOpenFullPage,
    this.requestedTab,
    this.onDeskTab,
    this.onCounts,
    this.assistantId,
    this.viewingName,
    this.onBackToMine,
  });

  @override
  State<OtAssistantDeskPanel> createState() => _OtAssistantDeskPanelState();
}

class _OtAssistantDeskPanelState extends State<OtAssistantDeskPanel>
    implements Refreshable {
  static const _tabValues = ['queue', 'history'];
  static const _tabLabels = {
    'queue': 'Ready for Surgery',
    'history': 'Surgery History',
  };

  String _tab = 'queue';
  bool _loading = true;
  String? _error;
  final Map<String, List<OtBookingSummary>> _items = {};
  final Map<String, int> _totals = {};

  // Matches web's `isSuperUser() || role.slug === 'hospital_admin'` exactly
  // (OtAssistantController::dashboard()) — same rule already used by the
  // full `OtAssistantDashboardScreen`.
  bool get _seeAll =>
      (widget.user.role?.isSuper ?? false) ||
      widget.user.role?.slug == 'hospital_admin';
  bool get _canSurgery => PermissionService.instance.can(Perm.otSurgeryRecord);

  @override
  void initState() {
    super.initState();
    if (widget.requestedTab != null) _tab = widget.requestedTab!;
    _load();
  }

  @override
  void didUpdateWidget(covariant OtAssistantDeskPanel oldWidget) {
    super.didUpdateWidget(oldWidget);
    final next = widget.requestedTab;
    if (next != null && next != _tab) setState(() => _tab = next);
    if (widget.assistantId != oldWidget.assistantId) _load();
  }

  @override
  Future<void> refreshSilently() => _load();

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final assistantId = widget.assistantId;
      final results = await Future.wait(
        _tabValues.map(
          (f) => OtAssistantService.instance.fetchBookings(
            filter: f,
            assistantId: assistantId,
            perPage: kDeskPanelMiniPageSize,
          ),
        ),
      );
      if (!mounted) return;
      setState(() {
        for (var i = 0; i < _tabValues.length; i++) {
          _items[_tabValues[i]] = results[i].items;
          _totals[_tabValues[i]] = results[i].meta.total;
        }
        _loading = false;
      });
      widget.onCounts?.call(Map<String, int>.from(_totals));
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString().replaceFirst('Exception: ', '');
        _loading = false;
      });
    }
  }

  void _openSurgery(OtBookingSummary item) {
    final name = item.patient?.fullName ?? 'Patient';
    Navigator.push(
      context,
      appRoute(OtSurgeryRecordScreen(bookingId: item.id, patientName: name)),
    ).then((_) => _load());
  }

  @override
  Widget build(BuildContext context) {
    final items = _items[_tab] ?? const [];
    final viewing = widget.viewingName;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (viewing != null && viewing.isNotEmpty)
          Container(
            margin: const EdgeInsets.only(bottom: 8),
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(
                color: AppColors.primary.withValues(alpha: 0.25),
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  "Viewing $viewing's patients — you can operate them",
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: AppColors.darkNavy,
                  ),
                ),
                Align(
                  alignment: Alignment.centerLeft,
                  child: TextButton(
                    onPressed: widget.onBackToMine,
                    style: TextButton.styleFrom(
                      padding: EdgeInsets.zero,
                      minimumSize: const Size(0, 32),
                    ),
                    child: const Text('Back to My Patients'),
                  ),
                ),
              ],
            ),
          ),
        DeskHomePanel(
          icon: Icons.handyman_rounded,
          title: 'OT Assistant Desk',
          totalCount: _totals[_tab] ?? items.length,
          onOpenFullPage: widget.onOpenFullPage,
          tabs: _tabValues
              .map(
                (v) => DeskPanelTabDef(
                  value: v,
                  label: _tabLabels[v]!,
                  count: _totals[v] ?? 0,
                ),
              )
              .toList(),
          selectedTab: _tab,
          onTabSelected: (v) {
            setState(() => _tab = v);
            widget.onDeskTab?.call(v);
          },
          loading: _loading,
          error: _error,
          onRetry: _load,
          emptyMessage: _tab == 'history'
              ? 'No surgery history yet.'
              : 'No bookings ready for surgery.',
          emptyIcon: Icons.handyman_outlined,
          rows: items
              .map((item) => DeskPanelRowCard(child: _row(item)))
              .toList(),
        ),
      ],
    );
  }

  Widget _row(OtBookingSummary item) {
    final status = _deskStatusVisual(item.otStatus);
    final payment = _deskPaymentVisual(item.paymentStatus);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _PatientAvatar(name: item.patient?.fullName ?? 'Patient'),
            const SizedBox(width: 8),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item.patient?.fullName ?? 'Patient',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: AppColors.darkNavy,
                    ),
                  ),
                  // Web parity note: the SECOND line here is contact_no, not
                  // patient_code (differs from the other 3 panels).
                  Text(
                    item.patient?.contactNo ?? '—',
                    style: const TextStyle(
                      fontSize: 11,
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            DeskPanelBadge(label: status.label, color: status.color),
          ],
        ),
        const SizedBox(height: 6),
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: AppFieldGrid(
                fields: [
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
                  if (_seeAll && item.otDoctor != null)
                    AppRecordField(
                      'Surgeon',
                      'Dr. ${item.otDoctor!.name}',
                      icon: Icons.medical_services_outlined,
                      color: RecordFieldColors.doctor,
                    ),
                  if (_tab == 'history' && item.surgeryDate != null)
                    AppRecordField(
                      'OT Date',
                      item.surgeryDate!,
                      icon: Icons.event_outlined,
                      color: RecordFieldColors.date,
                    ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            DeskPanelBadge(label: payment.label, color: payment.color),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            _viewIconButton(
              () => showOtAssistantBookingViewDialog(
                context,
                item,
                seeAll: _seeAll,
              ),
            ),
            if (_tab == 'queue' && _canSurgery)
              FilledButton.icon(
                onPressed: () => _openSurgery(item),
                style: _miniFilledStyle(AppColors.primary),
                icon: const Icon(Icons.medical_services_rounded, size: 15),
                label: const Text('Operate'),
              ),
          ],
        ),
      ],
    );
  }
}

// ── 4. Discharge Counter ─────────────────────────────────────────────────

class DischargeDeskPanel extends StatefulWidget {
  final VoidCallback onOpenFullPage;
  final String? requestedTab;
  final ValueChanged<String>? onDeskTab;
  final ValueChanged<Map<String, int>>? onCounts;
  const DischargeDeskPanel({
    super.key,
    required this.onOpenFullPage,
    this.requestedTab,
    this.onDeskTab,
    this.onCounts,
  });

  @override
  State<DischargeDeskPanel> createState() => _DischargeDeskPanelState();
}

class _DischargeDeskPanelState extends State<DischargeDeskPanel>
    implements Refreshable {
  static const _tabValues = ['queue', 'history'];
  static const _tabLabels = {
    'queue': 'Discharge & Invoice Queue',
    'history': 'Discharged Patients',
  };

  String _tab = 'queue';
  bool _loading = true;
  String? _error;
  final Map<String, List<OtBookingSummary>> _items = {};
  final Map<String, int> _totals = {};
  int? _busyBookingId;
  OtDischargeDocType? _busyDocType;

  // Web spec names `ot_billing_manage` for the Generate action explicitly;
  // the per-document print buttons reuse the exact same 3 permission gates
  // the full desk detail screen (`ot_discharge_detail_screen.dart`) already
  // uses for those same 3 buttons.
  bool get _canGenerate => PermissionService.instance.can(Perm.otBillingManage);
  bool get _canBillPrint => PermissionService.instance.can(Perm.otBillPrint);
  bool get _canDischargePrint =>
      PermissionService.instance.can(Perm.otDischargeGenerate);
  bool get _canCertificatePrint =>
      PermissionService.instance.can(Perm.otCertificatePrint);

  @override
  void initState() {
    super.initState();
    if (widget.requestedTab != null) _tab = widget.requestedTab!;
    _load();
  }

  @override
  void didUpdateWidget(covariant DischargeDeskPanel oldWidget) {
    super.didUpdateWidget(oldWidget);
    final next = widget.requestedTab;
    if (next != null && next != _tab) setState(() => _tab = next);
  }

  @override
  Future<void> refreshSilently() => _load();

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final results = await Future.wait(
        _tabValues.map(
          (f) => OtDischargeService.instance.fetchBookings(
            filter: f,
            perPage: kDeskPanelMiniPageSize,
          ),
        ),
      );
      if (!mounted) return;
      setState(() {
        for (var i = 0; i < _tabValues.length; i++) {
          _items[_tabValues[i]] = results[i].items;
          _totals[_tabValues[i]] = results[i].meta.total;
        }
        _loading = false;
      });
      widget.onCounts?.call(Map<String, int>.from(_totals));
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString().replaceFirst('Exception: ', '');
        _loading = false;
      });
    }
  }

  // Web parity (web pull 2026-09-30) — shared with the full Discharge &
  // Invoices list (see `showOtDischargeBookingViewDialog`) so both surfaces
  // always show the same rich detail instead of a 5-fact placeholder.
  Future<void> _viewDetails(OtBookingSummary item) =>
      showOtDischargeBookingViewDialog(context, item);

  Future<void> _generateInvoice(OtBookingSummary item) async {
    final result = await showDialog<OtInvoiceDraft>(
      context: context,
      builder: (_) => const OtGenerateInvoiceDialog(),
    );
    if (result == null) return;
    setState(() => _busyBookingId = item.id);
    try {
      final r = await OtDischargeService.instance.generateInvoice(
        item.id,
        followUpDate: result.followUpDate,
      );
      if (!mounted) return;
      showAppSnackBar(
        context,
        'Invoice ${r.invoiceNumber} generated. Print all 3 documents to complete discharge.',
        isSuccess: true,
      );
      // Web parity: generating redirects straight into printing the Invoice
      // document — same as `OtDischargeDetailScreen._generateInvoice`.
      await OtDischargeService.instance.printDocument(
        item.id,
        OtDischargeDocType.invoice,
      );
      await _load();
    } catch (e) {
      if (mounted)
        showAppSnackBar(
          context,
          e.toString().replaceFirst('Exception: ', ''),
          isError: true,
        );
    } finally {
      if (mounted) setState(() => _busyBookingId = null);
    }
  }

  Future<void> _printDoc(OtBookingSummary item, OtDischargeDocType type) async {
    int? restDays;
    if (type == OtDischargeDocType.certificate) {
      restDays = await askOtCertificateRestDays(context);
      if (restDays == null) return;
    }
    setState(() {
      _busyBookingId = item.id;
      _busyDocType = type;
    });
    try {
      await OtDischargeService.instance.printDocument(
        item.id,
        type,
        restDays: restDays,
      );
      await _load();
    } catch (e) {
      if (mounted)
        showAppSnackBar(
          context,
          e.toString().replaceFirst('Exception: ', ''),
          isError: true,
        );
    } finally {
      if (mounted)
        setState(() {
          _busyBookingId = null;
          _busyDocType = null;
        });
    }
  }

  String _fmtDate(String? iso) {
    if (iso == null) return '—';
    final d = DateTime.tryParse(iso);
    return d == null ? iso : toDisplayDate(d);
  }

  @override
  Widget build(BuildContext context) {
    final items = _items[_tab] ?? const [];
    return DeskHomePanel(
      icon: Icons.receipt_long_rounded,
      title: 'Discharge Desk',
      totalCount: _totals[_tab] ?? items.length,
      onOpenFullPage: widget.onOpenFullPage,
      tabs: _tabValues
          .map(
            (v) => DeskPanelTabDef(
              value: v,
              label: _tabLabels[v]!,
              count: _totals[v] ?? 0,
            ),
          )
          .toList(),
      selectedTab: _tab,
      onTabSelected: (v) {
        setState(() => _tab = v);
        widget.onDeskTab?.call(v);
      },
      loading: _loading,
      error: _error,
      onRetry: _load,
      emptyMessage: _tab == 'history'
          ? 'No discharged patients yet.'
          : 'No records available for billing.',
      emptyIcon: Icons.receipt_long_outlined,
      rows: items.map((item) => DeskPanelRowCard(child: _row(item))).toList(),
    );
  }

  Widget _row(OtBookingSummary item) {
    final hasInvoice = item.hasInvoice ?? false;
    final rowBusy = _busyBookingId == item.id;
    bool docBusy(OtDischargeDocType type) => rowBusy && _busyDocType == type;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _PatientAvatar(name: item.patient?.fullName ?? 'Patient'),
            const SizedBox(width: 8),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item.patient?.fullName ?? 'Patient',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: AppColors.darkNavy,
                    ),
                  ),
                  if (item.patient?.patientCode.isNotEmpty ?? false)
                    Text(
                      item.patient!.patientCode,
                      style: const TextStyle(
                        fontSize: 11,
                        color: AppColors.textSecondary,
                      ),
                    ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            dischargeQueueStatusBadge(item),
          ],
        ),
        const SizedBox(height: 6),
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: AppFieldGrid(
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
              ),
            ),
            const SizedBox(width: 8),
            DeskPanelBadge(
              label: hasInvoice ? 'Generated' : 'Pending',
              color: hasInvoice ? AppColors.green : AppColors.orange,
            ),
          ],
        ),
        const SizedBox(height: 10),
        // Client request (2026-10-03): the print icons used to sit in their own
        // row below this one, pushing the card taller for no reason — View and
        // the invoice action already live on one row with space to spare on
        // the right once Generate isn't showing, so the print icons now share
        // it instead.
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            _viewIconButton(() => _viewDetails(item)),
            if (_tab == 'queue' && !hasInvoice && _canGenerate)
              FilledButton.icon(
                onPressed: rowBusy ? null : () => _generateInvoice(item),
                icon: rowBusy
                    ? const SizedBox(
                        width: 12,
                        height: 12,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Icon(Icons.receipt_rounded, size: 14),
                label: const Text('Generate'),
                style: _miniFilledStyle(AppColors.teal),
              )
            else if (hasInvoice)
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (_canBillPrint)
                    otDischargeDocButton(
                      type: OtDischargeDocType.summaryBill,
                      busy: docBusy(OtDischargeDocType.summaryBill),
                      done: item.summaryBillPrintedAt != null,
                      onPressed: () =>
                          _printDoc(item, OtDischargeDocType.summaryBill),
                      compact: true,
                    ),
                  if (_canDischargePrint) ...[
                    const SizedBox(width: 8),
                    otDischargeDocButton(
                      type: OtDischargeDocType.discharge,
                      busy: docBusy(OtDischargeDocType.discharge),
                      done: item.dischargePrintedAt != null,
                      onPressed: () =>
                          _printDoc(item, OtDischargeDocType.discharge),
                      compact: true,
                    ),
                  ],
                  if (_canCertificatePrint) ...[
                    const SizedBox(width: 8),
                    otDischargeDocButton(
                      type: OtDischargeDocType.certificate,
                      busy: docBusy(OtDischargeDocType.certificate),
                      done: item.certificatePrintedAt != null,
                      onPressed: () =>
                          _printDoc(item, OtDischargeDocType.certificate),
                      compact: true,
                    ),
                  ],
                ],
              ),
          ],
        ),
      ],
    );
  }
}

// ── 5. Counselling ───────────────────────────────────────────────────────

class CounsellingDeskPanel extends StatefulWidget {
  final VoidCallback onOpenFullPage;
  final String? requestedTab;
  final ValueChanged<String>? onDeskTab;
  final ValueChanged<Map<String, int>>? onCounts;
  const CounsellingDeskPanel({
    super.key,
    required this.onOpenFullPage,
    this.requestedTab,
    this.onDeskTab,
    this.onCounts,
  });

  @override
  State<CounsellingDeskPanel> createState() => _CounsellingDeskPanelState();
}

class _CounsellingDeskPanelState extends State<CounsellingDeskPanel>
    implements Refreshable {
  static const _tabValues = ['queue', 'history', 'payments'];
  static const _tabLabels = {
    'queue': 'Awaiting Counselling',
    'history': 'Counselled Patients',
    'payments': 'Payment Status',
  };

  String _tab = 'queue';
  bool _loading = true;
  String? _error;
  final Map<String, List<OtBookingSummary>> _items = {};
  final Map<String, int> _totals = {};

  @override
  void initState() {
    super.initState();
    if (widget.requestedTab != null) _tab = widget.requestedTab!;
    _load();
  }

  @override
  void didUpdateWidget(covariant CounsellingDeskPanel oldWidget) {
    super.didUpdateWidget(oldWidget);
    final next = widget.requestedTab;
    if (next != null && next != _tab) setState(() => _tab = next);
  }

  @override
  Future<void> refreshSilently() => _load();

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final queue = OtCounsellorService.instance.fetchBookings(
        filter: 'queue',
        perPage: kDeskPanelMiniPageSize,
      );
      final history = OtCounsellorService.instance.fetchBookings(
        filter: 'history',
        perPage: kDeskPanelMiniPageSize,
      );
      final payments = OtCounsellorService.instance.fetchBookings(
        filter: 'payments',
        perPage: kDeskPanelMiniPageSize,
      );
      final results = await Future.wait([queue, history, payments]);
      if (!mounted) return;
      setState(() {
        _items['queue'] = results[0].items;
        _items['history'] = results[1].items;
        _items['payments'] = results[2].filter == 'payments'
            ? results[2].items
            : const [];
        _totals['queue'] = results[0].meta.total;
        _totals['history'] = results[1].meta.total;
        _totals['payments'] = results[2].filter == 'payments'
            ? results[2].meta.total
            : 0;
        _loading = false;
      });
      widget.onCounts?.call(Map<String, int>.from(_totals));
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString().replaceFirst('Exception: ', '');
        _loading = false;
      });
    }
  }

  ({String label, Color color}) _status(OtBookingSummary item) {
    if (_tab == 'payments') {
      return (
        label: 'Paid',
        color: item.otStatus == OtStatus.paid
            ? AppColors.orange
            : AppColors.green,
      );
    }
    if (_tab == 'queue' && item.otStatus == OtStatus.surgeryRecommended) {
      return (label: 'Surgery Recommended', color: AppColors.orange);
    }
    if (_tab == 'queue') return (label: 'Booked', color: AppColors.blue);
    return (label: OtStatus.label(item.otStatus), color: AppColors.green);
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

  @override
  Widget build(BuildContext context) {
    final items = _items[_tab] ?? const [];
    return DeskHomePanel(
      icon: _tab == 'payments'
          ? Icons.shield_outlined
          : Icons.hourglass_bottom_rounded,
      title: _tabLabels[_tab]!,
      totalCount: _totals[_tab] ?? items.length,
      onOpenFullPage: widget.onOpenFullPage,
      tabs: _tabValues
          .map(
            (v) => DeskPanelTabDef(
              value: v,
              label: _tabLabels[v]!,
              count: _totals[v] ?? 0,
            ),
          )
          .toList(),
      selectedTab: _tab,
      onTabSelected: (v) {
        setState(() => _tab = v);
        widget.onDeskTab?.call(v);
      },
      loading: _loading,
      error: _error,
      onRetry: _load,
      emptyMessage: switch (_tab) {
        'history' => 'No counselled patients yet.',
        'payments' => 'No bookings paid yet.',
        _ => 'No bookings awaiting counselling.',
      },
      emptyIcon: Icons.forum_outlined,
      rows: items.map((item) => DeskPanelRowCard(child: _row(item))).toList(),
    );
  }

  // Square avatar (not the circle _PatientAvatar) to match the convention
  // every other patient-row list in the app now uses (Patients screen,
  // Today Added Patients, My Primary Queue — see AppRecordCard). A thin
  // divider between the identity row and the meta/action area below gives
  // the same "two zones" structure AppRecordCard's own fields-divider does,
  // instead of a single dense block with no visual break.
  Widget _row(OtBookingSummary item) {
    final status = _status(item);
    final payments = _tab == 'payments';
    final name = item.patient?.fullName ?? 'Patient';
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 34,
              height: 34,
              decoration: BoxDecoration(
                color: AppColors.primary,
                borderRadius: BorderRadius.circular(10),
              ),
              alignment: Alignment.center,
              child: Text(
                name.trim().isNotEmpty ? name.trim()[0].toUpperCase() : '?',
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w800,
                  fontSize: 14,
                ),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 13.5,
                      fontWeight: FontWeight.w700,
                      color: AppColors.darkNavy,
                    ),
                  ),
                  if (item.patient?.patientCode.isNotEmpty ?? false)
                    Text(
                      item.patient!.patientCode,
                      style: const TextStyle(
                        fontSize: 11,
                        color: AppColors.textSecondary,
                      ),
                    ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            DeskPanelBadge(label: status.label, color: status.color),
          ],
        ),
        const SizedBox(height: 8),
        Container(height: 1, color: AppColors.primaryA08),
        const SizedBox(height: 8),
        // AppFieldGrid, not a Wrap of chips — a Wrap only drops an item to the
        // next line once it literally doesn't fit, so one long phone number or
        // surgery-type name pushed the last chip down while leaving the rest
        // of that first line's width empty next to it. AppFieldGrid always
        // lays fields out in fixed 2-column rows (each column truncates its
        // own text with an ellipsis instead), so the row's width is always
        // actually used.
        AppFieldGrid(
          fields: [
            AppRecordField(
              'Phone',
              item.patient?.contactNo ?? '—',
              icon: Icons.phone_outlined,
              color: RecordFieldColors.phone,
            ),
            if (payments)
              AppRecordField(
                'Package',
                (item.packageAmount ?? 0).toStringAsFixed(2),
                icon: Icons.currency_rupee_rounded,
                color: RecordFieldColors.package,
              )
            else ...[
              AppRecordField(
                'Eye',
                item.eye ?? '—',
                icon: Icons.remove_red_eye_outlined,
                color: RecordFieldColors.caseType,
              ),
              AppRecordField(
                'Surgery Type',
                item.otType ?? '—',
                icon: Icons.medical_services_outlined,
                color: RecordFieldColors.caseType,
              ),
            ],
          ],
        ),
        if (!payments) ...[
          const SizedBox(height: 10),
          // View (a quiet, universal icon action) on the left, Counsel (the
          // one thing this row actually wants you to do) as a full-weight
          // pill pinned to the right — spaceBetween instead of a cramped Wrap
          // uses the row's whole width on purpose instead of leaving it empty
          // next to two small buttons huddled on the left.
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              _viewIconButton(() => showOtCounsellorBookingView(context, item)),
              if (_tab == 'queue')
                FilledButton.icon(
                  onPressed: () => _counsel(item),
                  style: _miniFilledStyle(AppColors.primary),
                  icon: const Icon(Icons.forum_rounded, size: 15),
                  label: const Text('Counsel'),
                ),
            ],
          ),
        ],
      ],
    );
  }
}

/// Web pull 2026-10-07 — new dedicated `ot_appointment` role/desk, mirroring
/// `ot-appointment-panel.blade.php`: two tabs, "OT Appointments" (today's
/// still-pending appointments) and "Completed" (walked in today), each row
/// an Appt#/Patient/Phone/Doctor/Status card — Edit is the only action, and
/// only on the pending tab (same as the full `OtAppointmentListScreen`).
/// Reuses that same screen's `OtAppointmentService.list()` (tab:
/// 'booked'/'completed'), scoped to today only, instead of a bespoke
/// desk-only endpoint.
class OtAppointmentDeskPanel extends StatefulWidget {
  final VoidCallback onOpenFullPage;
  final String? requestedTab;
  final ValueChanged<String>? onDeskTab;
  final ValueChanged<Map<String, int>>? onCounts;
  const OtAppointmentDeskPanel({
    super.key,
    required this.onOpenFullPage,
    this.requestedTab,
    this.onDeskTab,
    this.onCounts,
  });

  @override
  State<OtAppointmentDeskPanel> createState() =>
      _OtAppointmentDeskPanelState();
}

class _OtAppointmentDeskPanelState extends State<OtAppointmentDeskPanel>
    implements Refreshable {
  // UI-facing tab keys — 'queue'/'history', not the API's own 'booked'/
  // 'completed' vocabulary, so this panel's default tab lines up with the
  // dashboard's one shared `_deskTab` field (defaults to 'queue' — every
  // sibling panel's first tab already is 'queue', so this one matches
  // instead of needing its own special-cased default).
  static const _tabValues = ['queue', 'history'];
  static const _tabLabels = {
    'queue': 'OT Appointments',
    'history': 'Completed',
  };

  String _tab = 'queue';
  bool _loading = true;
  String? _error;
  final Map<String, List<OtAppointmentItem>> _items = {};
  final Map<String, int> _totals = {};

  @override
  void initState() {
    super.initState();
    if (_tabValues.contains(widget.requestedTab)) {
      _tab = widget.requestedTab!;
    }
    _load();
  }

  @override
  void didUpdateWidget(covariant OtAppointmentDeskPanel oldWidget) {
    super.didUpdateWidget(oldWidget);
    final next = widget.requestedTab;
    if (next != null && next != _tab && _tabValues.contains(next)) {
      setState(() => _tab = next);
    }
  }

  @override
  Future<void> refreshSilently() => _load();

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final today = toIsoDate(DateTime.now());
      final booked = OtAppointmentService.instance.list(
        tab: 'booked',
        fromDate: today,
        toDate: today,
        perPage: kDeskPanelMiniPageSize,
      );
      final completed = OtAppointmentService.instance.list(
        tab: 'completed',
        fromDate: today,
        toDate: today,
        perPage: kDeskPanelMiniPageSize,
      );
      final results = await Future.wait([booked, completed]);
      if (!mounted) return;
      setState(() {
        _items['queue'] = results[0].items;
        _items['history'] = results[1].items;
        _totals['queue'] = results[0].bookedCount;
        _totals['history'] = results[1].completedCount;
        _loading = false;
      });
      widget.onCounts?.call(Map<String, int>.from(_totals));
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString().replaceFirst('Exception: ', '');
        _loading = false;
      });
    }
  }

  void _editAppointment(OtAppointmentItem item) {
    Navigator.of(context)
        .push(
          appRoute(
            OtAppointmentFormScreen(item: item, onSaved: _load),
          ),
        )
        .then((_) => _load());
  }

  @override
  Widget build(BuildContext context) {
    final items = _items[_tab] ?? const [];
    return DeskHomePanel(
      icon: _tab == 'history'
          ? Icons.check_circle_outline_rounded
          : Icons.calendar_today_rounded,
      title: _tabLabels[_tab]!,
      totalCount: _totals[_tab] ?? items.length,
      onOpenFullPage: widget.onOpenFullPage,
      tabs: _tabValues
          .map(
            (v) => DeskPanelTabDef(
              value: v,
              label: _tabLabels[v]!,
              count: _totals[v] ?? 0,
            ),
          )
          .toList(),
      selectedTab: _tab,
      onTabSelected: (v) {
        setState(() => _tab = v);
        widget.onDeskTab?.call(v);
      },
      loading: _loading,
      error: _error,
      onRetry: _load,
      emptyMessage: _tab == 'history'
          ? 'No appointments completed today.'
          : 'No OT appointments today.',
      emptyIcon: Icons.event_note_rounded,
      rows: items.map((item) => DeskPanelRowCard(child: _row(item))).toList(),
    );
  }

  Widget _row(OtAppointmentItem item) {
    final name = item.fullName;
    final showEdit = _tab == 'queue' &&
        PermissionService.instance.can(Perm.otAppointmentEdit);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 34,
              height: 34,
              decoration: BoxDecoration(
                color: AppColors.primary,
                borderRadius: BorderRadius.circular(10),
              ),
              alignment: Alignment.center,
              child: Text(
                name.trim().isNotEmpty ? name.trim()[0].toUpperCase() : '?',
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w800,
                  fontSize: 14,
                ),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 13.5,
                      fontWeight: FontWeight.w700,
                      color: AppColors.darkNavy,
                    ),
                  ),
                  Text(
                    item.appointmentNumber,
                    style: const TextStyle(
                      fontSize: 11,
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                StatusBadge.otAppointmentStage(
                  item.stageBadgeClass,
                  item.stageLabel,
                ),
                if ((item.stageSub ?? '').isNotEmpty) ...[
                  const SizedBox(height: 3),
                  Text(
                    item.stageSub!,
                    style: const TextStyle(
                      fontSize: 10,
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
              ],
            ),
          ],
        ),
        const SizedBox(height: 8),
        Container(height: 1, color: AppColors.primaryA08),
        const SizedBox(height: 8),
        AppFieldGrid(
          fields: [
            AppRecordField(
              'Phone',
              item.mobileNo,
              icon: Icons.phone_outlined,
              color: RecordFieldColors.phone,
            ),
            if (item.doctor != null)
              AppRecordField(
                'Doctor',
                item.doctor!.name,
                icon: Icons.medical_services_outlined,
                color: RecordFieldColors.doctor,
              ),
          ],
        ),
        if (showEdit) ...[
          const SizedBox(height: 10),
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              FilledButton.icon(
                onPressed: () => _editAppointment(item),
                style: _miniFilledStyle(const Color(0xFFE67E22)),
                icon: const Icon(Icons.edit_outlined, size: 15),
                label: const Text('Edit'),
              ),
            ],
          ),
        ],
      ],
    );
  }
}
