@extends('hospital.layouts.app')
@section('title', 'Patient Check-In')
@section('page-header', '')

@section('page-actions')
@endsection

@section('content')

    <div class="hms-card border-0 shadow-lg" style="border-radius:16px">
        {{-- Header --}}
        <div class="hms-card-header" style="background:linear-gradient(135deg,#1B4F72 0%,#2980B9 100%);padding:1.75rem;border-radius:16px 16px 0 0">
            <div style="display:flex;align-items:center;gap:1rem;color:#fff">
                <div style="width:48px;height:48px;background:rgba(255,255,255,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem">
                    <i class="bi bi-person-check-fill"></i>
                </div>
                <div>
                    <h4 style="margin:0;font-weight:700;font-size:1.25rem;color:#fff">Patient Check-In</h4>
                    <p style="margin:.25rem 0 0;font-size:.9rem;opacity:.9">
                        MRD: <strong>{{ $patient->patient_code }}</strong> &nbsp;|&nbsp; Phone appointment arrived — verify details &amp; assign case
                    </p>
                </div>
            </div>
        </div>

        <div class="hms-card-body" style="padding:2rem">
            <form method="POST"
                  action="{{ route('hospital.patients.checkin.store', ['slug' => $slug, 'patient' => $patient->id]) }}"
                  class="patient-create-form">
                @csrf

                {{-- ── Section 1: Personal Details ── --}}
                <div class="form-group">
                <label class="form-label fw-600">MRD No.</label>
                <input type="text" value="{{ $patient->patient_code }}" readonly class="form-control hms-input"
                style="background:linear-gradient(135deg,#ebf5fbeb,#D6EAF8);font-weight:700;color:#1B4F72;border:1px solid #D6EAF8; width: 35%">
                </div>
                <div style="margin-bottom:2.5rem">
                    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1.5rem"></div>

                    {{-- Row 0: MRD + Contact + WhatsApp --}}
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;margin-bottom:1.25rem">
                    <div class="form-group">
                            <label class="form-label fw-600">
                                    Appointment Date <span style="color:#C0392B;font-weight:700">*</span>
                            </label>
                    <input type="text" name="appointment_date" id="appointmentDate"
                    value="{{ old('appointment_date', $patient->appointment_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                    class="form-control hms-input flatpickr @error('appointment_date') is-invalid @enderror" required>
                    @error('appointment_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                        <div class="form-group position-relative">
                            <label class="form-label fw-600">
                                Contact No <span style="color:#C0392B;font-weight:700">*</span>
                            </label>
                            <input type="text" name="contact_no" id="contactNo"
                                   value="{{ old('contact_no', $patient->contact_no) }}" maxlength="15"
                                   class="form-control hms-input @error('contact_no') is-invalid @enderror"
                                   placeholder="e.g. 9876543210" required>
                            @error('contact_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label fw-600">WhatsApp No</label>
                            <input type="text" name="whatsapp_no"
                                   value="{{ old('whatsapp_no', $patient->whatsapp_no) }}" maxlength="15"
                                   class="form-control hms-input @error('whatsapp_no') is-invalid @enderror"
                                   placeholder="Same if blank">
                            @error('whatsapp_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Row 1: First Name + Surname + Middle Name --}}
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem">
                        <div class="form-group">
                            <label class="form-label fw-600">
                                First Name <span style="color:#C0392B;font-weight:700">*</span>
                            </label>
                            <input type="text" name="first_name"
                                   value="{{ old('first_name', $patient->first_name) }}"
                                   class="form-control hms-input @error('first_name') is-invalid @enderror" required
                                   placeholder="e.g. John">
                            @error('first_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label fw-600">
                                Surname <span style="color:#C0392B;font-weight:700">*</span>
                            </label>
                            <input type="text" name="last_name"
                                   value="{{ old('last_name', $patient->last_name) }}"
                                   class="form-control hms-input @error('last_name') is-invalid @enderror" required
                                   placeholder="e.g. Patel">
                            @error('last_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label fw-600">Middle Name</label>
                            <input type="text" name="middle_name"
                                   value="{{ old('middle_name', $patient->middle_name) }}"
                                   placeholder="e.g. Kumar"
                                   class="form-control hms-input">
                        </div>
                    </div>

                    {{-- Row 2: Age + Gender + Occupation --}}
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;margin-top:1.25rem">
                        <div class="form-group">
                            <label class="form-label fw-600">
                                Case Type <span style="color:#C0392B;font-weight:700">*</span>
                            </label>
                            <select name="case_id" id="caseSelect"
                                class="form-control select2 hms-select @error('case_id') is-invalid @enderror" required>
                                <option value="">Select Case</option>
                                @foreach($cases as $c)
                                    <option value="{{ $c->id }}" data-fee="{{ $c->fee ?? 0 }}" @selected(old('case_id', $patient->case_id) == $c->id)>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('case_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label fw-600">
                                Case Fee (₹) <span style="color:#C0392B;font-weight:700">*</span>
                            </label>
                            <div
                            style="display:flex;align-items:center;background:linear-gradient(135deg,#D5F5E3,#E8F8F5);border:1.5px solid #A9DFBF;border-radius:14px;padding:0 .75rem">
                            <span style="color:#27AE60;font-weight:700;font-size:1.1rem">₹</span>
                            <input type="number" name="case_fee" id="caseFee" value="{{ old('case_fee', $patient->case_fee ?? '0') }}"
                            step="0.01" min="0" class="form-control border-0 @error('case_fee') is-invalid @enderror" required
                            style="background:transparent;padding:.75rem .5rem;font-weight:600;color:#27AE60;box-shadow:none">
                            </div>
                            @error('case_fee')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label fw-600">
                                    Doctor <span style="color:#C0392B;font-weight:700">*</span>
                                </label>
                            <select name="doctor_id" class="form-control select2 hms-select @error('doctor_id') is-invalid @enderror" required>
                            <option value="">Select Doctor</option>
                            @foreach($doctors as $doc)
                            <option value="{{ $doc->id }}" @selected(old('doctor_id', $patient->doctor_id) == $doc->id)>
                            {{ $doc->name }}{{ $doc->doctor_type ? ' (' . ucfirst($doc->doctor_type) . ')' : '' }}
                            </option>
                            @endforeach
                            </select>
                            @error('doctor_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                {{-- ── Section 2: Location ── --}}
                <div style="margin-bottom:2.5rem">
                    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1.5rem"></div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-600">
                                City <span style="color:#C0392B;font-weight:700">*</span>
                            </label>
                            <div style="display:flex;gap:.5rem;align-items:center">
                                <select name="location_id" id="locationSelect"
                                    class="form-select select2 hms-select @error('location_id') is-invalid @enderror" required>
                                    <option value="">Select City</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" data-district="{{ $loc->district }}" data-state="{{ $loc->state }}"
                                            @selected(old('location_id', $patient->location_id) == $loc->id)>
                                            {{ $loc->city ?: "Location #{$loc->id}" }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('location_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-600">District</label>
                            <input type="text" id="district" class="form-control hms-input" readonly placeholder="Auto-filled"
                                value="{{ $patient->location?->district }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-600">State</label>
                            <input type="text" id="state" class="form-control hms-input" readonly placeholder="Auto-filled"
                                value="{{ $patient->location?->state }}">
                        </div>
                    </div>
                </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-600">
                                Age <span style="color:#C0392B;font-weight:700">*</span>
                            </label>
                            <input type="number" name="age"
                                   value="{{ old('age', $patient->age) }}" min="0" max="150"
                                   class="form-control hms-input @error('age') is-invalid @enderror" required>
                            @error('age')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-600">
                                Gender <span style="color:#C0392B;font-weight:700">*</span>
                            </label>
                            <select name="gender"
                                    class="form-control hms-select @error('gender') is-invalid @enderror" required>
                                <option value="">Select Gender</option>
                                <option value="male"   @selected(old('gender', $patient->gender) === 'male')>Male</option>
                                <option value="female" @selected(old('gender', $patient->gender) === 'female')>Female</option>
                                <option value="other"  @selected(old('gender', $patient->gender) === 'other')>Other</option>
                            </select>
                            @error('gender')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-600">Occupation</label>
                            <input type="text" name="occupation"
                                   value="{{ old('occupation', $patient->occupation) }}"
                                   class="form-control hms-input"
                                   placeholder="e.g. Farmer, Teacher">
                        </div>
                    </div>
                </div>



                {{-- ── Section 3: Appointment & Case ── --}}
                <div class="row mt-3">
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem"></div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label fw-600">Time Slot</label>
                            <select name="slot_id" class="form-control select2 hms-select">
                                <option value="">No Slot</option>
                                @foreach($slots as $s)
                                    <option value="{{ $s->id }}" @selected(old('slot_id', $patient->slot_id) == $s->id)>
                                        {{ $s->slot_name ?? $s->label ?? $s->name ?? $s->id }}
                                        @if(!empty($s->start_time) && !empty($s->end_time))
                                            ({{ \Carbon\Carbon::parse($s->start_time)->format('h:i A') }} -
                                            {{ \Carbon\Carbon::parse($s->end_time)->format('h:i A') }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label fw-600">Referred By</label>
                            <select name="referrer_id"
                                class="form-control select2 hms-select @error('referrer_id') is-invalid @enderror">
                                <option value="">Select Referrer</option>
                                @foreach($referrers as $r)
                                    <option value="{{ $r->id }}" @selected(old('referrer_id', $patient->referrer_id) == $r->id)>
                                        {{ $r->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div style="display:flex;gap:.875rem;margin-top:2.5rem;padding-top:1.75rem;border-top:2px solid #eef2f6">
                    <button type="submit" class="hms-btn hms-btn-primary">
                        <i class="bi bi-check-circle-fill"></i> Check In &amp; Print Bill
                    </button>
                </div>

            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Case Type → auto-fill fee (jQuery — required for Select2)
        var caseSelectEl = document.getElementById('caseSelect');
        var caseFeeEl    = document.getElementById('caseFee');
        if (caseSelectEl && caseFeeEl) {
            $(caseSelectEl).on('change', function () {
                var opt = this.options[this.selectedIndex];
                caseFeeEl.value = opt ? (opt.dataset.fee || 0) : 0;
            });
        }

        // City → auto-fill district/state (jQuery — required for Select2)
        var locationSelectEl = document.getElementById('locationSelect');
        var districtEl       = document.getElementById('district');
        var stateEl          = document.getElementById('state');
        if (locationSelectEl) {
            $(locationSelectEl).on('change', function () {
                var opt = this.options[this.selectedIndex];
                if (districtEl) districtEl.value = opt ? (opt.getAttribute('data-district') || '') : '';
                if (stateEl)    stateEl.value    = opt ? (opt.getAttribute('data-state')    || '') : '';
            });
        }

        // Flatpickr for appointment date
        if (typeof flatpickr !== 'undefined') {
            flatpickr('#appointmentDate', { dateFormat: 'Y-m-d', allowInput: true });
        }

        // Select2 if available
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('.select2').select2({ width: '100%' });
        }
    });
    </script>
    @endpush

    @push('styles')
        <style>
            /* Form group */
            .form-group {
                margin-bottom: 0;
                transition: transform 0.2s ease;
            }

            .form-group:focus-within {
                transform: translateY(-2px);
            }

            /* Label */
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

            /* Input fields */
            .hms-input,
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
            .form-control.hms-input:hover {
                border-color: #b8c5d0;
                background: #fefefe;
            }

            .hms-input:focus,
            .form-control.hms-input:focus {
                outline: none;
                border-color: #1B4F72;
                box-shadow: 0 0 0 4px rgba(27, 79, 114, 0.12);
                background: #ffffff;
            }

            /* Readonly */
            .hms-input[readonly],
            .form-control.hms-input[readonly] {
                background: #f5f7fb !important;
                border-color: #e2e8f0 !important;
                color: #4a6276 !important;
                cursor: default;
                font-weight: 500;
            }

            /* Select fields */
            .hms-select,
            .form-control.hms-select {
                width: 100%;
                padding: 0.75rem 1rem;
                font-size: 0.95rem;
                border: 1.5px solid #e2e8f0;
                border-radius: 14px !important;
                background: #ffffff;
                transition: all 0.25s ease;
                color: #1a2a3a;
            }

            .hms-select:hover,
            .form-control.hms-select:hover {
                border-color: #b8c5d0;
            }

            .hms-select:focus,
            .form-control.hms-select:focus {
                outline: none;
                border-color: #1B4F72;
                box-shadow: 0 0 0 4px rgba(27, 79, 114, 0.12);
            }

            /* Select2 */
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

            /* Primary button */
            .hms-btn.hms-btn-primary {
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
                color: #fff !important;
            }

            .hms-btn.hms-btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 22px rgba(27, 79, 114, 0.32);
                background: linear-gradient(135deg, #15455e 0%, #1e6a9c 100%) !important;
            }

            .hms-btn.hms-btn-primary:active {
                transform: translateY(0);
            }

            /* Outline button */
            .hms-btn.hms-btn-outline,
            a.hms-btn.hms-btn-outline {
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
            a.hms-btn.hms-btn-outline:hover {
                border-color: #1B4F72 !important;
                color: #1B4F72 !important;
                background: rgba(27, 79, 114, 0.05) !important;
                transform: translateY(-1px);
            }

            /* Header shine effect */
            .hms-card-header {
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
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
                transition: left 0.5s ease;
            }

            .hms-card-header:hover::after {
                left: 100%;
            }

            /* Flatpickr */
            .flatpickr-calendar {
                border-radius: 20px !important;
                box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.2) !important;
                border: 1px solid #e2e8f0 !important;
            }

            .flatpickr-day.selected,
            .flatpickr-day.selected:hover {
                background: #1B4F72 !important;
                border-color: #1B4F72 !important;
            }

            .flatpickr-day.today {
                border-color: #2980B9 !important;
            }

            /* Error state */
            .is-invalid,
            .was-validated .form-control:invalid {
                border-color: #e74c3c !important;
                background-image: none !important;
            }

            .is-invalid:focus {
                box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2) !important;
            }

            /* Placeholder */
            ::placeholder {
                color: #b8c5d0 !important;
                font-size: 0.85rem;
                opacity: 1;
            }

            /* Focus visible */
            *:focus-visible {
                outline: 2px solid #1B4F72;
                outline-offset: 2px;
            }

            @media (max-width: 768px) {
                .hms-btn.hms-btn-primary,
                .hms-btn.hms-btn-outline {
                    width: 100%;
                    text-align: center;
                    justify-content: center;
                }
            }
        </style>

        <style>
            /* ============================================================
           PATIENT CHECK-IN FORM - ATTRACTIVE DESIGN
           Color Theme: #1B4F72 | #2980B9 | #27AE60
           No Conflicts - Uses specific selectors
           ============================================================ */

        /* ── Main Card Enhancement ── */
        .hms-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hms-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 25px 45px -15px rgba(27, 79, 114, 0.2) !important;
        }

        /* ── Header with Animated Shine ── */
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
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
            transition: left 0.6s ease;
        }

        .hms-card-header:hover::after {
            left: 100%;
        }

        /* ── Header Icon Animation ── */
        .hms-card-header > div > div:first-child {
            transition: all 0.4s ease;
        }

        .hms-card-header:hover > div > div:first-child {
            transform: scale(1.08) rotate(-3deg);
            background: rgba(255,255,255,0.28) !important;
        }

        /* ── Section Headers ── */
        .hms-card-body > form > div > div:first-child {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .hms-card-body > form > div > div:first-child > div {
            width: 4px;
            height: 24px;
            border-radius: 2px;
            transition: height 0.3s ease;
        }

        .hms-card-body > form > div:hover > div:first-child > div {
            height: 28px;
        }

        .hms-card-body > form > div > div:first-child h5 {
            margin: 0;
            font-weight: 700;
            font-size: 1.05rem;
            transition: transform 0.2s ease;
        }

        .hms-card-body > form > div:hover > div:first-child h5 {
            transform: translateX(4px);
        }

        /* ── Form Group Styling ── */
        .form-group {
            margin-bottom: 0;
            transition: transform 0.25s ease;
        }

        .form-group:focus-within {
            transform: translateY(-3px);
        }

        /* ── Label Styling ── */
        .form-label.fw-600 {
            display: block;
            margin-bottom: 0.45rem;
            font-size: 0.7rem;
            font-weight: 800 !important;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #7a8e9e;
            transition: all 0.3s ease;
            position: relative;
        }

        .form-group:focus-within .form-label.fw-600 {
            color: #1B4F72;
            letter-spacing: 0.1em;
        }

        /* ── Required field indicator ── */
        .form-label.fw-600 span[style*="color:#C0392B"] {
            font-size: 0.9rem;
            margin-left: 2px;
        }

        /* ── Input Fields ── */
        .hms-input,
        .form-control.hms-input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px !important;
            background: #ffffff;
            transition: all 0.3s ease;
            color: #1a2a3a;
        }

        .hms-input:hover,
        .form-control.hms-input:hover {
            border-color: #b8c5d0;
            background: #fcfcfd;
        }

        .hms-input:focus,
        .form-control.hms-input:focus {
            outline: none;
            border-color: #1B4F72;
            box-shadow: 0 0 0 4px rgba(27, 79, 114, 0.1);
            background: #ffffff;
            transform: scale(1.005);
        }

        /* ── Readonly Fields ── */
        .hms-input[readonly],
        .form-control.hms-input[readonly] {
            background: linear-gradient(135deg, #f4f7fb, #eef2f8) !important;
            border-color: #dce3ec !important;
            color: #3d5a73 !important;
            cursor: default;
            font-weight: 600;
        }

        /* ── MRD Special Styling ── */
        input[value*="MRD"] {
            background: linear-gradient(135deg, #ebf5fb, #d6eaf8) !important;
            border-color: #aed6f1 !important;
            color: #1B4F72 !important;
            font-weight: 700 !important;
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
        }

        /* ── Select Fields ── */
        .hms-select,
        .form-control.hms-select {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px !important;
            background: #ffffff;
            transition: all 0.3s ease;
            color: #1a2a3a;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%234a6276' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 12px;
            padding-right: 2.5rem;
        }

        .hms-select:hover,
        .form-control.hms-select:hover {
            border-color: #b8c5d0;
        }

        .hms-select:focus,
        .form-control.hms-select:focus {
            outline: none;
            border-color: #1B4F72;
            box-shadow: 0 0 0 4px rgba(27, 79, 114, 0.1);
        }

        /* ── Select2 Customization ── */
        .select2-container--default .select2-selection--single {
            height: auto !important;
            padding: 0.55rem 1rem;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 14px !important;
            background: #ffffff !important;
            transition: all 0.3s ease;
        }

        .select2-container--default .select2-selection--single:hover {
            border-color: #b8c5d0 !important;
            background: #fcfcfd !important;
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
            box-shadow: 0 15px 35px -12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            background: #ffffff !important;
        }

        .select2-search--dropdown .select2-search__field {
            background: #f8fafc !important;
            color: #1a2a3a !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 10px !important;
            padding: 8px 12px !important;
            transition: border-color 0.2s ease;
        }

        .select2-search--dropdown .select2-search__field:focus {
            border-color: #1B4F72 !important;
            outline: none;
        }

        .select2-results__options {
            background: #ffffff !important;
        }

        .select2-results__option {
            padding: 0.7rem 1rem !important;
            transition: all 0.15s ease;
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

        /* ── Case Fee Input ── */
        .form-group:has(#caseFee) {
            position: relative;
        }

        #caseFee {
            background: transparent !important;
            padding: 0.75rem 0.5rem 0.75rem 0.25rem !important;
            font-weight: 700 !important;
            color: #1B4F72 !important;
            border: none !important;
            box-shadow: none !important;
        }

        #caseFee:focus {
            box-shadow: none !important;
        }

        .form-group:has(#caseFee) > div {
            background: linear-gradient(135deg, #eafaf1, #d5f5e3) !important;
            border: 2px solid #82e0aa !important;
            border-radius: 14px !important;
            padding: 0 0.75rem !important;
            transition: all 0.3s ease;
        }

        .form-group:has(#caseFee):focus-within > div {
            border-color: #27AE60 !important;
            box-shadow: 0 0 0 4px rgba(39, 174, 96, 0.15);
        }

        .form-group:has(#caseFee) > div > span {
            color: #27AE60 !important;
            font-weight: 700 !important;
            font-size: 1.1rem;
        }

        /* ── Buttons ── */
        .hms-btn.hms-btn-primary,
        button[type="submit"] {
            background: linear-gradient(135deg, #1B4F72 0%, #2471A3 100%) !important;
            border: none !important;
            padding: 0.85rem 2.2rem !important;
            font-weight: 700 !important;
            font-size: 0.9rem !important;
            letter-spacing: 0.04em;
            border-radius: 40px !important;
            transition: all 0.35s ease;
            box-shadow: 0 4px 16px rgba(27, 79, 114, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            color: #fff !important;
            position: relative;
            overflow: hidden;
        }

        .hms-btn.hms-btn-primary::before,
        button[type="submit"]::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.5s ease;
        }

        .hms-btn.hms-btn-primary:hover::before,
        button[type="submit"]:hover::before {
            left: 100%;
        }

        .hms-btn.hms-btn-primary:hover,
        button[type="submit"]:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(27, 79, 114, 0.35);
            background: linear-gradient(135deg, #15455e 0%, #1e6a9c 100%) !important;
        }

        .hms-btn.hms-btn-primary:active,
        button[type="submit"]:active {
            transform: translateY(-1px);
        }

        /* ── Outline Button ── */
        .hms-btn.hms-btn-outline,
        a.hms-btn.hms-btn-outline {
            background: transparent !important;
            border: 1.5px solid #cbd5e1 !important;
            color: #4a6276 !important;
            padding: 0.85rem 1.75rem !important;
            font-weight: 600 !important;
            font-size: 0.9rem !important;
            border-radius: 40px !important;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hms-btn.hms-btn-outline:hover,
        a.hms-btn.hms-btn-outline:hover {
            border-color: #1B4F72 !important;
            color: #1B4F72 !important;
            background: rgba(27, 79, 114, 0.05) !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(27, 79, 114, 0.08);
        }

        /* ── Action Buttons Container ── */
        .hms-card-body > form > div:last-child {
            display: flex;
            gap: 1rem;
            margin-top: 2.5rem;
            padding-top: 1.75rem;
            border-top: 2px solid #eef2f6;
            flex-wrap: wrap;
        }

        @media (max-width: 640px) {
            .hms-card-body > form > div:last-child {
                flex-direction: column-reverse;
                gap: 0.75rem;
            }

            .hms-card-body > form > div:last-child button,
            .hms-card-body > form > div:last-child a {
                width: 100%;
                justify-content: center;
            }
        }

        /* ── Flatpickr ── */
        .flatpickr-calendar {
            border-radius: 20px !important;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.2) !important;
            border: 1px solid #e2e8f0 !important;
            font-family: inherit;
        }

        .flatpickr-day.selected,
        .flatpickr-day.selected:hover {
            background: #1B4F72 !important;
            border-color: #1B4F72 !important;
            color: #fff !important;
        }

        .flatpickr-day.today {
            border-color: #2980B9 !important;
        }

        .flatpickr-day.today.selected {
            border-color: #1B4F72 !important;
        }

        .flatpickr-day:hover {
            background: #eef4fb !important;
        }

        /* ── Error State ── */
        .is-invalid,
        .was-validated .form-control:invalid,
        .was-validated .form-select:invalid {
            border-color: #e74c3c !important;
            background-image: none !important;
        }

        .is-invalid:focus,
        .was-validated .form-control:invalid:focus {
            box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.15) !important;
        }

        .invalid-feedback {
            font-size: 0.78rem;
            font-weight: 600;
            color: #e74c3c;
            margin-top: 0.3rem;
        }

        /* ── Placeholder ── */
        ::placeholder {
            color: #b8c5d0 !important;
            font-size: 0.85rem;
            opacity: 1;
        }

        /* ── Number Input Arrows ── */
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            opacity: 0.5;
        }

        input[type="number"]:hover::-webkit-inner-spin-button,
        input[type="number"]:hover::-webkit-outer-spin-button {
            opacity: 1;
        }

        /* ── Focus Visible ── */
        *:focus-visible {
            outline: 2px solid #1B4F72;
            outline-offset: 2px;
        }

        /* ── Smooth Scrolling ── */
        html {
            scroll-behavior: smooth;
        }

        /* ── Grid Responsive ── */
        @media (max-width: 992px) {
            .hms-card-body > form > div > div[style*="grid-template-columns: 1fr 1fr 1fr"] {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 1rem !important;
            }

            .hms-card-body > form > div > div[style*="grid-template-columns: 1fr 1fr"] {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }
        }

        @media (max-width: 640px) {
            .hms-card-body > form > div > div[style*="grid-template-columns: 1fr 1fr 1fr"] {
                grid-template-columns: 1fr !important;
                gap: 0.875rem !important;
            }

            .hms-card-body > form > div > div[style*="grid-template-columns: 1fr 1fr"] {
                grid-template-columns: 1fr !important;
                gap: 0.875rem !important;
            }
        }

        /* ── Row gutter spacing ── */
        .row.g-3 {
            --bs-gutter-y: 1rem;
        }

        /* ── Loading State ── */
        button[type="submit"]:disabled {
            opacity: 0.7;
            transform: none !important;
            cursor: not-allowed;
        }

        /* ── Section divider hover effect ── */
        .hms-card-body > form > div {
            padding: 0.5rem 0;
            border-radius: 12px;
            transition: background 0.3s ease;
        }

        .hms-card-body > form > div:hover {
            background: rgba(27, 79, 114, 0.02);
        }

        /* ── Small screen improvements ── */
        @media (max-width: 480px) {
            .hms-card-body {
                padding: 1rem !important;
            }

            .hms-card-header {
                padding: 1.25rem !important;
            }

            .hms-card-header h4 {
                font-size: 1rem !important;
            }

            .hms-card-header p {
                font-size: 0.8rem !important;
            }
        }
        </style>
    @endpush
@endsection
