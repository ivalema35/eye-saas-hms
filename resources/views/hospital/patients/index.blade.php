@extends('hospital.layouts.app')
@section('title', 'Patients')
@section('page-header', $showAll ? 'All Patients' : "Today's Patients")

@section('page-actions')
    <a href="{{ route('hospital.patients.create', ['slug' => $slug]) }}" class="hms-btn hms-btn-primary patients-top-action">
        <i class="bi bi-person-plus"></i> Walk-in
    </a>
    <a href="{{ route('hospital.patients.create-phone', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline patients-top-action">
        <i class="bi bi-telephone"></i> Phone Appt
    </a>
@endsection

@push('styles')
<style>
/* Modern Professional Design - Theme aligned to hospital shell */
:root {
    --patients-primary: #1B4F72;
    --patients-primary-soft: rgba(27, 79, 114, 0.08);
    --patients-primary-soft-2: rgba(27, 79, 114, 0.14);
    --patients-primary-soft-3: rgba(27, 79, 114, 0.18);
    --patients-accent: #1ABC9C;
    --patients-accent-soft: rgba(26, 188, 156, 0.12);
    --patients-surface: #ffffff;
    --patients-surface-soft: #f7fbfe;
    --patients-surface-muted: #eef5fa;
    --patients-border: rgba(27, 79, 114, 0.12);
    --patients-border-strong: rgba(27, 79, 114, 0.18);
    --patients-text: #17324d;
    --patients-muted: rgba(27, 79, 114, 0.68);
    --patients-shadow: 0 10px 30px rgba(27, 79, 114, 0.08);
}

.patients-index-page {
    padding: 0 0 2rem 0;
}

/* Stats Cards */
.stat-card {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(247, 251, 254, 0.98) 100%);
    border-radius: 20px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: var(--patients-shadow);
    transition: all 0.3s ease;
    border: 1px solid var(--patients-border);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 34px rgba(27, 79, 114, 0.12);
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.bg-primary-soft {
    background: linear-gradient(135deg, rgba(27, 79, 114, 0.10) 0%, rgba(27, 79, 114, 0.16) 100%);
    color: var(--patients-primary);
}

.bg-warning-soft {
    background: linear-gradient(135deg, rgba(26, 188, 156, 0.12) 0%, rgba(26, 188, 156, 0.20) 100%);
    color: var(--patients-accent);
}

.bg-info-soft {
    background: linear-gradient(135deg, rgba(27, 79, 114, 0.08) 0%, rgba(26, 188, 156, 0.10) 100%);
    color: var(--patients-primary);
}

.bg-success-soft {
    background: linear-gradient(135deg, rgba(27, 79, 114, 0.10) 0%, rgba(27, 79, 114, 0.18) 100%);
    color: var(--patients-primary);
}

.stat-info {
    flex: 1;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
    color: var(--patients-text);
    line-height: 1.2;
}

.stat-label {
    font-size: 13px;
    color: var(--patients-muted);
    margin: 4px 0 0 0;
    font-weight: 500;
}

/* Modern Card */
.modern-card {
    background: var(--patients-surface);
    border-radius: 24px;
    box-shadow: var(--patients-shadow);
    overflow: hidden;
    border: 1px solid var(--patients-border);
}

.card-header-modern {
    padding: 1.75rem 2rem;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(247, 251, 254, 0.98) 100%);
    border-bottom: 1px solid var(--patients-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--patients-primary) 0%, #0f3853 100%);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.header-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--patients-text);
    margin: 0;
}

.header-subtitle {
    font-size: 13px;
    color: var(--patients-muted);
    margin: 4px 0 0 0;
}

/* Toggle Switch */
.toggle-switch {
    background: var(--patients-surface-muted);
    border-radius: 12px;
    padding: 4px;
    display: inline-flex;
    gap: 4px;
}

.toggle-option {
    padding: 8px 20px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    color: var(--patients-muted);
}

.toggle-option.active {
    background: white;
    color: var(--patients-primary);
    box-shadow: 0 1px 3px rgba(27, 79, 114, 0.14);
}

.toggle-option:hover {
    background: white;
    color: var(--patients-primary);
    text-decoration: none;
}

/* Search Section */
.search-section {
    padding: 1.5rem 2rem;
    background: linear-gradient(180deg, rgba(247, 251, 254, 0.92) 0%, rgba(238, 245, 250, 0.92) 100%);
    border-bottom: 1px solid var(--patients-border);
}

.search-wrapper {
    max-width: 500px;
    position: relative;
}

.search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--patients-muted);
    font-size: 18px;
    pointer-events: none;
}

