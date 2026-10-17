import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import 'app_full_screen_error.dart';

/// Load-failure state used by every screen. Adapts to the room it is given:
///  - a whole screen body (tall, bounded area) → the full-screen
///    [AppFullScreenError] design;
///  - a section, tab or list inside a page (short or unbounded area) → a
///    compact inline card in the same visual language, so a failing section
///    never blanks the entire screen.
/// Failure kind (offline / not found / server / other) is detected from
/// [message]. Without [onRetry] it always renders the compact form.
class AppErrorState extends StatelessWidget {
  final String message;
  final VoidCallback? onRetry;

  const AppErrorState({super.key, required this.message, this.onRetry});

  /// Minimum height at which the full-screen layout fits without feeling
  /// cramped — below this the compact form is used.
  static const double _fullScreenMinHeight = 540;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, box) {
        final roomy = box.hasBoundedHeight && box.maxHeight >= _fullScreenMinHeight && box.maxWidth >= 300;
        if (roomy && onRetry != null) {
          return AppFullScreenError(message: message, onRetry: onRetry!);
        }
        return _CompactError(message: message, onRetry: onRetry);
      },
    );
  }
}

class _CompactError extends StatefulWidget {
  final String message;
  final VoidCallback? onRetry;
  const _CompactError({required this.message, required this.onRetry});

  @override
  State<_CompactError> createState() => _CompactErrorState();
}

class _CompactErrorState extends State<_CompactError> {
  bool _retrying = false;

  Future<void> _retry() async {
    if (_retrying || widget.onRetry == null) return;
    setState(() => _retrying = true);
    widget.onRetry!();
    await Future<void>.delayed(const Duration(milliseconds: 600));
    if (mounted) setState(() => _retrying = false);
  }

  @override
  Widget build(BuildContext context) {
    final kind = detectAppErrorKind(widget.message);
    final c = appErrorKindConfig(kind, widget.message);
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 360),
          child: Container(
            width: double.infinity,
            padding: const EdgeInsets.fromLTRB(20, 22, 20, 20),
            decoration: BoxDecoration(
              color: Color.alphaBlend(c.accent.withValues(alpha: 0.05), Colors.white),
              borderRadius: BorderRadius.circular(AppRadius.xl),
              border: Border.all(color: c.accent.withValues(alpha: 0.16)),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 56,
                  height: 56,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    shape: BoxShape.circle,
                    border: Border.all(color: c.accent.withValues(alpha: 0.22)),
                    boxShadow: [BoxShadow(color: c.accent.withValues(alpha: 0.22), blurRadius: 14, offset: const Offset(0, 5))],
                  ),
                  child: Icon(c.icon, size: 26, color: c.accent),
                ),
                const SizedBox(height: 14),
                Text(
                  c.title,
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.darkNavy, height: 1.25),
                ),
                const SizedBox(height: 6),
                Text(
                  c.description,
                  textAlign: TextAlign.center,
                  maxLines: 4,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 12.5, color: AppColors.textSecondary, height: 1.5),
                ),
                if (widget.onRetry != null) ...[
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    height: 44,
                    child: FilledButton.icon(
                      onPressed: _retrying ? null : _retry,
                      style: FilledButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
                      ),
                      icon: _retrying
                          ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2.2, color: Colors.white))
                          : const Icon(Icons.refresh_rounded, size: 18),
                      label: Text(_retrying ? 'Retrying…' : c.primaryLabel, style: const TextStyle(fontWeight: FontWeight.w700)),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}
