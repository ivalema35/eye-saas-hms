@extends('hospital.layouts.app')

@section('title', 'Create Role')
@section('page-header', 'Create New Role')

@section('content')

<style>
.permission-card { border: 0; border-radius: 15px; overflow: hidden; box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08); background: #fff; }
.permission-table { width: 100%; border-collapse: collapse; }
.perm-row { border-bottom: 1px solid #edf2f7; transition: background 0.2s; }
.perm-row:hover { background: #f8fafc; }
.perm-row:last-child { border-bottom: none; }
.module-label {
    background: #f1f5f9;
    color: #475569;
    font-weight: 700;
    width: 200px;
    min-width: 200px;
    padding: 15px 20px;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.05em;
    border-right: 1px solid #e2e8f0;
    vertical-align: top;
}
.perm-container { padding: 15px 20px; display: flex; flex-wrap: wrap; gap: 10px; }
.perm-badge {
    background: #fff;
    border: 1px solid #d1d5db;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    color: #334155;
}
.perm-badge:hover { border-color: #3b82f6; color: #3b82f6; }
.perm-badge input[type="checkbox"] { margin: 0; accent-color: #2563eb; }
.perm-badge input:checked + span { color: #1e40af; font-weight: 600; }
.perm-badge:has(input:checked) { background: #eff6ff; border-color: #3b82f6; }
@media (max-width: 768px) {
    .module-label,
    .perm-container {
        display: block;
        width: 100%;
    }

    .permission-table,
    .permission-table tbody,
    .permission-table tr,
    .permission-table td {
        display: block;
        width: 100%;
    }

    .module-label {
        border-right: 0;
        border-bottom: 1px solid #e2e8f0;
    }
}
</style>

<form method="POST" action="{{ route('hospital.roles.store', ['slug' => $slug]) }}">
    @csrf

    <div class="hms-card" style="margin-bottom:1rem">
        <div class="hms-card-header">
            <h3 class="hms-card-title"><i class="fa-solid fa-shield-halved" style="color:var(--hms-primary)"></i> Role Details</h3>
        </div>
        <div class="hms-card-body">
            <div class="hms-form-grid-3">
                <div class="hms-form-group" style="grid-column:span 2">
                    <label>Role Name <span class="hms-required">*</span></label>
                    <input type="text" name="name" class="hms-input @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" required maxlength="100" placeholder="e.g. OT Receptionist">
                    @error('name')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>
                <div class="hms-form-group">
                    <label>Color</label>
                    <input type="color" name="color" class="hms-input" style="height:42px;padding:.35rem"
                           value="{{ old('color', '#1B4F72') }}">
                </div>
                <div class="hms-form-group" style="grid-column:span 3">
                    <label>Description</label>
                    <input type="text" name="description" class="hms-input"
                           value="{{ old('description') }}" maxlength="255" placeholder="Short description">
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:2rem">
        <h5 style="color:#1A202C;font-weight:600;font-family:'Inter',sans-serif;margin:0">Assign Permissions</h5>
        <div style="display:flex;gap:.5rem">
            <button type="button" class="hms-btn hms-btn-outline" id="selectAll"
                    style="font-size:.8rem;padding:.3rem .75rem">
                <i class="fa-solid fa-check-double"></i> All
            </button>
            <button type="button" class="hms-btn hms-btn-outline" id="deselectAll"
                    style="font-size:.8rem;padding:.3rem .75rem">
                <i class="fa-solid fa-xmark"></i> None
            </button>
        </div>
    </div>
    <hr style="border-color:#E2E8F0">

    <div class="permission-card">
        <div class="table-responsive">
        <table class="permission-table">
            <tbody>
                @foreach($modules as $moduleName => $permissions)
                <tr class="perm-row">
                    <td class="module-label">
                        <i class="fa-solid fa-folder-open me-2 text-primary"></i>
                            {{ str_replace('_', ' ', $moduleName) }}
                    </td>
                    <td class="perm-container">
                            @foreach($permissions as $perm)
                            <label class="perm-badge">
                                <input type="checkbox" class="perm-checkbox" name="permissions[]" value="{{ $perm['id'] }}"
                                       {{ in_array($perm['id'], old('permissions', [])) ? 'checked' : '' }}>
                                <span>{{ $perm['label'] }}</span>
                            </label>
                            @endforeach
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>

    <div style="display:flex;gap:.75rem;margin-top:2rem">
        <button type="submit" class="hms-btn hms-btn-primary">
            <i class="fa-solid fa-check"></i> Create Role
        </button>
        <a href="{{ route('hospital.roles.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">Cancel</a>
    </div>
</form>

@endsection

@push('scripts')
<script>
    document.getElementById('selectAll').addEventListener('click', function () {
        document.querySelectorAll('.perm-checkbox').forEach(function (cb) { cb.checked = true; });
    });
    document.getElementById('deselectAll').addEventListener('click', function () {
        document.querySelectorAll('.perm-checkbox').forEach(function (cb) { cb.checked = false; });
    });
</script>
@endpush
