import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/ot_discharge_models.dart';
import '../services/ot_discharge_service.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_section_header.dart';

/// Tablet Discharge detail (Round 3 Phase 6) — Pattern C: all 8 print
/// documents visible at once in a wide grid (a natural tablet win — no
/// scrolling needed, unlike the mobile 2-column version). Full pushed
/// route (rail hidden). Ported from
/// eye_care_app/lib/screens/ot_discharge_detail_screen.dart.
class OtDischargeDetailScreen extends StatefulWidget {
  final int bookingId;
  final String patientName;

  const OtDischargeDetailScreen({super.key, required this.bookingId, required this.patientName});

  @override
  State<OtDischargeDetailScreen> createState() => _OtDischargeDetailScreenState();
}

class _OtDischargeDetailScreenState extends State<OtDischargeDetailScreen> {
  bool _generating = false;
  OtDischargeDocType? _printingType;
  bool _printingAll = false;

  Future<void> _generateInvoice() async {
    final result = await showDialog<_InvoiceDraft>(context: context, builder: (_) => const _GenerateInvoiceDialog());
    if (result == null) return;
    setState(() => _generating = true);
    try {
      final r = await OtDischargeService.instance.generateInvoice(widget.bookingId, followUpDate: result.followUpDate);
      if (!mounted) return;
      showAppSnackBar(context, 'Invoice ${r.invoiceNumber} generated — booking discharged', isSuccess: true);
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

  Future<void> _printAll() async {
    setState(() => _printingAll = true);
    try {
      final manifest = await OtDischargeService.instance.fetchBundleManifest(widget.bookingId);
      for (final doc in manifest) {
        await OtDischargeService.instance.printFromManifestUrl(doc);
      }
      if (mounted) showAppSnackBar(context, 'Opened ${manifest.length} documents', isSuccess: true);
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    } finally {
      if (mounted) setState(() => _printingAll = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFEBF5FB),
      body: Column(children: [
        _buildHeader(),
        Expanded(child: _buildBody()),
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
            IconButton(icon: const Icon(Icons.arrow_back_rounded, color: Colors.white), onPressed: () => Navigator.of(context).pop()),
            Container(width: 40, height: 40, decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(10)), child: const Icon(Icons.receipt_long_rounded, color: Colors.white, size: 20)),
            const SizedBox(width: 12),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                const Text('Discharge & Invoices', style: TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.w800)),
                Text(widget.patientName, style: TextStyle(color: Colors.white.withValues(alpha: 0.75), fontSize: 11)),
              ]),
            ),
            ElevatedButton.icon(
              onPressed: _generating ? null : _generateInvoice,
              icon: _generating ? SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.primary)) : const Icon(Icons.receipt_rounded, size: 18),
              label: const Text('Generate Invoice / Discharge'),
              style: ElevatedButton.styleFrom(backgroundColor: Colors.white, foregroundColor: AppColors.primary),
            ),
          ]),
        ),
      ),
    );
  }

  Widget _buildBody() {
    return Center(
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 900),
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            const AppSectionHeader(title: 'Print Documents', icon: Icons.print_outlined),
            GridView.count(
              crossAxisCount: 4,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              mainAxisSpacing: 12,
              crossAxisSpacing: 12,
              childAspectRatio: 2.2,
              children: OtDischargeDocType.values.map((t) => _docButton(t)).toList(),
            ),
            const SizedBox(height: 20),
            Center(child: Text('"Print All" opens each document individually — the backend has no single merged PDF (matches web behavior).', style: TextStyle(fontSize: 11, color: AppColors.textSecondary))),
            const SizedBox(height: 12),
            Center(
              child: OutlinedButton.icon(
                onPressed: _printingAll ? null : _printAll,
                icon: _printingAll ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.folder_zip_outlined, size: 18),
                label: const Text('Print All'),
                style: OutlinedButton.styleFrom(foregroundColor: AppColors.primary, padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 14)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _docButton(OtDischargeDocType type) {
    final busy = _printingType == type;
    return OutlinedButton.icon(
      onPressed: busy ? null : () => _print(type),
      icon: busy ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.picture_as_pdf_outlined, size: 16),
      label: Text(type.label, style: const TextStyle(fontSize: 12), overflow: TextOverflow.ellipsis),
      style: OutlinedButton.styleFrom(foregroundColor: AppColors.primary, side: BorderSide(color: AppColors.primary.withValues(alpha: 0.3))),
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
  // Generate button — no Tax Amount/Discount fields exist in the real UI
  // even though the backend accepts them (see OT_WEB_PARITY_FIX_PRD.md §7.1).
  DateTime? _followUpDate;

  Future<void> _pickFollowUp() async {
    final now = DateTime.now();
    final picked = await showDatePicker(context: context, initialDate: now.add(const Duration(days: 7)), firstDate: now, lastDate: now.add(const Duration(days: 365)));
    if (picked != null) setState(() => _followUpDate = picked);
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
      title: const Text('Generate Invoice'),
      content: SizedBox(
        width: 360,
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          InkWell(onTap: _pickFollowUp, child: InputDecorator(decoration: const InputDecoration(labelText: 'Follow-up Date (optional, defaults +7 days)', border: OutlineInputBorder()), child: Text(_followUpDate == null ? 'Select' : '${_followUpDate!.year}-${_followUpDate!.month.toString().padLeft(2, '0')}-${_followUpDate!.day.toString().padLeft(2, '0')}'))),
        ]),
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')),
        ElevatedButton(
          onPressed: () => Navigator.pop(
            context,
            _InvoiceDraft(
              followUpDate: _followUpDate == null ? null : '${_followUpDate!.year}-${_followUpDate!.month.toString().padLeft(2, '0')}-${_followUpDate!.day.toString().padLeft(2, '0')}',
            ),
          ),
          child: const Text('Generate'),
        ),
      ],
    );
  }
}
