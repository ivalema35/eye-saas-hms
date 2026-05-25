@extends('hospital.layouts.app')
@section('title', 'OT Accountant Dashboard')
@section('page-header', 'OT Accountant Dashboard')

@push('styles')
<style>
.ot-accountant-page {
    --ota-primary: #ebf5fbeb;
    --ota-secondary: #1B4F72;
    --ota-secondary-06: rgba(27, 79, 114, .06);
    --ota-secondary-08: rgba(27, 79, 114, .08);
    --ota-secondary-12: rgba(27, 79, 114, .12);
    --ota-secondary-18: rgba(27, 79, 114, .18);
    --ota-secondary-24: rgba(27, 79, 114, .24);
    color: var(--ota-secondary);
    animation: ota-page-in 420ms ease both;
}

.ota-card {
    border: 1px solid var(--ota-secondary-12) !important;
    border-radius: 22px;
    background: rgba(255, 255, 255, .86);
    box-shadow: 0 18px 48px rgba(27, 79, 114, .10) !important;
    overflow: hidden;
    animation: ota-card-rise 520ms cubic-bezier(.2,.9,.2,1) both;
}

.ota-card-header {
    background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94)) !important;
    border-bottom: 1px solid var(--ota-secondary-12) !important;
    padding: 1.15rem 1.25rem;
}

.ota-title-wrap {
    display: flex;
    align-items: center;
    gap: .85rem;
}

.ota-title-icon {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    background: var(--ota-secondary);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 14px 30px rgba(27, 79, 114, .22);
}

.ota-title {
    color: var(--ota-secondary) !important;
    font-weight: 900;
    letter-spacing: -.2px;
}

.ota-subtitle {
    color: rgba(27, 79, 114, .68);
    font-size: .84rem;
    font-weight: 650;
    margin-top: .15rem;
}

.ota-total-pill {
    background: rgba(255, 255, 255, .78);
    border: 1px solid var(--ota-secondary-12);
    color: var(--ota-secondary);
    border-radius: 999px;
    padding: .52rem .85rem;
    font-weight: 900;
    box-shadow: 0 10px 22px rgba(27, 79, 114, .08);
}

.ota-head-actions {
    display: inline-flex;
    align-items: center;
    gap: .6rem;
}

.ota-filter-wrap {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    background: rgba(255, 255, 255, .8);
    border: 1px solid var(--ota-secondary-12);
    border-radius: 999px;
    padding: .25rem;
    box-shadow: 0 8px 18px rgba(27, 79, 114, .08);
}

.ota-filter-btn {
    text-decoration: none;
    border: 1px solid transparent;
    border-radius: 999px;
    padding: .38rem .75rem;
    font-weight: 900;
    font-size: .75rem;
    letter-spacing: .04em;
    color: rgba(27, 79, 114, .72);
    transition: all 160ms ease;
}

.ota-filter-btn:hover {
    color: var(--ota-secondary);
    background: rgba(27, 79, 114, .08);
    border-color: rgba(27, 79, 114, .14);
}

.ota-filter-btn.active {
    background: var(--ota-secondary);
    border-color: var(--ota-secondary);
    color: #fff;
    box-shadow: 0 8px 16px rgba(27, 79, 114, .20);
}

.ota-table-wrap {
    padding: .9rem;
    overflow-x: auto;
}

.ota-table {
    border-collapse: separate;
    border-spacing: 0 8px;
    min-width: 860px;
}

.ota-table thead tr,
.ota-table thead th {
    background: var(--ota-secondary) !important;
}

.ota-table thead th {
    color: #fff !important;
    border: 0 !important;
    padding: .9rem 1rem;
    font-size: .72rem;
    letter-spacing: .08em;
    font-weight: 900;
    text-transform: uppercase;
    white-space: nowrap;
}

.ota-table thead th:first-child {
    border-top-left-radius: 14px;
    border-bottom-left-radius: 14px;
}

.ota-table thead th:last-child {
    border-top-right-radius: 14px;
    border-bottom-right-radius: 14px;
}

.ota-table tbody tr {
    animation: ota-row-in 460ms ease both;
    transition: transform 170ms ease, box-shadow 170ms ease;
}

.ota-table tbody tr:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 34px rgba(27, 79, 114, .10);
}

