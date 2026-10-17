import 'dart:async';
import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';

/// The severe, whole-screen-blocking failure moments this app hits (see
/// `base_service.dart`'s `parseApiResponse`) — distinct from the small inline
/// `AppErrorState` used for ordinary per-section load failures.
enum AppErrorKind { connectionLost, recordNotFound, serverError, generic }

AppErrorKind detectAppErrorKind(String message) {
  final m = message.toLowerCase();
  if (m.contains('socketexception') ||
      m.contains('timeoutexception') ||
      m.contains('failed host lookup') ||
      m.contains('connection refused') ||
      m.contains('network is unreachable') ||
      m.contains('no internet')) {
    return AppErrorKind.connectionLost;
  }
  if (m.contains('record not found')) return AppErrorKind.recordNotFound;
  if (m.contains('server error')) return AppErrorKind.serverError;
  return AppErrorKind.generic;
}

/// Full-screen takeover for a severe, whole-screen load failure. Reserved for
/// moments like app launch / dashboard failing to load or a record opened by
/// ID being gone; ordinary in-page failures keep the small `AppErrorState`.
///
/// Design: calm and clinical rather than loud — a soft tinted ground, one
/// vector-drawn animated illustration per failure kind (no image assets, so
/// it stays crisp and is themed by the kind's accent), plain-language copy,
/// concrete "try this" steps, a retry button that shows progress, and
/// collapsed technical details (with copy) for support. Respects the OS
/// "reduce motion" setting.
///
/// Drop-in: `AppFullScreenError(message: e, onRetry: _load)`. [onRetry] may be
/// async — the button shows a spinner until it completes.
class AppFullScreenError extends StatefulWidget {
  final String message;
  final FutureOr<void> Function() onRetry;
  final AppErrorKind? kind;

  /// Small pill under the description — e.g. "Last searched: #MRD-90421".
  final String? infoPillText;
  final IconData? infoPillIcon;

  /// Secondary action below the primary button, e.g. "Return to Home".
  final String? secondaryLabel;
  final IconData? secondaryIcon;
  final VoidCallback? onSecondary;

  const AppFullScreenError({
    super.key,
    required this.message,
    required this.onRetry,
    this.kind,
    this.infoPillText,
    this.infoPillIcon,
    this.secondaryLabel,
    this.secondaryIcon,
    this.onSecondary,
  });

  @override
  State<AppFullScreenError> createState() => _AppFullScreenErrorState();
}

class _AppFullScreenErrorState extends State<AppFullScreenError> with TickerProviderStateMixin {
  late final AnimationController _ambient;
  late final AnimationController _entrance;
  bool _retrying = false;
  bool _showDetails = false;
  bool _copied = false;
  bool _reduceMotion = false;

  @override
  void initState() {
    super.initState();
    _ambient = AnimationController(vsync: this, duration: const Duration(seconds: 5));
    _entrance = AnimationController(vsync: this, duration: const Duration(milliseconds: 1100));
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final reduce = MediaQuery.maybeOf(context)?.disableAnimations ?? false;
    if (reduce == _reduceMotion && (_entrance.isAnimating || _entrance.isCompleted)) return;
    _reduceMotion = reduce;
    if (reduce) {
      _ambient.stop();
      _entrance.value = 1;
    } else {
      if (!_ambient.isAnimating) _ambient.repeat();
      if (!_entrance.isCompleted && !_entrance.isAnimating) _entrance.forward();
    }
  }

  @override
  void dispose() {
    _ambient.dispose();
    _entrance.dispose();
    super.dispose();
  }

  Future<void> _retry() async {
    if (_retrying) return;
    setState(() => _retrying = true);
    try {
      await widget.onRetry();
    } finally {
      // Keep the spinner up long enough to read as feedback even when the
      // retry callback returns instantly.
      await Future<void>.delayed(const Duration(milliseconds: 600));
      if (mounted) setState(() => _retrying = false);
    }
  }

  Future<void> _copyDetails(AppErrorKindConfig c) async {
    await Clipboard.setData(ClipboardData(text: '${c.chipText}\n${widget.message}'));
    if (!mounted) return;
    setState(() => _copied = true);
    await Future<void>.delayed(const Duration(milliseconds: 1600));
    if (mounted) setState(() => _copied = false);
  }

