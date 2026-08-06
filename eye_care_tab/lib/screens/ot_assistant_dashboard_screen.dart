import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/permissions.dart';
import '../models/ot_booking_models.dart';
import '../services/ot_assistant_service.dart';
import '../services/permission_service.dart';
import '../utils/app_route.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_pagination_bar.dart';
import 'ot_surgery_record_screen.dart';

/// Tablet Assistant queue (Round 3 Phase 4) — top-level nav destination, no
/// own Scaffold. Serves both "Doctor Dashboard (OT)" and "Assistant
/// Dashboard" rail entries (same combined backend queue, see mobile
/// version's doc comment).
///
/// Matches web exactly (OT_WEB_PARITY_FIX_PRD.md §5.1): only a Surgery
/// Queue with a single "Operate" action — lens is deliberately hidden from
/// this screen, reachable instead from the Surgery Record screen. Ported
/// from eye_care_app/lib/screens/ot_assistant_dashboard_screen.dart.
class OtAssistantDashboardScreen extends StatefulWidget {
  const OtAssistantDashboardScreen({super.key});

  @override
  State<OtAssistantDashboardScreen> createState() => _OtAssistantDashboardScreenState();
}

class _OtAssistantDashboardScreenState extends State<OtAssistantDashboardScreen> {
  List<OtBookingSummary> _items = [];
  OtPaginationMeta? _meta;
  bool _loading = true;
  String? _error;
  int _page = 1;

  bool get _canSurgery => PermissionService.instance.can(Perm.otSurgeryRecord);

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final result = await OtAssistantService.instance.fetchBookings(page: _page);
      if (mounted) setState(() { _items = result.items; _meta = result.meta; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  void _openSurgery(OtBookingSummary item) {
    final name = item.patient?.fullName ?? 'Patient';
    Navigator.of(context, rootNavigator: true).push(appRoute(OtSurgeryRecordScreen(bookingId: item.id, patientName: name))).then((_) => _load());
  }

  @override
  Widget build(BuildContext context) {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Row(children: [
        Icon(Icons.handyman_rounded, color: AppColors.primary, size: 22),
        const SizedBox(width: 10),
        const Text('OT Assistant', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: AppColors.textPrimary)),
      ]),
      const SizedBox(height: 16),
      Expanded(child: _buildBody()),
      if (_meta != null) Padding(padding: const EdgeInsets.only(top: 8), child: AppPaginationBar(currentPage: _meta!.currentPage, totalPages: _meta!.lastPage, onPageChange: (p) { setState(() => _page = p); _load(); })),
    ]);
  }

  Widget _buildBody() {
    if (_loading) return Center(child: CircularProgressIndicator(color: AppColors.primary));
    if (_error != null) return AppErrorState(message: _error!, onRetry: _load);
    if (_items.isEmpty) return AppEmptyState(message: 'No bookings ready for surgery.', icon: Icons.handyman_rounded, onRefresh: _load);

    return ListView.separated(
      itemCount: _items.length,
      separatorBuilder: (_, _) => const SizedBox(height: 8),
      itemBuilder: (_, i) {
        final item = _items[i];
        return Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.primaryA08)),
          child: Row(children: [
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(item.patient?.fullName ?? 'Patient', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
                Text('${item.patient?.patientCode ?? ''}${item.eye != null ? ' · ${item.eye}' : ''}', style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
              ]),
            ),
            if (_canSurgery)
              OutlinedButton.icon(onPressed: () => _openSurgery(item), icon: const Icon(Icons.local_hospital_outlined, size: 16), label: const Text('Operate', style: TextStyle(fontSize: 12)), style: OutlinedButton.styleFrom(foregroundColor: AppColors.primary)),
          ]),
        );
      },
    );
  }
}
