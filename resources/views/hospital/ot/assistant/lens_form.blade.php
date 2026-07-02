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

                            <div class="ot-lens-section mb-4">
                                <div class="ot-lens-section-header">
                                    <h6 class="fw-bold mb-0">Lens Entry Form</h6>
                                </div>
                                <div class="ot-lens-section-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Lens Name <span class="text-danger">*</span></label>
                                            <input type="text" name="lens_name" class="form-control"
                                                value="{{ old('lens_name', $lensDetail?->lens_name) }}" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Lens Type <span class="text-danger">*</span></label>
                                            <select name="lens_type" class="form-select" required>
                                                <option value="">Select type...</option>
                                                @foreach($lensTypes as $type)
                                                    <option value="{{ $type->name }}" {{ old('lens_type', $lensDetail?->lens_type) === $type->name ? 'selected' : '' }}>
                                                        {{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Lens Power <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" name="lens_power" class="form-control"
                                                value="{{ old('lens_power', $lensDetail?->lens_power) }}" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Lens MRP <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">INR</span>
                                                <input type="number" step="0.01" min="0" name="lens_mrp"
                                                    class="form-control"
                                                    value="{{ old('lens_mrp', $lensDetail?->lens_mrp) }}" required>
                                            </div>
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