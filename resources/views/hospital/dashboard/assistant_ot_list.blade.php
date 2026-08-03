@extends('hospital.layouts.app')
@section('title', 'My OT Patients')
@section('page-header', $assistantName ? 'My OT Patients — '.$assistantName : 'My OT Patients')

@section('page-actions')
    <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
@endsection

@section('content')
<div class="dot-list-page">
    <div class="card dot-filter-card border-0 mb-4">
        <div class="card-body">
            <div class="dot-filter-title mb-3">
                <i class="bi bi-funnel-fill me-2"></i> Date Range Filter
            </div>
            <form method="GET" action="{{ route('hospital.dashboard.assistant-ot', ['slug' => $slug]) }}" class="dot-filter-form">
                <div class="dot-filter-fields">
                    <div>
                        <label class="form-label" for="date_range">Date range</label>
                        <input type="text" id="date_range" class="form-control dot-input"
                            data-hms-date-range
                            data-start-name="start_date"
                            data-end-name="end_date"
                            data-start-value="{{ $startDate }}"
                            data-end-value="{{ $endDate }}"
                            data-auto-submit="1"
                            placeholder="Select start → end date"
                            autocomplete="off"
                            readonly
                            style="min-width:220px;">
                    </div>
                    <div class="dot-filter-actions">
                        <a href="{{ route('hospital.dashboard.assistant-ot', ['slug' => $slug]) }}" class="btn dot-btn-outline">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card dot-table-card border-0">
        <div class="card-header dot-table-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <strong>Assigned OT Patients</strong>
            <span class="badge dot-count-badge">{{ $bookings->total() }} total</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 dot-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Contact</th>
                            <th>Age</th>
                            <th>OT Date</th>
                            <th>Surgery</th>
                            <th>Eye</th>
                            <th>Doctor</th>
                            <th>Process Status</th>
                            <th>Payment</th>
                            <th>Payment Mode</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            @php
                                $paymentStatus = $booking->payment_status;
                                $paymentLabel = match ($paymentStatus) {
                                    'paid' => 'Paid',
                                    'partially_paid' => 'Partially Paid',
                                    'unpriced' => 'Package Not Set',
                                    default => 'Pending',
                                };
                                $paymentClass = match ($paymentStatus) {
                                    'paid' => 'text-bg-success',
                                    'partially_paid' => 'text-bg-info',
                                    'unpriced' => 'text-bg-secondary',
                                    default => 'text-bg-warning',
                                };
                                $mode = strtolower((string) ($booking->payment_mode ?? ''));
                                $modeLabel = match ($mode) {
                                    'cash' => 'Cash',
                                    'online' => 'Online',
                                    'mediclaim' => 'Mediclaim',
                                    default => $mode !== '' ? ucfirst($mode) : '-',
                                };
                            @endphp
                            <tr>
                                <td>{{ $booking->patient?->full_name ?? '-' }}</td>
                                <td>{{ $booking->patient?->contact_no ?: '-' }}</td>
                                <td>{{ $booking->patient?->age ?: '-' }}</td>
                                <td>{{ optional($booking->surgery_date)->format('d M Y') ?? '-' }}</td>
                                <td>{{ $booking->ot_type ?: '-' }}</td>
                                <td>{{ $booking->eye ?: '-' }}</td>
                                <td>{{ $booking->otDoctor?->name ? 'Dr. '.$booking->otDoctor->name : '-' }}</td>
                                <td><span class="badge text-bg-secondary text-uppercase">{{ $booking->ot_status }}</span></td>
                                <td><span class="badge {{ $paymentClass }}">{{ $paymentLabel }}</span></td>
                                <td>{{ $modeLabel }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No patients assigned to you yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($bookings->hasPages())
        <div class="mt-3">
            {{ $bookings->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
.dot-list-page {
    --dot-primary: #1B4F72;
    --dot-soft: #EBF5FB;
    --dot-border: rgba(27, 79, 114, 0.12);
    padding-bottom: 1.5rem;
}
.dot-filter-card,
.dot-table-card {
    background: #fff;
    border-radius: 18px !important;
    box-shadow: 0 8px 22px rgba(27, 79, 114, 0.06);
    border: 1px solid var(--dot-border) !important;
    overflow: hidden;
}
.dot-filter-title { color: var(--dot-primary); font-weight: 800; font-size: .95rem; }
.dot-filter-fields {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: .85rem 1rem;
    align-items: end;
}
.dot-filter-form .form-label {
    font-size: .78rem;
    font-weight: 700;
    color: rgba(27, 79, 114, 0.8);
    margin-bottom: .25rem;
}
.dot-input {
    border-radius: 10px;
    border: 1px solid rgba(27, 79, 114, 0.16);
}
.dot-filter-actions { display: flex; gap: .5rem; }
.dot-btn-primary {
    background: var(--dot-primary);
    border: 1px solid var(--dot-primary);
    color: #fff;
    font-weight: 700;
    border-radius: 10px;
    padding: .45rem 1.1rem;
}
.dot-btn-primary:hover { background: #154360; border-color: #154360; color: #fff; }
.dot-btn-outline {
    border: 1px solid rgba(27, 79, 114, 0.22);
    color: var(--dot-primary);
    font-weight: 700;
    border-radius: 10px;
    background: #fff;
}
.dot-table-header {
    background: #fff;
    border-bottom: 1px solid var(--dot-border);
    color: var(--dot-primary);
    padding: .9rem 1.15rem;
}
.dot-count-badge {
    background: var(--dot-soft);
    color: var(--dot-primary);
    border: 1px solid var(--dot-border);
    font-weight: 700;
}
.dot-table thead th {
    background: rgba(27, 79, 114, 0.07);
    color: rgba(27, 79, 114, 0.82);
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .03em;
    white-space: nowrap;
}
@media (max-width: 768px) {
    .dot-filter-fields { grid-template-columns: 1fr 1fr; }
    .dot-filter-actions { grid-column: 1 / -1; }
}
</style>
@endpush
