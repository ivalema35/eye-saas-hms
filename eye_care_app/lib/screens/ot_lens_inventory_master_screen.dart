import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../widgets/app_animations.dart';
import '../services/ot_inventory_service.dart';
import '../models/ot_inventory_models.dart';

class OtLensInventoryMasterScreen extends StatefulWidget {
  final Color accentColor;

  const OtLensInventoryMasterScreen({super.key, this.accentColor = AppColors.blue});

  @override
  State<OtLensInventoryMasterScreen> createState() => _OtLensInventoryMasterScreenState();
}

class _OtLensInventoryMasterScreenState extends State<OtLensInventoryMasterScreen> {
  List<LensInventoryItem> _all = [];
  bool _loading = false;
  String? _error;
  String _query = '';

  List<LensInventoryItem> get _filtered => _query.isEmpty
      ? _all
      : _all.where((i) =>
          i.lensName.toLowerCase().contains(_query.toLowerCase()) ||
          i.lensCode.toLowerCase().contains(_query.toLowerCase())).toList();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final items = await OtInventoryService.instance.fetchLensInventory();
      if (mounted) setState(() { _all = items; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  Future<void> _delete(LensInventoryItem item) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
        title: const Text('Delete?', style: TextStyle(fontWeight: FontWeight.w800)),
        content: Text('Delete "${item.lensName}" (${item.lensCode})? This cannot be undone.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          TextButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Delete', style: TextStyle(color: AppColors.red, fontWeight: FontWeight.w700))),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    try {
      await OtInventoryService.instance.deleteLensInventory(item.id);
      await _load();
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      _load();
    }
  }

  void _openSheet({LensInventoryItem? item}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (_) => _LensInventorySheet(
        item: item,
        accentColor: widget.accentColor,
        onSave: (draft) async {
          if (item == null) {
            await OtInventoryService.instance.createLensInventory(draft);
          } else {
            await OtInventoryService.instance.updateLensInventory(item.id, draft);
          }
          await _load();
        },
      ),
    );
  }

  Color _stockColor(int stock) => stock == 0 ? AppColors.red : (stock < 5 ? AppColors.orange : AppColors.green);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        elevation: 0,
        leading: IconButton(icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20), onPressed: () => Navigator.pop(context)),
        title: const Text('Lens Inventory', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800, letterSpacing: -0.2)),
        actions: [IconButton(icon: const Icon(Icons.add_circle_outline_rounded, color: Colors.white), tooltip: 'Add', onPressed: () => _openSheet())],
      ),
      body: Column(children: [_buildSearch(), Expanded(child: _buildBody())]),
    );
  }

  Widget _buildSearch() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 4),
      child: Container(
        height: 44,
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.12)), boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.05), blurRadius: 8)]),
        child: TextField(
          onChanged: (v) => setState(() => _query = v.trim()),
          style: const TextStyle(fontSize: 13.5, color: AppColors.darkNavy),
          decoration: const InputDecoration(hintText: 'Search by name or SKU...', hintStyle: TextStyle(color: Color(0xFF94A3B8), fontSize: 13), prefixIcon: Icon(Icons.search_rounded, color: Color(0xFF94A3B8), size: 20), border: InputBorder.none, contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 12)),
        ),
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) return Center(child: CircularProgressIndicator(color: AppColors.primary));
    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Icon(Icons.error_outline_rounded, size: 48, color: AppColors.red.withValues(alpha: 0.6)),
            const SizedBox(height: 12),
            Text(_error!, textAlign: TextAlign.center, style: const TextStyle(color: Color(0xFF64748B))),
            const SizedBox(height: 16),
            ElevatedButton.icon(onPressed: _load, icon: const Icon(Icons.refresh_rounded), label: const Text('Retry'), style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white)),
          ]),
        ),
      );
    }
    final items = _filtered;
    if (items.isEmpty) {
      return ListView(children: [
        const SizedBox(height: 72),
        Center(
          child: Column(children: [
            Icon(Icons.inventory_2_rounded, size: 56, color: AppColors.primary.withValues(alpha: 0.15)),
            const SizedBox(height: 12),
            Text(_query.isNotEmpty ? 'No results for "$_query"' : 'No lens stock yet.\nTap + to add one.', textAlign: TextAlign.center, style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 14, fontWeight: FontWeight.w500)),
          ]),
        ),
      ]);
    }
    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: _load,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 6, 14, 100),
        itemCount: items.length,
        itemBuilder: (_, i) {
          final item = items[i];
          return Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Container(
              padding: const EdgeInsets.fromLTRB(12, 11, 10, 11),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.primary.withValues(alpha: 0.08)), boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.05), blurRadius: 8, offset: const Offset(0, 2))]),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(children: [
                  Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(item.lensName, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.darkNavy)),
                      const SizedBox(height: 2),
                      Text('${item.lensCode} · ${item.type}${item.power != null && item.power!.isNotEmpty ? ' · ${item.power}D' : ''}', style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
                    ]),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(color: _stockColor(item.availableStock).withValues(alpha: 0.12), borderRadius: BorderRadius.circular(AppRadius.sm)),
                    child: Text('${item.availableStock} in stock', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: _stockColor(item.availableStock))),
                  ),
                  const SizedBox(width: 2),
                  InkWell(onTap: () => _openSheet(item: item), borderRadius: BorderRadius.circular(AppRadius.sm), child: const Padding(padding: EdgeInsets.all(6), child: Icon(Icons.edit_outlined, size: 18, color: Color(0xFFE67E22)))),
                  InkWell(onTap: () => _delete(item), borderRadius: BorderRadius.circular(AppRadius.sm), child: const Padding(padding: EdgeInsets.all(6), child: Icon(Icons.delete_outline_rounded, size: 18, color: AppColors.red))),
                ]),
                Padding(
                  padding: const EdgeInsets.only(top: 4),
                  child: Text('MRP ₹${item.mrp.toStringAsFixed(0)}${item.manufacturer != null && item.manufacturer!.isNotEmpty ? '  ·  ${item.manufacturer}' : ''}', style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
                ),
              ]),
            ),
          );
        },
      ),
    );
  }
}

