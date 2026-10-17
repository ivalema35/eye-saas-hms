import 'package:flutter/material.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_refresh_indicator.dart';
import '../utils/app_route.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/permissions.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/skeleton.dart';
import '../models/auth_models.dart';
import '../models/role_models.dart';
import '../services/permission_service.dart';
import '../services/roles_service.dart';
import '../widgets/permission_tree_editor.dart';
import '../widgets/app_text_field.dart';

// ─────────────────────────────────────────────────────────────────────────────
// Roles list screen
// ─────────────────────────────────────────────────────────────────────────────

class RolesScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final VoidCallback? onMenuTap;

  const RolesScreen({
    super.key,
    required this.user,
    required this.hospital,
    this.onMenuTap,
  });

  @override
  State<RolesScreen> createState() => _RolesScreenState();
}

class _RolesScreenState extends State<RolesScreen> {
  final _p = PermissionService.instance;

  bool _loading = false;
  String? _error;
  List<RoleModel> _roles = [];

  bool get _canAdd => _p.can(Perm.masterRoleAdd);
  bool get _canView => _p.can(Perm.masterRoles);
  bool get _canEdit => _p.can(Perm.masterRoleEdit);
  bool get _canDelete => _p.can(Perm.masterRoleDelete);

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final roles = await RolesService.instance.fetchRoles();
      if (mounted)
        setState(() {
          _roles = roles;
          _loading = false;
        });
    } catch (e) {
      if (mounted)
        setState(() {
          _error = e.toString();
          _loading = false;
        });
    }
  }

  Future<void> _confirmDelete(RoleModel role) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
        ),
        title: const Text('Delete Role'),
        content: Text('Delete role "${role.name}"? This cannot be undone.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.red),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    try {
      await RolesService.instance.deleteRole(role.id);
      _load();
      if (mounted) {
        showAppSnackBar(
          context,
          'Role "${role.name}" deleted.',
          isSuccess: true,
        );
      }
    } catch (e) {
      if (mounted) {
        showAppSnackBar(context, e.toString(), isError: true);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F8FA),
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Roles & Permissions',
              style: TextStyle(fontWeight: FontWeight.w700, fontSize: 17),
            ),
            Text(
              widget.hospital.name,
              style: const TextStyle(fontSize: 11, color: Colors.white70),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'Refresh',
            onPressed: _load,
          ),
        ],
        leading: widget.onMenuTap != null
            ? IconButton(
                icon: const Icon(Icons.menu),
                onPressed: widget.onMenuTap,
              )
            : null,
      ),
      floatingActionButton: _canAdd
          ? FloatingActionButton.extended(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              icon: const Icon(Icons.add),
              label: const Text('New Role'),
              onPressed: () => _openRoleEditor(context, null),
            )
          : null,
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const AppSkeletonList();
    }
    if (_error != null) {
      return AppErrorState(message: _error!, onRetry: _load);
    }
    if (_roles.isEmpty) {
      return const AppEmptyState(message: 'No roles found.');
    }

    return AppRefreshIndicator(
      onRefresh: _load,
      child: ListView.builder(
        padding: const EdgeInsets.fromLTRB(12, 8, 12, 88),
        itemCount: _roles.length,
        itemBuilder: (_, i) =>
            AnimatedListItem(index: i, child: _buildRoleCard(_roles[i])),
      ),
    );
  }

  Widget _buildRoleCard(RoleModel role) {
    Color roleColor;
    try {
      final hex = role.color.replaceFirst('#', '');
      roleColor = Color(int.parse('FF$hex', radix: 16));
    } catch (_) {
      roleColor = AppColors.primary;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      elevation: 1.5,
      child: InkWell(
        borderRadius: BorderRadius.circular(10),
        onTap: (_canView || _canEdit)
            ? () => _openRoleEditor(context, role)
            : null,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            children: [
              // Color dot
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: roleColor.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.shield_outlined, color: roleColor, size: 20),
              ),
              const SizedBox(width: 12),
              // Role info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Wrap(
                      crossAxisAlignment: WrapCrossAlignment.center,
                      spacing: 6,
                      runSpacing: 4,
                      children: [
                        Text(
                          role.name,
                          style: TextStyle(
                            fontWeight: FontWeight.w700,
                            fontSize: 15,
                            color: AppColors.primary,
                          ),
                        ),
                        if (role.isSuper) _badge('Super Admin', AppColors.teal),
                        if (role.isSystem) _badge('System', Colors.grey),
                      ],
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '${role.usersCount} user${role.usersCount == 1 ? '' : 's'}',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey.shade600,
                      ),
                    ),
                    if (role.description != null &&
                        role.description!.isNotEmpty)
                      Padding(
                        padding: const EdgeInsets.only(top: 4),
                        child: Text(
                          role.description!,
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey.shade700,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                  ],
                ),
              ),
              // Actions
              if (_canEdit || _canDelete)
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    if (_canEdit)
                      IconButton(
                        icon: const Icon(Icons.edit_outlined, size: 20),
                        color: AppColors.primary,
                        tooltip: 'Edit',
                        onPressed: () => _openRoleEditor(context, role),
                      ),
                    if (_canDelete && role.isDeletable)
                      IconButton(
                        icon: const Icon(Icons.delete_outline, size: 20),
                        color: AppColors.red,
                        tooltip: 'Delete',
                        onPressed: () => _confirmDelete(role),
                      ),
                  ],
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _badge(String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }

  Future<void> _openRoleEditor(
    BuildContext context,
    RoleModel? existing,
  ) async {
    final result = await Navigator.push<bool>(
      context,
      appRoute(
        RoleEditorScreen(
          hospital: widget.hospital,
          existing: existing,
          readOnly: existing != null && !_canEdit,
        ),
      ),
    );
    if (result == true) _load();
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Role editor — create or edit with permission checkboxes
// ─────────────────────────────────────────────────────────────────────────────

class RoleEditorScreen extends StatefulWidget {
  final HospitalInfo hospital;
  final RoleModel? existing;
  final bool readOnly;

  const RoleEditorScreen({
    super.key,
    required this.hospital,
    this.existing,
    this.readOnly = false,
  });

  @override
  State<RoleEditorScreen> createState() => _RoleEditorScreenState();
}

class _RoleEditorScreenState extends State<RoleEditorScreen> {
  // Web's default new-role color (`create.blade.php`'s native color input
  // default) — matches exactly so a brand-new role's color isn't an
  // arbitrary Flutter-side pick. Same palette/dialog as eye_care_tab's
  // equivalent (roles_screen.dart), ported so both apps behave identically
  // — mobile's own list/editor visual style otherwise stays as-is.
  static const _defaultColor = '#1B4F72';

  static const _colorPalette = [
    '#1B4F72',
    '#154360',
    '#2471A3',
    '#2980B9',
    '#006497',
    '#1ABC9C',
    '#0E9E82',
    '#27AE60',
    '#16A34A',
    '#E67E22',
    '#EA580C',
    '#DC3545',
    '#C0392B',
    '#8E44AD',
    '#7D3C98',
    '#2C3E50',
    '#7F8C8D',
  ];

  final _formKey = GlobalKey<FormState>();
  final _nameCtrl = TextEditingController();
  final _descCtrl = TextEditingController();
  late String _colorHex;

  bool _loading = true;
  bool _saving = false;
  String? _error;

  // Permissions: module → feature → list of items (mutable, isGranted toggled by user)
  Map<String, PermissionModuleGroup> _permissions = {};

  bool get _isEdit => widget.existing != null;
  bool get _isSuper => widget.existing?.isSuper ?? false;
  bool get _isSystem => widget.existing?.isSystem ?? false;

  @override
  void initState() {
    super.initState();
    _colorHex = widget.existing?.color ?? _defaultColor;
    if (_isEdit) {
      _nameCtrl.text = widget.existing!.name;
      _descCtrl.text = widget.existing!.description ?? '';
    }
    _loadPermissions();
  }

  Color _hexToColor(String hex) {
    try {
      return Color(int.parse('FF${hex.replaceFirst('#', '')}', radix: 16));
    } catch (_) {
      return AppColors.primary;
    }
  }

  Future<void> _pickColor() async {
    String draft = _colorHex;
    final hexCtrl = TextEditingController(
      text: _colorHex.replaceFirst('#', ''),
    );
    final picked = await showDialog<String>(
      context: context,
      builder: (dCtx) => StatefulBuilder(
        builder: (dCtx, ss) {
          return AlertDialog(
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(AppRadius.lg),
            ),
            title: const Text('Role Color'),
            content: SizedBox(
              width: 320,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Wrap(
                    spacing: 10,
                    runSpacing: 10,
                    children: _colorPalette.map((hex) {
                      final selected = draft.toUpperCase() == hex.toUpperCase();
                      return GestureDetector(
                        onTap: () => ss(() {
                          draft = hex;
                          hexCtrl.text = hex.replaceFirst('#', '');
                        }),
                        child: Container(
                          width: 34,
                          height: 34,
                          decoration: BoxDecoration(
                            color: _hexToColor(hex),
                            shape: BoxShape.circle,
                            border: Border.all(
                              color: selected
                                  ? AppColors.primary
                                  : Colors.transparent,
                              width: 3,
                            ),
                          ),
                          child: selected
                              ? const Icon(
                                  Icons.check_rounded,
                                  color: Colors.white,
                                  size: 16,
                                )
                              : null,
                        ),
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 16),
                  TextField(
                    controller: hexCtrl,
                    maxLength: 6,
                    decoration: const InputDecoration(
                      labelText: 'Custom hex',
                      prefixText: '#',
                      border: OutlineInputBorder(),
                      counterText: '',
                    ),
                    onChanged: (v) => draft = '#$v',
                  ),
                ],
              ),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(dCtx),
                child: const Text('Cancel'),
              ),
              ElevatedButton(
                onPressed: () => Navigator.pop(dCtx, draft),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  foregroundColor: Colors.white,
                ),
                child: const Text('Select'),
              ),
            ],
          );
        },
      ),
    );
    if (picked != null && mounted) setState(() => _colorHex = picked);
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _descCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadPermissions() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      if (_isEdit) {
        // Load role with its current permission assignments
        final role = await RolesService.instance.fetchRole(widget.existing!.id);
        if (mounted)
          setState(() {
            _permissions = _deepCopy(role.permissions);
            _loading = false;
          });
      } else {
        // Load all available permissions (none granted yet)
        final all = await RolesService.instance.fetchAllPermissions();
        final perms = _deepCopy(all.modules);
        _applyNewRoleDefaults(perms);
        if (mounted)
          setState(() {
            _permissions = perms;
            _loading = false;
          });
      }
    } catch (e) {
      if (mounted)
        setState(() {
          _error = e.toString();
          _loading = false;
        });
    }
  }

  Map<String, PermissionModuleGroup> _deepCopy(
    Map<String, PermissionModuleGroup> src,
  ) {
    return {
      for (final me in src.entries)
        me.key: PermissionModuleGroup(
          label: me.value.label,
          features: {
            for (final fe in me.value.features.entries)
              fe.key: PermissionFeatureGroup(
                label: fe.value.label,
                permissions: fe.value.permissions
                    .map((p) => p.copyWith())
                    .toList(),
              ),
          },
        ),
    };
  }

  /// Mirrors the web Role-create form's default: Dashboard widgets start
  /// checked for a brand-new role, everything else unchecked.
  void _applyNewRoleDefaults(Map<String, PermissionModuleGroup> perms) {
    final dashboard = perms['dashboard'];
    if (dashboard == null) return;
    for (final feature in dashboard.features.values) {
      for (var i = 0; i < feature.permissions.length; i++) {
        feature.permissions[i] = feature.permissions[i].copyWith(
          isGranted: true,
        );
      }
    }
  }

  List<int> get _grantedIds => _permissions.values
      .expand((m) => m.allPermissions)
      .where((p) => p.isGranted)
      .map((p) => p.id)
      .toList();

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);

    try {
      if (_isEdit) {
        // Web parity: system roles lock the NAME only — color stays
        // editable regardless of is_system (`edit.blade.php`'s color
        // <input> has no readonly/disabled condition tied to is_system).
        await RolesService.instance.updateRole(
          id: widget.existing!.id,
          name: _isSystem ? null : _nameCtrl.text.trim(),
          color: _colorHex,
          description: _descCtrl.text.trim(),
          permissionIds: _isSuper ? null : _grantedIds,
        );
      } else {
        await RolesService.instance.createRole(
          name: _nameCtrl.text.trim(),
          color: _colorHex,
          description: _descCtrl.text.trim(),
          permissionIds: _grantedIds,
        );
      }
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        showAppSnackBar(context, e.toString(), isError: true);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F8FA),
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        title: Text(
          widget.readOnly ? 'View Role' : (_isEdit ? 'Edit Role' : 'New Role'),
          style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 17),
        ),
        actions: [
          if (!_loading && !widget.readOnly)
            TextButton(
              onPressed: _saving ? null : _save,
              child: _saving
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Text(
                      'Save',
                      style: TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
            ),
        ],
      ),
      body: _loading
          ? const AppSkeletonList()
          : _error != null
          ? AppErrorState(message: _error!, onRetry: _loadPermissions)
          : Form(
              key: _formKey,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  _buildNameCard(),
                  const SizedBox(height: 16),
                  if (!_isSuper)
                    PermissionTreeEditor(
                      permissions: _permissions,
                      enabled: !widget.readOnly,
                    ),
                  if (_isSuper)
                    Card(
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Row(
                          children: [
                            const Icon(
                              Icons.verified_user,
                              color: AppColors.teal,
                              size: 28,
                            ),
                            const SizedBox(width: 12),
                            const Expanded(
                              child: Text(
                                'Super Admin bypasses all permission checks — '
                                'permissions cannot be edited for this role.',
                                style: TextStyle(color: Colors.grey),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                ],
              ),
            ),
    );
  }

  // Client request (2026-10-04): this card was a bare Material `Card` +
  // stock `TextFormField`s (plain `OutlineInputBorder`, no fill color, no
  // icon badge) — one of the few screens left in this redesign pass that
  // never picked up the app's own field styling at all. Field order already
  // matched web (Name → Color → Description, confirmed against
  // `roles/create.blade.php`), so only the chrome changes here, not the
  // sequence. `PermissionTreeEditor` below is its own, already-built
  // component — untouched.
  Widget _buildNameCard() {
    return AppFormCard(
      icon: Icons.badge_outlined,
      title: 'Role Details',
      trailing: _isEdit
          ? Wrap(
              spacing: 6,
              runSpacing: 6,
              alignment: WrapAlignment.end,
              children: [
                if (_isSystem)
                  _headerBadge('SYSTEM ROLE', Colors.grey.shade600),
                if (_isSuper)
                  _headerBadge('SUPER — ALL PERMISSIONS', AppColors.orange),
                _headerBadge(
                  '${widget.existing!.usersCount} USER(S)',
                  AppColors.blue,
                ),
              ],
            )
          : null,
      children: [
        AppTextField(
          label: 'Role Name',
          required: true,
          controller: _nameCtrl,
          hint: 'e.g. Nurse',
          enabled: !widget.readOnly && !_isSystem,
          validator: (v) =>
              (v == null || v.trim().isEmpty) ? 'Role name is required' : null,
        ),
        if (_isSystem)
          const Padding(
            padding: EdgeInsets.only(top: 4),
            child: Text(
              'System role name cannot be changed.',
              style: TextStyle(fontSize: 11, color: AppColors.textSecondary),
            ),
          ),
        _buildColorField(),
        AppTextField(
          label: 'Description',
          controller: _descCtrl,
          hint: 'Short description (optional)',
          readOnly: widget.readOnly,
          maxLines: 2,
        ),
      ],
    );
  }

  Widget _headerBadge(String label, Color color) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
    decoration: BoxDecoration(
      color: color.withValues(alpha: 0.12),
      borderRadius: BorderRadius.circular(999),
    ),
    child: Text(
      label,
      style: TextStyle(
        fontSize: 10,
        fontWeight: FontWeight.w800,
        color: color,
        letterSpacing: 0.3,
      ),
    ),
  );

  Widget _buildColorField() {
    final swatch = _hexToColor(_colorHex);
    final labelColor = swatch.computeLuminance() > 0.5
        ? Colors.black87
        : Colors.white;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.only(bottom: 6, left: 2),
          child: Text(
            'Color',
            style: TextStyle(
              fontSize: 12.5,
              fontWeight: FontWeight.w700,
              color: AppColors.textPrimary,
            ),
          ),
        ),
        InkWell(
          onTap: widget.readOnly ? null : _pickColor,
          borderRadius: BorderRadius.circular(AppRadius.md),
          child: Container(
            height: 48,
            width: double.infinity,
            decoration: BoxDecoration(
              color: swatch,
              borderRadius: BorderRadius.circular(AppRadius.md),
              border: Border.all(color: Colors.black12),
            ),
            alignment: Alignment.center,
            child: Text(
              _colorHex.toUpperCase(),
              style: TextStyle(
                color: labelColor,
                fontWeight: FontWeight.w700,
                fontSize: 12,
                letterSpacing: 0.3,
              ),
            ),
          ),
        ),
      ],
    );
  }
}
