{{--
Doctor load overview — ABOVE page title.
One card per doctor; each line: "1 to 2 : 10"
--}}
@php
    $doctorLoadCards = $doctorLoadCards ?? [];
    $doctorLoadDate = $doctorLoadDate ?? now()->toDateString();
@endphp

<style>
    .ot-doc-load {
        margin-bottom: 1rem;
        padding: 1rem 1.1rem 1.05rem;
        border: 1px solid rgba(27, 79, 114, 0.12);
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(235, 245, 251, 0.92), rgba(255, 255, 255, 0.96));
        box-shadow: 0 14px 36px rgba(27, 79, 114, 0.08);
        color: #1B4F72;
    }

    .ot-doc-load-head {
        margin-bottom: .75rem;
    }

    .ot-doc-load-kicker {
        font-size: .78rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #1B4F72;
    }

    .ot-doc-load-sub {
        margin-top: .15rem;
        font-size: .8rem;
        font-weight: 600;
        color: rgba(27, 79, 114, 0.65);
    }

    .ot-doc-load-grid {
        display: flex;
        flex-wrap: nowrap;
        gap: .75rem;
        overflow-x: auto;
        padding-bottom: .35rem;
        scroll-snap-type: x proximity;
    }

    .ot-doc-load-grid::-webkit-scrollbar {
        height: 6px;
    }

    .ot-doc-load-grid::-webkit-scrollbar-thumb {
        background: #c5d6e4;
        border-radius: 10px;
    }

    .ot-doc-load-card {
        flex: 0 0 auto;
        min-width: 180px;
        max-width: 220px;
        scroll-snap-align: start;
        background: #fff;
        border: 1.5px solid rgba(27, 79, 114, 0.14);
        border-radius: 14px;
        padding: .8rem .85rem .7rem;
        transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
    }

    .ot-doc-load-card:hover {
        border-color: rgba(27, 79, 114, 0.32);
        box-shadow: 0 10px 22px rgba(27, 79, 114, 0.1);
        transform: translateY(-1px);
    }

    .ot-doc-load-card.is-selected {
        border-color: #1B4F72;
        box-shadow: 0 0 0 1px rgba(27, 79, 114, 0.15), 0 12px 26px rgba(27, 79, 114, 0.12);
        background: linear-gradient(160deg, rgba(235, 245, 251, 0.95), #fff);
    }

    .ot-doc-load-card-top {
        display: flex;
        align-items: center;
        gap: .55rem;
        margin-bottom: .55rem;
        padding-bottom: .5rem;
        border-bottom: 1px dashed rgba(27, 79, 114, 0.14);
    }

    .ot-doc-load-avatar {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: rgba(27, 79, 114, 0.08);
        color: #1B4F72;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: .9rem;
        flex-shrink: 0;
    }

    .ot-doc-load-card.is-selected .ot-doc-load-avatar {
        background: #1B4F72;
        color: #fff;
    }

    .ot-doc-load-name {
        flex: 1;
        min-width: 0;
        font-size: .95rem;
        font-weight: 800;
        color: #1B4F72;
        line-height: 1.25;
        text-transform: capitalize;
    }

    .ot-doc-load-total {
        min-width: 26px;
        height: 26px;
        padding: 0 .45rem;
        border-radius: 999px;
        background: #1B4F72;
        color: #fff;
        font-size: .78rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .ot-doc-load-slots {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: .2rem;
    }

    .ot-doc-load-slot-btn {
        width: 100%;
        display: flex;
        align-items: baseline;
        gap: .3rem;
        border: 0;
        background: transparent;
        border-radius: 8px;
        padding: .28rem .35rem;
        text-align: left;
        cursor: pointer;
        color: #1B4F72;
        font-weight: 700;
        font-size: .84rem;
        transition: background 140ms ease;
    }

    .ot-doc-load-slot-btn:hover {
        background: rgba(27, 79, 114, 0.06);
    }

    .ot-doc-load-slot-btn.is-active {
        background: rgba(27, 79, 114, 0.1);
    }

    .ot-doc-load-slot-label {
        flex: 1;
        min-width: 0;
    }

    .ot-doc-load-slot-sep {
        color: rgba(27, 79, 114, 0.45);
        font-weight: 800;
    }

    .ot-doc-load-slot-count {
        min-width: 1.4rem;
        font-weight: 800;
        color: rgba(27, 79, 114, 0.55);
        text-align: right;
    }

    .ot-doc-load-slot-count.has-count {
        color: #1B4F72;
    }

    .ot-doc-load-empty,
    .ot-doc-load-empty-slot {
        font-size: .8rem;
        font-weight: 600;
        color: rgba(27, 79, 114, 0.55);
        padding: .35rem 0;
    }

    .ot-appt-title-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .ot-appt-page-title {
        margin: 0;
        font-size: 1.35rem;
        font-weight: 900;
        color: #0D2137;
        letter-spacing: -.015em;
    }

    .ot-doc-load-slot-count {
        cursor: pointer;
        border-radius: 6px;
        padding: 0 .15rem;
        transition: background 140ms ease, color 140ms ease;
    }

    .ot-doc-load-slot-count:hover {
        background: #1B4F72;
        color: #fff;
    }

    .ot-slotp-list {
        max-height: 60vh;
        overflow-y: auto;
    }

    .ot-slotp-row {
        display: flex;
        align-items: center;
        gap: .7rem;
        padding: .7rem 1.1rem;
        border-bottom: 1px solid rgba(27, 79, 114, 0.08);
    }

    .ot-slotp-row:last-child {
        border-bottom: 0;
    }

    .ot-slotp-avatar {
        width: 36px;
        height: 36px;
        flex-shrink: 0;
        border-radius: 10px;
        background: rgba(27, 79, 114, 0.1);
        color: #1B4F72;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
    }

    .ot-slotp-info {
        flex: 1;
        min-width: 0;
    }

    .ot-slotp-name {
        font-weight: 700;
        color: #1B4F72;
        font-size: .92rem;
        text-transform: capitalize;
    }

    .ot-slotp-meta {
        font-size: .78rem;
        color: rgba(27, 79, 114, 0.6);
        font-weight: 600;
    }

    .ot-slotp-badge {
        flex-shrink: 0;
        font-size: .72rem;
        font-weight: 800;
        border-radius: 999px;
        padding: .25rem .6rem;
        text-transform: capitalize;
    }

    .ot-slotp-badge-booked {
        background: #FFF3CD;
        color: #856404;
    }

    .ot-slotp-badge-confirmed {
        background: #D1F2EB;
        color: #0E6655;
    }

    .ot-slotp-badge-cancelled {
        background: #FADBD8;
        color: #922B21;
    }

    .ot-slotp-badge-completed {
        background: #D6EAF8;
        color: #1B4F72;
    }

    .ot-slotp-empty,
    .ot-slotp-loading {
        padding: 1.5rem 1.1rem;
        text-align: center;
        color: rgba(27, 79, 114, 0.6);
        font-size: .88rem;
        font-weight: 600;
    }
</style>

<div class="ot-doc-load" id="otDocLoadPanel"
    data-load-url="{{ route('hospital.ot.appointments.doctor-slot-load', ['slug' => $slug]) }}"
    data-slot-url="{{ route('hospital.ot.appointments.slot-appointments', ['slug' => $slug]) }}"
    data-exclude-id="{{ $appointment->id ?? '' }}" data-date="{{ $doctorLoadDate }}">
    <div class="ot-doc-load-head">
        <div class="ot-doc-load-kicker"><i class="bi bi-people-fill me-1"></i> Doctors — slot load</div>
        <div class="ot-doc-load-sub">
            Patient count per time slot
            <span id="otDocLoadDateLabel">for {{ \Carbon\Carbon::parse($doctorLoadDate)->format('d M Y') }}</span>
        </div>
    </div>

    <div class="ot-doc-load-grid" id="otDocLoadGrid">
        @forelse($doctorLoadCards as $card)
            <div class="ot-doc-load-card" data-doctor-id="{{ $card['id'] }}">
                <div class="ot-doc-load-card-top">
                    <span class="ot-doc-load-avatar">{{ strtoupper(substr($card['name'], 0, 1)) }}</span>
                    <div class="ot-doc-load-name">{{ $card['name'] }}</div>
                    <span class="ot-doc-load-total">{{ $card['total'] ?? 0 }}</span>
                </div>
                <ul class="ot-doc-load-slots">
                    @forelse(($card['slots'] ?? []) as $slot)
                        <li>
                            <button type="button" class="ot-doc-load-slot-btn" data-doctor-id="{{ $card['id'] }}"
                                data-time="{{ $slot['time'] }}">
                                <span class="ot-doc-load-slot-label">{{ $slot['label'] }}</span>
                                <span class="ot-doc-load-slot-sep">:</span>
                                <span
                                    class="ot-doc-load-slot-count {{ ($slot['count'] ?? 0) > 0 ? 'has-count' : '' }}">{{ $slot['count'] ?? 0 }}</span>
                            </button>
                        </li>
                    @empty
                        <li class="ot-doc-load-empty-slot">No time slots configured</li>
                    @endforelse
                </ul>
            </div>
        @empty
            <div class="ot-doc-load-empty">No active doctors found for this hospital.</div>
        @endforelse
    </div>
</div>

<div>
    <a href="{{ route('hospital.dashboard.ot-appointments', ['slug' => $slug]) }}"
        class="btn btn-sm ms-2 text-decoration-none"
        style="background: #1b4f72; color: #ffffff; border:none; width: 150px; padding: 7px;">
        OT Appointment
    </a>
</div>

<div class="modal fade" id="otSlotPatientsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:0">
            <div class="modal-header" style="background:#1B4F72;color:#fff;border:0">
                <h6 class="modal-title mb-0" id="otSlotPatientsModalTitle" style="font-weight:800"></h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="otSlotPatientsList" class="ot-slotp-list"></div>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var panel = document.getElementById('otDocLoadPanel');
                var grid = document.getElementById('otDocLoadGrid');
                var dateLabel = document.getElementById('otDocLoadDateLabel');
                var dateEl = document.getElementById('appointment_date');
                var doctorEl = document.getElementById('doctor_id');
                var timeEl = document.getElementById('appointment_time');
                if (!panel || !grid) return;

                var loadUrl = panel.getAttribute('data-load-url');
                var slotUrl = panel.getAttribute('data-slot-url');
                var excludeId = panel.getAttribute('data-exclude-id') || '';

                var patientsModalEl = document.getElementById('otSlotPatientsModal');
                var patientsModal = (patientsModalEl && window.bootstrap)
                    ? new bootstrap.Modal(patientsModalEl)
                    : null;
                var patientsTitleEl = document.getElementById('otSlotPatientsModalTitle');
                var patientsListEl = document.getElementById('otSlotPatientsList');

                function escapeHtml(str) {
                    return String(str == null ? '' : str)
                        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                }

                function setSelectValue(el, value) {
                    if (!el) return;
                    el.value = value || '';
                    if (window.jQuery && jQuery(el).hasClass('select2-hidden-accessible')) {
                        jQuery(el).val(value || '').trigger('change');
                    } else {
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }

                function formatDateLabel(iso) {
                    if (!iso) return '';
                    try {
                        var d = new Date(iso + 'T00:00:00');
                        return d.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
                    } catch (e) {
                        return iso;
                    }
                }

                function renderCards(doctors) {
                    doctors = Array.isArray(doctors) ? doctors : [];
                    if (!doctors.length) {
                        grid.innerHTML = '<div class="ot-doc-load-empty">No active doctors found for this hospital.</div>';
                        return;
                    }

                    grid.innerHTML = doctors.map(function (doc) {
                        var slots = Array.isArray(doc.slots) ? doc.slots : [];
                        var slotHtml = slots.length
                            ? slots.map(function (s) {
                                var has = (s.count || 0) > 0;
                                return '<li><button type="button" class="ot-doc-load-slot-btn" data-doctor-id="'
                                    + escapeHtml(doc.id) + '" data-time="' + escapeHtml(s.time || '') + '">'
                                    + '<span class="ot-doc-load-slot-label">' + escapeHtml(s.label) + '</span>'
                                    + '<span class="ot-doc-load-slot-sep">:</span>'
                                    + '<span class="ot-doc-load-slot-count' + (has ? ' has-count' : '') + '">'
                                    + (s.count || 0) + '</span></button></li>';
                            }).join('')
                            : '<li class="ot-doc-load-empty-slot">No time slots configured</li>';

                        return '<div class="ot-doc-load-card" data-doctor-id="' + escapeHtml(doc.id) + '">'
                            + '<div class="ot-doc-load-card-top">'
                            + '<span class="ot-doc-load-avatar">' + escapeHtml((doc.name || 'D').charAt(0).toUpperCase()) + '</span>'
                            + '<div class="ot-doc-load-name">' + escapeHtml(doc.name) + '</div>'
                            + '<span class="ot-doc-load-total">' + (doc.total || 0) + '</span>'
                            + '</div><ul class="ot-doc-load-slots">' + slotHtml + '</ul></div>';
                    }).join('');

                    highlightSelection();
                }

                function statusLabel(status) {
                    var map = { booked: 'Booked', confirmed: 'Confirmed', cancelled: 'Cancelled', completed: 'Completed' };
                    return map[status] || (status || '-');
                }

                function statusBadgeClass(status) {
                    return 'ot-slotp-badge-' + (['confirmed', 'cancelled', 'completed'].indexOf(status) !== -1 ? status : 'booked');
                }

                function renderSlotPatients(list) {
                    list = Array.isArray(list) ? list : [];
                    if (!patientsListEl) return;
                    if (!list.length) {
                        patientsListEl.innerHTML = '<div class="ot-slotp-empty">No patients booked in this slot.</div>';
                        return;
                    }

                    patientsListEl.innerHTML = list.map(function (p) {
                        var ageGender = [p.age ? (p.age + 'y') : '', p.gender ? (String(p.gender).charAt(0).toUpperCase() + String(p.gender).slice(1)) : '']
                            .filter(Boolean).join(' / ');
                        var meta = [ageGender, p.mobile_no].filter(Boolean).join(' · ');

                        return '<div class="ot-slotp-row">'
                            + '<div class="ot-slotp-avatar">' + escapeHtml((p.name || 'P').charAt(0).toUpperCase()) + '</div>'
                            + '<div class="ot-slotp-info">'
                            + '<div class="ot-slotp-name">' + escapeHtml(p.name || 'Unnamed') + '</div>'
                            + (meta ? '<div class="ot-slotp-meta">' + escapeHtml(meta) + '</div>' : '')
                            + '</div>'
                            + '<span class="ot-slotp-badge ' + statusBadgeClass(p.status) + '">' + escapeHtml(statusLabel(p.status)) + '</span>'
                            + '</div>';
                    }).join('');
                }

                function openSlotPatientsModal(docId, docName, time, slotLabel) {
                    if (!patientsListEl) return;

                    if (patientsTitleEl) {
                        patientsTitleEl.textContent = (docName || 'Doctor') + ' — ' + (slotLabel || time || '');
                    }
                    patientsListEl.innerHTML = '<div class="ot-slotp-loading">Loading...</div>';
                    if (patientsModal) patientsModal.show();

                    if (!slotUrl || !time) {
                        patientsListEl.innerHTML = '<div class="ot-slotp-empty">No time slot to look up.</div>';
                        return;
                    }

                    var currentDate = (dateEl && dateEl.value) || panel.getAttribute('data-date');
                    var url = slotUrl + '?date=' + encodeURIComponent(currentDate) + '&time=' + encodeURIComponent(time);
                    if (docId) url += '&doctor_id=' + encodeURIComponent(docId);
                    if (excludeId) url += '&exclude_id=' + encodeURIComponent(excludeId);

                    fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function (r) { return r.json(); })
                        .then(function (data) { renderSlotPatients(data && data.appointments); })
                        .catch(function () {
                            patientsListEl.innerHTML = '<div class="ot-slotp-empty">Could not load patients. Please try again.</div>';
                        });
                }

                function highlightSelection() {
                    var docId = doctorEl ? String(doctorEl.value || '') : '';
                    var time = timeEl ? String(timeEl.value || '') : '';
                    grid.querySelectorAll('.ot-doc-load-card').forEach(function (card) {
                        card.classList.toggle('is-selected', !!(docId && card.getAttribute('data-doctor-id') === docId));
                    });
                    grid.querySelectorAll('.ot-doc-load-slot-btn').forEach(function (btn) {
                        var active = !!(docId && time
                            && btn.getAttribute('data-doctor-id') === docId
                            && btn.getAttribute('data-time') === time);
                        btn.classList.toggle('is-active', active);
                    });
                }

                function loadCards(date) {
                    if (!date) return;
                    var url = loadUrl + '?date=' + encodeURIComponent(date);
                    if (excludeId) url += '&exclude_id=' + encodeURIComponent(excludeId);
                    if (dateLabel) dateLabel.textContent = 'for ' + formatDateLabel(date);

                    fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function (r) { return r.json(); })
                        .then(function (data) { renderCards(data && data.doctors); })
                        .catch(function () { });
                }

                grid.addEventListener('click', function (e) {
                    var btn = e.target.closest('.ot-doc-load-slot-btn');
                    if (!btn) return;

                    var countEl = e.target.closest('.ot-doc-load-slot-count');
                    if (countEl) {
                        e.preventDefault();
                        e.stopPropagation();
                        var card = btn.closest('.ot-doc-load-card');
                        var docName = card ? (card.querySelector('.ot-doc-load-name') || {}).textContent : '';
                        var slotLabel = (btn.querySelector('.ot-doc-load-slot-label') || {}).textContent;
                        openSlotPatientsModal(
                            btn.getAttribute('data-doctor-id') || '',
                            docName,
                            btn.getAttribute('data-time') || '',
                            slotLabel
                        );
                        return;
                    }

                    setSelectValue(doctorEl, btn.getAttribute('data-doctor-id') || '');
                    var t = btn.getAttribute('data-time') || '';
                    if (t) setSelectValue(timeEl, t);
                    if (dateEl && !dateEl.value && panel.getAttribute('data-date')) {
                        if (dateEl._flatpickr) {
                            dateEl._flatpickr.setDate(panel.getAttribute('data-date'), true);
                        } else {
                            dateEl.value = panel.getAttribute('data-date');
                        }
                    }
                    highlightSelection();
                });

                if (dateEl) {
                    dateEl.addEventListener('change', function () {
                        if (dateEl.value) loadCards(dateEl.value);
                    });
                }
                if (doctorEl) doctorEl.addEventListener('change', highlightSelection);
                if (timeEl) timeEl.addEventListener('change', highlightSelection);

                highlightSelection();
            });
        </script>
    @endpush
@endonce