@extends('hospital.layouts.app')
@section('title', 'Patient Check-In')
@section('page-header', '')

@section('page-actions')
@endsection

@section('content')

    <div class="hms-card border-0 shadow-lg" style="border-radius:16px">
        {{-- Header --}}
        <div class="hms-card-header"
            style="background:linear-gradient(135deg,#1B4F72 0%,#2980B9 100%);padding:1.75rem;border-radius:16px 16px 0 0">
            <div style="display:flex;align-items:center;gap:1rem;color:#fff">
                <div
                    style="width:48px;height:48px;background:rgba(255,255,255,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem">
                    <i class="bi bi-person-check-fill"></i>
                </div>
                <div>
                    <h4 style="margin:0;font-weight:700;font-size:1.25rem;color:#fff">Patient Check-In</h4>
                    <p style="margin:.25rem 0 0;font-size:.9rem;opacity:.9">
                        MRD: <strong>{{ $patient->patient_code }}</strong> &nbsp;|&nbsp; Phone appointment arrived - verify
                        details &amp; assign case
                    </p>
                </div>
            </div>
        </div>

        <div class="hms-card-body" style="padding:2rem">
            <form method="POST"
                action="{{ route('hospital.patients.checkin.store', ['slug' => $slug, 'patient' => $patient->id]) }}"
                class="patient-create-form">
                @csrf

                {{-- MRD Number --}}
                <div class="hms-card-body patient-create-card-body" style="padding-bottom:0;margin-bottom:1.5rem">
                    <div style="display:grid;grid-template-columns: repeat(3, 1fr);gap:1.25rem">
                        <div class="form-group">
                            <label class="form-label">MRD No.</label>
                            <input type="text" value="{{ $patient->patient_code }}" class="form-control hms-input" readonly
                                style="background:#eef2f6">
                        </div>
                    </div>
                </div>

                <div class="hms-card-body patient-create-card-body">
                    <div style="display:grid;grid-template-columns: repeat(3, 1fr);gap:1.25rem">

                        {{-- Appointment Date --}}
                        <div class="form-group">
                            <label class="form-label">Appointment Date <span style="color:#C0392B">*</span></label>
                            <input type="text" name="appointment_date" id="appointmentDate"
                                value="{{ old('appointment_date', $patient->appointment_date?->format('Y-m-d')) }}"
                                class="form-control flatpickr hms-input @error('appointment_date') is-invalid @enderror"
                                required>
                            @error('appointment_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        {{-- Contact Number --}}
                        <div class="form-group">
                            <label class="form-label">Contact Number <span style="color:#C0392B">*</span></label>
                            <input type="text" name="contact_no" id="contactNo"
                                value="{{ old('contact_no', $patient->contact_no) }}" data-intl-phone
                                class="form-control hms-input @error('contact_no') is-invalid @enderror"
                                placeholder="+919876543210" required>
                            @error('contact_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        {{-- WhatsApp No --}}
                        <div class="form-group">
                            <label class="form-label">WhatsApp No</label>
                            <input type="text" name="whatsapp_no" id="whatsappNo"
                                value="{{ old('whatsapp_no', $patient->whatsapp_no) }}" data-intl-phone
                                class="form-control hms-input @error('whatsapp_no') is-invalid @enderror"
                                placeholder="Same if blank">
                            @error('whatsapp_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        {{-- Name Fields --}}
                        <div class="form-group">
                            <label class="form-label">First Name <span style="color:#C0392B">*</span></label>
                            <input type="text" name="first_name" id="firstName"
                                value="{{ old('first_name', $patient->first_name) }}"
                                class="form-control hms-input @error('first_name') is-invalid @enderror" required>
                            @error('first_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Surname <span style="color:#C0392B">*</span></label>
                            <input type="text" name="last_name" id="lastName"
                                value="{{ old('last_name', $patient->last_name) }}"
                                class="form-control hms-input @error('last_name') is-invalid @enderror" required>
                            @error('last_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" id="middleName"
                                value="{{ old('middle_name', $patient->middle_name) }}"
                                class="form-control hms-input">
                        </div>

                        {{-- Case Type --}}
                        <div class="form-group">
                            <label class="form-label">Case Type <span style="color:#C0392B">*</span></label>
                            <select name="case_id" id="caseSelect"
                                class="form-control select2 hms-select @error('case_id') is-invalid @enderror" required>
                                <option value="">Select Case</option>
                                @foreach($cases as $c)
                                    <option value="{{ $c->id }}" data-fee="{{ $c->fee ?? 0 }}"
                                        @selected(old('case_id', $patient->case_id) == $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                            @error('case_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        {{-- Case Fee --}}
                        <div class="form-group">
                            <label class="form-label">Case Fee ({{ currency_symbol() }}) <span style="color:#C0392B">*</span></label>
                            <input type="number" name="case_fee" id="caseFee"
                                value="{{ old('case_fee', $patient->case_fee ?? '0') }}"
                                class="form-control hms-input @error('case_fee') is-invalid @enderror" required readonly>
                            @error('case_fee')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        {{-- Doctor --}}
                        <div class="form-group">
                            <label class="form-label">Doctor Name <span style="color:#C0392B">*</span></label>
                            <select name="doctor_id"
                                class="form-control select2 hms-select @error('doctor_id') is-invalid @enderror" required>
                                <option value="">Select Doctor</option>
                                @foreach($doctors as $doc)
                                    <option value="{{ $doc->id }}" @selected(old('doctor_id', $patient->doctor_id) == $doc->id)>
                                        {{ $doc->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('doctor_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        {{-- City / District / State --}}
                        <div class="form-group">
                            <label class="form-label">City <span style="color:#C0392B">*</span></label>
                            <div style="display:flex;gap:5px">
                                <select name="location_id" id="locationSelect"
                                    class="form-control select2 hms-select @error('location_id') is-invalid @enderror"
                                    required>
                                    <option value="">Select City</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" data-district="{{ $loc->district?->name }}"
                                            data-state="{{ $loc->state?->name }}"
                                            @selected(old('location_id', $patient->location_id) == $loc->id)>
                                            {{ $loc->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" id="btnAddLocation" class="hms-btn hms-btn-outline">+</button>
                            </div>
                            @error('location_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">District</label>
                            <input type="text" id="district" class="form-control hms-input" readonly
                                placeholder="Auto-filled"
                                value="{{ $patient->masterCity?->district?->name ?? $patient->location?->district?->name }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">State</label>
                            <input type="text" id="state" class="form-control hms-input" readonly
                                placeholder="Auto-filled"
                                value="{{ $patient->masterCity?->state?->name ?? $patient->location?->state?->name }}">
                        </div>

                        {{-- Age / Gender / Occupation --}}
                        <div class="form-group">
                            <label class="form-label">Age <span style="color:#C0392B">*</span></label>
                            <input type="number" name="age" id="age" value="{{ old('age', $patient->age) }}"
                                class="form-control hms-input @error('age') is-invalid @enderror" required>
                            @error('age')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gender <span style="color:#C0392B">*</span></label>
                            <select name="gender" id="gender"
                                class="form-control hms-select @error('gender') is-invalid @enderror" required>
                                <option value="">Select Gender</option>
                                <option value="male" @selected(old('gender', $patient->gender) === 'male')>Male</option>
                                <option value="female" @selected(old('gender', $patient->gender) === 'female')>Female</option>
                                <option value="other" @selected(old('gender', $patient->gender) === 'other')>Other</option>
                            </select>
                            @error('gender')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Occupation</label>
                            <input type="text" name="occupation" id="occupation"
                                value="{{ old('occupation', $patient->occupation) }}"
                                class="form-control hms-input">
                        </div>

                        {{-- Time Slot (check-in only - not on walk-in) --}}
                        <div class="form-group">
                            <label class="form-label">Time Slot</label>
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

                        {{-- Referred By --}}
                        <div class="form-group">
                            <label class="form-label">Referred By</label>
                            <select name="referrer_id"
                                class="form-control select2 hms-select @error('referrer_id') is-invalid @enderror">
                                <option value="">Select Referrer</option>
                                @foreach($referrers as $r)
                                    <option value="{{ $r->id }}"
                                        @selected(old('referrer_id', $patient->referrer_id) == $r->id)>{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div
                        style="display:flex;gap:0.875rem;margin-top:2.5rem;padding-top:1.75rem;border-top:1px solid #E2E8F0">
                        <button type="submit" class="hms-btn hms-btn-primary">
                            <i class="bi bi-check-circle-fill"></i> Check In &amp; Print Bill
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Toast (for Add City) --}}
    <div id="contactToast"
        style="display:none;position:fixed;top:1.25rem;right:1.25rem;z-index:9999;
               background:#1B4F72;color:#fff;padding:.75rem 1.25rem;border-radius:.5rem;
               box-shadow:0 4px 12px rgba(0,0,0,.2);font-size:.9rem;max-width:320px">
        <i class="fa-solid fa-circle-check" style="margin-right:.4rem"></i>
        <span id="contactToastMsg"></span>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (window.HmsIntlPhone) {
                    HmsIntlPhone.bind(document.getElementById('contactNo'));
                    HmsIntlPhone.bind(document.getElementById('whatsappNo'));
                }

                if (typeof $.fn.select2 !== 'undefined') {
                    $('.select2').select2({ width: '100%' });
                }

                if (typeof flatpickr !== 'undefined') {
                    flatpickr('#appointmentDate', {
                        dateFormat: 'Y-m-d',
                        allowInput: true
                    });
                }

                var caseSelectEl = document.getElementById('caseSelect');
                var caseFeeEl = document.getElementById('caseFee');
                if (caseSelectEl && caseFeeEl) {
                    $(caseSelectEl).on('change', function () {
                        var opt = this.options[this.selectedIndex];
                        caseFeeEl.value = opt ? (opt.dataset.fee || 0) : 0;
                    });
                }

                var locationSelectEl = document.getElementById('locationSelect');
                var districtEl = document.getElementById('district');
                var stateEl = document.getElementById('state');

                function syncLocationCheckin() {
                    if (!locationSelectEl || !districtEl || !stateEl) { return; }
                    var opt = locationSelectEl.options[locationSelectEl.selectedIndex];
                    if (locationSelectEl.value && opt) {
                        districtEl.value = opt.getAttribute('data-district') || '';
                        stateEl.value = opt.getAttribute('data-state') || '';
                    }
                }

                if (locationSelectEl) {
                    $(locationSelectEl).on('change', syncLocationCheckin);
                    if (locationSelectEl.value) {
                        syncLocationCheckin();
                    }
                }

                // Add City (+) â€” same as walk-in
                var addBtn = document.getElementById('btnAddLocation');
                if (addBtn) {
                    var modalHtml = '\n<div class="modal fade" id="modalAddLocation" tabindex="-1" aria-hidden="true">\n  <div class="modal-dialog modal-sm modal-dialog-centered">\n    <div class="modal-content">\n      <div class="modal-header">\n        <h5 class="modal-title" style="color:#fff">Add City</h5>\n        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>\n      </div>\n      <div class="modal-body">\n        <div id="addLocErrors" class="text-danger mb-2"></div>\n        <div class="mb-2"><label class="form-label">City</label><input type="text" id="newCity" class="form-control" placeholder="City name"></div>\n        <div class="mb-2"><label class="form-label">District</label><input type="text" id="newDistrict" class="form-control" placeholder="District"></div>\n        <div class="mb-2"><label class="form-label">State</label><input type="text" id="newState" class="form-control" placeholder="State"></div>\n      </div>\n      <div class="modal-footer">\n        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>\n        <button type="button" id="saveLocationBtn" class="btn btn-primary">Add</button>\n      </div>\n    </div>\n  </div>\n</div>\n';
                    document.body.insertAdjacentHTML('beforeend', modalHtml);

                    var modalEl = document.getElementById('modalAddLocation');
                    var saveBtn = document.getElementById('saveLocationBtn');
                    var newCity = document.getElementById('newCity');
                    var newDistrict = document.getElementById('newDistrict');
                    var newState = document.getElementById('newState');
                    var addLocErrors = document.getElementById('addLocErrors');

                    addBtn.addEventListener('click', function () {
                        addLocErrors.innerHTML = '';
                        newCity.value = '';
                        newDistrict.value = '';
                        newState.value = '';
                        new bootstrap.Modal(modalEl).show();
                    });

                    saveBtn.addEventListener('click', function () {
                        addLocErrors.innerHTML = '';
                        var city = newCity.value.trim();
                        var district = newDistrict.value.trim();
                        var state = newState.value.trim();
                        if (!city) { addLocErrors.textContent = 'City is required.'; return; }
                        if (!state) { addLocErrors.textContent = 'State is required.'; return; }

                        fetch('{{ route("hospital.masters.basic.ajax.store", ["slug" => $slug, "type" => "locations"]) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ city: city, district: district, state: state })
                        })
                            .then(function (res) { return res.json(); })
                            .then(function (data) {
                                if (!data || !data.success) {
                                    addLocErrors.textContent = (data && data.message) || 'Failed to add city.';
                                    return;
                                }
                                var sel = document.getElementById('locationSelect');
                                var opt = document.createElement('option');
                                opt.value = data.id;
                                opt.text = city;
                                opt.setAttribute('data-district', district);
                                opt.setAttribute('data-state', state);
                                sel.appendChild(opt);
                                $(sel).val(data.id).trigger('change');
                                var modal = bootstrap.Modal.getInstance(modalEl);
                                if (modal) modal.hide();
                                var toast = document.getElementById('contactToast');
                                if (toast) {
                                    document.getElementById('contactToastMsg').textContent = 'City added.';
                                    toast.style.display = 'block';
                                    setTimeout(function () { toast.style.display = 'none'; }, 2500);
                                }
                            })
                            .catch(function () { addLocErrors.textContent = 'Network error.'; });
                    });
                }
            });
        </script>
    @endpush

    @push('styles')
        <style>
            .patient-create-form .form-group {
                margin-bottom: 0;
            }

            .patient-create-form .form-label {
                display: block;
                margin-bottom: 0.5rem;
                font-size: 0.7rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                color: #5a6e7c;
            }

            .patient-create-form .form-group:focus-within .form-label {
                color: #1B4F72;
            }

            .patient-create-form .hms-input,
            .patient-create-form .hms-select,
            .patient-create-form select.form-control,
            .patient-create-form input.form-control {
                width: 100%;
                padding: 0.75rem 1rem;
                font-size: 0.95rem;
                border: 1.5px solid #e2e8f0;
                border-radius: 14px !important;
                background: #ffffff;
                transition: all 0.2s ease;
                color: #1a2a3a;
            }

            .patient-create-form .hms-input:focus,
            .patient-create-form .hms-select:focus {
                outline: none;
                border-color: #1B4F72;
                box-shadow: 0 0 0 4px rgba(27, 79, 114, 0.1);
            }

            .patient-create-form .hms-input[readonly] {
                background: #f4f7fb !important;
                border-color: #e2e8f0 !important;
                color: #4a6276 !important;
            }

            .patient-create-form .select2-container--default .select2-selection--single {
                height: auto !important;
                padding: 0.5rem 1rem;
                border: 1.5px solid #e2e8f0 !important;
                border-radius: 14px !important;
                background: #ffffff !important;
            }

            .patient-create-form .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 1.4 !important;
                padding: 0 !important;
                color: #1a2a3a !important;
                font-size: 0.95rem;
            }

            .patient-create-form .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 100% !important;
                right: 12px !important;
            }

            #btnAddLocation {
                width: 20px !important;
                height: 35px !important;
                border-radius: 12px !important;
                flex-shrink: 0;
            }

            #btnAddLocation:hover {
                background: #1B4F72 !important;
                color: white !important;
                border-color: #1B4F72 !important;
            }

            @media (max-width: 991px) {
                .patient-create-form [style*="grid-template-columns: repeat(3"] {
                    grid-template-columns: 1fr 1fr !important;
                }
            }

            @media (max-width: 575px) {
                .patient-create-form [style*="grid-template-columns"] {
                    grid-template-columns: 1fr !important;
                }
            }
        </style>
    @endpush
@endsection
