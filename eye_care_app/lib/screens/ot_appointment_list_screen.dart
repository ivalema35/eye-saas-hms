import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/ot_appointment_models.dart';
import '../models/ot_booking_models.dart';
import '../services/masters_service.dart';
import '../services/ot_appointment_service.dart';
import '../services/ot_slot_service.dart';
import '../utils/app_route.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_pagination_bar.dart';
import '../widgets/app_search_bar.dart';
import '../widgets/app_section_header.dart';
import '../widgets/status_badge.dart';

class OtAppointmentListScreen extends StatefulWidget {
  const OtAppointmentListScreen({super.key});

  @override
  State<OtAppointmentListScreen> createState() => _OtAppointmentListScreenState();
}

class _OtAppointmentListScreenState extends State<OtAppointmentListScreen> {
  static const _statuses = ['all', 'booked', 'confirmed', 'cancelled', 'completed'];

  final _searchCtrl = TextEditingController();
  List<OtAppointmentItem> _items = [];
  OtPaginationMeta? _meta;
  bool _loading = true;
  String? _error;
  String _status = 'all';
  DateTime? _dateFilter;
  int _page = 1;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  String? get _dateStr => _dateFilter == null ? null : '${_dateFilter!.year}-${_dateFilter!.month.toString().padLeft(2, '0')}-${_dateFilter!.day.toString().padLeft(2, '0')}';

