import 'dart:async';
import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/auth_models.dart';
import '../models/medicine_models.dart';
import '../services/medicine_service.dart';
import '../utils/app_route.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_error_state.dart';
import '../widgets/skeleton.dart';
import 'medicine_group_detail_screen.dart';
import 'medicine_group_form_screen.dart';

BoxDecoration _cardDeco() => BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(14),
      border: Border.all(color: AppColors.primaryA10),
      boxShadow: [BoxShadow(color: AppColors.primaryA06, blurRadius: 8, offset: const Offset(0, 2))],
    );



// ── Tab definitions ────────────────────────────────────────────────────────────

enum _Tab {
  dosages('Dosages', Icons.medication_outlined),
  types('Med. Types', Icons.label_outline_rounded),
  categories('Categories', Icons.grid_view_rounded),
  routes('Route/Admin', Icons.arrow_circle_right_outlined),
  medicines('Medicines', Icons.medication_liquid_outlined),
  groups('Med. Groups', Icons.collections_bookmark_outlined);

  final String label;
  final IconData icon;
  const _Tab(this.label, this.icon);
}

// ═════════════════════════════════════════════════════════════════════════════
// MAIN SCREEN
// ═════════════════════════════════════════════════════════════════════════════

class MedicinesScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final int initialTab;

  const MedicinesScreen({
    super.key,
    required this.user,
    required this.hospital,
    this.initialTab = 4,
  });

  @override
  State<MedicinesScreen> createState() => _MedicinesScreenState();
}

class _MedicinesScreenState extends State<MedicinesScreen>
    with TickerProviderStateMixin {
  late final TabController _tabCtrl;

  @override
  void initState() {
    super.initState();
    _tabCtrl = TabController(
      length: _Tab.values.length,
      vsync: this,
      initialIndex: widget.initialTab.clamp(0, _Tab.values.length - 1),
    );
  }

  @override
  void dispose() {
    _tabCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Color(0xFFF6FAFD),
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          'Medicine Master',
          style: TextStyle(fontWeight: FontWeight.w800, fontSize: 18),
        ),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(52),
          child: _buildTabBar(),
        ),
      ),
      body: TabBarView(
        controller: _tabCtrl,
        children: [
          _SimpleMasterTab(
            key: const ValueKey('dosages'),
            title: 'Dosages',
            subtitle: 'Reusable dosage instructions for prescriptions.',
            fieldLabel: 'Dosage',
            fieldHint: 'e.g. 1-0-0, TDS, BD',
            fetchAll: MedicineService.instance.fetchDosages,
            onCreate: (v) => MedicineService.instance.createDosage(v),
            onUpdate: (id, v) => MedicineService.instance.updateDosage(id, v),
            onDelete: (id) => MedicineService.instance.deleteDosage(id),
          ),
          _SimpleMasterTab(
            key: const ValueKey('types'),
            title: 'Medicine Types',
            subtitle: 'Classify medicines (e.g. Antibiotic, Lubricant, Steroid).',
            fieldLabel: 'Type Name',
            fieldHint: 'e.g. Antibiotic',
            fetchAll: MedicineService.instance.fetchTypes,
            onCreate: (v) => MedicineService.instance.createType(v),
            onUpdate: (id, v) => MedicineService.instance.updateType(id, v),
            onDelete: (id) => MedicineService.instance.deleteType(id),
          ),
          _SimpleMasterTab(
            key: const ValueKey('categories'),
            title: 'Medicine Categories',
            subtitle: 'Broad groupings (e.g. Eye Drops, Oral Tablets, Ointments).',
            fieldLabel: 'Category Name',
            fieldHint: 'e.g. Eye Drops',
            fetchAll: MedicineService.instance.fetchCategories,
            onCreate: (v) => MedicineService.instance.createCategory(v),
            onUpdate: (id, v) => MedicineService.instance.updateCategory(id, v),
            onDelete: (id) => MedicineService.instance.deleteCategory(id),
          ),
          _SimpleMasterTab(
            key: const ValueKey('routes'),
            title: 'Route of Administration',
            subtitle: 'How a medicine is administered.',
            fieldLabel: 'Route Name',
            fieldHint: 'e.g. Eye Drops, Oral, Topical',
            fetchAll: MedicineService.instance.fetchRoutes,
            onCreate: (v) => MedicineService.instance.createRoute(v),
            onUpdate: (id, v) => MedicineService.instance.updateRoute(id, v),
            onDelete: (id) => MedicineService.instance.deleteRoute(id),
          ),
          _MedicinesTab(key: const ValueKey('medicines')),
          _MedGroupsTab(
            key: const ValueKey('groups'),
            user: widget.user,
            hospital: widget.hospital,
          ),
        ],
      ),
    );
  }

  Widget _buildTabBar() {
    return AnimatedBuilder(
      animation: _tabCtrl,
      builder: (_, _) => SizedBox(
        height: 52,
        child: ListView(
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          children: _Tab.values.map((t) {
            final idx = t.index;
            final active = _tabCtrl.index == idx;
            return GestureDetector(
              onTap: () => _tabCtrl.animateTo(idx),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 180),
                margin: const EdgeInsets.only(right: 8),
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: active ? Colors.white : Colors.white.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(999),
                  border: Border.all(
                    color: active ? Colors.white : Colors.white.withValues(alpha: 0.30),
                  ),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(t.icon, size: 13, color: active ? AppColors.primary : Colors.white),
                    const SizedBox(width: 5),
                    Text(
                      t.label,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: active ? FontWeight.w800 : FontWeight.w600,
                        color: active ? AppColors.primary : Colors.white,
                      ),
                    ),
                  ],
                ),
              ),
            );
          }).toList(),
        ),
      ),
    );
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// SIMPLE MASTER TAB — shared by Dosages / Types / Categories / Routes
// ═════════════════════════════════════════════════════════════════════════════

