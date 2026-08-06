import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/ot_accountant_models.dart';
import '../models/ot_booking_models.dart';
import '../services/ot_ward_service.dart';
import '../utils/app_route.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_pagination_bar.dart';
import 'ot_ward_screen.dart';

/// Ward Entry Queue — matches web's `ward.blade.php`
/// (`OtAccountantController::wardIndex()`). Replaces the earlier manual
/// booking-ID lookup stopgap now that a real queue endpoint exists (see
/// OT_WEB_PARITY_FIX_PRD.md §4).
class OtWardQueueScreen extends StatefulWidget {
  const OtWardQueueScreen({super.key});

  @override
  State<OtWardQueueScreen> createState() => _OtWardQueueScreenState();
}

class _OtWardQueueScreenState extends State<OtWardQueueScreen> {
  List<OtBookingSummary> _items = [];
  OtPaginationMeta? _meta;
  bool _loading = true;
  String? _error;
  int _page = 1;
  int? _sendingId;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final result = await OtWardService.instance.fetchBookings(page: _page);
      if (mounted) setState(() { _items = result.items; _meta = result.meta; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  void _open(OtBookingSummary item) {
    final name = item.patient?.fullName ?? 'Patient';
    Navigator.push(context, appRoute(OtWardScreen(bookingId: item.id, patientName: name, initialOtStatus: item.otStatus))).then((_) => _load());
  }

  Future<void> _sendToOtAssistant(OtBookingSummary item) async {
    setState(() => _sendingId = item.id);
    try {
      await OtWardService.instance.markReady(item.id);
      if (mounted) showAppSnackBar(context, 'Patient is ready and handed off to OT Assistant', isSuccess: true);
      _load();
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    } finally {
      if (mounted) setState(() => _sendingId = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      body: Column(children: [
        _buildHeader(),
        Expanded(
          child: _loading
              ? Center(child: CircularProgressIndicator(color: AppColors.primary))
              : _error != null
                  ? AppErrorState(message: _error!, onRetry: _load)
                  : _buildBody(),
        ),
        if (_meta != null) Padding(padding: const EdgeInsets.symmetric(vertical: 8), child: AppPaginationBar(currentPage: _meta!.currentPage, totalPages: _meta!.lastPage, onPageChange: (p) { setState(() => _page = p); _load(); })),
      ]),
    );
  }

  Widget _buildHeader() {
    return Container(
      decoration: BoxDecoration(gradient: LinearGradient(colors: [AppColors.primary, AppColors.blueLight], begin: Alignment.topLeft, end: Alignment.bottomRight)),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(8, 10, 20, 14),
          child: Row(children: [
            IconButton(icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20), onPressed: () => Navigator.pop(context)),
            Container(width: 40, height: 40, decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(10)), child: const Icon(Icons.bed_rounded, color: Colors.white, size: 20)),
            const SizedBox(width: 12),
            const Expanded(child: Text('Ward Entry Queue', style: TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.w800))),
          ]),
        ),
      ),
    );
  }

  Widget _buildBody() {
    if (_items.isEmpty) return AppEmptyState(message: 'No records available for ward workflow.', icon: Icons.bed_rounded, onRefresh: _load);

    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: _load,
      child: ListView.separated(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 20),
        itemCount: _items.length,
        separatorBuilder: (_, _) => const SizedBox(height: 8),
        itemBuilder: (_, i) {
          final item = _items[i];
          final canSend = item.otStatus == OtStatus.paymentVerified || item.otStatus == OtStatus.inWard;
          final sending = _sendingId == item.id;
          return Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.08))),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(children: [
                Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(item.patient?.fullName ?? 'Patient', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.darkNavy)),
                    const SizedBox(height: 2),
                    Text('${item.patient?.contactNo ?? '—'}${item.surgeryDate != null ? ' · ${item.surgeryDate}' : ''}', style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
                  ]),
                ),
                Container(padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3), decoration: BoxDecoration(color: const Color(0xFFF0F6FB), borderRadius: BorderRadius.circular(AppRadius.full)), child: Text(OtStatus.label(item.otStatus).toUpperCase(), style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: AppColors.darkNavy))),
              ]),
              const SizedBox(height: 8),
              Row(children: [
                if (item.paymentStatus != null)
                  Container(padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3), decoration: BoxDecoration(color: paymentStatusColor(item.paymentStatus!).withValues(alpha: 0.12), borderRadius: BorderRadius.circular(AppRadius.full)), child: Text(paymentStatusLabel(item.paymentStatus!), style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: paymentStatusColor(item.paymentStatus!)))),
              ]),
              const SizedBox(height: 10),
              Divider(height: 1, color: AppColors.primaryA08),
              const SizedBox(height: 8),
              Row(children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () => _open(item),
                    icon: const Icon(Icons.favorite_border_rounded, size: 15),
                    label: const Text('Vitals & Eye Drops', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                    style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 10), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.sm))),
                  ),
                ),
                if (canSend) ...[
                  const SizedBox(width: 8),
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: sending ? null : () => _sendToOtAssistant(item),
                      icon: sending ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Icon(Icons.send_rounded, size: 15),
                      label: const Text('Send to OT Assistant', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700), overflow: TextOverflow.ellipsis),
                      style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 10), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.sm))),
                    ),
                  ),
                ],
              ]),
            ]),
          );
        },
      ),
    );
  }
}
