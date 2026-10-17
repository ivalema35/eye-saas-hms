/// Dart mirror of the backend's `config/permission_matrix.php` — the
/// module → feature → action-key tree. This is the one place that needs
/// updating when the server-side matrix changes; `PermissionService`'s
/// `canModule()`/`canFeature()` are built on the lookups derived here
/// instead of parsing key strings.
library;

class PermissionAction {
  final String key;
  final String label;
  const PermissionAction(this.key, this.label);
}

class PermissionFeature {
  final String key;
  final String label;
  final List<PermissionAction> actions;
  const PermissionFeature(this.key, this.label, this.actions);
}

class PermissionModule {
  final String key;
  final String label;
  final List<PermissionFeature> features;
  const PermissionModule(this.key, this.label, this.features);
}

List<PermissionAction> _crud(String feature) => [
      PermissionAction('${feature}_view', 'View'),
      PermissionAction('${feature}_add', 'Add'),
      PermissionAction('${feature}_edit', 'Edit'),
      PermissionAction('${feature}_delete', 'Delete'),
    ];

class PermissionMatrix {
  PermissionMatrix._();

  static final List<PermissionModule> modules = [
    PermissionModule('dashboard', 'Dashboard', [
      PermissionFeature('widgets', 'Home widgets', const [
        PermissionAction('dashboard_clinical', 'Clinical / OPD stats & queues'),
        PermissionAction('dashboard_reception', 'Reception stats'),
        PermissionAction('dashboard_revenue', 'Revenue / collection'),
        PermissionAction('dashboard_ot', 'OT appointments'),
        PermissionAction('dashboard_staff', 'Staff overview'),
      ]),
    ]),
    PermissionModule('opd', 'OPD', [
      PermissionFeature('patient', 'Patients', const [
        PermissionAction('patient_view', 'View'),
        PermissionAction('patient_register', 'Add (walk-in)'),
        PermissionAction('patient_register_phone', 'Add (phone)'),
        PermissionAction('patient_edit', 'Edit'),
        PermissionAction('patient_delete', 'Delete'),
      ]),
      PermissionFeature('exam', 'Eye Exam', const [
        PermissionAction('exam_primary', 'Primary exam'),
        PermissionAction('exam_secondary', 'Secondary exam'),
        PermissionAction('exam_history', 'View history'),
        PermissionAction('prescription_print', 'Print prescription'),
      ]),
      PermissionFeature('billing', 'OPD Billing', const [
        PermissionAction('bill_print', 'Print bill'),
      ]),
      PermissionFeature('opd_reports', 'OPD Reports', const [
        PermissionAction('opd_reports_view', 'View'),
        PermissionAction('opd_reports_export', 'Export'),
      ]),
    ]),
    PermissionModule('ot', 'OT / Surgery', [
      PermissionFeature('ot_appointment', 'OT Appointments', const [
        PermissionAction('ot_appointment_view', 'View'),
        PermissionAction('ot_appointment_create', 'Add / book'),
        PermissionAction('ot_appointment_edit', 'Edit'),
        PermissionAction('ot_appointment_confirm', 'Confirm'),
        PermissionAction('ot_appointment_cancel', 'Cancel'),
      ]),
      PermissionFeature('ot_booking', 'OT Booking', const [
        PermissionAction('ot_booking_create', 'Add'),
        PermissionAction('ot_booking_modify', 'Edit'),
        PermissionAction('ot_booking_cancel', 'Cancel / delete'),
        PermissionAction('ot_patient_list', 'View patient list'),
      ]),
      PermissionFeature('ot_counselling', 'Counselling', const [
        PermissionAction('ot_counselling_fill', 'Fill form'),
        PermissionAction('ot_consent_capture', 'Capture consent'),
        PermissionAction('ot_package_set', 'Set package'),
      ]),
      PermissionFeature('ot_payment', 'OT Payments', const [
        PermissionAction('ot_payment_record', 'Add payment'),
        PermissionAction('ot_payment_export', 'Export'),
        PermissionAction('ot_invoice_view', 'View invoice'),
        PermissionAction('ot_invoice_edit', 'Edit invoice'),
        PermissionAction('ot_billing_manage', 'Manage billing docs'),
        PermissionAction('ot_bill_print', 'Print bill'),
      ]),
      PermissionFeature('ot_ward', 'Ward / Pre-Op', const [
        PermissionAction('ot_ward_entry', 'Ward admission'),
        PermissionAction('ot_preop_entry', 'Pre-op vitals'),
        PermissionAction('ot_dilation_track', 'Dilation tracking'),
      ]),
      PermissionFeature('ot_surgery', 'Surgery', const [
        PermissionAction('ot_surgery_recommend', 'Recommend'),
        PermissionAction('ot_surgery_ready', 'Ready to operate'),
        PermissionAction('ot_surgery_record', 'Record surgery'),
        PermissionAction('ot_lens_record', 'Record lens'),
        PermissionAction('ot_lens_implant', 'Mark implanted'),
        PermissionAction('ot_meds_takehome', 'Take-home meds'),
      ]),
      PermissionFeature('ot_discharge', 'Discharge', const [
        PermissionAction('ot_discharge_generate', 'Generate docs'),
        PermissionAction('ot_discharge_patient', 'Final discharge'),
        PermissionAction('ot_certificate_print', 'Print certificate'),
      ]),
      PermissionFeature('ot_reports', 'OT Reports', const [
        PermissionAction('ot_reports_view', 'View'),
        PermissionAction('ot_reports_export', 'Export'),
      ]),
    ]),
    PermissionModule('master', 'Masters / Config', [
      PermissionFeature('casetype', 'Case Types', _crud('casetype')),
      PermissionFeature('referrer', 'Referrers', _crud('referrer')),
      PermissionFeature('duration', 'Durations', _crud('duration')),
      PermissionFeature('location', 'Locations', _crud('location')),
      PermissionFeature('user_doctor', 'Doctors', _crud('user_doctor')),
      PermissionFeature('user_reception', 'Receptionists', _crud('user_reception')),
      PermissionFeature('user_ot_staff', 'OT Staff', _crud('user_ot_staff')),
      PermissionFeature('role', 'Roles & Permissions', _crud('role')),
      PermissionFeature('medicine', 'Medicines', _crud('medicine')),
      PermissionFeature('eye_exam_chief_complaints', 'Chief Complaints', _crud('eye_exam_chief_complaints')),
      PermissionFeature('eye_exam_kco', 'K/C/O', _crud('eye_exam_kco')),
      PermissionFeature('eye_exam_hno', 'H/O', _crud('eye_exam_hno')),
      PermissionFeature('eye_exam_diagnosis', 'Diagnoses', _crud('eye_exam_diagnosis')),
      PermissionFeature('eye_exam_advice', 'Advice', _crud('eye_exam_advice')),
      PermissionFeature('eye_exam_vn', 'V/N', _crud('eye_exam_vn')),
      PermissionFeature('eye_exam_vngl', 'Vn C GL', _crud('eye_exam_vngl')),
      PermissionFeature('eye_exam_vnst', 'Vn C ST', _crud('eye_exam_vnst')),
      PermissionFeature('eye_exam_pnvn', 'PH NV/N', _crud('eye_exam_pnvn')),
      PermissionFeature('eye_exam_nrvn', 'NR V/N', _crud('eye_exam_nrvn')),
      PermissionFeature('eye_exam_sph_cyl', 'SPH / CYL', _crud('eye_exam_sph_cyl')),
      PermissionFeature('eye_exam_axis', 'Axis', _crud('eye_exam_axis')),
      PermissionFeature('eye_exam_nct', 'NCT (IOP)', _crud('eye_exam_nct')),
      PermissionFeature('eye_exam_sac', 'SAC', _crud('eye_exam_sac')),
      PermissionFeature('eye_exam_lid', 'Lid', _crud('eye_exam_lid')),
      PermissionFeature('eye_exam_conj', 'Conjunctiva', _crud('eye_exam_conj')),
      PermissionFeature('eye_exam_cornea', 'Cornea', _crud('eye_exam_cornea')),
      PermissionFeature('eye_exam_ac', 'A/C', _crud('eye_exam_ac')),
      PermissionFeature('eye_exam_iris', 'Iris', _crud('eye_exam_iris')),
      PermissionFeature('eye_exam_pupil', 'Pupil', _crud('eye_exam_pupil')),
      PermissionFeature('eye_exam_lens', 'Lens', _crud('eye_exam_lens')),
      PermissionFeature('eye_exam_em', 'E/M', _crud('eye_exam_em')),
      PermissionFeature('eye_exam_covertest', 'Cover Test', _crud('eye_exam_covertest')),
      PermissionFeature('eye_exam_disc', 'Disc', _crud('eye_exam_disc')),
      PermissionFeature('eye_exam_fr', 'F/R', _crud('eye_exam_fr')),
      PermissionFeature('ot_slot', 'OT Slots', _crud('ot_slot')),
      PermissionFeature('ot_type', 'OT Types', _crud('ot_type')),
      PermissionFeature('ot_surgery_type', 'Surgery Types', _crud('ot_surgery_type')),
      PermissionFeature('ot_charge', 'OT Charge Heads', _crud('ot_charge')),
      PermissionFeature('ot_lens_option', 'Lens Options', _crud('ot_lens_option')),
      PermissionFeature('ot_lens_power', 'Lens Powers', _crud('ot_lens_power')),
      PermissionFeature('ot_inventory', 'OT Lens Inventory', _crud('ot_inventory')),
      PermissionFeature('ot_package_master', 'OT Packages', _crud('ot_package_master')),
    ]),
    PermissionModule('setting', 'Settings', [
      PermissionFeature('hospital', 'Hospital', const [
        PermissionAction('setting_hospital_view', 'View'),
        PermissionAction('setting_hospital_edit', 'Edit'),
        PermissionAction('setting_subscription_view', 'View subscription'),
      ]),
    ]),
    PermissionModule('report', 'Reports', [
      PermissionFeature('reports', 'All Reports', const [
        PermissionAction('report_view', 'View'),
        PermissionAction('report_export', 'Export'),
      ]),
    ]),
  ];

