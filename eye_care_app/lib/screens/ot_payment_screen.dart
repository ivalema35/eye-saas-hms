import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/ot_accountant_models.dart';
import '../services/ot_accountant_service.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_error_state.dart';

/// Round 3 Phase 5 — Payment status + record-payment. `storePayment` both
/// records the payment AND (when it completes the package amount) advances
/// the booking to Ward, in one call — no separate "verify payment" call
/// exists, don't build one client-side (see build PRD §9 gotcha).
class OtPaymentScreen extends StatefulWidget {
  final int bookingId;
  final String patientName;

  const OtPaymentScreen({super.key, required this.bookingId, required this.patientName});

  @override
  State<OtPaymentScreen> createState() => _OtPaymentScreenState();
}

class _OtPaymentScreenState extends State<OtPaymentScreen> {
  OtPaymentStatus? _status;
  bool _loading = true;
  String? _loadError;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _loadError = null; });
    try {
      final status = await OtAccountantService.instance.fetchPaymentStatus(widget.bookingId);
      if (mounted) setState(() { _status = status; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _loadError = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  void _openPaymentSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      isDismissible: false,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (_) => _RecordPaymentSheet(bookingId: widget.bookingId, onSaved: _load),
    );
  }

  void _viewReceipt(OtPaymentEntry payment) {
    showDialog(
      context: context,
      builder: (_) => _ReceiptDialog(paymentId: payment.id, patientName: widget.patientName),
    );
  }

  void _viewBillingDetails() {
    showDialog(context: context, builder: (_) => _BillingDetailsDialog(bookingId: widget.bookingId));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      body: Column(children: [
        _buildHeader(),
        Expanded(
          child: _loading
              ? Center(child: CircularProgressIndicator(color: AppColors.primary))
              : _loadError != null
                  ? AppErrorState(message: _loadError!, onRetry: _load)
                  : _buildBody(),
        ),
      ]),
    );
  }

  Widget _buildHeader() {
    return Container(
      decoration: BoxDecoration(gradient: LinearGradient(colors: [AppColors.primary, AppColors.blueLight], begin: Alignment.topLeft, end: Alignment.bottomRight)),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(8, 10, 20, 14),
          child: Row(children: [
            IconButton(icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20), onPressed: () => Navigator.pop(context)),
            Container(width: 40, height: 40, decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(10)), child: const Icon(Icons.receipt_long_rounded, color: Colors.white, size: 20)),
            const SizedBox(width: 12),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                const Text('Billing', style: TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.w800)),
                Text(widget.patientName, style: TextStyle(color: Colors.white.withValues(alpha: 0.75), fontSize: 11)),
              ]),
            ),
          ]),
        ),
      ),
    );
  }

  Widget _buildBody() {
    final s = _status!;
    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 20),
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.08))),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(children: [
                Container(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5), decoration: BoxDecoration(color: paymentStatusColor(s.paymentStatus).withValues(alpha: 0.12), borderRadius: BorderRadius.circular(AppRadius.full)), child: Text(paymentStatusLabel(s.paymentStatus), style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: paymentStatusColor(s.paymentStatus)))),
              ]),
              const Divider(height: 24),
              _row('Required Total', '₹${s.requiredTotal.toStringAsFixed(0)}'),
              _row('Total Paid', '₹${s.totalPaid.toStringAsFixed(0)}'),
              _row('Remaining Balance', '₹${s.remainingBalance.toStringAsFixed(0)}', bold: true),
            ]),
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(onPressed: _viewBillingDetails, icon: const Icon(Icons.description_outlined, size: 16), label: const Text('Billing Details')),
          const SizedBox(height: 12),
          if (s.paymentStatus != 'paid')
            ElevatedButton.icon(
              onPressed: s.remainingBalance <= 0 && s.paymentStatus != 'unpriced' ? null : _openPaymentSheet,
              icon: const Icon(Icons.payments_outlined, size: 18),
              label: const Text('Record Payment'),
              style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14)),
            ),
          const SizedBox(height: 20),
          if (s.payments.isNotEmpty) ...[
            const Text('Payment History', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: AppColors.darkNavy)),
            const SizedBox(height: 8),
            ...s.payments.map((p) => Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: InkWell(
                    onTap: () => _viewReceipt(p),
                    borderRadius: BorderRadius.circular(AppRadius.md),
                    child: Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.08))),
                      child: Row(children: [
                        Expanded(
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Text('₹${p.packageAmount.toStringAsFixed(0)} · ${p.paymentMode}', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700)),
                            if (p.receiptNumber != null) Text('Receipt: ${p.receiptNumber}', style: const TextStyle(fontSize: 11, color: AppColors.textSecondary)),
                          ]),
                        ),
                        const Icon(Icons.receipt_long_outlined, size: 18, color: AppColors.textSecondary),
                      ]),
                    ),
                  ),
                )),
          ],
        ],
      ),
    );
  }

  Widget _row(String label, String value, {bool bold = false}) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 3),
        child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Text(label, style: const TextStyle(fontSize: 13, color: AppColors.textSecondary)),
          Text(value, style: TextStyle(fontSize: bold ? 16 : 13, fontWeight: bold ? FontWeight.w800 : FontWeight.w600, color: AppColors.darkNavy)),
        ]),
      );
}

