import 'dart:async';

import 'package:flutter/material.dart';

import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../models/auth_models.dart';
import '../models/patient_models.dart';
import '../services/patient_service.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_error_state.dart';
import '../widgets/app_record_card.dart';
import '../widgets/app_refresh_indicator.dart';
import '../widgets/skeleton.dart';
import '../widgets/status_badge.dart';
import '../utils/app_date_picker.dart';
import '../utils/app_route.dart';
import '../utils/date_format.dart';
import 'patient_checkin_screen.dart';

/// Phone appointment history. Same search as the hospital page: name or
/// mobile, plus an optional date range.
///
/// Redesigned 2026-10-06 — the previous version was a one-off layout (plain
/// `TextField`/`AppBar`-less header, bare `Container` rows, plain
/// `CircularProgressIndicator`/text error state, literal "Previous"/"Next"
/// buttons) unlike anything else in this app. Rebuilt onto the same
/// `AppRecordCard` record-list convention + `AppSkeletonList`/
/// `AppErrorState`/`AppEmptyState` every other list screen already uses
/// (see `admin_collection_screen.dart`, `ot_appointment_list_screen.dart`).
class PhoneHistoryScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  const PhoneHistoryScreen({
    super.key,
    required this.user,
    required this.hospital,
  });

  @override
  State<PhoneHistoryScreen> createState() => _PhoneHistoryScreenState();
}

