import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/patient_history_models.dart';
import '../models/patient_models.dart';
import '../services/patient_history_service.dart';
import '../utils/date_format.dart';
import '../widgets/app_error_state.dart';
import '../widgets/exam_clinical_data_view.dart';
import '../widgets/skeleton.dart';

/// Doctor Dashboard exam module. Shows the patient's most recent examination
/// in the same box layout as one card of the clinical timeline
/// (`buildExamClinicalData`), and nothing else.
///
/// Web (`doctoredashboard.blade.php`):
/// - Primary queue "View" opens the full history page. The apps do not.
///   This module shows only the last filled exam.
/// - Secondary queue eye button opens the compact HUD of this visit's
///   primary exam. For a patient still waiting on secondary, that primary
///   is the latest exam, so the same module matches it.
///
/// The full multi-visit history screen stays on Patients and Share History.
Future<void> showLatestExamView(BuildContext context, Patient patient) {
  return showDialog<void>(
    context: context,
    useRootNavigator: true,
    builder: (ctx) {
      final height = MediaQuery.sizeOf(ctx).height;
      return Dialog(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        insetPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
        clipBehavior: Clip.antiAlias,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
        child: SizedBox(
          width: 920,
          height: height * 0.88,
          child: LatestExamViewScreen(patient: patient),
        ),
      );
    },
  );
}

class LatestExamViewScreen extends StatefulWidget {
  final Patient patient;

  const LatestExamViewScreen({super.key, required this.patient});

  @override
  State<LatestExamViewScreen> createState() => _LatestExamViewScreenState();
}

class _LatestExamViewScreenState extends State<LatestExamViewScreen> {
  bool _loading = true;
  String? _error;
  ExamRecord? _exam;
  Map<int, String> _diagnosisMasters = const {};

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final data = await PatientHistoryService.instance.fetchHistory(widget.patient.id);
      // Web: `loadExamHistoryForIds()` → `->sortByDesc('examined_at')` — the
      // most recent visit is whichever exam has the latest `examined_at`,
      // not necessarily `exams.first` if the API's own ordering ever drifts.
      ExamRecord? latest;
      for (final e in data.exams) {
        if (latest == null || e.examinedAt.isAfter(latest.examinedAt)) latest = e;
      }
      if (mounted) {
        setState(() {
          _exam = latest;
          _diagnosisMasters = data.diagnosisMasters;
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = widget.patient;
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: Text(p.fullName, style: const TextStyle(fontWeight: FontWeight.w800)),
      ),
      body: Padding(
        padding: const EdgeInsets.all(14),
        child: _loading
            ? const AppSkeletonList(count: 5, itemHeight: 70)
            : _error != null
                ? AppErrorState(message: _error!, onRetry: _load)
                : _exam == null
                    ? Center(
                        child: Column(mainAxisSize: MainAxisSize.min, children: [
                          Icon(Icons.folder_open_rounded, size: 38, color: AppColors.primaryA50),
                          const SizedBox(height: 12),
                          Text('No examination records found.', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.primaryA62)),
                        ]),
                      )
                    : SingleChildScrollView(
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          _buildHeader(_exam!),
                          const SizedBox(height: 14),
                          buildExamClinicalData(_exam!, _diagnosisMasters, onlyFilled: true),
                        ]),
                      ),
      ),
    );
  }

  Widget _buildHeader(ExamRecord exam) {
    final isPrimary = exam.isPrimary;
    final accent = isPrimary ? AppColors.blue : AppColors.purple;
    final label = isPrimary ? 'Primary Exam' : 'Secondary Exam';
    final icon = isPrimary ? Icons.assignment_outlined : Icons.remove_red_eye_outlined;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(color: accent.withValues(alpha: 0.08), borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: accent.withValues(alpha: 0.18))),
      child: Wrap(crossAxisAlignment: WrapCrossAlignment.center, spacing: 10, runSpacing: 4, children: [
        Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(icon, size: 18, color: accent),
          const SizedBox(width: 8),
          Text(label, style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w800, color: accent)),
        ]),
        if (exam.doctorName != null)
          Row(mainAxisSize: MainAxisSize.min, children: [
            Icon(Icons.person_outline_rounded, size: 13, color: AppColors.textSecondary),
            const SizedBox(width: 4),
            Text('Dr. ${exam.doctorName}', style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: AppColors.textSecondary)),
          ]),
        Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(Icons.access_time_rounded, size: 13, color: AppColors.textSecondary),
          const SizedBox(width: 4),
          Text(toDisplayDateTime(exam.examinedAt), style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: AppColors.textSecondary)),
        ]),
      ]),
    );
  }
}
