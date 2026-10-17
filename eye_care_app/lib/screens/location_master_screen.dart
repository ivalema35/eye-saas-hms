import 'package:flutter/material.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_refresh_indicator.dart';
import '../constants/app_colors.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_empty_state.dart';
import '../constants/app_radius.dart';
import '../constants/permissions.dart';
import '../services/permission_service.dart';
import '../services/location_service.dart';
import '../services/masters_service.dart';
import '../widgets/skeleton.dart';

class LocationMasterScreen extends StatefulWidget {
  final Color accentColor;

  const LocationMasterScreen({
    super.key,
    this.accentColor = AppColors.green,
  });

  @override
  State<LocationMasterScreen> createState() => _LocationMasterScreenState();
}

class _LocationMasterScreenState extends State<LocationMasterScreen> {
  final _p = PermissionService.instance;
  List<LocationItem> _all = [];
  bool _loading = false;
  String? _error;
  String _query = '';

  bool get _canAdd => _p.can(Perm.masterLocationAdd);
  bool get _canEdit => _p.can(Perm.masterLocationEdit);
  bool get _canDelete => _p.can(Perm.masterLocationDelete);

  List<LocationItem> get _filtered {
    if (_query.isEmpty) return _all;
    final q = _query.toLowerCase();
    return _all.where((i) =>
        i.city.toLowerCase().contains(q) ||
        (i.district ?? '').toLowerCase().contains(q) ||
        (i.state ?? '').toLowerCase().contains(q)).toList();
  }

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final items = await LocationService.instance.fetchAll();
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

