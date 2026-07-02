@extends('hospital.layouts.app')
@section('title', 'Record OT Surgery')
@section('page-header', 'Record OT Surgery')

@section('page-actions')
    <a href="{{ route('hospital.ot.doctor.dashboard', ['slug' => $slug]) }}" class="btn btn-outline-secondary btn-sm">
        Back to Dashboard
    </a>
@endsection

@section('content')
    <div class="ot-surgery-page">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="card border-0 shadow-sm ot-surgery-card">
                    <div class="card-header ot-surgery-card-header border-bottom-0">
                        <div class="ot-surgery-title-wrap">
                            <span class="ot-surgery-title-icon">
                                <i class="bi bi-heart-pulse-fill"></i>
                            </span>
                            <div>
                                <h5 class="mb-1 fw-bold ot-surgery-title">Surgery Recording Form</h5>
                                <p class="mb-0 text-muted small">Complete surgery details and ward medicines in one flow.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4 p-lg-5">
                        @if($errors->any())
                            <div class="alert alert-danger mb-4">
                                <ul class="mb-0 ps-3">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="ot-section mb-4">
                            <div class="ot-section-header">
                                <h6 class="fw-bold mb-0">A. Patient & Booking Details</h6>
                            </div>
                            <div class="ot-section-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Patient Name</label>
                                        <input type="text" class="form-control ot-readonly"
                                            value="{{ $booking->patient?->full_name ?? '-' }}" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Phone</label>
                                        <input type="text" class="form-control ot-readonly"
                                            value="{{ $booking->patient?->contact_no ?? '-' }}" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted">OT Date</label>
                                        <input type="text" class="form-control ot-readonly"
                                            value="{{ optional($booking->surgery_date)->format('d M Y') }}" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted">Package</label>
                                        <input type="text" class="form-control ot-readonly"
                                            value="INR {{ number_format((float) ($counselling?->package_amount ?? $booking->package_amount ?? 0), 2) }}"
                                            readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted">Mediclaim</label>
                                        <input type="text" class="form-control ot-readonly"
                                            value="{{ ($counselling?->mediclaim ?? $booking->has_mediclaim) ? 'YES' : 'NO' }}"
                                            readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="POST"
                            action="{{ route('hospital.ot.surgery.store', ['slug' => $slug, 'bookingId' => $booking->id]) }}">
                            @csrf

                            <div class="ot-section mb-4">
                                <div class="ot-section-header">
                                    <h6 class="fw-bold mb-0">B. Surgery Details</h6>
                                </div>
                                <div class="ot-section-body">
                                    <div class="row g-3 mb-1">
                                        <div class="col-md-6">
                                            <label class="form-label">Surgery Name <span
                                                    class="text-danger">*</span></label>
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
                                            <label class="form-label d-block mb-2">Eye Operated <span
                                                    class="text-danger">*</span></label>
                                            <div class="ot-radio-group">
                                                @foreach(['RE', 'LE', 'Both'] as $eye)
                                                    <div class="form-check form-check-inline ot-radio-pill">
                                                        <input class="form-check-input" type="radio" name="eye_operated"
                                                            id="eye_operated_{{ strtolower($eye) }}" value="{{ $eye }}" {{ old('eye_operated', $booking->eye) === $eye ? 'checked' : '' }}>
                                                        <label class="form-check-label"
                                                            for="eye_operated_{{ strtolower($eye) }}">{{ $eye }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Complication Status <span
                                                    class="text-danger">*</span></label>
                                            <select name="complication_status" id="complication_status" class="form-select"
                                                required>
                                                <option value="none" {{ old('complication_status', 'none') === 'none' ? 'selected' : '' }}>None</option>
                                                <option value="minor" {{ old('complication_status') === 'minor' ? 'selected' : '' }}>Minor</option>
                                                <option value="major" {{ old('complication_status') === 'major' ? 'selected' : '' }}>Major</option>
                                            </select>
                                        </div>

                                        <div class="col-md-8">
                                            <label class="form-label">Complication Notes</label>
                                            <textarea name="complication_notes" id="complication_notes" rows="2"
                                                class="form-control"
                                                placeholder="Only required if complication status is minor/major">{{ old('complication_notes') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="ot-section mb-4">
                                <div
                                    class="ot-section-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                    <h6 class="fw-bold mb-0">C. In-Ward Medicines</h6>
                                    <button type="button" id="addWardMedicine" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-plus-circle me-1"></i> Add Medicine
                                    </button>
                                </div>
                                <div class="ot-section-body">
                                    <div id="wardMedicineList">
                                        @php
                                            $oldWardMeds = old('ward_medicines', []);
                                            $oldWardMeds = is_array($oldWardMeds) ? $oldWardMeds : [];
                                        @endphp

                                        @forelse($oldWardMeds as $index => $row)
                                            <div class="row g-2 ward-medicine-row mb-2 align-items-end">
                                                <div class="col-md-6">
                                                    <label class="form-label">Medicine</label>
                                                    <select name="ward_medicines[{{ $index }}][medicine]"
                                                        class="form-select select2-medicine" required>
                                                        <option value="">Select medicine...</option>
                                                        @foreach($medicines as $med)
                                                            <option value="{{ $med->name }}" {{ ($row['medicine'] ?? '') === $med->name ? 'selected' : '' }}>{{ $med->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-label">Dose / Frequency</label>
                                                    <input type="text" name="ward_medicines[{{ $index }}][dose]"
                                                        class="form-control" placeholder="Dose / Frequency"
                                                        value="{{ $row['dose'] ?? '' }}">
                                                </div>
                                                <div class="col-md-1 d-grid">
                                                    <button type="button" class="btn btn-outline-danger remove-row">X</button>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="row g-2 ward-medicine-row mb-2 align-items-end">
                                                <div class="col-md-6">
                                                    <label class="form-label">Medicine</label>
                                                    <select name="ward_medicines[0][medicine]"
                                                        class="form-select select2-medicine" required>
                                                        <option value="">Select medicine...</option>
                                                        @foreach($medicines as $med)
                                                            <option value="{{ $med->name }}">{{ $med->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-label">Dose / Frequency</label>
                                                    <input type="text" name="ward_medicines[0][dose]" class="form-control"
                                                        placeholder="Dose / Frequency">
                                                </div>
                                                <div class="col-md-1 d-grid">
                                                    <button type="button" class="btn btn-outline-danger remove-row">X</button>
                                                </div>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 border-top pt-3 ot-form-actions">
                                <a href="{{ route('hospital.ot.doctor.dashboard', ['slug' => $slug]) }}"
                                    class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-check2-circle me-1"></i> Save Surgery
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
        .ot-surgery-page {
            position: relative;
        }

        .ot-surgery-page::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(1200px 400px at -5% -10%, rgba(41, 128, 185, 0.10), transparent 55%),
                radial-gradient(900px 360px at 105% 0%, rgba(27, 79, 114, 0.12), transparent 58%);
            pointer-events: none;
        }

        .ot-surgery-card {
            position: relative;
            z-index: 1;
            overflow: hidden;
            border-radius: 1rem;
        }

        .ot-surgery-card-header {
            background:
                linear-gradient(120deg, rgba(27, 79, 114, 0.10), rgba(41, 128, 185, 0.04));
            padding: 1.15rem 1.25rem;
        }

        .ot-surgery-title-wrap {
            display: flex;
            align-items: center;
            gap: 0.9rem;
        }

        .ot-surgery-title-icon {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: linear-gradient(145deg, var(--color-primary), var(--color-secondary));
            box-shadow: 0 8px 20px rgba(27, 79, 114, 0.28);
        }

        .ot-surgery-title {
            color: var(--color-primary);
        }

        .ot-section {
            border: 1px solid rgba(27, 79, 114, 0.12);
            border-radius: 0.9rem;
            overflow: hidden;
            background: #fff;
        }

        .ot-section-header {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid rgba(27, 79, 114, 0.10);
            background: linear-gradient(180deg, rgba(41, 128, 185, 0.08), rgba(41, 128, 185, 0.02));
            color: var(--color-primary);
        }

        .ot-section-body {
            padding: 1rem;
        }

        .ot-readonly {
            background: #f8fbff;
            border-color: rgba(27, 79, 114, 0.14);
        }

        .ot-radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
        }

        .ot-radio-pill {
            margin: 0;
            border: 1px solid rgba(27, 79, 114, 0.18);
            border-radius: 0.65rem;
            padding: 0.45rem 0.7rem;
            background: #fff;
        }

        .ot-radio-pill .form-check-input {
            border-color: rgba(27, 79, 114, 0.35);
        }

        .ward-medicine-row {
            border: 1px dashed rgba(27, 79, 114, 0.18);
            border-radius: 0.75rem;
            padding: 0.75rem;
            background: linear-gradient(180deg, #fff, #f9fcff);
        }

        .ot-form-actions {
            margin-top: 0.25rem;
        }

        @media (max-width: 991.98px) {
            .ot-surgery-card-header {
                padding: 1rem;
            }

            .ot-section-body {
                padding: 0.9rem;
            }
        }

        @media (max-width: 767.98px) {
            .ot-surgery-title-wrap {
                align-items: flex-start;
            }

            .ward-medicine-row {
                padding: 0.7rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const list = document.getElementById('wardMedicineList');
            const addBtn = document.getElementById('addWardMedicine');
            const complicationStatus = document.getElementById('complication_status');
            const complicationNotes = document.getElementById('complication_notes');
            const medicineOptionsHtml = @json('<option value="">Select medicine...</option>' . $medicines->map(fn($med) => '<option value="' . e($med->name) . '">' . e($med->name) . '</option>')->implode(''));

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
                    const doseInput = row.querySelector('select[name*="[dose]"], input[name*="[dose]"]');
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