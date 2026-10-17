import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/permissions.dart';
import '../models/ot_report_models.dart';
import '../services/ot_report_service.dart';
import '../services/permission_service.dart';
import '../utils/app_route.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_item_grid.dart';
import '../widgets/app_refresh_indicator.dart';
import '../widgets/skeleton.dart';
import '../widgets/app_section_card.dart';
import 'ot_lens_report_screen.dart';
import 'ot_report_viewer_screen.dart';

class _ReportMeta {
  final IconData icon;
  final Color color;
  const _ReportMeta(this.icon, this.color);
}

// Same icon/color mapping as eye_care_tab's ot_reports_screen.dart — kept in
// sync deliberately so the same report shows the same icon/color on both
// apps.
Map<String, _ReportMeta> get _itemMeta => <String, _ReportMeta>{
  'appointments': _ReportMeta(Icons.event_note_rounded, AppColors.primary),
  'registration': _ReportMeta(Icons.assignment_ind_rounded, AppColors.green),
  'doctor-consultation': _ReportMeta(
    Icons.medical_services_rounded,
    AppColors.orange,
  ),
  'counselling': _ReportMeta(Icons.support_agent_rounded, AppColors.teal),
  'billing': _ReportMeta(Icons.receipt_long_rounded, AppColors.blue),
  'ot': _ReportMeta(Icons.local_hospital_rounded, AppColors.purple),
  'discharge': _ReportMeta(
    Icons.assignment_turned_in_rounded,
    AppColors.tealDark,
  ),
  'hospital-report': _ReportMeta(Icons.description_rounded, AppColors.primary),
  'surgery-wise': _ReportMeta(Icons.cut_rounded, AppColors.orange),
  'doctor-wise': _ReportMeta(Icons.person_rounded, AppColors.teal),
  'lens-usage': _ReportMeta(Icons.remove_red_eye_rounded, AppColors.blue),
  'lens-report': _ReportMeta(Icons.remove_red_eye_rounded, AppColors.purple),
  'complications': _ReportMeta(
    Icons.warning_amber_rounded,
    const Color(0xFFE74C3C),
  ),
  'daily-collection': _ReportMeta(Icons.today_rounded, AppColors.primary),
  'monthly-revenue': _ReportMeta(Icons.calendar_month_rounded, AppColors.teal),
  'package-wise': _ReportMeta(Icons.card_giftcard_rounded, AppColors.purple),
  'pending-payments': _ReportMeta(
    Icons.pending_actions_rounded,
    const Color(0xFFE74C3C),
  ),
};
_ReportMeta get _defaultItemMeta =>
    _ReportMeta(Icons.insights_rounded, AppColors.primary);
_ReportMeta _metaFor(String key) => _itemMeta[key] ?? _defaultItemMeta;

class _GroupMeta {
  final IconData icon;
  final Color color;
  const _GroupMeta(this.icon, this.color);
}

Map<String, _GroupMeta> get _groupMeta => <String, _GroupMeta>{
  'Operational Registers': _GroupMeta(
    Icons.list_alt_rounded,
    AppColors.primary,
  ),
  'Clinical Reports': _GroupMeta(
    Icons.medical_information_rounded,
    AppColors.purple,
  ),
  'Financial Reports': _GroupMeta(Icons.payments_rounded, AppColors.green),
};
_GroupMeta get _defaultGroupMeta =>
    _GroupMeta(Icons.folder_rounded, AppColors.primary);
_GroupMeta _groupMetaFor(String title) =>
    _groupMeta[title] ?? _defaultGroupMeta;

/// Round 3 Phase 8 — OT Reports catalogue. Web's real Reports index screen
/// is purely a grouped report-type browser — no KPI/dashboard-summary block
/// exists there (that finding, and the dead web code it traces to, is
/// documented in OT_WEB_PARITY_FIX_PRD.md §9.3). Kept deliberately minimal
/// to match.
///
/// Visual style matches masters_screen.dart's catalogue pattern (colored
/// section-pill headers + icon-tile grid, via the shared `AppSectionCard`/
/// `AppItemGrid` widgets) instead of the old plain text-row list, for
/// consistency with the rest of the app and with eye_care_tab's OT Reports
/// hub (2026-08-26, user-reported: "design layout bau kharab lage 6e").
class OtReportsScreen extends StatefulWidget {
  const OtReportsScreen({super.key});

  @override
  State<OtReportsScreen> createState() => _OtReportsScreenState();
}

class _OtReportsScreenState extends State<OtReportsScreen> {
  bool _loading = true;
  String? _error;
  List<OtReportTypeGroup> _groups = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final groups = await OtReportService.instance.fetchReportTypes();
      if (mounted)
        setState(() {
          _groups = groups;
          _loading = false;
        });
    } catch (e) {
      if (mounted)
        setState(() {
          _error = e.toString().replaceFirst('Exception: ', '');
          _loading = false;
        });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!PermissionService.instance.can(Perm.reportsView) &&
        !PermissionService.instance.can(Perm.otReportsView)) {
      return const Scaffold(
        body: Center(child: Text('No access to OT reports.')),
      );
    }
    final totalCount = _groups.fold(0, (sum, g) => sum + g.types.length);
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(
            Icons.arrow_back_ios_new_rounded,
            color: Colors.white,
            size: 20,
          ),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text(
          'OT Reports',
          style: TextStyle(
            color: Colors.white,
            fontSize: 18,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.2,
          ),
        ),
        actions: [
          if (!_loading && _error == null)
            Container(
              margin: const EdgeInsets.only(right: 12),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(
                color: AppColors.teal,
                borderRadius: BorderRadius.circular(30),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.teal.withValues(alpha: 0.38),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Text(
                '$totalCount reports',
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w800,
                  fontSize: 11,
                ),
              ),
            ),
        ],
      ),
      body: _loading
          ? const AppSkeletonList()
          : _error != null
          ? AppErrorState(message: _error!, onRetry: _load)
          : _buildBody(),
    );
  }

  Widget _buildBody() {
    return AppRefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(14, 14, 14, 20),
        children: [
          for (final group in _groups) ...[
            _sectionFor(group),
            const SizedBox(height: 14),
          ],
        ],
      ),
    );
  }

  Widget _sectionFor(OtReportTypeGroup group) {
    final meta = _groupMetaFor(group.group);
    return AppSectionCard(
      icon: meta.icon,
      title: group.group,
      color: meta.color,
      count: group.types.length,
      child: AppItemGrid(
        items: group.types
            .map(
              (t) => AppGridItem(
                icon: _metaFor(t.key).icon,
                label: t.label,
                color: _metaFor(t.key).color,
                onTap: () => Navigator.of(context).push(
                  appRoute(t.key == 'lens-report'
                      ? const OtLensReportScreen()
                      : OtReportViewerScreen(type: t.key, label: t.label)),
                ),
              ),
            )
            .toList(),
      ),
    );
  }
}
