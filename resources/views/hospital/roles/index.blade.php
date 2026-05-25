@extends('hospital.layouts.app')

@section('title', 'Roles & Permissions')
@section('page-header', 'Roles & Permissions')

@section('page-actions')
    <a href="{{ route('hospital.roles.create', ['slug' => $slug]) }}" class="hms-btn hms-btn-primary roles-add-btn">
        <i class="bi bi-plus-lg"></i> New Role
    </a>
@endsection

@section('content')

<div class="roles-premium-page">
   

<div class="hms-card roles-card">
    <div class="hms-card-header roles-card-header">
        <div class="roles-title-wrap">
            <span class="roles-title-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </span>
            <div>
                <h3 class="hms-card-title roles-title">
                    Roles
                </h3>
                <div class="roles-subtitle">Review role assignments and manage available access profiles.</div>
            </div>
        </div>
        <span class="hms-badge hms-badge-info roles-count-pill">{{ $roles->count() }} roles</span>
    </div>
    <div class="hms-card-body roles-card-body">
        <div class="roles-table-wrap">
        <table class="hms-table roles-table">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Users</th>
                    <th>Type</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                    <tr>
                        <td>
                            <div class="roles-name-cell">
                                <span class="hms-role-dot" style="background: {{ $role->color }}"></span>
                                <strong>{{ $role->name }}</strong>
                                @if($role->is_super)
                                    <span class="hms-badge hms-badge-warning roles-status-badge ms-1" title="Full access">Super</span>
                                @endif
                            </div>
                            @if($role->description)
                                <small class="roles-description" style="color:var(--hms-text-muted);font-size:.8rem">{{ $role->description }}</small>
                            @endif
                        </td>
                        <td><span class="roles-user-pill">{{ $role->users_count }}</span></td>
                        <td>
                            @if($role->is_system)
                                <span class="hms-badge hms-badge-dark roles-status-badge">System</span>
                            @else
                                <span class="hms-badge hms-badge-info roles-status-badge">Custom</span>
                            @endif
                        </td>
                        <td class="text-end" style="white-space:nowrap">
                            <a href="{{ route('hospital.roles.edit', ['slug' => $slug, 'id' => $role->id]) }}"
                               class="btn btn-sm btn-outline-primary roles-action-btn roles-edit-btn me-2" style="border-radius:8px;font-weight:500">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            @if(! $role->is_system && $role->users_count === 0)
                                <form method="POST"
                                      action="{{ route('hospital.roles.destroy', ['slug' => $slug, 'id' => $role->id]) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Delete role &quot;{{ $role->name }}&quot;?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger roles-action-btn roles-delete-btn" style="border-radius:8px;font-weight:500">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="roles-empty-cell" style="padding:3rem 1rem;text-align:center">
                            <x-empty-state
                                icon="fa-solid fa-shield-halved"
                                title="No roles yet"
                                description="Create your first role to get started."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
</div>

@endsection

