import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/patient_models.dart';
import '../services/masters_service.dart';
import '../services/patient_service.dart';
import '../utils/app_route.dart';
import '../utils/currency_format.dart';
import '../utils/date_format.dart';
import 'app_animations.dart';

/// Web pull parity (2026-09-29) — `exam/_patient_info_modal.blade.php`'s
/// "Patient Details" button, shown in the exam app bar on both Primary and
/// Secondary Exam. A full screen here (mobile's own convention for a
/// form this size — see other per-app UI notes in this codebase) rather
/// than a dialog, but the same two sections and same data as web/tablet: an
/// editable Personal Information block (doctor-only on the server, PATCHes
/// `quick-personal`) and a read-only Appointment Information block.
/// See WEB_VIEW_BUTTONS_PARITY_AUDIT.md finding #2.
Future<void> showPatientDetailsModal(
  BuildContext context,
  Patient patient, {
  void Function(Patient updated)? onSaved,
}) {
  return Navigator.of(
    context,
  ).push(appRoute(_PatientDetailsScreen(patient: patient, onSaved: onSaved)));
}

class _PatientDetailsScreen extends StatefulWidget {
  final Patient patient;
  final void Function(Patient updated)? onSaved;
  const _PatientDetailsScreen({required this.patient, this.onSaved});

  @override
  State<_PatientDetailsScreen> createState() => _PatientDetailsScreenState();
}

class _PatientDetailsScreenState extends State<_PatientDetailsScreen> {
  late Patient _patient;
  bool _loading = true;
  bool _ready = false;
  bool _saving = false;
  String? _error;
  List<LocationOption> _locations = const [];

  late final TextEditingController _nameCtrl;
  late final TextEditingController _ageCtrl;
  late final TextEditingController _contactCtrl;
  String? _gender;
  int? _locationId;

