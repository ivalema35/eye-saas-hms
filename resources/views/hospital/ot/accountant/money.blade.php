@extends('hospital.layouts.app')
@section('title', 'OT Money — Collected vs Refunded')

@section('content')
    <div class="ot-money-page">
        <div class="otm-outer-card">
            <div class="otm-header-block">
                <div class="otm-header-title">
                    <span class="otm-header-icon"><i class="bi bi-graph-up-arrow"></i></span>
                    <div>
                        <h5 class="otm-header-heading">OT Money Report</h5>
                        <p class="otm-header-sub">Track OT collections and refunds for the selected period.</p>
                    </div>
                </div>
                <nav class="otm-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                    <span class="otm-breadcrumb-sep">/</span>
                    <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug]) }}">Accountant</a>
                    <span class="otm-breadcrumb-sep">/</span>
                    <span class="otm-breadcrumb-current">Money Report</span>
                </nav>
            </div>

            <div class="otm-body">
                <div class="otm-toolbar-row">
                    <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug]) }}"
                        class="hms-btn hms-btn-outline otm-back-btn">
                        <i class="bi bi-arrow-left me-1"></i> Back to Accountant
                    </a>
                    <form method="GET" action="{{ route('hospital.ot.accountant.money', ['slug' => $slug]) }}"
                        class="ot-money-filter">
                        <div class="ot-money-date-range">
                            <label>Date range</label>
                            <input id="money-date-range" type="text" class="ot-money-date-control" data-hms-date-range
                                data-start-name="start_date" data-end-name="end_date" data-start-value="{{ $startDate }}"
                                data-end-value="{{ $endDate }}" data-auto-submit="1" placeholder="Select start → end date"
                                autocomplete="off" readonly aria-label="Select date range">
                        </div>
                        <button type="submit" class="hms-btn hms-btn-primary"><i class="bi bi-funnel-fill"></i> Apply</button>
                    </form>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="ot-money-stat ot-money-stat-collected">
                            <span class="ot-money-stat-icon"><i class="bi bi-arrow-down-left"></i></span>
                            <div><span class="ot-money-stat-label">Collected</span><strong>{{ money_code($collected, 2) }}</strong>
                            </div>
                            <span class="ot-money-stat-mark">IN</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="ot-money-stat ot-money-stat-refunded">
                            <span class="ot-money-stat-icon"><i class="bi bi-arrow-up-right"></i></span>
                            <div><span class="ot-money-stat-label">Returned /
                                    Refunds</span><strong>{{ money_code($refunded, 2) }}</strong></div>
                            <span class="ot-money-stat-mark">OUT</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="ot-money-stat ot-money-stat-net">
                            <span class="ot-money-stat-icon"><i class="bi bi-wallet2"></i></span>
                            <div><span class="ot-money-stat-label">Net balance</span><strong>{{ money_code($net, 2) }}</strong>
                            </div>
                            <span class="ot-money-stat-mark">NET</span>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="ot-money-panel">
                            <div class="ot-money-panel-header"><span class="ot-money-panel-title"><i
                                        class="bi bi-check2-circle"></i><span>Payments in range</span></span>
                                <div class="ot-money-panel-actions"><small>Collections</small><a
                                        href="{{ route('hospital.ot.accountant.money.export', ['slug' => $slug, 'start_date' => $startDate, 'end_date' => $endDate, 'section' => 'payments']) }}"
                                        class="ot-money-export"><i class="bi bi-download"></i> Export</a></div>
                            </div>
                            <div class="table-responsive">
                                <table class="table ot-money-table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Patient</th>
                                            <th class="text-end">Amount</th>
                                            <th>Mode</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($paymentRows as $row)
                                            <tr>
                                                <td>{{ optional($row->paid_at)->format('d M Y H:i') ?? '-' }}</td>
                                                <td>{{ $row->booking?->patient?->full_name ?? '-' }}</td>
                                                <td class="text-end">{{ money_code((float) $row->package_amount, 2) }}</td>
                                                <td class="text-uppercase">{{ $row->payment_mode }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3 ot-money-empty-cell">No payments</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="ot-money-panel">
                            <div class="ot-money-panel-header"><span class="ot-money-panel-title"><i
                                        class="bi bi-arrow-counterclockwise"></i><span>Refunds in range</span></span>
                                <div class="ot-money-panel-actions"><small>Returns</small><a
                                        href="{{ route('hospital.ot.accountant.money.export', ['slug' => $slug, 'start_date' => $startDate, 'end_date' => $endDate, 'section' => 'refunds']) }}"
                                        class="ot-money-export"><i class="bi bi-download"></i> Export</a></div>
                            </div>
                            <div class="table-responsive">
                                <table class="table ot-money-table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Patient</th>
                                            <th class="text-end">Amount</th>
                                            <th>Mode</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($refundRows as $row)
                                            <tr>
                                                <td>{{ optional($row->refunded_at)->format('d M Y H:i') ?? '-' }}</td>
                                                <td>{{ $row->booking?->patient?->full_name ?? '-' }}</td>
                                                <td class="text-end text-danger">{{ money_code((float) $row->amount, 2) }}</td>
                                                <td class="text-uppercase">{{ $row->payment_mode }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3 ot-money-empty-cell">No refunds</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .ot-money-page {
            --ot-money-primary: #1B4F72;
            --ot-money-ink: #19324a;
            --ot-money-muted: #75869a;
            --ot-money-line: rgba(27, 79, 114, .12);
            color: var(--ot-money-ink);
            animation: ot-money-in 420ms ease both;
        }

        @keyframes ot-money-in {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .otm-outer-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.12);
            border-radius: 0.90rem;
            box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
            overflow: hidden;
        }

        .otm-header-block {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1.25rem;
            flex-wrap: wrap;
            padding: 1.3rem 1.5rem 1.1rem;
            background: linear-gradient(118deg, #ffffff 0%, #f7fbfd 58%, #edf6fa 100%);
        }

        .otm-header-title {
            display: flex;
            align-items: center;
            gap: .85rem;
        }

        .otm-header-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            border: 1px solid rgba(27, 79, 114, .14);
            border-radius: 14px;
            background: #fff;
            color: var(--ot-money-primary);
            font-size: 1.3rem;
            box-shadow: 0 10px 22px rgba(27, 79, 114, .12);
        }

        .otm-header-heading {
            margin: 0;
            font-weight: 800;
            font-size: 1.25rem;
            color: var(--ot-money-primary);
            letter-spacing: -.015em;
        }

        .otm-header-sub {
            margin: .25rem 0 0;
            color: var(--ot-money-muted);
            font-size: .84rem;
        }

        .otm-breadcrumb {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: #8891a0;
        }

        .otm-breadcrumb a {
            color: #8891a0;
            text-decoration: none;
        }

        .otm-breadcrumb a:hover {
            color: var(--ot-money-primary);
        }

        .otm-breadcrumb-sep {
            color: #c3c9d3;
        }

        .otm-breadcrumb-current {
            color: #4a5568;
            font-weight: 600;
        }

        .otm-body {
            padding: 1.35rem 1.5rem 1.5rem;
            border-top: 1px solid var(--ot-money-line);
        }

        .otm-toolbar-row {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .otm-back-btn {
            font-weight: 800;
        }

        .ot-money-filter {
            display: flex;
            align-items: flex-end;
            justify-content: flex-end;
            gap: .55rem;
            flex-wrap: wrap;
        }

        .ot-money-filter label {
            display: block;
            margin: 0 0 .3rem;
            color: var(--ot-money-muted);
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .ot-money-filter input {
            min-width: 145px;
            padding: .52rem .65rem;
            border: 0;
            color: var(--ot-money-ink);
            background: transparent;
        }

        .ot-money-filter input:focus {
            outline: 0;
            border-color: var(--ot-money-primary);
            box-shadow: 0 0 0 3px rgba(27, 79, 114, .12);
        }

        .ot-money-filter .hms-btn {
            height: 38px;
            border-radius: 8px;
            font-weight: 800;
        }

        .ot-money-date-range label {
            display: block;
            margin: 0 0 .3rem;
            color: var(--ot-money-muted);
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .ot-money-date-control {
            width: 250px;
            min-height: 38px;
            border: 1px solid #d7e2eb !important;
            border-radius: 8px;
            background: #fff !important;
            cursor: pointer;
        }

        .ot-money-date-control:focus {
            border-color: var(--ot-money-primary) !important;
            box-shadow: 0 0 0 3px rgba(27, 79, 114, .12);
        }

        .ot-money-stat {
            position: relative;
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .7rem .9rem;
            overflow: hidden;
            background: linear-gradient(135deg, #ffffff 0%, #fbfdfe 100%);
            border: 1px solid var(--ot-money-line);
            border-left: 4px solid currentColor;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(27, 79, 114, .06);
            transition: transform 180ms ease, box-shadow 180ms ease;
        }

        .ot-money-stat:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(27, 79, 114, .12);
        }

        .ot-money-stat::after {
            position: absolute;
            right: -18px;
            bottom: -26px;
            width: 70px;
            height: 70px;
            content: '';
            border: 12px solid currentColor;
            border-radius: 50%;
            opacity: .07;
        }

        .ot-money-stat-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            border-radius: 10px;
            font-size: .95rem;
            box-shadow: 0 6px 14px rgba(27, 79, 114, .1);
        }

        .ot-money-stat-label {
            display: block;
            color: var(--ot-money-muted);
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .ot-money-stat strong {
            display: block;
            margin-top: .1rem;
            font-size: 1.05rem;
            letter-spacing: -.02em;
        }

        .ot-money-stat-mark {
            position: absolute;
            top: .6rem;
            right: .7rem;
            color: currentColor;
            font-size: .6rem;
            font-weight: 900;
            letter-spacing: .1em;
            opacity: .55;
        }

        .ot-money-stat-collected {
            color: #17865b;
        }

        .ot-money-stat-collected .ot-money-stat-icon {
            background: #e8f8f0;
        }

        .ot-money-stat-refunded {
            color: #d94a57;
        }

        .ot-money-stat-refunded .ot-money-stat-icon {
            background: #fff0f1;
        }

        .ot-money-stat-net {
            color: var(--ot-money-primary);
        }

        .ot-money-stat-net .ot-money-stat-icon {
            background: #eaf3f8;
        }

        .ot-money-panel {
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--ot-money-line);
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(27, 79, 114, .065);
        }

        .ot-money-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: 1.05rem 1.15rem;
            background: linear-gradient(135deg, #174562, var(--ot-money-primary));
            color: #ffffff;
            font-weight: 850;
        }

        .ot-money-panel-title {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
        }

        .ot-money-panel-header i {
            margin-right: .4rem;
        }

        .ot-money-panel-actions {
            display: flex;
            align-items: center;
            gap: .8rem;
        }

        .ot-money-panel-header small {
            color: rgba(255, 255, 255, .72);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .ot-money-export {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .38rem .62rem;
            border: 1px solid rgba(255, 255, 255, .38);
            border-radius: 7px;
            color: #ffffff;
            font-size: .74rem;
            font-weight: 850;
            text-decoration: none;
            transition: all 160ms ease;
        }

        .ot-money-export i {
            margin-right: 0;
        }

        .ot-money-export:hover {
            background: #ffffff;
            color: var(--ot-money-primary);
            box-shadow: 0 6px 14px rgba(27, 79, 114, .16);
        }

        .ot-money-table {
            --bs-table-bg: transparent;
            color: var(--ot-money-ink);
            font-size: .86rem;
        }

        .ot-money-table thead th {
            padding: .78rem 1rem;
            background: #f8fbfd;
            color: var(--ot-money-muted);
            border-bottom: 1px solid var(--ot-money-line);
            font-size: .7rem;
            font-weight: 850;
            letter-spacing: .07em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .ot-money-table tbody td {
            padding: .9rem 1rem;
            border-color: rgba(27, 79, 114, .08);
            white-space: nowrap;
        }

        .ot-money-table tbody tr:nth-child(even) td {
            background: #f9fbfd;
        }

        .ot-money-table tbody tr:hover td {
            background: var(--ot-money-line);
        }

        .ot-money-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .ot-money-empty-cell {
            padding: 2rem 1rem !important;
            color: rgba(27, 79, 114, 0.5) !important;
            font-weight: 700;
        }

        .ot-money-table tbody td:nth-child(2) {
            font-weight: 700;
        }

        .ot-money-table tbody td:nth-child(3) {
            color: var(--ot-money-primary);
            font-weight: 850;
        }

        .ot-money-table tbody td:nth-child(4) {
            color: var(--ot-money-muted);
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .04em;
        }

        @media (max-width: 767.98px) {
            .otm-header-block {
                align-items: stretch;
                flex-direction: column;
                gap: 1rem;
            }

            .otm-body {
                padding: 1.1rem 1.1rem 1.25rem;
            }

            .otm-toolbar-row {
                align-items: stretch;
                flex-direction: column;
            }

            .ot-money-filter {
                align-items: stretch;
                justify-content: flex-start;
            }

            .ot-money-filter>div {
                flex: 1 1 130px;
            }

            .ot-money-filter input {
                width: 100%;
                min-width: 0;
            }

            .ot-money-filter .hms-btn {
                align-self: flex-end;
            }

            .ot-money-date-control {
                width: 100%;
            }

            .ot-money-panel-actions {
                gap: .35rem;
            }

            .ot-money-panel-actions small {
                display: none;
            }
        }
    </style>
@endpush