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
            <div class="hms-stat-value">₹{{ number_format($revenueToday, 2) }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-blue"><i class="fa-solid fa-calendar"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">This Month</div>
            <div class="hms-stat-value">₹{{ number_format($revenueMonth, 2) }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-teal"><i class="fa-solid fa-chart-line"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">This Year</div>
            <div class="hms-stat-value">₹{{ number_format($revenueYear, 2) }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-red"><i class="fa-solid fa-hand-holding-heart"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">FOC Pending</div>
            <div class="hms-stat-value">{{ $focPending }}</div>
        </div>
    </div>
</div>

{{-- ============================================================
     Subscription Alert
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
     Dashboard Row: Reception Performance + Quick Actions
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
                        <th class="text-end">Gross (₹)</th>
                        <th class="text-end">FOC (₹)</th>
                        <th class="text-end">Net (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receptionists as $rec)
                        <tr>
                            <td>{{ $rec->name }}</td>
                            <td class="text-center">{{ $rec->today_count }}</td>
                            <td class="text-end">{{ number_format($rec->today_gross, 2) }}</td>
                            <td class="text-end">{{ number_format($rec->today_foc, 2) }}</td>
                            <td class="text-end"><strong>{{ number_format($rec->today_net, 2) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center" style="color:#9CA3AF">No receptionists found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="hms-card">
        <div class="hms-card-header">
            <h3 class="hms-card-title"><i class="fa-solid fa-bolt"></i> Quick Actions</h3>
        </div>
        <div class="hms-card-body hms-qa-grid">
            <a href="{{ route('hospital.patients.create', $slug) }}" class="hms-qa-btn">
                <div class="hms-qa-icon qa-blue"><i class="fa-solid fa-user-plus"></i></div>
                <span>Add Patient</span>
            </a>
            <a href="{{ route('hospital.patients.index', $slug) }}" class="hms-qa-btn">
                <div class="hms-qa-icon qa-teal"><i class="fa-solid fa-users"></i></div>
                <span>All Patients</span>
            </a>
            <a href="{{ route('hospital.roles.index', $slug) }}" class="hms-qa-btn">
                <div class="hms-qa-icon qa-orange"><i class="fa-solid fa-shield-halved"></i></div>
                <span>Roles</span>
            </a>
            <a href="{{ route('hospital.users.create', ['slug' => $slug]) }}" class="hms-qa-btn">
                <div class="hms-qa-icon qa-teal"><i class="fa-solid fa-user-gear"></i></div>
                <span>Add User</span>
            </a>
            <a href="{{ route('hospital.reports.index', $slug) }}" class="hms-qa-btn">
                <div class="hms-qa-icon qa-purple"><i class="fa-solid fa-chart-bar"></i></div>
                <span>Reports</span>
            </a>
            <a href="{{ route('hospital.settings.index', $slug) }}" class="hms-qa-btn">
                <div class="hms-qa-icon qa-gray"><i class="fa-solid fa-gear"></i></div>
                <span>Settings</span>
            </a>
        </div>
    </div>

</div>

@endsection
