@extends('hospital.layouts.app')
@section('title', 'Create OT Booking')
@section('page-header', 'Create OT Booking')

@section('page-actions')
    <a href="{{ route('hospital.ot.bookings.index', ['slug' => $slug]) }}" class="btn btn-outline-secondary btn-sm ot-back-bookings-btn">
        <i class="bi bi-arrow-left me-1"></i> Back to Bookings
    </a>
@endsection

@section('content')
<div class="ot-page">
<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card border-0 shadow-sm ot-booking-card ot-animate-in">
            <div class="card-header ot-booking-header border-0 pt-4 pb-2 px-4">
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1 fw-bold ot-booking-title">
                            <i class="bi bi-calendar2-plus me-2"></i> OT Booking Form
                        </h5>
                        <div class="text-muted small">Fill required fields marked with <span class="text-danger">*</span>.</div>
                    </div>
                </div>
            </div>

            <div class="card-body px-4 pb-4 pt-2">
                @if($errors->any())
                    <div class="alert alert-danger mb-4 ot-alert ot-animate-in">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('hospital.ot.bookings.store', ['slug' => $slug]) }}">
                    @csrf

                    <section class="ot-section ot-section-a ot-animate-in">
                        <div class="ot-section-head">
                            <div>
                                <div class="ot-section-kicker">Section A</div>
                                <h4 class="fw-bold mb-0">Booking Details</h4>
                            </div>
                            <div class="ot-section-icon" aria-hidden="true">
                                <i class="bi bi-clipboard2-check"></i>
                            </div>
                        </div>

                        <div class="row g-3">
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
                    </section>

                    <section class="ot-section ot-section-b ot-animate-in">
                        <div class="ot-section-head">
                            <div>
                                <div class="ot-section-kicker">Section B</div>
                                <h4 class="fw-bold mb-0">Counselling Details</h4>
                            </div>
                            <div class="ot-section-icon" aria-hidden="true">
                                <i class="bi bi-chat-left-text"></i>
                            </div>
                        </div>

                        <div class="row g-3">
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

                        <!-- <div class="col-md-4">
                            <label class="form-label">Payment Mode</label>
                            <select name="payment_mode" class="form-select">
                                <option value="">Select payment mode...</option>
                                @foreach(['Cash', 'Online'] as $paymentMode)
                                    <option value="{{ $paymentMode }}" {{ old('payment_mode') === $paymentMode ? 'selected' : '' }}>
                                        {{ $paymentMode }}
                                    </option>
                                @endforeach
                            </select>
                        </div> -->

                        <!-- <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="report_ok" id="report_ok" value="1" {{ old('report_ok') ? 'checked' : '' }}>
                                <label class="form-check-label" for="report_ok">Pre-Report OK?</label>
                            </div>
                        </div> -->

                        <div class="col-md-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" rows="3" class="form-control" placeholder="Optional counselling notes...">{{ old('notes') }}</textarea>
                        </div>
                        </div>
                    </section>

                    <div class="ot-actions d-flex justify-content-end gap-2">
                        <a href="{{ route('hospital.ot.bookings.index', ['slug' => $slug]) }}" class="btn btn-outline-secondary ot-btn">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 ot-btn ot-btn-primary">
                            <i class="bi bi-check2-circle me-1"></i> Save Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('styles')
