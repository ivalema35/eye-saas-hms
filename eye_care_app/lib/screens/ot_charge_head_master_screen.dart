import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../widgets/app_animations.dart';
import '../services/ot_charge_head_service.dart';

class OtChargeHeadMasterScreen extends StatefulWidget {
  final Color accentColor;

  const OtChargeHeadMasterScreen({
    super.key,
    this.accentColor = const Color(0xFF1ABC9C),
  });

  @override
  State<OtChargeHeadMasterScreen> createState() =>
      _OtChargeHeadMasterScreenState();
}

class _OtChargeHeadMasterScreenState extends State<OtChargeHeadMasterScreen> {
  List<OtChargeHeadItem> _all = [];
  bool _loading = false;
  String? _error;
  String _query = '';

  List<OtChargeHeadItem> get _filtered => _query.isEmpty
      ? _all
      : _all
          .where((i) =>
              i.chargeName.toLowerCase().contains(_query.toLowerCase()))
          .toList();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final items = await OtChargeHeadService.instance.fetchAll();
      if (mounted) {
        setState(() { _all = items; _loading = false; });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString().replaceFirst('Exception: ', '');
          _loading = false;
        });
      }
    }
  }

  Future<void> _delete(OtChargeHeadItem item) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
        title: const Text('Delete?',
            style: TextStyle(fontWeight: FontWeight.w800)),
        content: Text('Delete "${item.chargeName}"? This cannot be undone.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete',
                style: TextStyle(color: AppColors.red, fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    try {
      await OtChargeHeadService.instance.delete(item.id);
      await _load();
    } catch (e) {
      if (mounted) {
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
      _load();
    }
  }

  void _openSheet({OtChargeHeadItem? item}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (_) => _ChargeHeadSheet(
        item: item,
        accentColor: widget.accentColor,
        onSave: (chargeName, percentage, isActive) async {
          if (item == null) {
            await OtChargeHeadService.instance
                .create(chargeName, percentage, isActive);
          } else {
            await OtChargeHeadService.instance
                .update(item.id, chargeName, percentage, isActive);
          }
          await _load();
        },
      ),
    );
  }



  static String _fmtPct(double pct) {
    if (pct == pct.roundToDouble()) return '${pct.toInt()}%';
    return '${pct.toStringAsFixed(2)}%';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded,
              color: Colors.white, size: 20),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text(
          'Charge Heads',
          style: TextStyle(
            color: Colors.white,
            fontSize: 18,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.2,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.add_circle_outline_rounded, color: Colors.white),
            tooltip: 'Add',
            onPressed: () => _openSheet(),
          ),
        ],
      ),
      body: Column(
        children: [
          _buildSearch(),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildSearch() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 4),
      child: Container(
        height: 44,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(AppRadius.md),
          border: Border.all(color: AppColors.primary.withValues(alpha: 0.12)),
          boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.05), blurRadius: 8)],
        ),
        child: TextField(
          onChanged: (v) => setState(() => _query = v.trim()),
          style: const TextStyle(fontSize: 13.5, color: Color(0xFF1A3A52)),
          decoration: const InputDecoration(
            hintText: 'Search charge heads...',
            hintStyle: TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
            prefixIcon: Icon(Icons.search_rounded,
                color: Color(0xFF94A3B8), size: 20),
            border: InputBorder.none,
            contentPadding:
                EdgeInsets.symmetric(horizontal: 12, vertical: 12),
          ),
        ),
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return Center(child: CircularProgressIndicator(color: AppColors.primary));
    }
    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.error_outline_rounded,
                  size: 48, color: AppColors.red.withValues(alpha: 0.6)),
              const SizedBox(height: 12),
              Text(_error!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: Color(0xFF64748B))),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: _load,
                icon: const Icon(Icons.refresh_rounded),
                label: const Text('Retry'),
                style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary, foregroundColor: Colors.white),
              ),
            ],
          ),
        ),
      );
    }

    final items = _filtered;
    if (items.isEmpty) {
      return ListView(children: [
        const SizedBox(height: 72),
        Center(
          child: Column(children: [
            Icon(Icons.payments_rounded,
                size: 56, color: AppColors.primary.withValues(alpha: 0.15)),
            const SizedBox(height: 12),
            Text(
              _query.isNotEmpty
                  ? 'No results for "$_query"'
                  : 'No charge heads yet.\nTap + to add one.',
              textAlign: TextAlign.center,
              style: const TextStyle(
                  color: Color(0xFF94A3B8),
                  fontSize: 14,
                  fontWeight: FontWeight.w500),
            ),
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
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppColors.primary.withValues(alpha: 0.08)),
                boxShadow: [BoxShadow(
                  color: AppColors.primary.withValues(alpha: 0.05),
                  blurRadius: 8,
                  offset: const Offset(0, 2),
                )],
              ),
              child: Row(
                children: [
                  // Serial badge
                  Container(
                    width: 30, height: 30,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: widget.accentColor.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(AppRadius.sm),
                    ),
                    child: Text('${i + 1}',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w800,
                          color: widget.accentColor,
                        )),
                  ),
                  const SizedBox(width: 12),
                  // Charge name
                  Expanded(
                    child: Text(item.chargeName,
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: Color(0xFF1A3A52),
                        )),
                  ),
                  // Active chip
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 7, vertical: 3),
                    decoration: BoxDecoration(
                      color: item.isActive
                          ? AppColors.green.withValues(alpha: 0.10)
                          : Color(0xFF7F8C8D).withValues(alpha: 0.10),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      item.isActive ? 'Active' : 'Inactive',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        color: item.isActive ? AppColors.green : Color(0xFF7F8C8D),
                      ),
                    ),
                  ),
                  const SizedBox(width: 6),
                  // Percentage badge
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: widget.accentColor.withValues(alpha: 0.10),
                      borderRadius: BorderRadius.circular(AppRadius.sm),
                    ),
                    child: Text(
                      _fmtPct(item.percentage),
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: widget.accentColor,
                      ),
                    ),
                  ),
                  const SizedBox(width: 4),
                  // Edit
                  InkWell(
                    onTap: () => _openSheet(item: item),
                    borderRadius: BorderRadius.circular(AppRadius.sm),
                    child: const Padding(
                      padding: EdgeInsets.all(6),
                      child: Icon(Icons.edit_outlined,
                          size: 18, color: Color(0xFFE67E22)),
                    ),
                  ),
                  const SizedBox(width: 2),
                  // Delete
                  InkWell(
                    onTap: () => _delete(item),
                    borderRadius: BorderRadius.circular(AppRadius.sm),
                    child: const Padding(
                      padding: EdgeInsets.all(6),
                      child: Icon(Icons.delete_outline_rounded,
                          size: 18, color: AppColors.red),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

// ── Add / Edit sheet ──────────────────────────────────────────────────────────

class _ChargeHeadSheet extends StatefulWidget {
  final OtChargeHeadItem? item;
  final Color accentColor;
  final Future<void> Function(
      String chargeName, double percentage, bool isActive) onSave;

  const _ChargeHeadSheet({
    required this.item,
    required this.accentColor,
    required this.onSave,
  });

  @override
  State<_ChargeHeadSheet> createState() => _ChargeHeadSheetState();
}

class _ChargeHeadSheetState extends State<_ChargeHeadSheet> {
  late final TextEditingController _nameCtrl;
  late final TextEditingController _pctCtrl;
  late bool _isActive;
  bool _saving = false;
  String? _nameError;
  String? _pctError;

  @override
  void initState() {
    super.initState();
    _nameCtrl = TextEditingController(text: widget.item?.chargeName ?? '');
    _pctCtrl  = TextEditingController(
      text: widget.item != null
          ? (widget.item!.percentage == widget.item!.percentage.roundToDouble()
              ? '${widget.item!.percentage.toInt()}'
              : widget.item!.percentage.toStringAsFixed(2))
          : '',
    );
    _isActive = widget.item?.isActive ?? true;
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _pctCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final name    = _nameCtrl.text.trim();
    final pctText = _pctCtrl.text.trim();
    final pct     = double.tryParse(pctText);

    setState(() {
      _nameError = name.isEmpty ? 'Charge name is required' : null;
      _pctError  = pctText.isEmpty
          ? 'Percentage is required'
          : pct == null
              ? 'Enter a valid number'
              : pct < 0 || pct > 100
                  ? 'Must be between 0 and 100'
                  : null;
    });
    if (_nameError != null || _pctError != null) return;

    setState(() => _saving = true);
    try {
      await widget.onSave(name, pct!, _isActive);
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
          // Drag handle
          Container(
            margin: const EdgeInsets.only(top: 10, bottom: 4),
            width: 40, height: 4,
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.18),
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          // Header
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 10, 8, 14),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(7),
                  decoration: BoxDecoration(
                    color: widget.accentColor.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(Icons.payments_rounded,
                      size: 16, color: widget.accentColor),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    widget.item == null ? 'Add Charge Head' : 'Edit Charge Head',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF1A3A52),
                    ),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close_rounded,
                      size: 20, color: Color(0xFF94A3B8)),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
          ),
          // Charge name
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
            child: TextFormField(
              controller: _nameCtrl,
              autofocus: true,
              textCapitalization: TextCapitalization.words,
              decoration: InputDecoration(
                labelText: 'Charge Name *',
                errorText: _nameError,
                filled: true,
                fillColor: const Color(0xFFF0F6FB),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide: BorderSide.none,
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide:
                      BorderSide(color: widget.accentColor, width: 1.5),
                ),
              ),
            ),
          ),
          // Percentage
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 4),
            child: TextFormField(
              controller: _pctCtrl,
              keyboardType:
                  const TextInputType.numberWithOptions(decimal: true),
              inputFormatters: [
                FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d*')),
              ],
              decoration: InputDecoration(
                labelText: 'Percentage (0–100) *',
                errorText: _pctError,
                suffixText: '%',
                filled: true,
                fillColor: const Color(0xFFF0F6FB),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide: BorderSide.none,
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide:
                      BorderSide(color: widget.accentColor, width: 1.5),
                ),
              ),
            ),
          ),
          // Active toggle
          Padding(
            padding: const EdgeInsets.fromLTRB(8, 4, 16, 0),
            child: SwitchListTile(
              value: _isActive,
              onChanged: (v) => setState(() => _isActive = v),
              activeThumbColor: widget.accentColor,
              activeTrackColor: widget.accentColor.withValues(alpha: 0.4),
              title: const Text('Active',
                  style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: Color(0xFF1A3A52))),
              subtitle: Text(
                _isActive
                    ? 'This charge head is enabled'
                    : 'This charge head is disabled',
                style:
                    const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
              ),
              contentPadding: const EdgeInsets.symmetric(horizontal: 8),
            ),
          ),
          // Actions
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
            child: Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: _saving ? null : () => Navigator.pop(context),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(AppRadius.md)),
                    ),
                    child: const Text('Cancel'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  flex: 2,
                  child: ElevatedButton(
                    onPressed: _saving ? null : _save,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(AppRadius.md)),
                    ),
                    child: _saving
                        ? const SizedBox(
                            width: 18, height: 18,
                            child: CircularProgressIndicator(
                                color: Colors.white, strokeWidth: 2),
                          )
                        : const Text('Save',
                            style: TextStyle(fontWeight: FontWeight.w700)),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
