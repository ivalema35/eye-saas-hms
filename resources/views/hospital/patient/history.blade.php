@extends('hospital.layouts.app')
@section('title', 'Patient History')
@section('page-header', 'Patient History')

@push('styles')
<style>
/* =========================================
   PATIENT HISTORY — PREMIUM VERTICAL TIMELINE
   ========================================= */
.timeline {
    position: relative;
    padding-left: 2.5rem;
}

.timeline::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 15px;
    width: 2px;
    background-color: #E2E8F0;
}

.timeline-item {
    position: relative;
    margin-bottom: 2rem;
}

.timeline-marker {
    position: absolute;
    top: 0;
    left: -2.5rem;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
    border: 3px solid #fff;
    font-size: 0.8rem;
}

.timeline-content {
    transition: transform 0.2s ease;
}

.timeline-content:hover {
    transform: translateX(5px);
}

.avatar-circle {
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-primary, #2d6a9f) 0%, #4a9bd1 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}
</style>
@endpush

@section('content')
{{-- Page heading --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0" style="color: var(--color-primary);">
        <i class="bi bi-clock-history me-2"></i> Patient History
    </h4>
</div>

{{-- Search Bar --}}
<div class="card premium-card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form action="{{ route('hospital.patients.history', ['slug' => $slug]) }}" method="GET">
            <div class="d-flex gap-3 flex-wrap align-items-center">
                <div class="input-group input-group-lg flex-grow-1" style="max-width: 600px;">
                    <span class="input-group-text bg-white border-end-0 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search"
                           class="form-control clinical-input border-start-0 ps-0"
                           placeholder="Search by MRD No, Contact, or Patient Name…"
                           value="{{ $search ?? '' }}"
                           autofocus>
                </div>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-search me-2"></i> Find Patient
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Not found alert --}}
@if($search && ! $patient)
    <div class="alert alert-warning border-0 shadow-sm rounded-3">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        No patient found matching "<strong>{{ $search }}</strong>".
    </div>
@endif

{{-- Patient card + timeline --}}
@if($patient)
<div class="row g-4">

    {{-- ── Left: Patient Summary Card ── --}}
    <div class="col-lg-4">
        <div class="card premium-card border-0 shadow-sm sticky-top" style="top: 80px;">
            <div class="card-body p-4 text-center">
                <div class="avatar-circle mx-auto mb-3" style="width:80px;height:80px;font-size:2rem;">
                    <i class="bi bi-person-fill"></i>
                </div>
                <h5 class="fw-bold mb-1">
                    {{ $patient->first_name }}
                    @if($patient->middle_name) {{ $patient->middle_name }} @endif
                    {{ $patient->last_name }}
                </h5>
                <p class="text-muted mb-0 small">
                    {{ $patient->patient_code }}
                    &nbsp;|&nbsp;
                    {{ ucfirst($patient->gender) }},
                    {{ $patient->age }} yrs
                </p>

                <ul class="list-group list-group-flush text-start mt-4">
                    <li class="list-group-item px-0 py-2 border-0">
                        <i class="bi bi-telephone text-muted me-2"></i>
                        {{ $patient->contact_no }}
                    </li>
                    <li class="list-group-item px-0 py-2 border-0">
                        <i class="bi bi-geo-alt text-muted me-2"></i>
                        {{ $patient->location->name ?? 'N/A' }}
                    </li>
                    <li class="list-group-item px-0 py-2 border-0">
                        <i class="bi bi-calendar-plus text-muted me-2"></i>
                        Registered: {{ $patient->created_at->format('d M Y') }}
                    </li>
                    <li class="list-group-item px-0 py-2 border-0">
                        <i class="bi bi-journal-medical text-muted me-2"></i>
                        <span class="badge bg-primary">{{ $history->count() }}</span>
                        visit{{ $history->count() === 1 ? '' : 's' }} recorded
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- ── Right: Clinical Timeline ── --}}
    <div class="col-lg-8">
        <div class="card premium-card border-0 shadow-sm">
            <div class="card-header bg-white p-4 border-bottom">
                <h5 class="mb-0 fw-bold" style="color: var(--color-primary);">
                    <i class="bi bi-clock-history me-2"></i> Clinical Timeline
                </h5>
            </div>
            <div class="card-body p-4">

                @if($history->isEmpty())
                    <p class="text-muted text-center py-5">
                        <i class="bi bi-journal-x fs-2 d-block mb-2 opacity-50"></i>
                        No examination history found for this patient.
                    </p>
                @else
                    <div class="timeline">
                        @foreach($history as $exam)
                            @php
                                $data = is_array($exam->exam_data)
                                    ? $exam->exam_data
                                    : (json_decode($exam->exam_data, true) ?? []);
                            @endphp

                            <div class="timeline-item">
                                {{-- Dot --}}
                                <div class="timeline-marker bg-{{ $exam->color }} text-white shadow-sm">
                                    <i class="bi {{ $exam->icon }}"></i>
                                </div>

                                {{-- Card --}}
                                <div class="timeline-content card border-0 border-start border-4
                                            border-{{ $exam->color }} shadow-sm mb-0">
                                    <div class="card-body p-3">

                                        {{-- Header row --}}
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                            <h6 class="fw-bold text-{{ $exam->color }} mb-0">
                                                {{ $exam->type }}
                                            </h6>
                                            <span class="badge bg-light text-muted border small">
                                                <i class="bi bi-calendar-event me-1"></i>
                                                {{ \Carbon\Carbon::parse($exam->examined_at)->format('d M Y, h:i A') }}
                                            </span>
                                        </div>

                                        {{-- Doctor --}}
                                        <p class="text-muted small mb-3">
                                            <i class="bi bi-person-badge me-1"></i>
                                            Examined by:
                                            <strong>Dr. {{ $exam->doctor->name ?? 'Unknown' }}</strong>
                                        </p>

                                        {{-- Quick summary row --}}
                                        @if(! empty($data))
                                        <div class="bg-light p-3 rounded-3 mb-3">
                                            <div class="row g-2 small">
                                                @if(! empty($data['chief_complaint']))
                                                <div class="col-sm-6">
                                                    <span class="text-muted">Chief Complaint</span><br>
                                                    <span class="fw-medium">{{ $data['chief_complaint'] }}</span>
                                                </div>
                                                @endif
                                                @if(! empty($data['diagnosis']))
                                                <div class="col-sm-6">
                                                    <span class="text-muted">Diagnosis</span><br>
                                                    <span class="fw-medium">{{ $data['diagnosis'] }}</span>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                        @endif

                                        {{-- Expand button --}}
                                        <button class="btn btn-sm btn-outline-{{ $exam->color }}"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#examData{{ $exam->id }}{{ $exam->type === 'Primary Exam' ? 'P' : 'S' }}">
                                            <i class="bi bi-chevron-down me-1"></i> View Full Clinical Data
                                        </button>

                                        {{-- Expanded clinical data grid --}}
                                        <div class="collapse mt-3"
                                             id="examData{{ $exam->id }}{{ $exam->type === 'Primary Exam' ? 'P' : 'S' }}">
                                            @if(empty($data))
                                                <p class="text-muted small mb-0">No structured data recorded.</p>
                                            @else
                                                <div class="card card-body bg-light border-0 p-3">
                                                    <div class="row g-3">
                                                        @foreach($data as $key => $value)
                                                            @if(! is_array($value) && $value !== null && $value !== '')
                                                            <div class="col-md-4 col-sm-6">
                                                                <small class="text-muted d-block text-truncate">
                                                                    {{ \Illuminate\Support\Str::headline($key) }}
                                                                </small>
                                                                <span class="fw-medium small">{{ $value }}</span>
                                                            </div>
                                                            @elseif(is_array($value) && ! empty($value))
                                                            <div class="col-12">
                                                                <small class="text-muted d-block mb-1">
                                                                    {{ \Illuminate\Support\Str::headline($key) }}
                                                                </small>
                                                                <div class="d-flex flex-wrap gap-1">
                                                                    @foreach($value as $subKey => $subVal)
                                                                        @if($subVal !== null && $subVal !== '')
                                                                        <span class="badge bg-white border text-dark small fw-normal">
                                                                            {{ is_string($subKey) ? \Illuminate\Support\Str::headline($subKey).': ' : '' }}@if(is_array($subVal)){{ json_encode($subVal) }}@else{{ $subVal }}@endif
                                                                        </span>
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

            </div>
        </div>
    </div>

</div>
@endif
@endsection