.search-input {
    width: 100%;
    padding: 12px 16px 12px 42px;
    border: 1px solid var(--patients-border-strong);
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.2s ease;
    background: var(--patients-surface);
}

.search-input:focus {
    outline: none;
    border-color: var(--patients-primary);
    box-shadow: 0 0 0 3px rgba(27, 79, 114, 0.10);
}

.search-btn {
    position: absolute;
    right: 6px;
    top: 50%;
    transform: translateY(-50%);
    background: linear-gradient(135deg, var(--patients-primary) 0%, #0f3853 100%);
    color: white;
    border: none;
    padding: 6px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.search-btn:hover {
    background: linear-gradient(135deg, #0f3853 0%, var(--patients-primary) 100%);
    cursor: pointer;
}

/* Table Styles */
.table-wrapper {
    overflow-x: auto;
    padding: 0;
}

.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.modern-table thead tr {
    background: var(--patients-surface-muted);
}

.modern-table th {
    padding: 1rem 1.25rem;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--patients-muted);
    border-bottom: 1px solid var(--patients-border);
    text-align: left;
}

.modern-table td {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid rgba(27, 79, 114, 0.08);
    color: var(--patients-text);
    font-size: 14px;
    vertical-align: middle;
}

.modern-table tbody tr {
    transition: all 0.2s ease;
}

.modern-table tbody tr:hover {
    background: rgba(27, 79, 114, 0.03);
}

.serial {
    color: var(--patients-muted);
    font-weight: 500;
    width: 50px;
}

.mrd-badge {
    background: rgba(27, 79, 114, 0.08);
    padding: 6px 12px;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    font-weight: 600;
    color: var(--patients-primary);
}

/* Patient row */
.pt-row td { vertical-align: middle; padding: .75rem 1rem; }

.pt-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.pt-avatar {
    flex-shrink: 0;
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: var(--patients-primary);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    letter-spacing: .5px;
}

.pt-name {
    font-weight: 600;
    font-size: 14px;
    color: var(--patients-text);
    line-height: 1.3;
}

.pt-meta {
    font-size: 11.5px;
    color: var(--patients-muted);
    margin-top: 2px;
    line-height: 1.3;
}

.pt-doctor {
    font-size: 13px;
    color: var(--patients-text);
}

.pt-fee {
    font-size: 14px;
    font-weight: 700;
    color: var(--patients-primary);
    line-height: 1.3;
}

.type-badge {
    display: inline-block;
    background: var(--patients-surface-muted);
    padding: 2px 8px;
    border-radius: 5px;
    font-size: 11px;
    font-weight: 600;
    color: var(--patients-muted);
    margin-top: 3px;
    text-transform: capitalize;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 600;
    white-space: nowrap;
}

.status-badge.completed  { background: rgba(26,188,156,.13);  color: #0e9e82; }
.status-badge.in-progress{ background: rgba(27,79,114,.10);   color: var(--patients-primary); }
.status-badge.waiting    { background: rgba(255,165,0,.12);   color: #c07a00; }

/* ─── Compact Action Buttons ─── */
.pt-actions {
    display: flex;
    align-items: center;
    gap: 3px;
    flex-wrap: nowrap;
    white-space: nowrap;
}

.d-contents { display: contents; }

.pta-divider {
    width: 1px;
    height: 20px;
    background: var(--patients-border);
    margin: 0 3px;
    flex-shrink: 0;
}

/* base icon button */
.pta {
    width: 28px;
    height: 28px;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 7px;
    font-size: 13px;
    background: transparent;
    color: var(--patients-muted);
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: background .18s, color .18s;
    line-height: 1;
}
.pta:hover { text-decoration: none; }

/* View — teal */
.pta-view            { background: rgba(26,188,156,.12); color: #0e9e82; }
.pta-view:hover      { background: #1abc9c; color: #fff; }

/* Primary exam — navy */
.pta-primary         { background: rgba(27,79,114,.10); color: var(--patients-primary); }
.pta-primary:hover   { background: var(--patients-primary); color: #fff; }

/* Secondary exam — purple-ish */
.pta-secondary       { background: rgba(100,54,180,.10); color: #6436b4; }
.pta-secondary:hover { background: #6436b4; color: #fff; }

/* Done state (primary or secondary completed) */
.pta-done  { background: rgba(26,188,156,.10); color: #0e9e82; cursor: default; opacity: .75; }

/* Locked */
.pta-locked { background: var(--patients-surface-muted); color: var(--patients-muted); cursor: not-allowed; opacity: .55; }

/* Timer */
.pta-timer {
    background: rgba(255,152,0,.12);
    color: #e07b00;
    cursor: default;
    min-width: 58px;
    width: auto;
    padding: 0 6px;
    gap: 3px;
    border-radius: 7px;
}

/* Utility — history, print */
.pta-util       { background: transparent; color: var(--patients-muted); }
.pta-util:hover { background: rgba(27,79,114,.08); color: var(--patients-primary); }

/* Edit */
.pta-edit       { background: transparent; color: var(--patients-muted); }
.pta-edit:hover { background: var(--patients-primary); color: #fff; }

/* Delete */
.pta-delete       { background: transparent; color: var(--patients-muted); }
.pta-delete:hover { background: #dc3545; color: #fff; }

/* Check-in */
.pta-checkin       { background: rgba(39,174,96,.12); color: #27ae60; }
.pta-checkin:hover { background: #27ae60; color: #fff; }

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem !important;
}

.empty-icon {
    font-size: 64px;
    color: rgba(27, 79, 114, 0.22);
    margin-bottom: 1rem;
}

.empty-state h5 {
    font-size: 18px;
    color: var(--patients-text);
    margin: 0 0 0.5rem 0;
}

.empty-state p {
    font-size: 14px;
    color: var(--patients-muted);
    margin: 0;
}

/* Pagination */
.pagination-modern {
    padding: 1.5rem 2rem;
    border-top: 1px solid var(--patients-border);
    display: flex;
    justify-content: center;
}

.pagination-modern nav {
    display: inline-block;
}

/* Responsive */
@media (max-width: 768px) {
    .stat-card {
        padding: 1rem;
    }
    
    .stat-icon {
        width: 44px;
        height: 44px;
        font-size: 22px;
    }
    
    .stat-value {
        font-size: 22px;
    }
    
    .card-header-modern {
        padding: 1.25rem;
    }
    
    .search-section {
        padding: 1rem 1.25rem;
    }
    
    .modern-table th,
    .modern-table td {
        padding: 0.75rem 1rem;
    }
    
    .pt-actions { gap: 2px; }
    .pta { width: 26px; height: 26px; font-size: 12px; }
}

.patients-index-page .toggle-option i,
.patients-index-page .search-btn i,
.patients-index-page .status-badge i,
.patients-index-page .pta i {
    vertical-align: middle;
}

.patients-index-page .modern-table a,
.patients-index-page .modern-table button {
    transition: transform 160ms ease, box-shadow 160ms ease, background 160ms ease, color 160ms ease;
}

.patients-index-page .modern-table a:hover,
.patients-index-page .modern-table button:hover {
    transform: translateY(-1px);
}
</style>
@endpush

@section('content')
<div class="patients-index-page">
    <!-- Modern Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary-soft">
                    <i class="bi bi-person-arms-up"></i>
                </div>
                <div class="stat-info">
                    <h3 class="stat-value">{{ $patients->total() }}</h3>
                    <p class="stat-label">Total Patients</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning-soft">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-info">
                    <h3 class="stat-value">{{ $patients->where('secondary_done_at', null)->where('primary_done_at', null)->count() }}</h3>
                    <p class="stat-label">Waiting</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info-soft">
                    <i class="bi bi-clipboard2-check"></i>
                </div>
                <div class="stat-info">
                    <h3 class="stat-value">{{ $patients->where('primary_done_at', '!=', null)->where('secondary_done_at', null)->count() }}</h3>
                    <p class="stat-label">Primary Done</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success-soft">
                    <i class="bi bi-check2-all"></i>
                </div>
                <div class="stat-info">
                    <h3 class="stat-value">{{ $patients->where('secondary_done_at', '!=', null)->count() }}</h3>
                    <p class="stat-label">Completed</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="modern-card">
        <div class="card-header-modern">
            <div class="header-left">
                <div class="header-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <h4 class="header-title">{{ $showAll ? 'All Patients' : "Today's Patients" }}</h4>
                    <p class="header-subtitle">Manage and track patient records</p>
                </div>
            </div>
            
            <div class="header-actions">
                <div class="toggle-switch">
                    <a href="{{ route('hospital.patients.index', ['slug' => $slug]) }}" class="toggle-option {{ !$showAll ? 'active' : '' }}">
                        <i class="bi bi-calendar-day"></i> Today
                    </a>
                    <a href="{{ route('hospital.patients.index', ['slug' => $slug, 'all' => 1]) }}" class="toggle-option {{ $showAll ? 'active' : '' }}">
                        <i class="bi bi-list-ul"></i> All
                    </a>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-section">
            <form method="GET" action="{{ route('hospital.patients.index', ['slug' => $slug]) }}" class="search-form">
                @if($showAll)
                    <input type="hidden" name="all" value="1">
                @endif
                <div class="search-wrapper">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="search-input" placeholder="Search by name, MRD number, or contact...">
                    <button type="submit" class="search-btn">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Patients Table -->
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width:44px">#</th>
                        <th style="width:110px">MRD</th>
                        <th>Patient</th>
                        <th style="width:130px">Doctor</th>
                        <th style="width:110px">Fee / Type</th>
                        <th style="width:150px">Status</th>
                        <th style="width:220px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $i => $p)
                        @php
                            $primary          = $p->primaryExamination;
                            $secondary        = $p->secondaryExamination;
                            $isPrimaryDone    = $primary !== null;
                            $isSecondaryDone  = $secondary !== null;
                            $unlockTimeMs     = null;
                            $isDilating       = false;

                            if ($primary && ($primary->exam_data['dilate'] ?? 'No') === 'Yes' && $primary->dilation_time && ! $isSecondaryDone) {
                                $expectedUnlock = $primary->updated_at->copy()->addMinutes($primary->dilation_time);
                                if (now()->lessThan($expectedUnlock)) {
                                    $isDilating   = true;
                                    $unlockTimeMs = $expectedUnlock->timestamp * 1000;
                                }
                            }
                        @endphp
                        <tr class="pt-row">
                            {{-- # --}}
                            <td class="serial">{{ $patients->firstItem() + $i }}</td>

                            {{-- MRD --}}
                            <td><span class="mrd-badge">{{ $p->patient_code }}</span></td>

                            {{-- Patient: name + meta --}}
                            <td>
                                <div class="pt-info">
                                    <div class="pt-avatar">{{ strtoupper(substr($p->first_name,0,1)) }}</div>
                                    <div>
                                        <div class="pt-name">{{ $p->full_name }}</div>
                                        <div class="pt-meta">
                                            {{ $p->age }}y · {{ ucfirst($p->gender) }}
                                            @if($p->contact_no) · {{ $p->contact_no }} @endif
                                            @if($p->created_at) · {{ $p->created_at->format('h:i A') }} @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Doctor --}}
                            <td class="pt-doctor">{{ $p->doctor?->name ?? '—' }}</td>

                            {{-- Fee / Type --}}
                            <td>
                                <div class="pt-fee">₹{{ number_format($p->case_fee, 0) }}</div>
                                <span class="type-badge">{{ ucfirst($p->type) }}</span>
                            </td>

                            {{-- Status --}}
                            <td>
                                @if($p->secondary_done_at)
                                    <span class="status-badge completed"><i class="bi bi-check2-all"></i> Completed</span>
                                @elseif($p->primary_done_at)
                                    <span class="status-badge in-progress"><i class="bi bi-clipboard2-check"></i> Primary Done</span>
                                @else
                                    <span class="status-badge waiting"><i class="bi bi-hourglass-split"></i> Waiting</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td>
                                <div class="pt-actions">
                                    {{-- View --}}
                                    <button type="button" class="pta pta-view view-patient-btn" title="View Patient"
                                        data-id="{{ $p->id }}"
                                        data-mrd="{{ $p->patient_code }}"
                                        data-name="{{ $p->full_name }}"
                                        data-age="{{ $p->age }}"
                                        data-gender="{{ ucfirst($p->gender ?? '') }}"
                                        data-contact="{{ $p->contact_no }}"
                                        data-whatsapp="{{ $p->whatsapp_no }}"
                                        data-occupation="{{ $p->occupation }}"
                                        data-city="{{ $p->location?->city ?? $p->masterCity?->name }}"
                                        data-district="{{ $p->location?->district }}"
                                        data-state="{{ $p->location?->state }}"
                                        data-doctor="{{ $p->doctor?->name }}"
                                        data-case="{{ $p->caseType?->case_type ?? '' }}"
                                        data-fee="{{ number_format($p->case_fee, 0) }}"
                                        data-referrer="{{ $p->referrer?->name }}"
                                        data-appt="{{ $p->appointment_date?->format('d M Y') }}"
                                        data-registered="{{ $p->created_at->format('d M Y, h:i A') }}"
                                        data-status="{{ $p->secondary_done_at ? 'Secondary Done' : ($p->primary_done_at ? 'Primary Done' : 'Waiting') }}"
                                        data-primary-at="{{ $p->primary_done_at?->format('d M Y, h:i A') }}"
                                        data-secondary-at="{{ $p->secondary_done_at?->format('d M Y, h:i A') }}"
                                        data-edit-url="{{ route('hospital.patients.edit', ['slug' => $slug, 'patient' => $p->id]) }}">
                                        <i class="bi bi-info-circle-fill"></i>
                                    </button>

                                    <div class="pta-divider"></div>

                                    {{-- Primary Exam --}}
                                    @if($isPrimaryDone)
                                        <span class="pta pta-done" title="Primary Done"><i class="bi bi-clipboard2-check-fill"></i></span>
                                    @else
                                        <a href="{{ route('hospital.exam.primary.show', ['slug' => $slug, 'id' => $p->id]) }}"
                                           class="pta pta-primary" title="Primary Exam">
                                            <i class="bi bi-clipboard2-pulse"></i>
                                        </a>
                                    @endif

                                    {{-- Secondary Exam --}}
                                    @if(! $isPrimaryDone)
                                        <span class="pta pta-locked" title="Complete Primary First"><i class="bi bi-lock-fill"></i></span>
                                    @elseif($isSecondaryDone)
                                        <span class="pta pta-done" title="Secondary Done"><i class="bi bi-check-all"></i></span>
                                    @elseif($isDilating)
                                        <span class="pta pta-timer dilation-timer-btn" data-unlock-time="{{ $unlockTimeMs }}" id="sec-btn-{{ $p->id }}">
                                            <i class="bi bi-hourglass-top"></i><span class="timer-text" style="font-size:10px;font-weight:700"> --:--</span>
                                        </span>
                                    @else
                                        <a href="{{ route('hospital.exam.secondary.show', ['slug' => $slug, 'id' => $p->id]) }}"
                                           class="pta pta-secondary" title="Secondary Exam">
                                            <i class="bi bi-file-earmark-medical"></i>
                                        </a>
                                    @endif

                                    <div class="pta-divider"></div>

                                    {{-- History --}}
                                    <a href="{{ route('hospital.patients.history', ['slug' => $slug, 'search' => $p->patient_code]) }}"
                                       class="pta pta-util" title="History"><i class="bi bi-clock-history"></i></a>

                                    {{-- Print --}}
                                    <a href="{{ route('hospital.patients.print', ['slug' => $slug, 'patient' => $p->id, 'auto_print' => 1, 'return_to' => 'back']) }}"
                                       class="pta pta-util" title="Print"><i class="bi bi-printer-fill"></i></a>

                                    {{-- Edit --}}
                                    <a href="{{ route('hospital.patients.edit', ['slug' => $slug, 'patient' => $p->id]) }}"
                                       class="pta pta-edit" title="Edit"><i class="bi bi-pencil-fill"></i></a>

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('hospital.patients.destroy', ['slug' => $slug, 'patient' => $p->id]) }}" class="d-contents delete-form">
                                        @csrf @method('DELETE')
                                        <button type="button" class="pta pta-delete delete-btn" title="Delete"><i class="bi bi-trash3-fill"></i></button>
                                    </form>

                                    {{-- Check-in (phone only) --}}
                                    @if($p->type === 'phone' && !$p->case_id)
                                        <a href="{{ route('hospital.patients.checkin', ['slug' => $slug, 'patient' => $p->id]) }}"
                                           class="pta pta-checkin" title="Check In"><i class="bi bi-person-check-fill"></i></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-state">
                                <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                                <h5>No patients found</h5>
                                <p>{{ request('search') ? 'No results match your search.' : ($showAll ? 'No patients registered yet.' : 'No patients registered today.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($patients->hasPages())
            <div class="pagination-modern">
                {{ $patients->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

{{-- Patient View Modal --}}
<div class="modal fade" id="patientViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" style="max-width:680px">
        <div class="modal-content pvm-modal-content">

            {{-- Header --}}
            <div class="pvm-header">
                <div class="pvm-avatar" id="pvmAvatar"></div>
                <div class="pvm-header-info">
                    <div id="pvmName" class="pvm-patient-name"></div>
                    <div class="pvm-header-meta">
                        <span id="pvmMrd" class="pvm-mrd-chip"></span>
                        <span id="pvmStatusBadge" class="pvm-status-chip"></span>
                        <span id="pvmGenderAge" class="pvm-meta-pill"></span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity:.8"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body pvm-body">

                {{-- Appointment highlight bar --}}
                <div class="pvm-appt-bar">
                    <div class="pvm-appt-item">
                        <i class="bi bi-calendar3"></i>
                        <div>
                            <div class="pvm-appt-lbl">Appointment</div>
                            <div id="pvmAppt" class="pvm-appt-val"></div>
                        </div>
                    </div>
                    <div class="pvm-appt-divider"></div>
                    <div class="pvm-appt-item">
                        <i class="bi bi-person-badge"></i>
                        <div>
                            <div class="pvm-appt-lbl">Doctor</div>
                            <div id="pvmDoctor" class="pvm-appt-val"></div>
                        </div>
                    </div>
                    <div class="pvm-appt-divider"></div>
                    <div class="pvm-appt-item">
                        <i class="bi bi-tag"></i>
                        <div>
                            <div class="pvm-appt-lbl">Case Type</div>
                            <div id="pvmCase" class="pvm-appt-val"></div>
                        </div>
                    </div>
                    <div class="pvm-appt-divider"></div>
                    <div class="pvm-appt-item">
                        <i class="bi bi-currency-rupee"></i>
                        <div>
                            <div class="pvm-appt-lbl">Fee</div>
                            <div id="pvmFee" class="pvm-appt-val pvm-fee"></div>
                        </div>
                    </div>
                </div>

                {{-- Two column cards --}}
                <div class="pvm-grid-2">
                    {{-- Personal --}}
                    <div class="pvm-card pvm-card-blue">
                        <div class="pvm-card-head"><i class="bi bi-person-lines-fill"></i> Personal</div>
                        <div class="pvm-field"><span class="pvm-fl">Occupation</span><span id="pvmOccupation" class="pvm-fv"></span></div>
                        <div class="pvm-field"><span class="pvm-fl">Referrer</span><span id="pvmReferrer" class="pvm-fv"></span></div>
                        <div class="pvm-field"><span class="pvm-fl">Registered</span><span id="pvmRegistered" class="pvm-fv pvm-fv-sm"></span></div>
                    </div>

                    {{-- Contact --}}
                    <div class="pvm-card pvm-card-teal">
                        <div class="pvm-card-head"><i class="bi bi-telephone-fill"></i> Contact & Location</div>
                        <div class="pvm-field">
                            <span class="pvm-fl"><i class="bi bi-phone" style="color:#1B4F72;margin-right:4px"></i>Phone</span>
                            <span id="pvmContact" class="pvm-fv pvm-contact"></span>
                        </div>
                        <div class="pvm-field">
                            <span class="pvm-fl"><i class="bi bi-whatsapp" style="color:#25D366;margin-right:4px"></i>WhatsApp</span>
                            <span id="pvmWhatsapp" class="pvm-fv pvm-contact"></span>
                        </div>
                        <div class="pvm-field">
                            <span class="pvm-fl"><i class="bi bi-geo-alt" style="color:#e74c3c;margin-right:4px"></i>Location</span>
                            <span id="pvmLocation" class="pvm-fv"></span>
                        </div>
                    </div>
                </div>

                {{-- Exam Status --}}
                <div class="pvm-card pvm-card-exam">
                    <div class="pvm-card-head"><i class="bi bi-clipboard2-pulse-fill"></i> Examination Status</div>
                    <div class="pvm-grid-2" style="margin-top:.6rem">
                        <div class="pvm-exam-box" id="pvmPrimaryBox">
                            <div class="pvm-exam-icon" id="pvmPrimaryIcon"></div>
                            <div>
                                <div class="pvm-exam-label">Primary Exam</div>
                                <div id="pvmPrimaryAt" class="pvm-exam-time"></div>
                            </div>
                        </div>
                        <div class="pvm-exam-box" id="pvmSecondaryBox">
                            <div class="pvm-exam-icon" id="pvmSecondaryIcon"></div>
                            <div>
                                <div class="pvm-exam-label">Secondary Exam</div>
                                <div id="pvmSecondaryAt" class="pvm-exam-time"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="pvm-footer">
                <a id="pvmEditBtn" href="#" class="pvm-btn-edit">
                    <i class="bi bi-pencil-square"></i> Edit Patient
                </a>
                <button type="button" class="pvm-btn-close" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>

/* ── Modal shell ── */
.pvm-modal-content {
    border:none;border-radius:22px;overflow:hidden;
    box-shadow:0 32px 64px rgba(27,79,114,0.22);
}

/* ── Header ── */
.pvm-header {
    background:linear-gradient(135deg,#1B4F72 0%,#2471A3 60%,#2980B9 100%);
    padding:1.4rem 1.6rem;
    display:flex;align-items:center;gap:1rem;
    position:relative;
}
.pvm-header::after {
    content:'';position:absolute;bottom:0;left:0;right:0;height:1px;
    background:rgba(255,255,255,0.12);
}
.pvm-avatar {
    width:54px;height:54px;border-radius:16px;flex-shrink:0;
    background:rgba(255,255,255,0.18);
    display:flex;align-items:center;justify-content:center;
    font-size:1.4rem;font-weight:800;color:#fff;letter-spacing:-.5px;
    border:2px solid rgba(255,255,255,0.25);
}
.pvm-header-info { flex:1;min-width:0; }
.pvm-patient-name {
    font-size:1.15rem;font-weight:700;color:#fff;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
    text-transform:capitalize;letter-spacing:-.01em;
}
.pvm-header-meta { display:flex;align-items:center;gap:.5rem;margin-top:.4rem;flex-wrap:wrap; }
.pvm-mrd-chip {
    font-family:'Courier New',monospace;font-size:.72rem;font-weight:700;
    background:rgba(255,255,255,0.15);color:#fff;
    padding:2px 10px;border-radius:6px;border:1px solid rgba(255,255,255,0.2);
    letter-spacing:.5px;
}
.pvm-status-chip {
    font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:20px;
    letter-spacing:.02em;
}
.pvm-meta-pill {
    font-size:.72rem;font-weight:600;color:rgba(255,255,255,0.75);
    background:rgba(255,255,255,0.10);padding:2px 9px;border-radius:20px;
}

/* ── Body ── */
.pvm-body { background:#f0f5fa;padding:1.25rem 1.4rem;display:flex;flex-direction:column;gap:.9rem; }

/* ── Appointment bar ── */
.pvm-appt-bar {
    background:#fff;border-radius:14px;padding:.9rem 1.1rem;
    display:flex;align-items:center;gap:0;
    border:1px solid rgba(27,79,114,0.10);
    box-shadow:0 2px 10px rgba(27,79,114,0.06);
}
.pvm-appt-item {
    flex:1;display:flex;align-items:center;gap:.65rem;padding:0 .5rem;
}
.pvm-appt-item > i { font-size:1.2rem;color:#1B4F72;opacity:.55;flex-shrink:0; }
.pvm-appt-lbl { font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8; }
.pvm-appt-val { font-size:.88rem;font-weight:700;color:#1e293b;margin-top:.1rem; }
.pvm-fee { color:#1B4F72;font-size:1rem; }
.pvm-appt-divider { width:1px;height:40px;background:#e8eef4;flex-shrink:0; }

/* ── Grid 2col ── */
.pvm-grid-2 { display:grid;grid-template-columns:1fr 1fr;gap:.9rem; }

/* ── Cards ── */
.pvm-card {
    background:#fff;border-radius:14px;padding:.95rem 1.1rem;
    border:1px solid rgba(27,79,114,0.09);
    box-shadow:0 2px 8px rgba(27,79,114,0.05);
}
.pvm-card-blue { border-left:3px solid #1B4F72; }
.pvm-card-teal { border-left:3px solid #2980B9; }
.pvm-card-exam { border-left:3px solid #1ABC9C; }

.pvm-card-head {
    font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;
    color:#1B4F72;margin-bottom:.7rem;display:flex;align-items:center;gap:.4rem;
    padding-bottom:.55rem;border-bottom:1px solid #f0f5fa;
}
.pvm-field {
    display:flex;justify-content:space-between;align-items:center;
    padding:.3rem 0;border-bottom:1px solid #f8fafc;
}
.pvm-field:last-child { border-bottom:none; }
.pvm-fl { font-size:.76rem;font-weight:600;color:#94a3b8;display:flex;align-items:center; }
.pvm-fv { font-size:.84rem;font-weight:600;color:#1e293b;text-align:right;max-width:58%;word-break:break-word; }
.pvm-fv-sm { font-size:.76rem;color:#64748b; }
.pvm-contact { font-size:.9rem;font-weight:700;color:#1B4F72;letter-spacing:.3px; }

/* ── Exam boxes ── */
.pvm-exam-box {
    display:flex;align-items:center;gap:.75rem;
    padding:.75rem;border-radius:11px;
    border:1px solid #e8eef4;background:#f8fafc;
    transition:border-color .2s;
}
.pvm-exam-box.done { background:#f0fdf4;border-color:#bbf7d0; }
.pvm-exam-box.pending { background:#f8fafc;border-color:#e2e8f0; }
.pvm-exam-icon {
    width:38px;height:38px;border-radius:10px;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;font-size:1.1rem;
}
.pvm-exam-label { font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8; }
.pvm-exam-time { font-size:.8rem;font-weight:700;color:#1B4F72;margin-top:.15rem; }
.pvm-exam-time.pending-text { color:#94a3b8;font-weight:600; }

/* ── Footer ── */
.pvm-footer {
    background:#fff;border-top:1px solid #e8eef4;
    padding:.9rem 1.4rem;display:flex;align-items:center;gap:.6rem;
}
.pvm-btn-edit {
    background:linear-gradient(135deg,#1B4F72 0%,#2471A3 100%);
    color:#fff;border:none;border-radius:10px;
    padding:.5rem 1.3rem;font-size:.86rem;font-weight:700;
    text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;
    box-shadow:0 4px 12px rgba(27,79,114,0.2);
    transition:box-shadow .2s,transform .15s;
}
.pvm-btn-edit:hover { color:#fff;box-shadow:0 6px 16px rgba(27,79,114,0.28);transform:translateY(-1px); }
.pvm-btn-close {
    background:#f1f5f9;border:none;border-radius:10px;
    padding:.5rem 1.2rem;font-size:.86rem;font-weight:600;color:#64748b;
    transition:background .15s;cursor:pointer;
}
.pvm-btn-close:hover { background:#e2e8f0; }

@media(max-width:600px){
    .pvm-grid-2 { grid-template-columns:1fr; }
    .pvm-appt-bar { flex-wrap:wrap;gap:.5rem; }
    .pvm-appt-divider { display:none; }
    .pvm-appt-item { min-width:45%; }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.view-patient-btn');
        if (!btn) { return; }
        var d = btn.dataset;

        // Avatar initials
        var initials = (d.name || '?').trim().split(' ').map(function(w){ return w[0]; }).slice(0,2).join('').toUpperCase();
        document.getElementById('pvmAvatar').textContent  = initials;
        document.getElementById('pvmName').textContent    = d.name || '—';
        document.getElementById('pvmMrd').textContent     = d.mrd  || '—';
        document.getElementById('pvmGenderAge').textContent = [d.gender, d.age ? d.age+'y' : ''].filter(Boolean).join(', ') || '—';
        document.getElementById('pvmEditBtn').href        = d.editUrl || '#';

        // Status badge
        var sb = document.getElementById('pvmStatusBadge');
        sb.textContent = d.status || '';
        var statusStyles = {
            'Secondary Done': 'background:#dcfce7;color:#15803d',
            'Primary Done':   'background:#dbeafe;color:#1d4ed8',
            'Waiting':        'background:#fef9c3;color:#854d0e',
        };
        sb.style.cssText = statusStyles[d.status] || '';

        // Appointment bar
        document.getElementById('pvmAppt').textContent   = d.appt   || '—';
        document.getElementById('pvmDoctor').textContent = d.doctor  || '—';
        document.getElementById('pvmCase').textContent   = d.case    || '—';
        document.getElementById('pvmFee').textContent    = d.fee ? '₹' + d.fee : '—';

        // Personal card
        document.getElementById('pvmOccupation').textContent = d.occupation || '—';
        document.getElementById('pvmReferrer').textContent   = d.referrer   || '—';
        document.getElementById('pvmRegistered').textContent = d.registered || '—';

        // Contact card
        document.getElementById('pvmContact').textContent  = d.contact  || '—';
        document.getElementById('pvmWhatsapp').textContent = d.whatsapp || d.contact || '—';
        var loc = [d.city, d.district, d.state].filter(function(v){ return v && v !== 'null'; }).join(', ');
        document.getElementById('pvmLocation').textContent = loc || '—';

        // Primary exam
        var priIcon = document.getElementById('pvmPrimaryIcon');
        var priAt   = document.getElementById('pvmPrimaryAt');
        var priBox  = document.getElementById('pvmPrimaryBox');
        if (d.primaryAt && d.primaryAt !== 'null') {
            priBox.className  = 'pvm-exam-box done';
            priIcon.style.background = '#dcfce7';
            priIcon.innerHTML = '<i class="bi bi-check-circle-fill" style="color:#16a34a"></i>';
            priAt.textContent = d.primaryAt;
            priAt.className   = 'pvm-exam-time';
        } else {
            priBox.className  = 'pvm-exam-box pending';
            priIcon.style.background = '#f1f5f9';
            priIcon.innerHTML = '<i class="bi bi-clock" style="color:#94a3b8"></i>';
            priAt.textContent = 'Not done yet';
            priAt.className   = 'pvm-exam-time pending-text';
        }

        // Secondary exam
        var secIcon = document.getElementById('pvmSecondaryIcon');
        var secAt   = document.getElementById('pvmSecondaryAt');
        var secBox  = document.getElementById('pvmSecondaryBox');
        if (d.secondaryAt && d.secondaryAt !== 'null') {
            secBox.className  = 'pvm-exam-box done';
            secIcon.style.background = '#dcfce7';
            secIcon.innerHTML = '<i class="bi bi-check-circle-fill" style="color:#16a34a"></i>';
            secAt.textContent = d.secondaryAt;
            secAt.className   = 'pvm-exam-time';
        } else {
            secBox.className  = 'pvm-exam-box pending';
            secIcon.style.background = '#f1f5f9';
            secIcon.innerHTML = '<i class="bi bi-clock" style="color:#94a3b8"></i>';
            secAt.textContent = 'Not done yet';
            secAt.className   = 'pvm-exam-time pending-text';
        }

        new bootstrap.Modal(document.getElementById('patientViewModal')).show();
    });
});
</script>
@endpush

