@extends('hospital.layouts.app')
@section('title', 'Patient Report')
@section('page-header', 'Patient Report')

@section('page-actions')
    <a href="{{ route('hospital.dashboard.admin-patients.export', array_filter([
            'slug' => $slug,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reception_id' => $receptionId,
            'doctor_id' => $doctorId,
        ])) }}" class="hms-btn hms-btn-primary">
        <i class="bi bi-file-earmark-excel me-1"></i> Download Excel
    </a>
    <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
@endsection

@section('content')
<div class="adr-page">
    <div class="card adr-filter-card border-0 mb-4">
        <div class="card-body">
            <div class="adr-filter-title mb-3">
                <i class="bi bi-funnel-fill me-2"></i> Duration-wise Filter
            </div>
            <form method="GET" action="{{ route('hospital.dashboard.admin-patients', ['slug' => $slug]) }}" class="adr-filter-form">
                <div class="adr-filter-fields">
                    <div>
                        <label class="form-label" for="date_range">Date range</label>
                        <input type="text" id="date_range" class="form-control adr-input"
                            data-hms-date-range
                            data-start-name="start_date"
                            data-end-name="end_date"
                            data-start-value="{{ $startDate }}"
                            data-end-value="{{ $endDate }}"
                            placeholder="Select start → end date"
                            autocomplete="off"
                            readonly
                            style="min-width:220px;">
                    </div>
                    <div>
                        <label class="form-label" for="reception_id">Reception</label>
                        <select name="reception_id" id="reception_id" class="form-select adr-input">
                            <option value="">All Reception</option>
                            @foreach($receptions as $rec)
                                <option value="{{ $rec->id }}" @selected($receptionId === $rec->id)>{{ $rec->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="doctor_id">Doctor</label>
                        <select name="doctor_id" id="doctor_id" class="form-select adr-input">
                            <option value="">All Doctors</option>
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->id }}" @selected($doctorId === $doc->id)>Dr. {{ $doc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="adr-filter-actions">
                        <button type="submit" class="btn adr-btn-primary">Apply</button>
                        <a href="{{ route('hospital.dashboard.admin-patients', ['slug' => $slug]) }}" class="btn adr-btn-outline">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card adr-table-card border-0">
        <div class="card-header adr-table-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <strong>
                Patients
                @if($startDate === $endDate)
                    — {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                @else
                    — {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                @endif
            </strong>
            <span class="badge adr-count-badge">{{ $patients->total() }} total</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 adr-table">
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>City</th>
                            <th>Contact</th>
                            <th>Age</th>
                            <th>Time</th>
                            <th>Time Slot</th>
                            <th>Doctor</th>
                            <th>Dr. Index</th>
                            <th>Reception</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($patients as $patient)
                            @php
                                $time = $patient->checked_in_at ?? $patient->created_at;
                            @endphp
                            <tr>
                                <td>{{ $patient->full_name }}</td>
                                <td>{{ $patient->cityName ?: '-' }}</td>
                                <td>{{ $patient->contact_no ?: '-' }}</td>
                                <td>{{ $patient->age ?: '-' }}</td>
                                <td>{{ $time ? $time->format('h:i A') : '-' }}</td>
                                <td>{{ $patient->slot?->slot_name ?: '-' }}</td>
                                <td>{{ $patient->doctor?->name ? 'Dr. '.$patient->doctor->name : '-' }}</td>
                                <td>{{ $patient->doctor_patient_no ?: '-' }}</td>
                                <td>{{ $patient->reception?->name ?: '-' }}</td>
                                <td>{{ $patient->appointment_date?->format('d M Y') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No patients found for selected filters.</td>
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
.adr-page { --c:#1B4F72; --soft:#EBF5FB; --border:rgba(27,79,114,.12); padding-bottom:1.5rem; }
.adr-filter-card, .adr-table-card {
    border-radius:18px; box-shadow:0 8px 22px rgba(27,79,114,.06); border:1px solid var(--border)!important; overflow:hidden;
}
.adr-filter-title { color:var(--c); font-weight:800; font-size:.95rem; }
.adr-filter-fields {
    display:grid; grid-template-columns:1.2fr 1fr 1fr auto; gap:.85rem 1rem; align-items:end;
}
.adr-filter-form .form-label { font-size:.78rem; font-weight:700; color:rgba(27,79,114,.8); margin-bottom:.25rem; }
.adr-input { border-radius:10px; border:1px solid rgba(27,79,114,.16); }
.adr-filter-actions { display:flex; gap:.5rem; flex-wrap:wrap; }
.adr-btn-primary {
    background:var(--c); border:1px solid var(--c); color:#fff; font-weight:700; border-radius:10px; padding:.45rem 1.1rem;
}
.adr-btn-primary:hover { background:#154360; border-color:#154360; color:#fff; }
.adr-btn-outline {
    border:1px solid rgba(27,79,114,.22); color:var(--c); font-weight:700; border-radius:10px; background:#fff;
}
.adr-btn-outline:hover { background:var(--soft); color:var(--c); }
.adr-table-header { background:#fff; border-bottom:1px solid var(--border); color:var(--c); padding:.9rem 1.15rem; }
.adr-count-badge { background:var(--soft); color:var(--c); border:1px solid var(--border); font-weight:700; }
.adr-table thead th {
    background:rgba(27,79,114,.07); color:rgba(27,79,114,.82); font-size:.75rem; text-transform:uppercase;
    letter-spacing:.03em; border-bottom:1px solid var(--border); white-space:nowrap;
}
.adr-table td { color:#1F3345; font-size:.9rem; }
@media (max-width: 992px) {
    .adr-filter-fields { grid-template-columns:1fr 1fr; }
    .adr-filter-actions { grid-column:1 / -1; }
}
@media (max-width: 576px) {
    .adr-filter-fields { grid-template-columns:1fr; }
}
</style>
@endpush
