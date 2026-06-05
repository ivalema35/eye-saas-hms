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

/* ==================== FIRST IMAGE STYLE - 4 METRIC CARDS ==================== */
/* These styles are for the 4 cards: Today's Patients, Pending Exams, Today Revenue, OT Today */
/* No conflict with existing .card or .data-tile styles */




/* ==================== DOCTOR PROFILE CARDS ==================== */
.doctor-cards-container {
    display: flex;
    flex-wrap: nowrap; /* આડા સ્ક્રોલ માટે */
    overflow-x: auto;
    gap: 15px;
    padding-bottom: 10px;
}
/* સ્ક્રોલબારની ડિઝાઈન */
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
    margin-top:8px;
    gap:6px;
}

.metric-card-item .doc-badge{
    font-size:10px;
    padding:2px 8px;
}

.metric-card-item .doc-badge.active{
    background:#1B4F72;
    color:#fff;
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
</style>
@endpush

@section('content')
<div class="doctor-page-wrap">

    {{-- Subscription Alert --}}
    @if($subscriptionDaysLeft !== null && $subscriptionDaysLeft <= 14)
        <div class="alert {{ $subscriptionDaysLeft <= 3 ? 'alert-danger' : 'alert-warning' }} d-flex align-items-center gap-2 rounded-3 mb-4 shadow-sm">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>
                Subscription expires in <strong>{{ $subscriptionDaysLeft }} days</strong>. Please renew soon.
            </span>
        </div>
    @endif

    {{-- Top Section: Today All Data (Left) & Doctors Component (Right) --}}
    {{-- ==================== FIRST IMAGE: 4 METRIC CARDS ==================== --}}
<div class="metric-grid">

    {{-- Card 1: TODAY'S ASSIGNED --}}
    <div class="metric-card-item d-flex flex-column justify-content-between position-relative pb-2">
        <div>
            {{-- નાનો બ્લુ આઈકોન --}}
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
            {{-- નાનો ઓરેન્જ આઈકોન --}}
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
            {{-- નાનો ગ્રીન આઈકોન --}}
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
            {{-- નાનો પર્પલ આઈકોન --}}
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
                                
                                {{-- માત્ર રેગ્યુલર ડૉક્ટર માટે જ Primary/Secondary બતાવવા માટેની શરત --}}
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
                                    {{-- OT Doctor માટે માત્ર Assigned નો બેઝ --}}
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
        <th style="width: 50px;">#</th>
        <th class="text-start">Patient Name</th>
        <th>Reception Time</th>
        <th>Primary Time</th>
        <th>Age</th>
        <th>Action</th>
    </tr>
</thead>
<tbody>
    @forelse($primaryQueue ?? [] as $i => $patient)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td class="text-start fw-semibold" style="color: #1B4F72;">
                {{ $patient->first_name }} {{ $patient->last_name }}
            </td>
            <td>{{ $patient->created_at->format('h:i A') }}</td>
            <td>{{ $patient->primary_done_at ? \Carbon\Carbon::parse($patient->primary_done_at)->format('h:i A') : '-' }}</td>
            <td>{{ $patient->age }}</td>
            <td>
                <a href="{{ route('hospital.exam.primary.show', ['slug' => $slug, 'id' => $patient->id]) }}" class="btn btn-sm text-white px-3 fw-semibold" style="background-color: #1B4F72; border-radius: 4px;">
                    Examine
                </a>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="py-4 text-muted bg-light fw-medium">No Data Found</td>
        </tr>
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
                                    <th style="width: 50px;">#</th>
                                    <th class="text-start">Patient Name</th>
                                    <th>Reception Time</th>
                                    <th>Primary Time</th>
                                    <th>Age</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($secondaryQueue ?? [] as $i => $patient)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td class="text-start fw-semibold" style="color: #1B4F72;">
                                            {{ $patient->first_name }} {{ $patient->last_name }}
                                        </td>
                                        <td>{{ $patient->created_at->format('h:i A') }}</td>
                                        <td>{{ $patient->primary_done_at ? \Carbon\Carbon::parse($patient->primary_done_at)->format('h:i A') : '-' }}</td>
                                        <td>{{ $patient->age }}</td>
                                        <td>
                                            <a href="{{ route('hospital.exam.secondary.show', ['slug' => $slug, 'id' => $patient->id]) }}" class="btn btn-sm text-white px-3 fw-semibold" style="background-color: #1B4F72; border-radius: 4px;">
                                                Examine
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-4 text-muted bg-light fw-medium">No Data Found</td>
                                    </tr>
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