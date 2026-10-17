import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';

abstract final class AppTheme {
  static ThemeData get light {
    // Poppins was already this app's intended brand font — login,
    // onboarding and the platform-admin screens all set it per-widget via
    // GoogleFonts.poppins(...), but ThemeData never set a default
    // fontFamily. Every other screen (Dashboard, Patients, Exams, OT —
    // i.e. nearly the whole app) fell back to Flutter's bare default
    // Roboto, and since almost none of those screens' hardcoded
    // TextStyle(fontSize: ...) literals set their own fontFamily, they
    // silently inherited that mismatched default instead of Poppins. This
    // one default fixes every one of them at once.
    final base = ThemeData(useMaterial3: true, brightness: Brightness.light);
    final textTheme = GoogleFonts.poppinsTextTheme(base.textTheme);

    return base.copyWith(
      textTheme: textTheme,
      primaryTextTheme: GoogleFonts.poppinsTextTheme(base.primaryTextTheme),
      colorScheme: ColorScheme.fromSeed(
        seedColor: AppColors.primary,
        brightness: Brightness.light,
      ).copyWith(
        primary:   AppColors.primary,
        secondary: AppColors.secondary,
        surface:   AppColors.surface,
        error:     AppColors.red,
      ),
      scaffoldBackgroundColor: AppColors.background,
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        backgroundColor: AppColors.primary,
        contentTextStyle: GoogleFonts.poppins(color: AppColors.textOnPrimary),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.surface,
        // 48dp+ tall fields (Material/iOS minimum comfortable touch target)
        // with 16sp input text — was 12 vertical with no explicit text
        // style, which rendered noticeably smaller/tighter than a normal
        // app's form fields.
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        hintStyle: GoogleFonts.poppins(fontSize: 15, color: AppColors.textDisabled),
        labelStyle: GoogleFonts.poppins(fontSize: 14, color: AppColors.textSecondary),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: BorderSide(color: AppColors.primaryA12),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: BorderSide(color: AppColors.primaryA12),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: BorderSide(color: AppColors.primary, width: 1.5),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: const BorderSide(color: AppColors.red),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: const BorderSide(color: AppColors.red, width: 1.5),
        ),
      ),
    );
  }
}
