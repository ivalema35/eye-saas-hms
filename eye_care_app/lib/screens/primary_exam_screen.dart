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
import '../widgets/ot/recommend_surgery_sheet.dart';


class _CoRow {
  String complaint; String since; String unit; String eye; String comment;
  // since/eye use '-' locally; toJson converts '-' → '' (empty) for API
  _CoRow({this.complaint = '', this.since = '-', this.unit = 'Days', this.eye = '-', this.comment = ''});
  Map<String, dynamic> toJson() => {
    'complaint': complaint,
    'since': since == '-' ? '' : since,
    'unit': unit,
    'eye': eye == '-' ? '' : eye,
    'comment': comment,
  };
}

class _KcoRow {
  String condition; String since; String unit; String comment;
  // K/C/O: default unit = Years (web default), since = '-'
  _KcoRow({this.condition = '', this.since = '-', this.unit = 'Years', this.comment = ''});
  Map<String, dynamic> toJson() => {
    'condition': condition,
    'since': since == '-' ? '' : since,
    'unit': unit,
    'comment': comment,
  };
}

class PrimaryExamScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final Patient patient;
  const PrimaryExamScreen({super.key, required this.user, required this.hospital, required this.patient});
  @override
  State<PrimaryExamScreen> createState() => _PrimaryExamScreenState();
}

class _PrimaryExamScreenState extends State<PrimaryExamScreen> {
  late PageController _pageCtrl;
  int _currentStep = 0;
  final _stepBarCtrl = ScrollController();

  bool _loading = true;
  String? _loadError;
  bool _saving    = false;
  bool _anySaved  = false; // pop(true) when back so patients list refreshes

  ExamFormData? _formData;
  int? _selectedDoctorId;

  // Section 1
  final List<String> _historyChips = [];
  final _historySearchCtrl = TextEditingController();
  final List<_CoRow> _coRows = [];
  final List<_KcoRow> _kcoRows = [];

  // Section 2: Vision — flat web keys: vn_re, vn_le, pnvn_re, pnvn_le, nrvn_re, nrvn_le
  final _vnRe = TextEditingController();
  final _vnLe = TextEditingController();
  final _pnvnRe = TextEditingController();
  final _pnvnLe = TextEditingController();
  final _nrvnRe = TextEditingController();
  final _nrvnLe = TextEditingController();

  // NCT — web keys: iop_re, iop_le
  final _nctIopRe = TextEditingController();
  final _nctIopLe = TextEditingController();

  // Section 3: Refraction — PG distance: ds,dc,ax,vn  near: ns,nc,na,near_vn
  static const _pgDistKeys = ['ds', 'dc', 'ax', 'vn'];
  static const _pgNearKeys = ['ns', 'nc', 'na', 'near_vn'];
  late final Map<String, Map<String, TextEditingController>> _pg;

  // ST distance: ds,dc,ax,vn  near: add,ns  + checkboxes
  static const _stDistKeys = ['ds', 'dc', 'ax', 'vn'];
  late final Map<String, Map<String, TextEditingController>> _st;
  // ST near ADD and NS stored separately
  final _stAddRe = TextEditingController();
  final _stAddLe = TextEditingController();
  final _stNsRe = TextEditingController();
  final _stNsLe = TextEditingController();
  bool _bifocal = false;
  bool _ndSeparate = false;
  bool _progressive = false;
  bool _computerUses = false;

  // Section 4: OE — flat web keys: sac_re, lid_re, ... other_le
  static const _oeFieldNames = ['sac', 'lid', 'conj', 'cornea', 'ac', 'iris', 'pupil', 'lens', 'em', 'covertest', 'other'];
  static const _oeLabels = {
    'sac': 'SAC', 'lid': 'Lid', 'conj': 'Conjunctiva', 'cornea': 'Cornea',
    'ac': 'Ant. Chamber', 'iris': 'Iris', 'pupil': 'Pupil', 'lens': 'Lens',
    'em': 'Ext. Mov.', 'covertest': 'Cover Test', 'other': 'Other',
  };
  late final Map<String, TextEditingController> _oeRe;
  late final Map<String, TextEditingController> _oeLe;
  // Mutable O/E master lists (local copy so toggle-favourite updates live)
  Map<String, List<ExamMasterItem>> _oeMasters = {};
  static const _oeMasterApiType = {
    'sac': 'sac', 'lid': 'lid', 'conj': 'conj', 'cornea': 'cornea',
    'ac': 'ac', 'iris': 'iris', 'pupil': 'pupil', 'lens': 'lens_master',
    'em': 'em', 'covertest': 'covertest',
  };
  // Pseudophakia — inline expansion when LENS = Pseudophakia
  String _pseudophakiaOpTypeRe = 'Phaco';
  String _pseudophakiaOpTypeLe = 'Phaco';
  final _pseudophakiaExpenseRe  = TextEditingController();
  final _pseudophakiaExpenseLe  = TextEditingController();
  final _pseudophakiaHospitalRe = TextEditingController();
  final _pseudophakiaHospitalLe = TextEditingController();

  // Fundus — web keys: disc_re, fr_re, comment_re, disc_le, fr_le, comment_le
  final _fundusDiscRe    = TextEditingController();
  final _fundusDiscLe    = TextEditingController();
  final _fundusFrRe      = TextEditingController();
  final _fundusFrLe      = TextEditingController();
  final _fundusCommentRe = TextEditingController();
  final _fundusCommentLe = TextEditingController();
  // Mutable local copies for toggle-favourite
  List<ExamMasterItem> _fundusDiscMasters = [];
  List<ExamMasterItem> _fundusFrMasters   = [];
  // Pseudophakia panel's Hospital field autocompletes from the Referrers
  // master list, matching web (datalist sourced from $masters['referrers']).
  List<ExamMasterItem> _referrers = [];

  // Dilate
  String _dilate = 'No';
  final _dilationTimeCtrl = TextEditingController();

  static const _stepNames = ['C/O', 'K/C/O & H/O', 'Vision', 'PG', 'ST', 'NCT', 'O/E', 'Fundus', 'Dilate'];