  @override
  void initState() {
    super.initState();
    _patient = widget.patient;
    _nameCtrl = TextEditingController();
    _ageCtrl = TextEditingController();
    _contactCtrl = TextEditingController();
    _load();
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _ageCtrl.dispose();
    _contactCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final results = await Future.wait([
        PatientService.instance.fetchOne(widget.patient.id),
        MastersService.instance.fetchAll(),
      ]);
      if (!mounted) return;
      final patient = results[0] as Patient;
      final masters = results[1] as MastersData;
      _apply(patient);
      setState(() {
        _locations = masters.locations;
        _syncChoices();
        _ready = true;
        _loading = false;
      });
    } catch (e) {
      if (mounted)
        setState(() {
          _error = e.toString().replaceFirst('Exception: ', '');
          _ready = false;
          _loading = false;
        });
    }
  }

  void _apply(Patient patient) {
    _patient = patient;
    _nameCtrl.text = patient.fullName;
    _ageCtrl.text = patient.age?.toString() ?? '';
    _contactCtrl.text = patient.contactNo ?? '';
    _gender = patient.gender;
    _locationId = patient.location?.id;
  }

  Future<void> _save() async {
    final name = _nameCtrl.text.trim();
    final age = int.tryParse(_ageCtrl.text.trim());
    final contact = _phoneForApi(_contactCtrl.text);
    if (name.isEmpty ||
        age == null ||
        _gender == null ||
        contact.isEmpty ||
        _locationId == null) {
      showAppSnackBar(
        context,
        'Please fill every Personal Information field.',
        isError: true,
      );
      return;
    }
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      final updated = await PatientService.instance.quickUpdatePersonal(
        _patient.id,
        fullName: name,
        age: age,
        gender: _gender!,
        contactNo: contact,
        locationId: _locationId!,
      );
      if (!mounted) return;
      setState(() => _patient = updated);
      widget.onSaved?.call(updated);
      showAppSnackBar(context, 'Patient details updated.', isSuccess: true);
    } catch (e) {
      if (mounted) setState(() => _error = _cleanError(e));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  void _syncChoices() {
    final gender = (_gender ?? '').toLowerCase();
    _gender = (gender == 'male' || gender == 'female' || gender == 'other')
        ? gender
        : null;
    if (!_locations.any((l) => l.id == _locationId)) _locationId = null;
  }

  String _cleanError(Object error) {
    final text = error.toString().replaceFirst('Exception: ', '').trim();
    if (text.isEmpty || text == 'Exception') {
      return 'Save failed. Only a doctor can update these details.';
    }
    return text;
  }

  String _phoneForApi(String raw) {
    final t = raw.trim();
    final plus = t.startsWith('+');
    final digits = t.replaceAll(RegExp(r'[^0-9]'), '');
    return plus ? '+$digits' : digits;
  }

  InputDecoration _deco(
    String label, {
    bool readOnly = false,
  }) => InputDecoration(
    labelText: label,
    filled: true,
    fillColor: readOnly
        ? AppColors.primary.withValues(alpha: 0.04)
        : Colors.white,
    contentPadding: const EdgeInsets.symmetric(vertical: 12, horizontal: 14),
    border: OutlineInputBorder(
      borderRadius: BorderRadius.circular(11),
      borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.18)),
    ),
    enabledBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(11),
      borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.18)),
    ),
    focusedBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(11),
      borderSide: BorderSide(color: AppColors.primary, width: 1.4),
    ),
  );

  LocationOption? get _selectedLocation {
    for (final l in _locations) {
      if (l.id == _locationId) return l;
    }
    return null;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(8, 8, 14, 0),
              child: Row(
                children: [
                  IconButton(
                    icon: const Icon(
                      Icons.arrow_back_ios_new_rounded,
                      size: 18,
                    ),
                    color: AppColors.primary,
                    onPressed: () => Navigator.pop(context),
                  ),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _patient.fullName.isNotEmpty
                              ? _patient.fullName
                              : 'Patient',
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w800,
                            color: AppColors.textPrimary,
                          ),
                          overflow: TextOverflow.ellipsis,
                        ),
                        Text(
                          'MRD: ${_patient.patientCode.isNotEmpty ? _patient.patientCode : '—'}',
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
            Expanded(
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : !_ready
                  ? Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(
                              _error ?? 'Could not load this patient.',
                              textAlign: TextAlign.center,
                              style: const TextStyle(
                                color: AppColors.red,
                                fontSize: 13,
                              ),
                            ),
                            const SizedBox(height: 8),
                            TextButton(
                              onPressed: _load,
                              child: const Text('Retry'),
                            ),
                          ],
                        ),
                      ),
                    )
                  : ListView(
                      padding: const EdgeInsets.fromLTRB(14, 14, 14, 20),
                      children: [
                        if (_error != null)
                          Padding(
                            padding: const EdgeInsets.only(bottom: 12),
                            child: Text(
                              _error!,
                              style: const TextStyle(
                                color: AppColors.red,
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        _sectionCard(
                          Icons.person_rounded,
                          'Personal Information',
                          _buildPersonalForm(),
                        ),
                        const SizedBox(height: 14),
                        _sectionCard(
                          Icons.event_available_rounded,
                          'Appointment Information',
                          _buildAppointmentGrid(),
                        ),
                        const SizedBox(height: 20),
                        ElevatedButton.icon(
                          onPressed: (_saving || !_ready) ? null : _save,
                          icon: _saving
                              ? const SizedBox(
                                  width: 14,
                                  height: 14,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : const Icon(Icons.check_rounded, size: 18),
                          label: const Text('Save Changes'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 13),
                            minimumSize: const Size.fromHeight(0),
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

  Widget _sectionCard(IconData icon, String title, Widget child) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 15, color: AppColors.primary),
              const SizedBox(width: 6),
              Text(
                title,
                style: TextStyle(
                  fontSize: 12.5,
                  fontWeight: FontWeight.w800,
                  color: AppColors.primary,
                  letterSpacing: 0.3,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }

  Widget _buildPersonalForm() {
    return Column(
      children: [
        TextField(controller: _nameCtrl, decoration: _deco('Full Name')),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: _ageCtrl,
                keyboardType: TextInputType.number,
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                decoration: _deco('Age'),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: DropdownButtonFormField<String>(
                initialValue: _gender,
                decoration: _deco('Gender'),
                items: const [
                  DropdownMenuItem(value: 'male', child: Text('Male')),
                  DropdownMenuItem(value: 'female', child: Text('Female')),
                  DropdownMenuItem(value: 'other', child: Text('Other')),
                ],
                onChanged: (v) => setState(() => _gender = v),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        TextField(
          controller: _contactCtrl,
          keyboardType: TextInputType.phone,
          decoration: _deco('Mobile Number'),
        ),
        const SizedBox(height: 12),
        DropdownButtonFormField<int>(
          initialValue: _locations.any((l) => l.id == _locationId)
              ? _locationId
              : null,
          isExpanded: true,
          decoration: _deco('City'),
          items: _locations
              .map(
                (l) => DropdownMenuItem(
                  value: l.id,
                  child: Text(l.city, overflow: TextOverflow.ellipsis),
                ),
              )
              .toList(),
          onChanged: (v) => setState(() => _locationId = v),
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: InputDecorator(
                decoration: _deco('District', readOnly: true),
                child: Text(
                  _place(
                    _selectedLocation?.district,
                    _patient.location?.district,
                  ),
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: InputDecorator(
                decoration: _deco('State', readOnly: true),
                child: Text(
                  _place(_selectedLocation?.state, _patient.location?.state),
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
              ),
            ),
          ],
        ),
      ],
    );
  }

  String _place(String? selected, String? stored) {
    final a = (selected ?? '').trim();
    if (a.isNotEmpty) return a;
    final b = (stored ?? '').trim();
    return b.isEmpty ? '—' : b;
  }

  String _text(String? value) {
    final t = (value ?? '').trim();
    return t.isEmpty ? '—' : t;
  }

  Widget _buildAppointmentGrid() {
    final date = parseApiPlainDate(_patient.appointmentDate);
    final typeLabel = _patient.type == 'phone'
        ? 'Phone'
        : _patient.type == 'walkin'
        ? 'Walk-in'
        : _text(_patient.type);
    final fee = _patient.caseFee == null
        ? '—'
        : '${currentCurrencySymbol()}${_patient.caseFee!.toStringAsFixed(2)}';
    final tiles = <(String, String)>[
      ('Appointment Date', date != null ? toDisplayDate(date) : '—'),
      ('Type', typeLabel),
      ('Patient Status', _patient.isOldPatient ? 'Old Patient' : 'New Patient'),
      ('Doctor', _text(_patient.doctor?.name)),
      ('Case Type', _text(_patient.caseType?.caseType)),
      ('Case Fee', fee),
      ('Receptionist', _text(_patient.receptionistName)),
      ('Referrer', _text(_patient.referrer?.name)),
    ];
    return Column(
      children: [
        for (var i = 0; i < tiles.length; i += 2) ...[
          if (i > 0) const SizedBox(height: 8),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(child: _infoTile(tiles[i].$1, tiles[i].$2)),
              const SizedBox(width: 8),
              Expanded(
                child: i + 1 < tiles.length
                    ? _infoTile(tiles[i + 1].$1, tiles[i + 1].$2)
                    : const SizedBox.shrink(),
              ),
            ],
          ),
        ],
      ],
    );
  }

  Widget _infoTile(String label, String value) => Container(
    width: double.infinity,
    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
    decoration: BoxDecoration(
      color: AppColors.backgroundAlt,
      borderRadius: BorderRadius.circular(AppRadius.md),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(fontSize: 10, color: AppColors.textSecondary),
        ),
        const SizedBox(height: 3),
        Text(
          value,
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(
            fontSize: 12.5,
            fontWeight: FontWeight.w700,
            color: AppColors.textPrimary,
          ),
        ),
      ],
    ),
  );
}