  /// Fade + rise for one staggered piece of the sheet, over [begin]..[end] of
  /// the entrance timeline.
  Widget _stagger(Widget child, double begin, double end) {
    final curve = CurvedAnimation(parent: _entrance, curve: Interval(begin, end, curve: Curves.easeOutCubic));
    return AnimatedBuilder(
      animation: curve,
      builder: (context, child) => Opacity(
        opacity: curve.value.clamp(0.0, 1.0),
        child: Transform.translate(offset: Offset(0, (1 - curve.value) * 18), child: child),
      ),
      child: child,
    );
  }

  @override
  Widget build(BuildContext context) {
    final kind = widget.kind ?? detectAppErrorKind(widget.message);
    final c = appErrorKindConfig(kind, widget.message);
    final stageScale = CurvedAnimation(parent: _entrance, curve: const Interval(0, 0.55, curve: Curves.easeOutBack));

    return DecoratedBox(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          stops: const [0, 0.6],
          colors: [Color.alphaBlend(c.accent.withValues(alpha: 0.08), Colors.white), Colors.white],
        ),
      ),
      child: LayoutBuilder(
        builder: (context, box) => SingleChildScrollView(
          child: ConstrainedBox(
            constraints: BoxConstraints(minHeight: box.maxHeight),
            child: Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 420),
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(24, 16, 24, 28),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      AnimatedBuilder(
                        animation: Listenable.merge([_ambient, stageScale]),
                        builder: (context, _) => Transform.scale(
                          scale: stageScale.value.clamp(0.0, 1.2),
                          child: SizedBox(
                            width: 280,
                            height: 220,
                            child: CustomPaint(
                              painter: _StagePainter(kind: kind, accent: c.accent, t: _reduceMotion ? 0.25 : _ambient.value),
                            ),
                          ),
                        ),
                      ),
                      _stagger(_StatusChip(text: c.chipText, color: c.accent), 0.25, 0.6),
                      const SizedBox(height: 14),
                      _stagger(
                        Text(
                          c.title,
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w800, color: AppColors.darkNavy, height: 1.2, letterSpacing: -0.3),
                        ),
                        0.35,
                        0.7,
                      ),
                      const SizedBox(height: 10),
                      _stagger(
                        Text(
                          c.description,
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 14, color: AppColors.textSecondary, height: 1.55),
                        ),
                        0.45,
                        0.8,
                      ),
                      if (c.tips.isNotEmpty) ...[
                        const SizedBox(height: 18),
                        _stagger(_TipsCard(tips: c.tips, accent: c.accent), 0.55, 0.9),
                      ],
                      if (widget.infoPillText != null) ...[
                        const SizedBox(height: 12),
                        _stagger(_InfoPill(icon: widget.infoPillIcon ?? Icons.info_outline_rounded, text: widget.infoPillText!), 0.55, 0.9),
                      ],
                      const SizedBox(height: 22),
                      _stagger(
                        Column(
                          children: [
                            _PrimaryButton(label: c.primaryLabel, icon: c.primaryIcon, loading: _retrying, onTap: _retry),
                            if (widget.secondaryLabel != null) ...[
                              const SizedBox(height: 10),
                              _SecondaryButton(
                                label: widget.secondaryLabel!,
                                icon: widget.secondaryIcon ?? Icons.home_outlined,
                                onTap: widget.onSecondary,
                              ),
                            ],
                          ],
                        ),
                        0.65,
                        1.0,
                      ),
                      const SizedBox(height: 6),
                      _stagger(_detailsSection(c), 0.75, 1.0),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _detailsSection(AppErrorKindConfig c) {
    return Column(
      children: [
        TextButton.icon(
          onPressed: () => setState(() => _showDetails = !_showDetails),
          style: TextButton.styleFrom(foregroundColor: AppColors.textSecondary, visualDensity: VisualDensity.compact),
          icon: AnimatedRotation(
            turns: _showDetails ? 0.5 : 0,
            duration: const Duration(milliseconds: 200),
            child: const Icon(Icons.expand_more_rounded, size: 18),
          ),
          label: Text(_showDetails ? 'Hide technical details' : 'Technical details', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
        ),
        AnimatedSize(
          duration: const Duration(milliseconds: 220),
          curve: Curves.easeOutCubic,
          alignment: Alignment.topCenter,
          child: _showDetails
              ? Container(
                  width: double.infinity,
                  margin: const EdgeInsets.only(top: 4),
                  padding: const EdgeInsets.fromLTRB(14, 10, 6, 10),
                  decoration: BoxDecoration(color: AppColors.surfaceFill, borderRadius: BorderRadius.circular(AppRadius.md)),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: SelectableText(
                          widget.message,
                          style: const TextStyle(fontSize: 11.5, height: 1.45, color: AppColors.textSecondary, fontFamily: 'monospace'),
                        ),
                      ),
                      IconButton(
                        onPressed: () => _copyDetails(c),
                        tooltip: 'Copy details',
                        visualDensity: VisualDensity.compact,
                        icon: Icon(_copied ? Icons.check_rounded : Icons.copy_rounded, size: 16, color: _copied ? AppColors.teal : AppColors.textSecondary),
                      ),
                    ],
                  ),
                )
              : const SizedBox(width: double.infinity),
        ),
      ],
    );
  }

  // (kind copy lives in appErrorKindConfig below)
}

