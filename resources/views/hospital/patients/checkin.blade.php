@extends('hospital.layouts.app')
@section('title', 'Patient Check-In')
@section('page-header', '')

@section('page-actions')
    <a href="{{ route('hospital.patients.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="bi bi-arrow-left"></i> Back
    </a>
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
            <div style="margin-bottom:2.5rem">
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1.5rem">
                    <div style="width:4px;height:24px;background:#1B4F72;border-radius:2px"></div>
                    <h5 style="margin:0;font-weight:700;color:#1B4F72;font-size:1.1rem">Personal Details</h5>
                </div>

                {{-- Row 0: MRD (readonly) + Contact + WhatsApp --}}
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;margin-bottom:1.25rem">
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:.9rem">MRD No.</label>
                        <input type="text" value="{{ $patient->patient_code }}" readonly
                               class="form-control"
                               style="background:linear-gradient(135deg,#ebf5fbeb,#D6EAF8);font-weight:700;color:#1B4F72;border:1px solid #D6EAF8;border-radius:8px;padding:.75rem 1rem">
                    </div>
                    <div class="form-group position-relative">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:.9rem">
                            Contact No <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <input type="text" name="contact_no" id="contactNo"
                               value="{{ old('contact_no', $patient->contact_no) }}" maxlength="15"
                               class="form-control @error('contact_no') is-invalid @enderror"
                               placeholder="e.g. 9876543210" required
                               style="border:1px solid #E2E8F0;border-radius:8px;padding:.75rem 1rem">
                        @error('contact_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:.9rem">WhatsApp No</label>
                        <input type="text" name="whatsapp_no"
                               value="{{ old('whatsapp_no', $patient->whatsapp_no) }}" maxlength="15"
                               class="form-control @error('whatsapp_no') is-invalid @enderror"
                               placeholder="Same if blank"
                               style="border:1px solid #E2E8F0;border-radius:8px;padding:.75rem 1rem">
                        @error('whatsapp_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Row 1: First Name + Surname + Middle Name --}}
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem">
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:.9rem">
                            First Name <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <input type="text" name="first_name"
                               value="{{ old('first_name', $patient->first_name) }}"
                               class="form-control @error('first_name') is-invalid @enderror" required
                               placeholder="e.g. John"
                               style="border:1px solid #E2E8F0;border-radius:8px;padding:.75rem 1rem">
                        @error('first_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:.9rem">
                            Surname <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <input type="text" name="last_name"
                               value="{{ old('last_name', $patient->last_name) }}"
                               class="form-control @error('last_name') is-invalid @enderror" required
                               placeholder="e.g. Patel"
                               style="border:1px solid #E2E8F0;border-radius:8px;padding:.75rem 1rem">
                        @error('last_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:.9rem">Middle Name</label>
                        <input type="text" name="middle_name"
                               value="{{ old('middle_name', $patient->middle_name) }}"
                               placeholder="e.g. Kumar"
                               class="form-control"
                               style="border:1px solid #E2E8F0;border-radius:8px;padding:.75rem 1rem">
                    </div>
                </div>

                {{-- Row 2: Age + Gender + Occupation --}}
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;margin-top:1.25rem">
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:.9rem">
                            Age <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <input type="number" name="age"
                               value="{{ old('age', $patient->age) }}" min="0" max="150"
                               class="form-control @error('age') is-invalid @enderror" required
                               style="border:1px solid #E2E8F0;border-radius:8px;padding:.75rem 1rem">
                        @error('age')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:.9rem">
                            Gender <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <select name="gender"
                                class="form-control @error('gender') is-invalid @enderror" required
                                style="border:1px solid #E2E8F0;border-radius:8px;padding:.75rem 1rem">
                            <option value="">Select Gender</option>
                            <option value="male"   @selected(old('gender', $patient->gender) === 'male')>Male</option>
                            <option value="female" @selected(old('gender', $patient->gender) === 'female')>Female</option>
                            <option value="other"  @selected(old('gender', $patient->gender) === 'other')>Other</option>
                        </select>
                        @error('gender')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:.9rem">Occupation</label>
                        <input type="text" name="occupation"
                               value="{{ old('occupation', $patient->occupation) }}"
                               class="form-control"
                               placeholder="e.g. Farmer, Teacher"
                               style="border:1px solid #E2E8F0;border-radius:8px;padding:.75rem 1rem">
                    </div>
                </div>
            </div>

            {{-- ── Section 2: Location & Contact ── --}}
            <div style="margin-bottom:2.5rem">
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1.5rem">
                    <div style="width:4px;height:24px;background:#2980B9;border-radius:2px"></div>
                    <h5 style="margin:0;font-weight:700;color:#2980B9;font-size:1.1rem">Location</h5>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:.9rem">
                            City <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <div style="display:flex;gap:.5rem;align-items:center">
                            <select name="location_id" id="locationSelect"
                                    class="form-select select2 @error('location_id') is-invalid @enderror" required
                                    style="border:1px solid #E2E8F0;border-radius:8px;padding:.75rem 1rem">
                                <option value="">Select City</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}"
                                            data-district="{{ $loc->district }}"
                                            data-state="{{ $loc->state }}"
                                            @selected(old('location_id', $patient->location_id) == $loc->id)>
                                        {{ $loc->city ?: "Location #{$loc->id}" }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('location_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:.9rem">District</label>
                        <input type="text" id="district" class="form-control" readonly placeholder="Auto-filled"
                               value="{{ $patient->location?->district }}"
                               style="background:linear-gradient(135deg,#ebf5fbeb,#D6EAF8);border:1px solid #D6EAF8;border-radius:8px;padding:.75rem 1rem">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:.9rem">State</label>
                        <input type="text" id="state" class="form-control" readonly placeholder="Auto-filled"
                               value="{{ $patient->location?->state }}"
                               style="background:linear-gradient(135deg,#ebf5fbeb,#D6EAF8);border:1px solid #D6EAF8;border-radius:8px;padding:.75rem 1rem">
                    </div>
                </div>
            </div>

            {{-- ── Section 3: Appointment & Case ── --}}
            <div style="margin-bottom:2.5rem">
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1.5rem">
                    <div style="width:4px;height:24px;background:#27AE60;border-radius:2px"></div>
                    <h5 style="margin:0;font-weight:700;color:#27AE60;font-size:1.1rem">Appointment &amp; Case</h5>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem">
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:.9rem">
                            Appointment Date <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <input type="text" name="appointment_date" id="appointmentDate"
                               value="{{ old('appointment_date', $patient->appointment_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                               class="form-control flatpickr @error('appointment_date') is-invalid @enderror" required
                               style="border:1px solid #E2E8F0;border-radius:8px;padding:.75rem 1rem">
                        @error('appointment_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:.9rem">
                            Case Type <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <select name="case_id" id="caseSelect"
                                class="form-control select2 @error('case_id') is-invalid @enderror" required
                                style="border:1px solid #E2E8F0;border-radius:8px;padding:.75rem 1rem">
                            <option value="">Select Case</option>
                            @foreach($cases as $c)
                                <option value="{{ $c->id }}" data-fee="{{ $c->fee ?? 0 }}"
                                        @selected(old('case_id', $patient->case_id) == $c->id)>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('case_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:.9rem">
                            Case Fee (₹) <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <div style="display:flex;align-items:center;background:linear-gradient(135deg,#D5F5E3,#E8F8F5);border:1px solid #A9DFBF;border-radius:8px;padding:0 .75rem">
                            <span style="color:#27AE60;font-weight:700;font-size:1.1rem">₹</span>
                            <input type="number" name="case_fee" id="caseFee"
                                   value="{{ old('case_fee', $patient->case_fee ?? '0') }}" step="0.01" min="0"
                                   class="form-control border-0 @error('case_fee') is-invalid @enderror" required
                                   style="background:transparent;padding:.75rem .5rem;font-weight:600;color:#27AE60">
                        </div>
                        @error('case_fee')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-top:1.25rem">
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:.9rem">
                            Doctor <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <select name="doctor_id"
                                class="form-control select2 @error('doctor_id') is-invalid @enderror" required
                                style="border:1px solid #E2E8F0;border-radius:8px;padding:.75rem 1rem">
                            <option value="">Select Doctor</option>
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->id }}"
                                        @selected(old('doctor_id', $patient->doctor_id) == $doc->id)>
                                    {{ $doc->name }}{{ $doc->doctor_type ? ' ('.ucfirst($doc->doctor_type).')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('doctor_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:.9rem">Referred By</label>
                        <select name="referrer_id"
                                class="form-control select2 @error('referrer_id') is-invalid @enderror"
                                style="border:1px solid #E2E8F0;border-radius:8px;padding:.75rem 1rem">
                            <option value="">Select Referrer</option>
                            @foreach($referrers as $r)
                                <option value="{{ $r->id }}"
                                        @selected(old('referrer_id', $patient->referrer_id) == $r->id)>
                                    {{ $r->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-top:1.25rem">
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:.9rem">Time Slot</label>
                        <select name="slot_id" class="form-control select2"
                                style="border:1px solid #E2E8F0;border-radius:8px;padding:.75rem 1rem">
                            <option value="">No Slot</option>
                            @foreach($slots as $s)
                                <option value="{{ $s->id }}"
                                        @selected(old('slot_id', $patient->slot_id) == $s->id)>
                                    {{ $s->slot_name ?? $s->label ?? $s->name ?? $s->id }}
                                    @if(!empty($s->start_time) && !empty($s->end_time))
                                        ({{ \Carbon\Carbon::parse($s->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($s->end_time)->format('h:i A') }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div style="display:flex;gap:.875rem;margin-top:2.5rem;padding-top:1.75rem;border-top:1px solid #E2E8F0">
                <button type="submit"
                        class="hms-btn"
                        style="background:linear-gradient(135deg,#27AE60,#229954);color:#fff;border:none;padding:.875rem 2rem;border-radius:8px;font-weight:600;display:flex;align-items:center;gap:.5rem;cursor:pointer;box-shadow:0 2px 8px rgba(39,174,96,.3)">
                    <i class="bi bi-check-circle-fill"></i> Check In &amp; Print Bill
                </button>
                <a href="{{ route('hospital.patients.index', ['slug' => $slug]) }}"
                   class="hms-btn"
                   style="background:#6C757D;color:#fff;border:none;padding:.75rem 1.5rem;border-radius:8px;font-weight:600;display:flex;align-items:center;gap:.5rem">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
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
@endsection
