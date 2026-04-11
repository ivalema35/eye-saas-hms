@extends('hospital.layouts.app')
@section('title', 'OT Doctor Dashboard')
@section('page-header', 'OT Doctor Dashboard')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0 fw-bold" style="color: var(--color-primary);">
            <i class="bi bi-activity me-2"></i> Surgery Queue
        </h5>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
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
                            <td>{{ $booking->patient?->full_name ?? '-' }}</td>
                            <td>{{ $booking->ot_type ?? '-' }}</td>
                            <td>INR {{ number_format((float) ($booking->package_amount ?? 0), 2) }}</td>
                            <td>
                                <span class="badge {{ in_array($status, ['PAID', 'READY'], true) ? 'text-bg-warning' : 'text-bg-secondary' }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if(in_array($status, ['PAID', 'READY'], true))
                                    <a href="{{ route('hospital.ot.surgery.create', ['slug' => $slug, 'bookingId' => $booking->id]) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-heart-pulse me-1"></i> Operate
                                    </a>
                                @else
                                    <span class="text-muted small">Not Actionable</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No bookings assigned for surgery.</td>
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
