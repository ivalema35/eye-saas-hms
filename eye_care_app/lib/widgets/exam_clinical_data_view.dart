import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/patient_history_models.dart';

/// The read-only clinical-data rendering for one [ExamRecord].
///
/// [onlyFilled] matches the doctor-dashboard View on web: a section or a
/// table row is drawn only when that part has a value. The full history
/// timeline keeps [onlyFilled] false and still shows every box.
Widget buildExamClinicalData(ExamRecord exam, Map<int, String> diagnosisMasters, {bool onlyFilled = false}) {
  final children = <Widget>[];
  void addBox(String title, Widget? content) {
    if (content == null) return;
    if (children.isNotEmpty) children.add(const SizedBox(height: 8));
    children.add(_buildBox(title, content));
  }

  addBox('History & Vision', _buildHistoryVision(exam, onlyFilled: onlyFilled));
  addBox('Subjective Testing (ST) & Diagnosis & Rx', _buildStAndRx(exam, diagnosisMasters, onlyFilled: onlyFilled));
  addBox('On Examination (O/E)', _buildOeTable(exam.oe, onlyFilled: onlyFilled));
  addBox('Fundus', _buildFundusTable(exam.fundus, onlyFilled: onlyFilled));
  if (exam.advice.trim().isNotEmpty) addBox('Advice', _buildAdvice(exam.advice));

  if (children.isEmpty) {
    return Text('No clinical data was recorded for this visit.', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.primaryA62));
  }
  return Column(crossAxisAlignment: CrossAxisAlignment.start, children: children);
}

Widget _buildBox(String title, Widget content) {
  return Container(
    decoration: BoxDecoration(border: Border.all(color: AppColors.primaryA18), borderRadius: BorderRadius.circular(AppRadius.sm)),
    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(color: AppColors.primary, borderRadius: const BorderRadius.vertical(top: Radius.circular(7))),
        child: Text(title.toUpperCase(), style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: Colors.white, letterSpacing: 0.4)),
      ),
      Padding(padding: const EdgeInsets.all(8), child: content),
    ]),
  );
}

Widget? _buildHistoryVision(ExamRecord exam, {bool onlyFilled = false}) {
  final co = exam.coRows.where((r) => _filled(r is Map ? r['complaint'] : null)).toList();
  final kco = exam.kcoRows.where((r) => _filled(r is Map ? r['condition'] : null)).toList();
  final chips = <(String, dynamic, dynamic)>[
    ('Vn', exam.vision['vn_re'], exam.vision['vn_le']),
    ('PH', exam.vision['pnvn_re'], exam.vision['pnvn_le']),
    ('NrVn', exam.vision['nrvn_re'], exam.vision['nrvn_le']),
    ('IOP', exam.nct['iop_re'], exam.nct['iop_le']),
  ].where((c) => !onlyFilled || _filled(c.$2) || _filled(c.$3)).toList();
  final pg = _buildRefractionTable(exam.pgRe, exam.pgLe, onlyFilled: onlyFilled);
  if (onlyFilled && co.isEmpty && kco.isEmpty && chips.isEmpty && pg == null) return null;

  return Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      if (!onlyFilled || co.isNotEmpty) ...[
        _buildSectionLabel('C/O'),
        _buildTable(
          headers: const ['Complaint', 'Since', 'Eye', 'Comment'],
          widths: const [2.0, 1.0, 1.0, 1.5],
          rows: co.map((r) => <String>[(r['complaint'] ?? '-').toString(), '${r['since'] ?? '-'} ${r['unit'] ?? ''}'.trim(), (r['eye'] ?? '-').toString(), (r['comment'] ?? '-').toString()]).toList(),
          emptyLabel: '—',
        ),
      ],
      if (kco.isNotEmpty) ...[
        const SizedBox(height: 6),
        _buildSectionLabel('K/C/O'),
        _buildTable(
          headers: const ['Condition', 'Since', 'Comment'],
          widths: const [2.0, 1.0, 1.5],
          rows: kco.map((r) => <String>[(r['condition'] ?? '-').toString(), '${r['since'] ?? '-'} ${r['unit'] ?? ''}'.trim(), (r['comment'] ?? '-').toString()]).toList(),
          emptyLabel: '—',
        ),
      ],
      if (chips.isNotEmpty) ...[
        const SizedBox(height: 8),
        Wrap(spacing: 10, runSpacing: 4, children: [
          for (final c in chips) _vnChip(c.$1, _cv(c.$2), _cv(c.$3)),
        ]),
      ],
      if (pg != null) ...[const SizedBox(height: 8), pg],
    ],
  );
}

