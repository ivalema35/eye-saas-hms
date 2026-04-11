@extends('hospital.layouts.app')
@section('title', 'OT Assistant Dashboard')
@section('page-header', 'OT Assistant Dashboard')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0 fw-bold" style="color: var(--color-primary);">
            <i class="bi bi-eye me-2"></i> Lens Workflow Queue
        </h5>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Patient</th>
                        <th>Contact</th>
                        <th>OT Date</th>
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
                            <td><span class="badge text-bg-secondary">{{ $status }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('hospital.ot.assistant.lens.edit', ['slug' => $slug, 'bookingId' => $booking->id]) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-pencil-square me-1"></i> Lens Entry
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No records found for lens workflow.</td>
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
