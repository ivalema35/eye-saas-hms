@extends('hospital.layouts.app')
@section('title', $label)
@section('page-header', $label)

@section('page-actions')
    <a href="{{ route('hospital.reports.ot.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="bi bi-arrow-left me-1"></i> Back to OT Reports
    </a>
@endsection

@section('content')
<div class="ot-reports-page">
    <div class="card ot-premium-card border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('hospital.reports.ot.show', ['slug' => $slug, 'type' => $type]) }}" class="row g-3 align-items-end">
                <div class="col-auto">
                    <label class="form-label ot-filter-label">Date range</label>
                    <input type="text" class="form-control clinical-input"
                        data-hms-date-range
                        data-start-name="from"
                        data-end-name="to"
                        data-start-value="{{ $from }}"
                        data-end-value="{{ $to }}"
                        data-auto-submit="1"
                        placeholder="Select start → end date"
                        autocomplete="off"
                        readonly
                        style="min-width:220px;">
                </div>
                <div class="col-auto ms-auto d-flex gap-2">
                    <a href="{{ route('hospital.reports.ot.export', ['slug' => $slug, 'type' => $type, 'from' => $from, 'to' => $to]) }}" class="btn btn-sm ot-export-btn ot-export-excel">
                        <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                    </a>
                    <a href="{{ route('hospital.reports.ot.export.pdf', ['slug' => $slug, 'type' => $type, 'from' => $from, 'to' => $to]) }}" class="btn btn-sm ot-export-btn ot-export-pdf">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card ot-premium-card border-0">
        <div class="ot-card-header">
            <div class="ot-title-wrap">
                <span class="ot-title-icon" aria-hidden="true"><i class="bi bi-clipboard2-data" style="font-size: 1.1rem;"></i></span>
                <h5 class="ot-title mb-0">{{ $label }}</h5>
            </div>
            <span class="badge ot-total-pill"><i class="bi bi-collection me-1"></i>{{ count($rows) }} rows</span>
        </div>
        <div class="card-body p-0">
            <div class="ot-table-wrap">
                <div class="table-responsive">
                    <table class="table ot-premium-table align-middle mb-0">
                        <thead>
                            <tr>
                                @foreach($headings as $i => $heading)
                                    <th>{{ $type === 'hospital-report' && $i === count($headings) - 1 ? 'Prescription' : $heading }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    @if($type === 'hospital-report')
                                        @php
                                            $rowCells = $row;
                                            $patientId = array_pop($rowCells);
                                            $hasExam = ($rowCells[6] ?? '-') !== '-' || ($rowCells[7] ?? '-') !== '-';
                                        @endphp
                                        @foreach($rowCells as $cell)
                                            <td>{{ $cell }}</td>
                                        @endforeach
                                        <td>
                                            @if($hasExam)
                                                <a href="{{ route('hospital.reports.ot.prescription.pdf', ['slug' => $slug, 'patient' => $patientId, 'from' => $from, 'to' => $to]) }}"
                                                    class="btn btn-sm ot-icon-btn ot-icon-btn-view"
                                                    target="_blank"
                                                    rel="noopener"
                                                    title="View">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    @else
                                        @foreach($row as $cell)
                                            <td>{{ $cell }}</td>
                                        @endforeach
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($headings) }}" class="text-center ot-empty">
                                        <i class="bi bi-inbox me-1"></i> No data for the selected range.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /*
      OT Reports — generic report view (Design refresh)
      Keep Blade/dynamic logic untouched; CSS-only + layout wrappers.
      Palette follows hospital shell theme (#1B4F72 / #ebf5fbeb).
    */

    .ot-reports-page {
        --ot-primary: #ebf5fbeb;
        --ot-secondary: #1B4F72;
        --ot-s2-06: rgba(27, 79, 114, 0.06);
        --ot-s2-08: rgba(27, 79, 114, 0.08);
        --ot-s2-10: rgba(27, 79, 114, 0.10);
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
    }

    .ot-filter-label {
        color: var(--ot-secondary);
        font-weight: 600;
        font-size: .85rem;
    }

    .ot-export-btn {
        border-radius: 10px;
        font-weight: 700;
        border: 1px solid transparent;
        transition: transform 170ms ease, box-shadow 170ms ease;
    }

    .ot-export-btn:hover {
        transform: translateY(-1px);
    }

    .ot-export-excel {
        background: rgba(30, 142, 90, 0.10);
        border-color: rgba(30, 142, 90, 0.30);
        color: #1E8E5A;
    }

    .ot-export-excel:hover {
        background: #1E8E5A;
        border-color: #1E8E5A;
        color: #fff;
        box-shadow: 0 10px 22px rgba(30, 142, 90, 0.22);
    }

    .ot-export-pdf {
        background: rgba(192, 57, 43, 0.10);
        border-color: rgba(192, 57, 43, 0.30);
        color: #C0392B;
    }

    .ot-export-pdf:hover {
        background: #C0392B;
        border-color: #C0392B;
        color: #fff;
        box-shadow: 0 10px 22px rgba(192, 57, 43, 0.22);
    }

    .ot-total-pill {
        background: rgba(255, 255, 255, .78);
        color: var(--ot-secondary);
        border: 1px solid var(--ot-s2-12);
        border-radius: 999px;
        padding: .55rem .85rem;
        font-weight: 900;
        white-space: nowrap;
        box-shadow: 0 10px 22px rgba(27, 79, 114, .08);
    }

    .ot-card-header {
        background:
            linear-gradient(135deg, rgba(235, 245, 251, 0.92), rgba(255, 255, 255, 0.94)),
            #ffffff;
        border-bottom: 1px solid var(--ot-s2-12);
        padding: 1.1rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
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

    .ot-table-wrap {
        padding: .9rem !important;
        overflow-x: auto;
    }

    .ot-premium-table {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0 8px;
        min-width: 720px;
    }

    .ot-premium-table thead,
    .ot-premium-table thead th {
        background: var(--ot-secondary) !important;
    }

    .ot-premium-table thead th {
        color: #ffffff !important;
        border-bottom: none !important;
        font-size: .72rem;
        letter-spacing: .08em;
        font-weight: 900;
        text-transform: uppercase;
        padding-top: .9rem;
        padding-bottom: .9rem;
        white-space: nowrap;
    }

    .ot-premium-table thead th:first-child {
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .ot-premium-table thead th:last-child {
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .ot-premium-table tbody td {
        background: rgba(255, 255, 255, 0.88);
        border-top: 1px solid var(--ot-s2-12);
        border-bottom: 1px solid var(--ot-s2-12);
        padding: .95rem .95rem;
        font-weight: 750;
        color: rgba(27, 79, 114, 0.90);
        vertical-align: middle;
        white-space: nowrap;
    }

    .ot-premium-table tbody td:first-child {
        border-left: 1px solid var(--ot-s2-12);
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
        color: rgba(27, 79, 114, 0.82);
        font-weight: 900;
    }

    .ot-premium-table tbody td:last-child {
        border-right: 1px solid var(--ot-s2-12);
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .ot-premium-table tbody tr:hover td {
        background: rgba(235, 245, 251, 0.78);
        border-color: var(--ot-s2-18);
    }

    .ot-icon-btn {
        border-radius: 10px;
        border: 1px solid var(--ot-s2-18);
        background: rgba(27, 79, 114, 0.06);
        color: var(--ot-secondary);
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .ot-icon-btn-view:hover {
        background: var(--ot-secondary);
        border-color: var(--ot-secondary);
        color: #fff;
    }

    .ot-empty {
        padding: 2.25rem 1rem !important;
        color: rgba(27, 79, 114, 0.72) !important;
        font-weight: 800;
        white-space: normal !important;
    }

    @media (prefers-reduced-motion: reduce) {
        .ot-reports-page,
        .ot-reports-page .hms-btn {
            animation: none !important;
            transition: none !important;
        }
    }
</style>
@endpush
