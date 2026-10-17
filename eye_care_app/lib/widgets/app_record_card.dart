import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';

/// One field (column heading + value) inside an [AppRecordCard]. Plain
/// `"Label: Value"` text unless [icon] is set, in which case it renders as
/// the icon-badge cell instead (see [AppRecordCard] doc) — [color] is only
/// used when [icon] is set. Prefer [RecordFieldColors] for [color] so the
/// same kind of field (date, phone, doctor...) reads as the same color on
/// every screen instead of each call site picking its own.
class AppRecordField {
  final String label;
  final String value;
  final IconData? icon;
  final Color? color;
  const AppRecordField(this.label, this.value, {this.icon, this.color});
}

/// Canonical icon-badge colors for common field types across every
/// [AppRecordField] call site — pass one of these as `color` so "phone"
/// (say) is always green everywhere, rather than each screen inventing its
/// own mapping. A convention list, not enforced by the type system: a
/// genuinely new field type should add a line here, not a one-off inline
/// color. Do NOT use this for status values (payment status, ot_status,
/// appointment stage, etc.) — those encode what a *value* means and stay on
/// their existing dedicated helpers (`paymentStatusColor`, `otStatusColor`,
/// `StatusBadge`), rendered as a card's trailing pill, not a field-grid
/// cell. This class only encodes what a field *is* (identity), which never
/// changes regardless of the value.
abstract class RecordFieldColors {
  static Color get date => AppColors.blue;
  static Color get age => AppColors.purple;
  static Color get phone => AppColors.green;
  static Color get doctor => AppColors.teal;
  static Color get assistant => AppColors.teal; // same bucket as doctor — both clinical staff
  static Color get caseType => AppColors.orange; // case type / surgery type / diagnosis-ish "category" fields
  static Color get receptionist => AppColors.primary; // non-clinical staff
  static Color get location => AppColors.redDark;
  static Color get package => AppColors.green; // amounts/fees — matches the existing fee-pill green
}

/// Renders one table row as a card: the first column promoted to a bold
/// title, remaining columns as a 2-column grid of fields below (paired into
/// explicit `Row`s rather than a `Wrap`, so it's always 2 columns regardless
/// of screen width — a `Wrap` of fixed-width boxes silently degenerated to 1
/// column on narrower phones, making cards with many fields far taller than
/// necessary). A generic "table row as card" widget for record lists where
/// columns are arbitrary/dynamic (not a fixed typed model) — e.g. OT
/// Reports' 16 report types, each with different headings from the backend
/// — as well as fixed-model record lists (patients, OT bookings,
/// appointments...) that opt into the icon-badge field style. See
/// RECORD_CARD_UNIFICATION_PLAN.md for the full migration plan and rationale
/// (3 design iterations on report_channel_screen.dart's patient card led
/// here: plain rows wasted space and didn't distinguish fields; fully
/// coloring each pill looked like a "rainbow"; landed on color living only
/// on a small icon badge, with the field's label doing the actual
/// identification work).
class AppRecordCard extends StatelessWidget {
  final String title;
  final String? subtitle;
  final List<AppRecordField> fields;
  final Widget? leading;
  final Widget? trailing;
  // Extra content below the field grid and above the actions divider — e.g.
  // a short preview list a card wants to show beneath its own fields
  // (Medicine Groups' "first 3 items"). Not a second field grid: no divider
  // of its own, so callers that want one include it in the widget they pass.
  final Widget? footer;
  final Widget? actions;
  final VoidCallback? onTap;
  final Color? borderColor;
  // When true, renders the same title/subtitle/field-grid layout WITHOUT the
  // bordered/shadowed card box — for embedding this exact look inside
  // another list (e.g. the dashboard's "Today Added Patients" widget) where
  // the separation between records should be a plain divider, not a stack
  // of individual cards. Lets that list reuse the identical field-grid
  // rendering the Patients screen uses instead of a second, divergent copy.
  final bool flat;

  const AppRecordCard({
    super.key,
    required this.title,
    required this.fields,
    this.subtitle,
    this.leading,
    this.trailing,
    this.footer,
    this.actions,
    this.onTap,
    this.borderColor,
    this.flat = false,
  });

