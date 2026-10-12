@extends('hospital.layouts.app')
@section('title', 'Phone Appointment History')
{{-- Layout page-header intentionally unused — the heading + breadcrumb are
rendered inside the card itself, matching the OT Appointments (Appointment
Register) design refresh: ot-appt-page / ot-premium-card / ot-inner-panel. --}}

@section('content')
<div class="ot-appt-page">
    <div class="card ot-premium-card border-0">
        <div class="ot-header-block">
            <div class="ot-header-title">
                <i class="bi bi-telephone-fill"></i> Phone Appointment History
            </div>
            <nav class="ot-breadcrumb" aria-label="breadcrumb">
                <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                <span class="ot-breadcrumb-sep">/</span>
                <span class="ot-breadcrumb-current">Phone Appointment History</span>
            </nav>
        </div>

        <div class="ot-inner-panel">
            <div class="ot-card-header">
                <div class="ot-title-wrap">
                    <span class="ot-title-icon" aria-hidden="true">
                        <i class="bi bi-calendar2-week" style="font-size: 1.2rem;"></i>
                    </span>
                    <div class="flex-grow-1">
                        <h5 class="ot-title">Phone Appointment Patients</h5>
                        <div class="ot-subtitle">Search by name/mobile and filter by date range.</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap ot-header-actions">
                    <form method="GET" id="phoneHistoryFilterForm"
                        class="d-inline-flex align-items-center gap-2 flex-wrap"
                        action="{{ route('hospital.patients.phone-history', ['slug' => $slug]) }}">
                        <div class="ph-search-wrap">
                            <i class="bi bi-search"></i>
                            <input type="text" id="ph_search" name="search"
                                class="form-control form-control-sm ot-desk-date-input ph-search-input"
                                value="{{ $search }}"
                                placeholder="Search by name or mobile"
                                autocomplete="off">
                        </div>
                        <input type="text" id="date_range" class="form-control form-control-sm ot-desk-date-input"
                            data-hms-date-range
                            data-start-name="from_date"
                            data-end-name="to_date"
                            data-start-value="{{ $fromDate }}"
                            data-end-value="{{ $toDate }}"
                            data-auto-submit="1"
                            placeholder="Date range"
                            autocomplete="off"
                            readonly
                            style="min-width:200px;">
                    </form>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="ot-table-wrap">
                    <div id="phoneHistoryResults">
                        @include('hospital.patients.partials.phone-history-results')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Patient Detail Modal --}}
