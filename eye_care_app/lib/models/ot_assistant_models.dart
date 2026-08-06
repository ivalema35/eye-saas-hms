import 'ot_booking_models.dart';
import 'ot_counsellor_models.dart';
import 'ot_inventory_models.dart';

class OtSurgeryTypeOption {
  final int id;
  final String surgeryName;

  const OtSurgeryTypeOption({required this.id, required this.surgeryName});

  factory OtSurgeryTypeOption.fromJson(Map<String, dynamic> j) => OtSurgeryTypeOption(
        id: j['id'] as int,
        surgeryName: j['surgery_name'] as String? ?? '',
      );
}

/// Prior verification state, if this booking's surgery was already recorded
/// once (verify + record happen atomically in one call — see build PRD §8
/// gotcha, there's no standalone "verify" step).
class OtVerificationStatus {
  final bool identityVerified;
  final bool consentVerified;
  final bool paymentVerified;
  final bool correctEyeVerified;

  const OtVerificationStatus({
    required this.identityVerified,
    required this.consentVerified,
    required this.paymentVerified,
    required this.correctEyeVerified,
  });

  factory OtVerificationStatus.fromJson(Map<String, dynamic> j) => OtVerificationStatus(
        identityVerified: j['identity_verified'] as bool? ?? false,
        consentVerified: j['consent_verified'] as bool? ?? false,
        paymentVerified: j['payment_verified'] as bool? ?? false,
        correctEyeVerified: j['correct_eye_verified'] as bool? ?? false,
      );
}

class OtSurgeryFormData {
  final OtBookingSummary booking;
  final OtCounsellingItem? counselling;
  final OtVerificationStatus? verification;
  final List<OtSurgeryTypeOption> surgeryTypes;
  final List<String> medicines;

  const OtSurgeryFormData({
    required this.booking,
    this.counselling,
    this.verification,
    required this.surgeryTypes,
    required this.medicines,
  });

  factory OtSurgeryFormData.fromJson(Map<String, dynamic> j) => OtSurgeryFormData(
        booking: OtBookingSummary.fromJson(j['booking'] as Map<String, dynamic>),
        counselling: j['counselling'] != null ? OtCounsellingItem.fromJson(j['counselling'] as Map<String, dynamic>) : null,
        verification: j['verification'] != null ? OtVerificationStatus.fromJson(j['verification'] as Map<String, dynamic>) : null,
        surgeryTypes: (j['surgery_types'] as List? ?? []).map((e) => OtSurgeryTypeOption.fromJson(e as Map<String, dynamic>)).toList(),
        medicines: (j['medicines'] as List? ?? []).map((e) => (e as Map<String, dynamic>)['name'] as String? ?? '').toList(),
      );
}

/// A single `ot_medicines[]` line item in the `POST .../surgery` body.
class OtSurgeryMedicineLine {
  final String medicine;
  final String? dose;

  const OtSurgeryMedicineLine({required this.medicine, this.dose});

  Map<String, dynamic> toJson() => {'medicine': medicine, 'dose': dose};
}

class OtLensDetail {
  final int? lensInventoryId;
  final String lensName;
  final String? manufacturer;
  final String lensType;
  final double lensPower;
  final int? axis;
  final double lensMrp;
  final String? batchNumber;
  final String? serialNumber;
  final String? expiryDate;
  final bool implanted;

  const OtLensDetail({
    this.lensInventoryId,
    required this.lensName,
    this.manufacturer,
    required this.lensType,
    required this.lensPower,
    this.axis,
    required this.lensMrp,
    this.batchNumber,
    this.serialNumber,
    this.expiryDate,
    this.implanted = false,
  });

  factory OtLensDetail.fromJson(Map<String, dynamic> j) => OtLensDetail(
        lensInventoryId: j['lens_inventory_id'] as int?,
        lensName: j['lens_name'] as String? ?? '',
        manufacturer: j['manufacturer'] as String?,
        lensType: j['lens_type'] as String? ?? '',
        lensPower: numOrStringToDouble(j['lens_power']) ?? 0,
        axis: j['axis'] as int?,
        lensMrp: numOrStringToDouble(j['lens_mrp']) ?? 0,
        batchNumber: j['batch_number'] as String?,
        serialNumber: j['serial_number'] as String?,
        expiryDate: j['expiry_date'] as String?,
        implanted: j['implanted'] as bool? ?? j['implant_status'] as bool? ?? false,
      );
}

class OtLensDetailResponse {
  final OtBookingSummary booking;
  final OtLensDetail? lensDetail;
  final List<String> lensTypes;
  final List<LensPowerItem> lensPowers;
  final List<LensInventoryItem> lensInventory;

  const OtLensDetailResponse({
    required this.booking,
    this.lensDetail,
    required this.lensTypes,
    required this.lensPowers,
    required this.lensInventory,
  });

  factory OtLensDetailResponse.fromJson(Map<String, dynamic> j) => OtLensDetailResponse(
        booking: OtBookingSummary.fromJson(j['booking'] as Map<String, dynamic>),
        lensDetail: j['lens_detail'] != null ? OtLensDetail.fromJson(j['lens_detail'] as Map<String, dynamic>) : null,
        lensTypes: (j['lens_types'] as List? ?? []).map((e) => e.toString()).toList(),
        lensPowers: (j['lens_powers'] as List? ?? []).map((e) => LensPowerItem.fromJson(e as Map<String, dynamic>)).toList(),
        lensInventory: (j['lens_inventory'] as List? ?? []).map((e) => LensInventoryItem.fromJson(e as Map<String, dynamic>)).toList(),
      );
}
