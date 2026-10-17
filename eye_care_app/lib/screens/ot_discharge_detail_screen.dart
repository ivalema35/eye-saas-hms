import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/permissions.dart';
import '../models/ot_accountant_models.dart';
import '../models/ot_booking_models.dart';
import '../models/ot_discharge_models.dart';
import '../utils/date_format.dart';
import '../services/ot_discharge_service.dart';
import '../services/permission_service.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_record_card.dart';
import '../widgets/app_section_header.dart';
import '../widgets/skeleton.dart';
import '../utils/currency_format.dart';
import '../utils/app_date_picker.dart';

/// Round 3 Phase 6 — Discharge detail: generate invoice + print documents.
///
/// Web parity (2026-09-18, re-confirmed by direct visual check of the live
/// web page): web's Discharge & Invoices queue only ever shows **3** print
/// options — Bill Summary, Discharge Summary, Surgery Certificate. The
/// backend also defines 5 more routes (Invoice, Take-Home Medicine Slip,
/// Prescription, Lens Implant Details, Follow-up Slip) plus a "Print All"
/// bundle, but they have no reachable link/button anywhere in web's actual
/// UI — a prior pass here (2026-09-17) treated "the route exists" as "the
/// feature exists" and exposed all 8, which the user then verified against
/// the real web page is wrong: only 3 are real, user-facing options on
/// web. Reverted back to those 3. See
/// OT_DISCHARGE_INVOICES_WEB_PARITY_FIX_PLAN.md Addendum 2.

/// Closes a dialog that contains a text field safely. `unfocus()` only
/// *schedules* the focus change — it doesn't complete synchronously — so
/// popping the route immediately after it still tears the field down mid
/// focus-change and throws `InheritedElement`'s `_dependents.isEmpty`
/// assertion (a full red-screen crash, since it's thrown by the framework
/// during Element teardown, not inside any catchable app code). Deferring
/// the pop to `addPostFrameCallback` lets that scheduled change fully
/// resolve on its own frame first. Same pattern as `ot_ward_screen.dart`'s
/// `_closeSheetSafely` (see OT_WEB_PARITY_FIX_PRD.md §4.3) — the Rest Days
/// dialog below was written later and never got it, which is why Certificate
/// was the only one of the 3 print buttons that could crash (it's the only
/// dialog with a text field the user types into).
void closeOtDialogSafely<T>(BuildContext dialogContext, [T? result]) {
  FocusManager.instance.primaryFocus?.unfocus();
  WidgetsBinding.instance.addPostFrameCallback((_) {
    if (dialogContext.mounted) Navigator.pop(dialogContext, result);
  });
}

/// Extracted (web pull 2026-09-28's Rest Days prompt for the Certificate
/// print) so the dashboard's compact Discharge desk panel (see
/// `lib/widgets/desk_home_panels.dart`) can reuse the exact same dialog
/// instead of rebuilding it.
Future<int?> askOtCertificateRestDays(BuildContext context) async {
  final ctrl = TextEditingController(text: '7');
  return showDialog<int>(
    context: context,
    builder: (ctx) => AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
      title: const Text('Rest Days'),
      content: TextField(controller: ctrl, keyboardType: TextInputType.number, inputFormatters: [FilteringTextInputFormatter.digitsOnly], decoration: const InputDecoration(labelText: 'Rest Days (1-90)')),
      actions: [
        TextButton(onPressed: () => closeOtDialogSafely(ctx), child: const Text('Cancel')),
        ElevatedButton(onPressed: () => closeOtDialogSafely<int>(ctx, int.tryParse(ctrl.text.trim())?.clamp(1, 90)), child: const Text('Print')),
      ],
    ),
  );
}

