@extends('hospital.layouts.app')

@section('content')
<style>
    .hosp-page-wrap { background: #f8fafc !important; padding: 2rem !important; min-height: 100vh; }
    .hosp-card { background: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(27, 79, 114, 0.1); border: 1px solid #e2e8f0; overflow: hidden; }
    .table thead th { background: #1B4F72 !important; color: #ffffff !important; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; padding: 15px !important; }
    .table tbody tr:hover { background-color: #f1f7fc !important; }
    .hosp-logo { width: 46px; height: 46px; object-fit: contain; border-radius: 10px; border: 1px solid #e2e8f0; background: #f8fafc; padding: 4px; }
    .hosp-logo-placeholder { width: 46px; height: 46px; border-radius: 10px; background: rgba(27,79,114,0.08); display: inline-flex; align-items: center; justify-content: center; }
    .badge-active   { background: #d1fae5; color: #065f46; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; }
    .badge-trial    { background: #fef3c7; color: #92400e; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; }
    .badge-grace    { background: #fee2e2; color: #991b1b; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; }
    .location-tag   { color: #475569; font-size: 13px; }
    .location-tag i { color: #1B4F72; }
</style>

<div class="hosp-page-wrap">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h5 class="fw-bold mb-0" style="color: #1B4F72;">
            <i class="bi bi-building me-2"></i> Hospital History
        </h5>
        <div class="d-flex gap-2">
            <a href="{{ route('hospital.doctor.history', ['slug' => $slug]) }}"
               class="btn btn-sm btn-outline-secondary px-3" style="border-radius: 6px;">
                <i class="bi bi-clock-history me-1"></i> Patient History
            </a>
            <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}"
               class="btn btn-sm btn-outline-secondary px-3" style="border-radius: 6px;">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    {{-- Summary Badge --}}
    <div class="mb-3">
        <span class="fw-semibold" style="color:#1B4F72; font-size:14px;">
            <i class="bi bi-hospitals me-1"></i>
            Total Active Hospitals: <strong>{{ $hospitals->count() }}</strong>
        </span>
    </div>

    <div class="hosp-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0" style="font-size: 14px;">
                <thead>
                    <tr>
                        <th style="width:55px;">#</th>
                        <th style="width:70px;">Logo</th>
                        <th class="text-start">Hospital Name</th>
                        <th>City</th>
                        <th>District</th>
                        <th>State</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($hospitals as $i => $hospital)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                @if($hospital->logo_path)
                                    <img src="{{ asset('storage/' . $hospital->logo_path) }}"
                                         alt="{{ $hospital->name }}"
                                         class="hosp-logo">
                                @else
                                    <span class="hosp-logo-placeholder">
                                        <i class="bi bi-hospital" style="color:#1B4F72; font-size:20px;"></i>
                                    </span>
                                @endif
                            </td>
                            <td class="text-start fw-semibold" style="color: #1B4F72;">
                                {{ $hospital->name }}
                            </td>
                            <td class="location-tag">
                                <i class="bi bi-geo-alt-fill me-1"></i>{{ $hospital->city ?? '—' }}
                            </td>
                            <td class="location-tag">
                                <i class="bi bi-map me-1"></i>{{ $hospital->district ?? '—' }}
                            </td>
                            <td class="location-tag">
                                <i class="bi bi-globe me-1"></i>{{ $hospital->state ?? '—' }}
                            </td>
                            <td>
                                @if($hospital->status === 'active')
                                    <span class="badge-active"><i class="bi bi-check-circle-fill me-1"></i>Active</span>
                                @elseif($hospital->status === 'trial')
                                    <span class="badge-trial"><i class="bi bi-clock-fill me-1"></i>Trial</span>
                                @else
                                    <span class="badge-grace"><i class="bi bi-exclamation-circle-fill me-1"></i>Grace</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-5 text-muted">
                                <i class="bi bi-building-slash me-2"></i>No active hospitals found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
