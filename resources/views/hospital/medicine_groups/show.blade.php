@extends('hospital.layouts.app')
@section('title', $group->name)
@section('page-header', $group->name)

@section('page-actions')
    <a href="{{ route('hospital.medicine-groups.edit', ['slug' => $slug, 'medicine_group' => $group->id]) }}"
       class="btn btn-outline-primary btn-sm">
        <i class="bi bi-pencil me-1"></i> Edit Group
    </a>
    <a href="{{ route('hospital.medicine-groups.index', ['slug' => $slug]) }}"
       class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
@endsection

@section('content')

<div class="card premium-card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <div class="section-icon-box bg-primary-subtle text-primary">
            <i class="bi bi-collection fs-5"></i>
        </div>
        <div>
            <h5 class="mb-0 fw-bold" style="color: var(--color-primary);">{{ $group->name }}</h5>
            <small class="text-muted">{{ $group->items->count() }} medicines in this group</small>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table premium-table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Medicine</th>
                        <th>Dosage</th>
                        <th>Frequency</th>
                        <th>Duration</th>
                        <th class="text-center" style="width:80px">Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group->items as $i => $item)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $item->medicine?->name ?? '—' }}</td>
                        <td class="text-muted">{{ $item->dosage?->dosage ?? '—' }}</td>
                        <td>{{ $item->frequency }}</td>
                        <td>{{ $item->duration }}</td>
                        <td class="text-center">
                            <span class="badge text-bg-light border">{{ $item->quantity }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
