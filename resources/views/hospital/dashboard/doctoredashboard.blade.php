@extends('hospital.layouts.app')
@section('title', 'Doctor Dashboard')

@push('styles')
<style>
.doctor-page-wrap {
    background: #ffffff;
    padding: 0 1.5rem 1.5rem 1.5rem;
    min-height: 100vh;
    font-family: system-ui, -apple-system, sans-serif;
}

/* Card visuals: soft shadow and rounded corners */
.doctor-page-wrap .card {
    border: none !important;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(11,35,50,0.06);
}

/* Top headers inside cards (override inline black header) */
.doctor-page-wrap .card > .px-3.py-2,
.doctor-page-wrap .card > .px-3.py-2.text-white {
    background: var(--shell-secondary) !important;
    color: #ffffff !important;
    border-radius: 10px 10px 0 0;
    padding: .6rem 1rem !important;
    font-weight: 800;
}

/* Small stat tiles */
.doctor-page-wrap .card .p-2.border.rounded {
    background: #ffffff;
    border: 1px solid rgba(27,79,114,0.06) !important;
}
.doctor-page-wrap .card .p-2 .d-block {
    color: rgba(0,0,0,0.6);
}

/* Doctors block: subtle primary tint */
.doctor-page-wrap .card.bg-tinted {
    background: var(--shell-primary) !important;
}

/* Table header and controls */
.doctor-page-wrap table thead {
    background: rgba(27,79,114,0.04) !important;
}
.doctor-page-wrap .form-control,
.doctor-page-wrap .form-select {
    border-radius: 6px;
}

/* Buttons */
.doctor-page-wrap .btn-primary,
.doctor-page-wrap .btn-examine {
    background: var(--shell-secondary) !important;
    border-color: var(--shell-secondary) !important;
}

/* ==================== DOCTOR PROFILE CARDS ==================== */
.doctor-cards-container {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    gap: 15px;
    padding-bottom: 10px;
}

.doctor-cards-container::-webkit-scrollbar {
    height: 6px;
}
.doctor-cards-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.doc-profile-card {
    min-width: 280px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.doc-profile-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(27, 79, 114, 0.08);
    border-color: #cde5f5;
}
.doc-avatar {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    background: #eef2f6;
    color: #1B4F72;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    font-weight: 800;
    text-transform: uppercase;
    flex-shrink: 0;
}
.doc-info {
    flex-grow: 1;
}
.doc-name {
    font-size: 15px;
    font-weight: 800;
    color: #1B4F72;
    margin-bottom: 2px;
    text-transform: capitalize;
}
.doc-assigned {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 8px;
}
.doc-badges {
    display: flex;
    gap: 8px;
}
.doc-badge {
    font-size: 11px;
    padding: 3px 10px;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    font-weight: 600;
}
.doc-badge.active {
    background: #1B4F72;
    color: #ffffff;
    border-color: #1B4F72;
}

/* ==================== TOP 4 DASHBOARD CARDS ==================== */

.metric-grid{
    display:flex;
    gap:16px;
    margin-bottom:24px;
}

.metric-card-item{
    flex:1;
    min-width:0;
    min-height:120px;
    background:#fff;
    border:1px solid #cde5f5;
    border-radius:14px;
    padding:14px 18px;
    position:relative;
    transition:all .3s ease;
    cursor:pointer;
    box-shadow:0 2px 8px rgba(0,0,0,.04);
}

.metric-card-item:hover{
    border-color:#7bb3d9;
    box-shadow:0 10px 25px rgba(27,79,114,.12);
    transform:translateY(-2px);
}

.metric-title{
    font-size:13px;
    font-weight:700;
    letter-spacing:1px;
    text-transform:uppercase;
    color:#64748b;
    margin-top:8px;
}

.metric-number{
    font-size:22px;
    font-weight:800;
    color:#1B4F72;
    line-height:1;
    margin-bottom:0;
}

.metric-card-item .doc-badges{
    gap:6px;
}

.metric-card-item .doc-badge{
    font-size:10px;
    padding:2px 8px;
}

.metric-icon{
    width:32px;
    height:32px;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
}

.metric-link{
    font-size:15px;
    font-weight:700;
    color:#111827;
    text-decoration:none;
}

@media(max-width:768px){
    .metric-grid{
        flex-wrap:wrap;
    }

    .metric-card-item{
        width:calc(50% - 8px);
        flex:none;
    }
}

@media(max-width:480px){
    .metric-card-item{
        width:100%;
    }
}

