@extends('hospital.layouts.app')
@section('title', 'Primary Examination - ' . $patient->full_name)@section('page-header', 'Primary Eye Examination')@section('page-actions')
    @if(auth('hospital_user')->user()?->role?->slug !== 'doctor')
        <a href="{{ route('hospital.patients.index', ['slug' => $slug, 'patient' => $patient->id]) }}"
           class="btn secondary-exam-back-btn btn-sm">
            Back to Patient
        </a>
    @endif
        @if($exam)
            <a href="{{ route('hospital.exam.primary.print', ['slug' => $slug, 'id' => $patient->id]) }}"
               target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-printer"></i> Print Rx
            </a>
        @endif
        <!-- @haspermission('opd.foc.create')
            <button type="button" class="btn secondary-exam-foc-btn btn-sm" data-bs-toggle="modal" data-bs-target="#focRequestExamModal">
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
    .pg-select-wrap .pg-inp { padding-right: 12px; }
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
    .oe-table th.oe-eye-col { font-size: 12px; font-weight: 700; letter-spacing: .06em; padding: 10px 14px; color: #1B4F72; border-bottom: 2px solid #1B4F72 !important; background: #f0f4f8; }
    .oe-table td.oe-cell-re { background: #ffffff; }
    .oe-table td.oe-cell-le { background: #f8faff; }
    .oe-table td.oe-label-cell { background: #fafbfc; vertical-align: middle; width: 140px; }
    .oe-select-wrap { position: relative; }
    .oe-select-wrap .oe-inp {
        padding-right: 28px;
        border: 1px solid #1B4F72;
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
    /* Medicine suggest — position:fixed escapes ALL overflow clipping (table, modal-body, etc.) */
    .medicine-suggest { display:none; position:fixed; z-index:9999; background:#fff; border:1px solid #dde3ea; border-radius:10px; box-shadow:0 8px 28px rgba(0,0,0,.16); overflow-y:auto; padding:4px 0; scrollbar-width:thin; scrollbar-color:#cbd5e1 transparent; }
    .med-opt { padding:8px 14px; cursor:pointer; border-bottom:1px solid #f1f5f9; transition:background .12s; }
    .med-opt:last-child { border-bottom:none; }
    .med-opt:hover, .med-opt.active { background:#f0f4f8; }
    .med-opt-brand { font-size:13px; font-weight:600; color:#1e293b; }
    .med-opt-generic { font-size:11px; color:#64748b; margin-top:2px; }
    /* Advice More dropdown hover */
    .advice-more-item:hover { background: #f1f5f9; }
    .advice-more-item .advice-fav-btn:hover { opacity: 1 !important; }
    #adviceMoreList { scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
    /* C/O favourite pills */
    .co-fav-pill {
        display: inline-flex; align-items: center; gap: 5px;
        background: rgba(245,158,11,0.10); border: 1px solid rgba(245,158,11,0.38);
        color: #78350f; border-radius: 999px; font-size: 12px;
        padding: 4px 8px 4px 11px; font-weight: 600; cursor: pointer;
        transition: background .15s, border-color .15s; user-select: none;
        white-space: nowrap;
    }
    .co-fav-pill:hover { background: rgba(245,158,11,0.20); border-color: rgba(245,158,11,0.55); }
    .co-fav-pill-star {
        background: none; border: none; cursor: pointer; font-size: 13px;
        color: #d97706; padding: 0; line-height: 1; opacity: 0.7;
        transition: opacity .15s, color .15s; flex-shrink: 0;
    }
    .co-fav-pill-star:hover { opacity: 1; color: #dc2626; }
    .co-fav-pills-section { margin-bottom: 10px; }
    .co-fav-pills-lbl { font-size: 10px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .07em; color: #94a3b8; margin-bottom: 5px; display: block; }
    /* C/O custom dropdown */
    .co-select-wrap { position: relative; display: inline-block; width: 100%; max-width: 300px; }
    .co-dropdown {
        position: fixed; width: 300px;
        background: #fff; border: 1px solid #dde3ea; border-radius: 12px;
        box-shadow: 0 12px 40px rgba(15,23,42,.18), 0 2px 8px rgba(27,79,114,.08);
        z-index: 9999; max-height: 300px; overflow-y: auto; display: none;
        padding: 4px 0;
    }
    .co-dropdown::-webkit-scrollbar { width: 5px; }
    .co-dropdown::-webkit-scrollbar-track { background: transparent; }
    .co-dropdown::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .co-dropdown.show { display: block; animation: coFadeIn .12s ease; }
    @keyframes coFadeIn { from { opacity:0; transform:translateY(-5px); } to { opacity:1; transform:translateY(0); } }
    /* Narrower dropdown for PG axis/vn fields */
    .pg-co-dropdown { width: 180px !important; }
    .co-section-lbl {
        font-size: 10px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .07em; color: #94a3b8; padding: 8px 12px 4px;
        position: sticky; top: 0; background: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
    }
    .co-item {
        display: flex; align-items: center; padding: 9px 14px;
        cursor: pointer; font-size: 13px; font-weight: 500; gap: 8px;
        user-select: none; border-radius: 6px; margin: 1px 4px;
        transition: background .1s, color .1s;
    }
    .co-item:hover { background: rgba(27,79,114,.08); color: #1B4F72; }
    .co-item:hover .co-item-name { color: #1B4F72; }
    .co-item.selected { background: #1B4F72; font-weight: 700; }
    .co-item.selected .co-item-name { color: #fff; }
    .co-item-name { flex: 1; color: #1e293b; }
    /* Axis / VN inputs look like select fields */
    .pg-select-wrap:has(.pg-inp-chevron) .pg-inp {
        cursor: pointer; border-color: #1B4F72 !important;
        background: #f8fafc;
    }
    .pg-select-wrap:has(.pg-inp-chevron) .pg-inp:focus {
        background: #fff;
    }
    .pg-select-wrap .pg-inp-chevron {
        color: #1B4F72 !important;
        font-size: 10px !important;
    }
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
/* ── Wait Status Pill ── */
.wait-pill { display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 10px 3px 3px;font-weight:700;white-space:nowrap;transition:background .4s,box-shadow .4s;vertical-align:middle; }
.wait-pill.wait-green  { background:rgba(22,163,74,.10);  box-shadow:0 0 0 1px rgba(22,163,74,.25); }
.wait-pill.wait-orange { background:rgba(234,88,12,.10);  box-shadow:0 0 0 1px rgba(234,88,12,.25); }
.wait-pill.wait-red    { background:rgba(220,38,38,.10);  box-shadow:0 0 0 1px rgba(220,38,38,.25); }
.wait-pill.wait-fire   { background:rgba(220,38,38,.10);  box-shadow:0 0 0 1px rgba(220,38,38,.35); animation:fire-glow 1s ease-in-out infinite alternate; }
@keyframes fire-glow   { from{box-shadow:0 0 0 1px rgba(220,38,38,.35),0 0 6px rgba(234,88,12,.4);}to{box-shadow:0 0 0 2px rgba(220,38,38,.55),0 0 12px rgba(234,88,12,.6);} }
.wp-r { display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;font-size:.65rem;font-weight:900;color:#fff;flex-shrink:0; }
.wait-green  .wp-r { background:#16a34a; }
.wait-orange .wp-r { background:#ea580c; }
.wait-red    .wp-r { background:#dc2626; }
.wait-fire   .wp-r { background:linear-gradient(135deg,#dc2626,#ea580c); }
.wp-time { font-size:.72rem;font-weight:700; }
.wait-green  .wp-time { color:#15803d; }
.wait-orange .wp-time { color:#c2410c; }
.wait-red    .wp-time { color:#b91c1c; }
.wait-fire   .wp-time { color:#dc2626; }
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
$sinceUnit = rtrim($sinceUnit, 's') . 's';

$prescriptions = $exam?->prescriptions ?? collect();
@endphp

@if(auth('hospital_user')->user()?->role?->slug === 'doctor')
    <form id="primaryExamForm" method="POST" action="{{ route('hospital.exam.primary.save', ['slug' => $slug, 'id' => $patient->id]) }}" novalidate>
        @csrf
        <input type="hidden" name="doctor_id" value="{{ old('doctor_id', $currentDoctorId ?? auth('hospital_user')->id()) }}">

        @php
    $pWGreen = (int) hospital_setting('wait_green_max', 30);
    $pWOrange = (int) hospital_setting('wait_orange_max', 60);
    $pWRed = (int) hospital_setting('wait_red_max', 120);
    $pWMins = (int) ($patient->checked_in_at ?? $patient->created_at)->diffInMinutes(now());
    $pWCls = $pWMins < $pWGreen ? 'wait-green' : ($pWMins < $pWOrange ? 'wait-orange' : ($pWMins < $pWRed ? 'wait-red' : 'wait-fire'));
    $pWFmt = $pWMins < 60 ? $pWMins . 'm' : (floor($pWMins / 60) . 'h' . ($pWMins % 60 > 0 ? ' ' . ($pWMins % 60) . 'm' : ''));
        @endphp
        {{-- Patient Info Bar --}}
        <div class="d-print-none mb-2 px-3 py-2 rounded border d-flex flex-wrap align-items-center gap-3"
             style="background:linear-gradient(135deg,#f0f7ff,#e8f4fd);border-color:rgba(27,79,114,.2)!important;font-size:13px;">
            <div class="d-flex align-items-center gap-1">
                <span style="color:#94a3b8;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;">MRD</span>
                <span class="fw-bold ms-1" style="color:#1B4F72;">{{ $patient->patient_code ?? '-' }}</span>
            </div>
            <span style="width:1px;height:16px;background:#cbd5e1;display:inline-block;"></span>
            <div class="d-flex align-items-center gap-1">
                <i class="bi bi-person-fill" style="color:#1B4F72;font-size:13px;"></i>
                <span class="fw-semibold ms-1" style="color:#1e293b;">{{ $patient->full_name }}</span>
            </div>
            <span style="width:1px;height:16px;background:#cbd5e1;display:inline-block;"></span>
            <div class="d-flex align-items-center gap-1">
                <span style="color:#94a3b8;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;">Age/Gender</span>
                <span class="fw-semibold ms-1" style="color:#1e293b;">{{ $patient->age ?? '-' }} / {{ ucfirst($patient->gender ?? '-') }}</span>
            </div>
            <span style="width:1px;height:16px;background:#cbd5e1;display:inline-block;"></span>
            <div class="d-flex align-items-center gap-1">
                <i class="bi bi-telephone-fill" style="color:#1B4F72;font-size:12px;"></i>
                <span class="ms-1" style="color:#1e293b;">{{ $patient->contact_no ?? '-' }}</span>
            </div>
            <span style="width:1px;height:16px;background:#cbd5e1;display:inline-block;"></span>
            <div class="d-flex align-items-center gap-1">
                <span style="color:#94a3b8;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;">Wait Status</span>
                <span class="wait-pill {{ $pWCls }} ms-1"
                      data-wait-from="{{ ($patient->checked_in_at ?? $patient->created_at)->toIso8601String() }}"
                      data-thresholds="{{ $pWGreen }},{{ $pWOrange }},{{ $pWRed }}">
                    <span class="wp-r">R</span>
                    <span class="wp-time">{{ $pWFmt }}</span>
                </span>
            </div>
        </div>

        <div class="exam-layout-wrapper">


            <div class="doctor-stepper-sidebar">
                <h6 class="fw-bold text-muted mb-2 ps-2">EXAM STEPS</h6>

                <div class="step-group-label first">Primary Exam</div>
                <button type="button" class="btn btn-outline-secondary step-btn" id="btn-clinical" data-bs-toggle="modal" data-bs-target="#modalClinical">C/O</button>
                <button type="button" class="btn btn-outline-secondary step-btn" id="btn-hko" data-bs-toggle="modal" data-bs-target="#modalHko">K/C/O &amp; H/O</button>
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
                <div class="card shadow-sm" style="padding:16px;" id="liveReportCanvas">
                    <div class="row g-2 clinical-grid-container" style="font-size:13px;">

                        <div class="col-6 col-md-6 d-flex flex-column gap-2">
                            <div class="canvas-box"><div class="canvas-section-title">History &amp; Vision</div><div id="canvas_history"><em class="text-muted" style="font-size:11px;">Enter chief complaints...</em></div><div id="canvas_vision" class="mt-1"></div></div>
                            <div class="canvas-box"><div class="canvas-section-title">ST</div><div id="canvas_st" class="mb-1"></div><div class="canvas-section-title mt-1">Diagnosis &amp; Rx</div><div id="canvas_rx"></div></div>
                        </div>
                        <div class="col-6 col-md-6 d-flex flex-column gap-2">
                            <div class="canvas-box"><div class="canvas-section-title">O/E</div><div id="canvas_oe"></div></div>
                            <div class="canvas-box"><div class="canvas-section-title">Fundus</div><div id="canvas_fundus"></div></div>
                        </div>
                    </div>
                    <div class="canvas-box mt-2">
                        <div class="canvas-section-title">Advice</div>
                        <div id="canvas_advice"></div>
                    </div>
                </div>
            </div>
        </div>
@else
    <form id="primaryExamForm" method="POST" action="{{ route('hospital.exam.primary.save', ['slug' => $slug, 'id' => $patient->id]) }}" novalidate>
        @csrf
        <input type="hidden" name="doctor_id" value="{{ old('doctor_id', $currentDoctorId ?? auth('hospital_user')->id()) }}">

        @php
    $pWGreen = (int) hospital_setting('wait_green_max', 30);
    $pWOrange = (int) hospital_setting('wait_orange_max', 60);
    $pWRed = (int) hospital_setting('wait_red_max', 120);
    $pWMins = (int) ($patient->checked_in_at ?? $patient->created_at)->diffInMinutes(now());
    $pWCls = $pWMins < $pWGreen ? 'wait-green' : ($pWMins < $pWOrange ? 'wait-orange' : ($pWMins < $pWRed ? 'wait-red' : 'wait-fire'));
    $pWFmt = $pWMins < 60 ? $pWMins . 'm' : (floor($pWMins / 60) . 'h' . ($pWMins % 60 > 0 ? ' ' . ($pWMins % 60) . 'm' : ''));
        @endphp
        {{-- Patient Info Bar --}}
        <div class="d-print-none mb-2 px-3 py-2 rounded border d-flex flex-wrap align-items-center gap-3"
             style="background:linear-gradient(135deg,#f0f7ff,#e8f4fd);border-color:rgba(27,79,114,.2)!important;font-size:13px;">
            <div class="d-flex align-items-center gap-1">
                <span style="color:#94a3b8;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;">MRD</span>
                <span class="fw-bold ms-1" style="color:#1B4F72;">{{ $patient->patient_code ?? '-' }}</span>
            </div>
            <span style="width:1px;height:16px;background:#cbd5e1;display:inline-block;"></span>
            <div class="d-flex align-items-center gap-1">
                <i class="bi bi-person-fill" style="color:#1B4F72;font-size:13px;"></i>
                <span class="fw-semibold ms-1" style="color:#1e293b;">{{ $patient->full_name }}</span>
            </div>
            <span style="width:1px;height:16px;background:#cbd5e1;display:inline-block;"></span>
            <div class="d-flex align-items-center gap-1">
                <span style="color:#94a3b8;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;">Age/Gender</span>
                <span class="fw-semibold ms-1" style="color:#1e293b;">{{ $patient->age ?? '-' }} / {{ ucfirst($patient->gender ?? '-') }}</span>
            </div>
            <span style="width:1px;height:16px;background:#cbd5e1;display:inline-block;"></span>
            <div class="d-flex align-items-center gap-1">
                <i class="bi bi-telephone-fill" style="color:#1B4F72;font-size:12px;"></i>
                <span class="ms-1" style="color:#1e293b;">{{ $patient->contact_no ?? '-' }}</span>
            </div>
            <span style="width:1px;height:16px;background:#cbd5e1;display:inline-block;"></span>
            <div class="d-flex align-items-center gap-1">
                <span style="color:#94a3b8;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;">Wait Status</span>
                <span class="wait-pill {{ $pWCls }} ms-1"
                      data-wait-from="{{ ($patient->checked_in_at ?? $patient->created_at)->toIso8601String() }}"
                      data-thresholds="{{ $pWGreen }},{{ $pWOrange }},{{ $pWRed }}">
                    <span class="wp-r">R</span>
                    <span class="wp-time">{{ $pWFmt }}</span>
                </span>
            </div>
        </div>

        <div class="stepper-wrap d-flex d-print-none justify-content-between align-items-center mb-3 p-2 bg-white rounded shadow-sm border gap-2 flex-wrap">
            <div class="d-flex align-items-center gap-1 flex-wrap">
                <span class="step-group-tag">Primary</span>
                <button type="button" class="btn btn-outline-secondary step-btn btn-sm" id="btn-clinical"  data-bs-toggle="modal" data-bs-target="#modalClinical">C/O</button>
                <button type="button" class="btn btn-outline-secondary step-btn btn-sm" id="btn-hko"       data-bs-toggle="modal" data-bs-target="#modalHko">K/C/O &amp; H/O</button>
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
                        <div class="canvas-section-title">ST</div>
                        <div id="canvas_st" class="mb-1"></div>
                        <div class="canvas-section-title mt-1">Diagnosis &amp; Rx</div>
                        <div id="canvas_rx"></div>
                    </div>

                </div>

                {{-- RIGHT COLUMN --}}
                <div class="col-6 col-md-6 d-flex flex-column gap-2">

                    {{-- Box 3: O/E --}}
                    <div class="canvas-box">
                        <div class="canvas-section-title">O/E</div>
                        <div id="canvas_oe"></div>
                    </div>

                    {{-- Box 4: Fundus --}}
                    <div class="canvas-box">
                        <div class="canvas-section-title">Fundus</div>
                        <div id="canvas_fundus"></div>
                    </div>

                </div>
            </div>
            <div class="canvas-box mt-2">
                <div class="canvas-section-title">Advice</div>
                <div id="canvas_advice"></div>
            </div>
        </div>
@endif
    <div class="modal fade" id="modalClinical" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">C/O</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="coFavPillsWrap" class="co-fav-pills-section" style="display:none;">
                        <span class="co-fav-pills-lbl">⭐ Favourites — click to add</span>
                        <div id="coFavPills" class="d-flex flex-wrap gap-1"></div>
                    </div>
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
                                                @foreach(range(1, 10) as $n)
                                                    <option value="{{ $n }}" {{ ($row['since'] ?? '') == $n ? 'selected' : '' }}>{{ $n }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="exam_data[co_rows][{{ $ri }}][unit]" class="form-select form-select-sm">
                                                @foreach(['Days', 'Weeks', 'Months', 'Years', 'Longtime'] as $u)
                                                    <option value="{{ $u }}" {{ ($row['unit'] ?? 'Days') === $u ? 'selected' : '' }}>{{ $u }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="exam_data[co_rows][{{ $ri }}][eye]" class="form-select form-select-sm">
                                                <option value="">-</option>
                                                @foreach(['RE' => 'Right', 'LE' => 'Left', 'Both' => 'Both', 'OU' => 'OU'] as $val => $lbl)
                                                    <option value="{{ $val }}" {{ ($row['eye'] ?? '') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="exam_data[co_rows][{{ $ri }}][comment]" value="{{ $row['comment'] ?? '' }}" class="form-control form-control-sm" placeholder="Comment"></td>
                                        <td class="text-center"><button type="button" class="btn btn-sm btn-danger px-2" onclick="this.closest('tr').remove(); checkProgress(); updateLivePreview();">&times;</button></td>
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
                    <h5 class="modal-title">K/C/O &amp; H/O</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- K/C/O --}}
                    <div class="fw-semibold mb-2" style="font-size:13px;">K/C/O</div>
                    <div id="kcoFavPillsWrap" class="co-fav-pills-section" style="display:none;">
                        <span class="co-fav-pills-lbl">⭐ Favourites — click to add</span>
                        <div id="kcoFavPills" class="d-flex flex-wrap gap-1"></div>
                    </div>
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
                                                @foreach(range(1, 10) as $n)
                                                    <option value="{{ $n }}" {{ ($krow['since'] ?? '') == $n ? 'selected' : '' }}>{{ $n }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="exam_data[kco_rows][{{ $ki }}][unit]" class="form-select form-select-sm">
                                                @foreach(['Days', 'Weeks', 'Months', 'Years', 'Longtime'] as $u)
                                                    <option value="{{ $u }}" {{ ($krow['unit'] ?? 'Years') === $u ? 'selected' : '' }}>{{ $u }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="exam_data[kco_rows][{{ $ki }}][comment]" value="{{ $krow['comment'] ?? '' }}" class="form-control form-control-sm" placeholder="Comment"></td>
                                        <td class="text-center"><button type="button" class="btn btn-sm btn-danger px-2" onclick="this.closest('tr').remove(); checkProgress(); updateLivePreview();">&times;</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(empty($ed['kco_rows']))
                        <p class="text-muted text-center mt-3" id="kcoEmptyMsg" style="font-size:13px;">No conditions added. Search above and click + Add.</p>
                    @endif
                    {{-- H/O --}}
                    <div class="mt-4" style="border-top:1px solid #e2e8f0; padding-top:12px;">
                        <label class="form-label fw-semibold mb-2" style="font-size:13px; display:block;">H/O</label>
                        <div id="hnoFavPillsWrap" class="co-fav-pills-section" style="display:none;">
                            <span class="co-fav-pills-lbl">⭐ Favourites — click to add</span>
                            <div id="hnoFavPills" class="d-flex flex-wrap gap-1"></div>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="co-select-wrap">
                                <input type="text" id="hnoSearch" class="form-control form-control-sm" placeholder="Search H/O..." autocomplete="off" style="min-width:260px;">
                                <div id="hnoDropdown" class="co-dropdown"></div>
                            </div>
                            <button type="button" id="addHnoChip" class="btn btn-sm btn-primary px-3">+ Add</button>
                        </div>
                        <div id="hnoChips" class="d-flex flex-wrap gap-1 mb-1">
                            @foreach(array_filter(array_map('trim', explode(',', $ed['history'] ?? ''))) as $hval)
                                <span class="badge rounded-pill hno-chip" style="background:rgba(27,79,114,.1);color:#1B4F72;font-size:12px;font-weight:600;padding:.35em .75em;border:1px solid rgba(27,79,114,.2);">
                                    {{ $hval }}
                                    <button type="button" class="btn-close btn-close-sm ms-1 hno-remove" style="font-size:.6em;vertical-align:middle;" aria-label="Remove"></button>
                                </span>
                            @endforeach
                        </div>
                        <input type="hidden" name="exam_data[history]" id="hnoHidden" value="{{ $ed['history'] ?? '' }}">
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
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background:#1B4F72;">
                    <h5 class="modal-title fw-semibold text-white">
                        <i class="bi bi-eye me-2"></i>VN
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">

                    @php
$vnCols = [
    ['abbr' => 'VN', 'full' => 'Distance Vision', 'master' => 'vn', 'field_re' => 'vn_re', 'field_le' => 'vn_le'],
    ['abbr' => 'PnVn', 'full' => 'Pinhole', 'master' => 'pnvn', 'field_re' => 'pnvn_re', 'field_le' => 'pnvn_le'],
    ['abbr' => 'NrVn', 'full' => 'Near Vision', 'master' => 'nrvn', 'field_re' => 'nrvn_re', 'field_le' => 'nrvn_le'],
];
                    @endphp

                    @foreach(['re' => 'Right Eye (RE)', 'le' => 'Left Eye (LE)'] as $eye => $eyeLabel)
                        <div class="mb-4 rounded-3 overflow-hidden" style="border:1px solid #dde3ea;box-shadow:0 1px 4px rgba(0,0,0,.07);">
                            <div class="d-flex align-items-center gap-2 px-3 py-2" style="background:#1B4F72;">
                                <i class="bi bi-eye-fill text-white"></i>
                                <span class="fw-semibold text-white" style="font-size:14px;">{{ $eyeLabel }}</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0" style="font-size:13px;">
                                    <thead style="background:#f0f4f8;">
                                        <tr>
                                            @foreach($vnCols as $col)
                                                <th class="text-center" style="font-weight:700;font-size:12px;letter-spacing:.06em;color:#1B4F72;border-bottom:2px solid #1B4F72;">
                                                    {{ $col['abbr'] }}
                                                    <div style="font-size:10px;font-weight:500;color:#64748b;letter-spacing:.03em;text-transform:none;">{{ $col['full'] }}</div>
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr style="background:white;">
                                            @foreach($vnCols as $col)
                                                @php $fieldKey = 'field_' . $eye; @endphp
                                                <td class="text-center py-2">
                                                    <div class="vision-select-wrap" style="max-width:160px;margin:auto;">
                                                        <input type="text" class="form-control form-control-sm vision-inp text-center"
                                                            style="border-color:#1B4F72;font-size:13px;"
                                                            placeholder="-" autocomplete="off"
                                                            data-master="{{ $col['master'] }}" data-field="{{ $col[$fieldKey] }}"
                                                            value="{{ $vision[$col[$fieldKey]] ?? '' }}">
                                                        <input type="hidden" name="exam_data[vision][{{ $col[$fieldKey] }}]" value="{{ $vision[$col[$fieldKey]] ?? '' }}">
                                                        <i class="bi bi-chevron-down" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);color:#1B4F72;font-size:11px;pointer-events:none;"></i>
                                                    </div>
                                                </td>
                                            @endforeach
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach

                </div>
                <div class="modal-footer" style="background:#f9fafb;">
                    <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: PG --}}
    <div class="modal fade" id="modalPG" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background:#1B4F72;">
                    <h5 class="modal-title fw-semibold text-white">
                        <i class="bi bi-eyeglasses me-2"></i>PG
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    @php
$pgMasterOpts = [
    'sph_cyl' => collect($masters['sph_cyl'])->pluck('value')->filter()->values()->all(),
    'axis' => collect($masters['axis'])->map(fn($o) => ltrim(trim($o->value), '+-'))->reject(fn($v) => $v === '')->unique()->values()->all(),
    'vn' => collect($masters['vn'])->pluck('value')->filter()->values()->all(),
    'nrvn' => collect($masters['nrvn'])->pluck('value')->filter()->values()->all(),
];
                    @endphp

                    @foreach(['re' => 'Right Eye (RE)', 'le' => 'Left Eye (LE)'] as $eye => $eyeLabel)
                        @php
    $pgRows = [
        'DISTANCE' => [
            'sph' => ['key' => 'ds', 'val' => $pg[$eye]['ds'] ?? ''],
            'cyl' => ['key' => 'dc', 'val' => $pg[$eye]['dc'] ?? ''],
            'ax' => ['key' => 'ax', 'val' => $pg[$eye]['ax'] ?? ''],
            'vn' => ['key' => 'vn', 'val' => $pg[$eye]['vn'] ?? '', 'master' => 'vn'],
        ],
        'NEAR' => [
            'sph' => ['key' => 'ns', 'val' => $pg[$eye]['ns'] ?? ''],
            'cyl' => ['key' => 'nc', 'val' => $pg[$eye]['nc'] ?? ''],
            'ax' => ['key' => 'na', 'val' => $pg[$eye]['na'] ?? ''],
            'vn' => ['key' => 'near_vn', 'val' => $pg[$eye]['near_vn'] ?? '', 'master' => 'nrvn'],
        ],
    ];
                        @endphp
                        <div class="mb-4 rounded-3 overflow-hidden" style="border:1px solid #dde3ea;box-shadow:0 1px 4px rgba(0,0,0,.07);">
                            <div class="d-flex align-items-center gap-2 px-3 py-2" style="background:#1B4F72;">
                                <i class="bi bi-eye-fill text-white"></i>
                                <span class="fw-semibold text-white" style="font-size:14px;">{{ $eyeLabel }}</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0" style="font-size:13px;">
                                    <thead style="background:#f0f4f8;">
                                        <tr>
                                            <th style="width:90px;border-bottom:2px solid #1B4F72;"></th>
                                            <th class="text-center" style="min-width:160px;font-weight:700;font-size:12px;letter-spacing:.06em;color:#1B4F72;border-bottom:2px solid #1B4F72;">SPH</th>
                                            <th class="text-center" style="min-width:160px;font-weight:700;font-size:12px;letter-spacing:.06em;color:#1B4F72;border-bottom:2px solid #1B4F72;">CYL</th>
                                            <th class="text-center" style="min-width:110px;font-weight:700;font-size:12px;letter-spacing:.06em;color:#1B4F72;border-bottom:2px solid #1B4F72;">Axis</th>
                                            <th class="text-center" style="min-width:130px;font-weight:700;font-size:12px;letter-spacing:.06em;color:#1B4F72;border-bottom:2px solid #1B4F72;">VN C GL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pgRows as $rowLabel => $rf)
                                            <tr style="background:white;">
                                                <td class="text-center fw-bold" style="font-size:11px;color:#64748b;letter-spacing:.06em;background:#fafbfc;">{{ $rowLabel }}</td>
                                                {{-- SPH --}}
                                                <td class="text-center py-2">
                                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                                            <button type="button" class="btn btn-danger pg-pick-btn" data-sign="neg" style="width:32px;height:32px;padding:0;font-size:20px;line-height:1;border-radius:6px;font-weight:300;">−</button>
                                                        <div class="pg-select-wrap" style="width:88px;">
                                                            <input type="text" class="form-control form-control-sm pg-inp text-center fw-semibold" style="font-size:13px;border-color:#1B4F72;cursor:pointer;" placeholder="0.00" autocomplete="off" data-master="sph_cyl" data-no-drop="1" readonly value="{{ $rf['sph']['val'] }}">
                                                            <input type="hidden" name="exam_data[pg][{{ $eye }}][{{ $rf['sph']['key'] }}]" value="{{ $rf['sph']['val'] }}">
                                                        </div>
                                                        <button type="button" class="btn btn-success pg-pick-btn" data-sign="pos" style="width:32px;height:32px;padding:0;font-size:20px;line-height:1;border-radius:6px;font-weight:300;">+</button>
                                                    </div>
                                                </td>
                                                {{-- CYL --}}
                                                <td class="text-center py-2">
                                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                                        <button type="button" class="btn btn-danger pg-pick-btn" data-sign="neg" style="width:32px;height:32px;padding:0;font-size:20px;line-height:1;border-radius:6px;font-weight:300;">−</button>
                                                        <div class="pg-select-wrap" style="width:88px;">
                                                            <input type="text" class="form-control form-control-sm pg-inp text-center fw-semibold" style="font-size:13px;border-color:#1B4F72;cursor:pointer;" placeholder="0.00" autocomplete="off" data-master="sph_cyl" data-no-drop="1" readonly value="{{ $rf['cyl']['val'] }}">
                                                            <input type="hidden" name="exam_data[pg][{{ $eye }}][{{ $rf['cyl']['key'] }}]" value="{{ $rf['cyl']['val'] }}">
                                                        </div>
                                                        <button type="button" class="btn btn-success pg-pick-btn" data-sign="pos" style="width:32px;height:32px;padding:0;font-size:20px;line-height:1;border-radius:6px;font-weight:300;">+</button>
                                                    </div>
                                                </td>
                                                {{-- AXIS --}}
                                                <td class="text-center py-2">
                                                    <div class="pg-select-wrap" style="max-width:90px;margin:auto;">
                                                        <input type="text" class="form-control form-control-sm axis-disp text-center fw-semibold" style="font-size:13px;border-color:#1B4F72;cursor:pointer;" placeholder="0°" autocomplete="off" data-axis-picker="1" readonly value="{{ $rf['ax']['val'] }}">
                                                        <input type="hidden" name="exam_data[pg][{{ $eye }}][{{ $rf['ax']['key'] }}]" value="{{ $rf['ax']['val'] }}">
                                                        <i class="bi bi-chevron-down pg-inp-chevron"></i>
                                                    </div>
                                                </td>
                                                {{-- VN C GL --}}
                                                <td class="text-center py-2">
                                                    <div class="pg-select-wrap" style="max-width:115px;margin:auto;">
                                                        <input type="text" class="form-control form-control-sm pg-inp text-center" style="font-size:12px;border-color:#1B4F72;" placeholder="Select VN" autocomplete="off" data-master="{{ $rf['vn']['master'] }}" value="{{ $rf['vn']['val'] }}">
                                                        <input type="hidden" name="exam_data[pg][{{ $eye }}][{{ $rf['vn']['key'] }}]" value="{{ $rf['vn']['val'] }}">
                                                        <i class="bi bi-chevron-down pg-inp-chevron"></i>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach

                </div>
                <div class="modal-footer" style="background:#f9fafb;">
                    <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">
                    Save
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: PG Value Picker --}}
    <div class="modal fade" id="modalPGPicker" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content" style="border:none;border-radius:12px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.25);">
                <div class="modal-header py-2 px-4" style="background:#1B4F72;">
                    <h6 class="modal-title text-white fw-bold mb-0 fs-6" id="pgPickerTitle">Select Value</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" style="background:#f8fafc;">
                    <div id="pgPickerGrid" style="display:grid;grid-template-columns:repeat(8,1fr);gap:10px;max-height:340px;overflow-y:auto;" class="mb-4"></div>
                    <div class="d-flex align-items-center gap-3 px-4 py-3 rounded-3" style="background:white;border:1px solid #dde3ea;box-shadow:0 1px 4px rgba(0,0,0,.05);">
                        <div class="d-flex flex-column align-items-center gap-1" style="min-width:90px;">
                            <span style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.1em;font-weight:600;">Selected</span>
                            <span id="pgPickerCurrent" class="fw-bold px-3 py-1 rounded-2 text-center" style="font-size:18px;color:white;background:#1B4F72;min-width:80px;letter-spacing:.02em;">—</span>
                        </div>
                        <div style="width:1px;height:44px;background:#e2e8f0;"></div>
                        <button type="button" class="btn btn-sm btn-danger d-flex align-items-center gap-1" id="pgPickerClear" style="border-radius:8px;font-size:12px;font-weight:600;padding:6px 14px;">
                             Clear
                        </button>
                        <div class="ms-auto d-flex align-items-center gap-2">
                            <div class="input-group" style="width:200px;">
                                <span class="input-group-text" style="background:#f0f4f8;border-color:#1B4F72;color:#1B4F72;font-size:11px;font-weight:700;letter-spacing:.05em;">CUSTOM</span>
                                <input type="number" id="pgPickerManual" class="form-control" step="0.25" style="border-color:#1B4F72;font-size:13px;font-weight:600;" placeholder="e.g. −3.75">
                            </div>
                            <button type="button" class="btn btn-primary d-flex align-items-center gap-1 px-3" id="pgPickerSaveManual" style="background:#1B4F72;border-color:#1B4F72;border-radius:8px;font-weight:600;font-size:13px;white-space:nowrap;">
                                 Apply
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Axis Picker --}}
    <div class="modal fade" id="modalAxisPicker" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border:none;border-radius:12px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.25);">
                <div class="modal-header py-2 px-4" style="background:#1B4F72;">
                    <h6 class="modal-title text-white fw-bold mb-0 fs-6 text-uppercase">AXIS</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3" style="background:#fff;">
                    <div id="axisPickerGrid" style="display:grid;grid-template-columns:repeat(6,1fr);gap:8px;padding:4px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: ST --}}
    <div class="modal fade" id="modalST" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background:#1B4F72;">
                    <h5 class="modal-title fw-semibold text-white">
                        <i class="bi bi-binoculars me-2"></i>ST
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">

                    @foreach(['re' => 'Right Eye (RE)', 'le' => 'Left Eye (LE)'] as $eye => $eyeLabel)
                        @php
    $stRows = [
        'DISTANCE' => [
            'sph' => ['key' => 'ds', 'val' => $st[$eye]['ds'] ?? ''],
            'cyl' => ['key' => 'dc', 'val' => $st[$eye]['dc'] ?? ''],
            'ax' => ['key' => 'ax', 'val' => $st[$eye]['ax'] ?? ''],
            'vn' => ['key' => 'vn', 'val' => $st[$eye]['vn'] ?? '', 'master' => 'vn'],
        ],
        'NEAR' => [
            'sph' => ['key' => 'ns', 'val' => $st[$eye]['ns'] ?? ''],
            'cyl' => ['key' => 'nc', 'val' => $st[$eye]['nc'] ?? ''],
            'ax' => ['key' => 'na', 'val' => $st[$eye]['na'] ?? ''],
            'vn' => ['key' => 'near_vn', 'val' => $st[$eye]['near_vn'] ?? '', 'master' => 'nrvn'],
        ],
    ];
                        @endphp
                        <div class="mb-4 rounded-3 overflow-hidden" style="border:1px solid #dde3ea;box-shadow:0 1px 4px rgba(0,0,0,.07);">
                            <div class="d-flex align-items-center gap-2 px-3 py-2" style="background:#1B4F72;">
                                <i class="bi bi-eye-fill text-white"></i>
                                <span class="fw-semibold text-white" style="font-size:14px;">{{ $eyeLabel }}</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0" style="font-size:13px;">
                                    <thead style="background:#f0f4f8;">
                                        <tr>
                                            <th style="width:90px;border-bottom:2px solid #1B4F72;"></th>
                                            <th class="text-center" style="min-width:160px;font-weight:700;font-size:12px;letter-spacing:.06em;color:#1B4F72;border-bottom:2px solid #1B4F72;">SPH</th>
                                            <th class="text-center" style="min-width:160px;font-weight:700;font-size:12px;letter-spacing:.06em;color:#1B4F72;border-bottom:2px solid #1B4F72;">CYL</th>
                                            <th class="text-center" style="min-width:110px;font-weight:700;font-size:12px;letter-spacing:.06em;color:#1B4F72;border-bottom:2px solid #1B4F72;">Axis</th>
                                            <th class="text-center" style="min-width:130px;font-weight:700;font-size:12px;letter-spacing:.06em;color:#1B4F72;border-bottom:2px solid #1B4F72;">VN C ST</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($stRows as $rowLabel => $rf)
                                            <tr style="background:white;">
                                                <td class="text-center fw-bold" style="font-size:11px;color:#64748b;letter-spacing:.06em;background:#fafbfc;">{{ $rowLabel }}</td>
                                                {{-- SPH: picker for DISTANCE; ADD picker + NS calc for NEAR --}}
                                                @if($rowLabel === 'DISTANCE')
                                                    <td class="text-center py-2">
                                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                                            <button type="button" class="btn btn-danger pg-pick-btn" data-sign="neg" style="width:32px;height:32px;padding:0;font-size:20px;line-height:1;border-radius:6px;font-weight:300;">−</button>
                                                            <div class="pg-select-wrap" style="width:88px;">
                                                                <input type="text" class="form-control form-control-sm pg-inp text-center fw-semibold" style="font-size:13px;border-color:#1B4F72;cursor:pointer;" placeholder="0.00" autocomplete="off" data-master="sph_cyl" data-no-drop="1" readonly value="{{ $rf['sph']['val'] }}">
                                                                <input type="hidden" name="exam_data[st][{{ $eye }}][{{ $rf['sph']['key'] }}]" value="{{ $rf['sph']['val'] }}">
                                                            </div>
                                                            <button type="button" class="btn btn-success pg-pick-btn" data-sign="pos" style="width:32px;height:32px;padding:0;font-size:20px;line-height:1;border-radius:6px;font-weight:300;">+</button>
                                                        </div>
                                                    </td>
                                                @else
                                                    @php
            $stAddVal = old('exam_data.st.' . $eye . '.add', $st[$eye]['add'] ?? '');
            $stNsVal = old('exam_data.st.' . $eye . '.ns', $st[$eye]['ns'] ?? '');
                                                    @endphp
                                                    <td class="text-center py-2">
                                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                                            @if(!($eye == 're' && $rowLabel == 'NEAR'))
                                                            <button type="button" class="btn btn-danger pg-pick-btn" data-sign="neg" style="width:32px;height:32px;padding:0;font-size:20px;line-height:1;border-radius:6px;font-weight:300;">−</button>
                                                            @endif
                                                            <div class="pg-select-wrap" style="width:88px;">
                                                                <input type="text" class="form-control form-control-sm pg-inp text-center fw-semibold" style="font-size:13px;border-color:#1B4F72;cursor:pointer;" placeholder="0.00" autocomplete="off" data-master="sph_cyl" data-no-drop="1" readonly value="{{ $stNsVal }}">
                                                                <input type="hidden" name="exam_data[st][{{ $eye }}][add]" value="{{ $stAddVal }}">
                                                            </div>
                                                            <button type="button" class="btn btn-success pg-pick-btn" data-sign="pos" style="width:32px;height:32px;padding:0;font-size:20px;line-height:1;border-radius:6px;font-weight:300;">+</button>
                                                        </div>
                                                        <div style="font-size:10px;color:#64748b;margin-top:3px;">ADD: <strong>{{ $stAddVal ?: '—' }}</strong></div>
                                                        <input type="hidden" name="exam_data[st][{{ $eye }}][ns]" value="{{ $stNsVal }}">
                                                    </td>
                                                @endif
                                                {{-- CYL: picker for DISTANCE, read-only mirror for NEAR --}}
                                                @if($rowLabel === 'DISTANCE')
                                                    <td class="text-center py-2">
                                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                                            <button type="button" class="btn btn-danger pg-pick-btn" data-sign="neg" style="width:32px;height:32px;padding:0;font-size:20px;line-height:1;border-radius:6px;font-weight:300;">−</button>
                                                            <div class="pg-select-wrap" style="width:88px;">
                                                                <input type="text" class="form-control form-control-sm pg-inp text-center fw-semibold" style="font-size:13px;border-color:#1B4F72;cursor:pointer;" placeholder="0.00" autocomplete="off" data-master="sph_cyl" data-no-drop="1" readonly value="{{ $rf['cyl']['val'] }}">
                                                                <input type="hidden" name="exam_data[st][{{ $eye }}][{{ $rf['cyl']['key'] }}]" value="{{ $rf['cyl']['val'] }}">
                                                            </div>
                                                            <button type="button" class="btn btn-success pg-pick-btn" data-sign="pos" style="width:32px;height:32px;padding:0;font-size:20px;line-height:1;border-radius:6px;font-weight:300;">+</button>
                                                        </div>
                                                    </td>
                                                @else
                                                    <td class="text-center py-2">
                                                        <div style="width:88px;margin:auto;">
                                                            <input type="text" class="form-control form-control-sm text-center fw-semibold" style="font-size:13px;border-color:#cbd5e1;background:#f8fafc;color:#475569;" placeholder="—" readonly value="{{ $rf['cyl']['val'] }}">
                                                            <input type="hidden" name="exam_data[st][{{ $eye }}][{{ $rf['cyl']['key'] }}]" value="{{ $rf['cyl']['val'] }}">
                                                        </div>
                                                        <div style="font-size:10px;color:#94a3b8;margin-top:3px;">= Distance</div>
                                                    </td>
                                                @endif
                                                {{-- AXIS: dropdown for DISTANCE, read-only mirror for NEAR --}}
                                                @if($rowLabel === 'DISTANCE')
                                                    <td class="text-center py-2">
                                                        <div class="pg-select-wrap" style="max-width:90px;margin:auto;">
                                                            <input type="text" class="form-control form-control-sm axis-disp text-center fw-semibold" style="font-size:13px;border-color:#1B4F72;cursor:pointer;" placeholder="0°" autocomplete="off" data-axis-picker="1" readonly value="{{ $rf['ax']['val'] }}">
                                                            <input type="hidden" name="exam_data[st][{{ $eye }}][{{ $rf['ax']['key'] }}]" value="{{ $rf['ax']['val'] }}">
                                                            <i class="bi bi-chevron-down pg-inp-chevron"></i>
                                                        </div>
                                                    </td>
                                                @else
                                                    <td class="text-center py-2">
                                                        <div style="max-width:90px;margin:auto;">
                                                            <input type="text" class="form-control form-control-sm text-center fw-semibold" style="font-size:13px;border-color:#cbd5e1;background:#f8fafc;color:#475569;" placeholder="—" readonly value="{{ $rf['ax']['val'] }}">
                                                            <input type="hidden" name="exam_data[st][{{ $eye }}][{{ $rf['ax']['key'] }}]" value="{{ $rf['ax']['val'] }}">
                                                        </div>
                                                        <div style="font-size:10px;color:#94a3b8;margin-top:3px;">= Distance</div>
                                                    </td>
                                                @endif
                                                {{-- VN C ST: only for DISTANCE row --}}
                                                @if($rowLabel === 'DISTANCE')
                                                    <td class="text-center py-2">
                                                        <div class="pg-select-wrap" style="max-width:115px;margin:auto;">
                                                            <input type="text" class="form-control form-control-sm pg-inp text-center" style="font-size:12px;border-color:#1B4F72;" placeholder="Select VN" autocomplete="off" data-master="{{ $rf['vn']['master'] }}" value="{{ $rf['vn']['val'] }}">
                                                            <input type="hidden" name="exam_data[st][{{ $eye }}][{{ $rf['vn']['key'] }}]" value="{{ $rf['vn']['val'] }}">
                                                            <i class="bi bi-chevron-down pg-inp-chevron"></i>
                                                        </div>
                                                    </td>
                                                @else
                                                    <td class="text-center align-middle" style="color:#94a3b8;font-size:20px;">—</td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach

                    {{-- Checkboxes --}}
                    <div class="rounded-3 p-3" style="border:1px solid #dde3ea;background:#fafbfc;">
                        <div class="d-flex flex-wrap gap-4">
                            @foreach([
    'bifocal' => 'Bifocal',
    'nd_separate' => 'Near & Distance Separate',
    'progressive' => 'Progressive',
    'computer_uses' => 'Computer Uses',
] as $cbKey => $cbLabel)
                                @php $cbVal = old('exam_data.st.' . $cbKey, $st[$cbKey] ?? false); @endphp
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="exam_data[st][{{ $cbKey }}]" value="1" id="st_{{ $cbKey }}" {{ $cbVal ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="st_{{ $cbKey }}" style="font-size:13px;color:#334155;">{{ $cbLabel }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>
                <div class="modal-footer" style="background:#f9fafb;">
                    <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: NCT --}}
    <div class="modal fade" id="modalNCT" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background:#1B4F72;">
                    <h5 class="modal-title fw-semibold text-white">
                        <i class="bi bi-speedometer2 me-2"></i>NCT
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="rounded-3 overflow-hidden" style="border:1px solid #dde3ea;box-shadow:0 1px 4px rgba(0,0,0,.07);">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0" style="font-size:13px;">
                                <thead style="background:#f0f4f8;">
                                    <tr>
                                        <th style="width:140px;border-bottom:2px solid #1B4F72;"></th>
                                        @foreach(['re' => 'Right Eye (RE)', 'le' => 'Left Eye (LE)'] as $eye => $eyeLabel)
                                            <th class="text-center" style="font-weight:700;font-size:12px;letter-spacing:.06em;color:#1B4F72;border-bottom:2px solid #1B4F72;">
                                                <i class="bi bi-eye-fill me-1"></i>{{ $eyeLabel }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr style="background:white;">
                                        <td style="background:#fafbfc;">
                                            <span class="fw-bold" style="font-size:13px;color:#1e293b;letter-spacing:.02em;">IOP</span>
                                            <div style="font-size:11px;color:#94a3b8;font-weight:500;">mmHg</div>
                                        </td>
                                        @foreach(['re', 'le'] as $eye)
                                            @php $sv = old('exam_data.nct.iop_' . $eye, $nct['iop_' . $eye] ?? ''); @endphp
                                            <td class="text-center py-3">
                                                <div class="nct-select-wrap" style="max-width:160px;margin:auto;">
                                                    <input type="text" class="form-control form-control-sm nct-inp text-center fw-semibold"
                                                        style="border-color:#1B4F72;font-size:14px;"
                                                        placeholder="—" autocomplete="off"
                                                        value="{{ $sv }}">
                                                    <input type="hidden" name="exam_data[nct][iop_{{ $eye }}]" value="{{ $sv }}">
                                                    <i class="bi bi-chevron-down nct-inp-chevron"></i>
                                                </div>
                                                <div style="font-size:10px;color:#94a3b8;margin-top:4px;font-weight:500;">mmHg</div>
                                            </td>
                                        @endforeach
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- IOP indicator badges --}}
                    <div class="d-flex gap-3 mt-3 px-1">
                        <div class="d-flex align-items-center gap-2">
                            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#22c55e;"></span>
                            <span style="font-size:11px;color:#64748b;">Normal: 10–21 mmHg</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#f59e0b;"></span>
                            <span style="font-size:11px;color:#64748b;">Borderline: 22–24 mmHg</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#ef4444;"></span>
                            <span style="font-size:11px;color:#64748b;">High: ≥25 mmHg</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background:#f9fafb;">
                    <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: O/E --}}
    <div class="modal fade" id="modalOE" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background:#1B4F72;">
                    <h5 class="modal-title fw-semibold text-white">
                        <i class="bi bi-clipboard2-pulse me-2"></i>O/E
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    @php
$oeFieldMeta = [
    'sac' => ['label' => 'SAC', 'full' => 'Sac', 'master' => 'sac', 'fav' => 'sac'],
    'lid' => ['label' => 'LID', 'full' => 'Lid', 'master' => 'lid', 'fav' => 'lid'],
    'conj' => ['label' => 'CONJ', 'full' => 'Conjunctiva', 'master' => 'conj', 'fav' => 'conj'],
    'cornea' => ['label' => 'CORNEA', 'full' => 'Cornea', 'master' => 'cornea', 'fav' => 'cornea'],
    'ac' => ['label' => 'AC', 'full' => 'Anterior Chamber', 'master' => 'ac', 'fav' => 'ac'],
    'iris' => ['label' => 'IRIS', 'full' => 'Iris', 'master' => 'iris', 'fav' => 'iris'],
    'pupil' => ['label' => 'PUPIL', 'full' => 'Pupil', 'master' => 'pupil', 'fav' => 'pupil'],
    'lens' => ['label' => 'LENS', 'full' => 'Lens', 'master' => 'lens_master', 'fav' => 'lens'],
    'em' => ['label' => 'EM', 'full' => 'Extraocular Mov.', 'master' => 'em', 'fav' => 'em'],
    'covertest' => ['label' => 'COVERTEST', 'full' => 'Cover Test', 'master' => 'covertest', 'fav' => 'covertest'],
];
$oeMasterData = [];
foreach ($oeFieldMeta as $meta) {
    if (!isset($oeMasterData[$meta['master']])) {
        $oeMasterData[$meta['master']] = collect($masters[$meta['master']])->map(fn($o) => [
            'id' => $o->id,
            'value' => $o->value,
            'is_favourite' => (bool) ($o->is_favourite ?? false),
        ])->values()->all();
    }
}
                    @endphp
                    <div class="rounded-3 overflow-hidden" style="border:1px solid #dde3ea;box-shadow:0 1px 4px rgba(0,0,0,.07);">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0 oe-table" style="font-size:13px;">
                                <thead>
                                    <tr>
                                        <th style="width:140px;background:#f0f4f8;border-bottom:2px solid #1B4F72;"></th>
                                        <th class="oe-eye-col text-center"><i class="bi bi-eye-fill me-1"></i>Right Eye (RE)</th>
                                        <th class="oe-eye-col text-center"><i class="bi bi-eye-fill me-1"></i>Left Eye (LE)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($oeFieldMeta as $key => $meta)
                                        <tr>
                                            <td class="oe-label-cell">
                                                <span class="fw-bold" style="font-size:13px;color:#1e293b;letter-spacing:.02em;">{{ $meta['label'] }}</span>
                                                <div style="font-size:10px;color:#94a3b8;font-weight:500;margin-top:1px;">{{ $meta['full'] }}</div>
                                            </td>
                                            @foreach(['re' => 'oe-cell-re', 'le' => 'oe-cell-le'] as $eye => $cellCls)
                                                @php $sv = old('exam_data.oe.' . $key . '_' . $eye, $oe[$key . '_' . $eye] ?? ''); @endphp
                                                <td class="{{ $cellCls }} py-2 px-3">
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
                                                        @php $pseudo = old('exam_data.oe.pseudophakia_' . $eye, $oe['pseudophakia_' . $eye] ?? []); @endphp
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
                                            <span class="fw-bold" style="font-size:13px;color:#1e293b;letter-spacing:.02em;">OTHER</span>
                                            <div style="font-size:10px;color:#94a3b8;font-weight:500;margin-top:1px;">Other findings</div>
                                        </td>
                                        @foreach(['re' => ['cls' => 'oe-cell-re', 'ph' => 'Right eye findings...'], 'le' => ['cls' => 'oe-cell-le', 'ph' => 'Left eye findings...']] as $eye => $em)
                                            <td class="{{ $em['cls'] }} py-2 px-3">
                                                <input type="text" name="exam_data[oe][other_{{ $eye }}]"
                                                    value="{{ old('exam_data.oe.other_' . $eye, $oe['other_' . $eye] ?? '') }}"
                                                    class="form-control form-control-sm exam-plain-inp"
                                                    style="border-color:#1B4F72;"
                                                    placeholder="{{ $em['ph'] }}" autocomplete="off">
                                            </td>
                                        @endforeach
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background:#f9fafb;">
                    <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">
                       Done
                    </button>
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
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background:#1B4F72;">
                    <h5 class="modal-title fw-semibold text-white">
                        <i class="bi bi-circle-half me-2"></i>Fundus Examination
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">

                    <div class="row g-3">
                        @foreach(['re' => 'Right Eye (RE)', 'le' => 'Left Eye (LE)'] as $eye => $eyeLabel)
                        <div class="col-md-6">
                            <div class="rounded-3 overflow-hidden h-100" style="border:1px solid #dde3ea;box-shadow:0 1px 4px rgba(0,0,0,.07);">
                                <div class="d-flex align-items-center gap-2 px-3 py-2" style="background:#1B4F72;">
                                    <i class="bi bi-eye-fill text-white"></i>
                                    <span class="fw-semibold text-white" style="font-size:14px;">{{ $eyeLabel }}</span>
                                </div>
                                <div class="p-3">
                                    <div class="mb-3">
                                        <label class="fw-semibold mb-1" style="font-size:11px;letter-spacing:.06em;color:#1B4F72;">DISC <span style="font-weight:400;color:#64748b;text-transform:none;letter-spacing:0;">CDR / Appearance</span></label>
                                        <div class="fundus-dd-wrap" style="position:relative;">
                                            <input type="text" class="form-control form-control-sm fundus-dd-inp"
                                                placeholder="Search or select..." autocomplete="off"
                                                data-dd-type="disc"
                                                value="{{ $fundus['disc_' . $eye] ?? '' }}"
                                                style="padding-right:28px;border-color:#1B4F72;">
                                            <i class="bi bi-chevron-down" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);color:#1B4F72;pointer-events:none;font-size:11px;"></i>
                                            <input type="hidden" name="exam_data[fundus][disc_{{ $eye }}]" value="{{ $fundus['disc_' . $eye] ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-semibold mb-1" style="font-size:11px;letter-spacing:.06em;color:#1B4F72;">FR <span style="font-weight:400;color:#64748b;text-transform:none;letter-spacing:0;">Foveal Reflex</span></label>
                                        <div class="fundus-dd-wrap" style="position:relative;">
                                            <input type="text" class="form-control form-control-sm fundus-dd-inp"
                                                placeholder="Search or select..." autocomplete="off"
                                                data-dd-type="fr"
                                                value="{{ $fundus['fr_' . $eye] ?? '' }}"
                                                style="padding-right:28px;border-color:#1B4F72;">
                                            <i class="bi bi-chevron-down" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);color:#1B4F72;pointer-events:none;font-size:11px;"></i>
                                            <input type="hidden" name="exam_data[fundus][fr_{{ $eye }}]" value="{{ $fundus['fr_' . $eye] ?? '' }}">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="fw-semibold mb-1" style="font-size:11px;letter-spacing:.06em;color:#1B4F72;">COMMENT <span style="font-weight:400;color:#64748b;text-transform:none;letter-spacing:0;">Additional findings</span></label>
                                        <textarea name="exam_data[fundus][comment_{{ $eye }}]"
                                            class="form-control form-control-sm"
                                            rows="3"
                                            placeholder="{{ $eyeLabel }} findings / notes..."
                                            style="resize:none;border-color:#1B4F72;font-size:12px;">{{ old('exam_data.fundus.comment_' . $eye, $fundus['comment_' . $eye] ?? '') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                </div>
                <div class="modal-footer" style="background:#f9fafb;">
                    <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">
                        Done
                    </button>
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
$dxGroupCount = collect($masters['med_groups'])->groupBy('diagnosis_id')->map->count();
$dxAdviceCount = [];
foreach ($masters['advices'] as $_a) {
    foreach ($_a->diagnosis_ids ?? [] as $_dxId) {
        $dxAdviceCount[$_dxId] = ($dxAdviceCount[$_dxId] ?? 0) + 1;
    }
}
                        @endphp
                        @foreach($masters['diagnoses'] as $d)
                            @php $gc = $dxGroupCount[$d->id] ?? 0;
    $ac = $dxAdviceCount[$d->id] ?? 0; @endphp
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
                <div class="modal-header" style="background:#1B4F72;">
                    <h5 class="modal-title fw-semibold text-white">
                        <i class="bi bi-capsule me-2"></i>Medicines
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- Suggested Groups panel (shown when diagnosis linked groups exist) --}}
                    <div id="dxSuggestedGroups" style="display:none;"></div>

                    {{-- Toolbar --}}
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <span class="fw-semibold" style="font-size:13px;color:#1B4F72;"><i class="bi bi-list-ul me-1"></i>Prescription List</span>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <div class="input-group input-group-sm" style="width:auto;">
                                <label class="input-group-text" style="font-size:12px;background:#f0f4f8;color:#1B4F72;font-weight:600;border-color:#1B4F72;">Group</label>
                                <select id="rxGroupSelector" class="form-select form-select-sm" style="min-width:180px;border-color:#1B4F72;">
                                    <option value="">-- Load Group --</option>
                                    @foreach($masters['med_groups'] as $grp)<option value="{{ $grp->id }}">{{ $grp->name }}</option>@endforeach
                                </select>
                            </div>
                            <button type="button" class="btn btn-sm px-3" style="background:#1B4F72;color:white;border:none;border-radius:8px;font-weight:600;" onclick="addMedicineRow()">
                                <i class="bi bi-plus-lg me-1"></i>Add Medicine
                            </button>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0" id="rxTable"
                               style="border:1px solid #dde3ea;border-radius:10px;overflow:hidden;">
                            <thead style="background:#f0f4f8;border-bottom:2px solid #1B4F72;">
                                <tr>
                                    <th style="font-size:12px;color:#1B4F72;font-weight:700;padding:10px 14px;">Medicine Name</th>
                                    <th style="width:130px;font-size:12px;color:#1B4F72;font-weight:700;padding:10px 14px;">Dosage</th>
                                    <th style="width:100px;font-size:12px;color:#1B4F72;font-weight:700;padding:10px 14px;">Days</th>
                                    <th style="width:80px;font-size:12px;color:#1B4F72;font-weight:700;padding:10px 14px;">QTY</th>
                                    <th style="width:160px;font-size:12px;color:#1B4F72;font-weight:700;padding:10px 14px;">Route of Administration</th>
                                    <th style="width:40px;padding:10px 8px;"></th>
                                </tr>
                            </thead>
                            <tbody id="rxBody">
                                @forelse($prescriptions as $i => $rx)
                                    <tr class="rx-row">
                                        <td style="padding:8px 14px;">
                                            <input type="hidden" name="medicines[{{ $i }}][medicine_id]" value="{{ $rx->medicine_id }}" class="med-id-input">
                                            <div class="medicine-search-wrap">
                                                <input type="text" name="medicines[{{ $i }}][name]" class="form-control form-control-sm medicine-search"
                                                       value="{{ $rx->medicine?->brand_name ?: $rx->medicine?->name }}"
                                                       placeholder="Type to search medicine..." autocomplete="off">
                                                <div class="medicine-suggest"></div>
                                            </div>
                                        </td>
                                        <td style="padding:8px 14px;">
                                            <select name="medicines[{{ $i }}][dosage_id]" class="form-select form-select-sm">
                                                <option value="">-</option>
                                                @foreach($masters['dosages'] as $dos)<option value="{{ $dos->id }}" {{ $rx->dosage_id == $dos->id ? 'selected' : '' }}>{{ $dos->dosage }}</option>@endforeach
                                            </select>
                                        </td>
                                        <td style="padding:8px 14px;">
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="medicines[{{ $i }}][duration]" class="form-control" value="{{ $rx->duration ?? '' }}" placeholder="7" min="1">
                                                <span class="input-group-text" style="font-size:11px;background:#f0f4f8;color:#1B4F72;font-weight:600;border-color:#dde3ea;">D</span>
                                            </div>
                                        </td>
                                        <td style="padding:8px 14px;"><input type="number" name="medicines[{{ $i }}][quantity]" class="form-control form-control-sm" value="{{ $rx->quantity ?? '' }}" placeholder="1" min="1"></td>
                                        <td style="padding:8px 14px;">
                                            <select name="medicines[{{ $i }}][route_id]" class="form-select form-select-sm">
                                                <option value="">-</option>
                                                @foreach($masters['routes'] as $rt)<option value="{{ $rt->id }}" {{ ($rx->route_id ?? '') == $rt->id ? 'selected' : '' }}>{{ $rt->name }}</option>@endforeach
                                            </select>
                                        </td>
                                        <td class="text-center" style="padding:8px;">
                                            <button type="button" class="btn btn-sm btn-outline-danger" style="padding:2px 7px;border-radius:6px;" onclick="this.closest('tr').remove(); updateLivePreview();">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="rxEmpty">
                                        <td colspan="6" class="text-center py-5 text-muted" style="font-size:13px;">
                                            <i class="bi bi-capsule me-1" style="color:#1B4F72;opacity:.4;font-size:20px;display:block;margin-bottom:6px;"></i>
                                            No medicines added yet — click <strong>+ Add Medicine</strong>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer" style="background:#f8fafc;border-top:1px solid #dde3ea;">
                    <button type="button" class="btn px-4 fw-semibold" style="background:#1B4F72;color:white;border-radius:8px;" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Advice --}}
    @php
$favAdvices = collect($masters['advices'] ?? [])->filter(fn($a) => $a->is_favourite && ($a->advice ?? ''))->values();
$nonFavAdvices = collect($masters['advices'] ?? [])->filter(fn($a) => !$a->is_favourite && ($a->advice ?? ''))->values();
$allAdvices = collect($masters['advices'] ?? [])->filter(fn($a) => ($a->advice ?? ''))->values();
    @endphp
    <div class="modal fade" id="modalAdvice" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background:#1B4F72;">
                    <h5 class="modal-title fw-semibold text-white">
                        <i class="bi bi-chat-square-text me-2"></i>Clinical Advice &amp; Instructions
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3 d-flex flex-column gap-3">

                    {{-- 1. Diagnosis-linked advices (JS rendered) --}}
                    <div id="dxSuggestedAdvices"></div>

                    {{-- 2. Favourites + More in one clean pill row --}}
                    <div class="rounded-3 p-3" style="background:#fafbfc;border:1px solid #e2e8f0;">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-lightning-charge-fill" style="color:#1B4F72;font-size:12px;"></i>
                            <span class="fw-semibold" style="font-size:11px;color:#374151;text-transform:uppercase;letter-spacing:.07em;">Quick Add</span>
                            <span class="text-muted" style="font-size:11px;">— click any pill to append</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center" id="adviceChipsWrap">
                            {{-- Favourite pills --}}
                            @foreach($favAdvices as $adv)
                                <button type="button" class="advice-quick-btn"
                                        style="font-size:12px;padding:4px 12px;border-radius:20px;border:1px solid #1B4F72;background:white;color:#1B4F72;font-weight:500;cursor:pointer;line-height:1.5;"
                                        data-advice="{{ $adv->advice }}">
                                    <i class="bi bi-star-fill me-1" style="font-size:9px;color:#f59e0b;"></i>{{ $adv->advice }}
                                </button>
                            @endforeach

                            {{-- More dropdown with search + add new --}}
                            <div class="dropdown" id="adviceMoreDropdown">
                                <button class="btn btn-sm dropdown-toggle" type="button"
                                        id="adviceMoreBtn"
                                        data-bs-toggle="dropdown"
                                        data-bs-auto-close="outside"
                                        aria-expanded="false"
                                        style="font-size:12px;border-radius:20px;border:1px solid #cbd5e1;background:white;color:#374151;padding:4px 12px;">
                                    <i class="bi bi-grid me-1"></i>More
                                </button>
                                <div class="dropdown-menu p-0 shadow" style="min-width:320px;border-radius:12px;overflow:hidden;border:1px solid #dde3ea;">
                                    {{-- Search + Add bar --}}
                                    <div class="p-2" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                                        <div class="d-flex gap-2 align-items-center">
                                            <div style="position:relative;flex:1;">
                                                <i class="bi bi-search" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px;pointer-events:none;"></i>
                                                <input type="text" id="newAdviceInput"
                                                       class="form-control form-control-sm"
                                                       placeholder="Search or type new advice..."
                                                       maxlength="255"
                                                       autocomplete="off"
                                                       oninput="adviceMoreFilter(this.value)"
                                                       style="padding-left:28px;padding-right:8px;border-color:#dde3ea;border-radius:8px;font-size:13px;background:white;">
                                            </div>
                                            <button type="button" id="newAdviceBtn"
                                                    class="btn btn-sm"
                                                    style="padding:5px 12px;border-radius:8px;background:#1B4F72;border:none;color:white;font-size:13px;white-space:nowrap;font-weight:600;"
                                                    title="Add as new advice">
                                                + Add
                                            </button>
                                        </div>
                                    </div>
                                    {{-- List --}}
                                    <ul id="adviceMoreList" style="max-height:240px;overflow-y:auto;margin:0;padding:6px 0;list-style:none;">
                                        @foreach($allAdvices as $adv)
                                            <li class="advice-more-item" data-advice-text="{{ strtolower($adv->advice) }}"
                                                style="display:flex;align-items:center;padding:2px 10px 2px 12px;transition:background .1s;">
                                                <button type="button" class="advice-quick-btn"
                                                        data-advice="{{ $adv->advice }}"
                                                        style="flex:1;font-size:13px;padding:6px 4px;text-align:left;border:none;background:transparent;color:#1e293b;cursor:pointer;border-radius:6px;line-height:1.4;">
                                                    {{ $adv->advice }}
                                                </button>
                                                <button type="button" class="advice-fav-btn"
                                                        data-id="{{ $adv->id }}"
                                                        data-fav="{{ $adv->is_favourite ? '1' : '0' }}"
                                                        title="{{ $adv->is_favourite ? 'Remove from favourites' : 'Mark as favourite' }}"
                                                        style="border:none;background:transparent;padding:4px 4px;cursor:pointer;line-height:1;flex-shrink:0;opacity:{{ $adv->is_favourite ? '1' : '0.3' }};transition:opacity .15s;">
                                                    <i class="bi {{ $adv->is_favourite ? 'bi-star-fill' : 'bi-star' }}"
                                                       style="font-size:14px;color:{{ $adv->is_favourite ? '#f59e0b' : '#94a3b8' }};"></i>
                                                </button>
                                            </li>
                                        @endforeach
                                        <li id="adviceNoResult" style="display:none;padding:10px 14px;font-size:12px;color:#94a3b8;text-align:center;">
                                            <i class="bi bi-search me-1"></i>No match — click <strong>+ Add</strong> to create
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 3. Advice Textarea --}}
                    <div class="rounded-3 p-3" style="border:1px solid #dde3ea;background:white;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="fw-semibold mb-0" style="font-size:13px;color:#1B4F72;">
                                <i class="bi bi-pencil-square me-1"></i>Advice Text
                            </label>
                            <span id="adviceCharCount" style="font-size:11px;color:#64748b;background:#f0f4f8;padding:2px 8px;border-radius:10px;">0 / 2000</span>
                        </div>
                        <textarea name="exam_data[advice]" id="advice_textarea" class="form-control" rows="8"
                                  placeholder="Clinical advice, post-operative care, follow-up instructions..."
                                  maxlength="2000"
                                  style="resize:vertical;font-size:13px;border-color:#e2e8f0;border-radius:8px;line-height:1.7;">{{ old('exam_data.advice', $ed['advice'] ?? '') }}</textarea>
                    </div>

                </div>
                <div class="modal-footer justify-content-between" style="background:#f9fafb;">
                    <button type="button" class="btn btn-sm btn-danger"
                            onclick="document.getElementById('advice_textarea').value=''; document.getElementById('adviceCharCount').textContent='0 / 2000'; if(typeof updateLivePreview==='function') updateLivePreview();">
                        Clear
                    </button>
                    <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal"
                            style="background:#1B4F72;border-color:#1B4F72;">
                       Done
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<datalist id="sph_cyl_list">
    @php
$uniqueSphCyl = collect($masters['sph_cyl'])
    ->map(fn($o) => ltrim(trim($o->value), '+-'))
    ->reject(fn($v) => $v === '')
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

{{-- MODAL: Exam Save Confirmation --}}
<!-- <div class="modal fade" id="modalExamConfirm" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content" style="border:none;border-radius:14px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.2);">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width:48px;height:48px;background:#e8f4fd;flex-shrink:0;">
                        <i class="bi bi-clipboard2-check" style="font-size:22px;color:#1B4F72;"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color:#1B4F72;">Save Exam?</h5>
                        <p class="text-muted mb-0" style="font-size:13px;">Please review before confirming.</p>
                    </div>
                </div>
            </div>
            <div class="modal-body px-4 py-3">
                <p class="mb-0" style="font-size:13px;color:#64748b;">Are you sure you want to save this examination? Once saved, the record will be updated.</p>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0 gap-2">
                <button type="button" class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal" style="border-radius:8px;font-weight:600;">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-success flex-fill" id="examConfirmYes" style="border-radius:8px;font-weight:600;background:#1B4F72;border-color:#1B4F72;">
                    <i class="bi bi-check-lg me-1"></i>Yes, Save
                </button>
            </div>
        </div>
    </div>
</div> -->

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
    const dosagesJson = @json($masters['dosages']->pluck('dosage', 'id'));
    const durationsJson = @json($masters['durations']->pluck('duration')->values());

    function syncComplaintDuration() {
        const numEl = document.getElementById('cc_since_number');
        const unitEl = document.getElementById('cc_since_unit');
        const hiddenEl = document.getElementById('cc_since_hidden');
        const n = (numEl?.value || '').trim();
        hiddenEl.value = n ? (n + ' ' + unitEl.value) : '';
    }

    // All medicines pre-loaded — instant search, no AJAX
    @php
$medicinesForJs = $masters['medicines']->map(fn($m) => [
    'id' => $m->id,
    'name' => $m->name ?? '',
    'brand_name' => $m->brand_name ?? '',
    'dosage_id' => $m->dosage_id,
    'dosage_label' => $m->dosage?->dosage ?? '',
    'duration' => $m->duration ?? '',
    'qty' => $m->qty ?? '',
])->values();
    @endphp
    const allMedicinesData = @json($medicinesForJs);

    function attachMedicineSearch(wrap) {
        if (!wrap) return;
        const input   = wrap.querySelector('.medicine-search');
        const suggest = wrap.querySelector('.medicine-suggest');
        if (!input || !suggest) return;
        const tr     = wrap.closest('tr');
        const hidden = tr?.querySelector('.med-id-input');

        function positionSuggest() {
            const rect = input.getBoundingClientRect();
            const vh   = window.innerHeight;
            const w    = Math.max(rect.width, 280);
            suggest.style.width = w + 'px';
            suggest.style.left  = rect.left + 'px';
            const spaceBelow = vh - rect.bottom - 4;
            if (spaceBelow >= 120) {
                suggest.style.top       = (rect.bottom + 4) + 'px';
                suggest.style.bottom    = '';
                suggest.style.maxHeight = Math.min(260, spaceBelow - 8) + 'px';
            } else {
                suggest.style.bottom    = (vh - rect.top + 4) + 'px';
                suggest.style.top       = '';
                suggest.style.maxHeight = Math.min(260, rect.top - 8) + 'px';
            }
        }

        function renderSuggest(items) {
            if (!items.length) { suggest.style.display = 'none'; return; }
            suggest.innerHTML = items.slice(0, 40).map(function (m) {
                const brand   = (m.brand_name || '').trim();
                const generic = (m.name || '').trim();
                const display = brand || generic;
                const sub     = brand && generic && generic !== brand ? generic : '';
                const badge   = m.dosage_label ? '<span style="font-size:10px;background:#e0f2fe;color:#0369a1;border-radius:4px;padding:1px 5px;margin-left:6px;">' + m.dosage_label + '</span>' : '';
                const meta    = [m.duration, m.qty ? 'Qty:'+m.qty : ''].filter(Boolean).join(' · ');
                return '<div class="med-opt" data-id="' + m.id +
                       '" data-name="' + display.replace(/"/g, '&quot;') +
                       '" data-dosage-id="' + (m.dosage_id || '') +
                       '" data-duration="' + (m.duration || '') +
                       '" data-qty="' + (m.qty || '') + '">' +
                       '<div class="med-opt-brand">' + display + badge + '</div>' +
                       (sub  ? '<div class="med-opt-generic">' + sub + '</div>' : '') +
                       (meta ? '<div class="med-opt-generic" style="color:#94a3b8;">' + meta + '</div>' : '') +
                       '</div>';
            }).join('');
            positionSuggest();
            suggest.style.display = 'block';
            suggest.querySelectorAll('.med-opt').forEach(function (opt) {
                opt.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    input.value = opt.dataset.name;
                    if (hidden) hidden.value = opt.dataset.id;
                    // Autofill dosage
                    var dosageSel = tr?.querySelector('select[name*="[dosage_id]"]');
                    if (dosageSel && opt.dataset.dosageId) dosageSel.value = opt.dataset.dosageId;
                    // Autofill duration (extract number from string like "7 Days" → 7)
                    var durInput = tr?.querySelector('input[name*="[duration]"]');
                    if (durInput && opt.dataset.duration) {
                        var durNum = parseInt(opt.dataset.duration, 10);
                        if (!isNaN(durNum)) durInput.value = durNum;
                    }
                    // Autofill qty
                    var qtyInput = tr?.querySelector('input[name*="[quantity]"]');
                    if (qtyInput && opt.dataset.qty) qtyInput.value = opt.dataset.qty;
                    suggest.style.display = 'none';
                    if (typeof updateLivePreview === 'function') updateLivePreview();
                    if (typeof checkProgress === 'function') checkProgress();
                });
            });
        }

        // Show on focus — display all medicines immediately
        input.addEventListener('focus', function () {
            if (hidden) hidden.value = '';
            renderSuggest(allMedicinesData);
        });

        // Filter on type
        input.addEventListener('input', function () {
            if (hidden) hidden.value = '';
            const q = this.value.trim().toLowerCase();
            if (!q) { renderSuggest(allMedicinesData); return; }
            const filtered = allMedicinesData.filter(function (m) {
                return (m.name || '').toLowerCase().includes(q) ||
                       (m.brand_name || '').toLowerCase().includes(q);
            });
            renderSuggest(filtered);
        });

        input.addEventListener('blur', function () {
            setTimeout(function () { suggest.style.display = 'none'; }, 200);
        });

        // Close on Escape
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { suggest.style.display = 'none'; }
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
            <td style="padding:8px 14px;">
                <input type="hidden" name="medicines[${idx}][medicine_id]" value="${medId}" class="med-id-input">
                <div class="medicine-search-wrap">
                    <input type="text" name="medicines[${idx}][name]" class="form-control form-control-sm medicine-search" placeholder="Type to search medicine..." autocomplete="off" value="${medName}">
                    <div class="medicine-suggest"></div>
                </div>
            </td>
            <td style="padding:8px 14px;"><select name="medicines[${idx}][dosage_id]" class="form-select form-select-sm"><option value="">-</option>${dosageOptions}</select></td>
            <td style="padding:8px 14px;"><div class="input-group input-group-sm"><input type="number" name="medicines[${idx}][duration]" class="form-control" value="${duration}" placeholder="7" min="1"><span class="input-group-text" style="font-size:11px;background:#f0f4f8;color:#1B4F72;font-weight:600;border-color:#dde3ea;">D</span></div></td>
            <td style="padding:8px 14px;"><input type="number" name="medicines[${idx}][quantity]" class="form-control form-control-sm" value="${quantity}" placeholder="1" min="1"></td>
            <td style="padding:8px 14px;"><select name="medicines[${idx}][route_id]" class="form-select form-select-sm"><option value="">-</option>${routeOptions}</select></td>
            <td class="text-center" style="padding:8px;"><button type="button" class="btn btn-sm btn-outline-danger" style="padding:2px 7px;border-radius:6px;" onclick="this.closest('tr').remove(); checkProgress(); updateLivePreview();"><i class="bi bi-x-lg"></i></button></td>
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
        setState('btn-pg',        hasValue('input[name="exam_data[pg][re][ds]"]'));
        setState('btn-st',        hasValue('input[name="exam_data[st][re][ds]"]'));
        setState('btn-nct',       hasValue('input[name="exam_data[nct][iop_re]"]'));
        setState('btn-oe',        hasValue('input[name="exam_data[oe][lid_re]"]'));
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

        const formatLensOe = (eye) => {
            const base = val(`exam_data[oe][lens_${eye}]`);
            const type = val(`exam_data[oe][pseudophakia_${eye}][operation_type]`);
            const exp  = val(`exam_data[oe][pseudophakia_${eye}][operation_expense]`);
            const hosp = val(`exam_data[oe][pseudophakia_${eye}][hospital_name]`);
            if (base === '-') { return '-'; }
            const extras = [type, exp !== '-' ? '₹' + exp : '', hosp].filter(v => v && v !== '-');
            return extras.length ? `${base} (${extras.join(', ')})` : base;
        };

        // Build C/O row data
        const coRows = Array.from(document.querySelectorAll('#coBody .co-row'));
        const coData = coRows.map(row => ({
            complaint : row.querySelector('[name*="[complaint]"]')?.value?.trim() || '',
            since     : row.querySelector('[name*="[since]"]')?.value || '',
            unit      : row.querySelector('[name*="[unit]"]')?.value || '',
            eye       : row.querySelector('[name*="[eye]"]')?.value || '',
            comment   : row.querySelector('[name*="[comment]"]')?.value?.trim() || '',
        })).filter(d => d.complaint);

        // Build K/C/O row data
        const kcoRows = Array.from(document.querySelectorAll('#kcoBody .kco-row'));
        const kcoData = kcoRows.map(row => ({
            condition : row.querySelector('[name*="[condition]"]')?.value?.trim() || '',
            since     : row.querySelector('[name*="[since]"]')?.value || '',
            unit      : row.querySelector('[name*="[unit]"]')?.value || '',
            comment   : row.querySelector('[name*="[comment]"]')?.value?.trim() || '',
        })).filter(d => d.condition);

        // Build H/O pill list
        const hnoVal  = document.getElementById('hnoHidden')?.value?.trim() || '';
        const hnoList = hnoVal ? hnoVal.split(',').map(s => s.trim()).filter(Boolean) : [];

        // V-notation block helper
        const makeVn = (label, re, le) =>
            `<div class="d-inline-flex align-items-center me-3 mb-1">` +
            `<span class="fw-bold me-1" style="font-size:11px;">${label}</span>` +
            `<span class="fw-light mx-1" style="font-size:1.4rem;line-height:0.5;">&lt;</span>` +
            `<div class="d-flex flex-column" style="font-size:11px;line-height:1.3;">` +
            `<span>${re}</span><span>${le}</span></div></div>`;

        const vnRe  = val('exam_data[vision][vn_re]');
        const vnLe  = val('exam_data[vision][vn_le]');
        const phRe  = val('exam_data[vision][pnvn_re]');
        const phLe  = val('exam_data[vision][pnvn_le]');
        const nrRe  = val('exam_data[vision][nrvn_re]');
        const nrLe  = val('exam_data[vision][nrvn_le]');
        const iopRe = val('exam_data[nct][iop_re]');
        const iopLe = val('exam_data[nct][iop_le]');

        const cvTd   = (t) => `<td class="text-center" style="font-size:10px;padding:2px 5px;">${t || '—'}</td>`;
        const pill   = (t) => `<span style="background:#eef4f9;color:#1B4F72;padding:1px 8px;border-radius:8px;margin-right:3px;font-size:10px;">${t}</span>`;
        const cvTable = (title, cols, rows) =>
            `<table class="table table-sm table-bordered mb-2" style="font-size:10px;">` +
            `<thead>` +
            `<tr><th colspan="${cols.length}" class="text-center" style="background:#1B4F72;color:#fff;font-size:10px;padding:2px;letter-spacing:.06em;border-color:#1B4F72;">${title}</th></tr>` +
            `<tr style="background:#eef4f9;">${cols.map(c => `<th class="text-center" style="color:#1B4F72;font-size:10px;padding:2px 5px;font-weight:600;">${c}</th>`).join('')}</tr>` +
            `</thead><tbody>${rows}</tbody></table>`;

        let historyHtml = '';

        // C/O table
        historyHtml += cvTable('C/O', ['Complaint', 'Since', 'Eye', 'Comment'],
            coData.length
                ? coData.map(d => `<tr>${cvTd(d.complaint)}${cvTd(d.since ? d.since+' '+d.unit : '')}${cvTd(d.eye)}${cvTd(d.comment)}</tr>`).join('')
                : `<tr><td colspan="4" class="text-center" style="font-size:10px;padding:3px;color:#94a3b8;">—</td></tr>`
        );

        // K/C/O table
        if (kcoData.length) {
            historyHtml += cvTable('K/C/O', ['Condition', 'Since', 'Comment'],
                kcoData.map(d => `<tr>${cvTd(d.condition)}${cvTd(d.since ? d.since+' '+d.unit : '')}${cvTd(d.comment)}</tr>`).join('')
            );
        }

        // H/O pills
        if (hnoList.length) {
            historyHtml +=
                `<div style="font-size:10px;font-weight:700;color:#1B4F72;letter-spacing:.06em;margin-bottom:3px;">H/O</div>` +
                `<div style="margin-bottom:5px;">${hnoList.map(pill).join('')}</div>`;
        }

        // Vision data
        historyHtml +=
            `<div class="d-flex flex-wrap align-items-center" style="border-top:1px solid #dde3ea;padding-top:4px;margin-top:2px;">` +
            makeVn('Vn', vnRe, vnLe) +
            makeVn('PH', phRe, phLe) +
            makeVn('NrVn', nrRe, nrLe) +
            `<div class="d-inline-flex align-items-center me-2 mb-1" style="font-size:11px;"><strong>IOP:</strong>&nbsp;${iopRe}/${iopLe}</div>` +
            `</div>`;
        document.getElementById('canvas_history').innerHTML = historyHtml;

        // BOX 1 lower: PG table (navy theme)
        const pgCell  = (v) => `<td class="text-center" style="padding:2px;">${v}</td>`;
        const pgSubTh = (t) => `<th class="text-center" style="color:#1B4F72;font-size:10px;padding:2px;">${t}</th>`;
        const visionHtml =
            `<table class="table table-sm table-bordered mb-0" style="font-size:11px;">` +
            `<thead>` +
            `<tr>` +
              `<th style="background:#1B4F72;color:#fff;width:22px;padding:2px;border-color:#1B4F72;"></th>` +
              `<th colspan="4" class="text-center" style="background:#1B4F72;color:#fff;font-size:10px;padding:2px;letter-spacing:.06em;border-color:#1B4F72;">RIGHT EYE (RE)</th>` +
              `<th colspan="4" class="text-center" style="background:#1B4F72;color:#fff;font-size:10px;padding:2px;letter-spacing:.06em;border-color:#1B4F72;">LEFT EYE (LE)</th>` +
            `</tr>` +
            `<tr style="background:#eef4f9;">${pgSubTh('')}${pgSubTh('SPH')}${pgSubTh('CYL')}${pgSubTh('AXIS')}${pgSubTh('VN')}${pgSubTh('SPH')}${pgSubTh('CYL')}${pgSubTh('AXIS')}${pgSubTh('VN')}</tr>` +
            `</thead><tbody>` +
            `<tr><th class="text-center" style="background:#f0f4f8;color:#1B4F72;font-size:10px;font-weight:700;padding:2px;">D</th>` +
              `${pgCell(val('exam_data[pg][re][ds]'))}${pgCell(val('exam_data[pg][re][dc]'))}${pgCell(val('exam_data[pg][re][ax]'))}${pgCell(val('exam_data[pg][re][vn]'))}` +
              `${pgCell(val('exam_data[pg][le][ds]'))}${pgCell(val('exam_data[pg][le][dc]'))}${pgCell(val('exam_data[pg][le][ax]'))}${pgCell(val('exam_data[pg][le][vn]'))}` +
            `</tr>` +
            `<tr><th class="text-center" style="background:#f0f4f8;color:#1B4F72;font-size:10px;font-weight:700;padding:2px;">N</th>` +
              `${pgCell(val('exam_data[pg][re][ns]'))}${pgCell(val('exam_data[pg][re][nc]'))}${pgCell(val('exam_data[pg][re][na]'))}${pgCell(val('exam_data[pg][re][near_vn]'))}` +
              `${pgCell(val('exam_data[pg][le][ns]'))}${pgCell(val('exam_data[pg][le][nc]'))}${pgCell(val('exam_data[pg][le][na]'))}${pgCell(val('exam_data[pg][le][near_vn]'))}` +
            `</tr>` +
            `</tbody></table>`;
        document.getElementById('canvas_vision').innerHTML = visionHtml;

        // BOX 2 top: ST table (navy theme, Near VN removed, ADD + badges below)
        const stBadges = [
            form.querySelector('[name="exam_data[st][bifocal]"]')?.checked       ? 'Bifocal'        : '',
            form.querySelector('[name="exam_data[st][nd_separate]"]')?.checked   ? 'N&D Separate'   : '',
            form.querySelector('[name="exam_data[st][progressive]"]')?.checked   ? 'Progressive'    : '',
            form.querySelector('[name="exam_data[st][computer_uses]"]')?.checked ? 'Computer Uses'  : '',
        ].filter(Boolean);
        const stReAdd = val('exam_data[st][re][add]');
        const stLeAdd = val('exam_data[st][le][add]');
        const stCell  = (v, dim) => `<td class="text-center" style="padding:2px;${dim ? 'color:#94a3b8;' : ''}">${v}</td>`;

        let stHtml =
            `<table class="table table-sm table-bordered mb-1" style="font-size:11px;">` +
            `<thead>` +
            `<tr>` +
              `<th style="background:#1B4F72;color:#fff;width:22px;padding:2px;border-color:#1B4F72;"></th>` +
              `<th colspan="4" class="text-center" style="background:#1B4F72;color:#fff;font-size:10px;padding:2px;letter-spacing:.06em;border-color:#1B4F72;">RIGHT EYE (RE)</th>` +
              `<th colspan="4" class="text-center" style="background:#1B4F72;color:#fff;font-size:10px;padding:2px;letter-spacing:.06em;border-color:#1B4F72;">LEFT EYE (LE)</th>` +
            `</tr>` +
            `<tr style="background:#eef4f9;">${pgSubTh('')}${pgSubTh('SPH')}${pgSubTh('CYL')}${pgSubTh('AXIS')}${pgSubTh('VN')}${pgSubTh('SPH')}${pgSubTh('CYL')}${pgSubTh('AXIS')}${pgSubTh('VN')}</tr>` +
            `</thead><tbody>` +
            `<tr><th class="text-center" style="background:#f0f4f8;color:#1B4F72;font-size:10px;font-weight:700;padding:2px;">D</th>` +
              `${stCell(val('exam_data[st][re][ds]'))}${stCell(val('exam_data[st][re][dc]'))}${stCell(val('exam_data[st][re][ax]'))}${stCell(val('exam_data[st][re][vn]'))}` +
              `${stCell(val('exam_data[st][le][ds]'))}${stCell(val('exam_data[st][le][dc]'))}${stCell(val('exam_data[st][le][ax]'))}${stCell(val('exam_data[st][le][vn]'))}` +
            `</tr>` +
            `<tr><th class="text-center" style="background:#f0f4f8;color:#1B4F72;font-size:10px;font-weight:700;padding:2px;">N</th>` +
              `${stCell(val('exam_data[st][re][ns]'))}${stCell(val('exam_data[st][re][nc]'))}${stCell(val('exam_data[st][re][na]'))}${stCell('—', true)}` +
              `${stCell(val('exam_data[st][le][ns]'))}${stCell(val('exam_data[st][le][nc]'))}${stCell(val('exam_data[st][le][na]'))}${stCell('—', true)}` +
            `</tr>` +
            `</tbody></table>`;
        if (stReAdd !== '-' || stLeAdd !== '-') {
            stHtml += `<div style="font-size:10px;color:#475569;margin-bottom:3px;"><span style="color:#1B4F72;font-weight:700;">ADD</span>&emsp;RE: <strong>${stReAdd}</strong>&emsp;LE: <strong>${stLeAdd}</strong></div>`;
        }
        if (stBadges.length) {
            stHtml += `<div class="d-flex flex-wrap gap-1">` +
                stBadges.map(b => `<span style="font-size:10px;background:#1B4F72;color:#fff;padding:1px 8px;border-radius:10px;">${b}</span>`).join('') +
                `</div>`;
        }
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
            `<tr><th>LENS</th><td>${formatLensOe('re')}</td><td>${formatLensOe('le')}</td></tr>` +
            `<tr><th>EM</th><td>${val('exam_data[oe][em_re]')}</td><td>${val('exam_data[oe][em_le]')}</td></tr>` +
            `<tr><th>COVERTEST</th><td>${val('exam_data[oe][covertest_re]')}</td><td>${val('exam_data[oe][covertest_le]')}</td></tr>` +
            `<tr><th>OTHER</th><td>${val('exam_data[oe][other_re]')}</td><td>${val('exam_data[oe][other_le]')}</td></tr>` +
            `</tbody></table>`;
        document.getElementById('canvas_oe').innerHTML = oeHtml;

        // BOX 4: Fundus table
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
            const qty = row.querySelector('[name*="[quantity]"]')?.value || '-';
            const routeId = row.querySelector('[name*="[route_id]"]')?.value || '';
            const route = routeId && routesJson[routeId] ? routesJson[routeId] : '-';
            return `<tr><td>${name}</td><td>${dosage}</td><td>${duration}</td><td>${qty}</td><td>${route}</td></tr>`;
        }).filter(Boolean).join('');

        const rxHtml =
            `<div class="mb-1" style="font-size:11px;"><strong>Dx:</strong> ${diagnosisText} &nbsp; <strong>Dilate:</strong> ${dilateVal}</div>` +
            `<table class="table table-sm table-bordered border-dark mb-0" style="font-size:11px;">` +
            `<thead><tr><th class="bg-dark text-white">Medicine</th><th class="bg-dark text-white">Dosage</th><th class="bg-dark text-white">Days</th><th class="bg-dark text-white">QTY</th><th class="bg-dark text-white">Route</th></tr></thead>` +
            `<tbody>${rxBodyHtml || '<tr><td colspan="5" class="text-center text-muted">No medicines</td></tr>'}</tbody>` +
            `</table>`;
        document.getElementById('canvas_rx').innerHTML = rxHtml;

        const adviceText = (document.getElementById('advice_textarea')?.value || '').trim();
        const adviceEl = document.getElementById('canvas_advice');
        if (adviceEl) {
            adviceEl.innerHTML = adviceText
                ? `<div style="font-size:12px;white-space:pre-line;">${adviceText}</div>`
                : `<em class="text-muted" style="font-size:11px;">No advice entered</em>`;
        }
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
$__dxGroups = $masters['med_groups']->map(fn($g) => ['id' => $g->id, 'name' => $g->name, 'diagnosis_id' => $g->diagnosis_id, 'item_count' => $g->items->count()])->values();
$__dxAdvices = $masters['advices']->map(fn($a) => ['id' => $a->id, 'advice' => $a->advice ?? '', 'diagnosis_ids' => $a->diagnosis_ids ?? []])->values();
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
                '<div class="p-3 rounded-3 mb-1" style="background:#f0f9ff;border:1px solid #bae6fd;">' +
                '<div class="d-flex align-items-center gap-2 mb-2">' +
                '<i class="bi bi-link-45deg" style="color:#1B4F72;font-size:14px;"></i>' +
                '<span class="fw-semibold" style="font-size:11px;color:#1B4F72;text-transform:uppercase;letter-spacing:.07em;">Diagnosis-linked Advices</span>' +
                '<span style="font-size:11px;color:#64748b;">— already added · click to re-append</span>' +
                '</div>' +
                '<div class="d-flex flex-wrap gap-2">' +
                advices.map(a =>
                    '<button type="button" class="advice-quick-btn" style="font-size:12px;padding:4px 12px;border-radius:20px;border:1px solid #0ea5e9;background:white;color:#0369a1;font-weight:500;cursor:pointer;" data-advice="' + esc(a.advice) + '">' +
                    '<i class="bi bi-check2 me-1" style="font-size:10px;"></i>' + esc(a.advice) + '</button>'
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
                    if (btn) { btn.disabled = false; btn.innerHTML = 'Added'; btn.classList.replace('btn-primary', 'btn-success'); }
                    updateLivePreview();
                })
                .catch(() => {
                    if (btn) { btn.disabled = false; btn.innerHTML = 'Add All'; }
                    alert('Could not load medicine group.');
                });
        };

        window.appendSuggestedAdvice = function (id) {
            const text = adviceMap[id] || '';
            if (!text) return;
            const ta = document.getElementById('advice_textarea');
            if (!ta) return;
            const cur = ta.value.trim();
            ta.value = cur ? cur + ', ' + text : text;
            if (typeof updateLivePreview === 'function') updateLivePreview();
        };

        function update() {
            const ids = Array.from(document.querySelectorAll('input[name="exam_data[diagnoses][]"]:checked')).map(el => +el.value);
            const hasIds = ids.length > 0;
            renderSuggestedGroups(dxMedGroups.filter(g => g.diagnosis_id && ids.includes(+g.diagnosis_id)), hasIds);
            renderSuggestedAdvices(dxAdvices.filter(a => a.diagnosis_ids && a.diagnosis_ids.some(id => ids.includes(id))), hasIds);
        }

        document.addEventListener('change', function (e) {
            if (!e.target.matches('input[name="exam_data[diagnoses][]"]')) return;
            update();
            if (e.target.checked) {
                const dxId = +e.target.value;
                const linked = dxAdvices.filter(a => a.diagnosis_ids && a.diagnosis_ids.includes(dxId));
                if (linked.length) {
                    const ta = document.getElementById('advice_textarea');
                    if (ta) {
                        linked.forEach(a => {
                            const text = adviceMap[a.id] || '';
                            if (!text) return;
                            if (!ta.value.includes(text)) {
                                ta.value = ta.value.trim() ? ta.value.trim() + ', ' + text : text;
                            }
                        });
                        ta.dispatchEvent(new Event('input'));
                        if (typeof updateLivePreview === 'function') updateLivePreview();
                    }
                }
            }
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

    // ── Diagnosis → Advice auto-fill ──────────────────────────────────────
    (function () {
        let autoInsertedDiagnoses = '';

        document.getElementById('modalDiagnosis')?.addEventListener('hidden.bs.modal', function () {
            const selected = Array.from(
                document.querySelectorAll('input[name="exam_data[diagnoses][]"]:checked')
            ).map(function (cb) {
                const label = document.querySelector('label[for="' + cb.id + '"]');
                if (!label) return '';
                const clone = label.cloneNode(true);
                clone.querySelectorAll('.badge').forEach(function (b) { b.remove(); });
                return clone.textContent.trim();
            }).filter(Boolean);

            const ta = document.getElementById('advice_textarea');
            if (!ta) return;

            ta.dispatchEvent(new Event('input'));
            if (typeof updateLivePreview === 'function') updateLivePreview();
        });
    })();

    // ── Advice textarea: char counter ─────────────────────────────────────
    (function () {
        const ta    = document.getElementById('advice_textarea');
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

    const coFavBase = '{{ url($slug . "/masters/detail/complaints") }}';
    const coCsrf    = '{{ csrf_token() }}';

    function positionCoDropdown() {
        if (!activeCoInput || !coDropdown) { return; }
        positionFixedDropdown(coDropdown, activeCoInput, 300);
    }

    // Render favourite complaints as quick-access pills above the search bar
    function renderCoFavPills() {
        const wrap = document.getElementById('coFavPillsWrap');
        const container = document.getElementById('coFavPills');
        if (!wrap || !container) { return; }

        const favs = coComplaints.filter(i => i.is_favourite)
            .sort((a, b) => String(a.complaint).localeCompare(String(b.complaint)));

        if (!favs.length) { wrap.style.display = 'none'; return; }

        wrap.style.display = '';
        container.innerHTML = favs.map(item =>
            `<button type="button" class="co-fav-pill" data-name="${escapeAttr(item.complaint)}" data-id="${item.id}">` +
            `${escapeAttr(item.complaint)}` +
            `<span class="co-fav-pill-star" data-id="${item.id}" title="Remove from favourites">★</span>` +
            `</button>`
        ).join('');

        container.querySelectorAll('.co-fav-pill').forEach(pill => {
            pill.addEventListener('click', function (e) {
                if (e.target.closest('.co-fav-pill-star')) { return; }
                addCoRow(this.dataset.name);
            });
        });

        container.querySelectorAll('.co-fav-pill-star').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const id = this.dataset.id;
                fetch(`${coFavBase}/${id}/toggle-favourite`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': coCsrf, 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    const c = coComplaints.find(x => String(x.id) === String(id));
                    if (c) { c.is_favourite = data.is_favourite; }
                    renderCoFavPills();
                    if (coDropdown?.classList.contains('show')) { renderCoDropdown(); }
                });
            });
        });
    }

    // Dropdown shows only non-favourite items (favourites are accessible via pills above)
    function renderCoDropdown(queryOverride) {
        if (!coDropdown || !activeCoInput) { return; }
        const query = queryOverride !== undefined ? queryOverride : (activeCoInput.value || '').trim().toLowerCase();
        const items = sortedCoComplaints()
            .filter(item => !item.is_favourite)
            .filter(item => String(item.complaint).toLowerCase().includes(query));

        if (!items.length) {
            coDropdown.innerHTML = '<div class="co-empty">No complaints found</div>';
            positionCoDropdown();
            coDropdown.classList.add('show');
            return;
        }

        coDropdown.innerHTML =
            `<div class="co-section-lbl">All Complaints</div>` +
            items.map(item =>
                `<div class="co-item" data-name="${escapeAttr(item.complaint)}">` +
                `<button type="button" class="co-fav-btn" data-id="${item.id}" title="Add to favourites">☆</button>` +
                `<span class="co-item-name">${escapeAttr(item.complaint)}</span>` +
                `</div>`
            ).join('');
        positionCoDropdown();
        coDropdown.classList.add('show');

        // Favourite toggle — moves item from dropdown to pills
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
                    renderCoFavPills();
                    renderCoDropdown();
                });
            });
        });
    }

    renderCoFavPills();

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
        const sinceOpts = ['-',...Array.from({length:10},(_,n)=>n+1)]
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
            <td class="text-center"><button type="button" class="btn btn-sm btn-danger px-2" onclick="this.closest('tr').remove(); checkProgress(); updateLivePreview();">&times;</button></td>`;
        document.getElementById('coBody').appendChild(tr);
        const msg = document.getElementById('coEmptyMsg');
        if (msg) { msg.style.display = 'none'; }
        if (coSearch) { coSearch.value = ''; }
        coDropdown?.classList.remove('show');
        activeCoInput = null;
        checkProgress();
        updateLivePreview();
        form.dispatchEvent(new Event('input', { bubbles: false }));
    }
    window._addCoRow = addCoRow;
    document.getElementById('addCoRow')?.addEventListener('click', function () {
        addCoRow(coSearch ? coSearch.value.trim() : '');
    });

    // ── KCO dropdown ──────────────────────────────────────────────────────────
    (function () {
        const kcoSearch   = document.getElementById('kcoSearch');
        const kcoDropdown = document.getElementById('kcoDropdown');
        if (!kcoSearch || !kcoDropdown) { return; }

        const kcoItems   = @json($masters['kcos']); // {id, kco, is_favourite}
        const kcoFavBase = '{{ url($slug . "/masters/detail/kcos") }}';
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

        function renderKcoFavPills() {
            const wrap = document.getElementById('kcoFavPillsWrap');
            const container = document.getElementById('kcoFavPills');
            if (!wrap || !container) { return; }
            const favs = kcoItems.filter(i => i.is_favourite)
                .sort((a, b) => String(a.kco).localeCompare(String(b.kco)));
            if (!favs.length) { wrap.style.display = 'none'; return; }
            wrap.style.display = '';
            container.innerHTML = favs.map(item =>
                `<button type="button" class="co-fav-pill" data-name="${escapeAttr(item.kco)}" data-id="${item.id}">` +
                `${escapeAttr(item.kco)}` +
                `<span class="co-fav-pill-star" data-id="${item.id}" title="Remove from favourites">★</span>` +
                `</button>`
            ).join('');
            container.querySelectorAll('.co-fav-pill').forEach(pill => {
                pill.addEventListener('click', function (e) {
                    if (e.target.closest('.co-fav-pill-star')) { return; }
                    addKcoRow(this.dataset.name);
                });
            });
            container.querySelectorAll('.co-fav-pill-star').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const id = this.dataset.id;
                    fetch(`${kcoFavBase}/${id}/toggle-favourite`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': coCsrf, 'Accept': 'application/json' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        const c = kcoItems.find(x => String(x.id) === String(id));
                        if (c) { c.is_favourite = data.is_favourite; }
                        renderKcoFavPills();
                        if (kcoDropdown?.classList.contains('show')) { renderKcoDropdown(); }
                    });
                });
            });
        }

        // Dropdown shows only non-favourite items (favourites accessible via pills above)
        function renderKcoDropdown(queryOverride) {
            const query = queryOverride !== undefined ? queryOverride : (activeKcoInput?.value || '').trim().toLowerCase();
            const items = sortedKcos()
                .filter(i => !i.is_favourite)
                .filter(i => String(i.kco).toLowerCase().includes(query));

            if (!items.length) {
                kcoDropdown.innerHTML = '<div class="co-empty">No conditions found</div>';
                positionKcoDropdown();
                kcoDropdown.classList.add('show');
                return;
            }

            kcoDropdown.innerHTML =
                `<div class="co-section-lbl">All Conditions</div>` +
                items.map(item =>
                    `<div class="co-item" data-name="${escapeAttr(item.kco)}">` +
                    `<button type="button" class="co-fav-btn" data-id="${item.id}" title="Add to favourites">☆</button>` +
                    `<span class="co-item-name">${escapeAttr(item.kco)}</span>` +
                    `</div>`
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
                        renderKcoFavPills();
                        renderKcoDropdown();
                    });
                });
            });
        }

        renderKcoFavPills();

        function addKcoRow(val) {
            const condition = (val || (activeKcoInput === kcoSearch ? kcoSearch.value : '') || '').trim();
            if (!condition) { activeKcoInput = kcoSearch; kcoSearch.focus(); renderKcoDropdown(''); return; }
            const i  = kcoRowIndex++;
            const tr = document.createElement('tr');
            tr.className = 'kco-row';
            const sinceOpts = ['-',...Array.from({length:10},(_,n)=>n+1)]
                .map((n,idx) => `<option value="${idx===0?'':n}">${n}</option>`).join('');
            const unitOpts = ['Days','Weeks','Months','Years','Longtime']
                .map(u => `<option value="${u}"${u==='Years'?' selected':''}>${u}</option>`).join('');
            tr.innerHTML = `
                <td><input type="text" name="exam_data[kco_rows][${i}][condition]" value="${escapeAttr(condition)}" class="form-control form-control-sm row-kco-search" placeholder="Condition" autocomplete="off"></td>
                <td><select name="exam_data[kco_rows][${i}][since]" class="form-select form-select-sm">${sinceOpts}</select></td>
                <td><select name="exam_data[kco_rows][${i}][unit]" class="form-select form-select-sm">${unitOpts}</select></td>
                <td><input type="text" name="exam_data[kco_rows][${i}][comment]" class="form-control form-control-sm" placeholder="Comment"></td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-danger px-2" onclick="this.closest('tr').remove(); checkProgress(); updateLivePreview();">&times;</button></td>`;
            document.getElementById('kcoBody').appendChild(tr);
            const msg = document.getElementById('kcoEmptyMsg');
            if (msg) { msg.style.display = 'none'; }
            kcoSearch.value = '';
            kcoDropdown.classList.remove('show');
            activeKcoInput = null;
            checkProgress();
            updateLivePreview();
            form.dispatchEvent(new Event('input', { bubbles: false }));
        }
        window._addKcoRow = addKcoRow;

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

    // Advice quick-add chips → append to textarea (delegated for dynamic pills too)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.advice-quick-btn');
        if (!btn) return;
        const text = btn.dataset.advice || '';
        if (!text) return;
        const ta = document.getElementById('advice_textarea');
        if (!ta) return;
        ta.value = ta.value.trim() ? ta.value.trim() + ', ' + text : text;
        ta.dispatchEvent(new Event('input'));
        checkProgress();
        if (typeof updateLivePreview === 'function') updateLivePreview();
    });

    // Global search filter for More dropdown — called via oninput on #newAdviceInput
    window.adviceMoreFilter = function (val) {
        const q        = val.trim().toLowerCase();
        const list     = document.getElementById('adviceMoreList');
        const noResult = document.getElementById('adviceNoResult');
        if (!list) return;
        let visible = 0;
        list.querySelectorAll('li.advice-more-item').forEach(function (li) {
            const text = (li.dataset.adviceText || li.querySelector('.advice-quick-btn')?.dataset.advice || '').toLowerCase();
            const show = !q || text.includes(q);
            li.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (noResult) noResult.style.display = (q && visible === 0) ? '' : 'none';
    };

    // Advice favourite toggle in More dropdown
    (function () {
        const advFavBase = '{{ url($slug . "/masters/detail/advice") }}';
        const csrf       = '{{ csrf_token() }}';

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.advice-fav-btn');
            if (!btn) return;
            e.stopPropagation();

            const id      = btn.dataset.id;
            const isFav   = btn.dataset.fav === '1';
            const icon    = btn.querySelector('i');
            const advText = btn.closest('li')?.querySelector('.advice-quick-btn')?.dataset.advice || '';

            // Optimistic UI update
            btn.dataset.fav  = isFav ? '0' : '1';
            icon.className   = isFav ? 'bi bi-star' : 'bi bi-star-fill';
            icon.style.color = isFav ? '#94a3b8' : '#f59e0b';
            btn.style.opacity = isFav ? '0.3' : '1';
            btn.title = isFav ? 'Mark as favourite' : 'Remove from favourites';

            // Update Quick Add pill star icon if it exists
            document.querySelectorAll('#adviceChipsWrap .advice-quick-btn').forEach(pill => {
                if (pill.dataset.advice === advText) {
                    const starI = pill.querySelector('i');
                    if (starI) starI.className = isFav ? 'bi bi-plus-lg me-1' : 'bi bi-star-fill me-1';
                }
            });

            fetch(`${advFavBase}/${id}/toggle-favourite`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            }).catch(() => {
                // Revert on failure
                btn.dataset.fav   = isFav ? '1' : '0';
                icon.className    = isFav ? 'bi bi-star-fill' : 'bi bi-star';
                icon.style.color  = isFav ? '#f59e0b' : '#94a3b8';
                btn.style.opacity = isFav ? '1' : '0.3';
            });
        });
    })();

    // Add New Advice (AJAX save to master + append to textarea)
    (function () {
        const input  = document.getElementById('newAdviceInput');
        const btn    = document.getElementById('newAdviceBtn');
        const moreList = document.getElementById('adviceMoreList');
        const moreDrop = document.getElementById('adviceMoreDropdown');

        function appendAdviceChip(text) {
            // Add as a pill in the pills row before the More dropdown
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'advice-quick-btn';
            chip.style.cssText = 'font-size:12px;padding:4px 12px;border-radius:20px;border:1px solid #1B4F72;background:white;color:#1B4F72;font-weight:500;cursor:pointer;line-height:1.5;';
            chip.dataset.advice = text;
            chip.innerHTML = '<i class="bi bi-plus-lg me-1" style="font-size:9px;"></i>' + text;
            const chipsWrap = document.getElementById('adviceChipsWrap');
            if (chipsWrap && moreDrop) {
                chipsWrap.insertBefore(chip, moreDrop);
            }
            // Also add to More list
            const newLi = document.createElement('li');
            newLi.className = 'advice-more-item';
            newLi.dataset.adviceText = text.toLowerCase();
            newLi.style.cssText = 'display:flex;align-items:center;padding:2px 10px 2px 12px;';
            newLi.innerHTML =
                '<button type="button" class="advice-quick-btn" data-advice="' + text.replace(/"/g, '&quot;') + '" style="flex:1;font-size:13px;padding:6px 4px;text-align:left;border:none;background:transparent;color:#1e293b;cursor:pointer;border-radius:6px;line-height:1.4;">' + text + '</button>';
            const moreListEl = document.getElementById('adviceMoreList');
            if (moreListEl) {
                const noRes = document.getElementById('adviceNoResult');
                if (noRes) moreListEl.insertBefore(newLi, noRes);
                else moreListEl.appendChild(newLi);
            }
        }

        function appendToTextarea(text) {
            const ta = document.getElementById('advice_textarea');
            if (!ta) return;
            ta.value = ta.value.trim() ? ta.value.trim() + ', ' + text : text;
            ta.dispatchEvent(new Event('input'));
            if (typeof updateLivePreview === 'function') updateLivePreview();
        }

        function doAdd() {
            const text = (input.value || '').trim();
            if (!text) { input.focus(); return; }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            fetch('{{ route("hospital.ajax.advice.add", ["slug" => $slug]) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ advice: text }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.advice) {
                    appendAdviceChip(data.advice);
                    appendToTextarea(data.advice);
                    input.value = '';
                }
            })
            .catch(() => {
                // Even on error, append text to textarea
                appendToTextarea(text);
                input.value = '';
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = '+ Add';
                input.focus();
            });
        }

        btn.addEventListener('click', doAdd);
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); doAdd(); }
        });
    })();

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

    // ── PG custom dropdowns ───────────────────────────────────────────────────
    (function () {
        const pgMasterOpts = {
            sph_cyl: @json($pgMasterOpts['sph_cyl'] ?? []),
            axis:    @json($pgMasterOpts['axis'] ?? []),
            vn:      @json($pgMasterOpts['vn'] ?? []),
            nrvn:    @json($pgMasterOpts['nrvn'] ?? []),
        };

        const pdd = document.createElement('div');
        pdd.className = 'co-dropdown pg-co-dropdown';
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
                    // Sync ST NEAR Axis from Distance Axis
                    if (hidden?.name) {
                        const axM = hidden.name.match(/^exam_data\[st\]\[(re|le)\]\[ax\]$/);
                        if (axM && typeof window.syncStNearFromDist === 'function') window.syncStNearFromDist(axM[1]);
                    }
                    pdd.classList.remove('show');
                    activePgInp = null;
                    checkProgress();
                    updateLivePreview();
                });
            });
        }

        document.querySelectorAll('.pg-inp').forEach(inp => {
            inp.addEventListener('focus', function () { if (this.dataset.noDrop || this.dataset.axisPicker) return; activePgInp = this; renderPdd(''); });
            inp.addEventListener('input', function () { if (this.dataset.noDrop || this.dataset.axisPicker) return; activePgInp = this; renderPdd(); });
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

    // ── PG value picker modal ─────────────────────────────────────────────────
    (function () {
        let pgPickTarget    = null;
        let pgParentModalEl = null;

        // Near SPH = Distance SPH + ADD (per eye)
        function calcStNearSph(eye) {
            var dsHid  = document.querySelector('[name="exam_data[st][' + eye + '][ds]"]');
            var addHid = document.querySelector('[name="exam_data[st][' + eye + '][add]"]');
            var nsHid  = document.querySelector('[name="exam_data[st][' + eye + '][ns]"]');
            if (!dsHid || !addHid || !nsHid) return;
            var ds  = parseFloat(String(dsHid.value  || '').replace(/^\+/, '')) || 0;
            var add = parseFloat(String(addHid.value || '').replace(/^\+/, '')) || 0;
            var ns  = ds + add;
            var fmt = ns > 0 ? '+' + ns.toFixed(2) : (ns < 0 ? ns.toFixed(2) : '0.00');
            nsHid.value = fmt;
            // Show calculated NS in the picker display input
            var pgInp = addHid.closest('.pg-select-wrap')?.querySelector('.pg-inp');
            if (pgInp) pgInp.value = fmt;
            // Update ADD label below
            var addLabel = nsHid.previousElementSibling?.querySelector('strong');
            if (addLabel) addLabel.textContent = addHid.value || '—';
        }

        // Sync ST NEAR CYL/Axis from Distance (NC mirrors DC, NA mirrors AX)
        function syncStNearFromDist(eye) {
            [['dc','nc'],['ax','na']].forEach(function (pair) {
                var distHid = document.querySelector('[name="exam_data[st][' + eye + '][' + pair[0] + ']"]');
                var nearHid = document.querySelector('[name="exam_data[st][' + eye + '][' + pair[1] + ']"]');
                if (!distHid || !nearHid) return;
                var v = distHid.value;
                nearHid.value = v;
                var disp = nearHid.previousElementSibling;
                if (disp && disp.tagName === 'INPUT') disp.value = v;
            });
        }
        window.syncStNearFromDist = syncStNearFromDist;

        // Sync NEAR on ST modal open
        document.getElementById('modalST')?.addEventListener('show.bs.modal', function () {
            ['re', 'le'].forEach(function (eye) { calcStNearSph(eye); syncStNearFromDist(eye); });
        });

        function pgFmt(num) {
            if (num === 0) return '0.00';
            return num > 0 ? '+' + num.toFixed(2) : num.toFixed(2);
        }

        function openPicker(btn) {
            const sign = btn.dataset.sign; // 'pos' | 'neg'
            const row  = btn.closest('.d-flex');
            const wrap = row?.querySelector('.pg-select-wrap');
            const inp  = wrap?.querySelector('.pg-inp');
            const hid  = wrap?.querySelector('input[type="hidden"]');
            if (!inp) return;
            pgPickTarget = { inp, hid };

            // Title
            const colHeader = btn.closest('td')?.previousElementSibling?.previousElementSibling;
            const fieldName = '';
            document.getElementById('pgPickerTitle').textContent = sign === 'pos' ? '+ Positive Values' : '− Negative Values';

            // Build grid
            const grid = document.getElementById('pgPickerGrid');
            grid.innerHTML = '';
            const cur = String(inp.value || '').trim();
            const masterVals = @json($pgMasterOpts['sph_cyl']);
            masterVals.forEach(function (rawVal) {
                const num = parseFloat(rawVal);
                if (isNaN(num) || num <= 0) return;
                const v   = num.toFixed(2);
                const fmt = sign === 'pos' ? '+' + v : '-' + v;
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'pg-picker-chip';
                chip.textContent = fmt;
                chip.dataset.val = fmt;
                if (cur === fmt) {
                    chip.style.cssText = 'width:100%;padding:10px 4px;font-size:14px;font-weight:700;border-radius:8px;border:2px solid #1B4F72;background:#fff;color:#1B4F72;cursor:pointer;transition:all .12s;';
                } else {
                    chip.style.cssText = 'width:100%;padding:10px 4px;font-size:14px;font-weight:700;border-radius:8px;border:none;background:#1B4F72;color:#fff;cursor:pointer;transition:all .12s;';
                }
                chip.addEventListener('mouseover', function () { if (this.dataset.val !== cur) { this.style.background = '#154360'; } });
                chip.addEventListener('mouseout',  function () { if (this.dataset.val !== cur) { this.style.background = '#1B4F72'; } });
                grid.appendChild(chip);
            });

            // Always show 0.00 as last chip
            (function () {
                const fmt  = '0.00';
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'pg-picker-chip';
                chip.textContent = fmt;
                chip.dataset.val = fmt;
                if (cur === fmt) {
                    chip.style.cssText = 'width:100%;padding:10px 4px;font-size:14px;font-weight:700;border-radius:8px;border:2px solid #64748b;background:#fff;color:#64748b;cursor:pointer;transition:all .12s;';
                } else {
                    chip.style.cssText = 'width:100%;padding:10px 4px;font-size:14px;font-weight:700;border-radius:8px;border:none;background:#475569;color:#fff;cursor:pointer;transition:all .12s;';
                }
                chip.addEventListener('mouseover', function () { if (this.dataset.val !== cur) { this.style.background = '#334155'; } });
                chip.addEventListener('mouseout',  function () { if (this.dataset.val !== cur) { this.style.background = '#475569'; } });
                grid.appendChild(chip);
            })();

            document.getElementById('pgPickerCurrent').textContent = cur || '—';
            document.getElementById('pgPickerManual').value = '';

            // Bootstrap can't stack two modals — close parent modal first, then open picker
            pgParentModalEl     = btn.closest('.modal');
            const pickerModalEl = document.getElementById('modalPGPicker');
            const pickerModal   = bootstrap.Modal.getOrCreateInstance(pickerModalEl);

            if (pgParentModalEl) {
                pgParentModalEl.addEventListener('hidden.bs.modal', function () { pickerModal.show(); }, { once: true });
                bootstrap.Modal.getInstance(pgParentModalEl)?.hide();
            } else {
                pickerModal.show();
            }
        }

        // Enable/disable Axis based on CYL value
        function syncAxisForCyl(cylInp, cylVal) {
            const td      = cylInp.closest('td');
            const axisTd  = td?.nextElementSibling;
            const axisInp = axisTd?.querySelector('.axis-disp');
            const axisHid = axisTd?.querySelector('input[type="hidden"]');
            if (!axisInp) return;

            const num    = parseFloat(String(cylVal || '').replace(/^\+/, ''));
            const isZero = isNaN(num) || num === 0;

            if (isZero) {
                axisInp.value = '';
                if (axisHid) axisHid.value = '';
                axisInp.disabled = true;
                axisInp.style.cssText += ';opacity:.35;cursor:not-allowed;pointer-events:none;background:#f1f5f9;border-color:#e2e8f0 !important;';
            } else {
                axisInp.disabled = false;
                axisInp.style.opacity   = '1';
                axisInp.style.cursor    = 'pointer';
                axisInp.style.pointerEvents = '';
                axisInp.style.background    = '';
                axisInp.style.borderColor   = '';
            }
        }

        function applyVal(val) {
            if (!pgPickTarget) return;
            pgPickTarget.inp.value = val;
            if (pgPickTarget.hid) pgPickTarget.hid.value = val;
            document.getElementById('pgPickerCurrent').textContent = val || '—';

            // Sync Axis disabled state when CYL changes
            const hidName = pgPickTarget.hid?.name || '';
            if (hidName.includes('[dc]') || hidName.includes('[nc]')) {
                syncAxisForCyl(pgPickTarget.inp, val);
            }

            // Recalc ST Near SPH when Distance SPH or ADD changes
            const stDsMatch  = hidName.match(/^exam_data\[st\]\[(re|le)\]\[ds\]$/);
            if (stDsMatch) calcStNearSph(stDsMatch[1]);
            const stAddMatch = hidName.match(/^exam_data\[st\]\[(re|le)\]\[add\]$/);
            if (stAddMatch) { calcStNearSph(stAddMatch[1]); syncStNearFromDist(stAddMatch[1]); }

            // Sync ST NEAR CYL from Distance CYL
            const stDcMatch = hidName.match(/^exam_data\[st\]\[(re|le)\]\[dc\]$/);
            if (stDcMatch) syncStNearFromDist(stDcMatch[1]);

            if (typeof checkProgress    === 'function') checkProgress();
            if (typeof updateLivePreview === 'function') updateLivePreview();
        }

        function closePicker() {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPGPicker')).hide();
        }

        // Reopen parent modal when picker closes
        document.getElementById('modalPGPicker').addEventListener('hidden.bs.modal', function () {
            if (pgParentModalEl) {
                bootstrap.Modal.getOrCreateInstance(pgParentModalEl).show();
            }
        });

        // Open picker on +/- button click
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.pg-pick-btn');
            if (btn) { openPicker(btn); return; }

            const chip = e.target.closest('.pg-picker-chip');
            if (chip) { applyVal(chip.dataset.val); closePicker(); }
        });

        // Clear button
        document.getElementById('pgPickerClear')?.addEventListener('click', function () {
            applyVal(''); closePicker();
        });

        // Manual save
        document.getElementById('pgPickerSaveManual')?.addEventListener('click', function () {
            const raw = document.getElementById('pgPickerManual').value.trim();
            if (!raw) return;
            const num = parseFloat(raw);
            if (isNaN(num)) return;
            applyVal(pgFmt(num)); closePicker();
        });

        // Prevent Enter in picker input from submitting exam form
        document.getElementById('pgPickerManual')?.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                document.getElementById('pgPickerSaveManual')?.click();
            }
        });

        // Init Axis state on page load based on saved CYL values (PG + ST)
        [
            'exam_data[pg][re][dc]', 'exam_data[pg][re][nc]', 'exam_data[pg][le][dc]', 'exam_data[pg][le][nc]',
            'exam_data[st][re][dc]', 'exam_data[st][re][nc]', 'exam_data[st][le][dc]', 'exam_data[st][le][nc]',
        ].forEach(function (name) {
            const hid = document.querySelector('[name="' + name + '"]');
            if (!hid) return;
            const cylInp = hid.closest('.pg-select-wrap')?.querySelector('.pg-inp');
            if (cylInp) syncAxisForCyl(cylInp, hid.value);
        });
    })();

    // ── Axis Picker Modal ─────────────────────────────────────────────────────
    (function () {
        let axisPickTarget    = null;
        let axisParentModalEl = null;

        const grid       = document.getElementById('axisPickerGrid');
        const axisValues = [5,10,15,20,25,30,35,40,45,50,55,60,65,70,75,80,85,90,95,100,105,110,115,120,125,130,135,140,145,150,155,160,165,170,175,180];

        function buildGrid(currentVal) {
            grid.innerHTML = '';
            axisValues.forEach(function (v) {
                const btn      = document.createElement('button');
                btn.type       = 'button';
                btn.className  = 'axis-picker-btn';
                btn.textContent = v + '°';
                btn.dataset.val = String(v);
                const isSel    = String(currentVal) === String(v);
                btn.style.cssText = 'width:100%;padding:10px 4px;font-size:13px;font-weight:700;border-radius:8px;border:2px solid #1B4F72'
                    + ';background:' + (isSel ? '#fff' : '#1B4F72')
                    + ';color:' + (isSel ? '#1B4F72' : '#fff') + ';cursor:pointer;transition:background .12s,color .12s,border-color .12s;';
                grid.appendChild(btn);
            });
        }

        function openAxisPicker(inp) {
            axisPickTarget = {
                inp: inp,
                hid: inp.closest('.pg-select-wrap')?.querySelector('input[type="hidden"]'),
            };
            buildGrid(String(inp.value || '').trim().replace('°', ''));

            axisParentModalEl   = inp.closest('.modal');
            const pickerModalEl = document.getElementById('modalAxisPicker');
            const pickerModal   = bootstrap.Modal.getOrCreateInstance(pickerModalEl);

            if (axisParentModalEl) {
                axisParentModalEl.addEventListener('hidden.bs.modal', function () { pickerModal.show(); }, { once: true });
                bootstrap.Modal.getInstance(axisParentModalEl)?.hide();
            } else {
                pickerModal.show();
            }
        }

        function applyAxisVal(val) {
            if (!axisPickTarget) return;
            axisPickTarget.inp.value = val;
            if (axisPickTarget.hid) axisPickTarget.hid.value = val;

            if (axisPickTarget.hid?.name) {
                const axM = axisPickTarget.hid.name.match(/^exam_data\[st\]\[(re|le)\]\[ax\]$/);
                if (axM && typeof window.syncStNearFromDist === 'function') window.syncStNearFromDist(axM[1]);
            }
            if (typeof checkProgress     === 'function') checkProgress();
            if (typeof updateLivePreview === 'function') updateLivePreview();
        }

        grid.addEventListener('click', function (e) {
            const btn = e.target.closest('.axis-picker-btn');
            if (!btn) return;
            applyAxisVal(btn.dataset.val);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAxisPicker')).hide();
        });

        document.getElementById('modalAxisPicker').addEventListener('hidden.bs.modal', function () {
            if (axisParentModalEl) {
                bootstrap.Modal.getOrCreateInstance(axisParentModalEl).show();
            }
        });

        document.addEventListener('click', function (e) {
            const inp = e.target.closest('.axis-disp[data-axis-picker]');
            if (inp && !inp.disabled) {
                e.preventDefault();
                openAxisPicker(inp);
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
        const oeFavBase = '{{ url($slug . "/masters/detail") }}';

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
        const discItems = @json(collect($masters['disc'])->map(fn($o) => ['id' => $o->id, 'value' => $o->value, 'is_favourite' => (bool) ($o->is_favourite ?? false)])->values());
        const frItems   = @json(collect($masters['fr'])->map(fn($o) => ['id' => $o->id, 'value' => $o->value, 'is_favourite' => (bool) ($o->is_favourite ?? false)])->values());
        const favBase   = '{{ url($slug . "/masters/detail") }}';

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

        const missingCoRows  = {};
        const missingKcoRows = {};

        Object.entries(data).forEach(([name, value]) => {
            if (Array.isArray(value)) {
                // Checkbox groups — tick matching values
                form.querySelectorAll(`input[name="${name}"][type="checkbox"]`).forEach(cb => {
                    cb.checked = value.includes(cb.value);
                });
            } else {
                const el = form.querySelector(`[name="${name}"]`);
                if (!el) {
                    // Collect dynamic rows that don't exist in DOM yet
                    const coM  = name.match(/^exam_data\[co_rows\]\[(\d+)\]\[(\w+)\]$/);
                    const kcoM = name.match(/^exam_data\[kco_rows\]\[(\d+)\]\[(\w+)\]$/);
                    if (coM)  { if (!missingCoRows[coM[1]])   missingCoRows[coM[1]]   = {}; missingCoRows[coM[1]][coM[2]]   = value; }
                    if (kcoM) { if (!missingKcoRows[kcoM[1]]) missingKcoRows[kcoM[1]] = {}; missingKcoRows[kcoM[1]][kcoM[2]] = value; }
                    return;
                }
                if (el.type === 'radio') {
                    const radio = form.querySelector(`[name="${name}"][value="${value}"]`);
                    if (radio) { radio.checked = true; }
                } else {
                    el.value = value;
                }
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        // Recreate C/O rows that were added dynamically (not rendered by Blade on load)
        Object.keys(missingCoRows).sort((a, b) => Number(a) - Number(b)).forEach(idx => {
            const row = missingCoRows[idx];
            if (!row.complaint || typeof window._addCoRow !== 'function') { return; }
            window._addCoRow(row.complaint);
            const allRows = document.querySelectorAll('#coBody .co-row');
            const newRow  = allRows[allRows.length - 1];
            if (!newRow) { return; }
            if (row.since   !== undefined) { const s = newRow.querySelector('[name*="[since]"]');   if (s) s.value = row.since; }
            if (row.unit    !== undefined) { const u = newRow.querySelector('[name*="[unit]"]');    if (u) u.value = row.unit; }
            if (row.eye     !== undefined) { const e = newRow.querySelector('[name*="[eye]"]');     if (e) e.value = row.eye; }
            if (row.comment !== undefined) { const c = newRow.querySelector('[name*="[comment]"]'); if (c) c.value = row.comment; }
        });

        // Recreate KCO rows that were added dynamically
        Object.keys(missingKcoRows).sort((a, b) => Number(a) - Number(b)).forEach(idx => {
            const row = missingKcoRows[idx];
            if (!row.condition || typeof window._addKcoRow !== 'function') { return; }
            window._addKcoRow(row.condition);
            const allRows = document.querySelectorAll('#kcoBody .kco-row');
            const newRow  = allRows[allRows.length - 1];
            if (!newRow) { return; }
            if (row.since   !== undefined) { const s = newRow.querySelector('[name*="[since]"]');   if (s) s.value = row.since; }
            if (row.unit    !== undefined) { const u = newRow.querySelector('[name*="[unit]"]');    if (u) u.value = row.unit; }
            if (row.comment !== undefined) { const c = newRow.querySelector('[name*="[comment]"]'); if (c) c.value = row.comment; }
        });

        // Rebuild H/O chips from the restored hnoHidden value
        if (Object.prototype.hasOwnProperty.call(data, 'exam_data[history]') && typeof window._addHnoChip === 'function') {
            const hnoHidden = document.getElementById('hnoHidden');
            const hnoChips  = document.getElementById('hnoChips');
            if (hnoHidden && hnoChips) {
                hnoChips.innerHTML = '';
                (hnoHidden.value || '').split(',').map(s => s.trim()).filter(Boolean)
                    .forEach(val => window._addHnoChip(val));
            }
        }
    }

    form.addEventListener('input', saveDraft);
    form.addEventListener('change', saveDraft);
    form.addEventListener('submit', function () {
    try {
        localStorage.removeItem(draftKey);
    } catch (_) {}
}); 

    // let _examConfirmed = false;
    // form.addEventListener('submit', function (e) {
    //     if (!_examConfirmed) {
    //         e.preventDefault();
    //         bootstrap.Modal.getOrCreateInstance(document.getElementById('modalExamConfirm')).show();
    //         return;
    //     }
    //     _examConfirmed = false;
    //     try { localStorage.removeItem(draftKey); } catch (_) { /* ignore */ }
    // });

    // document.getElementById('examConfirmYes')?.addEventListener('click', function () {
    //     bootstrap.Modal.getOrCreateInstance(document.getElementById('modalExamConfirm')).hide();
    //     _examConfirmed = true;
    //     form.requestSubmit();
    // });

    // Restore after a short delay so Select2 / dynamic rows are initialised
    setTimeout(loadDraft, 300);
})();
</script>

<script>
// ── H/O chip dropdown ────────────────────────────────────────────────────────
(function () {
    const hnoSearch   = document.getElementById('hnoSearch');
    const hnoDropdown = document.getElementById('hnoDropdown');
    if (!hnoSearch || !hnoDropdown) { return; }
    document.body.appendChild(hnoDropdown); // move to body so modal overflow doesn't clip the fixed dropdown

    const hnoItems   = @json(collect($masters['hnos'] ?? [])->map(fn($o) => ['id' => $o->id, 'hno' => $o->hno, 'is_favourite' => (bool) ($o->is_favourite ?? false)])->values());
    const hnoFavBase = '{{ url($slug . "/masters/detail/hno") }}';

    function syncHnoHidden() {
        const chips = Array.from(document.querySelectorAll('#hnoChips .hno-chip'))
            .map(c => c.childNodes[0].textContent.trim());
        const hidden = document.getElementById('hnoHidden');
        if (hidden) { hidden.value = chips.join(', '); }
        if (typeof checkProgress === 'function') { checkProgress(); }
        if (typeof updateLivePreview === 'function') { updateLivePreview(); }
        document.getElementById('primaryExamForm')?.dispatchEvent(new Event('input', { bubbles: false }));
    }

    const escA = typeof escapeAttr === 'function' ? escapeAttr : (v) => String(v || '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');

    function positionHnoDropdown() {
        if (!hnoDropdown || !hnoSearch) { return; }
        const gap = 4, maxH = 300, minW = 300;
        const rect = hnoSearch.getBoundingClientRect();
        const vh = window.innerHeight;
        const vw = window.innerWidth;
        const spaceBelow = vh - rect.bottom - gap;
        const spaceAbove = rect.top - gap;
        const width = Math.max(rect.width, minW);
        const openUp = spaceBelow < 160 && spaceAbove > spaceBelow;
        let left = rect.left;
        if (left + width > vw - 8) { left = Math.max(8, vw - width - 8); }
        hnoDropdown.style.width = width + 'px';
        hnoDropdown.style.left = left + 'px';
        hnoDropdown.style.transform = '';
        if (openUp) {
            hnoDropdown.style.top = 'auto';
            hnoDropdown.style.bottom = (vh - rect.top + gap) + 'px';
            hnoDropdown.style.maxHeight = Math.max(100, Math.min(maxH, spaceAbove - 8)) + 'px';
        } else {
            hnoDropdown.style.bottom = 'auto';
            hnoDropdown.style.top = (rect.bottom + gap) + 'px';
            hnoDropdown.style.maxHeight = Math.max(100, Math.min(maxH, spaceBelow - 8)) + 'px';
        }
    }

    function renderHnoFavPills() {
        const wrap = document.getElementById('hnoFavPillsWrap');
        const container = document.getElementById('hnoFavPills');
        if (!wrap || !container) { return; }
        const favs = hnoItems.filter(i => i.is_favourite)
            .sort((a, b) => String(a.hno).localeCompare(String(b.hno)));
        if (!favs.length) { wrap.style.display = 'none'; return; }
        wrap.style.display = '';
        container.innerHTML = favs.map(item =>
            `<button type="button" class="co-fav-pill" data-name="${escA(item.hno)}" data-id="${item.id}">` +
            `${escA(item.hno)}` +
            `<span class="co-fav-pill-star" data-id="${item.id}" title="Remove from favourites">★</span>` +
            `</button>`
        ).join('');
        container.querySelectorAll('.co-fav-pill').forEach(pill => {
            pill.addEventListener('click', function (e) {
                if (e.target.closest('.co-fav-pill-star')) { return; }
                addHnoChip(this.dataset.name);
            });
        });
        container.querySelectorAll('.co-fav-pill-star').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const id = this.dataset.id;
                fetch(`${hnoFavBase}/${id}/toggle-favourite`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                }).then(r => r.json()).then(data => {
                    const item = hnoItems.find(i => i.id == id);
                    if (item) { item.is_favourite = data.is_favourite; }
                    renderHnoFavPills();
                    if (hnoDropdown?.classList.contains('show')) { renderHnoDropdown(); }
                });
            });
        });
    }

    // Dropdown shows only non-favourite items (favourites accessible via pills above)
    function renderHnoDropdown(queryOverride) {
        const query = queryOverride !== undefined ? queryOverride : hnoSearch.value.trim().toLowerCase();
        const existing = Array.from(document.querySelectorAll('#hnoChips .hno-chip'))
            .map(c => c.childNodes[0].textContent.trim().toLowerCase());
        const items = hnoItems
            .filter(i => !i.is_favourite)
            .filter(i => String(i.hno).toLowerCase().includes(query) && !existing.includes(String(i.hno).toLowerCase()));

        if (!items.length) {
            hnoDropdown.innerHTML = '<div class="co-empty">No items found</div>';
            positionHnoDropdown();
            hnoDropdown.classList.add('show');
            return;
        }

        hnoDropdown.innerHTML =
            `<div class="co-section-lbl">All H/O Items</div>` +
            items.map(item =>
                `<div class="co-item" data-name="${escA(item.hno)}">` +
                `<button type="button" class="co-fav-btn" data-id="${item.id}" title="Add to favourites">☆</button>` +
                `<span>${escA(item.hno)}</span></div>`
            ).join('');
        positionHnoDropdown();
        hnoDropdown.classList.add('show');

        hnoDropdown.querySelectorAll('.co-fav-btn').forEach(btn => {
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault(); e.stopPropagation();
                const id = this.dataset.id;
                fetch(`${hnoFavBase}/${id}/toggle-favourite`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                }).then(r => r.json()).then(data => {
                    const item = hnoItems.find(i => i.id == id);
                    if (item) { item.is_favourite = data.is_favourite; }
                    renderHnoFavPills();
                    renderHnoDropdown();
                });
            });
        });
    }

    function addHnoChip(val) {
        const value = (val || hnoSearch.value).trim();
        if (!value) { hnoSearch.focus(); renderHnoDropdown(''); return; }
        const existing = Array.from(document.querySelectorAll('#hnoChips .hno-chip'))
            .map(c => c.childNodes[0].textContent.trim().toLowerCase());
        if (existing.includes(value.toLowerCase())) { hnoSearch.value = ''; hnoDropdown.classList.remove('show'); return; }

        const chip = document.createElement('span');
        chip.className = 'badge rounded-pill hno-chip';
        chip.style.cssText = 'background:rgba(27,79,114,.1);color:#1B4F72;font-size:12px;font-weight:600;padding:.35em .75em;border:1px solid rgba(27,79,114,.2);cursor:default;';
        chip.innerHTML = `${escA(value)}<button type="button" class="btn-close btn-close-sm ms-1 hno-remove" style="font-size:.6em;vertical-align:middle;" aria-label="Remove"></button>`;
        document.getElementById('hnoChips').appendChild(chip);
        hnoSearch.value = '';
        hnoDropdown.classList.remove('show');
        syncHnoHidden();
    }
    window._addHnoChip = addHnoChip;

    renderHnoFavPills();

    hnoSearch.addEventListener('focus', function () { renderHnoDropdown(''); });
    hnoSearch.addEventListener('input', function () { renderHnoDropdown(); });
    hnoSearch.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); addHnoChip(this.value.trim()); } });
    document.getElementById('addHnoChip')?.addEventListener('click', () => addHnoChip(hnoSearch.value.trim()));

    window.addEventListener('scroll', positionHnoDropdown, true);
    window.addEventListener('resize', positionHnoDropdown);

    hnoDropdown.addEventListener('mousedown', function (e) {
        const item = e.target.closest('.co-item');
        if (!item || e.target.closest('.co-fav-btn')) { return; }
        e.preventDefault();
        addHnoChip(item.dataset.name || '');
    });

    document.addEventListener('mousedown', function (e) {
        if (!e.target.closest('#hnoDropdown') && e.target !== hnoSearch && !e.target.closest('#addHnoChip')) {
            hnoDropdown.classList.remove('show');
        }
    });

    document.getElementById('hnoChips')?.addEventListener('click', function (e) {
        if (e.target.closest('.hno-remove')) {
            e.target.closest('.hno-chip').remove();
            syncHnoHidden();
        }
    });
})();

