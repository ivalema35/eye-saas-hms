{{--
  Ward → View: patient details, surgery / counselling, pre-op vitals and the eye-drop register.
  Required: $booking (with patient.*, counselling, preOp, dilationEntries.administeredBy, payments, otDoctor, otAssistant)
  Optional: $modalId (default wardView{id})
--}}
@php
    use App\Models\Hospital\OT\OtBooking;

    $modalId = $modalId ?? 'wardView' . $booking->id;
    $patient = $booking->patient;
    $counselling = $booking->relationLoaded('counselling') ? $booking->counselling : null;
    $preOp = $booking->relationLoaded('preOp') ? $booking->preOp : null;
    $drops = $booking->relationLoaded('dilationEntries')
        ? $booking->dilationEntries->sortBy([['administered_at', 'asc'], ['dose_number', 'asc']])
        : collect();
    $drName = fn (?string $name) => blank($name) ? null : (preg_match('/^dr\b\.?/i', trim($name)) ? trim($name) : 'Dr. ' . trim($name));

    [$stageLabel, $stageTone] = match ($booking->ot_status) {
        OtBooking::STATUS_PAYMENT_VERIFIED => ['Awaiting Ward Entry', 'amber'],
        OtBooking::STATUS_IN_WARD => ['In Ward', 'blue'],
        OtBooking::STATUS_DILATED => ['Dilated', 'purple'],
        OtBooking::STATUS_READY => ['Ready for OT', 'navy'],
        OtBooking::STATUS_OPERATED => ['Operated', 'green'],
        OtBooking::STATUS_DISCHARGED => ['Discharged', 'grey'],
        default => [(string) str((string) $booking->ot_status)->replace('_', ' ')->title(), 'grey'],
    };
    [$payLabel, $payTone] = match ($booking->payment_status) {
        'paid' => ['Paid', 'green'],
        'partially_paid' => ['Partially Paid', 'blue'],
        'unpriced' => ['Package Not Set', 'grey'],
        default => ['Payment Pending', 'amber'],
    };
    $preOpTone = match ($preOp?->pre_op_status) {
        'ready_for_surgery' => 'green',
        'preparing' => 'blue',
        'hold' => 'amber',
        'complicated', 'not_fit' => 'red',
        default => 'grey',
    };

    $type = strtolower((string) ($patient?->type ?? ''));
    $ageGender = trim(
        ($patient?->age !== null && $patient?->age !== '' ? $patient->age . 'y' : '')
        . ($patient?->gender ? ' / ' . ucfirst($patient->gender) : ''),
        ' /'
    );
    $lens = collect([$counselling?->lens_company, $counselling?->lens_model])->filter()->implode(' ');
    $lens = trim(($counselling?->lens_category ? ucfirst($counselling->lens_category) . ' · ' : '') . $lens, ' ·');
    $mediclaim = $counselling?->mediclaim ?? $booking->has_mediclaim;

    $vitals = $preOp ? array_filter([
        'BP' => $preOp->bp,
        'Pulse' => $preOp->pulse,
        'RBS' => $preOp->rbs,
        'Temp' => $preOp->temperature,
        'SpO2' => $preOp->spo2 !== null ? rtrim(rtrim((string) $preOp->spo2, '0'), '.') . '%' : null,
        'HbA1c' => $preOp->hba1c,
    ], fn ($v) => ! blank($v)) : [];

    $patientFields = [
        ['MRD No.', $patient?->patient_code],
        ['Phone', $patient?->contact_no],
        ['WhatsApp', $patient?->whatsapp_no],
        ['Age / Gender', $ageGender],
        ['City', $patient ? ($patient->cityName ?: null) : null],
        ['Visit Type', match ($type) { 'phone' => 'Phone Booking', 'walkin' => 'Walk-in', '' => null, default => ucfirst($type) }],
        ['Consulting Doctor', $drName($patient?->doctor?->name)],
        ['Registered By', $patient?->reception?->name],
    ];

    $otFields = [
        ['Eye', $booking->eye],
        ['Surgery Type', $booking->ot_type],
        ['Diagnosis', $counselling?->diagnosis],
        ['Lens', $lens],
        ['Package', collect([$counselling?->package_name, $counselling?->room_category ? ucfirst($counselling->room_category) . ' room' : null])->filter()->implode(' · ')],
        ['Mediclaim', $mediclaim === null ? null : ($mediclaim ? 'Yes' : 'No')],
        ['Payment', $payLabel . ' · ' . money_code($booking->total_paid, 2) . ' / ' . money_code((float) ($booking->package_amount ?? 0), 2)],
        ['OT Doctor', $drName($booking->otDoctor?->name)],
        ['OT Assistant', $booking->otAssistant?->name],
        ['OT Date', $booking->surgery_date?->format('d M Y')],
    ];
