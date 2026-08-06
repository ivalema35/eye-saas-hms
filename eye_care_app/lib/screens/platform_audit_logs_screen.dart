import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/app_text_styles.dart';
import '../models/platform_admin_models.dart';
import '../models/platform_audit_log_models.dart';
import '../services/platform_audit_log_service.dart';
import '../utils/app_decorations.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_pagination_bar.dart';

class PlatformAuditLogsScreen extends StatefulWidget {
  final PlatformAdmin admin;

  const PlatformAuditLogsScreen({super.key, required this.admin});

  @override
  State<PlatformAuditLogsScreen> createState() => _PlatformAuditLogsScreenState();
}

class _PlatformAuditLogsScreenState extends State<PlatformAuditLogsScreen> {
  bool _loading = true;
  String? _error;
  PlatformAuditLogListResult? _result;
  int _page = 1;

  // Active filters
  String _actionQuery    = '';
  int?   _tenantIdFilter;
  String _dateFrom       = '';
  String _dateTo         = '';

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load({int page = 1}) async {
    setState(() { _loading = true; _error = null; });
    final result = await PlatformAuditLogService.instance.getLogs(
      action:   _actionQuery.isEmpty  ? null : _actionQuery,
      tenantId: _tenantIdFilter,
      from:     _dateFrom.isEmpty     ? null : _dateFrom,
      to:       _dateTo.isEmpty       ? null : _dateTo,
      page:     page,
    );
    if (!mounted) return;
    setState(() {
      _loading = false;
      _page    = page;
      if (result == null) {
        _error = 'Could not load audit logs.';
      } else {
        _result = result;
      }
    });
  }

  Future<void> _showFilters() async {
    final tenants = _result?.tenants ?? [];

    // Local copies for the sheet
    String action   = _actionQuery;
    int?   tenantId = _tenantIdFilter;
    String from     = _dateFrom;
    String to       = _dateTo;

    final actionCtrl = TextEditingController(text: action);
    final fromCtrl   = TextEditingController(text: from);
    final toCtrl     = TextEditingController(text: to);

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, set) => Padding(
          padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom),
          child: Container(
            decoration: BoxDecoration(
              color: AppColors.background,
              borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const SizedBox(height: 10),
                Container(width: 36, height: 4,
                    decoration: BoxDecoration(color: AppColors.primaryA20, borderRadius: BorderRadius.circular(2))),
                const SizedBox(height: 12),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20),
                  child: Row(
                    children: [
                      Expanded(child: Text('Filter Audit Logs', style: AppTextStyles.headingSmall)),
                      TextButton(
                        onPressed: () {
                          set(() { action = ''; tenantId = null; from = ''; to = ''; });
                          actionCtrl.clear(); fromCtrl.clear(); toCtrl.clear();
                        },
                        child: Text('Clear All', style: TextStyle(color: AppColors.red)),
                      ),
                    ],
                  ),
                ),
                Divider(color: AppColors.primaryA12, height: 1),
                Padding(
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 30),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Action search
                      Text('Action', style: AppTextStyles.sectionLabel),
                      const SizedBox(height: 6),
                      TextField(
                        controller: actionCtrl,
                        onChanged: (v) => set(() => action = v),
                        decoration: AppDecorations.inputDecoration(hintText: 'Search actions... e.g. hospital.suspended', isDense: true),
                      ),
                      const SizedBox(height: 14),

                      // Hospital filter
                      Text('Hospital', style: AppTextStyles.sectionLabel),
                      const SizedBox(height: 6),
                      DropdownButtonFormField<int?>(
                        key: ValueKey(tenantId),
                        initialValue: tenantId,
                        decoration: AppDecorations.inputDecoration(hintText: 'All Hospitals', isDense: true),
                        items: [
                          const DropdownMenuItem(value: null, child: Text('All Hospitals')),
                          ...tenants.map((t) => DropdownMenuItem(value: t.id, child: Text(t.name, overflow: TextOverflow.ellipsis))),
                        ],
                        onChanged: (v) => set(() => tenantId = v),
                      ),
                      const SizedBox(height: 14),

