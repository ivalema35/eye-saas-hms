@extends('hospital.layouts.app')
@section('title', 'Phone Appointment History')
{{-- Layout page-header intentionally unused — the heading, breadcrumb and
list all sit inside one bordered card, matching the Medicine Master / Users /
Roles / History / OT Patients panel design. --}}

@push('styles')
<style>
.phone-history-page {
    padding-bottom: .25rem;
}

.phone-history-outer-card {
    background: #ffffff;
    border: 1px solid rgba(15, 79, 134, 0.12);
    border-radius: 16px;
    box-shadow: 0 12px 32px rgba(15, 79, 134, 0.08);
    overflow: hidden;
    padding: 1.25rem 1.5rem 1.5rem;
}

.phone-history-header-block {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: .75rem;
    padding: 0 0 1rem;
}

.phone-history-page-title {
    font-weight: 800;
    font-size: 1.3rem;
    color: #1B4F72;
    letter-spacing: -.015em;
    display: flex;
    align-items: center;
    gap: .65rem;
}

.phone-history-page-title .ph-title-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1B4F72, #2980B9);
    color: #fff;
    font-size: 1.05rem;
    box-shadow: 0 6px 14px rgba(27, 79, 114, 0.28);
}

.phone-history-breadcrumb {
    margin-top: .4rem;
    display: flex;
    align-items: center;
    gap: .4rem;
    font-size: .85rem;
    color: #8891a0;
}

.phone-history-breadcrumb a {
    color: #8891a0;
    text-decoration: none;
}

.phone-history-breadcrumb a:hover {
    color: #1B4F72;
}

.phone-history-breadcrumb-sep {
    color: #c3c9d3;
}

.phone-history-breadcrumb-current {
    color: #4a5568;
    font-weight: 600;
}

.phone-history-card {
    background: #ffffff;
    border: 1px solid rgba(15, 79, 134, 0.08);
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(15, 79, 134, 0.05);
    overflow: hidden;
}

.phone-history-header {
    padding: 1rem 1.25rem;
    background: #1B4F72;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.phone-history-title {
    margin: 0;
    font-size: .95rem;
    font-weight: 700;
    color: #ffffff;
    display: flex;
    align-items: center;
    gap: .5rem;
}

.phone-history-card-body {
    padding: 1.25rem 1.5rem;
}

.ph-filter-icon {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(27, 79, 114, 0.10);
    color: #1B4F72;
    font-size: 1rem;
}

.ph-filter-title {
    color: #1B4F72;
    font-weight: 800;
}

.ph-btn-outline {
    border: 1.5px solid rgba(27, 79, 114, 0.24);
    color: #1B4F72;
    font-weight: 700;
    border-radius: 10px;
    background: #fff;
    transition: background 170ms ease, border-color 170ms ease;
}

.ph-btn-outline:hover {
    background: rgba(27, 79, 114, 0.06);
    color: #1B4F72;
    border-color: #1B4F72;
}

.phone-history-filter {
    display: flex;
    align-items: end;
    gap: .65rem;
    flex-wrap: wrap;
}

.phone-history-filter .form-label {
    font-size: .78rem;
    font-weight: 700;
    color: rgba(27, 79, 114, 0.8);
    margin-bottom: .25rem;
}

.phone-history-filter .form-control {
    min-width: 170px;
    border-radius: 10px;
    border: 1px solid rgba(27, 79, 114, 0.16);
    transition: border-color 170ms ease, box-shadow 170ms ease;
}

.phone-history-filter .form-control:focus {
    border-color: #1B4F72;
    box-shadow: 0 0 0 .18rem rgba(27, 79, 114, 0.12);
}

.phone-history-filter .btn {
    border-radius: 10px;
    font-weight: 600;
    padding: .45rem .9rem;
}

.ph-filter-divider {
    width: 1px;
    align-self: stretch;
    min-height: 42px;
    background: rgba(27, 79, 114, 0.12);
    margin: 0 .15rem;
}

.ph-search-wrap {
    position: relative;
}

.ph-search-wrap i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(27, 79, 114, 0.5);
    font-size: .9rem;
    pointer-events: none;
}

.ph-search-wrap .form-control {
    min-width: 260px;
    padding-left: 34px;
}

.ph-btn-search {
    border-radius: 10px;
    font-weight: 700;
    padding: .45rem 1.1rem;
    background: #1B4F72;
    border: 1.5px solid #1B4F72;
    color: #fff;
    transition: background 170ms ease, box-shadow 170ms ease;
}

