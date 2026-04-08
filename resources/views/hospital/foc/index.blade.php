@extends('hospital.layouts.app')
@section('title', 'FOC Requests')
@section('page-header', 'Free of Charge Requests')

@push('styles')
<style>
.foc-premium-card {
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border: 1px solid #E2E8F0;
    overflow: hidden;
}
.foc-premium-head {
    background: #1B4F72;
    color: #fff;
}
.foc-premium-table {
    font-family: 'Inter', sans-serif;
    margin-bottom: 0;
}
.foc-premium-table thead {
    background: #1B4F72 !important;
}
.foc-premium-table thead th {
    background: #1B4F72 !important;
    color: #fff !important;
    border-bottom: none !important;
    font-size: .78rem;
    letter-spacing: .04em;
    font-weight: 700;
    padding-top: .9rem;
    padding-bottom: .9rem;
}
.foc-premium-table tbody td {
    padding-top: .95rem;
    padding-bottom: .95rem;
    vertical-align: middle;
}
.foc-premium-table tbody tr:hover {
    background: #F8FBFE;
}
.foc-badge-pending {
    background: #FDEBD0;
    color: #784212;
    border-radius: 999px;
    padding: .25rem .6rem;
    font-size: .75rem;
    font-weight: 600;
}
.foc-badge-accepted {
    background: #D5F5E3;
    color: #1A6F5B;
    border-radius: 999px;
    padding: .25rem .6rem;
    font-size: .75rem;
    font-weight: 600;
}
.foc-view-btn {
    border: 1px solid #1B4F72;
    color: #1B4F72;
    background: transparent;
    border-radius: 8px;
}
.foc-accept-btn {
    background: #27AE60;
    border-color: #27AE60;
    border-radius: 8px;
}
.foc-actions-cell {
    white-space: nowrap;
    text-align: center;
}
</style>
@endpush

@section('page-actions')
    <a href="{{ route('hospital.foc.create', ['slug' => $slug]) }}" class="hms-btn" style="background:#1B4F72;color:#fff;border-color:#1B4F72;border-radius:8px">
        <i class="fa-solid fa-plus"></i> New FOC Request
    </a>
@endsection

@section('content')

<div class="hms-card foc-premium-card">
    <div class="hms-card-header foc-premium-head">
        <h3 class="hms-card-title">
            <i class="fa-solid fa-hand-holding-heart"></i> FOC Requests
        </h3>
        <span class="hms-badge" style="background:#fff;color:#1B4F72">{{ $focs->count() }} total</span>
    </div>
    <div class="hms-card-body" style="padding:0">
        <table class="hms-table foc-premium-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Patient Name</th>
                    <th>Doctor Name</th>
                    <th>Fee</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($focs as $i => $foc)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $foc->patient?->full_name ?? '—' }}</td>
                        <td>{{ $foc->doctor?->name ?? '—' }}</td>
                        <td>{{ $foc->foc_fee ? '₹'.number_format($foc->foc_fee, 2) : '—' }}</td>
                        <td class="foc-actions-cell">
                            @if($foc->isAccepted())
                                <span class="foc-badge-accepted">Accepted</span>
                            @elseif($foc->isRejected())
                                <span class="hms-badge hms-badge-danger">Rejected</span>
                            @else
                                <span class="foc-badge-pending">Pending</span>
                            @endif
                        </td>
                        <td>{{ $foc->created_at?->format('d M Y, h:i A') ?? '—' }}</td>
                        <td>
                            <button type="button" class="hms-btn hms-btn-sm foc-view-btn" data-bs-toggle="modal" data-bs-target="#focIndexViewModal{{ $foc->id }}">
                                <i class="fa-solid fa-eye"></i> View
                            </button>

                            @if($foc->isPending())
                                @haspermission('opd.foc.accept')
                                    <form method="POST" action="{{ route('hospital.foc.accept', ['slug' => $slug, 'id' => $foc->id]) }}" style="display:inline">
                                        @csrf
                                        <button type="submit" class="hms-btn hms-btn-sm hms-btn-success foc-accept-btn">Accept</button>
                                    </form>
                                @endhaspermission
                            @endif

                            <div class="modal fade" id="focIndexViewModal{{ $foc->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">FOC Request Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="mb-1"><strong>Patient:</strong> {{ $foc->patient?->full_name ?? '—' }}</p>
                                            <p class="mb-1"><strong>MRD:</strong> {{ $foc->patient?->patient_code ?? '—' }}</p>
                                            <p class="mb-1"><strong>Doctor:</strong> {{ $foc->doctor?->name ?? '—' }}</p>
                                            <p class="mb-1"><strong>Requested Fee:</strong> ₹{{ number_format((float) $foc->foc_fee, 2) }}</p>
                                            <p class="mb-1"><strong>Status:</strong> {{ ucfirst($foc->status ?? 'pending') }}</p>
                                            <p class="mb-0"><strong>Reason:</strong><br>{{ $foc->reason ?: 'No reason provided.' }}</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="hms-btn hms-btn-sm hms-btn-outline" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:3rem 1rem;text-align:center">
                            <x-empty-state
                                icon="fa-solid fa-hand-holding-heart"
                                title="No FOC requests"
                                description="Free-of-charge requests submitted for patients will appear here."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
