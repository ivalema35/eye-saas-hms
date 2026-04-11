@extends('hospital.layouts.app')
@section('title', 'OT Lens Entry')
@section('page-header', 'OT Lens Entry')

@section('page-actions')
    <a href="{{ route('hospital.ot.assistant.dashboard', ['slug' => $slug]) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Assistant Dashboard
    </a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 fw-bold" style="color: var(--color-primary);">
                    <i class="bi bi-capsule-pill me-2"></i> Lens Details
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

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted">Patient Name</label>
                        <input type="text" class="form-control" value="{{ $booking->patient?->full_name ?? '-' }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Patient Code</label>
                        <input type="text" class="form-control" value="{{ $booking->patient?->patient_code ?? '-' }}" readonly>
                    </div>
                </div>

                <form method="POST" action="{{ route('hospital.ot.assistant.lens.store', ['slug' => $slug, 'bookingId' => $booking->id]) }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Lens Name <span class="text-danger">*</span></label>
                            <input type="text" name="lens_name" class="form-control" value="{{ old('lens_name', $lensDetail?->lens_name) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Lens Type <span class="text-danger">*</span></label>
                            <select name="lens_type" class="form-select" required>
                                <option value="">Select type...</option>
                                @foreach($lensTypes as $type)
                                    <option value="{{ $type->name }}" {{ old('lens_type', $lensDetail?->lens_type) === $type->name ? 'selected' : '' }}>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Lens Power <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="lens_power" class="form-control" value="{{ old('lens_power', $lensDetail?->lens_power) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Lens MRP <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">INR</span>
                                <input type="number" step="0.01" min="0" name="lens_mrp" class="form-control" value="{{ old('lens_mrp', $lensDetail?->lens_mrp) }}" required>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="implanted" id="implanted" value="1" {{ old('implanted', (int) ($lensDetail?->is_implanted ?? 0)) ? 'checked' : '' }}>
                                <label class="form-check-label" for="implanted">Lens Implanted</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-4">
                        <a href="{{ route('hospital.ot.assistant.dashboard', ['slug' => $slug]) }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check2-circle me-1"></i> Save Lens Details
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
