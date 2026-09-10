@extends('hospital.layouts.app')
@section('title', 'Dashboard')
@section('page-header', 'Dashboard')

@section('content')

{{-- ============================================================
     Stat Cards
============================================================ --}}
<div class="hms-stats-grid">
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-blue"><i class="fa-solid fa-user-injured"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Total Patients</div>
            <div class="hms-stat-value">{{ number_format($totalPatients) }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-green"><i class="fa-solid fa-calendar-day"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Today&#x2019;s Patients</div>
            <div class="hms-stat-value">{{ $todayPatients }}</div>
            <div class="hms-stat-meta">{{ now()->format('d M Y') }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-orange"><i class="fa-solid fa-scalpel"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">OT Scheduled Today</div>
            <div class="hms-stat-value">{{ $otBookingsToday }}</div>
            <div class="hms-stat-meta">Scheduled surgeries</div>
        </div>
    </div>
</div>

{{-- ============================================================
     Welcome Card
============================================================ --}}
<div class="hms-dashboard-row">

    {{-- Role Welcome Card --}}
    <div class="hms-card hms-welcome-card">
        <div class="hms-card-body hms-welcome-body">
            @php
                $roleSlug = auth('hospital_user')->user()?->role?->slug;
                $roleName = auth('hospital_user')->user()?->role?->name ?? 'Staff';
            @endphp
            <div class="hms-welcome-icon">
                <i class="fa-solid {{ match($roleSlug) {
                    'hospital_admin' => 'fa-user-tie',
                    'doctor' => 'fa-user-doctor',
                    'receptionist' => 'fa-headset',
                    default => 'fa-user-nurse',
                } }}"></i>
            </div>
            <div class="hms-welcome-text">
                <h3>Welcome back!</h3>
                <p>You are signed in as <strong>{{ $roleName }}</strong>.</p>
            </div>
            <div class="hms-welcome-date">
                <i class="fa-regular fa-calendar"></i> {{ now()->format('l, d F Y') }}
            </div>
        </div>
    </div>

</div>

@endsection
