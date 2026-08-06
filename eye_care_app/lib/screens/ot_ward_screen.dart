import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/permissions.dart';
import '../models/ot_appointment_models.dart';
import '../models/ot_booking_models.dart';
import '../models/ot_ward_models.dart';
import '../services/ot_appointment_service.dart';
import '../services/ot_ward_service.dart';
import '../services/permission_service.dart';
import '../services/user_service.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_error_state.dart';

/// Round 3 Phase 3 — Ward Management. Read (`ot.ward.entry`) and write
/// (`ot.preop.entry` vitals / `ot.dilation.track` eye-drops) are gated
/// per-action, not per-screen — a shared ward tablet may have different
/// staff logged in with different subsets of these permissions.
class OtWardScreen extends StatefulWidget {
  final int bookingId;
  final String? patientName;
  // Seeds Card 4's status banner correctly on first open when known (e.g.
  // from the Ward Entry Queue row) — otherwise it stays unknown until a
  // save this session round-trips it back. See _currentOtStatus below.
  final String? initialOtStatus;

  const OtWardScreen({super.key, required this.bookingId, this.patientName, this.initialOtStatus});

  @override
  State<OtWardScreen> createState() => _OtWardScreenState();
}

class _OtWardScreenState extends State<OtWardScreen> with SingleTickerProviderStateMixin {
  late final TabController _tabCtrl;

  OtVerificationHeader? _header;
  OtVitalsItem? _vitals;
  List<OtEyeDropEntry> _eyeDrops = [];
  List<OtNamedRef> _doctors = [];
  List<HospitalUserModel> _assistants = [];
  bool _loading = true;
  String? _loadError;

  // Card 4 "Ready to send to OT Assistant?" banner needs the booking's
  // *current* ot_status, which no GET endpoint on this screen returns
  // directly — seeded from the Ward Entry Queue row when available
  // (initialOtStatus), otherwise only known once a save round-trips it back
  // (see OT_WEB_PARITY_FIX_PRD.md §4 Card 4).
  String? _currentOtStatus;
  bool _sendingReady = false;

  bool get _canWriteVitals => PermissionService.instance.can(Perm.otPreopEntry);
  bool get _canWriteDrops => PermissionService.instance.can(Perm.otDilationTrack);

  @override
  void initState() {
    super.initState();
    _tabCtrl = TabController(length: 3, vsync: this);
    _currentOtStatus = widget.initialOtStatus;
    _load();
  }

