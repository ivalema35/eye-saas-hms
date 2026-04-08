@extends('superadmin.layouts.app')
@section('title', 'Payments')
@section('page-header', 'Payment Transactions')

@section('page-actions')
    <button class="hms-btn hms-btn-primary hms-btn-sm" data-bs-toggle="modal" data-bs-target="#offlineModal">
        <i class="fa-solid fa-plus"></i> Record Offline Payment
    </button>
@endsection

@section('content')

<div class="sa-stats-grid" style="margin-bottom:1.25rem">
    <div class="sa-stat-card">
        <div class="sa-stat-icon sa-stat-green"><i class="fa-solid fa-indian-rupee-sign"></i></div>
        <div class="sa-stat-body">
            <div class="sa-stat-label">Total Revenue</div>
            <div class="sa-stat-value">₹{{ number_format((float) $totalRevenue) }}</div>
        </div>
    </div>
    <div class="sa-stat-card">
        <div class="sa-stat-icon sa-stat-blue"><i class="fa-solid fa-calendar-check"></i></div>
        <div class="sa-stat-body">
            <div class="sa-stat-label">This Month</div>
            <div class="sa-stat-value">₹{{ number_format((float) $thisMonth) }}</div>
        </div>
    </div>
    <div class="sa-stat-card">
        <div class="sa-stat-icon sa-stat-orange"><i class="fa-solid fa-clock"></i></div>
        <div class="sa-stat-body">
            <div class="sa-stat-label">Pending</div>
            <div class="sa-stat-value">{{ $pendingCount }}</div>
        </div>
    </div>
</div>

<div class="hms-card" style="padding:1rem;margin-bottom:1rem">
    <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
        <div class="hms-form-group" style="margin-bottom:0;min-width:130px">
            <label style="font-size:.75rem">Status</label>
            <select name="status" class="hms-select">
                <option value="">All</option>
                <option value="success"  {{ request('status')=='success'  ? 'selected':'' }}>Success</option>
                <option value="pending"  {{ request('status')=='pending'  ? 'selected':'' }}>Pending</option>
                <option value="failed"   {{ request('status')=='failed'   ? 'selected':'' }}>Failed</option>
            </select>
        </div>
        <div class="hms-form-group" style="margin-bottom:0;min-width:130px">
            <label style="font-size:.75rem">Method</label>
            <select name="method" class="hms-select">
                <option value="">All</option>
                <option value="online"  {{ request('method')=='online'  ? 'selected':'' }}>Online</option>
                <option value="offline" {{ request('method')=='offline' ? 'selected':'' }}>Offline</option>
            </select>
        </div>
        <div class="hms-form-group" style="margin-bottom:0">
            <label style="font-size:.75rem">From Date</label>
            <input type="date" name="from" class="hms-input" value="{{ request('from') }}" style="width:140px">
        </div>
        <div class="hms-form-group" style="margin-bottom:0">
            <label style="font-size:.75rem">To Date</label>
            <input type="date" name="to" class="hms-input" value="{{ request('to') }}" style="width:140px">
        </div>
        <div style="display:flex;gap:.5rem">
            <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            <a href="{{ route('superadmin.payments.index') }}" class="hms-btn hms-btn-secondary hms-btn-sm">Clear</a>
        </div>
    </form>
</div>