// ── Add / Edit sheet ──────────────────────────────────────────────────────────

class _LensInventorySheet extends StatefulWidget {
  final LensInventoryItem? item;
  final Color accentColor;
  final Future<void> Function(LensInventoryItem draft) onSave;

  const _LensInventorySheet({required this.item, required this.accentColor, required this.onSave});

  @override
  State<_LensInventorySheet> createState() => _LensInventorySheetState();
}

class _LensInventorySheetState extends State<_LensInventorySheet> {
  late final TextEditingController _codeCtrl, _nameCtrl, _manufacturerCtrl, _powerCtrl, _batchCtrl, _serialCtrl, _mrpCtrl, _purchaseCostCtrl, _supplierCtrl, _stockCtrl;
  String? _type;
  DateTime? _expiryDate;
  bool _saving = false;
  String? _codeError, _nameError, _typeError, _mrpError, _stockError;

  @override
  void initState() {
    super.initState();
    final it = widget.item;
    _codeCtrl = TextEditingController(text: it?.lensCode ?? '');
    _nameCtrl = TextEditingController(text: it?.lensName ?? '');
    _manufacturerCtrl = TextEditingController(text: it?.manufacturer ?? '');
    _powerCtrl = TextEditingController(text: it?.power ?? '');
    _batchCtrl = TextEditingController(text: it?.batchNumber ?? '');
    _serialCtrl = TextEditingController(text: it?.serialNumber ?? '');
    _mrpCtrl = TextEditingController(text: it != null ? it.mrp.toStringAsFixed(2) : '');
    _purchaseCostCtrl = TextEditingController(text: it?.purchaseCost != null ? it!.purchaseCost!.toStringAsFixed(2) : '');
    _supplierCtrl = TextEditingController(text: it?.supplier ?? '');
    _stockCtrl = TextEditingController(text: it != null ? '${it.availableStock}' : '');
    _type = it?.type;
    if (it?.expiryDate != null && it!.expiryDate!.isNotEmpty) {
      _expiryDate = DateTime.tryParse(it.expiryDate!);
    }
  }