  @override
  void dispose() {
    _tabCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _loadError = null; });
    try {
      final results = await Future.wait([
        OtWardService.instance.fetchVerificationHeader(widget.bookingId),
        OtWardService.instance.fetchVitals(widget.bookingId),
        OtWardService.instance.fetchEyeDrops(widget.bookingId),
        OtAppointmentService.instance.fetchFormData(),
        UserService.instance.fetchUsers(),
      ]);
      if (mounted) {
        final users = (results[4] as UserListResponse).users;
        setState(() {
          _header = results[0] as OtVerificationHeader;
          _vitals = results[1] as OtVitalsItem?;
          _eyeDrops = results[2] as List<OtEyeDropEntry>;
          _doctors = (results[3] as OtAppointmentFormData).doctors;
          // No dedicated "list OT assistants" endpoint — reuse the users
          // master list filtered to the ot_assistant role slug (see
          // OT_WEB_PARITY_FIX_PRD.md §12.5). Falls back to showing every
          // non-doctor staff member if that role slug isn't present, so the
          // picker is never left empty.
          final byRole = users.where((u) => u.role?.slug == 'ot_assistant').toList();
          _assistants = byRole.isNotEmpty ? byRole : users.where((u) => u.role?.slug != 'doctor').toList();
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() { _loadError = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  void _openVitalsSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      isDismissible: false,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (_) => _VitalsSheet(
        current: _vitals,
        onSave: (body) async {
          // Card 1 is clinical vitals only — the current pre_op_status is
          // carried forward unchanged, not editable from this sheet.
          body['pre_op_status'] = _vitals?.preOpStatus ?? 'preparing';
          final status = await OtWardService.instance.storeVitals(widget.bookingId, body);
          if (mounted) setState(() => _currentOtStatus = status);
          await _load();
        },
      ),
    );
  }

  void _openEyeDropSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      isDismissible: false,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (_) => _EyeDropSheet(
        nextDose: (_eyeDrops.isEmpty ? 0 : _eyeDrops.map((e) => e.doseNumber).reduce((a, b) => a > b ? a : b)) + 1,
        onSave: (medicineName, eye, doseNumber, remarks) async {
          await OtWardService.instance.addEyeDrop(widget.bookingId, medicineName: medicineName, eye: eye, doseNumber: doseNumber, remarks: remarks);
          await _load();
        },
      ),
    );
  }

  void _openStatusSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      isDismissible: false,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (_) => _StatusSheet(
        currentStatus: _vitals?.preOpStatus ?? 'preparing',
        doctors: _doctors,
        assistants: _assistants,
        onSave: (status, doctorId, assistantId) async {
          final newStatus = await OtWardService.instance.storeVitals(widget.bookingId, {
            'pre_op_status': status,
            'assign_staff': true,
            if (doctorId != null) 'ot_doctor_id': doctorId,
            if (assistantId != null) 'ot_assistant_id': assistantId,
          });
          if (mounted) setState(() => _currentOtStatus = newStatus);
          await _load();
        },
      ),
    );
  }

  Future<void> _sendToOtAssistant() async {
    setState(() => _sendingReady = true);
    try {
      final status = await OtWardService.instance.markReady(widget.bookingId);
      if (mounted) {
        setState(() { _currentOtStatus = status; _sendingReady = false; });
        showAppSnackBar(context, 'Patient is ready and handed off to OT Assistant', isSuccess: true);
      }
    } catch (e) {
      if (mounted) {
        setState(() => _sendingReady = false);
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        elevation: 0,
        leading: IconButton(icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20), onPressed: () => Navigator.pop(context)),
        title: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          const Text('Ward Management', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800)),
          if (widget.patientName != null) Text(widget.patientName!, style: TextStyle(color: Colors.white.withValues(alpha: 0.75), fontSize: 11)),
        ]),
        bottom: TabBar(
          controller: _tabCtrl,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [Tab(text: 'Vitals'), Tab(text: 'Eye Drops'), Tab(text: 'Status')],
        ),
      ),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: AppColors.primary))
          : _loadError != null
              ? AppErrorState(message: _loadError!, onRetry: _load)
              : Column(children: [
                  _buildVerificationHeader(),
                  _buildReadyBanner(),
                  Expanded(
                    child: TabBarView(controller: _tabCtrl, children: [_buildVitalsTab(), _buildEyeDropsTab(), _buildStatusTab()]),
                  ),
                ]),
    );
  }

  /// Card 4 — "Ready to send to OT Assistant?" banner (OT_WEB_PARITY_FIX_PRD.md
  /// §4), now a real action matching web exactly — a submit button, not just
  /// a status display. `_vitals.preOpStatus` is reliably known from the
  /// server on every load (unlike `_currentOtStatus`, which needs seeding —
  /// see its doc comment), so the button shows whenever that's
  /// `ready_for_surgery` and the status is eligible; whether an OT Assistant
  /// is actually assigned isn't surfaced by any GET endpoint here, so that
  /// last check is left to the backend's own validation error on tap.
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
      label = preOpReady ? 'Ready to send to OT Assistant?' : 'Save Ready + OT Assistant first';
    } else {
      bg = AppColors.orangeA12;
      fg = AppColors.orange;
      label = 'Current status: ${status.toUpperCase()}';
    }
    return Container(
      margin: const EdgeInsets.fromLTRB(14, 10, 14, 0),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(AppRadius.md)),
      child: Row(children: [
        Icon(status == 'ready' ? Icons.check_circle_outline : Icons.info_outline, size: 16, color: fg),
        const SizedBox(width: 8),
        Expanded(child: Text(label, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: fg))),
        if (showButton)
          SizedBox(
            height: 30,
            child: ElevatedButton.icon(
              onPressed: _sendingReady ? null : _sendToOtAssistant,
              icon: _sendingReady ? const SizedBox(width: 12, height: 12, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Icon(Icons.send_rounded, size: 14),
              label: const Text('READY', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800)),
              style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(horizontal: 12), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.sm))),
            ),
          ),
      ]),
    );
  }

  Widget _buildVerificationHeader() {
    final h = _header;
    if (h == null) return const SizedBox.shrink();
    return Container(
      margin: const EdgeInsets.fromLTRB(14, 12, 14, 0),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.1))),
      child: Row(children: [
        Icon(Icons.verified_user_outlined, size: 18, color: AppColors.primary),
        const SizedBox(width: 8),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(h.patientName, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.darkNavy)),
            Text('UHID: ${h.uhid}${h.surgeryType != null ? ' · ${h.surgeryType}' : ''}${h.eye != null ? ' · ${h.eye}' : ''}', style: const TextStyle(fontSize: 11, color: AppColors.textSecondary)),
          ]),
        ),
      ]),
    );
  }

  Widget _buildVitalsTab() {
    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 20),
        children: [
          if (_vitals == null)
            Padding(padding: const EdgeInsets.symmetric(vertical: 40), child: Center(child: Text('No vitals recorded yet.', style: TextStyle(color: AppColors.textSecondary))))
          else
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.08))),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(children: [
                  Expanded(child: Text('Status: ${otPreOpStatusLabel(_vitals!.preOpStatus)}', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700))),
                  if (_vitals!.enteredBy != null) Text('by ${_vitals!.enteredBy!.name}', style: const TextStyle(fontSize: 11, color: AppColors.textSecondary)),
                ]),
                const Divider(height: 20),
                Wrap(spacing: 16, runSpacing: 8, children: [
                  _vitalStat('BP', _vitals!.bp ?? '—'),
                  _vitalStat('Pulse', _vitals!.pulse?.toString() ?? '—'),
                  _vitalStat('RBS', _vitals!.rbs?.toString() ?? '—'),
                  _vitalStat('Temp', _vitals!.temperature?.toString() ?? '—'),
                  _vitalStat('SpO2', _vitals!.spo2?.toString() ?? '—'),
                  _vitalStat('HbA1c', _vitals!.hba1c?.toString() ?? '—'),
                ]),
              ]),
            ),
          const SizedBox(height: 16),
          if (_canWriteVitals)
            ElevatedButton.icon(
              onPressed: _openVitalsSheet,
              icon: const Icon(Icons.monitor_heart_outlined, size: 18),
              label: Text(_vitals == null ? 'Record Vitals' : 'Update Vitals'),
              style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14)),
            ),
        ],
      ),
    );
  }

  Widget _vitalStat(String label, String value) => SizedBox(
        width: 90,
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(label, style: const TextStyle(fontSize: 10, color: AppColors.textSecondary, fontWeight: FontWeight.w600)),
          Text(value, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.darkNavy)),
        ]),
      );

  Widget _buildEyeDropsTab() {
    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 20),
        children: [
          if (_canWriteDrops)
            Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: ElevatedButton.icon(onPressed: _openEyeDropSheet, icon: const Icon(Icons.water_drop_outlined, size: 18), label: const Text('Log Dose'), style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14))),
            ),
          if (_eyeDrops.isEmpty)
            Padding(padding: const EdgeInsets.symmetric(vertical: 30), child: Center(child: Text('No doses logged yet.', style: TextStyle(color: AppColors.textSecondary))))
          else
            ..._eyeDrops.map((d) => Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.08))),
                    child: Row(children: [
                      Container(padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4), decoration: BoxDecoration(color: AppColors.tealA12, borderRadius: BorderRadius.circular(AppRadius.sm)), child: Text('#${d.doseNumber}', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: AppColors.tealDark))),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Text('${d.medicineName} · ${d.eye}', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700)),
                          if (d.remarks != null && d.remarks!.isNotEmpty) Text(d.remarks!, style: const TextStyle(fontSize: 11, color: AppColors.textSecondary)),
                        ]),
                      ),
                      if (d.administeredAt != null) Text(d.administeredAt!, style: const TextStyle(fontSize: 10, color: AppColors.textSecondary)),
                    ]),
                  ),
                )),
        ],
      ),
    );
  }

  /// Card 3 — "Patient Status" (separate card, own form, but posts to the
  /// same vitals endpoint with `assign_staff=1` — see OT_WEB_PARITY_FIX_PRD.md
  /// §4).
  Widget _buildStatusTab() {
    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 20),
        children: [
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.08))),
            child: Row(children: [
              Icon(Icons.assignment_turned_in_outlined, size: 18, color: AppColors.primary),
              const SizedBox(width: 8),
              Expanded(child: Text('Pre-op status: ${otPreOpStatusLabel(_vitals?.preOpStatus ?? 'preparing')}', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700))),
            ]),
          ),
          const SizedBox(height: 16),
          if (_canWriteVitals)
            ElevatedButton.icon(
              onPressed: _openStatusSheet,
              icon: const Icon(Icons.badge_outlined, size: 18),
              label: const Text('Save Status'),
              style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14)),
            ),
        ],
      ),
    );
  }
}

