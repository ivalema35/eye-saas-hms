import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/permissions.dart';
import '../models/medicine_models.dart';
import '../utils/date_format.dart';
import '../models/ot_assistant_models.dart';
import '../services/ot_assistant_service.dart';
import '../services/permission_service.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_full_screen_error.dart';
import '../widgets/app_text_field.dart';
import '../widgets/skeleton.dart';
import '../utils/currency_format.dart';
import '../utils/app_date_picker.dart';

/// One `_medicineRows` line — medicine is a dropdown pick (not free text, see
/// OT_SURGERY_RECORD_WEB_PARITY_FIX_PLAN.md G5) so it always resolves against
/// `Medicine::exists` server-side; dose stays free text like web.
class _MedRow {
  String? medicine;
  final TextEditingController doseCtrl = TextEditingController();
  _MedRow({this.medicine});
  void dispose() => doseCtrl.dispose();
}

/// Web parity rebuild (2026-08-07) — see OT_SURGERY_RECORD_WEB_PARITY_FIX_PLAN.md.
/// **One combined verify + record submit** — the backend auto-verifies the
/// pre-surgery checklist atomically with the surgery record (no separate
/// "verify" step and, since this rebuild, no checklist UI either — matches
/// web, which removed the checklist from its own form and hardcodes it
/// server-side).
///
/// No lens-recording entry point here — web's lens-record route exists but
/// isn't linked from any of its own UI (not the dashboard queue, not this
/// form), so neither app links to it either (removed 2026-08-07, previously
/// an app-bar action).
class OtSurgeryRecordScreen extends StatefulWidget {
  final int bookingId;
  final String patientName;

  const OtSurgeryRecordScreen({
    super.key,
    required this.bookingId,
    required this.patientName,
  });

  @override
  State<OtSurgeryRecordScreen> createState() => _OtSurgeryRecordScreenState();
}

class _OtSurgeryRecordScreenState extends State<OtSurgeryRecordScreen> {
  bool _loading = true;
  String? _loadError;
  OtSurgeryFormData? _formData;
  bool _saving = false;

  bool get _canLensRecord => PermissionService.instance.can(Perm.otLensRecord);
  bool get _canMedsTakehome =>
      PermissionService.instance.can(Perm.otMedsTakehome);

  final _otRoomCtrl = TextEditingController();
  final _complicationNotesCtrl = TextEditingController();
  final _bloodLossCtrl = TextEditingController();
  final _lensCompanyCtrl = TextEditingController();
  final _lensModelCtrl = TextEditingController();
  final _estimatedPowerCtrl = TextEditingController();
  final _lensCostCtrl = TextEditingController();

