@extends('hospital.layouts.app')
@section('title', 'OT Reception Dashboard')
@section('page-header', 'OT Reception Dashboard')

@section('page-actions')
    <a href="{{ route('hospital.ot.bookings.create', ['slug' => $slug]) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> New OT Booking
    </a>
@endsection

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Total OT Today</p>
                <h3 class="fw-bold mb-0">{{ number_format((int) ($stats['total_ot_today'] ?? 0)) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Pending Counselling</p>
                <h3 class="fw-bold mb-0">{{ number_format((int) ($stats['pending_counselling'] ?? 0)) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Ready For Surgery</p>
                <h3 class="fw-bold mb-0">{{ number_format((int) ($stats['ready_for_surgery'] ?? 0)) }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
        <p class="mb-0 text-muted">Manage OT bookings and counselling from a single workspace.</p>
        <a href="{{ route('hospital.ot.bookings.index', ['slug' => $slug]) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-list-check me-1"></i> View Bookings
        </a>
    </div>
</div>
@endsection
