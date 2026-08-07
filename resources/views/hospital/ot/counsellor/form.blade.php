@extends('hospital.layouts.app')
@section('title', 'OT Counselling')
@section('page-header', 'OT Counselling')

@section('page-actions')
    <a href="{{ route('hospital.ot.counsellor.dashboard', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
@endsection

@section('content')
            <div class="ot-counselling-page">
                <div class="card ot-premium-card ot-patient-banner border-0 mb-4">
                    <div class="card-body d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center">
                        <div class="ot-title-wrap">
                            <span class="ot-title-icon" aria-hidden="true">
                                <i class="bi bi-chat-left-heart" style="font-size: 1.2rem;"></i>
                            </span>
                            <div>
                                <h4 class="mb-1 ot-title">{{ $booking->patient?->full_name ?? 'Patient' }}</h4>
                                <div class="ot-subtitle">
                                    UHID {{ $booking->patient?->patient_code ?? '-' }} &middot;
                                    Eye {{ $booking->eye }} &middot;
                                    {{ $booking->ot_type }} &middot;
                                    Dr. {{ $booking->otDoctor?->name ?? '-' }}
                                </div>
                            </div>
                        </div>
                        <div class="text-md-end ot-booking-meta">
                            Booking #{{ $booking->id }}<br>
                            {{ optional($booking->surgery_date)->format('d M Y') }}
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
                                <button type="submit" class="hms-btn hms-btn-primary px-4">
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
                                <button type="submit" class="hms-btn hms-btn-primary px-4">
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
                            <div class="ot-title" style="font-size: 1rem;">Ready for Billing?</div>
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
          OT Counselling Form (Design refresh)
          Keep Blade/dynamic logic untouched; CSS-only + layout wrappers.
          Palette follows hospital shell theme (#1B4F72).
        */

        .ot-counselling-page {
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
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .ot-counselling-page .btn,
        .ot-counselling-page .hms-btn {
            border-radius: 12px;
            font-weight: 800;
            transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, border-color 170ms ease, color 170ms ease;
        }

        .ot-counselling-page .btn:hover,
        .ot-counselling-page .hms-btn:hover {
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
            animation: ot-card-rise 520ms cubic-bezier(.2, .9, .2, 1) both;
        }

        @keyframes ot-card-rise {
            from {
                opacity: 0;
                transform: translateY(10px) scale(0.99);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
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

        .ot-patient-banner .ot-booking-meta {
            color: rgba(27, 79, 114, 0.72);
            font-weight: 700;
            font-size: .88rem;
        }

        .ot-alert {
            border-radius: 14px;
            font-weight: 650;
        }

        .ot-counselling-page .form-label {
            font-weight: 800;
            font-size: .82rem;
            color: var(--ot-secondary);
            letter-spacing: .01em;
        }

        .ot-counselling-page .form-control,
        .ot-counselling-page .form-select {
            border: 1px solid var(--ot-s2-18);
            border-radius: 12px;
            padding: .55rem .85rem;
            background: rgba(255, 255, 255, 0.92);
            color: var(--ot-secondary);
            font-weight: 600;
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }

        .ot-counselling-page .form-control:focus,
        .ot-counselling-page .form-select:focus {
            border-color: var(--ot-secondary);
            box-shadow: 0 0 0 .2rem var(--ot-s2-12);
        }

        .ot-counselling-page .input-group-text {
            background: var(--ot-s2-08);
            border: 1px solid var(--ot-s2-18);
            border-radius: 12px 0 0 12px;
            color: var(--ot-secondary);
            font-weight: 800;
        }

        .ot-counselling-page .form-check-input:checked {
            background-color: var(--ot-secondary);
            border-color: var(--ot-secondary);
        }

        .ot-counselling-page hr {
            border-top: 1px solid var(--ot-s2-12);
            opacity: 1;
        }

        .ot-section-title {
            color: var(--ot-secondary);
            font-weight: 900;
            letter-spacing: -.01em;
        }

        .ot-form-actions {
            border-top: 1px solid var(--ot-s2-12);
        }

        .ot-total-box {
            background: linear-gradient(135deg, rgba(235, 245, 251, 0.92), rgba(255, 255, 255, 0.94));
            border: 1px solid var(--ot-s2-18);
            border-radius: 14px;
            padding: .9rem 1.15rem;
            color: var(--ot-secondary);
            box-shadow: 0 10px 22px rgba(27, 79, 114, 0.08);
        }

        .ot-signature-wrap {
            width: 100%;
            max-width: 480px;
            height: 160px;
            border: 1px dashed var(--ot-s2-24);
            border-radius: 14px;
            background: #fff;
            position: relative;
            z-index: 5;
            overflow: hidden;
            touch-action: none;
            -ms-touch-action: none;
        }

        .ot-signature-wrap::after {
            content: 'Draw signature here';
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(27, 79, 114, 0.28);
            font-size: .9rem;
            font-weight: 700;
            pointer-events: none;
            z-index: 0;
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

        .ot-billing-btn {
            background: #1E8E5A;
            border: 1px solid #1E8E5A;
            color: #fff;
        }

        .ot-billing-btn:hover {
            background: #17714a;
            border-color: #17714a;
            color: #fff;
        }

        .ot-billing-btn:disabled {
            background: rgba(27, 79, 114, 0.18);
            border-color: rgba(27, 79, 114, 0.18);
            color: rgba(27, 79, 114, 0.6);
            transform: none !important;
            box-shadow: none !important;
        }

        @media (prefers-reduced-motion: reduce) {

            .ot-counselling-page,
            .ot-premium-card,
            .ot-counselling-page .btn,
            .ot-counselling-page .hms-btn {
                animation: none !important;
                transition: none !important;
            }
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