{{-- Receptionist "Today Added Patients" table only. Re-rendered on every AJAX
mobile-number search — the filter form and count badge stay in the parent
view and are never replaced, so the input never loses focus while typing.
Thresholds are recomputed here (cheap, cached settings lookups) so this
partial is self-contained for the AJAX response. --}}
@php
    $wGreen = (int) hospital_setting('wait_green_max', 30);
    $wOrange = (int) hospital_setting('wait_orange_max', 60);
    $wRed = (int) hospital_setting('wait_red_max', 120);
    $wDGreen = (int) hospital_setting('wait_d_green_max', 40);
    $wDOrange = (int) hospital_setting('wait_d_orange_max', 90);
    $wDRed = (int) hospital_setting('wait_d_red_max', 120);
    $wNdGreen = (int) hospital_setting('wait_nd_green_max', 20);
    $wNdOrange = (int) hospital_setting('wait_nd_orange_max', 60);
    $wNdRed = (int) hospital_setting('wait_nd_red_max', 120);
@endphp
<div class="table-responsive" data-patient-count="{{ $receptionistTodayPatients->count() }}">
    <table class="tap-table">
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th>MRD</th>
                <th>Patient</th>
                <th>City / Age</th>
                <th>Contact</th>
                <th>Doctor</th>
                <th>DR Index</th>
                <th>Status</th>
                <th style="text-align:center">Wait</th>
                <th style="text-align:center">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($receptionistTodayPatients as $index => $patient)
                @php
                    $isOt = ($patient->source ?? 'patient') === 'ot_appointment';
                @endphp

                @if($isOt)
                    @php
                        $initials = strtoupper(substr((string) ($patient->first_name ?? 'O'), 0, 1) . substr((string) ($patient->last_name ?? 'T'), 0, 1));
                        $otStatus = strtolower((string) ($patient->ot_status ?? 'booked'));
                        $otStatusLabel = ucfirst($otStatus);
                        $otBadgeClass = match ($otStatus) {
                            'confirmed' => 'tap-status-primary',
                            'completed' => 'tap-status-done',
                            default => 'tap-status-waiting',
                        };
                    @endphp
                    <tr>
                        <td style="color:#94a3b8; font-size:12px; font-weight:600;">{{ $index + 1 }}</td>
                        <td><span class="tap-mrd">{{ $patient->patient_code }}</span></td>
                        <td>
                            <div class="tap-patient-cell">
                                <div class="tap-avatar" style="background:#ede9fe;color:#5b21b6;">{{ $initials }}</div>
                                <div>
                                    <div class="tap-name">{{ $patient->full_name }}</div>
                                    <span class="tap-type-pill tap-type-ot">
                                        <i class="bi bi-hospital" style="font-size:9px;"></i>
                                        OT Appt
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="tap-meta">
                            <strong>{{ $patient->cityName ?: '—' }}</strong><br>
                            <span>
                                {{ $patient->age !== null && $patient->age !== '' ? $patient->age . 'y' : '—' }}
                                @if($patient->gender) / {{ ucfirst($patient->gender) }} @endif
                            </span>
                        </td>
                        <td class="tap-slot">
                            @if($patient->contact_no)
                                {{ $patient->contact_no }}
                            @else
                                <span style="color:#cbd5e1">—</span>
                            @endif
                        </td>
                        <td style="font-size:13px; color:#334155; font-weight:500;">{{ $patient->doctor?->name ?? '—' }}</td>
                        <td>
                            @if(!empty($patient->appointment_time))
                                <span class="tap-dr-index" style="background:#f5f3ff;color:#5b21b6;">
                                    {{ \Illuminate\Support\Str::of($patient->appointment_time)->substr(0, 5) }}
                                </span>
                            @else
                                <span style="color:#cbd5e1">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="{{ $otBadgeClass }}"><i class="bi bi-calendar2-check"></i> {{ $otStatusLabel }}</span>
                        </td>
                        <td style="text-align:center">
                            <a href="{{ route('hospital.patients.create', ['slug' => $slug, 'ot_appointment_id' => $patient->ot_appointment_id]) }}"
                                style="display:inline-flex;align-items:center;gap:5px;padding:4px 11px;background:#5b21b6;color:#fff;border-radius:20px;font-size:.7rem;font-weight:700;text-decoration:none;box-shadow:0 0 0 2px rgba(91,33,182,.3);">
                                <i class="bi bi-person-walking"></i> Walk-In
                            </a>
                        </td>
                        <td style="text-align:center">
                            <a href="{{ route('hospital.ot.appointments.edit', ['slug' => $slug, 'id' => $patient->ot_appointment_id]) }}"
                                class="tap-print-btn" title="Open OT Appointment">
                                <i class="bi bi-box-arrow-up-right" style="font-size:14px;"></i>
                            </a>
                        </td>
                    </tr>
                @else
                    @php
                        $hasPrimaryDone = $patient->primary_done_at !== null;
                        $hasSecondaryDone = $patient->secondary_done_at !== null;
                        $waitMins = (int) ($patient->checked_in_at ?? $patient->created_at)->diffInMinutes(now());
                        $waitClass = $waitMins < $wGreen ? 'wait-green' : ($waitMins < $wOrange ? 'wait-orange' : ($waitMins < $wRed ? 'wait-red' : 'wait-fire'));
                        $waitFmt = $waitMins < 60 ? $waitMins . 'm' : floor($waitMins / 60) . 'h' . ($waitMins % 60 > 0 ? ' ' . ($waitMins % 60) . 'm' : '');
                        $pExam = $patient->primaryExamination;
                        $isDilated = $pExam && ($pExam->exam_data['dilate'] ?? 'No') === 'Yes';
                        $isNotDil = $pExam && ($pExam->exam_data['dilate'] ?? 'No') !== 'Yes';
                        $primeMins = $pExam ? (int) \Carbon\Carbon::parse($pExam->examined_at ?? $patient->primary_done_at)->diffInMinutes(now()) : 0;
                        $primeFmt = $primeMins < 60 ? $primeMins . 'm' : floor($primeMins / 60) . 'h' . ($primeMins % 60 > 0 ? ' ' . ($primeMins % 60) . 'm' : '');
                        $dClass = $primeMins < $wDGreen ? 'wait-green' : ($primeMins < $wDOrange ? 'wait-orange' : ($primeMins < $wDRed ? 'wait-red' : 'wait-fire'));
                        $ndClass = $primeMins < $wNdGreen ? 'wait-green' : ($primeMins < $wNdOrange ? 'wait-orange' : ($primeMins < $wNdRed ? 'wait-red' : 'wait-fire'));
                        $initials = strtoupper(substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1));
                    @endphp
                    <tr>
                        <td style="color:#94a3b8; font-size:12px; font-weight:600;">{{ $index + 1 }}</td>
                        <td><span class="tap-mrd">{{ $patient->patient_code }}</span></td>
                        <td>
                            <div class="tap-patient-cell">
                                <div class="tap-avatar">{{ $initials }}</div>
                                <div>
                                    <div class="tap-name">{{ $patient->full_name }}</div>
                                    <span
                                        class="tap-type-pill {{ $patient->type === 'phone' ? 'tap-type-phone' : 'tap-type-walkin' }}">
                                        <i class="bi bi-{{ $patient->type === 'phone' ? 'phone' : 'person-walking' }}"
                                            style="font-size:9px;"></i>
                                        {{ ucfirst($patient->type) }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="tap-meta">
                            <strong>{{ $patient->cityName ?: '—' }}</strong><br>
                            <span>{{ $patient->age }}y / {{ ucfirst($patient->gender) }}</span>
                        </td>
                        <td class="tap-slot">
                            @if($patient->contact_no)
                                {{ $patient->contact_no }}
                            @else
                                <span style="color:#cbd5e1">—</span>
                            @endif
                        </td>
                        <td style="font-size:13px; color:#334155; font-weight:500;">{{ $patient->doctor?->name ?? '—' }}</td>
                        <td>
                            @if($patient->doctor_patient_no)
                                <span
                                    class="tap-dr-index">{{ ($patient->doctor?->doctor_prefix ?? '') ? $patient->doctor->doctor_prefix . '-' . str_pad($patient->doctor_patient_no, 3, '0', STR_PAD_LEFT) : '#' . str_pad($patient->doctor_patient_no, 3, '0', STR_PAD_LEFT) }}</span>
                            @else
                                <span style="color:#cbd5e1">—</span>
                            @endif
                        </td>
                        <td>
                            @if($hasSecondaryDone)
                                <span class="tap-status-done"><i class="bi bi-check2-circle"></i> Done</span>
                            @elseif($hasPrimaryDone)
                                <span class="tap-status-primary"><i class="bi bi-arrow-repeat"></i> Primary Done</span>
                            @else
                                <span class="tap-status-waiting"><i class="bi bi-clock-history"></i> Waiting</span>
                            @endif
                        </td>
                        <td style="text-align:center">
                            @if($hasSecondaryDone)
                                <span style="color:#16a34a; font-size:11px; font-weight:700;"><i class="bi bi-check2-circle"></i>
                                    Done</span>
                            @elseif($patient->type === 'phone' && !$patient->checked_in_at)
                                <a href="{{ route('hospital.patients.checkin', ['slug' => $slug, 'patient' => $patient->id]) }}"
                                    style="display:inline-flex;align-items:center;gap:5px;padding:4px 11px;background:#1B4F72;color:#fff;border-radius:20px;font-size:.7rem;font-weight:700;text-decoration:none;box-shadow:0 0 0 2px rgba(27,79,114,.35);animation:checkin-pulse 1.4s ease-in-out infinite alternate;">
                                    <i class="bi bi-box-arrow-in-right"></i> Check In
                                </a>
                            @else
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <span class="wait-pill {{ $waitClass }}"
                                        data-wait-from="{{ ($patient->checked_in_at ?? $patient->created_at)->toIso8601String() }}"
                                        data-badge="R">
                                        <span class="wp-r">R</span>
                                        <span class="wp-time">{{ $waitFmt }}</span>
                                    </span>
                                    @if($isDilated)
                                        <span class="wait-pill {{ $dClass }}"
                                            data-wait-from="{{ \Carbon\Carbon::parse($pExam->examined_at ?? $patient->primary_done_at)->toIso8601String() }}"
                                            data-badge="D" data-thresholds="{{ $wDGreen }},{{ $wDOrange }},{{ $wDRed }}">
                                            <span class="wp-r">D</span>
                                            <span class="wp-time">{{ $primeFmt }}</span>
                                        </span>
                                    @elseif($isNotDil)
                                        <span class="wait-pill {{ $ndClass }}"
                                            data-wait-from="{{ \Carbon\Carbon::parse($pExam->examined_at ?? $patient->primary_done_at)->toIso8601String() }}"
                                            data-badge="ND" data-thresholds="{{ $wNdGreen }},{{ $wNdOrange }},{{ $wNdRed }}">
                                            <span class="wp-r" style="font-size:.58rem;">ND</span>
                                            <span class="wp-time">{{ $primeFmt }}</span>
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td style="text-align:center">
                            <a href="{{ route('hospital.patients.print', ['slug' => $slug, 'patient' => $patient->id]) }}"
                                class="tap-print-btn" title="Print">
                                <i class="bi bi-printer" style="font-size:14px;"></i>
                            </a>
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="10" class="tap-empty">
                        <i class="bi bi-inbox" style="font-size:2rem; display:block; margin-bottom:8px; opacity:.4;"></i>
                        No patients added today
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
