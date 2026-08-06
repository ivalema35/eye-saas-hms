import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/permissions.dart';
import '../models/medicine_models.dart';
import '../models/ot_assistant_models.dart';
import '../services/medicine_service.dart';
import '../services/ot_assistant_service.dart';
import '../services/permission_service.dart';
import '../utils/app_route.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_section_header.dart';
import 'ot_lens_form_screen.dart';

/// Round 3 Phase 4 — Surgery Record. **One combined verify + record
/// submit** — the backend has no state for a partial verify, so this is a
/// single form/screen, not a confirm-then-record flow (explicit backend
/// instruction, see build PRD §8 gotcha).
///
/// Lens recording is reachable from here (app-bar action), not from the
/// Assistant Dashboard queue — matches web exactly, where lens is hidden
/// from the dashboard by design (OT_WEB_PARITY_FIX_PRD.md §5.1).
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
  List<MedGroup> _medicineGroups = [];
  bool _saving = false;

  final _otRoomCtrl = TextEditingController();
  final _complicationNotesCtrl = TextEditingController();
  final _bloodLossCtrl = TextEditingController();

  bool _identityVerified = false;
  bool _consentVerified = false;
  bool _paymentVerified = false;
  bool _correctEyeVerified = false;
  String? _surgeryName;
  String _eyeOperated = 'Both';
  DateTime? _startDateTime;
  DateTime? _endDateTime;
  String _complicationStatus = 'none';
  int? _medicineGroupId;
  final List<(TextEditingController medicine, TextEditingController dose)> _medicineRows = [];

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
    for (final (m, d) in _medicineRows) {
      m.dispose();
      d.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _loadError = null; });
    try {
      final results = await Future.wait([
        OtAssistantService.instance.fetchSurgeryFormData(widget.bookingId),
        MedicineService.instance.fetchOtMedicineGroups(),
      ]);
      if (mounted) {
        setState(() {
          _formData = results[0] as OtSurgeryFormData;
          _medicineGroups = (results[1] as MedGroupListResult).groups;
          _eyeOperated = _formData!.booking.eye ?? 'Both';
          final v = _formData!.verification;
          if (v != null) {
            _identityVerified = v.identityVerified;
            _consentVerified = v.consentVerified;
            _paymentVerified = v.paymentVerified;
            _correctEyeVerified = v.correctEyeVerified;
          }
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() { _loadError = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
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

  void _addMedicineRow() => setState(() => _medicineRows.add((TextEditingController(), TextEditingController())));
  void _removeMedicineRow(int i) => setState(() {
        _medicineRows[i].$1.dispose();
        _medicineRows[i].$2.dispose();
        _medicineRows.removeAt(i);
      });

  /// Wipes and rebuilds the medicine row list from the selected group's
  /// items — matches web's quick-fill behaviour exactly (OT_WEB_PARITY_FIX_PRD.md §5.2).
  void _applyMedicineGroup(MedGroup group) {
    setState(() {
      for (final (m, d) in _medicineRows) {
        m.dispose();
        d.dispose();
      }
      _medicineRows.clear();
      _medicineGroupId = group.id;
      for (final item in group.items) {
        _medicineRows.add((TextEditingController(text: item.medicineName ?? ''), TextEditingController(text: item.dosageText ?? '')));
      }
    });
  }

  String _fmtDateTime(DateTime d) => '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')} ${d.hour.toString().padLeft(2, '0')}:${d.minute.toString().padLeft(2, '0')}:00';
  String _fmtDisplay(DateTime? d) => d == null ? 'Select' : '${d.day.toString().padLeft(2, '0')}/${d.month.toString().padLeft(2, '0')}/${d.year} ${TimeOfDay.fromDateTime(d).format(context)}';

  Future<void> _save() async {
    if (!(_identityVerified && _consentVerified && _paymentVerified && _correctEyeVerified)) {
      showAppSnackBar(context, 'All 4 verification items must be checked', isError: true);
      return;
    }
    if (_surgeryName == null || _surgeryName!.isEmpty) {
      showAppSnackBar(context, 'Surgery name is required', isError: true);
      return;
    }
    if (_complicationStatus != 'none' && _complicationNotesCtrl.text.trim().isEmpty) {
      showAppSnackBar(context, 'Complication notes are required when a complication is recorded', isError: true);
      return;
    }
    setState(() => _saving = true);
    try {
      await OtAssistantService.instance.storeSurgery(
        widget.bookingId,
        identityVerified: _identityVerified,
        consentVerified: _consentVerified,
        paymentVerified: _paymentVerified,
        correctEyeVerified: _correctEyeVerified,
        surgeryName: _surgeryName!,
        otRoom: _otRoomCtrl.text.trim().isEmpty ? null : _otRoomCtrl.text.trim(),
        eyeOperated: _eyeOperated,
        startTime: _startDateTime == null ? null : _fmtDateTime(_startDateTime!),
        endTime: _endDateTime == null ? null : _fmtDateTime(_endDateTime!),
        complicationStatus: _complicationStatus,
        complicationNotes: _complicationNotesCtrl.text.trim().isEmpty ? null : _complicationNotesCtrl.text.trim(),
        bloodLoss: _bloodLossCtrl.text.trim().isEmpty ? null : _bloodLossCtrl.text.trim(),
        medicineGroupId: _medicineGroupId,
        otMedicines: _medicineRows.where((r) => r.$1.text.trim().isNotEmpty).map((r) => OtSurgeryMedicineLine(medicine: r.$1.text.trim(), dose: r.$2.text.trim().isEmpty ? null : r.$2.text.trim())).toList(),
      );
      if (!mounted) return;
      showAppSnackBar(context, 'Surgery recorded', isSuccess: true);
      Navigator.pop(context);
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
    }
  }

  InputDecoration _deco(String label) => InputDecoration(
        labelText: label,
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
                const Text('Surgery Record', style: TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.w800)),
                Text(widget.patientName, style: TextStyle(color: Colors.white.withValues(alpha: 0.75), fontSize: 11)),
              ]),
            ),
            if (PermissionService.instance.can(Perm.otLensRecord) || PermissionService.instance.can(Perm.otLensImplant))
              IconButton(
                icon: const Icon(Icons.remove_red_eye_outlined, color: Colors.white),
                tooltip: 'Record Lens',
                onPressed: () => Navigator.of(context).push(appRoute(OtLensFormScreen(bookingId: widget.bookingId, patientName: widget.patientName))),
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
            const AppSectionHeader(title: 'Patient & Booking Details', icon: Icons.badge_outlined),
            _readOnlyRow('Patient Name', formData.booking.patient?.fullName ?? '—'),
            _readOnlyRow('Phone', formData.booking.patient?.contactNo ?? '—'),
            _readOnlyRow('OT Date', formData.booking.surgeryDate ?? '—'),
            _readOnlyRow('Package', formData.counselling?.totalEstimate != null ? '₹${formData.counselling!.totalEstimate!.toStringAsFixed(0)}' : '—'),
            _readOnlyRow('Mediclaim', (formData.counselling?.mediclaim ?? false) ? 'YES' : 'NO'),
            const SizedBox(height: 16),

            const AppSectionHeader(title: 'Pre-Surgery Verification', icon: Icons.verified_outlined),
            _checklistTile('Identity Verified', _identityVerified, (v) => setState(() => _identityVerified = v)),
            _checklistTile('Consent Verified', _consentVerified, (v) => setState(() => _consentVerified = v)),
            _checklistTile('Payment Verified', _paymentVerified, (v) => setState(() => _paymentVerified = v)),
            _checklistTile('Correct Eye Verified', _correctEyeVerified, (v) => setState(() => _correctEyeVerified = v)),
            const SizedBox(height: 16),

            const AppSectionHeader(title: 'Surgery Details', icon: Icons.local_hospital_outlined),
            DropdownButtonFormField<String>(
              initialValue: _surgeryName,
              isExpanded: true,
              decoration: _deco('Surgery Name *'),
              items: formData.surgeryTypes.map((t) => DropdownMenuItem(value: t.surgeryName, child: Text(t.surgeryName, overflow: TextOverflow.ellipsis))).toList(),
              onChanged: (v) => setState(() => _surgeryName = v),
            ),
            const SizedBox(height: 10),
            Row(children: [
              Expanded(child: TextFormField(controller: _otRoomCtrl, decoration: _deco('OT Room'))),
              const SizedBox(width: 10),
              Expanded(
                child: Wrap(spacing: 8, children: ['RE', 'LE', 'Both'].map((e) => ChoiceChip(label: Text(e), selected: _eyeOperated == e, onSelected: (_) => setState(() => _eyeOperated = e))).toList()),
              ),
            ]),
            const SizedBox(height: 10),
            Row(children: [
              Expanded(child: InkWell(onTap: () => _pickDateTime(isStart: true), child: InputDecorator(decoration: _deco('Start Time'), child: Text(_fmtDisplay(_startDateTime))))),
              const SizedBox(width: 10),
              Expanded(child: InkWell(onTap: () => _pickDateTime(isStart: false), child: InputDecorator(decoration: _deco('End Time'), child: Text(_fmtDisplay(_endDateTime))))),
            ]),
            const SizedBox(height: 16),

            const AppSectionHeader(title: 'Complications', icon: Icons.warning_amber_outlined),
            Wrap(spacing: 8, children: ['none', 'minor', 'major'].map((c) => ChoiceChip(label: Text(c[0].toUpperCase() + c.substring(1)), selected: _complicationStatus == c, onSelected: (_) => setState(() => _complicationStatus = c))).toList()),
            if (_complicationStatus != 'none') ...[
              const SizedBox(height: 10),
              TextFormField(controller: _complicationNotesCtrl, maxLines: 2, decoration: _deco('Complication Notes *')),
            ],
            const SizedBox(height: 10),
            TextFormField(controller: _bloodLossCtrl, decoration: _deco('Blood Loss')),
            const SizedBox(height: 16),

            AppSectionHeader(title: 'OT Medicines', icon: Icons.medication_outlined, trailing: TextButton.icon(onPressed: _addMedicineRow, icon: const Icon(Icons.add_rounded, size: 14), label: const Text('Add', style: TextStyle(fontSize: 12)))),
            if (_medicineGroups.isNotEmpty)
              DropdownButtonFormField<int>(
                initialValue: _medicineGroupId,
                isExpanded: true,
                decoration: _deco('Quick-fill from OT Medicine Group'),
                items: _medicineGroups.map((g) => DropdownMenuItem(value: g.id, child: Text(g.name, overflow: TextOverflow.ellipsis))).toList(),
                onChanged: (v) {
                  if (v == null) return;
                  _applyMedicineGroup(_medicineGroups.firstWhere((g) => g.id == v));
                },
              ),
            const SizedBox(height: 10),
            for (var i = 0; i < _medicineRows.length; i++)
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Row(children: [
                  Expanded(flex: 2, child: TextFormField(controller: _medicineRows[i].$1, decoration: _deco('Medicine'))),
                  const SizedBox(width: 8),
                  Expanded(child: TextFormField(controller: _medicineRows[i].$2, decoration: _deco('Dose'))),
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
          child: ElevatedButton(
            onPressed: _saving ? null : _save,
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md))),
            child: _saving ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) : const Text('Verify & Record Surgery', style: TextStyle(fontWeight: FontWeight.w700)),
          ),
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

  Widget _checklistTile(String label, bool value, ValueChanged<bool> onChanged) => CheckboxListTile(
        value: value,
        onChanged: (v) => onChanged(v ?? false),
        title: Text(label, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
        controlAffinity: ListTileControlAffinity.leading,
        contentPadding: EdgeInsets.zero,
        activeColor: AppColors.teal,
      );
}
