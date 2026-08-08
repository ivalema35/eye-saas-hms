import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/ot_counsellor_models.dart';
import '../models/ot_inventory_models.dart';
import '../services/ot_counsellor_service.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_section_header.dart';
import '../utils/app_route.dart';
import 'ot_consent_screen.dart';

/// Round 3 Phase 1 — Counsellor form. One scrollable sectioned form, single
/// Save (the backend's `storeCounselling()` is one combined call — no
/// per-section save, unlike the exam screens' pattern).
class OtCounsellingFormScreen extends StatefulWidget {
  final int bookingId;
  final String patientName;

  const OtCounsellingFormScreen({super.key, required this.bookingId, required this.patientName});

  @override
  State<OtCounsellingFormScreen> createState() => _OtCounsellingFormScreenState();
}

class _OtCounsellingFormScreenState extends State<OtCounsellingFormScreen> {
  static final _genFmt = [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d*'))];

  bool _loading = true;
  String? _loadError;
  OtCounsellingDetail? _detail;
  bool _saving = false;

  late final TextEditingController _diagnosisCtrl, _lensCompanyCtrl, _lensModelCtrl, _estimatedPowerCtrl, _lensCostCtrl, _packageNameCtrl, _otChargesCtrl, _surgeonChargesCtrl, _nursingChargesCtrl, _consumablesChargesCtrl, _notesCtrl;
  String _eye = 'Both';
  // Replaces the old surgery_type_confirmed checkbox — required, matches
  // web exactly. See WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §7.
  String? _otType;
  bool _mediclaim = false;
  String _lensCategory = 'standard';
  String? _lensType;
  String _roomCategory = 'general';
  String _paymentMode = 'cash';
  // Now required on web (was optional) — null until the user actively
  // answers, so "not yet answered" is distinguishable from "No".
  bool? _bloodReportsVerified;
  bool? _bloodReportsNormal;

  // Selecting a package still auto-fills room/name/4 charges, but no longer
  // lens cost — web pull 2026-08-07 decoupled lens cost from the package
  // entirely (it's now a manually-typed field, packages default to 0). See
  // WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §3/§7.
  int? _selectedPackageId;

  @override
  void initState() {
    super.initState();
    _diagnosisCtrl = TextEditingController();
    _lensCompanyCtrl = TextEditingController();
    _lensModelCtrl = TextEditingController();
    _estimatedPowerCtrl = TextEditingController();
    _lensCostCtrl = TextEditingController(text: '0');
    _packageNameCtrl = TextEditingController();
    _otChargesCtrl = TextEditingController(text: '0');
    _surgeonChargesCtrl = TextEditingController(text: '0');
    _nursingChargesCtrl = TextEditingController(text: '0');
    _consumablesChargesCtrl = TextEditingController(text: '0');
    _notesCtrl = TextEditingController();
    for (final c in [_otChargesCtrl, _surgeonChargesCtrl, _nursingChargesCtrl, _consumablesChargesCtrl, _lensCostCtrl]) {
      c.addListener(() => setState(() {}));
    }
    _load();
  }

  @override
  void dispose() {
    for (final c in [_diagnosisCtrl, _lensCompanyCtrl, _lensModelCtrl, _estimatedPowerCtrl, _lensCostCtrl, _packageNameCtrl, _otChargesCtrl, _surgeonChargesCtrl, _nursingChargesCtrl, _consumablesChargesCtrl, _notesCtrl]) {
      c.dispose();
    }
    super.dispose();
  }

  double get _totalEstimate =>
      (double.tryParse(_otChargesCtrl.text.trim()) ?? 0) +
      (double.tryParse(_surgeonChargesCtrl.text.trim()) ?? 0) +
      (double.tryParse(_nursingChargesCtrl.text.trim()) ?? 0) +
      (double.tryParse(_consumablesChargesCtrl.text.trim()) ?? 0) +
      (double.tryParse(_lensCostCtrl.text.trim()) ?? 0);

  Future<void> _load() async {
    setState(() { _loading = true; _loadError = null; });
    try {
      final detail = await OtCounsellorService.instance.fetchCounsellingDetail(widget.bookingId);
      if (mounted) {
        setState(() {
          _detail = detail;
          _loading = false;
          _eye = detail.counselling?.eye ?? detail.booking.eye ?? 'Both';
          final c = detail.counselling;
          if (c != null) {
            _diagnosisCtrl.text = c.diagnosis ?? '';
            _otType = c.otType;
            _mediclaim = c.mediclaim;
            _lensCategory = c.lensCategory ?? 'standard';
            _lensCompanyCtrl.text = c.lensCompany ?? '';
            _lensModelCtrl.text = c.lensModel ?? '';
            _lensType = c.lensType;
            _estimatedPowerCtrl.text = c.estimatedPower?.toString() ?? '';
            _lensCostCtrl.text = c.lensCost?.toString() ?? '0';
            _packageNameCtrl.text = c.packageName ?? '';
            _roomCategory = c.roomCategory;
            _otChargesCtrl.text = c.otCharges.toString();
            _surgeonChargesCtrl.text = c.surgeonCharges.toString();
            _nursingChargesCtrl.text = c.nursingCharges.toString();
            _consumablesChargesCtrl.text = c.consumablesCharges.toString();
            // Best-effort: pre-select the matching package option by name +
            // room category (lens_cost is no longer a meaningful match key).
            final match = detail.packageCostOptions.where((p) => p.packageName == c.packageName && p.roomCategory == c.roomCategory).toList();
            if (match.isNotEmpty) _selectedPackageId = match.first.id;
            _paymentMode = c.mediclaim ? 'cash' : c.paymentMode;
            _bloodReportsVerified = c.bloodReportsVerified;
            _bloodReportsNormal = c.bloodReportsNormal;
            _notesCtrl.text = c.notes ?? '';
          }
        });
      }
    } catch (e) {
      if (mounted) setState(() { _loadError = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  /// Selecting a package auto-fills package name, room category, and all 4
  /// charge fields — but NOT lens cost, which is a manually-typed field,
  /// fully decoupled from the package (web pull 2026-08-07). See
  /// WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §7.
  void _selectPackage(OtPackageMasterItem pkg) {
    setState(() {
      _selectedPackageId = pkg.id;
      _roomCategory = pkg.roomCategory;
      _packageNameCtrl.text = pkg.packageName;
      _otChargesCtrl.text = pkg.otCharges.toString();
      _surgeonChargesCtrl.text = pkg.surgeonCharges.toString();
      _nursingChargesCtrl.text = pkg.nursingCharges.toString();
      _consumablesChargesCtrl.text = pkg.consumablesCharges.toString();
    });
  }

  Future<void> _save() async {
    if (_otType == null) {
      showAppSnackBar(context, 'Surgery type is required', isError: true);
      return;
    }
    if (_bloodReportsVerified == null || _bloodReportsNormal == null) {
      showAppSnackBar(context, 'Blood reports verified/normal must be answered', isError: true);
      return;
    }
    setState(() => _saving = true);
    try {
      final counselling = OtCounsellingItem(
        diagnosis: _diagnosisCtrl.text.trim().isEmpty ? null : _diagnosisCtrl.text.trim(),
        eye: _eye,
        otType: _otType,
        mediclaim: _mediclaim,
        lensCategory: _lensCategory,
        lensCompany: _lensCompanyCtrl.text.trim().isEmpty ? null : _lensCompanyCtrl.text.trim(),
        lensModel: _lensModelCtrl.text.trim().isEmpty ? null : _lensModelCtrl.text.trim(),
        lensType: _lensType,
        estimatedPower: double.tryParse(_estimatedPowerCtrl.text.trim()),
        lensCost: double.tryParse(_lensCostCtrl.text.trim()),
        packageName: _packageNameCtrl.text.trim().isEmpty ? null : _packageNameCtrl.text.trim(),
        roomCategory: _roomCategory,
        otCharges: double.tryParse(_otChargesCtrl.text.trim()) ?? 0,
        surgeonCharges: double.tryParse(_surgeonChargesCtrl.text.trim()) ?? 0,
        nursingCharges: double.tryParse(_nursingChargesCtrl.text.trim()) ?? 0,
        consumablesCharges: double.tryParse(_consumablesChargesCtrl.text.trim()) ?? 0,
        paymentMode: _paymentMode,
        bloodReportsVerified: _bloodReportsVerified!,
        bloodReportsNormal: _bloodReportsNormal!,
        notes: _notesCtrl.text.trim().isEmpty ? null : _notesCtrl.text.trim(),
      );
      final totalEstimate = await OtCounsellorService.instance.storeCounselling(widget.bookingId, counselling);
      if (!mounted) return;
      showAppSnackBar(context, 'Counselling saved — estimate ₹${totalEstimate.toStringAsFixed(0)}', isSuccess: true);
      Navigator.of(context).push(appRoute(OtConsentScreen(bookingId: widget.bookingId, patientName: widget.patientName)));
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
    }
  }

  /// Yes/No pair for a field that's `required boolean` on the backend
  /// (blood_reports_verified/normal, web pull 2026-08-07 — was an optional
  /// checkbox defaulting to false before). `null` means "not yet answered",
  /// checked in `_save()` before submit.
  Widget _requiredYesNo(String label, bool? value, ValueChanged<bool> onChanged) {
    return Row(children: [
      Expanded(child: Text(label, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600))),
      ChoiceChip(label: const Text('Yes'), selected: value == true, onSelected: (_) => onChanged(true)),
      const SizedBox(width: 8),
      ChoiceChip(label: const Text('No'), selected: value == false, onSelected: (_) => onChanged(false)),
    ]);
  }

  InputDecoration _deco(String label, {String? suffix}) => InputDecoration(
        labelText: label,
        suffixText: suffix,
        filled: true,
        fillColor: const Color(0xFFF0F6FB),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide.none),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.md), borderSide: const BorderSide(color: AppColors.teal, width: 1.5)),
      );

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
                  : _buildForm(),
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
            Container(width: 40, height: 40, decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(10)), child: const Icon(Icons.support_agent_rounded, color: Colors.white, size: 20)),
            const SizedBox(width: 12),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                const Text('Counselling', style: TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.w800)),
                Text(widget.patientName, style: TextStyle(color: Colors.white.withValues(alpha: 0.75), fontSize: 11)),
              ]),
            ),
          ]),
        ),
      ),
    );
  }

  Widget _buildForm() {
    final detail = _detail!;
    return Column(children: [
      Expanded(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 20),
          children: [
            const AppSectionHeader(title: 'Diagnosis & Eye', icon: Icons.assignment_outlined),
            TextFormField(controller: _diagnosisCtrl, maxLines: 2, decoration: _deco('Diagnosis')),
            const SizedBox(height: 10),
            Wrap(spacing: 8, children: ['RE', 'LE', 'Both'].map((e) => ChoiceChip(label: Text(e), selected: _eye == e, onSelected: (_) => setState(() => _eye = e))).toList()),
            const SizedBox(height: 10),
            DropdownButtonFormField<String>(
              initialValue: _otType,
              isExpanded: true,
              decoration: _deco('Surgery Type *'),
              items: detail.otSurgeryTypes.map((t) => DropdownMenuItem(value: t.surgeryName, child: Text(t.surgeryName, overflow: TextOverflow.ellipsis))).toList(),
              onChanged: (v) => setState(() => _otType = v),
            ),
            const SizedBox(height: 6),
            SwitchListTile(value: _mediclaim, onChanged: (v) => setState(() => _mediclaim = v), title: const Text('Mediclaim', style: TextStyle(fontSize: 13)), contentPadding: EdgeInsets.zero, activeThumbColor: AppColors.teal),
            const SizedBox(height: 16),

            const AppSectionHeader(title: 'Lens', icon: Icons.remove_red_eye_outlined),
            Wrap(spacing: 8, children: ['standard', 'premium'].map((c) => ChoiceChip(label: Text(c[0].toUpperCase() + c.substring(1)), selected: _lensCategory == c, onSelected: (_) => setState(() => _lensCategory = c))).toList()),
            const SizedBox(height: 10),
            Row(children: [
              Expanded(child: TextFormField(controller: _lensCompanyCtrl, decoration: _deco('Lens Company'))),
              const SizedBox(width: 10),
              Expanded(child: TextFormField(controller: _lensModelCtrl, decoration: _deco('Lens Model'))),
            ]),
            const SizedBox(height: 10),
            DropdownButtonFormField<String>(initialValue: _lensType, isExpanded: true, decoration: _deco('Lens Type'), items: kOtLensTypes.map((t) => DropdownMenuItem(value: t, child: Text(t))).toList(), onChanged: (v) => setState(() => _lensType = v)),
            const SizedBox(height: 10),
            Row(children: [
              Expanded(child: TextFormField(controller: _estimatedPowerCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true, signed: true), inputFormatters: _genFmt, decoration: _deco('Estimated Power', suffix: 'D'))),
              const SizedBox(width: 10),
              // Lens Cost is now a plain manual field, decoupled from the
              // package (web pull 2026-08-07) — typed in and added into
              // Total Estimate, not autofilled by package selection anymore.
              Expanded(child: TextFormField(controller: _lensCostCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), inputFormatters: _genFmt, decoration: _deco('Lens Cost', suffix: '₹'))),
            ]),
            const SizedBox(height: 16),

            const AppSectionHeader(title: 'Package / Room', icon: Icons.card_giftcard_rounded),
            DropdownButtonFormField<int>(
              initialValue: _selectedPackageId,
              isExpanded: true,
              decoration: _deco('OT Package'),
              items: detail.packageCostOptions.map((p) => DropdownMenuItem(value: p.id, child: Text('${p.packageName} · ${p.roomCategory[0].toUpperCase()}${p.roomCategory.substring(1)}', overflow: TextOverflow.ellipsis))).toList(),
              onChanged: (v) {
                if (v == null) return;
                _selectPackage(detail.packageCostOptions.firstWhere((p) => p.id == v));
              },
            ),
            const SizedBox(height: 10),
            Wrap(spacing: 8, children: ['general', 'private'].map((r) => ChoiceChip(label: Text(r[0].toUpperCase() + r.substring(1)), selected: _roomCategory == r, onSelected: (_) => setState(() => _roomCategory = r))).toList()),
            const SizedBox(height: 10),
            TextFormField(controller: _packageNameCtrl, decoration: _deco('Package Name')),
            const SizedBox(height: 10),
            Row(children: [
              Expanded(child: TextFormField(controller: _otChargesCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), inputFormatters: _genFmt, decoration: _deco('OT Charges', suffix: '₹'))),
              const SizedBox(width: 10),
              Expanded(child: TextFormField(controller: _surgeonChargesCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), inputFormatters: _genFmt, decoration: _deco('Surgeon Charges', suffix: '₹'))),
            ]),
            const SizedBox(height: 10),
            Row(children: [
              Expanded(child: TextFormField(controller: _nursingChargesCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), inputFormatters: _genFmt, decoration: _deco('Nursing Charges', suffix: '₹'))),
              const SizedBox(width: 10),
              Expanded(child: TextFormField(controller: _consumablesChargesCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), inputFormatters: _genFmt, decoration: _deco('Consumables', suffix: '₹'))),
            ]),
            const SizedBox(height: 12),
            // Matches web exactly: Total Estimate = ot_charges + surgeon_charges
            // + nursing_charges + consumables_charges + lens_cost (web pull
            // 2026-08-07 changed this from lens_cost-only). See
            // WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §7.
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              decoration: BoxDecoration(color: const Color(0xFFF0F6FB), borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.18))),
              child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                const Text('Total Estimate', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700)),
                Text('₹${_totalEstimate.toStringAsFixed(2)}', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.primary)),
              ]),
            ),
            const SizedBox(height: 16),

            const AppSectionHeader(title: 'Payment & Reports', icon: Icons.receipt_long_outlined),
            if (!_mediclaim)
              Wrap(spacing: 8, children: ['cash', 'online'].map((m) => ChoiceChip(label: Text(m[0].toUpperCase() + m.substring(1)), selected: _paymentMode == m, onSelected: (_) => setState(() => _paymentMode = m))).toList())
            else
              Container(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8), decoration: BoxDecoration(color: AppColors.orangeA12, borderRadius: BorderRadius.circular(AppRadius.sm)), child: const Text('Payment mode: Mediclaim', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.orange))),
            const SizedBox(height: 10),
            _requiredYesNo('Blood reports verified *', _bloodReportsVerified, (v) => setState(() => _bloodReportsVerified = v)),
            const SizedBox(height: 10),
            _requiredYesNo('Blood reports normal *', _bloodReportsNormal, (v) => setState(() => _bloodReportsNormal = v)),
            const SizedBox(height: 10),
            TextFormField(controller: _notesCtrl, maxLines: 3, decoration: _deco('Notes')),
          ],
        ),
      ),
      SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
          child: ElevatedButton(
            onPressed: _saving ? null : _save,
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.teal, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md))),
            child: _saving
                ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                : const Text('Save & Continue to Consent', style: TextStyle(fontWeight: FontWeight.w700)),
          ),
        ),
      ),
    ]);
  }
}