  static final Map<String, String> keyToModule = {
    for (final m in modules)
      for (final f in m.features)
        for (final a in f.actions) a.key: m.key,
  };

  static final Map<String, String> keyToFeature = {
    for (final m in modules)
      for (final f in m.features)
        for (final a in f.actions) a.key: f.key,
  };

  static final Set<String> allKeys = keyToModule.keys.toSet();

  static String? moduleOf(String key) => keyToModule[key];

  /// Mirror of backend `config/permission_matrix.php` expand_map.
  /// Bundle/manage grants expand to concrete CRUD keys on login.
  /// Named `_crudKeys` so it does not shadow the top-level `_crud`
  /// (which returns `List<PermissionAction>` for the modules tree).
  static List<String> _crudKeys(String feature) => [
        '${feature}_view',
        '${feature}_add',
        '${feature}_edit',
        '${feature}_delete',
      ];

  static const List<String> eyeExamFeatures = [
    'eye_exam_chief_complaints', 'eye_exam_kco', 'eye_exam_hno',
    'eye_exam_diagnosis', 'eye_exam_advice', 'eye_exam_vn',
    'eye_exam_vngl', 'eye_exam_vnst', 'eye_exam_pnvn', 'eye_exam_nrvn',
    'eye_exam_sph_cyl', 'eye_exam_axis', 'eye_exam_nct', 'eye_exam_sac',
    'eye_exam_lid', 'eye_exam_conj', 'eye_exam_cornea', 'eye_exam_ac',
    'eye_exam_iris', 'eye_exam_pupil', 'eye_exam_lens', 'eye_exam_em',
    'eye_exam_covertest', 'eye_exam_disc', 'eye_exam_fr',
  ];