  // Matches web's `optional($appointment->appointment_date)->format('d M Y')`
  // exactly — web's index table has no time column at all, only this date.
  static const _months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  static String _fmtAppointmentDate(String raw) {
    final d = DateTime.tryParse(raw);
    if (d == null) return raw;
    return '${d.day.toString().padLeft(2, '0')} ${_months[d.month - 1]} ${d.year}';
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final result = await OtAppointmentService.instance.list(
        status: _status,
        date: _dateStr,
        search: _searchCtrl.text.trim().isEmpty ? null : _searchCtrl.text.trim(),
        page: _page,
      );
      if (mounted) setState(() { _items = result.items; _meta = result.meta; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  Future<void> _pickDateFilter() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _dateFilter ?? DateTime.now(),
      firstDate: DateTime.now().subtract(const Duration(days: 365)),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      builder: (ctx, child) => Theme(data: ThemeData.light().copyWith(colorScheme: ColorScheme.light(primary: AppColors.primary)), child: child!),
    );
    if (picked != null) { setState(() { _dateFilter = picked; _page = 1; }); _load(); }
  }

  Future<void> _confirm(OtAppointmentItem item) async {
    try {
      await OtAppointmentService.instance.confirm(item.id);
      if (mounted) showAppSnackBar(context, 'Appointment confirmed', isSuccess: true);
      _load();
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  Future<void> _cancel(OtAppointmentItem item) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
        title: const Text('Cancel appointment?', style: TextStyle(fontWeight: FontWeight.w800)),
        content: Text('Cancel ${item.fullName}\'s appointment (${item.appointmentNumber})?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('No')),
          TextButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Cancel Appointment', style: TextStyle(color: AppColors.red, fontWeight: FontWeight.w700))),
        ],
      ),
    );
    if (ok != true) return;
    try {
      await OtAppointmentService.instance.cancel(item.id);
      if (mounted) showAppSnackBar(context, 'Appointment cancelled', isSuccess: true);
      _load();
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  void _openForm({OtAppointmentItem? item}) {
    Navigator.of(context).push(appRoute(OtAppointmentFormScreen(item: item, onSaved: _load)));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        elevation: 0,
        leading: IconButton(icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20), onPressed: () => Navigator.pop(context)),
        title: const Text('OT Appointments', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800, letterSpacing: -0.2)),
        actions: [IconButton(icon: const Icon(Icons.add_circle_outline_rounded, color: Colors.white), tooltip: 'New Appointment', onPressed: () => _openForm())],
      ),
      body: Column(children: [
        _buildFilters(),
        Expanded(child: _buildBody()),
        if (_meta != null) Padding(padding: const EdgeInsets.symmetric(vertical: 8), child: AppPaginationBar(currentPage: _meta!.currentPage, totalPages: _meta!.lastPage, onPageChange: (p) { setState(() => _page = p); _load(); })),
      ]),
    );
  }

  Widget _buildFilters() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 4),
      child: Column(children: [
        SizedBox(
          height: 34,
          child: ListView(scrollDirection: Axis.horizontal, children: [
            for (final s in _statuses) ...[
              ChoiceChip(
                label: Text(s == 'all' ? 'All' : s[0].toUpperCase() + s.substring(1)),
                selected: _status == s,
                onSelected: (_) { setState(() { _status = s; _page = 1; }); _load(); },
                selectedColor: AppColors.primary.withValues(alpha: 0.16),
                labelStyle: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: _status == s ? AppColors.primary : AppColors.textSecondary),
              ),
              const SizedBox(width: 6),
            ],
            ActionChip(
              avatar: Icon(Icons.event_rounded, size: 16, color: _dateFilter != null ? AppColors.primary : AppColors.textSecondary),
              label: Text(_dateFilter == null ? 'Any date' : _dateStr!),
              onPressed: _pickDateFilter,
              labelStyle: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: _dateFilter != null ? AppColors.primary : AppColors.textSecondary),
            ),
            if (_dateFilter != null) ...[
              const SizedBox(width: 4),
              InkWell(onTap: () { setState(() { _dateFilter = null; _page = 1; }); _load(); }, child: const Padding(padding: EdgeInsets.all(6), child: Icon(Icons.close_rounded, size: 16, color: AppColors.textSecondary))),
            ],
          ]),
        ),
        const SizedBox(height: 8),
        AppSearchBar(controller: _searchCtrl, hint: 'Search by name, mobile, or APT number...', onChanged: (_) { setState(() => _page = 1); _load(); }, onClear: _load),
      ]),
    );
  }

  Widget _buildBody() {
    if (_loading) return Center(child: CircularProgressIndicator(color: AppColors.primary));
    if (_error != null) return AppErrorState(message: _error!, onRetry: _load);
    if (_items.isEmpty) return AppEmptyState(message: 'No appointments found.', icon: Icons.event_note_rounded, onRefresh: _load);

    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: _load,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 6, 14, 20),
        itemCount: _items.length,
        itemBuilder: (_, i) {
          final item = _items[i];
          // Matches web's index table exactly: Edit/Confirm/Cancel only show
          // for booked/confirmed appointments, else no action row at all.
          final actionable = item.status == 'booked' || item.status == 'confirmed';
          return Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.primary.withValues(alpha: 0.08)), boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.05), blurRadius: 8, offset: const Offset(0, 2))]),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(children: [
                  Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(item.fullName, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.darkNavy)),
                      const SizedBox(height: 2),
                      Text('${item.appointmentNumber} · ${item.mobileNo}', style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
                    ]),
                  ),
                  StatusBadge.otAppointmentStatus(item.status),
                ]),
                const SizedBox(height: 10),
                Row(children: [
                  Icon(Icons.event_rounded, size: 13, color: AppColors.primary.withValues(alpha: 0.5)),
                  const SizedBox(width: 4),
                  Text(_fmtAppointmentDate(item.appointmentDate), style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary)),
                  if (item.doctor != null) ...[
                    const SizedBox(width: 12),
                    Icon(Icons.medical_services_outlined, size: 13, color: AppColors.primary.withValues(alpha: 0.5)),
                    const SizedBox(width: 4),
                    Expanded(child: Text(item.doctor!.name, style: const TextStyle(fontSize: 11.5, color: AppColors.textSecondary), overflow: TextOverflow.ellipsis)),
                  ],
                ]),
                if (actionable) ...[
                  const SizedBox(height: 12),
                  Divider(height: 1, color: AppColors.primary.withValues(alpha: 0.08)),
                  const SizedBox(height: 10),
                  Row(children: [
                    if (item.status == 'booked') ...[
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () => _confirm(item),
                          icon: const Icon(Icons.check_circle_outline_rounded, size: 16, color: AppColors.green),
                          label: const Text('Confirm', style: TextStyle(color: AppColors.green, fontSize: 12.5, fontWeight: FontWeight.w700)),
                          style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 10), side: BorderSide(color: AppColors.green.withValues(alpha: 0.35)), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.sm))),
                        ),
                      ),
                      const SizedBox(width: 8),
                    ],
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () => _cancel(item),
                        icon: const Icon(Icons.cancel_outlined, size: 16, color: AppColors.red),
                        label: const Text('Cancel', style: TextStyle(color: AppColors.red, fontSize: 12.5, fontWeight: FontWeight.w700)),
                        style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 10), side: BorderSide(color: AppColors.red.withValues(alpha: 0.35)), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.sm))),
                      ),
                    ),
                    const SizedBox(width: 8),
                    IconButton(
                      onPressed: () => _openForm(item: item),
                      icon: const Icon(Icons.edit_outlined, size: 18, color: Color(0xFFE67E22)),
                      tooltip: 'Edit',
                      style: IconButton.styleFrom(backgroundColor: const Color(0xFFE67E22).withValues(alpha: 0.08), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.sm))),
                    ),
                  ]),
                ],
              ]),
            ),
          );
        },
      ),
    );
  }
}

