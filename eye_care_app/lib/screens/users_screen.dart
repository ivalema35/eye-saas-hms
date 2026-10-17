import 'dart:async';
import '../widgets/app_error_state.dart';
import 'package:flutter/material.dart';
import '../widgets/app_refresh_indicator.dart';
import 'package:flutter/services.dart' show Clipboard, ClipboardData;
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/permissions.dart';
import '../utils/app_route.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_record_card.dart';
import '../widgets/skeleton.dart';
import '../models/auth_models.dart';
import '../services/masters_service.dart';
import '../services/permission_service.dart';
import '../services/user_service.dart';
import '../utils/rbac_gates.dart';
import 'user_form_screen.dart';

class UsersScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;

  /// Web parity: the dashboard's Doctor/Reception cards link to
  /// `hospital.users.index?role=doctor` etc., pre-filtered to just that
  /// role — not the full unfiltered list. Resolved to a role_id (the only
  /// filter the API accepts) once the roles list loads.
  final String? initialRoleSlug;

  const UsersScreen({
    super.key,
    required this.user,
    required this.hospital,
    this.initialRoleSlug,
  });

  @override
  State<UsersScreen> createState() => _UsersScreenState();
}

class _UsersScreenState extends State<UsersScreen> {
  final _searchCtrl = TextEditingController();
  final _scrollCtrl = ScrollController();
  final _searchFocus = FocusNode();
  Timer? _debounce;

  final _p = PermissionService.instance;
  bool get _canAdd => _p.canAny(const [
    Perm.masterDoctorAdd,
    Perm.masterReceptionAdd,
    Perm.masterOtStaffAdd,
  ]);
  bool _canFor(HospitalUserModel user, String verb) {
    final role = user.role;
    if (role == null || role.isSuper) return false;
    return RbacGates.canManageUserRole(
      userCategory: role.userCategory,
      slug: role.slug,
      name: role.name,
      verb: verb,
      permissions: _p,
    );
  }

  List<HospitalUserModel> _users = [];
  int _currentPage = 1;
  int _lastPage = 1;
  int _total = 0;
  bool _loading = true;
  bool _loadingMore = false;
  bool _searchOpen = false;
  String? _error;
  String _search = '';
  int? _roleFilterId;
  String? _roleFilterLabel;

  @override
  void initState() {
    super.initState();
    if (widget.initialRoleSlug != null) {
      _applyInitialRoleFilter();
    } else {
      _load(refresh: true);
    }
    _scrollCtrl.addListener(_onScroll);
  }

  /// Resolves the dashboard's role-slug link (e.g. "doctor") to a role_id —
  /// the only filter `fetchUsers` accepts — before the first load.
  Future<void> _applyInitialRoleFilter() async {
    try {
      final formData = await UserService.instance.fetchFormData();
      UserRole? role;
      for (final r in formData.roles) {
        if (r.slug == widget.initialRoleSlug) {
          role = r;
          break;
        }
      }
      final found = role;
      if (found != null && mounted) {
        setState(() {
          _roleFilterId = found.id;
          _roleFilterLabel = found.name;
        });
      }
    } catch (_) {
      // Filter is a convenience — fall through to an unfiltered list rather
      // than blocking the screen on a failed lookup.
    }
    if (mounted) _load(refresh: true);
  }

