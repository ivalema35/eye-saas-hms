@extends('hospital.layouts.app')
@section('title', $group->name)
@section('page-header', $group->name)

@section('page-actions')
    <button type="button"
            class="btn btn-outline-primary btn-sm group-show-action-btn group-show-edit-btn"
            onclick="openGroupEditModal(@js($group))">
        <i class="bi bi-pencil me-1"></i> Edit Group
    </button>
    <a href="{{ route('hospital.medicine-groups.index', ['slug' => $slug]) }}"
       class="btn btn-outline-secondary btn-sm group-show-action-btn group-show-back-btn">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
@endsection

@section('content')

<div class="medicine-group-show-page medicines-page">

<style>
    .medicine-group-show-page {
        --group-primary: #ebf5fbeb;
        --group-secondary: #1B4F72;
        --group-secondary-08: rgba(27, 79, 114, .08);
        --group-secondary-12: rgba(27, 79, 114, .12);
        --group-secondary-18: rgba(27, 79, 114, .18);
        --group-secondary-24: rgba(27, 79, 114, .24);
        color: var(--group-secondary);
        animation: group-show-in 420ms ease both;
    }

    .group-show-action-btn {
        border-radius: 12px;
        font-weight: 850;
        padding: .42rem .82rem;
    }

    .group-show-edit-btn {
        border-color: var(--group-secondary-24) !important;
        color: var(--group-secondary) !important;
        background: rgba(255, 255, 255, .78) !important;
    }

    .group-show-edit-btn:hover {
        background: var(--group-secondary) !important;
        border-color: var(--group-secondary) !important;
        color: #fff !important;
    }

    .group-show-back-btn {
        border-color: var(--group-secondary-18) !important;
        color: rgba(27, 79, 114, .76) !important;
        background: rgba(255, 255, 255, .78) !important;
    }

    .group-show-card {
        border: 1px solid var(--group-secondary-12) !important;
        border-radius: 22px;
        background: rgba(255, 255, 255, .86);
        box-shadow: 0 18px 48px rgba(27, 79, 114, .10) !important;
        overflow: hidden;
        animation: group-show-card-rise 520ms cubic-bezier(.2,.9,.2,1) both;
    }

    .group-show-card .card-header {
        background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94)) !important;
        border-bottom: 1px solid var(--group-secondary-12) !important;
        padding: 1.2rem 1.25rem !important;
    }

    .group-show-title-wrap {
        display: flex;
        align-items: center;
        gap: .85rem;
    }

    .group-show-title-icon {
        width: 50px;
        height: 50px;
        border-radius: 16px;
        background: var(--group-secondary);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 14px 30px rgba(27, 79, 114, .22);
        flex: 0 0 auto;
    }

    .group-show-title {
        color: var(--group-secondary) !important;
        font-weight: 900;
        letter-spacing: -.2px;
    }

    .group-show-subtitle {
        color: rgba(27, 79, 114, .68);
        font-size: .84rem;
        font-weight: 650;
        margin-top: .15rem;
    }

    .group-show-count-pill {
        background: rgba(255, 255, 255, .78) !important;
        color: var(--group-secondary) !important;
        border: 1px solid var(--group-secondary-12) !important;
        border-radius: 999px;
        padding: .52rem .85rem;
        font-weight: 900;
        box-shadow: 0 10px 22px rgba(27, 79, 114, .08);
    }

    .group-show-table-wrap {
        padding: .9rem;
        overflow-x: auto;
    }

    .group-show-table {
        border-collapse: separate;
        border-spacing: 0 8px;
        min-width: 820px;
    }

    .group-show-table thead tr,
    .group-show-table thead th {
        background: var(--group-secondary) !important;
    }

    .group-show-table thead th {
        color: #fff !important;
        border: 0 !important;
        padding: .9rem 1rem;
        font-size: .72rem;
        letter-spacing: .08em;
        font-weight: 900;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .group-show-table thead th:first-child {
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .group-show-table thead th:last-child {
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .group-show-table tbody tr {
        animation: group-show-row-in 460ms ease both;
        transition: transform 170ms ease, box-shadow 170ms ease;
    }

    .group-show-table tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 34px rgba(27, 79, 114, .10);
    }

    .group-show-table tbody td {
        background: rgba(255, 255, 255, .92);
        border-top: 1px solid var(--group-secondary-08);
        border-bottom: 1px solid var(--group-secondary-08);
        color: var(--group-secondary);
        padding: .9rem 1rem;
        vertical-align: middle;
        font-weight: 650;
    }

    .group-show-table tbody td:first-child {
        border-left: 1px solid var(--group-secondary-08);
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .group-show-table tbody td:last-child {
        border-right: 1px solid var(--group-secondary-08);
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .group-show-index-cell {
        color: rgba(27, 79, 114, .58) !important;
        font-weight: 900;
    }

    .group-show-medicine-cell {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        color: var(--group-secondary);
        font-weight: 850;
    }

    .group-show-soft-cell {
        color: rgba(27, 79, 114, .66) !important;
        font-weight: 750;
    }

    .group-show-pill {
        background: var(--group-primary) !important;
        border: 1px solid var(--group-secondary-12) !important;
        color: var(--group-secondary) !important;
        border-radius: 999px;
        padding: .36rem .72rem;
        font-weight: 900;
    }

    @keyframes group-show-in {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes group-show-card-rise {
        from { opacity: 0; transform: translateY(12px) scale(.99); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes group-show-row-in {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (prefers-reduced-motion: reduce) {
        .medicine-group-show-page,
        .group-show-card,
        .group-show-table tbody tr {
            animation: none;
        }

        .medicine-group-show-page * {
            transition: none !important;
        }
    }

    @media (max-width: 576px) {
        .group-show-card .card-header {
            align-items: flex-start;
        }

        .group-show-title-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
        }
    }
</style>

<div class="card premium-card border-0 shadow-sm group-show-card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="group-show-title-wrap">
            <span class="group-show-title-icon">
                <i class="bi bi-collection fs-4"></i>
            </span>
            <div>
                <h5 class="mb-0 fw-bold group-show-title" style="color: var(--color-primary);">{{ $group->name }}</h5>
                @if($group->group_code)
                    <div class="group-show-subtitle">{{ $group->group_code }}</div>
                @endif
                @if($group->diagnosis)
                    <div class="mt-1">
                        <span style="font-size:.75rem;font-weight:700;color:#0d6949;background:rgba(13,105,73,.08);border:1px solid rgba(13,105,73,.2);border-radius:999px;padding:.18rem .6rem;">
                            <i class="bi bi-clipboard2-pulse me-1"></i>{{ $group->diagnosis->value }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
        <span class="badge text-bg-light border group-show-count-pill">{{ $group->items->count() }} medicines</span>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive group-show-table-wrap">
            <table class="table premium-table align-middle mb-0 group-show-table">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Medicine Name</th>
                        <th>Dosage</th>
                        <th>Days</th>
                        <th class="text-center" style="width:80px">Qty</th>
                        <th>Route of Administration</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group->items as $i => $item)
                    <tr>
                        <td class="text-muted group-show-index-cell">{{ $i + 1 }}</td>
                        <td class="fw-semibold">
                            <span class="group-show-medicine-cell">
                                {{ $item->medicine?->name ?? '—' }}
                            </span>
                        </td>
                        <td class="text-muted group-show-soft-cell">{{ $item->dosage?->dosage ?? '—' }}</td>
                        <td class="group-show-soft-cell">{{ $item->duration ?? '—' }}</td>
                        <td class="text-center">
                            <span class="badge text-bg-light border group-show-pill">{{ $item->quantity }}</span>
                        </td>
                        <td class="group-show-soft-cell">{{ $item->route?->name ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('hospital.medicine_groups._group_modal')

</div>

@endsection
