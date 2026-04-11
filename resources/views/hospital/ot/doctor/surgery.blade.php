@extends('hospital.layouts.app')
@section('title', 'Record OT Surgery')
@section('page-header', 'Record OT Surgery')

@section('page-actions')
    <a href="{{ route('hospital.ot.doctor.dashboard', ['slug' => $slug]) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 fw-bold" style="color: var(--color-primary);">
                    <i class="bi bi-heart-pulse-fill me-2"></i> Surgery Recording Form
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

                <h6 class="fw-bold text-primary mb-3">A. Patient & Booking Details</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted">Patient Name</label>
                        <input type="text" class="form-control" value="{{ $booking->patient?->full_name ?? '-' }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Phone</label>
                        <input type="text" class="form-control" value="{{ $booking->patient?->contact_no ?? '-' }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted">OT Date</label>
                        <input type="text" class="form-control" value="{{ optional($booking->surgery_date)->format('d M Y') }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted">Package</label>
                        <input type="text" class="form-control" value="INR {{ number_format((float) ($counselling?->package_amount ?? $booking->package_amount ?? 0), 2) }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted">Mediclaim</label>
                        <input type="text" class="form-control" value="{{ ($counselling?->mediclaim ?? $booking->has_mediclaim) ? 'YES' : 'NO' }}" readonly>
                    </div>
                </div>

                <form method="POST" action="{{ route('hospital.ot.surgery.store', ['slug' => $slug, 'bookingId' => $booking->id]) }}">
                    @csrf

                    <h6 class="fw-bold text-primary mb-3">B. Surgery Details</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Surgery Name <span class="text-danger">*</span></label>
                            <select name="surgery_name" class="form-select" required>
                                <option value="">Select surgery...</option>
                                @foreach($surgeryTypes as $surgeryType)
                                    <option value="{{ $surgeryType->surgery_name }}" {{ old('surgery_name', $booking->ot_type) === $surgeryType->surgery_name ? 'selected' : '' }}>
                                        {{ $surgeryType->surgery_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label d-block">Eye Operated <span class="text-danger">*</span></label>
                            @foreach(['RE', 'LE', 'Both'] as $eye)
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="eye_operated" id="eye_operated_{{ strtolower($eye) }}" value="{{ $eye }}" {{ old('eye_operated', $booking->eye) === $eye ? 'checked' : '' }}>
                                    <label class="form-check-label" for="eye_operated_{{ strtolower($eye) }}">{{ $eye }}</label>
                                </div>
                            @endforeach
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Complication Status <span class="text-danger">*</span></label>
                            <select name="complication_status" id="complication_status" class="form-select" required>
                                <option value="none" {{ old('complication_status', 'none') === 'none' ? 'selected' : '' }}>None</option>
                                <option value="minor" {{ old('complication_status') === 'minor' ? 'selected' : '' }}>Minor</option>
                                <option value="major" {{ old('complication_status') === 'major' ? 'selected' : '' }}>Major</option>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Complication Notes</label>
                            <textarea name="complication_notes" id="complication_notes" rows="2" class="form-control" placeholder="Only required if complication status is minor/major">{{ old('complication_notes') }}</textarea>
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mb-3">C. In-Ward Medicines</h6>
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <div id="wardMedicineList">
                                @php
                                    $oldWardMeds = old('ward_medicines', []);
                                    $oldWardMeds = is_array($oldWardMeds) ? $oldWardMeds : [];
                                @endphp

                                @forelse($oldWardMeds as $index => $row)
                                    <div class="row g-2 ward-medicine-row mb-2">
                                        <div class="col-md-6">
                                            <select name="ward_medicines[{{ $index }}][medicine]" class="form-select select2-medicine" required>
                                                <option value="">Select medicine...</option>
                                                @foreach($medicines as $med)
                                                    <option value="{{ $med->name }}" {{ ($row['medicine'] ?? '') === $med->name ? 'selected' : '' }}>{{ $med->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <input type="text" name="ward_medicines[{{ $index }}][dose]" class="form-control" placeholder="Dose / Frequency" value="{{ $row['dose'] ?? '' }}">
                                        </div>
                                        <div class="col-md-1 d-grid">
                                            <button type="button" class="btn btn-outline-danger remove-row">X</button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="row g-2 ward-medicine-row mb-2">
                                        <div class="col-md-6">
                                            <select name="ward_medicines[0][medicine]" class="form-select select2-medicine" required>
                                                <option value="">Select medicine...</option>
                                                @foreach($medicines as $med)
                                                    <option value="{{ $med->name }}">{{ $med->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <input type="text" name="ward_medicines[0][dose]" class="form-control" placeholder="Dose / Frequency">
                                        </div>
                                        <div class="col-md-1 d-grid">
                                            <button type="button" class="btn btn-outline-danger remove-row">X</button>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                            <button type="button" id="addWardMedicine" class="btn btn-sm btn-outline-primary mt-2">
                                <i class="bi bi-plus-circle me-1"></i> Add Medicine
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('hospital.ot.doctor.dashboard', ['slug' => $slug]) }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check2-circle me-1"></i> Save Surgery
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
    const list = document.getElementById('wardMedicineList');
    const addBtn = document.getElementById('addWardMedicine');
    const complicationStatus = document.getElementById('complication_status');
    const complicationNotes = document.getElementById('complication_notes');
    const medicineOptionsHtml = @json('<option value="">Select medicine...</option>' . $medicines->map(fn ($med) => '<option value="'.e($med->name).'">'.e($med->name).'</option>')->implode(''));

    function initMedicineSelects(scope) {
        if (window.jQuery && jQuery.fn.select2) {
            jQuery(scope).find('.select2-medicine').select2({
                width: '100%',
                placeholder: 'Select medicine...'
            });
        }
    }

    function attachRemoveHandlers() {
        list.querySelectorAll('.remove-row').forEach(function (btn) {
            btn.onclick = function () {
                const rows = list.querySelectorAll('.ward-medicine-row');
                if (rows.length === 1) {
                    rows[0].querySelectorAll('input, select').forEach(function (field) {
                        if (field.tagName === 'SELECT') {
                            field.selectedIndex = 0;
                            if (window.jQuery && jQuery.fn.select2) {
                                jQuery(field).trigger('change');
                            }
                        } else {
                            field.value = '';
                        }
                    });
                    return;
                }
                btn.closest('.ward-medicine-row').remove();
                reindexRows();
            };
        });
    }

    function reindexRows() {
        list.querySelectorAll('.ward-medicine-row').forEach(function (row, index) {
            const medicineInput = row.querySelector('select[name*="[medicine]"], input[name*="[medicine]"]');
            const doseInput = row.querySelector('input[name*="[dose]"]');
            medicineInput.name = `ward_medicines[${index}][medicine]`;
            doseInput.name = `ward_medicines[${index}][dose]`;
        });
    }

    addBtn.addEventListener('click', function () {
        const index = list.querySelectorAll('.ward-medicine-row').length;
        const row = document.createElement('div');
        row.className = 'row g-2 ward-medicine-row mb-2';
        row.innerHTML = `
            <div class="col-md-6">
                <select name="ward_medicines[${index}][medicine]" class="form-select select2-medicine" required>
                    ${medicineOptionsHtml}
                </select>
            </div>
            <div class="col-md-5">
                <input type="text" name="ward_medicines[${index}][dose]" class="form-control" placeholder="Dose / Frequency">
            </div>
            <div class="col-md-1 d-grid">
                <button type="button" class="btn btn-outline-danger remove-row">X</button>
            </div>
        `;
        list.appendChild(row);
        initMedicineSelects(row);
        attachRemoveHandlers();
    });

    function toggleComplicationNotesRequired() {
        const required = complicationStatus.value !== 'none';
        complicationNotes.required = required;
    }

    complicationStatus.addEventListener('change', toggleComplicationNotesRequired);
    toggleComplicationNotesRequired();
    attachRemoveHandlers();
    initMedicineSelects(document);
});
</script>
@endpush