  @override
  void initState() {
    super.initState();
    _pageCtrl = PageController();
    _pg = {
      're': {for (final k in [..._pgDistKeys, ..._pgNearKeys]) k: TextEditingController()},
      'le': {for (final k in [..._pgDistKeys, ..._pgNearKeys]) k: TextEditingController()},
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
    _pageCtrl.dispose();
    _stepBarCtrl.dispose();
    _historySearchCtrl.dispose();
    _vnRe.dispose(); _vnLe.dispose(); _pnvnRe.dispose(); _pnvnLe.dispose(); _nrvnRe.dispose(); _nrvnLe.dispose();
    _nctIopRe.dispose(); _nctIopLe.dispose();
    for (final e in _pg.values) { for (final c in e.values) c.dispose(); }
    for (final e in _st.values) { for (final c in e.values) c.dispose(); }
    _stAddRe.dispose(); _stAddLe.dispose(); _stNsRe.dispose(); _stNsLe.dispose();
    for (final c in _oeRe.values) c.dispose();
    for (final c in _oeLe.values) c.dispose();
    _pseudophakiaExpenseRe.dispose(); _pseudophakiaExpenseLe.dispose();
    _pseudophakiaHospitalRe.dispose(); _pseudophakiaHospitalLe.dispose();
    _fundusDiscRe.dispose(); _fundusDiscLe.dispose();
    _fundusFrRe.dispose(); _fundusFrLe.dispose();
    _fundusCommentRe.dispose(); _fundusCommentLe.dispose();
    _dilationTimeCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadAll() async {
    final cacheHit = ExamMastersService.instance.isCacheValid;
    if (!cacheHit) setState(() { _loading = true; _loadError = null; });

    try {
      // Step 1: Masters first (22 parallel calls) — if already cached this is instant.
      // Kept separate so the 22 master calls don't compete with exam data fetch.
      final formData = await ExamService.instance.loadFormData();

      // Step 2: Fetch exam data.
      final existing = await ExamService.instance.loadPrimaryExam(widget.patient.id);
      final referrers = await ReferrerService.instance.fetchAll();
      if (!mounted) return;
      setState(() {
        _formData = formData;
        _oeMasters        = { for (final f in _oeMasterApiType.keys) f: List<ExamMasterItem>.from(formData.masters.oeListFor(f)) };
        _fundusDiscMasters = List<ExamMasterItem>.from(formData.masters.disc);
        _fundusFrMasters   = List<ExamMasterItem>.from(formData.masters.fr);
        _referrers = referrers.map((r) => ExamMasterItem(id: r.id, value: r.name, isFavourite: false)).toList();
        _selectedDoctorId = widget.patient.doctor?.id;
        if (existing != null) _prefill(existing);
        _loading = false;
        _loadError = null;
      });
    } catch (e) {
      if (mounted) setState(() { _loadError = e.toString(); _loading = false; });
    }
  }

  void _prefill(Map<String, dynamic> exam) {
    final raw = exam['exam_data'];
    Map<String, dynamic> ed = {};
    if (raw is Map<String, dynamic>) {
      ed = raw;
    } else if (raw is String && raw.isNotEmpty) {
      try { ed = jsonDecode(raw) as Map<String, dynamic>? ?? {}; } catch (_) {}
    }

    final historyStr = ed['history'] as String? ?? '';
    _historyChips
      ..clear()
      ..addAll(historyStr.split(',').map((s) => s.trim()).where((s) => s.isNotEmpty));

    for (final r in (ed['co_rows'] as List? ?? [])) {
      final m = r as Map<String, dynamic>;
      final rawSince = m['since'] as String? ?? '';
      final rawEye   = m['eye']   as String? ?? '';
      _coRows.add(_CoRow(
        complaint: m['complaint'] as String? ?? '',
        since: rawSince.isEmpty ? '-' : rawSince,
        unit: m['unit'] as String? ?? 'Days',
        eye: rawEye.isEmpty ? '-' : rawEye,
        comment: m['comment'] as String? ?? '',
      ));
    }
    for (final r in (ed['kco_rows'] as List? ?? [])) {
      final m = r as Map<String, dynamic>;
      final rawSince = m['since'] as String? ?? '';
      _kcoRows.add(_KcoRow(
        condition: m['condition'] as String? ?? '',
        since: rawSince.isEmpty ? '-' : rawSince,
        unit: m['unit'] as String? ?? 'Years',
        comment: m['comment'] as String? ?? '',
      ));
    }

    // Vision — flat web keys
    final vd = ed['vision'] as Map<String, dynamic>? ?? {};
    _vnRe.text = vd['vn_re'] as String? ?? '';
    _vnLe.text = vd['vn_le'] as String? ?? '';
    _pnvnRe.text = vd['pnvn_re'] as String? ?? '';
    _pnvnLe.text = vd['pnvn_le'] as String? ?? '';
    _nrvnRe.text = vd['nrvn_re'] as String? ?? '';
    _nrvnLe.text = vd['nrvn_le'] as String? ?? '';

    // PG
    final pgd = ed['pg'] as Map<String, dynamic>? ?? {};
    for (final eye in ['re', 'le']) {
      final ev = pgd[eye] as Map<String, dynamic>? ?? {};
      for (final k in [..._pgDistKeys, ..._pgNearKeys]) {
        _pg[eye]![k]!.text = ev[k] as String? ?? '';
      }
    }

    // ST
    final std = ed['st'] as Map<String, dynamic>? ?? {};
    for (final eye in ['re', 'le']) {
      final ev = std[eye] as Map<String, dynamic>? ?? {};
      for (final k in _stDistKeys) {
        _st[eye]![k]!.text = ev[k] as String? ?? '';
      }
    }
    _stAddRe.text = (std['re'] as Map<String, dynamic>?)?['add'] as String? ?? '';
    _stAddLe.text = (std['le'] as Map<String, dynamic>?)?['add'] as String? ?? '';
    _stNsRe.text = (std['re'] as Map<String, dynamic>?)?['ns'] as String? ?? '';
    _stNsLe.text = (std['le'] as Map<String, dynamic>?)?['ns'] as String? ?? '';
    _bifocal = std['bifocal'] == true;
    _ndSeparate = std['nd_separate'] == true;
    _progressive = std['progressive'] == true;
    _computerUses = std['computer_uses'] == true;

    // NCT — web keys iop_re, iop_le
    final nct = ed['nct'] as Map<String, dynamic>? ?? {};
    _nctIopRe.text = nct['iop_re'] as String? ?? '';
    _nctIopLe.text = nct['iop_le'] as String? ?? '';

    // OE — flat web keys field_re / field_le
    final oed = ed['oe'] as Map<String, dynamic>? ?? {};
    for (final f in _oeFieldNames) {
      _oeRe[f]!.text = oed['${f}_re'] as String? ?? '';
      _oeLe[f]!.text = oed['${f}_le'] as String? ?? '';
    }
    final pseudoRe = oed['pseudophakia_re'] as Map<String, dynamic>? ?? {};
    _pseudophakiaOpTypeRe      = pseudoRe['operation_type']    as String? ?? 'Phaco';
    _pseudophakiaExpenseRe.text  = pseudoRe['operation_expense'] as String? ?? '';
    _pseudophakiaHospitalRe.text = pseudoRe['hospital_name']    as String? ?? '';
    final pseudoLe = oed['pseudophakia_le'] as Map<String, dynamic>? ?? {};
    _pseudophakiaOpTypeLe      = pseudoLe['operation_type']    as String? ?? 'Phaco';
    _pseudophakiaExpenseLe.text  = pseudoLe['operation_expense'] as String? ?? '';
    _pseudophakiaHospitalLe.text = pseudoLe['hospital_name']    as String? ?? '';

    // Fundus — web keys disc_re, fr_re, comment_re etc.
    final fundus = ed['fundus'] as Map<String, dynamic>? ?? {};
    _fundusDiscRe.text = fundus['disc_re'] as String? ?? '';
    _fundusDiscLe.text = fundus['disc_le'] as String? ?? '';
    _fundusFrRe.text = fundus['fr_re'] as String? ?? '';
    _fundusFrLe.text = fundus['fr_le'] as String? ?? '';
    _fundusCommentRe.text = fundus['comment_re'] as String? ?? '';
    _fundusCommentLe.text = fundus['comment_le'] as String? ?? '';

    _dilate = ed['dilate'] as String? ?? 'No';
    _dilationTimeCtrl.text = (exam['dilation_time'] as int?)?.toString() ?? '';
  }

  Future<void> _save() async {
    if (_saving) return;
    setState(() => _saving = true);
    try {
      await ExamService.instance.savePrimaryExam(widget.patient.id, _buildStepPayload(_currentStep));
      if (!mounted) return;
      _anySaved = true;
      showAppSnackBar(context, '${_stepNames[_currentStep]} saved.', isSuccess: true);
    } catch (e) {
      if (mounted) showAppSnackBar(context, e.toString(), isError: true, duration: const Duration(seconds: 4));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Map<String, dynamic> _buildStepPayload(int step) {
    void setIfNonEmpty(Map m, String k, String v) { if (v.isNotEmpty) m[k] = v; }
    final examData = <String, dynamic>{};

    switch (step) {
      case 0: // C/O
        examData['history'] = _historyChips.join(', ');
        examData['co_rows'] = _coRows.map((r) => r.toJson()).toList();
      case 1: // K/C/O & H/O
        examData['kco_rows'] = _kcoRows.map((r) => r.toJson()).toList();
      case 2: // Vision
        final vision = <String, dynamic>{};
        setIfNonEmpty(vision, 'vn_re',   _vnRe.text.trim());
        setIfNonEmpty(vision, 'vn_le',   _vnLe.text.trim());
        setIfNonEmpty(vision, 'pnvn_re', _pnvnRe.text.trim());
        setIfNonEmpty(vision, 'pnvn_le', _pnvnLe.text.trim());
        setIfNonEmpty(vision, 'nrvn_re', _nrvnRe.text.trim());
        setIfNonEmpty(vision, 'nrvn_le', _nrvnLe.text.trim());
        if (vision.isNotEmpty) examData['vision'] = vision;
      case 3: // PG
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
      case 4: // ST
        final st = <String, dynamic>{};
        for (final eye in ['re', 'le']) {
          final ev = <String, String>{};
          for (final k in _stDistKeys) {
            final v = _st[eye]![k]!.text.trim();
            if (v.isNotEmpty) ev[k] = v;
          }
          final addVal = eye == 're' ? _stAddRe.text.trim() : _stAddLe.text.trim();
          final nsVal  = eye == 're' ? _stNsRe.text.trim()  : _stNsLe.text.trim();
          if (addVal.isNotEmpty) ev['add'] = addVal;
          if (nsVal.isNotEmpty)  ev['ns']  = nsVal;
          // NC mirrors DC, NA mirrors AX (readonly on NEAR row per web)
          final dcVal = _st[eye]!['dc']!.text.trim();
          final axVal = _st[eye]!['ax']!.text.trim();
          if (dcVal.isNotEmpty) ev['nc'] = dcVal;
          if (axVal.isNotEmpty) ev['na'] = axVal;
          if (ev.isNotEmpty) st[eye] = ev;
        }
        if (_bifocal)      st['bifocal']      = true;
        if (_ndSeparate)   st['nd_separate']   = true;
        if (_progressive)  st['progressive']   = true;
        if (_computerUses) st['computer_uses'] = true;
        if (st.isNotEmpty) examData['st'] = st;
      case 5: // NCT
        final nct = <String, dynamic>{};
        setIfNonEmpty(nct, 'iop_re', _nctIopRe.text.trim());
        setIfNonEmpty(nct, 'iop_le', _nctIopLe.text.trim());
        if (nct.isNotEmpty) examData['nct'] = nct;
      case 6: // O/E
        final oe = <String, dynamic>{};
        for (final f in _oeFieldNames) {
          setIfNonEmpty(oe, '${f}_re', _oeRe[f]!.text.trim());
          setIfNonEmpty(oe, '${f}_le', _oeLe[f]!.text.trim());
        }
        bool _isPseudo(String eye) =>
            (eye == 're' ? _oeRe['lens'] : _oeLe['lens'])!.text.toLowerCase().contains('pseudophakia');
        if (_isPseudo('re')) oe['pseudophakia_re'] = {
          'operation_type':    _pseudophakiaOpTypeRe,
          'operation_expense': _pseudophakiaExpenseRe.text.trim(),
          'hospital_name':     _pseudophakiaHospitalRe.text.trim(),
        };
        if (_isPseudo('le')) oe['pseudophakia_le'] = {
          'operation_type':    _pseudophakiaOpTypeLe,
          'operation_expense': _pseudophakiaExpenseLe.text.trim(),
          'hospital_name':     _pseudophakiaHospitalLe.text.trim(),
        };
        if (oe.isNotEmpty) examData['oe'] = oe;
      case 7: // Fundus
        final fundus = <String, dynamic>{};
        setIfNonEmpty(fundus, 'disc_re',    _fundusDiscRe.text.trim());
        setIfNonEmpty(fundus, 'disc_le',    _fundusDiscLe.text.trim());
        setIfNonEmpty(fundus, 'fr_re',      _fundusFrRe.text.trim());
        setIfNonEmpty(fundus, 'fr_le',      _fundusFrLe.text.trim());
        setIfNonEmpty(fundus, 'comment_re', _fundusCommentRe.text.trim());
        setIfNonEmpty(fundus, 'comment_le', _fundusCommentLe.text.trim());
        if (fundus.isNotEmpty) examData['fundus'] = fundus;
      case 8: // Dilate
        examData['dilate'] = _dilate;
    }

    final payload = <String, dynamic>{
      'doctor_id': _selectedDoctorId,
      'exam_data': examData,
    };
    if (step == 8 && _dilate == 'Yes' && _dilationTimeCtrl.text.trim().isNotEmpty) {
      payload['dilation_time'] = int.tryParse(_dilationTimeCtrl.text.trim());
    }
    return payload;
  }

  // ── Step navigation ───────────────────────────────────────────────────────────

  void _goToStep(int step) {
    setState(() => _currentStep = step);
    _pageCtrl.animateToPage(step, duration: const Duration(milliseconds: 300), curve: Curves.easeInOut);
    // Scroll step bar to show active step
    _stepBarCtrl.animateTo(step * 110.0, duration: const Duration(milliseconds: 300), curve: Curves.easeInOut);
  }

  Widget _buildStepBar() {
    return Container(
      color: AppColors.primary,
      height: 50,
      child: ListView.builder(
        controller: _stepBarCtrl,
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
        itemCount: _stepNames.length,
        itemBuilder: (_, i) {
          final isActive = i == _currentStep;
          return GestureDetector(
            onTap: () => _goToStep(i),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              margin: const EdgeInsets.only(right: 6),
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
              decoration: BoxDecoration(
                color: isActive ? Colors.white : Colors.white.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(AppRadius.xl),
                border: Border.all(color: isActive ? Colors.white : Colors.white.withValues(alpha: 0.3)),
              ),
              child: Text(
                '${i + 1}. ${_stepNames[i]}',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: isActive ? FontWeight.w800 : FontWeight.w500,
                  color: isActive ? AppColors.primary : Colors.white,
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildNavButtons() {
    final isFirst = _currentStep == 0;
    final isLast  = _currentStep == _stepNames.length - 1;
    return SafeArea(
      top: false,
      child: Container(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
      decoration: const BoxDecoration(color: Colors.white, border: Border(top: BorderSide(color: Color(0xFFE0E0E0)))),
      child: Row(children: [
        // ── Previous ────────────────────────────────────────────────────────
        if (!isFirst) ...[
          Expanded(
            child: OutlinedButton.icon(
              onPressed: () => _goToStep(_currentStep - 1),
              icon: const Icon(Icons.arrow_back_rounded, size: 16),
              label: Text(_stepNames[_currentStep - 1], maxLines: 1, overflow: TextOverflow.ellipsis),
              style: OutlinedButton.styleFrom(foregroundColor: AppColors.primary, side: BorderSide(color: AppColors.primary), padding: EdgeInsets.symmetric(vertical: 10)),
            ),
          ),
          const SizedBox(width: 8),
        ],
        // ── Save Exam ────────────────────────────────────────────────────────
        Expanded(
          flex: 2,
          child: ElevatedButton.icon(
            onPressed: _saving ? null : _save,
            icon: _saving
              ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
              : const Icon(Icons.save_rounded, size: 16, color: Colors.white),
            label: Text(_saving ? 'Saving…' : 'Save ${_stepNames[_currentStep]}',
                maxLines: 1, overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontWeight: FontWeight.w700, color: Colors.white)),
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white, padding: EdgeInsets.symmetric(vertical: 10)),
          ),
        ),
        // ── Next ─────────────────────────────────────────────────────────────
        if (!isLast) ...[
          const SizedBox(width: 8),
          Expanded(
            child: ElevatedButton.icon(
              onPressed: () => _goToStep(_currentStep + 1),
              icon: const Icon(Icons.arrow_forward_rounded, size: 16),
              label: Text(_stepNames[_currentStep + 1], maxLines: 1, overflow: TextOverflow.ellipsis),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary.withValues(alpha: 0.75),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 10),
              ),
            ),
          ),
        ],
      ]),
    ),
    );
  }

  Widget _iopBadge(String label, Color color) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
    decoration: BoxDecoration(color: color.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(AppRadius.xl), border: Border.all(color: color.withValues(alpha: 0.4))),
    child: Row(mainAxisSize: MainAxisSize.min, children: [
      Container(width: 8, height: 8, decoration: BoxDecoration(color: color, shape: BoxShape.circle)),
      const SizedBox(width: 6),
      Text(label, style: TextStyle(fontSize: 11, color: color, fontWeight: FontWeight.w600)),
    ]),
  );

  // ── Favourite toggle helpers ──────────────────────────────────────────────────

  Future<void> _refreshMasters() async {
    final masters = await ExamMastersService.instance.fetchAll(forceRefresh: true);
    if (!mounted) return;
    setState(() {
      _formData = ExamFormData(
        dosages: _formData?.dosages ?? [],
        routes: _formData?.routes ?? [],
        masters: masters,
      );
      _oeMasters         = { for (final f in _oeMasterApiType.keys) f: List<ExamMasterItem>.from(masters.oeListFor(f)) };
      _fundusDiscMasters = List<ExamMasterItem>.from(masters.disc);
      _fundusFrMasters   = List<ExamMasterItem>.from(masters.fr);
    });
  }

  Future<void> _toggleFavourite(String masterType, ExamMasterItem item) async {
    try {
      await SimpleMasterService.instance.toggleFavourite('masters/detail/$masterType', item.id);
      await _refreshMasters();
    } catch (e) {
      debugPrint('[Exam] toggleFavourite error: $e');
    }
  }

  // ── Step builder methods ──────────────────────────────────────────────────────

  Widget _buildCoStep() {
    final favs = ExamMastersData.sortedFavFirst(
      _formData?.masters.chiefComplaints ?? [],
    ).where((c) => c.isFavourite).toList();
    return ListView(padding: const EdgeInsets.fromLTRB(16, 12, 16, 100), children: [
      if (favs.isNotEmpty) _buildFavouritesSection(
        favs,
        onUnfavourite: (item) => _toggleFavourite('complaints', item),
      ),
      _sectionHeader('Complaints (C/O)', trailing: _addBtn(() => setState(() => _coRows.add(_CoRow())))),
      if (_coRows.isEmpty) _emptyHint('Tap + to add a complaint')
      else ..._coRows.asMap().entries.map((e) => _coRow(e.key, e.value)),
    ]);
  }

  Widget _buildFavouritesSection(List<ExamMasterItem> favs, {required void Function(ExamMasterItem) onUnfavourite}) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
      decoration: BoxDecoration(
        color: Colors.amber.shade50,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: Colors.amber.shade200),
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Icon(Icons.star_rounded, size: 13, color: Colors.amber.shade700),
          const SizedBox(width: 6),
          Text('FAVOURITES — TAP TO ADD',
              style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800,
                  color: Colors.amber.shade800, letterSpacing: 0.5)),
        ]),
        const SizedBox(height: 8),
        Wrap(
          spacing: 6, runSpacing: 6,
          children: favs.map((item) => Container(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(AppRadius.xl),
              border: Border.all(color: Colors.amber.shade300),
            ),
            child: Row(mainAxisSize: MainAxisSize.min, children: [
              GestureDetector(
                onTap: () => setState(() => _coRows.add(_CoRow(complaint: item.value))),
                child: Padding(
                  padding: const EdgeInsets.only(left: 10, top: 5, bottom: 5, right: 4),
                  child: Text(item.value, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600,
                      color: Colors.amber.shade900)),
                ),
              ),
              GestureDetector(
                onTap: () => onUnfavourite(item),
                child: Padding(
                  padding: const EdgeInsets.only(right: 8, top: 5, bottom: 5),
                  child: Icon(Icons.star_rounded, size: 13, color: Colors.amber.shade600),
                ),
              ),
            ]),
          )).toList(),
        ),
      ]),
    );
  }

  Widget _buildKcoStep() {
    final kcoFavs = (_formData?.masters.kcos ?? []).where((i) => i.isFavourite).toList();
    return ListView(padding: const EdgeInsets.fromLTRB(16, 12, 16, 100), children: [
      // K/C/O favourite pills
      if (kcoFavs.isNotEmpty) Container(
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
        decoration: BoxDecoration(
          color: Colors.amber.shade50,
          borderRadius: BorderRadius.circular(AppRadius.md),
          border: Border.all(color: Colors.amber.shade200),
        ),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Icon(Icons.star_rounded, size: 13, color: Colors.amber.shade700),
            const SizedBox(width: 6),
            Text('FAVOURITES — TAP TO ADD', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800,
                color: Colors.amber.shade800, letterSpacing: 0.5)),
          ]),
          const SizedBox(height: 8),
          Wrap(spacing: 6, runSpacing: 6, children: kcoFavs.map((item) => Container(
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.xl),
                border: Border.all(color: Colors.amber.shade300)),
            child: Row(mainAxisSize: MainAxisSize.min, children: [
              GestureDetector(
                onTap: () => setState(() => _kcoRows.add(_KcoRow(condition: item.value))),
                child: Padding(padding: const EdgeInsets.only(left: 10, top: 5, bottom: 5, right: 4),
                    child: Text(item.value, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600,
                        color: Colors.amber.shade900))),
              ),
              GestureDetector(
                onTap: () => _toggleFavourite('kcos', item),
                child: Padding(padding: const EdgeInsets.only(right: 8, top: 5, bottom: 5),
                    child: Icon(Icons.star_rounded, size: 13, color: Colors.amber.shade600)),
              ),
            ]),
          )).toList()),
        ]),
      ),
      _sectionHeader('Known Conditions (K/CO)', trailing: _addBtn(() => setState(() => _kcoRows.add(_KcoRow())))),
      if (_kcoRows.isEmpty) _emptyHint('Tap + to add a known condition')
      else ..._kcoRows.asMap().entries.map((e) => _kcoRow(e.key, e.value)),
      const SizedBox(height: 12),
      _sectionHeader('History (H/O)'),
      _buildHistoryChipsSection(),
    ]);
  }

  Widget _buildVisionStep() => ListView(padding: const EdgeInsets.fromLTRB(16, 12, 16, 100), children: [
    _sectionHeader('Visual Acuity'),
    Container(
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.12))),
      child: Column(children: [
        Container(
          decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.06), borderRadius: BorderRadius.vertical(top: Radius.circular(11))),
          child: Row(children: [const SizedBox(width: 40), _thCell('VN (Dist)'), _thCell('Pinhole'), _thCell('Near VN')]),
        ),
        _visionEyeRow('RE', true, _vnRe, _pnvnRe, _nrvnRe, 'vn', 'pnvn', 'nrvn'),
        _visionEyeRow('LE', false, _vnLe, _pnvnLe, _nrvnLe, 'vn', 'pnvn', 'nrvn'),
      ]),
    ),
  ]);

  Widget _buildPgStep() => ListView(padding: const EdgeInsets.fromLTRB(16, 12, 16, 100), children: [
    _sectionHeader('PG — Prescription Glasses'),
    _pgTable(),
  ]);

  Widget _buildStStep() => ListView(padding: const EdgeInsets.fromLTRB(16, 12, 16, 100), children: [
    _sectionHeader('ST — Subjective Test'),
    _stTable(),
    const SizedBox(height: 8),
    _stCheckboxRow(),
  ]);

  Widget _buildNctStep() => ListView(padding: const EdgeInsets.fromLTRB(16, 12, 16, 100), children: [
    _sectionHeader('NCT — Non-Contact Tonometry'),
    Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.md),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.12)),
        boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.05), blurRadius: 6, offset: Offset(0, 2))],
      ),
      child: Column(children: [
        Container(
          decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.vertical(top: Radius.circular(11))),
          child: Row(children: [
            const SizedBox(width: 80),
            Expanded(child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                const Icon(Icons.remove_red_eye_rounded, color: Colors.white, size: 12),
                const SizedBox(width: 4),
                const Text('Right Eye (RE)', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Colors.white)),
              ]),
            )),
            Container(width: 1, height: 36, color: Colors.white.withValues(alpha: 0.2)),
            Expanded(child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                const Icon(Icons.remove_red_eye_rounded, color: Colors.white, size: 12),
                const SizedBox(width: 4),
                const Text('Left Eye (LE)', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Colors.white)),
              ]),
            )),
          ]),
        ),
        IntrinsicHeight(child: Row(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          Container(
            width: 80,
            padding: const EdgeInsets.symmetric(vertical: 16),
            decoration: const BoxDecoration(color: Color(0xFFFAFBFC)),
            child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
              const Text('IOP', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Color(0xFF1e293b), letterSpacing: .04)),
              const SizedBox(height: 2),
              Text('mmHg', style: TextStyle(fontSize: 10, color: AppColors.primary.withValues(alpha: 0.4), fontWeight: FontWeight.w500)),
            ]),
          ),
          Expanded(child: Padding(padding: const EdgeInsets.all(12), child: _nctField(_nctIopRe))),
          Container(width: 1, color: AppColors.primary.withValues(alpha: 0.08)),
          Expanded(child: Padding(padding: const EdgeInsets.all(12), child: _nctField(_nctIopLe))),
        ])),
      ]),
    ),
    const SizedBox(height: 12),
    Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.08)),
      ),
      child: Wrap(alignment: WrapAlignment.spaceAround, spacing: 8, runSpacing: 6, children: [
        _iopBadge('Normal 10–21', const Color(0xFF22C55E)),
        _iopBadge('Borderline 22–24', const Color(0xFFF59E0B)),
        _iopBadge('High ≥25', const Color(0xFFEF4444)),
      ]),
    ),
  ]);

  Widget _buildOeStep() => ListView(padding: const EdgeInsets.fromLTRB(16, 12, 16, 100), children: [
    _sectionHeader('O/E — On Examination'),
    _oeTable(),
  ]);

  Widget _buildFundusStep() => ListView(padding: const EdgeInsets.fromLTRB(16, 12, 16, 100), children: [
    _sectionHeader('Fundus Examination'),
    _fundusTable(),
  ]);

  Widget _buildDilateStep() => ListView(padding: const EdgeInsets.fromLTRB(16, 12, 16, 100), children: [
    _sectionHeader('Dilation'),
    Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.12))),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text('Dilate patient?', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.primary)),
        const SizedBox(height: 10),
        Row(children: [
          ChoiceChip(label: Text('Yes'), selected: _dilate == 'Yes', selectedColor: AppColors.primary,
              labelStyle: TextStyle(color: _dilate == 'Yes' ? Colors.white : AppColors.primary, fontWeight: FontWeight.w700),
              onSelected: (_) => setState(() {
                _dilate = 'Yes';
                if (_dilationTimeCtrl.text.trim().isEmpty) _dilationTimeCtrl.text = '40';
              })),
          const SizedBox(width: 8),
          ChoiceChip(label: Text('No'), selected: _dilate == 'No', selectedColor: AppColors.primary,
              labelStyle: TextStyle(color: _dilate == 'No' ? Colors.white : AppColors.primary, fontWeight: FontWeight.w700),
              onSelected: (_) => setState(() { _dilate = 'No'; _dilationTimeCtrl.clear(); })),
          if (_dilate == 'Yes') ...[
            const SizedBox(width: 12),
            SizedBox(width: 90, child: _examField(_dilationTimeCtrl, 'Mins', inputType: TextInputType.number)),
            const Text(' min', style: TextStyle(fontSize: 12, color: Colors.black54)),
          ],
        ]),
      ]),
    ),
  ]);

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) Navigator.of(context).pop(_anySaved);
      },
      child: Scaffold(
      backgroundColor: const Color(0xFFEBF5FB),
      body: Column(children: [
        _buildAppBar(),
        if (!_loading && _loadError == null) _buildStepBar(),
        Expanded(
          child: _loading
              ? Center(child: CircularProgressIndicator(color: AppColors.primary))
              : _loadError != null
                  ? _buildError()
                  : PageView(
                      controller: _pageCtrl,
                      physics: const NeverScrollableScrollPhysics(),
                      onPageChanged: (i) => setState(() => _currentStep = i),
                      children: [
                        _buildCoStep(),
                        _buildKcoStep(),
                        _buildVisionStep(),
                        _buildPgStep(),
                        _buildStStep(),
                        _buildNctStep(),
                        _buildOeStep(),
                        _buildFundusStep(),
                        _buildDilateStep(),
                      ],
                    ),
        ),
        if (!_loading && _loadError == null) _buildNavButtons(),
      ]),
      ),  // Scaffold
    );    // PopScope
  }

  Widget _buildAppBar() {
    final p = widget.patient;
    return Container(
      decoration: BoxDecoration(gradient: LinearGradient(colors: [AppColors.primary, AppColors.blueLight], begin: Alignment.topLeft, end: Alignment.bottomRight)),
      child: SafeArea(bottom: false, child: Padding(
        padding: const EdgeInsets.fromLTRB(8, 8, 16, 0),
        child: Row(children: [
          IconButton(icon: const Icon(Icons.arrow_back_rounded, color: Colors.white), onPressed: () => Navigator.of(context).pop(_anySaved)),
          const SizedBox(width: 4),
          Container(width: 40, height: 40,
              decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(10)),
              child: const Icon(Icons.remove_red_eye_rounded, color: Colors.white, size: 20)),
          const SizedBox(width: 10),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const Text('Primary Examination', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800)),
            const SizedBox(height: 2),
            Text('${p.fullName}  ·  MRD: ${p.patientCode}', style: TextStyle(color: Colors.white.withValues(alpha: 0.75), fontSize: 10)),
          ])),
          if (PermissionService.instance.can(Perm.otSurgeryRecommend))
            IconButton(
              icon: const Icon(Icons.medical_services_rounded, color: Colors.white),
              tooltip: 'Recommend Surgery',
              onPressed: () => showRecommendSurgerySheet(context, patient: widget.patient),
            ),
        ]),
      )),
    );
  }

  Widget _buildError() => Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
        const Icon(Icons.wifi_off_rounded, size: 48, color: Color(0xFFDC3545)),
        const SizedBox(height: 12),
        Text(_loadError ?? 'Failed to load exam.', textAlign: TextAlign.center),
        const SizedBox(height: 16),
        ElevatedButton.icon(onPressed: _loadAll, icon: Icon(Icons.refresh_rounded), label: Text('Retry'), style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary)),
      ]));

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
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        // Selected chips
        if (_historyChips.isNotEmpty) ...[
          Wrap(
            spacing: 6, runSpacing: 6,
            children: _historyChips.map((chip) => Chip(
              label: Text(chip, style: const TextStyle(fontSize: 12)),
              deleteIcon: const Icon(Icons.close, size: 14),
              onDeleted: () => setState(() => _historyChips.remove(chip)),
              backgroundColor: AppColors.primary.withValues(alpha: 0.08),
              deleteIconColor: Colors.red,
              materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
              padding: const EdgeInsets.symmetric(horizontal: 4),
            )).toList(),
          ),
          const SizedBox(height: 8),
        ],
        // Favourite quick-tap pills (amber style — tap label to add, tap ★ to un-favourite)
        if (favs.isNotEmpty) ...[
          Wrap(
            spacing: 6, runSpacing: 6,
            children: favs.map((item) {
              final selected = _historyChips.contains(item.value);
              return Container(
                decoration: BoxDecoration(
                  color: selected ? Colors.amber.shade50 : Colors.white,
                  borderRadius: BorderRadius.circular(AppRadius.xl),
                  border: Border.all(color: Colors.amber.shade300),
                ),
                child: Row(mainAxisSize: MainAxisSize.min, children: [
                  GestureDetector(
                    onTap: () {
                      if (!selected) setState(() => _historyChips.add(item.value));
                    },
                    child: Padding(
                      padding: const EdgeInsets.only(left: 10, top: 5, bottom: 5, right: 4),
                      child: Text(item.value, style: TextStyle(fontSize: 11,
                          color: selected ? Colors.amber.shade900 : Colors.amber.shade900,
                          fontWeight: FontWeight.w600)),
                    ),
                  ),
                  GestureDetector(
                    onTap: () => _toggleFavourite('hno', item),
                    child: Padding(
                      padding: const EdgeInsets.only(right: 8, top: 5, bottom: 5),
                      child: Icon(Icons.star_rounded, size: 13, color: Colors.amber.shade600),
                    ),
                  ),
                ]),
              );
            }).toList(),
          ),
          const SizedBox(height: 8),
        ],
        // Search field — chip mode: selecting adds chip and clears field
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
      ]),
    );
  }

  Widget _coRow(int idx, _CoRow row) {
    const eyeOptions   = ['-', 'RE', 'LE', 'Both', 'OU'];
    const sinceOptions = ['-', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];
    final safeEye   = eyeOptions.contains(row.eye)     ? row.eye   : '-';
    final safeSince = sinceOptions.contains(row.since) ? row.since : '-';
    return Container(
      key: ValueKey('co_$idx'),
      margin: const EdgeInsets.only(bottom: 8), padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.12))),
      child: Column(children: [
        Row(children: [
          Expanded(child: _MasterAutocompleteField(
            key: ValueKey('co_complaint_$idx'),
            items: _formData?.masters.chiefComplaints ?? [],
            initialValue: row.complaint,
            hint: 'Complaint',
            icon: Icons.report_problem_outlined,
            color: AppColors.primary,
            onChanged: (v) => setState(() => row.complaint = v),
            onToggleFavourite: (item) => _toggleFavourite('complaints', item),
          )),
          const SizedBox(width: 8),
          _miniDropdown(value: safeEye, items: eyeOptions, onChanged: (v) => setState(() => row.eye = v ?? '-'), width: 68),
          const SizedBox(width: 4),
          GestureDetector(onTap: () => setState(() => _coRows.removeAt(idx)), child: const Icon(Icons.remove_circle_rounded, color: Color(0xFFDC3545), size: 20)),
        ]),
        const SizedBox(height: 6),
        Row(children: [
          _miniDropdown(value: safeSince, items: sinceOptions, onChanged: (v) => setState(() => row.since = v ?? '-'), width: 64),
          const SizedBox(width: 6),
          _miniDropdown(value: row.unit, items: const ['Days', 'Weeks', 'Months', 'Years', 'Longtime'], onChanged: (v) => setState(() => row.unit = v!), width: 110),
          const SizedBox(width: 6),
          Expanded(child: _miniField(initialValue: row.comment, hint: 'Comment', onChanged: (v) => row.comment = v)),
        ]),
      ]),
    );
  }

  Widget _kcoRow(int idx, _KcoRow row) {
    const sinceOptions = ['-', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];
    final safeSince = sinceOptions.contains(row.since) ? row.since : '-';
    return Container(
      key: ValueKey('kco_$idx'),
      margin: const EdgeInsets.only(bottom: 8), padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.12))),
      child: Column(children: [
        Row(children: [
          Expanded(child: _MasterAutocompleteField(
            key: ValueKey('kco_condition_$idx'),
            items: _formData?.masters.kcos ?? [],
            initialValue: row.condition,
            hint: 'Known condition',
            icon: Icons.medical_information_outlined,
            color: AppColors.primary,
            onChanged: (v) => setState(() => row.condition = v),
            onToggleFavourite: (item) => _toggleFavourite('kcos', item),
          )),
          const SizedBox(width: 4),
          GestureDetector(onTap: () => setState(() => _kcoRows.removeAt(idx)), child: const Icon(Icons.remove_circle_rounded, color: Color(0xFFDC3545), size: 20)),
        ]),
        const SizedBox(height: 6),
        Row(children: [
          _miniDropdown(value: safeSince, items: sinceOptions, onChanged: (v) => setState(() => row.since = v ?? '-'), width: 64),
          const SizedBox(width: 6),
          _miniDropdown(value: row.unit, items: const ['Days', 'Weeks', 'Months', 'Years', 'Longtime'], onChanged: (v) => setState(() => row.unit = v!), width: 110),
          const SizedBox(width: 6),
          Expanded(child: _miniField(initialValue: row.comment, hint: 'Comment', onChanged: (v) => row.comment = v)),
        ]),
      ]),
    );
  }

  // ── Vision ────────────────────────────────────────────────────────────────────

  Widget _visionEyeRow(String label, bool isRe,
      TextEditingController a, TextEditingController b, TextEditingController c,
      String keyA, String keyB, String keyC) {
    return Column(children: [
      const Divider(height: 1),
      Padding(padding: const EdgeInsets.all(8), child: Row(children: [
        SizedBox(width: 32, child: Center(child: _eyeLabel(label, isRe))),
        const SizedBox(width: 8),
        _masterVisionCell(a, keyA), _masterVisionCell(b, keyB), _masterVisionCell(c, keyC),
      ])),
    ]);
  }

  Widget _masterVisionCell(TextEditingController ctrl, String masterKey) {
    final items = _formData?.masters.oeListFor(masterKey) ?? [];
    Future<void> openPicker() async {
      final picked = await showModalBottomSheet<String>(
        context: context,
        isScrollControlled: true,
        shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.xl))),
        builder: (_) => DraggableScrollableSheet(
          expand: false, initialChildSize: 0.5, maxChildSize: 0.85,
          builder: (_, sc) => Column(children: [
            Container(margin: const EdgeInsets.only(top: 10, bottom: 6),
                width: 36, height: 4, decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(2))),
            Padding(padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                child: Text('Select value', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: AppColors.primary))),
            Expanded(child: ListView(controller: sc, padding: const EdgeInsets.fromLTRB(12, 0, 12, 24),
              children: [
                ...items.where((i) => i.isFavourite).map((i) => ListTile(
                  dense: true, contentPadding: const EdgeInsets.symmetric(horizontal: 8),
                  leading: const Text('★', style: TextStyle(color: Colors.amber, fontSize: 14)),
                  title: Text(i.value, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                  onTap: () => Navigator.pop(context, i.value),
                )),
                ...items.where((i) => !i.isFavourite).map((i) => ListTile(
                  dense: true, contentPadding: const EdgeInsets.symmetric(horizontal: 8),
                  title: Text(i.value, style: const TextStyle(fontSize: 13)),
                  onTap: () => Navigator.pop(context, i.value),
                )),
              ],
            )),
          ]),
        ),
      );
      if (picked != null && mounted) setState(() => ctrl.text = picked);
    }

    return Expanded(child: Padding(
      padding: const EdgeInsets.symmetric(horizontal: 4),
      child: TextFormField(
        controller: ctrl, textAlign: TextAlign.center,
        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w500),
        onTap: items.isEmpty ? null : openPicker,
        readOnly: false,
        decoration: InputDecoration(
          hintText: '—', hintStyle: TextStyle(color: AppColors.primary.withValues(alpha: 0.3), fontSize: 13),
          suffixIcon: items.isNotEmpty
            ? IconButton(
                icon: Icon(Icons.arrow_drop_down_rounded, size: 18, color: AppColors.primary.withValues(alpha: 0.5)),
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
                onPressed: openPicker,
              )
            : null,
          contentPadding: const EdgeInsets.symmetric(vertical: 8, horizontal: 6),
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.15))),
          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.15))),
          focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary)),
        ),
      ),
    ));
  }

  Widget _nctField(TextEditingController ctrl) {
    final items = _formData?.masters.nct ?? [];
    final val  = ctrl.text.trim();
    final iop  = int.tryParse(val);
    final Color bdColor;
    final Color bgColor;
    if (iop != null && val.isNotEmpty) {
      if (iop >= 25)      { bdColor = const Color(0xFFEF4444); bgColor = const Color(0xFFFFF5F5); }
      else if (iop >= 22) { bdColor = const Color(0xFFF59E0B); bgColor = const Color(0xFFFFFBF0); }
      else if (iop >= 10) { bdColor = const Color(0xFF22C55E); bgColor = const Color(0xFFF0FFF4); }
      else                { bdColor = AppColors.primary.withValues(alpha: 0.18); bgColor = Colors.white; }
    } else                { bdColor = AppColors.primary.withValues(alpha: 0.18); bgColor = Colors.white; }

    Future<void> openGrid() async {
      if (items.isEmpty) return;
      String query = '';
      final picked = await showModalBottomSheet<String>(
        context: context,
        isScrollControlled: true,
        shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.xl))),
        builder: (ctx) => StatefulBuilder(builder: (ctx, ss) {
          final filtered = query.isEmpty
              ? items
              : items.where((i) => i.value.toLowerCase().contains(query.toLowerCase())).toList();
          return DraggableScrollableSheet(
            initialChildSize: 0.6, maxChildSize: 0.9, minChildSize: 0.35, expand: false,
            builder: (_, sc) => Column(children: [
              Container(margin: const EdgeInsets.only(top: 10, bottom: 6), width: 36, height: 4,
                  decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(2))),
              Padding(padding: const EdgeInsets.fromLTRB(16, 0, 16, 8), child: Row(children: [
                Expanded(child: Text('IOP — NCT (mmHg)', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: AppColors.primary))),
                if (ctrl.text.isNotEmpty) TextButton(
                  onPressed: () => Navigator.pop(ctx, ''),
                  style: TextButton.styleFrom(foregroundColor: Colors.red),
                  child: const Text('Clear'),
                ),
              ])),
              Padding(padding: const EdgeInsets.fromLTRB(12, 0, 12, 8), child: TextField(
                onChanged: (v) => ss(() => query = v),
                decoration: InputDecoration(
                  hintText: 'Type to filter…',
                  prefixIcon: const Icon(Icons.search_rounded, size: 18),
                  isDense: true,
                  contentPadding: const EdgeInsets.symmetric(vertical: 8, horizontal: 12),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm),
                      borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.2))),
                ),
              )),
              Expanded(child: GridView.builder(
                controller: sc,
                padding: const EdgeInsets.fromLTRB(12, 0, 12, 24),
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 5, mainAxisSpacing: 4, crossAxisSpacing: 4, childAspectRatio: 1.3),
                itemCount: filtered.length,
                itemBuilder: (_, i) {
                  final v = filtered[i].value;
                  final isSel = ctrl.text == v;
                  return GestureDetector(
                    onTap: () => Navigator.pop(ctx, v),
                    child: Container(
                      decoration: BoxDecoration(
                        color: isSel ? AppColors.primary : Colors.grey.shade100,
                        borderRadius: BorderRadius.circular(6),
                        border: Border.all(color: isSel ? AppColors.primary : Colors.grey.shade300),
                      ),
                      child: Center(child: Text(v,
                        textAlign: TextAlign.center,
                        style: TextStyle(fontSize: 12, fontWeight: isSel ? FontWeight.w700 : FontWeight.w500,
                            color: isSel ? Colors.white : const Color(0xFF1e293b)))),
                    ),
                  );
                },
              )),
            ]),
          );
        }),
      );
      if (picked != null && mounted) setState(() => ctrl.text = picked);
    }

    return TextFormField(
      controller: ctrl,
      keyboardType: TextInputType.number,
      textAlign: TextAlign.center,
      style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
      onChanged: (_) => setState(() {}),
      decoration: InputDecoration(
        hintText: '—',
        hintStyle: TextStyle(fontSize: 22, color: AppColors.primary.withValues(alpha: 0.2), fontWeight: FontWeight.w200),
        suffixIcon: IconButton(
          icon: Icon(Icons.arrow_drop_down_rounded, color: bdColor, size: 22),
          padding: EdgeInsets.zero,
          constraints: const BoxConstraints(),
          onPressed: openGrid,
        ),
        filled: true, fillColor: bgColor,
        contentPadding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
        border:        OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide(color: bdColor, width: 1.5)),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide(color: bdColor, width: 1.5)),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide(color: bdColor, width: 2.0)),
      ),
    );
  }

  // ── Refraction ────────────────────────────────────────────────────────────────

  /// Maps a refraction column key to the right master list and picker behaviour.
  /// ds/dc/ns/nc → sph_cyl with sign toggle
  /// ax/na → axis list
  /// vn → vn list   near_vn → nrvn list
  bool _axisDisabled(String cylText) {
    final t = cylText.trim();
    if (t.isEmpty) return true;
    if (t == 'Plano') return true;
    final abs = (t.startsWith('+') || t.startsWith('-')) ? t.substring(1) : t;
    return abs == '0.00' || abs == '0';
  }

  // SPH/CYL: [−] [readonly field] [+] — sign buttons open the grid picker pre-set to that sign
  // AXIS/VN : editable field + chevron icon — user can type freely OR pick from list
  Widget _refractionCell(TextEditingController ctrl, String key, {bool disabled = false, VoidCallback? onChanged}) {
    final isSphCyl = key == 'ds' || key == 'dc' || key == 'ns' || key == 'nc';
    final isAxis   = key == 'ax' || key == 'na';
    final isVn     = key == 'vn';
    final isNrVn   = key == 'near_vn';

    List<ExamMasterItem> items;
    if (isSphCyl)      items = _formData?.masters.sphCyl ?? [];
    else if (isAxis)   items = _formData?.masters.axis ?? [];
    else if (isVn)     items = _formData?.masters.vn ?? [];
    else if (isNrVn)   items = _formData?.masters.nrvn ?? [];
    else               items = [];

    // Shared list picker for AXIS and VN fields
    Future<void> openListPicker() async {
      final picked = await showModalBottomSheet<String>(
        context: context,
        isScrollControlled: true,
        shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.xl))),
        builder: (sheetCtx) => DraggableScrollableSheet(
          expand: false, initialChildSize: 0.45, maxChildSize: 0.8,
          builder: (_, sc) => Column(children: [
            Container(margin: const EdgeInsets.only(top: 10, bottom: 6),
                width: 36, height: 4, decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(2))),
            Padding(padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
              child: Row(children: [
                Expanded(child: Text('Select value', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: AppColors.primary))),
                if (ctrl.text.isNotEmpty)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.circular(6)),
                    child: Text(ctrl.text, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Colors.white)),
                  ),
              ]),
            ),
            Expanded(child: ListView(controller: sc, padding: const EdgeInsets.fromLTRB(12, 0, 12, 24),
              children: items.map((i) {
                final isSel = ctrl.text == i.value;
                return ListTile(
                  dense: true, contentPadding: const EdgeInsets.symmetric(horizontal: 8),
                  tileColor: isSel ? AppColors.primary.withValues(alpha: 0.08) : null,
                  title: Text(i.value, style: TextStyle(fontSize: 13,
                      fontWeight: isSel ? FontWeight.w700 : FontWeight.normal,
                      color: isSel ? AppColors.primary : null)),
                  trailing: isSel ? Icon(Icons.check_rounded, size: 18, color: AppColors.primary) : null,
                  onTap: () => Navigator.pop(sheetCtx, i.value),
                );
              }).toList(),
            )),
          ]),
        ),
      );
      if (picked != null && mounted) setState(() => ctrl.text = picked);
    }

    // ── SPH / CYL: colored tappable chip — opens full-screen picker ────────
    if (isSphCyl) {
      final t = ctrl.text.trim();
      final isEmpty = t.isEmpty;
      final isMinus = t.startsWith('-');
      final isZero  = t == '0.00';
      final bgColor  = isEmpty ? Colors.grey.shade50   : isZero ? Colors.grey.shade200  : isMinus ? const Color(0xFFFFF0F0) : const Color(0xFFF0FFF4);
      final bdColor  = isEmpty ? Colors.grey.shade300  : isZero ? Colors.grey.shade400  : isMinus ? const Color(0xFFFFB3B3) : const Color(0xFF86EFAC);
      final txtColor = isEmpty ? Colors.grey.shade400  : isZero ? const Color(0xFF475569) : isMinus ? const Color(0xFFB91C1C) : const Color(0xFF15803D);
      return Expanded(child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 2),
        child: GestureDetector(
          onTap: () async {
            final sign = t.startsWith('-') ? '-' : '+';
            final picked = await _showSphCylPicker(items, ctrl.text, forcedSign: isEmpty ? '+' : sign);
            if (picked != null && mounted) {
              setState(() => ctrl.text = picked);
              onChanged?.call();
            }
          },
          child: Container(
            height: 40,
            decoration: BoxDecoration(color: bgColor, borderRadius: BorderRadius.circular(AppRadius.sm), border: Border.all(color: bdColor)),
            child: Center(child: Text(
              isEmpty ? '—' : t,
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: txtColor),
            )),
          ),
        ),
      ));
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
      chipBg = isEmpty ? Colors.grey.shade50 : AppColors.primary.withValues(alpha: 0.08);
      chipBd = isEmpty ? Colors.grey.shade300 : AppColors.primary.withValues(alpha: 0.4);
      chipTxt = isEmpty ? Colors.grey.shade400 : AppColors.primary;
    }
    return Expanded(child: Padding(
      padding: const EdgeInsets.symmetric(horizontal: 2),
      child: GestureDetector(
        onTap: (!axisOff && items.isNotEmpty) ? openListPicker : null,
        child: Container(
          height: 40,
          decoration: BoxDecoration(
            color: chipBg,
            borderRadius: BorderRadius.circular(AppRadius.sm),
            border: Border.all(color: chipBd),
          ),
          child: Center(child: Text(
            isEmpty ? '—' : t,
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: chipTxt),
          )),
        ),
      ),
    ));
  }

  Future<String?> _showSphCylPicker(List<ExamMasterItem> items, String current, {String? forcedSign}) async {
    // Web: picker opens pre-set to the sign of the button pressed; no Plano tab
    String sign = forcedSign ?? (current.startsWith('-') ? '-' : '+');
    String selected = current;
    final customCtrl = TextEditingController();

    // positive master values only in grid; 0.00 pinned last (no sign prefix, matching web)
    final gridItems = items.where((i) {
      final n = double.tryParse(i.value.replaceAll(RegExp(r'[+\-]'), ''));
      return n != null && n > 0;
    }).toList();

    Widget chip(String label, bool isSel, bool isFav, VoidCallback onTap) =>
      GestureDetector(
        onTap: onTap,
        child: Container(
          width: 58, height: 36,
          decoration: BoxDecoration(
            color: isSel ? AppColors.primary : (isFav ? Colors.amber.shade50 : Colors.grey.shade100),
            borderRadius: BorderRadius.circular(AppRadius.sm),
            border: Border.all(color: isSel ? AppColors.primary : (isFav ? Colors.amber.shade300 : Colors.grey.shade300)),
          ),
          child: Center(child: Text(label, textAlign: TextAlign.center,
            style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600,
                color: isSel ? Colors.white : (isFav ? Colors.amber.shade800 : AppColors.primary.withValues(alpha: 0.85))))),
        ),
      );

    Widget zeroChip(bool isSel, VoidCallback onTap) => GestureDetector(
      onTap: onTap,
      child: Container(
        width: 58, height: 36,
        decoration: BoxDecoration(
          color: isSel ? const Color(0xFF475569) : Colors.grey.shade200,
          borderRadius: BorderRadius.circular(AppRadius.sm),
          border: Border.all(color: isSel ? const Color(0xFF475569) : Colors.grey.shade400),
        ),
        child: Center(child: Text('0.00', textAlign: TextAlign.center,
          style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600,
              color: isSel ? Colors.white : const Color(0xFF475569)))),
      ),
    );

    try {
      return await showDialog<String>(
        context: context,
        useSafeArea: false,
        barrierDismissible: true,
        builder: (_) => Dialog.fullscreen(
          child: StatefulBuilder(
            builder: (ctx, ss) {
              final title = sign == '-' ? '− Negative Values' : '+ Positive Values';
              return Scaffold(
                backgroundColor: Colors.white,
                appBar: AppBar(
                  backgroundColor: AppColors.primary, foregroundColor: Colors.white, elevation: 0,
                  leading: IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(ctx)),
                  title: Text(title, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800)),
                ),
                body: Column(children: [
                  // ── Sign tabs (− / +) only — matching web ─────────────────
                  Padding(
                    padding: const EdgeInsets.fromLTRB(12, 12, 12, 0),
                    child: Row(children: [
                      for (final s in ['-', '+'])
                        Expanded(child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 4),
                          child: GestureDetector(
                            onTap: () => ss(() {
                              final prev = sign;
                              sign = s;
                              // Convert existing selection to new sign
                              if (selected.isNotEmpty && selected != '0.00') {
                                final abs = selected.startsWith(prev) ? selected.substring(prev.length) : selected;
                                selected = '$s$abs';
                              }
                            }),
                            child: Container(
                              padding: const EdgeInsets.symmetric(vertical: 10),
                              decoration: BoxDecoration(
                                color: sign == s ? AppColors.primary : Colors.white,
                                borderRadius: BorderRadius.circular(AppRadius.sm),
                                border: Border.all(color: sign == s ? AppColors.primary : AppColors.primary.withValues(alpha: 0.2)),
                              ),
                              child: Text(
                                s == '-' ? 'Minus (−)' : 'Plus (+)',
                                textAlign: TextAlign.center,
                                style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700,
                                    color: sign == s ? Colors.white : AppColors.primary),
                              ),
                            ),
                          ),
                        )),
                    ]),
                  ),
                  const SizedBox(height: 8),
                  // ── Value grid ────────────────────────────────────────────
                  Expanded(
                    child: SingleChildScrollView(
                      padding: const EdgeInsets.fromLTRB(12, 4, 12, 16),
                      child: Wrap(
                        spacing: 6, runSpacing: 6,
                        children: [
                          ...gridItems.map((i) {
                            final v = '$sign${i.value}';
                            return chip(v, selected == v, i.isFavourite, () => ss(() => selected = v));
                          }),
                          // 0.00 always last — shown without sign, matching web
                          zeroChip(selected == '0.00', () => ss(() => selected = '0.00')),
                        ],
                      ),
                    ),
                  ),
                  // ── Bottom bar ────────────────────────────────────────────
                  Container(
                    padding: EdgeInsets.fromLTRB(12, 10, 12, MediaQuery.of(ctx).padding.bottom + 12),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      border: Border(top: BorderSide(color: AppColors.primary.withValues(alpha: 0.12))),
                      boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.06), blurRadius: 8, offset: Offset(0, -2))],
                    ),
                    child: Column(mainAxisSize: MainAxisSize.min, children: [
                      Row(children: [
                        Expanded(child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
                          decoration: BoxDecoration(
                            color: selected.isNotEmpty ? AppColors.primary.withValues(alpha: 0.07) : Colors.grey.shade100,
                            borderRadius: BorderRadius.circular(AppRadius.sm),
                          ),
                          child: Text(
                            selected.isNotEmpty ? 'SELECTED: $selected' : 'Nothing selected',
                            style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700,
                                color: selected.isNotEmpty ? AppColors.primary : Colors.grey.shade400),
                          ),
                        )),
                        const SizedBox(width: 8),
                        GestureDetector(
                          onTap: () => ss(() { selected = ''; customCtrl.clear(); }),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                            decoration: BoxDecoration(
                              border: Border.all(color: Colors.red.shade300),
                              borderRadius: BorderRadius.circular(AppRadius.sm),
                            ),
                            child: Text('Clear', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Colors.red.shade600)),
                          ),
                        ),
                      ]),
                      const SizedBox(height: 8),
                      Row(children: [
                        Expanded(child: TextFormField(
                          controller: customCtrl,
                          keyboardType: const TextInputType.numberWithOptions(decimal: true, signed: false),
                          onChanged: (_) => ss(() {}),
                          decoration: InputDecoration(
                            hintText: 'Custom (e.g. ${sign}1.75)',
                            hintStyle: TextStyle(fontSize: 12, color: AppColors.primary.withValues(alpha: 0.4)),
                            contentPadding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm),
                                borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.3))),
                            enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm),
                                borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.3))),
                            focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm),
                                borderSide: BorderSide(color: AppColors.primary)),
                          ),
                          style: const TextStyle(fontSize: 13),
                        )),
                        const SizedBox(width: 8),
                        GestureDetector(
                          onTap: () {
                            final custom = customCtrl.text.trim();
                            String? result;
                            if (custom.isNotEmpty) {
                              final stripped = (custom.startsWith('+') || custom.startsWith('-'))
                                  ? custom.substring(1) : custom;
                              result = '$sign$stripped';
                            } else if (selected.isNotEmpty) {
                              result = selected;
                            }
                            Navigator.pop(ctx, result);
                          },
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 13),
                            decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.circular(AppRadius.sm)),
                            child: const Text('Apply', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: Colors.white)),
                          ),
                        ),
                      ]),
                    ]),
                  ),
                ]),
              );
            },
          ),
        ),
      );
    } finally {
      customCtrl.dispose();
    }
  }

  Widget _pgTable() {
    return Container(
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.12))),
      child: Column(children: [
        Container(
          decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.06), borderRadius: BorderRadius.vertical(top: Radius.circular(11))),
          child: Row(children: [const SizedBox(width: 68), _thCell('SPH'), _thCell('CYL'), _thCell('AXIS'), _thCell('VN C GL')]),
        ),
        for (final eye in ['re', 'le']) ...[
          const Divider(height: 1),
          // DIST row
          Padding(padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 8), child: Row(children: [
            SizedBox(width: 68, child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              _eyeLabel(eye.toUpperCase(), eye == 're'),
              Text('DIST', style: TextStyle(fontSize: 9, color: AppColors.primary.withValues(alpha: 0.5))),
            ])),
            ..._pgDistKeys.map((k) => _refractionCell(_pg[eye]![k]!, k,
                disabled: k == 'ax' && _axisDisabled(_pg[eye]!['dc']!.text))),
          ])),
          // NEAR row
          Padding(padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 8), child: Row(children: [
            SizedBox(width: 68, child: Padding(padding: const EdgeInsets.only(left: 2),
                child: Text('NEAR', style: TextStyle(fontSize: 9, color: AppColors.primary.withValues(alpha: 0.5))))),
            ..._pgNearKeys.map((k) => _refractionCell(_pg[eye]![k]!, k,
                disabled: k == 'na' && _axisDisabled(_pg[eye]!['nc']!.text))),
          ])),
        ],
      ]),
    );
  }

  // ── ST unified table (DISTANCE + NEAR per eye) ───────────────────────────────

  Widget _stTable() {
    return Column(children: [
      for (final eye in ['re', 'le']) ...[
        if (eye == 'le') const SizedBox(height: 8),
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(AppRadius.md),
            border: Border.all(color: AppColors.primary.withValues(alpha: 0.12)),
          ),
          child: Column(children: [
            // Eye header
            Container(
              decoration: BoxDecoration(
                color: AppColors.primary,
                borderRadius: BorderRadius.vertical(top: Radius.circular(11)),
              ),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
              child: Row(children: [
                const Icon(Icons.remove_red_eye_rounded, color: Colors.white, size: 13),
                const SizedBox(width: 6),
                Text(eye == 're' ? 'Right Eye (RE)' : 'Left Eye (LE)',
                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Colors.white)),
              ]),
            ),
            // Column headers
            Container(
              color: AppColors.primary.withValues(alpha: 0.04),
              child: Row(children: [
                const SizedBox(width: 46),
                _thCell('SPH'),
                _thCell('CYL'),
                _thCell('AXIS'),
                _thCell('VN C ST'),
              ]),
            ),
            // DISTANCE row
            const Divider(height: 1),
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 6),
              child: Row(children: [
                SizedBox(width: 46, child: Center(child: Text('DIST',
                    style: TextStyle(fontSize: 9, fontWeight: FontWeight.w700,
                        color: AppColors.primary.withValues(alpha: 0.5), letterSpacing: .04)))),
                _refractionCell(_st[eye]!['ds']!, 'ds', onChanged: () => _recalcNs(eye)),
                _refractionCell(_st[eye]!['dc']!, 'dc'),
                _refractionCell(_st[eye]!['ax']!, 'ax',
                    disabled: _axisDisabled(_st[eye]!['dc']!.text)),
                _refractionCell(_st[eye]!['vn']!, 'vn'),
              ]),
            ),
            // NEAR row
            const Divider(height: 1),
            Padding(
              padding: const EdgeInsets.fromLTRB(6, 6, 6, 8),
              child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                SizedBox(width: 46, child: Padding(
                  padding: const EdgeInsets.only(top: 12),
                  child: Center(child: Text('NEAR',
                      style: TextStyle(fontSize: 9, fontWeight: FontWeight.w700,
                          color: AppColors.primary.withValues(alpha: 0.5), letterSpacing: .04))),
                )),
                _stNsCell(eye),
                _stMirrorCell(_st[eye]!['dc']!),
                _stMirrorCell(_st[eye]!['ax']!),
                Expanded(child: Padding(
                  padding: const EdgeInsets.only(top: 10),
                  child: Center(child: Text('—',
                      style: TextStyle(fontSize: 20, fontWeight: FontWeight.w200,
                          color: Colors.grey.shade400))),
                )),
              ]),
            ),
          ]),
        ),
      ],
    ]);
  }

  // NS = DS + ADD (web formula)
  void _recalcNs(String eye) {
    final ds  = double.tryParse(_st[eye]!['ds']!.text.replaceAll('+', '')) ?? 0.0;
    final add = double.tryParse((eye == 're' ? _stAddRe : _stAddLe).text.replaceAll('+', '')) ?? 0.0;
    final ns  = ds + add;
    (eye == 're' ? _stNsRe : _stNsLe).text =
        ns == 0.0 ? '' : (ns > 0 ? '+${ns.toStringAsFixed(2)}' : ns.toStringAsFixed(2));
  }

  // NS chip (shows calculated NS = DS+ADD); tap picks ADD; "ADD: X" label below
  Widget _stNsCell(String eye) {
    final nsCtrl  = eye == 're' ? _stNsRe : _stNsLe;
    final addCtrl = eye == 're' ? _stAddRe : _stAddLe;
    final items   = _formData?.masters.sphCyl ?? [];
    final ns  = nsCtrl.text.trim();
    final add = addCtrl.text.trim();
    final isEmpty = ns.isEmpty;
    final isMinus = ns.startsWith('-');
    final isZero  = ns == '0.00';
    final bgColor  = isEmpty ? Colors.grey.shade50  : isZero ? Colors.grey.shade200 : isMinus ? const Color(0xFFFFF0F0) : const Color(0xFFF0FFF4);
    final bdColor  = isEmpty ? Colors.grey.shade300 : isZero ? Colors.grey.shade400 : isMinus ? const Color(0xFFFFB3B3) : const Color(0xFF86EFAC);
    final txtColor = isEmpty ? Colors.grey.shade400 : isZero ? const Color(0xFF475569) : isMinus ? const Color(0xFFB91C1C) : const Color(0xFF15803D);
    return Expanded(child: Padding(
      padding: const EdgeInsets.symmetric(horizontal: 2),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        GestureDetector(
          onTap: () async {
            final curAdd = addCtrl.text.trim();
            final addIsMinus = curAdd.startsWith('-');
            final picked = await _showSphCylPicker(
              items, curAdd, forcedSign: curAdd.isEmpty ? '+' : (addIsMinus ? '-' : '+'));
            if (picked != null && mounted) {
              setState(() {
                addCtrl.text = picked;
                _recalcNs(eye);
              });
            }
          },
          child: Container(
            height: 40,
            decoration: BoxDecoration(
                color: bgColor, borderRadius: BorderRadius.circular(AppRadius.sm),
                border: Border.all(color: bdColor)),
            child: Center(child: Text(isEmpty ? '—' : ns,
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: txtColor))),
          ),
        ),
        const SizedBox(height: 3),
        Text(
          'ADD: ${add.isEmpty ? '—' : add}',
          textAlign: TextAlign.center,
          style: TextStyle(fontSize: 9, color: Colors.grey.shade500),
        ),
      ]),
    ));
  }

  // Readonly mirror of a distance field (NC = DC, NA = AX)
  Widget _stMirrorCell(TextEditingController mirrorOf) {
    final val = mirrorOf.text.trim();
    return Expanded(child: Padding(
      padding: const EdgeInsets.symmetric(horizontal: 2),
      child: Container(
        height: 40,
        decoration: BoxDecoration(
          color: Colors.grey.shade100,
          borderRadius: BorderRadius.circular(AppRadius.sm),
          border: Border.all(color: Colors.grey.shade300),
        ),
        child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
          Text(
            val.isEmpty ? '—' : val,
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600,
                color: val.isEmpty ? Colors.grey.shade400 : AppColors.primary.withValues(alpha: 0.4)),
          ),
          Text('= Dist', style: TextStyle(fontSize: 8, color: Colors.grey.shade400)),
        ]),
      ),
    ));
  }

  Widget _stCheckboxRow() {
    return Wrap(spacing: 4, runSpacing: 4, children: [
      _stCheck('Bifocal', _bifocal, (v) => setState(() => _bifocal = v!)),
      _stCheck('N&D Separate', _ndSeparate, (v) => setState(() => _ndSeparate = v!)),
      _stCheck('Progressive', _progressive, (v) => setState(() => _progressive = v!)),
      _stCheck('Computer Use', _computerUses, (v) => setState(() => _computerUses = v!)),
    ]);
  }

  Widget _stCheck(String label, bool val, void Function(bool?) onChanged) {
    return Row(mainAxisSize: MainAxisSize.min, children: [
      Checkbox(value: val, onChanged: onChanged, activeColor: AppColors.primary, materialTapTargetSize: MaterialTapTargetSize.shrinkWrap),
      Text(label, style: TextStyle(fontSize: 12, color: AppColors.primary)),
    ]);
  }

  // ── OE & Fundus ───────────────────────────────────────────────────────────────

  Widget _oeTable() {
    bool isPseudo(String eye) =>
        (eye == 're' ? _oeRe['lens'] : _oeLe['lens'])!.text.toLowerCase().contains('pseudophakia');

    return Container(
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md),
          border: Border.all(color: AppColors.primary.withValues(alpha: 0.12)),
          boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.05), blurRadius: 6, offset: Offset(0, 2))]),
      child: Column(children: [
        // Navy header
        Container(
          decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.vertical(top: Radius.circular(11))),
          child: Row(children: [
            const SizedBox(width: 110),
            Expanded(child: Padding(padding: const EdgeInsets.symmetric(vertical: 8),
                child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                  const Icon(Icons.remove_red_eye_rounded, color: Colors.white, size: 12),
                  const SizedBox(width: 4),
                  const Text('Right Eye (RE)', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Colors.white)),
                ]))),
            Container(width: 1, height: 36, color: Colors.white.withValues(alpha: 0.2)),
            Expanded(child: Padding(padding: const EdgeInsets.symmetric(vertical: 8),
                child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                  const Icon(Icons.remove_red_eye_rounded, color: Colors.white, size: 12),
                  const SizedBox(width: 4),
                  const Text('Left Eye (LE)', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Colors.white)),
                ]))),
          ]),
        ),

        // Data rows
        ..._oeFieldNames.map((f) {
          final isOther = f == 'other';
          return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const Divider(height: 1),
            Padding(padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 8), child: Row(children: [
              SizedBox(width: 100, child: Text(_oeLabels[f] ?? f,
                  style: TextStyle(fontSize: 12, color: AppColors.primary.withValues(alpha: 0.7), fontWeight: FontWeight.w600))),
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
            ])),
            // Pseudophakia inline expansion after LENS row
            if (f == 'lens' && (isPseudo('re') || isPseudo('le')))
              _pseudophakiaPanel(isPseudo('re'), isPseudo('le')),
          ]);
        }),
      ]),
    );
  }

  Widget _oePlainField(TextEditingController ctrl) => TextFormField(
    controller: ctrl,
    style: const TextStyle(fontSize: 12),
    decoration: InputDecoration(
      hintText: '—', hintStyle: TextStyle(color: AppColors.primary.withValues(alpha: 0.3), fontSize: 12),
      contentPadding: const EdgeInsets.symmetric(vertical: 6, horizontal: 8), isDense: true,
      border:        OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.15))),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.15))),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary)),
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
        hintText: '—', hintStyle: TextStyle(color: AppColors.primary.withValues(alpha: 0.3), fontSize: 12),
        contentPadding: const EdgeInsets.symmetric(vertical: 6, horizontal: 8), isDense: true,
        suffixIcon: hasItems ? GestureDetector(
          onTap: () => _openOePickerSheet(fieldKey, ctrl),
          child: Icon(Icons.arrow_drop_down_rounded, size: 18, color: AppColors.primary.withValues(alpha: 0.5)),
        ) : null,
        border:        OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.15))),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.15))),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary)),
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
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.xl))),
      builder: (bsCtx) => StatefulBuilder(builder: (_, bsSs) {
        final allItems = _oeMasters[fieldKey] ?? [];
        final favs   = allItems.where((i) => i.isFavourite  && (query.isEmpty || i.value.toLowerCase().contains(query.toLowerCase()))).toList();
        final others = allItems.where((i) => !i.isFavourite && (query.isEmpty || i.value.toLowerCase().contains(query.toLowerCase()))).toList();

        Future<void> doToggle(ExamMasterItem item) async {
          try {
            final newFav = await SimpleMasterService.instance.toggleFavourite('masters/detail/$apiType', item.id);
            bsSs(() {
              final list = _oeMasters[fieldKey]!;
              final idx  = list.indexWhere((e) => e.id == item.id);
              if (idx >= 0) list[idx] = ExamMasterItem(id: item.id, value: item.value, isFavourite: newFav);
            });
            if (mounted) setState(() {}); // rebuild table (fav star in parent)
          } catch (_) {}
        }

        Widget itemTile(ExamMasterItem item) => ListTile(
          dense: true,
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 0),
          title: Text(item.value, style: const TextStyle(fontSize: 13)),
          trailing: IconButton(
            icon: Icon(item.isFavourite ? Icons.star_rounded : Icons.star_outline_rounded,
                size: 18, color: item.isFavourite ? Colors.amber : AppColors.primary.withValues(alpha: 0.3)),
            padding: EdgeInsets.zero, constraints: const BoxConstraints(),
            onPressed: () => doToggle(item),
          ),
          onTap: () { if (mounted) setState(() => ctrl.text = item.value); Navigator.pop(bsCtx); },
        );

        return DraggableScrollableSheet(
          expand: false, initialChildSize: 0.6, maxChildSize: 0.92, minChildSize: 0.3,
          builder: (_, sc) => Column(children: [
            Container(margin: const EdgeInsets.only(top: 10, bottom: 6), width: 36, height: 4,
                decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(2))),
            Padding(padding: const EdgeInsets.fromLTRB(16, 0, 16, 8), child: Row(children: [
              Expanded(child: Text(_oeLabels[fieldKey] ?? fieldKey,
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: AppColors.primary))),
              if (ctrl.text.isNotEmpty) TextButton(
                onPressed: () { if (mounted) setState(() => ctrl.text = ''); Navigator.pop(bsCtx); },
                style: TextButton.styleFrom(foregroundColor: Colors.red),
                child: const Text('Clear'),
              ),
            ])),
            Padding(padding: const EdgeInsets.fromLTRB(12, 0, 12, 8), child: TextField(
              onChanged: (v) => bsSs(() => query = v),
              decoration: InputDecoration(
                hintText: 'Search…', prefixIcon: const Icon(Icons.search_rounded, size: 18),
                isDense: true, contentPadding: const EdgeInsets.symmetric(vertical: 8, horizontal: 12),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm),
                    borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.2))),
              ),
            )),
            Expanded(child: ListView(controller: sc, children: [
              if (favs.isNotEmpty) ...[
                Padding(padding: const EdgeInsets.fromLTRB(16, 6, 16, 2),
                    child: Text('★  Favourites', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Colors.amber.shade700))),
                ...favs.map(itemTile),
                const Divider(height: 1),
              ],
              if (others.isNotEmpty) ...[
                Padding(padding: const EdgeInsets.fromLTRB(16, 6, 16, 2),
                    child: Text('All', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.primary.withValues(alpha: 0.5)))),
                ...others.map(itemTile),
              ],
              if (favs.isEmpty && others.isEmpty)
                Padding(padding: const EdgeInsets.all(24), child: Text('No results',
                    textAlign: TextAlign.center, style: TextStyle(color: AppColors.primary.withValues(alpha: 0.35)))),
              const SizedBox(height: 24),
            ])),
          ]),
        );
      }),
    );
  }

  Widget _pseudophakiaPanel(bool showRe, bool showLe) {
    const opts = ['Block', 'Phaco'];

    Widget opChips(String current, void Function(String) onSel) => Wrap(spacing: 6, children: opts.map((o) =>
      GestureDetector(
        onTap: () => setState(() => onSel(o)),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
          decoration: BoxDecoration(
            color: current == o ? AppColors.primary : Colors.white,
            borderRadius: BorderRadius.circular(AppRadius.xl),
            border: Border.all(color: current == o ? AppColors.primary : AppColors.primary.withValues(alpha: 0.2)),
          ),
          child: Text(o, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600,
              color: current == o ? Colors.white : AppColors.primary)),
        ),
      )).toList());

    Widget panel(String eye, String opType, void Function(String) onOpType,
        TextEditingController expenseCtrl, TextEditingController hospitalCtrl) =>
      Container(
        margin: const EdgeInsets.fromLTRB(8, 0, 8, 6),
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(
          color: const Color(0xFFF0F4FF),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: AppColors.primary.withValues(alpha: 0.15)),
        ),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Pseudophakia — $eye', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.primary)),
          const SizedBox(height: 8),
          Text('Operation Type', style: TextStyle(fontSize: 11, color: AppColors.primary.withValues(alpha: 0.6))),
          const SizedBox(height: 4),
          opChips(opType, onOpType),
          const SizedBox(height: 8),
          Row(children: [
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Expense', style: TextStyle(fontSize: 11, color: AppColors.primary.withValues(alpha: 0.6))),
              const SizedBox(height: 4),
              _oePlainField(expenseCtrl),
            ])),
            const SizedBox(width: 8),
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Hospital', style: TextStyle(fontSize: 11, color: AppColors.primary.withValues(alpha: 0.6))),
              const SizedBox(height: 4),
              _MasterAutocompleteField(
                items: _referrers,
                initialValue: hospitalCtrl.text,
                hint: 'Hospital',
                icon: Icons.local_hospital_outlined,
                color: AppColors.primary,
                onChanged: (v) => hospitalCtrl.text = v,
              ),
            ])),
          ]),
        ]),
      );

    return Column(children: [
      if (showRe) panel('RE', _pseudophakiaOpTypeRe, (v) { _pseudophakiaOpTypeRe = v; },
          _pseudophakiaExpenseRe, _pseudophakiaHospitalRe),
      if (showLe) panel('LE', _pseudophakiaOpTypeLe, (v) { _pseudophakiaOpTypeLe = v; },
          _pseudophakiaExpenseLe, _pseudophakiaHospitalLe),
    ]);
  }

  Widget _fundusTable() {
    Widget eyeCard(bool isRe, TextEditingController discCtrl, TextEditingController frCtrl, TextEditingController commentCtrl) {
      final eyeLabel = isRe ? 'Right Eye (RE)' : 'Left Eye (LE)';
      return Container(
        margin: isRe ? EdgeInsets.zero : const EdgeInsets.only(top: 12),
        decoration: BoxDecoration(
          color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md),
          border: Border.all(color: AppColors.primary.withValues(alpha: 0.12)),
          boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.05), blurRadius: 6, offset: Offset(0, 2))],
        ),
        child: Column(children: [
          Container(
            decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.vertical(top: Radius.circular(11))),
            padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 12),
            child: Row(children: [
              const Icon(Icons.remove_red_eye_rounded, color: Colors.white, size: 13),
              const SizedBox(width: 6),
              Text(eyeLabel, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Colors.white)),
            ]),
          ),
          // Disc row
          const Divider(height: 1),
          Padding(padding: const EdgeInsets.symmetric(vertical: 7, horizontal: 10), child: Row(children: [
            SizedBox(width: 90, child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Disc', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.primary)),
              Text('CDR / Appearance', style: TextStyle(fontSize: 10, color: AppColors.primary.withValues(alpha: 0.45))),
            ])),
            const SizedBox(width: 8),
            Expanded(child: _fundusPickerField(_fundusDiscMasters, 'disc', discCtrl, 'Disc (CDR)')),
          ])),
          // FR row
          const Divider(height: 1),
          Padding(padding: const EdgeInsets.symmetric(vertical: 7, horizontal: 10), child: Row(children: [
            SizedBox(width: 90, child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('FR', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.primary)),
              Text('Foveal Reflex', style: TextStyle(fontSize: 10, color: AppColors.primary.withValues(alpha: 0.45))),
            ])),
            const SizedBox(width: 8),
            Expanded(child: _fundusPickerField(_fundusFrMasters, 'fr', frCtrl, 'Foveal Reflex')),
          ])),
          // Comment row — full-width textarea
          const Divider(height: 1),
          Padding(padding: const EdgeInsets.fromLTRB(10, 7, 10, 10), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('Comment', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: AppColors.primary.withValues(alpha: 0.6))),
            const SizedBox(height: 4),
            TextFormField(
              controller: commentCtrl,
              maxLines: 3, minLines: 2,
              keyboardType: TextInputType.multiline,
              style: const TextStyle(fontSize: 12),
              decoration: InputDecoration(
                hintText: 'Additional findings…',
                hintStyle: TextStyle(color: AppColors.primary.withValues(alpha: 0.3), fontSize: 12),
                contentPadding: const EdgeInsets.symmetric(vertical: 8, horizontal: 10),
                border:        OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.15))),
                enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.15))),
                focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary)),
              ),
            ),
          ])),
        ]),
      );
    }

    return Column(children: [
      eyeCard(true,  _fundusDiscRe, _fundusFrRe, _fundusCommentRe),
      eyeCard(false, _fundusDiscLe, _fundusFrLe, _fundusCommentLe),
    ]);
  }

  Widget _fundusPickerField(List<ExamMasterItem> masterList, String apiType, TextEditingController ctrl, String label) {
    return TextFormField(
      controller: ctrl,
      readOnly: masterList.isNotEmpty,
      style: const TextStyle(fontSize: 12),
      decoration: InputDecoration(
        hintText: '—', hintStyle: TextStyle(color: AppColors.primary.withValues(alpha: 0.3), fontSize: 12),
        contentPadding: const EdgeInsets.symmetric(vertical: 6, horizontal: 8), isDense: true,
        suffixIcon: masterList.isNotEmpty ? GestureDetector(
          onTap: () => _openFundusPickerSheet(masterList, apiType, ctrl, label),
          child: Icon(Icons.arrow_drop_down_rounded, size: 18, color: AppColors.primary.withValues(alpha: 0.5)),
        ) : null,
        border:        OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.15))),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.15))),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary)),
      ),
      onTap: masterList.isNotEmpty ? () => _openFundusPickerSheet(masterList, apiType, ctrl, label) : null,
    );
  }

  void _openFundusPickerSheet(List<ExamMasterItem> masterList, String apiType, TextEditingController ctrl, String label) {
    String query = '';
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.xl))),
      builder: (bsCtx) => StatefulBuilder(builder: (_, bsSs) {
        final favs   = masterList.where((i) => i.isFavourite  && (query.isEmpty || i.value.toLowerCase().contains(query.toLowerCase()))).toList();
        final others = masterList.where((i) => !i.isFavourite && (query.isEmpty || i.value.toLowerCase().contains(query.toLowerCase()))).toList();

        Future<void> doToggle(ExamMasterItem item) async {
          try {
            final newFav = await SimpleMasterService.instance.toggleFavourite('masters/detail/$apiType', item.id);
            bsSs(() {
              final idx = masterList.indexWhere((e) => e.id == item.id);
              if (idx >= 0) masterList[idx] = ExamMasterItem(id: item.id, value: item.value, isFavourite: newFav);
            });
            if (mounted) setState(() {});
          } catch (_) {}
        }

        Widget itemTile(ExamMasterItem item) => ListTile(
          dense: true,
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 0),
          title: Text(item.value, style: const TextStyle(fontSize: 13)),
          trailing: IconButton(
            icon: Icon(item.isFavourite ? Icons.star_rounded : Icons.star_outline_rounded,
                size: 18, color: item.isFavourite ? Colors.amber : AppColors.primary.withValues(alpha: 0.3)),
            padding: EdgeInsets.zero, constraints: const BoxConstraints(),
            onPressed: () => doToggle(item),
          ),
          onTap: () { if (mounted) setState(() => ctrl.text = item.value); Navigator.pop(bsCtx); },
        );

        return DraggableScrollableSheet(
          expand: false, initialChildSize: 0.6, maxChildSize: 0.92, minChildSize: 0.3,
          builder: (_, sc) => Column(children: [
            Container(margin: const EdgeInsets.only(top: 10, bottom: 6), width: 36, height: 4,
                decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(2))),
            Padding(padding: const EdgeInsets.fromLTRB(16, 0, 16, 8), child: Row(children: [
              Expanded(child: Text(label, style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: AppColors.primary))),
              if (ctrl.text.isNotEmpty) TextButton(
                onPressed: () { if (mounted) setState(() => ctrl.text = ''); Navigator.pop(bsCtx); },
                style: TextButton.styleFrom(foregroundColor: Colors.red),
                child: const Text('Clear'),
              ),
            ])),
            Padding(padding: const EdgeInsets.fromLTRB(12, 0, 12, 8), child: TextField(
              onChanged: (v) => bsSs(() => query = v),
              decoration: InputDecoration(
                hintText: 'Search…', prefixIcon: const Icon(Icons.search_rounded, size: 18),
                isDense: true, contentPadding: const EdgeInsets.symmetric(vertical: 8, horizontal: 12),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm),
                    borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.2))),
              ),
            )),
            Expanded(child: ListView(controller: sc, children: [
              if (favs.isNotEmpty) ...[
                Padding(padding: const EdgeInsets.fromLTRB(16, 6, 16, 2),
                    child: Text('★  Favourites', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Colors.amber.shade700))),
                ...favs.map(itemTile),
                const Divider(height: 1),
              ],
              if (others.isNotEmpty) ...[
                Padding(padding: const EdgeInsets.fromLTRB(16, 6, 16, 2),
                    child: Text('All', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.primary.withValues(alpha: 0.5)))),
                ...others.map(itemTile),
              ],
              if (favs.isEmpty && others.isEmpty)
                Padding(padding: const EdgeInsets.all(24), child: Text('No results',
                    textAlign: TextAlign.center, style: TextStyle(color: AppColors.primary.withValues(alpha: 0.35)))),
              const SizedBox(height: 24),
            ])),
          ]),
        );
      }),
    );
  }

  // ── Helpers ───────────────────────────────────────────────────────────────────

  Widget _sectionHeader(String text, {Widget? trailing}) => Padding(
        padding: const EdgeInsets.only(top: 6, bottom: 8),
        child: Row(children: [
          Text(text, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: AppColors.primary)),
          if (trailing != null) ...[const Spacer(), trailing],
        ]),
      );

  Widget _addBtn(VoidCallback onTap) => GestureDetector(onTap: onTap,
        child: Container(padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(AppRadius.xl)),
            child: Icon(Icons.add_rounded, color: AppColors.primary, size: 18)));

  Widget _emptyHint(String text) => Padding(padding: const EdgeInsets.only(bottom: 8),
        child: Text(text, style: TextStyle(fontSize: 12, color: AppColors.primary.withValues(alpha: 0.4), fontStyle: FontStyle.italic)));

  Widget _eyeLabel(String eye, bool isRe) => Padding(padding: const EdgeInsets.only(bottom: 4),
        child: Text(eye, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: isRe ? Colors.red.shade700 : AppColors.primary)));

  Widget _examField(TextEditingController ctrl, String hint, {bool multiline = false, TextInputType? inputType, int rows = 1}) {
    return TextFormField(controller: ctrl, keyboardType: multiline ? TextInputType.multiline : inputType,
        maxLines: multiline ? 3 : rows, style: const TextStyle(fontSize: 13), decoration: _examDeco(hint));
  }

  InputDecoration _examDeco(String hint, {IconData? prefix}) => InputDecoration(
        hintText: hint, hintStyle: TextStyle(fontSize: 12, color: AppColors.primary.withValues(alpha: 0.35)),
        prefixIcon: prefix != null ? Icon(prefix, size: 16, color: AppColors.primary.withValues(alpha: 0.5)) : null,
        filled: true, fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.18))),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.18))),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide(color: AppColors.primary, width: 1.5)),
      );

  Widget _thCell(String text) => Expanded(child: Padding(padding: const EdgeInsets.symmetric(vertical: 8),
        child: Text(text, textAlign: TextAlign.center,
            style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: AppColors.primary.withValues(alpha: 0.7)))));

  Widget _miniField({required String initialValue, required String hint, required void Function(String) onChanged, TextInputType inputType = TextInputType.text}) {
    return TextFormField(initialValue: initialValue, keyboardType: inputType,
        inputFormatters: inputType == TextInputType.number ? [FilteringTextInputFormatter.digitsOnly] : null,
        onChanged: onChanged, style: const TextStyle(fontSize: 12),
        decoration: InputDecoration(hintText: hint, hintStyle: TextStyle(fontSize: 11, color: AppColors.primary.withValues(alpha: 0.35)),
          contentPadding: const EdgeInsets.symmetric(vertical: 8, horizontal: 10), filled: true, fillColor: Colors.white,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.15))),
          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.15))),
          focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary))));
  }

  Widget _miniDropdown({required String value, required List<String> items, required void Function(String?) onChanged, double width = 80}) {
    return SizedBox(width: width, child: DropdownButtonFormField<String>(
        initialValue: value, isExpanded: true, icon: const Icon(Icons.expand_more, size: 14),
        style: const TextStyle(fontSize: 11, color: Colors.black87),
        decoration: InputDecoration(contentPadding: const EdgeInsets.symmetric(vertical: 8, horizontal: 8), filled: true, fillColor: Colors.white,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.15))),
          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.15))),
          focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary))),
        items: items.map((s) => DropdownMenuItem(value: s, child: Text(s, overflow: TextOverflow.ellipsis))).toList(),
        onChanged: onChanged));
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
  });
  @override
  State<_MasterAutocompleteField> createState() => _MasterAutocompleteFieldState();
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
  void dispose() { _ctrl.dispose(); _focusNode.dispose(); super.dispose(); }

  @override
  void didUpdateWidget(_MasterAutocompleteField old) {
    super.didUpdateWidget(old);
    // Re-filter if master list changed (e.g. after a favourite toggle)
    if (old.items != widget.items && _showSuggestions) {
      final q = _ctrl.text.trim().toLowerCase();
      final favs = widget.items.where((i) => i.isFavourite && i.value.toLowerCase().contains(q)).toList();
      final rest = widget.items.where((i) => !i.isFavourite && i.value.toLowerCase().contains(q)).toList();
      setState(() { _filtered = [...favs, ...rest]; });
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
    setState(() { _filtered = [...favs, ...rest]; _showSuggestions = true; });
  }

  void _onChanged(String v) {
    widget.onChanged(v);
    if (v.isEmpty) {
      _showAll();
      return;
    }
    final q = v.toLowerCase();
    final favs = widget.items.where((i) => i.isFavourite && i.value.toLowerCase().contains(q)).toList();
    final rest = widget.items.where((i) => !i.isFavourite && i.value.toLowerCase().contains(q)).toList();
    setState(() { _filtered = [...favs, ...rest]; _showSuggestions = _filtered.isNotEmpty; });
  }

  void _select(ExamMasterItem item) {
    if (widget.onSelected != null) {
      _ctrl.clear();
      widget.onSelected!(item.value);
    } else {
      _ctrl.text = item.value;
      widget.onChanged(item.value);
    }
    setState(() { _filtered = []; _showSuggestions = false; });
    _focusNode.unfocus();
  }

  @override
  Widget build(BuildContext context) {
    final c = widget.color;
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      TextFormField(
        controller: _ctrl,
        focusNode: _focusNode,
        onChanged: _onChanged,
        style: const TextStyle(fontSize: 12),
        decoration: InputDecoration(
          hintText: widget.hint,
          hintStyle: TextStyle(fontSize: 11, color: c.withValues(alpha: 0.35)),
          prefixIcon: Icon(widget.icon, size: 16, color: c),
          contentPadding: const EdgeInsets.symmetric(vertical: 8, horizontal: 10),
          filled: true, fillColor: Colors.white,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: c.withValues(alpha: 0.15))),
          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: c.withValues(alpha: 0.15))),
          focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: c)),
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
            boxShadow: [BoxShadow(color: c.withValues(alpha: 0.1), blurRadius: 8, offset: const Offset(0, 3))],
          ),
          child: ListView(
            shrinkWrap: true,
            padding: EdgeInsets.zero,
            children: _filtered.take(10).map((item) => InkWell(
              onTap: () => _select(item),
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
                child: Row(children: [
                  // ★/☆ toggle button — only when onToggleFavourite is provided
                  if (widget.onToggleFavourite != null)
                    GestureDetector(
                      onTap: () => widget.onToggleFavourite!(item),
                      behavior: HitTestBehavior.opaque,
                      child: Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: Icon(
                          item.isFavourite ? Icons.star_rounded : Icons.star_border_rounded,
                          size: 16,
                          color: item.isFavourite ? Colors.amber.shade600 : Colors.grey.shade400,
                        ),
                      ),
                    )
                  else if (item.isFavourite)
                    Padding(
                      padding: const EdgeInsets.only(right: 6),
                      child: Icon(Icons.star_rounded, size: 12, color: Colors.amber.shade600),
                    ),
                  Expanded(child: Text(item.value, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w500, color: c))),
                ]),
              ),
            )).toList(),
          ),
        ),
    ]);
  }
}

