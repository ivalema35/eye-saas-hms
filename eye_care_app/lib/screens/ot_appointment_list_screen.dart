import 'package:flutter/material.dart';
import '../widgets/app_refresh_indicator.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/permissions.dart';
import '../models/ot_appointment_models.dart';
import '../utils/date_format.dart';
import '../services/masters_service.dart';
import '../services/ot_appointment_service.dart';
import '../services/ot_slot_service.dart';
import '../services/permission_service.dart';
import '../utils/app_route.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_record_card.dart';
import '../widgets/app_text_field.dart';
import '../widgets/skeleton.dart';
import '../widgets/app_error_state.dart';
import '../widgets/status_badge.dart';
import '../utils/app_date_picker.dart';

class OtAppointmentListScreen extends StatefulWidget {
  const OtAppointmentListScreen({super.key});

  @override
  State<OtAppointmentListScreen> createState() =>
      _OtAppointmentListScreenState();
}

class _OtAppointmentListScreenState extends State<OtAppointmentListScreen> {
  List<OtAppointmentItem> _items = [];
  bool _loading = true;
  String? _error;
  // Web pull 2026-10-07 — the 16-stage filter was replaced by a simple
  // Booked/Completed tab split (mirrors
  // Hospital\OT\OtAppointmentController::index()'s own move).
  String _tab = 'booked';
  int _bookedCount = 0;
  int _completedCount = 0;
  // Web pull 2026-10-06 — index() defaults to today, widened via a
  // date-range picker. Defaulted client-side too, so the pill shows "Today"
  // rather than an empty placeholder on first open.
  DateTime _from = DateTime.now();
  DateTime _to = DateTime.now();

  bool get _canEdit => PermissionService.instance.can(Perm.otAppointmentEdit);

  @override
  void initState() {
    super.initState();
    _load();
  }

  // Matches web's `optional($appointment->appointment_date)->format('d M Y')`
  // exactly — web's index table has no time column at all, only this date.
  static String _fmtAppointmentDate(String raw) {
    final d = DateTime.tryParse(raw);
    if (d == null) return raw;
    return toDisplayDate(d);
  }

