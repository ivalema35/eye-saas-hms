@php
    $appointment = $appointment ?? null;
    $appointmentIdDisplay = $appointment
        ? $appointment->appointment_number
        : ($nextAppointmentNumber ?? 'Auto on save');
@endphp

<style>
    .ot-slot-occupancy {
        margin-top: .5rem;
        border: 1px solid rgba(27, 79, 114, .15);
        border-radius: 10px;
        background: rgba(235, 245, 251, .55);
        padding: .5rem .6rem;
    }

    .ot-slot-occupancy-title {
        font-size: .74rem;
        font-weight: 700;
        color: #1B4F72;
        margin-bottom: .35rem;
    }

    .ot-slot-occupancy-chips {
        display: flex;
        flex-wrap: wrap;
        gap: .3rem;
        max-height: 108px;
        overflow-y: auto;
    }

    .ot-slot-chip {
        display: inline-flex;
        align-items: center;
        max-width: 100%;
        padding: .18rem .55rem;
        border-radius: 999px;
        background: #fff;
        border: 1px solid rgba(27, 79, 114, .18);
        color: #1B4F72;
        font-size: .74rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">Appointment ID</label>
        <input type="text" class="form-control" value="{{ $appointmentIdDisplay }}" readonly
            style="background:#f8fafc; font-weight:600; letter-spacing:.02em;"
            title="Auto-generated appointment number">
        <!-- @unless($appointment)
            <div class="form-text">Auto-filled — confirmed on save.</div>
        @endunless -->
    </div>
    <div class="col-md-3">
        <label class="form-label">Appointment Type <span class="text-danger">*</span></label>
        <select name="appointment_type" class="form-select" required>
            @foreach(['phone' => 'Phone', 'walk_in' => 'Walk-in', 'online' => 'Online', 'referral' => 'Referral'] as $value => $label)
                <option value="{{ $value }}" {{ old('appointment_type', $appointment->appointment_type ?? 'phone') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Appointment Date <span class="text-danger">*</span></label>
        <input type="text" name="appointment_date" id="appointment_date" class="form-control"
            value="{{ old('appointment_date', optional($appointment?->appointment_date)->format('Y-m-d') ?? ($doctorLoadDate ?? now()->toDateString())) }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Appointment Time</label>
        <select name="appointment_time" id="appointment_time" class="form-select">
            <option value="">Select slot...</option>
            @foreach($slots as $slot)
                @php $slotValue = \Carbon\Carbon::parse($slot->start_time)->format('H:i'); @endphp
                <option value="{{ $slotValue }}" {{ old('appointment_time', $appointment->appointment_time ?? '') === $slotValue ? 'selected' : '' }}>
                    {{ $slot->slot_name }}
                    @if($slot->start_time && $slot->end_time)
                        ({{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }} -
                        {{ \Carbon\Carbon::parse($slot->end_time)->format('h:i A') }})
                    @endif
                </option>
            @endforeach
        </select>
        <div id="slotAppointmentsList" class="ot-slot-occupancy d-none">
            <div id="slotAppointmentsTitle" class="ot-slot-occupancy-title"></div>
            <div id="slotAppointmentsChips" class="ot-slot-occupancy-chips"></div>
        </div>
    </div>

    <div class="col-md-3">
        <label class="form-label">Contact Number <span class="text-danger">*</span></label>
        <input type="text" name="mobile_no" class="form-control @error('mobile_no') is-invalid @enderror"
            value="{{ old('mobile_no', $appointment->mobile_no ?? '') }}"
            data-intl-phone required placeholder="+919876543210">
        @error('mobile_no')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">WhatsApp Number</label>
        <input type="text" name="whatsapp_no" class="form-control @error('whatsapp_no') is-invalid @enderror"
            value="{{ old('whatsapp_no', $appointment->whatsapp_no ?? '') }}"
            data-intl-phone placeholder="Same if blank">
        @error('whatsapp_no')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">First Name <span class="text-danger">*</span></label>
        <input type="text" name="patient_name" class="form-control"
            value="{{ old('patient_name', $appointment->patient_name ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Surname <span class="text-danger">*</span></label>
        <input type="text" name="surname" class="form-control" value="{{ old('surname', $appointment->surname ?? '') }}"
            required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Middle Name</label>
        <input type="text" name="middle_name" class="form-control"
            value="{{ old('middle_name', $appointment->middle_name ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Doctor Name <span class="text-danger">*</span></label>
        <select name="doctor_id" id="doctor_id" class="form-select" required>
            <option value="">Select doctor...</option>
            @foreach($doctors as $doctor)
                <option value="{{ $doctor->id }}" {{ (string) old('doctor_id', $appointment->doctor_id ?? '') === (string) $doctor->id ? 'selected' : '' }}>
                    Dr. {{ $doctor->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">City <span class="text-danger">*</span></label>
        <div style="display:flex;gap:5px">
            <select name="location_id" id="location_id" class="form-select" required>
                <option value="">Select city...</option>
                @foreach($locations as $loc)
                    <option value="{{ $loc->id }}" data-district="{{ $loc->district?->name }}"
                        data-state="{{ $loc->state?->name }}" {{ (string) old('location_id', $appointment->location_id ?? '') === (string) $loc->id ? 'selected' : '' }}>
                        {{ $loc->name }}
                    </option>
                @endforeach
            </select>
            <button type="button" id="btnAddOtLocation" class="hms-btn hms-btn-outline"
                style="width:40px;flex-shrink:0">+</button>
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">District</label>
        <input type="text" id="district" class="form-control" readonly placeholder="Auto-filled"
            style="background:#f8fafc;">
    </div>
    <div class="col-md-4">
        <label class="form-label">State</label>
        <input type="text" id="state" class="form-control" readonly placeholder="Auto-filled"
            style="background:#f8fafc;">
    </div>

    <div class="col-md-3">
        <label class="form-label">Age <span class="text-danger">*</span></label>
        <input type="number" min="0" max="150" name="age" class="form-control"
            value="{{ old('age', $appointment->age ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Gender <span class="text-danger">*</span></label>
        <select name="gender" class="form-select" required>
            <option value="">Select...</option>
            @foreach(['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label)
                <option value="{{ $value }}" {{ old('gender', $appointment->gender ?? '') === $value ? 'selected' : '' }}>
                    {{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Occupation</label>
        <input type="text" name="occupation" class="form-control"
            value="{{ old('occupation', $appointment->occupation ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Referred By</label>
        <select name="referrer_id" class="form-select">
            <option value="">Select Referrer</option>
            @foreach($referrers as $referrer)
                <option value="{{ $referrer->id }}" {{ (string) old('referrer_id', $appointment->referrer_id ?? '') === (string) $referrer->id ? 'selected' : '' }}>
                    {{ $referrer->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var addBtn = document.getElementById('btnAddOtLocation');
            if (!addBtn) return;

            var modalHtml = '\n<div class="modal fade" id="modalAddOtLocation" tabindex="-1" aria-hidden="true">\n  <div class="modal-dialog modal-sm modal-dialog-centered">\n    <div class="modal-content">\n      <div class="modal-header">\n        <h5 class="modal-title" style="color:#fff">Add City</h5>\n        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>\n      </div>\n      <div class="modal-body">\n        <div id="addOtLocErrors" class="text-danger mb-2"></div>\n        <div class="mb-2">\n          <label class="form-label">City</label>\n          <input type="text" id="newOtCity" class="form-control" placeholder="City name">\n        </div>\n        <div class="mb-2">\n          <label class="form-label">District</label>\n          <input type="text" id="newOtDistrict" class="form-control" placeholder="District">\n        </div>\n        <div class="mb-2">\n          <label class="form-label">State</label>\n          <input type="text" id="newOtState" class="form-control" placeholder="State">\n        </div>\n      </div>\n      <div class="modal-footer">\n        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>\n        <button type="button" id="saveOtLocationBtn" class="btn btn-primary">Add</button>\n      </div>\n    </div>\n  </div>\n</div>\n';

            document.body.insertAdjacentHTML('beforeend', modalHtml);

            var modalEl = document.getElementById('modalAddOtLocation');
            var saveBtn = document.getElementById('saveOtLocationBtn');
            var newCity = document.getElementById('newOtCity');
            var newDistrict = document.getElementById('newOtDistrict');
            var newState = document.getElementById('newOtState');
            var addLocErrors = document.getElementById('addOtLocErrors');

            addBtn.addEventListener('click', function () {
                var modal = new bootstrap.Modal(modalEl);
                addLocErrors.innerHTML = '';
                newCity.value = '';
                newDistrict.value = '';
                newState.value = '';
                modal.show();
            });

            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    addLocErrors.innerHTML = '';
                    var city = newCity.value.trim();
                    var district = newDistrict.value.trim();
                    var state = newState.value.trim();
                    if (!city) { addLocErrors.textContent = 'City is required.'; return; }
                    if (!state) { addLocErrors.textContent = 'State is required.'; return; }

                    var url = '{{ route("hospital.masters.basic.ajax.store", ["slug" => $slug, "type" => "locations"]) }}';
                    var token = '{{ csrf_token() }}';

                    fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ city: city, district: district, state: state })
                    })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (!data || !data.success) {
                                addLocErrors.textContent = (data && data.message) || 'Failed to add city.';
                                return;
                            }

                            var sel = document.getElementById('location_id');
                            var opt = document.createElement('option');
                            opt.value = data.id;
                            opt.text = city;
                            opt.setAttribute('data-district', district);
                            opt.setAttribute('data-state', state);
                            sel.appendChild(opt);
                            if (typeof $ !== 'undefined') { $(sel).val(data.id).trigger('change'); }
                            else { sel.value = data.id; sel.dispatchEvent(new Event('change')); }

                            var modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        })
                        .catch(function () { addLocErrors.textContent = 'Network error.'; });
                });
            }
        });
    </script>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var timeEl = document.getElementById('appointment_time');
            var dateEl = document.getElementById('appointment_date');
            var listEl = document.getElementById('slotAppointmentsList');
            var titleEl = document.getElementById('slotAppointmentsTitle');
            var chipsEl = document.getElementById('slotAppointmentsChips');
            if (!timeEl || !dateEl || !listEl || !titleEl || !chipsEl) return;

            var slotAppointmentsUrl = '{{ route("hospital.ot.appointments.slot-appointments", ["slug" => $slug]) }}';
            var excludeId = {{ $appointment->id ?? 'null' }};

            var hideSlotAppointments = function () {
                listEl.classList.add('d-none');
                titleEl.textContent = '';
                chipsEl.innerHTML = '';
            };

            var loadSlotAppointments = function () {
                var date = dateEl.value.trim();
                var time = timeEl.value.trim();

                if (!date || !time) { hideSlotAppointments(); return; }

                var url = slotAppointmentsUrl + '?date=' + encodeURIComponent(date) + '&time=' + encodeURIComponent(time);
                if (excludeId) { url += '&exclude_id=' + excludeId; }

                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        var appointments = (data && data.appointments) || [];
                        if (appointments.length === 0) { hideSlotAppointments(); return; }

                        titleEl.textContent = appointments.length + ' patient' + (appointments.length > 1 ? 's' : '') + ' already booked in this slot:';
                        chipsEl.innerHTML = '';
                        appointments.forEach(function (a) {
                            var chip = document.createElement('span');
                            chip.className = 'ot-slot-chip';
                            chip.title = a.name;
                            chip.textContent = a.name;
                            chipsEl.appendChild(chip);
                        });
                        listEl.classList.remove('d-none');
                    })
                    .catch(hideSlotAppointments);
            };

            timeEl.addEventListener('change', loadSlotAppointments);
            dateEl.addEventListener('change', loadSlotAppointments);

            if (dateEl.value && timeEl.value) { loadSlotAppointments(); }
        });
    </script>
@endpush