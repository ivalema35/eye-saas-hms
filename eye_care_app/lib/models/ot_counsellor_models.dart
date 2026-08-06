import 'ot_booking_models.dart';
import 'ot_inventory_models.dart';

/// A package suggestion returned by `package-lookup` — a lighter shape than
/// the full `OtPackageMasterItem` (no id/lens_cost/room_category, since the
/// caller already supplied those as query params).
class OtPackageLookupSuggestion {
  final String packageName;
  final double otCharges;
  final double surgeonCharges;
  final double nursingCharges;
  final double consumablesCharges;

  const OtPackageLookupSuggestion({
    required this.packageName,
    required this.otCharges,
    required this.surgeonCharges,
    required this.nursingCharges,
    required this.consumablesCharges,
  });

  factory OtPackageLookupSuggestion.fromJson(Map<String, dynamic> j) => OtPackageLookupSuggestion(
        packageName: j['package_name'] as String? ?? '',
        otCharges: numOrStringToDouble(j['ot_charges']) ?? 0,
        surgeonCharges: numOrStringToDouble(j['surgeon_charges']) ?? 0,
        nursingCharges: numOrStringToDouble(j['nursing_charges']) ?? 0,
        consumablesCharges: numOrStringToDouble(j['consumables_charges']) ?? 0,
      );
}

/// The counselling record attached to a booking. `toJson()` builds the
/// `POST .../counselling` body exactly.
class OtCounsellingItem {
  final String? diagnosis;
  final String eye; // RE | LE | Both
  final bool surgeryTypeConfirmed;
  final bool mediclaim;
  final String? lensOption; // must match an active ot_lens_options.name
  final String? lensCategory; // standard | premium
  final String? lensCompany;
  final String? lensModel;
  final String? lensType;
  final double? estimatedPower;
  final double? lensCost;
  final String? packageName;
  final String roomCategory; // general | private
  final double otCharges;
  final double surgeonCharges;
  final double nursingCharges;
  final double consumablesCharges;
  final String paymentMode; // mediclaim | cash | online
  final bool bloodReportsVerified;
  final bool bloodReportsNormal;
  final String? notes;
  final double? totalEstimate;

  const OtCounsellingItem({
    this.diagnosis,
    required this.eye,
    this.surgeryTypeConfirmed = false,
    required this.mediclaim,
    this.lensOption,
    this.lensCategory,
    this.lensCompany,
    this.lensModel,
    this.lensType,
    this.estimatedPower,
    this.lensCost,
    this.packageName,
    required this.roomCategory,
    required this.otCharges,
    required this.surgeonCharges,
    required this.nursingCharges,
    required this.consumablesCharges,
    required this.paymentMode,
    this.bloodReportsVerified = false,
    this.bloodReportsNormal = false,
    this.notes,
    this.totalEstimate,
  });

  factory OtCounsellingItem.fromJson(Map<String, dynamic> j) => OtCounsellingItem(
        diagnosis: j['diagnosis'] as String?,
        eye: j['eye'] as String? ?? 'Both',
        surgeryTypeConfirmed: j['surgery_type_confirmed'] as bool? ?? false,
        mediclaim: j['mediclaim'] as bool? ?? false,
        lensOption: j['lens_option'] as String?,
        lensCategory: j['lens_category'] as String?,
        lensCompany: j['lens_company'] as String?,
        lensModel: j['lens_model'] as String?,
        lensType: j['lens_type'] as String?,
        estimatedPower: numOrStringToDouble(j['estimated_power']),
        lensCost: numOrStringToDouble(j['lens_cost']),
        packageName: j['package_name'] as String?,
        roomCategory: j['room_category'] as String? ?? 'general',
        otCharges: numOrStringToDouble(j['ot_charges']) ?? 0,
        surgeonCharges: numOrStringToDouble(j['surgeon_charges']) ?? 0,
        nursingCharges: numOrStringToDouble(j['nursing_charges']) ?? 0,
        consumablesCharges: numOrStringToDouble(j['consumables_charges']) ?? 0,
        paymentMode: j['payment_mode'] as String? ?? 'cash',
        bloodReportsVerified: j['blood_reports_verified'] as bool? ?? false,
        bloodReportsNormal: j['blood_reports_normal'] as bool? ?? false,
        notes: j['notes'] as String?,
        totalEstimate: numOrStringToDouble(j['total_estimate']),
      );

  Map<String, dynamic> toJson() => {
        'diagnosis': diagnosis,
        'eye': eye,
        'surgery_type_confirmed': surgeryTypeConfirmed,
        'mediclaim': mediclaim,
        'lens_option': lensOption,
        'lens_category': lensCategory,
        'lens_company': lensCompany,
        'lens_model': lensModel,
        'lens_type': lensType,
        'estimated_power': estimatedPower,
        'lens_cost': lensCost,
        'package_name': packageName,
        'room_category': roomCategory,
        'ot_charges': otCharges,
        'surgeon_charges': surgeonCharges,
        'nursing_charges': nursingCharges,
        'consumables_charges': consumablesCharges,
        'payment_mode': mediclaim ? 'mediclaim' : paymentMode,
        'blood_reports_verified': bloodReportsVerified,
        'blood_reports_normal': bloodReportsNormal,
        'notes': notes,
      };
}

class OtConsentItem {
  final bool consentGiven;
  final String? patientSignatureUrl;
  final String? guardianSignatureUrl;
  final String? witnessName;
  final String? consentDate;

  const OtConsentItem({
    required this.consentGiven,
    this.patientSignatureUrl,
    this.guardianSignatureUrl,
    this.witnessName,
    this.consentDate,
  });

  factory OtConsentItem.fromJson(Map<String, dynamic> j) => OtConsentItem(
        consentGiven: j['consent_given'] as bool? ?? false,
        patientSignatureUrl: j['patient_signature_url'] as String?,
        guardianSignatureUrl: j['guardian_signature_url'] as String?,
        witnessName: j['witness_name'] as String?,
        consentDate: j['consent_date'] as String?,
      );
}

/// `GET .../ot/bookings/{id}/counselling` — full detail for the counselling
/// form screen.
class OtCounsellingDetail {
  final OtBookingSummary booking;
  final OtCounsellingItem? counselling;
  final OtConsentItem? consent;
  final List<OtLensOptionItem> lensOptions;
  final List<OtPackageMasterItem> packageCostOptions;

  const OtCounsellingDetail({
    required this.booking,
    this.counselling,
    this.consent,
    required this.lensOptions,
    required this.packageCostOptions,
  });

  factory OtCounsellingDetail.fromJson(Map<String, dynamic> j) => OtCounsellingDetail(
        booking: OtBookingSummary.fromJson(j['booking'] as Map<String, dynamic>),
        counselling: j['counselling'] != null ? OtCounsellingItem.fromJson(j['counselling'] as Map<String, dynamic>) : null,
        consent: j['consent'] != null ? OtConsentItem.fromJson(j['consent'] as Map<String, dynamic>) : null,
        lensOptions: (j['lens_options'] as List? ?? []).map((e) => OtLensOptionItem.fromJson(e as Map<String, dynamic>)).toList(),
        packageCostOptions: (j['package_cost_options'] as List? ?? []).map((e) => OtPackageMasterItem.fromJson(e as Map<String, dynamic>)).toList(),
      );
}
