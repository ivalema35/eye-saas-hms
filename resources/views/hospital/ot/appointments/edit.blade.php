@extends('hospital.layouts.app')
@section('title', 'Edit OT Appointment')
@section('page-header', 'Edit OT Appointment')

@section('page-actions')
    <a href="{{ route('hospital.ot.appointments.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="bi bi-arrow-left me-1"></i> Back to Appointments
    </a>
@endsection

@section('content')
<div class="ot-appt-form-page">
<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card ot-premium-card border-0">
            <div class="ot-card-header">
                <div class="ot-title-wrap">
                    <span class="ot-title-icon" aria-hidden="true">
                        <i class="bi bi-pencil-square" style="font-size: 1.2rem;"></i>
                    </span>
                    <div class="flex-grow-1">
                        <h5 class="ot-title">{{ $appointment->appointment_number }}</h5>
                        <div class="ot-subtitle">{{ $appointment->patient_name }} &middot; {{ $appointment->mobile_no }}</div>
                    </div>
                </div>
            </div>

            <div class="card-body px-4 pb-4 pt-4">
                @if($errors->any())
                    <div class="alert alert-danger mb-4 ot-alert">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('hospital.ot.appointments.update', ['slug' => $slug, 'id' => $appointment->id]) }}">
                    @csrf
                    @method('PUT')
                    @include('hospital.ot.appointments._form')

                    <div class="ot-actions d-flex justify-content-end gap-2">
                        <a href="{{ route('hospital.ot.appointments.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">Cancel</a>
                        <button type="submit" class="hms-btn hms-btn-primary px-4">
                            <i class="bi bi-check2-circle me-1"></i> Save Changes
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
    /*
      OT Appointment Edit Form (Design refresh)
      Keep Blade/dynamic logic untouched; CSS-only + layout wrappers.
      Palette follows hospital shell theme (#1B4F72).
    */

    .ot-appt-form-page {
        --ot-secondary: #1B4F72;
        --ot-s2-06: rgba(27, 79, 114, 0.06);
        --ot-s2-08: rgba(27, 79, 114, 0.08);
        --ot-s2-12: rgba(27, 79, 114, 0.12);
        --ot-s2-18: rgba(27, 79, 114, 0.18);
        --ot-s2-24: rgba(27, 79, 114, 0.24);

        position: relative;
        padding: .25rem 0 1.25rem;
        color: var(--ot-secondary);
        animation: ot-page-in 420ms ease both;
    }

    @keyframes ot-page-in {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .ot-appt-form-page .btn,
    .ot-appt-form-page .hms-btn {
        border-radius: 12px;
        font-weight: 800;
        transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, border-color 170ms ease, color 170ms ease;
    }

    .ot-appt-form-page .btn:hover,
    .ot-appt-form-page .hms-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 26px rgba(27, 79, 114, 0.14);
    }

    .ot-premium-card {
        background: rgba(255, 255, 255, 0.84);
        border: 1px solid var(--ot-s2-12) !important;
        border-radius: 22px;
        box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
        overflow: hidden;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        animation: ot-card-rise 520ms cubic-bezier(.2,.9,.2,1) both;
    }

    @keyframes ot-card-rise {
        from { opacity: 0; transform: translateY(10px) scale(0.99); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .ot-card-header {
        background:
            linear-gradient(135deg, rgba(235, 245, 251, 0.92), rgba(255, 255, 255, 0.94)),
            #ffffff;
        border-bottom: 1px solid var(--ot-s2-12);
        padding: 1.15rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .ot-title-wrap {
        display: flex;
        align-items: center;
        gap: .85rem;
        min-width: 0;
    }

    .ot-title-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        background: var(--ot-secondary);
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 14px 30px rgba(27, 79, 114, 0.22);
        flex: 0 0 auto;
    }

    .ot-title {
        font-weight: 900;
        letter-spacing: -0.2px;
        margin: 0;
        color: var(--ot-secondary);
    }

    .ot-subtitle {
        margin: .15rem 0 0;
        font-weight: 650;
        color: rgba(27, 79, 114, 0.72);
        font-size: .85rem;
    }

    .ot-alert { border-radius: 14px; font-weight: 650; }

    .ot-appt-form-page .form-label {
        font-weight: 800;
        font-size: .82rem;
        color: var(--ot-secondary);
        letter-spacing: .01em;
    }

    .ot-appt-form-page .form-control,
    .ot-appt-form-page .form-select {
        border: 1px solid var(--ot-s2-18);
        border-radius: 12px;
        padding: .55rem .85rem;
        background: rgba(255, 255, 255, 0.92);
        color: var(--ot-secondary);
        font-weight: 600;
        transition: border-color 160ms ease, box-shadow 160ms ease;
    }

    .ot-appt-form-page .form-control:focus,
    .ot-appt-form-page .form-select:focus {
        border-color: var(--ot-secondary);
        box-shadow: 0 0 0 .2rem var(--ot-s2-12);
    }

    .ot-appt-form-page .form-control[readonly] {
        background: rgba(27, 79, 114, 0.05) !important;
        color: rgba(27, 79, 114, 0.65);
    }

    .ot-appt-form-page .form-text {
        color: rgba(27, 79, 114, 0.6);
        font-weight: 600;
        font-size: .78rem;
    }

    .ot-actions {
        margin-top: 1.25rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--ot-s2-12);
    }

    @media (prefers-reduced-motion: reduce) {
        .ot-appt-form-page,
        .ot-premium-card,
        .ot-appt-form-page .btn,
        .ot-appt-form-page .hms-btn {
            animation: none !important;
            transition: none !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof flatpickr !== 'undefined') {
        flatpickr('#appointment_date', { dateFormat: 'Y-m-d' });
    }

    function syncDistrictState() {
        var locationEl = document.getElementById('location_id');
        var districtEl = document.getElementById('district');
        var stateEl = document.getElementById('state');
        if (!locationEl || !districtEl || !stateEl) return;

        var opt = locationEl.options[locationEl.selectedIndex];
        if (opt && opt.value) {
            districtEl.value = opt.getAttribute('data-district') || '';
            stateEl.value = opt.getAttribute('data-state') || '';
        } else {
            districtEl.value = '';
            stateEl.value = '';
        }
    }

    if (window.jQuery && $.fn.select2) {
        $('#location_id').select2({ width: '100%', placeholder: 'Select city...' });
        $('#location_id').on('change', syncDistrictState);
    } else {
        var locationEl = document.getElementById('location_id');
        if (locationEl) {
            locationEl.addEventListener('change', syncDistrictState);
        }
    }

    syncDistrictState();
});
</script>
@endpush
