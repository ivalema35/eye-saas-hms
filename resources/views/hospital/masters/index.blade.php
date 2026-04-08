@extends('hospital.layouts.app')
@section('title', 'Master Data')
@section('page-header', 'Master Data Management')

@section('content')

{{-- ── Basic Masters ──────────────────────────────────────────────── --}}
<div class="mb-2">
    <h6 class="text-uppercase fw-bold letter-spacing-1 mb-3"
        style="color: var(--color-primary); font-size: .72rem; letter-spacing: .08em;">
        <i class="bi bi-grid-3x3-gap me-1"></i> Basic Masters
    </h6>

    <div class="row g-3">
        @php
        $basicMasters = [
            ['type' => 'cases',     'label' => 'Case Types',   'icon' => 'bi-folder2-open',    'color' => 'primary'],
            ['type' => 'locations', 'label' => 'Locations',    'icon' => 'bi-geo-alt-fill',    'color' => 'success'],
            ['type' => 'referrers', 'label' => 'Referrers',    'icon' => 'bi-person-lines-fill','color' => 'warning'],
            ['type' => 'durations', 'label' => 'Durations',    'icon' => 'bi-hourglass-split', 'color' => 'secondary'],
        ];
        @endphp

        @foreach($basicMasters as $m)
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <a href="{{ route('hospital.masters.basic.index', ['slug' => $slug, 'type' => $m['type']]) }}"
               class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 master-nav-card">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-3 gap-2">
                        <div class="master-icon-box bg-{{ $m['color'] }}-subtle text-{{ $m['color'] }}">
                            <i class="bi {{ $m['icon'] }} fs-5"></i>
                        </div>
                        <span class="fw-semibold small" style="color: var(--color-primary);">{{ $m['label'] }}</span>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
</div>

<hr class="my-4">

