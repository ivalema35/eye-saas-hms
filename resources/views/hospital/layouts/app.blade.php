<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hospital') — {{ hospital_name() }}</title>
    <link rel="icon" type="image/png" href="{{ platform_logo_url() }}">

    {{-- Design System CSS --}}
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}">
    <link rel="stylesheet" href="{{ asset('css/hospital.css') }}">

    {{-- Bootstrap 5.3 CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />

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

    {{-- Hospital Shell (Navbar + Sidebar) Theme Overrides --}}
    <style>
        /*
          Hospital shell redesign
          - Primary: #ebf5fbeb
          - Secondary: #1B4F72
          - Design-only: no logic/functionality changes
        */

        :root {
            --shell-primary: #ebf5fbeb;
            --shell-secondary: #1B4F72;
            --shell-s2-06: rgba(27, 79, 114, 0.06);
            --shell-s2-08: rgba(27, 79, 114, 0.08);
            --shell-s2-10: rgba(27, 79, 114, 0.10);
            --shell-s2-12: rgba(27, 79, 114, 0.12);
            --shell-s2-16: rgba(27, 79, 114, 0.16);
            --shell-s2-22: rgba(27, 79, 114, 0.22);
            --shell-white-70: rgba(255, 255, 255, 0.70);
            --shell-white-78: rgba(255, 255, 255, 0.78);
            --shell-white-86: rgba(255, 255, 255, 0.86);
        }

        body.hms-body {
            background: #ffffff !important;
            color: var(--shell-secondary);
        }

        /* Keep main content area white too */
        .hms-main {
            background: #ffffff;
        }

        @if(auth('hospital_user')->user()?->role?->slug === 'doctor')
            :root {
                --hms-navbar-h: 120px;
            }

            .hms-sidebar {
                display: none !important;
            }

            .hms-main {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 20px !important;
            }

            #hmsSidebarToggle {
                display: none !important;
            }

        @endif

        /* ── Navbar ─────────────────────────────────────────────────── */
        .hms-navbar {
            background: var(--shell-white-78) !important;
            color: var(--shell-secondary) !important;
            border-bottom: 1px solid var(--shell-s2-12) !important;
            box-shadow: none !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .hms-navbar * {
            color: inherit;
        }

        .hms-sidebar-toggle {
            color: var(--shell-secondary) !important;
            background: var(--shell-white-70);
            border: 1px solid var(--shell-s2-12);
            border-radius: 14px;
            padding: .35rem .6rem;
            line-height: 1;
            transition: transform 160ms ease, background 160ms ease, box-shadow 160ms ease;
        }

        .hms-sidebar-toggle:hover {
            transform: translateY(-1px);
            background: var(--shell-white-86);
            box-shadow: none;
        }

        .hms-breadcrumb-link {
            color: var(--shell-secondary) !important;
            font-weight: 800;
            text-decoration: none !important;
            font-size: 1.06rem;
        }

        .hms-breadcrumb-link:hover {
            opacity: .88;
            text-decoration: none !important;
        }

        .breadcrumb-item,
        .breadcrumb-item.active {
            font-size: 1.02rem;
            font-weight: 650;
        }

        .breadcrumb-item.active {
            opacity: 0.9;
        }

        .wait-queue-badge {
            background: var(--shell-white-70) !important;
            border: 1px solid var(--shell-s2-12) !important;
            box-shadow: none;
            padding: 0.46rem 1.05rem;
        }

        .wait-queue-badge i {
            color: var(--shell-secondary) !important;
            opacity: .95;
        }

        .wait-queue-badge span {
            color: rgba(27, 79, 114, 0.72) !important;
            font-weight: 700;
            font-size: 1.02rem;
        }

        .wait-queue-badge strong {
            color: var(--shell-secondary) !important;
            font-weight: 900;
            font-size: 1.45rem;
        }

        .reception-register-actions {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin-left: .65rem;
            margin-top: .85rem;
            align-self: flex-end;
        }

        .reception-register-btn {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .42rem .78rem;
            border-radius: 999px;
            border: 1px solid transparent;
            background: var(--shell-secondary);
            color: #ffffff !important;
            font-size: .83rem;
            font-weight: 800;
            letter-spacing: .02em;
            text-decoration: none;
            line-height: 1;
            transition: background 160ms ease, transform 160ms ease;
        }

        .reception-register-btn i,
        .reception-register-btn span {
            color: #ffffff !important;
        }

        .reception-register-btn:hover {
            background: #164361;
            transform: translateY(-1px);
            text-decoration: none;
        }

        .avatar-circle {
            background: rgba(27, 79, 114, 0.08) !important;
            border: 1px solid var(--shell-s2-12);
            color: var(--shell-secondary) !important;
            box-shadow: none;
        }

        .user-name {
            font-size: .92rem;
            font-weight: 900;
        }

        .user-role {
            color: rgba(27, 79, 114, 0.72) !important;
            font-weight: 650;
            font-size: .86rem;
        }

        .user-dropdown .dropdown-menu {
            border: 1px solid var(--shell-s2-12) !important;
            border-radius: 16px;
            box-shadow: none;
            overflow: hidden;
        }

        .user-dropdown .dropdown-item {
            border-radius: 12px;
            font-weight: 700;
        }

        /* ── Sidebar ────────────────────────────────────────────────── */
        .hms-sidebar {
            background: var(--shell-white-78) !important;
            border-right: none !important;
            box-shadow: none;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        /* Keep logo readable: brand strip in secondary */
        .premium-sidebar-brand {
            background: var(--shell-secondary);
            border-bottom: 1px solid rgba(255, 255, 255, 0.18) !important;
            margin-bottom: .6rem;
        }

        .premium-sidebar-brand-link {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .85rem 1rem;
            text-decoration: none;
        }

        .sidebar-brand-mark {
            background: transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 63px;
            height: 63px;
            overflow: hidden;
        }

        .sidebar-brand-mark .sidebar-logo {
            height: 48px;
            width: auto;
            max-width: 63px;
            object-fit: contain;
            display: block;
        }

        .sidebar-brand-copy {
            display: flex;
            flex-direction: column;
            min-width: 0;
            line-height: 1.1;
        }

        .sidebar-brand-name {
            color: #fff;
            font-size: 2rem;
            font-weight: 900;
            letter-spacing: -.02em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-brand-tag {
            color: rgba(255, 255, 255, 0.74);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .hms-sidenav {
            padding: 1rem .75rem;
            gap: .25rem;
        }

        .hms-nav-section-label {
            color: rgba(27, 79, 114, 0.58) !important;
            font-weight: 900;
            font-size: .70rem;
            letter-spacing: .12em;
        }

        .hms-nav-divider {
            background: var(--shell-s2-12) !important;
            opacity: 1;
        }

        .hms-nav-group-toggle {
            background: rgba(255, 255, 255, 0.65);
            border: none;
            border-radius: 8px;
            margin: .2rem .5rem;
            padding: .65rem .9rem;
            transition: transform 160ms ease, box-shadow 160ms ease, background 160ms ease, border-color 160ms ease;
        }

        .hms-nav-group-toggle:hover {
            transform: translateX(2px);
            box-shadow: none;
            background: var(--shell-secondary);
            border-color: transparent;
        }

        .hms-nav-group-toggle:hover .hms-nav-section-label,
        .hms-nav-group-toggle:hover .hms-nav-chevron {
            color: rgba(255, 255, 255, 0.95) !important;
        }

        .hms-nav-group-toggle .hms-nav-chevron {
            color: rgba(27, 79, 114, 0.72) !important;
            font-size: .75rem;
        }

        /* Make group header text bigger/bolder */
        .hms-nav-group-toggle .hms-nav-section-label {
            font-size: 1.05rem !important;
            font-weight: 900 !important;
            letter-spacing: .05em !important;
            color: rgba(27, 79, 114, 0.78) !important;
        }

        /* Dropdown items under the section: slightly smaller text */
        .hms-nav-group-items .hms-nav-item {
            font-size: .92rem;
        }

        .hms-nav-group-items .hms-nav-item i {
            font-size: .95rem;
        }

        .hms-nav-item {
            color: var(--shell-secondary) !important;
            font-size: 17px;
            font-weight: 900;
            border-radius: 18px;
            padding: .70rem .95rem;
            background: transparent;
            border: none;
            position: relative;
            transition: transform 160ms ease, background 160ms ease, box-shadow 160ms ease, border-color 160ms ease;
        }

        .hms-nav-item::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 50%;
            width: 0;
            height: 18px;
            transform: translateY(-50%);
            border-radius: 999px;
            background: var(--shell-secondary);
            opacity: 0;
            transition: width 160ms ease, opacity 160ms ease;
        }

        .hms-nav-item i {
            color: var(--shell-secondary) !important;
            font-size: 1.05rem;
            transition: transform 160ms ease;
        }

        .hms-nav-item:hover {
            background: var(--shell-secondary) !important;
            border-color: transparent !important;
            box-shadow: none;
            transform: translateX(2px);
            color: rgba(255, 255, 255, 0.98) !important;
            text-decoration: none !important;
            padding-left: .95rem;
            /* prevent old left-padding shift feeling */
        }

        .hms-nav-item:hover i {
            color: rgba(255, 255, 255, 0.98) !important;
        }

        .hms-nav-item:hover i {
            transform: translateX(1px);
        }

        .hms-nav-item.active {
            background: rgba(27, 79, 114, 0.12) !important;
            color: var(--shell-secondary) !important;
            border-left: none !important;
            border-color: transparent !important;
            box-shadow: none;
            padding-left: .95rem !important;
        }

        .hms-nav-item.active::before {
            width: 4px;
            opacity: 1;
        }

        /* Sidebar footer logout: keep it clean in same palette */
        .hms-sidebar-footer {
            border-top: none !important;
        }

        .hms-sidebar-logout {
            color: var(--shell-secondary) !important;
            font-weight: 900;
            border-radius: 18px;
            border: none;
            background: rgba(255, 255, 255, 0.65);
        }

        .hms-sidebar-logout:hover {
            background: var(--shell-secondary) !important;
            color: rgba(255, 255, 255, 0.98) !important;
        }

        .hms-sidebar-logout:hover i {
            color: rgba(255, 255, 255, 0.98) !important;
        }

        /* Black menu dropdown ne proper align karva mate */
        .black-menu-bar .dropdown {
            position: relative;
        }

        /* Default ma menu hide rakhva mate */
        .black-menu-bar .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 0;
            background-color: #ffffff;
            border: 1px solid #ddd;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            padding: 5px 0;
            min-width: 150px;
        }

        .black-menu-bar .dropdown::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            height: 8px;
        }

        .black-menu-bar .dropdown:hover>.dropdown-menu {
            display: block;
        }


        .black-menu-bar .dropdown-item {
            color: #333 !important;
            padding: 8px 15px;
            display: block;
        }

        .black-menu-bar .dropdown-item:hover {
            background-color: #f0f0f0;
        }

        .dropdown-menu .dropend {
            position: relative;
        }

        .dropdown-menu .dropend>.dropdown-menu {
            top: 0;
            left: 100%;
            margin-top: -0.25rem;
            display: none;
        }

        .dropdown-menu .dropend:hover>.dropdown-menu,
        .dropdown-menu .dropend.show>.dropdown-menu {
            display: block;
        }

        .black-menu-bar .dropdown-menu .dropdown-menu {
            display: none;
        }

        .dropdown-menu .dropdown-toggle::after {
            float: right;
            margin-top: 0.5em;
        }


        @if(auth('hospital_user')->user()?->role?->slug === 'doctor')
            .hms-navbar {
                display: flex;
                flex-direction: column;
                padding: 10px 20px !important;
            }

            .top-header {
                display: flex;
                justify-content: space-between;
                width: 100%;
                align-items: center;
                margin-bottom: 10px;
            }

            .black-menu-bar {
                background: #1a1a1a;
                width: 100%;
                padding: 10px 20px;
                display: flex;
                gap: 20px;
                color: white;
            }

            .black-menu-bar a {
                color: white;
                text-decoration: none;
            }

        @endif
        /* Reduce-motion support */
        @media (prefers-reduced-motion: reduce) {

            .hms-nav-item,
            .hms-nav-group-toggle,
            .hms-sidebar-toggle {
                transition: none !important;
            }
        }

        .avatar-circle {
            width: 35px;
            height: 35px;
            background: #1B4F72;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-name {
            font-size: 13px;
            font-weight: 800;
            color: #1B4F72;
            line-height: 1;
        }

        .user-role {
            font-size: 10px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }

        .user-dropdown .dropdown-toggle::after {
            display: none;
        }

        /* એરો દૂર કરવા માટે */


        .hms-sidebar-backdrop {
            background: rgba(27, 79, 114, 0.35) !important;
        }

        @if(auth('hospital_user')->user()?->role?->slug === 'doctor')
                <style>.hms-layout {
                    display: block !important;
                }

                .hms-main {
                    margin-left: 0 !important;
                    width: 100% !important;
                    padding: 1.5rem !important;
                }

                #hmsSidebarToggle {
                    display: none !important;
                }
            </style>
        @endif
    </style>

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
        @if(auth('hospital_user')->user()?->role?->slug === 'doctor')
            <div class="top-header">
                <div class="d-flex align-items-center">
                    <img src="{{ $hospitalLogoUrl }}" style="height: 40px; margin-right: 15px;">
                    <div class="fw-bold fs-4">{{ $hospitalName }}</div>
                </div>
                <div class="dropdown user-dropdown">
                    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown"
                        style="text-decoration: none;">
                        <span class="avatar-circle">
                            <i class="bi bi-person-fill"></i>
                        </span>
                        <div class="user-info d-flex flex-column text-start">
                            <span class="user-name">{{ auth('hospital_user')->user()->name }}</span>
                            <small class="user-role">{{ auth('hospital_user')->user()->role?->name ?? 'Admin' }}</small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end"
                        style="border-radius: 12px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <li>
                            <a class="dropdown-item"
                                href="{{ route('hospital.profile.show', ['slug' => request()->route('slug')]) }}">
                                <i class="bi bi-person-circle me-2"></i> My Profile
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider my-1">
                        </li>
                        <li>
                            <form method="POST"
                                action="{{ route('hospital.logout', ['slug' => request()->route('slug')]) }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger fw-bold">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="black-menu-bar d-flex align-items-center"
                style="gap: 25px; padding: 10px 20px; background: #1b4f72; color: white;">

                {{-- Dashboard --}}
                <a href="{{ route('hospital.dashboard', ['slug' => request()->route('slug')]) }}"
                    class="text-white text-decoration-none"><i class="bi bi-house-door-fill"></i> Dashboards</a>

                {{-- Basic Master Dropdown --}}
                <!-- <div class="dropdown">
                                                                                        <a href="#" class="text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                                                                                            <i class="bi bi-list-task"></i> Basic Master
                                                                                        </a>
                                                                                        <ul class="dropdown-menu">
                                                                                            <li><a class="dropdown-item" href="{{ url($slug . '/masters/basic/cases') }}">Case Type</a></li>
                                                                                    <li><a class="dropdown-item" href="{{ url($slug . '/masters/basic/locations') }}">Location</a></li>
                                                                                    <li><a class="dropdown-item" href="{{ url($slug . '/masters/basic/referrers') }}">Refered By</a></li>
                                                                                    <li><a class="dropdown-item" href="{{ url($slug . '/masters/basic/durations') }}">Duration</a></li>
                                                                                        </ul>
                                                                                    </div> -->

                {{-- Diagnosis Master Dropdown --}}
                <div class="dropdown">
                    <a href="#" class="text-white text-decoration-none dropdown-toggle">
                        <i class="bi bi-info-circle-fill"></i> Diagnosis Master
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ url($slug . '/masters/detail/chief-complaints') }}">C/O</a>
                        </li>
                        <li><a class="dropdown-item" href="{{ url($slug . '/masters/detail/kcos') }}">KCO</a></li>
                        <li class="dropend">
                            <a class="dropdown-item dropdown-toggle" href="#">O/E</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ url($slug . '/masters/detail/sac') }}">SAC</a></li>
                                <li><a class="dropdown-item" href="{{ url($slug . '/masters/detail/lid') }}">LID</a></li>
                                <li><a class="dropdown-item" href="{{ url($slug . '/masters/detail/conj') }}">CONJ</a></li>
                                <li><a class="dropdown-item" href="{{ url($slug . '/masters/detail/cornea') }}">CORNEA</a>
                                </li>
                                <li><a class="dropdown-item" href="{{ url($slug . '/masters/detail/ac') }}">AC</a></li>
                                <li><a class="dropdown-item" href="{{ url($slug . '/masters/detail/iris') }}">IRIS</a></li>
                                <li><a class="dropdown-item" href="{{ url($slug . '/masters/detail/pupil') }}">PUPIL</a>
                                </li>
                                <li><a class="dropdown-item" href="{{ url($slug . '/masters/detail/lens') }}">LENS</a></li>
                                <li><a class="dropdown-item" href="{{ url($slug . '/masters/detail/em') }}">EM</a></li>
                                <li><a class="dropdown-item" href="{{ url($slug . '/masters/detail/covertest') }}">COVER
                                        TEST</a></li>
                            </ul>
                        </li>

                        <li class="dropend">
                            <a class="dropdown-item dropdown-toggle" href="#">FUNDUS</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ url($slug . '/masters/detail/disc') }}">DISC</a></li>
                                <li><a class="dropdown-item" href="{{ url($slug . '/masters/detail/fr') }}">FR</a></li>
                            </ul>
                        </li>

                        <li><a class="dropdown-item" href="{{ url($slug . '/masters/detail/diagnosis') }}">DIAGNOSIS</a>
                        </li>
                        <li><a class="dropdown-item" href="{{ url($slug . '/masters/detail/advice') }}">ADVICE</a></li>
                    </ul>
                </div>

                <div class="dropdown">
                    <a href="#" class="text-white text-decoration-none dropdown-toggle">
                        <i class="bi bi-capsule"></i> Medicine Master
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ url($slug . '/medicine-dosages') }}">Dosage</a></li>
                        <li><a class="dropdown-item" href="{{ url($slug . '/medicine-types') }}">Medicine Type</a></li>
                        <li><a class="dropdown-item" href="{{ url($slug . '/medicine-categories') }}">Medicine Category</a>
                        </li>
                        <li><a class="dropdown-item" href="{{ url($slug . '/medicine-routes') }}">Root Of Administration</a>
                        </li>
                        <li><a class="dropdown-item" href="{{ url($slug . '/medicines') }}">Add Medicine</a></li>
                        <li><a class="dropdown-item" href="{{ url($slug . '/medicine-groups') }}">Add Group</a></li>
                    </ul>
                </div>

                {{-- History --}}
                <a href="{{ route('hospital.doctor.history', ['slug' => $slug ?? request()->route('slug')]) }}"
                    class="text-white text-decoration-none">
                    <i class="bi bi-clock-history"></i> History
                </a>
            </div>

        @else
            @php
                $currentUser = auth('hospital_user')->user();
                $slug = request()->route('slug');
                $isReceptionistUser = in_array($currentUser?->role?->slug, ['receptionist', 'receptionist_opd'], true);
                $showReceptionRegisterActions = $isReceptionistUser && request()->routeIs('hospital.dashboard');
                $permSvcTop = app(\App\Services\Auth\RolePermissionService::class);

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

                @if($showReceptionRegisterActions)
                    <div class="reception-register-actions">
                        @if($permSvcTop->can('opd.patient.register'))
                            <a href="{{ route('hospital.patients.create', ['slug' => $slug]) }}" class="reception-register-btn">
                                <i class="bi bi-person-plus"></i>
                                <span>WALK IN</span>
                            </a>
                        @endif
                        @if($permSvcTop->can('opd.patient.register_phone'))
                            <a href="{{ route('hospital.patients.create-phone', ['slug' => $slug]) }}"
                                class="reception-register-btn">
                                <i class="bi bi-telephone"></i>
                                <span>PHONE</span>
                            </a>
                        @endif
                    </div>
                @endif

                @if($currentUser)
                    <div class="dropdown user-dropdown">
                        <button class="nav-link dropdown-toggle d-flex align-items-center gap-2 border-0 bg-transparent"
                            id="navbarUserDropdown" type="button" data-bs-toggle="dropdown" data-bs-auto-close="true"
                            aria-expanded="false">
                            <span class="avatar-circle">
                                <i class="bi bi-person-fill"></i>
                            </span>
                            <span class="user-info d-none d-md-flex flex-column text-start">
                                <span class="user-name">{{ $currentUser->name }}</span>
                                <small class="user-role">{{ $currentUser->role?->name ?? 'Hospital Staff' }}</small>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                            @if($currentUser->role?->is_super)
                                {{-- Hospital Admin: full menu --}}
                                <li>
                                    <h6 class="dropdown-header">Switch Workspace</h6>
                                </li>
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
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('hospital.settings.index', ['slug' => $slug]) }}">
                                        <i class="bi bi-gear me-2"></i> Settings
                                    </a>
                                </li>
                            @endif
                            {{-- All roles: My Profile + Logout --}}
                            <li>
                                <a class="dropdown-item" href="{{ route('hospital.profile.show', ['slug' => $slug]) }}">
                                    <i class="bi bi-person-circle me-2"></i> My Profile
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
        @endif
    </nav>

    <div class="hms-layout">

        {{-- Mobile backdrop --}}
        <div class="hms-sidebar-backdrop" id="hmsSidebarBackdrop"></div>

        {{-- ============================================
        Sidebar Navigation
        ============================================ --}}
        @if(auth('hospital_user')->user()?->role?->slug !== 'doctor')
        <aside class="hms-sidebar" id="hmsSidebar">
            <div class="premium-sidebar-brand">
                <a href="{{ route('hospital.dashboard', ['slug' => request()->route('slug')]) }}"
                    class="premium-sidebar-brand-link" aria-label="Go to dashboard">
                    <span class="sidebar-brand-mark">
                        @php
                            $sidebarStyle = $hospitalLogoSidebarStyle ?? 'white';
                            $useBlurBox = !empty($hospitalLogo) && $sidebarStyle === 'original_blur';
                            $sidebarLogoFilter = (!empty($hospitalLogo) && $sidebarStyle === 'white') || empty($hospitalLogo)
                                ? 'brightness(0) invert(1)'
                                : 'none';
                        @endphp
                        @if($useBlurBox)
                            <span
                                style="display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;background:rgba(255,255,255,.22);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border-radius:10px;">
                                <img src="{{ $hospitalLogoUrl }}" alt="{{ $hospitalName }} Logo" class="sidebar-logo"
                                    style="filter:none!important;" loading="lazy" decoding="async">
                            </span>
                        @else
                            <img src="{{ $hospitalLogoUrl }}" alt="{{ $hospitalName }} Logo" class="sidebar-logo"
                                style="filter:{{ $sidebarLogoFilter }}!important;" loading="lazy" decoding="async">
                        @endif
                    </span>
                    <span class="sidebar-brand-copy">
                        <span class="sidebar-brand-name">{{ $hospitalName }}</span>
                        <span class="sidebar-brand-tag">Hospital Workspace</span>
                    </span>
                </a>
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
                            class="hms-nav-item {{ (request()->routeIs('hospital.patients.*') && !request()->routeIs('hospital.patients.history')) ? 'active' : '' }}">
                            <i class="bi bi-people-fill"></i>
                            <span>Patients</span>
                        </a>
                        <a href="{{ route('hospital.doctor.history', ['slug' => request()->route('slug')]) }}"
                            class="hms-nav-item {{ request()->routeIs('hospital.doctor.history') ? 'active' : '' }}">
                            <i class="bi bi-clock-history"></i>
                            <span>Share History</span>
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
                            <a href="{{ route('hospital.clinical.queue', ['slug' => request()->route('slug')]) }}"
                                class="hms-nav-item {{ request()->routeIs('hospital.clinical.queue') ? 'active' : '' }}">
                                <i class="bi bi-list-check"></i>
                                <span>Queue Dashboard</span>
                            </a>
                        </div>
                    @endif

                    {{-- ── FOC ─────────────────────────────────────────
                    Visible if user can view or approve FOC.
                    ── --}}
                    <!-- @php
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
                           class="hms-nav-item {{ (request()->routeIs('hospital.foc.index') || request()->routeIs('hospital.foc.create') || request()->routeIs('hospital.foc.store') || request()->routeIs('hospital.foc.request') || request()->routeIs('hospital.foc.show')) ? 'active' : '' }}">
                            <i class="bi bi-heart-fill"></i>
                            <span>FOC Requests</span>
                        </a>
                    @endhaspermission
                    @haspermission('opd.foc.accept')
                        <a href="{{ route('hospital.foc.index', ['slug' => request()->route('slug')]) }}"
                           class="hms-nav-item {{ (request()->routeIs('hospital.foc.accept') || request()->routeIs('hospital.foc.approve') || request()->routeIs('hospital.foc.reject')) ? 'active' : '' }}">
                            <i class="bi bi-heart-pulse-fill"></i>
                            <span>Approve FOC</span>
                        </a>
                    @endhaspermission
                </div>
                @endif -->

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
                        <a href="{{ route('hospital.medicine-categories.index', ['slug' => request()->route('slug')]) }}"
                            class="hms-nav-item {{ request()->routeIs('hospital.medicine-categories.*') ? 'active' : '' }}">
                            <i class="bi bi-grid"></i>
                            <span>Medicine Categories</span>
                        </a>
                        <a href="{{ route('hospital.medicine-routes.index', ['slug' => request()->route('slug')]) }}"
                            class="hms-nav-item {{ request()->routeIs('hospital.medicine-routes.*') ? 'active' : '' }}">
                            <i class="bi bi-arrow-right-circle"></i>
                            <span>Route of Admin.</span>
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
        @endif

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
                    @if(
                            auth('hospital_user')->user()?->role?->slug === 'doctor'
                            && !request()->routeIs('hospital.dashboard')
                            && !request()->routeIs('hospital.masters.detail.*')
                            && !request()->routeIs('hospital.masters.basic.*')
                        )
                        <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}" class="btn btn-sm btn-light me-2"
                            style="border-radius:8px; border:1px solid #cde5f5; color:#1B4F72;">
                            Back
                        </a>
                    @endif
                    <h1 style="font-weight:900!important;color:#0D2137!important;letter-spacing:-.015em">
                        @yield('page-header')</h1>
                    @hasSection('page-actions')
                        <div class="hms-page-actions">@yield('page-actions')</div>
                    @endif
                </div>
            @endif

            {{-- Main Content --}}
            @yield('content')

            @unless(request()->routeIs('hospital.dashboard'))
                <style>
                    .hms-main .shadow,
                    .hms-main .shadow-sm,
                    .hms-main .shadow-lg,
                    .hms-main .premium-card,
                    .hms-main .hms-card,
                    .hms-main .card,
                    .hms-main [class*="-hero"],
                    .hms-main [class*="-card"],
                    .hms-main [class*="-pill"],
                    .hms-main [class*="-btn"],
                    .hms-main [class*="-icon"],
                    .hms-main [class*="-box"],
                    .hms-main table tbody tr,
                    .hms-main table tbody tr:hover,
                    .hms-main .btn,
                    .hms-main .btn:hover,
                    .hms-main .badge {
                        box-shadow: none !important;
                    }

                    .hms-main a[title*="Edit"] i,
                    .hms-main button[title*="Edit"] i,
                    .hms-main a[aria-label*="Edit"] i,
                    .hms-main button[aria-label*="Edit"] i,
                    .hms-main [class*="edit-btn"] i,
                    .hms-main [class*="edit-action"] i,
                    .hms-main [class*="edit-type"] i,
                    .hms-main [class*="edit-dosage"] i,
                    .hms-main [class*="edit-instruction"] i {
                        color: #E67E22 !important;
                    }

                    .hms-main a[title*="Edit"]:hover,
                    .hms-main button[title*="Edit"]:hover,
                    .hms-main a[aria-label*="Edit"]:hover,
                    .hms-main button[aria-label*="Edit"]:hover,
                    .hms-main [class*="edit-btn"]:hover,
                    .hms-main [class*="edit-action"]:hover,
                    .hms-main [class*="edit-type"]:hover,
                    .hms-main [class*="edit-dosage"]:hover,
                    .hms-main [class*="edit-instruction"]:hover {
                        background: #E67E22 !important;
                        border-color: #E67E22 !important;
                        color: #ffffff !important;
                    }

                    .hms-main a[title*="Edit"]:hover i,
                    .hms-main button[title*="Edit"]:hover i,
                    .hms-main a[aria-label*="Edit"]:hover i,
                    .hms-main button[aria-label*="Edit"]:hover i,
                    .hms-main [class*="edit-btn"]:hover i,
                    .hms-main [class*="edit-action"]:hover i,
                    .hms-main [class*="edit-type"]:hover i,
                    .hms-main [class*="edit-dosage"]:hover i,
                    .hms-main [class*="edit-instruction"]:hover i {
                        color: #ffffff !important;
                    }

                    .hms-main a[title*="Delete"] i,
                    .hms-main button[title*="Delete"] i,
                    .hms-main a[aria-label*="Delete"] i,
                    .hms-main button[aria-label*="Delete"] i,
                    .hms-main [class*="delete-btn"] i,
                    .hms-main [class*="delete-action"] i,
                    .hms-main [class*="delete-type"] i,
                    .hms-main [class*="delete-dosage"] i,
                    .hms-main [class*="delete-instruction"] i {
                        color: #C0392B !important;
                    }

                    .hms-main a[title*="Delete"]:hover,
                    .hms-main button[title*="Delete"]:hover,
                    .hms-main a[aria-label*="Delete"]:hover,
                    .hms-main button[aria-label*="Delete"]:hover,
                    .hms-main [class*="delete-btn"]:hover,
                    .hms-main [class*="delete-action"]:hover,
                    .hms-main [class*="delete-type"]:hover,
                    .hms-main [class*="delete-dosage"]:hover,
                    .hms-main [class*="delete-instruction"]:hover {
                        background: #C0392B !important;
                        border-color: #C0392B !important;
                        color: #ffffff !important;
                    }

                    .hms-main a[title*="Delete"]:hover i,
                    .hms-main button[title*="Delete"]:hover i,
                    .hms-main a[aria-label*="Delete"]:hover i,
                    .hms-main button[aria-label*="Delete"]:hover i,
                    .hms-main [class*="delete-btn"]:hover i,
                    .hms-main [class*="delete-action"]:hover i,
                    .hms-main [class*="delete-type"]:hover i,
                    .hms-main [class*="delete-dosage"]:hover i,
                    .hms-main [class*="delete-instruction"]:hover i {
                        color: #ffffff !important;
                    }
                </style>
            @endunless
        </main>
    </div>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script>
        if (typeof window.bootstrap === 'undefined') {
            var fallbackScript = document.createElement('script');
            fallbackScript.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
            fallbackScript.integrity = 'sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz';
            fallbackScript.crossOrigin = 'anonymous';
            document.head.appendChild(fallbackScript);
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.dropdown-menu .dropend').forEach(function (item) {
                var trigger = item.querySelector(':scope > .dropdown-toggle');

                if (!trigger) {
                    return;
                }

                item.addEventListener('mouseenter', function () {
                    item.classList.add('show');
                });

                item.addEventListener('mouseleave', function () {
                    item.classList.remove('show');
                });

                trigger.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    item.classList.toggle('show');
                });
            });
        });
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
            var sidebar = document.getElementById('hmsSidebar');
            var backdrop = document.getElementById('hmsSidebarBackdrop');
            var toggle = document.getElementById('hmsSidebarToggle');

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

    @stack('modals')
    @stack('scripts')
</body>

</html>