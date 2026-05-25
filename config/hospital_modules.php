<?php

/**
 * config/hospital_modules.php
 *
 * Master list of all modules and their supported actions.
 * Used by: Permission UI (checkbox grid), Seeder, CheckPermission middleware.
 *
 * IMPORTANT: Never remove a module key — only add new ones.
 *            Removing a key will orphan existing permission rows in DB.
 */

return [
    // ─── OPD MODULE ──────────────────────────────────────────────────
    ['key' => 'patients',           'label' => 'Patient Registration',       'actions' => ['view', 'create', 'edit', 'delete']],
    ['key' => 'phone_appointments', 'label' => 'Phone Appointments',         'actions' => ['view', 'create', 'edit', 'delete']],
    ['key' => 'opd_primary_exam',   'label' => 'Primary Eye Examination',    'actions' => ['view', 'create', 'edit']],
    ['key' => 'opd_secondary_exam', 'label' => 'Secondary Eye Examination',  'actions' => ['view', 'create', 'edit']],
    ['key' => 'opd_bill_print',     'label' => 'OPD Bill Print',             'actions' => ['view']],
    ['key' => 'prescription_print', 'label' => 'Prescription Print',         'actions' => ['view']],
    ['key' => 'foc_create',         'label' => 'FOC — Create (Doctor)',      'actions' => ['create']],
    ['key' => 'foc_accept',         'label' => 'FOC — Accept (Reception)',   'actions' => ['create', 'edit']],
    ['key' => 'reports_opd',        'label' => 'OPD Reports & Export',       'actions' => ['view', 'create']],
    ['key' => 'patient_history',    'label' => 'Patient History Search',     'actions' => ['view']],

    // ─── OT MODULE ───────────────────────────────────────────────────
    ['key' => 'ot_booking',         'label' => 'OT Booking & Counselling',   'actions' => ['view', 'create', 'edit', 'delete']],
    ['key' => 'ot_package',         'label' => 'OT Package & Payment',       'actions' => ['view', 'create', 'edit']],
    ['key' => 'ot_payment_export',  'label' => 'OT Excel Export',            'actions' => ['view']],
    ['key' => 'ot_ward_entry',      'label' => 'Ward Admission & Pre-Op',    'actions' => ['view', 'create', 'edit']],
    ['key' => 'ot_dilation',        'label' => 'Dilation Tracking',          'actions' => ['view', 'create', 'edit']],
    ['key' => 'ot_surgery',         'label' => 'OT Surgery Recording',       'actions' => ['view', 'create', 'edit']],
    ['key' => 'ot_lens',            'label' => 'Lens Details (OT Assistant)', 'actions' => ['view', 'create', 'edit']],
    ['key' => 'ot_invoice',         'label' => 'OT Invoice View & Edit',     'actions' => ['view', 'edit']],
    ['key' => 'ot_discharge',       'label' => 'Discharge Patient',          'actions' => ['view', 'create']],
    ['key' => 'ot_reports',         'label' => 'OT Reports',                 'actions' => ['view', 'create']],

    // ─── MASTERS ─────────────────────────────────────────────────────
    ['key' => 'medicines',          'label' => 'Medicine Master',            'actions' => ['view', 'create', 'edit', 'delete']],
    ['key' => 'medicine_groups',    'label' => 'Medicine Groups',            'actions' => ['view', 'create', 'edit', 'delete']],
    ['key' => 'masters_basic',      'label' => 'Basic Masters (Cases, Slots)', 'actions' => ['view', 'create', 'edit', 'delete']],
    ['key' => 'masters_eye_exam',   'label' => 'Eye Exam Masters',           'actions' => ['view', 'create', 'edit', 'delete']],

    // ─── ADMIN ───────────────────────────────────────────────────────
    ['key' => 'manage_users',       'label' => 'Staff Management',           'actions' => ['view', 'create', 'edit', 'delete']],
    ['key' => 'manage_roles',       'label' => 'Roles & Permissions',        'actions' => ['view', 'create', 'edit', 'delete']],
    ['key' => 'hospital_settings',  'label' => 'Hospital Profile & Settings', 'actions' => ['view', 'edit']],
    ['key' => 'billing_info',       'label' => 'Subscription & Billing',     'actions' => ['view']],
];
