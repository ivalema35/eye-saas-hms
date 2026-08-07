@extends('hospital.layouts.app')
@section('title', 'Record OT Surgery')
@section('page-header', 'Record OT Surgery')

@section('page-actions')
    <a href="{{ route('hospital.ot.assistant.dashboard', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
@endsection

@section('content')
    <div class="ot-surgery-page">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="card ot-premium-card border-0 ot-surgery-card">
                    <div class="ot-card-header">
                        <div class="ot-title-wrap">
                            <span class="ot-title-icon" aria-hidden="true">
                                <i class="bi bi-heart-pulse-fill" style="font-size: 1.2rem;"></i>
                            </span>
                            <div>
                                <h5 class="mb-1 ot-title">Surgery Recording Form</h5>
                                <p class="mb-0 ot-subtitle">Complete surgery details and ward medicines in one flow.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4 p-lg-5">
                        @if($errors->any())
                            <div class="alert alert-danger mb-4 ot-alert">
                                <ul class="mb-0 ps-3">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST"
                            action="{{ route('hospital.ot.surgery.store', ['slug' => $slug, 'bookingId' => $booking->id]) }}">
                            @csrf

                        <div class="ot-section mb-4">
                            <div class="ot-section-header">
                                <h6 class="fw-bold mb-0"><i class="bi bi-person-vcard me-1"></i> A. Patient &amp; Booking Details</h6>
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
                                        <label class="form-label">OT Date <span class="text-danger">*</span></label>
                                        <input type="date" name="surgery_date" class="form-control" required
                                            value="{{ old('surgery_date', optional($booking->surgery_date)->format('Y-m-d') ?: now()->format('Y-m-d')) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted">Package</label>
                                        <input type="text" class="form-control ot-readonly"
                                            value="{{ money_code((float) ($counselling?->package_amount ?? $booking->package_amount ?? 0), 2) }}"
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

                            <div class="ot-section mb-4">
                                <div class="ot-section-header">
                                    <h6 class="fw-bold mb-0"><i class="bi bi-scissors me-1"></i> B. Surgery Details</h6>
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
                                            @php
                                                // Lock to eye chosen on Recommend Surgery modal (booking.eye).
                                                $lockedEye = in_array((string) $booking->eye, ['RE', 'LE', 'Both'], true)
                                                    ? (string) $booking->eye
                                                    : 'RE';
                                                $eyeOptions = [$lockedEye];
                                                $selectedEye = old('eye_operated', $lockedEye);
                                                if (! in_array($selectedEye, $eyeOptions, true)) {
                                                    $selectedEye = $lockedEye;
                                                }
                                            @endphp
                                            <div class="ot-radio-group">
                                                @foreach($eyeOptions as $eye)
                                                    <div class="form-check form-check-inline ot-radio-pill">
                                                        <input class="form-check-input" type="radio" name="eye_operated"
                                                            id="eye_operated_{{ strtolower($eye) }}" value="{{ $eye }}"
                                                            {{ $selectedEye === $eye ? 'checked' : '' }} required>
                                                        <label class="form-check-label"
                                                            for="eye_operated_{{ strtolower($eye) }}">{{ $eye }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">OT Room</label>
                                            <input type="text" name="ot_room" class="form-control" value="{{ old('ot_room') }}" placeholder="e.g. OT-1">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Start Time</label>
                                            <input type="datetime-local" name="start_time" id="start_time" class="form-control" value="{{ old('start_time') }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">End Time</label>
                                            <input type="datetime-local" name="end_time" id="end_time" class="form-control" value="{{ old('end_time') }}">
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

                                        <div class="col-md-4">
                                            <label class="form-label">Blood Loss</label>
                                            <input type="text" name="blood_loss" class="form-control" value="{{ old('blood_loss') }}" placeholder="e.g. Minimal, 50ml">
                                        </div>

                                        <div class="col-md-12">
                                            <label class="form-label">Complication Notes</label>
                                            <textarea name="complication_notes" id="complication_notes" rows="2"
                                                class="form-control"
                                                placeholder="Only required if complication status is minor/major">{{ old('complication_notes') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @php
                                $c = $counselling ?? null;
                                $lensTypes = $lensTypes ?? \App\Http\Controllers\Hospital\OT\OtAssistantController::LENS_TYPES;
                                $selectedLensCost = old('lens_cost', $c->lens_cost ?? '');
                                $selectedLensCost = $selectedLensCost !== '' && $selectedLensCost !== null
                                    ? number_format((float) $selectedLensCost, 2, '.', '')
                                    : '';
                            @endphp
                            <div class="ot-section mb-4">
                                <div class="ot-section-header">
                                    <h6 class="fw-bold mb-0"><i class="bi bi-eyeglasses me-1"></i> C. Lens Selection</h6>
                                </div>
                                <div class="ot-section-body">
                                    <p class="text-muted small mb-3 mb-md-2">
                                        Auto-filled from Counsellor form — confirm or adjust if needed before saving surgery.
                                    </p>
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Lens Category</label>
                                            <select name="lens_category" class="form-select">
                                                <option value="">Select...</option>
                                                <option value="standard" @selected(old('lens_category', $c->lens_category ?? '') === 'standard')>Standard</option>
                                                <option value="premium" @selected(old('lens_category', $c->lens_category ?? '') === 'premium')>Premium</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Lens Company</label>
                                            <input type="text" name="lens_company" class="form-control"
                                                value="{{ old('lens_company', $c->lens_company ?? '') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Lens Model</label>
                                            <input type="text" name="lens_model" class="form-control"
                                                value="{{ old('lens_model', $c->lens_model ?? '') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Lens Type</label>
                                            <select name="lens_type" class="form-select">
                                                <option value="">Select...</option>
                                                @foreach($lensTypes as $type)
                                                    <option value="{{ $type }}" @selected(old('lens_type', $c->lens_type ?? '') === $type)>{{ $type }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Estimated Power</label>
                                            <input type="number" step="0.01" name="estimated_power" class="form-control"
                                                value="{{ old('estimated_power', $c->estimated_power ?? '') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Lens Cost</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ currency_code() }}</span>
                                                <input type="number" step="0.01" min="0" name="lens_cost" class="form-control"
                                                    value="{{ $selectedLensCost }}"
                                                    placeholder="Enter lens cost">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="ot-section mb-4">
                                <div
                                    class="ot-section-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                    <h6 class="fw-bold mb-0"><i class="bi bi-capsule me-1"></i> D. In-Ward Medicines</h6>
                                    <button type="button" id="addWardMedicine" class="btn btn-sm ot-btn-outline">
                                        <i class="bi bi-plus-circle me-1"></i> Add Medicine
                                    </button>
                                </div>
                                <div class="ot-section-body">
                                    @if($medicineGroups->isNotEmpty())
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Quick-fill from OT Medicine Group</label>
                                                <select id="medicineGroupPicker" name="medicine_group_id" class="form-select">
                                                    <option value="">Select group (optional)...</option>
                                                    @foreach($medicineGroups as $group)
                                                        <option value="{{ $group->id }}"
                                                            data-items="{{ $group->items->map(fn($item) => ['medicine' => $item->medicine?->name, 'dose' => trim(($item->frequency ?? '').' '.($item->duration ?? ''))])->filter(fn($i) => $i['medicine'])->values()->toJson() }}">
                                                            {{ $group->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    @endif
                                    <div id="otMedicineList">
                                        @php
                                            $oldOtMeds = old('ot_medicines', []);
                                            $oldOtMeds = is_array($oldOtMeds) ? $oldOtMeds : [];
                                        @endphp

                                        @forelse($oldOtMeds as $index => $row)
                                            <div class="row g-2 ot-medicine-row mb-2 align-items-end">
                                                <div class="col-md-6">
                                                    <label class="form-label">Medicine</label>
                                                    <select name="ot_medicines[{{ $index }}][medicine]"
                                                        class="form-select select2-medicine" required>
                                                        <option value="">Select medicine...</option>
                                                        @foreach($medicines as $med)
                                                            <option value="{{ $med->name }}" {{ ($row['medicine'] ?? '') === $med->name ? 'selected' : '' }}>{{ $med->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-label">Dose / Frequency</label>
                                                    <input type="text" name="ot_medicines[{{ $index }}][dose]"
                                                        class="form-control" placeholder="Dose / Frequency"
                                                        value="{{ $row['dose'] ?? '' }}">
                                                </div>
                                                <div class="col-md-1 d-grid">
                                                    <button type="button" class="btn ot-btn-danger remove-row">X</button>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="row g-2 ot-medicine-row mb-2 align-items-end">
                                                <div class="col-md-6">
                                                    <label class="form-label">Medicine</label>
                                                    <select name="ot_medicines[0][medicine]"
                                                        class="form-select select2-medicine" required>
                                                        <option value="">Select medicine...</option>
                                                        @foreach($medicines as $med)
                                                            <option value="{{ $med->name }}">{{ $med->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-label">Dose / Frequency</label>
                                                    <input type="text" name="ot_medicines[0][dose]" class="form-control"
                                                        placeholder="Dose / Frequency">
                                                </div>
                                                <div class="col-md-1 d-grid">
                                                    <button type="button" class="btn ot-btn-danger remove-row">X</button>
                                                </div>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 pt-3 ot-form-actions">
                                <a href="{{ route('hospital.ot.assistant.dashboard', ['slug' => $slug]) }}"
                                    class="hms-btn hms-btn-outline">Cancel</a>
                                <button type="submit" class="hms-btn hms-btn-primary px-4">
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
        /*
          OT Surgery Recording Form (Design refresh)
          Keep Blade/dynamic logic untouched; CSS-only + layout wrappers.
          Palette follows hospital shell theme (#1B4F72).
        */

        .ot-surgery-page {
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

        .ot-surgery-page .btn,
        .ot-surgery-page .hms-btn {
            border-radius: 12px;
            font-weight: 800;
            transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, border-color 170ms ease, color 170ms ease;
        }

        .ot-surgery-page .btn:hover,
        .ot-surgery-page .hms-btn:hover {
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
        }

        .ot-title-wrap {
            display: flex;
            align-items: center;
            gap: 0.9rem;
        }

        .ot-title-icon {
            width: 46px;
            height: 46px;
            border-radius: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: var(--ot-secondary);
            box-shadow: 0 14px 30px rgba(27, 79, 114, 0.22);
            flex: 0 0 auto;
        }

        .ot-title {
            font-weight: 900;
            letter-spacing: -0.2px;
            color: var(--ot-secondary);
        }

        .ot-subtitle {
            font-weight: 650;
            color: rgba(27, 79, 114, 0.72);
            font-size: .85rem;
        }

        .ot-alert { border-radius: 14px; font-weight: 650; }

        .ot-section {
            border: 1px solid var(--ot-s2-12);
            border-radius: 16px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.9);
        }

        .ot-section-header {
            padding: 0.85rem 1.1rem;
            border-bottom: 1px solid var(--ot-s2-12);
            background: linear-gradient(180deg, rgba(235, 245, 251, 0.9), rgba(255, 255, 255, 0.94));
            color: var(--ot-secondary);
        }

        .ot-section-body {
            padding: 1.1rem;
        }

        .ot-surgery-page .form-label {
            font-weight: 800;
            font-size: .82rem;
            color: var(--ot-secondary);
            letter-spacing: .01em;
        }

        .ot-surgery-page .form-control,
        .ot-surgery-page .form-select {
            border: 1px solid var(--ot-s2-18);
            border-radius: 12px;
            padding: .55rem .85rem;
            background: rgba(255, 255, 255, 0.95);
            color: var(--ot-secondary);
            font-weight: 600;
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }

        .ot-surgery-page .form-control:focus,
        .ot-surgery-page .form-select:focus {
            border-color: var(--ot-secondary);
            box-shadow: 0 0 0 .2rem var(--ot-s2-12);
        }

        .ot-readonly {
            background: rgba(27, 79, 114, 0.05) !important;
            color: rgba(27, 79, 114, 0.65);
        }

        .ot-radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
        }

        .ot-radio-pill {
            margin: 0;
            border: 1px solid var(--ot-s2-18);
            border-radius: 999px;
            padding: 0.45rem 0.9rem;
            background: #fff;
        }

        .ot-radio-pill .form-check-input {
            border-color: var(--ot-s2-24);
        }

        .ot-radio-pill .form-check-input:checked {
            background-color: var(--ot-secondary);
            border-color: var(--ot-secondary);
        }

        .ot-check-pill {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
            border: 1px solid var(--ot-s2-18);
            border-radius: 12px;
            padding: 0.6rem 0.75rem;
            background: #fff;
            cursor: pointer;
            font-weight: 700;
            color: var(--ot-secondary);
            transition: background 160ms ease, border-color 160ms ease;
        }

        .ot-check-pill .form-check-input {
            margin: 0;
            float: none;
            border-color: var(--ot-s2-24);
        }

        .ot-check-pill:has(.form-check-input:checked) {
            background: rgba(30, 142, 90, 0.08);
            border-color: rgba(30, 142, 90, 0.45);
        }

        .ot-medicine-row {
            border: 1px dashed var(--ot-s2-18);
            border-radius: 14px;
            padding: 0.85rem;
            background: linear-gradient(180deg, #fff, #f7fbfe);
        }

        .ot-btn-outline {
            border: 1px solid var(--ot-s2-24);
            color: var(--ot-secondary);
            background: #ffffff;
        }

        .ot-btn-outline:hover {
            background: var(--ot-secondary);
            color: #ffffff;
            border-color: var(--ot-secondary);
        }

        .ot-btn-danger {
            border: 1px solid rgba(192, 57, 43, 0.3);
            color: #C0392B;
            background: rgba(192, 57, 43, 0.06);
        }

        .ot-btn-danger:hover {
            background: rgba(192, 57, 43, 0.14);
            color: #C0392B;
            border-color: rgba(192, 57, 43, 0.4);
        }

        .ot-form-actions {
            border-top: 1px solid var(--ot-s2-12);
            margin-top: 0.25rem;
        }

        @media (prefers-reduced-motion: reduce) {
            .ot-surgery-page,
            .ot-premium-card,
            .ot-surgery-page .btn,
            .ot-surgery-page .hms-btn {
                animation: none !important;
                transition: none !important;
            }
        }

        @media (max-width: 991.98px) {
            .ot-card-header {
                padding: 1rem;
            }

            .ot-section-body {
                padding: 0.9rem;
            }
        }

        @media (max-width: 767.98px) {
            .ot-title-wrap {
                align-items: flex-start;
            }

            .ot-medicine-row {
                padding: 0.7rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const list = document.getElementById('otMedicineList');
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
                        const rows = list.querySelectorAll('.ot-medicine-row');
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
                        btn.closest('.ot-medicine-row').remove();
                        reindexRows();
                    };
                });
            }

            function reindexRows() {
                list.querySelectorAll('.ot-medicine-row').forEach(function (row, index) {
                    const medicineInput = row.querySelector('select[name*="[medicine]"], input[name*="[medicine]"]');
                    const doseInput = row.querySelector('select[name*="[dose]"], input[name*="[dose]"]');
                    medicineInput.name = `ot_medicines[${index}][medicine]`;
                    doseInput.name = `ot_medicines[${index}][dose]`;
                });
            }

            function addWardMedicineRow(medicineName, dose) {
                const index = list.querySelectorAll('.ot-medicine-row').length;
                const row = document.createElement('div');
                row.className = 'row g-2 ot-medicine-row mb-2';
                row.innerHTML = `
                <div class="col-md-6">
                    <select name="ot_medicines[${index}][medicine]" class="form-select select2-medicine" required>
                        ${medicineOptionsHtml}
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="text" name="ot_medicines[${index}][dose]" class="form-control" placeholder="Dose / Frequency">
                </div>
                <div class="col-md-1 d-grid">
                    <button type="button" class="btn ot-btn-danger remove-row">X</button>
                </div>
            `;
                list.appendChild(row);
                if (medicineName) {
                    row.querySelector('select').value = medicineName;
                }
                if (dose) {
                    row.querySelector('input').value = dose;
                }
                initMedicineSelects(row);
                attachRemoveHandlers();
            }

            addBtn.addEventListener('click', function () {
                addWardMedicineRow(null, null);
            });

            // ── OT Medicine Group quick-fill (Phase 4) ──────────────────────
            const medicineGroupPicker = document.getElementById('medicineGroupPicker');
            if (medicineGroupPicker) {
                medicineGroupPicker.addEventListener('change', function () {
                    const selected = this.options[this.selectedIndex];
                    const itemsJson = selected ? selected.dataset.items : null;
                    if (!itemsJson) { return; }

                    let items = [];
                    try { items = JSON.parse(itemsJson); } catch (e) { items = []; }
                    if (!items.length) { return; }

                    list.innerHTML = '';
                    items.forEach(function (item) {
                        addWardMedicineRow(item.medicine, item.dose);
                    });
                });
            }

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