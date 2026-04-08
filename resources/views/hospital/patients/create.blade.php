@extends('hospital.layouts.app')
@section('title', 'Register Walk-in Patient')
@section('page-header', 'Register Walk-in Patient')

@section('page-actions')
    <a href="{{ route('hospital.patients.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')

{{-- Toast notification --}}
<div id="contactToast" style="display:none;position:fixed;top:1.25rem;right:1.25rem;z-index:9999;
     background:#1B4F72;color:#fff;padding:.75rem 1.25rem;border-radius:.5rem;
     box-shadow:0 4px 12px rgba(0,0,0,.2);font-size:.9rem;max-width:320px">
    <i class="fa-solid fa-circle-check" style="margin-right:.4rem"></i>
    <span id="contactToastMsg"></span>
</div>

<form method="POST" action="{{ route('hospital.patients.store', ['slug' => $slug]) }}">
    @csrf

    {{-- Row 1: Contact, Appointment Date, WhatsApp, Patient Code --}}
    <div class="hms-card" style="margin-bottom:1rem">
        <div class="hms-card-header">
            <h3 class="hms-card-title">
                <i class="fa-solid fa-id-card" style="color:var(--hms-primary)"></i> Visit Details
            </h3>
        </div>
        <div class="hms-card-body">
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem">
                <div class="hms-form-group">
                    <label>Contact No <span class="hms-required">*</span></label>
                    <input type="text" name="contact_no" id="contactNo"
                           value="{{ old('contact_no') }}" maxlength="15"
                           class="hms-input @error('contact_no') is-invalid @enderror"
                           placeholder="e.g. 9876543210" required>
                    @error('contact_no')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Appointment Date <span class="hms-required">*</span></label>
                    <input type="text" name="appointment_date" id="appointmentDate"
                           value="{{ old('appointment_date', now()->format('Y-m-d')) }}"
                           class="hms-input flatpickr @error('appointment_date') is-invalid @enderror" required>
                    @error('appointment_date')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>WhatsApp No</label>
                    <input type="text" name="whatsapp_no" id="whatsappNo"
                           value="{{ old('whatsapp_no') }}" maxlength="15"
                           class="hms-input @error('whatsapp_no') is-invalid @enderror"
                           placeholder="Same if blank">
                    @error('whatsapp_no')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Patient MRD</label>
                    <input type="text" class="hms-input"
                           value="" readonly placeholder="Auto-generated on save"
                           style="background:var(--hms-bg-light,#F8FAFC);color:#94A3B8">
                </div>
            </div>
        </div>
    </div>

    {{-- Row 2: First Name, Middle Name, Surname --}}
    <div class="hms-card" style="margin-bottom:1rem">
        <div class="hms-card-header">
            <h3 class="hms-card-title">
                <i class="fa-solid fa-user" style="color:var(--hms-primary)"></i> Personal Details
            </h3>
        </div>
        <div class="hms-card-body">
            <div class="hms-form-grid-3">
                <div class="hms-form-group">
                    <label>First Name <span class="hms-required">*</span></label>
                    <input type="text" name="first_name" id="firstName"
                           value="{{ old('first_name') }}"
                           class="hms-input @error('first_name') is-invalid @enderror" required>
                    @error('first_name')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" id="middleName"
                           value="{{ old('middle_name') }}" class="hms-input">
                </div>

                <div class="hms-form-group">
                    <label>Surname <span class="hms-required">*</span></label>
                    <input type="text" name="last_name" id="lastName"
                           value="{{ old('last_name') }}"
                           class="hms-input @error('last_name') is-invalid @enderror" required>
                    @error('last_name')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Row 3: Case Type, Case Fee, City, District, State --}}
    <div class="hms-card" style="margin-bottom:1rem">
        <div class="hms-card-header">
            <h3 class="hms-card-title">
                <i class="fa-solid fa-stethoscope" style="color:var(--hms-teal)"></i> Case & Location
            </h3>
        </div>
        <div class="hms-card-body">
            <div style="display:grid;grid-template-columns:2fr 1fr 2fr 2fr 2fr;gap:1rem">
                <div class="hms-form-group">
                    <label>Case Type <span class="hms-required">*</span></label>
                    <select name="case_id" id="caseSelect"
                            class="hms-select select2 @error('case_id') is-invalid @enderror" required>
                        <option value="">Select Case</option>
                        @foreach($cases as $c)
                            <option value="{{ $c->id }}" data-fee="{{ $c->fee ?? 0 }}"
                                    @selected(old('case_id') == $c->id)>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('case_id')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Fee (₹) <span class="hms-required">*</span></label>
                    <input type="number" name="case_fee" id="caseFee"
                           value="{{ old('case_fee', '0') }}" step="0.01" min="0"
                           class="hms-input @error('case_fee') is-invalid @enderror"
                           readonly style="background:var(--hms-bg-light,#F8FAFC)" required>
                    @error('case_fee')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>City <span class="hms-required">*</span></label>
                    <select name="location_id" id="locationSelect"
                            class="hms-select select2 @error('location_id') is-invalid @enderror" required>
                        <option value="">Select City</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}"
                                    data-district="{{ $loc->district }}"
                                    data-state="{{ $loc->state }}"
                                    @selected(old('location_id') == $loc->id)>
                                {{ $loc->city }}
                            </option>
                        @endforeach
                    </select>
                    @error('location_id')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>District</label>
                    <input type="text" id="district" class="hms-input"
                           readonly placeholder="Auto-filled"
                           style="background:var(--hms-bg-light,#F8FAFC);color:#64748B">
                </div>

                <div class="hms-form-group">
                    <label>State</label>
                    <input type="text" id="state" class="hms-input"
                           readonly placeholder="Auto-filled"
                           style="background:var(--hms-bg-light,#F8FAFC);color:#64748B">
                </div>
            </div>
        </div>
    </div>

    {{-- Row 4: Doctor, Age, Gender, Occupation, Referred By --}}
    <div class="hms-card" style="margin-bottom:1rem">
        <div class="hms-card-header">
            <h3 class="hms-card-title">
                <i class="fa-solid fa-user-doctor" style="color:var(--hms-primary)"></i> Clinical & Other
            </h3>
        </div>
        <div class="hms-card-body">
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr 2fr 2fr;gap:1rem">
                <div class="hms-form-group">
                    <label>Doctor <span class="hms-required">*</span></label>
                    <select name="doctor_id"
                            class="hms-select select2 @error('doctor_id') is-invalid @enderror" required>
                        <option value="">Select Doctor</option>
                        @foreach($doctors as $doc)
                            <option value="{{ $doc->id }}" @selected(old('doctor_id') == $doc->id)>
                                {{ $doc->name }}{{ $doc->doctor_type ? ' (' . ucfirst($doc->doctor_type) . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('doctor_id')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Age <span class="hms-required">*</span></label>
                    <input type="number" name="age" id="age"
                           value="{{ old('age') }}" min="0" max="150"
                           class="hms-input @error('age') is-invalid @enderror" required>
                    @error('age')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Gender <span class="hms-required">*</span></label>
                    <select name="gender" id="gender"
                            class="hms-select @error('gender') is-invalid @enderror" required>
                        <option value="">—</option>
                        <option value="male"   @selected(old('gender') === 'male')>Male</option>
                        <option value="female" @selected(old('gender') === 'female')>Female</option>
                        <option value="other"  @selected(old('gender') === 'other')>Other</option>
                    </select>
                    @error('gender')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="hms-form-group">
                    <label>Occupation</label>
                    <input type="text" name="occupation" id="occupation"
                           value="{{ old('occupation') }}" class="hms-input"
                           placeholder="e.g. Farmer, Teacher">
                </div>

                <div class="hms-form-group">
                    <label>Referred By</label>
                    <input type="text" name="referrer_id" value="{{ old('referrer_id') }}"
                           class="hms-input" placeholder="Referrer name (optional)">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:2fr 3fr;gap:1rem;margin-top:1rem">
                <div class="hms-form-group">
                    <label>Time Slot</label>
                    <select name="slot_id" class="hms-select select2">
                        <option value="">No Slot</option>
                        @foreach($slots as $s)
                            <option value="{{ $s->id }}" @selected(old('slot_id') == $s->id)>
                                {{ $s->slot_name ?? $s->label ?? $s->name ?? $s->id }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="hms-form-group" style="display:flex;align-items:flex-end;padding-bottom:.35rem">
                    <label class="hms-checkbox-label">
                        <input type="checkbox" name="is_old_patient" value="1" id="isOldPatient"
                               @checked(old('is_old_patient'))
                               style="width:16px;height:16px;accent-color:var(--hms-primary);cursor:pointer">
                        <span>Old / Returning Patient</span>
                    </label>
                </div>
            </div>

            <p class="hms-form-hint" style="margin-top:.5rem;margin-bottom:0">
                <i class="fa-solid fa-circle-info"></i>
                MRD number will be auto-generated on save.
            </p>
        </div>
    </div>

    <div style="display:flex;gap:.75rem">
        <button type="submit" class="hms-btn hms-btn-primary">
            <i class="fa-solid fa-check"></i> Register Patient
        </button>
        <a href="{{ route('hospital.patients.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">Cancel</a>
    </div>
</form>

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

    // ── Contact → AJAX auto-fill ──────────────────────────────────
    var contactInput = document.getElementById('contactNo');
    if (contactInput) {
        contactInput.addEventListener('blur', function () {
            var contact = this.value.trim();
            if (contact.length < 6) { return; }

            fetch(searchUrl + '?contact=' + encodeURIComponent(contact), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (! data.found) { return; }

                var p = data.patient;
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

                // Gender select
                var genderEl = document.getElementById('gender');
                if (genderEl && p.gender) { genderEl.value = p.gender; }

                // Location select (with Select2 trigger)
                var locEl = document.getElementById('locationSelect');
                if (locEl && p.location_id) {
                    locEl.value = p.location_id;
                    if (typeof $ !== 'undefined') {
                        $(locEl).trigger('change');
                    } else {
                        locEl.dispatchEvent(new Event('change'));
                    }
                }

                // Mark as returning patient
                var oldPatientCb = document.getElementById('isOldPatient');
                if (oldPatientCb) { oldPatientCb.checked = true; }

                showToast('Existing patient found — details auto-filled.');
            })
            .catch(function () { /* silently fail */ });
        });
    }

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
