@extends('hospital.layouts.app')
@section('title', 'Phone Appointment History')
@section('page-header', 'Phone Appointment History')

@push('styles')
<style>
.phone-history-page {
    padding-bottom: 2rem;
}

.phone-history-card {
    background: #ffffff;
    border: 1px solid rgba(27, 79, 114, 0.12);
    border-radius: 20px;
    box-shadow: 0 10px 28px rgba(27, 79, 114, 0.08);
    overflow: hidden;
}

.phone-history-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(27, 79, 114, 0.12);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.phone-history-title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: #1B4F72;
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
}

.phone-history-filter .btn {
    border-radius: 10px;
    font-weight: 600;
    padding: .45rem .9rem;
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
    background: rgba(27, 79, 114, 0.07);
    color: rgba(27, 79, 114, 0.82);
    font-size: .76rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    border-bottom: 1px solid rgba(27, 79, 114, 0.14);
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
}
</style>
@endpush

@section('content')
<div class="phone-history-page">
    <div class="phone-history-card">
        <div class="phone-history-header">
            <h3 class="phone-history-title">Date-wise Phone Appointment Patients</h3>

            <form method="GET" class="phone-history-filter">
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
                <div>
                    <a href="{{ route('hospital.patients.phone-history', ['slug' => $slug]) }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        <div class="phone-history-body">
            @forelse($groupedPatients as $date => $rows)
                <h4 class="phone-history-date">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h4>
                <div class="table-responsive">
                    <table class="table phone-history-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>MRD</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Reception</th>
                                <th>Contact</th>
                                <th>Case</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $index => $patient)
                                @php $checkedIn = !is_null($patient->case_id); @endphp
                                <tr style="{{ $checkedIn ? '' : 'background:rgba(255,243,205,.25)' }}">
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $patient->patient_code }}</strong></td>
                                    <td>{{ $patient->full_name }}</td>
                                    <td>{{ $patient->doctor?->name ?? '—' }}</td>
                                    <td>{{ $patient->reception?->name ?? '—' }}</td>
                                    <td>{{ $patient->contact_no ?: '—' }}</td>
                                    <td>
                                        @if($checkedIn)
                                            {{ $patient->caseType?->case_type ?? '—' }}
                                            <div style="font-size:.75rem;color:#64748B">{{ money((float)$patient->case_fee, 0) }}</div>
                                        @else
                                            <span style="color:#94A3B8;font-size:.8rem">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($checkedIn)
                                            <span class="ph-badge-done">
                                                <i class="bi bi-check-circle-fill"></i> Checked In
                                            </span>
                                        @else
                                            <span class="ph-badge-pending">
                                                <i class="bi bi-clock"></i> Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td style="white-space:nowrap">
                                        {{-- View button — always shown --}}
                                        <button type="button"
                                            class="ph-view-btn"
                                            data-mrd="{{ $patient->patient_code }}"
                                            data-name="{{ $patient->full_name }}"
                                            data-age="{{ $patient->age }}"
                                            data-gender="{{ ucfirst($patient->gender) }}"
                                            data-contact="{{ $patient->contact_no }}"
                                            data-whatsapp="{{ $patient->whatsapp_no ?: '' }}"
                                            data-city="{{ $patient->cityName ?: '—' }}"
                                            data-date="{{ $patient->appointment_date ? \Carbon\Carbon::parse($patient->appointment_date)->format('d M Y') : '—' }}"
                                            data-doctor="{{ $patient->doctor?->name ?? '—' }}"
                                            data-reception="{{ $patient->reception?->name ?? '—' }}"
                                            data-case="{{ $patient->caseType?->case_type ?? '—' }}"
                                            data-fee="{{ $patient->case_fee ? money((float)$patient->case_fee, 0) : '—' }}"
                                            data-registered="{{ $patient->created_at?->format('d M Y, h:i A') }}"
                                            data-occupation="{{ $patient->occupation ?: '' }}"
                                            data-checkin-url="{{ route('hospital.patients.checkin', ['slug' => $slug, 'patient' => $patient->id]) }}"
                                            data-checked="{{ $patient->case_id ? '1' : '0' }}">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                        {{-- Check In button — only for pending --}}
                                        @if(!$checkedIn)
                                            <a href="{{ route('hospital.patients.checkin', ['slug' => $slug, 'patient' => $patient->id]) }}"
                                               class="ph-checkin-btn ms-1">
                                                <i class="bi bi-person-check-fill"></i> Check In
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <div class="phone-history-empty">No phone appointment history found for selected dates.</div>
            @endforelse

            <div class="phone-history-footer">
                {{ $patients->links('vendor.pagination.hms') }}
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = new bootstrap.Modal(document.getElementById('patientDetailModal'));

    document.querySelectorAll('.ph-view-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
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
});
</script>
@endpush
