@extends('hospital.layouts.app')

@section('title', 'Edit Role — ' . $role->name)
{{-- Layout page-header intentionally unused — heading, breadcrumb and
list all sit inside one bordered card, matching the panel design used
across the rest of the app. --}}

@section('content')

<style>
.role-form-page {
    --roles-primary: #123C5A;
    --roles-secondary: #5B6F7B;
    --roles-accent: #1B4F72;
    --roles-soft: #F6FAFC;
    --roles-panel: #FFFFFF;
    --roles-border: rgba(18, 60, 90, .12);
    --roles-border-strong: rgba(18, 60, 90, .22);
    --roles-muted: rgba(18, 60, 90, .62);
    color: var(--roles-primary);
    animation: role-form-in 420ms ease both;
}
.role-outer-card {
    background: #ffffff;
    border: 1px solid rgba(15, 79, 134, 0.12);
    border-radius: 16px;
    box-shadow: 0 12px 32px rgba(15, 79, 134, 0.08);
    padding: 1.1rem 1.5rem;
    margin-bottom: 1.25rem;
}
.role-header-block {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: .75rem;
}
.role-header-title {
    font-weight: 800;
    font-size: 1.3rem;
    color: var(--roles-primary);
    letter-spacing: -.015em;
    display: flex;
    align-items: center;
    gap: .55rem;
}
.role-header-title i {
    color: var(--roles-primary);
    font-size: 1.2rem;
}
.role-breadcrumb {
    margin-top: .4rem;
    display: flex;
    align-items: center;
    gap: .4rem;
    font-size: .85rem;
    color: #8891a0;
}
.role-breadcrumb a {
    color: #8891a0;
    text-decoration: none;
}
.role-breadcrumb a:hover {
    color: var(--roles-primary);
}
.role-breadcrumb-sep {
    color: #c3c9d3;
}
.role-breadcrumb-current {
    color: #4a5568;
    font-weight: 600;
}
.role-form-card,
.permission-card {
    border: 1px solid rgba(15, 79, 134, 0.08) !important;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(15, 79, 134, 0.05) !important;
    background: var(--roles-panel);
}
.role-form-card .hms-card-header {
    background: linear-gradient(135deg, #F7FAFA, #FFFFFF);
    border-bottom: 1px solid var(--roles-border) !important;
    padding: 1.15rem 1.25rem;
}
.role-title-wrap,
.permission-title-wrap {
    display: flex;
    align-items: center;
    gap: .85rem;
}
.role-title-icon,
.permission-title-icon {
    width: 46px;
    height: 46px;
    border-radius: 15px;
    background: #FFFFFF;
    border: 1px solid var(--roles-border-strong);
    color: var(--roles-accent);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: none;
    font-size: 1.15rem;
}
.role-title,
.permission-title {
    color: var(--roles-primary) !important;
    font-weight: 900;
    letter-spacing: -.2px;
}
.role-subtitle,
.permission-subtitle {
    color: var(--roles-muted);
    font-size: .84rem;
    font-weight: 650;
    margin-top: .12rem;
}
.role-form-card .hms-card-body {
    background: #FCFEFF;
    padding: 1.25rem;
}
.role-form-card label {
    color: var(--roles-primary);
    font-weight: 850;
}
.role-form-card .hms-input {
    border: 1.5px solid var(--roles-border) !important;
    border-radius: 12px;
    background: #fff;
    color: var(--roles-primary);
    font-weight: 650;
}
.role-form-card .hms-input:focus {
    border-color: var(--roles-accent) !important;
    box-shadow: 0 0 0 4px rgba(27, 79, 114, .12);
}
.role-status-pills {
    display: flex;
    gap: .375rem;
    align-items: center;
    flex-wrap: wrap;
}
.permission-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-top: 1.25rem;
    margin-bottom: .85rem;
    flex-wrap: wrap;
}
.permission-actions {
    display: flex;
    gap: .5rem;
}
.permission-actions .hms-btn,
.role-submit-actions .hms-btn {
    border-radius: 12px;
    font-weight: 850;
}
.permission-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
.perm-row { transition: transform 170ms ease, box-shadow 170ms ease; }
.perm-row:hover { transform: translateY(-2px); box-shadow: 0 14px 30px rgba(38, 50, 56, .08); }
.module-label {
    background: #F1F7FA;
    color: var(--roles-primary);
    font-weight: 900;
    width: 200px;
    min-width: 200px;
    padding: 15px 20px;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.05em;
    border: 0;
    border-top-left-radius: 14px;
    border-bottom-left-radius: 14px;
    vertical-align: top;
}
.perm-container {
    padding: 15px 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    background: #fff;
    border-top: 1px solid var(--roles-border);
    border-right: 1px solid var(--roles-border);
    border-bottom: 1px solid var(--roles-border);
    border-top-right-radius: 14px;
    border-bottom-right-radius: 14px;
}
.perm-badge {
    background: #fff;
    border: 1px solid var(--roles-border);
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 13px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    color: var(--roles-primary);
    font-weight: 700;
}
.perm-badge:hover { border-color: var(--roles-accent); color: var(--roles-primary); transform: translateY(-1px); }
.perm-badge input[type="checkbox"] { margin: 0; accent-color: var(--roles-accent); }
.perm-badge input:checked + span { color: var(--roles-primary); font-weight: 900; }
.perm-badge:has(input:checked) { background: #EAF4FA; border-color: rgba(27, 79, 114, .42); }
.perm-badge.is-super { opacity: 0.75; cursor: not-allowed; }
.role-submit-actions { display:flex;gap:.75rem;margin-top:2rem;flex-wrap:wrap; }
@keyframes role-form-in {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
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
        border-radius: 14px 14px 0 0;
    }

    .perm-container {
        border-left: 1px solid var(--roles-border);
        border-radius: 0 0 14px 14px;
    }
}
</style>

