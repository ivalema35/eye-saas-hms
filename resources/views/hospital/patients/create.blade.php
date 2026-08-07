@extends('hospital.layouts.app')
@section('title', 'Register Walk-in Patient')
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
                    <i class="bi bi-plus-circle-fill"></i>
                </div>
                <div>
                    <h4 style="margin:0;font-weight:700;font-size:1.25rem;color:#fff">Register New Patient</h4>
                    <p style="margin:0.25rem 0 0;font-size:0.9rem;opacity:0.9">Capture patient information and appointment
                        details</p>
                </div>
            </div>
        </div>
        <div class="hms-card-body" style="padding:2rem">
            <form method="POST" action="{{ route('hospital.patients.store', ['slug' => $slug]) }}"
                class="patient-create-form">
                @csrf
                <input type="hidden" name="ot_appointment_id" id="otAppointmentId"
                    value="{{ old('ot_appointment_id', $prefillOtAppointment['id'] ?? '') }}">

                {{-- OT Appointment Search — Reception check-in (OT Workflow Upgrade Phase 2) --}}
                <div class="hms-card-body patient-create-card-body" style="padding-bottom:0">
                    <div class="alert alert-info border-0 mb-3" style="background:rgba(27,79,114,.06);color:#1B4F72;border-radius:12px;">
                        <i class="bi bi-hospital me-1"></i>
                        <strong>OT walk-in / pre-booked patient?</strong>
                        Search the OT appointment below first — form will auto-fill and today’s OPD visit will be linked to that appointment.
                    </div>
                    <div class="form-group position-relative" style="max-width:480px">
                        <label class="form-label"><i class="bi bi-search me-1"></i> Search OT Appointment (Name / Mobile / APT-000123)</label>
                        <input type="text" id="appointmentSearch" class="form-control hms-input" placeholder="Type name, mobile, or APT number for OT check-in...">
                        <div id="appointmentSuggestions" class="position-absolute w-100 bg-white shadow-lg rounded d-none"
                             style="z-index:1050; border:1px solid #E2E8F0; top:100%; margin-top:4px"></div>
                        <div id="appointmentLinkedNote" class="small text-success mt-1 d-none">
                            <i class="bi bi-check2-circle"></i> Linked to OT appointment <strong id="appointmentLinkedNumber"></strong> — continue with OPD fee / registration below.
                        </div>
                    </div>
                </div>

                {{-- MRD Number --}}
                <div class="hms-card-body patient-create-card-body" style="padding-bottom:0">
                    <div style="display:grid;grid-template-columns: repeat(3, 1fr);gap:1.25rem">
                        <div class="form-group">
                            <label class="form-label">MRD No.</label>
                            <input type="text" value="{{ $nextMrd }}" class="form-control hms-input" readonly
                                style="background:#eef2f6">
                        </div>
                    </div>
                </div>

                <div class="hms-card-body patient-create-card-body">
                    <div style="display:grid;grid-template-columns: repeat(3, 1fr);gap:1.25rem">

                        {{-- 1. Appointment Date --}}
                        <div class="form-group">
                            <label class="form-label">Appointment Date</label>
                            <input type="text" name="appointment_date" class="form-control flatpickr hms-input"
                                value="{{ old('appointment_date', now()->format('Y-m-d')) }}" required>
                        </div>

                        {{-- 2. Contact Number --}}
                        <div class="form-group position-relative">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_no" id="contactNo" class="form-control hms-input"
                                data-intl-phone required placeholder="+919876543210">
                            <div id="patientSuggestions" class="position-absolute w-100 bg-white shadow-lg rounded d-none"
                                style="z-index:1050; border:1px solid #E2E8F0; top:100%; margin-top:4px"></div>
                        </div>

                        {{-- 3. WhatsApp No --}}
                        <div class="form-group">
                            <label class="form-label">WhatsApp No</label>
                            <input type="text" name="whatsapp_no" id="whatsappNo" class="form-control hms-input"
                                data-intl-phone placeholder="Same if blank">
                        </div>

                        {{-- 4, 5, 6 Name Fields --}}
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" id="firstName" class="form-control hms-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Surname</label>
                            <input type="text" name="last_name" id="lastName" class="form-control hms-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" id="middleName" class="form-control hms-input">
                        </div>

                        {{-- 7. Case Type --}}
                        <div class="form-group">
                            <label class="form-label">Case Type</label>
                            <select name="case_id" id="caseSelect" class="form-control select2 hms-select" required>
                                <option value="">Select Case</option>
                                @foreach($cases as $c)
                                    <option value="{{ $c->id }}" data-fee="{{ $c->fee ?? 0 }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 8. Case Fee --}}
                        <div class="form-group">
                            <label class="form-label">Case Fee ({{ currency_symbol() }})</label>
                            <input type="number" name="case_fee" id="caseFee" class="form-control hms-input" required
                                readonly>
                        </div>

                        {{-- 12. Doctor Name --}}
                        <div class="form-group">
                            <label class="form-label">Doctor Name</label>
                            <select name="doctor_id" id="doctorSelect" class="form-control select2 hms-select" required>
                                <option value="">Select Doctor</option>
                                @foreach($doctors as $doc)
                                    <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 9, 10, 11 City, District, State --}}
                        <div class="form-group">
                            <label class="form-label">City</label>

                            <div style="display:flex;gap:5px">

                                <select name="location_id" id="locationSelect" class="form-control select2 hms-select"
                                    required>

                                    <option value="">Select City</option>

                                    @foreach($locations as $loc)

                                        <option value="{{ $loc->id }}" data-district="{{ $loc->district?->name }}"
                                            data-state="{{ $loc->state?->name }}">

                                            {{ $loc->name }}

                                        </option>

                                    @endforeach

                                </select>

                                <button type="button" id="btnAddLocation" class="hms-btn hms-btn-outline">
                                    +
                                </button>

                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">District</label>
                            <input type="text" id="district" class="form-control hms-input" readonly
                                placeholder="Auto-filled">
                        </div>
                        <div class="form-group">
                            <label class="form-label">State</label>
                            <input type="text" id="state" class="form-control hms-input" readonly placeholder="Auto-filled">
                        </div>


                        {{-- 13. Age --}}
                        <div class="form-group">
                            <label class="form-label">Age</label>
                            <input type="number" name="age" id="age" class="form-control hms-input" required>
                        </div>

                        {{-- 14. Gender --}}
                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <select name="gender" id="gender" class="form-control hms-select" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        {{-- 15. Occupation --}}
                        <div class="form-group">
                            <label class="form-label">Occupation</label>
                            <input type="text" name="occupation" id="occupation" class="form-control hms-input">
                        </div>

                        {{-- 16. Referred By --}}
                        <div class="form-group">
                            <label class="form-label">Referred By</label>
                            <select name="referrer_id" id="referrerSelect" class="form-control select2 hms-select">
                                <option value="">Select Referrer</option>
                                @foreach($referrers as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- 18. ADD Button --}}
                    <div
                        style="display:flex;gap:0.875rem;margin-top:2.5rem;padding-top:1.75rem;border-top:1px solid #E2E8F0">
                        <button type="submit" class="hms-btn hms-btn-primary">
                            <i class="bi bi-check-circle-fill"></i> Register Patient
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var searchUrl = '{{ route('hospital.patients.search-by-contact', ['slug' => $slug]) }}';
            var csrfToken = '{{ csrf_token() }}';
            var currentHospitalName = '{{ addslashes($currentHospitalName) }}';

            // ── Init plugins ─────────────────────────────────────────────
            if (typeof $.fn.select2 !== 'undefined') {
                $('.select2').select2({ width: '100%' });
            }
            if (typeof flatpickr !== 'undefined') {
                flatpickr('.flatpickr', {
                    dateFormat: 'Y-m-d',
                    defaultDate: 'today',
                    minDate: 'today'
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
                if (locEl && p.location_id && locEl.querySelector('option[value="' + p.location_id + '"]')) {
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

                var msg = p.type === 'shared'
                    ? 'Shared patient selected — please verify city/location.'
                    : 'Patient selected — details filled.';
                showToast(msg);
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
                                var isShared = p.type === 'shared';
                                var badgeLabel = isShared ? (p.hospital_name || 'Partner Hospital') : currentHospitalName;
                                var badgeStyle = isShared
                                    ? 'background:#fff3cd;color:#856404;border:1px solid #ffc107;'
                                    : 'background:#d1fae5;color:#065f46;border:1px solid #10b981;';
                                var badge = '<span style="display:inline-block;font-size:.68rem;font-weight:700;padding:1px 8px;border-radius:20px;margin-left:6px;vertical-align:middle;' + badgeStyle + '">' + badgeLabel + '</span>';
                                html += '<div class="patient-suggestion-card" onclick="fillSelectedPatient(' + idx + ')">' +
                                    '<div class="fw-bold" style="color: var(--prim-blue-600, #1B4F72);">' + displayName + badge + '</div>' +
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

            // ── OT Appointment Search → Prefill (Reception check-in, Phase 2) ──
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

                var contactEl = document.getElementById('contactNo');
                if (contactEl && a.mobile_no) { contactEl.value = a.mobile_no; }

                var genderEl = document.getElementById('gender');
                if (genderEl && a.gender) { genderEl.value = a.gender; }

                var selectAndTrigger = function (el, value) {
                    if (!el || !value || !el.querySelector('option[value="' + value + '"]')) { return; }
                    el.value = value;
                    if (typeof $ !== 'undefined') { $(el).trigger('change'); } else { el.dispatchEvent(new Event('change')); }
                };

                selectAndTrigger(document.getElementById('locationSelect'), a.location_id);
                selectAndTrigger(document.getElementById('doctorSelect'), a.doctor_id);
                selectAndTrigger(document.getElementById('referrerSelect'), a.referrer_id);

                document.getElementById('otAppointmentId').value = a.id;
                appointmentInput.value = a.appointment_number + ' — ' + a.patient_name;

                var linkedNote = document.getElementById('appointmentLinkedNote');
                document.getElementById('appointmentLinkedNumber').textContent = a.appointment_number;
                linkedNote.classList.remove('d-none');

                if (appointmentSuggestions) { appointmentSuggestions.classList.add('d-none'); }
                showToast('Appointment ' + a.appointment_number + ' linked — details filled.');
            };

            if (appointmentInput && appointmentSuggestions) {
                appointmentInput.addEventListener('input', function () {
                    var term = this.value.trim();

                    // Typing again after a previous link means the old link no longer applies.
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
                                        '<div class="fw-bold" style="color: var(--prim-blue-600, #1B4F72);">' + a.appointment_number + ' — ' + a.patient_name + '</div>' +
                                        '<div class="small text-muted">' + a.mobile_no + (a.doctor_name ? ' | Dr. ' + a.doctor_name : '') + ' | ' + (a.appointment_date || '-') + '</div>' +
                                        '</div>';
                                });

                                appointmentSuggestions.innerHTML = html;
                                appointmentSuggestions.classList.remove('d-none');
                            })
                            .catch(function () { appointmentSuggestions.classList.add('d-none'); });
                    }, 300);
                });
            }

            // Prefill from dashboard "Walk-In" button (?ot_appointment_id=…)
            @if(!empty($prefillOtAppointment))
            foundAppointmentsList = [@json($prefillOtAppointment)];
            // Wait for Select2 init so doctor / city / referrer bind correctly
            setTimeout(function () { fillSelectedAppointment(0); }, 150);
            @endif

            document.addEventListener('click', function (e) {
                if (appointmentSuggestions && appointmentInput && e.target !== appointmentInput && !appointmentSuggestions.contains(e.target)) {
                    appointmentSuggestions.classList.add('d-none');
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
            var modalHtml = '\n<div class="modal fade" id="modalAddLocation" tabindex="-1" aria-hidden="true">\n  <div class="modal-dialog modal-sm modal-dialog-centered">\n    <div class="modal-content">\n      <div class="modal-header">\n        <h5 class="modal-title" style="color: #fff;">Add City</h5>\n        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>\n      </div>\n      <div class="modal-body">\n        <div id="addLocErrors" class="text-danger mb-2"></div>\n        <div class="mb-2">\n          <label class="form-label">City</label>\n          <input type="text" id="newCity" class="form-control" placeholder="City name">\n        </div>\n        <div class="mb-2">\n          <label class="form-label">District</label>\n          <input type="text" id="newDistrict" class="form-control" placeholder="District">\n        </div>\n        <div class="mb-2">\n          <label class="form-label">State</label>\n          <input type="text" id="newState" class="form-control" placeholder="State">\n        </div>\n      </div>\n      <div class="modal-footer">\n        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>\n        <button type="button" id="saveLocationBtn" class="btn btn-primary">Add</button>\n      </div>\n    </div>\n  </div>\n</div>\n';

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
            color: var(--pc-primary) !important;
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
            color: var(--pc-primary) !important;
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
                            PATIENT REGISTRATION - ATTRACTIVE DESIGN
                            Color Theme: #1B4F72 (Deep Blue) | #2980B9 (Lighter Blue)
                            No conflicts with existing styles - uses specific selectors
                            ============================================================ */

        /* Main container enhancement */
        .hms-card.patient-registration-card {
            border-radius: 28px !important;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.3s ease;
        }

        .hms-card.patient-registration-card:hover {
            box-shadow: 0 25px 45px -12px rgba(27, 79, 114, 0.25) !important;
        }

        /* Header gradient enhancement */
        .hms-card-header {
            background: linear-gradient(135deg, #1B4F72 0%, #2471A3 50%, #2980B9 100%) !important;
            position: relative;
            overflow: hidden;
        }

        .hms-card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Icon container animation */
        .hms-card-header>div>div:first-child {
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .hms-card-header:hover>div>div:first-child {
            transform: scale(1.05);
            background: rgba(255, 255, 255, 0.25) !important;
        }

        /* Form grid - responsive with better spacing */
        .patient-registration-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        @media (max-width: 992px) {
            .patient-registration-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.25rem;
            }
        }

        @media (max-width: 640px) {
            .patient-registration-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        /* Form group styling */
        .form-group {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #5a6e7c;
            transition: color 0.2s ease;
        }

        .form-group:focus-within .form-label {
            color: #1B4F72;
        }

        /* Input fields styling */
        .hms-input,
        .hms-select,
        select.form-control,
        input.form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px !important;
            background: #ffffff;
            transition: all 0.2s ease;
            color: #1a2a3a;
        }

        .hms-input:hover,
        .hms-select:hover,
        select.form-control:hover,
        input.form-control:hover {
            border-color: #b8c5d0;
        }

        .hms-input:focus,
        .hms-select:focus,
        select.form-control:focus,
        input.form-control:focus {
            outline: none;
            border-color: #1B4F72;
            box-shadow: 0 0 0 4px rgba(27, 79, 114, 0.1);
        }

        /* Readonly fields */
        .hms-input[readonly],
        input.form-control[readonly] {
            background: #f4f7fb !important;
            border-color: #e2e8f0 !important;
            color: #4a6276 !important;
            cursor: default;
        }

        /* Select2 customization */
        .select2-container--default .select2-selection--single {
            height: auto !important;
            padding: 0.5rem 1rem;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 14px !important;
            background: #ffffff !important;
            transition: all 0.2s ease;
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
            right: 12px !important;
        }

        .select2-dropdown {
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
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
            padding: 0.65rem 1rem !important;
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

        /* Button group styling */
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid #eef2f6;
        }

        /* Primary button */
        .hms-btn.hms-btn-primary,
        .hms-card button[type="submit"] {
            background: linear-gradient(135deg, #1B4F72 0%, #2471A3 100%) !important;
            border: none !important;
            padding: 0.875rem 2rem !important;
            font-weight: 700 !important;
            font-size: 0.9rem !important;
            letter-spacing: 0.03em;
            border-radius: 40px !important;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(27, 79, 114, 0.2);
        }

        .hms-btn.hms-btn-primary:hover,
        .hms-card button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(27, 79, 114, 0.25);
            background: linear-gradient(135deg, #15455e 0%, #1e6a9c 100%) !important;
        }

        .hms-btn.hms-btn-primary:active,
        .hms-card button[type="submit"]:active {
            transform: translateY(0);
        }

        /* Outline button */
        .hms-btn.hms-btn-outline,
        a.hms-btn-outline {
            background: transparent !important;
            border: 1.5px solid #cbd5e1 !important;
            color: #4a6276 !important;
            padding: 0.875rem 1.75rem !important;
            font-weight: 600 !important;
            border-radius: 40px !important;
            transition: all 0.2s ease;
        }

        .hms-btn.hms-btn-outline:hover,
        a.hms-btn-outline:hover {
            border-color: #1B4F72 !important;
            color: #1B4F72 !important;
            background: rgba(27, 79, 114, 0.04) !important;
            transform: translateY(-1px);
        }

        /* Add location button */
        #btnAddLocation {
            width: 20px !important;
            height: 35px !important;
            border-radius: 12px !important;
            background: #f0f4f9 !important;
            border: 1.5px solid #e2e8f0 !important;
            color: #1B4F72 !important;
            font-size: 1.2rem;
            font-weight: bold;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        #btnAddLocation:hover {
            background: #1B4F72 !important;
            color: white !important;
            border-color: #1B4F72 !important;
            transform: scale(1.02);
        }

        /* Patient suggestions dropdown */
        #patientSuggestions {
            border-radius: 16px !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.15) !important;
            overflow: hidden;
            z-index: 1060 !important;
        }

        .patient-suggestion-card {
            padding: 0.875rem 1.125rem !important;
            border-bottom: 1px solid #eef2f6 !important;
            transition: all 0.15s ease;
        }

        .patient-suggestion-card:hover {
            background: #f8fafc !important;
            padding-left: 1.375rem !important;
        }

        .patient-suggestion-card .fw-bold {
            color: #1B4F72 !important;
            font-weight: 700 !important;
        }

        /* Toast notification */
        #contactToast {
            border-radius: 60px !important;
            font-weight: 500;
            backdrop-filter: blur(8px);
            background: #1B4F72 !important;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        /* Modal styling */
        .modal-content {
            border-radius: 24px !important;
            border: none !important;
            overflow: hidden;
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
        }

        .modal-header .modal-title {
            font-weight: 700;
            font-size: 1.2rem;
        }

        .modal-footer {
            border-top: 1px solid #eef2f6;
            padding: 1rem 1.5rem;
        }

        .modal-footer .btn-primary {
            background: linear-gradient(135deg, #1B4F72 0%, #2471A3 100%);
            border: none;
            border-radius: 40px;
            padding: 0.5rem 1.5rem;
        }

        .modal-footer .btn-secondary {
            background: #f1f5f9;
            border: none;
            color: #475569;
            border-radius: 40px;
            padding: 0.5rem 1.5rem;
        }

        /* MRD Number special styling */
        input[value*="MRD"] {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Fieldset grouping visual */
        .form-group:has(input[required]) .form-label::after,
        .form-group:has(select[required]) .form-label::after {
            content: '*';
            color: #e74c3c;
            margin-left: 4px;
        }

        /* Optional fields subtle styling */
        .form-group:has(input:not([required])) .form-label {
            opacity: 0.7;
        }

        /* Flatpickr customization */
        .flatpickr-calendar {
            border-radius: 20px !important;
            box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.2) !important;
            border: 1px solid #e2e8f0 !important;
        }

        .flatpickr-day.selected {
            background: #1B4F72 !important;
            border-color: #1B4F72 !important;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .hms-card-body {
                padding: 1.25rem !important;
            }

            .form-actions {
                flex-direction: column-reverse;
                gap: 0.75rem;
            }

            .form-actions button,
            .form-actions a {
                text-align: center;
                justify-content: center;
            }

            .hms-btn.hms-btn-primary,
            button[type="submit"],
            .hms-btn.hms-btn-outline {
                width: 100%;
                text-align: center;
            }
        }

        /* Loading state for buttons */
        .hms-card button[type="submit"]:disabled {
            opacity: 0.7;
            transform: none !important;
            cursor: not-allowed;
        }

        /* Smooth scrolling for the page */
        html {
            scroll-behavior: smooth;
        }

        /* Additional hover effects for better UX */
        select.form-control option {
            padding: 10px;
        }

        /* Focus visible outline for accessibility */
        *:focus-visible {
            outline: 2px solid #1B4F72;
            outline-offset: 2px;
        }
    </style>
@endpush