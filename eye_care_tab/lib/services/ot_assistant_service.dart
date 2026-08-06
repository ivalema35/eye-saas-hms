import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/app_config.dart';
import '../models/ot_assistant_models.dart';
import '../models/ot_booking_models.dart';
import 'base_service.dart';

/// Round 3 Phase 4 — `OtAssistantApiController`. `fetchBookings()` returns
/// only the ready-for-surgery queue — the web's lens workflow UI is hidden
/// by design (lens data is captured during counselling), there is no
/// separate ready-for-lens queue. Both surgery-record and lens-record
/// screens are reached from the same queue.
class OtAssistantService with AuthenticatedService {
  OtAssistantService._();
  static final OtAssistantService instance = OtAssistantService._();

  String get _base => AppConfig.hospitalApiUrl;

  Map<String, dynamic> _parse(http.Response res) => parseApiResponse(res);

  Future<({List<OtBookingSummary> items, OtPaginationMeta meta})> fetchBookings({int page = 1, int perPage = 25}) async {
    final uri = Uri.parse('$_base/ot/assistant/bookings').replace(queryParameters: {'page': '$page', 'per_page': '$perPage'});
    final res = await http.get(uri, headers: await headers).timeout(AppConfig.requestTimeout);
    final body = _parse(res);
    final data = body['data'] as Map<String, dynamic>;
    final items = (data['data'] as List? ?? []).map((e) => OtBookingSummary.fromJson(e as Map<String, dynamic>)).toList();
    return (items: items, meta: OtPaginationMeta.fromJson(data));
  }

  Future<OtSurgeryFormData> fetchSurgeryFormData(int bookingId) async {
    final res = await http.get(Uri.parse('$_base/ot/bookings/$bookingId/surgery-form-data'), headers: await headers).timeout(AppConfig.requestTimeout);
    final body = _parse(res);
    return OtSurgeryFormData.fromJson(body['data'] as Map<String, dynamic>);
  }

  /// The combined verify + record call — one submit, no separate "verify"
  /// step (see build PRD §8 gotcha).
  Future<void> storeSurgery(int bookingId, {
    required bool identityVerified,
    required bool consentVerified,
    required bool paymentVerified,
    required bool correctEyeVerified,
    required String surgeryName,
    String? otRoom,
    required String eyeOperated,
    String? startTime,
    String? endTime,
    required String complicationStatus,
    String? complicationNotes,
    String? bloodLoss,
    int? medicineGroupId,
    List<OtSurgeryMedicineLine> otMedicines = const [],
  }) async {
    final res = await http
        .post(
          Uri.parse('$_base/ot/bookings/$bookingId/surgery'),
          headers: await headers,
          body: jsonEncode({
            'identity_verified': identityVerified,
            'consent_verified': consentVerified,
            'payment_verified': paymentVerified,
            'correct_eye_verified': correctEyeVerified,
            'surgery_name': surgeryName,
            'ot_room': otRoom,
            'eye_operated': eyeOperated,
            'start_time': startTime,
            'end_time': endTime,
            'complication_status': complicationStatus,
            'complication_notes': complicationNotes,
            'blood_loss': bloodLoss,
            'medicine_group_id': medicineGroupId,
            'ot_medicines': otMedicines.map((m) => m.toJson()).toList(),
          }),
        )
        .timeout(AppConfig.requestTimeout);
    _parse(res);
  }

  Future<OtLensDetailResponse> fetchLens(int bookingId) async {
    final res = await http.get(Uri.parse('$_base/ot/bookings/$bookingId/lens'), headers: await headers).timeout(AppConfig.requestTimeout);
    final body = _parse(res);
    return OtLensDetailResponse.fromJson(body['data'] as Map<String, dynamic>);
  }

  Future<void> storeLens(int bookingId, {
    int? lensInventoryId,
    required String lensName,
    String? manufacturer,
    required String lensType,
    required double lensPower,
    int? axis,
    required double lensMrp,
    String? batchNumber,
    String? serialNumber,
    String? expiryDate,
    required bool implanted,
  }) async {
    final res = await http
        .post(
          Uri.parse('$_base/ot/bookings/$bookingId/lens'),
          headers: await headers,
          body: jsonEncode({
            'lens_inventory_id': lensInventoryId,
            'lens_name': lensName,
            'manufacturer': manufacturer,
            'lens_type': lensType,
            'lens_power': lensPower,
            'axis': axis,
            'lens_mrp': lensMrp,
            'batch_number': batchNumber,
            'serial_number': serialNumber,
            'expiry_date': expiryDate,
            'implanted': implanted,
          }),
        )
        .timeout(AppConfig.requestTimeout);
    _parse(res);
  }
}
