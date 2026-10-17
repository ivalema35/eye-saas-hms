import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../models/auth_models.dart';
import '../models/medicine_models.dart';
import '../models/patient_models.dart';
import '../services/exam_masters_service.dart';
import '../services/exam_service.dart';
import '../services/referrer_service.dart';
import '../services/simple_master_service.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/permissions.dart';
import '../services/permission_service.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_error_state.dart';
import '../widgets/exam/advice_picker.dart';
import '../widgets/exam/diagnosis_grouped_chips.dart';
import '../widgets/exam/exam_live_slip.dart';
import '../widgets/exam/exam_section_sheet.dart';
import '../widgets/exam/exam_finish_chooser.dart';
import '../widgets/exam/exam_sections.dart';
import '../widgets/exam/exam_stepper.dart';
import '../widgets/patient_details_modal.dart';
import '../widgets/skeleton.dart';
import '../widgets/ot/recommend_surgery_sheet.dart';

class _PrescRow {
  int? medicineId;
  String medicineName;
  int? dosageId;
  int? routeId;
  String duration;
  int quantity;
  _PrescRow({
    this.medicineId,
    this.medicineName = '',
    this.dosageId,
    this.routeId,
    this.duration = '',
    this.quantity = 1,
  });
  Map<String, dynamic> toJson() => {
    if (medicineId != null) 'medicine_id': medicineId,
    if (medicineName.isNotEmpty) 'name': medicineName,
    if (dosageId != null) 'dosage_id': dosageId,
    if (routeId != null) 'route_id': routeId,
    'duration': duration.isEmpty ? null : duration,
    'quantity': quantity,
  };
}

class _CoRow {
  String complaint;
  String since;
  String unit;
  String eye;
  String comment;
  // since/eye use '-' locally; toJson converts '-' → '' (empty) for API
  // Field-chain auto-advance (complaint → since → unit → eye → comment,
  // matching web's advanceExamRowField) lands here after the eye picker.
  final FocusNode commentFocus = FocusNode();
  _CoRow({
    this.complaint = '',
    this.since = '-',
    this.unit = 'Days',
    this.eye = '-',
    this.comment = '',
  });
  void dispose() => commentFocus.dispose();
  Map<String, dynamic> toJson() => {
    'complaint': complaint,
    'since': since == '-' ? '' : since,
    'unit': unit,
    'eye': eye == '-' ? '' : eye,
    'comment': comment,
  };
}

class _KcoRow {
  String condition;
  String since;
  String unit;
  String comment;
  // K/C/O: default unit = Years (web default), since = '-'
  // Field-chain auto-advance (condition → since → unit → comment) lands
  // here after the unit picker.
  final FocusNode commentFocus = FocusNode();
  _KcoRow({
    this.condition = '',
    this.since = '-',
    this.unit = 'Years',
    this.comment = '',
  });
  void dispose() => commentFocus.dispose();
  Map<String, dynamic> toJson() => {
    'condition': condition,
    'since': since == '-' ? '' : since,
    'unit': unit,
    'comment': comment,
  };
}

class SecondaryExamScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final Patient patient;
  const SecondaryExamScreen({
    super.key,
    required this.user,
    required this.hospital,
    required this.patient,
  });
  @override
  State<SecondaryExamScreen> createState() => _SecondaryExamScreenState();
}

class _SecondaryExamScreenState extends State<SecondaryExamScreen> {
  // Web pull 2026-09-29 (WEB_VIEW_BUTTONS_PARITY_AUDIT.md finding #2) — the
  // "Patient Details" modal can edit name/age/gender/contact/city; this
  // holds the latest saved copy so the app bar reflects it without needing
  // `widget.patient` (a `final`) to change.
  late Patient _currentPatient;
  bool _loading = true;
  String? _loadError;
  bool _anySaved = false; // pop(true) when back so patients list refreshes
  bool _committing = false;
  ExamSection? _activeSection;
  final Set<ExamSection> _filled = {};
  bool _sheetOpen = false;
  final ValueNotifier<int> _sheetTick = ValueNotifier(0);

  /// Guards the section-open auto-advance below so it fires exactly once
  /// per sheet open, not on every _sheetTick rebuild.
  bool _autoOpenDone = false;