/// Copy, accent and icon for each failure kind — shared by the full-screen
/// takeover and the compact inline [AppErrorState].
AppErrorKindConfig appErrorKindConfig(AppErrorKind k, String message) {
    switch (k) {
      case AppErrorKind.connectionLost:
        return AppErrorKindConfig(
          accent: AppColors.orange,
          chipText: 'No connection',
          icon: Icons.wifi_off_rounded,
          title: "You're offline",
          description: "We can't reach the hospital network right now. Nothing you've saved has been lost.",
          tips: const ['Check Wi-Fi or mobile data is on', 'Move closer to the router, or switch networks'],
          primaryIcon: Icons.refresh_rounded,
          primaryLabel: 'Try Again',
        );
      case AppErrorKind.recordNotFound:
        return AppErrorKindConfig(
          accent: AppColors.purple,
          chipText: 'Not found',
          icon: Icons.search_off_rounded,
          title: "We couldn't find that record",
          description: 'It may have been moved or deleted, or it belongs to a different hospital.',
          tips: const ['Refresh the list and open it again', 'Check you are signed in to the right hospital'],
          primaryIcon: Icons.refresh_rounded,
          primaryLabel: 'Retry',
        );
      case AppErrorKind.serverError:
        return AppErrorKindConfig(
          accent: AppColors.red,
          chipText: 'Server problem',
          icon: Icons.cloud_off_rounded,
          title: 'Something broke on our side',
          description: 'The server hit a problem. Your data is safe — this is not caused by anything you did.',
          tips: const ['Wait a few seconds, then try again', 'If it keeps happening, share the technical details with support'],
          primaryIcon: Icons.refresh_rounded,
          primaryLabel: 'Try Again',
        );
      case AppErrorKind.generic:
        return AppErrorKindConfig(
          accent: AppColors.blueLight,
          chipText: 'Unable to load',
          icon: Icons.error_outline_rounded,
          title: "That didn't load",
          description: message,
          tips: const [],
          primaryIcon: Icons.refresh_rounded,
          primaryLabel: 'Try Again',
        );
    }
}

class AppErrorKindConfig {
  final Color accent;
  final String chipText;
  final String title;
  final String description;
  final List<String> tips;
  final IconData primaryIcon;
  final String primaryLabel;
  final IconData icon;
  const AppErrorKindConfig({
    required this.accent,
    required this.chipText,
    required this.title,
    required this.description,
    required this.tips,
    required this.primaryIcon,
    required this.primaryLabel,
    required this.icon,
  });
}

// ── Illustration ─────────────────────────────────────────────────────────

/// One painter for all four kinds: soft halo + pulsing rings behind a white
/// "device card" that carries a kind-specific glyph. [t] is the looping
/// 0..1 ambient clock.
class _StagePainter extends CustomPainter {
  final AppErrorKind kind;
  final Color accent;
  final double t;
  const _StagePainter({required this.kind, required this.accent, required this.t});

