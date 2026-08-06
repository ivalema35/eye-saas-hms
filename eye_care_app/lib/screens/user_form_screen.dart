import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import '../models/auth_models.dart';
import '../services/user_service.dart';
import '../constants/app_colors.dart';
import '../utils/phone_rules.dart';
import '../constants/app_radius.dart';
import '../widgets/app_animations.dart';


class UserFormScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final HospitalUserModel? editUser;

  const UserFormScreen({
    super.key,
    required this.user,
    required this.hospital,
    this.editUser,
  });

  @override
  State<UserFormScreen> createState() => _UserFormScreenState();
}

class _UserFormScreenState extends State<UserFormScreen> {
  bool get _isEdit => widget.editUser != null;

  final _formKey = GlobalKey<FormState>();

  // Form controllers
  final _nameCtrl         = TextEditingController();
  final _emailCtrl        = TextEditingController();
  final _contactCtrl      = TextEditingController();
  final _passwordCtrl     = TextEditingController();
  final _passwordConfCtrl = TextEditingController();
  final _prefixCtrl       = TextEditingController();
  final _regNoCtrl        = TextEditingController();
  final _expYrsCtrl       = TextEditingController();

  // State
  String _status       = 'active';
  String _doctorType   = 'primary';
  bool   _focPerm      = false;
  bool   _showPassword = false;
  bool   _showConfirm  = false;
  bool   _saving       = false;
  bool   _saveSuccess  = false;

  // Role
  UserRole?          _selectedRole;
  List<UserRole>     _roles      = [];
  bool   _loadingForm = true;
  String? _formError;

  // Image files (newly picked)
  File? _signatureFile;
  File? _profilePhotoFile;
  // URLs of existing images (edit mode)
  String? _existingSignatureUrl;
  String? _existingPhotoUrl;
  bool _clearSignature   = false;
  bool _clearProfilePhoto = false;

  bool get _isDoctorRole => _selectedRole?.isDoctorRole == true;

