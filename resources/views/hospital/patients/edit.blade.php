@extends('hospital.layouts.app')
@section('title', 'Edit Patient')
@section('page-header', '')

@section('page-actions')
@endsection

@section('content')

        <div id="contactToast" style="display:none;position:fixed;top:1.25rem;right:1.25rem;z-index:9999;
                                       background:#1B4F72;color:#fff;padding:.75rem 1.25rem;border-radius:.5rem;
                                       box-shadow:0 4px 12px rgba(0,0,0,.2);font-size:.9rem;max-width:320px">
            <i class="fa-solid fa-circle-check" style="margin-right:.4rem"></i>
            <span id="contactToastMsg"></span>
        </div>

        @if($patient->primary_done_at)
            <div class="alert alert-warning alert-dismissible fade show border-0 mb-3"
                 style="background:#FDEBD0;color:#784212;border-left:4px solid #E67E22" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <strong>Examination Completed:</strong> Primary examination has already been done for this patient. Changes will not affect examination data.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="hms-card border-0 shadow-lg" style="border-radius:16px">
            <div class="hms-card-header"
                style="background:linear-gradient(135deg, #1B4F72 0%, #2980B9 100%);padding:1.75rem;border-radius:16px 16px 0 0">
                <div style="display:flex;align-items:center;gap:1rem;color:#fff">
                    <div style="width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div>
                        <h4 style="margin:0;font-weight:700;font-size:1.25rem;color:#fff">Edit Patient</h4>
                        <p style="margin:0.25rem 0 0;font-size:0.9rem;opacity:0.9">Update patient information and appointment details</p>
                    </div>
                </div>
            </div>
            <div class="hms-card-body" style="padding:2rem">
                <form method="POST"
                      action="{{ route('hospital.patients.update', ['slug' => $slug, 'patient' => $patient->id]) }}"
                      class="patient-create-form">
                    @csrf @method('PUT')

                    {{-- MRD Number --}}
                    <div class="hms-card-body patient-create-card-body" style="padding-bottom:0">
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem">
                            <div class="form-group">
                                <label class="form-label">MRD No.</label>
                                <input type="text" value="{{ $patient->patient_code }}" class="form-control hms-input" readonly
                                       style="background:#eef2f6">
                            </div>
                        </div>
                    </div>

                    <div class="hms-card-body patient-create-card-body">
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem">

                            {{-- Appointment Date --}}
                            <div class="form-group">
                                <label class="form-label">Appointment Date <span style="color:#e74c3c">*</span></label>
                                <input type="text" name="appointment_date" class="form-control flatpickr hms-input @error('appointment_date') is-invalid @enderror"
                                       value="{{ old('appointment_date', $patient->appointment_date?->format('Y-m-d')) }}" required>
                                @error('appointment_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            {{-- Contact Number --}}
                            <div class="form-group">
                                <label class="form-label">Contact Number <span style="color:#e74c3c">*</span></label>
                                <input type="text" name="contact_no" id="contactNo" class="form-control hms-input @error('contact_no') is-invalid @enderror"
                                       data-intl-phone required value="{{ old('contact_no', $patient->contact_no) }}">
                                @error('contact_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            {{-- WhatsApp No --}}
                            <div class="form-group">
                                <label class="form-label">WhatsApp No</label>
                                <input type="text" name="whatsapp_no" id="whatsappNo" class="form-control hms-input"
                                       data-intl-phone placeholder="Same if blank" value="{{ old('whatsapp_no', $patient->whatsapp_no) }}">
                            </div>

                            {{-- First Name --}}
                            <div class="form-group">
                                <label class="form-label">First Name <span style="color:#e74c3c">*</span></label>
                                <input type="text" name="first_name" id="firstName" class="form-control hms-input @error('first_name') is-invalid @enderror"
                                       value="{{ old('first_name', $patient->first_name) }}" required>
                                @error('first_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            {{-- Surname --}}
                            <div class="form-group">
                                <label class="form-label">Surname <span style="color:#e74c3c">*</span></label>
                                <input type="text" name="last_name" id="lastName" class="form-control hms-input @error('last_name') is-invalid @enderror"
                                       value="{{ old('last_name', $patient->last_name) }}" required>
                                @error('last_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            {{-- Middle Name --}}
                            <div class="form-group">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" id="middleName" class="form-control hms-input"
                                       value="{{ old('middle_name', $patient->middle_name) }}">
                            </div>

                            {{-- Case Type --}}
                            <div class="form-group">
                                <label class="form-label">Case Type <span style="color:#e74c3c">*</span></label>
                                <select name="case_id" id="caseSelect" class="form-control select2 hms-select @error('case_id') is-invalid @enderror" required>
                                    <option value="">Select Case</option>
                                    @foreach($cases as $c)
                                        <option value="{{ $c->id }}" data-fee="{{ $c->fee ?? 0 }}"
                                                @selected(old('case_id', $patient->case_id) == $c->id)>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                                @error('case_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            {{-- Case Fee --}}
                            <div class="form-group">
                                <label class="form-label">Case Fee ({{ currency_symbol() }}) <span style="color:#e74c3c">*</span></label>
                                <input type="number" name="case_fee" id="caseFee"
                                       value="{{ old('case_fee', $patient->case_fee) }}"
                                       class="form-control hms-input @error('case_fee') is-invalid @enderror" required>
                                @error('case_fee')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            {{-- Doctor Name --}}
                            <div class="form-group">
                                <label class="form-label">Doctor Name <span style="color:#e74c3c">*</span></label>
                                <select name="doctor_id" class="form-control select2 hms-select @error('doctor_id') is-invalid @enderror" required>
                                    <option value="">Select Doctor</option>
                                    @foreach($doctors as $doc)
                                        <option value="{{ $doc->id }}" @selected(old('doctor_id', $patient->doctor_id) == $doc->id)>
                                            {{ $doc->name }}{{ $doc->doctor_type ? ' (' . ucfirst($doc->doctor_type) . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('doctor_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            {{-- City --}}
                            <div class="form-group">
                                <label class="form-label">City <span style="color:#e74c3c">*</span></label>
                                <div style="display:flex;gap:5px">
                                    <select name="location_id" id="locationSelect" class="form-control select2 hms-select @error('location_id') is-invalid @enderror" required>
                                        <option value="">Select City</option>
                                        @foreach($locations as $loc)
                                            <option value="{{ $loc->id }}" data-district="{{ $loc->district->name ?? '' }}"
                                                data-state="{{ $loc->state->name ?? '' }}" @selected(old('location_id', $patient->location_id) == $loc->id)>

                                                {{ $loc->name }}

                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" id="btnAddLocation" class="hms-btn hms-btn-outline"
                                            style="width:30px;height:30px">+</button>
                                </div>
                                @error('location_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            {{-- District --}}
                            <div class="form-group">
                                <label class="form-label">District</label>
                                <input type="text" id="district" class="form-control hms-input" readonly placeholder="Auto-filled">
                            </div>

                            {{-- State --}}
                            <div class="form-group">
                                <label class="form-label">State</label>
                                <input type="text" id="state" class="form-control hms-input" readonly placeholder="Auto-filled">
                            </div>

                            {{-- Age --}}
                            <div class="form-group">
                                <label class="form-label">Age <span style="color:#e74c3c">*</span></label>
                                <input type="number" name="age" id="age"
                                       value="{{ old('age', $patient->age) }}" min="0" max="150"
                                       class="form-control hms-input @error('age') is-invalid @enderror" required>
                                @error('age')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            {{-- Gender --}}
                            <div class="form-group">
                                <label class="form-label">Gender <span style="color:#e74c3c">*</span></label>
                                <select name="gender" id="gender" class="form-control hms-select @error('gender') is-invalid @enderror" required>
                                    <option value="">Select Gender</option>
                                    <option value="male"   @selected(old('gender', $patient->gender) === 'male')>Male</option>
                                    <option value="female" @selected(old('gender', $patient->gender) === 'female')>Female</option>
                                    <option value="other"  @selected(old('gender', $patient->gender) === 'other')>Other</option>
                                </select>
                                @error('gender')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            {{-- Occupation --}}
                            <div class="form-group">
                                <label class="form-label">Occupation</label>
                                <input type="text" name="occupation" id="occupation" class="form-control hms-input"
                                       value="{{ old('occupation', $patient->occupation) }}">
                            </div>

                            {{-- Referred By --}}
                            <div class="form-group">
                                <label class="form-label">Referred By</label>
                                <select name="referrer_id" class="form-control select2 hms-select">
                                    <option value="">Select Referrer</option>
                                    @foreach($referrers as $r)
                                        <option value="{{ $r->id }}" @selected(old('referrer_id', $patient->referrer_id) == $r->id)>{{ $r->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>

                        {{-- Action Buttons --}}
                        <div style="display:flex;gap:0.875rem;margin-top:2.5rem;padding-top:1.75rem;border-top:1px solid #E2E8F0">
                            <button type="submit" class="hms-btn hms-btn-primary">
                                <i class="bi bi-check-circle-fill"></i> Update Patient
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof $.fn.select2 !== 'undefined') {
                $('.select2').select2({ width: '100%' });
            }
            if (typeof flatpickr !== 'undefined') {
                flatpickr('.flatpickr', { dateFormat: 'Y-m-d' });
            }

            function showToast(msg, isError) {
                var toast = document.getElementById('contactToast');
                document.getElementById('contactToastMsg').textContent = msg;
                toast.style.background = isError ? '#C0392B' : '#1B4F72';
                toast.style.display = 'block';
                setTimeout(function () { toast.style.display = 'none'; }, 3500);
            }

            // International phone inputs
            if (window.HmsIntlPhone) {
                HmsIntlPhone.bind(document.getElementById('contactNo'));
                HmsIntlPhone.bind(document.getElementById('whatsappNo'));
            }

            // Case type → fee
            var caseSelect = document.getElementById('caseSelect');
            var caseFeeEl  = document.getElementById('caseFee');
            if (caseSelect && caseFeeEl) {
                $(caseSelect).on('change', function () {
                    var opt = this.options[this.selectedIndex];
                    caseFeeEl.value = opt ? (opt.dataset.fee || 0) : 0;
                });
            }

            // Location → district / state
            var locationEl  = document.getElementById('locationSelect');
            var districtEl  = document.getElementById('district');
            var stateEl     = document.getElementById('state');
            function syncLocation() {
                if (!locationEl || !districtEl || !stateEl) { return; }
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

            // Add City modal
            var addBtn = document.getElementById('btnAddLocation');
            if (addBtn) {
                var modalHtml = '<div class="modal fade" id="modalAddLocation" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" style="color:#fff;">Add City</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><div id="addLocErrors" class="text-danger mb-2"></div><div class="mb-2"><label class="form-label">City</label><input type="text" id="newCity" class="form-control" placeholder="City name"></div><div class="mb-2"><label class="form-label">District</label><input type="text" id="newDistrict" class="form-control" placeholder="District"></div><div class="mb-2"><label class="form-label">State</label><input type="text" id="newState" class="form-control" placeholder="State"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" id="saveLocationBtn" class="btn btn-primary">Add</button></div></div></div></div>';
                document.body.insertAdjacentHTML('beforeend', modalHtml);

                var modalEl      = document.getElementById('modalAddLocation');
                var saveBtn      = document.getElementById('saveLocationBtn');
                var addLocErrors = document.getElementById('addLocErrors');

                addBtn.addEventListener('click', function () {
                    addLocErrors.innerHTML = '';
                    document.getElementById('newCity').value     = '';
                    document.getElementById('newDistrict').value = '';
                    document.getElementById('newState').value    = '';
                    new bootstrap.Modal(modalEl).show();
                });

                if (saveBtn) {
                    saveBtn.addEventListener('click', function () {
                        addLocErrors.innerHTML = '';
                        var city     = document.getElementById('newCity').value.trim();
                        var district = document.getElementById('newDistrict').value.trim();
                        var state    = document.getElementById('newState').value.trim();
                        if (!city) { addLocErrors.textContent = 'City is required.'; return; }

                        fetch('{{ route("hospital.masters.basic.ajax.store", ["slug" => $slug, "type" => "locations"]) }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({ city: city, district: district, state: state })
                        })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (!data || !data.success) { addLocErrors.textContent = (data?.message) || 'Failed to add city.'; return; }
                            var sel = document.getElementById('locationSelect');
                            var opt = document.createElement('option');
                            opt.value = data.id; opt.text = city;
                            opt.setAttribute('data-district', district);
                            opt.setAttribute('data-state', state);
                            sel.appendChild(opt);
                            if (typeof $ !== 'undefined') { $(sel).val(data.id).trigger('change'); }
                            else { sel.value = data.id; sel.dispatchEvent(new Event('change')); }
                            bootstrap.Modal.getInstance(modalEl)?.hide();
                            showToast('City added.');
                        })
                        .catch(function () { addLocErrors.textContent = 'Network error.'; });
                    });
                }
            }
        });
    </script>
@endpush

@push('styles')
    <style>
        .patient-suggestion-card {
            padding: 12px;
            border-bottom: 1px solid #E2E8F0;
            transition: background 0.2s;
        }
        .patient-suggestion-card:hover { background-color: #F8FAFC; cursor: pointer; }
        .patient-suggestion-card:last-child { border-bottom: none; }

        .patient-create-form { display: grid; gap: 1rem; }

        .patient-create-card-body {
            padding: 1.35rem !important;
            background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(246,250,253,.96));
        }

        .patient-create-page .form-label,
        .form-label {
            display: block;
            margin-bottom: .48rem;
            color: rgba(27,79,114,.8);
            font-size: .78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .form-group { margin-bottom: 0; }
        .form-group:focus-within .form-label { color: #1B4F72; }

        .hms-input, .hms-select,
        select.form-control, input.form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px !important;
            background: #ffffff;
            transition: all 0.2s ease;
            color: #1a2a3a;
        }
        .hms-input:hover, select.form-control:hover, input.form-control:hover { border-color: #b8c5d0; }
        .hms-input:focus, select.form-control:focus, input.form-control:focus {
            outline: none; border-color: #1B4F72;
            box-shadow: 0 0 0 4px rgba(27,79,114,0.1);
        }
        .hms-input[readonly], input.form-control[readonly] {
            background: #f4f7fb !important; border-color: #e2e8f0 !important;
            color: #4a6276 !important; cursor: default;
        }

        .select2-container--default .select2-selection--single {
            height: auto !important; padding: 0.5rem 1rem;
            border: 1.5px solid #e2e8f0 !important; border-radius: 14px !important;
            background: #ffffff !important; transition: all 0.2s ease;
        }
        .select2-container--default .select2-selection--single:hover { border-color: #b8c5d0 !important; }
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #1B4F72 !important; box-shadow: 0 0 0 3px rgba(27,79,114,0.1);
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.4 !important; padding: 0 !important; color: #1a2a3a !important; font-size: 0.95rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 100% !important; right: 12px !important; }
        .select2-dropdown {
            border: 1px solid #e2e8f0 !important; border-radius: 12px !important;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); overflow: hidden; background: #ffffff !important;
        }
        .select2-results__option { padding: 0.65rem 1rem !important; color: #1a2a3a !important; background: #ffffff !important; }
        .select2-container--default .select2-results__option--highlighted[aria-selected] { background: #eef4fb !important; color: #1B4F72 !important; }

        .hms-btn.hms-btn-primary {
            background: linear-gradient(135deg, #1B4F72 0%, #2471A3 100%) !important;
            color: #ffffff !important;
            border: none !important; padding: 0.875rem 2rem !important;
            font-weight: 700 !important; font-size: 0.9rem !important;
            border-radius: 40px !important; transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(27,79,114,0.2);
        }
        .hms-btn.hms-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(27,79,114,0.25); }

        .hms-btn.hms-btn-outline, a.hms-btn-outline {
            background: transparent !important; border: 1.5px solid #cbd5e1 !important;
            color: #4a6276 !important; padding: 0.875rem 1.75rem !important;
            font-weight: 600 !important; border-radius: 40px !important; transition: all 0.2s ease;
        }
        .hms-btn.hms-btn-outline:hover, a.hms-btn-outline:hover {
            border-color: #1B4F72 !important; color: #1B4F72 !important;
            background: rgba(27,79,114,0.04) !important; transform: translateY(-1px);
        }

        #btnAddLocation {
            width: 20px !important; height: 35px !important; border-radius: 12px !important;
            background: #f0f4f9 !important; border: 1.5px solid #e2e8f0 !important;
            color: #1B4F72 !important; font-size: 1.2rem; font-weight: bold;
            display: flex; align-items: center; justify-content: center; cursor: pointer;
        }
        #btnAddLocation:hover { background: #1B4F72 !important; color: white !important; border-color: #1B4F72 !important; }

        #contactToast { border-radius: 60px !important; font-weight: 500; }

        .modal-content { border-radius: 24px !important; border: none !important; overflow: hidden; }
        .modal-header {
            background: linear-gradient(135deg, #1B4F72 0%, #2471A3 100%);
            color: white; border-bottom: none; padding: 1.25rem 1.5rem;
        }
        .modal-header .btn-close { filter: brightness(0) invert(1); opacity: 0.8; }
        .modal-footer { border-top: 1px solid #eef2f6; padding: 1rem 1.5rem; }
        .modal-footer .btn-primary {
            background: linear-gradient(135deg, #1B4F72 0%, #2471A3 100%);
            border: none; border-radius: 40px; padding: 0.5rem 1.5rem;
        }
        .modal-footer .btn-secondary {
            background: #f1f5f9; border: none; color: #475569; border-radius: 40px; padding: 0.5rem 1.5rem;
        }

        .flatpickr-calendar { border-radius: 20px !important; box-shadow: 0 20px 35px -10px rgba(0,0,0,0.2) !important; border: 1px solid #e2e8f0 !important; }
        .flatpickr-day.selected { background: #1B4F72 !important; border-color: #1B4F72 !important; }

        @media (max-width: 768px) {
            .hms-card-body { padding: 1.25rem !important; }
        }
    </style>
@endpush
