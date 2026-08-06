import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_empty_state.dart';
import '../widgets/app_error_state.dart';
import '../models/auth_models.dart';
import '../models/patient_models.dart';
import '../models/share_history_models.dart';
import '../services/share_history_service.dart';
import '../utils/app_route.dart';
import 'patient_history_screen.dart';

// ── Screen ────────────────────────────────────────────────────────────────────

class ShareHistoryScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final int initialTab;

  const ShareHistoryScreen({
    super.key,
    required this.user,
    required this.hospital,
    this.initialTab = 0,
  });

  @override
  State<ShareHistoryScreen> createState() => _ShareHistoryScreenState();
}

class _ShareHistoryScreenState extends State<ShareHistoryScreen>
    with SingleTickerProviderStateMixin {
  late final TabController _tabCtrl;

  @override
  void initState() {
    super.initState();
    _tabCtrl = TabController(length: 3, vsync: this, initialIndex: widget.initialTab);
  }

  @override
  void dispose() {
    _tabCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          'Share History',
          style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18, color: Colors.white),
        ),
        bottom: TabBar(
          controller: _tabCtrl,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white60,
          indicatorColor: Colors.white,
          indicatorWeight: 3,
          labelStyle: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13),
          unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
          tabs: const [
            Tab(text: 'Patient History'),
            Tab(text: 'Hospital History'),
            Tab(text: 'Requests'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabCtrl,
        children: [
          _PatientHistoryTab(hospital: widget.hospital, user: widget.user),
          _HospitalHistoryTab(hospital: widget.hospital),
          _RequestsTab(hospital: widget.hospital),
        ],
      ),
    );
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// TAB 1 — Patient History
// ═══════════════════════════════════════════════════════════════════════════════

class _PatientHistoryTab extends StatefulWidget {
  final HospitalInfo hospital;
  final UserInfo user;
  const _PatientHistoryTab({required this.hospital, required this.user});

  @override
  State<_PatientHistoryTab> createState() => _PatientHistoryTabState();
}

class _PatientHistoryTabState extends State<_PatientHistoryTab>
    with AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;

  final _nameCtrl    = TextEditingController();
  final _doctorCtrl  = TextEditingController();
  final _contactCtrl = TextEditingController();
  final _dateCtrl    = TextEditingController();

  bool _filtersOpen  = false;
  bool _loading      = false;
  bool _searched     = false;
  String? _error;
  List<HistoryPatient> _patients = [];
  ShareHistoryMeta? _meta;
  int _page = 1;

  @override
  void initState() {
    super.initState();
    _search();
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _doctorCtrl.dispose();
    _contactCtrl.dispose();
    _dateCtrl.dispose();
    super.dispose();
  }

  Future<void> _search({int page = 1}) async {
    setState(() { _loading = true; _error = null; });
    try {
      final result = await ShareHistoryService.instance.fetchPatients(
        patientName: _nameCtrl.text.trim(),
        doctorName:  _doctorCtrl.text.trim(),
        contactNo:   _contactCtrl.text.trim(),
        date:        _dateCtrl.text,
        page:        page,
      );
      setState(() {
        _patients = result.patients;
        _meta     = result.meta;
        _page     = page;
        _searched = true;
      });
    } catch (e) {
      setState(() => _error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      setState(() => _loading = false);
    }
  }

  void _clear() {
    _nameCtrl.clear();
    _doctorCtrl.clear();
    _contactCtrl.clear();
    _dateCtrl.clear();
    setState(() { _patients = []; _meta = null; _searched = false; _error = null; });
  }

  bool get _hasFilters =>
      _nameCtrl.text.isNotEmpty ||
      _doctorCtrl.text.isNotEmpty ||
      _contactCtrl.text.isNotEmpty ||
      _dateCtrl.text.isNotEmpty;

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return RefreshIndicator(
      onRefresh: () => _search(page: _page),
      color: AppColors.primary,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(14),
        children: [
          _FilterCard(
            isOpen: _filtersOpen,
            onToggle: () => setState(() => _filtersOpen = !_filtersOpen),
            onFilter: () => _search(),
            onClear:  _hasFilters ? _clear : null,
            children: [
              _FilterField(controller: _nameCtrl,    label: 'Patient Name',  hint: 'Search patient...'),
              _FilterField(controller: _doctorCtrl,  label: 'Doctor Name',   hint: 'Search doctor...'),
              _FilterField(controller: _contactCtrl, label: 'Contact No.',   hint: 'Contact number...', keyboardType: TextInputType.phone),
              _DateFilterField(controller: _dateCtrl, label: 'Date'),
            ],
          ),
          const SizedBox(height: 14),
          if (_loading)
            Center(child: Padding(
              padding: EdgeInsets.all(40),
              child: CircularProgressIndicator(color: AppColors.primary),
            ))
          else if (_error != null)
            AppErrorState(message: _error!, onRetry: () => _search(page: _page))
          else if (_searched && _patients.isEmpty)
            const AppEmptyState(message: 'No history found.')
          else ...[
            ..._patients.map((p) => _HistoryPatientCard(
              patient: p,
              onView: () => _onView(p),
            )),
            if (_meta != null && _meta!.lastPage > 1)
              _SimplePagination(
                current: _meta!.currentPage,
                last:    _meta!.lastPage,
                total:   _meta!.total,
                onPage:  (p) => _search(page: p),
              ),
          ],
        ],
      ),
    );
  }

  void _onView(HistoryPatient p) {
    if (p.isOwn) {
      final patient = Patient(
        id:          p.id,
        patientCode: p.patientCode,
        firstName:   p.firstName,
        lastName:    p.lastName,
        fullName:    p.fullName,
        age:         p.age,
        contactNo:   p.contactNo,
      );
      Navigator.push(
        context,
        appRoute(PatientHistoryScreen(
          user:     widget.user,
          hospital: widget.hospital,
          patient:  patient,
        )),
      );
    } else {
      Navigator.push(
        context,
        appRoute(_PartnerPatientsScreen(
          hospital:        widget.hospital,
          partnerTenantId: p.tenantId,
          partnerName:     p.tenantName ?? 'Partner Hospital',
        )),
      );
    }
  }

}