typedef _FetchAll  = Future<List<MedMasterItem>> Function();
typedef _MutateOne = Future<void> Function(int id, String value);
typedef _CreateOne = Future<void> Function(String value);
typedef _DeleteOne = Future<void> Function(int id);

class _SimpleMasterTab extends StatefulWidget {
  final String title;
  final String subtitle;
  final String fieldLabel;
  final String fieldHint;
  final _FetchAll fetchAll;
  final _CreateOne onCreate;
  final _MutateOne onUpdate;
  final _DeleteOne onDelete;

  const _SimpleMasterTab({
    super.key,
    required this.title,
    required this.subtitle,
    required this.fieldLabel,
    required this.fieldHint,
    required this.fetchAll,
    required this.onCreate,
    required this.onUpdate,
    required this.onDelete,
  });

  @override
  State<_SimpleMasterTab> createState() => _SimpleMasterTabState();
}

class _SimpleMasterTabState extends State<_SimpleMasterTab>
    with AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;

  List<MedMasterItem> _items = [];
  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final items = await widget.fetchAll();
      if (mounted) setState(() { _items = items; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  void _openSheet({MedMasterItem? item}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.xl))),
      builder: (ctx) => _SingleFieldSheet(
        fieldLabel: widget.fieldLabel,
        fieldHint: widget.fieldHint,
        initialValue: item?.name ?? '',
        isEdit: item != null,
        onSave: (value) async {
          if (item == null) {
            await widget.onCreate(value);
          } else {
            await widget.onUpdate(item.id, value);
          }
          await _load();
        },
      ),
    );
  }

  Future<void> _confirmDelete(MedMasterItem item) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete?'),
        content: Text('Delete "${item.name}"?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete', style: TextStyle(color: Color(0xFFD94841))),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    try {
      await widget.onDelete(item.id);
      await _load();
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return RefreshIndicator(
      onRefresh: _load,
      color: AppColors.primary,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 14, 14, 90),
        children: [
          _SectionHeader(
            title: widget.title,
            subtitle: widget.subtitle,
            count: _items.length,
            onAdd: () => _openSheet(),
          ),
          const SizedBox(height: 12),
          if (_loading)
            const AppSkeletonList(count: 5, itemHeight: 68)
          else if (_error != null)
            AppErrorState(message: _error!, onRetry: _load)
          else if (_items.isEmpty)
            AppEmptyState(message: widget.title)
          else
            ...List.generate(_items.length, (i) {
              final item = _items[i];
              return Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 13),
                  decoration: _cardDeco(),
                  child: Row(
                    children: [
                      Container(
                        width: 32,
                        height: 32,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          color: AppColors.primaryA12,
                          borderRadius: BorderRadius.circular(AppRadius.sm),
                        ),
                        child: Text(
                          '${i + 1}',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w800,
                            color: AppColors.primary,
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          item.name,
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFF18324A),
                          ),
                        ),
                      ),
                      _IconBtn(
                        icon: Icons.edit_outlined,
                        color: AppColors.primary,
                        onTap: () => _openSheet(item: item),
                      ),
                      const SizedBox(width: 4),
                      _IconBtn(
                        icon: Icons.delete_outline_rounded,
                        color: Color(0xFFD94841),
                        onTap: () => _confirmDelete(item),
                      ),
                    ],
                  ),
                ),
              );
            }),
        ],
      ),
    );
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// MEDICINES TAB
// ═════════════════════════════════════════════════════════════════════════════