/// Extracted (web pull 2026-09-28 discharge print-gate) so the dashboard's
/// compact Discharge desk panel can render the exact same per-document
/// button (green checkmark once printed, plain PDF icon otherwise) as the
/// full desk screen, instead of rebuilding it.
///
/// [compact] (client request 2026-10-03): the desk panel's card used the same
/// full-width text pill as this full detail screen for all 3 documents —
/// stacked one per line since "Bill Summary"/"Discharge Summary"/"Surgery
/// Certificate" don't fit 3-across, each wrapping its label onto 2 lines. On
/// a card already packed with patient info that read as 3 oversized, ugly
/// buttons. [compact] swaps that for a small icon-only circle (same
/// green-check-vs-PDF-icon state), with the name available on long-press via
/// [Tooltip] instead of printed — the full detail screen below keeps the
/// text version, where there's room for it.
Widget otDischargeDocButton({required OtDischargeDocType type, required bool busy, required bool done, required VoidCallback onPressed, bool compact = false}) {
  final color = done ? AppColors.green : AppColors.primary;
  if (compact) {
    return Tooltip(
      message: type.label,
      child: InkWell(
        onTap: busy ? null : onPressed,
        customBorder: const CircleBorder(),
        child: Container(
          width: 36,
          height: 36,
          decoration: BoxDecoration(shape: BoxShape.circle, color: color.withValues(alpha: 0.10), border: Border.all(color: color.withValues(alpha: 0.3))),
          alignment: Alignment.center,
          child: busy
              ? SizedBox(width: 15, height: 15, child: CircularProgressIndicator(strokeWidth: 2, color: color))
              : Icon(done ? Icons.check_circle_rounded : Icons.picture_as_pdf_outlined, size: 17, color: color),
        ),
      ),
    );
  }
  return SizedBox(
    width: double.infinity,
    child: OutlinedButton.icon(
      onPressed: busy ? null : onPressed,
      icon: busy
          ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
          : Icon(done ? Icons.check_circle_rounded : Icons.picture_as_pdf_outlined, size: 18, color: color),
      label: Text(type.label, style: TextStyle(color: color)),
      style: OutlinedButton.styleFrom(foregroundColor: color, side: BorderSide(color: color.withValues(alpha: 0.3)), padding: const EdgeInsets.symmetric(vertical: 14)),
    ),
  );
}

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

  // Bug fix (2026-09-29) — this used to be one `_canGenerate` getter gating
  // BOTH the "Generate Invoice" button and the "Discharge" print-document
  // button off the same `otDischargeGenerate` permission. Web gates these
  // as two separate permissions: `routes/hospital.php`'s `invoice.generate`
  // route requires `ot_billing_manage`, while `OtBooking::DISCHARGE_PRINTS`'s
  // `discharge` entry requires `ot_discharge_generate` — a user could hold
  // one without the other. Split to match (tablet's
  // `ot_discharge_dashboard_screen.dart` already had this split correctly).
  bool get _canGenerateInvoice => PermissionService.instance.can(Perm.otBillingManage);
  bool get _canDischargePrint => PermissionService.instance.can(Perm.otDischargeGenerate);
  bool get _canBillPrint => PermissionService.instance.can(Perm.otBillPrint);
  bool get _canCertificatePrint => PermissionService.instance.can(Perm.otCertificatePrint);

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
    final result = await showDialog<OtInvoiceDraft>(context: context, builder: (_) => const OtGenerateInvoiceDialog());
    if (result == null) return;
    setState(() => _generating = true);
    try {
      final r = await OtDischargeService.instance.generateInvoice(widget.bookingId, followUpDate: result.followUpDate);
      if (!mounted) return;
      // Web pull 2026-09-28: generating the invoice no longer discharges the
      // booking — it only becomes Discharged once all 3 documents below are
      // printed (see the invoice card's discharge-progress badge).
      showAppSnackBar(context, 'Invoice ${r.invoiceNumber} generated. Print all 3 documents to complete discharge.', isSuccess: true);
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
      restDays = await askOtCertificateRestDays(context);
      if (restDays == null) return;
    }
    setState(() => _printingType = type);
    try {
      await OtDischargeService.instance.printDocument(widget.bookingId, type, restDays: restDays);
      // Web pull 2026-09-28: a print can be the one that completes the
      // 3-document discharge gate — reload so the card's progress badge (and,
      // once all 3 are done, the "Discharged" confirmation) reflects the
      // real server state instead of staying stale until the screen is
      // reopened.
      final wasDischarged = _invoice?.isDischarged ?? false;
      await _loadInvoice();
      if (mounted && !wasDischarged && (_invoice?.isDischarged ?? false)) {
        showAppSnackBar(context, 'All documents printed — patient discharged.', isSuccess: true);
      }
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    } finally {
      if (mounted) setState(() => _printingType = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      body: Column(children: [
        _buildHeader(),
        Expanded(
          child: _loadingInvoice
              ? const AppSkeletonList()
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
          if (_canGenerateInvoice)
            ElevatedButton.icon(
              onPressed: _generating ? null : _generateInvoice,
              icon: _generating ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Icon(Icons.receipt_rounded, size: 18),
              label: const Text('Generate Invoice / Discharge'),
              style: ElevatedButton.styleFrom(backgroundColor: AppColors.teal, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14)),
            ),
        ] else ...[
          _invoiceSummaryCard(invoice),
          const SizedBox(height: 20),
          if (_canBillPrint || _canDischargePrint || _canCertificatePrint) ...[
            const AppSectionHeader(title: 'Print Documents', icon: Icons.print_outlined),
            Column(children: [
              if (_canBillPrint) ...[
                otDischargeDocButton(type: OtDischargeDocType.summaryBill, busy: _printingType == OtDischargeDocType.summaryBill, done: _printedAtFor(OtDischargeDocType.summaryBill) != null, onPressed: () => _print(OtDischargeDocType.summaryBill)),
                const SizedBox(height: 10),
              ],
              if (_canDischargePrint) ...[
                otDischargeDocButton(type: OtDischargeDocType.discharge, busy: _printingType == OtDischargeDocType.discharge, done: _printedAtFor(OtDischargeDocType.discharge) != null, onPressed: () => _print(OtDischargeDocType.discharge)),
                const SizedBox(height: 10),
              ],
              if (_canCertificatePrint)
                otDischargeDocButton(type: OtDischargeDocType.certificate, busy: _printingType == OtDischargeDocType.certificate, done: _printedAtFor(OtDischargeDocType.certificate) != null, onPressed: () => _print(OtDischargeDocType.certificate)),
            ]),
          ],
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
          _dischargeProgressBadge(invoice),
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
                Text('${currentCurrencySymbol()}${item.amount.toStringAsFixed(2)}', style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600)),
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

  // Web pull 2026-09-28: an invoice existing no longer means the booking is
  // discharged — it only becomes Discharged once all 3 documents below are
  // printed. Shows "Discharged" once done, otherwise the live "X of 3
  // printed" progress instead of a flat "Generated" badge that used to
  // imply discharge was already complete.
  Widget _dischargeProgressBadge(OtInvoiceSummary invoice) {
    final discharged = invoice.isDischarged;
    final color = discharged ? AppColors.green : AppColors.orange;
    final label = discharged ? 'Discharged' : '${invoice.dischargePrintsDone} of ${invoice.dischargePrintsTotal} printed';
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(999)),
      child: Text(label, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: color)),
    );
  }

  Widget _totalRow(String label, double amount, {bool bold = false, bool negative = false}) => Padding(
        padding: const EdgeInsets.only(bottom: 2),
        child: Row(children: [
          Expanded(child: Text(label, style: TextStyle(fontSize: bold ? 13 : 12, fontWeight: bold ? FontWeight.w800 : FontWeight.w500, color: bold ? AppColors.darkNavy : AppColors.textSecondary))),
          Text('${negative && amount > 0 ? '-' : ''}${currentCurrencySymbol()}${amount.toStringAsFixed(2)}', style: TextStyle(fontSize: bold ? 13 : 12, fontWeight: bold ? FontWeight.w800 : FontWeight.w600, color: bold ? AppColors.darkNavy : AppColors.textSecondary)),
        ]),
      );

  // Web pull 2026-09-28: each print button gets a green checkmark once its
  // document is done (`billing/index.blade.php`) — re-printing an
  // already-done document is still allowed (the gate only cares whether it
  // has EVER been printed, not "only once"), this is just a status hint.
  DateTime? _printedAtFor(OtDischargeDocType type) => switch (type) {
        OtDischargeDocType.summaryBill => _invoice?.summaryBillPrintedAt,
        OtDischargeDocType.discharge => _invoice?.dischargePrintedAt,
        OtDischargeDocType.certificate => _invoice?.certificatePrintedAt,
        _ => null,
      };

}

