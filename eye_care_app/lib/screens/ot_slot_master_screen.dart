import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../widgets/app_animations.dart';
import '../services/ot_slot_service.dart';

class OtSlotMasterScreen extends StatefulWidget {
  final Color accentColor;

  const OtSlotMasterScreen({
    super.key,
    this.accentColor = const Color(0xFF1B4F72),
  });

  @override
  State<OtSlotMasterScreen> createState() => _OtSlotMasterScreenState();
}

class _OtSlotMasterScreenState extends State<OtSlotMasterScreen> {
  List<OtSlotItem> _all = [];
  bool _loading = false;
  String? _error;
  String _query = '';

  List<OtSlotItem> get _filtered => _query.isEmpty
      ? _all
      : _all
          .where((i) => i.slotName.toLowerCase().contains(_query.toLowerCase()))
          .toList();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final items = await OtSlotService.instance.fetchAll();
      if (mounted) {
        setState(() { _all = items; _loading = false; });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString().replaceFirst('Exception: ', '');
          _loading = false;
        });
      }
    }
  }

  Future<void> _delete(OtSlotItem item) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
        title: const Text('Delete?',
            style: TextStyle(fontWeight: FontWeight.w800)),
        content: Text('Delete "${item.slotName}"? This cannot be undone.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete',
                style: TextStyle(color: AppColors.red, fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    try {
      await OtSlotService.instance.delete(item.id);
      await _load();
    } catch (e) {
      if (mounted) {
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
      _load();
    }
  }

  void _openSheet({OtSlotItem? item}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (_) => _OtSlotSheet(
        item: item,
        accentColor: widget.accentColor,
        onSave: (slotName, start, end) async {
          if (item == null) {
            await OtSlotService.instance.create(slotName, start, end);
          } else {
            await OtSlotService.instance.update(item.id, slotName, start, end);
          }
          await _load();
        },
      ),
    );
  }



  // Formats "HH:MM:SS" or "HH:MM" → "HH:MM" (strips seconds if present)
  static String _fmt(String? t) {
    if (t == null || t.isEmpty) return '--:--';
    final parts = t.split(':');
    if (parts.length < 2) return t;
    return '${parts[0].padLeft(2, '0')}:${parts[1].padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded,
              color: Colors.white, size: 20),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text(
          'OT Slots',
          style: TextStyle(
            color: Colors.white,
            fontSize: 18,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.2,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.add_circle_outline_rounded, color: Colors.white),
            tooltip: 'Add',
            onPressed: () => _openSheet(),
          ),
        ],
      ),
      body: Column(
        children: [
          _buildSearch(),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildSearch() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 4),
      child: Container(
        height: 44,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(AppRadius.md),
          border: Border.all(color: AppColors.primary.withValues(alpha: 0.12)),
          boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.05), blurRadius: 8)],
        ),
        child: TextField(
          onChanged: (v) => setState(() => _query = v.trim()),
          style: const TextStyle(fontSize: 13.5, color: AppColors.darkNavy),
          decoration: const InputDecoration(
            hintText: 'Search OT slots...',
            hintStyle: TextStyle(color: AppColors.textDisabled, fontSize: 13),
            prefixIcon: Icon(Icons.search_rounded,
                color: AppColors.textDisabled, size: 20),
            border: InputBorder.none,
            contentPadding:
                EdgeInsets.symmetric(horizontal: 12, vertical: 12),
          ),
        ),
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return Center(child: CircularProgressIndicator(color: AppColors.primary));
    }
    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.error_outline_rounded,
                  size: 48, color: AppColors.red.withValues(alpha: 0.6)),
              const SizedBox(height: 12),
              Text(_error!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: AppColors.textSecondary)),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: _load,
                icon: const Icon(Icons.refresh_rounded),
                label: const Text('Retry'),
                style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary, foregroundColor: Colors.white),
              ),
            ],
          ),
        ),
      );
    }

    final items = _filtered;
    if (items.isEmpty) {
      return ListView(children: [
        const SizedBox(height: 72),
        Center(
          child: Column(children: [
            Icon(Icons.schedule_rounded,
                size: 56, color: AppColors.primary.withValues(alpha: 0.15)),
            const SizedBox(height: 12),
            Text(
              _query.isNotEmpty
                  ? 'No results for "$_query"'
                  : 'No OT slots yet.\nTap + to add one.',
              textAlign: TextAlign.center,
              style: const TextStyle(
                  color: AppColors.textDisabled,
                  fontSize: 14,
                  fontWeight: FontWeight.w500),
            ),
          ]),
        ),
      ]);
    }

    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: _load,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 6, 14, 100),
        itemCount: items.length,
        itemBuilder: (_, i) {
          final item = items[i];
          final hasTime = item.startTime != null || item.endTime != null;
          return Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Container(
              padding: const EdgeInsets.fromLTRB(12, 11, 8, 11),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppColors.primary.withValues(alpha: 0.08)),
                boxShadow: [BoxShadow(
                  color: AppColors.primary.withValues(alpha: 0.05),
                  blurRadius: 8,
                  offset: const Offset(0, 2),
                )],
              ),
              child: Row(
                children: [
                  // Serial badge
                  Container(
                    width: 30, height: 30,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: widget.accentColor.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(AppRadius.sm),
                    ),
                    child: Text('${i + 1}',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w800,
                          color: widget.accentColor,
                        )),
                  ),
                  const SizedBox(width: 12),
                  // Slot name
                  Expanded(
                    child: Text(item.slotName,
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: AppColors.darkNavy,
                        )),
                  ),
                  // Time range badge
                  if (hasTime) ...[
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: widget.accentColor.withValues(alpha: 0.10),
                        borderRadius: BorderRadius.circular(AppRadius.sm),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.access_time_rounded,
                              size: 11, color: widget.accentColor),
                          const SizedBox(width: 4),
                          Text(
                            '${_fmt(item.startTime)} – ${_fmt(item.endTime)}',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                              color: widget.accentColor,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 4),
                  ],
                  // Edit
                  InkWell(
                    onTap: () => _openSheet(item: item),
                    borderRadius: BorderRadius.circular(AppRadius.sm),
                    child: const Padding(
                      padding: EdgeInsets.all(6),
                      child: Icon(Icons.edit_outlined,
                          size: 18, color: AppColors.orange),
                    ),
                  ),
                  const SizedBox(width: 2),
                  // Delete
                  InkWell(
                    onTap: () => _delete(item),
                    borderRadius: BorderRadius.circular(AppRadius.sm),
                    child: const Padding(
                      padding: EdgeInsets.all(6),
                      child: Icon(Icons.delete_outline_rounded,
                          size: 18, color: AppColors.red),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

// ── Add / Edit sheet ──────────────────────────────────────────────────────────

class _OtSlotSheet extends StatefulWidget {
  final OtSlotItem? item;
  final Color accentColor;
  final Future<void> Function(
      String slotName, String? startTime, String? endTime) onSave;

  const _OtSlotSheet({
    required this.item,
    required this.accentColor,
    required this.onSave,
  });

  @override
  State<_OtSlotSheet> createState() => _OtSlotSheetState();
}

class _OtSlotSheetState extends State<_OtSlotSheet> {
  late final TextEditingController _nameCtrl;
  TimeOfDay? _startTime;
  TimeOfDay? _endTime;
  bool _saving = false;
  String? _nameError;
  String? _startError;
  String? _endError;

  @override
  void initState() {
    super.initState();
    _nameCtrl = TextEditingController(text: widget.item?.slotName ?? '');
    _startTime = _parseTime(widget.item?.startTime);
    _endTime   = _parseTime(widget.item?.endTime);
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    super.dispose();
  }

  // Parses "HH:MM" or "HH:MM:SS" string → TimeOfDay
  TimeOfDay? _parseTime(String? t) {
    if (t == null || t.isEmpty) return null;
    final parts = t.split(':');
    if (parts.length < 2) return null;
    final h = int.tryParse(parts[0]);
    final m = int.tryParse(parts[1]);
    if (h == null || m == null) return null;
    return TimeOfDay(hour: h, minute: m);
  }

  // Formats TimeOfDay → "HH:MM"
  String _toHHMM(TimeOfDay t) =>
      '${t.hour.toString().padLeft(2, '0')}:${t.minute.toString().padLeft(2, '0')}';

  String _displayTime(TimeOfDay? t) {
    if (t == null) return 'Select time';
    return _toHHMM(t);
  }

  Future<void> _pickTime(bool isStart) async {
    final initial = isStart
        ? (_startTime ?? const TimeOfDay(hour: 8, minute: 0))
        : (_endTime   ?? const TimeOfDay(hour: 10, minute: 0));

    final picked = await showTimePicker(
      context: context,
      initialTime: initial,
      builder: (ctx, child) => MediaQuery(
        data: MediaQuery.of(ctx).copyWith(alwaysUse24HourFormat: true),
        child: child!,
      ),
    );
    if (picked != null && mounted) {
      setState(() {
        if (isStart) {
          _startTime = picked;
          _startError = null;
        } else {
          _endTime = picked;
          _endError = null;
        }
      });
    }
  }

  Future<void> _save() async {
    final name = _nameCtrl.text.trim();
    final startMin = _startTime != null ? _startTime!.hour * 60 + _startTime!.minute : null;
    final endMin   = _endTime   != null ? _endTime!.hour   * 60 + _endTime!.minute   : null;
    setState(() {
      _nameError  = name.isEmpty ? 'Slot name is required' : null;
      _startError = _startTime == null ? 'Start time required' : null;
      _endError   = _endTime == null
          ? 'End time required'
          : (endMin != null && startMin != null && endMin <= startMin)
              ? 'End time must be after start time'
              : null;
    });
    if (_nameError != null || _startError != null || _endError != null) return;

    setState(() => _saving = true);
    try {
      await widget.onSave(
        name,
        _startTime != null ? _toHHMM(_startTime!) : null,
        _endTime   != null ? _toHHMM(_endTime!)   : null,
      );
      if (mounted) Navigator.pop(context);
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.only(bottom: bottom),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Drag handle
          Container(
            margin: const EdgeInsets.only(top: 10, bottom: 4),
            width: 40, height: 4,
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.18),
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          // Header
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 10, 8, 14),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(7),
                  decoration: BoxDecoration(
                    color: widget.accentColor.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(Icons.schedule_rounded,
                      size: 16, color: widget.accentColor),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    widget.item == null ? 'Add OT Slot' : 'Edit OT Slot',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w800,
                      color: AppColors.darkNavy,
                    ),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close_rounded,
                      size: 20, color: AppColors.textDisabled),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
          ),
          // Slot name
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
            child: TextFormField(
              controller: _nameCtrl,
              autofocus: true,
              textCapitalization: TextCapitalization.words,
              decoration: InputDecoration(
                labelText: 'Slot Name *',
                errorText: _nameError,
                filled: true,
                fillColor: AppColors.surfaceFill,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide: BorderSide.none,
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide:
                      BorderSide(color: widget.accentColor, width: 1.5),
                ),
              ),
            ),
          ),
          // Time pickers row
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 0),
            child: Row(
              children: [
                Expanded(child: _TimePicker(
                  label: 'Start Time *',
                  value: _displayTime(_startTime),
                  accentColor: widget.accentColor,
                  hasValue: _startTime != null,
                  errorText: _startError,
                  onTap: () => _pickTime(true),
                  onClear: () => setState(() { _startTime = null; _startError = null; }),
                )),
                const SizedBox(width: 12),
                Expanded(child: _TimePicker(
                  label: 'End Time *',
                  value: _displayTime(_endTime),
                  accentColor: widget.accentColor,
                  hasValue: _endTime != null,
                  errorText: _endError,
                  onTap: () => _pickTime(false),
                  onClear: () => setState(() { _endTime = null; _endError = null; }),
                )),
              ],
            ),
          ),
          // Actions
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 20, 16, 28),
            child: Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: _saving ? null : () => Navigator.pop(context),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(AppRadius.md)),
                    ),
                    child: const Text('Cancel'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  flex: 2,
                  child: ElevatedButton(
                    onPressed: _saving ? null : _save,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(AppRadius.md)),
                    ),
                    child: _saving
                        ? const SizedBox(
                            width: 18, height: 18,
                            child: CircularProgressIndicator(
                                color: Colors.white, strokeWidth: 2),
                          )
                        : const Text('Save',
                            style: TextStyle(fontWeight: FontWeight.w700)),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ── Time picker tile ──────────────────────────────────────────────────────────