class _MedicinesTab extends StatefulWidget {
  const _MedicinesTab({super.key});

  @override
  State<_MedicinesTab> createState() => _MedicinesTabState();
}

class _MedicinesTabState extends State<_MedicinesTab>
    with AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;

  MedListResult? _result;
  bool _loading = false;
  String? _error;
  int _page = 1;
  final _searchCtrl = TextEditingController();
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _load({int? page}) async {
    final p = page ?? _page;
    setState(() { _loading = true; _error = null; });
    try {
      final result = await MedicineService.instance.fetchMedicines(
        search: _searchCtrl.text.trim(),
        page: p,
      );
      if (mounted) setState(() { _result = result; _page = p; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  void _onSearchChanged(String _) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () {
      setState(() => _page = 1);
      _load(page: 1);
    });
  }

  void _openSheet({MedItem? item}) {
    final types   = _result?.types ?? [];
    final dosages = _result?.dosages ?? [];
    if (types.isEmpty) {
      showAppSnackBar(context, 'Load medicines list first');
      return;
    }
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.xl))),
      builder: (ctx) => _MedFormSheet(
        types: types,
        dosages: dosages,
        item: item,
        onSave: (data) async {
          if (item == null) {
            await MedicineService.instance.createMedicine(data);
          } else {
            await MedicineService.instance.updateMedicine(item.id, data);
          }
          await _load(page: _page);
        },
      ),
    );
  }

  Future<void> _confirmDelete(MedItem item) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Medicine?'),
        content: Text('Delete "${item.name}"?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete', style: TextStyle(color: Color(0xFFD94841))),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    try {
      await MedicineService.instance.deleteMedicine(item.id);
      await _load(page: _page);
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    final result = _result;
    return RefreshIndicator(
      onRefresh: _load,
      color: AppColors.primary,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 14, 14, 90),
        children: [
          _SectionHeader(
            title: 'Medicines',
            subtitle: 'Manage medicine names, brands, companies, pricing, and types.',
            count: result?.meta.total,
            onAdd: () => _openSheet(),
          ),
          const SizedBox(height: 10),
          // Search bar
          Container(
            decoration: _cardDeco(),
            child: TextField(
              controller: _searchCtrl,
              onChanged: _onSearchChanged,
              style: const TextStyle(fontSize: 14, color: Color(0xFF18324A)),
              decoration: InputDecoration(
                hintText: 'Search name or company…',
                hintStyle: TextStyle(color: Color(0xFF6B7D93).withValues(alpha: 0.60), fontSize: 13),
                prefixIcon: const Icon(Icons.search_rounded, color: Color(0xFF6B7D93), size: 20),
                suffixIcon: _searchCtrl.text.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.close_rounded, size: 18, color: Color(0xFF6B7D93)),
                        onPressed: () {
                          _searchCtrl.clear();
                          setState(() => _page = 1);
                          _load(page: 1);
                        },
                      )
                    : null,
                border: InputBorder.none,
                contentPadding: const EdgeInsets.symmetric(vertical: 13),
              ),
            ),
          ),
          const SizedBox(height: 12),
          if (_loading && result == null)
            const AppSkeletonList(count: 5, itemHeight: 68)
          else if (_error != null && result == null)
            AppErrorState(message: _error!, onRetry: _load)
          else if (result != null) ...[
            if (result.items.isEmpty)
              AppEmptyState(message: _searchCtrl.text.isNotEmpty ? 'No results for "${_searchCtrl.text}"' : 'No medicines yet')
            else ...[
              ...List.generate(result.items.length, (i) {
                final m = result.items[i];
                return AnimatedListItem(
                  index: i,
                  child: RepaintBoundary(
                    key: ValueKey(m.id),
                    child: Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: _MedCard(item: m, onEdit: () => _openSheet(item: m), onDelete: () => _confirmDelete(m)),
                    ),
                  ),
                );
              }),
              const SizedBox(height: 4),
              _PaginationRow(
                meta: result.meta,
                loading: _loading,
                onPrev: () => _load(page: _page - 1),
                onNext: () => _load(page: _page + 1),
              ),
            ],
          ],
        ],
      ),
    );
  }
}