// ═══════════════════════════════════════════════════════════════════════════════
// TAB 2 — Hospital History
// ═══════════════════════════════════════════════════════════════════════════════

class _HospitalHistoryTab extends StatefulWidget {
  final HospitalInfo hospital;
  const _HospitalHistoryTab({required this.hospital});

  @override
  State<_HospitalHistoryTab> createState() => _HospitalHistoryTabState();
}

class _HospitalHistoryTabState extends State<_HospitalHistoryTab>
    with AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;

  final _nameCtrl     = TextEditingController();
  final _cityCtrl     = TextEditingController();
  final _districtCtrl = TextEditingController();
  final _stateCtrl    = TextEditingController();

  bool _filtersOpen = false;
  bool _loading     = false;
  String? _error;
  List<ShareHospital> _hospitals = [];
  ShareHistoryMeta? _meta;
  int _page = 1;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _cityCtrl.dispose();
    _districtCtrl.dispose();
    _stateCtrl.dispose();
    super.dispose();
  }

  Future<void> _load({int page = 1}) async {
    setState(() { _loading = true; _error = null; });
    try {
      final result = await ShareHistoryService.instance.fetchHospitals(
        hospName: _nameCtrl.text.trim(),
        city:     _cityCtrl.text.trim(),
        district: _districtCtrl.text.trim(),
        state:    _stateCtrl.text.trim(),
        page:     page,
      );
      setState(() {
        _hospitals = result.hospitals;
        _meta      = result.meta;
        _page      = page;
      });
    } catch (e) {
      setState(() => _error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      setState(() => _loading = false);
    }
  }

  void _clear() {
    _nameCtrl.clear();
    _cityCtrl.clear();
    _districtCtrl.clear();
    _stateCtrl.clear();
    _load();
  }

  bool get _hasFilters =>
      _nameCtrl.text.isNotEmpty ||
      _cityCtrl.text.isNotEmpty ||
      _districtCtrl.text.isNotEmpty ||
      _stateCtrl.text.isNotEmpty;

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return RefreshIndicator(
      onRefresh: () => _load(page: _page),
      color: AppColors.primary,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(14),
        children: [
          if (_meta != null)
            Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: Row(
                children: [
                  Icon(Icons.apartment_rounded, size: 16, color: AppColors.primary),
                  const SizedBox(width: 6),
                  Text(
                    'Total: ${_meta!.total} hospitals',
                    style: TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 13,
                      color: AppColors.primary,
                    ),
                  ),
                ],
              ),
            ),
          _FilterCard(
            isOpen: _filtersOpen,
            onToggle: () => setState(() => _filtersOpen = !_filtersOpen),
            onFilter: () => _load(),
            onClear:  _hasFilters ? _clear : null,
            children: [
              _FilterField(controller: _nameCtrl,     label: 'Hospital Name', hint: 'Search hospital...'),
              _FilterField(controller: _cityCtrl,     label: 'City',          hint: 'City...'),
              _FilterField(controller: _districtCtrl, label: 'District',      hint: 'District...'),
              _FilterField(controller: _stateCtrl,    label: 'State',         hint: 'State...'),
            ],
          ),
          const SizedBox(height: 14),
          if (_loading)
            Center(child: Padding(
              padding: EdgeInsets.all(40),
              child: CircularProgressIndicator(color: AppColors.primary),
            ))
          else if (_error != null)
            AppErrorState(message: _error!, onRetry: () => _load(page: _page))
          else if (_hospitals.isEmpty)
            const AppEmptyState(message: 'No hospitals found.')
          else ...[
            ..._hospitals.map((h) => _HospitalCard(
              hospital: h,
              onView:           () => _openDetail(h),
              onSendRequest:    () => _sendRequest(h),
              onAcceptRequest:  (reqId) => _acceptRequest(h, reqId),
              onRemoveRequest:  (reqId) => _removeRequest(h, reqId),
            )),
            if (_meta != null && _meta!.lastPage > 1)
              _SimplePagination(
                current: _meta!.currentPage,
                last:    _meta!.lastPage,
                total:   _meta!.total,
                onPage:  (p) => _load(page: p),
              ),
          ],
        ],
      ),
    );
  }

  Future<void> _openDetail(ShareHospital h) async {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => _HospitalDetailSheet(hospitalId: h.id, hospitalName: h.name),
    );
  }

  Future<void> _sendRequest(ShareHospital h) async {
    final ok = await _confirm('Send request to ${h.name}?');
    if (!ok) return;
    try {
      await ShareHistoryService.instance.sendRequest(h.id);
      showAppSnackBar(context, 'Request sent to ${h.name}.', isSuccess: true);
      _load(page: _page);
    } catch (e) {
      showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  Future<void> _acceptRequest(ShareHospital h, int reqId) async {
    final ok = await _confirm('Accept connection from ${h.name}?');
    if (!ok) return;
    try {
      await ShareHistoryService.instance.acceptRequest(reqId);
      showAppSnackBar(context, 'Connected with ${h.name}.', isSuccess: true);
      _load(page: _page);
    } catch (e) {
      showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  Future<void> _removeRequest(ShareHospital h, int reqId) async {
    final ok = await _confirm('Remove connection with ${h.name}?');
    if (!ok) return;
    try {
      await ShareHistoryService.instance.removeRequest(reqId);
      showAppSnackBar(context, 'Removed.', isSuccess: true);
      _load(page: _page);
    } catch (e) {
      showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  Future<bool> _confirm(String msg) async {
    return await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        title: Text('Confirm', style: TextStyle(fontWeight: FontWeight.w900, color: AppColors.primary)),
        content: Text(msg),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))),
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Yes'),
          ),
        ],
      ),
    ) ?? false;
  }

}

