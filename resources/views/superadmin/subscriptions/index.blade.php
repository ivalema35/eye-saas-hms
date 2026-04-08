@extends('superadmin.layouts.app')

@section('title', 'Subscriptions')
@section('page-header', 'Subscriptions')

@section('content')

    <div class="hms-card" style="padding:0">
        <div class="hms-card-header">
            <h3 class="hms-card-title">
                <i class="fa-solid fa-calendar-check"></i> All Subscriptions
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
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subscriptions as $subscription)
                            <tr>
                                <td>
                                    @if($subscription->tenant)
                                        <a href="{{ route('superadmin.hospitals.show', $subscription->tenant) }}"
                                           style="font-weight:600">
                                            {{ $subscription->tenant->name }}
                                        </a>
                                        <div style="font-size:.72rem;color:var(--hms-text-muted)">
                                            {{ $subscription->tenant->slug }}
                                        </div>
                                    @else
                                        <span style="color:var(--hms-text-muted)">Deleted Hospital</span>
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
                                    <span class="hms-badge {{ $subBadge }}">
                                        {{ ucfirst($subscription->status) }}
                                    </span>
                                </td>
                                <td style="font-size:.85rem">
                                    {{ optional($subscription->starts_at)->format('d M Y') ?? '—' }}
                                </td>
                                <td style="font-size:.85rem">
                                    {{ optional($subscription->ends_at)->format('d M Y') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($subscriptions->hasPages())
                <div style="padding:1rem;border-top:1px solid var(--hms-border)">
                    {{ $subscriptions->links() }}
                </div>
            @endif
        @endif
    </div>

@endsection
