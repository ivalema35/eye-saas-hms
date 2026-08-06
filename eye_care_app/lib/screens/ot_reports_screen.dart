import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/ot_report_models.dart';
import '../services/ot_report_service.dart';
import '../utils/app_route.dart';
import '../widgets/app_error_state.dart';
import 'ot_report_viewer_screen.dart';

/// Round 3 Phase 8 — OT Reports catalogue. Web's real Reports index screen
/// is purely a grouped report-type browser — no KPI/dashboard-summary block
/// exists there (that finding, and the dead web code it traces to, is
/// documented in OT_WEB_PARITY_FIX_PRD.md §9.3). Kept deliberately minimal
/// to match.
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
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        elevation: 0,
        leading: IconButton(icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20), onPressed: () => Navigator.pop(context)),
        title: const Text('OT Reports', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800, letterSpacing: -0.2)),
      ),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: AppColors.primary))
          : _error != null
              ? AppErrorState(message: _error!, onRetry: _load)
              : _buildBody(),
    );
  }

  Widget _buildBody() {
    return ListView(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 20),
      children: [
        for (final group in _groups) ...[
          Padding(padding: const EdgeInsets.only(bottom: 8), child: Text(group.group, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: AppColors.darkNavy))),
          ...group.types.map((t) => Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: InkWell(
                  onTap: () => Navigator.of(context).push(appRoute(OtReportViewerScreen(type: t.key, label: t.label))),
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.08))),
                    child: Row(children: [
                      Expanded(child: Text(t.label, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600))),
                      const Icon(Icons.chevron_right_rounded, color: AppColors.textDisabled),
                    ]),
                  ),
                ),
              )),
          const SizedBox(height: 12),
        ],
      ],
    );
  }
}
