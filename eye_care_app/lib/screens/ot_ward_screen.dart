import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/permissions.dart';
import '../models/ot_ward_models.dart';
import '../services/ot_ward_service.dart';
import '../services/permission_service.dart';
import '../utils/date_format.dart';
import '../widgets/ot_datetime_field.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_record_card.dart';
import '../widgets/app_text_field.dart';
import '../widgets/skeleton.dart';

/// Closes the pane safely while a text field may still hold focus.
/// `unfocus()` only *schedules* the focus change — it doesn't complete
/// synchronously — so popping the route immediately after it still tears
/// the field down mid focus-change and throws `InheritedElement`'s
/// `_dependents.isEmpty` assertion. Deferring the pop to
/// `addPostFrameCallback` lets that scheduled change fully resolve on its
/// own frame first.
void _closeSheetSafely(BuildContext sheetContext) {
  FocusManager.instance.primaryFocus?.unfocus();
  WidgetsBinding.instance.addPostFrameCallback((_) {
    if (sheetContext.mounted) Navigator.pop(sheetContext);
  });
}

/// Ward Management's entire "record vitals / log eye drops / set status"
/// job, as one direct bottom sheet instead of a full pushed screen whose
/// 3 tabs each then needed their own "tap a button to open yet another
/// sheet with the actual fields" step. That used to be: tap Vitals & Eye
/// Drops → new screen → pick a tab → tap Record/Update → a sheet finally
/// has the fields → fill → Save → sheet closes → back on the tab showing
/// the saved value. Every field here is now directly visible and editable
/// (pre-filled with whatever's already saved) the moment this one sheet
/// opens, so recording a vital or logging a dose is fill-and-tap-Save, not
/// navigate-navigate-fill-save-navigate-back. Saving a section doesn't
/// close the sheet — only the explicit close button/swipe-down does — so a
/// nurse can save vitals, then log a dose, then update status, all in one
/// open/close of the pane.
Future<void> openOtWardEntryPane(
  BuildContext context, {
  required int bookingId,
  String? patientName,
  String? initialOtStatus,
}) {
  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (_) => DraggableScrollableSheet(
      initialChildSize: 0.92,
      minChildSize: 0.5,
      maxChildSize: 0.96,
      expand: false,
      builder: (_, scrollController) => _WardEntryPane(
        bookingId: bookingId,
        patientName: patientName,
        initialOtStatus: initialOtStatus,
        scrollController: scrollController,
      ),
    ),
  );
}

class _WardEntryPane extends StatefulWidget {
  final int bookingId;
  final String? patientName;
  final String? initialOtStatus;
  final ScrollController scrollController;

  const _WardEntryPane({
    required this.bookingId,
    this.patientName,
    this.initialOtStatus,
    required this.scrollController,
  });

  @override
  State<_WardEntryPane> createState() => _WardEntryPaneState();
}

class _WardEntryPaneState extends State<_WardEntryPane> {
  static final _genFmt = [
    FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d*')),
  ];

  OtVerificationHeader? _header;
  OtVitalsItem? _vitals;
  List<OtEyeDropEntry> _eyeDrops = [];
  bool _loading = true;
  String? _loadError;

  String? _currentOtStatus;
  bool _sendingReady = false;

  // ── Vitals form — always visible, pre-filled from the latest save ───────
  late final TextEditingController _bpCtrl,
      _pulseCtrl,
      _rbsCtrl,
      _tempCtrl,
      _spo2Ctrl,
      _hba1cCtrl;
  bool _savingVitals = false;

  // ── Status form ───────────────────────────────────────────────────────
  String _status = 'preparing';
  bool _savingStatus = false;
  String? _statusError;

  // ── Eye-drop form (add new dose) ─────────────────────────────────────
  final _doseCtrl = TextEditingController();
  final _remarksCtrl = TextEditingController();
  final _dilationTimeCtrl = TextEditingController();
  String? _medicineName;
  String _eye = 'RE';
  DateTime _administeredAt = toHospitalTime(DateTime.now());
  // Web pull 2026-10-10 — "Dilate?" Yes/No; dilation time (minutes) is
  // required only when Yes.
  bool _dilate = false;
  bool _savingDrop = false;
  String? _medicineError, _doseError, _dilationTimeError;

