@extends('hospital.layouts.app')
@section('title', 'Edit Medicine')
@section('page-header', 'Edit Medicine')

@section('page-actions')
    <a href="{{ route('hospital.medicines.index', ['slug' => $slug]) }}" class="btn btn-outline-secondary btn-sm">
         Back to List
    </a>
@endsection

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card premium-form-card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold" style="color: var(--color-primary);">
                    <i class="bi bi-pencil-square me-2"></i> Edit Medicine
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

                <form action="{{ route('hospital.medicines.update', ['slug' => $slug, 'medicine' => $medicine->id]) }}"
                      method="POST">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-medium">
                            Medicine Type <span class="text-danger">*</span>
                        </label>
                        <select name="medicine_type_id"
                                class="form-select clinical-input @error('medicine_type_id') is-invalid @enderror"
                                required>
                            <option value="">Select type...</option>
                            @foreach($medicineTypes as $type)
                                <option value="{{ $type->id }}"
                                    {{ old('medicine_type_id', $medicine->medicine_type_id) == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('medicine_type_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Select Type <span class="text-danger">*</span></label>
                        <select name="usage_scope"
                                class="form-select clinical-input @error('usage_scope') is-invalid @enderror"
                                required>
                            <option value="opd" @selected(old('usage_scope', $medicine->usage_scope ?? 'opd') === 'opd')>OPD</option>
                            <option value="ot" @selected(old('usage_scope', $medicine->usage_scope ?? '') === 'ot')>OT</option>
                        </select>
                        @error('usage_scope')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Medicine Name --}}
                    <div class="mb-3">
                        <label class="form-label fw-medium">Medicine Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $medicine->name) }}"
                               class="form-control clinical-input @error('name') is-invalid @enderror" required
                               placeholder="e.g. Moxifloxacin Tab / Vigamox Caps / Oint">
                        <div class="form-text text-muted" style="font-size:.75rem">Add Tab / Caps / Oint etc. at the end of name</div>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Dosage + Duration --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Medicine Dosage</label>
                            <select name="dosage_id" class="form-select clinical-input @error('dosage_id') is-invalid @enderror">
                                <option value="">Select dosage...</option>
                                @foreach($dosages as $d)
                                    <option value="{{ $d->id }}" {{ old('dosage_id', $medicine->dosage_id) == $d->id ? 'selected' : '' }}>
                                        {{ $d->dosage }}
                                    </option>
                                @endforeach
                            </select>
                            @error('dosage_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Duration</label>
                            <input type="text" name="duration" value="{{ old('duration', $medicine->duration) }}"
                                   class="form-control clinical-input @error('duration') is-invalid @enderror"
                                   placeholder="e.g. 4 days, 1 week">
                            <div class="form-text text-muted" style="font-size:.75rem">e.g. 4 days</div>
                            @error('duration')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Qty + Company --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Medicine Qty</label>
                            <input type="text" name="qty" value="{{ old('qty', $medicine->qty) }}"
                                   class="form-control clinical-input @error('qty') is-invalid @enderror"
                                   placeholder="e.g. 10 tablets, 5ml">
                            @error('qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Company Name</label>
                            <input type="text" name="company" value="{{ old('company', $medicine->company) }}"
                                   class="form-control clinical-input @error('company') is-invalid @enderror"
                                   placeholder="e.g. Alcon, Sun Pharma">
                            @error('company')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Composition --}}
                    <div class="mb-3">
                        <label class="form-label fw-medium">Composition</label>
                        <textarea name="composition" rows="2"
                                  class="form-control clinical-input @error('composition') is-invalid @enderror"
                                  placeholder="e.g. Moxifloxacin 0.5% w/v">{{ old('composition', $medicine->composition) }}</textarea>
                        @error('composition')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Price --}}
                    <div class="mb-4">
                        <label class="form-label fw-medium">Price ({{ currency_symbol() }})</label>
                        <div class="input-group">
                            <span class="input-group-text">{{ currency_symbol() }}</span>
                            <input type="number" name="price" step="0.01" min="0"
                                   value="{{ old('price', $medicine->price) }}"
                                   class="form-control clinical-input @error('price') is-invalid @enderror"
                                   placeholder="0.00">
                        </div>
                        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2 justify-content-end border-top pt-3">
                        <a href="{{ route('hospital.medicines.index', ['slug' => $slug]) }}"
                           class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i> Update Medicine
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
