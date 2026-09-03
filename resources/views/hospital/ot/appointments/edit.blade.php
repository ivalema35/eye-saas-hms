@extends('hospital.layouts.app')
@section('title', 'Edit OT Appointment')
@section('page-header', '')

@section('content')
    <div class="ot-appt-form-page">
        @include('hospital.ot.appointments._doctor_load_cards')

        <div class="rpc-page rpc-page--ot">
            <div class="rpc-card">
                <div class="rpc-form-title-row">
                    <h2 class="rpc-form-title">Edit Appointment — {{ $appointment->appointment_number }}</h2>
                    <a href="{{ route('hospital.ot.appointments.index', ['slug' => $slug]) }}" class="rpc-back-btn">
                        <i class="bi bi-arrow-left"></i> Back to Appointments
                    </a>
                </div>
                <p class="rpc-checkin-note">
                    <i class="bi bi-pencil-square"></i>
                    {{ $appointment->patient_name }} &middot; {{ $appointment->mobile_no }}
                </p>
                <div class="rpc-form-body">
                    @if($errors->any())
                        <div class="alert alert-danger py-2 px-3 mb-2" style="font-size:.82rem;border-radius:8px">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST"
                        action="{{ route('hospital.ot.appointments.update', ['slug' => $slug, 'id' => $appointment->id]) }}"
                        id="otAppointmentForm">
                        @csrf
                        @method('PUT')
                        @include('hospital.ot.appointments._form')

                        <div class="rpc-actions d-flex justify-content-end gap-2">
                            <a href="{{ route('hospital.ot.appointments.index', ['slug' => $slug]) }}"
                                class="hms-btn hms-btn-outline">Cancel</a>
                            <button type="submit" id="rpcSubmitBtn" class="rpc-submit">
                                <i class="bi bi-check2-circle"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/reception-patient-form.css') }}">
    <style>
        .ot-appt-form-page {
            padding: 0.25rem 0 1rem;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('js/reception-patient-form.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            ReceptionPatientForm.initPlugins({
                dateFormat: 'Y-m-d',
                onChange: function (selectedDates, dateStr, instance) {
                    instance.element.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
            ReceptionPatientForm.bindAutoOpenSelects(document.querySelector('.rpc-page--ot'));
            ReceptionPatientForm.bindSubmitFocus('#otAppointmentForm', '#rpcSubmitBtn');

            if (window.HmsIntlPhone) {
                var mobileEl = document.getElementById('mobile_no');
                var whatsappEl = document.querySelector('[name="whatsapp_no"]');
                if (mobileEl) { HmsIntlPhone.bind(mobileEl); }
                if (whatsappEl) { HmsIntlPhone.bind(whatsappEl); }
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
                $('#location_id').on('change', syncDistrictState);
            } else {
                var locationEl = document.getElementById('location_id');
                if (locationEl) locationEl.addEventListener('change', syncDistrictState);
            }
            syncDistrictState();

            document.getElementById('rpcSubmitBtn').addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('otAppointmentForm').requestSubmit();
                }
            });
        });
    </script>
@endpush
