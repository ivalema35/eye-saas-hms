@extends('hospital.layouts.app')
@section('title', 'Medicine Dosages')
@section('page-header', 'Medicine Master')

@section('page-actions')
    <button class="btn btn-primary btn-sm"
            data-bs-toggle="modal"
            data-bs-target="#dosageModal"
            onclick="resetDosageForm()">
        <i class="bi bi-plus-lg me-1"></i> Add Dosage
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

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link"
           href="{{ route('hospital.medicines.index', ['slug' => $slug]) }}">
            <i class="bi bi-capsule me-1"></i> Medicines
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
           href="{{ route('hospital.medicine-groups.index', ['slug' => $slug]) }}">
            <i class="bi bi-collection me-1"></i> Medicine Groups
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active"
           href="{{ route('hospital.medicine-dosages.index', ['slug' => $slug]) }}">
            <i class="bi bi-capsule-pill me-1"></i> Dosages
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link"
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
                        <th>Dosage</th>
                        <th class="text-end" style="width:120px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dosages as $index => $dosage)
                    <tr>
                        <td class="text-muted">{{ $index + 1 }}</td>
                        <td class="fw-semibold">{{ $dosage->dosage }}</td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1 action-btn-group">
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary edit-dosage-btn"
                                        data-record="{{ json_encode($dosage) }}"
                                        title="Edit">
                                    <i class="bi bi-pencil-fill" style="color: var(--color-secondary);"></i>
                                </button>
                                <form action="{{ route('hospital.medicine-dosages.destroy', ['slug' => $slug, 'id' => $dosage->id]) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Delete this dosage? This cannot be undone.');">
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
                            <i class="bi bi-capsule-pill fs-3 d-block mb-2 opacity-25"></i>
                            No dosage found. Add one to get started.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade premium-modal" id="dosageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="dosageModalTitle" style="color: var(--color-primary);">
                    Add Dosage
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="dosageForm"
                  action="{{ route('hospital.medicine-dosages.store', ['slug' => $slug]) }}"
                  method="POST">
                @csrf
                <input type="hidden" name="_method" id="dosageFormMethod" value="POST">

                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-medium">
                            Dosage <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="dosage"
                               id="input-dosage"
                               class="form-control clinical-input @error('dosage') is-invalid @enderror"
                               placeholder="Enter dosage instruction..."
                               value="{{ old('dosage') }}"
                               required>
                        @error('dosage')
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
const storeUrl = "{{ route('hospital.medicine-dosages.store', ['slug' => $slug]) }}";
const updateBase = "{{ route('hospital.medicine-dosages.update', ['slug' => $slug, 'id' => '__ID__']) }}";

function resetDosageForm() {
    document.getElementById('dosageModalTitle').innerText = 'Add Dosage';
    document.getElementById('dosageForm').reset();
    document.getElementById('dosageFormMethod').value = 'POST';
    document.getElementById('dosageForm').action = storeUrl;
}
window.resetDosageForm = resetDosageForm;

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.edit-dosage-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var record = JSON.parse(this.dataset.record);

            document.getElementById('dosageModalTitle').innerText = 'Edit Dosage';
            document.getElementById('dosageFormMethod').value = 'PUT';
            document.getElementById('dosageForm').action = updateBase.replace('__ID__', record.id);
            document.getElementById('input-dosage').value = record.dosage ?? '';

            bootstrap.Modal.getOrCreateInstance(document.getElementById('dosageModal')).show();
        });
    });

    @if($errors->any())
    (function () {
        var oldMethod = @json(old('_method', 'POST'));
        document.getElementById('dosageFormMethod').value = oldMethod;
        document.getElementById('dosageModalTitle').innerText =
            oldMethod === 'PUT' ? 'Edit Dosage' : 'Add Dosage';
        if (oldMethod !== 'PUT') {
            document.getElementById('dosageForm').action = storeUrl;
        }
        document.getElementById('input-dosage').value = @json(old('dosage', ''));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('dosageModal')).show();
    })();
    @endif
});
</script>
@endpush