// ── Record payment sheet ───────────────────────────────────────────────────────

class _RecordPaymentSheet extends StatefulWidget {
  final int bookingId;
  final VoidCallback onSaved;

  const _RecordPaymentSheet({required this.bookingId, required this.onSaved});

  @override
  State<_RecordPaymentSheet> createState() => _RecordPaymentSheetState();
}

class _RecordPaymentSheetState extends State<_RecordPaymentSheet> {
  bool _loadingForm = true;
  String? _formError;
  OtPaymentFormData? _formData;
  bool _saving = false;

  final _amountCtrl = TextEditingController();
  final _receiptCtrl = TextEditingController();
  String _paymentMode = 'cash';

  @override
  void initState() {
    super.initState();
    _loadFormData();
  }

  @override
  void dispose() {
    _amountCtrl.dispose();
    _receiptCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadFormData() async {
    try {
      final data = await OtAccountantService.instance.fetchPaymentFormData(widget.bookingId);
      if (mounted) {
        setState(() {
          _formData = data;
          _loadingForm = false;
          _amountCtrl.text = data.remainingBalance > 0 ? data.remainingBalance.toStringAsFixed(0) : data.defaultPackageAmount.toStringAsFixed(0);
          _receiptCtrl.text = data.autoReceiptNumber;
          _paymentMode = data.defaultPaymentMode;
        });
      }
    } catch (e) {
      if (mounted) setState(() { _formError = e.toString().replaceFirst('Exception: ', ''); _loadingForm = false; });
    }
  }

  Future<void> _save() async {
    final amount = double.tryParse(_amountCtrl.text.trim());
    if (amount == null || amount <= 0) {
      showAppSnackBar(context, 'Enter a valid amount', isError: true);
      return;
    }
    setState(() => _saving = true);
    try {
      final result = await OtAccountantService.instance.storePayment(widget.bookingId, packageAmount: amount, receiptNumber: _receiptCtrl.text.trim(), paymentMode: _paymentMode);
      widget.onSaved();
      if (mounted) {
        Navigator.pop(context);
        showAppSnackBar(context, result.isFullyPaid ? 'Payment complete — booking moved to Ward' : 'Payment recorded', isSuccess: true);
      }
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
    }
  }

  InputDecoration _deco(String label, {String? suffix}) => InputDecoration(
        labelText: label,
        suffixText: suffix,
        filled: true,
        fillColor: const Color(0xFFF0F6FB),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide.none),
      );

