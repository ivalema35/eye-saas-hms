@extends('hospital.layouts.app')
@section('title', 'OT Doctor Dashboard')
@section('page-header', 'OT Doctor Dashboard')

@section('content')
<div class="ot-doctor-page">
<div class="card border-0 shadow-sm otd-card">
    <div class="card-header bg-white border-bottom otd-card-header d-flex justify-content-between align-items-center flex-wrap">
        <div class="otd-title-wrap">
            <span class="otd-title-icon">
                <i class="bi bi-activity fs-4"></i>
            </span>
            <div>
                <h5 class="mb-0 fw-bold otd-title" style="color: var(--color-primary);">
                    Surgery Queue
                </h5>
                
            </div>
        </div>
        <span class="otd-total-pill">{{ $bookings->total() }} total</span>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive otd-table-wrap">
            <table class="table table-hover align-middle mb-0 otd-table">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Surgery Type</th>
                        <th>Package</th>
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
                            <td><span class="otd-patient-cell"><i class="bi bi-person-fill"></i>{{ $booking->patient?->full_name ?? '-' }}</span></td>
                            <td><span class="otd-surgery-cell"><i class="bi bi-heart-pulse"></i>{{ $booking->ot_type ?? '-' }}</span></td>
                            <td><span class="otd-amount-pill"><i class="bi bi-currency-rupee"></i>INR {{ number_format((float) ($booking->package_amount ?? 0), 2) }}</span></td>
                            <td>
                                <span class="badge otd-status-badge {{ in_array($status, ['PAID', 'READY'], true) ? 'text-bg-warning' : 'text-bg-secondary' }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if(in_array($status, ['PAID', 'READY'], true))
                                    <a href="{{ route('hospital.ot.surgery.create', ['slug' => $slug, 'bookingId' => $booking->id]) }}" class="btn btn-sm btn-primary otd-operate-btn">
                                        <i class="bi bi-heart-pulse me-1"></i> Operate
                                    </a>
                                @else
                                    <span class="text-muted small otd-not-actionable"><i class="bi bi-lock"></i> Not Actionable</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4 otd-empty-cell">No bookings assigned for surgery.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($bookings->hasPages())
    <div class="otd-pagination">
        {{ $bookings->links() }}
    </div>
@endif
</div>
@endsection
