@extends('hospital.layouts.app')
@section('title', 'OT Reports')
@section('page-header', 'OT Reports')

@section('page-actions')
    <a href="{{ route('hospital.reports.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="bi bi-clipboard2-pulse me-1"></i> OPD Patient Reports
    </a>
@endsection

@section('content')
<div class="ot-reports-page">
    @foreach($reportsByGroup as $group => $reports)
        <div class="card ot-premium-card border-0 mb-4">
            <div class="ot-card-header">
                <div class="ot-title-wrap">
                    <span class="ot-title-icon" aria-hidden="true"><i class="bi bi-folder2-open" style="font-size: 1.1rem;"></i></span>
                    <h5 class="ot-title mb-0">{{ $group }}</h5>
                </div>
            </div>
            <div class="card-body">
                <div class="ot-report-grid">
                    @foreach($reports as $report)
                        <a href="{{ route('hospital.reports.ot.show', ['slug' => $slug, 'type' => $report['key']]) }}" class="ot-report-tile">
                            <span class="ot-report-tile-icon"><i class="bi bi-file-earmark-bar-graph"></i></span>
                            <span class="ot-report-tile-label">{{ $report['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection

@push('styles')
<style>
    /*
      OT Reports (Design refresh)
      Keep Blade/dynamic logic untouched; CSS-only + layout wrappers.
      Palette follows hospital shell theme (#1B4F72).
    */

    .ot-reports-page {
        --ot-secondary: #1B4F72;
        --ot-s2-06: rgba(27, 79, 114, 0.06);
        --ot-s2-08: rgba(27, 79, 114, 0.08);
        --ot-s2-12: rgba(27, 79, 114, 0.12);
        --ot-s2-18: rgba(27, 79, 114, 0.18);
        --ot-s2-24: rgba(27, 79, 114, 0.24);

        position: relative;
        padding: .25rem 0 1.25rem;
        color: var(--ot-secondary);
        animation: ot-page-in 420ms ease both;
    }

    @keyframes ot-page-in {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .ot-reports-page .hms-btn {
        border-radius: 12px;
        font-weight: 800;
        transition: transform 170ms ease, box-shadow 170ms ease;
    }

    .ot-reports-page .hms-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 26px rgba(27, 79, 114, 0.14);
    }

    .ot-premium-card {
        background: rgba(255, 255, 255, 0.84);
        border: 1px solid var(--ot-s2-12) !important;
        border-radius: 22px;
        box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
        overflow: hidden;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        animation: ot-card-rise 520ms cubic-bezier(.2,.9,.2,1) both;
    }

    @keyframes ot-card-rise {
        from { opacity: 0; transform: translateY(10px) scale(0.99); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .ot-card-header {
        background:
            linear-gradient(135deg, rgba(235, 245, 251, 0.92), rgba(255, 255, 255, 0.94)),
            #ffffff;
        border-bottom: 1px solid var(--ot-s2-12);
        padding: 1.1rem 1.25rem;
    }

    .ot-title-wrap {
        display: flex;
        align-items: center;
        gap: .85rem;
    }

    .ot-title-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: var(--ot-secondary);
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 14px 30px rgba(27, 79, 114, 0.22);
        flex: 0 0 auto;
    }

    .ot-title {
        font-weight: 900;
        letter-spacing: -0.2px;
        color: var(--ot-secondary);
    }

    .ot-report-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1.1rem;
    }

    .ot-report-tile {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .9rem;
        min-height: 148px;
        border: 1px solid rgba(27, 79, 114, 0.08);
        border-radius: 20px;
        padding: 1.5rem 1rem;
        text-decoration: none;
        color: var(--ot-secondary);
        font-weight: 800;
        font-size: .92rem;
        text-align: center;
        background: #fff;
        box-shadow: 0 6px 16px rgba(27, 79, 114, 0.05);
        transition: transform 170ms ease, box-shadow 170ms ease, border-color 170ms ease;
    }

    .ot-report-tile-icon {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--ot-s2-08);
        color: var(--ot-secondary);
        font-size: 1.4rem;
    }

    /* Cycle a soft pastel palette across tiles, tied to the theme hue family */
    .ot-report-grid .ot-report-tile:nth-of-type(4n+1) .ot-report-tile-icon {
        background: rgba(41, 128, 185, 0.12);
        color: #2980B9;
    }
    .ot-report-grid .ot-report-tile:nth-of-type(4n+2) .ot-report-tile-icon {
        background: rgba(30, 142, 90, 0.12);
        color: #1E8E5A;
    }
    .ot-report-grid .ot-report-tile:nth-of-type(4n+3) .ot-report-tile-icon {
        background: rgba(224, 168, 0, 0.16);
        color: #B8860B;
    }
    .ot-report-grid .ot-report-tile:nth-of-type(4n+4) .ot-report-tile-icon {
        background: rgba(27, 79, 114, 0.10);
        color: var(--ot-secondary);
    }

    .ot-report-tile-label {
        line-height: 1.3;
        color: var(--ot-secondary);
    }

    .ot-report-tile:hover,
    .ot-report-tile:focus {
        text-decoration: none;
        transform: translateY(-4px);
        box-shadow: 0 18px 34px rgba(27, 79, 114, 0.14);
        border-color: rgba(27, 79, 114, 0.18);
    }

    @media (prefers-reduced-motion: reduce) {
        .ot-reports-page,
        .ot-premium-card,
        .ot-report-tile,
        .ot-reports-page .hms-btn {
            animation: none !important;
            transition: none !important;
        }
    }
</style>
@endpush