// ═══════════════════════════════════════════════════════════════════════════════
// TAB 3 — Requests
// ═══════════════════════════════════════════════════════════════════════════════

class _RequestsTab extends StatefulWidget {
  final HospitalInfo hospital;
  const _RequestsTab({required this.hospital});

  @override
  State<_RequestsTab> createState() => _RequestsTabState();
}

class _RequestsTabState extends State<_RequestsTab>
    with AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;

  bool _loading = true;
  String? _error;
  ConnectionsData? _data;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final data = await ShareHistoryService.instance.fetchConnections();
      setState(() => _data = data);
    } catch (e) {
      setState(() => _error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);

    if (_loading) {
      return Center(child: CircularProgressIndicator(color: AppColors.primary));
    }
    if (_error != null) {
      return AppErrorState(message: _error!, onRetry: _load);
    }

    final data = _data!;

    return RefreshIndicator(
      onRefresh: _load,
      color: AppColors.primary,
      child: ListView(
        padding: const EdgeInsets.all(14),
        children: [
          // ── Section A: Connected Hospitals ────────────────────────
          if (data.accepted.isNotEmpty) ...[
            _SectionHeader(
              title: 'Connected Hospitals',
              trailing: '${data.accepted.length} Active',
            ),
            const SizedBox(height: 10),
            ...data.accepted.map((c) => _ConnectedCard(
              connection: c,
              onViewHistory: () => Navigator.push(
                context,
                appRoute(_PartnerPatientsScreen(
                  hospital: widget.hospital,
                  partnerTenantId: c.partner.id,
                  partnerName:     c.partner.name,
                )),
              ),
            )),
            const SizedBox(height: 20),
          ],

          // ── Section B: Incoming Requests ──────────────────────────
          _SectionHeader(
            title: 'Incoming Requests',
            badge: data.incoming.isNotEmpty ? '${data.incoming.length} NEW' : null,
          ),
          const SizedBox(height: 10),
          if (data.incoming.isEmpty)
            const AppEmptyState(message: 'No incoming requests.')
          else
            ...data.incoming.map((req) => _IncomingRequestCard(
              request: req,
              onAccept: () => _acceptRequest(req),
              onRemove: () => _removeRequest(req.id),
            )),
          const SizedBox(height: 20),

          // ── Section C: Sent Requests ──────────────────────────────
          _SectionHeader(title: 'Sent Requests'),
          const SizedBox(height: 10),
          if (data.sent.isEmpty)
            const AppEmptyState(message: 'No sent requests.')
          else
            ...data.sent.map((req) => _SentRequestCard(
              request: req,
              onViewHistory: req.status == 'accepted'
                  ? () => Navigator.push(
                      context,
                      appRoute(_PartnerPatientsScreen(
                        hospital: widget.hospital,
                        partnerTenantId: req.toTenant.id,
                        partnerName:     req.toTenant.name,
                      )),
                    )
                  : null,
              onRemove: () => _removeRequest(req.id),
            )),
        ],
      ),
    );
  }

  Future<void> _acceptRequest(IncomingRequest req) async {
    try {
      await ShareHistoryService.instance.acceptRequest(req.id);
      showAppSnackBar(context, 'Connected with ${req.fromTenant.name}.', isSuccess: true);
      _load();
    } catch (e) {
      showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  Future<void> _removeRequest(int reqId) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        title: Text('Remove', style: TextStyle(fontWeight: FontWeight.w900, color: AppColors.primary)),
        content: const Text('Are you sure you want to remove this request?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.waitRed, foregroundColor: Colors.white, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))),
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Remove'),
          ),
        ],
      ),
    ) ?? false;
    if (!ok) return;
    try {
      await ShareHistoryService.instance.removeRequest(reqId);
      showAppSnackBar(context, 'Removed.', isSuccess: true);
      _load();
    } catch (e) {
      showAppSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

}

// ═══════════════════════════════════════════════════════════════════════════════
// Partner Patients Screen (pushed from both Tab1 + Tab3)
// ═══════════════════════════════════════════════════════════════════════════════

class _PartnerPatientsScreen extends StatefulWidget {
  final HospitalInfo hospital;
  final int partnerTenantId;
  final String partnerName;

