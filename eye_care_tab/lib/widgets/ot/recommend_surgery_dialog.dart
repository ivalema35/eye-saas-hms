import 'package:flutter/material.dart';
import '../../constants/app_colors.dart';
import '../../constants/app_radius.dart';
import '../../models/patient_models.dart';
import '../../services/ot_booking_service.dart';
import '../../services/ot_slot_service.dart';
import '../../services/ot_surgery_type_service.dart';
import '../app_animations.dart';

/// Doctor's "Recommend Surgery / Refer to Counsellor" action — the exam-
/// screen handoff into the OT workflow (Round 3.5, `ot.surgery.recommend`).
/// Returns `true` if a booking was created/updated.
Future<bool?> showRecommendSurgeryDialog(
  BuildContext context, {
  required Patient patient,
  String? diagnosisHint,
}) {
  return showDialog<bool>(
    context: context,
    builder: (_) => _RecommendSurgeryDialog(patient: patient, diagnosisHint: diagnosisHint),
  );
}

class _RecommendSurgeryDialog extends StatefulWidget {
  final Patient patient;
  final String? diagnosisHint;
  const _RecommendSurgeryDialog({required this.patient, this.diagnosisHint});

  @override
  State<_RecommendSurgeryDialog> createState() => _RecommendSurgeryDialogState();
}

class _RecommendSurgeryDialogState extends State<_RecommendSurgeryDialog> {
  static const _eyeOptions = ['RE', 'LE', 'Both'];

  late final TextEditingController _diagnosisCtrl;
  String _eye = 'RE';
  DateTime? _surgeryDate;
  List<OtSlotItem> _slots = [];
  List<OtSurgeryTypeItem> _surgeryTypes = [];
  OtSlotItem? _selectedSlot;
  OtSurgeryTypeItem? _selectedSurgeryType;

  bool _loadingMasters = true;
  String? _mastersError;
  bool _saving = false;
  String? _dateError, _slotError, _typeError;

  @override
  void initState() {
    super.initState();
    _diagnosisCtrl = TextEditingController(text: widget.diagnosisHint ?? '');
    _loadMasters();
  }

  @override
  void dispose() {
    _diagnosisCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadMasters() async {
    try {
      final results = await Future.wait([
        OtSlotService.instance.fetchAll(),
        OtSurgeryTypeService.instance.fetchAll(),
      ]);
      if (!mounted) return;
      setState(() {
        _slots = results[0] as List<OtSlotItem>;
        _surgeryTypes = results[1] as List<OtSurgeryTypeItem>;
        _loadingMasters = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _mastersError = e.toString().replaceFirst('Exception: ', '');
        _loadingMasters = false;
      });
    }
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _surgeryDate ?? now,
      firstDate: now,
      lastDate: now.add(const Duration(days: 365)),
      builder: (ctx, child) => Theme(
        data: ThemeData.light().copyWith(colorScheme: ColorScheme.light(primary: AppColors.primary)),
        child: child!,
      ),
    );
    if (picked != null) setState(() { _surgeryDate = picked; _dateError = null; });
  }

