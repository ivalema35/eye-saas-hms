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
    to { opacity: 1; transform: translateY(0); }
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
        <span class="ota-total-pill">{{ $bookings->total() }} total</span>
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
                        @endphp
                        <tr>
                            <td><span class="ota-patient-cell"><i class="bi bi-person-fill"></i>{{ $booking->patient?->full_name ?? '-' }}</span></td>
                            <td><span class="ota-phone-cell"><i class="bi bi-telephone-fill"></i>{{ $booking->patient?->contact_no ?? '-' }}</span></td>
                            <td><span class="ota-date-cell"><i class="bi bi-calendar2-event"></i>{{ optional($booking->surgery_date)->format('d M Y') }}</span></td>
                            <td><span class="ota-amount-pill"><i class="bi bi-currency-rupee"></i>INR {{ number_format((float) ($booking->package_amount ?? 0), 2) }}</span></td>
                            <td>
                                <span class="badge ota-status-badge {{ $status === 'PAID' ? 'text-bg-success' : 'text-bg-warning' }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if($status === 'BOOKED')
                                    <a href="{{ route('hospital.ot.payments.create', ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                                       class="btn btn-sm btn-primary ota-add-payment-btn">
                                        <i class="bi bi-plus-circle me-1"></i> Add Payment
                                    </a>
                                @else
                                    <span class="text-muted small ota-paid-note"><i class="bi bi-check2-circle"></i> Payment Added</span>
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
@endsection