class _MedCard extends StatelessWidget {
  final MedItem item;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _MedCard({required this.item, required this.onEdit, required this.onDelete});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: _cardDeco(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  item.name,
                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Color(0xFF18324A)),
                ),
              ),
              if (item.price != null)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(color: Color(0xFF1F9D55).withValues(alpha: 0.12), borderRadius: BorderRadius.circular(AppRadius.xl)),
                  child: Text(
                    '₹${item.price!.toStringAsFixed(2)}',
                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF1F9D55)),
                  ),
                ),
              const SizedBox(width: 6),
              _IconBtn(icon: Icons.edit_outlined, color: AppColors.primary, onTap: onEdit),
              const SizedBox(width: 4),
              _IconBtn(icon: Icons.delete_outline_rounded, color: Color(0xFFD94841), onTap: onDelete),
            ],
          ),
          const SizedBox(height: 6),
          Wrap(
            spacing: 10,
            runSpacing: 4,
            children: [
              if (item.medicineTypeName != null)
                _InfoChip(Icons.label_outline_rounded, item.medicineTypeName!),
              if (item.dosageText != null)
                _InfoChip(Icons.medication_outlined, item.dosageText!),
              if (item.duration != null)
                _InfoChip(Icons.timer_outlined, item.duration!),
              if (item.qty != null)
                _InfoChip(Icons.inventory_2_outlined, item.qty!),
              if (item.company != null)
                _InfoChip(Icons.business_outlined, item.company!),
            ],
          ),
        ],
      ),
    );
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// MEDICINE GROUPS TAB
// ═════════════════════════════════════════════════════════════════════════════

class _MedGroupsTab extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;

  const _MedGroupsTab({super.key, required this.user, required this.hospital});

  @override
  State<_MedGroupsTab> createState() => _MedGroupsTabState();
}

