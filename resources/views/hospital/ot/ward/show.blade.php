@extends('hospital.layouts.app')
@section('title', 'Ward Preparation')
{{-- Layout page-header intentionally unused — heading, breadcrumb and
actions render inside the content card instead, matching the panel design
used across the rest of the app. --}}

@section('content')
                <div class="ot-ward-page">

                    <div class="ot-outer-card">
                        <div class="ot-header-block">
                            <div>
                                <div class="ot-header-title"><i class="bi bi-person-check"></i> Ward Preparation</div>
                                <nav class="ot-breadcrumb" aria-label="breadcrumb">
                                    <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                                    <span class="ot-breadcrumb-sep">/</span>
                                    <a href="{{ route('hospital.ot.ward.index', ['slug' => $slug]) }}">Ward Queue</a>
                                    <span class="ot-breadcrumb-sep">/</span>
                                    <span class="ot-breadcrumb-current">Booking #{{ $booking->id }}</span>
                                </nav>
                            </div>
                            <a href="{{ route('hospital.ot.ward.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
                                <i class="bi bi-arrow-left me-1"></i> Back to Ward Queue
                            </a>
                        </div>
                    </div>

                    {{-- Patient Verification Header --}}
                    <div class="card ot-premium-card ot-patient-banner border-0 mb-4">
                        <div class="card-body d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center">
                            <div class="ot-title-wrap">
                                <span class="ot-title-icon" aria-hidden="true">
                                    <i class="bi bi-person-check" style="font-size: 1.2rem;"></i>
                                </span>
                                <div>
                                    <h4 class="mb-1 ot-title">{{ $booking->patient?->full_name ?? 'Patient' }}</h4>
                                    <div class="ot-subtitle">
                                        UHID {{ $booking->patient?->patient_code ?? '-' }} &middot;
                                        Eye <strong>{{ $booking->eye }}</strong> &middot;
                                        Surgery Type {{ $booking->ot_type }}
                                    </div>
                                </div>
                            </div>
                            <div class="text-md-end ot-booking-meta">
                                Booking #{{ $booking->id }}<br>
                                {{ optional($booking->surgery_date)->format('d M Y') }}
                                <div class="mt-1">
                                    @if($booking->payment_status === 'paid')
                                        <span class="badge bg-success-subtle text-success-emphasis">Payment: Paid</span>
                                    @elseif($booking->payment_status === 'partially_paid')
                                        <span class="badge bg-warning-subtle text-warning-emphasis">Payment: Partially Paid</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger-emphasis">Payment: Pending</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success ot-alert">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger ot-alert">{{ session('error') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger ot-alert">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Vitals --}}
                    <div class="card ot-premium-card border-0 mb-4">
                        <div class="ot-card-header">
                            <div class="ot-title-wrap">
                                <span class="ot-title-icon" aria-hidden="true"><i class="bi bi-heart-pulse" style="font-size: 1.1rem;"></i></span>
                                <h5 class="ot-title mb-0">Pre-Op Vitals</h5>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            @if($preOp)
                                <div class="ot-total-box mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <span>
                                        Last recorded by <strong>{{ $preOp->enteredBy?->name ?? '-' }}</strong>
                                        on {{ optional($preOp->updated_at)->format('d M Y, h:i A') }}
                                    </span>
                                    <span class="badge {{ $preOp->statusBadgeClass() }} ot-status-badge">
                                        {{ $preOp->statusLabel() }}
                                    </span>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('hospital.ot.ward.vitals.store', ['slug' => $slug, 'booking' => $booking->id]) }}">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-2">
                                        <label class="form-label">Blood Pressure</label>
                                        <input type="text" name="bp" class="form-control" placeholder="120/80" value="{{ old('bp', $preOp->bp ?? '') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Pulse</label>
                                        <input type="text" name="pulse" class="form-control" placeholder="78 bpm" value="{{ old('pulse', $preOp->pulse ?? '') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Blood Sugar (RBS)</label>
                                        <input type="number" step="0.1" name="rbs" class="form-control" value="{{ old('rbs', $preOp->rbs ?? '') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Temperature (°F)</label>
                                        <input type="number" step="0.1" name="temperature" class="form-control" value="{{ old('temperature', $preOp->temperature ?? '') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">SpO2 (%)</label>
                                        <input type="number" step="0.1" min="0" max="100" name="spo2" class="form-control" value="{{ old('spo2', $preOp->spo2 ?? '') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">HbA1c (%)</label>
                                        <input type="number" step="0.1" name="hba1c" class="form-control" value="{{ old('hba1c', $preOp->hba1c ?? '') }}">
                                    </div>
                                </div>

                                {{-- Patient Status is submitted separately below (after Eye Drop Register) --}}
                                <input type="hidden" name="pre_op_status" value="{{ old('pre_op_status', $preOp->pre_op_status ?? \App\Models\Hospital\OT\OtPreOp::STATUS_PREPARING) }}">

                                <div class="d-flex justify-content-end mt-4 pt-3 ot-form-actions">
                                    <button type="submit" class="hms-btn hms-btn-primary ot-save-btn px-4">
                                        <i class="bi bi-check2-circle me-1"></i> Save Vitals
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Eye Drop Register --}}
                    <div class="card ot-premium-card border-0 mb-4">
                        <div class="ot-card-header">
                            <div class="ot-title-wrap">
                                <span class="ot-title-icon" aria-hidden="true"><i class="bi bi-droplet-half" style="font-size: 1.1rem;"></i></span>
                                <h5 class="ot-title mb-0">Eye Drop Register</h5>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="ot-table-wrap mb-4">
                                <div class="table-responsive">
                                    <table class="table ot-premium-table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th><i class="bi bi-hash me-1"></i>Dose #</th>
                                                <th><i class="bi bi-capsule me-1"></i>Medicine</th>
                                                <th><i class="bi bi-eye me-1"></i>Eye</th>
                                                <th><i class="bi bi-clock-history me-1"></i>Date / Time</th>
                                                <th><i class="bi bi-person-badge me-1"></i>Administered By</th>
                                                <th><i class="bi bi-chat-left-text me-1"></i>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($eyeDrops as $drop)
                                                <tr>
                                                    <td>{{ $drop->dose_number ?? '-' }}</td>
                                                    <td>{{ $drop->medicine_name }}</td>
                                                    <td><span class="badge ot-type-badge">{{ $drop->eye ?? '-' }}</span></td>
                                                    <td>{{ optional($drop->administered_at)->format('d M Y, h:i A') }}</td>
                                                    <td>{{ $drop->administeredBy?->name ?? '-' }}</td>
                                                    <td>{{ $drop->remarks ?? '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center ot-empty">
                                                        <i class="bi bi-inbox me-1"></i> No eye drops logged yet.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('hospital.ot.ward.eye-drops.store', ['slug' => $slug, 'booking' => $booking->id]) }}">
                                @csrf
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label">Medicine Name <span class="text-danger">*</span></label>
                                        <select name="medicine_name" class="form-select" required>
                                            <option value="">Select medicine...</option>
                                            @forelse(($otMedicines ?? collect()) as $med)
                                                <option value="{{ $med->name }}" @selected(old('medicine_name') === $med->name)>
                                                    {{ $med->name }}
                                                </option>
                                            @empty
                                                <option value="" disabled>No ward medicines — add from Ward Medicine</option>
                                            @endforelse
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Eye <span class="text-danger">*</span></label>
                                        @php
    // Autofill / lock from Recommend Surgery (booking.eye); counsellor may have updated it.
    $bookingEye = old('eye', (string) ($booking->eye ?? ''));
    if (in_array($bookingEye, ['RE', 'LE'], true)) {
        $eyeDropOptions = [$bookingEye];
    } elseif ($bookingEye === 'Both') {
        $eyeDropOptions = ['RE', 'LE'];
    } else {
        $eyeDropOptions = ['RE', 'LE'];
    }
    $selectedDropEye = old('eye', $eyeDropOptions[0]);
    if (!in_array($selectedDropEye, $eyeDropOptions, true)) {
        $selectedDropEye = $eyeDropOptions[0];
    }
    $eyeDropLabels = [
        'RE' => 'Right (RE)',
        'LE' => 'Left (LE)',
    ];
                                        @endphp
                                        <select name="eye" class="form-select" required
                                            @if(count($eyeDropOptions) === 1) title="From Recommend Surgery" @endif>
                                            @foreach($eyeDropOptions as $eyeOpt)
                                                <option value="{{ $eyeOpt }}" @selected($selectedDropEye === $eyeOpt)>
                                                    {{ $eyeDropLabels[$eyeOpt] ?? $eyeOpt }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Dose # <span class="text-danger">*</span></label>
                                        <input type="number" name="dose_number" min="1" max="20" class="form-control" value="{{ count($eyeDrops) + 1 }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Date / Time</label>
                                        <input type="datetime-local" name="administered_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Remarks</label>
                                        <input type="text" name="remarks" class="form-control">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-3">
                                    <button type="submit" class="hms-btn hms-btn-primary ot-save-btn px-4">
                                        <i class="bi bi-plus-circle me-1"></i> Log Dose
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Patient Status --}}
                    <div class="card ot-premium-card border-0 mb-4">
                        <div class="ot-card-header">
                            <div class="ot-title-wrap">
                                <span class="ot-title-icon" aria-hidden="true"><i class="bi bi-clipboard2-pulse" style="font-size: 1.1rem;"></i></span>
                                <h5 class="ot-title mb-0">Patient Status</h5>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="{{ route('hospital.ot.ward.vitals.store', ['slug' => $slug, 'booking' => $booking->id]) }}" id="wardPatientStatusForm">
                                @csrf
                                <input type="hidden" name="assign_staff" value="1">
                                @php
    $currentStatus = old('pre_op_status', $preOp->pre_op_status ?? \App\Models\Hospital\OT\OtPreOp::STATUS_PREPARING);
    $statusOptions = [
        \App\Models\Hospital\OT\OtPreOp::STATUS_PREPARING => 'Preparing',
        \App\Models\Hospital\OT\OtPreOp::STATUS_READY_FOR_SURGERY => 'Ready for OT',
        \App\Models\Hospital\OT\OtPreOp::STATUS_HOLD => 'Hold',
        \App\Models\Hospital\OT\OtPreOp::STATUS_COMPLICATED => 'Complicated',
    ];
    $readyStatus = \App\Models\Hospital\OT\OtPreOp::STATUS_READY_FOR_SURGERY;
    $isReadyUi = $currentStatus === $readyStatus;
    $selectedAssistantId = (int) old('ot_assistant_id', $booking->ot_assistant_id);
    $otAssistants = $otAssistants ?? collect();
    $opdDoctorId = (int) ($opdDoctorId ?? $booking->patient?->doctor_id ?? 0);
    $opdDoctorName = $opdDoctorName ?? $booking->patient?->doctor?->name;
                                @endphp
                                @foreach($statusOptions as $value => $label)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input ward-status-radio" type="radio" name="pre_op_status" id="status_{{ $value }}" value="{{ $value }}"
                                               {{ $currentStatus === $value || ($value === 'complicated' && $currentStatus === 'not_fit') ? 'checked' : '' }}
                                               @if($loop->first) required @endif>
                                        <label class="form-check-label" for="status_{{ $value }}">{{ $label }}</label>
                                    </div>
                                @endforeach

                                <div class="row g-3 mt-3">
                                    {{-- Preparing / Hold / Complicated: OPD doctor is auto-assigned (not manual). --}}
                                    <div class="col-md-6" id="wardOpdDoctorWrap" @if($isReadyUi) style="display:none" @endif>
                                        @if($opdDoctorId > 0 && $opdDoctorName)
                                            <input type="text" class="form-control ot-readonly" readonly
                                                value="Dr. {{ $opdDoctorName }}">
                                        @else
                                            <input type="text" class="form-control is-invalid" readonly
                                                value="No OPD doctor on patient">
                                            <div class="invalid-feedback d-block">
                                                Assign an OPD doctor on the patient profile before Preparing / Hold / Complicated.
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-md-6" id="wardAssistantWrap" @if(!$isReadyUi) style="display:none" @endif>
                                        <label class="form-label" for="ward_ot_assistant_id">
                                            OT Assistant
                                            <span class="text-danger ward-assistant-req">*</span>
                                        </label>
                                        <select name="ot_assistant_id" id="ward_ot_assistant_id" class="form-select"
                                            @if($isReadyUi) required @endif>
                                            <option value="">Select OT assistant</option>
                                            @foreach($otAssistants as $assistant)
                                                <option value="{{ $assistant->id }}" {{ $selectedAssistantId === (int) $assistant->id ? 'selected' : '' }}>
                                                    {{ $assistant->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                @error('ot_assistant_id')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror

                                <div class="d-flex justify-content-end mt-3 pt-3 ot-form-actions">
                                    <button type="submit" class="hms-btn hms-btn-primary ot-save-btn px-4">
                                        <i class="bi bi-check2-circle me-1"></i> Save Status
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Hand-off to OT Assistant --}}
                    <div class="card ot-premium-card border-0 mb-4">
                        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 p-4">
                            <div>
                                <div class="ot-handoff-title">Ready to send to OT Assistant?</div>
                            </div>
                            @php
    $canSendReady = in_array($booking->ot_status, [\App\Models\Hospital\OT\OtBooking::STATUS_PAYMENT_VERIFIED, \App\Models\Hospital\OT\OtBooking::STATUS_IN_WARD], true)
        && ($preOp?->pre_op_status ?? null) === \App\Models\Hospital\OT\OtPreOp::STATUS_READY_FOR_SURGERY
        && !empty($booking->ot_assistant_id);
                            @endphp
                            @if($canSendReady)
                                <form method="POST" action="{{ route('hospital.ot.ward.ready', ['slug' => $slug, 'booking' => $booking->id]) }}">
                                    @csrf
                                    <button type="submit" class="hms-btn ot-send-btn px-4">
                                        <i class="bi bi-send-check me-1"></i> READY
                                    </button>
                                </form>
                            @elseif(in_array($booking->ot_status, [\App\Models\Hospital\OT\OtBooking::STATUS_PAYMENT_VERIFIED, \App\Models\Hospital\OT\OtBooking::STATUS_IN_WARD], true))
                                <span class="badge ot-status-badge ot-status-generic">Save Ready + OT Assistant first</span>
                            @else
                                <span class="badge ot-status-badge ot-status-generic text-uppercase">{{ $booking->ot_status }}</span>
                            @endif
                        </div>
                    </div>
                </div>
