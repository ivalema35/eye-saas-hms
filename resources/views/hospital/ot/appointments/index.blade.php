@extends('hospital.layouts.app')
@section('title', 'OT Appointments')
{{-- Layout page-header intentionally unused — the heading + breadcrumb are
rendered inside the card itself so the whole block (heading, breadcrumb,
panel bar, table) sits inside one bordered card, per design refresh. --}}

@section('content')
    <div class="ot-appt-page">
        <div class="card ot-premium-card border-0">
            <div class="ot-header-block">
                <div class="ot-header-title">
                    <i class="bi bi-calendar2-check-fill"></i> OT Appointments
                </div>
                <nav class="ot-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                    <span class="ot-breadcrumb-sep">/</span>
                    <span>OT</span>
                    <span class="ot-breadcrumb-sep">/</span>
                    <span class="ot-breadcrumb-current">Appointments</span>
                </nav>
            </div>
            <div class="ot-inner-panel">
                <div class="ot-card-header">
                    <div class="ot-title-wrap">
                        <span class="ot-title-icon" aria-hidden="true">
                            <i class="bi bi-calendar2-week" style="font-size: 1.2rem;"></i>
                        </span>
                        <div class="flex-grow-1">
                            <h5 class="ot-title">Appointment Register</h5>
                            <div class="ot-subtitle">Pre-registration appointments booked over
                                phone/walk-in/online/referral.
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap ot-header-actions">
                        <form method="GET" action="{{ route('hospital.ot.appointments.index', ['slug' => $slug]) }}"
                            class="ot-status-form">
                            <select name="status" class="form-select form-select-sm ot-status-select"
                                onchange="this.form.submit()">
                                @foreach(['all' => 'All Status', 'booked' => 'Booked', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled', 'completed' => 'Completed'] as $value => $label)
                                    <option value="{{ $value }}" {{ $activeStatus === $value ? 'selected' : '' }}>{{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                        <a href="{{ route('hospital.ot.appointments.create', ['slug' => $slug]) }}" class="ot-add-btn">
                            <i class="bi bi-plus-lg"></i> New Appointment
                        </a>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if(session('success'))
                        <div class="alert alert-success mx-4 mt-3 ot-alert"><i
                                class="bi bi-check-circle me-1"></i>{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger mx-4 mt-3 ot-alert"><i
                                class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}</div>
                    @endif

                    <div class="ot-table-wrap">
                        <div id="otAppointmentsTableContainer">
                            <table class="ot-table" id="otAppointmentsTable" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Appt #</th>
                                        <th>Patient</th>
                                        <th>Mobile</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Doctor</th>
                                        <th>Status</th>
                                        <th class="ot-actions-col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($appointments as $appointment)
                                        <tr>
                                            <td class="fw-bold">{{ $appointment->appointment_number }}</td>
                                            <td>{{ $appointment->patient_name }}</td>
                                            <td>{{ $appointment->mobile_no }}</td>
                                            <td><span
                                                    class="badge ot-type-badge text-capitalize">{{ str_replace('_', ' ', $appointment->appointment_type) }}</span>
                                            </td>
                                            <td>{{ optional($appointment->appointment_date)->format('d M Y') }}</td>
                                            <td>{{ $appointment->doctor?->name ? 'Dr. ' . $appointment->doctor->name : '-' }}
                                            </td>
                                            <td>
                                                <span
                                                    class="badge ot-status-badge {{ $appointment->stage_badge_class }}">{{ $appointment->stage_label }}</span>
                                            </td>
                                            <td class="ot-actions-cell">
                                                @if(in_array($appointment->status, ['booked', 'confirmed']))
                                                    <a href="{{ route('hospital.ot.appointments.edit', ['slug' => $slug, 'id' => $appointment->id]) }}"
                                                        class="btn btn-sm ot-icon-btn ot-icon-btn-edit me-1" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    @if($appointment->status === 'booked')
                                                        <form method="POST"
                                                            action="{{ route('hospital.ot.appointments.confirm', ['slug' => $slug, 'id' => $appointment->id]) }}"
                                                            class="d-inline">
                                                            @csrf
                                                            <button type="submit"
                                                                class="btn btn-sm ot-icon-btn ot-icon-btn-confirm me-1"
                                                                title="Confirm"><i class="bi bi-check2"></i></button>
                                                        </form>
                                                    @endif
                                                    <form method="POST"
                                                        action="{{ route('hospital.ot.appointments.cancel', ['slug' => $slug, 'id' => $appointment->id]) }}"
                                                        class="d-inline" onsubmit="return confirm('Cancel this appointment?');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm ot-icon-btn ot-icon-btn-cancel"
                                                            title="Cancel"><i class="bi bi-x-lg"></i></button>
                                                    </form>
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center ot-empty">
                                                <i class="bi bi-inbox me-1"></i> No appointments found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /*
                                            OT Appointments Index — design refresh (hospital shell theme, #1B4F72).
                                            Keep Blade/dynamic logic untouched; CSS-only + layout wrappers.
                                        */

        .ot-appt-page {
            --ot-primary: #1B4F72;
            --ot-primary-dark: #154160;
            --ot-s2-06: rgba(27, 79, 114, 0.06);
            --ot-s2-08: rgba(27, 79, 114, 0.08);
            --ot-s2-12: rgba(27, 79, 114, 0.12);
            --ot-s2-18: rgba(27, 79, 114, 0.18);
            --ot-s2-24: rgba(27, 79, 114, 0.24);

            position: relative;
            padding: .25rem 0 1.25rem;
            color: var(--ot-primary);
            animation: ot-page-in 420ms ease both;
        }

        @keyframes ot-page-in {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .ot-appt-page .btn,
        .ot-appt-page .hms-btn {
            border-radius: 10px;
            font-weight: 700;
            transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, border-color 170ms ease, color 170ms ease;
        }

        .ot-appt-page .btn:hover,
        .ot-appt-page .hms-btn:hover {
            transform: translateY(-1px);
        }

        .ot-premium-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.12) !important;
            border-radius: 0.90rem;
            box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
            overflow: hidden;
            animation: ot-card-rise 520ms cubic-bezier(.2, .9, .2, 1) both;
        }

        @keyframes ot-card-rise {
            from {
                opacity: 0;
                transform: translateY(10px) scale(0.99);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .ot-header-block {
            background: #ffffff;
            padding: 1.25rem 1.5rem 1rem;
        }

        .ot-header-title {
            font-weight: 800;
            font-size: 1.3rem;
            color: #1b4f72;
            letter-spacing: -.015em;
            display: flex;
            align-items: center;
            gap: .55rem;
        }

        .ot-header-title i {
            color: var(--ot-primary);
            font-size: 1.2rem;
        }

        .ot-breadcrumb {
            margin-top: .4rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: #8891a0;
        }

        .ot-breadcrumb a {
            color: #8891a0;
            text-decoration: none;
        }

        .ot-breadcrumb a:hover {
            color: var(--ot-primary);
        }

        .ot-breadcrumb-sep {
            color: #c3c9d3;
        }

        .ot-breadcrumb-current {
            color: #4a5568;
            font-weight: 600;
        }

        .ot-inner-panel {
            margin: 0 1.5rem 1.5rem;
            border: 1px solid rgba(15, 79, 134, 0.12);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(27, 79, 114, 0.06);
        }

        .ot-card-header {
            background: var(--ot-primary);
            padding: 1.15rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .ot-title-wrap {
            display: flex;
            align-items: center;
            gap: .85rem;
            min-width: 0;
        }

        .ot-title-wrap>div {
            min-width: 0;
        }

        .ot-title-icon {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.14);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .ot-title {
            font-weight: 800;
            letter-spacing: -0.2px;
            margin: 0;
            color: #ffffff;
        }

        .ot-subtitle {
            margin: .15rem 0 0;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.78);
            font-size: .85rem;
        }

        .ot-header-actions {
            flex-wrap: wrap;
        }

        .ot-status-form {
            display: inline-flex;
        }

        .ot-status-select {
            border: 1px solid rgba(255, 255, 255, 0.28) !important;
            background: rgba(255, 255, 255, 0.12) !important;
            color: #ffffff;
            font-weight: 600;
            border-radius: 10px;
            box-shadow: none !important;
        }

        .ot-status-select:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5) !important;
        }

        .ot-status-select option {
            color: var(--ot-primary);
        }

        .ot-add-btn {
            background: #ffffff;
            color: var(--ot-primary);
            border-radius: 10px;
            padding: .5rem 1rem;
            font-weight: 700;
            font-size: .875rem;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            text-decoration: none;
            white-space: nowrap;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.14);
        }

        .ot-add-btn:hover {
            background: #eaf3fa;
            color: var(--ot-primary-dark);
        }

        .ot-alert {
            border-radius: 12px;
            font-weight: 600;
        }

        .ot-table-wrap {
            padding: 0 1.5rem 1.25rem !important;
            overflow-x: auto;
        }

        .ot-table-wrap .dataTables_wrapper {
            padding-top: .25rem;
        }

        .ot-table-wrap .dataTables_length,
        .ot-table-wrap .dataTables_filter {
            padding: 1rem 0 .75rem;
            font-size: .85rem;
            color: rgba(27, 79, 114, 0.72);
        }

        .ot-table-wrap .dataTables_info,
        .ot-table-wrap .dataTables_paginate {
            padding: .75rem 0 .25rem;
            font-size: .85rem;
            color: rgba(27, 79, 114, 0.72);
        }

        .ot-table-wrap .dataTables_filter input,
        .ot-table-wrap .dataTables_length select.form-select,
        .ot-table-wrap .dataTables_length select {
            border: 1px solid var(--ot-s2-18) !important;
            border-radius: 8px;
            font-size: .85rem;
            color: var(--ot-primary) !important;
            background-color: #fff;
            padding: .3rem .6rem;
            margin-left: .5rem;
        }

        .ot-table-wrap .dataTables_length select.form-select,
        .ot-table-wrap .dataTables_length select {
            margin: 0 .4rem;
            padding-right: 1.75rem;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%231B4F72' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
        }

        .ot-table-wrap .dataTables_filter input:focus,
        .ot-table-wrap .dataTables_length select:focus {
            outline: none;
            border-color: var(--ot-primary);
            box-shadow: 0 0 0 .18rem var(--ot-s2-12);
        }

        .ot-table-wrap .dataTables_paginate .paginate_button {
            border-radius: 8px !important;
            padding: .3rem .65rem !important;
            margin-left: .2rem !important;
            border: 1px solid transparent !important;
            color: var(--ot-primary) !important;
        }

        .ot-table-wrap .dataTables_paginate .paginate_button.disabled {
            color: rgba(27, 79, 114, 0.35) !important;
        }

        .ot-table-wrap .dataTables_paginate .paginate_button:hover {
            background: var(--ot-s2-08) !important;
            border-color: var(--ot-s2-18) !important;
            color: var(--ot-primary) !important;
        }

        .ot-table-wrap .dataTables_paginate .paginate_button.current {
            background: var(--ot-primary) !important;
            border-color: var(--ot-primary) !important;
            color: #fff !important;
        }

        .ot-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }

        .ot-table thead th {
            background: #ffffff;
            color: rgba(27, 79, 114, 0.62);
            border-bottom: 2px solid var(--ot-s2-12);
            font-size: .72rem;
            letter-spacing: .06em;
            font-weight: 800;
            text-transform: uppercase;
            padding: .85rem .95rem;
            white-space: nowrap;
            text-align: left;
        }

        .ot-table thead th.ot-actions-col,
        .ot-table tbody td.ot-actions-cell {
            text-align: end;
        }

        .ot-table tbody td {
            padding: .85rem .95rem;
            font-weight: 550;
            font-size: .9rem;
            color: rgba(23, 50, 77, 0.86);
            vertical-align: middle;
            white-space: nowrap;
            border-bottom: 1px solid var(--ot-s2-08);
        }

        .ot-table tbody td:first-child {
            font-weight: 800;
            color: var(--ot-primary);
        }

        .ot-table tbody tr:nth-child(even) td {
            background: #f7fbfe;
        }

        .ot-table tbody tr:hover td {
            background: var(--ot-s2-06);
        }

        .ot-type-badge {
            background: var(--ot-s2-08);
            border: 1px solid var(--ot-s2-18);
            color: var(--ot-primary);
            border-radius: 999px;
            padding: .35rem .65rem;
            font-weight: 700;
            font-size: .78rem;
        }

        .ot-status-badge {
            display: inline-block;
            border-radius: 999px;
            padding: .35rem .7rem;
            font-weight: 700;
            font-size: .78rem;
            letter-spacing: .01em;
            border: 1px solid transparent;
        }

        .ot-stage-booked {
            background: #FFF6DF;
            border-color: #F0D48A;
            color: #92660A;
        }

        .ot-stage-confirmed,
        .ot-stage-discharged {
            background: #E7F8EF;
            border-color: #A9E4C4;
            color: #1E8E5A;
        }

        .ot-stage-cancelled {
            background: #FCEAEA;
            border-color: #F0B3AC;
            color: #C0392B;
        }

        .ot-stage-checkedin {
            background: #EEF0F2;
            border-color: #C7CCD1;
            color: #5D6D7E;
        }

        .ot-stage-recommended {
            background: #F3EAF9;
            border-color: #D8B9EE;
            color: #8E44AD;
        }

        .ot-stage-counselled {
            background: #E9F3FB;
            border-color: #AED4F0;
            color: #2E86C1;
        }

        .ot-stage-billing {
            background: #FDF2E3;
            border-color: #F0CC8F;
            color: #B9750B;
        }

        .ot-stage-ward {
            background: #E4F5F1;
            border-color: #A8DED0;
            color: #117864;
        }

        .ot-stage-ready {
            background: #E1F5F1;
            border-color: #9FE0D0;
            color: #148F77;
        }

        .ot-stage-operated {
            background: #E8EEF5;
            border-color: #AFC4DE;
            color: #1A5276;
        }

        .ot-icon-btn {
            border-radius: 8px;
            border: 1px solid var(--ot-s2-18);
            background: var(--ot-s2-06);
            color: var(--ot-primary);
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .ot-icon-btn:hover {
            background: var(--ot-s2-12);
            border-color: var(--ot-s2-24);
            color: var(--ot-primary);
        }

        .ot-icon-btn-confirm {
            border-color: rgba(30, 142, 90, 0.3);
            color: #1E8E5A;
        }

        .ot-icon-btn-confirm:hover {
            background: rgba(30, 142, 90, 0.12);
            color: #1E8E5A;
        }

        .ot-icon-btn-cancel {
            border-color: rgba(192, 57, 43, 0.3);
            color: #C0392B;
        }

        .ot-icon-btn-cancel:hover {
            background: rgba(192, 57, 43, 0.12);
            color: #C0392B;
        }

        .ot-empty {
            padding: 2.25rem 1rem !important;
            color: rgba(27, 79, 114, 0.55) !important;
            font-weight: 700;
        }

        @media (prefers-reduced-motion: reduce) {

            .ot-appt-page,
            .ot-premium-card,
            .ot-appt-page .btn,
            .ot-appt-page .hms-btn {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
@endpush

@push('scripts')
    {{-- OT Appointments table: client-side DataTable (search box, page-length,
    and pagination) — mirrors the pattern used on the Patients index page. --}}
    <script>
        $(function () {
            if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) {
                return;
            }

            var $table = $('#otAppointmentsTable');
            if (!$table.length || jQuery.fn.DataTable.isDataTable($table[0])) {
                return;
            }

            $table.find('tbody tr').each(function () {
                var $cells = jQuery(this).children('td');
                if ($cells.length === 1 && $cells.first().attr('colspan')) {
                    jQuery(this).remove();
                }
            });

            $table.DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                ordering: false,
                autoWidth: false,
                language: {
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'Showing 0 entries',
                    infoFiltered: '(filtered from _MAX_ total entries)',
                    emptyTable: 'No appointments found.',
                    zeroRecords: 'No matching appointments found.',
                    paginate: { previous: 'Previous', next: 'Next' }
                }
            });
        });
    </script>
@endpush