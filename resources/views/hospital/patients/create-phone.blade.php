@extends('hospital.layouts.app')
@section('title', 'Phone Appointment')
@section('page-header', '')

@section('page-actions')
@endsection

@section('content')

    {{-- Toast notification --}}
    <div id="contactToast"
        style="display:none;position:fixed;top:1.25rem;right:1.25rem;z-index:9999;
                                                         background:#1B4F72;color:#fff;padding:.75rem 1.25rem;border-radius:.5rem;
                                                         box-shadow:0 4px 12px rgba(0,0,0,.2);font-size:.9rem;max-width:320px">
        <i class="fa-solid fa-circle-check" style="margin-right:.4rem"></i>
        <span id="contactToastMsg"></span>
    </div>

    <div class="hms-card border-0 shadow-lg" style="border-radius:16px">
        <div class="hms-card-header"
            style="background:linear-gradient(135deg, #1B4F72 0%, #2980B9 100%);padding:1.75rem;border-radius:16px 16px 0 0">
            <div style="display:flex;align-items:center;gap:1rem;color:#fff">
                <div
                    style="width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem">
                    <i class="bi bi-telephone-fill"></i>
                </div>
                <div>
                    <h4 style="margin:0;font-weight:700;font-size:1.25rem;color:#fff">Register Phone Appointment</h4>
                    <p style="margin:0.25rem 0 0;font-size:0.9rem;opacity:0.9">Capture caller details and appointment
                        information</p>
                </div>
            </div>
        </div>
        <div class="hms-card-body" style="padding:2rem">
            <form method="POST" action="{{ route('hospital.patients.store-phone', ['slug' => $slug]) }}"
                class="patient-create-form">
                @csrf

                <div class="hms-card-body patient-create-card-body">

                    <div style="display:grid;grid-template-columns: repeat(3, 1fr);gap:1.25rem">

                        {{-- 1. Appointment Date --}}
                        <div class="form-group">
                            <label class="form-label fw-600">Appointment Date</label>
                            <input type="text" name="appointment_date"
                                value="{{ old('appointment_date', now()->format('Y-m-d')) }}"
                                class="form-control flatpickr hms-input" required>
                        </div>

                        {{-- 2. Contact Number --}}
                        <div class="form-group position-relative">
                            <label class="form-label fw-600">Contact Number</label>
                            <input type="text" name="contact_no" id="contactNo" value="{{ old('contact_no') }}"
                                class="form-control hms-input" data-intl-phone required placeholder="+919876543210">
                            <div id="patientSuggestions" class="position-absolute w-100 bg-white shadow-lg rounded d-none"
                                style="z-index:1050; max-height:250px; overflow-y:auto; border:1px solid #E2E8F0; top:100%; margin-top:4px">
                            </div>
                        </div>

                        {{-- 3. WhatsApp No --}}
                        <div class="form-group">
                            <label class="form-label fw-600">WhatsApp No</label>
                            <input type="text" name="whatsapp_no" id="whatsappNo" value="{{ old('whatsapp_no') }}"
                                class="form-control hms-input" data-intl-phone placeholder="Same if blank">
                        </div>

                        {{-- 4, 5, 6 Name Fields --}}
                        <div class="form-group">
                            <label class="form-label fw-600">First Name</label>
                            <input type="text" name="first_name" id="firstName" value="{{ old('first_name') }}"
                                class="form-control hms-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label fw-600">Surname</label>
                            <input type="text" name="last_name" id="lastName" value="{{ old('last_name') }}"
                                class="form-control hms-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label fw-600">Middle Name</label>
                            <input type="text" name="middle_name" id="middleName" value="{{ old('middle_name') }}"
                                class="form-control hms-input">
                        </div>

                        {{-- 7, 8, 9 Location Fields --}}
                        <div class="form-group">
                            <label class="form-label fw-600">City</label>
                            <div style="display:flex;gap:5px">
                                <select name="location_id" id="locationSelect" class="form-control select2 hms-select"
                                    required>
                                    <option value="">Select City</option>
                                    @foreach($locations as $loc)

                                        <option value="{{ $loc->id }}" data-district="{{ $loc->district?->name }}"
                                            data-state="{{ $loc->state?->name }}" @selected(old('location_id') == $loc->id)>

                                            {{ $loc->name }}

                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" id="btnAddLocation" class="hms-btn hms-btn-outline"
                                    style="width:30px;height:30px">+</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label fw-600">District</label>
                            <input type="text" id="district" class="form-control hms-input" readonly
                                placeholder="Auto-filled">
                        </div>
                        <div class="form-group">
                            <label class="form-label fw-600">State</label>
                            <input type="text" id="state" class="form-control hms-input" readonly placeholder="Auto-filled">
                        </div>

                        {{-- 10, 11 Doctor & Slot --}}
                        <div class="form-group">
                            <label class="form-label fw-600">Doctor Name</label>
                            <select name="doctor_id" class="form-control select2 hms-select" required>
                                <option value="">Select Doctor</option>
                                @foreach($doctors as $doc)
                                    <option value="{{ $doc->id }}" @selected(old('doctor_id') == $doc->id)>{{ $doc->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label fw-600">Time Slot</label>
                            <select name="slot_id" class="form-control select2 hms-select">
                                <option value="">No Slot</option>
                                @foreach($slots as $s)
                                    <option value="{{ $s->id }}" @selected(old('slot_id') == $s->id)>{{ $s->slot_name ?? $s->id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 12, 13, 14, 15 --}}
                        <div class="form-group">
                            <label class="form-label fw-600">Age</label>
                            <input type="number" name="age" id="age" value="{{ old('age') }}" class="form-control hms-input"
                                required>
                        </div>
                        <div class="form-group">
                            <label class="form-label fw-600">Gender</label>
                            <select name="gender" id="gender" class="form-control hms-select" required>
                                <option value="">Select</option>
                                <option value="male" @selected(old('gender') === 'male')>Male</option>
                                <option value="female" @selected(old('gender') === 'female')>Female</option>
                                <option value="other" @selected(old('gender') === 'other')>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label fw-600">Occupation</label>
                            <input type="text" name="occupation" id="occupation" value="{{ old('occupation') }}"
                                class="form-control hms-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label fw-600">Referred By</label>
                            <select name="referrer_id" class="form-control select2 hms-select">
                                <option value="">Select Referrer</option>
                                @foreach($referrers as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- 16, 17 Action + MRD Hidden --}}
                    <div
                        style="display:flex;gap:0.875rem;margin-top:2.5rem;padding-top:1.75rem;border-top:1px solid #E2E8F0">
                        <button type="submit" class="hms-btn hms-btn-primary" style="color: #ffffff;">
                            <i class="bi bi-check-circle-fill"></i> Register Phone Appointment
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        .patient-suggestion-card {
            padding: 12px;
            border-bottom: 1px solid var(--color-border-default, #E2E8F0);
            transition: background 0.2s;
        }

        .patient-suggestion-card:hover {
            background-color: var(--color-surface-page, #F8FAFC);
            cursor: pointer;
        }

        .patient-suggestion-card:last-child {
            border-bottom: none;
        }

        .patient-create-page {
            --pc-primary: #1B4F72;
            --pc-soft: #ebf5fbeb;
            --pc-border: rgba(27, 79, 114, .12);
            --pc-border-strong: rgba(27, 79, 114, .2);
            --pc-text-soft: rgba(27, 79, 114, .72);
            color: var(--pc-primary);
        }

        .patient-create-page .hms-btn.hms-btn-outline {
            border-color: var(--pc-border-strong) !important;
            color: var(--pc-primary) !important;
            background: rgba(255, 255, 255, .92) !important;
            border-radius: 12px !important;
        }

        .patient-create-form {
            display: grid;
            gap: 1rem;
        }

        .patient-create-hero,
        .patient-create-card {
            border: 1px solid var(--pc-border) !important;
            border-radius: 24px !important;
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 18px 42px rgba(27, 79, 114, .08);
            overflow: hidden;
        }

        .patient-create-hero {
            padding: 1.35rem 1.5rem;
            background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94));
        }

        .patient-create-kicker {
            display: inline-flex;
            align-items: center;
            margin-bottom: .35rem;
            font-size: .72rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(27, 79, 114, .78);
        }

        .patient-create-heading {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 900;
            color: var(--pc-primary);
            letter-spacing: -.02em;
        }

        .patient-create-copy {
            margin: .3rem 0 0;
            color: var(--pc-text-soft);
            font-size: .92rem;
            max-width: 760px;
        }

        .patient-create-card-header {
            padding: 1.1rem 1.35rem !important;
            border-bottom: 1px solid var(--pc-border) !important;
            background: rgba(255, 255, 255, .92);
        }

        .patient-create-card-header .hms-card-title {
            margin: 0;
            color: var(--pc-primary);
            font-weight: 850;
            font-size: 1.45rem;
            letter-spacing: -.01em;
        }

        .patient-create-card-header .hms-card-title i {
            color: var(--pc-primary);
        }

        .patient-create-card-body {
            padding: 1.35rem !important;
            background: linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(246, 250, 253, .96));
        }

        .patient-create-page .hms-form-group label:first-child,
        .patient-create-page .form-label {
            display: block;
            margin-bottom: .48rem;
            color: rgba(27, 79, 114, .8);
            font-size: .78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .patient-create-page .hms-input,
        .patient-create-page .hms-select,
        .patient-create-page .select2-container--default .select2-selection--single {
            min-height: 52px;
            border-radius: 16px !important;
            border-color: var(--pc-border) !important;
            color: var(--pc-text-soft);
            background: rgba(255, 255, 255, .94) !important;
            box-shadow: inset 0 1px 2px rgba(27, 79, 114, .04);
        }

        .patient-create-page .hms-input,
        .patient-create-page .hms-select {
            border-width: 1px !important;
        }

        .patient-create-page .hms-input:focus,
        .patient-create-page .hms-select:focus {
            border-color: var(--pc-primary) !important;
            box-shadow: 0 0 0 4px rgba(27, 79, 114, .10) !important;
        }

        .patient-create-page .select2-container--default .select2-selection--single {
            display: flex;
            align-items: center;
            padding: 0 .85rem;
        }

        .patient-create-page .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--pc-primary) !important;
            line-height: normal !important;
            padding-left: 0 !important;
        }

        .patient-create-page .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 50px !important;
            right: 8px !important;
        }

        .patient-create-page input[readonly] {
            background: rgba(235, 245, 251, .58) !important;
            color: rgba(27, 79, 114, .78) !important;
        }

        .patient-create-page .hms-checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: .7rem;
            padding: .9rem 1rem;
            border: 1px solid var(--pc-border);
            border-radius: 16px;
            background: rgba(255, 255, 255, .94);
            color: var(--pc-primary);
            font-weight: 700;
        }

        .patient-create-page .hms-form-hint {
            color: var(--pc-text-soft);
        }

        .patient-create-actions .hms-btn {
            min-width: 160px;
            border-radius: 14px !important;
            font-weight: 800 !important;
            padding: .85rem 1.2rem !important;
        }

        .patient-create-actions .hms-btn-primary {
            background: var(--pc-primary) !important;
            border-color: var(--pc-primary) !important;
            color: #ffffff !important;
            box-shadow: 0 14px 26px rgba(27, 79, 114, .16);
        }

        @media (max-width: 992px) {

            .patient-create-card-body>div[style*="repeat(4,1fr)"],
            .patient-create-card-body>div[style*="2fr 1fr 2fr 2fr 2fr"],
            .patient-create-card-body>div[style*="2fr 1fr 1fr 2fr 2fr"] {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 640px) {

            .patient-create-card-body>div[style*="repeat(4,1fr)"],
            .patient-create-card-body>div[style*="2fr 1fr 2fr 2fr 2fr"],
            .patient-create-card-body>div[style*="2fr 1fr 1fr 2fr 2fr"],
            .patient-create-card-body>div[style*="2fr 3fr"],
            .patient-create-page .hms-form-grid-3 {
                grid-template-columns: 1fr !important;
            }

            .patient-create-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .patient-create-actions .hms-btn {
                width: 100%;
            }
        }
    </style>

    <style>
        /* ============================================================
                                                   PHONE APPOINTMENT FORM - ATTRACTIVE DESIGN
                                                   Color Theme: #1B4F72 | #2980B9
                                                   No Conflicts - Uses specific selectors with parent wrapper
                                                   ============================================================ */

        /* Main card enhancement */
        .hms-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hms-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 40px -12px rgba(27, 79, 114, 0.25) !important;
        }

        /* Header gradient with shine effect */
        .hms-card-header {
            background: linear-gradient(135deg, #1B4F72 0%, #2471A3 50%, #2980B9 100%) !important;
            position: relative;
            overflow: hidden;
        }

        .hms-card-header::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
            transition: left 0.5s ease;
        }

        .hms-card-header:hover::after {
            left: 100%;
        }

        /* Header icon animation */
        .hms-card-header>div>div:first-child {
            transition: all 0.3s ease;
        }

        .hms-card-header:hover>div>div:first-child {
            transform: scale(1.08) rotate(5deg);
            background: rgba(255, 255, 255, 0.28) !important;
        }

        /* Form grid - responsive and modern */
        .hms-card-body>form>div>div[style*="grid-template-columns"] {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        @media (max-width: 992px) {
            .hms-card-body>form>div>div[style*="grid-template-columns"] {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.25rem;
            }
        }

        @media (max-width: 640px) {
            .hms-card-body>form>div>div[style*="grid-template-columns"] {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        /* Form group styling */
        .form-group {
            margin-bottom: 0;
            transition: transform 0.2s ease;
        }

        .form-group:focus-within {
            transform: translateY(-2px);
        }

        /* Label styling */
        .form-label.fw-600 {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.7rem;
            font-weight: 800 !important;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #7a8e9e;
            transition: all 0.2s ease;
        }

        .form-group:focus-within .form-label.fw-600 {
            color: #1B4F72;
            letter-spacing: 0.08em;
        }

        /* Input field styling */
        .hms-input,
        select.hms-input,
        .form-control.hms-input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px !important;
            background: #ffffff;
            transition: all 0.25s ease;
            color: #1a2a3a;
        }

        .hms-input:hover,
        select.hms-input:hover,
        .form-control.hms-input:hover {
            border-color: #b8c5d0;
            background: #fefefe;
        }

        .hms-input:focus,
        select.hms-input:focus,
        .form-control.hms-input:focus {
            outline: none;
            border-color: #1B4F72;
            box-shadow: 0 0 0 4px rgba(27, 79, 114, 0.12);
            background: #ffffff;
        }

        /* Readonly fields */
        .hms-input[readonly],
        .form-control.hms-input[readonly] {
            background: #f5f7fb !important;
            border-color: #e2e8f0 !important;
            color: #4a6276 !important;
            cursor: default;
            font-weight: 500;
        }

        /* Select2 customization */
        .select2-container--default .select2-selection--single {
            height: auto !important;
            padding: 0.6rem 1rem;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 14px !important;
            background: #ffffff !important;
            transition: all 0.25s ease;
        }

        .select2-container--default .select2-selection--single:hover {
            border-color: #b8c5d0 !important;
        }

        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #1B4F72 !important;
            box-shadow: 0 0 0 3px rgba(27, 79, 114, 0.1);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.4 !important;
            padding: 0 !important;
            color: #1a2a3a !important;
            font-size: 0.95rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100% !important;
            right: 14px !important;
        }

        .select2-dropdown {
            border: 1px solid #e2e8f0 !important;
            border-radius: 14px !important;
            box-shadow: 0 12px 28px -8px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            background: #ffffff !important;
        }

        .select2-search--dropdown .select2-search__field {
            background: #ffffff !important;
            color: #1a2a3a !important;
            border: 1px solid #d1d9e0 !important;
            border-radius: 8px !important;
            padding: 6px 10px !important;
        }

        .select2-results__options {
            background: #ffffff !important;
        }

        .select2-results__option {
            padding: 0.7rem 1rem !important;
            transition: background 0.15s ease;
            color: #1a2a3a !important;
            background: #ffffff !important;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background: #eef4fb !important;
            color: #1B4F72 !important;
        }

        .select2-container--default .select2-results__option[aria-selected=true] {
            background: #dbeafe !important;
            color: #1B4F72 !important;
        }

        /* Add location button */
        #btnAddLocation {
            width: 5px !important;
            height: 40px !important;
            border-radius: 14px !important;
            background: #f0f4f9 !important;
            border: 1.5px solid #e2e8f0 !important;
            color: #1B4F72 !important;
            font-size: 1.2rem;
            font-weight: bold;
            transition: all 0.2s ease;
            display: flex !important;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        #btnAddLocation:hover {
            background: #1B4F72 !important;
            color: white !important;
            border-color: #1B4F72 !important;
            transform: scale(1.03);
        }

        /* Patient suggestions dropdown */
        #patientSuggestions {
            border-radius: 16px !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.18) !important;
            overflow: hidden;
            z-index: 1060 !important;
        }

        .patient-suggestion-card {
            padding: 0.9rem 1.125rem !important;
            border-bottom: 1px solid #eef2f6 !important;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .patient-suggestion-card:hover {
            background: #f8fafc !important;
            padding-left: 1.375rem !important;
        }

        .patient-suggestion-card:last-child {
            border-bottom: none;
        }

        .patient-suggestion-card .fw-bold {
            color: #1B4F72 !important;
            font-weight: 700 !important;
            font-size: 0.95rem;
        }

        .patient-suggestion-card .small {
            font-size: 0.75rem;
            color: #8a9aaa !important;
        }

        /* Toast notification */
        #contactToast {
            border-radius: 60px !important;
            font-weight: 500;
            backdrop-filter: blur(10px);
            background: #1B4F72 !important;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 25px -8px rgba(0, 0, 0, 0.2);
            font-size: 0.85rem;
        }

        /* Modal styling */
        .modal-content {
            border-radius: 28px !important;
            border: none !important;
            overflow: hidden;
            box-shadow: 0 30px 50px -20px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, #1B4F72 0%, #2471A3 100%);
            color: white;
            border-bottom: none;
            padding: 1.25rem 1.5rem;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .modal-header .btn-close:hover {
            opacity: 1;
        }

        .modal-header .modal-title {
            font-weight: 700;
            font-size: 1.2rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid #eef2f6;
            padding: 1rem 1.5rem;
            background: #fafcfd;
        }

        .modal-footer .btn-primary {
            background: linear-gradient(135deg, #1B4F72 0%, #2471A3 100%);
            border: none;
            border-radius: 40px;
            padding: 0.55rem 1.6rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .modal-footer .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(27, 79, 114, 0.25);
        }

        .modal-footer .btn-secondary {
            background: #f1f5f9;
            border: none;
            color: #475569;
            border-radius: 40px;
            padding: 0.55rem 1.6rem;
            transition: all 0.2s;
        }

        .modal-footer .btn-secondary:hover {
            background: #e2e8f0;
        }

        /* Required field indicator */
        .form-group:has([required]) .form-label.fw-600::after {
            content: '*';
            color: #e74c3c;
            margin-left: 4px;
            font-size: 0.8rem;
        }

        /* Optional field subtle styling */
        .form-group:has(input:not([required])) .form-label.fw-600,
        .form-group:has(select:not([required])) .form-label.fw-600 {
            opacity: 0.7;
        }

        /* Flatpickr customization */
        .flatpickr-calendar {
            border-radius: 20px !important;
            box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.2) !important;
            border: 1px solid #e2e8f0 !important;
            font-family: inherit;
        }

        .flatpickr-day.selected,
        .flatpickr-day.selected:hover {
            background: #1B4F72 !important;
            border-color: #1B4F72 !important;
        }

        .flatpickr-day.today {
            border-color: #2980B9 !important;
        }

        .flatpickr-day.today.selected {
            border-color: #1B4F72 !important;
        }

        /* Buttons styling */
        .hms-btn.hms-btn-primary,
        .hms-card button[type="submit"] {
            background: linear-gradient(135deg, #1B4F72 0%, #2471A3 100%) !important;
            border: none !important;
            padding: 0.85rem 2rem !important;
            font-weight: 700 !important;
            font-size: 0.9rem !important;
            letter-spacing: 0.03em;
            border-radius: 40px !important;
            transition: all 0.3s ease;
            box-shadow: 0 4px 14px rgba(27, 79, 114, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hms-btn.hms-btn-primary:hover,
        .hms-card button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(27, 79, 114, 0.32);
            background: linear-gradient(135deg, #15455e 0%, #1e6a9c 100%) !important;
        }

        .hms-btn.hms-btn-primary:active,
        .hms-card button[type="submit"]:active {
            transform: translateY(0);
        }

        .hms-btn.hms-btn-outline,
        a.hms-btn-outline {
            background: transparent !important;
            border: 1.5px solid #cbd5e1 !important;
            color: #4a6276 !important;
            padding: 0.85rem 1.75rem !important;
            font-weight: 600 !important;
            border-radius: 40px !important;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hms-btn.hms-btn-outline:hover,
        a.hms-btn-outline:hover {
            border-color: #1B4F72 !important;
            color: #1B4F72 !important;
            background: rgba(27, 79, 114, 0.05) !important;
            transform: translateY(-1px);
        }

        /* Action buttons container */
        .hms-card-body>form>div>div:last-child {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid #eef2f6;
        }

        @media (max-width: 640px) {
            .hms-card-body>form>div>div:last-child {
                flex-direction: column-reverse;
                gap: 0.75rem;
            }

            .hms-card-body>form>div>div:last-child button,
            .hms-card-body>form>div>div:last-child a {
                width: 100%;
                justify-content: center;
            }
        }

        /* Loading/disabled state */
        .hms-card button[type="submit"]:disabled {
            opacity: 0.7;
            transform: none !important;
            cursor: not-allowed;
        }

        /* Focus visible for accessibility */
        *:focus-visible {
            outline: 2px solid #1B4F72;
            outline-offset: 2px;
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Number input spinners removal */
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            opacity: 0.5;
        }

        input[type="number"]:hover::-webkit-inner-spin-button,
        input[type="number"]:hover::-webkit-outer-spin-button {
            opacity: 1;
        }

        /* Placeholder styling */
        ::placeholder {
            color: #b8c5d0 !important;
            font-size: 0.85rem;
            opacity: 1;
        }

        /* Error state styling (if any) */
        .is-invalid,
        .was-validated .form-control:invalid {
            border-color: #e74c3c !important;
            background-image: none !important;
        }

        .is-invalid:focus,
        .was-validated .form-control:invalid:focus {
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2) !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var searchUrl = '{{ route('hospital.patients.search-by-contact', ['slug' => $slug]) }}';
            var csrfToken = '{{ csrf_token() }}';

            // ── Init plugins ─────────────────────────────────────────────
            if (typeof $.fn.select2 !== 'undefined') {
                $('.select2').select2({ width: '100%' });
            }
            if (typeof flatpickr !== 'undefined') {
                flatpickr('.flatpickr', {
                    dateFormat: 'Y-m-d',
                    defaultDate: new Date().fp_incr(1),
                    minDate: "today"
                });
            }

            // ── Toast helper ─────────────────────────────────────────────
            function showToast(msg, isError) {
                var toast = document.getElementById('contactToast');
                document.getElementById('contactToastMsg').textContent = msg;
                toast.style.background = isError ? '#C0392B' : '#1B4F72';
                toast.style.display = 'block';
                setTimeout(function () { toast.style.display = 'none'; }, 3500);
            }

            // ── Contact → Patient Dropdown Selection ──────────────────────
            var contactInput = document.getElementById('contactNo');
            var patientSuggestions = document.getElementById('patientSuggestions');
            var foundPatientsList = [];

            // ── Contact / WhatsApp: international phone input ─────────────
            if (window.HmsIntlPhone) {
                HmsIntlPhone.bind(contactInput);
                HmsIntlPhone.bind(document.getElementById('whatsappNo'));
            }

            window.fillSelectedPatient = function (index) {
                if (index < 0 || index >= foundPatientsList.length) { return; }

                var p = foundPatientsList[index];
                var setVal = function (id, val) {
                    var el = document.getElementById(id);
                    if (el && val !== null && val !== undefined) { el.value = val; }
                };

                setVal('firstName', p.first_name);
                setVal('middleName', p.middle_name);
                setVal('lastName', p.last_name);
                setVal('age', p.age);
                setVal('whatsappNo', p.whatsapp_no);
                setVal('occupation', p.occupation);

                var genderEl = document.getElementById('gender');
                if (genderEl && p.gender) { genderEl.value = p.gender; }

                var locEl = document.getElementById('locationSelect');
                if (locEl && p.location_id) {
                    locEl.value = p.location_id;
                    if (typeof $ !== 'undefined') {
                        $(locEl).trigger('change');
                    } else {
                        locEl.dispatchEvent(new Event('change'));
                    }
                }

                var oldPatientCb = document.getElementById('isOldPatient');
                if (oldPatientCb) { oldPatientCb.checked = true; }

                if (patientSuggestions) { patientSuggestions.classList.add('d-none'); }

                showToast('Patient selected — details filled.');
            };

            if (contactInput && patientSuggestions) {
                contactInput.addEventListener('input', function () {
                    var contact = this.value.trim();
                    if (contact.length < 10) {
                        patientSuggestions.classList.add('d-none');
                        return;
                    }

                    fetch(searchUrl + '?contact=' + encodeURIComponent(contact), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (!data.found || !data.patients || data.patients.length === 0) {
                                patientSuggestions.classList.add('d-none');
                                return;
                            }

                            foundPatientsList = data.patients;
                            var html = '';
                            data.patients.forEach(function (p, idx) {
                                var displayName = p.first_name + (p.middle_name ? ' ' + p.middle_name : '') + (p.last_name ? ' ' + p.last_name : '');
                                html += '<div class="patient-suggestion-card" onclick="fillSelectedPatient(' + idx + ')">' +
                                    '<div class="fw-bold" style="color: var(--prim-blue-600, #1B4F72);">' + displayName + '</div>' +
                                    '<div class="small text-muted">Age: ' + (p.age ?? '-') + ' | Gender: ' + (p.gender ?? '-') + '</div>' +
                                    '</div>';
                            });

                            patientSuggestions.innerHTML = html;
                            patientSuggestions.classList.remove('d-none');
                        })
                        .catch(function () { patientSuggestions.classList.add('d-none'); });
                });
            }

            document.addEventListener('click', function (e) {
                if (patientSuggestions && contactInput && e.target !== contactInput && !patientSuggestions.contains(e.target)) {
                    patientSuggestions.classList.add('d-none');
                }
            });

            // ── Case Type → Fee auto-fill ─────────────────────────────────
            var caseSelect = document.getElementById('caseSelect');
            var caseFeeEl = document.getElementById('caseFee');
            if (caseSelect && caseFeeEl) {
                $(caseSelect).on('change', function () {
                    var opt = this.options[this.selectedIndex];
                    caseFeeEl.value = opt ? (opt.dataset.fee || 0) : 0;
                });
            }

            // ── Location → District / State auto-fill ────────────────────
            var locationEl = document.getElementById('locationSelect');
            var districtEl = document.getElementById('district');
            var stateEl = document.getElementById('state');

            function syncLocation() {
                if (!locationEl || !districtEl || !stateEl) { return; }
                var opt = locationEl.options[locationEl.selectedIndex];
                if (locationEl.value && opt) {
                    districtEl.value = opt.getAttribute('data-district') || '';
                    stateEl.value = opt.getAttribute('data-state') || '';
                } else {
                    districtEl.value = '';
                    stateEl.value = '';
                }
            }

            if (locationEl) {
                $(locationEl).on('change', syncLocation);
                syncLocation();
            }
        });
    </script>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var addBtn = document.getElementById('btnAddLocation');
            if (!addBtn) return;

            // Create modal HTML and append to body
            var modalHtml = '\n<div class="modal fade" id="modalAddLocation" tabindex="-1" aria-hidden="true">\n  <div class="modal-dialog modal-sm modal-dialog-centered">\n    <div class="modal-content">\n      <div class="modal-header">\n        <h5 class="modal-title" style="color:#fff">Add City</h5>\n        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>\n      </div>\n      <div class="modal-body">\n        <div id="addLocErrors" class="text-danger mb-2"></div>\n        <div class="mb-2">\n          <label class="form-label">City</label>\n          <input type="text" id="newCity" class="form-control" placeholder="City name">\n        </div>\n        <div class="mb-2">\n          <label class="form-label">District</label>\n          <input type="text" id="newDistrict" class="form-control" placeholder="District">\n        </div>\n        <div class="mb-2">\n          <label class="form-label">State</label>\n          <input type="text" id="newState" class="form-control" placeholder="State">\n        </div>\n      </div>\n      <div class="modal-footer">\n        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>\n        <button type="button" id="saveLocationBtn" class="btn btn-primary">Add</button>\n      </div>\n    </div>\n  </div>\n</div>\n';

            document.body.insertAdjacentHTML('beforeend', modalHtml);

            var modalEl = document.getElementById('modalAddLocation');
            var saveBtn = document.getElementById('saveLocationBtn');
            var newCity = document.getElementById('newCity');
            var newDistrict = document.getElementById('newDistrict');
            var newState = document.getElementById('newState');
            var addLocErrors = document.getElementById('addLocErrors');

            addBtn.addEventListener('click', function () {
                var modal = new bootstrap.Modal(modalEl);
                addLocErrors.innerHTML = '';
                newCity.value = '';
                newDistrict.value = '';
                newState.value = '';
                modal.show();
            });

            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    addLocErrors.innerHTML = '';
                    var city = newCity.value.trim();
                    var district = newDistrict.value.trim();
                    var state = newState.value.trim();
                    if (!city) { addLocErrors.textContent = 'City is required.'; return; }
                    if (!state) { addLocErrors.textContent = 'State is required.'; return; }

                    var url = '{{ route("hospital.masters.basic.ajax.store", ["slug" => $slug, "type" => "locations"]) }}';
                    var token = '{{ csrf_token() }}';

                    fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ city: city, district: district, state: state })
                    })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (!data || !data.success) {
                                addLocErrors.textContent = (data?.message) || 'Failed to add city.';
                                return;
                            }

                            // Append to select and select it
                            var sel = document.getElementById('locationSelect');
                            var opt = document.createElement('option');
                            opt.value = data.id;
                            opt.text = city;
                            opt.setAttribute('data-district', district);
                            opt.setAttribute('data-state', state);
                            sel.appendChild(opt);
                            if (typeof $ !== 'undefined') { $(sel).val(data.id).trigger('change'); }
                            else { sel.value = data.id; sel.dispatchEvent(new Event('change')); }

                            // Close modal
                            var modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();

                            // Show toast
                            var toast = document.getElementById('contactToast');
                            if (toast) { document.getElementById('contactToastMsg').textContent = 'City added.'; toast.style.background = '#1B4F72'; toast.style.display = 'block'; setTimeout(function () { toast.style.display = 'none'; }, 2500); }
                        })
                        .catch(function (err) { addLocErrors.textContent = 'Network error.'; });
                });
            }
        });
    </script>
@endpush