@push('styles')
<style>
    .roles-premium-page {
        --roles-primary: #1B4F72;
        --roles-secondary: #2980B9;
        --roles-success: #27AE60;
        --roles-soft: #ebf5fbeb;
        --roles-border: rgba(27, 79, 114, .12);
        --roles-border-strong: rgba(27, 79, 114, .22);
        --roles-muted: rgba(27, 79, 114, .68);
        color: var(--roles-primary);
        animation: roles-page-in 420ms ease both;
    }

    .roles-add-btn {
        background: var(--roles-primary, #1B4F72) !important;
        border-color: var(--roles-primary, #1B4F72) !important;
        color: #fff !important;
        border-radius: 12px;
        font-weight: 900;
        box-shadow: 0 12px 26px rgba(27, 79, 114, .16);
    }

    .roles-add-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 34px rgba(27, 79, 114, .22);
    }

    .roles-hero {
        display: flex;
        align-items: center;
        gap: .9rem;
        padding: 1.2rem 1.25rem;
        margin-bottom: 1rem;
        border: 1px solid var(--roles-border);
        border-radius: 22px;
        background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94));
        box-shadow: 0 18px 48px rgba(27, 79, 114, .10);
        position: relative;
        overflow: hidden;
    }

    .roles-hero::before {
        content: '';
        position: absolute;
        inset: 0 auto 0 0;
        width: 5px;
        background: var(--roles-success);
    }

    .roles-hero-icon,
    .roles-title-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: var(--roles-primary);
        color: #fff;
        box-shadow: 0 14px 30px rgba(27, 79, 114, .22);
    }

    .roles-hero-icon {
        width: 52px;
        height: 52px;
        border-radius: 17px;
        font-size: 1.25rem;
    }

    .roles-kicker {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        color: var(--roles-secondary);
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-bottom: .15rem;
    }

    .roles-hero h4 {
        margin: 0;
        color: var(--roles-primary);
        font-weight: 900;
        letter-spacing: -.2px;
    }

    .roles-hero p {
        margin: .15rem 0 0;
        color: var(--roles-muted);
        font-size: .9rem;
        font-weight: 650;
    }

    .roles-card {
        border: 1px solid var(--roles-border) !important;
        border-radius: 22px;
        background: rgba(255, 255, 255, .88);
        box-shadow: 0 18px 48px rgba(27, 79, 114, .10) !important;
        overflow: hidden;
        padding: 0;
        animation: roles-card-rise 520ms cubic-bezier(.2,.9,.2,1) both;
    }

    .roles-card-header {
        background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94));
        border-bottom: 1px solid var(--roles-border) !important;
        padding: 1.1rem 1.25rem;
        margin-bottom: 0;
    }

    .roles-title-wrap {
        display: flex;
        align-items: center;
        gap: .85rem;
    }

    .roles-title-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: rgba(235, 245, 251, .95);
        color: var(--roles-primary);
        border: 1px solid var(--roles-border-strong);
        box-shadow: none;
    }

    .roles-title {
        color: var(--roles-primary);
        font-weight: 900;
        letter-spacing: -.2px;
    }

    .roles-subtitle {
        color: var(--roles-muted);
        font-size: .84rem;
        font-weight: 650;
        margin-top: .12rem;
    }

    .roles-count-pill,
    .roles-status-badge,
    .roles-user-pill {
        border-radius: 999px;
        font-weight: 900;
    }

    .roles-count-pill {
        background: rgba(255, 255, 255, .78) !important;
        color: var(--roles-primary) !important;
        border: 1px solid var(--roles-border) !important;
        padding: .52rem .85rem;
        box-shadow: 0 10px 22px rgba(27, 79, 114, .08);
    }

    .roles-card-body {
        padding: .9rem;
        background: var(--roles-soft);
    }

    .roles-table-wrap {
        overflow-x: auto;
    }

    .roles-table {
        border-collapse: separate;
        border-spacing: 0 8px;
        min-width: 760px;
    }

    .roles-table thead tr,
    .roles-table thead th {
        background: var(--roles-primary) !important;
    }

    .roles-table thead th {
        color: #fff !important;
        border: 0 !important;
        padding: .9rem 1rem;
        font-size: .72rem;
        letter-spacing: .08em;
        font-weight: 900;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .roles-table thead th:first-child {
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .roles-table thead th:last-child {
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .roles-table tbody tr {
        animation: roles-row-in 460ms ease both;
        transition: transform 170ms ease, box-shadow 170ms ease;
    }

    .roles-table tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 34px rgba(27, 79, 114, .10);
    }

    .roles-table tbody td {
        background: rgba(255, 255, 255, .94);
        border-top: 1px solid rgba(27, 79, 114, .08);
        border-bottom: 1px solid rgba(27, 79, 114, .08);
        color: var(--roles-primary);
        padding: .9rem 1rem;
        vertical-align: middle;
        font-weight: 650;
    }

    .roles-table tbody td:first-child {
        border-left: 1px solid rgba(27, 79, 114, .08);
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .roles-table tbody td:last-child {
        border-right: 1px solid rgba(27, 79, 114, .08);
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .roles-name-cell {
        display: flex;
        align-items: center;
        gap: .45rem;
        flex-wrap: wrap;
    }

    .roles-name-cell .hms-role-dot {
        display: inline-block;
        width: 4px;
        height: 24px;
        border-radius: 999px;
        margin-right: .2rem;
        vertical-align: middle;
        box-shadow: none;
    }

    .roles-description {
        display: block;
        margin-top: .25rem;
        color: rgba(27, 79, 114, .62) !important;
        font-weight: 600;
    }

    .roles-user-pill {
        display: inline-flex;
        min-width: 38px;
        justify-content: center;
        padding: .36rem .72rem;
        background: var(--roles-soft);
        border: 1px solid var(--roles-border);
        color: var(--roles-primary);
    }

    .roles-action-btn {
        width: 34px;
        height: 34px;
        border-radius: 50% !important;
        font-weight: 850 !important;
        padding: 0 !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, color 170ms ease;
    }

    .roles-edit-btn {
        border-color: var(--roles-border-strong) !important;
        color: var(--roles-primary) !important;
        background: rgba(255, 255, 255, .78) !important;
    }

    .roles-edit-btn:hover {
        background: var(--roles-primary) !important;
        color: #fff !important;
    }

    .roles-delete-btn {
        background: rgba(255, 255, 255, .78) !important;
    }

    .roles-action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(27, 79, 114, .12);
    }

    .roles-empty-cell {
        background: rgba(255, 255, 255, .94) !important;
        border: 1px dashed var(--roles-border-strong) !important;
        border-radius: 16px !important;
    }

    @keyframes roles-page-in {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes roles-card-rise {
        from { opacity: 0; transform: translateY(12px) scale(.99); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes roles-row-in {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (prefers-reduced-motion: reduce) {
        .roles-premium-page,
        .roles-card,
        .roles-table tbody tr {
            animation: none;
        }

        .roles-premium-page * {
            transition: none !important;
        }
    }

    @media (max-width: 576px) {
        .roles-hero {
            align-items: flex-start;
        }

        .roles-hero-icon,
        .roles-title-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
        }

        .roles-card-header {
            align-items: flex-start;
        }
    }
</style>
@endpush
