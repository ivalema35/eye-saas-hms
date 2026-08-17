@extends('hospital.layouts.app')
@section('title', 'OT Counsellor Dashboard')
{{-- Layout page-header intentionally unused — the heading + breadcrumb are
rendered inside the card itself so the whole block (heading, breadcrumb,
panel bars, tables) sits inside one bordered card, matching the OT
Appointments / Ward Management / OT Assistant / Billing / Accountant
design. --}}

@section('content')
    <div class="ot-counsellor-page">
        <div class="card ot-outer-card border-0">
            <div class="ot-header-block">
                <div class="ot-header-title">
                    <i class="bi bi-chat-left-heart-fill"></i> OT Counsellor Dashboard
                </div>
                <nav class="ot-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                    <span class="ot-breadcrumb-sep">/</span>
                    <span>OT</span>
                    <span class="ot-breadcrumb-sep">/</span>
                    <span>Counsellor</span>
                    <span class="ot-breadcrumb-sep">/</span>
                    <span class="ot-breadcrumb-current">Dashboard</span>
                </nav>
            </div>

            <div class="ot-inner-panel">
                <div class="ot-card-header">
                    <div class="ot-title-wrap">
                        <span class="ot-title-icon" aria-hidden="true">
                            <i class="bi bi-chat-left-heart" style="font-size: 1.2rem;"></i>
                        </span>
                        <div class="flex-grow-1">
                            <h5 class="ot-title">Awaiting Counselling</h5>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if(session('success'))
                        <div class="alert alert-success mx-4 mt-3 ot-alert">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger mx-4 mt-3 ot-alert">{{ session('error') }}</div>
                    @endif

                    <div class="ot-table-wrap">
                        <div id="otCounsellingTableContainer">
                            <table class="otCounselling-table" id="otCounsellingTable" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Phone</th>
                                        <th>OT Doctor</th>
                                        <th>OT Date</th>
                                        <th>Eye</th>
                                        <th>Surgery Type</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($bookings as $booking)
                                        <tr>
                                            <td><span class="ot-patient-cell"><i
                                                        class="bi bi-person-fill"></i>{{ $booking->patient?->full_name ?? '-' }}</span>
                                            </td>
                                            <td>{{ $booking->patient?->contact_no ?? '-' }}</td>
                                            <td>{{ $booking->otDoctor?->name ? 'Dr. ' . $booking->otDoctor->name : '-' }}</td>
                                            <td>{{ optional($booking->surgery_date)->format('d M Y') }}</td>
                                            <td><span class="ot-type-badge">{{ $booking->eye }}</span></td>
                                            <td>{{ $booking->ot_type }}</td>
                                            <td>
                                                @if($booking->ot_status === \App\Models\Hospital\OT\OtBooking::STATUS_SURGERY_RECOMMENDED)
                                                    <span class="ot-status-badge ot-status-recommended">Surgery Recommended</span>
                                                @else
                                                    <span class="ot-status-badge ot-status-booked">Booked</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('hospital.ot.counsellor.form', ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                                                    class="btn btn-sm ot-view-btn">
                                                    <i class="bi bi-chat-left-text me-1"></i> Counsel
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center ot-empty">
                                                <i class="bi bi-inbox me-1"></i> No bookings awaiting counselling.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- OT Workflow Upgrade — Phase 5: Payment Verification queue (PDF Step 6) --}}
            <div class="ot-inner-panel">
                <div class="ot-card-header">
                    <div class="ot-title-wrap">
                        <span class="ot-title-icon" aria-hidden="true">
                            <i class="bi bi-shield-check" style="font-size: 1.2rem;"></i>
                        </span>
                        <div class="flex-grow-1">
                            <h5 class="ot-title">Payment Status</h5>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="ot-table-wrap">
                        <div id="otPaymentStatusTableContainer">
                            <table class="otCounselling-table" id="otPaymentStatusTable" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Phone</th>
                                        <th>OT Date</th>
                                        <th>Package Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($paymentVerificationQueue as $booking)
                                        <tr>
                                            <td><span class="ot-patient-cell"><i
                                                        class="bi bi-person-fill"></i>{{ $booking->patient?->full_name ?? '-' }}</span>
                                            </td>
                                            <td>{{ $booking->patient?->contact_no ?? '-' }}</td>
                                            <td>{{ optional($booking->surgery_date)->format('d M Y') }}</td>
                                            <td>{{ money_code((float) ($booking->package_amount ?? 0), 2) }}</td>
                                            <td>
                                                @if($booking->ot_status === \App\Models\Hospital\OT\OtBooking::STATUS_PAID)
                                                    <span class="ot-pay-badge ot-pay-pending">Paid</span>
                                                @else
                                                    <span class="ot-pay-badge ot-pay-paid">Paid</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center ot-empty">
                                                <i class="bi bi-inbox me-1"></i> No bookings paid yet.
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
                            OT Counsellor Dashboard — design refresh (hospital shell theme, #1B4F72).
                            Keep Blade/dynamic logic untouched; CSS-only + layout wrappers.
                        */

        .ot-counsellor-page {
            --ot-secondary: #1B4F72;
            --ot-s2-06: rgba(27, 79, 114, 0.06);
            --ot-s2-08: rgba(27, 79, 114, 0.08);
            --ot-s2-12: rgba(27, 79, 114, 0.12);
            --ot-s2-18: rgba(27, 79, 114, 0.18);
            --ot-s2-24: rgba(27, 79, 114, 0.24);

            position: relative;
            padding: .25rem 0 1.25rem;
            color: var(--ot-secondary);
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

        .ot-counsellor-page .btn {
            border-radius: 8px;
            font-weight: 700;
            transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, border-color 170ms ease, color 170ms ease;
        }

        .ot-counsellor-page .btn:hover {
            transform: translateY(-1px);
        }

        .ot-outer-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.12) !important;
            border-radius: 0.90rem;
            box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
            overflow: hidden;
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
            color: var(--ot-secondary);
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
            color: var(--ot-secondary);
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
            background: var(--ot-secondary);
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
            color: var(--ot-secondary) !important;
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
            border-color: var(--ot-secondary);
            box-shadow: 0 0 0 .18rem var(--ot-s2-12);
        }

        .ot-table-wrap .dataTables_paginate .paginate_button {
            border-radius: 8px !important;
            padding: .3rem .65rem !important;
            margin-left: .2rem !important;
            border: 1px solid transparent !important;
            color: var(--ot-secondary) !important;
        }

        .ot-table-wrap .dataTables_paginate .paginate_button.disabled {
            color: rgba(27, 79, 114, 0.35) !important;
        }

        .ot-table-wrap .dataTables_paginate .paginate_button:hover {
            background: var(--ot-s2-08) !important;
            border-color: var(--ot-s2-18) !important;
            color: var(--ot-secondary) !important;
        }

        .ot-table-wrap .dataTables_paginate .paginate_button.current {
            background: var(--ot-secondary) !important;
            border-color: var(--ot-secondary) !important;
            color: #fff !important;
        }

        .otCounselling-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }

        .otCounselling-table thead th {
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

        .otCounselling-table thead th.text-end,
        .otCounselling-table tbody td.text-end {
            text-align: end;
        }

        .otCounselling-table tbody td {
            padding: .85rem .95rem;
            font-weight: 550;
            font-size: .9rem;
            color: rgba(23, 50, 77, 0.86);
            vertical-align: middle;
            white-space: nowrap;
            border-bottom: 1px solid var(--ot-s2-08);
        }

        .otCounselling-table tbody tr:nth-child(even) td {
            background: #f7fbfe;
        }

        .otCounselling-table tbody tr:hover td {
            background: var(--ot-s2-06);
        }

        .ot-patient-cell {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }

        .ot-patient-cell i {
            color: var(--ot-secondary);
        }

        .ot-type-badge {
            display: inline-block;
            background: var(--ot-s2-08);
            border: 1px solid var(--ot-s2-18);
            color: var(--ot-secondary);
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
            color: #fff;
        }

        .ot-status-recommended {
            background: #E0A800;
        }

        .ot-status-booked {
            background: var(--ot-secondary);
        }

        .ot-pay-badge {
            display: inline-block;
            border-radius: 999px;
            padding: .35rem .7rem;
            font-weight: 700;
            font-size: .78rem;
            border: 1px solid transparent;
        }

        .ot-pay-paid {
            background: #E7F8EF;
            border-color: #A9E4C4;
            color: #1E8E5A;
        }

        .ot-pay-pending {
            background: #FFF6DF;
            border-color: #F0D48A;
            color: #92660A;
        }

        .ot-view-btn {
            border-radius: 8px;
            border: 1px solid var(--ot-secondary);
            background: var(--ot-secondary);
            color: #fff;
            font-weight: 700;
            font-size: .82rem;
            padding: .4rem .8rem;
        }

        .ot-view-btn:hover {
            background: #15405d;
            border-color: #15405d;
            color: #fff;
        }

        .ot-empty {
            padding: 2.25rem 1rem !important;
            color: rgba(27, 79, 114, 0.55) !important;
            font-weight: 700;
        }

        @media (prefers-reduced-motion: reduce) {

            .ot-counsellor-page,
            .ot-outer-card,
            .ot-counsellor-page .btn {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
@endpush

@push('scripts')
    {{-- Counselling / Payment Status tables: client-side DataTable (search
    box, page-length, and pagination) — mirrors the pattern used on the OT
    Appointments / Ward Management / OT Assistant / Billing / Accountant
    index pages. --}}
    <script>
        $(function () {
            if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) {
                return;
            }

            function initOtTable(selector, emptyMessage) {
                var $table = $(selector);
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
                        emptyTable: emptyMessage,
                        zeroRecords: 'No matching records found.',
                        paginate: { previous: 'Previous', next: 'Next' }
                    }
                });
            }

            initOtTable('#otCounsellingTable', 'No bookings awaiting counselling.');
            initOtTable('#otPaymentStatusTable', 'No bookings paid yet.');
        });
    </script>
@endpush