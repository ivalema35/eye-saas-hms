import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import '../config/app_config.dart';
import '../models/doctor_dashboard_models.dart';
import 'base_service.dart';

class DoctorDashboardService with AuthenticatedService {
  DoctorDashboardService._();
  static final DoctorDashboardService instance = DoctorDashboardService._();

  Future<DoctorDashboardData> fetchDashboard({int? viewDoctorId}) async {
    var urlStr = '${AppConfig.hospitalApiUrl}/dashboard/doctor';
    if (viewDoctorId != null) urlStr += '?view_doctor_id=$viewDoctorId';

    final uri = Uri.parse(urlStr);
    debugPrint('[DoctorDashboardService] GET $uri');

    final response = await http.get(uri, headers: await headers).timeout(AppConfig.requestTimeout);

    debugPrint('[DoctorDashboardService] Status: ${response.statusCode}');

    // A zero-permission doctor gets an ordinary permission-denied 403 here
    // — distinguished from a tenant/plan-blocked 403 by the ABSENCE of the
    // `code` key (only CheckSubscriptionActive sends `code`; CheckPermission's
    // permission-denied 403 also happens to send an `error` key, so `error`
    // alone is NOT a safe discriminator — see the `PlanAccessBlockedException`
    // doc comment in base_service.dart). Distinguished so the screen can show
    // a calm "no permissions" state instead of a hard error, matching the
    // regular Dashboard's treatment of the same situation. See
    // DOCTOR_DASHBOARD_FIX_PLAN.md Phase 3.
    if (response.statusCode == 403) {
      Map<String, dynamic>? decoded;
      try {
        decoded = jsonDecode(response.body) as Map<String, dynamic>;
      } catch (_) {}
      if (decoded == null || !decoded.containsKey('code')) {
        throw PermissionDeniedException(
          (decoded?['message'] as String?) ?? 'You do not have permission to view the doctor dashboard.',
        );
      }
    }

    final body = parseApiResponse(response);
    return DoctorDashboardData.fromJson(body['data'] as Map<String, dynamic>);
  }
}
