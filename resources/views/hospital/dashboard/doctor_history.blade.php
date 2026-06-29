@extends('hospital.layouts.app')

@section('content')
    <style>
        .history-page-wrap {
            background: #f8fafc !important;
            padding: 2rem !important;
            min-height: 100vh;
        }

        /* ── Tabs ── */
        .history-tab-bar {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 1.5rem;
        }

        .history-tab-btn {
            background: none;
            border: none;
            outline: none;
            padding: 10px 28px;
            font-size: 14px;
            font-weight: 700;
            color: #94a3b8;
            cursor: pointer;
            position: relative;
            transition: color 0.2s;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }

        .history-tab-btn.active {
            color: #1B4F72;
            border-bottom: 3px solid #1B4F72;
        }

        .history-tab-btn:hover:not(.active) {
            color: #1B4F72;
        }

        .history-tab-pane {
            display: none;
        }

        .history-tab-pane.active {
            display: block;
        }

        /* ── Table / Card shared ── */
        .custom-history-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(27, 79, 114, 0.1);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .table thead th {
            background: #1B4F72 !important;
            color: #ffffff !important;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px !important;
        }

        .table tbody tr:hover {
            background-color: #f1f7fc !important;
            cursor: pointer;
        }

        .badge-time {
            background: #e0f2fe;
            color: #0369a1;
            font-weight: 600;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12px;
        }

        .btn-view-history {
            background: #f6c23e;
            color: #000;
            border: none;
            padding: 6px 14px;
            border-radius: 8px;
            transition: 0.3s;
            font-weight: 600;
        }

        .btn-view-history:hover {
            background: #d4a329;
            transform: scale(1.05);
        }

        /* ── Hospital tab ── */
        .hosp-logo {
            width: 44px;
            height: 44px;
            object-fit: contain;
            border-radius: 9px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 3px;
        }

        .hosp-logo-placeholder {
            width: 44px;
            height: 44px;
            border-radius: 9px;
            background: rgba(27, 79, 114, 0.08);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .badge-active {
            background: #d1fae5;
            color: #065f46;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .badge-trial {
            background: #fef3c7;
            color: #92400e;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .badge-grace {
            background: #fee2e2;
            color: #991b1b;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .location-tag {
            color: #475569;
            font-size: 13px;
        }

        .location-tag i {
            color: #1B4F72;
        }
    </style>

    <div class="history-page-wrap">

        {{-- Global flash messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert" style="border-radius:10px;">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert" style="border-radius:10px;">
                <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show mb-3" role="alert" style="border-radius:10px;">
                <i class="bi bi-info-circle-fill me-2"></i>{{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Page header --}}
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <h5 class="fw-bold mb-0" style="color: #1B4F72;">
                <i class="bi bi-clock-history me-2"></i>History
            </h5>
            <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}" class="btn btn-sm btn-outline-secondary px-3"
                style="border-radius: 6px;">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        {{-- Tab Bar --}}
        <div class="history-tab-bar">
            <button class="history-tab-btn active" data-tab="patient-history">
                <i class="bi bi-person-lines-fill me-1"></i> Patient History
            </button>
            <button class="history-tab-btn" data-tab="hospital-history">
                <i class="bi bi-building me-1"></i> Hospital History
            </button>
            <button class="history-tab-btn" data-tab="request-history">
                <i class="bi bi-send me-1"></i> Request
            </button>
        </div>

        {{-- ══════════ TAB 1: PATIENT HISTORY ══════════ --}}
        <div class="history-tab-pane active" id="tab-patient-history">

            {{-- Filter Form --}}
            <form method="GET" action="{{ route('hospital.doctor.history', ['slug' => $slug]) }}" class="card mb-4 p-3"
                style="border-radius:14px; border:1px solid #e2e8f0; background:#ffffff;">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold mb-1" style="color:#1B4F72; font-size:13px;">Patient Name</label>
                        <input type="text" name="patient_name" value="{{ request('patient_name') }}"
                            class="form-control form-control-sm" placeholder="Search patient..."
                            style="border-radius:8px; border:1px solid #cbd5e1;">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold mb-1" style="color:#1B4F72; font-size:13px;">Doctor Name</label>
                        <input type="text" name="doctor_name" value="{{ request('doctor_name') }}"
                            class="form-control form-control-sm" placeholder="Search doctor..."
                            style="border-radius:8px; border:1px solid #cbd5e1;">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold mb-1" style="color:#1B4F72; font-size:13px;">Contact No.</label>
                        <input type="text" name="contact_no" value="{{ request('contact_no') }}"
                            class="form-control form-control-sm" placeholder="Search contact..."
                            maxlength="15" style="border-radius:8px; border:1px solid #cbd5e1;">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold mb-1" style="color:#1B4F72; font-size:13px;">Date</label>
                        <input type="date" name="date" value="{{ request('date') }}" class="form-control form-control-sm"
                            style="border-radius:8px; border:1px solid #cbd5e1;">
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm w-100 fw-bold"
                            style="background:#1B4F72; color:#fff; border-radius:8px;">
                            <i class="bi bi-search me-1"></i> Filter
                        </button>
                        @if(request('patient_name') || request('doctor_name') || request('contact_no') || request('date'))
                            <a href="{{ route('hospital.doctor.history', ['slug' => $slug]) }}" class="btn btn-sm w-100 fw-bold"
                                style="background:#e2e8f0; color:#1B4F72; border-radius:8px;">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="card custom-history-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center mb-0" style="font-size: 14px;">
                        <thead>
                            <tr>
                                <th style="width:60px;">#</th>
                                <th>MRD No.</th>
                                <th class="text-start">Patient Name</th>
                                <th>Hospital</th>
                                <th>Doctor Name</th>
                                <th>Date</th>
                                <th>Contact</th>
                                <th>Age</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($historyPatients as $i => $patient)
                                <tr>
                                    <td>{{ $historyPatients->firstItem() + $i }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $patient->patient_code ?? '—' }}</span></td>
                                    <td class="text-start fw-semibold" style="color:#1B4F72;">
                                        {{ $patient->first_name ?? '' }} {{ $patient->last_name ?? '' }}
                                    </td>
                                    <td>
                                        @if($patient->tenant_id === $currentTenant->id)
                                            <span style="background:#e0f2fe;color:#0369a1;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;">
                                                <i class="bi bi-house-fill me-1"></i>Own
                                            </span>
                                        @else
                                            <span style="background:#f0fdf4;color:#166534;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;">
                                                <i class="bi bi-building me-1"></i>{{ $patient->tenant->name ?? '—' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">{{ $patient->doctor->name ?? '—' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($patient->appointment_date)->format('d M, Y') }}</td>
                                    <td>{{ $patient->contact_no ?? '-' }}</td>
                                    <td>{{ $patient->age ?? '-' }}</td>
                                    <td>
                                        @if($patient->tenant_id === $currentTenant->id)
                                            <a href="{{ route('hospital.patients.history', ['slug' => $slug]) }}?patient_ids={{ $patient->all_patient_ids }}"
                                                class="btn btn-sm btn-view-history" title="View Details" target="_blank">
                                                <i class="bi bi-eye-fill"></i> View
                                            </a>
                                        @else
                                            <a href="{{ route('hospital.shared.patient.history', ['slug' => $slug]) }}?patient_ids={{ $patient->all_patient_ids }}"
                                                class="btn btn-sm fw-bold" target="_blank"
                                                style="background:#0d9488;color:#fff;border-radius:8px;padding:5px 12px;">
                                                <i class="bi bi-eye-fill"></i> View
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-5 text-muted">No history found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($historyPatients->hasPages())
                    <div class="d-flex justify-content-center p-3 border-top" style="background-color:#fcfcfc;">
                    {{ $historyPatients->appends(request()->except('patient_page'))->appends([
                            '_tab' => 'patient'
                        ])->links() }}
                                        </div>
                @endif
            </div>

        </div>{{-- end tab-patient-history --}}

        {{-- ══════════ TAB 2: HOSPITAL HISTORY ══════════ --}}
        <div class="history-tab-pane" id="tab-hospital-history">

            {{-- Filter Form --}}
            <form method="GET" action="{{ route('hospital.doctor.history', ['slug' => $slug]) }}"
                class="card mb-4 p-3" style="border-radius:14px; border:1px solid #e2e8f0; background:#ffffff;">
                <input type="hidden" name="_tab" value="hospital">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold mb-1" style="color:#1B4F72; font-size:13px;">Hospital Name</label>
                        <input type="text" name="hosp_name" value="{{ request('hosp_name') }}"
                            class="form-control form-control-sm" placeholder="Search hospital..."
                            style="border-radius:8px; border:1px solid #cbd5e1;">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold mb-1" style="color:#1B4F72; font-size:13px;">City</label>
                        <input type="text" name="hosp_city" value="{{ request('hosp_city') }}"
                            class="form-control form-control-sm" placeholder="Search city..."
                            style="border-radius:8px; border:1px solid #cbd5e1;">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold mb-1" style="color:#1B4F72; font-size:13px;">District</label>
                        <input type="text" name="hosp_district" value="{{ request('hosp_district') }}"
                            class="form-control form-control-sm" placeholder="District..."
                            style="border-radius:8px; border:1px solid #cbd5e1;">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold mb-1" style="color:#1B4F72; font-size:13px;">State</label>
                        <input type="text" name="hosp_state" value="{{ request('hosp_state') }}"
                            class="form-control form-control-sm" placeholder="State..."
                            style="border-radius:8px; border:1px solid #cbd5e1;">
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm w-100 fw-bold"
                            style="background:#1B4F72; color:#fff; border-radius:8px;">
                            <i class="bi bi-search me-1"></i>Filter
                        </button>
                        @if(request('hosp_name') || request('hosp_city') || request('hosp_district') || request('hosp_state'))
                            <a href="{{ route('hospital.doctor.history', ['slug' => $slug]) }}#hospital"
                                class="btn btn-sm fw-bold" style="background:#e2e8f0; color:#1B4F72; border-radius:8px;">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="mb-3">
                <span class="fw-semibold" style="color:#1B4F72; font-size:14px;">
                    <i class="bi bi-hospitals me-1"></i>
                    Total: <strong>{{ $hospitals->total() }}</strong> hospitals
                </span>
            </div>

            <div class="card custom-history-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center mb-0" style="font-size:14px;">
                        <thead>
                            <tr>
                                <th style="width:55px;">#</th>
                                <th style="width:65px;">Logo</th>
                                <th class="text-start">Hospital Name</th>
                                <th>City</th>
                                <th>District</th>
                                <th>State</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($hospitals as $i => $hospital)
                                @php $reqInfo = $requestMap[$hospital->id] ?? null; @endphp
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        @if($hospital->logo_path)
                                            <img src="{{ asset('storage/' . $hospital->logo_path) }}" alt="{{ $hospital->name }}" class="hosp-logo">
                                        @else
                                            <span class="hosp-logo-placeholder">
                                                <i class="bi bi-hospital" style="color:#1B4F72; font-size:18px;"></i>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-start fw-semibold" style="color:#1B4F72;">{{ $hospital->name }}</td>
                                    <td class="location-tag"><i class="bi bi-geo-alt-fill me-1"></i>{{ $hospital->city ?? '—' }}</td>
                                    <td class="location-tag"><i class="bi bi-map me-1"></i>{{ $hospital->district ?? '—' }}</td>
                                    <td class="location-tag"><i class="bi bi-globe me-1"></i>{{ $hospital->state ?? '—' }}</td>
                                    <td>
                                        @if(!$reqInfo)
                                            <span style="background:#f1f5f9;color:#64748b;font-size:11px;font-weight:600;padding:4px 10px;border-radius:20px;">
                                                —
                                            </span>
                                        @elseif($reqInfo['status'] === 'accepted')
                                            <span class="badge-active"><i class="bi bi-check2-circle me-1"></i>Connected</span>
                                        @elseif($reqInfo['direction'] === 'sent')
                                            <span style="background:#fef3c7;color:#92400e;font-size:11px;font-weight:700;padding:4px 10px;border-radius:20px;">
                                                <i class="bi bi-clock me-1"></i>Requested
                                            </span>
                                        @else
                                            <span style="background:#ede9fe;color:#5b21b6;font-size:11px;font-weight:700;padding:4px 10px;border-radius:20px;">
                                                <i class="bi bi-bell me-1"></i>Incoming
                                            </span>
                                        @endif
                                    </td>

                                         <td>
                                        <div class="d-flex justify-content-center align-items-center gap-1 flex-nowrap"> 
                                        <button type="button" class="btn btn-sm btn-outline-primary view-hosp-btn"
                                            style="border-radius:8px;" data-id="{{ $hospital->id }}">
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        @if(!$reqInfo)
                                            <form method="POST" action="{{ route('hospital.hospital.share.send', ['slug' => $slug, 'toTenantId' => $hospital->id]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm fw-bold"
                                                    style="background:#1B4F72;color:#fff;border-radius:8px;">
                                                    <i class="bi bi-send me-1"></i>Request
                                                </button>
                                            </form>
                                        @elseif($reqInfo['status'] === 'accepted')
                                            <form method="POST" action="{{ route('hospital.hospital.share.remove', ['slug' => $slug, 'requestId' => $reqInfo['id']]) }}">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger fw-bold"
                                                    style="border-radius:8px;"
                                                    onclick="return confirm('Connection remove karna chahte ho?')">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        @elseif($reqInfo['direction'] === 'sent')
                                            <form method="POST" action="{{ route('hospital.hospital.share.remove', ['slug' => $slug, 'requestId' => $reqInfo['id']]) }}">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-secondary fw-bold"
                                                    style="border-radius:8px;">
                                                    <i class="bi bi-x-lg"></i> Cancel
                                                </button>
                                            </form>
                                        @else
                                            {{-- Incoming: Accept or Remove from hospital tab too --}}
                                            <form method="POST" action="{{ route('hospital.hospital.share.accept', ['slug' => $slug, 'requestId' => $reqInfo['id']]) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm fw-bold"
                                                    style="background:#16a34a;color:#fff;border-radius:8px;">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('hospital.hospital.share.remove', ['slug' => $slug, 'requestId' => $reqInfo['id']]) }}" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger fw-bold" style="border-radius:8px;">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-5 text-muted">
                                        <i class="bi bi-building-slash me-2"></i>No active hospitals found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($hospitals->hasPages())
                <div class="d-flex justify-content-center p-3 border-top" style="background-color:#fcfcfc;">
                {{ $hospitals->appends(request()->except('hospital_page'))->appends(['_tab' => 'hospital'])->links() }}
                </div>
                @endif
            </div>

        </div>{{-- end tab-hospital-history --}}

        {{-- ══════════ TAB 3: REQUEST ══════════ --}}
        <div class="history-tab-pane" id="tab-request-history">

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:10px;">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius:10px;">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- ── Connected Hospitals ── --}}
            @if($acceptedConnections->count())
            <h6 class="fw-bold mb-3" style="color:#1B4F72;">
                <i class="bi bi-link-45deg me-2"></i>Connected Hospitals
            </h6>
            <div class="row g-3 mb-4">
                @foreach($acceptedConnections as $conn)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 p-3" style="border-radius:14px;border:2px solid #0d9488;background:#f0fdfa;">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:46px;height:46px;border-radius:12px;background:#0d9488;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-building" style="color:#fff;font-size:20px;"></i>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="fw-bold text-truncate" style="color:#0f766e;font-size:14px;">{{ $conn->partner->name ?? '—' }}</div>
                                <div class="text-muted" style="font-size:12px;">
                                    <i class="bi bi-geo-alt me-1"></i>{{ $conn->partner->city ?? '' }}{{ $conn->partner->state ? ', ' . $conn->partner->state : '' }}
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('hospital.partner.history', ['slug' => $slug, 'partnerTenantId' => $conn->partner->id]) }}"
                                class="btn btn-sm w-100 fw-bold"
                                style="background:#0d9488;color:#fff;border-radius:8px;">
                                <i class="bi bi-person-lines-fill me-1"></i>View Patient History
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- ── Incoming Requests ── --}}
            <h6 class="fw-bold mb-3" style="color:#1B4F72;">
                <i class="bi bi-inbox me-2"></i>Incoming Requests
                @if($incomingRequests->count())
                    <span style="background:#dc2626;color:#fff;font-size:11px;padding:2px 8px;border-radius:20px;margin-left:4px;">
                        {{ $incomingRequests->count() }}
                    </span>
                @endif
            </h6>

            <div class="card custom-history-card mb-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center mb-0" style="font-size:14px;">
                        <thead>
                            <tr>
                                <th style="width:55px;">#</th>
                                <th class="text-start">From Hospital</th>
                                <th>City</th>
                                <th>State</th>
                                <th>Requested On</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($incomingRequests as $i => $req)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td class="text-start fw-semibold" style="color:#1B4F72;">
                                        <i class="bi bi-building me-1"></i>{{ $req->fromTenant->name ?? '—' }}
                                    </td>
                                    <td class="location-tag">{{ $req->fromTenant->city ?? '—' }}</td>
                                    <td class="location-tag">{{ $req->fromTenant->state ?? '—' }}</td>
                                    <td class="text-muted" style="font-size:13px;">
                                        {{ $req->created_at->format('d M, Y') }}
                                    </td>
                                    <td class="d-flex gap-2 justify-content-center">
                                        <form method="POST" action="{{ route('hospital.hospital.share.accept', ['slug' => $slug, 'requestId' => $req->id]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm fw-bold"
                                                style="background:#16a34a;color:#fff;border-radius:8px;min-width:80px;">
                                                <i class="bi bi-check-lg me-1"></i>Accept
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('hospital.hospital.share.remove', ['slug' => $slug, 'requestId' => $req->id]) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger fw-bold"
                                                style="border-radius:8px;min-width:80px;"
                                                onclick="return confirm('Request remove karna chahte ho?')">
                                                <i class="bi bi-x-lg me-1"></i>Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-muted">
                                        <i class="bi bi-inbox me-2"></i>No incoming requests.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ── Sent Requests ── --}}
            <h6 class="fw-bold mb-3" style="color:#1B4F72;">
                <i class="bi bi-send me-2"></i>Sent Requests
            </h6>

            <div class="card custom-history-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center mb-0" style="font-size:14px;">
                        <thead>
                            <tr>
                                <th style="width:55px;">#</th>
                                <th class="text-start">To Hospital</th>
                                <th>City</th>
                                <th>State</th>
                                <th>Sent On</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sentRequests as $i => $req)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td class="text-start fw-semibold" style="color:#1B4F72;">
                                        <i class="bi bi-building me-1"></i>{{ $req->toTenant->name ?? '—' }}
                                    </td>
                                    <td class="location-tag">{{ $req->toTenant->city ?? '—' }}</td>
                                    <td class="location-tag">{{ $req->toTenant->state ?? '—' }}</td>
                                    <td class="text-muted" style="font-size:13px;">{{ $req->created_at->format('d M, Y') }}</td>
                                    <td>
                                        @if($req->status === 'accepted')
                                            <span class="badge-active"><i class="bi bi-check2-circle me-1"></i>Connected</span>
                                        @elseif($req->status === 'pending')
                                            <span style="background:#fef3c7;color:#92400e;font-size:11px;font-weight:700;padding:4px 10px;border-radius:20px;">
                                                <i class="bi bi-clock me-1"></i>Pending
                                            </span>
                                        @else
                                            <span class="badge-grace"><i class="bi bi-x-circle me-1"></i>Rejected</span>
                                        @endif
                                    </td>
                                    <td class="d-flex gap-1 justify-content-center">
                                        @if($req->status === 'accepted')
                                            <a href="{{ route('hospital.partner.history', ['slug' => $slug, 'partnerTenantId' => $req->toTenant->id]) }}"
                                                class="btn btn-sm fw-bold"
                                                style="background:#0d9488;color:#fff;border-radius:8px;">
                                                <i class="bi bi-person-lines-fill me-1"></i>View History
                                            </a>
                                        @endif
                                        <form method="POST" action="{{ route('hospital.hospital.share.remove', ['slug' => $slug, 'requestId' => $req->id]) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger fw-bold"
                                                style="border-radius:8px;"
                                                onclick="return confirm('Remove karna chahte ho?')">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-4 text-muted">
                                        <i class="bi bi-send me-2"></i>No sent requests.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>{{-- end tab-request-history --}}

    </div>

    @push('scripts')
        <script>
            // Global tab switch — callable from anywhere (View History button etc.)
            function historyTabSwitch(name) {
                var btns  = document.querySelectorAll('.history-tab-btn');
                var panes = document.querySelectorAll('.history-tab-pane');
                btns.forEach(function (b) {
                    b.classList.toggle('active', b.getAttribute('data-tab') === name);
                });
                panes.forEach(function (p) {
                    p.classList.toggle('active', p.id === 'tab-' + name);
                });
                var newHash = name === 'hospital-history' ? '#hospital' : (name === 'request-history' ? '#request' : '#');
                history.replaceState(null, '', newHash);
                window.scrollTo(0, 0);
            }

            document.addEventListener('DOMContentLoaded', function () {
                var hash    = window.location.hash;
                var urlTab  = new URLSearchParams(window.location.search).get('_tab');
                var activeTab = (hash === '#hospital' || urlTab === 'hospital') ? 'hospital-history'
                              : (hash === '#request'  || urlTab === 'request')  ? 'request-history'
                              : 'patient-history';

                historyTabSwitch(activeTab);

                document.querySelectorAll('.history-tab-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        historyTabSwitch(btn.getAttribute('data-tab'));
                    });
                });

                document.querySelectorAll('.view-hosp-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var hospId = this.getAttribute('data-id');
                        var modalEl = document.getElementById('hospDetailModal');
                        document.getElementById('hospModalBody').innerHTML = '<div class="text-center py-4">Loading details...</div>';
                        var modal = new bootstrap.Modal(modalEl);
                        modal.show();

                        fetch('{{ url($slug . "/ajax/hospital-details") }}/' + hospId)
                            .then(function (res) { return res.json(); })
                            .then(function (data) {
                                document.getElementById('hospModalBody').innerHTML =
                                    '<div style="color:#1B4F72; font-weight:600;">' + data.name + '</div>' +
                                    '<p class="small text-muted mb-3">' + data.city + ', ' + data.state + '</p>' +
                                    '<hr>' +
                                    '<div class="row text-center">' +
                                        '<div class="col-4"><strong>' + data.doctors_count + '</strong><br><small>Doctors</small></div>' +
                                        '<div class="col-4"><strong>' + data.receptionists_count + '</strong><br><small>Staff</small></div>' +
                                        '<div class="col-4"><strong>' + data.patients_count + '</strong><br><small>Patients</small></div>' +
                                    '</div>' +
                                    '<div class="mt-3 small"><strong>Admin Email:</strong> ' + data.admin_email + '</div>';
                            })
                            .catch(function () {
                                document.getElementById('hospModalBody').innerHTML = '<div class="text-danger text-center py-3">Failed to load details.</div>';
                            });
                    });
                });
            });
        </script>
    @endpush

    <div class="modal fade" id="hospDetailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content"
                style="border-radius: 16px; border:none; box-shadow: 0 20px 50px rgba(27,79,114,0.2);">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" style="color:#1B4F72;">Hospital Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="hospModalBody">
                    <div class="text-center py-4">Loading details...</div>
                </div>
            </div>
        </div>
    </div>

@endsection