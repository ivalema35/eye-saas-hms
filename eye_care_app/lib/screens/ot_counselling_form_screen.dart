import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:signature/signature.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/permissions.dart';
import '../models/ot_assistant_models.dart';
import '../models/ot_booking_models.dart';
import '../models/ot_counsellor_models.dart';
import '../models/ot_inventory_models.dart';
import '../services/ot_counsellor_service.dart';
import '../services/permission_service.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_error_state.dart';
import '../widgets/skeleton.dart';
import '../widgets/app_section_header.dart';
import '../widgets/ot/eye_choice_selector.dart';
import '../widgets/ot/signature_pad_field.dart';
import '../utils/currency_format.dart';

/// Web parity (`ot/counsellor/form.blade.php`): ONE scrollable page holding
/// THREE independent forms/actions, always simultaneously visible with no
/// step-gating — Counselling (`storeCounselling`), Informed Consent
/// (`storeConsent`), and Send to Billing. Previously split across two
/// screens (Counselling → push → Consent); web has never had that
/// step-by-step structure, so this was merged back into one page.
class OtCounsellingFormScreen extends StatefulWidget {
  final int bookingId;
  final String patientName;

  const OtCounsellingFormScreen({
    super.key,
    required this.bookingId,
    required this.patientName,
  });

  @override
  State<OtCounsellingFormScreen> createState() =>
      _OtCounsellingFormScreenState();
}

class _OtCounsellingFormScreenState extends State<OtCounsellingFormScreen> {
  static final _genFmt = [
    FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d*')),
  ];

  bool _loading = true;
  String? _loadError;
  OtCounsellingDetail? _detail;
  bool _savingCounselling = false;

  bool get _canConsent => PermissionService.instance.can(Perm.otConsentCapture);

  // Usually just `detail.otSurgeryTypes` (the active list) — but if this
  // booking's already-saved `ot_type` was since deactivated/renamed, it
  // won't be in that list, and the dropdown would show blank despite the
  // value being genuinely saved (Bug #3, OT_BUGS_ROUND4_AUDIT.md). Mirrors
  // the backend's own tolerance for this exact situation
  // (storeCounselling()'s "already-set-but-since-deactivated" fallback).
  List<OtSurgeryTypeOption> _surgeryTypeOptions = [];

  // ── Counselling form state ──────────────────────────────────────────────
  late final TextEditingController _diagnosisCtrl,
      _lensCompanyCtrl,
      _lensModelCtrl,
      _lensCostCtrl,
      _otChargesCtrl,
      _surgeonChargesCtrl,
      _nursingChargesCtrl,
      _consumablesChargesCtrl;
  String _eye = 'Both';
  // Replaces the old surgery_type_confirmed checkbox — required, matches
  // web exactly. See WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §7.
  String? _otType;
  String _lensCategory = 'standard';
  String? _lensType;
  // Web pull 2026-10-13: "Estimated Power" → "Lens Power" dropdown sourced
  // from the active Lens Power master, not a free numeric input. Keeps the
  // field name `estimated_power` server-side (no rename).
  double? _estimatedPower;
  String _roomCategory = 'general';
  // Web parity: this field is a hidden `<input>` on web, only ever set by
  // the OT Package dropdown's JS, never a control the counsellor types
  // into directly — no visible field for it here either.
  String? _packageName;
  bool _mediclaim = false;
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

  // ── Consent form state ───────────────────────────────────────────────────
  final _patientSigCtrl = SignatureController(
    penStrokeWidth: 2,
    penColor: AppColors.darkNavy,
  );
  final _guardianSigCtrl = SignatureController(
    penStrokeWidth: 2,
    penColor: AppColors.darkNavy,
  );
  final _witnessCtrl = TextEditingController();
  bool _consentGiven = false;
  bool _savingConsent = false;
  bool _consentSaved = false;

  // ── Send to billing state ────────────────────────────────────────────────
  bool _sendingToBilling = false;
  String? _otStatus;