class _PhoneHistoryScreenState extends State<PhoneHistoryScreen> {
  final _searchCtrl = TextEditingController();
  Timer? _debounce;
  DateTime? _from;
  DateTime? _to;
  bool _loading = true;
  String? _error;
  List<Patient> _rows = [];
  int _page = 1;
  int _lastPage = 1;
  int _total = 0;

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
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final result = await PatientService.instance.fetchPhoneHistory(
        search: _searchCtrl.text,
        fromDate: _from == null ? null : toIsoDate(_from!),
        toDate: _to == null ? null : toIsoDate(_to!),
        page: page,
      );
      if (!mounted) return;
      setState(() {
        _rows = result.patients;
        _page = result.meta.currentPage;
        _lastPage = result.meta.lastPage;
        _total = result.meta.total;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = e.toString().replaceFirst('Exception: ', '');
      });
    }
  }

  void _onSearch(String _) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () => _load());
  }

  Future<void> _pickRange() async {
    final picked = await showAppDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
      initialDateRange: (_from != null && _to != null)
          ? DateTimeRange(start: _from!, end: _to!)
          : null,
    );
    if (picked == null) return;
    setState(() {
      _from = picked.start;
      _to = picked.end;
    });
    _load();
  }

  Future<void> _checkIn(Patient p) async {
    await Navigator.of(context).push(
      appRoute(
        PatientCheckinScreen(
          user: widget.user,
          hospital: widget.hospital,
          patient: p,
        ),
      ),
    );
    if (mounted) _load(page: _page);
  }

  void _view(Patient p) {
    final date = parseApiPlainDate(p.appointmentDate);
    final checked = p.caseId != null;
    showAppDetailSheet(
      context,
      name: p.fullName,
      code: p.patientCode.isEmpty ? null : p.patientCode,
      pills: [
        AppStatusPill(
          label: checked ? 'Checked In' : 'Pending',
          color: checked ? AppColors.green : AppColors.orange,
        ),
      ],
      sections: [
        AppDetailSectionCard(
          title: 'Registration Details',
          icon: Icons.event_note_rounded,
          child: AppFieldGrid(
            fields: [
              AppRecordField(
                'Age / Gender',
                '${p.age ?? '—'} / ${p.gender ?? '—'}',
                icon: Icons.cake_outlined,
                color: RecordFieldColors.age,
              ),
              AppRecordField(
                'Contact',
                p.contactNo ?? '—',
                icon: Icons.call_outlined,
                color: RecordFieldColors.phone,
              ),
              AppRecordField(
                'WhatsApp',
                (p.whatsappNo ?? '').isEmpty ? '—' : p.whatsappNo!,
                icon: Icons.chat_outlined,
                color: RecordFieldColors.phone,
              ),
              AppRecordField(
                'City',
                p.location?.city ?? '—',
                icon: Icons.location_on_outlined,
                color: RecordFieldColors.location,
              ),
              AppRecordField(
                'Appointment',
                date != null ? toDisplayDate(date) : '—',
                icon: Icons.event_rounded,
                color: RecordFieldColors.date,
              ),
              AppRecordField(
                'Doctor',
                p.doctor?.name ?? '—',
                icon: Icons.medical_services_outlined,
                color: RecordFieldColors.doctor,
              ),
              AppRecordField(
                'Reception',
                p.receptionistName ?? '—',
                icon: Icons.support_agent_rounded,
                color: RecordFieldColors.receptionist,
              ),
              AppRecordField(
                'Case',
                p.caseId == null ? '—' : (p.caseType?.caseType ?? '—'),
                icon: Icons.label_outline_rounded,
                color: RecordFieldColors.caseType,
              ),
            ],
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final rangeLabel = (_from == null || _to == null)
        ? 'All dates'
        : '${toDisplayDate(_from!)} – ${toDisplayDate(_to!)}';
    return Scaffold(
      backgroundColor: AppColors.backgroundAlt,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          'Phone Appointment History',
          style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16),
        ),
        actions: [
          IconButton(
            onPressed: _pickRange,
            tooltip: 'Change date range',
            icon: const Icon(Icons.date_range_rounded),
          ),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
            child: TextField(
              controller: _searchCtrl,
              onChanged: _onSearch,
              decoration: InputDecoration(
                hintText: 'Search by name or mobile number...',
                hintStyle: TextStyle(
                  fontSize: 13,
                  color: AppColors.primaryA45,
                ),
                prefixIcon: Icon(
                  Icons.search_rounded,
                  color: AppColors.primaryA55,
                  size: 20,
                ),
                suffixIcon: _searchCtrl.text.isNotEmpty
                    ? IconButton(
                        icon: Icon(
                          Icons.close_rounded,
                          color: AppColors.primaryA55,
                          size: 18,
                        ),
                        onPressed: () {
                          _searchCtrl.clear();
                          _onSearch('');
                        },
                      )
                    : null,
                filled: true,
                fillColor: Colors.white,
                contentPadding: const EdgeInsets.symmetric(
                  vertical: 10,
                  horizontal: 14,
                ),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide: BorderSide(color: AppColors.primaryA18),
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide: BorderSide(color: AppColors.primaryA18),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.md),
                  borderSide: BorderSide(color: AppColors.primary, width: 1.5),
                ),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 10, 16, 2),
            child: Row(
              children: [
                Text(
                  '$_total record${_total == 1 ? '' : 's'}',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: AppColors.primary,
                  ),
                ),
                const Spacer(),
                Text(
                  rangeLabel,
                  style: const TextStyle(
                    fontSize: 10.5,
                    color: AppColors.textSecondary,
                  ),
                ),
                if (_from != null) ...[
                  const SizedBox(width: 6),
                  InkWell(
                    onTap: () {
                      setState(() {
                        _from = null;
                        _to = null;
                      });
                      _load();
                    },
                    child: Icon(
                      Icons.close_rounded,
                      size: 14,
                      color: AppColors.primaryA55,
                    ),
                  ),
                ],
              ],
            ),
          ),
          Expanded(child: _body()),
        ],
      ),
    );
  }

  Widget _body() {
    if (_loading) return const AppSkeletonList();
    if (_error != null) {
      return AppErrorState(message: _error!, onRetry: () => _load(page: _page));
    }
    if (_rows.isEmpty) {
      return AppEmptyState(
        message: 'No phone appointments found.',
        icon: Icons.phone_disabled_rounded,
        onRefresh: () => _load(page: _page),
      );
    }
    return Column(
      children: [
        Expanded(
          child: AppRefreshIndicator(
            color: AppColors.primary,
            onRefresh: () => _load(page: _page),
            child: ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(14, 10, 14, 12),
              itemCount: _rows.length,
              separatorBuilder: (_, _) => const SizedBox(height: 10),
              itemBuilder: (_, i) {
                final p = _rows[i];
                final checked = p.caseId != null;
                return AnimatedListItem(
                  index: i,
                  child: AppRecordCard(
                    title: p.fullName,
                    subtitle: '${p.patientCode} · ${p.contactNo ?? '—'}',
                    trailing: StatusBadge(
                      label: checked ? 'Checked In' : 'Pending',
                      color: checked ? AppColors.green : AppColors.orange,
                    ),
                    fields: [
                      AppRecordField(
                        'Doctor',
                        p.doctor?.name ?? '—',
                        icon: Icons.medical_services_outlined,
                        color: RecordFieldColors.doctor,
                      ),
                      AppRecordField(
                        'Appointment',
                        () {
                          final d = parseApiPlainDate(p.appointmentDate);
                          return d != null ? toDisplayDate(d) : '—';
                        }(),
                        icon: Icons.event_rounded,
                        color: RecordFieldColors.date,
                      ),
                    ],
                    actions: Row(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        TextButton(
                          onPressed: () => _view(p),
                          child: const Text('View'),
                        ),
                        if (!checked) ...[
                          const SizedBox(width: 4),
                          FilledButton.icon(
                            onPressed: () => _checkIn(p),
                            icon: const Icon(
                              Icons.login_rounded,
                              size: 15,
                            ),
                            label: const Text('Check In'),
                            style: miniFilledStyle(AppColors.primary),
                          ),
                        ],
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
        ),
        if (_lastPage > 1)
          Container(
            margin: const EdgeInsets.fromLTRB(14, 0, 14, 10),
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: AppColors.primaryA10),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  '$_total results · Page $_page/$_lastPage',
                  style: const TextStyle(
                    fontSize: 11,
                    color: AppColors.textSecondary,
                  ),
                ),
                Row(
                  children: [
                    _PagBtn(
                      icon: Icons.chevron_left_rounded,
                      enabled: _page > 1,
                      onTap: () => _load(page: _page - 1),
                    ),
                    const SizedBox(width: 6),
                    _PagBtn(
                      icon: Icons.chevron_right_rounded,
                      enabled: _page < _lastPage,
                      onTap: () => _load(page: _page + 1),
                    ),
                  ],
                ),
              ],
            ),
          ),
      ],
    );
  }
}

class _PagBtn extends StatelessWidget {
  final IconData icon;
  final bool enabled;
  final VoidCallback onTap;

  const _PagBtn({required this.icon, required this.enabled, required this.onTap});

  @override
  Widget build(BuildContext context) => InkWell(
    onTap: enabled ? onTap : null,
    borderRadius: BorderRadius.circular(AppRadius.sm),
    child: Container(
      width: 30,
      height: 30,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: enabled
            ? AppColors.primaryA10
            : Colors.grey.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(AppRadius.sm),
      ),
      child: Icon(
        icon,
        size: 18,
        color: enabled ? AppColors.primary : Colors.grey[400],
      ),
    ),
  );
}