  void _clearRoleFilter() {
    setState(() {
      _roleFilterId = null;
      _roleFilterLabel = null;
    });
    _load(refresh: true);
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _searchCtrl.dispose();
    _scrollCtrl.dispose();
    _searchFocus.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollCtrl.position.pixels >=
            _scrollCtrl.position.maxScrollExtent - 200 &&
        !_loadingMore &&
        _currentPage < _lastPage) {
      _loadMore();
    }
  }

  Future<void> _load({bool refresh = false}) async {
    if (refresh) {
      setState(() {
        _loading = true;
        _error = null;
        _currentPage = 1;
      });
    }

    try {
      final resp = await UserService.instance.fetchUsers(
        page: _currentPage,
        search: _search.isEmpty ? null : _search,
        roleId: _roleFilterId,
      );
      if (!mounted) return;
      setState(() {
        if (refresh || _currentPage == 1) {
          _users = resp.users;
        } else {
          _users.addAll(resp.users);
        }
        _currentPage = resp.currentPage;
        _lastPage = resp.lastPage;
        _total = resp.total;
        _loading = false;
        _error = null;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = e.toString().replaceFirst('Exception: ', '');
      });
    }
  }

  Future<void> _loadMore() async {
    if (_loadingMore || _currentPage >= _lastPage) return;
    setState(() => _loadingMore = true);
    _currentPage++;
    try {
      final resp = await UserService.instance.fetchUsers(
        page: _currentPage,
        search: _search.isEmpty ? null : _search,
        roleId: _roleFilterId,
      );
      if (!mounted) return;
      setState(() {
        _users.addAll(resp.users);
        _lastPage = resp.lastPage;
        _total = resp.total;
        _loadingMore = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loadingMore = false);
    }
  }

  void _onSearch(String value) {
    _search = value;
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 500), () {
      if (mounted) _load(refresh: true);
    });
  }

  void _toggleSearch() {
    setState(() => _searchOpen = !_searchOpen);
    if (_searchOpen) {
      Future.delayed(
        const Duration(milliseconds: 50),
        () => _searchFocus.requestFocus(),
      );
    } else {
      _debounce?.cancel();
      _searchCtrl.clear();
      _search = '';
      _load(refresh: true);
    }
  }

  Future<void> _openAdd() async {
    final result = await Navigator.push<bool>(
      context,
      appRoute(UserFormScreen(user: widget.user, hospital: widget.hospital)),
    );
    if (result == true) _load(refresh: true);
  }

  Future<void> _openEdit(HospitalUserModel u) async {
    final result = await Navigator.push<bool>(
      context,
      appRoute(
        UserFormScreen(
          user: widget.user,
          hospital: widget.hospital,
          editUser: u,
        ),
      ),
    );
    if (result == true) _load(refresh: true);
  }

  Future<void> _confirmDelete(HospitalUserModel u) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.xl),
        ),
        title: Text(
          'Delete User',
          style: TextStyle(
            fontWeight: FontWeight.w800,
            color: AppColors.primary,
          ),
        ),
        content: Text(
          'Delete "${u.name}"? This action cannot be undone.',
          style: const TextStyle(color: AppColors.textSecondary),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red.shade600,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(AppRadius.md),
              ),
            ),
            child: const Text(
              'Delete',
              style: TextStyle(fontWeight: FontWeight.w700),
            ),
          ),
        ],
      ),
    );

    if (ok != true || !mounted) return;

    try {
      await UserService.instance.deleteUser(u.id);
      // See user_form_screen.dart's save() for why this matters — an
      // inactive/deleted doctor must not keep showing up in other screens'
      // doctor dropdowns for the rest of the app session.
      MastersService.instance.clearCache();
      if (!mounted) return;
      showAppSnackBar(
        context,
        '"${u.name}" deleted successfully',
        isSuccess: true,
      );
      _load(refresh: true);
    } catch (e) {
      if (!mounted) return;
      showAppSnackBar(
        context,
        e.toString().replaceFirst('Exception: ', ''),
        isError: true,
      );
      _load(refresh: true);
    }
  }

  Future<void> _toggleStatus(HospitalUserModel u) async {
    try {
      final newStatus = await UserService.instance.toggleStatus(u.id);
      MastersService.instance.clearCache();
      if (!mounted) return;
      setState(() {
        final idx = _users.indexWhere((x) => x.id == u.id);
        if (idx >= 0) {
          _users[idx] = HospitalUserModel(
            id: u.id,
            name: u.name,
            email: u.email,
            contact: u.contact,
            status: newStatus,
            role: u.role,
            focPermission: u.focPermission,
            lastLoginAt: u.lastLoginAt,
            doctorType: u.doctorType,
            doctorPrefix: u.doctorPrefix,
            registrationNo: u.registrationNo,
            experienceYears: u.experienceYears,
            signatureUrl: u.signatureUrl,
            profilePhotoUrl: u.profilePhotoUrl,
          );
        }
      });
    } catch (e) {
      if (!mounted) return;
      showAppSnackBar(
        context,
        e.toString().replaceFirst('Exception: ', ''),
        isError: true,
      );
      _load(refresh: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = PermissionService.instance;
    if (!p.canFeature('master', 'user_doctor') &&
        !p.canFeature('master', 'user_reception') &&
        !p.canFeature('master', 'user_ot_staff')) {
      return Scaffold(
        backgroundColor: AppColors.backgroundAlt,
        appBar: AppBar(
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
          elevation: 0,
          title: const Text(
            'Users',
            style: TextStyle(
              fontWeight: FontWeight.w900,
              fontSize: 18,
              color: Colors.white,
            ),
          ),
        ),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 80, horizontal: 32),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(
                  Icons.lock_outline_rounded,
                  size: 56,
                  color: AppColors.primary.withValues(alpha: 0.25),
                ),
                const SizedBox(height: 16),
                Text(
                  'No Access',
                  style: TextStyle(
                    fontSize: 17,
                    fontWeight: FontWeight.w700,
                    color: AppColors.primary.withValues(alpha: 0.50),
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  'You do not have permission to manage users.',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 13,
                    color: AppColors.primary.withValues(alpha: 0.35),
                  ),
                ),
              ],
            ),
          ),
        ),
      );
    }
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Users',
              style: TextStyle(
                fontWeight: FontWeight.w900,
                fontSize: 18,
                color: Colors.white,
              ),
            ),
            if (!_loading && _error == null)
              Text(
                '$_total member${_total == 1 ? '' : 's'}',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: Color(0xA6FFFFFF),
                ),
              ),
          ],
        ),
        actions: [
          IconButton(
            onPressed: _toggleSearch,
            tooltip: _searchOpen ? 'Close Search' : 'Search',
            icon: Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: _searchOpen ? Colors.white24 : const Color(0x26FFFFFF),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(
                _searchOpen ? Icons.close_rounded : Icons.search_rounded,
                size: 20,
                color: Colors.white,
              ),
            ),
          ),
          if (_canAdd)
            IconButton(
              onPressed: _openAdd,
              icon: Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  color: const Color(0x26FFFFFF),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(
                  Icons.add_rounded,
                  size: 20,
                  color: Colors.white,
                ),
              ),
              tooltip: 'Add User',
            ),
          const SizedBox(width: 4),
        ],
      ),
      body: Column(
        children: [
          if (_roleFilterLabel != null) _buildRoleFilterChip(),
          AnimatedSize(
            duration: const Duration(milliseconds: 200),
            curve: Curves.easeInOut,
            child: _searchOpen ? _buildSearchBar() : const SizedBox.shrink(),
          ),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  /// Shown when arriving from the dashboard's Doctor/Reception cards —
  /// makes the applied filter visible and lets the admin clear it to see
  /// everyone, instead of silently hiding other roles with no indication.
  Widget _buildRoleFilterChip() {
    return Container(
      width: double.infinity,
      color: AppColors.primary,
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
      child: Align(
        alignment: Alignment.centerLeft,
        child: Container(
          padding: const EdgeInsets.only(left: 12, right: 6, top: 6, bottom: 6),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(AppRadius.xl),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                Icons.filter_alt_rounded,
                size: 14,
                color: AppColors.primary,
              ),
              const SizedBox(width: 6),
              Text(
                _roleFilterLabel!,
                style: TextStyle(
                  fontSize: 12.5,
                  fontWeight: FontWeight.w700,
                  color: AppColors.primary,
                ),
              ),
              const SizedBox(width: 4),
              InkWell(
                onTap: _clearRoleFilter,
                borderRadius: BorderRadius.circular(20),
                child: Padding(
                  padding: const EdgeInsets.all(4),
                  child: Icon(
                    Icons.close_rounded,
                    size: 15,
                    color: AppColors.primary.withValues(alpha: 0.6),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSearchBar() {
    return Container(
      color: AppColors.primary,
      padding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
        ),
        child: TextField(
          controller: _searchCtrl,
          focusNode: _searchFocus,
          onChanged: _onSearch,
          style: TextStyle(
            color: AppColors.primary,
            fontSize: 14,
            fontWeight: FontWeight.w500,
          ),
          decoration: InputDecoration(
            hintText: 'Search by name, email or contact...',
            hintStyle: TextStyle(color: Colors.grey[400], fontSize: 13),
            prefixIcon: Icon(
              Icons.search_rounded,
              color: AppColors.primary,
              size: 20,
            ),
            suffixIcon: _searchCtrl.text.isNotEmpty
                ? IconButton(
                    icon: Icon(
                      Icons.close_rounded,
                      size: 18,
                      color: Colors.grey[500],
                    ),
                    onPressed: () {
                      _searchCtrl.clear();
                      _onSearch('');
                    },
                  )
                : null,
            border: InputBorder.none,
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 16,
              vertical: 13,
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const AppSkeletonList();
    }

    if (_error != null) {
      return AppErrorState(message: _error!, onRetry: () => _load(refresh: true));
    }

    if (_users.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.manage_accounts_rounded,
              size: 64,
              color: AppColors.primaryA18,
            ),
            const SizedBox(height: 16),
            Text(
              _search.isEmpty
                  ? 'No users yet.\nTap + to add the first user.'
                  : 'No users match "$_search"',
              textAlign: TextAlign.center,
              style: TextStyle(
                color: Color(0xB364748B),
                fontSize: 14,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
      );
    }

    return AppRefreshIndicator(
      color: AppColors.primary,
      onRefresh: () => _load(refresh: true),
      child: ListView.builder(
        controller: _scrollCtrl,
        padding: const EdgeInsets.fromLTRB(16, 14, 16, 100),
        itemCount: _users.length + (_loadingMore ? 1 : 0),
        itemBuilder: (ctx, i) {
          if (i == _users.length) {
            return Padding(
              padding: EdgeInsets.symmetric(vertical: 20),
              child: Center(
                child: CircularProgressIndicator(color: AppColors.primary),
              ),
            );
          }
          return AnimatedListItem(
            index: i,
            child: RepaintBoundary(
              child: _UserCard(
                user: _users[i],
                canEdit: _canFor(_users[i], 'edit'),
                canDelete: _canFor(_users[i], 'delete'),
                canToggleStatus: _canFor(_users[i], 'edit'),
                onEdit: () => _openEdit(_users[i]),
                onDelete: () => _confirmDelete(_users[i]),
                onToggleStatus: () => _toggleStatus(_users[i]),
              ),
            ),
          );
        },
      ),
    );
  }
}

// ── User Card ────────────────────────────────────────────────────────────────

class _UserCard extends StatelessWidget {
  final HospitalUserModel user;
  final bool canEdit;
  final bool canDelete;
  final bool canToggleStatus;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final VoidCallback onToggleStatus;

  const _UserCard({
    required this.user,
    required this.canEdit,
    required this.canDelete,
    required this.canToggleStatus,
    required this.onEdit,
    required this.onDelete,
    required this.onToggleStatus,
  });

  String _initials(String name) {
    final parts = name.trim().split(RegExp(r'\s+'));
    if (parts.isEmpty) return '?';
    if (parts.length == 1) {
      return parts[0].substring(0, parts[0].length >= 2 ? 2 : 1).toUpperCase();
    }
    return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
  }

  @override
  Widget build(BuildContext context) {
    final roleColor = user.role?.parsedColor ?? AppColors.primary;
    final isActive = user.status == 'active';
    final initials = _initials(user.name);
    final showPassword =
        PermissionService.instance.isSuper &&
        (user.originalPassword?.isNotEmpty ?? false);

    // Web parity: the users list shows exactly ONE role badge per row (a
    // colored dot + `role.name`) — there is no separate "Doctor"/prefix
    // badge alongside it. Previously this screen rendered a second badge
    // whenever `isDoctorRole` was true, which duplicated the role chip's
    // own text whenever the role was literally named "Doctor" (both chips
    // read "Doctor"), and was actively misleading for a differently-named
    // doctor-category role (e.g. a custom role named "test" showed "test" +
    // "Doctor" side by side, implying two different things about one
    // user). Doctor-specific detail (prefix, registration no., etc.)
    // belongs in the Edit form, not a second list badge.
    final fields = <AppRecordField>[
      if (user.role != null)
        AppRecordField(
          'Role',
          user.role!.name,
          icon: Icons.badge_outlined,
          color: roleColor,
        ),
      if (user.contact.isNotEmpty)
        AppRecordField(
          'Phone',
          user.contact,
          icon: Icons.call_outlined,
          color: RecordFieldColors.phone,
        ),
    ];

    return AppRecordCard(
      title: user.name,
      subtitle: user.email,
      leading: Container(
        width: 46,
        height: 46,
        decoration: BoxDecoration(
          color: roleColor.withValues(alpha: 0.12),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: roleColor.withValues(alpha: 0.20)),
        ),
        alignment: Alignment.center,
        child: Text(
          initials,
          style: TextStyle(
            fontWeight: FontWeight.w900,
            fontSize: 16,
            color: roleColor,
          ),
        ),
      ),
      trailing: PressScaleWrapper(
        onTap: canToggleStatus ? onToggleStatus : null,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
          decoration: BoxDecoration(
            color: isActive ? Colors.green.shade50 : Colors.grey.shade100,
            borderRadius: BorderRadius.circular(AppRadius.xl),
            border: Border.all(
              color: isActive ? Colors.green.shade300 : Colors.grey.shade300,
            ),
          ),
          child: Text(
            isActive ? 'Active' : 'Inactive',
            style: TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w700,
              color: isActive ? Colors.green.shade700 : Colors.grey.shade600,
            ),
          ),
        ),
      ),
      fields: fields,
      actions: (canEdit || canDelete || showPassword)
          ? Row(
              children: [
                if (showPassword)
                  Expanded(
                    child: InkWell(
                      borderRadius: BorderRadius.circular(8),
                      onTap: () {
                        Clipboard.setData(
                          ClipboardData(text: user.originalPassword!),
                        );
                        showAppSnackBar(
                          context,
                          'Password copied.',
                          isSuccess: true,
                        );
                      },
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            Icons.key_rounded,
                            size: 13,
                            color: Color(0x8064748B),
                          ),
                          const SizedBox(width: 4),
                          Flexible(
                            child: Text(
                              user.originalPassword!,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontSize: 12,
                                color: Color(0xCC64748B),
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                          const SizedBox(width: 4),
                          Icon(
                            Icons.copy_rounded,
                            size: 13,
                            color: AppColors.primaryA45,
                          ),
                        ],
                      ),
                    ),
                  )
                else
                  const Spacer(),
                if (canEdit) ...[
                  _ActionBtn(
                    icon: Icons.edit_rounded,
                    color: AppColors.primary,
                    onTap: onEdit,
                  ),
                  const SizedBox(width: 8),
                ],
                if (canDelete)
                  _ActionBtn(
                    icon: Icons.delete_outline_rounded,
                    color: Colors.red.shade400,
                    onTap: onDelete,
                  ),
              ],
            )
          : null,
    );
  }
}

class _ActionBtn extends StatelessWidget {
  final IconData icon;
  final Color color;
  final VoidCallback onTap;

  const _ActionBtn({
    required this.icon,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 34,
        height: 34,
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.09),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: color.withValues(alpha: 0.18)),
        ),
        alignment: Alignment.center,
        child: Icon(icon, size: 16, color: color),
      ),
    );
  }
}