  @override
  void initState() {
    super.initState();
    _diagnosisCtrl = TextEditingController();
    _lensCompanyCtrl = TextEditingController();
    _lensModelCtrl = TextEditingController();
    _lensCostCtrl = TextEditingController(text: '0');
    _otChargesCtrl = TextEditingController(text: '0');
    _surgeonChargesCtrl = TextEditingController(text: '0');
    _nursingChargesCtrl = TextEditingController(text: '0');
    _consumablesChargesCtrl = TextEditingController(text: '0');
    for (final c in [
      _otChargesCtrl,
      _surgeonChargesCtrl,
      _nursingChargesCtrl,
      _consumablesChargesCtrl,
      _lensCostCtrl,
    ]) {
      c.addListener(() => setState(() {}));
    }
    _load();
  }

  @override
  void dispose() {
    for (final c in [
      _diagnosisCtrl,
      _lensCompanyCtrl,
      _lensModelCtrl,
      _lensCostCtrl,
      _otChargesCtrl,
      _surgeonChargesCtrl,
      _nursingChargesCtrl,
      _consumablesChargesCtrl,
      _witnessCtrl,
    ]) {
      c.dispose();
    }
    _patientSigCtrl.dispose();
    _guardianSigCtrl.dispose();
    super.dispose();
  }