Widget _vnChip(String label, String re, String le) {
  return Container(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
    decoration: BoxDecoration(color: AppColors.primaryA06, borderRadius: BorderRadius.circular(6), border: Border.all(color: AppColors.primaryA12)),
    child: RichText(
      text: TextSpan(style: TextStyle(fontSize: 11, color: AppColors.primary), children: [
        TextSpan(text: '$label  ', style: const TextStyle(fontWeight: FontWeight.w800)),
        TextSpan(text: '$re/$le'),
      ]),
    ),
  );
}

Widget? _buildRefractionTable(Map<String, dynamic> re, Map<String, dynamic> le, {bool onlyFilled = false}) {
  bool rowFilled(List<String> keys) => keys.any((k) => _filled(re[k]) || _filled(le[k]));
  final showD = !onlyFilled || rowFilled(const ['ds', 'dc', 'ax', 'vn']);
  final showN = !onlyFilled || rowFilled(const ['ns', 'nc', 'na', 'near_vn']);
  if (!showD && !showN) return null;
  return Table(
    border: TableBorder.all(color: AppColors.primaryA18, width: 0.8),
    columnWidths: const {0: FixedColumnWidth(18), 1: FlexColumnWidth(), 2: FlexColumnWidth(), 3: FlexColumnWidth(), 4: FlexColumnWidth(), 5: FlexColumnWidth(), 6: FlexColumnWidth(), 7: FlexColumnWidth(), 8: FlexColumnWidth()},
    children: [
      TableRow(decoration: BoxDecoration(color: AppColors.primary), children: [_th(''), _th('RIGHT EYE (RE)'), _th(''), _th(''), _th(''), _th('LEFT EYE (LE)'), _th(''), _th(''), _th('')]),
      TableRow(decoration: BoxDecoration(color: AppColors.primaryA08), children: [_thSub(''), _thSub('SPH'), _thSub('CYL'), _thSub('AXIS'), _thSub('VN'), _thSub('SPH'), _thSub('CYL'), _thSub('AXIS'), _thSub('VN')]),
      if (showD) TableRow(children: [_rowLabel('D'), _td(_cv(re['ds'])), _td(_cv(re['dc'])), _td(_cv(re['ax'])), _td(_cv(re['vn'])), _td(_cv(le['ds'])), _td(_cv(le['dc'])), _td(_cv(le['ax'])), _td(_cv(le['vn']))]),
      if (showN) TableRow(children: [_rowLabel('N'), _td(_cv(re['ns'])), _td(_cv(re['nc'])), _td(_cv(re['na'])), _td(_cv(re['near_vn'])), _td(_cv(le['ns'])), _td(_cv(le['nc'])), _td(_cv(le['na'])), _td(_cv(le['near_vn']))]),
    ],
  );
}

