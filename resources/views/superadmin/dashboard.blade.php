@extends('superadmin.layouts.app')
@section('title', 'Dashboard')
@section('page-header', 'Platform Dashboard')

@section('content')

{{-- ============================================================
     8-Card Stats Row
============================================================ --}}
<div class="sa-stats-grid">
    <div class="sa-stat-card">
        <div class="sa-stat-icon sa-stat-blue"><i class="fa-solid fa-hospital-user"></i></div>
        <div class="sa-stat-body">
            <div class="sa-stat-label">Total Hospitals</div>
            <div class="sa-stat-value">{{ $totalHospitals }}</div>
        </div>
    </div>
    <div class="sa-stat-card">
        <div class="sa-stat-icon sa-stat-green"><i class="fa-solid fa-circle-check"></i></div>
        <div class="sa-stat-body">
            <div class="sa-stat-label">Active</div>
            <div class="sa-stat-value">{{ $activeCount }}</div>
        </div>
    </div>
    <div class="sa-stat-card">
        <div class="sa-stat-icon sa-stat-orange"><i class="fa-solid fa-flask"></i></div>
        <div class="sa-stat-body">
            <div class="sa-stat-label">On Trial</div>
            <div class="sa-stat-value">{{ $trialCount }}</div>
        </div>
    </div>
    <div class="sa-stat-card">
        <div class="sa-stat-icon sa-stat-amber"><i class="fa-solid fa-hourglass-half"></i></div>
        <div class="sa-stat-body">
            <div class="sa-stat-label">Grace Period</div>
            <div class="sa-stat-value">{{ $graceCount }}</div>
        </div>
    </div>
    <div class="sa-stat-card">
        <div class="sa-stat-icon sa-stat-red"><i class="fa-solid fa-ban"></i></div>
        <div class="sa-stat-body">
            <div class="sa-stat-label">Inactive / Suspended</div>
            <div class="sa-stat-value">{{ $inactiveCount + $suspendedCount }}</div>
        </div>
    </div>
    <div class="sa-stat-card">
        <div class="sa-stat-icon sa-stat-teal"><i class="fa-solid fa-indian-rupee-sign"></i></div>
        <div class="sa-stat-body">
            <div class="sa-stat-label">MRR</div>
            <div class="sa-stat-value">&#x20B9;{{ number_format($monthlyRevenue) }}</div>
        </div>
    </div>
    <div class="sa-stat-card">
        <div class="sa-stat-icon sa-stat-purple"><i class="fa-solid fa-coins"></i></div>
        <div class="sa-stat-body">
            <div class="sa-stat-label">Revenue This Month</div>
            <div class="sa-stat-value">&#x20B9;{{ number_format($monthlyRevenue) }}</div>
        </div>
    </div>
    <div class="sa-stat-card">
        <div class="sa-stat-icon sa-stat-warning"><i class="fa-solid fa-calendar-xmark"></i></div>
        <div class="sa-stat-body">
            <div class="sa-stat-label">Expiring This Week</div>
            <div class="sa-stat-value">{{ $expiringThisWeek }}</div>
        </div>
    </div>
</div>

{{-- ============================================================
     Charts Row
============================================================ --}}
<div class="sa-charts-row">

    {{-- Revenue Trend (Bar Chart) --}}
    <div class="hms-card sa-chart-card-lg">
        <div class="hms-card-header">
            <h3 class="hms-card-title"><i class="fa-solid fa-chart-bar"></i> Revenue Trend (Last 6 Months)</h3>
        </div>
        <div class="hms-card-body">
            <canvas id="revenueChart" height="220"></canvas>
        </div>
    </div>

    {{-- Status Distribution (Donut) --}}
    <div class="hms-card sa-chart-card-sm">
        <div class="hms-card-header">
            <h3 class="hms-card-title"><i class="fa-solid fa-chart-pie"></i> Status Distribution</h3>
        </div>
        <div class="hms-card-body" style="display:flex;flex-direction:column;align-items:center;gap:1.25rem">
            <canvas id="statusChart" style="max-width:220px;max-height:220px"></canvas>
            <div class="sa-donut-legend">
                <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#27AE60"></span>Active ({{ $activeCount }})</span>
                <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#E67E22"></span>Trial ({{ $trialCount }})</span>
                <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#F59E0B"></span>Grace ({{ $graceCount }})</span>
                <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#C0392B"></span>Suspended ({{ $suspendedCount }})</span>
                <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#94A3B8"></span>Inactive ({{ $inactiveCount }})</span>
            </div>
        </div>
    </div>

</div>

<div class="sa-charts-row" style="margin-top:1.25rem">

    <div class="hms-card sa-chart-card-lg">
        <div class="hms-card-header">
            <h3 class="hms-card-title"><i class="fa-solid fa-chart-line"></i> New Registrations (Last 6 Months)</h3>
        </div>
        <div class="hms-card-body">
            <canvas id="regChart" height="220"></canvas>
        </div>
    </div>

    <div class="hms-card sa-chart-card-sm">
        <div class="hms-card-header">
            <h3 class="hms-card-title"><i class="fa-solid fa-chart-pie"></i> Subscription Cycles</h3>
        </div>
        <div class="hms-card-body" style="display:flex;flex-direction:column;align-items:center;gap:1rem">
            <canvas id="cycleChart" style="max-width:200px;max-height:200px"></canvas>
            <div class="sa-donut-legend">
                <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#2980B9"></span>Monthly ({{ $cycleMonthly }})</span>
                <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#27AE60"></span>Quarterly ({{ $cycleQuarterly }})</span>
                <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#1ABC9C"></span>Yearly ({{ $cycleYearly }})</span>
            </div>
        </div>
    </div>

