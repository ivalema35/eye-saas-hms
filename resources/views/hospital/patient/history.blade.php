@extends('hospital.layouts.app')
@section('title', 'Patient History')
@section('page-header', 'Patient History')

@push('styles')
<style>
.patient-history-page {
    --history-primary: #ebf5fbeb;
    --history-secondary: #1B4F72;
    --history-secondary-08: rgba(27, 79, 114, 0.08);
    --history-secondary-12: rgba(27, 79, 114, 0.12);
    --history-secondary-18: rgba(27, 79, 114, 0.18);
    --history-secondary-24: rgba(27, 79, 114, 0.24);
    color: var(--history-secondary);
    animation: history-page-in 420ms ease both;
}

.history-heading {
    background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94));
    border: 1px solid var(--history-secondary-12);
    border-radius: 22px;
    padding: 1.15rem 1.25rem;
    box-shadow: 0 18px 44px rgba(27, 79, 114, 0.09);
}

.history-heading-icon,
.history-avatar {
    background: var(--history-secondary);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 14px 30px rgba(27, 79, 114, 0.22);
}

.history-heading-icon {
    width: 48px;
    height: 48px;
    border-radius: 16px;
}

.history-heading h4,
.history-timeline-title,
.history-patient-name {
    color: var(--history-secondary) !important;
}

.history-search-card,
.history-summary-card,
.history-timeline-card {
    background: rgba(255, 255, 255, .84);
    border: 1px solid var(--history-secondary-12) !important;
    border-radius: 22px;
    box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
    overflow: hidden;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    animation: history-card-rise 520ms cubic-bezier(.2,.9,.2,1) both;
}

.history-search-card { animation-delay: 40ms; }
.history-summary-card { top: 80px; animation-delay: 80ms; }
.history-timeline-card { animation-delay: 120ms; }

.history-search-shell {
    display: flex;
    gap: .85rem;
    flex-wrap: wrap;
    align-items: center;
}

.history-search-box {
    max-width: 680px;
    min-width: 300px;
    border: 1px solid var(--history-secondary-12);
    border-radius: 18px;
    background: rgba(255, 255, 255, .76);
    padding: .35rem;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.7);
}

.history-search-box .input-group-text,
.history-search-box .form-control {
    background: transparent !important;
    border: 0 !important;
    color: var(--history-secondary);
    box-shadow: none !important;
}

.history-search-box .form-control {
    font-weight: 750;
}

.history-search-box .form-control::placeholder {
    color: rgba(27, 79, 114, .52);
}

.history-search-btn,
.patient-history-page .btn-primary {
    background: var(--history-secondary) !important;
    border-color: var(--history-secondary) !important;
    color: #fff !important;
}

.patient-history-page .btn {
    border-radius: 12px;
    font-weight: 800;
    transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, border-color 170ms ease, color 170ms ease;
}

.patient-history-page .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 26px rgba(27, 79, 114, .14);
}

.history-alert {
    border: 1px solid rgba(230, 126, 34, .22) !important;
    background: rgba(253, 235, 208, .72) !important;
    color: var(--history-secondary);
    border-radius: 18px !important;
    animation: history-card-rise 420ms ease both;
}

.history-avatar {
    border-radius: 24px;
    width: 86px;
    height: 86px;
    font-size: 2.05rem;
    margin: 0 auto 1rem;
}

.history-patient-code {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    background: var(--history-primary);
    color: var(--history-secondary);
    border: 1px solid var(--history-secondary-12);
    border-radius: 999px;
    padding: .42rem .85rem;
    font-weight: 900;
}

.history-detail-list .list-group-item {
    background: transparent;
    color: var(--history-secondary);
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: .65rem;
}

.history-detail-list .list-group-item i {
    width: 34px;
    height: 34px;
    border-radius: 12px;
    background: var(--history-primary);
    color: var(--history-secondary) !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: 0 !important;
}

.history-count-badge {
    background: var(--history-secondary) !important;
    border-radius: 999px;
    padding: .35rem .65rem;
}

.history-timeline-header {
    background: linear-gradient(135deg, rgba(235, 245, 251, .92), rgba(255, 255, 255, .94)) !important;
    border-bottom: 1px solid var(--history-secondary-12) !important;
}

.history-print-btn {
    width: 42px;
    height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: #EDF4FF !important;
    border: 1px solid #D9E5F2 !important;
    color: #5D7FB6 !important;
    text-decoration: none !important;
    flex-shrink: 0;
    box-shadow: none !important;
    padding: 0 !important;
    line-height: 1;
}

.history-print-btn:hover {
    background: #E6EFFF !important;
    border-color: #C9D9EE !important;
    color: #5D7FB6 !important;
}

.history-print-btn i {
    font-size: 1.12rem;
    line-height: 1;
}

.history-empty {
    background: var(--history-primary);
    border: 1px dashed var(--history-secondary-18);
    border-radius: 18px;
}