Widget? _buildStAndRx(ExamRecord exam, Map<int, String> diagnosisMasters, {bool onlyFilled = false}) {
  final dxIds = exam.diagnoses.map((id) => (id is num) ? id.toInt() : int.tryParse(id.toString())).whereType<int>().toList();
  final dxNames = dxIds.map((id) => diagnosisMasters[id] ?? '').where((s) => s.isNotEmpty).join(', ');
  final st = _buildRefractionTable(exam.stRe, exam.stLe, onlyFilled: onlyFilled);
  final hasEyeAdd = _filled(exam.stRe['add']) || _filled(exam.stLe['add']);
  final hasTopAdd = onlyFilled && _filled(exam.st['add']);
  final hasAdd = hasEyeAdd || hasTopAdd;
  final hasLens = onlyFilled && _filled(exam.st['lens_type']);
  final showDilate = !onlyFilled || exam.dilate == 'Yes';
  final showDx = !onlyFilled || dxNames.isNotEmpty || showDilate;
  final showRx = !onlyFilled || exam.prescriptions.isNotEmpty;
  if (onlyFilled && st == null && !hasAdd && exam.stBadges.isEmpty && !hasLens && dxNames.isEmpty && exam.dilate != 'Yes' && exam.prescriptions.isEmpty) {
    return null;
  }

  return Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      if (st != null) st,
      if (hasAdd) ...[
        const SizedBox(height: 6),
        Row(children: [
          Text('ADD  ', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: AppColors.primary)),
          Text(
            hasTopAdd && !hasEyeAdd ? _cv(exam.st['add']) : 'RE: ${_cv(exam.stRe['add'])}  LE: ${_cv(exam.stLe['add'])}',
            style: TextStyle(fontSize: 11, color: AppColors.primary),
          ),
        ]),
      ],
      if (hasLens) ...[
        const SizedBox(height: 6),
        Text('Lens: ${exam.st['lens_type']}', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.primary)),
      ],
      if (exam.stBadges.isNotEmpty) ...[
        const SizedBox(height: 6),
        Wrap(spacing: 4, runSpacing: 4, children: exam.stBadges.map((b) => Container(padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3), decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.circular(AppRadius.md)), child: Text(b, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Colors.white)))).toList()),
      ],
      if (showDx) ...[
        const SizedBox(height: 8),
        _buildSectionLabel('Diagnosis & Rx'),
        Padding(
          padding: const EdgeInsets.only(bottom: 6),
          child: Wrap(children: [
            RichText(
              text: TextSpan(style: TextStyle(fontSize: 11, color: AppColors.primary), children: [
                if (!onlyFilled || dxNames.isNotEmpty) ...[
                  const TextSpan(text: 'Dx: ', style: TextStyle(fontWeight: FontWeight.w800)),
                  TextSpan(text: dxNames.isEmpty ? '-' : dxNames),
                ],
                if (showDilate) ...[
                  const TextSpan(text: '   Dilate: ', style: TextStyle(fontWeight: FontWeight.w800)),
                  TextSpan(text: exam.dilate),
                ],
              ]),
            ),
          ]),
        ),
      ],
      if (showRx) _buildTable(headers: const ['Medicine', 'Dosage', 'Days', 'Eye'], widths: const [2.5, 1.2, 1.0, 1.0], rows: exam.prescriptions.map((rx) => [rx.medicineName, rx.dosage, rx.duration, rx.eye]).toList(), emptyLabel: 'No medicines'),
    ],
  );
}

Widget? _buildOeTable(Map<String, dynamic> oe, {bool onlyFilled = false}) {
  const fields = [('sac', 'SAC'), ('lid', 'LID'), ('conj', 'CONJ'), ('cornea', 'CORNEA'), ('ac', 'AC'), ('iris', 'IRIS'), ('pupil', 'PUPIL'), ('lens', 'LENS'), ('em', 'EM'), ('covertest', 'COVERTEST'), ('other', 'OTHER')];
  final shown = onlyFilled ? fields.where((f) => _filled(oe['${f.$1}_re']) || _filled(oe['${f.$1}_le'])).toList() : fields;
  if (shown.isEmpty) return null;
  return Table(
    border: TableBorder.all(color: AppColors.primaryA18, width: 0.8),
    columnWidths: const {0: FlexColumnWidth(1.6), 1: FlexColumnWidth(1.0), 2: FlexColumnWidth(1.0)},
    children: [
      TableRow(decoration: BoxDecoration(color: AppColors.primary), children: [_th('O/E', align: TextAlign.left), _th('RIGHT'), _th('LEFT')]),
      ...shown.map((f) => TableRow(children: [_rowLabel(f.$2, align: TextAlign.left), _td(_cv(oe['${f.$1}_re'])), _td(_cv(oe['${f.$1}_le']))])),
    ],
  );
}