@push('modals')
<div class="modal fade" id="patientDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:640px">
        <div class="modal-content border-0" style="border-radius:18px;overflow:hidden">

            {{-- Modal Header --}}
            <div id="mdlHeader" style="background:linear-gradient(135deg,#1B4F72,#2980B9);padding:1.4rem 1.6rem;display:flex;align-items:center;gap:1rem">
                <div style="width:48px;height:48px;background:rgba(255,255,255,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.35rem;color:#fff;flex-shrink:0">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div style="flex:1">
                    <div id="mdlName" style="font-size:1.15rem;font-weight:800;color:#fff"></div>
                    <div id="mdlMeta" style="font-size:.8rem;color:rgba(255,255,255,.8);margin-top:.15rem"></div>
                </div>
                <div>
                    <span id="mdlStatusBadge"></span>
                </div>
                <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal"></button>
            </div>

            {{-- Modal Body --}}
            <div class="modal-body p-0">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1px;background:#E2EBF0">

                    {{-- Contact --}}
                    <div style="background:#fff;padding:1.1rem 1.25rem">
                        <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748B;margin-bottom:.8rem">
                            <i class="bi bi-telephone-fill me-1" style="color:#1B4F72"></i> Contact
                        </div>
                        <div style="display:flex;flex-direction:column;gap:.55rem">
                            <div>
                                <div style="font-size:.7rem;color:#94A3B8;font-weight:600">Mobile</div>
                                <div id="mdlContact" style="font-weight:700;color:#1A202C;font-size:.9rem"></div>
                            </div>
                            <div>
                                <div style="font-size:.7rem;color:#94A3B8;font-weight:600">WhatsApp</div>
                                <div id="mdlWhatsapp" style="font-weight:600;color:#1A202C;font-size:.9rem"></div>
                            </div>
                            <div>
                                <div style="font-size:.7rem;color:#94A3B8;font-weight:600">City</div>
                                <div id="mdlCity" style="font-weight:600;color:#1A202C;font-size:.9rem"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Appointment --}}
                    <div style="background:#fff;padding:1.1rem 1.25rem">
                        <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748B;margin-bottom:.8rem">
                            <i class="bi bi-calendar-check-fill me-1" style="color:#1B4F72"></i> Appointment
                        </div>
                        <div style="display:flex;flex-direction:column;gap:.55rem">
                            <div>
                                <div style="font-size:.7rem;color:#94A3B8;font-weight:600">Date</div>
                                <div id="mdlDate" style="font-weight:700;color:#1A202C;font-size:.9rem"></div>
                            </div>
                            <div>
                                <div style="font-size:.7rem;color:#94A3B8;font-weight:600">Doctor</div>
                                <div id="mdlDoctor" style="font-weight:700;color:#1A202C;font-size:.9rem"></div>
                            </div>
                            <div>
                                <div style="font-size:.7rem;color:#94A3B8;font-weight:600">Receptionist</div>
                                <div id="mdlReception" style="font-weight:600;color:#1A202C;font-size:.9rem"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Case Details --}}
                    <div style="background:#fff;padding:1.1rem 1.25rem">
                        <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748B;margin-bottom:.8rem">
                            <i class="bi bi-clipboard2-pulse-fill me-1" style="color:#1B4F72"></i> Case Details
                        </div>
                        <div style="display:flex;flex-direction:column;gap:.55rem">
                            <div>
                                <div style="font-size:.7rem;color:#94A3B8;font-weight:600">Case Type</div>
                                <div id="mdlCase" style="font-weight:700;color:#1A202C;font-size:.9rem"></div>
                            </div>
                            <div>
                                <div style="font-size:.7rem;color:#94A3B8;font-weight:600">Case Fee</div>
                                <div id="mdlFee" style="font-weight:800;color:#27AE60;font-size:1rem"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Registration --}}
                    <div style="background:#fff;padding:1.1rem 1.25rem">
                        <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748B;margin-bottom:.8rem">
                            <i class="bi bi-info-circle-fill me-1" style="color:#1B4F72"></i> Registration
                        </div>
                        <div style="display:flex;flex-direction:column;gap:.55rem">
                            <div>
                                <div style="font-size:.7rem;color:#94A3B8;font-weight:600">Registered On</div>
                                <div id="mdlRegistered" style="font-weight:700;color:#1A202C;font-size:.9rem"></div>
                            </div>
                            <div>
                                <div style="font-size:.7rem;color:#94A3B8;font-weight:600">MRD</div>
                                <div id="mdlMrd" style="font-weight:800;color:#1B4F72;font-size:.95rem"></div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Check-In footer (only for pending) --}}
                <div id="mdlCheckinFooter" style="display:none;padding:1rem 1.25rem;background:#FFFDE7;border-top:1px solid #FFE082;display:flex;align-items:center;justify-content:space-between;gap:1rem">
                    <div>
                        <div style="font-weight:700;color:#856404;font-size:.88rem"><i class="bi bi-exclamation-circle-fill me-1"></i> Patient not yet checked in</div>
                        <div style="font-size:.76rem;color:#A07800;margin-top:.15rem">Assign case type and fee to complete check-in</div>
                    </div>
                    <a id="mdlCheckinLink" href="#"
                       style="background:#1B4F72;color:#fff;border-radius:10px;padding:.55rem 1.1rem;font-weight:700;font-size:.82rem;text-decoration:none;white-space:nowrap;display:inline-flex;align-items:center;gap:.4rem">
                        <i class="bi bi-person-check-fill"></i> Check In Now
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
@endpush