<style>
    .ot-page {
        position: relative;
        padding: .25rem 0 1.25rem;
    }

    .ot-page::before {
        content: none;
    }

    .ot-page > .row {
        position: relative;
        z-index: 1;
    }

    .ot-booking-card {
        border-radius: 18px;
        overflow: hidden;
        box-shadow: none;
        transform-origin: top center;
    }

    /* Bootstrap shadow utilities can still apply; force-disable for this page */
    .ot-page .shadow,
    .ot-page .shadow-sm,
    .ot-page .shadow-lg,
    .ot-booking-card.shadow,
    .ot-booking-card.shadow-sm,
    .ot-booking-card.shadow-lg {
        box-shadow: none !important;
    }

    .ot-booking-header {
        background:
            linear-gradient(180deg,
                color-mix(in srgb, var(--color-primary) 6%, var(--color-surface)) 0%,
                var(--color-surface) 76%
            );
    }

    .ot-booking-title {
        color: #1B4F72 !important;
        letter-spacing: .2px;
    }

    .ot-booking-title i {
        color: #1B4F72 !important;
    }

    .ot-back-bookings-btn {
        background: #1B4F72 !important;
        border-color: #1B4F72 !important;
        color: #fff !important;
        border-radius: 8px !important;
        font-weight: 800;
        padding: .45rem .9rem;
        box-shadow: 0 10px 22px rgba(27, 79, 114, .16);
        transition: transform 160ms ease, box-shadow 160ms ease, background 160ms ease;
    }

    .ot-back-bookings-btn:hover {
        background: #1B4F72 !important;
        border-color: #1B4F72 !important;
        color: #fff !important;
        transform: translateY(-1px);
        box-shadow: 0 14px 28px rgba(27, 79, 114, .22);
        text-decoration: none;
    }

    .ot-section {
        border: 1px solid #1B4F72;
        border-radius: 16px;
        padding: 1rem;
        background: var(--color-surface);
        margin-bottom: 1rem;
        box-shadow: none;
        transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
    }

    .ot-section:hover {
        transform: translateY(-2px);
        border-color: #1B4F72;
        box-shadow: none;
    }

    .ot-section:focus-within {
        border-color: #1B4F72;
        box-shadow: none;
    }

    .ot-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding-bottom: .75rem;
        margin-bottom: .75rem;
        border-bottom: 1px dashed #1B4F72;
    }

    .ot-section-head h6 {
        color: #1B4F72 !important;
    }

    .ot-section-kicker {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: #1B4F72;
        opacity: .85;
        margin-bottom: .15rem;
    }

    .ot-section-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        color: #1B4F72 !important;
        border: 1px solid #1B4F72;
        background:
            radial-gradient(100% 100% at 30% 20%, rgba(27, 79, 114, .10) 0%, transparent 60%),
            var(--color-surface);
        flex: 0 0 auto;
    }

    .ot-section-icon i {
        color: #1B4F72 !important;
    }

    .ot-section .form-control,
    .ot-section .form-select,
    .ot-section .input-group-text {
        border-radius: 12px;
        transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease;
    }

    .ot-section .form-control:focus,
    .ot-section .form-select:focus {
        transform: translateY(-1px);
    }

    .ot-section input[type="radio"],
    .ot-section input[type="checkbox"] {
        accent-color: var(--color-primary);
    }

    .ot-section .form-check-inline {
        margin-right: 1rem;
    }

    .ot-section .form-check-label {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .65rem;
        border-radius: 999px;
        border: 1px solid var(--border-color);
        background: color-mix(in srgb, var(--color-primary) 4%, var(--color-surface));
        transition: transform 160ms ease, border-color 160ms ease, background 160ms ease, box-shadow 160ms ease;
        cursor: pointer;
        user-select: none;
    }

    .ot-section .form-check-input:focus + .form-check-label {
        box-shadow: none;
        border-color: color-mix(in srgb, var(--color-primary) 30%, var(--border-color));
    }

    .ot-section .form-check-input:checked + .form-check-label {
        background: color-mix(in srgb, var(--color-primary) 10%, var(--color-surface));
        border-color: color-mix(in srgb, var(--color-primary) 28%, var(--border-color));
        box-shadow: none;
        transform: translateY(-1px);
    }

    .ot-alert {
        border-radius: 16px;
    }

    .ot-animate-in {
        animation: otFadeUp 420ms cubic-bezier(.2, .8, .2, 1) both;
    }

    .ot-section-a {
        animation-delay: 40ms;
    }

    .ot-section-b {
        animation-delay: 90ms;
    }

    @keyframes otFadeUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .ot-animate-in,
        .ot-section-a,
        .ot-section-b {
            animation: none !important;
        }

        .ot-section .form-control,
        .ot-section .form-select,
        .ot-section .input-group-text {
            transition: none !important;
        }

        .ot-section {
            transform: none !important;
        }
    }

    /* Select2 (if enabled) – keep it consistent with theme */
    .select2-container--default .select2-selection--single {
        border-radius: 12px;
        min-height: calc(1.5em + .75rem + 2px);
        border-color: var(--border-color);
        transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease;
    }

    .select2-container--default.select2-container--focus .select2-selection--single {
        transform: translateY(-1px);
        border-color: color-mix(in srgb, var(--color-primary) 30%, var(--border-color));
        box-shadow: none;
    }

    /* Remove Bootstrap focus ring/glow on this page only */
    .ot-page .form-control:focus,
    .ot-page .form-select:focus,
    .ot-page .form-check-input:focus,
    .ot-page .btn:focus,
    .ot-page .btn:focus-visible {
        box-shadow: none !important;
        outline: none !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: calc(1.5em + .75rem);
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: calc(1.5em + .75rem + 2px);
    }

    .ot-actions {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border-color);
        background:
            linear-gradient(180deg,
                transparent 0%,
                color-mix(in srgb, var(--color-primary) 3%, var(--color-surface)) 55%,
                var(--color-surface) 100%
            );
    }

    .ot-btn {
        border-radius: 12px;
        transition: transform 160ms ease, box-shadow 160ms ease;
    }

    .ot-btn:hover {
        transform: translateY(-1px);
        box-shadow: none;
    }

    .ot-btn-primary:hover {
        box-shadow: none;
    }
</style>
@endpush

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
