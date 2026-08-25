@extends('superadmin.layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="sa-dash">

    {{-- Welcome --}}
    <div class="sa-dashboard-welcome sa-reveal" style="--d:0s">
        <div class="sa-welcome-copy">
            <div class="sa-welcome-date">
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                {{ now()->format('d M, Y') }}
            </div>
            <h2>Welcome back, {{ auth('superadmin')->user()?->name ?? 'Admin' }}</h2>
            <p>Platform overview — hospitals, revenue, trials and subscriptions.</p>
        </div>
        <div class="sa-welcome-actions">
            <button type="button" class="sa-btn-solid" data-bs-toggle="modal" data-bs-target="#addHospitalModal">
                <i class="bi bi-plus-lg"></i> Add Hospital
            </button>
            <a href="{{ route('superadmin.hospitals.index') }}" class="sa-btn-ghost">
                <i class="bi bi-hospital"></i> View Hospitals
            </a>
        </div>
    </div>

    {{-- KPI cards — reference layout, your data --}}
    <div class="sa-kpi-grid">
        @php
            $requestsHref = ($pendingCount ?? 0) === 1 && !empty($latestPending)
                ? route('superadmin.hospitals.show', $latestPending)
                : route('superadmin.hospitals.index', ['status' => 'pending']);
        @endphp
        <a href="{{ $requestsHref }}" class="sa-kpi sa-kpi--navy sa-reveal" style="--d:.02s;text-decoration:none;color:inherit">
            <span class="sa-kpi-gloss" aria-hidden="true"></span>
            <div class="sa-kpi-top">
                <div class="sa-kpi-icon"><i class="bi bi-send-fill"></i></div>
                @if(($pendingCount ?? 0) > 0)
                    <span class="sa-kpi-chip sa-kpi-chip--new">{{ $pendingCount }} New</span>
                @endif
            </div>
            <strong class="sa-kpi-value">{{ $pendingCount ?? 0 }}</strong>
            <span class="sa-kpi-label">Registration Requests</span>
        </a>

        <article class="sa-kpi sa-kpi--navy sa-reveal" style="--d:.04s">
            <span class="sa-kpi-gloss" aria-hidden="true"></span>
            <div class="sa-kpi-top">
                <div class="sa-kpi-icon"><i class="bi bi-hospital"></i></div>
                <span class="sa-kpi-chip">{{ $activeCount }} Live</span>
            </div>
            <strong class="sa-kpi-value">{{ $totalHospitals }}</strong>
            <span class="sa-kpi-label">Total Hospitals</span>
        </article>

        <article class="sa-kpi sa-kpi--teal sa-reveal" style="--d:.08s">
            <span class="sa-kpi-gloss" aria-hidden="true"></span>
            <div class="sa-kpi-top">
                <div class="sa-kpi-icon"><i class="bi bi-check2-circle"></i></div>
                <span class="sa-kpi-chip sa-kpi-chip--ok">Active</span>
            </div>
            <strong class="sa-kpi-value">{{ $activeCount }}</strong>
            <span class="sa-kpi-label">Active</span>
        </article>

        <article class="sa-kpi sa-kpi--blue sa-reveal" style="--d:.12s">
            <span class="sa-kpi-gloss" aria-hidden="true"></span>
            <div class="sa-kpi-top">
                <div class="sa-kpi-icon"><i class="bi bi-capsule"></i></div>
                <span class="sa-kpi-chip">Trial</span>
            </div>
            <strong class="sa-kpi-value">{{ $trialCount }}</strong>
            <span class="sa-kpi-label">On Trial</span>
        </article>

        <article class="sa-kpi sa-kpi--sky sa-reveal" style="--d:.16s">
            <span class="sa-kpi-gloss" aria-hidden="true"></span>
            <div class="sa-kpi-top">
                <div class="sa-kpi-icon"><i class="bi bi-hourglass-split"></i></div>
                <span class="sa-kpi-chip">Grace</span>
            </div>
            <strong class="sa-kpi-value">{{ $graceCount }}</strong>
            <span class="sa-kpi-label">Grace Period</span>
        </article>

        <article class="sa-kpi sa-kpi--slate sa-reveal" style="--d:.2s">
            <span class="sa-kpi-gloss" aria-hidden="true"></span>
            <div class="sa-kpi-top">
                <div class="sa-kpi-icon"><i class="bi bi-x-circle"></i></div>
                <span class="sa-kpi-chip">{{ $suspendedCount }} Susp</span>
            </div>
            <strong class="sa-kpi-value">{{ $inactiveCount + $suspendedCount }}</strong>
            <span class="sa-kpi-label">Inactive / Suspended</span>
        </article>

        <article class="sa-kpi sa-kpi--teal sa-reveal" style="--d:.24s">
            <span class="sa-kpi-gloss" aria-hidden="true"></span>
            <div class="sa-kpi-top">
                <div class="sa-kpi-icon"><i class="bi bi-cash-coin"></i></div>
                <span class="sa-kpi-chip">MRR</span>
            </div>
            <strong class="sa-kpi-value">&#x20B9;{{ number_format($monthlyRevenue) }}</strong>
            <span class="sa-kpi-label">Monthly Recurring Revenue</span>
        </article>

        <article class="sa-kpi sa-kpi--navy sa-reveal" style="--d:.28s">
            <span class="sa-kpi-gloss" aria-hidden="true"></span>
            <div class="sa-kpi-top">
                <div class="sa-kpi-icon"><i class="bi bi-cash-stack"></i></div>
                <span class="sa-kpi-chip">Month</span>
            </div>
            <strong class="sa-kpi-value">&#x20B9;{{ number_format($monthlyRevenue) }}</strong>
            <span class="sa-kpi-label">Revenue This Month</span>
        </article>

        <article class="sa-kpi sa-kpi--amber sa-reveal {{ $expiringThisWeek > 0 ? 'sa-kpi--alert' : '' }}" style="--d:.32s">
            <span class="sa-kpi-gloss" aria-hidden="true"></span>
            <div class="sa-kpi-top">
                <div class="sa-kpi-icon"><i class="bi bi-calendar-x"></i></div>
                @if($expiringThisWeek > 0)
                    <span class="sa-kpi-chip sa-kpi-chip--warn">Alert</span>
                @else
                    <span class="sa-kpi-chip sa-kpi-chip--ok">Clear</span>
                @endif
            </div>
            <strong class="sa-kpi-value">{{ $expiringThisWeek }}</strong>
            <span class="sa-kpi-label">Expiring This Week</span>
        </article>
    </div>

    {{-- Charts row 1 --}}
    <div class="sa-charts-row">
        <div class="sa-premium-card sa-chart-card-lg sa-reveal" style="--d:.1s">
            <div class="sa-premium-card-header">
                <div class="sa-premium-card-header-left">
                    <div class="sa-premium-card-icon sa-ci-navy">
                        <i class="bi bi-bar-chart-fill"></i>
                    </div>
                    <div>
                        <h3 class="sa-premium-card-title">Revenue Trend</h3>
                        <div class="sa-premium-card-subtitle">Last 6 months</div>
                    </div>
                </div>
            </div>
            <div class="sa-premium-card-body sa-chart-body">
                <canvas id="revenueChart" height="220" class="sa-chart-canvas"></canvas>
            </div>
        </div>

        <div class="sa-premium-card sa-chart-card-sm sa-reveal" style="--d:.16s">
            <div class="sa-premium-card-header">
                <div class="sa-premium-card-header-left">
                    <div class="sa-premium-card-icon sa-ci-teal">
                        <i class="bi bi-pie-chart-fill"></i>
                    </div>
                    <div>
                        <h3 class="sa-premium-card-title">Status Distribution</h3>
                        <div class="sa-premium-card-subtitle">Hospital status breakdown</div>
                    </div>
                </div>
            </div>
            <div class="sa-premium-card-body sa-donut-wrap">
                <canvas id="statusChart" class="sa-donut-canvas"></canvas>
                <div class="sa-donut-legend">
                    <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#0d9488"></span>Active ({{ $activeCount }})</span>
                    <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#1a6fb5"></span>Trial ({{ $trialCount }})</span>
                    <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#5b9bd5"></span>Grace ({{ $graceCount }})</span>
                    <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#0f4f86"></span>Suspended ({{ $suspendedCount }})</span>
                    <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#94a3b8"></span>Inactive ({{ $inactiveCount }})</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts row 2 --}}
    <div class="sa-charts-row">
        <div class="sa-premium-card sa-chart-card-lg sa-reveal" style="--d:.12s">
            <div class="sa-premium-card-header">
                <div class="sa-premium-card-header-left">
                    <div class="sa-premium-card-icon sa-ci-blue">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div>
                        <h3 class="sa-premium-card-title">New Registrations</h3>
                        <div class="sa-premium-card-subtitle">Last 6 months</div>
                    </div>
                </div>
            </div>
            <div class="sa-premium-card-body sa-chart-body">
                <canvas id="regChart" height="220" class="sa-chart-canvas"></canvas>
            </div>
        </div>

        <div class="sa-premium-card sa-chart-card-sm sa-reveal" style="--d:.18s">
            <div class="sa-premium-card-header">
                <div class="sa-premium-card-header-left">
                    <div class="sa-premium-card-icon sa-ci-navy">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <div>
                        <h3 class="sa-premium-card-title">Subscription Cycles</h3>
                        <div class="sa-premium-card-subtitle">Active plan distribution</div>
                    </div>
                </div>
            </div>
            <div class="sa-premium-card-body sa-donut-wrap">
                <canvas id="cycleChart" class="sa-donut-canvas"></canvas>
                <div class="sa-donut-legend">
                    <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#0f4f86"></span>Monthly ({{ $cycleMonthly }})</span>
                    <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#1a6fb5"></span>Quarterly ({{ $cycleQuarterly }})</span>
                    <span class="sa-legend-item"><span class="sa-legend-dot" style="background:#0d9488"></span>Yearly ({{ $cycleYearly }})</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent hospitals --}}
    <div class="sa-table-card sa-reveal" style="--d:.14s">
        <div class="sa-table-card-head">
            <div class="sa-table-card-title">
                <span class="sa-table-card-ico"><i class="bi bi-clock-history"></i></span>
                <div>
                    <h3>Recently Registered Hospitals</h3>
                    <p>Latest platform sign-ups</p>
                </div>
            </div>
            <a href="{{ route('superadmin.hospitals.index') }}" class="sa-btn-ghost sa-btn-sm">
                <i class="bi bi-list-ul"></i> View All
            </a>
        </div>

        @if($recentHospitals->isEmpty())
            <div class="sa-table-empty">
                <x-empty-state
                    icon="bi bi-hospital-fill"
                    title="No Hospitals Registered"
                    description="Hospitals will appear here once they register on the platform." />
            </div>
        @else
            <div class="sa-table-wrap">
                <table class="sa-table js-datatable">
                    <thead>
                        <tr>
                            <th>Hospital</th>
                            <th>Admin</th>
                            <th>City</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentHospitals as $hospital)
                            <tr>
                                <td>
                                    <span class="sa-table-name">{{ $hospital->name }}</span>
                                    <span class="sa-table-sub">{{ $hospital->slug }}</span>
                                </td>
                                <td>{{ $hospital->admin_name ?? '—' }}</td>
                                <td>{{ $hospital->city ?? '—' }}</td>
                                <td>
                                    <span class="hms-badge hms-badge-{{ strtolower($hospital->displayStatus()) }}">
                                        {{ ucfirst($hospital->displayStatus()) }}
                                    </span>
                                </td>
                                <td class="sa-table-date">
                                    {{ $hospital->created_at->format('d M Y') }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('superadmin.hospitals.show', $hospital) }}"
                                       class="sa-icon-btn"
                                       title="View details"
                                       aria-label="View details">
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

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"
        integrity="sha256-oVuQa3dJgpxXqV5PKdWxd+3bpRWijMzLzKGVq8QWZVA="
        crossorigin="anonymous"></script>
