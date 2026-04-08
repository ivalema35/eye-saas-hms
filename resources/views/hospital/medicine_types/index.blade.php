@extends('hospital.layouts.app')
@section('title', 'Medicine Types')
@section('page-header', 'Medicine Master')

@section('page-actions')
    <button class="btn btn-primary btn-sm"
            data-bs-toggle="modal"
            data-bs-target="#typeModal"
            onclick="resetTypeForm()">
        <i class="bi bi-plus-lg me-1"></i> Add Medicine Type
    </button>
@endsection

@section('content')

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

{{-- Medicine Module Navigation --}}
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link"
           href="{{ route('hospital.medicines.index', ['slug' => $slug]) }}">
            <i class="bi bi-capsule me-1"></i> Medicines
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active"
           href="{{ route('hospital.medicine-types.index', ['slug' => $slug]) }}">
            <i class="bi bi-tags me-1"></i> Medicine Types
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link"
           href="{{ route('hospital.medicine-groups.index', ['slug' => $slug]) }}">
            <i class="bi bi-collection me-1"></i> Medicine Groups
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link"
           href="{{ route('hospital.medicine-dosages.index', ['slug' => $slug]) }}">
            <i class="bi bi-capsule-pill me-1"></i> Dosages
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('hospital.medicine_instructions.*') ? 'active' : '' }}"
           href="{{ route('hospital.medicine_instructions.index', ['slug' => $slug]) }}">
            <i class="bi bi-list-ul me-1"></i> Instructions
        </a>
    </li>
</ul>

<div class="card premium-card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table premium-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Name</th>
                        <th class="text-end" style="width:120px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($types as $index => $type)
                    <tr>
                        <td class="text-muted">{{ $index + 1 }}</td>
                        <td class="fw-semibold">{{ $type->name }}</td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1 action-btn-group">
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary edit-type-btn"
                                        data-record="{{ json_encode($type) }}"
                                        title="Edit">
                                    <i class="bi bi-pencil-fill" style="color: var(--color-secondary);"></i>
                                </button>
                                <form action="{{ route('hospital.medicine-types.destroy', ['slug' => $slug, 'id' => $type->id]) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Delete this medicine type? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-5 fst-italic">
                            <i class="bi bi-tags fs-3 d-block mb-2 opacity-25"></i>
                            No medicine types found. Add one to get started.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Add / Edit Modal --}}
<div class="modal fade premium-modal" id="typeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="typeModalTitle" style="color: var(--color-primary);">
                    Add Medicine Type
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="typeForm"
                  action="{{ route('hospital.medicine-types.store', ['slug' => $slug]) }}"
                  method="POST">
                @csrf
                <input type="hidden" name="_method" id="typeFormMethod" value="POST">

                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-medium">
                            Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="name"
                               id="input-name"
                               class="form-control clinical-input @error('name') is-invalid @enderror"
                               placeholder="Enter medicine type name..."
                               value="{{ old('name') }}"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="bi bi-x me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check2 me-1"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const storeUrl   = "{{ route('hospital.medicine-types.store',  ['slug' => $slug]) }}";
const updateBase = "{{ route('hospital.medicine-types.update', ['slug' => $slug, 'id' => '__ID__']) }}";

// Called by Add button's onclick — resets form before Bootstrap opens the modal
function resetTypeForm() {
    document.getElementById('typeModalTitle').innerText = 'Add Medicine Type';
    document.getElementById('typeForm').reset();
    document.getElementById('typeFormMethod').value = 'POST';
    document.getElementById('typeForm').action = storeUrl;
}
window.resetTypeForm = resetTypeForm;

document.addEventListener('DOMContentLoaded', function () {

    // Edit buttons: read data-record attribute (HTML-entity-safe, no inline JSON quoting)
    document.querySelectorAll('.edit-type-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var record = JSON.parse(this.dataset.record);

            document.getElementById('typeModalTitle').innerText = 'Edit Medicine Type';
            document.getElementById('typeFormMethod').value     = 'PUT';
            document.getElementById('typeForm').action          = updateBase.replace('__ID__', record.id);
            document.getElementById('input-name').value         = record.name ?? '';

            bootstrap.Modal.getOrCreateInstance(document.getElementById('typeModal')).show();
        });
    });

    @if($errors->any())
    // Re-open modal with old input on validation failure
    (function () {
        var oldMethod = @json(old('_method', 'POST'));
        document.getElementById('typeFormMethod').value = oldMethod;
        document.getElementById('typeModalTitle').innerText =
            oldMethod === 'PUT' ? 'Edit Medicine Type' : 'Add Medicine Type';
        if (oldMethod !== 'PUT') {
            document.getElementById('typeForm').action = storeUrl;
        }
        document.getElementById('input-name').value = @json(old('name', ''));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('typeModal')).show();
    })();
    @endif

});
</script>
@endpush
