import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../utils/date_format.dart';
import '../utils/app_date_picker.dart';

/// One filter choice for [OtDeskFilterBar]'s segmented control.
class OtFilterOption {
  final String value;
  final String label;
  const OtFilterOption(this.value, this.label);
}

/// The Queue/All (or Queue/Completed/Refunds) filter + optional date-range
/// row every OT desk screen (Counsellor/Accountant/Ward/Assistant/Discharge)
/// shows.
///
/// Redesigned 2026-09-21 after the first positional-consistency pass (same
/// slot on all 5 screens) still read as cramped — a small pill glued to the
/// left with a tiny boxed date chip beside it. Per user request this was
/// rebuilt from real research (Material 3 `SegmentedButton` guidance +
/// Fitts's-Law full-width-touch-target guidance for mobile filter controls):
/// a full-width Material 3 segmented control (thumb-friendly, all options
/// visible at once, no cramped small pill) plus, when a date range applies,
/// a full-width tappable row below it (icon + range text + chevron) instead
/// of a small chip squeezed next to the toggle.
///
/// [options] generalizes the control so it now also replaces OT Accountant's
/// separate `TabBar`/`TabController` (3 options instead of 2) — one shared
/// widget for all 5 screens instead of a per-screen control.
///
/// Deliberately paints no background of its own beyond the translucent
/// capsule it draws for the segmented control — it relies on whatever
/// already-colored surface it sits inside (the AppBar's own
/// `backgroundColor`, or OT Ward/Accountant's gradient Container).
class OtDeskFilterBar extends StatelessWidget implements PreferredSizeWidget {
  final List<OtFilterOption> options;
  final String selected;
  final ValueChanged<String> onChanged;
  final bool showDateRange;
  final DateTime? from;
  final DateTime? to;
  final ValueChanged<DateTimeRange>? onDateRangeChanged;

  const OtDeskFilterBar({
    super.key,
    required this.options,
    required this.selected,
    required this.onChanged,
    this.showDateRange = false,
    this.from,
    this.to,
    this.onDateRangeChanged,
  });

  @override
  Size get preferredSize => Size.fromHeight(showDateRange ? 104 : 60);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 2, 14, 12),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        _buildSegmented(),
        if (showDateRange) ...[
          const SizedBox(height: 8),
          _DateRangeRow(from: from!, to: to!, onChanged: onDateRangeChanged!),
        ],
      ]),
    );
  }

  Widget _buildSegmented() {
    return Container(
      height: 40,
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.18), borderRadius: BorderRadius.circular(AppRadius.md)),
      child: Row(children: [
        Expanded(
          child: SegmentedButton<String>(
            segments: options.map((o) => ButtonSegment(value: o.value, label: Text(o.label))).toList(),
            selected: {selected},
            showSelectedIcon: false,
            onSelectionChanged: (s) => onChanged(s.first),
            style: ButtonStyle(
              backgroundColor: WidgetStateProperty.resolveWith((states) => states.contains(WidgetState.selected) ? Colors.white : Colors.transparent),
              foregroundColor: WidgetStateProperty.resolveWith((states) => states.contains(WidgetState.selected) ? AppColors.primary : Colors.white),
              overlayColor: WidgetStateProperty.all(Colors.white.withValues(alpha: 0.12)),
              side: WidgetStateProperty.all(BorderSide.none),
              shape: WidgetStateProperty.all(RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.sm))),
              textStyle: WidgetStateProperty.all(const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700)),
              padding: WidgetStateProperty.all(EdgeInsets.zero),
              elevation: WidgetStateProperty.all(0),
              shadowColor: WidgetStateProperty.all(Colors.transparent),
              visualDensity: VisualDensity.compact,
              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
            ),
          ),
        ),
      ]),
    );
  }
}

class _DateRangeRow extends StatelessWidget {
  final DateTime from;
  final DateTime to;
  final ValueChanged<DateTimeRange> onChanged;

  const _DateRangeRow({required this.from, required this.to, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    final sameDay = from.year == to.year && from.month == to.month && from.day == to.day;
    final label = sameDay ? toDisplayDate(from) : '${toDisplayDate(from)} – ${toDisplayDate(to)}';
    return Material(
      color: Colors.white.withValues(alpha: 0.18),
      borderRadius: BorderRadius.circular(AppRadius.md),
      child: InkWell(
        borderRadius: BorderRadius.circular(AppRadius.md),
        onTap: () async {
          final now = DateTime.now();
          final picked = await showAppDateRangePicker(
            context: context,
            firstDate: DateTime(now.year - 3),
            lastDate: now.add(const Duration(days: 1)),
            initialDateRange: DateTimeRange(start: from, end: to),
          );
          if (picked != null) onChanged(picked);
        },
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
          child: Row(children: [
            const Icon(Icons.calendar_today_rounded, size: 14, color: Colors.white),
            const SizedBox(width: 8),
            Expanded(child: Text(label, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: Colors.white))),
            const Icon(Icons.keyboard_arrow_down_rounded, size: 18, color: Colors.white),
          ]),
        ),
      ),
    );
  }
}