  /// Web parity: modalClinical/modalHko/modalVision's `shown.bs.modal`
  /// handlers open the first field's picker immediately, no manual tap
  /// needed. Called from a field's `exposeOpener`-equivalent the first time
  /// it builds after a section sheet opens.
  ///
  /// [section] is the section this open() belongs to. A user who finishes a
  /// quick section (e.g. NCT's 2 fields, or Fundus with favourites already
  /// picked) within the 250ms delay below auto-advances to the NEXT
  /// section's sheet before this fires — without the [_activeSection]
  /// check, the stale callback still ran `open()` regardless, popping the
  /// OLD section's picker (e.g. NCT's IOP sheet) on top of whatever section
  /// is showing now (e.g. O/E). Checking the section is still current before
  /// firing fixes that race without touching the delay itself.
  void _autoOpenFirstField(ExamSection section, Future<void> Function() open) {
    if (_autoOpenDone) return;
    _autoOpenDone = true;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Future.delayed(const Duration(milliseconds: 250), () {
        if (mounted && _activeSection == section) open();
      });
    });
  }

  @override
  void setState(VoidCallback fn) {
    super.setState(fn);
    if (_sheetOpen) _sheetTick.value++;
  }

  // 'none' | 'primary' | 'secondary'
  String _prefillSource = 'none';

  ExamFormData? _formData;
  int? _selectedDoctorId;

  // Section 1
  final List<String> _historyChips = [];
  final _historySearchCtrl = TextEditingController();
  final List<_CoRow> _coRows = [];
  final List<_KcoRow> _kcoRows = [];

  // Section 2: Vision — flat web keys
  final _vnRe = TextEditingController();
  final _vnLe = TextEditingController();
  final _pnvnRe = TextEditingController();
  final _pnvnLe = TextEditingController();
  final _nrvnRe = TextEditingController();
  final _nrvnLe = TextEditingController();

  // NCT — web keys: iop_re, iop_le
  final _nctIopRe = TextEditingController();
  final _nctIopLe = TextEditingController();

  // Section 3: Refraction
  static const _pgDistKeys = ['ds', 'dc', 'ax', 'vn'];
  static const _pgNearKeys = ['ns', 'nc', 'na', 'near_vn'];
  late final Map<String, Map<String, TextEditingController>> _pg;

  static const _stDistKeys = ['ds', 'dc', 'ax', 'vn'];
  late final Map<String, Map<String, TextEditingController>> _st;
  final _stAddRe = TextEditingController();
  final _stAddLe = TextEditingController();
  final _stNsRe = TextEditingController();
  final _stNsLe = TextEditingController();
  bool _bifocal = false;
  bool _ndSeparate = false;
  bool _progressive = false;
  bool _computerUses = false;

  // Section 4: OE — flat web keys
  static const _oeFieldNames = [
    'sac',
    'lid',
    'conj',
    'cornea',
    'ac',
    'iris',
    'pupil',
    'lens',
    'em',
    'covertest',
    'other',
  ];
  // Client request (2026-10-07): matches web's actual visible row labels
  // exactly (`$oeFieldMeta[...]['label']` in primary.blade.php — web keeps
  // a separate, unused 'full' name alongside these for every field).
  static const _oeLabels = {
    'sac': 'SAC',
    'lid': 'LID',
    'conj': 'CONJ',
    'cornea': 'CORNEA',
    'ac': 'AC',
    'iris': 'IRIS',
    'pupil': 'PUPIL',
    'lens': 'LENS',
    'em': 'EM',
    'covertest': 'COVERTEST',
    'other': 'Other',
  };
  late final Map<String, TextEditingController> _oeRe;
  late final Map<String, TextEditingController> _oeLe;
  Map<String, List<ExamMasterItem>> _oeMasters = {};
  static const _oeMasterApiType = {
    'sac': 'sac',
    'lid': 'lid',
    'conj': 'conj',
    'cornea': 'cornea',
    'ac': 'ac',
    'iris': 'iris',
    'pupil': 'pupil',
    'lens': 'lens_master',
    'em': 'em',
    'covertest': 'covertest',
  };
  String _pseudophakiaOpTypeRe = 'Phaco';
  String _pseudophakiaOpTypeLe = 'Phaco';
  final _pseudophakiaExpenseRe = TextEditingController();
  final _pseudophakiaExpenseLe = TextEditingController();
  final _pseudophakiaHospitalRe = TextEditingController();
  final _pseudophakiaHospitalLe = TextEditingController();

  // Fundus
  final _fundusDiscRe = TextEditingController();
  final _fundusDiscLe = TextEditingController();
  final _fundusFrRe = TextEditingController();
  final _fundusFrLe = TextEditingController();
  final _fundusCommentRe = TextEditingController();
  final _fundusCommentLe = TextEditingController();
  // Field-chain lands here after Disc→FR are picked (see _fundusTable).
  final _fundusCommentReFocus = FocusNode();
  final _fundusCommentLeFocus = FocusNode();
  List<ExamMasterItem> _fundusDiscMasters = [];
  List<ExamMasterItem> _fundusFrMasters = [];
  // Pseudophakia panel's Hospital field autocompletes from the Referrers
  // master list, matching web (datalist sourced from $masters['referrers']).
  List<ExamMasterItem> _referrers = [];

  // Diagnosis
  List<ExamMasterItem> _diagnosisMasters = [];

  // Advice
  List<ExamMasterItem> _adviceMasters = [];

  // Dilate
  String _dilate = 'No';
  final _dilationTimeCtrl = TextEditingController();

  // Section 5
  final Set<int> _selectedDiagnosisIds = {};
  String _diagnosisSearch = '';
  final _adviceCtrl = TextEditingController();
  final List<_PrescRow> _medicines = [];
  bool _surgeryRecommended = false;
  List<ExamSection> get _sections => kSecondaryExamSections;

  @override
  void initState() {
    super.initState();
    _currentPatient = widget.patient;
    _pg = {
      're': {
        for (final k in [..._pgDistKeys, ..._pgNearKeys])
          k: TextEditingController(),
      },
      'le': {
        for (final k in [..._pgDistKeys, ..._pgNearKeys])
          k: TextEditingController(),
      },
    };
    _st = {
      're': {for (final k in _stDistKeys) k: TextEditingController()},
      'le': {for (final k in _stDistKeys) k: TextEditingController()},
    };
    _oeRe = {for (final f in _oeFieldNames) f: TextEditingController()};
    _oeLe = {for (final f in _oeFieldNames) f: TextEditingController()};
    _loadAll();
  }

  @override
  void dispose() {
    _sheetTick.dispose();
    _historySearchCtrl.dispose();
    for (final r in _coRows) {
      r.dispose();
    }
    for (final r in _kcoRows) {
      r.dispose();
    }
    _vnRe.dispose();
    _vnLe.dispose();
    _pnvnRe.dispose();
    _pnvnLe.dispose();
    _nrvnRe.dispose();
    _nrvnLe.dispose();
    _nctIopRe.dispose();
    _nctIopLe.dispose();
    for (final e in _pg.values) {
      for (final c in e.values) c.dispose();
    }
    for (final e in _st.values) {
      for (final c in e.values) c.dispose();
    }
    _stAddRe.dispose();
    _stAddLe.dispose();
    _stNsRe.dispose();
    _stNsLe.dispose();
    for (final c in _oeRe.values) c.dispose();
    for (final c in _oeLe.values) c.dispose();
    _pseudophakiaExpenseRe.dispose();
    _pseudophakiaExpenseLe.dispose();
    _pseudophakiaHospitalRe.dispose();
    _pseudophakiaHospitalLe.dispose();
    _fundusDiscRe.dispose();
    _fundusDiscLe.dispose();
    _fundusFrRe.dispose();
    _fundusFrLe.dispose();
    _fundusCommentRe.dispose();
    _fundusCommentLe.dispose();
    _fundusCommentReFocus.dispose();
    _fundusCommentLeFocus.dispose();
    _dilationTimeCtrl.dispose();
    _adviceCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadAll() async {
    final cacheHit = ExamMastersService.instance.isCacheValid;
    if (!cacheHit)
      setState(() {
        _loading = true;
        _loadError = null;
      });

    try {
      // Step 1: Masters first (22 parallel calls) — kept separate to avoid
      // competing with exam data fetch on slow connections.
      final formData = await ExamService.instance.loadFormData();

      // Step 2: Secondary exam data fetch.
      final secondary = await ExamService.instance.loadSecondaryExam(
        widget.patient.id,
      );

      // Step 3: Primary fetch is conditional — only if no secondary exists.
      final primary = secondary == null
          ? await ExamService.instance.loadPrimaryExam(widget.patient.id)
          : null;

      final referrers = await ReferrerService.instance.fetchAll();

      if (!mounted) return;
      setState(() {
        _formData = formData;
        _oeMasters = {
          for (final f in _oeMasterApiType.keys)
            f: List<ExamMasterItem>.from(formData.masters.oeListFor(f)),
        };
        _fundusDiscMasters = List<ExamMasterItem>.from(formData.masters.disc);
        _fundusFrMasters = List<ExamMasterItem>.from(formData.masters.fr);
        _diagnosisMasters = List<ExamMasterItem>.from(
          formData.masters.diagnoses,
        );
        _adviceMasters = List<ExamMasterItem>.from(formData.masters.advices);
        _referrers = referrers
            .map(
              (r) =>
                  ExamMasterItem(id: r.id, value: r.name, isFavourite: false),
            )
            .toList();
        // Mirrors web's $currentDoctorId fallback exactly
        // (SecondaryExamController::show()) — never leaves this null, since
        // there's no picker UI for it in either app or on web (web sets it
        // via a hidden field). A previously-saved secondary exam keeps its
        // own doctor for continuity; otherwise, if you're a doctor, the
        // exam is attributed to you; else the patient's assigned doctor,
        // then you as a last resort.
        _selectedDoctorId =
            (secondary?['doctor_id'] as num?)?.toInt() ??
            (widget.user.role?.slug == 'doctor'
                ? widget.user.id
                : (widget.patient.doctor?.id ?? widget.user.id));
        if (secondary != null) {
          _prefill(secondary);
          _prefillSource = 'secondary';
        } else if (primary != null) {
          _prefill(primary);
          _prefillSource = 'primary';
        }
        _refreshFilledFlags();
        _loading = false;
        _loadError = null;
      });

      // Stale disk-cache guard: if diagnoses came back empty but other masters
      // loaded fine, the cache is corrupt/old — force a fresh network pull.
      if (formData.masters.diagnoses.isEmpty &&
          formData.masters.vn.isNotEmpty) {
        _refreshMasters();
      }
    } catch (e) {
      if (mounted)
        setState(() {
          _loadError = e.toString();
          _loading = false;
        });
    }
  }

  void _prefill(Map<String, dynamic> exam) {
    final raw = exam['exam_data'];
    Map<String, dynamic> ed = {};
    if (raw is Map<String, dynamic>) {
      ed = raw;
    } else if (raw is String && raw.isNotEmpty) {
      try {
        ed = jsonDecode(raw) as Map<String, dynamic>? ?? {};
      } catch (_) {}
    }

    final historyStr = ed['history'] as String? ?? '';
    _historyChips
      ..clear()
      ..addAll(
        historyStr.split(',').map((s) => s.trim()).where((s) => s.isNotEmpty),
      );

    for (final r in (ed['co_rows'] as List? ?? [])) {
      final m = r as Map<String, dynamic>;
      final rawSince = m['since'] as String? ?? '';
      final rawEye = m['eye'] as String? ?? '';
      _coRows.add(
        _CoRow(
          complaint: m['complaint'] as String? ?? '',
          since: rawSince.isEmpty ? '-' : rawSince,
          unit: m['unit'] as String? ?? 'Days',
          eye: rawEye.isEmpty ? '-' : rawEye,
          comment: m['comment'] as String? ?? '',
        ),
      );
    }
    for (final r in (ed['kco_rows'] as List? ?? [])) {
      final m = r as Map<String, dynamic>;
      final rawSince = m['since'] as String? ?? '';
      _kcoRows.add(
        _KcoRow(
          condition: m['condition'] as String? ?? '',
          since: rawSince.isEmpty ? '-' : rawSince,
          unit: m['unit'] as String? ?? 'Years',
          comment: m['comment'] as String? ?? '',
        ),
      );
    }

    final vd = ed['vision'] as Map<String, dynamic>? ?? {};
    _vnRe.text = vd['vn_re'] as String? ?? '';
    _vnLe.text = vd['vn_le'] as String? ?? '';
    _pnvnRe.text = vd['pnvn_re'] as String? ?? '';
    _pnvnLe.text = vd['pnvn_le'] as String? ?? '';
    _nrvnRe.text = vd['nrvn_re'] as String? ?? '';
    _nrvnLe.text = vd['nrvn_le'] as String? ?? '';

    final pgd = ed['pg'] as Map<String, dynamic>? ?? {};
    for (final eye in ['re', 'le']) {
      final ev = pgd[eye] as Map<String, dynamic>? ?? {};
      for (final k in [..._pgDistKeys, ..._pgNearKeys]) {
        _pg[eye]![k]!.text = ev[k] as String? ?? '';
      }
    }

    final std = ed['st'] as Map<String, dynamic>? ?? {};
    for (final eye in ['re', 'le']) {
      final ev = std[eye] as Map<String, dynamic>? ?? {};
      for (final k in _stDistKeys) {
        _st[eye]![k]!.text = ev[k] as String? ?? '';
      }
    }
    _stAddRe.text =
        (std['re'] as Map<String, dynamic>?)?['add'] as String? ?? '';
    _stAddLe.text =
        (std['le'] as Map<String, dynamic>?)?['add'] as String? ?? '';
    _stNsRe.text = (std['re'] as Map<String, dynamic>?)?['ns'] as String? ?? '';
    _stNsLe.text = (std['le'] as Map<String, dynamic>?)?['ns'] as String? ?? '';
    _bifocal = std['bifocal'] == true;
    _ndSeparate = std['nd_separate'] == true;
    _progressive = std['progressive'] == true;
    _computerUses = std['computer_uses'] == true;

    final nct = ed['nct'] as Map<String, dynamic>? ?? {};
    _nctIopRe.text = nct['iop_re'] as String? ?? '';
    _nctIopLe.text = nct['iop_le'] as String? ?? '';

    final oed = ed['oe'] as Map<String, dynamic>? ?? {};
    for (final f in _oeFieldNames) {
      _oeRe[f]!.text = oed['${f}_re'] as String? ?? '';
      _oeLe[f]!.text = oed['${f}_le'] as String? ?? '';
    }
    final pseudoRe = oed['pseudophakia_re'] as Map<String, dynamic>? ?? {};
    _pseudophakiaOpTypeRe = pseudoRe['operation_type'] as String? ?? 'Phaco';
    _pseudophakiaExpenseRe.text =
        pseudoRe['operation_expense'] as String? ?? '';
    _pseudophakiaHospitalRe.text = pseudoRe['hospital_name'] as String? ?? '';
    final pseudoLe = oed['pseudophakia_le'] as Map<String, dynamic>? ?? {};
    _pseudophakiaOpTypeLe = pseudoLe['operation_type'] as String? ?? 'Phaco';
    _pseudophakiaExpenseLe.text =
        pseudoLe['operation_expense'] as String? ?? '';
    _pseudophakiaHospitalLe.text = pseudoLe['hospital_name'] as String? ?? '';

    final fundus = ed['fundus'] as Map<String, dynamic>? ?? {};
    _fundusDiscRe.text = fundus['disc_re'] as String? ?? '';
    _fundusDiscLe.text = fundus['disc_le'] as String? ?? '';
    _fundusFrRe.text = fundus['fr_re'] as String? ?? '';
    _fundusFrLe.text = fundus['fr_le'] as String? ?? '';
    _fundusCommentRe.text = fundus['comment_re'] as String? ?? '';
    _fundusCommentLe.text = fundus['comment_le'] as String? ?? '';

    _dilate = ed['dilate'] as String? ?? 'No';
    _dilationTimeCtrl.text = (exam['dilation_time'] as int?)?.toString() ?? '';

    for (final d in (ed['diagnoses'] as List? ?? [])) {
      final id = d is int ? d : int.tryParse(d.toString());
      if (id != null) _selectedDiagnosisIds.add(id);
    }

    _adviceCtrl.text = ed['advice'] as String? ?? '';

    // Primary exam format: medicines in top-level prescriptions relation
    for (final m in (exam['prescriptions'] as List? ?? [])) {
      final row = m as Map<String, dynamic>;
      _medicines.add(
        _PrescRow(
          medicineId: row['medicine_id'] as int?,
          medicineName:
              (row['medicine'] as Map<String, dynamic>?)?['name'] as String? ??
              '',
          dosageId: row['dosage_id'] as int?,
          routeId: row['route_id'] as int?,
          duration: row['duration'] as String? ?? '',
          quantity: (row['quantity'] as num?)?.toInt() ?? 1,
        ),
      );
    }
    // Secondary exam format: medicines embedded in exam_data.rx
    for (final m in (ed['rx'] as List? ?? [])) {
      final row = m as Map<String, dynamic>;
      _medicines.add(
        _PrescRow(
          medicineId: row['medicine_id'] as int?,
          medicineName: row['name'] as String? ?? '',
          dosageId: row['dosage_id'] as int?,
          routeId: row['route_id'] as int?,
          duration: row['duration'] as String? ?? '',
          quantity: (row['quantity'] as num?)?.toInt() ?? 1,
        ),
      );
    }
  }

  bool _isPseudo(String eye) => (eye == 're' ? _oeRe['lens'] : _oeLe['lens'])!
      .text
      .toLowerCase()
      .contains('pseudophakia');

  // Builds exam_data from EVERY section's current in-memory value, not
  // just the active step — every Save is a full snapshot of everything
  // entered so far (across all steps, including data pre-filled from
  // Primary that's still just sitting in these controllers unsaved).
  // Previously this only ever sent the *current* step's fields, which had
  // two real consequences: (1) the Medicine step had no case at all, so
  // saving it sent an empty exam_data and the backend's `required` rule
  // rejected it ("exam data field is required"); (2) any step the user
  // never explicitly re-visited/saved was simply never transmitted, so
  // Primary-prefilled-but-unsaved sections silently vanished. Since this
  // whole multi-step form already lives in one State object, every
  // field's current value is available regardless of which step is on
  // screen — so there's no reason a save should ever be partial. See
  // APP_BUGS_ROUND5_AUDIT.md Bugs #1/#2/#3.
  Map<String, dynamic> _buildFullExamData() {
    void setIfNonEmpty(Map m, String k, String v) {
      if (v.isNotEmpty) m[k] = v;
    }

    final examData = <String, dynamic>{};

    // C/O
    examData['history'] = _historyChips.join(', ');
    examData['co_rows'] = _coRows.map((r) => r.toJson()).toList();
    // K/C/O & H/O
    examData['kco_rows'] = _kcoRows.map((r) => r.toJson()).toList();
    // Vision
    final vision = <String, dynamic>{};
    setIfNonEmpty(vision, 'vn_re', _vnRe.text.trim());
    setIfNonEmpty(vision, 'vn_le', _vnLe.text.trim());
    setIfNonEmpty(vision, 'pnvn_re', _pnvnRe.text.trim());
    setIfNonEmpty(vision, 'pnvn_le', _pnvnLe.text.trim());
    setIfNonEmpty(vision, 'nrvn_re', _nrvnRe.text.trim());
    setIfNonEmpty(vision, 'nrvn_le', _nrvnLe.text.trim());
    if (vision.isNotEmpty) examData['vision'] = vision;
    // PG
    final pg = <String, dynamic>{};
    for (final eye in ['re', 'le']) {
      final ev = <String, String>{};
      for (final k in [..._pgDistKeys, ..._pgNearKeys]) {
        final v = _pg[eye]![k]!.text.trim();
        if (v.isNotEmpty) ev[k] = v;
      }
      if (ev.isNotEmpty) pg[eye] = ev;
    }
    if (pg.isNotEmpty) examData['pg'] = pg;
    // ST
    final st = <String, dynamic>{};
    for (final eye in ['re', 'le']) {
      final ev = <String, String>{};
      for (final k in _stDistKeys) {
        final v = _st[eye]![k]!.text.trim();
        if (v.isNotEmpty) ev[k] = v;
      }
      final addVal = eye == 're' ? _stAddRe.text.trim() : _stAddLe.text.trim();
      final nsVal = eye == 're' ? _stNsRe.text.trim() : _stNsLe.text.trim();
      if (addVal.isNotEmpty) ev['add'] = addVal;
      if (nsVal.isNotEmpty) ev['ns'] = nsVal;
      // NC mirrors DC, NA mirrors AX (readonly on NEAR row per web)
      final dcVal = _st[eye]!['dc']!.text.trim();
      final axVal = _st[eye]!['ax']!.text.trim();
      if (dcVal.isNotEmpty) ev['nc'] = dcVal;
      if (axVal.isNotEmpty) ev['na'] = axVal;
      if (ev.isNotEmpty) st[eye] = ev;
    }
    if (_bifocal) st['bifocal'] = true;
    if (_ndSeparate) st['nd_separate'] = true;
    if (_progressive) st['progressive'] = true;
    if (_computerUses) st['computer_uses'] = true;
    if (st.isNotEmpty) examData['st'] = st;
    // NCT
    final nct = <String, dynamic>{};
    setIfNonEmpty(nct, 'iop_re', _nctIopRe.text.trim());
    setIfNonEmpty(nct, 'iop_le', _nctIopLe.text.trim());
    if (nct.isNotEmpty) examData['nct'] = nct;
    // O/E
    final oe = <String, dynamic>{};
    for (final f in _oeFieldNames) {
      setIfNonEmpty(oe, '${f}_re', _oeRe[f]!.text.trim());
      setIfNonEmpty(oe, '${f}_le', _oeLe[f]!.text.trim());
    }
    if (_isPseudo('re'))
      oe['pseudophakia_re'] = {
        'operation_type': _pseudophakiaOpTypeRe,
        'operation_expense': _pseudophakiaExpenseRe.text.trim(),
        'hospital_name': _pseudophakiaHospitalRe.text.trim(),
      };
    if (_isPseudo('le'))
      oe['pseudophakia_le'] = {
        'operation_type': _pseudophakiaOpTypeLe,
        'operation_expense': _pseudophakiaExpenseLe.text.trim(),
        'hospital_name': _pseudophakiaHospitalLe.text.trim(),
      };
    if (oe.isNotEmpty) examData['oe'] = oe;
    // Fundus
    final fundus = <String, dynamic>{};
    setIfNonEmpty(fundus, 'disc_re', _fundusDiscRe.text.trim());
    setIfNonEmpty(fundus, 'disc_le', _fundusDiscLe.text.trim());
    setIfNonEmpty(fundus, 'fr_re', _fundusFrRe.text.trim());
    setIfNonEmpty(fundus, 'fr_le', _fundusFrLe.text.trim());
    setIfNonEmpty(fundus, 'comment_re', _fundusCommentRe.text.trim());
    setIfNonEmpty(fundus, 'comment_le', _fundusCommentLe.text.trim());
    if (fundus.isNotEmpty) examData['fundus'] = fundus;
    // Dilate
    examData['dilate'] = _dilate;
    // Diagnosis
    examData['diagnoses'] = _selectedDiagnosisIds.toList();
    // Advice
    examData['advice'] = _adviceCtrl.text.trim();
    return examData;
  }

  void _refreshFilledFlags() {
    final ed = _buildFullExamData();
    void mark(ExamSection s, bool ok) {
      if (ok) {
        _filled.add(s);
      } else {
        _filled.remove(s);
      }
    }

    mark(
      ExamSection.co,
      (ed['co_rows'] as List).any(
        (r) => (r as Map)['complaint']?.toString().trim().isNotEmpty == true,
      ),
    );
    mark(
      ExamSection.kcoHo,
      (ed['kco_rows'] as List).any(
            (r) =>
                (r as Map)['condition']?.toString().trim().isNotEmpty == true,
          ) ||
          _historyChips.isNotEmpty,
    );
    mark(
      ExamSection.vision,
      ed['vision'] is Map && (ed['vision'] as Map).isNotEmpty,
    );
    mark(ExamSection.pg, ed['pg'] is Map && (ed['pg'] as Map).isNotEmpty);
    mark(ExamSection.st, ed['st'] is Map && (ed['st'] as Map).isNotEmpty);
    mark(ExamSection.nct, ed['nct'] is Map && (ed['nct'] as Map).isNotEmpty);
    mark(ExamSection.oe, ed['oe'] is Map && (ed['oe'] as Map).isNotEmpty);
    mark(
      ExamSection.fundus,
      ed['fundus'] is Map && (ed['fundus'] as Map).isNotEmpty,
    );
    mark(ExamSection.dilate, true);
    mark(ExamSection.diagnosis, _selectedDiagnosisIds.isNotEmpty);
    mark(
      ExamSection.medicine,
      _medicines.any(
        (m) => m.medicineId != null || m.medicineName.trim().isNotEmpty,
      ),
    );
    mark(ExamSection.advice, _adviceCtrl.text.trim().isNotEmpty);
    mark(ExamSection.surgery, _surgeryRecommended);
  }

  Future<void> _persistSecondary() async {
    final payload = <String, dynamic>{
      'doctor_id': _selectedDoctorId,
      'exam_data': _buildFullExamData(),
    };
    if (_dilate == 'Yes' && _dilationTimeCtrl.text.trim().isNotEmpty) {
      payload['dilation_time'] = int.tryParse(_dilationTimeCtrl.text.trim());
    }
    final medicines = _medicines
        .where((m) => m.medicineId != null || m.medicineName.isNotEmpty)
        .map((m) => m.toJson())
        .toList();
    if (medicines.isNotEmpty) payload['medicines'] = medicines;
    await ExamService.instance.saveSecondaryExam(widget.patient.id, payload);
    _anySaved = true;
  }

  Future<void> _commitSecondary() async {
    if (_committing) return;
    setState(() => _committing = true);
    try {
      await _persistSecondary();
      if (!mounted) return;
      setState(() => _committing = false);
      showAppSnackBar(context, 'Secondary exam saved.', isSuccess: true);
      Navigator.of(context).pop(true);
    } catch (e) {
      if (mounted) {
        setState(() => _committing = false);
        showAppSnackBar(
          context,
          e.toString(),
          isError: true,
          duration: const Duration(seconds: 4),
        );
      }
    }
  }

  /// Auto-save on every section's Done — not just the final explicit "Save
  /// Exam" — so an accidental back-navigation never loses already-entered
  /// data. Sends the same full accumulated payload the explicit save uses
  /// (never a per-section fragment — a fragment-only save was tried before
  /// and caused data loss for sections the user never explicitly
  /// re-saved). The backend upserts on patient id, so repeated calls are
  /// safe. Fired in the background (not awaited by the caller) so it never
  /// slows down the section-to-section auto-advance.
  Future<void> _autoSaveProgress() async {
    try {
      // is_final: false — tells the API this isn't the doctor's actual
      // finish, so it must not stamp the patient as "Secondary Done" if the
      // doctor backs out before completing every section.
      final payload = <String, dynamic>{
        'doctor_id': _selectedDoctorId,
        'exam_data': _buildFullExamData(),
        'is_final': false,
      };
      if (_dilate == 'Yes' && _dilationTimeCtrl.text.trim().isNotEmpty) {
        payload['dilation_time'] = int.tryParse(_dilationTimeCtrl.text.trim());
      }
      final medicines = _medicines
          .where((m) => m.medicineId != null || m.medicineName.isNotEmpty)
          .map((m) => m.toJson())
          .toList();
      if (medicines.isNotEmpty) payload['medicines'] = medicines;
      await ExamService.instance.saveSecondaryExam(widget.patient.id, payload);
      if (mounted) _anySaved = true;
    } catch (_) {
      if (mounted)
        showAppSnackBar(
          context,
          'Progress not synced — check connection.',
          isError: true,
        );
    }
  }

  /// Web parity for "Start / Continue": resumes at the first section that
  /// isn't filled yet instead of always restarting at C/O, so reopening
  /// this screen after an accidental back-navigation picks up where the
  /// user left off.
  ExamSection _nextIncompleteSection() {
    for (final s in _sections) {
      if (!_filled.contains(s)) return s;
    }
    return _sections.last;
  }

  Future<void> _openSection(
    ExamSection section, {
    bool autoOpened = false,
  }) async {
    if (_sheetOpen || _loading || _loadError != null) return;
    _autoOpenDone = false;
    setState(() {
      _activeSection = section;
      _sheetOpen = true;
    });
    final close = await showExamSectionSheet(
      context: context,
      section: section,
      listenable: _sheetTick,
      builder: (_) => _sectionBody(section),
    );
    if (!mounted) return;
    setState(() {
      _sheetOpen = false;
      _refreshFilledFlags();
      if (close == ExamSectionClose.done) _filled.add(section);
    });

    if (close != ExamSectionClose.done) {
      setState(() => _activeSection = null);
      return;
    }

    unawaited(_autoSaveProgress());

    // Dilate Done on secondary does NOT commit — just advance to Diagnosis.
    final next = nextExamSection(section, _sections);
    if (next != null) {
      await _openSection(next, autoOpened: true);
    } else {
      setState(() => _activeSection = null);
      unawaited(_showFinishChooser());
    }
  }

  Future<void> _showFinishChooser() async {
    if (!mounted || _committing) return;
    final choice = await showExamFinishChooser(
      context,
      canRecommend: PermissionService.instance.can(Perm.otSurgeryRecommend),
    );
    if (!mounted || choice == null) return;
    if (choice == ExamFinishChoice.recommendOt) {
      final ok = await showRecommendSurgerySheet(
        context,
        patient: widget.patient,
        diagnosisHint: _diagnosisHintText(),
        beforeRecommend: _persistSecondary,
      );
      if (ok == true && mounted) Navigator.of(context).pop(true);
      return;
    }
    await _commitSecondary();
  }

  Widget _sectionBody(ExamSection section) {
    switch (section) {
      case ExamSection.co:
        return _coBody();
      case ExamSection.kcoHo:
        return _kcoHoBody();
      case ExamSection.vision:
        return _visionBody();
      case ExamSection.pg:
        return _pgBody();
      case ExamSection.st:
        return _stBody();
      case ExamSection.nct:
        return _nctBody();
      case ExamSection.oe:
        return _oeBody();
      case ExamSection.fundus:
        return _fundusBody();
      case ExamSection.dilate:
        return _dilateBody();
      case ExamSection.diagnosis:
        return _diagnosisBody();
      case ExamSection.medicine:
        return _medicineBody();
      case ExamSection.advice:
        return _adviceBody();
      case ExamSection.surgery:
        return _surgeryBody();
    }
  }

  Widget _iopBadge(String label, Color color) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
    decoration: BoxDecoration(
      color: color.withValues(alpha: 0.1),
      borderRadius: BorderRadius.circular(AppRadius.xl),
      border: Border.all(color: color.withValues(alpha: 0.4)),
    ),
    child: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 8,
          height: 8,
          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
        ),
        const SizedBox(width: 6),
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            color: color,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    ),
  );

  // ── Favourite toggle helpers ──────────────────────────────────────────────────

  Future<void> _refreshMasters() async {
    final masters = await ExamMastersService.instance.fetchAll(
      forceRefresh: true,
    );
    if (!mounted) return;
    setState(() {
      _formData = ExamFormData(
        dosages: _formData?.dosages ?? [],
        routes: _formData?.routes ?? [],
        masters: masters,
        medGroups: _formData?.medGroups ?? [],
        medicines: _formData?.medicines ?? [],
      );
      _oeMasters = {
        for (final f in _oeMasterApiType.keys)
          f: List<ExamMasterItem>.from(masters.oeListFor(f)),
      };
      _fundusDiscMasters = List<ExamMasterItem>.from(masters.disc);
      _fundusFrMasters = List<ExamMasterItem>.from(masters.fr);
      _diagnosisMasters = List<ExamMasterItem>.from(masters.diagnoses);
      _adviceMasters = List<ExamMasterItem>.from(masters.advices);
    });
  }

  Future<void> _toggleDiagnosisFav(ExamMasterItem d) async {
    try {
      final newFav = await SimpleMasterService.instance.toggleFavourite(
        'masters/detail/diagnoses',
        d.id,
      );
      final idx = _diagnosisMasters.indexWhere((e) => e.id == d.id);
      if (idx >= 0 && mounted) {
        setState(() {
          _diagnosisMasters[idx] = ExamMasterItem(
            id: d.id,
            value: d.value,
            isFavourite: newFav,
          );
        });
      }
    } catch (e) {
      debugPrint('[Diagnosis] toggleFav error: $e');
    }
  }

  Future<void> _toggleFavourite(String masterType, ExamMasterItem item) async {
    try {
      await SimpleMasterService.instance.toggleFavourite(
        'masters/detail/$masterType',
        item.id,
      );
      await _refreshMasters();
    } catch (e) {
      debugPrint('[Exam] toggleFavourite error: $e');
    }
  }

  // ── Step builder methods ──────────────────────────────────────────────────────

  /// Web parity (getCoModalFocusTarget): the section's first-open auto-open
  /// targets the last row with an empty complaint, or — since apps have no
  /// standalone top search bar like web's #coSearch — auto-adds a fresh
  /// blank row when every row is already filled (or none exist), then
  /// targets that one instead.
  _CoRow? _coAutoOpenTarget() {
    if (_autoOpenDone) return null;
    for (final r in _coRows.reversed) {
      if (r.complaint.trim().isEmpty) return r;
    }
    final row = _CoRow();
    _coRows.add(row);
    return row;
  }

  Widget _coBody() {
    final favs = ExamMastersData.sortedFavFirst(
      _formData?.masters.chiefComplaints ?? [],
    ).where((c) => c.isFavourite).toList();
    final autoTarget = _coAutoOpenTarget();
    return Column(
      children: [
        if (favs.isNotEmpty)
          _buildFavouritesSection(
            favs,
            onUnfavourite: (item) => _toggleFavourite('complaints', item),
          ),
        _sectionHeader(
          'Complaints (C/O)',
          trailing: _addBtn(() => setState(() => _coRows.add(_CoRow()))),
        ),
        if (_coRows.isEmpty)
          _emptyHint('Tap + to add a complaint')
        else
          ..._coRows.asMap().entries.map(
            (e) => _coRow(
              e.key,
              e.value,
              autoOpen: identical(e.value, autoTarget),
            ),
          ),
      ],
    );
  }

  Widget _buildFavouritesSection(
    List<ExamMasterItem> favs, {
    required void Function(ExamMasterItem) onUnfavourite,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
      decoration: BoxDecoration(
        color: Colors.amber.shade50,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: Colors.amber.shade200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.star_rounded, size: 13, color: Colors.amber.shade700),
              const SizedBox(width: 6),
              Text(
                'FAVOURITES — TAP TO ADD',
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.w800,
                  color: Colors.amber.shade800,
                  letterSpacing: 0.5,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 6,
            runSpacing: 6,
            children: favs
                .map(
                  (item) => Container(
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(AppRadius.xl),
                      border: Border.all(color: Colors.amber.shade300),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        PressScaleWrapper(
                          onTap: () => setState(
                            () => _coRows.add(_CoRow(complaint: item.value)),
                          ),
                          child: Padding(
                            padding: const EdgeInsets.only(
                              left: 10,
                              top: 5,
                              bottom: 5,
                              right: 4,
                            ),
                            child: Text(
                              item.value,
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                                color: Colors.amber.shade900,
                              ),
                            ),
                          ),
                        ),
                        PressScaleWrapper(
                          onTap: () => onUnfavourite(item),
                          child: Padding(
                            padding: const EdgeInsets.only(
                              right: 8,
                              top: 5,
                              bottom: 5,
                            ),
                            child: Icon(
                              Icons.star_rounded,
                              size: 13,
                              color: Colors.amber.shade600,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                )
                .toList(),
          ),
        ],
      ),
    );
  }

  /// See _coAutoOpenTarget — same fallback (auto-add a blank row when none
  /// is empty) for the K/C/O section's first-open auto-open.
  _KcoRow? _kcoAutoOpenTarget() {
    if (_autoOpenDone) return null;
    for (final r in _kcoRows.reversed) {
      if (r.condition.trim().isEmpty) return r;
    }
    final row = _KcoRow();
    _kcoRows.add(row);
    return row;
  }

  Widget _kcoHoBody() {
    final kcoFavs = (_formData?.masters.kcos ?? [])
        .where((i) => i.isFavourite)
        .toList();
    final autoTarget = _kcoAutoOpenTarget();
    return Column(
      children: [
        if (kcoFavs.isNotEmpty)
          Container(
            margin: const EdgeInsets.only(bottom: 10),
            padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
            decoration: BoxDecoration(
              color: Colors.amber.shade50,
              borderRadius: BorderRadius.circular(AppRadius.md),
              border: Border.all(color: Colors.amber.shade200),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(
                      Icons.star_rounded,
                      size: 13,
                      color: Colors.amber.shade700,
                    ),
                    const SizedBox(width: 6),
                    Text(
                      'FAVOURITES — TAP TO ADD',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w800,
                        color: Colors.amber.shade800,
                        letterSpacing: 0.5,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 6,
                  runSpacing: 6,
                  children: kcoFavs
                      .map(
                        (item) => Container(
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(AppRadius.xl),
                            border: Border.all(color: Colors.amber.shade300),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              PressScaleWrapper(
                                onTap: () => setState(
                                  () => _kcoRows.add(
                                    _KcoRow(condition: item.value),
                                  ),
                                ),
                                child: Padding(
                                  padding: const EdgeInsets.only(
                                    left: 10,
                                    top: 5,
                                    bottom: 5,
                                    right: 4,
                                  ),
                                  child: Text(
                                    item.value,
                                    style: TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w600,
                                      color: Colors.amber.shade900,
                                    ),
                                  ),
                                ),
                              ),
                              PressScaleWrapper(
                                onTap: () => _toggleFavourite('kcos', item),
                                child: Padding(
                                  padding: const EdgeInsets.only(
                                    right: 8,
                                    top: 5,
                                    bottom: 5,
                                  ),
                                  child: Icon(
                                    Icons.star_rounded,
                                    size: 13,
                                    color: Colors.amber.shade600,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      )
                      .toList(),
                ),
              ],
            ),
          ),
        _sectionHeader(
          'Known Conditions (K/CO)',
          trailing: _addBtn(() => setState(() => _kcoRows.add(_KcoRow()))),
        ),
        if (_kcoRows.isEmpty)
          _emptyHint('Tap + to add a known condition')
        else
          ..._kcoRows.asMap().entries.map(
            (e) => _kcoRow(
              e.key,
              e.value,
              autoOpen: identical(e.value, autoTarget),
            ),
          ),
        const SizedBox(height: 12),
        _sectionHeader('History (H/O)'),
        _buildHistoryChipsSection(),
      ],
    );
  }

  // Vision field-chain auto-advance — matches web's advanceVisionField:
  // VN RE → Pinhole RE → Near VN RE → VN LE → Pinhole LE → Near VN LE.
  // Built bottom-up so each opener can reference the next one already
  // assembled (closures capture the variable, not its value at creation
  // time, so forward-declared `late` openers resolve fine once assigned).
  Future<void> Function() _visionOpener(
    TextEditingController ctrl,
    String masterKey,
    Future<void> Function()? onDone,
  ) {
    return () async {
      final items = _formData?.masters.oeListFor(masterKey) ?? [];
      if (items.isEmpty) return;
      final picked = await _pickMasterValue(items);
      if (picked == null || !mounted) return;
      setState(() => ctrl.text = picked);
      if (onDone != null) {
        await Future.delayed(const Duration(milliseconds: 150));
        if (mounted) onDone();
      }
    };
  }

  Widget _visionBody() {
    final openNearLe = _visionOpener(_nrvnLe, 'nrvn', null);
    final openPinLe = _visionOpener(_pnvnLe, 'pnvn', openNearLe);
    final openVnLe = _visionOpener(_vnLe, 'vn', openPinLe);
    final openNearRe = _visionOpener(_nrvnRe, 'nrvn', openVnLe);
    final openPinRe = _visionOpener(_pnvnRe, 'pnvn', openNearRe);
    final openVnRe = _visionOpener(_vnRe, 'vn', openPinRe);
    _autoOpenFirstField(ExamSection.vision, openVnRe);

    return Column(
      children: [
        _sectionHeader('Visual Acuity'),
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(AppRadius.md),
            border: Border.all(
              color: AppColors.primary.withValues(alpha: 0.12),
            ),
          ),
          child: Column(
            children: [
              Container(
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.06),
                  borderRadius: BorderRadius.vertical(top: Radius.circular(11)),
                ),
                child: Row(
                  children: [
                    const SizedBox(width: 40),
                    _thCell('VN (Dist)'),
                    _thCell('Pinhole'),
                    _thCell('Near VN'),
                  ],
                ),
              ),
              _visionEyeRow(
                'RE',
                true,
                _vnRe,
                _pnvnRe,
                _nrvnRe,
                openVnRe,
                openPinRe,
                openNearRe,
              ),
              _visionEyeRow(
                'LE',
                false,
                _vnLe,
                _pnvnLe,
                _nrvnLe,
                openVnLe,
                openPinLe,
                openNearLe,
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _pgBody() => Column(
    children: [_sectionHeader('PG — Prescription Glasses'), _pgTable()],
  );

  Widget _stBody() => Column(
    children: [
      _sectionHeader('ST — Subjective Test'),
      _stTable(),
      const SizedBox(height: 8),
      _stCheckboxRow(),
    ],
  );

  Widget _nctBody() {
    final openRe = _nctOpenRe();
    _autoOpenFirstField(ExamSection.nct, openRe);
    return Column(
      children: [
        _sectionHeader('NCT — Non-Contact Tonometry'),
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(AppRadius.md),
            border: Border.all(
              color: AppColors.primary.withValues(alpha: 0.12),
            ),
            boxShadow: [
              BoxShadow(
                color: AppColors.primary.withValues(alpha: 0.05),
                blurRadius: 6,
                offset: Offset(0, 2),
              ),
            ],
          ),
          child: Column(
            children: [
              // Navy header: RE | LE
              Container(
                decoration: BoxDecoration(
                  color: AppColors.primary,
                  borderRadius: BorderRadius.vertical(top: Radius.circular(11)),
                ),
                child: Row(
                  children: [
                    const SizedBox(width: 80),
                    Expanded(
                      child: Padding(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(
                              Icons.remove_red_eye_rounded,
                              color: Colors.white,
                              size: 12,
                            ),
                            const SizedBox(width: 4),
                            const Text(
                              'Right Eye (RE)',
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                                color: Colors.white,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    Container(
                      width: 1,
                      height: 36,
                      color: Colors.white.withValues(alpha: 0.2),
                    ),
                    Expanded(
                      child: Padding(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(
                              Icons.remove_red_eye_rounded,
                              color: Colors.white,
                              size: 12,
                            ),
                            const SizedBox(width: 4),
                            const Text(
                              'Left Eye (LE)',
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                                color: Colors.white,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              // IOP row
              IntrinsicHeight(
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Container(
                      width: 80,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      decoration: const BoxDecoration(color: Color(0xFFFAFBFC)),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Text(
                            'IOP',
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                              color: Color(0xFF1e293b),
                              letterSpacing: .04,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            'mmHg',
                            style: TextStyle(
                              fontSize: 10,
                              color: AppColors.primary.withValues(alpha: 0.4),
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ],
                      ),
                    ),
                    Expanded(
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: _nctField(_nctIopRe, openRe),
                      ),
                    ),
                    Container(
                      width: 1,
                      color: AppColors.primary.withValues(alpha: 0.08),
                    ),
                    Expanded(
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: _nctField(_nctIopLe, _nctOpenLe()),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(
              color: AppColors.primary.withValues(alpha: 0.08),
            ),
          ),
          child: Wrap(
            alignment: WrapAlignment.spaceAround,
            spacing: 8,
            runSpacing: 6,
            children: [
              _iopBadge('Normal 10–21', const Color(0xFF22C55E)),
              _iopBadge('Borderline 22–24', const Color(0xFFF59E0B)),
              _iopBadge('High ≥25', const Color(0xFFEF4444)),
            ],
          ),
        ),
      ],
    );
  }

  Widget _oeBody() =>
      Column(children: [_sectionHeader('O/E — On Examination'), _oeTable()]);

  Widget _fundusBody() =>
      Column(children: [_sectionHeader('Fundus Examination'), _fundusTable()]);

  Widget _dilateBody() => Column(
    children: [
      _sectionHeader('Dilation'),
      Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(AppRadius.md),
          border: Border.all(color: AppColors.primary.withValues(alpha: 0.12)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Dilate patient?',
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: AppColors.primary,
              ),
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                ChoiceChip(
                  label: const Text('Yes'),
                  selected: _dilate == 'Yes',
                  selectedColor: AppColors.teal,
                  labelStyle: TextStyle(
                    color: _dilate == 'Yes' ? Colors.white : AppColors.primary,
                    fontWeight: FontWeight.w700,
                  ),
                  onSelected: (_) => setState(() {
                    _dilate = 'Yes';
                    if (_dilationTimeCtrl.text.trim().isEmpty)
                      _dilationTimeCtrl.text = '40';
                  }),
                ),
                const SizedBox(width: 8),
                ChoiceChip(
                  label: const Text('No'),
                  selected: _dilate == 'No',
                  selectedColor: AppColors.teal,
                  labelStyle: TextStyle(
                    color: _dilate == 'No' ? Colors.white : AppColors.primary,
                    fontWeight: FontWeight.w700,
                  ),
                  onSelected: (_) => setState(() {
                    _dilate = 'No';
                    _dilationTimeCtrl.clear();
                  }),
                ),
                if (_dilate == 'Yes') ...[
                  const SizedBox(width: 12),
                  SizedBox(
                    width: 90,
                    child: _examField(
                      _dilationTimeCtrl,
                      'Mins',
                      inputType: TextInputType.number,
                    ),
                  ),
                  const Text(
                    ' min',
                    style: TextStyle(fontSize: 12, color: Colors.black54),
                  ),
                ],
              ],
            ),
          ],
        ),
      ),
    ],
  );

  Widget _diagnosisBody() {
    return Column(children: [_buildDiagnosisSection()]);
  }

  Widget _medicineBody() {
    final dosages = _formData?.dosages ?? [];
    final routes = _formData?.routes ?? [];
    final groups = _formData?.medGroups ?? [];
    final medicines = _formData?.medicines ?? [];

    return Column(
      children: [
        // ── Toolbar ────────────────────────────────────────────────────────
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          decoration: BoxDecoration(
            color: AppColors.primary,
            borderRadius: BorderRadius.circular(AppRadius.md),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  const Icon(
                    Icons.medication_rounded,
                    size: 16,
                    color: Colors.white,
                  ),
                  const SizedBox(width: 6),
                  const Text(
                    'Medicines (Rx)',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w800,
                      color: Colors.white,
                    ),
                  ),
                  const Spacer(),
                  PressScaleWrapper(
                    onTap: () => setState(() => _medicines.add(_PrescRow())),
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 5,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(AppRadius.sm),
                      ),
                      child: const Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            Icons.add_rounded,
                            size: 14,
                            color: Colors.white,
                          ),
                          SizedBox(width: 4),
                          Text(
                            'Add',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              color: Colors.white,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
              if (groups.isNotEmpty) ...[
                const SizedBox(height: 8),
                DropdownButtonFormField<int>(
                  value: null,
                  isExpanded: true,
                  icon: const Icon(
                    Icons.expand_more,
                    size: 14,
                    color: Colors.white70,
                  ),
                  style: const TextStyle(fontSize: 12, color: Colors.black87),
                  hint: const Row(
                    children: [
                      Icon(
                        Icons.playlist_add_rounded,
                        size: 14,
                        color: Colors.white70,
                      ),
                      SizedBox(width: 6),
                      Text(
                        'Load a medicine group…',
                        style: TextStyle(fontSize: 12, color: Colors.white70),
                      ),
                    ],
                  ),
                  dropdownColor: Colors.white,
                  decoration: InputDecoration(
                    contentPadding: const EdgeInsets.symmetric(
                      vertical: 4,
                      horizontal: 10,
                    ),
                    filled: true,
                    fillColor: Colors.white.withValues(alpha: 0.12),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(AppRadius.sm),
                      borderSide: BorderSide(
                        color: Colors.white.withValues(alpha: 0.3),
                      ),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(AppRadius.sm),
                      borderSide: BorderSide(
                        color: Colors.white.withValues(alpha: 0.3),
                      ),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(AppRadius.sm),
                      borderSide: const BorderSide(color: Colors.white),
                    ),
                  ),
                  items: groups
                      .map(
                        (g) => DropdownMenuItem(
                          value: g.id,
                          child: Text(
                            g.name,
                            style: const TextStyle(fontSize: 12),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      )
                      .toList(),
                  onChanged: (groupId) {
                    if (groupId == null) return;
                    final group = groups.firstWhere((g) => g.id == groupId);
                    setState(() {
                      for (final item in group.items) {
                        if (item.medicineName == null ||
                            item.medicineName!.isEmpty)
                          continue;
                        _medicines.add(
                          _PrescRow(
                            medicineId: item.medicineId,
                            medicineName: item.medicineName ?? '',
                            dosageId: item.dosageId,
                            routeId: item.routeId,
                            duration: item.duration ?? '',
                            quantity: item.quantity,
                          ),
                        );
                      }
                    });
                  },
                ),
              ],
            ],
          ),
        ),
        const SizedBox(height: 8),
        if (_medicines.isEmpty)
          _emptyHint('Tap + to add a prescription')
        else
          ..._medicines.asMap().entries.map(
            (e) => _prescRow(e.key, e.value, dosages, routes, medicines),
          ),
      ],
    );
  }

  Widget _surgeryBody() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: _surgeryRecommended
            ? AppColors.green.withValues(alpha: 0.08)
            : AppColors.teal.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(
          color: (_surgeryRecommended ? AppColors.green : AppColors.teal)
              .withValues(alpha: 0.35),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(
                _surgeryRecommended
                    ? Icons.check_circle_rounded
                    : Icons.medical_services_rounded,
                size: 22,
                color: _surgeryRecommended ? AppColors.green : AppColors.teal,
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  _surgeryRecommended
                      ? 'Surgery recommended — sent to OT Counsellor'
                      : 'Does this patient need surgery?',
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w800,
                    color: AppColors.darkNavy,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          const Text(
            'Recommend surgery here to hand this patient over to the OT Counsellor. If no surgery is needed, just tap Done to skip.',
            style: TextStyle(
              fontSize: 12,
              color: AppColors.textSecondary,
              height: 1.4,
            ),
          ),
          const SizedBox(height: 14),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () async {
                final ok = await showRecommendSurgerySheet(
                  context,
                  patient: widget.patient,
                  diagnosisHint: _diagnosisHintText(),
                );
                if (ok == true && mounted) {
                  setState(() => _surgeryRecommended = true);
                  _sheetTick.value++;
                }
              },
              icon: const Icon(Icons.medical_services_rounded, size: 18),
              label: Text(
                _surgeryRecommended
                    ? 'Update Recommendation'
                    : 'Recommend Surgery',
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.teal,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _adviceBody() {
    return Column(
      children: [
        AdviceLibraryPicker(
          adviceCtrl: _adviceCtrl,
          masters: _adviceMasters,
          onMastersChanged: (updated) =>
              setState(() => _adviceMasters = updated),
        ),
        const SizedBox(height: 12),

        // ── Advice textarea with char counter ─────────────────────────────────────
        _sectionHeader('Advice'),
        ValueListenableBuilder<TextEditingValue>(
          valueListenable: _adviceCtrl,
          builder: (_, val, __) => Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              TextFormField(
                controller: _adviceCtrl,
                maxLines: 5,
                maxLength: 2000,
                keyboardType: TextInputType.multiline,
                style: const TextStyle(fontSize: 13),
                decoration: _examDeco(
                  'Clinical advice, post-operative care, follow-up instructions...',
                ).copyWith(counterText: ''),
              ),
              const SizedBox(height: 2),
              Text(
                '${val.text.length} / 2000',
                style: TextStyle(
                  fontSize: 10,
                  color: val.text.length > 1800
                      ? Colors.red
                      : AppColors.primary.withValues(alpha: 0.4),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) Navigator.of(context).pop(_anySaved);
      },
      child: Scaffold(
        backgroundColor: const Color(0xFFEBF5FB),
        body: Column(
          children: [
            _buildAppBar(),
            if (_prefillSource == 'primary') _primaryDataBanner(),
            Expanded(
              child: _loading
                  ? const AppSkeletonList()
                  : _loadError != null
                  ? _buildError()
                  : Column(
                      children: [
                        ExamChipBar(
                          sections: _sections,
                          active: _activeSection,
                          filled: _filled,
                          onSelect: (s) => _openSection(s),
                          activeColor: AppColors.teal,
                          secondaryFrom: ExamSection.diagnosis,
                        ),
                        Expanded(
                          child: Padding(
                            padding: const EdgeInsets.fromLTRB(12, 4, 12, 8),
                            child: ExamLiveSlip(
                              examData: _buildFullExamData(),
                              showDiagnosis: true,
                              showMedicine: true,
                              showAdvice: true,
                              prescriptions: _medicines
                                  .map((m) => m.toJson())
                                  .toList(),
                              dosageLabels: _dosageLabels(),
                              routeLabels: _routeLabels(),
                              diagnosisLabels: _diagnosisLabels(),
                            ),
                          ),
                        ),
                      ],
                    ),
            ),
            if (_committing) const LinearProgressIndicator(minHeight: 2),
            if (!_loading && _loadError == null)
              SafeArea(
                top: false,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
                  child: ElevatedButton.icon(
                    onPressed: _committing ? null : _commitSecondary,
                    icon: _committing
                        ? const SizedBox(
                            width: 14,
                            height: 14,
                            child: CircularProgressIndicator(
                              color: Colors.white,
                              strokeWidth: 2,
                            ),
                          )
                        : const Icon(
                            Icons.save_rounded,
                            size: 16,
                            color: Colors.white,
                          ),
                    label: Text(
                      _committing ? 'Saving…' : 'Save Exam',
                      style: const TextStyle(
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                      ),
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.teal,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                  ),
                ),
              ),
          ],
        ),
      ), // Scaffold
    ); // PopScope
  }

  List<String> _diagnosisLabels() => _diagnosisMasters
      .where((d) => _selectedDiagnosisIds.contains(d.id))
      .map((d) => d.value)
      .toList();

  Map<int, String> _dosageLabels() => {
    for (final d in _formData?.dosages ?? []) d.id: d.name,
  };

  Map<int, String> _routeLabels() => {
    for (final r in _formData?.routes ?? []) r.id: r.name,
  };

  Widget _primaryDataBanner() => Container(
    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
    color: Colors.amber.shade50,
    child: Row(
      children: [
        Icon(
          Icons.info_outline_rounded,
          size: 16,
          color: Colors.amber.shade800,
        ),
        const SizedBox(width: 8),
        const Expanded(
          child: Text(
            'Pre-filled from Primary Examination — review and update',
            style: TextStyle(fontSize: 11, color: Colors.black87),
          ),
        ),
      ],
    ),
  );

  Widget _buildAppBar() {
    final p = _currentPatient;
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [AppColors.teal, Color(0xFF16A085)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(8, 8, 16, 0),
          child: Row(
            children: [
              IconButton(
                icon: const Icon(Icons.arrow_back_rounded, color: Colors.white),
                onPressed: () => Navigator.of(context).pop(_anySaved),
              ),
              const SizedBox(width: 4),
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(
                  Icons.visibility_rounded,
                  color: Colors.white,
                  size: 20,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Secondary Examination',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '${p.fullName}  ·  MRD: ${p.patientCode}',
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.75),
                        fontSize: 10,
                      ),
                    ),
                  ],
                ),
              ),
              // Web pull 2026-09-29 — `exam/_patient_info_modal.blade.php`'s
              // "Patient Details" button (WEB_VIEW_BUTTONS_PARITY_AUDIT.md #2).
              if (widget.user.role?.slug == 'doctor')
                IconButton(
                  icon: const Icon(Icons.badge_outlined, color: Colors.white),
                  tooltip: 'Patient Details',
                  onPressed: () => showPatientDetailsModal(
                    context,
                    p,
                    onSaved: (updated) =>
                        setState(() => _currentPatient = updated),
                  ),
                ),
              if (!_loading && _loadError == null)
                IconButton(
                  icon: const Icon(
                    Icons.play_arrow_rounded,
                    color: Colors.white,
                  ),
                  tooltip: 'Start / Continue',
                  onPressed: () => _openSection(_nextIncompleteSection()),
                ),
              if (PermissionService.instance.can(Perm.otSurgeryRecommend))
                IconButton(
                  icon: const Icon(
                    Icons.medical_services_rounded,
                    color: Colors.white,
                  ),
                  tooltip: 'Recommend Surgery',
                  onPressed: () async {
                    final ok = await showRecommendSurgerySheet(
                      context,
                      patient: widget.patient,
                      diagnosisHint: _diagnosisHintText(),
                      beforeRecommend: _persistSecondary,
                    );
                    if (ok == true && mounted) Navigator.of(context).pop(true);
                  },
                ),
            ],
          ),
        ),
      ),
    );
  }

  String? _diagnosisHintText() {
    if (_selectedDiagnosisIds.isEmpty) return null;
    final joined = _diagnosisMasters
        .where((d) => _selectedDiagnosisIds.contains(d.id))
        .map((d) => d.value)
        .join(', ');
    // Web truncates this exact same way before ever putting it in the
    // field (`_recommend_surgery_modal.blade.php`: `.slice(0, 255)`) — the
    // backend's diagnosis_hint column/validation is max:255 on both
    // platforms, web just never lets the value get that long in the first
    // place instead of letting the user hit a validation error on submit.
    return joined.length > 255 ? joined.substring(0, 255) : joined;
  }

  Widget _buildError() => AppErrorState(
    message: _loadError ?? 'Failed to load exam.',
    onRetry: _loadAll,
  );

  // ── History chips ─────────────────────────────────────────────────────────────

  Widget _buildHistoryChipsSection() {
    final hnoItems = _formData?.masters.hno ?? [];
    final favs = hnoItems.where((i) => i.isFavourite).toList();
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.12)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (_historyChips.isNotEmpty) ...[
            Wrap(
              spacing: 6,
              runSpacing: 6,
              children: _historyChips
                  .map(
                    (chip) => Chip(
                      label: Text(chip, style: const TextStyle(fontSize: 12)),
                      deleteIcon: const Icon(Icons.close, size: 14),
                      onDeleted: () =>
                          setState(() => _historyChips.remove(chip)),
                      backgroundColor: AppColors.primary.withValues(
                        alpha: 0.08,
                      ),
                      deleteIconColor: Colors.red,
                      materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      padding: const EdgeInsets.symmetric(horizontal: 4),
                    ),
                  )
                  .toList(),
            ),
            const SizedBox(height: 8),
          ],
          if (favs.isNotEmpty) ...[
            Wrap(
              spacing: 6,
              runSpacing: 6,
              children: favs.map((item) {
                final selected = _historyChips.contains(item.value);
                return Container(
                  decoration: BoxDecoration(
                    color: selected ? Colors.amber.shade50 : Colors.white,
                    borderRadius: BorderRadius.circular(AppRadius.xl),
                    border: Border.all(color: Colors.amber.shade300),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      PressScaleWrapper(
                        onTap: () {
                          if (!selected)
                            setState(() => _historyChips.add(item.value));
                        },
                        child: Padding(
                          padding: const EdgeInsets.only(
                            left: 10,
                            top: 5,
                            bottom: 5,
                            right: 4,
                          ),
                          child: Text(
                            item.value,
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.amber.shade900,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ),
                      PressScaleWrapper(
                        onTap: () => _toggleFavourite('hno', item),
                        child: Padding(
                          padding: const EdgeInsets.only(
                            right: 8,
                            top: 5,
                            bottom: 5,
                          ),
                          child: Icon(
                            Icons.star_rounded,
                            size: 13,
                            color: Colors.amber.shade600,
                          ),
                        ),
                      ),
                    ],
                  ),
                );
              }).toList(),
            ),
            const SizedBox(height: 8),
          ],
          _MasterAutocompleteField(
            items: hnoItems,
            initialValue: '',
            hint: 'Search or type history item...',
            icon: Icons.history_edu_rounded,
            color: AppColors.primary,
            onChanged: (_) {},
            onToggleFavourite: (item) => _toggleFavourite('hno', item),
            onSelected: (val) {
              if (!_historyChips.contains(val)) {
                setState(() => _historyChips.add(val));
              }
            },
          ),
        ],
      ),
    );
  }

  // Field-chain auto-advance for C/O and K/C/O rows — matches web's
  // advanceExamRowField: complaint/condition → since → unit → (eye) →
  // comment. Each picker opens the next one on completion, ending on a
  // plain focus request for the comment text field.
  static const _sinceOptions = [
    '-',
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '10',
  ];
  static const _unitOptions = ['Days', 'Weeks', 'Months', 'Years', 'Longtime'];
  static const _eyeOptions = ['-', 'RE', 'LE', 'Both', 'OU'];

  Widget _coRow(int idx, _CoRow row, {bool autoOpen = false}) {
    final safeEye = _eyeOptions.contains(row.eye) ? row.eye : '-';
    final safeSince = _sinceOptions.contains(row.since) ? row.since : '-';

    Future<void> pickEye() async {
      final v = await _pickFromMiniList('Eye', _eyeOptions, safeEye);
      if (v == null) return;
      setState(() => row.eye = v);
      row.commentFocus.requestFocus();
    }

    Future<void> pickUnit() async {
      final v = await _pickFromMiniList('Unit', _unitOptions, row.unit);
      if (v == null) return;
      setState(() => row.unit = v);
      await Future.delayed(const Duration(milliseconds: 150));
      if (mounted) pickEye();
    }

    Future<void> pickSince() async {
      final v = await _pickFromMiniList('Since', _sinceOptions, safeSince);
      if (v == null) return;
      setState(() => row.since = v);
      await Future.delayed(const Duration(milliseconds: 150));
      if (mounted) pickUnit();
    }

    return Container(
      key: ValueKey('co_$idx'),
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.12)),
      ),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: _MasterAutocompleteField(
                  key: ValueKey('co_complaint_$idx'),
                  items: _formData?.masters.chiefComplaints ?? [],
                  initialValue: row.complaint,
                  hint: 'Complaint',
                  icon: Icons.report_problem_outlined,
                  color: AppColors.primary,
                  onChanged: (v) => setState(() => row.complaint = v),
                  onToggleFavourite: (item) =>
                      _toggleFavourite('complaints', item),
                  exposeOpener: autoOpen
                      ? (open) => _autoOpenFirstField(
                          ExamSection.co,
                          () async => open(),
                        )
                      : null,
                  onFinalPick: (_) async {
                    await Future.delayed(const Duration(milliseconds: 150));
                    if (mounted) pickSince();
                  },
                ),
              ),
              const SizedBox(width: 8),
              _miniDropdown(value: safeEye, onTap: pickEye, width: 68),
              const SizedBox(width: 4),
              PressScaleWrapper(
                onTap: () => setState(() => _coRows.removeAt(idx).dispose()),
                child: const Icon(
                  Icons.remove_circle_rounded,
                  color: AppColors.red,
                  size: 20,
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Row(
            children: [
              _miniDropdown(value: safeSince, onTap: pickSince, width: 64),
              const SizedBox(width: 6),
              _miniDropdown(value: row.unit, onTap: pickUnit, width: 110),
              const SizedBox(width: 6),
              Expanded(
                child: _miniField(
                  initialValue: row.comment,
                  hint: 'Comment',
                  onChanged: (v) => row.comment = v,
                  focusNode: row.commentFocus,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _kcoRow(int idx, _KcoRow row, {bool autoOpen = false}) {
    final safeSince = _sinceOptions.contains(row.since) ? row.since : '-';

    Future<void> pickUnit() async {
      final v = await _pickFromMiniList('Unit', _unitOptions, row.unit);
      if (v == null) return;
      setState(() => row.unit = v);
      row.commentFocus.requestFocus();
    }

    Future<void> pickSince() async {
      final v = await _pickFromMiniList('Since', _sinceOptions, safeSince);
      if (v == null) return;
      setState(() => row.since = v);
      await Future.delayed(const Duration(milliseconds: 150));
      if (mounted) pickUnit();
    }

    return Container(
      key: ValueKey('kco_$idx'),
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.12)),
      ),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: _MasterAutocompleteField(
                  key: ValueKey('kco_condition_$idx'),
                  items: _formData?.masters.kcos ?? [],
                  initialValue: row.condition,
                  hint: 'Known condition',
                  icon: Icons.medical_information_outlined,
                  color: AppColors.primary,
                  onChanged: (v) => setState(() => row.condition = v),
                  onToggleFavourite: (item) => _toggleFavourite('kcos', item),
                  exposeOpener: autoOpen
                      ? (open) => _autoOpenFirstField(
                          ExamSection.kcoHo,
                          () async => open(),
                        )
                      : null,
                  onFinalPick: (_) async {
                    await Future.delayed(const Duration(milliseconds: 150));
                    if (mounted) pickSince();
                  },
                ),
              ),
              const SizedBox(width: 4),
              PressScaleWrapper(
                onTap: () => setState(() => _kcoRows.removeAt(idx).dispose()),
                child: const Icon(
                  Icons.remove_circle_rounded,
                  color: AppColors.red,
                  size: 20,
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Row(
            children: [
              _miniDropdown(value: safeSince, onTap: pickSince, width: 64),
              const SizedBox(width: 6),
              _miniDropdown(value: row.unit, onTap: pickUnit, width: 110),
              const SizedBox(width: 6),
              Expanded(
                child: _miniField(
                  initialValue: row.comment,
                  hint: 'Comment',
                  onChanged: (v) => row.comment = v,
                  focusNode: row.commentFocus,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  // ── Vision ────────────────────────────────────────────────────────────────────

  Widget _visionEyeRow(
    String label,
    bool isRe,
    TextEditingController a,
    TextEditingController b,
    TextEditingController c,
    Future<void> Function() openA,
    Future<void> Function() openB,
    Future<void> Function() openC,
  ) {
    return Column(
      children: [
        const Divider(height: 1),
        Padding(
          padding: const EdgeInsets.all(8),
          child: Row(
            children: [
              SizedBox(width: 32, child: Center(child: _eyeLabel(label, isRe))),
              const SizedBox(width: 8),
              _masterVisionCell(a, openA),
              _masterVisionCell(b, openB),
              _masterVisionCell(c, openC),
            ],
          ),
        ),
      ],
    );
  }

  /// Favourites-first bottom-sheet picker shared by Vision/PG/ST-style
  /// master-list fields. Returns the picked value, or null if dismissed.
  // Client request (2026-10-07): Axis/VN used to open as a single-column
  // scrolling list — now a chip grid matching SPH/CYL's own picker
  // (_showSphCylPicker) instead of looking like a completely different
  // control for a sibling field on the same row. Also bigger to start
  // (0.72 vs the old 0.5 initial size) so less scrolling is needed, same
  // "less scroll" request that applies to _showSphCylPicker below. Chips
  // size to their own text (not a fixed box like SPH/CYL's) since VN
  // values vary a lot in length ("6/6" vs "HM/PL/PR inacc."), unlike
  // Axis's uniform "N°" values.
  Future<String?> _pickMasterValue(
    List<ExamMasterItem> items, {
    String? current,
  }) {
    String query = '';
    return showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.xl)),
      ),
      builder: (_) => StatefulBuilder(
        builder: (ctx, ss) {
          final filtered = query.isEmpty
              ? items
              : items
                    .where(
                      (i) => i.value.toLowerCase().contains(
                        query.toLowerCase(),
                      ),
                    )
                    .toList();
          final favs = filtered.where((i) => i.isFavourite).toList();
          final others = filtered.where((i) => !i.isFavourite).toList();

          Widget chip(ExamMasterItem item) {
            final sel = item.value == current;
            return PressScaleWrapper(
              onTap: () => Navigator.pop(context, item.value),
              child: Container(
                constraints: const BoxConstraints(minWidth: 52),
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 9,
                ),
                decoration: BoxDecoration(
                  color: sel
                      ? AppColors.primary
                      : (item.isFavourite
                            ? Colors.amber.shade50
                            : Colors.grey.shade100),
                  borderRadius: BorderRadius.circular(AppRadius.sm),
                  border: Border.all(
                    color: sel
                        ? AppColors.primary
                        : (item.isFavourite
                              ? Colors.amber.shade300
                              : Colors.grey.shade300),
                  ),
                ),
                child: Text(
                  item.value,
                  textAlign: TextAlign.center,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: sel
                        ? Colors.white
                        : (item.isFavourite
                              ? Colors.amber.shade800
                              : AppColors.primary.withValues(alpha: 0.85)),
                  ),
                ),
              ),
            );
          }

          return DraggableScrollableSheet(
            expand: false,
            initialChildSize: 0.72,
            minChildSize: 0.4,
            maxChildSize: 0.95,
            builder: (_, sc) => Column(
              children: [
                Container(
                  margin: const EdgeInsets.only(top: 10, bottom: 6),
                  width: 36,
                  height: 4,
                  decoration: BoxDecoration(
                    color: AppColors.primary.withValues(alpha: 0.2),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                  child: Text(
                    'Select value',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w800,
                      color: AppColors.primary,
                    ),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
                  child: TextField(
                    onChanged: (v) => ss(() => query = v),
                    decoration: InputDecoration(
                      hintText: 'Search…',
                      hintStyle: const TextStyle(fontSize: 12),
                      prefixIcon: const Icon(Icons.search_rounded, size: 18),
                      isDense: true,
                      contentPadding: const EdgeInsets.symmetric(
                        vertical: 8,
                        horizontal: 10,
                      ),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(AppRadius.sm),
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: favs.isEmpty && others.isEmpty
                      ? const Center(
                          child: Text(
                            'No results',
                            style: TextStyle(fontSize: 12),
                          ),
                        )
                      : SingleChildScrollView(
                          controller: sc,
                          padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              if (favs.isNotEmpty) ...[
                                Text(
                                  '★ Favourites',
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w700,
                                    color: Colors.amber.shade700,
                                  ),
                                ),
                                const SizedBox(height: 6),
                                Wrap(
                                  spacing: 6,
                                  runSpacing: 6,
                                  children: favs.map(chip).toList(),
                                ),
                                const SizedBox(height: 12),
                              ],
                              if (others.isNotEmpty)
                                Wrap(
                                  spacing: 6,
                                  runSpacing: 6,
                                  children: others.map(chip).toList(),
                                ),
                            ],
                          ),
                        ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _masterVisionCell(
    TextEditingController ctrl,
    Future<void> Function() openPicker,
  ) {
    return Expanded(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 4),
        child: TextFormField(
          controller: ctrl,
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w500),
          onTap: openPicker,
          readOnly: false,
          decoration: InputDecoration(
            hintText: '—',
            hintStyle: TextStyle(
              color: AppColors.primary.withValues(alpha: 0.3),
              fontSize: 13,
            ),
            suffixIcon: IconButton(
              icon: Icon(
                Icons.arrow_drop_down_rounded,
                size: 18,
                color: AppColors.primary.withValues(alpha: 0.5),
              ),
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(),
              onPressed: openPicker,
            ),
            contentPadding: const EdgeInsets.symmetric(
              vertical: 8,
              horizontal: 6,
            ),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppRadius.sm),
              borderSide: BorderSide(
                color: AppColors.primary.withValues(alpha: 0.15),
              ),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppRadius.sm),
              borderSide: BorderSide(
                color: AppColors.primary.withValues(alpha: 0.15),
              ),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppRadius.sm),
              borderSide: BorderSide(color: AppColors.primary),
            ),
          ),
        ),
      ),
    );
  }

  // NCT field-chain: IOP RE → IOP LE (end — O/E after this stays manual).
  Future<void> Function() _nctOpenLe() => _nctOpener(_nctIopLe);
  Future<void> Function() _nctOpenRe() =>
      _nctOpener(_nctIopRe, onDone: () => _nctOpenLe()());

  Future<void> Function() _nctOpener(
    TextEditingController ctrl, {
    VoidCallback? onDone,
  }) {
    final items = _formData?.masters.nct ?? [];
    return () async {
      if (items.isEmpty) return;
      String query = '';
      final picked = await showModalBottomSheet<String>(
        context: context,
        isScrollControlled: true,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(
            top: Radius.circular(AppRadius.xl),
          ),
        ),
        builder: (ctx) => StatefulBuilder(
          builder: (ctx, ss) {
            final filtered = query.isEmpty
                ? items
                : items
                      .where(
                        (i) =>
                            i.value.toLowerCase().contains(query.toLowerCase()),
                      )
                      .toList();
            return DraggableScrollableSheet(
              initialChildSize: 0.6,
              maxChildSize: 0.9,
              minChildSize: 0.35,
              expand: false,
              builder: (_, sc) => Column(
                children: [
                  Container(
                    margin: const EdgeInsets.only(top: 10, bottom: 6),
                    width: 36,
                    height: 4,
                    decoration: BoxDecoration(
                      color: AppColors.primary.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                    child: Row(
                      children: [
                        Expanded(
                          child: Text(
                            'IOP — NCT (mmHg)',
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w800,
                              color: AppColors.primary,
                            ),
                          ),
                        ),
                        if (ctrl.text.isNotEmpty)
                          TextButton(
                            onPressed: () => Navigator.pop(ctx, ''),
                            style: TextButton.styleFrom(
                              foregroundColor: Colors.red,
                            ),
                            child: const Text('Clear'),
                          ),
                      ],
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(12, 0, 12, 8),
                    child: TextField(
                      onChanged: (v) => ss(() => query = v),
                      decoration: InputDecoration(
                        hintText: 'Type to filter…',
                        prefixIcon: const Icon(Icons.search_rounded, size: 18),
                        isDense: true,
                        contentPadding: const EdgeInsets.symmetric(
                          vertical: 8,
                          horizontal: 12,
                        ),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(AppRadius.sm),
                          borderSide: BorderSide(
                            color: AppColors.primary.withValues(alpha: 0.2),
                          ),
                        ),
                      ),
                    ),
                  ),
                  Expanded(
                    child: GridView.builder(
                      controller: sc,
                      padding: const EdgeInsets.fromLTRB(12, 0, 12, 24),
                      gridDelegate:
                          const SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 5,
                            mainAxisSpacing: 4,
                            crossAxisSpacing: 4,
                            childAspectRatio: 1.3,
                          ),
                      itemCount: filtered.length,
                      itemBuilder: (_, i) {
                        final v = filtered[i].value;
                        final isSel = ctrl.text == v;
                        return PressScaleWrapper(
                          onTap: () => Navigator.pop(ctx, v),
                          child: Container(
                            decoration: BoxDecoration(
                              color: isSel
                                  ? AppColors.primary
                                  : Colors.grey.shade100,
                              borderRadius: BorderRadius.circular(6),
                              border: Border.all(
                                color: isSel
                                    ? AppColors.primary
                                    : Colors.grey.shade300,
                              ),
                            ),
                            child: Center(
                              child: Text(
                                v,
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: isSel
                                      ? FontWeight.w700
                                      : FontWeight.w500,
                                  color: isSel
                                      ? Colors.white
                                      : const Color(0xFF1e293b),
                                ),
                              ),
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ],
              ),
            );
          },
        ),
      );
      if (picked != null && mounted) {
        setState(() => ctrl.text = picked);
        if (onDone != null) {
          await Future.delayed(const Duration(milliseconds: 150));
          if (mounted) onDone();
        }
      }
    };
  }

  Widget _nctField(
    TextEditingController ctrl,
    Future<void> Function() openGrid,
  ) {
    final val = ctrl.text.trim();
    final iop = int.tryParse(val);
    final Color bdColor;
    final Color bgColor;
    if (iop != null && val.isNotEmpty) {
      if (iop >= 25) {
        bdColor = const Color(0xFFEF4444);
        bgColor = const Color(0xFFFFF5F5);
      } else if (iop >= 22) {
        bdColor = const Color(0xFFF59E0B);
        bgColor = const Color(0xFFFFFBF0);
      } else if (iop >= 10) {
        bdColor = const Color(0xFF22C55E);
        bgColor = const Color(0xFFF0FFF4);
      } else {
        bdColor = AppColors.primary.withValues(alpha: 0.18);
        bgColor = Colors.white;
      }
    } else {
      bdColor = AppColors.primary.withValues(alpha: 0.18);
      bgColor = Colors.white;
    }

    return TextFormField(
      controller: ctrl,
      keyboardType: TextInputType.number,
      textAlign: TextAlign.center,
      style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
      onChanged: (_) => setState(() {}),
      decoration: InputDecoration(
        hintText: '—',
        hintStyle: TextStyle(
          fontSize: 22,
          color: AppColors.primary.withValues(alpha: 0.2),
          fontWeight: FontWeight.w200,
        ),
        suffixIcon: IconButton(
          icon: Icon(Icons.arrow_drop_down_rounded, color: bdColor, size: 22),
          padding: EdgeInsets.zero,
          constraints: const BoxConstraints(),
          onPressed: openGrid,
        ),
        filled: true,
        fillColor: bgColor,
        contentPadding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: bdColor, width: 1.5),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: bdColor, width: 1.5),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: bdColor, width: 2.0),
        ),
      ),
    );
  }

  // ── Refraction ────────────────────────────────────────────────────────────────

  bool _axisDisabled(String cylText) {
    final t = cylText.trim();
    if (t.isEmpty) return true;
    if (t == 'Plano') return true;
    final abs = (t.startsWith('+') || t.startsWith('-')) ? t.substring(1) : t;
    return abs == '0.00' || abs == '0';
  }

  // Field-context label for the SPH/CYL bottom sheet's header — e.g.
  // "RE · Distance SPH" — so the sheet reads which cell it's editing instead
  // of only showing the +/- sign (see _showSphCylPicker's redesign note).
  static String _refractionFieldLabel(String eye, String key) {
    final eyeLabel = eye == 're' ? 'RE' : 'LE';
    final distNear = (key == 'ds' || key == 'dc') ? 'Distance' : 'Near';
    final sphCyl = (key == 'ds' || key == 'ns') ? 'SPH' : 'CYL';
    return '$eyeLabel · $distNear $sphCyl';
  }

  // SPH/CYL: [−] [readonly field] [+] — sign buttons open the grid picker pre-set to that sign
  // AXIS/VN : editable field + chevron icon — user can type freely OR pick from list
  /// Builds the async "open this refraction cell's picker" action — usable
  /// both as the cell's own tap handler and, externally, as a field-chain
  /// advance target (see _pgTable/_stTable: onDone fires the next field's
  /// opener after a short delay, matching web's advanceExamRowField).
  Future<void> Function() _refractionOpener(
    TextEditingController ctrl,
    String key, {
    bool disabled = false,
    VoidCallback? onDone,
    VoidCallback? onPicked,
    String? fieldLabel,
  }) {
    final isSphCyl = key == 'ds' || key == 'dc' || key == 'ns' || key == 'nc';
    final isAxis = key == 'ax' || key == 'na';
    final isVn = key == 'vn';
    final isNrVn = key == 'near_vn';

    List<ExamMasterItem> items;
    if (isSphCyl)
      items = _formData?.masters.sphCyl ?? [];
    else if (isAxis)
      items = _formData?.masters.axis ?? [];
    else if (isVn)
      items = _formData?.masters.vn ?? [];
    else if (isNrVn)
      items = _formData?.masters.nrvn ?? [];
    else
      items = [];

    return () async {
      if (disabled) return;
      String? picked;
      if (isSphCyl) {
        final t = ctrl.text.trim();
        final sign = t.startsWith('-') ? '-' : '+';
        picked = await _showSphCylPicker(
          items,
          ctrl.text,
          forcedSign: t.isEmpty ? '+' : sign,
          fieldLabel: fieldLabel,
        );
      } else if (items.isNotEmpty) {
        picked = await _pickMasterValue(items, current: ctrl.text.trim());
      }
      if (picked != null && mounted) {
        setState(() => ctrl.text = picked!);
        onPicked?.call();
        if (onDone != null) {
          await Future.delayed(const Duration(milliseconds: 150));
          if (mounted) onDone();
        }
      }
    };
  }

  Widget _refractionCell(
    TextEditingController ctrl,
    String key,
    Future<void> Function() open, {
    bool disabled = false,
  }) {
    final isSphCyl = key == 'ds' || key == 'dc' || key == 'ns' || key == 'nc';
    final isAxis = key == 'ax' || key == 'na';

    // ── SPH / CYL: colored tappable chip — opens full-screen picker ────────
    if (isSphCyl) {
      final t = ctrl.text.trim();
      final isEmpty = t.isEmpty;
      final isMinus = t.startsWith('-');
      final isZero = t == '0.00';
      final bgColor = isEmpty
          ? Colors.grey.shade50
          : isZero
          ? Colors.grey.shade200
          : isMinus
          ? const Color(0xFFFFF0F0)
          : const Color(0xFFF0FFF4);
      final bdColor = isEmpty
          ? Colors.grey.shade300
          : isZero
          ? Colors.grey.shade400
          : isMinus
          ? const Color(0xFFFFB3B3)
          : const Color(0xFF86EFAC);
      final txtColor = isEmpty
          ? Colors.grey.shade400
          : isZero
          ? const Color(0xFF475569)
          : isMinus
          ? const Color(0xFFB91C1C)
          : const Color(0xFF15803D);
      return Expanded(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 2),
          child: PressScaleWrapper(
            onTap: open,
            child: Container(
              height: 40,
              decoration: BoxDecoration(
                color: bgColor,
                borderRadius: BorderRadius.circular(AppRadius.sm),
                border: Border.all(color: bdColor),
              ),
              child: Center(
                child: Text(
                  isEmpty ? '—' : t,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: txtColor,
                  ),
                ),
              ),
            ),
          ),
        ),
      );
    }

    // ── VN / Near-VN / AXIS: chip style (value always visible) ─────────────
    final axisOff = isAxis && disabled;
    final t = ctrl.text.trim();
    final isEmpty = t.isEmpty;
    final Color chipBg, chipBd, chipTxt;
    if (axisOff) {
      chipBg = AppColors.primary.withValues(alpha: 0.04);
      chipBd = AppColors.primary.withValues(alpha: 0.08);
      chipTxt = AppColors.primary.withValues(alpha: 0.25);
    } else {
      chipBg = isEmpty
          ? Colors.grey.shade50
          : AppColors.primary.withValues(alpha: 0.08);
      chipBd = isEmpty
          ? Colors.grey.shade300
          : AppColors.primary.withValues(alpha: 0.4);
      chipTxt = isEmpty ? Colors.grey.shade400 : AppColors.primary;
    }
    return Expanded(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 2),
        child: PressScaleWrapper(
          onTap: axisOff ? null : open,
          child: Container(
            height: 40,
            decoration: BoxDecoration(
              color: chipBg,
              borderRadius: BorderRadius.circular(AppRadius.sm),
              border: Border.all(color: chipBd),
            ),
            child: Center(
              child: Text(
                isEmpty ? '—' : t,
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: chipTxt,
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  // Redesigned 2026-09-23 per user report: this used to be a Dialog.fullscreen
  // that replaced the whole screen with no indication of which field (which
  // eye / Distance-or-Near / SPH-or-CYL) it was for. Now a compact bottom
  // sheet — same idiom the rest of this exam form's pickers already use
  // (_nctOpener/_openOePickerSheet/_openFundusPickerSheet) — with the field
  // name in the header, so the parent screen stays visible/contextual behind
  // it instead of being fully replaced.
  //
  // Also matches web's actual behavior now (checked `secondary.blade.php`'s
  // pgPicker JS directly): tapping a grid value or Clear applies it and
  // closes immediately — no separate Apply tap needed. Only the custom/
  // manual text entry has its own explicit "Save"/Apply button, since typing
  // is not itself a commit gesture the way a tap is.
  Future<String?> _showSphCylPicker(
    List<ExamMasterItem> items,
    String current, {
    String? forcedSign,
    String? fieldLabel,
  }) async {
    // Web: picker opens pre-set to the sign of the button pressed; no Plano tab
    String sign = forcedSign ?? (current.startsWith('-') ? '-' : '+');
    final customCtrl = TextEditingController();

    // positive master values only in grid; 0.00 pinned last (no sign prefix, matching web)
    final gridItems = items.where((i) {
      final n = double.tryParse(i.value.replaceAll(RegExp(r'[+\-]'), ''));
      return n != null && n > 0;
    }).toList();

    Widget chip(String label, bool isSel, bool isFav, VoidCallback onTap) =>
        PressScaleWrapper(
          onTap: onTap,
          child: Container(
            width: 58,
            height: 36,
            decoration: BoxDecoration(
              color: isSel
                  ? AppColors.primary
                  : (isFav ? Colors.amber.shade50 : Colors.grey.shade100),
              borderRadius: BorderRadius.circular(AppRadius.sm),
              border: Border.all(
                color: isSel
                    ? AppColors.primary
                    : (isFav ? Colors.amber.shade300 : Colors.grey.shade300),
              ),
            ),
            child: Center(
              child: Text(
                label,
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: isSel
                      ? Colors.white
                      : (isFav
                            ? Colors.amber.shade800
                            : AppColors.primary.withValues(alpha: 0.85)),
                ),
              ),
            ),
          ),
        );

    Widget zeroChip(bool isSel, VoidCallback onTap) => PressScaleWrapper(
      onTap: onTap,
      child: Container(
        width: 58,
        height: 36,
        decoration: BoxDecoration(
          color: isSel ? const Color(0xFF475569) : Colors.grey.shade200,
          borderRadius: BorderRadius.circular(AppRadius.sm),
          border: Border.all(
            color: isSel ? const Color(0xFF475569) : Colors.grey.shade400,
          ),
        ),
        child: Center(
          child: Text(
            '0.00',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: isSel ? Colors.white : const Color(0xFF475569),
            ),
          ),
        ),
      ),
    );

    try {
      return await showModalBottomSheet<String>(
        context: context,
        isScrollControlled: true,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(
            top: Radius.circular(AppRadius.xl),
          ),
        ),
        builder: (sheetCtx) => StatefulBuilder(
          builder: (ctx, ss) {
            final signTitle = sign == '-'
                ? '− Negative Values'
                : '+ Positive Values';
            // Client report (2026-10-07): bigger so less scrolling is
            // needed to see the value grid — same "less scroll" request
            // applied to the new Axis/VN grid in _pickMasterValue above.
            return Padding(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(ctx).viewInsets.bottom,
              ),
              child: DraggableScrollableSheet(
                expand: false,
                initialChildSize: 0.72,
                minChildSize: 0.4,
                maxChildSize: 0.95,
                builder: (_, sc) => Column(
                children: [
                  Container(
                    margin: const EdgeInsets.only(top: 10, bottom: 4),
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: AppColors.primary.withValues(alpha: 0.18),
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(20, 10, 8, 4),
                    child: Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              if (fieldLabel != null)
                                Text(
                                  fieldLabel,
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w700,
                                    color: AppColors.primary.withValues(
                                      alpha: 0.6,
                                    ),
                                  ),
                                ),
                              Text(
                                signTitle,
                                style: const TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w800,
                                  color: AppColors.darkNavy,
                                ),
                              ),
                            ],
                          ),
                        ),
                        IconButton(
                          icon: const Icon(
                            Icons.close_rounded,
                            size: 20,
                            color: Color(0xFF94A3B8),
                          ),
                          onPressed: () => Navigator.pop(ctx),
                        ),
                      ],
                    ),
                  ),
                  // ── Sign tabs (− / +) only — matching web ─────────────────
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
                    child: Row(
                      children: [
                        for (final s in ['-', '+'])
                          Expanded(
                            child: Padding(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 4,
                              ),
                              child: PressScaleWrapper(
                                onTap: () => ss(() => sign = s),
                                child: Container(
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 10,
                                  ),
                                  decoration: BoxDecoration(
                                    color: sign == s
                                        ? AppColors.primary
                                        : Colors.white,
                                    borderRadius: BorderRadius.circular(
                                      AppRadius.sm,
                                    ),
                                    border: Border.all(
                                      color: sign == s
                                          ? AppColors.primary
                                          : AppColors.primary.withValues(
                                              alpha: 0.2,
                                            ),
                                    ),
                                  ),
                                  child: Text(
                                    s == '-' ? 'Minus (−)' : 'Plus (+)',
                                    textAlign: TextAlign.center,
                                    style: TextStyle(
                                      fontSize: 13,
                                      fontWeight: FontWeight.w700,
                                      color: sign == s
                                          ? Colors.white
                                          : AppColors.primary,
                                    ),
                                  ),
                                ),
                              ),
                            ),
                          ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 8),
                  // ── Value grid — tap applies + closes immediately ─────────
                  Expanded(
                    child: SingleChildScrollView(
                      controller: sc,
                      padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
                      child: Wrap(
                        spacing: 6,
                        runSpacing: 6,
                        children: [
                          ...gridItems.map((i) {
                            final v = '$sign${i.value}';
                            return chip(
                              v,
                              current == v,
                              i.isFavourite,
                              () => Navigator.pop(ctx, v),
                            );
                          }),
                          // 0.00 always last — shown without sign, matching web
                          zeroChip(
                            current == '0.00',
                            () => Navigator.pop(ctx, '0.00'),
                          ),
                        ],
                      ),
                    ),
                  ),
                  // ── Custom entry — needs its own explicit Apply ───────────
                  Container(
                    padding: const EdgeInsets.fromLTRB(16, 10, 16, 24),
                    decoration: BoxDecoration(
                      border: Border(
                        top: BorderSide(
                          color: AppColors.primary.withValues(alpha: 0.10),
                        ),
                      ),
                    ),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: TextFormField(
                                controller: customCtrl,
                                keyboardType:
                                    const TextInputType.numberWithOptions(
                                      decimal: true,
                                      signed: false,
                                    ),
                                decoration: InputDecoration(
                                  hintText: 'Custom (e.g. ${sign}1.75)',
                                  hintStyle: TextStyle(
                                    fontSize: 12,
                                    color: AppColors.primary.withValues(
                                      alpha: 0.4,
                                    ),
                                  ),
                                  contentPadding: const EdgeInsets.symmetric(
                                    vertical: 10,
                                    horizontal: 12,
                                  ),
                                  border: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(
                                      AppRadius.sm,
                                    ),
                                    borderSide: BorderSide(
                                      color: AppColors.primary.withValues(
                                        alpha: 0.3,
                                      ),
                                    ),
                                  ),
                                  enabledBorder: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(
                                      AppRadius.sm,
                                    ),
                                    borderSide: BorderSide(
                                      color: AppColors.primary.withValues(
                                        alpha: 0.3,
                                      ),
                                    ),
                                  ),
                                  focusedBorder: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(
                                      AppRadius.sm,
                                    ),
                                    borderSide: BorderSide(
                                      color: AppColors.primary,
                                    ),
                                  ),
                                ),
                                style: const TextStyle(fontSize: 13),
                              ),
                            ),
                            const SizedBox(width: 8),
                            PressScaleWrapper(
                              onTap: () {
                                final custom = customCtrl.text.trim();
                                if (custom.isEmpty) return;
                                final stripped =
                                    (custom.startsWith('+') ||
                                        custom.startsWith('-'))
                                    ? custom.substring(1)
                                    : custom;
                                Navigator.pop(ctx, '$sign$stripped');
                              },
                              child: Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 22,
                                  vertical: 13,
                                ),
                                decoration: BoxDecoration(
                                  color: AppColors.primary,
                                  borderRadius: BorderRadius.circular(
                                    AppRadius.sm,
                                  ),
                                ),
                                child: const Text(
                                  'Apply',
                                  style: TextStyle(
                                    fontSize: 14,
                                    fontWeight: FontWeight.w700,
                                    color: Colors.white,
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Align(
                          alignment: Alignment.centerLeft,
                          child: PressScaleWrapper(
                            // Applies the clear immediately and closes — matches
                            // web's Clear button and tablet's SignGridPopover.
                            onTap: () => Navigator.pop(ctx, ''),
                            child: Text(
                              'Clear',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: Colors.red.shade600,
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
                ),
              ),
            );
          },
        ),
      );
    } finally {
      customCtrl.dispose();
    }
  }

  Widget _pgTable() {
    // Field-chain order per eye: DS→DC→AX→VN (dist) →NS→NC→NA→NearVN (near),
    // RE then LE — same technique as C/O/K-C-O/Vision; web has no reference
    // pattern for PG specifically, so this follows the natural reading order.
    Future<void> Function()? nextOpen;
    final opens = <String, Future<void> Function()>{};
    for (final eye in ['le', 're']) {
      final keys = [..._pgDistKeys, ..._pgNearKeys];
      for (final k in keys.reversed) {
        final disabled =
            (k == 'ax' && _axisDisabled(_pg[eye]!['dc']!.text)) ||
            (k == 'na' && _axisDisabled(_pg[eye]!['nc']!.text));
        final open = _refractionOpener(
          _pg[eye]![k]!,
          k,
          disabled: disabled,
          onDone: nextOpen,
          fieldLabel: _refractionFieldLabel(eye, k),
        );
        opens['${eye}_$k'] = open;
        nextOpen = open;
      }
    }
    _autoOpenFirstField(ExamSection.pg, opens['re_ds']!);

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.12)),
      ),
      child: Column(
        children: [
          Container(
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.06),
              borderRadius: BorderRadius.vertical(top: Radius.circular(11)),
            ),
            child: Row(
              children: [
                const SizedBox(width: 68),
                _thCell('SPH'),
                _thCell('CYL'),
                _thCell('AXIS'),
                _thCell('VN C GL'),
              ],
            ),
          ),
          for (final eye in ['re', 'le']) ...[
            const Divider(height: 1),
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 8),
              child: Row(
                children: [
                  SizedBox(
                    width: 68,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _eyeLabel(eye.toUpperCase(), eye == 're'),
                        Text(
                          'DIST',
                          style: TextStyle(
                            fontSize: 9,
                            color: AppColors.primary.withValues(alpha: 0.5),
                          ),
                        ),
                      ],
                    ),
                  ),
                  ..._pgDistKeys.map(
                    (k) => _refractionCell(
                      _pg[eye]![k]!,
                      k,
                      opens['${eye}_$k']!,
                      disabled:
                          k == 'ax' && _axisDisabled(_pg[eye]!['dc']!.text),
                    ),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 8),
              child: Row(
                children: [
                  SizedBox(
                    width: 68,
                    child: Padding(
                      padding: const EdgeInsets.only(left: 2),
                      child: Text(
                        'NEAR',
                        style: TextStyle(
                          fontSize: 9,
                          color: AppColors.primary.withValues(alpha: 0.5),
                        ),
                      ),
                    ),
                  ),
                  ..._pgNearKeys.map(
                    (k) => _refractionCell(
                      _pg[eye]![k]!,
                      k,
                      opens['${eye}_$k']!,
                      disabled:
                          k == 'na' && _axisDisabled(_pg[eye]!['nc']!.text),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  // ── ST unified table (DISTANCE + NEAR per eye) ───────────────────────────────

  Widget _stTable() {
    // Field-chain per eye: DS→DC→AX→VN→ADD (NC/NA-near are read-only
    // mirrors, not pickable, so they're skipped), RE then LE.
    Future<void> Function()? nextEyeStart;
    final stOpens = <String, Future<void> Function()>{};
    for (final eye in ['le', 're']) {
      final openAdd = _stAddOpener(eye, onDone: nextEyeStart);
      final openVn = _refractionOpener(_st[eye]!['vn']!, 'vn', onDone: openAdd);
      final openAx = _refractionOpener(
        _st[eye]!['ax']!,
        'ax',
        disabled: _axisDisabled(_st[eye]!['dc']!.text),
        onDone: openVn,
      );
      final openDc = _refractionOpener(
        _st[eye]!['dc']!,
        'dc',
        onDone: openAx,
        fieldLabel: _refractionFieldLabel(eye, 'dc'),
      );
      final openDs = _refractionOpener(
        _st[eye]!['ds']!,
        'ds',
        onPicked: () => _recalcNs(eye),
        onDone: openDc,
        fieldLabel: _refractionFieldLabel(eye, 'ds'),
      );
      stOpens['${eye}_ds'] = openDs;
      stOpens['${eye}_dc'] = openDc;
      stOpens['${eye}_ax'] = openAx;
      stOpens['${eye}_vn'] = openVn;
      stOpens['${eye}_add'] = openAdd;
      nextEyeStart = openDs;
    }
    _autoOpenFirstField(ExamSection.st, stOpens['re_ds']!);

    return Column(
      children: [
        for (final eye in ['re', 'le']) ...[
          if (eye == 'le') const SizedBox(height: 8),
          Container(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(AppRadius.md),
              border: Border.all(
                color: AppColors.primary.withValues(alpha: 0.12),
              ),
            ),
            child: Column(
              children: [
                // Eye header
                Container(
                  decoration: BoxDecoration(
                    color: AppColors.primary,
                    borderRadius: BorderRadius.vertical(
                      top: Radius.circular(11),
                    ),
                  ),
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 7,
                  ),
                  child: Row(
                    children: [
                      const Icon(
                        Icons.remove_red_eye_rounded,
                        color: Colors.white,
                        size: 13,
                      ),
                      const SizedBox(width: 6),
                      Text(
                        eye == 're' ? 'Right Eye (RE)' : 'Left Eye (LE)',
                        style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          color: Colors.white,
                        ),
                      ),
                    ],
                  ),
                ),
                // Column headers
                Container(
                  color: AppColors.primary.withValues(alpha: 0.04),
                  child: Row(
                    children: [
                      const SizedBox(width: 46),
                      _thCell('SPH'),
                      _thCell('CYL'),
                      _thCell('AXIS'),
                      _thCell('VN C ST'),
                    ],
                  ),
                ),
                // DISTANCE row
                const Divider(height: 1),
                Padding(
                  padding: const EdgeInsets.symmetric(
                    vertical: 6,
                    horizontal: 6,
                  ),
                  child: Row(
                    children: [
                      SizedBox(
                        width: 46,
                        child: Center(
                          child: Text(
                            'DIST',
                            style: TextStyle(
                              fontSize: 9,
                              fontWeight: FontWeight.w700,
                              color: AppColors.primary.withValues(alpha: 0.5),
                              letterSpacing: .04,
                            ),
                          ),
                        ),
                      ),
                      _refractionCell(
                        _st[eye]!['ds']!,
                        'ds',
                        stOpens['${eye}_ds']!,
                      ),
                      _refractionCell(
                        _st[eye]!['dc']!,
                        'dc',
                        stOpens['${eye}_dc']!,
                      ),
                      _refractionCell(
                        _st[eye]!['ax']!,
                        'ax',
                        stOpens['${eye}_ax']!,
                        disabled: _axisDisabled(_st[eye]!['dc']!.text),
                      ),
                      _refractionCell(
                        _st[eye]!['vn']!,
                        'vn',
                        stOpens['${eye}_vn']!,
                      ),
                    ],
                  ),
                ),
                // NEAR row
                const Divider(height: 1),
                Padding(
                  padding: const EdgeInsets.fromLTRB(6, 6, 6, 8),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      SizedBox(
                        width: 46,
                        child: Padding(
                          padding: const EdgeInsets.only(top: 12),
                          child: Center(
                            child: Text(
                              'NEAR',
                              style: TextStyle(
                                fontSize: 9,
                                fontWeight: FontWeight.w700,
                                color: AppColors.primary.withValues(alpha: 0.5),
                                letterSpacing: .04,
                              ),
                            ),
                          ),
                        ),
                      ),
                      _stNsCell(eye, stOpens['${eye}_add']!),
                      _stMirrorCell(_st[eye]!['dc']!),
                      _stMirrorCell(_st[eye]!['ax']!),
                      Expanded(
                        child: Padding(
                          padding: const EdgeInsets.only(top: 10),
                          child: Center(
                            child: Text(
                              '—',
                              style: TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.w200,
                                color: Colors.grey.shade400,
                              ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ],
    );
  }

  // NS (near SPH) chip + ADD text field stacked
  // NS = DS + ADD (web formula)
  void _recalcNs(String eye) {
    final ds =
        double.tryParse(_st[eye]!['ds']!.text.replaceAll('+', '')) ?? 0.0;
    final add =
        double.tryParse(
          (eye == 're' ? _stAddRe : _stAddLe).text.replaceAll('+', ''),
        ) ??
        0.0;
    final ns = ds + add;
    (eye == 're' ? _stNsRe : _stNsLe).text = ns == 0.0
        ? ''
        : (ns > 0 ? '+${ns.toStringAsFixed(2)}' : ns.toStringAsFixed(2));
  }

  /// ADD picker opener (feeds NS = DS+ADD) — extracted like
  /// _refractionOpener so it can both drive _stNsCell's tap target and be
  /// chained into from the DIST row's VN field.
  Future<void> Function() _stAddOpener(String eye, {VoidCallback? onDone}) {
    final addCtrl = eye == 're' ? _stAddRe : _stAddLe;
    final items = _formData?.masters.sphCyl ?? [];
    return () async {
      final curAdd = addCtrl.text.trim();
      final addIsMinus = curAdd.startsWith('-');
      final picked = await _showSphCylPicker(
        items,
        curAdd,
        forcedSign: curAdd.isEmpty ? '+' : (addIsMinus ? '-' : '+'),
        fieldLabel: '${eye == 're' ? 'RE' : 'LE'} · ADD',
      );
      if (picked != null && mounted) {
        setState(() {
          addCtrl.text = picked;
          _recalcNs(eye);
        });
        if (onDone != null) {
          await Future.delayed(const Duration(milliseconds: 150));
          if (mounted) onDone();
        }
      }
    };
  }

  // NS chip (shows calculated NS = DS+ADD); tap picks ADD; "ADD: X" label below
  Widget _stNsCell(String eye, Future<void> Function() open) {
    final nsCtrl = eye == 're' ? _stNsRe : _stNsLe;
    final addCtrl = eye == 're' ? _stAddRe : _stAddLe;
    final ns = nsCtrl.text.trim();
    final add = addCtrl.text.trim();
    final isEmpty = ns.isEmpty;
    final isMinus = ns.startsWith('-');
    final isZero = ns == '0.00';
    final bgColor = isEmpty
        ? Colors.grey.shade50
        : isZero
        ? Colors.grey.shade200
        : isMinus
        ? const Color(0xFFFFF0F0)
        : const Color(0xFFF0FFF4);
    final bdColor = isEmpty
        ? Colors.grey.shade300
        : isZero
        ? Colors.grey.shade400
        : isMinus
        ? const Color(0xFFFFB3B3)
        : const Color(0xFF86EFAC);
    final txtColor = isEmpty
        ? Colors.grey.shade400
        : isZero
        ? const Color(0xFF475569)
        : isMinus
        ? const Color(0xFFB91C1C)
        : const Color(0xFF15803D);
    return Expanded(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 2),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            PressScaleWrapper(
              onTap: open,
              child: Container(
                height: 40,
                decoration: BoxDecoration(
                  color: bgColor,
                  borderRadius: BorderRadius.circular(AppRadius.sm),
                  border: Border.all(color: bdColor),
                ),
                child: Center(
                  child: Text(
                    isEmpty ? '—' : ns,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: txtColor,
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 3),
            Text(
              'ADD: ${add.isEmpty ? '—' : add}',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 9, color: Colors.grey.shade500),
            ),
          ],
        ),
      ),
    );
  }

  // Readonly mirror of a distance field (NC = DC, NA = AX)
  Widget _stMirrorCell(TextEditingController mirrorOf) {
    final val = mirrorOf.text.trim();
    return Expanded(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 2),
        child: Container(
          height: 40,
          decoration: BoxDecoration(
            color: Colors.grey.shade100,
            borderRadius: BorderRadius.circular(AppRadius.sm),
            border: Border.all(color: Colors.grey.shade300),
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                val.isEmpty ? '—' : val,
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: val.isEmpty
                      ? Colors.grey.shade400
                      : AppColors.primary.withValues(alpha: 0.4),
                ),
              ),
              Text(
                '= Dist',
                style: TextStyle(fontSize: 8, color: Colors.grey.shade400),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _stCheckboxRow() {
    return Wrap(
      spacing: 4,
      runSpacing: 4,
      children: [
        _stCheck('Bifocal', _bifocal, (v) => setState(() => _bifocal = v!)),
        _stCheck(
          'N&D Separate',
          _ndSeparate,
          (v) => setState(() => _ndSeparate = v!),
        ),
        _stCheck(
          'Progressive',
          _progressive,
          (v) => setState(() => _progressive = v!),
        ),
        _stCheck(
          'Computer Use',
          _computerUses,
          (v) => setState(() => _computerUses = v!),
        ),
      ],
    );
  }

  Widget _stCheck(String label, bool val, void Function(bool?) onChanged) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Checkbox(
          value: val,
          onChanged: onChanged,
          activeColor: AppColors.teal,
          materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
        ),
        Text(label, style: TextStyle(fontSize: 12, color: AppColors.primary)),
      ],
    );
  }

  // ── OE & Fundus ───────────────────────────────────────────────────────────────

  Widget _oeTable() {
    bool isPseudo(String eye) => (eye == 're' ? _oeRe['lens'] : _oeLe['lens'])!
        .text
        .toLowerCase()
        .contains('pseudophakia');

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.12)),
        boxShadow: [
          BoxShadow(
            color: AppColors.primary.withValues(alpha: 0.05),
            blurRadius: 6,
            offset: Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          Container(
            decoration: BoxDecoration(
              color: AppColors.primary,
              borderRadius: BorderRadius.vertical(top: Radius.circular(11)),
            ),
            child: Row(
              children: [
                const SizedBox(width: 110),
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(
                          Icons.remove_red_eye_rounded,
                          color: Colors.white,
                          size: 12,
                        ),
                        const SizedBox(width: 4),
                        const Text(
                          'Right Eye (RE)',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                Container(
                  width: 1,
                  height: 36,
                  color: Colors.white.withValues(alpha: 0.2),
                ),
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(
                          Icons.remove_red_eye_rounded,
                          color: Colors.white,
                          size: 12,
                        ),
                        const SizedBox(width: 4),
                        const Text(
                          'Left Eye (LE)',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
          ..._oeFieldNames.map((f) {
            final isOther = f == 'other';
            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Divider(height: 1),
                Padding(
                  padding: const EdgeInsets.symmetric(
                    vertical: 6,
                    horizontal: 8,
                  ),
                  child: Row(
                    children: [
                      SizedBox(
                        width: 100,
                        child: Text(
                          _oeLabels[f] ?? f,
                          style: TextStyle(
                            fontSize: 12,
                            color: AppColors.primary.withValues(alpha: 0.7),
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                      const SizedBox(width: 6),
                      if (isOther) ...[
                        Expanded(child: _oePlainField(_oeRe[f]!)),
                        const SizedBox(width: 6),
                        Expanded(child: _oePlainField(_oeLe[f]!)),
                      ] else ...[
                        Expanded(child: _oePickerField(f, _oeRe[f]!)),
                        const SizedBox(width: 6),
                        Expanded(child: _oePickerField(f, _oeLe[f]!)),
                      ],
                    ],
                  ),
                ),
                if (f == 'lens' && (isPseudo('re') || isPseudo('le')))
                  _pseudophakiaPanel(isPseudo('re'), isPseudo('le')),
              ],
            );
          }),
        ],
      ),
    );
  }

  Widget _oePlainField(TextEditingController ctrl) => TextFormField(
    controller: ctrl,
    style: const TextStyle(fontSize: 12),
    decoration: InputDecoration(
      hintText: '—',
      hintStyle: TextStyle(
        color: AppColors.primary.withValues(alpha: 0.3),
        fontSize: 12,
      ),
      contentPadding: const EdgeInsets.symmetric(vertical: 6, horizontal: 8),
      isDense: true,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(AppRadius.sm),
        borderSide: BorderSide(
          color: AppColors.primary.withValues(alpha: 0.15),
        ),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(AppRadius.sm),
        borderSide: BorderSide(
          color: AppColors.primary.withValues(alpha: 0.15),
        ),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(AppRadius.sm),
        borderSide: BorderSide(color: AppColors.primary),
      ),
    ),
  );

  Widget _oePickerField(String fieldKey, TextEditingController ctrl) {
    final hasItems = (_oeMasters[fieldKey] ?? []).isNotEmpty;
    return TextFormField(
      controller: ctrl,
      readOnly: hasItems,
      onChanged: (_) => setState(() {}),
      style: const TextStyle(fontSize: 12),
      decoration: InputDecoration(
        hintText: '—',
        hintStyle: TextStyle(
          color: AppColors.primary.withValues(alpha: 0.3),
          fontSize: 12,
        ),
        contentPadding: const EdgeInsets.symmetric(vertical: 6, horizontal: 8),
        isDense: true,
        suffixIcon: hasItems
            ? PressScaleWrapper(
                onTap: () => _openOePickerSheet(fieldKey, ctrl),
                child: Icon(
                  Icons.arrow_drop_down_rounded,
                  size: 18,
                  color: AppColors.primary.withValues(alpha: 0.5),
                ),
              )
            : null,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
          borderSide: BorderSide(
            color: AppColors.primary.withValues(alpha: 0.15),
          ),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
          borderSide: BorderSide(
            color: AppColors.primary.withValues(alpha: 0.15),
          ),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
          borderSide: BorderSide(color: AppColors.primary),
        ),
      ),
      onTap: hasItems ? () => _openOePickerSheet(fieldKey, ctrl) : null,
    );
  }

  void _openOePickerSheet(String fieldKey, TextEditingController ctrl) {
    final apiType = _oeMasterApiType[fieldKey] ?? fieldKey;
    String query = '';
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.xl)),
      ),
      builder: (bsCtx) => StatefulBuilder(
        builder: (_, bsSs) {
          final allItems = _oeMasters[fieldKey] ?? [];
          final favs = allItems
              .where(
                (i) =>
                    i.isFavourite &&
                    (query.isEmpty ||
                        i.value.toLowerCase().contains(query.toLowerCase())),
              )
              .toList();
          final others = allItems
              .where(
                (i) =>
                    !i.isFavourite &&
                    (query.isEmpty ||
                        i.value.toLowerCase().contains(query.toLowerCase())),
              )
              .toList();

          Future<void> doToggle(ExamMasterItem item) async {
            try {
              final newFav = await SimpleMasterService.instance.toggleFavourite(
                'masters/detail/$apiType',
                item.id,
              );
              bsSs(() {
                final list = _oeMasters[fieldKey]!;
                final idx = list.indexWhere((e) => e.id == item.id);
                if (idx >= 0)
                  list[idx] = ExamMasterItem(
                    id: item.id,
                    value: item.value,
                    isFavourite: newFav,
                  );
              });
              if (mounted) setState(() {});
            } catch (_) {}
          }

          Widget itemTile(ExamMasterItem item) => ListTile(
            dense: true,
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 16,
              vertical: 0,
            ),
            title: Text(item.value, style: const TextStyle(fontSize: 13)),
            trailing: IconButton(
              icon: Icon(
                item.isFavourite
                    ? Icons.star_rounded
                    : Icons.star_outline_rounded,
                size: 18,
                color: item.isFavourite
                    ? Colors.amber
                    : AppColors.primary.withValues(alpha: 0.3),
              ),
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(),
              onPressed: () => doToggle(item),
            ),
            onTap: () {
              if (mounted) setState(() => ctrl.text = item.value);
              Navigator.pop(bsCtx);
            },
          );

          return DraggableScrollableSheet(
            expand: false,
            initialChildSize: 0.6,
            maxChildSize: 0.92,
            minChildSize: 0.3,
            builder: (_, sc) => Column(
              children: [
                Container(
                  margin: const EdgeInsets.only(top: 10, bottom: 6),
                  width: 36,
                  height: 4,
                  decoration: BoxDecoration(
                    color: AppColors.primary.withValues(alpha: 0.2),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          _oeLabels[fieldKey] ?? fieldKey,
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w800,
                            color: AppColors.primary,
                          ),
                        ),
                      ),
                      if (ctrl.text.isNotEmpty)
                        TextButton(
                          onPressed: () {
                            if (mounted) setState(() => ctrl.text = '');
                            Navigator.pop(bsCtx);
                          },
                          style: TextButton.styleFrom(
                            foregroundColor: Colors.red,
                          ),
                          child: const Text('Clear'),
                        ),
                    ],
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(12, 0, 12, 8),
                  child: TextField(
                    onChanged: (v) => bsSs(() => query = v),
                    decoration: InputDecoration(
                      hintText: 'Search…',
                      prefixIcon: const Icon(Icons.search_rounded, size: 18),
                      isDense: true,
                      contentPadding: const EdgeInsets.symmetric(
                        vertical: 8,
                        horizontal: 12,
                      ),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(AppRadius.sm),
                        borderSide: BorderSide(
                          color: AppColors.primary.withValues(alpha: 0.2),
                        ),
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: ListView(
                    controller: sc,
                    children: [
                      if (favs.isNotEmpty) ...[
                        Padding(
                          padding: const EdgeInsets.fromLTRB(16, 6, 16, 2),
                          child: Text(
                            '★  Favourites',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: Colors.amber.shade700,
                            ),
                          ),
                        ),
                        ...favs.map(itemTile),
                        const Divider(height: 1),
                      ],
                      if (others.isNotEmpty) ...[
                        Padding(
                          padding: const EdgeInsets.fromLTRB(16, 6, 16, 2),
                          child: Text(
                            'All',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: AppColors.primary.withValues(alpha: 0.5),
                            ),
                          ),
                        ),
                        ...others.map(itemTile),
                      ],
                      if (favs.isEmpty && others.isEmpty)
                        Padding(
                          padding: const EdgeInsets.all(24),
                          child: Text(
                            'No results',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              color: AppColors.primary.withValues(alpha: 0.35),
                            ),
                          ),
                        ),
                      const SizedBox(height: 24),
                    ],
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _pseudophakiaPanel(bool showRe, bool showLe) {
    const opts = ['Block', 'Phaco'];

    Widget opChips(String current, void Function(String) onSel) => Wrap(
      spacing: 6,
      children: opts
          .map(
            (o) => PressScaleWrapper(
              onTap: () => setState(() => onSel(o)),
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: current == o ? AppColors.primary : Colors.white,
                  borderRadius: BorderRadius.circular(AppRadius.xl),
                  border: Border.all(
                    color: current == o
                        ? AppColors.primary
                        : AppColors.primary.withValues(alpha: 0.2),
                  ),
                ),
                child: Text(
                  o,
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: current == o ? Colors.white : AppColors.primary,
                  ),
                ),
              ),
            ),
          )
          .toList(),
    );

    Widget panel(
      String eye,
      String opType,
      void Function(String) onOpType,
      TextEditingController expenseCtrl,
      TextEditingController hospitalCtrl,
    ) => Container(
      margin: const EdgeInsets.fromLTRB(8, 0, 8, 6),
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: const Color(0xFFF0F4FF),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.15)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Pseudophakia — $eye',
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: AppColors.primary,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Operation Type',
            style: TextStyle(
              fontSize: 11,
              color: AppColors.primary.withValues(alpha: 0.6),
            ),
          ),
          const SizedBox(height: 4),
          opChips(opType, onOpType),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Expense',
                      style: TextStyle(
                        fontSize: 11,
                        color: AppColors.primary.withValues(alpha: 0.6),
                      ),
                    ),
                    const SizedBox(height: 4),
                    _oePlainField(expenseCtrl),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Hospital',
                      style: TextStyle(
                        fontSize: 11,
                        color: AppColors.primary.withValues(alpha: 0.6),
                      ),
                    ),
                    const SizedBox(height: 4),
                    _MasterAutocompleteField(
                      items: _referrers,
                      initialValue: hospitalCtrl.text,
                      hint: 'Hospital',
                      icon: Icons.local_hospital_outlined,
                      color: AppColors.primary,
                      onChanged: (v) => hospitalCtrl.text = v,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );

    return Column(
      children: [
        if (showRe)
          panel(
            'RE',
            _pseudophakiaOpTypeRe,
            (v) {
              _pseudophakiaOpTypeRe = v;
            },
            _pseudophakiaExpenseRe,
            _pseudophakiaHospitalRe,
          ),
        if (showLe)
          panel(
            'LE',
            _pseudophakiaOpTypeLe,
            (v) {
              _pseudophakiaOpTypeLe = v;
            },
            _pseudophakiaExpenseLe,
            _pseudophakiaHospitalLe,
          ),
      ],
    );
  }

  Widget _fundusTable() {
    // Field-chain per eye: Disc → FR → focus Comment (a free-text field has
    // no "final pick" moment of its own to chain further, so the flow ends
    // there — matching C/O's comment field being a chain terminus too).
    Widget eyeCard(
      bool isRe,
      TextEditingController discCtrl,
      TextEditingController frCtrl,
      TextEditingController commentCtrl,
      FocusNode commentFocus,
    ) {
      final eyeLabel = isRe ? 'Right Eye (RE)' : 'Left Eye (LE)';
      // Client report (2026-10-07): used to auto-focus (and so auto-open
      // the keyboard over) the Comment field right after FR was picked.
      // Comment is free text, not a picker — web's own modal has no such
      // auto-advance into it. Chain just ends at FR now; the user taps
      // Comment themselves when they want it.
      void openFr() => _openFundusPickerSheet(
        _fundusFrMasters,
        'fr',
        frCtrl,
        'Foveal Reflex',
      );
      if (isRe) {
        void openDisc() => _openFundusPickerSheet(
          _fundusDiscMasters,
          'disc',
          discCtrl,
          'Disc (CDR)',
          onDone: openFr,
        );
        _autoOpenFirstField(ExamSection.fundus, () async => openDisc());
      }
      return Container(
        margin: isRe ? EdgeInsets.zero : const EdgeInsets.only(top: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(AppRadius.md),
          border: Border.all(color: AppColors.primary.withValues(alpha: 0.12)),
          boxShadow: [
            BoxShadow(
              color: AppColors.primary.withValues(alpha: 0.05),
              blurRadius: 6,
              offset: Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          children: [
            Container(
              decoration: BoxDecoration(
                color: AppColors.primary,
                borderRadius: BorderRadius.vertical(top: Radius.circular(11)),
              ),
              padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 12),
              child: Row(
                children: [
                  const Icon(
                    Icons.remove_red_eye_rounded,
                    color: Colors.white,
                    size: 13,
                  ),
                  const SizedBox(width: 6),
                  Text(
                    eyeLabel,
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 7, horizontal: 10),
              child: Row(
                children: [
                  SizedBox(
                    width: 90,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'DISC',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                            color: AppColors.primary,
                          ),
                        ),
                        Text(
                          'CDR / Appearance',
                          style: TextStyle(
                            fontSize: 10,
                            color: AppColors.primary.withValues(alpha: 0.45),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: _fundusPickerField(
                      _fundusDiscMasters,
                      'disc',
                      discCtrl,
                      'Disc (CDR)',
                      onDone: openFr,
                    ),
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 7, horizontal: 10),
              child: Row(
                children: [
                  SizedBox(
                    width: 90,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'FR',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                            color: AppColors.primary,
                          ),
                        ),
                        Text(
                          'Foveal Reflex',
                          style: TextStyle(
                            fontSize: 10,
                            color: AppColors.primary.withValues(alpha: 0.45),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    // Client report (2026-10-07): used to auto-focus (and so
                    // auto-open the keyboard over) the Comment field right
                    // after FR was picked. Comment is free text, not a
                    // picker — web's own modal has no such auto-advance
                    // into it. Chain just ends here now; the user taps
                    // Comment themselves when they want it.
                    child: _fundusPickerField(
                      _fundusFrMasters,
                      'fr',
                      frCtrl,
                      'Foveal Reflex',
                    ),
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            Padding(
              padding: const EdgeInsets.fromLTRB(10, 7, 10, 10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'COMMENT',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: AppColors.primary.withValues(alpha: 0.6),
                    ),
                  ),
                  const SizedBox(height: 4),
                  TextFormField(
                    controller: commentCtrl,
                    focusNode: commentFocus,
                    maxLines: 3,
                    minLines: 2,
                    keyboardType: TextInputType.multiline,
                    style: const TextStyle(fontSize: 12),
                    decoration: InputDecoration(
                      hintText: 'Additional findings…',
                      hintStyle: TextStyle(
                        color: AppColors.primary.withValues(alpha: 0.3),
                        fontSize: 12,
                      ),
                      contentPadding: const EdgeInsets.symmetric(
                        vertical: 8,
                        horizontal: 10,
                      ),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(AppRadius.sm),
                        borderSide: BorderSide(
                          color: AppColors.primary.withValues(alpha: 0.15),
                        ),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(AppRadius.sm),
                        borderSide: BorderSide(
                          color: AppColors.primary.withValues(alpha: 0.15),
                        ),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(AppRadius.sm),
                        borderSide: BorderSide(color: AppColors.primary),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      );
    }

    return Column(
      children: [
        eyeCard(
          true,
          _fundusDiscRe,
          _fundusFrRe,
          _fundusCommentRe,
          _fundusCommentReFocus,
        ),
        eyeCard(
          false,
          _fundusDiscLe,
          _fundusFrLe,
          _fundusCommentLe,
          _fundusCommentLeFocus,
        ),
      ],
    );
  }

  Widget _fundusPickerField(
    List<ExamMasterItem> masterList,
    String apiType,
    TextEditingController ctrl,
    String label, {
    VoidCallback? onDone,
  }) {
    void open() => _openFundusPickerSheet(
      masterList,
      apiType,
      ctrl,
      label,
      onDone: onDone,
    );
    return TextFormField(
      controller: ctrl,
      readOnly: masterList.isNotEmpty,
      style: const TextStyle(fontSize: 12),
      decoration: InputDecoration(
        hintText: '—',
        hintStyle: TextStyle(
          color: AppColors.primary.withValues(alpha: 0.3),
          fontSize: 12,
        ),
        contentPadding: const EdgeInsets.symmetric(vertical: 6, horizontal: 8),
        isDense: true,
        suffixIcon: masterList.isNotEmpty
            ? PressScaleWrapper(
                onTap: open,
                child: Icon(
                  Icons.arrow_drop_down_rounded,
                  size: 18,
                  color: AppColors.primary.withValues(alpha: 0.5),
                ),
              )
            : null,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
          borderSide: BorderSide(
            color: AppColors.primary.withValues(alpha: 0.15),
          ),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
          borderSide: BorderSide(
            color: AppColors.primary.withValues(alpha: 0.15),
          ),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
          borderSide: BorderSide(color: AppColors.primary),
        ),
      ),
      onTap: masterList.isNotEmpty ? open : null,
    );
  }

  void _openFundusPickerSheet(
    List<ExamMasterItem> masterList,
    String apiType,
    TextEditingController ctrl,
    String label, {
    VoidCallback? onDone,
  }) {
    String query = '';
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.xl)),
      ),
      builder: (bsCtx) => StatefulBuilder(
        builder: (_, bsSs) {
          final favs = masterList
              .where(
                (i) =>
                    i.isFavourite &&
                    (query.isEmpty ||
                        i.value.toLowerCase().contains(query.toLowerCase())),
              )
              .toList();
          final others = masterList
              .where(
                (i) =>
                    !i.isFavourite &&
                    (query.isEmpty ||
                        i.value.toLowerCase().contains(query.toLowerCase())),
              )
              .toList();

          Future<void> doToggle(ExamMasterItem item) async {
            try {
              final newFav = await SimpleMasterService.instance.toggleFavourite(
                'masters/detail/$apiType',
                item.id,
              );
              bsSs(() {
                final idx = masterList.indexWhere((e) => e.id == item.id);
                if (idx >= 0)
                  masterList[idx] = ExamMasterItem(
                    id: item.id,
                    value: item.value,
                    isFavourite: newFav,
                  );
              });
              if (mounted) setState(() {});
            } catch (_) {}
          }

          Widget itemTile(ExamMasterItem item) => ListTile(
            dense: true,
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 16,
              vertical: 0,
            ),
            title: Text(item.value, style: const TextStyle(fontSize: 13)),
            trailing: IconButton(
              icon: Icon(
                item.isFavourite
                    ? Icons.star_rounded
                    : Icons.star_outline_rounded,
                size: 18,
                color: item.isFavourite
                    ? Colors.amber
                    : AppColors.primary.withValues(alpha: 0.3),
              ),
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(),
              onPressed: () => doToggle(item),
            ),
            onTap: () async {
              if (mounted) setState(() => ctrl.text = item.value);
              Navigator.pop(bsCtx);
              if (onDone != null) {
                await Future.delayed(const Duration(milliseconds: 150));
                if (mounted) onDone();
              }
            },
          );

          return DraggableScrollableSheet(
            expand: false,
            initialChildSize: 0.6,
            maxChildSize: 0.92,
            minChildSize: 0.3,
            builder: (_, sc) => Column(
              children: [
                Container(
                  margin: const EdgeInsets.only(top: 10, bottom: 6),
                  width: 36,
                  height: 4,
                  decoration: BoxDecoration(
                    color: AppColors.primary.withValues(alpha: 0.2),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          label,
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w800,
                            color: AppColors.primary,
                          ),
                        ),
                      ),
                      if (ctrl.text.isNotEmpty)
                        TextButton(
                          onPressed: () {
                            if (mounted) setState(() => ctrl.text = '');
                            Navigator.pop(bsCtx);
                          },
                          style: TextButton.styleFrom(
                            foregroundColor: Colors.red,
                          ),
                          child: const Text('Clear'),
                        ),
                    ],
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(12, 0, 12, 8),
                  child: TextField(
                    onChanged: (v) => bsSs(() => query = v),
                    decoration: InputDecoration(
                      hintText: 'Search…',
                      prefixIcon: const Icon(Icons.search_rounded, size: 18),
                      isDense: true,
                      contentPadding: const EdgeInsets.symmetric(
                        vertical: 8,
                        horizontal: 12,
                      ),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(AppRadius.sm),
                        borderSide: BorderSide(
                          color: AppColors.primary.withValues(alpha: 0.2),
                        ),
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: ListView(
                    controller: sc,
                    children: [
                      if (favs.isNotEmpty) ...[
                        Padding(
                          padding: const EdgeInsets.fromLTRB(16, 6, 16, 2),
                          child: Text(
                            '★  Favourites',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: Colors.amber.shade700,
                            ),
                          ),
                        ),
                        ...favs.map(itemTile),
                        const Divider(height: 1),
                      ],
                      if (others.isNotEmpty) ...[
                        Padding(
                          padding: const EdgeInsets.fromLTRB(16, 6, 16, 2),
                          child: Text(
                            'All',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: AppColors.primary.withValues(alpha: 0.5),
                            ),
                          ),
                        ),
                        ...others.map(itemTile),
                      ],
                      if (favs.isEmpty && others.isEmpty)
                        Padding(
                          padding: const EdgeInsets.all(24),
                          child: Text(
                            'No results',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              color: AppColors.primary.withValues(alpha: 0.35),
                            ),
                          ),
                        ),
                      const SizedBox(height: 24),
                    ],
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  // ── Diagnosis & Medicines ─────────────────────────────────────────────────────

  Widget _buildDiagnosisSection() {
    final filtered = _diagnosisSearch.isEmpty
        ? _diagnosisMasters
        : _diagnosisMasters
              .where(
                (d) => d.value.toLowerCase().contains(
                  _diagnosisSearch.toLowerCase(),
                ),
              )
              .toList();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Text(
              'Diagnoses',
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w800,
                color: AppColors.primary,
              ),
            ),
            if (_selectedDiagnosisIds.isNotEmpty) ...[
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: AppColors.teal,
                  borderRadius: BorderRadius.circular(AppRadius.md),
                ),
                child: Text(
                  '${_selectedDiagnosisIds.length} selected',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              const Spacer(),
              PressScaleWrapper(
                onTap: () => setState(() => _selectedDiagnosisIds.clear()),
                child: Text(
                  'Clear all',
                  style: TextStyle(
                    fontSize: 11,
                    color: Colors.red.shade600,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ],
        ),
        const SizedBox(height: 8),
        TextFormField(
          initialValue: _diagnosisSearch,
          onChanged: (v) => setState(() => _diagnosisSearch = v),
          style: const TextStyle(fontSize: 13),
          decoration: InputDecoration(
            hintText: 'Search diagnoses…',
            hintStyle: TextStyle(
              fontSize: 12,
              color: AppColors.primary.withValues(alpha: 0.35),
            ),
            prefixIcon: Icon(
              Icons.search_rounded,
              size: 18,
              color: AppColors.primary.withValues(alpha: 0.5),
            ),
            filled: true,
            fillColor: Colors.white,
            contentPadding: const EdgeInsets.symmetric(
              vertical: 8,
              horizontal: 12,
            ),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: BorderSide(
                color: AppColors.primary.withValues(alpha: 0.18),
              ),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: BorderSide(
                color: AppColors.primary.withValues(alpha: 0.18),
              ),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: BorderSide(color: AppColors.primary, width: 1.5),
            ),
          ),
        ),
        const SizedBox(height: 8),
        if (filtered.isEmpty)
          Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Text(
              _diagnosisSearch.isEmpty
                  ? 'No diagnoses available'
                  : 'No results for "$_diagnosisSearch"',
              style: TextStyle(
                fontSize: 12,
                color: AppColors.primary.withValues(alpha: 0.4),
                fontStyle: FontStyle.italic,
              ),
            ),
          )
        else
          DiagnosisGroupedChips(
            items: filtered,
            selectedIds: _selectedDiagnosisIds,
            chipBuilder: (d) {
              final selected = _selectedDiagnosisIds.contains(d.id);
              return Container(
                decoration: BoxDecoration(
                  color: selected ? AppColors.teal : Colors.white,
                  borderRadius: BorderRadius.circular(AppRadius.xl),
                  border: Border.all(
                    color: selected
                        ? AppColors.teal
                        : AppColors.primary.withValues(alpha: 0.2),
                  ),
                ),
                child: IntrinsicHeight(
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // ☆/★ favourite toggle
                      PressScaleWrapper(
                        onTap: () => _toggleDiagnosisFav(d),
                        child: Container(
                          padding: const EdgeInsets.only(
                            left: 8,
                            top: 6,
                            bottom: 6,
                            right: 4,
                          ),
                          child: Icon(
                            d.isFavourite
                                ? Icons.star_rounded
                                : Icons.star_border_rounded,
                            size: 13,
                            color: d.isFavourite
                                ? Colors.amber.shade600
                                : (selected
                                      ? Colors.white54
                                      : AppColors.primary.withValues(
                                          alpha: 0.3,
                                        )),
                          ),
                        ),
                      ),
                      Container(
                        width: 0.5,
                        color: selected
                            ? Colors.white24
                            : AppColors.primary.withValues(alpha: 0.1),
                      ),
                      // Main selection tap area
                      PressScaleWrapper(
                        onTap: () => setState(() {
                          if (selected)
                            _selectedDiagnosisIds.remove(d.id);
                          else
                            _selectedDiagnosisIds.add(d.id);
                        }),
                        child: Padding(
                          padding: const EdgeInsets.only(
                            left: 8,
                            right: 10,
                            top: 6,
                            bottom: 6,
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              if (selected)
                                const Icon(
                                  Icons.check_rounded,
                                  size: 13,
                                  color: Colors.white,
                                ),
                              if (selected) const SizedBox(width: 3),
                              Text(
                                d.value,
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w500,
                                  color: selected
                                      ? Colors.white
                                      : AppColors.primary,
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
            },
          ),
      ],
    );
  }

  Widget _prescRow(
    int idx,
    _PrescRow row,
    List<MedMasterItem> dosages,
    List<MedMasterItem> routes,
    List<MedItem> medicines,
  ) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.12)),
      ),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: _MedicineSearchField(
                  initialName: row.medicineName,
                  items: medicines,
                  onTextChanged: (t) {
                    row.medicineName = t.trim();
                    row.medicineId = null;
                  },
                  onSelected: (med) => setState(() {
                    row.medicineId = med.id;
                    row.medicineName = med.name;
                    if (med.dosageId != null) row.dosageId = med.dosageId;
                    if (med.duration != null) row.duration = med.duration!;
                  }),
                ),
              ),
              const SizedBox(width: 6),
              PressScaleWrapper(
                onTap: () => setState(() => _medicines.removeAt(idx)),
                child: const Icon(
                  Icons.remove_circle_rounded,
                  color: AppColors.red,
                  size: 22,
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Row(
            children: [
              Expanded(
                flex: 2,
                child: _miniDropdownItems<int?>(
                  value: row.dosageId,
                  hint: 'Dosage',
                  items: [
                    const DropdownMenuItem(value: null, child: Text('—')),
                    ...dosages.map(
                      (d) => DropdownMenuItem(
                        value: d.id,
                        child: Text(d.name, overflow: TextOverflow.ellipsis),
                      ),
                    ),
                  ],
                  onChanged: (v) => setState(() => row.dosageId = v),
                ),
              ),
              const SizedBox(width: 6),
              Expanded(
                flex: 2,
                child: _miniDropdownItems<int?>(
                  value: row.routeId,
                  hint: 'Route',
                  items: [
                    const DropdownMenuItem(value: null, child: Text('—')),
                    ...routes.map(
                      (r) => DropdownMenuItem(
                        value: r.id,
                        child: Text(r.name, overflow: TextOverflow.ellipsis),
                      ),
                    ),
                  ],
                  onChanged: (v) => setState(() => row.routeId = v),
                ),
              ),
              const SizedBox(width: 6),
              SizedBox(
                width: 68,
                child: _miniField(
                  initialValue: row.duration,
                  hint: 'Days',
                  inputType: TextInputType.number,
                  onChanged: (v) => row.duration = v,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 9),
                decoration: BoxDecoration(
                  color: const Color(0xFFF0F4F8),
                  border: Border(
                    top: BorderSide(
                      color: AppColors.primary.withValues(alpha: 0.15),
                    ),
                    right: BorderSide(
                      color: AppColors.primary.withValues(alpha: 0.15),
                    ),
                    bottom: BorderSide(
                      color: AppColors.primary.withValues(alpha: 0.15),
                    ),
                  ),
                  borderRadius: const BorderRadius.only(
                    topRight: Radius.circular(AppRadius.sm),
                    bottomRight: Radius.circular(AppRadius.sm),
                  ),
                ),
                child: Text(
                  'D',
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                    color: AppColors.primary,
                  ),
                ),
              ),
              const SizedBox(width: 6),
              SizedBox(
                width: 46,
                child: _miniField(
                  initialValue: row.quantity.toString(),
                  hint: 'Qty',
                  inputType: TextInputType.number,
                  onChanged: (v) => row.quantity = int.tryParse(v) ?? 1,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  // ── Helpers ───────────────────────────────────────────────────────────────────

  Widget _sectionHeader(String text, {Widget? trailing}) => Padding(
    padding: const EdgeInsets.only(top: 6, bottom: 8),
    child: Row(
      children: [
        Text(
          text,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w800,
            color: AppColors.primary,
          ),
        ),
        if (trailing != null) ...[const Spacer(), trailing],
      ],
    ),
  );

  Widget _addBtn(VoidCallback onTap) => PressScaleWrapper(
    onTap: onTap,
    child: Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: AppColors.teal.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(AppRadius.xl),
      ),
      child: Icon(Icons.add_rounded, color: AppColors.teal, size: 18),
    ),
  );

  Widget _emptyHint(String text) => Padding(
    padding: const EdgeInsets.only(bottom: 8),
    child: Text(
      text,
      style: TextStyle(
        fontSize: 12,
        color: AppColors.primary.withValues(alpha: 0.4),
        fontStyle: FontStyle.italic,
      ),
    ),
  );

  Widget _eyeLabel(String eye, bool isRe) => Padding(
    padding: const EdgeInsets.only(bottom: 4),
    child: Text(
      eye,
      style: TextStyle(
        fontSize: 11,
        fontWeight: FontWeight.w800,
        color: isRe ? Colors.red.shade700 : AppColors.primary,
      ),
    ),
  );

  Widget _examField(
    TextEditingController ctrl,
    String hint, {
    bool multiline = false,
    TextInputType? inputType,
    int rows = 1,
  }) {
    return TextFormField(
      controller: ctrl,
      keyboardType: multiline ? TextInputType.multiline : inputType,
      maxLines: multiline ? 3 : rows,
      style: const TextStyle(fontSize: 13),
      decoration: _examDeco(hint),
    );
  }

  InputDecoration _examDeco(String hint, {IconData? prefix}) => InputDecoration(
    hintText: hint,
    hintStyle: TextStyle(
      fontSize: 12,
      color: AppColors.primary.withValues(alpha: 0.35),
    ),
    prefixIcon: prefix != null
        ? Icon(
            prefix,
            size: 16,
            color: AppColors.primary.withValues(alpha: 0.5),
          )
        : null,
    filled: true,
    fillColor: Colors.white,
    contentPadding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
    border: OutlineInputBorder(
      borderRadius: BorderRadius.circular(10),
      borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.18)),
    ),
    enabledBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(10),
      borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.18)),
    ),
    focusedBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(10),
      borderSide: BorderSide(color: AppColors.primary, width: 1.5),
    ),
  );

  Widget _thCell(String text) => Expanded(
    child: Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Text(
        text,
        textAlign: TextAlign.center,
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w700,
          color: AppColors.primary.withValues(alpha: 0.7),
        ),
      ),
    ),
  );

  Widget _miniField({
    required String initialValue,
    required String hint,
    required void Function(String) onChanged,
    TextInputType inputType = TextInputType.text,
    FocusNode? focusNode,
  }) {
    return TextFormField(
      initialValue: initialValue,
      keyboardType: inputType,
      focusNode: focusNode,
      inputFormatters: inputType == TextInputType.number
          ? [FilteringTextInputFormatter.digitsOnly]
          : null,
      onChanged: onChanged,
      style: const TextStyle(fontSize: 12),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: TextStyle(
          fontSize: 11,
          color: AppColors.primary.withValues(alpha: 0.35),
        ),
        contentPadding: const EdgeInsets.symmetric(vertical: 8, horizontal: 10),
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
          borderSide: BorderSide(
            color: AppColors.primary.withValues(alpha: 0.15),
          ),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
          borderSide: BorderSide(
            color: AppColors.primary.withValues(alpha: 0.15),
          ),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
          borderSide: BorderSide(color: AppColors.primary),
        ),
      ),
    );
  }

  /// Tap target that opens [_pickFromMiniList] — replaces the native
  /// DropdownButtonFormField for since/unit/eye so the field-chain
  /// auto-advance (see _coRow/_kcoRow) can open it programmatically after
  /// the previous field is picked, which a native dropdown can't do.
  Widget _miniDropdown({
    required String value,
    required VoidCallback onTap,
    double width = 80,
  }) {
    return SizedBox(
      width: width,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppRadius.sm),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 9, horizontal: 8),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(AppRadius.sm),
            border: Border.all(
              color: AppColors.primary.withValues(alpha: 0.15),
            ),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Text(
                  value,
                  style: const TextStyle(fontSize: 11, color: Colors.black87),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              Icon(
                Icons.expand_more_rounded,
                size: 14,
                color: AppColors.primary.withValues(alpha: 0.5),
              ),
            ],
          ),
        ),
      ),
    );
  }

  /// Bottom-sheet list picker for short fixed option lists (since/unit/eye).
  /// Returns the picked value, or null if dismissed without a pick.
  Future<String?> _pickFromMiniList(
    String title,
    List<String> items,
    String current,
  ) {
    return showModalBottomSheet<String>(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              margin: const EdgeInsets.symmetric(vertical: 10),
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: AppColors.primary.withValues(alpha: 0.18),
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
              child: Align(
                alignment: Alignment.centerLeft,
                child: Text(
                  title,
                  style: TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 14,
                    color: AppColors.primary,
                  ),
                ),
              ),
            ),
            Flexible(
              child: ListView(
                shrinkWrap: true,
                children: items
                    .map(
                      (i) => ListTile(
                        dense: true,
                        title: Text(i, style: const TextStyle(fontSize: 13)),
                        trailing: i == current
                            ? Icon(
                                Icons.check_rounded,
                                color: AppColors.primary,
                                size: 18,
                              )
                            : null,
                        onTap: () => Navigator.pop(ctx, i),
                      ),
                    )
                    .toList(),
              ),
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }

  Widget _miniDropdownItems<T>({
    required T value,
    required List<DropdownMenuItem<T>> items,
    required void Function(T?) onChanged,
    required String hint,
  }) {
    return DropdownButtonFormField<T>(
      initialValue: value,
      isExpanded: true,
      icon: const Icon(Icons.expand_more, size: 14),
      style: const TextStyle(fontSize: 11, color: Colors.black87),
      hint: Text(hint, style: const TextStyle(fontSize: 11)),
      decoration: InputDecoration(
        contentPadding: const EdgeInsets.symmetric(vertical: 8, horizontal: 8),
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
          borderSide: BorderSide(
            color: AppColors.primary.withValues(alpha: 0.15),
          ),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
          borderSide: BorderSide(
            color: AppColors.primary.withValues(alpha: 0.15),
          ),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.sm),
          borderSide: BorderSide(color: AppColors.primary),
        ),
      ),
      items: items,
      onChanged: onChanged,
    );
  }
}