class _MedGroupsTabState extends State<_MedGroupsTab>
    with AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;

  MedGroupListResult? _result;
  bool _loading = false;
  String? _error;
  int _page = 1;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load({int? page}) async {
    final p = page ?? _page;
    setState(() { _loading = true; _error = null; });
    try {
      final r = await MedicineService.instance.fetchGroups(page: p);
      if (mounted) setState(() { _result = r; _page = p; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  Future<void> _pushForm({MedGroup? group}) async {
    final refreshed = await Navigator.push<bool>(
      context,
      appRoute(MedicineGroupFormScreen(
        user: widget.user,
        hospital: widget.hospital,
        existing: group,
      ),
      ),
    );
    if (refreshed == true && mounted) _load(page: _page);
  }

  Future<void> _confirmDelete(MedGroup group) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Group?'),
        content: Text('Delete "${group.name}"?\nAll medicine rows will be removed.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete', style: TextStyle(color: Color(0xFFD94841))),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    try {
      await MedicineService.instance.deleteGroup(group.id);
      await _load(page: _page);
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    final result = _result;
    return RefreshIndicator(
      onRefresh: _load,
      color: AppColors.primary,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 14, 14, 90),
        children: [
          _SectionHeader(
            title: 'Prescription Groups',
            subtitle: 'Reusable medicine sets for faster prescriptions.',
            count: result?.meta.total,
            onAdd: () => _pushForm(),
          ),
          const SizedBox(height: 12),
          if (_loading && result == null)
            const AppSkeletonList(count: 5, itemHeight: 68)
          else if (_error != null && result == null)
            AppErrorState(message: _error!, onRetry: _load)
          else if (result != null) ...[
            if (result.groups.isEmpty)
              const AppEmptyState(message: 'No medicine groups yet')
            else ...[
              ...List.generate(result.groups.length, (i) {
                final g = result.groups[i];
                return AnimatedListItem(
                  index: i,
                  child: RepaintBoundary(
                    key: ValueKey(g.id),
                    child: Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: _GroupCard(
                        group: g,
                        onView: () => Navigator.push(
                          context,
                          appRoute(MedicineGroupDetailScreen(
                            user: widget.user,
                            hospital: widget.hospital,
                            groupId: g.id,
                          )),
                        ),
                        onEdit: () => _pushForm(group: g),
                        onDelete: () => _confirmDelete(g),
                      ),
                    ),
                  ),
                );
              }),
              _PaginationRow(
                meta: result.meta,
                loading: _loading,
                onPrev: () => _load(page: _page - 1),
                onNext: () => _load(page: _page + 1),
              ),
            ],
          ],
        ],
      ),
    );
  }
}

class _GroupCard extends StatelessWidget {
  final MedGroup group;
  final VoidCallback onView;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _GroupCard({required this.group, required this.onView, required this.onEdit, required this.onDelete});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: _cardDeco(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(group.name, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Color(0xFF18324A))),
                    if (group.groupCode != null) ...[
                      const SizedBox(height: 2),
                      Text(group.groupCode!, style: TextStyle(fontSize: 11, color: Color(0xFF6B7D93).withValues(alpha: 0.60), fontWeight: FontWeight.w600)),
                    ],
                  ],
                ),
              ),
              _IconBtn(icon: Icons.visibility_outlined, color: const Color(0xFF2C6E99), onTap: onView),
              const SizedBox(width: 4),
              _IconBtn(icon: Icons.edit_outlined, color: AppColors.primary, onTap: onEdit),
              const SizedBox(width: 4),
              _IconBtn(icon: Icons.delete_outline_rounded, color: Color(0xFFD94841), onTap: onDelete),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              if (group.diagnosisValue != null)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  margin: const EdgeInsets.only(right: 8),
                  decoration: BoxDecoration(
                    color: Color(0xFF1F9D55).withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(AppRadius.xl),
                  ),
                  child: Text(
                    group.diagnosisValue!,
                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFF1F9D55)),
                  ),
                ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: AppColors.primaryA12,
                  borderRadius: BorderRadius.circular(AppRadius.xl),
                ),
                child: Text(
                  '${group.itemsCount} medicine${group.itemsCount == 1 ? '' : 's'}',
                  style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.primary),
                ),
              ),
            ],
          ),
          if (group.items.isNotEmpty) ...[
            const SizedBox(height: 8),
            const Divider(height: 1),
            const SizedBox(height: 8),
            ...group.items.take(3).map((item) => Padding(
              padding: const EdgeInsets.only(bottom: 4),
              child: Row(
                children: [
                  const Icon(Icons.medication_outlined, size: 12, color: Color(0xFF6B7D93)),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      item.medicineName ?? '-',
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF18324A)),
                    ),
                  ),
                  if (item.dosageText != null)
                    Text(item.dosageText!, style: TextStyle(fontSize: 11, color: Color(0xFF6B7D93).withValues(alpha: 0.60))),
                  if (item.duration != null)
                    Padding(
                      padding: const EdgeInsets.only(left: 8),
                      child: Text(item.duration!, style: TextStyle(fontSize: 11, color: Color(0xFF6B7D93).withValues(alpha: 0.60))),
                    ),
                ],
              ),
            )),
            if (group.itemsCount > 3)
              Padding(
                padding: const EdgeInsets.only(top: 2),
                child: Text(
                  '+${group.itemsCount - 3} more — tap View',
                  style: TextStyle(fontSize: 11, color: Color(0xFF6B7D93).withValues(alpha: 0.60), fontStyle: FontStyle.italic),
                ),
              ),
          ],
        ],
      ),
    );
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// SHARED FORM WIDGETS
// ═════════════════════════════════════════════════════════════════════════════

