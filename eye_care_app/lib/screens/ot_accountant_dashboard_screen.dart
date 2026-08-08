import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/ot_accountant_models.dart';
import '../models/ot_booking_models.dart';
import '../services/ot_accountant_service.dart';
import '../utils/app_route.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_pagination_bar.dart';
import 'ot_payment_screen.dart';

/// Round 3 Phase 5 — Accountant / Billing. `today` = pending-payment queue
/// (status counselled/paid, today's surgery date); `completed` = payment
/// done onward.
class OtAccountantDashboardScreen extends StatefulWidget {
  const OtAccountantDashboardScreen({super.key});

  @override
  State<OtAccountantDashboardScreen> createState() => _OtAccountantDashboardScreenState();
}

class _OtAccountantDashboardScreenState extends State<OtAccountantDashboardScreen> with SingleTickerProviderStateMixin {
  late final TabController _tabCtrl;
  List<OtBookingSummary> _items = [];
  OtPaginationMeta? _meta;
  OtMoneySummary? _moneySummary;
  bool _loading = true;
  String? _error;
  int _page = 1;

  static const _filters = ['today', 'completed', 'refunds'];
  String get _filter => _filters[_tabCtrl.index];

  @override
  void initState() {
    super.initState();
    _tabCtrl = TabController(length: _filters.length, vsync: this);
    _tabCtrl.addListener(() {
      if (!_tabCtrl.indexIsChanging) return;
      setState(() => _page = 1);
      _load();
    });
    _load();
  }

