import 'package:flutter/material.dart';
import '../constants/app_colors.dart';

/// Doctor dashboard OPD / OT switch, shown in the app header (top bar) instead
/// of inside the dashboard body. [DoctorDashboardScreen] and the shell header
/// share the selection through [doctorDashTab].
final ValueNotifier<String> doctorDashTab = ValueNotifier<String>('opd');

class DashTabSwitch extends StatelessWidget {
  final String value;
  final ValueChanged<String> onChanged;

  const DashTabSwitch({super.key, required this.value, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.18), borderRadius: BorderRadius.circular(10)),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        _btn('OPD', 'opd'),
        const SizedBox(width: 3),
        _btn('OT', 'ot'),
      ]),
    );
  }

  Widget _btn(String label, String tab) {
    final active = value == tab;
    return GestureDetector(
      onTap: () => onChanged(tab),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
        decoration: BoxDecoration(color: active ? Colors.white : Colors.transparent, borderRadius: BorderRadius.circular(8)),
        child: Text(label, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: active ? AppColors.primary : Colors.white)),
      ),
    );
  }
}

/// Header-ready switch bound to [doctorDashTab].
class DoctorDashTabSwitch extends StatelessWidget {
  const DoctorDashTabSwitch({super.key});

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<String>(
      valueListenable: doctorDashTab,
      builder: (_, v, _) => DashTabSwitch(value: v, onChanged: (t) => doctorDashTab.value = t),
    );
  }
}
