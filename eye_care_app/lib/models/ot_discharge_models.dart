/// Laravel's `decimal:N` model casts serialize to JSON as a STRING (e.g.
/// "1234.00"), not a number — every money/measurement field read from a raw
/// (un-mapped) Eloquent model response needs this instead of a bare
/// `as num?` cast. See OT_WEB_PARITY_FIX_PRD.md (decimal-cast gotcha).
double? numOrStringToDouble(dynamic v) => switch (v) { num n => n.toDouble(), String s => double.tryParse(s), _ => null };

/// Result of `POST .../invoice/generate` — flips the booking to
/// `discharged` in the same call.
class OtInvoiceGenerateResult {
  final String invoiceNumber;
  final double totalAmount;
  final double netAmount;

  const OtInvoiceGenerateResult({required this.invoiceNumber, required this.totalAmount, required this.netAmount});

  factory OtInvoiceGenerateResult.fromJson(Map<String, dynamic> j) => OtInvoiceGenerateResult(
        invoiceNumber: j['invoice_number'] as String? ?? '',
        totalAmount: numOrStringToDouble(j['total_amount']) ?? 0,
        netAmount: numOrStringToDouble(j['net_amount']) ?? 0,
      );
}

/// One entry from the `discharge-bundle` manifest — a URL list, **not** a
/// merged PDF (see build PRD §10 gotcha, no server-side PDF merge exists).
class OtDischargeDocument {
  final String label;
  final String downloadUrl;

  const OtDischargeDocument({required this.label, required this.downloadUrl});

  factory OtDischargeDocument.fromJson(Map<String, dynamic> j) => OtDischargeDocument(
        label: j['label'] as String? ?? '',
        downloadUrl: j['download_url'] as String? ?? '',
      );
}

/// The 8 individual print document types — matches the 8
/// `GET .../print/{type}` routes exactly (`discharge-bundle` is handled
/// separately, it's a manifest not a print type).
enum OtDischargeDocType {
  invoice('invoice', 'Invoice'),
  summaryBill('summary-bill', 'Summary Bill'),
  discharge('discharge', 'Discharge Summary'),
  certificate('certificate', 'Certificate'),
  medicineSlip('medicine-slip', 'Medicine Slip'),
  prescription('prescription', 'Prescription'),
  lensSlip('lens-slip', 'Lens Slip'),
  followupSlip('followup-slip', 'Follow-up Slip');

  final String path;
  final String label;
  const OtDischargeDocType(this.path, this.label);
}
