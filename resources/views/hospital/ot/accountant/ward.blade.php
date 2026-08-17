@extends('hospital.layouts.app')
@section('title', 'Ward Management')
{{-- Layout page-header intentionally unused — the heading + breadcrumb are
rendered inside the card itself so the whole block (heading, breadcrumb,
panel bar, table) sits inside one bordered card, matching the OT
Appointments page design. --}}

@section('content')
    <div class="ot-ward-page">
        <div class="card ward-premium-card border-0">
            <div class="ward-header-block">
                <div class="ward-header-title">
                    <i class="bi bi-hospital-fill"></i> Ward Management
                </div>
                <nav class="ward-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                    <span class="ward-breadcrumb-sep">/</span>
                    <span>OT</span>
                    <span class="ward-breadcrumb-sep">/</span>
                    <span class="ward-breadcrumb-current">Ward</span>
                </nav>
            </div>

            <div class="ward-inner-panel">
                <div class="ward-card-header">
                    <div class="ward-title-wrap">
                        <span class="ward-title-icon" aria-hidden="true">
                            <i class="bi bi-hospital" style="font-size: 1.2rem;"></i>
                        </span>
                        <div class="flex-grow-1">
                            <h5 class="ward-title">Ward Entry Queue</h5>
                            <div class="ward-subtitle">Patients on the ward — paid, in ward, or dilated — before OT
                                assignment.</div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="ward-table-wrap">
                        <div id="wardTableContainer">
                            <table class="ward-table" id="wardTable" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Patient Name</th>
                                        <th>Phone</th>
                                        <th>OT Date</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th class="ward-actions-col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($bookings as $booking)
                                        <tr>
                                            <td><span class="ward-patient-cell"><i
                                                        class="bi bi-person-fill"></i>{{ $booking->patient?->full_name ?? '-' }}</span>
                                            </td>
                                            <td><span class="ward-phone-cell"><i
                                                        class="bi bi-telephone-fill"></i>{{ $booking->patient?->contact_no ?? '-' }}</span>
                                            </td>
                                            <td><span class="ward-date-cell"><i
                                                        class="bi bi-calendar2-event"></i>{{ optional($booking->surgery_date)->format('d M Y') }}</span>
                                            </td>
                                            <td><span
                                                    class="ward-status-badge">{{ strtoupper((string) $booking->ot_status) }}</span>
                                            </td>
                                            <td>
                                                @if($booking->payment_status === 'paid')
                                                    <span class="ward-pay-badge ward-pay-paid">Paid</span>
                                                @elseif($booking->payment_status === 'partially_paid')
                                                    <span class="ward-pay-badge ward-pay-partial">Partially Paid</span>
                                                @else
                                                    <span class="ward-pay-badge ward-pay-pending">Pending</span>
                                                @endif
                                            </td>
                                            <td class="ward-actions-cell">
                                                <a href="{{ route('hospital.ot.ward.show', ['slug' => $slug, 'booking' => $booking->id]) }}"
                                                    class="ward-send-btn">
                                                    <i class="bi bi-heart-pulse me-1"></i> Vitals &amp; Eye Drops
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center ward-empty">
                                                <i class="bi bi-inbox me-1"></i> No records available for ward workflow.
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
                                    Ward Management Index — design refresh (hospital shell theme, #1B4F72).
                                    Keep Blade/dynamic logic untouched; CSS-only + layout wrappers.
                                */

        .ot-ward-page {
            --ward-primary: #1B4F72;
            --ward-primary-dark: #154160;
            --ward-s2-06: rgba(27, 79, 114, 0.06);
            --ward-s2-08: rgba(27, 79, 114, 0.08);
            --ward-s2-12: rgba(27, 79, 114, 0.12);
            --ward-s2-18: rgba(27, 79, 114, 0.18);
            --ward-s2-24: rgba(27, 79, 114, 0.24);

            position: relative;
            padding: .25rem 0 1.25rem;
            color: var(--ward-primary);
            animation: ward-page-in 420ms ease both;
        }

        @keyframes ward-page-in {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .ward-premium-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.12) !important;
            border-radius: 0.90rem;
            box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
            overflow: hidden;
            animation: ward-card-rise 520ms cubic-bezier(.2, .9, .2, 1) both;
        }

        @keyframes ward-card-rise {
            from {
                opacity: 0;
                transform: translateY(10px) scale(0.99);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .ward-header-block {
            background: #ffffff;
            padding: 1.25rem 1.5rem 1rem;
        }

        .ward-header-title {
            font-weight: 800;
            font-size: 1.3rem;
            color: #1b4f72;
            letter-spacing: -.015em;
            display: flex;
            align-items: center;
            gap: .55rem;
        }

        .ward-header-title i {
            color: var(--ward-primary);
            font-size: 1.2rem;
        }

        .ward-breadcrumb {
            margin-top: .4rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: #8891a0;
        }

        .ward-breadcrumb a {
            color: #8891a0;
            text-decoration: none;
        }

        .ward-breadcrumb a:hover {
            color: var(--ward-primary);
        }

        .ward-breadcrumb-sep {
            color: #c3c9d3;
        }

        .ward-breadcrumb-current {
            color: #4a5568;
            font-weight: 600;
        }

        .ward-inner-panel {
            margin: 0 1.5rem 1.5rem;
            border: 1px solid rgba(15, 79, 134, 0.12);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(27, 79, 114, 0.06);
        }

        .ward-card-header {
            background: var(--ward-primary);
            padding: 1.15rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .ward-title-wrap {
            display: flex;
            align-items: center;
            gap: .85rem;
            min-width: 0;
        }

        .ward-title-icon {
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

        .ward-title {
            font-weight: 800;
            letter-spacing: -0.2px;
            margin: 0;
            color: #ffffff;
        }

        .ward-subtitle {
            margin: .15rem 0 0;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.78);
            font-size: .85rem;
        }

        .ward-table-wrap {
            padding: 0 1.5rem 1.25rem !important;
            overflow-x: auto;
        }

        .ward-table-wrap .dataTables_wrapper {
            padding-top: .25rem;
        }

        .ward-table-wrap .dataTables_length,
        .ward-table-wrap .dataTables_filter {
            padding: 1rem 0 .75rem;
            font-size: .85rem;
            color: rgba(27, 79, 114, 0.72);
        }

        .ward-table-wrap .dataTables_info,
        .ward-table-wrap .dataTables_paginate {
            padding: .75rem 0 .25rem;
            font-size: .85rem;
            color: rgba(27, 79, 114, 0.72);
        }

        .ward-table-wrap .dataTables_filter input,
        .ward-table-wrap .dataTables_length select.form-select,
        .ward-table-wrap .dataTables_length select {
            border: 1px solid var(--ward-s2-18) !important;
            border-radius: 8px;
            font-size: .85rem;
            color: var(--ward-primary) !important;
            background-color: #fff;
            padding: .3rem .6rem;
            margin-left: .5rem;
        }

        .ward-table-wrap .dataTables_length select.form-select,
        .ward-table-wrap .dataTables_length select {
            margin: 0 .4rem;
            padding-right: 1.75rem;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%231B4F72' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
        }

        .ward-table-wrap .dataTables_filter input:focus,
        .ward-table-wrap .dataTables_length select:focus {
            outline: none;
            border-color: var(--ward-primary);
            box-shadow: 0 0 0 .18rem var(--ward-s2-12);
        }

        .ward-table-wrap .dataTables_paginate .paginate_button {
            border-radius: 8px !important;
            padding: .3rem .65rem !important;
            margin-left: .2rem !important;
            border: 1px solid transparent !important;
            color: var(--ward-primary) !important;
        }

        .ward-table-wrap .dataTables_paginate .paginate_button.disabled {
            color: rgba(27, 79, 114, 0.35) !important;
        }

        .ward-table-wrap .dataTables_paginate .paginate_button:hover {
            background: var(--ward-s2-08) !important;
            border-color: var(--ward-s2-18) !important;
            color: var(--ward-primary) !important;
        }

        .ward-table-wrap .dataTables_paginate .paginate_button.current {
            background: var(--ward-primary) !important;
            border-color: var(--ward-primary) !important;
            color: #fff !important;
        }

        .ward-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }

        .ward-table thead th {
            background: #ffffff;
            color: rgba(27, 79, 114, 0.62);
            border-bottom: 2px solid var(--ward-s2-12);
            font-size: .72rem;
            letter-spacing: .06em;
            font-weight: 800;
            text-transform: uppercase;
            padding: .85rem .95rem;
            white-space: nowrap;
            text-align: left;
        }

        .ward-table thead th.ward-actions-col,
        .ward-table tbody td.ward-actions-cell {
            text-align: end;
        }

        .ward-table tbody td {
            padding: .85rem .95rem;
            font-weight: 550;
            font-size: .9rem;
            color: rgba(23, 50, 77, 0.86);
            vertical-align: middle;
            white-space: nowrap;
            border-bottom: 1px solid var(--ward-s2-08);
        }

        .ward-table tbody tr:nth-child(even) td {
            background: #f7fbfe;
        }

        .ward-table tbody tr:hover td {
            background: var(--ward-s2-06);
        }

        .ward-patient-cell,
        .ward-phone-cell,
        .ward-date-cell {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }

        .ward-patient-cell i,
        .ward-phone-cell i,
        .ward-date-cell i {
            color: var(--ward-primary);
        }

        .ward-status-badge {
            display: inline-block;
            background: #ebf5fb;
            border: 1px solid var(--ward-s2-18);
            color: var(--ward-primary);
            border-radius: 999px;
            padding: .35rem .75rem;
            font-size: .74rem;
            font-weight: 800;
            letter-spacing: .03em;
        }

        .ward-pay-badge {
            display: inline-block;
            border-radius: 999px;
            padding: .35rem .7rem;
            font-weight: 700;
            font-size: .78rem;
            border: 1px solid transparent;
        }

        .ward-pay-paid {
            background: #E7F8EF;
            border-color: #A9E4C4;
            color: #1E8E5A;
        }

        .ward-pay-partial {
            background: #FFF6DF;
            border-color: #F0D48A;
            color: #92660A;
        }

        .ward-pay-pending {
            background: #FCEAEA;
            border-color: #F0B3AC;
            color: #C0392B;
        }

        .ward-send-btn {
            display: inline-flex;
            align-items: center;
            border-radius: 8px;
            border: 1px solid var(--ward-s2-18);
            background: var(--ward-s2-06);
            color: var(--ward-primary);
            font-weight: 700;
            font-size: .82rem;
            padding: .4rem .75rem;
            text-decoration: none;
            white-space: nowrap;
            transition: background 170ms ease, border-color 170ms ease, transform 170ms ease;
        }

        .ward-send-btn:hover {
            background: var(--ward-s2-12);
            border-color: var(--ward-s2-24);
            color: var(--ward-primary);
            transform: translateY(-1px);
        }

        .ward-empty {
            padding: 2.25rem 1rem !important;
            color: rgba(27, 79, 114, 0.55) !important;
            font-weight: 700;
        }

        @media (prefers-reduced-motion: reduce) {

            .ot-ward-page,
            .ward-premium-card {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
@endpush

@push('scripts')
    {{-- Ward table: client-side DataTable (search box, page-length, and
    pagination) — mirrors the pattern used on the OT Appointments index page. --}}
    <script>
        $(function () {
            if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) {
                return;
            }

            var $table = $('#wardTable');
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
                    emptyTable: 'No records available for ward workflow.',
                    zeroRecords: 'No matching records found.',
                    paginate: { previous: 'Previous', next: 'Next' }
                }
            });
        });
    </script>
@endpush