/// Extracted (see `otDischargeDocButton` above and `askOtCertificateRestDays`)
/// so the dashboard's compact Discharge desk panel
/// (`lib/widgets/desk_home_panels.dart`) can reuse this exact "Generate
/// Invoice" dialog instead of rebuilding it.
class OtInvoiceDraft {
  final String? followUpDate;
  const OtInvoiceDraft({this.followUpDate});
}

class OtGenerateInvoiceDialog extends StatefulWidget {
  const OtGenerateInvoiceDialog({super.key});

  @override
  State<OtGenerateInvoiceDialog> createState() => _OtGenerateInvoiceDialogState();
}

class _OtGenerateInvoiceDialogState extends State<OtGenerateInvoiceDialog> {
  // Web's Billing Desk only exposes a Follow-up Date picker next to the
  // Generate button, pre-filled with today+7 — no Tax Amount/Discount
  // fields exist in the real UI even though the backend accepts them (see
  // OT_DISCHARGE_INVOICES_WEB_PARITY_FIX_PLAN.md).
  late DateTime _followUpDate = DateTime.now().add(const Duration(days: 7));

  Future<void> _pickFollowUp() async {
    final now = DateTime.now();
    final picked = await showAppDatePicker(context: context, initialDate: _followUpDate, firstDate: now, lastDate: now.add(const Duration(days: 365)));
    if (picked != null) setState(() => _followUpDate = picked);
  }

