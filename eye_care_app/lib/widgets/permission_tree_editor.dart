import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../models/role_models.dart';

/// Web parity (`permission_matrix.blade.php`'s cross-permission JS,
/// confirmed 2026-09-21): granting Secondary Exam also grants OT → Surgery
/// → Recommend — a doctor authorized for secondary exams is assumed to
/// also be authorized to recommend surgery. One-way: revoking Secondary
/// Exam does NOT revoke Surgery Recommend, matching web exactly.
void _applyExamSecondaryCoupling(Map<String, PermissionModuleGroup> permissions, String toggledAction, bool granted) {
  if (toggledAction != 'exam_secondary' || !granted) return;
  for (final module in permissions.values) {
    for (final feature in module.features.values) {
      for (var i = 0; i < feature.permissions.length; i++) {
        if (feature.permissions[i].action == 'ot_surgery_recommend') {
          feature.permissions[i] = feature.permissions[i].copyWith(isGranted: true);
        }
      }
    }
  }
}

/// Renders a role's permissions as module → feature → checkbox, mirroring
/// the web's `permission_matrix.blade.php` structure (ExpansionTile
/// accordion per module — mobile's compact layout).
///
/// Used by [RoleEditorScreen] for both the create-role and edit-role flows
/// — same widget either way, just fed a differently-sourced but
/// identically-shaped `Map<String, PermissionModuleGroup>` (role_models.dart's
/// dual-shape parsing already normalizes both backend endpoints into this
/// one shape before it reaches here).
///
/// Mutates [permissions]' nested lists in place and manages its own
/// setState for the checkbox UI — the caller reads the final grants back
/// out through the same map reference when saving, so no change callback
/// is needed.
class PermissionTreeEditor extends StatefulWidget {
  final Map<String, PermissionModuleGroup> permissions;
  final bool enabled;

  const PermissionTreeEditor({
    super.key,
    required this.permissions,
    this.enabled = true,
  });

  @override
  State<PermissionTreeEditor> createState() => _PermissionTreeEditorState();
}

class _PermissionTreeEditorState extends State<PermissionTreeEditor> {
  @override
  Widget build(BuildContext context) {
    return Column(
      children: widget.permissions.entries
          .map((entry) => _buildModuleCard(entry.value))
          .toList(),
    );
  }

  Widget _buildModuleCard(PermissionModuleGroup module) {
    final allPerms = module.allPermissions;
    final grantedCount = allPerms.where((p) => p.isGranted).length;

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Card(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        elevation: 1.5,
        child: Theme(
          data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
          child: ExpansionTile(
            initiallyExpanded: grantedCount > 0,
            tilePadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            title: Row(children: [
              Text(
                module.label,
                style: TextStyle(
                    fontWeight: FontWeight.w700, color: AppColors.primary, fontSize: 14),
              ),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: grantedCount > 0 ? AppColors.green.withValues(alpha: 0.10) : AppColors.primaryA08,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  '$grantedCount / ${allPerms.length}',
                  style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color:
                          grantedCount > 0 ? AppColors.green : Colors.grey.shade600),
                ),
              ),
            ]),
            trailing: Row(mainAxisSize: MainAxisSize.min, children: [
              // Toggle-all button
              TextButton(
                onPressed: widget.enabled ? () {
                  final allGranted = allPerms.every((p) => p.isGranted);
                  setState(() {
                    for (final feature in module.features.values) {
                      for (var i = 0; i < feature.permissions.length; i++) {
                        feature.permissions[i] =
                            feature.permissions[i].copyWith(isGranted: !allGranted);
                      }
                    }
                  });
                } : null,
                child: Text(
                  allPerms.every((p) => p.isGranted) ? 'None' : 'All',
                  style: TextStyle(fontSize: 12, color: AppColors.primary),
                ),
              ),
              Icon(Icons.expand_more, color: AppColors.primary),
            ]),
            children: module.features.entries.map((fe) {
              final feature = fe.value;
              return Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (feature.label.isNotEmpty)
                      Padding(
                        padding: const EdgeInsets.only(top: 4, bottom: 2),
                        child: Text(
                          feature.label.toUpperCase(),
                          style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w800,
                              letterSpacing: 0.4,
                              color: AppColors.primary.withValues(alpha: 0.55)),
                        ),
                      ),
                    ...feature.permissions.asMap().entries.map((pe) {
                      final idx = pe.key;
                      final perm = pe.value;
                      return CheckboxListTile(
                        dense: true,
                        contentPadding: EdgeInsets.zero,
                        value: perm.isGranted,
                        activeColor: AppColors.primary,
                        controlAffinity: ListTileControlAffinity.leading,
                        title: Text(perm.label, style: const TextStyle(fontSize: 13)),
                        onChanged: widget.enabled ? (val) {
                          setState(() {
                            final action = feature.permissions[idx].action;
                            final granted = val ?? false;
                            feature.permissions[idx] =
                                feature.permissions[idx].copyWith(isGranted: granted);
                            _applyExamSecondaryCoupling(widget.permissions, action, granted);
                          });
                        } : null,
                      );
                    }),
                  ],
                ),
              );
            }).toList(),
          ),
        ),
      ),
    );
  }
}
