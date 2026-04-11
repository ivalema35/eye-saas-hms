@extends('hospital.layouts.app')
@section('title', 'Create OT Booking')
@section('page-header', 'Create OT Booking')

@section('page-actions')
    <a href="{{ route('hospital.ot.bookings.index', ['slug' => $slug]) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Bookings
    </a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold" style="color: var(--color-primary);">
                    <i class="bi bi-calendar2-plus me-2"></i> OT Booking Form
                </h5>
            </div>

            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('hospital.ot.bookings.store', ['slug' => $slug]) }}">
                    @csrf

                    <h6 class="fw-bold text-primary mb-3">A. Booking Details</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Patient <span class="text-danger">*</span></label>
                            <select name="patient_id" id="patient_id" class="form-select" required>
                                <option value="">Select patient...</option>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}" {{ (string) old('patient_id') === (string) $patient->id ? 'selected' : '' }}>
                                        {{ $patient->patient_code }} - {{ $patient->full_name }} ({{ $patient->contact_no ?? 'NA' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">OT Date <span class="text-danger">*</span></label>
                            <input type="text" name="surgery_date" id="surgery_date" class="form-control" value="{{ old('surgery_date') }}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">OT Slot <span class="text-danger">*</span></label>
                            <select name="slot_id" class="form-select" required>
                                <option value="">Select slot...</option>
                                @foreach($slots as $slot)
                                    <option value="{{ $slot->id }}" {{ (string) old('slot_id') === (string) $slot->id ? 'selected' : '' }}>
                                        {{ $slot->slot_name }}
                                        @if($slot->start_time && $slot->end_time)
                                            ({{ \Illuminate\Support\Carbon::parse($slot->start_time)->format('h:i A') }} - {{ \Illuminate\Support\Carbon::parse($slot->end_time)->format('h:i A') }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">OT Doctor <span class="text-danger">*</span></label>
                            <select name="ot_doctor_id" class="form-select" required>
                                <option value="">Select doctor...</option>
                                @foreach($doctors as $doctor)
                                    <option value="{{ $doctor->id }}" {{ (string) old('ot_doctor_id') === (string) $doctor->id ? 'selected' : '' }}>
                                        Dr. {{ $doctor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label d-block">Eye <span class="text-danger">*</span></label>
                            @foreach(['RE', 'LE', 'Both'] as $eye)
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="eye" id="eye_{{ strtolower($eye) }}" value="{{ $eye }}" {{ old('eye', 'RE') === $eye ? 'checked' : '' }}>
                                    <label class="form-check-label" for="eye_{{ strtolower($eye) }}">{{ $eye }}</label>
                                </div>
                            @endforeach
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">OT Type <span class="text-danger">*</span></label>
                            <select name="ot_type_id" id="ot_type_id" class="form-select" required>
                                <option value="">Select OT type...</option>
                                @foreach($otTypes as $otType)
                                    <option value="{{ $otType->id }}" {{ (string) old('ot_type_id') === (string) $otType->id ? 'selected' : '' }}>
                                        {{ $otType->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Surgery Type <span class="text-danger">*</span></label>
                            <select name="ot_surgery_type_id" id="ot_surgery_type_id" class="form-select" required>
                                <option value="">Select surgery type...</option>
                                @foreach($surgeryTypes as $surgeryType)
                                    <option value="{{ $surgeryType->id }}"
                                            data-ot-type="{{ $surgeryType->ot_type_id }}"
                                            {{ (string) old('ot_surgery_type_id') === (string) $surgeryType->id ? 'selected' : '' }}>
                                        {{ $surgeryType->surgery_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mb-3">B. Counselling Details</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label d-block">Mediclaim <span class="text-danger">*</span></label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="mediclaim" id="mediclaim_yes" value="1" {{ old('mediclaim', '0') === '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="mediclaim_yes">Yes</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="mediclaim" id="mediclaim_no" value="0" {{ old('mediclaim', '0') === '0' ? 'checked' : '' }}>
                                <label class="form-check-label" for="mediclaim_no">No</label>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Lens Option</label>
                            <select name="lens_option" class="form-select">
                                <option value="">Select lens option...</option>
                                @foreach($lensOptions as $lensOption)
                                    <option value="{{ $lensOption->name }}" {{ old('lens_option') === $lensOption->name ? 'selected' : '' }}>
                                        {{ $lensOption->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Package Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">INR</span>
                                <input type="number" step="0.01" min="0" name="package_amount" class="form-control" value="{{ old('package_amount') }}" placeholder="0.00">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Payment Mode</label>
                            <select name="payment_mode" class="form-select">
                                <option value="">Select payment mode...</option>
                                @foreach(['Cash', 'Online'] as $paymentMode)
                                    <option value="{{ $paymentMode }}" {{ old('payment_mode') === $paymentMode ? 'selected' : '' }}>
                                        {{ $paymentMode }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="report_ok" id="report_ok" value="1" {{ old('report_ok') ? 'checked' : '' }}>
                                <label class="form-check-label" for="report_ok">Pre-Report OK?</label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" rows="3" class="form-control" placeholder="Optional counselling notes...">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('hospital.ot.bookings.index', ['slug' => $slug]) }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check2-circle me-1"></i> Save Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery && $.fn.select2) {
            $('#patient_id').select2({ width: '100%', placeholder: 'Select patient...' });
        }

        if (typeof flatpickr !== 'undefined') {
            flatpickr('#surgery_date', {
                dateFormat: 'Y-m-d',
                minDate: 'today'
            });
        }

        const otTypeSelect = document.getElementById('ot_type_id');
        const surgeryTypeSelect = document.getElementById('ot_surgery_type_id');

        function filterSurgeryTypes() {
            const selectedType = otTypeSelect.value;
            let hasVisible = false;

            Array.from(surgeryTypeSelect.options).forEach(function (option, index) {
                if (index === 0) {
                    option.hidden = false;
                    return;
                }

                const match = selectedType === '' || option.dataset.otType === selectedType;
                option.hidden = !match;
                if (match) {
                    hasVisible = true;
                }
            });

            if (!hasVisible) {
                surgeryTypeSelect.value = '';
            }
        }

        otTypeSelect.addEventListener('change', filterSurgeryTypes);
        filterSurgeryTypes();
    });
</script>
@endpush
