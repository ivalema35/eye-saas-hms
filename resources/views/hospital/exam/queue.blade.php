@extends('hospital.layouts.app')
@section('title', 'Clinical Queue Dashboard')
@section('page-header', 'Clinical Queue Dashboard')

@section('page-actions')
    <form method="GET" class="queue-filter-bar">
        <input type="hidden" name="doctor_id" value="{{ request('doctor_id') }}">
        <span class="queue-filter-label">
            <i class="fa-solid fa-calendar-days"></i>
            Filter by Date
        </span>
        <input type="date" id="filterDate" name="date" value="{{ $filterDate }}" placeholder="Select date" class="queue-filter-input" onchange="this.form.submit()">
    </form>
@endsection

@section('content')

<style>
    :root {
        --queue-primary: #1B4F72;
        --queue-primary-soft: rgba(27, 79, 114, 0.08);
        --queue-primary-surface: rgba(27, 79, 114, 0.04);
        --queue-border: rgba(27, 79, 114, 0.12);
        --queue-text: #18324A;
        --queue-muted: #6B7D93;
        --queue-bg: #F6FAFD;
        --queue-danger: #D94841;
        --queue-warning: #D97706;
        --queue-success: #1F9D55;
        --queue-secondary: #2563EB;
    }

    .queue-page-shell {
        position: relative;
        isolation: isolate;
    }

    .queue-page-shell::before,
    .queue-page-shell::after {
        content: '';
        position: absolute;
        border-radius: 9999px;
        pointer-events: none;
        z-index: -1;
        filter: blur(8px);
    }

    .queue-page-shell::before {
        top: -2rem;
        right: 2rem;
        width: 14rem;
        height: 14rem;
        background: radial-gradient(circle, rgba(27, 79, 114, 0.10), transparent 70%);
    }

    .queue-page-shell::after {
        bottom: 8rem;
        left: -3rem;
        width: 18rem;
        height: 18rem;
        background: radial-gradient(circle, rgba(41, 128, 185, 0.10), transparent 68%);
    }

    .queue-filter-bar {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.65rem 0.9rem;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.72);
        border: 1px solid var(--queue-border);
        box-shadow: 0 12px 30px rgba(27, 79, 114, 0.06);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        flex-wrap: wrap;
    }

    .queue-filter-label {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        color: var(--queue-text);
        font-size: 0.92rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .queue-filter-label i {
        color: var(--queue-primary);
    }

    .queue-filter-input {
        min-width: 170px;
        padding: 0.68rem 0.9rem;
        border-radius: 14px;
        border: 1px solid rgba(27, 79, 114, 0.18);
        background: #ffffff;
        color: var(--queue-text);
        font-weight: 600;
        outline: none;
        box-shadow: none;
    }

    .queue-filter-input:focus {
        border-color: rgba(27, 79, 114, 0.35);
        box-shadow: 0 0 0 4px rgba(27, 79, 114, 0.08);
    }

    .doctor-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(245px, 1fr));
        gap: 1rem;
    }

    .doctor-card-link {
        text-decoration: none;
        color: inherit;
    }

    .doctor-card {
        display: flex;
        gap: 1rem;
        padding: 1.15rem;
        border: 1px solid rgba(27, 79, 114, 0.12);
        border-radius: 1.15rem;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(247, 250, 253, 0.96));
        cursor: pointer;
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
        height: 100%;
        box-shadow: 0 10px 26px rgba(27, 79, 114, 0.05);
    }

    .doctor-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 34px rgba(27, 79, 114, 0.10);
        border-color: rgba(27, 79, 114, 0.22);
    }

    .doctor-card.active {
        border: 1px solid rgba(27, 79, 114, 0.30);
        background: linear-gradient(180deg, rgba(235, 245, 251, 0.98), rgba(245, 250, 253, 0.98));
        box-shadow: 0 18px 34px rgba(27, 79, 114, 0.12);
    }

    .doctor-avatar {
        width: 52px;
        height: 52px;
        min-width: 52px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(27, 79, 114, 0.12), rgba(41, 128, 185, 0.14));
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.15rem;
        color: var(--queue-primary);
        border: 1px solid rgba(27, 79, 114, 0.12);
    }

    .doctor-card-content {
        flex: 1;
        min-width: 0;
    }

    .doctor-name {
        font-weight: 700;
        font-size: 1rem;
        color: var(--queue-text);
        margin-bottom: 0.28rem;
        word-break: break-word;
    }

    .doctor-stat {
        font-size: 0.8rem;
        color: var(--queue-muted);
        margin-bottom: 0.5rem;
    }

    .doctor-stat-value {
        font-weight: 600;
        color: var(--queue-text);
    }

    .doctor-badges {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .doctor-badge {
        display: inline-block;
        padding: 0.25rem 0.625rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .doctor-badge.primary {
        background-color: rgba(217, 72, 65, 0.10);
        color: var(--queue-danger);
    }

    .doctor-badge.secondary {
        background-color: rgba(37, 99, 235, 0.10);
        color: var(--queue-secondary);
    }

    /* Tabs Styling */
    .tabs-container {
        display: flex;
        gap: 0.6rem;
        margin-bottom: 1.25rem;
        padding: 0.4rem;
        background: rgba(244, 248, 251, 0.9);
        border: 1px solid var(--queue-border);
        border-radius: 20px;
        position: sticky;
        top: 0.75rem;
        z-index: 4;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }

    .tab-button {
        flex: 1;
        padding: 0.95rem 1.15rem;
        background: transparent;
        border: none;
        cursor: pointer;
        font-weight: 600;
        color: var(--queue-muted);
        font-size: 0.95rem;
        position: relative;
        transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        justify-content: center;
        border-radius: 16px;
    }

    .tab-button:hover {
        color: var(--queue-primary);
    }

    .tab-button.active {
        color: var(--queue-primary);
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 8px 18px rgba(27, 79, 114, 0.08);
    }

    .tab-button.active::after {
        content: none;
    }

    .tab-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        background-color: rgba(27, 79, 114, 0.08);
        color: var(--queue-primary);
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .queue-panel {
        overflow: hidden;
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(27, 79, 114, 0.10);
        box-shadow: 0 18px 40px rgba(27, 79, 114, 0.07);
    }

    .queue-panel-inner {
        padding: 1.25rem;
    }

    /* Table Styling */
    .queue-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 920px;
    }

    .queue-table thead {
        background: linear-gradient(90deg, var(--queue-primary), #2C6E99);
        color: white;
    }

    .queue-table thead th {
        padding: 1rem 1rem;
        text-align: left;
        font-weight: 600;
        font-size: 0.84rem;
        letter-spacing: 0.03em;
        border: none;
        text-transform: uppercase;
    }

    .queue-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid rgba(27, 79, 114, 0.08);
        vertical-align: middle;
        color: var(--queue-text);
    }

    .queue-table tbody tr:hover {
        background-color: rgba(27, 79, 114, 0.03);
    }

    .patient-name {
        font-weight: 600;
        color: var(--queue-primary);
    }

    .patient-meta {
        font-size: 0.85rem;
        color: var(--queue-muted);
        margin-top: 0.25rem;
    }

    .queue-badge {
        display: inline-block;
        padding: 0.375rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .queue-badge.primary {
        background-color: rgba(217, 72, 65, 0.10);
        color: var(--queue-danger);
    }

    .queue-badge.dilation {
        background-color: rgba(217, 119, 6, 0.10);
        color: var(--queue-warning);
    }

    .queue-badge.secondary {
        background-color: rgba(37, 99, 235, 0.10);
        color: var(--queue-secondary);
    }

    .dilation-countdown {
        font-weight: 600;
        color: var(--queue-danger);
        font-family: 'Courier New', monospace;
        font-size: 0.95rem;
    }

    .queue-table .queue-state-line {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        color: var(--queue-muted);
        font-size: 0.84rem;
    }

    .queue-action-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.72rem 1rem;
        border-radius: 14px;
        background: linear-gradient(135deg, var(--queue-primary), #2C6E99);
        border: 1px solid rgba(27, 79, 114, 0.15);
        color: #ffffff;
        text-decoration: none;
        font-weight: 700;
        box-shadow: 0 10px 20px rgba(27, 79, 114, 0.14);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        white-space: nowrap;
    }

    .queue-action-link:hover {
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 14px 26px rgba(27, 79, 114, 0.18);
    }

    .empty-queue {
        text-align: center;
        padding: 3.25rem 1.25rem;
        color: var(--queue-muted);
        background: linear-gradient(180deg, rgba(250, 252, 254, 0.96), rgba(241, 246, 250, 0.96));
    }

    .empty-queue-icon {
        display: grid;
        place-items: center;
        width: 82px;
        height: 82px;
        margin: 0 auto 1rem;
        border-radius: 50%;
        background: rgba(27, 79, 114, 0.08);
        color: var(--queue-primary);
        font-size: 2rem;
    }

    .hms-card-table {
        border-radius: 0;
        overflow: visible;
        background: transparent;
    }

    .queue-section-heading {
        margin: 0 0 1rem;
        color: var(--queue-text);
        font-size: 1.02rem;
        font-weight: 800;
    }

    @media (max-width: 768px) {
        .tabs-container {
            flex-direction: column;
        }

        .tab-button {
            justify-content: flex-start;
        }

        .queue-panel-inner {
            padding: 1rem;
        }

        .queue-filter-bar {
            width: 100%;
        }

        .queue-filter-input {
            width: 100%;
            min-width: 0;
        }
    }
</style>

<div class="queue-page-shell">

<!-- Doctor Summary Cards Section -->
<div class="hms-card" style="margin-bottom: 1.5rem; border-radius: 24px; overflow: hidden; border: 1px solid rgba(27, 79, 114, 0.10); box-shadow: 0 18px 40px rgba(27, 79, 114, 0.07);">
    <div class="hms-card-header">
        <h3 class="hms-card-title">
            <i class="fa-solid fa-user-doctor" style="color: var(--queue-primary); margin-right: 0.5rem;"></i>
            Doctor Assignments
        </h3>
    </div>
    <div class="hms-card-body">
        <div class="doctor-cards-grid">
            <!-- All Doctors Option -->
            <a href="{{ route('hospital.clinical.queue', ['slug' => $slug, 'date' => $filterDate]) }}"
               class="doctor-card-link">
                <div class="doctor-card {{ !$selectedDoctorId ? 'active' : '' }}">
                    <div class="doctor-avatar">
                        <i class="fa-solid fa-users" style="font-size: 1.45rem;"></i>
                    </div>
                    <div class="doctor-card-content">
                        <div class="doctor-name">All Doctors</div>
                        <div class="doctor-stat">
                            <span class="doctor-stat-value">{{ $primaryQueue->count() + $dilationQueue->count() + $secondaryQueue->count() }}</span> Total
                        </div>
                        <div class="doctor-badges">
                            <span class="doctor-badge primary">
                                <i class="fa-solid fa-eye"></i> {{ $primaryQueue->count() }} Primary
                            </span>
                            <span class="doctor-badge secondary">
                                <i class="fa-solid fa-heart-pulse"></i> {{ $dilationQueue->count() + $secondaryQueue->count() }} Secondary
                            </span>
                        </div>
                    </div>
                </div>
            </a>

            @foreach($doctors as $doctor)
                <a href="{{ route('hospital.clinical.queue', ['slug' => $slug, 'doctor_id' => $doctor['id'], 'date' => $filterDate]) }}"
                   class="doctor-card-link">
                    <div class="doctor-card {{ $selectedDoctorId === $doctor['id'] ? 'active' : '' }}">
                        <div class="doctor-avatar">
                            {{ strtoupper(substr($doctor['name'], 0, 1)) }}
                        </div>
                        <div class="doctor-card-content">
                            <div class="doctor-name">{{ $doctor['name'] }}</div>
                            <div class="doctor-stat">
                                <span class="doctor-stat-value">{{ $doctor['total_assigned'] }}</span> Assigned
                            </div>
                            <div class="doctor-badges">
                                @if($doctor['primary_pending'] > 0)
                                    <span class="doctor-badge primary">
                                        <i class="fa-solid fa-eye"></i> {{ $doctor['primary_pending'] }}
                                    </span>
                                @endif
                                @if($doctor['secondary_pending'] > 0)
                                    <span class="doctor-badge secondary">
                                        <i class="fa-solid fa-heart-pulse"></i> {{ $doctor['secondary_pending'] }}
                                    </span>
                                @endif
                                @if($doctor['primary_pending'] === 0 && $doctor['secondary_pending'] === 0)
                                    <span class="doctor-stat" style="color: #22C55E; font-weight: 600;">
                                        <i class="fa-solid fa-check-circle"></i> All Clear
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>

<!-- Queue Tabs Section -->
<div class="queue-panel">
    <div class="queue-panel-inner">
        <!-- Tab Buttons -->
        <div class="tabs-container">
            <button class="tab-button active" onclick="showTab('primary')">
                <i class="fa-solid fa-eye"></i> Primary Queue
                <span class="tab-badge">{{ $primaryQueue->count() }}</span>
            </button>
            <button class="tab-button" onclick="showTab('dilation')">
                <i class="fa-solid fa-hourglass-end"></i> Dilation Waiting
                <span class="tab-badge">{{ $dilationQueue->count() }}</span>
            </button>
            <button class="tab-button" onclick="showTab('secondary')">
                <i class="fa-solid fa-heart-pulse"></i> Secondary Queue
                <span class="tab-badge">{{ $secondaryQueue->count() }}</span>
            </button>
        </div>

        <!-- Primary Queue Tab -->
        <div id="primary-tab" class="tab-content active">
            @if($primaryQueue->count() > 0)
                <div style="overflow-x: auto;">
                    <table class="queue-table">
                        <thead>
                            <tr>
                                <th>Patient Name</th>
                                <th>Age / Gender</th>
                                <th>Contact</th>
                                <th>Case Type</th>
                                <th>Registered</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($primaryQueue as $patient)
                                <tr>
                                    <td>
                                        <div class="patient-name">{{ $patient->first_name }} {{ $patient->last_name }}</div>
                                        <div class="patient-meta">MRD: {{ $patient->patient_code ?? 'N/A' }}</div>
                                    </td>
                                    <td>
                                        <span class="queue-badge primary">{{ $patient->age }} / {{ ucfirst($patient->gender ?? 'N/A') }}</span>
                                    </td>
                                    <td>
                                        <div class="queue-state-line"><i class="fa-solid fa-phone"></i> {{ $patient->contact_no }}</div>
                                    </td>
                                    <td>
                                        <div class="queue-state-line"><i class="fa-solid fa-tag"></i> {{ $patient->caseType?->case_type ?? '—' }}</div>
                                    </td>
                                    <td>
                                        <div class="queue-state-line"><i class="fa-regular fa-clock"></i> {{ $patient->created_at->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        <a href="{{ route('hospital.exam.primary.show', ['slug' => $slug, 'id' => $patient->id]) }}" class="queue-action-link">
                                            <i class="fa-solid fa-arrow-right"></i> Start
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-queue">
                    <div class="empty-queue-icon">
                        <i class="fa-solid fa-check-circle"></i>
                    </div>
                    <p><strong>No patients waiting for Primary Exam</strong></p>
                    <p style="font-size: 0.9rem; margin-top: 0.5rem;">All primary exams completed for the selected date</p>
                </div>
            @endif
        </div>

        <!-- Dilation Waiting Tab -->
        <div id="dilation-tab" class="tab-content">
            @if($dilationQueue->count() > 0)
                <div style="overflow-x: auto;">
                    <table class="queue-table">
                        <thead>
                            <tr>
                                <th>Patient Name</th>
                                <th>Age / Gender</th>
                                <th>Primary Done</th>
                                <th>Dilation Time</th>
                                <th>Unlock Time</th>
                                <th>Countdown</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dilationQueue as $patient)
                                @php
                                    $primary = $patient->primaryExamination;
                                    $dilationMinutes = $primary->dilation_time ?? 0;
                                    $unlockTime = $primary->updated_at->copy()->addMinutes($dilationMinutes);
                                    $countdownMs = $unlockTime->timestamp * 1000;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="patient-name">{{ $patient->first_name }} {{ $patient->last_name }}</div>
                                        <div class="patient-meta">MRD: {{ $patient->patient_code ?? 'N/A' }}</div>
                                    </td>
                                    <td>
                                        <span class="queue-badge dilation">{{ $patient->age }} / {{ ucfirst($patient->gender ?? 'N/A') }}</span>
                                    </td>
                                    <td>
                                        <div class="queue-state-line"><i class="fa-regular fa-clock"></i> {{ $primary->examined_at->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        <div class="queue-state-line"><i class="fa-solid fa-stopwatch"></i> {{ $dilationMinutes }} min</div>
                                    </td>
                                    <td>
                                        <div class="queue-state-line"><i class="fa-regular fa-bell"></i> {{ $unlockTime->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        <div class="dilation-countdown" data-unlock-ms="{{ $countdownMs }}">
                                            --:--
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-queue">
                    <div class="empty-queue-icon">
                        <i class="fa-solid fa-eye"></i>
                    </div>
                    <p><strong>No patients currently dilating</strong></p>
                    <p style="font-size: 0.9rem; margin-top: 0.5rem;">All dilation periods have completed or no dilation requested</p>
                </div>
            @endif
        </div>

        <!-- Secondary Queue Tab -->
        <div id="secondary-tab" class="tab-content">
            @if($secondaryQueue->count() > 0)
                <div style="overflow-x: auto;">
                    <table class="queue-table">
                        <thead>
                            <tr>
                                <th>Patient Name</th>
                                <th>Age / Gender</th>
                                <th>Contact</th>
                                <th>Primary Done</th>
                                <th>Primary Findings</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($secondaryQueue as $patient)
                                @php
                                    $primary = $patient->primaryExamination;
                                    $diagnosis = $primary->exam_data['diagnosis'] ?? 'N/A';
                                    if (is_array($diagnosis)) {
                                        $diagnosis = implode(', ', array_slice($diagnosis, 0, 2));
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <div class="patient-name">{{ $patient->first_name }} {{ $patient->last_name }}</div>
                                        <div class="patient-meta">MRD: {{ $patient->patient_code ?? 'N/A' }}</div>
                                    </td>
                                    <td>
                                        <span class="queue-badge secondary">{{ $patient->age }} / {{ ucfirst($patient->gender ?? 'N/A') }}</span>
                                    </td>
                                    <td>{{ $patient->contact_no }}</td>
                                    <td>
                                        <div class="queue-state-line"><i class="fa-regular fa-clock"></i> {{ $primary->examined_at->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        <div class="queue-state-line"><i class="fa-solid fa-notes-medical"></i> {{ $diagnosis }}</div>
                                    </td>
                                    <td>
                                        <a href="{{ route('hospital.exam.secondary.show', ['slug' => $slug, 'id' => $patient->id]) }}" class="queue-action-link">
                                            <i class="fa-solid fa-arrow-right"></i> Start
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-queue">
                    <div class="empty-queue-icon">
                        <i class="fa-solid fa-heart"></i>
                    </div>
                    <p><strong>No patients waiting for Secondary Exam</strong></p>
                    <p style="font-size: 0.9rem; margin-top: 0.5rem;">All secondary exams completed for the selected date</p>
                </div>
            @endif
        </div>
    </div>
</div>

</div>

@endsection

@push('scripts')
<script>
    function showTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(el => {
            el.classList.remove('active');
        });
        document.querySelectorAll('.tab-button').forEach(el => {
            el.classList.remove('active');
        });

        document.getElementById(tabName + '-tab').classList.add('active');
        event.target.closest('.tab-button').classList.add('active');
    }

    function updateDilationCountdowns() {
        document.querySelectorAll('.dilation-countdown').forEach(el => {
            const unlockMs = parseInt(el.getAttribute('data-unlock-ms'));
            const now = Date.now();
            const diffMs = unlockMs - now;

            if (diffMs <= 0) {
                el.textContent = '00:00';
                el.style.color = '#22C55E';
            } else {
                const minutes = Math.floor(diffMs / 60000);
                const seconds = Math.floor((diffMs % 60000) / 1000);
                el.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            }
        });
    }

    updateDilationCountdowns();
    setInterval(updateDilationCountdowns, 1000);
</script>
@endpush
