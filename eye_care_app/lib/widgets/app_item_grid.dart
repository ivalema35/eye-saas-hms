import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';

/// One tile in an `AppItemGrid` — presentation-only (icon/label/color +
/// resolved tap callback). The caller resolves `onTap` itself (permission
/// checks, "coming soon" fallbacks, navigation, etc.) so this stays a pure
/// rendering widget, same design principle as `SplitPaneScaffold`.
class AppGridItem {
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;

  const AppGridItem({required this.icon, required this.label, required this.color, required this.onTap});
}

/// Shared 3-column icon-tile grid — extracted from masters_screen.dart's
/// private `_Grid`/`_Card` so other catalogue-style screens (e.g. OT
/// Reports) can reuse the exact same visual language instead of
/// duplicating it.
class AppItemGrid extends StatelessWidget {
  final List<AppGridItem> items;
  final bool compact;

  const AppItemGrid({super.key, required this.items, this.compact = false});

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(builder: (_, constraints) {
      final gap = compact ? 16.0 : 20.0;
      final w = (constraints.maxWidth - gap) / 3;
      return Wrap(
        spacing: compact ? 8 : 10,
        runSpacing: compact ? 8 : 10,
        children: [for (final item in items) SizedBox(width: w, child: _AppGridCard(item: item, compact: compact))],
      );
    });
  }
}

class _AppGridCard extends StatelessWidget {
  final AppGridItem item;
  final bool compact;
  const _AppGridCard({required this.item, this.compact = false});

  @override
  Widget build(BuildContext context) {
    final c = item.color;
    final iconBox = compact ? 30.0 : 44.0;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: item.onTap,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        splashColor: c.withValues(alpha: 0.12),
        highlightColor: c.withValues(alpha: 0.07),
        child: Ink(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(AppRadius.lg),
            border: Border.all(color: c.withValues(alpha: 0.14)),
            boxShadow: [BoxShadow(color: c.withValues(alpha: 0.09), blurRadius: 12, offset: const Offset(0, 3))],
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(AppRadius.lg),
            child: Stack(children: [
              Positioned(
                top: 0,
                left: 0,
                right: 0,
                child: Container(
                  height: 3,
                  decoration: BoxDecoration(gradient: LinearGradient(colors: [c, c.withValues(alpha: 0.35)], begin: Alignment.centerLeft, end: Alignment.centerRight)),
                ),
              ),
              Padding(
                padding: EdgeInsets.fromLTRB(6, compact ? 7 : 10, 6, compact ? 7 : 10),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Align(
                      alignment: Alignment.center,
                      child: Container(
                        width: iconBox,
                        height: iconBox,
                        decoration: BoxDecoration(
                          gradient: LinearGradient(colors: [c.withValues(alpha: 0.14), c.withValues(alpha: 0.04)], begin: Alignment.topLeft, end: Alignment.bottomRight),
                          borderRadius: BorderRadius.circular(AppRadius.md),
                          border: Border.all(color: c.withValues(alpha: 0.18)),
                        ),
                        child: Icon(item.icon, color: c, size: compact ? 16 : 22),
                      ),
                    ),
                    SizedBox(height: compact ? 5 : 8),
                    Text(
                      item.label,
                      textAlign: TextAlign.center,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.darkNavy, height: 1.2),
                    ),
                    if (!compact) ...[
                      const SizedBox(height: 4),
                      Align(alignment: Alignment.center, child: Icon(Icons.chevron_right_rounded, size: 12, color: c.withValues(alpha: 0.45))),
                    ],
                  ],
                ),
              ),
            ]),
          ),
        ),
      ),
    );
  }
}
