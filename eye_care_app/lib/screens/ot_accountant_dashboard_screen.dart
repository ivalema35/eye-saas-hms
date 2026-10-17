import 'package:flutter/material.dart';
import '../widgets/app_refresh_indicator.dart';
import 'package:printing/printing.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/permissions.dart';
import '../models/ot_accountant_models.dart';
import '../models/ot_booking_models.dart';
import '../services/auth_service.dart';
import '../services/ot_accountant_service.dart';
import '../services/settings_service.dart';
import '../services/ot_receipt_pdf_service.dart';
import '../services/permission_service.dart';
import '../utils/date_format.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_record_card.dart';
import '../widgets/app_pagination_bar.dart';
import '../widgets/ot_desk_filter_bar.dart';
import 'ot_money_report_screen.dart';
import 'ot_payment_screen.dart';
import '../utils/app_route.dart';
import '../utils/currency_format.dart';
import '../widgets/skeleton.dart';

/// Round 3 Phase 5 — Accountant / Billing. Web parity: Add Payment / View /
/// Receipt on each card (not a drill-in that requires ot_invoice_view).
class OtAccountantDashboardScreen extends StatefulWidget {
  const OtAccountantDashboardScreen({super.key});

  @override
  State<OtAccountantDashboardScreen> createState() =>
      _OtAccountantDashboardScreenState();
}

