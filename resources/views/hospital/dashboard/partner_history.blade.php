@extends('hospital.layouts.app')

@section('content')
<style>
    .partner-page-wrap { background: #f8fafc; padding: 2rem; min-height: 100vh; }
    .custom-partner-card { background: #fff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(27,79,114,.1); border: 1px solid #e2e8f0; overflow: hidden; }
    .table thead th { background: #1B4F72 !important; color: #fff !important; font-size: 13px; text-transform: uppercase; letter-spacing: .5px; padding: 15px !important; }
    .table tbody tr:hover { background-color: #f1f7fc !important; cursor: pointer; }
    .badge-time { background: #e0f2fe; color: #0369a1; font-weight: 600; padding: 5px 10px; border-radius: 6px; font-size: 12px; }
    .btn-view-partner { background: #0d9488; color: #fff; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 600; font-size: 13px; transition: .2s; }
    .btn-view-partner:hover { background: #0f766e; transform: scale(1.04); color: #fff; }
</style>

<div class="partner-page-wrap">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h5 class="fw-bold mb-0" style="color:#1B4F72;">
                <i class="bi bi-building me-2"></i>{{ $partnerTenant->name }}
                <span style="font-size:13px;font-weight:500;color:#64748b;margin-left:8px;">— Patient History</span>
            </h5>
            <small class="text-muted">
                <i class="bi bi-geo-alt me-1"></i>{{ $partnerTenant->city ?? '' }}{{ $partnerTenant->state ? ', '.$partnerTenant->state : '' }}
            </small>
        </div>
        <a href="{{ route('hospital.doctor.history', ['slug' => $slug]) }}#request"
            class="btn btn-sm btn-outline-secondary px-3" style="border-radius:6px;">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('hospital.partner.history', ['slug' => $slug, 'partnerTenantId' => $partnerTenant->id]) }}"
        class="card mb-4 p-3" style="border-radius:14px;border:1px solid #e2e8f0;background:#fff;">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold mb-1" style="color:#1B4F72;font-size:13px;">Patient Name</label>
                <input type="text" name="patient_name" value="{{ $patientName ?? '' }}"
                    class="form-control form-control-sm" placeholder="Search patient..."
                    style="border-radius:8px;border:1px solid #cbd5e1;">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold mb-1" style="color:#1B4F72;font-size:13px;">Doctor Name</label>
                <input type="text" name="doctor_name" value="{{ $doctorName ?? '' }}"
                    class="form-control form-control-sm" placeholder="Search doctor..."
                    style="border-radius:8px;border:1px solid #cbd5e1;">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label fw-semibold mb-1" style="color:#1B4F72;font-size:13px;">Date</label>
                <input type="date" name="date" value="{{ $date ?? '' }}"
                    class="form-control form-control-sm" style="border-radius:8px;border:1px solid #cbd5e1;">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm w-100 fw-bold"
                    style="background:#1B4F72;color:#fff;border-radius:8px;">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                @if($patientName || $doctorName || $date)
                    <a href="{{ route('hospital.partner.history', ['slug' => $slug, 'partnerTenantId' => $partnerTenant->id]) }}"
                        class="btn btn-sm w-100 fw-bold" style="background:#e2e8f0;color:#1B4F72;border-radius:8px;">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- Summary --}}
    <div class="mb-3">
        <span class="fw-semibold" style="color:#1B4F72;font-size:14px;">
            <i class="bi bi-people me-1"></i>
            Total Records: <strong>{{ $partnerPatients->total() }}</strong>
        </span>
    </div>

    {{-- Table --}}
    <div class="custom-partner-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0" style="font-size:14px;">
                <thead>
                    <tr>
                        <th style="width:55px;">#</th>
                        <th>MRD No.</th>
                        <th class="text-start">Patient Name</th>
                        <th>Doctor</th>
                        <th>Date</th>
                        <th>Primary</th>
                        <th>Secondary</th>
                        <th>Age</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($partnerPatients as $i => $patient)
                        <tr>
                            <td>{{ $partnerPatients->firstItem() + $i }}</td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $patient->patient_code ?? '—' }}</span>
                            </td>
                            <td class="text-start fw-semibold" style="color:#1B4F72;">
                                {{ $patient->first_name }} {{ $patient->last_name }}
                            </td>
                            <td class="fw-semibold">{{ $patient->doctor->name ?? '—' }}</td>
                            <td>{{ \Carbon\Carbon::parse($patient->appointment_date)->format('d M, Y') }}</td>
                            <td>
                                @if($patient->primary_done_at)
                                    <span class="badge-time">{{ \Carbon\Carbon::parse($patient->primary_done_at)->format('h:i A') }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($patient->secondary_done_at)
                                    <span class="badge-time">{{ \Carbon\Carbon::parse($patient->secondary_done_at)->format('h:i A') }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $patient->age ?? '—' }}</td>
                            <td>
                                <a href="{{ route('hospital.shared.patient.history', ['slug' => $slug]) }}?search={{ $patient->patient_code }}"
                                    class="btn-view-partner" target="_blank">
                                    <i class="bi bi-eye-fill me-1"></i>View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-5 text-muted">
                                <i class="bi bi-person-slash me-2"></i>Koi patient history nahi mili.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($partnerPatients->hasPages())
            <div class="d-flex justify-content-center p-3 border-top" style="background:#fcfcfc;">
                {{ $partnerPatients->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
