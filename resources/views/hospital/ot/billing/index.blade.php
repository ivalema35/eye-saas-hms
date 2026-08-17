@extends('hospital.layouts.app')
@section('title', 'Discharge & Invoices')
{{-- Layout page-header intentionally unused — the heading + breadcrumb are
rendered inside the card itself so the whole block (heading, breadcrumb,
panel bar, table) sits inside one bordered card, matching the OT
Appointments / Ward Management / OT Assistant design. --}}

@section('content')
    <div class="ot-billing-page">
        <div class="card otb-outer-card border-0">
            <div class="otb-header-block">
                <div class="otb-header-title">
                    <i class="bi bi-receipt-cutoff"></i> Discharge &amp; Invoices
                </div>
                <nav class="otb-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                    <span class="otb-breadcrumb-sep">/</span>
                    <span>OT</span>
                    <span class="otb-breadcrumb-sep">/</span>
                    <span class="otb-breadcrumb-current">Billing</span>
                </nav>
            </div>

            <div class="otb-inner-panel">
                <div class="otb-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="otb-title-wrap">
                        <span class="otb-title-icon">
                            <i class="bi bi-receipt fs-4"></i>
                        </span>
                        <div>
                            <h5 class="mb-0 fw-bold otb-title">
                                Billing Desk
                            </h5>
                            <div class="otb-subtitle">Generate invoices and print discharge documents.</div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive otb-table-wrap">
                        <div id="otbBillingTableContainer">
                            <table class="otbBilling-table" id="otbBillingTable" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>OT Date</th>
                                        <th>Status</th>
                                        <th>Invoice</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($bookings as $booking)
                                        @php
                                            $hasInvoice = in_array((int) $booking->id, $invoiceBookingIds, true);
                                        @endphp
                                        <tr>
                                            <td><span class="otb-patient-cell"><i
                                                        class="bi bi-person-fill"></i>{{ $booking->patient?->full_name ?? '-' }}</span>
                                            </td>
                                            <td><span class="otb-date-cell"><i
                                                        class="bi bi-calendar2-event"></i>{{ optional($booking->surgery_date)->format('d M Y') }}</span>
                                            </td>
                                            <td><span
                                                    class="otb-status-badge">{{ strtoupper((string) $booking->ot_status) }}</span>
                                            </td>
                                            <td>
                                                @if($hasInvoice)
                                                    <span class="otb-invoice-badge otb-invoice-generated">Generated</span>
                                                @else
                                                    <span class="otb-invoice-badge otb-invoice-pending">Pending</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if(!$hasInvoice)
                                                    <form method="POST"
                                                        action="{{ route('hospital.ot.invoice.generate', ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                                                        class="d-inline-flex align-items-center gap-2">
                                                        @csrf
                                                        <input type="date" name="follow_up_date"
                                                            class="form-control form-control-sm" style="width:auto;"
                                                            value="{{ now()->addDays(7)->format('Y-m-d') }}" title="Follow-up date">
                                                        <button type="submit" class="btn btn-sm otb-generate-btn">
                                                            <i class="bi bi-file-earmark-plus me-1"></i> Generate
                                                        </button>
                                                    </form>
                                                @endif

                                                @if($hasInvoice)
                                                    <div class="otb-action-group">
                                                        <a href="{{ route('hospital.ot.summary-bill.print', ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                                                            class="btn btn-sm otb-print-btn">
                                                            <i class="bi bi-receipt-cutoff"></i> Bill Summary
                                                        </a>
                                                        <a href="{{ route('hospital.ot.discharge.print', ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                                                            class="btn btn-sm otb-print-btn otb-print-btn-discharge">
                                                            <i class="bi bi-file-medical"></i> Discharge
                                                        </a>
                                                        <a href="{{ route('hospital.ot.certificate.print', ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                                                            class="btn btn-sm otb-print-btn">
                                                            <i class="bi bi-patch-check"></i> Certificate
                                                        </a>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center otb-empty-cell">
                                                <i class="bi bi-inbox me-1"></i> No records available for billing.
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
                            Discharge & Invoices Index — design refresh (hospital shell theme, #1B4F72).
                            Keep Blade/dynamic logic untouched; CSS-only + layout wrappers.
                        */

        .ot-billing-page {
            --otb-primary: #1B4F72;
            --otb-s2-06: rgba(27, 79, 114, 0.06);
            --otb-s2-08: rgba(27, 79, 114, 0.08);
            --otb-s2-12: rgba(27, 79, 114, 0.12);
            --otb-s2-18: rgba(27, 79, 114, 0.18);
            --otb-s2-24: rgba(27, 79, 114, 0.24);
        }

        .otb-outer-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.12) !important;
            border-radius: 0.90rem;
            box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
            overflow: hidden;
        }

        .otb-header-block {
            background: #ffffff;
            padding: 1.25rem 1.5rem 1rem;
        }

        .otb-header-title {
            font-weight: 800;
            font-size: 1.3rem;
            color: #1b4f72;
            letter-spacing: -.015em;
            display: flex;
            align-items: center;
            gap: .55rem;
        }

        .otb-header-title i {
            color: var(--otb-primary);
            font-size: 1.2rem;
        }

        .otb-breadcrumb {
            margin-top: .4rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: #8891a0;
        }

        .otb-breadcrumb a {
            color: #8891a0;
            text-decoration: none;
        }

        .otb-breadcrumb a:hover {
            color: var(--otb-primary);
        }

        .otb-breadcrumb-sep {
            color: #c3c9d3;
        }

        .otb-breadcrumb-current {
            color: #4a5568;
            font-weight: 600;
        }

        .ot-billing-page .otb-inner-panel {
            margin: 0 1.5rem 1.5rem;
            border: 1px solid rgba(15, 79, 134, 0.12);
            border-radius: 10px !important;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(27, 79, 114, 0.06);
        }

        .ot-billing-page .otb-card-header {
            background: var(--otb-primary) !important;
            padding: 1.15rem 1.5rem !important;
        }

        .ot-billing-page .otb-title-wrap {
            display: flex;
            align-items: center;
            gap: .85rem;
        }

        .ot-billing-page .otb-title-icon {
            width: 40px;
            height: 40px;
            border-radius: 14px !important;
            background: rgba(255, 255, 255, 0.14) !important;
            color: #ffffff !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .ot-billing-page .otb-title,
        .ot-billing-page .otb-subtitle {
            color: #ffffff !important;
        }

        .ot-billing-page .otb-title {
            font-weight: 800;
            letter-spacing: -0.2px;
        }

        .ot-billing-page .otb-subtitle {
            margin: .15rem 0 0;
            font-weight: 500;
            opacity: .85;
            font-size: .85rem;
        }

        .ot-billing-page .otb-table-wrap {
            padding: 0 1.5rem 1.25rem !important;
        }

        .otb-table-wrap .dataTables_wrapper {
            padding-top: .25rem;
        }

        .otb-table-wrap .dataTables_length,
        .otb-table-wrap .dataTables_filter {
            padding: 1rem 0 .75rem;
            font-size: .85rem;
            color: rgba(27, 79, 114, 0.72);
        }

        .otb-table-wrap .dataTables_info,
        .otb-table-wrap .dataTables_paginate {
            padding: .75rem 0 .25rem;
            font-size: .85rem;
            color: rgba(27, 79, 114, 0.72);
        }

        .otb-table-wrap .dataTables_filter input,
        .otb-table-wrap .dataTables_length select.form-select,
        .otb-table-wrap .dataTables_length select {
            border: 1px solid var(--otb-s2-18) !important;
            border-radius: 8px;
            font-size: .85rem;
            color: var(--otb-primary) !important;
            background-color: #fff;
            padding: .3rem .6rem;
            margin-left: .5rem;
        }

        .otb-table-wrap .dataTables_length select.form-select,
        .otb-table-wrap .dataTables_length select {
            margin: 0 .4rem;
            padding-right: 1.75rem;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%231B4F72' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
        }

        .otb-table-wrap .dataTables_filter input:focus,
        .otb-table-wrap .dataTables_length select:focus {
            outline: none;
            border-color: var(--otb-primary);
            box-shadow: 0 0 0 .18rem var(--otb-s2-12);
        }

        .otb-table-wrap .dataTables_paginate .paginate_button {
            border-radius: 8px !important;
            padding: .3rem .65rem !important;
            margin-left: .2rem !important;
            border: 1px solid transparent !important;
            color: var(--otb-primary) !important;
        }

        .otb-table-wrap .dataTables_paginate .paginate_button.disabled {
            color: rgba(27, 79, 114, 0.35) !important;
        }

        .otb-table-wrap .dataTables_paginate .paginate_button:hover {
            background: var(--otb-s2-08) !important;
            border-color: var(--otb-s2-18) !important;
            color: var(--otb-primary) !important;
        }

        .otb-table-wrap .dataTables_paginate .paginate_button.current {
            background: var(--otb-primary) !important;
            border-color: var(--otb-primary) !important;
            color: #fff !important;
        }

        .otbBilling-table {
            width: 100% !important;
            margin-bottom: 0 !important;
            border-collapse: collapse !important;
        }

        .otbBilling-table thead tr,
        .otbBilling-table thead th {
            background: #ffffff !important;
        }

        .otbBilling-table thead th {
            color: rgba(27, 79, 114, 0.62) !important;
            border: 0 !important;
            border-bottom: 2px solid var(--otb-s2-12) !important;
            border-radius: 0 !important;
            font-size: .72rem;
            letter-spacing: .06em;
            font-weight: 800;
            text-transform: uppercase;
            padding: .85rem .95rem;
            white-space: nowrap;
            text-align: left;
        }

        .otbBilling-table thead th.text-end,
        .otbBilling-table tbody td.text-end {
            text-align: end;
        }

        .otbBilling-table tbody td {
            background: rgba(255, 255, 255, 0.92) !important;
            padding: .85rem .95rem;
            font-weight: 550;
            font-size: .9rem;
            color: rgba(23, 50, 77, 0.86);
            vertical-align: middle;
            white-space: nowrap;
            border: 0 !important;
            border-bottom: 1px solid var(--otb-s2-08) !important;
            border-radius: 0 !important;
        }

        .otbBilling-table tbody tr:nth-child(even) td {
            background: #f7fbfe !important;
        }

        .otbBilling-table tbody tr:hover td {
            background: var(--otb-s2-06) !important;
        }

        .otb-patient-cell,
        .otb-date-cell {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }

        .otb-patient-cell i,
        .otb-date-cell i {
            color: var(--otb-primary);
        }

        .otb-status-badge {
            display: inline-block !important;
            background: #ebf5fb !important;
            border: 1px solid var(--otb-s2-18);
            color: var(--otb-primary) !important;
            border-radius: 999px;
            padding: .35rem .75rem;
            font-size: .74rem;
            font-weight: 800;
            letter-spacing: .03em;
        }

        .otb-invoice-badge {
            display: inline-block;
            border-radius: 999px;
            padding: .35rem .7rem;
            font-weight: 700;
            font-size: .78rem;
            border: 1px solid transparent;
        }

        .otb-invoice-generated {
            background: #E7F8EF;
            border-color: #A9E4C4;
            color: #1E8E5A;
        }

        .otb-invoice-pending {
            background: #FFF6DF;
            border-color: #F0D48A;
            color: #92660A;
        }

        .otb-action-group {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: .45rem;
            flex-wrap: wrap;
        }

        .otb-generate-btn {
            background: var(--otb-primary) !important;
            border-color: var(--otb-primary) !important;
            color: #fff !important;
            border-radius: 8px;
            font-weight: 700;
            font-size: .82rem;
            padding: .4rem .85rem;
        }

        .otb-print-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .42rem;
            min-height: 34px;
            border-radius: 8px !important;
            border: 1px solid var(--otb-s2-18) !important;
            background: var(--otb-s2-06) !important;
            color: var(--otb-primary) !important;
            font-weight: 700 !important;
            font-size: .78rem;
            padding: .4rem .75rem !important;
            line-height: 1;
            transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, border-color 170ms ease, color 170ms ease;
        }

        .otb-print-btn:hover,
        .otb-print-btn:focus {
            transform: translateY(-1px);
            background: var(--otb-primary) !important;
            border-color: var(--otb-primary) !important;
            color: #ffffff !important;
        }

        .otb-empty-cell {
            padding: 2.25rem 1rem !important;
            background: transparent !important;
            border: 0 !important;
            border-radius: 0 !important;
            color: rgba(27, 79, 114, 0.55) !important;
            font-weight: 700;
        }
    </style>
@endpush

@push('scripts')
    {{-- Billing table: client-side DataTable (search box, page-length, and
    pagination) — mirrors the pattern used on the OT Appointments / Ward
    Management / OT Assistant Dashboard index pages. --}}
    <script>
        $(function () {
            if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) {
                return;
            }

            var $table = $('#otbBillingTable');
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
                    emptyTable: 'No records available for billing.',
                    zeroRecords: 'No matching records found.',
                    paginate: { previous: 'Previous', next: 'Next' }
                }
            });
        });
    </script>
@endpush