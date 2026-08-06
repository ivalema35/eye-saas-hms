import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/ot_report_models.dart';
import '../services/ot_report_service.dart';
import '../utils/app_route.dart';
import '../widgets/app_error_state.dart';
import 'ot_report_viewer_screen.dart';

/// Tablet OT Reports catalogue (Round 3 Phase 8) — top-level nav
/// destination, no own Scaffold. Web's real Reports index screen is purely
/// a grouped report-type browser — no KPI/dashboard-summary block exists
/// there (see OT_WEB_PARITY_FIX_PRD.md §9.3). Ported from
/// eye_care_app/lib/screens/ot_reports_screen.dart.
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
    setState(() { _loading = true; _error = null; });
    try {
      final groups = await OtReportService.instance.fetchReportTypes();
      if (mounted) setState(() { _groups = groups; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Row(children: [
        Icon(Icons.insights_rounded, color: AppColors.primary, size: 22),
        const SizedBox(width: 10),
        const Text('OT Reports', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: AppColors.textPrimary)),
      ]),
      const SizedBox(height: 16),
      Expanded(
        child: _loading
            ? Center(child: CircularProgressIndicator(color: AppColors.primary))
            : _error != null
                ? AppErrorState(message: _error!, onRetry: _load)
                : _buildBody(),
      ),
    ]);
  }

  Widget _buildBody() {
    return ListView(
      children: [
        for (final group in _groups) ...[
          Padding(padding: const EdgeInsets.only(bottom: 8), child: Text(group.group, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800))),
          Wrap(spacing: 10, runSpacing: 10, children: group.types.map((t) => InkWell(
                onTap: () => Navigator.of(context, rootNavigator: true).push(appRoute(OtReportViewerScreen(type: t.key, label: t.label))),
                borderRadius: BorderRadius.circular(AppRadius.md),
                child: Container(
                  width: 220,
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primaryA08)),
                  child: Row(children: [
                    Expanded(child: Text(t.label, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600), overflow: TextOverflow.ellipsis)),
                    const Icon(Icons.chevron_right_rounded, color: AppColors.textDisabled, size: 18),
                  ]),
                ),
              )).toList()),
          const SizedBox(height: 16),
        ],
      ],
    );
  }
}
