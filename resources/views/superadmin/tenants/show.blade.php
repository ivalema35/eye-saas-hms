@extends('superadmin.layouts.app')

@section('title', $tenant->name)
@section('page-header', $tenant->name)

@section('page-actions')
    <a href="{{ route('superadmin.hospitals.index') }}" class="hms-btn hms-btn-outline hms-btn-sm">
        <i class="bi bi-arrow-left"></i> Back
    </a>
    <a href="{{ route('superadmin.hospitals.edit', $tenant) }}" class="hms-btn hms-btn-primary hms-btn-sm"
        style="color: #1B4F72;">
        <i class="bi bi-pencil-fill"></i> Edit
    </a>
    <a href="{{ url($tenant->slug . '/login') }}" target="_blank" class="hms-btn hms-btn-outline hms-btn-sm">
        <i class="bi bi-box-arrow-up-right"></i> Open Portal
    </a>
@endsection

@section('content')

    {{-- Mini Stat Bar --}}
    <div class="hms-stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.25rem">
        <div class="hms-stat-card">
            <div class="hms-stat-icon hsi-blue"><i class="bi bi-hospital-fill"></i></div>
            <div class="hms-stat-body">
                <div class="hms-stat-label">Status</div>
                <div style="margin-top:.25rem">
                    <span class="hms-badge hms-badge-{{ $tenant->displayStatus() }}">
                        {{ ucfirst($tenant->displayStatus()) }}
                    </span>
                </div>
            </div>
        </div>
        <div class="hms-stat-card">
            <div class="hms-stat-icon hsi-teal"><i class="bi bi-calendar3"></i></div>
            <div class="hms-stat-body">
                <div class="hms-stat-label">Trial Ends</div>
                <div style="font-size:1rem;font-weight:700;color:#1A202C;margin-top:.25rem">
                    {{ optional($tenant->trial_ends_at)->format('d M Y') ?? 'N/A' }}
                </div>
            </div>
        </div>
        <div class="hms-stat-card">
            <div class="hms-stat-icon hsi-purple"><i class="bi bi-credit-card-fill"></i></div>
            <div class="hms-stat-body">
                <div class="hms-stat-label">Subscriptions</div>
                <div class="hms-stat-value">{{ $tenant->subscriptions->count() }}</div>
            </div>
        </div>
        <div class="hms-stat-card">
            <div class="hms-stat-icon hsi-green"><i class="bi bi-cash-coin"></i></div>
            <div class="hms-stat-body">
                <div class="hms-stat-label">Total Paid</div>
                <div class="hms-stat-value">
                    {{ platform_money($tenant->payments->where('status', 'success')->sum('amount')) }}
                </div>
            </div>
        </div>
    </div>

    {{-- Hospital Info + Quick Actions (side by side) --}}
    <div style="display:grid;grid-template-columns:1fr 320px;gap:1.25rem;align-items:start;margin-bottom:1.25rem">

        {{-- Hospital Information --}}
        <div class="hms-card" style="padding:0">
            <div class="hms-card-header">
                <h3 class="hms-card-title">
                    <i class="bi bi-hospital-fill" style="color:#1B4F72"></i>
                    Hospital Information
                </h3>
            </div>
            <div style="padding:1.25rem;display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                @php
                    $fields = [
                        ['Hospital Name', $tenant->name],
                        ['Slug / URL', $tenant->slug],
                        ['Admin Name', $tenant->admin_name ?? '—'],
                        ['Admin Email', $tenant->admin_email ?? '—'],
                        ['Admin Phone', $tenant->admin_phone ?? '—'],
                        ['City / State', trim(($tenant->city ?? '') . ', ' . ($tenant->state ?? ''), ', ') ?: '—'],
                        ['Registered On', optional($tenant->created_at)->format('d M Y, h:i A') ?? '—'],
                    ];
                @endphp
                @foreach($fields as [$label, $value])
                    <div>
                        <div
                            style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748B;margin-bottom:.25rem">
                            {{ $label }}
                        </div>
                        <div style="font-size:.9rem;font-weight:500;color:#1A202C">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="hms-card" style="padding:0">
            <div class="hms-card-header">
                <h3 class="hms-card-title">
                    <i class="bi bi-lightning-fill" style="color:#E67E22"></i>
                    Quick Actions
                </h3>
            </div>
            <div style="padding:1.25rem;display:flex;flex-direction:column;gap:.625rem">
                @if($tenant->status === 'pending')
                    <form method="POST" action="{{ route('superadmin.hospitals.approve', $tenant) }}">
                        @csrf
                        <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm" style="width:100%; color: #1B4F72;">
                            <i class="bi bi-check-lg"></i> Accept Registration
                        </button>
                    </form>
                    <form method="POST" action="{{ route('superadmin.hospitals.reject', $tenant) }}"
                        onsubmit="return confirm('Reject this registration? The hospital admin will not be able to login.')">
                        @csrf
                        <button type="submit" class="hms-btn hms-btn-danger hms-btn-sm" style="width:100%">
                            <i class="bi bi-x-lg"></i> Reject Registration
                        </button>
                    </form>
                @else
                @if($tenant->status !== 'active')
                    <form method="POST" action="{{ route('superadmin.hospitals.activate', $tenant) }}">
                        @csrf
                        <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm" style="width:100%; color: #1B4F72;">
                            <i class="bi bi-check-lg"></i> Activate Hospital
                        </button>
                    </form>
                @endif

                @if($tenant->status !== 'suspended')
                    <form method="POST" action="{{ route('superadmin.hospitals.suspend', $tenant) }}"
                        onsubmit="return confirm('Are you sure you want to suspend this hospital?')">
                        @csrf
                        <button type="submit" class="hms-btn hms-btn-danger hms-btn-sm" style="width:100%">
                            <i class="bi bi-ban"></i> Suspend Hospital
                        </button>
                    </form>
                @endif

                @if($tenant->status === 'suspended')
                    <form method="POST" action="{{ route('superadmin.hospitals.reactivate', $tenant) }}">
                        @csrf
                        <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm" style="width:100%; color: #1B4F72;">
                            <i class="bi bi-arrow-clockwise"></i> Reactivate
                        </button>
                    </form>
                @endif

                <form method="POST" action="{{ route('superadmin.hospitals.reseed-masters', $tenant) }}"
                    onsubmit="return confirm('Re-seed default masters for this hospital?')">
                    @csrf
                    <button type="submit" class="hms-btn hms-btn-sm" style="width:100%;background:#e2e8f0;color:#1A202C">
                        <i class="bi bi-database-fill-gear"></i> Re-seed Default Masters
                    </button>
                </form>

                <form method="POST" action="{{ route('superadmin.hospitals.extend', $tenant) }}"
                    style="display:flex;gap:.5rem;align-items:center">
                    @csrf
                    <input type="number" name="days" class="hms-input"
                        style="width:70px;padding:.35rem .5rem;text-align:center" min="1" max="90" value="7">
                    <button type="submit" class="hms-btn hms-btn-outline hms-btn-sm" style="flex:1">
                        <i class="bi bi-clock-history"></i> Extend Grace
                    </button>
                </form>

                @if(!$tenant->trashed())
                    <form method="POST" action="{{ route('superadmin.hospitals.destroy', $tenant) }}"
                        onsubmit="return confirm('Archive this hospital? Data will be retained for 30 days.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="hms-btn hms-btn-danger hms-btn-sm" style="width:100%">
                            <i class="bi bi-archive-fill"></i> Archive Hospital
                        </button>
                    </form>
                @endif
                @endif
            </div>
        </div>

    </div>

    {{-- Subscription History --}}
    <div class="hms-card" style="padding:0;margin-bottom:1.25rem">
        <div class="hms-card-header">
            <h3 class="hms-card-title">
                <i class="bi bi-calendar-check-fill" style="color:#1B4F72"></i>
                Subscription History
            </h3>
            @if($tenant->subscriptions->isNotEmpty())
                <span class="hms-badge hms-badge-info">{{ $tenant->subscriptions->count() }} records</span>
            @endif
        </div>
        @if($tenant->subscriptions->isEmpty())
            <x-empty-state icon="bi bi-calendar-x-fill" title="No subscriptions yet"
                description="This hospital is currently on trial. Subscriptions will appear here after payment." />
        @else
            <div class="hms-table-wrap" style="border:none">
                <table class="hms-table">
                    <thead>
                        <tr>
                            <th>Cycle</th>
                            <th>Price</th>
                            <th>Starts</th>
                            <th>Ends</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tenant->subscriptions->sortByDesc('created_at') as $sub)
                            <tr>
                                <td>
                                    <span class="hms-badge hms-badge-info">{{ ucfirst($sub->cycle) }}</span>
                                </td>
                                <td style="font-weight:600;color:#1A202C">{{ platform_money($sub->price) }}</td>
                                <td style="font-size:.85rem;color:#475569">{{ optional($sub->starts_at)->format('d M Y') ?? '—' }}
                                </td>
                                <td style="font-size:.85rem;color:#475569">{{ optional($sub->ends_at)->format('d M Y') ?? '—' }}
                                </td>
                                <td>
                                    <span class="hms-badge hms-badge-{{ $sub->status === 'active' ? 'active' : 'inactive' }}">
                                        {{ ucfirst($sub->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Payment History --}}
    <div class="hms-card" style="padding:0">
        <div class="hms-card-header">
            <h3 class="hms-card-title">
                <i class="bi bi-cash-coin" style="color:#27AE60"></i>
                Payment History
            </h3>
            @if($tenant->payments->isNotEmpty())
                <span class="hms-badge hms-badge-info">{{ $tenant->payments->count() }} records</span>
            @endif
        </div>
        @if($tenant->payments->isEmpty())
            <x-empty-state icon="bi bi-receipt-cutoff" title="No payments recorded"
                description="Payment transactions for this hospital will appear here once received." />
        @else
            <div class="hms-table-wrap" style="border:none">
                <table class="hms-table">
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Transaction ID</th>
                            <th>Status</th>
                            <th>Paid At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tenant->payments->sortByDesc('created_at') as $payment)
                            <tr>
                                <td style="font-weight:600;color:#1A202C">{{ platform_money($payment->amount) }}</td>
                                <td>{{ ucfirst($payment->method ?? '—') }}</td>
                                <td style="font-family:monospace;font-size:.8rem;color:#475569">
                                    {{ $payment->transaction_id ?? '—' }}
                                </td>
                                <td>
                                    <span
                                        class="hms-badge hms-badge-{{ $payment->status === 'success' ? 'active' : ($payment->status === 'failed' ? 'suspended' : 'trial') }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                                <td style="font-size:.85rem;color:#475569">
                                    {{ optional($payment->paid_at)->format('d M Y, h:i A') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

@endsection