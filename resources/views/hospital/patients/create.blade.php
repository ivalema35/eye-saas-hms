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

    <div class="hms-card-body patient-create-card-body">
        <div style="display:grid;grid-template-columns: repeat(3, 1fr);gap:1.25rem">
            

            {{-- MRD Number --}}
            <div class="form-group">
                <label class="form-label">MRD No.</label>
                <input type="text" value="{{ $nextMrd }}" class="form-control hms-input" readonly style="background:#eef2f6">
            </div>
        
            {{-- 1. Appointment Date --}}
            <div class="form-group">
                <label class="form-label">Appointment Date *</label>
                <input type="text" name="appointment_date" class="form-control flatpickr hms-input" value="{{ old('appointment_date', now()->format('Y-m-d')) }}" required>
            </div>

            {{-- 2. Contact Number --}}
            <div class="form-group position-relative">
                <label class="form-label">Contact Number *</label>
                <input type="text" name="contact_no" id="contactNo" class="form-control hms-input" maxlength="10" required placeholder="10-digit number">
                <div id="patientSuggestions" class="position-absolute w-100 bg-white shadow-lg rounded d-none" style="z-index:1050; border:1px solid #E2E8F0; top:100%; margin-top:4px"></div>
            </div>

            {{-- 3. WhatsApp No --}}
            <div class="form-group">
                <label class="form-label">WhatsApp No</label>
                <input type="text" name="whatsapp_no" id="whatsappNo" class="form-control hms-input" maxlength="10" placeholder="Same if blank">
            </div>

            {{-- 4, 5, 6 Name Fields --}}
            <div class="form-group">
                <label class="form-label">First Name *</label>
                <input type="text" name="first_name" id="firstName" class="form-control hms-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Surname *</label>
                <input type="text" name="last_name" id="lastName" class="form-control hms-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Middle Name</label>
                <input type="text" name="middle_name" id="middleName" class="form-control hms-input">
            </div>

            {{-- 7. Case Type --}}
            <div class="form-group">
                <label class="form-label">Case Type *</label>
                <select name="case_id" id="caseSelect" class="form-control select2 hms-select" required>
                    <option value="">Select Case</option>
                    @foreach($cases as $c)
                        <option value="{{ $c->id }}" data-fee="{{ $c->fee ?? 0 }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 8. Case Fee --}}
            <div class="form-group">
                <label class="form-label">Case Fee (₹) *</label>
                <input type="number" name="case_fee" id="caseFee" class="form-control hms-input" required readonly>
            </div>

            {{-- 9, 10, 11 City, District, State --}}
            <div class="form-group">
                <label class="form-label">City *</label>
                <div style="display:flex;gap:5px">
                    <select name="location_id" id="locationSelect" class="form-control select2 hms-select" required>
                        <option value="">Select City</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" data-district="{{ $loc->district }}" data-state="{{ $loc->state }}">{{ $loc->city }}</option>
                        @endforeach
                    </select>
                    <button type="button" id="btnAddLocation" class="hms-btn hms-btn-outline" style="width:30px;height:30px">+</button>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">District</label>
                <input type="text" id="district" class="form-control hms-input" readonly placeholder="Auto-filled">
            </div>
            <div class="form-group">
                <label class="form-label">State</label>
                <input type="text" id="state" class="form-control hms-input" readonly placeholder="Auto-filled">
            </div>

            {{-- 12. Doctor Name --}}
            <div class="form-group">
                <label class="form-label">Doctor Name *</label>
                <select name="doctor_id" class="form-control select2 hms-select" required>
                    <option value="">Select Doctor</option>
                    @foreach($doctors as $doc)
                        <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 13. Age --}}
            <div class="form-group">
                <label class="form-label">Age *</label>
                <input type="number" name="age" id="age" class="form-control hms-input" required>
            </div>

            {{-- 14. Gender --}}
            <div class="form-group">
                <label class="form-label">Gender *</label>
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
                <select name="referrer_id" class="form-control select2 hms-select">
                    <option value="">Select Referrer</option>
                    @foreach($referrers as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- 18. ADD Button --}}
        <div style="display:flex;gap:0.875rem;margin-top:2.5rem;padding-top:1.75rem;border-top:1px solid #E2E8F0">
            <button type="submit" class="hms-btn hms-btn-primary">
                <i class="bi bi-check-circle-fill"></i> Register Patient
            </button>
            <a href="{{ route('hospital.patients.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
                <i class="bi bi-arrow-left"></i> Back
            </a>
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