// ── Vitals sheet ───────────────────────────────────────────────────────────────

class _VitalsSheet extends StatefulWidget {
  final OtVitalsItem? current;
  final Future<void> Function(Map<String, dynamic> body) onSave;

  const _VitalsSheet({required this.current, required this.onSave});

  @override
  State<_VitalsSheet> createState() => _VitalsSheetState();
}

class _VitalsSheetState extends State<_VitalsSheet> {
  static final _genFmt = [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d*'))];

  late final TextEditingController _bpCtrl, _pulseCtrl, _rbsCtrl, _tempCtrl, _spo2Ctrl, _hba1cCtrl;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final v = widget.current;
    _bpCtrl = TextEditingController(text: v?.bp ?? '');
    _pulseCtrl = TextEditingController(text: v?.pulse?.toString() ?? '');
    _rbsCtrl = TextEditingController(text: v?.rbs?.toString() ?? '');
    _tempCtrl = TextEditingController(text: v?.temperature?.toString() ?? '');
    _spo2Ctrl = TextEditingController(text: v?.spo2?.toString() ?? '');
    _hba1cCtrl = TextEditingController(text: v?.hba1c?.toString() ?? '');
  }

  @override
  void dispose() {
    for (final c in [_bpCtrl, _pulseCtrl, _rbsCtrl, _tempCtrl, _spo2Ctrl, _hba1cCtrl]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    setState(() => _saving = true);
    try {
      await widget.onSave({
        'bp': _bpCtrl.text.trim().isEmpty ? null : _bpCtrl.text.trim(),
        'pulse': int.tryParse(_pulseCtrl.text.trim()),
        'rbs': double.tryParse(_rbsCtrl.text.trim()),
        'temperature': double.tryParse(_tempCtrl.text.trim()),
        'spo2': double.tryParse(_spo2Ctrl.text.trim()),
        'hba1c': double.tryParse(_hba1cCtrl.text.trim()),
      });
      if (mounted) Navigator.pop(context);
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
    }
  }

  InputDecoration _deco(String label, {String? suffix}) => InputDecoration(
        labelText: label,
        suffixText: suffix,
        filled: true,
        fillColor: const Color(0xFFF0F6FB),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide.none),
      );

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.only(bottom: bottom),
      child: SingleChildScrollView(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Container(margin: const EdgeInsets.only(top: 10, bottom: 4), width: 40, height: 4, decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.18), borderRadius: BorderRadius.circular(2))),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 10, 8, 14),
            child: Row(children: [
              const Expanded(child: Text('Record Vitals', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.darkNavy))),
              IconButton(icon: const Icon(Icons.close_rounded, size: 20, color: Color(0xFF94A3B8)), onPressed: () => Navigator.pop(context)),
            ]),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Column(children: [
              Row(children: [
                Expanded(child: TextFormField(controller: _bpCtrl, decoration: _deco('BP (e.g. 120/80)'))),
                const SizedBox(width: 10),
                Expanded(child: TextFormField(controller: _pulseCtrl, keyboardType: TextInputType.number, inputFormatters: [FilteringTextInputFormatter.digitsOnly], decoration: _deco('Pulse', suffix: 'bpm'))),
              ]),
              const SizedBox(height: 12),
              Row(children: [
                Expanded(child: TextFormField(controller: _rbsCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), inputFormatters: _genFmt, decoration: _deco('RBS', suffix: 'mg/dL'))),
                const SizedBox(width: 10),
                Expanded(child: TextFormField(controller: _tempCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), inputFormatters: _genFmt, decoration: _deco('Temperature', suffix: '°F'))),
              ]),
              const SizedBox(height: 12),
              Row(children: [
                Expanded(child: TextFormField(controller: _spo2Ctrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), inputFormatters: _genFmt, decoration: _deco('SpO2', suffix: '%'))),
                const SizedBox(width: 10),
                Expanded(child: TextFormField(controller: _hba1cCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), inputFormatters: _genFmt, decoration: _deco('HbA1c', suffix: '%'))),
              ]),
            ]),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 20, 16, 28),
            child: Row(children: [
              Expanded(child: OutlinedButton(onPressed: _saving ? null : () => Navigator.pop(context), style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md))), child: const Text('Cancel'))),
              const SizedBox(width: 12),
              Expanded(
                flex: 2,
                child: ElevatedButton(
                  onPressed: _saving ? null : _save,
                  style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md))),
                  child: _saving ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) : const Text('Save', style: TextStyle(fontWeight: FontWeight.w700)),
                ),
              ),
            ]),
          ),
        ]),
      ),
    );
  }
}

