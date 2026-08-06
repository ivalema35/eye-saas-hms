import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../widgets/app_animations.dart';
import '../constants/permissions.dart';
import '../models/auth_models.dart';
import '../services/permission_service.dart';
import '../services/settings_service.dart';


class SettingsScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;

  const SettingsScreen({super.key, required this.user, required this.hospital});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  final _formKey = GlobalKey<FormState>();

  // General
  late final TextEditingController _nameCtrl;
  late final TextEditingController _emailCtrl;
  late final TextEditingController _phoneCtrl;
  late final TextEditingController _addressCtrl;

  // Location cascade state
  String _selCountry    = '';
  int?   _selCountryId;
  String _selState      = '';
  int?   _selStateId;
  String _selDistrict   = '';
  int?   _selDistrictId;
  String _selCity       = '';
  String _selTimezone   = '';

  // Billing
  late final TextEditingController _prefixCtrl;
  late final TextEditingController _taxCtrl;

  // Print
  late final TextEditingController _headerNoteCtrl;
  late final TextEditingController _footerNoteCtrl;

  // Queue
  late final TextEditingController _dilationCtrl;
  late final Map<String, TextEditingController> _waitCtrl;

  // Logo
  String? _logoUrl;
  bool _uploadingLogo = false;

  // Page state
  bool _letterPadAvailable = false;
  String _paginationLimit  = '25';
  bool _isDirty  = false;
  bool _loading  = true;
  bool _saving   = false;
  String? _error;
  HospitalSettingsData? _settings;

  // Debounce timer for wait-threshold preview updates
  Timer? _debounce;

  // Location API cache — avoid re-fetching on each picker open
  List<LocationCountry>?             _cachedCountries;
  final Map<int, List<LocationItem>> _cachedStates    = {};
  final Map<int, List<LocationItem>> _cachedDistricts = {};
  final Map<int, List<LocationItem>> _cachedCities    = {};
  List<LocationTimezone>?            _cachedTimezones;

  @override
  void initState() {
    super.initState();
    _nameCtrl       = TextEditingController(text: widget.hospital.name);
    _emailCtrl      = TextEditingController();
    _phoneCtrl      = TextEditingController();
    _addressCtrl    = TextEditingController();
    _prefixCtrl     = TextEditingController();
    _taxCtrl        = TextEditingController();
    _headerNoteCtrl = TextEditingController();
    _footerNoteCtrl = TextEditingController();
    _dilationCtrl   = TextEditingController();
    _waitCtrl = {
      'wait_green_max':     TextEditingController(),
      'wait_orange_max':    TextEditingController(),
      'wait_red_max':       TextEditingController(),
      'wait_d_green_max':   TextEditingController(),
      'wait_d_orange_max':  TextEditingController(),
      'wait_d_red_max':     TextEditingController(),
      'wait_nd_green_max':  TextEditingController(),
      'wait_nd_orange_max': TextEditingController(),
      'wait_nd_red_max':    TextEditingController(),
    };
    _load();
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _emailCtrl.dispose();
    _phoneCtrl.dispose();
    _addressCtrl.dispose();
    _prefixCtrl.dispose();
    _taxCtrl.dispose();
    _headerNoteCtrl.dispose();
    _footerNoteCtrl.dispose();
    _dilationCtrl.dispose();
    for (final c in _waitCtrl.values) {
      c.dispose();
    }
    _debounce?.cancel();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final s = await SettingsService.instance.fetchSettings();
      if (!mounted) return;
      _fillSettings(s);
      setState(() { _settings = s; _loading = false; _isDirty = false; });
    } catch (e) {
      if (!mounted) return;
      setState(() { _loading = false; _error = e.toString().replaceFirst('Exception: ', ''); });
    }
  }

  void _fillSettings(HospitalSettingsData s) {
    _nameCtrl.text       = s.hospitalName;
    _emailCtrl.text      = s.hospitalEmail;
    _phoneCtrl.text      = s.hospitalPhone;
    _addressCtrl.text    = s.hospitalAddress;
    // Logo
    _logoUrl     = s.hospitalLogoUrl.isNotEmpty ? s.hospitalLogoUrl : null;
    // Location — names and pre-resolved IDs from server
    _selCountry   = s.hospitalCountry;
    _selCountryId = s.hospitalCountryId;
    _selState     = s.hospitalState;
    _selStateId   = s.hospitalStateId;
    _selDistrict  = s.hospitalDistrict;
    _selDistrictId = s.hospitalDistrictId;
    _selCity      = s.hospitalCity;
    _selTimezone  = s.hospitalTimezone;
    // Billing
    _prefixCtrl.text = s.invoicePrefix;
    _taxCtrl.text    = s.taxPercentage == s.taxPercentage.roundToDouble()
        ? s.taxPercentage.toInt().toString()
        : s.taxPercentage.toString();
    // Print
    _headerNoteCtrl.text = s.printHeaderNote;
    _footerNoteCtrl.text = s.printFooterNote;
    // Queue
    _dilationCtrl.text = s.defaultDilationTime.toString();
    _waitCtrl['wait_green_max']!.text     = s.waitGreenMax.toString();
    _waitCtrl['wait_orange_max']!.text    = s.waitOrangeMax.toString();
    _waitCtrl['wait_red_max']!.text       = s.waitRedMax.toString();
    _waitCtrl['wait_d_green_max']!.text   = s.waitDGreenMax.toString();
    _waitCtrl['wait_d_orange_max']!.text  = s.waitDOrangeMax.toString();
    _waitCtrl['wait_d_red_max']!.text     = s.waitDRedMax.toString();
    _waitCtrl['wait_nd_green_max']!.text  = s.waitNdGreenMax.toString();
    _waitCtrl['wait_nd_orange_max']!.text = s.waitNdOrangeMax.toString();
    _waitCtrl['wait_nd_red_max']!.text    = s.waitNdRedMax.toString();
    // Other
    _letterPadAvailable = s.letterPadAvailable;
    _paginationLimit    = s.paginationLimit.toString();
  }

  void _markDirty() {
    if (_isDirty) return;
    setState(() => _isDirty = true);
  }

  void _markDirtyDebounced() {
    if (!_isDirty) _isDirty = true;
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 300), () {
      if (mounted) setState(() {});
    });
  }

  Future<void> _save() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    setState(() => _saving = true);
    try {
      final thresholds = <String, int>{};
      for (final e in _waitCtrl.entries) {
        final v = int.tryParse(e.value.text.trim());
        if (v != null && v > 0) thresholds[e.key] = v;
      }
      await SettingsService.instance.updateSettings(
        hospitalName:        _nameCtrl.text.trim(),
        hospitalEmail:       _emailCtrl.text.trim(),
        hospitalPhone:       _phoneCtrl.text.trim(),
        hospitalAddress:     _addressCtrl.text.trim(),
        hospitalCountry:     _selCountry,
        hospitalState:       _selState,
        hospitalDistrict:    _selDistrict,
        hospitalCity:        _selCity,
        hospitalTimezone:    _selTimezone,
        invoicePrefix:       _prefixCtrl.text.trim(),
        taxPercentage:       double.tryParse(_taxCtrl.text.trim()) ?? 0.0,
        letterPadAvailable:  _letterPadAvailable,
        printHeaderNote:     _headerNoteCtrl.text.trim(),
        printFooterNote:     _footerNoteCtrl.text.trim(),
        paginationLimit:     int.tryParse(_paginationLimit) ?? 25,
        defaultDilationTime: int.tryParse(_dilationCtrl.text.trim()) ?? 40,
        waitThresholds:      thresholds,
      );
      if (!mounted) return;
      setState(() { _isDirty = false; _saving = false; });
      showAppSnackBar(context, 'Settings saved successfully.', isSuccess: true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _saving = false);
      showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true, duration: const Duration(seconds: 4));
    }
  }

  // ── Logo upload ────────────────────────────────────────────────────────────

  Future<void> _pickAndUploadLogo() async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 85,
      maxWidth: 1024,
    );
    if (picked == null || !mounted) return;

    setState(() => _uploadingLogo = true);
    try {
      final url = await SettingsService.instance.uploadLogo(File(picked.path));
      if (!mounted) return;
      setState(() { _logoUrl = url; _uploadingLogo = false; });
      showAppSnackBar(context, 'Logo updated successfully.', isSuccess: true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _uploadingLogo = false);
      showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true, duration: const Duration(seconds: 4));
    }
  }

  // ── Location cascade pickers ───────────────────────────────────────────────

  Future<void> _pickCountry() async {
    final picked = await _showPicker<LocationCountry>(
      title: 'Select Country',
      loader: () async => _cachedCountries ??= await SettingsService.instance.fetchCountries(),
      label: (c) => c.name,
    );
    if (picked == null) return;
    setState(() {
      _selCountry    = picked.name;
      _selCountryId  = picked.id;
      // Auto-fill timezone from country default
      if (picked.defaultTimezone != null && picked.defaultTimezone!.isNotEmpty) {
        _selTimezone = picked.defaultTimezone!;
      }
      // Reset child fields
      _selState      = '';
      _selStateId    = null;
      _selDistrict   = '';
      _selDistrictId = null;
      _selCity       = '';
    });
    _markDirty();
  }

  Future<void> _pickState() async {
    if (_selCountry.isEmpty) {
      showAppSnackBar(context, 'Please select a country first.', isError: true);
      return;
    }
    // Lazily resolve country ID if settings were loaded without it
    int? cid = _selCountryId;
    if (cid == null) {
      try {
        _cachedCountries ??= await SettingsService.instance.fetchCountries();
        final countries = _cachedCountries!;
        final match = countries.where(
            (c) => c.name.trim().toLowerCase() == _selCountry.trim().toLowerCase()).firstOrNull;
        if (match != null) {
          _selCountryId = match.id;
          cid = match.id;
        }
      } catch (_) {}
      if (cid == null) {
        if (mounted) showAppSnackBar(context, 'Country not found — re-select it from the list.', isError: true);
        return;
      }
    }
    final int stateCid = cid;
    final picked = await _showPicker<LocationItem>(
      title: 'Select State',
      loader: () async => _cachedStates[stateCid] ??= await SettingsService.instance.fetchStates(stateCid),
      label: (s) => s.name,
    );
    if (picked == null) return;
    setState(() {
      _selState      = picked.name;
      _selStateId    = picked.id;
      _selDistrict   = '';
      _selDistrictId = null;
      _selCity       = '';
    });
    _markDirty();
  }

  Future<void> _pickDistrict() async {
    if (_selState.isEmpty) {
      showAppSnackBar(context, 'Please select a state first.', isError: true);
      return;
    }
    final sid = _selStateId;
    if (sid == null) {
      showAppSnackBar(context, 'Re-select the state to load districts.', isError: true);
      return;
    }
    final picked = await _showPicker<LocationItem>(
      title: 'Select District',
      loader: () async => _cachedDistricts[sid] ??= await SettingsService.instance.fetchDistricts(sid),
      label: (d) => d.name,
    );
    if (picked == null) return;
    setState(() {
      _selDistrict   = picked.name;
      _selDistrictId = picked.id;
      _selCity       = '';
    });
    _markDirty();
  }

  Future<void> _pickCity() async {
    if (_selDistrict.isEmpty) {
      showAppSnackBar(context, 'Please select a district first.', isError: true);
      return;
    }
    final did = _selDistrictId;
    if (did == null) {
      showAppSnackBar(context, 'Re-select the district to load cities.', isError: true);
      return;
    }
    final picked = await _showPicker<LocationItem>(
      title: 'Select City',
      loader: () async => _cachedCities[did] ??= await SettingsService.instance.fetchCities(did),
      label: (c) => c.name,
    );
    if (picked == null) return;
    setState(() => _selCity = picked.name);
    _markDirty();
  }

  Future<void> _pickTimezone() async {
    final picked = await _showPicker<LocationTimezone>(
      title: 'Select Timezone',
      loader: () async => _cachedTimezones ??= await SettingsService.instance.fetchTimezones(),
      label: (t) => '${t.offset}  ${t.label}',
    );
    if (picked == null) return;
    setState(() => _selTimezone = picked.value);
    _markDirty();
  }

  Future<T?> _showPicker<T>({
    required String title,
    required Future<List<T>> Function() loader,
    required String Function(T) label,
  }) {
    return showModalBottomSheet<T>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _PickerSheet<T>(title: title, loader: loader, label: label),
    );
  }

  // ── Build ──────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    if (!PermissionService.instance.can(Perm.settingsHospital)) {
      return Scaffold(
        backgroundColor: AppColors.backgroundAlt,
        appBar: AppBar(
          backgroundColor: AppColors.primary,
          elevation: 0,
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20),
            onPressed: () => Navigator.pop(context),
          ),
          title: const Text('Hospital Settings',
              style: TextStyle(color: Colors.white, fontSize: 18,
                  fontWeight: FontWeight.w800, letterSpacing: -0.2)),
        ),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 80, horizontal: 32),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.lock_outline_rounded, size: 56,
                    color: AppColors.primary.withValues(alpha: 0.25)),
                const SizedBox(height: 16),
                Text('No Access',
                    style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700,
                        color: AppColors.primary.withValues(alpha: 0.50))),
                const SizedBox(height: 6),
                Text('You do not have permission to view hospital settings.',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 13, color: AppColors.primary.withValues(alpha: 0.35))),
              ],
            ),
          ),
        ),
      );
    }
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: _buildAppBar(),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: AppColors.primary))
          : _error != null
              ? _buildError()
              : _buildBody(),
    );
  }

  AppBar _buildAppBar() {
    return AppBar(
      backgroundColor: AppColors.primary,
      elevation: 0,
      leading: IconButton(
        icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20),
        onPressed: () => Navigator.pop(context),
      ),
      title: const Text('Hospital Settings',
          style: TextStyle(color: Colors.white, fontSize: 18,
              fontWeight: FontWeight.w800, letterSpacing: -0.2)),
      actions: [
        Padding(
          padding: const EdgeInsets.only(right: 8),
          child: TextButton(
            onPressed: (_isDirty && !_saving) ? _save : null,
            child: _saving
                ? const SizedBox(width: 16, height: 16,
                    child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                : Text('Save', style: TextStyle(
                    color: _isDirty ? Colors.white : const Color(0x59FFFFFF),
                    fontSize: 14, fontWeight: FontWeight.w700)),
          ),
        ),
      ],
    );
  }

  Widget _buildError() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Icon(Icons.error_outline_rounded, size: 48, color: AppColors.waitRed.withValues(alpha: 0.60)),
          const SizedBox(height: 12),
          Text(_error!, textAlign: TextAlign.center,
              style: const TextStyle(color: Color(0xFF64748B))),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: _load,
            icon: const Icon(Icons.refresh_rounded),
            label: const Text('Retry'),
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white),
          ),
        ]),
      ),
    );
  }

  Widget _buildBody() {
    return Form(
      key: _formKey,
      child: Stack(
        children: [
          SingleChildScrollView(
            padding: const EdgeInsets.only(bottom: 110),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _buildHospitalHeader(),
                const SizedBox(height: 24),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _buildGeneralSection(),
                      const SizedBox(height: 24),
                      _buildLogoSection(),
                      const SizedBox(height: 24),
                      _buildLocationSection(),
                      const SizedBox(height: 24),
                      _buildBillingSection(),
                      const SizedBox(height: 24),
                      _buildPrintSection(),
                      const SizedBox(height: 24),
                      _buildQueueSection(),
                      const SizedBox(height: 24),
                      _buildSecuritySection(),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Positioned(bottom: 0, left: 0, right: 0, child: _buildSaveFooter()),
        ],
      ),
    );
  }

  // ── Hospital Header ────────────────────────────────────────────────────────

  Widget _buildHospitalHeader() {
    final s    = _settings!;
    final name = s.hospitalName.isNotEmpty ? s.hospitalName : widget.hospital.name;

    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft, end: Alignment.bottomRight,
          colors: [AppColors.primary, AppColors.blueLight],
        ),
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(AppRadius.xxl), bottomRight: Radius.circular(AppRadius.xxl)),
      ),
      padding: const EdgeInsets.fromLTRB(20, 24, 20, 28),
      child: Column(children: [
        // Logo or icon placeholder
        ClipRRect(
          borderRadius: BorderRadius.circular(14),
          child: _logoUrl != null && _logoUrl!.isNotEmpty
              ? Image.network(_logoUrl!, width: 72, height: 72, fit: BoxFit.contain,
                  cacheWidth: 144,
                  errorBuilder: (ctx, e, s) => _headerIconCircle())
              : _headerIconCircle(),
        ),
        const SizedBox(height: 14),
        Text(name, style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: Colors.white),
            textAlign: TextAlign.center),
        if (s.hospitalEmail.isNotEmpty) ...[
          const SizedBox(height: 6),
          Text(s.hospitalEmail, style: TextStyle(fontSize: 12, color: const Color(0xB3FFFFFF)),
              textAlign: TextAlign.center),
        ],
        if (s.hospitalPhone.isNotEmpty) ...[
          const SizedBox(height: 2),
          Text(s.hospitalPhone, style: TextStyle(fontSize: 12, color: const Color(0xB3FFFFFF))),
        ],
        const SizedBox(height: 14),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 5),
          decoration: BoxDecoration(
            color: const Color(0x33FFFFFF), borderRadius: BorderRadius.circular(AppRadius.xl)),
          child: const Text('HOSPITAL SETTINGS',
              style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700,
                  color: Colors.white, letterSpacing: 1.2)),
        ),
      ]),
    );
  }

  Widget _headerIconCircle() {
    return Container(
      width: 72, height: 72,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: const Color(0x33FFFFFF),
        border: Border.all(color: const Color(0x66FFFFFF), width: 2),
      ),
      child: const Icon(Icons.local_hospital_rounded, color: Colors.white, size: 34),
    );
  }

  // ── Component Helpers ──────────────────────────────────────────────────────

  Widget _sectionHeader(String title, IconData icon) {
    return Row(children: [
      Icon(icon, size: 18, color: AppColors.primaryA50),
      const SizedBox(width: 8),
      Text(title, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800,
          color: AppColors.primaryA50, letterSpacing: 1.4)),
    ]);
  }

  Widget _card(List<Widget> children) {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 16),
      decoration: BoxDecoration(
        color: Colors.white, borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.primaryA08),
        boxShadow: [BoxShadow(color: AppColors.primaryA05,
            blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: children),
    );
  }

  Widget _fieldRow({
    required String label,
    required TextEditingController ctrl,
    IconData? suffix,
    TextInputType? keyboardType,
    TextCapitalization textCapitalization = TextCapitalization.none,
    List<TextInputFormatter>? inputFormatters,
    String? Function(String?)? validator,
    int maxLines = 1,
    bool enabled = true,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800,
            color: AppColors.primaryA40, letterSpacing: 1.0)),
        const SizedBox(height: 4),
        Row(children: [
          Expanded(
            child: TextFormField(
              controller: ctrl,
              keyboardType: keyboardType,
              textCapitalization: textCapitalization,
              inputFormatters: inputFormatters,
              maxLines: maxLines,
              enabled: enabled,
              style: TextStyle(fontSize: 15, fontWeight: FontWeight.w600,
                  color: enabled ? const Color(0xFF1A3A52) : const Color(0x731A3A52)),
              decoration: const InputDecoration(border: InputBorder.none, isDense: true,
                  contentPadding: EdgeInsets.symmetric(vertical: 8)),
              onChanged: enabled ? (_) => _markDirty() : null,
              validator: validator,
            ),
          ),
          if (suffix != null) Icon(suffix, size: 20, color: AppColors.primaryA30),
        ]),
        Divider(height: 1, color: enabled ? AppColors.primaryA18 : AppColors.primaryA06),
      ],
    );
  }

  // Tappable picker row — for location cascade & timezone
  Widget _pickerRow({required String label, required String value, required VoidCallback onTap}) {
    return InkWell(
      onTap: onTap,
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(label, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800,
            color: AppColors.primaryA40, letterSpacing: 1.0)),
        const SizedBox(height: 4),
        Row(children: [
          Expanded(
            child: Text(value.isEmpty ? '— Select' : value,
                style: TextStyle(fontSize: 15, fontWeight: FontWeight.w600,
                    color: value.isEmpty ? AppColors.primaryA30 : const Color(0xFF1A3A52))),
          ),
          Icon(Icons.expand_more_rounded, size: 22, color: AppColors.primaryA40),
        ]),
        Divider(height: 1, color: AppColors.primaryA18),
      ]),
    );
  }

  // ── Sections ───────────────────────────────────────────────────────────────

  Widget _buildGeneralSection() {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      _sectionHeader('GENERAL INFORMATION', Icons.business_rounded),
      const SizedBox(height: 10),
      _card([
        _fieldRow(
          label: 'HOSPITAL NAME', ctrl: _nameCtrl, suffix: Icons.edit_outlined,
          textCapitalization: TextCapitalization.words,
          validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
        ),
        const SizedBox(height: 16),
        _fieldRow(
          label: 'EMAIL', ctrl: _emailCtrl, suffix: Icons.mail_outline_rounded,
          keyboardType: TextInputType.emailAddress,
          validator: (v) {
            if (v == null || v.trim().isEmpty) return 'Required';
            if (!RegExp(r'^[\w\.\+\-]+@[a-zA-Z0-9\-]+\.[a-zA-Z]{2,}$').hasMatch(v.trim())) {
              return 'Enter a valid email';
            }
            return null;
          },
        ),
        const SizedBox(height: 16),
        _fieldRow(
          label: 'PHONE', ctrl: _phoneCtrl, suffix: Icons.phone_outlined,
          keyboardType: TextInputType.phone,
          inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[\d\+\-\s\(\)]'))],
          validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
        ),
        const SizedBox(height: 16),
        _fieldRow(
          label: 'ADDRESS', ctrl: _addressCtrl, suffix: Icons.location_on_outlined,
          maxLines: 3, textCapitalization: TextCapitalization.sentences,
          validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
        ),
      ]),
    ]);
  }

  Widget _buildLogoSection() {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      _sectionHeader('HOSPITAL LOGO', Icons.image_rounded),
      const SizedBox(height: 10),
      _card([
        // Logo preview
        Center(
          child: _logoUrl != null && _logoUrl!.isNotEmpty
              ? ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: Image.network(
                    _logoUrl!, height: 90, fit: BoxFit.contain,
                    cacheWidth: 300, cacheHeight: 180,
                    loadingBuilder: (ctx, child, progress) => progress == null
                        ? child
                        : SizedBox(height: 90,
                            child: Center(child: CircularProgressIndicator(
                                color: AppColors.primaryA50, strokeWidth: 2))),
                    errorBuilder: (ctx, e, s) => _logoPlaceholder(),
                  ),
                )
              : _logoPlaceholder(),
        ),
        const SizedBox(height: 14),
        SizedBox(
          width: double.infinity,
          child: OutlinedButton.icon(
            onPressed: _uploadingLogo ? null : _pickAndUploadLogo,
            icon: _uploadingLogo
                ? SizedBox(width: 16, height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.primary))
                : const Icon(Icons.upload_rounded, size: 18),
            label: Text(_uploadingLogo ? 'Uploading...' : 'Change Logo'),
            style: OutlinedButton.styleFrom(
              foregroundColor: AppColors.primary,
              side: BorderSide(color: AppColors.primaryA30),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              padding: const EdgeInsets.symmetric(vertical: 10),
            ),
          ),
        ),
        const SizedBox(height: 6),
        Center(
          child: Text('JPG, PNG, WebP — max 2 MB',
              style: TextStyle(fontSize: 11, color: AppColors.primaryA38)),
        ),
      ]),
    ]);
  }

  Widget _logoPlaceholder() {
    return Container(
      width: 110, height: 80,
      decoration: BoxDecoration(
        color: AppColors.primaryA04,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.primaryA10),
      ),
      child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
        Icon(Icons.image_outlined, size: 28, color: AppColors.primaryA28),
        const SizedBox(height: 4),
        Text('No Logo', style: TextStyle(fontSize: 10, color: AppColors.primaryA35)),
      ]),
    );
  }

  Widget _buildLocationSection() {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      _sectionHeader('LOCATION', Icons.location_city_rounded),
      const SizedBox(height: 10),
      _card([
        _pickerRow(label: 'COUNTRY',  value: _selCountry,  onTap: _pickCountry),
        const SizedBox(height: 16),
        _pickerRow(label: 'STATE',    value: _selState,    onTap: _pickState),
        const SizedBox(height: 16),
        _pickerRow(label: 'DISTRICT', value: _selDistrict, onTap: _pickDistrict),
        const SizedBox(height: 16),
        _pickerRow(label: 'CITY',     value: _selCity,     onTap: _pickCity),
        const SizedBox(height: 16),
        _pickerRow(label: 'TIMEZONE', value: _selTimezone, onTap: _pickTimezone),
      ]),
      const SizedBox(height: 6),
      Padding(
        padding: const EdgeInsets.only(left: 2),
        child: Text(
          'Selecting a country auto-fills the timezone. Each field filters the next.',
          style: TextStyle(fontSize: 11, fontStyle: FontStyle.italic,
              color: AppColors.primaryA40),
        ),
      ),
    ]);
  }

  Widget _buildBillingSection() {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      _sectionHeader('BILLING', Icons.receipt_long_outlined),
      const SizedBox(height: 10),
      _card([
        _fieldRow(
          label: 'INVOICE PREFIX', ctrl: _prefixCtrl, suffix: Icons.tag_rounded,
          textCapitalization: TextCapitalization.characters,
          validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
        ),
        const SizedBox(height: 16),
        _fieldRow(
          label: 'TAX PERCENTAGE (%)', ctrl: _taxCtrl, suffix: Icons.percent_rounded,
          keyboardType: const TextInputType.numberWithOptions(decimal: true),
          inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[\d\.]'))],
          validator: (v) {
            if (v == null || v.trim().isEmpty) return 'Required';
            final n = double.tryParse(v.trim());
            if (n == null || n < 0 || n > 100) return 'Enter 0–100';
            return null;
          },
        ),
      ]),
    ]);
  }

  Widget _buildPrintSection() {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      _sectionHeader('PRINT SETTINGS', Icons.print_outlined),
      const SizedBox(height: 10),
      Container(
        padding: const EdgeInsets.fromLTRB(16, 14, 16, 16),
        decoration: BoxDecoration(
          color: Colors.white, borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppColors.primaryA08),
          boxShadow: [BoxShadow(color: AppColors.primaryA05, blurRadius: 8, offset: const Offset(0, 2))],
        ),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('LETTER PAD', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800,
              color: AppColors.primaryA40, letterSpacing: 1.0)),
          const SizedBox(height: 12),
          Row(children: [
            _letterPadOption(
              selected: !_letterPadAvailable, label: 'Unavailable',
              desc: 'Header prints hospital info & logo automatically',
              onTap: () { setState(() => _letterPadAvailable = false); _markDirty(); },
            ),
            const SizedBox(width: 10),
            _letterPadOption(
              selected: _letterPadAvailable, label: 'Available',
              desc: 'Physical letterhead — header appears in black',
              onTap: () { setState(() => _letterPadAvailable = true); _markDirty(); },
            ),
          ]),
          const SizedBox(height: 16),
          Divider(height: 1, color: AppColors.primaryA08),
          const SizedBox(height: 12),
          _fieldRow(label: 'PRINT HEADER NOTE', ctrl: _headerNoteCtrl, suffix: Icons.notes_rounded),
          const SizedBox(height: 16),
          _fieldRow(label: 'PRINT FOOTER NOTE', ctrl: _footerNoteCtrl, suffix: Icons.notes_rounded),
        ]),
      ),
    ]);
  }

  Widget _letterPadOption({
    required bool selected, required String label, required String desc, required VoidCallback onTap,
  }) {
    return Expanded(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(10),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            border: Border.all(
                color: selected ? AppColors.primary : AppColors.primaryA15, width: selected ? 2 : 1),
            borderRadius: BorderRadius.circular(10),
            color: selected ? AppColors.primaryA05 : Colors.transparent,
          ),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              Container(
                width: 14, height: 14,
                decoration: BoxDecoration(shape: BoxShape.circle,
                    border: Border.all(color: selected ? AppColors.primary : AppColors.primaryA30, width: 2)),
                child: selected ? Center(child: Container(width: 6, height: 6,
                    decoration: BoxDecoration(color: AppColors.primary, shape: BoxShape.circle))) : null,
              ),
              const SizedBox(width: 6),
              Text(label, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800,
                  color: selected ? AppColors.primary : AppColors.primaryA55)),
            ]),
            const SizedBox(height: 5),
            Text(desc, style: TextStyle(fontSize: 10, color: AppColors.primaryA45, height: 1.3),
                maxLines: 2, overflow: TextOverflow.ellipsis),
          ]),
        ),
      ),
    );
  }

  Widget _buildQueueSection() {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      _sectionHeader('QUEUE & WAIT THRESHOLDS', Icons.timer_outlined),
      const SizedBox(height: 10),
      _card([
        _fieldRow(
          label: 'DEFAULT DILATION TIME (MINUTES)', ctrl: _dilationCtrl,
          suffix: Icons.av_timer_rounded, keyboardType: TextInputType.number,
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
          validator: (v) {
            if (v != null && v.trim().isNotEmpty) {
              final n = int.tryParse(v.trim());
              if (n == null || n < 1 || n > 180) return '1–180 minutes';
            }
            return null;
          },
        ),
        const SizedBox(height: 6),
        Text(
          'Auto-filled in primary exam when doctor selects "Yes, Dilated". Doctor can still edit per patient.',
          style: TextStyle(fontSize: 11, color: AppColors.primaryA45, height: 1.4),
        ),
      ]),
      const SizedBox(height: 12),
      _waitGroup(badge: 'R',  label: 'Reception Entry', desc: 'Time since patient check-in',
          greenKey: 'wait_green_max',    orangeKey: 'wait_orange_max',    redKey: 'wait_red_max'),
      const SizedBox(height: 10),
      _waitGroup(badge: 'D',  label: 'Dilated',         desc: 'Post-primary exam — dilation = yes',
          greenKey: 'wait_d_green_max',  orangeKey: 'wait_d_orange_max',  redKey: 'wait_d_red_max'),
      const SizedBox(height: 10),
      _waitGroup(badge: 'ND', label: 'Not Dilated',     desc: 'Post-primary exam — dilation = no',
          greenKey: 'wait_nd_green_max', orangeKey: 'wait_nd_orange_max', redKey: 'wait_nd_red_max'),
    ]);
  }

  Widget _waitGroup({
    required String badge, required String label, required String desc,
    required String greenKey, required String orangeKey, required String redKey,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white, borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.primaryA08),
        boxShadow: [BoxShadow(color: AppColors.primaryA05, blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(color: AppColors.primaryA08, borderRadius: BorderRadius.circular(6)),
            child: Text(badge, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w900, color: AppColors.primary)),
          ),
          const SizedBox(width: 10),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(label, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Color(0xFF1A3A52))),
            Text(desc, style: TextStyle(fontSize: 10, color: AppColors.primaryA45)),
          ])),
        ]),
        const SizedBox(height: 14),
        Row(children: [
          _waitField('Green',  _waitCtrl[greenKey]!,  AppColors.waitGreen),
          const SizedBox(width: 6),
          _waitField('Orange', _waitCtrl[orangeKey]!, AppColors.waitOrange),
          const SizedBox(width: 6),
          _waitField('Red',    _waitCtrl[redKey]!,    AppColors.waitRed),
          const SizedBox(width: 6),
          _fireColumn(_waitCtrl[redKey]!),
        ]),
        const SizedBox(height: 12),
        _buildWaitPreview(greenKey, orangeKey, redKey, badge),
      ]),
    );
  }

  Widget _fireColumn(TextEditingController redCtrl) {
    final redVal = int.tryParse(redCtrl.text.trim()) ?? 0;
    return Expanded(
      child: Column(children: [
        Row(mainAxisAlignment: MainAxisAlignment.center, children: [
          Container(width: 8, height: 8, decoration: const BoxDecoration(color: Color(0xFF7C0000), shape: BoxShape.circle)),
          const SizedBox(width: 4),
          const Text('Fire', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFF7C0000))),
        ]),
        const SizedBox(height: 6),
        Container(
          height: 48, alignment: Alignment.center,
          decoration: BoxDecoration(
            color: const Color(0x0A7C0000), borderRadius: BorderRadius.circular(10),
            border: Border.all(color: const Color(0x2E7C0000)),
          ),
          child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
            Text('>$redVal m', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: Color(0xFF7C0000))),
            const Text('auto', style: TextStyle(fontSize: 8, color: Color(0xFF7C0000), fontWeight: FontWeight.w600)),
          ]),
        ),
      ]),
    );
  }

  Widget _waitField(String label, TextEditingController ctrl, Color color) {
    return Expanded(
      child: Column(children: [
        Row(mainAxisAlignment: MainAxisAlignment.center, children: [
          Container(width: 8, height: 8, decoration: BoxDecoration(color: color, shape: BoxShape.circle)),
          const SizedBox(width: 4),
          Text(label, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: AppColors.primaryA55)),
        ]),
        const SizedBox(height: 6),
        TextFormField(
          controller: ctrl,
          keyboardType: TextInputType.number,
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
          textAlign: TextAlign.center,
          style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: color),
          decoration: InputDecoration(
            isDense: true,
            contentPadding: const EdgeInsets.symmetric(horizontal: 6, vertical: 10),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10),
                borderSide: BorderSide(color: color.withValues(alpha: 0.25))),
            enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10),
                borderSide: BorderSide(color: color.withValues(alpha: 0.25))),
            focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10),
                borderSide: BorderSide(color: color, width: 2)),
            errorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10),
                borderSide: const BorderSide(color: Color(0xFFDC2626))),
            focusedErrorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10),
                borderSide: const BorderSide(color: Color(0xFFDC2626), width: 2)),
            filled: true, fillColor: color.withValues(alpha: 0.04),
            suffixText: 'm',
            suffixStyle: TextStyle(fontSize: 10, color: color.withValues(alpha: 0.7), fontWeight: FontWeight.w700),
            errorStyle: const TextStyle(fontSize: 9, height: 1.2),
          ),
          onChanged: (_) => _markDirtyDebounced(),
          validator: (v) {
            if (v != null && v.trim().isNotEmpty) {
              final n = int.tryParse(v.trim());
              if (n == null || n < 1) return '≥1';
            }
            return null;
          },
        ),
      ]),
    );
  }

  Widget _buildWaitPreview(String greenKey, String orangeKey, String redKey, String badge) {
    final g = int.tryParse(_waitCtrl[greenKey]!.text.trim())  ?? 0;
    final o = int.tryParse(_waitCtrl[orangeKey]!.text.trim()) ?? 0;
    final r = int.tryParse(_waitCtrl[redKey]!.text.trim())    ?? 0;

    return Container(
      padding: const EdgeInsets.fromLTRB(8, 10, 8, 8),
      decoration: BoxDecoration(
        color: AppColors.primaryA03, borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.primaryA07),
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text('PREVIEW', style: TextStyle(fontSize: 9, fontWeight: FontWeight.w800,
            color: AppColors.primaryA35, letterSpacing: 1.0)),
        const SizedBox(height: 6),
        Row(children: [
          _previewPill(badge, '0–${g}m',    AppColors.waitGreen),
          const SizedBox(width: 4),
          _previewPill(badge, '$g–${o}m',   AppColors.waitOrange),
          const SizedBox(width: 4),
          _previewPill(badge, '$o–${r}m',   AppColors.waitRed),
          const SizedBox(width: 4),
          _previewPill(badge, '${r}m+', const Color(0xFF7C0000), isFire: true),
        ]),
      ]),
    );
  }

  Widget _previewPill(String badge, String time, Color color, {bool isFire = false}) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 5),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.08), borderRadius: BorderRadius.circular(AppRadius.sm),
          border: Border.all(color: color.withValues(alpha: 0.20)),
        ),
        child: Row(mainAxisSize: MainAxisSize.min, mainAxisAlignment: MainAxisAlignment.center, children: [
          Container(
            width: 16, height: 16, alignment: Alignment.center,
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
            child: Text(isFire ? '🔥' : badge,
                style: const TextStyle(fontSize: 7, fontWeight: FontWeight.w900, color: Colors.white)),
          ),
          const SizedBox(width: 3),
          Flexible(child: Text(time,
              style: TextStyle(fontSize: 9, fontWeight: FontWeight.w700, color: color),
              overflow: TextOverflow.ellipsis)),
        ]),
      ),
    );
  }

  // ── Security section ───────────────────────────────────────────────────────

  Widget _buildSecuritySection() {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      _sectionHeader('SECURITY', Icons.lock_outline_rounded),
      const SizedBox(height: 10),
      _card([
        InkWell(
          onTap: _showChangePasswordSheet,
          borderRadius: BorderRadius.circular(10),
          child: Row(children: [
            Container(
              width: 38, height: 38,
              decoration: BoxDecoration(
                color: AppColors.primaryA08, borderRadius: BorderRadius.circular(10)),
              child: Icon(Icons.password_rounded, size: 20, color: AppColors.primary),
            ),
            const SizedBox(width: 12),
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              const Text('Change Password',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: Color(0xFF1A3A52))),
              Text('Update your account login password',
                  style: TextStyle(fontSize: 11, color: AppColors.primaryA45)),
            ])),
            Icon(Icons.chevron_right_rounded, color: AppColors.primaryA35),
          ]),
        ),
      ]),
    ]);
  }

  void _showChangePasswordSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _ChangePasswordSheet(onSuccess: () => showAppSnackBar(context, 'Password changed successfully.', isSuccess: true)),
    );
  }

  // ── Save Footer ────────────────────────────────────────────────────────────

  Widget _buildSaveFooter() {
    return Container(
      padding: EdgeInsets.fromLTRB(20, 16, 20, MediaQuery.of(context).padding.bottom + 16),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.xl)),
        boxShadow: [BoxShadow(color: Color(0x14000000), blurRadius: 16, offset: Offset(0, -4))],
      ),
      child: SizedBox(
        width: double.infinity, height: 52,
        child: ElevatedButton(
          onPressed: (_isDirty && !_saving) ? _save : null,
          style: ElevatedButton.styleFrom(
            backgroundColor: AppColors.primary,
            disabledBackgroundColor: AppColors.primaryA35,
            foregroundColor: Colors.white,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            elevation: 0,
          ),
          child: _saving
              ? const SizedBox(width: 20, height: 20,
                  child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
              : const Text('Save Changes', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
        ),
      ),
    );
  }
}

