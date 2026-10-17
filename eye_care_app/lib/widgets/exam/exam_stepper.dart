import 'package:flutter/material.dart';
import '../../constants/app_colors.dart';
import '../../constants/app_radius.dart';
import 'exam_sections.dart';

/// Left sticky stepper — mirrors web doctor sidebar.
class ExamStepper extends StatelessWidget {
  final List<ExamSection> sections;
  final ExamSection? active;
  final Set<ExamSection> filled;
  final ValueChanged<ExamSection> onSelect;
  final Widget? footer;

  const ExamStepper({
    super.key,
    required this.sections,
    required this.active,
    required this.filled,
    required this.onSelect,
    this.footer,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 168,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: AppColors.primaryA10),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 14, 12, 8),
            child: Text('EXAM STEPS', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, letterSpacing: 0.8, color: AppColors.primaryA55)),
          ),
          Expanded(
            child: ListView.separated(
              padding: const EdgeInsets.fromLTRB(8, 0, 8, 12),
              itemCount: sections.length,
              separatorBuilder: (_, __) => const SizedBox(height: 4),
              itemBuilder: (_, i) {
                final s = sections[i];
                final isActive = s == active;
                final isFilled = filled.contains(s);
                return Material(
                  color: isActive
                      ? AppColors.primary
                      : isFilled
                          ? AppColors.primaryA12
                          : Colors.transparent,
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  child: InkWell(
                    borderRadius: BorderRadius.circular(AppRadius.md),
                    onTap: () => onSelect(s),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                      child: Row(children: [
                        Container(
                          width: 22,
                          height: 22,
                          alignment: Alignment.center,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: isActive
                                ? Colors.white.withValues(alpha: 0.2)
                                : isFilled
                                    ? AppColors.primary
                                    : AppColors.primaryA10,
                          ),
                          child: Text(
                            '${i + 1}',
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w800,
                              color: isActive || isFilled ? Colors.white : AppColors.primaryA60,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            s.shortLabel,
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              color: isActive ? Colors.white : AppColors.primary,
                            ),
                          ),
                        ),
                        if (isFilled && !isActive)
                          Icon(Icons.check_rounded, size: 14, color: AppColors.primary),
                      ]),
                    ),
                  ),
                );
              },
            ),
          ),
          if (footer != null) ...[
            Divider(height: 1, color: AppColors.primaryA08),
            Padding(padding: const EdgeInsets.all(10), child: footer!),
          ],
        ],
      ),
    );
  }
}

/// Compact horizontal chip bar for phone portrait — same filled/active semantics.
class ExamChipBar extends StatelessWidget {
  final List<ExamSection> sections;
  final ExamSection? active;
  final Set<ExamSection> filled;
  final ValueChanged<ExamSection> onSelect;
  final Color? activeColor;
  final ExamSection? secondaryFrom;

  const ExamChipBar({
    super.key,
    required this.sections,
    required this.active,
    required this.filled,
    required this.onSelect,
    this.activeColor,
    this.secondaryFrom,
  });

  @override
  Widget build(BuildContext context) {
    final accent = activeColor ?? AppColors.primary;
    return SizedBox(
      height: secondaryFrom == null ? 44 : 52,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        children: [
          for (var i = 0; i < sections.length; i++) ...[
            if (secondaryFrom != null && i == 0) _groupTag('Primary'),
            if (secondaryFrom != null && sections[i] == secondaryFrom) _groupTag('Secondary'),
            _chip(sections[i], i, accent),
            const SizedBox(width: 6),
          ],
        ],
      ),
    );
  }

  Widget _groupTag(String text) => Padding(
        padding: const EdgeInsets.only(right: 6),
        child: Center(child: Text(text.toUpperCase(), style: TextStyle(fontSize: 9, fontWeight: FontWeight.w800, letterSpacing: 0.4, color: AppColors.primaryA55))),
      );

  Widget _chip(ExamSection s, int i, Color accent) {
    final isActive = s == active;
    final isFilled = filled.contains(s);
    return Material(
            color: isActive
                ? accent
                : isFilled
                    ? AppColors.primaryA12
                    : Colors.white,
            borderRadius: BorderRadius.circular(AppRadius.xl),
            child: InkWell(
              borderRadius: BorderRadius.circular(AppRadius.xl),
              onTap: () => onSelect(s),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(AppRadius.xl),
                  border: Border.all(
                    color: isActive
                        ? accent
                        : isFilled
                            ? AppColors.primary.withValues(alpha: 0.35)
                            : AppColors.primaryA10,
                  ),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      '${i + 1}',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w800,
                        color: isActive ? Colors.white : AppColors.primaryA60,
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text(
                      s.shortLabel,
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: isActive ? Colors.white : AppColors.primary,
                      ),
                    ),
                    if (isFilled && !isActive) ...[
                      const SizedBox(width: 4),
                      Icon(Icons.check_rounded, size: 12, color: accent),
                    ],
                  ],
                ),
              ),
            ),
    );
  }
}
