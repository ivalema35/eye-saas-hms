@extends('hospital.layouts.app')
@section('title', 'Dashboard')
@section('page-header', 'Dashboard')

@push('styles')
<style>
/* ═══════════════════════════════════════════════════════════════════════════
   BENTO BOX DASHBOARD — NATIVE CSS GRID
   Inter font · #1B4F72 primary · cool-gray page · glass icon circles.
═══════════════════════════════════════════════════════════════════════════ */

/* ── Page shell ─────────────────────────────────────────────────────────── */
.bento-page {
    background-color: #F0F4F8;
    padding: 1.75rem;
    min-height: 100%;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

/* ── Bento Grid Container ───────────────────────────────────────────────── */
.bento-dashboard {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 20px;
}

/* ── Bento Card ─────────────────────────────────────────────────────────── */
.bento-card {
    background: #FFFFFF;
    border-radius: 20px;
    border: 1px solid #E2E8F0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.bento-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(27, 79, 114, 0.06);
}

/* ── Span helpers ───────────────────────────────────────────────────────── */
.span-2  { grid-column: span 2; }
.span-3  { grid-column: span 3; }
.span-4  { grid-column: span 4; }
.span-6  { grid-column: span 6; }
.span-7  { grid-column: span 7; }
.span-8  { grid-column: span 8; }
.span-12 { grid-column: span 12; }
.row-span-2 { grid-row: span 2; }

/* ── Metric stat card interior ──────────────────────────────────────────── */
.bento-stat {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem 1.375rem;
    height: 100%;
}
.bento-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 22px;
}
.bento-icon i   { font-size: 22px; }
.bento-icon svg { width: 22px; height: 22px; stroke-width: 1.75; }

/* ── Metric typography ──────────────────────────────────────────────────── */
.metric-value {
    font-weight: 800;
    font-size: 32px;
    color: #1A202C;
    letter-spacing: -1px;
    line-height: 1.2;
    margin: 4px 0 0;
}
.metric-label {
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    color: #718096;
    letter-spacing: 0.5px;
    margin: 0;
}
.metric-meta {
    font-size: 11px;
    color: #A0AEC0;
    margin: 2px 0 0;
}