// ── Inline exam mode: rewire step buttons to scroll-to-section ────────────
if (document.documentElement.classList.contains('exam-inline')) {
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.step-btn[data-bs-toggle="modal"]').forEach(function (btn) {
            var targetId = (btn.getAttribute('data-bs-target') || '').replace('#', '');
            btn.removeAttribute('data-bs-toggle');
            btn.removeAttribute('data-bs-target');
            btn.addEventListener('click', function () {
                var el = document.getElementById(targetId);
                if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
            });
        });
    });
}
</script>

<script>
(function () {
    const W = { green: {{ (int) hospital_setting('wait_green_max', 30) }}, orange: {{ (int) hospital_setting('wait_orange_max', 60) }}, red: {{ (int) hospital_setting('wait_red_max', 120) }} };
    function getWC(m, g, o, r) { return m < g ? 'wait-green' : m < o ? 'wait-orange' : m < r ? 'wait-red' : 'wait-fire'; }
    function fmtTime(m) { return m < 60 ? m + 'm' : Math.floor(m / 60) + 'h' + (m % 60 > 0 ? ' ' + (m % 60) + 'm' : ''); }
    function updateWaitPills() {
        var now = Date.now();
        document.querySelectorAll('.wait-pill[data-wait-from]').forEach(function (pill) {
            var mins = Math.floor((now - new Date(pill.dataset.waitFrom).getTime()) / 60000);
            var thr = pill.dataset.thresholds ? pill.dataset.thresholds.split(',').map(Number) : null;
            pill.className = 'wait-pill ' + (thr ? getWC(mins, thr[0], thr[1], thr[2]) : getWC(mins, W.green, W.orange, W.red));
            var t = pill.querySelector('.wp-time');
            if (t) t.textContent = fmtTime(mins);
        });
    }
    updateWaitPills();
    setInterval(updateWaitPills, 30000);
})();
</script>

<script>
// Ensure canvas is always fully open/populated on page load (for both doctor and admin)
(function () {
    function initCanvas() {
        if (typeof updateLivePreview === 'function') {
            try { updateLivePreview(); } catch (e) {}
        }
    }
    // Run immediately if DOM is ready, else wait for it
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCanvas);
    } else {
        initCanvas();
    }
    // Fallback: run after draft-restore timer (300ms) completes
    setTimeout(initCanvas, 400);
})();
</script>

@endsection
