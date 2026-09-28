{{-- Awaiting Counselling → View: registration details + primary / secondary exam progress. Required: $booking --}}
@php
    $drName = fn (?string $name) => blank($name) ? null : (preg_match('/^dr\b\.?/i', trim($name)) ? trim($name) : 'Dr. ' . trim($name));
    $patient = $booking->patient;
    $primary = $patient?->primaryExamination;
    $secondary = $patient?->secondaryExamination;
    $type = strtolower((string) ($patient?->type ?? ''));
    $typeLabel = match ($type) {
        'phone' => 'Phone Booking',
        'walkin' => 'Walk-in',
        'ot' => 'OT Appointment',
        default => $type !== '' ? ucfirst($type) : '-',
    };
    $examSteps = [
        [
            'label' => 'Primary Exam',
            'done' => $patient?->primary_done_at !== null,
            'at' => $patient?->primary_done_at ?? $primary?->examined_at,
            'by' => $drName($primary?->doctor?->name),
        ],
        [
            'label' => 'Secondary Exam',
            'done' => $patient?->secondary_done_at !== null,
            'at' => $patient?->secondary_done_at ?? $secondary?->examined_at,
            'by' => $drName($secondary?->doctor?->name),
        ],
    ];
    $fields = [
        ['MRD No.', $patient?->patient_code],
        ['Phone', $patient?->contact_no],
        ['WhatsApp', $patient?->whatsapp_no],
        ['Age / Gender', trim(($patient?->age !== null && $patient?->age !== '' ? $patient->age . 'y' : '') . ($patient?->gender ? ' / ' . ucfirst($patient->gender) : ''), ' /')],
        ['City', $patient?->cityName],
        ['Occupation', $patient?->occupation],
        ['Visit Type', $typeLabel . ($patient?->is_old_patient ? ' · Old patient' : '')],
        ['Visit Date', optional($patient?->appointment_date)->format('d M Y')],
        ['Consulting Doctor', $drName($patient?->doctor?->name)],
        ['Case Type', $patient?->caseType?->case_type],
        ['Case Fee', $patient?->case_fee !== null ? money_code((float) $patient->case_fee, 2) : null],
        ['Referred By', $patient?->referrer?->name],
        ['Registered By', $patient?->reception?->name],
        ['Registered At', optional($patient?->created_at)->format('d M Y, h:i A')],
    ];
@endphp
<div class="modal fade ot-desk-view-modal" id="otCounselPatient{{ $booking->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0">
                    <i class="bi bi-person-vcard me-2"></i>{{ $patient?->full_name ?? 'Patient' }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="ocp-section-title"><i class="bi bi-clipboard2-check"></i> Examination</div>
                <div class="ocp-steps">
                    @foreach($examSteps as $step)
                        <div class="ocp-step {{ $step['done'] ? 'is-done' : 'is-pending' }}">
                            <span class="ocp-step-icon">
                                <i class="bi {{ $step['done'] ? 'bi-check-circle-fill' : 'bi-hourglass-split' }}"></i>
                            </span>
                            <div>
                                <div class="ocp-step-label">{{ $step['label'] }}</div>
                                <div class="ocp-step-state">{{ $step['done'] ? 'Done' : 'Pending' }}</div>
                                @if($step['done'] && ($step['at'] || $step['by']))
                                    <div class="ocp-step-meta">
                                        {{ $step['at'] ? $step['at']->format('d M Y, h:i A') : '' }}
                                        @if($step['by'])
                                            {{ $step['at'] ? '·' : '' }} {{ $step['by'] }}
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="ocp-section-title"><i class="bi bi-person-lines-fill"></i> Registration Details</div>
                <div class="ot-desk-modal-grid">
                    @foreach($fields as [$label, $value])
                        @continue(blank($value))
                        <div class="ot-desk-modal-field">
                            <div class="ot-desk-modal-label">{{ $label }}</div>
                            <div class="ot-desk-modal-value">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="ocp-section-title"><i class="bi bi-hospital"></i> Surgery</div>
                <div class="ot-desk-modal-grid">
                    <div class="ot-desk-modal-field">
                        <div class="ot-desk-modal-label">Eye</div>
                        <div class="ot-desk-modal-value">{{ $booking->eye ?: '-' }}</div>
                    </div>
                    <div class="ot-desk-modal-field">
                        <div class="ot-desk-modal-label">Surgery Type</div>
                        <div class="ot-desk-modal-value">{{ $booking->ot_type ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
