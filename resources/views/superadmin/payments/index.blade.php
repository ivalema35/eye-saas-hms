@extends('superadmin.layouts.app')
@section('title', 'Payments')
@section('page-header', 'Payment Transactions')

@section('page-actions')
    <button class="hms-btn hms-btn-primary hms-btn-sm" data-bs-toggle="modal" data-bs-target="#offlineModal">
        <i class="bi bi-plus-lg"></i> Record Offline Payment
    </button>
@endsection

@section('content')

{{-- Stat Cards --}}
<div class="hms-stats-grid" style="grid-template-columns:repeat(3,1fr)">
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-green"><i class="bi bi-currency-rupee"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Total Revenue</div>
            <div class="hms-stat-value">₹{{ number_format((float) $totalRevenue) }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-blue"><i class="bi bi-calendar-check-fill"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">This Month</div>
            <div class="hms-stat-value">₹{{ number_format((float) $thisMonth) }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-orange"><i class="bi bi-clock-fill"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Pending Payments</div>
            <div class="hms-stat-value">{{ $pendingCount }}</div>
        </div>
    </div>
</div>

{{-- Filter Card --}}
<div class="hms-card" style="padding:1.25rem;margin-bottom:1.25rem">
    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem">
        <i class="bi bi-funnel-fill" style="color:#1B4F72;font-size:.875rem"></i>
        <span style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748B">Filters</span>
    </div>
    <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
        <div class="hms-form-group" style="margin-bottom:0;min-width:130px">
            <label class="hms-label">Status</label>
            <select name="status" class="hms-select">
                <option value="">All</option>
                <option value="success" {{ request('status')=='success' ? 'selected':'' }}>Success</option>
                <option value="pending" {{ request('status')=='pending' ? 'selected':'' }}>Pending</option>
                <option value="failed"  {{ request('status')=='failed'  ? 'selected':'' }}>Failed</option>
            </select>
        </div>
        <div class="hms-form-group" style="margin-bottom:0;min-width:130px">
            <label class="hms-label">Method</label>
            <select name="method" class="hms-select">
                <option value="">All</option>
                <option value="online"  {{ request('method')=='online'  ? 'selected':'' }}>Online</option>
                <option value="offline" {{ request('method')=='offline' ? 'selected':'' }}>Offline</option>
            </select>
        </div>
        <div class="hms-form-group" style="margin-bottom:0">
            <label class="hms-label">From Date</label>
            <input type="date" name="from" class="hms-input" value="{{ request('from') }}" style="width:145px">
        </div>
        <div class="hms-form-group" style="margin-bottom:0">
            <label class="hms-label">To Date</label>
            <input type="date" name="to" class="hms-input" value="{{ request('to') }}" style="width:145px">
        </div>
        <div style="display:flex;gap:.5rem">
            <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm">
                <i class="bi bi-funnel-fill"></i> Filter
            </button>
            <a href="{{ route('superadmin.payments.index') }}" class="hms-btn hms-btn-outline hms-btn-sm">Clear</a>
        </div>
    </form>
</div>

{{-- Payments Table --}}
<div class="hms-card" style="padding:0">
    <div class="hms-card-header">
        <h3 class="hms-card-title">
            <i class="bi bi-receipt-cutoff" style="color:#1B4F72"></i>
            Payment Transactions
        </h3>
        <span class="hms-badge hms-badge-info">{{ $payments->total() }} total</span>
    </div>

    <div class="hms-table-wrap" style="border:none">
        <table class="hms-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Hospital</th>
                    <th>Amount</th>
                    <th>Cycle</th>
                    <th>Method</th>
                    <th>Transaction ID</th>
                    <th>Status</th>
                    <th>Paid At</th>
                    <th>Notes</th>
                    <th style="text-align:center">Invoice</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr>
                    <td style="color:#94A3B8;font-size:.8rem">{{ $payment->id }}</td>
                    <td>
                        @if($payment->tenant)
                            <a href="{{ route('superadmin.hospitals.show', $payment->tenant) }}"
                               style="font-weight:600;color:#1A202C">{{ $payment->tenant->name }}</a>
                            <div style="font-size:.72rem;color:#64748B">{{ $payment->tenant->slug }}</div>
                        @else
                            <span style="color:#94A3B8">Deleted Hospital</span>
                        @endif
                    </td>
                    <td style="font-weight:700;color:#1A202C">₹{{ number_format((float) $payment->amount) }}</td>
                    <td style="font-size:.85rem">{{ ucfirst($payment->cycle ?? '—') }}</td>
                    <td>
                        @if($payment->method === 'online')
                            <span class="hms-badge hms-badge-info"><i class="bi bi-wifi" style="font-size:.6rem"></i> Online</span>
                        @else
                            <span class="hms-badge" style="background:#FFF3E0;color:#E65100"><i class="bi bi-cash-stack" style="font-size:.6rem"></i> Offline</span>
                        @endif
                    </td>
                    <td style="font-family:monospace;font-size:.8rem;color:#475569">{{ $payment->transaction_id ?? '—' }}</td>
                    <td>
                        @php
                            $badgeClass = match($payment->status) {
                                'success' => 'hms-badge-active',
                                'pending' => 'hms-badge-trial',
                                'failed'  => 'hms-badge-suspended',
                                default   => 'hms-badge-inactive',
                            };
                        @endphp
                        <span class="hms-badge {{ $badgeClass }}">{{ ucfirst($payment->status) }}</span>
                    </td>
                    <td style="white-space:nowrap;font-size:.82rem;color:#475569">
                        {{ $payment->paid_at?->format('d M Y, h:i A') ?? '—' }}
                    </td>
                    <td style="font-size:.8rem;color:#64748B;max-width:150px">
                        {{ \Illuminate\Support\Str::limit($payment->notes ?? '', 40) ?: '—' }}
                    </td>
                    <td style="text-align:center">
                        @if($payment->status === 'success')
                            <a href="{{ route('superadmin.payments.invoice', $payment) }}"
                               target="_blank"
                               class="hms-btn-icon" data-tooltip="Download Invoice PDF">
                                <i class="bi bi-file-pdf-fill" style="color:#C0392B"></i>
                            </a>
                        @else
                            <span style="color:#94A3B8;font-size:.75rem">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align:center;padding:2.5rem;color:#94A3B8">
                        <i class="bi bi-receipt-cutoff" style="font-size:2rem;opacity:.3;display:block;margin-bottom:.5rem"></i>
                        No payments found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
    <div style="padding:1rem;border-top:1px solid rgba(27,79,114,.1)">
        {{ $payments->withQueryString()->links() }}
    </div>
    @endif
</div>

{{-- Record Offline Payment Modal --}}
<div class="modal fade" id="offlineModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:14px;border:none;box-shadow:0 25px 80px rgba(0,0,0,.2)">
            <div class="modal-header" style="border-bottom:1px solid rgba(27,79,114,.12);padding:1.25rem 1.5rem">
                <h5 class="modal-title" style="font-weight:700;font-size:1rem;color:#1B4F72;display:flex;align-items:center;gap:.5rem">
                    <i class="bi bi-cash-stack"></i>
                    Record Offline Payment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('superadmin.payments.offline') }}">
                @csrf
                <div class="modal-body" style="padding:1.5rem">
                    <div class="hms-form-group">
                        <label class="hms-label">Hospital <span style="color:#C0392B">*</span></label>
                        <select name="tenant_id" class="hms-select" required>
                            <option value="">Select hospital...</option>
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}">{{ $tenant->name }} ({{ $tenant->slug }}) — {{ ucfirst($tenant->status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                        <div class="hms-form-group">
                            <label class="hms-label">Amount (₹) <span style="color:#C0392B">*</span></label>
                            <input type="number" name="amount" class="hms-input" min="1" step="1" placeholder="e.g. 999" required>
                        </div>
                        <div class="hms-form-group">
                            <label class="hms-label">Billing Cycle <span style="color:#C0392B">*</span></label>
                            <select name="cycle" class="hms-select" required>
                                <option value="monthly">Monthly (₹999)</option>
                                <option value="quarterly">Quarterly (₹2,427)</option>
                                <option value="yearly">Yearly (₹9,590)</option>
                            </select>
                        </div>
                    </div>
                    <div class="hms-form-group">
                        <label class="hms-label">Transaction / Cheque Number</label>
                        <input type="text" name="transaction_id" class="hms-input" placeholder="e.g. CHQ-12345 or UTR-XXXXX">
                    </div>
                    <div class="hms-form-group" style="margin-bottom:0">
                        <label class="hms-label">Notes (optional)</label>
                        <input type="text" name="notes" class="hms-input" placeholder="e.g. Cash received by X on dd/mm/yyyy">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid rgba(27,79,114,.12);padding:1rem 1.5rem">
                    <button type="button" class="hms-btn hms-btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="hms-btn hms-btn-primary">
                        <i class="bi bi-check-lg"></i> Record & Activate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