class _OtAccountantDashboardScreenState
    extends State<OtAccountantDashboardScreen> {
  List<OtBookingSummary> _items = [];
  OtPaginationMeta? _meta;
  OtMoneySummary? _moneySummary;
  bool _loading = true;
  String? _error;
  int _page = 1;

  String _filter = 'today';

  DateTime _fromDate = DateTime.now();
  DateTime _toDate = DateTime.now();

  bool get _canBillPrint => PermissionService.instance.can(Perm.otBillPrint);
  bool get _canPaymentRecord =>
      PermissionService.instance.can(Perm.otPaymentRecord);

  @override
  void initState() {
    super.initState();
    _load();
  }

  void _setFilter(String f) {
    if (f == _filter) return;
    setState(() {
      _filter = f;
      _page = 1;
    });
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      // Web pull 2026-10-05 (commit 65865858) — all three tabs (not just
      // Completed) default to today and accept the same date filter.
      final result = await OtAccountantService.instance.fetchBookings(
        filter: _filter,
        fromDate: toIsoDate(_fromDate),
        toDate: toIsoDate(_toDate),
        page: _page,
      );
      if (mounted)
        setState(() {
          _items = result.items;
          _meta = result.meta;
          _moneySummary = result.moneySummary;
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

  void _setDateRange(DateTimeRange r) {
    setState(() {
      _fromDate = r.start;
      _toDate = r.end;
      _page = 1;
    });
    _load();
  }

  void _openAddPayment(OtBookingSummary item) {
    showOtRecordPaymentSheet(context, bookingId: item.id, onSaved: _load);
  }

  void _openRefund(OtBookingSummary item) {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => OtAccountantRefundDialog(bookingId: item.id),
    ).then((_) => _load());
  }

  void _viewDetails(OtBookingSummary item) {
    // Web View modal uses dashboard row data only — no payment-status API.
    showOtAccountantBookingViewDialog(context, item);
  }

  Future<void> _viewLatestReceipt(OtBookingSummary item) async {
    final paymentId = item.latestPaymentId;
    if (paymentId == null) {
      if (mounted) showAppSnackBar(context, 'No payments recorded yet.');
      return;
    }
    if (mounted) {
      showDialog(
        context: context,
        builder: (_) => OtAccountantReceiptDialog(
          paymentId: paymentId,
          patientName: item.patient?.fullName,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      body: Column(
        children: [
          _buildHeader(),
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
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(8, 10, 20, 6),
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
                      Icons.account_balance_wallet_rounded,
                      color: Colors.white,
                      size: 20,
                    ),
                  ),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Text(
                      'Accountant / Billing',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                        letterSpacing: -0.2,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            // Same shared OtDeskFilterBar every other OT desk screen uses —
            // the 3-way Today/Completed/Refunds switch is now the same
            // full-width Material 3 segmented control (options generalized to
            // 3), replacing the separate TabBar/TabController this screen
            // used to run on its own.
            OtDeskFilterBar(
              options: const [
                OtFilterOption('today', 'Today'),
                OtFilterOption('completed', 'Completed'),
                OtFilterOption('refunds', 'Refunds'),
              ],
              selected: _filter,
              onChanged: _setFilter,
              // Web pull 2026-10-05 (commit 65865858) — date range shows on
              // every tab now, not just Completed.
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

  Widget _buildMoneySummary() {
    final s = _moneySummary;
    if (s == null) return const SizedBox.shrink();
    Widget stat(String label, String value, Color color) => Expanded(
      child: Column(
        children: [
          Text(
            value,
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
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
      child: Material(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.md),
        child: InkWell(
          onTap: () =>
              Navigator.push(context, appRoute(const OtMoneyReportScreen())),
          borderRadius: BorderRadius.circular(AppRadius.md),
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 12),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(AppRadius.md),
              border: Border.all(
                color: AppColors.primary.withValues(alpha: 0.08),
              ),
            ),
            child: Row(
              children: [
                stat(
                  'Collected',
                  '${currentCurrencySymbol()}${s.collected.toStringAsFixed(0)}',
                  AppColors.green,
                ),
                stat(
                  'Refunded',
                  '${currentCurrencySymbol()}${s.refunded.toStringAsFixed(0)}',
                  AppColors.red,
                ),
                stat(
                  'Net',
                  '${currentCurrencySymbol()}${s.net.toStringAsFixed(0)}',
                  AppColors.primary,
                ),
                const Padding(
                  padding: EdgeInsets.only(right: 8),
                  child: Icon(
                    Icons.chevron_right_rounded,
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) return const AppSkeletonList();
    if (_error != null) return AppErrorState(message: _error!, onRetry: _load);
    if (_items.isEmpty)
      return AppEmptyState(
        message: 'No bookings here.',
        icon: Icons.account_balance_wallet_outlined,
        onRefresh: _load,
      );

    return AppRefreshIndicator(
      color: AppColors.primary,
      onRefresh: _load,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 20),
        itemCount: _items.length + 1,
        itemBuilder: (_, i) {
          if (i == 0) {
            return Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: _buildMoneySummary(),
            );
          }
          final item = _items[i - 1];
          return AnimatedListItem(index: i, child: _buildRow(item));
        },
      ),
    );
  }

  // Client request (2026-10-03): this full screen's rows still used the old
  // oversized labeled-pill buttons and plain text, while the exact same data
  // (same model, same actions) already got the icon-tagged AppRecordCard
  // treatment on the dashboard's compact Accountant desk panel
  // (`AccountantDeskPanel._row` in `desk_home_panels.dart`) — the two looked
  // like two different apps. This mirrors that panel's row exactly so
  // opening "full page" doesn't feel like a regression in polish.
  Widget _buildRow(OtBookingSummary item) {
    final ps = item.paymentStatus ?? 'pending';
    final isRefunds =
        _filter == 'refunds' || item.otStatus == OtStatus.surgeryRefused;
    final canAddPayment =
        _canPaymentRecord && !isRefunds && ps != 'paid' && ps != 'unpriced';

    late final String statusLabel;
    late final Color statusColor;
    if (isRefunds) {
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

    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: AppRecordCard(
        leading: PatientAvatar(name: item.patient?.fullName ?? 'Patient'),
        title: item.patient?.fullName ?? 'Patient',
        subtitle: item.patient?.patientCode,
        trailing: AppStatusPill(label: statusLabel, color: statusColor),
        // Web parity (`accountant/dashboard.blade.php`): no OT Date column
        // on this list at all.
        fields: [
          if (isRefunds)
            AppRecordField(
              (item.refundableBalance ?? 0) > 0 ? 'Refundable' : 'Refunded',
              '${currentCurrencySymbol()}${((item.refundableBalance ?? 0) > 0 ? item.refundableBalance! : (item.totalRefunded ?? 0)).toStringAsFixed(0)}',
              icon: Icons.currency_rupee_rounded,
              color: (item.refundableBalance ?? 0) > 0
                  ? AppColors.orange
                  : AppColors.green,
            )
          else if (ps == 'partially_paid' && item.remainingBalance != null)
            AppRecordField(
              'Balance',
              '${currentCurrencySymbol()}${item.remainingBalance!.toStringAsFixed(0)}',
              icon: Icons.account_balance_wallet_outlined,
              color: RecordFieldColors.package,
            ),
        ],
        actions: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            viewIconButton(() => _viewDetails(item)),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              alignment: WrapAlignment.end,
              children: [
                if (isRefunds)
                  (item.refundableBalance ?? 0) > 0
                      ? FilledButton.icon(
                          onPressed: () => _openRefund(item),
                          style: miniFilledStyle(AppColors.red),
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
                    style: miniFilledStyle(AppColors.primary),
                    icon: const Icon(Icons.payments_rounded, size: 15),
                    label: Text(
                      ps == 'partially_paid' ? 'Add Balance' : 'Add Payment',
                    ),
                  ),
                if (_canBillPrint && item.latestPaymentId != null && !isRefunds)
                  OutlinedButton(
                    onPressed: () => _viewLatestReceipt(item),
                    style: miniOutlinedStyle(),
                    child: const Text('Receipt'),
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

// ── Full refund dialog ───────────────────────────────────────────────────────
// Amount is never editable — always the full refundable balance, matching
// web exactly (no partial-refund UI exists). See
// WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §4.

class OtAccountantRefundDialog extends StatefulWidget {
  final int bookingId;
  const OtAccountantRefundDialog({super.key, required this.bookingId});

  @override
  State<OtAccountantRefundDialog> createState() =>
      _OtAccountantRefundDialogState();
}

class _OtAccountantRefundDialogState extends State<OtAccountantRefundDialog> {
  bool _loading = true;
  String? _loadError;
  OtRefundFormData? _formData;
  bool _saving = false;

  final _receiptCtrl = TextEditingController();
  final _reasonCtrl = TextEditingController();
  String _paymentMode = 'cash';

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _receiptCtrl.dispose();
    _reasonCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _loadError = null;
    });
    try {
      final data = await OtAccountantService.instance.fetchRefundFormData(
        widget.bookingId,
      );
      if (mounted) {
        setState(() {
          _formData = data;
          _loading = false;
          _receiptCtrl.text = data.autoReceiptNumber;
          _reasonCtrl.text = 'Patient refused OT — full refund';
        });
      }
    } catch (e) {
      if (mounted)
        setState(() {
          _loadError = e.toString().replaceFirst('Exception: ', '');
          _loading = false;
        });
    }
  }

  Future<void> _save() async {
    setState(() => _saving = true);
    try {
      await OtAccountantService.instance.storeRefund(
        widget.bookingId,
        paymentMode: _paymentMode,
        receiptNumber: _receiptCtrl.text.trim(),
        reason: _reasonCtrl.text.trim(),
      );
      if (mounted) {
        Navigator.pop(context);
        showAppSnackBar(context, 'Full refund recorded', isSuccess: true);
      }
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        showAppSnackBar(
          context,
          e.toString().replaceFirst('Exception: ', ''),
          isError: true,
        );
      }
    }
  }

  Widget _readRow(String label, String value) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 2),
    child: Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontSize: 11.5,
            color: AppColors.textSecondary,
          ),
        ),
        Text(
          value,
          style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700),
        ),
      ],
    ),
  );

  InputDecoration _deco(String label) => InputDecoration(
    labelText: label,
    filled: true,
    fillColor: const Color(0xFFF0F6FB),
    border: OutlineInputBorder(
      borderRadius: BorderRadius.circular(AppRadius.md),
      borderSide: BorderSide.none,
    ),
  );

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppRadius.lg),
      ),
      title: const Text('Full Refund — Surgery Refused'),
      content: SizedBox(
        width: 360,
        child: _loading
            ? const SizedBox(
                height: 120,
                child: AppSkeletonList(
                  count: 1,
                  itemHeight: 90,
                  padding: EdgeInsets.zero,
                ),
              )
            : _loadError != null
            ? Text(_loadError!, style: const TextStyle(color: AppColors.red))
            : Builder(
                builder: (_) {
                  final f = _formData!;
                  return SingleChildScrollView(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF0F6FB),
                            borderRadius: BorderRadius.circular(AppRadius.md),
                          ),
                          child: Column(
                            children: [
                              _readRow(
                                'Patient',
                                f.booking.patient?.fullName ?? '—',
                              ),
                              _readRow(
                                'UHID',
                                f.booking.patient?.patientCode ?? '—',
                              ),
                              _readRow(
                                'Total Paid',
                                '${currentCurrencySymbol()}${f.totalPaid.toStringAsFixed(0)}',
                              ),
                              _readRow(
                                'Already Refunded',
                                '${currentCurrencySymbol()}${f.totalRefunded.toStringAsFixed(0)}',
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 12),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(
                            horizontal: 14,
                            vertical: 12,
                          ),
                          decoration: BoxDecoration(
                            color: AppColors.red.withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(AppRadius.md),
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text(
                                'Refund Amount (full)',
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              Text(
                                '${currentCurrencySymbol()}${f.refundAmount.toStringAsFixed(0)}',
                                style: const TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w900,
                                  color: AppColors.red,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 12),
                        Wrap(
                          spacing: 8,
                          children: ['cash', 'online']
                              .map(
                                (m) => ChoiceChip(
                                  label: Text(
                                    m[0].toUpperCase() + m.substring(1),
                                  ),
                                  selected: _paymentMode == m,
                                  onSelected: (_) =>
                                      setState(() => _paymentMode = m),
                                ),
                              )
                              .toList(),
                        ),
                        const SizedBox(height: 12),
                        TextFormField(
                          controller: _receiptCtrl,
                          decoration: _deco('Receipt Number'),
                        ),
                        const SizedBox(height: 12),
                        TextFormField(
                          controller: _reasonCtrl,
                          maxLines: 2,
                          decoration: _deco('Reason'),
                        ),
                      ],
                    ),
                  );
                },
              ),
      ),
      actions: [
        TextButton(
          onPressed: _saving ? null : () => Navigator.pop(context),
          child: const Text('Cancel'),
        ),
        if (_formData != null)
          ElevatedButton(
            onPressed: _saving ? null : _save,
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.red,
              foregroundColor: Colors.white,
            ),
            child: _saving
                ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Colors.white,
                    ),
                  )
                : const Text('Record Full Refund'),
          ),
      ],
    );
  }
}