  @override
  Widget build(BuildContext context) {
    final card = Container(
      padding: EdgeInsets.all(flat ? 0 : 14),
      decoration: flat
          ? null
          : BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: borderColor ?? AppColors.primaryA10),
              boxShadow: [BoxShadow(color: AppColors.primaryA06, blurRadius: 8, offset: const Offset(0, 2))],
            ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            ?leading,
            if (leading != null) const SizedBox(width: 8),
            // Title + subtitle stacked together, both starting at the same
            // x as the title — previously the subtitle sat in the outer
            // Column below the whole leading+title row, so it started at
            // x=0 under the icon instead of under the name.
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(children: [
                    Expanded(
                      child: Text(title, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Color(0xFF18324A))),
                    ),
                    ?trailing,
                  ]),
                  if (subtitle != null) ...[
                    const SizedBox(height: 3),
                    Text(subtitle!, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w600, color: Color(0xFF6B7D93))),
                  ],
                ],
              ),
            ),
          ]),
          if (fields.isNotEmpty) ...[
            const SizedBox(height: 10),
            Container(height: 1, color: AppColors.primaryA08),
            const SizedBox(height: 10),
            AppFieldGrid(fields: fields),
          ],
          if (footer != null) ...[
            const SizedBox(height: 8),
            footer!,
          ],
          if (actions != null) ...[
            const SizedBox(height: 4),
            Container(height: 1, color: AppColors.primaryA08),
            const SizedBox(height: 10),
            actions!,
          ],
        ],
      ),
    );
    if (onTap == null) return card;
    return Material(
      color: Colors.transparent,
      child: InkWell(onTap: onTap, borderRadius: BorderRadius.circular(14), child: card),
    );
  }
}

/// The 2-column icon-badge field grid [AppRecordCard] renders below its
/// title — pulled out to a public widget so other surfaces that need this
/// exact grid without the rest of the card (a bottom sheet section, say)
/// can reuse it instead of re-implementing their own version of it.
class AppFieldGrid extends StatelessWidget {
  final List<AppRecordField> fields;
  const AppFieldGrid({super.key, required this.fields});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        for (var i = 0; i < fields.length; i += 2)
          Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Expanded(child: _field(fields[i])),
              const SizedBox(width: 14),
              Expanded(child: i + 1 < fields.length ? _field(fields[i + 1]) : const SizedBox.shrink()),
            ]),
          ),
      ],
    );
  }

  Widget _field(AppRecordField f) {
    if (f.icon != null) return _IconBadgeField(field: f);
    return RichText(
      text: TextSpan(children: [
        TextSpan(text: '${f.label}: ', style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: Color(0xFF6B7D93))),
        TextSpan(text: f.value, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: Color(0xFF18324A))),
      ]),
    );
  }
}

/// One grid cell in the icon-badge style: a small colored icon badge + tiny
/// grey label on one line, the value bold underneath. The label means
/// identification never depends on remembering what a color means — the
/// icon badge's tint is a quiet secondary accent layered on top of that.
class _IconBadgeField extends StatelessWidget {
  final AppRecordField field;
  const _IconBadgeField({required this.field});

  @override
  Widget build(BuildContext context) {
    final color = field.color ?? AppColors.primary;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        Row(
          children: [
            Container(
              width: 20,
              height: 20,
              alignment: Alignment.center,
              decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(6)),
              child: Icon(field.icon, size: 12, color: color),
            ),
            const SizedBox(width: 6),
            Expanded(
              child: Text(
                field.label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFF8A97A8), letterSpacing: 0.3),
              ),
            ),
          ],
        ),
        const SizedBox(height: 4),
        Padding(
          padding: const EdgeInsets.only(left: 26),
          child: Text(
            field.value,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: Color(0xFF18324A)),
          ),
        ),
      ],
    );
  }
}

/// One bordered, shadowed white card with an icon+title header and
/// arbitrary content below — the section grouping every "view detail"
/// bottom sheet uses (Examination / Registration Details / Surgery / Pre-Op
/// Vitals / Eye Drop Register / ...), so each of those sheets doesn't
/// reimplement its own card chrome.
class AppDetailSectionCard extends StatelessWidget {
  final String title;
  final IconData icon;
  final Widget child;
  const AppDetailSectionCard({super.key, required this.title, required this.icon, required this.child});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: AppColors.primaryA08),
        boxShadow: [BoxShadow(color: AppColors.primaryA05, blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Icon(icon, size: 15, color: AppColors.primary),
          const SizedBox(width: 6),
          Text(title.toUpperCase(), style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 0.5, color: AppColors.primary)),
        ]),
        const SizedBox(height: 12),
        child,
      ]),
    );
  }
}

/// A small colored status pill — the "Surgery Recommended" / "Paid" /
/// "In Ward" style chip shown under a detail sheet's header.
class AppStatusPill extends StatelessWidget {
  final String label;
  final Color color;
  const AppStatusPill({super.key, required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(AppRadius.full),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Text(label, style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w700, color: color)),
    );
  }
}

