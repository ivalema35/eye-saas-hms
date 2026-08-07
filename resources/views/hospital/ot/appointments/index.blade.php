@extends('hospital.layouts.app')
@section('title', 'OT Appointments')
@section('page-header', 'OT Appointments')

@section('page-actions')
    <a href="{{ route('hospital.ot.appointments.create', ['slug' => $slug]) }}" class="hms-btn hms-btn-primary">
        <i class="bi bi-calendar-plus me-1"></i> New Appointment
    </a>
@endsection

@section('content')
<div class="ot-appt-page">
    <div class="card ot-premium-card border-0">
        <div class="ot-card-header">
            <div class="ot-title-wrap">
                <span class="ot-title-icon" aria-hidden="true">
                    <i class="bi bi-calendar2-week" style="font-size: 1.2rem;"></i>
                </span>
                <div class="flex-grow-1">
                    <h5 class="ot-title">Appointment Register</h5>
                    <div class="ot-subtitle">Pre-registration appointments booked over phone/walk-in/online/referral.</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <form method="GET" action="{{ route('hospital.ot.appointments.index', ['slug' => $slug]) }}" class="ot-search-form d-flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ot-search-input" placeholder="Name / Mobile / APT-000123">
                    <select name="status" class="form-select form-select-sm ot-search-select" onchange="this.form.submit()">
                        @foreach(['all' => 'All', 'booked' => 'Booked', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled', 'completed' => 'Completed'] as $value => $label)
                            <option value="{{ $value }}" {{ $activeStatus === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm ot-search-btn"><i class="bi bi-search"></i></button>
                </form>
                <span class="badge ot-total-pill"><i class="bi bi-collection me-1"></i>{{ $appointments->total() }} total</span>
            </div>
        </div>

        <div class="card-body p-0">
            @if(session('success'))
                <div class="alert alert-success mx-4 mt-3 ot-alert"><i class="bi bi-check-circle me-1"></i>{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger mx-4 mt-3 ot-alert"><i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}</div>
            @endif

            <div class="ot-table-wrap">
                <div class="table-responsive">
                    <table class="table ot-premium-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th><i class="bi bi-hash me-1"></i>Appt #</th>
                                <th><i class="bi bi-person-badge me-1"></i>Patient</th>
                                <th><i class="bi bi-telephone me-1"></i>Mobile</th>
                                <th><i class="bi bi-tag me-1"></i>Type</th>
                                <th><i class="bi bi-calendar-event me-1"></i>Date</th>
                                <th><i class="bi bi-person-vcard me-1"></i>Doctor</th>
                                <th><i class="bi bi-flag me-1"></i>Status</th>
                                <th class="ot-actions-col"><i class="bi bi-three-dots me-1"></i>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($appointments as $appointment)
                                <tr>
                                    <td class="fw-bold">{{ $appointment->appointment_number }}</td>
                                    <td>{{ $appointment->patient_name }}</td>
                                    <td>{{ $appointment->mobile_no }}</td>
                                    <td><span class="badge ot-type-badge text-capitalize">{{ str_replace('_', ' ', $appointment->appointment_type) }}</span></td>
                                    <td>{{ optional($appointment->appointment_date)->format('d M Y') }}</td>
                                    <td>{{ $appointment->doctor?->name ? 'Dr. '.$appointment->doctor->name : '-' }}</td>
                                    <td>
                                        <span class="badge ot-status-badge {{ $appointment->stage_badge_class }}">{{ $appointment->stage_label }}</span>
                                    </td>
                                    <td class="ot-actions-cell">
                                        @if(in_array($appointment->status, ['booked', 'confirmed']))
                                            <a href="{{ route('hospital.ot.appointments.edit', ['slug' => $slug, 'id' => $appointment->id]) }}"
                                               class="btn btn-sm ot-icon-btn ot-icon-btn-edit me-1" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            @if($appointment->status === 'booked')
                                                <form method="POST" action="{{ route('hospital.ot.appointments.confirm', ['slug' => $slug, 'id' => $appointment->id]) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm ot-icon-btn ot-icon-btn-confirm me-1" title="Confirm"><i class="bi bi-check2"></i></button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('hospital.ot.appointments.cancel', ['slug' => $slug, 'id' => $appointment->id]) }}" class="d-inline"
                                                  onsubmit="return confirm('Cancel this appointment?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm ot-icon-btn ot-icon-btn-cancel" title="Cancel"><i class="bi bi-x-lg"></i></button>
                                            </form>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center ot-empty">
                                        <i class="bi bi-inbox me-1"></i> No appointments found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if($appointments->hasPages())
        <div class="mt-3 d-flex justify-content-center">
            {{ $appointments->links() }}
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
    /*
      OT Appointments Index (Design refresh)
      Keep Blade/dynamic logic untouched; CSS-only + layout wrappers.
      Palette follows hospital shell theme (#1B4F72).
    */

    .ot-appt-page {
        --ot-primary: #ebf5fbeb;
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

    .ot-appt-page .btn,
    .ot-appt-page .hms-btn {
        border-radius: 12px;
        font-weight: 800;
        transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, border-color 170ms ease, color 170ms ease;
    }

    .ot-appt-page .btn:hover,
    .ot-appt-page .hms-btn:hover {
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
        padding: 1.15rem 1.25rem;
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
        min-width: 0;
    }

    .ot-title-wrap > div {
        min-width: 0;
    }

    .ot-title-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
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
        margin: 0;
        color: var(--ot-secondary);
    }

    .ot-subtitle {
        margin: .15rem 0 0;
        font-weight: 650;
        color: rgba(27, 79, 114, 0.72);
        font-size: .85rem;
    }

    .ot-search-form {
        background: rgba(255, 255, 255, 0.8);
        border: 1px solid var(--ot-s2-12);
        border-radius: 999px;
        padding: .3rem .4rem .3rem .9rem;
        box-shadow: 0 8px 18px rgba(27, 79, 114, 0.08);
    }

    .ot-search-input,
    .ot-search-select {
        border: none !important;
        background: transparent !important;
        color: var(--ot-secondary);
        font-weight: 650;
        box-shadow: none !important;
    }

    .ot-search-input:focus,
    .ot-search-select:focus {
        outline: none;
    }

    .ot-search-btn {
        background: var(--ot-secondary);
        color: #fff;
        border-radius: 999px;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        flex: 0 0 auto;
    }

    .ot-search-btn:hover {
        background: #245f86;
        color: #fff;
    }

    .ot-total-pill {
        background: rgba(255,255,255,.78);
        color: var(--ot-secondary);
        border: 1px solid var(--ot-s2-12);
        border-radius: 999px;
        padding: .55rem .85rem;
        font-weight: 900;
        white-space: nowrap;
        box-shadow: 0 10px 22px rgba(27,79,114,.08);
    }

    .ot-alert {
        border-radius: 14px;
        font-weight: 650;
    }

    .ot-table-wrap {
        padding: .9rem !important;
        overflow-x: auto;
    }

    .ot-premium-table {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0 8px;
        min-width: 980px;
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

    .ot-premium-table thead th.ot-actions-col,
    .ot-premium-table tbody td.ot-actions-cell {
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

    .ot-premium-table tbody tr {
        animation: ot-row-in 460ms cubic-bezier(.2,.9,.2,1) both;
        transform-origin: center;
    }

    @keyframes ot-row-in {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .ot-premium-table tbody tr:hover td {
        background: rgba(235, 245, 251, 0.78);
        border-color: var(--ot-s2-18);
    }

    .ot-premium-table tbody tr:hover {
        transform: translateY(-1px);
    }

    .ot-premium-table tbody tr:nth-child(1) { animation-delay: 20ms; }
    .ot-premium-table tbody tr:nth-child(2) { animation-delay: 40ms; }
    .ot-premium-table tbody tr:nth-child(3) { animation-delay: 60ms; }
    .ot-premium-table tbody tr:nth-child(4) { animation-delay: 80ms; }
    .ot-premium-table tbody tr:nth-child(5) { animation-delay: 100ms; }
    .ot-premium-table tbody tr:nth-child(6) { animation-delay: 120ms; }
    .ot-premium-table tbody tr:nth-child(7) { animation-delay: 140ms; }
    .ot-premium-table tbody tr:nth-child(8) { animation-delay: 160ms; }
    .ot-premium-table tbody tr:nth-child(9) { animation-delay: 180ms; }
    .ot-premium-table tbody tr:nth-child(10) { animation-delay: 200ms; }
    .ot-premium-table tbody tr:nth-child(11) { animation-delay: 220ms; }
    .ot-premium-table tbody tr:nth-child(12) { animation-delay: 240ms; }
    .ot-premium-table tbody tr:nth-child(13) { animation-delay: 260ms; }
    .ot-premium-table tbody tr:nth-child(14) { animation-delay: 280ms; }

    .ot-type-badge {
        background: rgba(27, 79, 114, 0.08);
        border: 1px solid var(--ot-s2-18);
        color: var(--ot-secondary);
        border-radius: 999px;
        padding: .4rem .7rem;
        font-weight: 800;
    }

    .ot-status-badge {
        border-radius: 999px;
        padding: .45rem .70rem;
        font-weight: 900;
        letter-spacing: .02em;
        box-shadow: 0 10px 22px rgba(27, 79, 114, 0.10);
        color: #fff;
    }

    .ot-stage-booked { background: #E0A800; }
    .ot-stage-confirmed { background: #1E8E5A; }
    .ot-stage-cancelled { background: #C0392B; }
    .ot-stage-checkedin { background: #5D6D7E; }
    .ot-stage-recommended { background: #8E44AD; }
    .ot-stage-counselled { background: #2E86C1; }
    .ot-stage-billing { background: #D68910; }
    .ot-stage-ward { background: #117864; }
    .ot-stage-ready { background: #148F77; }
    .ot-stage-operated { background: #1A5276; }
    .ot-stage-discharged { background: #1E8E5A; }

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

    .ot-icon-btn:hover {
        background: rgba(27, 79, 114, 0.12);
        border-color: var(--ot-s2-24);
        color: var(--ot-secondary);
    }

    .ot-icon-btn-confirm {
        border-color: rgba(30, 142, 90, 0.3);
        color: #1E8E5A;
    }
    .ot-icon-btn-confirm:hover {
        background: rgba(30, 142, 90, 0.12);
        color: #1E8E5A;
    }

    .ot-icon-btn-cancel {
        border-color: rgba(192, 57, 43, 0.3);
        color: #C0392B;
    }
    .ot-icon-btn-cancel:hover {
        background: rgba(192, 57, 43, 0.12);
        color: #C0392B;
    }

    .ot-empty {
        padding: 2.25rem 1rem !important;
        color: rgba(27, 79, 114, 0.72) !important;
        font-weight: 800;
    }

    @media (prefers-reduced-motion: reduce) {
        .ot-appt-page,
        .ot-premium-card,
        .ot-premium-table tbody tr,
        .ot-appt-page .btn,
        .ot-appt-page .hms-btn {
            animation: none !important;
            transition: none !important;
        }
    }
</style>
@endpush
