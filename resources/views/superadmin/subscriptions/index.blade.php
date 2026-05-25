@extends('superadmin.layouts.app')

@section('title', 'Subscriptions')
@section('page-header', 'Subscriptions')

@section('content')

{{-- Stat Cards --}}
<div class="hms-stats-grid" style="grid-template-columns:repeat(3,1fr)">
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-blue"><i class="bi bi-calendar-check-fill"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Total Subscriptions</div>
            <div class="hms-stat-value">{{ $totalCount }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-green"><i class="bi bi-check-circle-fill"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Active</div>
            <div class="hms-stat-value">{{ $activeCount }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-red"><i class="bi bi-calendar-x-fill"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Expired</div>
            <div class="hms-stat-value">{{ $expiredCount }}</div>
        </div>
    </div>
</div>

{{-- Table Card --}}
<div class="hms-card" style="padding:0">
    <div class="hms-card-header">
        <h3 class="hms-card-title">
            <i class="fa-solid fa-calendar-check" style="color:#1B4F72"></i>
            All Subscriptions
        </h3>
        <span class="hms-badge hms-badge-info">{{ $subscriptions->total() }} total</span>
    </div>

    @if($subscriptions->count() === 0)
        <x-empty-state
            icon="fa-solid fa-calendar-xmark"
            title="No subscriptions yet"
            description="Hospital subscriptions will appear here once hospitals upgrade from trial." />
    @else
        <div class="hms-table-wrap" style="border:none">
            <table class="hms-table">
                <thead>
                    <tr>
                        <th>Hospital</th>
                        <th>Plan / Cycle</th>
                        <th>Status</th>
                        <th>Starts</th>
                        <th>Ends</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subscriptions as $subscription)
                        <tr>
                            <td>
                                @if($subscription->tenant)
                                    <a href="{{ route('superadmin.hospitals.show', $subscription->tenant) }}"
                                       style="font-weight:600;color:#1A202C">
                                        {{ $subscription->tenant->name }}
                                    </a>
                                    <div style="font-size:.72rem;color:#64748B">{{ $subscription->tenant->slug }}</div>
                                @else
                                    <span style="color:#94A3B8">Deleted Hospital</span>
                                @endif
                            </td>
                            <td>
                                <span class="hms-badge hms-badge-info">
                                    {{ ucfirst($subscription->plan_type ?? $subscription->cycle ?? '—') }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $subBadge = match($subscription->status) {
                                        'active'   => 'hms-badge-active',
                                        'expired'  => 'hms-badge-suspended',
                                        'canceled' => 'hms-badge-inactive',
                                        default    => 'hms-badge-trial',
                                    };
                                @endphp
                                <span class="hms-badge {{ $subBadge }}">{{ ucfirst($subscription->status) }}</span>
                            </td>
                            <td style="font-size:.85rem;color:#475569">
                                {{ optional($subscription->starts_at)->format('d M Y') ?? '—' }}
                            </td>
                            <td style="font-size:.85rem;color:#475569">
                                {{ optional($subscription->ends_at)->format('d M Y') ?? '—' }}
                            </td>
                            <td style="text-align:right">
                                @if($subscription->tenant)
                                    <a href="{{ route('superadmin.hospitals.show', $subscription->tenant) }}"
                                       class="hms-btn-icon" data-tooltip="View Hospital">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($subscriptions->hasPages())
            <div style="padding:1rem;border-top:1px solid rgba(27,79,114,.1)">
                {{ $subscriptions->links() }}
            </div>
        @endif
    @endif
</div>

@endsection
