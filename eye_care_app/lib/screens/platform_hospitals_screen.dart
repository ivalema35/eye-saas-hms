import 'dart:async';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../constants/app_text_styles.dart';
import '../models/platform_admin_models.dart';
import '../models/platform_tenant_models.dart';
import '../services/platform_tenant_service.dart';
import '../utils/app_decorations.dart';
import '../utils/app_route.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_pagination_bar.dart';
import '../widgets/app_search_bar.dart';
import '../widgets/app_section_header.dart';
import '../widgets/status_badge.dart';
import 'platform_hospital_detail_screen.dart';
import 'platform_hospital_form_screen.dart';

class PlatformHospitalsScreen extends StatefulWidget {
  final PlatformAdmin admin;
  final VoidCallback onMenuTap;

  const PlatformHospitalsScreen({super.key, required this.admin, required this.onMenuTap});

  @override
  State<PlatformHospitalsScreen> createState() => _PlatformHospitalsScreenState();
}

class _PlatformHospitalsScreenState extends State<PlatformHospitalsScreen> {
  final _searchCtrl = TextEditingController();
  Timer? _debounce;

  bool _loading = true;
  String? _error;
  List<TenantSummary> _hospitals = [];
  int _currentPage = 1;
  int _lastPage    = 1;
  int _total       = 0;
  String? _statusFilter; // null = all

  static const _filters = [null, 'trial', 'active', 'grace', 'suspended', 'inactive'];
  static const _filterLabels = ['All', 'Trial', 'Active', 'Grace', 'Suspended', 'Inactive'];

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _load({int page = 1}) async {
    setState(() { _loading = true; _error = null; });

    final result = await PlatformTenantService.instance.list(
      search: _searchCtrl.text.trim().isEmpty ? null : _searchCtrl.text.trim(),
      status: _statusFilter,
      page: page,
    );

    if (!mounted) return;
    setState(() {
      _loading = false;
      if (result == null) {
        _error = 'Could not load hospitals. Check your connection.';
      } else {
        _hospitals    = result.hospitals;
        _currentPage  = page;
        _lastPage     = result.lastPage;
        _total        = result.total;
      }
    });
  }

  void _onSearch(String _) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () => _load());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          // AppBar
          Material(
            color: AppColors.primary,
            child: SafeArea(
              bottom: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(4, 4, 16, 12),
                child: Row(
                  children: [
                    IconButton(
                      icon: const Icon(Icons.menu_rounded, color: Colors.white),
                      onPressed: widget.onMenuTap,
                    ),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Hospitals', style: GoogleFonts.poppins(fontSize: 18, fontWeight: FontWeight.w800, color: Colors.white)),
                          if (_total > 0)
                            Text('$_total hospitals', style: GoogleFonts.poppins(fontSize: 10, color: Colors.white70)),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),

          // Search
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            child: AppSearchBar(
              controller: _searchCtrl,
              hint: 'Search hospitals, admin, city...',
              onChanged: _onSearch,
              onClear: () => _load(),
            ),
          ),

          // Status filter chips
          SizedBox(
            height: 44,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              itemCount: _filters.length,
              separatorBuilder: (_, __) => const SizedBox(width: 8),
              itemBuilder: (_, i) {
                final selected = _statusFilter == _filters[i];
                return ChoiceChip(
                  label: Text(_filterLabels[i]),
                  selected: selected,
                  onSelected: (_) {
                    setState(() => _statusFilter = _filters[i]);
                    _load();
                  },
                  selectedColor: AppColors.primaryA15,
                  labelStyle: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: selected ? AppColors.primary : AppColors.textSecondary,
                  ),
                  backgroundColor: AppColors.surface,
                  side: BorderSide(color: selected ? AppColors.primary : AppColors.primaryA12, width: 1),
                  padding: const EdgeInsets.symmetric(horizontal: 10),
                  visualDensity: VisualDensity.compact,
                );
              },
            ),
          ),

          const SizedBox(height: 4),

          // List
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : _error != null
                    ? AppErrorState(message: _error!, onRetry: _load)
                    : _hospitals.isEmpty
                        ? AppEmptyState(
                            message: 'No hospitals found.',
                            icon: Icons.local_hospital_outlined,
                            onRefresh: _load,
                          )
                        : RefreshIndicator(
                            color: AppColors.primary,
                            onRefresh: () => _load(page: _currentPage),
                            child: ListView.builder(
                              padding: const EdgeInsets.fromLTRB(16, 4, 16, 100),
                              itemCount: _hospitals.length + 1,
                              itemBuilder: (_, i) {
                                if (i == _hospitals.length) {
                                  return Padding(
                                    padding: const EdgeInsets.only(top: 12, bottom: 8),
                                    child: AppPaginationBar(
                                      currentPage: _currentPage,
                                      totalPages: _lastPage,
                                      onPageChange: (p) => _load(page: p),
                                    ),
                                  );
                                }
                                return AnimatedListItem(
                                  index: i,
                                  child: _HospitalCard(
                                    tenant: _hospitals[i],
                                    onTap: () async {
                                      await Navigator.push(context, appRoute(PlatformHospitalDetailScreen(tenantId: _hospitals[i].id)));
                                      _load(page: _currentPage);
                                    },
                                  ),
                                );
                              },
                            ),
                          ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          await Navigator.push(context, appRoute(const PlatformHospitalFormScreen()));
          _load(page: 1);
        },
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        icon: const Icon(Icons.add_rounded),
        label: Text('New Hospital', style: GoogleFonts.poppins(fontWeight: FontWeight.w700)),
      ),
    );
  }
}

class _HospitalCard extends StatelessWidget {
  final TenantSummary tenant;
  final VoidCallback onTap;

  const _HospitalCard({required this.tenant, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final t = tenant;
    return PressScaleWrapper(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.all(14),
        decoration: AppDecorations.card(),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 40, height: 40,
              decoration: BoxDecoration(
                color: AppColors.primaryA10,
                borderRadius: BorderRadius.circular(AppRadius.sm),
              ),
              child: Center(
                child: Text(
                  t.name.isNotEmpty ? t.name[0].toUpperCase() : '?',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.primary),
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(t.name, style: AppTextStyles.cardTitle, overflow: TextOverflow.ellipsis),
                  const SizedBox(height: 2),
                  Text(
                    '/${t.slug}${t.adminName != null ? ' · ${t.adminName}' : ''}',
                    style: AppTextStyles.cardSubtitle,
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (t.city != null || t.trialEndsAt != null) ...[
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        if (t.city != null) ...[
                          Icon(Icons.location_on_outlined, size: 12, color: AppColors.textDisabled),
                          const SizedBox(width: 2),
                          Text(t.city!, style: const TextStyle(fontSize: 11, color: AppColors.textDisabled)),
                          const SizedBox(width: 8),
                        ],
                        if (t.trialEndsAt != null && t.status == 'trial') ...[
                          Icon(Icons.timer_outlined, size: 12, color: AppColors.orange),
                          const SizedBox(width: 2),
                          Text(
                            'Ends ${_formatDate(t.trialEndsAt!)}',
                            style: const TextStyle(fontSize: 11, color: AppColors.orange),
                          ),
                        ],
                      ],
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(width: 8),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                StatusBadge.hospitalStatus(t.status),
                const SizedBox(height: 4),
                Icon(Icons.chevron_right_rounded, size: 18, color: AppColors.textDisabled),
              ],
            ),
          ],
        ),
      ),
    );
  }

  String _formatDate(DateTime dt) {
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return '${dt.day} ${months[dt.month - 1]}';
  }
}