  String _fmt(DateTime d) => toIsoDate(d);

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
          onPressed: () => Navigator.pop(context, OtInvoiceDraft(followUpDate: _fmt(_followUpDate))),
          child: const Text('Generate'),
        ),
      ],
    );
  }
}

String _dischargeDrName(String? name) {
  final n = name?.trim();
  if (n == null || n.isEmpty) return '';
  return RegExp(r'^dr\b\.?', caseSensitive: false).hasMatch(n) ? n : 'Dr. $n';
}

String _dischargeTitle(String? v) {
  if (v == null || v.trim().isEmpty) return '';
  return v.split('_').map((w) => w.isEmpty ? w : w[0].toUpperCase() + w.substring(1)).join(' ');
}

String _dischargeFmtNum(num? v) {
  if (v == null) return '';
  final s = v.toStringAsFixed(2);
  return s.replaceFirst(RegExp(r'\.?0+$'), '');
}

String _dischargeFmt12h(DateTime d) {
  final h12 = d.hour % 12 == 0 ? 12 : d.hour % 12;
  final period = d.hour >= 12 ? 'PM' : 'AM';
  return '$h12:${d.minute.toString().padLeft(2, '0')} $period';
}

(String, Color) _dischargeStageStyle(OtBookingSummary item) {
  final isDischarged = item.otStatus.toLowerCase() == 'discharged';
  final hasInvoice = item.hasInvoice ?? false;
  final done = item.dischargePrintsDone ?? 0;
  final total = item.dischargePrintsTotal ?? 3;
  if (isDischarged) return ('Discharged', AppColors.green);
  if (hasInvoice) return ('Printing $done/$total', AppColors.blue);
  return ('Awaiting Invoice', AppColors.orange);
}

