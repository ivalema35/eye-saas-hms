@extends('hospital.layouts.app')
@section('title', 'FOC Requests')
@section('page-header', 'Free of Charge Requests')

@push('styles')
<style>
.foc-index-page {
    --foc-primary: #ebf5fbeb;
    --foc-secondary: #1B4F72;
    --foc-secondary-08: rgba(27, 79, 114, 0.08);
    --foc-secondary-12: rgba(27, 79, 114, 0.12);
    --foc-secondary-18: rgba(27, 79, 114, 0.18);
    --foc-secondary-24: rgba(27, 79, 114, 0.24);
    color: var(--foc-secondary);
    animation: foc-page-in 420ms ease both;
}

.foc-page-action {
    background: var(--foc-secondary) !important;
    color: #fff !important;
    border-color: var(--foc-secondary) !important;
    border-radius: 12px !important;
    font-weight: 800;
    box-shadow: 0 12px 26px rgba(27, 79, 114, .16);
}

.foc-page-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 16px 34px rgba(27, 79, 114, .22);
    text-decoration: none;
}

.foc-premium-card {
    border-radius: 22px;
    box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
    border: 1px solid var(--foc-secondary-12);
    overflow: visible;
    background: rgba(255, 255, 255, .84);
    padding: 0;
    animation: foc-card-rise 520ms cubic-bezier(.2,.9,.2,1) both;
}

.foc-premium-head {
    background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94));
    color: var(--foc-secondary);
    border-bottom: 1px solid var(--foc-secondary-12);
    padding: 1.1rem 1.25rem;
}

.foc-title-wrap {
    display: flex;
    align-items: center;
    gap: .85rem;
}

.foc-title-icon {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    background: var(--foc-secondary);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 14px 30px rgba(27, 79, 114, .22);
}

.foc-premium-head .hms-card-title {
    color: var(--foc-secondary);
    font-weight: 900;
    letter-spacing: -0.2px;
}

.foc-total-badge {
    background: rgba(255, 255, 255, .78) !important;
    color: var(--foc-secondary) !important;
    border: 1px solid var(--foc-secondary-12);
    border-radius: 999px;
    padding: .52rem .85rem;
    font-weight: 900;
    box-shadow: 0 10px 22px rgba(27, 79, 114, .08);
}

.foc-table-wrap {
    padding: .9rem !important;
    overflow-x: auto;
}

.foc-table-wrap:has(.modal.show) {
    overflow: visible;
}

.foc-premium-table {
    font-family: 'Inter', sans-serif;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0 8px;
    min-width: 920px;
}

.foc-premium-table thead,
.foc-premium-table thead th {
    background: var(--foc-secondary) !important;
}

.foc-premium-table thead th {
    color: #fff !important;
    border-bottom: none !important;
    font-size: .72rem;
    letter-spacing: .08em;
    font-weight: 900;
    text-transform: uppercase;
    padding-top: .9rem;
    padding-bottom: .9rem;
    white-space: nowrap;
}

.foc-premium-table thead th:first-child {
    border-top-left-radius: 14px;
    border-bottom-left-radius: 14px;
}

.foc-premium-table thead th:last-child {
    border-top-right-radius: 14px;
    border-bottom-right-radius: 14px;
}

.foc-premium-table tbody tr {
    animation: foc-row-in 460ms ease both;
    transition: transform 170ms ease, box-shadow 170ms ease;
}

.foc-premium-table tbody tr:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 34px rgba(27, 79, 114, .10);
}

.foc-premium-table tbody td {
    background: rgba(255, 255, 255, .90);
    border-top: 1px solid var(--foc-secondary-08);
    border-bottom: 1px solid var(--foc-secondary-08);
    color: var(--foc-secondary);
    padding: .95rem .875rem;
    vertical-align: middle;
    font-weight: 650;
}