Widget? _buildFundusTable(Map<String, dynamic> fundus, {bool onlyFilled = false}) {
  final rows = <(String, String, String)>[
    ('DISC', 'disc_re', 'disc_le'),
    ('FR', 'fr_re', 'fr_le'),
    ('MACULA', 'macula_re', 'macula_le'),
    ('VESSELS', 'vessels_re', 'vessels_le'),
    ('PERIPHERY', 'periphery_re', 'periphery_le'),
    ('COMMENT', 'comment_re', 'comment_le'),
  ].where((r) => !onlyFilled || _filled(fundus[r.$2]) || _filled(fundus[r.$3]) || (r.$1 == 'COMMENT' && _filled(fundus['comment']))).toList();
  if (onlyFilled && rows.isEmpty) return null;
  final drawn = onlyFilled ? rows : rows.where((r) => r.$1 == 'DISC' || r.$1 == 'FR' || r.$1 == 'COMMENT').toList();
  return Table(
    border: TableBorder.all(color: AppColors.primaryA18, width: 0.8),
    columnWidths: const {0: FlexColumnWidth(1.6), 1: FlexColumnWidth(1.0), 2: FlexColumnWidth(1.0)},
    children: [
      TableRow(decoration: BoxDecoration(color: AppColors.primary), children: [_th('Fundus', align: TextAlign.left), _th('RIGHT'), _th('LEFT')]),
      for (final r in drawn)
        TableRow(children: [
          _rowLabel(r.$1, align: TextAlign.left),
          _td(r.$1 == 'COMMENT' && onlyFilled && !_filled(fundus[r.$2]) && _filled(fundus['comment']) ? _cv(fundus['comment']) : _cv(fundus[r.$2])),
          _td(_cv(fundus[r.$3])),
        ]),
    ],
  );
}

Widget _buildAdvice(String text) => Text(text.trim(), style: TextStyle(fontSize: 12, height: 1.6, color: AppColors.primary));

Widget _buildSectionLabel(String label) => Padding(padding: const EdgeInsets.only(bottom: 4), child: Text(label, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: AppColors.primary, letterSpacing: 0.3)));

Widget _buildTable({required List<String> headers, required List<double> widths, required List<List<String>> rows, required String emptyLabel}) {
  final colWidths = <int, TableColumnWidth>{};
  for (var i = 0; i < widths.length; i++) {
    colWidths[i] = FlexColumnWidth(widths[i]);
  }
  return Table(
    border: TableBorder.all(color: AppColors.primaryA18, width: 0.8),
    columnWidths: colWidths,
    children: [
      TableRow(decoration: BoxDecoration(color: AppColors.primary), children: headers.asMap().map((i, h) => MapEntry(i, _th(h, align: i == 0 ? TextAlign.left : TextAlign.center))).values.toList()),
      if (rows.isEmpty)
        TableRow(children: [
          TableCell(child: Padding(padding: const EdgeInsets.symmetric(vertical: 6), child: Text(emptyLabel, textAlign: TextAlign.center, style: TextStyle(fontSize: 10, color: AppColors.primaryA50)))),
          ...List.generate(headers.length - 1, (_) => const TableCell(child: SizedBox())),
        ])
      else
        ...rows.map((row) => TableRow(children: row.asMap().map((i, cell) => MapEntry(i, i == 0 ? _rowLabel(cell, align: TextAlign.left) : _td(cell))).values.toList())),
    ],
  );
}

Widget _th(String text, {TextAlign align = TextAlign.center}) => TableCell(child: Padding(padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 4), child: Text(text, textAlign: align, style: const TextStyle(fontSize: 9, fontWeight: FontWeight.w800, color: Colors.white))));

Widget _thSub(String text) => TableCell(child: Container(color: AppColors.primaryA08, padding: const EdgeInsets.symmetric(horizontal: 3, vertical: 3), child: Text(text, textAlign: TextAlign.center, style: TextStyle(fontSize: 8, fontWeight: FontWeight.w700, color: AppColors.primaryA62))));

Widget _rowLabel(String text, {TextAlign align = TextAlign.center}) => TableCell(child: Container(color: AppColors.primaryA06, padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 5), child: Text(text, textAlign: align, style: TextStyle(fontSize: 9, fontWeight: FontWeight.w800, color: AppColors.primary))));

Widget _td(String text) => TableCell(child: Padding(padding: const EdgeInsets.symmetric(horizontal: 3, vertical: 5), child: Text(text, textAlign: TextAlign.center, style: TextStyle(fontSize: 10, color: AppColors.primary))));

String _cv(dynamic v) => _filled(v) ? v.toString() : '-';

bool _filled(dynamic v) {
  if (v == null) return false;
  final s = v.toString().trim();
  return s.isNotEmpty && s != '-' && s != '—';
}