.ota-table tbody td {
    background: rgba(255, 255, 255, .92);
    border-top: 1px solid var(--ota-secondary-08);
    border-bottom: 1px solid var(--ota-secondary-08);
    color: var(--ota-secondary);
    padding: .9rem 1rem;
    vertical-align: middle;
    font-weight: 650;
}

.ota-table tbody td:first-child {
    border-left: 1px solid var(--ota-secondary-08);
    border-top-left-radius: 14px;
    border-bottom-left-radius: 14px;
}

.ota-table tbody td:last-child {
    border-right: 1px solid var(--ota-secondary-08);
    border-top-right-radius: 14px;
    border-bottom-right-radius: 14px;
}

.ota-patient-cell,
.ota-phone-cell,
.ota-date-cell {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
}

.ota-patient-cell i,
.ota-phone-cell i,
.ota-date-cell i {
    color: var(--ota-secondary);
}

.ota-amount-pill {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    background: var(--ota-primary);
    border: 1px solid var(--ota-secondary-12);
    border-radius: 999px;
    padding: .36rem .72rem;
    font-weight: 900;
}

.ota-status-badge {
    border-radius: 999px !important;
    padding: .38rem .78rem;
    font-size: .75rem;
    font-weight: 900;
    letter-spacing: .04em;
    border: 1px solid var(--ota-secondary-18);
}

.ota-status-badge.text-bg-success {
    background: rgba(39, 174, 96, .14) !important;
    color: #1A6F5B !important;
}

.ota-status-badge.text-bg-warning {
    background: rgba(230, 126, 34, .14) !important;
    color: #784212 !important;
}

.ota-view-btn {
    background: rgba(27, 79, 114, .06) !important;
    border-color: rgba(27, 79, 114, .18) !important;
    color: var(--ota-secondary) !important;
    border-radius: 999px;
    font-weight: 900;
    padding: .38rem .85rem;
    box-shadow: 0 10px 22px rgba(27, 79, 114, .10);
    transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, border-color 170ms ease;
}

.ota-view-btn:hover,
.ota-view-btn:focus {
    background: rgba(27, 79, 114, .12) !important;
    border-color: rgba(27, 79, 114, .28) !important;
    color: var(--ota-secondary) !important;
    transform: translateY(-1px);
    box-shadow: 0 14px 28px rgba(27, 79, 114, .16);
}

.ota-detail-modal .modal-content {
    border: 0;
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 24px 70px rgba(27, 79, 114, .22);
    background: #ffffff;
}

.ota-detail-modal .modal-header {
    background: linear-gradient(135deg, #174562, #1b4f72);
    color: #fff;
    border-bottom: 0;
    padding: 1rem 1.15rem;
}

.ota-detail-modal .modal-title {
    display: flex;
    align-items: center;
    gap: .7rem;
    font-weight: 900;
    color: #ffffff !important;
}

.ota-modal-icon {
    width: 40px;
    height: 40px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, .15);
    border: 1px solid rgba(255, 255, 255, .18);
    flex: 0 0 auto;
}

.ota-detail-modal .modal-body {
    background: #f7fbfe;
    padding: 1.15rem;
}

.ota-detail-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 1rem;
}

.ota-modal-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .85rem;
}

.ota-modal-field {
    background: #fff;
    border: 1px solid rgba(27, 79, 114, .10);
    border-radius: 16px;
    padding: .9rem .95rem;
    box-shadow: 0 8px 20px rgba(27, 79, 114, .06);
}

.ota-modal-field-label {
    font-size: .72rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: rgba(27, 79, 114, .62);
    margin-bottom: .35rem;
}

.ota-modal-field-value {
    font-weight: 800;
    color: var(--ota-secondary);
    word-break: break-word;
}

.ota-modal-wide {
    grid-column: 1 / -1;
}

.ota-detail-panel {
    background: #fff;
    border: 1px solid rgba(27, 79, 114, .10);
    border-radius: 18px;
    box-shadow: 0 10px 22px rgba(27, 79, 114, .06);
    overflow: hidden;
}

.ota-detail-panel-header {
    padding: .95rem 1rem .75rem;
    border-bottom: 1px solid rgba(27, 79, 114, .08);
    background: linear-gradient(135deg, rgba(235, 245, 251, .95), rgba(255, 255, 255, .96));
}

.ota-detail-panel-title {
    margin: 0;
    color: var(--ota-secondary);
    font-weight: 900;
    font-size: .96rem;
}

