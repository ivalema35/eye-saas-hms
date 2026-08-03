@extends('hospital.layouts.app')
@section('title', 'OT Lens Entry')
@section('page-header', 'OT Lens Entry')

@section('page-actions')
    <a href="{{ route('hospital.ot.assistant.dashboard', ['slug' => $slug]) }}" class="btn btn-outline-secondary btn-sm">
        Back to Assistant Dashboard
    </a>
@endsection

@section('content')
    <div class="ot-lens-page">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-9">
                <div class="card border-0 shadow-sm ot-lens-card">
                    <div class="card-header ot-lens-card-header border-bottom-0">
                        <div class="ot-lens-title-wrap">
                            <span class="ot-lens-title-icon">
                                <i class="bi bi-capsule-pill"></i>
                            </span>
                            <div>
                                <h5 class="mb-1 fw-bold ot-lens-title">Lens Details</h5>
                                <p class="mb-0 text-muted small">Record final lens specification and implantation status.
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

                        <div class="ot-lens-section mb-4">
                            <div class="ot-lens-section-header">
                                <h6 class="fw-bold mb-0">Patient Snapshot</h6>
                            </div>
                            <div class="ot-lens-section-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Patient Name</label>
                                        <input type="text" class="form-control ot-lens-readonly"
                                            value="{{ $booking->patient?->full_name ?? '-' }}" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Patient Code</label>
                                        <input type="text" class="form-control ot-lens-readonly"
                                            value="{{ $booking->patient?->patient_code ?? '-' }}" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="POST"
                            action="{{ route('hospital.ot.assistant.lens.store', ['slug' => $slug, 'bookingId' => $booking->id]) }}">
                            @csrf
                            <input type="hidden" name="lens_inventory_id" id="lens_inventory_id" value="{{ old('lens_inventory_id', $lensDetail?->lens_inventory_id) }}">

                            @if($lensInventory->isNotEmpty())
                                <div class="ot-lens-section mb-4">
                                    <div class="ot-lens-section-header">
                                        <h6 class="fw-bold mb-0"><i class="bi bi-box-seam me-1"></i> Pick from Stock (optional)</h6>
                                    </div>
                                    <div class="ot-lens-section-body">
                                        <label class="form-label">Lens Inventory</label>
                                        <select id="lensInventoryPicker" class="form-select">
                                            <option value="">Select a stock unit to auto-fill below, or enter manually...</option>
                                            @foreach($lensInventory as $item)
                                                <option value="{{ $item->id }}"
                                                    {{ (string) old('lens_inventory_id', $lensDetail?->lens_inventory_id) === (string) $item->id ? 'selected' : '' }}
                                                    data-manufacturer="{{ $item->manufacturer }}"
                                                    data-lens-name="{{ $item->lens_name }}"
                                                    data-type="{{ $item->type }}"
                                                    data-power="{{ $item->power }}"
                                                    data-mrp="{{ $item->mrp }}"
                                                    data-batch="{{ $item->batch_number }}"
                                                    data-serial="{{ $item->serial_number }}"
                                                    data-expiry="{{ optional($item->expiry_date)->format('Y-m-d') }}">
                                                    {{ $item->lens_code }} — {{ $item->lens_name }} ({{ $item->available_stock }} in stock)
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endif

                            <div class="ot-lens-section mb-4">
                                <div class="ot-lens-section-header">
                                    <h6 class="fw-bold mb-0">Lens Entry Form</h6>
                                </div>
                                <div class="ot-lens-section-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Lens Name <span class="text-danger">*</span></label>
                                            <input type="text" name="lens_name" id="lens_name" class="form-control"
                                                value="{{ old('lens_name', $lensDetail?->lens_name) }}" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Manufacturer</label>
                                            <input type="text" name="manufacturer" id="manufacturer" class="form-control"
                                                value="{{ old('manufacturer', $lensDetail?->manufacturer) }}">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Lens Type <span class="text-danger">*</span></label>
                                            <select name="lens_type" id="lens_type" class="form-select" required>
                                                <option value="">Select type...</option>
                                                @foreach($lensTypes as $type)
                                                    <option value="{{ $type }}" {{ old('lens_type', $lensDetail?->lens_type) === $type ? 'selected' : '' }}>
                                                        {{ $type }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Axis (°)</label>
                                            <input type="number" step="0.01" min="0" max="180" name="axis" class="form-control"
                                                value="{{ old('axis', $lensDetail?->axis) }}" placeholder="0-180, for toric lenses">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Lens Power <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" name="lens_power" id="lens_power" class="form-control"
                                                value="{{ old('lens_power', $lensDetail?->lens_power) }}" required>
                                            @if($lensPowers->isNotEmpty())
                                                <div class="ot-lens-power-picks mt-2">
                                                    @foreach($lensPowers as $power)
                                                        <button type="button" class="ot-lens-power-chip {{ $power->is_favourite ? 'is-favourite' : '' }}" data-power="{{ $power->power }}">
                                                            @if($power->is_favourite)<i class="bi bi-star-fill"></i>@endif
                                                            +{{ number_format((float) $power->power, 2) }}D
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Lens MRP <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ currency_code() }}</span>
                                                <input type="number" step="0.01" min="0" name="lens_mrp" id="lens_mrp"
                                                    class="form-control"
                                                    value="{{ old('lens_mrp', $lensDetail?->lens_mrp) }}" required>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Batch Number</label>
                                            <input type="text" name="batch_number" id="batch_number" class="form-control"
                                                value="{{ old('batch_number', $lensDetail?->batch_number) }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Serial Number</label>
                                            <input type="text" name="serial_number" id="serial_number" class="form-control"
                                                value="{{ old('serial_number', $lensDetail?->serial_number) }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Expiry Date</label>
                                            <input type="date" name="expiry_date" id="expiry_date" class="form-control"
                                                value="{{ old('expiry_date', optional($lensDetail?->expiry_date)->format('Y-m-d')) }}">
                                        </div>

                                        <div class="col-md-12">
                                            <label class="ot-lens-check-wrap" for="implanted">
                                                <input class="form-check-input" type="checkbox" name="implanted"
                                                    id="implanted" value="1" {{ old('implanted', (int) ($lensDetail?->is_implanted ?? 0)) ? 'checked' : '' }}>
                                                <span class="ot-lens-check-label">Lens Implanted</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-4 ot-lens-actions">
                                <a href="{{ route('hospital.ot.assistant.dashboard', ['slug' => $slug]) }}"
                                    class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-check2-circle me-1"></i> Save Lens Details
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
        .ot-lens-page {
            position: relative;
        }

        .ot-lens-page::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(1100px 380px at -8% -8%, rgba(41, 128, 185, 0.10), transparent 58%),
                radial-gradient(900px 340px at 105% 0%, rgba(27, 79, 114, 0.11), transparent 60%);
            pointer-events: none;
        }

        .ot-lens-card {
            position: relative;
            z-index: 1;
            border-radius: 1rem;
            overflow: hidden;
        }

        .ot-lens-card-header {
            background: linear-gradient(120deg, rgba(27, 79, 114, 0.10), rgba(41, 128, 185, 0.05));
            padding: 1.15rem 1.25rem;
        }

        .ot-lens-title-wrap {
            display: flex;
            align-items: center;
            gap: 0.9rem;
        }

        .ot-lens-title-icon {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: linear-gradient(145deg, var(--color-primary), var(--color-secondary));
            box-shadow: 0 8px 18px rgba(27, 79, 114, 0.28);
        }

        .ot-lens-title {
            color: var(--color-primary);
        }

        .ot-lens-section {
            border: 1px solid rgba(27, 79, 114, 0.12);
            border-radius: 0.9rem;
            overflow: hidden;
            background: #fff;
        }

        .ot-lens-section-header {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid rgba(27, 79, 114, 0.10);
            background: linear-gradient(180deg, rgba(41, 128, 185, 0.08), rgba(41, 128, 185, 0.02));
            color: var(--color-primary);
        }

        .ot-lens-section-body {
            padding: 1rem;
        }

        .ot-lens-readonly {
            background: #f8fbff;
            border-color: rgba(27, 79, 114, 0.14);
        }

        .ot-lens-check-wrap {
            border: 1px solid rgba(27, 79, 114, 0.18);
            border-radius: 0.75rem;
            background: linear-gradient(180deg, #ffffff, #f9fcff);
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.6rem 0.85rem;
            cursor: pointer;
        }

        .ot-lens-check-wrap .form-check-input {
            margin: 0;
            float: none;
            border-color: rgba(27, 79, 114, 0.35);
        }

        .ot-lens-check-label {
            color: var(--color-primary);
            font-weight: 600;
        }

        .ot-lens-actions {
            margin-top: 0.25rem;
        }

        .ot-lens-power-picks {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }

        .ot-lens-power-chip {
            border: 1px solid rgba(27, 79, 114, 0.22);
            border-radius: 999px;
            background: #fff;
            color: var(--color-primary);
            font-size: 0.78rem;
            font-weight: 700;
            padding: 0.3rem 0.65rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .ot-lens-power-chip.is-favourite {
            border-color: rgba(230, 126, 34, 0.4);
            color: #b9770e;
        }

        .ot-lens-power-chip:hover,
        .ot-lens-power-chip.active {
            background: var(--color-primary);
            border-color: var(--color-primary);
            color: #fff;
        }

        @media (max-width: 991.98px) {
            .ot-lens-card-header {
                padding: 1rem;
            }

            .ot-lens-section-body {
                padding: 0.9rem;
            }
        }

        @media (max-width: 767.98px) {
            .ot-lens-title-wrap {
                align-items: flex-start;
            }

            .ot-lens-check-wrap {
                width: 100%;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const lensPowerInput = document.getElementById('lens_power');
            document.querySelectorAll('.ot-lens-power-chip').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    lensPowerInput.value = this.dataset.power;
                    document.querySelectorAll('.ot-lens-power-chip').forEach(function (c) { c.classList.remove('active'); });
                    this.classList.add('active');
                });
            });

            // ── Lens Inventory picker (Phase 7) — auto-fill from stock ──────
            const inventoryPicker = document.getElementById('lensInventoryPicker');
            if (inventoryPicker) {
                inventoryPicker.addEventListener('change', function () {
                    const selected = this.options[this.selectedIndex];
                    document.getElementById('lens_inventory_id').value = this.value || '';

                    if (!this.value) { return; }

                    const setVal = function (id, val) {
                        const el = document.getElementById(id);
                        if (el && val) { el.value = val; }
                    };

                    setVal('manufacturer', selected.dataset.manufacturer);
                    setVal('lens_name', selected.dataset.lensName);
                    setVal('lens_power', selected.dataset.power);
                    setVal('lens_mrp', selected.dataset.mrp);
                    setVal('batch_number', selected.dataset.batch);
                    setVal('serial_number', selected.dataset.serial);
                    setVal('expiry_date', selected.dataset.expiry);

                    const typeEl = document.getElementById('lens_type');
                    if (typeEl && selected.dataset.type && typeEl.querySelector('option[value="' + selected.dataset.type + '"]')) {
                        typeEl.value = selected.dataset.type;
                    }
                });
            }
        });
    </script>
@endpush