@extends('hospital.layouts.app')
@section('title', 'Total Patients')
@section('page-header', 'Total Patients')

@section('page-actions')
    <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
@endsection

@section('content')
<div class="rtp-page">

    <div class="rtp-top mb-4">
        <div class="rtp-collection">
            <div class="rtp-collection-icon">
                <i class="bi bi-wallet2"></i>
            </div>
            <div class="rtp-collection-label">Total Collection</div>
            <div class="rtp-collection-value">{{ money($collection, 0) }}</div>
            <div class="rtp-collection-hint">
                @if($startDate === $endDate)
                    {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                @else
                    {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                @endif
            </div>
        </div>

        <div class="rtp-filter-card">
            <div class="rtp-filter-title">
                <i class="bi bi-funnel-fill me-2"></i> Date Range Filter
            </div>
            <form method="GET" action="{{ route('hospital.receptionist.total-patients', ['slug' => $slug]) }}" class="rtp-filter-form">
                <div class="rtp-filter-fields">
                    <div>
                        <label class="form-label" for="date_range">Date range</label>
                        <input type="text" id="date_range" class="form-control rtp-input"
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
                    <div class="rtp-filter-actions">
                        <a href="{{ route('hospital.receptionist.total-patients', ['slug' => $slug]) }}" class="btn rtp-btn-outline">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card rtp-table-card border-0">
        <div class="card-header rtp-table-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <strong>Patients</strong>
            <span class="badge rtp-count-badge">{{ $patients->total() }} total</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 rtp-table">
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Reception</th>
                            <th>Date</th>
                            <th>Contact</th>
                            <th>City</th>
                            <th>Age</th>
                            <th>Type</th>
                            <th>Doctor</th>
                            <th>Case type</th>
                            <th>Fee</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($patients as $patient)
                            @php
                                $caseTypeValue = strtolower(trim((string) ($patient->caseType?->case_type ?? '')));
                                $caseTypeLabel = match (true) {
                                    str_contains($caseTypeValue, 'general') => 'General',
                                    str_contains($caseTypeValue, 'old') => 'Old',
                                    str_contains($caseTypeValue, 'new') => 'New',
                                    default => $patient->caseType?->case_type ?: '-',
                                };
                                $typeLabel = $patient->otAppointmentSource
                                    ? 'OT'
                                    : (in_array((string) $patient->type, ['phone', '1'], true) ? 'Phone' : 'Walk-in');
                            @endphp
                            <tr>
                                <td>{{ $patient->full_name }}</td>
                                <td>{{ $patient->reception?->name ?: '-' }}</td>
                                <td>{{ $patient->appointment_date?->format('d M Y') ?? '-' }}</td>
                                <td>{{ $patient->contact_no ?: '-' }}</td>
                                <td>{{ $patient->cityName ?: '-' }}</td>
                                <td>{{ $patient->age ?: '-' }}</td>
                                <td><span class="badge rtp-type-badge">{{ $typeLabel }}</span></td>
                                <td>{{ $patient->doctor?->name ? 'Dr. '.$patient->doctor->name : '-' }}</td>
                                <td>{{ $caseTypeLabel }}</td>
                                <td class="fw-semibold">{{ money((float) $patient->case_fee, 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No patients found for selected dates.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($patients->hasPages())
        <div class="mt-3">
            {{ $patients->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
.rtp-page {
    --rtp-primary: #1B4F72;
    --rtp-soft: #EBF5FB;
    --rtp-border: rgba(27, 79, 114, 0.12);
    --rtp-muted: rgba(27, 79, 114, 0.68);
    padding-bottom: 1.5rem;
}
.rtp-top {
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 1rem;
    align-items: stretch;
}
.rtp-collection {
    background: #fff;
    border: 1px solid var(--rtp-border);
    border-radius: 18px;
    padding: 1.25rem 1.1rem;
    box-shadow: 0 8px 22px rgba(27, 79, 114, 0.06);
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.rtp-collection-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #D5F5E3;
    color: #27AE60;
    font-size: 1.15rem;
    margin-bottom: .65rem;
}
.rtp-collection-label {
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--rtp-muted);
}
.rtp-collection-value {
    font-size: 1.85rem;
    font-weight: 900;
    color: var(--rtp-primary);
    line-height: 1.15;
    margin: .3rem 0;
    letter-spacing: -0.5px;
}
.rtp-collection-hint {
    font-size: .75rem;
    color: var(--rtp-muted);
}
.rtp-filter-card {
    background: #fff;
    border: 1px solid var(--rtp-border);
    border-radius: 18px;
    padding: 1.15rem 1.25rem;
    box-shadow: 0 8px 22px rgba(27, 79, 114, 0.06);
}
.rtp-filter-title {
    color: var(--rtp-primary);
    font-weight: 800;
    font-size: .95rem;
    margin-bottom: .85rem;
}
.rtp-filter-fields {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: .85rem 1rem;
    align-items: end;
}
.rtp-filter-form .form-label {
    font-size: .78rem;
    font-weight: 700;
    color: rgba(27, 79, 114, 0.8);
    margin-bottom: .25rem;
}
.rtp-input {
    border-radius: 10px;
    border: 1px solid rgba(27, 79, 114, 0.16);
}
.rtp-input:focus {
    border-color: var(--rtp-primary);
    box-shadow: 0 0 0 .2rem rgba(27, 79, 114, 0.12);
}
.rtp-filter-actions {
    display: flex;
    gap: .5rem;
}
.rtp-btn-primary {
    background: var(--rtp-primary);
    border: 1px solid var(--rtp-primary);
    color: #fff;
    font-weight: 700;
    border-radius: 10px;
    padding: .45rem 1.1rem;
}
.rtp-btn-primary:hover { background: #154360; border-color: #154360; color: #fff; }
.rtp-btn-outline {
    border: 1px solid rgba(27, 79, 114, 0.22);
    color: var(--rtp-primary);
    font-weight: 700;
    border-radius: 10px;
    background: #fff;
}
.rtp-btn-outline:hover { background: var(--rtp-soft); color: var(--rtp-primary); }
.rtp-table-card {
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 8px 22px rgba(27, 79, 114, 0.06);
    border: 1px solid var(--rtp-border) !important;
}
.rtp-table-header {
    background: #fff;
    border-bottom: 1px solid var(--rtp-border);
    color: var(--rtp-primary);
    padding: .9rem 1.15rem;
}
.rtp-count-badge {
    background: var(--rtp-soft);
    color: var(--rtp-primary);
    border: 1px solid var(--rtp-border);
    font-weight: 700;
}
.rtp-table thead th {
    background: rgba(27, 79, 114, 0.07);
    color: rgba(27, 79, 114, 0.82);
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .03em;
    border-bottom: 1px solid var(--rtp-border);
    white-space: nowrap;
}
.rtp-table td { color: #1F3345; font-size: .9rem; }
.rtp-type-badge {
    background: var(--rtp-soft);
    color: var(--rtp-primary);
    font-weight: 700;
}
@media (max-width: 900px) {
    .rtp-top { grid-template-columns: 1fr; }
    .rtp-filter-fields { grid-template-columns: 1fr 1fr; }
    .rtp-filter-actions { grid-column: 1 / -1; }
}
@media (max-width: 576px) {
    .rtp-filter-fields { grid-template-columns: 1fr; }
}
</style>
@endpush
