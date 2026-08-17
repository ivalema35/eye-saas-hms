@extends('hospital.layouts.app')
@section('title', 'Total Collection')
@section('page-header', 'Total Collection')

@section('page-actions')
    <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
@endsection

@section('content')
    <div class="acoll-page">
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

        .acoll-premium-card {
            background: rgba(255, 255, 255, 0.84);
            border: 1px solid var(--ot-s2-12) !important;
            border-radius: 22px;
            box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
            overflow: hidden;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
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

        .acoll-title-wrap {
            display: flex;
            align-items: center;
            gap: .85rem;
        }

        .acoll-title-icon {
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

        .acoll-title {
            font-weight: 900;
            letter-spacing: -0.2px;
            color: var(--ot-secondary);
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
            padding: .9rem !important;
            overflow-x: auto;
        }

        .acoll-table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0 8px;
            min-width: 560px;
        }

        .acoll-table thead th {
            background: var(--ot-secondary) !important;
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

        .acoll-table thead th:first-child {
            border-top-left-radius: 14px;
            border-bottom-left-radius: 14px;
        }

        .acoll-table thead th:last-child {
            border-top-right-radius: 14px;
            border-bottom-right-radius: 14px;
        }

        .acoll-table thead th.acoll-detail-col,
        .acoll-table tbody td.acoll-detail-col {
            text-align: end;
        }

        .acoll-table tbody td {
            background: rgba(255, 255, 255, 0.88);
            border-top: 1px solid var(--ot-s2-12);
            border-bottom: 1px solid var(--ot-s2-12);
            padding: .95rem .95rem;
            font-weight: 750;
            color: rgba(27, 79, 114, 0.90);
            vertical-align: middle;
            white-space: nowrap;
        }

        .acoll-table tbody td:first-child {
            border-left: 1px solid var(--ot-s2-12);
            border-top-left-radius: 14px;
            border-bottom-left-radius: 14px;
            color: rgba(27, 79, 114, 0.82);
            font-weight: 900;
        }

        .acoll-table tbody td:last-child {
            border-right: 1px solid var(--ot-s2-12);
            border-top-right-radius: 14px;
            border-bottom-right-radius: 14px;
            color: var(--ot-s2-24);
        }

        .acoll-table tbody td.acoll-amount {
            font-weight: 900;
            color: var(--ot-secondary);
        }

        .acoll-row:hover td {
            background: rgba(235, 245, 251, 0.78);
            border-color: var(--ot-s2-18);
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