  static const _tau = math.pi * 2;

  @override
  void paint(Canvas canvas, Size size) {
    final c = Offset(size.width / 2, size.height / 2);

    // Halo.
    canvas.drawCircle(
      c,
      104,
      Paint()..shader = RadialGradient(colors: [accent.withValues(alpha: 0.20), accent.withValues(alpha: 0)]).createShader(Rect.fromCircle(center: c, radius: 104)),
    );

    // Expanding rings, three staggered phases.
    for (var i = 0; i < 3; i++) {
      final p = (t * 1.0 + i / 3) % 1.0;
      canvas.drawCircle(
        c,
        58 + p * 52,
        Paint()
          ..style = PaintingStyle.stroke
          ..strokeWidth = 1.6
          ..color = accent.withValues(alpha: (1 - p) * 0.26),
      );
    }

    // Floating sparks.
    const sparks = [(-0.78, -0.55, 4.0, 0.0), (0.82, -0.35, 3.0, 0.3), (-0.7, 0.55, 3.0, 0.6), (0.72, 0.62, 4.5, 0.85)];
    for (final s in sparks) {
      final bob = math.sin((t + s.$4) * _tau) * 5;
      final a = 0.3 + 0.3 * (0.5 + 0.5 * math.sin((t + s.$4) * _tau));
      canvas.drawCircle(Offset(c.dx + s.$1 * 118, c.dy + s.$2 * 90 + bob), s.$3, Paint()..color = accent.withValues(alpha: a));
    }

    // Card (bobs gently).
    final bob = math.sin(t * _tau) * 4;
    canvas.save();
    canvas.translate(c.dx, c.dy + bob);

    final card = RRect.fromRectAndRadius(Rect.fromCenter(center: Offset.zero, width: 112, height: 112), const Radius.circular(30));
    canvas.drawRRect(
      card.shift(const Offset(0, 14)),
      Paint()
        ..color = accent.withValues(alpha: 0.28)
        ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 16),
    );
    canvas.drawRRect(card, Paint()..color = Colors.white);
    canvas.drawRRect(
      card,
      Paint()
        ..style = PaintingStyle.stroke
        ..strokeWidth = 1.2
        ..color = accent.withValues(alpha: 0.18),
    );

