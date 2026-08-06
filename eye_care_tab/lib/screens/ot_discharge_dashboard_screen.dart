import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/ot_booking_models.dart';
import '../services/ot_discharge_service.dart';
import '../utils/app_route.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_pagination_bar.dart';
import '../widgets/status_badge.dart';
import 'ot_discharge_detail_screen.dart';

/// Tablet Discharge & Invoices (Round 3 Phase 6) — top-level nav
/// destination, no own Scaffold. Ported from
/// eye_care_app/lib/screens/ot_discharge_dashboard_screen.dart.
class OtDischargeDashboardScreen extends StatefulWidget {
  const OtDischargeDashboardScreen({super.key});

  @override
  State<OtDischargeDashboardScreen> createState() => _OtDischargeDashboardScreenState();
}

class _OtDischargeDashboardScreenState extends State<OtDischargeDashboardScreen> {
  List<OtBookingSummary> _items = [];
  OtPaginationMeta? _meta;
  bool _loading = true;
  String? _error;
  int _page = 1;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final result = await OtDischargeService.instance.fetchBookings(page: _page);
      if (mounted) setState(() { _items = result.items; _meta = result.meta; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  void _open(OtBookingSummary item) {
    final name = item.patient?.fullName ?? 'Patient';
    Navigator.of(context, rootNavigator: true).push(appRoute(OtDischargeDetailScreen(bookingId: item.id, patientName: name))).then((_) => _load());
  }

  @override
  Widget build(BuildContext context) {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Row(children: [
        Icon(Icons.receipt_long_rounded, color: AppColors.primary, size: 22),
        const SizedBox(width: 10),
        const Text('Discharge & Invoices', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: AppColors.textPrimary)),
      ]),
      const SizedBox(height: 16),
      Expanded(child: _buildBody()),
      if (_meta != null) Padding(padding: const EdgeInsets.only(top: 8), child: AppPaginationBar(currentPage: _meta!.currentPage, totalPages: _meta!.lastPage, onPageChange: (p) { setState(() => _page = p); _load(); })),
    ]);
  }

  Widget _buildBody() {
    if (_loading) return Center(child: CircularProgressIndicator(color: AppColors.primary));
    if (_error != null) return AppErrorState(message: _error!, onRetry: _load);
    if (_items.isEmpty) return AppEmptyState(message: 'No operated bookings yet.', icon: Icons.receipt_long_rounded, onRefresh: _load);

    return ListView.separated(
      itemCount: _items.length,
      separatorBuilder: (_, _) => const SizedBox(height: 8),
      itemBuilder: (_, i) {
        final item = _items[i];
        return InkWell(
          onTap: () => _open(item),
          borderRadius: BorderRadius.circular(AppRadius.md),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primaryA08)),
            child: Row(children: [
              Expanded(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(item.patient?.fullName ?? 'Patient', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
                  Text(item.patient?.patientCode ?? '', style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
                ]),
              ),
              StatusBadge(label: OtStatus.label(item.otStatus), color: item.otStatus == OtStatus.discharged ? AppColors.green : AppColors.orange),
              const SizedBox(width: 8),
              const Icon(Icons.chevron_right_rounded, color: AppColors.textDisabled),
            ]),
          ),
        );
      },
    );
  }
}
