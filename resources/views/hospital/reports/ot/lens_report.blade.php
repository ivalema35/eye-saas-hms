@extends('hospital.layouts.app')
@section('title', 'Lens Report')
@section('page-header')
    <div class="ot-detail-page-heading">
        <span class="ot-detail-page-icon"><i class="bi bi-eyeglasses"></i></span>
        <span>
            <span class="ot-detail-page-title">Lens Report</span>
            <span class="ot-detail-breadcrumb">
                <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                <i class="bi bi-chevron-right"></i>
                <a href="{{ route('hospital.reports.ot.index', ['slug' => $slug]) }}">Reports</a>
                <i class="bi bi-chevron-right"></i>
                <span>OT Reports</span>
                <i class="bi bi-chevron-right"></i>
                <strong>Lens Report</strong>
            </span>
        </span>
    </div>
@endsection

@section('page-actions')
    <a href="{{ route('hospital.reports.ot.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="bi bi-arrow-left me-1"></i> Back to OT Reports
    </a>
@endsection

@section('content')
<div class="ot-reports-page ot-report-detail-page">

    {{-- Date filter --}}
    <div class="card ot-premium-card border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('hospital.reports.ot.lens-report', ['slug' => $slug]) }}"
                class="row g-3 align-items-end">
                <div class="col-auto">
                    <label class="form-label ot-filter-label">Start Date</label>
                    <input type="date" name="from" class="form-control" value="{{ $from }}" max="{{ $to }}">
                </div>
                <div class="col-auto">
                    <label class="form-label ot-filter-label">End Date</label>
                    <input type="date" name="to" class="form-control" value="{{ $to }}" min="{{ $from }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="hms-btn hms-btn-primary">
                        <i class="bi bi-funnel me-1"></i> Apply Filter
                    </button>
                </div>
                <div class="col-auto">
                    <a href="{{ route('hospital.reports.ot.lens-report', ['slug' => $slug]) }}"
                        class="hms-btn hms-btn-outline">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>
                </div>
                <div class="col-auto ms-auto">
                    @hasanypermission('report_export|ot_reports_export')
                    <a href="{{ route('hospital.reports.ot.lens-report.export', ['slug' => $slug, 'from' => $from, 'to' => $to]) }}"
                        class="btn btn-sm ot-export-btn ot-export-excel">
                        <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                    </a>
                    @endhasanypermission
                </div>
            </form>
        </div>
    </div>

    {{-- Lens Power Breakdown --}}
    <div class="card ot-premium-card border-0">
        <div class="ot-card-header">
            <div class="ot-title-wrap">
                <span class="ot-title-icon" aria-hidden="true"><i class="bi bi-bar-chart-steps"
                        style="font-size: 1.1rem;"></i></span>
                <h5 class="ot-title mb-0">Lens Power Breakdown</h5>
            </div>
            <span class="badge ot-total-pill"><i class="bi bi-collection me-1"></i>{{ count($rows) }} rows</span>
        </div>
        <div class="card-body p-0">
            <div class="ot-table-wrap">
                <div class="table-responsive">
                    <table class="table ot-premium-table align-middle mb-0">
                        <thead>
                            <tr>
                                @foreach($headings as $heading)
                                    <th>{{ $heading }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    @foreach($row as $cell)
                                        <td>{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($headings) }}" class="text-center ot-empty">
                                        <i class="bi bi-inbox me-1"></i> No lens usage for the selected range.
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
          Lens Report — reuses the OT Reports theme (#1B4F72) and card chrome
          (.ot-premium-card / .ot-premium-table / .ot-card-header / .ot-title-*),
          same tokens as resources/views/hospital/reports/ot/show.blade.php.
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
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hms-main:has(.ot-report-detail-page) {
            background: linear-gradient(180deg, #f8fbfd 0%, #ffffff 34%);
        }

        .hms-main:has(.ot-report-detail-page) .hms-page-header {
            align-items: center;
            margin-bottom: 1.35rem;
            padding: 1.05rem 1.1rem;
            border: 1px solid rgba(27, 79, 114, .1);
            border-radius: 18px;
            background: rgba(255, 255, 255, .82);
            box-shadow: 0 12px 28px rgba(27, 79, 114, .06) !important;
        }

        .hms-main:has(.ot-report-detail-page) .hms-page-header h1 {
            margin: 0 !important;
            flex: 1 1 auto;
        }

        .ot-detail-page-heading {
            display: flex;
            align-items: center;
            gap: .85rem;
        }

        .ot-detail-page-icon {
            width: 46px;
            height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            color: #fff;
            background: linear-gradient(135deg, #1B4F72, #2471a3);
            box-shadow: 0 10px 22px rgba(27, 79, 114, .2);
            font-size: 1.15rem;
        }

        .ot-detail-page-title {
            display: block;
            color: #0D2137;
            font-size: clamp(1.2rem, 2vw, 1.55rem);
            font-weight: 900;
            line-height: 1.15;
            letter-spacing: -.025em;
        }

        .ot-detail-breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: .42rem;
            margin-top: .32rem;
            color: rgba(27, 79, 114, .58);
            font-size: .72rem;
            font-weight: 700;
        }

        .ot-detail-breadcrumb a {
            color: rgba(27, 79, 114, .62);
            text-decoration: none;
        }

        .ot-detail-breadcrumb a:hover,
        .ot-detail-breadcrumb strong {
            color: #1B4F72;
        }

        .ot-detail-breadcrumb i {
            color: rgba(27, 79, 114, .32);
            font-size: .58rem;
        }

        .ot-report-detail-page>.ot-premium-card:first-child {
            position: relative;
            background: linear-gradient(135deg, #ffffff 0%, #f4f9fc 100%);
            /* border-top: 4px solid #1B4F72 !important; */
        }

        .ot-report-detail-page>.ot-premium-card:first-child .card-body {
            padding: 1.3rem 1.4rem;
        }

        .ot-report-detail-page .form-control {
            min-height: 44px;
            border: 1px solid rgba(27, 79, 114, .2);
            border-radius: 12px;
            color: #1B4F72;
            font-weight: 700;
            box-shadow: 0 5px 14px rgba(27, 79, 114, .05);
        }

        .ot-report-detail-page .form-control:focus {
            border-color: #1B4F72;
            box-shadow: 0 0 0 .2rem rgba(27, 79, 114, .12);
        }

        @media (max-width: 640px) {
            .hms-main:has(.ot-report-detail-page) .hms-page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .hms-main:has(.ot-report-detail-page) .hms-page-actions,
            .hms-main:has(.ot-report-detail-page) .hms-page-actions>* {
                width: 100%;
            }

            .hms-main:has(.ot-report-detail-page) .hms-page-actions>* {
                justify-content: center;
            }
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