import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_full_screen_error.dart';

/// Debug-only — lets a developer see all 4 `AppFullScreenError` kinds on
/// demand instead of having to actually go offline / find a deleted
/// record / break the server. Never reachable outside `kDebugMode` (see the
/// `_buildDebugSection()` guard in profile_screen.dart, the only place this
/// is linked from).
class DebugErrorPreviewScreen extends StatelessWidget {
  const DebugErrorPreviewScreen({super.key});

  static const _entries = [
    (
      kind: AppErrorKind.connectionLost,
      label: 'Connection Lost',
      sub: 'No internet / request timed out',
      message: 'SocketException: Failed host lookup (preview)',
    ),
    (
      kind: AppErrorKind.recordNotFound,
      label: 'Record Not Found',
      sub: 'A fetched-by-id record is gone (404)',
      message: 'Record not found. Please refresh the list.',
    ),
    (
      kind: AppErrorKind.serverError,
      label: 'Server Error',
      sub: 'Backend returned a 5xx',
      message: 'Server error. Please try again later.',
    ),
    (
      kind: AppErrorKind.generic,
      label: 'Generic Fallback',
      sub: 'Anything that doesn\'t match the above',
      message: 'Something unexpected happened. Please try again.',
    ),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Error Screen Previews'),
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text(
            'Debug only — tap any row to see that AppFullScreenError kind full-screen.',
            style: TextStyle(fontSize: 12.5, color: AppColors.textSecondary),
          ),
          const SizedBox(height: 16),
          for (final e in _entries) ...[
            _row(context, e.kind, e.label, e.sub, e.message),
            const SizedBox(height: 10),
          ],
        ],
      ),
    );
  }

  Widget _row(BuildContext context, AppErrorKind kind, String label, String sub, String message) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.primaryA08),
        boxShadow: [BoxShadow(color: AppColors.primaryA05, blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: () => Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => _PreviewHost(kind: kind, label: label, message: message),
          ),
        ),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(color: AppColors.background, borderRadius: BorderRadius.circular(AppRadius.md)),
                alignment: Alignment.center,
                child: Icon(_iconFor(kind), color: AppColors.primary, size: 20),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(label, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.darkNavy)),
                    Text(sub, style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
                  ],
                ),
              ),
              Icon(Icons.chevron_right_rounded, color: AppColors.primaryA50),
            ],
          ),
        ),
      ),
    );
  }

  IconData _iconFor(AppErrorKind kind) => switch (kind) {
    AppErrorKind.connectionLost => Icons.wifi_off_rounded,
    AppErrorKind.recordNotFound => Icons.find_in_page_outlined,
    AppErrorKind.serverError => Icons.cloud_off_rounded,
    AppErrorKind.generic => Icons.error_outline_rounded,
  };
}

class _PreviewHost extends StatelessWidget {
  final AppErrorKind kind;
  final String label;
  final String message;
  const _PreviewHost({required this.kind, required this.label, required this.message});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(label)),
      body: AppFullScreenError(
        kind: kind,
        message: message,
        infoPillText: kind == AppErrorKind.recordNotFound ? 'Last searched: #MRD-90421 · OPD Desk' : null,
        secondaryLabel: 'Return to Home',
        secondaryIcon: Icons.home_outlined,
        onSecondary: () => Navigator.of(context).popUntil((r) => r.isFirst),
        onRetry: () => showAppSnackBar(context, 'Retry tapped (preview only).', isSuccess: true),
      ),
    );
  }
}
