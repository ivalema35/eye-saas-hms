import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../widgets/app_animations.dart';
import '../models/auth_models.dart';
import '../models/medicine_models.dart';
import '../services/medicine_service.dart';

BoxDecoration _cardDeco() => BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(14),
      border: Border.all(color: AppColors.primaryA10),
      boxShadow: [BoxShadow(color: AppColors.primaryA06, blurRadius: 8, offset: const Offset(0, 2))],
    );



// ═════════════════════════════════════════════════════════════════════════════
// FORM SCREEN
// ═════════════════════════════════════════════════════════════════════════════

class MedicineGroupFormScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final MedGroup? existing;

  const MedicineGroupFormScreen({
    super.key,
    required this.user,
    required this.hospital,
    this.existing,
  });

  @override
  State<MedicineGroupFormScreen> createState() => _MedicineGroupFormScreenState();
}

class _MedicineGroupFormScreenState extends State<MedicineGroupFormScreen> {
  final _formKey = GlobalKey<FormState>();

  MedGroupFormData? _formData;
  bool _loadingFormData = true;
  String? _loadError;
  bool _saving = false;

  // Header controllers
  late final TextEditingController _nameCtrl;
  late final TextEditingController _codeCtrl;
  int? _diagnosisId;

  // Parallel lists for repeater rows
  late List<int?> _medIds;
  late List<int?> _dosageIds;
  late List<int?> _routeIds;
  late List<TextEditingController> _durationCtrls;
  late List<TextEditingController> _qtyCtrls;

  @override
  void initState() {
    super.initState();
    final g = widget.existing;
    _nameCtrl = TextEditingController(text: g?.name ?? '');
    _codeCtrl = TextEditingController(text: g?.groupCode ?? '');
    _diagnosisId = g?.diagnosisId;

    if (g != null && g.items.isNotEmpty) {
      _medIds      = g.items.map((i) => i.medicineId).toList();
      _dosageIds   = g.items.map((i) => i.dosageId).toList();
      _routeIds    = g.items.map((i) => i.routeId).toList();
      _durationCtrls = g.items.map((i) => TextEditingController(text: i.duration ?? '')).toList();
      _qtyCtrls    = g.items.map((i) => TextEditingController(text: '${i.quantity}')).toList();
    } else {
      _medIds      = [null];
      _dosageIds   = [null];
      _routeIds    = [null];
      _durationCtrls = [TextEditingController()];
      _qtyCtrls    = [TextEditingController(text: '1')];
    }

    _loadFormData();
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _codeCtrl.dispose();
    for (final c in _durationCtrls) { c.dispose(); }
    for (final c in _qtyCtrls) { c.dispose(); }
    super.dispose();
  }

  Future<void> _loadFormData() async {
    setState(() { _loadingFormData = true; _loadError = null; });
    try {
      final data = await MedicineService.instance.fetchGroupFormData();
      if (mounted) setState(() { _formData = data; _loadingFormData = false; });
    } catch (e) {
      if (mounted) setState(() { _loadError = e.toString().replaceFirst('Exception: ', ''); _loadingFormData = false; });
    }
  }

  // ── Row management ───────────────────────────────────────────────────────────

  void _addRow() => setState(() {
    _medIds.add(null);
    _dosageIds.add(null);
    _routeIds.add(null);
    _durationCtrls.add(TextEditingController());
    _qtyCtrls.add(TextEditingController(text: '1'));
  });

  void _removeRow(int i) {
    if (_medIds.length <= 1) return;
    setState(() {
      _medIds.removeAt(i);
      _dosageIds.removeAt(i);
      _routeIds.removeAt(i);
      _durationCtrls[i].dispose();
      _durationCtrls.removeAt(i);
      _qtyCtrls[i].dispose();
      _qtyCtrls.removeAt(i);
    });
  }

  void _onMedicineSelected(int idx, int? medicineId) {
    setState(() {
      _medIds[idx] = medicineId;
      if (medicineId != null) {
        final af = _formData?.autoFillFor(medicineId);
        if (af != null) {
          if (af.dosageId != null) _dosageIds[idx] = af.dosageId;
          if (af.duration != null && af.duration!.isNotEmpty) {
            _durationCtrls[idx].text = af.duration!;
          }
        }
      }
    });
  }