  @override
  void dispose() {
    for (final c in [_codeCtrl, _nameCtrl, _manufacturerCtrl, _powerCtrl, _batchCtrl, _serialCtrl, _mrpCtrl, _purchaseCostCtrl, _supplierCtrl, _stockCtrl]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _pickExpiry() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _expiryDate ?? now,
      firstDate: now.subtract(const Duration(days: 365)),
      lastDate: now.add(const Duration(days: 365 * 10)),
      builder: (ctx, child) => Theme(data: ThemeData.light().copyWith(colorScheme: ColorScheme.light(primary: AppColors.primary)), child: child!),
    );
    if (picked != null) setState(() => _expiryDate = picked);
  }

  Future<void> _save() async {
    final mrp = double.tryParse(_mrpCtrl.text.trim());
    final stock = int.tryParse(_stockCtrl.text.trim());
    setState(() {
      _codeError = _codeCtrl.text.trim().isEmpty ? 'SKU is required' : null;
      _nameError = _nameCtrl.text.trim().isEmpty ? 'Lens name is required' : null;
      _typeError = _type == null ? 'Please select a lens type' : null;
      _mrpError = mrp == null ? 'Enter a valid MRP' : null;
      _stockError = stock == null || stock < 0 ? 'Enter a valid stock quantity' : null;
    });
    if (_codeError != null || _nameError != null || _typeError != null || _mrpError != null || _stockError != null) return;

    setState(() => _saving = true);
    try {
      final draft = LensInventoryItem(
        id: widget.item?.id ?? 0,
        lensCode: _codeCtrl.text.trim(),
        manufacturer: _manufacturerCtrl.text.trim().isEmpty ? null : _manufacturerCtrl.text.trim(),
        lensName: _nameCtrl.text.trim(),
        type: _type!,
        power: _powerCtrl.text.trim().isEmpty ? null : _powerCtrl.text.trim(),
        batchNumber: _batchCtrl.text.trim().isEmpty ? null : _batchCtrl.text.trim(),
        serialNumber: _serialCtrl.text.trim().isEmpty ? null : _serialCtrl.text.trim(),
        mrp: mrp!,
        purchaseCost: double.tryParse(_purchaseCostCtrl.text.trim()),
        supplier: _supplierCtrl.text.trim().isEmpty ? null : _supplierCtrl.text.trim(),
        expiryDate: _expiryDate == null ? null : '${_expiryDate!.year}-${_expiryDate!.month.toString().padLeft(2, '0')}-${_expiryDate!.day.toString().padLeft(2, '0')}',
        availableStock: stock!,
      );
      await widget.onSave(draft);
      if (mounted) Navigator.pop(context);
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
    }
  }

