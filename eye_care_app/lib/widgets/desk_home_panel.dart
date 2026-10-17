import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import 'app_empty_state.dart';
import 'app_error_state.dart';
import 'skeleton.dart';

/// One tab pill (with its own live count) inside a [DeskHomePanel]'s tab row.
class DeskPanelTabDef {
  final String value;
  final String label;
  final int count;
  const DeskPanelTabDef({required this.value, required this.label, required this.count});
}

/// Shared "desk panel" chrome for the 4 role-specific compact queue/history
/// widgets embedded directly on the Hospital Dashboard home
/// (`accountant-panel.blade.php` / `ward-panel.blade.php` /
/// `ot-assistant-panel.blade.php` / `discharge-panel.blade.php` on web).
///
/// Web renders each role's panel as a data TABLE. This app's own convention
/// (every other OT desk screen — see `ot_accountant_dashboard_screen.dart`
/// etc.) is a vertically-stacked card list instead, since a wide table
/// doesn't fit phone width. This widget is that convention's shared chrome:
/// header (icon + title + "N patient(s)" + "Open full page" link) → a
/// horizontally-scrollable row of tab pills (each with its own count badge,
/// matching web's per-tab count) → the active tab's body (loading skeleton /
/// error+retry / empty state / a plain `Column` of role-supplied row cards).
///
/// Deliberately a `Column`, not a `ListView` — this sits inside the
/// dashboard's own outer scrolling `ListView`, and it's a "mini" preview
/// (each per-role panel fetches only a handful of rows per tab), not a full
/// paginated list — that's what "Open full page" is for.
class DeskHomePanel extends StatefulWidget {
  final IconData icon;
  final String title;
  final int totalCount;
  final String openFullPageLabel;
  final VoidCallback onOpenFullPage;
  final List<DeskPanelTabDef> tabs;
  final String selectedTab;
  final ValueChanged<String> onTabSelected;
  final bool loading;
  final String? error;
  final VoidCallback onRetry;
  final List<Widget> rows;
  final String emptyMessage;
  final IconData emptyIcon;

  const DeskHomePanel({
    super.key,
    required this.icon,
    required this.title,
    required this.totalCount,
    this.openFullPageLabel = 'Open full page',
    required this.onOpenFullPage,
    required this.tabs,
    required this.selectedTab,
    required this.onTabSelected,
    required this.loading,
    required this.error,
    required this.onRetry,
    required this.rows,
    required this.emptyMessage,
    this.emptyIcon = Icons.inbox_rounded,
  });

  @override
  State<DeskHomePanel> createState() => _DeskHomePanelState();
}

class _DeskHomePanelState extends State<DeskHomePanel> {
  // Keyed per tab so the selected pill can be scrolled into view — a tab
  // opened directly from the stat-card tap above (e.g. "Payment Status")
  // previously landed selected but scrolled off the right edge of this
  // horizontal pill row, with nothing on screen to show which tab was even
  // active until the user noticed they could scroll it themselves.
  final Map<String, GlobalKey> _tabKeys = {};

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _scrollToSelected());
  }

  @override
  void didUpdateWidget(covariant DeskHomePanel oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.selectedTab != widget.selectedTab) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _scrollToSelected());
    }
  }

  void _scrollToSelected() {
    final key = _tabKeys[widget.selectedTab];
    final ctx = key?.currentContext;
    if (ctx == null) return;
    Scrollable.ensureVisible(ctx, alignment: 0.5, duration: const Duration(milliseconds: 200));
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(top: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.08)),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, 3))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _header(),
          const Divider(height: 1),
          _tabRow(),
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 4, 12, 12),
            child: _body(),
          ),
        ],
      ),
    );
  }

  Widget _header() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 14, 10, 10),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Container(
            width: 34,
            height: 34,
            decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(10)),
            child: Icon(widget.icon, color: AppColors.primary, size: 18),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(widget.title, style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w800, color: AppColors.darkNavy)),
          ),
          TextButton.icon(
            onPressed: widget.onOpenFullPage,
            style: TextButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4), visualDensity: VisualDensity.compact),
            icon: const Icon(Icons.open_in_new_rounded, size: 14),
            label: Text(widget.openFullPageLabel, style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700)),
          ),
        ]),
        const SizedBox(height: 2),
        Text('${widget.totalCount} patient${widget.totalCount == 1 ? '' : 's'}', style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
      ]),
    );
  }

  Widget _tabRow() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(10, 8, 10, 4),
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(children: [
          for (final t in widget.tabs)
            Padding(
              padding: const EdgeInsets.only(right: 8),
              child: _TabPill(
                key: _tabKeys.putIfAbsent(t.value, () => GlobalKey()),
                def: t,
                selected: t.value == widget.selectedTab,
                onTap: () => widget.onTabSelected(t.value),
              ),
            ),
        ]),
      ),
    );
  }

  Widget _body() {
    if (widget.loading) return const AppSkeletonList(count: 2, itemHeight: 96, padding: EdgeInsets.zero);
    if (widget.error != null) return Padding(padding: const EdgeInsets.symmetric(vertical: 8), child: AppErrorState(message: widget.error!, onRetry: widget.onRetry));
    if (widget.rows.isEmpty) return Padding(padding: const EdgeInsets.symmetric(vertical: 12), child: AppEmptyState(message: widget.emptyMessage, icon: widget.emptyIcon));
    return Column(children: widget.rows);
  }
}

class _TabPill extends StatelessWidget {
  final DeskPanelTabDef def;
  final bool selected;
  final VoidCallback onTap;
  const _TabPill({super.key, required this.def, required this.selected, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final bg = selected ? AppColors.primary : AppColors.primary.withValues(alpha: 0.06);
    final fg = selected ? Colors.white : AppColors.primary;
    return Material(
      color: bg,
      borderRadius: BorderRadius.circular(AppRadius.full),
      child: InkWell(
        borderRadius: BorderRadius.circular(AppRadius.full),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            Text(def.label, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: fg)),
            const SizedBox(width: 6),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
              decoration: BoxDecoration(color: selected ? Colors.white.withValues(alpha: 0.25) : AppColors.primary.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(AppRadius.full)),
              child: Text('${def.count}', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w800, color: fg)),
            ),
          ]),
        ),
      ),
    );
  }
}

/// Shared card shell every role-specific mini-row uses — a compact
/// (2–4 line) card matching the visual weight of this app's other OT desk
/// screens (white surface, subtle border, small shadow) but condensed for a
/// "mini" dashboard preview.
///
/// Was filled with `AppColors.backgroundAlt` (a flat grey-blue) — on a page
/// already sitting on that same light-blue background, every row read as a
/// disabled/greyed-out form field instead of live content. A plain white
/// surface with a soft shadow (the same card language used everywhere else
/// in the app) makes these rows look like the active, tappable content they
/// are.
class DeskPanelRowCard extends StatelessWidget {
  final Widget child;
  const DeskPanelRowCard({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.08)),
        boxShadow: [BoxShadow(color: AppColors.primaryA05, blurRadius: 6, offset: const Offset(0, 2))],
      ),
      child: child,
    );
  }
}

/// Small colored status/payment pill — the same visual token every role's
/// mini-row uses for its Status/Payment/Invoice badges.
class DeskPanelBadge extends StatelessWidget {
  final String label;
  final Color color;
  const DeskPanelBadge({super.key, required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(AppRadius.full)),
      child: Text(label, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: color)),
    );
  }
}
