@extends('superadmin.layouts.app')

@section('title', $tenant->name)
@section('page-header', $tenant->name)

@section('page-actions')
    <a href="{{ route('superadmin.hospitals.index') }}" class="hms-btn hms-btn-sm hms-btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
    <a href="{{ route('superadmin.hospitals.edit', $tenant) }}" class="hms-btn hms-btn-sm hms-btn-primary">
        <i class="fa-solid fa-pen-to-square"></i> Edit
    </a>
    <a href="{{ url($tenant->slug . '/login') }}" target="_blank" class="hms-btn hms-btn-sm hms-btn-secondary">
        <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Portal
    </a>
@endsection

@section('content')

    {{-- Hospital Info --}}
    <div class="hms-card">
        <div class="hms-card-header">
            <h3 class="hms-card-title"><i class="fa-solid fa-hospital"></i> Hospital Information</h3>
            <span class="hms-badge hms-badge-{{ $tenant->status ?? 'inactive' }}">
                {{ ucfirst($tenant->status ?? 'inactive') }}
            </span>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="hms-form-group" style="margin-bottom:.5rem">
                <label>Hospital Name</label>
                <div style="font-size:.9rem;font-weight:500">{{ $tenant->name }}</div>
            </div>
            <div class="hms-form-group" style="margin-bottom:.5rem">
                <label>Slug / URL</label>
                <div style="font-size:.9rem;font-weight:500">{{ $tenant->slug }}</div>
            </div>
            <div class="hms-form-group" style="margin-bottom:.5rem">
                <label>Admin Name</label>
                <div style="font-size:.9rem">{{ $tenant->admin_name ?? '-' }}</div>
            </div>
            <div class="hms-form-group" style="margin-bottom:.5rem">
                <label>Admin Email</label>
                <div style="font-size:.9rem">{{ $tenant->admin_email ?? '-' }}</div>
            </div>
            <div class="hms-form-group" style="margin-bottom:.5rem">
                <label>Admin Phone</label>
                <div style="font-size:.9rem">{{ $tenant->admin_phone ?? '-' }}</div>
            </div>
            <div class="hms-form-group" style="margin-bottom:.5rem">
                <label>City / State</label>
                <div style="font-size:.9rem">{{ $tenant->city ?? '-' }}, {{ $tenant->state ?? '-' }}</div>
            </div>
            <div class="hms-form-group" style="margin-bottom:.5rem">
                <label>Trial Ends At</label>
                <div style="font-size:.9rem">{{ optional($tenant->trial_ends_at)->format('d M Y') ?? 'N/A' }}</div>
            </div>
            <div class="hms-form-group" style="margin-bottom:.5rem">
                <label>Registered On</label>
                <div style="font-size:.9rem">{{ optional($tenant->created_at)->format('d M Y, h:i A') ?? '-' }}</div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="hms-card">
        <div class="hms-card-header">
            <h3 class="hms-card-title">Quick Actions</h3>
        </div>
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center">
            @if($tenant->status !== 'active')
                <form method="POST" action="{{ route('superadmin.hospitals.activate', $tenant) }}">
                    @csrf
                    <button type="submit" class="hms-btn hms-btn-success hms-btn-sm">
                        <i class="fa-solid fa-check"></i> Activate
                    </button>
                </form>
            @endif

            @if($tenant->status !== 'suspended')
                                <form method="POST" action="{{ route('superadmin.hospitals.suspend', $tenant) }}"
                      onsubmit="return confirm('Are you sure you want to suspend this hospital?')">
                    @csrf
                    <button type="submit" class="hms-btn hms-btn-danger hms-btn-sm">
                        <i class="fa-solid fa-ban"></i> Suspend
                    </button>
                </form>
            @endif

                        <form method="POST" action="{{ route('superadmin.hospitals.extend', $tenant) }}"
                  style="display:flex;gap:.5rem;align-items:center">
                @csrf
                <input type="number" name="days" class="hms-input" style="width:80px;padding:.3rem .5rem"
                       min="1" max="90" value="7" placeholder="Days">
                <button type="submit" class="hms-btn hms-btn-primary hms-btn-sm">
                    <i class="fa-solid fa-clock-rotate-left"></i> Extend Grace
                </button>
            </form>

            @if($tenant->status === 'suspended')
                <form method="POST" action="{{ route('superadmin.hospitals.reactivate', $tenant) }}">
                    @csrf
                    <button type="submit" class="hms-btn hms-btn-success hms-btn-sm">
                        <i class="fa-solid fa-rotate-right"></i> Reactivate
                    </button>
                </form>
            @endif

            @if(!$tenant->trashed())
                <form method="POST"
                      action="{{ route('superadmin.hospitals.destroy', $tenant) }}"
                      onsubmit="return confirm('Archive this hospital? Data will be retained for 30 days.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="hms-btn hms-btn-danger hms-btn-sm">
                        <i class="fa-solid fa-archive"></i> Archive
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Subscription History --}}
    <div class="hms-card">
        <div class="hms-card-header">
            <h3 class="hms-card-title"><i class="fa-solid fa-calendar-check"></i> Subscription History</h3>
            @if($tenant->subscriptions->isNotEmpty())
                <span class="hms-badge hms-badge-info">{{ $tenant->subscriptions->count() }} records</span>
            @endif
        </div>
        @if($tenant->subscriptions->isEmpty())
            <x-empty-state
                icon="fa-solid fa-calendar-xmark"
                title="No subscriptions yet"
                description="This hospital is currently on trial. Subscriptions will appear here after payment." />
        @else
            <div class="hms-table-wrap">
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
                                <td>{{ ucfirst($sub->cycle) }}</td>
                                <td>₹{{ number_format($sub->price) }}</td>
                                <td>{{ optional($sub->starts_at)->format('d M Y') ?? '-' }}</td>
                                <td>{{ optional($sub->ends_at)->format('d M Y') ?? '-' }}</td>
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
    <div class="hms-card">
        <div class="hms-card-header">
            <h3 class="hms-card-title"><i class="fa-solid fa-indian-rupee-sign"></i> Payment History</h3>
            @if($tenant->payments->isNotEmpty())
                <span class="hms-badge hms-badge-info">{{ $tenant->payments->count() }} records</span>
            @endif
        </div>
        @if($tenant->payments->isEmpty())
            <x-empty-state
                icon="fa-solid fa-receipt"
                title="No payments recorded"
                description="Payment transactions for this hospital will appear here once received." />
        @else
            <div class="hms-table-wrap">
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
                                <td>₹{{ number_format($payment->amount) }}</td>
                                <td>{{ ucfirst($payment->method ?? '-') }}</td>
                                <td>{{ $payment->transaction_id ?? '-' }}</td>
                                <td>
                                    <span class="hms-badge hms-badge-{{ $payment->status === 'success' ? 'active' : ($payment->status === 'failed' ? 'suspended' : 'trial') }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                                <td>{{ optional($payment->paid_at)->format('d M Y, h:i A') ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

@endsection
