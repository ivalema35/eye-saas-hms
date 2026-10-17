import 'package:flutter/material.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_refresh_indicator.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/permissions.dart';
import '../services/permission_service.dart';
import '../widgets/app_animations.dart';
import '../services/duration_service.dart';
import '../widgets/skeleton.dart';

class DurationMasterScreen extends StatefulWidget {
  final Color accentColor;

  const DurationMasterScreen({
    super.key,
    this.accentColor = const Color(0xFF7F8C8D),
  });

  @override
  State<DurationMasterScreen> createState() => _DurationMasterScreenState();
}

class _DurationMasterScreenState extends State<DurationMasterScreen> {
  final _p = PermissionService.instance;
  List<DurationItem> _all = [];
  bool _loading = false;
  String? _error;
  String _query = '';

  bool get _canAdd => _p.can(Perm.masterDurationAdd);
  bool get _canEdit => _p.can(Perm.masterDurationEdit);
  bool get _canDelete => _p.can(Perm.masterDurationDelete);

  List<DurationItem> get _filtered => _query.isEmpty
      ? _all
      : _all
          .where((i) => i.duration.toLowerCase().contains(_query.toLowerCase()))
          .toList();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final items = await DurationService.instance.fetchAll();
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

  Future<void> _delete(DurationItem item) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
        title: const Text('Delete?', style: TextStyle(fontWeight: FontWeight.w800)),
        content: Text('Delete "${item.duration}"? This cannot be undone.'),
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
      await DurationService.instance.delete(item.id);
      await _load();
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  void _openSheet({DurationItem? item}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (_) => _DurationSheet(
        item: item,
        accentColor: widget.accentColor,
        onSave: (duration) async {
          if (item == null) {
            await DurationService.instance.create(duration);
          } else {
            await DurationService.instance.update(item.id, duration);
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
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded,
              color: Colors.white, size: 20),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text(
          'Durations',
          style: TextStyle(
            color: Colors.white,
            fontSize: 18,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.2,
          ),
        ),
        actions: [
          if (_canAdd)
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
            hintText: 'Search durations...',
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
      return const AppSkeletonList();
    }
    if (_error != null) {
      return AppErrorState(message: _error!, onRetry: _load);
    }

    final items = _filtered;
    if (items.isEmpty) {
      return ListView(children: [
        const SizedBox(height: 72),
        Center(
          child: Column(children: [
            Icon(Icons.hourglass_bottom_rounded,
                size: 56, color: AppColors.primary.withValues(alpha: 0.15)),
            const SizedBox(height: 12),
            Text(
              _query.isNotEmpty
                  ? 'No results for "$_query"'
                  : (_canAdd ? 'No durations yet.\nTap + to add one.' : 'No durations yet.'),
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

    return AppRefreshIndicator(
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
                  Expanded(
                    child: Text(item.duration,
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: AppColors.darkNavy,
                        )),
                  ),
                  if (_canEdit)
                    InkWell(
                      onTap: () => _openSheet(item: item),
                      borderRadius: BorderRadius.circular(AppRadius.sm),
                      child: const Padding(
                        padding: EdgeInsets.all(6),
                        child: Icon(Icons.edit_outlined,
                            size: 18, color: AppColors.orange),
                      ),
                    ),
                  if (_canDelete) const SizedBox(width: 2),
                  if (_canDelete)
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

class _DurationSheet extends StatefulWidget {
  final DurationItem? item;
  final Color accentColor;
  final Future<void> Function(String duration) onSave;

  const _DurationSheet({
    required this.item,
    required this.accentColor,
    required this.onSave,
  });

  @override
  State<_DurationSheet> createState() => _DurationSheetState();
}

class _DurationSheetState extends State<_DurationSheet> {
  late final TextEditingController _nameCtrl;
  bool _saving = false;
  String? _nameError;

  @override
  void initState() {
    super.initState();
    _nameCtrl = TextEditingController(text: widget.item?.duration ?? '');
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final name = _nameCtrl.text.trim();

    setState(() {
      _nameError = name.isEmpty ? 'Duration is required' : null;
    });
    if (_nameError != null) return;

    setState(() => _saving = true);
    try {
      await widget.onSave(name);
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
          Container(
            margin: const EdgeInsets.only(top: 10, bottom: 4),
            width: 40, height: 4,
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.18),
              borderRadius: BorderRadius.circular(2),
            ),
          ),
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
                    widget.item == null ? 'Add Duration' : 'Edit Duration',
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
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 0),
            child: TextFormField(
              controller: _nameCtrl,
              autofocus: true,
              textCapitalization: TextCapitalization.words,
              decoration: InputDecoration(
                labelText: 'Duration (e.g. 1 Week, 15 Days)',
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
