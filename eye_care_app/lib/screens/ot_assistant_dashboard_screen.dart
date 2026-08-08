import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/permissions.dart';
import '../models/auth_models.dart';
import '../models/ot_accountant_models.dart';
import '../models/ot_booking_models.dart';
import '../services/ot_assistant_service.dart';
import '../services/permission_service.dart';
import '../utils/app_route.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_pagination_bar.dart';
import 'ot_surgery_record_screen.dart';

/// Round 3 Phase 4 — Assistant queue (ready-for-surgery bookings). Single
/// "Assistant Dashboard" nav entry, gated by the combined
/// `otLensRecord`/`otLensImplant`/`otPatientList`/`otSurgeryReady`/`otSurgeryRecord`
/// permission set — matches web's one merged nav item (the old separate
/// "Doctor Dashboard (OT)" entry was removed; the backend only ever had one
/// combined queue, `bookings()`, so it showed identical data anyway).
///
/// Matches web exactly (OT_WEB_PARITY_FIX_PRD.md §5.1): the web dashboard
/// shows only a Surgery Queue with a single "Operate" action — lens
/// recording is deliberately hidden from this screen (web code comment:
/// "Lens workflow UI hidden — counselling already captures planned lens").
/// Lens is instead reachable from the Surgery Record screen once a surgery
/// is saved, not from this queue.
class OtAssistantDashboardScreen extends StatefulWidget {
  final UserInfo user;

  const OtAssistantDashboardScreen({super.key, required this.user});

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
  // Matches web's `isSuperUser() || role.slug === 'hospital_admin'` exactly
  // (OtAssistantController::dashboard()) — admins see the Surgeon column
  // and every ready booking, not just their own.
  bool get _seeAll => (widget.user.role?.isSuper ?? false) || widget.user.role?.slug == 'hospital_admin';

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
    Navigator.of(context).push(appRoute(OtSurgeryRecordScreen(bookingId: item.id, patientName: name))).then((_) => _load());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        elevation: 0,
        leading: IconButton(icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20), onPressed: () => Navigator.pop(context)),
        title: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisSize: MainAxisSize.min, children: [
          const Text('OT Assistant', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800, letterSpacing: -0.2)),
          if (_meta != null) Text('${_meta!.total} total', style: TextStyle(color: Colors.white.withValues(alpha: 0.75), fontSize: 11)),
        ]),
      ),
      body: Column(children: [
        Expanded(child: _buildBody()),
        if (_meta != null) Padding(padding: const EdgeInsets.symmetric(vertical: 8), child: AppPaginationBar(currentPage: _meta!.currentPage, totalPages: _meta!.lastPage, onPageChange: (p) { setState(() => _page = p); _load(); })),
      ]),
    );
  }

  Widget _buildBody() {
    if (_loading) return Center(child: CircularProgressIndicator(color: AppColors.primary));
    if (_error != null) return AppErrorState(message: _error!, onRetry: _load);
    if (_items.isEmpty) return AppEmptyState(message: 'No bookings ready for surgery.', icon: Icons.handyman_rounded, onRefresh: _load);

    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: _load,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 20),
        itemCount: _items.length,
        itemBuilder: (_, i) {
          final item = _items[i];
          return Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.primary.withValues(alpha: 0.08)), boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.05), blurRadius: 8, offset: const Offset(0, 2))]),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(children: [
                  Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(item.patient?.fullName ?? 'Patient', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.darkNavy)),
                      const SizedBox(height: 2),
                      Text('${item.patient?.patientCode ?? ''}${item.eye != null ? ' · ${item.eye}' : ''}', style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
                    ]),
                  ),
                  Container(padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3), decoration: BoxDecoration(color: const Color(0xFFF0F6FB), borderRadius: BorderRadius.circular(999)), child: Text(OtStatus.label(item.otStatus).toUpperCase(), style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: AppColors.darkNavy))),
                ]),
                const SizedBox(height: 8),
                Wrap(spacing: 6, runSpacing: 6, children: [
                  if (item.otType != null) _infoChip(Icons.heart_broken_outlined, item.otType!),
                  if (item.packageAmount != null) _infoChip(Icons.currency_rupee_rounded, item.packageAmount!.toStringAsFixed(0)),
                  if (_seeAll && item.otDoctor != null) _infoChip(Icons.person_outline_rounded, 'Dr. ${item.otDoctor!.name}'),
                  if (item.paymentStatus != null)
                    Container(padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3), decoration: BoxDecoration(color: paymentStatusColor(item.paymentStatus!).withValues(alpha: 0.12), borderRadius: BorderRadius.circular(999)), child: Text(paymentStatusLabel(item.paymentStatus!), style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: paymentStatusColor(item.paymentStatus!)))),
                ]),
                const SizedBox(height: 10),
                if (_canSurgery)
                  SizedBox(
                    width: double.infinity,
                    child: OutlinedButton.icon(
                      onPressed: () => _openSurgery(item),
                      icon: const Icon(Icons.local_hospital_outlined, size: 16),
                      label: const Text('Operate', style: TextStyle(fontSize: 12)),
                      style: OutlinedButton.styleFrom(foregroundColor: AppColors.primary, side: BorderSide(color: AppColors.primary.withValues(alpha: 0.4))),
                    ),
                  ),
              ]),
            ),
          );
        },
      ),
    );
  }

  Widget _infoChip(IconData icon, String label) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
        decoration: BoxDecoration(color: AppColors.background, borderRadius: BorderRadius.circular(999)),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(icon, size: 11, color: AppColors.textSecondary),
          const SizedBox(width: 3),
          Text(label, style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w600, color: AppColors.textSecondary)),
        ]),
      );
}
