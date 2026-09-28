{{--
  Shared styles + toggle script for dashboard inline desk panels
  (accountant-panel, ward-panel). Cards link with href="#<panelId>" data-acc-tab="<pane>".
--}}
@once
@push('styles')
    @include('hospital.ot.partials.desk-filter-styles')
    <style>
        .acc-tab-card {
            cursor: pointer;
        }

        .acc-tab-card.is-active {
            border-color: #1B4F72 !important;
            box-shadow: 0 0 0 2px rgba(27, 79, 114, .18), var(--dash-shadow-hover);
        }

        .acc-tab-card.is-active::after {
            content: "";
            position: absolute;
            left: 50%;
            bottom: 0;
            width: 36px;
            height: 4px;
            border-radius: 4px 4px 0 0;
            background: #1B4F72;
            transform: translateX(-50%);
        }

        .acc-panel {
            position: relative;
            z-index: 1;
            margin-bottom: 1.5rem;
            background: #fff;
            border: 1px solid rgba(27, 79, 114, .12);
            border-radius: 18px;
            box-shadow: var(--dash-shadow);
            overflow: hidden;
            scroll-margin-top: 90px;
        }

        .acc-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            padding: 1rem 1.25rem;
            background: #1B4F72;
            color: #fff;
        }

        .acc-panel-title {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .acc-panel-title h5 {
            font-weight: 800;
            color: #fff;
        }

        .acc-panel-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, .15);
            font-size: 1.1rem;
        }

        .acc-panel-sub {
            font-size: .8rem;
            font-weight: 600;
            color: rgba(255, 255, 255, .78);
        }

        .acc-open-page {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .45rem .9rem;
            border-radius: 10px;
            background: #fff;
            color: #1B4F72;
            font-size: .82rem;
            font-weight: 700;
            text-decoration: none;
        }

        .acc-open-page:hover {
            background: #EBF5FB;
            color: #154360;
        }

        .acc-pane {
            padding: 0 1.25rem 1rem;
        }

        .acc-pane[hidden] {
            display: none;
        }

        .acc-pane .dataTables_wrapper .dataTables_length,
        .acc-pane .dataTables_wrapper .dataTables_filter {
            padding: .9rem 0 .6rem;
            font-size: .85rem;
            color: rgba(27, 79, 114, .72);
        }

        .acc-pane .dataTables_wrapper .dataTables_info,
        .acc-pane .dataTables_wrapper .dataTables_paginate {
            padding: .6rem 0 0;
            font-size: .85rem;
            color: rgba(27, 79, 114, .72);
        }

        .acc-pane .dataTables_filter input,
        .acc-pane .dataTables_length select {
            border: 1px solid rgba(27, 79, 114, .18);
            border-radius: 8px;
            padding: .3rem .6rem;
            color: #1B4F72;
        }

        .acc-pane .dataTables_paginate .paginate_button.current {
            background: #1B4F72 !important;
            border-color: #1B4F72 !important;
            color: #fff !important;
            border-radius: 8px;
        }

        .acc-table {
            width: 100%;
            border-collapse: collapse;
        }

        .acc-table thead th {
            background: #EBF5FB;
            color: #1B4F72;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: .8rem .9rem;
            white-space: nowrap;
            border-bottom: 2px solid rgba(27, 79, 114, .12);
        }

        .acc-table tbody td {
            padding: .75rem .9rem;
            font-size: .88rem;
            color: #17324D;
            vertical-align: middle;
            border-bottom: 1px solid rgba(27, 79, 114, .08);
            white-space: nowrap;
        }

        .acc-table tbody tr:hover td {
            background: #F4F9FD;
        }

        .acc-patient {
            display: flex;
            align-items: center;
            gap: .6rem;
        }

        .acc-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #EBF5FB;
            color: #1B4F72;
            font-weight: 800;
            font-size: .8rem;
            flex-shrink: 0;
        }

        .acc-name {
            font-weight: 700;
            color: #1B4F72;
        }

        .acc-code,
        .acc-sub {
            font-size: .74rem;
            color: #7F8C8D;
            font-weight: 600;
        }

        .acc-sub {
            margin-top: .2rem;
        }

        .acc-amount {
            font-weight: 800;
            color: #1B4F72;
        }

        .acc-badge {
            display: inline-block;
            padding: .3rem .65rem;
            border-radius: 999px;
            font-size: .74rem;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .acc-tone-green  { background: #E7F8EF; border-color: #A9E4C4; color: #1E8E5A; }
        .acc-tone-blue   { background: #E9F3FB; border-color: #AED4F0; color: #2E86C1; }
        .acc-tone-amber  { background: #FFF6DF; border-color: #F0D48A; color: #92660A; }
        .acc-tone-red    { background: #FCEAEA; border-color: #F0B3AC; color: #C0392B; }
        .acc-tone-grey   { background: #EEF0F2; border-color: #C7CCD1; color: #5D6D7E; }
        .acc-tone-purple { background: #F4ECF7; border-color: #D7BDE2; color: #7D3C98; }
        .acc-tone-navy   { background: #EBF5FB; border-color: #A9CCE3; color: #1B4F72; }

        .acc-actions {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
        }

        .acc-btn {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            height: 30px;
            padding: 0 .75rem;
            border-radius: 999px;
            font-size: .74rem;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
            border: 1px solid transparent;
            transition: transform .15s ease, background .15s ease, color .15s ease;
        }

        .acc-btn:hover {
            transform: translateY(-1px);
        }

        .acc-btn-ghost {
            background: #EBF5FB;
            border-color: rgba(27, 79, 114, .2);
            color: #1B4F72;
        }

        .acc-btn-ghost:hover {
            background: #1B4F72;
            color: #fff;
        }

        .acc-btn-primary {
            background: #1B4F72;
            color: #fff;
        }

        .acc-btn-primary:hover {
            background: #154360;
            color: #fff;
        }

        .acc-btn-danger {
            background: #C0392B;
            color: #fff;
        }

        .acc-btn-danger:hover {
            background: #A93226;
            color: #fff;
        }

        .acc-inline-form {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            margin: 0;
        }

        .acc-date-input {
            height: 30px;
            padding: 0 .5rem;
            border: 1px solid rgba(27, 79, 114, .2);
            border-radius: 8px;
            font-size: .76rem;
            color: #1B4F72;
            background: #fff;
        }

        .acc-date-input:focus {
            outline: none;
            border-color: #1B4F72;
            box-shadow: 0 0 0 2px rgba(27, 79, 114, .12);
        }

        .acc-note {
            font-size: .76rem;
            font-weight: 700;
            color: #5D6D7E;
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            function initTable(table) {
                if (!table || !window.jQuery || !jQuery.fn.DataTable || jQuery.fn.DataTable.isDataTable(table)) {
                    return;
                }
                jQuery(table).DataTable({
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    ordering: false,
                    autoWidth: false,
                    language: {
                        search: 'Search:',
                        lengthMenu: 'Show _MENU_ entries',
                        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                        infoEmpty: 'Showing 0 entries',
                        emptyTable: table.dataset.empty,
                        zeroRecords: 'No matching records found.',
                        paginate: { previous: 'Previous', next: 'Next' }
                    }
                });
            }

            function setupPanel(panel) {
                const cards = document.querySelectorAll('a[href="#' + panel.id + '"][data-acc-tab]');
                const panes = panel.querySelectorAll('[data-acc-pane]');
                const titleEl = panel.querySelector('[data-acc-title]');
                const iconEl = panel.querySelector('[data-acc-title-icon]');
                const countEl = panel.querySelector('[data-acc-count]');
                const pageLink = panel.querySelector('[data-acc-page-link]');

                function show(tab, scroll) {
                    let activePane = null;
                    panes.forEach(function (pane) {
                        const on = pane.dataset.accPane === tab;
                        pane.hidden = !on;
                        if (on) activePane = pane;
                    });
                    cards.forEach(function (card) {
                        card.classList.toggle('is-active', card.dataset.accTab === tab);
                    });
                    if (!activePane) return;

                    titleEl.textContent = activePane.dataset.title;
                    iconEl.className = 'bi ' + activePane.dataset.icon;
                    countEl.textContent = activePane.dataset.count;
                    pageLink.href = activePane.dataset.pageUrl;

                    const table = activePane.querySelector('[data-acc-table]');
                    initTable(table);
                    if (window.jQuery && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable(table)) {
                        jQuery(table).DataTable().columns.adjust();
                    }
                    if (scroll) {
                        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }

                cards.forEach(function (card) {
                    card.addEventListener('click', function (e) {
                        e.preventDefault();
                        show(card.dataset.accTab, true);
                    });
                });

                show(panel.dataset.defaultTab || (panes[0] && panes[0].dataset.accPane), false);
            }

            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.acc-panel[id]').forEach(setupPanel);
            });
        })();
    </script>
@endpush
@endonce
