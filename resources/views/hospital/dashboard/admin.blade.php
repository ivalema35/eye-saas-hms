@extends('hospital.layouts.app')
@section('title', 'Admin Dashboard')
@section('page-header', 'Dashboard')

@section('content')

{{-- ============================================================
     Stat Cards
============================================================ --}}
<div class="hms-stats-grid">
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-blue"><i class="fa-solid fa-user-doctor"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Total Doctors</div>
            <div class="hms-stat-value">{{ $totalDoctors }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-teal"><i class="fa-solid fa-headset"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Total Receptionists</div>
            <div class="hms-stat-value">{{ $totalReceptions }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-green"><i class="fa-solid fa-calendar-day"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Today's Patients</div>
            <div class="hms-stat-value">{{ $todayPatients }}</div>
            <div class="hms-stat-meta">{{ now()->format('d M Y') }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-orange"><i class="fa-solid fa-eye"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Primary Done</div>
            <div class="hms-stat-value">{{ $todayPrimary }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-purple"><i class="fa-solid fa-eye-low-vision"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Secondary Done</div>
            <div class="hms-stat-value">{{ $todaySecondary }}</div>
        </div>
    </div>
</div>

{{-- ============================================================
     Revenue Row
============================================================ --}}
<div class="hms-stats-grid" style="margin-top:1rem">
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-green"><i class="fa-solid fa-indian-rupee-sign"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Today Revenue</div>
            <div class="hms-stat-value">{{ money($revenueToday, 2) }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-blue"><i class="fa-solid fa-calendar"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">This Month</div>
            <div class="hms-stat-value">{{ money($revenueMonth, 2) }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-teal"><i class="fa-solid fa-chart-line"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">This Year</div>
            <div class="hms-stat-value">{{ money($revenueYear, 2) }}</div>
        </div>
    </div>
</div>

{{-- ============================================================
     Subscription Alert (admin dashboard)
============================================================ --}}
@if($subscriptionDaysLeft !== null && $subscriptionDaysLeft <= 14)
    <div class="hms-alert {{ $subscriptionDaysLeft <= 3 ? 'hms-alert-danger' : 'hms-alert-warning' }}" style="margin-top:1rem">
        <i class="fa-solid fa-triangle-exclamation"></i>
        @if($subscriptionDaysLeft <= 0)
            Your subscription has expired. Please renew immediately to continue access.
        @else
            Your subscription expires in <strong>{{ $subscriptionDaysLeft }} day{{ $subscriptionDaysLeft === 1 ? '' : 's' }}</strong>. Please renew soon.
        @endif
        <a href="{{ route('hospital.settings.index', ['slug' => $slug]) }}" style="margin-left:auto;color:inherit;font-weight:600">Renew Now →</a>
    </div>
@endif

{{-- ============================================================
     Dashboard Row: Reception Performance
============================================================ --}}
<div class="hms-dashboard-row" style="margin-top:1.5rem">

    {{-- Reception Performance --}}
    <div class="hms-card">
        <div class="hms-card-header">
            <h3 class="hms-card-title"><i class="fa-solid fa-headset"></i> Reception Performance — Today</h3>
        </div>
        <div class="hms-card-body" style="padding:0">
            <table class="hms-table">
                <thead>
                    <tr>
                        <th>Receptionist</th>
                        <th class="text-center">Walk-ins</th>
                        <th class="text-end">Gross ({{ currency_symbol() }})</th>
                        <th class="text-end">Net ({{ currency_symbol() }})</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receptionists as $rec)
                        <tr>
                            <td>{{ $rec->name }}</td>
                            <td class="text-center">{{ $rec->today_count }}</td>
                            <td class="text-end">{{ number_format($rec->today_gross, 2) }}</td>
                            <td class="text-end"><strong>{{ number_format($rec->today_net, 2) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center" style="color:#9CA3AF">No receptionists found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection
