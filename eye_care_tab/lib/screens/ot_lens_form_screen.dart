import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/ot_assistant_models.dart';
import '../models/ot_inventory_models.dart';
import '../services/ot_assistant_service.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_section_header.dart';

/// Tablet Lens Record (Round 3 Phase 4) — Pattern C, full pushed route.
/// Ported from eye_care_app/lib/screens/ot_lens_form_screen.dart.
class OtLensFormScreen extends StatefulWidget {
  final int bookingId;
  final String patientName;

  const OtLensFormScreen({super.key, required this.bookingId, required this.patientName});

  @override
  State<OtLensFormScreen> createState() => _OtLensFormScreenState();
}

class _OtLensFormScreenState extends State<OtLensFormScreen> {
  static final _genFmt = [FilteringTextInputFormatter.allow(RegExp(r'^-?\d*\.?\d*'))];

  bool _loading = true;
  String? _loadError;
  OtLensDetailResponse? _detail;
  bool _saving = false;

  final _lensNameCtrl = TextEditingController();
  final _manufacturerCtrl = TextEditingController();
  final _lensPowerCtrl = TextEditingController();
  final _axisCtrl = TextEditingController();
  final _lensMrpCtrl = TextEditingController();
  final _batchCtrl = TextEditingController();
  final _serialCtrl = TextEditingController();