  @override
  void dispose() {
    _tabCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final result = await OtAccountantService.instance.fetchBookings(filter: _filter, page: _page);
      if (mounted) setState(() { _items = result.items; _meta = result.meta; _moneySummary = result.moneySummary; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  void _open(OtBookingSummary item) {
    if (item.otStatus == OtStatus.surgeryRefused) {
      if ((item.refundableBalance ?? 0) > 0) _openRefund(item);
      return;
    }
    final name = item.patient?.fullName ?? 'Patient';
    Navigator.of(context).push(appRoute(OtPaymentScreen(bookingId: item.id, patientName: name))).then((_) => _load());
  }

  void _openRefund(OtBookingSummary item) {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => _RefundDialog(bookingId: item.id),
    ).then((_) => _load());
  }

  Future<void> _viewDetails(OtBookingSummary item) async {
    showDialog(context: context, builder: (_) => _ViewDetailsDialog(item: item));
  }

  Future<void> _viewLatestReceipt(OtBookingSummary item) async {
    try {
      final status = await OtAccountantService.instance.fetchPaymentStatus(item.id);
      if (status.payments.isEmpty) {
        if (mounted) showAppSnackBar(context, 'No payments recorded yet.');
        return;
      }
      if (mounted) showDialog(context: context, builder: (_) => _ReceiptDialog(paymentId: status.payments.last.id, patientName: item.patient?.fullName));
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      body: Column(children: [
        _buildHeader(),
        Expanded(child: _buildBody()),
        if (_meta != null) Padding(padding: const EdgeInsets.symmetric(vertical: 8), child: AppPaginationBar(currentPage: _meta!.currentPage, totalPages: _meta!.lastPage, onPageChange: (p) { setState(() => _page = p); _load(); })),
      ]),
    );
  }

  Widget _buildHeader() {
    return Container(
      decoration: BoxDecoration(gradient: LinearGradient(colors: [AppColors.primary, AppColors.blueLight], begin: Alignment.topLeft, end: Alignment.bottomRight)),
      child: SafeArea(
        bottom: false,
        child: Column(children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(8, 10, 20, 6),
            child: Row(children: [
              IconButton(icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20), onPressed: () => Navigator.pop(context)),
              Container(width: 40, height: 40, decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(10)), child: const Icon(Icons.account_balance_wallet_rounded, color: Colors.white, size: 20)),
              const SizedBox(width: 12),
              const Expanded(child: Text('Accountant / Billing', style: TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.w800, letterSpacing: -0.2))),
            ]),
          ),
          TabBar(controller: _tabCtrl, indicatorColor: Colors.white, labelColor: Colors.white, unselectedLabelColor: Colors.white70, tabs: const [Tab(text: 'Today'), Tab(text: 'Completed'), Tab(text: 'Refunds')]),
        ]),
      ),
    );
  }

  Widget _buildMoneySummary() {
    final s = _moneySummary;
    if (s == null) return const SizedBox.shrink();
    Widget stat(String label, String value, Color color) => Expanded(
          child: Column(children: [
            Text(value, style: TextStyle(fontSize: 15, fontWeight: FontWeight.w900, color: color)),
            Text(label, style: const TextStyle(fontSize: 10, color: AppColors.textSecondary, fontWeight: FontWeight.w600)),
          ]),
        );
    return Container(
      margin: const EdgeInsets.fromLTRB(14, 12, 14, 0),
      padding: const EdgeInsets.symmetric(vertical: 12),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.08))),
      child: Row(children: [
        stat('Collected', '₹${s.collected.toStringAsFixed(0)}', AppColors.green),
        stat('Refunded', '₹${s.refunded.toStringAsFixed(0)}', AppColors.red),
        stat('Net', '₹${s.net.toStringAsFixed(0)}', AppColors.primary),
      ]),
    );
  }

  Widget _buildBody() {
    if (_loading) return Center(child: CircularProgressIndicator(color: AppColors.primary));
    if (_error != null) return AppErrorState(message: _error!, onRetry: _load);
    if (_items.isEmpty) return AppEmptyState(message: 'No bookings here.', icon: Icons.account_balance_wallet_outlined, onRefresh: _load);

    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: _load,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 20),
        itemCount: _items.length + 1,
        itemBuilder: (_, i) {
          if (i == 0) return Padding(padding: const EdgeInsets.only(bottom: 8), child: _buildMoneySummary());
          final item = _items[i - 1];
          return _buildRow(item);
        },
      ),
    );
  }

  Widget _buildRow(OtBookingSummary item) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: InkWell(
        onTap: () => _open(item),
        borderRadius: BorderRadius.circular(14),
        child: Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.primary.withValues(alpha: 0.08)), boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.05), blurRadius: 8, offset: const Offset(0, 2))]),
          child: Row(children: [
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(item.patient?.fullName ?? 'Patient', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.darkNavy)),
                const SizedBox(height: 2),
                Text('${item.patient?.patientCode ?? ''}${item.surgeryDate != null ? ' · ${item.surgeryDate}' : ''}', style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
                if (item.paymentStatus != null) ...[
                  const SizedBox(height: 6),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(color: paymentStatusColor(item.paymentStatus!).withValues(alpha: 0.12), borderRadius: BorderRadius.circular(AppRadius.full)),
                    child: Text(paymentStatusLabel(item.paymentStatus!), style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: paymentStatusColor(item.paymentStatus!))),
                  ),
                ],
                if (item.otStatus == OtStatus.surgeryRefused) ...[
                  const SizedBox(height: 6),
                  if ((item.refundableBalance ?? 0) > 0)
                    Text('Refundable: ₹${item.refundableBalance!.toStringAsFixed(0)}', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.orange))
                  else
                    Text('Refunded: ₹${(item.totalRefunded ?? 0).toStringAsFixed(0)}', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.green)),
                ],
              ]),
            ),
            if (item.otStatus == OtStatus.surgeryRefused)
              (item.refundableBalance ?? 0) > 0
                  ? OutlinedButton(
                      onPressed: () => _openRefund(item),
                      style: OutlinedButton.styleFrom(foregroundColor: AppColors.red, side: const BorderSide(color: AppColors.red)),
                      child: const Text('Refund', style: TextStyle(fontSize: 12)),
                    )
                  : const Icon(Icons.check_circle_outline_rounded, color: AppColors.green)
            else ...[
              IconButton(icon: const Icon(Icons.visibility_outlined, size: 20, color: AppColors.textSecondary), tooltip: 'View', onPressed: () => _viewDetails(item)),
              IconButton(icon: const Icon(Icons.receipt_long_outlined, size: 20, color: AppColors.textSecondary), tooltip: 'Receipt', onPressed: () => _viewLatestReceipt(item)),
              const Icon(Icons.chevron_right_rounded, color: AppColors.textDisabled),
            ],
          ]),
        ),
      ),
    );
  }
}

// ── Full refund dialog ───────────────────────────────────────────────────────
// Amount is never editable — always the full refundable balance, matching
// web exactly (no partial-refund UI exists). See
// WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §4.

class _RefundDialog extends StatefulWidget {
  final int bookingId;
  const _RefundDialog({required this.bookingId});

  @override
  State<_RefundDialog> createState() => _RefundDialogState();
}

