@extends('hospital.layouts.app')
@section('title', 'New OT Appointment')
{{-- Layout page-header intentionally unused — title sits BELOW doctor cards --}}

@section('content')
    <div class="ot-appt-form-page">

        @include('hospital.ot.appointments._doctor_load_cards')

        <div class="ot-appt-title-card">
            <div class="ot-appt-title-row">
                <div class="d-flex align-items-center gap-3">
                    <span class="ot-appt-title-icon" aria-hidden="true">
                        <i class="bi bi-calendar2-plus"></i>
                    </span>
                    <div>
                        <h1 class="ot-appt-page-title">New OT Appointment</h1>
                        <nav class="ot-appt-breadcrumb" aria-label="breadcrumb">
                            <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                            <span class="ot-appt-breadcrumb-sep">/</span>
                            <a href="{{ route('hospital.ot.appointments.index', ['slug' => $slug]) }}">Appointments</a>
                            <span class="ot-appt-breadcrumb-sep">/</span>
                            <span class="ot-appt-breadcrumb-current">New Appointment</span>
                        </nav>
                    </div>
                </div>
                <a href="{{ route('hospital.ot.appointments.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
                    <i class="bi bi-arrow-left me-1"></i> Back to Appointments
                </a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="card ot-premium-card border-0">
                    <div class="ot-card-header">
                        <div class="ot-title-wrap">
                            <span class="ot-title-icon" aria-hidden="true">
                                <i class="bi bi-calendar2-plus" style="font-size: 1.2rem;"></i>
                            </span>
                            <div class="flex-grow-1">
                                <h5 class="ot-title">Book Appointment</h5>
                                <div class="ot-subtitle">Pre-registration — patient hasn't arrived yet.</div>
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

                        <form method="POST" action="{{ route('hospital.ot.appointments.store', ['slug' => $slug]) }}">
                            @csrf
                            @include('hospital.ot.appointments._form')

                            <div class="ot-actions d-flex justify-content-end gap-2">
                                <a href="{{ route('hospital.ot.appointments.index', ['slug' => $slug]) }}"
                                    class="hms-btn hms-btn-outline">Cancel</a>
                                <button type="submit" class="hms-btn hms-btn-primary px-4" style="color: #1B4F72;">
                                    <i class="bi bi-check2-circle me-1"></i> Save Appointment
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
        .ot-appt-form-page {
            --ot-secondary: #1B4F72;
            --ot-s2-12: rgba(27, 79, 114, 0.12);
            --ot-s2-18: rgba(27, 79, 114, 0.18);
            position: relative;
            padding: .25rem 0 1.25rem;
            color: var(--ot-secondary);
            animation: ot-page-in 420ms ease both;
        }

        @keyframes ot-page-in {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .ot-appt-title-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.12);
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(15, 79, 134, 0.08);
            padding: 1.1rem 1.5rem;
            margin: 1.5rem 0 1.25rem;
        }

        .ot-appt-title-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .75rem;
        }

        .ot-appt-title-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: var(--ot-secondary);
            color: #fff;
            font-size: 1.2rem;
            box-shadow: 0 14px 30px rgba(27, 79, 114, 0.22);
        }

        .ot-appt-page-title {
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--ot-secondary);
            letter-spacing: -.015em;
            margin: 0;
        }

        .ot-appt-breadcrumb {
            margin-top: .4rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: #8891a0;
        }

        .ot-appt-breadcrumb a {
            color: #8891a0;
            text-decoration: none;
        }

        .ot-appt-breadcrumb a:hover {
            color: var(--ot-secondary);
        }

        .ot-appt-breadcrumb-sep {
            color: #c3c9d3;
        }

        .ot-appt-breadcrumb-current {
            color: #4a5568;
            font-weight: 600;
        }

        .ot-appt-form-page .btn,
        .ot-appt-form-page .hms-btn {
            border-radius: 12px;
            font-weight: 800;
            transition: transform 170ms ease, box-shadow 170ms ease;
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
        }

        .ot-card-header {
            background: linear-gradient(135deg, rgba(235, 245, 251, 0.92), rgba(255, 255, 255, 0.94));
            border-bottom: 1px solid var(--ot-s2-12);
            padding: 1.15rem 1.25rem;
        }

        .ot-title-wrap {
            display: flex;
            align-items: center;
            gap: .85rem;
        }

        .ot-title-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: var(--ot-secondary);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 14px 30px rgba(27, 79, 114, 0.22);
        }

        .ot-title {
            font-weight: 900;
            margin: 0;
            color: var(--ot-secondary);
        }

        .ot-subtitle {
            margin: .15rem 0 0;
            font-weight: 650;
            color: rgba(27, 79, 114, 0.72);
            font-size: .85rem;
        }

        .ot-alert {
            border-radius: 14px;
            font-weight: 650;
        }

        .ot-appt-form-page .form-label {
            font-weight: 800;
            font-size: .82rem;
            color: var(--ot-secondary);
        }

        .ot-appt-form-page .form-control,
        .ot-appt-form-page .form-select {
            border: 1px solid var(--ot-s2-18);
            border-radius: 12px;
            padding: .55rem .85rem;
            font-weight: 600;
            color: var(--ot-secondary);
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

        .ot-actions {
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--ot-s2-12);
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var dateEl = document.getElementById('appointment_date');
            if (typeof flatpickr !== 'undefined' && dateEl) {
                flatpickr(dateEl, {
                    dateFormat: 'Y-m-d',
                    minDate: 'today',
                    onChange: function () {
                        dateEl.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
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
                if (locationEl) locationEl.addEventListener('change', syncDistrictState);
            }
            syncDistrictState();
        });
    </script>
@endpush