  Widget _readRow(String label, String value) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 2),
        child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Text(label, style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
          Text(value, style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700)),
        ]),
      );

  /// Client-side only — matches web exactly (`RCP-YYYYMM-XXXX`), no API
  /// call. See OT_WEB_PARITY_FIX_PRD.md §6.2.
  String _generateReceiptNumber() {
    final now = DateTime.now();
    final ym = '${now.year}${now.month.toString().padLeft(2, '0')}';
    final rand = (now.millisecondsSinceEpoch % 10000).toString().padLeft(4, '0');
    return 'RCP-$ym-$rand';
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.only(bottom: bottom),
      child: SingleChildScrollView(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Container(margin: const EdgeInsets.only(top: 10, bottom: 4), width: 40, height: 4, decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.18), borderRadius: BorderRadius.circular(2))),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 10, 8, 14),
            child: Row(children: [
              const Expanded(child: Text('Record Payment', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.darkNavy))),
              IconButton(icon: const Icon(Icons.close_rounded, size: 20, color: Color(0xFF94A3B8)), onPressed: () => Navigator.pop(context)),
            ]),
          ),
          if (_loadingForm)
            Padding(padding: const EdgeInsets.all(32), child: CircularProgressIndicator(color: AppColors.primary))
          else if (_formError != null)
            Padding(padding: const EdgeInsets.all(20), child: Column(children: [Text(_formError!, style: const TextStyle(color: AppColors.red)), const SizedBox(height: 10), ElevatedButton(onPressed: () { setState(() => _loadingForm = true); _loadFormData(); }, child: const Text('Retry'))]))
          else ...[
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Builder(builder: (_) {
                final f = _formData!;
                final disabled = f.remainingBalance <= 0 && f.defaultPackageAmount > 0;
                return Column(children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(color: const Color(0xFFF0F6FB), borderRadius: BorderRadius.circular(AppRadius.md)),
                    child: Column(children: [
                      _readRow('UHID', f.booking.patient?.patientCode ?? '—'),
                      _readRow('OT Package', f.counselling?.packageName ?? f.counselling?.lensOption ?? '—'),
                      _readRow('Total Amount', '₹${f.requiredTotal.toStringAsFixed(0)}'),
                      _readRow('Amount Paid', '₹${f.totalPaidSoFar.toStringAsFixed(0)}'),
                      _readRow('Payment Status', paymentStatusLabel(f.booking.paymentStatus ?? 'pending')),
                      _readRow('Mediclaim', (f.counselling?.mediclaim ?? false) ? 'YES' : 'NO'),
                      _readRow('Invoice Number', f.invoiceNumber ?? '—'),
                    ]),
                  ),
                  const SizedBox(height: 12),
                  Text('Remaining balance: ₹${f.remainingBalance.toStringAsFixed(0)}', style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
                  if (disabled) Padding(padding: const EdgeInsets.only(top: 4), child: Text('Nothing remaining to pay.', style: const TextStyle(fontSize: 12, color: AppColors.red, fontWeight: FontWeight.w600))),
                  const SizedBox(height: 12),
                  TextFormField(enabled: !disabled, controller: _amountCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d*'))], decoration: _deco('Amount *', suffix: '₹')),
                  const SizedBox(height: 12),
                  Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Expanded(child: TextFormField(enabled: !disabled, controller: _receiptCtrl, decoration: _deco('Receipt Number'))),
                    const SizedBox(width: 8),
                    IconButton(
                      tooltip: 'Auto-generate',
                      onPressed: disabled ? null : () => setState(() => _receiptCtrl.text = _generateReceiptNumber()),
                      icon: const Icon(Icons.autorenew_rounded),
                    ),
                  ]),
                  const SizedBox(height: 12),
                  Wrap(spacing: 8, children: ['cash', 'online', 'mediclaim'].map((m) => ChoiceChip(label: Text(m[0].toUpperCase() + m.substring(1)), selected: _paymentMode == m, onSelected: disabled ? null : (_) => setState(() => _paymentMode = m))).toList()),
                ]);
              }),
            ),
          ],
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 20, 16, 28),
            child: Row(children: [
              Expanded(child: OutlinedButton(onPressed: _saving ? null : () => Navigator.pop(context), style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md))), child: const Text('Cancel'))),
              const SizedBox(width: 12),
              Expanded(
                flex: 2,
                child: ElevatedButton(
                  onPressed: (_saving || _loadingForm || _formError != null) ? null : _save,
                  style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md))),
                  child: _saving ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) : const Text('Save Payment', style: TextStyle(fontWeight: FontWeight.w700)),
                ),
              ),
            ]),
          ),
        ]),
      ),
    );
  }
}

