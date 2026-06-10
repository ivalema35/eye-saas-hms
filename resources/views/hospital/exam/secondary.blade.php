@extends('hospital.layouts.app')
@section('title', 'Secondary Examination - '.$patient->full_name)
@section('page-header', 'Secondary Eye Examination')

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
    <!-- @haspermission('opd.foc.create')
        <button type="button" class="btn secondary-exam-foc-btn btn-sm" data-bs-toggle="modal" data-bs-target="#focRequestExamModalSecondary">
            <i class="fa-solid fa-hand-holding-heart"></i> Request FOC
        </button>
    @endhaspermission -->
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
    /* Vision / PG dropdown */
    .vision-select-wrap, .pg-select-wrap { position: relative; }
    .vision-select-wrap .vision-inp:focus {
        border-color: #1B4F72;
        box-shadow: 0 0 0 3px rgba(27, 79, 114, 0.12);
    }
    .pg-select-wrap .pg-inp,
    .exam-plain-inp {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        color: #1e293b;
        background: #fff;
        transition: border-color .15s, box-shadow .15s;
    }
    .pg-select-wrap .pg-inp { padding-right: 28px; }
    .pg-select-wrap .pg-inp:focus,
    .exam-plain-inp:focus {
        border-color: #1B4F72;
        box-shadow: 0 0 0 3px rgba(27, 79, 114, 0.12);
        outline: none;
    }
    .pg-select-wrap .pg-inp-chevron {
        position: absolute; right: 10px; top: 50%;
        transform: translateY(-50%);
        font-size: 11px; color: #94a3b8; pointer-events: none;
    }
    .pg-table td { vertical-align: middle; }
    .pg-eye-hdr { font-weight: 700; font-size: 13px; letter-spacing: .04em; padding: 8px 12px; }
    .pg-eye-hdr.re { background: #fff0f0; color: #dc2626; }
    .pg-eye-hdr.le { background: #eff6ff; color: #1B4F72; }
    .pg-row-re { background: #fffafa; }
    .pg-row-le { background: #f8faff; }
    /* NCT dropdown */
    .nct-select-wrap { position: relative; }
    .nct-select-wrap .nct-inp {
        padding-right: 28px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        color: #1e293b;
        background: #fff;
        transition: border-color .15s, box-shadow .15s;
    }
    .nct-select-wrap .nct-inp:focus {
        border-color: #1B4F72;
        box-shadow: 0 0 0 3px rgba(27, 79, 114, 0.12);
        outline: none;
    }
    .nct-select-wrap .nct-inp-chevron {
        position: absolute; right: 10px; top: 50%;
        transform: translateY(-50%);
        font-size: 11px; color: #94a3b8; pointer-events: none;
    }
    .nct-dropdown-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 3px;
        padding: 8px;
    }
    .nct-grid-item {
        text-align: center;
        padding: 7px 4px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        color: #1e293b;
        border: 1px solid transparent;
        user-select: none;
        transition: background .12s, border-color .12s, color .12s;
    }
    .nct-grid-item:hover {
        background: rgba(27, 79, 114, 0.08);
        border-color: rgba(27, 79, 114, 0.15);
    }
    .nct-grid-item.selected {
        background: #1B4F72;
        color: #fff;
        font-weight: 700;
        border-color: #1B4F72;
    }
    /* O/E dropdown */
    .oe-table th.oe-eye-col { font-size: 13px; font-weight: 700; letter-spacing: .03em; padding: 8px 12px; }
    .oe-table th.oe-eye-col.re { background: #fff0f0; color: #dc2626; }
    .oe-table th.oe-eye-col.le { background: #eff6ff; color: #1B4F72; }
    .oe-table td.oe-cell-re { background: #fffafa; }
    .oe-table td.oe-cell-le { background: #f8faff; }
    .oe-table td.oe-label-cell { background: #f8fafc; vertical-align: middle; }
    .oe-select-wrap { position: relative; }
    .oe-select-wrap .oe-inp {
        padding-right: 28px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        color: #1e293b;
        background: #fff;
        transition: border-color .15s, box-shadow .15s;
    }
    .oe-select-wrap .oe-inp:focus {
        border-color: #1B4F72;
        box-shadow: 0 0 0 3px rgba(27, 79, 114, 0.12);
        outline: none;
    }
    .oe-select-wrap .oe-inp-chevron {
        position: absolute; right: 10px; top: 50%;
        transform: translateY(-50%);
        font-size: 11px; color: #94a3b8; pointer-events: none;
    }
    .pseudo-lens-summary {
        font-size: 10px; color: #1B4F72; background: rgba(27, 79, 114, 0.06);
        border-radius: 5px; padding: 3px 8px; line-height: 1.4;
    }
    .pseudo-type-btn.active { background: #1B4F72 !important; color: #fff !important; border-color: #1B4F72 !important; }
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
    .co-item:hover { background: rgba(27, 79, 114, 0.07); }
    .co-item.selected { background: rgba(27, 79, 114, 0.1); font-weight: 600; }
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
        /* 1. Margin 0 strictly removes browser URLs and Dates */
        @page {
            size: A4 portrait;
            margin: 0mm !important;
        }

        body {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            background-color: white !important;
            font-size: 11px !important; /* Reduce base font to fit more */
            margin: 10mm !important; /* Re-add margin to body so it doesn't touch paper edge */
        }

        body * {
            visibility: hidden;
        }

        /* 2. Show only printable area */
        .d-print-block, .d-print-block *,
        .clinical-grid-container, .clinical-grid-container * {
            visibility: visible;
        }

        /* 3. Reset positioning */
        .d-print-block {
            position: relative !important;
            left: auto !important;
            top: auto !important;
            width: 100%;
            margin-bottom: 10px !important;
        }

        .clinical-grid-container {
            position: relative !important;
            left: auto !important;
            top: auto !important;
            width: 100%;
        }

        /* 4. Enforce 2-Column layout and prevent wrapping */
        .clinical-grid-container .row {
            display: flex !important;
            flex-wrap: nowrap !important;
            width: 100% !important;
        }

        .clinical-grid-container .col-md-6,
        .clinical-grid-container .col-6 {
            width: 50% !important;
            flex: 0 0 50% !important;
            max-width: 50% !important;
            padding: 0 4px !important; /* Tighten column gaps */
        }

        /* 5. Fix Table Squishing & Number Wrapping */
        .clinical-grid-container table {
            width: 100% !important;
            table-layout: auto !important;
            margin-bottom: 5px !important;
        }

        .clinical-grid-container .table-sm td,
        .clinical-grid-container .table-sm th {
            padding: 0.15rem !important; /* Extreme compact padding */
            font-size: 11px !important;
            line-height: 1.1 !important;
        }

        /* CRITICAL: Stop ST and PG table numbers from breaking */
        .clinical-grid-container table td,
        .clinical-grid-container table th {
            white-space: nowrap !important;
        }

        /* Allow advice text to wrap normally */
        #advice_print_row td {
            white-space: normal !important;
        }

        /* 6. Prevent Page Breaks inside boxes */
        .card, table, tr, th, td, .canvas-box {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
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
<div class="modal fade" id="focRequestExamModalSecondary" tabindex="-1" aria-hidden="true">
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
    $ed = old('exam_data', $ed ?? ($exam?->exam_data ?? []));
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

    $initialMedicines = collect(old('medicines', ($initialMedicines ?? collect())->toArray()));
@endphp

@php
    $isDoctor = auth('hospital_user')->user()?->role?->slug === 'doctor';
@endphp

<div class="accordion mb-3" id="primaryExamReferenceAccordion">
    <div class="accordion-item border">
        <h2 class="accordion-header" id="primaryExamReferenceHeading">
            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#primaryExamReferenceBody" aria-expanded="false" aria-controls="primaryExamReferenceBody">
                Primary Exam Findings (Reference)
            </button>
        </h2>
        <div id="primaryExamReferenceBody" class="accordion-collapse collapse" aria-labelledby="primaryExamReferenceHeading" data-bs-parent="#primaryExamReferenceAccordion">
            <div class="accordion-body bg-light">
                @if($primaryExam)
                    @php
                        $ped = $primaryExam->exam_data ?? [];
                        $pVision = $ped['vision'] ?? [];
                        $pSt = $ped['st'] ?? [];
                        $pOe = $ped['oe'] ?? [];
                        $pFundus = $ped['fundus'] ?? [];
                        $pComplaintNames = collect($masters['complaints'])->whereIn('id', $ped['complaints'] ?? [])->pluck('complaint')->implode(', ');
                        $pKcoNames = collect($masters['kcos'])->whereIn('id', $ped['kcos'] ?? [])->pluck('kco')->implode(', ');
                        $pDxNames = collect($masters['diagnoses'])->whereIn('id', $ped['diagnoses'] ?? [])->pluck('diagnosis')->implode(', ');
                        $pAdviceNames = collect($masters['advices'])->whereIn('id', $ped['advices'] ?? [])->pluck('value')->implode(', ');
                    @endphp

                    <div class="small text-muted mb-2">
                        Captured on {{ $primaryExam->examined_at?->format('d M Y, h:i A') ?? '---' }}
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <tbody>
                                <tr>
                                    <th class="table-light" style="width:220px">Chief Complaints</th>
                                    <td>{{ $pComplaintNames ?: '---' }}</td>
                                </tr>
                                <tr>
                                    <th class="table-light">Duration</th>
                                    <td>{{ $ped['complaint_duration'] ?? '---' }}</td>
                                </tr>
                                <tr>
                                    <th class="table-light">KCO</th>
                                    <td>{{ $pKcoNames ?: '---' }}</td>
                                </tr>
                                <tr>
                                    <th class="table-light">Diagnosis</th>
                                    <td>{{ $pDxNames ?: '---' }}</td>
                                </tr>
                                <tr>
                                    <th class="table-light">Advice</th>
                                    <td>{{ $pAdviceNames ?: '---' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="fw-semibold mb-1">Vision</div>
                            <table class="table table-sm table-bordered text-center mb-0">
                                <thead class="table-light"><tr><th>Eye</th><th>Distance (VN)</th><th>Pinhole (PH)</th><th>Near (NV)</th></tr></thead>
                                <tbody>
                                    <tr><th>RE</th><td>{{ $pVision['vn_re'] ?? '---' }}</td><td>{{ $pVision['pnvn_re'] ?? '---' }}</td><td>{{ $pVision['nrvn_re'] ?? '---' }}</td></tr>
                                    <tr><th>LE</th><td>{{ $pVision['vn_le'] ?? '---' }}</td><td>{{ $pVision['pnvn_le'] ?? '---' }}</td><td>{{ $pVision['nrvn_le'] ?? '---' }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-lg-6">
                            <div class="fw-semibold mb-1">Final Glass (ST)</div>
                            <table class="table table-sm table-bordered text-center mb-0">
                                <thead class="table-light"><tr><th>Eye</th><th>SPH</th><th>CYL</th><th>AXIS</th><th>Near SPH</th><th>Near CYL</th><th>Near AXIS</th></tr></thead>
                                <tbody>
                                    <tr><th>RE</th><td>{{ $pSt['re']['ds'] ?? '---' }}</td><td>{{ $pSt['re']['dc'] ?? '---' }}</td><td>{{ $pSt['re']['ax'] ?? '---' }}</td><td>{{ $pSt['re']['ns'] ?? '---' }}</td><td>{{ $pSt['re']['nc'] ?? '---' }}</td><td>{{ $pSt['re']['na'] ?? '---' }}</td></tr>
                                    <tr><th>LE</th><td>{{ $pSt['le']['ds'] ?? '---' }}</td><td>{{ $pSt['le']['dc'] ?? '---' }}</td><td>{{ $pSt['le']['ax'] ?? '---' }}</td><td>{{ $pSt['le']['ns'] ?? '---' }}</td><td>{{ $pSt['le']['nc'] ?? '---' }}</td><td>{{ $pSt['le']['na'] ?? '---' }}</td></tr>
                                </tbody>
                            </table>
                            <div class="small mt-1">
                                <strong>ADD:</strong> {{ $pSt['add'] ?? '---' }}
                                <span class="ms-2"><strong>Lens Type:</strong> {{ $pSt['lens_type'] ?? '---' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-lg-4">
                            <div class="fw-semibold mb-1">NCT / IOP</div>
                            <div class="small border rounded bg-white p-2">
                                RE: {{ $ped['nct']['iop_re'] ?? '---' }} mmHg<br>
                                LE: {{ $ped['nct']['iop_le'] ?? '---' }} mmHg
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="fw-semibold mb-1">Ocular Examination (O/E) & Fundus</div>
                            <table class="table table-sm table-bordered text-center mb-0">
                                <thead class="table-light"><tr><th>Part</th><th>RE</th><th>LE</th></tr></thead>
                                <tbody>
                                    @foreach(['sac' => 'SAC', 'lid' => 'Lid', 'conj' => 'Conj', 'cornea' => 'Cornea', 'ac' => 'AC', 'iris' => 'Iris', 'pupil' => 'Pupil', 'lens' => 'Lens', 'em' => 'EM', 'covertest' => 'Cover Test'] as $key => $label)
                                        <tr>
                                            <th>{{ $label }}</th>
                                            <td>{{ $pOe[$key.'_re'] ?? '---' }}</td>
                                            <td>{{ $pOe[$key.'_le'] ?? '---' }}</td>
                                        </tr>
                                    @endforeach
                                    <tr><th>Disc</th><td>{{ $pFundus['disc_re'] ?? '---' }}</td><td>{{ $pFundus['disc_le'] ?? '---' }}</td></tr>
                                    <tr><th>FR</th><td>{{ $pFundus['fr_re'] ?? '---' }}</td><td>{{ $pFundus['fr_le'] ?? '---' }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="alert alert-info mb-0">
                        Primary examination is not available yet for this patient.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<form id="primaryExamForm" method="POST" action="{{ route('hospital.exam.secondary.save', ['slug' => $slug, 'id' => $patient->id]) }}" novalidate>
    @csrf

    @if($isDoctor)
        <style>
            .exam-layout-wrapper {
                display: grid;
                grid-template-columns: 240px 1fr;
                gap: 20px;
                align-items: start;
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
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            }

            .doctor-stepper-sidebar .step-btn {
                width: 100%;
                text-align: left !important;
                padding: 10px 15px !important;
                border-radius: 8px !important;
                font-weight: 600;
            }

            @media (max-width: 991.98px) {
                .exam-layout-wrapper {
                    grid-template-columns: 1fr;
                }

                .doctor-stepper-sidebar {
                    position: static;
                }
            }
        </style>

        <div class="exam-layout-wrapper">
            <div class="doctor-stepper-sidebar d-print-none">
                <h6 class="fw-bold text-muted mb-2 ps-2">EXAM STEPS</h6>

                <div class="step-group-label first">Primary Exam</div>
                <button type="button" class="btn btn-outline-secondary step-btn" id="btn-context" data-bs-toggle="modal" data-bs-target="#modalContext">Context</button>
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

            <div class="main-canvas">
                <div class="card shadow-sm mx-auto" style="width:100%;max-width:1200px;background:white;padding:16px;" id="liveReportCanvas">
                    <div class="clinical-grid-container" style="font-size:13px;">
                        <div class="row g-2">
                            <div class="col-6 col-md-6 d-flex flex-column gap-2">
                                <div class="canvas-box">
                                    <div class="canvas-section-title">History &amp; Vision</div>
                                    <div id="canvas_history"><em class="text-muted" style="font-size:11px;">Enter chief complaints to see them here...</em></div>
                                    <div id="canvas_vision" class="mt-1"></div>
                                </div>
                                <div class="canvas-box">
                                    <div class="canvas-section-title">Subjective Testing (ST)</div>
                                    <div id="canvas_st" class="mb-1"></div>
                                    <div class="canvas-section-title mt-1">Diagnosis &amp; Rx</div>
                                    <div id="canvas_rx"></div>
                                </div>
                            </div>

                            <div class="col-6 col-md-6 d-flex flex-column gap-2">
                                <div class="canvas-box">
                                    <div class="canvas-section-title">On Examination (O/E)</div>
                                    <div id="canvas_oe"></div>
                                </div>
                                <div class="canvas-box">
                                    <div class="canvas-section-title">Fundus</div>
                                    <div id="canvas_fundus"></div>
                                </div>
                            </div>
                        </div>

                        <div class="canvas-box mt-2">
                            <div id="canvas_advice"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="stepper-wrap d-flex d-print-none justify-content-between align-items-center mb-4 p-3 bg-white rounded shadow-sm border gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="h5 mb-0 text-primary fw-bold">Exam Steps:</div>
                @if(!$secondaryExam)
                    <span class="badge bg-info text-dark ms-3 rounded-pill px-3 py-2" style="font-size: 0.8rem; font-weight: 500;">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-filled from Primary Exam
                    </span>
                @endif
            </div>
            <div class="d-flex align-items-center gap-1 flex-wrap">
                <span class="step-group-tag">Primary</span>
                <button type="button" class="btn btn-outline-secondary step-btn btn-sm" id="btn-context"   data-bs-toggle="modal" data-bs-target="#modalContext">Context</button>
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

        <div class="card shadow-sm mx-auto" style="width:100%;max-width:1200px;background:white;padding:16px;" id="liveReportCanvas">
            <div class="clinical-grid-container" style="font-size:13px;">
                <div class="row g-2">
                    <div class="col-6 col-md-6 d-flex flex-column gap-2">
                        <div class="canvas-box">
                            <div class="canvas-section-title">History &amp; Vision</div>
                            <div id="canvas_history"><em class="text-muted" style="font-size:11px;">Enter chief complaints to see them here...</em></div>
                            <div id="canvas_vision" class="mt-1"></div>
                        </div>
                        <div class="canvas-box">
                            <div class="canvas-section-title">Subjective Testing (ST)</div>
                            <div id="canvas_st" class="mb-1"></div>
                            <div class="canvas-section-title mt-1">Diagnosis &amp; Rx</div>
                            <div id="canvas_rx"></div>
                        </div>
                    </div>

                    <div class="col-6 col-md-6 d-flex flex-column gap-2">
                        <div class="canvas-box">
                            <div class="canvas-section-title">On Examination (O/E)</div>
                            <div id="canvas_oe"></div>
                        </div>
                        <div class="canvas-box">
                            <div class="canvas-section-title">Fundus</div>
                            <div id="canvas_fundus"></div>
                        </div>
                    </div>
                </div>

                <div class="canvas-box mt-2">
                    <div id="canvas_advice"></div>
                </div>
            </div>
        </div>
    @endif

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
                                    <option value="{{ $doctor->id }}" @selected(old('doctor_id', $secondaryExam?->doctor_id ?? $currentDoctorId ?? auth('hospital_user')->id()) == $doctor->id)>
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
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger px-2" onclick="this.closest('tr').remove(); checkProgress();">&times;</button></td>
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
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="bi bi-eyeglasses me-2" style="color:#1B4F72;"></i>Present Glasses (PG)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @php
                        $pgFields = [
                            'ds'      => ['abbr' => 'SPH',  'full' => 'Sphere',      'master' => 'sph_cyl'],
                            'dc'      => ['abbr' => 'CYL',  'full' => 'Cylinder',    'master' => 'sph_cyl'],
                            'ax'      => ['abbr' => 'AXIS', 'full' => 'Axis',        'master' => 'axis'],
                            'vn'      => ['abbr' => 'VN',   'full' => 'Vision',      'master' => 'vn'],
                            'add'     => ['abbr' => 'ADD',  'full' => 'Addition',    'master' => 'sph_cyl'],
                            'near_vn' => ['abbr' => 'NV',   'full' => 'Near Vision', 'master' => 'nrvn'],
                        ];
                        $pgMasterOpts = [
                            'sph_cyl' => collect($masters['sph_cyl'])->map(fn ($o) => ltrim(trim($o->value), '+-'))->reject(fn ($v) => $v === '')->unique()->flatMap(function ($cv) {
                                if (! in_array((string) $cv, ['0', '0.00', 'Plano', 'PL'])) {
                                    return ['+'.$cv, '-'.$cv];
                                }
                                return [(string) $cv];
                            })->values()->all(),
                            'axis' => collect($masters['axis'])->map(fn ($o) => ltrim(trim($o->value), '+-'))->reject(fn ($v) => $v === '')->unique()->values()->all(),
                            'vn'   => collect($masters['vn'])->pluck('value')->filter()->values()->all(),
                            'nrvn' => collect($masters['nrvn'])->pluck('value')->filter()->values()->all(),
                        ];
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0 pg-table">
                            <tbody>
                                @foreach([
                                    're' => ['label' => 'Right Eye (RE)', 'cls' => 're', 'row' => 'pg-row-re'],
                                    'le' => ['label' => 'Left Eye (LE)',  'cls' => 'le', 'row' => 'pg-row-le'],
                                ] as $eye => $em)
                                <tr>
                                    <td colspan="2" class="pg-eye-hdr {{ $em['cls'] }}">
                                        <i class="bi bi-eye-fill me-1"></i>{{ $em['label'] }}
                                    </td>
                                </tr>
                                @foreach($pgFields as $key => $meta)
                                @php $sv = $pg[$eye][$key] ?? ''; @endphp
                                <tr class="{{ $em['row'] }}">
                                    <td style="width:160px;">
                                        <span class="fw-bold" style="font-size:13px;color:#1e293b;">{{ $meta['abbr'] }}</span>
                                        <div style="font-size:11px;color:#94a3b8;">{{ $meta['full'] }}</div>
                                    </td>
                                    <td>
                                        <div class="pg-select-wrap">
                                            <input type="text" class="form-control form-control-sm pg-inp"
                                                placeholder="—" autocomplete="off"
                                                data-master="{{ $meta['master'] }}"
                                                value="{{ $sv }}">
                                            <input type="hidden" name="exam_data[pg][{{ $eye }}][{{ $key }}]" value="{{ $sv }}">
                                            <i class="bi bi-chevron-down pg-inp-chevron"></i>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
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

    {{-- MODAL: ST --}}
    <div class="modal fade" id="modalST" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="bi bi-binoculars me-2" style="color:#1B4F72;"></i>Subjective Trial (ST)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @php
                        $stDistFields = [
                            'ds' => ['abbr' => 'SPH',  'full' => 'Sphere',   'master' => 'sph_cyl'],
                            'dc' => ['abbr' => 'CYL',  'full' => 'Cylinder', 'master' => 'sph_cyl'],
                            'ax' => ['abbr' => 'AXIS', 'full' => 'Axis',     'master' => 'axis'],
                        ];
                        $stNearFields = [
                            'ns' => ['abbr' => 'NSPH',  'full' => 'Near Sphere',   'master' => 'sph_cyl'],
                            'nc' => ['abbr' => 'NCYL',  'full' => 'Near Cylinder', 'master' => 'sph_cyl'],
                            'na' => ['abbr' => 'NAXIS', 'full' => 'Near Axis',     'master' => 'axis'],
                        ];
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0 pg-table">
                            <tbody>
                                @foreach([
                                    're' => ['label' => 'Right Eye (RE)', 'cls' => 're', 'row' => 'pg-row-re'],
                                    'le' => ['label' => 'Left Eye (LE)',  'cls' => 'le', 'row' => 'pg-row-le'],
                                ] as $eye => $em)
                                <tr>
                                    <td colspan="2" class="pg-eye-hdr {{ $em['cls'] }}">
                                        <i class="bi bi-eye-fill me-1"></i>{{ $em['label'] }}
                                    </td>
                                </tr>
                                <tr class="{{ $em['row'] }}">
                                    <td colspan="2" style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;padding:5px 12px;background:rgba(0,0,0,.02);">Distance</td>
                                </tr>
                                @foreach($stDistFields as $key => $meta)
                                @php $sv = old('exam_data.st.'.$eye.'.'.$key, $st[$eye][$key] ?? ''); @endphp
                                <tr class="{{ $em['row'] }}">
                                    <td style="width:160px;">
                                        <span class="fw-bold" style="font-size:13px;color:#1e293b;">{{ $meta['abbr'] }}</span>
                                        <div style="font-size:11px;color:#94a3b8;">{{ $meta['full'] }}</div>
                                    </td>
                                    <td>
                                        <div class="pg-select-wrap">
                                            <input type="text" class="form-control form-control-sm pg-inp"
                                                placeholder="—" autocomplete="off"
                                                data-master="{{ $meta['master'] }}"
                                                value="{{ $sv }}">
                                            <input type="hidden" name="exam_data[st][{{ $eye }}][{{ $key }}]" value="{{ $sv }}">
                                            <i class="bi bi-chevron-down pg-inp-chevron"></i>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                                <tr class="{{ $em['row'] }}">
                                    <td colspan="2" style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;padding:5px 12px;background:rgba(0,0,0,.02);">Near Vision</td>
                                </tr>
                                @foreach($stNearFields as $key => $meta)
                                @php $sv = old('exam_data.st.'.$eye.'.'.$key, $st[$eye][$key] ?? ''); @endphp
                                <tr class="{{ $em['row'] }}">
                                    <td style="width:160px;">
                                        <span class="fw-bold" style="font-size:13px;color:#1e293b;">{{ $meta['abbr'] }}</span>
                                        <div style="font-size:11px;color:#94a3b8;">{{ $meta['full'] }}</div>
                                    </td>
                                    <td>
                                        <div class="pg-select-wrap">
                                            <input type="text" class="form-control form-control-sm pg-inp"
                                                placeholder="—" autocomplete="off"
                                                data-master="{{ $meta['master'] }}"
                                                value="{{ $sv }}">
                                            <input type="hidden" name="exam_data[st][{{ $eye }}][{{ $key }}]" value="{{ $sv }}">
                                            <i class="bi bi-chevron-down pg-inp-chevron"></i>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                                @endforeach

                                <tr>
                                    <td colspan="2" class="pg-eye-hdr le" style="background:#f1f5f9;color:#475569;">
                                        <i class="bi bi-sliders me-1"></i>Common
                                    </td>
                                </tr>
                                @php $stAdd = old('exam_data.st.add', $st['add'] ?? ''); @endphp
                                <tr class="pg-row-le">
                                    <td style="width:160px;">
                                        <span class="fw-bold" style="font-size:13px;color:#1e293b;">ADD</span>
                                        <div style="font-size:11px;color:#94a3b8;">Addition</div>
                                    </td>
                                    <td>
                                        <div class="pg-select-wrap">
                                            <input type="text" class="form-control form-control-sm pg-inp"
                                                placeholder="—" autocomplete="off"
                                                data-master="sph_cyl"
                                                value="{{ $stAdd }}">
                                            <input type="hidden" name="exam_data[st][add]" value="{{ $stAdd }}">
                                            <i class="bi bi-chevron-down pg-inp-chevron"></i>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="pg-row-le">
                                    <td style="width:160px;">
                                        <span class="fw-bold" style="font-size:13px;color:#1e293b;">Lens</span>
                                        <div style="font-size:11px;color:#94a3b8;">Lens Type</div>
                                    </td>
                                    <td>
                                        <input type="text" name="exam_data[st][lens_type]"
                                            value="{{ old('exam_data.st.lens_type', $st['lens_type'] ?? '') }}"
                                            class="form-control form-control-sm exam-plain-inp"
                                            placeholder="SV / Bifocal / Progressive" autocomplete="off">
                                    </td>
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

    {{-- MODAL: NCT --}}
    <div class="modal fade" id="modalNCT" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="bi bi-speedometer2 me-2" style="color:#1B4F72;"></i>NCT (Intraocular Pressure — mmHg)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0 pg-table">
                            <tbody>
                                @foreach([
                                    're' => ['label' => 'Right Eye (RE)', 'cls' => 're', 'row' => 'pg-row-re'],
                                    'le' => ['label' => 'Left Eye (LE)',  'cls' => 'le', 'row' => 'pg-row-le'],
                                ] as $eye => $em)
                                @php $sv = old('exam_data.nct.iop_'.$eye, $nct['iop_'.$eye] ?? ''); @endphp
                                <tr>
                                    <td colspan="2" class="pg-eye-hdr {{ $em['cls'] }}">
                                        <i class="bi bi-eye-fill me-1"></i>{{ $em['label'] }}
                                    </td>
                                </tr>
                                <tr class="{{ $em['row'] }}">
                                    <td style="width:160px;">
                                        <span class="fw-bold" style="font-size:13px;color:#1e293b;">IOP</span>
                                        <div style="font-size:11px;color:#94a3b8;">mmHg</div>
                                    </td>
                                    <td>
                                        <div class="nct-select-wrap">
                                            <input type="text" class="form-control form-control-sm nct-inp"
                                                placeholder="—" autocomplete="off"
                                                value="{{ $sv }}">
                                            <input type="hidden" name="exam_data[nct][iop_{{ $eye }}]" value="{{ $sv }}">
                                            <i class="bi bi-chevron-down nct-inp-chevron"></i>
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

    {{-- MODAL: O/E --}}
    <div class="modal fade" id="modalOE" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="bi bi-clipboard2-pulse me-2" style="color:#1B4F72;"></i>Ocular Examination (O/E)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @php
                        $oeFieldMeta = [
                            'sac'       => ['label' => 'SAC',       'full' => 'Sac',       'master' => 'sac',         'fav' => 'sac'],
                            'lid'       => ['label' => 'LID',       'full' => 'Lid',       'master' => 'lid',         'fav' => 'lid'],
                            'conj'      => ['label' => 'CONJ',      'full' => 'Conjunctiva','master' => 'conj',        'fav' => 'conj'],
                            'cornea'    => ['label' => 'CORNEA',    'full' => 'Cornea',    'master' => 'cornea',      'fav' => 'cornea'],
                            'ac'        => ['label' => 'AC',        'full' => 'Anterior Chamber', 'master' => 'ac',   'fav' => 'ac'],
                            'iris'      => ['label' => 'IRIS',      'full' => 'Iris',      'master' => 'iris',        'fav' => 'iris'],
                            'pupil'     => ['label' => 'PUPIL',     'full' => 'Pupil',     'master' => 'pupil',       'fav' => 'pupil'],
                            'lens'      => ['label' => 'LENS',      'full' => 'Lens',      'master' => 'lens_master', 'fav' => 'lens'],
                            'em'        => ['label' => 'EM',        'full' => 'Extraocular', 'master' => 'em',        'fav' => 'em'],
                            'covertest' => ['label' => 'COVERTEST', 'full' => 'Cover Test','master' => 'covertest',   'fav' => 'covertest'],
                        ];
                        $oeMasterData = [];
                        foreach ($oeFieldMeta as $meta) {
                            if (! isset($oeMasterData[$meta['master']])) {
                                $oeMasterData[$meta['master']] = collect($masters[$meta['master']])->map(fn ($o) => [
                                    'id'           => $o->id,
                                    'value'        => $o->value,
                                    'is_favourite' => (bool) ($o->is_favourite ?? false),
                                ])->values()->all();
                            }
                        }
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0 oe-table">
                            <thead>
                                <tr>
                                    <th style="width:130px;background:#f8fafc;"></th>
                                    <th class="oe-eye-col re"><i class="bi bi-eye-fill me-1"></i>Right Eye (RE)</th>
                                    <th class="oe-eye-col le"><i class="bi bi-eye-fill me-1"></i>Left Eye (LE)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($oeFieldMeta as $key => $meta)
                                <tr>
                                    <td class="oe-label-cell">
                                        <span class="fw-bold" style="font-size:13px;color:#1e293b;">{{ $meta['label'] }}</span>
                                        <div style="font-size:11px;color:#94a3b8;">{{ $meta['full'] }}</div>
                                    </td>
                                    @foreach(['re' => 'oe-cell-re', 'le' => 'oe-cell-le'] as $eye => $cellCls)
                                    @php $sv = old('exam_data.oe.'.$key.'_'.$eye, $oe[$key.'_'.$eye] ?? ''); @endphp
                                    <td class="{{ $cellCls }}">
                                        <div class="oe-select-wrap">
                                            <input type="text" class="form-control form-control-sm oe-inp"
                                                placeholder="—" autocomplete="off"
                                                data-oe-key="{{ $key }}"
                                                data-master="{{ $meta['master'] }}"
                                                data-fav="{{ $meta['fav'] }}"
                                                value="{{ $sv }}">
                                            <input type="hidden" name="exam_data[oe][{{ $key }}_{{ $eye }}]" value="{{ $sv }}">
                                            <i class="bi bi-chevron-down oe-inp-chevron"></i>
                                        </div>
                                        @if($key === 'lens')
                                        @php $pseudo = old('exam_data.oe.pseudophakia_'.$eye, $oe['pseudophakia_'.$eye] ?? []); @endphp
                                        <input type="hidden" name="exam_data[oe][pseudophakia_{{ $eye }}][operation_type]" value="{{ $pseudo['operation_type'] ?? '' }}" class="pseudo-op-type" data-eye="{{ $eye }}">
                                        <input type="hidden" name="exam_data[oe][pseudophakia_{{ $eye }}][operation_expense]" value="{{ $pseudo['operation_expense'] ?? '' }}" class="pseudo-op-expense" data-eye="{{ $eye }}">
                                        <input type="hidden" name="exam_data[oe][pseudophakia_{{ $eye }}][hospital_name]" value="{{ $pseudo['hospital_name'] ?? '' }}" class="pseudo-hospital" data-eye="{{ $eye }}">
                                        <div class="pseudo-lens-summary mt-1" data-eye="{{ $eye }}" style="display:none;"></div>
                                        @endif
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                                <tr>
                                    <td class="oe-label-cell">
                                        <span class="fw-bold" style="font-size:13px;color:#1e293b;">OTHER</span>
                                        <div style="font-size:11px;color:#94a3b8;">Other findings</div>
                                    </td>
                                    @foreach(['re' => ['cls' => 'oe-cell-re', 'ph' => 'Right eye'], 'le' => ['cls' => 'oe-cell-le', 'ph' => 'Left eye']] as $eye => $em)
                                    <td class="{{ $em['cls'] }}">
                                        <input type="text" name="exam_data[oe][other_{{ $eye }}]"
                                            value="{{ old('exam_data.oe.other_'.$eye, $oe['other_'.$eye] ?? '') }}"
                                            class="form-control form-control-sm exam-plain-inp"
                                            placeholder="{{ $em['ph'] }}" autocomplete="off">
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

    {{-- MODAL: Pseudophakia details --}}
    <div class="modal fade" id="modalPseudophakia" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2" style="color:#1B4F72;"></i>Pseudophakia — <span id="pseudoModalEyeLabel"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold mb-2">Operation Type</label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary flex-fill pseudo-type-btn" data-val="Block">Block</button>
                            <button type="button" class="btn btn-outline-secondary flex-fill pseudo-type-btn" data-val="Phaco">Phaco</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="pseudoOpExpense">Operation Expense</label>
                        <input type="text" id="pseudoOpExpense" class="form-control exam-plain-inp" placeholder="Amount" autocomplete="off">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold" for="pseudoHospital">Hospital Name</label>
                        <input type="text" id="pseudoHospital" class="form-control exam-plain-inp" placeholder="Hospital name" list="pseudoHospitalList" autocomplete="off">
                        <datalist id="pseudoHospitalList">
                            @foreach($masters['referrers'] ?? [] as $ref)
                            <option value="{{ $ref->name }}">
                            @endforeach
                        </datalist>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="pseudoModalSave">Save</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Fundus --}}
    <div class="modal fade" id="modalFundus" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="bi bi-circle-half me-2" style="color:#7c3aed;"></i>Fundus Examination
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <tbody>
                            {{-- ── Right Eye ── --}}
                            <tr>
                                <td colspan="2" style="background:#fff0f0;color:#dc2626;font-weight:700;font-size:13px;letter-spacing:.04em;padding:9px 14px;border-bottom:2px solid #fca5a5;">
                                    <i class="bi bi-eye-fill me-1"></i> Right Eye <span style="font-weight:400;opacity:.75;">(RE)</span>
                                </td>
                            </tr>
                            @foreach([
                                ['Disc', 'CDR / Appearance', 'disc', 'disc_re'],
                                ['FR',   'Foveal Reflex',    'fr',   'fr_re'],
                            ] as [$lbl, $sub, $type, $field])
                            <tr style="background:#fffafa;">
                                <td style="width:155px;padding:8px 10px 8px 14px;border-left:3px solid #fca5a5;">
                                    <div class="fw-semibold" style="font-size:13px;color:#1e293b;">{{ $lbl }}</div>
                                    <div style="font-size:11px;color:#94a3b8;">{{ $sub }}</div>
                                </td>
                                <td style="padding:6px 10px;">
                                    <div class="fundus-dd-wrap" style="position:relative;">
                                        <input type="text" class="form-control form-control-sm fundus-dd-inp"
                                            placeholder="Search or select..." autocomplete="off"
                                            data-dd-type="{{ $type }}"
                                            value="{{ $fundus[$field] ?? '' }}"
                                            style="padding-right:28px;">
                                        <i class="bi bi-chevron-down" style="position:absolute;right:9px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none;font-size:11px;"></i>
                                        <input type="hidden" name="exam_data[fundus][{{ $field }}]" value="{{ $fundus[$field] ?? '' }}">
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                            <tr style="background:#fffafa;">
                                <td style="width:155px;padding:8px 10px 8px 14px;border-left:3px solid #fca5a5;vertical-align:top;">
                                    <div class="fw-semibold" style="font-size:13px;color:#1e293b;">Comment</div>
                                    <div style="font-size:11px;color:#94a3b8;">RE findings</div>
                                </td>
                                <td style="padding:6px 10px;">
                                    <textarea name="exam_data[fundus][comment_re]" class="form-control form-control-sm"
                                        rows="2" placeholder="Right eye findings / notes..."
                                        style="resize:none;">{{ old('exam_data.fundus.comment_re', $fundus['comment_re'] ?? '') }}</textarea>
                                </td>
                            </tr>

                            {{-- ── Left Eye ── --}}
                            <tr>
                                <td colspan="2" style="background:#eff6ff;color:#1d4ed8;font-weight:700;font-size:13px;letter-spacing:.04em;padding:9px 14px;border-bottom:2px solid #93c5fd;">
                                    <i class="bi bi-eye-fill me-1"></i> Left Eye <span style="font-weight:400;opacity:.75;">(LE)</span>
                                </td>
                            </tr>
                            @foreach([
                                ['Disc', 'CDR / Appearance', 'disc', 'disc_le'],
                                ['FR',   'Foveal Reflex',    'fr',   'fr_le'],
                            ] as [$lbl, $sub, $type, $field])
                            <tr style="background:#f8faff;">
                                <td style="width:155px;padding:8px 10px 8px 14px;border-left:3px solid #93c5fd;">
                                    <div class="fw-semibold" style="font-size:13px;color:#1e293b;">{{ $lbl }}</div>
                                    <div style="font-size:11px;color:#94a3b8;">{{ $sub }}</div>
                                </td>
                                <td style="padding:6px 10px;">
                                    <div class="fundus-dd-wrap" style="position:relative;">
                                        <input type="text" class="form-control form-control-sm fundus-dd-inp"
                                            placeholder="Search or select..." autocomplete="off"
                                            data-dd-type="{{ $type }}"
                                            value="{{ $fundus[$field] ?? '' }}"
                                            style="padding-right:28px;">
                                        <i class="bi bi-chevron-down" style="position:absolute;right:9px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none;font-size:11px;"></i>
                                        <input type="hidden" name="exam_data[fundus][{{ $field }}]" value="{{ $fundus[$field] ?? '' }}">
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                            <tr style="background:#f8faff;">
                                <td style="width:155px;padding:8px 10px 8px 14px;border-left:3px solid #93c5fd;vertical-align:top;">
                                    <div class="fw-semibold" style="font-size:13px;color:#1e293b;">Comment</div>
                                    <div style="font-size:11px;color:#94a3b8;">LE findings</div>
                                </td>
                                <td style="padding:6px 10px;">
                                    <textarea name="exam_data[fundus][comment_le]" class="form-control form-control-sm"
                                        rows="2" placeholder="Left eye findings / notes..."
                                        style="resize:none;">{{ old('exam_data.fundus.comment_le', $fundus['comment_le'] ?? '') }}</textarea>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Diagnosis --}}
    <div class="modal fade" id="modalDiagnosis" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-semibold">
                        <i class="bi bi-clipboard2-pulse me-2 text-danger"></i>Diagnosis
                        <span id="dxSelectedCount" class="badge bg-danger ms-2" style="font-size:11px;display:none;"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="dxSearch" class="form-control border-start-0" placeholder="Search diagnosis..." autocomplete="off">
                        </div>
                    </div>
                    <div class="mb-2" style="font-size:11px;color:#94a3b8;">
                        <i class="bi bi-info-circle me-1"></i>Badges: <span class="badge bg-primary" style="font-size:9px;">G</span> groups &nbsp; <span class="badge bg-success" style="font-size:9px;">A</span> advices — linked to that diagnosis
                    </div>
                    <div class="d-flex flex-wrap gap-2" id="diagnosis-tags">
                        @php
                            $dxGroupCount  = collect($masters['med_groups'])->groupBy('diagnosis_id')->map->count();
                            $dxAdviceCount = collect($masters['advices'])->groupBy('diagnosis_id')->map->count();
                        @endphp
                        @foreach($masters['diagnoses'] as $d)
                            @php $gc = $dxGroupCount[$d->id] ?? 0; $ac = $dxAdviceCount[$d->id] ?? 0; @endphp
                            <div class="dx-tag-wrap">
                                <input class="btn-check" type="checkbox" name="exam_data[diagnoses][]" id="dx_{{ $d->id }}" value="{{ $d->id }}"
                                    {{ in_array($d->id, $ed['diagnoses'] ?? []) ? 'checked' : '' }}>
                                <label class="btn btn-outline-danger rounded-pill btn-sm px-3" for="dx_{{ $d->id }}" style="font-size:12.5px;">
                                    {{ $d->diagnosis }}
                                    @if($gc > 0)<span class="badge bg-primary ms-1" style="font-size:9px;">{{ $gc }}G</span>@endif
                                    @if($ac > 0)<span class="badge bg-success ms-1" style="font-size:9px;">{{ $ac }}A</span>@endif
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <p id="dxNoResult" class="text-muted text-center mt-3" style="display:none;font-size:13px;">No diagnosis found</p>
                </div>
                <div class="modal-footer justify-content-between">
                    <span id="dxFooterHint" class="text-muted" style="font-size:12px;"></span>
                    <button type="button" class="btn btn-danger px-4" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Dilate --}}
    <div class="modal fade" id="modalDilate" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light"><h5 class="modal-title">Dilate Patient?</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
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
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-semibold">
                        <i class="bi bi-capsule me-2 text-primary"></i>Medicines
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- diagnosis-tags div kept here for JS compatibility --}}
                    <div class="d-none" id="diagnosis-tags-rx"></div>

                    {{-- Suggested Groups panel --}}
                    <div id="dxSuggestedGroups" style="display:none;"></div>

                    {{-- Toolbar --}}
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <span class="fw-semibold text-secondary" style="font-size:13px;"><i class="bi bi-list-ul me-1"></i>Prescription List</span>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <div class="input-group input-group-sm" style="width:auto;">
                                <label class="input-group-text bg-white" style="font-size:12px;">Group</label>
                                <select id="rxGroupSelector" class="form-select form-select-sm" style="min-width:180px;">
                                    <option value="">-- Load Group --</option>
                                    @foreach($masters['med_groups'] as $grp)
                                        <option value="{{ $grp->id }}">{{ $grp->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary px-3" onclick="addMedicineRow()">
                                <i class="bi bi-plus-lg me-1"></i>Add Medicine
                            </button>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" id="rxTable"
                               style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                            <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                                <tr>
                                    <th style="font-size:12px;color:#64748b;font-weight:600;">Medicine Name</th>
                                    <th style="width:130px;font-size:12px;color:#64748b;font-weight:600;">Dosage</th>
                                    <th style="width:100px;font-size:12px;color:#64748b;font-weight:600;">Days</th>
                                    <th style="width:80px;font-size:12px;color:#64748b;font-weight:600;">QTY</th>
                                    <th style="width:160px;font-size:12px;color:#64748b;font-weight:600;">Route of Administration</th>
                                    <th style="width:36px;"></th>
                                </tr>
                            </thead>
                            <tbody id="rxBody">
                                @forelse($initialMedicines as $i => $rx)
                                    <tr class="rx-row">
                                        <td>
                                            <input type="hidden" name="medicines[{{ $i }}][medicine_id]" value="{{ data_get($rx, 'medicine_id') }}" class="med-id-input">
                                            <div class="medicine-search-wrap" style="position:relative">
                                                <input type="text" name="medicines[{{ $i }}][name]" class="form-control form-control-sm medicine-search"
                                                       value="{{ data_get($rx, 'name') ?: data_get($rx, 'medicine.brand_name') ?: data_get($rx, 'medicine.name') }}"
                                                       placeholder="Medicine name" autocomplete="off" list="medicine_list">
                                                <div class="medicine-suggest" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #dee2e6;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.12);z-index:100;max-height:180px;overflow-y:auto"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <select name="medicines[{{ $i }}][dosage_id]" class="form-select form-select-sm">
                                                <option value="">-</option>
                                                @foreach($masters['dosages'] as $dos)
                                                    <option value="{{ $dos->id }}" {{ (string) data_get($rx, 'dosage_id') === (string) $dos->id ? 'selected' : '' }}>{{ $dos->dosage }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="medicines[{{ $i }}][duration]" class="form-control" value="{{ data_get($rx, 'duration', '') }}" placeholder="7" min="1">
                                                <span class="input-group-text" style="font-size:11px;">D</span>
                                            </div>
                                        </td>
                                        <td><input type="number" name="medicines[{{ $i }}][quantity]" class="form-control form-control-sm" value="{{ data_get($rx, 'quantity', '') }}" placeholder="1" min="1"></td>
                                        <td>
                                            <select name="medicines[{{ $i }}][route_id]" class="form-select form-select-sm">
                                                <option value="">-</option>
                                                @foreach($masters['routes'] as $rt)
                                                    <option value="{{ $rt->id }}" {{ (string) data_get($rx, 'route_id') === (string) $rt->id ? 'selected' : '' }}>{{ $rt->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger" style="padding:2px 7px;" onclick="this.closest('tr').remove(); updateLivePreview();">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="rxEmpty">
                                        <td colspan="6" class="text-center py-4 text-muted" style="font-size:13px;">
                                            <i class="bi bi-capsule me-1"></i>No medicines added yet
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Advice --}}
    <div class="modal fade" id="modalAdvice" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#f0fdf4 0%,#ecfdf5 100%);border-bottom:1px solid #bbf7d0;">
                    <h5 class="modal-title fw-semibold">
                        <i class="bi bi-chat-square-text me-2 text-success"></i>Clinical Advice &amp; Instructions
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    {{-- Diagnosis-linked suggestions --}}
                    <div id="dxSuggestedAdvices"></div>

                    {{-- Master list as quick-add chips --}}
                    <div class="mb-3">
                        <div class="fw-semibold mb-2" style="font-size:13px;color:#374151;">
                            <i class="bi bi-collection me-1 text-secondary"></i>Quick Add from Master
                            <span class="fw-normal text-muted ms-1" style="font-size:11px;">(click to append)</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($masters['advices'] ?? [] as $adv)
                                @php $advText = $adv->value ?? ''; @endphp
                                @if($advText)
                                <button type="button" class="btn btn-sm btn-outline-secondary advice-quick-btn"
                                        style="font-size:12px;border-radius:20px;"
                                        data-advice="{{ $advText }}">
                                    <i class="bi bi-plus-lg me-1"></i>{{ $advText }}
                                </button>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Textarea --}}
                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="fw-semibold mb-0" style="font-size:13px;color:#374151;">
                                <i class="bi bi-pencil-square me-1 text-success"></i>Advice Text
                            </label>
                            <span id="adviceCharCount" class="text-muted" style="font-size:11px;">0 / 2000</span>
                        </div>
                        <textarea name="advice" id="advice_textarea" class="form-control" rows="7"
                                  placeholder="Enter clinical advice, post-operative care, follow-up instructions, lifestyle recommendations, etc."
                                  maxlength="2000"
                                  style="resize:vertical;font-size:13px;">{{ old('advice', $secondaryExam?->advice ?? '') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between" style="background:#f9fafb;">
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="document.getElementById('advice_textarea').value=''; document.getElementById('adviceCharCount').textContent='0 / 2000'; if(typeof updateLivePreview==='function') updateLivePreview();">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </button>
                    <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">
                        <i class="bi bi-check-lg me-1"></i>Done
                    </button>
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

    let rxRowIndex = {{ count(old('medicines', ($initialMedicines ?? collect())->toArray())) }};
    const dosagesJson = @json($masters['dosages']->pluck('dosage','id'));
    const durationsJson = @json($masters['durations']->pluck('duration')->values());

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

    function positionFixedDropdown(dropdown, anchorEl, minWidth = 200) {
        if (!dropdown || !anchorEl) { return; }
        const gap = 4;
        const maxH = 300;
        const rect = anchorEl.getBoundingClientRect();
        const vh = window.innerHeight;
        const vw = window.innerWidth;
        const spaceBelow = vh - rect.bottom - gap;
        const spaceAbove = rect.top - gap;
        const width = Math.max(rect.width, minWidth);
        const openUp = spaceBelow < 160 && spaceAbove > spaceBelow;

        let left = rect.left;
        if (left + width > vw - 8) { left = Math.max(8, vw - width - 8); }

        dropdown.style.width = width + 'px';
        dropdown.style.left = left + 'px';
        dropdown.style.transform = '';

        if (openUp) {
            dropdown.style.top = 'auto';
            dropdown.style.bottom = (vh - rect.top + gap) + 'px';
            dropdown.style.maxHeight = Math.max(100, Math.min(maxH, spaceAbove - 8)) + 'px';
        } else {
            dropdown.style.bottom = 'auto';
            dropdown.style.top = (rect.bottom + gap) + 'px';
            dropdown.style.maxHeight = Math.max(100, Math.min(maxH, spaceBelow - 8)) + 'px';
        }
    }

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
        positionFixedDropdown(coDropdown, activeCoInput, 300);
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
            positionFixedDropdown(kcoDropdown, activeKcoInput, 300);
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
                { label: '⭐ Favourites',  rows: items.filter(i => i.is_favourite)  },
                { label: 'All Conditions', rows: items.filter(i => !i.is_favourite) },
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

        kcoSearch.addEventListener('focus', function () { activeKcoInput = this; renderKcoDropdown(''); });
        kcoSearch.addEventListener('input', function () { activeKcoInput = this; renderKcoDropdown(); });
        kcoSearch.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); addKcoRow(this.value.trim()); } });
        document.getElementById('addKcoRow')?.addEventListener('click', () => addKcoRow(kcoSearch.value.trim()));

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

    const routesJson = @json($masters['routes']->pluck('name', 'id'));

    window.addMedicineRow = function (medId = '', medName = '', dosageId = '', duration = '', quantity = '', routeId = '') {
        const emptyRow = document.getElementById('rxEmpty');
        if (emptyRow) { emptyRow.remove(); }

        const idx = rxRowIndex++;
        const dosageOptions = Object.entries(dosagesJson)
            .map(([id, label]) => `<option value="${id}" ${String(dosageId) === String(id) ? 'selected' : ''}>${label}</option>`).join('');
        const routeOptions = Object.entries(routesJson)
            .map(([id, name]) => `<option value="${id}" ${String(routeId) === String(id) ? 'selected' : ''}>${name}</option>`).join('');

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
            <td><div class="input-group input-group-sm"><input type="number" name="medicines[${idx}][duration]" class="form-control" value="${duration}" placeholder="7" min="1"><span class="input-group-text" style="font-size:0.75rem">D</span></div></td>
            <td><input type="number" name="medicines[${idx}][quantity]" class="form-control form-control-sm" value="${quantity}" placeholder="1" min="1"></td>
            <td><select name="medicines[${idx}][route_id]" class="form-select form-select-sm"><option value="">-</option>${routeOptions}</select></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" style="padding:2px 7px;" onclick="this.closest('tr').remove(); checkProgress(); updateLivePreview();"><i class="bi bi-x-lg"></i></button></td>
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

        setState('btn-context',   hasValue('select[name="doctor_id"]'));
        setState('btn-clinical',  document.querySelectorAll('#coBody .co-row').length > 0);
        setState('btn-hko',       document.querySelectorAll('#kcoBody .kco-row').length > 0 || (document.getElementById('historyTextarea')?.value || '').trim() !== '');
        setState('btn-vision',    hasValue('input[name="exam_data[vision][vn_re]"]'));
        setState('btn-pg',        hasValue('input[name="exam_data[pg][re][ds]"]'));
        setState('btn-st',        hasValue('input[name="exam_data[st][re][ds]"]'));
        setState('btn-nct',       hasValue('input[name="exam_data[nct][iop_re]"]'));
        setState('btn-oe',        hasValue('input[name="exam_data[oe][lid_re]"]'));
        setState('btn-fundus',    hasValue('select[name="exam_data[fundus][disc_re]"]'));
        setState('btn-diagnosis', hasValue('input[name="exam_data[diagnoses][]"]'));
        setState('btn-dilate',    hasValue('input[name="exam_data[dilate]"]:checked'));
        setState('btn-rx',        hasValue('input[name^="medicines["][name$="[name]"]'));
        setState('btn-advice',    hasValue('textarea[name="advice"]'));
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

        const formatLensOe = (eye) => {
            const base = val(`exam_data[oe][lens_${eye}]`);
            const type = val(`exam_data[oe][pseudophakia_${eye}][operation_type]`);
            const exp  = val(`exam_data[oe][pseudophakia_${eye}][operation_expense]`);
            const hosp = val(`exam_data[oe][pseudophakia_${eye}][hospital_name]`);
            if (base === '-') { return '-'; }
            const extras = [type, exp !== '-' ? '₹' + exp : '', hosp].filter(v => v && v !== '-');
            return extras.length ? `${base} (${extras.join(', ')})` : base;
        };

        const coRows = Array.from(document.querySelectorAll('#coBody .co-row'));
        const complaints = coRows.map((row) => {
            const complaint = row.querySelector('[name*="[complaint]"]')?.value?.trim() || '';
            if (!complaint) { return ''; }
            const since = row.querySelector('[name*="[since]"]')?.value || '';
            const unit = row.querySelector('[name*="[unit]"]')?.value || '';
            const eye = row.querySelector('[name*="[eye]"]')?.value || '';
            const comment = row.querySelector('[name*="[comment]"]')?.value?.trim() || '';
            const parts = [complaint, since ? `since ${since} ${unit}` : '', eye, comment].filter(Boolean);
            return parts.join(' | ');
        }).filter(Boolean);

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

        const historyHtml =
            `<div class="mb-1" style="font-size:12px;">` +
            `<strong>C/O:</strong> ${complaints.length ? complaints.join('<br>') : '-'}</div>` +
            `<div class="d-flex flex-wrap align-items-center border-top pt-1 mt-1">` +
            makeVn('Vn', vnRe, vnLe) +
            makeVn('PH', phRe, phLe) +
            makeVn('NrVn', nrRe, nrLe) +
            `<div class="d-inline-flex align-items-center me-3 mb-1" style="font-size:11px;"><strong>IOP:</strong>&nbsp;${iopRe} / ${iopLe}</div>` +
            `</div>`;
        document.getElementById('canvas_history').innerHTML = historyHtml;

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
            `<tr><th>LENS</th><td>${formatLensOe('re')}</td><td>${formatLensOe('le')}</td></tr>` +
            `<tr><th>EM</th><td>${val('exam_data[oe][em_re]')}</td><td>${val('exam_data[oe][em_le]')}</td></tr>` +
            `<tr><th>COVERTEST</th><td>${val('exam_data[oe][covertest_re]')}</td><td>${val('exam_data[oe][covertest_le]')}</td></tr>` +
            `<tr><th>OTHER</th><td>${val('exam_data[oe][other_re]')}</td><td>${val('exam_data[oe][other_le]')}</td></tr>` +
            `</tbody></table>`;
        document.getElementById('canvas_oe').innerHTML = oeHtml;

        const fVal = (k) => {
            const el = form.querySelector(`[name="exam_data[fundus][${k}]"]`);
            return (el && String(el.value).trim()) ? el.value : '-';
        };
        const fundusHtml =
            `<table class="table table-sm table-bordered border-dark text-center mb-0" style="font-size:11px;">` +
            `<thead><tr><th class="bg-dark text-white">Fundus</th><th class="bg-dark text-white">RIGHT</th><th class="bg-dark text-white">LEFT</th></tr></thead>` +
            `<tbody>` +
            `<tr><th>DISC</th><td>${fVal('disc_re')}</td><td>${fVal('disc_le')}</td></tr>` +
            `<tr><th>FR</th><td>${fVal('fr_re')}</td><td>${fVal('fr_le')}</td></tr>` +
            `<tr><th>COMMENT</th><td>${fVal('comment_re')}</td><td>${fVal('comment_le')}</td></tr>` +
            `</tbody></table>`;
        document.getElementById('canvas_fundus').innerHTML = fundusHtml;

        const diagnoses = selectedLabels('input[name="exam_data[diagnoses][]"]');
        const diagnosisText = diagnoses.length ? diagnoses.join(', ') : '-';

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
            `<div class="mb-1" style="font-size:11px;"><strong>Dx:</strong> ${diagnosisText}</div>` +
            `<table class="table table-sm table-bordered border-dark mb-0" style="font-size:11px;">` +
            `<thead><tr><th class="bg-dark text-white">Medicine</th><th class="bg-dark text-white">Dose</th><th class="bg-dark text-white">Days</th><th class="bg-dark text-white">Eye</th><th class="bg-dark text-white">Instr.</th></tr></thead>` +
            `<tbody>${rxBodyHtml || '<tr><td colspan="5" class="text-center text-muted">No medicines</td></tr>'}</tbody>` +
            `</table>`;
        document.getElementById('canvas_rx').innerHTML = rxHtml;

        // Advice canvas
        const adviceText = (document.getElementById('advice_textarea')?.value || '').trim();
        const adviceHtml =
            `<table class="table table-sm table-bordered border-dark mb-0" style="page-break-inside:avoid;">` +
            `<thead><tr><th class="bg-dark text-white">CLINICAL ADVICE &amp; INSTRUCTIONS</th></tr></thead>` +
            `<tbody><tr id="advice_print_row"><td style="min-height:50px;font-size:13px;">${adviceText || 'No specific advice recorded.'}</td></tr></tbody>` +
            `</table>`;
        document.getElementById('canvas_advice').innerHTML = adviceHtml;
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
                        item.quantity || '',
                        item.route_id || ''
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

    @php
        $__dxGroups  = $masters['med_groups']->map(fn($g) => ['id' => $g->id, 'name' => $g->name, 'diagnosis_id' => $g->diagnosis_id, 'item_count' => $g->items->count()])->values();
        $__dxAdvices = $masters['advices']->map(fn($a) => ['id' => $a->id, 'advice' => $a->value ?? '', 'diagnosis_id' => $a->diagnosis_id ?? null])->values();
    @endphp
    // ── Diagnosis → Suggested Groups & Advice ────────────────────────────
    (function () {
        const dxMedGroups = @json($__dxGroups);
        const dxAdvices   = @json($__dxAdvices);
        const adviceMap   = Object.fromEntries(dxAdvices.map(a => [a.id, a.advice || '']));

        function esc(s) {
            return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function renderSuggestedGroups(groups, hasIds) {
            const wrap = document.getElementById('dxSuggestedGroups');
            if (!wrap) return;
            wrap.style.display = '';

            if (!hasIds) {
                wrap.innerHTML =
                    '<div class="d-flex align-items-center gap-2 px-3 py-2 mb-3 rounded" style="background:#f8fafc;border:1px dashed #cbd5e1;">' +
                    '<i class="bi bi-lightbulb" style="color:#f59e0b;font-size:15px;"></i>' +
                    '<span style="font-size:12px;color:#64748b;">Select a <strong>Diagnosis</strong> first — linked medicine groups will appear here for quick add.</span>' +
                    '</div>';
                return;
            }

            if (!groups.length) {
                wrap.innerHTML =
                    '<div class="d-flex align-items-center gap-2 px-3 py-2 mb-3 rounded" style="background:#fff7ed;border:1px solid #fed7aa;">' +
                    '<i class="bi bi-info-circle" style="color:#f97316;font-size:15px;"></i>' +
                    '<span style="font-size:12px;color:#9a3412;">No medicine groups linked to the selected diagnosis. You can link them from Medicine Groups master.</span>' +
                    '</div>';
                return;
            }

            wrap.innerHTML =
                '<div class="mb-3 p-3 rounded-3" style="background:linear-gradient(135deg,#eff6ff 0%,#f0fdf4 100%);border:1px solid #bfdbfe;">' +
                '<div class="d-flex align-items-center justify-content-between mb-2">' +
                '<span class="fw-semibold" style="font-size:13px;color:#1d4ed8;"><i class="bi bi-stars me-1"></i>Suggested Groups</span>' +
                '<span class="text-muted" style="font-size:11px;">Linked to selected diagnosis — click to load</span>' +
                '</div>' +
                '<div class="d-flex flex-wrap gap-2">' +
                groups.map(g =>
                    '<div class="bg-white rounded-3 px-3 py-2 d-flex flex-column" style="min-width:145px;max-width:200px;box-shadow:0 1px 4px rgba(0,0,0,.08);border:1px solid #dbeafe;">' +
                    '<div class="fw-semibold mb-1" style="font-size:12.5px;color:#1e40af;">' + esc(g.name) + '</div>' +
                    '<div class="text-muted mb-2" style="font-size:11px;"><i class="bi bi-capsule me-1"></i>' + g.item_count + ' medicine(s)</div>' +
                    '<button type="button" class="btn btn-primary btn-sm mt-auto" style="font-size:11.5px;padding:4px 0;" onclick="loadSuggestedGroup(' + g.id + ')">' +
                    '<i class="bi bi-plus-lg me-1"></i>Add All' +
                    '</button>' +
                    '</div>'
                ).join('') +
                '</div></div>';
        }

        function renderSuggestedAdvices(advices, hasIds) {
            const wrap = document.getElementById('dxSuggestedAdvices');
            if (!wrap) return;
            wrap.style.display = '';

            if (!hasIds) {
                wrap.innerHTML =
                    '<div class="d-flex align-items-center gap-2 px-3 py-2 mb-3 rounded" style="background:#f8fafc;border:1px dashed #cbd5e1;">' +
                    '<i class="bi bi-lightbulb" style="color:#f59e0b;font-size:15px;"></i>' +
                    '<span style="font-size:12px;color:#64748b;">Select a <strong>Diagnosis</strong> — linked advices will appear here.</span>' +
                    '</div>';
                return;
            }

            if (!advices.length) {
                wrap.innerHTML =
                    '<div class="d-flex align-items-center gap-2 px-3 py-2 mb-3 rounded" style="background:#fff7ed;border:1px solid #fed7aa;">' +
                    '<i class="bi bi-info-circle" style="color:#f97316;font-size:15px;"></i>' +
                    '<span style="font-size:12px;color:#9a3412;">No advices linked to selected diagnosis.</span>' +
                    '</div>';
                return;
            }

            wrap.innerHTML =
                '<div class="p-3 rounded-3 mb-3" style="background:linear-gradient(135deg,#f0fdf4 0%,#ecfdf5 100%);border:1px solid #bbf7d0;">' +
                '<div class="fw-semibold mb-2" style="font-size:13px;color:#166534;"><i class="bi bi-check-circle me-1"></i>Suggested Advices <small class="fw-normal text-muted">(click to append)</small></div>' +
                '<div class="d-flex flex-wrap gap-2">' +
                advices.map(a =>
                    '<button type="button" class="btn btn-sm btn-outline-success" style="font-size:12px;" onclick="appendSuggestedAdvice(' + a.id + ')">' +
                    esc(a.advice) + '</button>'
                ).join('') +
                '</div></div>';
        }

        window.loadSuggestedGroup = function (id) {
            const btn = document.querySelector(`button[onclick="loadSuggestedGroup(${id})"]`);
            if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...'; }
            fetch(groupApiBase + '/' + id)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('rxEmpty')?.remove();
                    (data.items || []).forEach(item => addMedicineRow(
                        item.medicine_id || '',
                        item.medicine ? (item.medicine.brand_name || item.medicine.name || '') : '',
                        item.dosage_id || '',
                        item.duration || '',
                        item.quantity || '',
                        item.route_id || ''
                    ));
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Added'; btn.classList.replace('btn-primary', 'btn-success'); }
                    updateLivePreview();
                })
                .catch(() => {
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-plus-lg me-1"></i>Add All'; }
                    alert('Could not load medicine group.');
                });
        };

        window.appendSuggestedAdvice = function (id) {
            const text = adviceMap[id] || '';
            if (!text) return;
            const ta = document.getElementById('advice_textarea');
            if (!ta) return;
            const cur = ta.value.trim();
            ta.value = cur ? cur + '\n' + text : text;
            if (typeof updateLivePreview === 'function') updateLivePreview();
        };

        function update() {
            const ids = Array.from(document.querySelectorAll('input[name="exam_data[diagnoses][]"]:checked')).map(el => +el.value);
            const hasIds = ids.length > 0;
            renderSuggestedGroups(dxMedGroups.filter(g => g.diagnosis_id && ids.includes(+g.diagnosis_id)), hasIds);
            renderSuggestedAdvices(dxAdvices.filter(a => a.diagnosis_id && ids.includes(+a.diagnosis_id)), hasIds);
        }

        document.addEventListener('change', function (e) {
            if (e.target.matches('input[name="exam_data[diagnoses][]"]')) update();
        });

        update();
    })();

    // ── Diagnosis modal: search filter + selected count ───────────────────
    (function () {
        const searchInput = document.getElementById('dxSearch');
        const countBadge  = document.getElementById('dxSelectedCount');
        const footerHint  = document.getElementById('dxFooterHint');
        const noResult    = document.getElementById('dxNoResult');

        function updateCount() {
            const n = document.querySelectorAll('input[name="exam_data[diagnoses][]"]:checked').length;
            if (countBadge) { countBadge.textContent = n; countBadge.style.display = n ? '' : 'none'; }
            if (footerHint) footerHint.textContent = n ? n + ' diagnosis selected' : '';
        }

        searchInput?.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            let visible = 0;
            document.querySelectorAll('.dx-tag-wrap').forEach(wrap => {
                const label = wrap.querySelector('label');
                const text  = (label?.textContent || '').toLowerCase();
                const show  = !q || text.includes(q);
                wrap.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            if (noResult) noResult.style.display = visible === 0 ? '' : 'none';
        });

        document.addEventListener('change', e => {
            if (e.target.matches('input[name="exam_data[diagnoses][]"]')) updateCount();
        });

        updateCount();
    })();

    // ── Advice textarea: char counter ─────────────────────────────────────
    (function () {
        const ta      = document.getElementById('advice_textarea');
        const counter = document.getElementById('adviceCharCount');
        if (!ta || !counter) return;
        function sync() { counter.textContent = ta.value.length + ' / 2000'; }
        ta.addEventListener('input', sync);
        sync();
    })();

    document.getElementById('cc_since_number')?.addEventListener('input', () => {
        checkProgress();
        updateLivePreview();
    });
    document.getElementById('cc_since_unit')?.addEventListener('change', () => {
        syncComplaintDuration();
        checkProgress();
        updateLivePreview();
    });

    // Advice quick-add chips → append to textarea
    document.querySelectorAll('.advice-quick-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const text = this.dataset.advice || '';
            if (!text) return;
            const ta = document.getElementById('advice_textarea');
            if (!ta) return;
            ta.value = ta.value.trim() ? ta.value.trim() + '\n' + text : text;
            ta.dispatchEvent(new Event('input'));
            if (typeof updateLivePreview === 'function') updateLivePreview();
        });
    });

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

    const dilateYes = document.getElementById('dilateYes');
    const dilateNo = document.getElementById('dilateNo');
    const dilationTimeWrap = document.getElementById('dilationTimeWrap');
    if (dilateYes && dilateNo && dilationTimeWrap) {
        function toggleDilationTime() {
            dilationTimeWrap.style.display = dilateYes.checked ? '' : 'none';
        }
        dilateYes.addEventListener('change', toggleDilationTime);
        dilateNo.addEventListener('change', toggleDilationTime);
    }

    document.querySelectorAll('.medicine-search-wrap').forEach(attachMedicineSearch);

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
            positionFixedDropdown(vdd, activeVInp, 180);
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

    // ── PG custom dropdowns ───────────────────────────────────────────────────
    (function () {
        const pgMasterOpts = {
            sph_cyl: @json($pgMasterOpts['sph_cyl'] ?? []),
            axis:    @json($pgMasterOpts['axis'] ?? []),
            vn:      @json($pgMasterOpts['vn'] ?? []),
            nrvn:    @json($pgMasterOpts['nrvn'] ?? []),
        };

        const pdd = document.createElement('div');
        pdd.className = 'co-dropdown';
        document.body.appendChild(pdd);

        let activePgInp = null;

        function positionPdd() {
            if (!activePgInp) { return; }
            positionFixedDropdown(pdd, activePgInp, 200);
        }

        function renderPdd(queryOverride) {
            if (!activePgInp) { return; }
            const all   = pgMasterOpts[activePgInp.dataset.master] || [];
            const query = queryOverride !== undefined ? queryOverride : (activePgInp.value || '').trim().toLowerCase();
            const items = query ? all.filter(v => String(v).toLowerCase().includes(query)) : all;
            const current = activePgInp.value;

            pdd.innerHTML = items.length
                ? items.map(v => {
                    const sel = v === current ? ' selected' : '';
                    return `<div class="co-item${sel}" data-val="${escapeAttr(String(v))}"><span class="co-item-name">${escapeAttr(String(v))}</span></div>`;
                }).join('')
                : '<div class="co-empty">No options found</div>';

            positionPdd();
            pdd.classList.add('show');

            pdd.querySelectorAll('.co-item').forEach(item => {
                item.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    if (!activePgInp) { return; }
                    const val = this.dataset.val;
                    activePgInp.value = val;
                    const hidden = activePgInp.closest('.pg-select-wrap')?.querySelector('input[type="hidden"]');
                    if (hidden) { hidden.value = val; }
                    pdd.classList.remove('show');
                    activePgInp = null;
                    checkProgress();
                    updateLivePreview();
                });
            });
        }

        document.querySelectorAll('.pg-inp').forEach(inp => {
            inp.addEventListener('focus', function () { activePgInp = this; renderPdd(''); });
            inp.addEventListener('input', function () { activePgInp = this; renderPdd(); });
            inp.addEventListener('blur', function () {
                const hidden = this.closest('.pg-select-wrap')?.querySelector('input[type="hidden"]');
                if (hidden) { hidden.value = this.value; }
            });
        });

        window.addEventListener('scroll', positionPdd, true);
        window.addEventListener('resize', positionPdd);

        document.addEventListener('mousedown', function (e) {
            if (!e.target.closest('.pg-select-wrap') && !pdd.contains(e.target)) {
                pdd.classList.remove('show');
                activePgInp = null;
            }
        });
    })();

    // ── NCT custom dropdown (multi-column grid) ───────────────────────────────
    (function () {
        const nctOpts = @json(collect($masters['nct'])->pluck('value')->filter()->values());

        const ndd = document.createElement('div');
        ndd.className = 'co-dropdown nct-dropdown';
        document.body.appendChild(ndd);

        let activeNctInp = null;

        function positionNdd() {
            if (!activeNctInp) { return; }
            positionFixedDropdown(ndd, activeNctInp, 260);
        }

        function renderNdd(queryOverride) {
            if (!activeNctInp) { return; }
            const query = queryOverride !== undefined ? queryOverride : (activeNctInp.value || '').trim().toLowerCase();
            const items = query ? nctOpts.filter(v => String(v).toLowerCase().includes(query)) : nctOpts;
            const current = activeNctInp.value;

            ndd.innerHTML = items.length
                ? `<div class="nct-dropdown-grid">${items.map(v => {
                    const sel = String(v) === String(current) ? ' selected' : '';
                    return `<div class="nct-grid-item${sel}" data-val="${escapeAttr(String(v))}">${escapeAttr(String(v))}</div>`;
                }).join('')}</div>`
                : '<div class="co-empty">No options found</div>';

            ndd.classList.add('show');
            positionNdd();

            ndd.querySelectorAll('.nct-grid-item').forEach(item => {
                item.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    if (!activeNctInp) { return; }
                    const val = this.dataset.val;
                    activeNctInp.value = val;
                    const hidden = activeNctInp.closest('.nct-select-wrap')?.querySelector('input[type="hidden"]');
                    if (hidden) { hidden.value = val; }
                    ndd.classList.remove('show');
                    activeNctInp = null;
                    checkProgress();
                    updateLivePreview();
                });
            });
        }

        document.querySelectorAll('.nct-inp').forEach(inp => {
            inp.addEventListener('focus', function () { activeNctInp = this; renderNdd(''); });
            inp.addEventListener('input', function () { activeNctInp = this; renderNdd(); });
            inp.addEventListener('blur', function () {
                const hidden = this.closest('.nct-select-wrap')?.querySelector('input[type="hidden"]');
                if (hidden) { hidden.value = this.value; }
            });
        });

        window.addEventListener('scroll', positionNdd, true);
        window.addEventListener('resize', positionNdd);

        document.addEventListener('mousedown', function (e) {
            if (!e.target.closest('.nct-select-wrap') && !ndd.contains(e.target)) {
                ndd.classList.remove('show');
                activeNctInp = null;
            }
        });
    })();

    // ── O/E custom dropdowns (favourites + search) ────────────────────────────
    (function () {
        const oeMasters = @json($oeMasterData ?? []);
        const oeFavBase = '{{ url($slug."/masters/detail") }}';

        const odd = document.createElement('div');
        odd.className = 'co-dropdown';
        document.body.appendChild(odd);

        let activeOeInp = null;

        function sortedOeItems(masterKey) {
            return [...(oeMasters[masterKey] || [])].sort((a, b) => {
                if (Boolean(a.is_favourite) !== Boolean(b.is_favourite)) { return a.is_favourite ? -1 : 1; }
                return String(a.value).localeCompare(String(b.value));
            });
        }

        function positionOdd() {
            if (!activeOeInp) { return; }
            positionFixedDropdown(odd, activeOeInp, 300);
        }

        function renderOdd(queryOverride) {
            if (!activeOeInp) { return; }
            const masterKey = activeOeInp.dataset.master;
            const favType   = activeOeInp.dataset.fav;
            const query     = queryOverride !== undefined ? queryOverride : (activeOeInp.value || '').trim().toLowerCase();
            const items     = sortedOeItems(masterKey).filter(i => String(i.value).toLowerCase().includes(query));
            const current   = activeOeInp.value;

            if (!items.length) {
                odd.innerHTML = '<div class="co-empty">No options found</div>';
                positionOdd();
                odd.classList.add('show');
                return;
            }

            const groups = [
                { label: '⭐ Favourites', rows: items.filter(i => i.is_favourite) },
                { label: 'All Options',  rows: items.filter(i => !i.is_favourite) },
            ].filter(g => g.rows.length);

            odd.innerHTML = groups.map(g =>
                `<div class="co-section-lbl">${g.label}</div>` +
                g.rows.map(item => {
                    const sel = item.value === current ? ' selected' : '';
                    return `<div class="co-item${sel}" data-val="${escapeAttr(item.value)}">` +
                        `<button type="button" class="co-fav-btn ${item.is_favourite ? 'fav-on' : ''}" data-id="${item.id}" data-fav="${escapeAttr(favType)}" title="${item.is_favourite ? 'Remove favourite' : 'Add favourite'}">${item.is_favourite ? '★' : '☆'}</button>` +
                        `<span class="co-item-name">${escapeAttr(item.value)}</span>` +
                        `</div>`;
                }).join('')
            ).join('');

            positionOdd();
            odd.classList.add('show');

            odd.querySelectorAll('.co-item').forEach(item => {
                item.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    if (!activeOeInp) { return; }
                    const val = this.dataset.val;
                    const oeKey = activeOeInp.dataset.oeKey;
                    const hidden = activeOeInp.closest('.oe-select-wrap')?.querySelector('input[type="hidden"]');
                    const eye = hidden?.name?.match(/_(re|le)\]$/)?.[1] || null;
                    activeOeInp.value = val;
                    if (hidden) { hidden.value = val; }
                    odd.classList.remove('show');
                    activeOeInp = null;
                    if (oeKey === 'lens' && eye && window.handleOeLensSelection) {
                        window.handleOeLensSelection(val, eye);
                    }
                    checkProgress();
                    updateLivePreview();
                });
            });

            odd.querySelectorAll('.co-fav-btn').forEach(btn => {
                btn.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const id  = this.dataset.id;
                    const ft  = this.dataset.fav;
                    fetch(`${oeFavBase}/${ft}/${id}/toggle-favourite`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': coCsrf, 'Accept': 'application/json' },
                    })
                    .then(r => r.json())
                    .then(data => {
                        const list = oeMasters[masterKey] || [];
                        const rec  = list.find(x => String(x.id) === String(id));
                        if (rec) { rec.is_favourite = data.is_favourite; }
                        renderOdd();
                    });
                });
            });
        }

        document.querySelectorAll('.oe-inp').forEach(inp => {
            inp.addEventListener('focus', function () { activeOeInp = this; renderOdd(''); });
            inp.addEventListener('input', function () { activeOeInp = this; renderOdd(); });
            inp.addEventListener('blur', function () {
                const hidden = this.closest('.oe-select-wrap')?.querySelector('input[type="hidden"]');
                if (hidden) { hidden.value = this.value; }
            });
        });

        window.addEventListener('scroll', positionOdd, true);
        window.addEventListener('resize', positionOdd);

        document.addEventListener('mousedown', function (e) {
            if (!e.target.closest('.oe-select-wrap') && !odd.contains(e.target)) {
                odd.classList.remove('show');
                activeOeInp = null;
            }
        });

        odd.addEventListener('mousedown', function (e) { e.preventDefault(); });
    })();

    // ── Fundus custom dropdowns (Disc + FR — favourites, search, A-Z) ─────────
    (function () {
        const discItems = @json(collect($masters['disc'])->map(fn ($o) => ['id' => $o->id, 'value' => $o->value, 'is_favourite' => (bool) ($o->is_favourite ?? false)])->values());
        const frItems   = @json(collect($masters['fr'])->map(fn ($o)   => ['id' => $o->id, 'value' => $o->value, 'is_favourite' => (bool) ($o->is_favourite ?? false)])->values());
        const favBase   = '{{ url($slug."/masters/detail") }}';

        let activeFInp = null, activeFType = null;

        const fdd = document.createElement('div');
        fdd.style.cssText = 'display:none;position:fixed;z-index:9999;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,.15);max-height:230px;overflow-y:auto;min-width:220px;';
        document.body.appendChild(fdd);

        function getItems() { return activeFType === 'disc' ? discItems : frItems; }

        function sortedItems(q) {
            let list = getItems();
            if (q) { list = list.filter(i => i.value.toLowerCase().includes(q.toLowerCase())); }
            const favs = list.filter(i => i.is_favourite).sort((a, b) => a.value.localeCompare(b.value));
            const rest = list.filter(i => !i.is_favourite).sort((a, b) => a.value.localeCompare(b.value));
            return [...favs, ...rest];
        }

        function renderFdd(queryOverride) {
            const q = queryOverride !== undefined ? queryOverride : (activeFInp?.value || '');
            const items = sortedItems(q);
            if (!items.length) {
                fdd.innerHTML = '<div style="padding:10px 12px;color:#94a3b8;font-size:13px;text-align:center;">No results</div>';
                return;
            }
            let html = '', prevFav = null;
            items.forEach((item, idx) => {
                if (idx > 0 && prevFav && !item.is_favourite) {
                    html += '<div style="border-top:1px dashed #e2e8f0;margin:2px 0;font-size:10px;color:#94a3b8;padding:2px 10px;">Other</div>';
                }
                const sel = activeFInp?.value === item.value;
                const esc = item.value.replace(/"/g, '&quot;');
                html += `<div class="fdd-item" data-val="${esc}" style="display:flex;align-items:center;gap:6px;padding:6px 10px;cursor:pointer;font-size:13px;${sel ? 'background:#eff6ff;font-weight:600;' : ''}">` +
                    `<button type="button" class="fdd-fav" data-id="${item.id}" style="background:none;border:none;padding:0;font-size:15px;line-height:1;color:${item.is_favourite ? '#f59e0b' : '#cbd5e1'};cursor:pointer;flex-shrink:0;">${item.is_favourite ? '★' : '☆'}</button>` +
                    `<span style="flex:1;">${item.value}</span>` +
                    `</div>`;
                prevFav = item.is_favourite;
            });
            fdd.innerHTML = html;
        }

        function positionFdd() {
            if (!activeFInp) { return; }
            const r = activeFInp.getBoundingClientRect();
            fdd.style.top   = (r.bottom + 2) + 'px';
            fdd.style.left  = r.left + 'px';
            fdd.style.width = Math.max(r.width, 220) + 'px';
            fdd.style.display = 'block';
        }

        const fundusModal = document.getElementById('modalFundus');

        fundusModal.addEventListener('focusin', e => {
            const inp = e.target.closest('.fundus-dd-inp');
            if (!inp) { return; }
            activeFInp  = inp;
            activeFType = inp.dataset.ddType;
            renderFdd('');
            positionFdd();
        });

        fundusModal.addEventListener('input', e => {
            const inp = e.target.closest('.fundus-dd-inp');
            if (!inp) { return; }
            renderFdd();
            positionFdd();
        });

        fdd.addEventListener('mousedown', e => {
            const favBtn = e.target.closest('.fdd-fav');
            if (favBtn) {
                e.preventDefault();
                const id   = favBtn.dataset.id;
                const type = activeFType;
                fetch(`${favBase}/${type}/${id}/toggle-favourite`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                }).then(r => r.json()).then(d => {
                    const items = type === 'disc' ? discItems : frItems;
                    const item  = items.find(i => i.id == id);
                    if (item) { item.is_favourite = d.is_favourite; }
                    renderFdd();
                });
                return;
            }
            const row = e.target.closest('.fdd-item');
            if (row) {
                e.preventDefault();
                const v = row.dataset.val;
                if (activeFInp) {
                    activeFInp.value = v;
                    const wrap = activeFInp.closest('.fundus-dd-wrap');
                    if (wrap) { const h = wrap.querySelector('input[type="hidden"]'); if (h) { h.value = v; } }
                    activeFInp.dispatchEvent(new Event('change', { bubbles: true }));
                }
                fdd.style.display = 'none';
            }
        });

        document.addEventListener('click', e => {
            if (e.target !== activeFInp && !fdd.contains(e.target)) { fdd.style.display = 'none'; }
        });
        window.addEventListener('scroll', () => { if (fdd.style.display !== 'none') { positionFdd(); } }, true);
        window.addEventListener('resize', () => { if (fdd.style.display !== 'none') { positionFdd(); } });
        fundusModal?.addEventListener('hide.bs.modal', () => { fdd.style.display = 'none'; });
    })();

    // ── Pseudophakia popup (Lens → Pseudophakia) ──────────────────────────────
    (function () {
        const pseudoModalEl = document.getElementById('modalPseudophakia');
        if (!pseudoModalEl) { return; }

        const pseudoModal = bootstrap.Modal.getOrCreateInstance(pseudoModalEl);
        let currentPseudoEye = null;
        let selectedOpType = '';

        function isPseudophakia(val) {
            return String(val).toLowerCase().includes('pseudophakia');
        }

        function pseudoInput(eye, cls) {
            return document.querySelector(`input.pseudo-${cls}[data-eye="${eye}"]`);
        }

        window.updatePseudoSummary = function (eye) {
            const el = document.querySelector(`.pseudo-lens-summary[data-eye="${eye}"]`);
            if (!el) { return; }
            const type = pseudoInput(eye, 'op-type')?.value || '';
            const exp  = pseudoInput(eye, 'op-expense')?.value || '';
            const hosp = pseudoInput(eye, 'hospital')?.value || '';
            const parts = [];
            if (type) { parts.push(type); }
            if (exp)  { parts.push('₹' + exp); }
            if (hosp) { parts.push(hosp); }
            if (parts.length) {
                el.textContent = parts.join(' · ');
                el.style.display = '';
            } else {
                el.textContent = '';
                el.style.display = 'none';
            }
        };

        function clearPseudoData(eye) {
            ['op-type', 'op-expense', 'hospital'].forEach(cls => {
                const f = pseudoInput(eye, cls);
                if (f) { f.value = ''; }
            });
            window.updatePseudoSummary(eye);
        }

        function setOpTypeUI(type) {
            selectedOpType = type || '';
            pseudoModalEl.querySelectorAll('.pseudo-type-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.val === selectedOpType);
            });
        }

        window.openPseudoModal = function (eye) {
            currentPseudoEye = eye;
            document.getElementById('pseudoModalEyeLabel').textContent = eye === 're' ? 'Right Eye (RE)' : 'Left Eye (LE)';
            setOpTypeUI(pseudoInput(eye, 'op-type')?.value || '');
            document.getElementById('pseudoOpExpense').value = pseudoInput(eye, 'op-expense')?.value || '';
            document.getElementById('pseudoHospital').value = pseudoInput(eye, 'hospital')?.value || '';
            pseudoModal.show();
        };

        window.handleOeLensSelection = function (val, eye) {
            if (isPseudophakia(val)) {
                window.openPseudoModal(eye);
            } else {
                clearPseudoData(eye);
            }
        };

        pseudoModalEl.querySelectorAll('.pseudo-type-btn').forEach(btn => {
            btn.addEventListener('click', function () { setOpTypeUI(this.dataset.val); });
        });

        document.getElementById('pseudoModalSave')?.addEventListener('click', function () {
            if (!currentPseudoEye) { return; }
            const exp  = document.getElementById('pseudoOpExpense').value.trim();
            const hosp = document.getElementById('pseudoHospital').value.trim();
            if (pseudoInput(currentPseudoEye, 'op-type')) { pseudoInput(currentPseudoEye, 'op-type').value = selectedOpType; }
            if (pseudoInput(currentPseudoEye, 'op-expense')) { pseudoInput(currentPseudoEye, 'op-expense').value = exp; }
            if (pseudoInput(currentPseudoEye, 'hospital')) { pseudoInput(currentPseudoEye, 'hospital').value = hosp; }
            window.updatePseudoSummary(currentPseudoEye);
            pseudoModal.hide();
            checkProgress();
            updateLivePreview();
        });

        ['re', 'le'].forEach(eye => window.updatePseudoSummary(eye));
    })();

    syncComplaintDuration();
    checkProgress();
    updateLivePreview();
});

