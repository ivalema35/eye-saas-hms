@extends('hospital.layouts.app')
@section('title', 'OT Accountant Dashboard')
@section('page-header', 'OT Accountant Dashboard')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0 fw-bold" style="color: var(--color-primary);">
            <i class="bi bi-cash-coin me-2"></i> Payment Queue
        </h5>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
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
                            <td>{{ $booking->patient?->full_name ?? '-' }}</td>
                            <td>{{ $booking->patient?->contact_no ?? '-' }}</td>
                            <td>{{ optional($booking->surgery_date)->format('d M Y') }}</td>
                            <td>INR {{ number_format((float) ($booking->package_amount ?? 0), 2) }}</td>
                            <td>
                                <span class="badge {{ $status === 'PAID' ? 'text-bg-success' : 'text-bg-warning' }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if($status === 'BOOKED')
                                    <a href="{{ route('hospital.ot.payments.create', ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                                       class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-circle me-1"></i> Add Payment
                                    </a>
                                @else
                                    <span class="text-muted small">Payment Added</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No bookings in payment queue.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($bookings->hasPages())
    <div class="mt-3 d-flex justify-content-center">
        {{ $bookings->links() }}
    </div>
@endif
@endsection