  // ── Save ─────────────────────────────────────────────────────────────────────

  Future<void> _save() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;

    for (int i = 0; i < _medIds.length; i++) {
      if (_medIds[i] == null) {
        showAppSnackBar(context, 'Row ${i + 1}: Select a medicine', isError: true);
        return;
      }
    }

    setState(() => _saving = true);
    try {
      final payload = {
        'name': _nameCtrl.text.trim(),
        'group_code': _codeCtrl.text.trim().isEmpty ? null : _codeCtrl.text.trim(),
        'diagnosis_id': _diagnosisId,
        'items': List.generate(_medIds.length, (i) => {
          'medicine_id': _medIds[i],
          'dosage_id':   _dosageIds[i],
          'route_id':    _routeIds[i],
          'duration':    _durationCtrls[i].text.trim().isEmpty ? null : _durationCtrls[i].text.trim(),
          'quantity':    int.tryParse(_qtyCtrls[i].text) ?? 1,
        }),
      };

      if (widget.existing == null) {
        await MedicineService.instance.createGroup(payload);
      } else {
        await MedicineService.instance.updateGroup(widget.existing!.id, payload);
      }

      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
    }
  }

  // ── Build ─────────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    final isEdit = widget.existing != null;
    return Scaffold(
      backgroundColor: const Color(0xFFF6FAFD),
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: Text(
          isEdit ? 'Edit Group' : 'New Medicine Group',
          style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 18),
        ),
        actions: [
          if (_saving)
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 16),
              child: Center(
                child: SizedBox(
                  width: 20, height: 20,
                  child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                ),
              ),
            )
          else
            TextButton.icon(
              onPressed: _save,
              icon: const Icon(Icons.check_rounded, color: Colors.white, size: 18),
              label: const Text('Save', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 14)),
            ),
        ],
      ),
      body: _loadingFormData
          ? Center(child: CircularProgressIndicator(color: AppColors.primary))
          : _loadError != null
              ? _buildError()
              : _buildForm(),
    );
  }

  Widget _buildError() => Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.wifi_off_rounded, size: 48, color: Color(0xFF6B7D93).withValues(alpha: 0.60)),
              const SizedBox(height: 12),
              Text(_loadError!, textAlign: TextAlign.center, style: const TextStyle(color: Color(0xFF6B7D93), fontSize: 13)),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: _loadFormData,
                icon: const Icon(Icons.refresh_rounded, size: 16),
                label: const Text('Retry'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary, foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
                ),
              ),
            ],
          ),
        ),
      );

  Widget _buildForm() {
    final fd = _formData!;
    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(14, 14, 14, 32),
        children: [
          // ── Group details ──────────────────────────────────────────────────
          Container(
            padding: const EdgeInsets.all(16),
            decoration: _cardDeco(),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Group Details',
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.w900, color: AppColors.primary)),
                const SizedBox(height: 14),
                _lbl('Group Name *'),
                _Field(
                  ctrl: _nameCtrl,
                  hint: 'e.g. Post-Op Cataract',
                  validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                ),
                const SizedBox(height: 12),
                _lbl('Group Code'),
                _Field(ctrl: _codeCtrl, hint: 'e.g. GRP-001'),
                const SizedBox(height: 12),
                _lbl('Diagnosis (optional)'),
                _DiagnosisDropdown(
                  diagnoses: fd.diagnoses,
                  value: _diagnosisId,
                  onChanged: (v) => setState(() => _diagnosisId = v),
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),
          // ── Medicine rows ──────────────────────────────────────────────────
          Container(
            padding: const EdgeInsets.all(16),
            decoration: _cardDeco(),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text('Medicines',
                          style: TextStyle(fontSize: 13, fontWeight: FontWeight.w900, color: AppColors.primary)),
                    ),
                    ElevatedButton.icon(
                      onPressed: _addRow,
                      icon: const Icon(Icons.add_rounded, size: 14),
                      label: const Text('Add Row', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.sm)),
                        elevation: 0,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                ...List.generate(_medIds.length, (i) => Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: _RowCard(
                    index: i,
                    canRemove: _medIds.length > 1,
                    medicines: fd.medicines,
                    dosages: fd.dosages,
                    routes: fd.routes,
                    medicineId: _medIds[i],
                    dosageId: _dosageIds[i],
                    routeId: _routeIds[i],
                    durationCtrl: _durationCtrls[i],
                    qtyCtrl: _qtyCtrls[i],
                    onMedicineChanged: (mid) => _onMedicineSelected(i, mid),
                    onDosageChanged: (did) => setState(() => _dosageIds[i] = did),
                    onRouteChanged: (rid) => setState(() => _routeIds[i] = rid),
                    onRemove: () => _removeRow(i),
                  ),
                )),
              ],
            ),
          ),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: _saving ? null : _save,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
              ),
              child: _saving
                  ? const SizedBox(
                      width: 18, height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : Text(
                      widget.existing != null ? 'Update Group' : 'Save Group',
                      style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
                    ),
            ),
          ),
        ],
      ),
    );
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// ROW CARD — single medicine row in the repeater
// ═════════════════════════════════════════════════════════════════════════════