@endsection

@push('styles')
    <style>
        /*
          OT Ward Preparation — simple design matching the Walk-in register form
          (reception-patient-form.css). CSS-only; Blade/dynamic logic untouched.
        */

        .ot-ward-page {
            --oc-primary: #1B4F72;
            --oc-primary-dark: #154360;
            --oc-accent: #2980B9;
            --oc-success: #27AE60;
            --oc-border: rgba(27, 79, 114, 0.14);
            --oc-input-border: #d1dce6;
            --oc-muted: #64748B;
            --oc-focus: rgba(39, 174, 96, 0.22);
            --oc-soft: #EBF5FB;

            padding: .25rem 0 1.25rem;
            color: #1a2a3a;
        }

        /* ── Page header ─────────────────────────────────────────── */
        .ot-outer-card {
            background: #fff;
            border: 1px solid var(--oc-border);
            border-radius: 10px;
            box-shadow: 0 8px 28px rgba(27, 79, 114, 0.08);
            padding: .85rem 1.25rem;
            margin-bottom: 1rem;
        }

        .ot-header-block {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .75rem;
        }

        .ot-header-title {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-weight: 800;
            font-size: 1.15rem;
            color: var(--oc-primary);
        }

        .ot-breadcrumb {
            margin-top: .25rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .8rem;
            color: var(--oc-muted);
        }

        .ot-breadcrumb a {
            color: var(--oc-muted);
            text-decoration: none;
        }

        .ot-breadcrumb a:hover {
            color: var(--oc-primary);
        }

        .ot-breadcrumb-sep {
            color: #c3c9d3;
        }

        .ot-breadcrumb-current {
            color: var(--oc-primary);
            font-weight: 700;
        }

        .ot-ward-page .hms-btn-outline {
            display: inline-flex;
            align-items: center;
            padding: .4rem .9rem;
            border: 1px solid var(--oc-border);
            border-radius: 6px;
            background: #fff;
            color: var(--oc-primary);
            font-size: .8rem;
            font-weight: 700;
        }

        .ot-ward-page .hms-btn-outline:hover {
            background: var(--oc-soft);
            border-color: var(--oc-primary);
            color: var(--oc-primary);
        }

        /* ── Cards ───────────────────────────────────────────────── */
        .ot-premium-card {
            background: #fff;
            border: 1px solid var(--oc-border) !important;
            border-radius: 10px;
            box-shadow: 0 8px 28px rgba(27, 79, 114, 0.08);
            overflow: hidden;
            margin-bottom: 1rem !important;
        }

        .ot-premium-card > .card-body {
            padding: 1rem 1.25rem 1.25rem !important;
        }

        /* Gradient strip header — same as Walk-in "ADD PATIENT" title */
        .ot-card-header {
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, var(--oc-primary) 0%, var(--oc-accent) 100%);
            padding: .55rem 1rem;
        }

        .ot-title-wrap {
            display: flex;
            align-items: center;
            gap: .6rem;
            min-width: 0;
        }

        .ot-card-header .ot-title-icon {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .ot-card-header .ot-title-icon i {
            font-size: .9rem !important;
        }

        .ot-card-header .ot-title {
            margin: 0;
            color: #fff;
            font-size: .95rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        /* ── Patient banner ──────────────────────────────────────── */
        .ot-patient-banner {
            background: linear-gradient(135deg, var(--oc-soft) 0%, #fff 75%);
        }

        .ot-patient-banner .card-body {
            padding: .9rem 1.25rem !important;
        }

        .ot-patient-banner .ot-title-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: var(--oc-primary);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .ot-patient-banner .ot-title {
            margin: 0;
            color: var(--oc-primary);
            font-size: 1.15rem;
            font-weight: 800;
        }

        .ot-subtitle {
            margin-top: .15rem;
            font-size: .8rem;
            font-weight: 600;
            color: var(--oc-muted);
        }

        .ot-subtitle strong {
            color: var(--oc-primary);
        }

        .ot-booking-meta {
            color: var(--oc-muted);
            font-size: .78rem;
            font-weight: 700;
        }

        .ot-booking-meta .badge {
            padding: .3rem .7rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 800;
        }

        .ot-alert {
            border-radius: 8px;
            font-size: .85rem;
            font-weight: 600;
        }

        /* ── Fields — Walk-in form look ──────────────────────────── */
        .ot-ward-page .form-label {
            display: block;
            margin: 0 0 .35rem;
            font-size: .7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--oc-muted);
            line-height: 1.2;
        }

        .ot-ward-page [class*="col-"]:focus-within > .form-label {
            color: var(--oc-primary);
        }

        .ot-ward-page .form-control,
        .ot-ward-page .form-select {
            min-height: 38px;
            padding: .35rem .65rem;
            font-size: .84rem;
            color: #1a2a3a;
            background-color: #fff;
            border: 1px solid var(--oc-input-border);
            border-radius: 6px;
            box-shadow: none;
        }

        .ot-ward-page .form-control:focus,
        .ot-ward-page .form-select:focus {
            border-color: var(--oc-success);
            box-shadow: 0 0 0 3px var(--oc-focus);
            outline: none;
        }

        /* Read-only values — same as the Walk-in MRD field */
        .ot-ward-page .ot-readonly {
            background: #eef2f6 !important;
            color: var(--oc-primary);
            font-weight: 700;
        }

        .ot-ward-page .select2-container--default .select2-selection--single {
            min-height: 38px !important;
            height: 38px !important;
            display: flex;
            align-items: center;
            padding: 0 .45rem;
            border: 1px solid var(--oc-input-border) !important;
            border-radius: 6px !important;
        }

        .ot-ward-page .select2-container--default .select2-selection--single .select2-selection__rendered {
            font-size: .84rem;
            line-height: 36px !important;
            padding-left: 0 !important;
            color: #1a2a3a !important;
        }

        .ot-ward-page .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }

        .ot-ward-page .select2-container--default.select2-container--focus .select2-selection--single,
        .ot-ward-page .select2-container--default.select2-container--open .select2-selection--single {
            border-color: var(--oc-success) !important;
            box-shadow: 0 0 0 3px var(--oc-focus) !important;
        }

        /* Patient status radios */
        .ot-ward-page .form-check-inline {
            margin-right: 1.1rem;
        }

        .ot-ward-page .form-check-label {
            font-size: .84rem;
            font-weight: 600;
            color: #1a2a3a;
        }

        .ot-ward-page .form-check-input:checked {
            background-color: var(--oc-primary);
            border-color: var(--oc-primary);
        }

        .ot-ward-page .form-check-input:focus {
            border-color: var(--oc-success);
            box-shadow: 0 0 0 3px var(--oc-focus);
        }

        /* Last-recorded strip */
        .ot-total-box {
            background: var(--oc-soft);
            border: 1px solid var(--oc-border);
            border-left: 4px solid var(--oc-primary);
            border-radius: 8px;
            padding: .6rem 1rem;
            font-size: .82rem;
            color: var(--oc-primary);
        }

        .ot-status-badge {
            border-radius: 999px;
            padding: .3rem .7rem;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .02em;
        }

        .ot-status-generic {
            background: #eef2f6;
            border: 1px solid var(--oc-input-border);
            color: var(--oc-primary);
        }

        .ot-type-badge {
            background: var(--oc-soft);
            border: 1px solid var(--oc-border);
            color: var(--oc-primary);
            border-radius: 999px;
            padding: .3rem .65rem;
            font-size: .75rem;
            font-weight: 800;
        }

        /* ── Eye drop table ──────────────────────────────────────── */
        .ot-table-wrap {
            overflow-x: auto;
            border: 1px solid var(--oc-border);
            border-radius: 8px;
        }

        .ot-premium-table {
            width: 100%;
            min-width: 820px;
            margin-bottom: 0;
            border-collapse: collapse;
        }

        .ot-premium-table thead th {
            background: var(--oc-soft) !important;
            color: var(--oc-primary) !important;
            border: 0;
            border-bottom: 1px solid var(--oc-border) !important;
            padding: .6rem .9rem;
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
            white-space: nowrap;
            text-align: left;
        }

        .ot-premium-table tbody td {
            background: transparent;
            border: 0;
            border-bottom: 1px solid #edf1f5;
            padding: .6rem .9rem;
            font-size: .84rem;
            font-weight: 600;
            color: #1a2a3a;
            vertical-align: middle;
            white-space: nowrap;
        }

        .ot-premium-table tbody td:first-child {
            color: var(--oc-primary);
            font-weight: 800;
        }

        .ot-premium-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .ot-premium-table tbody tr:hover td {
            background: #f7fbfe;
        }

        .ot-empty {
            padding: 1.25rem 1rem !important;
            color: var(--oc-muted) !important;
            font-weight: 700;
        }

        /* ── Actions ─────────────────────────────────────────────── */
        .ot-form-actions {
            margin-top: 1rem !important;
            padding-top: .85rem !important;
            border-top: 1px solid var(--oc-border);
        }

        .ot-ward-page .ot-save-btn,
        .ot-ward-page .ot-send-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            min-width: 140px;
            padding: .45rem 1.25rem;
            border: 2px solid var(--oc-primary);
            border-radius: 6px;
            background: var(--oc-primary);
            color: #fff !important;
            font-size: .82rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
        }

        .ot-ward-page .ot-save-btn:hover {
            background: var(--oc-primary-dark);
            border-color: var(--oc-primary-dark);
        }

        .ot-ward-page .ot-save-btn:focus {
            background: var(--oc-success);
            border-color: #1e8449;
            box-shadow: 0 0 0 4px var(--oc-focus);
            outline: none;
        }

        .ot-ward-page .ot-send-btn {
            background: var(--oc-success);
            border-color: var(--oc-success);
        }

        .ot-ward-page .ot-send-btn:hover {
            background: #1e8449;
            border-color: #1e8449;
        }

        .ot-handoff-title {
            font-size: .95rem;
            font-weight: 800;
            color: var(--oc-primary);
        }
    </style>
@endpush

@push('scripts')
<script>
    (function () {
        const readyValue = @json(\App\Models\Hospital\OT\OtPreOp::STATUS_READY_FOR_SURGERY);
        const radios = document.querySelectorAll('.ward-status-radio');
        const opdWrap = document.getElementById('wardOpdDoctorWrap');
        const assistantWrap = document.getElementById('wardAssistantWrap');
        const assistantSelect = document.getElementById('ward_ot_assistant_id');

        function syncUi() {
            const checked = document.querySelector('.ward-status-radio:checked');
            const isReady = checked && checked.value === readyValue;
            if (opdWrap) opdWrap.style.display = isReady ? 'none' : '';
            if (assistantWrap) assistantWrap.style.display = isReady ? '' : 'none';
            if (assistantSelect) {
                assistantSelect.required = !!isReady;
                if (!isReady) {
                    assistantSelect.value = '';
                }
            }
        }

        radios.forEach(function (el) {
            el.addEventListener('change', syncUi);
        });
        syncUi();
    })();
</script>
@endpush
