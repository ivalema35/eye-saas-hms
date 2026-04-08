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
    <div class="hms-alert hms-alert-warning" style="margin-bottom:1rem">
        <i class="fa-solid fa-triangle-exclamation"></i>
        Primary examination has already been done for this patient. Changes will not affect examination data.
    </div>
@endif

<div class="hms-card">
    <div class="hms-card-body">
        <form method="POST" action="{{ route('hospital.patients.update', ['slug' => $slug, 'patient' => $patient->id]) }}">
            @csrf @method('PUT')

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem">
                {{-- MRD (Read-only) --}}
                <div class="form-group">
                    <label class="form-label">MRD Number</label>
                    <input type="text" value="{{ $patient->patient_code }}" class="form-control" readonly
                           style="background:#F1F5F9;font-weight:700;color:#1B4F72">
                </div>

                {{-- First Name --}}
                <div class="form-group">
                    <label class="form-label">First Name <span style="color:#C0392B">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name', $patient->first_name) }}"
                           class="form-control @error('first_name') is-invalid @enderror" required>
                    @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Middle Name --}}
                <div class="form-group">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name', $patient->middle_name) }}" class="form-control">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-top:1rem">
                {{-- Last Name --}}
                <div class="form-group">
                    <label class="form-label">Last Name <span style="color:#C0392B">*</span></label>
                    <input type="text" name="last_name" value="{{ old('last_name', $patient->last_name) }}"
                           class="form-control @error('last_name') is-invalid @enderror" required>
                    @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Age --}}
                <div class="form-group">
                    <label class="form-label">Age <span style="color:#C0392B">*</span></label>
                    <input type="number" name="age" value="{{ old('age', $patient->age) }}" min="0" max="150"
                           class="form-control @error('age') is-invalid @enderror" required>
                    @error('age')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Gender --}}
                <div class="form-group">
                    <label class="form-label">Gender <span style="color:#C0392B">*</span></label>
                    <select name="gender" class="form-control @error('gender') is-invalid @enderror" required>
                        <option value="">Select</option>
                        <option value="male" @selected(old('gender', $patient->gender) === 'male')>Male</option>
                        <option value="female" @selected(old('gender', $patient->gender) === 'female')>Female</option>
                        <option value="other" @selected(old('gender', $patient->gender) === 'other')>Other</option>
                    </select>
                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row g-3" style="margin-top:1rem">
                <div class="col-md-4">
                    <label class="form-label">City <span class="text-danger">*</span></label>
                    <select name="location_id" id="location_id" class="form-select clinical-input @error('location_id') is-invalid @enderror" required>
                        <option value="">Select City...</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}"
                                    data-district="{{ $loc->district }}"
                                    data-state="{{ $loc->state }}"
                                    @selected(old('location_id', $patient->location_id) == $loc->id)>
                                {{ $loc->city }}
                            </option>
                        @endforeach
                    </select>
                    @error('location_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">District</label>
                    <input type="text" id="district" class="form-control clinical-input" readonly placeholder="Auto-filled">
                </div>

                <div class="col-md-4">
                    <label class="form-label">State</label>
                    <input type="text" id="state" class="form-control clinical-input" readonly placeholder="Auto-filled">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1rem">
                {{-- Contact --}}
                <div class="form-group">
                    <label class="form-label">Contact No <span style="color:#C0392B">*</span></label>
                    <input type="text" name="contact_no" value="{{ old('contact_no', $patient->contact_no) }}" maxlength="15"
                           class="form-control @error('contact_no') is-invalid @enderror" required>
                    @error('contact_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Doctor --}}
                <div class="form-group">
                    <label class="form-label">Doctor <span style="color:#C0392B">*</span></label>
                    <select name="doctor_id" class="form-control select2 @error('doctor_id') is-invalid @enderror" required>
                        <option value="">Select Doctor</option>
                        @foreach($doctors as $doc)
                            <option value="{{ $doc->id }}" @selected(old('doctor_id', $patient->doctor_id) == $doc->id)>
                                {{ $doc->name }} {{ $doc->doctor_type ? '(' . ucfirst($doc->doctor_type) . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('doctor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-top:1rem">
                {{-- Case Type --}}
                <div class="form-group">
                    <label class="form-label">Case Type <span style="color:#C0392B">*</span></label>
                    <select name="case_id" id="caseSelect" class="form-control select2 @error('case_id') is-invalid @enderror" required>
                        <option value="">Select Case</option>
                        @foreach($cases as $c)
                            <option value="{{ $c->id }}" data-fee="{{ $c->fee ?? 0 }}"
                                    @selected(old('case_id', $patient->case_id) == $c->id)>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('case_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Case Fee --}}
                <div class="form-group">
                    <label class="form-label">Case Fee (₹) <span style="color:#C0392B">*</span></label>
                    <input type="number" name="case_fee" id="caseFee"
                           value="{{ old('case_fee', $patient->case_fee) }}"
                           step="0.01" min="0"
                           class="form-control @error('case_fee') is-invalid @enderror" required>
                    @error('case_fee')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Appointment Date --}}
                <div class="form-group">
                    <label class="form-label">Appointment Date <span style="color:#C0392B">*</span></label>
                    <input type="text" name="appointment_date" id="appointmentDate"
                           value="{{ old('appointment_date', $patient->appointment_date?->format('Y-m-d')) }}"
                           class="form-control flatpickr @error('appointment_date') is-invalid @enderror" required>
                    @error('appointment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1rem">
                {{-- Time Slot --}}
                <div class="form-group">
                    <label class="form-label">Time Slot</label>
                    <select name="slot_id" class="form-control select2">
                        <option value="">No Slot</option>
                        @foreach($slots as $s)
                            <option value="{{ $s->id }}" @selected(old('slot_id', $patient->slot_id) == $s->id)>
                                {{ $s->label ?? $s->name ?? $s->id }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Old Patient --}}
                <div class="form-group" style="display:flex;align-items:flex-end;gap:.5rem;padding-bottom:.35rem">
                    <input type="checkbox" name="is_old_patient" value="1" id="isOldPatient"
                           @checked(old('is_old_patient', $patient->is_old_patient))
                           style="width:18px;height:18px;accent-color:#1B4F72">
                    <label for="isOldPatient" class="form-label" style="margin:0">Old / Returning Patient</label>
                </div>
            </div>

            <div style="margin-top:1.5rem;display:flex;gap:.75rem">
                <button type="submit" class="hms-btn hms-btn-primary">
                    <i class="fa-solid fa-save"></i> Update Patient
                </button>
                <a href="{{ route('hospital.patients.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">Cancel</a>
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
