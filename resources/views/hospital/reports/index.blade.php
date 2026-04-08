@extends('hospital.layouts.app')
@section('title', 'Patient Reports')
@section('page-header', 'Patient Reports')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h5 class="fw-bold mb-0" style="color: var(--color-primary);">
        <i class="bi bi-bar-chart-fill me-2"></i>Patient Reports
    </h5>

    <div class="d-flex gap-2">
        <a href="{{ route('hospital.reports.export.excel', array_merge(['slug' => $slug], request()->all())) }}" class="btn btn-success">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </a>
        <a href="{{ route('hospital.reports.export.pdf', array_merge(['slug' => $slug], request()->all())) }}" class="btn btn-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </a>
    </div>
</div>

<div class="card premium-card border-0 shadow-sm bg-success-subtle border-start border-success border-4 mb-4 sticky-top" style="top: 72px; z-index: 5;">
    <div class="card-body p-3 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="text-success fw-bold mb-1 text-uppercase">Total Collection (Walk-in)</h6>
            <small class="text-muted">Based on currently applied filters</small>
        </div>
        <h3 class="text-success fw-bold mb-0">₹ {{ number_format($totalCollection, 2) }}</h3>
    </div>
</div>

<div class="card premium-card border-0 shadow-sm mb-4">
    <div class="card-header bg-white p-3 border-bottom fw-bold">
        <i class="bi bi-funnel-fill text-primary me-2"></i> Report Filters
    </div>
    <div class="card-body p-4">
        <form action="{{ route('hospital.reports.index', ['slug' => $slug]) }}" method="GET" id="filterForm">
            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Date Range</label>
                    <input type="text" name="date_range" id="date_range" class="form-control clinical-input" placeholder="Select dates" value="{{ request('date_range') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Receptionist</label>
                    <select name="reception_id" id="reception_id" class="form-select clinical-input">
                        <option value="">All Receptionists</option>
                        @foreach($receptions as $rec)
                            <option value="{{ $rec->id }}" {{ (string) request('reception_id') === (string) $rec->id ? 'selected' : '' }}>{{ $rec->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Doctor</label>
                    <select name="doctor_id" class="form-select clinical-input">
                        <option value="">All Doctors</option>
                        @foreach($doctors as $doc)
                            <option value="{{ $doc->id }}" {{ (string) request('doctor_id') === (string) $doc->id ? 'selected' : '' }}>Dr. {{ $doc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 filter-conditional">
                    <label class="form-label">City / Location</label>
                    <select name="location_id" id="location_id" class="form-select clinical-input">
                        <option value="">All Cities</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ (string) request('location_id') === (string) $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 filter-conditional">
                    <label class="form-label">Case Type</label>
                    <select name="case_id" id="case_id" class="form-select clinical-input">
                        <option value="">All Cases</option>
                        @foreach($cases as $case)
                            <option value="{{ $case->id }}" {{ (string) request('case_id') === (string) $case->id ? 'selected' : '' }}>{{ $case->case_type }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Patient Type</label>
                    <select name="type" class="form-select clinical-input">
                        <option value="">All Types</option>
                        <option value="walkin" {{ in_array((string) request('type'), ['walkin', '0'], true) ? 'selected' : '' }}>Walk-in</option>
                        <option value="phone" {{ in_array((string) request('type'), ['phone', '1'], true) ? 'selected' : '' }}>Phone Appt</option>
                    </select>
                </div>

                <div class="col-12 text-end mt-4">
                    <a href="{{ route('hospital.reports.index', ['slug' => $slug]) }}" class="btn btn-light me-2">Clear Filters</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-search"></i> Generate Report</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card premium-card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table premium-table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>MRD No</th>
                        <th>Patient Name</th>
                        <th>City</th>
                        <th>Doctor</th>
                        <th>Receptionist</th>
                        <th>Case</th>
                        <th>Type</th>
                        <th>Fee</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $patient)
                    <tr>
                        <td>{{ $patient->created_at?->format('d M, Y h:i A') }}</td>
                        <td class="fw-bold text-secondary">{{ $patient->patient_code ?: '-' }}</td>
                        <td>{{ $patient->full_name }}</td>
                        <td>{{ $patient->location?->name ?: '-' }}</td>
                        <td>Dr. {{ $patient->doctor?->name ?: '-' }}</td>
                        <td>{{ $patient->reception?->name ?: '-' }}</td>
                        <td>{{ $patient->caseType?->case_type ?: '-' }}</td>
                        <td>{{ in_array((string) $patient->type, ['walkin', '0'], true) ? 'Walk-in' : 'Phone' }}</td>
                        <td class="fw-bold">₹{{ number_format((float) $patient->case_fee, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No records found for the selected filters.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($patients->hasPages())
    <div class="mt-3 d-flex justify-content-center">
        {{ $patients->links() }}
    </div>
@endif

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    flatpickr('#date_range', {
        mode: 'range',
        dateFormat: 'Y-m-d',
    });

    const receptionSelect = document.getElementById('reception_id');
    const conditionalFilters = document.querySelectorAll('.filter-conditional');
    const locationSelect = document.getElementById('location_id');
    const caseSelect = document.getElementById('case_id');

    function toggleFilters() {
        const hasReception = receptionSelect.value !== '';

        conditionalFilters.forEach((wrapper) => {
            wrapper.style.opacity = hasReception ? '0.5' : '1';
            wrapper.style.pointerEvents = hasReception ? 'none' : 'auto';
        });

        locationSelect.disabled = hasReception;
        caseSelect.disabled = hasReception;

        if (hasReception) {
            locationSelect.value = '';
            caseSelect.value = '';
        }
    }

    toggleFilters();
    receptionSelect.addEventListener('change', toggleFilters);
});
</script>
@endsection
