@extends('hospital.layouts.app')
@section('title', $doctor ? 'OT — Dr. ' . $doctor->name : 'OT Patients')
{{-- Layout page-header intentionally unused — the heading, breadcrumb and
list all sit inside one bordered card, matching the Medicine Master / Users /
Roles / History panel design. --}}

@section('content')
    <div class="dot-list-page">
        <div class="dot-outer-card">
            <div class="dot-header-block">
                <div>
                    <div class="dot-header-title">
                        <i class="bi bi-clipboard2-pulse"></i> {{ $doctor ? 'OT — Dr. ' . $doctor->name : 'OT Patients' }}
                    </div>
                    <nav class="dot-breadcrumb" aria-label="breadcrumb">
                        <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                        <span class="dot-breadcrumb-sep">/</span>
                        <span class="dot-breadcrumb-current">OT Patients</span>
                    </nav>
                </div>
                <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card dot-premium-card border-0 mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="dot-filter-icon"><i class="bi bi-funnel-fill"></i></span>
                        <strong class="dot-filter-title">Date Range Filter</strong>
                    </div>
                    <form method="GET" action="{{ route('hospital.dashboard.doctor-ot', ['slug' => $slug]) }}"
                        class="dot-filter-form">
                        <div class="dot-filter-fields">
                            <div>
                                <label class="form-label dot-form-label" for="date_range">Date range</label>
                                <input type="text" id="date_range" class="form-control clinical-input" data-hms-date-range
                                    data-start-name="start_date" data-end-name="end_date"
                                    data-start-value="{{ $startDate }}" data-end-value="{{ $endDate }}"
                                    placeholder="Select start → end date" autocomplete="off" readonly
                                    style="min-width:220px;">
                            </div>
                            @if(($doctors ?? collect())->isNotEmpty())
                                <div>
                                    <label class="form-label dot-form-label" for="doctor_id">Doctor</label>
                                    <select name="doctor_id" id="doctor_id" class="form-select clinical-input">
                                        <option value="">All Doctors</option>
                                        @foreach($doctors as $doc)
                                            <option value="{{ $doc->id }}" @selected($doctorId === $doc->id)>Dr. {{ $doc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @elseif($doctorId)
                                <input type="hidden" name="doctor_id" value="{{ $doctorId }}">
                            @endif
                            <div class="dot-filter-actions">
                                <button type="submit" class="btn dot-btn-primary">Apply</button>
                                <a href="{{ route('hospital.dashboard.doctor-ot', ['slug' => $slug]) }}"
                                    class="btn dot-btn-outline">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card dot-premium-card border-0">
                <div class="dot-card-header">
                    <div class="dot-title-wrap">
                        <span class="dot-title-icon" aria-hidden="true"><i class="bi bi-clipboard2-pulse"
                                style="font-size: 1.1rem;"></i></span>
                        <h5 class="dot-title mb-0">OT Patients</h5>
                    </div>
                    <span class="badge dot-count-badge">{{ $bookings->count() }} total</span>
                </div>
                <div class="card-body p-0">
                    <div class="dot-table-wrap">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0 dot-table js-datatable">
                                <thead>
                                    <tr>
                                        <th><i class="bi bi-person-badge me-1"></i>Patient</th>
                                        <th><i class="bi bi-telephone me-1"></i>Contact</th>
                                        <th><i class="bi bi-person me-1"></i>Age</th>
                                        <th><i class="bi bi-calendar-event me-1"></i>OT Date</th>
                                        <th><i class="bi bi-bandaid me-1"></i>Surgery</th>
                                        <th><i class="bi bi-eye me-1"></i>Eye</th>
                                        <th><i class="bi bi-clipboard-pulse me-1"></i>Ward</th>
                                        <th><i class="bi bi-flag me-1"></i>Status</th>
                                        <th class="text-end"><i class="bi bi-lightning me-1"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $otAssistants = $otAssistants ?? collect();
                                        $authUser = auth('hospital_user')->user();
                                        $canActAsDoctor = $authUser && (
                                            (method_exists($authUser, 'isSuperUser') && $authUser->isSuperUser())
                                            || in_array($authUser->role?->slug, ['hospital_admin', 'admin', 'doctor', 'ot_doctor'], true)
                                        );
                                    @endphp
                                    @forelse($bookings as $booking)
                                        @php
                                            $consultPending = $booking->isDoctorConsultationPending();
                                            // Like exams: any doctor can act (not only ot_doctor_id owner)
                                            $canActThis = $canActAsDoctor;
                                            $wardLabel = $booking->preOp
                                                ? (\App\Models\Hospital\OT\OtPreOp::STATUS_LABELS[$booking->preOp->pre_op_status] ?? $booking->preOp->pre_op_status)
                                                : '—';
                                        @endphp
                                        <tr>
                                            <td>{{ $booking->patient?->full_name ?? '-' }}</td>
                                            <td>{{ $booking->patient?->contact_no ?: '-' }}</td>
                                            <td>{{ $booking->patient?->age ?: '-' }}</td>
                                            <td>{{ optional($booking->surgery_date)->format('d M Y') ?? '-' }}</td>
                                            <td>{{ $booking->ot_type ?: '-' }}</td>
                                            <td>{{ $booking->eye ?: '-' }}</td>
                                            <td>
                                                <span
                                                    class="badge bg-secondary-subtle text-secondary-emphasis">{{ $wardLabel }}</span>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge dot-status-badge text-uppercase">{{ $booking->ot_status }}</span>
                                                @if($booking->otAssistant?->name)
                                                    <div class="small text-muted mt-1">Asst: {{ $booking->otAssistant->name }}</div>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if($consultPending && $canActThis)
                                                    <div class="d-flex flex-column align-items-end gap-2">
                                                        <form method="POST"
                                                            action="{{ route('hospital.dashboard.doctor-ot.assign-assistant', ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                                                            class="d-flex flex-wrap justify-content-end gap-2 align-items-center">
                                                            @csrf
                                                            <select name="ot_assistant_id" class="form-select form-select-sm"
                                                                style="min-width:160px" required>
                                                                <option value="">OT Assistant…</option>
                                                                @foreach($otAssistants as $asst)
                                                                    <option value="{{ $asst->id }}">{{ $asst->name }}</option>
                                                                @endforeach
                                                            </select>
                                                            <button type="submit" class="btn btn-sm btn-primary">
                                                                <i class="bi bi-person-check me-1"></i> Assign OT Assistant
                                                            </button>
                                                        </form>
                                                        <form method="POST"
                                                            action="{{ route('hospital.dashboard.doctor-ot.refuse', ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                                                            onsubmit="return confirm('Patient refuses OT? Will mark surgery_refused and send to Accounts for full refund.');">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="bi bi-cash-coin me-1"></i> Send to Account (Refuse OT)
                                                            </button>
                                                        </form>
                                                    </div>
                                                @elseif($booking->ot_status === \App\Models\Hospital\OT\OtBooking::STATUS_SURGERY_REFUSED)
                                                    <span class="text-muted small">Awaiting refund (Accounts)</span>
                                                @elseif($booking->ot_status === \App\Models\Hospital\OT\OtBooking::STATUS_READY)
                                                    <span class="text-success small"><i class="bi bi-check2-circle"></i> Ready for
                                                        OT</span>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center dot-empty">
                                                <i class="bi bi-inbox me-1"></i> No OT bookings found for selected dates.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- /.dot-outer-card --}}
    </div>