class _RefundDialogState extends State<_RefundDialog> {
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
    setState(() { _loading = true; _loadError = null; });
    try {
      final data = await OtAccountantService.instance.fetchRefundFormData(widget.bookingId);
      if (mounted) {
        setState(() {
          _formData = data;
          _loading = false;
          _receiptCtrl.text = data.autoReceiptNumber;
          _reasonCtrl.text = 'Patient refused OT — full refund';
        });
      }
    } catch (e) {
      if (mounted) setState(() { _loadError = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
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
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
    }
  }

  Widget _readRow(String label, String value) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 2),
        child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Text(label, style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
          Text(value, style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700)),
        ]),
      );

  InputDecoration _deco(String label) => InputDecoration(
        labelText: label,
        filled: true,
        fillColor: const Color(0xFFF0F6FB),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide.none),
      );

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
      title: const Text('Full Refund — Surgery Refused'),
      content: SizedBox(
        width: 360,
        child: _loading
            ? const SizedBox(height: 120, child: Center(child: CircularProgressIndicator()))
            : _loadError != null
                ? Text(_loadError!, style: const TextStyle(color: AppColors.red))
                : Builder(builder: (_) {
                    final f = _formData!;
                    return SingleChildScrollView(
                      child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(color: const Color(0xFFF0F6FB), borderRadius: BorderRadius.circular(AppRadius.md)),
                          child: Column(children: [
                            _readRow('Patient', f.booking.patient?.fullName ?? '—'),
                            _readRow('UHID', f.booking.patient?.patientCode ?? '—'),
                            _readRow('Total Paid', '₹${f.totalPaid.toStringAsFixed(0)}'),
                            _readRow('Already Refunded', '₹${f.totalRefunded.toStringAsFixed(0)}'),
                          ]),
                        ),
                        const SizedBox(height: 12),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          decoration: BoxDecoration(color: AppColors.red.withValues(alpha: 0.08), borderRadius: BorderRadius.circular(AppRadius.md)),
                          child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                            const Text('Refund Amount (full)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                            Text('₹${f.refundAmount.toStringAsFixed(0)}', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: AppColors.red)),
                          ]),
                        ),
                        const SizedBox(height: 12),
                        Wrap(spacing: 8, children: ['cash', 'online'].map((m) => ChoiceChip(label: Text(m[0].toUpperCase() + m.substring(1)), selected: _paymentMode == m, onSelected: (_) => setState(() => _paymentMode = m))).toList()),
                        const SizedBox(height: 12),
                        TextFormField(controller: _receiptCtrl, decoration: _deco('Receipt Number')),
                        const SizedBox(height: 12),
                        TextFormField(controller: _reasonCtrl, maxLines: 2, decoration: _deco('Reason')),
                      ]),
                    );
                  }),
      ),
      actions: [
        TextButton(onPressed: _saving ? null : () => Navigator.pop(context), child: const Text('Cancel')),
        if (_formData != null)
          ElevatedButton(
            onPressed: _saving ? null : _save,
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.red, foregroundColor: Colors.white),
            child: _saving ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Text('Record Full Refund'),
          ),
      ],
    );
  }
}

// ── View details dialog ─────────────────────────────────────────────────────────

class _ViewDetailsDialog extends StatefulWidget {
  final OtBookingSummary item;
  const _ViewDetailsDialog({required this.item});

  @override
  State<_ViewDetailsDialog> createState() => _ViewDetailsDialogState();
}

class _ViewDetailsDialogState extends State<_ViewDetailsDialog> {
  bool _loading = true;
  String? _error;
  OtPaymentStatus? _status;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final s = await OtAccountantService.instance.fetchPaymentStatus(widget.item.id);
      if (mounted) setState(() { _status = s; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  Widget _row(String label, String value) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 3),
        child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Text(label, style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
          Flexible(child: Text(value, textAlign: TextAlign.right, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700))),
        ]),
      );

  @override
  Widget build(BuildContext context) {
    final item = widget.item;
    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
      title: const Text('Booking Details'),
      content: SizedBox(
        width: 340,
        child: _loading
            ? const SizedBox(height: 100, child: Center(child: CircularProgressIndicator()))
            : _error != null
                ? Text(_error!, style: const TextStyle(color: AppColors.red))
                : Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
                    const Text('Patient Details', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: AppColors.darkNavy)),
                    const Divider(height: 16),
                    _row('Name', item.patient?.fullName ?? '—'),
                    _row('Contact', item.patient?.contactNo ?? '—'),
                    _row('Patient Code', item.patient?.patientCode ?? '—'),
                    _row('Location', item.patient?.location?.city ?? '—'),
                    const SizedBox(height: 14),
                    const Text('OT Payment Details', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: AppColors.darkNavy)),
                    const Divider(height: 16),
                    _row('OT Date', item.surgeryDate ?? '—'),
                    _row('Package Amount', '₹${_status!.requiredTotal.toStringAsFixed(0)}'),
                    _row('Payment Status', paymentStatusLabel(_status!.paymentStatus)),
                    if (_status!.paymentStatus == 'partially_paid') _row('Remaining Balance', '₹${_status!.remainingBalance.toStringAsFixed(0)}'),
                    _row('Booking ID', '#${item.id}'),
                    const SizedBox(height: 14),
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(color: const Color(0xFFF0F6FB), borderRadius: BorderRadius.circular(AppRadius.md)),
                      child: Text(
                        '${item.patient?.fullName ?? 'This patient'} is scheduled for OT on ${item.surgeryDate ?? '—'}.',
                        style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                      ),
                    ),
                  ]),
      ),
      actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('Close'))],
    );
  }
}