  double get _totalEstimate =>
      (double.tryParse(_otChargesCtrl.text.trim()) ?? 0) +
      (double.tryParse(_surgeonChargesCtrl.text.trim()) ?? 0) +
      (double.tryParse(_nursingChargesCtrl.text.trim()) ?? 0) +
      (double.tryParse(_consumablesChargesCtrl.text.trim()) ?? 0) +
      (double.tryParse(_lensCostCtrl.text.trim()) ?? 0);

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _loadError = null;
    });
    try {
      final detail = await OtCounsellorService.instance.fetchCounsellingDetail(
        widget.bookingId,
      );
      if (mounted) {
        setState(() {
          _detail = detail;
          _loading = false;
          _otStatus = detail.booking.otStatus;
          _surgeryTypeOptions = detail.otSurgeryTypes;
          // `eye`/`ot_type` are NOT real columns on the counselling record —
          // storeCounselling() only ever persists them onto the booking row
          // (`$booking->update(['eye' => ..., 'ot_type' => ...])`). Reading
          // them from `detail.counselling` first was always wrong: that
          // field is either genuinely absent (ot_type — silently null, so
          // the dropdown showed no selection) or has a model-level fallback
          // baked in (eye — OtCounsellingItem.fromJson defaults a missing
          // key to 'Both', which is a non-null value that always won the
          // old `??` chain before it could ever reach the real value on
          // `detail.booking`). `detail.booking.eye`/`.otType` are the actual
          // source of truth and correctly parsed already. See
          // OT_BUGS_ROUND4_AUDIT.md Bug #3 (live-testing follow-up).
          _eye = detail.booking.eye ?? 'Both';
          _otType = detail.booking.otType;
          if (_otType != null &&
              _surgeryTypeOptions.every((t) => t.surgeryName != _otType)) {
            _surgeryTypeOptions = [
              OtSurgeryTypeOption(id: -1, surgeryName: _otType!),
              ..._surgeryTypeOptions,
            ];
          }
          final c = detail.counselling;
          if (c != null) {
            _diagnosisCtrl.text = c.diagnosis ?? '';
            _mediclaim = c.mediclaim;
            _lensCategory = c.lensCategory ?? 'standard';
            _lensCompanyCtrl.text = c.lensCompany ?? '';
            _lensModelCtrl.text = c.lensModel ?? '';
            _lensType = c.lensType;
            _estimatedPower = c.estimatedPower;
            _lensCostCtrl.text = c.lensCost?.toString() ?? '0';
            _packageName = c.packageName;
            _roomCategory = c.roomCategory;
            _otChargesCtrl.text = c.otCharges.toString();
            _surgeonChargesCtrl.text = c.surgeonCharges.toString();
            _nursingChargesCtrl.text = c.nursingCharges.toString();
            _consumablesChargesCtrl.text = c.consumablesCharges.toString();
            // Best-effort: pre-select the matching package option by name +
            // room category (lens_cost is no longer a meaningful match key).
            final match = detail.packageCostOptions
                .where(
                  (p) =>
                      p.packageName == c.packageName &&
                      p.roomCategory == c.roomCategory,
                )
                .toList();
            if (match.isNotEmpty) _selectedPackageId = match.first.id;
            _paymentMode = c.paymentMode;
            _bloodReportsVerified = c.bloodReportsVerified;
            _bloodReportsNormal = c.bloodReportsNormal;
          }
          final consent = detail.consent;
          if (consent != null) {
            _consentGiven = consent.consentGiven;
            _witnessCtrl.text = consent.witnessName ?? '';
            _consentSaved = consent.consentGiven;
          }
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

  /// Selecting a package auto-fills package name, room category, and all 4
  /// charge fields — but NOT lens cost, which is a manually-typed field,
  /// fully decoupled from the package (web pull 2026-08-07). See
  /// WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §7.
  void _selectPackage(OtPackageMasterItem pkg) {
    setState(() {
      _selectedPackageId = pkg.id;
      _roomCategory = pkg.roomCategory;
      _packageName = pkg.packageName;
      _otChargesCtrl.text = pkg.otCharges.toString();
      _surgeonChargesCtrl.text = pkg.surgeonCharges.toString();
      _nursingChargesCtrl.text = pkg.nursingCharges.toString();
      _consumablesChargesCtrl.text = pkg.consumablesCharges.toString();
    });
  }

  // Web parity (web pull 2026-10-13): "Lens Power" dropdown sourced from
  // the active Lens Power master, "★ " prefix for favourites, "+{power}D"
  // label. A legacy value that no longer matches any active master row
  // stays selectable as its own "(previously saved)" option, so resaving
  // the form without touching this field never silently wipes legacy data.
  Widget _buildLensPowerDropdown(OtCounsellingDetail detail) {
    final powers = detail.lensPowers;
    final matchesMaster = _estimatedPower != null && powers.any((p) => p.power == _estimatedPower);
    return DropdownButtonFormField<double>(
      initialValue: _estimatedPower,
      isExpanded: true,
      decoration: _deco('Lens Power'),
      items: [
        for (final p in powers)
          DropdownMenuItem(value: p.power, child: Text('${p.isFavourite ? '★ ' : ''}+${p.power.toStringAsFixed(2)}D')),
        if (_estimatedPower != null && !matchesMaster)
          DropdownMenuItem(value: _estimatedPower, child: Text('+${_estimatedPower!.toStringAsFixed(2)}D (previously saved)')),
      ],
      onChanged: (v) => setState(() => _estimatedPower = v),
    );
  }

  Future<void> _saveCounselling() async {
    if (_otType == null) {
      showAppSnackBar(context, 'Surgery type is required', isError: true);
      return;
    }
    if (_bloodReportsVerified == null || _bloodReportsNormal == null) {
      showAppSnackBar(
        context,
        'Blood reports verified/normal must be answered',
        isError: true,
      );
      return;
    }
    setState(() => _savingCounselling = true);
    try {
      final counselling = OtCounsellingItem(
        diagnosis: _diagnosisCtrl.text.trim().isEmpty
            ? null
            : _diagnosisCtrl.text.trim(),
        eye: _eye,
        otType: _otType,
        mediclaim: _mediclaim,
        lensCategory: _lensCategory,
        lensCompany: _lensCompanyCtrl.text.trim().isEmpty
            ? null
            : _lensCompanyCtrl.text.trim(),
        lensModel: _lensModelCtrl.text.trim().isEmpty
            ? null
            : _lensModelCtrl.text.trim(),
        lensType: _lensType,
        estimatedPower: _estimatedPower,
        lensCost: double.tryParse(_lensCostCtrl.text.trim()),
        packageName: _packageName,
        roomCategory: _roomCategory,
        otCharges: double.tryParse(_otChargesCtrl.text.trim()) ?? 0,
        surgeonCharges: double.tryParse(_surgeonChargesCtrl.text.trim()) ?? 0,
        nursingCharges: double.tryParse(_nursingChargesCtrl.text.trim()) ?? 0,
        consumablesCharges:
            double.tryParse(_consumablesChargesCtrl.text.trim()) ?? 0,
        paymentMode: _paymentMode,
        bloodReportsVerified: _bloodReportsVerified!,
        bloodReportsNormal: _bloodReportsNormal!,
        notes: null,
      );
      final totalEstimate = await OtCounsellorService.instance.storeCounselling(
        widget.bookingId,
        counselling,
      );
      if (!mounted) return;
      setState(() => _savingCounselling = false);
      showAppSnackBar(
        context,
        'Counselling saved — estimate ${currentCurrencySymbol()}${totalEstimate.toStringAsFixed(0)}',
        isSuccess: true,
      );
    } catch (e) {
      if (mounted) {
        setState(() => _savingCounselling = false);
        showAppSnackBar(
          context,
          e.toString().replaceFirst('Exception: ', ''),
          isError: true,
        );
      }
    }
  }

  Future<void> _saveConsent() async {
    if (!_consentGiven) {
      showAppSnackBar(
        context,
        'Consent must be given to proceed',
        isError: true,
      );
      return;
    }
    setState(() => _savingConsent = true);
    try {
      final patientSig = await exportSignatureDataUri(_patientSigCtrl);
      final guardianSig = await exportSignatureDataUri(_guardianSigCtrl);
      await OtCounsellorService.instance.storeConsent(
        widget.bookingId,
        consentGiven: _consentGiven,
        patientSignatureDataUri: patientSig,
        guardianSignatureDataUri: guardianSig,
        witnessName: _witnessCtrl.text.trim().isEmpty
            ? null
            : _witnessCtrl.text.trim(),
      );
      if (!mounted) return;
      setState(() {
        _savingConsent = false;
        _consentSaved = true;
      });
      showAppSnackBar(context, 'Consent saved', isSuccess: true);
    } catch (e) {
      if (mounted) {
        setState(() => _savingConsent = false);
        showAppSnackBar(
          context,
          e.toString().replaceFirst('Exception: ', ''),
          isError: true,
        );
      }
    }
  }

  Future<void> _sendToBilling() async {
    setState(() => _sendingToBilling = true);
    try {
      await OtCounsellorService.instance.sendToBilling(widget.bookingId);
      if (!mounted) return;
      showAppSnackBar(context, 'Sent to Billing', isSuccess: true);
      Navigator.of(context).pop();
    } catch (e) {
      if (mounted) {
        setState(() => _sendingToBilling = false);
        showAppSnackBar(
          context,
          e.toString().replaceFirst('Exception: ', ''),
          isError: true,
        );
      }
    }
  }

  /// Yes/No pair for a field that's `required boolean` on the backend
  /// (blood_reports_verified/normal, web pull 2026-08-07 — was an optional
  /// checkbox defaulting to false before). `null` means "not yet answered",
  /// checked in `_saveCounselling()` before submit.
  Widget _requiredYesNo(
    String label,
    bool? value,
    ValueChanged<bool> onChanged,
  ) {
    return Row(
      children: [
        Expanded(
          child: Text(
            label,
            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
          ),
        ),
        ChoiceChip(
          label: const Text('Yes'),
          selected: value == true,
          onSelected: (_) => onChanged(true),
        ),
        const SizedBox(width: 8),
        ChoiceChip(
          label: const Text('No'),
          selected: value == false,
          onSelected: (_) => onChanged(false),
        ),
      ],
    );
  }

  InputDecoration _deco(String label, {String? suffix}) => InputDecoration(
    labelText: label,
    suffixText: suffix,
    filled: true,
    fillColor: const Color(0xFFF0F6FB),
    border: OutlineInputBorder(
      borderRadius: BorderRadius.circular(AppRadius.md),
      borderSide: BorderSide.none,
    ),
    focusedBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(AppRadius.md),
      borderSide: const BorderSide(color: AppColors.teal, width: 1.5),
    ),
  );

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
                : _loadError != null
                ? AppErrorState(message: _loadError!, onRetry: _load)
                : _buildBody(),
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
        child: Padding(
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
                  Icons.support_agent_rounded,
                  color: Colors.white,
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Counselling',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    Text(
                      widget.patientName,
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.75),
                        fontSize: 11,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  // Web parity: one scrollable page, three independent sections/cards
  // (Diagnosis/Lens/Package, Informed Consent, Ready for Billing) — no
  // forced step order, each has its own save action.
  Widget _buildBody() {
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 20),
      children: [
        _buildCounsellingCard(),
        const SizedBox(height: 20),
        if (_canConsent) ...[_buildConsentCard(), const SizedBox(height: 20)],
        _buildBillingCard(),
      ],
    );
  }

  // Client request (2026-10-04): web's `ot_counsellor/form.blade.php` has a
  // real two-tier heading structure — an `<h5>` CARD title ("Diagnosis, Lens
  // & Package") in its own `.ot-card-header` strip, then inside the body an
  // *unlabeled* first group (Diagnosis/Eye/Surgery Type) followed by two
  // `<h6>` sub-section titles ("Lens Selection", "Package & Cost Estimate"),
  // each preceded by an `<hr>`. This screen had flattened that into three
  // identically-styled `AppSectionHeader`s at the same level, which made
  // "Diagnosis, Lens & Package" read as a label for just the first group —
  // promising lens/package content that group never actually has. Restoring
  // the card-title/sub-section split (plus the `<hr>` web draws between
  // groups, missing here before) fixes the mismatched label and gives this
  // long scrolling card actual visual structure instead of one continuous
  // run of fields.
  Widget _buildCounsellingCard() {
    final detail = _detail!;
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.08)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
            child: Row(
              children: [
                Icon(
                  Icons.assignment_outlined,
                  size: 19,
                  color: AppColors.primary,
                ),
                const SizedBox(width: 9),
                const Expanded(
                  child: Text(
                    'Diagnosis, Lens & Package',
                    style: TextStyle(
                      fontSize: 15.5,
                      fontWeight: FontWeight.w800,
                      color: AppColors.darkNavy,
                    ),
                  ),
                ),
              ],
            ),
          ),
          Divider(height: 1, color: AppColors.primaryA08),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                TextFormField(
                  controller: _diagnosisCtrl,
                  maxLines: 2,
                  decoration: _deco('Diagnosis'),
                ),
                const SizedBox(height: 12),
                EyeChoiceSelector(
                  value: _eye,
                  onChanged: (e) => setState(() => _eye = e),
                ),
                const SizedBox(height: 14),
                DropdownButtonFormField<String>(
                  initialValue: _otType,
                  isExpanded: true,
                  decoration: _deco('Surgery Type *'),
                  items: _surgeryTypeOptions
                      .map(
                        (t) => DropdownMenuItem(
                          value: t.surgeryName,
                          child: Text(
                            t.surgeryName,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      )
                      .toList(),
                  onChanged: (v) => setState(() => _otType = v),
                ),
                const SizedBox(height: 16),
                Divider(height: 1, color: AppColors.primaryA08),
                const SizedBox(height: 16),

                const AppSectionHeader(
                  title: 'Lens Selection',
                  icon: Icons.remove_red_eye_outlined,
                ),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 8,
                  children: ['standard', 'premium']
                      .map(
                        (c) => ChoiceChip(
                          label: Text(c[0].toUpperCase() + c.substring(1)),
                          selected: _lensCategory == c,
                          onSelected: (_) => setState(() => _lensCategory = c),
                        ),
                      )
                      .toList(),
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _lensCompanyCtrl,
                        decoration: _deco('Lens Company'),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextFormField(
                        controller: _lensModelCtrl,
                        decoration: _deco('Lens Model'),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                DropdownButtonFormField<String>(
                  initialValue: _lensType,
                  isExpanded: true,
                  decoration: _deco('Lens Type'),
                  items: kOtLensTypes
                      .map((t) => DropdownMenuItem(value: t, child: Text(t)))
                      .toList(),
                  onChanged: (v) => setState(() => _lensType = v),
                ),
                const SizedBox(height: 10),
                _buildLensPowerDropdown(detail),
                const SizedBox(height: 16),

                // Web parity: always visible to anyone who can open this page —
                // web has no separate permission gate for this section
                // (ot_package_set is defined but never enforced anywhere for this
                // screen; ot_counselling_fill alone is what gates page access).
                const AppSectionHeader(
                  title: 'Package & Cost Estimate',
                  icon: Icons.card_giftcard_rounded,
                ),
                const SizedBox(height: 10),
                DropdownButtonFormField<int>(
                  initialValue: _selectedPackageId,
                  isExpanded: true,
                  decoration: _deco('OT Package'),
                  items: detail.packageCostOptions
                      .map(
                        (p) => DropdownMenuItem(
                          value: p.id,
                          child: Text(
                            '${p.packageName} · ${p.roomCategory[0].toUpperCase()}${p.roomCategory.substring(1)}',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      )
                      .toList(),
                  onChanged: (v) {
                    if (v == null) return;
                    _selectPackage(
                      detail.packageCostOptions.firstWhere((p) => p.id == v),
                    );
                  },
                ),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 8,
                  children: ['general', 'private']
                      .map(
                        (r) => ChoiceChip(
                          label: Text(r[0].toUpperCase() + r.substring(1)),
                          selected: _roomCategory == r,
                          onSelected: (_) => setState(() => _roomCategory = r),
                        ),
                      )
                      .toList(),
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _otChargesCtrl,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        inputFormatters: _genFmt,
                        decoration: _deco(
                          'OT Charges',
                          suffix: currentCurrencySymbol(),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextFormField(
                        controller: _surgeonChargesCtrl,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        inputFormatters: _genFmt,
                        decoration: _deco(
                          'Surgeon Charges',
                          suffix: currentCurrencySymbol(),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _nursingChargesCtrl,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        inputFormatters: _genFmt,
                        decoration: _deco(
                          'Nursing Charges',
                          suffix: currentCurrencySymbol(),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextFormField(
                        controller: _consumablesChargesCtrl,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        inputFormatters: _genFmt,
                        decoration: _deco(
                          'Consumables',
                          suffix: currentCurrencySymbol(),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                // Web parity: Lens Cost lives in the Package/Cost section (next to
                // Nursing/Consumables), not in Lens Selection — it's decoupled from
                // package selection (manually typed, folded into Total Estimate).
                TextFormField(
                  controller: _lensCostCtrl,
                  keyboardType: const TextInputType.numberWithOptions(
                    decimal: true,
                  ),
                  inputFormatters: _genFmt,
                  decoration: _deco(
                    'Lens Cost',
                    suffix: currentCurrencySymbol(),
                  ),
                ),
                const SizedBox(height: 12),
                // Matches web exactly: Total Estimate = ot_charges + surgeon_charges
                // + nursing_charges + consumables_charges + lens_cost (web pull
                // 2026-08-07 changed this from lens_cost-only). See
                // WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §7.
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 12,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF0F6FB),
                    borderRadius: BorderRadius.circular(AppRadius.md),
                    border: Border.all(
                      color: AppColors.primary.withValues(alpha: 0.18),
                    ),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Total Estimate',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      Text(
                        '${currentCurrencySymbol()}${_totalEstimate.toStringAsFixed(2)}',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          color: AppColors.primary,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                // Web parity: Mediclaim lives here — right after Total Estimate,
                // before Payment Mode — not up near Surgery Type.
                SwitchListTile(
                  value: _mediclaim,
                  onChanged: (v) => setState(() => _mediclaim = v),
                  title: const Text(
                    'Mediclaim',
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                  ),
                  contentPadding: EdgeInsets.zero,
                  activeThumbColor: AppColors.teal,
                ),
                const SizedBox(height: 6),
                // Web pull 2026-10-13 (same one that dropped the counselling
                // form's old mediclaim↔payment_mode JS auto-sync): Mediclaim
                // and Payment Mode are independent fields now — a mediclaim
                // booking can still be paid cash/online, and a non-mediclaim
                // one can still route through "Mediclaim" as its payment
                // mode. Previously this hid Cash/Online entirely whenever
                // Mediclaim was Yes, so the accountant could never actually
                // record how a mediclaim patient really paid.
                Wrap(
                  spacing: 8,
                  children: ['cash', 'online', 'mediclaim']
                      .map(
                        (m) => ChoiceChip(
                          label: Text(m[0].toUpperCase() + m.substring(1)),
                          selected: _paymentMode == m,
                          onSelected: (_) => setState(() => _paymentMode = m),
                        ),
                      )
                      .toList(),
                ),
                const SizedBox(height: 10),
                _requiredYesNo(
                  'Blood reports verified',
                  _bloodReportsVerified,
                  (v) => setState(() => _bloodReportsVerified = v),
                ),
                const SizedBox(height: 10),
                _requiredYesNo(
                  'Blood reports normal',
                  _bloodReportsNormal,
                  (v) => setState(() => _bloodReportsNormal = v),
                ),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _savingCounselling ? null : _saveCounselling,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.teal,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(AppRadius.md),
                      ),
                    ),
                    child: _savingCounselling
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              color: Colors.white,
                              strokeWidth: 2,
                            ),
                          )
                        : const Text(
                            'Save Counselling',
                            style: TextStyle(fontWeight: FontWeight.w700),
                          ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildConsentCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.08)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const AppSectionHeader(
            title: 'Informed Consent',
            icon: Icons.draw_rounded,
          ),
          const SizedBox(height: 10),
          SwitchListTile(
            value: _consentGiven,
            onChanged: (v) => setState(() => _consentGiven = v),
            title: const Text(
              'Patient / guardian has given consent',
              style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
            ),
            contentPadding: EdgeInsets.zero,
            activeThumbColor: AppColors.teal,
          ),
          const SizedBox(height: 10),
          // Web parity: Witness Name comes right after Consent Given, before
          // the signature pads.
          TextFormField(
            controller: _witnessCtrl,
            decoration: _deco('Witness Name'),
          ),
          const SizedBox(height: 16),
          SignaturePadField(
            label: 'Patient Signature',
            controller: _patientSigCtrl,
          ),
          const SizedBox(height: 16),
          SignaturePadField(
            label: 'Guardian Signature (optional)',
            controller: _guardianSigCtrl,
          ),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: _savingConsent ? null : _saveConsent,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                ),
              ),
              child: _savingConsent
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                        color: Colors.white,
                        strokeWidth: 2,
                      ),
                    )
                  : const Text(
                      'Save Consent',
                      style: TextStyle(fontWeight: FontWeight.w700),
                    ),
            ),
          ),
        ],
      ),
    );
  }

  // Matches web's separate "Ready for Billing?" card exactly — its own
  // section with a status subtitle, not just a button glued onto another
  // form's action row.
  Widget _buildBillingCard() {
    final alreadySent = _otStatus == OtStatus.counselled;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.08)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Ready for Billing?',
            style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 4),
          const Text(
            'Requires counselling saved and consent given.',
            style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
          ),
          const SizedBox(height: 14),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: (alreadySent || !_consentSaved || _sendingToBilling)
                  ? null
                  : _sendToBilling,
              icon: alreadySent
                  ? const Icon(Icons.check_circle_outline_rounded, size: 18)
                  : const Icon(Icons.send_rounded, size: 18),
              label: _sendingToBilling
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                        color: Colors.white,
                        strokeWidth: 2,
                      ),
                    )
                  : Text(
                      alreadySent
                          ? 'Already Sent to Billing'
                          : 'Send to Billing',
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.green,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