<script>
(function () {
    'use strict';

    var theme = {
        navy: '#0f4f86',
        blue: '#1a6fb5',
        blueSoft: '#5b9bd5',
        teal: '#0d9488',
        tealSoft: 'rgba(13, 148, 136, 0.22)',
        slate: '#94a3b8',
        grid: 'rgba(15, 79, 134, 0.06)',
        tick: '#8aa0b5',
        tipBg: '#0a1628'
    };

    var months = @json($revenueMonths);
    var amounts = @json($revenueAmounts);
    var regMonths = @json($regMonths);
    var regCounts = @json($regCounts);
    var currency = @json(platform_currency_symbol());

    var easeOpts = {
        animation: {
            duration: 900,
            easing: 'easeOutQuart'
        }
    };

    var ctx1 = document.getElementById('revenueChart');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Revenue (' + currency + ')',
                    data: amounts,
                    backgroundColor: function (ctx) {
                        var chart = ctx.chart;
                        var {ctx: c, chartArea} = chart;
                        if (!chartArea) return 'rgba(15, 79, 134, 0.75)';
                        var g = c.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                        g.addColorStop(0, 'rgba(15, 79, 134, 0.55)');
                        g.addColorStop(1, 'rgba(26, 111, 181, 0.95)');
                        return g;
                    },
                    borderWidth: 0,
                    borderRadius: 8,
                    borderSkipped: false,
                    hoverBackgroundColor: theme.teal,
                    maxBarThickness: 42
                }]
            },
            options: Object.assign({
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: theme.tipBg,
                        titleFont: { weight: '700' },
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: function (ctx) {
                                return ' ' + currency + ctx.parsed.y.toLocaleString('en-IN');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: theme.grid, drawBorder: false },
                        ticks: {
                            callback: function (v) {
                                return currency + (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v);
                            },
                            font: { size: 11 },
                            color: theme.tick
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 }, color: theme.tick }
                    }
                }
            }, easeOpts)
        });
    }

    var ctx2 = document.getElementById('statusChart');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Trial', 'Grace', 'Suspended', 'Inactive'],
                datasets: [{
                    data: [{{ $activeCount }}, {{ $trialCount }}, {{ $graceCount }}, {{ $suspendedCount }}, {{ $inactiveCount }}],
                    backgroundColor: [theme.teal, theme.blue, theme.blueSoft, theme.navy, theme.slate],
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 8
                }]
            },
            options: Object.assign({
                cutout: '70%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: theme.tipBg,
                        padding: 12,
                        cornerRadius: 10
                    }
                }
            }, easeOpts)
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
                    borderColor: theme.navy,
                    backgroundColor: function (context) {
                        var chart = context.chart;
                        var {ctx: c, chartArea} = chart;
                        if (!chartArea) return 'rgba(15, 79, 134, 0.12)';
                        var g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                        g.addColorStop(0, 'rgba(26, 111, 181, 0.28)');
                        g.addColorStop(0.5, 'rgba(13, 148, 136, 0.12)');
                        g.addColorStop(1, 'rgba(15, 79, 134, 0.02)');
                        return g;
                    },
                    borderWidth: 3,
                    pointBackgroundColor: theme.teal,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: Object.assign({
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: theme.tipBg,
                        padding: 12,
                        cornerRadius: 10
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { size: 11 }, color: theme.tick },
                        grid: { color: theme.grid, drawBorder: false }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 }, color: theme.tick }
                    }
                }
            }, easeOpts)
        });
    }

    var ctx4 = document.getElementById('cycleChart');
    if (ctx4) {
        new Chart(ctx4, {
            type: 'doughnut',
            data: {
                labels: ['Monthly', 'Quarterly', 'Yearly'],
                datasets: [{
                    data: [{{ $cycleMonthly }}, {{ $cycleQuarterly }}, {{ $cycleYearly }}],
                    backgroundColor: [theme.navy, theme.blue, theme.teal],
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 8
                }]
            },
            options: Object.assign({
                cutout: '62%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: theme.tipBg,
                        padding: 12,
                        cornerRadius: 10
                    }
                }
            }, easeOpts)
        });
    }
}());
</script>
@endpush

@push('modals')
    @include('superadmin.tenants._create_modal')
@endpush
