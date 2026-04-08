@extends('hospital.layouts.app')
@section('title', 'Receptionist Dashboard')
@section('page-header', 'Receptionist Dashboard')

@section('content')

@php
    $pendingFocRequests = $pendingFocRequests ?? collect();
@endphp

{{-- ============================================================
     Today Collection Cards
============================================================ --}}
<div class="hms-stats-grid">
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-blue"><i class="fa-solid fa-indian-rupee-sign"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Gross Collection</div>
            <div class="hms-stat-value">₹{{ number_format($todayGross, 2) }}</div>
            <div class="hms-stat-meta">{{ now()->format('d M Y') }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-red"><i class="fa-solid fa-hand-holding-heart"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">FOC Deductions</div>
            <div class="hms-stat-value">₹{{ number_format($todayFocAmount, 2) }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-green"><i class="fa-solid fa-wallet"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Net Collection</div>
            <div class="hms-stat-value">₹{{ number_format($todayNet, 2) }}</div>
        </div>
    </div>
</div>

{{-- ============================================================
     Stats Row
============================================================ --}}
<div class="hms-stats-grid" style="margin-top:1rem">
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-blue"><i class="fa-solid fa-person-walking"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Walk-ins Today</div>
            <div class="hms-stat-value">{{ $todayWalkinCount }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-teal"><i class="fa-solid fa-phone"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Phone Appointments</div>
            <div class="hms-stat-value">{{ $todayPhoneCount }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-orange"><i class="fa-solid fa-clock"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">FOC Pending</div>
            <div class="hms-stat-value">{{ $focPendingCount }}</div>
        </div>
    </div>
</div>

{{-- ============================================================
     Doctors Overview Cards
============================================================ --}}
<div class="hms-card" style="margin-top:1.5rem">
    <div class="hms-card-header">
        <h3 class="hms-card-title"><i class="fa-solid fa-user-doctor"></i> Doctors — Today Overview</h3>
    </div>
    <div class="hms-card-body">
        <div class="hms-stats-grid">
            @forelse($doctors as $doc)
                <div class="hms-stat-card" style="border-left:3px solid #1B4F72">
                    <div class="hms-stat-body">
                        <div class="hms-stat-label">{{ $doc->name }}</div>
                        <div class="hms-stat-value">{{ $doc->today_total }}</div>
                        <div class="hms-stat-meta">
                            P: {{ $doc->today_primary }} &bull; S: {{ $doc->today_secondary }}
                        </div>
                    </div>
                </div>
            @empty
                <p style="color:#9CA3AF;margin:0">No doctors found</p>
            @endforelse
        </div>
    </div>
</div>

{{-- ============================================================
     Today's Patients Table
============================================================ --}}
<div class="hms-card" style="margin-top:1.5rem">
    <div class="hms-card-header">
        <h3 class="hms-card-title"><i class="fa-solid fa-users"></i> My Patients — Today</h3>
        <a href="{{ route('hospital.patients.create', ['slug' => $slug]) }}" class="hms-btn hms-btn-sm hms-btn-primary">
            <i class="fa-solid fa-user-plus"></i> New Patient
        </a>
    </div>
    <div class="hms-card-body" style="padding:0">
        <table class="hms-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>MRD</th>
                    <th>Name</th>
                    <th>Age / Gender</th>
                    <th>Contact</th>
                    <th>Doctor</th>
                    <th>Fee (₹)</th>
                    <th>Type</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($todayPatients as $i => $p)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><strong>{{ $p->patient_code }}</strong></td>
                        <td>{{ $p->full_name }}</td>
                        <td>{{ $p->age }}y / {{ ucfirst($p->gender) }}</td>
                        <td>{{ $p->contact_no }}</td>
                        <td>{{ $p->doctor?->name ?? '—' }}</td>
                        <td>{{ number_format($p->case_fee, 2) }}</td>
                        <td>
                            <span class="hms-badge {{ $p->type === 'walkin' ? 'hms-badge-info' : 'hms-badge-warning' }}">
                                {{ ucfirst($p->type) }}
                            </span>
                        </td>
                        <td>{{ $p->created_at->format('h:i A') }}</td>
                        <td>
                            <a href="{{ route('hospital.patients.show', ['slug' => $slug, 'patient' => $p->id]) }}"
                               class="hms-btn hms-btn-sm hms-btn-outline" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center" style="color:#9CA3AF;padding:2rem">No patients registered today</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="hms-card" style="margin-top:1.5rem">
    <div class="hms-card-header">
        <h3 class="hms-card-title"><i class="fa-solid fa-hand-holding-heart"></i> Pending FOC Requests</h3>
        <span class="hms-badge hms-badge-warning">{{ $pendingFocRequests->count() }} pending</span>
    </div>
    <div class="hms-card-body" style="padding:0">
        <table class="hms-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Doctor</th>
                    <th>Patient</th>
                    <th>MRD</th>
                    <th>Fee to Waive</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendingFocRequests as $i => $foc)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $foc->doctor?->name ?? '—' }}</td>
                        <td>{{ $foc->patient?->full_name ?? '—' }}</td>
                        <td>{{ $foc->patient?->patient_code ?? '—' }}</td>
                        <td>₹{{ number_format((float) $foc->foc_fee, 2) }}</td>
                        <td>
                            <form method="POST" action="{{ route('hospital.foc.accept', ['slug' => $slug, 'id' => $foc->id]) }}" style="display:inline">
                                @csrf
                                <button type="submit" class="hms-btn hms-btn-sm hms-btn-success">Accept</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center" style="color:#9CA3AF;padding:2rem">No pending FOC requests</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
