import 'package:flutter/material.dart';

/// Shared "Pattern A" section header — a colored pill badge (icon + title)
/// plus a count chip, wrapping arbitrary content. Extracted from
/// masters_screen.dart's private `_SectionCard` so other catalogue-style
/// screens (e.g. OT Reports) can reuse the exact same visual language
/// instead of duplicating it.
class AppSectionCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final Color color;
  final int count;
  final Widget child;

  const AppSectionCard({
    super.key,
    required this.icon,
    required this.title,
    required this.color,
    required this.count,
    required this.child,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: color.withValues(alpha: 0.08)),
        boxShadow: [
          BoxShadow(color: color.withValues(alpha: 0.07), blurRadius: 24, offset: const Offset(0, 6)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.fromLTRB(16, 13, 14, 13),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.06),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(22)),
              border: Border(bottom: BorderSide(color: color.withValues(alpha: 0.12), width: 1)),
            ),
            child: Row(children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
                decoration: BoxDecoration(
                  gradient: LinearGradient(colors: [color, color.withValues(alpha: 0.72)], begin: Alignment.topLeft, end: Alignment.bottomRight),
                  borderRadius: BorderRadius.circular(999),
                  boxShadow: [BoxShadow(color: color.withValues(alpha: 0.32), blurRadius: 10, offset: const Offset(0, 3))],
                ),
                child: Row(mainAxisSize: MainAxisSize.min, children: [
                  Icon(icon, color: Colors.white, size: 13),
                  const SizedBox(width: 6),
                  Text(title.toUpperCase(), style: const TextStyle(color: Colors.white, fontSize: 9.5, fontWeight: FontWeight.w900, letterSpacing: 0.8)),
                ]),
              ),
              const Spacer(),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(color: color.withValues(alpha: 0.10), borderRadius: BorderRadius.circular(999), border: Border.all(color: color.withValues(alpha: 0.20))),
                child: Text('$count', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: color)),
              ),
            ]),
          ),
          Padding(padding: const EdgeInsets.fromLTRB(14, 14, 14, 14), child: child),
        ],
      ),
    );
  }
}
