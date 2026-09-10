@extends('hospital.layouts.app')
@section('title', 'Register Walk-in Patient')
@section('page-header', '')

@section('content')

    <div id="contactToast"
        style="display:none;position:fixed;top:1rem;right:1rem;z-index:9999;background:#1B4F72;color:#fff;padding:.6rem 1rem;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.2);font-size:.85rem;max-width:320px">
        <i class="bi bi-check-circle-fill" style="margin-right:.35rem"></i>
        <span id="contactToastMsg"></span>
    </div>

    <div class="rpc-page">
        @include('hospital.patients.partials.compact-form-header', ['slug' => $slug, 'activeMode' => 'walkin'])

        <div class="rpc-card">
            <h2 class="rpc-form-title">Add Patient</h2>
            <div class="rpc-form-body">
                @if($errors->any())
                    <div class="alert alert-danger py-2 px-3 mb-2" style="font-size:.82rem;border-radius:8px">
                        <ul class="mb-0 ps-3">@foreach($errors->all() as $err)
                        <li>{{ $err }}</li>@endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('hospital.patients.store', ['slug' => $slug]) }}"
                    class="patient-create-form" id="walkinPatientForm">
                    @csrf
                    <input type="hidden" name="ot_appointment_id" id="otAppointmentId"
                        value="{{ old('ot_appointment_id', $prefillOtAppointment['id'] ?? '') }}">

                    <button type="button" class="rpc-ot-toggle" id="rpcOtToggle" aria-expanded="false">
                        <span><i class="bi bi-hospital"></i> OT check-in search (optional)</span>
                        <i class="bi bi-chevron-down rpc-ot-chevron"></i>
                    </button>
                    <div class="rpc-ot-panel" id="rpcOtPanel">
                        <div class="rpc-field position-relative">
                            <label class="form-label">Search OT Appointment</label>
                            <input type="text" id="appointmentSearch" class="form-control hms-input"
                                placeholder="Name, mobile, or APT number...">
                            <div id="appointmentSuggestions"
                                class="position-absolute w-100 bg-white rounded d-none rpc-suggestions"
                                style="top:100%;margin-top:4px"></div>
                            <div id="appointmentLinkedNote" class="rpc-ot-linked d-none">
                                <i class="bi bi-check2-circle"></i> Linked to <strong id="appointmentLinkedNumber"></strong>
                            </div>
                        </div>
                    </div>

                    {{-- Row 1 --}}
                    <div class="rpc-grid rpc-grid--6">
                        <div class="rpc-field">
                            <label class="form-label">Appointment Date <span class="req">*</span></label>
                            <input type="text" name="appointment_date" class="form-control flatpickr hms-input"
                                value="{{ old('appointment_date', now()->format('Y-m-d')) }}" required>
                        </div>
                        <div class="rpc-field position-relative">
                            <label class="form-label">Contact <span class="req">*</span></label>
                            <input type="text" name="contact_no" id="contactNo" class="form-control hms-input"
                                data-intl-phone required placeholder="10-digit number">
                            <div id="patientSuggestions"
                                class="position-absolute w-100 bg-white rounded d-none rpc-suggestions"
                                style="top:100%;margin-top:4px"></div>
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">WhatsApp No.</label>
                            <input type="text" name="whatsapp_no" id="whatsappNo" class="form-control hms-input"
                                data-intl-phone placeholder="Same if blank">
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">MRD No.</label>
                            <input type="text" value="{{ $nextMrd }}" class="form-control hms-input" readonly tabindex="-1">
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">First Name <span class="req">*</span></label>
                            <input type="text" name="first_name" id="firstName" class="form-control hms-input" required
                                placeholder="First name">
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">Surname <span class="req">*</span></label>
                            <input type="text" name="last_name" id="lastName" class="form-control hms-input" required
                                placeholder="Surname">
                        </div>
                    </div>

                    {{-- Row 2 --}}
                    <div class="rpc-grid rpc-grid--6">
                        <div class="rpc-field">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" id="middleName" class="form-control hms-input"
                                placeholder="Middle name">
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">Case Type <span class="req">*</span></label>
                            <select name="case_id" id="caseSelect" class="form-control select2 hms-select rpc-auto-open"
                                required>
                                <option value="">--Select--</option>
                                @foreach($cases as $c)
                                    <option value="{{ $c->id }}" data-fee="{{ $c->fee ?? 0 }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">Case Fee ({{ currency_symbol() }}) <span class="req">*</span></label>
                            <input type="number" name="case_fee" id="caseFee" class="form-control hms-input" required
                                readonly>
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">City <span class="req">*</span></label>
                            <div class="rpc-city-row">
                                <select name="location_id" id="locationSelect"
                                    class="form-control select2 hms-select rpc-auto-open" required>
                                    <option value="">Select or add</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" data-district="{{ $loc->district?->name }}"
                                            data-state="{{ $loc->state?->name }}">{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" id="btnAddLocation"
                                    class="hms-btn hms-btn-outline rpc-city-add">+</button>
                            </div>
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">District</label>
                            <input type="text" id="district" class="form-control hms-input" readonly placeholder="Auto"
                                tabindex="-1">
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">State</label>
                            <input type="text" id="state" class="form-control hms-input" readonly placeholder="Auto"
                                tabindex="-1">
                        </div>
                    </div>

                    {{-- Row 3 --}}
                    <div class="rpc-grid">
                        <div class="rpc-field">
                            <label class="form-label">Doctor <span class="req">*</span></label>
                            <select name="doctor_id" id="doctorSelect" class="form-control select2 hms-select rpc-auto-open"
                                required>
                                <option value="">--Select--</option>
                                @foreach($doctors as $doc)
                                    <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">Age <span class="req">*</span></label>
                            <input type="number" name="age" id="age" class="form-control hms-input" required
                                placeholder="Age" min="0" max="150">
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">Gender <span class="req">*</span></label>
                            <select name="gender" id="gender" class="form-control hms-select rpc-auto-open" required>
                                <option value="">SELECT</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">Occupation</label>
                            <input type="text" name="occupation" id="occupation" class="form-control hms-input"
                                placeholder="Occupation">
                        </div>
                        <div class="rpc-field">
                            <label class="form-label">Referred By</label>
                            <select name="referrer_id" id="referrerSelect"
                                class="form-control select2 hms-select rpc-auto-open">
                                <option value="">Select referrer</option>
                                @foreach($referrers as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="rpc-actions">
                        <button type="submit" id="rpcSubmitBtn" class="rpc-submit">
                            <i class="bi bi-person-plus-fill"></i> Add Patient
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
            var searchUrl = '{{ route('hospital.patients.search-by-contact', ['slug' => $slug]) }}';
            var currentHospitalName = '{{ addslashes($currentHospitalName) }}';

            ReceptionPatientForm.initPlugins({
                dateFormat: 'Y-m-d',
                defaultDate: 'today',
                minDate: 'today'
            });
            ReceptionPatientForm.bindAutoOpenSelects(document.querySelector('.rpc-page'));
            ReceptionPatientForm.bindSubmitFocus('#walkinPatientForm', '#rpcSubmitBtn');
            ReceptionPatientForm.bindOtCollapse('#rpcOtToggle', '#rpcOtPanel');

            function showToast(msg, isError) {
                ReceptionPatientForm.showToast(msg, isError);
            }

            function normalizeGender(raw) {
                if (!raw) { return ''; }
                var g = String(raw).trim().toLowerCase();
                if (g === 'm' || g === 'male') { return 'male'; }
                if (g === 'f' || g === 'female') { return 'female'; }
                if (g === 'other' || g === 'o') { return 'other'; }
                return g;
            }

            function selectAndTrigger(el, value) {
                if (!el || value === null || value === undefined || value === '') { return; }
                var strVal = String(value);
                if (!el.querySelector('option[value="' + strVal + '"]')) { return; }
                if (typeof $ !== 'undefined') {
                    $(el).val(strVal).trigger('change');
                } else {
                    el.value = strVal;
                    el.dispatchEvent(new Event('change'));
                }
            }

            var contactInput = document.getElementById('contactNo');
            var patientSuggestions = document.getElementById('patientSuggestions');
            var foundPatientsList = [];

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
                selectAndTrigger(document.getElementById('gender'), normalizeGender(p.gender));
                selectAndTrigger(document.getElementById('locationSelect'), p.location_id);
                if (patientSuggestions) { patientSuggestions.classList.add('d-none'); }
                showToast(p.type === 'shared' ? 'Shared patient selected — verify city.' : 'Patient details filled.');
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
                                var badgeLabel = p.type === 'shared' ? (p.hospital_name || 'Partner') : currentHospitalName;
                                html += '<div class="patient-suggestion-card" onclick="fillSelectedPatient(' + idx + ')">' +
                                    '<div class="fw-bold" style="color:#1B4F72">' + displayName + '</div>' +
                                    '<div class="small text-muted">Age: ' + (p.age ?? '-') + ' | ' + badgeLabel + '</div></div>';
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

            var appointmentSearchUrl = '{{ route('hospital.ot.appointments.search', ['slug' => $slug]) }}';
            var appointmentInput = document.getElementById('appointmentSearch');
            var appointmentSuggestions = document.getElementById('appointmentSuggestions');
            var foundAppointmentsList = [];
            var appointmentSearchTimer = null;

            window.fillSelectedAppointment = function (index) {
                if (index < 0 || index >= foundAppointmentsList.length) { return; }
                var a = foundAppointmentsList[index];
                var setVal = function (id, val) {
                    var el = document.getElementById(id);
                    if (el && val !== null && val !== undefined && val !== '') { el.value = val; }
                };
                setVal('firstName', a.patient_name);
                setVal('lastName', a.surname);
                setVal('middleName', a.middle_name);
                setVal('whatsappNo', a.whatsapp_no);
                setVal('age', a.age);
                setVal('occupation', a.occupation);
                if (contactInput && a.mobile_no) { contactInput.value = a.mobile_no; }
                selectAndTrigger(document.getElementById('gender'), normalizeGender(a.gender));
                selectAndTrigger(document.getElementById('locationSelect'), a.location_id);
                selectAndTrigger(document.getElementById('doctorSelect'), a.doctor_id);
                selectAndTrigger(document.getElementById('referrerSelect'), a.referrer_id);
                document.getElementById('otAppointmentId').value = a.id;
                appointmentInput.value = a.appointment_number + ' — ' + a.patient_name;
                document.getElementById('appointmentLinkedNumber').textContent = a.appointment_number;
                document.getElementById('appointmentLinkedNote').classList.remove('d-none');
                if (appointmentSuggestions) { appointmentSuggestions.classList.add('d-none'); }
                showToast('OT appointment linked.');
            };

            if (appointmentInput && appointmentSuggestions) {
                appointmentInput.addEventListener('input', function () {
                    var term = this.value.trim();
                    document.getElementById('otAppointmentId').value = '';
                    document.getElementById('appointmentLinkedNote').classList.add('d-none');
                    if (appointmentSearchTimer) { clearTimeout(appointmentSearchTimer); }
                    if (term.length < 3) {
                        appointmentSuggestions.classList.add('d-none');
                        return;
                    }
                    appointmentSearchTimer = setTimeout(function () {
                        fetch(appointmentSearchUrl + '?q=' + encodeURIComponent(term), {
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                        })
                            .then(function (res) { return res.json(); })
                            .then(function (data) {
                                if (!data.found || !data.appointments || data.appointments.length === 0) {
                                    appointmentSuggestions.classList.add('d-none');
                                    return;
                                }
                                foundAppointmentsList = data.appointments;
                                var html = '';
                                data.appointments.forEach(function (a, idx) {
                                    html += '<div class="patient-suggestion-card" onclick="fillSelectedAppointment(' + idx + ')">' +
                                        '<div class="fw-bold" style="color:#1B4F72">' + a.appointment_number + ' — ' + a.patient_name + '</div>' +
                                        '<div class="small text-muted">' + a.mobile_no + '</div></div>';
                                });
                                appointmentSuggestions.innerHTML = html;
                                appointmentSuggestions.classList.remove('d-none');
                            })
                            .catch(function () { appointmentSuggestions.classList.add('d-none'); });
                    }, 300);
                });
            }

            @if(!empty($prefillOtAppointment))
                foundAppointmentsList = [@json($prefillOtAppointment)];
                document.getElementById('rpcOtPanel').classList.add('is-open');
                document.getElementById('rpcOtToggle').setAttribute('aria-expanded', 'true');
                setTimeout(function () { fillSelectedAppointment(0); }, 150);
            @endif

            document.addEventListener('click', function (e) {
                if (appointmentSuggestions && appointmentInput && e.target !== appointmentInput && !appointmentSuggestions.contains(e.target)) {
                    appointmentSuggestions.classList.add('d-none');
                }
            });

            var caseSelect = document.getElementById('caseSelect');
            var caseFeeEl = document.getElementById('caseFee');
            if (caseSelect && caseFeeEl) {
                $(caseSelect).on('change', function () {
                    var opt = this.options[this.selectedIndex];
                    caseFeeEl.value = opt ? (opt.dataset.fee || 0) : 0;
                });
            }

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

            var addBtn = document.getElementById('btnAddLocation');
            if (addBtn) {
                var modalHtml = '<div class="modal fade" id="modalAddLocation" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" style="color:#1b4f72">Add City</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div id="addLocErrors" class="text-danger mb-2"></div><div class="mb-2"><label class="form-label">City</label><input type="text" id="newCity" class="form-control"></div><div class="mb-2"><label class="form-label">District</label><input type="text" id="newDistrict" class="form-control"></div><div class="mb-2"><label class="form-label">State</label><input type="text" id="newState" class="form-control"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" id="saveLocationBtn" class="btn btn-primary">Add</button></div></div></div></div>';
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
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ city: city, district: district, state: state })
                    })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (!data || !data.success) {
                                addLocErrors.textContent = data?.message || 'Failed to add city.';
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
                            showToast('City added.');
                        })
                        .catch(function () { addLocErrors.textContent = 'Network error.'; });
                });
            }

            document.getElementById('rpcSubmitBtn').addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('walkinPatientForm').requestSubmit();
                }
            });
        });
    </script>
@endpush