<div class="role-form-page">

<div class="role-outer-card">
    <div class="role-header-block">
        <div>
            <div class="role-header-title"><i class="bi bi-shield-lock"></i> Edit Role: {{ $role->name }}</div>
            <nav class="role-breadcrumb" aria-label="breadcrumb">
                <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                <span class="role-breadcrumb-sep">/</span>
                <a href="{{ route('hospital.roles.index', ['slug' => $slug]) }}">Roles &amp; Permissions</a>
                <span class="role-breadcrumb-sep">/</span>
                <span class="role-breadcrumb-current">Edit Role</span>
            </nav>
        </div>
        <a href="{{ route('hospital.roles.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
            <i class="bi bi-arrow-left me-1"></i> Back to Roles
        </a>
    </div>
</div>

<form method="POST" action="{{ route('hospital.roles.update', ['slug' => $slug, 'id' => $role->id]) }}">
    @csrf
    @method('PUT')

    <div class="hms-card role-form-card" style="margin-bottom:1rem">
        <div class="hms-card-header">
            <div class="role-title-wrap">
                <span class="role-title-icon"><i class="bi bi-shield-lock"></i></span>
                <div>
                    <h3 class="hms-card-title role-title">Role Details</h3>
                    <div class="role-subtitle">Update access settings and permission coverage for this role.</div>
                </div>
            </div>
            <div class="role-status-pills">
                @if($role->is_system)
                    <span class="hms-badge hms-badge-dark">System Role</span>
                @endif
                @if($role->is_super)
                    <span class="hms-badge hms-badge-warning">Super — All Permissions</span>
                @endif
                <span class="hms-badge hms-badge-info">{{ $role->users_count }} user(s)</span>
            </div>
        </div>
        <div class="hms-card-body">
            <div class="hms-form-grid-3">
                <div class="hms-form-group" style="grid-column:span 2">
                    <label>Role Name <span class="hms-required">*</span></label>
                    <input type="text" name="name" class="hms-input @error('name') is-invalid @enderror"
                           value="{{ old('name', $role->name) }}" required maxlength="100"
                           {{ $role->is_system ? 'readonly' : '' }}>
                    @error('name')<div class="hms-field-error">{{ $message }}</div>@enderror
                </div>
                <div class="hms-form-group">
                    <label>Color</label>
                    <input type="color" name="color" class="hms-input" style="height:42px;padding:.35rem"
                           value="{{ old('color', $role->color) }}">
                </div>
                <div class="hms-form-group" style="grid-column:span 3">
                    <label>Description</label>
                    <input type="text" name="description" class="hms-input"
                           value="{{ old('description', $role->description) }}" maxlength="255">
                </div>
            </div>
        </div>
    </div>

    @if($role->is_super)
        <div class="hms-alert hms-alert-warning" style="margin-bottom:1rem">
            <i class="fa-solid fa-shield-halved"></i>
            This is a <strong>Super Role</strong> — it automatically has all permissions. Checkboxes are for reference only.
        </div>
    @endif

    <div class="permission-toolbar">
        <div class="permission-title-wrap">
            <span class="permission-title-icon"><i class="bi bi-key"></i></span>
            <div>
                <h5 class="permission-title mb-0">Assign Permissions</h5>
                <div class="permission-subtitle">Choose the modules and actions available to this role.</div>
            </div>
        </div>
        @unless($role->is_super)
        <div class="permission-actions">
            <button type="button" class="hms-btn hms-btn-outline" id="selectAll"
                    style="font-size:.8rem;padding:.3rem .75rem">
                <i class="fa-solid fa-check-double"></i> All
            </button>
            <button type="button" class="hms-btn hms-btn-outline" id="deselectAll"
                    style="font-size:.8rem;padding:.3rem .75rem">
                <i class="fa-solid fa-xmark"></i> None
            </button>
        </div>
        @endunless
    </div>

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
                            <label class="perm-badge {{ $role->is_super ? 'is-super' : '' }}">
                                <input type="checkbox" class="perm-checkbox" name="permissions[]" value="{{ $perm['id'] }}"
                                       {{ $perm['is_granted'] || $role->is_super ? 'checked' : '' }}
                                       {{ $role->is_super ? 'disabled' : '' }}>
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

    <div class="role-submit-actions">
        <button type="submit" class="hms-btn hms-btn-primary" style="color: #1b4f72;">
            <i class="fa-solid fa-check"></i> Save Role
        </button>
        <a href="{{ route('hospital.roles.index', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">Back to Roles</a>
    </div>
</form>
</div>

@endsection

@push('scripts')
<script>
    document.getElementById('selectAll')?.addEventListener('click', function () {
        document.querySelectorAll('.perm-checkbox:not(:disabled)').forEach(function (cb) { cb.checked = true; });
    });
    document.getElementById('deselectAll')?.addEventListener('click', function () {
        document.querySelectorAll('.perm-checkbox:not(:disabled)').forEach(function (cb) { cb.checked = false; });
    });
</script>
@endpush