// ── Location Picker Bottom Sheet ──────────────────────────────────────────────

class _PickerSheet<T> extends StatefulWidget {
  final String title;
  final Future<List<T>> Function() loader;
  final String Function(T) label;

  const _PickerSheet({required this.title, required this.loader, required this.label});

  @override
  State<_PickerSheet<T>> createState() => _PickerSheetState<T>();
}

class _PickerSheetState<T> extends State<_PickerSheet<T>> {
  List<T>? _items;
  List<T> _filtered = [];
  bool _loading = true;
  String? _error;
  final _searchCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _searchCtrl.addListener(_filter);
    _loadData();
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    try {
      final items = await widget.loader();
      if (!mounted) return;
      setState(() { _items = items; _filtered = items; _loading = false; });
    } catch (e) {
      if (!mounted) return;
      setState(() { _loading = false; _error = e.toString().replaceFirst('Exception: ', ''); });
    }
  }

  void _filter() {
    final q = _searchCtrl.text.toLowerCase();
    setState(() {
      _filtered = (_items ?? []).where((i) => widget.label(i).toLowerCase().contains(q)).toList();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.78),
      decoration: const BoxDecoration(
        color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.xl))),
      child: Column(children: [
        Center(child: Container(
          margin: const EdgeInsets.only(top: 12, bottom: 4), width: 36, height: 4,
          decoration: BoxDecoration(color: AppColors.primaryA15, borderRadius: BorderRadius.circular(2)),
        )),
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
          child: Column(children: [
            Text(widget.title, style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.primary)),
            const SizedBox(height: 10),
            TextField(
              controller: _searchCtrl, autofocus: true,
              decoration: InputDecoration(
                hintText: 'Search...',
                hintStyle: TextStyle(color: AppColors.primaryA35),
                prefixIcon: Icon(Icons.search_rounded, color: AppColors.primaryA40, size: 20),
                filled: true, fillColor: AppColors.backgroundAlt,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                isDense: true, contentPadding: const EdgeInsets.symmetric(vertical: 10),
              ),
            ),
          ]),
        ),
        const SizedBox(height: 8),
        Divider(height: 1, color: AppColors.primaryA08),
        Flexible(
          child: _loading
              ? Padding(padding: EdgeInsets.all(40),
                  child: Center(child: CircularProgressIndicator(color: AppColors.primary)))
              : _error != null
                  ? Padding(padding: const EdgeInsets.all(32),
                      child: Text(_error!, textAlign: TextAlign.center,
                          style: const TextStyle(color: Color(0xFF64748B))))
                  : _filtered.isEmpty
                      ? Padding(padding: const EdgeInsets.all(32),
                          child: Text('No results found.',
                              textAlign: TextAlign.center,
                              style: TextStyle(color: AppColors.primaryA45)))
                      : ListView.builder(
                          itemCount: _filtered.length,
                          itemBuilder: (ctx, i) {
                            final item = _filtered[i];
                            return ListTile(
                              dense: true,
                              title: Text(widget.label(item),
                                  style: const TextStyle(fontSize: 14,
                                      fontWeight: FontWeight.w600, color: Color(0xFF1A3A52))),
                              onTap: () => Navigator.pop(ctx, item),
                            );
                          },
                        ),
        ),
      ]),
    );
  }
}

