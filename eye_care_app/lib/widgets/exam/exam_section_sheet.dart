import 'package:flutter/material.dart';
import '../../constants/app_colors.dart';
import 'exam_sections.dart';

/// Result of closing a section sheet.
/// - [done] = user tapped Done → auto-advance (web step-done-btn)
/// - [dismissed] = X / barrier → no advance
enum ExamSectionClose { done, dismissed }

/// Full-width, near-full-height bottom sheet section editor — mobile's own
/// pattern (matches the Add/Edit sheets already used across the app, e.g.
/// case_type/referrer/duration/location master screens), not tablet's
/// side-docked floating dialog. Phone screens have no room to keep a live
/// slip visible behind a side panel, so the editor takes the full width
/// instead — the dense RE/LE field tables (Vision/PG/ST/O/E/Fundus) need
/// every bit of it; the previous ~88%-width dialog was cropping their text.
Future<ExamSectionClose> showExamSectionSheet({
  required BuildContext context,
  required ExamSection section,
  required Widget Function(BuildContext sheetContext) builder,
  Listenable? listenable,
  String? doneLabel,
}) async {
  final result = await showModalBottomSheet<ExamSectionClose>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.white,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
    ),
    builder: (ctx) {
      final height = MediaQuery.sizeOf(ctx).height;
      Widget body = builder(ctx);
      if (listenable != null) {
        body = AnimatedBuilder(animation: listenable, builder: (_, __) => builder(ctx));
      }
      return SafeArea(
        child: SizedBox(
          height: height * 0.92,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                margin: const EdgeInsets.only(top: 10, bottom: 2),
                width: 40,
                height: 4,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.18),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 10, 8, 8),
                child: Row(children: [
                  Expanded(
                    child: Text(section.shortLabel, style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.primary)),
                  ),
                  IconButton(
                    tooltip: 'Close',
                    onPressed: () => Navigator.of(ctx).pop(ExamSectionClose.dismissed),
                    icon: const Icon(Icons.close_rounded),
                  ),
                ]),
              ),
              Divider(height: 1, color: AppColors.primaryA08),
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(12, 12, 12, 16),
                  child: body,
                ),
              ),
              Divider(height: 1, color: AppColors.primaryA08),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 10, 16, 14),
                child: Row(children: [
                  TextButton(
                    onPressed: () => Navigator.of(ctx).pop(ExamSectionClose.dismissed),
                    child: const Text('Close'),
                  ),
                  const Spacer(),
                  ElevatedButton.icon(
                    onPressed: () => Navigator.of(ctx).pop(ExamSectionClose.done),
                    icon: const Icon(Icons.check_rounded, size: 18),
                    label: Text(doneLabel ?? 'Done'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                    ),
                  ),
                ]),
              ),
            ],
          ),
        ),
      );
    },
  );
  return result ?? ExamSectionClose.dismissed;
}