.foc-premium-table tbody td:first-child {
    border-left: 1px solid var(--foc-secondary-08);
    border-top-left-radius: 14px;
    border-bottom-left-radius: 14px;
    font-weight: 900;
    color: rgba(27, 79, 114, .58);
}

.foc-premium-table tbody td:last-child {
    border-right: 1px solid var(--foc-secondary-08);
    border-top-right-radius: 14px;
    border-bottom-right-radius: 14px;
}

.foc-person-cell {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
}

.foc-person-cell i,
.foc-fee-pill i {
    color: var(--foc-secondary);
}

.foc-fee-pill {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    background: var(--foc-primary);
    border: 1px solid var(--foc-secondary-12);
    border-radius: 999px;
    padding: .35rem .7rem;
    font-weight: 900;
}

.foc-badge-pending,
.foc-badge-accepted,
.foc-index-page .hms-badge-danger {
    border-radius: 999px;
    padding: .34rem .72rem;
    font-size: .75rem;
    font-weight: 900;
}

.foc-badge-pending {
    background: rgba(230, 126, 34, .14);
    color: #784212;
    border: 1px solid rgba(230, 126, 34, .22);
}

.foc-badge-accepted {
    background: rgba(39, 174, 96, .14);
    color: #1A6F5B;
    border: 1px solid rgba(39, 174, 96, .20);
}

.foc-view-btn,
.foc-accept-btn {
    border-radius: 999px;
    font-weight: 900;
    transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, color 170ms ease;
}

.foc-view-btn {
    border: 1px solid var(--foc-secondary-24) !important;
    color: var(--foc-secondary) !important;
    background: rgba(255, 255, 255, .78) !important;
}

.foc-accept-btn {
    background: var(--foc-secondary) !important;
    border-color: var(--foc-secondary) !important;
    color: #fff !important;
}

.foc-view-btn:hover,
.foc-accept-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 26px rgba(27, 79, 114, .14);
}

.foc-view-btn:hover {
    background: var(--foc-secondary) !important;
    color: #fff !important;
}

.foc-actions-cell {
    white-space: nowrap;
    text-align: center;
}

.foc-action-buttons {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    flex-wrap: wrap;
}

.foc-empty-cell {
    padding: 3rem 1rem !important;
    text-align: center;
    background: var(--foc-primary) !important;
    border: 1px dashed var(--foc-secondary-18) !important;
    border-radius: 16px !important;
}

.foc-view-modal .modal-content {
    border: 1px solid var(--foc-secondary-12);
    border-radius: 22px;
    box-shadow: 0 22px 60px rgba(27, 79, 114, .18);
    overflow: hidden;
    background: rgba(255, 255, 255, .98);
}

.foc-view-modal {
    position: fixed;
    z-index: 1060;
}

.foc-view-modal .modal-header {
    background: #1B4F72 !important;
    color: #fff;
    border-bottom: 0;
    padding: 1.15rem 1.25rem;
}

.foc-view-modal .modal-title {
    display: inline-flex;
    align-items: center;
    gap: .65rem;
    font-weight: 900;
    letter-spacing: -.15px;
    color: #ffffff !important;
    opacity: 1 !important;
}

.foc-view-modal .modal-title,
.foc-view-modal .modal-title * {
    color: #ffffff !important;
}

.foc-modal-title-icon {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, .16);
    border: 1px solid rgba(255, 255, 255, .18);
}

.foc-view-modal .modal-body {
    color: var(--foc-secondary);
    padding: 1.15rem;
    background: linear-gradient(135deg, rgba(235, 245, 251, .52), rgba(255, 255, 255, .94));
}

.foc-view-modal .modal-body p {
    margin: 0;
}

.foc-view-modal .modal-footer {
    border-top: 1px solid var(--foc-secondary-12);
    padding: 1rem 1.15rem;
    background: rgba(255, 255, 255, .96);
}

.foc-modal-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .75rem;
}

