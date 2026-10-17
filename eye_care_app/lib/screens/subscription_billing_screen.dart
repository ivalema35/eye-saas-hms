import 'package:flutter/material.dart';
import '../widgets/app_refresh_indicator.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/subscription_billing_models.dart';
import '../services/subscription_billing_service.dart';
import '../utils/date_format.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_error_state.dart';
import '../widgets/skeleton.dart';

/// Web pull parity (2026-09-29) — `hospital.subscription.index`'s "Plan
/// History" list + tenant header, VIEW-only (see
/// WEB_VIEW_BUTTONS_PARITY_AUDIT.md finding #1 — the actual renew/checkout
/// flow is web-only, not ported here). Reachable from the dashboard's
/// "Renew →" banner, which was previously a dead, non-tappable label.
class SubscriptionBillingScreen extends StatefulWidget {
  const SubscriptionBillingScreen({super.key});

  @override
  State<SubscriptionBillingScreen> createState() => _SubscriptionBillingScreenState();
}

class _SubscriptionBillingScreenState extends State<SubscriptionBillingScreen> {
  SubscriptionBillingData? _data;
  bool _loading = true;
  String? _error;
  int? _downloadingPaymentId;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final data = await SubscriptionBillingService.instance.fetch();
      if (mounted) setState(() { _data = data; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  Future<void> _downloadInvoice(int paymentId) async {
    setState(() => _downloadingPaymentId = paymentId);
    try {
      await SubscriptionBillingService.instance.downloadInvoice(paymentId);
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    } finally {
      if (mounted) setState(() => _downloadingPaymentId = null);
    }
  }

  Color _statusColor(String status) => switch (status) {
        'Active' => AppColors.green,
        'Extended' => AppColors.blue,
        _ => AppColors.red,
      };

  @override
  Widget build(BuildContext context) {
    final days = _data?.subscriptionDaysLeft;
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        elevation: 0,
        leading: IconButton(icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20), onPressed: () => Navigator.pop(context)),
        title: const Text('Subscription & Billing', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800, letterSpacing: -0.2)),
        actions: [IconButton(icon: const Icon(Icons.refresh_rounded, color: Colors.white), onPressed: _load)],
      ),
      body: _loading
          ? const AppSkeletonList()
          : _error != null
              ? AppErrorState(message: _error!, onRetry: _load)
              : AppRefreshIndicator(color: AppColors.primary, onRefresh: _load, child: _buildBody(days)),
    );
  }

  Widget _buildBody(int? days) {
    final rows = _data?.historyRows ?? const <SubscriptionHistoryRow>[];
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 20),
      children: [
        _buildHeaderCard(days),
        const SizedBox(height: 14),
        Text('Plan History', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: AppColors.primary)),
        const SizedBox(height: 8),
        if (rows.isEmpty)
          const AppEmptyState(message: 'No plan history yet.', icon: Icons.receipt_long_rounded)
        else
          ...rows.map(_historyCard),
      ],
    );
  }

  Widget _buildHeaderCard(int? days) {
    final expired = days != null && days <= 0;
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.lg), boxShadow: [
        BoxShadow(color: Colors.black.withValues(alpha: 0.06), blurRadius: 10, offset: const Offset(0, 3)),
      ]),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(_data?.tenantName ?? '', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: AppColors.primary)),
        const SizedBox(height: 4),
        Text(
          [if (_data?.countryName.isNotEmpty ?? false) _data!.countryName, if (_data?.currencyCode.isNotEmpty ?? false) '${_data!.currencyCode} (${_data!.currencySymbol})'].join('  ·  '),
          style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
        ),
        if (days != null) ...[
          const SizedBox(height: 10),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(color: (expired ? AppColors.red : AppColors.blue).withValues(alpha: 0.12), borderRadius: BorderRadius.circular(AppRadius.full)),
            child: Row(mainAxisSize: MainAxisSize.min, children: [
              Icon(Icons.access_time_rounded, size: 14, color: expired ? AppColors.red : AppColors.blue),
              const SizedBox(width: 6),
              Text(expired ? 'Plan expired' : '$days day${days == 1 ? '' : 's'} remaining',
                  style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: expired ? AppColors.red : AppColors.blue)),
            ]),
          ),
        ],
      ]),
    );
  }

  Widget _historyCard(SubscriptionHistoryRow r) {
    final color = _statusColor(r.status);
    final busy = _downloadingPaymentId == r.paymentId;
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.lg), boxShadow: [
        BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 8, offset: const Offset(0, 2)),
      ]),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Expanded(child: Text(r.type, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.primary))),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(AppRadius.full)),
            child: Text(r.status, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: color)),
          ),
        ]),
        const SizedBox(height: 8),
        Text(
          '${r.start != null ? toDisplayDate(r.start!) : '—'}  →  ${r.end != null ? toDisplayDate(r.end!) : '—'}',
          style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary),
        ),
        const SizedBox(height: 4),
        Text('Days Used: ${r.daysUsed ?? '—'}   ·   Days Left: ${r.daysRemaining ?? '—'}', style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
        if (r.hasInvoice) ...[
          const SizedBox(height: 8),
          Align(
            alignment: Alignment.centerRight,
            child: busy
                ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                : OutlinedButton.icon(
                    onPressed: () => _downloadInvoice(r.paymentId!),
                    icon: const Icon(Icons.picture_as_pdf_outlined, size: 15),
                    label: const Text('Invoice', style: TextStyle(fontSize: 11.5)),
                    style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6), minimumSize: Size.zero, tapTargetSize: MaterialTapTargetSize.shrinkWrap),
                  ),
          ),
        ],
      ]),
    );
  }
}
