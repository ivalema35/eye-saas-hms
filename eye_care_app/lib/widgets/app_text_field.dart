import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';

/// Standard form field for the whole app — a label above a white, rounded,
/// softly-shadowed field (same "card" visual language as [AppRecordCard] and
/// the dashboard's stat tiles, so forms feel like part of the same app
/// instead of a bare, flat default `TextFormField`). Mobile form-field
/// guidelines (Material/iOS touch-target minimums, ~48dp field height, 15sp+
/// input text) land in the sizing/padding below — see AppTheme.light's
/// matching InputDecorationTheme for screens that use the default theme
/// styling directly instead of this widget.
///
/// Every call site used to hand-roll its own `InputDecoration` (each screen
/// had its own near-duplicate `_inputDeco`/`_textField` helper with slightly
/// different radius/padding/border-opacity), which is why forms looked
/// inconsistent from one screen to the next. New forms should use this
/// instead of writing another one-off decoration.
class AppTextField extends StatelessWidget {
  final String label;
  final TextEditingController? controller;
  final String? hint;
  final IconData? icon;
  final Color? iconColor;
  final bool required;
  final bool readOnly;
  final bool enabled;
  final bool obscureText;
  final int maxLines;
  final int? maxLength;
  final String? suffixText;
  final Widget? suffixIcon;
  final TextInputType? keyboardType;
  final List<TextInputFormatter>? inputFormatters;
  final String? Function(String?)? validator;
  final ValueChanged<String>? onChanged;
  final VoidCallback? onTap;
  final bool tapOnly;

  const AppTextField({
    super.key,
    required this.label,
    this.controller,
    this.hint,
    this.icon,
    this.iconColor,
    this.required = false,
    this.readOnly = false,
    this.enabled = true,
    this.obscureText = false,
    this.maxLines = 1,
    this.maxLength,
    this.suffixText,
    this.suffixIcon,
    this.keyboardType,
    this.inputFormatters,
    this.validator,
    this.onChanged,
    this.onTap,
    this.tapOnly = false,
  });

  @override
  Widget build(BuildContext context) {
    final color = iconColor ?? AppColors.primary;
    final field = TextFormField(
      controller: controller,
      readOnly: readOnly || tapOnly,
      enabled: enabled,
      obscureText: obscureText,
      maxLines: obscureText ? 1 : maxLines,
      maxLength: maxLength,
      keyboardType: keyboardType,
      inputFormatters: inputFormatters,
      onChanged: onChanged,
      onTap: onTap,
      validator:
          validator ??
          (required
              ? (v) => (v == null || v.trim().isEmpty)
                    ? '$label is required'
                    : null
              : null),
      style: const TextStyle(
        fontSize: 15,
        fontWeight: FontWeight.w600,
        color: AppColors.textPrimary,
      ),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: TextStyle(
          fontSize: 14,
          fontWeight: FontWeight.w500,
          color: AppColors.textDisabled,
        ),
        counterText: '',
        isDense: false,
        prefixIcon: icon == null
            ? null
            : Padding(
                padding: const EdgeInsets.all(12),
                child: Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.10),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(icon, size: 16, color: color),
                ),
              ),
        prefixIconConstraints: const BoxConstraints(minWidth: 0, minHeight: 0),
        suffixIcon: suffixIcon,
        suffixText: suffixText,
        suffixStyle: const TextStyle(
          fontSize: 13,
          fontWeight: FontWeight.w700,
          color: AppColors.textSecondary,
        ),
        filled: true,
        fillColor: !enabled || readOnly ? AppColors.surfaceFill : Colors.white,
        contentPadding: EdgeInsets.symmetric(
          horizontal: icon == null ? 16 : 4,
          vertical: maxLines > 1 ? 14 : 15,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: BorderSide(color: AppColors.primaryA18),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: BorderSide(color: AppColors.primaryA18),
        ),
        disabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: BorderSide(color: AppColors.primaryA12),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: BorderSide(color: AppColors.primary, width: 1.6),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: const BorderSide(color: AppColors.red),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: const BorderSide(color: AppColors.red, width: 1.6),
        ),
        errorStyle: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: AppColors.red,
        ),
      ),
    );

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(bottom: 6, left: 2),
          child: RichText(
            text: TextSpan(
              style: const TextStyle(
                fontSize: 12.5,
                fontWeight: FontWeight.w700,
                color: AppColors.textPrimary,
              ),
              children: [
                TextSpan(text: label),
                if (required)
                  const TextSpan(
                    text: '  *',
                    style: TextStyle(
                      color: AppColors.red,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
              ],
            ),
          ),
        ),
        Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(AppRadius.md),
            boxShadow: [
              BoxShadow(
                color: AppColors.primaryA05,
                blurRadius: 6,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: tapOnly ? AbsorbPointer(child: field) : field,
        ),
      ],
    );
  }
}

/// A piece of reference data shown inside a form that the user cannot edit
/// here — an auto-generated ID, a value carried over from an earlier step
/// (e.g. the eye locked in at counselling), a derived field (district/state
/// filled in from a picked city). Several screens used to show these as a
/// disabled `TextFormField`/`InputDecorator` — same bordered box + floating
/// label as a real input, just greyed — which reads as "a field I can't use
/// right now" rather than "this isn't a field at all," and in at least one
/// case (`patient_form_screen.dart`'s MRD No.) rendered real, meaningful data
/// in a muted tone that looked exactly like empty placeholder/hint text.
/// This widget is deliberately NOT a field: no border, no floating label, a
/// flat tinted chip with a static caption above it and a trailing lock glyph
/// — so at a glance it reads as "shown to you," never "type here," while the
/// value itself stays fully legible (never hint-grey).
class AppReadOnlyField extends StatelessWidget {
  final String label;
  final String value;
  final IconData? icon;
  // Some screens already print this field's label themselves (their own
  // `_sectionLabel`-style helper, shared with every other field on the
  // page) — set false there so this widget contributes only the value chip,
  // not a second, differently-styled label on top of theirs.
  final bool showLabel;
  // Auto-generated IDs are often needed elsewhere (SMS to a patient, a
  // printed slip) — a one-tap copy affordance beats re-typing a value the
  // user is explicitly not allowed to edit here.
  final VoidCallback? onCopy;

