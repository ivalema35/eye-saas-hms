@extends('superadmin.layouts.app')
@section('title', 'Dashboard')
@section('page-header', 'Platform Overview')

@section('content')

{{-- ============================================================
     8-Card Premium Stats Row
============================================================ --}}
<div class="sa-stats-grid">
    {{-- Total Hospitals --}}
    <div class="sa-stat-card">
        <div class="sa-stat-icon sa-stat-blue"><i class="fa-solid fa-hospital-user"></i></div>
        <div class="sa-stat-body">
            <div class="sa-stat-label"><i class="fa-solid fa-circle" style="color:#1B4F72"></i> Total Hospitals</div>
            <div class="sa-stat-value">{{ $totalHospitals }}</div>
        </div>
    </div>

    {{-- Active --}}
    <div class="sa-stat-card">
        <div class="sa-stat-icon sa-stat-green"><i class="fa-solid fa-circle-check"></i></div>
        <div class="sa-stat-body">
            <div class="sa-stat-label"><i class="fa-solid fa-circle" style="color:#27AE60"></i> Active</div>
            <div class="sa-stat-value">{{ $activeCount }}</div>
        </div>
    </div>

    {{-- On Trial --}}
    <div class="sa-stat-card">
        <div class="sa-stat-icon sa-stat-orange"><i class="fa-solid fa-flask"></i></div>
        <div class="sa-stat-body">
            <div class="sa-stat-label"><i class="fa-solid fa-circle" style="color:#E67E22"></i> On Trial</div>
            <div class="sa-stat-value">{{ $trialCount }}</div>
        </div>
    </div>

    {{-- Grace Period --}}
    <div class="sa-stat-card">
        <div class="sa-stat-icon sa-stat-amber"><i class="fa-solid fa-hourglass-half"></i></div>
        <div class="sa-stat-body">
            <div class="sa-stat-label"><i class="fa-solid fa-circle" style="color:#D97706"></i> Grace Period</div>
            <div class="sa-stat-value">{{ $graceCount }}</div>
        </div>
    </div>

    {{-- Inactive / Suspended --}}
    <div class="sa-stat-card">
        <div class="sa-stat-icon sa-stat-red"><i class="fa-solid fa-ban"></i></div>
        <div class="sa-stat-body">
            <div class="sa-stat-label"><i class="fa-solid fa-circle" style="color:#C0392B"></i> Inactive / Suspended</div>
            <div class="sa-stat-value">{{ $inactiveCount + $suspendedCount }}</div>
        </div>
    </div>

    {{-- MRR --}}
    <div class="sa-stat-card">
        <div class="sa-stat-icon sa-stat-teal"><i class="fa-solid fa-indian-rupee-sign"></i></div>
        <div class="sa-stat-body">
            <div class="sa-stat-label"><i class="fa-solid fa-circle" style="color:#1ABC9C"></i> Monthly Recurring Revenue</div>
            <div class="sa-stat-value">&#x20B9;{{ number_format($monthlyRevenue) }}</div>
        </div>
    </div>

    {{-- Revenue This Month --}}
    <div class="sa-stat-card">
        <div class="sa-stat-icon sa-stat-purple"><i class="fa-solid fa-coins"></i></div>
        <div class="sa-stat-body">
            <div class="sa-stat-label"><i class="fa-solid fa-circle" style="color:#7C3AED"></i> Revenue This Month</div>
            <div class="sa-stat-value">&#x20B9;{{ number_format($monthlyRevenue) }}</div>
        </div>
    </div>

    {{-- Expiring This Week --}}
    <div class="sa-stat-card">
        <div class="sa-stat-icon sa-stat-warning"><i class="fa-solid fa-calendar-xmark"></i></div>
        <div class="sa-stat-body">
            <div class="sa-stat-label"><i class="fa-solid fa-circle" style="color:#EA580C"></i> Expiring This Week</div>
            <div class="sa-stat-value">{{ $expiringThisWeek }}</div>
            @if($expiringThisWeek > 0)
                <div class="sa-stat-sub">Needs attention</div>
            @endif
        </div>
    </div>
</div>

