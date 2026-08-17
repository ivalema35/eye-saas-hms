@extends('hospital.layouts.app')
@section('title', 'OT Assistant Dashboard')
{{-- Layout page-header intentionally unused — the heading + breadcrumb are
     rendered inside the card itself so the whole block (heading, breadcrumb,
     panel bar, table) sits inside one bordered card, matching the OT
     Appointments / Ward Management design. --}}

@push('styles')
    <style>
        .ot-assistant-page {
            --ota2-primary: #1B4F72;
            --ota2-s2-06: rgba(27, 79, 114, 0.06);
            --ota2-s2-08: rgba(27, 79, 114, 0.08);
            --ota2-s2-12: rgba(27, 79, 114, 0.12);
            --ota2-s2-18: rgba(27, 79, 114, 0.18);
            --ota2-s2-24: rgba(27, 79, 114, 0.24);
        }

        .ota2-outer-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.12) !important;
            border-radius: 0.90rem;
            box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
            overflow: hidden;
        }

        .ota2-header-block {
            background: #ffffff;
            padding: 1.25rem 1.5rem 1rem;
        }

        .ota2-header-title {
            font-weight: 800;
            font-size: 1.3rem;
            color: #1b4f72;
            letter-spacing: -.015em;
            display: flex;
            align-items: center;
            gap: .55rem;
        }

        .ota2-header-title i {
            color: var(--ota2-primary);
            font-size: 1.2rem;
        }

        .ota2-breadcrumb {
            margin-top: .4rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: #8891a0;
        }

        .ota2-breadcrumb a {
            color: #8891a0;
            text-decoration: none;
        }

        .ota2-breadcrumb a:hover {
            color: var(--ota2-primary);
        }

        .ota2-breadcrumb-sep {
            color: #c3c9d3;
        }

        .ota2-breadcrumb-current {
            color: #4a5568;
            font-weight: 600;
        }

        .ot-assistant-page .ota2-inner-panel {
            margin: 0 1.5rem 1.5rem;
            border: 1px solid rgba(15, 79, 134, 0.12);
            border-radius: 10px !important;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(27, 79, 114, 0.06);
        }

        .ot-assistant-page .ota2-card-header {
            padding: 1.15rem 1.5rem !important;
        }

        .ot-assistant-page .ota2-table-wrap {
            padding: 0 1.5rem 1.25rem !important;
        }

        .ota2-table-wrap .dataTables_wrapper {
            padding-top: .25rem;
        }

        .ota2-table-wrap .dataTables_length,
        .ota2-table-wrap .dataTables_filter {
            padding: 1rem 0 .75rem;
            font-size: .85rem;
            color: rgba(27, 79, 114, 0.72);
        }

        .ota2-table-wrap .dataTables_info,
        .ota2-table-wrap .dataTables_paginate {
            padding: .75rem 0 .25rem;
            font-size: .85rem;
            color: rgba(27, 79, 114, 0.72);
        }

        .ota2-table-wrap .dataTables_filter input,
        .ota2-table-wrap .dataTables_length select.form-select,
        .ota2-table-wrap .dataTables_length select {
            border: 1px solid var(--ota2-s2-18) !important;
            border-radius: 8px;
            font-size: .85rem;
            color: var(--ota2-primary) !important;
            background-color: #fff;
            padding: .3rem .6rem;
            margin-left: .5rem;
        }

        .ota2-table-wrap .dataTables_length select.form-select,
        .ota2-table-wrap .dataTables_length select {
            margin: 0 .4rem;
            padding-right: 1.75rem;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%231B4F72' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
        }

        .ota2-table-wrap .dataTables_filter input:focus,
        .ota2-table-wrap .dataTables_length select:focus {
            outline: none;
            border-color: var(--ota2-primary);
            box-shadow: 0 0 0 .18rem var(--ota2-s2-12);
        }

        .ota2-table-wrap .dataTables_paginate .paginate_button {
            border-radius: 8px !important;
            padding: .3rem .65rem !important;
            margin-left: .2rem !important;
            border: 1px solid transparent !important;
            color: var(--ota2-primary) !important;
        }

        .ota2-table-wrap .dataTables_paginate .paginate_button.disabled {
            color: rgba(27, 79, 114, 0.35) !important;
        }

        .ota2-table-wrap .dataTables_paginate .paginate_button:hover {
            background: var(--ota2-s2-08) !important;
            border-color: var(--ota2-s2-18) !important;
            color: var(--ota2-primary) !important;
        }

        .ota2-table-wrap .dataTables_paginate .paginate_button.current {
            background: var(--ota2-primary) !important;
            border-color: var(--ota2-primary) !important;
            color: #fff !important;
        }

        .otaSurgery-table {
            width: 100% !important;
            margin-bottom: 0 !important;
            border-collapse: collapse !important;
            border-spacing: 0 !important;
        }

        .otaSurgery-table thead tr,
        .otaSurgery-table thead th {
            background: #ffffff !important;
        }

        .otaSurgery-table thead th {
            color: rgba(27, 79, 114, 0.62) !important;
            border: 0 !important;
            border-bottom: 2px solid var(--ota2-s2-12) !important;
            border-radius: 0 !important;
            font-size: .72rem;
            letter-spacing: .06em;
            font-weight: 800;
            text-transform: uppercase;
            padding: .85rem .95rem;
            white-space: nowrap;
            text-align: left;
        }

        .otaSurgery-table thead th.text-end,
        .otaSurgery-table tbody td.text-end {
            text-align: end;
        }

        .otaSurgery-table tbody td {
            background: rgba(255, 255, 255, 0.92) !important;
            padding: .85rem .95rem;
            font-weight: 550;
            font-size: .9rem;
            color: rgba(23, 50, 77, 0.86);
            vertical-align: middle;
            white-space: nowrap;
            border: 0 !important;
            border-bottom: 1px solid var(--ota2-s2-08) !important;
            border-radius: 0 !important;
        }

        .otaSurgery-table tbody tr:nth-child(even) td {
            background: #f7fbfe !important;
        }

        .otaSurgery-table tbody tr:hover td {
            background: var(--ota2-s2-06) !important;
        }

        .otaSurgery-table tbody tr:hover {
            transform: none !important;
            box-shadow: none !important;
        }

        .otd-patient-cell,
        .otd-surgery-cell {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }

        .otd-patient-cell i,
        .otd-surgery-cell i {
            color: var(--ota2-primary);
        }

        .otd-amount-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            background: var(--ota2-s2-08);
            border: 1px solid var(--ota2-s2-18);
            color: var(--ota2-primary);
            border-radius: 999px;
            padding: .35rem .65rem;
            font-weight: 700;
            font-size: .82rem;
        }

        .otd-status-badge {
            display: inline-block !important;
            background: #ebf5fb !important;
            border: 1px solid var(--ota2-s2-18);
            color: var(--ota2-primary) !important;
            border-radius: 999px;
            padding: .35rem .75rem;
            font-size: .74rem;
            font-weight: 800;
            letter-spacing: .03em;
        }

        .ota2-pay-badge {
            display: inline-block;
            border-radius: 999px;
            padding: .35rem .7rem;
            font-weight: 700;
            font-size: .78rem;
            border: 1px solid transparent;
        }

        .ota2-pay-paid {
            background: #E7F8EF;
            border-color: #A9E4C4;
            color: #1E8E5A;
        }

        .ota2-pay-partial {
            background: #FFF6DF;
            border-color: #F0D48A;
            color: #92660A;
        }

        .ota2-pay-pending {
            background: #FCEAEA;
            border-color: #F0B3AC;
            color: #C0392B;
        }

        .otd-empty-cell {
            padding: 2.25rem 1rem !important;
            background: transparent !important;
            border: 0 !important;
            border-radius: 0 !important;
            color: rgba(27, 79, 114, 0.55) !important;
            font-weight: 700;
        }

        .ota2-view-btn {
            border-radius: 8px;
            font-weight: 700;
            font-size: .78rem;
            border-color: var(--ota2-s2-18) !important;
            color: var(--ota2-primary) !important;
            background: var(--ota2-s2-06) !important;
        }

        .ota2-view-btn:hover {
            background: var(--ota2-s2-12) !important;
        }

        .otd-operate-btn {
            border-radius: 8px;
            font-weight: 700;
            font-size: .78rem;
            background: var(--ota2-primary) !important;
            border-color: var(--ota2-primary) !important;
        }

        /* Modal lives in @stack('modals') — full screen overlay, not clipped by .hms-main */
        .ota2-view-modal {
            z-index: 1065 !important;
        }

        .ota2-view-modal .modal-dialog {
            max-width: 760px;
            margin: 1.5rem auto;
        }

        .ota2-view-modal .modal-content {
            border: none !important;
            border-radius: 18px !important;
            background: #fff !important;
            box-shadow: 0 24px 60px rgba(27, 79, 114, .22) !important;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 3rem);
        }

        .ota2-view-modal .modal-header {
            background: #1B4F72 !important;
            color: #fff !important;
            border: 0 !important;
            padding: 1rem 1.25rem;
            flex-shrink: 0;
        }

        .ota2-view-modal .modal-title {
            font-weight: 800;
            font-size: 1rem;
            color: #fff !important;
        }

        .ota2-view-modal .modal-body {
            background: #f8fafc !important;
            padding: 1.25rem !important;
            overflow-y: auto;
            flex: 1 1 auto;
        }

        .ota2-view-modal .modal-footer {
            border-top: 1px solid rgba(27, 79, 114, .1) !important;
            background: #fff !important;
            flex-shrink: 0;
        }

        .ota2-detail-box {
            background: #fff !important;
            border: 1px solid rgba(27, 79, 114, .12);
            border-radius: 12px;
            padding: .85rem 1rem;
            height: 100%;
            min-height: 72px;
        }

        .ota2-detail-label {
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: rgba(27, 79, 114, .55);
            margin-bottom: .35rem;
        }

        .ota2-detail-value {
            font-size: .95rem;
            font-weight: 700;
            color: #1B4F72 !important;
            word-break: break-word;
        }
    </style>
