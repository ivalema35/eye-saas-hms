@extends('hospital.layouts.app')
@section('title', 'Medicine Groups')
{{-- Layout page-header intentionally unused — the heading, breadcrumb, tabs
and list all sit inside one bordered card, matching the superadmin Medicine
Master design. --}}

@section('content')
<div class="medicine-groups-page medicines-page">

<div class="medmaster-outer-card">
    <div class="medmaster-header-block">
        <div class="medmaster-header-title"><i class="bi bi-capsule"></i> Medicine Master</div>
        <nav class="medmaster-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
            <span class="medmaster-breadcrumb-sep">/</span>
            <span>Medicines</span>
            <span class="medmaster-breadcrumb-sep">/</span>
            <span class="medmaster-breadcrumb-current">Medicine Master</span>
        </nav>
    </div>

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

    /* ── master table ── */
    .group-table {
        border-collapse: collapse;
        min-width: 820px;
        width: 100%;
    }

    .group-table thead th {
        background: #F8FAFC !important;
        color: #4A5568 !important;
        border: 0 !important;
        border-bottom: 1px solid #E2E8F0 !important;
        padding: .7rem 1rem;
        font-size: .75rem;
        letter-spacing: .05em;
        font-weight: 700;
        text-transform: uppercase;
        white-space: nowrap;
        vertical-align: middle;
        text-align: left;
    }

    .group-table td {
        border: 0;
        border-bottom: 1px solid var(--group-secondary-12);
        padding: .6rem .9rem;
        vertical-align: middle;
        color: var(--group-secondary);
        font-weight: 600;
        font-size: .88rem;
        background: #fff;
    }

    .group-table tbody tr:hover td {
        background: #F5F8FC;
    }

    /* separator row between groups */
    .group-table tr.group-separator td {
        background: var(--group-secondary-08) !important;
        border: none !important;
        padding: 3px 0 !important;
    }

    /* action column */
    .gti-action-cell {
        text-align: center;
        white-space: nowrap;
        background: linear-gradient(160deg, rgba(235,245,251,.9), rgba(255,255,255,.8)) !important;
    }

    .gti-view-link {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .75rem;
        font-weight: 900;
        color: var(--group-secondary) !important;
        text-decoration: none;
        border: 1px solid var(--group-secondary-24);
        border-radius: 999px;
        padding: .34rem .72rem;
        background: rgba(255,255,255,.82);
        transition: background .15s, color .15s;
        white-space: nowrap;
    }

    .gti-view-link:hover {
        background: var(--group-secondary) !important;
        color: #fff !important;
    }

    /* group name column */
    .gti-name-cell {
        background: linear-gradient(160deg, rgba(235,245,251,.85), rgba(255,255,255,.9)) !important;
        min-width: 160px;
    }

    .gti-group-name {
        font-weight: 900;
        font-size: .92rem;
        color: var(--group-secondary);
        display: block;
        margin-bottom: .2rem;
    }

    .gti-group-code {
        font-size: .72rem;
        font-weight: 700;
        color: rgba(27,79,114,.55);
        letter-spacing: .04em;
    }

    .gti-diagnosis-badge {
        display: inline-block;
        margin-top: .25rem;
        font-size: .72rem;
        font-weight: 700;
        color: #0d6949;
        background: rgba(13,105,73,.08);
        border: 1px solid rgba(13,105,73,.2);
        border-radius: 999px;
        padding: .18rem .55rem;
        white-space: nowrap;
    }

    .gti-crud-wrap {
        display: flex;
        gap: .3rem;
        margin-top: .55rem;
        flex-wrap: wrap;
    }

    .group-icon-btn {
        width: 30px;
        height: 30px;
        padding: 0 !important;
        border-radius: 8px !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .75rem;
        background: rgba(255,255,255,.78) !important;
        transition: transform .15s, box-shadow .15s, background .15s, color .15s;
    }

    .group-icon-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(27,79,114,.12); }

    .group-view-btn, .group-edit-btn {
        border-color: var(--group-secondary-24) !important;
        color: var(--group-secondary) !important;
    }

    .group-view-btn:hover, .group-edit-btn:hover {
        background: var(--group-secondary) !important;
        color: #fff !important;
    }

    .group-delete-btn { border-color: #dc2626 !important; color: #dc2626 !important; }
    .group-delete-btn:hover { background: #dc2626 !important; color: #fff !important; }

    /* medicine sub-rows */
    .gti-med-cell   { font-weight: 700; }
    .gti-soft-cell  { color: rgba(27,79,114,.66) !important; font-weight: 600; }
    .gti-qty-cell   { text-align: center; }
    .gti-qty-badge  {
        background: var(--group-primary) !important;
        border: 1px solid var(--group-secondary-12) !important;
        color: var(--group-secondary) !important;
        border-radius: 999px;
        padding: .22rem .62rem;
        font-weight: 900;
        font-size: .8rem;
    }

    /* first medicine row — top border slightly heavier */
    .gti-first-med td { border-top: 2px solid var(--group-secondary-18) !important; }

    /* empty medicines */
    .gti-empty-med {
        color: rgba(27,79,114,.45) !important;
        font-style: italic;
        font-size: .84rem;
    }

    .group-empty-cell {
        background: var(--group-primary) !important;
        border: 1px dashed var(--group-secondary-18) !important;
        border-radius: 16px !important;
        color: var(--group-secondary) !important;
        font-weight: 800;
    }

    .group-card-footer {
        background: linear-gradient(135deg, rgba(235,245,251,.72), rgba(255,255,255,.94)) !important;
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

<div class="medmaster-tabs-row">
    <ul class="nav nav-tabs type-nav-tabs">
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
                <i class="bi bi-arrow-right-circle me-1"></i> Mode
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
    <div class="medmaster-tab-actions">
        <button type="button"
                class="btn btn-primary btn-sm med-add-btn group-add-btn"
                data-bs-toggle="modal"
                data-bs-target="#groupFormModal"
                onclick="resetGroupForm()">
            <i class="bi bi-plus-lg me-1"></i> New Group
        </button>
    </div>
</div>

<div class="card premium-card border-0 shadow-sm med-card group-card">
    <div class="medmaster-list-header">
        <h3 class="medmaster-list-title"><i class="bi bi-collection"></i> Medicine Group List</h3>
        <span class="medmaster-list-badge">{{ $groups->total() }} total</span>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive group-table-wrap">
            <table class="table mb-0 group-table">
                <thead>
                    <tr>
                        <th style="width:110px">View Group</th>
                        <th style="width:170px">Group</th>
                        <th>Medicine Name</th>
                        <th style="width:110px">Dosage</th>
                        <th style="width:90px">Days</th>
                        <th style="width:70px">Qty</th>
                        <th style="width:160px">Mode</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $i => $group)
                        @php
                            $items   = $group->items;
                            $rowspan = $items->count() ?: 1;
                            $groupRecord = [
                                'id'           => $group->id,
                                'name'         => $group->name,
                                'group_code'   => $group->group_code,
                                'diagnosis_id' => $group->diagnosis_id,
                                'items'        => $items->map(fn($it) => [
                                    'medicine_id' => $it->medicine_id,
                                    'dosage_id'   => $it->dosage_id,
                                    'route_id'    => $it->route_id,
                                    'duration'    => $it->duration,
                                    'quantity'    => $it->quantity,
                                ])->values()->toArray(),
                            ];
                        @endphp

                        @if($items->isNotEmpty())
                            @foreach($items as $j => $item)
                            <tr class="{{ $j === 0 ? 'gti-first-med' : '' }}">
                                @if($j === 0)
                                <td rowspan="{{ $rowspan }}" class="gti-action-cell text-center align-middle">
                                    <a href="{{ route('hospital.medicine-groups.show', ['slug' => $slug, 'medicine_group' => $group->id]) }}"
                                       class="gti-view-link">
                                        <i class="bi bi-eye-fill"></i> View
                                    </a>
                                </td>
                                <td rowspan="{{ $rowspan }}" class="gti-name-cell align-top pt-2">
                                    <span class="gti-group-name">{{ $group->name }}</span>
                                    @if($group->group_code)
                                        <span class="gti-group-code">{{ $group->group_code }}</span>
                                    @endif
                                    @if($group->diagnosis)
                                        <span class="gti-diagnosis-badge">
                                            <i class="bi bi-clipboard2-pulse me-1"></i>{{ $group->diagnosis->value }}
                                        </span>
                                    @endif
                                    @if(($group->usage_scope ?? 'opd') !== 'opd')
                                        <span class="badge {{ $group->usage_scope === 'ot' ? 'text-bg-warning' : 'text-bg-info' }} text-uppercase">
                                            {{ $group->usage_scope === 'ot' ? 'OT only' : 'OPD + OT' }}
                                        </span>
                                    @endif
                                    <div class="gti-crud-wrap">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary group-icon-btn group-edit-btn edit-group-modal-btn"
                                                data-record="{{ json_encode($groupRecord) }}"
                                                title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
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
                                @endif
                                <td class="gti-med-cell">{{ $item->medicine?->name ?? '—' }}</td>
                                <td class="gti-soft-cell">{{ $item->dosage?->dosage ?? '—' }}</td>
                                <td class="gti-soft-cell">{{ $item->duration ?? '—' }}</td>
                                <td class="gti-qty-cell"><span class="gti-qty-badge">{{ $item->quantity }}</span></td>
                                <td class="gti-soft-cell">{{ $item->route?->name ?? '—' }}</td>
                            </tr>
                            @endforeach
                        @else
                            <tr class="gti-first-med">
                                <td class="gti-action-cell text-center align-middle">
                                    <a href="{{ route('hospital.medicine-groups.show', ['slug' => $slug, 'medicine_group' => $group->id]) }}"
                                       class="gti-view-link">
                                        <i class="bi bi-eye-fill"></i> View
                                    </a>
                                </td>
                                <td class="gti-name-cell align-top pt-2">
                                    <span class="gti-group-name">{{ $group->name }}</span>
                                    @if($group->group_code)
                                        <span class="gti-group-code">{{ $group->group_code }}</span>
                                    @endif
                                    @if($group->diagnosis)
                                        <span class="gti-diagnosis-badge">
                                            <i class="bi bi-clipboard2-pulse me-1"></i>{{ $group->diagnosis->value }}
                                        </span>
                                    @endif
                                    @if(($group->usage_scope ?? 'opd') !== 'opd')
                                        <span class="badge {{ $group->usage_scope === 'ot' ? 'text-bg-warning' : 'text-bg-info' }} text-uppercase">
                                            {{ $group->usage_scope === 'ot' ? 'OT only' : 'OPD + OT' }}
                                        </span>
                                    @endif
                                    <div class="gti-crud-wrap">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary group-icon-btn group-edit-btn edit-group-modal-btn"
                                                data-record="{{ json_encode($groupRecord) }}"
                                                title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
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
                                <td colspan="5" class="gti-empty-med">No medicines added yet.</td>
                            </tr>
                        @endif

                        {{-- separator row between groups --}}
                        @if(!$loop->last)
                        <tr class="group-separator"><td colspan="7"></td></tr>
                        @endif

                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 group-empty-cell">
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

</div>{{-- /.medmaster-outer-card --}}
</div>

@include('hospital.medicine_groups._group_modal')

@endsection
