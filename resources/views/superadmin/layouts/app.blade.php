<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SuperAdmin') — EYENOSIS</title>
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

    {{-- DataTables (Bootstrap 5) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" />

    {{-- SweetAlert2 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    {{-- SuperAdmin shell styles in superadmin.css; light overrides only --}}
    <style>
        body.sa-body {
            background: #eef4fb !important;
        }

        .sa-main .hms-card,
        .sa-main .sa-premium-card,
        .sa-main .hms-stat-card {
            box-shadow: 0 8px 24px rgba(15, 79, 134, 0.05) !important;
        }

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

        @media (max-width: 768px) {
            .hms-sidebar-toggle {
                display: inline-grid !important;
            }
        }
    </style>

    @stack('styles')
</head>

<body class="sa-body">

    {{-- Mobile backdrop --}}
    <div class="hms-sidebar-backdrop" id="hmsSidebarBackdrop"></div>

    {{-- ============================================
    Sidebar (white, brand blue active)
    ============================================ --}}
    <aside class="sa-sidebar" id="hmsSidebar">
        <button type="button" class="sa-sidebar-rail-btn" id="saSidebarCollapse" aria-label="Collapse or expand sidebar"
            title="Collapse sidebar">
            <i class="bi bi-chevron-left" id="saSidebarCollapseIcon"></i>
        </button>

        <a href="{{ route('superadmin.dashboard') }}" class="sa-sidebar-brand">
            <span class="sa-sidebar-brand-mark">
                <img src="{{ platform_logo_url() }}" alt="Eye HMS">
            </span>
            <div class="sa-sidebar-brand-text">
                <small>Platform Admin</small>
            </div>
        </a>

        <nav class="hms-sidenav">
            <a href="{{ route('superadmin.dashboard') }}"
                class="hms-nav-item {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>

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

            <div class="hms-nav-divider"></div>
            <div class="hms-nav-group-toggle {{ request()->routeIs('superadmin.locations.*', 'superadmin.timezones.*', 'superadmin.medicine-master.*') ? '' : 'collapsed' }}"
                data-target="sa-nav-masters">
                <span class="hms-nav-section-label" style="padding:0;margin:0">Masters</span>
                <i class="bi bi-chevron-down hms-nav-chevron"></i>
            </div>
            <div class="hms-nav-group-items {{ request()->routeIs('superadmin.locations.*', 'superadmin.timezones.*', 'superadmin.medicine-master.*') ? '' : 'collapsed' }}"
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
                <a href="{{ route('superadmin.medicine-master.index') }}"
                    class="hms-nav-item {{ request()->routeIs('superadmin.medicine-master.*') ? 'active' : '' }}">
                    <i class="bi bi-capsule"></i>
                    <span>Medicine Master</span>
                </a>
            </div>

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
    </aside>

    <div class="sa-layout">
        {{-- Floating topbar: search + logout --}}
        <header class="sa-topbar">
            <div class="sa-topbar-left">
                <button class="hms-sidebar-toggle" id="hmsSidebarToggle" type="button" aria-label="Toggle sidebar">
                    <i class="bi bi-list"></i>
                </button>
                <label class="sa-topbar-search" for="saTopSearch">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="saTopSearch" name="sa_search" placeholder="Search (Ctrl+/)"
                        autocomplete="off">
                </label>
            </div>
            <div class="sa-topbar-right">
                <div class="sa-topbar-user">
                    <strong>{{ auth('superadmin')->user()?->name ?? 'SuperAdmin' }}</strong>
                    <small>{{ auth('superadmin')->user()?->email ?? 'Platform admin' }}</small>
                </div>
                <form method="POST" action="{{ route('superadmin.logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="sa-topbar-logout" title="Logout" aria-label="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </form>
            </div>
        </header>

        <main class="sa-main">
            @hasSection('page-header')
                <div class="sa-page-header">
                    <h1>@yield('page-header')</h1>
                    @hasSection('page-actions')
                        <div class="sa-page-actions">@yield('page-actions')</div>
                    @endif
                </div>
            @endif

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

    {{-- DataTables --}}
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        /**
         * Turns any table.js-datatable into a searchable/sortable/paginated
         * DataTable. Same convention as the hospital-panel layout.
         */
        window.initHmsDataTables = function (root) {
            if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) {
                return;
            }

            var $root = root ? jQuery(root) : jQuery(document);

            $root.find('table.js-datatable').each(function () {
                var $table = jQuery(this);

                if (jQuery.fn.DataTable.isDataTable(this)) {
                    return;
                }

                var headers = $table.find('thead th').map(function () {
                    return jQuery(this).text().replace(/\s+/g, ' ').trim().toLowerCase();
                }).get();

                var nonInteractive = [];
                headers.forEach(function (label, index) {
                    if (label === '#' || label === 'action' || label === 'actions') {
                        nonInteractive.push(index);
                    }
                });

                $table.DataTable({
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
                    order: [],
                    autoWidth: false,
                    columnDefs: nonInteractive.length
                        ? [{ orderable: false, searchable: false, targets: nonInteractive }]
                        : [],
                    language: {
                        search: 'Search:',
                        lengthMenu: 'Show _MENU_',
                        info: 'Showing _START_ to _END_ of _TOTAL_',
                        infoEmpty: 'Showing 0 entries',
                        emptyTable: 'No records found.',
                        zeroRecords: 'No matching records.'
                    }
                });
            });
        };

        jQuery(function () {
            window.initHmsDataTables();
        });
    </script>

    {{-- Select2 --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    {{-- Flatpickr --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        /**
         * Shared date-range filter (flatpickr range) — SuperAdmin shell.
         * Same API as hospital layout: [data-hms-date-range]
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

                if (mode === 'combined' && el.value && el.value.indexOf(' to ') !== -1) {
                    var parts = el.value.split(' to ');
                    startVal = (parts[0] || '').trim();
                    endVal = (parts[1] || '').trim();
                }

                function ensureHidden(name, value) {
                    if (!form || !name) {
                        return null;
                    }
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
                        return;
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
                }

                function submitIfReady(selectedDates) {
                    if (!autoSubmit || !form || isSubmitting) {
                        return;
                    }
                    if (selectedDates.length >= 1) {
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
                    onChange: function (selectedDates, _dateStr, instance) {
                        applyDates(selectedDates, instance);
                        if (selectedDates.length === 2) {
                            submitIfReady(selectedDates);
                        }
                    },
                    onClose: function (selectedDates, _dateStr, instance) {
                        if (selectedDates.length === 1) {
                            applyDates(selectedDates, instance);
                            submitIfReady(selectedDates);
                        }
                    }
                });
            });
        };

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.initHmsDateRange === 'function') {
                window.initHmsDateRange();
            }
        });
    </script>

    {{-- Sidebar toggle & collapsible groups --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var sidebar = document.getElementById('hmsSidebar');
            var backdrop = document.getElementById('hmsSidebarBackdrop');
            var toggle = document.getElementById('hmsSidebarToggle');
            var collapseBtn = document.getElementById('saSidebarCollapse');
            var collapseIcon = document.getElementById('saSidebarCollapseIcon');
            var COLLAPSE_KEY = 'sa_sidebar_collapsed';

            function setCollapsed(on) {
                if (!sidebar) return;
                sidebar.classList.toggle('is-collapsed', on);
                document.documentElement.classList.toggle('sa-sidebar-is-collapsed', on);
                document.documentElement.style.setProperty(
                    '--sa-sidebar-w',
                    on ? '78px' : '260px'
                );
                if (collapseIcon) {
                    collapseIcon.className = on ? 'bi bi-chevron-right' : 'bi bi-chevron-left';
                }
                if (collapseBtn) {
                    collapseBtn.title = on ? 'Expand sidebar' : 'Collapse sidebar';
                    collapseBtn.setAttribute('aria-label', on ? 'Expand sidebar' : 'Collapse sidebar');
                    collapseBtn.setAttribute('aria-expanded', on ? 'false' : 'true');
                }
                try {
                    localStorage.setItem(COLLAPSE_KEY, on ? '1' : '0');
                } catch (e) { /* ignore */ }
            }

            /* Desktop collapse rail button (UI width only) */
            if (collapseBtn) {
                var saved = false;
                try {
                    saved = localStorage.getItem(COLLAPSE_KEY) === '1';
                } catch (e) { /* ignore */ }
                if (window.matchMedia && window.matchMedia('(min-width: 769px)').matches) {
                    setCollapsed(saved);
                }
                collapseBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (window.matchMedia && window.matchMedia('(max-width: 768px)').matches) {
                        return;
                    }
                    setCollapsed(!sidebar.classList.contains('is-collapsed'));
                });
            }

            /* Mobile drawer toggle (existing) */
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

            /* Search: Ctrl+/ focuses field (UI only, no data change) */
            var search = document.getElementById('saTopSearch');
            if (search) {
                document.addEventListener('keydown', function (e) {
                    if ((e.ctrlKey || e.metaKey) && e.key === '/') {
                        e.preventDefault();
                        search.focus();
                    }
                });
            }
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
    <script src="{{ asset('js/intl-phone-input.js') }}"></script>
</body>

</html>