@extends('hospital.layouts.app')

@section('title', 'Roles & Permissions')
@section('page-header', 'Roles & Permissions')

@section('page-actions')
    <a href="{{ route('hospital.roles.create', ['slug' => $slug]) }}" class="hms-btn hms-btn-primary">
        <i class="fa-solid fa-plus"></i> New Role
    </a>
@endsection

@section('content')

<div class="hms-card">
    <div class="hms-card-header">
        <h3 class="hms-card-title">
            <i class="fa-solid fa-shield-halved"></i> Roles
        </h3>
        <span class="hms-badge hms-badge-info">{{ $roles->count() }} roles</span>
    </div>
    <div class="hms-card-body">
        <table class="hms-table">
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
                            <span class="hms-role-dot" style="background: {{ $role->color }}"></span>
                            <strong>{{ $role->name }}</strong>
                            @if($role->is_super)
                                <span class="hms-badge hms-badge-warning ms-1" title="Full access">Super</span>
                            @endif
                            @if($role->description)
                                <br><small style="color:var(--hms-text-muted);font-size:.8rem">{{ $role->description }}</small>
                            @endif
                        </td>
                        <td>{{ $role->users_count }}</td>
                        <td>
                            @if($role->is_system)
                                <span class="hms-badge hms-badge-dark">System</span>
                            @else
                                <span class="hms-badge hms-badge-info">Custom</span>
                            @endif
                        </td>
                        <td class="text-end" style="white-space:nowrap">
                            <a href="{{ route('hospital.roles.edit', ['slug' => $slug, 'id' => $role->id]) }}"
                               class="btn btn-sm btn-outline-primary me-2" style="border-radius:8px;font-weight:500">
                                <i class="fa-solid fa-pen me-1"></i> Edit
                            </a>
                            @if(! $role->is_system && $role->users_count === 0)
                                <form method="POST"
                                      action="{{ route('hospital.roles.destroy', ['slug' => $slug, 'id' => $role->id]) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Delete role &quot;{{ $role->name }}&quot;?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:8px;font-weight:500">
                                        <i class="fa-solid fa-trash me-1"></i> Delete
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="padding:3rem 1rem;text-align:center">
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

@endsection

@push('styles')
<style>
    .hms-role-dot {
        display: inline-block;
        width: 10px; height: 10px;
        border-radius: 50%;
        margin-right: 6px;
        vertical-align: middle;
    }
</style>
@endpush
