/// `GET /reports/ot` — report catalogue grouped by category.
class OtReportTypeGroup {
  final String group;
  final List<OtReportType> types;
  const OtReportTypeGroup({required this.group, required this.types});
}

class OtReportType {
  final String label;
  final String key;
  const OtReportType({required this.label, required this.key});

  factory OtReportType.fromJson(Map<String, dynamic> j) => OtReportType(
        label: j['label'] as String? ?? '',
        key: j['key'] as String? ?? '',
      );
}

List<OtReportTypeGroup> parseOtReportGroups(Map<String, dynamic> data) => data.entries
    .map((e) => OtReportTypeGroup(
          group: e.key,
          types: (e.value as List? ?? []).map((t) => OtReportType.fromJson(t as Map<String, dynamic>)).toList(),
        ))
    .toList();

/// `GET /reports/ot/{type}` result — a plain heading/rows table.
class OtReportResult {
  final String type;
  final String label;
  final List<String> headings;
  final List<List<dynamic>> rows;
  final String from;
  final String to;

  const OtReportResult({required this.type, required this.label, required this.headings, required this.rows, required this.from, required this.to});

  factory OtReportResult.fromJson(Map<String, dynamic> j) => OtReportResult(
        type: j['type'] as String? ?? '',
        label: j['label'] as String? ?? '',
        headings: (j['headings'] as List? ?? []).map((e) => e.toString()).toList(),
        rows: (j['rows'] as List? ?? []).map((r) => r as List<dynamic>).toList(),
        from: j['from'] as String? ?? '',
        to: j['to'] as String? ?? '',
      );
}

/// `GET /dashboard/ot-summary` — `ot_utilization_pct` deliberately not
/// available (no OT-room-capacity concept in the schema, see build PRD §12
/// gotcha), and the two `*_minutes` fields are `null` (not `0`) when there's
/// no qualifying data in range.
class OtDashboardSummary {
  final String from;
  final String to;
  final int totalPatients;
  final int surgeriesCompleted;
  final double? avgWaitingTimeMinutes;
  final double? patientTurnaroundTimeMinutes;
  final List<OtRevenueTrendPoint> revenueTrend;
  final int lensConsumption;

  const OtDashboardSummary({
    required this.from,
    required this.to,
    required this.totalPatients,
    required this.surgeriesCompleted,
    this.avgWaitingTimeMinutes,
    this.patientTurnaroundTimeMinutes,
    required this.revenueTrend,
    required this.lensConsumption,
  });

  factory OtDashboardSummary.fromJson(Map<String, dynamic> j) => OtDashboardSummary(
        from: j['from'] as String? ?? '',
        to: j['to'] as String? ?? '',
        totalPatients: j['total_patients'] as int? ?? 0,
        surgeriesCompleted: j['surgeries_completed'] as int? ?? 0,
        avgWaitingTimeMinutes: (j['avg_waiting_time_minutes'] as num?)?.toDouble(),
        patientTurnaroundTimeMinutes: (j['patient_turnaround_time_minutes'] as num?)?.toDouble(),
        revenueTrend: (j['revenue_trend'] as List? ?? []).map((e) => OtRevenueTrendPoint.fromJson(e as Map<String, dynamic>)).toList(),
        lensConsumption: j['lens_consumption'] as int? ?? 0,
      );
}

class OtRevenueTrendPoint {
  final String date;
  final double total;
  const OtRevenueTrendPoint({required this.date, required this.total});

  factory OtRevenueTrendPoint.fromJson(Map<String, dynamic> j) => OtRevenueTrendPoint(
        date: j['date'] as String? ?? '',
        total: (j['total'] as num?)?.toDouble() ?? 0,
      );
}