  // Matches web's index Type badge: "OT" or Title-Case of phone/walk_in/online.
  static String _typeLabel(String type) => switch (type) {
    'phone' => 'Phone',
    'walk_in' => 'Walk-in',
    'online' => 'Online',
    'ot' => 'OT',
    _ => type,
  };

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      // perPage generous enough to cover a full result set in one page,
      // matching web's own "no server pagination" index.
      final result = await OtAppointmentService.instance.list(
        tab: _tab,
        fromDate: toIsoDate(_from),
        toDate: toIsoDate(_to),
        perPage: 200,
      );
      if (mounted)
        setState(() {
          _items = result.items;
          _bookedCount = result.bookedCount;
          _completedCount = result.completedCount;
          _loading = false;
        });
    } catch (e) {
      if (mounted)
        setState(() {
          _error = e.toString().replaceFirst('Exception: ', '');
          _loading = false;
        });
    }
  }

  void _openForm({OtAppointmentItem? item}) {
    Navigator.of(
      context,
    ).push(appRoute(OtAppointmentFormScreen(item: item, onSaved: _load)));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(
            Icons.arrow_back_ios_new_rounded,
            color: Colors.white,
            size: 20,
          ),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text(
          'OT Appointments',
          style: TextStyle(
            color: Colors.white,
            fontSize: 18,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.2,
          ),
        ),
      ),
      body: Column(
        children: [
          _buildFilters(),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  // Web pull 2026-10-07 — the 16-stage filter (a "Status: X ▾" pill opening
  // a bottom sheet) was replaced by a simple Booked/Completed tab pair,
  // mirroring web's own `.ot-tabs` pill row. New appointments are no longer
  // created from this screen at all (web dropped the header's "New
  // Appointment" button too) — booking now only happens from the
  // dashboard's "+ OT Appointment" quick action.
  Widget _buildFilters() {
    final sameDay =
        _from.year == _to.year && _from.month == _to.month && _from.day == _to.day;
    final isToday = sameDay && _only(_from) == _only(DateTime.now());
    final rangeLabel = isToday
        ? 'Today'
        : sameDay
            ? toDisplayDate(_from)
            : '${toDisplayDate(_from)} – ${toDisplayDate(_to)}';
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 4),
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(
          children: [
            _tabPill('Booked', _bookedCount, 'booked'),
            const SizedBox(width: 8),
            _tabPill('Completed', _completedCount, 'completed'),
            const SizedBox(width: 8),
            InkWell(
              onTap: _pickRange,
              borderRadius: BorderRadius.circular(AppRadius.full),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                decoration: BoxDecoration(
                  color: AppColors.teal.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(AppRadius.full),
                  border: Border.all(color: AppColors.teal.withValues(alpha: 0.25)),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.date_range_rounded, size: 15, color: AppColors.tealDark),
                    const SizedBox(width: 6),
                    Text(
                      rangeLabel,
                      style: TextStyle(
                        fontSize: 12.5,
                        fontWeight: FontWeight.w700,
                        color: AppColors.tealDark,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _tabPill(String label, int count, String tab) {
    final selected = _tab == tab;
    return InkWell(
      onTap: selected
          ? null
          : () {
              setState(() => _tab = tab);
              _load();
            },
      borderRadius: BorderRadius.circular(AppRadius.full),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: selected ? AppColors.primary : AppColors.primary.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(AppRadius.full),
          border: Border.all(
            color: AppColors.primary.withValues(alpha: selected ? 1 : 0.2),
          ),
        ),
        child: Text(
          '$label ($count)',
          style: TextStyle(
            fontSize: 12.5,
            fontWeight: FontWeight.w700,
            color: selected ? Colors.white : AppColors.primary,
          ),
        ),
      ),
    );
  }

  static DateTime _only(DateTime d) => DateTime(d.year, d.month, d.day);

  Future<void> _pickRange() async {
    final now = DateTime.now();
    final picked = await showAppDateRangePicker(
      context: context,
      firstDate: DateTime(now.year - 3),
      lastDate: now.add(const Duration(days: 365)),
      initialDateRange: DateTimeRange(start: _from, end: _to),
    );
    if (picked == null) return;
    setState(() {
      _from = picked.start;
      _to = picked.end;
    });
    _load();
  }

  Widget _buildBody() {
    if (_loading) return const AppSkeletonList();
    if (_error != null) return AppErrorState(message: _error!, onRetry: _load);
    if (_items.isEmpty)
      return AppEmptyState(
        message: 'No appointments found.',
        icon: Icons.event_note_rounded,
        onRefresh: _load,
      );

    return AppRefreshIndicator(
      color: AppColors.primary,
      onRefresh: _load,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 6, 14, 20),
        itemCount: _items.length,
        itemBuilder: (_, i) {
          final item = _items[i];
          // Web pull 2026-10-07 — Edit is the only row action now, and only
          // on the Booked tab (Completed has no Action column at all on
          // web). Walk-In and Delete were both dropped from this screen —
          // booking happens from the dashboard quick action now, and
          // there's no delete here any more either.
          final showEdit = _tab == 'booked' && _canEdit;
          return AnimatedListItem(
            index: i,
            // Migrated onto the shared AppRecordCard/AppRecordField
            // icon-badge grid — see RECORD_CARD_UNIFICATION_PLAN.md Tier 1
            // §3. Type/Date/Doctor were previously one plain flowing Row
            // with no per-field distinction; actions stay in their own
            // slot below, unchanged.
            child: Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: AppRecordCard(
                title: item.fullName,
                subtitle: '${item.appointmentNumber} · ${item.mobileNo}',
                trailing: Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    StatusBadge.otAppointmentStage(
                      item.stageBadgeClass,
                      item.stageLabel,
                    ),
                    if ((item.stageSub ?? '').isNotEmpty) ...[
                      const SizedBox(height: 3),
                      Text(
                        item.stageSub!,
                        style: TextStyle(
                          fontSize: 10,
                          color: AppColors.textSecondary,
                        ),
                      ),
                    ],
                  ],
                ),
                fields: [
                  // Web's "Type" column (Phone/Walk-in/Online/OT) — was
                  // loaded into the model but never actually shown.
                  AppRecordField(
                    'Type',
                    _typeLabel(item.appointmentType),
                    icon: Icons.label_outline_rounded,
                    color: RecordFieldColors.caseType,
                  ),
                  AppRecordField(
                    'Date',
                    _fmtAppointmentDate(item.appointmentDate),
                    icon: Icons.event_rounded,
                    color: RecordFieldColors.date,
                  ),
                  if (item.doctor != null)
                    AppRecordField(
                      'Doctor',
                      item.doctor!.name,
                      icon: Icons.medical_services_outlined,
                      color: RecordFieldColors.doctor,
                    ),
                ],
                actions: !showEdit
                    ? null
                    : Row(
                        mainAxisAlignment: MainAxisAlignment.end,
                        children: [
                          IconButton(
                            onPressed: () => _openForm(item: item),
                            icon: const Icon(
                              Icons.edit_outlined,
                              size: 17,
                              color: Color(0xFFE67E22),
                            ),
                            tooltip: 'Edit',
                            padding: EdgeInsets.zero,
                            constraints: const BoxConstraints.tightFor(
                              width: 34,
                              height: 34,
                            ),
                            style: IconButton.styleFrom(
                              backgroundColor: const Color(
                                0xFFE67E22,
                              ).withValues(alpha: 0.08),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(
                                  AppRadius.sm,
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
              ),
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

  const OtAppointmentFormScreen({
    super.key,
    required this.item,
    required this.onSaved,
  });

  @override
  State<OtAppointmentFormScreen> createState() =>
      _OtAppointmentFormScreenState();
}

class _OtAppointmentFormScreenState extends State<OtAppointmentFormScreen> {
  // Matches web's real 4 types exactly (_form.blade.php) — 'ot', not
  // 'referral', which never existed on web at all. See
  // OT_BUGS_ROUND4_AUDIT.md.
  static const _types = ['phone', 'walk_in', 'online', 'ot'];
  static const _typeLabels = {
    'phone': 'Phone',
    'walk_in': 'Walk-in',
    'online': 'Online',
    'ot': 'OT',
  };
  static const _genders = ['male', 'female', 'other'];

  late final TextEditingController _nameCtrl,
      _middleCtrl,
      _surnameCtrl,
      _mobileCtrl,
      _whatsappCtrl,
      _ageCtrl,
      _occupationCtrl;
  String _type = 'ot';
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
  String? _nameErr,
      _surnameErr,
      _mobileErr,
      _ageErr,
      _dateErr,
      _doctorErr,
      _locationErr;

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
    _type = it?.appointmentType ?? 'ot';
    _gender = it?.gender ?? 'male';
    _doctorId = it?.doctor?.id;
    _referrerId = it?.referrerId;
    _locationId = it?.location?.id;
    if (it != null && it.appointmentDate.isNotEmpty)
      _date = parseApiPlainDate(it.appointmentDate);
    // The API returns appointment_time with seconds ("14:30:00" — a MySQL
    // TIME column, no Eloquent cast), but slots compare on "HH:mm" only
    // (_slotTimeStr) — truncate here or the match below always silently
    // fails and the saved slot never shows as selected.
    final rawTime = it?.appointmentTime;
    _pendingSlotTime = (rawTime != null && rawTime.length >= 5)
        ? rawTime.substring(0, 5)
        : rawTime;
    _loadFormData();
  }

  @override
  void dispose() {
    for (final c in [
      _nameCtrl,
      _middleCtrl,
      _surnameCtrl,
      _mobileCtrl,
      _whatsappCtrl,
      _ageCtrl,
      _occupationCtrl,
    ]) {
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
            final match = data.slots
                .where((s) => _slotTimeStr(s) == _pendingSlotTime)
                .toList();
            if (match.isNotEmpty) _selectedSlot = match.first;
          }
        });
        if (_date != null && _selectedSlot != null) _refreshSlotConflicts();
      }
    } catch (e) {
      if (mounted)
        setState(() {
          _formError = e.toString().replaceFirst('Exception: ', '');
          _loadingForm = false;
        });
    }
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final picked = await showAppDatePicker(
      context: context,
      initialDate: _date ?? now,
      firstDate: widget.item == null
          ? now
          : now.subtract(const Duration(days: 365)),
      lastDate: now.add(const Duration(days: 365)),
      builder: (ctx, child) => Theme(
        data: ThemeData.light().copyWith(
          colorScheme: ColorScheme.light(primary: AppColors.primary),
        ),
        child: child!,
      ),
    );
    if (picked != null) {
      setState(() {
        _date = picked;
        _dateErr = null;
      });
      _refreshSlotConflicts();
    }
  }

  /// Raw `HH:mm` (matches web's `Carbon::parse($slot->start_time)->format('H:i')`
  /// stored value) derived from the slot's start time — `null`/short values
  /// return null so a slot with no configured start time is never selectable
  /// as a time filter.
  String? _slotTimeStr(OtSlotItem s) =>
      (s.startTime != null && s.startTime!.length >= 5)
      ? s.startTime!.substring(0, 5)
      : null;

  /// 12-hour display clock ("09:00 AM") matching web's `format('h:i A')`.
  String _fmtClock(String? raw) {
    if (raw == null || raw.length < 5) return '';
    final h = int.tryParse(raw.substring(0, 2)) ?? 0;
    final m = raw.substring(3, 5);
    final period = h >= 12 ? 'PM' : 'AM';
    final h12 = h % 12 == 0 ? 12 : h % 12;
    return '$h12:$m $period';
  }

  String _slotLabel(OtSlotItem s) => s.startTime != null && s.endTime != null
      ? '${s.slotName} (${_fmtClock(s.startTime)} - ${_fmtClock(s.endTime)})'
      : s.slotName;

  /// On Date + Time change, shows a chip list of patients already booked in
  /// that exact slot — mirrors web's AJAX slot-occupancy panel (hidden if
  /// none). See OT_WEB_PARITY_FIX_PRD.md §1.1.
  Future<void> _refreshSlotConflicts() async {
    final timeStr = _selectedSlot != null ? _slotTimeStr(_selectedSlot!) : null;
    if (_date == null || timeStr == null) {
      setState(() => _slotConflicts = []);
      return;
    }
    final dateStr = toIsoDate(_date!);
    setState(() => _loadingSlot = true);
    try {
      final conflicts = await OtAppointmentService.instance.slotAppointments(
        date: dateStr,
        time: timeStr,
        excludeId: widget.item?.id,
      );
      if (mounted)
        setState(() {
          _slotConflicts = conflicts;
          _loadingSlot = false;
        });
    } catch (_) {
      if (mounted)
        setState(() {
          _slotConflicts = [];
          _loadingSlot = false;
        });
    }
  }

  Future<void> _addCity() async {
    final cityCtrl = TextEditingController();
    final distCtrl = TextEditingController();
    final stateCtrl = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.lg),
        ),
        title: const Text('Add City'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: cityCtrl,
              decoration: const InputDecoration(
                labelText: 'City *',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: distCtrl,
              decoration: const InputDecoration(
                labelText: 'District',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: stateCtrl,
              decoration: const InputDecoration(
                labelText: 'State',
                border: OutlineInputBorder(),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
            ),
            child: const Text('Add'),
          ),
        ],
      ),
    );
    if (ok != true || cityCtrl.text.trim().isEmpty) return;
    try {
      final loc = await MastersService.instance.addLocation(
        city: cityCtrl.text.trim(),
        district: distCtrl.text.trim(),
        state: stateCtrl.text.trim(),
      );
      if (!mounted) return;
      setState(() {
        _formData = OtAppointmentFormData(
          doctors: _formData!.doctors,
          locations: [
            ..._formData!.locations,
            OtAppointmentLocation(
              id: loc.id,
              name: loc.city,
              district: loc.district,
              state: loc.state,
            ),
          ],
          referrers: _formData!.referrers,
          slots: _formData!.slots,
          nextAppointmentNumber: _formData!.nextAppointmentNumber,
        );
        _locationId = loc.id;
        _locationErr = null;
      });
      showAppSnackBar(context, 'City "${loc.city}" added.', isSuccess: true);
    } catch (e) {
      if (mounted)
        showAppSnackBar(
          context,
          e.toString().replaceFirst('Exception: ', ''),
          isError: true,
        );
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
    if ([
      _nameErr,
      _surnameErr,
      _mobileErr,
      _ageErr,
      _dateErr,
      _doctorErr,
      _locationErr,
    ].any((e) => e != null))
      return;

    setState(() => _saving = true);
    try {
      final body = {
        'appointment_type': _type,
        'appointment_date': toIsoDate(_date!),
        if (_selectedSlot != null)
          'appointment_time': _slotTimeStr(_selectedSlot!),
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
        showAppSnackBar(
          context,
          e.toString().replaceFirst('Exception: ', ''),
          isError: true,
        );
      }
    }
  }

  InputDecoration _deco(
    String label, {
    String? error,
    String? suffix,
    Widget? suffixIcon,
  }) => InputDecoration(
    labelText: label,
    errorText: error,
    suffixText: suffix,
    suffixIcon: suffixIcon,
    filled: true,
    fillColor: const Color(0xFFF0F6FB),
    border: OutlineInputBorder(
      borderRadius: BorderRadius.circular(AppRadius.md),
      borderSide: BorderSide.none,
    ),
    focusedBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(AppRadius.md),
      borderSide: BorderSide(color: AppColors.primary, width: 1.5),
    ),
  );

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFEBF5FB),
      body: Column(
        children: [
          _buildHeader(),
          Expanded(
            child: _loadingForm
                ? const AppSkeletonList()
                : _formError != null
                ? AppErrorState(
                    message: _formError!,
                    onRetry: () {
                      setState(() => _loadingForm = true);
                      _loadFormData();
                    },
                  )
                : _buildForm(),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader() {
    final isEdit = widget.item != null;
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [AppColors.primary, AppColors.blueLight],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(8, 10, 20, 14),
          child: Row(
            children: [
              IconButton(
                icon: const Icon(
                  Icons.arrow_back_ios_new_rounded,
                  color: Colors.white,
                  size: 20,
                ),
                onPressed: () => Navigator.pop(context),
              ),
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(
                  Icons.event_note_rounded,
                  color: Colors.white,
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      isEdit ? 'Edit Appointment' : 'New Appointment',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    Text(
                      isEdit
                          ? 'Update appointment details'
                          : 'Pre-registration — patient hasn\'t arrived yet',
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.75),
                        fontSize: 11,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  // Small caption above a field-less control (segmented chips have no
  // built-in label the way `_deco()`'s floating labelText gives every
  // TextFormField/Dropdown on this screen) — kept visually identical to
  // those so a chip group doesn't read as a different kind of control.
  Widget _chipLabel(String text) => Padding(
    padding: const EdgeInsets.only(bottom: 7, left: 2),
    child: Text(
      text,
      style: const TextStyle(
        fontSize: 12.5,
        fontWeight: FontWeight.w600,
        color: AppColors.textSecondary,
      ),
    ),
  );

  Widget _buildForm() {
    final formData = _formData!;
    final apptNumber =
        widget.item?.appointmentNumber ?? formData.nextAppointmentNumber;
    final selectedLoc = _locationId == null
        ? const <OtAppointmentLocation>[]
        : formData.locations.where((l) => l.id == _locationId).toList();
    final district = selectedLoc.isNotEmpty
        ? (selectedLoc.first.district ?? '—')
        : '—';
    final state = selectedLoc.isNotEmpty
        ? (selectedLoc.first.state ?? '—')
        : '—';

    // Field order below matches web's `_form.blade.php` exactly (client
    // requirement: same fields, same order, no more/no less) — Appointment
    // ID → Type → Date → Time → Contact/WhatsApp → First/Surname/Middle →
    // Doctor → City/District/State → Age/Gender → Occupation → Referred By.
    // Grouped into cards (not a flat run of fields) purely for visual
    // structure — card *boundaries* don't change that sequence.
    return Column(
      children: [
        Expanded(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 20),
            children: [
              AppFormCard(
                icon: Icons.event_note_rounded,
                title: 'Appointment Details',
                trailing: AppStatusPill(
                  label: widget.item != null ? 'Editing' : 'New Entry',
                  color: widget.item != null
                      ? AppColors.orange
                      : AppColors.green,
                ),
                children: [
                  AppReadOnlyField(
                    label: 'Appointment ID',
                    value: apptNumber,
                    icon: Icons.tag_rounded,
                    onCopy: () {
                      Clipboard.setData(ClipboardData(text: apptNumber));
                      showAppSnackBar(
                        context,
                        'Appointment ID copied',
                        isSuccess: true,
                      );
                    },
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _chipLabel('Appointment Type'),
                      AppSegmentedChips(
                        values: _types,
                        labels: _typeLabels,
                        selected: _type,
                        onChanged: (v) => setState(() => _type = v),
                      ),
                    ],
                  ),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: InkWell(
                          onTap: _pickDate,
                          borderRadius: BorderRadius.circular(AppRadius.md),
                          child: InputDecorator(
                            decoration: _deco('Date *', error: _dateErr),
                            child: Text(
                              _date == null ? 'Select' : toIsoDate(_date!),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: DropdownButtonFormField<OtSlotItem>(
                          initialValue: _selectedSlot,
                          isExpanded: true,
                          decoration: _deco('Time'),
                          items: formData.slots
                              .map(
                                (s) => DropdownMenuItem(
                                  value: s,
                                  child: Text(
                                    _slotLabel(s),
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              )
                              .toList(),
                          onChanged: (v) {
                            setState(() => _selectedSlot = v);
                            _refreshSlotConflicts();
                          },
                        ),
                      ),
                    ],
                  ),
                  if (_loadingSlot || _slotConflicts.isNotEmpty)
                    Align(
                      alignment: Alignment.centerLeft,
                      child: _loadingSlot
                          ? const SizedBox(
                              width: 14,
                              height: 14,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : Wrap(
                              spacing: 6,
                              runSpacing: 6,
                              children: [
                                const Padding(
                                  padding: EdgeInsets.only(right: 2),
                                  child: Text(
                                    'Already booked:',
                                    style: TextStyle(
                                      fontSize: 11,
                                      color: AppColors.textSecondary,
                                    ),
                                  ),
                                ),
                                ..._slotConflicts.map(
                                  (c) => Container(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 8,
                                      vertical: 3,
                                    ),
                                    decoration: BoxDecoration(
                                      color: AppColors.orangeA12,
                                      borderRadius: BorderRadius.circular(
                                        AppRadius.full,
                                      ),
                                    ),
                                    child: Text(
                                      c.name,
                                      style: const TextStyle(
                                        fontSize: 11,
                                        fontWeight: FontWeight.w700,
                                        color: AppColors.orange,
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                    ),
                ],
              ),
              AppFormCard(
                icon: Icons.person_outline_rounded,
                title: 'Contact & Patient Details',
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: TextFormField(
                          controller: _mobileCtrl,
                          keyboardType: TextInputType.phone,
                          inputFormatters: [
                            FilteringTextInputFormatter.digitsOnly,
                          ],
                          decoration: _deco('Mobile No *', error: _mobileErr),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: TextFormField(
                          controller: _whatsappCtrl,
                          keyboardType: TextInputType.phone,
                          inputFormatters: [
                            FilteringTextInputFormatter.digitsOnly,
                          ],
                          decoration: _deco(
                            'WhatsApp No',
                            suffixIcon: TextButton(
                              onPressed: () => setState(
                                () => _whatsappCtrl.text = _mobileCtrl.text,
                              ),
                              style: TextButton.styleFrom(
                                minimumSize: Size.zero,
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                ),
                                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                              ),
                              child: const Text(
                                'Same',
                                style: TextStyle(
                                  fontSize: 11.5,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                  TextFormField(
                    controller: _nameCtrl,
                    textCapitalization: TextCapitalization.words,
                    decoration: _deco('First Name *', error: _nameErr),
                  ),
                  TextFormField(
                    controller: _surnameCtrl,
                    textCapitalization: TextCapitalization.words,
                    decoration: _deco('Surname *', error: _surnameErr),
                  ),
                  TextFormField(
                    controller: _middleCtrl,
                    textCapitalization: TextCapitalization.words,
                    decoration: _deco('Middle Name'),
                  ),
                ],
              ),
              AppFormCard(
                icon: Icons.medical_services_outlined,
                title: 'Doctor & Location',
                children: [
                  DropdownButtonFormField<int>(
                    initialValue: _doctorId,
                    isExpanded: true,
                    decoration: _deco('Doctor *', error: _doctorErr),
                    items: formData.doctors
                        .map(
                          (d) => DropdownMenuItem(
                            value: d.id,
                            child: Text(
                              d.name,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        )
                        .toList(),
                    onChanged: (v) => setState(() {
                      _doctorId = v;
                      _doctorErr = null;
                    }),
                  ),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: DropdownButtonFormField<int>(
                          initialValue: _locationId,
                          isExpanded: true,
                          decoration: _deco('City *', error: _locationErr),
                          items: formData.locations
                              .map(
                                (l) => DropdownMenuItem(
                                  value: l.id,
                                  child: Text(
                                    l.name,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              )
                              .toList(),
                          onChanged: (v) => setState(() {
                            _locationId = v;
                            _locationErr = null;
                          }),
                        ),
                      ),
                      IconButton(
                        icon: Icon(
                          Icons.add_circle_outline_rounded,
                          color: AppColors.primary,
                        ),
                        tooltip: 'Add City',
                        onPressed: _addCity,
                      ),
                    ],
                  ),
                  // Web always renders District/State (empty/readonly until a
                  // city is chosen) rather than hiding them — matched here.
                  Row(
                    children: [
                      Expanded(
                        child: AppReadOnlyField(
                          label: 'District',
                          value: district,
                          icon: Icons.map_outlined,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: AppReadOnlyField(
                          label: 'State',
                          value: state,
                          icon: Icons.flag_outlined,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              AppFormCard(
                icon: Icons.notes_rounded,
                title: 'Additional Details',
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: TextFormField(
                          controller: _ageCtrl,
                          keyboardType: TextInputType.number,
                          inputFormatters: [
                            FilteringTextInputFormatter.digitsOnly,
                          ],
                          decoration: _deco('Age *', error: _ageErr),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        flex: 2,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            _chipLabel('Gender *'),
                            AppSegmentedChips(
                              values: _genders,
                              labels: const {
                                'male': 'Male',
                                'female': 'Female',
                                'other': 'Other',
                              },
                              selected: _gender,
                              onChanged: (v) => setState(() => _gender = v),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  TextFormField(
                    controller: _occupationCtrl,
                    decoration: _deco('Occupation'),
                  ),
                  DropdownButtonFormField<int?>(
                    initialValue: _referrerId,
                    isExpanded: true,
                    decoration: _deco('Referrer'),
                    items: [
                      const DropdownMenuItem(value: null, child: Text('None')),
                      ...formData.referrers.map(
                        (r) => DropdownMenuItem(
                          value: r.id,
                          child: Text(r.name, overflow: TextOverflow.ellipsis),
                        ),
                      ),
                    ],
                    onChanged: (v) => setState(() => _referrerId = v),
                  ),
                ],
              ),
            ],
          ),
        ),
        SafeArea(
          top: false,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
            child: Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: _saving ? null : () => Navigator.pop(context),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(AppRadius.md),
                      ),
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
                        borderRadius: BorderRadius.circular(AppRadius.md),
                      ),
                    ),
                    child: _saving
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(
                              color: Colors.white,
                              strokeWidth: 2,
                            ),
                          )
                        : const Text(
                            'Save',
                            style: TextStyle(fontWeight: FontWeight.w700),
                          ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}
