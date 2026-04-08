@extends('hospital.layouts.app')
@section('title', 'Primary Examination - '.$patient->full_name)
@section('page-header', 'Primary Eye Examination')

@section('page-actions')
    <a href="{{ route('hospital.patients.show', ['slug' => $slug, 'patient' => $patient->id]) }}"
       class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Patient
    </a>
    @if($exam)
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer"></i> Print Rx
        </button>
    @endif
    @haspermission('opd.foc.create')
        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#focRequestExamModal">
            <i class="fa-solid fa-hand-holding-heart"></i> Request FOC
        </button>
    @endhaspermission
@endsection

@section('content')

<style>
    .step-btn {
        min-width: 118px;
        font-weight: 600;
    }
    #liveReportCanvas {
        border-top: 3px solid #1B4F72;
        background: #fff;
    }
    .canvas-section-title {
        background: #1B4F72;
        color: #fff;
        padding: 2px 6px;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.04em;
        margin-bottom: 4px !important;
    }
    .canvas-box {
        border: 1px solid #343a40;
        border-radius: 4px;
        padding: 6px;
    }
    .table-premium th {
        background-color: #e9ecef !important;
        color: #495057;
        text-transform: uppercase;
        font-size: 0.8rem;
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        body {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            background-color: white !important;
        }

        body * { visibility: hidden; }

        .print-header, .print-header *,
        .clinical-grid-container, .clinical-grid-container * {
            visibility: visible;
        }

        .print-header {
            position: relative !important;
            left: auto !important;
            top: auto !important;
            width: 100%;
            padding: 8px 0;
            margin-bottom: 12px !important;
        }

        .clinical-grid-container {
            position: relative !important;
            left: auto !important;
            top: auto !important;
            width: 100%;
            padding: 0;
        }

        /* Keep 2-column layout on print */
        .clinical-grid-container > .row {
            display: flex !important;
            flex-wrap: nowrap !important;
            flex-direction: row !important;
        }

        .clinical-grid-container > .row > .col-md-6 {
            width: 50% !important;
            flex: 0 0 50% !important;
            max-width: 50% !important;
        }

        /* Prevent tables breaking across pages */
        .canvas-box, table, tr, td, th {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .bg-dark,
        .canvas-section-title {
            background-color: #1B4F72 !important;
            color: white !important;
        }

        .canvas-box {
            border: 1px solid #343a40 !important;
        }
    }
</style>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 8px;">
        <strong><i class="fas fa-exclamation-triangle"></i> Cannot Save Exam!</strong>
        <ul class="mb-0 mt-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@haspermission('opd.foc.create')
<div class="modal fade" id="focRequestExamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('hospital.foc.request', ['slug' => $slug]) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Request FOC</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                    <input type="hidden" name="doctor_id" value="{{ old('doctor_id', $currentDoctorId ?? auth('hospital_user')->id()) }}">

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
                            @foreach(($focReceptionists ?? collect()) as $receptionist)
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
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endhaspermission

@php
    $ed = $exam?->exam_data ?? [];
    $vision = $ed['vision'] ?? [];
    $pg = $ed['pg'] ?? [];
    $st = $ed['st'] ?? [];
    $nct = $ed['nct'] ?? [];
    $oe = $ed['oe'] ?? [];
    $fundus = $ed['fundus'] ?? [];

    $sinceRaw = old('exam_data.complaint_duration', $ed['complaint_duration'] ?? '');
    preg_match('/^(\d+)\s*(Days?|Weeks?|Months?|Years?)$/i', $sinceRaw, $sinceMatch);
    $sinceNumber = $sinceMatch[1] ?? '';
    $sinceUnit = isset($sinceMatch[2]) ? ucfirst(strtolower($sinceMatch[2])) : 'Days';
    $sinceUnit = rtrim($sinceUnit, 's').'s';

    $prescriptions = $exam?->prescriptions ?? collect();
@endphp

<form id="primaryExamForm" method="POST" action="{{ route('hospital.exam.primary.save', ['slug' => $slug, 'id' => $patient->id]) }}" novalidate>
    @csrf

    <div class="stepper-wrap d-flex d-print-none justify-content-between align-items-center mb-4 p-3 bg-white rounded shadow-sm border gap-3 flex-wrap">
        <div class="h5 mb-0 text-primary fw-bold">Exam Steps:</div>
        <div class="btn-group flex-wrap" role="group">
            <button type="button" class="btn btn-outline-secondary step-btn" id="btn-context" data-bs-toggle="modal" data-bs-target="#modalContext">Context</button>
            <button type="button" class="btn btn-outline-secondary step-btn" id="btn-clinical" data-bs-toggle="modal" data-bs-target="#modalClinical">A. Clinical</button>
            <button type="button" class="btn btn-outline-secondary step-btn" id="btn-vision" data-bs-toggle="modal" data-bs-target="#modalVision">B. Vision & PG</button>
            <button type="button" class="btn btn-outline-secondary step-btn" id="btn-st" data-bs-toggle="modal" data-bs-target="#modalST">C. ST & NCT</button>
            <button type="button" class="btn btn-outline-secondary step-btn" id="btn-oe" data-bs-toggle="modal" data-bs-target="#modalOE">D. O/E & Fundus</button>
            <button type="button" class="btn btn-outline-secondary step-btn" id="btn-rx" data-bs-toggle="modal" data-bs-target="#modalRx">E. Rx</button>
        </div>
        <button type="submit" class="btn btn-success fw-bold px-4">Save Exam</button>
    </div>

    {{-- Print-only hospital + patient header --}}
    <div class="print-header d-none d-print-block mb-3 border-bottom border-dark pb-2">
        <div class="text-center mb-2">
            <h4 class="mb-0 fw-bold" style="color:#1B4F72;">{{ app('tenant')->name ?? 'Eye Hospital' }}</h4>
            <p class="mb-0" style="font-size:12px;">Complete Eye Care Center</p>
        </div>
        <div class="d-flex justify-content-between" style="font-size:13px;font-weight:600;">
            <div>
                <div>Patient: {{ $patient->full_name }}</div>
                <div>Age/Sex: {{ $patient->age }} / {{ ucfirst($patient->gender ?? '') }}</div>
            </div>
            <div class="text-end">
                <div>MRD: {{ $patient->patient_code ?? $patient->mr_number ?? '-' }}</div>
                <div>Date: {{ $exam ? $exam->created_at->format('d M Y') : date('d M Y') }}</div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mx-auto" style="width:100%;max-width:1200px;background:white;padding:16px;" id="liveReportCanvas">
        <div class="row g-2 clinical-grid-container" style="font-size:13px;">

            {{-- LEFT COLUMN --}}
            <div class="col-6 col-md-6 d-flex flex-column gap-2">

                {{-- Box 1: History & Vision --}}
                <div class="canvas-box">
                    <div class="canvas-section-title">History &amp; Vision</div>
                    <div id="canvas_history"><em class="text-muted" style="font-size:11px;">Enter chief complaints to see them here...</em></div>
                    <div id="canvas_vision" class="mt-1"></div>
                </div>

                {{-- Box 2: ST & Rx --}}
                <div class="canvas-box">
                    <div class="canvas-section-title">Subjective Testing (ST)</div>
                    <div id="canvas_st" class="mb-1"></div>
                    <div class="canvas-section-title mt-1">Diagnosis &amp; Rx</div>
                    <div id="canvas_rx"></div>
                </div>

            </div>

            {{-- RIGHT COLUMN --}}
            <div class="col-6 col-md-6 d-flex flex-column gap-2">

                {{-- Box 3: O/E --}}
                <div class="canvas-box">
                    <div class="canvas-section-title">On Examination (O/E)</div>
                    <div id="canvas_oe"></div>
                </div>

                {{-- Box 4: Fundus --}}
                <div class="canvas-box">
                    <div class="canvas-section-title">Fundus</div>
                    <div id="canvas_fundus"></div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalContext" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">Context</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Examining Doctor</label>
                            <select name="doctor_id" class="form-select @error('doctor_id') is-invalid @enderror" required>
                                <option value="">Select Doctor</option>
                                @foreach(($masters['doctors'] ?? $doctors ?? collect()) as $doctor)
                                    <option value="{{ $doctor->id }}" {{ (string) old('doctor_id', $exam?->doctor_id ?? $currentDoctorId ?? auth('hospital_user')->id()) === (string) $doctor->id ? 'selected' : '' }}>
                                        {{ $doctor->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('doctor_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalClinical" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">A. Clinical History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">Chief Complaints (CC)</label>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach($masters['complaints'] as $c)
                            <div>
                                <input class="btn-check" type="checkbox" name="exam_data[complaints][]" id="cc_{{ $c->id }}" value="{{ $c->id }}"
                                    {{ in_array($c->id, $ed['complaints'] ?? []) ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary rounded-pill btn-sm" for="cc_{{ $c->id }}">{{ $c->complaint }}</label>
                            </div>
                        @endforeach
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Duration</label>
                            <input type="number" name="cc_since_number" id="cc_since_number" class="form-control" min="1" value="{{ $sinceNumber }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Unit</label>
                            <select name="cc_since_unit" id="cc_since_unit" class="form-select">
                                @foreach(['Days', 'Weeks', 'Months', 'Years'] as $unit)
                                    <option value="{{ $unit }}" {{ $sinceUnit === $unit ? 'selected' : '' }}>{{ $unit }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <input type="hidden" name="exam_data[complaint_duration]" id="cc_since_hidden" value="{{ $sinceRaw }}">
                            <small class="text-muted">Stored as combined value</small>
                        </div>
                    </div>

                    <label class="form-label fw-semibold">Known Case Of (KCO)</label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($masters['kcos'] as $k)
                            <div>
                                <input class="btn-check" type="checkbox" name="exam_data[kcos][]" id="kco_{{ $k->id }}" value="{{ $k->id }}"
                                    {{ in_array($k->id, $ed['kcos'] ?? []) ? 'checked' : '' }}>
                                <label class="btn btn-outline-secondary rounded-pill btn-sm" for="kco_{{ $k->id }}">{{ $k->kco }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalVision" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">B. Vision (VN) &amp; Present Glasses (PG)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 px-3 mb-3" style="font-size: 0.85rem;">
                        <i class="bi bi-info-circle"></i> Record the patient's visual acuity (unaided) and current glasses prescription here.
                    </div>
                    <h6 class="mb-2">Visual Acuity</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead>
                                <tr><th style="width:120px"></th><th>RE</th><th>LE</th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Distance Vision (VN)</td>
                                    @foreach(['re','le'] as $eye)
                                        <td>
                                            <select name="exam_data[vision][vn_{{ $eye }}]" class="form-select form-select-sm">
                                                <option value="">-</option>
                                                @foreach($masters['vn'] as $opt)
                                                    <option value="{{ $opt->value }}" {{ ($vision['vn_'.$eye] ?? '') === $opt->value ? 'selected' : '' }}>{{ $opt->value }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    @endforeach
                                </tr>
                                <tr>
                                    <td>Pinhole (PH)</td>
                                    @foreach(['re','le'] as $eye)
                                        <td>
                                            <select name="exam_data[vision][pnvn_{{ $eye }}]" class="form-select form-select-sm">
                                                <option value="">-</option>
                                                @foreach($masters['pnvn'] as $opt)
                                                    <option value="{{ $opt->value }}" {{ ($vision['pnvn_'.$eye] ?? '') === $opt->value ? 'selected' : '' }}>{{ $opt->value }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    @endforeach
                                </tr>
                                <tr>
                                    <td>Near Vision (NV)</td>
                                    @foreach(['re','le'] as $eye)
                                        <td>
                                            <select name="exam_data[vision][nrvn_{{ $eye }}]" class="form-select form-select-sm">
                                                <option value="">-</option>
                                                @foreach($masters['nrvn'] as $opt)
                                                    <option value="{{ $opt->value }}" {{ ($vision['nrvn_'.$eye] ?? '') === $opt->value ? 'selected' : '' }}>{{ $opt->value }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h6 class="mb-2">Present Glasses (PG)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead>
                                <tr><th style="width:120px"></th><th>RE</th><th>LE</th></tr>
                            </thead>
                            <tbody>
                                @foreach([
                                    'ds'      => ['label' => 'SPH (Sphere)',      'master' => 'sph_cyl', 'col' => 'value', 'bipolar' => true],
                                    'dc'      => ['label' => 'CYL (Cylinder)',    'master' => 'sph_cyl', 'col' => 'value', 'bipolar' => true],
                                    'ax'      => ['label' => 'AXIS',              'master' => 'axis',    'col' => 'value'],
                                    'vn'      => ['label' => 'Vision (VN)',       'master' => 'vn',      'col' => 'value'],
                                    'add'     => ['label' => 'ADD (Addition)',    'master' => 'sph_cyl', 'col' => 'value', 'bipolar' => true],
                                    'near_vn' => ['label' => 'Near Vision (NV)', 'master' => 'nrvn',    'col' => 'value'],
                                ] as $key => $meta)
                                    <tr>
                                        <td>{{ $meta['label'] }}</td>
                                        @foreach(['re','le'] as $eye)
                                            @php
                                                $uniqueVals = collect($masters[$meta['master']])
                                                    ->map(fn ($o) => ltrim(trim($o->{$meta['col']}), '+-'))
                                                    ->reject(fn ($v) => $v === '')
                                                    ->unique()
                                                    ->values();
                                                $savedVal = $pg[$eye][$key] ?? '';
                                            @endphp
                                            <td>
                                                <select name="exam_data[pg][{{ $eye }}][{{ $key }}]" class="form-select form-select-sm">
                                                    <option value="">-</option>
                                                    @foreach($uniqueVals as $cleanVal)
                                                        @if(!empty($meta['bipolar']) && !in_array((string) $cleanVal, ['0', '0.00', 'Plano', 'PL']))
                                                            <option value="+{{ $cleanVal }}" @selected($savedVal === '+'.$cleanVal)>+{{ $cleanVal }}</option>
                                                            <option value="-{{ $cleanVal }}" @selected($savedVal === '-'.$cleanVal)>-{{ $cleanVal }}</option>
                                                        @else
                                                            <option value="{{ $cleanVal }}" @selected($savedVal === (string) $cleanVal)>{{ $cleanVal }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalST" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">C. Subjective Trial (ST) &amp; NCT</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 px-3 mb-3" style="font-size: 0.85rem;">
                        <i class="bi bi-info-circle"></i> Enter the subjective trial refraction results. These become the final glass prescription.
                    </div>
                    <h6 class="mb-2">Subjective Trial (ST — Final Glass)</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead>
                                <tr><th style="width:120px"></th><th>RE</th><th>LE</th></tr>
                            </thead>
                            <tbody>
                                @foreach([
                                    'ds' => ['label' => 'SPH (Sphere)', 'list' => 'sph_cyl_list'],
                                    'dc' => ['label' => 'CYL (Cylinder)', 'list' => 'sph_cyl_list'],
                                    'ax' => ['label' => 'AXIS', 'list' => 'axis_list'],
                                    'ns' => ['label' => 'Near SPH', 'list' => 'sph_cyl_list'],
                                    'nc' => ['label' => 'Near CYL', 'list' => 'sph_cyl_list'],
                                    'na' => ['label' => 'Near AXIS', 'list' => 'axis_list'],
                                ] as $key => $meta)
                                    <tr>
                                        <td>{{ $meta['label'] }}</td>
                                        @foreach(['re','le'] as $eye)
                                            <td>
                                                <input type="text"
                                                       name="exam_data[st][{{ $eye }}][{{ $key }}]"
                                                       value="{{ old('exam_data.st.'.$eye.'.'.$key, $st[$eye][$key] ?? '') }}"
                                                       list="{{ $meta['list'] }}"
                                                       class="form-control form-control-sm">
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                <tr>
                                    <td>ADD (Addition)</td>
                                    <td colspan="2">
                                        <input type="text" name="exam_data[st][add]" value="{{ old('exam_data.st.add', $st['add'] ?? '') }}" list="sph_cyl_list" class="form-control form-control-sm">
                                    </td>
                                </tr>
                                <tr>
                                    <td>Lens Type</td>
                                    <td colspan="2">
                                        <input type="text" name="exam_data[st][lens_type]" value="{{ old('exam_data.st.lens_type', $st['lens_type'] ?? '') }}" class="form-control form-control-sm" placeholder="SV / Bifocal / Progressive">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h6 class="mb-2">NCT (Intraocular Pressure — mmHg)</h6>
                    <div class="row g-2">
                        @foreach(['re' => 'Right Eye', 'le' => 'Left Eye'] as $eye => $label)
                            <div class="col-md-6">
                                <label class="form-label">{{ $label }}</label>
                                <select name="exam_data[nct][iop_{{ $eye }}]" class="form-select form-select-sm">
                                    <option value="">-</option>
                                    @foreach($masters['nct'] as $opt)
                                        <option value="{{ $opt->value }}" {{ ($nct['iop_'.$eye] ?? '') === $opt->value ? 'selected' : '' }}>{{ $opt->value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalOE" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">D. Ocular Examination (O/E) &amp; Fundus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 px-3 mb-3" style="font-size: 0.85rem;">
                        <i class="bi bi-info-circle"></i> Document anterior and posterior segment findings for both eyes.
                    </div>
                    <h6 class="mb-2">Ocular Examination (O/E)</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead>
                                <tr><th style="width:140px"></th><th>RE</th><th>LE</th></tr>
                            </thead>
                            <tbody>
                                @foreach([
                                    'sac' => ['label' => 'SAC', 'master' => 'sac'],
                                    'lid' => ['label' => 'Lid', 'master' => 'lid'],
                                    'conj' => ['label' => 'Conj', 'master' => 'conj'],
                                    'cornea' => ['label' => 'Cornea', 'master' => 'cornea'],
                                    'ac' => ['label' => 'AC', 'master' => 'ac'],
                                    'iris' => ['label' => 'Iris', 'master' => 'iris'],
                                    'pupil' => ['label' => 'Pupil', 'master' => 'pupil'],
                                    'lens' => ['label' => 'Lens', 'master' => 'lens_master'],
                                    'em' => ['label' => 'EM', 'master' => 'em'],
                                    'covertest' => ['label' => 'Covertest', 'master' => 'covertest'],
                                ] as $key => $meta)
                                    <tr>
                                        <td>{{ $meta['label'] }}</td>
                                        @foreach(['re','le'] as $eye)
                                            <td>
                                                <select name="exam_data[oe][{{ $key }}_{{ $eye }}]" class="form-select form-select-sm">
                                                    <option value="">-</option>
                                                    @foreach($masters[$meta['master']] as $opt)
                                                        <option value="{{ $opt->value }}" {{ ($oe[$key.'_'.$eye] ?? '') === $opt->value ? 'selected' : '' }}>{{ $opt->value }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <h6 class="mb-2">Fundus Examination</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead>
                                <tr><th style="width:140px"></th><th>RE</th><th>LE</th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Disc</td>
                                    @foreach(['re','le'] as $eye)
                                        <td>
                                            <select name="exam_data[fundus][disc_{{ $eye }}]" class="form-select form-select-sm">
                                                <option value="">-</option>
                                                @foreach($masters['disc'] as $opt)
                                                    <option value="{{ $opt->value }}" {{ ($fundus['disc_'.$eye] ?? '') === $opt->value ? 'selected' : '' }}>{{ $opt->value }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    @endforeach
                                </tr>
                                <tr>
                                    <td>FR</td>
                                    @foreach(['re','le'] as $eye)
                                        <td>
                                            <select name="exam_data[fundus][fr_{{ $eye }}]" class="form-select form-select-sm">
                                                <option value="">-</option>
                                                @foreach($masters['fr'] as $opt)
                                                    <option value="{{ $opt->value }}" {{ ($fundus['fr_'.$eye] ?? '') === $opt->value ? 'selected' : '' }}>{{ $opt->value }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalRx" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">E. Diagnosis &amp; Medicines</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 px-3 mb-4" style="font-size: 0.85rem;">
                        <i class="bi bi-info-circle"></i> Select diagnosis, dilate status, and prescribe medicines with dosage and instructions.
                    </div>
                    {{-- Dilate toggle --}}
                    <div class="mb-4 border-bottom pb-3">
                        <label class="fw-bold text-primary mb-2 d-block">Dilate Patient for Secondary Exam?</label>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exam_data[dilate]" id="dilateYes" value="Yes"
                                    {{ old('exam_data.dilate', $ed['dilate'] ?? 'No') === 'Yes' ? 'checked' : '' }}>
                                <label class="form-check-label" for="dilateYes">Yes, Dilated</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exam_data[dilate]" id="dilateNo" value="No"
                                    {{ old('exam_data.dilate', $ed['dilate'] ?? 'No') !== 'Yes' ? 'checked' : '' }}>
                                <label class="form-check-label" for="dilateNo">No</label>
                            </div>
                            <div id="dilationTimeWrap" class="d-flex align-items-center gap-2"
                                 style="{{ old('exam_data.dilate', $ed['dilate'] ?? 'No') === 'Yes' ? '' : 'display:none!important;' }}">
                                <input type="number" name="dilation_time" id="dilation_time" min="1" max="180"
                                       class="form-control form-control-sm" style="width:80px;"
                                       placeholder="Mins" value="{{ old('dilation_time', $exam?->dilation_time ?? '') }}">
                                <span class="text-muted" style="font-size:0.85rem;">minutes lock</span>
                            </div>
                        </div>
                    </div>

                    <label class="form-label fw-semibold">Diagnosis</label>
                    <div class="d-flex flex-wrap gap-2 mb-3" id="diagnosis-tags">
                        @foreach($masters['diagnoses'] as $d)
                            <div>
                                <input class="btn-check" type="checkbox" name="exam_data[diagnoses][]" id="dx_{{ $d->id }}" value="{{ $d->id }}"
                                    {{ in_array($d->id, $ed['diagnoses'] ?? []) ? 'checked' : '' }}>
                                <label class="btn btn-outline-danger rounded-pill btn-sm" for="dx_{{ $d->id }}">{{ $d->diagnosis }}</label>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-semibold mb-0">Medicines</label>
                        <div class="d-flex gap-2 align-items-center">
                            <select id="rxGroupSelector" class="form-select form-select-sm" style="min-width:200px">
                                <option value="">-- Load Medicine Group --</option>
                                @foreach($masters['med_groups'] as $grp)
                                    <option value="{{ $grp->id }}">{{ $grp->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addMedicineRow()">+ Add</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0" id="rxTable">
                            <thead>
                                <tr>
                                    <th style="min-width:200px">Medicine</th>
                                    <th style="width:130px">Dosage</th>
                                    <th style="width:120px">Duration</th>
                                    <th>Instructions</th>
                                    <th style="width:90px">Mode/Eye</th>
                                    <th style="width:45px"></th>
                                </tr>
                            </thead>
                            <tbody id="rxBody">
                                @forelse($prescriptions as $i => $rx)
                                    <tr class="rx-row">
                                        <td>
                                            <input type="hidden" name="medicines[{{ $i }}][medicine_id]" value="{{ $rx->medicine_id }}" class="med-id-input">
                                            <div class="medicine-search-wrap" style="position:relative">
                                                <input type="text" name="medicines[{{ $i }}][name]" class="form-control form-control-sm medicine-search"
                                                       value="{{ $rx->medicine?->brand_name ?: $rx->medicine?->name }}"
                                                       placeholder="Medicine name"
                                                       autocomplete="off"
                                                       list="medicine_list">
                                                <div class="medicine-suggest" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #dee2e6;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.12);z-index:100;max-height:180px;overflow-y:auto"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <select name="medicines[{{ $i }}][dosage_id]" class="form-select form-select-sm">
                                                <option value="">-</option>
                                                @foreach($masters['dosages'] as $dos)
                                                    <option value="{{ $dos->id }}" {{ $rx->dosage_id == $dos->id ? 'selected' : '' }}>{{ $dos->dosage }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="medicines[{{ $i }}][duration]" class="form-control" value="{{ $rx->duration ?? '' }}" placeholder="e.g. 5" min="1">
                                                <span class="input-group-text">Days</span>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="medicines[{{ $i }}][instructions]" class="form-control form-control-sm" value="{{ $rx->instructions }}" list="instructions_list" placeholder="Instructions">
                                        </td>
                                        <td>
                                            <select name="medicines[{{ $i }}][eye]" class="form-select form-select-sm">
                                                <option value="">-</option>
                                                @foreach(['RE','LE','Both','OU'] as $eyeOpt)
                                                    <option value="{{ $eyeOpt }}" {{ $rx->eye === $eyeOpt ? 'selected' : '' }}>{{ $eyeOpt }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove(); updateLivePreview();">x</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="rxEmpty">
                                        <td colspan="6" class="text-center text-muted">No medicines added</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>
</form>

<datalist id="sph_cyl_list">
    @php
        $uniqueSphCyl = collect($masters['sph_cyl'])
            ->map(fn ($o) => ltrim(trim($o->value), '+-'))
            ->reject(fn ($v) => $v === '')
            ->unique()
            ->values();
    @endphp
    @foreach($uniqueSphCyl as $cleanVal)
        @if(in_array((string) $cleanVal, ['0', '0.00', 'Plano', 'PL']))
            <option value="{{ $cleanVal }}"></option>
        @else
            <option value="+{{ $cleanVal }}"></option>
            <option value="-{{ $cleanVal }}"></option>
        @endif
    @endforeach
</datalist>
<datalist id="axis_list">
    @foreach($masters['axis'] as $opt)
        <option value="{{ $opt->value }}"></option>
    @endforeach
</datalist>
<datalist id="instructions_list">
    @foreach($masters['instructions'] as $ins)
        <option value="{{ $ins->value }}"></option>
    @endforeach
</datalist>
<datalist id="medicine_list">
    @foreach($masters['medicines'] as $med)
        <option value="{{ $med->brand_name ?: $med->name }}"></option>
    @endforeach
</datalist>

<script>
    function formatOpto(val) {
        const num = parseFloat(val);
        if (isNaN(num)) { return val; }
        if (num === 0) { return '0.00'; }
        return num > 0 ? '+' + num.toFixed(2) : num.toFixed(2);
    }

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('primaryExamForm');
    const rxBody = document.getElementById('rxBody');

    let rxRowIndex = {{ max($exam?->prescriptions?->count() ?? 0, 0) }};
    const dosagesJson = @json($masters['dosages']->pluck('dosage','id'));
    const durationsJson = @json($masters['durations']->pluck('duration')->values());

    function syncComplaintDuration() {
        const numEl = document.getElementById('cc_since_number');
        const unitEl = document.getElementById('cc_since_unit');
        const hiddenEl = document.getElementById('cc_since_hidden');
        const n = (numEl?.value || '').trim();
        hiddenEl.value = n ? (n + ' ' + unitEl.value) : '';
    }

    function attachMedicineSearch(wrap) {
        if (!wrap) { return; }
        const input = wrap.querySelector('.medicine-search');
        const suggest = wrap.querySelector('.medicine-suggest');
        const hidden = wrap.closest('tr').querySelector('.med-id-input');
        let timer;

        input.addEventListener('input', () => {
            clearTimeout(timer);
            const q = input.value.trim();
            hidden.value = '';
            if (q.length < 2) {
                suggest.style.display = 'none';
                return;
            }
            timer = setTimeout(() => {
                fetch(`{{ route("hospital.ajax.medicines.search", ["slug" => $slug]) }}?q=${encodeURIComponent(q)}`)
                    .then(r => r.json())
                    .then(items => {
                        if (!items.length) {
                            suggest.style.display = 'none';
                            return;
                        }
                        suggest.innerHTML = items.map(m =>
                            `<div class="med-opt" data-id="${m.id}" data-name="${m.brand_name || m.name}" style="padding:.45rem .75rem;cursor:pointer;border-bottom:1px solid #f0f0f0">` +
                            `<strong>${m.name}</strong>` +
                            `${m.brand_name ? `<span style="color:#888"> (${m.brand_name})</span>` : ''}` +
                            `</div>`
                        ).join('');
                        suggest.style.display = 'block';
                        suggest.querySelectorAll('.med-opt').forEach(opt => {
                            opt.addEventListener('mousedown', e => {
                                e.preventDefault();
                                input.value = opt.dataset.name;
                                hidden.value = opt.dataset.id;
                                suggest.style.display = 'none';
                                updateLivePreview();
                            });
                        });
                    });
            }, 250);
        });

        input.addEventListener('blur', () => {
            setTimeout(() => { suggest.style.display = 'none'; }, 150);
        });
    }

    window.addMedicineRow = function (medId = '', medName = '', dosageId = '', duration = '', instruction = '', eye = '') {
        const emptyRow = document.getElementById('rxEmpty');
        if (emptyRow) { emptyRow.remove(); }

        const idx = rxRowIndex++;
        const dosageOptions = Object.entries(dosagesJson)
            .map(([id, label]) => `<option value="${id}" ${String(dosageId) === String(id) ? 'selected' : ''}>${label}</option>`).join('');
        const eyeOptions = ['RE', 'LE', 'Both', 'OU']
            .map(e => `<option value="${e}" ${eye === e ? 'selected' : ''}>${e}</option>`).join('');

        const row = document.createElement('tr');
        row.className = 'rx-row';
        row.innerHTML = `
            <td>
                <input type="hidden" name="medicines[${idx}][medicine_id]" value="${medId}" class="med-id-input">
                <div class="medicine-search-wrap" style="position:relative">
                    <input type="text" name="medicines[${idx}][name]" class="form-control form-control-sm medicine-search" placeholder="Medicine name" autocomplete="off" list="medicine_list" value="${medName}">
                    <div class="medicine-suggest" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #dee2e6;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.12);z-index:100;max-height:180px;overflow-y:auto"></div>
                </div>
            </td>
            <td><select name="medicines[${idx}][dosage_id]" class="form-select form-select-sm"><option value="">-</option>${dosageOptions}</select></td>
            <td><div class="input-group input-group-sm"><input type="number" name="medicines[${idx}][duration]" class="form-control" value="${duration}" placeholder="5" min="1"><span class="input-group-text" style="font-size:0.75rem">Days</span></div></td>
            <td><input type="text" name="medicines[${idx}][instructions]" class="form-control form-control-sm" list="instructions_list" placeholder="Instructions" value="${instruction}"></td>
            <td><select name="medicines[${idx}][eye]" class="form-select form-select-sm"><option value="">-</option>${eyeOptions}</select></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove(); checkProgress(); updateLivePreview();">x</button></td>
        `;
        rxBody.appendChild(row);
        attachMedicineSearch(row.querySelector('.medicine-search-wrap'));
        if (!medName) { row.querySelector('.medicine-search').focus(); }
    };

    function selectedLabels(selector) {
        return Array.from(form.querySelectorAll(selector + ':checked')).map(el => {
            if (el.id) {
                const lbl = form.querySelector('label[for="' + el.id + '"]');
                if (lbl) { return lbl.textContent.trim(); }
            }
            const span = el.closest('label')?.querySelector('span');
            return span ? span.textContent.trim() : el.value;
        });
    }

    function hasValue(selector) {
        const inputs = document.querySelectorAll(selector);
        for (const el of inputs) {
            if (el.type === 'checkbox' || el.type === 'radio') {
                if (el.checked) { return true; }
            } else if (String(el.value || '').trim() !== '') {
                return true;
            }
        }
        return false;
    }

    window.checkProgress = function () {
        const setState = (id, filled) => {
            const btn = document.getElementById(id);
            if (!btn) { return; }
            if (filled) {
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-primary');
            } else {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-secondary');
            }
        };

        setState('btn-context', hasValue('select[name="doctor_id"]'));
        setState('btn-clinical', hasValue('input[name="exam_data[complaints][]"], input[name="exam_data[kcos][]"], input[name="cc_since_number"]'));
        setState('btn-vision', hasValue('select[name="exam_data[vision][vn_re]"], select[name="exam_data[pg][re][ds]"]'));
        setState('btn-st', hasValue('input[name="exam_data[st][re][ds]"], select[name="exam_data[nct][iop_re]"]'));
        setState('btn-oe', hasValue('select[name="exam_data[oe][lid_re]"], select[name="exam_data[fundus][disc_re]"]'));
        setState('btn-rx', hasValue('input[name="exam_data[diagnoses][]"], input[name^="medicines["][name$="[name]"]'));
    };

    window.updateLivePreview = function () {
        const val = (name) => {
            const el = form.querySelector(`[name="${name}"]`);
            return (el && String(el.value).trim() !== '') ? el.value : '-';
        };

        const byId = (id) => {
            const el = document.getElementById(id);
            return (el && String(el.value).trim() !== '') ? el.value : '-';
        };

        const complaints = selectedLabels('input[name="exam_data[complaints][]"]');
        const kcos = selectedLabels('input[name="exam_data[kcos][]"]');
        const ccDur = `${byId('cc_since_number')} ${byId('cc_since_unit')}`;

        // V-notation block helper: re value on top, le on bottom, separated by <
        const makeVn = (label, re, le) =>
            `<div class="d-inline-flex align-items-center me-3 mb-1">` +
            `<span class="fw-bold me-1" style="font-size:11px;">${label}</span>` +
            `<span class="fw-light mx-1" style="font-size:1.4rem;line-height:0.5;">&lt;</span>` +
            `<div class="d-flex flex-column" style="font-size:11px;line-height:1.3;">` +
            `<span>${re}</span><span>${le}</span></div></div>`;

        const vnRe   = val('exam_data[vision][vn_re]');
        const vnLe   = val('exam_data[vision][vn_le]');
        const phRe   = val('exam_data[vision][pnvn_re]');
        const phLe   = val('exam_data[vision][pnvn_le]');
        const nrRe   = val('exam_data[vision][nrvn_re]');
        const nrLe   = val('exam_data[vision][nrvn_le]');
        const iopRe  = val('exam_data[nct][iop_re]');
        const iopLe  = val('exam_data[nct][iop_le]');

        // BOX 1: History (inline) + V-notation vision
        const historyHtml =
            `<div class="mb-1" style="font-size:12px;">` +
            `<strong>C/O:</strong> ${complaints.length ? complaints.join(', ') : '-'} ` +
            `<strong class="ms-2">Since:</strong> ${ccDur}</div>` +
            `<div class="mb-1" style="font-size:12px;"><strong>KCO:</strong> ${kcos.length ? kcos.join(', ') : '-'}</div>` +
            `<div class="d-flex flex-wrap align-items-center border-top pt-1 mt-1">` +
            makeVn('Vn', vnRe, vnLe) +
            makeVn('PH', phRe, phLe) +
            makeVn('NrVn', nrRe, nrLe) +
            `<div class="d-inline-flex align-items-center me-3 mb-1" style="font-size:11px;"><strong>IOP:</strong>&nbsp;${iopRe} / ${iopLe}</div>` +
            `</div>`;
        document.getElementById('canvas_history').innerHTML = historyHtml;

        // BOX 1 lower: PG table (colspan double-header)
        const visionHtml =
            `<table class="table table-sm table-bordered border-dark text-center mb-0" style="font-size:11px;">` +
            `<thead>` +
            `<tr><th></th><th class="bg-secondary text-white" colspan="4">Right Eye PG</th><th class="bg-secondary text-white" colspan="4">Left Eye PG</th></tr>` +
            `<tr><th></th><th>DS</th><th>DC</th><th>AX</th><th>VA</th><th>DS</th><th>DC</th><th>AX</th><th>VA</th></tr>` +
            `</thead><tbody>` +
            `<tr><th>D</th>` +
            `<td>${val('exam_data[pg][re][ds]')}</td><td>${val('exam_data[pg][re][dc]')}</td><td>${val('exam_data[pg][re][ax]')}</td><td>${val('exam_data[pg][re][vn]')}</td>` +
            `<td>${val('exam_data[pg][le][ds]')}</td><td>${val('exam_data[pg][le][dc]')}</td><td>${val('exam_data[pg][le][ax]')}</td><td>${val('exam_data[pg][le][vn]')}</td>` +
            `</tr><tr><th>N</th>` +
            `<td colspan="3" class="text-muted" style="font-size:10px;">Add: ${val('exam_data[pg][re][add]')}</td><td>${val('exam_data[pg][re][near_vn]')}</td>` +
            `<td colspan="3" class="text-muted" style="font-size:10px;">Add: ${val('exam_data[pg][le][add]')}</td><td>${val('exam_data[pg][le][near_vn]')}</td>` +
            `</tr></tbody></table>`;
        document.getElementById('canvas_vision').innerHTML = visionHtml;

        // BOX 2 top: ST double-header table (Right Eye / Left Eye, D & N rows)
        const stHtml =
            `<div class="d-flex gap-3 mb-1" style="font-size:11px;"><strong>ADD:</strong> ${val('exam_data[st][add]')} &nbsp; <strong>Lens:</strong> ${val('exam_data[st][lens_type]')}</div>` +
            `<table class="table table-sm table-bordered border-dark text-center mb-0" style="font-size:11px;">` +
            `<thead>` +
            `<tr><th></th><th class="bg-dark text-white" colspan="4">Right Eye</th><th class="bg-dark text-white" colspan="4">Left Eye</th></tr>` +
            `<tr><th></th><th>SPH</th><th>CYL</th><th>AXIS</th><th>VA</th><th>SPH</th><th>CYL</th><th>AXIS</th><th>VA</th></tr>` +
            `</thead><tbody>` +
            `<tr><th>D</th>` +
            `<td>${val('exam_data[st][re][ds]')}</td><td>${val('exam_data[st][re][dc]')}</td><td>${val('exam_data[st][re][ax]')}</td><td>${vnRe}</td>` +
            `<td>${val('exam_data[st][le][ds]')}</td><td>${val('exam_data[st][le][dc]')}</td><td>${val('exam_data[st][le][ax]')}</td><td>${vnLe}</td>` +
            `</tr><tr><th>N</th>` +
            `<td>${val('exam_data[st][re][ns]')}</td><td>${val('exam_data[st][re][nc]')}</td><td>${val('exam_data[st][re][na]')}</td><td>${nrRe}</td>` +
            `<td>${val('exam_data[st][le][ns]')}</td><td>${val('exam_data[st][le][nc]')}</td><td>${val('exam_data[st][le][na]')}</td><td>${nrLe}</td>` +
            `</tr></tbody></table>`;
        document.getElementById('canvas_st').innerHTML = stHtml;

        // BOX 3: O/E table (SAC → OTHER, no fundus)
        const oeHtml =
            `<table class="table table-sm table-bordered border-dark text-center mb-0" style="font-size:11px;">` +
            `<thead><tr><th class="bg-dark text-white">O/E</th><th class="bg-dark text-white">RIGHT</th><th class="bg-dark text-white">LEFT</th></tr></thead>` +
            `<tbody>` +
            `<tr><th>SAC</th><td>${val('exam_data[oe][sac_re]')}</td><td>${val('exam_data[oe][sac_le]')}</td></tr>` +
            `<tr><th>LID</th><td>${val('exam_data[oe][lid_re]')}</td><td>${val('exam_data[oe][lid_le]')}</td></tr>` +
            `<tr><th>CONJ</th><td>${val('exam_data[oe][conj_re]')}</td><td>${val('exam_data[oe][conj_le]')}</td></tr>` +
            `<tr><th>CORNEA</th><td>${val('exam_data[oe][cornea_re]')}</td><td>${val('exam_data[oe][cornea_le]')}</td></tr>` +
            `<tr><th>AC</th><td>${val('exam_data[oe][ac_re]')}</td><td>${val('exam_data[oe][ac_le]')}</td></tr>` +
            `<tr><th>IRIS</th><td>${val('exam_data[oe][iris_re]')}</td><td>${val('exam_data[oe][iris_le]')}</td></tr>` +
            `<tr><th>PUPIL</th><td>${val('exam_data[oe][pupil_re]')}</td><td>${val('exam_data[oe][pupil_le]')}</td></tr>` +
            `<tr><th>LENS</th><td>${val('exam_data[oe][lens_re]')}</td><td>${val('exam_data[oe][lens_le]')}</td></tr>` +
            `<tr><th>EM</th><td>${val('exam_data[oe][em_re]')}</td><td>${val('exam_data[oe][em_le]')}</td></tr>` +
            `<tr><th>COVERTEST</th><td>${val('exam_data[oe][covertest_re]')}</td><td>${val('exam_data[oe][covertest_le]')}</td></tr>` +
            `<tr><th>OTHER</th><td>${val('exam_data[oe][other_re]')}</td><td>${val('exam_data[oe][other_le]')}</td></tr>` +
            `</tbody></table>`;
        document.getElementById('canvas_oe').innerHTML = oeHtml;

        // BOX 4: Fundus table
        const fundusHtml =
            `<table class="table table-sm table-bordered border-dark text-center mb-0" style="font-size:11px;">` +
            `<thead><tr><th class="bg-dark text-white">Fundus</th><th class="bg-dark text-white">RIGHT</th><th class="bg-dark text-white">LEFT</th></tr></thead>` +
            `<tbody>` +
            `<tr><th>DISC</th><td>${val('exam_data[fundus][disc_re]')}</td><td>${val('exam_data[fundus][disc_le]')}</td></tr>` +
            `<tr><th>FR</th><td>${val('exam_data[fundus][fr_re]')}</td><td>${val('exam_data[fundus][fr_le]')}</td></tr>` +
            `<tr><th>COMMENT</th><td colspan="2" class="text-start">${val('exam_data[fundus][comment]')}</td></tr>` +
            `</tbody></table>`;
        document.getElementById('canvas_fundus').innerHTML = fundusHtml;

        // BOX 2 bottom: Diagnosis + Rx
        const diagnoses = selectedLabels('input[name="exam_data[diagnoses][]"]');
        const diagnosisText = diagnoses.length ? diagnoses.join(', ') : '-';
        const dilateVal = form.querySelector('input[name="exam_data[dilate]"]:checked')?.value || 'No';

        const rxRows = Array.from(document.querySelectorAll('#rxBody .rx-row'));
        const rxBodyHtml = rxRows.map((row) => {
            const name = row.querySelector('.medicine-search')?.value?.trim() || '';
            if (!name) { return ''; }
            const dosageId = row.querySelector('[name*="[dosage_id]"]')?.value || '';
            const dosage = dosageId && dosagesJson[dosageId] ? dosagesJson[dosageId] : '-';
            const durRaw = row.querySelector('[name*="[duration]"]')?.value.trim() || '';
            const duration = durRaw ? durRaw + ' Days' : '-';
            const instruction = row.querySelector('[name*="[instructions]"]')?.value || '-';
            const eye = row.querySelector('[name*="[eye]"]')?.value || '-';
            return `<tr><td>${name}</td><td>${dosage}</td><td>${duration}</td><td>${eye}</td><td>${instruction}</td></tr>`;
        }).filter(Boolean).join('');

        const rxHtml =
            `<div class="mb-1" style="font-size:11px;"><strong>Dx:</strong> ${diagnosisText} &nbsp; <strong>Dilate:</strong> ${dilateVal}</div>` +
            `<table class="table table-sm table-bordered border-dark mb-0" style="font-size:11px;">` +
            `<thead><tr><th class="bg-dark text-white">Medicine</th><th class="bg-dark text-white">Dose</th><th class="bg-dark text-white">Days</th><th class="bg-dark text-white">Eye</th><th class="bg-dark text-white">Instr.</th></tr></thead>` +
            `<tbody>${rxBodyHtml || '<tr><td colspan="5" class="text-center text-muted">No medicines</td></tr>'}</tbody>` +
            `</table>`;
        document.getElementById('canvas_rx').innerHTML = rxHtml;
    };

    // Medicine Group selector — AJAX auto-fill
    const groupApiBase = '{{ url($slug . "/ajax/medicine-group") }}';
    document.getElementById('rxGroupSelector')?.addEventListener('change', function () {
        const groupId = this.value;
        if (!groupId) { return; }
        fetch(`${groupApiBase}/${groupId}`)
            .then(r => r.json())
            .then(group => {
                (group.items || []).forEach(item => {
                    addMedicineRow(
                        item.medicine_id || '',
                        item.medicine ? (item.medicine.brand_name || item.medicine.name || '') : '',
                        item.dosage_id || '',
                        item.duration || '',
                        '',
                        ''
                    );
                });
                this.value = '';
                checkProgress();
                updateLivePreview();
            })
            .catch(() => {
                alert('Could not load medicine group. Please try again.');
            });
    });

    document.getElementById('cc_since_number')?.addEventListener('input', () => {
        checkProgress();
        updateLivePreview();
    });
    document.getElementById('cc_since_unit')?.addEventListener('change', () => {
        syncComplaintDuration();
        checkProgress();
        updateLivePreview();
    });

    // Dilation time visibility toggle
    const dilationWrap = document.getElementById('dilationTimeWrap');
    const dilateYes   = document.getElementById('dilateYes');
    const dilateNo    = document.getElementById('dilateNo');
    const dilateTimeInput = document.getElementById('dilation_time');

    function toggleDilationTime() {
        const isDilated = dilateYes?.checked;
        if (dilationWrap) { dilationWrap.style.display = isDilated ? '' : 'none'; }
        if (!isDilated && dilateTimeInput) { dilateTimeInput.value = ''; }
    }

    dilateYes?.addEventListener('change', toggleDilationTime);
    dilateNo?.addEventListener('change', toggleDilationTime);

    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function () {
            checkProgress();
            updateLivePreview();
        });
    });

    form.addEventListener('input', () => {
        checkProgress();
        updateLivePreview();
    });
    form.addEventListener('change', () => {
        checkProgress();
        updateLivePreview();
    });

    document.querySelectorAll('.medicine-search-wrap').forEach(attachMedicineSearch);

    syncComplaintDuration();
    checkProgress();
    updateLivePreview();
});

// ─── LocalStorage Draft Auto-Save ────────────────────────────────────────────
(function () {
    const draftKey = 'hms_primary_draft_pt_{{ $patient->id ?? 0 }}';
    const form = document.getElementById('primaryExamForm');

    function saveDraft() {
        const data = {};
        form.querySelectorAll('input, select, textarea').forEach(el => {
            if (!el.name) { return; }
            if (el.type === 'checkbox' || el.type === 'radio') {
                if (el.checked) {
                    // For checkbox arrays store array of values; for single flags store true
                    if (!Object.prototype.hasOwnProperty.call(data, el.name)) {
                        data[el.name] = [];
                    }
                    if (Array.isArray(data[el.name])) {
                        data[el.name].push(el.value);
                    }
                }
            } else {
                data[el.name] = el.value;
            }
        });
        try {
            localStorage.setItem(draftKey, JSON.stringify(data));
        } catch (_) { /* quota exceeded — silently ignore */ }
    }

    function loadDraft() {
        let saved;
        try {
            saved = localStorage.getItem(draftKey);
        } catch (_) { return; }
        if (!saved) { return; }

        let data;
        try {
            data = JSON.parse(saved);
        } catch (_) { return; }

        Object.entries(data).forEach(([name, value]) => {
            if (Array.isArray(value)) {
                // Checkbox groups — tick matching values
                form.querySelectorAll(`input[name="${name}"][type="checkbox"]`).forEach(cb => {
                    cb.checked = value.includes(cb.value);
                });
            } else {
                const el = form.querySelector(`[name="${name}"]`);
                if (!el) { return; }
                if (el.type === 'radio') {
                    const radio = form.querySelector(`[name="${name}"][value="${value}"]`);
                    if (radio) { radio.checked = true; }
                } else {
                    el.value = value;
                }
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    form.addEventListener('input', saveDraft);
    form.addEventListener('change', saveDraft);

    form.addEventListener('submit', function () {
        try { localStorage.removeItem(draftKey); } catch (_) { /* ignore */ }
    });

    // Restore after a short delay so Select2 / dynamic rows are initialised
    setTimeout(loadDraft, 300);
})();
</script>

@endsection
