<?php

/**
 * Permission Matrix — single source of truth (WEB).
 *
 * Structure: module → features → actions
 * action key format: {feature}_{verb}  e.g. casetype_view, casetype_add, patient_edit
 *
 * Sync: php artisan permissions:sync
 *
 * expand_map: old single "*_manage" / dotted keys → full CRUD set (grant copy on sync)
 * legacy_map: 1:1 old → new for middleware/can() resolution
 *
 * Web + API share the same underscore permission keys (see legacy_map for old dotted aliases).
 */

if (! function_exists('permission_matrix_crud')) {
    /**
     * @return list<array{key:string,label:string,sort:int}>
     */
    function permission_matrix_crud(string $feature, string $singularLabel): array
    {
        return [
            ['key' => "{$feature}_view", 'label' => "View {$singularLabel}", 'sort' => 10],
            ['key' => "{$feature}_add", 'label' => "Add {$singularLabel}", 'sort' => 20],
            ['key' => "{$feature}_edit", 'label' => "Edit {$singularLabel}", 'sort' => 30],
            ['key' => "{$feature}_delete", 'label' => "Delete {$singularLabel}", 'sort' => 40],
        ];
    }
}

if (! function_exists('permission_matrix_crud_keys')) {
    /** @return list<string> */
    function permission_matrix_crud_keys(string $feature): array
    {
        return ["{$feature}_view", "{$feature}_add", "{$feature}_edit", "{$feature}_delete"];
    }
}

if (! function_exists('eye_exam_master_types')) {
    /**
     * One entry per Eye Exam Masters card — each gets its own full CRUD
     * permission set (see DetailMasterController::featureKeyForType()).
     *
     * @return list<array{key:string,label:string}>
     */
    function eye_exam_master_types(): array
    {
        return [
            ['key' => 'eye_exam_chief_complaints', 'label' => 'Chief Complaints'],
            ['key' => 'eye_exam_kco', 'label' => 'K/C/O'],
            ['key' => 'eye_exam_hno', 'label' => 'H/O'],
            ['key' => 'eye_exam_diagnosis', 'label' => 'Diagnoses'],
            ['key' => 'eye_exam_advice', 'label' => 'Advice'],
            ['key' => 'eye_exam_vn', 'label' => 'V/N'],
            ['key' => 'eye_exam_vngl', 'label' => 'Vn C GL'],
            ['key' => 'eye_exam_vnst', 'label' => 'Vn C ST'],
            ['key' => 'eye_exam_pnvn', 'label' => 'PH NV/N'],
            ['key' => 'eye_exam_nrvn', 'label' => 'NR V/N'],
            ['key' => 'eye_exam_sph_cyl', 'label' => 'SPH / CYL'],
            ['key' => 'eye_exam_axis', 'label' => 'Axis'],
            ['key' => 'eye_exam_nct', 'label' => 'NCT (IOP)'],
            ['key' => 'eye_exam_sac', 'label' => 'SAC'],
            ['key' => 'eye_exam_lid', 'label' => 'Lid'],
            ['key' => 'eye_exam_conj', 'label' => 'Conjunctiva'],
            ['key' => 'eye_exam_cornea', 'label' => 'Cornea'],
            ['key' => 'eye_exam_ac', 'label' => 'A/C'],
            ['key' => 'eye_exam_iris', 'label' => 'Iris'],
            ['key' => 'eye_exam_pupil', 'label' => 'Pupil'],
            ['key' => 'eye_exam_lens', 'label' => 'Lens'],
            ['key' => 'eye_exam_em', 'label' => 'E/M'],
            ['key' => 'eye_exam_covertest', 'label' => 'Cover Test'],
            ['key' => 'eye_exam_disc', 'label' => 'Disc'],
            ['key' => 'eye_exam_fr', 'label' => 'F/R'],
        ];
    }
}

if (! function_exists('eye_exam_master_all_action_keys')) {
    /** @return list<string> every view/add/edit/delete key across every Eye Exam Masters card */
    function eye_exam_master_all_action_keys(): array
    {
        $keys = [];
        foreach (eye_exam_master_types() as $type) {
            $keys = array_merge($keys, permission_matrix_crud_keys($type['key']));
        }

        return $keys;
    }
}

if (! function_exists('eye_exam_master_action_keys_for_verb')) {
    /** @return list<string> e.g. every *_view key across every Eye Exam Masters card */
    function eye_exam_master_action_keys_for_verb(string $verb): array
    {
        return array_map(fn (array $type) => "{$type['key']}_{$verb}", eye_exam_master_types());
    }
}

