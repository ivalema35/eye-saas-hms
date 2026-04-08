@extends('hospital.layouts.app')
@section('title', 'Phone Appointment')
@section('page-header', 'Register Phone Appointment')

@section('page-actions')
    <a href="{{ route('hospital.patients.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')

<div class="hms-card">
    <div class="hms-card-body">
        <form method="POST" action="{{ route('hospital.patients.store-phone', ['slug' => $slug]) }}">
            @csrf

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem">
                {{-- First Name --}}
                <div class="form-group">
                    <label class="form-label">First Name <span style="color:#C0392B">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}"
                           class="form-control @error('first_name') is-invalid @enderror" required>
                    @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Middle Name --}}
                <div class="form-group">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="form-control">
                </div>

                {{-- Last Name --}}
                <div class="form-group">
                    <label class="form-label">Last Name <span style="color:#C0392B">*</span></label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}"
                           class="form-control @error('last_name') is-invalid @enderror" required>
                    @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-top:1rem">
                {{-- Age --}}
                <div class="form-group">
                    <label class="form-label">Age <span style="color:#C0392B">*</span></label>
                    <input type="number" name="age" value="{{ old('age') }}" min="0" max="150"
                           class="form-control @error('age') is-invalid @enderror" required>
                    @error('age')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Gender --}}
                <div class="form-group">
                    <label class="form-label">Gender <span style="color:#C0392B">*</span></label>
                    <select name="gender" class="form-control @error('gender') is-invalid @enderror" required>
                        <option value="">Select</option>
                        <option value="male" @selected(old('gender') === 'male')>Male</option>
                        <option value="female" @selected(old('gender') === 'female')>Female</option>
                        <option value="other" @selected(old('gender') === 'other')>Other</option>
                    </select>
                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Contact --}}
                <div class="form-group">
                    <label class="form-label">Contact No <span style="color:#C0392B">*</span></label>
                    <input type="text" name="contact_no" value="{{ old('contact_no') }}" maxlength="15"
                           class="form-control @error('contact_no') is-invalid @enderror" required>
                    @error('contact_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1rem">
                {{-- Doctor --}}
                <div class="form-group">
                    <label class="form-label">Doctor <span style="color:#C0392B">*</span></label>
                    <select name="doctor_id" class="form-control select2 @error('doctor_id') is-invalid @enderror" required>
                        <option value="">Select Doctor</option>
                        @foreach($doctors as $doc)
                            <option value="{{ $doc->id }}" @selected(old('doctor_id') == $doc->id)>
                                {{ $doc->name }} {{ $doc->doctor_type ? '(' . ucfirst($doc->doctor_type) . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('doctor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Case Type --}}
                <div class="form-group">
                    <label class="form-label">Case Type <span style="color:#C0392B">*</span></label>
                    <select name="case_id" id="caseSelect" class="form-control select2 @error('case_id') is-invalid @enderror" required>
                        <option value="">Select Case</option>
                        @foreach($cases as $c)
                            <option value="{{ $c->id }}" data-fee="{{ $c->fee ?? 0 }}" @selected(old('case_id') == $c->id)>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('case_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-top:1rem">
                {{-- Case Fee --}}
                <div class="form-group">
                    <label class="form-label">Case Fee (₹) <span style="color:#C0392B">*</span></label>
                    <input type="number" name="case_fee" id="caseFee" value="{{ old('case_fee', '0') }}"
                           step="0.01" min="0"
                           class="form-control @error('case_fee') is-invalid @enderror" required>
                    @error('case_fee')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Appointment Date --}}
                <div class="form-group">
                    <label class="form-label">Appointment Date <span style="color:#C0392B">*</span></label>
                    <input type="text" name="appointment_date" id="appointmentDate"
                           value="{{ old('appointment_date', now()->addDay()->format('Y-m-d')) }}"
                           class="form-control flatpickr @error('appointment_date') is-invalid @enderror" required>
                    @error('appointment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Time Slot --}}
                <div class="form-group">
                    <label class="form-label">Time Slot</label>
                    <select name="slot_id" class="form-control select2">
                        <option value="">No Slot</option>
                        @foreach($slots as $s)
                            <option value="{{ $s->id }}" @selected(old('slot_id') == $s->id)>
                                {{ $s->label ?? $s->name ?? $s->id }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1rem">
                {{-- Referrer --}}
                <div class="form-group">
                    <label class="form-label">Referred By</label>
                    <input type="text" name="referrer_id" value="{{ old('referrer_id') }}" class="form-control"
                           placeholder="Referrer (optional)">
                </div>

                {{-- Old Patient --}}
                <div class="form-group" style="display:flex;align-items:flex-end;gap:.5rem;padding-bottom:.35rem">
                    <input type="checkbox" name="is_old_patient" value="1" id="isOldPatient"
                           @checked(old('is_old_patient')) style="width:18px;height:18px;accent-color:#1B4F72">
                    <label for="isOldPatient" class="form-label" style="margin:0">Old / Returning Patient</label>
                </div>
            </div>

            <div style="margin-top:1rem;color:#2980B9;font-size:.85rem">
                <i class="fa-solid fa-phone"></i> This patient will be registered as a <strong>Phone Appointment</strong>.
            </div>

            <div style="margin-top:1.5rem;display:flex;gap:.75rem">
                <button type="submit" class="hms-btn hms-btn-primary">
                    <i class="fa-solid fa-check"></i> Register Phone Appointment
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
        if (caseSelect && caseFee) {
            $(caseSelect).on('change', function() {
                var opt = this.options[this.selectedIndex];
                caseFee.value = opt.dataset.fee || 0;
            });
        }
    });
</script>
@endpush
