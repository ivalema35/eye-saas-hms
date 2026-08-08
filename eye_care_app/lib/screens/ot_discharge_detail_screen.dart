import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/ot_discharge_models.dart';
import '../services/ot_discharge_service.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_section_header.dart';

/// Round 3 Phase 6 — Discharge detail: generate invoice + print documents.
///
/// Web parity (2026-08-07, corrected): web's queue only ever links **3**
/// documents once an invoice exists — Bill Summary, Discharge Summary,
/// Surgery Certificate. The other 5 (Invoice, Prescription, Lens Slip,
/// Medicine Slip, Follow-up Slip) and the "Print All" bundle have **no
/// visible link anywhere on web** — their routes exist but are only
/// reachable via the post-generate redirect (Invoice) or a bundle page
/// nothing links to. An earlier pass here exposed all 8 + Print All, which
/// was a mislabeled "match web" — this now shows only the 3 real ones. See
/// OT_DISCHARGE_INVOICES_WEB_PARITY_FIX_PLAN.md Addendum.
class OtDischargeDetailScreen extends StatefulWidget {
  final int bookingId;
  final String patientName;

  const OtDischargeDetailScreen({super.key, required this.bookingId, required this.patientName});

  @override
  State<OtDischargeDetailScreen> createState() => _OtDischargeDetailScreenState();
}

class _OtDischargeDetailScreenState extends State<OtDischargeDetailScreen> {
  bool _loadingInvoice = true;
  OtInvoiceSummary? _invoice;
  bool _generating = false;
  OtDischargeDocType? _printingType;

  @override
  void initState() {
    super.initState();
    _loadInvoice();
  }

  Future<void> _loadInvoice() async {
    setState(() => _loadingInvoice = true);
    try {
      final invoice = await OtDischargeService.instance.fetchInvoiceDetail(widget.bookingId);
      if (mounted) setState(() { _invoice = invoice; _loadingInvoice = false; });
    } catch (e) {
      if (mounted) {
        setState(() => _loadingInvoice = false);
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
    }
  }

  Future<void> _generateInvoice() async {
    final result = await showDialog<_InvoiceDraft>(context: context, builder: (_) => const _GenerateInvoiceDialog());
    if (result == null) return;
    setState(() => _generating = true);
    try {
      final r = await OtDischargeService.instance.generateInvoice(widget.bookingId, followUpDate: result.followUpDate);
      if (!mounted) return;
      showAppSnackBar(context, 'Invoice ${r.invoiceNumber} generated — booking discharged', isSuccess: true);
      await _loadInvoice();
      // Matches web's redirect-to-invoice-print after generate.
      if (mounted) await _print(OtDischargeDocType.invoice);
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    } finally {
      if (mounted) setState(() => _generating = false);
    }
  }

  Future<void> _print(OtDischargeDocType type) async {
    int? restDays;
    if (type == OtDischargeDocType.certificate) {
      restDays = await _askRestDays();
      if (restDays == null) return;
    }
    setState(() => _printingType = type);
    try {
      await OtDischargeService.instance.printDocument(widget.bookingId, type, restDays: restDays);
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    } finally {
      if (mounted) setState(() => _printingType = null);
    }
  }

  Future<int?> _askRestDays() async {
    final ctrl = TextEditingController(text: '7');
    return showDialog<int>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
        title: const Text('Rest Days'),
        content: TextField(controller: ctrl, keyboardType: TextInputType.number, inputFormatters: [FilteringTextInputFormatter.digitsOnly], decoration: const InputDecoration(labelText: 'Rest Days (1-90)')),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          ElevatedButton(onPressed: () => Navigator.pop(ctx, int.tryParse(ctrl.text.trim())?.clamp(1, 90)), child: const Text('Print')),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      body: Column(children: [
        _buildHeader(),
        Expanded(
          child: _loadingInvoice
              ? Center(child: CircularProgressIndicator(color: AppColors.primary))
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
            Container(width: 40, height: 40, decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(10)), child: const Icon(Icons.local_hospital_rounded, color: Colors.white, size: 20)),
            const SizedBox(width: 12),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                const Text('Discharge & Invoices', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800)),
                Text(widget.patientName, style: TextStyle(color: Colors.white.withValues(alpha: 0.75), fontSize: 11)),
              ]),
            ),
          ]),
        ),
      ),
    );
  }

  Widget _buildBody() {
    final invoice = _invoice;
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 20),
      children: [
        if (invoice == null) ...[
          ElevatedButton.icon(
            onPressed: _generating ? null : _generateInvoice,
            icon: _generating ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Icon(Icons.receipt_rounded, size: 18),
            label: const Text('Generate Invoice / Discharge'),
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.teal, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14)),
          ),
        ] else ...[
          _invoiceSummaryCard(invoice),
          const SizedBox(height: 20),
          const AppSectionHeader(title: 'Print Documents', icon: Icons.print_outlined),
          Column(children: [
            _docButton(OtDischargeDocType.summaryBill),
            const SizedBox(height: 10),
            _docButton(OtDischargeDocType.discharge),
            const SizedBox(height: 10),
            _docButton(OtDischargeDocType.certificate),
          ]),
        ],
      ],
    );
  }

  Widget _invoiceSummaryCard(OtInvoiceSummary invoice) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.lg), border: Border.all(color: AppColors.primary.withValues(alpha: 0.1)), boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.05), blurRadius: 8, offset: const Offset(0, 2))]),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Expanded(child: Text(invoice.invoiceNumber, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: AppColors.darkNavy))),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(color: AppColors.green.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(999)),
            child: const Text('Generated', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: AppColors.green)),
          ),
        ]),
        if (invoice.lineItems.isNotEmpty) ...[
          const SizedBox(height: 10),
          const Divider(height: 1),
          const SizedBox(height: 10),
          for (final item in invoice.lineItems)
            Padding(
              padding: const EdgeInsets.only(bottom: 6),
              child: Row(children: [
                Expanded(child: Text(item.head, style: const TextStyle(fontSize: 12.5))),
                Text('₹${item.amount.toStringAsFixed(2)}', style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600)),
              ]),
            ),
        ],
        const SizedBox(height: 6),
        const Divider(height: 1),
        const SizedBox(height: 8),
        _totalRow('Total', invoice.totalAmount),
        _totalRow('Tax', invoice.taxAmount),
        _totalRow('Discount', invoice.discount, negative: true),
        const SizedBox(height: 4),
        _totalRow('Net Amount', invoice.netAmount, bold: true),
        if (invoice.followUpDate != null) ...[
          const SizedBox(height: 10),
          Row(children: [
            Icon(Icons.event_repeat_rounded, size: 14, color: AppColors.textSecondary),
            const SizedBox(width: 6),
            Text('Follow-up: ${invoice.followUpDate}', style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
          ]),
        ],
      ]),
    );
  }

  Widget _totalRow(String label, double amount, {bool bold = false, bool negative = false}) => Padding(
        padding: const EdgeInsets.only(bottom: 2),
        child: Row(children: [
          Expanded(child: Text(label, style: TextStyle(fontSize: bold ? 13 : 12, fontWeight: bold ? FontWeight.w800 : FontWeight.w500, color: bold ? AppColors.darkNavy : AppColors.textSecondary))),
          Text('${negative && amount > 0 ? '-' : ''}₹${amount.toStringAsFixed(2)}', style: TextStyle(fontSize: bold ? 13 : 12, fontWeight: bold ? FontWeight.w800 : FontWeight.w600, color: bold ? AppColors.darkNavy : AppColors.textSecondary)),
        ]),
      );

  Widget _docButton(OtDischargeDocType type) {
    final busy = _printingType == type;
    return SizedBox(
      width: double.infinity,
      child: OutlinedButton.icon(
        onPressed: busy ? null : () => _print(type),
        icon: busy ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.picture_as_pdf_outlined, size: 18),
        label: Text(type.label),
        style: OutlinedButton.styleFrom(foregroundColor: AppColors.primary, side: BorderSide(color: AppColors.primary.withValues(alpha: 0.3)), padding: const EdgeInsets.symmetric(vertical: 14)),
      ),
    );
  }
}