@endpush

@section('content')
    <div class="ot-assistant-page">

        {{-- Surgery Queue — absorbed from the old OT Doctor role (docs/tulsi.md §5) --}}
        <div class="card ota2-outer-card border-0">
            <div class="ota2-header-block">
                <div class="ota2-header-title">
                    <i class="bi bi-clipboard2-pulse-fill"></i> OT Assistant Dashboard
                </div>
                <nav class="ota2-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                    <span class="ota2-breadcrumb-sep">/</span>
                    <span>OT</span>
                    <span class="ota2-breadcrumb-sep">/</span>
                    <span>Assistant</span>
                    <span class="ota2-breadcrumb-sep">/</span>
                    <span class="ota2-breadcrumb-current">Dashboard</span>
                </nav>
            </div>

            <div class="ota2-inner-panel">
                <div class="ota2-card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="ota2-title-wrap">
                        <span class="ota2-title-icon">
                            <i class="bi bi-activity fs-4"></i>
                        </span>
                        <div>
                            <h5 class="mb-0 fw-bold ota2-title">
                                Surgery Queue
                            </h5>
                            <div class="ota2-subtitle">Patients ready for OT — record the surgery.</div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive ota2-table-wrap">
                        <div id="otaSurgeryTableContainer">
                            <table class="otaSurgery-table" id="otaSurgeryTable" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        @if(!empty($seeAll))
                                            <th>Surgeon</th>
                                        @endif
                                        <th>Surgery Type</th>
                                        <th>Package</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($readyBookings as $booking)
                                        @php
    $status = strtoupper((string) $booking->ot_status);
                                        @endphp
                                        <tr>
                                            <td><span class="otd-patient-cell"><i
                                                        class="bi bi-person-fill"></i>{{ $booking->patient?->full_name ?? '-' }}</span>
                                            </td>
                                            @if(!empty($seeAll))
                                                <td>{{ $booking->otDoctor?->name ? 'Dr. ' . $booking->otDoctor->name : '-' }}</td>
                                            @endif
                                            <td><span class="otd-surgery-cell"><i
                                                        class="bi bi-heart-pulse"></i>{{ $booking->ot_type ?? '-' }}</span></td>
                                            <td><span class="otd-amount-pill"><i
                                                        class="bi bi-cash-coin"></i>{{ money_code((float) ($booking->package_amount ?? 0), 2) }}</span>
                                            </td>
                                            <td>
                                                @if($booking->payment_status === 'paid')
                                                    <span class="ota2-pay-badge ota2-pay-paid">Paid</span>
                                                @elseif($booking->payment_status === 'partially_paid')
                                                    <span class="ota2-pay-badge ota2-pay-partial">Partially Paid</span>
                                                @else
                                                    <span class="ota2-pay-badge ota2-pay-pending">Pending</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="otd-status-badge">{{ $status }}</span>
                                            </td>
                                            <td class="text-end text-nowrap">
                                                <button type="button" class="btn btn-sm ota2-view-btn me-2"
                                                    data-bs-toggle="modal" data-bs-target="#ota2ViewModal{{ $booking->id }}">
                                                    <i class="bi bi-eye-fill me-1"></i> View
                                                </button>
                                                <a href="{{ route('hospital.ot.surgery.create', ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                                                    class="btn btn-sm otd-operate-btn">
                                                    <i class="bi bi-heart-pulse me-1"></i> Operate
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ !empty($seeAll) ? 7 : 6 }}" class="text-center otd-empty-cell">
                                                <i class="bi bi-inbox me-1"></i> No bookings ready for surgery.
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