(function () {
    const draftKey = 'hms_secondary_draft_pt_{{ $patient->id }}';
    const form = document.getElementById('primaryExamForm');

    function saveDraft() {
        const data = {};
        form.querySelectorAll('input, select, textarea').forEach(el => {
            if (!el.name) { return; }
            if (el.type === 'checkbox' || el.type === 'radio') {
                if (el.checked) {
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
        } catch (_) {
            // ignore storage errors
        }
    }

    function loadDraft() {
        let saved;
        try {
            saved = localStorage.getItem(draftKey);
        } catch (_) {
            return;
        }

        if (!saved) { return; }

        let data;
        try {
            data = JSON.parse(saved);
        } catch (_) {
            return;
        }

        Object.entries(data).forEach(([name, value]) => {
            if (Array.isArray(value)) {
                form.querySelectorAll(`input[name="${name}"][type="checkbox"]`).forEach(cb => {
                    cb.checked = value.includes(cb.value);
                });
            } else {
                const el = form.querySelector(`[name="${name}"]`);
                if (!el) { return; }

                if (el.type === 'radio') {
                    const radio = form.querySelector(`[name="${name}"][value="${value}"]`);
                    if (radio) {
                        radio.checked = true;
                    }
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
        try {
            localStorage.removeItem(draftKey);
        } catch (_) {
            // ignore storage errors
        }
    });

    setTimeout(loadDraft, 300);
})();
</script>

@endsection