class _InvoiceDraft {
  final String? followUpDate;
  const _InvoiceDraft({this.followUpDate});
}

class _GenerateInvoiceDialog extends StatefulWidget {
  const _GenerateInvoiceDialog();

  @override
  State<_GenerateInvoiceDialog> createState() => _GenerateInvoiceDialogState();
}

class _GenerateInvoiceDialogState extends State<_GenerateInvoiceDialog> {
  // Web's Billing Desk only exposes a Follow-up Date picker next to the
  // Generate button, pre-filled with today+7 — no Tax Amount/Discount
  // fields exist in the real UI even though the backend accepts them (see
  // OT_DISCHARGE_INVOICES_WEB_PARITY_FIX_PLAN.md).
  late DateTime _followUpDate = DateTime.now().add(const Duration(days: 7));

  Future<void> _pickFollowUp() async {
    final now = DateTime.now();
    final picked = await showDatePicker(context: context, initialDate: _followUpDate, firstDate: now, lastDate: now.add(const Duration(days: 365)));
    if (picked != null) setState(() => _followUpDate = picked);
  }

  String _fmt(DateTime d) => '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
      title: const Text('Generate Invoice'),
      content: Column(mainAxisSize: MainAxisSize.min, children: [
        InkWell(onTap: _pickFollowUp, child: InputDecorator(decoration: const InputDecoration(labelText: 'Follow-up Date'), child: Text(_fmt(_followUpDate)))),
      ]),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')),
        ElevatedButton(
          onPressed: () => Navigator.pop(context, _InvoiceDraft(followUpDate: _fmt(_followUpDate))),
          child: const Text('Generate'),
        ),
      ],
    );
  }
}