class _SingleFieldSheet extends StatefulWidget {
  final String fieldLabel;
  final String fieldHint;
  final String initialValue;
  final bool isEdit;
  final Future<void> Function(String value) onSave;

  const _SingleFieldSheet({
    required this.fieldLabel,
    required this.fieldHint,
    required this.initialValue,
    required this.isEdit,
    required this.onSave,
  });

  @override
  State<_SingleFieldSheet> createState() => _SingleFieldSheetState();
}

class _SingleFieldSheetState extends State<_SingleFieldSheet> {
  late final TextEditingController _ctrl;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _ctrl = TextEditingController(text: widget.initialValue);
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (_ctrl.text.trim().isEmpty) return;
    setState(() => _saving = true);
    try {
      await widget.onSave(_ctrl.text.trim());
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
    return Padding(
      padding: EdgeInsets.only(
        left: 20, right: 20, top: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(
                widget.isEdit ? 'Edit ${widget.fieldLabel}' : 'Add ${widget.fieldLabel}',
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Color(0xFF18324A)),
              ),
              const Spacer(),
              IconButton(
                onPressed: () => Navigator.pop(context),
                icon: const Icon(Icons.close_rounded, color: Color(0xFF6B7D93)),
                visualDensity: VisualDensity.compact,
              ),
            ],
          ),
          const SizedBox(height: 14),
          TextField(
            controller: _ctrl,
            autofocus: true,
            textCapitalization: TextCapitalization.sentences,
            decoration: InputDecoration(
              labelText: '${widget.fieldLabel} *',
              hintText: widget.fieldHint,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(10),
                borderSide: BorderSide(color: AppColors.primary, width: 2),
              ),
            ),
            onSubmitted: (_) => _save(),
          ),
          const SizedBox(height: 16),
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
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : Text(
                      widget.isEdit ? 'Update' : 'Save',
                      style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
                    ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Medicine Add/Edit bottom sheet ───────────────────────────────────────────

class _MedFormSheet extends StatefulWidget {
  final List<MedMasterItem> types;
  final List<MedMasterItem> dosages;
  final MedItem? item;
  final Future<void> Function(Map<String, dynamic> data) onSave;

  const _MedFormSheet({
    required this.types,
    required this.dosages,
    this.item,
    required this.onSave,
  });

  @override
  State<_MedFormSheet> createState() => _MedFormSheetState();
}

class _MedFormSheetState extends State<_MedFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late int? _typeId;
  late int? _dosageId;
  late final TextEditingController _name, _duration, _qty, _company, _composition, _price;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final m = widget.item;
    _typeId   = m?.medicineTypeId;
    _dosageId = m?.dosageId;
    _name        = TextEditingController(text: m?.name ?? '');
    _duration    = TextEditingController(text: m?.duration ?? '');
    _qty         = TextEditingController(text: m?.qty ?? '');
    _company     = TextEditingController(text: m?.company ?? '');
    _composition = TextEditingController(text: m?.composition ?? '');
    _price       = TextEditingController(text: m?.price?.toStringAsFixed(2) ?? '0.00');
  }

  @override
  void dispose() {
    _name.dispose(); _duration.dispose(); _qty.dispose();
    _company.dispose(); _composition.dispose(); _price.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    setState(() => _saving = true);
    try {
      await widget.onSave({
        'medicine_type_id': _typeId,
        'name':             _name.text.trim(),
        'dosage_id':        _dosageId,
        'duration':         _duration.text.trim(),
        'qty':              _qty.text.trim(),
        'company':          _company.text.trim().isEmpty ? null : _company.text.trim(),
        'composition':      _composition.text.trim().isEmpty ? null : _composition.text.trim(),
        'price':            double.tryParse(_price.text) ?? 0.0,
      });
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
    final isEdit = widget.item != null;
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: DraggableScrollableSheet(
        expand: false,
        initialChildSize: 0.90,
        minChildSize: 0.60,
        maxChildSize: 0.95,
        builder: (_, ctrl) => Form(
          key: _formKey,
          child: ListView(
            controller: ctrl,
            padding: const EdgeInsets.fromLTRB(20, 20, 20, 20),
            children: [
              Row(
                children: [
                  Text(
                    isEdit ? 'Edit Medicine' : 'Add Medicine',
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Color(0xFF18324A)),
                  ),
                  const Spacer(),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close_rounded, color: Color(0xFF6B7D93)),
                    visualDensity: VisualDensity.compact,
                  ),
                ],
              ),
              const SizedBox(height: 16),
              _label('Medicine Type *'),
              _Dropdown<int>(
                hint: 'Select type',
                value: _typeId,
                items: widget.types.map((t) => DropdownMenuItem(value: t.id, child: Text(t.name))).toList(),
                onChanged: (v) => setState(() => _typeId = v),
                validator: (v) => v == null ? 'Required' : null,
              ),
              const SizedBox(height: 12),
              _label('Medicine Name *'),
              _Field(ctrl: _name, hint: 'Add Tab / Caps / Oint at end', validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null),
              const SizedBox(height: 12),
              _label('Dosage *'),
              _Dropdown<int>(
                hint: 'Select dosage',
                value: _dosageId,
                items: widget.dosages.map((d) => DropdownMenuItem(value: d.id, child: Text(d.name))).toList(),
                onChanged: (v) => setState(() => _dosageId = v),
                validator: (v) => v == null ? 'Required' : null,
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    _label('Duration *'),
                    _Field(ctrl: _duration, hint: 'e.g. 4 days', validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null),
                  ])),
                  const SizedBox(width: 12),
                  Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    _label('Qty *'),
                    _Field(ctrl: _qty, hint: 'e.g. 10 tablets', validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null),
                  ])),
                ],
              ),
              const SizedBox(height: 12),
              _label('Company'),
              _Field(ctrl: _company, hint: 'e.g. Alcon, Sun Pharma'),
              const SizedBox(height: 12),
              _label('Composition'),
              _Field(ctrl: _composition, hint: 'e.g. Moxifloxacin 0.5% w/v', maxLines: 2),
              const SizedBox(height: 12),
              _label('Price (₹)'),
              _Field(ctrl: _price, hint: '0.00', keyboardType: const TextInputType.numberWithOptions(decimal: true)),
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
                      ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : Text(isEdit ? 'Update Medicine' : 'Save Medicine',
                          style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// SHARED SMALL WIDGETS
// ═════════════════════════════════════════════════════════════════════════════

class _SectionHeader extends StatelessWidget {
  final String title;
  final String subtitle;
  final int? count;
  final VoidCallback onAdd;

  const _SectionHeader({required this.title, required this.subtitle, this.count, required this.onAdd});

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: TextStyle(fontSize: 15, fontWeight: FontWeight.w900, color: AppColors.primary)),
              const SizedBox(height: 2),
              Text(subtitle, style: TextStyle(fontSize: 11, color: Color(0xFF6B7D93).withValues(alpha: 0.60))),
            ],
          ),
        ),
        if (count != null)
          Container(
            margin: const EdgeInsets.only(right: 8, top: 2),
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(color: AppColors.primaryA12, borderRadius: BorderRadius.circular(AppRadius.xl)),
            child: Text('$count', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: AppColors.primary)),
          ),
        ElevatedButton.icon(
          onPressed: onAdd,
          icon: const Icon(Icons.add_rounded, size: 15),
          label: const Text('Add', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800)),
          style: ElevatedButton.styleFrom(
            backgroundColor: AppColors.primary,
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            elevation: 0,
          ),
        ),
      ],
    );
  }
}