class _RowCard extends StatelessWidget {
  final int index;
  final bool canRemove;
  final List<MedMasterItem> medicines;
  final List<MedMasterItem> dosages;
  final List<MedMasterItem> routes;
  final int? medicineId;
  final int? dosageId;
  final int? routeId;
  final TextEditingController durationCtrl;
  final TextEditingController qtyCtrl;
  final ValueChanged<int?> onMedicineChanged;
  final ValueChanged<int?> onDosageChanged;
  final ValueChanged<int?> onRouteChanged;
  final VoidCallback onRemove;

  const _RowCard({
    required this.index,
    required this.canRemove,
    required this.medicines,
    required this.dosages,
    required this.routes,
    required this.medicineId,
    required this.dosageId,
    required this.routeId,
    required this.durationCtrl,
    required this.qtyCtrl,
    required this.onMedicineChanged,
    required this.onDosageChanged,
    required this.onRouteChanged,
    required this.onRemove,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.primaryA06,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: AppColors.primaryA12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 24, height: 24,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: AppColors.primary,
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  '${index + 1}',
                  style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Colors.white),
                ),
              ),
              const Spacer(),
              if (canRemove)
                InkWell(
                  onTap: onRemove,
                  borderRadius: BorderRadius.circular(6),
                  child: Container(
                    padding: const EdgeInsets.all(4),
                    decoration: BoxDecoration(
                      color: const Color(0xFFD94841).withValues(alpha: 0.10),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Icon(Icons.close_rounded, size: 15, color: const Color(0xFFD94841)),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 8),
          _lbl('Medicine *'),
          _RowDropdown<int>(
            hint: 'Select medicine',
            value: medicineId,
            items: medicines
                .map((m) => DropdownMenuItem(value: m.id, child: Text(m.name, overflow: TextOverflow.ellipsis)))
                .toList(),
            onChanged: onMedicineChanged,
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                flex: 3,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _lbl('Dosage'),
                    _RowDropdown<int>(
                      hint: 'Select',
                      value: dosageId,
                      items: dosages
                          .map((d) => DropdownMenuItem(value: d.id, child: Text(d.name)))
                          .toList(),
                      onChanged: onDosageChanged,
                      includeNull: true,
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                flex: 3,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _lbl('Duration'),
                    _RowField(ctrl: durationCtrl, hint: 'e.g. 4 days'),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                flex: 3,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _lbl('Route'),
                    _RowDropdown<int>(
                      hint: 'Select',
                      value: routeId,
                      items: routes
                          .map((r) => DropdownMenuItem(value: r.id, child: Text(r.name)))
                          .toList(),
                      onChanged: onRouteChanged,
                      includeNull: true,
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              SizedBox(
                width: 72,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _lbl('Qty'),
                    _RowField(
                      ctrl: qtyCtrl,
                      hint: '1',
                      keyboardType: TextInputType.number,
                      inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// SMALL WIDGETS
// ═════════════════════════════════════════════════════════════════════════════

class _DiagnosisDropdown extends StatelessWidget {
  final List<MedMasterItem> diagnoses;
  final int? value;
  final ValueChanged<int?> onChanged;

  const _DiagnosisDropdown({
    required this.diagnoses,
    required this.value,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) => DropdownButtonFormField<int?>(
        value: value,
        isExpanded: true,
        dropdownColor: Colors.white,
        hint: Text('— None —', style: TextStyle(fontSize: 12, color: Color(0xFF6B7D93).withValues(alpha: 0.60))),
        decoration: InputDecoration(
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(10),
            borderSide: BorderSide(color: AppColors.primary, width: 2),
          ),
          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
          isDense: true,
        ),
        style: const TextStyle(fontSize: 13, color: Color(0xFF18324A)),
        items: [
          const DropdownMenuItem<int?>(value: null, child: Text('— None —')),
          ...diagnoses.map((d) => DropdownMenuItem<int?>(value: d.id, child: Text(d.name))),
        ],
        onChanged: onChanged,
      );
}

class _RowDropdown<T> extends StatelessWidget {
  final String hint;
  final T? value;
  final List<DropdownMenuItem<T>> items;
  final ValueChanged<T?> onChanged;
  final bool includeNull;

  const _RowDropdown({
    required this.hint,
    required this.value,
    required this.items,
    required this.onChanged,
    this.includeNull = false,
  });

  @override
  Widget build(BuildContext context) => DropdownButtonFormField<T>(
        value: value,
        isExpanded: true,
        dropdownColor: Colors.white,
        hint: Text(hint, style: TextStyle(fontSize: 11, color: Color(0xFF6B7D93).withValues(alpha: 0.60))),
        decoration: InputDecoration(
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm)),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppRadius.sm),
            borderSide: BorderSide(color: AppColors.primary, width: 2),
          ),
          contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 9),
          isDense: true,
          filled: true,
          fillColor: Colors.white,
        ),
        style: const TextStyle(fontSize: 12, color: Color(0xFF18324A)),
        items: includeNull
            ? [DropdownMenuItem<T>(value: null, child: Text('—', style: TextStyle(color: Color(0xFF6B7D93).withValues(alpha: 0.60)))), ...items]
            : items,
        onChanged: onChanged,
      );
}

class _RowField extends StatelessWidget {
  final TextEditingController ctrl;
  final String? hint;
  final TextInputType? keyboardType;
  final List<TextInputFormatter>? inputFormatters;

  const _RowField({required this.ctrl, this.hint, this.keyboardType, this.inputFormatters});

  @override
  Widget build(BuildContext context) => TextFormField(
        controller: ctrl,
        keyboardType: keyboardType,
        inputFormatters: inputFormatters,
        style: const TextStyle(fontSize: 12, color: Color(0xFF18324A)),
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: TextStyle(fontSize: 11, color: Color(0xFF6B7D93).withValues(alpha: 0.60)),
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm)),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppRadius.sm),
            borderSide: BorderSide(color: AppColors.primary, width: 2),
          ),
          contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 9),
          isDense: true,
          filled: true,
          fillColor: Colors.white,
        ),
      );
}

class _Field extends StatelessWidget {
  final TextEditingController ctrl;
  final String? hint;
  final String? Function(String?)? validator;

  const _Field({required this.ctrl, this.hint, this.validator});

  @override
  Widget build(BuildContext context) => TextFormField(
        controller: ctrl,
        validator: validator,
        style: const TextStyle(fontSize: 13, color: Color(0xFF18324A)),
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: TextStyle(fontSize: 12, color: Color(0xFF6B7D93).withValues(alpha: 0.60)),
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(10),
            borderSide: BorderSide(color: AppColors.primary, width: 2),
          ),
          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
          isDense: true,
        ),
      );
}

Widget _lbl(String text) => Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Text(
        text,
        style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFF6B7D93).withValues(alpha: 0.60)),
      ),
    );
