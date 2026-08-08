import 'package:flutter/material.dart';
import '../../constants/app_colors.dart';
import '../../constants/app_radius.dart';
import '../../models/patient_models.dart';
import '../../services/ot_booking_service.dart';
import '../../services/ot_surgery_type_service.dart';
import '../app_animations.dart';

/// Doctor's "Recommend Surgery / Refer to Counsellor" action — the exam-
/// screen handoff into the OT workflow (Round 3.5, `ot.surgery.recommend`).
/// Returns `true` if a booking was created/updated, so the caller can show
/// its own confirmation if it wants.
Future<bool?> showRecommendSurgerySheet(
  BuildContext context, {
  required Patient patient,
  String? diagnosisHint,
}) {
  return showModalBottomSheet<bool>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.white,
    shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
    builder: (_) => _RecommendSurgerySheet(patient: patient, diagnosisHint: diagnosisHint),
  );
}

class _RecommendSurgerySheet extends StatefulWidget {
  final Patient patient;
  final String? diagnosisHint;
  const _RecommendSurgerySheet({required this.patient, this.diagnosisHint});

  @override
  State<_RecommendSurgerySheet> createState() => _RecommendSurgerySheetState();
}

class _RecommendSurgerySheetState extends State<_RecommendSurgerySheet> {
  static const _eyeOptions = ['RE', 'LE', 'Both'];

  late final TextEditingController _diagnosisCtrl;
  String _eye = 'RE';
  List<OtSurgeryTypeItem> _surgeryTypes = [];
  OtSurgeryTypeItem? _selectedSurgeryType;

  bool _loadingMasters = true;
  String? _mastersError;
  bool _saving = false;
  String? _typeError;

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
      final surgeryTypes = await OtSurgeryTypeService.instance.fetchAll();
      if (!mounted) return;
      setState(() {
        _surgeryTypes = surgeryTypes;
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

  Future<void> _save() async {
    setState(() {
      _typeError = _selectedSurgeryType == null ? 'Please select a surgery type' : null;
    });
    if (_typeError != null) return;

    setState(() => _saving = true);
    try {
      await OtBookingService.instance.recommendSurgery(
        patientId: widget.patient.id,
        eye: _eye,
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

  InputDecoration _deco(String label, {String? error}) => InputDecoration(
        labelText: label,
        errorText: error,
        filled: true,
        fillColor: const Color(0xFFF0F6FB),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide.none),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: const BorderSide(color: AppColors.teal, width: 1.5),
        ),
      );

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.only(bottom: bottom),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              margin: const EdgeInsets.only(top: 10, bottom: 4),
              width: 40, height: 4,
              decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.18), borderRadius: BorderRadius.circular(2)),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 10, 8, 14),
              child: Row(children: [
                Container(
                  padding: const EdgeInsets.all(7),
                  decoration: BoxDecoration(color: AppColors.teal.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(10)),
                  child: const Icon(Icons.medical_services_rounded, size: 16, color: AppColors.teal),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    const Text('Recommend Surgery', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.darkNavy)),
                    Text(widget.patient.fullName, style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
                  ]),
                ),
                IconButton(icon: const Icon(Icons.close_rounded, size: 20, color: Color(0xFF94A3B8)), onPressed: () => Navigator.pop(context)),
              ]),
            ),
            if (_loadingMasters)
              const Padding(padding: EdgeInsets.all(32), child: CircularProgressIndicator(color: AppColors.teal))
            else if (_mastersError != null)
              Padding(
                padding: const EdgeInsets.all(20),
                child: Column(children: [
                  Text(_mastersError!, style: const TextStyle(color: AppColors.red), textAlign: TextAlign.center),
                  const SizedBox(height: 10),
                  ElevatedButton(onPressed: () { setState(() => _loadingMasters = true); _loadMasters(); }, child: const Text('Retry')),
                ]),
              )
            else
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 0),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
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
                  DropdownButtonFormField<OtSurgeryTypeItem>(
                    initialValue: _selectedSurgeryType,
                    isExpanded: true,
                    decoration: _deco('Surgery Type *', error: _typeError),
                    items: _surgeryTypes.map((t) => DropdownMenuItem(value: t, child: Text(t.surgeryName, overflow: TextOverflow.ellipsis))).toList(),
                    onChanged: (v) => setState(() { _selectedSurgeryType = v; _typeError = null; }),
                  ),
                  const SizedBox(height: 14),
                  TextFormField(
                    controller: _diagnosisCtrl,
                    maxLines: 2,
                    decoration: _deco('Diagnosis Hint (optional)'),
                  ),
                ]),
              ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 20, 16, 28),
              child: Row(children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: _saving ? null : () => Navigator.pop(context),
                    style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md))),
                    child: const Text('Cancel'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  flex: 2,
                  child: ElevatedButton(
                    onPressed: (_saving || _loadingMasters || _mastersError != null) ? null : _save,
                    style: ElevatedButton.styleFrom(backgroundColor: AppColors.teal, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md))),
                    child: _saving
                        ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : const Text('Recommend Surgery', style: TextStyle(fontWeight: FontWeight.w700)),
                  ),
                ),
              ]),
            ),
          ],
        ),
      ),
    );
  }
}