</div>

{{-- ============================================================
     Recent Hospitals Table
============================================================ --}}
<div class="hms-card">
    <div class="hms-card-header">
        <h3 class="hms-card-title"><i class="fa-solid fa-clock-rotate-left"></i> Recently Registered Hospitals</h3>
        <a href="{{ route('superadmin.hospitals.index') }}" class="hms-btn hms-btn-sm hms-btn-secondary">
            <i class="fa-solid fa-list"></i> View All
        </a>
    </div>

    @if($recentHospitals->isEmpty())
        <div class="hms-card-body" style="text-align:center;padding:3rem;color:var(--hms-text-muted)">
            <i class="fa-solid fa-hospital" style="font-size:2.5rem;color:var(--hms-border);margin-bottom:.75rem;display:block"></i>
            No hospitals registered yet.
        </div>
    @else
        <div class="hms-table-wrap">
            <table class="hms-table">
                <thead>
                    <tr>
                        <th>Hospital</th>
                        <th>Admin</th>
                        <th>City</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentHospitals as $hospital)
                        <tr>
                            <td>
                                <strong>{{ $hospital->name }}</strong>
                                <div style="font-size:.72rem;color:var(--hms-text-muted);font-family:var(--hms-font-mono)">{{ $hospital->slug }}</div>
                            </td>
                            <td>{{ $hospital->admin_name ?? '—' }}</td>
                            <td>{{ $hospital->city ?? '—' }}</td>
                            <td>
                                <span style="color:var(--hms-text-muted)">—</span>
                            </td>
                            <td>
                                <span class="hms-badge hms-badge-{{ strtolower($hospital->status) }}">
                                    <i class="fa-solid fa-circle" style="font-size:.45rem"></i>
                                    {{ ucfirst($hospital->status) }}
                                </span>
                            </td>
                            <td style="white-space:nowrap">{{ $hospital->created_at->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('superadmin.hospitals.show', $hospital) }}"
                                   class="hms-btn hms-btn-sm hms-btn-secondary"
                                   title="View Hospital">
                                    <i class="fa-solid fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"
        integrity="sha256-oVuQa3dJgpxXqV5PKdWxd+3bpRWijMzLzKGVq8QWZVA="
        crossorigin="anonymous"></script>
<script>
(function () {
    'use strict';

    var months = @json($revenueMonths);
    var amounts = @json($revenueAmounts);
    var regMonths = @json($regMonths);
    var regCounts = @json($regCounts);

    /* Revenue Bar Chart */
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'Revenue (₹)',
                data: amounts,
                backgroundColor: 'rgba(27,79,114,.75)',
                borderColor: '#1B4F72',
                borderWidth: 2,
                borderRadius: 6,
                hoverBackgroundColor: '#1ABC9C',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            return ' ₹' + ctx.parsed.y.toLocaleString('en-IN');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,.04)' },
                    ticks: {
                        callback: function (val) {
                            return '₹' + (val >= 1000 ? (val / 1000).toFixed(0) + 'k' : val);
                        },
                        font: { size: 11 }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 } }
                }
            }
        }
    });

    /* Status Donut Chart */
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Trial', 'Grace', 'Suspended', 'Inactive'],
            datasets: [{
                data: [
                    {{ $activeCount }},
                    {{ $trialCount }},
                    {{ $graceCount }},
                    {{ $suspendedCount }},
                    {{ $inactiveCount }}
                ],
                backgroundColor: ['#27AE60','#E67E22','#F59E0B','#C0392B','#94A3B8'],
                borderWidth: 0,
                hoverOffset: 8
            }]
        },
        options: {
            cutout: '70%',
            plugins: {
                legend: { display: false }
            }
        }
    });

    new Chart(document.getElementById('regChart'), {
        type: 'line',
        data: {
            labels: regMonths,
            datasets: [{
                label: 'New Hospitals',
                data: regCounts,
                borderColor: '#2980B9',
                backgroundColor: 'rgba(41,128,185,.15)',
                borderWidth: 2,
                pointBackgroundColor: '#2980B9',
                pointRadius: 4,
                tension: 0.3,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, font: { size: 11 } },
                    grid: { color: 'rgba(0,0,0,.04)' }
                },
                x: { grid: { display: false }, ticks: { font: { size: 11 } } }
            }
        }
    });

    new Chart(document.getElementById('cycleChart'), {
        type: 'pie',
        data: {
            labels: ['Monthly', 'Quarterly', 'Yearly'],
            datasets: [{
                data: [{{ $cycleMonthly }}, {{ $cycleQuarterly }}, {{ $cycleYearly }}],
                backgroundColor: ['#2980B9', '#27AE60', '#1ABC9C'],
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            plugins: { legend: { display: false } }
        }
    });
}());
</script>
@endpush
