import 'package:flutter/material.dart';
import '../widgets/app_error_state.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/auth_models.dart';
import '../models/medicine_models.dart';
import '../services/medicine_service.dart';
import '../utils/app_route.dart';
import 'medicine_group_form_screen.dart';

BoxDecoration _cardDeco() => BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(14),
      border: Border.all(color: AppColors.primaryA10),
      boxShadow: [BoxShadow(color: AppColors.primaryA06, blurRadius: 8, offset: const Offset(0, 2))],
    );

// ═════════════════════════════════════════════════════════════════════════════
// DETAIL SCREEN
// ═════════════════════════════════════════════════════════════════════════════

class MedicineGroupDetailScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final int groupId;

  const MedicineGroupDetailScreen({
    super.key,
    required this.user,
    required this.hospital,
    required this.groupId,
  });

  @override
  State<MedicineGroupDetailScreen> createState() => _MedicineGroupDetailScreenState();
}

class _MedicineGroupDetailScreenState extends State<MedicineGroupDetailScreen> {
  MedGroup? _group;
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
      final g = await MedicineService.instance.fetchGroup(widget.groupId);
      if (mounted) setState(() { _group = g; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  Future<void> _pushEdit() async {
    final refreshed = await Navigator.push<bool>(
      context,
      appRoute(MedicineGroupFormScreen(
        user: widget.user,
        hospital: widget.hospital,
        existing: _group,
      )),
    );
    if (refreshed == true && mounted) _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF6FAFD),
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: Text(
          _group?.name ?? 'Group Detail',
          style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 18),
          overflow: TextOverflow.ellipsis,
        ),
        actions: [
          if (_group != null)
            IconButton(
              onPressed: _pushEdit,
              icon: const Icon(Icons.edit_outlined),
              tooltip: 'Edit',
            ),
        ],
      ),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: AppColors.primary))
          : _error != null
              ? AppErrorState(message: _error!, onRetry: _load)
              : _group != null
                  ? _buildBody(_group!)
                  : const SizedBox.shrink(),
    );
  }

  Widget _buildBody(MedGroup group) {
    return RefreshIndicator(
      onRefresh: _load,
      color: AppColors.primary,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 14, 14, 32),
        children: [
          // Header card
          Container(
            padding: const EdgeInsets.all(16),
            decoration: _cardDeco(),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            group.name,
                            style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: AppColors.primary),
                          ),
                          if (group.groupCode != null) ...[
                            const SizedBox(height: 4),
                            Text(
                              group.groupCode!,
                              style: TextStyle(fontSize: 13, color: Color(0xFF6B7D93).withValues(alpha: 0.60), fontWeight: FontWeight.w600),
                            ),
                          ],
                        ],
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                      decoration: BoxDecoration(color: AppColors.primaryA12, borderRadius: BorderRadius.circular(AppRadius.xl)),
                      child: Text(
                        '${group.itemsCount} med${group.itemsCount == 1 ? '' : 's'}',
                        style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: AppColors.primary),
                      ),
                    ),
                  ],
                ),
                if (group.diagnosisValue != null) ...[
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      const Icon(Icons.local_hospital_outlined, size: 14, color: Color(0xFF1F9D55)),
                      const SizedBox(width: 5),
                      Text(
                        group.diagnosisValue!,
                        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Color(0xFF1F9D55)),
                      ),
                    ],
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(height: 14),
          // Items
          if (group.items.isEmpty)
            Container(
              padding: const EdgeInsets.all(32),
              decoration: _cardDeco(),
              child: Center(
                child: Text(
                  'No medicines in this group.',
                  style: TextStyle(color: Color(0xFF6B7D93).withValues(alpha: 0.60), fontSize: 14),
                ),
              ),
            )
          else
            Container(
              decoration: _cardDeco(),
              clipBehavior: Clip.hardEdge,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                    color: AppColors.primaryA06,
                    child: Row(
                      children: [
                        Expanded(
                          flex: 3,
                          child: Text('Medicine', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: AppColors.primary)),
                        ),
                        Expanded(
                          flex: 2,
                          child: Text('Dosage', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: AppColors.primary)),
                        ),
                        Expanded(
                          flex: 2,
                          child: Text('Duration', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: AppColors.primary)),
                        ),
                        Expanded(
                          flex: 2,
                          child: Text('Route', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: AppColors.primary)),
                        ),
                        SizedBox(
                          width: 40,
                          child: Text('Qty', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: AppColors.primary), textAlign: TextAlign.center),
                        ),
                      ],
                    ),
                  ),
                  ...group.items.asMap().entries.map((entry) {
                    final i = entry.key;
                    final item = entry.value;
                    return Container(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                      decoration: BoxDecoration(
                        border: Border(
                          top: BorderSide(color: AppColors.primaryA10),
                        ),
                        color: i.isEven ? Colors.white : AppColors.primaryA06,
                      ),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(
                            flex: 3,
                            child: Text(
                              item.medicineName ?? '-',
                              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF18324A)),
                            ),
                          ),
                          Expanded(
                            flex: 2,
                            child: Text(
                              item.dosageText ?? '-',
                              style: TextStyle(fontSize: 12, color: Color(0xFF6B7D93).withValues(alpha: 0.60)),
                            ),
                          ),
                          Expanded(
                            flex: 2,
                            child: Text(
                              item.duration ?? '-',
                              style: TextStyle(fontSize: 12, color: Color(0xFF6B7D93).withValues(alpha: 0.60)),
                            ),
                          ),
                          Expanded(
                            flex: 2,
                            child: Text(
                              item.routeName ?? '-',
                              style: TextStyle(fontSize: 12, color: Color(0xFF6B7D93).withValues(alpha: 0.60)),
                            ),
                          ),
                          SizedBox(
                            width: 40,
                            child: Text(
                              '${item.quantity}',
                              style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: AppColors.primary),
                              textAlign: TextAlign.center,
                            ),
                          ),
                        ],
                      ),
                    );
                  }),
                ],
              ),
            ),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              onPressed: _pushEdit,
              icon: const Icon(Icons.edit_outlined, size: 16),
              label: const Text('Edit Group', style: TextStyle(fontWeight: FontWeight.w800)),
              style: OutlinedButton.styleFrom(
                foregroundColor: AppColors.primary,
                side: BorderSide(color: AppColors.primaryA10, width: 1.5),
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