  const _PartnerPatientsScreen({
    required this.hospital,
    required this.partnerTenantId,
    required this.partnerName,
  });

  @override
  State<_PartnerPatientsScreen> createState() => _PartnerPatientsScreenState();
}

class _PartnerPatientsScreenState extends State<_PartnerPatientsScreen> {
  final _nameCtrl    = TextEditingController();
  final _doctorCtrl  = TextEditingController();
  final _contactCtrl = TextEditingController();
  final _dateCtrl    = TextEditingController();

  bool _filtersOpen = false;
  bool _loading = false;
  String? _error;
  List<HistoryPatient> _patients = [];
  ShareHistoryMeta? _meta;
  int _page = 1;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _doctorCtrl.dispose();
    _contactCtrl.dispose();
    _dateCtrl.dispose();
    super.dispose();
  }

  Future<void> _load({int page = 1}) async {
    setState(() { _loading = true; _error = null; });
    try {
      final result = await ShareHistoryService.instance.fetchPartnerPatients(
        widget.partnerTenantId,
        patientName: _nameCtrl.text.trim(),
        doctorName:  _doctorCtrl.text.trim(),
        contactNo:   _contactCtrl.text.trim(),
        date:        _dateCtrl.text,
        page:        page,
      );
      setState(() {
        _patients = result.patients;
        _meta     = result.meta;
        _page     = page;
      });
    } catch (e) {
      setState(() => _error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              widget.partnerName,
              style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 17, color: Colors.white),
            ),
            const Text(
              'Partner Hospital Patients',
              style: TextStyle(fontSize: 11, color: Colors.white70),
            ),
          ],
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.all(14),
        children: [
          _FilterCard(
            isOpen: _filtersOpen,
            onToggle: () => setState(() => _filtersOpen = !_filtersOpen),
            children: [
              _FilterField(controller: _nameCtrl,    label: 'Patient Name', hint: 'Search patient...'),
              _FilterField(controller: _doctorCtrl,  label: 'Doctor Name',  hint: 'Search doctor...'),
              _FilterField(controller: _contactCtrl, label: 'Contact No.',  hint: 'Contact...', keyboardType: TextInputType.phone),
              _DateFilterField(controller: _dateCtrl, label: 'Date'),
            ],
            onFilter: () => _load(),
            onClear: () {
              _nameCtrl.clear();
              _doctorCtrl.clear();
              _contactCtrl.clear();
              _dateCtrl.clear();
              _load();
            },
          ),
          const SizedBox(height: 14),
          if (_loading)
            Center(child: Padding(
              padding: EdgeInsets.all(40),
              child: CircularProgressIndicator(color: AppColors.primary),
            ))
          else if (_error != null)
            AppErrorState(message: _error!, onRetry: () => _load(page: _page))
          else if (_patients.isEmpty)
            const AppEmptyState(message: 'No patient records found.')
          else ...[
            ..._patients.map((p) => _HistoryPatientCard(patient: p, onView: null)),
            if (_meta != null && _meta!.lastPage > 1)
              _SimplePagination(
                current: _meta!.currentPage,
                last:    _meta!.lastPage,
                total:   _meta!.total,
                onPage:  (p) => _load(page: p),
              ),
          ],
        ],
      ),
    );
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Shared card widgets
// ═══════════════════════════════════════════════════════════════════════════════

class _FilterCard extends StatelessWidget {
  final bool isOpen;
  final VoidCallback onToggle;
  final List<Widget> children;
  final VoidCallback onFilter;
  final VoidCallback? onClear;

  const _FilterCard({
    required this.isOpen,
    required this.onToggle,
    required this.children,
    required this.onFilter,
    this.onClear,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: AppColors.primaryA10),
        boxShadow: [BoxShadow(color: AppColors.primaryA06, blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          InkWell(
            onTap: onToggle,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(AppRadius.lg)),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              child: Row(
                children: [
                  Icon(Icons.filter_list_rounded, size: 20, color: AppColors.primary),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text('Filters', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15, color: AppColors.primary)),
                  ),
                  AnimatedRotation(
                    turns: isOpen ? 0.5 : 0,
                    duration: const Duration(milliseconds: 200),
                    child: Icon(Icons.expand_more_rounded, size: 22, color: AppColors.primaryA22),
                  ),
                ],
              ),
            ),
          ),
          AnimatedCrossFade(
            firstChild: const SizedBox.shrink(),
            secondChild: Padding(
              padding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
              child: Column(
                children: [
                  for (int i = 0; i < children.length; i += 2)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(child: children[i]),
                          if (i + 1 < children.length) ...[
                            const SizedBox(width: 10),
                            Expanded(child: children[i + 1]),
                          ] else
                            const Expanded(child: SizedBox()),
                        ],
                      ),
                    ),
                  const SizedBox(height: 2),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      if (onClear != null)
                        OutlinedButton(
                          onPressed: onClear,
                          style: OutlinedButton.styleFrom(
                            foregroundColor: AppColors.primary,
                            side: BorderSide(color: AppColors.primaryA22),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                          ),
                          child: const Text('Clear'),
                        ),
                      if (onClear != null) const SizedBox(width: 10),
                      ElevatedButton.icon(
                        onPressed: onFilter,
                        icon: const Icon(Icons.search_rounded, size: 16),
                        label: const Text('Filter'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          foregroundColor: Colors.white,
                          elevation: 0,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            crossFadeState: isOpen ? CrossFadeState.showSecond : CrossFadeState.showFirst,
            duration: const Duration(milliseconds: 200),
          ),
        ],
      ),
    );
  }
}

