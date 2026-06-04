@extends('hospital.layouts.app')
@section('title', 'Medicine Groups')
@section('page-header', 'Medicine Groups')

@section('page-actions')
    <button type="button"
            class="btn btn-primary btn-sm med-add-btn group-add-btn"
            data-bs-toggle="modal"
            data-bs-target="#groupFormModal"
            onclick="resetGroupForm()">
        <i class="bi bi-plus-lg me-1"></i> New Group
    </button>
@endsection

@section('content')
<div class="medicine-groups-page medicines-page">

<style>
    .medicine-groups-page {
        --group-primary: #ebf5fbeb;
        --group-secondary: #1B4F72;
        --group-secondary-08: rgba(27, 79, 114, .08);
        --group-secondary-12: rgba(27, 79, 114, .12);
        --group-secondary-18: rgba(27, 79, 114, .18);
        --group-secondary-24: rgba(27, 79, 114, .24);
        color: var(--group-secondary);
    }

    .type-nav-tabs { background: var(--card-bg, #f7fbff); padding: 10px; border-radius: 14px; border: none; display: flex; gap: .5rem; align-items: center; box-shadow: none; }
    .type-nav-tabs .nav-item { margin: 0; }
    .type-nav-tabs .nav-link { border: none !important; background: transparent; color: var(--muted-color, #1f3560); padding: .5rem .9rem; border-radius: 999px; box-shadow: none; transition: all .15s ease-in-out; display: inline-flex; align-items: center; }
    .type-nav-tabs .nav-link i { margin-right: .5rem; }
    .type-nav-tabs .nav-link.active { background: var(--color-primary) !important; color: #ffffff !important; border-color: transparent !important; box-shadow: 0 6px 18px rgba(36,85,160,0.12); }
    .type-nav-tabs .nav-link:hover { background: rgba(36,85,160,0.1); color: var(--muted-color, #1f3560) !important; }

    .medicine-groups-page .group-nav-tabs .nav-link:hover {
        background: rgba(36, 85, 160, .1);
        color: var(--muted-color, #1f3560) !important;
    }

    .group-card .card-header {
        background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94)) !important;
        border-bottom: 1px solid var(--group-secondary-12) !important;
        padding: 1.15rem 1.25rem !important;
    }

    .group-title-wrap {
        display: flex;
        align-items: center;
        gap: .85rem;
    }

    .group-title-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        background: var(--group-secondary);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 14px 30px rgba(27, 79, 114, .22);
    }

    .group-title {
        color: var(--group-secondary) !important;
        font-weight: 900;
        letter-spacing: -.2px;
    }

    .group-subtitle {
        color: rgba(27, 79, 114, .68);
        font-size: .84rem;
        font-weight: 650;
        margin-top: .15rem;
    }

    .group-total-pill {
        background: rgba(255, 255, 255, .78) !important;
        color: var(--group-secondary) !important;
        border: 1px solid var(--group-secondary-12) !important;
        border-radius: 999px;
        padding: .52rem .85rem;
        font-weight: 900;
        box-shadow: 0 10px 22px rgba(27, 79, 114, .08);
    }

    .group-table-wrap {
        padding: .9rem;
        overflow-x: auto;
    }

    .group-table {
        border-collapse: separate;
        border-spacing: 0 8px;
        min-width: 760px;
    }

    .group-table thead tr,
    .group-table thead th {
        background: var(--group-secondary) !important;
    }

    .group-table thead th {
        color: #fff !important;
        border: 0 !important;
        padding: .9rem 1rem;
        font-size: .72rem;
        letter-spacing: .08em;
        font-weight: 900;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .group-table thead th:first-child {
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .group-table thead th:last-child {
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .group-table tbody tr {
        animation: med-row-in 460ms ease both;
        transition: transform 170ms ease, box-shadow 170ms ease;
    }

    .group-table tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 34px rgba(27, 79, 114, .10);
    }

    .group-table tbody td {
        background: rgba(255, 255, 255, .92);
        border-top: 1px solid var(--group-secondary-08);
        border-bottom: 1px solid var(--group-secondary-08);
        color: var(--group-secondary);
        padding: .9rem 1rem;
        vertical-align: middle;
        font-weight: 650;
    }

    .group-table tbody td:first-child {
        border-left: 1px solid var(--group-secondary-08);
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .group-table tbody td:last-child {
        border-right: 1px solid var(--group-secondary-08);
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .group-index-cell {
        color: rgba(27, 79, 114, .58) !important;
        font-weight: 900;
    }

    .group-name-cell {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        color: var(--group-secondary);
        font-weight: 800;
        letter-spacing: -.1px;
    }

    .group-name-cell i {
        color: var(--group-secondary);
    }

    .group-count-badge {
        background: var(--group-primary) !important;
        border: 1px solid var(--group-secondary-12) !important;
        color: var(--group-secondary) !important;
        border-radius: 999px;
        padding: .36rem .72rem;
        font-weight: 900;
    }

    .group-date-cell {
        color: rgba(27, 79, 114, .64) !important;
        font-size: .9rem;
        font-weight: 750;
    }

    .group-icon-btn {
        width: 34px;
        height: 34px;
        padding: 0 !important;
        border-radius: 50% !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, .78) !important;
        transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, color 170ms ease;
    }

    .group-icon-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(27, 79, 114, .12);
    }

    .group-view-btn,
    .group-edit-btn {
        border-color: var(--group-secondary-24) !important;
        color: var(--group-secondary) !important;
    }

    .group-view-btn:hover,
    .group-edit-btn:hover {
        background: var(--group-secondary) !important;
        color: #fff !important;
    }

    .group-edit-btn:hover i {
        color: #fff !important;
    }

    .group-delete-btn {
        border-color: #dc2626 !important;
        color: #dc2626 !important;
    }

    .group-delete-btn:hover {
        background: #dc2626 !important;
        color: #fff !important;
    }

    .group-empty-cell {
        background: var(--group-primary) !important;
        border: 1px dashed var(--group-secondary-18) !important;
        border-radius: 16px !important;
        color: var(--group-secondary) !important;
        font-weight: 800;
    }

    .group-card-footer {
        background: linear-gradient(135deg, rgba(235, 245, 251, .72), rgba(255, 255, 255, .94)) !important;
        border-top: 1px solid var(--group-secondary-12) !important;
        color: var(--group-secondary);
    }

    @media (prefers-reduced-motion: reduce) {
        .medicine-groups-page,
        .group-table tbody tr {
            animation: none;
        }

        .medicine-groups-page * {
            transition: none !important;
        }
    }

    @media (max-width: 576px) {
        .group-card .card-header {
            align-items: flex-start;
        }

        .group-title-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
        }
    }
</style>

<ul class="nav nav-tabs mb-4 type-nav-tabs">
    <li class="nav-item">
        <a class="nav-link"
           href="{{ route('hospital.medicine-dosages.index', ['slug' => $slug]) }}">
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
        <a class="nav-link active"
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

<div class="card premium-card border-0 shadow-sm med-card group-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3 py-3 group-card-header">
        <div class="group-title-wrap">
            <span class="group-title-icon">
                <i class="bi bi-collection fs-4"></i>
            </span>
            <div>
                <h5 class="mb-0 fw-bold group-title" style="color: var(--color-primary);">
                    Prescription Groups
                </h5>
                <div class="group-subtitle">Organize reusable medicine sets for faster prescriptions.</div>
            </div>
        </div>
        <span class="badge text-bg-light border group-total-pill">{{ $groups->total() }} total</span>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive group-table-wrap">
            <table class="table premium-table table-hover align-middle mb-0 group-table">
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
                        <td class="text-muted group-index-cell">{{ $groups->firstItem() + $i }}</td>
                        <td class="fw-semibold">
                            <span class="group-name-cell">{{ $group->name }}</span>
                        </td>
                        <td>
                            <span class="badge text-bg-primary group-count-badge">{{ $group->items_count }}</span>
                        </td>
                        <td class="group-date-cell">{{ $group->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1 action-btn-group">
                                <a href="{{ route('hospital.medicine-groups.show', ['slug' => $slug, 'medicine_group' => $group->id]) }}"
                                   class="btn btn-sm btn-outline-info group-icon-btn group-view-btn" title="View">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary group-icon-btn group-edit-btn edit-group-modal-btn"
                                        data-record="{{ json_encode($group) }}"
                                        title="Edit">
                                    <i class="bi bi-pencil-fill" style="color: var(--color-secondary);"></i>
                                </button>
                                <form action="{{ route('hospital.medicine-groups.destroy', ['slug' => $slug, 'medicine_group' => $group->id]) }}"
                                      method="POST"
                                      onsubmit="return confirm('Delete this medicine group?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger group-icon-btn group-delete-btn" title="Delete">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted group-empty-cell">
                            No medicine groups yet. Create one to speed up prescriptions.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($groups->hasPages())
    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center group-card-footer">
        <small class="text-muted">
            Showing {{ $groups->firstItem() }} - {{ $groups->lastItem() }} of {{ $groups->total() }}
        </small>
        {{ $groups->links() }}
    </div>
    @endif
</div>
</div>

@include('hospital.medicine_groups._group_modal')

@endsection
