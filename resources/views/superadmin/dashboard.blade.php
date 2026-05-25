@extends('superadmin.layouts.app')
@section('title', 'Dashboard')
@section('page-header', 'Platform Overview')

@section('content')

{{-- ============================================================
     Stat Cards Row 1 — Hospital Counts
============================================================ --}}
<div class="hms-stats-grid">
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-blue"><i class="bi bi-hospital-fill"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Total Hospitals</div>
            <div class="hms-stat-value">{{ $totalHospitals }}</div>
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
        <div class="hms-stat-icon hsi-orange"><i class="bi bi-capsule"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">On Trial</div>
            <div class="hms-stat-value">{{ $trialCount }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-amber"><i class="bi bi-hourglass-split"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Grace Period</div>
            <div class="hms-stat-value">{{ $graceCount }}</div>
        </div>
    </div>
</div>

{{-- Stat Cards Row 2 — Revenue + Alerts --}}
<div class="hms-stats-grid">
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-red"><i class="bi bi-x-circle-fill"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Inactive / Suspended</div>
            <div class="hms-stat-value">{{ $inactiveCount + $suspendedCount }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-teal"><i class="bi bi-currency-rupee"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Monthly Recurring Revenue</div>
            <div class="hms-stat-value">&#x20B9;{{ number_format($monthlyRevenue) }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-purple"><i class="bi bi-cash-stack"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Revenue This Month</div>
            <div class="hms-stat-value">&#x20B9;{{ number_format($monthlyRevenue) }}</div>
        </div>
    </div>
    <div class="hms-stat-card">
        <div class="hms-stat-icon hsi-orange"><i class="bi bi-calendar-x-fill"></i></div>
        <div class="hms-stat-body">
            <div class="hms-stat-label">Expiring This Week</div>
            <div class="hms-stat-value">{{ $expiringThisWeek }}</div>
            @if($expiringThisWeek > 0)
                <div class="hms-stat-meta">Needs attention</div>
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
                <div class="sa-premium-card-icon" style="background:rgba(27,79,114,.08);color:#1B4F72">
                    <i class="bi bi-bar-chart-fill"></i>
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
                <div class="sa-premium-card-icon" style="background:rgba(39,174,96,.08);color:#27AE60">
                    <i class="bi bi-pie-chart-fill"></i>
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
                <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#27AE60"></span>Active ({{ $activeCount }})</span>
                <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#E67E22"></span>Trial ({{ $trialCount }})</span>
                <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#F59E0B"></span>Grace ({{ $graceCount }})</span>
                <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#C0392B"></span>Suspended ({{ $suspendedCount }})</span>
                <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#94A3B8"></span>Inactive ({{ $inactiveCount }})</span>
            </div>
        </div>
    </div>

</div>

{{-- Charts Row 2 — Registrations + Subscription Cycles --}}
<div class="sa-charts-row" style="margin-top:1.5rem">

    <div class="sa-premium-card sa-chart-card-lg">
        <div class="sa-premium-card-header">
            <div class="sa-premium-card-header-left">
                <div class="sa-premium-card-icon" style="background:rgba(26,188,156,.08);color:#1ABC9C">
                    <i class="bi bi-graph-up"></i>
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

    <div class="sa-premium-card sa-chart-card-sm">
        <div class="sa-premium-card-header">
            <div class="sa-premium-card-header-left">
                <div class="sa-premium-card-icon" style="background:rgba(124,58,237,.08);color:#7C3AED">
                    <i class="bi bi-arrow-repeat"></i>
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
                <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#2980B9"></span>Monthly ({{ $cycleMonthly }})</span>
                <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#27AE60"></span>Quarterly ({{ $cycleQuarterly }})</span>
                <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#1ABC9C"></span>Yearly ({{ $cycleYearly }})</span>
            </div>
        </div>
    </div>

</div>

{{-- ============================================================
     Recently Registered Hospitals
============================================================ --}}
<div class="hms-card" style="margin-top:1.5rem;padding:0">
    <div class="hms-card-header">
        <h3 class="hms-card-title">
            <i class="bi bi-clock-history" style="color:#7C3AED"></i>
            Recently Registered Hospitals
        </h3>
        <a href="{{ route('superadmin.hospitals.index') }}" class="hms-btn hms-btn-outline hms-btn-sm">
            <i class="bi bi-list-ul"></i> View All
        </a>
    </div>

    @if($recentHospitals->isEmpty())
        <x-empty-state
            icon="bi bi-hospital-fill"
            title="No Hospitals Registered"
            description="Hospitals will appear here once they register on the platform." />
    @else
        <div class="hms-table-wrap" style="border:none">
            <table class="hms-table">
                <thead>
                    <tr>
                        <th>Hospital</th>
                        <th>Admin</th>
                        <th>City</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentHospitals as $hospital)
                        <tr>
                            <td>
                                <span style="font-weight:600;color:#1A202C">{{ $hospital->name }}</span>
                                <div style="font-size:.75rem;color:#64748B">{{ $hospital->slug }}</div>
                            </td>
                            <td>{{ $hospital->admin_name ?? '—' }}</td>
                            <td>{{ $hospital->city ?? '—' }}</td>
                            <td>
                                <span class="hms-badge hms-badge-{{ strtolower($hospital->status) }}">
                                    {{ ucfirst($hospital->status) }}
                                </span>
                            </td>
                            <td style="white-space:nowrap;font-size:.825rem;color:#64748B">
                                {{ $hospital->created_at->format('d M Y') }}
                            </td>
                            <td style="text-align:right">
                                <a href="{{ route('superadmin.hospitals.show', $hospital) }}"
                                   class="hms-btn-icon" data-tooltip="View Details">
                                    <i class="bi bi-eye-fill"></i>
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

    var months    = @json($revenueMonths);
    var amounts   = @json($revenueAmounts);
    var regMonths = @json($regMonths);
    var regCounts = @json($regCounts);

    var ctx1 = document.getElementById('revenueChart');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: amounts,
                    backgroundColor: 'rgba(27,79,114,.7)',
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
                        callbacks: { label: ctx => ' ₹' + ctx.parsed.y.toLocaleString('en-IN') }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,.04)', drawBorder: false },
                        ticks: { callback: v => '₹' + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v), font: { size: 11 }, color: '#94A3B8' }
                    },
                    x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#94A3B8' } }
                }
            }
        });
    }

    var ctx2 = document.getElementById('statusChart');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Active','Trial','Grace','Suspended','Inactive'],
                datasets: [{
                    data: [{{ $activeCount }},{{ $trialCount }},{{ $graceCount }},{{ $suspendedCount }},{{ $inactiveCount }}],
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
                    tooltip: { backgroundColor: '#0D2137', padding: 10, cornerRadius: 8 }
                }
            }
        });
    }

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
                        var {ctx, chartArea} = context.chart;
                        if (!chartArea) return 'rgba(41,128,185,.15)';
                        var g = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                        g.addColorStop(0, 'rgba(41,128,185,.3)');
                        g.addColorStop(1, 'rgba(41,128,185,.02)');
                        return g;
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
                    tooltip: { backgroundColor: '#0D2137', padding: 10, cornerRadius: 8 }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 }, color: '#94A3B8' }, grid: { color: 'rgba(0,0,0,.04)', drawBorder: false } },
                    x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#94A3B8' } }
                }
            }
        });
    }

    var ctx4 = document.getElementById('cycleChart');
    if (ctx4) {
        new Chart(ctx4, {
            type: 'pie',
            data: {
                labels: ['Monthly','Quarterly','Yearly'],
                datasets: [{
                    data: [{{ $cycleMonthly }},{{ $cycleQuarterly }},{{ $cycleYearly }}],
                    backgroundColor: ['#2980B9','#27AE60','#1ABC9C'],
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 10,
                }]
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: '#0D2137', padding: 10, cornerRadius: 8 }
                }
            }
        });
    }
}());
</script>
@endpush
