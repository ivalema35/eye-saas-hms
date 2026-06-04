@extends('hospital.layouts.app')
@section('title', 'Doctor Dashboard')
@section('page-header', 'Dashboard')

@push('styles')
<style>
.doctor-page-wrap {
    background: #ffffff;
    padding: 1.5rem;
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

.metric-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.metric-card-item {
    flex: 1;
    min-width: 180px;
    background: #ffffff;
    border-radius: 16px;
    padding: 1rem 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.03);
    border: 1px solid #eef2f6;
    transition: all 0.2s ease;
}

.metric-card-item:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    transform: translateY(-2px);
}

.metric-title {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.5px;
    color: #6c7e8f;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.metric-number {
    font-size: 32px;
    font-weight: 800;
    color: #1B4F72;
    line-height: 1.2;
    margin-bottom: 6px;
}

.metric-subtext {
    font-size: 11px;
    color: #8a9aa8;
    font-weight: 500;
}

.metric-subtext-badge {
    background: #e9f5ef;
    color: #2c7a4d;
    padding: 2px 10px;
    border-radius: 30px;
    display: inline-block;
    font-size: 11px;
    font-weight: 600;
}

.metric-ot-stats {
    font-size: 11px;
    color: #5e7e8c;
    margin-top: 6px;
}

/* Responsive */
@media (max-width: 768px) {
    .metric-card-item {
        min-width: calc(50% - 0.5rem);
    }
    .metric-number {
        font-size: 26px;
    }
}

@media (max-width: 480px) {
    .metric-card-item {
        min-width: 100%;
    }
}

/* ==================== 4 CARDS - LIGHT BLUE BORDER & HOVER SHADOW ==================== */

.metric-card-item {
    background: #ffffff;
    border-radius: 16px;
    padding: 1rem 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.03);
    border: 1.5px solid #cde5f5;  /* Light blue border */
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}

.metric-card-item:hover {
    border-color: #7bb3d9;  /* Slightly darker light blue on hover */
    box-shadow: 0 10px 25px rgba(27, 79, 114, 0.15), 0 4px 8px rgba(27, 79, 114, 0.08);  /* Light blue shadow */
    transform: translateY(-3px);
}

/* Optional: Extra light blue glow effect on hover */
.metric-card-item:hover::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(27, 79, 114, 0.02);
    border-radius: 16px;
    pointer-events: none;
}

/* Title styling - keep as is */
.metric-title {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.5px;
    color: #6c7e8f;
    text-transform: uppercase;
    margin-bottom: 8px;
}

/* Number styling */
.metric-number {
    font-size: 32px;
    font-weight: 800;
    color: #1B4F72;
    line-height: 1.2;
    margin-bottom: 6px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .metric-card-item {
        min-width: calc(50% - 0.5rem);
    }
    .metric-number {
        font-size: 26px;
    }
}

@media (max-width: 480px) {
    .metric-card-item {
        min-width: 100%;
    }
}
</style>
@endpush

@section('content')
<div class="doctor-page-wrap">

    {{-- Subscription Alert --}}
    @if($subscriptionDaysLeft !== null && $subscriptionDaysLeft <= 14)
        <div class="alert {{ $subscriptionDaysLeft <= 3 ? 'alert-danger' : 'alert-warning' }} d-flex align-items-center gap-2 rounded-3 mb-4 shadow-sm" style="margin-top:25px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>
                Subscription expires in <strong>{{ $subscriptionDaysLeft }} days</strong>. Please renew soon.
            </span>
        </div>
    @endif

    {{-- Top Section: Today All Data (Left) & Doctors Component (Right) --}}
    {{-- ==================== FIRST IMAGE: 4 METRIC CARDS ==================== --}}
<div class="metric-grid">
    {{-- Card 1: TODAY'S PATIENTS --}}
    <div class="metric-card-item">
        <div class="metric-title">📋 Doctor Profile</div>
        <div class="metric-number">0</div>
    </div>

    {{-- Card 2: PENDING EXAMS --}}
    <div class="metric-card-item">
        <div class="metric-title">⏳ Primary Checkup</div>
        <div class="metric-number">0</div>
    </div>

    {{-- Card 3: TODAY REVENUE --}}
    <div class="metric-card-item">
        <div class="metric-title">💰 Secondary</div>
        <div class="metric-number">0</div>
    </div>

    {{-- Card 4: OT TODAY --}}
    <div class="metric-card-item">
        <div class="metric-title">🏥 Report</div>
        <div class="metric-number">0</div>
    </div>
</div>
    <div class="row g-4 mb-4">
    

        {{-- Right Grid Box: Empty Doctors Block --}}
        <div class="col-lg-12 col-md-7">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; background: #ebf5fbeb;">
                <h6 class="fw-bold mb-2" style="color: #1B4F72;">Doctors</h6>
                <hr class="my-2" style="border-color: rgba(27, 79, 114, 0.15)">
                <div class="text-muted small">No other duty records live right now.</div>
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
                                    <th>Age</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($primaryQueue ?? [] as $i => $patient)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td class="text-start fw-semibold" style="color: #1B4F72;">{{ $patient->full_name }}</td>
                                        <td>{{ $patient->created_at->format('h:i A') }}</td>
                                        <td>{{ $patient->age }}</td>
                                        <td>
                                            <a href="{{ route('hospital.exam.primary.show', ['slug' => $slug, 'id' => $patient->id]) }}" class="btn btn-sm text-white px-3 fw-semibold" style="background-color: #1B4F72; border-radius: 4px;">
                                                Examine
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-4 text-muted bg-light fw-medium">No Data Found</td>
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
                                <tr>
                                    <td colspan="6" class="py-4 text-muted bg-light fw-medium">No Data Found</td>
                                </tr>
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