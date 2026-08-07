@extends('hospital.layouts.app')
@section('title', 'OT Bookings')
@section('page-header', 'OT Bookings')

@push('styles')
<style>
    /*
      OT Bookings Index (Design refresh)
      - Keep Blade/dynamic logic untouched; CSS-only + layout wrappers.
      - Palette follows hospital shell theme.
    */

    .ot-bookings-page {
        --ot-primary: #ebf5fbeb;
        --ot-secondary: #1B4F72;
        --ot-s2-06: rgba(27, 79, 114, 0.06);
        --ot-s2-08: rgba(27, 79, 114, 0.08);
        --ot-s2-12: rgba(27, 79, 114, 0.12);
        --ot-s2-18: rgba(27, 79, 114, 0.18);
        --ot-s2-24: rgba(27, 79, 114, 0.24);

        color: var(--ot-secondary);
        animation: ot-page-in 420ms ease both;
    }

    @keyframes ot-page-in {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .ot-bookings-page .btn,
    .ot-bookings-page .hms-btn {
        border-radius: 12px;
        font-weight: 800;
        transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, border-color 170ms ease, color 170ms ease;
    }

    .ot-bookings-page .btn:hover,
    .ot-bookings-page .hms-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 26px rgba(27, 79, 114, 0.14);
    }

    .ot-premium-card {
        background: rgba(255, 255, 255, 0.84);
        border: 1px solid var(--ot-s2-12) !important;
        border-radius: 22px;
        box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
        overflow: hidden;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        animation: ot-card-rise 520ms cubic-bezier(.2,.9,.2,1) both;
    }

    @keyframes ot-card-rise {
        from { opacity: 0; transform: translateY(10px) scale(0.99); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .ot-card-header {
        background:
            linear-gradient(135deg, rgba(235, 245, 251, 0.92), rgba(255, 255, 255, 0.94)),
            #ffffff;
        border-bottom: 1px solid var(--ot-s2-12);
        padding: 1.15rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .ot-title-wrap {
        display: flex;
        align-items: center;
        gap: .85rem;
        min-width: 0;
    }

    .ot-title-wrap > div {
        min-width: 0;
    }

    .ot-title-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        background: var(--ot-secondary);
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 14px 30px rgba(27, 79, 114, 0.22);
        flex: 0 0 auto;
    }

    .ot-title {
        font-weight: 900;
        letter-spacing: -0.2px;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: var(--ot-secondary);
    }

    .ot-subtitle {
        margin: .15rem 0 0;
        font-weight: 650;
        color: rgba(27, 79, 114, 0.72);
        font-size: .92rem;
    }

    .ot-filter-wrap {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        background: rgba(255, 255, 255, 0.8);
        border: 1px solid var(--ot-s2-12);
        border-radius: 999px;
        padding: .25rem;
        box-shadow: 0 8px 18px rgba(27, 79, 114, 0.08);
    }

    .ot-filter-btn {
        text-decoration: none;
        border: 1px solid transparent;
        border-radius: 999px;
        padding: .38rem .75rem;
        font-weight: 900;
        font-size: .75rem;
        letter-spacing: .04em;
        color: rgba(27, 79, 114, 0.72);
        transition: all 160ms ease;
    }

    .ot-filter-btn:hover {
        color: var(--ot-secondary);
        background: rgba(27, 79, 114, 0.08);
        border-color: rgba(27, 79, 114, 0.14);
    }

    .ot-filter-btn.active {
        background: var(--ot-secondary);
        border-color: var(--ot-secondary);
        color: #fff;
        box-shadow: 0 8px 16px rgba(27, 79, 114, 0.20);
    }

    .ot-table-wrap {
        padding: .9rem !important;
        overflow-x: auto;
    }

    .ot-premium-table {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0 8px;
        min-width: 980px;
    }

    .ot-premium-table thead,
    .ot-premium-table thead th {
        background: var(--ot-secondary) !important;
    }

    .ot-premium-table thead th {
        color: #ffffff !important;
        border-bottom: none !important;
        font-size: .72rem;
        letter-spacing: .08em;
        font-weight: 900;
        text-transform: uppercase;
        padding-top: .9rem;
        padding-bottom: .9rem;
        white-space: nowrap;
    }

    .ot-premium-table thead th:first-child {
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .ot-premium-table thead th:last-child {
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .ot-premium-table thead th.ot-actions-col,
    .ot-premium-table tbody td.ot-actions-cell {
        text-align: center;
    }

    .ot-premium-table tbody td {
        background: rgba(255, 255, 255, 0.88);
        border-top: 1px solid var(--ot-s2-12);
        border-bottom: 1px solid var(--ot-s2-12);
        padding: .95rem .95rem;
        font-weight: 750;
        color: rgba(27, 79, 114, 0.90);
        vertical-align: middle;
        white-space: nowrap;
    }

    .ot-premium-table tbody td:first-child {
        border-left: 1px solid var(--ot-s2-12);
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
        color: rgba(27, 79, 114, 0.82);
        font-weight: 900;
    }

    .ot-premium-table tbody td:last-child {
        border-right: 1px solid var(--ot-s2-12);
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .ot-premium-table tbody tr {
        animation: ot-row-in 460ms cubic-bezier(.2,.9,.2,1) both;
        transform-origin: center;
    }

    @keyframes ot-row-in {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .ot-premium-table tbody tr:hover td {
        background: rgba(235, 245, 251, 0.78);
        border-color: var(--ot-s2-18);
    }

    .ot-premium-table tbody tr:hover {
        transform: translateY(-1px);
    }

    /* Stagger row entrance without JS (caps at 14 for smoothness) */
    .ot-premium-table tbody tr:nth-child(1) { animation-delay: 20ms; }
    .ot-premium-table tbody tr:nth-child(2) { animation-delay: 40ms; }
    .ot-premium-table tbody tr:nth-child(3) { animation-delay: 60ms; }
    .ot-premium-table tbody tr:nth-child(4) { animation-delay: 80ms; }
    .ot-premium-table tbody tr:nth-child(5) { animation-delay: 100ms; }
    .ot-premium-table tbody tr:nth-child(6) { animation-delay: 120ms; }
    .ot-premium-table tbody tr:nth-child(7) { animation-delay: 140ms; }
    .ot-premium-table tbody tr:nth-child(8) { animation-delay: 160ms; }
    .ot-premium-table tbody tr:nth-child(9) { animation-delay: 180ms; }
    .ot-premium-table tbody tr:nth-child(10) { animation-delay: 200ms; }
    .ot-premium-table tbody tr:nth-child(11) { animation-delay: 220ms; }
    .ot-premium-table tbody tr:nth-child(12) { animation-delay: 240ms; }
    .ot-premium-table tbody tr:nth-child(13) { animation-delay: 260ms; }
    .ot-premium-table tbody tr:nth-child(14) { animation-delay: 280ms; }

    .ot-status-badge {
        border-radius: 999px;
        padding: .45rem .70rem;
        font-weight: 900;
        letter-spacing: .02em;
        box-shadow: 0 10px 22px rgba(27, 79, 114, 0.10);
    }

    .ot-view-btn {
        border-radius: 12px;
        border: 1px solid rgba(27, 79, 114, 0.18);
        background: rgba(27, 79, 114, 0.06);
        color: var(--ot-secondary);
        font-weight: 900;
        padding: .45rem .8rem;
        transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, border-color 170ms ease, color 170ms ease;
    }

    .ot-view-btn:hover,
    .ot-view-btn:focus {
        background: rgba(27, 79, 114, 0.12);
        border-color: rgba(27, 79, 114, 0.28);
        color: var(--ot-secondary);
        box-shadow: 0 10px 20px rgba(27, 79, 114, 0.12);
    }

    .ot-booking-modal .modal-content {
        border: 0;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 24px 70px rgba(27, 79, 114, 0.22);
    }

    .ot-booking-modal .modal-header {
        background: linear-gradient(135deg, #1B4F72, #245f86);
        color: #fff;
        border-bottom: 0;
        padding: 1rem 1.15rem;
    }

    .ot-booking-modal .modal-title {
        display: flex;
        align-items: center;
        gap: .7rem;
        font-weight: 900;
        letter-spacing: -.02em;
        color: #ffffff;
    }

    .ot-modal-icon {
        width: 40px;
        height: 40px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.18);
        flex: 0 0 auto;
    }

    .ot-booking-modal .modal-body {
        padding: 1.15rem;
        background: #f7fbfe;
    }

    .ot-modal-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .85rem;
    }

    .ot-modal-field {
        background: #fff;
        border: 1px solid rgba(27, 79, 114, 0.10);
        border-radius: 16px;
        padding: .9rem .95rem;
        box-shadow: 0 8px 20px rgba(27, 79, 114, 0.06);
    }

    .ot-modal-field-label {
        font-size: .72rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: rgba(27, 79, 114, 0.62);
        margin-bottom: .35rem;
    }

    .ot-modal-field-value {
        font-weight: 800;
        color: var(--ot-secondary);
        word-break: break-word;
    }

    .ot-modal-wide {
        grid-column: 1 / -1;
    }

    .ot-empty {
        padding: 2.25rem 1rem !important;
        color: rgba(27, 79, 114, 0.72) !important;
        font-weight: 800;
    }

    @media (prefers-reduced-motion: reduce) {
        .ot-bookings-page,
        .ot-premium-card,
        .ot-premium-table tbody tr,
        .ot-bookings-page .btn,
        .ot-bookings-page .hms-btn {
            animation: none !important;
            transition: none !important;
        }
    }
</style>
@endpush

@section('page-actions')
    <a href="{{ route('hospital.ot.bookings.create', ['slug' => $slug]) }}" class="hms-btn hms-btn-primary">
        <i class="bi bi-plus-circle"></i> New Booking
    </a>
@endsection

@section('content')
<div class="ot-bookings-page">
    <div class="card ot-premium-card border-0">
        <div class="ot-card-header">
            <div class="ot-title-wrap">
                <span class="ot-title-icon" aria-hidden="true">
                    <i class="bi bi-calendar2-check" style="font-size: 1.2rem;"></i>
                </span>
                <div class="flex-grow-1">
                    <h5 class="ot-title">OT Bookings</h5>
                    <div class="ot-subtitle">View scheduled surgeries and their current status</div>
                </div>
            </div>
            <div class="d-none d-md-flex align-items-center gap-2">
                <div class="ot-filter-wrap" role="group" aria-label="OT booking filter">
                    <a href="{{ route('hospital.ot.bookings.index', ['slug' => $slug, 'filter' => 'all']) }}"
                       class="ot-filter-btn {{ ($activeFilter ?? 'all') === 'all' ? 'active' : '' }}">All</a>
                    <a href="{{ route('hospital.ot.bookings.index', ['slug' => $slug, 'filter' => 'today']) }}"
                       class="ot-filter-btn {{ ($activeFilter ?? 'all') === 'today' ? 'active' : '' }}">Today</a>
                </div>
                <span class="badge" style="background: rgba(255,255,255,.78); color: var(--ot-secondary); border: 1px solid var(--ot-s2-12); border-radius: 999px; padding: .55rem .85rem; font-weight: 900; box-shadow: 0 10px 22px rgba(27,79,114,.08);">
                    <i class="bi bi-info-circle me-1"></i> List
                </span>
            </div>
        </div>

        <div class="ot-table-wrap">
            <div class="table-responsive">
                <table class="table ot-premium-table align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><i class="bi bi-person-badge me-1"></i> Patient</th>
                            <th><i class="bi bi-person-vcard me-1"></i> OT Doctor</th>
                            <th><i class="bi bi-calendar-event me-1"></i> Surgery Date</th>
                            <th><i class="bi bi-eye me-1"></i> Eye</th>
                            <th><i class="bi bi-activity me-1"></i> Type</th>
                            <th><i class="bi bi-flag me-1"></i> Status</th>
                            <th class="ot-actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            <tr>
                                <td>{{ $booking->id }}</td>
                                <td>{{ $booking->patient?->full_name ?? '-' }}</td>
                                <td>{{ $booking->otDoctor?->name ?? '-' }}</td>
                                <td>{{ optional($booking->surgery_date)->format('d M Y') }}</td>
                                <td>{{ $booking->eye }}</td>
                                <td>{{ $booking->ot_type }}</td>
                                <td><span class="badge text-bg-secondary ot-status-badge">{{ str($booking->ot_status)->replace('_', ' ')->title() }}</span></td>
                                <td class="ot-actions-cell">
                                    <button type="button" class="btn btn-sm ot-view-btn" data-bs-toggle="modal" data-bs-target="#otBookingViewModal{{ $booking->id }}">
                                        <i class="bi bi-eye-fill me-1"></i> View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center ot-empty">
                                    <i class="bi bi-inbox me-1"></i> No OT bookings found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@foreach($bookings as $booking)
    <div class="modal fade ot-booking-modal" id="otBookingViewModal{{ $booking->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mb-0">
                        <span class="ot-modal-icon" aria-hidden="true">
                            <i class="bi bi-calendar2-check"></i>
                        </span>
                        OT Booking Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="ot-modal-grid">
                        <div class="ot-modal-field">
                            <div class="ot-modal-field-label"><i class="bi bi-hash me-1"></i> Booking ID</div>
                            <div class="ot-modal-field-value">#{{ $booking->id }}</div>
                        </div>
                        <div class="ot-modal-field">
                            <div class="ot-modal-field-label"><i class="bi bi-flag me-1"></i> Status</div>
                            <div class="ot-modal-field-value">{{ str($booking->ot_status)->replace('_', ' ')->title() }}</div>
                        </div>
                        <div class="ot-modal-field">
                            <div class="ot-modal-field-label"><i class="bi bi-person-badge me-1"></i> Patient</div>
                            <div class="ot-modal-field-value">{{ $booking->patient?->full_name ?? '-' }}</div>
                        </div>
                        <div class="ot-modal-field">
                            <div class="ot-modal-field-label"><i class="bi bi-upc-scan me-1"></i> Patient Code</div>
                            <div class="ot-modal-field-value">{{ $booking->patient?->patient_code ?? '-' }}</div>
                        </div>
                        <div class="ot-modal-field">
                            <div class="ot-modal-field-label"><i class="bi bi-telephone me-1"></i> Contact</div>
                            <div class="ot-modal-field-value">{{ $booking->patient?->contact_no ?? '-' }}</div>
                        </div>
                        <div class="ot-modal-field">
                            <div class="ot-modal-field-label"><i class="bi bi-person-vcard me-1"></i> OT Doctor</div>
                            <div class="ot-modal-field-value">{{ $booking->otDoctor?->name ?? '-' }}</div>
                        </div>
                        <div class="ot-modal-field">
                            <div class="ot-modal-field-label"><i class="bi bi-person-check me-1"></i> Booked By</div>
                            <div class="ot-modal-field-value">{{ $booking->bookedBy?->name ?? '-' }}</div>
                        </div>
                        <div class="ot-modal-field">
                            <div class="ot-modal-field-label"><i class="bi bi-calendar-event me-1"></i> Surgery Date</div>
                            <div class="ot-modal-field-value">{{ optional($booking->surgery_date)->format('d M Y') ?? '-' }}</div>
                        </div>
                        <div class="ot-modal-field">
                            <div class="ot-modal-field-label"><i class="bi bi-eye me-1"></i> Eye</div>
                            <div class="ot-modal-field-value">{{ $booking->eye ?? '-' }}</div>
                        </div>
                        <div class="ot-modal-field">
                            <div class="ot-modal-field-label"><i class="bi bi-activity me-1"></i> OT Type</div>
                            <div class="ot-modal-field-value">{{ $booking->ot_type ?? '-' }}</div>
                        </div>
                        <div class="ot-modal-field ot-modal-wide">
                            <div class="ot-modal-field-label"><i class="bi bi-person-circle me-1"></i> Assigned Summary</div>
                            <div class="ot-modal-field-value">
                                Patient {{ $booking->patient?->full_name ?? '-' }} is assigned to {{ $booking->otDoctor?->name ?? '-' }}.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach

@if($bookings->hasPages())
    <div class="mt-3 d-flex justify-content-center">
        {{ $bookings->links() }}
    </div>
@endif
@endsection