// ── Receipt dialog ─────────────────────────────────────────────────────────────

class _ReceiptDialog extends StatefulWidget {
  final int paymentId;
  final String? patientName;
  const _ReceiptDialog({required this.paymentId, this.patientName});

  @override
  State<_ReceiptDialog> createState() => _ReceiptDialogState();
}

class _ReceiptDialogState extends State<_ReceiptDialog> {
  bool _loading = true;
  String? _error;
  OtPaymentReceipt? _receipt;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final r = await OtAccountantService.instance.fetchReceipt(widget.paymentId);
      if (mounted) setState(() { _receipt = r; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 400, maxHeight: 640),
        child: _loading
            ? const SizedBox(height: 200, child: Center(child: CircularProgressIndicator()))
            : _error != null
                ? Padding(padding: const EdgeInsets.all(24), child: Column(mainAxisSize: MainAxisSize.min, children: [Text(_error!, style: const TextStyle(color: AppColors.red), textAlign: TextAlign.center), const SizedBox(height: 12), ElevatedButton(onPressed: _load, child: const Text('Retry'))]))
                : Padding(
                    padding: const EdgeInsets.all(20),
                    child: SingleChildScrollView(
                      child: _DashboardReceiptPreview(receipt: _receipt!, patientNameFallback: widget.patientName),
                    ),
                  ),
      ),
    );
  }
}

// Matches web's `d M Y, h:i A` receipt timestamp format exactly.
const _dashReceiptMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
String _dashFmtPaidAt(String? raw) {
  if (raw == null) return '—';
  final d = DateTime.tryParse(raw);
  if (d == null) return raw;
  final local = d.toLocal();
  final h12 = local.hour % 12 == 0 ? 12 : local.hour % 12;
  final period = local.hour >= 12 ? 'PM' : 'AM';
  return '${local.day.toString().padLeft(2, '0')} ${_dashReceiptMonths[local.month - 1]} ${local.year}, ${h12.toString().padLeft(2, '0')}:${local.minute.toString().padLeft(2, '0')} $period';
}

class _DashboardReceiptPreview extends StatelessWidget {
  final OtPaymentReceipt receipt;
  final String? patientNameFallback;

  const _DashboardReceiptPreview({required this.receipt, this.patientNameFallback});

  Widget _box(String title, List<Widget> rows) => Container(
        width: double.infinity,
        padding: const EdgeInsets.all(14),
        margin: const EdgeInsets.only(bottom: 10),
        decoration: BoxDecoration(color: const Color(0xFFF7FBFE), borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.10))),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(title, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: AppColors.darkNavy)),
          const SizedBox(height: 8),
          ...rows,
        ]),
      );

  Widget _row(String label, String value) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 3),
        child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Text(label, style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
          Flexible(child: Text(value, textAlign: TextAlign.right, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700))),
        ]),
      );

  @override
  Widget build(BuildContext context) {
    final p = receipt.payment;
    final remaining = (receipt.requiredTotal - receipt.totalPaid).clamp(0, double.infinity);
    return Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      Row(children: [
        Container(width: 36, height: 36, decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(10)), child: Icon(Icons.receipt_long_rounded, color: AppColors.primary, size: 18)),
        const SizedBox(width: 10),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const Text('Payment Receipt', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: AppColors.darkNavy)),
            Text(p.receiptNumber ?? '—', style: const TextStyle(fontSize: 11, color: AppColors.textSecondary)),
          ]),
        ),
      ]),
      const SizedBox(height: 18),
      Text('₹${p.packageAmount.toStringAsFixed(2)}', textAlign: TextAlign.center, style: TextStyle(fontSize: 26, fontWeight: FontWeight.w900, color: AppColors.primary)),
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
        _row('Paid At', _dashFmtPaidAt(p.paidAt)),
        _row('Recorded By', p.recordedBy?.name ?? '—'),
      ]),
      _box('Package Balance', [
        _row('Package Total', '₹${receipt.requiredTotal.toStringAsFixed(0)}'),
        _row('Total Paid', '₹${receipt.totalPaid.toStringAsFixed(0)}'),
        _row('Remaining', '₹${remaining.toStringAsFixed(0)}'),
        _row('Status', paymentStatusLabel(receipt.bookingPaymentStatus ?? 'pending')),
      ]),
      const SizedBox(height: 4),
      const Text('This is a computer-generated receipt for OT surgery charges.', textAlign: TextAlign.center, style: TextStyle(fontSize: 10.5, color: AppColors.textSecondary, fontStyle: FontStyle.italic)),
      const SizedBox(height: 14),
      Align(alignment: Alignment.center, child: TextButton(onPressed: () => Navigator.pop(context), child: const Text('Close'))),
    ]);
  }
}
