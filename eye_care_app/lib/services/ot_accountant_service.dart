import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/app_config.dart';
import '../models/ot_accountant_models.dart';
import '../models/ot_booking_models.dart';
import 'base_service.dart';

/// Round 3 Phase 5 — `OtAccountantApiController`. `storePayment` is the
/// single call that both records payment AND (when it completes the
/// package amount) advances the booking to Ward — there is no separate
/// "verify payment" call, don't build one client-side.
class OtAccountantService with AuthenticatedService {
  OtAccountantService._();
  static final OtAccountantService instance = OtAccountantService._();

  String get _base => AppConfig.hospitalApiUrl;

  Map<String, dynamic> _parse(http.Response res) => parseApiResponse(res);

  /// `filter`: `today` (pending-payment queue) or `completed` (payment done
  /// onward).
  Future<({List<OtBookingSummary> items, OtPaginationMeta meta})> fetchBookings({String filter = 'today', int page = 1, int perPage = 25}) async {
    final uri = Uri.parse('$_base/ot/accountant/bookings').replace(queryParameters: {'filter': filter, 'page': '$page', 'per_page': '$perPage'});
    final res = await http.get(uri, headers: await headers).timeout(AppConfig.requestTimeout);
    final body = _parse(res);
    final data = body['data'] as Map<String, dynamic>;
    final items = (data['data'] as List? ?? []).map((e) => OtBookingSummary.fromJson(e as Map<String, dynamic>)).toList();
    return (items: items, meta: OtPaginationMeta.fromJson(data));
  }

  Future<OtPaymentStatus> fetchPaymentStatus(int bookingId) async {
    final res = await http.get(Uri.parse('$_base/ot/bookings/$bookingId/payment-status'), headers: await headers).timeout(AppConfig.requestTimeout);
    final body = _parse(res);
    return OtPaymentStatus.fromJson(body['data'] as Map<String, dynamic>);
  }

  Future<OtPaymentFormData> fetchPaymentFormData(int bookingId) async {
    final res = await http.get(Uri.parse('$_base/ot/bookings/$bookingId/payment-form-data'), headers: await headers).timeout(AppConfig.requestTimeout);
    final body = _parse(res);
    return OtPaymentFormData.fromJson(body['data'] as Map<String, dynamic>);
  }

  Future<OtPaymentResult> storePayment(int bookingId, {required double packageAmount, String? receiptNumber, required String paymentMode}) async {
    final res = await http
        .post(
          Uri.parse('$_base/ot/bookings/$bookingId/payments'),
          headers: await headers,
          body: jsonEncode({
            'package_amount': packageAmount,
            if (receiptNumber != null && receiptNumber.isNotEmpty) 'receipt_number': receiptNumber,
            'payment_mode': paymentMode,
          }),
        )
        .timeout(AppConfig.requestTimeout);
    final body = _parse(res);
    return OtPaymentResult.fromJson(body['data'] as Map<String, dynamic>);
  }

  Future<OtPaymentReceipt> fetchReceipt(int paymentId) async {
    final res = await http.get(Uri.parse('$_base/ot/payments/$paymentId/receipt'), headers: await headers).timeout(AppConfig.requestTimeout);
    final body = _parse(res);
    return OtPaymentReceipt.fromJson(body['data'] as Map<String, dynamic>);
  }
}