return [

    'legacy_map' => [
        'opd.patient.register' => 'patient_register',
        'opd.patient.register_phone' => 'patient_register_phone',
        'opd.patient.view' => 'patient_view',
        'opd.patient.edit' => 'patient_edit',
        'opd.patient.delete' => 'patient_delete',
        'opd.exam.primary' => 'exam_primary',
        'opd.exam.secondary' => 'exam_secondary',
        'opd.exam.history' => 'exam_history',
        'opd.bill.print' => 'bill_print',
        'opd.prescription.print' => 'prescription_print',
        'opd.reports.view' => 'opd_reports_view',
        'opd.reports.export' => 'opd_reports_export',

        'ot.appointment.view' => 'ot_appointment_view',
        'ot.appointment.create' => 'ot_appointment_create',
        'ot.appointment.edit' => 'ot_appointment_edit',
        'ot.appointment.confirm' => 'ot_appointment_confirm',
        'ot.appointment.cancel' => 'ot_appointment_cancel',
        'ot.booking.create' => 'ot_booking_create',
        'ot.booking.modify' => 'ot_booking_modify',
        'ot.booking.cancel' => 'ot_booking_cancel',
        'ot.counselling.fill' => 'ot_counselling_fill',
        'ot.consent.capture' => 'ot_consent_capture',
        'ot.patient.list' => 'ot_patient_list',
        'ot.package.set' => 'ot_package_set',
        'ot.payment.record' => 'ot_payment_record',
        'ot.payment.export' => 'ot_payment_export',
        'ot.ward.entry' => 'ot_ward_entry',
        'ot.preop.entry' => 'ot_preop_entry',
        'ot.dilation.track' => 'ot_dilation_track',
        'ot.surgery.recommend' => 'ot_surgery_recommend',
        'ot.surgery.ready' => 'ot_surgery_ready',
        'ot.surgery.record' => 'ot_surgery_record',
        'ot.lens.record' => 'ot_lens_record',
        'ot.lens.implant' => 'ot_lens_implant',
        'ot.meds.takehome' => 'ot_meds_takehome',
        'ot.invoice.view' => 'ot_invoice_view',
        'ot.invoice.edit' => 'ot_invoice_edit',
        'ot.discharge.generate' => 'ot_discharge_generate',
        'ot.discharge.patient' => 'ot_discharge_patient',
        'ot.certificate.print' => 'ot_certificate_print',
        'ot.bill.print' => 'ot_bill_print',
        'ot.billing.manage' => 'ot_billing_manage',

        // Old dotted / manage → primary view key (expand_map grants full CRUD on sync)
        'master.case_types' => 'casetype_view',
        'master.doctors' => 'user_doctor_view',
        'master.receptions' => 'user_reception_view',
        'master.ot_staff' => 'user_ot_staff_view',
        'master.roles' => 'role_view',
        'master.locations' => 'location_view',
        'master.medicines' => 'medicine_view',
        'master.eye_exam' => 'eye_exam_master_view',
        'master.ot_slots' => 'ot_slot_view',
        'master.ot_types' => 'ot_type_view',
        'master.ot_charges' => 'ot_charge_view',
        'master.ot_inventory' => 'ot_inventory_view',

        'casetype_manage' => 'casetype_view',
        'location_manage' => 'location_view',
        'medicine_manage' => 'medicine_view',
        'eye_exam_master_manage' => 'eye_exam_master_view',
        'ot_slot_manage' => 'ot_slot_view',
        'ot_type_manage' => 'ot_type_view',
        'ot_charge_manage' => 'ot_charge_view',
        'ot_inventory_manage' => 'ot_inventory_view',
        'ot_lens_option_manage' => 'ot_lens_option_view',
        'ot_lens_power_manage' => 'ot_lens_power_view',
        'ot_package_master_manage' => 'ot_package_master_view',
        'role_manage' => 'role_view',
        'user_doctor_manage' => 'user_doctor_view',
        'user_reception_manage' => 'user_reception_view',
        'user_ot_staff_manage' => 'user_ot_staff_view',

        'settings.hospital' => 'setting_hospital_view',
        'settings.subscription' => 'setting_subscription_view',
        'setting_hospital' => 'setting_hospital_view',
        'setting_subscription' => 'setting_subscription_view',

        'reports.view' => 'report_view',
        'reports.export' => 'report_export',
    ],

    /*
    |--------------------------------------------------------------------------
    | Expand map — one old grant → full action set
    |--------------------------------------------------------------------------
    */
    'expand_map' => [
        'master.case_types' => array_merge(
            permission_matrix_crud_keys('casetype'),
            permission_matrix_crud_keys('referrer'),
            permission_matrix_crud_keys('duration')
        ),
        'casetype_manage' => array_merge(
            permission_matrix_crud_keys('casetype'),
            permission_matrix_crud_keys('referrer'),
            permission_matrix_crud_keys('duration')
        ),
        'master.locations' => permission_matrix_crud_keys('location'),
        'location_manage' => permission_matrix_crud_keys('location'),
        'master.medicines' => permission_matrix_crud_keys('medicine'),
        'medicine_manage' => permission_matrix_crud_keys('medicine'),
        // Eye Exam Masters split into one CRUD set per card (2026-09) — old
        // blanket eye_exam_master_* grants expand onto the same verb across
        // every new per-card key, preserving what each role could already do.
        'master.eye_exam' => eye_exam_master_all_action_keys(),
        'eye_exam_master_manage' => eye_exam_master_all_action_keys(),
        'eye_exam_master_view' => eye_exam_master_action_keys_for_verb('view'),
        'eye_exam_master_add' => eye_exam_master_action_keys_for_verb('add'),
        'eye_exam_master_edit' => eye_exam_master_action_keys_for_verb('edit'),
        'eye_exam_master_delete' => eye_exam_master_action_keys_for_verb('delete'),
        'master.ot_slots' => permission_matrix_crud_keys('ot_slot'),
        'ot_slot_manage' => permission_matrix_crud_keys('ot_slot'),
        'master.ot_types' => permission_matrix_crud_keys('ot_type'),
        'ot_type_manage' => permission_matrix_crud_keys('ot_type'),
        'master.ot_charges' => permission_matrix_crud_keys('ot_charge'),
        'ot_charge_manage' => permission_matrix_crud_keys('ot_charge'),
        'master.ot_inventory' => array_merge(
            permission_matrix_crud_keys('ot_inventory'),
            permission_matrix_crud_keys('ot_lens_option'),
            permission_matrix_crud_keys('ot_lens_power'),
            permission_matrix_crud_keys('ot_package_master')
        ),
        'ot_inventory_manage' => array_merge(
            permission_matrix_crud_keys('ot_inventory'),
            permission_matrix_crud_keys('ot_lens_option'),
            permission_matrix_crud_keys('ot_lens_power'),
            permission_matrix_crud_keys('ot_package_master')
        ),
        'ot_lens_option_manage' => permission_matrix_crud_keys('ot_lens_option'),
        'ot_lens_power_manage' => permission_matrix_crud_keys('ot_lens_power'),
        'ot_package_master_manage' => permission_matrix_crud_keys('ot_package_master'),
        'master.roles' => permission_matrix_crud_keys('role'),
        'role_manage' => permission_matrix_crud_keys('role'),
        'master.doctors' => permission_matrix_crud_keys('user_doctor'),
        'user_doctor_manage' => permission_matrix_crud_keys('user_doctor'),
        'master.receptions' => permission_matrix_crud_keys('user_reception'),
        'user_reception_manage' => permission_matrix_crud_keys('user_reception'),
        'master.ot_staff' => permission_matrix_crud_keys('user_ot_staff'),
        'user_ot_staff_manage' => permission_matrix_crud_keys('user_ot_staff'),
        'settings.hospital' => ['setting_hospital_view', 'setting_hospital_edit'],
        'setting_hospital' => ['setting_hospital_view', 'setting_hospital_edit'],
        'settings.subscription' => ['setting_subscription_view'],
        'setting_subscription' => ['setting_subscription_view'],
    ],

    'modules' => [

        /*
        | Home dashboard widget toggles — check/uncheck in Roles.
        | Empty hospital still shows cards with 0; uncheck hides the widget.
        */
        'dashboard' => [
            'label' => 'Dashboard',
            'sort' => 5,
            'features' => [
                'widgets' => [
                    'label' => 'Home widgets',
                    'sort' => 10,
                    'actions' => [
                        ['key' => 'dashboard_clinical', 'label' => 'Clinical / OPD stats & queues', 'sort' => 10],
                        ['key' => 'dashboard_reception', 'label' => 'Reception stats', 'sort' => 20],
                        ['key' => 'dashboard_revenue', 'label' => 'Revenue / collection', 'sort' => 30],
                        ['key' => 'dashboard_ot', 'label' => 'OT appointments', 'sort' => 40],
                        ['key' => 'dashboard_staff', 'label' => 'Staff overview', 'sort' => 50],
                    ],
                ],
            ],
        ],

        'opd' => [
            'label' => 'OPD',
            'sort' => 10,
            'features' => [
                'patient' => [
                    'label' => 'Patients',
                    'sort' => 10,
                    'actions' => [
                        ['key' => 'patient_view', 'label' => 'View', 'sort' => 10],
                        ['key' => 'patient_register', 'label' => 'Add (walk-in)', 'sort' => 20],
                        ['key' => 'patient_register_phone', 'label' => 'Add (phone)', 'sort' => 30],
                        ['key' => 'patient_edit', 'label' => 'Edit', 'sort' => 40],
                        ['key' => 'patient_delete', 'label' => 'Delete', 'sort' => 50],
                    ],
                ],
                'exam' => [
                    'label' => 'Eye Exam',
                    'sort' => 20,
                    'actions' => [
                        ['key' => 'exam_primary', 'label' => 'Primary exam', 'sort' => 10],
                        ['key' => 'exam_secondary', 'label' => 'Secondary exam', 'sort' => 20],
                        ['key' => 'exam_history', 'label' => 'View history', 'sort' => 30],
                        ['key' => 'prescription_print', 'label' => 'Print prescription', 'sort' => 40],
                    ],
                ],
                'billing' => [
                    'label' => 'OPD Billing',
                    'sort' => 30,
                    'actions' => [
                        ['key' => 'bill_print', 'label' => 'Print bill', 'sort' => 10],
                    ],
                ],
                'opd_reports' => [
                    'label' => 'OPD Reports',
                    'sort' => 40,
                    'actions' => [
                        ['key' => 'opd_reports_view', 'label' => 'View', 'sort' => 10],
                        ['key' => 'opd_reports_export', 'label' => 'Export', 'sort' => 20],
                    ],
                ],
            ],
        ],

        'ot' => [
            'label' => 'OT / Surgery',
            'sort' => 20,
            'features' => [
                'ot_appointment' => [
                    'label' => 'OT Appointments',
                    'sort' => 10,
                    'actions' => [
                        ['key' => 'ot_appointment_view', 'label' => 'View', 'sort' => 10],
                        ['key' => 'ot_appointment_create', 'label' => 'Add / book', 'sort' => 20],
                        ['key' => 'ot_appointment_edit', 'label' => 'Edit', 'sort' => 30],
                        ['key' => 'ot_appointment_confirm', 'label' => 'Confirm', 'sort' => 40],
                        ['key' => 'ot_appointment_cancel', 'label' => 'Cancel', 'sort' => 50],
                    ],
                ],
                'ot_booking' => [
                    'label' => 'OT Booking',
                    'sort' => 20,
                    'actions' => [
                        ['key' => 'ot_booking_create', 'label' => 'Add', 'sort' => 10],
                        ['key' => 'ot_booking_modify', 'label' => 'Edit', 'sort' => 20],
                        ['key' => 'ot_booking_cancel', 'label' => 'Cancel / delete', 'sort' => 30],
                        ['key' => 'ot_patient_list', 'label' => 'View patient list', 'sort' => 40],
                    ],
                ],
                'ot_counselling' => [
                    'label' => 'Counselling',
                    'sort' => 30,
                    'actions' => [
                        ['key' => 'ot_counselling_fill', 'label' => 'Fill form', 'sort' => 10],
                        ['key' => 'ot_consent_capture', 'label' => 'Capture consent', 'sort' => 20],
                        ['key' => 'ot_package_set', 'label' => 'Set package', 'sort' => 30],
                    ],
                ],
                'ot_payment' => [
                    'label' => 'OT Payments',
                    'sort' => 40,
                    'actions' => [
                        ['key' => 'ot_payment_record', 'label' => 'Add payment', 'sort' => 10],
                        ['key' => 'ot_payment_export', 'label' => 'Export', 'sort' => 20],
                        ['key' => 'ot_invoice_view', 'label' => 'View invoice', 'sort' => 30],
                        ['key' => 'ot_invoice_edit', 'label' => 'Edit invoice', 'sort' => 40],
                        ['key' => 'ot_billing_manage', 'label' => 'Manage billing docs', 'sort' => 50],
                        ['key' => 'ot_bill_print', 'label' => 'Print bill', 'sort' => 60],
                    ],
                ],
                'ot_ward' => [
                    'label' => 'Ward / Pre-Op',
                    'sort' => 50,
                    'actions' => [
                        ['key' => 'ot_ward_entry', 'label' => 'Ward admission', 'sort' => 10],
                        ['key' => 'ot_preop_entry', 'label' => 'Pre-op vitals', 'sort' => 20],
                        ['key' => 'ot_dilation_track', 'label' => 'Dilation tracking', 'sort' => 30],
                    ],
                ],
                'ot_surgery' => [
                    'label' => 'Surgery',
                    'sort' => 60,
                    'actions' => [
                        ['key' => 'ot_surgery_recommend', 'label' => 'Recommend', 'sort' => 10],
                        ['key' => 'ot_surgery_ready', 'label' => 'Ready to operate', 'sort' => 20],
                        ['key' => 'ot_surgery_record', 'label' => 'Record surgery', 'sort' => 30],
                        ['key' => 'ot_lens_record', 'label' => 'Record lens', 'sort' => 40],
                        ['key' => 'ot_lens_implant', 'label' => 'Mark implanted', 'sort' => 50],
                        ['key' => 'ot_meds_takehome', 'label' => 'Take-home meds', 'sort' => 60],
                    ],
                ],
                'ot_discharge' => [
                    'label' => 'Discharge',
                    'sort' => 70,
                    'actions' => [
                        ['key' => 'ot_discharge_generate', 'label' => 'Generate docs', 'sort' => 10],
                        ['key' => 'ot_discharge_patient', 'label' => 'Final discharge', 'sort' => 20],
                        ['key' => 'ot_certificate_print', 'label' => 'Print certificate', 'sort' => 30],
                    ],
                ],
                'ot_reports' => [
                    'label' => 'OT Reports',
                    'sort' => 80,
                    'actions' => [
                        ['key' => 'ot_reports_view', 'label' => 'View', 'sort' => 10],
                        ['key' => 'ot_reports_export', 'label' => 'Export', 'sort' => 20],
                    ],
                ],
            ],
        ],

        'master' => [
            'label' => 'Masters / Config',
            'sort' => 30,
            'features' => [
                'casetype' => [
                    'label' => 'Case Types',
                    'sort' => 10,
                    'actions' => permission_matrix_crud('casetype', 'case type'),
                ],
                'referrer' => [
                    'label' => 'Referrers',
                    'sort' => 15,
                    'actions' => permission_matrix_crud('referrer', 'referrer'),
                ],
                'duration' => [
                    'label' => 'Durations',
                    'sort' => 16,
                    'actions' => permission_matrix_crud('duration', 'duration'),
                ],
                'location' => [
                    'label' => 'Locations',
                    'sort' => 20,
                    'actions' => permission_matrix_crud('location', 'location'),
                ],
                'user_doctor' => [
                    'label' => 'Doctors',
                    'sort' => 30,
                    'actions' => permission_matrix_crud('user_doctor', 'doctor'),
                ],
                'user_reception' => [
                    'label' => 'Receptionists',
                    'sort' => 31,
                    'actions' => permission_matrix_crud('user_reception', 'receptionist'),
                ],
                'user_ot_staff' => [
                    'label' => 'OT Staff',
                    'sort' => 32,
                    'actions' => permission_matrix_crud('user_ot_staff', 'OT staff'),
                ],
                'role' => [
                    'label' => 'Roles & Permissions',
                    'sort' => 40,
                    'actions' => permission_matrix_crud('role', 'role'),
                ],
                'medicine' => [
                    'label' => 'Medicines',
                    'sort' => 50,
                    'actions' => permission_matrix_crud('medicine', 'medicine'),
                ],
            ] + (function (): array {
                // One feature per Eye Exam Masters card (sort 60-84) — each
                // gets its own full view/add/edit/delete set.
                $out = [];
                foreach (eye_exam_master_types() as $i => $type) {
                    $out[$type['key']] = [
                        'label' => $type['label'],
                        'sort' => 60 + $i,
                        'actions' => permission_matrix_crud($type['key'], $type['label']),
                    ];
                }

                return $out;
            })() + [
                'ot_slot' => [
                    'label' => 'OT Slots',
                    'sort' => 90,
                    'actions' => permission_matrix_crud('ot_slot', 'OT slot'),
                ],
                'ot_type' => [
                    'label' => 'OT Types',
                    'sort' => 91,
                    'actions' => permission_matrix_crud('ot_type', 'OT type'),
                ],
                'ot_surgery_type' => [
                    'label' => 'Surgery Types',
                    'sort' => 92,
                    'actions' => permission_matrix_crud('ot_surgery_type', 'surgery type'),
                ],
                'ot_charge' => [
                    'label' => 'OT Charge Heads',
                    'sort' => 93,
                    'actions' => permission_matrix_crud('ot_charge', 'charge head'),
                ],
                'ot_lens_option' => [
                    'label' => 'Lens Options',
                    'sort' => 94,
                    'actions' => permission_matrix_crud('ot_lens_option', 'lens option'),
                ],
                'ot_lens_power' => [
                    'label' => 'Lens Powers',
                    'sort' => 95,
                    'actions' => permission_matrix_crud('ot_lens_power', 'lens power'),
                ],
                'ot_inventory' => [
                    'label' => 'OT Lens Inventory',
                    'sort' => 96,
                    'actions' => permission_matrix_crud('ot_inventory', 'lens inventory'),
                ],
                'ot_package_master' => [
                    'label' => 'OT Packages',
                    'sort' => 97,
                    'actions' => permission_matrix_crud('ot_package_master', 'OT package'),
                ],
            ],
        ],

        'setting' => [
            'label' => 'Settings',
            'sort' => 40,
            'features' => [
                'hospital' => [
                    'label' => 'Hospital',
                    'sort' => 10,
                    'actions' => [
                        ['key' => 'setting_hospital_view', 'label' => 'View', 'sort' => 10],
                        ['key' => 'setting_hospital_edit', 'label' => 'Edit', 'sort' => 20],
                        ['key' => 'setting_subscription_view', 'label' => 'View subscription', 'sort' => 30],
                    ],
                ],
            ],
        ],

        'report' => [
            'label' => 'Reports',
            'sort' => 50,
            'features' => [
                'reports' => [
                    'label' => 'All Reports',
                    'sort' => 10,
                    'actions' => [
                        ['key' => 'report_view', 'label' => 'View', 'sort' => 10],
                        ['key' => 'report_export', 'label' => 'Export', 'sort' => 20],
                    ],
                ],
            ],
        ],
    ],

    'role_templates' => [
        'doctor' => array_merge(
            permission_matrix_crud_keys('casetype'),
            permission_matrix_crud_keys('referrer'),
            permission_matrix_crud_keys('duration'),
            eye_exam_master_all_action_keys(),
            permission_matrix_crud_keys('medicine'),
            permission_matrix_crud_keys('location'),
            permission_matrix_crud_keys('role'),
            [
                'dashboard_clinical',
                'dashboard_ot',
                'patient_view',
                'patient_edit',
                'exam_primary',
                'exam_secondary',
                'exam_history',
                'prescription_print',
                'ot_surgery_recommend',
                'report_view',
            ]
        ),
        'receptionist' => array_merge(
            permission_matrix_crud_keys('user_reception'),
            permission_matrix_crud_keys('location'),
            permission_matrix_crud_keys('referrer'),
            [
                'dashboard_reception',
                'dashboard_ot',
                'patient_register',
                'patient_register_phone',
                'patient_view',
                'patient_edit',
                'patient_delete',
                'exam_history',
                'bill_print',
                'report_view',
                'report_export',
                'ot_appointment_view',
                'ot_appointment_create',
                'ot_appointment_edit',
                'ot_appointment_confirm',
                'ot_appointment_cancel',
                'ot_booking_create',
                'ot_booking_modify',
                'ot_booking_cancel',
                'ot_counselling_fill',
                'ot_consent_capture',
                'ot_patient_list',
                'ot_package_set',
                'ot_invoice_view',
                'ot_bill_print',
            ]
        ),
        'accountant' => [
            'ot_patient_list',
            'ot_payment_record',
            'ot_payment_export',
            'report_view',
            'report_export',
        ],
        'ward_management' => [
            'dashboard_ot',
            'ot_patient_list',
            'ot_ward_entry',
            'ot_preop_entry',
            'ot_dilation_track',
        ],
        'ot_assistant' => [
            'dashboard_ot',
            'ot_patient_list',
            'ot_surgery_ready',
            'ot_surgery_record',
            'ot_lens_record',
            'ot_lens_implant',
            'ot_meds_takehome',
        ],
        'discharge_counter' => [
            'dashboard_ot',
            'ot_invoice_view',
            'ot_invoice_edit',
            'ot_billing_manage',
            'ot_discharge_generate',
            'ot_discharge_patient',
            'ot_certificate_print',
            'ot_bill_print',
        ],
    ],
];
