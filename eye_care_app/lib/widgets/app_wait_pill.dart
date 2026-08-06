import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../models/clinical_queue_models.dart';

class AppWaitPill extends StatefulWidget {
  final DateTime waitFrom;
  final String label;           // 'R', 'D', or 'ND'
  final WaitThresholds thresholds;

  const AppWaitPill({
    super.key,
    required this.waitFrom,
    required this.label,
    required this.thresholds,
  });

  @override
  State<AppWaitPill> createState() => _AppWaitPillState();
}

class _AppWaitPillState extends State<AppWaitPill>
    with SingleTickerProviderStateMixin {
  late AnimationController _pulseCtrl;
  late Animation<double> _pulseAnim;

  @override
  void initState() {
    super.initState();
    _pulseCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1000),
    )..repeat(reverse: true);
    _pulseAnim = Tween<double>(begin: 0.30, end: 0.70)
        .animate(CurvedAnimation(parent: _pulseCtrl, curve: Curves.easeInOut));
  }

  @override
  void dispose() {
    _pulseCtrl.dispose();
    super.dispose();
  }

  int get _mins => DateTime.now().difference(widget.waitFrom).inMinutes;

  Color _color(int mins) {
    if (mins < widget.thresholds.greenMax)  return AppColors.waitGreen;
    if (mins < widget.thresholds.orangeMax) return AppColors.waitOrange;
    return AppColors.waitRed;
  }

  @override
  Widget build(BuildContext context) {
    final mins = _mins;
    final col  = _color(mins);
    final fire = mins >= widget.thresholds.redMax;

    return AnimatedBuilder(
      animation: _pulseAnim,
      builder: (_, _) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(
          color: col.withValues(alpha: 0.10),
          borderRadius: BorderRadius.circular(999),
          border: Border.all(
            color: col.withValues(alpha: fire ? _pulseAnim.value : 0.25),
          ),
          boxShadow: fire
              ? [
                  BoxShadow(
                    color: AppColors.waitOrange
                        .withValues(alpha: _pulseAnim.value * 0.50),
                    blurRadius: 10,
                    spreadRadius: 1,
                  ),
                ]
              : [],
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 22,
              height: 22,
              decoration: BoxDecoration(shape: BoxShape.circle, color: col),
              alignment: Alignment.center,
              child: Text(
                widget.label,
                style: TextStyle(
                  color: Colors.white,
                  fontSize: widget.label == 'ND' ? 7.5 : 9,
                  fontWeight: FontWeight.w900,
                  letterSpacing: -0.3,
                ),
              ),
            ),
            const SizedBox(width: 5),
            Text(
              '${mins}m',
              style: TextStyle(
                color: col,
                fontSize: 12,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
