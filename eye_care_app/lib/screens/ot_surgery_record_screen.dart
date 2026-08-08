import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/medicine_models.dart';
import '../models/ot_assistant_models.dart';
import '../services/ot_assistant_service.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_section_header.dart';

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

  const OtSurgeryRecordScreen({super.key, required this.bookingId, required this.patientName});

  @override
  State<OtSurgeryRecordScreen> createState() => _OtSurgeryRecordScreenState();
}

class _OtSurgeryRecordScreenState extends State<OtSurgeryRecordScreen> {
  bool _loading = true;
  String? _loadError;
  OtSurgeryFormData? _formData;
  bool _saving = false;

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
    setState(() { _loading = true; _loadError = null; });
    try {
      final formData = await OtAssistantService.instance.fetchSurgeryFormData(widget.bookingId);
      if (!mounted) return;
      setState(() {
        _formData = formData;

        final bookingEye = formData.booking.eye;
        _lockedEye = (bookingEye == 'RE' || bookingEye == 'LE' || bookingEye == 'Both') ? bookingEye! : 'RE';

        final apptSurgeryDate = formData.booking.surgeryDate;
        _surgeryDate = apptSurgeryDate != null ? (DateTime.tryParse(apptSurgeryDate) ?? DateTime.now()) : DateTime.now();

        final otType = formData.booking.otType;
        if (otType != null && formData.surgeryTypes.any((t) => t.surgeryName == otType)) {
          _surgeryName = otType;
        }

        final c = formData.counselling;
        _lensCategory = c?.lensCategory;
        _lensCompanyCtrl.text = c?.lensCompany ?? '';
        _lensModelCtrl.text = c?.lensModel ?? '';
        _lensType = c?.lensType;
        _estimatedPowerCtrl.text = c?.estimatedPower?.toString() ?? '';
        _lensCostCtrl.text = c?.lensCost != null ? c!.lensCost!.toStringAsFixed(2) : '';

        _loading = false;
      });
    } catch (e) {
      if (mounted) setState(() { _loadError = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  Future<void> _pickSurgeryDate() async {
    final date = await showDatePicker(context: context, initialDate: _surgeryDate, firstDate: DateTime(2020), lastDate: DateTime(2100));
    if (date != null) setState(() => _surgeryDate = date);
  }

  Future<void> _pickDateTime({required bool isStart}) async {
    final current = (isStart ? _startDateTime : _endDateTime) ?? DateTime.now();
    final date = await showDatePicker(context: context, initialDate: current, firstDate: DateTime(2020), lastDate: DateTime(2100));
    if (date == null || !mounted) return;
    final time = await showTimePicker(context: context, initialTime: TimeOfDay.fromDateTime(current));
    if (time == null) return;
    final combined = DateTime(date.year, date.month, date.day, time.hour, time.minute);
    setState(() { if (isStart) _startDateTime = combined; else _endDateTime = combined; });
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
        _medicineRows.add(_MedRow(medicine: item.medicineName)..doseCtrl.text = item.quickFillDose);
      }
      if (_medicineRows.isEmpty) _medicineRows.add(_MedRow());
    });
  }

  String _fmtDate(DateTime d) => '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
  String _fmtDateTime(DateTime d) => '${_fmtDate(d)} ${d.hour.toString().padLeft(2, '0')}:${d.minute.toString().padLeft(2, '0')}:00';
  String _fmtDisplayDate(DateTime d) => '${d.day.toString().padLeft(2, '0')}/${d.month.toString().padLeft(2, '0')}/${d.year}';
  String _fmtDisplayDateTime(DateTime? d) => d == null ? 'Select' : '${_fmtDisplayDate(d)} ${TimeOfDay.fromDateTime(d).format(context)}';

