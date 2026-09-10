@php
    $appointment = $appointment ?? null;
    $appointmentIdDisplay = $appointment
        ? $appointment->appointment_number
        : ($nextAppointmentNumber ?? 'Auto on save');
@endphp

{{-- Row 1 --}}
<div class="rpc-grid rpc-grid--4">
    <div class="rpc-field">
        <label class="form-label">Appointment ID</label>
        <input type="text" class="form-control hms-input" value="{{ $appointmentIdDisplay }}" readonly tabindex="-1"
            title="Auto-generated appointment number">
    </div>
    <div class="rpc-field">
        <label class="form-label">Appointment Type <span class="req">*</span></label>
        <select name="appointment_type" class="form-select hms-select rpc-auto-open" required>
            @foreach(['phone' => 'Phone', 'walk_in' => 'Walk-in', 'online' => 'Online', 'ot' => 'OT'] as $value => $label)
                <option value="{{ $value }}" {{ old('appointment_type', $appointment->appointment_type ?? 'ot') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="rpc-field">
        <label class="form-label">Appointment Date <span class="req">*</span></label>
        <input type="text" name="appointment_date" id="appointment_date" class="form-control flatpickr hms-input"
            value="{{ old('appointment_date', optional($appointment?->appointment_date)->format('Y-m-d') ?? ($doctorLoadDate ?? now()->toDateString())) }}"
            required>
    </div>
    <div class="rpc-field">
        <label class="form-label">Appointment Time</label>
        <select name="appointment_time" id="appointment_time" class="form-select hms-select rpc-auto-open">
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
</div>

{{-- Row 2 --}}
<div class="rpc-grid rpc-grid--4">
    <div class="rpc-field">
        <label class="form-label">Contact Number <span class="req">*</span></label>
        <input type="text" name="mobile_no" id="mobile_no"
            class="form-control hms-input @error('mobile_no') is-invalid @enderror"
            value="{{ old('mobile_no', $appointment->mobile_no ?? '') }}" data-intl-phone required
            placeholder="10-digit number">
        @error('mobile_no')
        <div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="rpc-field">
        <label class="form-label">WhatsApp Number</label>
        <input type="text" name="whatsapp_no" class="form-control hms-input @error('whatsapp_no') is-invalid @enderror"
            value="{{ old('whatsapp_no', $appointment->whatsapp_no ?? '') }}" data-intl-phone
            placeholder="Same if blank">
        @error('whatsapp_no')
        <div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="rpc-field">
        <label class="form-label">First Name <span class="req">*</span></label>
        <input type="text" name="patient_name" class="form-control hms-input"
            value="{{ old('patient_name', $appointment->patient_name ?? '') }}" required placeholder="First name">
    </div>
    <div class="rpc-field">
        <label class="form-label">Surname <span class="req">*</span></label>
        <input type="text" name="surname" class="form-control hms-input"
            value="{{ old('surname', $appointment->surname ?? '') }}" required placeholder="Surname">
    </div>
</div>

{{-- Row 3 --}}
<div class="rpc-grid rpc-grid--2">
    <div class="rpc-field">
        <label class="form-label">Middle Name</label>
        <input type="text" name="middle_name" class="form-control hms-input"
            value="{{ old('middle_name', $appointment->middle_name ?? '') }}" placeholder="Middle name">
    </div>
    <div class="rpc-field">
        <label class="form-label">Doctor Name <span class="req">*</span></label>
        <select name="doctor_id" id="doctor_id" class="form-control select2 hms-select rpc-auto-open" required>
            <option value="">Select doctor...</option>
            @foreach($doctors as $doctor)
                <option value="{{ $doctor->id }}" {{ (string) old('doctor_id', $appointment->doctor_id ?? '') === (string) $doctor->id ? 'selected' : '' }}>
                    Dr. {{ $doctor->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>

{{-- Row 4 --}}
<div class="rpc-grid rpc-grid--3">
    <div class="rpc-field">
        <label class="form-label">City <span class="req">*</span></label>
        <div class="rpc-city-row">
            <select name="location_id" id="location_id" class="form-control select2 hms-select rpc-auto-open" required>
                <option value="">Select city...</option>
                @foreach($locations as $loc)
                    <option value="{{ $loc->id }}" data-district="{{ $loc->district?->name }}"
                        data-state="{{ $loc->state?->name }}" {{ (string) old('location_id', $appointment->location_id ?? '') === (string) $loc->id ? 'selected' : '' }}>
                        {{ $loc->name }}
                    </option>
                @endforeach
            </select>
            <button type="button" id="btnAddOtLocation" class="hms-btn hms-btn-outline rpc-city-add">+</button>
        </div>
    </div>
    <div class="rpc-field">
        <label class="form-label">District</label>
        <input type="text" id="district" class="form-control hms-input" readonly placeholder="Auto-filled"
            tabindex="-1">
    </div>
    <div class="rpc-field">
        <label class="form-label">State</label>
        <input type="text" id="state" class="form-control hms-input" readonly placeholder="Auto-filled" tabindex="-1">
    </div>
</div>

{{-- Row 5 --}}
<div class="rpc-grid rpc-grid--4">
    <div class="rpc-field">
        <label class="form-label">Age <span class="req">*</span></label>
        <input type="number" min="0" max="150" name="age" class="form-control hms-input"
            value="{{ old('age', $appointment->age ?? '') }}" required placeholder="Age">
    </div>
    <div class="rpc-field">
        <label class="form-label">Gender <span class="req">*</span></label>
        <select name="gender" class="form-select hms-select rpc-auto-open" required>
            <option value="">Select...</option>
            @foreach(['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label)
                <option value="{{ $value }}" {{ old('gender', $appointment->gender ?? '') === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="rpc-field">
        <label class="form-label">Occupation</label>
        <input type="text" name="occupation" class="form-control hms-input"
            value="{{ old('occupation', $appointment->occupation ?? '') }}" placeholder="Occupation">
    </div>
    <div class="rpc-field">
        <label class="form-label">Referred By</label>
        <select name="referrer_id" class="form-control select2 hms-select rpc-auto-open">
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

            var modalHtml = '<div class="modal fade" id="modalAddOtLocation" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" style="color:#1b4f72">Add City</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div id="addOtLocErrors" class="text-danger mb-2"></div><div class="mb-2"><label class="form-label">City</label><input type="text" id="newOtCity" class="form-control"></div><div class="mb-2"><label class="form-label">District</label><input type="text" id="newOtDistrict" class="form-control"></div><div class="mb-2"><label class="form-label">State</label><input type="text" id="newOtState" class="form-control"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" id="saveOtLocationBtn" class="btn btn-primary">Add</button></div></div></div></div>';
            document.body.insertAdjacentHTML('beforeend', modalHtml);

            var modalEl = document.getElementById('modalAddOtLocation');
            addBtn.addEventListener('click', function () {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            });

            document.getElementById('saveOtLocationBtn').addEventListener('click', function () {
                var city = document.getElementById('newOtCity').value.trim();
                var district = document.getElementById('newOtDistrict').value.trim();
                var state = document.getElementById('newOtState').value.trim();
                var addLocErrors = document.getElementById('addOtLocErrors');
                addLocErrors.textContent = '';
                if (!city) { addLocErrors.textContent = 'City is required.'; return; }
                if (!state) { addLocErrors.textContent = 'State is required.'; return; }

                fetch('{{ route("hospital.masters.basic.ajax.store", ["slug" => $slug, "type" => "locations"]) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
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
                        bootstrap.Modal.getInstance(modalEl).hide();
                    })
                    .catch(function () { addLocErrors.textContent = 'Network error.'; });
            });
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