{{-- ── Eye Exam Masters ────────────────────────────────────────────── --}}
<div>
    <h6 class="text-uppercase fw-bold mb-3"
        style="color: var(--color-primary); font-size: .72rem; letter-spacing: .08em;">
        <i class="bi bi-eye me-1"></i> Eye Exam Masters
    </h6>

    {{-- Clinical / Complaint / Diagnosis --}}
    <p class="text-muted small mb-2 fw-medium">Clinical</p>
    <div class="row g-3 mb-4">
        @php
        $clinicalMasters = [
            ['type' => 'chief-complaints', 'label' => 'Chief Complaints', 'icon' => 'bi-clipboard2-pulse', 'color' => 'danger'],
            ['type' => 'kcos',       'label' => 'K/C/O',            'icon' => 'bi-heart-pulse',      'color' => 'warning'],
            ['type' => 'diagnosis',  'label' => 'Diagnoses',        'icon' => 'bi-patch-check',      'color' => 'success'],
            ['type' => 'advice',     'label' => 'Advice',           'icon' => 'bi-chat-left-text',   'color' => 'primary'],
        ];
        @endphp
        @foreach($clinicalMasters as $m)
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <a href="{{ route('hospital.masters.detail.index', ['slug' => $slug, 'type' => $m['type']]) }}"
               class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 master-nav-card">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-3 gap-2">
                        <div class="master-icon-box bg-{{ $m['color'] }}-subtle text-{{ $m['color'] }}">
                            <i class="bi {{ $m['icon'] }} fs-5"></i>
                        </div>
                        <span class="fw-semibold small" style="color: var(--color-primary);">{{ $m['label'] }}</span>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    {{-- Vision --}}
    <p class="text-muted small mb-2 fw-medium">Vision Values</p>
    <div class="row g-3 mb-4">
        @php
        $visionMasters = [
            ['type' => 'vn',      'label' => 'V/N',       'icon' => 'bi-eye',            'color' => 'info'],
            ['type' => 'vngl',    'label' => 'Vn C GL',   'icon' => 'bi-eyeglasses',     'color' => 'primary'],
            ['type' => 'vnst',    'label' => 'Vn C ST',   'icon' => 'bi-view-list',      'color' => 'primary'],
            ['type' => 'pnvn',    'label' => 'PH NV/N',   'icon' => 'bi-eye-fill',       'color' => 'info'],
            ['type' => 'nrvn',    'label' => 'NR V/N',    'icon' => 'bi-binoculars',     'color' => 'info'],
            ['type' => 'sph_cyl', 'label' => 'SPH / CYL', 'icon' => 'bi-circle-half',   'color' => 'primary'],
            ['type' => 'axis',    'label' => 'Axis',      'icon' => 'bi-arrows-angle-expand','color' => 'secondary'],
            ['type' => 'nct',     'label' => 'NCT (IOP)', 'icon' => 'bi-activity',       'color' => 'warning'],
        ];
        @endphp
        @foreach($visionMasters as $m)
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <a href="{{ route('hospital.masters.detail.index', ['slug' => $slug, 'type' => $m['type']]) }}"
               class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 master-nav-card">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-3 gap-2">
                        <div class="master-icon-box bg-{{ $m['color'] }}-subtle text-{{ $m['color'] }}">
                            <i class="bi {{ $m['icon'] }} fs-5"></i>
                        </div>
                        <span class="fw-semibold small" style="color: var(--color-primary);">{{ $m['label'] }}</span>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    {{-- Anterior Segment --}}
    <p class="text-muted small mb-2 fw-medium">Anterior Segment (O/E)</p>
    <div class="row g-3 mb-4">
        @php
        $anteriorMasters = [
            ['type' => 'sac',    'label' => 'SAC',        'icon' => 'bi-droplet',       'color' => 'info'],
            ['type' => 'lid',    'label' => 'Lid',        'icon' => 'bi-eye-slash',     'color' => 'secondary'],
            ['type' => 'conj',   'label' => 'Conjunctiva','icon' => 'bi-circle-fill',   'color' => 'danger'],
            ['type' => 'cornea', 'label' => 'Cornea',     'icon' => 'bi-record-circle', 'color' => 'primary'],
            ['type' => 'ac',     'label' => 'A/C',        'icon' => 'bi-layers',        'color' => 'info'],
            ['type' => 'iris',   'label' => 'Iris',       'icon' => 'bi-bullseye',      'color' => 'warning'],
            ['type' => 'pupil',  'label' => 'Pupil',      'icon' => 'bi-dot',           'color' => 'dark'],
            ['type' => 'lens',   'label' => 'Lens',       'icon' => 'bi-camera-lens',   'color' => 'success'],
            ['type' => 'em',     'label' => 'E/M',        'icon' => 'bi-arrows-move',   'color' => 'secondary'],
            ['type' => 'covertest','label' => 'Cover Test','icon' => 'bi-shield-check', 'color' => 'success'],
        ];
        @endphp
        @foreach($anteriorMasters as $m)
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <a href="{{ route('hospital.masters.detail.index', ['slug' => $slug, 'type' => $m['type']]) }}"
               class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 master-nav-card">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-3 gap-2">
                        <div class="master-icon-box bg-{{ $m['color'] }}-subtle text-{{ $m['color'] }}">
                            <i class="bi {{ $m['icon'] }} fs-5"></i>
                        </div>
                        <span class="fw-semibold small" style="color: var(--color-primary);">{{ $m['label'] }}</span>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    {{-- Posterior Segment --}}
    <p class="text-muted small mb-2 fw-medium">Posterior Segment (FUNDUS)</p>
    <div class="row g-3 mb-4">
        @php
        $posteriorMasters = [
            ['type' => 'disc', 'label' => 'Disc', 'icon' => 'bi-circle',      'color' => 'secondary'],
            ['type' => 'fr',   'label' => 'F/R',  'icon' => 'bi-reception-4', 'color' => 'secondary'],
        ];
        @endphp
        @foreach($posteriorMasters as $m)
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <a href="{{ route('hospital.masters.detail.index', ['slug' => $slug, 'type' => $m['type']]) }}"
               class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 master-nav-card">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-3 gap-2">
                        <div class="master-icon-box bg-{{ $m['color'] }}-subtle text-{{ $m['color'] }}">
                            <i class="bi {{ $m['icon'] }} fs-5"></i>
                        </div>
                        <span class="fw-semibold small" style="color: var(--color-primary);">{{ $m['label'] }}</span>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
</div>

@endsection

@push('styles')
<style>
.master-nav-card {
    transition: transform .15s ease, box-shadow .15s ease;
    cursor: pointer;
    min-height: 100px;
}
.master-nav-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(27, 79, 114, .12) !important;
}
.master-icon-box {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
</style>
@endpush

