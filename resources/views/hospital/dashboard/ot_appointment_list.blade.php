@extends('hospital.layouts.app')
@section('title', 'OT Appointment')
{{-- Layout page-header intentionally unused — the heading, breadcrumb and
list all sit inside one bordered card, matching the Medicine Master / Users /
Roles / History / OT Patients panel design. --}}

@section('content')
    <div class="ota-list-page">
        <div class="ota-outer-card">
            <div class="ota-header-block">
                <div>
                    <div class="ota-header-title"><i class="bi bi-calendar2-week"></i> OT Appointment</div>
                    <nav class="ota-breadcrumb" aria-label="breadcrumb">
                        <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                        <span class="ota-breadcrumb-sep">/</span>
                        <span class="ota-breadcrumb-current">OT Appointment</span>
                    </nav>
                </div>
                <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>

            <div class="card ota-premium-card border-0 mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="ota-filter-icon"><i class="bi bi-funnel-fill"></i></span>
                        <strong class="ota-filter-title">Date Range Filter</strong>
                    </div>
                    <form method="GET" action="{{ route('hospital.dashboard.ot-appointments', ['slug' => $slug]) }}"
                        class="ota-filter-form">
                        <div class="ota-filter-fields">
                            <div>
                                <label class="form-label ota-form-label" for="date_range">Date range</label>
                                <input type="text" id="date_range" class="form-control clinical-input" data-hms-date-range
                                    data-start-name="start_date" data-end-name="end_date"
                                    data-start-value="{{ $startDate }}" data-end-value="{{ $endDate }}" data-auto-submit="1"
                                    placeholder="Select start → end date" autocomplete="off" readonly
                                    style="min-width:220px;">
                            </div>
                            <div class="ota-filter-actions">
                                <a href="{{ route('hospital.dashboard.ot-appointments', ['slug' => $slug]) }}"
                                    class="btn ota-btn-outline">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card ota-premium-card border-0">
                <div class="ota-card-header">
                    <div class="ota-title-wrap">
                        <span class="ota-title-icon" aria-hidden="true"><i class="bi bi-calendar2-week"
                                style="font-size: 1.1rem;"></i></span>
                        <h5 class="ota-title mb-0">OT Patients</h5>
                    </div>
                    <span class="badge ota-count-badge">{{ $appointments->count() }} total</span>
                </div>
                <div class="card-body p-0">
                    <div class="ota-table-wrap">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0 ota-table js-datatable">
                                <thead>
                                    <tr>
                                        <th><i class="bi bi-hash me-1"></i>Appt #</th>
                                        <th><i class="bi bi-person-badge me-1"></i>Patient</th>
                                        <th><i class="bi bi-telephone me-1"></i>Mobile</th>
                                        <th><i class="bi bi-tag me-1"></i>Type</th>
                                        <th><i class="bi bi-calendar-event me-1"></i>Date</th>
                                        <th><i class="bi bi-person-vcard me-1"></i>Doctor</th>
                                        <th><i class="bi bi-geo-alt me-1"></i>City</th>
                                        <th><i class="bi bi-flag me-1"></i>Status</th>
                                        <th class="text-center"><i class="bi bi-lightning me-1"></i>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($appointments as $appointment)
                                        @php
                                            $fullName = trim(implode(' ', array_filter([
                                                $appointment->patient_name,
                                                $appointment->middle_name,
                                                $appointment->surname,
                                            ])));
                                            $canWalkIn = in_array($appointment->status, [
                                                \App\Models\Hospital\OT\OtAppointment::STATUS_BOOKED,
                                                \App\Models\Hospital\OT\OtAppointment::STATUS_CONFIRMED,
                                            ], true) && empty($appointment->converted_patient_id);
                                        @endphp
                                        <tr>
                                            <td class="fw-semibold">{{ $appointment->appointment_number }}</td>
                                            <td>{{ $fullName !== '' ? $fullName : ($appointment->patient_name ?: '-') }}</td>
                                            <td>{{ $appointment->mobile_no ?: '-' }}</td>
                                            <td><span
                                                    class="badge ota-type-badge text-capitalize">{{ str_replace('_', ' ', (string) $appointment->appointment_type) }}</span>
                                            </td>
                                            <td>{{ optional($appointment->appointment_date)->format('d M Y') ?? '-' }}</td>
                                            <td>{{ $appointment->doctor?->name ? 'Dr. ' . $appointment->doctor->name : '-' }}
                                            </td>
                                            <td>{{ $appointment->location?->name ?: '-' }}</td>
                                            <td>
                                                <span
                                                    class="badge ota-status-badge {{ $appointment->stage_badge_class }}">{{ $appointment->stage_label }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if($canWalkIn)
                                                    <a href="{{ route('hospital.patients.create', ['slug' => $slug, 'ot_appointment_id' => $appointment->id]) }}"
                                                        class="ota-walkin-btn"
                                                        title="Register as walk-in (prefill from OT appointment)">
                                                        <i class="bi bi-person-walking"></i> Walk-In
                                                    </a>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center ota-empty">
                                                <i class="bi bi-inbox me-1"></i> No OT appointments found for selected dates.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- /.ota-outer-card --}}
    </div>