// ── Medicine search field ─────────────────────────────────────────────────────

// ── Master Autocomplete Field ─────────────────────────────────────────────────
class _MasterAutocompleteField extends StatefulWidget {
  final List<ExamMasterItem> items;
  final String initialValue;
  final String hint;
  final IconData icon;
  final Color color;
  final void Function(String) onChanged;

  /// Chip-mode: field is cleared after selection and this callback fires.
  final void Function(String)? onSelected;

  /// When set, a ★/☆ button appears per suggestion to toggle favourite.
  final void Function(ExamMasterItem)? onToggleFavourite;

  /// Fires only when a suggestion is actually tapped (not on every
  /// keystroke, unlike [onChanged]) — used to trigger field-chain
  /// auto-advance to the next field, matching web's behavior.
  final void Function(String)? onFinalPick;

  /// Lets a parent (e.g. a section body wiring the section-open auto-open)
  /// capture this field's own focus-request function, so it can show the
  /// suggestion list programmatically the instant a section sheet opens —
  /// there's no other way to open this private State's suggestion overlay
  /// from outside.
  final void Function(VoidCallback requestOpen)? exposeOpener;

  const _MasterAutocompleteField({
    super.key,
    required this.items,
    required this.initialValue,
    required this.hint,
    required this.icon,
    required this.color,
    required this.onChanged,
    this.onSelected,
    this.onToggleFavourite,
    this.onFinalPick,
    this.exposeOpener,
  });
  @override
  State<_MasterAutocompleteField> createState() =>
      _MasterAutocompleteFieldState();
}

