import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../models/auth_models.dart';
import '../models/ot_appointment_models.dart';
import '../models/patient_models.dart';
import '../services/base_service.dart' show StaleRecordException;
import '../services/masters_service.dart';
import '../services/ot_appointment_service.dart';
import '../services/patient_service.dart';
import '../constants/app_colors.dart';
import '../utils/phone_rules.dart';
import '../constants/app_radius.dart';
import '../utils/date_format.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_record_card.dart';
import '../widgets/app_text_field.dart';
import '../widgets/skeleton.dart';
import '../utils/app_date_picker.dart';

enum PatientFormMode { addWalkIn, addPhone, edit }

class PatientFormScreen extends StatefulWidget {
  final PatientFormMode mode;
  final Patient? patient;
  final UserInfo user;
  final HospitalInfo hospital;
  // Light client-side prefill when opening "Walk-In" from an OT appointment
  // row (receptionist "Today Added Patients" widget) — name/contact/age/
  // gender only, no backend link to the OT appointment (that's the separate,
  // optional TASK 5.3). See WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §9.
  final OtAppointmentItem? prefillOt;

  const PatientFormScreen({
    super.key,
    required this.mode,
    this.patient,
    required this.user,
    required this.hospital,
    this.prefillOt,
  });

  @override
  State<PatientFormScreen> createState() => _PatientFormScreenState();
}