  @override
  void initState() {
    super.initState();
    _loadFormData();
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _emailCtrl.dispose();
    _contactCtrl.dispose();
    _passwordCtrl.dispose();
    _passwordConfCtrl.dispose();
    _prefixCtrl.dispose();
    _regNoCtrl.dispose();
    _expYrsCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadFormData() async {
    try {
      final data = await UserService.instance.fetchFormData();
      if (!mounted) return;

      setState(() {
        _roles       = data.roles;
        _loadingForm = false;
      });

      if (_isEdit) _fillFromEdit();
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _formError   = e.toString().replaceFirst('Exception: ', '');
        _loadingForm = false;
      });
    }
  }

  void _fillFromEdit() {
    final u = widget.editUser!;
    _nameCtrl.text    = u.name;
    _emailCtrl.text   = u.email;
    _contactCtrl.text = u.contact;
    _status           = u.status;
    _focPerm          = u.focPermission;

    // Match role
    if (u.role != null) {
      _selectedRole = _roles.firstWhere(
        (r) => r.id == u.role!.id,
        orElse: () => u.role!,
      );
    }

    // Doctor fields
    _doctorType        = u.doctorType ?? 'primary';
    _prefixCtrl.text   = u.doctorPrefix ?? '';
    _regNoCtrl.text    = u.registrationNo ?? '';
    _expYrsCtrl.text   = u.experienceYears?.toString() ?? '';
    _existingSignatureUrl = u.signatureUrl;
    _existingPhotoUrl     = u.profilePhotoUrl;

    setState(() {});
  }

  // ── Image picking ─────────────────────────────────────────────────────────

  Future<void> _pickImage(bool isSignature) async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(
      source:       ImageSource.gallery,
      imageQuality: 30,
      maxWidth:     400,
    );
    if (picked == null) return;

    final file = File(picked.path);
    final size = await file.length();
    if (size > 20 * 1024 && mounted) {
      showAppSnackBar(context, 'Image must be under 20 KB. Please choose a smaller/more compressed image.');
      return;
    }

    setState(() {
      if (isSignature) {
        _signatureFile   = file;
        _clearSignature  = false;
      } else {
        _profilePhotoFile   = file;
        _clearProfilePhoto  = false;
      }
    });
  }

  // ── Save ──────────────────────────────────────────────────────────────────

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedRole == null) {
      showAppSnackBar(context, 'Please select a role.');
      return;
    }

    setState(() { _saving = true; _saveSuccess = false; });

    try {
      if (_isEdit) {
        await UserService.instance.updateUser(
          widget.editUser!.id,
          name:                 _nameCtrl.text.trim(),
          email:                _emailCtrl.text.trim(),
          contact:              _contactCtrl.text.trim(),
          roleId:               _selectedRole!.id,
          password:             _passwordCtrl.text.isNotEmpty
                                 ? _passwordCtrl.text
                                 : null,
          passwordConfirmation: _passwordCtrl.text.isNotEmpty
                                 ? _passwordConfCtrl.text
                                 : null,
          status:               _status,
          doctorType:           _isDoctorRole ? _doctorType : null,
          doctorPrefix:         _isDoctorRole && _prefixCtrl.text.trim().isNotEmpty
                                 ? _prefixCtrl.text.trim().toUpperCase()
                                 : null,
          registrationNo:       _isDoctorRole && _regNoCtrl.text.trim().isNotEmpty
                                 ? _regNoCtrl.text.trim()
                                 : null,
          experienceYears:      _isDoctorRole && _expYrsCtrl.text.isNotEmpty
                                 ? int.tryParse(_expYrsCtrl.text)
                                 : null,
          focPermission:        _isDoctorRole ? _focPerm : false,
          signature:            _signatureFile,
          clearSignature:       _clearSignature,
          profilePhoto:         _profilePhotoFile,
          clearProfilePhoto:    _clearProfilePhoto,
        );
      } else {
        await UserService.instance.createUser(
          name:                 _nameCtrl.text.trim(),
          email:                _emailCtrl.text.trim(),
          contact:              _contactCtrl.text.trim(),
          roleId:               _selectedRole!.id,
          password:             _passwordCtrl.text,
          passwordConfirmation: _passwordConfCtrl.text,
          status:               _status,
          doctorType:           _isDoctorRole ? _doctorType : null,
          doctorPrefix:         _isDoctorRole && _prefixCtrl.text.trim().isNotEmpty
                                 ? _prefixCtrl.text.trim().toUpperCase()
                                 : null,
          registrationNo:       _isDoctorRole && _regNoCtrl.text.trim().isNotEmpty
                                 ? _regNoCtrl.text.trim()
                                 : null,
          experienceYears:      _isDoctorRole && _expYrsCtrl.text.isNotEmpty
                                 ? int.tryParse(_expYrsCtrl.text)
                                 : null,
          focPermission:        _isDoctorRole ? _focPerm : false,
          signature:            _signatureFile,
          profilePhoto:         _profilePhotoFile,
        );
      }

      if (!mounted) return;
      setState(() { _saving = false; _saveSuccess = true; });
      await Future.delayed(const Duration(milliseconds: 700));
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _saving = false);
      showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  // ── Role picker ───────────────────────────────────────────────────────────

  void _pickRole() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _RolePickerSheet(
        roles:    _roles,
        selected: _selectedRole,
        onSelect: (r) {
          setState(() {
            _selectedRole = r;
            // If role changes to non-doctor, clear doctor type selection
            if (!r.isDoctorRole) _focPerm = false;
          });
          Navigator.pop(context);
        },
      ),
    );
  }

  // ── Build ─────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: Text(
          _isEdit ? 'Edit User' : 'Add User',
          style: const TextStyle(
            fontWeight: FontWeight.w900,
            fontSize: 18,
            color: Colors.white,
          ),
        ),
        actions: [
          if (!_loadingForm && _formError == null)
            Padding(
              padding: const EdgeInsets.only(right: 12),
              child: TextButton(
                onPressed: (_saving || _saveSuccess) ? null : _save,
                style: TextButton.styleFrom(
                  backgroundColor: _saveSuccess
                      ? AppColors.green.withValues(alpha: 0.9)
                      : const Color(0x26FFFFFF),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(AppRadius.md),
                  ),
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                ),
                child: AnimatedSwitcher(
                  duration: const Duration(milliseconds: 220),
                  child: _saveSuccess
                      ? const Icon(Icons.check_rounded,
                          color: Colors.white, size: 18, key: ValueKey('chk'))
                      : _saving
                          ? const SizedBox(
                              width: 16, height: 16, key: ValueKey('spin'),
                              child: CircularProgressIndicator(
                                  strokeWidth: 2, color: Colors.white))
                          : const Text('SAVE', key: ValueKey('txt'),
                              style: TextStyle(
                                fontWeight: FontWeight.w900,
                                fontSize: 13,
                                letterSpacing: 0.8,
                              )),
                ),
              ),
            ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_loadingForm) {
      return Center(child: CircularProgressIndicator(color: AppColors.primary));
    }

    if (_formError != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.error_outline_rounded, size: 52, color: Colors.red.shade300),
              const SizedBox(height: 16),
              Text(_formError!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: AppColors.textSecondary, fontSize: 14)),
              const SizedBox(height: 20),
              ElevatedButton.icon(
                onPressed: () {
                  setState(() { _loadingForm = true; _formError = null; });
                  _loadFormData();
                },
                icon: const Icon(Icons.refresh_rounded),
                label: const Text('Retry'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary, foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
              ),
            ],
          ),
        ),
      );
    }

    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
        children: [
          AnimatedListItem(key: const ValueKey('uf-basic'),    index: 0, child: _buildBasicInfo()),
          const SizedBox(height: 14),
          AnimatedListItem(key: const ValueKey('uf-role'),     index: 1, child: _buildRoleStatus()),
          const SizedBox(height: 14),
          AnimatedListItem(key: const ValueKey('uf-security'), index: 2, child: _buildSecurity()),
          const SizedBox(height: 14),
          AnimatedSize(
            duration: const Duration(milliseconds: 300),
            curve: Curves.easeInOut,
            child: _isDoctorRole
                ? Column(
                    children: [
                      _buildDoctorDetails(),
                      const SizedBox(height: 14),
                    ],
                  )
                : const SizedBox.shrink(),
          ),
          // Save button at bottom
          AnimatedListItem(
            key: const ValueKey('uf-save'),
            index: 3,
            child: SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: (_saving || _saveSuccess) ? null : _save,
                style: ElevatedButton.styleFrom(
                  backgroundColor: _saveSuccess ? AppColors.green : AppColors.primary,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(AppRadius.lg)),
                  elevation: 0,
                ),
                child: AnimatedSwitcher(
                  duration: const Duration(milliseconds: 220),
                  child: _saveSuccess
                      ? const Icon(Icons.check_rounded,
                          color: Colors.white, size: 24, key: ValueKey('chk'))
                      : _saving
                          ? const SizedBox(
                              width: 20, height: 20, key: ValueKey('spin'),
                              child: CircularProgressIndicator(
                                  strokeWidth: 2, color: Colors.white))
                          : Text(
                              _isEdit ? 'Update User' : 'Create User',
                              key: const ValueKey('txt'),
                              style: const TextStyle(
                                fontWeight: FontWeight.w900,
                                fontSize: 15,
                                letterSpacing: 0.5,
                              ),
                            ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ── BASIC INFORMATION section ─────────────────────────────────────────────

  Widget _buildBasicInfo() {
    return _SectionCard(
      header: _SectionHeader(
        icon: Icons.person_outline_rounded,
        label: 'BASIC INFORMATION',
      ),
      children: [
        _fieldLabel('Full Name *'),
        _styledField(
          controller: _nameCtrl,
          hint:       'Enter full name',
          validator:  (v) => (v == null || v.trim().isEmpty) ? 'Name is required' : null,
          textCapitalization: TextCapitalization.words,
        ),
        const SizedBox(height: 12),
        _fieldLabel('Email Address *'),
        _styledField(
          controller:   _emailCtrl,
          hint:         'Enter email address',
          keyboardType: TextInputType.emailAddress,
          validator: (v) {
            if (v == null || v.trim().isEmpty) return 'Email is required';
            if (!v.contains('@')) return 'Enter a valid email';
            return null;
          },
        ),
        const SizedBox(height: 12),
        _fieldLabel('Contact Number'),
        _styledField(
          controller:   _contactCtrl,
          hint:         'Contact number (e.g. +91XXXXXXXXXX)',
          keyboardType: TextInputType.phone,
          maxLength:    16,
          inputFormatters: [],
          validator:    PhoneRules.nullable,
        ),
      ],
    );
  }

  // ── ROLE & STATUS section ─────────────────────────────────────────────────

  Widget _buildRoleStatus() {
    return _SectionCard(
      header: _SectionHeader(
        icon: Icons.admin_panel_settings_rounded,
        label: 'ROLE & STATUS',
      ),
      children: [
        _fieldLabel('Role *'),
        GestureDetector(
          onTap: _pickRole,
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
            decoration: BoxDecoration(
              color: const Color(0xFFF4F7FB),
              borderRadius: BorderRadius.circular(AppRadius.md),
              border: Border.all(color: AppColors.primaryA12),
            ),
            child: Row(
              children: [
                if (_selectedRole != null) ...[
                  Container(
                    width: 10,
                    height: 10,
                    decoration: BoxDecoration(
                      color: _parseHexColor(_selectedRole!.color),
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      _selectedRole!.name,
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 14,
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                ] else ...[
                  Expanded(
                    child: Text(
                      'Select a role',
                      style: TextStyle(
                        fontSize: 14,
                        color: AppColors.textSecondary.withValues(alpha: 0.55),
                      ),
                    ),
                  ),
                ],
                Icon(Icons.expand_more_rounded,
                    size: 20, color: AppColors.textSecondary.withValues(alpha: 0.40)),
              ],
            ),
          ),
        ),
        if (_selectedRole?.isSuper == true) ...[
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.orange.shade50,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: Colors.orange.shade200),
            ),
            child: Row(
              children: [
                Icon(Icons.shield_rounded, size: 16, color: Colors.orange.shade700),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Super Admin role — bypasses all permission checks.',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: Colors.orange.shade700,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
        const SizedBox(height: 14),
        _fieldLabel('Status *'),
        Row(
          children: [
            Expanded(
              child: _StatusOption(
                label:    'Active',
                isActive: true,
                selected: _status == 'active',
                onTap:    () => setState(() => _status = 'active'),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _StatusOption(
                label:    'Inactive',
                isActive: false,
                selected: _status == 'inactive',
                onTap:    () => setState(() => _status = 'inactive'),
              ),
            ),
          ],
        ),
      ],
    );
  }

  // ── SECURITY section ──────────────────────────────────────────────────────

  Widget _buildSecurity() {
    return _SectionCard(
      header: _SectionHeader(
        icon: Icons.lock_outline_rounded,
        label: 'SECURITY',
      ),
      children: [
        _fieldLabel(_isEdit ? 'New Password (leave blank to keep)' : 'Password *'),
        TextFormField(
          controller:  _passwordCtrl,
          obscureText: !_showPassword,
          decoration: _fieldDeco(
            hint: _isEdit ? 'Enter new password to change' : 'Minimum 8 characters',
            suffix: IconButton(
              icon: Icon(
                _showPassword ? Icons.visibility_off_rounded : Icons.visibility_rounded,
                size: 18,
                color: AppColors.textSecondary.withValues(alpha: 0.50),
              ),
              onPressed: () => setState(() => _showPassword = !_showPassword),
            ),
          ),
          validator: (v) {
            if (!_isEdit && (v == null || v.isEmpty)) return 'Password is required';
            if (v != null && v.isNotEmpty && v.length < 8) {
              return 'Password must be at least 8 characters';
            }
            return null;
          },
        ),
        const SizedBox(height: 12),
        _fieldLabel(_isEdit ? 'Confirm New Password' : 'Confirm Password *'),
        TextFormField(
          controller:  _passwordConfCtrl,
          obscureText: !_showConfirm,
          decoration: _fieldDeco(
            hint: 'Re-enter password',
            suffix: IconButton(
              icon: Icon(
                _showConfirm ? Icons.visibility_off_rounded : Icons.visibility_rounded,
                size: 18,
                color: AppColors.textSecondary.withValues(alpha: 0.50),
              ),
              onPressed: () => setState(() => _showConfirm = !_showConfirm),
            ),
          ),
          validator: (v) {
            if (_passwordCtrl.text.isNotEmpty && v != _passwordCtrl.text) {
              return 'Passwords do not match';
            }
            if (!_isEdit && (v == null || v.isEmpty)) return 'Please confirm password';
            return null;
          },
        ),
      ],
    );
  }

  // ── DOCTOR DETAILS section ────────────────────────────────────────────────

  Widget _buildDoctorDetails() {
    return _SectionCard(
      header: _SectionHeader(
        icon: Icons.medical_services_rounded,
        label: 'DOCTOR DETAILS',
        badgeText: 'Auto-detected from role',
      ),
      children: [
        // Doctor Type
        _fieldLabel('Doctor Type'),
        Row(
          children: [
            Expanded(
              child: _DoctorTypeOption(
                label:    'Primary',
                subtitle: 'Ophthalmologist',
                selected: _doctorType == 'primary',
                onTap:    () => setState(() => _doctorType = 'primary'),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _DoctorTypeOption(
                label:    'Secondary',
                subtitle: 'Optometrist',
                selected: _doctorType == 'secondary',
                onTap:    () => setState(() => _doctorType = 'secondary'),
              ),
            ),
          ],
        ),
        const SizedBox(height: 14),
        // Doctor Prefix + Registration in row
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _fieldLabel('Prefix (2–5 letters)'),
                  _styledField(
                    controller:   _prefixCtrl,
                    hint:         'e.g. DR',
                    maxLength:    5,
                    inputFormatters: [
                      FilteringTextInputFormatter.allow(RegExp(r'[A-Za-z]')),
                      _UpperCaseFormatter(),
                    ],
                    validator: (v) {
                      if (v != null && v.isNotEmpty && v.length < 2) {
                        return 'Min 2 letters';
                      }
                      return null;
                    },
                  ),
                ],
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _fieldLabel('Exp. Years'),
                  _styledField(
                    controller:   _expYrsCtrl,
                    hint:         '0–60',
                    keyboardType: TextInputType.number,
                    maxLength:    2,
                    inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                    validator: (v) {
                      if (v == null || v.isEmpty) return null;
                      final n = int.tryParse(v);
                      if (n == null || n < 0 || n > 60) return '0–60';
                      return null;
                    },
                  ),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        _fieldLabel('Registration Number'),
        _styledField(
          controller: _regNoCtrl,
          hint:       'Medical council registration no.',
        ),
        const SizedBox(height: 14),
        // FOC Permission
        Container(
          decoration: BoxDecoration(
            color: const Color(0xFFF4F7FB),
            borderRadius: BorderRadius.circular(AppRadius.md),
            border: Border.all(color: AppColors.primaryA10),
          ),
          child: SwitchListTile(
            title: Text(
              'FOC Permission',
              style: TextStyle(
                fontWeight: FontWeight.w700,
                fontSize: 14,
                color: AppColors.primary,
              ),
            ),
            subtitle: Text(
              'Allow this doctor to mark visits as free of charge',
              style: TextStyle(
                fontSize: 11,
                color: AppColors.textSecondary.withValues(alpha: 0.75),
              ),
            ),
            value:      _focPerm,
            onChanged:  (v) => setState(() => _focPerm = v),
            activeThumbColor: AppColors.primary,
            dense: true,
            contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
          ),
        ),
        const SizedBox(height: 14),
        // Images row
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: _ImageUploadCard(
                label:       'Profile Photo',
                existingUrl: _clearProfilePhoto ? null : _existingPhotoUrl,
                pickedFile:  _profilePhotoFile,
                onPick:      () => _pickImage(false),
                onClear:     () => setState(() {
                  _profilePhotoFile   = null;
                  _existingPhotoUrl   = null;
                  _clearProfilePhoto  = true;
                }),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _ImageUploadCard(
                label:       'Signature',
                existingUrl: _clearSignature ? null : _existingSignatureUrl,
                pickedFile:  _signatureFile,
                onPick:      () => _pickImage(true),
                onClear:     () => setState(() {
                  _signatureFile    = null;
                  _existingSignatureUrl = null;
                  _clearSignature   = true;
                }),
              ),
            ),
          ],
        ),
        Container(
          margin: const EdgeInsets.only(top: 8),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          decoration: BoxDecoration(
            color: Colors.amber.shade50,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: Colors.amber.shade200),
          ),
          child: Row(
            children: [
              Icon(Icons.info_outline_rounded, size: 14, color: Colors.amber.shade700),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  'Images must be under 20 KB (JPG/PNG). Use low-resolution photos.',
                  style: TextStyle(
                    fontSize: 11,
                    color: Colors.amber.shade800,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  // ── Helpers ───────────────────────────────────────────────────────────────

  Widget _fieldLabel(String text) => Padding(
        padding: const EdgeInsets.only(bottom: 6),
        child: Text(
          text,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w700,
            color: AppColors.textSecondary.withValues(alpha: 0.80),
            letterSpacing: 0.2,
          ),
        ),
      );

  InputDecoration _fieldDeco({String? hint, Widget? suffix}) => InputDecoration(
        hintText:        hint,
        hintStyle:       TextStyle(
          color: AppColors.textSecondary.withValues(alpha: 0.40),
          fontSize: 13,
        ),
        filled:          true,
        fillColor:       const Color(0xFFF4F7FB),
        contentPadding:  const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        border:          OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide:   BorderSide(color: AppColors.primaryA12),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide:   BorderSide(color: AppColors.primaryA12),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide:   BorderSide(color: AppColors.primaryA50, width: 1.5),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide:   const BorderSide(color: Colors.red),
        ),
        suffixIcon:      suffix,
      );

  Widget _styledField({
    required TextEditingController controller,
    String?  hint,
    TextInputType? keyboardType,
    int?     maxLength,
    List<TextInputFormatter>? inputFormatters,
    String?  Function(String?)? validator,
    TextCapitalization textCapitalization = TextCapitalization.none,
  }) =>
      TextFormField(
        controller:          controller,
        keyboardType:        keyboardType,
        maxLength:           maxLength,
        inputFormatters:     inputFormatters,
        validator:           validator,
        textCapitalization:  textCapitalization,
        style: TextStyle(fontSize: 14, color: AppColors.primary, fontWeight: FontWeight.w600),
        decoration: _fieldDeco(hint: hint).copyWith(
          counterText: '',
        ),
      );

  Color _parseHexColor(String hex) {
    try {
      final clean = hex.replaceFirst('#', '');
      return Color(int.parse('FF$clean', radix: 16));
    } catch (_) {
      return AppColors.primary;
    }
  }
}

// ── Sub-widgets ───────────────────────────────────────────────────────────────

class _SectionCard extends StatelessWidget {
  final Widget header;
  final List<Widget> children;

  const _SectionCard({required this.header, required this.children});


  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.xl),
        border: Border.all(color: AppColors.primaryA07),
        boxShadow: [
          BoxShadow(
            color: AppColors.primaryA05,
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          header,
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: children,
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  final IconData icon;
  final String label;
  final String? badgeText;

  const _SectionHeader({required this.icon, required this.label, this.badgeText});


  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 12),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(7),
            decoration: BoxDecoration(
              color: AppColors.primaryA08,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, size: 16, color: AppColors.primary),
          ),
          const SizedBox(width: 10),
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w900,
              letterSpacing: 1.2,
              color: AppColors.primaryA75,
            ),
          ),
          if (badgeText != null) ...[
            const SizedBox(width: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
              decoration: BoxDecoration(
                color: Colors.blue.shade50,
                borderRadius: BorderRadius.circular(AppRadius.xl),
                border: Border.all(color: Colors.blue.shade200),
              ),
              child: Text(
                badgeText!,
                style: TextStyle(
                  fontSize: 9,
                  fontWeight: FontWeight.w700,
                  color: Colors.blue.shade700,
                ),
              ),
            ),
          ],
          const Spacer(),
          Divider(color: AppColors.textSecondary.withValues(alpha: 0.10)),
        ],
      ),
    );
  }
}