  InputDecoration _deco(String label, {String? error, String? suffix}) => InputDecoration(
        labelText: label,
        errorText: error,
        suffixText: suffix,
        filled: true,
        fillColor: const Color(0xFFF0F6FB),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide.none),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide(color: widget.accentColor, width: 1.5)),
      );

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.only(bottom: bottom),
      child: DraggableScrollableSheet(
        expand: false,
        initialChildSize: 0.9,
        maxChildSize: 0.95,
        builder: (_, scrollCtrl) => SingleChildScrollView(
          controller: scrollCtrl,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(margin: const EdgeInsets.only(top: 10, bottom: 4), width: 40, height: 4, decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.18), borderRadius: BorderRadius.circular(2))),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 10, 8, 14),
                child: Row(children: [
                  Container(padding: const EdgeInsets.all(7), decoration: BoxDecoration(color: widget.accentColor.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(10)), child: Icon(Icons.inventory_2_rounded, size: 16, color: widget.accentColor)),
                  const SizedBox(width: 10),
                  Expanded(child: Text(widget.item == null ? 'Add Lens Stock' : 'Edit Lens Stock', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.darkNavy))),
                  IconButton(icon: const Icon(Icons.close_rounded, size: 20, color: Color(0xFF94A3B8)), onPressed: () => Navigator.pop(context)),
                ]),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Column(children: [
                  Row(children: [
                    Expanded(child: TextFormField(controller: _codeCtrl, decoration: _deco('SKU / Lens Code *', error: _codeError))),
                    const SizedBox(width: 10),
                    Expanded(child: TextFormField(controller: _manufacturerCtrl, decoration: _deco('Manufacturer'))),
                  ]),
                  const SizedBox(height: 12),
                  TextFormField(controller: _nameCtrl, textCapitalization: TextCapitalization.words, decoration: _deco('Lens Name *', error: _nameError)),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    initialValue: _type,
                    isExpanded: true,
                    decoration: _deco('Lens Type *', error: _typeError),
                    items: kOtLensTypes.map((t) => DropdownMenuItem(value: t, child: Text(t))).toList(),
                    onChanged: (v) => setState(() { _type = v; _typeError = null; }),
                  ),
                  const SizedBox(height: 12),
                  Row(children: [
                    Expanded(child: TextFormField(controller: _powerCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true, signed: true), inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^-?\d*\.?\d*'))], decoration: _deco('Power', suffix: 'D'))),
                    const SizedBox(width: 10),
                    Expanded(child: TextFormField(controller: _stockCtrl, keyboardType: TextInputType.number, inputFormatters: [FilteringTextInputFormatter.digitsOnly], decoration: _deco('Available Stock *', error: _stockError))),
                  ]),
                  const SizedBox(height: 12),
                  Row(children: [
                    Expanded(child: TextFormField(controller: _batchCtrl, decoration: _deco('Batch Number'))),
                    const SizedBox(width: 10),
                    Expanded(child: TextFormField(controller: _serialCtrl, decoration: _deco('Serial Number'))),
                  ]),
                  const SizedBox(height: 12),
                  Row(children: [
                    Expanded(child: TextFormField(controller: _mrpCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d*'))], decoration: _deco('MRP *', error: _mrpError, suffix: '₹'))),
                    const SizedBox(width: 10),
                    Expanded(child: TextFormField(controller: _purchaseCostCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d*'))], decoration: _deco('Purchase Cost', suffix: '₹'))),
                  ]),
                  const SizedBox(height: 12),
                  TextFormField(controller: _supplierCtrl, decoration: _deco('Supplier')),
                  const SizedBox(height: 12),
                  InkWell(
                    onTap: _pickExpiry,
                    borderRadius: BorderRadius.circular(AppRadius.md),
                    child: InputDecorator(
                      decoration: _deco('Expiry Date'),
                      child: Text(
                        _expiryDate == null ? 'Select date (optional)' : '${_expiryDate!.year}-${_expiryDate!.month.toString().padLeft(2, '0')}-${_expiryDate!.day.toString().padLeft(2, '0')}',
                        style: TextStyle(fontSize: 14, color: _expiryDate == null ? const Color(0xFF94A3B8) : AppColors.darkNavy),
                      ),
                    ),
                  ),
                ]),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 20, 16, 28),
                child: Row(children: [
                  Expanded(child: OutlinedButton(onPressed: _saving ? null : () => Navigator.pop(context), style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md))), child: const Text('Cancel'))),
                  const SizedBox(width: 12),
                  Expanded(
                    flex: 2,
                    child: ElevatedButton(
                      onPressed: _saving ? null : _save,
                      style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md))),
                      child: _saving ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) : const Text('Save', style: TextStyle(fontWeight: FontWeight.w700)),
                    ),
                  ),
                ]),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
