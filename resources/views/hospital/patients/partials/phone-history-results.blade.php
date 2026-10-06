{{--
  AJAX-refreshable results block for the Phone Appointment History page.
  Required: $slug, $patients, $groupedPatients, $search, $fromDate, $toDate.
--}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
    <span class="text-muted small fw-semibold">{{ $patients->total() }} {{ Str::plural('record', $patients->total()) }}</span>
    @if($search !== '')
        <div class="ph-active-search mb-0">
            <i class="bi bi-search"></i> Showing results for "<strong>{{ $search }}</strong>"
            <a href="{{ route('hospital.patients.phone-history', ['slug' => $slug, 'from_date' => $fromDate, 'to_date' => $toDate]) }}"><i class="bi bi-x-circle-fill"></i></a>
        </div>
    @endif
</div>

@forelse($groupedPatients as $date => $rows)
    <h4 class="ot-date-heading">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h4>
    <div class="table-responsive">
        <table class="ot-table">
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
                            @haspermission('patient_register')
                            @if(!$checkedIn)
                                <a href="{{ route('hospital.patients.checkin', ['slug' => $slug, 'patient' => $patient->id]) }}"
                                   class="ph-checkin-btn ms-1">
                                    <i class="bi bi-person-check-fill"></i> Check In
                                </a>
                            @endif
                            @endhaspermission
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <div class="text-center ot-empty">
        <i class="bi bi-inbox me-1"></i>
        @if($search !== '')
            No phone appointment history found for "{{ $search }}".
        @else
            No phone appointment history found for selected dates.
        @endif
    </div>
@endforelse

<div class="ph-footer">
    {{ $patients->links('vendor.pagination.hms') }}
</div>
