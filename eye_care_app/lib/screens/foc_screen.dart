import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_radius.dart';
import '../widgets/app_animations.dart';
import '../widgets/app_empty_state.dart';
import '../constants/permissions.dart';
import '../models/auth_models.dart';
import '../models/foc_models.dart';
import '../services/foc_service.dart';
import '../services/permission_service.dart';


class FocScreen extends StatefulWidget {
  final UserInfo user;
  final HospitalInfo hospital;
  final VoidCallback? onMenuTap;

  const FocScreen({
    super.key,
    required this.user,
    required this.hospital,
    this.onMenuTap,
  });

  @override
  State<FocScreen> createState() => _FocScreenState();
}

class _FocScreenState extends State<FocScreen> {
  final _p = PermissionService.instance;

  bool _loading = false;
  String? _error;
  FocListResult? _result;
  String? _filterStatus; // null = all, 'pending', 'accepted', 'rejected'

  bool get _canCreate => _p.can(Perm.opdFocCreate);
  bool get _canAccept => _p.can(Perm.opdFocAccept);

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final r = await FocService.instance.fetchFocs(status: _filterStatus);
      if (mounted) setState(() { _result = r; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString(); _loading = false; });
    }
  }

  // ── Create FOC Dialog (doctor action) ─────────────────────────────────────

  Future<void> _showCreateDialog() async {
    final patientIdCtrl  = TextEditingController();
    final focFeeCtrl     = TextEditingController();
    final reasonCtrl     = TextEditingController();
    final formKey        = GlobalKey<FormState>();
    bool submitting      = false;

    await showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDlg) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
          title: Text('New FOC Request',
              style: TextStyle(fontWeight: FontWeight.w600, color: AppColors.primary)),
          content: SizedBox(
            width: 360,
            child: Form(
              key: formKey,
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                _field(patientIdCtrl, 'Patient ID',
                    hint: 'Enter patient ID',
                    keyboardType: TextInputType.number,
                    validator: (v) =>
                        (v == null || v.trim().isEmpty) ? 'Required' : null),
                const SizedBox(height: 12),
                _field(focFeeCtrl, 'FOC Fee (₹)',
                    hint: '0.00',
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    validator: (v) {
                      if (v == null || v.trim().isEmpty) return 'Required';
                      if (double.tryParse(v.trim()) == null) return 'Enter valid amount';
                      return null;
                    }),
                const SizedBox(height: 12),
                _field(reasonCtrl, 'Reason',
                    hint: 'Reason for waiving fee…',
                    maxLines: 3,
                    validator: (v) =>
                        (v == null || v.trim().isEmpty) ? 'Required' : null),
              ]),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary),
              onPressed: submitting
                  ? null
                  : () async {
                      if (!formKey.currentState!.validate()) return;
                      setDlg(() => submitting = true);
                      try {
                        await FocService.instance.createFoc(
                          patientId: int.parse(patientIdCtrl.text.trim()),
                          focFee: double.parse(focFeeCtrl.text.trim()),
                          reason: reasonCtrl.text.trim(),
                        );
                        if (ctx.mounted) Navigator.pop(ctx);
                        _load();
                        if (mounted) {
                          showAppSnackBar(context, 'FOC request submitted.', isSuccess: true);
                        }
                      } catch (e) {
                        setDlg(() => submitting = false);
                        if (ctx.mounted) {
                          showAppSnackBar(ctx, e.toString(), isError: true);
                        }
                      }
                    },
              child: submitting
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white))
                  : const Text('Submit',
                      style: TextStyle(color: Colors.white)),
            ),
          ],
        ),
      ),
    );

    patientIdCtrl.dispose();
    focFeeCtrl.dispose();
    reasonCtrl.dispose();
  }

  // ── Reject Dialog (reception action) ──────────────────────────────────────

  Future<void> _showRejectDialog(FocItem foc) async {
    final reasonCtrl = TextEditingController();
    final formKey    = GlobalKey<FormState>();
    bool submitting  = false;

    await showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDlg) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
          title: const Text('Reject FOC Request',
              style: TextStyle(fontWeight: FontWeight.w600, color: AppColors.red)),
          content: Form(
            key: formKey,
            child: _field(reasonCtrl, 'Reason for rejection',
                maxLines: 3,
                validator: (v) =>
                    (v == null || v.trim().isEmpty) ? 'Required' : null),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: AppColors.red),
              onPressed: submitting
                  ? null
                  : () async {
                      if (!formKey.currentState!.validate()) return;
                      setDlg(() => submitting = true);
                      try {
                        await FocService.instance.rejectFoc(
                            foc.id, reasonCtrl.text.trim());
                        if (ctx.mounted) Navigator.pop(ctx);
                        _load();
                      } catch (e) {
                        setDlg(() => submitting = false);
                        if (ctx.mounted) {
                          showAppSnackBar(ctx, e.toString(), isError: true);
                        }
                        _load();
                      }
                    },
              child: submitting
                  ? const SizedBox(
                      width: 18, height: 18,
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white))
                  : const Text('Reject',
                      style: TextStyle(color: Colors.white)),
            ),
          ],
        ),
      ),
    );
    reasonCtrl.dispose();
  }

  // ── Accept (reception action) ──────────────────────────────────────────────

  Future<void> _accept(FocItem foc) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.md)),
        title: const Text('Accept FOC Request'),
        content: Text(
          'Accept FOC for ${foc.patient?.fullName ?? "Patient #${foc.patientId}"}?'
          '\nFee of ₹${foc.focFee.toStringAsFixed(2)} will be waived.',
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.green),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Accept',
                style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      await FocService.instance.acceptFoc(foc.id);
      _load();
      if (mounted) {
        showAppSnackBar(context, 'FOC accepted. Fee waived.', isSuccess: true);
      }
    } catch (e) {
      if (mounted) {
        showAppSnackBar(context, e.toString(), isError: true);
      }
      _load();
    }
  }

  // ── Build ──────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F8FA),
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        title: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          const Text('FOC Requests',
              style: TextStyle(fontWeight: FontWeight.w700, fontSize: 17)),
          Text(widget.hospital.name,
              style: const TextStyle(fontSize: 11, color: Colors.white70)),
        ]),
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
                onPressed: widget.onMenuTap)
            : null,
      ),
      floatingActionButton: _canCreate
          ? FloatingActionButton.extended(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              icon: const Icon(Icons.add),
              label: const Text('FOC Request'),
              onPressed: _showCreateDialog,
            )
          : null,
      body: Column(children: [
        _buildFilterBar(),
        Expanded(child: _buildBody()),
      ]),
    );
  }

  Widget _buildFilterBar() {
    final statuses = <String?>['All', 'pending', 'accepted', 'rejected'];
    final labels   = ['All', 'Pending', 'Accepted', 'Rejected'];
    final colors   = [AppColors.primary, Color(0xFFD4A017), AppColors.green, AppColors.red];

    return Container(
      color: Colors.white,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      child: Row(
        children: List.generate(statuses.length, (i) {
          final sel = _filterStatus == (statuses[i] == 'All' ? null : statuses[i]);
          return Padding(
            padding: const EdgeInsets.only(right: 8),
            child: ChoiceChip(
              label: Text(labels[i],
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight:
                        sel ? FontWeight.w700 : FontWeight.w400,
                    color: sel ? Colors.white : colors[i],
                  )),
              selected: sel,
              selectedColor: colors[i],
              backgroundColor: Colors.grey.shade100,
              side: BorderSide(color: sel ? colors[i] : Colors.grey.shade300),
              onSelected: (_) {
                setState(() {
                  _filterStatus = statuses[i] == 'All' ? null : statuses[i];
                });
                _load();
              },
            ),
          );
        }),
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null) {
      return Center(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          const Icon(Icons.error_outline, color: AppColors.red, size: 48),
          const SizedBox(height: 8),
          Text(_error!, textAlign: TextAlign.center,
              style: const TextStyle(color: AppColors.red)),
          const SizedBox(height: 12),
          ElevatedButton.icon(
            onPressed: _load,
            icon: const Icon(Icons.refresh),
            label: const Text('Retry'),
          ),
        ]),
      );
    }
    if (_result == null || _result!.items.isEmpty) {
      return const AppEmptyState(
        message: 'No FOC requests found.',
        icon: Icons.receipt_long_outlined,
      );
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.builder(
        padding: const EdgeInsets.fromLTRB(12, 8, 12, 88),
        itemCount: _result!.items.length,
        itemBuilder: (_, i) => _buildCard(_result!.items[i]),
      ),
    );
  }

  Widget _buildCard(FocItem foc) {
    Color statusColor;
    String statusLabel;
    Color statusBg;

    switch (foc.status) {
      case 'accepted':
        statusColor = AppColors.green;
        statusBg    = AppColors.green.withValues(alpha: 0.10);
        statusLabel = 'Accepted';
        break;
      case 'rejected':
        statusColor = AppColors.red;
        statusBg    = AppColors.red.withValues(alpha: 0.10);
        statusLabel = 'Rejected';
        break;
      default:
        statusColor = const Color(0xFFD4A017);
        statusBg    = Color(0xFFD4A017).withValues(alpha: 0.12);
        statusLabel = 'Pending';
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      elevation: 1.5,
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          // Header row
          Row(children: [
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(
                  foc.patient?.fullName ?? 'Patient #${foc.patientId}',
                  style: TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: 15,
                      color: AppColors.primary),
                ),
                if (foc.patient?.patientCode.isNotEmpty == true)
                  Text(foc.patient!.patientCode,
                      style: TextStyle(
                          fontSize: 11,
                          color: Colors.grey.shade600)),
              ]),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: statusBg,
                borderRadius: BorderRadius.circular(AppRadius.xl),
              ),
              child: Text(statusLabel,
                  style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: statusColor)),
            ),
          ]),
          const SizedBox(height: 10),
          // Info row
          Wrap(spacing: 16, runSpacing: 4, children: [
            _infoChip(Icons.currency_rupee,
                foc.focFee.toStringAsFixed(2), AppColors.orange),
            if (foc.doctor != null)
              _infoChip(Icons.person_outline,
                  'Dr. ${foc.doctor!.name}', AppColors.primary),
          ]),
          const SizedBox(height: 8),
          Text(foc.reason,
              style: TextStyle(fontSize: 13, color: Colors.grey.shade800)),
          if (foc.isRejected && foc.rejectedReason != null) ...[
            const SizedBox(height: 6),
            Text('Rejection: ${foc.rejectedReason}',
                style: const TextStyle(fontSize: 12, color: AppColors.red)),
          ],
          if (foc.isAccepted && foc.acceptedByUser != null) ...[
            const SizedBox(height: 6),
            Text('Accepted by: ${foc.acceptedByUser!.name}',
                style: const TextStyle(fontSize: 12, color: AppColors.green)),
          ],
          // Action buttons (reception only, pending only)
          if (_canAccept && foc.isPending) ...[
            const SizedBox(height: 12),
            Row(children: [
              Expanded(
                child: OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(
                      foregroundColor: AppColors.red,
                      side: const BorderSide(color: AppColors.red)),
                  icon: const Icon(Icons.close, size: 16),
                  label: const Text('Reject'),
                  onPressed: () => _showRejectDialog(foc),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.green,
                      foregroundColor: Colors.white),
                  icon: const Icon(Icons.check, size: 16),
                  label: const Text('Accept'),
                  onPressed: () => _accept(foc),
                ),
              ),
            ]),
          ],
        ]),
      ),
    );
  }

  Widget _infoChip(IconData icon, String label, Color color) {
    return Row(mainAxisSize: MainAxisSize.min, children: [
      Icon(icon, size: 13, color: color),
      const SizedBox(width: 4),
      Text(label,
          style: TextStyle(fontSize: 12, color: color, fontWeight: FontWeight.w500)),
    ]);
  }

  Widget _field(
    TextEditingController ctrl,
    String label, {
    String? hint,
    int? maxLines,
    TextInputType? keyboardType,
    String? Function(String?)? validator,
  }) {
    return TextFormField(
      controller: ctrl,
      maxLines: maxLines ?? 1,
      keyboardType: keyboardType,
      validator: validator,
      decoration: InputDecoration(
        labelText: label,
        hintText: hint,
        border: const OutlineInputBorder(),
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      ),
    );
  }
}