.timeline {
    position: relative;
    padding-left: 2.75rem;
}

.timeline::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 17px;
    width: 3px;
    border-radius: 999px;
    background: linear-gradient(180deg, var(--history-secondary), rgba(27, 79, 114, .08));
}

.timeline-item {
    position: relative;
    margin-bottom: 2rem;
    animation: history-row-in 460ms ease both;
}

.timeline-marker {
    position: absolute;
    top: .25rem;
    left: -2.68rem;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
    border: 4px solid #fff;
    font-size: .88rem;
    background: var(--history-secondary) !important;
    box-shadow: 0 12px 28px rgba(27, 79, 114, .20) !important;
}

.timeline-content {
    border: 1px solid var(--history-secondary-12) !important;
    border-left: 5px solid var(--history-secondary) !important;
    border-radius: 18px;
    box-shadow: 0 12px 32px rgba(27, 79, 114, .08) !important;
    transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
    overflow: hidden;
}

.timeline-content:hover {
    transform: translateX(4px);
    border-color: var(--history-secondary-24) !important;
    box-shadow: 0 18px 42px rgba(27, 79, 114, .13) !important;
}

.timeline-content h6,
.timeline-content strong,
.history-data-value {
    color: var(--history-secondary) !important;
}

.history-date-badge {
    background: var(--history-primary) !important;
    color: var(--history-secondary) !important;
    border: 1px solid var(--history-secondary-12) !important;
    border-radius: 999px;
    font-weight: 800;
}

.history-summary-box,
.history-data-card {
    background: var(--history-primary) !important;
    border: 1px solid var(--history-secondary-12) !important;
    border-radius: 16px !important;
}

.history-field-label {
    color: rgba(27, 79, 114, .62) !important;
    font-weight: 800;
}

.history-data-badge {
    background: #fff !important;
    border-color: var(--history-secondary-12) !important;
    color: var(--history-secondary) !important;
    border-radius: 999px;
    padding: .42rem .7rem;
}

.patient-history-page [class*="btn-outline-"] {
    color: var(--history-secondary) !important;
    border-color: var(--history-secondary-24) !important;
    background: rgba(255, 255, 255, .78) !important;
}

.patient-history-page [class*="btn-outline-"]:hover {
    color: #fff !important;
    background: var(--history-secondary) !important;
    border-color: var(--history-secondary) !important;
}

