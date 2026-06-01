@extends('hospital.layouts.app')
@section('title', 'Edit Patient')
@section('page-header', 'Edit Patient — ' . $patient->patient_code)

@section('page-actions')
    <a href="{{ route('hospital.patients.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')

@if($patient->primary_done_at)
    <div class="alert alert-warning alert-dismissible fade show border-0" style="background:#FDEBD0;color:#784212;border-left:4px solid #E67E22" role="alert">
        <i class="bi bi-exclamation-circle-fill me-2"></i>
        <strong>Examination Completed:</strong> Primary examination has already been done for this patient. Changes will not affect examination data.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="hms-card border-0 shadow-lg" style="border-radius:16px">
    <div class="hms-card-header" style="background:linear-gradient(135deg, #1B4F72 0%, #2980B9 100%);padding:1.75rem;border-radius:16px 16px 0 0">
        <div style="display:flex;align-items:center;gap:1rem;color:#fff">
            <div style="width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem">
                <i class="bi bi-person-fill"></i>
            </div>
            <div>
                <h4 style="margin:0;font-weight:700;font-size:1.25rem;color:#fff">Patient Information</h4>
                <p style="margin:0.25rem 0 0;font-size:0.9rem;opacity:0.9">Update patient details and appointment information</p>
            </div>
        </div>
    </div>
    <div class="hms-card-body" style="padding:2rem">
        <form method="POST" action="{{ route('hospital.patients.update', ['slug' => $slug, 'patient' => $patient->id]) }}">
            @csrf @method('PUT')

            {{-- Section 1: Personal Information --}}
            <div style="margin-bottom:2.5rem">
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem">
                    <div style="width:4px;height:24px;background:#1B4F72;border-radius:2px"></div>
                    <h5 style="margin:0;font-weight:700;color:#1B4F72;font-size:1.1rem">Personal Details</h5>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem">
                    {{-- MRD (Read-only) --}}
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">MRD Number</label>
                        <input type="text" value="{{ $patient->patient_code }}" class="form-control" readonly
                               style="background:linear-gradient(135deg, #ebf5fbeb 0%, #D6EAF8 100%);font-weight:700;color:#1B4F72;border:1px solid #D6EAF8;border-radius:8px;padding:0.75rem 1rem">
                    </div>

                    {{-- First Name --}}
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">
                            First Name <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <input type="text" name="first_name" value="{{ old('first_name', $patient->first_name) }}"
                               class="form-control @error('first_name') is-invalid @enderror" required
                               style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem;transition:all 0.3s">
                        @error('first_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    {{-- Middle Name --}}
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">Middle Name</label>
                        <input type="text" name="middle_name" value="{{ old('middle_name', $patient->middle_name) }}" 
                               class="form-control" style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;margin-top:1.25rem">
                    {{-- Last Name --}}
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">
                            Last Name <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <input type="text" name="last_name" value="{{ old('last_name', $patient->last_name) }}"
                               class="form-control @error('last_name') is-invalid @enderror" required
                               style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                        @error('last_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    {{-- Age --}}
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">
                            Age <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <input type="number" name="age" value="{{ old('age', $patient->age) }}" min="0" max="150"
                               class="form-control @error('age') is-invalid @enderror" required
                               style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                        @error('age')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    {{-- Gender --}}
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">
                            Gender <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <select name="gender" class="form-control @error('gender') is-invalid @enderror" required
                                style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                            <option value="">Select Gender</option>
                            <option value="male" @selected(old('gender', $patient->gender) === 'male')>Male</option>
                            <option value="female" @selected(old('gender', $patient->gender) === 'female')>Female</option>
                            <option value="other" @selected(old('gender', $patient->gender) === 'other')>Other</option>
                        </select>
                        @error('gender')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- Section 2: Location Information --}}
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
                        <select name="location_id" id="location_id" class="form-select clinical-input @error('location_id') is-invalid @enderror" required
                                style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                            <option value="">Select City...</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}"
                                        data-district="{{ $loc->district }}"
                                        data-state="{{ $loc->state }}"
                                        @selected(old('location_id', $patient->location_id) == $loc->id)>
                                    {{ $loc->name ?: ($loc->city ?: "Location #{$loc->id}") }}
                                </option>
                            @endforeach
                        </select>
                        @error('location_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">District</label>
                        <input type="text" id="district" class="form-control clinical-input" readonly placeholder="Auto-filled"
                               style="background:linear-gradient(135deg, #ebf5fbeb 0%, #D6EAF8 100%);border:1px solid #D6EAF8;border-radius:8px;padding:0.75rem 1rem">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">State</label>
                        <input type="text" id="state" class="form-control clinical-input" readonly placeholder="Auto-filled"
                               style="background:linear-gradient(135deg, #ebf5fbeb 0%, #D6EAF8 100%);border:1px solid #D6EAF8;border-radius:8px;padding:0.75rem 1rem">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-top:1.25rem">
                    {{-- Contact --}}
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">
                            Contact No <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <input type="text" name="contact_no" value="{{ old('contact_no', $patient->contact_no) }}" maxlength="15"
                               class="form-control @error('contact_no') is-invalid @enderror" required
                               style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                        @error('contact_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    {{-- Doctor --}}
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">
                            Doctor <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <select name="doctor_id" class="form-control select2 @error('doctor_id') is-invalid @enderror" required
                                style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                            <option value="">Select Doctor</option>
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->id }}" @selected(old('doctor_id', $patient->doctor_id) == $doc->id)>
                                    {{ $doc->name }} {{ $doc->doctor_type ? '(' . ucfirst($doc->doctor_type) . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('doctor_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- Section 3: Appointment Details --}}
            <div style="margin-bottom:2.5rem">
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem">
                    <div style="width:4px;height:24px;background:#27AE60;border-radius:2px"></div>
                    <h5 style="margin:0;font-weight:700;color:#27AE60;font-size:1.1rem">Appointment & Case Details</h5>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem">
                    {{-- Case Type --}}
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">
                            Case Type <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <select name="case_id" id="caseSelect" class="form-control select2 @error('case_id') is-invalid @enderror" required
                                style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
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

                    {{-- Case Fee --}}
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">
                            Case Fee (₹) <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <div style="display:flex;align-items:center;background:linear-gradient(135deg, #D5F5E3 0%, #E8F8F5 100%);border:1px solid #A9DFBF;border-radius:8px;padding:0 0.75rem;position:relative">
                            <span style="color:#27AE60;font-weight:700;font-size:1.1rem">₹</span>
                            <input type="number" name="case_fee" id="caseFee"
                                   value="{{ old('case_fee', $patient->case_fee) }}"
                                   step="0.01" min="0"
                                   class="form-control border-0 @error('case_fee') is-invalid @enderror" required
                                   style="background:transparent;padding:0.75rem 0.75rem 0.75rem 0.5rem;font-weight:600;color:#27AE60">
                        </div>
                        @error('case_fee')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    {{-- Appointment Date --}}
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">
                            Appointment Date <span style="color:#C0392B;font-weight:700">*</span>
                        </label>
                        <input type="text" name="appointment_date" id="appointmentDate"
                               value="{{ old('appointment_date', $patient->appointment_date?->format('Y-m-d')) }}"
                               class="form-control flatpickr @error('appointment_date') is-invalid @enderror" required
                               style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                        @error('appointment_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-top:1.25rem">
                    {{-- Time Slot --}}
                    <div class="form-group">
                        <label class="form-label fw-600" style="color:#2C3E50;font-size:0.9rem">Time Slot</label>
                        <select name="slot_id" class="form-control select2"
                                style="border:1px solid #E2E8F0;border-radius:8px;padding:0.75rem 1rem">
                            <option value="">No Slot</option>
                            @foreach($slots as $s)
                                <option value="{{ $s->id }}" @selected(old('slot_id', $patient->slot_id) == $s->id)>
                                    {{ $s->slot_name ?? $s->label ?? $s->name ?? $s->id }}
                                    @if(!empty($s->start_time) && !empty($s->end_time))
                                        ({{ \Illuminate\Support\Carbon::parse($s->start_time)->format('h:i A') }} - {{ \Illuminate\Support\Carbon::parse($s->end_time)->format('h:i A') }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Old Patient --}}
                    <div class="form-group" style="display:flex;align-items:flex-end;gap:0.75rem;padding-bottom:0.35rem;background:#F0F4F8;padding:1rem;border-radius:8px;border:1px dashed #2980B9">
                        <input type="checkbox" name="is_old_patient" value="1" id="isOldPatient"
                               @checked(old('is_old_patient', $patient->is_old_patient))
                               style="width:20px;height:20px;accent-color:#1B4F72;cursor:pointer;flex-shrink:0">
                        <label for="isOldPatient" class="form-label fw-600" style="margin:0;cursor:pointer;color:#2C3E50;font-size:0.9rem">Old / Returning Patient</label>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div style="display:flex;gap:0.875rem;margin-top:2.5rem;padding-top:1.75rem;border-top:1px solid #E2E8F0">
                <button type="submit" class="hms-btn" style="background:linear-gradient(135deg, #27AE60 0%, #229954 100%);color:#fff;border:none;padding:0.875rem 2rem;border-radius:8px;font-weight:600;display:flex;align-items:center;gap:0.5rem;transition:all 0.3s;cursor:pointer;box-shadow:0 2px 8px rgba(39,174,96,0.3)">
                    <i class="bi bi-check-circle-fill"></i> Update Patient
                </button>
                <a href="{{ route('hospital.patients.index', ['slug' => $slug]) }}" class="hms-btn" style="background:#fff;color:#1B4F72;border:2px solid #1B4F72;padding:0.75rem 2rem;border-radius:8px;font-weight:600;display:flex;align-items:center;gap:0.5rem;transition:all 0.3s;cursor:pointer">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $.fn.select2 !== 'undefined') {
            $('.select2').select2({ width: '100%' });
        }
        if (typeof flatpickr !== 'undefined') {
            flatpickr('.flatpickr', { dateFormat: 'Y-m-d' });
        }

        // ── Contact No & WhatsApp No: Only Numbers Allowed ──────────
        var contactInput = document.querySelector('input[name="contact_no"]');
        var whatsappInput = document.querySelector('input[name="whatsapp_no"]');

        var blockNonNumeric = function (input) {
            if (!input) { return; }

            input.addEventListener('keypress', function (e) {
                var char = String.fromCharCode(e.which);
                if (!/[0-9]/.test(char)) {
                    e.preventDefault();
                }
            });

            input.addEventListener('paste', function (e) {
                e.preventDefault();
                var pastedText = (e.clipboardData || window.clipboardData).getData('text');
                if (/^\d+$/.test(pastedText)) {
                    this.value = pastedText;
                }
            });
        };

        blockNonNumeric(contactInput);
        blockNonNumeric(whatsappInput);

        var caseSelect = document.getElementById('caseSelect');
        var caseFee    = document.getElementById('caseFee');
        var locationSelect = document.getElementById('location_id');
        var districtInput = document.getElementById('district');
        var stateInput = document.getElementById('state');

        function updateLocationFields() {
            if (!locationSelect || !districtInput || !stateInput) {
                return;
            }

            var selectedOption = locationSelect.options[locationSelect.selectedIndex];

            if (locationSelect.value) {
                districtInput.value = selectedOption.getAttribute('data-district') || '';
                stateInput.value = selectedOption.getAttribute('data-state') || '';
            } else {
                districtInput.value = '';
                stateInput.value = '';
            }
        }

        if (caseSelect && caseFee) {
            $(caseSelect).on('change', function() {
                var opt = this.options[this.selectedIndex];
                caseFee.value = opt.dataset.fee || 0;
            });
        }

        if (locationSelect) {
            locationSelect.addEventListener('change', updateLocationFields);
            updateLocationFields();
        }
    });
</script>
@endpush