class _StatusOption extends StatelessWidget {
  final String label;
  final bool isActive;
  final bool selected;
  final VoidCallback onTap;

  const _StatusOption({
    required this.label,
    required this.isActive,
    required this.selected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final color  = isActive ? AppColors.green : Colors.grey.shade500;
    final bgColor = isActive ? Colors.green.shade50 : Colors.grey.shade50;

    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color:        selected ? bgColor : Colors.white,
          borderRadius: BorderRadius.circular(AppRadius.md),
          border: Border.all(
            color:  selected ? color : Colors.grey.shade200,
            width:  selected ? 2 : 1,
          ),
        ),
        alignment: Alignment.center,
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              selected ? Icons.radio_button_checked_rounded : Icons.radio_button_unchecked_rounded,
              size: 15,
              color: selected ? color : Colors.grey.shade400,
            ),
            const SizedBox(width: 6),
            Text(
              label,
              style: TextStyle(
                fontWeight: FontWeight.w700,
                fontSize: 13,
                color: selected ? color : Colors.grey.shade500,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DoctorTypeOption extends StatelessWidget {
  final String label;
  final String subtitle;
  final bool selected;
  final VoidCallback onTap;

  const _DoctorTypeOption({
    required this.label,
    required this.subtitle,
    required this.selected,
    required this.onTap,
  });


  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 10),
        decoration: BoxDecoration(
          color:        selected ? AppColors.primaryA07 : Colors.white,
          borderRadius: BorderRadius.circular(AppRadius.md),
          border: Border.all(
            color: selected ? AppColors.primaryA50 : Colors.grey.shade200,
            width: selected ? 1.8 : 1,
          ),
        ),
        child: Column(
          children: [
            Icon(
              selected ? Icons.radio_button_checked_rounded : Icons.radio_button_unchecked_rounded,
              size: 16,
              color: selected ? AppColors.primary : Colors.grey.shade400,
            ),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 12,
                color: selected ? AppColors.primary : Colors.grey.shade600,
              ),
            ),
            Text(
              subtitle,
              style: TextStyle(
                fontSize: 10,
                color: selected
                    ? AppColors.primaryA60
                    : Colors.grey.shade500,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ImageUploadCard extends StatelessWidget {
  final String label;
  final String? existingUrl;
  final File? pickedFile;
  final VoidCallback onPick;
  final VoidCallback onClear;

  const _ImageUploadCard({
    required this.label,
    this.existingUrl,
    this.pickedFile,
    required this.onPick,
    required this.onClear,
  });


  bool get _hasImage => pickedFile != null || (existingUrl != null && existingUrl!.isNotEmpty);

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w700,
            color: AppColors.textSecondary.withValues(alpha: 0.80),
          ),
        ),
        const SizedBox(height: 6),
        GestureDetector(
          onTap: onPick,
          child: Container(
            height: 100,
            decoration: BoxDecoration(
              color: const Color(0xFFF4F7FB),
              borderRadius: BorderRadius.circular(AppRadius.md),
              border: Border.all(
                color: _hasImage
                    ? AppColors.primaryA30
                    : AppColors.primaryA12,
                style: _hasImage ? BorderStyle.solid : BorderStyle.none,
              ),
            ),
            clipBehavior: Clip.antiAlias,
            child: _hasImage ? _imagePreview() : _emptyState(),
          ),
        ),
        if (_hasImage)
          Padding(
            padding: const EdgeInsets.only(top: 4),
            child: GestureDetector(
              onTap: onClear,
              child: Text(
                'Remove',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: Colors.red.shade400,
                ),
              ),
            ),
          ),
      ],
    );
  }

  Widget _imagePreview() {
    if (pickedFile != null) {
      return Stack(
        fit: StackFit.expand,
        children: [
          Image.file(pickedFile!, fit: BoxFit.cover),
          Positioned(
            top: 4,
            right: 4,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: BoxDecoration(
                color: const Color(0x99000000),
                borderRadius: BorderRadius.circular(AppRadius.sm),
              ),
              child: const Text('NEW',
                  style: TextStyle(
                      color: Colors.white,
                      fontSize: 9,
                      fontWeight: FontWeight.w800)),
            ),
          ),
        ],
      );
    }
    return Image.network(
      existingUrl!,
      fit: BoxFit.cover,
      errorBuilder: (ctx, e, s) => _emptyState(),
    );
  }

  Widget _emptyState() {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(Icons.add_photo_alternate_rounded,
            size: 28, color: AppColors.primaryA30),
        const SizedBox(height: 6),
        Text(
          'Tap to upload',
          style: TextStyle(
            fontSize: 11,
            color: AppColors.textSecondary.withValues(alpha: 0.55),
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }
}

// ── Role Picker Sheet ─────────────────────────────────────────────────────────

class _RolePickerSheet extends StatefulWidget {
  final List<UserRole> roles;
  final UserRole? selected;
  final void Function(UserRole) onSelect;

  const _RolePickerSheet({
    required this.roles,
    this.selected,
    required this.onSelect,
  });

  @override
  State<_RolePickerSheet> createState() => _RolePickerSheetState();
}

class _RolePickerSheetState extends State<_RolePickerSheet> {

  final _searchCtrl = TextEditingController();
  List<UserRole> _filtered = [];

  @override
  void initState() {
    super.initState();
    _filtered = widget.roles;
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  void _onSearch(String v) {
    setState(() {
      _filtered = v.isEmpty
          ? widget.roles
          : widget.roles
              .where((r) => r.name.toLowerCase().contains(v.toLowerCase()))
              .toList();
    });
  }

  Color _parseColor(String hex) {
    try {
      return Color(int.parse('FF${hex.replaceFirst('#', '')}', radix: 16));
    } catch (_) {
      return AppColors.primary;
    }
  }

  @override
  Widget build(BuildContext context) {
    return DraggableScrollableSheet(
      initialChildSize: 0.65,
      minChildSize:     0.40,
      maxChildSize:     0.90,
      builder: (ctx, sc) => Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: Column(
          children: [
            const SizedBox(height: 8),
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.grey.shade300,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 14, 20, 8),
              child: Row(
                children: [
                  Text(
                    'Select Role',
                    style: TextStyle(
                      fontWeight: FontWeight.w900,
                      fontSize: 17,
                      color: AppColors.primary,
                    ),
                  ),
                  const Spacer(),
                  IconButton(
                    icon: const Icon(Icons.close_rounded),
                    onPressed: () => Navigator.pop(context),
                    color: Colors.grey.shade400,
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: TextField(
                controller:  _searchCtrl,
                onChanged:   _onSearch,
                autofocus:   true,
                decoration: InputDecoration(
                  hintText: 'Search roles...',
                  hintStyle: TextStyle(
                    color: AppColors.textSecondary.withValues(alpha: 0.45),
                    fontSize: 13,
                  ),
                  prefixIcon: Icon(Icons.search_rounded,
                      size: 18, color: AppColors.textSecondary.withValues(alpha: 0.45)),
                  filled:     true,
                  fillColor:  const Color(0xFFF4F7FB),
                  contentPadding: const EdgeInsets.symmetric(vertical: 12),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14),
                    borderSide:   BorderSide.none,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 8),
            Expanded(
              child: ListView.builder(
                controller:  sc,
                padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                itemCount:   _filtered.length,
                itemBuilder: (_, i) {
                  final role    = _filtered[i];
                  final color   = _parseColor(role.color);
                  final isSelected = widget.selected?.id == role.id;

                  return ListTile(
                    onTap: () => widget.onSelect(role),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(AppRadius.md)),
                    tileColor: isSelected ? AppColors.primaryA06 : null,
                    leading: Container(
                      width: 36,
                      height: 36,
                      decoration: BoxDecoration(
                        color: color.withValues(alpha: 0.12),
                        shape: BoxShape.circle,
                        border: Border.all(color: color.withValues(alpha: 0.30)),
                      ),
                      child: Center(
                        child: Text(
                          role.name.isNotEmpty ? role.name[0].toUpperCase() : '?',
                          style: TextStyle(
                            fontWeight: FontWeight.w900,
                            color: color,
                            fontSize: 14,
                          ),
                        ),
                      ),
                    ),
                    title: Text(
                      role.name,
                      style: TextStyle(
                        fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                        color: isSelected ? AppColors.primary : Colors.black87,
                        fontSize: 14,
                      ),
                    ),
                    subtitle: Row(
                      children: [
                        if (role.isDoctorRole)
                          _roleBadge('Doctor', Colors.blue.shade700, Colors.blue.shade50),
                        if (role.isSuper)
                          _roleBadge('Super Admin', Colors.orange.shade700, Colors.orange.shade50),
                      ],
                    ),
                    trailing: isSelected
                        ? Icon(Icons.check_circle_rounded, color: AppColors.primary, size: 20)
                        : null,
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _roleBadge(String text, Color textColor, Color bgColor) => Container(
        margin: const EdgeInsets.only(right: 4, top: 2),
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
        decoration: BoxDecoration(
          color:        bgColor,
          borderRadius: BorderRadius.circular(AppRadius.xl),
        ),
        child: Text(
          text,
          style: TextStyle(
            fontSize: 9,
            fontWeight: FontWeight.w700,
            color: textColor,
          ),
        ),
      );
}

// ── Text formatter ────────────────────────────────────────────────────────────

class _UpperCaseFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(
          TextEditingValue oldValue, TextEditingValue newValue) =>
      newValue.copyWith(text: newValue.text.toUpperCase());
}