{{-- ============================================================
     Charts Row — Revenue + Status Distribution
============================================================ --}}
<div class="sa-charts-row">

    {{-- Revenue Trend (Bar Chart) --}}
    <div class="sa-premium-card sa-chart-card-lg">
        <div class="sa-premium-card-header">
            <div class="sa-premium-card-header-left">
                <div class="sa-premium-card-icon" style="background:linear-gradient(135deg,#D6EAF8,#EBF5FB);color:#1B4F72">
                    <i class="fa-solid fa-chart-bar"></i>
                </div>
                <div>
                    <h3 class="sa-premium-card-title">Revenue Trend</h3>
                    <div class="sa-premium-card-subtitle">Last 6 months</div>
                </div>
            </div>
        </div>
        <div class="sa-premium-card-body" style="position:relative">
            <canvas id="revenueChart" height="220" class="sa-chart-canvas"></canvas>
        </div>
    </div>

    {{-- Status Distribution (Donut) --}}
    <div class="sa-premium-card sa-chart-card-sm">
        <div class="sa-premium-card-header">
            <div class="sa-premium-card-header-left">
                <div class="sa-premium-card-icon" style="background:linear-gradient(135deg,#D5F5E3,#E8F8F0);color:#27AE60">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div>
                    <h3 class="sa-premium-card-title">Status Distribution</h3>
                    <div class="sa-premium-card-subtitle">Hospital status breakdown</div>
                </div>
            </div>
        </div>
        <div class="sa-premium-card-body" style="display:flex;flex-direction:column;align-items:center;gap:1.25rem">
            <canvas id="statusChart" style="max-width:220px;max-height:220px"></canvas>
            <div class="sa-donut-legend">
                <span class="sa-legend-item">
                    <span class="sa-legend-dot" style="background:#27AE60"></span>
                    Active ({{ $activeCount }})
                </span>
                <span class="sa-legend-item">
                    <span class="sa-legend-dot" style="background:#E67E22"></span>
                    Trial ({{ $trialCount }})
                </span>
                <span class="sa-legend-item">
                    <span class="sa-legend-dot" style="background:#F59E0B"></span>
                    Grace ({{ $graceCount }})
                </span>
                <span class="sa-legend-item">
                    <span class="sa-legend-dot" style="background:#C0392B"></span>
                    Suspended ({{ $suspendedCount }})
                </span>
                <span class="sa-legend-item">
                    <span class="sa-legend-dot" style="background:#94A3B8"></span>
                    Inactive ({{ $inactiveCount }})
                </span>
            </div>
        </div>
    </div>

</div>

{{-- ============================================================
     Charts Row 2 — Registrations + Subscription Cycles
============================================================ --}}
<div class="sa-charts-row" style="margin-top:1.5rem">

    {{-- New Registrations (Line Chart) --}}
    <div class="sa-premium-card sa-chart-card-lg">
        <div class="sa-premium-card-header">
            <div class="sa-premium-card-header-left">
                <div class="sa-premium-card-icon" style="background:linear-gradient(135deg,#D1F2EB,#E8F8F5);color:#1ABC9C">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div>
                    <h3 class="sa-premium-card-title">New Registrations</h3>
                    <div class="sa-premium-card-subtitle">Last 6 months</div>
                </div>
            </div>
        </div>
        <div class="sa-premium-card-body">
            <canvas id="regChart" height="220" class="sa-chart-canvas"></canvas>
        </div>
    </div>

    {{-- Subscription Cycles (Pie) --}}
    <div class="sa-premium-card sa-chart-card-sm">
        <div class="sa-premium-card-header">
            <div class="sa-premium-card-header-left">
                <div class="sa-premium-card-icon" style="background:linear-gradient(135deg,#F5F3FF,#EDE9FE);color:#7C3AED">
                    <i class="fa-solid fa-rotate"></i>
                </div>
                <div>
                    <h3 class="sa-premium-card-title">Subscription Cycles</h3>
                    <div class="sa-premium-card-subtitle">Active plan distribution</div>
                </div>
            </div>
        </div>
        <div class="sa-premium-card-body" style="display:flex;flex-direction:column;align-items:center;gap:1rem">
            <canvas id="cycleChart" style="max-width:200px;max-height:200px"></canvas>
            <div class="sa-donut-legend">
                <span class="sa-legend-item">
                    <span class="sa-legend-dot" style="background:#2980B9"></span>
                    Monthly ({{ $cycleMonthly }})
                </span>
                <span class="sa-legend-item">
                    <span class="sa-legend-dot" style="background:#27AE60"></span>
                    Quarterly ({{ $cycleQuarterly }})
                </span>
                <span class="sa-legend-item">
                    <span class="sa-legend-dot" style="background:#1ABC9C"></span>
                    Yearly ({{ $cycleYearly }})
                </span>
            </div>
        </div>
    </div>

</div>