  static List<String> _eyeExamKeysFor(String verb) =>
      eyeExamFeatures.map((feature) => '${feature}_$verb').toList();

  static final List<String> _eyeExamAllKeys =
      eyeExamFeatures.expand(_crudKeys).toList();

  static final Map<String, List<String>> expandMap = {
    'master.case_types': [..._crudKeys('casetype'), ..._crudKeys('referrer'), ..._crudKeys('duration')],
    'casetype_manage': [..._crudKeys('casetype'), ..._crudKeys('referrer'), ..._crudKeys('duration')],
    'master.locations': _crudKeys('location'),
    'location_manage': _crudKeys('location'),
    'master.medicines': _crudKeys('medicine'),
    'medicine_manage': _crudKeys('medicine'),
    'master.eye_exam': _eyeExamAllKeys,
    'eye_exam_master_manage': _eyeExamAllKeys,
    'eye_exam_master_view': _eyeExamKeysFor('view'),
    'eye_exam_master_add': _eyeExamKeysFor('add'),
    'eye_exam_master_edit': _eyeExamKeysFor('edit'),
    'eye_exam_master_delete': _eyeExamKeysFor('delete'),
    'master.ot_slots': _crudKeys('ot_slot'),
    'ot_slot_manage': _crudKeys('ot_slot'),
    'master.ot_types': _crudKeys('ot_type'),
    'ot_type_manage': _crudKeys('ot_type'),
    'ot_surgery_type_manage': _crudKeys('ot_surgery_type'),
    'master.ot_charges': _crudKeys('ot_charge'),
    'ot_charge_manage': _crudKeys('ot_charge'),
    'master.ot_inventory': [
      ..._crudKeys('ot_inventory'),
      ..._crudKeys('ot_lens_option'),
      ..._crudKeys('ot_lens_power'),
      ..._crudKeys('ot_package_master'),
    ],
    'ot_inventory_manage': [
      ..._crudKeys('ot_inventory'),
      ..._crudKeys('ot_lens_option'),
      ..._crudKeys('ot_lens_power'),
      ..._crudKeys('ot_package_master'),
    ],
    'ot_lens_option_manage': _crudKeys('ot_lens_option'),
    'ot_lens_power_manage': _crudKeys('ot_lens_power'),
    'ot_package_master_manage': _crudKeys('ot_package_master'),
    'master.roles': _crudKeys('role'),
    'role_manage': _crudKeys('role'),
    'master.doctors': _crudKeys('user_doctor'),
    'user_doctor_manage': _crudKeys('user_doctor'),
    'master.receptions': _crudKeys('user_reception'),
    'user_reception_manage': _crudKeys('user_reception'),
    'master.ot_staff': _crudKeys('user_ot_staff'),
    'user_ot_staff_manage': _crudKeys('user_ot_staff'),
    'settings.hospital': const ['setting_hospital_view', 'setting_hospital_edit'],
    'setting_hospital': const ['setting_hospital_view', 'setting_hospital_edit'],
    'settings.subscription': const ['setting_subscription_view'],
    'setting_subscription': const ['setting_subscription_view'],
  };

  /// Expand manage/bundle keys present in [granted] into concrete action keys.
  static List<String> expandGranted(List<String> granted) {
    if (granted.isEmpty) return granted;
    final out = <String>{...granted};
    for (final key in granted) {
      final actions = expandMap[key];
      if (actions != null) out.addAll(actions);
    }
    return out.toList();
  }
}
