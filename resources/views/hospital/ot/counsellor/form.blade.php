@extends('hospital.layouts.app')
@section('title', 'OT Counselling')
{{-- Layout page-header intentionally unused — heading, breadcrumb and
actions render inside the content card instead, matching the panel design
used across the rest of the app. --}}

@section('content')
                    <div class="ot-counselling-page">
                        <div class="ot-outer-card">
                            <div class="ot-header-block">
                                <div>
                                    <div class="ot-header-title"><i class="bi bi-chat-left-heart"></i> OT Counselling</div>
                                    <nav class="ot-breadcrumb" aria-label="breadcrumb">
                                        <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                                        <span class="ot-breadcrumb-sep">/</span>
                                        <a href="{{ route('hospital.ot.counsellor.dashboard', ['slug' => $slug]) }}">Counselling</a>
                                        <span class="ot-breadcrumb-sep">/</span>
                                        <span class="ot-breadcrumb-current">Booking #{{ $booking->id }}</span>
                                    </nav>
                                </div>
                                <a href="{{ route('hospital.ot.counsellor.dashboard', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
                                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                                </a>
                            </div>
                        </div>

                        <div class="card ot-premium-card ot-patient-banner border-0 mb-4">
                            <div class="card-body d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center">
                                <div class="ot-title-wrap">
                                    <span class="ot-title-icon" aria-hidden="true">
                                        <i class="bi bi-chat-left-heart" style="font-size: 1.2rem;"></i>
                                    </span>
                                    @php
                                        $bannerChips = array_filter([
                                            ['bi-upc-scan', 'UHID ' . ($booking->patient?->patient_code ?? '-')],
                                            $booking->patient?->contact_no ? ['bi-telephone', $booking->patient->contact_no] : null,
                                            $booking->eye ? ['bi-eye', 'Eye ' . $booking->eye] : null,
                                            $booking->ot_type ? ['bi-bandaid', $booking->ot_type] : null,
                                            $booking->otDoctor?->name ? ['bi-person-badge', 'Dr. ' . $booking->otDoctor->name] : null,
                                        ]);
                                        $isRecommended = $booking->ot_status === \App\Models\Hospital\OT\OtBooking::STATUS_SURGERY_RECOMMENDED;
                                    @endphp
                                    <div class="min-w-0">
                                        <h4 class="mb-1 ot-title">{{ $booking->patient?->full_name ?? 'Patient' }}</h4>
                                        <div class="ot-banner-chips">
                                            @foreach($bannerChips as [$chipIcon, $chipText])
                                                <span class="ot-banner-chip"><i class="bi {{ $chipIcon }}"></i> {{ $chipText }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="ot-booking-meta">
                                    <span class="ot-banner-status {{ $isRecommended ? 'is-recommended' : '' }}">
                                        {{ $isRecommended ? 'Surgery Recommended' : str((string) $booking->ot_status)->replace('_', ' ')->title() }}
                                    </span>
                                    <div class="ot-booking-meta-line">
                                        Booking #{{ $booking->id }}
                                        @if($booking->surgery_date)
                                            &middot; {{ $booking->surgery_date->format('d M Y') }}
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

                        {{-- ===================== Counselling Form ===================== --}}
                        <div class="card ot-premium-card border-0 mb-4">
                            <div class="ot-card-header">
                                <div class="ot-title-wrap">
                                    <span class="ot-title-icon" aria-hidden="true"><i class="bi bi-clipboard2-pulse"
                                            style="font-size: 1.2rem;"></i></span>
                                    <h5 class="ot-title mb-0">Diagnosis, Lens &amp; Package</h5>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <form method="POST"
                                    action="{{ route('hospital.ot.counsellor.counselling.store', ['slug' => $slug, 'bookingId' => $booking->id]) }}">
                                    @csrf

                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Diagnosis</label>
                                            <input type="text" name="diagnosis" class="form-control"
                                                value="{{ old('diagnosis', $counselling->diagnosis ?? '') }}"
                                                placeholder="e.g. Senile Cataract">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Eye <span class="text-danger">*</span></label>
                                            @php $eyeVal = old('eye', $booking->eye ?? ''); @endphp
                                            <select name="eye" class="form-select" required>
                                                <option value="">Select eye</option>
                                                <option value="RE" {{ $eyeVal === 'RE' ? 'selected' : '' }}>Right (RE)</option>
                                                <option value="LE" {{ $eyeVal === 'LE' ? 'selected' : '' }}>Left (LE)</option>
                                                <option value="Both" {{ $eyeVal === 'Both' ? 'selected' : '' }}>Both</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Surgery Type <span class="text-danger">*</span></label>
                                            @php $otTypeVal = old('ot_type', $booking->ot_type ?? ''); @endphp
                                            <select name="ot_type" class="form-select" required>
                                                <option value="">Select surgery</option>
                                                @foreach($otSurgeryTypes as $stype)
                                                    <option value="{{ $stype->surgery_name }}" {{ $otTypeVal === $stype->surgery_name ? 'selected' : '' }}>
                                                        {{ $stype->surgery_name }}
                                                    </option>
                                                @endforeach
                                                @if($otTypeVal !== '' && !$otSurgeryTypes->contains('surgery_name', $otTypeVal))
                                                    <option value="{{ $otTypeVal }}" selected>{{ $otTypeVal }}</option>
                                                @endif
                                            </select>
                                        </div>
                                    </div>

                                    <hr class="my-4">
                                    <h6 class="ot-section-title mb-3"><i class="bi bi-eyeglasses me-1"></i> Lens Selection</h6>
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Lens Category</label>
                                            <select name="lens_category" class="form-select">
                                                <option value="">Select...</option>
                                                <option value="standard" {{ old('lens_category', $counselling->lens_category ?? '') === 'standard' ? 'selected' : '' }}>Standard</option>
                                                <option value="premium" {{ old('lens_category', $counselling->lens_category ?? '') === 'premium' ? 'selected' : '' }}>Premium</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Lens Company</label>
                                            <input type="text" name="lens_company" class="form-control"
                                                value="{{ old('lens_company', $counselling->lens_company ?? '') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Lens Model</label>
                                            <input type="text" name="lens_model" class="form-control"
                                                value="{{ old('lens_model', $counselling->lens_model ?? '') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Lens Type</label>
                                            <select name="lens_type" class="form-select">
                                                <option value="">Select...</option>
                                                @foreach(['Accommodating', 'Aspheric', 'EDOF', 'Monofocal', 'Multifocal', 'Spherical', 'Toric', 'Trifocal'] as $type)
                                                    <option value="{{ $type }}" {{ old('lens_type', $counselling->lens_type ?? '') === $type ? 'selected' : '' }}>{{ $type }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Estimated Power</label>
                                            <input type="number" step="0.01" name="estimated_power" class="form-control"
                                                value="{{ old('estimated_power', $counselling->estimated_power ?? '') }}">
                                        </div>
                                    </div>

                                    <hr class="my-4">
                                    <h6 class="ot-section-title mb-3"><i class="bi bi-bag-check me-1"></i> Package &amp; Cost Estimate</h6>
                                    <div class="row g-3">
                                        @php
    $selectedPackageName = old('package_name', $counselling->package_name ?? '');
    $selectedRoom = old('room_category', $counselling->room_category ?? '');
    $selectedLensCost = old('lens_cost', $counselling->lens_cost ?? '');
    $selectedLensCost = $selectedLensCost !== '' && $selectedLensCost !== null
        ? number_format((float) $selectedLensCost, 2, '.', '')
        : '';
    $matchedPackageId = null;
    foreach ($packageCostOptions as $pkgOpt) {
        if (
            $selectedPackageName !== ''
            && $pkgOpt->package_name === $selectedPackageName
            && ($selectedRoom === '' || $selectedRoom === $pkgOpt->room_category)
        ) {
            $matchedPackageId = $pkgOpt->id;
            if ($selectedRoom === $pkgOpt->room_category) {
                break;
            }
        }
    }
    $totalEstimateDisplay = (
        (float) old('ot_charges', $counselling->ot_charges ?? 0)
        + (float) old('surgeon_charges', $counselling->surgeon_charges ?? 0)
        + (float) old('nursing_charges', $counselling->nursing_charges ?? 0)
        + (float) old('consumables_charges', $counselling->consumables_charges ?? 0)
        + (float) old('lens_cost', $counselling->lens_cost ?? 0)
    );
    if ($totalEstimateDisplay <= 0 && !empty($counselling?->total_estimate)) {
        $totalEstimateDisplay = (float) $counselling->total_estimate;
    }
                                        @endphp
                                        <div class="col-md-4">
                                            <label class="form-label">OT Package</label>
                                            <select id="ot_package_select" class="form-select">
                                                <option value="">Select package...</option>
                                                @foreach($packageCostOptions as $pkgOpt)
                                                    @php
        $optLabel = $pkgOpt->package_name
            . ' · ' . ucfirst((string) $pkgOpt->room_category);
                                                    @endphp
                                                    <option value="{{ $pkgOpt->id }}"
                                                        data-package-name="{{ $pkgOpt->package_name }}"
                                                        data-room="{{ $pkgOpt->room_category }}"
                                                        data-ot-charges="{{ number_format((float) $pkgOpt->ot_charges, 2, '.', '') }}"
                                                        data-surgeon-charges="{{ number_format((float) $pkgOpt->surgeon_charges, 2, '.', '') }}"
                                                        data-nursing-charges="{{ number_format((float) $pkgOpt->nursing_charges, 2, '.', '') }}"
                                                        data-consumables-charges="{{ number_format((float) $pkgOpt->consumables_charges, 2, '.', '') }}"
                                                        {{ (int) $matchedPackageId === (int) $pkgOpt->id ? 'selected' : '' }}>
                                                        {{ $optLabel }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="package_name" id="package_name"
                                                value="{{ $selectedPackageName }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Room Category</label>
                                            <select name="room_category" id="room_category" class="form-select">
                                                <option value="">Select...</option>
                                                <option value="general" {{ $selectedRoom === 'general' ? 'selected' : '' }}>General</option>
                                                <option value="private" {{ $selectedRoom === 'private' ? 'selected' : '' }}>Private</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">OT Charges</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ currency_code() }}</span>
                                                <input type="number" step="0.01" min="0" name="ot_charges" id="ot_charges"
                                                    class="form-control ot-cost-input"
                                                    value="{{ old('ot_charges', $counselling->ot_charges ?? '') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Surgeon Charges</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ currency_code() }}</span>
                                                <input type="number" step="0.01" min="0" name="surgeon_charges" id="surgeon_charges"
                                                    class="form-control ot-cost-input"
                                                    value="{{ old('surgeon_charges', $counselling->surgeon_charges ?? '') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Nursing Charges</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ currency_code() }}</span>
                                                <input type="number" step="0.01" min="0" name="nursing_charges" id="nursing_charges"
                                                    class="form-control ot-cost-input"
                                                    value="{{ old('nursing_charges', $counselling->nursing_charges ?? '') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Consumables</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ currency_code() }}</span>
                                                <input type="number" step="0.01" min="0" name="consumables_charges" id="consumables_charges"
                                                    class="form-control ot-cost-input"
                                                    value="{{ old('consumables_charges', $counselling->consumables_charges ?? '') }}">
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Lens Cost</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ currency_code() }}</span>
                                                <input type="number" step="0.01" min="0" name="lens_cost" id="lens_cost" class="form-control ot-cost-input"
                                                    value="{{ $selectedLensCost }}" placeholder="Enter lens cost">
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="ot-total-box d-flex justify-content-between align-items-center">
                                                <span class="fw-bold">Total Estimate</span>
                                                <span class="fw-bold fs-5" id="otTotalEstimate">{{ currency_code() }}
                                                    {{ number_format($totalEstimateDisplay, 2) }}</span>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label d-block">Mediclaim <span class="text-danger">*</span></label>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="mediclaim" id="mediclaim_yes" value="1"
                                                    {{ old('mediclaim', $counselling->mediclaim ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="mediclaim_yes">Yes</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="mediclaim" id="mediclaim_no" value="0"
                                                    {{ old('mediclaim', $counselling->mediclaim ?? false) ? '' : 'checked' }}>
                                                <label class="form-check-label" for="mediclaim_no">No</label>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Payment Mode</label>
                                            <select name="payment_mode" id="ot_payment_mode" class="form-select">
                                                <option value="">Select...</option>
                                                <option value="cash" data-mediclaim="0" {{ old('payment_mode', $counselling->payment_mode ?? '') === 'cash' ? 'selected' : '' }}>Cash</option>
                                                <option value="online" data-mediclaim="0" {{ old('payment_mode', $counselling->payment_mode ?? '') === 'online' ? 'selected' : '' }}>Online</option>
                                                <option value="mediclaim" data-mediclaim="1" {{ old('payment_mode', $counselling->payment_mode ?? '') === 'mediclaim' ? 'selected' : '' }}>Mediclaim</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label d-block">Blood reports verified</label>
                                            @php $bloodVerified = old('blood_reports_verified', $counselling->blood_reports_verified ?? false); @endphp
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="blood_reports_verified"
                                                    id="blood_verified_yes" value="1" {{ $bloodVerified ? 'checked' : '' }}>
                                                <label class="form-check-label" for="blood_verified_yes">Yes</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="blood_reports_verified"
                                                    id="blood_verified_no" value="0" {{ $bloodVerified ? '' : 'checked' }}>
                                                <label class="form-check-label" for="blood_verified_no">No</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label d-block">Blood reports normal</label>
                                            @php $bloodNormal = old('blood_reports_normal', $counselling->blood_reports_normal ?? false); @endphp
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="blood_reports_normal"
                                                    id="blood_normal_yes" value="1" {{ $bloodNormal ? 'checked' : '' }}>
                                                <label class="form-check-label" for="blood_normal_yes">Yes</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="blood_reports_normal"
                                                    id="blood_normal_no" value="0" {{ $bloodNormal ? '' : 'checked' }}>
                                                <label class="form-check-label" for="blood_normal_no">No</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 ot-form-actions">
                                        <button type="submit" class="hms-btn hms-btn-primary ot-save-btn px-4">
                                            <i class="bi bi-check2-circle me-1"></i> Save Counselling
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- ===================== Consent Form ===================== --}}
                        <div class="card ot-premium-card border-0 mb-4">
                            <div class="ot-card-header">
                                <div class="ot-title-wrap">
                                    <span class="ot-title-icon" aria-hidden="true"><i class="bi bi-pen"
                                            style="font-size: 1.2rem;"></i></span>
                                    <h5 class="ot-title mb-0">Informed Consent</h5>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <form method="POST"
                                    action="{{ route('hospital.ot.counsellor.consent.store', ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                                    id="consentForm">
                                    @csrf
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="consent_given" id="consent_given"
                                                    value="1" {{ old('consent_given', $consent->consent_given ?? false) ? 'checked' : '' }}
                                                    required>
                                                <label class="form-check-label fw-bold" for="consent_given">Patient has given informed
                                                    consent for surgery</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Witness Name</label>
                                            <input type="text" name="witness_name" class="form-control"
                                                value="{{ old('witness_name', $consent->witness_name ?? '') }}">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label d-flex justify-content-between">
                                                Patient Signature
                                                <button type="button" class="btn btn-sm btn-link p-0 ot-clear-pad"
                                                    data-target="patientSignaturePad">Clear</button>
                                            </label>
                                            <div class="ot-signature-wrap">
                                                <canvas id="patientSignaturePad" class="ot-signature-pad"></canvas>
                                            </div>
                                            <input type="hidden" name="patient_signature" id="patient_signature_input">
                                            @if(!empty($consent?->patient_signature_path))
                                                <div class="small text-muted mt-1"><i class="bi bi-check2-circle text-success"></i> Signature
                                                    already on file — draw again to replace.</div>
                                            @endif
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label d-flex justify-content-between">
                                                Guardian Signature (optional)
                                                <button type="button" class="btn btn-sm btn-link p-0 ot-clear-pad"
                                                    data-target="guardianSignaturePad">Clear</button>
                                            </label>
                                            <div class="ot-signature-wrap">
                                                <canvas id="guardianSignaturePad" class="ot-signature-pad"></canvas>
                                            </div>
                                            <input type="hidden" name="guardian_signature" id="guardian_signature_input">
                                            @if(!empty($consent?->guardian_signature_path))
                                                <div class="small text-muted mt-1"><i class="bi bi-check2-circle text-success"></i> Signature
                                                    already on file — draw again to replace.</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 ot-form-actions">
                                        <button type="submit" class="hms-btn hms-btn-primary ot-save-btn px-4">
                                            <i class="bi bi-check2-circle me-1"></i> Save Consent
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- ===================== Send to Billing ===================== --}}
                        <div class="card ot-premium-card border-0 mb-4">
                            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 p-4">
                                <div>
                                    <div class="ot-billing-title">Ready for Billing?</div>
                                    <div class="ot-subtitle">Requires counselling saved and consent given.</div>
                                </div>
                                <form method="POST"
                                    action="{{ route('hospital.ot.counsellor.send-to-billing', ['slug' => $slug, 'bookingId' => $booking->id]) }}">
                                    @csrf
                                    <button type="submit" class="hms-btn ot-billing-btn px-4" {{ $booking->ot_status === \App\Models\Hospital\OT\OtBooking::STATUS_COUNSELLED ? 'disabled' : '' }}>
                                        <i class="bi bi-send-check me-1"></i>
                                        {{ $booking->ot_status === \App\Models\Hospital\OT\OtBooking::STATUS_COUNSELLED ? 'Already Sent to Billing' : 'Send to Billing' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
@endsection

@push('styles')
    <style>
        /*
          OT Counselling Form — simple design matching the Walk-in register form
          (reception-patient-form.css). CSS-only; Blade/dynamic logic untouched.
        */

        .ot-counselling-page {
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

        .ot-header-block .hms-btn-outline {
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

        .ot-header-block .hms-btn-outline:hover {
            background: var(--oc-soft);
            border-color: var(--oc-primary);
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

        .ot-banner-chips {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            margin-top: .3rem;
        }

        .ot-banner-chip {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .2rem .6rem;
            border-radius: 999px;
            background: #fff;
            border: 1px solid var(--oc-border);
            color: var(--oc-primary);
            font-size: .75rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .ot-banner-chip i {
            opacity: .7;
        }

        .ot-booking-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: .35rem;
            color: var(--oc-muted);
            font-size: .78rem;
            font-weight: 700;
        }

        @media (min-width: 768px) {
            .ot-booking-meta {
                align-items: flex-end;
            }
        }

        .ot-banner-status {
            display: inline-block;
            padding: .3rem .75rem;
            border-radius: 999px;
            background: var(--oc-primary);
            color: #fff;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .ot-banner-status.is-recommended {
            background: #E67E22;
        }

        .ot-alert {
            border-radius: 8px;
            font-size: .85rem;
            font-weight: 600;
        }

        /* ── Fields — Walk-in form look ──────────────────────────── */
        .ot-counselling-page .form-label {
            display: block;
            margin: 0 0 .35rem;
            font-size: .7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--oc-muted);
            line-height: 1.2;
        }

        .ot-counselling-page [class*="col-"]:focus-within > .form-label {
            color: var(--oc-primary);
        }

        .ot-counselling-page .form-control,
        .ot-counselling-page .form-select {
            min-height: 38px;
            padding: .35rem .65rem;
            font-size: .84rem;
            color: #1a2a3a;
            background-color: #fff;
            border: 1px solid var(--oc-input-border);
            border-radius: 6px;
            box-shadow: none;
        }

        .ot-counselling-page .form-control:focus,
        .ot-counselling-page .form-select:focus {
            border-color: var(--oc-success);
            box-shadow: 0 0 0 3px var(--oc-focus);
            outline: none;
        }

        .ot-counselling-page .input-group-text {
            min-height: 38px;
            padding: .35rem .6rem;
            font-size: .75rem;
            font-weight: 800;
            color: var(--oc-primary);
            background: var(--oc-soft);
            border: 1px solid var(--oc-input-border);
            border-radius: 6px 0 0 6px;
        }

        .ot-counselling-page .input-group .form-control {
            border-radius: 0 6px 6px 0;
        }

        .ot-counselling-page .select2-container--default .select2-selection--single {
            min-height: 38px !important;
            height: 38px !important;
            display: flex;
            align-items: center;
            padding: 0 .45rem;
            border: 1px solid var(--oc-input-border) !important;
            border-radius: 6px !important;
        }

        .ot-counselling-page .select2-container--default .select2-selection--single .select2-selection__rendered {
            font-size: .84rem;
            line-height: 36px !important;
            padding-left: 0 !important;
            color: #1a2a3a !important;
        }

        .ot-counselling-page .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }

        .ot-counselling-page .select2-container--default.select2-container--focus .select2-selection--single,
        .ot-counselling-page .select2-container--default.select2-container--open .select2-selection--single {
            border-color: var(--oc-success) !important;
            box-shadow: 0 0 0 3px var(--oc-focus) !important;
        }

        /* Yes / No radios */
        .ot-counselling-page .form-check-inline {
            margin-right: 1rem;
        }

        .ot-counselling-page .form-check-label {
            font-size: .84rem;
            font-weight: 600;
            color: #1a2a3a;
        }

        .ot-counselling-page .form-check-input:checked {
            background-color: var(--oc-primary);
            border-color: var(--oc-primary);
        }

        .ot-counselling-page .form-check-input:focus {
            border-color: var(--oc-success);
            box-shadow: 0 0 0 3px var(--oc-focus);
        }

        /* Section sub-headings inside a card */
        .ot-counselling-page hr {
            margin: 1.15rem 0 .85rem !important;
            border-top: 1px dashed var(--oc-border);
            opacity: 1;
        }

        .ot-section-title {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin-bottom: .75rem !important;
            padding-left: .55rem;
            border-left: 3px solid var(--oc-accent);
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--oc-primary);
        }

        /* Total estimate */
        .ot-total-box {
            background: var(--oc-soft);
            border: 1px solid var(--oc-border);
            border-left: 4px solid var(--oc-primary);
            border-radius: 8px;
            padding: .7rem 1rem;
            color: var(--oc-primary);
        }

        .ot-total-box > span:first-child {
            font-size: .75rem;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        /* ── Actions ─────────────────────────────────────────────── */
        .ot-form-actions {
            margin-top: 1rem !important;
            padding-top: .85rem !important;
            border-top: 1px solid var(--oc-border);
        }

        .ot-counselling-page .ot-save-btn,
        .ot-counselling-page .ot-billing-btn {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            min-width: 140px;
            justify-content: center;
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

        .ot-counselling-page .ot-save-btn:hover {
            background: var(--oc-primary-dark);
            border-color: var(--oc-primary-dark);
        }

        .ot-counselling-page .ot-save-btn:focus {
            background: var(--oc-success);
            border-color: #1e8449;
            box-shadow: 0 0 0 4px var(--oc-focus);
            outline: none;
        }

        .ot-counselling-page .ot-billing-btn {
            background: var(--oc-success);
            border-color: var(--oc-success);
        }

        .ot-counselling-page .ot-billing-btn:hover {
            background: #1e8449;
            border-color: #1e8449;
        }

        .ot-counselling-page .ot-billing-btn:disabled {
            background: #eef2f6;
            border-color: var(--oc-input-border);
            color: var(--oc-muted) !important;
            cursor: not-allowed;
        }

        .ot-billing-title {
            font-size: .95rem;
            font-weight: 800;
            color: var(--oc-primary);
        }

        .ot-subtitle {
            margin-top: .15rem;
            font-size: .8rem;
            font-weight: 600;
            color: var(--oc-muted);
        }

        /* ── Signature pads (JS relies on .ot-signature-wrap / .is-signed) ── */
        .ot-clear-pad {
            font-size: .72rem;
            font-weight: 700;
            color: #e74c3c !important;
            text-decoration: none;
            text-transform: none;
            letter-spacing: 0;
        }

        .ot-signature-wrap {
            position: relative;
            z-index: 5;
            width: 100%;
            max-width: 480px;
            height: 160px;
            overflow: hidden;
            background: #fafcfe;
            border: 1px dashed #b9c9d8;
            border-radius: 8px;
            touch-action: none;
            -ms-touch-action: none;
        }

        .ot-signature-wrap::after {
            content: 'Draw signature here';
            position: absolute;
            inset: 0;
            z-index: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(27, 79, 114, 0.3);
            font-size: .82rem;
            font-weight: 700;
            pointer-events: none;
        }

        .ot-signature-wrap.is-signed::after {
            display: none;
        }

        .ot-signature-pad {
            position: relative;
            z-index: 1;
            display: block;
            width: 100% !important;
            height: 100% !important;
            cursor: crosshair;
            touch-action: none;
            -ms-touch-action: none;
            pointer-events: auto !important;
            user-select: none;
            -webkit-user-select: none;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // ---- Mediclaim ↔ Payment Mode ----
            const paymentModeSelect = document.getElementById('ot_payment_mode');
            const mediclaimRadios = document.querySelectorAll('input[name="mediclaim"]');

            function refreshSelect2(selectEl, value) {
                if (!selectEl || !window.jQuery || !jQuery.fn.select2) {
                    return;
                }
                const $el = jQuery(selectEl);
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
                const $modal = $el.closest('.modal');
                const options = {
                    width: '100%',
                    placeholder: 'Select...',
                    allowClear: !$el.prop('required') && $el.find('option[value=""]').length > 0,
                };
                if ($modal.length) {
                    options.dropdownParent = $modal;
                }
                $el.select2(options);
                if (typeof value !== 'undefined') {
                    $el.val(value === null || value === undefined ? '' : value).trigger('change');
                }
            }

            function syncPaymentModeWithMediclaim() {
                if (!paymentModeSelect) return;
                const yesChecked = document.getElementById('mediclaim_yes')?.checked;
                const options = Array.from(paymentModeSelect.options);

                options.forEach(function (opt) {
                    if (!opt.value) return; // keep "Select..."
                    const forMediclaim = opt.getAttribute('data-mediclaim') === '1';
                    if (yesChecked) {
                        opt.hidden = !forMediclaim;
                        opt.disabled = !forMediclaim;
                    } else {
                        opt.hidden = forMediclaim;
                        opt.disabled = forMediclaim;
                    }
                });

                let nextVal = paymentModeSelect.value;
                if (yesChecked) {
                    nextVal = 'mediclaim';
                } else if (paymentModeSelect.value === 'mediclaim') {
                    nextVal = '';
                }
                paymentModeSelect.value = nextVal;

                // Select2: force UI + option list refresh after enable/disable options
                if (window.jQuery && jQuery.fn.select2) {
                    refreshSelect2(paymentModeSelect, nextVal);
                }
            }

            mediclaimRadios.forEach(function (radio) {
                radio.addEventListener('change', syncPaymentModeWithMediclaim);
            });
            // After global Select2 init (runs after stack scripts on same ready tick)
            setTimeout(syncPaymentModeWithMediclaim, 0);
            if (window.jQuery) {
                jQuery(function () {
                    setTimeout(syncPaymentModeWithMediclaim, 50);
                });
            }

            // ---- Package select → fill charges (no lens cost) | Total = charges + lens ----
            const totalDisplay = document.getElementById('otTotalEstimate');
            const packageSelect = document.getElementById('ot_package_select');
            const packageNameInput = document.getElementById('package_name');
            const lensCostInput = document.getElementById('lens_cost');
            const roomCategorySelect = document.getElementById('room_category');
            const chargeFieldIds = ['ot_charges', 'surgeon_charges', 'nursing_charges', 'consumables_charges'];

            function setFieldValue(id, value) {
                const el = document.getElementById(id);
                if (!el) {
                    return;
                }
                el.value = value ?? '';
                if (window.jQuery && el.tagName === 'SELECT' && jQuery(el).hasClass('select2-hidden-accessible')) {
                    jQuery(el).val(el.value).trigger('change.select2');
                }
            }

            function numVal(id) {
                return parseFloat(document.getElementById(id)?.value) || 0;
            }

            function recalcTotal() {
                const total = numVal('ot_charges')
                    + numVal('surgeon_charges')
                    + numVal('nursing_charges')
                    + numVal('consumables_charges')
                    + numVal('lens_cost');
                if (totalDisplay) {
                    totalDisplay.textContent = currencyCode + ' ' + total.toFixed(2);
                }
            }

            /**
             * Autofill room + charges from selected OT package (NOT lens cost).
             * Lens cost is typed manually and added into Total Estimate.
             */
            function applyPackageFromSelect() {
                if (!packageSelect || packageSelect.selectedIndex < 0) {
                    return;
                }

                const opt = packageSelect.options[packageSelect.selectedIndex];
                if (!opt || !opt.value) {
                    if (packageNameInput) {
                        packageNameInput.value = '';
                    }
                    recalcTotal();
                    return;
                }

                const room = opt.getAttribute('data-room') || '';
                if (room && roomCategorySelect) {
                    roomCategorySelect.value = room;
                    if (window.jQuery && jQuery(roomCategorySelect).hasClass('select2-hidden-accessible')) {
                        jQuery(roomCategorySelect).val(room).trigger('change.select2');
                    }
                }

                if (packageNameInput) {
                    packageNameInput.value = opt.getAttribute('data-package-name') || '';
                }

                setFieldValue('ot_charges', opt.getAttribute('data-ot-charges') || '');
                setFieldValue('surgeon_charges', opt.getAttribute('data-surgeon-charges') || '');
                setFieldValue('nursing_charges', opt.getAttribute('data-nursing-charges') || '');
                setFieldValue('consumables_charges', opt.getAttribute('data-consumables-charges') || '');
                // Do NOT touch lens_cost — user types it.
                recalcTotal();
            }

            if (window.jQuery) {
                jQuery(packageSelect).on('change.select2 change', applyPackageFromSelect);
            } else {
                packageSelect?.addEventListener('change', applyPackageFromSelect);
            }

            chargeFieldIds.concat(['lens_cost']).forEach(function (id) {
                const el = document.getElementById(id);
                if (!el) {
                    return;
                }
                el.addEventListener('input', recalcTotal);
                el.addEventListener('change', recalcTotal);
            });

            recalcTotal();

            // ---- Signature pad (pointer events + synced canvas size) ----
            function setupPad(canvasId, hiddenInputId) {
                const canvas = document.getElementById(canvasId);
                const hiddenInput = document.getElementById(hiddenInputId);
                if (!canvas || !hiddenInput) {
                    return;
                }

                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    return;
                }

                let drawing = false;
                let hasDrawn = false;

                function syncSize() {
                    const rect = canvas.getBoundingClientRect();
                    const cssW = Math.max(1, Math.floor(rect.width));
                    const cssH = Math.max(1, Math.floor(rect.height));
                    const ratio = window.devicePixelRatio || 1;
                    const nextW = Math.max(1, Math.floor(cssW * ratio));
                    const nextH = Math.max(1, Math.floor(cssH * ratio));

                    if (canvas.width !== nextW || canvas.height !== nextH) {
                        canvas.width = nextW;
                        canvas.height = nextH;
                    }

                    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
                    ctx.lineWidth = 2.25;
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                    ctx.strokeStyle = '#1B4F72';
                }

                function pos(evt) {
                    const rect = canvas.getBoundingClientRect();
                    return {
                        x: evt.clientX - rect.left,
                        y: evt.clientY - rect.top,
                    };
                }

                function start(evt) {
                    if (evt.pointerType === 'mouse' && evt.button !== 0) {
                        return;
                    }
                    evt.preventDefault();
                    evt.stopPropagation();
                    if (!hasDrawn) {
                        syncSize();
                    }
                    drawing = true;
                    try {
                        canvas.setPointerCapture(evt.pointerId);
                    } catch (e) { /* ignore */ }
                    const p = pos(evt);
                    ctx.beginPath();
                    ctx.moveTo(p.x, p.y);
                }

                function move(evt) {
                    if (!drawing) return;
                    evt.preventDefault();
                    const p = pos(evt);
                    ctx.lineTo(p.x, p.y);
                    ctx.stroke();
                    hasDrawn = true;
                    markSigned(true);
                }

                function end(evt) {
                    if (!drawing) return;
                    drawing = false;
                    try {
                        if (evt && evt.pointerId != null) {
                            canvas.releasePointerCapture(evt.pointerId);
                        }
                    } catch (e) { /* ignore */ }
                    if (hasDrawn) {
                        hiddenInput.value = canvas.toDataURL('image/png');
                        markSigned(true);
                    }
                }

                function markSigned(signed) {
                    const wrap = canvas.closest('.ot-signature-wrap');
                    if (wrap) {
                        wrap.classList.toggle('is-signed', !!signed);
                    }
                }

                function clearPad() {
                    syncSize();
                    ctx.save();
                    ctx.setTransform(1, 0, 0, 1, 0, 0);
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.restore();
                    hiddenInput.value = '';
                    hasDrawn = false;
                    markSigned(false);
                }

                syncSize();
                requestAnimationFrame(function () { syncSize(); });
                setTimeout(function () { if (!hasDrawn) syncSize(); }, 600);

                canvas.addEventListener('pointerdown', start, { passive: false });
                canvas.addEventListener('pointermove', move, { passive: false });
                canvas.addEventListener('pointerup', end);
                canvas.addEventListener('pointercancel', end);
                canvas.addEventListener('lostpointercapture', end);

                window.addEventListener('resize', function () {
                    if (!hasDrawn && !hiddenInput.value) {
                        syncSize();
                    }
                });

                document.querySelector('.ot-clear-pad[data-target="' + canvasId + '"]')?.addEventListener('click', function (e) {
                    e.preventDefault();
                    clearPad();
                });
            }

            setupPad('patientSignaturePad', 'patient_signature_input');
            setupPad('guardianSignaturePad', 'guardian_signature_input');
        });
    </script>
@endpush