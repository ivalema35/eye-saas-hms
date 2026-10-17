import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';
import '../config/app_config.dart';
import '../models/dashboard_models.dart';
import 'base_service.dart';
import 'cache_service.dart';

class DashboardService with AuthenticatedService {
  DashboardService._();
  static final instance = DashboardService._();

  static const _cacheKey = 'cache_dashboard_v1';

  Future<DashboardData?> getCached() async {
    try {
      final json = await CacheService.instance.getJson(_cacheKey);
      if (json == null) return null;
      return DashboardData.fromJson(json as Map<String, dynamic>);
    } catch (_) {
      return null;
    }
  }

  Future<DashboardData> fetchDashboard() async {
    final uri = Uri.parse('${AppConfig.hospitalApiUrl}/admin/dashboard');
    final response = await http.get(uri, headers: await headers).timeout(AppConfig.requestTimeout);
    final json = parseApiResponse(response);
    await CacheService.instance.setJson(_cacheKey, json);
    return DashboardData.fromJson(json);
  }

  Future<TodayPatientsData> fetchTodayPatients({String searchContact = ''}) async {
    final uri = Uri.parse('${AppConfig.hospitalApiUrl}/dashboard/today-patients').replace(
      queryParameters: searchContact.isNotEmpty ? {'search_contact': searchContact} : null,
    );
    final response = await http.get(uri, headers: await headers).timeout(AppConfig.requestTimeout);
    return TodayPatientsData.fromJson(parseApiResponse(response));
  }

  /// Web/API: AdminCollectionController — the dashboard's "Total Collection"
  /// card drill-down. Defaults to today when no range is passed, matching
  /// the controller's own `resolvedDates()` fallback.
  Future<CollectionSummary> fetchCollectionSummary({required String startDate, required String endDate}) async {
    final uri = Uri.parse('${AppConfig.hospitalApiUrl}/dashboard/collection').replace(
      queryParameters: {'start_date': startDate, 'end_date': endDate},
    );
    final response = await http.get(uri, headers: await headers).timeout(AppConfig.requestTimeout);
    return CollectionSummary.fromJson(parseApiResponse(response));
  }

  Future<CollectionDetail> fetchCollectionDetail(int receptionId, {required String startDate, required String endDate}) async {
    final uri = Uri.parse('${AppConfig.hospitalApiUrl}/dashboard/collection/$receptionId').replace(
      queryParameters: {'start_date': startDate, 'end_date': endDate},
    );
    final response = await http.get(uri, headers: await headers).timeout(AppConfig.requestTimeout);
    return CollectionDetail.fromJson(parseApiResponse(response));
  }

  /// Web pull 2026-09-30 — the "Export" button on the collection detail
  /// page (`AdminCollectionController::export`), missing from this app
  /// entirely. Same download-bytes-then-open pattern as
  /// `OtDischargeService`'s document downloads.
  Future<void> downloadCollectionExport(int receptionId, {required String startDate, required String endDate}) async {
    final uri = Uri.parse('${AppConfig.hospitalApiUrl}/dashboard/collection/$receptionId/export').replace(
      queryParameters: {'start_date': startDate, 'end_date': endDate},
    );
    final req = http.Request('GET', uri);
    (await headers).forEach((k, v) => req.headers[k] = v);

    final client = http.Client();
    try {
      final streamedRes = await client.send(req).timeout(const Duration(seconds: 120));
      syncPermissionsVersion(streamedRes.headers, statusCode: streamedRes.statusCode);
      if (streamedRes.statusCode != 200) {
        throw Exception('Export failed (${streamedRes.statusCode})');
      }
      final bytes = await streamedRes.stream.toBytes();
      final ts = DateTime.now().millisecondsSinceEpoch;

      Directory dir;
      if (Platform.isAndroid) {
        dir = (await getExternalStorageDirectory()) ?? await getApplicationDocumentsDirectory();
      } else {
        dir = await getApplicationDocumentsDirectory();
      }

      final file = File('${dir.path}/Collection_${receptionId}_$ts.xlsx');
      await file.writeAsBytes(bytes);
      await OpenFilex.open(file.path);
    } finally {
      client.close();
    }
  }
}
