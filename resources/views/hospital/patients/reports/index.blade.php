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
        <h3 class="text-success fw-bold mb-0">{{ money($totalCollection, 2) }}</h3>
    </div>
</div>

@php
    $channelCards = [
        'ot_appointment' => ['label' => 'OT Appointment', 'icon' => 'bi-calendar2-week'],
        'walkin' => ['label' => 'Walk-in', 'icon' => 'bi-person-walking'],
        'phone' => ['label' => 'Phone Appointment', 'icon' => 'bi-telephone-fill'],
    ];
@endphp
<div class="card ot-premium-card border-0 mb-4">
    <div class="card-body">
        <div class="ot-report-grid">
            @foreach($channelCards as $channelKey => $card)
                <a href="{{ route('hospital.reports.channel.show', ['slug' => $slug, 'channel' => $channelKey]) }}" class="ot-report-tile">
                    <span class="ot-report-tile-icon"><i class="bi {{ $card['icon'] }}"></i></span>
                    <span class="ot-report-tile-label">{{ $card['label'] }}</span>
                    <span class="ot-report-tile-count">{{ $channelCounts[$channelKey] ?? 0 }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    /* Channel cards — same tile style as the OT Reports page (hospital/reports/ot/index.blade.php) */
    .ot-premium-card {
        background: rgba(255, 255, 255, 0.84);
        border: 1px solid rgba(27, 79, 114, 0.12) !important;
        border-radius: 22px;
        box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
        overflow: hidden;
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
        gap: .6rem;
        min-height: 148px;
        border: 1px solid rgba(27, 79, 114, 0.08);
        border-radius: 20px;
        padding: 1.5rem 1rem;
        text-decoration: none;
        color: #1B4F72;
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
        background: rgba(27, 79, 114, 0.08);
        color: #1B4F72;
        font-size: 1.4rem;
    }

    .ot-report-grid .ot-report-tile:nth-of-type(3n+1) .ot-report-tile-icon {
        background: rgba(125, 60, 152, 0.12);
        color: #7D3C98;
    }
    .ot-report-grid .ot-report-tile:nth-of-type(3n+2) .ot-report-tile-icon {
        background: rgba(27, 79, 114, 0.10);
        color: #1B4F72;
    }
    .ot-report-grid .ot-report-tile:nth-of-type(3n+3) .ot-report-tile-icon {
        background: rgba(17, 122, 101, 0.12);
        color: #117A65;
    }

    .ot-report-tile-label {
        line-height: 1.3;
    }

    .ot-report-tile-count {
        font-size: 1.3rem;
        font-weight: 900;
    }

    .ot-report-tile:hover,
    .ot-report-tile:focus {
        text-decoration: none;
        transform: translateY(-4px);
        box-shadow: 0 18px 34px rgba(27, 79, 114, 0.14);
        border-color: rgba(27, 79, 114, 0.18);
        color: #1B4F72;
    }

    .ot-report-tile-active {
        border-color: #1B4F72;
        border-width: 2px;
        background: rgba(27, 79, 114, 0.04);
    }
</style>
@endpush