/// Web parity (web pull 2026-09-30, `_view-modal.blade.php`) — extracted so
/// the dashboard's compact Discharge desk panel (see
/// `lib/widgets/desk_home_panels.dart`) shows the exact same rich read-only
/// detail as the full Discharge & Invoices list, instead of a second,
/// shallower copy. Neither surface had a real "View" before this — the full
/// list's whole row instead navigated straight into this actionable
/// generate/print screen, with no separate read-only view; the desk panel
/// had a 5-fact placeholder dialog.
Future<void> showOtDischargeBookingViewDialog(BuildContext context, OtBookingSummary item) async {
  final p = item.patient;
  final isDischarged = item.otStatus.toLowerCase() == 'discharged';
  final (stageLabel, stageColor) = _dischargeStageStyle(item);
  final ps = item.paymentStatus;
  final payLabel = ps != null ? paymentStatusLabel(ps) : 'Payment Pending';
  final payColor = paymentStatusColor(ps ?? 'pending');
  final cur = currentCurrencySymbol();
  final surgery = item.surgery;
  final lens = item.lensDetail;
  final invoice = item.invoice;

  final plannedLens = [
    if ((item.counselling?.lensCategory ?? '').isNotEmpty) item.counselling!.lensCategory![0].toUpperCase() + item.counselling!.lensCategory!.substring(1),
    [item.counselling?.lensCompany, item.counselling?.lensModel].where((s) => (s ?? '').isNotEmpty).join(' '),
  ].where((s) => s.isNotEmpty).join(' · ');
  final package = [
    item.counselling?.packageName,
    (item.counselling?.roomCategory.isNotEmpty ?? false) ? '${item.counselling!.roomCategory[0].toUpperCase()}${item.counselling!.roomCategory.substring(1)} room' : null,
  ].where((s) => (s ?? '').isNotEmpty).join(' · ');
  final mediclaim = item.counselling?.mediclaim ?? item.hasMediclaim;

  final patientFields = <AppRecordField>[
    if (p?.patientCode.isNotEmpty ?? false) AppRecordField('MRD No.', p!.patientCode, icon: Icons.badge_outlined, color: AppColors.primary),
    if (p?.contactNo != null) AppRecordField('Phone', p!.contactNo!, icon: Icons.call_outlined, color: RecordFieldColors.phone),
    if (p?.whatsappNo != null) AppRecordField('WhatsApp', p!.whatsappNo!, icon: Icons.chat_outlined, color: RecordFieldColors.phone),
    if (p?.age != null || p?.gender != null)
      AppRecordField('Age / Gender', [if (p?.age != null) '${p!.age}y', if (p?.gender != null) p!.genderDisplay].join(' / '), icon: Icons.cake_outlined, color: RecordFieldColors.age),
    if (p?.location?.city != null) AppRecordField('City', p!.location!.city, icon: Icons.location_on_outlined, color: RecordFieldColors.location),
    if (switch (p?.type) { 'phone' => true, 'walkin' => true, _ => false })
      AppRecordField('Visit Type', p?.type == 'phone' ? 'Phone Booking' : 'Walk-in', icon: Icons.call_outlined, color: RecordFieldColors.caseType),
    if (_dischargeDrName(p?.doctor?.name).isNotEmpty) AppRecordField('Consulting Doctor', _dischargeDrName(p?.doctor?.name), icon: Icons.person_outline_rounded, color: RecordFieldColors.doctor),
    if (p?.receptionistName != null) AppRecordField('Registered By', p!.receptionistName!, icon: Icons.how_to_reg_outlined, color: RecordFieldColors.receptionist),
  ];

  final planFields = <AppRecordField>[
    if (formatPlainDate(item.surgeryDate) != null) AppRecordField('OT Date', formatPlainDate(item.surgeryDate)!, icon: Icons.event_outlined, color: RecordFieldColors.date),
    if (item.eye != null) AppRecordField('Eye', item.eye!, icon: Icons.remove_red_eye_outlined, color: RecordFieldColors.caseType),
    if (item.otType != null) AppRecordField('Surgery Type', item.otType!, icon: Icons.medical_services_outlined, color: RecordFieldColors.caseType),
    if (item.counselling?.diagnosis != null) AppRecordField('Diagnosis', item.counselling!.diagnosis!, icon: Icons.description_outlined, color: RecordFieldColors.caseType),
    if (plannedLens.isNotEmpty) AppRecordField('Planned Lens', plannedLens, icon: Icons.lens_outlined, color: AppColors.textSecondary),
    if (package.isNotEmpty) AppRecordField('Package', package, icon: Icons.inventory_2_outlined, color: RecordFieldColors.package),
    if (mediclaim != null) AppRecordField('Mediclaim', mediclaim ? 'Yes' : 'No', icon: Icons.health_and_safety_outlined, color: AppColors.textSecondary),
    if (_dischargeDrName(item.otDoctor?.name).isNotEmpty) AppRecordField('OT Doctor', _dischargeDrName(item.otDoctor?.name), icon: Icons.person_outline_rounded, color: RecordFieldColors.doctor),
    if (item.otAssistant?.name != null) AppRecordField('OT Assistant', item.otAssistant!.name, icon: Icons.support_agent_outlined, color: RecordFieldColors.assistant),
  ];

  final duration = surgery?.durationMinutes;
  final surgeryFields = surgery == null
      ? const <AppRecordField>[]
      : <AppRecordField>[
          if (surgery.surgeryName != null) AppRecordField('Surgery', surgery.surgeryName!, icon: Icons.medical_services_outlined, color: RecordFieldColors.caseType),
          if (surgery.eyeOperated != null) AppRecordField('Eye Operated', surgery.eyeOperated!, icon: Icons.remove_red_eye_outlined, color: RecordFieldColors.caseType),
          if (_dischargeDrName(surgery.operatedBy?.name).isNotEmpty) AppRecordField('Operated By', _dischargeDrName(surgery.operatedBy?.name), icon: Icons.person_outline_rounded, color: RecordFieldColors.doctor),
          if (surgery.otRoom != null) AppRecordField('OT Room', surgery.otRoom!, icon: Icons.meeting_room_outlined, color: AppColors.textSecondary),
          if (surgery.surgeryAt != null || surgery.startTime != null)
            AppRecordField('Date', toDisplayDate((surgery.surgeryAt ?? surgery.startTime)!), icon: Icons.event_outlined, color: RecordFieldColors.date),
          if (surgery.startTime != null)
            AppRecordField('Time', '${_dischargeFmt12h(surgery.startTime!)}${surgery.endTime != null ? ' – ${_dischargeFmt12h(surgery.endTime!)}' : ''}', icon: Icons.schedule_rounded, color: RecordFieldColors.date),
          if (duration != null) AppRecordField('Duration', '$duration min', icon: Icons.timer_outlined, color: RecordFieldColors.date),
          if (_dischargeTitle(surgery.complicationStatus).isNotEmpty) AppRecordField('Complication', _dischargeTitle(surgery.complicationStatus), icon: Icons.warning_amber_outlined, color: AppColors.orange),
          if (surgery.complicationNotes != null) AppRecordField('Complication Notes', surgery.complicationNotes!, icon: Icons.notes_outlined, color: AppColors.textSecondary),
        ];

  final lensFields = lens == null
      ? const <AppRecordField>[]
      : <AppRecordField>[
          if (lens.lensName != null) AppRecordField('Lens', lens.lensName!, icon: Icons.lens_outlined, color: AppColors.textSecondary),
          if (lens.manufacturer != null) AppRecordField('Manufacturer', lens.manufacturer!, icon: Icons.factory_outlined, color: AppColors.textSecondary),
          if (lens.lensType != null) AppRecordField('Type', lens.lensType!, icon: Icons.category_outlined, color: RecordFieldColors.caseType),
          if (lens.lensPower != null) AppRecordField('Power', '${_dischargeFmtNum(lens.lensPower)} D', icon: Icons.bolt_outlined, color: AppColors.textSecondary),
          if (lens.axis != null && lens.axis != 0) AppRecordField('Axis', '${_dischargeFmtNum(lens.axis)}°', icon: Icons.explore_outlined, color: AppColors.textSecondary),
          if (lens.serialNumber != null) AppRecordField('Serial No.', lens.serialNumber!, icon: Icons.numbers_rounded, color: AppColors.textSecondary),
        ];

  final invoiceFields = invoice == null
      ? const <AppRecordField>[]
      : <AppRecordField>[
          AppRecordField('Invoice No.', invoice.invoiceNumber, icon: Icons.receipt_long_outlined, color: AppColors.primary),
          if (invoice.createdAt != null) AppRecordField('Generated On', formatDateTimeString(invoice.createdAt), icon: Icons.event_outlined, color: RecordFieldColors.date),
          if (invoice.generatedBy?.name != null) AppRecordField('Generated By', invoice.generatedBy!.name, icon: Icons.person_outline_rounded, color: AppColors.textSecondary),
          if (formatPlainDate(invoice.followUpDate) != null) AppRecordField('Follow-up Date', formatPlainDate(invoice.followUpDate)!, icon: Icons.event_repeat_outlined, color: RecordFieldColors.date),
          if (isDischarged && item.dischargedAt != null) AppRecordField('Discharged On', toDisplayDateTime(item.dischargedAt!), icon: Icons.logout_rounded, color: AppColors.green),
        ];

  final docs = [
    ('Bill Summary', item.summaryBillPrintedAt),
    ('Discharge Summary', item.dischargePrintedAt),
    ('Surgery Certificate', item.certificatePrintedAt),
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
        title: 'Discharge Documents',
        icon: Icons.fact_check_outlined,
        child: Column(
          children: docs.map((d) => Padding(
                padding: const EdgeInsets.symmetric(vertical: 4),
                child: Row(children: [
                  Icon(d.$2 != null ? Icons.check_circle_rounded : Icons.hourglass_empty_rounded, size: 15, color: d.$2 != null ? AppColors.green : AppColors.orange),
                  const SizedBox(width: 8),
                  Expanded(child: Text(d.$1, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600))),
                  Text(d.$2 != null ? formatDateTimeString(d.$2!.toIso8601String()) : (isDischarged ? 'Not recorded' : 'Not printed'), style: TextStyle(fontSize: 10.5, color: d.$2 != null ? AppColors.green : AppColors.orange)),
                ]),
              )).toList(),
        ),
      ),
      if (invoice != null)
        AppDetailSectionCard(
          title: 'Invoice',
          icon: Icons.receipt_long_outlined,
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            AppFieldGrid(fields: invoiceFields),
            if (invoice.lineItems.isNotEmpty) ...[
              const SizedBox(height: 4),
              Container(height: 1, color: AppColors.primaryA08),
              const SizedBox(height: 10),
              for (final li in invoice.lineItems)
                Padding(padding: const EdgeInsets.symmetric(vertical: 3), child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [Text(li.head, style: const TextStyle(fontSize: 12)), Text('$cur${li.amount.toStringAsFixed(2)}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600))])),
              if (invoice.taxAmount > 0) Padding(padding: const EdgeInsets.symmetric(vertical: 3), child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [const Text('Tax', style: TextStyle(fontSize: 12)), Text('$cur${invoice.taxAmount.toStringAsFixed(2)}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600))])),
              if (invoice.discount > 0) Padding(padding: const EdgeInsets.symmetric(vertical: 3), child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [const Text('Discount', style: TextStyle(fontSize: 12)), Text('− $cur${invoice.discount.toStringAsFixed(2)}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600))])),
              const SizedBox(height: 2),
              Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [const Text('Net Amount', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w800)), Text('$cur${invoice.netAmount.toStringAsFixed(2)}', style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w800))]),
            ],
          ]),
        ),
      AppDetailSectionCard(
        title: 'Surgery Record',
        icon: Icons.medical_services_outlined,
        child: surgeryFields.isEmpty
            ? const Text('Surgery record not found.', style: TextStyle(fontSize: 12, color: AppColors.textSecondary))
            : AppFieldGrid(fields: surgeryFields),
      ),
      if (lensFields.isNotEmpty)
        AppDetailSectionCard(
          title: 'Implanted Lens',
          icon: Icons.lens_outlined,
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            if (lens?.isImplanted ?? false) Padding(padding: const EdgeInsets.only(bottom: 10), child: AppStatusPill(label: 'Implanted', color: AppColors.green)),
            AppFieldGrid(fields: lensFields),
          ]),
        ),
      AppDetailSectionCard(title: 'Surgery Plan', icon: Icons.event_note_outlined, child: AppFieldGrid(fields: planFields)),
      AppDetailSectionCard(title: 'Patient Details', icon: Icons.badge_outlined, child: AppFieldGrid(fields: patientFields)),
    ],
  );
}
