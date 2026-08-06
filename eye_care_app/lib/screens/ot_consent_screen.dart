import 'package:flutter/material.dart';
import 'package:signature/signature.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/ot_booking_models.dart';
import '../services/ot_counsellor_service.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_section_header.dart';
import '../widgets/ot/signature_pad_field.dart';

/// Round 3 Phase 1 — Consent capture, the final step after counselling is
/// saved. `storeConsent()` and `sendToBilling()` are two separate calls
/// (matches the backend contract exactly — send-to-billing 422s if consent
/// isn't given yet, so the button stays disabled until consent saves).
class OtConsentScreen extends StatefulWidget {
  final int bookingId;
  final String patientName;

  const OtConsentScreen({super.key, required this.bookingId, required this.patientName});

  @override
  State<OtConsentScreen> createState() => _OtConsentScreenState();
}

class _OtConsentScreenState extends State<OtConsentScreen> {
  final _patientSigCtrl = SignatureController(penStrokeWidth: 2, penColor: AppColors.darkNavy);
  final _guardianSigCtrl = SignatureController(penStrokeWidth: 2, penColor: AppColors.darkNavy);
  final _witnessCtrl = TextEditingController();

  bool _loading = true;
  String? _loadError;
  bool _consentGiven = false;
  bool _savingConsent = false;
  bool _sendingToBilling = false;
  bool _consentSaved = false;
  String? _otStatus;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _patientSigCtrl.dispose();
    _guardianSigCtrl.dispose();
    _witnessCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _loadError = null; });
    try {
      final detail = await OtCounsellorService.instance.fetchCounsellingDetail(widget.bookingId);
      if (mounted) {
        setState(() {
          _loading = false;
          _otStatus = detail.booking.otStatus;
          final consent = detail.consent;
          if (consent != null) {
            _consentGiven = consent.consentGiven;
            _witnessCtrl.text = consent.witnessName ?? '';
            _consentSaved = consent.consentGiven;
          }
        });
      }
    } catch (e) {
      if (mounted) setState(() { _loadError = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  Future<void> _saveConsent() async {
    if (!_consentGiven) {
      showAppSnackBar(context, 'Consent must be given to proceed', isError: true);
      return;
    }
    setState(() => _savingConsent = true);
    try {
      final patientSig = await exportSignatureDataUri(_patientSigCtrl);
      final guardianSig = await exportSignatureDataUri(_guardianSigCtrl);
      await OtCounsellorService.instance.storeConsent(
        widget.bookingId,
        consentGiven: _consentGiven,
        patientSignatureDataUri: patientSig,
        guardianSignatureDataUri: guardianSig,
        witnessName: _witnessCtrl.text.trim().isEmpty ? null : _witnessCtrl.text.trim(),
      );
      if (!mounted) return;
      setState(() { _savingConsent = false; _consentSaved = true; });
      showAppSnackBar(context, 'Consent saved', isSuccess: true);
    } catch (e) {
      if (mounted) {
        setState(() => _savingConsent = false);
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
    }
  }

  Future<void> _sendToBilling() async {
    setState(() => _sendingToBilling = true);
    try {
      await OtCounsellorService.instance.sendToBilling(widget.bookingId);
      if (!mounted) return;
      showAppSnackBar(context, 'Sent to Billing', isSuccess: true);
      // Pop both this screen and the Counselling form beneath it, back to
      // the Counsellor dashboard — the whole flow (counselling → consent →
      // billing) is done for this booking.
      final nav = Navigator.of(context);
      nav.pop();
      nav.pop();
    } catch (e) {
      if (mounted) {
        setState(() => _sendingToBilling = false);
        showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      body: Column(children: [
        _buildHeader(),
        Expanded(
          child: _loading
              ? Center(child: CircularProgressIndicator(color: AppColors.primary))
              : _loadError != null
                  ? AppErrorState(message: _loadError!, onRetry: _load)
                  : _buildForm(),
        ),
      ]),
    );
  }

  Widget _buildHeader() {
    return Container(
      decoration: BoxDecoration(gradient: LinearGradient(colors: [AppColors.primary, AppColors.blueLight], begin: Alignment.topLeft, end: Alignment.bottomRight)),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(8, 10, 20, 14),
          child: Row(children: [
            IconButton(icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20), onPressed: () => Navigator.pop(context)),
            Container(width: 40, height: 40, decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(10)), child: const Icon(Icons.draw_rounded, color: Colors.white, size: 20)),
            const SizedBox(width: 12),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                const Text('Consent', style: TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.w800)),
                Text(widget.patientName, style: TextStyle(color: Colors.white.withValues(alpha: 0.75), fontSize: 11)),
              ]),
            ),
          ]),
        ),
      ),
    );
  }

  Widget _buildForm() {
    return Column(children: [
      Expanded(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 20),
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.08))),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                const AppSectionHeader(title: 'Informed Consent', icon: Icons.draw_rounded),
                SwitchListTile(
                  value: _consentGiven,
                  onChanged: (v) => setState(() => _consentGiven = v),
                  title: const Text('Patient / guardian has given consent', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                  contentPadding: EdgeInsets.zero,
                  activeThumbColor: AppColors.teal,
                ),
                const SizedBox(height: 12),
                SignaturePadField(label: 'Patient Signature', controller: _patientSigCtrl),
                const SizedBox(height: 16),
                SignaturePadField(label: 'Guardian Signature (optional)', controller: _guardianSigCtrl),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _witnessCtrl,
                  decoration: InputDecoration(
                    labelText: 'Witness Name',
                    filled: true,
                    fillColor: const Color(0xFFF0F6FB),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(AppRadius.md), borderSide: BorderSide.none),
                  ),
                ),
              ]),
            ),
            const SizedBox(height: 20),
            _buildBillingCard(),
          ],
        ),
      ),
      SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
          child: ElevatedButton(
            onPressed: _savingConsent ? null : _saveConsent,
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md))),
            child: _savingConsent ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) : const Text('Save Consent', style: TextStyle(fontWeight: FontWeight.w700)),
          ),
        ),
      ),
    ]);
  }

  // Matches web's separate "Ready for Billing?" card exactly — its own
  // section with a status subtitle, not just a button glued onto the
  // Consent form's action row.
  Widget _buildBillingCard() {
    final alreadySent = _otStatus == OtStatus.counselled;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(AppRadius.md), border: Border.all(color: AppColors.primary.withValues(alpha: 0.08))),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        const Text('Ready for Billing?', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800)),
        const SizedBox(height: 4),
        Text('Requires counselling saved and consent given.', style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
        const SizedBox(height: 14),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton.icon(
            onPressed: (alreadySent || !_consentSaved || _sendingToBilling) ? null : _sendToBilling,
            icon: alreadySent ? const Icon(Icons.check_circle_outline_rounded, size: 18) : const Icon(Icons.send_rounded, size: 18),
            label: _sendingToBilling
                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                : Text(alreadySent ? 'Already Sent to Billing' : 'Send to Billing', style: const TextStyle(fontWeight: FontWeight.w700)),
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.green, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md))),
          ),
        ),
      ]),
    );
  }
}