class _PaginationRow extends StatelessWidget {
  final MedMeta meta;
  final bool loading;
  final VoidCallback onPrev;
  final VoidCallback onNext;

  const _PaginationRow({required this.meta, required this.loading, required this.onPrev, required this.onNext});

  @override
  Widget build(BuildContext context) {
    if (meta.lastPage <= 1) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(top: 8),
      child: Row(
        children: [
          Expanded(
            child: OutlinedButton.icon(
              onPressed: (meta.hasPrev && !loading) ? onPrev : null,
              icon: const Icon(Icons.arrow_back_ios_rounded, size: 13),
              label: const Text('Prev'),
              style: OutlinedButton.styleFrom(
                foregroundColor: AppColors.primary,
                side: BorderSide(color: AppColors.primaryA10),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14),
            child: loading
                ? SizedBox(width: 16, height: 16, child: CircularProgressIndicator(color: AppColors.primary, strokeWidth: 2))
                : Text('${meta.currentPage} / ${meta.lastPage}',
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: AppColors.primary)),
          ),
          Expanded(
            child: OutlinedButton.icon(
              onPressed: (meta.hasNext && !loading) ? onNext : null,
              icon: const Icon(Icons.arrow_forward_ios_rounded, size: 13),
              label: const Text('Next'),
              iconAlignment: IconAlignment.end,
              style: OutlinedButton.styleFrom(
                foregroundColor: AppColors.primary,
                side: BorderSide(color: AppColors.primaryA10),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _IconBtn extends StatelessWidget {
  final IconData icon;
  final Color color;
  final VoidCallback onTap;

  const _IconBtn({required this.icon, required this.color, required this.onTap});

  @override
  Widget build(BuildContext context) => InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppRadius.sm),
        child: Container(
          padding: const EdgeInsets.all(6),
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.10),
            borderRadius: BorderRadius.circular(AppRadius.sm),
          ),
          child: Icon(icon, size: 16, color: color),
        ),
      );
}

class _InfoChip extends StatelessWidget {
  final IconData icon;
  final String text;
  const _InfoChip(this.icon, this.text);

  @override
  Widget build(BuildContext context) => Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 11, color: Color(0xFF6B7D93)),
          const SizedBox(width: 3),
          Text(text, style: TextStyle(fontSize: 11, color: Color(0xFF6B7D93).withValues(alpha: 0.60), fontWeight: FontWeight.w600)),
        ],
      );
}