// ── Add / Edit form (full page, matches PatientFormScreen's pattern —
// OT_WEB_PARITY_FIX_PRD.md §1.6: a floating bottom sheet was cramped and
// inconsistent with how every other "add an entity" flow in this app works)
// ─────────────────────────────────────────────────────────────────────────

class OtAppointmentFormScreen extends StatefulWidget {
  final OtAppointmentItem? item;
  final VoidCallback onSaved;

  const OtAppointmentFormScreen({super.key, required this.item, required this.onSaved});

  @override
  State<OtAppointmentFormScreen> createState() => _OtAppointmentFormScreenState();
}

class _OtAppointmentFormScreenState extends State<OtAppointmentFormScreen> {
  static const _types = ['phone', 'walk_in', 'online', 'referral'];
  static const _typeLabels = {'phone': 'Phone', 'walk_in': 'Walk-in', 'online': 'Online', 'referral': 'Referral'};
  static const _genders = ['male', 'female', 'other'];

  late final TextEditingController _nameCtrl, _middleCtrl, _surnameCtrl, _mobileCtrl, _whatsappCtrl, _ageCtrl, _occupationCtrl, _notesCtrl;
  String _type = 'walk_in';
  String _gender = 'male';
  DateTime? _date;
  OtSlotItem? _selectedSlot;
  int? _doctorId;
  int? _referrerId;
  int? _locationId;

  OtAppointmentFormData? _formData;
  bool _loadingForm = true;
  String? _formError;
  bool _saving = false;
  String? _nameErr, _surnameErr, _mobileErr, _ageErr, _dateErr, _doctorErr, _locationErr;

  List<OtSlotAppointmentConflict> _slotConflicts = [];
  bool _loadingSlot = false;

  // Matched against loaded slots once form data arrives (§1 field-order:
  // Appointment Time is a select of OtSlot rows on web, not a free time
  // picker — see OT_WEB_PARITY_FIX_PRD.md §1).
  String? _pendingSlotTime;

  @override
  void initState() {
    super.initState();
    final it = widget.item;
    _nameCtrl = TextEditingController(text: it?.patientName ?? '');
    _middleCtrl = TextEditingController(text: it?.middleName ?? '');
    _surnameCtrl = TextEditingController(text: it?.surname ?? '');
    _mobileCtrl = TextEditingController(text: it?.mobileNo ?? '');
    _whatsappCtrl = TextEditingController(text: it?.whatsappNo ?? '');
    _ageCtrl = TextEditingController(text: it != null ? '${it.age}' : '');
    _occupationCtrl = TextEditingController(text: it?.occupation ?? '');
    _notesCtrl = TextEditingController(text: it?.notes ?? '');
    _type = it?.appointmentType ?? 'walk_in';
    _gender = it?.gender ?? 'male';
    _doctorId = it?.doctor?.id;
    _referrerId = it?.referrerId;
    _locationId = it?.location?.id;
    if (it != null && it.appointmentDate.isNotEmpty) _date = DateTime.tryParse(it.appointmentDate);
    _pendingSlotTime = it?.appointmentTime;
    _loadFormData();
  }