// ── Change Password Bottom Sheet ──────────────────────────────────────────────

class _ChangePasswordSheet extends StatefulWidget {
  final VoidCallback onSuccess;
  const _ChangePasswordSheet({required this.onSuccess});

  @override
  State<_ChangePasswordSheet> createState() => _ChangePasswordSheetState();
}

class _ChangePasswordSheetState extends State<_ChangePasswordSheet> {
  final _formKey       = GlobalKey<FormState>();
  final _currentCtrl   = TextEditingController();
  final _newCtrl       = TextEditingController();
  final _confirmCtrl   = TextEditingController();

  bool _savingPw        = false;
  bool _showCurrent     = false;
  bool _showNew         = false;
  bool _showConfirm     = false;

  @override
  void dispose() {
    _currentCtrl.dispose();
    _newCtrl.dispose();
    _confirmCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    setState(() => _savingPw = true);
    try {
      await SettingsService.instance.changePassword(
        currentPassword:          _currentCtrl.text,
        newPassword:              _newCtrl.text,
        newPasswordConfirmation:  _confirmCtrl.text,
      );
      if (!mounted) return;
      Navigator.pop(context);
      widget.onSuccess();
    } catch (e) {
      if (!mounted) return;
      setState(() => _savingPw = false);
      showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true, duration: const Duration(seconds: 4));
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return Container(
      padding: EdgeInsets.fromLTRB(20, 0, 20, bottom + 20),
      decoration: const BoxDecoration(
        color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.xl))),
      child: Form(
        key: _formKey,
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Center(child: Container(
            margin: const EdgeInsets.only(top: 12, bottom: 16), width: 36, height: 4,
            decoration: BoxDecoration(color: AppColors.primaryA15, borderRadius: BorderRadius.circular(2)),
          )),
          Row(children: [
            Container(
              width: 38, height: 38,
              decoration: BoxDecoration(color: AppColors.primaryA08, borderRadius: BorderRadius.circular(10)),
              child: Icon(Icons.password_rounded, size: 20, color: AppColors.primary),
            ),
            const SizedBox(width: 12),
            Text('Change Password', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.primary)),
          ]),
          const SizedBox(height: 20),
          _pwField('Current Password', _currentCtrl, _showCurrent,
              () => setState(() => _showCurrent = !_showCurrent),
              validator: (v) => (v == null || v.isEmpty) ? 'Required' : null),
          const SizedBox(height: 14),
          _pwField('New Password (min 8 chars)', _newCtrl, _showNew,
              () => setState(() => _showNew = !_showNew),
              validator: (v) {
                if (v == null || v.isEmpty) return 'Required';
                if (v.length < 8) return 'Minimum 8 characters';
                return null;
              }),
          const SizedBox(height: 14),
          _pwField('Confirm New Password', _confirmCtrl, _showConfirm,
              () => setState(() => _showConfirm = !_showConfirm),
              validator: (v) {
                if (v == null || v.isEmpty) return 'Required';
                if (v != _newCtrl.text) return 'Passwords do not match';
                return null;
              }),
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity, height: 50,
            child: ElevatedButton(
              onPressed: _savingPw ? null : _submit,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                disabledBackgroundColor: AppColors.primaryA35,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
                elevation: 0,
              ),
              child: _savingPw
                  ? const SizedBox(width: 20, height: 20,
                      child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                  : const Text('Update Password',
                      style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
            ),
          ),
        ]),
      ),
    );
  }

  Widget _pwField(String label, TextEditingController ctrl, bool show, VoidCallback toggleShow,
      {String? Function(String?)? validator}) {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(label, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800,
          color: AppColors.primaryA40, letterSpacing: 1.0)),
      const SizedBox(height: 4),
      Row(children: [
        Expanded(
          child: TextFormField(
            controller: ctrl,
            obscureText: !show,
            style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600, color: Color(0xFF1A3A52)),
            decoration: const InputDecoration(border: InputBorder.none, isDense: true,
                contentPadding: EdgeInsets.symmetric(vertical: 8)),
            validator: validator,
          ),
        ),
        GestureDetector(
          onTap: toggleShow,
          child: Icon(show ? Icons.visibility_off_outlined : Icons.visibility_outlined,
              size: 20, color: AppColors.primaryA35),
        ),
      ]),
      Divider(height: 1, color: AppColors.primaryA18),
    ]);
  }
}