  Future<void> _save() async {
    if (_surgeryName == null || _surgeryName!.isEmpty) {
      showAppSnackBar(context, 'Surgery name is required', isError: true);
      return;
    }
    if (_complicationStatus != 'none' && _complicationNotesCtrl.text.trim().isEmpty) {
      showAppSnackBar(context, 'Complication notes are required when a complication is recorded', isError: true);
      return;
    }
    if (_startDateTime != null && _endDateTime != null && !_endDateTime!.isAfter(_startDateTime!)) {
      showAppSnackBar(context, 'End time must be after start time', isError: true);
      return;
    }
    setState(() => _saving = true);
    try {
      await OtAssistantService.instance.storeSurgery(
        widget.bookingId,
        surgeryDate: _fmtDate(_surgeryDate),
        surgeryName: _surgeryName!,
        otRoom: _otRoomCtrl.text.trim().isEmpty ? null : _otRoomCtrl.text.trim(),
        eyeOperated: _lockedEye,
        startTime: _startDateTime == null ? null : _fmtDateTime(_startDateTime!),
        endTime: _endDateTime == null ? null : _fmtDateTime(_endDateTime!),
        complicationStatus: _complicationStatus,
        complicationNotes: _complicationNotesCtrl.text.trim().isEmpty ? null : _complicationNotesCtrl.text.trim(),
        bloodLoss: _bloodLossCtrl.text.trim().isEmpty ? null : _bloodLossCtrl.text.trim(),
        medicineGroupId: _medicineGroupId,
        otMedicines: _medicineRows
            .where((r) => (r.medicine ?? '').isNotEmpty)
            .map((r) => OtSurgeryMedicineLine(medicine: r.medicine!, dose: r.doseCtrl.text.trim().isEmpty ? null : r.doseCtrl.text.trim()))
            .toList(),
        lensCategory: _lensCategory,
        lensCompany: _lensCompanyCtrl.text.trim().isEmpty ? null : _lensCompanyCtrl.text.trim(),
        lensModel: _lensModelCtrl.text.trim().isEmpty ? null : _lensModelCtrl.text.trim(),
        lensType: _lensType,
        estimatedPower: double.tryParse(_estimatedPowerCtrl.text.trim()),
        lensCost: double.tryParse(_lensCostCtrl.text.trim()),
      );
      if (!mounted) return;
      showAppSnackBar(context, 'Surgery recorded successfully.', isSuccess: true);
      Navigator.pop(context);
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
    }
  }

