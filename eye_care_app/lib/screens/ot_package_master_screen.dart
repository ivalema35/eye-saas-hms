import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../widgets/app_animations.dart';
import '../services/ot_inventory_service.dart';
import '../models/ot_inventory_models.dart';

class OtPackageMasterScreen extends StatefulWidget {
  final Color accentColor;

  const OtPackageMasterScreen({super.key, this.accentColor = AppColors.teal});

  @override
  State<OtPackageMasterScreen> createState() => _OtPackageMasterScreenState();
}

class _OtPackageMasterScreenState extends State<OtPackageMasterScreen> {
  List<OtPackageMasterItem> _all = [];
  bool _loading = false;
  String? _error;
  String _query = '';

  List<OtPackageMasterItem> get _filtered =>
      _query.isEmpty ? _all : _all.where((i) => i.packageName.toLowerCase().contains(_query.toLowerCase())).toList();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final items = await OtInventoryService.instance.fetchPackages();
      if (mounted) setState(() { _all = items; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  Future<void> _delete(OtPackageMasterItem item) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
        title: const Text('Delete?', style: TextStyle(fontWeight: FontWeight.w800)),
        content: Text('Delete "${item.packageName}"? This cannot be undone.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          TextButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Delete', style: TextStyle(color: AppColors.red, fontWeight: FontWeight.w700))),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    try {
      await OtInventoryService.instance.deletePackage(item.id);
      await _load();
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      _load();
    }
  }