// ── View details dialog ─────────────────────────────────────────────────────────

/// Reused directly by the dashboard's compact Accountant desk panel (see
/// `lib/widgets/desk_home_panels.dart`) — not just the full desk screen.
// Redesigned onto the shared showAppDetailSheet/AppDetailSectionCard/
// AppFieldGrid bottom-sheet pattern — see OtCounsellorDashboardScreen.openView
// for the full rationale. Was a StatefulWidget rendered directly as an
// AlertDialog's content; converted to a plain function (matching
// showOtWardBookingViewDialog/showOtAssistantBookingViewDialog/
// showOtDischargeBookingViewDialog's naming) since showAppDetailSheet owns
// the dialog/sheet shell itself now.
String _accountantDrName(String? name) {
  final n = name?.trim();
  if (n == null || n.isEmpty) return '';
  return RegExp(r'^dr\b\.?', caseSensitive: false).hasMatch(n) ? n : 'Dr. $n';
}

// Web parity (`_payment-view-modal.blade.php`) — status recomputed here from
// `payment_status`/`is_fully_refunded`/`refundable_balance`, not the plain
// queue-tab status badge, since the same modal is shared by
// Pending/Refunds/Completed.
(String, Color) _accountantDetailStatus(OtBookingSummary item) {
  final isRefused = item.otStatus == OtStatus.surgeryRefused;
  if (isRefused) {
    if (item.isFullyRefunded ?? false)
      return ('Fully Refunded', AppColors.green);
    if ((item.refundableBalance ?? 0) > 0)
      return ('Refund Pending', AppColors.red);
    return ('Surgery Refused', AppColors.textDisabled);
  }
  return switch (item.paymentStatus) {
    'paid' => ('Payment Completed', AppColors.green),
    'partially_paid' => ('Partially Paid', AppColors.blue),
    'unpriced' => ('Package Not Set', AppColors.textDisabled),
    _ => ('Payment Pending', AppColors.orange),
  };
}

