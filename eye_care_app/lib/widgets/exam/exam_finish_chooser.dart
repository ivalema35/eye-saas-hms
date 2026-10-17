import 'package:flutter/material.dart';
import '../../constants/app_colors.dart';
import '../../constants/app_radius.dart';

enum ExamFinishChoice { recommendOt, saveSecondary }

/// Short sheet after Advice. Recommend OT opens the real fields sheet.
/// Save Secondary finishes without OT.
Future<ExamFinishChoice?> showExamFinishChooser(
  BuildContext context, {
  required bool canRecommend,
}) {
  return showModalBottomSheet<ExamFinishChoice>(
    context: context,
    backgroundColor: Colors.white,
    shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
    builder: (ctx) => SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
        child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          Center(child: Container(width: 36, height: 4, decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(4)))),
          const SizedBox(height: 14),
          const Text('Finish examination', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.darkNavy)),
          const SizedBox(height: 6),
          const Text(
            'Save secondary here, or open Recommend OT if surgery is needed.',
            style: TextStyle(fontSize: 13, color: AppColors.textSecondary, height: 1.35),
          ),
          const SizedBox(height: 16),
          if (canRecommend) ...[
            OutlinedButton.icon(
              onPressed: () => Navigator.pop(ctx, ExamFinishChoice.recommendOt),
              icon: const Icon(Icons.medical_services_rounded, size: 18),
              label: const Text('Recommend OT'),
              style: OutlinedButton.styleFrom(
                foregroundColor: AppColors.teal,
                padding: const EdgeInsets.symmetric(vertical: 12),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
              ),
            ),
            const SizedBox(height: 8),
          ],
          ElevatedButton.icon(
            onPressed: () => Navigator.pop(ctx, ExamFinishChoice.saveSecondary),
            icon: const Icon(Icons.save_rounded, size: 18),
            label: const Text('Save Secondary'),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.teal,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 12),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
            ),
          ),
        ]),
      ),
    ),
  );
}