@endphp
<div class="modal fade ot-desk-view-modal apm-modal" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0">
                    <i class="bi bi-heart-pulse me-2"></i>Ward Patient Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="apm-hero">
                    <div class="apm-hero-main">
                        <span class="apm-avatar">{{ strtoupper(mb_substr($patient?->first_name ?? '?', 0, 1)) }}</span>
                        <div class="min-w-0">
                            <div class="apm-name">{{ $patient?->full_name ?? 'Patient' }}</div>
                            <div class="apm-sub">
                                Booking #{{ $booking->id }}
                                @if($booking->eye) · Eye {{ $booking->eye }} @endif
                                @if($booking->ot_type) · {{ $booking->ot_type }} @endif
                            </div>
                        </div>
                    </div>
                    <div class="apm-badges">
                        <span class="apm-badge apm-tone-{{ $stageTone }}">{{ $stageLabel }}</span>
                        <span class="apm-badge apm-tone-{{ $payTone }}">{{ $payLabel }}</span>
                    </div>
                </div>

                <div class="apm-section-title">
                    <i class="bi bi-activity"></i> Pre-Op Vitals
                    @if($preOp?->pre_op_status)
                        <span class="apm-badge apm-tone-{{ $preOpTone }}">{{ $preOp->statusLabel() }}</span>
                    @endif
                </div>
                @if(empty($vitals))
                    <div class="apm-empty"><i class="bi bi-inbox"></i> Vitals not recorded yet.</div>
                @else
                    <div class="apm-vitals">
                        @foreach($vitals as $vLabel => $vValue)
                            <div class="apm-amount">
                                <div class="apm-amount-label">{{ $vLabel }}</div>
                                <div class="apm-amount-value">{{ $vValue }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="apm-section-title"><i class="bi bi-person-lines-fill"></i> Patient Details</div>
                <div class="ot-desk-modal-grid apm-grid">
                    @foreach($patientFields as [$label, $value])
                        @continue(blank($value))
                        <div class="ot-desk-modal-field">
                            <div class="ot-desk-modal-label">{{ $label }}</div>
                            <div class="ot-desk-modal-value">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="apm-section-title"><i class="bi bi-hospital"></i> Surgery &amp; OT</div>
                <div class="ot-desk-modal-grid apm-grid">
                    @foreach($otFields as [$label, $value])
                        @continue(blank($value))
                        <div class="ot-desk-modal-field">
                            <div class="ot-desk-modal-label">{{ $label }}</div>
                            <div class="ot-desk-modal-value">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="apm-section-title">
                    <i class="bi bi-droplet-half"></i> Eye Drop Register
                    @if($drops->isNotEmpty())
                        <span class="apm-badge apm-tone-blue">{{ $drops->count() }} {{ $drops->count() === 1 ? 'entry' : 'entries' }}</span>
                    @endif
                </div>
                @if($drops->isEmpty())
                    <div class="apm-empty"><i class="bi bi-inbox"></i> No eye drops given yet.</div>
                @else
                    <div class="table-responsive">
                        <table class="apm-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Medicine</th>
                                    <th>Eye</th>
                                    <th>Dose</th>
                                    <th>Drops</th>
                                    <th>Given By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($drops as $drop)
                                    <tr>
                                        <td>{{ optional($drop->administered_at)->format('d M, h:i A') ?? '-' }}</td>
                                        <td>{{ $drop->medicine_name ?: '-' }}</td>
                                        <td>{{ $drop->eye ?: '-' }}</td>
                                        <td>{{ $drop->dose_number ?: '-' }}</td>
                                        <td>{{ $drop->drops_count ?: '-' }}</td>
                                        <td>{{ $drop->administeredBy?->name ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@include('hospital.ot.partials.detail-modal-styles')
