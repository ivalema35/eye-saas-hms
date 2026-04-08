<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SuperAdmin') — Eye HMS SaaS</title>

    {{-- Design System CSS --}}
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}">
    <link rel="stylesheet" href="{{ asset('css/superadmin.css') }}">

    {{-- Bootstrap 5.3 CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous" />

    {{-- Select2 CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />

    {{-- Flatpickr CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
          integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uf0aJSUYjaQfXArGPgql7EiSBEeP4MNFxZJR2A=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    @stack('styles')
</head>
<body class="sa-body">

    {{-- ================================================
         Top Navigation Bar
    ================================================ --}}
    <nav class="sa-navbar">
        <div style="display:flex;align-items:center;gap:.5rem">
            <button class="hms-sidebar-toggle" id="hmsSidebarToggle" type="button" aria-label="Toggle sidebar">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a href="{{ route('superadmin.dashboard') }}" class="sa-navbar-brand" style="text-decoration:none">
                <i class="fa-solid fa-eye"></i>
                <span>Eye HMS</span>
                <small class="sa-badge-platform">Platform Admin</small>
            </a>
        </div>

        <div class="sa-navbar-right">
            <span class="sa-navbar-user">
                <i class="fa-solid fa-user-shield"></i>
                {{ auth('superadmin')->user()?->name ?? 'SuperAdmin' }}
            </span>
            <form method="POST" action="{{ route('superadmin.logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="sa-btn-logout">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </button>
            </form>
        </div>
    </nav>

    <div class="sa-layout">

        {{-- Mobile backdrop --}}
        <div class="hms-sidebar-backdrop" id="hmsSidebarBackdrop"></div>

        {{-- ============================================
             Sidebar
        ============================================ --}}
        <aside class="sa-sidebar" id="hmsSidebar">
            <nav class="sa-sidenav">
                <ul>
                    {{-- OVERVIEW --}}
                    <li class="sa-nav-group-toggle" data-target="sa-nav-overview">
                        <span class="sa-nav-section-label" style="padding:0;margin:0">Overview</span>
                        <i class="fa-solid fa-chevron-down hms-nav-chevron"></i>
                    </li>
                    <div class="sa-nav-group-items" id="sa-nav-overview">
                        <li>
                            <a href="{{ route('superadmin.dashboard') }}"
                               class="{{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                                <i class="fa-solid fa-gauge-high"></i> Dashboard
                            </a>
                        </li>
                    </div>

                    {{-- MANAGEMENT --}}
                    <li class="sa-nav-group-toggle" data-target="sa-nav-management">
                        <span class="sa-nav-section-label" style="padding:0;margin:0">Management</span>
                        <i class="fa-solid fa-chevron-down hms-nav-chevron"></i>
                    </li>
                    <div class="sa-nav-group-items" id="sa-nav-management">
                        <li>
                            <a href="{{ route('superadmin.hospitals.index') }}"
                               class="{{ request()->routeIs('superadmin.hospitals.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-hospital"></i> Hospitals
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('superadmin.subscriptions.index') }}"
                               class="{{ request()->routeIs('superadmin.subscriptions.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-credit-card"></i> Subscriptions
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('superadmin.payments.index') }}"
                               class="{{ request()->routeIs('superadmin.payments.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-money-bill-wave"></i> Payments
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('superadmin.audit-logs.index') }}"
                               class="{{ request()->routeIs('superadmin.audit-logs.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-clipboard-list"></i> Audit Logs
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('superadmin.notifications.index') }}"
                               class="{{ request()->routeIs('superadmin.notifications.*') ? 'active' : '' }}">
                                <i class="fa-solid fa-bell"></i> Notifications
                            </a>
                        </li>
                    </div>

                    {{-- SYSTEM --}}
                    <li class="sa-nav-group-toggle" data-target="sa-nav-system">
                        <span class="sa-nav-section-label" style="padding:0;margin:0">System</span>
                        <i class="fa-solid fa-chevron-down hms-nav-chevron"></i>
                    </li>
                    <div class="sa-nav-group-items" id="sa-nav-system">
                        <li>
                            <a href="{{ route('superadmin.settings') }}"
                               class="{{ request()->routeIs('superadmin.settings') ? 'active' : '' }}">
                                <i class="fa-solid fa-sliders"></i> Settings
                            </a>
                        </li>
                    </div>
                </ul>
            </nav>
        </aside>

        {{-- ============================================
             Main Content Area
        ============================================ --}}
        <main class="sa-main">

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="hms-alert hms-alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="hms-alert hms-alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    {{ session('error') }}
                </div>
            @endif

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
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmTE50ZQ1CzAMRQGTMd4U+Nc2bMr"
            crossorigin="anonymous"></script>

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
        var sidebar  = document.getElementById('hmsSidebar');
        var backdrop = document.getElementById('hmsSidebarBackdrop');
        var toggle   = document.getElementById('hmsSidebarToggle');

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

        document.querySelectorAll('.sa-nav-group-toggle').forEach(function (el) {
            var target = document.getElementById(el.getAttribute('data-target'));
            if (!target) return;

            if (!target.querySelector('a.active')) {
                target.classList.add('collapsed');
                el.classList.add('collapsed');
            }

            el.addEventListener('click', function () {
                target.classList.toggle('collapsed');
                el.classList.toggle('collapsed');
            });
        });
    });
    </script>

    @stack('scripts')
</body>
</html>