// ── Eye-drop sheet ─────────────────────────────────────────────────────────────

class _EyeDropSheet extends StatefulWidget {
  final int nextDose;
  final Future<void> Function(String medicineName, String eye, int doseNumber, String? remarks) onSave;

  const _EyeDropSheet({required this.nextDose, required this.onSave});

  @override
  State<_EyeDropSheet> createState() => _EyeDropSheetState();
}

class _EyeDropSheetState extends State<_EyeDropSheet> {
  late final TextEditingController _medicineCtrl, _doseCtrl, _remarksCtrl;
  String _eye = 'RE';
  bool _saving = false;
  String? _medicineError, _doseError;

  @override
  void initState() {
    super.initState();
    _medicineCtrl = TextEditingController();
    _doseCtrl = TextEditingController(text: '${widget.nextDose}');
    _remarksCtrl = TextEditingController();
  }

  @override
  void dispose() {
    _medicineCtrl.dispose();
    _doseCtrl.dispose();
    _remarksCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final dose = int.tryParse(_doseCtrl.text.trim());
    setState(() {
      _medicineError = _medicineCtrl.text.trim().isEmpty ? 'Required' : null;
      _doseError = (dose == null || dose < 1 || dose > 20) ? 'Must be 1-20' : null;
    });
    if (_medicineError != null || _doseError != null) return;

    setState(() => _saving = true);
    try {
      await widget.onSave(_medicineCtrl.text.trim(), _eye, dose!, _remarksCtrl.text.trim().isEmpty ? null : _remarksCtrl.text.trim());
      if (mounted) Navigator.pop(context);
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
    }
  }