/* ── Glass icon color variants ──────────────────────────────────────────── */
.ig-blue   { background: linear-gradient(135deg, rgba(27,79,114,0.10) 0%, rgba(41,128,185,0.05) 100%);  border: 1px solid rgba(27,79,114,0.10);   color: #1B4F72; }
.ig-green  { background: linear-gradient(135deg, rgba(39,174,96,0.10) 0%, rgba(39,174,96,0.04) 100%);   border: 1px solid rgba(39,174,96,0.12);    color: #27AE60; }
.ig-orange { background: linear-gradient(135deg, rgba(230,126,34,0.10) 0%, rgba(230,126,34,0.04) 100%); border: 1px solid rgba(230,126,34,0.12);   color: #E67E22; }
.ig-teal   { background: linear-gradient(135deg, rgba(26,188,156,0.10) 0%, rgba(26,188,156,0.04) 100%); border: 1px solid rgba(26,188,156,0.12);   color: #1ABC9C; }
.ig-purple { background: linear-gradient(135deg, rgba(142,68,173,0.10) 0%, rgba(142,68,173,0.04) 100%); border: 1px solid rgba(142,68,173,0.12);   color: #8E44AD; }
.ig-red    { background: linear-gradient(135deg, rgba(231,76,60,0.10) 0%, rgba(231,76,60,0.04) 100%);   border: 1px solid rgba(231,76,60,0.12);    color: #E74C3C; }
.ig-indigo { background: linear-gradient(135deg, rgba(52,73,94,0.10) 0%, rgba(52,73,94,0.04) 100%);     border: 1px solid rgba(52,73,94,0.12);     color: #34495E; }
.ig-cobalt { background: linear-gradient(135deg, rgba(41,128,185,0.10) 0%, rgba(41,128,185,0.04) 100%); border: 1px solid rgba(41,128,185,0.12);   color: #2980B9; }

/* ── Section card header ────────────────────────────────────────────────── */
.bento-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.375rem;
    border-bottom: 1px solid #EDF2F7;
    background: #FAFBFC;
    flex-shrink: 0;
}
.bento-title {
    font-size: .9375rem;
    font-weight: 700;
    color: #1B4F72;
    letter-spacing: -0.2px;
    margin: 0;
}

/* ── Borderless zebra table ─────────────────────────────────────────────── */
.bento-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
}
.bento-table thead tr    { background: #F7FAFC; }
.bento-table thead th    { padding: .625rem 1.125rem; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #4A5568; border-bottom: 2px solid #E2E8F0; white-space: nowrap; }
.bento-table tbody tr:nth-child(odd)  { background: #FFFFFF; }
.bento-table tbody tr:nth-child(even) { background: #F7FAFC; }
.bento-table tbody tr    { border-bottom: 1px solid #EDF2F7; transition: background .15s; }
.bento-table tbody tr:hover { background: rgba(27, 79, 114, 0.03); }
.bento-table tbody td    { padding: .75rem 1.125rem; color: #2D3748; vertical-align: middle; }
.bento-table tbody tr:last-child { border-bottom: none; }

/* ── Status badge ───────────────────────────────────────────────────────── */
.b-badge { display: inline-flex; align-items: center; font-size: 11px; font-weight: 700; padding: .3em .85em; border-radius: 20px; letter-spacing: .02em; }
.b-badge-warn  { background: rgba(230,126,34,0.10); color: #C05621; }
.b-badge-green { background: rgba(39,174,96,0.10);  color: #276749; }

/* ── Revenue 3-col inside bento card ────────────────────────────────────── */
.rev-grid { display: flex; flex: 1; }
.rev-col  { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.25rem .75rem; text-align: center; }
.rev-col + .rev-col { border-left: 1px solid #EDF2F7; }
.rev-value { font-weight: 800; font-size: 1.375rem; color: #1A202C; letter-spacing: -0.5px; margin: 4px 0 0; }
.rev-label { font-weight: 600; font-size: 11px; text-transform: uppercase; color: #718096; letter-spacing: .5px; margin: 0; }

/* ── Quick actions grid ─────────────────────────────────────────────────── */
.qa-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 16px; padding: 1.375rem; }
.qa-pill {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .625rem;
    padding: 1.25rem .75rem;
    background: #F7FAFC;
    border: 1.5px solid #EDF2F7;
    border-radius: 16px;
    text-decoration: none;
    color: #4A5568;
    font-weight: 600;
    font-size: 13px;
    text-align: center;
    transition: all 0.2s ease;
    cursor: pointer;
}
.qa-pill:hover {
    background: #1B4F72;
    border-color: #1B4F72;
    color: #FFFFFF;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(27, 79, 114, 0.18);
}
.qa-pill i   { font-size: 24px; display: block; }
.qa-pill svg { width: 24px; height: 24px; stroke-width: 1.75; }
.qa-pill:hover i,
.qa-pill:hover svg { color: #FFFFFF !important; stroke: #FFFFFF !important; }

/* ── Alert banner ───────────────────────────────────────────────────────── */
.bento-alert { border-radius: 12px; padding: .9rem 1.25rem; display: flex; align-items: center; gap: .75rem; font-size: .875rem; font-weight: 500; margin-bottom: 1.25rem; }
.bento-alert-warn   { background: #FFFBF0; color: #92400E; border-left: 4px solid #F59E0B; }
.bento-alert-danger { background: #FEF2F2; color: #991B1B; border-left: 4px solid #EF4444; }

/* ── FOC pulsing badge ──────────────────────────────────────────────────── */
.foc-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 24px; border-radius: 12px; background: #EF4444; color: #fff; font-size: 11px; font-weight: 800; padding: 0 .4rem; animation: foc-pulse 2s infinite; }
@keyframes foc-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,.45); }
    50%       { box-shadow: 0 0 0 7px rgba(239,68,68,0); }
}

.foc-premium-card {
    border-radius: 12px !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
    border: 1px solid #E2E8F0 !important;
}
.foc-premium-table thead tr {
    background: #1B4F72 !important;
}
.foc-premium-table thead th {
    color: #FFFFFF !important;
    border-bottom: none !important;
}
.foc-view-btn {
    border-color: #1B4F72 !important;
    color: #1B4F72 !important;
    border-radius: 8px !important;
}
.foc-accept-btn {
    background: #27AE60 !important;
    border-color: #27AE60 !important;
    border-radius: 8px !important;
}

/* ── Fallback welcome card ──────────────────────────────────────────────── */
.bento-welcome { max-width: 540px; margin: 3.5rem auto; background: #FFFFFF; border-radius: 20px; border: 1px solid #E2E8F0; box-shadow: 0 8px 32px rgba(27,79,114,0.06); padding: 3.5rem 2.5rem; text-align: center; }
.bento-welcome-icon { width: 88px; height: 88px; border-radius: 50%; background: linear-gradient(135deg, rgba(27,79,114,0.10) 0%, rgba(41,128,185,0.05) 100%); border: 1px solid rgba(27,79,114,0.12); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; font-size: 2.5rem; color: #1B4F72; }

/* ── Responsive breakpoints ─────────────────────────────────────────────── */
@media (max-width: 1200px) {
    .span-3  { grid-column: span 4; }
    .span-7  { grid-column: span 8; }
    .span-4, .span-5 { grid-column: span 6; }
}
@media (max-width: 900px) {
    .span-2, .span-3, .span-4 { grid-column: span 6; }
    .span-7, .span-8 { grid-column: span 12; }
    .row-span-2 { grid-row: span 1; }
}
@media (max-width: 600px) {
    .bento-dashboard { gap: 12px; }
    .span-2, .span-3, .span-4, .span-6, .span-7, .span-8, .span-12 { grid-column: span 12; }
}
</style>
@endpush

@section('content')
<div class="bento-page">

{{-- ────────────────────────────────────────────────────────────────────────
     PHP flags — each is null when the user's role lacks the gate permission
──────────────────────────────────────────────────────────────────────────── --}}
@php
    $hasClinical  = $todayPatients      !== null;
    $hasReception = $todayRegistrations !== null;
    $hasRevenue   = $revenueToday       !== null;
    $hasStaff     = $totalDoctors       !== null;
    $hasQueue     = $primaryQueue       !== null;
    $hasPerf      = $receptionists      !== null;
    $hasOt        = $otToday            !== null;
    $hasFocAlert  = $focAlerts          !== null;
    $focReceptionists = $focReceptionists ?? collect();
    $pendingFocRequests = $pendingFocRequests ?? collect();
    $hasAnyData   = $hasClinical || $hasReception || $hasRevenue || $hasStaff || $hasOt || $hasFocAlert;
@endphp

{{-- Subscription Alert --}}
@if($subscriptionDaysLeft !== null && $subscriptionDaysLeft <= 14)
    <div class="bento-alert {{ $subscriptionDaysLeft <= 3 ? 'bento-alert-danger' : 'bento-alert-warn' }}">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>
            @if($subscriptionDaysLeft <= 0)
                Your subscription has <strong>expired</strong>. Please renew immediately.
            @else
                Subscription expires in <strong>{{ $subscriptionDaysLeft }} day{{ $subscriptionDaysLeft === 1 ? '' : 's' }}</strong>. Please renew soon.
            @endif
        </span>
        <a href="{{ route('hospital.settings.index', ['slug' => $slug]) }}"
           class="ms-auto text-decoration-none fw-semibold" style="color:inherit">Renew Now →</a>
    </div>
@endif

{{-- ────────────────────────────────────────────────────────────────────────
     FALLBACK: No dashboard permissions
──────────────────────────────────────────────────────────────────────────── --}}
@if(!$hasAnyData)
    @php
        $authUser     = auth('hospital_user')->user();
        $hospitalName = $tenant?->name ?? config('app.name');
    @endphp
    <div class="bento-welcome">
        <div class="bento-welcome-icon"><i class="fa-solid fa-hospital"></i></div>
        <h4 class="fw-bold mb-1" style="color:#1B4F72">Welcome to {{ $hospitalName }}</h4>
        <p class="text-muted mb-1">
            Logged in as <strong>{{ $authUser?->name }}</strong>
            @if($authUser?->role?->name) &mdash; {{ $authUser->role->name }} @endif
        </p>
        <p class="text-muted small mb-0">Your role has no dashboard widgets assigned yet. Contact your administrator to configure the appropriate permissions.</p>
    </div>
@else

{{-- ════════════════════════════════════════════════════════════════════════
     BENTO GRID — ROW 1: Stat Metric Cards
     Each card only renders when its permission gate was satisfied.
════════════════════════════════════════════════════════════════════════════ --}}
<div class="bento-dashboard mb-4">

    {{-- Today's Patients (exam.primary.view) --}}
    @if($hasClinical)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-blue">
                    <i data-lucide="users" style="width:22px;height:22px;color:#1B4F72;stroke-width:1.75"></i>
                </div>
                <div>
                    <p class="metric-label">Today's Patients</p>
                    <div class="metric-value">{{ $todayPatients }}</div>
                    <p class="metric-meta">{{ now()->format('d M Y') }}</p>
                </div>
            </div>
        </div>

        {{-- Pending Exams --}}
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-orange">
                    <i data-lucide="stethoscope" style="width:22px;height:22px;color:#E67E22;stroke-width:1.75"></i>
                </div>
                <div>
                    <p class="metric-label">Pending Exams</p>
                    <div class="metric-value">{{ $pendingExams }}</div>
                    <p class="metric-meta">In queue</p>
                </div>
            </div>
        </div>

        {{-- Primary Done --}}
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-teal">
                    <i data-lucide="eye" style="width:22px;height:22px;color:#1ABC9C;stroke-width:1.75"></i>
                </div>
                <div>
                    <p class="metric-label">Primary Done</p>
                    <div class="metric-value">{{ $todayPrimary }}</div>
                    <p class="metric-meta">Secondary: {{ $todaySecondary }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Today's Registrations (opd.patient.register / opd.patient.register_phone) --}}
    @if($hasReception)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-indigo">
                    <i data-lucide="clipboard-list" style="width:22px;height:22px;color:#34495E;stroke-width:1.75"></i>
                </div>
                <div>
                    <p class="metric-label">Registrations</p>
                    <div class="metric-value">{{ $todayRegistrations }}</div>
                    <p class="metric-meta">Walk-in: {{ $todayWalkin }} &bull; Phone: {{ $todayPhone }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Revenue Today (reports.view / reports.export context) --}}
    @if($hasRevenue)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-green">
                    <i data-lucide="indian-rupee" style="width:22px;height:22px;color:#27AE60;stroke-width:1.75"></i>
                </div>
                <div>
                    <p class="metric-label">Today Revenue</p>
                    <div class="metric-value">₹{{ number_format($revenueToday, 0) }}</div>
                    <p class="metric-meta">Month: ₹{{ number_format($revenueMonth, 0) }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- OT Stats (ot.patient.list) --}}
    @if($hasOt)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-purple">
                    <i data-lucide="activity" style="width:22px;height:22px;color:#8E44AD;stroke-width:1.75"></i>
                </div>
                <div>
                    <p class="metric-label">OT Today</p>
                    <div class="metric-value">{{ $otToday }}</div>
                    <p class="metric-meta">Done: {{ $otOperated }} &bull; Pending: {{ $otPending }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- FOC Approval Alert (opd.foc.accept) --}}
    @if($hasFocAlert)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-red">
                    <i data-lucide="file-check" style="width:22px;height:22px;color:#C0392B;stroke-width:1.75"></i>
                </div>
                <div>
                    <p class="metric-label">FOC Approval</p>
                    <div class="d-flex align-items-center gap-2">
                        <div class="metric-value">{{ $focAlerts }}</div>
                        @if($focAlerts > 0)<span class="foc-badge">!</span>@endif
                    </div>
                    <p class="metric-meta">Awaiting approval</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Staff Counts (master.doctors / master.receptions) --}}
    @if($hasStaff)
        <div class="bento-card span-3">
            <div class="bento-stat">
                <div class="bento-icon ig-cobalt">
                    <i data-lucide="user-cog" style="width:22px;height:22px;color:#2980B9;stroke-width:1.75"></i>
                </div>
                <div>
                    <p class="metric-label">Staff</p>
                    <div class="metric-value">{{ $totalDoctors + $totalReceptions }}</div>
                    <p class="metric-meta">Drs: {{ $totalDoctors }} &bull; Rec: {{ $totalReceptions }}</p>
                </div>
            </div>
        </div>
    @endif

</div>{{-- /bento-dashboard row 1 --}}

{{-- ════════════════════════════════════════════════════════════════════════
     ROW 2: Queue (left, col-lg-8) + Revenue/Reception stacked (right, col-lg-4)
     Bootstrap columns for the skeleton · .bento-card for the aesthetics.
════════════════════════════════════════════════════════════════════════════ --}}
@if($hasQueue || $hasPerf || $hasRevenue)
<div class="row g-4 mb-4">

    {{-- Primary Patient Queue (Doctor) ─────────────────────────────────── --}}
    @if($hasQueue)
        <div class="{{ ($hasPerf || $hasRevenue) ? 'col-lg-8' : 'col-12' }}">
            <div class="bento-card h-100">
                <div class="bento-header">
                    <h3 class="bento-title">
                        <i class="fa-solid fa-list-ol me-1"></i> My Primary Queue
                    </h3>
                    <span class="b-badge {{ $primaryQueue->count() > 0 ? 'b-badge-warn' : 'b-badge-green' }}">
                        {{ $primaryQueue->count() }} waiting
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="bento-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>MRD</th>
                                <th>Patient</th>
                                <th>Age / Gender</th>
                                <th>Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($primaryQueue as $i => $patient)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td><strong>{{ $patient->patient_code }}</strong></td>
                                    <td>{{ $patient->full_name }}</td>
                                    <td>{{ $patient->age }}y / {{ ucfirst($patient->gender) }}</td>
                                    <td>{{ $patient->created_at->format('h:i A') }}</td>
                                    <td>
                                        <a href="{{ route('hospital.exam.primary.show', ['slug' => $slug, 'id' => $patient->id]) }}"
                                           class="hms-btn hms-btn-sm hms-btn-primary">
                                            <i class="fa-solid fa-stethoscope"></i> Examine
                                        </a>
                                        @haspermission('opd.foc.create')
                                            <button type="button"
                                                    class="hms-btn hms-btn-sm hms-btn-outline"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#focRequestModal{{ $patient->id }}">
                                                <i class="fa-solid fa-hand-holding-heart"></i> Request FOC
                                            </button>

                                            <div class="modal fade" id="focRequestModal{{ $patient->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <form method="POST" action="{{ route('hospital.foc.request', ['slug' => $slug]) }}">
                                                            @csrf
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Request FOC</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                                                <input type="hidden" name="doctor_id" value="{{ auth('hospital_user')->id() }}">

                                                                <div class="mb-2">
                                                                    <label class="form-label mb-1">Patient Name</label>
                                                                    <input type="text" class="form-control" value="{{ $patient->full_name }}" readonly>
                                                                </div>

                                                                <div class="mb-2">
                                                                    <label class="form-label mb-1">Case Fee</label>
                                                                    <input type="number" step="0.01" name="foc_fee" class="form-control" value="{{ $patient->case_fee }}" readonly>
                                                                </div>

                                                                <div class="mb-2">
                                                                    <label class="form-label mb-1">Select Receptionist</label>
                                                                    <select name="reception_id" class="form-select" required>
                                                                        <option value="">Select Receptionist</option>
                                                                        @foreach($focReceptionists as $receptionist)
                                                                            <option value="{{ $receptionist->id }}">{{ $receptionist->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>

                                                                <div>
                                                                    <label class="form-label mb-1">Reason</label>
                                                                    <textarea name="reason" class="form-control" rows="2" placeholder="Why FOC is requested" required></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="hms-btn hms-btn-sm hms-btn-outline" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="hms-btn hms-btn-sm hms-btn-primary">Submit Request</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endhaspermission
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4" style="color:#94A3B8">
                                        <i class="fa-regular fa-circle-check fa-xl d-block mb-2" style="color:#27AE60"></i>
                                        Queue is clear
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Right pane: Revenue + Reception stacked ─────────────────────────── --}}
    @if($hasRevenue || $hasPerf)
        <div class="{{ $hasQueue ? 'col-lg-4' : 'col-12' }} d-flex flex-column gap-4">

            {{-- Revenue Overview (reports.view / reports.export context) --}}
            @if($hasRevenue)
                <div class="bento-card">
                    <div class="bento-header">
                        <h3 class="bento-title"><i class="fa-solid fa-chart-line me-1"></i> Revenue Overview</h3>
                    </div>
                    <div class="rev-grid">
                        <div class="rev-col">
                            <p class="rev-label">Today</p>
                            <div class="rev-value">₹{{ number_format($revenueToday, 0) }}</div>
                        </div>
                        <div class="rev-col">
                            <p class="rev-label">This Month</p>
                            <div class="rev-value">₹{{ number_format($revenueMonth, 0) }}</div>
                        </div>
                        <div class="rev-col">
                            <p class="rev-label">This Year</p>
                            <div class="rev-value">₹{{ number_format($revenueYear, 0) }}</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Reception Performance (master.receptions) --}}
            @if($hasPerf)
                <div class="bento-card">
                    <div class="bento-header">
                        <h3 class="bento-title"><i class="fa-solid fa-headset me-1"></i> Reception — Today</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="bento-table">
                            <thead>
                                <tr>
                                    <th>Receptionist</th>
                                    <th class="text-center">Walk-ins</th>
                                    <th class="text-end">Net (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($receptionists as $rec)
                                    <tr>
                                        <td>{{ $rec->name }}</td>
                                        <td class="text-center">{{ $rec->today_count }}</td>
                                        <td class="text-end"><strong>{{ number_format($rec->today_net, 0) }}</strong></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-3" style="color:#94A3B8">No receptionists found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    @endif

</div>{{-- /row middle --}}
@endif

@if($pendingFocRequests->isNotEmpty() || $hasFocAlert)
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="bento-card foc-premium-card">
            <div class="bento-header">
                <h3 class="bento-title"><i class="fa-solid fa-hand-holding-heart me-1"></i> Pending FOC Requests</h3>
                <span class="b-badge {{ $pendingFocRequests->count() > 0 ? 'b-badge-warn' : 'b-badge-green' }}">{{ $pendingFocRequests->count() }} pending</span>
            </div>
            <div class="table-responsive">
                <table class="bento-table foc-premium-table">
                    <thead style="background-color: #1B4F72 !important;">
                        <tr>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;">#</th>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;">DOCTOR NAME</th>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;">PATIENT NAME</th>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;">MRD</th>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;">FEE TO WAIVE</th>
                            <th style="color: #ffffff !important; font-weight: 600; border: none;" class="text-center">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingFocRequests as $i => $foc)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $foc->doctor?->name ?? '—' }}</td>
                                <td>{{ $foc->patient?->full_name ?? '—' }}</td>
                                <td>{{ $foc->patient?->patient_code ?? '—' }}</td>
                                <td>₹{{ number_format((float) $foc->foc_fee, 2) }}</td>
                                <td>
                                    <button type="button" class="hms-btn hms-btn-sm hms-btn-outline foc-view-btn" data-bs-toggle="modal" data-bs-target="#focViewModal{{ $foc->id }}">
                                        <i class="fa-solid fa-eye"></i> View
                                    </button>

                                    @haspermission('opd.foc.accept')
                                        <form method="POST" action="{{ route('hospital.foc.accept', ['slug' => $slug, 'id' => $foc->id]) }}" style="display:inline">
                                            @csrf
                                            <button type="submit" class="hms-btn hms-btn-sm hms-btn-success foc-accept-btn">Accept</button>
                                        </form>
                                    @else
                                        <span class="text-muted small">No access</span>
                                    @endhaspermission
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4" style="color:#94A3B8">No pending FOC requests</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@foreach($pendingFocRequests as $foc)
    <div class="modal fade" id="focViewModal{{ $foc->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:12px;border:1px solid #E2E8F0;box-shadow:0 2px 8px rgba(0,0,0,.08)">
                <div class="modal-header" style="background:#1B4F72;color:#fff">
                    <h5 class="modal-title">FOC Request Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="font-family:'Inter',sans-serif">
                    <p class="mb-1"><strong>Patient:</strong> {{ $foc->patient?->full_name ?? '—' }}</p>
                    <p class="mb-1"><strong>MRD:</strong> {{ $foc->patient?->patient_code ?? '—' }}</p>
                    <p class="mb-1"><strong>Doctor:</strong> {{ $foc->doctor?->name ?? '—' }}</p>
                    <p class="mb-1"><strong>Fee to Waive:</strong> ₹{{ number_format((float) $foc->foc_fee, 2) }}</p>
                    <p class="mb-0"><strong>Reason:</strong><br>{{ $foc->reason ?: 'No reason provided.' }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="hms-btn hms-btn-sm hms-btn-outline" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endif

{{-- ════════════════════════════════════════════════════════════════════════
     ROW 3: Quick Actions — full width, qa-pill grid inside bento-card
     Icons have explicit font-size so they render correctly.
     @haspermission gates preserved exactly.
════════════════════════════════════════════════════════════════════════════ --}}
<div class="row">
    <div class="col-12">
        <div class="bento-card">
            <div class="bento-header">
                <h3 class="bento-title"><i class="fa-solid fa-bolt me-1"></i> Quick Actions</h3>
            </div>
            <div class="qa-grid">
                @haspermission('opd.patient.register')
                    <a href="{{ route('hospital.patients.create', $slug) }}" class="qa-pill">
                        <i class="fa-solid fa-user-plus" style="font-size:24px;color:#1B4F72"></i>
                        <span>Add Patient</span>
                    </a>
                @endhaspermission
                @haspermission('opd.patient.view')
                    <a href="{{ route('hospital.patients.index', $slug) }}" class="qa-pill">
                        <i class="fa-solid fa-users" style="font-size:24px;color:#1ABC9C"></i>
                        <span>All Patients</span>
                    </a>
                @endhaspermission
                @haspermission('ot.booking.create')
                    <a href="{{ route('hospital.ot.index', $slug) }}" class="qa-pill">
                        <i class="fa-solid fa-scalpel" style="font-size:24px;color:#8E44AD"></i>
                        <span>OT Bookings</span>
                    </a>
                @endhaspermission
                @haspermission('opd.foc.create')
                    <a href="{{ route('hospital.foc.index', $slug) }}" class="qa-pill">
                        <i class="fa-solid fa-hand-holding-heart" style="font-size:24px;color:#C0392B"></i>
                        <span>FOC Cases</span>
                    </a>
                @endhaspermission
                @haspermission('opd.reports.view')
                    <a href="{{ route('hospital.reports.index', $slug) }}" class="qa-pill">
                        <i class="fa-solid fa-chart-bar" style="font-size:24px;color:#27AE60"></i>
                        <span>Reports</span>
                    </a>
                @endhaspermission
                @haspermission('master.roles')
                    <a href="{{ route('hospital.roles.index', $slug) }}" class="qa-pill">
                        <i class="fa-solid fa-shield-halved" style="font-size:24px;color:#E67E22"></i>
                        <span>Roles</span>
                    </a>
                @endhaspermission
                @haspermission('master.doctors')
                    <a href="{{ route('hospital.users.create', ['slug' => $slug]) }}" class="qa-pill">
                        <i class="fa-solid fa-user-gear" style="font-size:24px;color:#34495E"></i>
                        <span>Add User</span>
                    </a>
                @endhaspermission
                @haspermission('settings.hospital')
                    <a href="{{ route('hospital.settings.index', $slug) }}" class="qa-pill">
                        <i class="fa-solid fa-gear" style="font-size:24px;color:#34495E"></i>
                        <span>Settings</span>
                    </a>
                @endhaspermission
            </div>
        </div>
    </div>
</div>{{-- /row quick actions --}}

@endif {{-- /hasAnyData --}}

</div>{{-- /bento-page --}}
@endsection
