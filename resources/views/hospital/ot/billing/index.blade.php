@extends('hospital.layouts.app')
@section('title', 'Discharge & Invoices')
@section('page-header', 'Discharge & Invoices')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0 fw-bold" style="color: var(--color-primary);">
            <i class="bi bi-receipt me-2"></i> Billing Desk
        </h5>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Patient</th>
                        <th>OT Date</th>
                        <th>Status</th>
                        <th>Invoice</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        @php
                            $hasInvoice = in_array((int) $booking->id, $invoiceBookingIds, true);
                        @endphp
                        <tr>
                            <td>{{ $booking->patient?->full_name ?? '-' }}</td>
                            <td>{{ optional($booking->surgery_date)->format('d M Y') }}</td>
                            <td><span class="badge text-bg-secondary">{{ strtoupper((string) $booking->ot_status) }}</span></td>
                            <td>
                                @if($hasInvoice)
                                    <span class="badge text-bg-success">Generated</span>
                                @else
                                    <span class="badge text-bg-warning">Pending</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if(!$hasInvoice)
                                    <form method="POST" action="{{ route('hospital.ot.invoice.generate', ['slug' => $slug, 'bookingId' => $booking->id]) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">Generate</button>
                                    </form>
                                @endif

                                @if($hasInvoice)
                                    <a href="{{ route('hospital.ot.invoice.print', ['slug' => $slug, 'bookingId' => $booking->id]) }}" class="btn btn-sm btn-outline-primary">Invoice</a>
                                    <a href="{{ route('hospital.ot.discharge.print', ['slug' => $slug, 'bookingId' => $booking->id]) }}" class="btn btn-sm btn-outline-secondary">Discharge</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No records available for billing.</td>
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