  Future<void> _save() async {
    setState(() {
      _dateError = _surgeryDate == null ? 'Surgery date is required' : null;
      _slotError = _selectedSlot == null ? 'Please select a slot' : null;
      _typeError = _selectedSurgeryType == null ? 'Please select a surgery type' : null;
    });
    if (_dateError != null || _slotError != null || _typeError != null) return;

    setState(() => _saving = true);
    try {
      await OtBookingService.instance.recommendSurgery(
        patientId: widget.patient.id,
        eye: _eye,
        surgeryDate: _surgeryDate!,
        slotId: _selectedSlot!.id,
        otSurgeryTypeId: _selectedSurgeryType!.id,
        diagnosisHint: _diagnosisCtrl.text,
      );
      if (mounted) {
        Navigator.pop(context, true);
        showAppSnackBar(context, 'Surgery recommended — sent to OT Counsellor', isSuccess: true);
      }
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
      title: Row(children: [
        Container(
          padding: const EdgeInsets.all(7),
          decoration: BoxDecoration(color: AppColors.teal.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(10)),
          child: const Icon(Icons.medical_services_rounded, size: 16, color: AppColors.teal),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisSize: MainAxisSize.min, children: [
            const Text('Recommend Surgery', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
            Text(widget.patient.fullName, style: const TextStyle(fontSize: 12, color: AppColors.textSecondary, fontWeight: FontWeight.normal)),
          ]),
        ),
      ]),
      content: SizedBox(
        width: 420,
        child: _loadingMasters
            ? const Padding(padding: EdgeInsets.all(24), child: Center(child: CircularProgressIndicator(color: AppColors.teal)))
            : _mastersError != null
                ? Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(mainAxisSize: MainAxisSize.min, children: [
                      Text(_mastersError!, style: const TextStyle(color: AppColors.red)),
                      const SizedBox(height: 10),
                      ElevatedButton(onPressed: () { setState(() => _loadingMasters = true); _loadMasters(); }, child: const Text('Retry')),
                    ]),
                  )
                : SingleChildScrollView(
                    child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
                      const Text('Eye', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.textSecondary)),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        children: _eyeOptions.map((e) => ChoiceChip(
                          label: Text(e),
                          selected: _eye == e,
                          onSelected: (_) => setState(() => _eye = e),
                          selectedColor: AppColors.teal.withValues(alpha: 0.18),
                          labelStyle: TextStyle(color: _eye == e ? AppColors.tealDark : AppColors.textPrimary, fontWeight: FontWeight.w600),
                        )).toList(),
                      ),
                      const SizedBox(height: 14),
                      InkWell(
                        onTap: _pickDate,
                        borderRadius: BorderRadius.circular(AppRadius.md),
                        child: InputDecorator(
                          decoration: InputDecoration(labelText: 'Surgery Date *', errorText: _dateError, border: const OutlineInputBorder()),
                          child: Text(
                            _surgeryDate == null
                                ? 'Select date'
                                : '${_surgeryDate!.year}-${_surgeryDate!.month.toString().padLeft(2, '0')}-${_surgeryDate!.day.toString().padLeft(2, '0')}',
                          ),
                        ),
                      ),
                      const SizedBox(height: 14),
                      DropdownButtonFormField<OtSlotItem>(
                        initialValue: _selectedSlot,
                        isExpanded: true,
                        decoration: InputDecoration(labelText: 'Slot *', errorText: _slotError, border: const OutlineInputBorder()),
                        items: _slots.map((s) => DropdownMenuItem(value: s, child: Text(s.slotName, overflow: TextOverflow.ellipsis))).toList(),
                        onChanged: (v) => setState(() { _selectedSlot = v; _slotError = null; }),
                      ),
                      const SizedBox(height: 14),
                      DropdownButtonFormField<OtSurgeryTypeItem>(
                        initialValue: _selectedSurgeryType,
                        isExpanded: true,
                        decoration: InputDecoration(labelText: 'Surgery Type *', errorText: _typeError, border: const OutlineInputBorder()),
                        items: _surgeryTypes.map((t) => DropdownMenuItem(value: t, child: Text(t.surgeryName, overflow: TextOverflow.ellipsis))).toList(),
                        onChanged: (v) => setState(() { _selectedSurgeryType = v; _typeError = null; }),
                      ),
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: _diagnosisCtrl,
                        maxLines: 2,
                        decoration: const InputDecoration(labelText: 'Diagnosis Hint (optional)', border: OutlineInputBorder()),
                      ),
                    ]),
                  ),
      ),
      actions: [
        TextButton(onPressed: _saving ? null : () => Navigator.pop(context), child: const Text('Cancel')),
        ElevatedButton(
          onPressed: (_saving || _loadingMasters || _mastersError != null) ? null : _save,
          style: ElevatedButton.styleFrom(backgroundColor: AppColors.teal, foregroundColor: Colors.white),
          child: _saving
              ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
              : const Text('Recommend Surgery'),
        ),
      ],
    );
  }
}
