@extends('hospital.layouts.app')
@section('title', 'Record OT Surgery')
{{-- Layout page-header intentionally unused — heading, breadcrumb and
actions render inside the content card instead, matching the panel design
used across the rest of the app. --}}

@section('content')
        <div class="ot-surgery-page">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="ot-outer-card">
                        <div class="ot-header-block">
                            <div>
                                <div class="ot-header-title"><i class="bi bi-heart-pulse-fill"></i> Record OT Surgery</div>
                                <nav class="ot-breadcrumb" aria-label="breadcrumb">
                                    <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                                    <span class="ot-breadcrumb-sep">/</span>
                                    <a href="{{ route('hospital.ot.assistant.dashboard', ['slug' => $slug]) }}">OT Assistant</a>
                                    <span class="ot-breadcrumb-sep">/</span>
                                    <span class="ot-breadcrumb-current">Booking #{{ $booking->id }}</span>
                                </nav>
                            </div>
                            <a href="{{ route('hospital.ot.assistant.dashboard', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
                                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>

                    <div class="card ot-premium-card border-0 ot-surgery-card">
                        <div class="ot-card-header">
                            <div class="ot-title-wrap">
                                <span class="ot-title-icon" aria-hidden="true">
                                    <i class="bi bi-heart-pulse-fill" style="font-size: 1.2rem;"></i>
                                </span>
                                <div>
                                    <h5 class="mb-1 ot-title">Surgery Recording Form</h5>
                                    <p class="mb-0 ot-subtitle">Complete surgery details and ward medicines in one flow.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-4 p-lg-5">
                            @if($errors->any())
                                <div class="alert alert-danger mb-4 ot-alert">
                                    <ul class="mb-0 ps-3">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form method="POST"
                                action="{{ route('hospital.ot.surgery.store', ['slug' => $slug, 'bookingId' => $booking->id]) }}">
                                @csrf

                            <div class="ot-section mb-4">
                                <div class="ot-section-header">
                                    <h6 class="fw-bold mb-0"><i class="bi bi-person-vcard me-1"></i> A. Patient &amp; Booking Details</h6>
                                </div>
                                <div class="ot-section-body">
                                    <div class="row g-3">
                                        <div class="col-md-6 col-lg-4">
                                            <label class="form-label text-muted">Patient Name</label>
                                            <input type="text" class="form-control ot-readonly"
                                                value="{{ $booking->patient?->full_name ?? '-' }}" readonly>
                                        </div>
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label text-muted">Phone</label>
                                            <input type="text" class="form-control ot-readonly"
                                                value="{{ $booking->patient?->contact_no ?? '-' }}" readonly>
                                        </div>
                                        <div class="col-md-4 col-lg-2">
                                            <label class="form-label">OT Date <span class="text-danger">*</span></label>
                                            <input type="date" name="surgery_date" class="form-control" required
                                                value="{{ old('surgery_date', optional($booking->surgery_date)->format('Y-m-d') ?: now()->format('Y-m-d')) }}">
                                        </div>
                                        <div class="col-md-4 col-lg-2">
                                            <label class="form-label text-muted">Package</label>
                                            <input type="text" class="form-control ot-readonly"
                                                value="{{ money_code((float) ($counselling?->package_amount ?? $booking->package_amount ?? 0), 2) }}"
                                                readonly>
                                        </div>
                                        <div class="col-md-4 col-lg-2">
                                            <label class="form-label text-muted">Mediclaim</label>
                                            <input type="text" class="form-control ot-readonly"
                                                value="{{ ($counselling?->mediclaim ?? $booking->has_mediclaim) ? 'YES' : 'NO' }}"
                                                readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                                <div class="ot-section mb-4">
                                    <div class="ot-section-header">
                                        <h6 class="fw-bold mb-0"><i class="bi bi-scissors me-1"></i> B. Surgery Details</h6>
                                    </div>
                                    <div class="ot-section-body">
                                        <div class="row g-3 mb-1">
                                            <div class="col-md-6 col-lg-4">
                                                <label class="form-label">Surgery Name <span
                                                        class="text-danger">*</span></label>
                                                <select name="surgery_name" class="form-select" required>
                                                    <option value="">Select surgery...</option>
                                                    @foreach($surgeryTypes as $surgeryType)
                                                        <option value="{{ $surgeryType->surgery_name }}" {{ old('surgery_name', $booking->ot_type) === $surgeryType->surgery_name ? 'selected' : '' }}>
                                                            {{ $surgeryType->surgery_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6 col-lg-2">
                                                <label class="form-label d-block mb-2">Eye Operated <span
                                                        class="text-danger">*</span></label>
                                                @php
    // Lock to eye chosen on Recommend Surgery modal (booking.eye).
    $lockedEye = in_array((string) $booking->eye, ['RE', 'LE', 'Both'], true)
        ? (string) $booking->eye
        : 'RE';
    $eyeOptions = [$lockedEye];
    $selectedEye = old('eye_operated', $lockedEye);
    if (!in_array($selectedEye, $eyeOptions, true)) {
        $selectedEye = $lockedEye;
    }
                                                @endphp
                                                <div class="ot-radio-group">
                                                    @foreach($eyeOptions as $eye)
                                                        <div class="form-check form-check-inline ot-radio-pill">
                                                            <input class="form-check-input" type="radio" name="eye_operated"
                                                                id="eye_operated_{{ strtolower($eye) }}" value="{{ $eye }}"
                                                                {{ $selectedEye === $eye ? 'checked' : '' }} required>
                                                            <label class="form-check-label"
                                                                for="eye_operated_{{ strtolower($eye) }}">{{ $eye }}</label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div class="col-md-4 col-lg-2">
                                                <label class="form-label">OT Room</label>
                                                <input type="text" name="ot_room" class="form-control" value="{{ old('ot_room') }}" placeholder="e.g. OT-1">
                                            </div>
                                            <div class="col-md-4 col-lg-2">
                                                <label class="form-label">Start Time</label>
                                                <input type="datetime-local" name="start_time" id="start_time" class="form-control" value="{{ old('start_time') }}">
                                            </div>
                                            <div class="col-md-4 col-lg-2">
                                                <label class="form-label">End Time</label>
                                                <input type="datetime-local" name="end_time" id="end_time" class="form-control" value="{{ old('end_time') }}">
                                            </div>

                                            <div class="col-md-4 col-lg-2">
                                                <label class="form-label">Complication Status <span
                                                        class="text-danger">*</span></label>
                                                <select name="complication_status" id="complication_status" class="form-select"
                                                    required>
                                                    <option value="none" {{ old('complication_status', 'none') === 'none' ? 'selected' : '' }}>None</option>
                                                    <option value="minor" {{ old('complication_status') === 'minor' ? 'selected' : '' }}>Minor</option>
                                                    <option value="major" {{ old('complication_status') === 'major' ? 'selected' : '' }}>Major</option>
                                                </select>
                                            </div>

                                            <div class="col-md-4 col-lg-2">
                                                <label class="form-label">Blood Loss</label>
                                                <input type="text" name="blood_loss" class="form-control" value="{{ old('blood_loss') }}" placeholder="e.g. Minimal, 50ml">
                                            </div>

                                            <div class="col-md-12 col-lg-8">
                                                <label class="form-label">Complication Notes</label>
                                                <textarea name="complication_notes" id="complication_notes" rows="2"
                                                    class="form-control"
                                                    placeholder="Only required if complication status is minor/major">{{ old('complication_notes') }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @php
    $c = $counselling ?? null;
    $lensTypes = $lensTypes ?? \App\Http\Controllers\Hospital\OT\OtAssistantController::LENS_TYPES;
    $selectedLensCost = old('lens_cost', $c->lens_cost ?? '');
    $selectedLensCost = $selectedLensCost !== '' && $selectedLensCost !== null
        ? number_format((float) $selectedLensCost, 2, '.', '')
        : '';
    $selectedLensImplantation = old('lens_implantation', $c && $c->lens_implantation !== null
        ? ($c->lens_implantation ? 'yes' : 'no')
        : '');
                                @endphp
                                <div class="ot-section mb-4">
                                    <div class="ot-section-header">
                                        <h6 class="fw-bold mb-0"><i class="bi bi-eyeglasses me-1"></i> C. Lens Selection</h6>
                                    </div>
                                    <div class="ot-section-body">
                                        <p class="text-muted small mb-3 mb-md-2">
                                            Auto-filled from Counsellor form — confirm or adjust if needed before saving surgery.
                                        </p>
                                        <div class="row g-3">
                                            <div class="col-md-3 col-xl">
                                                <label class="form-label">Lens Category</label>
                                                <select name="lens_category" class="form-select">
                                                    <option value="">Select...</option>
                                                    <option value="standard" @selected(old('lens_category', $c->lens_category ?? '') === 'standard')>Standard</option>
                                                    <option value="premium" @selected(old('lens_category', $c->lens_category ?? '') === 'premium')>Premium</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 col-xl">
                                                <label class="form-label">Lens Company</label>
                                                <input type="text" name="lens_company" class="form-control"
                                                    value="{{ old('lens_company', $c->lens_company ?? '') }}">
                                            </div>
                                            <div class="col-md-3 col-xl">
                                                <label class="form-label">Lens Model</label>
                                                <input type="text" name="lens_model" class="form-control"
                                                    value="{{ old('lens_model', $c->lens_model ?? '') }}">
                                            </div>
                                            <div class="col-md-3 col-xl">
                                                <label class="form-label">Lens Type</label>
                                                <select name="lens_type" class="form-select">
                                                    <option value="">Select...</option>
                                                    @foreach($lensTypes as $type)
                                                        <option value="{{ $type }}" @selected(old('lens_type', $c->lens_type ?? '') === $type)>{{ $type }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3 col-xl">
                                                <label class="form-label">Estimated Power</label>
                                                <input type="number" step="0.01" name="estimated_power" class="form-control"
                                                    value="{{ old('estimated_power', $c->estimated_power ?? '') }}">
                                            </div>
                                            <div class="col-md-3 col-xl">
                                                <label class="form-label">Lens Cost</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">{{ currency_code() }}</span>
                                                    <input type="number" step="0.01" min="0" name="lens_cost" class="form-control"
                                                        value="{{ $selectedLensCost }}"
                                                        placeholder="Enter lens cost">
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-xl">
                                                <label class="form-label d-block">Lens Implantation</label>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="lens_implantation"
                                                        id="lens_implantation_yes" value="yes" {{ $selectedLensImplantation === 'yes' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="lens_implantation_yes">Yes</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="lens_implantation"
                                                        id="lens_implantation_no" value="no" {{ $selectedLensImplantation === 'no' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="lens_implantation_no">No</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="ot-section mb-4">
                                    <div
                                        class="ot-section-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                        <h6 class="fw-bold mb-0"><i class="bi bi-capsule me-1"></i> D. In-Ward Medicines</h6>
                                        <button type="button" id="addWardMedicine" class="btn btn-sm ot-btn-outline">
                                            <i class="bi bi-plus-circle me-1"></i> Add Medicine
                                        </button>
                                    </div>
                                    <div class="ot-section-body">
                                        @if($medicineGroups->isNotEmpty())
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6 col-lg-4">
                                                    <label class="form-label">Quick-fill from OT Medicine Group</label>
                                                    <select id="medicineGroupPicker" name="medicine_group_id" class="form-select">
                                                        <option value="">Select group (optional)...</option>
                                                        @foreach($medicineGroups as $group)
                                                            <option value="{{ $group->id }}"
                                                                data-items="{{ $group->items->map(fn($item) => ['medicine' => $item->medicine?->name, 'dose' => trim(($item->frequency ?? '') . ' ' . ($item->duration ?? ''))])->filter(fn($i) => $i['medicine'])->values()->toJson() }}">
                                                                {{ $group->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        @endif
                                        <div id="otMedicineList">
                                            @php
    $oldOtMeds = old('ot_medicines', []);
    $oldOtMeds = is_array($oldOtMeds) ? $oldOtMeds : [];
                                            @endphp

                                            @forelse($oldOtMeds as $index => $row)
                                                <div class="row g-2 ot-medicine-row mb-2 align-items-end">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Medicine</label>
                                                        <select name="ot_medicines[{{ $index }}][medicine]"
                                                            class="form-select select2-medicine" required>
                                                            <option value="">Select medicine...</option>
                                                            @foreach($medicines as $med)
                                                                <option value="{{ $med->name }}" {{ ($row['medicine'] ?? '') === $med->name ? 'selected' : '' }}>{{ $med->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <label class="form-label">Dose / Frequency</label>
                                                        <input type="text" name="ot_medicines[{{ $index }}][dose]"
                                                            class="form-control" placeholder="Dose / Frequency"
                                                            value="{{ $row['dose'] ?? '' }}">
                                                    </div>
                                                    <div class="col-md-1 d-grid">
                                                        <button type="button" class="btn ot-btn-danger remove-row">X</button>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="row g-2 ot-medicine-row mb-2 align-items-end">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Medicine</label>
                                                        <select name="ot_medicines[0][medicine]"
                                                            class="form-select select2-medicine" required>
                                                            <option value="">Select medicine...</option>
                                                            @foreach($medicines as $med)
                                                                <option value="{{ $med->name }}">{{ $med->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <label class="form-label">Dose / Frequency</label>
                                                        <input type="text" name="ot_medicines[0][dose]" class="form-control"
                                                            placeholder="Dose / Frequency">
                                                    </div>
                                                    <div class="col-md-1 d-grid">
                                                        <button type="button" class="btn ot-btn-danger remove-row">X</button>
                                                    </div>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 pt-3 ot-form-actions">
                                    <a href="{{ route('hospital.ot.assistant.dashboard', ['slug' => $slug]) }}"
                                        class="hms-btn hms-btn-outline">Cancel</a>
                                    <button type="submit" class="hms-btn hms-btn-primary ot-save-btn px-4">
                                        <i class="bi bi-check2-circle me-1"></i> Save Surgery
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection

@push('styles')
    <style>
        /*
          OT Surgery Recording Form — simple design matching the Walk-in register
          form (reception-patient-form.css). CSS-only; Blade/dynamic logic untouched.
        */

        .ot-surgery-page {
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

        .ot-surgery-page .hms-btn-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .4rem .9rem;
            border: 1px solid var(--oc-border);
            border-radius: 6px;
            background: #fff;
            color: var(--oc-primary);
            font-size: .8rem;
            font-weight: 700;
        }

        .ot-surgery-page .hms-btn-outline:hover {
            background: var(--oc-soft);
            border-color: var(--oc-primary);
            color: var(--oc-primary);
        }

        /* ── Card ────────────────────────────────────────────────── */
        .ot-premium-card {
            background: #fff;
            border: 1px solid var(--oc-border) !important;
            border-radius: 10px;
            box-shadow: 0 8px 28px rgba(27, 79, 114, 0.08);
            overflow: hidden;
        }

        .ot-premium-card > .card-body {
            padding: 1rem 1.25rem 1.25rem !important;
        }

        /* Gradient strip header — same as Walk-in "ADD PATIENT" title */
        .ot-card-header {
            background: linear-gradient(135deg, var(--oc-primary) 0%, var(--oc-accent) 100%);
            padding: .55rem 1rem;
        }

        .ot-title-wrap {
            display: flex;
            align-items: center;
            gap: .6rem;
            min-width: 0;
        }

        .ot-title-icon {
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

        .ot-title-icon i {
            font-size: .9rem !important;
        }

        .ot-title {
            margin: 0 !important;
            color: #fff;
            font-size: .95rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .ot-subtitle {
            margin-top: .1rem;
            font-size: .75rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.82);
        }

        .ot-alert {
            border-radius: 8px;
            font-size: .85rem;
            font-weight: 600;
        }

        /* ── Sections A–D ────────────────────────────────────────── */
        .ot-section {
            border: 1px solid var(--oc-border);
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            margin-bottom: 1rem !important;
        }

        .ot-section-header {
            padding: .5rem .9rem;
            background: var(--oc-soft);
            border-bottom: 1px solid var(--oc-border);
            border-left: 3px solid var(--oc-accent);
        }

        .ot-section-header h6 {
            font-size: .78rem;
            font-weight: 800 !important;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--oc-primary);
        }

        .ot-section-body {
            padding: .9rem 1rem 1rem;
        }

        .ot-section-body > p.text-muted {
            font-size: .78rem;
        }

        /* ── Fields — Walk-in form look ──────────────────────────── */
        .ot-surgery-page .form-label {
            display: block;
            margin: 0 0 .35rem;
            font-size: .7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--oc-muted) !important;
            line-height: 1.2;
        }

        .ot-surgery-page [class*="col-"]:focus-within > .form-label {
            color: var(--oc-primary) !important;
        }

        .ot-surgery-page .form-control,
        .ot-surgery-page .form-select {
            min-height: 38px;
            padding: .35rem .65rem;
            font-size: .84rem;
            color: #1a2a3a;
            background-color: #fff;
            border: 1px solid var(--oc-input-border);
            border-radius: 6px;
            box-shadow: none;
        }

        /* Notes sit beside status/blood loss — one line, still resizable */
        .ot-surgery-page textarea.form-control {
            height: 38px;
            min-height: 38px;
            resize: vertical;
        }

        .ot-surgery-page .form-control:focus,
        .ot-surgery-page .form-select:focus {
            border-color: var(--oc-success);
            box-shadow: 0 0 0 3px var(--oc-focus);
            outline: none;
        }

        /* Read-only values — same as the Walk-in MRD field */
        .ot-surgery-page .ot-readonly {
            background: #eef2f6 !important;
            color: var(--oc-primary);
            font-weight: 700;
        }

        .ot-surgery-page .input-group-text {
            min-height: 38px;
            padding: .35rem .6rem;
            font-size: .75rem;
            font-weight: 800;
            color: var(--oc-primary);
            background: var(--oc-soft);
            border: 1px solid var(--oc-input-border);
            border-radius: 6px 0 0 6px;
        }

        .ot-surgery-page .input-group .form-control {
            border-radius: 0 6px 6px 0;
        }

        .ot-surgery-page .select2-container--default .select2-selection--single {
            min-height: 38px !important;
            height: 38px !important;
            display: flex;
            align-items: center;
            padding: 0 .45rem;
            border: 1px solid var(--oc-input-border) !important;
            border-radius: 6px !important;
        }

        .ot-surgery-page .select2-container--default .select2-selection--single .select2-selection__rendered {
            font-size: .84rem;
            line-height: 36px !important;
            padding-left: 0 !important;
            color: #1a2a3a !important;
        }

        .ot-surgery-page .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }

        .ot-surgery-page .select2-container--default.select2-container--focus .select2-selection--single,
        .ot-surgery-page .select2-container--default.select2-container--open .select2-selection--single {
            border-color: var(--oc-success) !important;
            box-shadow: 0 0 0 3px var(--oc-focus) !important;
        }

        /* Radios — Eye operated pill + Yes/No */
        .ot-surgery-page .form-check-label {
            font-size: .84rem;
            font-weight: 600;
            color: #1a2a3a;
        }

        .ot-surgery-page .form-check-input:checked {
            background-color: var(--oc-primary);
            border-color: var(--oc-primary);
        }

        .ot-surgery-page .form-check-input:focus {
            border-color: var(--oc-success);
            box-shadow: 0 0 0 3px var(--oc-focus);
        }

        .ot-radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .ot-radio-pill {
            margin: 0;
            min-height: 38px;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .35rem .9rem .35rem 2.1rem;
            border: 1px solid var(--oc-input-border);
            border-radius: 6px;
            background: #fff;
        }

        .ot-radio-pill:has(.form-check-input:checked) {
            background: var(--oc-soft);
            border-color: var(--oc-primary);
        }

        .ot-radio-pill .form-check-label {
            font-weight: 800;
            color: var(--oc-primary);
        }

        /* In-ward medicine rows */
        .ot-medicine-row {
            margin-left: 0 !important;
            margin-right: 0 !important;
            padding: .6rem .4rem;
            border: 1px dashed #c9d6e2;
            border-radius: 8px;
            background: #fafcfe;
        }

        .ot-surgery-page .ot-btn-outline {
            padding: .3rem .75rem;
            border: 1px solid var(--oc-primary);
            border-radius: 6px;
            background: #fff;
            color: var(--oc-primary);
            font-size: .75rem;
            font-weight: 800;
        }

        .ot-surgery-page .ot-btn-outline:hover {
            background: var(--oc-primary);
            color: #fff;
        }

        .ot-surgery-page .ot-btn-danger {
            min-height: 38px;
            border: 1px solid rgba(231, 76, 60, 0.35);
            border-radius: 6px;
            background: #fdf0ef;
            color: #e74c3c;
            font-weight: 800;
        }

        .ot-surgery-page .ot-btn-danger:hover {
            background: #e74c3c;
            border-color: #e74c3c;
            color: #fff;
        }

        /* ── Actions ─────────────────────────────────────────────── */
        .ot-form-actions {
            margin-top: .25rem;
            padding-top: .85rem !important;
            border-top: 1px solid var(--oc-border);
        }

        .ot-surgery-page .ot-save-btn {
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

        .ot-surgery-page .ot-save-btn:hover {
            background: var(--oc-primary-dark);
            border-color: var(--oc-primary-dark);
        }

        .ot-surgery-page .ot-save-btn:focus {
            background: var(--oc-success);
            border-color: #1e8449;
            box-shadow: 0 0 0 4px var(--oc-focus);
            outline: none;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const list = document.getElementById('otMedicineList');
            const addBtn = document.getElementById('addWardMedicine');
            const complicationStatus = document.getElementById('complication_status');
            const complicationNotes = document.getElementById('complication_notes');
            const medicineOptionsHtml = @json('<option value="">Select medicine...</option>' . $medicines->map(fn($med) => '<option value="' . e($med->name) . '">' . e($med->name) . '</option>')->implode(''));

            function initMedicineSelects(scope) {
                if (window.jQuery && jQuery.fn.select2) {
                    jQuery(scope).find('.select2-medicine').select2({
                        width: '100%',
                        placeholder: 'Select medicine...'
                    });
                }
            }

            function attachRemoveHandlers() {
                list.querySelectorAll('.remove-row').forEach(function (btn) {
                    btn.onclick = function () {
                        const rows = list.querySelectorAll('.ot-medicine-row');
                        if (rows.length === 1) {
                            rows[0].querySelectorAll('input, select').forEach(function (field) {
                                if (field.tagName === 'SELECT') {
                                    field.selectedIndex = 0;
                                    if (window.jQuery && jQuery.fn.select2) {
                                        jQuery(field).trigger('change');
                                    }
                                } else {
                                    field.value = '';
                                }
                            });
                            return;
                        }
                        btn.closest('.ot-medicine-row').remove();
                        reindexRows();
                    };
                });
            }

            function reindexRows() {
                list.querySelectorAll('.ot-medicine-row').forEach(function (row, index) {
                    const medicineInput = row.querySelector('select[name*="[medicine]"], input[name*="[medicine]"]');
                    const doseInput = row.querySelector('select[name*="[dose]"], input[name*="[dose]"]');
                    medicineInput.name = `ot_medicines[${index}][medicine]`;
                    doseInput.name = `ot_medicines[${index}][dose]`;
                });
            }

            function addWardMedicineRow(medicineName, dose) {
                const index = list.querySelectorAll('.ot-medicine-row').length;
                const row = document.createElement('div');
                row.className = 'row g-2 ot-medicine-row mb-2';
                row.innerHTML = `
                <div class="col-md-6">
                    <select name="ot_medicines[${index}][medicine]" class="form-select select2-medicine" required>
                        ${medicineOptionsHtml}
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="text" name="ot_medicines[${index}][dose]" class="form-control" placeholder="Dose / Frequency">
                </div>
                <div class="col-md-1 d-grid">
                    <button type="button" class="btn ot-btn-danger remove-row">X</button>
                </div>
            `;
                list.appendChild(row);
                if (medicineName) {
                    row.querySelector('select').value = medicineName;
                }
                if (dose) {
                    row.querySelector('input').value = dose;
                }
                initMedicineSelects(row);
                attachRemoveHandlers();
            }

            addBtn.addEventListener('click', function () {
                addWardMedicineRow(null, null);
            });

            // ── OT Medicine Group quick-fill (Phase 4) ──────────────────────
            const medicineGroupPicker = document.getElementById('medicineGroupPicker');
            if (medicineGroupPicker) {
                medicineGroupPicker.addEventListener('change', function () {
                    const selected = this.options[this.selectedIndex];
                    const itemsJson = selected ? selected.dataset.items : null;
                    if (!itemsJson) { return; }

                    let items = [];
                    try { items = JSON.parse(itemsJson); } catch (e) { items = []; }
                    if (!items.length) { return; }

                    list.innerHTML = '';
                    items.forEach(function (item) {
                        addWardMedicineRow(item.medicine, item.dose);
                    });
                });
            }

            function toggleComplicationNotesRequired() {
                const required = complicationStatus.value !== 'none';
                complicationNotes.required = required;
            }

            complicationStatus.addEventListener('change', toggleComplicationNotesRequired);
            toggleComplicationNotesRequired();
            attachRemoveHandlers();
            initMedicineSelects(document);
        });
    </script>
@endpush