/* ── Wait Status Pill ── */
.wait-pill { display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 10px 3px 3px;font-weight:700;white-space:nowrap;transition:background .4s,box-shadow .4s;vertical-align:middle; }
.wait-pill.wait-green  { background:rgba(22,163,74,.10);  box-shadow:0 0 0 1px rgba(22,163,74,.25); }
.wait-pill.wait-orange { background:rgba(234,88,12,.10);  box-shadow:0 0 0 1px rgba(234,88,12,.25); }
.wait-pill.wait-red    { background:rgba(220,38,38,.10);  box-shadow:0 0 0 1px rgba(220,38,38,.25); }
.wait-pill.wait-fire   { background:rgba(220,38,38,.10);  box-shadow:0 0 0 1px rgba(220,38,38,.35); animation:fire-glow 1s ease-in-out infinite alternate; }
@keyframes fire-glow { from{box-shadow:0 0 0 1px rgba(220,38,38,.35),0 0 6px rgba(234,88,12,.4);}to{box-shadow:0 0 0 2px rgba(220,38,38,.55),0 0 12px rgba(234,88,12,.6);} }
.wp-r { display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;font-size:.65rem;font-weight:900;color:#fff;flex-shrink:0; }
.wait-green  .wp-r { background:#16a34a; }
.wait-orange .wp-r { background:#ea580c; }
.wait-red    .wp-r { background:#dc2626; }
.wait-fire   .wp-r { background:linear-gradient(135deg,#dc2626,#ea580c); }
.wp-time { font-size:.72rem;font-weight:700; }
.wait-green  .wp-time { color:#15803d; }
.wait-orange .wp-time { color:#c2410c; }
.wait-red    .wp-time { color:#b91c1c; }
.wait-fire   .wp-time { color:#dc2626; }
</style>
@endpush

@section('content')
<div class="doctor-page-wrap">

    @php
        $drIndexLabel    = $drIndexLabel    ?? 'DR Index No';
        $waitStatusLabel = $waitStatusLabel ?? 'Wait Status';
        $wGreen          = (int) hospital_setting('wait_green_max',  30);
        $wOrange         = (int) hospital_setting('wait_orange_max', 60);
        $wRed            = (int) hospital_setting('wait_red_max',   120);
    @endphp

    {{-- Subscription Alert --}}
    @if($subscriptionDaysLeft !== null && $subscriptionDaysLeft <= 14)
        <div class="alert {{ $subscriptionDaysLeft <= 3 ? 'alert-danger' : 'alert-warning' }} d-flex align-items-center gap-2 rounded-3 mb-4 shadow-sm">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>
                Subscription expires in <strong>{{ $subscriptionDaysLeft }} days</strong>. Please renew soon.
            </span>
        </div>
    @endif

        <div class="metric-grid">
            {{-- Card 1: TODAY'S ASSIGNED --}}
            <div class="metric-card-item d-flex flex-column justify-content-between position-relative pb-2">
                <div>
        <div class="metric-icon" style="background:#e0f2fe;">
            <i class="bi bi-people-fill" style="color:#0284c7;font-size:14px;"></i>
        </div>
                    <div class="metric-title text-uppercase" style="letter-spacing: 1px;">Today's Assigned</div>
                </div>
                <div>
                    <div class="metric-number">{{ $doctorAssignedPatients ?? 0 }}</div>
                    
                    <div class="doc-badges mt-1">
                        <span class="doc-badge {{ ($doctorPrimaryDone ?? 0) > 0 ? 'active' : '' }}" style="font-size: 10px; padding: 2px 8px;">
                            Primary {{ $doctorPrimaryDone ?? 0 }}
                        </span>
                        <span class="doc-badge {{ ($doctorSecondaryDone ?? 0) > 0 ? 'active' : '' }}" style="font-size: 10px; padding: 2px 8px;">
                            Secondary {{ $doctorSecondaryDone ?? 0 }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Card 2: PENDING EXAMS (Primary Done) --}}
            <div class="metric-card-item d-flex flex-column justify-content-between position-relative pb-2">
                <div>
        <div class="metric-icon" style="background:#ffedd5;">
            <i class="bi bi-file-earmark-medical-fill" style="color:#ea580c;font-size:14px;"></i>
        </div>
                    <div class="metric-title text-uppercase" style="letter-spacing: 1px;">Primary Done</div>
                </div>
                <div>
                    <div class="metric-number mb-0">{{ $doctorPrimaryDone ?? 0 }}</div>
                </div>
            </div>

            {{-- Card 3: SECONDARY DONE --}}
            <div class="metric-card-item d-flex flex-column justify-content-between position-relative pb-2">
                <div>
                    <div class="metric-icon" style="background:#dcfce7;">
                        <i class="bi bi-check-circle-fill" style="color:#16a34a;font-size:14px;"></i>
                    </div>
                    <div class="metric-title text-uppercase" style="letter-spacing: 1px;">Secondary Done</div>
                </div>
                <div>
                    <div class="metric-number mb-0">{{ $doctorSecondaryDone ?? 0 }}</div>
                </div>
            </div>

            {{-- Card 4: REPORT --}}
            <div class="metric-card-item d-flex flex-column justify-content-between position-relative pb-2">
                <div>
                    <div class="metric-icon" style="background:#f8f0fc;">
                        <i class="bi bi-bar-chart-fill" style="color:#7b2cbf;font-size:14px;"></i>
                    </div>
                    <div class="metric-title text-uppercase" style="letter-spacing: 1px;">Reports</div>
                </div>
                <div class="mt-2">
                    <a href="{{ route('hospital.reports.index', ['slug' => $slug]) }}" class="text-decoration-none text-dark d-flex align-items-center fw-bolder" style="font-size: 14px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                        View <i class="bi bi-arrow-right ms-1" style="font-size: 14px;"></i>
                    </a>
                </div>
            </div>
        </div>


    <div class="row g-4 mb-4">
    {{-- Right Grid Box: Doctors Block --}}
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; background: #ebf5fbeb;">
                <h6 class="fw-bold mb-3" style="color: #1B4F72;">All Doctors</h6>
                
                <div class="doctor-cards-container">
                    @forelse($doctorCards ?? [] as $doc)
                        @php $isSelf = $doc->id === auth('hospital_user')->id(); @endphp
                        <div class="doc-profile-card" style="{{ $isSelf ? 'border:2px solid #1B4F72; background:#f0f6fb;' : '' }}">
                            {{-- Avatar --}}
                            <div class="doc-avatar" style="{{ $isSelf ? 'background:#1B4F72; color:#fff;' : '' }}">
                                {{ substr($doc->name, 0, 1) }}
                            </div>

                            <div class="doc-info">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="doc-name">{{ $doc->name }}</div>
                                    @if($isSelf)
                                        <span style="font-size:10px;font-weight:700;background:#1B4F72;color:#fff;padding:2px 8px;border-radius:20px;">You</span>
                                    @endif
                                </div>
                                
                                @if($doc->role?->slug === 'ot_doctor')
                                    <div class="doc-assigned">OT Doctor</div>
                                @else
                                    <div class="doc-assigned">{{ $doc->assigned_today ?? 0 }} Assigned</div>
                                @endif
                                @if($doc->role?->slug !== 'ot_doctor')
                                    <div class="doc-badges">
                                        <span class="doc-badge {{ ($doc->primary_count ?? 0) > 0 ? 'active' : '' }}">
                                            Primary Exam {{ $doc->primary_count ?? 0 }}
                                        </span>
                                        <span class="doc-badge {{ ($doc->secondary_count ?? 0) > 0 ? 'active' : '' }}">
                                            Secondary Exam {{ $doc->secondary_count ?? 0 }}
                                        </span>
                                    </div>
                                @else
                                    {{-- OT Doctor Assigned --}}
                                    <div class="doc-badges">
                                        <span class="doc-badge">
                                            Assigned {{ $doc->assigned_today ?? 0 }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-muted small p-2">No other duty records live right now.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom Section: Side-by-Side Split Tables --}}
    <div class="row g-4">
        
        {{-- Left Table Panel: Primary Patient --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 10px;">
                <div class="px-3 py-2 text-white fw-bold fs-5" style="background-color: #000000; font-size: 16px;">
                    Primary Patient
                </div>
                <div class="p-3 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-1 text-secondary small">
                            Show entries 
                            <select class="form-select form-select-sm w-auto"><option>10</option></select>
                        </div>
                        <div class="d-flex align-items-center gap-1 text-secondary small">
                            search <input type="search" class="form-control form-control-sm w-auto" style="border: 1px solid #cbd5e1;">
                        </div>
                    </div>
                    <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center mb-0" style="font-size: 13.5px; border-color: #e2e8f0;">
                        <thead class="table-light text-secondary">
                            <tr>
                                <th>{{ $drIndexLabel }}</th>
                                <th class="text-start">Patient Name</th>
                                <th>Age</th>
                                <th>City</th>
                                <th>{{ $waitStatusLabel }}</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($primaryQueue ?? [] as $i => $patient)
                                <tr>
                                    <td><span class="badge rounded-pill text-bg-light border px-3 py-2 fw-semibold">{{ $patient->display_doctor_index ?? '-' }}</span></td>
                                    <td class="text-start fw-semibold" style="color: #1B4F72;">
                                        {{ $patient->first_name }} {{ $patient->last_name }}
                                    </td>
                                    <td>{{ $patient->age }}</td>
                                    <td>{{ $patient->location->city ?? '-' }}</td>
                                    <td>
                                        @php
                                            $wMins = (int) $patient->created_at->diffInMinutes(now());
                                            $wCls  = $wMins < $wGreen ? 'wait-green' : ($wMins < $wOrange ? 'wait-orange' : ($wMins < $wRed ? 'wait-red' : 'wait-fire'));
                                            $wFmt  = $wMins < 60 ? $wMins.'m' : floor($wMins/60).'h'.($wMins%60>0?' '.($wMins%60).'m':'');
                                        @endphp
                                        <span class="wait-pill {{ $wCls }}" data-wait-from="{{ $patient->created_at->toIso8601String() }}">
                                            <span class="wp-r">R</span>
                                            <span class="wp-time">{{ $wFmt }}</span>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('hospital.exam.primary.show', ['slug' => $slug, 'id' => $patient->id]) }}" 
                                        class="btn btn-sm text-white px-3 fw-semibold" style="background-color: #1B4F72; border-radius: 4px;">
                                            Examine
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-4 text-muted bg-light">No Data Found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 small text-muted">
                        <div>Showing 1 to entries of 0 entries</div>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-secondary px-3" disabled>Previous</button>
                            <button class="btn btn-sm btn-outline-secondary px-3" disabled>Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Table Panel: Secondary Patient --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 10px;">
                <div class="px-3 py-2 text-white fw-bold fs-5" style="background-color: #000000; font-size: 16px;">
                    Secondary Patient
                </div>
                <div class="p-3 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-1 text-secondary small">
                            Show entries 
                            <select class="form-select form-select-sm w-auto"><option>10</option></select>
                        </div>
                        <div class="d-flex align-items-center gap-1 text-secondary small">
                            search <input type="search" class="form-control form-control-sm w-auto" style="border: 1px solid #cbd5e1;">
                        </div>
                    </div>
                    <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center mb-0" style="font-size: 13.5px; border-color: #e2e8f0;">
                        <thead class="table-light text-secondary">
                            <tr>
                                <th>{{ $drIndexLabel }}</th>
                                <th class="text-start">Patient Name</th>
                                <th>Age</th>
                                <th>City</th>
                                <th>{{ $waitStatusLabel }}</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($secondaryQueue ?? [] as $i => $patient)
                                <tr>
                                    <td><span class="badge rounded-pill text-bg-light border px-3 py-2 fw-semibold">{{ $patient->display_doctor_index ?? '-' }}</span></td>
                                    <td class="text-start fw-semibold" style="color: #1B4F72;">
                                        {{ $patient->first_name }} {{ $patient->last_name }}
                                    </td>
                                    <td>{{ $patient->age }}</td>
                                    <td>{{ $patient->location->city ?? '-' }}</td>
                                    <td>
                                        @php
                                            $wMins = (int) $patient->created_at->diffInMinutes(now());
                                            $wCls  = $wMins < $wGreen ? 'wait-green' : ($wMins < $wOrange ? 'wait-orange' : ($wMins < $wRed ? 'wait-red' : 'wait-fire'));
                                            $wFmt  = $wMins < 60 ? $wMins.'m' : floor($wMins/60).'h'.($wMins%60>0?' '.($wMins%60).'m':'');
                                        @endphp
                                        <span class="wait-pill {{ $wCls }}" data-wait-from="{{ $patient->created_at->toIso8601String() }}">
                                            <span class="wp-r">R</span>
                                            <span class="wp-time">{{ $wFmt }}</span>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('hospital.exam.secondary.show', ['slug' => $slug, 'id' => $patient->id]) }}" 
                                        class="btn btn-sm text-white px-3 fw-semibold" style="background-color: #1B4F72; border-radius: 4px;">
                                            Examine
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-4 text-muted bg-light">No Data Found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 small text-muted">
                        <div>Showing 1 to entries of 0 entries</div>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-secondary px-3" disabled>Previous</button>
                            <button class="btn btn-sm btn-outline-secondary px-3" disabled>Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const W = { green: {{ $wGreen }}, orange: {{ $wOrange }}, red: {{ $wRed }} };
    function getWaitClass(m) {
        return m < W.green ? 'wait-green' : m < W.orange ? 'wait-orange' : m < W.red ? 'wait-red' : 'wait-fire';
    }
    function fmtTime(m) {
        return m < 60 ? m + 'm' : Math.floor(m / 60) + 'h' + (m % 60 > 0 ? ' ' + (m % 60) + 'm' : '');
    }
    function updateWaitPills() {
        const now = Date.now();
        document.querySelectorAll('.wait-pill[data-wait-from]').forEach(function (pill) {
            const mins = Math.floor((now - new Date(pill.dataset.waitFrom).getTime()) / 60000);
            pill.className = 'wait-pill ' + getWaitClass(mins);
            const t = pill.querySelector('.wp-time');
            if (t) t.textContent = fmtTime(mins);
        });
    }
    updateWaitPills();
    setInterval(updateWaitPills, 30000);
})();
</script>
@endpush