  String? _surgeryName;
  DateTime _surgeryDate = DateTime.now();
  // Eye Operated is locked to the booking's own eye (Recommend Surgery
  // selection) — matches web's `$lockedEye`, not a user-editable picker.
  String _lockedEye = 'RE';
  DateTime? _startDateTime;
  DateTime? _endDateTime;
  String _complicationStatus = 'none';
  String? _lensCategory;
  String? _lensType;
  String? _lensImplantation;
  int? _medicineGroupId;
  final List<_MedRow> _medicineRows = [_MedRow()];

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _otRoomCtrl.dispose();
    _complicationNotesCtrl.dispose();
    _bloodLossCtrl.dispose();
    _lensCompanyCtrl.dispose();
    _lensModelCtrl.dispose();
    _estimatedPowerCtrl.dispose();
    _lensCostCtrl.dispose();
    for (final r in _medicineRows) {
      r.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _loadError = null;
    });
    try {
      final formData = await OtAssistantService.instance.fetchSurgeryFormData(
        widget.bookingId,
      );
      if (!mounted) return;
      setState(() {
        _formData = formData;

        final bookingEye = formData.booking.eye;
        _lockedEye =
            (bookingEye == 'RE' || bookingEye == 'LE' || bookingEye == 'Both')
            ? bookingEye!
            : 'RE';

        final apptSurgeryDate = formData.booking.surgeryDate;
        _surgeryDate = apptSurgeryDate != null
            ? (parseApiPlainDate(apptSurgeryDate) ?? DateTime.now())
            : DateTime.now();

        final otType = formData.booking.otType;
        if (otType != null &&
            formData.surgeryTypes.any((t) => t.surgeryName == otType)) {
          _surgeryName = otType;
        }

        final c = formData.counselling;
        _lensCategory = c?.lensCategory;
        _lensCompanyCtrl.text = c?.lensCompany ?? '';
        _lensModelCtrl.text = c?.lensModel ?? '';
        _lensType = c?.lensType;
        _estimatedPowerCtrl.text = c?.estimatedPower?.toString() ?? '';
        _lensCostCtrl.text = c?.lensCost != null
            ? c!.lensCost!.toStringAsFixed(2)
            : '';
        _lensImplantation = c?.lensImplantation == null
            ? null
            : (c!.lensImplantation! ? 'yes' : 'no');

        _loading = false;
      });
    } catch (e) {
      if (mounted)
        setState(() {
          _loadError = e.toString().replaceFirst('Exception: ', '');
          _loading = false;
        });
    }
  }

  Future<void> _pickSurgeryDate() async {
    final date = await showAppDatePicker(
      context: context,
      initialDate: _surgeryDate,
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
    );
    if (date != null) setState(() => _surgeryDate = date);
  }

  Future<void> _pickDateTime({required bool isStart}) async {
    final current = (isStart ? _startDateTime : _endDateTime) ?? DateTime.now();
    final date = await showAppDatePicker(
      context: context,
      initialDate: current,
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
    );
    if (date == null || !mounted) return;
    final time = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.fromDateTime(current),
    );
    if (time == null) return;
    final combined = DateTime(
      date.year,
      date.month,
      date.day,
      time.hour,
      time.minute,
    );
    setState(() {
      if (isStart)
        _startDateTime = combined;
      else
        _endDateTime = combined;
    });
  }

  void _addMedicineRow() => setState(() => _medicineRows.add(_MedRow()));

  /// Removing the last remaining row clears it instead of deleting it —
  /// matches web's JS behaviour exactly (a row is always present).
  void _removeMedicineRow(int i) {
    if (_medicineRows.length == 1) {
      setState(() {
        _medicineRows[i].medicine = null;
        _medicineRows[i].doseCtrl.clear();
      });
      return;
    }
    setState(() {
      _medicineRows[i].dispose();
      _medicineRows.removeAt(i);
    });
  }

  /// Wipes and rebuilds the medicine row list from the selected group's
  /// items — matches web's quick-fill behaviour, incl. composing dose from
  /// `frequency + duration` (not dosage text).
  void _applyMedicineGroup(MedGroup group) {
    setState(() {
      for (final r in _medicineRows) {
        r.dispose();
      }
      _medicineRows.clear();
      _medicineGroupId = group.id;
      for (final item in group.items) {
        if (item.medicineName == null || item.medicineName!.isEmpty) continue;
        _medicineRows.add(
          _MedRow(medicine: item.medicineName)
            ..doseCtrl.text = item.quickFillDose,
        );
      }
      if (_medicineRows.isEmpty) _medicineRows.add(_MedRow());
    });
  }

  String _fmtDate(DateTime d) => toIsoDate(d);
  String _fmtDateTime(DateTime d) =>
      '${_fmtDate(d)} ${d.hour.toString().padLeft(2, '0')}:${d.minute.toString().padLeft(2, '0')}:00';
  String _fmtDisplayDate(DateTime d) =>
      '${d.day.toString().padLeft(2, '0')}/${d.month.toString().padLeft(2, '0')}/${d.year}';
  String _fmtDisplayDateTime(DateTime? d) => d == null
      ? 'Select'
      : '${_fmtDisplayDate(d)} ${TimeOfDay.fromDateTime(d).format(context)}';

  Future<void> _save() async {
    if (_surgeryName == null || _surgeryName!.isEmpty) {
      showAppSnackBar(context, 'Surgery name is required', isError: true);
      return;
    }
    if (_complicationStatus != 'none' &&
        _complicationNotesCtrl.text.trim().isEmpty) {
      showAppSnackBar(
        context,
        'Complication notes are required when a complication is recorded',
        isError: true,
      );
      return;
    }
    if (_startDateTime != null &&
        _endDateTime != null &&
        !_endDateTime!.isAfter(_startDateTime!)) {
      showAppSnackBar(
        context,
        'End time must be after start time',
        isError: true,
      );
      return;
    }
    setState(() => _saving = true);
    try {
      await OtAssistantService.instance.storeSurgery(
        widget.bookingId,
        surgeryDate: _fmtDate(_surgeryDate),
        surgeryName: _surgeryName!,
        otRoom: _otRoomCtrl.text.trim().isEmpty
            ? null
            : _otRoomCtrl.text.trim(),
        eyeOperated: _lockedEye,
        startTime: _startDateTime == null
            ? null
            : _fmtDateTime(_startDateTime!),
        endTime: _endDateTime == null ? null : _fmtDateTime(_endDateTime!),
        complicationStatus: _complicationStatus,
        complicationNotes: _complicationNotesCtrl.text.trim().isEmpty
            ? null
            : _complicationNotesCtrl.text.trim(),
        bloodLoss: _bloodLossCtrl.text.trim().isEmpty
            ? null
            : _bloodLossCtrl.text.trim(),
        medicineGroupId: _medicineGroupId,
        otMedicines: _medicineRows
            .where((r) => (r.medicine ?? '').isNotEmpty)
            .map(
              (r) => OtSurgeryMedicineLine(
                medicine: r.medicine!,
                dose: r.doseCtrl.text.trim().isEmpty
                    ? null
                    : r.doseCtrl.text.trim(),
              ),
            )
            .toList(),
        lensCategory: _lensCategory,
        lensCompany: _lensCompanyCtrl.text.trim().isEmpty
            ? null
            : _lensCompanyCtrl.text.trim(),
        lensModel: _lensModelCtrl.text.trim().isEmpty
            ? null
            : _lensModelCtrl.text.trim(),
        lensType: _lensType,
        estimatedPower: double.tryParse(_estimatedPowerCtrl.text.trim()),
        lensCost: double.tryParse(_lensCostCtrl.text.trim()),
        lensImplantation: _lensImplantation,
      );
      if (!mounted) return;
      showAppSnackBar(
        context,
        'Surgery recorded successfully.',
        isSuccess: true,
      );
      Navigator.pop(context);
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

  InputDecoration _deco(String label, {String? hint}) => InputDecoration(
    labelText: label,
    hintText: hint,
    filled: true,
    fillColor: const Color(0xFFF0F6FB),
    border: OutlineInputBorder(
      borderRadius: BorderRadius.circular(AppRadius.md),
      borderSide: BorderSide.none,
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
            // Fetched by bookingId — a 404 here is a genuine "this record
            // is gone" moment (the booking was deleted/invalid), not an
            // ordinary transient load failure, so it gets the full severe-
            // moment treatment instead of the small inline AppErrorState.
            child: _loading
                ? const AppSkeletonList()
                : _loadError != null
                ? AppFullScreenError(message: _loadError!, onRetry: _load)
                : _buildForm(),
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
                  Icons.medical_services_rounded,
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
                      'Surgery Recording Form',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    Text(
                      '${widget.patientName} · Complete surgery details and ward medicines',
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.75),
                        fontSize: 11,
                      ),
                      overflow: TextOverflow.ellipsis,
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

  // Client request (2026-10-04): redesigned onto the same `AppFormCard`/
  // `AppSegmentedChips` language as the other forms in this pass. While
  // rebuilding, cross-checked field order against `ot/assistant/surgery.
  // blade.php` and found Blood Loss and Complication Notes were swapped —
  // web's row order is Complication Status → Blood Loss → Complication
  // Notes, this screen had Notes before Blood Loss. Fixed below. Also
  // converted every other read-only field in this screen (Patient Name,
  // Phone, Package, Mediclaim) onto `AppReadOnlyField` — Eye Operated
  // already used it (from the earlier read-only-field fix pass) while these
  // four still used the older plain `_readOnlyRow` line, so the same screen
  // was inconsistently showing two different "this isn't editable" styles.
  Widget _buildForm() {
    final formData = _formData!;
    return Column(
      children: [
        Expanded(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 20),
            children: [
              AppFormCard(
                icon: Icons.badge_outlined,
                title: 'A. Patient & Booking Details',
                children: [
                  AppReadOnlyField(
                    label: 'Patient Name',
                    value: formData.booking.patient?.fullName ?? '—',
                    icon: Icons.person_outline_rounded,
                  ),
                  AppReadOnlyField(
                    label: 'Phone',
                    value: formData.booking.patient?.contactNo ?? '—',
                    icon: Icons.call_outlined,
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      InkWell(
                        onTap: _pickSurgeryDate,
                        child: InputDecorator(
                          decoration: _deco('OT Date *'),
                          child: Text(_fmtDisplayDate(_surgeryDate)),
                        ),
                      ),
                    ],
                  ),
                  AppReadOnlyField(
                    label: 'Package',
                    value:
                        '${currentCurrencySymbol()}${(formData.counselling?.packageAmount ?? formData.booking.packageAmount ?? 0).toStringAsFixed(2)}',
                    icon: Icons.currency_rupee_rounded,
                  ),
                  AppReadOnlyField(
                    label: 'Mediclaim',
                    value:
                        (formData.counselling?.mediclaim ??
                            formData.booking.hasMediclaim ??
                            false)
                        ? 'YES'
                        : 'NO',
                    icon: Icons.health_and_safety_outlined,
                  ),
                ],
              ),
              AppFormCard(
                icon: Icons.local_hospital_outlined,
                title: 'B. Surgery Details',
                children: [
                  DropdownButtonFormField<String>(
                    initialValue: _surgeryName,
                    isExpanded: true,
                    decoration: _deco(
                      'Surgery Name *',
                      hint: 'Select surgery...',
                    ),
                    items: formData.surgeryTypes
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
                    onChanged: (v) => setState(() => _surgeryName = v),
                  ),
                  // Locked to the booking's own eye (Recommend Surgery
                  // selection, see `_lockedEye`'s doc comment) — never
                  // user-editable here, so it shouldn't look like the
                  // editable dropdown right above it.
                  AppReadOnlyField(
                    label: 'Eye Operated',
                    value: _lockedEye,
                    icon: Icons.remove_red_eye_outlined,
                  ),
                  TextFormField(
                    controller: _otRoomCtrl,
                    decoration: _deco('OT Room', hint: 'e.g. OT-1'),
                  ),
                  Row(
                    children: [
                      Expanded(
                        child: InkWell(
                          onTap: () => _pickDateTime(isStart: true),
                          child: InputDecorator(
                            decoration: _deco('Start Time'),
                            child: Text(_fmtDisplayDateTime(_startDateTime)),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: InkWell(
                          onTap: () => _pickDateTime(isStart: false),
                          child: InputDecorator(
                            decoration: _deco('End Time'),
                            child: Text(_fmtDisplayDateTime(_endDateTime)),
                          ),
                        ),
                      ),
                    ],
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Complication Status *',
                        style: TextStyle(
                          fontSize: 12.5,
                          fontWeight: FontWeight.w600,
                          color: AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 7),
                      AppSegmentedChips(
                        values: const ['none', 'minor', 'major'],
                        labels: const {
                          'none': 'None',
                          'minor': 'Minor',
                          'major': 'Major',
                        },
                        selected: _complicationStatus,
                        onChanged: (v) =>
                            setState(() => _complicationStatus = v),
                      ),
                    ],
                  ),
                  // Web order: Complication Status → Blood Loss →
                  // Complication Notes (was Notes-then-Blood-Loss here).
                  TextFormField(
                    controller: _bloodLossCtrl,
                    decoration: _deco('Blood Loss', hint: 'e.g. Minimal, 50ml'),
                  ),
                  TextFormField(
                    controller: _complicationNotesCtrl,
                    maxLines: 2,
                    decoration: _deco(
                      'Complication Notes',
                      hint:
                          'Only required if complication status is minor/major',
                    ),
                  ),
                ],
              ),
              if (_canLensRecord)
                AppFormCard(
                  icon: Icons.remove_red_eye_outlined,
                  title: 'C. Lens Selection',
                  children: [
                    const Text(
                      'Auto-filled from Counsellor form — confirm or adjust if needed before saving surgery.',
                      style: TextStyle(
                        fontSize: 11,
                        color: AppColors.textSecondary,
                      ),
                    ),
                    // Category + Type (both short dropdowns) and Company +
                    // Model (both short text fields) paired side-by-side —
                    // was 4 full-width rows stacked one per line, needlessly
                    // tall for how little each one holds.
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          child: DropdownButtonFormField<String>(
                            initialValue: _lensCategory,
                            isExpanded: true,
                            decoration: _deco(
                              'Lens Category',
                              hint: 'Select...',
                            ),
                            items: const [
                              DropdownMenuItem(
                                value: 'standard',
                                child: Text('Standard'),
                              ),
                              DropdownMenuItem(
                                value: 'premium',
                                child: Text('Premium'),
                              ),
                            ],
                            onChanged: (v) => setState(() => _lensCategory = v),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: DropdownButtonFormField<String>(
                            initialValue: _lensType,
                            isExpanded: true,
                            decoration: _deco('Lens Type', hint: 'Select...'),
                            items: formData.lensTypes
                                .map(
                                  (t) =>
                                      DropdownMenuItem(value: t, child: Text(t)),
                                )
                                .toList(),
                            onChanged: (v) => setState(() => _lensType = v),
                          ),
                        ),
                      ],
                    ),
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
                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            controller: _estimatedPowerCtrl,
                            keyboardType: const TextInputType.numberWithOptions(
                              decimal: true,
                              signed: true,
                            ),
                            decoration: _deco('Estimated Power'),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: TextFormField(
                            controller: _lensCostCtrl,
                            keyboardType: const TextInputType.numberWithOptions(
                              decimal: true,
                            ),
                            decoration: _deco(
                              'Lens Cost (${currentCurrencySymbol()})',
                              hint: 'Enter lens cost',
                            ),
                          ),
                        ),
                      ],
                    ),
                    // Compact inline Yes/No — was a full-width AppSegmentedChips
                    // pair (same widget the 3-way Complication Status uses),
                    // which stretched each option to half the card's width for
                    // just one word. A plain label + two small chips reads just
                    // as clearly in a fraction of the height.
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            'Lens Implantation',
                            style: TextStyle(
                              fontSize: 12.5,
                              fontWeight: FontWeight.w600,
                              color: AppColors.textSecondary,
                            ),
                          ),
                        ),
                        ChoiceChip(
                          label: const Text('Yes'),
                          selected: _lensImplantation == 'yes',
                          onSelected: (_) =>
                              setState(() => _lensImplantation = 'yes'),
                        ),
                        const SizedBox(width: 8),
                        ChoiceChip(
                          label: const Text('No'),
                          selected: _lensImplantation == 'no',
                          onSelected: (_) =>
                              setState(() => _lensImplantation = 'no'),
                        ),
                      ],
                    ),
                  ],
                ),
              if (_canMedsTakehome)
                AppFormCard(
                  icon: Icons.medication_outlined,
                  title: 'D. In-Ward Medicines',
                  trailing: TextButton.icon(
                    onPressed: _addMedicineRow,
                    icon: const Icon(Icons.add_rounded, size: 14),
                    label: const Text(
                      'Add Medicine',
                      style: TextStyle(fontSize: 12),
                    ),
                  ),
                  children: [
                    if (formData.medicineGroups.isNotEmpty)
                      DropdownButtonFormField<int>(
                        initialValue: _medicineGroupId,
                        isExpanded: true,
                        decoration: _deco(
                          'Quick-fill from OT Medicine Group',
                          hint: 'Select group (optional)...',
                        ),
                        items: formData.medicineGroups
                            .map(
                              (g) => DropdownMenuItem(
                                value: g.id,
                                child: Text(
                                  g.name,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            )
                            .toList(),
                        onChanged: (v) {
                          if (v == null) return;
                          _applyMedicineGroup(
                            formData.medicineGroups.firstWhere(
                              (g) => g.id == v,
                            ),
                          );
                        },
                      ),
                    for (var i = 0; i < _medicineRows.length; i++)
                      Row(
                        children: [
                          Expanded(
                            flex: 2,
                            child: DropdownButtonFormField<String>(
                              initialValue: _medicineRows[i].medicine,
                              isExpanded: true,
                              decoration: _deco(
                                'Medicine',
                                hint: 'Select medicine...',
                              ),
                              items: formData.medicines
                                  .map(
                                    (m) => DropdownMenuItem(
                                      value: m,
                                      child: Text(
                                        m,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                  )
                                  .toList(),
                              onChanged: (v) =>
                                  setState(() => _medicineRows[i].medicine = v),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: TextFormField(
                              controller: _medicineRows[i].doseCtrl,
                              decoration: _deco('Dose / Frequency'),
                            ),
                          ),
                          IconButton(
                            icon: const Icon(
                              Icons.remove_circle_outline,
                              size: 20,
                              color: AppColors.red,
                            ),
                            onPressed: () => _removeMedicineRow(i),
                          ),
                        ],
                      ),
                  ],
                ),
            ],
          ),
        ),
        SafeArea(
          top: false,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
            child: Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: _saving ? null : () => Navigator.pop(context),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(AppRadius.md),
                      ),
                    ),
                    child: const Text(
                      'Cancel',
                      style: TextStyle(fontWeight: FontWeight.w700),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  flex: 2,
                  child: ElevatedButton(
                    onPressed: _saving ? null : _save,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(AppRadius.md),
                      ),
                    ),
                    child: _saving
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              color: Colors.white,
                              strokeWidth: 2,
                            ),
                          )
                        : const Text(
                            'Save Surgery',
                            style: TextStyle(fontWeight: FontWeight.w700),
                          ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}
