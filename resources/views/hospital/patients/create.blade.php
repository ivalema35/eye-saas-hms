@extends('hospital.layouts.app')
@section('title', 'Register Walk-in Patient')
@section('page-header', '')

@section('page-actions')
@endsection

@section('content')

{{-- Toast notification --}}
<div id="contactToast" style="display:none;position:fixed;top:1.25rem;right:1.25rem;z-index:9999;
     background:#1B4F72;color:#fff;padding:.75rem 1.25rem;border-radius:.5rem;
     box-shadow:0 4px 12px rgba(0,0,0,.2);font-size:.9rem;max-width:320px">
    <i class="fa-solid fa-circle-check" style="margin-right:.4rem"></i>
    <span id="contactToastMsg"></span>
</div>

<div class="hms-card border-0 shadow-lg" style="border-radius:16px">
    <div class="hms-card-header" style="background:linear-gradient(135deg, #1B4F72 0%, #2980B9 100%);padding:1.75rem;border-radius:16px 16px 0 0">
        <div style="display:flex;align-items:center;gap:1rem;color:#fff">
            <div style="width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem">
                <i class="bi bi-plus-circle-fill"></i>
            </div>
            <div>
                <h4 style="margin:0;font-weight:700;font-size:1.25rem;color:#fff">Register New Patient</h4>
                <p style="margin:0.25rem 0 0;font-size:0.9rem;opacity:0.9">Capture patient information and appointment details</p>
            </div>
        </div>
    </div>
    <div class="hms-card-body" style="padding:2rem">
        <form method="POST" action="{{ route('hospital.patients.store', ['slug' => $slug]) }}" class="patient-create-form">
            @csrf

            {{-- Section 1: Personal Information --}}
            <div style="margin-bottom:2.5rem">
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem">
                    <div style="width:4px;height:24px;background:#1B4F72;border-radius:2px"></div>
                    <h5 style="margin:0;font-weight:700;color:#1B4F72;font-size:1.1rem">Personal Details</h5>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem">
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">
                            First Name <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <input type="text" name="first_name" id="firstName"
                               value="{{ old('first_name') }}"
                               class="form-control @error('first_name') is-invalid @enderror" required
                               placeholder="e.g. John"
                               style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem;transition:all 0.3s">
                        @error('first_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">
                            Surname <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <input type="text" name="last_name" id="lastName"
                               value="{{ old('last_name') }}"
                               class="form-control @error('last_name') is-invalid @enderror" required
                               placeholder="e.g. Patel"
                               style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                        @error('last_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">Middle Name</label>
                        <input type="text" name="middle_name" id="middleName"
                               value="{{ old('middle_name') }}" 
                               placeholder="e.g. Kumar"
                               class="form-control" style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                    </div>


                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;margin-top:1.25rem">
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">
                            Age <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <input type="number" name="age" id="age"
                               value="{{ old('age') }}" min="0" max="150"
                               class="form-control @error('age') is-invalid @enderror" required
                               style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                        @error('age')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">
                            Gender <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <select name="gender" id="gender"
                                class="form-control @error('gender') is-invalid @enderror" required
                                style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                            <option value="">Select Gender</option>
                            <option value="male" @selected(old('gender') === 'male')>Male</option>
                            <option value="female" @selected(old('gender') === 'female')>Female</option>
                            <option value="other" @selected(old('gender') === 'other')>Other</option>
                        </select>
                        @error('gender')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">Occupation</label>
                        <input type="text" name="occupation" id="occupation"
                               value="{{ old('occupation') }}" 
                               class="form-control" style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem"
                               placeholder="e.g. Farmer, Teacher">
                    </div>
                </div>
            </div>

            {{-- Section 2: Location & Contact --}}
            <div style="margin-bottom:2.5rem">
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem">
                    <div style="width:4px;height:24px;background:#2980B9;border-radius:2px"></div>
                    <h5 style="margin:0;font-weight:700;color:#2980B9;font-size:1.1rem">Location & Contact</h5>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">
                            City <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <div style="display:flex;gap:.5rem;align-items:center">
                            <select name="location_id" id="locationSelect"
                                    class="form-select select2 @error('location_id') is-invalid @enderror" required
                                    style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                                <option value="">Select City</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}"
                                            data-district="{{ $loc->district }}"
                                            data-state="{{ $loc->state }}"
                                            @selected(old('location_id') == $loc->id)>
                                        {{ $loc->city ?: "Location #{$loc->id}" }}
                                    </option>
                                @endforeach
                            </select>

                            <button type="button" id="btnAddLocation" class="hms-btn hms-btn-outline" aria-label="Add city"
                                    style="width:30px;height:30px;padding:0;display:flex;align-items:center;justify-content:center;background:#fff;color:#1B4F72;margin-left:8px;flex:0 0 auto">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M12 5v14" stroke="#1B4F72" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M5 12h14" stroke="#1B4F72" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>
                        @error('location_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">District</label>
                        <input type="text" id="district" class="form-control" readonly placeholder="Auto-filled"
                               style="background:linear-gradient(135deg, #ebf5fbeb 0%, #D6EAF8 100%);border:1px solid #D6EAF8;border-radius:8px;padding:0.75rem 1rem">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">State</label>
                        <input type="text" id="state" class="form-control" readonly placeholder="Auto-filled"
                               style="background:linear-gradient(135deg, #ebf5fbeb 0%, #D6EAF8 100%);border:1px solid #D6EAF8;border-radius:8px;padding:0.75rem 1rem">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-top:1.25rem">
                    <div class="form-group position-relative">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">
                            Contact No <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <input type="text" name="contact_no" id="contactNo"
                               value="{{ old('contact_no') }}" maxlength="15"
                               class="form-control @error('contact_no') is-invalid @enderror"
                               placeholder="e.g. 9876543210" autocomplete="off" required
                               style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                        <div id="patientSuggestions" class="position-absolute w-100 bg-white shadow-lg rounded d-none"
                             style="z-index: 1050; max-height: 250px; overflow-y: auto; border: 1px solid var(--color-border-default, #E2E8F0); top: 100%; left: 0; margin-top: 4px;"></div>
                        @error('contact_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">WhatsApp No</label>
                        <input type="text" name="whatsapp_no" id="whatsappNo"
                               value="{{ old('whatsapp_no') }}" maxlength="15"
                               class="form-control @error('whatsapp_no') is-invalid @enderror"
                               placeholder="Same if blank" style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                        @error('whatsapp_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- Section 3: Appointment & Case Details --}}
            <div style="margin-bottom:2.5rem">
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem">
                    <div style="width:4px;height:24px;background:#27AE60;border-radius:2px"></div>
                    <h5 style="margin:0;font-weight:700;color:#27AE60;font-size:1.1rem">Appointment & Case</h5>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem">
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">
                            Appointment Date <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <input type="text" name="appointment_date" id="appointmentDate"
                               value="{{ old('appointment_date', now()->format('Y-m-d')) }}"
                               class="form-control flatpickr @error('appointment_date') is-invalid @enderror" required
                               style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                        @error('appointment_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">
                            Case Type <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <select name="case_id" id="caseSelect"
                                class="form-control select2 @error('case_id') is-invalid @enderror" required
                                style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                            <option value="">Select Case</option>
                            @foreach($cases as $c)
                                <option value="{{ $c->id }}" data-fee="{{ $c->fee ?? 0 }}"
                                        @selected(old('case_id') == $c->id)>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('case_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">
                            Case Fee (₹) <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <div style="display:flex;align-items:center;background:linear-gradient(135deg, #D5F5E3 0%, #E8F8F5 100%);border:1px solid #A9DFBF;border-radius:8px;padding:0 0.75rem;position:relative">
                            <span style="color:#27AE60;font-weight:700;font-size:1.1rem">₹</span>
                            <input type="number" name="case_fee" id="caseFee"
                                   value="{{ old('case_fee', '0') }}" step="0.01" min="0"
                                   class="form-control border-0 @error('case_fee') is-invalid @enderror" required
                                   style="background:transparent;padding:0.75rem 0.75rem 0.75rem 0.5rem;font-weight:600;color:#27AE60">
                        </div>
                        @error('case_fee')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-top:1.25rem">
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">
                            Doctor <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <select name="doctor_id"
                                class="form-control select2 @error('doctor_id') is-invalid @enderror" required
                                style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                            <option value="">Select Doctor</option>
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->id }}" @selected(old('doctor_id') == $doc->id)>
                                    {{ $doc->name }}{{ $doc->doctor_type ? ' (' . ucfirst($doc->doctor_type) . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('doctor_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">Referred By</label>
                        <select name="referrer_id" class="form-control select2 @error('referrer_id') is-invalid @enderror"
                                style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                            <option value="">Select Referrer</option>
                            @if(isset($referrers) && $referrers->isNotEmpty())
                                @foreach($referrers as $r)
                                    <option value="{{ $r->id }}" @selected(old('referrer_id') == $r->id)>{{ $r->name }}</option>
                                @endforeach
                            @else
                                @foreach(config('masters.basic_masters.referrers', []) as $rKey => $r)
                                    @php
                                        $value = is_array($r) ? ($r['id'] ?? $r['value'] ?? $rKey) : $rKey;
                                        $label = is_array($r) ? ($r['name'] ?? $r['label'] ?? ($r['title'] ?? $value)) : $r;
                                    @endphp
                                    <option value="{{ $value }}" @selected(old('referrer_id') == $value)>{{ $label }}</option>
                                @endforeach
                            @endif
                        </select>
                        @error('referrer_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-top:1.25rem">
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">Time Slot</label>
                        <select name="slot_id" class="form-control select2"
                                style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                            <option value="">No Slot</option>
                            @foreach($slots as $s)
                                <option value="{{ $s->id }}" @selected(old('slot_id') == $s->id)>
                                    {{ $s->slot_name ?? $s->label ?? $s->name ?? $s->id }}
                                    @if(!empty($s->start_time) && !empty($s->end_time))
                                        ({{ \Illuminate\Support\Carbon::parse($s->start_time)->format('h:i A') }} - {{ \Illuminate\Support\Carbon::parse($s->end_time)->format('h:i A') }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- <div class="form-group" style="display:flex;align-items:flex-end;gap:0.75rem;padding:1rem;background:#F0F4F8;border-radius:8px;border:1px dashed #2980B9">
                        <input type="checkbox" name="is_old_patient" value="1" id="isOldPatient"
                               @checked(old('is_old_patient'))
                               style="width:20px;height:20px;accent-color:#1B4F72;cursor:pointer;flex-shrink:0">
                        <label for="isOldPatient" class="form-label fw-600" style="margin:0;cursor:pointer;color:#2C3E50;font-size:0.9rem">Old / Returning Patient</label>
                    </div> -->
                </div>
            </div>

            {{-- Action Buttons --}}
            <div style="display:flex;gap:0.875rem;margin-top:2.5rem;padding-top:1.75rem;border-top:1px solid #E2E8F0">
                <button type="submit" class="hms-btn" style="background:linear-gradient(135deg, #27AE60 0%, #229954 100%);color:#fff;border:none;padding:0.875rem 2rem;border-radius:8px;font-weight:600;display:flex;align-items:center;gap:0.5rem;transition:all 0.3s;cursor:pointer;box-shadow:0 2px 8px rgba(39,174,96,0.3)">
                    <i class="bi bi-check-circle-fill"></i> Register Patient
                </button>
                <a href="{{ route('hospital.patients.index', ['slug' => $slug]) }}" class="hms-btn" style="background:#1B4F72;color:#fff;border:none;padding:0.75rem 2rem;border-radius:8px;font-weight:600;display:flex;align-items:center;gap:0.5rem;transition:all 0.3s;cursor:pointer;box-shadow:0 2px 8px rgba(27,79,114,0.3)">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
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

    // ── Init plugins ─────────────────────────────────────────────
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({ width: '100%' });
    }
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.flatpickr', { dateFormat: 'Y-m-d', defaultDate: 'today' });
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

    // ── Contact No: Only Numbers Allowed ──────────────────────────
    if (contactInput) {
        contactInput.addEventListener('keypress', function (e) {
            var char = String.fromCharCode(e.which);
            if (!/[0-9]/.test(char)) {
                e.preventDefault();
            }
        });

        contactInput.addEventListener('paste', function (e) {
            e.preventDefault();
            var pastedText = (e.clipboardData || window.clipboardData).getData('text');
            if (/^\d+$/.test(pastedText)) {
                this.value = pastedText;
            }
        });
    }

    // ── WhatsApp No: Only Numbers Allowed ──────────────────────────
    var whatsappInput = document.getElementById('whatsappNo');
    if (whatsappInput) {
        whatsappInput.addEventListener('keypress', function (e) {
            var char = String.fromCharCode(e.which);
            if (!/[0-9]/.test(char)) {
                e.preventDefault();
            }
        });

        whatsappInput.addEventListener('paste', function (e) {
            e.preventDefault();
            var pastedText = (e.clipboardData || window.clipboardData).getData('text');
            if (/^\d+$/.test(pastedText)) {
                this.value = pastedText;
            }
        });
    }

    window.fillSelectedPatient = function (index) {
        if (index < 0 || index >= foundPatientsList.length) { return; }

        var p = foundPatientsList[index];
        var setVal = function (id, val) {
            var el = document.getElementById(id);
            if (el && val !== null && val !== undefined) { el.value = val; }
        };

        setVal('firstName',  p.first_name);
        setVal('middleName', p.middle_name);
        setVal('lastName',   p.last_name);
        setVal('age',        p.age);
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
                if (! data.found || ! data.patients || data.patients.length === 0) {
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
        if (patientSuggestions && contactInput && e.target !== contactInput && ! patientSuggestions.contains(e.target)) {
            patientSuggestions.classList.add('d-none');
        }
    });

    // ── Case Type → Fee auto-fill ─────────────────────────────────
    var caseSelect = document.getElementById('caseSelect');
    var caseFeeEl  = document.getElementById('caseFee');
    if (caseSelect && caseFeeEl) {
        $(caseSelect).on('change', function () {
            var opt = this.options[this.selectedIndex];
            caseFeeEl.value = opt ? (opt.dataset.fee || 0) : 0;
        });
    }

    // ── Location → District / State auto-fill ────────────────────
    var locationEl  = document.getElementById('locationSelect');
    var districtEl  = document.getElementById('district');
    var stateEl     = document.getElementById('state');

    function syncLocation() {
        if (! locationEl || ! districtEl || ! stateEl) { return; }
        var opt = locationEl.options[locationEl.selectedIndex];
        if (locationEl.value && opt) {
            districtEl.value = opt.getAttribute('data-district') || '';
            stateEl.value    = opt.getAttribute('data-state')    || '';
        } else {
            districtEl.value = '';
            stateEl.value    = '';
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
    if (! addBtn) return;

    // Create modal HTML and append to body
    var modalHtml = '\n<div class="modal fade" id="modalAddLocation" tabindex="-1" aria-hidden="true">\n  <div class="modal-dialog modal-sm modal-dialog-centered">\n    <div class="modal-content">\n      <div class="modal-header">\n        <h5 class="modal-title">Add City</h5>\n        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>\n      </div>\n      <div class="modal-body">\n        <div id="addLocErrors" class="text-danger mb-2"></div>\n        <div class="mb-2">\n          <label class="form-label">City</label>\n          <input type="text" id="newCity" class="form-control" placeholder="City name">\n        </div>\n        <div class="mb-2">\n          <label class="form-label">District</label>\n          <input type="text" id="newDistrict" class="form-control" placeholder="District">\n        </div>\n        <div class="mb-2">\n          <label class="form-label">State</label>\n          <input type="text" id="newState" class="form-control" placeholder="State">\n        </div>\n      </div>\n      <div class="modal-footer">\n        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>\n        <button type="button" id="saveLocationBtn" class="btn btn-primary">Add</button>\n      </div>\n    </div>\n  </div>\n</div>\n';

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
            if (! city) { addLocErrors.textContent = 'City is required.'; return; }

            var url = '{{ route("hospital.masters.basic.ajax.store", ["slug" => $slug, "type" => "locations"]) }}';
            var token = '{{ csrf_token() }}';

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ city: city, district: district, state: state })
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (! data || ! data.success) {
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
                if (toast) { document.getElementById('contactToastMsg').textContent = 'City added.'; toast.style.background = '#1B4F72'; toast.style.display = 'block'; setTimeout(function(){ toast.style.display='none'; },2500); }
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
        background: rgba(255,255,255,.92) !important;
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
        background: linear-gradient(135deg, rgba(235,245,251,.96), rgba(255,255,255,.94));
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
        background: rgba(255,255,255,.92);
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
        background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(246,250,253,.96));
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
        background: rgba(255,255,255,.94) !important;
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
        background: rgba(235,245,251,.58) !important;
        color: rgba(27, 79, 114, .78) !important;
    }

    .patient-create-page .hms-checkbox-label {
        display: inline-flex;
        align-items: center;
        gap: .7rem;
        padding: .9rem 1rem;
        border: 1px solid var(--pc-border);
        border-radius: 16px;
        background: rgba(255,255,255,.94);
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
        .patient-create-card-body > div[style*="repeat(4,1fr)"],
        .patient-create-card-body > div[style*="2fr 1fr 2fr 2fr 2fr"],
        .patient-create-card-body > div[style*="2fr 1fr 1fr 2fr 2fr"] {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }

    @media (max-width: 640px) {
        .patient-create-card-body > div[style*="repeat(4,1fr)"],
        .patient-create-card-body > div[style*="2fr 1fr 2fr 2fr 2fr"],
        .patient-create-card-body > div[style*="2fr 1fr 1fr 2fr 2fr"],
        .patient-create-card-body > div[style*="2fr 3fr"],
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
@endpush
