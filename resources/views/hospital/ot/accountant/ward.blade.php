@extends('hospital.layouts.app')
@section('title', 'Ward Management')
@section('page-header', 'Ward Management')

@push('styles')
<style>
.ot-ward-page {
    --ward-primary: #ebf5fbeb;
    --ward-secondary: #1B4F72;
    --ward-secondary-08: rgba(27, 79, 114, .08);
    --ward-secondary-12: rgba(27, 79, 114, .12);
    --ward-secondary-18: rgba(27, 79, 114, .18);
    --ward-secondary-24: rgba(27, 79, 114, .24);
    color: var(--ward-secondary);
    animation: ward-page-in 420ms ease both;
}

.ward-card {
    border: 1px solid var(--ward-secondary-12) !important;
    border-radius: 22px;
    background: rgba(255, 255, 255, .86);
    box-shadow: 0 18px 48px rgba(27, 79, 114, .10) !important;
    overflow: hidden;
    animation: ward-card-rise 520ms cubic-bezier(.2,.9,.2,1) both;
}

.ward-card-header {
    background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94)) !important;
    border-bottom: 1px solid var(--ward-secondary-12) !important;
    padding: 1.15rem 1.25rem;
}

.ward-title-wrap {
    display: flex;
    align-items: center;
    gap: .85rem;
}

.ward-title-icon {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    background: var(--ward-secondary);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 14px 30px rgba(27, 79, 114, .22);
}

.ward-title {
    color: var(--ward-secondary) !important;
    font-weight: 900;
    letter-spacing: -.2px;
}

.ward-subtitle {
    color: rgba(27, 79, 114, .68);
    font-size: .84rem;
    font-weight: 650;
    margin-top: .15rem;
}

.ward-total-pill {
    background: rgba(255, 255, 255, .78);
    border: 1px solid var(--ward-secondary-12);
    color: var(--ward-secondary);
    border-radius: 999px;
    padding: .52rem .85rem;
    font-weight: 900;
    box-shadow: 0 10px 22px rgba(27, 79, 114, .08);
}

.ward-table-wrap {
    padding: .9rem;
    overflow-x: auto;
}

.ward-table {
    border-collapse: separate;
    border-spacing: 0 8px;
    min-width: 760px;
}

.ward-table thead tr,
.ward-table thead th {
    background: var(--ward-secondary) !important;
}

.ward-table thead th {
    color: #fff !important;
    border: 0 !important;
    padding: .9rem 1rem;
    font-size: .72rem;
    letter-spacing: .08em;
    font-weight: 900;
    text-transform: uppercase;
    white-space: nowrap;
}

.ward-table thead th:first-child {
    border-top-left-radius: 14px;
    border-bottom-left-radius: 14px;
}

.ward-table thead th:last-child {
    border-top-right-radius: 14px;
    border-bottom-right-radius: 14px;
}

.ward-table tbody tr {
    animation: ward-row-in 460ms ease both;
    transition: transform 170ms ease, box-shadow 170ms ease;
}

.ward-table tbody tr:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 34px rgba(27, 79, 114, .10);
}

.ward-table tbody td {
    background: rgba(255, 255, 255, .92);
    border-top: 1px solid var(--ward-secondary-08);
    border-bottom: 1px solid var(--ward-secondary-08);
    color: var(--ward-secondary);
    padding: .9rem 1rem;
    vertical-align: middle;
    font-weight: 650;
}

.ward-table tbody td:first-child {
    border-left: 1px solid var(--ward-secondary-08);
    border-top-left-radius: 14px;
    border-bottom-left-radius: 14px;
}

.ward-table tbody td:last-child {
    border-right: 1px solid var(--ward-secondary-08);
    border-top-right-radius: 14px;
    border-bottom-right-radius: 14px;
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
    color: var(--ward-secondary);
}

.ward-status-badge {
    background: var(--ward-primary) !important;
    border: 1px solid var(--ward-secondary-18);
    color: var(--ward-secondary) !important;
    border-radius: 999px !important;
    padding: .38rem .78rem;
    font-size: .75rem;
    font-weight: 900;
    letter-spacing: .04em;
}

.ward-send-btn {
    background: var(--ward-secondary) !important;
    border-color: var(--ward-secondary) !important;
    color: #fff !important;
    border-radius: 999px;
    font-weight: 900;
    padding: .38rem .85rem;
    box-shadow: 0 10px 22px rgba(27, 79, 114, .14);
    transition: transform 170ms ease, box-shadow 170ms ease;
}

