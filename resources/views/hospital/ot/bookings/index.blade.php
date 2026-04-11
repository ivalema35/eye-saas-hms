@extends('hospital.layouts.app')
@section('title', 'OT Bookings')
@section('page-header', 'OT Bookings')

@section('page-actions')
    <a href="{{ route('hospital.ot.bookings.create', ['slug' => $slug]) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> New Booking
    </a>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>OT Doctor</th>
                        <th>Surgery Date</th>
                        <th>Eye</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        <tr>
                            <td>{{ $booking->id }}</td>
                            <td>{{ $booking->patient?->full_name ?? '-' }}</td>
                            <td>{{ $booking->otDoctor?->name ?? '-' }}</td>
                            <td>{{ optional($booking->surgery_date)->format('d M Y') }}</td>
                            <td>{{ $booking->eye }}</td>
                            <td>{{ $booking->ot_type }}</td>
                            <td><span class="badge text-bg-secondary">{{ str($booking->ot_status)->replace('_', ' ')->title() }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No OT bookings found.</td>
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