class _TimePicker extends StatelessWidget {
  final String label;
  final String value;
  final Color accentColor;
  final bool hasValue;
  final String? errorText;
  final VoidCallback onTap;
  final VoidCallback onClear;

  const _TimePicker({
    required this.label,
    required this.value,
    required this.accentColor,
    required this.hasValue,
    required this.onTap,
    required this.onClear,
    this.errorText,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
      GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.fromLTRB(12, 10, 8, 10),
        decoration: BoxDecoration(
          color: AppColors.surfaceFill,
          borderRadius: BorderRadius.circular(AppRadius.md),
          border: Border.all(
            color: hasValue
                ? accentColor.withValues(alpha: 0.4)
                : Colors.transparent,
            width: 1.5,
          ),
        ),
        child: Row(
          children: [
            Icon(Icons.access_time_rounded,
                size: 16,
                color: hasValue ? accentColor : AppColors.textDisabled),
            const SizedBox(width: 6),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(label,
                      style: const TextStyle(
                          fontSize: 10,
                          color: AppColors.textDisabled,
                          fontWeight: FontWeight.w500)),
                  Text(value,
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: hasValue
                            ? accentColor
                            : AppColors.textDisabled,
                      )),
                ],
              ),
            ),
            if (hasValue)
              GestureDetector(
                onTap: onClear,
                child: Icon(Icons.close_rounded,
                    size: 14, color: accentColor.withValues(alpha: 0.5)),
              ),
          ],
        ),
      ),
      ),
      if (errorText != null)
        Padding(
          padding: const EdgeInsets.only(top: 4, left: 4),
          child: Text(
            errorText!,
            style: const TextStyle(color: Colors.red, fontSize: 11, fontWeight: FontWeight.w500),
          ),
        ),
      ],
    );
  }
}