class _MedicineSearchField extends StatefulWidget {
  final String initialName;
  final void Function(MedItem) onSelected;
  const _MedicineSearchField({required this.initialName, required this.onSelected});
  @override
  State<_MedicineSearchField> createState() => _MedicineSearchFieldState();
}

class _MedicineSearchFieldState extends State<_MedicineSearchField> {
  late TextEditingController _ctrl;
  Timer? _debounce;
  List<MedItem> _results = [];
  bool _showSuggestions = false;

  @override
  void initState() { super.initState(); _ctrl = TextEditingController(text: widget.initialName); }

  @override
  void dispose() { _ctrl.dispose(); _debounce?.cancel(); super.dispose(); }

  void _onChanged(String v) {
    _debounce?.cancel();
    if (v.length < 2) { setState(() { _results = []; _showSuggestions = false; }); return; }
    _debounce = Timer(const Duration(milliseconds: 400), () async {
      try {
        final r = await ExamService.instance.searchMedicines(v);
        if (mounted) setState(() { _results = r; _showSuggestions = r.isNotEmpty; });
      } catch (_) {}
    });
  }

  @override
  Widget build(BuildContext context) {
    return Column(children: [
      TextFormField(controller: _ctrl, onChanged: _onChanged, style: const TextStyle(fontSize: 12),
          decoration: InputDecoration(hintText: 'Search medicine...',
            hintStyle: TextStyle(fontSize: 11, color: AppColors.primary.withValues(alpha: 0.35)),
            prefixIcon: Icon(Icons.medication_rounded, size: 16, color: AppColors.primary),
            contentPadding: const EdgeInsets.symmetric(vertical: 8, horizontal: 10), filled: true, fillColor: Colors.white,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.15))),
            enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary.withValues(alpha: 0.15))),
            focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.sm), borderSide: BorderSide(color: AppColors.primary)))),
      if (_showSuggestions)
        Container(
          margin: const EdgeInsets.only(top: 2),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.sm),
              border: Border.all(color: AppColors.primary.withValues(alpha: 0.15)),
              boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.1), blurRadius: 8, offset: Offset(0, 3))]),
          child: Column(children: _results.take(6).map((m) => InkWell(
              onTap: () { _ctrl.text = m.name; setState(() => _showSuggestions = false); widget.onSelected(m); },
              child: Padding(padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  child: Row(children: [Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(m.name, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.primary)),
                    if (m.medicineTypeName != null) Text(m.medicineTypeName!, style: TextStyle(fontSize: 10, color: AppColors.primary.withValues(alpha: 0.55))),
                  ]))])))
          ).toList()),
        ),
    ]);
  }
}
