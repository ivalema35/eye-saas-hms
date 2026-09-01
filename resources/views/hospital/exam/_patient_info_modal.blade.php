{{-- Patient Info Modal (editable personal details) --}}
<div class="modal fade d-print-none" id="patientInfoModalExam" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content"
            style="border-radius:14px; overflow:hidden; border:none; box-shadow:0 20px 60px rgba(0,0,0,.18);">

            {{-- Header --}}
            <div class="modal-header text-white py-3 px-4" style="background:#1B4F72;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-badge-fill fs-5"></i>
                    <div>
                        <div class="fw-bold fs-6" id="pipModalPatientName">{{ $patient->full_name }}</div>
                        <small style="opacity:.8;">MRD: {{ $patient->patient_code ?? '—' }}</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body p-4" style="background:#f8fafc;">
                <div class="row g-3">

                    {{-- Personal Info (editable) --}}
                    <div class="col-12">
                        <div class="fw-bold mb-2"
                            style="color:#1B4F72; font-size:13px; text-transform:uppercase; letter-spacing:.5px; border-bottom:2px solid #e2e8f0; padding-bottom:6px;">
                            <i class="bi bi-person-fill me-1"></i> Personal Information
                        </div>
                        <form id="patientInfoPersonalForm" class="row g-2"
                            data-update-url="{{ route('hospital.patients.quick-personal', ['slug' => $slug, 'patient' => $patient->id]) }}">
                            <div class="col-12 col-md-6">
                                <label class="form-label text-muted mb-1" style="font-size:11px;">Full Name</label>
                                <input type="text" name="full_name" id="pipFullName"
                                    class="form-control form-control-sm bg-white"
                                    value="{{ $patient->full_name }}" required maxlength="150">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label text-muted mb-1" style="font-size:11px;">Age</label>
                                <input type="number" name="age" id="pipAge"
                                    class="form-control form-control-sm bg-white"
                                    value="{{ $patient->age }}" min="0" max="150" required>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label text-muted mb-1" style="font-size:11px;">Gender</label>
                                <select name="gender" id="pipGender" class="form-select form-select-sm bg-white" required>
                                    <option value="">Select</option>
                                    <option value="male" @selected($patient->gender === 'male')>Male</option>
                                    <option value="female" @selected($patient->gender === 'female')>Female</option>
                                    <option value="other" @selected($patient->gender === 'other')>Other</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label text-muted mb-1" style="font-size:11px;">Mobile Number</label>
                                <input type="text" name="contact_no" id="pipContact"
                                    class="form-control form-control-sm bg-white"
                                    value="{{ $patient->contact_no }}" required maxlength="15"
                                    pattern="\+?[0-9]{7,15}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label text-muted mb-1" style="font-size:11px;">City</label>
                                <select name="location_id" id="pipCity" class="form-select form-select-sm bg-white" required>
                                    <option value="">Select City</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}"
                                            data-district="{{ $loc->district->name ?? '' }}"
                                            data-state="{{ $loc->state->name ?? '' }}"
                                            @selected($patient->location_id == $loc->id)>
                                            {{ $loc->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label text-muted mb-1" style="font-size:11px;">District</label>
                                <input type="text" id="pipDistrict" class="form-control form-control-sm bg-light"
                                    value="{{ $patient->districtName ?: '—' }}" readonly tabindex="-1">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label text-muted mb-1" style="font-size:11px;">State</label>
                                <input type="text" id="pipState" class="form-control form-control-sm bg-light"
                                    value="{{ $patient->stateName ?: '—' }}" readonly tabindex="-1">
                            </div>
                        </form>
                        <div id="pipFormError" class="text-danger small mt-2 d-none"></div>
                    </div>

                    {{-- Appointment Info (read-only) --}}
                    <div class="col-12">
                        <div class="fw-bold mb-2"
                            style="color:#1B4F72; font-size:13px; text-transform:uppercase; letter-spacing:.5px; border-bottom:2px solid #e2e8f0; padding-bottom:6px;">
                            <i class="bi bi-calendar2-check-fill me-1"></i> Appointment Information
                        </div>
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <div class="p-2 bg-white rounded-3 border" style="font-size:13px;">
                                    <div class="text-muted" style="font-size:11px;">Appointment Date</div>
                                    <div class="fw-semibold">
                                        {{ $patient->appointment_date ? $patient->appointment_date->format('d M, Y') : '—' }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-2 bg-white rounded-3 border" style="font-size:13px;">
                                    <div class="text-muted" style="font-size:11px;">Type</div>
                                    <div class="fw-semibold">
                                        @if($patient->type === 'walkin')
                                            <span class="badge" style="background:#dcfce7; color:#166534;">Walk-in</span>
                                        @elseif($patient->type === 'phone')
                                            <span class="badge" style="background:#dbeafe; color:#1e40af;">Phone</span>
                                        @else
                                            {{ ucfirst($patient->type ?? '—') }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-2 bg-white rounded-3 border" style="font-size:13px;">
                                    <div class="text-muted" style="font-size:11px;">Patient Status</div>
                                    <div class="fw-semibold">
                                        @if($patient->is_old_patient)
                                            <span class="badge" style="background:#fef3c7; color:#92400e;">Old Patient</span>
                                        @else
                                            <span class="badge" style="background:#d1fae5; color:#065f46;">New Patient</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-2 bg-white rounded-3 border" style="font-size:13px;">
                                    <div class="text-muted" style="font-size:11px;">Doctor</div>
                                    <div class="fw-semibold">{{ $patient->doctor->name ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-2 bg-white rounded-3 border" style="font-size:13px;">
                                    <div class="text-muted" style="font-size:11px;">Case Type</div>
                                    <div class="fw-semibold">{{ $patient->caseType->case_type ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-2 bg-white rounded-3 border" style="font-size:13px;">
                                    <div class="text-muted" style="font-size:11px;">Case Fee</div>
                                    <div class="fw-semibold">{{ money($patient->case_fee ?? 0, 2) }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-2 bg-white rounded-3 border" style="font-size:13px;">
                                    <div class="text-muted" style="font-size:11px;">Receptionist</div>
                                    <div class="fw-semibold">{{ $patient->reception->name ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-2 bg-white rounded-3 border" style="font-size:13px;">
                                    <div class="text-muted" style="font-size:11px;">Referrer</div>
                                    <div class="fw-semibold">{{ $patient->referrer->name ?? '—' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer px-4 py-3" style="background:#fff; border-top:1px solid #e2e8f0;">
                <button type="button" class="btn btn-sm px-4" data-bs-dismiss="modal"
                    style="background:#e2e8f0; color:#475569; border-radius:8px; font-weight:600; border:none;">
                    Close
                </button>
                <button type="button" id="pipSaveBtn" class="btn btn-sm px-4 text-white fw-semibold"
                    style="background:#1B4F72; border-radius:8px; border:none;">
                    <i class="bi bi-check-lg me-1"></i> Save Changes
                </button>
            </div>

        </div>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('patientInfoPersonalForm');
    var saveBtn = document.getElementById('pipSaveBtn');
    var citySelect = document.getElementById('pipCity');
    var districtInput = document.getElementById('pipDistrict');
    var stateInput = document.getElementById('pipState');
    var modalName = document.getElementById('pipModalPatientName');
    var errorBox = document.getElementById('pipFormError');
    if (!form || !saveBtn) { return; }

    function syncLocationFields() {
        if (!citySelect || !districtInput || !stateInput) { return; }
        var opt = citySelect.options[citySelect.selectedIndex];
        districtInput.value = opt?.dataset?.district || '—';
        stateInput.value = opt?.dataset?.state || '—';
    }

    function showError(msg) {
        if (!errorBox) { return; }
        errorBox.textContent = msg;
        errorBox.classList.remove('d-none');
    }

    function clearError() {
        if (!errorBox) { return; }
        errorBox.textContent = '';
        errorBox.classList.add('d-none');
    }

    citySelect?.addEventListener('change', syncLocationFields);
    syncLocationFields();

    saveBtn.addEventListener('click', function () {
        clearError();
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        var payload = {
            full_name: document.getElementById('pipFullName')?.value.trim(),
            age: parseInt(document.getElementById('pipAge')?.value, 10),
            gender: document.getElementById('pipGender')?.value,
            contact_no: document.getElementById('pipContact')?.value.trim(),
            location_id: parseInt(citySelect?.value, 10),
        };

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving…';

        fetch(form.dataset.updateUrl, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        })
            .then(function (res) {
                if (!res.ok) {
                    return res.json().then(function (data) {
                        var msg = data.message || Object.values(data.errors || {}).flat().join(' ') || 'Could not save.';
                        return Promise.reject(new Error(msg));
                    });
                }
                return res.json();
            })
            .then(function (data) {
                if (modalName) { modalName.textContent = data.full_name; }
                document.getElementById('pipFullName').value = data.full_name;
                document.getElementById('pipAge').value = data.age;
                document.getElementById('pipGender').value = data.gender;
                document.getElementById('pipContact').value = data.contact_no;
                if (citySelect) { citySelect.value = String(data.location_id); }
                if (districtInput) { districtInput.value = data.district_name; }
                if (stateInput) { stateInput.value = data.state_name; }

                var nameDisplay = document.getElementById('patientNameDisplay');
                if (nameDisplay) { nameDisplay.textContent = data.full_name; }
                var nameInput = document.getElementById('patientNameInput');
                if (nameInput) { nameInput.value = data.full_name; }

                var toast = document.createElement('div');
                toast.textContent = 'Patient details updated.';
                toast.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:9999;background:#166534;color:#fff;padding:.6rem 1rem;border-radius:8px;font-size:13px;box-shadow:0 4px 12px rgba(0,0,0,.15)';
                document.body.appendChild(toast);
                setTimeout(function () { toast.remove(); }, 2500);
            })
            .catch(function (err) {
                showError(err.message || 'Could not save. Please try again.');
            })
            .finally(function () {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Changes';
            });
    });
})();
</script>