.foc-modal-field {
    background: rgba(255, 255, 255, .92);
    border: 1px solid var(--foc-secondary-08);
    border-radius: 16px;
    padding: .78rem .85rem;
}

.foc-modal-field-label {
    display: flex;
    align-items: center;
    gap: .4rem;
    color: rgba(27, 79, 114, .62);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
    margin-bottom: .32rem;
}

.foc-modal-field-value {
    color: var(--foc-secondary);
    font-weight: 850;
    line-height: 1.35;
}

.foc-modal-fee {
    background: rgba(39, 174, 96, .10);
    border-color: rgba(39, 174, 96, .20);
}

.foc-modal-fee .foc-modal-field-value {
    color: #1A6F5B;
    font-size: 1.05rem;
    font-weight: 950;
}

.foc-modal-reason {
    grid-column: 1 / -1;
}

.foc-modal-reason-text {
    white-space: pre-wrap;
    font-weight: 750;
    color: rgba(27, 79, 114, .82);
}

.foc-modal-status-pill {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: .32rem .68rem;
    background: rgba(230, 126, 34, .14);
    border: 1px solid rgba(230, 126, 34, .24);
    color: #784212;
    font-weight: 900;
}

.foc-modal-close-btn {
    border-radius: 999px !important;
    padding: .52rem 1rem !important;
    font-weight: 900;
    border-color: var(--foc-secondary-24) !important;
    color: var(--foc-secondary) !important;
    background: rgba(255, 255, 255, .9) !important;
}

.foc-modal-close-btn:hover,
.foc-modal-close-btn:focus,
.foc-modal-close-btn:active {
    background: var(--foc-secondary) !important;
    border-color: var(--foc-secondary) !important;
    color: #fff !important;
    box-shadow: none !important;
}

.foc-modal-close-btn:hover i,
.foc-modal-close-btn:focus i,
.foc-modal-close-btn:active i {
    color: #fff !important;
}

.foc-view-modal .modal-footer .foc-modal-close-btn,
.foc-view-modal .modal-footer .foc-modal-close-btn:visited {
    background: #ffffff !important;
    border: 1px solid #1B4F72 !important;
    color: #1B4F72 !important;
}

.foc-view-modal .modal-footer .foc-modal-close-btn:hover,
.foc-view-modal .modal-footer .foc-modal-close-btn:focus,
.foc-view-modal .modal-footer .foc-modal-close-btn:active {
    background: #1B4F72 !important;
    border-color: #1B4F72 !important;
    color: #ffffff !important;
    opacity: 1 !important;
    text-decoration: none !important;
}

.foc-view-modal .modal-footer .foc-modal-close-btn:hover i,
.foc-view-modal .modal-footer .foc-modal-close-btn:focus i,
.foc-view-modal .modal-footer .foc-modal-close-btn:active i {
    color: #ffffff !important;
}

@keyframes foc-page-in {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: none; }
}

@keyframes foc-card-rise {
    from { opacity: 0; transform: translateY(12px) scale(.99); }
    to { opacity: 1; transform: none; }
}

@keyframes foc-row-in {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
    .foc-index-page,
    .foc-premium-card,
    .foc-premium-table tbody tr {
        animation: none;
    }

    .foc-index-page * {
        transition: none !important;
    }
}

@media (max-width: 576px) {
    .foc-premium-head {
        align-items: flex-start;
        gap: 1rem;
    }

    .foc-title-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
    }

    .foc-modal-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush

@section('page-actions')
    <a href="{{ route('hospital.foc.create', ['slug' => $slug]) }}" class="hms-btn foc-page-action" style="background:#1B4F72;color:#fff;border-color:#1B4F72;border-radius:8px">
        <i class="bi bi-plus-lg"></i> New FOC Request
    </a>
@endsection

@section('content')

