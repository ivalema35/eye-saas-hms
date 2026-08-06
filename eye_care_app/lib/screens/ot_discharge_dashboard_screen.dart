import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../models/ot_booking_models.dart';
import '../services/ot_discharge_service.dart';
import '../utils/app_route.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_pagination_bar.dart';
import '../widgets/status_badge.dart';
import 'ot_discharge_detail_screen.dart';

/// Round 3 Phase 6 — Discharge & Invoices. Operated/discharged queue.
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
    Navigator.of(context).push(appRoute(OtDischargeDetailScreen(bookingId: item.id, patientName: name))).then((_) => _load());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        elevation: 0,
        leading: IconButton(icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20), onPressed: () => Navigator.pop(context)),
        title: const Text('Discharge & Invoices', style: TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.w800, letterSpacing: -0.2)),
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
    if (_items.isEmpty) return AppEmptyState(message: 'No operated bookings yet.', icon: Icons.receipt_long_rounded, onRefresh: _load);

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
            child: InkWell(
              onTap: () => _open(item),
              borderRadius: BorderRadius.circular(14),
              child: Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.primary.withValues(alpha: 0.08)), boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.05), blurRadius: 8, offset: const Offset(0, 2))]),
                child: Row(children: [
                  Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(item.patient?.fullName ?? 'Patient', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.darkNavy)),
                      const SizedBox(height: 2),
                      Text(item.patient?.patientCode ?? '', style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
                    ]),
                  ),
                  StatusBadge(label: OtStatus.label(item.otStatus), color: item.otStatus == OtStatus.discharged ? AppColors.green : AppColors.orange),
                  const SizedBox(width: 8),
                  const Icon(Icons.chevron_right_rounded, color: AppColors.textDisabled),
                ]),
              ),
            ),
          );
        },
      ),
    );
  }
}
