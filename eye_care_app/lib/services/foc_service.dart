// FOC (Free of Charge) feature disabled at the client's request — kept
// commented out (not deleted) in case it's needed again in future, mirroring
// how the web developer handled the same request on the web app.
/*
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/app_config.dart';
import '../models/foc_models.dart';
import 'base_service.dart';

class FocService with AuthenticatedService {
  FocService._();
  static final FocService instance = FocService._();


  Future<FocListResult> fetchFocs({String? status, int page = 1}) async {
    final params = <String, String>{'page': page.toString(), 'per_page': '20'};
    if (status != null) params['status'] = status;

    final uri = Uri.parse('${AppConfig.hospitalApiUrl}/foc')
        .replace(queryParameters: params);

    final resp = await http
        .get(uri, headers: await headers)
        .timeout(AppConfig.requestTimeout);

    return FocListResult.fromJson(parseApiResponse(resp));
  }

  Future<FocItem> createFoc({
    required int patientId,
    required double focFee,
    required String reason,
  }) async {
    final resp = await http
        .post(
          Uri.parse('${AppConfig.hospitalApiUrl}/foc'),
          headers: await headers,
          body: jsonEncode({
            'patient_id': patientId,
            'foc_fee': focFee,
            'reason': reason,
          }),
        )
        .timeout(AppConfig.requestTimeout);

    final body = parseApiResponse(resp);
    return FocItem.fromJson(body['data'] as Map<String, dynamic>);
  }

  Future<void> acceptFoc(int id) async {
    final resp = await http
        .post(
          Uri.parse('${AppConfig.hospitalApiUrl}/foc/$id/accept'),
          headers: await headers,
        )
        .timeout(AppConfig.requestTimeout);

    parseApiResponse(resp);
  }

  Future<void> rejectFoc(int id, String rejectedReason) async {
    final resp = await http
        .post(
          Uri.parse('${AppConfig.hospitalApiUrl}/foc/$id/reject'),
          headers: await headers,
          body: jsonEncode({'rejected_reason': rejectedReason}),
        )
        .timeout(AppConfig.requestTimeout);

    parseApiResponse(resp);
  }
}
*/