class _MasterAutocompleteFieldState extends State<_MasterAutocompleteField> {
  late TextEditingController _ctrl;
  late FocusNode _focusNode;
  List<ExamMasterItem> _filtered = [];
  bool _showSuggestions = false;

  @override
  void initState() {
    super.initState();
    _ctrl = TextEditingController(text: widget.initialValue);
    _focusNode = FocusNode()..addListener(_onFocusChange);
  }

  @override
  void dispose() {
    _ctrl.dispose();
    _focusNode.dispose();
    super.dispose();
  }

  @override
  void didUpdateWidget(_MasterAutocompleteField old) {
    super.didUpdateWidget(old);
    if (old.items != widget.items && _showSuggestions) {
      final q = _ctrl.text.trim().toLowerCase();
      final favs = widget.items
          .where((i) => i.isFavourite && i.value.toLowerCase().contains(q))
          .toList();
      final rest = widget.items
          .where((i) => !i.isFavourite && i.value.toLowerCase().contains(q))
          .toList();
      setState(() {
        _filtered = [...favs, ...rest];
      });
    }
  }

  void _onFocusChange() {
    // Matches the web app: focusing the field shows the full master list
    // immediately (not just after typing), then typing filters it further.
    if (_focusNode.hasFocus) {
      _showAll();
    } else {
      setState(() => _showSuggestions = false);
    }
  }