@endsection

@push('styles')
    <style>
        /*
                              OT Patients (Doctor OT) — Design refresh
                              Keep Blade/dynamic logic untouched; CSS-only + layout wrappers.
                              Palette follows hospital shell theme (#1B4F72 / #ebf5fbeb).
                            */

        .dot-list-page {
            --dot-secondary: #1B4F72;
            --dot-s2-06: rgba(27, 79, 114, 0.06);
            --dot-s2-08: rgba(27, 79, 114, 0.08);
            --dot-s2-10: rgba(27, 79, 114, 0.10);
            --dot-s2-12: rgba(27, 79, 114, 0.12);
            --dot-s2-18: rgba(27, 79, 114, 0.18);
            --dot-s2-24: rgba(27, 79, 114, 0.24);

            position: relative;
            padding: .25rem 0 1.5rem;
            color: var(--dot-secondary);
        }

        .dot-outer-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.12);
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(15, 79, 134, 0.08);
            overflow: hidden;
            padding: 1.25rem 1.5rem 1.5rem;
        }

        .dot-header-block {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .75rem;
            padding: 0 0 1rem;
        }

        .dot-header-title {
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--dot-secondary);
            letter-spacing: -.015em;
            display: flex;
            align-items: center;
            gap: .55rem;
        }

        .dot-header-title i {
            color: var(--dot-secondary);
            font-size: 1.2rem;
        }

        .dot-breadcrumb {
            margin-top: .4rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: #8891a0;
        }

        .dot-breadcrumb a {
            color: #8891a0;
            text-decoration: none;
        }

        .dot-breadcrumb a:hover {
            color: var(--dot-secondary);
        }

        .dot-breadcrumb-sep {
            color: #c3c9d3;
        }

        .dot-breadcrumb-current {
            color: #4a5568;
            font-weight: 600;
        }

        .dot-premium-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.08) !important;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(15, 79, 134, 0.05);
            overflow: hidden;
        }

        .dot-filter-icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--dot-s2-10);
            color: var(--dot-secondary);
            font-size: 1rem;
        }

        .dot-filter-title {
            color: var(--dot-secondary);
            font-weight: 800;
        }

        .dot-filter-fields {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: .85rem 1rem;
            align-items: end;
        }

        .dot-form-label {
            font-size: .78rem;
            font-weight: 700;
            color: rgba(27, 79, 114, 0.8);
            margin-bottom: .25rem;
        }

        .dot-filter-actions {
            display: flex;
            gap: .5rem;
        }

        .dot-btn-primary {
            background: var(--dot-secondary);
            border: 1px solid var(--dot-secondary);
            color: #fff;
            font-weight: 700;
            border-radius: 10px;
            padding: .45rem 1.1rem;
            transition: transform 170ms ease, background 170ms ease, box-shadow 170ms ease;
        }

        .dot-btn-primary:hover {
            background: #154360;
            border-color: #154360;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(27, 79, 114, 0.22);
        }

        .dot-btn-outline {
            border: 1.5px solid var(--dot-s2-24);
            color: var(--dot-secondary);
            font-weight: 700;
            border-radius: 10px;
            background: #fff;
            transition: background 170ms ease, border-color 170ms ease;
        }

        .dot-btn-outline:hover {
            background: var(--dot-s2-06);
            color: var(--dot-secondary);
            border-color: var(--dot-secondary);
        }

        .dot-card-header {
            background: #1b4f72;
            border-bottom: 1px solid var(--dot-s2-12);
            padding: 1.1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .dot-title-wrap {
            display: flex;
            align-items: center;
            gap: .85rem;
        }

        .dot-title-icon {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            background: #ffffff;
            color: #1b4f72;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 14px 30px rgba(27, 79, 114, 0.22);
            flex: 0 0 auto;
        }

        .dot-title {
            font-weight: 900;
            letter-spacing: -0.2px;
            color: #ffffff;
        }

        .dot-count-badge {
            background: rgba(255, 255, 255, .78);
            color: var(--dot-secondary);
            border: 1px solid var(--dot-s2-12);
            border-radius: 999px;
            padding: .5rem .8rem;
            font-weight: 800;
            white-space: nowrap;
            box-shadow: 0 10px 22px rgba(27, 79, 114, .08);
        }

        .dot-table-wrap {
            padding: 0 .9rem .9rem !important;
            overflow-x: auto;
        }

        .dot-table-wrap .dataTables_wrapper {
            padding-top: .25rem;
        }

        .dot-table-wrap .dataTables_length,
        .dot-table-wrap .dataTables_filter {
            padding: 1rem 0 .75rem;
            font-size: .85rem;
            color: rgba(27, 79, 114, 0.72);
        }

        .dot-table-wrap .dataTables_info,
        .dot-table-wrap .dataTables_paginate {
            padding: .75rem 0 .25rem;
            font-size: .85rem;
            color: rgba(27, 79, 114, 0.72);
        }

        .dot-table-wrap .dataTables_filter input,
        .dot-table-wrap .dataTables_length select {
            border: 1px solid var(--dot-s2-18) !important;
            border-radius: 8px;
            font-size: .85rem;
            color: var(--dot-secondary) !important;
            background-color: #fff;
            padding: .3rem .6rem;
            margin-left: .5rem;
        }

        .dot-table-wrap .dataTables_length select {
            margin: 0 .4rem;
        }

        .dot-table-wrap .dataTables_filter input:focus,
        .dot-table-wrap .dataTables_length select:focus {
            outline: none;
            border-color: var(--dot-secondary);
            box-shadow: 0 0 0 .18rem var(--dot-s2-12);
        }

        .dot-table-wrap .dataTables_paginate .paginate_button {
            border-radius: 8px !important;
            padding: .3rem .65rem !important;
            margin-left: .2rem !important;
            border: 1px solid transparent !important;
            color: var(--dot-secondary) !important;
        }

        .dot-table-wrap .dataTables_paginate .paginate_button.disabled {
            color: rgba(27, 79, 114, 0.35) !important;
        }

        .dot-table-wrap .dataTables_paginate .paginate_button:hover {
            background: var(--dot-s2-08) !important;
            border-color: var(--dot-s2-18) !important;
            color: var(--dot-secondary) !important;
        }

        .dot-table-wrap .dataTables_paginate .paginate_button.current {
            background: var(--dot-secondary) !important;
            border-color: var(--dot-secondary) !important;
            color: #fff !important;
        }

        .dot-table {
            margin-bottom: 0;
            border-collapse: collapse;
            width: 100%;
            min-width: 1040px;
        }

        .dot-table thead th {
            background: #F8FAFC !important;
            color: #4A5568 !important;
            border: 0;
            border-bottom: 1px solid #E2E8F0 !important;
            font-size: .75rem;
            letter-spacing: .05em;
            font-weight: 700;
            text-transform: uppercase;
            padding: .7rem 1rem;
            white-space: nowrap;
            text-align: left;
        }

        .dot-table tbody td {
            background: transparent;
            border: 0;
            border-bottom: 1px solid var(--dot-s2-12);
            padding: .75rem 1rem;
            font-weight: 600;
            color: rgba(27, 79, 114, 0.90);
            vertical-align: middle;
            white-space: nowrap;
        }

        .dot-table tbody td:first-child {
            color: rgba(27, 79, 114, 0.82);
            font-weight: 700;
        }

        .dot-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .dot-table tbody tr:hover td {
            background: #F5F8FC;
        }

        .dot-status-badge {
            background: var(--dot-s2-08);
            border: 1px solid var(--dot-s2-18);
            color: var(--dot-secondary);
            border-radius: 999px;
            padding: .4rem .7rem;
            font-weight: 800;
            letter-spacing: .02em;
        }

        .dot-empty {
            padding: 2.25rem 1rem !important;
            color: rgba(27, 79, 114, 0.72) !important;
            font-weight: 800;
            white-space: normal !important;
        }

        @media (max-width: 768px) {
            .dot-filter-fields {
                grid-template-columns: 1fr 1fr;
            }

            .dot-filter-actions {
                grid-column: 1 / -1;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .dot-list-page {
                animation: none !important;
            }
        }
    </style>
@endpush