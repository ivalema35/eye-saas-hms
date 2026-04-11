<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hospital') — {{ config('app.name') }}</title>

    {{-- Design System CSS --}}
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}">
    <link rel="stylesheet" href="{{ asset('css/hospital.css') }}">

    {{-- Bootstrap 5.3 CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous" />

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Premium Theme Overrides --}}
    <link rel="stylesheet" href="{{ asset('css/premium-theme.css') }}">

        {{-- Font Awesome (legacy pages; can be removed after full icon migration) --}}
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
            integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uf0aJSUYjaQfXArGPgql7EiSBEeP4MNFxZJR2A=="
            crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Select2 CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />

    {{-- Flatpickr CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />

    @stack('styles')
</head>
<body class="hms-body">

    {{-- ================================================
         Grace Period Warning Banner
    ================================================ --}}
    @if(session('show_grace_warning'))
        <div class="hms-grace-banner">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Warning:</strong>
            Aapka subscription expire ho gaya hai. Please renew karein warna access band ho jayega.
            <a href="{{ route('hospital.settings.index', ['slug' => request()->route('slug')]) }}"
               class="hms-grace-link">Renew Now</a>
        </div>
    @endif

    {{-- ================================================
         Top Navigation Bar
    ================================================ --}}
    <nav class="hms-navbar">
        @php
            $currentUser = auth('hospital_user')->user();
            $slug = request()->route('slug');

            $waitQueueCount = 0;
            try {
                $waitQueueCount = \App\Models\Hospital\Patient::query()
                    ->whereDate('appointment_date', today())
                    ->whereNull('primary_done_at')
                    ->count();
            } catch (\Throwable $e) {
                $waitQueueCount = 0;
            }

            $segments = collect(request()->segments());
            if ($slug && $segments->isNotEmpty() && $segments->first() === $slug) {
                $segments = $segments->slice(1)->values();
            }
        @endphp

        {{-- Mobile Sidebar Toggle --}}
        <button class="hms-sidebar-toggle" id="hmsSidebarToggle" aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
        </button>

        <nav class="hms-breadcrumb-wrap" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}" class="hms-breadcrumb-link">
                        <i class="bi bi-house-door"></i> Home
                    </a>
                </li>
                @forelse($segments as $i => $segment)
                    @php
                        $isLast = $i === $segments->count() - 1;
                        $segmentLabel = str($segment)->replace(['-', '_'], ' ')->title();
                    @endphp
                    <li class="breadcrumb-item {{ $isLast ? 'active' : '' }}" {{ $isLast ? 'aria-current=page' : '' }}>
                        {{ $segmentLabel }}
                    </li>
                @empty
                    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                @endforelse
            </ol>
        </nav>

        <div class="hms-navbar-right">
            <div class="wait-queue-badge">
                <i class="bi bi-hourglass-split"></i>
                <span>Wait Queue (OPD):</span>
                <strong>{{ $waitQueueCount }}</strong>
            </div>

            @if($currentUser)
                <div class="dropdown user-dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                       href="#"
                       id="navbarUserDropdown"
                       role="button"
                       data-bs-toggle="dropdown"
                       aria-expanded="false">
                        <span class="avatar-circle">
                            <i class="bi bi-person-fill"></i>
                        </span>
                        <span class="user-info d-none d-md-flex flex-column text-start">
                            <span class="user-name">{{ $currentUser->name }}</span>
                            <small class="user-role">{{ $currentUser->role?->name ?? 'Hospital Staff' }}</small>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                        <li><h6 class="dropdown-header">Switch Workspace</h6></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('hospital.patients.index', ['slug' => $slug]) }}">
                                <i class="bi bi-person-badge me-2"></i> Doctor Workspace
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">
                                <i class="bi bi-clipboard2-pulse me-2"></i> Reception Workspace
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('hospital.settings.index', ['slug' => $slug]) }}">
                                <i class="bi bi-gear me-2"></i> Settings
                            </a>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('hospital.logout', ['slug' => $slug]) }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endif
        </div>
    </nav>

    <div class="hms-layout">

        {{-- Mobile backdrop --}}
        <div class="hms-sidebar-backdrop" id="hmsSidebarBackdrop"></div>

        {{-- ============================================
             Sidebar Navigation
        ============================================ --}}
        <aside class="hms-sidebar" id="hmsSidebar">
            <div class="premium-sidebar-brand">
                <img src="{{ asset('images/aeh-logo-white.svg') }}"
                     alt="AEH Logo"
                     class="sidebar-logo"
                     loading="lazy"
                     decoding="async">
            </div>
            <div class="hms-sidenav-wrap">
            <nav class="hms-sidenav">

                {{-- Dashboard — always visible to authenticated hospital users --}}
                @php $permSvc = app(\App\Services\Auth\RolePermissionService::class); @endphp
                <a href="{{ route('hospital.dashboard', ['slug' => request()->route('slug')]) }}"
                   class="hms-nav-item {{ request()->routeIs('hospital.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>

                {{-- ── OPD ─────────────────────────────────────────
                     Visible if user has any patient permission.
                ── --}}
                @php
                    $showOpd = $permSvc->can('opd.patient.view')
                        || $permSvc->can('opd.patient.register')
                        || $permSvc->can('opd.patient.register_phone');
                @endphp

                @if($showOpd)
                <div class="hms-nav-divider"></div>
                <div class="hms-nav-group-toggle" data-target="nav-opd">
                    <span class="hms-nav-section-label" style="padding:0;margin:0">OPD</span>
                    <i class="bi bi-chevron-down hms-nav-chevron"></i>
                </div>
                <div class="hms-nav-group-items" id="nav-opd">
                    @haspermission('opd.patient.view')
                        <a href="{{ route('hospital.patients.index', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ request()->routeIs('hospital.patients.*') ? 'active' : '' }}">
                            <i class="bi bi-people-fill"></i>
                            <span>Patients</span>
                        </a>
                        <a href="{{ route('hospital.patients.history', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ request()->routeIs('hospital.patients.history') ? 'active' : '' }}">
                            <i class="bi bi-clock-history"></i>
                            <span>Patient History</span>
                        </a>
                    @endhaspermission
                </div>
                @endif

                {{-- ── CLINICAL ────────────────────────────────────
                     Visible if user can view/write any exam.
                ── --}}
                @php
                    $showClin = $permSvc->can('opd.exam.primary')
                             || $permSvc->can('opd.exam.secondary');
                @endphp

                @if($showClin)
                <div class="hms-nav-divider"></div>
                <div class="hms-nav-group-toggle" data-target="nav-clinical">
                    <span class="hms-nav-section-label" style="padding:0;margin:0">Clinical</span>
                    <i class="bi bi-chevron-down hms-nav-chevron"></i>
                </div>
                <div class="hms-nav-group-items" id="nav-clinical">
                    @haspermission('opd.exam.primary')
                        <a href="{{ route('hospital.patients.index', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ request()->routeIs('hospital.patients.*') ? 'active' : '' }}">
                            <i class="bi bi-eye-fill"></i>
                            <span>Primary Exam</span>
                        </a>
                    @endhaspermission
                    @haspermission('opd.exam.secondary')
                        <a href="{{ route('hospital.patients.index', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ request()->routeIs('hospital.patients.*') ? 'active' : '' }}">
                            <i class="bi bi-activity"></i>
                            <span>Secondary Exam</span>
                        </a>
                    @endhaspermission
                </div>
                @endif

                {{-- ── FOC ─────────────────────────────────────────
                     Visible if user can view or approve FOC.
                ── --}}
                @php
                    $showFoc = $permSvc->can('opd.foc.create') || $permSvc->can('opd.foc.accept');
                @endphp

                @if($showFoc)
                <div class="hms-nav-divider"></div>
                <div class="hms-nav-group-toggle" data-target="nav-foc">
                    <span class="hms-nav-section-label" style="padding:0;margin:0">FOC</span>
                    <i class="bi bi-chevron-down hms-nav-chevron"></i>
                </div>
                <div class="hms-nav-group-items" id="nav-foc">
                    @haspermission('opd.foc.create')
                        <a href="{{ route('hospital.foc.index', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ request()->routeIs('hospital.foc.*') ? 'active' : '' }}">
                            <i class="bi bi-heart-fill"></i>
                            <span>FOC Requests</span>
                        </a>
                    @endhaspermission
                    @haspermission('opd.foc.accept')
                        <a href="{{ route('hospital.foc.index', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ request()->routeIs('hospital.foc.*') ? 'active' : '' }}">
                            <i class="bi bi-heart-pulse-fill"></i>
                            <span>Approve FOC</span>
                        </a>
                    @endhaspermission
                </div>
                @endif

                {{-- ── OT / SURGERY ────────────────────────────────
                     Visible if user can view OT bookings or surgery.
                ── --}}
                @php
                    $showOt = $permSvc->can('ot.patient.list')
                        || $permSvc->can('ot.payment.record')
                        || $permSvc->can('ot.ward.entry')
                        || $permSvc->can('ot.surgery.record')
                        || $permSvc->can('ot.lens.record')
                        || $permSvc->can('ot.billing.manage');
                @endphp

                @if($showOt)
                <div class="hms-nav-divider"></div>
                <div class="hms-nav-group-toggle" data-target="nav-ot">
                    <span class="hms-nav-section-label" style="padding:0;margin:0">OT / Surgery</span>
                    <i class="bi bi-chevron-down hms-nav-chevron"></i>
                </div>
                <div class="hms-nav-group-items" id="nav-ot">
                    @haspermission('ot.patient.list')
                        <a href="{{ route('hospital.ot.index', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ request()->routeIs('hospital.ot.dashboard') || request()->routeIs('hospital.ot.bookings.*') || request()->routeIs('hospital.ot.index') ? 'active' : '' }}">
                            <i class="bi bi-heart-pulse-fill"></i>
                            <span>OT Bookings</span>
                        </a>
                    @endhaspermission

                    @haspermission('ot.payment.record')
                        <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ request()->routeIs('hospital.ot.accountant.dashboard') || request()->routeIs('hospital.ot.payments.*') ? 'active' : '' }}">
                            <i class="bi bi-cash-coin"></i>
                            <span>OT Accountant / Billing</span>
                        </a>
                    @endhaspermission

                    @haspermission('ot.ward.entry')
                        <a href="{{ route('hospital.ot.ward.index', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ request()->routeIs('hospital.ot.ward.index') ? 'active' : '' }}">
                            <i class="bi bi-hospital"></i>
                            <span>Ward Management</span>
                        </a>
                    @endhaspermission

                    @haspermission('ot.surgery.record')
                        <a href="{{ route('hospital.ot.doctor.dashboard', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ request()->routeIs('hospital.ot.doctor.dashboard') || request()->routeIs('hospital.ot.surgery.*') ? 'active' : '' }}">
                            <i class="bi bi-heart-pulse"></i>
                            <span>OT Doctor Dashboard</span>
                        </a>
                    @endhaspermission

                    @haspermission('ot.lens.record')
                        <a href="{{ route('hospital.ot.assistant.dashboard', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ request()->routeIs('hospital.ot.assistant.*') ? 'active' : '' }}">
                            <i class="bi bi-eyeglasses"></i>
                            <span>OT Assistant Dashboard</span>
                        </a>
                    @endhaspermission

                    @haspermission('ot.billing.manage')
                        <a href="{{ route('hospital.ot.billing.index', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ request()->routeIs('hospital.ot.billing.index') || request()->routeIs('hospital.ot.invoice.*') || request()->routeIs('hospital.ot.discharge.*') || request()->routeIs('hospital.ot.summary-bill.*') || request()->routeIs('hospital.ot.certificate.*') || request()->routeIs('hospital.ot.medicine-slip.*') ? 'active' : '' }}">
                            <i class="bi bi-receipt-cutoff"></i>
                            <span>Discharge & Invoices</span>
                        </a>
                    @endhaspermission
                </div>
                @endif

                {{-- ── REPORTS ─────────────────────────────────────
                     Visible if user has any report permission.
                ── --}}
                @php
                    $showReports = $permSvc->can('reports.view') || $permSvc->can('reports.export');
                @endphp

                @if($showReports)
                <div class="hms-nav-divider"></div>
                <div class="hms-nav-group-toggle" data-target="nav-reports">
                    <span class="hms-nav-section-label" style="padding:0;margin:0">Reports</span>
                    <i class="bi bi-chevron-down hms-nav-chevron"></i>
                </div>
                <div class="hms-nav-group-items" id="nav-reports">
                    <a href="{{ route('hospital.reports.index', ['slug' => request()->route('slug')]) }}"
                       class="hms-nav-item {{ request()->routeIs('hospital.reports.*') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-line-fill"></i>
                        <span>Reports</span>
                    </a>
                </div>
                @endif

                {{-- ── MEDICINES ────────────────────────────────────
                     Visible if user has any medicine permission.
                ── --}}
                @php
                    $showMed = $permSvc->can('master.medicines');
                @endphp

                @if($showMed)
                <div class="hms-nav-divider"></div>
                <div class="hms-nav-group-toggle" data-target="nav-medicines">
                    <span class="hms-nav-section-label" style="padding:0;margin:0">Medicines</span>
                    <i class="bi bi-chevron-down hms-nav-chevron"></i>
                </div>
                <div class="hms-nav-group-items" id="nav-medicines">
                    @haspermission('master.medicines')
                        <a href="{{ route('hospital.medicines.index', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ request()->routeIs('hospital.medicines.*') ? 'active' : '' }}">
                            <i class="bi bi-capsule-pill"></i>
                            <span>Medicines</span>
                        </a>
                        <a href="{{ route('hospital.medicine-types.index', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ request()->routeIs('hospital.medicine-types.*') ? 'active' : '' }}">
                            <i class="bi bi-tags"></i>
                            <span>Medicine Types</span>
                        </a>
                        <a href="{{ route('hospital.medicine-dosages.index', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ request()->routeIs('hospital.medicine-dosages.*') ? 'active' : '' }}">
                            <i class="bi bi-eyedropper"></i>
                            <span>Dosages</span>
                        </a>
                    @endhaspermission
                </div>
                @endif

                {{-- ── CONFIG / MASTERS ────────────────────────────
                     Visible if user can manage masters, settings, roles, or users.
                ── --}}
                @php
                    $showConfig = $permSvc->can('master.case_types')
                               || $permSvc->can('settings.hospital')
                               || $permSvc->can('master.roles')
                               || $permSvc->can('master.doctors')
                               || $permSvc->can('master.receptions');
                @endphp

                @if($showConfig)
                <div class="hms-nav-divider"></div>
                <div class="hms-nav-group-toggle" data-target="nav-config">
                    <span class="hms-nav-section-label" style="padding:0;margin:0">Config</span>
                    <i class="bi bi-chevron-down hms-nav-chevron"></i>
                </div>
                <div class="hms-nav-group-items" id="nav-config">
                    @haspermission('master.case_types')
                        <a href="{{ route('hospital.masters.index', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ request()->routeIs('hospital.masters.*') ? 'active' : '' }}">
                            <i class="bi bi-database-fill-gear"></i>
                            <span>Masters</span>
                        </a>
                    @endhaspermission
                    @haspermission('settings.hospital')
                        <a href="{{ route('hospital.settings.index', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ request()->routeIs('hospital.settings.*') ? 'active' : '' }}">
                            <i class="bi bi-gear-fill"></i>
                            <span>Settings</span>
                        </a>
                    @endhaspermission
                    @haspermission('master.roles')
                        <a href="{{ route('hospital.roles.index', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ request()->routeIs('hospital.roles.*') ? 'active' : '' }}">
                            <i class="bi bi-shield-lock-fill"></i>
                            <span>Roles & Permissions</span>
                        </a>
                    @endhaspermission
                    @if($permSvc->can('master.doctors') || $permSvc->can('master.receptions'))
                        <a href="{{ route('hospital.users.index', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ request()->routeIs('hospital.users.*') ? 'active' : '' }}">
                            <i class="bi bi-person-gear"></i>
                            <span>Users</span>
                        </a>
                    @endif
                </div>
                @endif

            </nav>
            </div>{{-- /.hms-sidenav-wrap --}}

            <div class="hms-sidebar-footer">
                <form method="POST" action="{{ route('hospital.logout', ['slug' => request()->route('slug')]) }}">
                    @csrf
                    <button type="submit" class="hms-sidebar-logout">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- ============================================
             Main Content Area
        ============================================ --}}
        <main class="hms-main" id="hmsMain">

            {{-- Flash Messages --}}
            @if($errors->any())
                <div class="hms-alert hms-alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Page Header --}}
            @hasSection('page-header')
                <div class="hms-page-header">
                    <h1>@yield('page-header')</h1>
                    @hasSection('page-actions')
                        <div class="hms-page-actions">@yield('page-actions')</div>
                    @endif
                </div>
            @endif

            {{-- Main Content --}}
            @yield('content')

        </main>

    </div>{{-- /.hms-layout --}}

    {{-- Bootstrap 5.3 JS (local first, CDN fallback) --}}
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script>
    if (typeof window.bootstrap === 'undefined') {
        var fallbackScript = document.createElement('script');
        fallbackScript.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
        fallbackScript.integrity = 'sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmTE50ZQ1CzAMRQGTMd4U+Nc2bMr';
        fallbackScript.crossOrigin = 'anonymous';
        document.head.appendChild(fallbackScript);
    }
    </script>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    {{-- jQuery (Select2 dependency) --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>

    {{-- Select2 --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    {{-- Flatpickr --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Sidebar toggle & collapsible groups --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var sidebar  = document.getElementById('hmsSidebar');
        var backdrop = document.getElementById('hmsSidebarBackdrop');
        var toggle   = document.getElementById('hmsSidebarToggle');

        // Mobile sidebar open / close
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

        // Collapsible nav groups
        document.querySelectorAll('.hms-nav-group-toggle').forEach(function (el) {
            var target = document.getElementById(el.getAttribute('data-target'));
            if (!target) return;

            // Auto-collapse groups that don't contain the active link
            if (!target.querySelector('.hms-nav-item.active')) {
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

    {{-- Lucide Icons — free, open-source, MIT licensed --}}
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>lucide.createIcons();</script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Swal === 'undefined') {
            return;
        }

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: function (toast) {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        const flashMessages = [
            { icon: 'success', title: @json(session('success')) },
            { icon: 'error', title: @json(session('error')) },
            { icon: 'warning', title: @json(session('warning')) },
            { icon: 'info', title: @json(session('info')) }
        ].filter(function (message) {
            return Boolean(message.title);
        });

        flashMessages.forEach(function (message) {
            Toast.fire(message);
        });
    });
    </script>

    @stack('scripts')
</body>
</html>
