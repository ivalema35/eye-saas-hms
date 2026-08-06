import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../widgets/app_animations.dart';
import '../services/case_type_service.dart';

class CaseTypeMasterScreen extends StatefulWidget {
  final Color accentColor;

  const CaseTypeMasterScreen({
    super.key,
    this.accentColor = const Color(0xFF1B4F72),
  });

  @override
  State<CaseTypeMasterScreen> createState() => _CaseTypeMasterScreenState();
}

class _CaseTypeMasterScreenState extends State<CaseTypeMasterScreen> {
  List<CaseTypeItem> _all = [];
  bool _loading = false;
  String? _error;
  String _query = '';

  List<CaseTypeItem> get _filtered => _query.isEmpty
      ? _all
      : _all
          .where((i) => i.caseType.toLowerCase().contains(_query.toLowerCase()))
          .toList();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final items = await CaseTypeService.instance.fetchAll();
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

  Future<void> _delete(CaseTypeItem item) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
        title: const Text('Delete?', style: TextStyle(fontWeight: FontWeight.w800)),
        content: Text('Delete "${item.caseType}"? This cannot be undone.'),
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
      await CaseTypeService.instance.delete(item.id);
      await _load();
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  void _openSheet({CaseTypeItem? item}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (_) => _CaseTypeSheet(
        item: item,
        accentColor: widget.accentColor,
        onSave: (caseType, caseFee) async {
          if (item == null) {
            await CaseTypeService.instance.create(caseType, caseFee);
          } else {
            await CaseTypeService.instance.update(item.id, caseType, caseFee);
          }
          await _load();
        },
      ),
    );
  }



  static String _formatFee(double fee) {
    if (fee == fee.roundToDouble()) return '₹ ${fee.toInt()}';
    return '₹ ${fee.toStringAsFixed(2)}';
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
          'Case Types',
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
          style: const TextStyle(fontSize: 13.5, color: AppColors.darkNavy),
          decoration: const InputDecoration(
            hintText: 'Search case types...',
            hintStyle: TextStyle(color: AppColors.textDisabled, fontSize: 13),
            prefixIcon: Icon(Icons.search_rounded,
                color: AppColors.textDisabled, size: 20),
            border: InputBorder.none,
            contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 12),
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
                  style: const TextStyle(color: AppColors.textSecondary)),
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
            Icon(Icons.folder_open_rounded,
                size: 56, color: AppColors.primary.withValues(alpha: 0.15)),
            const SizedBox(height: 12),
            Text(
              _query.isNotEmpty
                  ? 'No results for "$_query"'
                  : 'No case types yet.\nTap + to add one.',
              textAlign: TextAlign.center,
              style: const TextStyle(
                  color: AppColors.textDisabled,
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
                  // Case type name
                  Expanded(
                    child: Text(item.caseType,
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: AppColors.darkNavy,
                        )),
                  ),
                  // Fee badge
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: AppColors.green.withValues(alpha: 0.10),
                      borderRadius: BorderRadius.circular(AppRadius.sm),
                    ),
                    child: Text(
                      _formatFee(item.caseFee),
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: AppColors.green,
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
                          size: 18, color: AppColors.orange),
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

class _CaseTypeSheet extends StatefulWidget {
  final CaseTypeItem? item;
  final Color accentColor;
  final Future<void> Function(String caseType, double caseFee) onSave;

  const _CaseTypeSheet({
    required this.item,
    required this.accentColor,
    required this.onSave,
  });

  @override
  State<_CaseTypeSheet> createState() => _CaseTypeSheetState();
}

class _CaseTypeSheetState extends State<_CaseTypeSheet> {
  late final TextEditingController _nameCtrl;
  late final TextEditingController _feeCtrl;
  bool _saving = false;
  String? _nameError;
  String? _feeError;

  @override
  void initState() {
    super.initState();
    _nameCtrl = TextEditingController(text: widget.item?.caseType ?? '');
    _feeCtrl  = TextEditingController(
      text: widget.item != null
          ? (widget.item!.caseFee == widget.item!.caseFee.roundToDouble()
              ? '${widget.item!.caseFee.toInt()}'
              : widget.item!.caseFee.toStringAsFixed(2))
          : '',
    );
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _feeCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final name = _nameCtrl.text.trim();
    final feeText = _feeCtrl.text.trim();
    final fee = double.tryParse(feeText);

    setState(() {
      _nameError = name.isEmpty ? 'Case type name is required' : null;
      _feeError = feeText.isEmpty
          ? 'Case fee is required'
          : fee == null
              ? 'Enter a valid number'
              : fee < 0
                  ? 'Fee cannot be negative'
                  : null;
    });
    if (_nameError != null || _feeError != null) return;

    setState(() => _saving = true);
    try {
      await widget.onSave(name, fee!);
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
                  child: Icon(Icons.edit_rounded,
                      size: 16, color: widget.accentColor),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    widget.item == null ? 'Add Case Type' : 'Edit Case Type',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w800,
                      color: AppColors.darkNavy,
                    ),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close_rounded,
                      size: 20, color: AppColors.textDisabled),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
          ),
          // Case type name
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
            child: TextFormField(
              controller: _nameCtrl,
              autofocus: true,
              textCapitalization: TextCapitalization.words,
              decoration: InputDecoration(
                labelText: 'Case Type Name',
                errorText: _nameError,
                filled: true,
                fillColor: AppColors.surfaceFill,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide: BorderSide.none,
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide: BorderSide(color: widget.accentColor, width: 1.5),
                ),
              ),
            ),
          ),
          // Case fee
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 0),
            child: TextFormField(
              controller: _feeCtrl,
              keyboardType:
                  const TextInputType.numberWithOptions(decimal: true),
              inputFormatters: [
                FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d*')),
              ],
              decoration: InputDecoration(
                labelText: 'Case Fee (₹)',
                errorText: _feeError,
                prefixText: '₹ ',
                filled: true,
                fillColor: AppColors.surfaceFill,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide: BorderSide.none,
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide: BorderSide(color: widget.accentColor, width: 1.5),
                ),
              ),
            ),
          ),
          // Actions
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 20, 16, 28),
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