  InputDecoration _deco(String label, {String? hint}) => InputDecoration(
        labelText: label,
        hintText: hint,
        filled: true,
        fillColor: const Color(0xFFF0F6FB),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide.none),
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
            Container(width: 40, height: 40, decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(10)), child: const Icon(Icons.medical_services_rounded, color: Colors.white, size: 20)),
            const SizedBox(width: 12),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                const Text('Surgery Recording Form', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800)),
                Text('${widget.patientName} · Complete surgery details and ward medicines', style: TextStyle(color: Colors.white.withValues(alpha: 0.75), fontSize: 11), overflow: TextOverflow.ellipsis),
              ]),
            ),
          ]),
        ),
      ),
    );
  }

  Widget _buildForm() {
    final formData = _formData!;
    return Column(children: [
      Expanded(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 20),
          children: [
            const AppSectionHeader(title: 'A. Patient & Booking Details', icon: Icons.badge_outlined),
            _readOnlyRow('Patient Name', formData.booking.patient?.fullName ?? '—'),
            _readOnlyRow('Phone', formData.booking.patient?.contactNo ?? '—'),
            const SizedBox(height: 8),
            InkWell(
              onTap: _pickSurgeryDate,
              child: InputDecorator(decoration: _deco('OT Date *'), child: Text(_fmtDisplayDate(_surgeryDate))),
            ),
            const SizedBox(height: 8),
            _readOnlyRow('Package', '₹${(formData.counselling?.packageAmount ?? formData.booking.packageAmount ?? 0).toStringAsFixed(2)}'),
            _readOnlyRow('Mediclaim', (formData.counselling?.mediclaim ?? formData.booking.hasMediclaim ?? false) ? 'YES' : 'NO'),
            const SizedBox(height: 16),

            const AppSectionHeader(title: 'B. Surgery Details', icon: Icons.local_hospital_outlined),
            DropdownButtonFormField<String>(
              initialValue: _surgeryName,
              isExpanded: true,
              decoration: _deco('Surgery Name *', hint: 'Select surgery...'),
              items: formData.surgeryTypes.map((t) => DropdownMenuItem(value: t.surgeryName, child: Text(t.surgeryName, overflow: TextOverflow.ellipsis))).toList(),
              onChanged: (v) => setState(() => _surgeryName = v),
            ),
            const SizedBox(height: 10),
            InputDecorator(
              decoration: _deco('Eye Operated'),
              child: Text(_lockedEye, style: const TextStyle(fontWeight: FontWeight.w700)),
            ),
            const SizedBox(height: 10),
            TextFormField(controller: _otRoomCtrl, decoration: _deco('OT Room', hint: 'e.g. OT-1')),
            const SizedBox(height: 10),
            Row(children: [
              Expanded(child: InkWell(onTap: () => _pickDateTime(isStart: true), child: InputDecorator(decoration: _deco('Start Time'), child: Text(_fmtDisplayDateTime(_startDateTime))))),
              const SizedBox(width: 10),
              Expanded(child: InkWell(onTap: () => _pickDateTime(isStart: false), child: InputDecorator(decoration: _deco('End Time'), child: Text(_fmtDisplayDateTime(_endDateTime))))),
            ]),
            const SizedBox(height: 10),
            Wrap(spacing: 8, children: ['none', 'minor', 'major'].map((c) => ChoiceChip(label: Text(c[0].toUpperCase() + c.substring(1)), selected: _complicationStatus == c, onSelected: (_) => setState(() => _complicationStatus = c))).toList()),
            const SizedBox(height: 10),
            TextFormField(controller: _complicationNotesCtrl, maxLines: 2, decoration: _deco('Complication Notes', hint: 'Only required if complication status is minor/major')),
            const SizedBox(height: 10),
            TextFormField(controller: _bloodLossCtrl, decoration: _deco('Blood Loss', hint: 'e.g. Minimal, 50ml')),
            const SizedBox(height: 16),

            const AppSectionHeader(title: 'C. Lens Selection', icon: Icons.remove_red_eye_outlined),
            const Padding(
              padding: EdgeInsets.only(bottom: 8),
              child: Text('Auto-filled from Counsellor form — confirm or adjust if needed before saving surgery.', style: TextStyle(fontSize: 11, color: AppColors.textSecondary)),
            ),
            DropdownButtonFormField<String>(
              initialValue: _lensCategory,
              isExpanded: true,
              decoration: _deco('Lens Category', hint: 'Select...'),
              items: const [DropdownMenuItem(value: 'standard', child: Text('Standard')), DropdownMenuItem(value: 'premium', child: Text('Premium'))],
              onChanged: (v) => setState(() => _lensCategory = v),
            ),
            const SizedBox(height: 10),
            TextFormField(controller: _lensCompanyCtrl, decoration: _deco('Lens Company')),
            const SizedBox(height: 10),
            TextFormField(controller: _lensModelCtrl, decoration: _deco('Lens Model')),
            const SizedBox(height: 10),
            DropdownButtonFormField<String>(
              initialValue: _lensType,
              isExpanded: true,
              decoration: _deco('Lens Type', hint: 'Select...'),
              items: formData.lensTypes.map((t) => DropdownMenuItem(value: t, child: Text(t))).toList(),
              onChanged: (v) => setState(() => _lensType = v),
            ),
            const SizedBox(height: 10),
            Row(children: [
              Expanded(child: TextFormField(controller: _estimatedPowerCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true, signed: true), decoration: _deco('Estimated Power'))),
              const SizedBox(width: 10),
              Expanded(child: TextFormField(controller: _lensCostCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), decoration: _deco('Lens Cost (₹)', hint: 'Enter lens cost'))),
            ]),
            const SizedBox(height: 16),

            AppSectionHeader(title: 'D. In-Ward Medicines', icon: Icons.medication_outlined, trailing: TextButton.icon(onPressed: _addMedicineRow, icon: const Icon(Icons.add_rounded, size: 14), label: const Text('Add Medicine', style: TextStyle(fontSize: 12)))),
            if (formData.medicineGroups.isNotEmpty)
              DropdownButtonFormField<int>(
                initialValue: _medicineGroupId,
                isExpanded: true,
                decoration: _deco('Quick-fill from OT Medicine Group', hint: 'Select group (optional)...'),
                items: formData.medicineGroups.map((g) => DropdownMenuItem(value: g.id, child: Text(g.name, overflow: TextOverflow.ellipsis))).toList(),
                onChanged: (v) {
                  if (v == null) return;
                  _applyMedicineGroup(formData.medicineGroups.firstWhere((g) => g.id == v));
                },
              ),
            const SizedBox(height: 10),
            for (var i = 0; i < _medicineRows.length; i++)
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Row(children: [
                  Expanded(
                    flex: 2,
                    child: DropdownButtonFormField<String>(
                      initialValue: _medicineRows[i].medicine,
                      isExpanded: true,
                      decoration: _deco('Medicine', hint: 'Select medicine...'),
                      items: formData.medicines.map((m) => DropdownMenuItem(value: m, child: Text(m, overflow: TextOverflow.ellipsis))).toList(),
                      onChanged: (v) => setState(() => _medicineRows[i].medicine = v),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(child: TextFormField(controller: _medicineRows[i].doseCtrl, decoration: _deco('Dose / Frequency'))),
                  IconButton(icon: const Icon(Icons.remove_circle_outline, size: 20, color: AppColors.red), onPressed: () => _removeMedicineRow(i)),
                ]),
              ),
          ],
        ),
      ),
      SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
          child: Row(children: [
            Expanded(
              child: OutlinedButton(
                onPressed: _saving ? null : () => Navigator.pop(context),
                style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md))),
                child: const Text('Cancel', style: TextStyle(fontWeight: FontWeight.w700)),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              flex: 2,
              child: ElevatedButton(
                onPressed: _saving ? null : _save,
                style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md))),
                child: _saving ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) : const Text('Save Surgery', style: TextStyle(fontWeight: FontWeight.w700)),
              ),
            ),
          ]),
        ),
      ),
    ]);
  }

  Widget _readOnlyRow(String label, String value) => Padding(
        padding: const EdgeInsets.only(bottom: 6),
        child: Row(children: [
          SizedBox(width: 110, child: Text(label, style: const TextStyle(fontSize: 12, color: AppColors.textSecondary))),
          Expanded(child: Text(value, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600))),
        ]),
      );
}
