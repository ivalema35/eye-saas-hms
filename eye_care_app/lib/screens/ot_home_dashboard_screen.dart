import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/permissions.dart';
import '../models/ot_booking_models.dart';
import '../services/ot_booking_service.dart';
import '../services/permission_service.dart';
import '../utils/app_route.dart';
import '../widgets/app_error_state.dart';
import 'ot_appointment_list_screen.dart';
import 'ot_counsellor_dashboard_screen.dart';

/// OT Home Dashboard — web's actual `/ot/dashboard` landing page
/// (`hospital.ot.dashboard`), previously missing entirely from both apps
/// (see OT_WEB_PARITY_FIX_PRD.md §8). Web's sidebar links directly to "OT
/// Appointments" (not this dashboard) — reachable there via its own
/// `OT Appointments` drawer item, matching the sidebar's flat direct-link
/// structure. This screen is reached from the home dashboard's "OT
/// Dashboard" quick-action tile instead.
class OtHomeDashboardScreen extends StatefulWidget {
  const OtHomeDashboardScreen({super.key});

  @override
  State<OtHomeDashboardScreen> createState() => _OtHomeDashboardScreenState();
}

class _OtHomeDashboardScreenState extends State<OtHomeDashboardScreen> {
  bool _loading = true;
  String? _error;
  OtReceptionistDashboardStats? _stats;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final s = await OtBookingService.instance.fetchReceptionistDashboard();
      if (mounted) setState(() { _stats = s; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = PermissionService.instance;
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        elevation: 0,
        leading: IconButton(icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20), onPressed: () => Navigator.pop(context)),
        title: const Text('OT Dashboard', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800, letterSpacing: -0.2)),
        actions: [
          if (p.can(Perm.otAppointmentCreate))
            TextButton.icon(
              onPressed: () => Navigator.of(context).push(appRoute(const OtAppointmentListScreen())),
              icon: const Icon(Icons.add_circle_outline_rounded, color: Colors.white, size: 18),
              label: const Text('New Appointment', style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w700)),
            ),
        ],
      ),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: AppColors.primary))
          : _error != null
              ? AppErrorState(message: _error!, onRetry: _load)
              : _buildBody(p),
    );
  }

  Widget _buildBody(PermissionService p) {
    final s = _stats!;
    return ListView(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 20),
      children: [
        GridView.count(
          crossAxisCount: 3,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 10,
          crossAxisSpacing: 10,
          childAspectRatio: 1.0,
          children: [
            _kpiCard('Total OT Today', '${s.totalOtToday}', Icons.event_note_rounded, AppColors.primary),
            _kpiCard('Pending Counselling', '${s.pendingCounselling}', Icons.support_agent_rounded, AppColors.orange),
            _kpiCard('Ready For Surgery', '${s.readyForSurgery}', Icons.local_hospital_rounded, AppColors.green),
          ],
        ),
        const SizedBox(height: 20),
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.08))),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const Text('OT Workspace', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: AppColors.darkNavy)),
            const SizedBox(height: 12),
            if (p.can(Perm.otAppointmentView))
              _workspaceButton('OT Appointments', Icons.event_note_rounded, () => Navigator.of(context).push(appRoute(const OtAppointmentListScreen()))),
            if (p.can(Perm.otCounsellingFill)) ...[
              const SizedBox(height: 10),
              _workspaceButton('Counselling Queue', Icons.support_agent_rounded, () => Navigator.of(context).push(appRoute(const OtCounsellorDashboardScreen()))),
            ],
            const SizedBox(height: 12),
            Text(
              'Book OT appointments, then continue counselling after the doctor recommends surgery. Manual OT booking is no longer used.',
              style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary, height: 1.4),
            ),
          ]),
        ),
      ],
    );
  }

  Widget _kpiCard(String label, String value, IconData icon, Color color) => Container(
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: color.withValues(alpha: 0.15))),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.center, children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(height: 8),
          Text(value, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
          Text(label, style: const TextStyle(fontSize: 10, color: AppColors.textSecondary), maxLines: 2),
        ]),
      );

  Widget _workspaceButton(String label, IconData icon, VoidCallback onTap) => SizedBox(
        width: double.infinity,
        child: OutlinedButton.icon(
          onPressed: onTap,
          icon: Icon(icon, size: 16),
          label: Text(label),
          style: OutlinedButton.styleFrom(foregroundColor: AppColors.primary, alignment: Alignment.centerLeft, padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 14)),
        ),
      );
}
