import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../widgets/app_animations.dart';
import '../services/ot_inventory_service.dart';
import '../models/ot_inventory_models.dart';

class OtLensPowerMasterScreen extends StatefulWidget {
  final Color accentColor;

  const OtLensPowerMasterScreen({super.key, this.accentColor = AppColors.purple});

  @override
  State<OtLensPowerMasterScreen> createState() => _OtLensPowerMasterScreenState();
}

class _OtLensPowerMasterScreenState extends State<OtLensPowerMasterScreen> {
  List<LensPowerItem> _all = [];
  bool _loading = false;
  String? _error;
  String _query = '';

  List<LensPowerItem> get _filtered =>
      _query.isEmpty ? _all : _all.where((i) => i.value.toLowerCase().contains(_query.toLowerCase())).toList();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final items = await OtInventoryService.instance.fetchLensPowers();
      if (mounted) setState(() { _all = items; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  Future<void> _delete(LensPowerItem item) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
        title: const Text('Delete?', style: TextStyle(fontWeight: FontWeight.w800)),
        content: Text('Delete "${item.value}"? This cannot be undone.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          TextButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Delete', style: TextStyle(color: AppColors.red, fontWeight: FontWeight.w700))),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    try {
      await OtInventoryService.instance.deleteLensPower(item.id);
      await _load();
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      _load();
    }
  }

  void _openSheet({LensPowerItem? item}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (_) => _LensPowerSheet(
        item: item,
        accentColor: widget.accentColor,
        onSave: (value, isFavourite) async {
          if (item == null) {
            await OtInventoryService.instance.createLensPower(value, isFavourite);
          } else {
            await OtInventoryService.instance.updateLensPower(item.id, value, isFavourite);
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
        title: const Text('Lens Powers', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800, letterSpacing: -0.2)),
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
          decoration: const InputDecoration(hintText: 'Search lens powers...', hintStyle: TextStyle(color: Color(0xFF94A3B8), fontSize: 13), prefixIcon: Icon(Icons.search_rounded, color: Color(0xFF94A3B8), size: 20), border: InputBorder.none, contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 12)),
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
            Icon(Icons.lens_rounded, size: 56, color: AppColors.primary.withValues(alpha: 0.15)),
            const SizedBox(height: 12),
            Text(_query.isNotEmpty ? 'No results for "$_query"' : 'No lens powers yet.\nTap + to add one.', textAlign: TextAlign.center, style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 14, fontWeight: FontWeight.w500)),
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
              padding: const EdgeInsets.fromLTRB(12, 11, 8, 11),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.primary.withValues(alpha: 0.08)), boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.05), blurRadius: 8, offset: const Offset(0, 2))]),
              child: Row(children: [
                if (item.isFavourite) ...[
                  const Icon(Icons.star_rounded, size: 18, color: Colors.amber),
                  const SizedBox(width: 6),
                ] else ...[
                  Container(width: 30, height: 30, alignment: Alignment.center, decoration: BoxDecoration(color: widget.accentColor.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(AppRadius.sm)), child: Text('${i + 1}', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: widget.accentColor))),
                  const SizedBox(width: 12),
                ],
                Expanded(child: Text(item.value, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.darkNavy))),
                InkWell(onTap: () => _openSheet(item: item), borderRadius: BorderRadius.circular(AppRadius.sm), child: const Padding(padding: EdgeInsets.all(6), child: Icon(Icons.edit_outlined, size: 18, color: Color(0xFFE67E22)))),
                const SizedBox(width: 2),
                InkWell(onTap: () => _delete(item), borderRadius: BorderRadius.circular(AppRadius.sm), child: const Padding(padding: EdgeInsets.all(6), child: Icon(Icons.delete_outline_rounded, size: 18, color: AppColors.red))),
              ]),
            ),
          );
        },
      ),
    );
  }
}

class _LensPowerSheet extends StatefulWidget {
  final LensPowerItem? item;
  final Color accentColor;
  final Future<void> Function(String value, bool isFavourite) onSave;

  const _LensPowerSheet({required this.item, required this.accentColor, required this.onSave});

  @override
  State<_LensPowerSheet> createState() => _LensPowerSheetState();
}

class _LensPowerSheetState extends State<_LensPowerSheet> {
  late final TextEditingController _valueCtrl;
  late bool _isFavourite;
  bool _saving = false;
  String? _valueError;

  @override
  void initState() {
    super.initState();
    _valueCtrl = TextEditingController(text: widget.item?.value ?? '');
    _isFavourite = widget.item?.isFavourite ?? false;
  }

  @override
  void dispose() {
    _valueCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final value = _valueCtrl.text.trim();
    setState(() => _valueError = value.isEmpty ? 'Power value is required' : null);
    if (_valueError != null) return;

    setState(() => _saving = true);
    try {
      await widget.onSave(value, _isFavourite);
      if (mounted) Navigator.pop(context);
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.only(bottom: bottom),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(margin: const EdgeInsets.only(top: 10, bottom: 4), width: 40, height: 4, decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.18), borderRadius: BorderRadius.circular(2))),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 10, 8, 14),
            child: Row(children: [
              Container(padding: const EdgeInsets.all(7), decoration: BoxDecoration(color: widget.accentColor.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(10)), child: Icon(Icons.lens_rounded, size: 16, color: widget.accentColor)),
              const SizedBox(width: 10),
              Expanded(child: Text(widget.item == null ? 'Add Lens Power' : 'Edit Lens Power', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.darkNavy))),
              IconButton(icon: const Icon(Icons.close_rounded, size: 20, color: Color(0xFF94A3B8)), onPressed: () => Navigator.pop(context)),
            ]),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 0),
            child: TextFormField(
              controller: _valueCtrl,
              autofocus: true,
              decoration: InputDecoration(
                labelText: 'Power (e.g. +10.0D) *',
                errorText: _valueError,
                filled: true,
                fillColor: const Color(0xFFF0F6FB),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide.none),
                focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide(color: widget.accentColor, width: 1.5)),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(8, 4, 16, 0),
            child: SwitchListTile(
              value: _isFavourite,
              onChanged: (v) => setState(() => _isFavourite = v),
              activeThumbColor: Colors.amber,
              activeTrackColor: Colors.amber.withValues(alpha: 0.4),
              title: const Text('Favourite', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.darkNavy)),
              subtitle: const Text('Shown first in the lens power picker', style: TextStyle(fontSize: 12, color: Color(0xFF94A3B8))),
              contentPadding: const EdgeInsets.symmetric(horizontal: 8),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
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
    );
  }
}
