@extends('hospital.layouts.app')
@section('title', 'Collection — ' . $reception->name)

@section('content')
    @php
$isSingleDay = $startDate === $endDate;
$rangeLabel = $isSingleDay
    ? \Carbon\Carbon::parse($startDate)->format('d M Y')
    : \Carbon\Carbon::parse($startDate)->format('d M Y') . ' – ' . \Carbon\Carbon::parse($endDate)->format('d M Y');
$backUrl = route('hospital.dashboard.collection', ['slug' => $slug, 'start_date' => $startDate, 'end_date' => $endDate]);
$exportUrl = route('hospital.dashboard.collection.export', [
    'slug' => $slug,
    'reception' => $reception->id,
    'start_date' => $startDate,
    'end_date' => $endDate,
]);
    @endphp
    <div class="acs-page">

        <div class="acs-topbar">
            <nav class="acs-breadcrumb" aria-label="breadcrumb">
                <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                <span class="acs-sep">/</span>
                <a href="{{ $backUrl }}">Total Collection</a>
                <span class="acs-sep">/</span>
                <span class="acs-current">{{ $reception->name }}</span>
            </nav>
            <a href="{{ $backUrl }}" class="acs-btn acs-btn-light">
                <i class="bi bi-arrow-left"></i> All Reception
            </a>
        </div>

        <div class="acs-hero">
            <div class="acs-hero-main">
                <span class="acs-hero-icon"><i class="bi bi-cash-stack"></i></span>
                <div>
                    <div class="acs-hero-label">Total Collection</div>
                    <div class="acs-hero-value">{{ money($total, 0) }}</div>
                    <div class="acs-hero-meta">
                        <span><i class="bi bi-person-badge"></i> {{ $reception->name }}</span>
                        <span><i class="bi bi-people"></i> {{ $count }}
                            {{ \Illuminate\Support\Str::plural('patient', $count) }}</span>
                        <span><i class="bi bi-calendar3"></i> {{ $rangeLabel }}</span>
                    </div>
                </div>
            </div>

            <form method="GET"
                action="{{ route('hospital.dashboard.collection.show', ['slug' => $slug, 'reception' => $reception->id]) }}"
                class="acs-hero-tools">
                <div class="acs-date">
                    <label for="acsDateRange">Date Range</label>
                    <input type="text" id="acsDateRange" class="form-control" data-hms-date-range
                        data-start-name="start_date" data-end-name="end_date" data-start-value="{{ $startDate }}"
                        data-end-value="{{ $endDate }}" data-auto-submit="1" placeholder="Select start → end date"
                        autocomplete="off" readonly>
                </div>
                <a href="{{ route('hospital.dashboard.collection.show', ['slug' => $slug, 'reception' => $reception->id]) }}"
                    class="acs-btn acs-btn-ghost" title="Back to today">
                    <i class="bi bi-arrow-counterclockwise"></i> Today
                </a>
                <a href="{{ $exportUrl }}" class="acs-btn acs-btn-white" @if($count === 0) aria-disabled="true" tabindex="-1"
                style="pointer-events:none;opacity:.6" @endif>
                    <i class="bi bi-file-earmark-excel"></i> Export
                </a>
            </form>
        </div>

        <div class="acs-card">
            <div class="acs-card-head">
                <div class="acs-card-title">
                    <span class="acs-card-icon"><i class="bi bi-person-lines-fill"></i></span>
                    <div>
                        <h5 class="mb-0">Registered Patients</h5>
                        <small>Registered by {{ $reception->name }} · {{ $rangeLabel }}</small>
                    </div>
                </div>
                <span class="acs-count-pill">{{ $count }} {{ \Illuminate\Support\Str::plural('patient', $count) }}</span>
            </div>

            <div class="acs-table-wrap">
                <table class="acs-table" id="acsPatientsTable" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Patient Name</th>
                            <th>Age</th>
                            <th>Case Type</th>
                            <th class="text-end">Case Fee</th>
                            <th>Doctor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($patients as $i => $patient)
                            @php $stage = $patient->workflowStage(); @endphp
                            <tr>
                                <td class="acs-sr">{{ $i + 1 }}</td>
                                <td>
                                    <div class="acs-patient">
                                        <span
                                            class="acs-avatar">{{ mb_strtoupper(mb_substr((string) $patient->first_name, 0, 1) ?: '?') }}</span>
                                        <span>
                                            <span class="acs-name">{{ $patient->full_name ?: '—' }}</span>
                                            <span class="acs-code">
                                                {{ $patient->patient_code }}
                                                @unless($isSingleDay)·
                                                {{ $patient->appointment_date?->format('d M') }}@endunless
                                            </span>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    {{ $patient->age !== null && $patient->age !== '' ? $patient->age . ' y' : '—' }}
                                    @if($patient->gender)<span class="acs-muted">/
                                    {{ ucfirst(substr((string) $patient->gender, 0, 1)) }}</span>@endif
                                </td>
                                <td><span class="acs-case">{{ $patient->caseType?->case_type ?: '—' }}</span></td>
                                <td class="text-end acs-fee" data-order="{{ (float) $patient->case_fee }}">
                                    {{ money((float) $patient->case_fee, 0) }}</td>
                                <td>{{ $patient->doctor?->name ? 'Dr. ' . $patient->doctor->name : '—' }}</td>
                                <td>
                                    <span class="acs-status acs-tone-{{ $stage['tone'] }}">
                                        <i class="bi {{ $stage['icon'] }}"></i> {{ $stage['label'] }}
                                    </span>
                                    @if($stage['sub'])
                                        <div class="acs-status-sub">{{ $stage['sub'] }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="acs-empty">
                                    <i class="bi bi-inbox"></i>
                                    No patients registered by {{ $reception->name }} for {{ $rangeLabel }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($count > 0)
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end">Total</td>
                                <td class="text-end">{{ money($total, 0) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .acs-page {
            --c: #1B4F72;
            --c-dark: #154360;
            --soft: #EBF5FB;
            --line: rgba(27, 79, 114, .12);
            padding: .25rem 0 2rem;
            color: var(--c);
            animation: acs-in 380ms ease both;
        }

        @keyframes acs-in {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        .acs-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            margin: .35rem .35rem 1rem;
        }

        .acs-breadcrumb {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: #8891a0;
            flex-wrap: wrap;
        }

        .acs-breadcrumb a {
            color: #8891a0;
            text-decoration: none;
        }

        .acs-breadcrumb a:hover {
            color: var(--c);
        }

        .acs-sep {
            color: #c3c9d3;
        }

        .acs-current {
            color: #4a5568;
            font-weight: 700;
        }

        .acs-btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1rem;
            border-radius: 10px;
            font-weight: 800;
            font-size: .85rem;
            text-decoration: none;
            border: 1.5px solid transparent;
            white-space: nowrap;
            transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
        }

        .acs-btn:hover {
            transform: translateY(-1px);
        }

        .acs-btn-light {
            background: #fff;
            color: var(--c);
            border-color: rgba(27, 79, 114, .22);
        }

        .acs-btn-light:hover {
            background: var(--soft);
            color: var(--c);
        }

        .acs-btn-white {
            background: #fff;
            color: var(--c);
            box-shadow: 0 10px 22px rgba(0, 0, 0, .12);
        }

        .acs-btn-white:hover {
            color: var(--c-dark);
            box-shadow: 0 14px 28px rgba(0, 0, 0, .18);
        }

        .acs-btn-ghost {
            background: rgba(255, 255, 255, .12);
            color: #fff;
            border-color: rgba(255, 255, 255, .35);
        }

        .acs-btn-ghost:hover {
            background: rgba(255, 255, 255, .22);
            color: #fff;
        }

        .acs-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
            flex-wrap: wrap;
            padding: 0.9rem;
            margin-bottom: 1.25rem;
            border-radius: 22px;
            color: #fff;
            background: #1b4f72;
            box-shadow: 0 20px 44px rgba(27, 79, 114, .25);
        }

        .acs-hero-main {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 0;
        }

        .acs-hero-icon {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .16);
            font-size: 1.6rem;
        }

        .acs-hero-label {
            font-size: .74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: rgba(255, 255, 255, .8);
        }

        .acs-hero-value {
            font-size: 2.3rem;
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -.03em;
            margin: .15rem 0 .35rem;
        }

        .acs-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
        }

        .acs-hero-meta span {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .65rem;
            border-radius: 999px;
            font-size: .76rem;
            font-weight: 700;
            background: rgba(255, 255, 255, .14);
        }

        .acs-hero-tools {
            display: flex;
            align-items: flex-end;
            gap: .6rem;
            flex-wrap: wrap;
        }

        .acs-date label {
            display: block;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(255, 255, 255, .8);
            margin-bottom: .3rem;
        }

        .acs-date .form-control {
            min-width: 230px;
            border-radius: 10px;
            border: 0;
            font-weight: 700;
            color: var(--c);
            background: #fff;
            padding: .55rem .8rem;
            cursor: pointer;
        }

        .acs-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(27, 79, 114, .08);
            overflow: hidden;
        }

        .acs-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            padding: 1.1rem 1.25rem;
            background: var(--c);
            color: #fff;
        }

        .acs-card-title {
            display: flex;
            align-items: center;
            gap: .8rem;
        }

        .acs-card-title h5 {
            font-weight: 900;
            color: #fff;
        }

        .acs-card-title small {
            color: rgba(255, 255, 255, .75);
            font-weight: 600;
        }

        .acs-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 13px;
            background: #fff;
            color: var(--c);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .acs-count-pill {
            background: rgba(255, 255, 255, .9);
            color: var(--c);
            border-radius: 999px;
            padding: .4rem .85rem;
            font-weight: 900;
            font-size: .8rem;
        }

        .acs-table-wrap {
            padding: .25rem 1.25rem 1.1rem;
            overflow-x: auto;
        }

        .acs-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
        }

        .acs-table thead th {
            background: var(--soft);
            color: var(--c);
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: .75rem .9rem;
            white-space: nowrap;
            border-bottom: 2px solid rgba(27, 79, 114, .15);
        }

        .acs-table tbody td {
            padding: .75rem .9rem;
            border-bottom: 1px solid #EEF3F7;
            color: #1C2833;
            font-weight: 600;
            font-size: .88rem;
            vertical-align: middle;
            white-space: nowrap;
        }

        .acs-table tbody tr:hover td {
            background: #F7FBFE;
        }

        .acs-table tfoot td {
            padding: .8rem .9rem;
            font-weight: 900;
            color: var(--c);
            background: var(--soft);
            border-top: 2px solid rgba(27, 79, 114, .15);
        }

        .acs-sr {
            color: #94a3b8 !important;
            font-size: .8rem !important;
        }

        .acs-patient {
            display: flex;
            align-items: center;
            gap: .6rem;
        }

        .acs-avatar {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: var(--soft);
            color: var(--c);
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .acs-name {
            display: block;
            font-weight: 800;
            color: var(--c);
        }

        .acs-code {
            display: block;
            font-size: .72rem;
            color: #7B8A97;
            font-weight: 600;
        }

        .acs-muted {
            color: #94a3b8;
            font-size: .78rem;
            margin-left: .15rem;
        }

        .acs-case {
            display: inline-block;
            padding: .22rem .6rem;
            border-radius: 8px;
            background: var(--soft);
            color: var(--c);
            font-size: .76rem;
            font-weight: 800;
        }

        .acs-fee {
            font-weight: 900 !important;
            color: var(--c) !important;
        }

        .acs-status {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .3rem .7rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 800;
            border: 1px solid transparent;
        }

        .acs-status-sub {
            font-size: .7rem;
            color: #7B8A97;
            font-weight: 600;
            margin-top: .2rem;
            padding-left: .2rem;
        }

        .acs-tone-primary {
            background: #E3EEF7;
            color: #154360;
            border-color: #A9CCE3;
        }

        .acs-tone-info {
            background: #EBF5FB;
            color: #1B4F72;
            border-color: #AED6F1;
        }

        .acs-tone-warning {
            background: #FEF5E7;
            color: #9A5B0B;
            border-color: #F8C471;
        }

        .acs-tone-success {
            background: #E9F7EF;
            color: #1E8449;
            border-color: #A9DFBF;
        }

        .acs-tone-danger {
            background: #FDEDEC;
            color: #B03A2E;
            border-color: #F1948A;
        }

        .acs-tone-purple {
            background: #F4ECF7;
            color: #6C3483;
            border-color: #D2B4DE;
        }

        .acs-tone-teal {
            background: #E8F8F5;
            color: #117864;
            border-color: #A3E4D7;
        }

        .acs-tone-muted {
            background: #F2F4F4;
            color: #5D6D7E;
            border-color: #D5DBDB;
        }

        .acs-empty {
            text-align: center;
            padding: 2.5rem 1rem !important;
            color: rgba(27, 79, 114, .65) !important;
            font-weight: 800 !important;
            white-space: normal !important;
        }

        .acs-empty i {
            display: block;
            font-size: 2rem;
            opacity: .45;
            margin-bottom: .4rem;
        }

        .acs-table-wrap .dataTables_wrapper .dataTables_length,
        .acs-table-wrap .dataTables_wrapper .dataTables_filter {
            padding: .9rem 0 .6rem;
        }

        .acs-table-wrap .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .acs-table-wrap .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: var(--c) !important;
            border-color: var(--c) !important;
            color: #fff !important;
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .acs-hero-value {
                font-size: 1.8rem;
            }

            .acs-date .form-control {
                min-width: 0;
                width: 100%;
            }

            .acs-hero-tools {
                width: 100%;
            }

            .acs-date {
                flex: 1 1 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .acs-page {
                animation: none;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(function () {
            if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) {
                return;
            }
            var $table = $('#acsPatientsTable');
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
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                order: [],
                autoWidth: false,
                columnDefs: [{ targets: 0, orderable: false, searchable: false }],
                language: {
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ patients',
                    infoEmpty: 'Showing 0 patients',
                    infoFiltered: '(filtered from _MAX_ total)',
                    emptyTable: @json('No patients registered by ' . $reception->name . ' for ' . $rangeLabel . '.'),
                    zeroRecords: 'No matching patients found.',
                    paginate: { previous: 'Previous', next: 'Next' }
                },
                drawCallback: function () {
                    var api = this.api();
                    var start = api.page.info().start;
                    api.column(0, { search: 'applied', order: 'applied', page: 'current' }).nodes().each(function (cell, i) {
                        cell.innerHTML = start + i + 1;
                    });
                }
            });
        });
    </script>
@endpush