.ota-detail-panel-body {
    padding: 1rem;
}

.ota-summary-box {
    background: #f8fbfe;
    border: 1px solid rgba(27, 79, 114, .08);
    border-radius: 14px;
    padding: .85rem .95rem;
    margin-bottom: .75rem;
}

.ota-summary-label {
    font-size: .75rem;
    color: rgba(27, 79, 114, .7);
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: .2rem;
}

.ota-summary-value {
    color: #173d57;
    font-weight: 700;
}

.ota-mini-note {
    background: var(--ota-primary);
    border: 1px solid var(--ota-secondary-12);
    border-radius: 14px;
    padding: .85rem .95rem;
    color: var(--ota-secondary);
    font-weight: 800;
}

.ota-add-payment-btn {
    background: var(--ota-secondary) !important;
    border-color: var(--ota-secondary) !important;
    color: #fff !important;
    border-radius: 999px;
    font-weight: 900;
    padding: .38rem .85rem;
    box-shadow: 0 10px 22px rgba(27, 79, 114, .14);
    transition: transform 170ms ease, box-shadow 170ms ease;
}

.ota-add-payment-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 28px rgba(27, 79, 114, .22);
}

.ota-paid-note {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    background: var(--ota-primary);
    border: 1px solid var(--ota-secondary-12);
    color: var(--ota-secondary) !important;
    border-radius: 999px;
    padding: .38rem .72rem;
    font-weight: 800;
}

.ota-empty-cell {
    padding: 3rem 1rem !important;
    background: var(--ota-primary) !important;
    border: 1px dashed var(--ota-secondary-18) !important;
    border-radius: 16px !important;
    color: var(--ota-secondary) !important;
    font-weight: 800;
}

.ota-pagination {
    margin-top: 1rem;
    display: flex;
    justify-content: center;
    animation: ota-page-in 420ms ease both;
}

@keyframes ota-page-in {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: none; }
}

@keyframes ota-card-rise {
    from { opacity: 0; transform: translateY(12px) scale(.99); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes ota-row-in {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
    .ot-accountant-page,
    .ota-card,
    .ota-table tbody tr,
    .ota-pagination {
        animation: none;
    }

    .ot-accountant-page * {
        transition: none !important;
    }
}

@media (max-width: 576px) {
    .ota-card-header {
        align-items: flex-start;
        gap: 1rem;
    }

    .ota-title-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
    }
}
</style>
@endpush

@section('content')
<div class="ot-accountant-page">
<div class="card border-0 shadow-sm ota-card">
    <div class="card-header bg-white border-bottom ota-card-header d-flex justify-content-between align-items-center flex-wrap">
        <div class="ota-title-wrap">
            <span class="ota-title-icon">
                <i class="bi bi-cash-coin fs-4"></i>
            </span>
            <div>
                <h5 class="mb-0 fw-bold ota-title" style="color: var(--color-primary);">
                    Payment Queue
                </h5>
                
            </div>
        </div>
        <div class="ota-head-actions">
            <div class="ota-filter-wrap" role="group" aria-label="OT accountant filter">
                <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug, 'filter' => 'all']) }}"
                   class="ota-filter-btn {{ ($activeFilter ?? 'all') === 'all' ? 'active' : '' }}">All</a>
                <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug, 'filter' => 'today']) }}"
                   class="ota-filter-btn {{ ($activeFilter ?? 'all') === 'today' ? 'active' : '' }}">Today</a>
            </div>
            <span class="ota-total-pill">{{ $bookings->total() }} total</span>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive ota-table-wrap">
            <table class="table table-hover align-middle mb-0 ota-table">
                <thead class="table-light">
                    <tr>
                        <th>Patient Name</th>
                        <th>Phone</th>
                        <th>OT Date</th>
                        <th>Package Amount</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        @php
                            $status = strtoupper((string) $booking->ot_status);
                            $statusLabel = $status === 'PAID' ? 'Payment Completed' : 'Payment Pending';
                        @endphp
                        <tr>
                            <td><span class="ota-patient-cell"><i class="bi bi-person-fill"></i>{{ $booking->patient?->full_name ?? '-' }}</span></td>
                            <td><span class="ota-phone-cell"><i class="bi bi-telephone-fill"></i>{{ $booking->patient?->contact_no ?? '-' }}</span></td>
                            <td><span class="ota-date-cell"><i class="bi bi-calendar2-event"></i>{{ optional($booking->surgery_date)->format('d M Y') }}</span></td>
                            <td><span class="ota-amount-pill"><i class="bi bi-currency-rupee"></i>INR {{ number_format((float) ($booking->package_amount ?? 0), 2) }}</span></td>
                            <td>
                                <span class="badge ota-status-badge {{ $status === 'PAID' ? 'text-bg-success' : 'text-bg-warning' }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary ota-view-btn me-2" data-bs-toggle="modal" data-bs-target="#otaDetailModal{{ $booking->id }}">
                                    <i class="bi bi-eye-fill me-1"></i> View
                                </button>
                                @if($status === 'BOOKED')
                                    <a href="{{ route('hospital.ot.payments.create', ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                                       class="btn btn-sm btn-primary ota-add-payment-btn">
                                        <i class="bi bi-plus-circle me-1"></i> Add Payment
                                    </a>
                                @else
                                    <span class="text-muted small ota-paid-note"><i class="bi bi-check2-circle"></i> Payment Completed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4 ota-empty-cell">No bookings in payment queue.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($bookings->hasPages())
    <div class="ota-pagination">
        {{ $bookings->links() }}
    </div>