  void _showAll() {
    if (widget.items.isEmpty) return;
    final favs = widget.items.where((i) => i.isFavourite).toList();
    final rest = widget.items.where((i) => !i.isFavourite).toList();
    setState(() {
      _filtered = [...favs, ...rest];
      _showSuggestions = true;
    });
  }

  void _onChanged(String v) {
    widget.onChanged(v);
    if (v.isEmpty) {
      _showAll();
      return;
    }
    final q = v.toLowerCase();
    final favs = widget.items
        .where((i) => i.isFavourite && i.value.toLowerCase().contains(q))
        .toList();
    final rest = widget.items
        .where((i) => !i.isFavourite && i.value.toLowerCase().contains(q))
        .toList();
    setState(() {
      _filtered = [...favs, ...rest];
      _showSuggestions = _filtered.isNotEmpty;
    });
  }

  void _select(ExamMasterItem item) {
    if (widget.onSelected != null) {
      _ctrl.clear();
      widget.onSelected!(item.value);
    } else {
      _ctrl.text = item.value;
      widget.onChanged(item.value);
      widget.onFinalPick?.call(item.value);
    }
    setState(() {
      _filtered = [];
      _showSuggestions = false;
    });
    _focusNode.unfocus();
  }

  @override
  Widget build(BuildContext context) {
    widget.exposeOpener?.call(_focusNode.requestFocus);
    final c = widget.color;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        TextFormField(
          controller: _ctrl,
          focusNode: _focusNode,
          onChanged: _onChanged,
          style: const TextStyle(fontSize: 12),
          decoration: InputDecoration(
            hintText: widget.hint,
            hintStyle: TextStyle(
              fontSize: 11,
              color: c.withValues(alpha: 0.35),
            ),
            prefixIcon: Icon(widget.icon, size: 16, color: c),
            contentPadding: const EdgeInsets.symmetric(
              vertical: 8,
              horizontal: 10,
            ),
            filled: true,
            fillColor: Colors.white,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppRadius.sm),
              borderSide: BorderSide(color: c.withValues(alpha: 0.15)),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppRadius.sm),
              borderSide: BorderSide(color: c.withValues(alpha: 0.15)),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppRadius.sm),
              borderSide: BorderSide(color: c),
            ),
          ),
        ),
        if (_showSuggestions)
          Container(
            margin: const EdgeInsets.only(top: 2),
            constraints: const BoxConstraints(maxHeight: 200),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(AppRadius.sm),
              border: Border.all(color: c.withValues(alpha: 0.15)),
              boxShadow: [
                BoxShadow(
                  color: c.withValues(alpha: 0.1),
                  blurRadius: 8,
                  offset: const Offset(0, 3),
                ),
              ],
            ),
            child: ListView(
              shrinkWrap: true,
              padding: EdgeInsets.zero,
              children: _filtered
                  .take(10)
                  .map(
                    (item) => InkWell(
                      onTap: () => _select(item),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 9,
                        ),
                        child: Row(
                          children: [
                            if (widget.onToggleFavourite != null)
                              PressScaleWrapper(
                                onTap: () => widget.onToggleFavourite!(item),
                                child: Padding(
                                  padding: const EdgeInsets.only(right: 8),
                                  child: Icon(
                                    item.isFavourite
                                        ? Icons.star_rounded
                                        : Icons.star_border_rounded,
                                    size: 16,
                                    color: item.isFavourite
                                        ? Colors.amber.shade600
                                        : Colors.grey.shade400,
                                  ),
                                ),
                              )
                            else if (item.isFavourite)
                              Padding(
                                padding: const EdgeInsets.only(right: 6),
                                child: Icon(
                                  Icons.star_rounded,
                                  size: 12,
                                  color: Colors.amber.shade600,
                                ),
                              ),
                            Expanded(
                              child: Text(
                                item.value,
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w500,
                                  color: c,
                                ),
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
      ],
    );
  }
}

class _MedicineSearchField extends StatefulWidget {
  final String initialName;
  final List<MedItem> items;
  final void Function(MedItem) onSelected;
  final void Function(String) onTextChanged;
  const _MedicineSearchField({
    required this.initialName,
    required this.items,
    required this.onSelected,
    required this.onTextChanged,
  });
  @override
  State<_MedicineSearchField> createState() => _MedicineSearchFieldState();
}

class _MedicineSearchFieldState extends State<_MedicineSearchField> {
  late TextEditingController _ctrl;
  late FocusNode _focusNode;
  List<MedItem> _results = [];
  bool _showSuggestions = false;

  @override
  void initState() {
    super.initState();
    _ctrl = TextEditingController(text: widget.initialName);
    _focusNode = FocusNode()..addListener(_onFocusChange);
  }

  @override
  void dispose() {
    _ctrl.dispose();
    _focusNode.dispose();
    super.dispose();
  }

  void _onFocusChange() {
    // Matches web + every other exam autocomplete field: focusing the
    // field shows the full medicine list immediately (no typing needed),
    // then typing filters it further, all client-side with no API call.
    if (_focusNode.hasFocus) {
      _showAll();
    } else {
      setState(() => _showSuggestions = false);
    }
  }

  void _showAll() {
    if (widget.items.isEmpty) return;
    setState(() {
      _results = widget.items;
      _showSuggestions = true;
    });
  }

  void _onChanged(String v) {
    widget.onTextChanged(v);
    if (v.isEmpty) {
      _showAll();
      return;
    }
    final q = v.toLowerCase();
    final filtered = widget.items
        .where(
          (m) =>
              m.name.toLowerCase().contains(q) ||
              (m.brandName?.toLowerCase().contains(q) ?? false),
        )
        .toList();
    setState(() {
      _results = filtered;
      _showSuggestions = _results.isNotEmpty;
    });
  }

  Widget _suggestionTile(MedItem m) {
    final meta = [
      if (m.medicineTypeName != null) m.medicineTypeName!,
      if (m.duration != null) '${m.duration} days',
    ].join(' · ');
    return InkWell(
      onTap: () {
        _ctrl.text = m.name;
        setState(() => _showSuggestions = false);
        widget.onSelected(m);
        _focusNode.unfocus();
      },
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    m.name,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: AppColors.primary,
                    ),
                  ),
                  if (meta.isNotEmpty)
                    Text(
                      meta,
                      style: TextStyle(
                        fontSize: 10,
                        color: AppColors.primary.withValues(alpha: 0.55),
                      ),
                    ),
                ],
              ),
            ),
            if (m.dosageText != null)
              Container(
                margin: const EdgeInsets.only(left: 6, top: 1),
                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                decoration: BoxDecoration(
                  color: const Color(0xFFE0F2FE),
                  borderRadius: BorderRadius.circular(AppRadius.xs),
                ),
                child: Text(
                  m.dosageText!,
                  style: const TextStyle(
                    fontSize: 10,
                    color: Color(0xFF0369A1),
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        TextFormField(
          controller: _ctrl,
          focusNode: _focusNode,
          onChanged: _onChanged,
          style: const TextStyle(fontSize: 12),
          decoration: InputDecoration(
            hintText: 'Search medicine...',
            hintStyle: TextStyle(
              fontSize: 11,
              color: AppColors.primary.withValues(alpha: 0.35),
            ),
            prefixIcon: Icon(
              Icons.medication_rounded,
              size: 16,
              color: AppColors.primary,
            ),
            contentPadding: const EdgeInsets.symmetric(
              vertical: 8,
              horizontal: 10,
            ),
            filled: true,
            fillColor: Colors.white,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppRadius.sm),
              borderSide: BorderSide(
                color: AppColors.primary.withValues(alpha: 0.15),
              ),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppRadius.sm),
              borderSide: BorderSide(
                color: AppColors.primary.withValues(alpha: 0.15),
              ),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppRadius.sm),
              borderSide: BorderSide(color: AppColors.primary),
            ),
          ),
        ),
        if (_showSuggestions)
          Container(
            margin: const EdgeInsets.only(top: 2),
            constraints: const BoxConstraints(maxHeight: 220),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(AppRadius.sm),
              border: Border.all(
                color: AppColors.primary.withValues(alpha: 0.15),
              ),
              boxShadow: [
                BoxShadow(
                  color: AppColors.primary.withValues(alpha: 0.1),
                  blurRadius: 8,
                  offset: Offset(0, 3),
                ),
              ],
            ),
            child: ListView(
              shrinkWrap: true,
              padding: EdgeInsets.zero,
              children: _results
                  .take(30)
                  .map((m) => _suggestionTile(m))
                  .toList(),
            ),
          ),
      ],
    );
  }
}
