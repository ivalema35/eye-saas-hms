@extends('hospital.layouts.app')
@section('title', $doctor ? 'OT — Dr. '.$doctor->name : 'OT Patients')
@section('page-header', $doctor ? 'OT — Dr. '.$doctor->name : 'OT Patients')

@section('page-actions')
    <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
@endsection

@section('content')
<div class="dot-list-page">
    <div class="card dot-premium-card border-0 mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="dot-filter-icon"><i class="bi bi-funnel-fill"></i></span>
                <strong class="dot-filter-title">Date Range Filter</strong>
            </div>
            <form method="GET" action="{{ route('hospital.dashboard.doctor-ot', ['slug' => $slug]) }}" class="dot-filter-form">
                <div class="dot-filter-fields">
                    <div>
                        <label class="form-label dot-form-label" for="date_range">Date range</label>
                        <input type="text" id="date_range" class="form-control clinical-input"
                            data-hms-date-range
                            data-start-name="start_date"
                            data-end-name="end_date"
                            data-start-value="{{ $startDate }}"
                            data-end-value="{{ $endDate }}"
                            placeholder="Select start → end date"
                            autocomplete="off"
                            readonly
                            style="min-width:220px;">
                    </div>
                    @if(($doctors ?? collect())->isNotEmpty())
                        <div>
                            <label class="form-label dot-form-label" for="doctor_id">Doctor</label>
                            <select name="doctor_id" id="doctor_id" class="form-select clinical-input">
                                <option value="">All Doctors</option>
                                @foreach($doctors as $doc)
                                    <option value="{{ $doc->id }}" @selected($doctorId === $doc->id)>Dr. {{ $doc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @elseif($doctorId)
                        <input type="hidden" name="doctor_id" value="{{ $doctorId }}">
                    @endif
                    <div class="dot-filter-actions">
                        <button type="submit" class="btn dot-btn-primary">Apply</button>
                        <a href="{{ route('hospital.dashboard.doctor-ot', ['slug' => $slug]) }}" class="btn dot-btn-outline">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card dot-premium-card border-0">
        <div class="dot-card-header">
            <div class="dot-title-wrap">
                <span class="dot-title-icon" aria-hidden="true"><i class="bi bi-clipboard2-pulse" style="font-size: 1.1rem;"></i></span>
                <h5 class="dot-title mb-0">OT Patients</h5>
            </div>
            <span class="badge dot-count-badge">{{ $bookings->total() }} total</span>
        </div>
        <div class="card-body p-0">
            <div class="dot-table-wrap">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 dot-table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-person-badge me-1"></i>Patient</th>
                                <th><i class="bi bi-telephone me-1"></i>Contact</th>
                                <th><i class="bi bi-person me-1"></i>Age</th>
                                <th><i class="bi bi-calendar-event me-1"></i>OT Date</th>
                                <th><i class="bi bi-bandaid me-1"></i>Surgery</th>
                                <th><i class="bi bi-eye me-1"></i>Eye</th>
                                <th><i class="bi bi-person-vcard me-1"></i>Doctor</th>
                                <th><i class="bi bi-person-plus me-1"></i>Assistant</th>
                                <th><i class="bi bi-flag me-1"></i>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bookings as $booking)
                                <tr>
                                    <td>{{ $booking->patient?->full_name ?? '-' }}</td>
                                    <td>{{ $booking->patient?->contact_no ?: '-' }}</td>
                                    <td>{{ $booking->patient?->age ?: '-' }}</td>
                                    <td>{{ optional($booking->surgery_date)->format('d M Y') ?? '-' }}</td>
                                    <td>{{ $booking->ot_type ?: '-' }}</td>
                                    <td>{{ $booking->eye ?: '-' }}</td>
                                    <td>{{ $booking->otDoctor?->name ? 'Dr. '.$booking->otDoctor->name : '-' }}</td>
                                    <td>{{ $booking->otAssistant?->name ?: '-' }}</td>
                                    <td><span class="badge dot-status-badge text-uppercase">{{ $booking->ot_status }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center dot-empty">
                                        <i class="bi bi-inbox me-1"></i> No OT bookings found for selected dates.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if($bookings->hasPages())
        <div class="mt-3">
            {{ $bookings->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
    /*
      OT Patients (Doctor OT) — Design refresh
      Keep Blade/dynamic logic untouched; CSS-only + layout wrappers.
      Palette follows hospital shell theme (#1B4F72 / #ebf5fbeb).
    */

    .dot-list-page {
        --dot-secondary: #1B4F72;
        --dot-s2-06: rgba(27, 79, 114, 0.06);
        --dot-s2-08: rgba(27, 79, 114, 0.08);
        --dot-s2-10: rgba(27, 79, 114, 0.10);
        --dot-s2-12: rgba(27, 79, 114, 0.12);
        --dot-s2-18: rgba(27, 79, 114, 0.18);
        --dot-s2-24: rgba(27, 79, 114, 0.24);

        position: relative;
        padding: .25rem 0 1.5rem;
        color: var(--dot-secondary);
        animation: dot-page-in 420ms ease both;
    }

    @keyframes dot-page-in {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .dot-premium-card {
        background: rgba(255, 255, 255, 0.84);
        border: 1px solid var(--dot-s2-12) !important;
        border-radius: 22px;
        box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
        overflow: hidden;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    .dot-filter-icon {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--dot-s2-10);
        color: var(--dot-secondary);
        font-size: 1rem;
    }

    .dot-filter-title {
        color: var(--dot-secondary);
        font-weight: 800;
    }

    .dot-filter-fields {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: .85rem 1rem;
        align-items: end;
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
        transition: transform 170ms ease, background 170ms ease, box-shadow 170ms ease;
    }

    .dot-btn-primary:hover {
        background: #154360;
        border-color: #154360;
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(27, 79, 114, 0.22);
    }

    .dot-btn-outline {
        border: 1.5px solid var(--dot-s2-24);
        color: var(--dot-secondary);
        font-weight: 700;
        border-radius: 10px;
        background: #fff;
        transition: background 170ms ease, border-color 170ms ease;
    }

    .dot-btn-outline:hover {
        background: var(--dot-s2-06);
        color: var(--dot-secondary);
        border-color: var(--dot-secondary);
    }

    .dot-card-header {
        background:
            linear-gradient(135deg, rgba(235, 245, 251, 0.92), rgba(255, 255, 255, 0.94)),
            #ffffff;
        border-bottom: 1px solid var(--dot-s2-12);
        padding: 1.1rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .dot-title-wrap {
        display: flex;
        align-items: center;
        gap: .85rem;
    }

    .dot-title-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: var(--dot-secondary);
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 14px 30px rgba(27, 79, 114, 0.22);
        flex: 0 0 auto;
    }

    .dot-title {
        font-weight: 900;
        letter-spacing: -0.2px;
        color: var(--dot-secondary);
    }

    .dot-count-badge {
        background: rgba(255, 255, 255, .78);
        color: var(--dot-secondary);
        border: 1px solid var(--dot-s2-12);
        border-radius: 999px;
        padding: .5rem .8rem;
        font-weight: 800;
        white-space: nowrap;
        box-shadow: 0 10px 22px rgba(27, 79, 114, .08);
    }

    .dot-table-wrap {
        padding: .9rem !important;
        overflow-x: auto;
    }

    .dot-table {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0 8px;
        min-width: 1040px;
    }

    .dot-table thead th {
        background: var(--dot-secondary) !important;
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

    .dot-table thead th:first-child {
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .dot-table thead th:last-child {
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .dot-table tbody td {
        background: rgba(255, 255, 255, 0.88);
        border-top: 1px solid var(--dot-s2-12);
        border-bottom: 1px solid var(--dot-s2-12);
        padding: .95rem .95rem;
        font-weight: 750;
        color: rgba(27, 79, 114, 0.90);
        vertical-align: middle;
        white-space: nowrap;
    }

    .dot-table tbody td:first-child {
        border-left: 1px solid var(--dot-s2-12);
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
        color: rgba(27, 79, 114, 0.82);
        font-weight: 900;
    }

    .dot-table tbody td:last-child {
        border-right: 1px solid var(--dot-s2-12);
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .dot-table tbody tr:hover td {
        background: rgba(235, 245, 251, 0.78);
        border-color: var(--dot-s2-18);
    }

    .dot-status-badge {
        background: var(--dot-s2-08);
        border: 1px solid var(--dot-s2-18);
        color: var(--dot-secondary);
        border-radius: 999px;
        padding: .4rem .7rem;
        font-weight: 800;
        letter-spacing: .02em;
    }

    .dot-empty {
        padding: 2.25rem 1rem !important;
        color: rgba(27, 79, 114, 0.72) !important;
        font-weight: 800;
        white-space: normal !important;
    }

    @media (max-width: 768px) {
        .dot-filter-fields { grid-template-columns: 1fr 1fr; }
        .dot-filter-actions { grid-column: 1 / -1; }
    }

    @media (prefers-reduced-motion: reduce) {
        .dot-list-page {
            animation: none !important;
        }
    }
</style>
@endpush