@endif
</div>

@foreach($bookings as $booking)
    @php
        $status = strtoupper((string) $booking->ot_status);
        $statusLabel = $status === 'PAID' ? 'Payment Completed' : 'Payment Pending';
    @endphp
    <div class="modal fade ota-detail-modal" id="otaDetailModal{{ $booking->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mb-0">
                        <span class="ota-modal-icon" aria-hidden="true">
                            <i class="bi bi-receipt"></i>
                        </span>
                        Record OT Payment
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="ota-detail-grid">
                        <div class="ota-detail-panel">
                            <div class="ota-detail-panel-header">
                                <p class="ota-detail-panel-title"><i class="bi bi-person-lines-fill me-1"></i> Patient Details</p>
                            </div>
                            <div class="ota-detail-panel-body">
                                <div class="ota-summary-box">
                                    <div class="ota-summary-label">Patient Name</div>
                                    <div class="ota-summary-value">{{ $booking->patient?->full_name ?? '-' }}</div>
                                </div>
                                <div class="ota-summary-box">
                                    <div class="ota-summary-label">Contact</div>
                                    <div class="ota-summary-value">{{ $booking->patient?->contact_no ?? '-' }}</div>
                                </div>
                                <div class="ota-summary-box">
                                    <div class="ota-summary-label">Patient Code</div>
                                    <div class="ota-summary-value">{{ $booking->patient?->patient_code ?? '-' }}</div>
                                </div>
                                <div class="ota-summary-box mb-0">
                                    <div class="ota-summary-label">Location</div>
                                    <div class="ota-summary-value">{{ $booking->patient?->location?->name ?? '-' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="ota-detail-panel">
                            <div class="ota-detail-panel-header">
                                <p class="ota-detail-panel-title"><i class="bi bi-receipt me-1"></i> OT Payment Details</p>
                            </div>
                            <div class="ota-detail-panel-body">
                                <div class="ota-summary-box">
                                    <div class="ota-summary-label">OT Date</div>
                                    <div class="ota-summary-value">{{ optional($booking->surgery_date)->format('d M Y') ?? '-' }}</div>
                                </div>
                                <div class="ota-summary-box">
                                    <div class="ota-summary-label">Package Amount</div>
                                    <div class="ota-summary-value">INR {{ number_format((float) ($booking->package_amount ?? 0), 2) }}</div>
                                </div>
                                <div class="ota-summary-box">
                                    <div class="ota-summary-label">Payment Status</div>
                                    <div class="ota-summary-value">{{ $statusLabel }}</div>
                                </div>
                                <div class="ota-summary-box mb-0">
                                    <div class="ota-summary-label">Booking ID</div>
                                    <div class="ota-summary-value">#{{ $booking->id }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="ota-modal-field ota-modal-wide">
                            <div class="ota-modal-field-label"><i class="bi bi-info-circle me-1"></i> Summary</div>
                            <div class="ota-modal-field-value">
                                {{ $booking->patient?->full_name ?? 'This patient' }} is scheduled for OT on {{ optional($booking->surgery_date)->format('d M Y') ?? '-' }}.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach

@endsection