  InputDecoration _deco(String label, {String? error}) => InputDecoration(
        labelText: label,
        errorText: error,
        filled: true,
        fillColor: const Color(0xFFF0F6FB),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide.none),
      );

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.only(bottom: bottom),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        Container(margin: const EdgeInsets.only(top: 10, bottom: 4), width: 40, height: 4, decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.18), borderRadius: BorderRadius.circular(2))),
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 10, 8, 14),
          child: Row(children: [
            const Expanded(child: Text('Log Eye-Drop Dose', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.darkNavy))),
            IconButton(icon: const Icon(Icons.close_rounded, size: 20, color: Color(0xFF94A3B8)), onPressed: () => Navigator.pop(context)),
          ]),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Column(children: [
            TextFormField(controller: _medicineCtrl, textCapitalization: TextCapitalization.words, decoration: _deco('Medicine Name *', error: _medicineError)),
            const SizedBox(height: 12),
            Row(children: [
              Wrap(spacing: 8, children: ['RE', 'LE'].map((e) => ChoiceChip(label: Text(e), selected: _eye == e, onSelected: (_) => setState(() => _eye = e))).toList()),
              const SizedBox(width: 16),
              Expanded(child: TextFormField(controller: _doseCtrl, keyboardType: TextInputType.number, inputFormatters: [FilteringTextInputFormatter.digitsOnly], decoration: _deco('Dose #', error: _doseError))),
            ]),
            const SizedBox(height: 12),
            TextFormField(controller: _remarksCtrl, decoration: _deco('Remarks')),
          ]),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 20, 16, 28),
          child: Row(children: [
            Expanded(child: OutlinedButton(onPressed: _saving ? null : () => Navigator.pop(context), style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md))), child: const Text('Cancel'))),
            const SizedBox(width: 12),
            Expanded(
              flex: 2,
              child: ElevatedButton(
                onPressed: _saving ? null : _save,
                style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md))),
                child: _saving ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) : const Text('Save', style: TextStyle(fontWeight: FontWeight.w700)),
              ),
            ),
          ]),
        ),
      ]),
    );
  }
}

