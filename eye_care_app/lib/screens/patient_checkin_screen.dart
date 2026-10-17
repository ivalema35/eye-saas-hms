import 'dart:async';
import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../utils/app_route.dart';
import '../utils/date_format.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_record_card.dart';
import '../widgets/app_text_field.dart';
import '../widgets/skeleton.dart';
import 'package:flutter/services.dart';
import '../models/auth_models.dart';
import '../models/patient_models.dart';
import '../services/masters_service.dart';
import '../utils/phone_rules.dart';
import '../services/patient_service.dart';
import 'opd_bill_screen.dart';
import '../utils/app_date_picker.dart';

class PatientCheckinScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final Patient patient;

  const PatientCheckinScreen({
    super.key,
    required this.user,
    required this.hospital,
    required this.patient,
  });

  @override
  State<PatientCheckinScreen> createState() => _PatientCheckinScreenState();
}

class _PatientCheckinScreenState extends State<PatientCheckinScreen> {
  final _formKey = GlobalKey<FormState>();

  // Controllers
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

  // Masters
  MastersData? _masters;
  bool _mastersLoading = true;
  String? _mastersError;

  bool _submitting = false;
  DateTime _appointmentDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    _prefill(widget.patient);
    _loadMasters();
  }

  void _prefill(Patient p) {
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
    _selectedDoctorId = p.doctor?.id;
    _selectedLocationId = p.location?.id;
    _districtCtrl.text = p.location?.district ?? '';
    _stateCtrl.text = p.location?.state ?? '';
    _selectedSlotId = p.slotId;
    _selectedReferrerId = p.referrerId;
    if (p.appointmentDate != null) {
      _appointmentDate =
          parseApiPlainDate(p.appointmentDate!) ?? DateTime.now();
    }
    _apptDateCtrl.text = _fmtDate(_appointmentDate);
  }

  Future<void> _loadMasters() async {
    setState(() {
      _mastersLoading = true;
      _mastersError = null;
    });
    try {
      final m = await MastersService.instance.fetchAll();
      if (!mounted) return;
      setState(() => _masters = m);
      // Auto-fill district/state after masters load
      if (_selectedLocationId != null) {
        final loc = m.locations
            .where((l) => l.id == _selectedLocationId)
            .firstOrNull;
        if (loc != null && mounted) {
          setState(() {
            _districtCtrl.text = loc.district;
            _stateCtrl.text = loc.state;
          });
        }
      }
    } catch (e) {
      if (mounted) setState(() => _mastersError = e.toString());
    } finally {
      if (mounted) setState(() => _mastersLoading = false);
    }
  }

  @override
  void dispose() {
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
    super.dispose();
  }

  Future<void> _pickDate() async {
    final picked = await showAppDatePicker(
      context: context,
      initialDate: _appointmentDate,
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      builder: (ctx, child) => Theme(
        data: Theme.of(
          ctx,
        ).copyWith(colorScheme: ColorScheme.light(primary: AppColors.primary)),
        child: child!,
      ),
    );
    if (picked != null && mounted) {
      setState(() {
        _appointmentDate = picked;
        _apptDateCtrl.text = _fmtDate(picked);
      });
    }
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    if (_submitting) return;

    setState(() => _submitting = true);

    try {
      final data = <String, dynamic>{
        'appointment_date': _fmtDateIso(_appointmentDate),
        'contact_no': _contactCtrl.text.trim(),
        'whatsapp_no': _whatsappCtrl.text.trim().isEmpty
            ? null
            : _whatsappCtrl.text.trim(),
        'first_name': _firstCtrl.text.trim(),
        'last_name': _lastCtrl.text.trim(),
        'middle_name': _middleCtrl.text.trim(),
        'age': int.tryParse(_ageCtrl.text.trim()),
        'gender': _selectedGender,
        'occupation': _occupationCtrl.text.trim().isEmpty
            ? null
            : _occupationCtrl.text.trim(),
        'doctor_id': _selectedDoctorId,
        'location_id': _selectedLocationId,
        'case_id': _selectedCaseId,
        'case_fee': double.tryParse(_caseFeeCtrl.text.trim()) ?? 0,
        'slot_id': _selectedSlotId,
        'referrer_id': _selectedReferrerId,
      };

      final updated = await PatientService.instance.checkIn(
        widget.patient.id,
        data,
      );

      if (!mounted) return;

      // Navigate to OPD Bill. result: true so the dashboard/patients-list
      // push<bool> awaiter (which resolves to THIS replaced route's
      // completion, not the new one's) sees the check-in and refreshes —
      // otherwise it resolves with null and the list never shows this
      // patient as checked-in until some unrelated refresh happens.
      await Navigator.pushReplacement(
        context,
        appRoute(
          OpdBillScreen(
            user: widget.user,
            hospital: widget.hospital,
            patient: updated,
          ),
        ),
        result: true,
      );
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
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          _buildAppBar(),
          Expanded(
            child: _mastersLoading
                ? const AppSkeletonList()
                : _mastersError != null
                ? _buildError()
                : _buildForm(),
          ),
        ],
      ),
    );
  }

  Widget _buildAppBar() {
    final p = widget.patient;
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
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(AppRadius.md),
                ),
                child: const Icon(
                  Icons.how_to_reg_rounded,
                  color: Colors.white,
                  size: 22,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Patient Check-In',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'MRD: ${p.patientCode}  ·  Verify details & assign case',
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

  Widget _buildError() {
    return AppErrorState(
      message: _mastersError ?? 'Failed to load form data.',
      onRetry: _loadMasters,
    );
  }

  // Client request (2026-10-04): redesigned onto the same card/chip language
  // as `patient_form_screen.dart`/the OT Appointment form. Cross-checked
  // field order against `patients/checkin.blade.php` while rebuilding this
  // and found the same kind of drift that screen had, fixed here:
  //  1. MRD No. was shown first, above Appointment Date; web places it
  //     mid-row (after WhatsApp, before First Name).
  //  2. Doctor sat BEFORE City/District/State; web's row always ends City →
  //     District → State → Doctor.
  //  3. Time Slot was paired with Referred By in one row AFTER Age/Gender/
  //     Occupation; web puts Slot Time right after Doctor (before Age) and
  //     Referred By last (after Occupation) — they were never adjacent.
  Widget _buildForm() {
    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 100),
        children: [
          // ── Pre-filled notice ────────────────────────────────────────
          _infoNotice(),

          AppFormCard(
            icon: Icons.event_note_rounded,
            title: 'Appointment Details',
            trailing: const AppStatusPill(
              label: 'Check-In',
              color: AppColors.teal,
            ),
            children: [
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
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _sectionLabel('Contact No *'),
                        _textField(
                          _contactCtrl,
                          'Contact number',
                          inputType: TextInputType.phone,
                          maxLength: 16,
                          validator: PhoneRules.required,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _sectionLabel('WhatsApp No'),
                        _textField(
                          _whatsappCtrl,
                          'Same if blank',
                          inputType: TextInputType.phone,
                          maxLength: 10,
                          suffixIcon: TextButton(
                            onPressed: () => setState(
                              () => _whatsappCtrl.text = _contactCtrl.text,
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
                      ],
                    ),
                  ),
                ],
              ),
              AppReadOnlyField(
                label: 'MRD No.',
                value: widget.patient.patientCode,
                icon: Icons.badge_outlined,
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
              Row(
                children: [
                  Expanded(
                    flex: 3,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _sectionLabel('Case Type *'),
                        _caseTypeDropdown(),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    flex: 2,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _sectionLabel(
                          'Case Fee (${widget.hospital.currencySymbol}) *',
                        ),
                        _caseFeeField(),
                      ],
                    ),
                  ),
                ],
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [_sectionLabel('City *'), _locationDropdown()],
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
              // Web parity: Doctor comes AFTER City/District/State.
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [_sectionLabel('Doctor *'), _doctorDropdown()],
              ),
              // Web parity: Slot Time is right after Doctor, before Age —
              // not paired with Referred By after Occupation.
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
              // Web parity: Referred By is the LAST field, after Occupation.
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [_sectionLabel('Referred By'), _referrerDropdown()],
              ),
            ],
          ),

          const SizedBox(height: 8),
          _submitButton(),
        ],
      ),
    );
  }

  // ── Widgets ─────────────────────────────────────────────────────────────────

  Widget _infoNotice() {
    return Container(
      margin: const EdgeInsets.only(bottom: 4),
      padding: const EdgeInsets.fromLTRB(14, 10, 14, 10),
      decoration: BoxDecoration(
        color: const Color(0xFFD6EAF8),
        borderRadius: BorderRadius.circular(10),
        border: const Border(
          left: BorderSide(color: AppColors.blueLight, width: 4),
        ),
      ),
      child: Row(
        children: [
          const Icon(
            Icons.info_outline_rounded,
            color: AppColors.blueLight,
            size: 18,
          ),
          const SizedBox(width: 8),
          const Expanded(
            child: Text(
              'Pre-filled from phone appointment. Verify and update if needed before checking in.',
              style: TextStyle(fontSize: 11, color: Color(0xFF1A5276)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _sectionLabel(String text) => Padding(
    padding: const EdgeInsets.only(top: 14, bottom: 5),
    child: Text(
      text,
      style: TextStyle(
        fontSize: 12,
        fontWeight: FontWeight.w600,
        color: AppColors.primary,
      ),
    ),
  );

  InputDecoration _inputDeco(
    String hint, {
    IconData? prefix,
    bool readOnly = false,
  }) {
    return InputDecoration(
      hintText: hint,
      hintStyle: TextStyle(fontSize: 13, color: AppColors.primaryA40),
      prefixIcon: prefix != null
          ? Icon(prefix, size: 18, color: AppColors.primaryA50)
          : null,
      filled: true,
      fillColor: readOnly ? AppColors.primaryA04 : Colors.white,
      contentPadding: const EdgeInsets.symmetric(vertical: 12, horizontal: 14),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(11),
        borderSide: BorderSide(color: AppColors.primaryA18),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(11),
        borderSide: BorderSide(color: AppColors.primaryA18),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(11),
        borderSide: BorderSide(color: AppColors.primary, width: 1.5),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(11),
        borderSide: const BorderSide(color: AppColors.red),
      ),
      focusedErrorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(11),
        borderSide: const BorderSide(color: AppColors.red, width: 1.5),
      ),
    );
  }

  Widget _textField(
    TextEditingController ctrl,
    String hint, {
    TextInputType inputType = TextInputType.text,
    bool required = false,
    int? maxLength,
    Widget? suffixIcon,
    String? Function(String?)? validator,
  }) {
    return TextFormField(
      controller: ctrl,
      keyboardType: inputType,
      maxLength: maxLength,
      inputFormatters: inputType == TextInputType.number
          ? [FilteringTextInputFormatter.digitsOnly]
          : null,
      decoration: _inputDeco(
        hint,
      ).copyWith(counterText: '', suffixIcon: suffixIcon),
      validator:
          validator ??
          (required
              ? (v) =>
                    (v == null || v.trim().isEmpty) ? '$hint is required' : null
              : null),
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

  Widget _caseFeeField() {
    return TextFormField(
      controller: _caseFeeCtrl,
      keyboardType: TextInputType.number,
      decoration: _inputDeco('0').copyWith(
        prefixIcon: const Icon(
          Icons.currency_rupee_rounded,
          size: 16,
          color: AppColors.green,
        ),
        filled: true,
        fillColor: const Color(0xFFEAFAF1),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(11),
          borderSide: const BorderSide(color: Color(0xFFA9DFBF)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(11),
          borderSide: const BorderSide(color: AppColors.green, width: 1.5),
        ),
      ),
      style: const TextStyle(
        color: AppColors.green,
        fontWeight: FontWeight.w700,
      ),
      validator: (v) {
        if (v == null || v.trim().isEmpty) return 'Fee required';
        if (double.tryParse(v.trim()) == null) return 'Invalid fee';
        return null;
      },
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
      initialValue: value,
      items: items,
      onChanged: onChanged,
      validator: validator,
      decoration: _inputDeco(hint),
      style: TextStyle(
        color: AppColors.primary,
        fontSize: 13,
        fontWeight: FontWeight.w500,
      ),
      icon: Icon(Icons.expand_more_rounded, color: AppColors.primaryA60),
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
      onChanged: (v) => setState(() {
        _selectedCaseId = v;
        final c = cases.where((x) => x.id == v).firstOrNull;
        if (c != null) _caseFeeCtrl.text = c.fee.toInt().toString();
      }),
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
      onChanged: (v) => setState(() {
        _selectedLocationId = v;
        final loc = locs.where((l) => l.id == v).firstOrNull;
        if (loc != null) {
          _districtCtrl.text = loc.district;
          _stateCtrl.text = loc.state;
        }
      }),
      validator: (v) => v == null ? 'City required' : null,
    );
  }

  Widget _slotDropdown() {
    final slots = _masters?.slots ?? [];
    return _buildDropdown<int>(
      value: _selectedSlotId,
      hint: 'No Slot',
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
      hint: 'No Referrer',
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

  Widget _submitButton() {
    return SizedBox(
      width: double.infinity,
      child: ElevatedButton.icon(
        onPressed: _submitting ? null : _submit,
        icon: _submitting
            ? const SizedBox(
                width: 18,
                height: 18,
                child: CircularProgressIndicator(
                  color: Colors.white,
                  strokeWidth: 2,
                ),
              )
            : const Icon(Icons.how_to_reg_rounded, size: 20),
        label: const Text(
          'Check In & Print Bill',
          style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700),
        ),
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
          disabledBackgroundColor: AppColors.primaryA50,
          padding: const EdgeInsets.symmetric(vertical: 16),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(40),
          ),
          elevation: 2,
        ),
      ),
    );
  }

  // ── Helpers ─────────────────────────────────────────────────────────────────

  String _fmtDate(DateTime dt) =>
      '${dt.day.toString().padLeft(2, '0')}-${dt.month.toString().padLeft(2, '0')}-${dt.year}';

  String _fmtDateIso(DateTime dt) => toIsoDate(dt);
}