    switch (kind) {
      case AppErrorKind.connectionLost:
        _wifi(canvas);
      case AppErrorKind.serverError:
        _server(canvas);
      case AppErrorKind.recordNotFound:
        _search(canvas);
      case AppErrorKind.generic:
        _warning(canvas);
    }
    canvas.restore();
  }

  Paint _stroke(double w, [double alpha = 1]) => Paint()
    ..style = PaintingStyle.stroke
    ..strokeWidth = w
    ..strokeCap = StrokeCap.round
    ..strokeJoin = StrokeJoin.round
    ..color = accent.withValues(alpha: alpha);

  void _badge(Canvas canvas, Offset at, void Function(Canvas) glyph) {
    canvas.save();
    canvas.translate(at.dx, at.dy);
    canvas.drawCircle(Offset.zero, 15, Paint()..color = Colors.white);
    canvas.drawCircle(Offset.zero, 12, Paint()..color = accent);
    glyph(canvas);
    canvas.restore();
  }

  void _wifi(Canvas canvas) {
    const origin = Offset(0, 16);
    for (var i = 0; i < 3; i++) {
      final r = 16.0 + i * 13;
      // Arcs "search": brightness travels outward and repeats.
      final a = 0.28 + 0.72 * (0.5 + 0.5 * math.sin(t * _tau * 2 - i * 1.1));
      canvas.drawArc(Rect.fromCircle(center: origin, radius: r), -math.pi * 3 / 4, math.pi / 2, false, _stroke(5.5, a));
    }
    canvas.drawCircle(origin, 4.2, Paint()..color = accent);
    _badge(canvas, const Offset(40, 40), (c) {
      final p = Paint()
        ..color = Colors.white
        ..strokeWidth = 2.6
        ..strokeCap = StrokeCap.round;
      c.drawLine(const Offset(-4.5, -4.5), const Offset(4.5, 4.5), p);
      c.drawLine(const Offset(-4.5, 4.5), const Offset(4.5, -4.5), p);
    });
  }

  void _server(Canvas canvas) {
    for (var i = 0; i < 3; i++) {
      final y = -26.0 + i * 24;
      final r = RRect.fromRectAndRadius(Rect.fromCenter(center: Offset(0, y), width: 66, height: 18), const Radius.circular(6));
      canvas.drawRRect(r, Paint()..color = accent.withValues(alpha: 0.09));
      canvas.drawRRect(r, _stroke(1.4, 0.4));
      // LEDs: two healthy, the bottom one blinks in the accent colour.
      final blink = i == 2 ? (math.sin(t * _tau * 3) > 0 ? 1.0 : 0.25) : 1.0;
      canvas.drawCircle(Offset(-23, y), 3, Paint()..color = (i == 2 ? accent : AppColors.teal).withValues(alpha: blink));
      canvas.drawLine(Offset(-10, y), Offset(i == 1 ? 18 : 24, y), _stroke(2.2, 0.35));
    }
    _badge(canvas, const Offset(40, 40), (c) {
      final p = Paint()
        ..color = Colors.white
        ..strokeWidth = 2.6
        ..strokeCap = StrokeCap.round;
      c.drawLine(const Offset(0, -5.5), const Offset(0, 1), p);
      c.drawCircle(const Offset(0, 5.2), 1.6, Paint()..color = Colors.white);
    });
  }

  void _search(Canvas canvas) {
    // Document.
    final doc = RRect.fromRectAndRadius(Rect.fromCenter(center: const Offset(-8, -2), width: 50, height: 64), const Radius.circular(9));
    canvas.drawRRect(doc, Paint()..color = accent.withValues(alpha: 0.08));
    canvas.drawRRect(doc, _stroke(1.6, 0.4));
    for (var i = 0; i < 4; i++) {
      canvas.drawLine(Offset(-26, -20 + i * 11.0), Offset(i == 3 ? -12 : 8, -20 + i * 11.0), _stroke(2.4, 0.3));
    }
    // Magnifier sweeps left-right over it.
    final dx = math.sin(t * _tau) * 6;
    final lens = Offset(14 + dx, 10);
    canvas.drawCircle(lens, 17, Paint()..color = Colors.white.withValues(alpha: 0.85));
    canvas.drawCircle(lens, 17, _stroke(5));
    canvas.drawLine(lens + const Offset(12, 12), lens + const Offset(25, 25), _stroke(6));
    final tp = TextPainter(
      text: TextSpan(text: '?', style: TextStyle(color: accent, fontSize: 21, fontWeight: FontWeight.w900)),
      textDirection: TextDirection.ltr,
    )..layout();
    tp.paint(canvas, lens - Offset(tp.width / 2, tp.height / 2));
  }

  void _warning(Canvas canvas) {
    final wobble = math.sin(t * _tau * 2) * 0.05;
    canvas.save();
    canvas.rotate(wobble);
    final path = Path()
      ..moveTo(0, -30)
      ..quadraticBezierTo(4, -30, 7, -24)
      ..lineTo(30, 18)
      ..quadraticBezierTo(34, 26, 26, 26)
      ..lineTo(-26, 26)
      ..quadraticBezierTo(-34, 26, -30, 18)
      ..lineTo(-7, -24)
      ..quadraticBezierTo(-4, -30, 0, -30)
      ..close();
    canvas.drawPath(path, Paint()..color = accent.withValues(alpha: 0.12));
    canvas.drawPath(path, _stroke(4.5));
    canvas.drawLine(const Offset(0, -9), const Offset(0, 6), _stroke(5));
    canvas.drawCircle(const Offset(0, 16), 3, Paint()..color = accent);
    canvas.restore();
  }

  @override
  bool shouldRepaint(_StagePainter old) => old.t != t || old.kind != kind || old.accent != accent;
}

// ── Sheet chrome ─────────────────────────────────────────────────────────