class _FilterField extends StatelessWidget {
  final TextEditingController controller;
  final String label;
  final String hint;
  final TextInputType? keyboardType;

  const _FilterField({
    required this.controller,
    required this.label,
    required this.hint,
    this.keyboardType,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label.toUpperCase(),
          style: TextStyle(fontSize: 10, fontWeight: FontWeight.w900, color: AppColors.primary, letterSpacing: 0.8),
        ),
        const SizedBox(height: 4),
        TextField(
          controller: controller,
          keyboardType: keyboardType,
          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: TextStyle(fontSize: 12, color: Colors.grey[400]),
            filled: true,
            fillColor: AppColors.primaryA06,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: BorderSide.none,
            ),
            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            isDense: true,
          ),
        ),
      ],
    );
  }
}

class _DateFilterField extends StatelessWidget {
  final TextEditingController controller;
  final String label;

  const _DateFilterField({required this.controller, required this.label});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label.toUpperCase(),
          style: TextStyle(fontSize: 10, fontWeight: FontWeight.w900, color: AppColors.primary, letterSpacing: 0.8),
        ),
        const SizedBox(height: 4),
        TextField(
          controller: controller,
          readOnly: true,
          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
          onTap: () async {
            final picked = await showDatePicker(
              context: context,
              initialDate: DateTime.now(),
              firstDate: DateTime(2020),
              lastDate: DateTime.now(),
              builder: (ctx, child) => Theme(
                data: Theme.of(ctx).copyWith(
                  colorScheme: ColorScheme.light(primary: AppColors.primary),
                ),
                child: child!,
              ),
            );
            if (picked != null) {
              controller.text = '${picked.year}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';
            }
          },
          decoration: InputDecoration(
            hintText: 'Select date...',
            hintStyle: TextStyle(fontSize: 12, color: Colors.grey[400]),
            suffixIcon: controller.text.isEmpty
                ? Icon(Icons.calendar_today_rounded, size: 16, color: Colors.grey[400])
                : IconButton(
                    icon: Icon(Icons.close_rounded, size: 16, color: Colors.grey[400]),
                    onPressed: () => controller.clear(),
                  ),
            filled: true,
            fillColor: AppColors.primaryA06,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: BorderSide.none,
            ),
            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            isDense: true,
          ),
        ),
      ],
    );
  }
}

// ── History Patient Card ──────────────────────────────────────────────────────

class _HistoryPatientCard extends StatelessWidget {
  final HistoryPatient patient;
  final VoidCallback? onView;

  const _HistoryPatientCard({required this.patient, required this.onView});

