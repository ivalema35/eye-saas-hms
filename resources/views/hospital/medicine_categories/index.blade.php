@extends('hospital.layouts.app')
@section('title', 'Medicine Categories')
@section('page-header', 'Medicine Master')

@section('page-actions')
    <button class="btn btn-primary btn-sm type-add-btn"
            data-bs-toggle="modal"
            data-bs-target="#categoryModal"
            onclick="resetCategoryForm()">
        <i class="bi bi-plus-lg me-1"></i> Add Medicine Category
    </button>
@endsection

@section('content')
<div class="medicine-categories-page">

<style>
    .medicine-categories-page {
        --type-primary: #ebf5fbeb;
        --type-secondary: #1B4F72;
        --type-secondary-08: rgba(27, 79, 114, .08);
        --type-secondary-12: rgba(27, 79, 114, .12);
        --type-secondary-18: rgba(27, 79, 114, .18);
        --type-secondary-24: rgba(27, 79, 114, .24);
    }
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

{{-- Medicine Module Navigation --}}
<ul class="nav nav-tabs mb-4 type-nav-tabs">
    <li class="nav-item">
        <a class="nav-link" href="{{ route('hospital.medicine-dosages.index', ['slug' => $slug]) }}">
            <i class="bi bi-capsule-pill me-1"></i> Dosages
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('hospital.medicine-types.index', ['slug' => $slug]) }}">
            <i class="bi bi-tags me-1"></i> Medicine Types
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('hospital.medicine-categories.index', ['slug' => $slug]) }}">
            <i class="bi bi-grid me-1"></i> Medicine Categories
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('hospital.medicine-routes.index', ['slug' => $slug]) }}">
            <i class="bi bi-arrow-right-circle me-1"></i> Route of Admin.
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('hospital.medicines.index', ['slug' => $slug]) }}">
            <i class="bi bi-capsule me-1"></i> Medicines
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('hospital.medicine-groups.index', ['slug' => $slug]) }}">
            <i class="bi bi-collection me-1"></i> Medicine Groups
        </a>
    </li>
    {{-- <li class="nav-item">
        <a class="nav-link" href="{{ route('hospital.medicine_instructions.index', ['slug' => $slug]) }}">
            <i class="bi bi-list-ul me-1"></i> Instructions
        </a>
    </li> --}}
</ul>

<div class="card type-card border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3" style="border-bottom:1px solid rgba(27,79,114,.12)">
        <div class="d-flex align-items-center gap-3">
            <span style="width:48px;height:48px;border-radius:16px;background:#1B4F72;color:#fff;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 14px 30px rgba(27,79,114,.22);flex-shrink:0">
                <i class="bi bi-grid fs-5"></i>
            </span>
            <div>
                <h5 class="mb-0 fw-bold" style="color:#1B4F72">Medicine Categories</h5>
                <div style="color:rgba(27,79,114,.68);font-size:.84rem;font-weight:500;margin-top:.1rem">Manage categories e.g. Antibiotic, Steroid, Lubricant</div>
            </div>
        </div>
        <span class="badge text-bg-light border" style="font-size:.85rem;font-weight:900;padding:.45rem .85rem">{{ $categories->count() }} total</span>
    </div>
    <div class="card-body p-0">
        <div class="type-table-wrap">
            <table class="table type-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Category Name</th>
                        <th class="text-end" style="width:120px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $index => $category)
                    <tr>
                        <td class="type-index-cell">{{ $index + 1 }}</td>
                        <td class="type-name-cell">{{ $category->name }}</td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1 action-btn-group">
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary type-icon-btn edit-category-btn"
                                        data-record="{{ json_encode($category) }}"
                                        title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <form action="{{ route('hospital.medicine-categories.destroy', ['slug' => $slug, 'id' => $category->id]) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this category?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger type-icon-btn" title="Delete">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center py-5 text-muted">
                            <i class="bi bi-grid fs-2 d-block mb-2 opacity-50"></i>
                            No medicine categories found. Add one to get started.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

</div>

{{-- Add / Edit Modal --}}
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="categoryModalTitle">Add Medicine Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="categoryForm"
                  action="{{ route('hospital.medicine-categories.store', ['slug' => $slug]) }}"
                  method="POST">
                @csrf
                <input type="hidden" name="_method" id="categoryFormMethod" value="POST">

                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-medium">
                            Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="name"
                               id="input-category-name"
                               class="form-control clinical-input @error('name') is-invalid @enderror"
                               placeholder="e.g. Antibiotic, Steroid, Lubricant..."
                               value="{{ old('name') }}"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer border-0 gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const catStoreUrl   = "{{ route('hospital.medicine-categories.store',  ['slug' => $slug]) }}";
const catUpdateBase = "{{ route('hospital.medicine-categories.update', ['slug' => $slug, 'id' => '__ID__']) }}";

function resetCategoryForm() {
    document.getElementById('categoryModalTitle').innerText = 'Add Medicine Category';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryFormMethod').value = 'POST';
    document.getElementById('categoryForm').action = catStoreUrl;
}
window.resetCategoryForm = resetCategoryForm;

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.edit-category-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var record = JSON.parse(this.dataset.record);
            document.getElementById('categoryModalTitle').innerText = 'Edit Medicine Category';
            document.getElementById('categoryFormMethod').value = 'PUT';
            document.getElementById('categoryForm').action = catUpdateBase.replace('__ID__', record.id);
            document.getElementById('input-category-name').value = record.name ?? '';
            new bootstrap.Modal(document.getElementById('categoryModal')).show();
        });
    });

    @if($errors->any())
    (function () {
        var oldMethod = @json(old('_method', 'POST'));
        document.getElementById('categoryFormMethod').value = oldMethod;
        document.getElementById('categoryModalTitle').innerText =
            oldMethod === 'PUT' ? 'Edit Medicine Category' : 'Add Medicine Category';
        document.getElementById('input-category-name').value = @json(old('name', ''));
        new bootstrap.Modal(document.getElementById('categoryModal')).show();
    })();
    @endif
});
</script>
@endpush
