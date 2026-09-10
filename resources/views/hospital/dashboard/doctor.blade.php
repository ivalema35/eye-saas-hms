@extends('hospital.layouts.app')
@section('title', 'Doctor Dashboard')
@section('page-header', 'Doctor Dashboard')

@section('content')

    @php
        $doctorTodayPatients = $doctorAssignedPatients ?? $todayPatients;
    @endphp

    {{-- ============================================================
    Today Summary Cards
    ============================================================ --}}
    <div class="hms-stats-grid">
        <div class="hms-stat-card">
            <div class="hms-stat-icon hsi-blue"><i class="fa-solid fa-eye"></i></div>
            <div class="hms-stat-body">
                <div class="hms-stat-label">Today's Patients</div>
                <div class="hms-stat-value">{{ $doctorTodayPatients }}</div>
                <div class="hms-stat-meta">{{ now()->format('d M Y') }}</div>
            </div>
        </div>
        <div class="hms-stat-card">
            <div class="hms-stat-icon hsi-green"><i class="fa-solid fa-eye-low-vision"></i></div>
            <div class="hms-stat-body">
                <div class="hms-stat-label">Primary Queue</div>
                <div class="hms-stat-value">{{ $doctorPrimaryDone }}</div>
                <div class="hms-stat-meta">Secondary Queue: {{ $doctorSecondaryDone }}</div>
            </div>
        </div>
    </div>

    {{-- ============================================================
    Primary Patient Queue
    ============================================================ --}}
    <div class="hms-card" style="margin-top:1.5rem">
        <div class="hms-card-header">
            <h3 class="hms-card-title"><i class="fa-solid fa-list-ol"></i> Primary Queue</h3>
            <span class="hms-badge hms-badge-info">{{ $primaryQueue->count() }} waiting</span>
        </div>
        <div class="hms-card-body" style="padding:0">
            <table class="hms-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>MRD</th>
                        <th>Patient Name</th>
                        <th>Age / Gender</th>
                        <th>Contact</th>
                        <th>Reception Time</th>
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
                            <td>{{ $patient->contact_no }}</td>
                            <td>{{ $patient->created_at->format('h:i A') }}</td>
                            <td>
                                @haspermission('exam_primary')
                                <a href="{{ route('hospital.exam.primary.show', ['slug' => $slug, 'id' => $patient->id]) }}"
                                    class="hms-btn hms-btn-sm hms-btn-primary">
                                    <i class="fa-solid fa-stethoscope"></i> Examine
                                </a>
                                @endhaspermission
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center" style="color:#9CA3AF;padding:2rem">No patients in primary queue
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================================
    Secondary Patient Queue
    ============================================================ --}}
    <div class="hms-card" style="margin-top:1.5rem">
        <div class="hms-card-header">
            <h3 class="hms-card-title"><i class="fa-solid fa-list-check"></i> Secondary Queue</h3>
            <span class="hms-badge hms-badge-warning">{{ $secondaryQueue->count() }} waiting</span>
        </div>
        <div class="hms-card-body" style="padding:0">
            <table class="hms-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>MRD</th>
                        <th>Patient Name</th>
                        <th>Age / Gender</th>
                        <th>Contact</th>
                        <th>Primary Done</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($secondaryQueue as $i => $patient)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><strong>{{ $patient->patient_code }}</strong></td>
                            <td>{{ $patient->full_name }}</td>
                            <td>{{ $patient->age }}y / {{ ucfirst($patient->gender) }}</td>
                            <td>{{ $patient->contact_no }}</td>
                            <td>{{ $patient->primary_done_at->format('h:i A') }}</td>
                            <td>
                                @haspermission('exam_secondary')
                                <a href="{{ route('hospital.exam.secondary.show', ['slug' => $slug, 'id' => $patient->id]) }}"
                                    class="hms-btn hms-btn-sm hms-btn-success">
                                    <i class="fa-solid fa-stethoscope"></i> Examine
                                </a>
                                @endhaspermission
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center" style="color:#9CA3AF;padding:2rem">No patients in secondary
                                queue</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection