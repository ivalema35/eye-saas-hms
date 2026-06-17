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

/* ── Date-grouped visit layout ── */
.date-group { margin-bottom: 2.25rem; }

.date-divider {
    display: flex;
    align-items: center;
    gap: .9rem;
    margin-bottom: 1.1rem;
}

.date-divider-badge {
    background: var(--history-secondary);
    color: #fff;
    border-radius: 999px;
    padding: .38rem 1.05rem;
    font-size: 12.5px;
    font-weight: 800;
    white-space: nowrap;
    letter-spacing: .2px;
    box-shadow: 0 6px 18px rgba(27,79,114,.22);
    flex-shrink: 0;
}

.date-divider-line {
    flex: 1;
    height: 2px;
    border-radius: 999px;
    background: linear-gradient(90deg, rgba(27,79,114,.22), rgba(27,79,114,.04));
}

.date-divider-count {
    font-size: 11px;
    font-weight: 700;
    color: rgba(27,79,114,.52);
    white-space: nowrap;
    flex-shrink: 0;
}

.visit-exam-card {
    border-radius: 16px !important;
    border: 1px solid var(--history-secondary-12) !important;
    box-shadow: 0 6px 22px rgba(27,79,114,.07) !important;
    transition: transform 170ms ease, box-shadow 170ms ease;
    overflow: hidden;
}

.visit-exam-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 36px rgba(27,79,114,.13) !important;
}

.visit-exam-header {
    padding: .65rem 1rem;
    font-size: 13px;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: .5rem;
    border-bottom: 1px solid var(--history-secondary-12);
}

.visit-exam-header.is-primary {
    background: rgba(27,79,114,.07);
    color: #1B4F72;
}

.visit-exam-header.is-secondary {
    background: rgba(108,117,125,.07);
    color: #495057;
}

.visit-exam-time {
    margin-left: auto;
    font-size: 11.5px;
    font-weight: 700;
    background: #fff;
    border: 1px solid var(--history-secondary-12);
    border-radius: 999px;
    padding: .22rem .7rem;
    color: var(--history-secondary);
}