  void _openSheet({LocationItem? item}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (_) => _LocationSheet(
        item: item,
        accentColor: widget.accentColor,
        onSave: (city, state, district) async {
          if (item == null) {
            await LocationService.instance.create(city: city, state: state, district: district);
          } else {
            await LocationService.instance.update(item.id, city: city, state: state, district: district);
          }
          MastersService.instance.clearCache();
          await _load();
        },
      ),
    );
  }

  Future<void> _delete(LocationItem item) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
        title: const Text('Delete?', style: TextStyle(fontWeight: FontWeight.w800)),
        content: Text('Delete "${item.city}"? This cannot be undone.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete', style: TextStyle(color: AppColors.red, fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    try {
      await LocationService.instance.delete(item.id);
      MastersService.instance.clearCache();
      await _load();
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      _load();
    }
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
          'Locations',
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
              tooltip: 'Add Location',
              onPressed: () => _openSheet(),
            ),
        ],
      ),
      body: Column(
        children: [
          _buildSearch(),
          _buildCount(),
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
          border: Border.all(color: AppColors.primaryA12),
          boxShadow: [BoxShadow(color: AppColors.primaryA05, blurRadius: 8)],
        ),
        child: TextField(
          onChanged: (v) => setState(() => _query = v.trim()),
          style: const TextStyle(fontSize: 13.5, color: AppColors.darkNavy),
          decoration: const InputDecoration(
            hintText: 'Search city, district, or state...',
            hintStyle: TextStyle(color: AppColors.textDisabled, fontSize: 13),
            prefixIcon: Icon(Icons.search_rounded,
                color: AppColors.textDisabled, size: 20),
            border: InputBorder.none,
            contentPadding:
                EdgeInsets.symmetric(horizontal: 12, vertical: 12),
          ),
        ),
      ),
    );
  }

  Widget _buildCount() {
    if (_loading || _error != null) return const SizedBox.shrink();
    final count = _filtered.length;
    final total = _all.length;
    final label = _query.isNotEmpty
        ? '$count of $total locations'
        : '$total locations';
    return Padding(
      padding: const EdgeInsets.fromLTRB(18, 6, 18, 2),
      child: Align(
        alignment: Alignment.centerLeft,
        child: Text(label,
            style: TextStyle(
              fontSize: 11.5,
              color: AppColors.primaryA45,
              fontWeight: FontWeight.w500,
            )),
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
        AppEmptyState(
          message: _query.isNotEmpty
              ? 'No results for "$_query"'
              : (_canAdd ? 'No locations yet.\nTap + to add one.' : 'No locations yet.'),
          icon: Icons.location_on_rounded,
        ),
      ]);
    }

    return AppRefreshIndicator(
      color: AppColors.primary,
      onRefresh: _load,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 4, 14, 40),
        itemCount: items.length,
        itemBuilder: (_, i) {
          final item = items[i];
          final sub = item.subtitle;
          return Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Container(
              padding: const EdgeInsets.fromLTRB(12, 11, 8, 11),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppColors.primaryA08),
                boxShadow: [BoxShadow(
                  color: AppColors.primaryA05,
                  blurRadius: 8,
                  offset: const Offset(0, 2),
                )],
              ),
              child: Row(
                children: [
                  // Icon badge
                  Container(
                    width: 36, height: 36,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: widget.accentColor.withValues(alpha: 0.10),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Icon(Icons.location_on_rounded,
                        size: 18, color: widget.accentColor),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(item.city,
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w700,
                              color: AppColors.darkNavy,
                            )),
                        if (sub.isNotEmpty) ...[
                          const SizedBox(height: 2),
                          Text(sub,
                              style: TextStyle(
                                fontSize: 11.5,
                                color: AppColors.primaryA50,
                                fontWeight: FontWeight.w500,
                              )),
                        ],
                      ],
                    ),
                  ),
                  if (_canEdit)
                    InkWell(
                      onTap: () => _openSheet(item: item),
                      borderRadius: BorderRadius.circular(AppRadius.sm),
                      child: Padding(
                        padding: const EdgeInsets.all(6),
                        child: Icon(Icons.edit_outlined, size: 18, color: widget.accentColor),
                      ),
                    ),
                  if (_canDelete) const SizedBox(width: 2),
                  if (_canDelete)
                    InkWell(
                      onTap: () => _delete(item),
                      borderRadius: BorderRadius.circular(AppRadius.sm),
                      child: const Padding(
                        padding: EdgeInsets.all(6),
                        child: Icon(Icons.delete_outline_rounded, size: 18, color: AppColors.red),
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

class _LocationSheet extends StatefulWidget {
  final LocationItem? item;
  final Color accentColor;
  final Future<void> Function(String city, String state, String? district) onSave;

  const _LocationSheet({
    required this.item,
    required this.accentColor,
    required this.onSave,
  });

  @override
  State<_LocationSheet> createState() => _LocationSheetState();
}

class _LocationSheetState extends State<_LocationSheet> {
  late final TextEditingController _cityCtrl;
  late final TextEditingController _stateCtrl;
  late final TextEditingController _districtCtrl;
  bool _saving = false;
  String? _cityError;
  String? _stateError;

  @override
  void initState() {
    super.initState();
    _cityCtrl = TextEditingController(text: widget.item?.city ?? '');
    _stateCtrl = TextEditingController(text: widget.item?.state ?? '');
    _districtCtrl = TextEditingController(text: widget.item?.district ?? '');
  }

  @override
  void dispose() {
    _cityCtrl.dispose();
    _stateCtrl.dispose();
    _districtCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final city = _cityCtrl.text.trim();
    final state = _stateCtrl.text.trim();
    final district = _districtCtrl.text.trim();

    setState(() {
      _cityError = city.isEmpty ? 'City is required' : null;
      _stateError = state.isEmpty ? 'State is required' : null;
    });
    if (_cityError != null || _stateError != null) return;

    setState(() => _saving = true);
    try {
      await widget.onSave(city, state, district.isEmpty ? null : district);
      if (mounted) Navigator.pop(context);
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
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide(color: widget.accentColor, width: 1.5)),
      );

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
                  child: Icon(Icons.location_on_rounded, size: 16, color: widget.accentColor),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    widget.item == null ? 'Add Location' : 'Edit Location',
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.darkNavy),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close_rounded, size: 20, color: AppColors.textDisabled),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
            child: TextFormField(
              controller: _cityCtrl,
              autofocus: true,
              textCapitalization: TextCapitalization.words,
              decoration: _deco('City *', error: _cityError),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
            child: TextFormField(
              controller: _districtCtrl,
              textCapitalization: TextCapitalization.words,
              decoration: _deco('District (optional)'),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 0),
            child: TextFormField(
              controller: _stateCtrl,
              textCapitalization: TextCapitalization.words,
              decoration: _deco('State *', error: _stateError),
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
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
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
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
                    ),
                    child: _saving
                        ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : const Text('Save', style: TextStyle(fontWeight: FontWeight.w700)),
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
