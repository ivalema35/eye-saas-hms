@extends('hospital.layouts.app')
@section('title', 'Patient Check-In')
@section('page-header', '')

@section('content')

    <div id="contactToast"
        style="display:none;position:fixed;top:1rem;right:1rem;z-index:9999;background:#1B4F72;color:#fff;padding:.6rem 1rem;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.2);font-size:.85rem;max-width:320px">
        <i class="bi bi-check-circle-fill" style="margin-right:.35rem"></i>
        <span id="contactToastMsg"></span>
    </div>

    <div class="rpc-page">
        @include('hospital.patients.partials.compact-form-header', ['slug' => $slug, 'activeMode' => 'phone'])

        <div class="rpc-card">
            <h2 class="rpc-form-title">Patient Check-In</h2>
            <p class="rpc-checkin-note">
                <i class="bi bi-telephone-fill"></i>
                Phone appointment arrived — verify details &amp; assign case &nbsp;|&nbsp;
                MRD: <strong>{{ $patient->patient_code }}</strong>
            </p>
            <div class="rpc-form-body">
                @if($errors->any())
                    <div class="alert alert-danger py-2 px-3 mb-2" style="font-size:.82rem;border-radius:8px">
                        <ul class="mb-0 ps-3">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
                    </div>
                @endif

                <form method="POST"
                    action="{{ route('hospital.patients.checkin.store', ['slug' => $slug, 'patient' => $patient->id]) }}"
                    id="checkinPatientForm">
                    @csrf

                    {{-- Row 1 --}}
                    <div class="rpc-grid rpc-grid--6">
                        <div class="rpc-field">
                            <label class="form-label">Appointment Date <span class="req">*</span></label>
                            <input type="text" name="appointment_date" id="appointmentDate"
                                value="{{ old('appointment_date', $patient->appointment_date?->format('Y-m-d')) }}"
                                class="form-control flatpickr hms-input @error('appointment_date') is-invalid @enderror" required>
                            @error('appointment_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">Contact <span class="req">*</span></label>
                            <input type="text" name="contact_no" id="contactNo"
                                value="{{ old('contact_no', $patient->contact_no) }}" data-intl-phone
                                class="form-control hms-input @error('contact_no') is-invalid @enderror"
                                placeholder="10-digit number" required>
                            @error('contact_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">WhatsApp No.</label>
                            <input type="text" name="whatsapp_no" id="whatsappNo"
                                value="{{ old('whatsapp_no', $patient->whatsapp_no) }}" data-intl-phone
                                class="form-control hms-input @error('whatsapp_no') is-invalid @enderror"
                                placeholder="Same if blank">
                            @error('whatsapp_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">MRD No.</label>
                            <input type="text" value="{{ $patient->patient_code }}" class="form-control hms-input" readonly tabindex="-1">
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">First Name <span class="req">*</span></label>
                            <input type="text" name="first_name" id="firstName"
                                value="{{ old('first_name', $patient->first_name) }}"
                                class="form-control hms-input @error('first_name') is-invalid @enderror" required>
                            @error('first_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">Surname <span class="req">*</span></label>
                            <input type="text" name="last_name" id="lastName"
                                value="{{ old('last_name', $patient->last_name) }}"
                                class="form-control hms-input @error('last_name') is-invalid @enderror" required>
                            @error('last_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Row 2 --}}
                    <div class="rpc-grid rpc-grid--6">
                        <div class="rpc-field">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" id="middleName"
                                value="{{ old('middle_name', $patient->middle_name) }}"
                                class="form-control hms-input">
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">Case Type <span class="req">*</span></label>
                            <select name="case_id" id="caseSelect"
                                class="form-control select2 hms-select rpc-auto-open @error('case_id') is-invalid @enderror" required>
                                <option value="">--Select--</option>
                                @foreach($cases as $c)
                                    <option value="{{ $c->id }}" data-fee="{{ $c->fee ?? 0 }}"
                                        @selected(old('case_id', $patient->case_id) == $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                            @error('case_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">Case Fee ({{ currency_symbol() }}) <span class="req">*</span></label>
                            <input type="number" name="case_fee" id="caseFee"
                                value="{{ old('case_fee', $patient->case_fee ?? '0') }}"
                                class="form-control hms-input @error('case_fee') is-invalid @enderror" required readonly>
                            @error('case_fee')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">City <span class="req">*</span></label>
                            <div class="rpc-city-row">
                                <select name="location_id" id="locationSelect"
                                    class="form-control select2 hms-select rpc-auto-open @error('location_id') is-invalid @enderror" required>
                                    <option value="">Select or add</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" data-district="{{ $loc->district?->name }}"
                                            data-state="{{ $loc->state?->name }}"
                                            @selected(old('location_id', $patient->location_id) == $loc->id)>
                                            {{ $loc->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" id="btnAddLocation" class="hms-btn hms-btn-outline rpc-city-add">+</button>
                            </div>
                            @error('location_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">District</label>
                            <input type="text" id="district" class="form-control hms-input" readonly placeholder="Auto" tabindex="-1"
                                value="{{ $patient->masterCity?->district?->name ?? $patient->location?->district?->name }}">
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">State</label>
                            <input type="text" id="state" class="form-control hms-input" readonly placeholder="Auto" tabindex="-1"
                                value="{{ $patient->masterCity?->state?->name ?? $patient->location?->state?->name }}">
                        </div>
                    </div>

                    {{-- Row 3 --}}
                    <div class="rpc-grid rpc-grid--6">
                        <div class="rpc-field">
                            <label class="form-label">Doctor <span class="req">*</span></label>
                            <select name="doctor_id" id="doctorSelect"
                                class="form-control select2 hms-select rpc-auto-open @error('doctor_id') is-invalid @enderror" required>
                                <option value="">--Select--</option>
                                @foreach($doctors as $doc)
                                    <option value="{{ $doc->id }}" @selected(old('doctor_id', $patient->doctor_id) == $doc->id)>
                                        {{ $doc->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('doctor_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">Slot Time</label>
                            <select name="slot_id" id="slotSelect" class="form-control select2 hms-select rpc-auto-open">
                                <option value="">--Select--</option>
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
                        <div class="rpc-field">
                            <label class="form-label">Age <span class="req">*</span></label>
                            <input type="number" name="age" id="age" value="{{ old('age', $patient->age) }}"
                                class="form-control hms-input @error('age') is-invalid @enderror" required
                                placeholder="Age" min="0" max="150">
                            @error('age')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">Gender <span class="req">*</span></label>
                            <select name="gender" id="gender"
                                class="form-control hms-select rpc-auto-open @error('gender') is-invalid @enderror" required>
                                <option value="">SELECT</option>
                                <option value="male" @selected(old('gender', $patient->gender) === 'male')>Male</option>
                                <option value="female" @selected(old('gender', $patient->gender) === 'female')>Female</option>
                                <option value="other" @selected(old('gender', $patient->gender) === 'other')>Other</option>
                            </select>
                            @error('gender')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">Occupation</label>
                            <input type="text" name="occupation" id="occupation"
                                value="{{ old('occupation', $patient->occupation) }}"
                                class="form-control hms-input" placeholder="Occupation">
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">Referred By</label>
                            <select name="referrer_id" id="referrerSelect"
                                class="form-control select2 hms-select rpc-auto-open @error('referrer_id') is-invalid @enderror">
                                <option value="">Select referrer</option>
                                @foreach($referrers as $r)
                                    <option value="{{ $r->id }}"
                                        @selected(old('referrer_id', $patient->referrer_id) == $r->id)>{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="rpc-actions">
                        <button type="submit" id="rpcSubmitBtn" class="rpc-submit">
                            <i class="bi bi-check-circle-fill"></i> Check In &amp; Print Bill
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/reception-patient-form.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/reception-patient-form.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            ReceptionPatientForm.initPlugins({
                dateFormat: 'Y-m-d',
                allowInput: true
            });
            ReceptionPatientForm.bindAutoOpenSelects(document.querySelector('.rpc-page'));
            ReceptionPatientForm.bindSubmitFocus('#checkinPatientForm', '#rpcSubmitBtn');

            if (window.HmsIntlPhone) {
                HmsIntlPhone.bind(document.getElementById('contactNo'));
                HmsIntlPhone.bind(document.getElementById('whatsappNo'));
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

            var addBtn = document.getElementById('btnAddLocation');
            if (addBtn) {
                var modalHtml = '<div class="modal fade" id="modalAddLocation" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" style="color:#fff">Add City</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div id="addLocErrors" class="text-danger mb-2"></div><div class="mb-2"><label class="form-label">City</label><input type="text" id="newCity" class="form-control"></div><div class="mb-2"><label class="form-label">District</label><input type="text" id="newDistrict" class="form-control"></div><div class="mb-2"><label class="form-label">State</label><input type="text" id="newState" class="form-control"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" id="saveLocationBtn" class="btn btn-primary">Add</button></div></div></div></div>';
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                var modalEl = document.getElementById('modalAddLocation');

                addBtn.addEventListener('click', function () {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                });

                document.getElementById('saveLocationBtn').addEventListener('click', function () {
                    var city = document.getElementById('newCity').value.trim();
                    var district = document.getElementById('newDistrict').value.trim();
                    var state = document.getElementById('newState').value.trim();
                    var addLocErrors = document.getElementById('addLocErrors');
                    addLocErrors.textContent = '';
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
                            bootstrap.Modal.getInstance(modalEl).hide();
                            ReceptionPatientForm.showToast('City added.');
                        })
                        .catch(function () { addLocErrors.textContent = 'Network error.'; });
                });
            }

            document.getElementById('rpcSubmitBtn').addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('checkinPatientForm').requestSubmit();
                }
            });
        });
    </script>
@endpush