  @override
  void dispose() {
    for (final c in [_nameCtrl, _middleCtrl, _surnameCtrl, _mobileCtrl, _whatsappCtrl, _ageCtrl, _occupationCtrl, _notesCtrl]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _loadFormData() async {
    try {
      final data = await OtAppointmentService.instance.fetchFormData();
      if (mounted) {
        setState(() {
          _formData = data;
          _loadingForm = false;
          if (_pendingSlotTime != null) {
            final match = data.slots.where((s) => _slotTimeStr(s) == _pendingSlotTime).toList();
            if (match.isNotEmpty) _selectedSlot = match.first;
          }
        });
        if (_date != null && _selectedSlot != null) _refreshSlotConflicts();
      }
    } catch (e) {
      if (mounted) setState(() { _formError = e.toString().replaceFirst('Exception: ', ''); _loadingForm = false; });
    }
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _date ?? now,
      firstDate: widget.item == null ? now : now.subtract(const Duration(days: 365)),
      lastDate: now.add(const Duration(days: 365)),
      builder: (ctx, child) => Theme(data: ThemeData.light().copyWith(colorScheme: ColorScheme.light(primary: AppColors.primary)), child: child!),
    );
    if (picked != null) {
      setState(() { _date = picked; _dateErr = null; });
      _refreshSlotConflicts();
    }
  }

  /// Raw `HH:mm` (matches web's `Carbon::parse($slot->start_time)->format('H:i')`
  /// stored value) derived from the slot's start time — `null`/short values
  /// return null so a slot with no configured start time is never selectable
  /// as a time filter.
  String? _slotTimeStr(OtSlotItem s) => (s.startTime != null && s.startTime!.length >= 5) ? s.startTime!.substring(0, 5) : null;

  /// 12-hour display clock ("09:00 AM") matching web's `format('h:i A')`.
  String _fmtClock(String? raw) {
    if (raw == null || raw.length < 5) return '';
    final h = int.tryParse(raw.substring(0, 2)) ?? 0;
    final m = raw.substring(3, 5);
    final period = h >= 12 ? 'PM' : 'AM';
    final h12 = h % 12 == 0 ? 12 : h % 12;
    return '$h12:$m $period';
  }

  String _slotLabel(OtSlotItem s) => s.startTime != null && s.endTime != null ? '${s.slotName} (${_fmtClock(s.startTime)} - ${_fmtClock(s.endTime)})' : s.slotName;

  /// On Date + Time change, shows a chip list of patients already booked in
  /// that exact slot — mirrors web's AJAX slot-occupancy panel (hidden if
  /// none). See OT_WEB_PARITY_FIX_PRD.md §1.1.
  Future<void> _refreshSlotConflicts() async {
    final timeStr = _selectedSlot != null ? _slotTimeStr(_selectedSlot!) : null;
    if (_date == null || timeStr == null) {
      setState(() => _slotConflicts = []);
      return;
    }
    final dateStr = '${_date!.year}-${_date!.month.toString().padLeft(2, '0')}-${_date!.day.toString().padLeft(2, '0')}';
    setState(() => _loadingSlot = true);
    try {
      final conflicts = await OtAppointmentService.instance.slotAppointments(date: dateStr, time: timeStr, excludeId: widget.item?.id);
      if (mounted) setState(() { _slotConflicts = conflicts; _loadingSlot = false; });
    } catch (_) {
      if (mounted) setState(() { _slotConflicts = []; _loadingSlot = false; });
    }
  }

  Future<void> _addCity() async {
    final cityCtrl = TextEditingController();
    final distCtrl = TextEditingController();
    final stateCtrl = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.lg)),
        title: const Text('Add City'),
        content: Column(mainAxisSize: MainAxisSize.min, children: [
          TextField(controller: cityCtrl, decoration: const InputDecoration(labelText: 'City *', border: OutlineInputBorder())),
          const SizedBox(height: 12),
          TextField(controller: distCtrl, decoration: const InputDecoration(labelText: 'District', border: OutlineInputBorder())),
          const SizedBox(height: 12),
          TextField(controller: stateCtrl, decoration: const InputDecoration(labelText: 'State', border: OutlineInputBorder())),
        ]),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          ElevatedButton(onPressed: () => Navigator.pop(ctx, true), style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white), child: const Text('Add')),
        ],
      ),
    );
    if (ok != true || cityCtrl.text.trim().isEmpty) return;
    try {
      final loc = await MastersService.instance.addLocation(city: cityCtrl.text.trim(), district: distCtrl.text.trim(), state: stateCtrl.text.trim());
      if (!mounted) return;
      setState(() {
        _formData = OtAppointmentFormData(
          doctors: _formData!.doctors,
          locations: [..._formData!.locations, OtAppointmentLocation(id: loc.id, name: loc.city, district: loc.district, state: loc.state)],
          referrers: _formData!.referrers,
          slots: _formData!.slots,
          nextAppointmentNumber: _formData!.nextAppointmentNumber,
        );
        _locationId = loc.id;
        _locationErr = null;
      });
      showAppSnackBar(context, 'City "${loc.city}" added.', isSuccess: true);
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  Future<void> _save() async {
    final age = int.tryParse(_ageCtrl.text.trim());
    setState(() {
      _nameErr = _nameCtrl.text.trim().isEmpty ? 'Required' : null;
      _surnameErr = _surnameCtrl.text.trim().isEmpty ? 'Required' : null;
      _mobileErr = _mobileCtrl.text.trim().isEmpty ? 'Required' : null;
      _ageErr = age == null ? 'Invalid' : null;
      _dateErr = _date == null ? 'Required' : null;
      _doctorErr = _doctorId == null ? 'Select a doctor' : null;
      _locationErr = _locationId == null ? 'Select a location' : null;
    });
    if ([_nameErr, _surnameErr, _mobileErr, _ageErr, _dateErr, _doctorErr, _locationErr].any((e) => e != null)) return;

    setState(() => _saving = true);
    try {
      final body = {
        'appointment_type': _type,
        'appointment_date': '${_date!.year}-${_date!.month.toString().padLeft(2, '0')}-${_date!.day.toString().padLeft(2, '0')}',
        if (_selectedSlot != null) 'appointment_time': _slotTimeStr(_selectedSlot!),
        'doctor_id': _doctorId,
        'patient_name': _nameCtrl.text.trim(),
        'middle_name': _middleCtrl.text.trim(),
        'surname': _surnameCtrl.text.trim(),
        'mobile_no': _mobileCtrl.text.trim(),
        'whatsapp_no': _whatsappCtrl.text.trim(),
        'age': age,
        'gender': _gender,
        'occupation': _occupationCtrl.text.trim(),
        if (_referrerId != null) 'referrer_id': _referrerId,
        'location_id': _locationId,
        'notes': _notesCtrl.text.trim(),
      };
      if (widget.item == null) {
        await OtAppointmentService.instance.create(body);
      } else {
        await OtAppointmentService.instance.update(widget.item!.id, body);
      }
      widget.onSaved();
      if (mounted) Navigator.pop(context);
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
    }
  }

  InputDecoration _deco(String label, {String? error, String? suffix}) => InputDecoration(
        labelText: label,
        errorText: error,
        suffixText: suffix,
        filled: true,
        fillColor: const Color(0xFFF0F6FB),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide.none),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide(color: AppColors.primary, width: 1.5)),
      );

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFEBF5FB),
      body: Column(children: [
        _buildHeader(),
        Expanded(
          child: _loadingForm
              ? Center(child: CircularProgressIndicator(color: AppColors.primary))
              : _formError != null
                  ? AppErrorState(message: _formError!, onRetry: () { setState(() => _loadingForm = true); _loadFormData(); })
                  : _buildForm(),
        ),
      ]),
    );
  }

  Widget _buildHeader() {
    final isEdit = widget.item != null;
    return Container(
      decoration: BoxDecoration(gradient: LinearGradient(colors: [AppColors.primary, AppColors.blueLight], begin: Alignment.topLeft, end: Alignment.bottomRight)),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(8, 10, 20, 14),
          child: Row(children: [
            IconButton(icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20), onPressed: () => Navigator.pop(context)),
            Container(width: 40, height: 40, decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(10)), child: const Icon(Icons.event_note_rounded, color: Colors.white, size: 20)),
            const SizedBox(width: 12),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(isEdit ? 'Edit Appointment' : 'New Appointment', style: const TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.w800)),
                Text(isEdit ? 'Update appointment details' : 'Pre-registration — patient hasn\'t arrived yet', style: TextStyle(color: Colors.white.withValues(alpha: 0.75), fontSize: 11)),
              ]),
            ),
          ]),
        ),
      ),
    );
  }

  Widget _buildForm() {
    final formData = _formData!;
    return Column(children: [
      Expanded(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 14, 16, 20),
          children: [
            const AppSectionHeader(title: 'Appointment Details', icon: Icons.event_note_rounded),
            Wrap(spacing: 8, children: _types.map((t) => ChoiceChip(label: Text(_typeLabels[t]!), selected: _type == t, onSelected: (_) => setState(() => _type = t))).toList()),
            const SizedBox(height: 12),
            Row(children: [
              Expanded(child: InkWell(onTap: _pickDate, borderRadius: BorderRadius.circular(AppRadius.md), child: InputDecorator(decoration: _deco('Date *', error: _dateErr), child: Text(_date == null ? 'Select' : '${_date!.year}-${_date!.month.toString().padLeft(2, '0')}-${_date!.day.toString().padLeft(2, '0')}')))),
              const SizedBox(width: 10),
              Expanded(
                child: DropdownButtonFormField<OtSlotItem>(
                  initialValue: _selectedSlot,
                  isExpanded: true,
                  decoration: _deco('Time'),
                  items: formData.slots.map((s) => DropdownMenuItem(value: s, child: Text(_slotLabel(s), overflow: TextOverflow.ellipsis))).toList(),
                  onChanged: (v) {
                    setState(() => _selectedSlot = v);
                    _refreshSlotConflicts();
                  },
                ),
              ),
            ]),
            if (_loadingSlot || _slotConflicts.isNotEmpty) ...[
              const SizedBox(height: 8),
              Align(
                alignment: Alignment.centerLeft,
                child: _loadingSlot
                    ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2))
                    : Wrap(spacing: 6, runSpacing: 6, children: [
                        const Padding(padding: EdgeInsets.only(right: 2), child: Text('Already booked:', style: TextStyle(fontSize: 11, color: AppColors.textSecondary))),
                        ..._slotConflicts.map((c) => Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(color: AppColors.orangeA12, borderRadius: BorderRadius.circular(AppRadius.full)),
                              child: Text(c.name, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.orange)),
                            )),
                      ]),
              ),
            ],
            const SizedBox(height: 16),

            const AppSectionHeader(title: 'Doctor', icon: Icons.medical_services_outlined),
            DropdownButtonFormField<int>(
              initialValue: _doctorId,
              isExpanded: true,
              decoration: _deco('Doctor *', error: _doctorErr),
              items: formData.doctors.map((d) => DropdownMenuItem(value: d.id, child: Text(d.name, overflow: TextOverflow.ellipsis))).toList(),
              onChanged: (v) => setState(() { _doctorId = v; _doctorErr = null; }),
            ),
            const SizedBox(height: 16),

            const AppSectionHeader(title: 'Patient Details', icon: Icons.person_outline_rounded),
            Row(children: [
              Expanded(child: TextFormField(controller: _nameCtrl, textCapitalization: TextCapitalization.words, decoration: _deco('First Name *', error: _nameErr))),
              const SizedBox(width: 10),
              Expanded(child: TextFormField(controller: _middleCtrl, textCapitalization: TextCapitalization.words, decoration: _deco('Middle Name'))),
            ]),
            const SizedBox(height: 12),
            TextFormField(controller: _surnameCtrl, textCapitalization: TextCapitalization.words, decoration: _deco('Surname *', error: _surnameErr)),
            const SizedBox(height: 12),
            Row(children: [
              Expanded(child: TextFormField(controller: _mobileCtrl, keyboardType: TextInputType.phone, inputFormatters: [FilteringTextInputFormatter.digitsOnly], decoration: _deco('Mobile No *', error: _mobileErr))),
              const SizedBox(width: 10),
              Expanded(child: TextFormField(controller: _whatsappCtrl, keyboardType: TextInputType.phone, inputFormatters: [FilteringTextInputFormatter.digitsOnly], decoration: _deco('WhatsApp No'))),
            ]),
            const SizedBox(height: 12),
            Row(children: [
              Expanded(child: TextFormField(controller: _ageCtrl, keyboardType: TextInputType.number, inputFormatters: [FilteringTextInputFormatter.digitsOnly], decoration: _deco('Age *', error: _ageErr))),
              const SizedBox(width: 10),
              Expanded(
                child: DropdownButtonFormField<String>(
                  initialValue: _gender,
                  decoration: _deco('Gender *'),
                  items: _genders.map((g) => DropdownMenuItem(value: g, child: Text(g[0].toUpperCase() + g.substring(1)))).toList(),
                  onChanged: (v) => setState(() => _gender = v ?? _gender),
                ),
              ),
            ]),
            const SizedBox(height: 12),
            TextFormField(controller: _occupationCtrl, decoration: _deco('Occupation')),
            const SizedBox(height: 16),

            const AppSectionHeader(title: 'Location', icon: Icons.location_on_outlined),
            Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Expanded(
                child: DropdownButtonFormField<int>(
                  initialValue: _locationId,
                  isExpanded: true,
                  decoration: _deco('City *', error: _locationErr),
                  items: formData.locations.map((l) => DropdownMenuItem(value: l.id, child: Text(l.name, overflow: TextOverflow.ellipsis))).toList(),
                  onChanged: (v) => setState(() { _locationId = v; _locationErr = null; }),
                ),
              ),
              IconButton(icon: Icon(Icons.add_circle_outline_rounded, color: AppColors.primary), tooltip: 'Add City', onPressed: _addCity),
            ]),
            if (_locationId != null) ...[
              const SizedBox(height: 10),
              Builder(builder: (_) {
                final loc = formData.locations.where((l) => l.id == _locationId).toList();
                if (loc.isEmpty) return const SizedBox.shrink();
                return Row(children: [
                  Expanded(child: TextFormField(enabled: false, controller: TextEditingController(text: loc.first.district ?? '—'), decoration: _deco('District'))),
                  const SizedBox(width: 10),
                  Expanded(child: TextFormField(enabled: false, controller: TextEditingController(text: loc.first.state ?? '—'), decoration: _deco('State'))),
                ]);
              }),
            ],
            const SizedBox(height: 16),

            const AppSectionHeader(title: 'Additional Details', icon: Icons.notes_rounded),
            DropdownButtonFormField<int?>(
              initialValue: _referrerId,
              isExpanded: true,
              decoration: _deco('Referrer'),
              items: [
                const DropdownMenuItem(value: null, child: Text('None')),
                ...formData.referrers.map((r) => DropdownMenuItem(value: r.id, child: Text(r.name, overflow: TextOverflow.ellipsis))),
              ],
              onChanged: (v) => setState(() => _referrerId = v),
            ),
            const SizedBox(height: 12),
            TextFormField(controller: _notesCtrl, maxLines: 3, decoration: _deco('Notes')),
          ],
        ),
      ),
      SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
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
      ),
    ]);
  }
}
