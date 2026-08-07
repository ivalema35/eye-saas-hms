@extends('hospital.layouts.app')
@section('title', 'Discharge & Invoices')
@section('page-header', 'Discharge & Invoices')

@push('styles')
<style>
    .ot-billing-page {
        --otb-primary: #ebf5fbeb;
        --otb-secondary: #1B4F72;
        --otb-secondary-08: rgba(27, 79, 114, .08);
        --otb-secondary-12: rgba(27, 79, 114, .12);
        --otb-secondary-18: rgba(27, 79, 114, .18);
        --otb-secondary-24: rgba(27, 79, 114, .24);
    }

    .otb-action-group {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: .45rem;
        flex-wrap: wrap;
    }

    .otb-print-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .42rem;
        min-height: 36px;
        border-radius: 999px !important;
        border: 1px solid var(--otb-secondary-18) !important;
        background: #ffffff !important;
        color: var(--otb-secondary) !important;
        font-weight: 900 !important;
        padding: .4rem .82rem !important;
        line-height: 1;
        box-shadow: 0 8px 18px rgba(27, 79, 114, .08);
        transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, border-color 170ms ease, color 170ms ease;
    }

    .otb-print-btn i {
        width: 24px;
        height: 24px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 0 !important;
        background: var(--otb-primary);
        color: var(--otb-secondary);
        font-size: .88rem;
        transition: background 170ms ease, color 170ms ease;
    }

    .otb-print-btn:hover,
    .otb-print-btn:focus {
        transform: translateY(-1px);
        background: var(--otb-secondary) !important;
        border-color: var(--otb-secondary) !important;
        color: #ffffff !important;
        box-shadow: 0 12px 26px var(--otb-secondary-24);
    }

    .otb-print-btn:hover i,
    .otb-print-btn:focus i {
        background: rgba(255, 255, 255, .18);
        color: #ffffff;
    }

    .otb-print-btn-discharge {
        background: var(--otb-primary) !important;
    }

    .otb-print-btn-discharge:hover,
    .otb-print-btn-discharge:focus {
        background: #ffffff !important;
        color: var(--otb-secondary) !important;
        box-shadow: 0 12px 26px var(--otb-secondary-18);
    }

    .otb-print-btn-discharge:hover i,
    .otb-print-btn-discharge:focus i {
        background: var(--otb-secondary);
        color: #ffffff;
    }
</style>
@endpush

@section('content')
<div class="ot-billing-page">
<div class="card border-0 shadow-sm otb-card">
    <div class="card-header bg-white border-bottom otb-card-header d-flex justify-content-between align-items-center flex-wrap">
        <div class="otb-title-wrap">
            <span class="otb-title-icon">
                <i class="bi bi-receipt fs-4"></i>
            </span>
            <div>
                <h5 class="mb-0 fw-bold otb-title" style="color: var(--color-primary);">
                    Billing Desk
                </h5>
                <div class="otb-subtitle">Generate invoices and print discharge documents.</div>
            </div>
        </div>
        <span class="otb-total-pill">{{ $bookings->total() }} total</span>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive otb-table-wrap">
            <table class="table table-hover align-middle mb-0 otb-table">
                <thead class="table-light">
                    <tr>
                        <th>Patient</th>
                        <th>OT Date</th>
                        <th>Status</th>
                        <th>Invoice</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        @php
                            $hasInvoice = in_array((int) $booking->id, $invoiceBookingIds, true);
                        @endphp
                        <tr>
                            <td><span class="otb-patient-cell"><i class="bi bi-person-fill"></i>{{ $booking->patient?->full_name ?? '-' }}</span></td>
                            <td><span class="otb-date-cell"><i class="bi bi-calendar2-event"></i>{{ optional($booking->surgery_date)->format('d M Y') }}</span></td>
                            <td><span class="badge text-bg-secondary otb-status-badge">{{ strtoupper((string) $booking->ot_status) }}</span></td>
                            <td>
                                @if($hasInvoice)
                                    <span class="badge text-bg-success otb-invoice-badge">Generated</span>
                                @else
                                    <span class="badge text-bg-warning otb-invoice-badge">Pending</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if(!$hasInvoice)
                                    <form method="POST" action="{{ route('hospital.ot.invoice.generate', ['slug' => $slug, 'bookingId' => $booking->id]) }}" class="d-inline-flex align-items-center gap-2">
                                        @csrf
                                        <input type="date" name="follow_up_date" class="form-control form-control-sm" style="width:auto;"
                                               value="{{ now()->addDays(7)->format('Y-m-d') }}" title="Follow-up date">
                                        <button type="submit" class="btn btn-sm btn-primary otb-generate-btn">
                                            <i class="bi bi-file-earmark-plus me-1"></i> Generate
                                        </button>
                                    </form>
                                @endif

                                @if($hasInvoice)
                                    <div class="otb-action-group">
                                        <a href="{{ route('hospital.ot.summary-bill.print', ['slug' => $slug, 'bookingId' => $booking->id]) }}" class="btn btn-sm otb-print-btn">
                                            <i class="bi bi-receipt-cutoff"></i> Bill Summary
                                        </a>
                                        <a href="{{ route('hospital.ot.discharge.print', ['slug' => $slug, 'bookingId' => $booking->id]) }}" class="btn btn-sm otb-print-btn otb-print-btn-discharge">
                                            <i class="bi bi-file-medical"></i> Discharge
                                        </a>
                                        <a href="{{ route('hospital.ot.certificate.print', ['slug' => $slug, 'bookingId' => $booking->id]) }}" class="btn btn-sm otb-print-btn">
                                            <i class="bi bi-patch-check"></i> Certificate
                                        </a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4 otb-empty-cell">No records available for billing.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($bookings->hasPages())
    <div class="otb-pagination">
        {{ $bookings->links() }}
    </div>
@endif
</div>
@endsection