@keyframes history-page-in {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes history-card-rise {
    from { opacity: 0; transform: translateY(12px) scale(.99); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes history-row-in {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
    .patient-history-page,
    .history-search-card,
    .history-summary-card,
    .history-timeline-card,
    .timeline-item {
        animation: none;
    }

    .patient-history-page * {
        transition: none !important;
    }
}

@media (max-width: 992px) {
    .history-summary-card {
        position: static !important;
    }
}

@media (max-width: 576px) {
    .history-heading {
        padding: 1rem;
    }

    .history-search-shell {
        align-items: stretch;
    }

    .history-search-box,
    .history-search-btn {
        max-width: none;
        min-width: 100%;
        width: 100%;
    }

    .timeline {
        padding-left: 2.25rem;
    }

    .timeline-marker {
        left: -2.3rem;
        width: 34px;
        height: 34px;
    }
}
</style>
@endpush

@section('content')
<div class="patient-history-page">
@php
        $user = Auth::guard('hospital_user')->user();
        $isDoctor = in_array($user?->role?->slug, ['doctor', 'ot_doctor']);
    @endphp

    @if(!$isDoctor)
    <div class="history-heading d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0 d-flex align-items-center gap-3" style="color: var(--color-primary);">
            <span class="history-heading-icon">
                <i class="bi bi-clock-history fs-4"></i>
            </span>
            <span>Patient History</span>
        </h4>
        <a href="{{ route('hospital.patients.index', ['slug' => $slug]) }}" 
           class="btn btn-outline-secondary d-flex align-items-center gap-2" 
           style="border-color: var(--history-secondary); color: var(--history-secondary);">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Patients</span>
        </a>
    </div>
    @endif

    <div class="card premium-card history-search-card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form action="{{ route('hospital.patients.history', ['slug' => $slug]) }}" method="GET">
                <div class="history-search-shell">
                    <div class="input-group input-group-lg history-search-box flex-grow-1" style="max-width: 600px;">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search"
                               class="form-control clinical-input border-start-0 ps-0"
                               placeholder="Search by MRD No, Contact, or Patient Name..."
                               value="{{ $search ?? '' }}"autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary history-search-btn px-4">
                        <i class="bi bi-search me-2"></i> Find Patient
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($search && ! $patient)
        <div class="alert alert-warning history-alert border-0 shadow-sm rounded-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            No patient found matching "<strong>{{ $search }}</strong>".
        </div>
    @endif

    @if($patient)
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card premium-card history-summary-card border-0 shadow-sm sticky-top" style="top: 80px;">
                <div class="card-body p-4 text-center">
                    <div class="history-avatar mx-auto mb-3" style="width:80px;height:80px;font-size:2rem;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <h5 class="history-patient-name fw-bold mb-2">
                        {{ $patient->first_name }}
                        @if($patient->middle_name) {{ $patient->middle_name }} @endif
                        {{ $patient->last_name }}
                    </h5>
                    <p class="history-patient-code mb-0 small">
                        {{ $patient->patient_code }}
                        &nbsp;|&nbsp;
                        {{ ucfirst($patient->gender) }},
                        {{ $patient->age }} yrs
                    </p>

                    <ul class="history-detail-list list-group list-group-flush text-start mt-4">
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
                            <span class="history-count-badge badge bg-primary">{{ $history->count() }}</span>
                            visit{{ $history->count() === 1 ? '' : 's' }} recorded
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card premium-card history-timeline-card border-0 shadow-sm">
                <div class="card-header history-timeline-header bg-white p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                        <h5 class="history-timeline-title mb-0 fw-bold" style="color: var(--color-primary);">
                            <i class="bi bi-clock-history me-2"></i> Clinical Timeline
                        </h5>
                        <a href="{{ route('hospital.patients.history.print', ['slug' => $slug, 'patient' => $patient->id]) }}"
                           class="history-print-btn"
                           title="Print patient history"
                           aria-label="Print patient history"
                           target="_blank"
                           rel="noopener">
                            <i class="bi bi-printer"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">

                    @if($history->isEmpty())
                        <p class="history-empty text-muted text-center py-5 mb-0">
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
                                    <div class="timeline-marker bg-{{ $exam->color }} text-white shadow-sm">
                                        <i class="bi {{ $exam->icon }}"></i>
                                    </div>

                                    <div class="timeline-content card border-0 border-start border-4
                                                border-{{ $exam->color }} shadow-sm mb-0">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                                <h6 class="fw-bold text-{{ $exam->color }} mb-0">
                                                    {{ $exam->type }}
                                                </h6>
                                                <span class="history-date-badge badge bg-light text-muted border small">
                                                    <i class="bi bi-calendar-event me-1"></i>
                                                    {{ \Carbon\Carbon::parse($exam->examined_at)->format('d M Y, h:i A') }}
                                                </span>
                                            </div>

                                            <p class="text-muted small mb-3">
                                                <i class="bi bi-person-badge me-1"></i>
                                                Examined by:
                                                <strong>Dr. {{ $exam->doctor->name ?? 'Unknown' }}</strong>
                                            </p>

                                            @if(! empty($data))
                                            <div class="history-summary-box bg-light p-3 rounded-3 mb-3">
                                                <div class="row g-2 small">
                                                    @if(! empty($data['chief_complaint']))
                                                    <div class="col-sm-6">
                                                        <span class="history-field-label text-muted">Chief Complaint</span><br>
                                                        <span class="history-data-value fw-medium">{{ $data['chief_complaint'] }}</span>
                                                    </div>
                                                    @endif
                                                    @if(! empty($data['diagnosis']))
                                                    <div class="col-sm-6">
                                                        <span class="history-field-label text-muted">Diagnosis</span><br>
                                                        <span class="history-data-value fw-medium">{{ $data['diagnosis'] }}</span>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                            @endif

                                            <button class="btn btn-sm btn-outline-{{ $exam->color }}"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#examData{{ $exam->id }}{{ $exam->type === 'Primary Exam' ? 'P' : 'S' }}">
                                                <i class="bi bi-chevron-down me-1"></i> View Full Clinical Data
                                            </button>

                                            <div class="collapse mt-3"
                                                 id="examData{{ $exam->id }}{{ $exam->type === 'Primary Exam' ? 'P' : 'S' }}">
                                                @if(empty($data))
                                                    <p class="text-muted small mb-0">No structured data recorded.</p>
                                                @else
                                                    <div class="history-data-card card card-body bg-light border-0 p-3">
                                                        <div class="row g-3">
                                                            @foreach($data as $key => $value)
                                                                @if(! is_array($value) && $value !== null && $value !== '')
                                                                <div class="col-md-4 col-sm-6">
                                                                    <small class="history-field-label text-muted d-block text-truncate">
                                                                        {{ \Illuminate\Support\Str::headline($key) }}
                                                                    </small>
                                                                    <span class="history-data-value fw-medium small">{{ $value }}</span>
                                                                </div>
                                                                @elseif(is_array($value) && ! empty($value))
                                                                <div class="col-12">
                                                                    <small class="history-field-label text-muted d-block mb-1">
                                                                        {{ \Illuminate\Support\Str::headline($key) }}
                                                                    </small>
                                                                    <div class="d-flex flex-wrap gap-1">
                                                                        @foreach($value as $subKey => $subVal)
                                                                            @if($subVal !== null && $subVal !== '')
                                                                            <span class="history-data-badge badge bg-white border text-dark small fw-normal">
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
</div>
@endsection
