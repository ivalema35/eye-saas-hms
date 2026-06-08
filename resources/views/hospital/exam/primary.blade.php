@extends('hospital.layouts.app')
@section('title', 'Primary Examination - '.$patient->full_name)
@section('page-header', 'Primary Eye Examination')

@section('page-actions')
@if(auth('hospital_user')->user()?->role?->slug !== 'doctor')
    <a href="{{ route('hospital.patients.index', ['slug' => $slug, 'patient' => $patient->id]) }}"
       class="btn secondary-exam-back-btn btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Patient
    </a>
@endif
    @if($exam)
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer"></i> Print Rx
        </button>
    @endif
    @haspermission('opd.foc.create')
        <button type="button" class="btn secondary-exam-foc-btn btn-sm" data-bs-toggle="modal" data-bs-target="#focRequestExamModal">
            <i class="fa-solid fa-hand-holding-heart"></i> Request FOC
        </button>
    @endhaspermission
@endsection

@section('content')

<style>

        .secondary-exam-back-btn {
        border: 1px solid rgba(27, 79, 114, 0.22);
        background: rgba(27, 79, 114, 0.06);
        color: #1B4F72;
        border-radius: 8px;
        font-weight: 600;
        box-shadow: 0 2px 6px rgba(27, 79, 114, 0.08);
        transition: background-color 160ms ease, border-color 160ms ease, color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
    }

    .secondary-exam-back-btn:hover,
    .secondary-exam-back-btn:focus {
        background: rgba(27, 79, 114, 0.12);
        border-color: rgba(27, 79, 114, 0.34);
        color: #1B4F72;
        box-shadow: 0 4px 10px rgba(27, 79, 114, 0.12);
        transform: translateY(-1px);
    }

    .secondary-exam-foc-btn {
        background: #1B4F72;
        border: 1px solid #1B4F72;
        color: #fff;
        border-radius: 8px;
        font-weight: 600;
        box-shadow: 0 4px 10px rgba(27, 79, 114, 0.18);
        transition: background-color 160ms ease, border-color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
    }

    .secondary-exam-foc-btn:hover,
    .secondary-exam-foc-btn:focus {
        background: #16405d;
        border-color: #16405d;
        color: #fff;
        box-shadow: 0 6px 14px rgba(27, 79, 114, 0.24);
        transform: translateY(-1px);
    }

    .step-group-label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #94a3b8;
        padding: 10px 4px 4px;
        border-top: 1px solid #e2e8f0;
        margin-top: 4px;
    }
    .step-group-label.first { border-top: none; padding-top: 0; margin-top: 0; }
    /* Vision dropdown */
    .vision-select-wrap { position: relative; }
    /* C/O custom dropdown */
    .co-select-wrap { position: relative; display: inline-block; width: 100%; max-width: 300px; }
    .co-dropdown {
        position: fixed; width: 300px;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
        box-shadow: 0 8px 28px rgba(15,23,42,.14); z-index: 9999;
        max-height: 300px; overflow-y: auto; display: none;
    }
    .co-dropdown.show { display: block; }
    .co-section-lbl {
        font-size: 10px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .07em; color: #94a3b8; padding: 8px 12px 4px;
        position: sticky; top: 0; background: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
    }
    .co-item {
        display: flex; align-items: center; padding: 8px 12px;
        cursor: pointer; font-size: 13px; gap: 8px; user-select: none;
    }
    .co-item:hover { background: #eff6ff; }
    .co-item-name { flex: 1; color: #1e293b; }
    .co-fav-btn {
        background: none; border: none; cursor: pointer; font-size: 17px;
        padding: 0; line-height: 1; color: #cbd5e1; transition: color .15s, transform .15s;
        flex-shrink: 0;
    }
    .co-fav-btn:hover { transform: scale(1.2); }
    .co-fav-btn.fav-on { color: #f59e0b; }
    .co-empty { padding: 14px 12px; text-align: center; color: #94a3b8; font-size: 13px; }
    .step-group-tag {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #475569;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 5px;
        padding: 3px 9px;
        white-space: nowrap;
        align-self: center;
    }
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

    /* આ સ્ટાઇલ ફક્ત આ જ પેજ માટે છે */
.exam-layout-wrapper {
    display: grid;
    grid-template-columns: 240px 1fr; /* સાઈડબાર અને મેઈન કન્ટેન્ટ */
    gap: 20px;
    align-items: start;
    margin-top: 10px;
}

.doctor-stepper-sidebar {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    padding: 15px;
    border-radius: 12px;
    position: sticky;
    top: 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.doctor-stepper-sidebar .step-btn {
    width: 100%;
    text-align: left !important;
    padding: 10px 15px !important;
    border-radius: 8px !important;
    font-weight: 600;
    transition: all 0.2s;
}

/* આ વધારાની સ્ટાઇલ મેઈન કેનવાસને સાઈડબાર સાથે સરખું રાખશે */
.main-canvas {
    width: 100%;
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

@if(auth('hospital_user')->user()?->role?->slug === 'doctor')
<form id="primaryExamForm" method="POST" action="{{ route('hospital.exam.primary.save', ['slug' => $slug, 'id' => $patient->id]) }}" novalidate>
    @csrf

    <div class="exam-layout-wrapper">
        
        {{-- ૨. નવી સાઈડબાર --}}
        <div class="doctor-stepper-sidebar">
            <h6 class="fw-bold text-muted mb-2 ps-2">EXAM STEPS</h6>

            <div class="step-group-label first">Primary Exam</div>
            <button type="button" class="btn btn-outline-secondary step-btn" id="btn-clinical" data-bs-toggle="modal" data-bs-target="#modalClinical">C/O</button>
            <button type="button" class="btn btn-outline-secondary step-btn" id="btn-hko" data-bs-toggle="modal" data-bs-target="#modalHko">H/O &amp; K/C/O</button>
            <button type="button" class="btn btn-outline-secondary step-btn" id="btn-vision" data-bs-toggle="modal" data-bs-target="#modalVision">Vision</button>
            <button type="button" class="btn btn-outline-secondary step-btn" id="btn-pg" data-bs-toggle="modal" data-bs-target="#modalPG">PG</button>
            <button type="button" class="btn btn-outline-secondary step-btn" id="btn-st" data-bs-toggle="modal" data-bs-target="#modalST">ST</button>
            <button type="button" class="btn btn-outline-secondary step-btn" id="btn-nct" data-bs-toggle="modal" data-bs-target="#modalNCT">NCT</button>
            <button type="button" class="btn btn-outline-secondary step-btn" id="btn-oe" data-bs-toggle="modal" data-bs-target="#modalOE">O/E</button>
            <button type="button" class="btn btn-outline-secondary step-btn" id="btn-fundus" data-bs-toggle="modal" data-bs-target="#modalFundus">Fundus</button>
            <button type="button" class="btn btn-outline-secondary step-btn" id="btn-dilate" data-bs-toggle="modal" data-bs-target="#modalDilate">Dilate</button>

            <div class="step-group-label">Secondary Exam</div>
            <button type="button" class="btn btn-outline-secondary step-btn" id="btn-diagnosis" data-bs-toggle="modal" data-bs-target="#modalDiagnosis">Diagnosis</button>
            <button type="button" class="btn btn-outline-secondary step-btn" id="btn-rx" data-bs-toggle="modal" data-bs-target="#modalRx">Medicine</button>
            <button type="button" class="btn btn-outline-secondary step-btn" id="btn-advice" data-bs-toggle="modal" data-bs-target="#modalAdvice">Advice</button>

            <hr>
            <button type="submit" class="btn btn-success fw-bold w-100">Save Exam</button>
        </div>

        {{-- ૩. મેઈન કેનવાસ (તમારો જૂનો કેનવાસ કોડ) --}}
        <div class="main-canvas">
            <div class="card shadow-sm" style="padding:16px;" id="liveReportCanvas">
                <div class="row g-2 clinical-grid-container" style="font-size:13px;">
                    {{-- તમારી જૂની ફાઈલમાંથી આ DIV (LEFT/RIGHT Column) કોપી કરીને અહીં મૂકી દો --}}
                    <div class="col-6 col-md-6 d-flex flex-column gap-2">
                        <div class="canvas-box"><div class="canvas-section-title">History &amp; Vision</div><div id="canvas_history"><em class="text-muted" style="font-size:11px;">Enter chief complaints...</em></div><div id="canvas_vision" class="mt-1"></div></div>
                        <div class="canvas-box"><div class="canvas-section-title">Subjective Testing (ST)</div><div id="canvas_st" class="mb-1"></div><div class="canvas-section-title mt-1">Diagnosis &amp; Rx</div><div id="canvas_rx"></div></div>
                    </div>
                    <div class="col-6 col-md-6 d-flex flex-column gap-2">
                        <div class="canvas-box"><div class="canvas-section-title">On Examination (O/E)</div><div id="canvas_oe"></div></div>
                        <div class="canvas-box"><div class="canvas-section-title">Fundus</div><div id="canvas_fundus"></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
 <form id="primaryExamForm" method="POST" action="{{ route('hospital.exam.primary.save', ['slug' => $slug, 'id' => $patient->id]) }}" novalidate>
    @csrf

    <div class="stepper-wrap d-flex d-print-none justify-content-between align-items-center mb-3 p-2 bg-white rounded shadow-sm border gap-2 flex-wrap">
        <div class="d-flex align-items-center gap-1 flex-wrap">
            <span class="step-group-tag">Primary</span>
            <button type="button" class="btn btn-outline-secondary step-btn btn-sm" id="btn-clinical"  data-bs-toggle="modal" data-bs-target="#modalClinical">C/O</button>
            <button type="button" class="btn btn-outline-secondary step-btn btn-sm" id="btn-hko"       data-bs-toggle="modal" data-bs-target="#modalHko">H/O &amp; K/C/O</button>
            <button type="button" class="btn btn-outline-secondary step-btn btn-sm" id="btn-vision"    data-bs-toggle="modal" data-bs-target="#modalVision">Vision</button>
            <button type="button" class="btn btn-outline-secondary step-btn btn-sm" id="btn-pg"        data-bs-toggle="modal" data-bs-target="#modalPG">PG</button>
            <button type="button" class="btn btn-outline-secondary step-btn btn-sm" id="btn-st"        data-bs-toggle="modal" data-bs-target="#modalST">ST</button>
            <button type="button" class="btn btn-outline-secondary step-btn btn-sm" id="btn-nct"       data-bs-toggle="modal" data-bs-target="#modalNCT">NCT</button>
            <button type="button" class="btn btn-outline-secondary step-btn btn-sm" id="btn-oe"        data-bs-toggle="modal" data-bs-target="#modalOE">O/E</button>
            <button type="button" class="btn btn-outline-secondary step-btn btn-sm" id="btn-fundus"    data-bs-toggle="modal" data-bs-target="#modalFundus">Fundus</button>
            <button type="button" class="btn btn-outline-secondary step-btn btn-sm" id="btn-dilate"    data-bs-toggle="modal" data-bs-target="#modalDilate">Dilate</button>
            <span class="step-group-tag ms-1">Secondary</span>
            <button type="button" class="btn btn-outline-secondary step-btn btn-sm" id="btn-diagnosis" data-bs-toggle="modal" data-bs-target="#modalDiagnosis">Diagnosis</button>
            <button type="button" class="btn btn-outline-secondary step-btn btn-sm" id="btn-rx"        data-bs-toggle="modal" data-bs-target="#modalRx">Medicine</button>
            <button type="button" class="btn btn-outline-secondary step-btn btn-sm" id="btn-advice"    data-bs-toggle="modal" data-bs-target="#modalAdvice">Advice</button>
        </div>
        <button type="submit" class="btn btn-success fw-bold px-4 btn-sm">Save Exam</button>
    </div>


    {{-- Print-only hospital + patient header --}}
    <style>
        .print-logo {
            width: 72px;
            height: 72px;
            border-radius: 12px;
            background: #F8FAFC;
            border: 1px solid #E5E7EB;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .print-logo img { width: 100%; height: 100%; object-fit: contain; padding: 8px; }
        .print-logo span { font-size: 28px; color: #1B4F72; }
    </style>

    <div class="print-header d-none d-print-block mb-3 border-bottom border-dark pb-2">
        <div class="text-center mb-2">
            <div class="print-logo" style="margin:0 auto 8px;">
                @if(hospital_logo_url())
                    <img src="{{ hospital_logo_url() }}" alt="{{ hospital_name() }} logo">
                @else
                    <span>👁</span>
                @endif
            </div>
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
@endif
    <div class="modal fade" id="modalClinical" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">A. Clinical History - Chief Complaints (C/O)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="co-select-wrap">
                            <input type="text" id="coSearch" class="form-control form-control-sm" placeholder="Search complaint..." autocomplete="off" style="min-width:260px;">
                            <div id="coDropdown" class="co-dropdown"></div>
                        </div>
                        <button type="button" id="addCoRow" class="btn btn-sm btn-primary px-3">+ Add</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0" id="coTable">
                            <thead class="table-light text-center" style="font-size:12px;">
                                <tr>
                                    <th class="text-start" style="min-width:180px;">C/O</th>
                                    <th style="width:80px;">Since</th>
                                    <th style="width:120px;">Duration</th>
                                    <th style="width:110px;">Eye</th>
                                    <th>Comment</th>
                                    <th style="width:38px;"></th>
                                </tr>
                            </thead>
                            <tbody id="coBody">
                                @foreach($ed['co_rows'] ?? [] as $ri => $row)
                                <tr class="co-row">
                                    <td><input type="text" name="exam_data[co_rows][{{ $ri }}][complaint]" value="{{ $row['complaint'] ?? '' }}" class="form-control form-control-sm row-co-search" placeholder="Complaint" autocomplete="off"></td>
                                    <td>
                                        <select name="exam_data[co_rows][{{ $ri }}][since]" class="form-select form-select-sm">
                                            <option value="">-</option>
                                            @foreach(array_merge(range(1,30),[45,60,90]) as $n)
                                                <option value="{{ $n }}" {{ ($row['since'] ?? '') == $n ? 'selected' : '' }}>{{ $n }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="exam_data[co_rows][{{ $ri }}][unit]" class="form-select form-select-sm">
                                            @foreach(['Days','Weeks','Months','Years','Longtime'] as $u)
                                                <option value="{{ $u }}" {{ ($row['unit'] ?? 'Days') === $u ? 'selected' : '' }}>{{ $u }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="exam_data[co_rows][{{ $ri }}][eye]" class="form-select form-select-sm">
                                            <option value="">-</option>
                                            @foreach(['RE'=>'Right','LE'=>'Left','Both'=>'Both','OU'=>'OU'] as $val => $lbl)
                                                <option value="{{ $val }}" {{ ($row['eye'] ?? '') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="exam_data[co_rows][{{ $ri }}][comment]" value="{{ $row['comment'] ?? '' }}" class="form-control form-control-sm" placeholder="Comment"></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger px-2" onclick="this.closest('tr').remove(); checkProgress(); updateLivePreview();">&times;</button></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(empty($ed['co_rows']))
                    <p class="text-muted text-center mt-3" id="coEmptyMsg" style="font-size:13px;">No complaints added. Search above and click + Add.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: H/O & K/C/O --}}
    <div class="modal fade" id="modalHko" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">B. History &amp; Known Conditions (H/O &amp; K/C/O)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- K/C/O --}}
                    <div class="fw-semibold mb-2" style="font-size:13px;">K/C/O (Known Conditions)</div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="co-select-wrap">
                            <input type="text" id="kcoSearch" class="form-control form-control-sm" placeholder="Search condition..." autocomplete="off" style="min-width:260px;">
                            <div id="kcoDropdown" class="co-dropdown"></div>
                        </div>
                        <button type="button" id="addKcoRow" class="btn btn-sm btn-primary px-3">+ Add</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light text-center" style="font-size:12px;">
                                <tr>
                                    <th class="text-start" style="min-width:180px;">K/C/O</th>
                                    <th style="width:80px;">Since</th>
                                    <th style="width:120px;">Duration</th>
                                    <th>Comment</th>
                                    <th style="width:38px;"></th>
                                </tr>
                            </thead>
                            <tbody id="kcoBody">
                                @foreach($ed['kco_rows'] ?? [] as $ki => $krow)
                                <tr class="kco-row">
                                    <td><input type="text" name="exam_data[kco_rows][{{ $ki }}][condition]" value="{{ $krow['condition'] ?? '' }}" class="form-control form-control-sm row-kco-search" placeholder="Condition" autocomplete="off"></td>
                                    <td>
                                        <select name="exam_data[kco_rows][{{ $ki }}][since]" class="form-select form-select-sm">
                                            <option value="">-</option>
                                            @foreach(array_merge(range(1,30),[45,60,90]) as $n)
                                                <option value="{{ $n }}" {{ ($krow['since'] ?? '') == $n ? 'selected' : '' }}>{{ $n }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="exam_data[kco_rows][{{ $ki }}][unit]" class="form-select form-select-sm">
                                            @foreach(['Days','Weeks','Months','Years','Longtime'] as $u)
                                                <option value="{{ $u }}" {{ ($krow['unit'] ?? 'Years') === $u ? 'selected' : '' }}>{{ $u }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="exam_data[kco_rows][{{ $ki }}][comment]" value="{{ $krow['comment'] ?? '' }}" class="form-control form-control-sm" placeholder="Comment"></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger px-2" onclick="this.closest('tr').remove(); checkProgress(); updateLivePreview();">&times;</button></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(empty($ed['kco_rows']))
                    <p class="text-muted text-center mt-3" id="kcoEmptyMsg" style="font-size:13px;">No conditions added. Search above and click + Add.</p>
                    @endif
                    {{-- H/O --}}
                    <div class="mt-4">
                        <label class="form-label fw-semibold" style="font-size:13px; border-top:1px solid #e2e8f0; padding-top:12px; display:block;">H/O (History of Present Illness)</label>
                        <textarea name="exam_data[history]" id="historyTextarea" class="form-control form-control-sm" rows="2" placeholder="Enter patient history...">{{ $ed['history'] ?? '' }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Vision --}}
    <div class="modal fade" id="modalVision" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">Visual Acuity (VN)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <tbody>
                                {{-- Right Eye --}}
                                <tr>
                                    <td colspan="2" style="background:#fff0f0; color:#dc2626; font-weight:700; font-size:13px; letter-spacing:.04em; padding:8px 12px;">
                                        <i class="bi bi-eye-fill me-1"></i> Right Eye (RE)
                                    </td>
                                </tr>
                                @foreach([
                                    ['Vn',   'Distance Vision', 'vn',   'vn_re'],
                                    ['PnVn', 'Pinhole',         'pnvn', 'pnvn_re'],
                                    ['NrVn', 'Near Vision',     'nrvn', 'nrvn_re'],
                                ] as [$abbr, $full, $master, $field])
                                <tr style="background:#fffafa;">
                                    <td style="width:160px;">
                                        <span class="fw-bold" style="font-size:13px; color:#1e293b;">{{ $abbr }}</span>
                                        <div style="font-size:11px; color:#94a3b8;">{{ $full }}</div>
                                    </td>
                                    <td>
                                        <div class="vision-select-wrap">
                                            <input type="text" class="form-control form-control-sm vision-inp"
                                                placeholder="-" autocomplete="off"
                                                data-master="{{ $master }}" data-field="{{ $field }}"
                                                value="{{ $vision[$field] ?? '' }}">
                                            <input type="hidden" name="exam_data[vision][{{ $field }}]" value="{{ $vision[$field] ?? '' }}">
                                        </div>
                                    </td>
                                </tr>
                                @endforeach

                                {{-- Left Eye --}}
                                <tr>
                                    <td colspan="2" style="background:#eff6ff; color:#1d4ed8; font-weight:700; font-size:13px; letter-spacing:.04em; padding:8px 12px;">
                                        <i class="bi bi-eye-fill me-1"></i> Left Eye (LE)
                                    </td>
                                </tr>
                                @foreach([
                                    ['Vn',   'Distance Vision', 'vn',   'vn_le'],
                                    ['PnVn', 'Pinhole',         'pnvn', 'pnvn_le'],
                                    ['NrVn', 'Near Vision',     'nrvn', 'nrvn_le'],
                                ] as [$abbr, $full, $master, $field])
                                <tr style="background:#f8faff;">
                                    <td style="width:160px;">
                                        <span class="fw-bold" style="font-size:13px; color:#1e293b;">{{ $abbr }}</span>
                                        <div style="font-size:11px; color:#94a3b8;">{{ $full }}</div>
                                    </td>
                                    <td>
                                        <div class="vision-select-wrap">
                                            <input type="text" class="form-control form-control-sm vision-inp"
                                                placeholder="-" autocomplete="off"
                                                data-master="{{ $master }}" data-field="{{ $field }}"
                                                value="{{ $vision[$field] ?? '' }}">
                                            <input type="hidden" name="exam_data[vision][{{ $field }}]" value="{{ $vision[$field] ?? '' }}">
                                        </div>
                                    </td>
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

    {{-- MODAL: PG --}}
    <div class="modal fade" id="modalPG" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable" style="max-width:460px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius:14px;overflow:hidden;">
                <div class="modal-header" style="background:linear-gradient(135deg,#f8fafc,#e2e8f0);border-bottom:2px solid #e2e8f0;">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:36px;height:36px;background:#3b82f6;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-eyeglasses text-white" style="font-size:17px;"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0 fw-bold" style="color:#1e293b;font-size:15px;">Present Glasses</h5>
                            <small style="color:#64748b;font-size:10px;letter-spacing:.05em;">CURRENT PRESCRIPTION (PG)</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3" style="background:#f1f5f9;">
                    @php
                        $pgFields = [
                            'ds'      => ['abbr' => 'SPH',  'full' => 'Sphere',      'master' => 'sph_cyl', 'bipolar' => true],
                            'dc'      => ['abbr' => 'CYL',  'full' => 'Cylinder',    'master' => 'sph_cyl', 'bipolar' => true],
                            'ax'      => ['abbr' => 'AXIS', 'full' => 'Axis',        'master' => 'axis',    'bipolar' => false],
                            'vn'      => ['abbr' => 'VN',   'full' => 'Vision',      'master' => 'vn',      'bipolar' => false],
                            'add'     => ['abbr' => 'ADD',  'full' => 'Addition',    'master' => 'sph_cyl', 'bipolar' => true],
                            'near_vn' => ['abbr' => 'NV',   'full' => 'Near Vision', 'master' => 'nrvn',    'bipolar' => false],
                        ];
                    @endphp
                    <div class="d-flex flex-column gap-3">
                        @foreach([
                            're' => ['label' => 'Right Eye', 'abbr' => 'RE', 'hdr_bg' => '#dc2626', 'row_bg' => '#fffafa', 'border' => '#fecaca'],
                            'le' => ['label' => 'Left Eye',  'abbr' => 'LE', 'hdr_bg' => '#1d4ed8', 'row_bg' => '#f0f7ff', 'border' => '#bfdbfe'],
                        ] as $eye => $em)
                        <div class="card border-0" style="border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
                            <div class="d-flex align-items-center gap-2 px-3 py-2" style="background:{{ $em['hdr_bg'] }};">
                                <i class="bi bi-eye-fill text-white" style="font-size:14px;"></i>
                                <span class="text-white fw-bold" style="font-size:13px;letter-spacing:.04em;">{{ $em['label'] }} ({{ $em['abbr'] }})</span>
                            </div>
                            <table class="table table-sm align-middle mb-0" style="background:#fff;">
                                <tbody>
                                    @foreach($pgFields as $key => $meta)
                                    @php
                                        $uv = collect($masters[$meta['master']])->map(fn ($o) => ltrim(trim($o->value), '+-'))->reject(fn ($v) => $v === '')->unique()->values();
                                        $sv = $pg[$eye][$key] ?? '';
                                    @endphp
                                    <tr>
                                        <td style="width:110px;padding:6px 12px;background:{{ $em['row_bg'] }};border-right:2px solid {{ $em['border'] }};">
                                            <div class="fw-semibold" style="font-size:13px;color:#334155;">{{ $meta['abbr'] }}</div>
                                            <div style="font-size:10px;color:#94a3b8;">{{ $meta['full'] }}</div>
                                        </td>
                                        <td style="padding:5px 10px;">
                                            <select name="exam_data[pg][{{ $eye }}][{{ $key }}]"
                                                class="form-select form-select-sm"
                                                style="border:1px solid #e2e8f0;border-radius:6px;font-size:13px;color:#1e293b;">
                                                <option value="">—</option>
                                                @foreach($uv as $cv)
                                                    @if(!empty($meta['bipolar']) && !in_array((string) $cv, ['0', '0.00', 'Plano', 'PL']))
                                                        <option value="+{{ $cv }}" @selected($sv === '+'.$cv)>+{{ $cv }}</option>
                                                        <option value="-{{ $cv }}" @selected($sv === '-'.$cv)>-{{ $cv }}</option>
                                                    @else
                                                        <option value="{{ $cv }}" @selected($sv === (string) $cv)>{{ $cv }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer py-2" style="background:#f8fafc;border-top:1px solid #e2e8f0;">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">
                        <i class="bi bi-x me-1"></i>Close
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-dismiss="modal">
                        <i class="bi bi-check-lg me-1"></i>Done
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: ST --}}
    <div class="modal fade" id="modalST" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light"><h5 class="modal-title">Subjective Trial (ST)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead><tr><th style="width:160px"></th><th>RE</th><th>LE</th></tr></thead>
                            <tbody>
                                @foreach([
                                    'ds' => ['label' => 'SPH (Sphere)',   'list' => 'sph_cyl_list'],
                                    'dc' => ['label' => 'CYL (Cylinder)', 'list' => 'sph_cyl_list'],
                                    'ax' => ['label' => 'AXIS',           'list' => 'axis_list'],
                                    'ns' => ['label' => 'Near SPH',       'list' => 'sph_cyl_list'],
                                    'nc' => ['label' => 'Near CYL',       'list' => 'sph_cyl_list'],
                                    'na' => ['label' => 'Near AXIS',      'list' => 'axis_list'],
                                ] as $key => $meta)
                                    <tr>
                                        <td>{{ $meta['label'] }}</td>
                                        @foreach(['re','le'] as $eye)
                                            <td><input type="text" name="exam_data[st][{{ $eye }}][{{ $key }}]" value="{{ old('exam_data.st.'.$eye.'.'.$key, $st[$eye][$key] ?? '') }}" list="{{ $meta['list'] }}" class="form-control form-control-sm"></td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                <tr>
                                    <td>ADD (Addition)</td>
                                    <td colspan="2"><input type="text" name="exam_data[st][add]" value="{{ old('exam_data.st.add', $st['add'] ?? '') }}" list="sph_cyl_list" class="form-control form-control-sm"></td>
                                </tr>
                                <tr>
                                    <td>Lens Type</td>
                                    <td colspan="2"><input type="text" name="exam_data[st][lens_type]" value="{{ old('exam_data.st.lens_type', $st['lens_type'] ?? '') }}" class="form-control form-control-sm" placeholder="SV / Bifocal / Progressive"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button></div>
            </div>
        </div>
    </div>

    {{-- MODAL: NCT --}}
    <div class="modal fade" id="modalNCT" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light"><h5 class="modal-title">NCT (Intraocular Pressure — mmHg)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2">
                        @foreach(['re' => 'Right Eye', 'le' => 'Left Eye'] as $eye => $label)
                            <div class="col-md-6">
                                <label class="form-label">{{ $label }}</label>
                                <select name="exam_data[nct][iop_{{ $eye }}]" class="form-select form-select-sm">
                                    <option value="">-</option>
                                    @foreach($masters['nct'] as $opt)<option value="{{ $opt->value }}" {{ ($nct['iop_'.$eye] ?? '') === $opt->value ? 'selected' : '' }}>{{ $opt->value }}</option>@endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button></div>
            </div>
        </div>
    </div>

    {{-- MODAL: O/E --}}
    <div class="modal fade" id="modalOE" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light"><h5 class="modal-title">Ocular Examination (O/E)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead><tr><th style="width:140px"></th><th>RE</th><th>LE</th></tr></thead>
                            <tbody>
                                @foreach([
                                    'sac' => ['label' => 'SAC', 'master' => 'sac'], 'lid' => ['label' => 'Lid', 'master' => 'lid'],
                                    'conj' => ['label' => 'Conj', 'master' => 'conj'], 'cornea' => ['label' => 'Cornea', 'master' => 'cornea'],
                                    'ac' => ['label' => 'AC', 'master' => 'ac'], 'iris' => ['label' => 'Iris', 'master' => 'iris'],
                                    'pupil' => ['label' => 'Pupil', 'master' => 'pupil'], 'lens' => ['label' => 'Lens', 'master' => 'lens_master'],
                                    'em' => ['label' => 'EM', 'master' => 'em'], 'covertest' => ['label' => 'Covertest', 'master' => 'covertest'],
                                ] as $key => $meta)
                                    <tr>
                                        <td>{{ $meta['label'] }}</td>
                                        @foreach(['re','le'] as $eye)
                                            <td><select name="exam_data[oe][{{ $key }}_{{ $eye }}]" class="form-select form-select-sm"><option value="">-</option>@foreach($masters[$meta['master']] as $opt)<option value="{{ $opt->value }}" {{ ($oe[$key.'_'.$eye] ?? '') === $opt->value ? 'selected' : '' }}>{{ $opt->value }}</option>@endforeach</select></td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button></div>
            </div>
        </div>
    </div>

    {{-- MODAL: Fundus --}}
    <div class="modal fade" id="modalFundus" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light"><h5 class="modal-title">Fundus Examination</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead><tr><th style="width:140px"></th><th>RE</th><th>LE</th></tr></thead>
                            <tbody>
                                <tr>
                                    <td>Disc</td>
                                    @foreach(['re','le'] as $eye)<td><select name="exam_data[fundus][disc_{{ $eye }}]" class="form-select form-select-sm"><option value="">-</option>@foreach($masters['disc'] as $opt)<option value="{{ $opt->value }}" {{ ($fundus['disc_'.$eye] ?? '') === $opt->value ? 'selected' : '' }}>{{ $opt->value }}</option>@endforeach</select></td>@endforeach
                                </tr>
                                <tr>
                                    <td>FR</td>
                                    @foreach(['re','le'] as $eye)<td><select name="exam_data[fundus][fr_{{ $eye }}]" class="form-select form-select-sm"><option value="">-</option>@foreach($masters['fr'] as $opt)<option value="{{ $opt->value }}" {{ ($fundus['fr_'.$eye] ?? '') === $opt->value ? 'selected' : '' }}>{{ $opt->value }}</option>@endforeach</select></td>@endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button></div>
            </div>
        </div>
    </div>

    {{-- MODAL: Diagnosis --}}
    <div class="modal fade" id="modalDiagnosis" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light"><h5 class="modal-title">Diagnosis</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="d-flex flex-wrap gap-2" id="diagnosis-tags">
                        @foreach($masters['diagnoses'] as $d)
                            <div>
                                <input class="btn-check" type="checkbox" name="exam_data[diagnoses][]" id="dx_{{ $d->id }}" value="{{ $d->id }}"
                                    {{ in_array($d->id, $ed['diagnoses'] ?? []) ? 'checked' : '' }}>
                                <label class="btn btn-outline-danger rounded-pill btn-sm" for="dx_{{ $d->id }}">{{ $d->diagnosis }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button></div>
            </div>
        </div>
    </div>

    {{-- MODAL: Dilate --}}
    <div class="modal fade" id="modalDilate" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light"><h5 class="modal-title">Dilate Patient for Secondary Exam?</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
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
                <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button></div>
            </div>
        </div>
    </div>
 
    {{-- MODAL: Medicine --}}
   <div class="modal fade" id="modalRx" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light"><h5 class="modal-title">Medicines</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-semibold mb-0">Medicines</label>
                        <div class="d-flex gap-2 align-items-center">
                            <select id="rxGroupSelector" class="form-select form-select-sm" style="min-width:200px">
                                <option value="">-- Load Medicine Group --</option>
                                @foreach($masters['med_groups'] as $grp)<option value="{{ $grp->id }}">{{ $grp->name }}</option>@endforeach
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
                                                       placeholder="Medicine name" autocomplete="off" list="medicine_list">
                                                <div class="medicine-suggest" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #dee2e6;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.12);z-index:100;max-height:180px;overflow-y:auto"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <select name="medicines[{{ $i }}][dosage_id]" class="form-select form-select-sm">
                                                <option value="">-</option>
                                                @foreach($masters['dosages'] as $dos)<option value="{{ $dos->id }}" {{ $rx->dosage_id == $dos->id ? 'selected' : '' }}>{{ $dos->dosage }}</option>@endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="medicines[{{ $i }}][duration]" class="form-control" value="{{ $rx->duration ?? '' }}" placeholder="e.g. 5" min="1">
                                                <span class="input-group-text">Days</span>
                                            </div>
                                        </td>
                                        <td><input type="text" name="medicines[{{ $i }}][instructions]" class="form-control form-control-sm" value="{{ $rx->instructions }}" list="instructions_list" placeholder="Instructions"></td>
                                        <td>
                                            <select name="medicines[{{ $i }}][eye]" class="form-select form-select-sm">
                                                <option value="">-</option>
                                                @foreach(['RE','LE','Both','OU'] as $eyeOpt)<option value="{{ $eyeOpt }}" {{ $rx->eye === $eyeOpt ? 'selected' : '' }}>{{ $eyeOpt }}</option>@endforeach
                                            </select>
                                        </td>
                                        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove(); updateLivePreview();">x</button></td>
                                    </tr>
                                @empty
                                    <tr id="rxEmpty"><td colspan="6" class="text-center text-muted">No medicines added</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button></div>
            </div>
        </div>
    </div>

    {{-- MODAL: Advice --}}
    <div class="modal fade" id="modalAdvice" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">Clinical Advice &amp; Instructions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select from Master Advices</label>
                        <select id="advice_master_select" class="form-select">
                            <option value="">-- Select Advice --</option>
                            @foreach($masters['advices'] ?? [] as $adv)
                                <option value="{{ $adv->advice ?? '' }}">{{ $adv->advice ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Advice Text</label>
                        <textarea name="exam_data[advice]" id="advice_textarea" class="form-control" rows="6"
                                  placeholder="Enter clinical advice, post-operative care, lifestyle instructions, etc."
                                  maxlength="2000">{{ old('exam_data.advice', $ed['advice'] ?? '') }}</textarea>
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

        setState('btn-clinical',  document.querySelectorAll('#coBody .co-row').length > 0);
        setState('btn-hko',       document.querySelectorAll('#kcoBody .kco-row').length > 0 || (document.getElementById('historyTextarea')?.value || '').trim() !== '');
        setState('btn-vision',    hasValue('input[name="exam_data[vision][vn_re]"]'));
        setState('btn-pg',        hasValue('select[name="exam_data[pg][re][ds]"]'));
        setState('btn-st',        hasValue('input[name="exam_data[st][re][ds]"]'));
        setState('btn-nct',       hasValue('select[name="exam_data[nct][iop_re]"]'));
        setState('btn-oe',        hasValue('select[name="exam_data[oe][lid_re]"]'));
        setState('btn-fundus',    hasValue('select[name="exam_data[fundus][disc_re]"]'));
        setState('btn-diagnosis', hasValue('input[name="exam_data[diagnoses][]"]'));
        setState('btn-dilate',    hasValue('input[name="exam_data[dilate]"]'));
        setState('btn-rx',        hasValue('input[name^="medicines["][name$="[name]"]'));
        setState('btn-advice',    hasValue('textarea[name="exam_data[advice]"]'));
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

        // Build co_rows summary for canvas
        const coRows = Array.from(document.querySelectorAll('#coBody .co-row'));
        const coSummary = coRows.map(row => {
            const c = row.querySelector('[name*="[complaint]"]')?.value?.trim() || '';
            const s = row.querySelector('[name*="[since]"]')?.value || '';
            const u = row.querySelector('[name*="[unit]"]')?.value || '';
            const e = row.querySelector('[name*="[eye]"]')?.value || '';
            if (!c) { return ''; }
            return `${c}${s ? ' '+s+' '+u : ''}${e ? ' ('+e+')' : ''}`;
        }).filter(Boolean).join(', ');

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
            `<div class="mb-1" style="font-size:12px;"><strong>C/O:</strong> ${coSummary || '-'}</div>` +
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

    const defaultDilationTime = {{ (int) hospital_setting('default_dilation_time', 40) }};

    function toggleDilationTime() {
        const isDilated = dilateYes?.checked;
        if (dilationWrap) { dilationWrap.style.display = isDilated ? '' : 'none'; }
        if (isDilated && dilateTimeInput && !dilateTimeInput.value) {
            dilateTimeInput.value = defaultDilationTime;
        }
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

    // C/O rows and custom complaint dropdown
    const coSearch = document.getElementById('coSearch');
    const coDropdown = document.getElementById('coDropdown');
    const coComplaints = @json($masters['complaints']);
    let activeCoInput = null; // tracks which input is currently driving the dropdown

    const escapeAttr = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    function sortedCoComplaints() {
        return [...coComplaints].sort((a, b) => {
            if (Boolean(a.is_favourite) !== Boolean(b.is_favourite)) {
                return a.is_favourite ? -1 : 1;
            }
            return String(a.complaint).localeCompare(String(b.complaint));
        });
    }

    const coFavBase = '{{ url($slug."/masters/detail/complaints") }}';
    const coCsrf    = '{{ csrf_token() }}';

    function positionCoDropdown() {
        if (!activeCoInput || !coDropdown) { return; }
        const rect = activeCoInput.getBoundingClientRect();
        coDropdown.style.top  = (rect.bottom + 4) + 'px';
        coDropdown.style.left = rect.left + 'px';
        coDropdown.style.width = Math.max(rect.width, 300) + 'px';
    }

    function renderCoDropdown(queryOverride) {
        if (!coDropdown || !activeCoInput) { return; }
        const query = queryOverride !== undefined ? queryOverride : (activeCoInput.value || '').trim().toLowerCase();
        const items = sortedCoComplaints().filter(item => String(item.complaint).toLowerCase().includes(query));

        if (!items.length) {
            coDropdown.innerHTML = '<div class="co-empty">No complaints found</div>';
            positionCoDropdown();
            coDropdown.classList.add('show');
            return;
        }

        const groups = [
            { label: '⭐ Favourites',  rows: items.filter(i => i.is_favourite)  },
            { label: 'All Complaints', rows: items.filter(i => !i.is_favourite) },
        ].filter(g => g.rows.length);

        coDropdown.innerHTML = groups.map(g =>
            `<div class="co-section-lbl">${g.label}</div>` +
            g.rows.map(item =>
                `<div class="co-item" data-name="${escapeAttr(item.complaint)}">` +
                `<button type="button" class="co-fav-btn ${item.is_favourite ? 'fav-on' : ''}" data-id="${item.id}" title="${item.is_favourite ? 'Remove from favourites' : 'Add to favourites'}">${item.is_favourite ? '★' : '☆'}</button>` +
                `<span class="co-item-name">${escapeAttr(item.complaint)}</span>` +
                `</div>`
            ).join('')
        ).join('');
        positionCoDropdown();
        coDropdown.classList.add('show');

        // Favourite toggle click
        coDropdown.querySelectorAll('.co-fav-btn').forEach(btn => {
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault(); e.stopPropagation();
                const id = this.dataset.id;
                fetch(`${coFavBase}/${id}/toggle-favourite`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': coCsrf, 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    const c = coComplaints.find(x => String(x.id) === String(id));
                    if (c) { c.is_favourite = data.is_favourite; }
                    renderCoDropdown();
                });
            });
        });
    }

    // Top search input
    coSearch?.addEventListener('focus', function () { activeCoInput = this; renderCoDropdown(); });
    coSearch?.addEventListener('input', function () { activeCoInput = this; renderCoDropdown(); });
    coSearch?.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); addCoRow(this.value.trim()); }
    });

    // Row complaint inputs (event delegation)
    document.getElementById('coBody')?.addEventListener('focusin', function (e) {
        const input = e.target.closest('.row-co-search');
        if (!input) { return; }
        activeCoInput = input;
        renderCoDropdown(''); // show all on focus, not filtered by existing value
    });
    document.getElementById('coBody')?.addEventListener('input', function (e) {
        const input = e.target.closest('.row-co-search');
        if (!input) { return; }
        activeCoInput = input;
        renderCoDropdown(); // filter by typed value
    });

    window.addEventListener('scroll', positionCoDropdown, true);
    window.addEventListener('resize', positionCoDropdown);

    coDropdown?.addEventListener('mousedown', function (e) {
        const item = e.target.closest('.co-item');
        if (!item || e.target.closest('.co-fav-btn') || !activeCoInput) { return; }
        e.preventDefault();
        const name = item.dataset.name || '';
        activeCoInput.value = name;
        coDropdown.classList.remove('show');
        // If it's the top search, also trigger addCoRow
        if (activeCoInput === coSearch) {
            addCoRow(name);
        }
        activeCoInput.dispatchEvent(new Event('input', { bubbles: true }));
    });

    document.addEventListener('mousedown', function (e) {
        if (!e.target.closest('.co-select-wrap') && !e.target.closest('#coDropdown') && !e.target.closest('.row-co-search')) {
            coDropdown?.classList.remove('show');
            activeCoInput = null;
        }
    });

    let coRowIndex = document.querySelectorAll('#coBody .co-row').length;
    function addCoRow(complaintVal) {
        const complaint = (complaintVal || (activeCoInput === coSearch ? coSearch?.value : '') || '').trim();
        if (!complaint) {
            activeCoInput = coSearch;
            coSearch?.focus();
            renderCoDropdown();
            return;
        }
        const i = coRowIndex++;
        const tr = document.createElement('tr');
        tr.className = 'co-row';
        const sinceOpts = ['-',...Array.from({length:30},(_,n)=>n+1),45,60,90]
            .map((n,idx) => `<option value="${idx===0?'':n}">${n}</option>`).join('');
        const unitOpts = ['Days','Weeks','Months','Years','Longtime']
            .map(u => `<option value="${u}">${u}</option>`).join('');
        const eyeOpts = [['','-'],['RE','Right'],['LE','Left'],['Both','Both'],['OU','OU']]
            .map(([v,l]) => `<option value="${v}">${l}</option>`).join('');
        tr.innerHTML = `
            <td><input type="text" name="exam_data[co_rows][${i}][complaint]" value="${escapeAttr(complaint)}" class="form-control form-control-sm row-co-search" placeholder="Complaint" autocomplete="off"></td>
            <td><select name="exam_data[co_rows][${i}][since]" class="form-select form-select-sm">${sinceOpts}</select></td>
            <td><select name="exam_data[co_rows][${i}][unit]" class="form-select form-select-sm">${unitOpts}</select></td>
            <td><select name="exam_data[co_rows][${i}][eye]" class="form-select form-select-sm">${eyeOpts}</select></td>
            <td><input type="text" name="exam_data[co_rows][${i}][comment]" class="form-control form-control-sm" placeholder="Comment"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger px-2" onclick="this.closest('tr').remove(); checkProgress(); updateLivePreview();">&times;</button></td>`;
        document.getElementById('coBody').appendChild(tr);
        const msg = document.getElementById('coEmptyMsg');
        if (msg) { msg.style.display = 'none'; }
        if (coSearch) { coSearch.value = ''; }
        coDropdown?.classList.remove('show');
        activeCoInput = null;
        checkProgress();
        updateLivePreview();
    }
    document.getElementById('addCoRow')?.addEventListener('click', function () {
        addCoRow(coSearch ? coSearch.value.trim() : '');
    });

    // ── KCO dropdown ──────────────────────────────────────────────────────────
    (function () {
        const kcoSearch   = document.getElementById('kcoSearch');
        const kcoDropdown = document.getElementById('kcoDropdown');
        if (!kcoSearch || !kcoDropdown) { return; }

        const kcoItems   = @json($masters['kcos']); // {id, kco, is_favourite}
        const kcoFavBase = '{{ url($slug."/masters/detail/kcos") }}';
        let activeKcoInput = null;
        let kcoRowIndex    = document.querySelectorAll('#kcoBody .kco-row').length;

        function sortedKcos() {
            return [...kcoItems].sort((a, b) => {
                if (Boolean(a.is_favourite) !== Boolean(b.is_favourite)) { return a.is_favourite ? -1 : 1; }
                return String(a.kco).localeCompare(String(b.kco));
            });
        }

        function positionKcoDropdown() {
            if (!activeKcoInput) { return; }
            const rect = activeKcoInput.getBoundingClientRect();
            kcoDropdown.style.top   = (rect.bottom + 4) + 'px';
            kcoDropdown.style.left  = rect.left + 'px';
            kcoDropdown.style.width = Math.max(rect.width, 300) + 'px';
        }

        function renderKcoDropdown(queryOverride) {
            const query = queryOverride !== undefined ? queryOverride : (activeKcoInput?.value || '').trim().toLowerCase();
            const items = sortedKcos().filter(i => String(i.kco).toLowerCase().includes(query));

            if (!items.length) {
                kcoDropdown.innerHTML = '<div class="co-empty">No conditions found</div>';
                positionKcoDropdown();
                kcoDropdown.classList.add('show');
                return;
            }

            const groups = [
                { label: '⭐ Favourites',    rows: items.filter(i => i.is_favourite)  },
                { label: 'All Conditions',   rows: items.filter(i => !i.is_favourite) },
            ].filter(g => g.rows.length);

            kcoDropdown.innerHTML = groups.map(g =>
                `<div class="co-section-lbl">${g.label}</div>` +
                g.rows.map(item =>
                    `<div class="co-item" data-name="${escapeAttr(item.kco)}">` +
                    `<button type="button" class="co-fav-btn ${item.is_favourite ? 'fav-on' : ''}" data-id="${item.id}" title="${item.is_favourite ? 'Remove' : 'Add'} favourite">${item.is_favourite ? '★' : '☆'}</button>` +
                    `<span class="co-item-name">${escapeAttr(item.kco)}</span>` +
                    `</div>`
                ).join('')
            ).join('');
            positionKcoDropdown();
            kcoDropdown.classList.add('show');

            kcoDropdown.querySelectorAll('.co-fav-btn').forEach(btn => {
                btn.addEventListener('mousedown', function (e) {
                    e.preventDefault(); e.stopPropagation();
                    const id = this.dataset.id;
                    fetch(`${kcoFavBase}/${id}/toggle-favourite`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': coCsrf, 'Accept': 'application/json' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        const c = kcoItems.find(x => String(x.id) === String(id));
                        if (c) { c.is_favourite = data.is_favourite; }
                        renderKcoDropdown();
                    });
                });
            });
        }

        function addKcoRow(val) {
            const condition = (val || (activeKcoInput === kcoSearch ? kcoSearch.value : '') || '').trim();
            if (!condition) { activeKcoInput = kcoSearch; kcoSearch.focus(); renderKcoDropdown(''); return; }
            const i  = kcoRowIndex++;
            const tr = document.createElement('tr');
            tr.className = 'kco-row';
            const sinceOpts = ['-',...Array.from({length:30},(_,n)=>n+1),45,60,90]
                .map((n,idx) => `<option value="${idx===0?'':n}">${n}</option>`).join('');
            const unitOpts = ['Days','Weeks','Months','Years','Longtime']
                .map(u => `<option value="${u}"${u==='Years'?' selected':''}>${u}</option>`).join('');
            tr.innerHTML = `
                <td><input type="text" name="exam_data[kco_rows][${i}][condition]" value="${escapeAttr(condition)}" class="form-control form-control-sm row-kco-search" placeholder="Condition" autocomplete="off"></td>
                <td><select name="exam_data[kco_rows][${i}][since]" class="form-select form-select-sm">${sinceOpts}</select></td>
                <td><select name="exam_data[kco_rows][${i}][unit]" class="form-select form-select-sm">${unitOpts}</select></td>
                <td><input type="text" name="exam_data[kco_rows][${i}][comment]" class="form-control form-control-sm" placeholder="Comment"></td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger px-2" onclick="this.closest('tr').remove(); checkProgress(); updateLivePreview();">&times;</button></td>`;
            document.getElementById('kcoBody').appendChild(tr);
            const msg = document.getElementById('kcoEmptyMsg');
            if (msg) { msg.style.display = 'none'; }
            kcoSearch.value = '';
            kcoDropdown.classList.remove('show');
            activeKcoInput = null;
            checkProgress();
            updateLivePreview();
        }

        // Top search
        kcoSearch.addEventListener('focus', function () { activeKcoInput = this; renderKcoDropdown(''); });
        kcoSearch.addEventListener('input', function () { activeKcoInput = this; renderKcoDropdown(); });
        kcoSearch.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); addKcoRow(this.value.trim()); } });
        document.getElementById('addKcoRow')?.addEventListener('click', () => addKcoRow(kcoSearch.value.trim()));

        // Row inputs delegation
        document.getElementById('kcoBody')?.addEventListener('focusin', function (e) {
            const input = e.target.closest('.row-kco-search');
            if (!input) { return; }
            activeKcoInput = input;
            renderKcoDropdown('');
        });
        document.getElementById('kcoBody')?.addEventListener('input', function (e) {
            const input = e.target.closest('.row-kco-search');
            if (!input) { return; }
            activeKcoInput = input;
            renderKcoDropdown();
        });

        window.addEventListener('scroll', positionKcoDropdown, true);
        window.addEventListener('resize', positionKcoDropdown);

        kcoDropdown.addEventListener('mousedown', function (e) {
            const item = e.target.closest('.co-item');
            if (!item || e.target.closest('.co-fav-btn') || !activeKcoInput) { return; }
            e.preventDefault();
            const name = item.dataset.name || '';
            activeKcoInput.value = name;
            kcoDropdown.classList.remove('show');
            if (activeKcoInput === kcoSearch) { addKcoRow(name); }
            activeKcoInput.dispatchEvent(new Event('input', { bubbles: true }));
        });

        document.addEventListener('mousedown', function (e) {
            if (!e.target.closest('#kcoDropdown') && !e.target.closest('.row-kco-search') && e.target !== kcoSearch) {
                kcoDropdown.classList.remove('show');
                activeKcoInput = null;
            }
        });

        document.getElementById('historyTextarea')?.addEventListener('input', function () {
            checkProgress();
            updateLivePreview();
        });
    })();

    // Advice master select → append to textarea
    document.getElementById('advice_master_select')?.addEventListener('change', function () {
        const val = this.value;
        if (!val) { return; }
        const ta = document.getElementById('advice_textarea');
        ta.value = ta.value ? ta.value + '\n' + val : val;
        this.value = '';
        checkProgress();
    });

    // ── Vision custom dropdowns ───────────────────────────────────────────────
    (function () {
        const visionMasters = {
            vn:   @json($masters['vn']->pluck('value')->values()),
            pnvn: @json($masters['pnvn']->pluck('value')->values()),
            nrvn: @json($masters['nrvn']->pluck('value')->values()),
        };

        const vdd = document.createElement('div');
        vdd.className = 'co-dropdown';
        document.body.appendChild(vdd);

        let activeVInp = null;

        function positionVdd() {
            if (!activeVInp) { return; }
            const rect = activeVInp.getBoundingClientRect();
            vdd.style.top   = (rect.bottom + 4) + 'px';
            vdd.style.left  = rect.left + 'px';
            vdd.style.width = Math.max(rect.width, 180) + 'px';
        }

        function renderVdd(queryOverride) {
            if (!activeVInp) { return; }
            const all   = visionMasters[activeVInp.dataset.master] || [];
            const query = queryOverride !== undefined ? queryOverride : (activeVInp.value || '').trim().toLowerCase();
            const items = query ? all.filter(v => String(v).toLowerCase().includes(query)) : all;

            vdd.innerHTML = items.length
                ? items.map(v => `<div class="co-item" data-val="${escapeAttr(String(v))}"><span class="co-item-name">${escapeAttr(String(v))}</span></div>`).join('')
                : '<div class="co-empty">No options found</div>';

            positionVdd();
            vdd.classList.add('show');

            vdd.querySelectorAll('.co-item').forEach(item => {
                item.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    if (!activeVInp) { return; }
                    const val = this.dataset.val;
                    activeVInp.value = val;
                    const hidden = activeVInp.closest('.vision-select-wrap')?.querySelector('input[type="hidden"]');
                    if (hidden) { hidden.value = val; }
                    vdd.classList.remove('show');
                    activeVInp = null;
                    checkProgress();
                    updateLivePreview();
                });
            });
        }

        document.querySelectorAll('.vision-inp').forEach(inp => {
            inp.addEventListener('focus',  function () { activeVInp = this; renderVdd(''); });
            inp.addEventListener('input',  function () { activeVInp = this; renderVdd(); });
            inp.addEventListener('blur',   function () {
                // Sync hidden if user typed a value directly
                const hidden = this.closest('.vision-select-wrap')?.querySelector('input[type="hidden"]');
                if (hidden) { hidden.value = this.value; }
            });
        });

        window.addEventListener('scroll', positionVdd, true);
        window.addEventListener('resize', positionVdd);

        document.addEventListener('mousedown', function (e) {
            if (!e.target.closest('.vision-select-wrap') && !vdd.contains(e.target)) {
                vdd.classList.remove('show');
                activeVInp = null;
            }
        });
    })();

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
