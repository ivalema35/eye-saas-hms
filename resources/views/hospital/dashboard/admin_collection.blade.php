@extends('hospital.layouts.app')
@section('title', 'Total Collection')
{{-- Layout page-header intentionally unused — heading, breadcrumb and
actions render inside the content card instead, matching the panel design
used across the rest of the app. --}}

@section('content')
    <div class="acoll-page">

        <div class="acoll-outer-card">
            <div class="acoll-header-block">
                <div>
                    <div class="acoll-header-title"><i class="bi bi-cash-stack"></i> Total Collection</div>
                    <nav class="acoll-breadcrumb" aria-label="breadcrumb">
                        <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                        <span class="acoll-breadcrumb-sep">/</span>
                        <span class="acoll-breadcrumb-current">Total Collection</span>
                    </nav>
                </div>
                <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        </div>

        @php
            $bd = $breakdown ?? ['opd' => 0, 'ot_collected' => 0, 'ot_refunded' => 0, 'ot_net' => 0, 'total' => $grandTotal ?? 0];
        @endphp
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card acoll-premium-card border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="acoll-icon"><i class="bi bi-cash-stack"></i></span>
                            <span class="acoll-label">Grand Total</span>
                        </div>
                        <div class="acoll-value">{{ money($grandTotal, 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card acoll-premium-card border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="acoll-icon"><i class="bi bi-clipboard2-pulse"></i></span>
                            <span class="acoll-label">OPD</span>
                        </div>
                        <div class="acoll-value">{{ money((float) $bd['opd'], 0) }}</div>
                        <div class="acoll-hint">Case fees (not cut by OT refund)</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card acoll-premium-card border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="acoll-icon"><i class="bi bi-hospital"></i></span>
                            <span class="acoll-label">OT Net</span>
                        </div>
                        <div class="acoll-value">{{ money((float) $bd['ot_net'], 0) }}</div>
                        <div class="acoll-hint">
                            {{ money((float) $bd['ot_collected'], 0) }} paid
                            − {{ money((float) $bd['ot_refunded'], 0) }} refund
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card acoll-premium-card border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="acoll-icon"><i class="bi bi-funnel-fill"></i></span>
                            <span class="acoll-label">Date Range</span>
                        </div>
                        <form method="GET" action="{{ route('hospital.dashboard.collection', ['slug' => $slug]) }}">
                            <div class="d-flex flex-wrap gap-2 align-items-end">
                                <div style="min-width:160px;flex:1">
                                    <label class="form-label acoll-form-label" for="date_range">Dates</label>
                                    <input type="text" id="date_range" class="form-control clinical-input"
                                        data-hms-date-range data-start-name="start_date" data-end-name="end_date"
                                        data-start-value="{{ $startDate }}" data-end-value="{{ $endDate }}"
                                        data-auto-submit="1" placeholder="Select start → end date" autocomplete="off"
                                        readonly>
                                </div>
                                <a href="{{ route('hospital.dashboard.collection', ['slug' => $slug]) }}"
                                    class="btn acoll-btn-outline">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card acoll-premium-card border-0">
            <div class="acoll-card-header">
                <div class="acoll-title-wrap">
                    <span class="acoll-title-icon" aria-hidden="true"><i class="bi bi-people-fill"
                            style="font-size: 1.1rem;"></i></span>
                    <h5 class="acoll-title mb-0">Reception-wise OPD Collection</h5>
                </div>
                <span class="badge acoll-count-badge">OPD case fees only · OT is in grand total above</span>
            </div>
            <div class="card-body p-0">
                <div class="acoll-table-wrap">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 acoll-table">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-person-lines-fill me-1"></i>Reception</th>
                                    <th class="text-end"><i class="bi bi-people me-1"></i>Patients</th>
                                    <th class="text-end"><i class="bi bi-cash-coin me-1"></i>Collection</th>
                                    <th class="acoll-detail-col">Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $row)
                                                            <tr class="acoll-row" onclick="window.location='{{ route('hospital.dashboard.collection.show', [
                                        'slug' => $slug,
                                        'reception' => $row->id,
                                        'start_date' => $startDate,
                                        'end_date' => $endDate,
                                    ]) }}'" style="cursor:pointer;">
                                                                <td class="fw-semibold">{{ $row->name }}</td>
                                                                <td class="text-end">{{ $row->count }}</td>
                                                                <td class="text-end acoll-amount">{{ money($row->total, 0) }}</td>
                                                                <td class="acoll-detail-col"><i class="bi bi-chevron-right"></i></td>
                                                            </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center acoll-empty">
                                            <i class="bi bi-inbox me-1"></i> No collection for selected dates.
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
                          Total Collection (Design refresh)
                          Keep Blade/dynamic logic untouched; CSS-only + layout wrappers.
                          Palette follows hospital shell theme (#1B4F72 / #ebf5fbeb).
                        */

        .acoll-page {
            --ot-secondary: #1B4F72;
            --ot-s2-06: rgba(27, 79, 114, 0.06);
            --ot-s2-08: rgba(27, 79, 114, 0.08);
            --ot-s2-10: rgba(27, 79, 114, 0.10);
            --ot-s2-12: rgba(27, 79, 114, 0.12);
            --ot-s2-18: rgba(27, 79, 114, 0.18);
            --ot-s2-24: rgba(27, 79, 114, 0.24);

            position: relative;
            padding: .25rem 0 1.5rem;
            color: var(--ot-secondary);
            animation: acoll-page-in 420ms ease both;
        }

        @keyframes acoll-page-in {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .acoll-outer-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.12);
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(15, 79, 134, 0.08);
            padding: 1.1rem 1.5rem;
            margin-bottom: 1.25rem;
        }

        .acoll-header-block {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .75rem;
        }

        .acoll-header-title {
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--ot-secondary);
            letter-spacing: -.015em;
            display: flex;
            align-items: center;
            gap: .55rem;
        }

        .acoll-header-title i {
            color: var(--ot-secondary);
            font-size: 1.2rem;
        }

        .acoll-breadcrumb {
            margin-top: .4rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: #8891a0;
        }

        .acoll-breadcrumb a {
            color: #8891a0;
            text-decoration: none;
        }

        .acoll-breadcrumb a:hover {
            color: var(--ot-secondary);
        }

        .acoll-breadcrumb-sep {
            color: #c3c9d3;
        }

        .acoll-breadcrumb-current {
            color: #4a5568;
            font-weight: 600;
        }

        .acoll-premium-card {
            background: #ffffff;
            border: 3px solid rgba(15, 79, 134, 0.08) !important;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(15, 79, 134, 0.05);
            overflow: hidden;
        }

        .acoll-icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--ot-s2-10);
            color: var(--ot-secondary);
            font-size: 1rem;
            flex: 0 0 auto;
        }

        .acoll-label {
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--ot-secondary);
        }

        .acoll-value {
            font-size: 1.9rem;
            font-weight: 900;
            color: var(--ot-secondary);
            letter-spacing: -.5px;
            margin: .25rem 0;
        }

        .acoll-hint {
            font-size: .78rem;
            color: rgba(27, 79, 114, .68);
            font-weight: 650;
        }

        .acoll-form-label {
            font-size: .78rem;
            font-weight: 700;
            color: rgba(27, 79, 114, .8);
            margin-bottom: .25rem;
        }

        .acoll-btn-outline {
            border: 1.5px solid var(--ot-s2-24);
            color: var(--ot-secondary);
            font-weight: 700;
            border-radius: 10px;
            background: #fff;
            transition: background 170ms ease, border-color 170ms ease;
        }

        .acoll-btn-outline:hover {
            background: var(--ot-s2-06);
            color: var(--ot-secondary);
            border-color: var(--ot-secondary);
        }

        .acoll-card-header {
            background: #1B4F72;
            border-bottom: 1px solid var(--ot-s2-12);
            padding: 1.1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .acoll-title-wrap {
            display: flex;
            align-items: center;
            gap: .85rem;
        }

        .acoll-title-icon {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            background: #ffffff;
            color: #1B4F72;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 14px 30px rgba(27, 79, 114, 0.22);
            flex: 0 0 auto;
        }

        .acoll-title {
            font-weight: 900;
            letter-spacing: -0.2px;
            color: #ffffff;
        }

        .acoll-count-badge {
            background: rgba(255, 255, 255, .78);
            color: var(--ot-secondary);
            border: 1px solid var(--ot-s2-12);
            border-radius: 999px;
            padding: .5rem .8rem;
            font-weight: 800;
            white-space: nowrap;
            box-shadow: 0 10px 22px rgba(27, 79, 114, .08);
        }

        .acoll-table-wrap {
            overflow-x: auto;
        }

        .acoll-table {
            margin-bottom: 0;
            border-collapse: collapse;
            width: 100%;
            min-width: 560px;
        }

        .acoll-table thead th {
            background: #F8FAFC !important;
            color: #4A5568 !important;
            border: 0;
            border-bottom: 1px solid #E2E8F0 !important;
            font-size: .75rem;
            letter-spacing: .05em;
            font-weight: 700;
            text-transform: uppercase;
            padding: .7rem 1rem;
            white-space: nowrap;
            text-align: left;
        }

        .acoll-table thead th.acoll-detail-col,
        .acoll-table tbody td.acoll-detail-col {
            text-align: end;
        }

        .acoll-table tbody td {
            background: transparent;
            border: 0;
            border-bottom: 1px solid var(--ot-s2-12);
            padding: .75rem 1rem;
            font-weight: 600;
            color: rgba(27, 79, 114, 0.90);
            vertical-align: middle;
            white-space: nowrap;
        }

        .acoll-table tbody td:first-child {
            color: rgba(27, 79, 114, 0.82);
            font-weight: 700;
        }

        .acoll-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .acoll-table tbody td.acoll-amount {
            font-weight: 900;
            color: var(--ot-secondary);
        }

        .acoll-row:hover td {
            background: #F5F8FC;
        }

        .acoll-empty {
            padding: 2.25rem 1rem !important;
            color: rgba(27, 79, 114, 0.72) !important;
            font-weight: 800;
            white-space: normal !important;
        }

        @media (prefers-reduced-motion: reduce) {
            .acoll-page {
                animation: none !important;
            }
        }
    </style>
@endpush