  @override
  Widget build(BuildContext context) {
    final p = patient;
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: AppColors.primaryA10),
        boxShadow: [BoxShadow(color: AppColors.primaryA06, blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                _HospBadge(isOwn: p.isOwn, name: p.tenantName),
                const SizedBox(width: 8),
                Text(
                  'MRD: ${p.patientCode}',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: AppColors.primary,
                    fontFamily: 'monospace',
                  ),
                ),
              ],
            ),
            const SizedBox(height: 6),
            Text(
              p.fullName,
              style: TextStyle(fontWeight: FontWeight.w900, fontSize: 15, color: AppColors.primary),
            ),
            const SizedBox(height: 4),
            Row(
              children: [
                Icon(Icons.person_outline_rounded, size: 13, color: Colors.grey[400]),
                const SizedBox(width: 4),
                Text(
                  p.doctorName ?? '—',
                  style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                ),
                Text('  ·  ', style: TextStyle(color: Colors.grey[400])),
                Icon(Icons.calendar_today_rounded, size: 13, color: Colors.grey[400]),
                const SizedBox(width: 4),
                Text(
                  _formatDate(p.appointmentDate),
                  style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                ),
              ],
            ),
            const SizedBox(height: 2),
            Row(
              children: [
                Icon(Icons.phone_outlined, size: 13, color: Colors.grey[400]),
                const SizedBox(width: 4),
                Text(p.contactNo, style: TextStyle(fontSize: 12, color: Colors.grey[600])),
                Text('  ·  ', style: TextStyle(color: Colors.grey[400])),
                Text('${p.age}y', style: TextStyle(fontSize: 12, color: Colors.grey[600])),
              ],
            ),
            if (onView != null) ...[
              const SizedBox(height: 10),
              Align(
                alignment: Alignment.centerRight,
                child: TextButton.icon(
                  onPressed: onView,
                  icon: const Icon(Icons.arrow_forward_rounded, size: 15),
                  label: const Text('View', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 13)),
                  style: TextButton.styleFrom(
                    foregroundColor: AppColors.primary,
                    backgroundColor: AppColors.primaryA08,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

// ── Hospital Card ─────────────────────────────────────────────────────────────

class _HospitalCard extends StatelessWidget {
  final ShareHospital hospital;
  final VoidCallback onView;
  final VoidCallback onSendRequest;
  final void Function(int) onAcceptRequest;
  final void Function(int) onRemoveRequest;

  const _HospitalCard({
    required this.hospital,
    required this.onView,
    required this.onSendRequest,
    required this.onAcceptRequest,
    required this.onRemoveRequest,
  });

  @override
  Widget build(BuildContext context) {
    final h   = hospital;
    final req = h.requestInfo;

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: AppColors.primaryA10),
        boxShadow: [BoxShadow(color: AppColors.primaryA06, blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(color: AppColors.primaryA08, borderRadius: BorderRadius.circular(10)),
                  child: Icon(Icons.apartment_rounded, color: AppColors.primary, size: 20),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(h.name, style: TextStyle(fontWeight: FontWeight.w900, fontSize: 14, color: AppColors.primary)),
                      if ((h.city ?? '').isNotEmpty)
                        Text(
                          [h.city, h.district, h.state].where((s) => (s ?? '').isNotEmpty).join(' · '),
                          style: TextStyle(fontSize: 11, color: Colors.grey[500]),
                        ),
                    ],
                  ),
                ),
                _ReqStatusBadge(req: req),
              ],
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                OutlinedButton.icon(
                  onPressed: onView,
                  icon: const Icon(Icons.visibility_outlined, size: 15),
                  label: const Text('View', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.primary,
                    side: BorderSide(color: AppColors.primaryA22),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
                ),
                const SizedBox(width: 8),
                ..._actionButtons(req),
              ],
            ),
          ],
        ),
      ),
    );
  }

  List<Widget> _actionButtons(ShareRequestInfo? req) {
    if (req == null) {
      return [
        ElevatedButton(
          onPressed: onSendRequest,
          style: ElevatedButton.styleFrom(
            backgroundColor: AppColors.primary, foregroundColor: Colors.white, elevation: 0,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          ),
          child: const Text('Request', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800)),
        ),
      ];
    }
    if (req.status == 'accepted') {
      return [
        OutlinedButton(
          onPressed: () => onRemoveRequest(req.id),
          style: OutlinedButton.styleFrom(
            foregroundColor: AppColors.waitRed, side: BorderSide(color: AppColors.waitRed),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          ),
          child: const Text('✕ Remove', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800)),
        ),
      ];
    }
    if (req.direction == 'sent') {
      return [
        OutlinedButton(
          onPressed: () => onRemoveRequest(req.id),
          style: OutlinedButton.styleFrom(
            foregroundColor: Colors.grey[600], side: BorderSide(color: Colors.grey.shade300),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          ),
          child: const Text('✕ Cancel', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800)),
        ),
      ];
    }
    // received
    return [
      ElevatedButton(
        onPressed: () => onAcceptRequest(req.id),
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.waitGreen, foregroundColor: Colors.white, elevation: 0,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        ),
        child: const Text('✓ Accept', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800)),
      ),
      const SizedBox(width: 6),
      OutlinedButton(
        onPressed: () => onRemoveRequest(req.id),
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.waitRed, side: BorderSide(color: AppColors.waitRed),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
        ),
        child: const Text('✕', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800)),
      ),
    ];
  }
}

// ── Hospital Detail Bottom Sheet ──────────────────────────────────────────────

class _HospitalDetailSheet extends StatefulWidget {
  final int hospitalId;
  final String hospitalName;

  const _HospitalDetailSheet({required this.hospitalId, required this.hospitalName});

  @override
  State<_HospitalDetailSheet> createState() => _HospitalDetailSheetState();
}

class _HospitalDetailSheetState extends State<_HospitalDetailSheet> {
  bool _loading = true;
  HospitalDetail? _detail;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final d = await ShareHistoryService.instance.fetchHospitalDetail(widget.hospitalId);
      setState(() => _detail = d);
    } catch (e) {
      setState(() => _error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Center(child: Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.grey[300], borderRadius: BorderRadius.circular(2)))),
          const SizedBox(height: 16),
          Text(widget.hospitalName, style: TextStyle(fontWeight: FontWeight.w900, fontSize: 17, color: AppColors.primary)),
          if (_loading)
            Padding(
              padding: EdgeInsets.symmetric(vertical: 40),
              child: Center(child: CircularProgressIndicator(color: AppColors.primary)),
            )
          else if (_error != null)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 20),
              child: Text(_error!, style: TextStyle(color: Colors.red[400])),
            )
          else if (_detail != null) ...[
            const SizedBox(height: 4),
            Text('${_detail!.city}, ${_detail!.state}', style: TextStyle(fontSize: 12, color: Colors.grey[500])),
            const SizedBox(height: 16),
            Row(
              children: [
                _DetailStat(label: 'Doctors',  value: '${_detail!.doctorsCount}'),
                _vDivider,
                _DetailStat(label: 'Staff',    value: '${_detail!.staffCount}'),
                _vDivider,
                _DetailStat(label: 'Patients', value: '${_detail!.patientsCount}'),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Icon(Icons.email_outlined, size: 14, color: Colors.grey[400]),
                const SizedBox(width: 6),
                Text(_detail!.adminEmail, style: TextStyle(fontSize: 12, color: Colors.grey[600])),
              ],
            ),
          ],
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton(
              onPressed: () => Navigator.pop(context),
              style: OutlinedButton.styleFrom(
                foregroundColor: AppColors.primary,
                side: BorderSide(color: AppColors.primaryA22),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                padding: const EdgeInsets.symmetric(vertical: 14),
              ),
              child: const Text('Close', style: TextStyle(fontWeight: FontWeight.w700)),
            ),
          ),
        ],
      ),
    );
  }

  static final _vDivider = Container(width: 1, height: 36, color: const Color(0xFFE2E8F0), margin: const EdgeInsets.symmetric(horizontal: 12));
}

