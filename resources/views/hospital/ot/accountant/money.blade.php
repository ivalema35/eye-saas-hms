@extends('hospital.layouts.app')
@section('title', 'OT Money — Collected vs Refunded')
@section('page-header', 'OT Money Report')

@section('page-actions')
    <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
        <i class="bi bi-arrow-left me-1"></i> Back to Accountant
    </a>
@endsection

@section('content')
<div class="mb-4">
    <form method="GET" action="{{ route('hospital.ot.accountant.money', ['slug' => $slug]) }}" class="row g-2 align-items-end">
        <div class="col-auto">
            <label class="form-label small mb-0">From</label>
            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
        </div>
        <div class="col-auto">
            <label class="form-label small mb-0">To</label>
            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
        </div>
        <div class="col-auto">
            <button type="submit" class="hms-btn hms-btn-primary">Apply</button>
        </div>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Collected</div>
                <div class="fs-4 fw-bold text-success">{{ money_code($collected, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Returned (Refunds)</div>
                <div class="fs-4 fw-bold text-danger">{{ money_code($refunded, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Net</div>
                <div class="fs-4 fw-bold" style="color:#1B4F72;">{{ money_code($net, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white fw-bold">Payments in range</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Patient</th>
                            <th class="text-end">Amount</th>
                            <th>Mode</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paymentRows as $row)
                            <tr>
                                <td>{{ optional($row->paid_at)->format('d M Y H:i') ?? '-' }}</td>
                                <td>{{ $row->booking?->patient?->full_name ?? '-' }}</td>
                                <td class="text-end">{{ money_code((float) $row->package_amount, 2) }}</td>
                                <td class="text-uppercase">{{ $row->payment_mode }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No payments</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white fw-bold">Refunds in range</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Patient</th>
                            <th class="text-end">Amount</th>
                            <th>Mode</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($refundRows as $row)
                            <tr>
                                <td>{{ optional($row->refunded_at)->format('d M Y H:i') ?? '-' }}</td>
                                <td>{{ $row->booking?->patient?->full_name ?? '-' }}</td>
                                <td class="text-end text-danger">{{ money_code((float) $row->amount, 2) }}</td>
                                <td class="text-uppercase">{{ $row->payment_mode }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No refunds</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