// ── Form helpers ──────────────────────────────────────────────────────────────

Widget _label(String text) => Padding(
      padding: const EdgeInsets.only(bottom: 5),
      child: Text(text, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFF6B7D93))),
    );

class _Field extends StatelessWidget {
  final TextEditingController ctrl;
  final String? hint;
  final int maxLines;
  final TextInputType? keyboardType;
  final String? Function(String?)? validator;

  const _Field({required this.ctrl, this.hint, this.maxLines = 1, this.keyboardType, this.validator});

  @override
  Widget build(BuildContext context) => TextFormField(
        controller: ctrl,
        maxLines: maxLines,
        keyboardType: keyboardType,
        style: const TextStyle(fontSize: 13, color: Color(0xFF18324A)),
        validator: validator,
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

class _Dropdown<T> extends StatelessWidget {
  final String hint;
  final T? value;
  final List<DropdownMenuItem<T>> items;
  final ValueChanged<T?> onChanged;
  final String? Function(T?)? validator;

  const _Dropdown({required this.hint, required this.value, required this.items, required this.onChanged, this.validator});

  @override
  Widget build(BuildContext context) => DropdownButtonFormField<T>(
        value: value,
        hint: Text(hint, style: TextStyle(fontSize: 12, color: Color(0xFF6B7D93).withValues(alpha: 0.60))),
        items: items,
        onChanged: onChanged,
        validator: validator,
        isExpanded: true,
        dropdownColor: Colors.white,
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
      );
}
