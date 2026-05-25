@extends('hospital.layouts.app')
@section('title', 'OT Assistant Dashboard')
@section('page-header', 'OT Assistant Dashboard')

@section('content')
<div class="ot-assistant-page">
<div class="card border-0 shadow-sm ota2-card">
    <div class="card-header bg-white border-bottom ota2-card-header d-flex justify-content-between align-items-center flex-wrap">
        <div class="ota2-title-wrap">
            <span class="ota2-title-icon">
                <i class="bi bi-eye fs-4"></i>
            </span>
            <div>
                <h5 class="mb-0 fw-bold ota2-title" style="color: var(--color-primary);">
                    Lens Workflow Queue
                </h5>
                <div class="ota2-subtitle">Track OT lens entries and complete lens workflow details.</div>
            </div>
        </div>
        <span class="ota2-total-pill">{{ $bookings->total() }} total</span>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive ota2-table-wrap">
            <table class="table table-hover align-middle mb-0 ota2-table">
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
                            <td><span class="ota2-patient-cell"><i class="bi bi-person-fill"></i>{{ $booking->patient?->full_name ?? '-' }}</span></td>
                            <td><span class="ota2-contact-cell"><i class="bi bi-telephone-fill"></i>{{ $booking->patient?->contact_no ?? '-' }}</span></td>
                            <td><span class="ota2-date-cell"><i class="bi bi-calendar2-event"></i>{{ optional($booking->surgery_date)->format('d M Y') }}</span></td>
                            <td><span class="badge text-bg-secondary ota2-status-badge">{{ $status }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('hospital.ot.assistant.lens.edit', ['slug' => $slug, 'bookingId' => $booking->id]) }}" class="btn btn-sm btn-primary ota2-lens-btn">
                                    <i class="bi bi-pencil-square me-1"></i> Lens Entry
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4 ota2-empty-cell">No records found for lens workflow.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($bookings->hasPages())
    <div class="ota2-pagination">
        {{ $bookings->links() }}
    </div>
@endif
</div>
@endsection
