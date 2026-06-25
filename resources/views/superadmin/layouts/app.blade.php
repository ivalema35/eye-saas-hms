<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SuperAdmin') — Eye HMS SaaS</title>
    <link rel="icon" type="image/png" href="{{ platform_logo_url() }}">

    {{-- Design System CSS --}}
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}">
    <link rel="stylesheet" href="{{ asset('css/hospital.css') }}">
    <link rel="stylesheet" href="{{ asset('css/premium-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('css/superadmin.css') }}">

    {{-- Bootstrap 5.3 CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


    {{-- Select2 CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />

    {{-- Flatpickr CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />

    {{-- SweetAlert2 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    {{-- SuperAdmin shell: dark-navy sidebar + clean white navbar --}}
    <style>
        :root {
            --shell-secondary: #1B4F72;
            /* Dark sidebar palette */
            --sa-dark-900: #0F172A;
            --sa-dark-800: #1E293B;
            --sa-dark-muted: #94A3B8;
            --sa-dark-subtle: #64748B;
            --sa-dark-border: rgba(255, 255, 255, .08);
        }

        body.sa-body {
            background: #F8FAFC !important;
            color: #1B4F72;
            font-family: 'Inter', sans-serif;
        }

        .sa-main {
            background: #F8FAFC;
            font-family: 'Inter', sans-serif;
        }

        .sa-page-header h1 {
            font-size: 1.35rem;
            font-weight: 800;
            color: #1A202C;
            letter-spacing: -.02em;
        }

        .sa-main .hms-card,
        .sa-main [class*="-card"],
        .sa-main .hms-stat-card,
        .sa-main .sa-premium-card {
            box-shadow: none !important;
        }

        /* ── Stat card horizontal layout ─────────────────────── */
        .hms-stat-card {
            flex-direction: row !important;
            align-items: center !important;
        }

        .hms-stat-body {
            flex: 1;
            min-width: 0;
        }

        .hms-stat-icon i,
        .hms-stat-icon .bi {
            color: inherit !important;
            font-size: 1.375rem !important;
        }

        /* ══════════════════════════════════════════════════════
           TOP NAVBAR — solid white, clean shadow
        ══════════════════════════════════════════════════════ */
        .sa-navbar {
            background: #fff !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            border-bottom: 1px solid #E2E8F0 !important;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .05) !important;
        }

        /* ══════════════════════════════════════════════════════
           DARK NAVY SIDEBAR
        ══════════════════════════════════════════════════════ */
        .sa-sidebar {
            background: var(--sa-dark-900) !important;
            border-right: 1px solid var(--sa-dark-border) !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            display: flex;
            flex-direction: column;
        }

        @media (max-width: 768px) {
            .sa-sidebar {
                background: var(--sa-dark-900) !important;
            }
        }

        .hms-sidenav {
            display: flex;
            flex-direction: column;
            padding: 1rem .75rem;
            gap: .25rem;
            flex: 1;
        }

        /* Section label text inside collapsible group toggles */
        .hms-nav-section-label {
            color: var(--sa-dark-subtle) !important;
            font-weight: 700;
            font-size: .70rem;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .hms-nav-divider {
            height: 1px;
            background: var(--sa-dark-border) !important;
            border: none;
            margin: .35rem .5rem;
            opacity: 1;
        }

        /* Collapsible group toggle rows (Management / System) */
        .hms-nav-group-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, .04);
            border: none;
            border-radius: 8px;
            margin: .2rem 0;
            padding: .65rem .9rem;
            cursor: pointer;
            transition: transform 160ms ease, background 160ms ease;
        }

        .hms-nav-group-toggle:hover {
            background: rgba(255, 255, 255, .08);
            transform: translateX(2px);
        }

        .hms-nav-group-toggle:hover .hms-nav-section-label,
        .hms-nav-group-toggle:hover .hms-nav-chevron {
            color: #fff !important;
        }

        .hms-nav-group-toggle .hms-nav-section-label {
            font-size: 1.05rem !important;
            font-weight: 700 !important;
            letter-spacing: .05em !important;
            color: var(--sa-dark-muted) !important;
        }

        .hms-nav-group-toggle .hms-nav-chevron {
            color: var(--sa-dark-subtle) !important;
            font-size: .75rem;
            transition: transform 200ms ease;
        }

        .hms-nav-group-toggle.collapsed .hms-nav-chevron {
            transform: rotate(-90deg);
        }

        .hms-nav-group-items {
            display: flex;
            flex-direction: column;
            gap: .15rem;
            overflow: hidden;
            transition: max-height 220ms ease;
        }

        .hms-nav-group-items.collapsed {
            max-height: 0 !important;
        }

        .hms-nav-group-items .hms-nav-item {
            font-size: .92rem;
        }

        .hms-nav-group-items .hms-nav-item i {
            font-size: .95rem;
        }

        /* ── Nav items — dark theme ──────────────────────────── */
        .hms-nav-item {
            display: flex;
            align-items: center;
            gap: .55rem;
            color: var(--sa-dark-muted) !important;
            font-size: .9rem;
            font-weight: 600;
            border-radius: 10px;
            padding: .70rem .95rem;
            background: transparent;
            border: none;
            text-decoration: none !important;
            position: relative;
            transition: transform 160ms ease, background 160ms ease, color 160ms ease;
        }

        .hms-nav-item i {
            color: var(--sa-dark-subtle) !important;
            font-size: 1.05rem;
            transition: color 160ms ease;
        }

        .hms-nav-item:hover {
            background: var(--sa-dark-800) !important;
            color: #fff !important;
            transform: translateX(2px);
            text-decoration: none !important;
        }

        .hms-nav-item:hover i {
            color: #fff !important;
        }

        /* Active: slate-800 bg + white text + blue left accent bar */
        .hms-nav-item.active {
            background: var(--sa-dark-800) !important;
            color: #fff !important;
            font-weight: 700;
        }

        .hms-nav-item.active i {
            color: #fff !important;
        }

        .hms-nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            min-height: 1.125rem;
            border-radius: 0 3px 3px 0;
            background: var(--shell-secondary);
        }

        @media (prefers-reduced-motion: reduce) {

            .hms-nav-item,
            .hms-nav-group-toggle {
                transition: none !important;
            }
        }

        /* ── Sidebar footer / logout ─────────────────────────── */
        .sa-sidebar-footer {
            padding: .75rem;
            border-top: 1px solid var(--sa-dark-border);
            margin-top: auto;
        }

        .sa-sidebar-logout {
            display: flex;
            align-items: center;
            gap: .55rem;
            width: 100%;
            padding: .70rem .95rem;
            border-radius: 10px;
            border: none;
            background: rgba(192, 57, 43, .15);
            color: #FCA5A5;
            font-size: .875rem;
            font-weight: 600;
            cursor: pointer;
            text-align: left;
            transition: background 160ms ease, transform 160ms ease;
        }

        .sa-sidebar-logout i {
            color: #FCA5A5;
            font-size: 1rem;
        }

        .sa-sidebar-logout:hover {
            background: #C0392B;
            color: #fff;
            transform: translateX(2px);
        }

        .sa-sidebar-logout:hover i {
            color: #fff;
        }
    </style>

    @stack('styles')
</head>

<body class="sa-body">

    {{-- ================================================
    Top Navigation Bar
    ================================================ --}}
    <nav class="sa-navbar">
        <div style="display:flex;align-items:center;gap:.5rem">
            <button class="hms-sidebar-toggle" id="hmsSidebarToggle" type="button" aria-label="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>
            <a href="{{ route('superadmin.dashboard') }}" class="sa-navbar-brand" style="text-decoration:none">
                <img src="{{ platform_logo_url() }}" alt="Eye HMS" class="sa-navbar-logo">
                <span>Eye HMS</span>
                <small class="sa-badge-platform">Platform Admin</small>
            </a>
        </div>

        <div class="sa-navbar-right">
            {{-- User Dropdown --}}
            <div class="sa-user-dropdown dropdown">
                <button class="sa-user-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <span class="sa-user-avatar">
                        <i class="bi bi-shield-fill-check"></i>
                    </span>
                    <span class="sa-user-name d-none d-md-inline">
                        {{ auth('superadmin')->user()?->name ?? 'SuperAdmin' }}
                    </span>
                    <i class="bi bi-chevron-down sa-user-chevron"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end sa-user-dropdown-menu">
                    <li class="sa-dropdown-header">
                        <div class="sa-dropdown-user-info">
                            <span class="sa-dropdown-avatar"><i class="bi bi-shield-fill-check"></i></span>
                            <div>
                                <div class="sa-dropdown-name">{{ auth('superadmin')->user()?->name ?? 'SuperAdmin' }}
                                </div>
                                <div class="sa-dropdown-email">{{ auth('superadmin')->user()?->email ?? '' }}</div>
                            </div>
                        </div>
                    </li>
                    <li>
                        <hr class="dropdown-divider my-1">
                    </li>
                    <li>
                        <a class="dropdown-item sa-dropdown-item" href="{{ route('superadmin.profile') }}">
                            <i class="bi bi-person-circle"></i> My Profile
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item sa-dropdown-item" href="{{ route('superadmin.settings') }}">
                            <i class="bi bi-sliders"></i> Platform Settings
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider my-1">
                    </li>
                    <li>
                        <form method="POST" action="{{ route('superadmin.logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item sa-dropdown-item sa-dropdown-item-danger">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="sa-layout">

        {{-- Mobile backdrop --}}
        <div class="hms-sidebar-backdrop" id="hmsSidebarBackdrop"></div>

        {{-- ============================================
        Sidebar
        ============================================ --}}
        <aside class="sa-sidebar" id="hmsSidebar">
            <nav class="hms-sidenav">

                {{-- Dashboard (always visible) --}}
                <a href="{{ route('superadmin.dashboard') }}"
                    class="hms-nav-item {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>

                {{-- MANAGEMENT --}}
                <div class="hms-nav-divider"></div>
                <div class="hms-nav-group-toggle {{ request()->routeIs('superadmin.hospitals.*', 'superadmin.plans.*', 'superadmin.subscriptions.*', 'superadmin.payments.*', 'superadmin.audit-logs.*', 'superadmin.notifications.*') ? '' : 'collapsed' }}"
                    data-target="sa-nav-management">
                    <span class="hms-nav-section-label" style="padding:0;margin:0">Management</span>
                    <i class="bi bi-chevron-down hms-nav-chevron"></i>
                </div>
                <div class="hms-nav-group-items {{ request()->routeIs('superadmin.hospitals.*', 'superadmin.plans.*', 'superadmin.subscriptions.*', 'superadmin.payments.*', 'superadmin.audit-logs.*', 'superadmin.notifications.*') ? '' : 'collapsed' }}"
                    id="sa-nav-management">
                    <a href="{{ route('superadmin.hospitals.index') }}"
                        class="hms-nav-item {{ request()->routeIs('superadmin.hospitals.*') ? 'active' : '' }}">
                        <i class="bi bi-hospital-fill"></i>
                        <span>Hospitals</span>
                    </a>
                    <a href="{{ route('superadmin.plans.index') }}"
                        class="hms-nav-item {{ request()->routeIs('superadmin.plans.*') ? 'active' : '' }}">
                        <i class="bi bi-layers-fill"></i>
                        <span>Plans</span>
                    </a>
                    <a href="{{ route('superadmin.subscriptions.index') }}"
                        class="hms-nav-item {{ request()->routeIs('superadmin.subscriptions.*') ? 'active' : '' }}">
                        <i class="bi bi-credit-card-2-front-fill"></i>
                        <span>Subscriptions</span>
                    </a>
                    <a href="{{ route('superadmin.payments.index') }}"
                        class="hms-nav-item {{ request()->routeIs('superadmin.payments.*') ? 'active' : '' }}">
                        <i class="bi bi-cash-coin"></i>
                        <span>Payments</span>
                    </a>
                    <a href="{{ route('superadmin.audit-logs.index') }}"
                        class="hms-nav-item {{ request()->routeIs('superadmin.audit-logs.*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard2-check-fill"></i>
                        <span>Audit Logs</span>
                    </a>
                    <a href="{{ route('superadmin.notifications.index') }}"
                        class="hms-nav-item {{ request()->routeIs('superadmin.notifications.*') ? 'active' : '' }}">
                        <i class="bi bi-bell-fill"></i>
                        <span>Notifications</span>
                    </a>
                </div>

                {{-- MASTERS --}}
                <div class="hms-nav-divider"></div>
                <div class="hms-nav-group-toggle {{ request()->routeIs('superadmin.locations.*', 'superadmin.timezones.*') ? '' : 'collapsed' }}"
                    data-target="sa-nav-masters">
                    <span class="hms-nav-section-label" style="padding:0;margin:0">Masters</span>
                    <i class="bi bi-chevron-down hms-nav-chevron"></i>
                </div>
                <div class="hms-nav-group-items {{ request()->routeIs('superadmin.locations.*', 'superadmin.timezones.*') ? '' : 'collapsed' }}"
                    id="sa-nav-masters">
                    <a href="{{ route('superadmin.locations.index') }}"
                        class="hms-nav-item {{ request()->routeIs('superadmin.locations.*') ? 'active' : '' }}">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>Location Master</span>
                    </a>
                    <a href="{{ route('superadmin.timezones.index') }}"
                        class="hms-nav-item {{ request()->routeIs('superadmin.timezones.*') ? 'active' : '' }}">
                        <i class="bi bi-clock-fill"></i>
                        <span>Timezone Master</span>
                    </a>
                </div>

                {{-- SYSTEM --}}
                <div class="hms-nav-divider"></div>
                <div class="hms-nav-group-toggle {{ request()->routeIs('superadmin.profile', 'superadmin.settings') ? '' : 'collapsed' }}"
                    data-target="sa-nav-system">
                    <span class="hms-nav-section-label" style="padding:0;margin:0">System</span>
                    <i class="bi bi-chevron-down hms-nav-chevron"></i>
                </div>
                <div class="hms-nav-group-items {{ request()->routeIs('superadmin.profile', 'superadmin.settings') ? '' : 'collapsed' }}"
                    id="sa-nav-system">
                    <a href="{{ route('superadmin.profile') }}"
                        class="hms-nav-item {{ request()->routeIs('superadmin.profile') ? 'active' : '' }}">
                        <i class="bi bi-person-circle"></i>
                        <span>My Profile</span>
                    </a>
                    <a href="{{ route('superadmin.settings') }}"
                        class="hms-nav-item {{ request()->routeIs('superadmin.settings') ? 'active' : '' }}">
                        <i class="bi bi-sliders"></i>
                        <span>Settings</span>
                    </a>
                </div>

            </nav>

            {{-- Sidebar Footer — Logout --}}
            <div class="sa-sidebar-footer">
                <form method="POST" action="{{ route('superadmin.logout') }}">
                    @csrf
                    <button type="submit" class="sa-sidebar-logout">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>

        </aside>

        {{-- ============================================
        Main Content Area
        ============================================ --}}
        <main class="sa-main">

            {{-- Flash messages handled via SweetAlert2 toasts (see bottom JS) --}}

            {{-- Page Header --}}
            @hasSection('page-header')
                <div class="sa-page-header">
                    <h1>@yield('page-header')</h1>
                    @hasSection('page-actions')
                        <div class="sa-page-actions">@yield('page-actions')</div>
                    @endif
                </div>
            @endif

            {{-- Main Content --}}
            @yield('content')

        </main>

    </div>{{-- /.sa-layout --}}

    {{-- Bootstrap 5.3 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    {{-- jQuery (Select2 dependency) --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>

    {{-- Select2 --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    {{-- Flatpickr --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    {{-- Sidebar toggle & collapsible groups --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var sidebar = document.getElementById('hmsSidebar');
            var backdrop = document.getElementById('hmsSidebarBackdrop');
            var toggle = document.getElementById('hmsSidebarToggle');

            if (toggle) {
                toggle.addEventListener('click', function () {
                    sidebar.classList.toggle('open');
                    backdrop.classList.toggle('show');
                });
            }
            if (backdrop) {
                backdrop.addEventListener('click', function () {
                    sidebar.classList.remove('open');
                    backdrop.classList.remove('show');
                });
            }

            /* Set initial max-height on open groups so CSS transition works */
            document.querySelectorAll('.hms-nav-group-items').forEach(function (items) {
                if (!items.classList.contains('collapsed')) {
                    items.style.maxHeight = items.scrollHeight + 'px';
                } else {
                    items.style.maxHeight = '0';
                }
            });

            document.querySelectorAll('.hms-nav-group-toggle').forEach(function (el) {
                var targetId = el.getAttribute('data-target');
                var target = document.getElementById(targetId);
                if (!target) return;

                el.addEventListener('click', function () {
                    var isCollapsed = target.classList.contains('collapsed');
                    if (isCollapsed) {
                        target.classList.remove('collapsed');
                        target.style.maxHeight = target.scrollHeight + 'px';
                        el.classList.remove('collapsed');
                    } else {
                        target.style.maxHeight = '0';
                        target.classList.add('collapsed');
                        el.classList.add('collapsed');
                    }
                });
            });
        });
    </script>

    {{-- SweetAlert2 Flash Toast --}}
    @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'success',
                    title: @json(session('success')), showConfirmButton: false, timer: 3500,
                    timerProgressBar: true, customClass: { popup: 'sa-swal-toast' }
                });
            });
        </script>
    @endif
    @if(session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'error',
                    title: @json(session('error')), showConfirmButton: false, timer: 4500,
                    timerProgressBar: true, customClass: { popup: 'sa-swal-toast' }
                });
            });
        </script>
    @endif
    @if(session('warning'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'warning',
                    title: @json(session('warning')), showConfirmButton: false, timer: 4000,
                    timerProgressBar: true, customClass: { popup: 'sa-swal-toast' }
                });
            });
        </script>
    @endif

    @stack('scripts')
    @stack('modals')
</body>

</html>