/// Shared "view detail" bottom sheet shell — drag handle, avatar+name+code
/// header with a close icon, an optional row of status pills, then a
/// scrollable body of [sections] (normally a list of [AppDetailSectionCard]s).
/// Every OT role's "View" action opens one of these now instead of each
/// screen hand-rolling its own AlertDialog, so they all share one look.
Future<void> showAppDetailSheet(
  BuildContext context, {
  required String name,
  String? code,
  List<Widget> pills = const [],
  required List<Widget> sections,
}) {
  final initial = name.trim().isNotEmpty ? name.trim()[0].toUpperCase() : '?';
  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (sheetCtx) => DraggableScrollableSheet(
      initialChildSize: 0.8,
      minChildSize: 0.45,
      maxChildSize: 0.95,
      expand: false,
      builder: (_, scrollController) => Container(
        decoration: BoxDecoration(
          color: AppColors.background,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(22)),
        ),
        child: Column(children: [
          const SizedBox(height: 10),
          Container(width: 40, height: 4, decoration: BoxDecoration(color: AppColors.primaryA18, borderRadius: BorderRadius.circular(2))),
          Padding(
            padding: const EdgeInsets.fromLTRB(18, 12, 10, 10),
            child: Row(children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.circular(12)),
                alignment: Alignment.center,
                child: Text(initial, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 17)),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(name, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 16.5, fontWeight: FontWeight.w800, color: AppColors.darkNavy)),
                  if (code != null && code.isNotEmpty) Text(code, style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
                ]),
              ),
              IconButton(icon: const Icon(Icons.close_rounded), color: AppColors.textSecondary, onPressed: () => Navigator.pop(sheetCtx)),
            ]),
          ),
          if (pills.isNotEmpty)
            Padding(
              padding: const EdgeInsets.fromLTRB(18, 0, 18, 10),
              child: Wrap(spacing: 6, runSpacing: 6, children: pills),
            ),
          const Divider(height: 1),
          Expanded(
            child: SingleChildScrollView(
              controller: scrollController,
              padding: const EdgeInsets.fromLTRB(16, 14, 16, 24),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: sections),
            ),
          ),
        ]),
      ),
    ),
  );
}

// ── Shared compact row-action styling ───────────────────────────────────────
// Originated on the OT desk panels (`desk_home_panels.dart`) and promoted
// here so every "full desk screen" (Accountant, Ward, Discharge, OT
// Assistant...) can match that same compact look instead of each hand-rolling
// its own oversized `OutlinedButton.icon`/`FilledButton.icon` — a labeled
// pill sized for a lone, rare action looks and behaves very differently once
// a row has 2-3 of them side by side.

/// `OutlinedButton`/`FilledButton` enforce a 64×36dp minimum touch target by
/// default regardless of custom `padding` — `minimumSize: Size.zero` +
/// `tapTargetSize: shrinkWrap` are both required to actually shrink below
/// that, not just the padding.
ButtonStyle miniOutlinedStyle() => OutlinedButton.styleFrom(
      foregroundColor: AppColors.textSecondary,
      side: BorderSide(color: AppColors.primary.withValues(alpha: 0.2)),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
      minimumSize: Size.zero,
      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
      visualDensity: VisualDensity.compact,
      textStyle: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700),
    );

ButtonStyle miniFilledStyle(Color color) => FilledButton.styleFrom(
      backgroundColor: color,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
      minimumSize: Size.zero,
      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
      visualDensity: VisualDensity.compact,
      textStyle: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700),
    );

/// Small circular initial avatar — the same patient-identity token every OT
/// desk panel and full desk screen uses next to a name/MRD header.
class PatientAvatar extends StatelessWidget {
  final String name;
  final double size;
  const PatientAvatar({super.key, required this.name, this.size = 32});

  @override
  Widget build(BuildContext context) {
    final trimmed = name.trim();
    final initial = trimmed.isNotEmpty ? trimmed[0].toUpperCase() : '?';
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.12), shape: BoxShape.circle),
      alignment: Alignment.center,
      child: Text(initial, style: TextStyle(fontSize: size * 0.4, fontWeight: FontWeight.w800, color: AppColors.primary)),
    );
  }
}

/// "View" is the one action nearly every OT record row has in common, and an
/// eye icon is about as universally understood as an action gets — a
/// compact icon button reads better than a labeled pill once it sits next to
/// a real primary action (Add Payment/Operate/Generate/...), and doesn't
/// look like an orphaned lone button on rows where it's the only action.
Widget viewIconButton(VoidCallback onTap) => InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AppRadius.full),
      child: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.08), shape: BoxShape.circle),
        child: Icon(Icons.visibility_outlined, size: 17, color: AppColors.primary),
      ),
    );