Future<void> showOtAccountantBookingViewDialog(
  BuildContext context,
  OtBookingSummary item,
) async {
  final p = item.patient;
  final isRefused = item.otStatus == OtStatus.surgeryRefused;
  final (statusLabel, statusColor) = _accountantDetailStatus(item);
  final cur = currentCurrencySymbol();

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
  final counselledBy = item.counselling?.counsellorName != null
      ? '${item.counselling!.counsellorName}${item.counselling?.counselledAt != null ? ' · ${toDisplayDate(toHospitalTime(item.counselling!.counselledAt!))}' : ''}'
      : null;

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
    if (formatDateString(p?.appointmentDate, fallback: '').isNotEmpty)
      AppRecordField(
        'Visit Date',
        formatDateString(p?.appointmentDate, fallback: '—'),
        icon: Icons.event_outlined,
        color: RecordFieldColors.date,
      ),
    if (_accountantDrName(p?.doctor?.name).isNotEmpty)
      AppRecordField(
        'Consulting Doctor',
        _accountantDrName(p?.doctor?.name),
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
    if (formatPlainDate(item.surgeryDate) != null)
      AppRecordField(
        'OT Date',
        formatPlainDate(item.surgeryDate)!,
        icon: Icons.event_outlined,
        color: RecordFieldColors.date,
      ),
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
    if (package.isNotEmpty)
      AppRecordField(
        'Package',
        package,
        icon: Icons.inventory_2_outlined,
        color: RecordFieldColors.package,
      ),
    if (lens.isNotEmpty)
      AppRecordField(
        'Lens',
        lens,
        icon: Icons.lens_outlined,
        color: AppColors.textSecondary,
      ),
    if (mediclaim != null)
      AppRecordField(
        'Mediclaim',
        mediclaim ? 'Yes' : 'No',
        icon: Icons.health_and_safety_outlined,
        color: AppColors.textSecondary,
      ),
    if (_accountantDrName(item.otDoctor?.name).isNotEmpty)
      AppRecordField(
        'OT Doctor',
        _accountantDrName(item.otDoctor?.name),
        icon: Icons.person_outline_rounded,
        color: RecordFieldColors.doctor,
      ),
    if (counselledBy != null)
      AppRecordField(
        'Counselled By',
        counselledBy,
        icon: Icons.support_agent_outlined,
        color: AppColors.textSecondary,
      ),
  ];

  Widget amountTile(String label, String value, Color color) => Expanded(
    child: Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 9),
      decoration: BoxDecoration(
        color: AppColors.surfaceFill,
        borderRadius: BorderRadius.circular(AppRadius.md),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label.toUpperCase(),
            style: const TextStyle(
              fontSize: 8.5,
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
              fontSize: 13,
              fontWeight: FontWeight.w900,
              color: color,
            ),
          ),
        ],
      ),
    ),
  );

  await showAppDetailSheet(
    context,
    name: p?.fullName ?? 'Patient',
    code: 'Booking #${item.id}',
    pills: [AppStatusPill(label: statusLabel, color: statusColor)],
    sections: [
      AppDetailSectionCard(
        title: 'Payment Summary',
        icon: Icons.account_balance_wallet_outlined,
        child: Row(
          children: [
            amountTile(
              'Package Amount',
              '$cur${(item.packageAmount ?? 0).toStringAsFixed(2)}',
              AppColors.darkNavy,
            ),
            const SizedBox(width: 8),
            amountTile(
              'Paid',
              '$cur${(item.totalPaid ?? 0).toStringAsFixed(2)}',
              AppColors.green,
            ),
            const SizedBox(width: 8),
            if (isRefused)
              amountTile(
                'Refunded',
                '$cur${(item.totalRefunded ?? 0).toStringAsFixed(2)}',
                AppColors.red,
              )
            else
              amountTile(
                'Balance',
                '$cur${(item.remainingBalance ?? 0).toStringAsFixed(2)}',
                (item.remainingBalance ?? 0) > 0
                    ? AppColors.orange
                    : AppColors.green,
              ),
          ],
        ),
      ),
      AppDetailSectionCard(
        title: 'Patient Details',
        icon: Icons.badge_outlined,
        child: AppFieldGrid(fields: patientFields),
      ),
      AppDetailSectionCard(
        title: 'Surgery & Counselling',
        icon: Icons.local_hospital_outlined,
        child: AppFieldGrid(fields: otFields),
      ),
      AppDetailSectionCard(
        title: 'Payments',
        icon: Icons.receipt_long_outlined,
        child: item.payments.isEmpty
            ? const Text(
                'No payment recorded yet.',
                style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
              )
            : Column(
                children: item.payments
                    .map(
                      (pay) => Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    pay.receiptNumber ?? '-',
                                    style: const TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                  Text(
                                    '${pay.paidAt != null ? formatDateTimeString(pay.paidAt) : '-'} · ${pay.paymentMode.isEmpty ? '-' : pay.paymentMode[0].toUpperCase() + pay.paymentMode.substring(1)}',
                                    style: const TextStyle(
                                      fontSize: 10.5,
                                      color: AppColors.textSecondary,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            Text(
                              '$cur${pay.packageAmount.toStringAsFixed(2)}',
                              style: const TextStyle(
                                fontSize: 12.5,
                                fontWeight: FontWeight.w800,
                                color: AppColors.green,
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

// ── Receipt dialog ─────────────────────────────────────────────────────────────

/// Reused directly by the dashboard's compact Accountant desk panel (see
/// `lib/widgets/desk_home_panels.dart`) — not just the full desk screen.
class OtAccountantReceiptDialog extends StatefulWidget {
  final int paymentId;
  final String? patientName;
  const OtAccountantReceiptDialog({
    super.key,
    required this.paymentId,
    this.patientName,
  });

  @override
  State<OtAccountantReceiptDialog> createState() =>
      _OtAccountantReceiptDialogState();
}

class _OtAccountantReceiptDialogState extends State<OtAccountantReceiptDialog> {
  bool _loading = true;
  String? _error;
  OtPaymentReceipt? _receipt;

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
      final r = await OtAccountantService.instance.fetchReceipt(
        widget.paymentId,
      );
      if (mounted)
        setState(() {
          _receipt = r;
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

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppRadius.lg),
      ),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 400, maxHeight: 640),
        child: _loading
            ? const SizedBox(
                height: 200,
                child: AppSkeletonList(
                  count: 2,
                  itemHeight: 80,
                  padding: EdgeInsets.zero,
                ),
              )
            : _error != null
            ? Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      _error!,
                      style: const TextStyle(color: AppColors.red),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 12),
                    ElevatedButton(
                      onPressed: _load,
                      child: const Text('Retry'),
                    ),
                  ],
                ),
              )
            : Padding(
                padding: const EdgeInsets.all(20),
                child: SingleChildScrollView(
                  child: _DashboardReceiptPreview(
                    receipt: _receipt!,
                    patientNameFallback: widget.patientName,
                  ),
                ),
              ),
      ),
    );
  }
}

// Matches web's `d M Y, h:i A` receipt timestamp format exactly.
class _DashboardReceiptPreview extends StatefulWidget {
  final OtPaymentReceipt receipt;
  final String? patientNameFallback;

  const _DashboardReceiptPreview({
    required this.receipt,
    this.patientNameFallback,
  });

  @override
  State<_DashboardReceiptPreview> createState() =>
      _DashboardReceiptPreviewState();
}

class _DashboardReceiptPreviewState extends State<_DashboardReceiptPreview> {
  bool _printing = false;

  OtPaymentReceipt get receipt => widget.receipt;
  String? get patientNameFallback => widget.patientNameFallback;

  bool get _canBillPrint => PermissionService.instance.can(Perm.otBillPrint);

  Future<void> _printOrShare() async {
    setState(() => _printing = true);
    try {
      final hospital = AuthService.instance.cachedHospital;
      // Web's Settings → Print "Letter Pad" toggle — see
      // OtReceiptPdfService.generatePdf()'s doc comment. Best-effort: a
      // failed fetch just falls back to the existing branded header.
      bool letterPadAvailable = false;
      try {
        final settings = await SettingsService.instance.fetchSettings();
        letterPadAvailable = settings.letterPadAvailable;
      } catch (_) {}
      final bytes = await OtReceiptPdfService.instance.generatePdf(
        hospitalName: hospital?.name ?? '',
        receipt: receipt,
        currencySymbol: currentCurrencySymbol(),
        patientNameFallback: patientNameFallback,
        hospitalLogoUrl: hospital?.logoUrl,
        letterPadAvailable: letterPadAvailable,
      );
      await Printing.layoutPdf(
        onLayout: (_) async => bytes,
        name:
            'Receipt_${receipt.payment.receiptNumber ?? receipt.payment.id}.pdf',
      );
    } catch (e) {
      if (mounted)
        showAppSnackBar(
          context,
          e.toString().replaceFirst('Exception: ', ''),
          isError: true,
        );
    } finally {
      if (mounted) setState(() => _printing = false);
    }
  }

  Widget _box(String title, List<Widget> rows) => Container(
    width: double.infinity,
    padding: const EdgeInsets.all(14),
    margin: const EdgeInsets.only(bottom: 10),
    decoration: BoxDecoration(
      color: const Color(0xFFF7FBFE),
      borderRadius: BorderRadius.circular(AppRadius.md),
      border: Border.all(color: AppColors.primary.withValues(alpha: 0.10)),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w800,
            color: AppColors.darkNavy,
          ),
        ),
        const SizedBox(height: 8),
        ...rows,
      ],
    ),
  );

  Widget _row(String label, String value) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 3),
    child: Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
        ),
        Flexible(
          child: Text(
            value,
            textAlign: TextAlign.right,
            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
          ),
        ),
      ],
    ),
  );

  @override
  Widget build(BuildContext context) {
    final p = receipt.payment;
    final remaining = (receipt.requiredTotal - receipt.totalPaid).clamp(
      0,
      double.infinity,
    );
    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          children: [
            Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: AppColors.primary.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(
                Icons.receipt_long_rounded,
                color: AppColors.primary,
                size: 18,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Payment Receipt',
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w800,
                      color: AppColors.darkNavy,
                    ),
                  ),
                  Text(
                    p.receiptNumber ?? '—',
                    style: const TextStyle(
                      fontSize: 11,
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 18),
        Text(
          '${currentCurrencySymbol()}${p.packageAmount.toStringAsFixed(2)}',
          textAlign: TextAlign.center,
          style: TextStyle(
            fontSize: 26,
            fontWeight: FontWeight.w900,
            color: AppColors.primary,
          ),
        ),
        const SizedBox(height: 18),
        _box('Patient', [
          _row('Name', receipt.patient?.fullName ?? patientNameFallback ?? '—'),
          _row('UHID', receipt.patient?.patientCode ?? '—'),
          _row('Mobile', receipt.patient?.contactNo ?? '—'),
        ]),
        _box('Payment Details', [
          _row('Receipt No.', p.receiptNumber ?? '—'),
          _row('Mode', p.paymentMode.toUpperCase()),
          _row('Mediclaim', p.hasMediclaim ? 'Yes' : 'No'),
          _row('Paid At', formatDateTimeString(p.paidAt)),
          _row('Recorded By', p.recordedBy?.name ?? '—'),
        ]),
        _box('Package Balance', [
          _row(
            'Package Total',
            '${currentCurrencySymbol()}${receipt.requiredTotal.toStringAsFixed(0)}',
          ),
          _row(
            'Total Paid',
            '${currentCurrencySymbol()}${receipt.totalPaid.toStringAsFixed(0)}',
          ),
          _row(
            'Remaining',
            '${currentCurrencySymbol()}${remaining.toStringAsFixed(0)}',
          ),
          _row(
            'Status',
            paymentStatusLabel(
              receipt.bookingPaymentStatus ?? 'pending',
              verbose: true,
            ),
          ),
        ]),
        const SizedBox(height: 4),
        const Text(
          'This is a computer-generated receipt for OT surgery charges.',
          textAlign: TextAlign.center,
          style: TextStyle(
            fontSize: 10.5,
            color: AppColors.textSecondary,
            fontStyle: FontStyle.italic,
          ),
        ),
        const SizedBox(height: 14),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            if (_canBillPrint)
              TextButton.icon(
                onPressed: _printing ? null : _printOrShare,
                icon: _printing
                    ? const SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.print_outlined, size: 18),
                label: const Text('Print / Share'),
              ),
            if (_canBillPrint) const SizedBox(width: 8),
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Close'),
            ),
          ],
        ),
      ],
    );
  }
}