class _DetailStat extends StatelessWidget {
  final String label;
  final String value;
  const _DetailStat({required this.label, required this.value});

  @override
  Widget build(BuildContext context) => Expanded(
    child: Column(
      children: [
        Text(value, style: TextStyle(fontWeight: FontWeight.w900, fontSize: 22, color: AppColors.primary)),
        Text(label, style: TextStyle(fontSize: 11, color: Colors.grey[500])),
      ],
    ),
  );
}

// ── Requests tab sub-cards ────────────────────────────────────────────────────

class _ConnectedCard extends StatelessWidget {
  final ShareConnection connection;
  final VoidCallback onViewHistory;

  const _ConnectedCard({required this.connection, required this.onViewHistory});

  @override
  Widget build(BuildContext context) {
    final p = connection.partner;
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: const Color(0xFFF0FDFA),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF0D9488), width: 1.5),
      ),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(color: const Color(0xFF0D9488), borderRadius: BorderRadius.circular(AppRadius.md)),
                child: const Icon(Icons.apartment_rounded, color: Colors.white, size: 22),
              ),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(p.name, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 14, color: Color(0xFF0F766E))),
                  Text(
                    [p.city, p.state].where((s) => (s ?? '').isNotEmpty).join(', '),
                    style: TextStyle(fontSize: 11, color: Colors.grey[500]),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 10),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: onViewHistory,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF0D9488), foregroundColor: Colors.white, elevation: 0,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                padding: const EdgeInsets.symmetric(vertical: 10),
              ),
              child: const Text('View Patient History →', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 13)),
            ),
          ),
        ],
      ),
    );
  }
}

class _IncomingRequestCard extends StatelessWidget {
  final IncomingRequest request;
  final VoidCallback onAccept;
  final VoidCallback onRemove;

  const _IncomingRequestCard({required this.request, required this.onAccept, required this.onRemove});

  @override
  Widget build(BuildContext context) {
    final f = request.fromTenant;
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border(left: BorderSide(color: AppColors.primary, width: 4)),
        boxShadow: [BoxShadow(color: AppColors.primaryA06, blurRadius: 8, offset: const Offset(0, 2))],
      ),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(color: AppColors.primaryA10, borderRadius: BorderRadius.circular(11)),
                child: Icon(Icons.move_to_inbox_rounded, color: AppColors.primary, size: 20),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(f.name, style: TextStyle(fontWeight: FontWeight.w900, fontSize: 14, color: AppColors.primary)),
                    Text(
                      [f.city, f.state].where((s) => (s ?? '').isNotEmpty).join(', '),
                      style: TextStyle(fontSize: 11, color: Colors.grey[500]),
                    ),
                    if (request.createdAt.isNotEmpty)
                      Text('Requested: ${_formatDate(request.createdAt)}', style: TextStyle(fontSize: 10, color: Colors.grey[400])),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: ElevatedButton(
                  onPressed: onAccept,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.waitGreen, foregroundColor: Colors.white, elevation: 0,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    padding: const EdgeInsets.symmetric(vertical: 10),
                  ),
                  child: const Text('✓ Accept', style: TextStyle(fontWeight: FontWeight.w800)),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: OutlinedButton(
                  onPressed: onRemove,
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.waitRed, side: BorderSide(color: AppColors.waitRed),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    padding: const EdgeInsets.symmetric(vertical: 10),
                  ),
                  child: const Text('✕ Remove', style: TextStyle(fontWeight: FontWeight.w800)),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _SentRequestCard extends StatelessWidget {
  final SentRequest request;
  final VoidCallback? onViewHistory;
  final VoidCallback onRemove;

  const _SentRequestCard({required this.request, this.onViewHistory, required this.onRemove});

  @override
  Widget build(BuildContext context) {
    final t = request.toTenant;
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.primaryA10),
        boxShadow: [BoxShadow(color: AppColors.primaryA06, blurRadius: 6, offset: const Offset(0, 2))],
      ),
      padding: const EdgeInsets.all(14),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(color: AppColors.primaryA08, borderRadius: BorderRadius.circular(10)),
            child: Icon(Icons.apartment_rounded, color: AppColors.primary, size: 18),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(t.name, style: TextStyle(fontWeight: FontWeight.w800, fontSize: 13, color: AppColors.primary)),
                Text(
                  [t.city, t.state].where((s) => (s ?? '').isNotEmpty).join(', '),
                  style: TextStyle(fontSize: 10, color: Colors.grey[500]),
                ),
                if (request.createdAt.isNotEmpty)
                  Text('Sent: ${_formatDate(request.createdAt)}', style: TextStyle(fontSize: 10, color: Colors.grey[400])),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              _SentStatusBadge(status: request.status),
              const SizedBox(height: 6),
              Row(
                children: [
                  if (onViewHistory != null)
                    TextButton(
                      onPressed: onViewHistory,
                      style: TextButton.styleFrom(
                        foregroundColor: Colors.white,
                        backgroundColor: const Color(0xFF0D9488),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.sm)),
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                        minimumSize: Size.zero,
                      ),
                      child: const Text('View', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800)),
                    ),
                  if (onViewHistory != null) const SizedBox(width: 6),
                  InkWell(
                    onTap: onRemove,
                    borderRadius: BorderRadius.circular(AppRadius.sm),
                    child: Container(
                      padding: const EdgeInsets.all(6),
                      decoration: BoxDecoration(
                        border: Border.all(color: AppColors.waitRed),
                        borderRadius: BorderRadius.circular(AppRadius.sm),
                      ),
                      child: Icon(Icons.delete_outline_rounded, size: 14, color: AppColors.waitRed),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }
}