/* ── Canvas Clone ── */
.cv-wrap {
    border: 1px solid #343a40;
    border-radius: 4px;
    background: #fff;
}
.cv-box {
    border: 1px solid #343a40;
    border-radius: 4px;
    padding: 6px;
    font-size: 11px;
    color: #1a1a1a;
}
.cv-title {
    background: #1B4F72;
    color: #fff;
    padding: 2px 6px;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 10px;
    letter-spacing: .04em;
    margin-bottom: 4px;
}
.cv-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
    margin-bottom: 4px;
}
.cv-table th, .cv-table td {
    border: 1px solid #343a40;
    padding: 2px 4px;
    text-align: center;
}
.cv-table thead tr:first-child th { background:#1B4F72; color:#fff; font-size:10px; }
.cv-table thead tr:nth-child(2) th { background:#eef4f9; color:#1B4F72; font-size:10px; font-weight:600; }
.cv-table tbody th { background:#f0f4f8; color:#1B4F72; font-weight:700; font-size:10px; }
.cv-oe-hd th { background:#343a40; color:#fff; font-weight:700; }
.cv-pill {
    display:inline-block;
    background:#eef4f9;
    color:#1B4F72;
    padding:1px 8px;
    border-radius:8px;
    margin:1px 2px 1px 0;
    font-size:10px;
}
.cv-badge {
    display:inline-block;
    background:#1B4F72;
    color:#fff;
    padding:1px 8px;
    border-radius:10px;
    font-size:10px;
    margin:1px 2px 1px 0;
}
.cv-vn-line {
    font-size:11px;
    display:inline-flex;
    align-items:center;
    margin-right:8px;
    margin-bottom:2px;
}
.cv-rx-hd th { background:#343a40; color:#fff; font-weight:700; }

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

    {{-- Disambiguation: multiple distinct-name patients found for this search --}}
    @if(isset($nameGroups) && $nameGroups->isNotEmpty())
        <div class="alert history-alert border-0 shadow-sm mb-3">
            <i class="bi bi-people-fill me-2"></i>
            <strong>{{ $nameGroups->count() }} patients found</strong> for
            "<strong>{{ $search }}</strong>". Please select the correct patient:
        </div>
        <div class="row g-3 mb-4">
            @foreach($nameGroups as $group)
            @php
                $baseUrl = ($historyRoute ?? route('hospital.patients.history', ['slug' => $slug]));
                $href    = $baseUrl . '?search=' . urlencode($search ?? '') . '&patient_ids=' . $group->patient_ids;
            @endphp
            <div class="col-12 col-md-6 col-lg-4">
                <a href="{{ $href }}"
                   class="card h-100 text-decoration-none history-search-card border-0 shadow-sm"
                   style="border-radius:18px;">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <div class="history-heading-icon flex-shrink-0"
                             style="width:44px;height:44px;border-radius:14px;font-size:1.1rem;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <h6 class="fw-bold mb-1 text-truncate" style="color:var(--history-secondary);">
                                {{ $group->display_name }}
                            </h6>
                            <div class="small" style="color:rgba(27,79,114,.62);">
                                MRD: <strong>{{ $group->patient_code }}</strong>
                            </div>
                            <div class="small" style="color:rgba(27,79,114,.62);">
                                {{ ucfirst($group->gender ?? '') }}{{ $group->age ? ', ' . $group->age . ' yrs' : '' }}
                            </div>
                        </div>
                        <i class="bi bi-chevron-right ms-auto flex-shrink-0"
                           style="color:rgba(27,79,114,.4);"></i>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    @endif

    @if($search && ! $patient && (isset($nameGroups) ? $nameGroups->isEmpty() : true))
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
                        @php
                            $visitDays = $history->groupBy(fn($e) => \Carbon\Carbon::parse($e->examined_at)->format('d M Y'))->count();
                        @endphp
                        <li class="list-group-item px-0 py-2 border-0">
                            <i class="bi bi-journal-medical text-muted me-2"></i>
                            <span class="history-count-badge badge bg-primary">{{ $visitDays }}</span>
                            visit{{ $visitDays === 1 ? '' : 's' }} recorded
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
                        @php
                            $grouped = $history->groupBy(
                                fn($e) => \Carbon\Carbon::parse($e->examined_at)->format('d M Y')
                            );
                        @endphp

                        @foreach($grouped as $date => $exams)
                        <div class="date-group">

                            {{-- Date divider --}}
                            <div class="date-divider">
                                <span class="date-divider-badge">
                                    <i class="bi bi-calendar3 me-1"></i>{{ $date }}
                                </span>
                                <div class="date-divider-line"></div>
                                <span class="date-divider-count">
                                    {{ $exams->count() }} exam{{ $exams->count() > 1 ? 's' : '' }}
                                </span>
                            </div>

                            {{-- Exam cards for this date --}}
                            <div class="row g-3">
                                @foreach($exams as $exam)
                                @php
                                    $data    = is_array($exam->exam_data)
                                                ? $exam->exam_data
                                                : (json_decode($exam->exam_data, true) ?? []);
                                    $isPrimary = $exam->type === 'Primary Exam';
                                    $collapseId = 'ed' . $exam->id . ($isPrimary ? 'P' : 'S');
                                @endphp
                                <div class="col-12">
                                    <div class="visit-exam-card card border-0 mb-0">

                                        {{-- Card header: type + time --}}
                                        <div class="visit-exam-header {{ $isPrimary ? 'is-primary' : 'is-secondary' }}">
                                            <i class="bi {{ $exam->icon }} fs-5"></i>
                                            {{ $exam->type }}
                                            <span class="visit-exam-time">
                                                <i class="bi bi-clock me-1"></i>
                                                {{ \Carbon\Carbon::parse($exam->examined_at)->format('h:i A') }}
                                            </span>
                                        </div>

                                        <div class="card-body p-3">
                                            @php
                                                $vision  = $data['vision']  ?? [];
                                                $pg      = $data['pg']      ?? [];
                                                $st      = $data['st']      ?? [];
                                                $nct     = $data['nct']     ?? [];
                                                $oe      = $data['oe']      ?? [];
                                                $fundus  = $data['fundus']  ?? [];
                                                $coRows  = array_filter($data['co_rows'] ?? [], fn($r) => !empty($r['complaint']));
                                                $kcoRows = array_filter($data['kco_rows'] ?? [], fn($r) => !empty($r['condition']));
                                                $dxIds   = $data['diagnoses'] ?? [];
                                                $advTxt  = trim($data['advice'] ?? '');
                                                $oeMap   = ['sac'=>'SAC','lid'=>'Lids','conj'=>'Conjunctiva','cornea'=>'Cornea','ac'=>'Ant. Chamber','iris'=>'Iris','pupil'=>'Pupil','lens'=>'Lens','em'=>'EOM','covertest'=>'Cover Test'];
                                                // primary prescriptions come via relationship; secondary via rx array
                                                $rxLines = $isPrimary
                                                    ? ($exam->prescriptions ?? collect())
                                                    : collect($data['rx'] ?? []);
                                            @endphp

                                            {{-- Doctor --}}
                                            <p class="text-muted small mb-2">
                                                <i class="bi bi-person-badge me-1"></i>
                                                Examined by: <strong style="color:var(--history-secondary);">Dr. {{ $exam->doctor->name ?? 'Unknown' }}</strong>
                                            </p>

                                            {{-- Toggle --}}
                                            <button class="btn btn-sm btn-outline-{{ $exam->color }} mt-1"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#{{ $collapseId }}">
                                                <i class="bi bi-clipboard2-pulse me-1"></i> View Clinical Data
                                            </button>

                                            <div class="collapse mt-3" id="{{ $collapseId }}">
                                              @php
                                                $cv = fn($v) => (isset($v) && $v !== '' && $v !== null) ? $v : '-';
                                                $dxNames = $diagnosisMasters->whereIn('id', (array)$dxIds)->pluck('diagnosis')->implode(', ');
                                                $dilate  = $data['dilate'] ?? 'No';
                                                $stBadges = array_filter([
                                                    ($data['st']['bifocal']       ?? false) ? 'Bifocal' : '',
                                                    ($data['st']['nd_separate']   ?? false) ? 'N&D Separate' : '',
                                                    ($data['st']['progressive']   ?? false) ? 'Progressive' : '',
                                                    ($data['st']['computer_uses'] ?? false) ? 'Computer Uses' : '',
                                                ]);
                                              @endphp

                                              {{-- Same 2-col canvas grid as the exam view --}}
                                              <div class="row g-2">

                                                {{-- LEFT COL: History+Vision  &  ST+Diagnosis+Rx --}}
                                                <div class="col-md-6 d-flex flex-column gap-2">

                                                  {{-- BOX 1: History & Vision --}}
                                                  <div class="cv-box">
                                                    <div class="cv-title">History &amp; Vision</div>

                                                    {{-- C/O table --}}
                                                    <table class="cv-table">
                                                      <thead>
                                                        <tr><th colspan="4">C/O</th></tr>
                                                        <tr><th>Complaint</th><th>Since</th><th>Eye</th><th>Comment</th></tr>
                                                      </thead>
                                                      <tbody>
                                                        @forelse($coRows as $cr)
                                                        <tr>
                                                          <td style="text-align:left">{{ $cr['complaint'] }}</td>
                                                          <td>{{ !empty($cr['since']) ? $cr['since'].' '.($cr['unit']??'') : '-' }}</td>
                                                          <td>{{ $cr['eye'] ?? '-' }}</td>
                                                          <td style="text-align:left">{{ $cr['comment'] ?? '-' }}</td>
                                                        </tr>
                                                        @empty
                                                        <tr><td colspan="4" style="text-align:center;color:#94a3b8">—</td></tr>
                                                        @endforelse
                                                      </tbody>
                                                    </table>

                                                    {{-- K/C/O table (only if exists) --}}
                                                    @if(count($kcoRows))
                                                    <table class="cv-table">
                                                      <thead>
                                                        <tr><th colspan="3">K/C/O</th></tr>
                                                        <tr><th>Condition</th><th>Since</th><th>Comment</th></tr>
                                                      </thead>
                                                      <tbody>
                                                        @foreach($kcoRows as $kr)
                                                        <tr>
                                                          <td style="text-align:left">{{ $kr['condition'] }}</td>
                                                          <td>{{ !empty($kr['since']) ? $kr['since'].' '.($kr['unit']??'') : '-' }}</td>
                                                          <td style="text-align:left">{{ $kr['comment'] ?? '-' }}</td>
                                                        </tr>
                                                        @endforeach
                                                      </tbody>
                                                    </table>
                                                    @endif

                                                    {{-- Vision + IOP inline row --}}
                                                    <div style="border-top:1px solid #dee2e6;padding-top:4px;margin-top:3px;display:flex;flex-wrap:wrap;align-items:center;gap:4px;font-size:11px;">
                                                      <span class="cv-vn-line"><strong>Vn</strong>&nbsp;{{ $cv($vision['vn_re']??'') }}/{{ $cv($vision['vn_le']??'') }}</span>
                                                      <span class="cv-vn-line"><strong>PH</strong>&nbsp;{{ $cv($vision['pnvn_re']??'') }}/{{ $cv($vision['pnvn_le']??'') }}</span>
                                                      <span class="cv-vn-line"><strong>NrVn</strong>&nbsp;{{ $cv($vision['nrvn_re']??'') }}/{{ $cv($vision['nrvn_le']??'') }}</span>
                                                      <span class="cv-vn-line"><strong>IOP:</strong>&nbsp;{{ $cv($nct['iop_re']??'') }}/{{ $cv($nct['iop_le']??'') }}</span>
                                                    </div>

                                                    {{-- PG table --}}
                                                    <table class="cv-table" style="margin-top:4px">
                                                      <thead>
                                                        <tr>
                                                          <th style="width:18px"></th>
                                                          <th colspan="4">RIGHT EYE (RE)</th>
                                                          <th colspan="4">LEFT EYE (LE)</th>
                                                        </tr>
                                                        <tr>
                                                          <th></th><th>SPH</th><th>CYL</th><th>AXIS</th><th>VN</th>
                                                          <th>SPH</th><th>CYL</th><th>AXIS</th><th>VN</th>
                                                        </tr>
                                                      </thead>
                                                      <tbody>
                                                        <tr>
                                                          <th>D</th>
                                                          <td>{{ $cv($pg['re']['ds']??'') }}</td><td>{{ $cv($pg['re']['dc']??'') }}</td><td>{{ $cv($pg['re']['ax']??'') }}</td><td>{{ $cv($pg['re']['vn']??'') }}</td>
                                                          <td>{{ $cv($pg['le']['ds']??'') }}</td><td>{{ $cv($pg['le']['dc']??'') }}</td><td>{{ $cv($pg['le']['ax']??'') }}</td><td>{{ $cv($pg['le']['vn']??'') }}</td>
                                                        </tr>
                                                        <tr>
                                                          <th>N</th>
                                                          <td>{{ $cv($pg['re']['ns']??'') }}</td><td>{{ $cv($pg['re']['nc']??'') }}</td><td>{{ $cv($pg['re']['na']??'') }}</td><td>{{ $cv($pg['re']['near_vn']??'') }}</td>
                                                          <td>{{ $cv($pg['le']['ns']??'') }}</td><td>{{ $cv($pg['le']['nc']??'') }}</td><td>{{ $cv($pg['le']['na']??'') }}</td><td>{{ $cv($pg['le']['near_vn']??'') }}</td>
                                                        </tr>
                                                      </tbody>
                                                    </table>
                                                  </div>

                                                  {{-- BOX 2: ST + Diagnosis & Rx --}}
                                                  <div class="cv-box">
                                                    <div class="cv-title">Subjective Testing (ST)</div>

                                                    <table class="cv-table">
                                                      <thead>
                                                        <tr>
                                                          <th style="width:18px"></th>
                                                          <th colspan="4">RIGHT EYE (RE)</th>
                                                          <th colspan="4">LEFT EYE (LE)</th>
                                                        </tr>
                                                        <tr>
                                                          <th></th><th>SPH</th><th>CYL</th><th>AXIS</th><th>VN</th>
                                                          <th>SPH</th><th>CYL</th><th>AXIS</th><th>VN</th>
                                                        </tr>
                                                      </thead>
                                                      <tbody>
                                                        <tr>
                                                          <th>D</th>
                                                          <td>{{ $cv($st['re']['ds']??'') }}</td><td>{{ $cv($st['re']['dc']??'') }}</td><td>{{ $cv($st['re']['ax']??'') }}</td><td>{{ $cv($st['re']['vn']??'') }}</td>
                                                          <td>{{ $cv($st['le']['ds']??'') }}</td><td>{{ $cv($st['le']['dc']??'') }}</td><td>{{ $cv($st['le']['ax']??'') }}</td><td>{{ $cv($st['le']['vn']??'') }}</td>
                                                        </tr>
                                                        <tr>
                                                          <th>N</th>
                                                          <td>{{ $cv($st['re']['ns']??'') }}</td><td>{{ $cv($st['re']['nc']??'') }}</td><td>{{ $cv($st['re']['na']??'') }}</td><td>-</td>
                                                          <td>{{ $cv($st['le']['ns']??'') }}</td><td>{{ $cv($st['le']['nc']??'') }}</td><td>{{ $cv($st['le']['na']??'') }}</td><td>-</td>
                                                        </tr>
                                                      </tbody>
                                                    </table>

                                                    @if(!empty($st['re']['add']) || !empty($st['le']['add']))
                                                    <div style="font-size:10px;color:#475569;margin:3px 0">
                                                      <span style="color:#1B4F72;font-weight:700;">ADD</span>&emsp;RE: <strong>{{ $st['re']['add'] ?? '-' }}</strong>&emsp;LE: <strong>{{ $st['le']['add'] ?? '-' }}</strong>
                                                    </div>
                                                    @endif
                                                    @if(count($stBadges))
                                                    <div style="margin-bottom:4px">
                                                      @foreach($stBadges as $b)<span class="cv-badge">{{ $b }}</span>@endforeach
                                                    </div>
                                                    @endif

                                                    <div class="cv-title mt-2">Diagnosis &amp; Rx</div>

                                                    <div style="font-size:10px;margin-bottom:4px">
                                                      <strong>Dx:</strong> {{ $dxNames ?: '-' }} &nbsp;
                                                      <strong>Dilate:</strong> {{ $dilate }}
                                                    </div>

                                                    <table class="cv-table">
                                                      <thead class="cv-rx-hd">
                                                        <tr><th>Medicine</th><th>Dosage</th><th>Days</th><th>Eye</th></tr>
                                                      </thead>
                                                      <tbody>
                                                        @forelse($rxLines as $rx)
                                                        @php
                                                          if ($isPrimary) {
                                                            $mName = $rx->medicine?->brand_name ?: ($rx->medicine?->name ?? '-');
                                                            $mDose = $rx->dosage?->dosage ?? '-';
                                                            $mDays = $rx->duration ? $rx->duration.' D' : '-';
                                                            $mEye  = $rx->eye ?? '-';
                                                          } else {
                                                            $rx = (array)$rx;
                                                            $mName = $rx['name'] ?? '-';
                                                            $mDose = isset($rx['dosage_id']) ? ($dosageMasters[$rx['dosage_id']]?->dosage ?? '-') : '-';
                                                            $mDays = !empty($rx['duration']) ? $rx['duration'].' D' : '-';
                                                            $mEye  = $rx['eye'] ?? '-';
                                                          }
                                                        @endphp
                                                        <tr>
                                                          <td style="text-align:left;font-weight:600">{{ $mName }}</td>
                                                          <td>{{ $mDose }}</td>
                                                          <td>{{ $mDays }}</td>
                                                          <td>{{ $mEye }}</td>
                                                        </tr>
                                                        @empty
                                                        <tr><td colspan="4" style="text-align:center;color:#94a3b8">No medicines</td></tr>
                                                        @endforelse
                                                      </tbody>
                                                    </table>
                                                  </div>

                                                </div>{{-- /left col --}}

                                                {{-- RIGHT COL: O/E  &  Fundus --}}
                                                <div class="col-md-6 d-flex flex-column gap-2">

                                                  {{-- BOX 3: O/E --}}
                                                  <div class="cv-box">
                                                    <div class="cv-title">On Examination (O/E)</div>
                                                    <table class="cv-table">
                                                      <thead class="cv-oe-hd">
                                                        <tr><th style="text-align:left">O/E</th><th>RIGHT</th><th>LEFT</th></tr>
                                                      </thead>
                                                      <tbody>
                                                        @foreach(['sac'=>'SAC','lid'=>'LID','conj'=>'CONJ','cornea'=>'CORNEA','ac'=>'AC','iris'=>'IRIS','pupil'=>'PUPIL','lens'=>'LENS','em'=>'EM','covertest'=>'COVERTEST','other'=>'OTHER'] as $k=>$lbl)
                                                        <tr>
                                                          <th style="text-align:left;background:#f0f4f8;color:#1B4F72">{{ $lbl }}</th>
                                                          <td>{{ $cv($oe[$k.'_re']??'') }}</td>
                                                          <td>{{ $cv($oe[$k.'_le']??'') }}</td>
                                                        </tr>
                                                        @endforeach
                                                      </tbody>
                                                    </table>
                                                  </div>

                                                  {{-- BOX 4: Fundus --}}
                                                  <div class="cv-box">
                                                    <div class="cv-title">Fundus</div>
                                                    <table class="cv-table">
                                                      <thead class="cv-oe-hd">
                                                        <tr><th style="text-align:left">Fundus</th><th>RIGHT</th><th>LEFT</th></tr>
                                                      </thead>
                                                      <tbody>
                                                        <tr><th style="text-align:left;background:#f0f4f8;color:#1B4F72">DISC</th><td>{{ $cv($fundus['disc_re']??'') }}</td><td>{{ $cv($fundus['disc_le']??'') }}</td></tr>
                                                        <tr><th style="text-align:left;background:#f0f4f8;color:#1B4F72">FR</th><td>{{ $cv($fundus['fr_re']??'') }}</td><td>{{ $cv($fundus['fr_le']??'') }}</td></tr>
                                                        <tr><th style="text-align:left;background:#f0f4f8;color:#1B4F72">COMMENT</th><td>{{ $cv($fundus['comment_re']??'') }}</td><td>{{ $cv($fundus['comment_le']??'') }}</td></tr>
                                                      </tbody>
                                                    </table>
                                                  </div>

                                                </div>{{-- /right col --}}
                                              </div>{{-- /row --}}

                                              {{-- Advice (full width) --}}
                                              @if($advTxt)
                                              <div class="cv-box mt-2">
                                                <div class="cv-title">Advice</div>
                                                <div style="font-size:12px;white-space:pre-line;line-height:1.6">{{ $advTxt }}</div>
                                              </div>
                                              @endif

                                            </div>{{-- /collapse --}}
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                        </div>
                        @endforeach
                    @endif

                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
