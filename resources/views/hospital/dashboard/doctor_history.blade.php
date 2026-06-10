@extends('hospital.layouts.app')

@section('content')
<style>
    .doctor-page-wrap { background: #f8fafc !important; padding: 2rem !important; min-height: 100vh; }
    .custom-history-card { background: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(27, 79, 114, 0.1); border: 1px solid #e2e8f0; overflow: hidden; }
    .history-header { background: #ffffff; padding: 1.5rem; border-bottom: 2px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
    .table thead th { background: #1B4F72 !important; color: #ffffff !important; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; padding: 15px !important; }
    .table tbody tr:hover { background-color: #f1f7fc !important; cursor: pointer; }
    .badge-time { background: #e0f2fe; color: #0369a1; font-weight: 600; padding: 6px 10px; border-radius: 6px; font-size: 12px; }
    .btn-view-history { background: #f6c23e; color: #000; border: none; padding: 6px 14px; border-radius: 8px; transition: 0.3s; font-weight: 600; }
    .btn-view-history:hover { background: #d4a329; transform: scale(1.05); }
</style>

<div class="doctor-page-wrap">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h5 class="fw-bold mb-0" style="color: #1B4F72;">
            <i class="bi bi-clock-history me-2"></i> Attended Patients History
        </h5>
        <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}" class="btn btn-sm btn-outline-secondary px-3" style="border-radius: 6px;">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="card custom-history-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0" style="font-size: 14px;">
    <thead>
        <tr>
            <th style="width: 60px;">#</th>
            <th>MRD No.</th>  <th class="text-start">Patient Name</th>
            <th>Doctor Name</th> <th>Date</th>
            <th>Primary Time</th>
            <th>Secondary Time</th>
            <th>Age</th>
            <th>Action</th>
        </tr>
    </thead>
<tbody>
    @forelse($historyPatients as $i => $patient)
        <tr>
            <td>{{ $historyPatients->firstItem() + $i }}</td>
            {{-- 1. MRD No --}}
            <td><span class="badge bg-light text-dark border">{{ $patient->patient_code ?? '—' }}</span></td>
            
            <td class="text-start fw-semibold" style="color: #1B4F72;">
                {{ $patient->first_name ?? '' }} {{ $patient->last_name ?? '' }}
            </td>

            {{-- 2. Doctor Name --}}
            <td class="fw-semibold">{{ $patient->doctor->name ?? '—' }}</td>

            <td>{{ \Carbon\Carbon::parse($patient->appointment_date)->format('d M, Y') }}</td>
            <td>
                @if($patient->primary_done_at)
                    <span class="badge-time">{{ \Carbon\Carbon::parse($patient->primary_done_at)->format('h:i A') }}</span>
                @else <span class="text-muted">-</span> @endif
            </td>
            <td>
                @if($patient->secondary_done_at)
                    <span class="badge-time">{{ \Carbon\Carbon::parse($patient->secondary_done_at)->format('h:i A') }}</span>
                @else <span class="text-muted">-</span> @endif
            </td>
            <td>{{ $patient->age ?? '-' }}</td>
            <td>
                <a href="{{ url($slug . '/patient-history') }}?search={{ $patient->patient_code }}" 
                   class="btn btn-sm btn-view-history" title="View Details" target="_blank">
                    <i class="bi bi-eye-fill"></i> View
                </a>
            </td>
        </tr>
    @empty
        <tr><td colspan="9" class="py-5 text-muted">No history found.</td></tr>
    @endforelse
</tbody>
            </table>
        </div>
        
        @if($historyPatients->hasPages())
            <div class="d-flex justify-content-center p-3 border-top" style="background-color: #fcfcfc;">
                {{ $historyPatients->links() }}
            </div>
        @endif
    </div>
</div>
@endsection