  void _openSheet({OtPackageMasterItem? item}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (_) => _PackageSheet(
        item: item,
        accentColor: widget.accentColor,
        onSave: (packageName, roomCategory, otCharges, surgeonCharges, nursingCharges, consumablesCharges) async {
          if (item == null) {
            await OtInventoryService.instance.createPackage(
              packageName: packageName, roomCategory: roomCategory,
              otCharges: otCharges, surgeonCharges: surgeonCharges, nursingCharges: nursingCharges, consumablesCharges: consumablesCharges,
            );
          } else {
            await OtInventoryService.instance.updatePackage(item.id,
              packageName: packageName, roomCategory: roomCategory,
              otCharges: otCharges, surgeonCharges: surgeonCharges, nursingCharges: nursingCharges, consumablesCharges: consumablesCharges,
            );
          }
          await _load();
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        elevation: 0,
        leading: IconButton(icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20), onPressed: () => Navigator.pop(context)),
        title: const Text('OT Packages', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800, letterSpacing: -0.2)),
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
          decoration: const InputDecoration(hintText: 'Search packages...', hintStyle: TextStyle(color: Color(0xFF94A3B8), fontSize: 13), prefixIcon: Icon(Icons.search_rounded, color: Color(0xFF94A3B8), size: 20), border: InputBorder.none, contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 12)),
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
            Icon(Icons.card_giftcard_rounded, size: 56, color: AppColors.primary.withValues(alpha: 0.15)),
            const SizedBox(height: 12),
            Text(_query.isNotEmpty ? 'No results for "$_query"' : 'No packages yet.\nTap + to add one.', textAlign: TextAlign.center, style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 14, fontWeight: FontWeight.w500)),
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
                  Expanded(child: Text(item.packageName, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.darkNavy))),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(color: widget.accentColor.withValues(alpha: 0.10), borderRadius: BorderRadius.circular(AppRadius.sm)),
                    child: Text(item.roomCategory == 'private' ? 'Private' : 'General', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: widget.accentColor)),
                  ),
                  const SizedBox(width: 2),
                  InkWell(onTap: () => _openSheet(item: item), borderRadius: BorderRadius.circular(AppRadius.sm), child: const Padding(padding: EdgeInsets.all(6), child: Icon(Icons.edit_outlined, size: 18, color: Color(0xFFE67E22)))),
                  InkWell(onTap: () => _delete(item), borderRadius: BorderRadius.circular(AppRadius.sm), child: const Padding(padding: EdgeInsets.all(6), child: Icon(Icons.delete_outline_rounded, size: 18, color: AppColors.red))),
                ]),
                Padding(
                  padding: const EdgeInsets.only(top: 4),
                  child: Text('Total ₹${item.totalPreview.toStringAsFixed(0)}  ·  OT ₹${item.otCharges.toStringAsFixed(0)} · Surgeon ₹${item.surgeonCharges.toStringAsFixed(0)} · Nursing ₹${item.nursingCharges.toStringAsFixed(0)} · Consumables ₹${item.consumablesCharges.toStringAsFixed(0)}',
                      style: const TextStyle(fontSize: 11, color: AppColors.textSecondary)),
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

class _PackageSheet extends StatefulWidget {
  final OtPackageMasterItem? item;
  final Color accentColor;
  final Future<void> Function(String packageName, String roomCategory, double otCharges, double surgeonCharges, double nursingCharges, double consumablesCharges) onSave;

  const _PackageSheet({required this.item, required this.accentColor, required this.onSave});

  @override
  State<_PackageSheet> createState() => _PackageSheetState();
}

class _PackageSheetState extends State<_PackageSheet> {
  late final TextEditingController _nameCtrl, _otCtrl, _surgeonCtrl, _nursingCtrl, _consumablesCtrl;
  String _roomCategory = 'general';
  bool _saving = false;
  String? _nameError;

  @override
  void initState() {
    super.initState();
    final it = widget.item;
    _nameCtrl = TextEditingController(text: it?.packageName ?? '');
    _otCtrl = TextEditingController(text: it != null ? it.otCharges.toStringAsFixed(2) : '0');
    _surgeonCtrl = TextEditingController(text: it != null ? it.surgeonCharges.toStringAsFixed(2) : '0');
    _nursingCtrl = TextEditingController(text: it != null ? it.nursingCharges.toStringAsFixed(2) : '0');
    _consumablesCtrl = TextEditingController(text: it != null ? it.consumablesCharges.toStringAsFixed(2) : '0');
    _roomCategory = it?.roomCategory ?? 'general';
  }

  @override
  void dispose() {
    for (final c in [_nameCtrl, _otCtrl, _surgeonCtrl, _nursingCtrl, _consumablesCtrl]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    final name = _nameCtrl.text.trim();
    setState(() => _nameError = name.isEmpty ? 'Package name is required' : null);
    if (_nameError != null) return;

    setState(() => _saving = true);
    try {
      await widget.onSave(
        name,
        _roomCategory,
        double.tryParse(_otCtrl.text.trim()) ?? 0,
        double.tryParse(_surgeonCtrl.text.trim()) ?? 0,
        double.tryParse(_nursingCtrl.text.trim()) ?? 0,
        double.tryParse(_consumablesCtrl.text.trim()) ?? 0,
      );
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
    final numFmt = [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d*'))];
    return Padding(
      padding: EdgeInsets.only(bottom: bottom),
      child: DraggableScrollableSheet(
        expand: false,
        initialChildSize: 0.85,
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
                  Container(padding: const EdgeInsets.all(7), decoration: BoxDecoration(color: widget.accentColor.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(10)), child: Icon(Icons.card_giftcard_rounded, size: 16, color: widget.accentColor)),
                  const SizedBox(width: 10),
                  Expanded(child: Text(widget.item == null ? 'Add Package' : 'Edit Package', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.darkNavy))),
                  IconButton(icon: const Icon(Icons.close_rounded, size: 20, color: Color(0xFF94A3B8)), onPressed: () => Navigator.pop(context)),
                ]),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Column(children: [
                  TextFormField(controller: _nameCtrl, textCapitalization: TextCapitalization.words, decoration: _deco('Package Name *', error: _nameError)),
                  const SizedBox(height: 12),
                  Row(children: [
                    Expanded(
                      child: ChoiceChip(label: const Text('General'), selected: _roomCategory == 'general', onSelected: (_) => setState(() => _roomCategory = 'general'),
                        selectedColor: widget.accentColor.withValues(alpha: 0.18), labelStyle: TextStyle(color: _roomCategory == 'general' ? widget.accentColor : AppColors.textPrimary, fontWeight: FontWeight.w600)),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: ChoiceChip(label: const Text('Private'), selected: _roomCategory == 'private', onSelected: (_) => setState(() => _roomCategory = 'private'),
                        selectedColor: widget.accentColor.withValues(alpha: 0.18), labelStyle: TextStyle(color: _roomCategory == 'private' ? widget.accentColor : AppColors.textPrimary, fontWeight: FontWeight.w600)),
                    ),
                  ]),
                  const SizedBox(height: 12),
                  Row(children: [
                    Expanded(child: TextFormField(controller: _otCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), inputFormatters: numFmt, decoration: _deco('OT Charges', suffix: '₹'))),
                    const SizedBox(width: 10),
                    Expanded(child: TextFormField(controller: _surgeonCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), inputFormatters: numFmt, decoration: _deco('Surgeon Charges', suffix: '₹'))),
                  ]),
                  const SizedBox(height: 12),
                  Row(children: [
                    Expanded(child: TextFormField(controller: _nursingCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), inputFormatters: numFmt, decoration: _deco('Nursing Charges', suffix: '₹'))),
                    const SizedBox(width: 10),
                    Expanded(child: TextFormField(controller: _consumablesCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), inputFormatters: numFmt, decoration: _deco('Consumables', suffix: '₹'))),
                  ]),
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