@endsection

@push('styles')
    <style>
        /*
                          OT Appointment (Dashboard) — Design refresh
                          Keep Blade/dynamic logic untouched; CSS-only + layout wrappers.
                          Palette follows hospital shell theme (#1B4F72 / #ebf5fbeb).
                        */

        .ota-list-page {
            --ota-secondary: #1B4F72;
            --ota-s2-06: rgba(27, 79, 114, 0.06);
            --ota-s2-08: rgba(27, 79, 114, 0.08);
            --ota-s2-10: rgba(27, 79, 114, 0.10);
            --ota-s2-12: rgba(27, 79, 114, 0.12);
            --ota-s2-18: rgba(27, 79, 114, 0.18);
            --ota-s2-24: rgba(27, 79, 114, 0.24);

            position: relative;
            padding: .25rem 0 1.5rem;
            color: var(--ota-secondary);
        }

        .ota-outer-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.12);
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(15, 79, 134, 0.08);
            overflow: hidden;
            padding: 1.25rem 1.5rem 1.5rem;
        }

        .ota-header-block {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .75rem;
            padding: 0 0 1rem;
        }

        .ota-header-title {
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--ota-secondary);
            letter-spacing: -.015em;
            display: flex;
            align-items: center;
            gap: .55rem;
        }

        .ota-header-title i {
            color: var(--ota-secondary);
            font-size: 1.2rem;
        }

        .ota-breadcrumb {
            margin-top: .4rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: #8891a0;
        }

        .ota-breadcrumb a {
            color: #8891a0;
            text-decoration: none;
        }

        .ota-breadcrumb a:hover {
            color: var(--ota-secondary);
        }

        .ota-breadcrumb-sep {
            color: #c3c9d3;
        }

        .ota-breadcrumb-current {
            color: #4a5568;
            font-weight: 600;
        }

        .ota-premium-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.08) !important;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(15, 79, 134, 0.05);
            overflow: hidden;
        }

        .ota-filter-icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--ota-s2-10);
            color: var(--ota-secondary);
            font-size: 1rem;
        }

        .ota-filter-title {
            color: var(--ota-secondary);
            font-weight: 800;
        }

        .ota-filter-fields {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: .85rem 1rem;
            align-items: end;
        }

        .ota-form-label {
            font-size: .78rem;
            font-weight: 700;
            color: rgba(27, 79, 114, 0.8);
            margin-bottom: .25rem;
        }

        .ota-filter-actions {
            display: flex;
            gap: .5rem;
        }

        .ota-btn-outline {
            border: 1.5px solid var(--ota-s2-24);
            color: var(--ota-secondary);
            font-weight: 700;
            border-radius: 10px;
            background: #fff;
            transition: background 170ms ease, border-color 170ms ease;
        }

        .ota-btn-outline:hover {
            background: var(--ota-s2-06);
            color: var(--ota-secondary);
            border-color: var(--ota-secondary);
        }

        .ota-card-header {
            background: #1b4f72;
            border-bottom: 1px solid var(--ota-s2-12);
            padding: 1.1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .ota-title-wrap {
            display: flex;
            align-items: center;
            gap: .85rem;
        }

        .ota-title-icon {
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

        .ota-title {
            font-weight: 900;
            letter-spacing: -0.2px;
            color: #ffffff;
        }

        .ota-count-badge {
            background: rgba(255, 255, 255, .78);
            color: var(--ota-secondary);
            border: 1px solid var(--ota-s2-12);
            border-radius: 999px;
            padding: .5rem .8rem;
            font-weight: 800;
            white-space: nowrap;
            box-shadow: 0 10px 22px rgba(27, 79, 114, .08);
        }

        .ota-table-wrap {
            padding: 0 .9rem .9rem !important;
            overflow-x: auto;
        }

        .ota-table-wrap .dataTables_wrapper {
            padding-top: .25rem;
        }

        .ota-table-wrap .dataTables_length,
        .ota-table-wrap .dataTables_filter {
            padding: 1rem 0 .75rem;
            font-size: .85rem;
            color: rgba(27, 79, 114, 0.72);
        }

        .ota-table-wrap .dataTables_info,
        .ota-table-wrap .dataTables_paginate {
            padding: .75rem 0 .25rem;
            font-size: .85rem;
            color: rgba(27, 79, 114, 0.72);
        }

        .ota-table-wrap .dataTables_filter input,
        .ota-table-wrap .dataTables_length select {
            border: 1px solid var(--ota-s2-18) !important;
            border-radius: 8px;
            font-size: .85rem;
            color: var(--ota-secondary) !important;
            background-color: #fff;
            padding: .3rem .6rem;
            margin-left: .5rem;
        }

        .ota-table-wrap .dataTables_length select {
            margin: 0 .4rem;
        }

        .ota-table-wrap .dataTables_filter input:focus,
        .ota-table-wrap .dataTables_length select:focus {
            outline: none;
            border-color: var(--ota-secondary);
            box-shadow: 0 0 0 .18rem var(--ota-s2-12);
        }

        .ota-table-wrap .dataTables_paginate .paginate_button {
            border-radius: 8px !important;
            padding: .3rem .65rem !important;
            margin-left: .2rem !important;
            border: 1px solid transparent !important;
            color: var(--ota-secondary) !important;
        }

        .ota-table-wrap .dataTables_paginate .paginate_button.disabled {
            color: rgba(27, 79, 114, 0.35) !important;
        }

        .ota-table-wrap .dataTables_paginate .paginate_button:hover {
            background: var(--ota-s2-08) !important;
            border-color: var(--ota-s2-18) !important;
            color: var(--ota-secondary) !important;
        }

        .ota-table-wrap .dataTables_paginate .paginate_button.current {
            background: var(--ota-secondary) !important;
            border-color: var(--ota-secondary) !important;
            color: #fff !important;
        }

        .ota-table {
            margin-bottom: 0;
            border-collapse: collapse;
            width: 100%;
            min-width: 980px;
        }

        .ota-table thead th {
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

        .ota-table tbody td {
            background: transparent;
            border: 0;
            border-bottom: 1px solid var(--ota-s2-12);
            padding: .75rem 1rem;
            font-weight: 600;
            color: rgba(27, 79, 114, 0.90);
            vertical-align: middle;
            white-space: nowrap;
        }

        .ota-table tbody td:first-child {
            color: rgba(27, 79, 114, 0.82);
            font-weight: 700;
        }

        .ota-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .ota-table tbody tr:hover td {
            background: #F5F8FC;
        }

        .ota-type-badge {
            background: var(--ota-s2-08);
            border: 1px solid var(--ota-s2-18);
            color: var(--ota-secondary);
            border-radius: 999px;
            padding: .4rem .7rem;
            font-weight: 800;
        }

        .ota-status-badge {
            border-radius: 999px;
            padding: .45rem .70rem;
            font-weight: 900;
            letter-spacing: .02em;
            box-shadow: 0 10px 22px rgba(27, 79, 114, 0.10);
            color: #fff;
        }

        /* Same stage colours as OT Appointments register */
        .ot-stage-booked {
            background: #E0A800;
        }

        .ot-stage-confirmed {
            background: #1E8E5A;
        }

        .ot-stage-cancelled {
            background: #C0392B;
        }

        .ot-stage-checkedin {
            background: #5D6D7E;
        }

        .ot-stage-recommended {
            background: #8E44AD;
        }

        .ot-stage-counselled {
            background: #2E86C1;
        }

        .ot-stage-billing {
            background: #D68910;
        }

        .ot-stage-ward {
            background: #117864;
        }

        .ot-stage-ready {
            background: #148F77;
        }

        .ot-stage-operated {
            background: #1A5276;
        }

        .ot-stage-discharged {
            background: #1E8E5A;
        }

        .ota-walkin-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            background: #5b21b6;
            color: #fff !important;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 800;
            text-decoration: none !important;
            box-shadow: 0 0 0 2px rgba(91, 33, 182, .28);
            white-space: nowrap;
            transition: transform 160ms ease, background 160ms ease;
        }

        .ota-walkin-btn:hover {
            background: #4c1d95;
            color: #fff !important;
            transform: translateY(-1px);
        }

        .ota-empty {
            padding: 2.25rem 1rem !important;
            color: rgba(27, 79, 114, 0.72) !important;
            font-weight: 800;
            white-space: normal !important;
        }

        @media (max-width: 768px) {
            .ota-filter-fields {
                grid-template-columns: 1fr 1fr;
            }

            .ota-filter-actions {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 576px) {
            .ota-filter-fields {
                grid-template-columns: 1fr;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .ota-list-page {
                animation: none !important;
            }
        }
    </style>
@endpush