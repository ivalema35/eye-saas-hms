@extends('hospital.layouts.app')
@section('title', 'OT Assistant Dashboard')
@section('page-header', 'OT Assistant Dashboard')

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
                            <td class="text-end">
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
