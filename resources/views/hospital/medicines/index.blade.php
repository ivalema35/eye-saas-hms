@extends('hospital.layouts.app')
@section('title', 'Medicines')
@section('page-header', 'Medicine Master')

@section('page-actions')
    <a href="{{ route('hospital.medicines.create', ['slug' => $slug]) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Medicine
    </a>
@endsection

@section('content')

{{-- Medicine Module Navigation --}}
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link active"
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
        <a class="nav-link"
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
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
        <h5 class="mb-0 fw-bold" style="color: var(--color-primary);">
            <i class="bi bi-capsule me-2"></i> Medicines
        </h5>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" action="{{ route('hospital.medicines.index', ['slug' => $slug]) }}"
                  class="d-flex gap-2">
                <div class="input-group" style="min-width:240px">
                    <span class="input-group-text bg-white border-end-0 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control border-start-0 ps-0 clinical-input"
                           placeholder="Search name or brand...">
                </div>
                <button type="submit" class="btn btn-outline-primary text-nowrap">Search</button>
                @if(request('search'))
                    <a href="{{ route('hospital.medicines.index', ['slug' => $slug]) }}"
                       class="btn btn-outline-secondary text-nowrap">Clear</a>
                @endif
            </form>
            <span class="badge text-bg-light border">{{ $medicines->total() }} total</span>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table premium-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Medicine Name</th>
                        <th>Brand Name</th>
                        <th>Company</th>
                        <th>Price (₹)</th>
                        <th>Type</th>
                        <th class="text-end" style="width:120px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($medicines as $i => $med)
                    <tr>
                        <td class="text-muted">{{ $medicines->firstItem() + $i }}</td>
                        <td class="fw-semibold">{{ $med->name }}</td>
                        <td class="text-muted">{{ $med->brand_name ?: '—' }}</td>
                        <td class="text-muted">{{ $med->company ?: '—' }}</td>
                        <td>{{ $med->price !== null ? '₹'.number_format($med->price, 2) : '—' }}</td>
                        <td>
                            @if($med->medicineType)
                                <span class="badge text-bg-light border">{{ $med->medicineType->name }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1 action-btn-group">
                                <a href="{{ route('hospital.medicines.edit', ['slug' => $slug, 'medicine' => $med->id]) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Edit">
                                    <i class="bi bi-pencil-fill" style="color: var(--color-secondary);"></i>
                                </a>
                                <form action="{{ route('hospital.medicines.destroy', ['slug' => $slug, 'medicine' => $med->id]) }}"
                                      method="POST"
                                      onsubmit="return confirm('Delete this medicine?')">
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
                            <i class="bi bi-capsule fs-2 d-block mb-2 opacity-25"></i>
                            No medicines found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($medicines->hasPages())
    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center">
        <small class="text-muted">
            Showing {{ $medicines->firstItem() }}–{{ $medicines->lastItem() }} of {{ $medicines->total() }}
        </small>
        {{ $medicines->links() }}
    </div>
    @endif
</div>

@endsection

