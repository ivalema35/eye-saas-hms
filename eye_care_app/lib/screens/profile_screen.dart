import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/auth_models.dart';
import '../services/onboarding_service.dart';
import '../services/profile_service.dart';
import '../widgets/widgets.dart';

class ProfileScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;

  const ProfileScreen({
    super.key,
    required this.user,
    required this.hospital,
  });

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  ProfileData? _profile;
  bool _loading = false;
  bool _saving  = false;
  String? _error;
  bool _isDirty  = false;
  bool _passwordExpanded = false;

  final _formKey = GlobalKey<FormState>();

  // Text controllers — pre-populated from widget.user for instant display
  late final TextEditingController _nameCtrl;
  late final TextEditingController _emailCtrl;
  final TextEditingController _regNoCtrl     = TextEditingController();
  final TextEditingController _expYearsCtrl  = TextEditingController();
  final TextEditingController _curPassCtrl   = TextEditingController();
  final TextEditingController _newPassCtrl   = TextEditingController();
  final TextEditingController _confPassCtrl  = TextEditingController();

  bool _showCurPass  = false;
  bool _showNewPass  = false;
  bool _showConfPass = false;

  @override
  void initState() {
    super.initState();
    _nameCtrl  = TextEditingController(text: widget.user.name);
    _emailCtrl = TextEditingController(text: widget.user.email);
    _load();
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _emailCtrl.dispose();
    _regNoCtrl.dispose();
    _expYearsCtrl.dispose();
    _curPassCtrl.dispose();
    _newPassCtrl.dispose();
    _confPassCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final profile = await ProfileService.instance.fetchProfile();
      if (!mounted) return;
      _nameCtrl.text  = profile.name;
      _emailCtrl.text = profile.email;
      if (profile.isDoctor) {
        _regNoCtrl.text    = profile.registrationNo ?? '';
        _expYearsCtrl.text = profile.experienceYears?.toString() ?? '';
      }
      setState(() { _profile = profile; _loading = false; _isDirty = false; });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error   = e.toString().replaceFirst('Exception: ', '');
        _loading = false;
      });
    }
  }

  void _markDirty() {
    if (!_isDirty) setState(() => _isDirty = true);
  }

  Future<void> _save() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;

    final hasPasswordChange = _newPassCtrl.text.isNotEmpty ||
        _curPassCtrl.text.isNotEmpty ||
        _confPassCtrl.text.isNotEmpty;

    if (hasPasswordChange) {
      if (_curPassCtrl.text.isEmpty) {
        _snack('Enter your current password to change it.', isError: true);
        return;
      }
      if (_newPassCtrl.text.length < 8) {
        _snack('New password must be at least 8 characters.', isError: true);
        return;
      }
      if (_newPassCtrl.text != _confPassCtrl.text) {
        _snack('New passwords do not match.', isError: true);
        return;
      }
    }

    setState(() => _saving = true);
    try {
      final isDoctor = _profile?.isDoctor ?? false;
      final regNo    = _regNoCtrl.text.trim();
      final expStr   = _expYearsCtrl.text.trim();

      final updated = await ProfileService.instance.updateProfile(
        name:            _nameCtrl.text.trim(),
        email:           _emailCtrl.text.trim(),
        currentPassword: hasPasswordChange ? _curPassCtrl.text : null,
        newPassword:     hasPasswordChange ? _newPassCtrl.text : null,
        registrationNo:  isDoctor ? (regNo.isEmpty ? null : regNo) : null,
        experienceYears: isDoctor && expStr.isNotEmpty ? int.tryParse(expStr) : null,
      );

      if (!mounted) return;
      _curPassCtrl.clear();
      _newPassCtrl.clear();
      _confPassCtrl.clear();

      setState(() {
        _profile           = updated;
        _saving            = false;
        _isDirty           = false;
        _passwordExpanded  = false;
      });
      _snack('Profile updated successfully.');
    } catch (e) {
      if (!mounted) return;
      setState(() => _saving = false);
      _snack(e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  void _snack(String msg, {bool isError = false}) {
    showAppSnackBar(
      context, msg,
      isError: isError,
      isSuccess: !isError,
      duration: Duration(milliseconds: isError ? 4000 : 2500),
    );
  }

  // ── Build ─────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
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
      title: const Text(
        'My Profile',
        style: TextStyle(
          color: Colors.white,
          fontSize: 18,
          fontWeight: FontWeight.w800,
          letterSpacing: -0.2,
        ),
      ),
      actions: [
        Padding(
          padding: const EdgeInsets.only(right: 8),
          child: TextButton(
            onPressed: (_isDirty && !_saving) ? _save : null,
            child: _saving
                ? const SizedBox(
                    width: 16, height: 16,
                    child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                  )
                : Text(
                    'Save',
                    style: TextStyle(
                      color: _isDirty ? Colors.white : Colors.white.withValues(alpha: 0.35),
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
          ),
        ),
      ],
    );
  }

  Widget _buildError() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.error_outline_rounded, size: 48,
                color: AppColors.red.withValues(alpha: 0.6)),
            const SizedBox(height: 12),
            Text(_error!, textAlign: TextAlign.center,
                style: const TextStyle(color: AppColors.textSecondary)),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: _load,
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('Retry'),
              style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary, foregroundColor: Colors.white),
            ),
          ],
        ),
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
                _buildProfileHeader(),
                const SizedBox(height: 24),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  child: Column(
                    children: [
                      _buildAccountSection(),
                      if (_profile?.isDoctor == true) ...[
                        const SizedBox(height: 24),
                        _buildDoctorSection(),
                      ],
                      const SizedBox(height: 24),
                      _buildSecuritySection(),
                      if (kDebugMode) ...[
                        const SizedBox(height: 24),
                        _buildDebugSection(),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
          Positioned(
            bottom: 0, left: 0, right: 0,
            child: _buildSaveFooter(),
          ),
        ],
      ),
    );
  }

  // ── Profile Header ────────────────────────────────────────────────────────

  Widget _buildProfileHeader() {
    final profile  = _profile;
    final name     = profile?.name ?? widget.user.name;
    final email    = profile?.email ?? widget.user.email;
    final contact  = profile?.contact ?? widget.user.contact;
    final role     = profile?.role;
    final roleName = role?.name ?? widget.user.role?.name;

    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppColors.primary, AppColors.blueLight],
        ),
        borderRadius: BorderRadius.only(
          bottomLeft:  Radius.circular(AppRadius.xxl),
          bottomRight: Radius.circular(AppRadius.xxl),
        ),
      ),
      padding: const EdgeInsets.fromLTRB(20, 24, 20, 28),
      child: Column(
        children: [
          // Avatar
          Container(
            width: 72, height: 72,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white.withValues(alpha: 0.20),
              border: Border.all(color: Colors.white.withValues(alpha: 0.40), width: 2),
            ),
            child: Text(
              _initials(name),
              style: const TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.w900,
                color: Colors.white,
              ),
            ),
          ),
          const SizedBox(height: 14),
          Text(
            name,
            style: const TextStyle(
              fontSize: 17,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 6),
          Text(email,
              style: TextStyle(fontSize: 12, color: Colors.white.withValues(alpha: 0.70))),
          if (contact != null && contact.isNotEmpty) ...[
            const SizedBox(height: 2),
            Text(contact,
                style: TextStyle(fontSize: 12, color: Colors.white.withValues(alpha: 0.70))),
          ],
          const SizedBox(height: 14),
          if (roleName != null)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 5),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.20),
                borderRadius: BorderRadius.circular(AppRadius.xl),
              ),
              child: Text(
                roleName.toUpperCase(),
                style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                  letterSpacing: 1.2,
                ),
              ),
            ),
        ],
      ),
    );
  }

  // ── Sections ──────────────────────────────────────────────────────────────

  Widget _buildAccountSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _sectionHeader('ACCOUNT INFORMATION', Icons.person_outline_rounded),
        const SizedBox(height: 10),
        _card([
          _fieldRow(
            label: 'FULL NAME',
            controller: _nameCtrl,
            suffixIcon: Icons.edit_outlined,
            textCapitalization: TextCapitalization.words,
            validator: (v) =>
                (v == null || v.trim().isEmpty) ? 'Name is required' : null,
          ),
          const SizedBox(height: 16),
          _fieldRow(
            label: 'EMAIL ADDRESS',
            controller: _emailCtrl,
            suffixIcon: Icons.mail_outline_rounded,
            keyboardType: TextInputType.emailAddress,
            validator: (v) =>
                (v == null || !v.contains('@')) ? 'Valid email required' : null,
          ),
        ]),
      ],
    );
  }

  Widget _buildDoctorSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _sectionHeader('DOCTOR INFORMATION', Icons.medical_services_outlined),
        const SizedBox(height: 10),
        _card([
          _fieldRow(
            label: 'REGISTRATION NO.',
            controller: _regNoCtrl,
            suffixIcon: Icons.badge_outlined,
            textCapitalization: TextCapitalization.characters,
          ),
          const SizedBox(height: 16),
          _fieldRow(
            label: 'EXPERIENCE (YEARS)',
            controller: _expYearsCtrl,
            suffixIcon: Icons.work_history_outlined,
            keyboardType: TextInputType.number,
            inputFormatters: [FilteringTextInputFormatter.digitsOnly],
            validator: (v) {
              if (v == null || v.isEmpty) return null;
              final n = int.tryParse(v);
              if (n == null || n < 0 || n > 60) return 'Enter 0–60';
              return null;
            },
          ),
        ]),
      ],
    );
  }

  Widget _buildSecuritySection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _sectionHeader('SECURITY', Icons.security_rounded),
        const SizedBox(height: 10),
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: AppColors.primaryA08),
            boxShadow: [
              BoxShadow(
                color: AppColors.primaryA05,
                blurRadius: 8,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Column(
            children: [
              // Header row
              InkWell(
                onTap: () => setState(() => _passwordExpanded = !_passwordExpanded),
                borderRadius: const BorderRadius.vertical(top: Radius.circular(14)),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Container(
                        width: 40, height: 40,
                        decoration: BoxDecoration(
                          color: AppColors.background,
                          borderRadius: BorderRadius.circular(AppRadius.md),
                        ),
                        alignment: Alignment.center,
                        child: Icon(Icons.lock_outline_rounded,
                            color: AppColors.primary, size: 20),
                      ),
                      const SizedBox(width: 14),
                      const Expanded(
                        child: Text(
                          'Change Password',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                            color: AppColors.darkNavy,
                          ),
                        ),
                      ),
                      AnimatedRotation(
                        turns: _passwordExpanded ? 0.5 : 0,
                        duration: const Duration(milliseconds: 250),
                        child: Icon(
                          Icons.expand_more_rounded,
                          size: 24,
                          color: AppColors.primaryA50,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              // Password fields (animated)
              AnimatedCrossFade(
                firstChild: const SizedBox.shrink(),
                secondChild: _buildPasswordFields(),
                crossFadeState: _passwordExpanded
                    ? CrossFadeState.showSecond
                    : CrossFadeState.showFirst,
                duration: const Duration(milliseconds: 250),
              ),
            ],
          ),
        ),
      ],
    );
  }

  // Debug-only — never rendered in a release build (see kDebugMode guard at
  // the call site). Lets a developer replay the one-time onboarding tour
  // without uninstalling the app.
  Widget _buildDebugSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _sectionHeader('DEVELOPER', Icons.bug_report_outlined),
        const SizedBox(height: 10),
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: AppColors.primaryA08),
            boxShadow: [BoxShadow(color: AppColors.primaryA05, blurRadius: 8, offset: const Offset(0, 2))],
          ),
          child: InkWell(
            borderRadius: BorderRadius.circular(14),
            onTap: () async {
              await OnboardingService.instance.resetForTesting();
              if (!mounted) return;
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Onboarding will show again on next app open.')),
              );
            },
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Container(
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(color: AppColors.background, borderRadius: BorderRadius.circular(AppRadius.md)),
                    alignment: Alignment.center,
                    child: Icon(Icons.replay_rounded, color: AppColors.primary, size: 20),
                  ),
                  const SizedBox(width: 14),
                  const Expanded(
                    child: Text('Reset Onboarding', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.darkNavy)),
                  ),
                  Icon(Icons.chevron_right_rounded, color: AppColors.primaryA50),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildPasswordFields() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
      child: Column(
        children: [
          Divider(height: 1, color: AppColors.primaryA08),
          const SizedBox(height: 16),
          _passwordField(
            label: 'CURRENT PASSWORD',
            controller: _curPassCtrl,
            isVisible: _showCurPass,
            onToggle: () => setState(() => _showCurPass = !_showCurPass),
          ),
          const SizedBox(height: 16),
          _passwordField(
            label: 'NEW PASSWORD',
            controller: _newPassCtrl,
            isVisible: _showNewPass,
            onToggle: () => setState(() => _showNewPass = !_showNewPass),
          ),
          const SizedBox(height: 16),
          _passwordField(
            label: 'CONFIRM NEW PASSWORD',
            controller: _confPassCtrl,
            isVisible: _showConfPass,
            onToggle: () => setState(() => _showConfPass = !_showConfPass),
          ),
        ],
      ),
    );
  }

  // ── Save Footer ───────────────────────────────────────────────────────────

  Widget _buildSaveFooter() {
    return Container(
      padding: EdgeInsets.fromLTRB(
          20, 16, 20, MediaQuery.of(context).padding.bottom + 16),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.xl)),
        boxShadow: [
          BoxShadow(
            color: Color(0x14000000),
            blurRadius: 16,
            offset: Offset(0, -4),
          ),
        ],
      ),
      child: SizedBox(
        width: double.infinity,
        height: 52,
        child: ElevatedButton(
          onPressed: (_isDirty && !_saving) ? _save : null,
          style: ElevatedButton.styleFrom(
            backgroundColor: AppColors.primary,
            disabledBackgroundColor: AppColors.primaryA35,
            foregroundColor: Colors.white,
            shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(14)),
            elevation: 0,
          ),
          child: _saving
              ? const SizedBox(
                  width: 20, height: 20,
                  child: CircularProgressIndicator(
                      color: Colors.white, strokeWidth: 2),
                )
              : const Text(
                  'Save Changes',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700),
                ),
        ),
      ),
    );
  }

  // ── Component Helpers ─────────────────────────────────────────────────────

  Widget _sectionHeader(String title, IconData icon) {
    return Row(
      children: [
        Icon(icon, size: 18, color: AppColors.primaryA50),
        const SizedBox(width: 8),
        Text(
          title,
          style: TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w800,
            color: AppColors.primaryA50,
            letterSpacing: 1.4,
          ),
        ),
      ],
    );
  }

  Widget _card(List<Widget> children) {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.primaryA08),
        boxShadow: [
          BoxShadow(
            color: AppColors.primaryA05,
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: children,
      ),
    );
  }

  Widget _fieldRow({
    required String label,
    required TextEditingController controller,
    IconData? suffixIcon,
    TextInputType? keyboardType,
    TextCapitalization textCapitalization = TextCapitalization.none,
    List<TextInputFormatter>? inputFormatters,
    String? Function(String?)? validator,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w800,
            color: AppColors.primaryA40,
            letterSpacing: 1.0,
          ),
        ),
        const SizedBox(height: 4),
        Row(
          children: [
            Expanded(
              child: TextFormField(
                controller: controller,
                keyboardType: keyboardType,
                textCapitalization: textCapitalization,
                inputFormatters: inputFormatters,
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w600,
                  color: AppColors.darkNavy,
                ),
                decoration: const InputDecoration(
                  border: InputBorder.none,
                  isDense: true,
                  contentPadding: EdgeInsets.symmetric(vertical: 8),
                ),
                validator: validator,
                onChanged: (_) => _markDirty(),
              ),
            ),
            if (suffixIcon != null)
              Icon(suffixIcon, size: 20,
                  color: AppColors.primary.withValues(alpha: 0.30)),
          ],
        ),
        Divider(height: 1, color: AppColors.primaryA18),
      ],
    );
  }

  Widget _passwordField({
    required String label,
    required TextEditingController controller,
    required bool isVisible,
    required VoidCallback onToggle,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w800,
            color: AppColors.primaryA40,
            letterSpacing: 1.0,
          ),
        ),
        const SizedBox(height: 4),
        Row(
          children: [
            Expanded(
              child: TextFormField(
                controller: controller,
                obscureText: !isVisible,
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w600,
                  color: AppColors.darkNavy,
                ),
                decoration: const InputDecoration(
                  border: InputBorder.none,
                  isDense: true,
                  contentPadding: EdgeInsets.symmetric(vertical: 8),
                  hintText: '••••••••',
                  hintStyle: TextStyle(fontSize: 15, color: Color(0xFFB0BEC5)),
                ),
                onChanged: (_) => _markDirty(),
              ),
            ),
            InkWell(
              onTap: onToggle,
              borderRadius: BorderRadius.circular(AppRadius.full),
              child: Padding(
                padding: const EdgeInsets.all(4),
                child: Icon(
                  isVisible
                      ? Icons.visibility_off_outlined
                      : Icons.visibility_outlined,
                  size: 20,
                  color: AppColors.primary.withValues(alpha: 0.30),
                ),
              ),
            ),
          ],
        ),
        Divider(height: 1, color: AppColors.primaryA18),
      ],
    );
  }

  // ── Static helpers ────────────────────────────────────────────────────────

  static String _initials(String name) {
    final parts     = name.trim().split(RegExp(r'\s+')).where((p) => p.isNotEmpty).toList();
    if (parts.isEmpty) return '?';
    // Skip honorifics like "Dr.", "Mr.", "Mrs.", "Ms."
    final words     = parts.where((p) => !(p.length <= 4 && p.endsWith('.'))).toList();
    final effective = words.isEmpty ? parts : words;
    if (effective.length == 1) {
      final w = effective[0];
      return w.length >= 2
          ? w.substring(0, 2).toUpperCase()
          : w[0].toUpperCase();
    }
    return '${effective[0][0]}${effective[1][0]}'.toUpperCase();
  }
}
