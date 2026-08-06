import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/app_text_styles.dart';
import '../models/platform_tenant_models.dart';
import '../services/platform_tenant_service.dart';
import '../utils/app_decorations.dart';
import '../utils/app_route.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_section_header.dart';
import '../widgets/status_badge.dart';
import 'platform_hospital_form_screen.dart';

class PlatformHospitalDetailScreen extends StatefulWidget {
  final int tenantId;

  const PlatformHospitalDetailScreen({super.key, required this.tenantId});

  @override
  State<PlatformHospitalDetailScreen> createState() => _PlatformHospitalDetailScreenState();
}

class _PlatformHospitalDetailScreenState extends State<PlatformHospitalDetailScreen> {
  bool _loading = true;
  String? _error;
  TenantDetail? _tenant;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    final detail = await PlatformTenantService.instance.getDetail(widget.tenantId);
    if (!mounted) return;
    setState(() {
      _loading = false;
      if (detail == null) {
        _error = 'Could not load hospital details.';
      } else {
        _tenant = detail;
      }
    });
  }

  Future<void> _confirmAndAct({
    required String title,
    required String body,
    required String actionLabel,
    required Color actionColor,
    required Future<({bool success, String message})> Function() action,
  }) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
        title: Text(title, style: AppTextStyles.headingSmall),
        content: Text(body, style: AppTextStyles.bodyMedium),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text(actionLabel, style: TextStyle(color: actionColor, fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );

    if (confirmed != true || !mounted) return;

    final result = await action();
    if (!mounted) return;

    showAppSnackBar(context, result.message, isSuccess: result.success, isError: !result.success);
    if (result.success) _load();
  }

  Future<void> _extendGrace() async {
    int days = 7;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, set) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
          title: const Text('Extend Grace Period', style: AppTextStyles.headingSmall),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text('Choose number of days to extend:'),
              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  IconButton(
                    icon: const Icon(Icons.remove_rounded),
                    onPressed: days > 1 ? () => set(() => days--) : null,
                  ),
                  Container(
                    width: 60, height: 40,
                    decoration: AppDecorations.card(),
                    child: Center(
                      child: Text('$days', style: AppTextStyles.headingSmall),
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.add_rounded),
                    onPressed: days < 90 ? () => set(() => days++) : null,
                  ),
                ],
              ),
              Text('days', style: AppTextStyles.bodySmall),
            ],
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
            TextButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: Text('Extend', style: TextStyle(color: AppColors.orange, fontWeight: FontWeight.w700)),
            ),
          ],
        ),
      ),
    );

    if (confirmed != true || !mounted) return;
    final result = await PlatformTenantService.instance.extendGrace(widget.tenantId, days);
    if (!mounted) return;
    showAppSnackBar(context, result.message, isSuccess: result.success, isError: !result.success);
    if (result.success) _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: Text(
          _tenant?.name ?? 'Hospital Detail',
          style: GoogleFonts.poppins(fontSize: 16, fontWeight: FontWeight.w800, color: Colors.white),
        ),
        actions: [
          if (_tenant != null && !_tenant!.isArchived)
            IconButton(
              icon: const Icon(Icons.edit_rounded),
              tooltip: 'Edit',
              onPressed: () async {
                await Navigator.push(context, appRoute(PlatformHospitalFormScreen(tenant: _tenant)));
                _load();
              },
            ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(_error!, style: AppTextStyles.bodyMedium),
                      const SizedBox(height: 12),
                      ElevatedButton.icon(onPressed: _load, icon: const Icon(Icons.refresh_rounded, size: 16), label: const Text('Retry')),
                    ],
                  ),
                )
              : RefreshIndicator(
                  color: AppColors.primary,
                  onRefresh: _load,
                  child: SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
                    child: _buildBody(_tenant!),
                  ),
                ),
    );
  }

  Widget _buildBody(TenantDetail t) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildStatusStrip(t),
        const SizedBox(height: 16),

        const AppSectionHeader(title: 'Hospital Info'),
        _buildInfoCard(t),
        const SizedBox(height: 16),

        if (!t.isArchived) ...[
          const AppSectionHeader(title: 'Quick Actions'),
          _buildQuickActions(t),
          const SizedBox(height: 16),
        ],

        const AppSectionHeader(title: 'Subscription History'),
        _buildSubscriptions(t.subscriptions),
        const SizedBox(height: 16),

        const AppSectionHeader(title: 'Payment History'),
        _buildPayments(t.payments),
      ],
    );
  }

  Widget _buildStatusStrip(TenantDetail t) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: AppDecorations.card(),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(child: _stripItem('Status', '', badge: StatusBadge.hospitalStatus(t.status))),
              Expanded(child: _stripItem('Trial Ends', t.trialEndsAt != null ? _formatDate(t.trialEndsAt!) : '—')),
            ],
          ),
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 10),
            child: Divider(height: 1, thickness: 0.5),
          ),
          Row(
            children: [
              Expanded(child: _stripItem('Subscriptions', '${t.subscriptions.length}')),
              Expanded(child: _stripItem('Payments', '${t.payments.length}')),
            ],
          ),
        ],
      ),
    );
  }

  Widget _stripItem(String label, String value, {Widget? badge}) {
    return Column(
      children: [
        badge ?? Text(value, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.textPrimary)),
        const SizedBox(height: 3),
        Text(label, style: AppTextStyles.cardSubtitle, textAlign: TextAlign.center),
      ],
    );
  }

  Widget _buildInfoCard(TenantDetail t) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: AppDecorations.card(),
      child: Column(
        children: [
          _infoRow('Hospital Name', t.name),
          _infoRow('Slug', '/${t.slug}'),
          if (t.hospitalCode != null) _infoRow('Hospital Code', t.hospitalCode!),
          _infoRow('Admin Name', t.adminName ?? '—'),
          _infoRow('Admin Email', t.adminEmail ?? '—'),
          _infoRow('Admin Phone', t.adminPhone ?? '—'),
          _infoRow('City', t.city ?? '—'),
          _infoRow('State', t.state ?? '—'),
          _infoRow('Country', t.country ?? '—'),
          _infoRow('Timezone', t.timezone ?? '—'),
          _infoRow('Registered', t.createdAt != null ? _formatDateTime(t.createdAt!) : '—'),
          _infoRow('Setup Done', t.isSetupDone ? 'Yes' : 'No', isLast: true),
        ],
      ),
    );
  }

  Widget _infoRow(String label, String value, {bool isLast = false}) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 7),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              SizedBox(
                width: 110,
                child: Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.textSecondary)),
              ),
              Expanded(
                child: Text(value, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.textPrimary)),
              ),
            ],
          ),
        ),
        if (!isLast) Divider(height: 1, color: AppColors.primaryA05),
      ],
    );
  }

  Widget _buildQuickActions(TenantDetail t) {
    final actions = <_QuickAction>[];
    final status = t.status;

    if (status == 'trial' || status == 'grace' || status == 'inactive') {
      actions.add(_QuickAction('Activate', Icons.check_circle_outline_rounded, AppColors.green,
          () => _confirmAndAct(
            title: 'Activate Hospital',
            body: "Activate '${t.name}'? They will get full access.",
            actionLabel: 'Activate',
            actionColor: AppColors.green,
            action: () => PlatformTenantService.instance.activate(t.id),
          )));
    }
    if (status == 'active' || status == 'trial') {
      actions.add(_QuickAction('Suspend', Icons.block_rounded, AppColors.red,
          () => _confirmAndAct(
            title: 'Suspend Hospital',
            body: "Suspend '${t.name}'? All staff will lose access immediately.",
            actionLabel: 'Suspend',
            actionColor: AppColors.red,
            action: () => PlatformTenantService.instance.suspend(t.id),
          )));
    }
    if (status == 'suspended') {
      actions.add(_QuickAction('Reactivate', Icons.refresh_rounded, AppColors.secondary,
          () => _confirmAndAct(
            title: 'Reactivate Hospital',
            body: "Reactivate '${t.name}'?",
            actionLabel: 'Reactivate',
            actionColor: AppColors.secondary,
            action: () => PlatformTenantService.instance.reactivate(t.id),
          )));
    }
    if (status != 'suspended' && status != 'inactive') {
      actions.add(_QuickAction('Extend Grace', Icons.hourglass_bottom_rounded, AppColors.orange, _extendGrace));
    }
    actions.add(_QuickAction('Re-seed Masters', Icons.refresh_rounded, AppColors.teal,
        () => _confirmAndAct(
          title: 'Re-seed Default Masters',
          body: "Re-seed default masters for '${t.name}'? This will re-run the master seeder job.",
          actionLabel: 'Re-seed',
          actionColor: AppColors.teal,
          action: () => PlatformTenantService.instance.reseedMasters(t.id),
        )));
    actions.add(_QuickAction('Archive', Icons.archive_outlined, AppColors.textSecondary,
        () => _confirmAndAct(
          title: 'Archive Hospital',
          body: "Archive '${t.name}'? Data is retained for 30 days.",
          actionLabel: 'Archive',
          actionColor: AppColors.red,
          action: () async {
            final r = await PlatformTenantService.instance.archive(t.id);
            if (r.success && mounted) Navigator.pop(context);
            return r;
          },
        )));

    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: actions.map((a) => OutlinedButton.icon(
        onPressed: a.onTap,
        icon: Icon(a.icon, size: 15),
        label: Text(a.label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
        style: OutlinedButton.styleFrom(
          foregroundColor: a.color,
          side: BorderSide(color: a.color.withValues(alpha: 0.4)),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.full)),
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          visualDensity: VisualDensity.compact,
        ),
      )).toList(),
    );
  }

  Widget _buildSubscriptions(List<SubscriptionItem> subs) {
    if (subs.isEmpty) {
      return Container(
        padding: const EdgeInsets.symmetric(vertical: 20),
        decoration: AppDecorations.card(),
        child: const AppEmptyState(message: 'No subscriptions yet.', icon: Icons.receipt_long_outlined),
      );
    }
    return Container(
      decoration: AppDecorations.card(),
      child: Column(
        children: subs.asMap().entries.map((e) {
          final s = e.value;
          return Column(
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                child: Row(
                  children: [
                    Expanded(child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(s.cycle.toUpperCase(), style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
                        Text(
                          '${_formatDate2(s.startsAt)} – ${_formatDate2(s.endsAt)}',
                          style: AppTextStyles.cardSubtitle,
                        ),
                      ],
                    )),
                    Text('₹${s.price.toStringAsFixed(0)}', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
                    const SizedBox(width: 8),
                    StatusBadge.subscriptionStatus(s.status),
                  ],
                ),
              ),
              if (e.key < subs.length - 1) Divider(height: 1, color: AppColors.primaryA05),
            ],
          );
        }).toList(),
      ),
    );
  }

  Widget _buildPayments(List<PaymentItem> payments) {
    if (payments.isEmpty) {
      return Container(
        padding: const EdgeInsets.symmetric(vertical: 20),
        decoration: AppDecorations.card(),
        child: const AppEmptyState(message: 'No payments yet.', icon: Icons.payment_outlined),
      );
    }
    return Container(
      decoration: AppDecorations.card(),
      child: Column(
        children: payments.asMap().entries.map((e) {
          final p = e.value;
          return Column(
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: AppDecorations.pill(
                        color: p.method == 'offline' ? AppColors.orange : AppColors.secondary,
                      ),
                      child: Text(
                        p.method.toUpperCase(),
                        style: TextStyle(fontSize: 9, fontWeight: FontWeight.w700,
                          color: p.method == 'offline' ? AppColors.orange : AppColors.secondary),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(p.cycle.toUpperCase(), style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.textPrimary)),
                        if (p.transactionId != null)
                          Text(p.transactionId!, style: AppTextStyles.cardSubtitle),
                      ],
                    )),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text('₹${p.amount.toStringAsFixed(0)}', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
                        StatusBadge.paymentStatus(p.status),
                      ],
                    ),
                  ],
                ),
              ),
              if (e.key < payments.length - 1) Divider(height: 1, color: AppColors.primaryA05),
            ],
          );
        }).toList(),
      ),
    );
  }

  String _formatDate(DateTime dt) {
    const m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return '${dt.day} ${m[dt.month - 1]} ${dt.year}';
  }

  String _formatDate2(DateTime? dt) => dt == null ? '—' : _formatDate(dt);

  String _formatDateTime(DateTime dt) {
    const m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return '${dt.day} ${m[dt.month - 1]} ${dt.year}';
  }
}

class _QuickAction {
  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;
  const _QuickAction(this.label, this.icon, this.color, this.onTap);
}