                      // Date range
                      Text('Date Range', style: AppTextStyles.sectionLabel),
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          Expanded(child: TextField(controller: fromCtrl, onChanged: (v) => set(() => from = v), decoration: AppDecorations.inputDecoration(hintText: 'From (YYYY-MM-DD)', isDense: true))),
                          const SizedBox(width: 10),
                          Expanded(child: TextField(controller: toCtrl, onChanged: (v) => set(() => to = v), decoration: AppDecorations.inputDecoration(hintText: 'To (YYYY-MM-DD)', isDense: true))),
                        ],
                      ),
                      const SizedBox(height: 20),

                      SizedBox(
                        width: double.infinity,
                        height: 48,
                        child: ElevatedButton(
                          onPressed: () {
                            Navigator.pop(ctx);
                            setState(() {
                              _actionQuery    = action;
                              _tenantIdFilter = tenantId;
                              _dateFrom       = from;
                              _dateTo         = to;
                            });
                            _load();
                          },
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            foregroundColor: Colors.white,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
                            elevation: 0,
                          ),
                          child: Text('Apply Filters',
                              style: GoogleFonts.poppins(fontSize: 14, fontWeight: FontWeight.w800)),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );

    actionCtrl.dispose();
    fromCtrl.dispose();
    toCtrl.dispose();
  }

  bool get _hasActiveFilters =>
      _actionQuery.isNotEmpty || _tenantIdFilter != null || _dateFrom.isNotEmpty || _dateTo.isNotEmpty;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Audit Logs', style: GoogleFonts.poppins(fontSize: 16, fontWeight: FontWeight.w800, color: Colors.white)),
            if (_result != null)
              Text('${_result!.total} entries', style: GoogleFonts.poppins(fontSize: 10, color: Colors.white70)),
          ],
        ),
        actions: [
          Stack(
            children: [
              IconButton(
                icon: const Icon(Icons.filter_list_rounded),
                tooltip: 'Filter',
                onPressed: _showFilters,
              ),
              if (_hasActiveFilters)
                Positioned(
                  right: 8, top: 8,
                  child: Container(
                    width: 8, height: 8,
                    decoration: BoxDecoration(color: AppColors.orange, shape: BoxShape.circle),
                  ),
                ),
            ],
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? AppErrorState(message: _error!, onRetry: _load)
              : (_result?.logs.isEmpty ?? true)
                  ? AppEmptyState(
                      message: 'No audit logs found.',
                      icon: Icons.history_outlined,
                      onRefresh: _load,
                    )
                  : RefreshIndicator(
                      color: AppColors.primary,
                      onRefresh: () => _load(page: _page),
                      child: ListView.builder(
                        padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
                        itemCount: _result!.logs.length + 1,
                        itemBuilder: (_, i) {
                          if (i == _result!.logs.length) {
                            return Padding(
                              padding: const EdgeInsets.only(top: 12),
                              child: AppPaginationBar(
                                currentPage: _page,
                                totalPages: _result!.lastPage,
                                onPageChange: (p) => _load(page: p),
                              ),
                            );
                          }
                          return AnimatedListItem(
                            index: i,
                            child: _AuditLogCard(log: _result!.logs[i]),
                          );
                        },
                      ),
                    ),
    );
  }

}

class _AuditLogCard extends StatelessWidget {
  final PlatformAuditLog log;
  const _AuditLogCard({required this.log});

  @override
  Widget build(BuildContext context) {
    final color = log.actionColor;
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: AppDecorations.card(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(AppRadius.full),
                ),
                child: Text(
                  log.action,
                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: color),
                ),
              ),
              const Spacer(),
              if (log.createdAt != null)
                Text(_formatDateTime(log.createdAt!), style: AppTextStyles.cardSubtitle),
            ],
          ),
          if (log.description != null && log.description!.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(log.description!, style: AppTextStyles.bodyMedium),
          ],
          const SizedBox(height: 6),
          Row(
            children: [
              if (log.tenantName != null) ...[
                _chip(Icons.local_hospital_outlined, log.tenantName!, AppColors.secondary),
                const SizedBox(width: 6),
              ] else
                _chip(Icons.shield_outlined, 'Platform', AppColors.primary),
              if (log.adminName != null) ...[
                const SizedBox(width: 6),
                _chip(Icons.person_outline_rounded, log.adminName!, AppColors.textSecondary),
              ],
              if (log.ipAddress != null) ...[
                const Spacer(),
                Text(log.ipAddress!, style: TextStyle(fontSize: 10, fontFamily: 'monospace', color: AppColors.textDisabled)),
              ],
            ],
          ),
        ],
      ),
    );
  }

  Widget _chip(IconData icon, String label, Color color) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 11, color: color),
        const SizedBox(width: 3),
        Text(label, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: color)),
      ],
    );
  }

  String _formatDateTime(DateTime dt) {
    const m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    final h = dt.hour.toString().padLeft(2, '0');
    final min = dt.minute.toString().padLeft(2, '0');
    return '${dt.day} ${m[dt.month - 1]}, $h:$min';
  }
}
