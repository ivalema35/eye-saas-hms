@extends('hospital.layouts.app')
@section('title', 'Medicine Groups')
@section('page-header', 'Medicine Groups')

@section('page-actions')
    <a href="{{ route('hospital.medicine-groups.create', ['slug' => $slug]) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Group
    </a>
@endsection

@section('content')

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
        <a class="nav-link active"
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
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold" style="color: var(--color-primary);">
            <i class="bi bi-collection me-2"></i> Prescription Groups
        </h5>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table premium-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Group Name</th>
                        <th style="width:120px">Medicines</th>
                        <th style="width:150px">Created</th>
                        <th class="text-end" style="width:140px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $i => $group)
                    <tr>
                        <td class="text-muted">{{ $groups->firstItem() + $i }}</td>
                        <td class="fw-semibold">{{ $group->name }}</td>
                        <td>
                            <span class="badge text-bg-primary">{{ $group->items_count }}</span>
                        </td>
                        <td class="text-muted small">{{ $group->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1 action-btn-group">
                                <a href="{{ route('hospital.medicine-groups.show', ['slug' => $slug, 'medicine_group' => $group->id]) }}"
                                   class="btn btn-sm btn-outline-info" title="View">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                <a href="{{ route('hospital.medicine-groups.edit', ['slug' => $slug, 'medicine_group' => $group->id]) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Edit">
                                    <i class="bi bi-pencil-fill" style="color: var(--color-secondary);"></i>
                                </a>
                                <form action="{{ route('hospital.medicine-groups.destroy', ['slug' => $slug, 'medicine_group' => $group->id]) }}"
                                      method="POST"
                                      onsubmit="return confirm('Delete this medicine group?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-collection fs-2 d-block mb-2 opacity-25"></i>
                            No medicine groups yet. Create one to speed up prescriptions.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($groups->hasPages())
    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center">
        <small class="text-muted">
            Showing {{ $groups->firstItem() }}–{{ $groups->lastItem() }} of {{ $groups->total() }}
        </small>
        {{ $groups->links() }}
    </div>
    @endif
</div>

@endsection