// ── Receipt preview (matches web's receipt_print.blade.php: large amount +
// 3 boxed sections — Patient / Payment Details / Package Balance — plus a
// footer disclaimer, not a plain list of text lines) ───────────────────────

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
                      child: _ReceiptPreview(receipt: _receipt!, patientNameFallback: widget.patientName),
                    ),
                  ),
      ),
    );
  }
}

// Matches web's `d M Y, h:i A` receipt timestamp format exactly.
const _receiptMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
String _fmtPaidAt(String? raw) {
  if (raw == null) return '—';
  final d = DateTime.tryParse(raw);
  if (d == null) return raw;
  final local = d.toLocal();
  final h12 = local.hour % 12 == 0 ? 12 : local.hour % 12;
  final period = local.hour >= 12 ? 'PM' : 'AM';
  return '${local.day.toString().padLeft(2, '0')} ${_receiptMonths[local.month - 1]} ${local.year}, ${h12.toString().padLeft(2, '0')}:${local.minute.toString().padLeft(2, '0')} $period';
}

class _ReceiptPreview extends StatelessWidget {
  final OtPaymentReceipt receipt;
  final String? patientNameFallback;

  const _ReceiptPreview({required this.receipt, this.patientNameFallback});

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
        _row('Paid At', _fmtPaidAt(p.paidAt)),
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

// ── Billing details dialog ──────────────────────────────────────────────────────

class _BillingDetailsDialog extends StatefulWidget {
  final int bookingId;
  const _BillingDetailsDialog({required this.bookingId});

  @override
  State<_BillingDetailsDialog> createState() => _BillingDetailsDialogState();
}

class _BillingDetailsDialogState extends State<_BillingDetailsDialog> {
  bool _loading = true;
  String? _error;
  OtPaymentFormData? _data;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final d = await OtAccountantService.instance.fetchPaymentFormData(widget.bookingId);
      if (mounted) setState(() { _data = d; _loading = false; });
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
    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
      title: const Text('Billing Details'),
      content: SizedBox(
        width: 320,
        child: _loading
            ? const SizedBox(height: 100, child: Center(child: CircularProgressIndicator()))
            : _error != null
                ? Text(_error!, style: const TextStyle(color: AppColors.red))
                : Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
                    _row('Patient', _data!.booking.patient?.fullName ?? '—'),
                    _row('UHID', _data!.booking.patient?.patientCode ?? '—'),
                    _row('OT Package', _data!.counselling?.packageName ?? _data!.counselling?.lensOption ?? '—'),
                    _row('Total Amount', '₹${_data!.requiredTotal.toStringAsFixed(0)}'),
                    _row('Amount Paid', '₹${_data!.totalPaidSoFar.toStringAsFixed(0)}'),
                    _row('Remaining Balance', '₹${_data!.remainingBalance.toStringAsFixed(0)}'),
                    _row('Mediclaim', (_data!.counselling?.mediclaim ?? false) ? 'YES' : 'NO'),
                    _row('Payment Status', paymentStatusLabel(_data!.booking.paymentStatus ?? 'pending')),
                    _row('Invoice Number', _data!.invoiceNumber ?? '—'),
                  ]),
      ),
      actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('Close'))],
    );
  }
}
