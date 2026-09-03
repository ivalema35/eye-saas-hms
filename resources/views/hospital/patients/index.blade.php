@extends('hospital.layouts.app')
@section('title', 'Patients')
{{-- Layout page-header intentionally unused — the heading + breadcrumb are
rendered inside the card itself so the whole block (heading, breadcrumb,
stat cards, panel bar, table) sits inside one bordered card, matching
the OT Appointments / Ward Management / Billing / Accountant / Counsellor
design. --}}

@push('styles')
    <style>
        /* Modern Professional Design - Theme aligned to hospital shell */
        :root {
            --patients-primary: #1B4F72;
            --patients-primary-soft: rgba(27, 79, 114, 0.08);
            --patients-primary-soft-2: rgba(27, 79, 114, 0.14);
            --patients-primary-soft-3: rgba(27, 79, 114, 0.18);
            --patients-accent: #1ABC9C;
            --patients-accent-soft: rgba(26, 188, 156, 0.12);
            --patients-surface: #ffffff;
            --patients-surface-soft: #f7fbfe;
            --patients-surface-muted: #eef5fa;
            --patients-border: rgba(27, 79, 114, 0.12);
            --patients-border-strong: rgba(27, 79, 114, 0.18);
            --patients-text: #17324d;
            --patients-muted: rgba(27, 79, 114, 0.68);
            --patients-shadow: 0 10px 30px rgba(27, 79, 114, 0.08);
        }

        .patients-index-page {
            padding: 0 0 2rem 0;
        }

        .patients-outer-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.12) !important;
            border-radius: 0.90rem;
            box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
            overflow: hidden;
        }

        .patients-header-block {
            background: #ffffff;
            padding: 1.25rem 1.5rem 1rem;
        }

        .patients-header-title {
            font-weight: 800;
            font-size: 1.3rem;
            color: #0D2137;
            letter-spacing: -.015em;
            display: flex;
            align-items: center;
            gap: .55rem;
        }

        .patients-header-title i {
            color: var(--patients-primary);
            font-size: 1.2rem;
        }

        .patients-breadcrumb {
            margin-top: .4rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: #8891a0;
        }

        .patients-breadcrumb a {
            color: #8891a0;
            text-decoration: none;
        }

        .patients-breadcrumb a:hover {
            color: var(--patients-primary);
        }

        .patients-breadcrumb-sep {
            color: #c3c9d3;
        }

        .patients-breadcrumb-current {
            color: #4a5568;
            font-weight: 600;
        }

        .patients-stats-wrap {
            padding: 0 1.5rem 1.5rem;
        }

        .patients-inner-panel {
            margin: 0 1.5rem 1.5rem;
            border: 1px solid rgba(15, 79, 134, 0.12);
            border-radius: 10px !important;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(27, 79, 114, 0.06);
        }

        .patients-top-btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: .5rem 1rem;
            font-weight: 700;
            font-size: .85rem;
            text-decoration: none;
            white-space: nowrap;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.10);
            transition: background 170ms ease, color 170ms ease, transform 170ms ease;
        }

        .patients-top-btn:hover {
            /* background: #eaf3fa; */
            color: #ffffff;
        }

        .patients-top-btn-outline {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: none;
        }

        .patients-top-btn-outline:hover {
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff;
        }

        /* Stats Cards */
        .stat-card {
            --stat-accent: #1B4F72;
            position: relative;
            background: #ffffff;
            border-radius: 12px;
            padding: 1rem 1.1rem;
            display: flex;
            align-items: center;
            gap: .85rem;
            box-shadow: 0 1px 3px rgba(27, 79, 114, 0.08);
            border: 1px solid var(--patients-border);
            border-left: 4px solid #1b4f72;
        }

        .stat-card-primary {
            --stat-accent: #1B4F72;
        }

        .stat-card-warning {
            --stat-accent: #D97706;
        }

        .stat-card-info {
            --stat-accent: #1D4ED8;
        }

        .stat-card-success {
            --stat-accent: #15803D;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
            background: #1b4f72;
            color: #ffffff;
        }

        .stat-info {
            flex: 1;
        }

        .stat-value {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            color: #1f2937;
            line-height: 1.15;
        }

        .stat-label {
            font-size: 11px;
            color: var(--patients-muted);
            margin: 2px 0 0 0;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        /* Modern Card — nested inside .patients-inner-panel, which now owns
                                           the border/shadow, so this is just a content wrapper. */
        .modern-card {
            background: var(--patients-surface);
            overflow: hidden;
        }

        .card-header-modern {
            padding: 1.15rem 1.5rem;
            background: var(--patients-primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-icon {
            width: 40px;
            height: 40px;
            background: #ffffff;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--hms-primary);
            font-size: 20px;
        }

        .header-title {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
        }

        .header-subtitle {
            font-size: 13px;
            color: var(--patients-muted);
            margin: 4px 0 0 0;
        }

        /* Toggle Switch */
        .toggle-switch {
            background: var(--patients-surface-muted);
            border-radius: 12px;
            padding: 0px;
            display: inline-flex;
        }

        .toggle-option {
            padding: 7px 8px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            color: var(--patients-muted);
        }

        .toggle-option.active {
            background: white;
            color: var(--patients-primary);
            box-shadow: 0 1px 3px rgba(27, 79, 114, 0.14);
        }

        .toggle-option:hover {
            background: white;
            color: var(--patients-primary);
            text-decoration: none;
        }

        /* Table Styles */
        .table-wrapper {
            overflow-x: auto;
            padding: 0;
        }

        .modern-card .dataTables_wrapper {
            padding: 0 2rem 1.5rem;
        }

        .modern-card .dataTables_wrapper .row:first-child {
            padding-top: 0.5rem;
        }

        .modern-card .dataTables_wrapper .row:last-child {
            padding-top: 1rem;
        }

        @media (max-width: 768px) {
            .modern-card .dataTables_wrapper {
                padding: 0 1.25rem 1.25rem;
            }
        }

        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .modern-table thead tr {
            background: var(--patients-surface);
        }

        .modern-table th {
            padding: 1rem 1.25rem;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--patients-muted);
            border-bottom: 2px solid var(--patients-border-strong);
            text-align: left;
        }

        .modern-table td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(27, 79, 114, 0.08);
            color: var(--patients-text);
            font-size: 14px;
            vertical-align: middle;
        }

        .modern-table tbody tr {
            background: var(--patients-surface);
            transition: all 0.2s ease;
        }

        .modern-table tbody tr:nth-child(even) {
            background: var(--patients-surface-muted);
        }

        .modern-table tbody tr:hover {
            background: var(--patients-primary-soft);
        }

        .serial {
            color: var(--patients-muted);
            font-weight: 500;
            width: 50px;
        }

        .mrd-badge {
            background: rgba(27, 79, 114, 0.08);
            padding: 6px 12px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            font-weight: 600;
            color: var(--patients-primary);
        }

        /* Patient row */
        .pt-row td {
            vertical-align: middle;
            padding: .75rem 1rem;
        }

        .pt-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pt-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--patients-text);
            line-height: 1.3;
        }

        .pt-meta {
            font-size: 11.5px;
            color: var(--patients-muted);
            margin-top: 2px;
            line-height: 1.3;
        }

        .pt-doctor {
            font-size: 13px;
            color: var(--patients-text);
        }

        .pt-fee {
            font-size: 14px;
            font-weight: 700;
            color: var(--patients-primary);
            line-height: 1.3;
        }

        .type-badge {
            display: inline-block;
            background: var(--patients-surface-muted);
            padding: 2px 8px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
            color: var(--patients-muted);
            margin-top: 3px;
            text-transform: capitalize;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            border: 1px solid transparent;
            font-size: 11.5px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-badge.completed {
            background: rgba(26, 188, 156, .13);
            border-color: rgba(26, 188, 156, .35);
            color: #0e9e82;
        }

        .status-badge.in-progress {
            background: rgba(27, 79, 114, .10);
            border-color: rgba(27, 79, 114, .30);
            color: var(--patients-primary);
        }

        .status-badge.waiting {
            background: rgba(255, 165, 0, .12);
            border-color: rgba(255, 165, 0, .35);
            color: #c07a00;
        }

        /* ─── Compact Action Buttons ─── */
        .pt-actions {
            display: flex;
            align-items: center;
            gap: 3px;
            flex-wrap: nowrap;
            white-space: nowrap;
        }

        .d-contents {
            display: contents;
        }

        .pta-divider {
            width: 1px;
            height: 20px;
            background: var(--patients-border);
            margin: 0 3px;
            flex-shrink: 0;
        }

        /* base icon button */
        .pta {
            width: 28px;
            height: 28px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
            font-size: 13px;
            background: transparent;
            color: var(--patients-muted);
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background .18s, color .18s;
            line-height: 1;
        }

        .pta:hover {
            text-decoration: none;
        }

        /* View — teal */
        .pta-view {
            background: rgba(26, 188, 156, .12);
            color: #0e9e82;
        }

        .pta-view:hover {
            background: #1abc9c;
            color: #fff;
        }

        /* Primary exam — navy */
        .pta-primary {
            background: rgba(27, 79, 114, .10);
            color: var(--patients-primary);
        }

        .pta-primary:hover {
            background: var(--patients-primary);
            color: #fff;
        }

        /* Secondary exam — purple-ish */
        .pta-secondary {
            background: rgba(100, 54, 180, .10);
            color: #6436b4;
        }

        .pta-secondary:hover {
            background: #6436b4;
            color: #fff;
        }

        /* Done state (primary or secondary completed) */
        .pta-done {
            background: rgba(26, 188, 156, .10);
            color: #0e9e82;
            cursor: default;
            opacity: .75;
        }

        /* Locked */
        .pta-locked {
            background: var(--patients-surface-muted);
            color: var(--patients-muted);
            cursor: not-allowed;
            opacity: .55;
        }

        /* Timer */
        .pta-timer {
            background: rgba(255, 152, 0, .12);
            color: #e07b00;
            cursor: default;
            min-width: 58px;
            width: auto;
            padding: 0 6px;
            gap: 3px;
            border-radius: 7px;
        }

        /* Utility — history, print */
        .pta-util {
            background: transparent;
            color: var(--patients-muted);
        }

        .pta-util:hover {
            background: rgba(27, 79, 114, .08);
            color: var(--patients-primary);
        }

        /* Edit */
        .pta-edit {
            background: transparent;
            color: var(--patients-muted);
        }

        .pta-edit:hover {
            background: var(--patients-primary);
            color: #fff;
        }

        /* Delete */
        .pta-delete {
            background: transparent;
            color: var(--patients-muted);
        }

        .pta-delete:hover {
            background: #dc3545;
            color: #fff;
        }

        /* Check-in */
        .pta-checkin {
            background: rgba(39, 174, 96, .12);
            color: #27ae60;
        }

        .pta-checkin:hover {
            background: #27ae60;
            color: #fff;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem !important;
        }

        .empty-icon {
            font-size: 64px;
            color: rgba(27, 79, 114, 0.22);
            margin-bottom: 1rem;
        }

        .empty-state h5 {
            font-size: 18px;
            color: var(--patients-text);
            margin: 0 0 0.5rem 0;
        }

        .empty-state p {
            font-size: 14px;
            color: var(--patients-muted);
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stat-card {
                padding: .75rem .9rem;
            }

            .stat-icon {
                width: 38px;
                height: 38px;
                font-size: 17px;
            }

            .stat-value {
                font-size: 19px;
            }

            .card-header-modern {
                padding: 1.25rem;
            }

            .modern-table th,
            .modern-table td {
                padding: 0.75rem 1rem;
            }

            .pt-actions {
                gap: 2px;
            }

            .pta {
                width: 26px;
                height: 26px;
                font-size: 12px;
            }
        }

        .patients-index-page .toggle-option i,
        .patients-index-page .status-badge i,
        .patients-index-page .pta i {
            vertical-align: middle;
        }

        .patients-index-page .modern-table a,
        .patients-index-page .modern-table button {
            transition: transform 160ms ease, box-shadow 160ms ease, background 160ms ease, color 160ms ease;
        }

        .patients-index-page .modern-table a:hover,
        .patients-index-page .modern-table button:hover {
            transform: translateY(-1px);
        }
    </style>
@endpush

@section('content')
    <div class="patients-index-page">
        <div class="card patients-outer-card border-0">
            <div class="patients-header-block">
                <div class="patients-header-title">
                    <i class="bi bi-people-fill"></i> Patients
                </div>
                <nav class="patients-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                    <span class="patients-breadcrumb-sep">/</span>
                    <span class="patients-breadcrumb-current">{{ $showAll ? 'All Patients' : "Today's Patients" }}</span>
                </nav>
            </div>

            <!-- Modern Stats Cards -->
            <div class="patients-stats-wrap">
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="stat-card stat-card-primary">
                            <div class="stat-icon">
                                <i class="bi bi-person-arms-up"></i>
                            </div>
                            <div class="stat-info">
                                <h3 class="stat-value">{{ $patients->count() }}</h3>
                                <p class="stat-label">Total Patients</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card stat-card-warning">
                            <div class="stat-icon">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                            <div class="stat-info">
                                <h3 class="stat-value">{{ $stats['waiting'] }}</h3>
                                <p class="stat-label">Waiting</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card stat-card-info">
                            <div class="stat-icon">
                                <i class="bi bi-clipboard2-check"></i>
                            </div>
                            <div class="stat-info">
                                <h3 class="stat-value">{{ $stats['primary_done'] }}</h3>
                                <p class="stat-label">Primary Done</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card stat-card-success">
                            <div class="stat-icon">
                                <i class="bi bi-check2-all"></i>
                            </div>
                            <div class="stat-info">
                                <h3 class="stat-value">{{ $stats['completed'] }}</h3>
                                <p class="stat-label">Completed</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Panel -->
            <div class="patients-inner-panel">
                <div class="modern-card">
                    <div class="card-header-modern">
                        <div class="header-left">
                            <div class="header-icon">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <h4 class="header-title">{{ $showAll ? 'All Patients' : "Today's Patients" }}</h4>
                            </div>
                        </div>

                        <div class="header-actions d-flex align-items-center gap-2 flex-wrap">
                            <div class="toggle-switch">
                                <a href="{{ route('hospital.patients.index', ['slug' => $slug]) }}"
                                    class="toggle-option {{ !$showAll ? 'active' : '' }}">
                                    <i class="bi bi-calendar-day"></i> Today
                                </a>
                                <a href="{{ route('hospital.patients.index', ['slug' => $slug, 'all' => 1]) }}"
                                    class="toggle-option {{ $showAll ? 'active' : '' }}">
                                    <i class="bi bi-list-ul"></i> All
                                </a>
                            </div>
                            <a href="{{ route('hospital.patients.create', ['slug' => $slug]) }}" class="patients-top-btn">
                                <i class="bi bi-person-plus"></i> Walk-in
                            </a>
                            <a href="{{ route('hospital.patients.create-phone', ['slug' => $slug]) }}"
                                class="patients-top-btn patients-top-btn-outline">
                                <i class="bi bi-telephone"></i> Phone Appt
                            </a>
                        </div>
                    </div>

                    <!-- Patients Table -->
                    <div id="patientsTableContainer">
                        @include('hospital.patients.partials.table', ['patients' => $patients, 'slug' => $slug, 'showAll' => $showAll])
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Patient View Modal --}}
    <div class="modal fade" id="patientViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" style="max-width:680px">
            <div class="modal-content pvm-modal-content">

                {{-- Header --}}
                <div class="pvm-header">
                    <div class="pvm-avatar" id="pvmAvatar"></div>
                    <div class="pvm-header-info">
                        <div id="pvmName" class="pvm-patient-name"></div>
                        <div class="pvm-header-meta">
                            <span id="pvmMrd" class="pvm-mrd-chip"></span>
                            <span id="pvmStatusBadge" class="pvm-status-chip"></span>
                            <span id="pvmGenderAge" class="pvm-meta-pill"></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        style="opacity:.8"></button>
                </div>

                {{-- Body --}}
                <div class="modal-body pvm-body">

                    {{-- Appointment highlight bar --}}
                    <div class="pvm-appt-bar">
                        <div class="pvm-appt-item">
                            <i class="bi bi-calendar3"></i>
                            <div>
                                <div class="pvm-appt-lbl">Appointment</div>
                                <div id="pvmAppt" class="pvm-appt-val"></div>
                            </div>
                        </div>
                        <div class="pvm-appt-divider"></div>
                        <div class="pvm-appt-item">
                            <i class="bi bi-person-badge"></i>
                            <div>
                                <div class="pvm-appt-lbl">Doctor</div>
                                <div id="pvmDoctor" class="pvm-appt-val"></div>
                            </div>
                        </div>
                        <div class="pvm-appt-divider"></div>
                        <div class="pvm-appt-item">
                            <i class="bi bi-tag"></i>
                            <div>
                                <div class="pvm-appt-lbl">Case Type</div>
                                <div id="pvmCase" class="pvm-appt-val"></div>
                            </div>
                        </div>
                        <div class="pvm-appt-divider"></div>
                        <div class="pvm-appt-item">
                            <i class="bi bi-cash-coin"></i>
                            <div>
                                <div class="pvm-appt-lbl">Fee</div>
                                <div id="pvmFee" class="pvm-appt-val pvm-fee"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Two column cards --}}
                    <div class="pvm-grid-2">
                        {{-- Personal --}}
                        <div class="pvm-card pvm-card-blue">
                            <div class="pvm-card-head"><i class="bi bi-person-lines-fill"></i> Personal</div>
                            <div class="pvm-field"><span class="pvm-fl">Occupation</span><span id="pvmOccupation"
                                    class="pvm-fv"></span></div>
                            <div class="pvm-field"><span class="pvm-fl">Referrer</span><span id="pvmReferrer"
                                    class="pvm-fv"></span></div>
                            <div class="pvm-field"><span class="pvm-fl">Registered</span><span id="pvmRegistered"
                                    class="pvm-fv pvm-fv-sm"></span></div>
                        </div>

                        {{-- Contact --}}
                        <div class="pvm-card pvm-card-teal">
                            <div class="pvm-card-head"><i class="bi bi-telephone-fill"></i> Contact & Location</div>
                            <div class="pvm-field">
                                <span class="pvm-fl"><i class="bi bi-phone"
                                        style="color:#1B4F72;margin-right:4px"></i>Phone</span>
                                <span id="pvmContact" class="pvm-fv pvm-contact"></span>
                            </div>
                            <div class="pvm-field">
                                <span class="pvm-fl"><i class="bi bi-whatsapp"
                                        style="color:#25D366;margin-right:4px"></i>WhatsApp</span>
                                <span id="pvmWhatsapp" class="pvm-fv pvm-contact"></span>
                            </div>
                            <div class="pvm-field">
                                <span class="pvm-fl"><i class="bi bi-geo-alt"
                                        style="color:#e74c3c;margin-right:4px"></i>Location</span>
                                <span id="pvmLocation" class="pvm-fv"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Exam Status --}}
                    <div class="pvm-card pvm-card-exam">
                        <div class="pvm-card-head"><i class="bi bi-clipboard2-pulse-fill"></i> Examination Status</div>
                        <div class="pvm-grid-2" style="margin-top:.6rem">
                            <div class="pvm-exam-box" id="pvmPrimaryBox">
                                <div class="pvm-exam-icon" id="pvmPrimaryIcon"></div>
                                <div>
                                    <div class="pvm-exam-label">Primary Exam</div>
                                    <div id="pvmPrimaryAt" class="pvm-exam-time"></div>
                                </div>
                            </div>
                            <div class="pvm-exam-box" id="pvmSecondaryBox">
                                <div class="pvm-exam-icon" id="pvmSecondaryIcon"></div>
                                <div>
                                    <div class="pvm-exam-label">Secondary Exam</div>
                                    <div id="pvmSecondaryAt" class="pvm-exam-time"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="pvm-footer">
                    <a id="pvmEditBtn" href="#" class="pvm-btn-edit">
                        <i class="bi bi-pencil-square"></i> Edit Patient
                    </a>
                    <button type="button" class="pvm-btn-close" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        /* ── Modal shell ── */
        .pvm-modal-content {
            border: none;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 32px 64px rgba(27, 79, 114, 0.22);
        }

        /* ── Header ── */
        .pvm-header {
            background: linear-gradient(135deg, #1B4F72 0%, #2471A3 60%, #2980B9 100%);
            padding: 1.4rem 1.6rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
        }

        .pvm-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(255, 255, 255, 0.12);
        }

        .pvm-avatar {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            flex-shrink: 0;
            background: rgba(255, 255, 255, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.5px;
            border: 2px solid rgba(255, 255, 255, 0.25);
        }

        .pvm-header-info {
            flex: 1;
            min-width: 0;
        }

        .pvm-patient-name {
            font-size: 1.15rem;
            font-weight: 700;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-transform: capitalize;
            letter-spacing: -.01em;
        }

        .pvm-header-meta {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-top: .4rem;
            flex-wrap: wrap;
        }

        .pvm-mrd-chip {
            font-family: 'Courier New', monospace;
            font-size: .72rem;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            padding: 2px 10px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            letter-spacing: .5px;
        }

        .pvm-status-chip {
            font-size: .7rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            letter-spacing: .02em;
        }

        .pvm-meta-pill {
            font-size: .72rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.75);
            background: rgba(255, 255, 255, 0.10);
            padding: 2px 9px;
            border-radius: 20px;
        }

        /* ── Body ── */
        .pvm-body {
            background: #f0f5fa;
            padding: 1.25rem 1.4rem;
            display: flex;
            flex-direction: column;
            gap: .9rem;
        }

        /* ── Appointment bar ── */
        .pvm-appt-bar {
            background: #fff;
            border-radius: 14px;
            padding: .9rem 1.1rem;
            display: flex;
            align-items: center;
            gap: 0;
            border: 1px solid rgba(27, 79, 114, 0.10);
            box-shadow: 0 2px 10px rgba(27, 79, 114, 0.06);
        }

        .pvm-appt-item {
            flex: 1;
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: 0 .5rem;
        }

        .pvm-appt-item>i {
            font-size: 1.2rem;
            color: #1B4F72;
            opacity: .55;
            flex-shrink: 0;
        }

        .pvm-appt-lbl {
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #94a3b8;
        }

        .pvm-appt-val {
            font-size: .88rem;
            font-weight: 700;
            color: #1e293b;
            margin-top: .1rem;
        }

        .pvm-fee {
            color: #1B4F72;
            font-size: 1rem;
        }

        .pvm-appt-divider {
            width: 1px;
            height: 40px;
            background: #e8eef4;
            flex-shrink: 0;
        }

        /* ── Grid 2col ── */
        .pvm-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .9rem;
        }

        /* ── Cards ── */
        .pvm-card {
            background: #fff;
            border-radius: 14px;
            padding: .95rem 1.1rem;
            border: 1px solid rgba(27, 79, 114, 0.09);
            box-shadow: 0 2px 8px rgba(27, 79, 114, 0.05);
        }

        .pvm-card-blue {
            border-left: 3px solid #1B4F72;
        }

        .pvm-card-teal {
            border-left: 3px solid #2980B9;
        }

        .pvm-card-exam {
            border-left: 3px solid #1ABC9C;
        }

        .pvm-card-head {
            font-size: .7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #1B4F72;
            margin-bottom: .7rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            padding-bottom: .55rem;
            border-bottom: 1px solid #f0f5fa;
        }

        .pvm-field {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .3rem 0;
            border-bottom: 1px solid #f8fafc;
        }

        .pvm-field:last-child {
            border-bottom: none;
        }

        .pvm-fl {
            font-size: .76rem;
            font-weight: 600;
            color: #94a3b8;
            display: flex;
            align-items: center;
        }

        .pvm-fv {
            font-size: .84rem;
            font-weight: 600;
            color: #1e293b;
            text-align: right;
            max-width: 58%;
            word-break: break-word;
        }

        .pvm-fv-sm {
            font-size: .76rem;
            color: #64748b;
        }

        .pvm-contact {
            font-size: .9rem;
            font-weight: 700;
            color: #1B4F72;
            letter-spacing: .3px;
        }

        /* ── Exam boxes ── */
        .pvm-exam-box {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .75rem;
            border-radius: 11px;
            border: 1px solid #e8eef4;
            background: #f8fafc;
            transition: border-color .2s;
        }

        .pvm-exam-box.done {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .pvm-exam-box.pending {
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        .pvm-exam-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .pvm-exam-label {
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #94a3b8;
        }

        .pvm-exam-time {
            font-size: .8rem;
            font-weight: 700;
            color: #1B4F72;
            margin-top: .15rem;
        }

        .pvm-exam-time.pending-text {
            color: #94a3b8;
            font-weight: 600;
        }

        /* ── Footer ── */
        .pvm-footer {
            background: #fff;
            border-top: 1px solid #e8eef4;
            padding: .9rem 1.4rem;
            display: flex;
            align-items: center;
            gap: .6rem;
        }

        .pvm-btn-edit {
            background: linear-gradient(135deg, #1B4F72 0%, #2471A3 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: .5rem 1.3rem;
            font-size: .86rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            box-shadow: 0 4px 12px rgba(27, 79, 114, 0.2);
            transition: box-shadow .2s, transform .15s;
        }

        .pvm-btn-edit:hover {
            color: #fff;
            box-shadow: 0 6px 16px rgba(27, 79, 114, 0.28);
            transform: translateY(-1px);
        }

        .pvm-btn-close {
            background: #f1f5f9;
            border: none;
            border-radius: 10px;
            padding: .5rem 1.2rem;
            font-size: .86rem;
            font-weight: 600;
            color: #64748b;
            transition: background .15s;
            cursor: pointer;
        }

        .pvm-btn-close:hover {
            background: #e2e8f0;
        }

        @media(max-width:600px) {
            .pvm-grid-2 {
                grid-template-columns: 1fr;
            }

            .pvm-appt-bar {
                flex-wrap: wrap;
                gap: .5rem;
            }

            .pvm-appt-divider {
                display: none;
            }

            .pvm-appt-item {
                min-width: 45%;
            }
        }
    </style>
@endpush

@push('scripts')
    {{-- Patients table: client-side DataTable (search box, sorting, pagination).
    Uses its own init (not the shared .js-datatable auto-init) so the
    default order stays "as rendered" (latest first) instead of the shared
    helper's alphabetical-by-column-1 default. --}}
    <script>
        $(function () {
            if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) {
                return;
            }

            var $table = $('#patientsTableContainer table.patients-table');
            if (!$table.length || jQuery.fn.DataTable.isDataTable($table[0])) {
                return;
            }

            // The "No patients found" placeholder row is a single
            // colspan'd <td> — DataTables can't reconcile that against a
            // 7-column header, so drop it before initializing.
            $table.find('tbody tr').each(function () {
                var $cells = jQuery(this).children('td');
                if ($cells.length === 1 && $cells.first().attr('colspan')) {
                    jQuery(this).remove();
                }
            });

            $table.DataTable({
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                order: [],
                autoWidth: false,
                columnDefs: [
                    { orderable: false, searchable: false, targets: [0, 6] }
                ],
                // Pre-filled from the top navbar's global search box (?q=...)
                search: { search: new URLSearchParams(window.location.search).get('q') || '' },
                language: {
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_',
                    info: 'Showing _START_ to _END_ of _TOTAL_',
                    infoEmpty: 'Showing 0 entries',
                    emptyTable: 'No records found.',
                    zeroRecords: 'No matching records.'
                },
                drawCallback: function () {
                    var info = this.api().page.info();
                    this.api().column(0, { search: 'applied', order: 'applied', page: 'current' })
                        .nodes()
                        .each(function (cell, i) {
                            cell.innerHTML = info.start + i + 1;
                        });
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.view-patient-btn');
                if (!btn) { return; }
                var d = btn.dataset;

                // Avatar initials
                var initials = (d.name || '?').trim().split(' ').map(function (w) { return w[0]; }).slice(0, 2).join('').toUpperCase();
                document.getElementById('pvmAvatar').textContent = initials;
                document.getElementById('pvmName').textContent = d.name || '—';
                document.getElementById('pvmMrd').textContent = d.mrd || '—';
                document.getElementById('pvmGenderAge').textContent = [d.gender, d.age ? d.age + 'y' : ''].filter(Boolean).join(', ') || '—';
                document.getElementById('pvmEditBtn').href = d.editUrl || '#';

                // Status badge
                var sb = document.getElementById('pvmStatusBadge');
                sb.textContent = d.status || '';
                var statusStyles = {
                    'Secondary Done': 'background:#dcfce7;color:#15803d',
                    'Primary Done': 'background:#dbeafe;color:#1d4ed8',
                    'Waiting': 'background:#fef9c3;color:#854d0e',
                };
                sb.style.cssText = statusStyles[d.status] || '';

                // Appointment bar
                document.getElementById('pvmAppt').textContent = d.appt || '—';
                document.getElementById('pvmDoctor').textContent = d.doctor || '—';
                document.getElementById('pvmCase').textContent = d.case || '—';
                document.getElementById('pvmFee').textContent = d.fee ? currencySymbol + d.fee : '—';

                // Personal card
                document.getElementById('pvmOccupation').textContent = d.occupation || '—';
                document.getElementById('pvmReferrer').textContent = d.referrer || '—';
                document.getElementById('pvmRegistered').textContent = d.registered || '—';

                // Contact card
                document.getElementById('pvmContact').textContent = d.contact || '—';
                document.getElementById('pvmWhatsapp').textContent = d.whatsapp || d.contact || '—';
                var loc = [d.city, d.district, d.state].filter(function (v) { return v && v !== 'null'; }).join(', ');
                document.getElementById('pvmLocation').textContent = loc || '—';

                // Primary exam
                var priIcon = document.getElementById('pvmPrimaryIcon');
                var priAt = document.getElementById('pvmPrimaryAt');
                var priBox = document.getElementById('pvmPrimaryBox');
                if (d.primaryAt && d.primaryAt !== 'null') {
                    priBox.className = 'pvm-exam-box done';
                    priIcon.style.background = '#dcfce7';
                    priIcon.innerHTML = '<i class="bi bi-check-circle-fill" style="color:#16a34a"></i>';
                    priAt.textContent = d.primaryAt;
                    priAt.className = 'pvm-exam-time';
                } else {
                    priBox.className = 'pvm-exam-box pending';
                    priIcon.style.background = '#f1f5f9';
                    priIcon.innerHTML = '<i class="bi bi-clock" style="color:#94a3b8"></i>';
                    priAt.textContent = 'Not done yet';
                    priAt.className = 'pvm-exam-time pending-text';
                }

                // Secondary exam
                var secIcon = document.getElementById('pvmSecondaryIcon');
                var secAt = document.getElementById('pvmSecondaryAt');
                var secBox = document.getElementById('pvmSecondaryBox');
                if (d.secondaryAt && d.secondaryAt !== 'null') {
                    secBox.className = 'pvm-exam-box done';
                    secIcon.style.background = '#dcfce7';
                    secIcon.innerHTML = '<i class="bi bi-check-circle-fill" style="color:#16a34a"></i>';
                    secAt.textContent = d.secondaryAt;
                    secAt.className = 'pvm-exam-time';
                } else {
                    secBox.className = 'pvm-exam-box pending';
                    secIcon.style.background = '#f1f5f9';
                    secIcon.innerHTML = '<i class="bi bi-clock" style="color:#94a3b8"></i>';
                    secAt.textContent = 'Not done yet';
                    secAt.className = 'pvm-exam-time pending-text';
                }

                new bootstrap.Modal(document.getElementById('patientViewModal')).show();
            });
        });
    </script>

    {{-- Live dilation countdown: the "Secondary Exam" slot shows an
    hourglass with a live MM:SS timer while a patient is dilating. The
    table markup only carries the unlock timestamp (data-unlock-time);
    this ticks the visible text every second. When the timer reaches zero
    the page is reloaded so the slot flips into the real "Secondary Exam"
    link/button. --}}
    <script>
        (function () {
            var reloadedForZero = false;

            function updateDilationTimers() {
                document.querySelectorAll('.dilation-timer-btn').forEach(function (el) {
                    var unlockMs = parseInt(el.getAttribute('data-unlock-time'), 10);
                    var textEl = el.querySelector('.timer-text');
                    if (!unlockMs || !textEl) { return; }

                    var diff = unlockMs - Date.now();
                    if (diff <= 0) {
                        textEl.textContent = ' 00:00';
                        if (!reloadedForZero) {
                            reloadedForZero = true;
                            window.location.reload();
                        }
                        return;
                    }

                    var m = Math.floor(diff / 60000);
                    var s = Math.floor((diff % 60000) / 1000);
                    textEl.textContent = ' ' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                });
            }

            updateDilationTimers();
            setInterval(updateDilationTimers, 1000);
        })();
    </script>
@endpush