class _StatusChip extends StatelessWidget {
  final String text;
  final Color color;
  const _StatusChip({required this.text, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(AppRadius.full),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(width: 6, height: 6, decoration: BoxDecoration(color: color, shape: BoxShape.circle)),
          const SizedBox(width: 7),
          Text(text.toUpperCase(), style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: color, letterSpacing: 0.8)),
        ],
      ),
    );
  }
}

class _TipsCard extends StatelessWidget {
  final List<String> tips;
  final Color accent;
  const _TipsCard({required this.tips, required this.accent});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.08)),
        boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.05), blurRadius: 14, offset: const Offset(0, 4))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('TRY THIS', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w800, letterSpacing: 1, color: AppColors.textDisabled)),
          const SizedBox(height: 10),
          for (var i = 0; i < tips.length; i++) ...[
            if (i > 0) const SizedBox(height: 9),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  margin: const EdgeInsets.only(top: 1),
                  width: 18,
                  height: 18,
                  decoration: BoxDecoration(color: accent.withValues(alpha: 0.12), shape: BoxShape.circle),
                  child: Icon(Icons.check_rounded, size: 12, color: accent),
                ),
                const SizedBox(width: 10),
                Expanded(child: Text(tips[i], style: const TextStyle(fontSize: 13, height: 1.4, color: AppColors.textPrimary))),
              ],
            ),
          ],
        ],
      ),
    );
  }
}

class _InfoPill extends StatelessWidget {
  final IconData icon;
  final String text;
  const _InfoPill({required this.icon, required this.text});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
      decoration: BoxDecoration(color: AppColors.surfaceFill, borderRadius: BorderRadius.circular(AppRadius.md)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 15, color: AppColors.textSecondary),
          const SizedBox(width: 8),
          Flexible(
            child: Text(
              text,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.textSecondary),
            ),
          ),
        ],
      ),
    );
  }
}

class _PrimaryButton extends StatelessWidget {
  final String label;
  final IconData icon;
  final bool loading;
  final VoidCallback onTap;
  const _PrimaryButton({required this.label, required this.icon, required this.loading, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return _PressScale(
      onTap: loading ? null : onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        width: double.infinity,
        height: 52,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: loading ? AppColors.primary.withValues(alpha: 0.8) : AppColors.primary,
          borderRadius: BorderRadius.circular(AppRadius.lg),
          boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: loading ? 0.12 : 0.30), blurRadius: 16, offset: const Offset(0, 6))],
        ),
        child: AnimatedSwitcher(
          duration: const Duration(milliseconds: 180),
          child: loading
              ? const SizedBox(key: ValueKey('spin'), width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.6, color: Colors.white))
              : Row(
                  key: const ValueKey('label'),
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(icon, size: 19, color: Colors.white),
                    const SizedBox(width: 8),
                    Text(label, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: Colors.white)),
                  ],
                ),
        ),
      ),
    );
  }
}

class _SecondaryButton extends StatelessWidget {
  final String label;
  final IconData icon;
  final VoidCallback? onTap;
  const _SecondaryButton({required this.label, required this.icon, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return _PressScale(
      onTap: onTap,
      child: Container(
        width: double.infinity,
        height: 50,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: Colors.white,
          border: Border.all(color: AppColors.textDisabled.withValues(alpha: 0.45)),
          borderRadius: BorderRadius.circular(AppRadius.lg),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 18, color: AppColors.darkNavy),
            const SizedBox(width: 8),
            Text(label, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.darkNavy)),
          ],
        ),
      ),
    );
  }
}

/// Tiny tactile press-scale wrapper, kept local so this widget file stays
/// standalone.
class _PressScale extends StatefulWidget {
  final Widget child;
  final VoidCallback? onTap;
  const _PressScale({required this.child, required this.onTap});

  @override
  State<_PressScale> createState() => _PressScaleState();
}

class _PressScaleState extends State<_PressScale> {
  bool _down = false;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTapDown: widget.onTap == null ? null : (_) => setState(() => _down = true),
      onTapUp: widget.onTap == null ? null : (_) => setState(() => _down = false),
      onTapCancel: widget.onTap == null ? null : () => setState(() => _down = false),
      onTap: widget.onTap,
      child: AnimatedScale(scale: _down ? 0.97 : 1.0, duration: const Duration(milliseconds: 100), child: widget.child),
    );
  }
}
