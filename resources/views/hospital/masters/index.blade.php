@extends('hospital.layouts.app')
@section('title', 'Master Data')
{{-- Layout page-header intentionally unused — the title + breadcrumb are
rendered above the sections instead. --}}

@section('content')
    <div class="masters-premium-page" id="mastersDesignRoot">

        <div class="masters-header-block">
            <div class="masters-header-title">
                <span class="masters-header-icon"><i class="bi bi-database-fill-gear"></i></span>
                Master Data Management
            </div>
            <nav class="masters-breadcrumb" aria-label="breadcrumb">
                <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                <span class="masters-breadcrumb-sep">/</span>
                <span class="masters-breadcrumb-current">Master Data</span>
            </nav>
        </div>


        {{-- ── Basic Masters ──────────────────────────────────────────────── --}}
        <div class="mb-2 masters-section">
            <h6 class="text-uppercase fw-bold letter-spacing-1 mb-3 masters-section-title"
                style="color: var(--color-primary); font-size: .72rem; letter-spacing: .08em;">
                <i class="bi bi-grid-3x3-gap me-1"></i> Basic Masters
            </h6>

            <div class="row g-3">
                @php
                    $basicMasters = [
                        ['type' => 'cases', 'label' => 'Case Types', 'icon' => 'bi-folder2-open', 'color' => 'primary'],
                        ['type' => 'locations', 'label' => 'Locations', 'icon' => 'bi-geo-alt-fill', 'color' => 'success'],
                        ['type' => 'referrers', 'label' => 'Referrers', 'icon' => 'bi-person-lines-fill', 'color' => 'warning'],
                        ['type' => 'durations', 'label' => 'Durations', 'icon' => 'bi-hourglass-split', 'color' => 'secondary'],
                    ];
                @endphp

                @foreach($basicMasters as $m)
                    <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                        <a href="{{ route('hospital.masters.basic.index', ['slug' => $slug, 'type' => $m['type']]) }}"
                            class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100 master-nav-card">
                                <div
                                    class="card-body d-flex flex-column align-items-center justify-content-center text-center p-3 gap-2">
                                    <div class="master-icon-box bg-{{ $m['color'] }}-subtle text-{{ $m['color'] }}">
                                        <i class="bi {{ $m['icon'] }} fs-5"></i>
                                    </div>
                                    <span class="fw-semibold small"
                                        style="color: var(--color-primary);">{{ $m['label'] }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>

        <hr class="my-4">

        {{-- ── OT Masters ─────────────────────────────────────────────────── --}}
        <div class="mb-4 masters-section">
            <h6 class="text-uppercase fw-bold mb-3 masters-section-title"
                style="color: var(--color-primary); font-size: .72rem; letter-spacing: .08em;">
                <i class="bi bi-hospital me-1"></i> OT Masters
            </h6>

            <div class="row g-3">
                @php
                    $otMasters = [
                        ['route' => 'hospital.masters.ot.lens-options.index', 'label' => 'Lens Options', 'icon' => 'bi-eyeglasses', 'color' => 'dark'],
                        ['route' => 'hospital.masters.ot.lens-powers.index', 'label' => 'Lens Powers', 'icon' => 'bi-rulers', 'color' => 'dark'],
                        ['route' => 'hospital.masters.ot.lens-inventory.index', 'label' => 'Lens Inventory', 'icon' => 'bi-box-seam', 'color' => 'dark'],
                        ['route' => 'hospital.masters.ot.slots.index', 'label' => 'OT Slots', 'icon' => 'bi-clock-history', 'color' => 'primary'],
                        ['route' => 'hospital.masters.ot.types.index', 'label' => 'OT Types', 'icon' => 'bi-tags-fill', 'color' => 'success'],
                        ['route' => 'hospital.masters.ot.surgery-types.index', 'label' => 'Surgery Types', 'icon' => 'bi-scissors', 'color' => 'warning'],
                        ['route' => 'hospital.masters.ot.charge-heads.index', 'label' => 'Charge Heads', 'icon' => 'bi-cash-stack', 'color' => 'info'],
                        ['route' => 'hospital.masters.ot.packages.index', 'label' => 'OT Packages', 'icon' => 'bi-box2-heart', 'color' => 'primary'],
                    ];
                @endphp

                @foreach($otMasters as $m)
                    <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                        <a href="{{ route($m['route'], ['slug' => $slug]) }}" class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100 master-nav-card">
                                <div
                                    class="card-body d-flex flex-column align-items-center justify-content-center text-center p-3 gap-2">
                                    <div class="master-icon-box bg-{{ $m['color'] }}-subtle text-{{ $m['color'] }}">
                                        <i class="bi {{ $m['icon'] }} fs-5"></i>
                                    </div>
                                    <span class="fw-semibold small"
                                        style="color: var(--color-primary);">{{ $m['label'] }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>

        <hr class="my-4">

        {{-- ── Eye Exam Masters ────────────────────────────────────────────── --}}
        <div class="masters-section">
            <h6 class="text-uppercase fw-bold mb-3 masters-section-title"
                style="color: var(--color-primary); font-size: .72rem; letter-spacing: .08em;">
                <i class="bi bi-eye me-1"></i> Eye Exam Masters
            </h6>

            {{-- Clinical / Complaint / Diagnosis --}}
            <p class="text-muted small mb-2 fw-medium masters-subsection-title">Clinical</p>
            <div class="row g-3 mb-4">
                @php
                    $clinicalMasters = [
                        ['type' => 'chief-complaints', 'label' => 'Chief Complaints', 'icon' => 'bi-clipboard2-pulse', 'color' => 'danger'],
                        ['type' => 'kcos', 'label' => 'K/C/O', 'icon' => 'bi-heart-pulse', 'color' => 'warning'],
                        ['type' => 'hno', 'label' => 'H/O', 'icon' => 'bi-clock-history', 'color' => 'info'],
                        ['type' => 'diagnosis', 'label' => 'Diagnoses', 'icon' => 'bi-patch-check', 'color' => 'success'],
                        ['type' => 'advice', 'label' => 'Advice', 'icon' => 'bi-chat-left-text', 'color' => 'primary'],
                    ];
                @endphp
                @foreach($clinicalMasters as $m)
                    <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                        <a href="{{ route('hospital.masters.detail.index', ['slug' => $slug, 'type' => $m['type']]) }}"
                            class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100 master-nav-card">
                                <div
                                    class="card-body d-flex flex-column align-items-center justify-content-center text-center p-3 gap-2">
                                    <div class="master-icon-box bg-{{ $m['color'] }}-subtle text-{{ $m['color'] }}">
                                        <i class="bi {{ $m['icon'] }} fs-5"></i>
                                    </div>
                                    <span class="fw-semibold small"
                                        style="color: var(--color-primary);">{{ $m['label'] }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            {{-- Vision --}}
            <p class="text-muted small mb-2 fw-medium masters-subsection-title">Vision Values</p>
            <div class="row g-3 mb-4">
                @php
                    $visionMasters = [
                        ['type' => 'vn', 'label' => 'V/N', 'icon' => 'bi-eye', 'color' => 'info'],
                        ['type' => 'vngl', 'label' => 'Vn C GL', 'icon' => 'bi-eyeglasses', 'color' => 'primary'],
                        ['type' => 'vnst', 'label' => 'Vn C ST', 'icon' => 'bi-view-list', 'color' => 'primary'],
                        ['type' => 'pnvn', 'label' => 'PH NV/N', 'icon' => 'bi-eye-fill', 'color' => 'info'],
                        ['type' => 'nrvn', 'label' => 'NR V/N', 'icon' => 'bi-binoculars', 'color' => 'info'],
                        ['type' => 'sph_cyl', 'label' => 'SPH / CYL', 'icon' => 'bi-circle-half', 'color' => 'primary'],
                        ['type' => 'axis', 'label' => 'Axis', 'icon' => 'bi-arrows-angle-expand', 'color' => 'secondary'],
                        ['type' => 'nct', 'label' => 'NCT (IOP)', 'icon' => 'bi-activity', 'color' => 'warning'],
                    ];
                @endphp
                @foreach($visionMasters as $m)
                    <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                        <a href="{{ route('hospital.masters.detail.index', ['slug' => $slug, 'type' => $m['type']]) }}"
                            class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100 master-nav-card">
                                <div
                                    class="card-body d-flex flex-column align-items-center justify-content-center text-center p-3 gap-2">
                                    <div class="master-icon-box bg-{{ $m['color'] }}-subtle text-{{ $m['color'] }}">
                                        <i class="bi {{ $m['icon'] }} fs-5"></i>
                                    </div>
                                    <span class="fw-semibold small"
                                        style="color: var(--color-primary);">{{ $m['label'] }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            {{-- Anterior Segment --}}
            <p class="text-muted small mb-2 fw-medium masters-subsection-title">Anterior Segment (O/E)</p>
            <div class="row g-3 mb-4">
                @php
                    $anteriorMasters = [
                        ['type' => 'sac', 'label' => 'SAC', 'icon' => 'bi-droplet', 'color' => 'info'],
                        ['type' => 'lid', 'label' => 'Lid', 'icon' => 'bi-eye-slash', 'color' => 'secondary'],
                        ['type' => 'conj', 'label' => 'Conjunctiva', 'icon' => 'bi-circle-fill', 'color' => 'danger'],
                        ['type' => 'cornea', 'label' => 'Cornea', 'icon' => 'bi-record-circle', 'color' => 'primary'],
                        ['type' => 'ac', 'label' => 'A/C', 'icon' => 'bi-layers', 'color' => 'info'],
                        ['type' => 'iris', 'label' => 'Iris', 'icon' => 'bi-bullseye', 'color' => 'warning'],
                        ['type' => 'pupil', 'label' => 'Pupil', 'icon' => 'bi-dot', 'color' => 'dark'],
                        ['type' => 'lens', 'label' => 'Lens', 'icon' => 'bi-camera-lens', 'color' => 'success'],
                        ['type' => 'em', 'label' => 'E/M', 'icon' => 'bi-arrows-move', 'color' => 'secondary'],
                        ['type' => 'covertest', 'label' => 'Cover Test', 'icon' => 'bi-shield-check', 'color' => 'success'],
                    ];
                @endphp
                @foreach($anteriorMasters as $m)
                    <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                        <a href="{{ route('hospital.masters.detail.index', ['slug' => $slug, 'type' => $m['type']]) }}"
                            class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100 master-nav-card">
                                <div
                                    class="card-body d-flex flex-column align-items-center justify-content-center text-center p-3 gap-2">
                                    <div class="master-icon-box bg-{{ $m['color'] }}-subtle text-{{ $m['color'] }}">
                                        <i class="bi {{ $m['icon'] }} fs-5"></i>
                                    </div>
                                    <span class="fw-semibold small"
                                        style="color: var(--color-primary);">{{ $m['label'] }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            {{-- Posterior Segment --}}
            <p class="text-muted small mb-2 fw-medium masters-subsection-title">Posterior Segment (FUNDUS)</p>
            <div class="row g-3 mb-4">
                @php
                    $posteriorMasters = [
                        ['type' => 'disc', 'label' => 'Disc', 'icon' => 'bi-circle', 'color' => 'secondary'],
                        ['type' => 'fr', 'label' => 'F/R', 'icon' => 'bi-reception-4', 'color' => 'secondary'],
                    ];
                @endphp
                @foreach($posteriorMasters as $m)
                    <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                        <a href="{{ route('hospital.masters.detail.index', ['slug' => $slug, 'type' => $m['type']]) }}"
                            class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100 master-nav-card">
                                <div
                                    class="card-body d-flex flex-column align-items-center justify-content-center text-center p-3 gap-2">
                                    <div class="master-icon-box bg-{{ $m['color'] }}-subtle text-{{ $m['color'] }}">
                                        <i class="bi {{ $m['icon'] }} fs-5"></i>
                                    </div>
                                    <span class="fw-semibold small"
                                        style="color: var(--color-primary);">{{ $m['label'] }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

@endsection

@push('styles')
    <style>
        .masters-premium-page {
            --master-primary: #1B4F72;
            --master-secondary: #2980B9;
            --master-success: #27AE60;
            --master-soft: #ebf5fbeb;
            --master-border: rgba(27, 79, 114, .12);
            --master-border-strong: rgba(27, 79, 114, .2);
            --master-text-soft: rgba(27, 79, 114, .68);
            animation: masters-page-in 420ms ease both;
        }

        .masters-header-block {
            padding: 0 0 1.15rem;
        }

        .masters-header-title {
            font-weight: 800;
            font-size: 1.4rem;
            color: var(--master-primary);
            letter-spacing: -.02em;
            display: flex;
            align-items: center;
            gap: .7rem;
        }

        .masters-header-icon {
            width: 40px;
            height: 40px;
            border-radius: 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: linear-gradient(135deg, var(--master-primary) 0%, #2471a3 100%);
            color: #fff;
            box-shadow: 0 10px 22px rgba(27, 79, 114, .28);
        }

        .masters-header-icon i {
            font-size: 1.05rem;
        }

        .masters-breadcrumb {
            margin-top: .4rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: #8891a0;
        }

        .masters-breadcrumb a {
            color: #8891a0;
            text-decoration: none;
        }

        .masters-breadcrumb a:hover {
            color: var(--master-primary);
        }

        .masters-breadcrumb-sep {
            color: #c3c9d3;
        }

        .masters-breadcrumb-current {
            color: #4a5568;
            font-weight: 600;
        }

        .masters-hero {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 1.2rem 1.25rem;
            margin-bottom: 1.15rem;
            border: 1px solid var(--master-border);
            border-radius: 22px;
            background:
                linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94));
            box-shadow: 0 18px 48px rgba(27, 79, 114, .10);
            overflow: hidden;
            position: relative;
        }

        .masters-hero::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 5px;
            background: var(--master-success);
        }

        .masters-hero-copy {
            min-width: 0;
        }

        .masters-kicker {
            display: inline-flex;
            align-items: center;
            color: var(--master-secondary);
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-bottom: .25rem;
        }

        .masters-hero h2 {
            color: var(--master-primary);
            font-size: 1.35rem;
            font-weight: 900;
            letter-spacing: -.2px;
            margin: 0;
        }

        .masters-hero p {
            color: var(--master-text-soft);
            font-size: .9rem;
            font-weight: 650;
            margin: .2rem 0 0;
        }

        .masters-section {
            border: 1px solid var(--master-border);
            border-radius: 22px;
            background: rgba(255, 255, 255, .86);
            box-shadow: 0 16px 42px rgba(27, 79, 114, .08);
            padding: 1rem;
            overflow: hidden;
            animation: masters-card-rise 520ms cubic-bezier(.2, .9, .2, 1) both;
        }

        .masters-section+hr {
            display: none;
        }

        .masters-section+.masters-section,
        hr+.masters-section {
            margin-top: 1rem;
        }

        .masters-section-title {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            background: var(--master-primary);
            color: #fff !important;
            border-radius: 999px;
            padding: .48rem .78rem;
            margin-bottom: 1rem !important;
            box-shadow: 0 10px 24px rgba(27, 79, 114, .18);
        }

        .masters-subsection-title {
            display: inline-flex;
            align-items: center;
            color: var(--master-primary) !important;
            background: var(--master-soft);
            border: 1px solid var(--master-border);
            border-radius: 999px;
            padding: .35rem .72rem;
            font-weight: 850 !important;
            letter-spacing: .01em;
            margin-top: .35rem;
        }

        .master-nav-card {
            cursor: pointer;
            min-height: 112px;
            border: 1px solid var(--master-border) !important;
            border-radius: 18px !important;
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 10px 24px rgba(27, 79, 114, .07) !important;
            overflow: hidden;
            position: relative;
            transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease, background 180ms ease;
            animation: masters-card-rise 460ms ease both;
        }

        .master-nav-card::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--master-primary), var(--master-success));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 180ms ease;
        }

        .master-nav-card:hover {
            transform: translateY(-4px);
            border-color: var(--master-border-strong) !important;
            background: #fff;
            box-shadow: 0 18px 38px rgba(27, 79, 114, .14) !important;
        }

        .master-nav-card:hover::after {
            transform: scaleX(1);
        }

        .master-nav-card .card-body {
            min-height: 112px;
        }

        .master-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 10px 20px rgba(27, 79, 114, .10);
            transition: transform 220ms cubic-bezier(.34,1.56,.64,1), box-shadow 180ms ease;
        }

        .master-nav-card:hover .master-icon-box {
            transform: translateY(-3px) scale(1.08) rotate(-4deg);
            box-shadow: 0 14px 26px rgba(27, 79, 114, .18);
        }

        /* Richer, more saturated icon colors than Bootstrap's washed *-subtle utilities */
        .master-icon-box.bg-primary-subtle { background: linear-gradient(135deg, rgba(27,79,114,.16), rgba(27,79,114,.24)) !important; }
        .master-icon-box.bg-success-subtle { background: linear-gradient(135deg, rgba(39,174,96,.16), rgba(39,174,96,.26)) !important; }
        .master-icon-box.bg-warning-subtle { background: linear-gradient(135deg, rgba(230,168,15,.18), rgba(230,168,15,.28)) !important; }
        .master-icon-box.bg-secondary-subtle { background: linear-gradient(135deg, rgba(100,116,139,.16), rgba(100,116,139,.26)) !important; }
        .master-icon-box.bg-dark-subtle { background: linear-gradient(135deg, rgba(27,79,114,.14), rgba(13,33,55,.22)) !important; }
        .master-icon-box.bg-info-subtle { background: linear-gradient(135deg, rgba(41,128,185,.16), rgba(41,128,185,.26)) !important; }
        .master-icon-box.bg-danger-subtle { background: linear-gradient(135deg, rgba(192,57,43,.16), rgba(192,57,43,.26)) !important; }

        .master-icon-box.text-primary { color: #1B4F72 !important; }
        .master-icon-box.text-success { color: #1e8449 !important; }
        .master-icon-box.text-warning { color: #b7791f !important; }
        .master-icon-box.text-secondary { color: #475569 !important; }
        .master-icon-box.text-dark { color: #0D2137 !important; }
        .master-icon-box.text-info { color: #21618c !important; }
        .master-icon-box.text-danger { color: #a93226 !important; }

        .master-nav-card span {
            line-height: 1.25;
            font-weight: 850 !important;
            letter-spacing: -.05px;
        }

        @keyframes masters-page-in {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes masters-card-rise {
            from {
                opacity: 0;
                transform: translateY(12px) scale(.99);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .masters-premium-page,
            .masters-section,
            .master-nav-card {
                animation: none;
            }

            .masters-premium-page * {
                transition: none !important;
            }
        }

        @media (max-width: 768px) {
            .masters-hero {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 576px) {
            .masters-section {
                padding: .8rem;
            }

            .master-nav-card {
                min-height: 104px;
            }

            .master-icon-box {
                width: 42px;
                height: 42px;
                border-radius: 14px;
            }
        }
    </style>
@endpush