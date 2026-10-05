@extends('hospital.layouts.app')
@section('title', $label)

@section('content')
<div class="ot-reports-page">

    <div class="ot-channel-hero">
        <div class="ot-channel-hero-copy">
            <div class="ot-channel-eyebrow"><i class="bi bi-activity"></i> Patient analytics</div>
            <h1 class="ot-channel-title">{{ $label }}</h1>
            <nav class="ot-reports-breadcrumb" aria-label="breadcrumb">
                <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                <span class="ot-reports-breadcrumb-sep">/</span>
                <a href="{{ route('hospital.reports.index', ['slug' => $slug]) }}">Reports</a>
                <span class="ot-reports-breadcrumb-sep">/</span>
                <span class="ot-reports-breadcrumb-current">{{ $label }}</span>
            </nav>
        </div>
        <a href="{{ route('hospital.reports.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline ot-back-btn">
            <i class="bi bi-arrow-left me-1"></i> All Reports
        </a>
    </div>

    <div class="card ot-premium-card ot-filter-card border-0 mb-4">
        <div class="card-body">
            <div class="ot-section-heading">
                <div class="d-flex align-items-center gap-2">
                    <span class="ot-filter-icon"><i class="bi bi-sliders2-vertical"></i></span>
                    <div>
                        <strong class="ot-filter-title d-block">Refine your report</strong>
                        <span class="ot-section-hint">Choose filters to narrow down the patient list</span>
                    </div>
                </div>
                <span class="ot-filter-status"><i class="bi bi-shield-check me-1"></i> Live data</span>
            </div>

            <form method="GET" action="{{ route('hospital.reports.channel.show', ['slug' => $slug, 'channel' => $channel]) }}" id="channelFilterForm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label ot-filter-label">Date Range</label>
                        <input type="text" name="date_range" id="date_range"
                            class="form-control clinical-input"
                            data-hms-date-range
                            data-range-mode="combined"
                            data-auto-submit="1"
                            placeholder="Select start → end date"
                            value="{{ request('date_range') }}"
                            autocomplete="off"
                            readonly>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label ot-filter-label">Receptionist</label>
                        <select name="reception_id" class="form-select clinical-input">
                            <option value="">All Receptionists</option>
                            @foreach($receptions as $rec)
                                <option value="{{ $rec->id }}" {{ (string) request('reception_id') === (string) $rec->id ? 'selected' : '' }}>{{ $rec->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($channel === 'ot_appointment')
                        <div class="col-md-4">
                            <label class="form-label ot-filter-label">Assistant</label>
                            <select name="doctor_id" class="form-select clinical-input">
                                <option value="">All Assistants</option>
                                @foreach($assistants as $assistant)
                                    <option value="{{ $assistant->id }}" {{ (string) request('doctor_id') === (string) $assistant->id ? 'selected' : '' }}>{{ $assistant->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="col-md-4">
                            <label class="form-label ot-filter-label">Doctor</label>
                            <select name="doctor_id" class="form-select clinical-input">
                                <option value="">All Doctors</option>
                                @foreach($doctors as $doc)
                                    <option value="{{ $doc->id }}" {{ (string) request('doctor_id') === (string) $doc->id ? 'selected' : '' }}>Dr. {{ $doc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-md-4">
                        <label class="form-label ot-filter-label">City / Location</label>
                        <select name="location_id" class="form-select clinical-input">
                            <option value="">All Cities</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ (string) request('location_id') === (string) $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label ot-filter-label">Case Type</label>
                        <select name="case_id" class="form-select clinical-input">
                            <option value="">All Cases</option>
                            @foreach($cases as $case)
                                <option value="{{ $case->id }}" {{ (string) request('case_id') === (string) $case->id ? 'selected' : '' }}>{{ $case->case_type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 text-end mt-2">
                        <a href="{{ route('hospital.reports.channel.show', ['slug' => $slug, 'channel' => $channel]) }}" class="btn ot-btn-outline me-2">Clear Filters</a>
                        <button type="submit" class="btn ot-btn-primary px-4"><i class="bi bi-search me-1"></i> Generate Report</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card ot-premium-card ot-export-card border-0 mb-4">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
            <div class="d-flex gap-2 flex-wrap">
                @hasanypermission('report_export|opd_reports_export')
                <a href="{{ route('hospital.reports.export.excel', array_merge(['slug' => $slug], request()->all())) }}" class="btn btn-sm ot-export-btn ot-export-excel">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                </a>
                <a href="{{ route('hospital.reports.export.pdf', array_merge(['slug' => $slug], request()->all())) }}" class="btn btn-sm ot-export-btn ot-export-pdf">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                </a>
                @endhasanypermission
            </div>
            <span class="badge ot-total-pill"><i class="bi bi-collection me-1"></i>{{ $patients->total() }} total</span>
        </div>
    </div>

    <div class="card ot-premium-card ot-results-card border-0">
        <div class="ot-card-header">
            <div class="ot-title-wrap">
                <span class="ot-title-icon" aria-hidden="true"><i class="bi bi-clipboard2-data" style="font-size: 1.1rem;"></i></span>
                <h5 class="ot-title mb-0">{{ $label }}</h5>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="ot-table-wrap">
                <div class="table-responsive">
                    <table class="table ot-premium-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th><i class="bi bi-person-badge me-1"></i>Patient Name</th>
                                <th><i class="bi bi-person-lines-fill me-1"></i>Receptionist</th>
                                <th><i class="bi bi-calendar-event me-1"></i>Appointment Date / Time</th>
                                <th><i class="bi bi-telephone me-1"></i>Contact No</th>
                                <th><i class="bi bi-geo-alt me-1"></i>City</th>
                                <th><i class="bi bi-person me-1"></i>Age</th>
                                <th><i class="bi bi-person-vcard me-1"></i>{{ $channel === 'ot_appointment' ? 'Assistant' : 'Doctor' }}</th>
                                <th><i class="bi bi-tag me-1"></i>Case Type</th>
                                <th class="ot-fees-col"><i class="bi bi-cash-coin me-1"></i>Case Fees</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($patients as $patient)
                                @php
                                    $caseTypeValue = strtolower(trim((string) ($patient->caseType?->case_type ?? '')));
                                    $caseTypeLabel = match (true) {
                                        str_contains($caseTypeValue, 'general') => 'General',
                                        str_contains($caseTypeValue, 'old') => 'Old',
                                        str_contains($caseTypeValue, 'new') => 'New',
                                        default => $patient->caseType?->case_type ?: '-',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $patient->full_name }}</td>
                                    <td>{{ $patient->reception?->name ?: '-' }}</td>
                                    <td>{{ $patient->appointment_date?->format('d M, Y h:i A') ?? ($patient->created_at?->format('d M, Y h:i A') ?? '-') }}</td>
                                    <td>{{ $patient->contact_no ?: '-' }}</td>
                                    <td>{{ $patient->cityName ?: '-' }}</td>
                                    <td>{{ $patient->age ?: '-' }}</td>
                                    <td>{{ $patient->doctor?->name ?: '-' }}</td>
                                    <td><span class="badge ot-type-badge">{{ $caseTypeLabel }}</span></td>
                                    <td class="ot-fees-cell">{{ money((float) $patient->case_fee, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center ot-empty">
                                        <i class="bi bi-inbox me-1"></i> No patients found for this channel.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if($patients->hasPages())
        <div class="mt-3 d-flex justify-content-center">
            {{ $patients->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
    /*
      OT Channel Report (Design refresh)
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

    .ot-channel-hero {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin: .15rem 0 1.35rem;
        padding: 1.35rem 1.5rem;
        border: 1px solid var(--ot-s2-12);
        border-radius: 22px;
        overflow: hidden;
        background:
            radial-gradient(circle at 91% 18%, rgba(36, 113, 163, .14), transparent 28%),
            linear-gradient(125deg, rgba(235, 245, 251, .98), rgba(255, 255, 255, .96) 62%);
        box-shadow: 0 16px 38px rgba(27, 79, 114, .08);
    }

    .ot-channel-hero::after {
        content: "";
        position: absolute;
        right: 1.5rem;
        bottom: -2.8rem;
        width: 118px;
        height: 118px;
        border: 18px solid rgba(27, 79, 114, .055);
        border-radius: 50%;
        pointer-events: none;
    }

    .ot-channel-hero-copy,
    .ot-back-btn { position: relative; z-index: 1; }

    .ot-channel-eyebrow {
        color: #2471a3;
        font-size: .7rem;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .ot-channel-eyebrow i { margin-right: .3rem; }

    .ot-channel-title {
        margin: .3rem 0 0;
        color: var(--ot-secondary);
        font-size: clamp(1.45rem, 2.4vw, 2rem);
        font-weight: 900;
        letter-spacing: -.035em;
    }

    .ot-reports-breadcrumb {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .4rem;
        margin-top: .45rem;
        color: #8891a0;
        font-size: .8rem;
    }

    .ot-reports-breadcrumb a {
        color: #748196;
        text-decoration: none;
    }

    .ot-reports-breadcrumb a:hover { color: var(--ot-secondary); }
    .ot-reports-breadcrumb-sep { color: #c3c9d3; }
    .ot-reports-breadcrumb-current { color: #4a5568; font-weight: 700; }

    .ot-back-btn {
        border-radius: 11px;
        border-color: var(--ot-s2-18);
        color: var(--ot-secondary);
        background: rgba(255, 255, 255, .72);
        font-weight: 800;
    }

    .ot-back-btn:hover {
        color: var(--ot-secondary);
        border-color: var(--ot-secondary);
        background: #fff;
        transform: translateY(-1px);
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

    .ot-filter-icon {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--ot-s2-10);
        color: var(--ot-secondary);
        font-size: 1rem;
    }

    .ot-filter-title {
        color: var(--ot-secondary);
        font-weight: 800;
    }

    .ot-section-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .ot-section-hint {
        display: block;
        margin-top: .15rem;
        color: #8a97a8;
        font-size: .76rem;
    }

    .ot-filter-status {
        padding: .4rem .7rem;
        border: 1px solid rgba(17, 122, 101, .18);
        border-radius: 999px;
        background: rgba(17, 122, 101, .07);
        color: #117a65;
        font-size: .72rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .ot-filter-label {
        color: var(--ot-secondary);
        font-weight: 600;
        font-size: .85rem;
    }

    .clinical-input {
        min-height: 42px;
        border-color: rgba(27, 79, 114, .15);
        border-radius: 11px;
        color: #274b65;
        box-shadow: 0 3px 10px rgba(27, 79, 114, .035);
        transition: border-color 170ms ease, box-shadow 170ms ease, background 170ms ease;
    }

    .clinical-input:hover { border-color: var(--ot-s2-24); }

    .clinical-input:focus {
        border-color: var(--ot-secondary);
        background: #fff;
        box-shadow: 0 0 0 .2rem rgba(27, 79, 114, .11);
    }

    .ot-filter-card .card-body { padding: 1.35rem; }

    .ot-export-card {
        border-radius: 16px;
        box-shadow: 0 10px 28px rgba(27, 79, 114, .075);
    }

    .ot-export-card .card-body { padding: .85rem 1rem; }
    .ot-results-card { box-shadow: 0 20px 52px rgba(27, 79, 114, .12); }

    .ot-btn-primary {
        background: var(--ot-secondary);
        border: 1px solid var(--ot-secondary);
        color: #fff;
        font-weight: 700;
        border-radius: 10px;
        transition: transform 170ms ease, background 170ms ease, box-shadow 170ms ease;
    }

    .ot-btn-primary:hover,
    .ot-btn-primary:focus {
        background: #163e5c;
        border-color: #163e5c;
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(27, 79, 114, 0.22);
    }

    .ot-btn-outline {
        background: #fff;
        border: 1.5px solid var(--ot-s2-24);
        color: var(--ot-secondary);
        font-weight: 700;
        border-radius: 10px;
        transition: background 170ms ease, border-color 170ms ease;
    }

    .ot-btn-outline:hover,
    .ot-btn-outline:focus {
        background: var(--ot-s2-06);
        color: var(--ot-secondary);
        border-color: var(--ot-secondary);
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
        min-width: 1080px;
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

    .ot-premium-table thead th.ot-fees-col,
    .ot-premium-table tbody td.ot-fees-cell {
        text-align: end;
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

    .ot-premium-table tbody td.ot-fees-cell {
        font-weight: 900;
        color: var(--ot-secondary);
    }

    .ot-premium-table tbody tr:hover td {
        background: rgba(235, 245, 251, 0.78);
        border-color: var(--ot-s2-18);
    }

    .ot-type-badge {
        background: var(--ot-s2-08);
        border: 1px solid var(--ot-s2-18);
        color: var(--ot-secondary);
        border-radius: 999px;
        padding: .4rem .7rem;
        font-weight: 800;
    }

    .ot-empty {
        padding: 2.25rem 1rem !important;
        color: rgba(27, 79, 114, 0.72) !important;
        font-weight: 800;
        white-space: normal !important;
    }

    @media (max-width: 575.98px) {
        .ot-channel-hero { padding: 1.15rem; }
        .ot-channel-title { font-size: 1.45rem; }
        .ot-back-btn { width: 100%; text-align: center; }
        .ot-section-heading { align-items: flex-start; flex-direction: column; }
        .ot-filter-status { align-self: flex-start; }
        .ot-filter-card .card-body { padding: 1rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .ot-reports-page {
            animation: none !important;
        }
    }
</style>
@endpush
