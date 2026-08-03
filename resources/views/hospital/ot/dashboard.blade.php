@extends('hospital.layouts.app')
@section('title', 'OT Reception Dashboard')
@section('page-header', 'OT Reception Dashboard')

@push('styles')
<style>
    /*
      OT Reception Dashboard (Design refresh)
      - Design-only changes. Dynamic Blade logic untouched.
      - Palette follows hospital shell: Primary #ebf5fbeb, Secondary #1B4F72
    */

    .ot-recep-dashboard {
        --ot-primary: #ebf5fbeb;
        --ot-secondary: #1B4F72;
        --ot-s2-06: rgba(27, 79, 114, 0.06);
        --ot-s2-08: rgba(27, 79, 114, 0.08);
        --ot-s2-12: rgba(27, 79, 114, 0.12);
        --ot-s2-18: rgba(27, 79, 114, 0.18);
        --ot-s2-24: rgba(27, 79, 114, 0.24);

        color: var(--ot-secondary);
        animation: ot-recep-page-in 420ms ease both;
    }

    @keyframes ot-recep-page-in {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .ot-recep-dashboard .btn,
    .ot-recep-dashboard .hms-btn {
        border-radius: 12px;
        font-weight: 800;
        transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, border-color 170ms ease, color 170ms ease;
    }
    .ot-recep-dashboard .btn:hover,
    .ot-recep-dashboard .hms-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 26px rgba(27, 79, 114, 0.14);
    }

    .ot-kpi-card {
        background: rgba(255, 255, 255, 0.86);
        border: 1px solid var(--ot-s2-12) !important;
        border-radius: 22px;
        box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
        overflow: hidden;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        position: relative;
        animation: ot-kpi-rise 520ms cubic-bezier(.2,.9,.2,1) both;
    }
    @keyframes ot-kpi-rise {
        from { opacity: 0; transform: translateY(10px) scale(0.99); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .ot-kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 26px 56px rgba(27, 79, 114, 0.16);
    }

    .ot-kpi-body {
        padding: 1.1rem 1.2rem;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .ot-kpi-meta {
        min-width: 0;
    }

    .ot-kpi-label {
        margin: 0;
        font-size: .78rem;
        letter-spacing: .10em;
        text-transform: uppercase;
        font-weight: 900;
        color: rgba(27, 79, 114, 0.72);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ot-kpi-value {
        margin: .35rem 0 0;
        font-weight: 950;
        letter-spacing: -0.3px;
        color: var(--ot-secondary);
        font-size: 2.0rem;
        line-height: 1.1;
    }

    .ot-kpi-hint {
        margin: .35rem 0 0;
        font-size: .92rem;
        font-weight: 650;
        color: rgba(27, 79, 114, 0.70);
    }

    .ot-kpi-icon {
        width: 54px;
        height: 54px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        background: rgba(27, 79, 114, 0.08);
        border: 1px solid rgba(27, 79, 114, 0.14);
        color: var(--ot-secondary);
    }
    .ot-kpi-icon i {
        font-size: 1.25rem;
    }

    .ot-workspace-card {
        background: rgba(255, 255, 255, 0.86);
        border: 1px solid var(--ot-s2-12) !important;
        border-radius: 22px;
        box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
        overflow: hidden;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        animation: ot-workspace-in 520ms cubic-bezier(.2,.9,.2,1) both;
    }
    @keyframes ot-workspace-in {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .ot-workspace-head {
        padding: 1.05rem 1.2rem;
        background:
            linear-gradient(135deg, rgba(235, 245, 251, 0.92), rgba(255, 255, 255, 0.94)),
            #ffffff;
        border-bottom: 1px solid var(--ot-s2-12);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .ot-workspace-title {
        margin: 0;
        font-weight: 950;
        letter-spacing: -0.2px;
        color: var(--ot-secondary);
        display: flex;
        align-items: center;
        gap: .6rem;
    }

    .ot-workspace-title i {
        width: 40px;
        height: 40px;
        border-radius: 14px;
        background: var(--ot-secondary);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 14px 30px rgba(27, 79, 114, 0.20);
    }

    .ot-workspace-body {
        padding: 1rem 1.2rem;
    }

    .ot-workspace-text {
        margin: 0;
        color: rgba(27, 79, 114, 0.72) !important;
        font-weight: 700;
    }

    @media (prefers-reduced-motion: reduce) {
        .ot-recep-dashboard,
        .ot-kpi-card,
        .ot-workspace-card,
        .ot-recep-dashboard .btn,
        .ot-recep-dashboard .hms-btn {
            animation: none !important;
            transition: none !important;
        }
    }
</style>
@endpush

@section('page-actions')
    @haspermission('ot.appointment.create')
    <a href="{{ route('hospital.ot.appointments.create', ['slug' => $slug]) }}" class="hms-btn hms-btn-primary">
        <i class="bi bi-plus-circle"></i> New OT Appointment
    </a>
    @endhaspermission
@endsection

@section('content')
<div class="ot-recep-dashboard">
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card ot-kpi-card h-100 border-0">
                <div class="ot-kpi-body">
                    <div class="ot-kpi-meta">
                        <p class="ot-kpi-label">Total OT Today</p>
                        <div class="ot-kpi-value">{{ number_format((int) ($stats['total_ot_today'] ?? 0)) }}</div>
                        <div class="ot-kpi-hint">Today’s scheduled OT count</div>
                    </div>
                    <span class="ot-kpi-icon" aria-hidden="true">
                        <i class="bi bi-calendar2-check"></i>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card ot-kpi-card h-100 border-0">
                <div class="ot-kpi-body">
                    <div class="ot-kpi-meta">
                        <p class="ot-kpi-label">Pending Counselling</p>
                        <div class="ot-kpi-value">{{ number_format((int) ($stats['pending_counselling'] ?? 0)) }}</div>
                        <div class="ot-kpi-hint">Needs counselling before OT</div>
                    </div>
                    <span class="ot-kpi-icon" aria-hidden="true">
                        <i class="bi bi-chat-square-text"></i>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card ot-kpi-card h-100 border-0">
                <div class="ot-kpi-body">
                    <div class="ot-kpi-meta">
                        <p class="ot-kpi-label">Ready For Surgery</p>
                        <div class="ot-kpi-value">{{ number_format((int) ($stats['ready_for_surgery'] ?? 0)) }}</div>
                        <div class="ot-kpi-hint">Ready to move into OT</div>
                    </div>
                    <span class="ot-kpi-icon" aria-hidden="true">
                        <i class="bi bi-heart-pulse"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="card ot-workspace-card border-0">
        <div class="ot-workspace-head">
            <h5 class="ot-workspace-title">
                <i class="bi bi-kanban" aria-hidden="true"></i>
                OT Workspace
            </h5>
            <div class="d-flex flex-wrap gap-2">
                @haspermission('ot.appointment.view')
                <a href="{{ route('hospital.ot.appointments.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
                    <i class="bi bi-calendar2-week"></i> OT Appointments
                </a>
                @endhaspermission
                @haspermission('ot.counselling.fill')
                <a href="{{ route('hospital.ot.counsellor.dashboard', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
                    <i class="bi bi-chat-left-heart"></i> Counselling Queue
                </a>
                @endhaspermission
            </div>
        </div>
        <div class="ot-workspace-body">
            <p class="ot-workspace-text">Book OT appointments, then continue counselling after the doctor recommends surgery. Manual OT booking is no longer used.</p>
        </div>
    </div>
</div>
@endsection