<div class="hms-card" style="padding:0">
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
                    <td style="color:var(--hms-text-muted);font-size:.8rem">{{ $payment->id }}</td>
                    <td>
                        @if($payment->tenant)
                            <a href="{{ route('superadmin.hospitals.show', $payment->tenant) }}" style="font-weight:600">{{ $payment->tenant->name }}</a>
                            <div style="font-size:.72rem;color:var(--hms-text-muted)">{{ $payment->tenant->slug }}</div>
                        @else
                            <span style="color:var(--hms-text-muted)">Deleted Hospital</span>
                        @endif
                    </td>
                    <td style="font-weight:700">₹{{ number_format((float) $payment->amount) }}</td>
                    <td>{{ ucfirst($payment->cycle ?? '—') }}</td>
                    <td>
                        <span class="hms-badge" style="background:{{ $payment->method === 'online' ? 'var(--hms-info-bg)' : '#FFF3E0' }};color:{{ $payment->method === 'online' ? 'var(--hms-info)' : '#E65100' }}">
                            <i class="fa-solid fa-{{ $payment->method === 'online' ? 'wifi' : 'money-bill-wave' }}" style="font-size:.6rem"></i>
                            {{ ucfirst($payment->method ?? '—') }}
                        </span>
                    </td>
                    <td style="font-family:var(--hms-font-mono);font-size:.8rem">{{ $payment->transaction_id ?? '—' }}</td>
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
                    <td style="white-space:nowrap;font-size:.82rem">{{ $payment->paid_at?->format('d M Y, h:i A') ?? '—' }}</td>
                    <td style="font-size:.8rem;color:var(--hms-text-muted);max-width:150px">{{ \Illuminate\Support\Str::limit($payment->notes ?? '', 40) ?: '—' }}</td>
                    <td style="text-align:center">
                        @if($payment->status === 'success')
                            <a href="{{ route('superadmin.payments.invoice', $payment) }}"
                               target="_blank"
                               class="hms-btn hms-btn-outline hms-btn-sm"
                               title="Download Invoice PDF">
                                <i class="fa-solid fa-file-pdf"></i>
                            </a>
                        @else
                            <span style="color:var(--hms-text-muted);font-size:.75rem">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align:center;padding:2rem;color:var(--hms-text-muted)">No payments found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
    <div style="padding:1rem;border-top:1px solid var(--hms-border)">
        {{ $payments->withQueryString()->links() }}
    </div>
    @endif
</div>

<div class="modal fade" id="offlineModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:var(--hms-radius-lg)">
            <div class="modal-header" style="border-bottom:1px solid var(--hms-border)">
                <h5 class="modal-title" style="font-weight:700">
                    <i class="fa-solid fa-money-bill-wave" style="color:var(--hms-success)"></i>
                    Record Offline Payment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('superadmin.payments.offline') }}">
                @csrf
                <div class="modal-body" style="padding:1.5rem">
                    <div class="hms-form-group">
                        <label>Hospital <span style="color:var(--hms-danger)">*</span></label>
                        <select name="tenant_id" class="hms-select" required>
                            <option value="">Select hospital...</option>
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}">{{ $tenant->name }} ({{ $tenant->slug }}) — {{ ucfirst($tenant->status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                        <div class="hms-form-group">
                            <label>Amount (₹) <span style="color:var(--hms-danger)">*</span></label>
                            <input type="number" name="amount" class="hms-input" min="1" step="1" placeholder="e.g. 999" required>
                        </div>
                        <div class="hms-form-group">
                            <label>Billing Cycle <span style="color:var(--hms-danger)">*</span></label>
                            <select name="cycle" class="hms-select" required>
                                <option value="monthly">Monthly (₹999)</option>
                                <option value="quarterly">Quarterly (₹2,427)</option>
                                <option value="yearly">Yearly (₹9,590)</option>
                            </select>
                        </div>
                    </div>
                    <div class="hms-form-group">
                        <label>Transaction / Cheque Number</label>
                        <input type="text" name="transaction_id" class="hms-input" placeholder="e.g. CHQ-12345 or UTR-XXXXX">
                    </div>
                    <div class="hms-form-group" style="margin-bottom:0">
                        <label>Notes (optional)</label>
                        <input type="text" name="notes" class="hms-input" placeholder="e.g. Cash received by X on dd/mm/yyyy">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--hms-border)">
                    <button type="button" class="hms-btn hms-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="hms-btn hms-btn-success">
                        <i class="fa-solid fa-check"></i> Record & Activate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
