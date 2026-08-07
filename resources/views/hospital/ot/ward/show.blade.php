@extends('hospital.layouts.app')
@section('title', 'Ward Preparation')
@section('page-header', 'Ward Preparation')

@section('page-actions')
    <a href="{{ route('hospital.ot.ward.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="bi bi-arrow-left me-1"></i> Back to Ward Queue
    </a>
@endsection

@section('content')
<div class="ot-ward-page">

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
                    <button type="submit" class="hms-btn hms-btn-primary px-4">
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
                                <option value="" disabled>No OT medicines in master</option>
                            @endforelse
                        </select>
                        @if(($otMedicines ?? collect())->isEmpty())
                            <div class="form-text text-warning">Add medicines with usage scope OT under Medicine Master.</div>
                        @endif
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
                            if (! in_array($selectedDropEye, $eyeDropOptions, true)) {
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
                    <button type="submit" class="hms-btn hms-btn-primary px-4">
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
                        <label class="form-label">Doctor <span class="text-muted small">(OPD — auto)</span></label>
                        @if($opdDoctorId > 0 && $opdDoctorName)
                            <input type="text" class="form-control ot-readonly" readonly
                                value="Dr. {{ $opdDoctorName }}">
                            <div class="form-text">Auto-assigned from OPD. Appears on this doctor's OT dashboard card.</div>
                        @else
                            <input type="text" class="form-control is-invalid" readonly
                                value="No OPD doctor on patient">
                            <div class="invalid-feedback d-block">
                                Assign an OPD doctor on the patient profile before Preparing / Hold / Complicated.
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6" id="wardAssistantWrap" @if(! $isReadyUi) style="display:none" @endif>
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
                    <button type="submit" class="hms-btn hms-btn-primary px-4">
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
                <div class="ot-title" style="font-size: 1rem;">Ready to send to OT Assistant?</div>
                <div class="ot-subtitle">
                    Set status to Ready for OT, assign OT Assistant, then send.
                </div>
            </div>
            @php
                $canSendReady = in_array($booking->ot_status, [\App\Models\Hospital\OT\OtBooking::STATUS_PAYMENT_VERIFIED, \App\Models\Hospital\OT\OtBooking::STATUS_IN_WARD], true)
                    && ($preOp?->pre_op_status ?? null) === \App\Models\Hospital\OT\OtPreOp::STATUS_READY_FOR_SURGERY
                    && ! empty($booking->ot_assistant_id);
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
      OT Ward Preparation (Design refresh)
      Keep Blade/dynamic logic untouched; CSS-only + layout wrappers.
      Palette follows hospital shell theme (#1B4F72).
    */

    .ot-ward-page {
        --ot-secondary: #1B4F72;
        --ot-s2-06: rgba(27, 79, 114, 0.06);
        --ot-s2-08: rgba(27, 79, 114, 0.08);
        --ot-s2-12: rgba(27, 79, 114, 0.12);
        --ot-s2-18: rgba(27, 79, 114, 0.18);
        --ot-s2-24: rgba(27, 79, 114, 0.24);

        position: relative;
        padding: .25rem 0 1.25rem;
        color: var(--ot-secondary);
        animation: ot-page-in 420ms ease both;
    }

    @keyframes ot-page-in {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .ot-ward-page .btn,
    .ot-ward-page .hms-btn {
        border-radius: 12px;
        font-weight: 800;
        transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, border-color 170ms ease, color 170ms ease;
    }

    .ot-ward-page .btn:hover,
    .ot-ward-page .hms-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 26px rgba(27, 79, 114, 0.14);
    }

    .ot-premium-card {
        background: rgba(255, 255, 255, 0.84);
        border: 1px solid var(--ot-s2-12) !important;
        border-radius: 22px;
        box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
        overflow: hidden;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        animation: ot-card-rise 520ms cubic-bezier(.2,.9,.2,1) both;
    }

    @keyframes ot-card-rise {
        from { opacity: 0; transform: translateY(10px) scale(0.99); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .ot-card-header {
        background:
            linear-gradient(135deg, rgba(235, 245, 251, 0.92), rgba(255, 255, 255, 0.94)),
            #ffffff;
        border-bottom: 1px solid var(--ot-s2-12);
        padding: 1.15rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .ot-title-wrap {
        display: flex;
        align-items: center;
        gap: .85rem;
        min-width: 0;
    }

    .ot-title-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: var(--ot-secondary);
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 14px 30px rgba(27, 79, 114, 0.22);
        flex: 0 0 auto;
    }

    .ot-title {
        font-weight: 900;
        letter-spacing: -0.2px;
        margin: 0;
        color: var(--ot-secondary);
    }

    .ot-subtitle {
        margin: .15rem 0 0;
        font-weight: 650;
        color: rgba(27, 79, 114, 0.72);
        font-size: .85rem;
    }

    .ot-booking-meta {
        color: rgba(27, 79, 114, 0.72);
        font-weight: 700;
        font-size: .88rem;
    }

    .ot-alert { border-radius: 14px; font-weight: 650; }

    .ot-ward-page .form-label {
        font-weight: 800;
        font-size: .82rem;
        color: var(--ot-secondary);
        letter-spacing: .01em;
    }

    .ot-ward-page .form-control,
    .ot-ward-page .form-select {
        border: 1px solid var(--ot-s2-18);
        border-radius: 12px;
        padding: .55rem .85rem;
        background: rgba(255, 255, 255, 0.92);
        color: var(--ot-secondary);
        font-weight: 600;
        transition: border-color 160ms ease, box-shadow 160ms ease;
    }

    .ot-ward-page .form-control:focus,
    .ot-ward-page .form-select:focus {
        border-color: var(--ot-secondary);
        box-shadow: 0 0 0 .2rem var(--ot-s2-12);
    }

    .ot-ward-page .form-check-input:checked {
        background-color: var(--ot-secondary);
        border-color: var(--ot-secondary);
    }

    .ot-form-actions {
        border-top: 1px solid var(--ot-s2-12);
    }

    .ot-total-box {
        background: linear-gradient(135deg, rgba(235, 245, 251, 0.92), rgba(255, 255, 255, 0.94));
        border: 1px solid var(--ot-s2-18);
        border-radius: 14px;
        padding: .8rem 1.1rem;
        color: var(--ot-secondary);
        box-shadow: 0 10px 22px rgba(27, 79, 114, 0.08);
    }

    .ot-status-badge {
        border-radius: 999px;
        padding: .45rem .70rem;
        font-weight: 900;
        letter-spacing: .02em;
    }

    .ot-status-generic {
        background: var(--ot-secondary);
        color: #fff;
    }

    .ot-type-badge {
        background: rgba(27, 79, 114, 0.08);
        border: 1px solid var(--ot-s2-18);
        color: var(--ot-secondary);
        border-radius: 999px;
        padding: .4rem .7rem;
        font-weight: 800;
    }

    .ot-table-wrap {
        overflow-x: auto;
    }

    .ot-premium-table {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0 8px;
        min-width: 820px;
    }

    .ot-premium-table thead,
    .ot-premium-table thead th {
        background: var(--ot-secondary) !important;
    }

    .ot-premium-table thead th {
        color: #ffffff !important;
        border-bottom: none !important;
        font-size: .72rem;
        letter-spacing: .08em;
        font-weight: 900;
        text-transform: uppercase;
        padding-top: .8rem;
        padding-bottom: .8rem;
        white-space: nowrap;
    }

    .ot-premium-table thead th:first-child {
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .ot-premium-table thead th:last-child {
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .ot-premium-table tbody td {
        background: rgba(255, 255, 255, 0.88);
        border-top: 1px solid var(--ot-s2-12);
        border-bottom: 1px solid var(--ot-s2-12);
        padding: .8rem .95rem;
        font-weight: 700;
        color: rgba(27, 79, 114, 0.90);
        vertical-align: middle;
        white-space: nowrap;
    }

    .ot-premium-table tbody td:first-child {
        border-left: 1px solid var(--ot-s2-12);
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
        color: rgba(27, 79, 114, 0.82);
        font-weight: 900;
    }

    .ot-premium-table tbody td:last-child {
        border-right: 1px solid var(--ot-s2-12);
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .ot-premium-table tbody tr:hover td {
        background: rgba(235, 245, 251, 0.78);
        border-color: var(--ot-s2-18);
    }

    .ot-empty {
        padding: 1.5rem 1rem !important;
        color: rgba(27, 79, 114, 0.72) !important;
        font-weight: 800;
    }

    .ot-send-btn {
        background: #1E8E5A;
        border: 1px solid #1E8E5A;
        color: #fff;
    }

    .ot-send-btn:hover {
        background: #17714a;
        border-color: #17714a;
        color: #fff;
    }

    @media (prefers-reduced-motion: reduce) {
        .ot-ward-page,
        .ot-premium-card,
        .ot-ward-page .btn,
        .ot-ward-page .hms-btn {
            animation: none !important;
            transition: none !important;
        }
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
