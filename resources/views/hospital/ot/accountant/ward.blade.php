@extends('hospital.layouts.app')
@section('title', 'Ward Management')
@section('page-header', 'Ward Management')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0 fw-bold" style="color: var(--color-primary);">
            <i class="bi bi-hospital me-2"></i> Ward Entry Queue
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
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        <tr>
                            <td>{{ $booking->patient?->full_name ?? '-' }}</td>
                            <td>{{ $booking->patient?->contact_no ?? '-' }}</td>
                            <td>{{ optional($booking->surgery_date)->format('d M Y') }}</td>
                            <td><span class="badge text-bg-secondary">{{ strtoupper((string) $booking->ot_status) }}</span></td>
                            <td class="text-end">
                                @if(in_array($booking->ot_status, [\App\Models\Hospital\OT\OtBooking::STATUS_PAID, \App\Models\Hospital\OT\OtBooking::STATUS_IN_WARD], true))
                                    <form method="POST" action="{{ route('hospital.ot.ward.ready', ['slug' => $slug, 'booking' => $booking->id]) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">Send to OT Doctor</button>
                                    </form>
                                @else
                                    <span class="text-muted small">No action</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No records available for ward workflow.</td>
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
