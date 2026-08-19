@extends('hospital.layouts.app')
@section('title', 'Patient History')
@section('page-header', 'Patient History')

@push('styles')
    <style>
        .patient-history-page {
            --history-primary: #ebf5fbeb;
            --history-secondary: #1B4F72;
            --history-secondary-08: rgba(27, 79, 114, 0.08);
            --history-secondary-12: rgba(27, 79, 114, 0.12);
            --history-secondary-18: rgba(27, 79, 114, 0.18);
            --history-secondary-24: rgba(27, 79, 114, 0.24);
            color: var(--history-secondary);
            animation: history-page-in 420ms ease both;
        }

        .history-heading {
            background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94));
            border: 1px solid var(--history-secondary-12);
            border-radius: 22px;
            padding: 1.15rem 1.25rem;
            box-shadow: 0 18px 44px rgba(27, 79, 114, 0.09);
        }

        .history-heading-icon,
        .history-avatar {
            background: var(--history-secondary);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 14px 30px rgba(27, 79, 114, 0.22);
        }

        .history-heading-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
        }

        .history-heading h4,
        .history-timeline-title,
        .history-patient-name {
            color: var(--history-secondary) !important;
        }

        .history-search-card,
        .history-summary-card,
        .history-timeline-card {
            background: rgba(255, 255, 255, .84);
            border: 1px solid var(--history-secondary-12) !important;
            border-radius: 22px;
            box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
            overflow: hidden;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            animation: history-card-rise 520ms cubic-bezier(.2, .9, .2, 1) both;
        }

        .history-search-card {
            animation-delay: 40ms;
        }

        .history-summary-card {
            top: 80px;
            animation-delay: 80ms;
        }

        .history-timeline-card {
            animation-delay: 120ms;
        }

        .history-search-shell {
            display: flex;
            gap: .85rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .history-search-box {
            max-width: 680px;
            min-width: 300px;
            border: 1px solid var(--history-secondary-12);
            border-radius: 18px;
            background: rgba(255, 255, 255, .76);
            padding: .35rem;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .7);
        }

        .history-search-box .input-group-text,
        .history-search-box .form-control {
            background: transparent !important;
            border: 0 !important;
            color: var(--history-secondary);
            box-shadow: none !important;
        }

        .history-search-box .form-control {
            font-weight: 750;
        }

        .history-search-box .form-control::placeholder {
            color: rgba(27, 79, 114, .52);
        }

        .history-search-btn,
        .patient-history-page .btn-primary {
            background: var(--history-secondary) !important;
            border-color: var(--history-secondary) !important;
            color: #fff !important;
        }

        .patient-history-page .btn {
            border-radius: 12px;
            font-weight: 800;
            transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, border-color 170ms ease, color 170ms ease;
        }

        .patient-history-page .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(27, 79, 114, .14);
        }

        .history-alert {
            border: 1px solid rgba(230, 126, 34, .22) !important;
            background: rgba(253, 235, 208, .72) !important;
            color: var(--history-secondary);
            border-radius: 18px !important;
            animation: history-card-rise 420ms ease both;
        }

        .history-avatar {
            border-radius: 24px;
            width: 86px;
            height: 86px;
            font-size: 2.05rem;
            margin: 0 auto 1rem;
        }

        .history-patient-code {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            background: var(--history-primary);
            color: var(--history-secondary);
            border: 1px solid var(--history-secondary-12);
            border-radius: 999px;
            padding: .42rem .85rem;
            font-weight: 900;
        }

        .history-detail-list .list-group-item {
            background: transparent;
            color: var(--history-secondary);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: .65rem;
        }

        .history-detail-list .list-group-item i {
            width: 34px;
            height: 34px;
            border-radius: 12px;
            background: var(--history-primary);
            color: var(--history-secondary) !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 0 !important;
        }

        .history-count-badge {
            background: var(--history-secondary) !important;
            border-radius: 999px;
            padding: .35rem .65rem;
        }

        .history-timeline-header {
            background: linear-gradient(135deg, rgba(235, 245, 251, .92), rgba(255, 255, 255, .94)) !important;
            border-bottom: 1px solid var(--history-secondary-12) !important;
        }

        .history-print-btn {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: #EDF4FF !important;
            border: 1px solid #D9E5F2 !important;
            color: #5D7FB6 !important;
            text-decoration: none !important;
            flex-shrink: 0;
            box-shadow: none !important;
            padding: 0 !important;
            line-height: 1;
        }

        .history-print-btn:hover {
            background: #E6EFFF !important;
            border-color: #C9D9EE !important;
            color: #5D7FB6 !important;
        }

        .history-print-btn i {
            font-size: 1.12rem;
            line-height: 1;
        }

        .history-empty {
            background: var(--history-primary);
            border: 1px dashed var(--history-secondary-18);
            border-radius: 18px;
        }

        .timeline {
            position: relative;
            padding-left: 2.75rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 17px;
            width: 3px;
            border-radius: 999px;
            background: linear-gradient(180deg, var(--history-secondary), rgba(27, 79, 114, .08));
        }

        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            animation: history-row-in 460ms ease both;
        }

        .timeline-marker {
            position: absolute;
            top: .25rem;
            left: -2.68rem;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
            border: 4px solid #fff;
            font-size: .88rem;
            background: var(--history-secondary) !important;
            box-shadow: 0 12px 28px rgba(27, 79, 114, .20) !important;
        }

        .timeline-content {
            border: 1px solid var(--history-secondary-12) !important;
            border-left: 5px solid var(--history-secondary) !important;
            border-radius: 18px;
            box-shadow: 0 12px 32px rgba(27, 79, 114, .08) !important;
            transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
            overflow: hidden;
        }

        .timeline-content:hover {
            transform: translateX(4px);
            border-color: var(--history-secondary-24) !important;
            box-shadow: 0 18px 42px rgba(27, 79, 114, .13) !important;
        }

        .timeline-content h6,
        .timeline-content strong,
        .history-data-value {
            color: var(--history-secondary) !important;
        }

        .history-date-badge {
            background: var(--history-primary) !important;
            color: var(--history-secondary) !important;
            border: 1px solid var(--history-secondary-12) !important;
            border-radius: 999px;
            font-weight: 800;
        }

        .history-summary-box,
        .history-data-card {
            background: var(--history-primary) !important;
            border: 1px solid var(--history-secondary-12) !important;
            border-radius: 16px !important;
        }

        .history-field-label {
            color: rgba(27, 79, 114, .62) !important;
            font-weight: 800;
        }

        .history-data-badge {
            background: #fff !important;
            border-color: var(--history-secondary-12) !important;
            color: var(--history-secondary) !important;
            border-radius: 999px;
            padding: .42rem .7rem;
        }

        .patient-history-page [class*="btn-outline-"] {
            color: var(--history-secondary) !important;
            border-color: var(--history-secondary-24) !important;
            background: rgba(255, 255, 255, .78) !important;
        }

        .patient-history-page [class*="btn-outline-"]:hover {
            color: #fff !important;
            background: var(--history-secondary) !important;
            border-color: var(--history-secondary) !important;
        }

        @keyframes history-page-in {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes history-card-rise {
            from {
                opacity: 0;
                transform: translateY(12px) scale(.99);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes history-row-in {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── Date-grouped visit layout ── */
        .date-group {
            margin-bottom: 2.25rem;
        }

        .date-divider {
            display: flex;
            align-items: center;
            gap: .9rem;
            margin-bottom: 1.1rem;
        }

        .date-divider-badge {
            background: var(--history-secondary);
            color: #fff;
            border-radius: 999px;
            padding: .38rem 1.05rem;
            font-size: 12.5px;
            font-weight: 800;
            white-space: nowrap;
            letter-spacing: .2px;
            box-shadow: 0 6px 18px rgba(27, 79, 114, .22);
            flex-shrink: 0;
        }

        .date-divider-line {
            flex: 1;
            height: 2px;
            border-radius: 999px;
            background: linear-gradient(90deg, rgba(27, 79, 114, .22), rgba(27, 79, 114, .04));
        }

        .date-divider-count {
            font-size: 11px;
            font-weight: 700;
            color: rgba(27, 79, 114, .52);
            white-space: nowrap;
            flex-shrink: 0;
        }

        .visit-exam-card {
            border-radius: 16px !important;
            border: 1px solid var(--history-secondary-12) !important;
            box-shadow: 0 6px 22px rgba(27, 79, 114, .07) !important;
            transition: transform 170ms ease, box-shadow 170ms ease;
            overflow: hidden;
        }

        .visit-exam-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 36px rgba(27, 79, 114, .13) !important;
        }

        .visit-exam-header {
            padding: .65rem 1rem;
            font-size: 13px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: .5rem;
            border-bottom: 1px solid var(--history-secondary-12);
        }

        .visit-exam-header.is-primary {
            background: rgba(27, 79, 114, .07);
            color: #1B4F72;
        }

        .visit-exam-header.is-secondary {
            background: rgba(108, 117, 125, .07);
            color: #495057;
        }

        .visit-exam-time {
            margin-left: auto;
            font-size: 11.5px;
            font-weight: 700;
            background: #fff;
            border: 1px solid var(--history-secondary-12);
            border-radius: 999px;
            padding: .22rem .7rem;
            color: var(--history-secondary);
        }

        /* ── Canvas Clone ── */
        .cv-wrap {
            border: 1px solid #343a40;
            border-radius: 4px;
            background: #fff;
        }

        .cv-box {
            border: 1px solid #343a40;
            border-radius: 4px;
            padding: 6px;
            font-size: 11px;
            color: #1a1a1a;
        }

        .cv-title {
            background: #1B4F72;
            color: #fff;
            padding: 2px 6px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: .04em;
            margin-bottom: 4px;
        }

        .cv-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 4px;
        }

        .cv-table th,
        .cv-table td {
            border: 1px solid #343a40;
            padding: 2px 4px;
            text-align: center;
        }

        .cv-table thead tr:first-child th {
            background: #1B4F72;
            color: #fff;
            font-size: 10px;
        }

        .cv-table thead tr:nth-child(2) th {
            background: #eef4f9;
            color: #1B4F72;
            font-size: 10px;
            font-weight: 600;
        }

        .cv-table tbody th {
            background: #f0f4f8;
            color: #1B4F72;
            font-weight: 700;
            font-size: 10px;
        }

        .cv-oe-hd th {
            background: #343a40;
            color: #fff;
            font-weight: 700;
        }

        .cv-pill {
            display: inline-block;
            background: #eef4f9;
            color: #1B4F72;
            padding: 1px 8px;
            border-radius: 8px;
            margin: 1px 2px 1px 0;
            font-size: 10px;
        }

        .cv-badge {
            display: inline-block;
            background: #1B4F72;
            color: #fff;
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 10px;
            margin: 1px 2px 1px 0;
        }

        .cv-vn-line {
            font-size: 11px;
            display: inline-flex;
            align-items: center;
            margin-right: 8px;
            margin-bottom: 2px;
        }

        .cv-rx-hd th {
            background: #343a40;
            color: #fff;
            font-weight: 700;
        }

        @media (prefers-reduced-motion: reduce) {

            .patient-history-page,
            .history-search-card,
            .history-summary-card,
            .history-timeline-card,
            .timeline-item {
                animation: none;
            }

            .patient-history-page * {
                transition: none !important;
            }
        }

        @media (max-width: 992px) {
            .history-summary-card {
                position: static !important;
            }
        }

        @media (max-width: 576px) {
            .history-heading {
                padding: 1rem;
            }

            .history-search-shell {
                align-items: stretch;
            }

            .history-search-box,
            .history-search-btn {
                max-width: none;
                min-width: 100%;
                width: 100%;
            }

            .timeline {
                padding-left: 2.25rem;
            }

            .timeline-marker {
                left: -2.3rem;
                width: 34px;
                height: 34px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="patient-history-page">

        {{-- ══════════════════════════════════════════════════════════════════════
        LIST MODE — table of all examined patients with filters
        (rendered when $patients paginator is passed from the controller)
        ══════════════════════════════════════════════════════════════════════ --}}
        @if(isset($patients))
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                <div>
                    <h5 class="fw-bold mb-0" style="color:#1B4F72;">
                        <i class="bi bi-clock-history me-2"></i>Patient History
                    </h5>
                </div>
                <a href="{{ route('hospital.patients.index', ['slug' => $slug]) }}"
                    class="btn btn-sm btn-outline-secondary px-3" style="border-radius:6px;">
                     Back to Patients
                </a>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('hospital.patients.history', ['slug' => $slug]) }}" class="card mb-4 p-3"
                style="border-radius:14px;border:1px solid #e2e8f0;background:#fff;">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold mb-1" style="color:#1B4F72;font-size:13px;">Patient Name</label>
                        <input type="text" name="patient_name" value="{{ $patientName ?? '' }}"
                            class="form-control form-control-sm" placeholder="Search patient..."
                            style="border-radius:8px;border:1px solid #cbd5e1;">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold mb-1" style="color:#1B4F72;font-size:13px;">Doctor Name</label>
                        <input type="text" name="doctor_name" value="{{ $doctorName ?? '' }}"
                            class="form-control form-control-sm" placeholder="Search doctor..."
                            style="border-radius:8px;border:1px solid #cbd5e1;">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold mb-1" style="color:#1B4F72;font-size:13px;">Contact No.</label>
                        <input type="text" name="contact_no" value="{{ $contactNo ?? '' }}" class="form-control form-control-sm"
                            placeholder="Search contact..." maxlength="15" style="border-radius:8px;border:1px solid #cbd5e1;">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold mb-1" style="color:#1B4F72;font-size:13px;">Date</label>
                        <input type="date" name="date" value="{{ $date ?? '' }}" class="form-control form-control-sm"
                            style="border-radius:8px;border:1px solid #cbd5e1;">
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm w-100 fw-bold"
                            style="background:#1B4F72;color:#fff;border-radius:8px;">
                            <i class="bi bi-search me-1"></i>Filter
                        </button>
                        @if($patientName || $doctorName || $contactNo || $date)
                            <a href="{{ route('hospital.patients.history', ['slug' => $slug]) }}" class="btn btn-sm w-100 fw-bold"
                                style="background:#e2e8f0;color:#1B4F72;border-radius:8px;">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="mb-3">
                <span class="fw-semibold" style="color:#1B4F72;font-size:14px;">
                    <i class="bi bi-people me-1"></i>
                    Total Records: <strong>{{ $patients->total() }}</strong>
                </span>
            </div>

            <div class="custom-partner-card"
                style="background:#fff;border-radius:16px;box-shadow:0 10px 25px -5px rgba(27,79,114,.1);border:1px solid #e2e8f0;overflow:hidden;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center mb-0" style="font-size:14px;">
                        <thead>
                            <tr>
                                <th
                                    style="width:55px;background:#1B4F72;color:#fff;font-size:13px;text-transform:uppercase;letter-spacing:.5px;padding:15px;">
                                    #</th>
                                <th
                                    style="background:#1B4F72;color:#fff;font-size:13px;text-transform:uppercase;letter-spacing:.5px;padding:15px;">
                                    MRD No.</th>
                                <th class="text-start"
                                    style="background:#1B4F72;color:#fff;font-size:13px;text-transform:uppercase;letter-spacing:.5px;padding:15px;">
                                    Patient Name</th>
                                <th
                                    style="background:#1B4F72;color:#fff;font-size:13px;text-transform:uppercase;letter-spacing:.5px;padding:15px;">
                                    Doctor</th>
                                <th
                                    style="background:#1B4F72;color:#fff;font-size:13px;text-transform:uppercase;letter-spacing:.5px;padding:15px;">
                                    Date</th>
                                <th
                                    style="background:#1B4F72;color:#fff;font-size:13px;text-transform:uppercase;letter-spacing:.5px;padding:15px;">
                                    Contact No.</th>
                                <th
                                    style="background:#1B4F72;color:#fff;font-size:13px;text-transform:uppercase;letter-spacing:.5px;padding:15px;">
                                    Age</th>
                                <th
                                    style="background:#1B4F72;color:#fff;font-size:13px;text-transform:uppercase;letter-spacing:.5px;padding:15px;">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($patients as $i => $p)
                                <tr style="cursor:pointer;"
                                    onclick="window.location='{{ route('hospital.patients.history', ['slug' => $slug]) }}?patient_ids={{ $p->all_patient_ids }}'">
                                    <td>{{ $patients->firstItem() + $i }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $p->patient_code ?? '—' }}</span>
                                    </td>
                                    <td class="text-start fw-semibold" style="color:#1B4F72;">
                                        {{ $p->first_name }} {{ $p->last_name }}
                                    </td>
                                    <td class="fw-semibold">{{ $p->doctor->name ?? '—' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($p->appointment_date)->format('d M, Y') }}</td>
                                    <td class="text-muted">{{ $p->contact_no ?? '—' }}</td>
                                    <td>{{ $p->age ?? '—' }}</td>
                                    <td>
                                        <a href="{{ route('hospital.patients.history', ['slug' => $slug]) }}?patient_ids={{ $p->all_patient_ids }}"
                                            class="btn-view-partner"
                                            style="background:#0d9488;color:#fff;border:none;padding:6px 14px;border-radius:8px;font-weight:600;font-size:13px;text-decoration:none;"
                                            onclick="event.stopPropagation()">
                                            <i class="bi bi-eye-fill me-1"></i>View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-5 text-muted">
                                        <i class="bi bi-person-slash me-2"></i>Not Found Patient History Records.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($patients->hasPages())
                    <div class="d-flex justify-content-center p-3 border-top" style="background:#fcfcfc;">
                        {{ $patients->links() }}
                    </div>
                @endif
            </div>

        @else
            {{-- ══════════════════════════════════════════════════════════════════════
            DETAIL MODE — clinical timeline for a specific patient
            ══════════════════════════════════════════════════════════════════════ --}}
            @php
                $user = Auth::guard('hospital_user')->user();
                $isDoctor = in_array($user?->role?->slug, ['doctor', 'ot_assistant']);
            @endphp

            @if(!$isDoctor)
                <div class="history-heading d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0 d-flex align-items-center gap-3" style="color: var(--color-primary);">
                        <span class="history-heading-icon">
                            <i class="bi bi-clock-history fs-4"></i>
                        </span>
                        <span>Patient History</span>
                    </h4>
                    <a href="{{ route('hospital.patients.index', ['slug' => $slug]) }}"
                        class="btn btn-outline-secondary d-flex align-items-center gap-2"
                        style="border-color: var(--history-secondary); color: var(--history-secondary);">
                        <span>Back to Patients</span>
                    </a>
                </div>
            @endif

            <div class="card premium-card history-search-card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <form action="{{ route('hospital.patients.history', ['slug' => $slug]) }}" method="GET">
                        <div class="history-search-shell">
                            <div class="input-group input-group-lg history-search-box flex-grow-1" style="max-width: 600px;">
                                <span class="input-group-text bg-white border-end-0 text-muted">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" name="search" class="form-control clinical-input border-start-0 ps-0"
                                    placeholder="Search by MRD No, Contact, or Patient Name..." value="{{ $search ?? '' }}"
                                    autofocus>
                            </div>
                            <button type="submit" class="btn btn-primary history-search-btn px-4">
                                <i class="bi bi-search me-2"></i> Find Patient
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Disambiguation: multiple distinct-name patients found for this search --}}
            @if(isset($nameGroups) && $nameGroups->isNotEmpty())
                <div class="alert history-alert border-0 shadow-sm mb-3">
                    <i class="bi bi-people-fill me-2"></i>
                    <strong>{{ $nameGroups->count() }} patients found</strong> for
                    "<strong>{{ $search }}</strong>". Please select the correct patient:
                </div>
                <div class="row g-3 mb-4">
                    @foreach($nameGroups as $group)
                        @php
                            $baseUrl = ($historyRoute ?? route('hospital.patients.history', ['slug' => $slug]));
                            $href = $baseUrl . '?search=' . urlencode($search ?? '') . '&patient_ids=' . $group->patient_ids;
                        @endphp
                        <div class="col-12 col-md-6 col-lg-4">
                            <a href="{{ $href }}" class="card h-100 text-decoration-none history-search-card border-0 shadow-sm"
                                style="border-radius:18px;">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="history-heading-icon flex-shrink-0"
                                        style="width:44px;height:44px;border-radius:14px;font-size:1.1rem;">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <h6 class="fw-bold mb-1 text-truncate" style="color:var(--history-secondary);">
                                            {{ $group->display_name }}
                                        </h6>
                                        <div class="small" style="color:rgba(27,79,114,.62);">
                                            MRD: <strong>{{ $group->patient_code }}</strong>
                                        </div>
                                        <div class="small" style="color:rgba(27,79,114,.62);">
                                            {{ ucfirst($group->gender ?? '') }}{{ $group->age ? ', ' . $group->age . ' yrs' : '' }}
                                        </div>
                                    </div>
                                    <i class="bi bi-chevron-right ms-auto flex-shrink-0" style="color:rgba(27,79,114,.4);"></i>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($search && !$patient && (isset($nameGroups) ? $nameGroups->isEmpty() : true))
                <div class="alert alert-warning history-alert border-0 shadow-sm rounded-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    No patient found matching "<strong>{{ $search }}</strong>".
                </div>
            @endif

            @if($patient)
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="card premium-card history-summary-card border-0 shadow-sm sticky-top" style="top: 80px;">
                            <div class="card-body p-4 text-center">
                                <div class="history-avatar mx-auto mb-3" style="width:80px;height:80px;font-size:2rem;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <h5 class="history-patient-name fw-bold mb-2">
                                    {{ $patient->first_name }}
                                    @if($patient->middle_name) {{ $patient->middle_name }} @endif
                                    {{ $patient->last_name }}
                                </h5>
                                <p class="history-patient-code mb-0 small">
                                    {{ $patient->patient_code }}
                                    &nbsp;|&nbsp;
                                    {{ ucfirst($patient->gender) }},
                                    {{ $patient->age }} yrs
                                </p>

                                <ul class="history-detail-list list-group list-group-flush text-start mt-4">
                                    <li class="list-group-item px-0 py-2 border-0">
                                        <i class="bi bi-telephone text-muted me-2"></i>
                                        {{ $patient->contact_no }}
                                    </li>
                                    <li class="list-group-item px-0 py-2 border-0">
                                        <i class="bi bi-geo-alt text-muted me-2"></i>
                                        {{ $patient->locationLabel }}
                                    </li>
                                    <li class="list-group-item px-0 py-2 border-0">
                                        <i class="bi bi-calendar-plus text-muted me-2"></i>
                                        Registered: {{ $patient->created_at->format('d M Y') }}
                                    </li>
                                    @php
                                        $visitDays = $history->groupBy(fn($e) => \Carbon\Carbon::parse($e->examined_at)->format('d M Y'))->count();
                                    @endphp
                                    <li class="list-group-item px-0 py-2 border-0">
                                        <i class="bi bi-journal-medical text-muted me-2"></i>
                                        <span class="history-count-badge badge bg-primary">{{ $visitDays }}</span>
                                        visit{{ $visitDays === 1 ? '' : 's' }} recorded
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card premium-card history-timeline-card border-0 shadow-sm">
                            <div class="card-header history-timeline-header bg-white p-4 border-bottom">
                                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                                    <h5 class="history-timeline-title mb-0 fw-bold" style="color: var(--color-primary);">
                                        <i class="bi bi-clock-history me-2"></i> Clinical Timeline
                                    </h5>
                                    <a href="{{ route('hospital.patients.history.print', ['slug' => $slug, 'patient' => $patient->id]) }}"
                                        class="history-print-btn" title="Print patient history" aria-label="Print patient history"
                                        target="_blank" rel="noopener">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="card-body p-4">

                                @include('hospital.patient._exam_timeline')

                            </div>{{-- /card-body --}}
                        </div>{{-- /card (timeline) --}}
                    </div>{{-- /col-lg-8 --}}
                </div>{{-- /row g-4 --}}

                {{-- ═══ Partner Hospital History ═══ --}}
                @if(isset($partnerHistoryGroups) && $partnerHistoryGroups->isNotEmpty())
                    @foreach($partnerHistoryGroups as $partnerGroup)
                        @php
                            $partnerTenant        = $partnerGroup['tenant'];
                            $partnerHistory       = $partnerGroup['history'];
                            $partnerDiagnoses     = collect($partnerGroup['diagnosisMasters']);
                        @endphp
                        <div class="card premium-card border-0 shadow-sm mt-4"
                            style="border-radius:22px;overflow:hidden;border:1px solid rgba(27,79,114,.12)!important;">
                            <div class="card-header p-4"
                                style="background:linear-gradient(135deg,#eef3fb,#fff);border-bottom:1px solid rgba(27,79,114,.12);">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width:44px;height:44px;border-radius:14px;background:#1B4F72;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i class="bi bi-building-fill text-white fs-5"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="fw-bold mb-0" style="color:#1B4F72;">{{ $partnerTenant->name }}</h5>
                                        @if($partnerTenant->city)
                                            <div class="small text-muted">
                                                {{ $partnerTenant->city }}{{ $partnerTenant->state ? ', ' . $partnerTenant->state : '' }}
                                            </div>
                                        @endif
                                    </div>
                                    <span class="badge" style="background:#1B4F72;color:#fff;border-radius:999px;padding:.42rem 1rem;font-size:11px;font-weight:700;">
                                        <i class="bi bi-link-45deg me-1"></i>Partner Hospital
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                @include('hospital.patient._exam_timeline', [
                                    'history'         => $partnerHistory,
                                    'diagnosisMasters' => $partnerDiagnoses,
                                    'isOwnHistory'    => false,
                                ])
                            </div>
                        </div>
                    @endforeach
                @endif
            @endif
        @endif {{-- end list/detail mode --}}
    </div>
@endsection