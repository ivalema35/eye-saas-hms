@extends('hospital.layouts.app')
@section('title', 'Doctor Dashboard')
@section('page-header', 'Doctor Dashboard')

@section('content')

@php
    $focReceptionists = $focReceptionists ?? collect();
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
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-orange"><i class="fa-solid fa-hand-holding-heart"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">FOC Given Today</div>
            <div class="hms-stat-value">{{ $todayFoc }}</div>
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
                            <a href="{{ route('hospital.exam.primary.show', ['slug' => $slug, 'id' => $patient->id]) }}"
                               class="hms-btn hms-btn-sm hms-btn-primary">
                                <i class="fa-solid fa-stethoscope"></i> Examine
                            </a>
                            <!-- <button type="button"
                                    class="hms-btn hms-btn-sm hms-btn-outline"
                                    data-bs-toggle="modal"
                                    data-bs-target="#focRequestModal{{ $patient->id }}">
                                <i class="fa-solid fa-hand-holding-heart"></i> Request FOC
                            </button> -->

                            <div class="modal fade" id="focRequestModal{{ $patient->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('hospital.foc.request', ['slug' => $slug]) }}">
                                            @csrf
                                            <!-- <div class="modal-header">
                                                <h5 class="modal-title">Request FOC</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div> -->
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
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center" style="color:#9CA3AF;padding:2rem">No patients in primary queue</td></tr>
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
                            <a href="{{ route('hospital.exam.secondary.show', ['slug' => $slug, 'id' => $patient->id]) }}"
                               class="hms-btn hms-btn-sm hms-btn-success">
                                <i class="fa-solid fa-stethoscope"></i> Examine
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center" style="color:#9CA3AF;padding:2rem">No patients in secondary queue</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
