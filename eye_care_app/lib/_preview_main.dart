// Throwaway preview entry point — boots straight into the error-screen
// previews, skipping login, so they can be screenshotted quickly. Not part
// of the real app; delete after use.
import 'package:flutter/material.dart';
import 'screens/debug_error_preview_screen.dart';

void main() {
  runApp(const MaterialApp(home: DebugErrorPreviewScreen(), debugShowCheckedModeBanner: false));
}