<div class="foc-index-page">
    <div class="hms-card foc-premium-card">
        <div class="hms-card-header foc-premium-head">
            <div class="foc-title-wrap">
                <span class="foc-title-icon">
                    <i class="bi bi-heart-pulse-fill fs-4"></i>
                </span>
                <h3 class="hms-card-title mb-0">FOC Requests</h3>
            </div>
            <span class="hms-badge foc-total-badge" style="background:#fff;color:#1B4F72">{{ $focs->count() }} total</span>
        </div>
        <div class="hms-card-body foc-table-wrap" style="padding:0">
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
                            <td><span class="foc-person-cell"><i class="bi bi-person-fill"></i>{{ $foc->patient?->full_name ?? '—' }}</span></td>
                            <td><span class="foc-person-cell"><i class="bi bi-person-badge-fill"></i>{{ $foc->doctor?->name ?? '—' }}</span></td>
                            <td><span class="foc-fee-pill"><i class="bi bi-currency-rupee"></i>{{ $foc->foc_fee ? '₹'.number_format($foc->foc_fee, 2) : '—' }}</span></td>
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
                                <div class="foc-action-buttons">
                                    <button type="button" class="hms-btn hms-btn-sm foc-view-btn" data-bs-toggle="modal" data-bs-target="#focIndexViewModal{{ $foc->id }}">
                                        <i class="bi bi-eye-fill"></i> View
                                    </button>

                                    @if($foc->isPending())
                                        @haspermission('opd.foc.accept')
                                            <form method="POST" action="{{ route('hospital.foc.accept', ['slug' => $slug, 'id' => $foc->id]) }}" style="display:inline">
                                                @csrf
                                                <button type="submit" class="hms-btn hms-btn-sm hms-btn-success foc-accept-btn">
                                                    <i class="bi bi-check2-circle"></i> Accept
                                                </button>
                                            </form>
                                        @endhaspermission
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="foc-empty-cell" style="padding:3rem 1rem;text-align:center">
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
</div>

@foreach($focs as $foc)
    <div class="modal fade foc-view-modal" id="focIndexViewModal{{ $foc->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <span class="foc-modal-title-icon"><i class="bi bi-heart-pulse-fill"></i></span>
                        FOC Request Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="foc-modal-grid">
                        <div class="foc-modal-field">
                            <div class="foc-modal-field-label"><i class="bi bi-person-fill"></i> Patient</div>
                            <div class="foc-modal-field-value">{{ $foc->patient?->full_name ?? '—' }}</div>
                        </div>
                        <div class="foc-modal-field">
                            <div class="foc-modal-field-label"><i class="bi bi-upc-scan"></i> MRD</div>
                            <div class="foc-modal-field-value">{{ $foc->patient?->patient_code ?? '—' }}</div>
                        </div>
                        <div class="foc-modal-field">
                            <div class="foc-modal-field-label"><i class="bi bi-person-badge-fill"></i> Doctor</div>
                            <div class="foc-modal-field-value">{{ $foc->doctor?->name ?? '—' }}</div>
                        </div>
                        <div class="foc-modal-field foc-modal-fee">
                            <div class="foc-modal-field-label"><i class="bi bi-currency-rupee"></i> Requested Fee</div>
                            <div class="foc-modal-field-value">₹{{ number_format((float) $foc->foc_fee, 2) }}</div>
                        </div>
                        <div class="foc-modal-field">
                            <div class="foc-modal-field-label"><i class="bi bi-activity"></i> Status</div>
                            <div class="foc-modal-field-value">
                                <span class="foc-modal-status-pill">{{ ucfirst($foc->status ?? 'pending') }}</span>
                            </div>
                        </div>
                        <div class="foc-modal-field foc-modal-reason">
                            <div class="foc-modal-field-label"><i class="bi bi-chat-left-text-fill"></i> Reason</div>
                            <div class="foc-modal-reason-text">{{ $foc->reason ?: 'No reason provided.' }}</div>
                        </div>
                    </div>
                </div>
               
            </div>
        </div>
    </div>
@endforeach

@endsection