  int? _lensInventoryId;
  String? _lensType;
  DateTime? _expiryDate;
  bool _implanted = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    for (final c in [_lensNameCtrl, _manufacturerCtrl, _lensPowerCtrl, _axisCtrl, _lensMrpCtrl, _batchCtrl, _serialCtrl]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _loadError = null; });
    try {
      final detail = await OtAssistantService.instance.fetchLens(widget.bookingId);
      if (mounted) {
        setState(() {
          _detail = detail;
          _loading = false;
          final d = detail.lensDetail;
          if (d != null) {
            _lensInventoryId = d.lensInventoryId;
            _lensNameCtrl.text = d.lensName;
            _manufacturerCtrl.text = d.manufacturer ?? '';
            _lensType = d.lensType;
            _lensPowerCtrl.text = d.lensPower.toString();
            _axisCtrl.text = d.axis?.toString() ?? '';
            _lensMrpCtrl.text = d.lensMrp.toString();
            _batchCtrl.text = d.batchNumber ?? '';
            _serialCtrl.text = d.serialNumber ?? '';
            _implanted = d.implanted;
            if (d.expiryDate != null && d.expiryDate!.isNotEmpty) _expiryDate = DateTime.tryParse(d.expiryDate!);
          }
        });
      }
    } catch (e) {
      if (mounted) setState(() { _loadError = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  void _applyInventoryItem(LensInventoryItem item) {
    setState(() {
      _lensInventoryId = item.id;
      _lensNameCtrl.text = item.lensName;
      _manufacturerCtrl.text = item.manufacturer ?? '';
      _lensType = item.type;
      _lensPowerCtrl.text = item.power ?? '';
      _lensMrpCtrl.text = item.mrp.toString();
      _batchCtrl.text = item.batchNumber ?? '';
      _serialCtrl.text = item.serialNumber ?? '';
      if (item.expiryDate != null && item.expiryDate!.isNotEmpty) _expiryDate = DateTime.tryParse(item.expiryDate!);
    });
  }

  Future<void> _pickExpiry() async {
    final now = DateTime.now();
    final picked = await showDatePicker(context: context, initialDate: _expiryDate ?? now, firstDate: now.subtract(const Duration(days: 365)), lastDate: now.add(const Duration(days: 365 * 10)));
    if (picked != null) setState(() => _expiryDate = picked);
  }

  Future<void> _save() async {
    final power = double.tryParse(_lensPowerCtrl.text.trim());
    final mrp = double.tryParse(_lensMrpCtrl.text.trim());
    if (_lensNameCtrl.text.trim().isEmpty || _lensType == null || power == null || mrp == null) {
      showAppSnackBar(context, 'Lens name, type, power, and MRP are required', isError: true);
      return;
    }
    setState(() => _saving = true);
    try {
      await OtAssistantService.instance.storeLens(
        widget.bookingId,
        lensInventoryId: _lensInventoryId,
        lensName: _lensNameCtrl.text.trim(),
        manufacturer: _manufacturerCtrl.text.trim().isEmpty ? null : _manufacturerCtrl.text.trim(),
        lensType: _lensType!,
        lensPower: power,
        axis: int.tryParse(_axisCtrl.text.trim()),
        lensMrp: mrp,
        batchNumber: _batchCtrl.text.trim().isEmpty ? null : _batchCtrl.text.trim(),
        serialNumber: _serialCtrl.text.trim().isEmpty ? null : _serialCtrl.text.trim(),
        expiryDate: _expiryDate == null ? null : '${_expiryDate!.year}-${_expiryDate!.month.toString().padLeft(2, '0')}-${_expiryDate!.day.toString().padLeft(2, '0')}',
        implanted: _implanted,
      );
      if (!mounted) return;
      showAppSnackBar(context, 'Lens details saved', isSuccess: true);
      Navigator.of(context).pop();
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
    }
  }

  InputDecoration _deco(String label, {String? suffix}) => InputDecoration(labelText: label, suffixText: suffix, border: const OutlineInputBorder());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFEBF5FB),
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
            IconButton(icon: const Icon(Icons.arrow_back_rounded, color: Colors.white), onPressed: () => Navigator.of(context).pop()),
            Container(width: 40, height: 40, decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(10)), child: const Icon(Icons.remove_red_eye_outlined, color: Colors.white, size: 20)),
            const SizedBox(width: 12),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                const Text('Lens Record', style: TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.w800)),
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
    final lensTypeOptions = detail.lensTypes.isNotEmpty ? detail.lensTypes : kOtLensTypes;
    return Column(children: [
      Expanded(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 800),
            child: ListView(
              padding: const EdgeInsets.fromLTRB(24, 20, 24, 20),
              children: [
                if (detail.lensInventory.isNotEmpty) ...[
                  const AppSectionHeader(title: 'Pick from Stock', icon: Icons.inventory_2_outlined),
                  DropdownButtonFormField<int>(
                    initialValue: _lensInventoryId,
                    isExpanded: true,
                    decoration: _deco('Lens Inventory Item'),
                    items: detail.lensInventory.map((i) => DropdownMenuItem(value: i.id, child: Text('${i.lensName} (${i.lensCode}) · ${i.availableStock} in stock', overflow: TextOverflow.ellipsis))).toList(),
                    onChanged: (v) {
                      final item = detail.lensInventory.firstWhere((i) => i.id == v);
                      _applyInventoryItem(item);
                    },
                  ),
                  const SizedBox(height: 20),
                ],
                const AppSectionHeader(title: 'Lens Details', icon: Icons.remove_red_eye_outlined),
                Row(children: [
                  Expanded(child: TextFormField(controller: _lensNameCtrl, decoration: _deco('Lens Name *'))),
                  const SizedBox(width: 12),
                  Expanded(child: TextFormField(controller: _manufacturerCtrl, decoration: _deco('Manufacturer'))),
                  const SizedBox(width: 12),
                  Expanded(child: DropdownButtonFormField<String>(initialValue: _lensType, isExpanded: true, decoration: _deco('Lens Type *'), items: lensTypeOptions.map((t) => DropdownMenuItem(value: t, child: Text(t, overflow: TextOverflow.ellipsis))).toList(), onChanged: (v) => setState(() => _lensType = v))),
                ]),
                const SizedBox(height: 12),
                if (detail.lensPowers.isNotEmpty) ...[
                  Wrap(spacing: 6, children: detail.lensPowers.where((p) => p.isFavourite).map((p) => ActionChip(label: Text(p.value, style: const TextStyle(fontSize: 11)), onPressed: () => setState(() => _lensPowerCtrl.text = p.value))).toList()),
                  const SizedBox(height: 8),
                ],
                Row(children: [
                  Expanded(child: TextFormField(controller: _lensPowerCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true, signed: true), inputFormatters: _genFmt, decoration: _deco('Lens Power *', suffix: 'D'))),
                  const SizedBox(width: 12),
                  Expanded(child: TextFormField(controller: _axisCtrl, keyboardType: TextInputType.number, inputFormatters: [FilteringTextInputFormatter.digitsOnly], decoration: _deco('Axis (0-180)'))),
                  const SizedBox(width: 12),
                  Expanded(child: TextFormField(controller: _lensMrpCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d*'))], decoration: _deco('MRP *', suffix: '₹'))),
                ]),
                const SizedBox(height: 12),
                Row(children: [
                  Expanded(child: TextFormField(controller: _batchCtrl, decoration: _deco('Batch Number'))),
                  const SizedBox(width: 12),
                  Expanded(child: TextFormField(controller: _serialCtrl, decoration: _deco('Serial Number'))),
                  const SizedBox(width: 12),
                  Expanded(child: InkWell(onTap: _pickExpiry, child: InputDecorator(decoration: _deco('Expiry Date'), child: Text(_expiryDate == null ? 'Select' : '${_expiryDate!.year}-${_expiryDate!.month.toString().padLeft(2, '0')}-${_expiryDate!.day.toString().padLeft(2, '0')}')))),
                ]),
                const SizedBox(height: 20),
                Row(children: [
                  Checkbox(value: _implanted, onChanged: (v) => setState(() => _implanted = v ?? false)),
                  const Text('Implanted', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                  const SizedBox(width: 10),
                  Text('(auto-decrements inventory stock on save)', style: TextStyle(fontSize: 11, color: AppColors.textSecondary)),
                ]),
              ],
            ),
          ),
        ),
      ),
      Container(
        width: double.infinity,
        padding: const EdgeInsets.fromLTRB(24, 12, 24, 16),
        decoration: const BoxDecoration(color: Colors.white, border: Border(top: BorderSide(color: Color(0xFFE0E0E0)))),
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 800),
            child: Align(
              alignment: Alignment.centerRight,
              child: ElevatedButton(
                onPressed: _saving ? null : _save,
                style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md))),
                child: _saving ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) : const Text('Save Lens Details', style: TextStyle(fontWeight: FontWeight.w700)),
              ),
            ),
          ),
        ),
      ),
    ]);
  }
}
