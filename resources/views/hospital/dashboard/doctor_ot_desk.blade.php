@extends('hospital.layouts.app')
@section('title', 'OT Patients')

@section('content')
@php
    $statusMeta = [
        'surgery_recommended' => ['In Counselling', 'otd-pill-counsel'],
        'booked' => ['In Counselling', 'otd-pill-counsel'],
        'counselled' => ['Counselled', 'otd-pill-counsel'],
        'paid' => ['Paid', 'otd-pill-discharge'],
        'payment_verified' => ['Payment Verified', 'otd-pill-discharge'],
        'in_ward' => ['In Ward', 'otd-pill-discharge'],
        'dilated' => ['Dilated', 'otd-pill-discharge'],
        'ready' => ['OT Assistant', 'otd-pill-done'],
    ];
    $rangeLabel = \Carbon\Carbon::parse($startDate)->format('d M Y');
    if ($endDate !== $startDate) {
        $rangeLabel .= ' – ' . \Carbon\Carbon::parse($endDate)->format('d M Y');
    }
@endphp
<div class="dot-list-page">
    <div class="dot-outer-card">
        <div class="dot-header-block">
            <div>
                <div class="dot-header-title">
                    <i class="bi bi-clipboard2-pulse"></i>
                    OT Patients
                </div>
                <nav class="dot-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                    <span class="dot-breadcrumb-sep">/</span>
                    <span class="dot-breadcrumb-current">OT Patients</span>
                </nav>
            </div>
            <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>

        <div class="card dot-premium-card border-0 mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="dot-filter-icon"><i class="bi bi-funnel-fill"></i></span>
                    <strong class="dot-filter-title">Date Range Filter</strong>
                </div>
                <form method="GET" action="{{ route('hospital.dashboard.ot-patients', ['slug' => $slug]) }}" class="dot-filter-form">
                    <div class="dot-filter-fields">
                        <div>
                            <label class="form-label dot-form-label" for="otd_date_range">Date range</label>
                            <input type="text" id="otd_date_range" class="form-control clinical-input" data-hms-date-range
                                data-start-name="start_date" data-end-name="end_date"
                                data-start-value="{{ $startDate }}" data-end-value="{{ $endDate }}"
                                data-auto-submit="0"
                                placeholder="Select start → end date" autocomplete="off" readonly
                                style="min-width:220px;">
                        </div>
                        <div class="dot-filter-actions">
                            <button type="submit" class="btn dot-btn-primary">Apply</button>
                            <a href="{{ route('hospital.dashboard.ot-patients', ['slug' => $slug]) }}" class="btn dot-btn-outline">Today</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card dot-premium-card border-0">
            <div class="dot-card-header">
                <div class="dot-title-wrap">
                    <span class="dot-title-icon" aria-hidden="true">
                        <i class="bi bi-clipboard2-pulse" style="font-size:1.1rem;"></i>
                    </span>
                    <div>
                        <h5 class="dot-title mb-0">OT Patients</h5>
                        <div class="dot-card-sub">Counselling through OT Assistant · {{ $rangeLabel }}</div>
                    </div>
                </div>
                <span class="badge dot-count-badge">{{ $bookings->count() }} total</span>
            </div>
            <div class="card-body p-0">
                <div class="dot-table-wrap">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 dot-table js-datatable">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-hash me-1"></i>#</th>
                                    <th><i class="bi bi-person-badge me-1"></i>MRD</th>
                                    <th><i class="bi bi-person me-1"></i>Patient</th>
                                    <th><i class="bi bi-telephone me-1"></i>Phone</th>
                                    <th><i class="bi bi-eye me-1"></i>Eye</th>
                                    <th><i class="bi bi-bandaid me-1"></i>Surgery</th>
                                    <th><i class="bi bi-flag me-1"></i>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bookings as $booking)
                                    @php
                                        $meta = $statusMeta[$booking->ot_status] ?? [ucfirst(str_replace('_', ' ', (string) $booking->ot_status)), 'otd-pill-counsel'];
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $booking->patient?->patient_code ?? '—' }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $booking->patient?->full_name ?? '—' }}</div>
                                            <div class="small text-muted">
                                                {{ $booking->patient?->age ?? '—' }} / {{ ucfirst((string) ($booking->patient?->gender ?? '—')) }}
                                            </div>
                                        </td>
                                        <td>{{ $booking->patient?->contact_no ?: '—' }}</td>
                                        <td>{{ $booking->eye ?: '—' }}</td>
                                        <td>{{ $booking->ot_type ?: '—' }}</td>
                                        <td>
                                            <span class="otd-pill {{ $meta[1] }}">{{ $meta[0] }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center dot-empty">
                                            <i class="bi bi-inbox me-1"></i>
                                            No counselling-to-OT-assistant patients for {{ $rangeLabel }}.
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
</div>
@endsection

@push('styles')
<style>
    .dot-list-page {
        --dot-secondary: #1B4F72;
        --dot-s2-06: rgba(27, 79, 114, 0.06);
        --dot-s2-08: rgba(27, 79, 114, 0.08);
        --dot-s2-12: rgba(27, 79, 114, 0.12);
        --dot-s2-18: rgba(27, 79, 114, 0.18);
        --dot-s2-24: rgba(27, 79, 114, 0.24);
        position: relative;
        padding: .25rem 0 1.5rem;
        color: var(--dot-secondary);
    }

    .dot-outer-card {
        background: #ffffff;
        border: 1px solid rgba(15, 79, 134, 0.12);
        border-radius: 16px;
        box-shadow: 0 12px 32px rgba(15, 79, 134, 0.08);
        overflow: hidden;
        padding: 1.25rem 1.5rem 1.5rem;
    }

    .dot-header-block {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .75rem;
        padding: 0 0 1rem;
    }

    .dot-header-title {
        font-weight: 800;
        font-size: 1.3rem;
        color: var(--dot-secondary);
        letter-spacing: -.015em;
        display: flex;
        align-items: center;
        gap: .55rem;
    }

    .dot-breadcrumb {
        margin-top: .4rem;
        display: flex;
        align-items: center;
        gap: .4rem;
        font-size: .85rem;
        color: #8891a0;
    }

    .dot-breadcrumb a {
        color: #8891a0;
        text-decoration: none;
    }

    .dot-breadcrumb a:hover { color: var(--dot-secondary); }
    .dot-breadcrumb-sep { color: #c3c9d3; }
    .dot-breadcrumb-current { color: #4a5568; font-weight: 600; }

    .dot-premium-card {
        background: #ffffff;
        border: 1px solid rgba(15, 79, 134, 0.08) !important;
        border-radius: 16px;
        box-shadow: 0 8px 24px rgba(15, 79, 134, 0.05);
        overflow: hidden;
    }

    .dot-filter-icon {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--dot-s2-08);
        color: var(--dot-secondary);
        font-size: 1rem;
    }

    .dot-filter-title { color: var(--dot-secondary); font-weight: 800; }

    .dot-filter-fields {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: .85rem 1rem;
    }

    .dot-form-label {
        font-size: .78rem;
        font-weight: 700;
        color: rgba(27, 79, 114, 0.8);
        margin-bottom: .25rem;
    }

    .dot-filter-actions { display: flex; gap: .5rem; }

    .dot-btn-primary {
        background: var(--dot-secondary);
        border: 1px solid var(--dot-secondary);
        color: #fff;
        font-weight: 700;
        border-radius: 10px;
        padding: .45rem 1.1rem;
    }

    .dot-btn-primary:hover {
        background: #154360;
        border-color: #154360;
        color: #fff;
    }

    .dot-btn-outline {
        border: 1.5px solid var(--dot-s2-24);
        color: var(--dot-secondary);
        font-weight: 700;
        border-radius: 10px;
        background: #fff;
    }

    .dot-btn-outline:hover {
        background: var(--dot-s2-06);
        color: var(--dot-secondary);
        border-color: var(--dot-secondary);
    }

    .dot-card-header {
        background: #1b4f72;
        padding: 1.1rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .dot-title-wrap { display: flex; align-items: center; gap: .85rem; }

    .dot-title-icon {
        width: 40px;
        height: 40px;
        border-radius: 14px;
        background: #ffffff;
        color: #1b4f72;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 14px 30px rgba(27, 79, 114, 0.22);
        flex: 0 0 auto;
    }

    .dot-title {
        font-weight: 900;
        letter-spacing: -0.2px;
        color: #ffffff;
    }

    .dot-card-sub {
        color: rgba(255, 255, 255, .78);
        font-size: .8rem;
        font-weight: 600;
        margin-top: .15rem;
    }

    .dot-count-badge {
        background: rgba(255, 255, 255, .78);
        color: var(--dot-secondary);
        border: 1px solid var(--dot-s2-12);
        border-radius: 999px;
        padding: .5rem .8rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .dot-table-wrap { padding: 0 .9rem .9rem !important; overflow-x: auto; }
    .dot-table-wrap .dataTables_wrapper { padding-top: .25rem; }

    .dot-table-wrap .dataTables_length,
    .dot-table-wrap .dataTables_filter {
        padding: 1rem 0 .75rem;
        font-size: .85rem;
        color: rgba(27, 79, 114, 0.72);
    }

    .dot-table-wrap .dataTables_info,
    .dot-table-wrap .dataTables_paginate {
        padding: .75rem 0 .25rem;
        font-size: .85rem;
        color: rgba(27, 79, 114, 0.72);
    }

    .dot-table-wrap .dataTables_filter input,
    .dot-table-wrap .dataTables_length select {
        border: 1px solid var(--dot-s2-18) !important;
        border-radius: 8px;
        font-size: .85rem;
        color: var(--dot-secondary) !important;
        background-color: #fff;
        padding: .3rem .6rem;
        margin-left: .5rem;
    }

    .dot-table-wrap .dataTables_filter input:focus,
    .dot-table-wrap .dataTables_length select:focus {
        outline: none;
        border-color: var(--dot-secondary);
        box-shadow: 0 0 0 .18rem var(--dot-s2-12);
    }

    .dot-table-wrap .dataTables_paginate .paginate_button {
        border-radius: 8px !important;
        padding: .3rem .65rem !important;
        margin-left: .2rem !important;
        border: 1px solid transparent !important;
        color: var(--dot-secondary) !important;
    }

    .dot-table-wrap .dataTables_paginate .paginate_button.current {
        background: var(--dot-secondary) !important;
        border-color: var(--dot-secondary) !important;
        color: #fff !important;
    }

    .dot-table {
        margin-bottom: 0;
        border-collapse: collapse;
        width: 100%;
        min-width: 860px;
    }

    .dot-table thead th {
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

    .dot-table tbody td {
        background: transparent;
        border: 0;
        border-bottom: 1px solid var(--dot-s2-12);
        padding: .75rem 1rem;
        font-weight: 600;
        color: rgba(27, 79, 114, 0.90);
        vertical-align: middle;
        white-space: nowrap;
    }

    .dot-table tbody tr:hover td { background: #F5F8FC; }
    .dot-table tbody tr:last-child td { border-bottom: 0; }

    .dot-empty {
        padding: 2.25rem 1rem !important;
        color: rgba(27, 79, 114, 0.72) !important;
        font-weight: 800;
        white-space: normal !important;
    }

    .otd-pill {
        display: inline-block;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .02em;
        border-radius: 999px;
        padding: .35rem .7rem;
        border: 1px solid transparent;
    }

    .otd-pill-counsel {
        color: #1B4F72;
        background: #EBF5FB;
        border-color: rgba(27, 79, 114, .18);
    }

    .otd-pill-discharge {
        color: #7a4a00;
        background: rgba(214, 137, 16, .12);
        border-color: rgba(214, 137, 16, .28);
    }

    .otd-pill-done {
        color: #0d6949;
        background: rgba(13, 105, 73, .08);
        border-color: rgba(13, 105, 73, .2);
    }
</style>
@endpush