.ward-send-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 28px rgba(27, 79, 114, .22);
}

.ward-no-action {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    background: var(--ward-primary);
    border: 1px solid var(--ward-secondary-12);
    color: var(--ward-secondary) !important;
    border-radius: 999px;
    padding: .38rem .72rem;
    font-weight: 800;
}

.ward-empty-cell {
    padding: 3rem 1rem !important;
    background: var(--ward-primary) !important;
    border: 1px dashed var(--ward-secondary-18) !important;
    border-radius: 16px !important;
    color: var(--ward-secondary) !important;
    font-weight: 800;
}

.ward-pagination {
    margin-top: 1rem;
    display: flex;
    justify-content: center;
    animation: ward-page-in 420ms ease both;
}

@keyframes ward-page-in {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes ward-card-rise {
    from { opacity: 0; transform: translateY(12px) scale(.99); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes ward-row-in {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
    .ot-ward-page,
    .ward-card,
    .ward-table tbody tr,
    .ward-pagination {
        animation: none;
    }

    .ot-ward-page * {
        transition: none !important;
    }
}

@media (max-width: 576px) {
    .ward-card-header {
        align-items: flex-start;
        gap: 1rem;
    }

    .ward-title-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
    }
}
</style>
@endpush

@section('content')
<div class="ot-ward-page">
<div class="card border-0 shadow-sm ward-card">
    <div class="card-header bg-white border-bottom ward-card-header d-flex justify-content-between align-items-center flex-wrap">
        <div class="ward-title-wrap">
            <span class="ward-title-icon">
                <i class="bi bi-hospital fs-4"></i>
            </span>
            <div>
                <h5 class="mb-0 fw-bold ward-title" style="color: var(--color-primary);">
                    Ward Entry Queue
                </h5>
               
            </div>
        </div>
        <span class="ward-total-pill">{{ $bookings->total() }} total</span>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive ward-table-wrap">
            <table class="table table-hover align-middle mb-0 ward-table">
                <thead class="table-light">
                    <tr>
                        <th>Patient Name</th>
                        <th>Phone</th>
                        <th>OT Date</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        <tr>
                            <td><span class="ward-patient-cell"><i class="bi bi-person-fill"></i>{{ $booking->patient?->full_name ?? '-' }}</span></td>
                            <td><span class="ward-phone-cell"><i class="bi bi-telephone-fill"></i>{{ $booking->patient?->contact_no ?? '-' }}</span></td>
                            <td><span class="ward-date-cell"><i class="bi bi-calendar2-event"></i>{{ optional($booking->surgery_date)->format('d M Y') }}</span></td>
                            <td><span class="badge text-bg-secondary ward-status-badge">{{ strtoupper((string) $booking->ot_status) }}</span></td>
                            <td>
                                @if($booking->payment_status === 'paid')
                                    <span class="badge bg-success-subtle text-success-emphasis">Paid</span>
                                @elseif($booking->payment_status === 'partially_paid')
                                    <span class="badge bg-warning-subtle text-warning-emphasis">Partially Paid</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger-emphasis">Pending</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('hospital.ot.ward.show', ['slug' => $slug, 'booking' => $booking->id]) }}"
                                   class="btn btn-sm btn-outline-secondary ward-send-btn me-2" style="background:transparent !important;color:var(--ward-secondary) !important;border-color:var(--ward-secondary-18) !important;box-shadow:none;">
                                    <i class="bi bi-heart-pulse me-1"></i> Vitals &amp; Eye Drops
                                </a>
                                @if(in_array($booking->ot_status, [\App\Models\Hospital\OT\OtBooking::STATUS_PAYMENT_VERIFIED, \App\Models\Hospital\OT\OtBooking::STATUS_IN_WARD], true))
                                    <form method="POST" action="{{ route('hospital.ot.ward.ready', ['slug' => $slug, 'booking' => $booking->id]) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary ward-send-btn">
                                            <i class="bi bi-send-check me-1"></i> Send to OT Assistant
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small ward-no-action"><i class="bi bi-dash-circle"></i> No action</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4 ward-empty-cell">No records available for ward workflow.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($bookings->hasPages())
    <div class="ward-pagination">
        {{ $bookings->links() }}
    </div>
@endif
</div>
@endsection
