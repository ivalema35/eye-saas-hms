@extends('hospital.layouts.app')
@section('title', 'Medicines')
@section('page-header', 'Medicine Master')

@section('page-actions')
    <button type="button"
            class="btn btn-primary btn-sm med-add-btn"
            data-bs-toggle="modal"
            data-bs-target="#medicineModal"
            onclick="resetMedicineForm()">
        <i class="bi bi-plus-lg me-1"></i> Add Medicine
    </button>
@endsection

@section('content')
<div class="medicines-page">

<style>
    .type-nav-tabs { background: var(--card-bg, #f7fbff); padding: 10px; border-radius: 14px; border: none; display: flex; gap: .5rem; align-items: center; box-shadow: none; }
    .type-nav-tabs .nav-item { margin: 0; }
    .type-nav-tabs .nav-link { border: none !important; background: transparent; color: var(--muted-color, #1f3560); padding: .5rem .9rem; border-radius: 999px; box-shadow: none; transition: all .15s ease-in-out; display: inline-flex; align-items: center; }
    .type-nav-tabs .nav-link i { margin-right: .5rem; }
    .type-nav-tabs .nav-link.active { background: var(--color-primary) !important; color: #ffffff !important; border-color: transparent !important; box-shadow: 0 6px 18px rgba(36,85,160,0.12); }
    .type-nav-tabs .nav-link:hover { background: rgba(36,85,160,0.1); color: var(--muted-color, #1f3560) !important; }
</style>

@if($errors->any())
    <div class="alert alert-danger d-flex gap-2 mb-4">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <ul class="mb-0 ps-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<ul class="nav nav-tabs mb-4 type-nav-tabs">
    <li class="nav-item">
        <a class="nav-link"
           href="{{ route('hospital.medicine-dosages.index', ['slug' => $slug]) }}">
            <i class="bi bi-capsule-pill me-1"></i> Dosages
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link"
           href="{{ route('hospital.medicine-types.index', ['slug' => $slug]) }}">
            <i class="bi bi-tags me-1"></i> Medicine Types
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link"
           href="{{ route('hospital.medicine-categories.index', ['slug' => $slug]) }}">
            <i class="bi bi-grid me-1"></i> Medicine Categories
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link"
           href="{{ route('hospital.medicine-routes.index', ['slug' => $slug]) }}">
            <i class="bi bi-arrow-right-circle me-1"></i> Route of Admin.
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active"
           href="{{ route('hospital.medicines.index', ['slug' => $slug]) }}">
            <i class="bi bi-capsule me-1"></i> Medicines
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link"
           href="{{ route('hospital.medicine-groups.index', ['slug' => $slug]) }}">
            <i class="bi bi-collection me-1"></i> Medicine Groups
        </a>
    </li>

    {{-- <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('hospital.medicine_instructions.*') ? 'active' : '' }}"
           href="{{ route('hospital.medicine_instructions.index', ['slug' => $slug]) }}">
            <i class="bi bi-list-ul me-1"></i> Instructions
        </a>
    </li> --}}
</ul>

<div class="card premium-card border-0 shadow-sm med-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3 py-3 med-card-header">
        <div class="med-title-wrap">
            <span class="med-title-icon">
                <i class="bi bi-capsule fs-4"></i>
            </span>
            <div>
                <h5 class="mb-0 fw-bold med-title" style="color: var(--color-primary);">
                    Medicines
                </h5>
                <div class="med-subtitle">Manage medicine names, brands, companies, pricing, and types.</div>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center med-tools">
            <form method="GET" action="{{ route('hospital.medicines.index', ['slug' => $slug]) }}"
                  class="d-flex gap-2 med-search-form">
                <div class="input-group med-search-box" style="min-width:240px">
                    <span class="input-group-text bg-white border-end-0 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control border-start-0 ps-0 clinical-input"
                           placeholder="Search name or brand...">
                </div>
                <button type="submit" class="btn btn-outline-primary text-nowrap med-search-btn">Search</button>
                @if(request('search'))
                    <a href="{{ route('hospital.medicines.index', ['slug' => $slug]) }}"
                       class="btn btn-outline-secondary text-nowrap med-clear-btn">Clear</a>
                @endif
            </form>
            <span class="badge text-bg-light border med-total-pill">{{ $medicines->total() }} total</span>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive med-table-wrap">
            <table class="table premium-table table-hover align-middle mb-0 med-table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Medicine Name</th>
                        <th>Dosage</th>
                        <th>Pricing</th>
                        <th class="text-end" style="width:120px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($medicines as $i => $med)
                    <tr>
                        <td class="text-muted med-index-cell">{{ $medicines->firstItem() + $i }}</td>
                        <td class="fw-semibold">
                            <span class="med-name-cell">{{ $med->name }}</span>
                        </td>
                        <td class="text-muted">{{ $med->dosage?->dosage ?? '—' }}</td>
                        <td><span class="med-price-pill">{{ $med->price !== null ? '₹'.number_format($med->price, 2) : '—' }}</span></td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1 action-btn-group">
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary med-icon-btn med-edit-btn edit-medicine-btn"
                                        data-record="{{ json_encode($med) }}"
                                        title="Edit">
                                    <i class="bi bi-pencil-fill" style="color: var(--color-secondary);"></i>
                                </button>
                                <form action="{{ route('hospital.medicines.destroy', ['slug' => $slug, 'medicine' => $med->id]) }}"
                                      method="POST"
                                      onsubmit="return confirm('Delete this medicine?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger med-icon-btn med-delete-btn" title="Delete">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted med-empty-cell">
                            No medicines found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($medicines->hasPages())
    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center med-card-footer">
        <small class="text-muted">
            Showing {{ $medicines->firstItem() }}–{{ $medicines->lastItem() }} of {{ $medicines->total() }}
        </small>
        {{ $medicines->links() }}
    </div>
    @endif
</div>
</div>

{{-- Add / Edit Modal --}}
<div class="modal fade medicine-modal" id="medicineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="medicineModalTitle">
                    Add Medicine
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="medicineForm"
                  action="{{ route('hospital.medicines.store', ['slug' => $slug]) }}"
                  method="POST">
                @csrf
                <input type="hidden" name="_method" id="medicineFormMethod" value="POST">
                <input type="hidden" name="medicine_id" id="input-medicine-id" value="{{ old('medicine_id') }}">

                <div class="modal-body py-3">

                    {{-- Row 1: Medicine Type + Medicine Name --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Medicine Type <span class="text-danger">*</span></label>
                            <select name="medicine_type_id" id="input-medicine-type-id"
                                    class="form-select clinical-input @error('medicine_type_id') is-invalid @enderror" required>
                                <option value="">Select type...</option>
                                @foreach($medicineTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('medicine_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                @endforeach
                            </select>
                            @error('medicine_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Medicine Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="input-name" value="{{ old('name') }}"
                                   class="form-control clinical-input @error('name') is-invalid @enderror"
                                   required placeholder="e.g. Moxifloxacin Tab">
                            <div class="form-text" style="font-size:.72rem">Add Tab / Caps / Oint at end</div>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Row 2: Dosage + Duration --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Medicine Dosage <span class="text-danger">*</span></label>
                            <select name="dosage_id" id="input-dosage-id" class="form-select clinical-input @error('dosage_id') is-invalid @enderror" required>
                                <option value="">Select dosage...</option>
                                @foreach($dosages as $d)
                                    <option value="{{ $d->id }}" {{ old('dosage_id') == $d->id ? 'selected' : '' }}>{{ $d->dosage }}</option>
                                @endforeach
                            </select>
                            @error('dosage_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Duration <span class="text-danger">*</span></label>
                            <input type="text" name="duration" id="input-duration" value="{{ old('duration') }}"
                                class="form-control clinical-input @error('duration') is-invalid @enderror"
                                placeholder="e.g. 4 days, 1 week" required>
                            @error('duration')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Row 3: Qty + Company --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Medicine Qty <span class="text-danger">*</span></label>
                            <input type="text" name="qty" id="input-qty" value="{{ old('qty') }}"
                                class="form-control clinical-input @error('qty') is-invalid @enderror"
                                placeholder="e.g. 10 tablets, 5ml" required>
                            @error('qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Company</label>
                            <input type="text" name="company" id="input-company" value="{{ old('company') }}"
                                class="form-control clinical-input @error('company') is-invalid @enderror"
                                placeholder="e.g. Sun Pharma">
                            @error('company')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        </div>

                    {{-- Row 4: Composition --}}
                    <div class="mb-3">
                        <label class="form-label fw-medium">Composition</label>
                        <textarea name="composition" id="input-composition" rows="2"
                                  class="form-control clinical-input @error('composition') is-invalid @enderror"
                                  placeholder="e.g. Moxifloxacin 0.5% w/v">{{ old('composition') }}</textarea>
                        @error('composition')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Price --}}
                    <div class="mb-1">
                        <label class="form-label fw-medium">Price (₹)</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" name="price" id="input-price" step="0.01" min="0"
                                   value="{{ old('price', '0.00') }}"
                                   class="form-control clinical-input @error('price') is-invalid @enderror"
                                   placeholder="0.00">
                        </div>
                        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                </div>

                <div class="modal-footer border-0 gap-2 medicine-modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Cancel
                    </button>
                    <button type="submit" class="btn btn-outline-secondary">
                     Save Medicine
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const medicineStoreUrl = "{{ route('hospital.medicines.store', ['slug' => $slug]) }}";
const medicineUpdateBase = "{{ route('hospital.medicines.update', ['slug' => $slug, 'medicine' => '__ID__']) }}";

function resetMedicineForm() {
    document.getElementById('medicineModalTitle').innerText = 'Add Medicine';
    document.getElementById('medicineForm').reset();
    document.getElementById('medicineFormMethod').value = 'POST';
    document.getElementById('medicineForm').action = medicineStoreUrl;
    document.getElementById('input-medicine-id').value = '';
    document.getElementById('input-price').value = '0.00';
}
window.resetMedicineForm = resetMedicineForm;

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.edit-medicine-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var record = JSON.parse(this.dataset.record);

            document.getElementById('medicineModalTitle').innerText = 'Edit Medicine';
            document.getElementById('medicineFormMethod').value = 'PUT';
            document.getElementById('medicineForm').action = medicineUpdateBase.replace('__ID__', record.id);
            document.getElementById('input-medicine-id').value = record.id ?? '';
            document.getElementById('input-medicine-type-id').value = record.medicine_type_id ?? '';
            document.getElementById('input-name').value        = record.name ?? '';
            document.getElementById('input-dosage-id').value   = record.dosage_id ?? '';
            document.getElementById('input-duration').value    = record.duration ?? '';
            document.getElementById('input-qty').value         = record.qty ?? '';
            document.getElementById('input-company').value     = record.company ?? '';
            document.getElementById('input-composition').value = record.composition ?? '';
            document.getElementById('input-price').value       = record.price ?? '0.00';

            var modal = new bootstrap.Modal(document.getElementById('medicineModal'), {});
            modal.show();
        });
    });

    @if($errors->any())
    (function () {
        var oldMethod = @json(old('_method', 'POST'));
        var oldMedicineId = @json(old('medicine_id', ''));

        document.getElementById('medicineFormMethod').value = oldMethod;
        document.getElementById('medicineModalTitle').innerText =
            oldMethod === 'PUT' ? 'Edit Medicine' : 'Add Medicine';
        document.getElementById('medicineForm').action =
            oldMethod === 'PUT' && oldMedicineId
                ? medicineUpdateBase.replace('__ID__', oldMedicineId)
                : medicineStoreUrl;
        document.getElementById('input-medicine-id').value = oldMedicineId;
        document.getElementById('input-medicine-type-id').value = @json(old('medicine_type_id', ''));
        document.getElementById('input-name').value = @json(old('name', ''));
        document.getElementById('input-company').value = @json(old('company', ''));
        document.getElementById('input-price').value = @json(old('price', '0.00'));

        var modal = new bootstrap.Modal(document.getElementById('medicineModal'), {});
        modal.show();
    })();
    @endif
});
</script>
@endpush