// ── Status sheet (Card 3) ───────────────────────────────────────────────────────

class _StatusSheet extends StatefulWidget {
  final String currentStatus;
  final List<OtNamedRef> doctors;
  final List<HospitalUserModel> assistants;
  final Future<void> Function(String status, int? doctorId, int? assistantId) onSave;

  const _StatusSheet({required this.currentStatus, required this.doctors, required this.assistants, required this.onSave});

  @override
  State<_StatusSheet> createState() => _StatusSheetState();
}

class _StatusSheetState extends State<_StatusSheet> {
  late String _status;
  int? _doctorId;
  int? _assistantId;
  bool _saving = false;
  String? _error;

  bool get _isReady => _status == 'ready_for_surgery';

  @override
  void initState() {
    super.initState();
    _status = widget.currentStatus;
  }

  Future<void> _save() async {
    // Live conditional-required toggling matches web exactly: Doctor is
    // required unless status=Ready for OT, Assistant only when it is.
    setState(() {
      _error = null;
      if (!_isReady && _doctorId == null) _error = 'Doctor is required unless status is Ready for OT';
      if (_isReady && _assistantId == null) _error = 'OT Assistant is required when status is Ready for OT';
    });
    if (_error != null) return;

    setState(() => _saving = true);
    try {
      await widget.onSave(_status, _doctorId, _assistantId);
      if (mounted) Navigator.pop(context);
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
    }
  }

  InputDecoration _deco(String label) => InputDecoration(
        labelText: label,
        filled: true,
        fillColor: const Color(0xFFF0F6FB),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide.none),
      );

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.only(bottom: bottom),
      child: SingleChildScrollView(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Container(margin: const EdgeInsets.only(top: 10, bottom: 4), width: 40, height: 4, decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.18), borderRadius: BorderRadius.circular(2))),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 10, 8, 14),
            child: Row(children: [
              const Expanded(child: Text('Patient Status', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.darkNavy))),
              IconButton(icon: const Icon(Icons.close_rounded, size: 20, color: Color(0xFF94A3B8)), onPressed: () => Navigator.pop(context)),
            ]),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Column(children: [
              Align(alignment: Alignment.centerLeft, child: Text('Status *', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.textSecondary))),
              const SizedBox(height: 8),
              Wrap(spacing: 8, runSpacing: 8, children: kOtPreOpStatuses.map((s) => ChoiceChip(label: Text(otPreOpStatusLabel(s)), selected: _status == s, onSelected: (_) => setState(() { _status = s; _error = null; }))).toList()),
              const SizedBox(height: 14),
              DropdownButtonFormField<int>(
                initialValue: _doctorId,
                isExpanded: true,
                decoration: _deco(_isReady ? 'Doctor' : 'Doctor *'),
                items: widget.doctors.map((d) => DropdownMenuItem(value: d.id, child: Text(d.name, overflow: TextOverflow.ellipsis))).toList(),
                onChanged: (v) => setState(() => _doctorId = v),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<int>(
                initialValue: _assistantId,
                isExpanded: true,
                decoration: _deco(_isReady ? 'OT Assistant *' : 'OT Assistant'),
                items: widget.assistants.map((a) => DropdownMenuItem(value: a.id, child: Text(a.name, overflow: TextOverflow.ellipsis))).toList(),
                onChanged: (v) => setState(() => _assistantId = v),
              ),
              if (_error != null) Padding(padding: const EdgeInsets.only(top: 8), child: Text(_error!, style: const TextStyle(color: AppColors.red, fontSize: 12))),
            ]),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 20, 16, 28),
            child: Row(children: [
              Expanded(child: OutlinedButton(onPressed: _saving ? null : () => Navigator.pop(context), style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md))), child: const Text('Cancel'))),
              const SizedBox(width: 12),
              Expanded(
                flex: 2,
                child: ElevatedButton(
                  onPressed: _saving ? null : _save,
                  style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md))),
                  child: _saving ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) : const Text('Save Status', style: TextStyle(fontWeight: FontWeight.w700)),
                ),
              ),
            ]),
          ),
        ]),
      ),
    );
  }
}