// ── Small reusable pieces ─────────────────────────────────────────────────────

class _HospBadge extends StatelessWidget {
  final bool isOwn;
  final String? name;

  const _HospBadge({required this.isOwn, this.name});

  @override
  Widget build(BuildContext context) {
    const ownBg   = Color(0xFFE0F2FE);
    const ownText = Color(0xFF0369A1);
    final partBg  = const Color(0xFFF0FDF4);
    final partText = const Color(0xFF166534);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: isOwn ? ownBg : partBg,
        borderRadius: BorderRadius.circular(AppRadius.xl),
      ),
      child: Text(
        isOwn ? 'OWN' : (name ?? 'Partner'),
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w900,
          color: isOwn ? ownText : partText,
          letterSpacing: 0.5,
        ),
      ),
    );
  }
}

class _ReqStatusBadge extends StatelessWidget {
  final ShareRequestInfo? req;
  const _ReqStatusBadge({this.req});

  @override
  Widget build(BuildContext context) {
    if (req == null) return const SizedBox.shrink();

    String label; Color bg; Color text;
    if (req!.status == 'accepted') {
      label = '✓ Connected'; bg = const Color(0xFFD1FAE5); text = const Color(0xFF065F46);
    } else if (req!.direction == 'sent') {
      label = '⏰ Requested'; bg = const Color(0xFFFEF3C7); text = const Color(0xFF92400E);
    } else {
      label = '🔔 Incoming'; bg = const Color(0xFFEDE9FE); text = const Color(0xFF5B21B6);
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(AppRadius.xl)),
      child: Text(label, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w900, color: text)),
    );
  }
}

class _SentStatusBadge extends StatelessWidget {
  final String status;
  const _SentStatusBadge({required this.status});

  @override
  Widget build(BuildContext context) {
    String label; Color bg; Color text;
    switch (status) {
      case 'accepted':
        label = '✓ Connected'; bg = const Color(0xFFD1FAE5); text = const Color(0xFF065F46);
      case 'rejected':
        label = '✕ Rejected'; bg = const Color(0xFFFEE2E2); text = const Color(0xFF991B1B);
      default:
        label = '⏰ Pending'; bg = const Color(0xFFFEF3C7); text = const Color(0xFF92400E);
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(AppRadius.xl)),
      child: Text(label, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w900, color: text)),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  final String title;
  final String? trailing;
  final String? badge;

  const _SectionHeader({required this.title, this.trailing, this.badge});

  @override
  Widget build(BuildContext context) => Row(
    children: [
      Text(title, style: TextStyle(fontWeight: FontWeight.w900, fontSize: 15, color: AppColors.primary)),
      if (badge != null) ...[
        const SizedBox(width: 8),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
          decoration: BoxDecoration(color: AppColors.waitRed, borderRadius: BorderRadius.circular(AppRadius.xl)),
          child: Text(badge!, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w900, color: Colors.white)),
        ),
      ],
      const Spacer(),
      if (trailing != null)
        Text(trailing!, style: TextStyle(fontSize: 12, color: Colors.grey[500])),
    ],
  );
}

class _SimplePagination extends StatelessWidget {
  final int current;
  final int last;
  final int total;
  final void Function(int) onPage;

  const _SimplePagination({
    required this.current,
    required this.last,
    required this.total,
    required this.onPage,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(vertical: 10),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.primaryA10),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text('$total results · Page $current/$last', style: TextStyle(fontSize: 11, color: Colors.grey[600])),
          Row(
            children: [
              _PagBtn(icon: Icons.chevron_left_rounded,  enabled: current > 1,    onTap: () => onPage(current - 1)),
              const SizedBox(width: 6),
              _PagBtn(icon: Icons.chevron_right_rounded, enabled: current < last, onTap: () => onPage(current + 1)),
            ],
          ),
        ],
      ),
    );
  }
}

class _PagBtn extends StatelessWidget {
  final IconData icon;
  final bool enabled;
  final VoidCallback onTap;

  const _PagBtn({required this.icon, required this.enabled, required this.onTap});

  @override
  Widget build(BuildContext context) => GestureDetector(
    onTap: enabled ? onTap : null,
    child: Container(
      width: 30,
      height: 30,
      decoration: BoxDecoration(
        color: enabled ? AppColors.primaryA10 : Colors.grey.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(AppRadius.sm),
      ),
      child: Icon(icon, size: 18, color: enabled ? AppColors.primary : Colors.grey[400]),
    ),
  );
}


// ── Helpers ───────────────────────────────────────────────────────────────────

String _formatDate(String iso) {
  if (iso.isEmpty) return '—';
  try {
    final d = DateTime.parse(iso);
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return '${d.day} ${months[d.month - 1]} ${d.year}';
  } catch (_) {
    return iso;
  }
}
