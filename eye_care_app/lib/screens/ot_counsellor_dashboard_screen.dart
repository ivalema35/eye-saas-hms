import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/ot_booking_models.dart';
import '../services/ot_accountant_service.dart';
import '../services/ot_counsellor_service.dart';
import '../utils/app_route.dart';
import '../widgets/app_error_state.dart';
import 'ot_counselling_form_screen.dart';

/// Round 3 Phase 1 — Counsellor Dashboard. Web has **two** tables on this
/// screen (OT_WEB_PARITY_FIX_PRD.md §3): "Awaiting Counselling" (tappable,
/// this API's own queue) and read-only "Payment Status" (bookings from
/// `paid` onward — informational only, no action, since auto-verification
/// is server-side).
///
/// **Known gap:** `OtCounsellorApiController::bookings()` only returns the
/// Awaiting-Counselling queue — there's no dedicated endpoint for the
/// Payment Status table. Reusing the Accountant "completed" queue
/// (`payment_verified`/`in_ward`/`dilated`/`ready`/`operated`/`discharged`)
/// as the closest available substitute — it's missing bookings still in
/// the bare `paid` status (payment recorded, not yet auto-verified), which
/// won't show here until a dedicated endpoint exists.
class OtCounsellorDashboardScreen extends StatefulWidget {
  const OtCounsellorDashboardScreen({super.key});

  @override
  State<OtCounsellorDashboardScreen> createState() => _OtCounsellorDashboardScreenState();
}

class _OtCounsellorDashboardScreenState extends State<OtCounsellorDashboardScreen> {
  List<OtBookingSummary> _awaiting = [];
  List<OtBookingSummary> _paymentStatus = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final results = await Future.wait([
        OtCounsellorService.instance.fetchBookings(),
        OtAccountantService.instance.fetchBookings(filter: 'completed'),
      ]);
      if (mounted) {
        setState(() {
          _awaiting = results[0].items;
          _paymentStatus = results[1].items;
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  void _open(OtBookingSummary item) {
    final name = item.patient?.fullName ?? 'Patient';
    Navigator.of(context).push(appRoute(OtCounsellingFormScreen(bookingId: item.id, patientName: name))).then((_) => _load());
  }

  // Matches web's `optional($booking->surgery_date)->format('d M Y')` exactly.
  static const _months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  static String? _fmtDate(String? raw) {
    if (raw == null) return null;
    final d = DateTime.tryParse(raw);
    if (d == null) return raw;
    return '${d.day.toString().padLeft(2, '0')} ${_months[d.month - 1]} ${d.year}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        elevation: 0,
        leading: IconButton(icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20), onPressed: () => Navigator.pop(context)),
        title: const Text('OT Counsellor', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800, letterSpacing: -0.2)),
      ),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: AppColors.primary))
          : _error != null
              ? AppErrorState(message: _error!, onRetry: _load)
              : RefreshIndicator(color: AppColors.primary, onRefresh: _load, child: _buildBody()),
    );
  }

  Widget _buildBody() {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 20),
      children: [
        const _SectionLabel('Awaiting Counselling'),
        if (_awaiting.isEmpty)
          const _EmptyRow('No bookings awaiting counselling.')
        else
          ..._awaiting.map((item) => _bookingCard(item, tappable: true)),
        const SizedBox(height: 20),
        const _SectionLabel('Payment Status', subtitle: 'Bookings billing has taken payment on'),
        if (_paymentStatus.isEmpty)
          const _EmptyRow('No bookings yet.')
        else
          ..._paymentStatus.map((item) => _bookingCard(item, tappable: false)),
      ],
    );
  }

  Widget _bookingCard(OtBookingSummary item, {required bool tappable}) {
    final recommended = item.otStatus == OtStatus.surgeryRecommended;
    final date = _fmtDate(item.surgeryDate);
    final card = Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: recommended ? AppColors.orange.withValues(alpha: 0.35) : AppColors.primary.withValues(alpha: 0.08)),
        boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.05), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(item.patient?.fullName ?? 'Patient', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.darkNavy)),
              const SizedBox(height: 2),
              Text('${item.patient?.patientCode ?? ''}${item.eye != null ? ' · ${item.eye}' : ''}', style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
            ]),
          ),
          if (tappable && recommended)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(color: AppColors.orangeA12, borderRadius: BorderRadius.circular(AppRadius.sm)),
              child: const Text('Recommended', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: AppColors.orange)),
            )
          else if (!tappable)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(color: (item.otStatus == OtStatus.paid ? AppColors.orange : AppColors.green).withValues(alpha: 0.12), borderRadius: BorderRadius.circular(AppRadius.sm)),
              child: Text(item.otStatus == OtStatus.paid ? 'Paid' : 'Complete', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: item.otStatus == OtStatus.paid ? AppColors.orange : AppColors.green)),
            ),
          if (tappable) ...[const SizedBox(width: 6), const Icon(Icons.chevron_right_rounded, color: AppColors.textDisabled)],
        ]),
        const SizedBox(height: 10),
        Row(children: [
          if (item.patient?.contactNo != null) ...[
            const Icon(Icons.phone_outlined, size: 12, color: AppColors.textSecondary),
            const SizedBox(width: 4),
            Text(item.patient!.contactNo!, style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
          ],
          if (date != null) ...[
            const SizedBox(width: 12),
            const Icon(Icons.event_rounded, size: 12, color: AppColors.textSecondary),
            const SizedBox(width: 4),
            Text(date, style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
          ],
        ]),
        if (tappable) ...[
          const SizedBox(height: 4),
          Row(children: [
            if (item.otDoctor != null) ...[
              const Icon(Icons.medical_services_outlined, size: 12, color: AppColors.textSecondary),
              const SizedBox(width: 4),
              Text('Dr. ${item.otDoctor!.name}', style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
            ],
            if (item.otType != null) ...[
              const SizedBox(width: 12),
              const Icon(Icons.local_hospital_outlined, size: 12, color: AppColors.textSecondary),
              const SizedBox(width: 4),
              Expanded(child: Text(item.otType!, style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary), overflow: TextOverflow.ellipsis)),
            ],
          ]),
        ] else if (item.packageAmount != null) ...[
          const SizedBox(height: 4),
          Row(children: [
            const Icon(Icons.currency_rupee_rounded, size: 12, color: AppColors.textSecondary),
            const SizedBox(width: 2),
            Text(item.packageAmount!.toStringAsFixed(0), style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary, fontWeight: FontWeight.w600)),
          ]),
        ],
      ]),
    );
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: tappable ? InkWell(onTap: () => _open(item), borderRadius: BorderRadius.circular(14), child: card) : card,
    );
  }
}

class _SectionLabel extends StatelessWidget {
  final String title;
  final String? subtitle;
  const _SectionLabel(this.title, {this.subtitle});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(title, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: AppColors.darkNavy)),
        if (subtitle != null) Text(subtitle!, style: const TextStyle(fontSize: 11, color: AppColors.textSecondary)),
      ]),
    );
  }
}

class _EmptyRow extends StatelessWidget {
  final String message;
  const _EmptyRow(this.message);

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 16),
        child: Center(child: Text(message, style: const TextStyle(fontSize: 12, color: AppColors.textSecondary))),
      );
}