  const AppReadOnlyField({
    super.key,
    required this.label,
    required this.value,
    this.icon,
    this.showLabel = true,
    this.onCopy,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (showLabel)
          Padding(
            padding: const EdgeInsets.only(bottom: 6, left: 2),
            child: Text(
              label,
              style: const TextStyle(
                fontSize: 12.5,
                fontWeight: FontWeight.w700,
                color: AppColors.textSecondary,
              ),
            ),
          ),
        Container(
          width: double.infinity,
          padding: EdgeInsets.symmetric(
            horizontal: icon == null ? 14 : 12,
            vertical: 13,
          ),
          decoration: BoxDecoration(
            color: AppColors.primaryA04,
            borderRadius: BorderRadius.circular(AppRadius.md),
          ),
          child: Row(
            children: [
              if (icon != null) ...[
                Icon(icon, size: 16, color: AppColors.primaryA50),
                const SizedBox(width: 8),
              ],
              Expanded(
                child: Text(
                  value.isEmpty ? '—' : value,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                    color: AppColors.darkNavy,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              if (onCopy != null)
                InkWell(
                  onTap: onCopy,
                  borderRadius: BorderRadius.circular(AppRadius.sm),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 4,
                      vertical: 2,
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          Icons.copy_rounded,
                          size: 13,
                          color: AppColors.primary,
                        ),
                        const SizedBox(width: 3),
                        Text(
                          'Copy',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                            color: AppColors.primary,
                          ),
                        ),
                      ],
                    ),
                  ),
                )
              else
                Icon(
                  Icons.lock_outline_rounded,
                  size: 14,
                  color: AppColors.textDisabled,
                ),
            ],
          ),
        ),
      ],
    );
  }
}

/// A full form section as one bordered, shadowed card — icon badge + bold
/// title (+ an optional trailing status pill) in its own header strip over a
/// divider, then the section's fields below with consistent spacing between
/// them. Promoted out of the one-off card headers several form screens used
/// to hand-roll (plain `AppSectionHeader` dividers floating in one
/// continuous `ListView`, no card chrome at all) so a long form reads as a
/// sequence of distinct, scannable groups instead of one undifferentiated
/// run of fields — same idea as [AppDetailSectionCard] for "view" surfaces,
/// but with a bigger title and an icon *badge* (tinted box, not just a bare
/// icon) to match this app's form-reference visual language.
class AppFormCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final Widget? trailing;
  final List<Widget> children;

  const AppFormCard({
    super.key,
    required this.icon,
    required this.title,
    this.trailing,
    required this.children,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: AppColors.primaryA08),
        boxShadow: [
          BoxShadow(
            color: AppColors.primaryA05,
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
            child: Row(
              children: [
                Container(
                  width: 34,
                  height: 34,
                  decoration: BoxDecoration(
                    color: AppColors.primaryA10,
                    borderRadius: BorderRadius.circular(9),
                  ),
                  alignment: Alignment.center,
                  child: Icon(icon, size: 18, color: AppColors.primary),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    title,
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w800,
                      color: AppColors.darkNavy,
                    ),
                  ),
                ),
                ?trailing,
              ],
            ),
          ),
          Divider(height: 1, color: AppColors.primaryA08),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                for (var i = 0; i < children.length; i++) ...[
                  if (i > 0) const SizedBox(height: 14),
                  children[i],
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Equal-width segmented picker for a small fixed set of options (appointment
/// type, gender, eye, yes/no...) — a selected-dot + highlighted border
/// instead of a `ChoiceChip`'s left-aligned, variable-width pill, so options
/// read as one deliberate control instead of a loose row of buttons that
/// happen to sit next to each other.
class AppSegmentedChips extends StatelessWidget {
  final List<String> values;
  final Map<String, String>? labels;
  final String? selected;
  final ValueChanged<String> onChanged;

  const AppSegmentedChips({
    super.key,
    required this.values,
    this.labels,
    required this.selected,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        for (var i = 0; i < values.length; i++) ...[
          if (i > 0) const SizedBox(width: 8),
          Expanded(child: _segment(values[i])),
        ],
      ],
    );
  }

  Widget _segment(String value) {
    final isSelected = selected == value;
    return InkWell(
      onTap: () => onChanged(value),
      borderRadius: BorderRadius.circular(AppRadius.md),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 11),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.primaryA08 : Colors.white,
          borderRadius: BorderRadius.circular(AppRadius.md),
          border: Border.all(
            color: isSelected ? AppColors.primary : AppColors.primaryA18,
            width: isSelected ? 1.4 : 1,
          ),
        ),
        alignment: Alignment.center,
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (isSelected) ...[
              Container(
                width: 6,
                height: 6,
                decoration: BoxDecoration(
                  color: AppColors.primary,
                  shape: BoxShape.circle,
                ),
              ),
              const SizedBox(width: 6),
            ],
            Flexible(
              child: Text(
                labels?[value] ?? value,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: isSelected
                      ? AppColors.primary
                      : AppColors.textSecondary,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