.ph-btn-search:hover {
    background: #154360;
    color: #fff;
    box-shadow: 0 6px 16px rgba(27, 79, 114, 0.28);
}

.ph-active-search {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    background: rgba(27, 79, 114, 0.08);
    color: #1B4F72;
    border: 1px solid rgba(27, 79, 114, 0.18);
    border-radius: 20px;
    padding: .3rem .85rem;
    font-size: .8rem;
    font-weight: 600;
    margin-bottom: .9rem;
}

.ph-active-search a {
    color: #1B4F72;
    margin-left: .3rem;
}

.phone-history-body {
    padding: 1rem 1.25rem 1.25rem;
}

.phone-history-date {
    margin: .9rem 0 .55rem;
    font-size: .94rem;
    font-weight: 700;
    color: #1B4F72;
}

.phone-history-table {
    margin: 0;
}

.phone-history-table thead th {
    background: #F8FAFC;
    color: #4A5568;
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    border-bottom: 1px solid #E2E8F0;
}

.phone-history-table tbody tr:hover td {
    background: #F5F8FC;
}

.phone-history-table td {
    vertical-align: middle;
    color: #1F3345;
    font-size: .9rem;
}

.phone-history-empty {
    text-align: center;
    color: rgba(27, 79, 114, 0.7);
    padding: 2.2rem 1rem;
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
    background: #1B4F72;
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
.ph-checkin-btn:hover { background: #154360; color: #fff; }
.ph-view-btn {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    background: #EAF4FB;
    color: #1B4F72;
    border: 1px solid rgba(27,79,114,.22);
    border-radius: 8px;
    padding: .3rem .8rem;
    font-size: .78rem;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s;
}
.ph-view-btn:hover { background: #D6EAF8; }

.phone-history-footer {
    margin-top: 1rem;
    display: flex;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .phone-history-header {
        padding: 1rem;
    }

    .phone-history-body {
        padding: .9rem;
    }

    .phone-history-filter .form-control {
        min-width: 140px;
    }

    .ph-search-wrap .form-control {
        min-width: 100%;
        width: 100%;
    }

    .phone-history-filter {
        width: 100%;
    }

    .phone-history-filter > div {
        width: 100%;
    }

    .ph-filter-divider {
        display: none;
    }
}
</style>
@endpush

@section('content')
<div class="phone-history-page">
    <div class="phone-history-outer-card">
        <div class="phone-history-header-block">
            <div>
                <div class="phone-history-page-title"><span class="ph-title-icon"><i class="bi bi-telephone-fill"></i></span> Phone Appointment History</div>
                <nav class="phone-history-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                    <span class="phone-history-breadcrumb-sep">/</span>
                    <span class="phone-history-breadcrumb-current">Phone Appointment History</span>
                </nav>
            </div>
        </div>

    <div class="phone-history-card mb-4">
        <div class="phone-history-card-body">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="ph-filter-icon"><i class="bi bi-funnel-fill"></i></span>
                <strong class="ph-filter-title">Search &amp; Date Range Filter</strong>
            </div>

            <form method="GET" id="phoneHistoryFilterForm" class="phone-history-filter"
                action="{{ route('hospital.patients.phone-history', ['slug' => $slug]) }}">
                <div>
                    <label class="form-label" for="ph_search">Name / Mobile number</label>
                    <div class="ph-search-wrap">
                        <i class="bi bi-search"></i>
                        <input type="text" id="ph_search" name="search" class="form-control"
                            value="{{ $search }}"
                            placeholder="Search by patient name or mobile number"
                            autocomplete="off">
                    </div>
                </div>
                <div class="ph-filter-divider d-none d-md-block"></div>
                <div>
                    <label class="form-label" for="date_range">Date range</label>
                    <input type="text" id="date_range" class="form-control"
                        data-hms-date-range
                        data-start-name="from_date"
                        data-end-name="to_date"
                        data-start-value="{{ $fromDate }}"
                        data-end-value="{{ $toDate }}"
                        data-auto-submit="1"
                        placeholder="Select start → end date"
                        autocomplete="off"
                        readonly
                        style="min-width:220px;">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn ph-btn-search"><i class="bi bi-search"></i> Search</button>
                    <a href="{{ route('hospital.patients.phone-history', ['slug' => $slug]) }}" class="btn ph-btn-outline">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div id="phoneHistoryResults">
        @include('hospital.patients.partials.phone-history-results')
    </div>
    </div>{{-- /.phone-history-outer-card --}}
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