class _PatientFormScreenState extends State<PatientFormScreen>
    with SingleTickerProviderStateMixin {
  final _formKey = GlobalKey<FormState>();

  // Controllers
  final _mrdCtrl = TextEditingController();
  final _contactCtrl = TextEditingController();
  final _whatsappCtrl = TextEditingController();
  final _firstCtrl = TextEditingController();
  final _lastCtrl = TextEditingController();
  final _middleCtrl = TextEditingController();
  final _caseFeeCtrl = TextEditingController();
  final _ageCtrl = TextEditingController();
  final _occupationCtrl = TextEditingController();
  final _districtCtrl = TextEditingController();
  final _stateCtrl = TextEditingController();
  final _apptDateCtrl = TextEditingController();

  // Dropdown values
  int? _selectedCaseId;
  int? _selectedDoctorId;
  int? _selectedLocationId;
  int? _selectedSlotId;
  int? _selectedReferrerId;
  String _selectedGender = 'male';

  // Masters data
  MastersData? _masters;
  bool _mastersLoading = true;
  String? _mastersError;

  // Contact suggestion
  List<ContactSuggestion> _suggestions = [];
  Timer? _contactDebounce;
  bool _showSuggestions = false;
  bool _fillingSuggestion = false;

  // ── OT check-in search (walk-in only) — web parity: `create.blade.php`'s
  // optional "OT check-in search" panel lets reception pick a same-day,
  // not-yet-checked-in OT appointment to pre-fill this form from and, on
  // submit, mark that appointment completed + linked to the new patient
  // (see PatientApiController::store()'s `ot_appointment_id` handling,
  // already wired — only this search UI was missing).
  final _otSearchCtrl = TextEditingController();
  Timer? _otSearchDebounce;
  List<OtAppointmentItem> _otSearchResults = [];
  bool _otSearchExpanded = false;
  bool _otSearching = false;
  int? _linkedOtAppointmentId;
  String? _linkedOtAppointmentNumber;

  // Form state
  bool _submitting = false;
  bool _submitSuccess = false;
  bool _examDoneWarning = false;
  bool _isOldPatient = false;

  // Warning banner dismiss
  bool _warnDismissed = false;

  late final AnimationController _formEnter;
  late final CurvedAnimation _formEnterAnim;

  DateTime _appointmentDate = DateTime.now();

  // Web parity (`patients/edit-phone.blade.php`, web pull 2026-10-05) — a
  // phone-registered patient who hasn't checked in yet (`type == 'phone' &&
  // case_id == null`) edits with the slimmer phone-style form (Slot instead
  // of Case Type/Fee), not the full walk-in edit form. Previously `isPhone`
  // was ONLY true for the addPhone *creation* mode, never during edit — so
  // editing a still-pending phone patient always opened the full walk-in
  // form, and saving it unconditionally sent `case_id`/`case_fee`, silently
  // converting the phone booking into a walk-in on save.
  bool get _isPhoneEdit =>
      widget.mode == PatientFormMode.edit &&
      widget.patient?.type == 'phone' &&
      widget.patient?.caseId == null;

  bool get _isPhoneForm =>
      widget.mode == PatientFormMode.addPhone || _isPhoneEdit;

  @override
  void initState() {
    super.initState();
    _formEnter = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 320),
    );
    _formEnterAnim = CurvedAnimation(parent: _formEnter, curve: Curves.easeOut);
    _initDate();
    _loadMasters();
    if (widget.mode == PatientFormMode.edit && widget.patient != null) {
      _prefillFromPatient(widget.patient!);
    } else if (widget.mode == PatientFormMode.addWalkIn &&
        widget.prefillOt != null) {
      _prefillFromOt(widget.prefillOt!);
    }
  }

  void _initDate() {
    if (widget.mode == PatientFormMode.addPhone) {
      _appointmentDate = DateTime.now().add(const Duration(days: 1));
    } else if (widget.mode == PatientFormMode.edit &&
        widget.patient?.appointmentDate != null) {
      _appointmentDate =
          parseApiPlainDate(widget.patient!.appointmentDate!) ?? DateTime.now();
    }
    _apptDateCtrl.text = _formatDate(_appointmentDate);
  }

  void _prefillFromPatient(Patient p) {
    _mrdCtrl.text = p.patientCode;
    _contactCtrl.text = p.contactNo ?? '';
    _whatsappCtrl.text = p.whatsappNo ?? '';
    _firstCtrl.text = p.firstName;
    _lastCtrl.text = p.lastName;
    _middleCtrl.text = p.middleName;
    _caseFeeCtrl.text = p.caseFee?.toInt().toString() ?? '';
    _ageCtrl.text = p.age?.toString() ?? '';
    _occupationCtrl.text = p.occupation ?? '';
    _selectedGender = p.gender ?? 'male';
    _selectedCaseId = p.caseId;
    _selectedSlotId = p.slotId;
    _selectedDoctorId = p.doctor?.id;
    _selectedLocationId = p.location?.id;
    _districtCtrl.text = p.location?.district ?? '';
    _stateCtrl.text = p.location?.state ?? '';
    _selectedReferrerId = p.referrer?.id;
    _isOldPatient = p.isOldPatient;
    _examDoneWarning = p.primaryDoneAt != null;
  }

  void _prefillFromOt(OtAppointmentItem o) {
    _firstCtrl.text = o.patientName;
    _middleCtrl.text = o.middleName ?? '';
    _lastCtrl.text = o.surname;
    _contactCtrl.text = o.mobileNo;
    _whatsappCtrl.text = o.whatsappNo ?? '';
    _ageCtrl.text = o.age > 0 ? o.age.toString() : '';
    _selectedGender = o.gender.toLowerCase() == 'female' ? 'female' : 'male';
    _occupationCtrl.text = o.occupation ?? '';
    // Doctor/City were never read here before (comment above used to say
    // "name/contact/age/gender only") — District/State can't be resolved
    // yet since `_masters` hasn't finished loading at this point (this runs
    // synchronously in initState, before `_loadMasters()`'s awaited fetch
    // returns); `_loadMasters()` resolves them once the location list is
    // actually available. The dropdowns themselves don't have this problem
    // — they aren't built until masters finishes loading, so setting the
    // id here is enough for them to show the right selection on first build.
    _selectedDoctorId = o.doctor?.id;
    _selectedLocationId = o.location?.id;
    _selectedReferrerId = o.referrerId;
    _linkedOtAppointmentId = o.id;
    _linkedOtAppointmentNumber = o.appointmentNumber;
    _otSearchCtrl.text = '${o.appointmentNumber} — ${o.fullName}';
    _otSearchExpanded = true;
  }

  // ── OT check-in search ───────────────────────────────────────────────────

  void _onOtSearchChanged(String val) {
    _otSearchDebounce?.cancel();
    if (val.trim().length < 3) {
      setState(() => _otSearchResults = []);
      return;
    }
    _otSearchDebounce = Timer(const Duration(milliseconds: 300), () async {
      setState(() => _otSearching = true);
      try {
        final result = await OtAppointmentService.instance.search(val.trim());
        if (mounted)
          setState(() {
            _otSearchResults = result.appointments;
            _otSearching = false;
          });
      } catch (_) {
        if (mounted)
          setState(() {
            _otSearchResults = [];
            _otSearching = false;
          });
      }
    });
  }

  void _selectOtAppointment(OtAppointmentItem o) {
    setState(() {
      _firstCtrl.text = o.patientName;
      _lastCtrl.text = o.surname;
      _middleCtrl.text = o.middleName ?? '';
      _whatsappCtrl.text = o.whatsappNo ?? '';
      _ageCtrl.text = o.age > 0 ? o.age.toString() : '';
      _occupationCtrl.text = o.occupation ?? '';
      _contactCtrl.text = o.mobileNo;
      _selectedGender = o.gender.toLowerCase() == 'female' ? 'female' : 'male';
      if (o.doctor != null) _selectedDoctorId = o.doctor!.id;
      if (o.referrerId != null) _selectedReferrerId = o.referrerId;
      if (o.location != null) {
        final loc = _masters?.locations
            .where((l) => l.id == o.location!.id)
            .firstOrNull;
        if (loc != null) {
          _selectedLocationId = loc.id;
          _districtCtrl.text = loc.district;
          _stateCtrl.text = loc.state;
        }
      }
      _linkedOtAppointmentId = o.id;
      _linkedOtAppointmentNumber = o.appointmentNumber;
      _otSearchCtrl.text = '${o.appointmentNumber} — ${o.fullName}';
      _otSearchResults = [];
    });
  }

  void _clearOtLink() {
    setState(() {
      _linkedOtAppointmentId = null;
      _linkedOtAppointmentNumber = null;
      _otSearchCtrl.clear();
      _otSearchResults = [];
    });
  }

  Future<void> _loadMasters() async {
    setState(() {
      _mastersLoading = true;
      _mastersError = null;
    });
    try {
      final masters = await MastersService.instance.fetchAll();
      if (!mounted) return;
      setState(() {
        _masters = masters;
        // Resolves District/State for the OT-dashboard-widget prefill flow
        // (_prefillFromOt already set _selectedLocationId, but couldn't look
        // up the district/state text since this list wasn't loaded yet).
        if (widget.mode == PatientFormMode.addWalkIn &&
            _selectedLocationId != null) {
          final loc = masters.locations
              .where((l) => l.id == _selectedLocationId)
              .firstOrNull;
          if (loc != null) {
            _districtCtrl.text = loc.district;
            _stateCtrl.text = loc.state;
          }
        }
      });

      // Fetch next MRD for add modes
      if (widget.mode != PatientFormMode.edit) {
        final mrd = await PatientService.instance.fetchNextMrd();
        if (mounted) _mrdCtrl.text = mrd;
      }
    } catch (e) {
      if (mounted) setState(() => _mastersError = e.toString());
    } finally {
      if (mounted) {
        setState(() => _mastersLoading = false);
        _formEnter.forward();
      }
    }
  }

  @override
  void dispose() {
    _mrdCtrl.dispose();
    _contactCtrl.dispose();
    _whatsappCtrl.dispose();
    _firstCtrl.dispose();
    _lastCtrl.dispose();
    _middleCtrl.dispose();
    _caseFeeCtrl.dispose();
    _ageCtrl.dispose();
    _occupationCtrl.dispose();
    _districtCtrl.dispose();
    _stateCtrl.dispose();
    _apptDateCtrl.dispose();
    _contactDebounce?.cancel();
    _otSearchCtrl.dispose();
    _otSearchDebounce?.cancel();
    _formEnter.dispose();
    super.dispose();
  }

  // ── Contact smart search ────────────────────────────────────────────────────

  // Web parity: the past-patient suggestion lookup is keyed off the Contact
  // Number only, not the Name — typing a new patient's name must never
  // surface someone else's record by name match.
  void _onLookupChanged(String val) {
    if (_fillingSuggestion) return;
    _contactDebounce?.cancel();
    final q = val.trim();
    if (q.length < 3) {
      setState(() {
        _suggestions = [];
        _showSuggestions = false;
      });
      return;
    }
    _contactDebounce = Timer(const Duration(milliseconds: 500), () async {
      try {
        final results = await PatientService.instance.searchByContact(q);
        if (mounted) {
          setState(() {
            _suggestions = results;
            _showSuggestions = results.isNotEmpty;
          });
        }
      } catch (_) {}
    });
  }

  void _fillFromSuggestion(ContactSuggestion s) {
    _fillingSuggestion = true;
    setState(() {
      _firstCtrl.text = s.firstName;
      _lastCtrl.text = s.lastName;
      _middleCtrl.text = s.middleName;
      _ageCtrl.text = s.age?.toString() ?? '';
      _occupationCtrl.text = s.occupation ?? '';
      if (s.whatsappNo != null) _whatsappCtrl.text = s.whatsappNo!;
      if (s.contactNo != null && s.contactNo!.isNotEmpty)
        _contactCtrl.text = s.contactNo!;
      if (s.gender != null) _selectedGender = s.gender!;
      if (s.locationId != null) {
        final loc = _masters?.locations
            .where((l) => l.id == s.locationId)
            .firstOrNull;
        if (loc != null) {
          _selectedLocationId = s.locationId;
          _districtCtrl.text = loc.district;
          _stateCtrl.text = loc.state;
        }
      }
      _isOldPatient = true;
      _suggestions = [];
      _showSuggestions = false;
    });
    _fillingSuggestion = false;

    if (s.type == 'shared') {
      showAppSnackBar(
        context,
        'Shared patient selected — please verify city/location.',
      );
    }
  }

  // ── Date picker ─────────────────────────────────────────────────────────────

  Future<void> _pickDate() async {
    final picked = await showAppDatePicker(
      context: context,
      initialDate: _appointmentDate,
      firstDate: widget.mode == PatientFormMode.edit
          ? DateTime(2020)
          : DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      builder: (ctx, child) => Theme(
        data: Theme.of(
          ctx,
        ).copyWith(colorScheme: ColorScheme.light(primary: AppColors.primary)),
        child: child!,
      ),
    );
    if (picked != null) {
      setState(() {
        _appointmentDate = picked;
        _apptDateCtrl.text = _formatDate(picked);
      });
    }
  }

  // ── Add City dialog ─────────────────────────────────────────────────────────

  Future<void> _addCity() async {
    final cityCtrl = TextEditingController();
    final distCtrl = TextEditingController();
    final stateCtrl = TextEditingController();

    final result = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.lg),
        ),
        titlePadding: EdgeInsets.zero,
        title: Container(
          padding: const EdgeInsets.fromLTRB(20, 18, 16, 14),
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: [AppColors.primary, AppColors.blueLight],
              begin: Alignment.centerLeft,
              end: Alignment.centerRight,
            ),
            borderRadius: BorderRadius.vertical(
              top: Radius.circular(AppRadius.lg),
            ),
          ),
          child: Row(
            children: [
              const Icon(
                Icons.add_location_alt_outlined,
                color: Colors.white,
                size: 20,
              ),
              const SizedBox(width: 8),
              const Expanded(
                child: Text(
                  'Add City',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              IconButton(
                icon: const Icon(Icons.close, color: Colors.white, size: 18),
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
                onPressed: () => Navigator.pop(ctx, false),
              ),
            ],
          ),
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(height: 8),
            _dialogField(cityCtrl, 'City *'),
            const SizedBox(height: 12),
            _dialogField(distCtrl, 'District'),
            const SizedBox(height: 12),
            _dialogField(stateCtrl, 'State'),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary),
            child: const Text('Add', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (result != true) return;
    if (cityCtrl.text.trim().isEmpty) return;

    try {
      final loc = await MastersService.instance.addLocation(
        city: cityCtrl.text.trim(),
        district: distCtrl.text.trim(),
        state: stateCtrl.text.trim(),
      );
      if (mounted) {
        setState(() {
          _masters = MastersData(
            cases: _masters!.cases,
            doctors: _masters!.doctors,
            locations: [..._masters!.locations, loc],
            slots: _masters!.slots,
            referrers: _masters!.referrers,
          );
          _selectedLocationId = loc.id;
          _districtCtrl.text = loc.district;
          _stateCtrl.text = loc.state;
        });
        showAppSnackBar(context, 'City "${loc.city}" added.', isSuccess: true);
      }
    } catch (e) {
      if (mounted) {
        showAppSnackBar(context, e.toString(), isError: true);
      }
    }
  }

  Widget _dialogField(TextEditingController ctrl, String label) {
    return TextField(
      controller: ctrl,
      decoration: InputDecoration(
        labelText: label,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: AppColors.primary, width: 1.5),
        ),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 12,
          vertical: 10,
        ),
      ),
    );
  }

  // ── Submit ──────────────────────────────────────────────────────────────────

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    if (_submitting) return;

    setState(() {
      _submitting = true;
      _submitSuccess = false;
    });

    try {
      final isPhone = _isPhoneForm;
      final isEdit = widget.mode == PatientFormMode.edit;

      final data = <String, dynamic>{
        'appointment_date': _formatDate(_appointmentDate, iso: true),
        'contact_no': _contactCtrl.text.trim(),
        'whatsapp_no': _whatsappCtrl.text.trim().isEmpty
            ? null
            : _whatsappCtrl.text.trim(),
        'first_name': _firstCtrl.text.trim(),
        'last_name': _lastCtrl.text.trim(),
        'middle_name': _middleCtrl.text.trim(),
        'doctor_id': _selectedDoctorId,
        'location_id': _selectedLocationId,
        'age': int.tryParse(_ageCtrl.text.trim()),
        'gender': _selectedGender,
        'occupation': _occupationCtrl.text.trim().isEmpty
            ? null
            : _occupationCtrl.text.trim(),
        'referrer_id': _selectedReferrerId,
        'is_old_patient': _isOldPatient,
      };

      if (!isPhone) {
        data['case_id'] = _selectedCaseId;
        data['case_fee'] = double.tryParse(_caseFeeCtrl.text.trim()) ?? 0;
      } else {
        data['slot_id'] = _selectedSlotId;
      }

      if (widget.mode == PatientFormMode.addWalkIn &&
          _linkedOtAppointmentId != null) {
        data['ot_appointment_id'] = _linkedOtAppointmentId;
      }

      if (isEdit) {
        // case_fee/case_id are editable in edit — but NEVER for a still-
        // pending phone patient (no case assigned yet): sending them would
        // silently convert the phone booking into a walk-in on save, since
        // `case_id`/`case_fee` being set is exactly what distinguishes a
        // checked-in patient from a pending phone appointment server-side.
        if (!isPhone) {
          data['case_fee'] = double.tryParse(_caseFeeCtrl.text.trim()) ?? 0;
          data['case_id'] = _selectedCaseId;
        }
        // Optimistic-concurrency guard — lets the backend detect if this
        // record was edited elsewhere (web/another platform) since this
        // form loaded it. See ACCESS_CONTROL_AND_DATA_SYNC_PLAN.md Phase 5.
        if (widget.patient!.updatedAt != null) {
          data['expected_updated_at'] = widget.patient!.updatedAt!
              .toIso8601String();
        }
        await PatientService.instance.updatePatient(widget.patient!.id, data);
      } else if (isPhone) {
        await PatientService.instance.registerPhone(data);
      } else {
        await PatientService.instance.registerWalkIn(data);
      }

      if (mounted) {
        setState(() {
          _submitting = false;
          _submitSuccess = true;
        });
        await Future.delayed(const Duration(milliseconds: 700));
        if (mounted) Navigator.of(context).pop(true);
      }
    } on StaleRecordException catch (e) {
      if (mounted) {
        setState(() => _submitting = false);
        await showStaleRecordDialog(
          context,
          e.humanMessage,
          () => Navigator.of(context).pop(true),
        );
      }
      return;
    } catch (e) {
      if (mounted) {
        showAppSnackBar(
          context,
          e.toString(),
          isError: true,
          duration: const Duration(seconds: 4),
        );
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  // ── Build ───────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFEBF5FB),
      body: Column(
        children: [
          _buildAppBar(),
          Expanded(
            child: _mastersLoading
                ? const AppSkeletonList()
                : _mastersError != null
                ? _buildMastersError()
                : FadeTransition(
                    opacity: _formEnterAnim,
                    child: SlideTransition(
                      position: Tween<Offset>(
                        begin: const Offset(0, 0.03),
                        end: Offset.zero,
                      ).animate(_formEnterAnim),
                      child: _buildForm(),
                    ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildAppBar() {
    final (title, subtitle, icon) = switch (widget.mode) {
      PatientFormMode.addWalkIn => (
        'Register New Patient',
        'Capture patient information and appointment details',
        Icons.person_add_rounded,
      ),
      PatientFormMode.addPhone => (
        'Register Phone Appointment',
        'Capture caller details and appointment information',
        Icons.phone_in_talk_rounded,
      ),
      // Web parity (`patients/edit-phone.blade.php`) — a still-pending
      // phone patient edits under its own title, matching web exactly.
      PatientFormMode.edit when _isPhoneEdit => (
        'Edit Phone Appointment',
        'Update caller details and appointment information',
        Icons.phone_in_talk_rounded,
      ),
      PatientFormMode.edit => (
        'Edit Patient',
        'Update patient information and appointment details',
        Icons.edit_rounded,
      ),
    };

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
          padding: const EdgeInsets.fromLTRB(8, 8, 16, 16),
          child: Row(
            children: [
              IconButton(
                icon: const Icon(Icons.arrow_back_rounded, color: Colors.white),
                onPressed: () => Navigator.of(context).pop(),
              ),
              const SizedBox(width: 4),
              // Icon circle
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(AppRadius.md),
                ),
                child: Icon(icon, color: Colors.white, size: 22),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      subtitle,
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

  Widget _buildMastersError() {
    return AppErrorState(
      message: _mastersError ?? 'Failed to load form data.',
      onRetry: _loadMasters,
    );
  }

  // Client request (2026-10-04): redesigned onto the same card/chip language
  // as the OT Appointment form (see `AppFormCard`/`AppSegmentedChips` in
  // `widgets/app_text_field.dart`). While rebuilding this, cross-checked
  // every field's position against the 3 web blades this screen covers
  // (`patients/create.blade.php` walk-in, `create-phone.blade.php` phone,
  // `edit.blade.php` edit) and found this screen's field ORDER had quietly
  // drifted from web in three places — fixed here, not just restyled:
  //  1. Doctor sat BEFORE City/District/State; web's row always ends City →
  //     District → State → Doctor (Doctor last, not first).
  //  2. MRD No. showed in phone mode too; web's phone create form has no
  //     MRD field at all (a phone pre-registration has no patient_code yet
  //     — the "next MRD" preview shown here was never going to be the
  //     number actually assigned once this patient later checks in).
  //  3. Time Slot sat AFTER Age/Gender/Occupation; web's phone row 3 starts
  //     with Slot Time, before Age.
  Widget _buildForm() {
    final isPhone = _isPhoneForm;
    final isEdit = widget.mode == PatientFormMode.edit;

    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 100),
        children: [
          // ── Warning banner for edit (exam done) ─────────────────────
          if (isEdit && _examDoneWarning && !_warnDismissed) _buildWarnBanner(),

          // ── OT check-in search (walk-in only) ────────────────────────
          if (widget.mode == PatientFormMode.addWalkIn)
            _buildOtCheckinSection(),

          AppFormCard(
            icon: Icons.assignment_ind_outlined,
            title: 'Appointment Details',
            trailing: AppStatusPill(
              label: isEdit ? 'Editing' : 'New Entry',
              color: isEdit ? AppColors.orange : AppColors.green,
            ),
            children: [
              // Web has no MRD field on the phone pre-registration (create)
              // form — a not-yet-created phone booking has no patient_code
              // yet. Editing a phone patient DOES show it, read-only
              // (`edit-phone.blade.php`) — the patient already has a code.
              if (isEdit || !isPhone)
                _readonlyField(_mrdCtrl, 'MRD No.', Icons.badge_outlined),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [_sectionLabel('Appointment Date *'), _datefield()],
              ),
            ],
          ),

          AppFormCard(
            icon: Icons.person_outline_rounded,
            title: 'Contact & Patient Details',
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _sectionLabel('Contact Number *'),
                  _contactField(),
                  if (_showSuggestions) _suggestionDropdown(),
                ],
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _sectionLabel('WhatsApp Number'),
                  _textField(
                    _whatsappCtrl,
                    'WhatsApp No (same if blank)',
                    inputType: TextInputType.phone,
                    maxLength: 16,
                    validator: PhoneRules.nullable,
                    suffixIcon: TextButton(
                      onPressed: () => setState(
                        () => _whatsappCtrl.text = _contactCtrl.text,
                      ),
                      style: TextButton.styleFrom(
                        minimumSize: Size.zero,
                        padding: const EdgeInsets.symmetric(horizontal: 8),
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
                ],
              ),
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _sectionLabel('First Name *'),
                        _textField(_firstCtrl, 'First Name', required: true),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _sectionLabel('Surname *'),
                        _textField(_lastCtrl, 'Surname', required: true),
                      ],
                    ),
                  ),
                ],
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _sectionLabel('Middle Name'),
                  _textField(_middleCtrl, 'Middle Name'),
                ],
              ),
            ],
          ),

          AppFormCard(
            icon: Icons.medical_services_outlined,
            title: 'Case, Doctor & Location',
            children: [
              // ── Case Type + Fee (walk-in and edit only) ──────────────
              if (!isPhone) ...[
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [_sectionLabel('Case Type *'), _caseTypeDropdown()],
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _sectionLabel(
                      'Case Fee (${widget.hospital.currencySymbol}) *',
                    ),
                    _textField(
                      _caseFeeCtrl,
                      'Case Fee',
                      inputType: TextInputType.number,
                      required: true,
                      readOnly: widget.mode == PatientFormMode.addWalkIn,
                      hintOverride: widget.mode == PatientFormMode.addWalkIn
                          ? 'Auto-filled from Case Type'
                          : null,
                    ),
                  ],
                ),
              ],
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          'City *',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: AppColors.primary,
                          ),
                        ),
                      ),
                      TextButton.icon(
                        onPressed: _addCity,
                        icon: Icon(
                          Icons.add_rounded,
                          size: 14,
                          color: AppColors.primary,
                        ),
                        label: Text(
                          'Add',
                          style: TextStyle(
                            fontSize: 12,
                            color: AppColors.primary,
                          ),
                        ),
                        style: TextButton.styleFrom(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 2,
                          ),
                          minimumSize: Size.zero,
                        ),
                      ),
                    ],
                  ),
                  _locationDropdown(),
                ],
              ),
              Row(
                children: [
                  Expanded(
                    child: AppReadOnlyField(
                      label: 'District',
                      value: _districtCtrl.text,
                      icon: Icons.location_city_outlined,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: AppReadOnlyField(
                      label: 'State',
                      value: _stateCtrl.text,
                      icon: Icons.map_outlined,
                    ),
                  ),
                ],
              ),
              // ── Doctor — web parity: comes AFTER City/District/State,
              // last in that row, not before it. ─────────────────────────
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [_sectionLabel('Doctor Name *'), _doctorDropdown()],
              ),
              // ── Time Slot (phone only) — web parity: first field of the
              // next row, right before Age, not after Occupation. ────────
              if (isPhone)
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [_sectionLabel('Time Slot'), _slotDropdown()],
                ),
            ],
          ),

          AppFormCard(
            icon: Icons.notes_rounded,
            title: 'Additional Details',
            children: [
              Row(
                children: [
                  Expanded(
                    flex: 1,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _sectionLabel('Age *'),
                        _textField(
                          _ageCtrl,
                          'Age',
                          inputType: TextInputType.number,
                          required: true,
                          maxLength: 3,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    flex: 2,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _sectionLabel('Gender *'),
                        AppSegmentedChips(
                          values: const ['male', 'female', 'other'],
                          labels: const {
                            'male': 'Male',
                            'female': 'Female',
                            'other': 'Other',
                          },
                          selected: _selectedGender,
                          onChanged: (v) => setState(() => _selectedGender = v),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _sectionLabel('Occupation'),
                  _textField(_occupationCtrl, 'Occupation'),
                ],
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [_sectionLabel('Referred By'), _referrerDropdown()],
              ),
            ],
          ),

          const SizedBox(height: 8),

          // ── Submit ───────────────────────────────────────────────────
          _submitButton(),
        ],
      ),
    );
  }

  // ── Form field helpers ──────────────────────────────────────────────────────

  Widget _sectionLabel(String text) {
    return Padding(
      padding: const EdgeInsets.only(top: 16, bottom: 6, left: 2),
      child: Text(
        text,
        style: TextStyle(
          fontSize: 12.5,
          fontWeight: FontWeight.w700,
          color: AppColors.primary,
        ),
      ),
    );
  }

  // Shared by every field/dropdown on this screen (_textField, _readonlyField,
  // _datefield, _contactField and the dropdown helpers all decorate through
  // this one function) — was flat-bordered with a plain small prefix icon and
  // tight padding, which is why every field on this form looked the same
  // bare, dated style. Rounder corners, a comfortable ~50dp tap height (15sp
  // input text, matching mobile form-field size guidelines), and the prefix
  // icon rendered as a small tinted badge (the same treatment AppRecordCard's
  // fields use elsewhere in the app) bring it in line with the rest of the
  // app's visual language instead of looking like a bare default TextField.
  InputDecoration _inputDeco(
    String hint, {
    IconData? prefix,
    bool readOnly = false,
  }) {
    return InputDecoration(
      hintText: hint,
      hintStyle: TextStyle(
        fontSize: 14,
        fontWeight: FontWeight.w500,
        color: AppColors.textDisabled,
      ),
      prefixIcon: prefix == null
          ? null
          : Padding(
              padding: const EdgeInsets.all(12),
              child: Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.10),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(prefix, size: 16, color: AppColors.primary),
              ),
            ),
      prefixIconConstraints: const BoxConstraints(minWidth: 0, minHeight: 0),
      filled: true,
      fillColor: readOnly ? AppColors.surfaceFill : Colors.white,
      contentPadding: EdgeInsets.symmetric(
        horizontal: prefix == null ? 16 : 4,
        vertical: 15,
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
    );
  }

  Widget _textField(
    TextEditingController ctrl,
    String hint, {
    TextInputType inputType = TextInputType.text,
    bool required = false,
    bool readOnly = false,
    int? maxLength,
    String? suffixText,
    Widget? suffixIcon,
    String? Function(String?)? validator,
    String? hintOverride,
    ValueChanged<String>? onChanged,
  }) {
    return TextFormField(
      controller: ctrl,
      keyboardType: inputType,
      readOnly: readOnly,
      onChanged: onChanged,
      maxLength: maxLength,
      inputFormatters: inputType == TextInputType.number
          ? [FilteringTextInputFormatter.digitsOnly]
          : null,
      decoration: _inputDeco(hintOverride ?? hint, readOnly: readOnly).copyWith(
        counterText: '',
        suffixText: suffixText,
        suffixIcon: suffixIcon,
      ),
      validator:
          validator ??
          (required
              ? (v) =>
                    (v == null || v.trim().isEmpty) ? '$hint is required' : null
              : null),
    );
  }

  // Client request (2026-10-04): this used to be a disabled `TextFormField`
  // — same bordered box + floating-label chrome as every real editable field
  // on this screen, just greyed, which both looked like "a field you can't
  // use right now" instead of "not a field at all," and rendered the real
  // MRD value in a muted tone that read as empty placeholder text rather
  // than actual data. `AppReadOnlyField` is a flat, unbordered chip with an
  // explicit lock glyph instead — `showLabel: false` since this screen
  // already prints the label itself via `_sectionLabel` above each field.
  Widget _readonlyField(
    TextEditingController ctrl,
    String hint,
    IconData icon,
  ) {
    return AppReadOnlyField(
      label: hint,
      value: ctrl.text,
      icon: icon,
      showLabel: false,
    );
  }

  Widget _datefield() {
    return GestureDetector(
      onTap: _pickDate,
      child: AbsorbPointer(
        child: TextFormField(
          controller: _apptDateCtrl,
          decoration: _inputDeco(
            'Pick date',
            prefix: Icons.calendar_today_outlined,
          ),
          validator: (v) =>
              (v == null || v.isEmpty) ? 'Appointment date required' : null,
        ),
      ),
    );
  }

  Widget _contactField() {
    return TextFormField(
      controller: _contactCtrl,
      keyboardType: TextInputType.phone,
      inputFormatters: [LengthLimitingTextInputFormatter(16)],
      onChanged: _onLookupChanged,
      decoration: _inputDeco(
        'Contact number (e.g. +91XXXXXXXXXX)',
        prefix: Icons.phone_outlined,
      ).copyWith(counterText: ''),
      validator: PhoneRules.required,
    );
  }

  Widget _suggestionDropdown() {
    return Container(
      margin: const EdgeInsets.only(top: 2, bottom: 4),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.18)),
        boxShadow: [
          BoxShadow(
            color: AppColors.primary.withValues(alpha: 0.08),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        children: _suggestions.map((s) {
          final isLocal = s.type == 'local';
          return InkWell(
            onTap: () => _fillFromSuggestion(s),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          s.fullName,
                          style: TextStyle(
                            fontWeight: FontWeight.w600,
                            fontSize: 13,
                            color: AppColors.primary,
                          ),
                        ),
                        if (s.age != null ||
                            s.gender != null ||
                            (s.contactNo ?? '').isNotEmpty)
                          Text(
                            [
                              if (s.age != null) '${s.age}y',
                              if (s.gender != null)
                                '${s.gender![0].toUpperCase()}${s.gender!.substring(1)}',
                              if ((s.contactNo ?? '').isNotEmpty) s.contactNo!,
                            ].join(' · '),
                            style: TextStyle(
                              fontSize: 11,
                              color: AppColors.primary.withValues(alpha: 0.55),
                            ),
                          ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 3,
                    ),
                    decoration: BoxDecoration(
                      color: isLocal
                          ? const Color(0xFFD1FAE5)
                          : const Color(0xFFFFF3CD),
                      borderRadius: BorderRadius.circular(AppRadius.xl),
                      border: Border.all(
                        color: isLocal
                            ? const Color(0xFF10B981)
                            : const Color(0xFFFFC107),
                      ),
                    ),
                    child: Text(
                      isLocal ? 'Own' : s.hospitalName ?? 'Partner',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        color: isLocal
                            ? const Color(0xFF065F46)
                            : const Color(0xFF856404),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          );
        }).toList(),
      ),
    );
  }

  Widget _buildDropdown<T>({
    required T? value,
    required List<DropdownMenuItem<T>> items,
    required void Function(T?) onChanged,
    required String hint,
    String? Function(T?)? validator,
  }) {
    return DropdownButtonFormField<T>(
      value: value,
      items: items,
      onChanged: onChanged,
      validator: validator,
      decoration: _inputDeco(hint),
      style: TextStyle(
        color: AppColors.primary,
        fontSize: 13,
        fontWeight: FontWeight.w500,
      ),
      icon: Icon(
        Icons.expand_more_rounded,
        color: AppColors.primary.withValues(alpha: 0.6),
      ),
      dropdownColor: Colors.white,
      isExpanded: true,
    );
  }

  Widget _caseTypeDropdown() {
    final cases = _masters?.cases ?? [];
    return _buildDropdown<int>(
      value: _selectedCaseId,
      hint: 'Select Case Type',
      items: cases
          .map(
            (c) => DropdownMenuItem(
              value: c.id,
              child: Text(c.name, overflow: TextOverflow.ellipsis, maxLines: 1),
            ),
          )
          .toList(),
      onChanged: (v) {
        setState(() {
          _selectedCaseId = v;
          final caseObj = cases.where((c) => c.id == v).firstOrNull;
          if (caseObj != null && widget.mode != PatientFormMode.edit) {
            _caseFeeCtrl.text = caseObj.fee.toInt().toString();
          }
        });
      },
      validator: (v) => v == null ? 'Case type required' : null,
    );
  }

  Widget _doctorDropdown() {
    final docs = _masters?.doctors ?? [];
    return _buildDropdown<int>(
      value: _selectedDoctorId,
      hint: 'Select Doctor',
      items: docs
          .map(
            (d) => DropdownMenuItem(
              value: d.id,
              child: Text(d.name, overflow: TextOverflow.ellipsis, maxLines: 1),
            ),
          )
          .toList(),
      onChanged: (v) => setState(() => _selectedDoctorId = v),
      validator: (v) => v == null ? 'Doctor required' : null,
    );
  }

  Widget _locationDropdown() {
    final locs = _masters?.locations ?? [];
    return _buildDropdown<int>(
      value: _selectedLocationId,
      hint: 'Select City',
      items: locs
          .map(
            (l) => DropdownMenuItem(
              value: l.id,
              child: Text(l.city, overflow: TextOverflow.ellipsis, maxLines: 1),
            ),
          )
          .toList(),
      onChanged: (v) {
        setState(() {
          _selectedLocationId = v;
          final loc = locs.where((l) => l.id == v).firstOrNull;
          if (loc != null) {
            _districtCtrl.text = loc.district;
            _stateCtrl.text = loc.state;
          }
        });
      },
      validator: (v) => v == null ? 'City required' : null,
    );
  }

  Widget _slotDropdown() {
    final slots = _masters?.slots ?? [];
    return _buildDropdown<int>(
      value: _selectedSlotId,
      hint: 'No Slot (optional)',
      items: slots
          .map(
            (s) => DropdownMenuItem(
              value: s.id,
              child: Text(
                s.display,
                overflow: TextOverflow.ellipsis,
                maxLines: 1,
              ),
            ),
          )
          .toList(),
      onChanged: (v) => setState(() => _selectedSlotId = v),
    );
  }

  Widget _referrerDropdown() {
    final refs = _masters?.referrers ?? [];
    return _buildDropdown<int>(
      value: _selectedReferrerId,
      hint: 'No Referrer (optional)',
      items: refs
          .map(
            (r) => DropdownMenuItem(
              value: r.id,
              child: Text(r.name, overflow: TextOverflow.ellipsis, maxLines: 1),
            ),
          )
          .toList(),
      onChanged: (v) => setState(() => _selectedReferrerId = v),
    );
  }

  Widget _buildOtCheckinSection() {
    return Container(
      margin: const EdgeInsets.only(bottom: 6),
      decoration: BoxDecoration(
        color: AppColors.primary.withValues(alpha: 0.04),
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.18)),
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          InkWell(
            onTap: () => setState(() => _otSearchExpanded = !_otSearchExpanded),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              child: Row(
                children: [
                  Icon(
                    Icons.event_available_outlined,
                    size: 16,
                    color: AppColors.primary,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'OT check-in search (optional)',
                      style: TextStyle(
                        fontSize: 12.5,
                        fontWeight: FontWeight.w700,
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                  Icon(
                    _otSearchExpanded
                        ? Icons.keyboard_arrow_up_rounded
                        : Icons.keyboard_arrow_down_rounded,
                    color: AppColors.primary,
                  ),
                ],
              ),
            ),
          ),
          if (_otSearchExpanded)
            Padding(
              padding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'SEARCH OT APPOINTMENT',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      color: AppColors.primary.withValues(alpha: 0.55),
                      letterSpacing: 0.4,
                    ),
                  ),
                  const SizedBox(height: 6),
                  TextFormField(
                    controller: _otSearchCtrl,
                    onChanged: _linkedOtAppointmentId == null
                        ? _onOtSearchChanged
                        : null,
                    readOnly: _linkedOtAppointmentId != null,
                    decoration:
                        _inputDeco(
                          'Search by APT number, name or mobile...',
                          prefix: Icons.search_rounded,
                        ).copyWith(
                          suffixIcon: _otSearching
                              ? const Padding(
                                  padding: EdgeInsets.all(14),
                                  child: SizedBox(
                                    width: 14,
                                    height: 14,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                    ),
                                  ),
                                )
                              : (_linkedOtAppointmentId != null
                                    ? IconButton(
                                        icon: const Icon(
                                          Icons.close_rounded,
                                          size: 18,
                                        ),
                                        onPressed: _clearOtLink,
                                      )
                                    : null),
                        ),
                  ),
                  if (_otSearchResults.isNotEmpty)
                    Container(
                      margin: const EdgeInsets.only(top: 4),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(
                          color: AppColors.primary.withValues(alpha: 0.18),
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: AppColors.primary.withValues(alpha: 0.08),
                            blurRadius: 8,
                            offset: const Offset(0, 3),
                          ),
                        ],
                      ),
                      child: Column(
                        children: _otSearchResults
                            .map(
                              (o) => InkWell(
                                onTap: () => _selectOtAppointment(o),
                                child: Padding(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 14,
                                    vertical: 10,
                                  ),
                                  child: Row(
                                    children: [
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              '${o.appointmentNumber} — ${o.fullName}',
                                              style: TextStyle(
                                                fontWeight: FontWeight.w600,
                                                fontSize: 13,
                                                color: AppColors.primary,
                                              ),
                                            ),
                                            Text(
                                              '${o.mobileNo}${o.doctor != null ? ' · Dr. ${o.doctor!.name}' : ''}',
                                              style: TextStyle(
                                                fontSize: 11,
                                                color: AppColors.primary
                                                    .withValues(alpha: 0.55),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            )
                            .toList(),
                      ),
                    ),
                  if (_linkedOtAppointmentId != null) ...[
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        const Icon(
                          Icons.check_circle_rounded,
                          size: 14,
                          color: AppColors.green,
                        ),
                        const SizedBox(width: 5),
                        Text(
                          'Linked to $_linkedOtAppointmentNumber',
                          style: const TextStyle(
                            fontSize: 11.5,
                            fontWeight: FontWeight.w700,
                            color: AppColors.green,
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildWarnBanner() {
    return Container(
      margin: const EdgeInsets.only(bottom: 4),
      padding: const EdgeInsets.fromLTRB(14, 10, 10, 10),
      decoration: BoxDecoration(
        color: const Color(0xFFFDEBD0),
        borderRadius: BorderRadius.circular(10),
        border: const Border(
          left: BorderSide(color: Color(0xFFE67E22), width: 4),
        ),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(
            Icons.warning_amber_rounded,
            color: Color(0xFFE67E22),
            size: 18,
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: const [
                Text(
                  'Examination Completed',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF784212),
                  ),
                ),
                SizedBox(height: 2),
                Text(
                  'Primary examination has already been done. Changes will not affect examination data.',
                  style: TextStyle(fontSize: 11, color: Color(0xFF784212)),
                ),
              ],
            ),
          ),
          IconButton(
            icon: const Icon(
              Icons.close_rounded,
              size: 16,
              color: Color(0xFF784212),
            ),
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
            onPressed: () => setState(() => _warnDismissed = true),
          ),
        ],
      ),
    );
  }

  Widget _submitButton() {
    final isPhone = _isPhoneForm;
    final isEdit = widget.mode == PatientFormMode.edit;
    final label = isEdit
        ? 'Update Patient'
        : isPhone
        ? 'Register Phone Appointment'
        : 'Register Patient';

    return SizedBox(
      width: double.infinity,
      child: ElevatedButton(
        onPressed: (_submitting || _submitSuccess) ? null : _submit,
        style: ElevatedButton.styleFrom(
          backgroundColor: _submitSuccess
              ? Colors.green.shade600
              : AppColors.primary,
          foregroundColor: Colors.white,
          disabledBackgroundColor: _submitSuccess
              ? Colors.green.shade600
              : AppColors.primary.withValues(alpha: 0.5),
          padding: const EdgeInsets.symmetric(vertical: 16),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(40),
          ),
          elevation: 2,
        ),
        child: AnimatedSwitcher(
          duration: const Duration(milliseconds: 220),
          child: _submitSuccess
              ? const Icon(
                  Icons.check_rounded,
                  color: Colors.white,
                  size: 24,
                  key: ValueKey('chk'),
                )
              : _submitting
              ? const SizedBox(
                  width: 18,
                  height: 18,
                  key: ValueKey('spin'),
                  child: CircularProgressIndicator(
                    color: Colors.white,
                    strokeWidth: 2,
                  ),
                )
              : Row(
                  key: const ValueKey('txt'),
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.check_circle_outline_rounded, size: 20),
                    const SizedBox(width: 8),
                    Text(
                      label,
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
        ),
      ),
    );
  }

  // ── Helpers ─────────────────────────────────────────────────────────────────

  String _formatDate(DateTime dt, {bool iso = false}) {
    if (iso) return toIsoDate(dt);
    return '${dt.day.toString().padLeft(2, '0')}-${dt.month.toString().padLeft(2, '0')}-${dt.year}';
  }
}