{{-- ============================================================
     Recently Registered Hospitals (Premium Table)
============================================================ --}}
<div class="sa-premium-card" style="margin-top:1.5rem">
    <div class="sa-premium-card-header">
        <div class="sa-premium-card-header-left">
            <div class="sa-premium-card-icon" style="background:linear-gradient(135deg,#F5F3FF,#EDE9FE);color:#7C3AED">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div>
                <h3 class="sa-premium-card-title">Recently Registered Hospitals</h3>
                <div class="sa-premium-card-subtitle">Latest {{ $recentHospitals->count() }} registrations</div>
            </div>
        </div>
        <a href="{{ route('superadmin.hospitals.index') }}" class="sa-premium-btn sa-premium-btn-secondary sa-premium-btn-sm">
            <i class="fa-solid fa-list"></i> View All
        </a>
    </div>

    @if($recentHospitals->isEmpty())
        <div class="sa-empty-state">
            <div class="sa-empty-state-icon">
                <i class="fa-solid fa-hospital"></i>
            </div>
            <h4>No Hospitals Registered</h4>
            <p>Hospitals will appear here once they register on the platform.</p>
        </div>
    @else
        <div class="sa-premium-table-wrap" style="border-radius:0;border-left:none;border-right:none;border-bottom:none;box-shadow:none">
            <table class="sa-premium-table">
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
                                <span class="sa-cell-name">{{ $hospital->name }}</span>
                                <div class="sa-cell-muted">{{ $hospital->slug }}</div>
                            </td>
                            <td>{{ $hospital->admin_name ?? '—' }}</td>
                            <td>{{ $hospital->city ?? '—' }}</td>
                            <td>
                                <span style="color:#94A3B8;font-size:.825rem">—</span>
                            </td>
                            <td>
                                <span class="sa-premium-badge sa-premium-badge-{{ strtolower($hospital->status) }}">
                                    <i class="fa-solid fa-circle"></i>
                                    {{ ucfirst($hospital->status) }}
                                </span>
                            </td>
                            <td style="white-space:nowrap;font-size:.825rem;color:#64748B">
                                {{ $hospital->created_at->format('d M Y') }}
                            </td>
                            <td>
                                <a href="{{ route('superadmin.hospitals.show', $hospital) }}"
                                   class="sa-premium-btn sa-premium-btn-secondary sa-premium-btn-sm"
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

    /* --- Revenue Bar Chart (Premium) --- */
    var ctx1 = document.getElementById('revenueChart');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: amounts,
                    backgroundColor: [
                        'rgba(27,79,114,.6)',
                        'rgba(27,79,114,.65)',
                        'rgba(27,79,114,.7)',
                        'rgba(27,79,114,.75)',
                        'rgba(27,79,114,.8)',
                        'rgba(27,79,114,.85)',
                    ],
                    borderColor: '#1B4F72',
                    borderWidth: 2,
                    borderRadius: 6,
                    hoverBackgroundColor: '#2980B9',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0D2137',
                        titleFont: { weight: '700' },
                        padding: 10,
                        cornerRadius: 8,
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
                        grid: { color: 'rgba(0,0,0,.04)', drawBorder: false },
                        ticks: {
                            callback: function (val) {
                                return '₹' + (val >= 1000 ? (val / 1000).toFixed(0) + 'k' : val);
                            },
                            font: { size: 11, family: "'Inter', sans-serif" },
                            color: '#94A3B8'
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11, family: "'Inter', sans-serif" }, color: '#94A3B8' }
                    }
                }
            }
        });
    }

    /* --- Status Donut Chart (Premium) --- */
    var ctx2 = document.getElementById('statusChart');
    if (ctx2) {
        new Chart(ctx2, {
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
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 10
                }]
            },
            options: {
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0D2137',
                        padding: 10,
                        cornerRadius: 8,
                    }
                }
            }
        });
    }

    /* --- Registrations Line Chart (Premium) --- */
    var ctx3 = document.getElementById('regChart');
    if (ctx3) {
        new Chart(ctx3, {
            type: 'line',
            data: {
                labels: regMonths,
                datasets: [{
                    label: 'New Hospitals',
                    data: regCounts,
                    borderColor: '#2980B9',
                    backgroundColor: function(context) {
                        var chart = context.chart;
                        var {ctx, chartArea} = chart;
                        if (!chartArea) return 'rgba(41,128,185,.15)';
                        var gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                        gradient.addColorStop(0, 'rgba(41,128,185,.3)');
                        gradient.addColorStop(1, 'rgba(41,128,185,.02)');
                        return gradient;
                    },
                    borderWidth: 3,
                    pointBackgroundColor: '#2980B9',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    tension: 0.35,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0D2137',
                        padding: 10,
                        cornerRadius: 8,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: { size: 11, family: "'Inter', sans-serif" },
                            color: '#94A3B8'
                        },
                        grid: { color: 'rgba(0,0,0,.04)', drawBorder: false }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11, family: "'Inter', sans-serif" }, color: '#94A3B8' }
                    }
                }
            }
        });
    }

    /* --- Subscription Cycles Pie Chart (Premium) --- */
    var ctx4 = document.getElementById('cycleChart');
    if (ctx4) {
        new Chart(ctx4, {
            type: 'pie',
            data: {
                labels: ['Monthly', 'Quarterly', 'Yearly'],
                datasets: [{
                    data: [{{ $cycleMonthly }}, {{ $cycleQuarterly }}, {{ $cycleYearly }}],
                    backgroundColor: ['#2980B9', '#27AE60', '#1ABC9C'],
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 10,
                }]
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0D2137',
                        padding: 10,
                        cornerRadius: 8,
                    }
                }
            }
        });
    }
}());
</script>
@endpush
