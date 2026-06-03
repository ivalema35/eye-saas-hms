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
.doctor-page-wrap .card {
    border: 1px solid #e2e8f0 !important;
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
    <div class="row g-4 mb-4">
        
        {{-- Left Grid Box: 4 Mini Count Cards --}}
        <div class="col-lg-4 col-md-5">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; background: #ffffff;">
                <h6 class="fw-bold mb-3 text-secondary" style="font-size: 14px;">Today All Data</h6>
                
                <div class="row g-3">
                    {{-- Primary Checkup --}}
                    <div class="col-6">
                        <div class="p-2 border rounded d-flex align-items-center gap-2 bg-white" style="min-height: 75px;">
                            <img src="https://cdn-icons-png.flaticon.com/512/3774/3774299.png" alt="Primary" style="width: 32px; height: 32px; object-fit: contain;">
                            <div>
                                <span class="d-block text-muted fw-bold" style="font-size: 11px; line-height: 1.2;">Primary Checkup</span>
                                <h4 class="mb-0 fw-bold mt-1" style="color: #1B4F72; font-size: 20px;">{{ $doctorPrimaryDone ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>

                    {{-- Secondary --}}
                    <div class="col-6">
                        <div class="p-2 border rounded d-flex align-items-center gap-2 bg-white" style="min-height: 75px;">
                            <img src="https://cdn-icons-png.flaticon.com/512/2785/2785501.png" alt="Secondary" style="width: 32px; height: 32px; object-fit: contain;">
                            <div>
                                <span class="d-block text-muted fw-bold" style="font-size: 11px; line-height: 1.2;">Secondary</span>
                                <h4 class="mb-0 fw-bold mt-1" style="color: #1B4F72; font-size: 20px;">{{ $doctorSecondaryDone ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>

                    {{-- Doctor Profile --}}
                    <div class="col-6">
                        <div class="p-2 border rounded d-flex align-items-center gap-2 bg-white" style="min-height: 75px;">
                            <img src="https://cdn-icons-png.flaticon.com/512/883/883360.png" alt="My self" style="width: 32px; height: 32px; object-fit: contain;">
                            <div>
                                <span class="d-block text-muted fw-bold" style="font-size: 11px; line-height: 1.2;">Doctor Profile</span>
                                <h4 class="mb-0 fw-bold mt-1" style="color: #1B4F72; font-size: 20px;">0</h4>
                            </div>
                        </div>
                    </div>

                    {{-- Reports --}}
                    <div class="col-6">
                        <div class="p-2 border rounded d-flex align-items-center gap-2 bg-white" style="min-height: 75px;">
                            <img src="https://cdn-icons-png.flaticon.com/512/2620/2620582.png" alt="Reports" style="width: 32px; height: 32px; object-fit: contain;">
                            <div>
                                <span class="d-block text-muted fw-bold" style="font-size: 11px; line-height: 1.2;">Reports</span>
                                <h4 class="mb-0 fw-bold mt-1" style="color: #1B4F72; font-size: 20px;">0</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Grid Box: Empty Doctors Block --}}
        <div class="col-lg-8 col-md-7">
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