@push('scripts')
    {{-- Surgery Queue table: client-side DataTable (search box, page-length,
    and pagination) — mirrors the pattern used on the OT Appointments /
    Ward Management index pages. --}}
    <script>
        $(function () {
            if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) {
                return;
            }

            var $table = $('#otaSurgeryTable');
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
                    emptyTable: 'No bookings ready for surgery.',
                    zeroRecords: 'No matching records found.',
                    paginate: { previous: 'Previous', next: 'Next' }
                }
            });
        });
    </script>
@endpush

{{-- Outside .hms-main overflow/transform so Bootstrap modal centers and paints fully --}}
@push('modals')
    @foreach($readyBookings as $booking)
        @php
    $apptNo = 'OT-' . str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT);
    $latestPayment = $booking->payments->sortByDesc('id')->first();
    $paymentMode = $latestPayment?->payment_mode
        ?? $booking->payment_mode
        ?? null;
    $paymentModeLabel = $paymentMode
        ? ucfirst(str_replace('_', ' ', (string) $paymentMode))
        : '—';
    $payStatus = $booking->payment_status;
    $payStatusLabel = match ($payStatus) {
        'paid' => 'Paid',
        'partially_paid' => 'Partially Paid',
        'unpriced' => 'Package Not Set',
        default => 'Pending',
    };
        @endphp
        <div class="modal fade ota2-view-modal" id="ota2ViewModal{{ $booking->id }}" tabindex="-1"
            aria-labelledby="ota2ViewModalLabel{{ $booking->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title mb-0" id="ota2ViewModalLabel{{ $booking->id }}">
                            <i class="bi bi-clipboard2-pulse me-2"></i>
                            OT Booking Details — {{ $apptNo }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="ota2-detail-box">
                                    <div class="ota2-detail-label">APPT</div>
                                    <div class="ota2-detail-value">{{ $apptNo }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="ota2-detail-box">
                                    <div class="ota2-detail-label">Name</div>
                                    <div class="ota2-detail-value">{{ $booking->patient?->full_name ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="ota2-detail-box">
                                    <div class="ota2-detail-label">Mobile Number</div>
                                    <div class="ota2-detail-value">{{ $booking->patient?->contact_no ?: '—' }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="ota2-detail-box">
                                    <div class="ota2-detail-label">Date</div>
                                    <div class="ota2-detail-value">
                                        {{ optional($booking->surgery_date)->format('d M Y') ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="ota2-detail-box">
                                    <div class="ota2-detail-label">Doctor</div>
                                    <div class="ota2-detail-value">
                                        {{ $booking->otDoctor?->name ? 'Dr. ' . $booking->otDoctor->name : '—' }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="ota2-detail-box">
                                    <div class="ota2-detail-label">Surgery Name</div>
                                    <div class="ota2-detail-value">{{ $booking->ot_type ?: '—' }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="ota2-detail-box">
                                    <div class="ota2-detail-label">Package</div>
                                    <div class="ota2-detail-value">{{ money_code((float) ($booking->package_amount ?? 0), 2) }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="ota2-detail-box">
                                    <div class="ota2-detail-label">Payment Mode</div>
                                    <div class="ota2-detail-value">{{ $paymentModeLabel }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="ota2-detail-box">
                                    <div class="ota2-detail-label">Payment Status</div>
                                    <div class="ota2-detail-value">
                                        <span
                                            class="badge {{ $payStatus === 'paid' ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis' }}">
                                            {{ $payStatusLabel }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="ota2-detail-box">
                                    <div class="ota2-detail-label">OT Status</div>
                                    <div class="ota2-detail-value">
                                        <span
                                            class="badge text-bg-warning text-uppercase">{{ strtoupper((string) $booking->ot_status) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="ota2-detail-box">
                                    <div class="ota2-detail-label">Eye</div>
                                    <div class="ota2-detail-value">{{ $booking->eye ?: '—' }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="ota2-detail-box">
                                    <div class="ota2-detail-label">OT Assistant</div>
                                    <div class="ota2-detail-value">{{ $booking->otAssistant?->name ?: '—' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <a href="{{ route('hospital.ot.surgery.create', ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                            class="btn btn-primary">
                            <i class="bi bi-heart-pulse me-1"></i> Operate
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endpush