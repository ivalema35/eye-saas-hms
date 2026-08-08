@extends('hospital.layouts.app')
@section('title', 'OT Assistant Dashboard')
@section('page-header', 'OT Assistant Dashboard')

@push('styles')
<style>
.ota2-view-btn {
    border-radius: 999px;
    font-weight: 700;
    font-size: .75rem;
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
<div class="card border-0 shadow-sm ota2-card mb-4">
    <div class="card-header bg-white border-bottom ota2-card-header d-flex justify-content-between align-items-center flex-wrap">
        <div class="ota2-title-wrap">
            <span class="ota2-title-icon">
                <i class="bi bi-activity fs-4"></i>
            </span>
            <div>
                <h5 class="mb-0 fw-bold ota2-title" style="color: var(--color-primary);">
                    Surgery Queue
                </h5>
                <div class="ota2-subtitle">Patients ready for OT — record the surgery.</div>
            </div>
        </div>
        <span class="ota2-total-pill">{{ $readyBookings->total() }} total</span>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive ota2-table-wrap">
            <table class="table table-hover align-middle mb-0 ota2-table">
                <thead class="table-light">
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
                            <td><span class="otd-patient-cell"><i class="bi bi-person-fill"></i>{{ $booking->patient?->full_name ?? '-' }}</span></td>
                            @if(!empty($seeAll))
                                <td>{{ $booking->otDoctor?->name ? 'Dr. '.$booking->otDoctor->name : '-' }}</td>
                            @endif
                            <td><span class="otd-surgery-cell"><i class="bi bi-heart-pulse"></i>{{ $booking->ot_type ?? '-' }}</span></td>
                            <td><span class="otd-amount-pill"><i class="bi bi-cash-coin"></i>{{ money_code((float) ($booking->package_amount ?? 0), 2) }}</span></td>
                            <td>
                                @if($booking->payment_status === 'paid')
                                    <span class="badge bg-success-subtle text-success-emphasis">Paid</span>
                                @elseif($booking->payment_status === 'partially_paid')
                                    <span class="badge bg-warning-subtle text-warning-emphasis">Partially Paid</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger-emphasis">Pending</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge otd-status-badge text-bg-warning">{{ $status }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <button type="button"
                                    class="btn btn-sm btn-outline-secondary ota2-view-btn me-2"
                                    data-bs-toggle="modal"
                                    data-bs-target="#ota2ViewModal{{ $booking->id }}">
                                    <i class="bi bi-eye-fill me-1"></i> View
                                </button>
                                <a href="{{ route('hospital.ot.surgery.create', ['slug' => $slug, 'bookingId' => $booking->id]) }}" class="btn btn-sm btn-primary otd-operate-btn">
                                    <i class="bi bi-heart-pulse me-1"></i> Operate
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ !empty($seeAll) ? 7 : 6 }}" class="text-center text-muted py-4 otd-empty-cell">No bookings ready for surgery.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($readyBookings->hasPages())
    <div class="ota2-pagination mb-4">
        {{ $readyBookings->links() }}
    </div>
@endif

</div>
@endsection

{{-- Outside .hms-main overflow/transform so Bootstrap modal centers and paints fully --}}
@push('modals')
@foreach($readyBookings as $booking)
    @php
        $apptNo = 'OT-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT);
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                <div class="ota2-detail-value">{{ optional($booking->surgery_date)->format('d M Y') ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="ota2-detail-box">
                                <div class="ota2-detail-label">Doctor</div>
                                <div class="ota2-detail-value">
                                    {{ $booking->otDoctor?->name ? 'Dr. '.$booking->otDoctor->name : '—' }}
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
                                <div class="ota2-detail-value">{{ money_code((float) ($booking->package_amount ?? 0), 2) }}</div>
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
                                    <span class="badge {{ $payStatus === 'paid' ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis' }}">
                                        {{ $payStatusLabel }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="ota2-detail-box">
                                <div class="ota2-detail-label">OT Status</div>
                                <div class="ota2-detail-value">
                                    <span class="badge text-bg-warning text-uppercase">{{ strtoupper((string) $booking->ot_status) }}</span>
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
