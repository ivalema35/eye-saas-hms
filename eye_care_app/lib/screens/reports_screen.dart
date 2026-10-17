import 'package:flutter/material.dart';
import '../widgets/app_refresh_indicator.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_error_state.dart';
import '../widgets/skeleton.dart';
import '../constants/permissions.dart';
import '../models/auth_models.dart';
import '../models/report_models.dart';
import '../services/permission_service.dart';
import '../services/report_service.dart';
import 'report_channel_screen.dart';
import '../utils/app_route.dart';
import '../utils/currency_format.dart';
import '../utils/refreshable.dart';

// ═════════════════════════════════════════════════════════════════════════════
// OPD REPORTS — LANDING SCREEN
// Web redesign (index.blade.php): a "Total Collection (Walk-in)" banner +
// 3 tappable channel tiles (OT Appointment / Walk-in / Phone), no filter
// form or patient list at this level — those live on the per-channel screen.
// ═════════════════════════════════════════════════════════════════════════════

class ReportsScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final VoidCallback? onMenuTap;

  const ReportsScreen({
    super.key,
    required this.user,
    required this.hospital,
    this.onMenuTap,
  });

  @override
  State<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends State<ReportsScreen> implements Refreshable {
  ReportChannelCounts? _counts;
  bool _loading = true;
  String? _error;
  bool _exporting = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  Future<void> refreshSilently() => _load();

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final counts = await ReportService.instance.fetchChannelCounts(const ReportFilter());
      if (mounted) setState(() { _counts = counts; _loading = false; });
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString().replaceFirst('Exception: ', '');
          _loading = false;
        });
      }
    }
  }

  Future<void> _export(String format) async {
    setState(() => _exporting = true);
    showAppSnackBar(context, 'Preparing ${format.toUpperCase()} export…', duration: const Duration(seconds: 2));
    try {
      await ReportService.instance.downloadAndOpenExport(format: format, filter: const ReportFilter());
      if (!mounted) return;
      ScaffoldMessenger.of(context).hideCurrentSnackBar();
      showAppSnackBar(context, '${format == 'excel' ? 'Excel' : 'PDF'} opened successfully', isSuccess: true, duration: const Duration(seconds: 2));
    } catch (e) {
      if (!mounted) return;
      showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    } finally {
      if (mounted) setState(() => _exporting = false);
    }
  }

  void _openChannel(String channel, String label) {
    Navigator.push(
      context,
      appRoute(ReportChannelScreen(channel: channel, label: label)),
    ).then((_) => _load());
  }

  @override
  Widget build(BuildContext context) {
    if (!PermissionService.instance.can(Perm.reportsView) &&
        !PermissionService.instance.can(Perm.opdReportsView)) {
      return Scaffold(
        backgroundColor: const Color(0xFFF6FAFD),
        appBar: _buildAppBar(),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 80, horizontal: 32),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.lock_outline_rounded, size: 56,
                    color: AppColors.primary.withValues(alpha: 0.25)),
                const SizedBox(height: 16),
                Text('No Access',
                    style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700,
                        color: AppColors.primary.withValues(alpha: 0.50))),
                const SizedBox(height: 6),
                Text('You do not have permission to view reports.',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 13, color: const Color(0xFF6B7D93).withValues(alpha: 0.60))),
              ],
            ),
          ),
        ),
      );
    }
    return Scaffold(
      backgroundColor: const Color(0xFFF6FAFD),
      appBar: _buildAppBar(),
      body: AppRefreshIndicator(
        onRefresh: _load,
        color: AppColors.primary,
        child: _buildBody(),
      ),
    );
  }

  AppBar _buildAppBar() => AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        leading: widget.onMenuTap != null
            ? IconButton(
                icon: const Icon(Icons.menu_rounded, color: Colors.white),
                onPressed: widget.onMenuTap,
              )
            : null,
        title: const Text(
          'Patient Reports',
          style: TextStyle(fontWeight: FontWeight.w800, fontSize: 18),
        ),
        actions: [
          if (PermissionService.instance.can(Perm.reportsExport) ||
              PermissionService.instance.can(Perm.opdReportsExport)) ...[
            IconButton(
              tooltip: 'Export Excel',
              onPressed: _exporting ? null : () => _export('excel'),
              icon: const Icon(Icons.table_chart_outlined, size: 22),
            ),
            IconButton(
              tooltip: 'Export PDF',
              onPressed: _exporting ? null : () => _export('pdf'),
              icon: const Icon(Icons.picture_as_pdf_outlined, size: 22),
            ),
          ],
          IconButton(
            tooltip: 'Refresh',
            onPressed: _loading ? null : _load,
            icon: _loading
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white60),
                  )
                : const Icon(Icons.refresh_rounded, size: 22),
          ),
          const SizedBox(width: 4),
        ],
      );

  Widget _buildBody() {
    if (_loading && _counts == null) {
      return const AppSkeletonList();
    }
    if (_error != null && _counts == null) {
      return AppErrorState(message: _error!, onRetry: _load);
    }
    final counts = _counts!;
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 24),
      children: [
        _CollectionBanner(total: counts.totalCollection),
        const SizedBox(height: 14),
        _ChannelTile(
          icon: Icons.calendar_month_rounded,
          iconColor: const Color(0xFF7D3C98),
          label: 'OT Appointment',
          count: counts.otAppointment,
          onTap: () => _openChannel('ot_appointment', 'OT Appointment Patients'),
        ),
        const SizedBox(height: 12),
        _ChannelTile(
          icon: Icons.directions_walk_rounded,
          iconColor: AppColors.primary,
          label: 'Walk-in',
          count: counts.walkin,
          onTap: () => _openChannel('walkin', 'Walk-in Patients'),
        ),
        const SizedBox(height: 12),
        _ChannelTile(
          icon: Icons.call_rounded,
          iconColor: const Color(0xFF117A65),
          label: 'Phone Appointment',
          count: counts.phone,
          onTap: () => _openChannel('phone', 'Phone Appointment Patients'),
        ),
      ],
    );
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// TOTAL COLLECTION BANNER
// ═════════════════════════════════════════════════════════════════════════════