@push('styles')
    @include('hospital.ot.partials.desk-filter-styles')
    <style>
        /*
            Phone Appointment History — reuses the OT Appointments (Appointment
            Register) design refresh (hospital shell theme, #1B4F72).
        */

        .ot-appt-page {
            --ot-primary: #1B4F72;
            --ot-primary-dark: #154160;
            --ot-s2-06: rgba(27, 79, 114, 0.06);
            --ot-s2-08: rgba(27, 79, 114, 0.08);
            --ot-s2-12: rgba(27, 79, 114, 0.12);
            --ot-s2-18: rgba(27, 79, 114, 0.18);
            --ot-s2-24: rgba(27, 79, 114, 0.24);

            position: relative;
            padding: .25rem 0 1.25rem;
            color: var(--ot-primary);
            animation: ot-page-in 420ms ease both;
        }

        @keyframes ot-page-in {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .ot-appt-page .btn,
        .ot-appt-page .hms-btn {
            border-radius: 10px;
            font-weight: 700;
            transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, border-color 170ms ease, color 170ms ease;
        }

        .ot-appt-page .btn:hover,
        .ot-appt-page .hms-btn:hover {
            transform: translateY(-1px);
        }

        .ot-premium-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.12) !important;
            border-radius: 0.90rem;
            box-shadow: 0 18px 48px rgba(27, 79, 114, 0.10);
            overflow: hidden;
            animation: ot-card-rise 520ms cubic-bezier(.2, .9, .2, 1) both;
        }

        @keyframes ot-card-rise {
            from {
                opacity: 0;
                transform: translateY(10px) scale(0.99);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .ot-header-block {
            background: #ffffff;
            padding: 1.25rem 1.5rem 1rem;
        }

        .ot-header-title {
            font-weight: 800;
            font-size: 1.3rem;
            color: #1b4f72;
            letter-spacing: -.015em;
            display: flex;
            align-items: center;
            gap: .55rem;
        }

        .ot-header-title i {
            color: var(--ot-primary);
            font-size: 1.2rem;
        }

        .ot-breadcrumb {
            margin-top: .4rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: #8891a0;
        }

        .ot-breadcrumb a {
            color: #8891a0;
            text-decoration: none;
        }

        .ot-breadcrumb a:hover {
            color: var(--ot-primary);
        }

        .ot-breadcrumb-sep {
            color: #c3c9d3;
        }

        .ot-breadcrumb-current {
            color: #4a5568;
            font-weight: 600;
        }

        .ot-inner-panel {
            margin: 0 1.5rem 1.5rem;
            border: 1px solid rgba(15, 79, 134, 0.12);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(27, 79, 114, 0.06);
        }

        .ot-card-header {
            background: var(--ot-primary);
            padding: 1.15rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .ot-title-wrap {
            display: flex;
            align-items: center;
            gap: .85rem;
            min-width: 0;
        }

        .ot-title-wrap>div {
            min-width: 0;
        }

        .ot-title-icon {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.14);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .ot-title {
            font-weight: 800;
            letter-spacing: -0.2px;
            margin: 0;
            color: #ffffff;
        }

        .ot-subtitle {
            margin: .15rem 0 0;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.78);
            font-size: .85rem;
        }

        .ot-header-actions {
            flex-wrap: wrap;
        }

        .ot-alert {
            border-radius: 12px;
            font-weight: 600;
        }

        .ot-table-wrap {
            padding: 0 1.5rem 1.25rem !important;
            overflow-x: auto;
        }

        .ot-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }

        .ot-table thead th {
            background: #ffffff;
            color: rgba(27, 79, 114, 0.62);
            border-bottom: 2px solid var(--ot-s2-12);
            font-size: .72rem;
            letter-spacing: .06em;
            font-weight: 800;
            text-transform: uppercase;
            padding: .85rem .95rem;
            white-space: nowrap;
            text-align: left;
        }

        .ot-table tbody td {
            padding: .85rem .95rem;
            font-weight: 550;
            font-size: .9rem;
            color: rgba(23, 50, 77, 0.86);
            vertical-align: middle;
            white-space: nowrap;
            border-bottom: 1px solid var(--ot-s2-08);
        }

        .ot-table tbody td:first-child {
            font-weight: 800;
            color: var(--ot-primary);
        }

        .ot-table tbody tr:nth-child(even) td {
            background: #f7fbfe;
        }

        .ot-table tbody tr:hover td {
            background: var(--ot-s2-06);
        }

        .ot-empty {
            padding: 2.25rem 1rem !important;
            color: rgba(27, 79, 114, 0.55) !important;
            font-weight: 700;
        }

        .ot-date-heading {
            margin: 1.1rem 0 .55rem;
            font-size: .94rem;
            font-weight: 800;
            color: var(--ot-primary);
        }

        .ot-date-heading:first-child {
            margin-top: .25rem;
        }

        /* Phone-specific pieces (search box, active-search chip, status badges,
           check-in / view buttons) — same hospital shell palette as above. */
        .ph-search-wrap {
            position: relative;
            width: 230px;
            flex: 0 0 230px;
        }

        .ph-search-wrap i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(27, 79, 114, 0.5);
            font-size: .85rem;
            pointer-events: none;
        }

        .ph-search-input {
            width: 100%;
            min-width: 0;
            padding-left: 32px !important;
        }

        #date_range {
            width: 230px;
            min-width: 230px !important;
            flex: 0 0 230px;
        }

        .ph-active-search {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: var(--ot-s2-08);
            color: var(--ot-primary);
            border: 1px solid var(--ot-s2-18);
            border-radius: 20px;
            padding: .3rem .85rem;
            font-size: .8rem;
            font-weight: 600;
            margin: 1rem 0 0;
        }

        .ph-active-search a {
            color: var(--ot-primary);
            margin-left: .3rem;
        }

        .ph-badge-pending {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            background: #FFF3CD;
            color: #856404;
            border: 1px solid #FFECB5;
            border-radius: 20px;
            padding: .2rem .7rem;
            font-size: .73rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .ph-badge-done {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            background: #D1F2EB;
            color: #0E6655;
            border: 1px solid #A2DFD0;
            border-radius: 20px;
            padding: .2rem .7rem;
            font-size: .73rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .ph-checkin-btn {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            background: var(--ot-primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: .3rem .8rem;
            font-size: .78rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: background .15s;
        }

        .ph-checkin-btn:hover {
            background: var(--ot-primary-dark);
            color: #fff;
        }

        .ph-view-btn {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            background: #EAF4FB;
            color: var(--ot-primary);
            border: 1px solid rgba(27, 79, 114, .22);
            border-radius: 8px;
            padding: .3rem .8rem;
            font-size: .78rem;
            font-weight: 700;
            cursor: pointer;
            transition: background .15s;
        }

        .ph-view-btn:hover {
            background: #D6EAF8;
        }

        .ph-footer {
            margin-top: 1rem;
            display: flex;
            justify-content: flex-end;
        }

        @media (prefers-reduced-motion: reduce) {

            .ot-appt-page,
            .ot-premium-card,
            .ot-appt-page .btn,
            .ot-appt-page .hms-btn {
                animation: none !important;
                transition: none !important;
            }
        }

        @media (max-width: 768px) {
            .ot-card-header {
                align-items: flex-start;
            }

            .ph-search-input {
                min-width: 100%;
                width: 100%;
            }

            .ph-search-wrap,
            #date_range {
                width: 100%;
                min-width: 100% !important;
                flex-basis: 100%;
            }

            #phoneHistoryFilterForm {
                width: 100%;
            }

            #phoneHistoryFilterForm>* {
                width: 100%;
            }
        }
    </style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const resultsBox = document.getElementById('phoneHistoryResults');
    const filterForm = document.getElementById('phoneHistoryFilterForm');
    const searchInput = document.getElementById('ph_search');

    // AJAX refresh of the results block only — the filter form (and the
    // focused search input inside it) is never touched, so typing never
    // loses the cursor/focus the way a full page reload would.
    function loadResults(url) {
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) {
                if (!res.ok) { throw new Error('bad response'); }
                return res.text();
            })
            .then(function (html) {
                resultsBox.innerHTML = html;
                window.history.replaceState(null, '', url);
            })
            .catch(function () {
                window.location.href = url; // fallback: plain navigation
            });
    }

    if (filterForm && resultsBox) {
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const params = new URLSearchParams(new FormData(filterForm));
            loadResults(filterForm.action + '?' + params.toString());
        });
    }

    if (searchInput && filterForm) {
        let phSearchTimer = null;
        searchInput.addEventListener('input', function () {
            clearTimeout(phSearchTimer);
            phSearchTimer = setTimeout(function () {
                if (typeof filterForm.requestSubmit === 'function') {
                    filterForm.requestSubmit();
                } else {
                    filterForm.submit();
                }
            }, 500);
        });
    }

    const modal = new bootstrap.Modal(document.getElementById('patientDetailModal'));

    // Delegated so it keeps working on rows swapped in by loadResults() above.
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.ph-view-btn');
        if (!btn) { return; }

        const d = btn.dataset;
        const checkedIn = d.checked === '1';

        document.getElementById('mdlName').textContent = d.name;
        document.getElementById('mdlMrd').textContent = d.mrd;
        document.getElementById('mdlMeta').textContent =
            d.age + 'y / ' + d.gender + (d.occupation ? ' · ' + d.occupation : '') + '  |  MRD: ' + d.mrd;

        document.getElementById('mdlContact').textContent  = d.contact  || '—';
        document.getElementById('mdlWhatsapp').textContent = d.whatsapp || 'Same as mobile';
        document.getElementById('mdlCity').textContent     = d.city     || '—';
        document.getElementById('mdlDate').textContent     = d.date     || '—';
        document.getElementById('mdlDoctor').textContent   = d.doctor   || '—';
        document.getElementById('mdlReception').textContent= d.reception|| '—';
        document.getElementById('mdlCase').textContent     = d.case     || '—';
        document.getElementById('mdlFee').textContent      = d.fee      || '—';
        document.getElementById('mdlRegistered').textContent = d.registered || '—';

        const badge = document.getElementById('mdlStatusBadge');
        if (checkedIn) {
            badge.innerHTML = '<span style="background:rgba(39,174,96,.25);color:#D5F5E3;border:1px solid rgba(39,174,96,.4);border-radius:20px;padding:.25rem .85rem;font-size:.72rem;font-weight:700"><i class=\'bi bi-check-circle-fill\'></i> Checked In</span>';
        } else {
            badge.innerHTML = '<span style="background:rgba(255,193,7,.2);color:#FFEAA7;border:1px solid rgba(255,193,7,.35);border-radius:20px;padding:.25rem .85rem;font-size:.72rem;font-weight:700"><i class=\'bi bi-clock\'></i> Pending</span>';
        }

        const footer = document.getElementById('mdlCheckinFooter');
        if (!checkedIn) {
            document.getElementById('mdlCheckinLink').href = d.checkinUrl;
            footer.style.display = 'flex';
        } else {
            footer.style.display = 'none';
        }

        modal.show();
    });
});
</script>
@endpush
