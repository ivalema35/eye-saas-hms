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

.patient-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.patient-avatar {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, rgba(27, 79, 114, 0.10) 0%, rgba(26, 188, 156, 0.12) 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--patients-primary);
    font-size: 18px;
}

.patient-name {
    font-weight: 600;
    color: var(--patients-text);
}

.fee {
    font-weight: 600;
    color: var(--patients-primary);
}

.type-badge {
    background: var(--patients-surface-muted);
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    color: var(--patients-muted);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.completed {
    background: rgba(27, 79, 114, 0.10);
    color: var(--patients-primary);
}

.status-badge.in-progress {
    background: rgba(27, 79, 114, 0.08);
    color: var(--patients-primary);
}

.status-badge.waiting {
    background: rgba(26, 188, 156, 0.12);
    color: var(--patients-accent);
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.action-icon {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: var(--patients-surface-muted);
    color: var(--patients-muted);
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
    text-decoration: none;
}

.action-icon:hover {
    background: rgba(27, 79, 114, 0.10);
    color: var(--patients-primary);
    text-decoration: none;
}

.action-icon.primary {
    background: rgba(27, 79, 114, 0.10);
    color: var(--patients-primary);
}

.action-icon.primary:hover {
    background: var(--patients-primary);
    color: white;
}

.action-icon.secondary {
    background: rgba(26, 188, 156, 0.12);
    color: var(--patients-accent);
}

.action-icon.secondary:hover {
    background: var(--patients-accent);
    color: white;
}

.action-icon.print:hover {
    background: rgba(26, 188, 156, 0.14);
    color: var(--patients-accent);
}

.action-icon.edit:hover {
    background: var(--patients-primary);
    color: white;
}

.action-icon.delete:hover {
    background: #dc3545;
    color: white;
}

.action-icon.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.action-icon.timer {
    background: rgba(26, 188, 156, 0.12);
    color: var(--patients-accent);
    font-size: 12px;
    gap: 4px;
    min-width: 70px;
    cursor: default;
}

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
    
    .action-buttons {
        gap: 4px;
    }
    
    .action-icon {
        width: 28px;
        height: 28px;
    }
}

.patients-index-page .toggle-option i,
.patients-index-page .search-btn i,
.patients-index-page .status-badge i,
.patients-index-page .action-icon i {
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
                        <th>#</th>
                        <th>MRD</th>
                        <th>Patient</th>
                        <th>Age/Gender</th>
                        <th>Contact</th>
                        <th>Doctor</th>
                        <th>Fee</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $i => $p)
                        @php
                            $primary = $p->primaryExamination;
                            $secondary = $p->secondaryExamination;
                            $isPrimaryDone = $primary !== null;
                            $isSecondaryDone = $secondary !== null;
                            $unlockTimeMs = null;
                            $isDilating = false;

                            if ($primary && ($primary->exam_data['dilate'] ?? 'No') === 'Yes' && $primary->dilation_time && ! $isSecondaryDone) {
                                $expectedUnlock = $primary->updated_at->copy()->addMinutes($primary->dilation_time);
                                if (now()->lessThan($expectedUnlock)) {
                                    $isDilating = true;
                                    $unlockTimeMs = $expectedUnlock->timestamp * 1000;
                                }
                            }
                        @endphp
                        <tr>
                            <td class="serial">{{ $patients->firstItem() + $i }}</td>
                            <td>
                                <span class="mrd-badge">{{ $p->patient_code }}</span>
                            </td>
                            <td>
                                <div class="patient-info">
                                    <div class="patient-avatar">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                    <span class="patient-name">{{ $p->full_name }}</span>
                                </div>
                            </td>
                            <td>{{ $p->age }}y / {{ ucfirst($p->gender) }}</td>
                            <td>{{ $p->contact_no }}</td>
                            <td>{{ $p->doctor?->name ?? '—' }}</td>
                            <td class="fee">₹{{ number_format($p->case_fee, 0) }}</td>
                            <td>
                                <span class="type-badge">{{ ucfirst($p->type) }}</span>
                            </td>
                            <td>
                                @if($p->secondary_done_at)
                                    <span class="status-badge completed">
                                        <i class="bi bi-check2"></i> Completed
                                    </span>
                                @elseif($p->primary_done_at)
                                    <span class="status-badge in-progress">
                                        <i class="bi bi-arrow-repeat"></i> In Progress
                                    </span>
                                @else
                                    <span class="status-badge waiting">
                                        <i class="bi bi-clock-history"></i> Waiting
                                    </span>
                                @endif
                            </td>
                            <td>{{ $p->created_at->format('h:i A') }}</td>
                            <td>
                                <div class="action-buttons">
                                    {{-- Check-in button: phone appointment not yet checked in --}}
                                    @if($p->type === 'phone' && !$p->case_id)
                                        <a href="{{ route('hospital.patients.checkin', ['slug' => $slug, 'patient' => $p->id]) }}"
                                           class="action-icon"
                                           title="Check In"
                                           style="background:#E8F8F0;color:#27AE60;border-color:#A9DFBF">
                                            <i class="bi bi-person-check-fill"></i>
                                        </a>
                                    @endif

                                    <a href="{{ route('hospital.patients.history', ['slug' => $slug, 'search' => $p->patient_code]) }}"
                                       class="action-icon" title="History">
                                        <i class="bi bi-clock-history"></i>
                                    </a>

                                    @if($isPrimaryDone)
                                        <span class="action-icon disabled" title="Primary Completed">
                                            <i class="bi bi-check-circle text-success"></i>
                                        </span>
                                    @else
                                        <a href="{{ route('hospital.exam.primary.show', ['slug' => $slug, 'id' => $p->id]) }}" 
                                           class="action-icon primary" title="Primary Exam">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endif

                                    @if(! $isPrimaryDone)
                                        <span class="action-icon disabled" title="Complete Primary First">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                    @elseif($isSecondaryDone)
                                        <span class="action-icon disabled" title="Secondary Completed">
                                            <i class="bi bi-check-all text-success"></i>
                                            
                                        </span>
                                    @elseif($isDilating)
                                        <span class="action-icon timer dilation-timer-btn" 
                                              data-unlock-time="{{ $unlockTimeMs }}"
                                              id="sec-btn-{{ $p->id }}">
                                            <i class="bi bi-hourglass-top"></i>
                                            <span class="timer-text">--:--</span>
                                        </span>
                                    @else
                                        <a href="{{ route('hospital.exam.secondary.show', ['slug' => $slug, 'id' => $p->id]) }}" 
                                           class="action-icon secondary" title="Secondary Exam">
                                            <i class="bi bi-file-medical"></i>
                                        </a>
                                    @endif

                                    <a href="{{ route('hospital.patients.print', ['slug' => $slug, 'patient' => $p->id, 'auto_print' => 1, 'return_to' => 'back']) }}"
                                       class="action-icon print" title="Print">
                                        <i class="bi bi-printer"></i>
                                    </a>

                                    <a href="{{ route('hospital.patients.edit', ['slug' => $slug, 'patient' => $p->id]) }}" 
                                       class="action-icon edit" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <form method="POST" action="{{ route('hospital.patients.destroy', ['slug' => $slug, 'patient' => $p->id]) }}"
                                          class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="action-icon delete delete-btn" title="Delete">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="empty-state">
                                <div class="empty-icon">
                                    <i class="bi bi-inbox"></i>
                                </div>
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
@endsection

