@extends('hospital.layouts.app')
@section('title', 'Route of Administration')
@section('page-header', 'Medicine Master')

@section('page-actions')
    <button class="btn btn-primary btn-sm type-add-btn"
            data-bs-toggle="modal"
            data-bs-target="#routeModal"
            onclick="resetRouteForm()">
        <i class="bi bi-plus-lg me-1"></i> Add Route of Admin.
    </button>
@endsection

@section('content')
<div class="medicine-routes-page">

<style>
    .medicine-routes-page {
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
        <a class="nav-link" href="{{ route('hospital.medicine-categories.index', ['slug' => $slug]) }}">
            <i class="bi bi-grid me-1"></i> Medicine Categories
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('hospital.medicine-routes.index', ['slug' => $slug]) }}">
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
                <i class="bi bi-arrow-right-circle fs-5"></i>
            </span>
            <div>
                <h5 class="mb-0 fw-bold" style="color:#1B4F72">Route of Administration</h5>
                <div style="color:rgba(27,79,114,.68);font-size:.84rem;font-weight:500;margin-top:.1rem">Manage routes e.g. Oral, Left Eye, Right Eye, IV, IM</div>
            </div>
        </div>
        <span class="badge text-bg-light border" style="font-size:.85rem;font-weight:900;padding:.45rem .85rem">{{ $routes->count() }} total</span>
    </div>
    <div class="card-body p-0">
        <div class="type-table-wrap">
            <table class="table type-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Route of Administration</th>
                        <th class="text-end" style="width:120px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($routes as $index => $route)
                    <tr>
                        <td class="type-index-cell">{{ $index + 1 }}</td>
                        <td class="type-name-cell">{{ $route->name }}</td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1 action-btn-group">
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary type-icon-btn edit-route-btn"
                                        data-record="{{ json_encode($route) }}"
                                        title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <form action="{{ route('hospital.medicine-routes.destroy', ['slug' => $slug, 'id' => $route->id]) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this route?');">
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
                            <i class="bi bi-arrow-right-circle fs-2 d-block mb-2 opacity-50"></i>
                            No routes found. Add one to get started.<br>
                            <small class="text-muted">e.g. Oral, Left Eye, Right Eye, Both Eyes, IV, IM, Nasal</small>
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
<div class="modal fade" id="routeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="routeModalTitle">Add Route of Administration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="routeForm"
                  action="{{ route('hospital.medicine-routes.store', ['slug' => $slug]) }}"
                  method="POST">
                @csrf
                <input type="hidden" name="_method" id="routeFormMethod" value="POST">

                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-medium">
                            Route Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="name"
                               id="input-route-name"
                               class="form-control clinical-input @error('name') is-invalid @enderror"
                               placeholder="e.g. Oral, Left Eye, Right Eye..."
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
const routeStoreUrl   = "{{ route('hospital.medicine-routes.store',  ['slug' => $slug]) }}";
const routeUpdateBase = "{{ route('hospital.medicine-routes.update', ['slug' => $slug, 'id' => '__ID__']) }}";

function resetRouteForm() {
    document.getElementById('routeModalTitle').innerText = 'Add Route of Administration';
    document.getElementById('routeForm').reset();
    document.getElementById('routeFormMethod').value = 'POST';
    document.getElementById('routeForm').action = routeStoreUrl;
}
window.resetRouteForm = resetRouteForm;

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.edit-route-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var record = JSON.parse(this.dataset.record);
            document.getElementById('routeModalTitle').innerText = 'Edit Route of Administration';
            document.getElementById('routeFormMethod').value = 'PUT';
            document.getElementById('routeForm').action = routeUpdateBase.replace('__ID__', record.id);
            document.getElementById('input-route-name').value = record.name ?? '';
            new bootstrap.Modal(document.getElementById('routeModal')).show();
        });
    });

    @if($errors->any())
    (function () {
        var oldMethod = @json(old('_method', 'POST'));
        document.getElementById('routeFormMethod').value = oldMethod;
        document.getElementById('routeModalTitle').innerText =
            oldMethod === 'PUT' ? 'Edit Route of Administration' : 'Add Route of Administration';
        document.getElementById('input-route-name').value = @json(old('name', ''));
        new bootstrap.Modal(document.getElementById('routeModal')).show();
    })();
    @endif
});
</script>
@endpush
