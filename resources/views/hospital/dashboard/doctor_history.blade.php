@extends('hospital.layouts.app')
@section('title', 'My Patient History')
@section('page-header', 'My Patient History')

@section('content')
<div class="doctor-page-wrap" style="background: #ffffff; padding: 3.5rem; min-height: 100vh;">
    
    {{-- ઉપરનું હેડર અને Back બટન --}}
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h5 class="fw-bold mb-0" style="color: #1B4F72;">
            <i class="bi bi-clock-history me-2"></i> Attended Patients History
        </h5>
        <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}" class="btn btn-sm btn-outline-secondary px-3" style="border-radius: 6px;">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    {{-- પેશન્ટનું ટેબલ --}}
    <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 12px; border: 1px solid #e2e8f0 !important;">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0" style="font-size: 14px;">
                <thead class="text-secondary" style="background-color: #f8fafc;">
                    <tr>
                        <th style="width: 60px; padding: 12px;">#</th>
                        <th class="text-start" style="padding: 12px;">Patient Name</th>
                        <th>Date</th>
                        <th>Primary Time</th>
                        <th>Secondary Time</th>
                        <th>Age</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($historyPatients as $i => $patient)
                        <tr style="transition: all 0.2s;">
                            <td>{{ $historyPatients->firstItem() + $i }}</td>
                            
                            {{-- પેશન્ટનું નામ --}}
                            <td class="text-start fw-semibold" style="color: #1B4F72;">
                                {{ $patient->first_name ?? '' }} {{ $patient->last_name ?? '' }}
                            </td>
                            
                            {{-- તારીખ --}}
                            <td>{{ \Carbon\Carbon::parse($patient->appointment_date)->format('d M, Y') }}</td>
                            
                            {{-- પ્રાયમરી ટાઈમ --}}
                            <td>
                                @if($patient->primary_done_at)
                                    <span class="badge bg-light text-dark border"><i class="bi bi-check-circle text-success me-1"></i> {{ \Carbon\Carbon::parse($patient->primary_done_at)->format('h:i A') }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            
                            {{-- સેકન્ડરી ટાઈમ --}}
                            <td>
                                @if($patient->secondary_done_at)
                                    <span class="badge bg-light text-dark border"><i class="bi bi-check-circle text-success me-1"></i> {{ \Carbon\Carbon::parse($patient->secondary_done_at)->format('h:i A') }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            
                            <td>{{ $patient->age ?? '-' }}</td>
>
                            {{-- આ Action બ્લોક રિપ્લેસ કરો --}}
                            <td>
                                <a href="{{ url($slug . '/patient-history') }}?search={{ $patient->patient_code }}" 
                                   class="btn btn-sm text-white" 
                                   style="background-color: #f6c23e; border-radius: 6px; padding: 4px 10px;" 
                                   title="View Patient Details"
                                   target="_blank">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-5 text-muted bg-light" style="font-size: 15px;">
                                <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary"></i>
                                No history found. You haven't attended any patients yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- નીચે પેજીનેશન (1, 2, 3 પેજ જવા માટે) --}}
        @if($historyPatients->hasPages())
            <div class="d-flex justify-content-center p-3 border-top" style="background-color: #fcfcfc;">
                {{ $historyPatients->links() }}
            </div>
        @endif
    </div>
</div>
@endsection