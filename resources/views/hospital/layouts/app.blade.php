<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hospital') — {{ hospital_name() }}</title>
    <link rel="icon" type="image/png" href="{{ platform_favicon_url() }}">

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
        integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Select2 CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />

    {{-- Flatpickr CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />

    {{-- DataTables (Bootstrap 5) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" />
    <style>
        table.js-datatable {
            width: 100% !important;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: .875rem;
            color: #334155;
            background-color: #fff;
            padding: .35rem .65rem;
            min-width: 12rem;
        }

        /*
          Bootstrap form-select already draws a custom chevron.
          Do NOT force appearance:menulist (double arrow / overlap).
        */
        .dataTables_wrapper .dataTables_length select.form-select,
        .dataTables_wrapper .dataTables_length select {
            min-width: 5.25rem !important;
            width: auto !important;
            display: inline-block;
            vertical-align: middle;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: .875rem;
            color: #334155;
            background-color: #fff;
            padding: .375rem 2.35rem .375rem .75rem !important;
            line-height: 1.4;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%231B4F72' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right .65rem center !important;
            background-size: 14px 10px !important;
        }

        .dataTables_wrapper .dataTables_filter input:focus,
        .dataTables_wrapper .dataTables_length select:focus {
            border-color: #1B4F72;
            outline: none;
            box-shadow: 0 0 0 .2rem rgba(27, 79, 114, .15);
        }

        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_paginate {
            font-size: .85rem;
            color: #64748b;
            padding-top: .75rem;
            padding-bottom: .25rem;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 6px !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #1B4F72 !important;
            border-color: #1B4F72 !important;
            color: #fff !important;
        }

        .card .dataTables_wrapper {
            padding: 0 .75rem .75rem;
        }

        .card .dataTables_wrapper .row:first-child {
            padding-top: .75rem;
        }

        /* Select2 height align with Bootstrap form-select */
        .select2-container--default .select2-selection--single {
            min-height: 38px;
            padding: .25rem .25rem;
            border: 1px solid rgba(27, 79, 114, 0.18);
            border-radius: 8px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px;
            padding-left: .5rem;
            color: #1B4F72;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #1B4F72;
        }

        /* Flatpickr range highlight */
        .flatpickr-day.inRange,
        .flatpickr-day.nextMonthDay.inRange,
        .flatpickr-day.prevMonthDay.inRange {
            background: rgba(27, 79, 114, 0.12) !important;
            border-color: transparent !important;
            box-shadow: none !important;
            color: #1B4F72 !important;
        }

        .flatpickr-day.selected.startRange,
        .flatpickr-day.selected.endRange,
        .flatpickr-day.startRange,
        .flatpickr-day.endRange {
            background: #1B4F72 !important;
            border-color: #1B4F72 !important;
            color: #fff !important;
        }
    </style>

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

        @else :root {
                /* No more fixed top navbar for non-doctor roles — the sidebar
                                                                       now runs full height and the search/profile bar lives
                                                                       inside the scrollable content column (design refresh). */
                --hms-navbar-h: 0px;
            }

        @endif

        /* ── Navbar (doctor role only — other roles use .hms-content-topbar
               inside the content column, see below) ────────────────────── */
        .hms-navbar {
            background: var(--shell-white-78) !important;
            color: var(--shell-secondary) !important;
            border-bottom: 1px solid var(--shell-s2-12) !important;
            box-shadow: none !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .hms-search-form,
        .hms-search-form-spacer {
            position: relative;
            flex: 1 1 auto;
            display: flex;
            align-items: center;
        }

        .hms-search-form i {
            position: absolute;
            left: 14px;
            color: var(--shell-s2-70, rgba(27, 79, 114, 0.55));
            font-size: .95rem;
            pointer-events: none;
        }

        .hms-search-input {
            width: 100%;
            border: 1px solid var(--shell-s2-12);
            background: var(--shell-white-70);
            border-radius: 10px;
            padding: .55rem 1rem .55rem 2.4rem;
            font-size: .88rem;
            color: var(--shell-secondary);
            outline: none;
            transition: background 160ms ease, border-color 160ms ease, box-shadow 160ms ease;
        }

        .hms-search-input::placeholder {
            color: rgba(27, 79, 114, 0.45);
        }

        .hms-search-input:focus {
            background: #ffffff;
            border-color: var(--shell-secondary);
            box-shadow: 0 0 0 .18rem rgba(27, 79, 114, 0.12);
        }

        /* ── In-content top bar (search + profile) — full-width flush bar,
               bled out to the edges of .hms-main's padding ────────────── */
        .hms-content-topbar {
            display: flex;
            align-items: center;
            gap: 1rem;

            /* Always use the complete available content width */
            width: 100%;
            box-sizing: border-box;

            margin: -1.5rem 0 1.5rem;
            padding: 1rem 1.5rem;

            background: #ffffff;
            border-bottom: 1px solid var(--shell-s2-12);

            /* Stay pinned to the top of the viewport while the content
               column scrolls underneath, instead of scrolling away. */
            position: sticky;
            top: 0;
            z-index: 850;
        }

        @media (max-width: 768px) {
            .hms-content-topbar {
                margin: -1rem -1rem 1rem;
                padding: .85rem 1rem;
            }
        }

        .hms-search-form,
        .hms-search-form-spacer {
            position: relative;

            flex: 0 1 420px;
            min-width: 0;
            width: auto;
            max-width: 420px;

            display: flex;
            align-items: center;
        }

        .hms-search-input {
            background: #F3F7FB;
        }

        .hms-content-topbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-shrink: 0;
            margin-left: auto;
        }

        .hms-content-user-info {
            display: none;
            flex-direction: column;
            align-items: flex-end;
            line-height: 1.2;
            background: transparent;
            border: none;
            padding: 0;
            cursor: pointer;
        }

        .hms-content-user-info::after {
            display: none;
        }

        .hms-content-user-info strong {
            font-size: .82rem;
            font-weight: 800;
            color: var(--shell-secondary);
        }

        .hms-content-user-info small {
            font-size: .72rem;
            color: rgba(27, 79, 114, 0.6);
            text-transform: capitalize;
        }

        @media (min-width: 768px) {
            .hms-content-user-info {
                display: flex;
            }
        }

        .hms-content-logout-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            border: none;
            background: var(--shell-secondary);
            color: #ffffff;
            cursor: pointer;
            transition: background 160ms ease, transform 160ms ease;
        }

        .hms-content-logout-btn i {
            color: #ffffff;
            font-size: 1.05rem;
        }

        .hms-content-logout-btn:hover {
            background: #164361;
            transform: translateY(-1px);
        }

        .hms-content-logout-btn.dropdown-toggle::after {
            display: none;
        }

        .hms-content-profile-toggle {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: transparent;
            border: none;
            padding: 0;
            line-height: 0;
        }

        .hms-content-profile-toggle::after {
            display: none;
        }

        .hms-content-profile-toggle i {
            color: var(--shell-secondary);
            font-size: .8rem;
        }

        .hms-content-profile-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 33px;
            height: 33px;
            border-radius: 999px;
            border: 1px solid var(--shell-s2-12);
            background: var(--shell-white-70);
            overflow: hidden;
            flex-shrink: 0;
        }

        .hms-content-profile-avatar img {
            width: 108%;
            height: 100%;
            object-fit: contain;
            padding: 5px;
        }

        .hms-content-profile-menu {
            min-width: 250px;
            border: none;
            border-radius: 16px;
            padding: .6rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
        }

        .hms-content-profile-header {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .4rem .5rem .7rem;
        }

        .hms-content-profile-header-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 999px;
            border: 1px solid var(--shell-s2-12);
            overflow: hidden;
            flex-shrink: 0;
        }

        .hms-content-profile-header-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 4px;
        }

        .hms-content-profile-name {
            font-size: .92rem;
            font-weight: 800;
            color: var(--shell-secondary);
            line-height: 1.2;
        }

        .hms-content-profile-role {
            font-size: .74rem;
            color: rgba(27, 79, 114, 0.6);
            font-weight: 600;
        }

        .hms-content-profile-menu .dropdown-item {
            border-radius: 10px;
            padding: .5rem .6rem;
            font-size: .88rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .hms-search-form {
                max-width: none;
                flex: 1 1 auto;
                min-width: 0;
            }

            .hms-content-topbar {
                flex-wrap: wrap;
                gap: .65rem;
                overflow: visible;
            }

            .hms-content-topbar-right {
                gap: .5rem;
                flex-shrink: 0;
                width: 100%;
                order: 3;
                justify-content: space-between;
                margin-left: 0;
            }

            .reception-register-actions {
                margin-left: 0;
                flex: 1 1 auto;
                min-width: 0;
            }

            .reception-register-btn {
                padding: .4rem .55rem;
                font-size: .75rem;
            }

            .reception-register-btn span {
                display: none;
            }

            .hms-main {
                overflow-x: hidden;
            }
        }

        @media (max-width: 420px) {
            .hms-search-input {
                font-size: .82rem;
                padding-left: 2.1rem;
            }

            .hms-search-input::placeholder {
                font-size: .78rem;
            }
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
            margin-top: 0;
            align-self: center;
            flex-wrap: wrap;
            max-width: 100%;
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
            white-space: nowrap;
            flex-shrink: 0;
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
            background: #ffffff !important;
            border-right: 1px solid var(--shell-s2-12) !important;
            box-shadow: none;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
            overflow: visible !important;
            transition: width 200ms ease;
        }

        .hms-main {
            transition: margin-left 200ms ease;
        }

        /* Floating collapse/expand button pinned to the sidebar edge */
        .hms-sidebar-collapse-btn {
            position: absolute;
            top: 58px;
            right: -16px;
            width: 25px;
            height: 25px;
            border-radius: 999px;
            background: var(--shell-secondary);
            color: #fff;
            border: 3px solid #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            cursor: pointer;
            z-index: 950;
            box-shadow: 0 2px 8px rgba(27, 79, 114, .28);
            transition: background 160ms ease;
        }

        .hms-sidebar-collapse-btn:hover {
            background: #164361;
        }

        .hms-sidebar-collapse-btn i {
            font-size: .75rem;
            transition: transform 200ms ease;
        }

        body.hms-sidebar-collapsed .hms-sidebar-collapse-btn i {
            transform: rotate(180deg);
        }

        @media (max-width: 768px) {
            .hms-sidebar-collapse-btn {
                display: none;
            }

            /* Never keep mini-rail margin on phones — drawer is off-canvas */
            body.hms-sidebar-collapsed .hms-sidebar {
                width: var(--hms-sidebar-w) !important;
            }

            body.hms-sidebar-collapsed .hms-main {
                margin-left: 0 !important;
            }

            body.hms-sidebar-collapsed .sidebar-brand-copy,
            body.hms-sidebar-collapsed .hms-nav-item span,
            body.hms-sidebar-collapsed .hms-nav-group-label-wrap .hms-nav-section-label,
            body.hms-sidebar-collapsed .hms-nav-group-toggle .hms-nav-chevron,
            body.hms-sidebar-collapsed .hms-sidebar-logout span {
                display: initial !important;
            }

            body.hms-sidebar-collapsed .premium-sidebar-brand-link,
            body.hms-sidebar-collapsed .hms-nav-item,
            body.hms-sidebar-collapsed .hms-nav-group-toggle,
            body.hms-sidebar-collapsed .hms-sidebar-logout {
                justify-content: flex-start !important;
                padding-left: .875rem !important;
            }

            body.hms-sidebar-collapsed .sidebar-brand-mark {
                max-width: none !important;
            }

            body.hms-sidebar-collapsed .sidebar-brand-mark .sidebar-logo {
                max-height: none !important;
                width: auto !important;
            }
        }

        /* Collapsed (mini icon-rail) state — desktop only */
        body.hms-sidebar-collapsed .hms-sidebar {
            width: var(--hms-sidebar-w-collapsed);
        }

        body.hms-sidebar-collapsed .hms-main {
            margin-left: var(--hms-sidebar-w-collapsed);
        }

        body.hms-sidebar-collapsed .sidebar-brand-copy,
        body.hms-sidebar-collapsed .hms-nav-item span,
        body.hms-sidebar-collapsed .hms-nav-group-label-wrap .hms-nav-section-label,
        body.hms-sidebar-collapsed .hms-nav-group-toggle .hms-nav-chevron,
        body.hms-sidebar-collapsed .hms-sidebar-logout span {
            display: none !important;
        }

        body.hms-sidebar-collapsed .premium-sidebar-brand {
            height: 72px;
            min-height: 72px;
            max-height: 72px;
        }

        body.hms-sidebar-collapsed .premium-sidebar-brand-link {
            justify-content: center;
            padding: 0;
        }

        body.hms-sidebar-collapsed .sidebar-brand-mark {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 56px;
            overflow: hidden;
        }

        body.hms-sidebar-collapsed .sidebar-brand-mark>span {
            width: 44px !important;
            height: 44px !important;
            padding: 3px;
        }

        body.hms-sidebar-collapsed .sidebar-brand-mark .sidebar-logo {
            height: auto !important;
            width: auto !important;
            max-width: 58px !important;
            max-height: 48px !important;
            transform: scale(1.35) !important;
            object-fit: contain;
            object-position: center;
        }

        body.hms-sidebar-collapsed .hms-nav-item,
        body.hms-sidebar-collapsed .hms-nav-group-toggle,
        body.hms-sidebar-collapsed .hms-sidebar-logout {
            justify-content: center;
            padding-left: .3rem !important;
            padding-right: .3rem !important;
        }

        body.hms-sidebar-collapsed .hms-nav-group-label-wrap {
            justify-content: center;
        }

        /* Hover peek: only when permanently collapsed (desktop).
           Expands as overlay — main content margin stays collapsed. */
        @media (min-width: 769px) {
            body.hms-sidebar-collapsed.hms-sidebar-hover-expand .hms-sidebar {
                width: var(--hms-sidebar-w);
                z-index: 980;
                box-shadow: 8px 0 28px rgba(15, 23, 42, 0.12);
            }

            body.hms-sidebar-collapsed.hms-sidebar-hover-expand .sidebar-brand-copy,
            body.hms-sidebar-collapsed.hms-sidebar-hover-expand .hms-nav-item span,
            body.hms-sidebar-collapsed.hms-sidebar-hover-expand .hms-nav-group-label-wrap .hms-nav-section-label,
            body.hms-sidebar-collapsed.hms-sidebar-hover-expand .hms-nav-group-toggle .hms-nav-chevron,
            body.hms-sidebar-collapsed.hms-sidebar-hover-expand .hms-sidebar-logout span {
                display: initial !important;
            }

            body.hms-sidebar-collapsed.hms-sidebar-hover-expand .premium-sidebar-brand-link,
            body.hms-sidebar-collapsed.hms-sidebar-hover-expand .hms-nav-item,
            body.hms-sidebar-collapsed.hms-sidebar-hover-expand .hms-nav-group-toggle,
            body.hms-sidebar-collapsed.hms-sidebar-hover-expand .hms-sidebar-logout {
                justify-content: flex-start !important;
                padding-left: .875rem !important;
                padding-right: .875rem !important;
            }

            body.hms-sidebar-collapsed.hms-sidebar-hover-expand .premium-sidebar-brand-link {
                padding: 0 !important;
            }

            body.hms-sidebar-collapsed.hms-sidebar-hover-expand .sidebar-brand-mark {
                max-width: none !important;
                width: 100%;
                justify-content: center;
            }

            body.hms-sidebar-collapsed.hms-sidebar-hover-expand .sidebar-brand-mark>span {
                width: auto !important;
                height: auto !important;
            }

            body.hms-sidebar-collapsed.hms-sidebar-hover-expand .sidebar-brand-mark .sidebar-logo {
                max-width: 100% !important;
                max-height: none !important;
                width: 100% !important;
                height: 72px !important;
                transform: scale(1.85) !important;
            }

            body.hms-sidebar-collapsed.hms-sidebar-hover-expand .sidebar-brand-mark .sidebar-logo.platform-logo-on-dark {
                transform: scale(2.15) !important;
            }

            body.hms-sidebar-collapsed.hms-sidebar-hover-expand .hms-nav-group-label-wrap {
                justify-content: flex-start;
            }

            body.hms-sidebar-collapsed.hms-sidebar-hover-expand .sidebar-brand-copy {
                display: flex !important;
            }
        }

        /* Fixed-height brand strip — logo scales inside, blue bg does not grow */
        .premium-sidebar-brand {
            background: var(--shell-secondary);
            border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
            margin-bottom: .45rem;
            height: 72px;
            min-height: 72px;
            max-height: 72px;
            padding: 0;
            overflow: hidden;
            flex-shrink: 0;
        }

        .premium-sidebar-brand-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .75rem;
            padding: 0;
            text-decoration: none;
            width: 100%;
            height: 100%;
        }

        .sidebar-brand-mark {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            line-height: 0;
        }

        .sidebar-brand-mark .sidebar-logo {
            height: 30px;
            width: 100%;
            max-width: 100%;
            object-fit: contain;
            object-position: center;
            display: block;
            transform: scale(1.85);
            transform-origin: center center;
        }

        .sidebar-brand-mark .sidebar-logo.platform-logo-on-dark {
            transform: scale(2.15);
            transform-origin: center center;
        }

        .sidebar-brand-copy {
            display: flex;
            flex-direction: column;
            min-width: 0;
            line-height: 1.1;
        }

        .sidebar-brand-name {
            color: #fff;
            font-size: clamp(1.05rem, calc(2.35rem - 0.05rem * var(--name-len, 10)), 2rem);
            font-weight: 900;
            letter-spacing: -.02em;
            line-height: 1.08;
            word-break: break-word;
            overflow-wrap: break-word;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
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
            background: transparent;
            border: none;
            border-radius: 10px;
            margin: .1rem .35rem;
            padding: .55rem .6rem .3rem;
            transition: background 160ms ease;
        }

        .hms-nav-group-toggle:hover {
            background: var(--shell-s2-06);
        }

        .hms-nav-group-label-wrap {
            display: flex;
            align-items: center;
            gap: .55rem;
            min-width: 0;
        }

        .hms-nav-group-icon {
            color: rgba(27, 79, 114, 0.45) !important;
            font-size: .91rem;
            width: 1rem;
            text-align: center;
            flex-shrink: 0;
        }

        .hms-nav-group-toggle .hms-nav-chevron {
            color: rgba(27, 79, 114, 0.35) !important;
            font-size: .68rem;
            flex-shrink: 0;
        }

        /* Small uppercase category label */
        .hms-nav-group-toggle .hms-nav-section-label {
            font-size: 1rem !important;
            font-weight: 800 !important;
            letter-spacing: .1em !important;
            text-transform: uppercase;
            color: rgba(27, 79, 114, 0.48) !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Dropdown items under the section: slightly smaller text */
        .hms-nav-group-items .hms-nav-item {
            font-size: .875rem;
        }

        .hms-nav-group-items .hms-nav-item i {
            font-size: .95rem;
        }

        .hms-nav-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            color: var(--shell-secondary) !important;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 999px;
            padding: .62rem 1rem;
            background: transparent;
            border: none;
            position: relative;
            transition: background 160ms ease, color 160ms ease;
        }

        .hms-nav-item i {
            color: var(--shell-secondary) !important;
            font-size: 1.02rem;
            width: 1.15rem;
            text-align: center;
            flex-shrink: 0;
        }

        .hms-nav-item:hover {
            background: var(--shell-s2-08) !important;
            color: var(--shell-secondary) !important;
            text-decoration: none !important;
        }

        .hms-nav-item.active {
            background: var(--shell-secondary) !important;
            color: #ffffff !important;
            box-shadow: none;
        }

        .hms-nav-item.active i {
            color: #ffffff !important;
        }

        /* Sidebar footer logout: keep it clean in same palette */
        .hms-sidebar-footer {
            border-top: none !important;
        }

        .hms-sidebar-logout {
            color: var(--shell-secondary) !important;
            font-weight: 700;
            font-size: .875rem;
            border-radius: 999px;
            border: none;
            background: var(--shell-s2-06);
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
            z-index: 1080;
        }

        .black-menu-bar .dropdown::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            height: 8px;
        }

        /* Hover (desktop) + Bootstrap .show (click / mobile) */
        .black-menu-bar .dropdown:hover>.dropdown-menu,
        .black-menu-bar .dropdown>.dropdown-menu.show {
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
            position: absolute;
            z-index: 1081;
        }

        .dropdown-menu .dropend:hover>.dropdown-menu,
        .dropdown-menu .dropend.show>.dropdown-menu {
            display: block;
        }

        .black-menu-bar .dropdown-menu .dropdown-menu {
            display: none;
        }

        .black-menu-bar .dropdown-menu .dropend:hover>.dropdown-menu,
        .black-menu-bar .dropdown-menu .dropend.show>.dropdown-menu {
            display: block;
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
                background: #1B4F72;
                width: 100%;
                padding: 10px 20px;
                display: flex;
                gap: 20px;
                color: white;
                align-items: center;
                flex-wrap: wrap;
                overflow: visible;
            }

            .black-menu-bar a,
            .black-menu-bar .dropdown>a {
                color: white;
                text-decoration: none;
                white-space: nowrap;
                display: inline-flex;
                align-items: center;
                gap: .4rem;
                flex-shrink: 0;
                font-size: .9rem;
                font-weight: 600;
            }

            .black-menu-bar .dropdown-toggle::after {
                margin-left: .35rem;
            }

            @media (max-width: 768px) {
                .hms-navbar {
                    padding: 8px 12px !important;
                }

                .top-header {
                    margin-bottom: 8px;
                    gap: .75rem;
                }

                .top-header .fs-4 {
                    font-size: 1rem !important;
                    max-width: 42vw;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                .top-header img {
                    height: 32px !important;
                    margin-right: 8px !important;
                }

                .top-header .user-info {
                    display: none !important;
                }

                .black-menu-bar {
                    padding: 8px 12px;
                    gap: 14px;
                    overflow: visible;
                }
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
    <script>
        (function () {
            try {
                // Collapsed mini-rail is desktop-only; ignore stored state on phones
                if (window.matchMedia('(min-width: 769px)').matches
                    && localStorage.getItem('hms_sidebar_collapsed') === '1') {
                    document.body.classList.add('hms-sidebar-collapsed');
                }
            } catch (e) { }
        })();
    </script>

    {{-- Sidebar toggle & collapsible groups — kept early (before the jQuery/Select2/
    DataTables/SweetAlert2 CDN
    <script> tags further down) since this is plain
    vanilla JS with zero dependency on them.If any of those CDN scripts are slow
    or blocked, they hold up every later inline script on the page; this one used
    to sit after them, so the collapse button could silently stop responding
    whenever that happened. --}}
        <script>
            document.addEventListener('DOMContentLoaded', function () {
            var sidebar = document.getElementById('hmsSidebar');
            var backdrop = document.getElementById('hmsSidebarBackdrop');
            var toggle = document.getElementById('hmsSidebarToggle');
            var collapseBtn = document.getElementById('hmsSidebarCollapseBtn');

            // Desktop collapse / expand (mini icon-rail)
            if (collapseBtn) {
                collapseBtn.addEventListener('click', function () {
                    var collapsed = document.body.classList.toggle('hms-sidebar-collapsed');
                    document.body.classList.remove('hms-sidebar-hover-expand');
                    try {
                        localStorage.setItem('hms_sidebar_collapsed', collapsed ? '1' : '0');
                    } catch (e) { }
                });
            }

            // Hover peek expand — only while permanently collapsed (desktop)
            if (sidebar) {
                var hoverLeaveTimer = null;
            var canHoverPeek = function () {
                    return document.body.classList.contains('hms-sidebar-collapsed')
            && window.matchMedia('(min-width: 769px)').matches;
                };

            sidebar.addEventListener('mouseenter', function () {
                    if (!canHoverPeek()) { return; }
            clearTimeout(hoverLeaveTimer);
            document.body.classList.add('hms-sidebar-hover-expand');
                });

            sidebar.addEventListener('mouseleave', function () {
                clearTimeout(hoverLeaveTimer);
            hoverLeaveTimer = setTimeout(function () {
                document.body.classList.remove('hms-sidebar-hover-expand');
                    }, 140);
                });
            }

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

    {{-- Grace Period Warning Banner removed --}}

    {{-- ================================================
    Top Navigation Bar
    ================================================ --}}
    @if(auth('hospital_user')->user()?->role?->slug === 'doctor')
        <nav class="hms-navbar">
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
            <div class="black-menu-bar d-flex align-items-center doctor-black-menu">

                {{-- Dashboard --}}
                <a href="{{ route('hospital.dashboard', ['slug' => request()->route('slug')]) }}"
                    class="text-white text-decoration-none"><i class="bi bi-house-door-fill"></i>
                    <span>Dashboards</span></a>

                {{-- Diagnosis Master Dropdown --}}
                <div class="dropdown">
                    <a href="#" class="text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown"
                        data-bs-auto-close="outside" aria-expanded="false" role="button">
                        <i class="bi bi-info-circle-fill"></i> <span>Diagnosis Master</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ url($slug . '/masters/detail/chief-complaints') }}">C/O</a>
                        </li>
                        <li><a class="dropdown-item" href="{{ url($slug . '/masters/detail/kcos') }}">KCO</a></li>
                        <li><a class="dropdown-item" href="{{ url($slug . '/masters/detail/hno') }}">H/O</a></li>
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
                    <a href="#" class="text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown"
                        data-bs-auto-close="outside" aria-expanded="false" role="button">
                        <i class="bi bi-capsule"></i> <span>Medicine Master</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ url($slug . '/medicine-dosages') }}">Dosage</a></li>
                        <li><a class="dropdown-item" href="{{ url($slug . '/medicine-types') }}">Medicine Type</a></li>
                        <li><a class="dropdown-item" href="{{ url($slug . '/medicine-categories') }}">Medicine Category</a>
                        </li>
                        <li><a class="dropdown-item" href="{{ url($slug . '/medicine-routes') }}">Mode</a>
                        </li>
                        <li><a class="dropdown-item" href="{{ url($slug . '/medicines') }}">Add Medicine</a></li>
                        <li><a class="dropdown-item" href="{{ url($slug . '/medicine-groups') }}">Add Group</a></li>
                    </ul>
                </div>

                {{-- History --}}
                <a href="{{ route('hospital.doctor.history', ['slug' => $slug ?? request()->route('slug')]) }}"
                    class="text-white text-decoration-none">
                    <i class="bi bi-clock-history"></i> <span>History</span>
                </a>
            </div>

        </nav>
    @endif

    <div class="hms-layout">

        {{-- Mobile backdrop --}}
        <div class="hms-sidebar-backdrop" id="hmsSidebarBackdrop"></div>

        {{-- ============================================
        Sidebar Navigation
        ============================================ --}}
        @if(auth('hospital_user')->user()?->role?->slug !== 'doctor')
        <aside class="hms-sidebar" id="hmsSidebar">
            <button type="button" class="hms-sidebar-collapse-btn" id="hmsSidebarCollapseBtn"
                aria-label="Collapse sidebar">
                <i class="bi bi-chevron-left"></i>
            </button>
            <div class="premium-sidebar-brand">
                <a href="{{ route('hospital.dashboard', ['slug' => request()->route('slug')]) }}"
                    class="premium-sidebar-brand-link" aria-label="Go to dashboard">
                    <span class="sidebar-brand-mark">
                        @php
                            $sidebarStyle = $hospitalLogoSidebarStyle ?? 'white';
                            $useBlurBox = !empty($hospitalLogo) && $sidebarStyle === 'original_blur';
                            $isPlatformSidebarLogo = empty($hospitalLogo);
                            $sidebarLogoFilter = (!empty($hospitalLogo) && $sidebarStyle === 'white')
                                ? 'brightness(0) invert(1)'
                                : 'none';
                            $sidebarLogoClass = 'sidebar-logo' . ($isPlatformSidebarLogo ? ' platform-logo-on-dark' : '');
                        @endphp
                        @if($useBlurBox)
                            <span
                                style="display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;background:rgba(255,255,255,.22);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border-radius:10px;">
                                <img src="{{ $hospitalSidebarLogoUrl }}" alt="{{ $hospitalName }} Logo"
                                    class="{{ $sidebarLogoClass }}" style="filter:none!important;" loading="lazy"
                                    decoding="async">
                            </span>
                        @else
                            <img src="{{ $hospitalSidebarLogoUrl }}" alt="{{ $hospitalName }} Logo"
                                class="{{ $sidebarLogoClass }}" style="filter:{{ $sidebarLogoFilter }}!important;"
                                loading="lazy" decoding="async">
                        @endif
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
                        <span class="hms-nav-group-label-wrap">
                            <i class="bi bi-person-lines-fill hms-nav-group-icon"></i>
                            <span class="hms-nav-section-label" style="padding:0;margin:0">OPD</span>
                        </span>
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
                            <span class="hms-nav-group-label-wrap">
                                <i class="bi bi-heart-pulse-fill hms-nav-group-icon"></i>
                                <span class="hms-nav-section-label" style="padding:0;margin:0">Clinical</span>
                            </span>
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
                            || $permSvc->can('ot.appointment.view')
                            || $permSvc->can('ot.counselling.fill')
                            || $permSvc->can('ot.payment.record')
                            || $permSvc->can('ot.ward.entry')
                            || $permSvc->can('ot.surgery.record')
                            || $permSvc->can('ot.lens.record')
                            || $permSvc->can('ot.billing.manage');
                    @endphp

                    @if($showOt)
                    <div class="hms-nav-divider"></div>
                    <div class="hms-nav-group-toggle" data-target="nav-ot">
                        <span class="hms-nav-group-label-wrap">
                            <i class="bi bi-hospital-fill hms-nav-group-icon"></i>
                            <span class="hms-nav-section-label" style="padding:0;margin:0">OT / Surgery</span>
                        </span>
                        <i class="bi bi-chevron-down hms-nav-chevron"></i>
                    </div>
                    <div class="hms-nav-group-items" id="nav-ot">
                        @haspermission('ot.appointment.view')
                        <a href="{{ route('hospital.ot.appointments.index', ['slug' => request()->route('slug')]) }}"
                            class="hms-nav-item {{ request()->routeIs('hospital.ot.appointments.*') ? 'active' : '' }}">
                            <i class="bi bi-calendar2-week"></i>
                            <span>OT Appointments</span>
                        </a>
                        @endhaspermission

                        @haspermission('ot.counselling.fill')
                        <a href="{{ route('hospital.ot.counsellor.dashboard', ['slug' => request()->route('slug')]) }}"
                            class="hms-nav-item {{ request()->routeIs('hospital.ot.counsellor.*') ? 'active' : '' }}">
                            <i class="bi bi-chat-left-heart"></i>
                            <span>OT Counselling</span>
                        </a>
                        @endhaspermission

                        @haspermission('ot.payment.record')
                        <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => request()->route('slug')]) }}"
                            class="hms-nav-item {{ request()->routeIs('hospital.ot.accountant.*') || request()->routeIs('hospital.ot.payments.*') || request()->routeIs('hospital.ot.refunds.*') ? 'active' : '' }}">
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

                        @php
                            // OT Assistant now absorbs the old OT Doctor role's surgery
                            // recording alongside its own lens-recording job (docs/tulsi.md §5).
                            $showAssistant = $permSvc->can('ot.lens.record')
                                || $permSvc->can('ot.lens.implant')
                                || $permSvc->can('ot.surgery.ready')
                                || $permSvc->can('ot.surgery.record');
                        @endphp
                        @if($showAssistant)
                            <a href="{{ route('hospital.ot.assistant.dashboard', ['slug' => request()->route('slug')]) }}"
                                class="hms-nav-item {{ request()->routeIs('hospital.ot.assistant.*') || request()->routeIs('hospital.ot.surgery.*') ? 'active' : '' }}">
                                <i class="bi bi-eyeglasses"></i>
                                <span>OT Assistant Dashboard</span>
                            </a>
                        @endif

                        {{-- Discharge Counter owns this desk (not Accountant). --}}
                        @haspermission('ot.discharge.generate')
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
                            <span class="hms-nav-group-label-wrap">
                                <i class="bi bi-bar-chart-line-fill hms-nav-group-icon"></i>
                                <span class="hms-nav-section-label" style="padding:0;margin:0">Reports</span>
                            </span>
                            <i class="bi bi-chevron-down hms-nav-chevron"></i>
                        </div>
                        <div class="hms-nav-group-items" id="nav-reports">
                            <a href="{{ route('hospital.reports.index', ['slug' => request()->route('slug')]) }}"
                                class="hms-nav-item {{ request()->routeIs('hospital.reports.index') ? 'active' : '' }}">
                                <i class="bi bi-bar-chart-line-fill"></i>
                                <span>OPD Reports</span>
                            </a>
                            <a href="{{ route('hospital.reports.ot.index', ['slug' => request()->route('slug')]) }}"
                                class="hms-nav-item {{ request()->routeIs('hospital.reports.ot.*') ? 'active' : '' }}">
                                <i class="bi bi-file-earmark-bar-graph"></i>
                                <span>OT Reports</span>
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
                        <span class="hms-nav-group-label-wrap">
                            <i class="bi bi-capsule hms-nav-group-icon"></i>
                            <span class="hms-nav-section-label" style="padding:0;margin:0">Medicines</span>
                        </span>
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
                            <span>Mode</span>
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
                        <span class="hms-nav-group-label-wrap">
                            <i class="bi bi-gear-fill hms-nav-group-icon"></i>
                            <span class="hms-nav-section-label" style="padding:0;margin:0">Config</span>
                        </span>
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

            @if(auth('hospital_user')->user()?->role?->slug !== 'doctor')
                @php
                    $contentTopbarUser = auth('hospital_user')->user();
                    $contentTopbarSlug = request()->route('slug');
                    $isReceptionistUser = in_array($contentTopbarUser?->role?->slug, ['receptionist', 'receptionist_opd'], true);
                    $showReceptionRegisterActions = $isReceptionistUser && request()->routeIs('hospital.dashboard');
                    $permSvcTop = app(\App\Services\Auth\RolePermissionService::class);
                @endphp

                {{-- In-content top bar: search + profile menu (design refresh) --}}
                <div class="hms-content-topbar">
                    <button class="hms-sidebar-toggle" id="hmsSidebarToggle" aria-label="Toggle sidebar">
                        <i class="bi bi-list"></i>
                    </button>

                    @if($permSvcTop->can('opd.patient.view'))
                        <form class="hms-search-form"
                            action="{{ route('hospital.patients.index', ['slug' => $contentTopbarSlug]) }}" method="GET"
                            role="search">
                            <i class="bi bi-search"></i>
                            <input type="text" name="q" id="hmsGlobalSearch" class="hms-search-input"
                                placeholder="Search patients (Ctrl+/)" autocomplete="off">
                        </form>
                    @else
                        <div class="hms-search-form-spacer"></div>
                    @endif

                    <div class="hms-content-topbar-right">
                        @if($showReceptionRegisterActions)
                            <div class="reception-register-actions">
                                @if($permSvcTop->can('opd.patient.register'))
                                    <a href="{{ route('hospital.patients.create', ['slug' => $contentTopbarSlug]) }}"
                                        class="reception-register-btn">
                                        <i class="bi bi-person-plus"></i>
                                        <span>WALK IN</span>
                                    </a>
                                @endif
                                @if($permSvcTop->can('opd.patient.register_phone'))
                                    <a href="{{ route('hospital.patients.create-phone', ['slug' => $contentTopbarSlug]) }}"
                                        class="reception-register-btn">
                                        <i class="bi bi-telephone"></i>
                                        <span>PHONE</span>
                                    </a>
                                @endif
                                @if($permSvcTop->can('ot.appointment.create'))
                                    <a href="{{ route('hospital.ot.appointments.create', ['slug' => $contentTopbarSlug]) }}"
                                        class="reception-register-btn">
                                        <i class="bi bi-calendar2-plus"></i>
                                        <span>OT APPOINTMENT</span>
                                    </a>
                                @endif
                            </div>
                        @endif

                        <div class="dropdown hms-content-profile">
                            <button type="button" class="hms-content-user-info dropdown-toggle" data-bs-toggle="dropdown"
                                aria-expanded="false" title="Account menu" aria-label="Account menu">
                                <strong>{{ $hospitalName }}</strong>
                                <small>{{ $contentTopbarUser?->role?->name ?? 'Hospital Staff' }}</small>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end hms-content-profile-menu">
                                <div class="hms-content-profile-header">
                                    <span class="hms-content-profile-header-logo">
                                        <img src="{{ $hospitalLogoUrl }}" alt="{{ $hospitalName }}" loading="lazy"
                                            decoding="async">
                                    </span>
                                    <div>
                                        <div class="hms-content-profile-name">{{ $contentTopbarUser?->name }}</div>
                                        <div class="hms-content-profile-role">
                                            {{ $contentTopbarUser?->role?->name ?? 'Hospital Staff' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                @if($contentTopbarUser?->role?->is_super)
                                    <a class="dropdown-item"
                                        href="{{ route('hospital.patients.index', ['slug' => $contentTopbarSlug]) }}">
                                        <i class="bi bi-person-badge me-2"></i> Doctor Workspace
                                    </a>
                                    <a class="dropdown-item"
                                        href="{{ route('hospital.dashboard', ['slug' => $contentTopbarSlug]) }}">
                                        <i class="bi bi-clipboard2-pulse me-2"></i> Reception Workspace
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item"
                                        href="{{ route('hospital.settings.index', ['slug' => $contentTopbarSlug]) }}">
                                        <i class="bi bi-gear me-2"></i> Settings
                                    </a>
                                @else
                                    <a class="dropdown-item"
                                        href="{{ route('hospital.profile.show', ['slug' => $contentTopbarSlug]) }}">
                                        <i class="bi bi-person-circle me-2"></i> My Profile
                                    </a>
                                @endif
                            </div>
                        </div>

                        <form method="POST" action="{{ route('hospital.logout', ['slug' => $contentTopbarSlug]) }}">
                            @csrf
                            <button type="submit" class="hms-content-logout-btn" title="Log out" aria-label="Log out">
                                <i class="bi bi-box-arrow-right"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @endif

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
                        @yield('page-header')
                    </h1>
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
    <script src="{{ asset('js/intl-phone-input.js') }}"></script>
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
            document.querySelectorAll('.doctor-black-menu > .dropdown > .dropdown-toggle').forEach(function (toggle) {
                toggle.addEventListener('click', function (event) {
                    event.preventDefault();
                });

                if (window.bootstrap && window.bootstrap.Dropdown) {
                    bootstrap.Dropdown.getOrCreateInstance(toggle, {
                        autoClose: 'outside',
                        popperConfig: {
                            strategy: 'fixed',
                            modifiers: [{ name: 'preventOverflow', options: { boundary: 'viewport' } }]
                        }
                    });
                }
            });

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

    {{-- Global search box: Ctrl+/ focuses it from anywhere --}}
    <script>
        document.addEventListener('keydown', function (event) {
            if (event.ctrlKey && event.key === '/') {
                var searchInput = document.getElementById('hmsGlobalSearch');
        if (searchInput) {
            event.preventDefault();
        searchInput.focus();
        searchInput.select();
                }
            }
        });
    </script>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    {{-- jQuery (Select2 dependency) --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>

    {{-- Select2 --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        /**
         * Global Select2 for hospital CRM dropdowns.
         * Skips: already inited, .no-select2, .form-select-sm (dense tables), DataTables length.
         */
        window.initHmsSelect2 = function (root) {
            if (typeof jQuery === 'undefined' || !jQuery.fn.select2) {
                return;
            }

        var $root = root ? jQuery(root) : jQuery(document);

        $root.find('select.form-select, select.hms-select, select.js-select2, select.select2, select.clinical-input').each(function () {
                var $el = jQuery(this);

        if ($el.hasClass('select2-hidden-accessible')) {
                    return;
                }
        if ($el.hasClass('no-select2') || $el.hasClass('form-select-sm')) {
                    return;
                }
        if ($el.closest('.no-select2-scope').length) {
                    return;
                }
        if ($el.closest('.dataTables_length').length) {
                    return;
                }
        // Native multi-select without explicit opt-in can be awkward; allow if already classed select2/js-select2
        if ($el.prop('multiple') && !$el.hasClass('select2') && !$el.hasClass('js-select2') && !$el.data('select2Multiple')) {
                    return;
                }

        var $modal = $el.closest('.modal');
        var placeholder = $el.data('placeholder')
        || jQuery.trim($el.find('option[value=""]').first().text())
        || 'Select...';
                var allowClear = !$el.prop('required') && $el.find('option[value=""]').length > 0;

        var options = {
            width: '100%',
        placeholder: placeholder,
        allowClear: allowClear,
        dropdownAutoWidth: false
                };

        if ($modal.length) {
            options.dropdownParent = $modal;
                }

        $el.select2(options);
            });
        };
    </script>

    {{-- Flatpickr --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        /**
         * Shared date-range filter (flatpickr range).
         * Use on a text input:
         *   data-hms-date-range
         *   data-range-mode="split|combined"   default split
         *   data-start-name / data-end-name    default start_date / end_date
         *   data-start-value / data-end-value
         *   data-auto-submit="1|0"             default 1 (submit form when range complete)
         * combined mode posts the visible input value as "YYYY-MM-DD to YYYY-MM-DD"
         */
        window.initHmsDateRange = function (root) {
            if (typeof flatpickr === 'undefined') {
                return;
            }

        var scope = root || document;

        scope.querySelectorAll('[data-hms-date-range]').forEach(function (el) {
                if (el._hmsDateRangeInit) {
                    return;
                }
        el._hmsDateRangeInit = true;

        var mode = el.getAttribute('data-range-mode') || 'split';
        var startName = el.getAttribute('data-start-name') || 'start_date';
        var endName = el.getAttribute('data-end-name') || 'end_date';
        var startVal = (el.getAttribute('data-start-value') || '').trim();
        var endVal = (el.getAttribute('data-end-value') || '').trim();
        var autoSubmit = el.getAttribute('data-auto-submit') !== '0';
        var form = el.closest('form');
        var isSubmitting = false;

        // Parse existing combined value if present
        if (mode === 'combined' && el.value && el.value.indexOf(' to ') !== -1) {
                    var parts = el.value.split(' to ');
        startVal = (parts[0] || '').trim();
        endVal = (parts[1] || '').trim();
                }

        function ensureHidden(name, value) {
                    if (!form || !name) {
                        return null;
                    }
        // Drop any non-hidden inputs with this name so only our values post
        form.querySelectorAll('input[name="' + name + '"]').forEach(function (node) {
                        if (node !== el && node.type !== 'hidden') {
            node.remove();
                        }
                    });
        var existing = form.querySelector('input[type="hidden"][name="' + name + '"]');
        if (existing) {
                        if (value !== undefined && value !== null && value !== '') {
            existing.value = value;
                        }
        return existing;
                    }
        var inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = name;
        inp.value = value || '';
        form.appendChild(inp);
        return inp;
                }

        var startInput = null;
        var endInput = null;
        if (mode === 'split') {
            el.removeAttribute('name');
        startInput = ensureHidden(startName, startVal);
        endInput = ensureHidden(endName, endVal || startVal);
                }

        var defaultDates = null;
        if (startVal && endVal) {
            defaultDates = [startVal, endVal];
                } else if (startVal) {
            defaultDates = [startVal, startVal];
                }

        function applyDates(selectedDates, instance) {
                    if (!selectedDates || !selectedDates.length) {
                        if (mode === 'combined') {
            el.value = '';
                        } else {
                            if (startInput) startInput.value = '';
        if (endInput) endInput.value = '';
                        }
        return false;
                    }

        var a = instance.formatDate(selectedDates[0], 'Y-m-d');
                    var b = selectedDates.length > 1
        ? instance.formatDate(selectedDates[1], 'Y-m-d')
        : a;

        if (mode === 'combined') {
            el.value = a === b ? a : (a + ' to ' + b);
                    } else {
                        if (startInput) startInput.value = a;
        if (endInput) endInput.value = b;
                    }
                    return selectedDates.length >= 2 || selectedDates.length === 1;
                }

        function submitIfReady(selectedDates) {
                    if (!autoSubmit || !form || isSubmitting) {
                        return;
                    }
                    // Complete when user picked end date, or confirmed single day on close
                    if (selectedDates.length >= 2 || selectedDates.length === 1) {
            isSubmitting = true;
        setTimeout(function () {
                            if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
                            } else {
            form.submit();
                            }
                        }, 30);
                    }
                }

        flatpickr(el, {
            mode: 'range',
        dateFormat: 'Y-m-d',
        defaultDate: defaultDates,
        allowInput: false,
        clickOpens: true,
        onChange: function (selectedDates, _dateStr, instance) {
            applyDates(selectedDates, instance);
        // Auto-filter as soon as end date is chosen
        if (selectedDates.length === 2) {
            submitIfReady(selectedDates);
                        }
                    },
        onClose: function (selectedDates, _dateStr, instance) {
                        // Single day: start only → treat as that day and filter
                        if (selectedDates.length === 1) {
            applyDates(selectedDates, instance);
        submitIfReady(selectedDates);
                        }
                    }
                });
            });
        };
    </script>

    {{-- DataTables --}}
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        window.initHmsDataTables = function (root) {
            if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) {
                return;
            }

        var $root = root ? jQuery(root) : jQuery(document);

        $root.find('table.js-datatable').each(function () {
                var table = this;
        var $table = jQuery(table);

        if (jQuery.fn.DataTable.isDataTable(table)) {
                    return;
                }

        // Placeholder empty rows (colspan) break DataTables column count
        $table.find('tbody tr').each(function () {
                    var $cells = jQuery(this).children('td');
        if ($cells.length === 1 && $cells.first().attr('colspan')) {
            jQuery(this).remove();
                    }
                });

        var headers = $table.find('thead th').map(function () {
                    return jQuery(this).text().replace(/\s+/g, ' ').trim().toLowerCase();
                }).get();

        var nonInteractive = [];
        headers.forEach(function (label, index) {
                    if (label === '#' || label === 'actions' || label === 'favourite' || label === 'action') {
            nonInteractive.push(index);
                    }
                });

        $table.DataTable({
            pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                    order: headers[0] === '#' && headers.length > 1 ? [[1, 'asc']] : [[0, 'asc']],
        autoWidth: false,
        columnDefs: nonInteractive.length
        ? [{orderable: false, searchable: false, targets: nonInteractive }]
        : [],
        language: {
            search: 'Search:',
        lengthMenu: 'Show _MENU_',
        info: 'Showing _START_ to _END_ of _TOTAL_',
        infoEmpty: 'Showing 0 entries',
        emptyTable: 'No records found.',
        zeroRecords: 'No matching records.'
                    },
        drawCallback: function () {
                        var api = this.api();
        if (headers[0] === '#') {
                            var info = api.page.info();
        api.column(0, {search: 'applied', order: 'applied', page: 'current' })
        .nodes()
        .each(function (cell, i) {
            cell.innerHTML = info.start + i + 1;
                                });
                        }
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
                        }
                    }
                });
            });
        };

        jQuery(function () {
            window.initHmsDataTables();
        });
    </script>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
        {icon: 'success', title: @json(session('success')) },
        {icon: 'error', title: @json(session('error')) },
        {icon: 'warning', title: @json(session('warning')) },
        {icon: 'info', title: @json(session('info')) }
        ].filter(function (message) {
                return Boolean(message.title);
            });

        flashMessages.forEach(function (message) {
            Toast.fire(message);
            });
        });
    </script>

    <script>
        window.HMS_CURRENCY = {
            code: @json(currency_code()),
        symbol: @json(currency_symbol())
        };
        var currencyCode = window.HMS_CURRENCY.code;
        var currencySymbol = window.HMS_CURRENCY.symbol;
    </script>

    @stack('modals')
    @stack('scripts')

    {{-- Global Select2 + date-range AFTER page scripts so page-specific inits win; then fill the rest --}}
    <script>
        jQuery(function () {
            window.initHmsSelect2();
        if (typeof window.initHmsDateRange === 'function') {
            window.initHmsDateRange();
            }
        });

        jQuery(document).on('shown.bs.modal', '.modal', function () {
            window.initHmsSelect2(this);
        if (typeof window.initHmsDateRange === 'function') {
            window.initHmsDateRange(this);
            }
        });
    </script>
</body>

</html>