  bool get _canWriteVitals => PermissionService.instance.can(Perm.otPreopEntry);
  bool get _canWriteDrops =>
      PermissionService.instance.can(Perm.otDilationTrack);
  // Web gates the whole ward page — including "send to OT Assistant" — by
  // ot_ward_entry alone; the API's mark-ready route enforces the same key.
  bool get _canSurgeryReady => PermissionService.instance.can(Perm.otWardEntry);

  @override
  void initState() {
    super.initState();
    _currentOtStatus = widget.initialOtStatus;
    _bpCtrl = TextEditingController();
    _pulseCtrl = TextEditingController();
    _rbsCtrl = TextEditingController();
    _tempCtrl = TextEditingController();
    _spo2Ctrl = TextEditingController();
    _hba1cCtrl = TextEditingController();
    _load();
  }

  @override
  void dispose() {
    for (final c in [
      _bpCtrl,
      _pulseCtrl,
      _rbsCtrl,
      _tempCtrl,
      _spo2Ctrl,
      _hba1cCtrl,
      _doseCtrl,
      _remarksCtrl,
      _dilationTimeCtrl,
    ]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _loadError = null;
    });
    try {
      // Web embeds assistants on the ward page under ot_ward_entry.
      // Do NOT call /config/users here — that requires user_*_view and
      // blocks ward-only staff (Access denied on open).
      final results = await Future.wait([
        OtWardService.instance.fetchVerificationHeader(widget.bookingId),
        OtWardService.instance.fetchVitals(widget.bookingId),
        OtWardService.instance.fetchEyeDrops(widget.bookingId),
      ]);
      if (!mounted) return;
      final header = results[0] as OtVerificationHeader;
      final vitals = results[1] as OtVitalsItem?;
      final eyeDrops = results[2] as List<OtEyeDropEntry>;
      setState(() {
        _header = header;
        _vitals = vitals;
        _eyeDrops = eyeDrops;
        _loading = false;
        // Re-seed the always-visible form fields from the latest server
        // state — this is what "showing the filled value" means here: the
        // same field the user types into already holds the saved value.
        _bpCtrl.text = vitals?.bp ?? '';
        _pulseCtrl.text = vitals?.pulse ?? '';
        _rbsCtrl.text = vitals?.rbs?.toString() ?? '';
        _tempCtrl.text = vitals?.temperature?.toString() ?? '';
        _spo2Ctrl.text = vitals?.spo2?.toString() ?? '';
        _hba1cCtrl.text = vitals?.hba1c?.toString() ?? '';
        _status = vitals?.preOpStatus ?? 'preparing';
        final eyeOptions = switch (header.eye) {
          'RE' => const ['RE'],
          'LE' => const ['LE'],
          _ => const ['RE', 'LE'],
        };
        if (!eyeOptions.contains(_eye)) _eye = eyeOptions.first;
        _doseCtrl.text =
            '${(_eyeDrops.isEmpty ? 0 : _eyeDrops.map((e) => e.doseNumber).reduce((a, b) => a > b ? a : b)) + 1}';
      });
    } catch (e) {
      if (mounted)
        setState(() {
          _loadError = e.toString().replaceFirst('Exception: ', '');
          _loading = false;
        });
    }
  }

  Future<void> _saveVitals() async {
    setState(() => _savingVitals = true);
    try {
      // Card 1 is clinical vitals only — the current pre_op_status is
      // carried forward unchanged from the Status section's own state.
      final status = await OtWardService.instance.storeVitals(
        widget.bookingId,
        {
          'bp': _bpCtrl.text.trim().isEmpty ? null : _bpCtrl.text.trim(),
          // API/web: pulse is string (nullable|string|max:20) — not int.
          'pulse': _pulseCtrl.text.trim().isEmpty
              ? null
              : _pulseCtrl.text.trim(),
          'rbs': double.tryParse(_rbsCtrl.text.trim()),
          'temperature': double.tryParse(_tempCtrl.text.trim()),
          'spo2': double.tryParse(_spo2Ctrl.text.trim()),
          'hba1c': double.tryParse(_hba1cCtrl.text.trim()),
          'pre_op_status': _vitals?.preOpStatus ?? 'preparing',
        },
      );
      if (!mounted) return;
      setState(() {
        _currentOtStatus = status;
        _savingVitals = false;
      });
      showAppSnackBar(context, 'Vitals saved.', isSuccess: true);
      await _load();
    } catch (e) {
      if (mounted) {
        setState(() => _savingVitals = false);
        showAppSnackBar(
          context,
          e.toString().replaceFirst('Exception: ', ''),
          isError: true,
        );
      }
    }
  }

  bool get _statusIsReady => _status == 'ready_for_surgery';

  Future<void> _saveStatus() async {
    setState(() {
      // Web pull 2026-10-12 — the OT Assistant picker is gone; Ready for OT
      // now auto-resolves the assistant from the linked doctor's "Connected
      // OT Assistant" profile field, so this is caught client-side before
      // the save round-trip instead of after a 422.
      _statusError = _statusIsReady && _header?.defaultOtAssistant == null
          ? "This doctor has no active OT Assistant connected. Connect one on the doctor's profile (Users → Edit Doctor) before marking Ready for OT."
          : null;
    });
    if (_statusError != null) return;

    setState(() => _savingStatus = true);
    try {
      final status = await OtWardService.instance
          .storeVitals(widget.bookingId, {
            'pre_op_status': _status,
            'assign_staff': true,
          });
      if (!mounted) return;
      setState(() {
        _currentOtStatus = status;
        _savingStatus = false;
      });
      showAppSnackBar(context, 'Status saved.', isSuccess: true);
      await _load();
    } catch (e) {
      if (mounted) {
        setState(() => _savingStatus = false);
        showAppSnackBar(
          context,
          e.toString().replaceFirst('Exception: ', ''),
          isError: true,
        );
      }
    }
  }

  Future<void> _logEyeDrop() async {
    final dose = int.tryParse(_doseCtrl.text.trim());
    final dilationTime = int.tryParse(_dilationTimeCtrl.text.trim());
    setState(() {
      _medicineError = _medicineName == null ? 'Required' : null;
      _doseError = (dose == null || dose < 1 || dose > 20)
          ? 'Must be 1-20'
          : null;
      _dilationTimeError = _dilate && (dilationTime == null || dilationTime < 1 || dilationTime > 180)
          ? 'Enter the dilation time (minutes)'
          : null;
    });
    if (_medicineError != null || _doseError != null || _dilationTimeError != null) return;

    setState(() => _savingDrop = true);
    try {
      await OtWardService.instance.addEyeDrop(
        widget.bookingId,
        medicineName: _medicineName!,
        eye: _eye,
        doseNumber: dose!,
        administeredAt: toApiDateTimeLocal(_administeredAt),
        remarks: _remarksCtrl.text.trim().isEmpty
            ? null
            : _remarksCtrl.text.trim(),
        dilate: _dilate ? 'Yes' : 'No',
        dilationTime: _dilate ? dilationTime : null,
      );
      if (!mounted) return;
      setState(() {
        _savingDrop = false;
        _remarksCtrl.clear();
        _medicineName = null;
        _administeredAt = toHospitalTime(DateTime.now());
        _dilate = false;
        _dilationTimeCtrl.clear();
      });
      showAppSnackBar(context, 'Dose logged.', isSuccess: true);
      await _load();
    } catch (e) {
      if (mounted) {
        setState(() => _savingDrop = false);
        showAppSnackBar(
          context,
          e.toString().replaceFirst('Exception: ', ''),
          isError: true,
        );
      }
    }
  }

  Future<void> _sendToOtAssistant() async {
    setState(() => _sendingReady = true);
    try {
      final status = await OtWardService.instance.markReady(widget.bookingId);
      if (mounted) {
        setState(() {
          _currentOtStatus = status;
          _sendingReady = false;
        });
        showAppSnackBar(
          context,
          'Patient is ready and handed off to OT Assistant',
          isSuccess: true,
        );
      }
    } catch (e) {
      if (mounted) {
        setState(() => _sendingReady = false);
        showAppSnackBar(
          context,
          e.toString().replaceFirst('Exception: ', ''),
          isError: true,
        );
      }
    }
  }

  InputDecoration _deco(String label, {String? suffix, String? error}) =>
      InputDecoration(
        labelText: label,
        suffixText: suffix,
        errorText: error,
        filled: true,
        fillColor: AppColors.surfaceFill,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: BorderSide.none,
        ),
      );

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.background,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(22)),
      ),
      child: Column(
        children: [
          const SizedBox(height: 10),
          Container(
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: AppColors.primaryA18,
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(18, 12, 10, 10),
            child: Row(
              children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: AppColors.primary,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  alignment: Alignment.center,
                  child: const Icon(
                    Icons.bed_rounded,
                    color: Colors.white,
                    size: 20,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Ward Management',
                        style: TextStyle(
                          fontSize: 16.5,
                          fontWeight: FontWeight.w800,
                          color: AppColors.darkNavy,
                        ),
                      ),
                      if (widget.patientName != null)
                        Text(
                          widget.patientName!,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 12,
                            color: AppColors.textSecondary,
                          ),
                        ),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close_rounded),
                  color: AppColors.textSecondary,
                  onPressed: () => _closeSheetSafely(context),
                ),
              ],
            ),
          ),
          const Divider(height: 1),
          Expanded(
            child: _loading
                ? const AppSkeletonList()
                : _loadError != null
                ? AppErrorState(message: _loadError!, onRetry: _load)
                : SingleChildScrollView(
                    controller: widget.scrollController,
                    padding: const EdgeInsets.fromLTRB(16, 14, 16, 24),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _buildVerificationCard(),
                        _buildReadyBanner(),
                        // Web parity (`ot/ward/show.blade.php`): Vitals →
                        // Eye Drop Register → Patient Status, in that order.
                        AppDetailSectionCard(
                          title: 'Vitals',
                          icon: Icons.monitor_heart_outlined,
                          child: _buildVitalsForm(),
                        ),
                        AppDetailSectionCard(
                          title: 'Eye Drop Register',
                          icon: Icons.opacity_rounded,
                          child: _buildEyeDropsSection(),
                        ),
                        AppDetailSectionCard(
                          title: 'Status',
                          icon: Icons.assignment_turned_in_outlined,
                          child: _buildStatusForm(),
                        ),
                      ],
                    ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildVerificationCard() {
    final h = _header;
    if (h == null) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(AppRadius.md),
          border: Border.all(color: AppColors.primaryA10),
        ),
        child: Row(
          children: [
            Icon(
              Icons.verified_user_outlined,
              size: 18,
              color: AppColors.primary,
            ),
            const SizedBox(width: 8),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    h.patientName,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: AppColors.darkNavy,
                    ),
                  ),
                  Text(
                    'UHID: ${h.uhid}${h.surgeryType != null ? ' · ${h.surgeryType}' : ''}${h.eye != null ? ' · ${h.eye}' : ''}',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 11,
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// "Ready to send to OT Assistant?" banner — a real action, not just a
  /// status display. `_vitals.preOpStatus` is reliably known from the
  /// server on every load (unlike `_currentOtStatus`, which needs seeding
  /// from the caller), so the button shows whenever that's
  /// `ready_for_surgery` and the booking status is eligible; whether an OT
  /// Assistant is actually assigned isn't surfaced by any GET endpoint
  /// here, so that last check is left to the backend's own validation
  /// error on tap.
  Widget _buildReadyBanner() {
    final status = _currentOtStatus;
    if (status == null) return const SizedBox.shrink();
    final eligibleStatuses = {'payment_verified', 'in_ward'};
    final preOpReady = _vitals?.preOpStatus == 'ready_for_surgery';
    Color bg;
    Color fg;
    String label;
    final showButton = eligibleStatuses.contains(status) && preOpReady;
    if (status == 'ready') {
      bg = AppColors.green.withValues(alpha: 0.12);
      fg = AppColors.green;
      label = 'READY — sent to OT Assistant';
    } else if (eligibleStatuses.contains(status)) {
      bg = const Color(0xFFF1F5F9);
      fg = AppColors.textSecondary;
      label = preOpReady
          ? 'Ready to send to OT Assistant?'
          : 'Save Ready + OT Assistant first';
    } else {
      bg = AppColors.orangeA12;
      fg = AppColors.orange;
      label = 'Current status: ${status.toUpperCase()}';
    }
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(AppRadius.md),
        ),
        child: Row(
          children: [
            Icon(
              status == 'ready'
                  ? Icons.check_circle_outline
                  : Icons.info_outline,
              size: 16,
              color: fg,
            ),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                label,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: fg,
                ),
              ),
            ),
            if (showButton && _canSurgeryReady)
              SizedBox(
                height: 30,
                child: ElevatedButton.icon(
                  onPressed: _sendingReady ? null : _sendToOtAssistant,
                  icon: _sendingReady
                      ? const SizedBox(
                          width: 12,
                          height: 12,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Icon(Icons.send_rounded, size: 14),
                  label: const Text(
                    'READY',
                    style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(AppRadius.sm),
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildVitalsForm() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (_vitals?.enteredBy != null)
          Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: Text(
              'Last entered by ${_vitals!.enteredBy!.name}',
              style: const TextStyle(
                fontSize: 11,
                color: AppColors.textSecondary,
              ),
            ),
          ),
        IgnorePointer(
          ignoring: !_canWriteVitals,
          child: Opacity(
            opacity: _canWriteVitals ? 1 : 0.6,
            child: Column(
              children: [
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _bpCtrl,
                        decoration: _deco('BP (e.g. 120/80)'),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextFormField(
                        controller: _pulseCtrl,
                        keyboardType: TextInputType.number,
                        inputFormatters: [
                          FilteringTextInputFormatter.digitsOnly,
                        ],
                        decoration: _deco('Pulse', suffix: 'bpm'),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _rbsCtrl,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        inputFormatters: _genFmt,
                        decoration: _deco('RBS', suffix: 'mg/dL'),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextFormField(
                        controller: _tempCtrl,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        inputFormatters: _genFmt,
                        decoration: _deco('Temperature', suffix: '°F'),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _spo2Ctrl,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        inputFormatters: _genFmt,
                        decoration: _deco('SpO2', suffix: '%'),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextFormField(
                        controller: _hba1cCtrl,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        inputFormatters: _genFmt,
                        decoration: _deco('HbA1c', suffix: '%'),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
        if (_canWriteVitals) ...[
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _savingVitals ? null : _saveVitals,
              icon: _savingVitals
                  ? const SizedBox(
                      width: 14,
                      height: 14,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Icon(Icons.save_outlined, size: 16),
              label: const Text('Save Vitals'),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 12),
              ),
            ),
          ),
        ],
      ],
    );
  }

  Widget _buildStatusForm() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Status',
          style: TextStyle(
            fontSize: 11.5,
            fontWeight: FontWeight.w700,
            color: AppColors.textSecondary,
          ),
        ),
        const SizedBox(height: 8),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: kOtPreOpStatuses
              .map(
                (s) => ChoiceChip(
                  label: Text(otPreOpStatusLabel(s)),
                  selected: _status == s,
                  onSelected: _canWriteVitals
                      ? (_) => setState(() {
                          _status = s;
                          _statusError = null;
                        })
                      : null,
                ),
              )
              .toList(),
        ),
        const SizedBox(height: 12),
        // Web pull 2026-10-12 — web's Patient Status card is just the
        // status radios + Save button, nothing else ("No doctor / OT
        // Assistant fields here — both are assigned automatically on
        // save," per the blade's own comment). A prior pass here added a
        // read-only "Doctor/OT Assistant (auto-assigned)" field, which
        // still looked like a pickable form field even though it wasn't
        // one — removed per client feedback 2026-10-14 to match web's
        // actual minimal UI exactly. The auto-assign logic itself is
        // unchanged (still fully server-side); _statusError below still
        // catches the one case worth warning about before a round-trip.
        if (_statusError != null)
          Padding(
            padding: const EdgeInsets.only(top: 8),
            child: Text(
              _statusError!,
              style: const TextStyle(color: AppColors.red, fontSize: 12),
            ),
          ),
        if (_canWriteVitals) ...[
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _savingStatus ? null : _saveStatus,
              icon: _savingStatus
                  ? const SizedBox(
                      width: 14,
                      height: 14,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Icon(Icons.save_outlined, size: 16),
              label: const Text('Save Status'),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 12),
              ),
            ),
          ),
        ],
      ],
    );
  }

  Widget _buildEyeDropsSection() {
    final eyeOptions = switch (_header?.eye) {
      'RE' => const ['RE'],
      'LE' => const ['LE'],
      _ => const ['RE', 'LE'],
    };
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (_eyeDrops.isEmpty)
          const Padding(
            padding: EdgeInsets.only(bottom: 12),
            child: Text(
              'No doses logged yet.',
              style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
            ),
          )
        else ...[
          // API returns doses oldest-first (administered_at asc, matching
          // web); reversed here so the most recent dose — the one a nurse
          // actually needs to see — is the first thing on screen, not the
          // one scrolled furthest away.
          ..._eyeDrops.reversed.map(
            (d) => Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: AppColors.surfaceFill,
                  borderRadius: BorderRadius.circular(AppRadius.sm),
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 7,
                        vertical: 3,
                      ),
                      decoration: BoxDecoration(
                        color: AppColors.tealA12,
                        borderRadius: BorderRadius.circular(AppRadius.sm),
                      ),
                      child: Text(
                        '#${d.doseNumber}',
                        style: const TextStyle(
                          fontSize: 10.5,
                          fontWeight: FontWeight.w800,
                          color: AppColors.tealDark,
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            '${d.medicineName} · ${d.eye}',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontSize: 12.5,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                          if (d.dilate == 'Yes')
                            Text(
                              'Dilated${d.dilationTime != null ? ' · ${d.dilationTime} mins' : ''}',
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontSize: 10.5,
                                fontWeight: FontWeight.w700,
                                color: AppColors.tealDark,
                              ),
                            ),
                          if (d.remarks != null && d.remarks!.isNotEmpty)
                            Text(
                              d.remarks!,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontSize: 10.5,
                                color: AppColors.textSecondary,
                              ),
                            ),
                        ],
                      ),
                    ),
                    if (d.administeredAt != null)
                      Text(
                        formatDateTimeString(d.administeredAt),
                        style: const TextStyle(
                          fontSize: 10,
                          color: AppColors.textSecondary,
                        ),
                      ),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(height: 4),
          Container(height: 1, color: AppColors.primaryA08),
          const SizedBox(height: 12),
        ],
        if (_canWriteDrops) ...[
          Text(
            'Log New Dose',
            style: TextStyle(
              fontSize: 11.5,
              fontWeight: FontWeight.w700,
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 8),
          DropdownButtonFormField<String>(
            initialValue: _medicineName,
            isExpanded: true,
            decoration: _deco('Medicine Name *', error: _medicineError),
            items: (_header?.otMedicines ?? const [])
                .map(
                  (m) => DropdownMenuItem(
                    value: m.name,
                    child: Text(m.name, overflow: TextOverflow.ellipsis),
                  ),
                )
                .toList(),
            onChanged: (v) => setState(() {
              _medicineName = v;
              _medicineError = null;
            }),
          ),
          if ((_header?.otMedicines ?? const []).isEmpty) ...[
            const SizedBox(height: 6),
            const Text(
              'No ward medicines — add from Ward Medicine',
              style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
            ),
          ],
          const SizedBox(height: 10),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Eye',
                      style: TextStyle(
                        fontSize: 12.5,
                        fontWeight: FontWeight.w600,
                        color: AppColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 7),
                    AppSegmentedChips(
                      values: eyeOptions,
                      selected: _eye,
                      onChanged: (v) => setState(() => _eye = v),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: TextFormField(
                  controller: _doseCtrl,
                  keyboardType: TextInputType.number,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                  decoration: _deco('Dose #', error: _doseError),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          OtDateTimeField(
            label: 'Administered At',
            value: _administeredAt,
            onChanged: (v) => setState(() => _administeredAt = v),
          ),
          const SizedBox(height: 10),
          TextFormField(controller: _remarksCtrl, decoration: _deco('Remarks')),
          const SizedBox(height: 10),
          // Web pull 2026-10-10 — "Dilate?" Yes/No; dilation time (minutes)
          // only required/shown when Yes.
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Dilate',
                      style: TextStyle(
                        fontSize: 12.5,
                        fontWeight: FontWeight.w600,
                        color: AppColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 7),
                    Row(
                      children: [
                        ChoiceChip(
                          label: const Text('Yes'),
                          selected: _dilate,
                          onSelected: (_) => setState(() {
                            _dilate = true;
                            _dilationTimeError = null;
                          }),
                        ),
                        const SizedBox(width: 8),
                        ChoiceChip(
                          label: const Text('No'),
                          selected: !_dilate,
                          onSelected: (_) => setState(() {
                            _dilate = false;
                            _dilationTimeCtrl.clear();
                            _dilationTimeError = null;
                          }),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              if (_dilate) ...[
                const SizedBox(width: 16),
                Expanded(
                  child: TextFormField(
                    controller: _dilationTimeCtrl,
                    keyboardType: TextInputType.number,
                    inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                    decoration: _deco('Dilation Time', suffix: 'mins', error: _dilationTimeError),
                  ),
                ),
              ],
            ],
          ),
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _savingDrop ? null : _logEyeDrop,
              icon: _savingDrop
                  ? const SizedBox(
                      width: 14,
                      height: 14,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Icon(Icons.water_drop_outlined, size: 16),
              label: const Text('Log Dose'),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 12),
              ),
            ),
          ),
        ],
      ],
    );
  }
}