class _CollectionBanner extends StatelessWidget {
  final double total;

  const _CollectionBanner({required this.total});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF1F9D55), Color(0xFF27AE60)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(AppRadius.lg),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF1F9D55).withValues(alpha: 0.28),
            blurRadius: 14,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.20),
              borderRadius: BorderRadius.circular(AppRadius.md),
            ),
            child: const Icon(Icons.account_balance_wallet_rounded, color: Colors.white, size: 22),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Total Collection (Walk-in)',
                  style: TextStyle(color: Colors.white70, fontSize: 11, fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 2),
                Text(
                  '${currentCurrencySymbol()}${total.toStringAsFixed(2)}',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 22,
                    fontWeight: FontWeight.w900,
                    letterSpacing: -0.5,
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

// ═════════════════════════════════════════════════════════════════════════════
// CHANNEL TILE
// ═════════════════════════════════════════════════════════════════════════════

class _ChannelTile extends StatelessWidget {
  final IconData icon;
  final Color iconColor;
  final String label;
  final int count;
  final VoidCallback onTap;

  const _ChannelTile({
    required this.icon,
    required this.iconColor,
    required this.label,
    required this.count,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(AppRadius.lg),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(AppRadius.lg),
            border: Border.all(color: AppColors.primaryA10),
            boxShadow: [BoxShadow(color: AppColors.primaryA06, blurRadius: 10, offset: const Offset(0, 3))],
          ),
          child: Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: iconColor.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(AppRadius.md),
                ),
                child: Icon(icon, color: iconColor, size: 24),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Text(
                  label,
                  style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Color(0xFF18324A)),
                ),
              ),
              Text(
                '$count',
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: iconColor),
              ),
              const SizedBox(width: 8),
              Icon(Icons.chevron_right_rounded, color: const Color(0xFF6B7D93).withValues(alpha: 0.6)),
            ],
          ),
        ),
      ),
    );
  }
}
