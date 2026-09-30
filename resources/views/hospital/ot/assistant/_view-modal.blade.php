{{--
  OT Assistant → View: patient, surgery plan, pre-op vitals and (after OT) the surgery record + implanted lens.
  Required: $booking (with OtBooking::OT_ASSISTANT_VIEW_RELATIONS)
  Optional: $modalId (default otaView{id}), $showOperate (bool)
--}}
@php
    use App\Models\Hospital\OT\OtBooking;

    $modalId = $modalId ?? 'otaView' . $booking->id;
    $showOperate = ($showOperate ?? false) && $booking->ot_status === OtBooking::STATUS_READY;
    $patient = $booking->patient;
    $counselling = $booking->relationLoaded('counselling') ? $booking->counselling : null;
    $preOp = $booking->relationLoaded('preOp') ? $booking->preOp : null;
    $surgery = $booking->relationLoaded('surgery') ? $booking->surgery : null;
    $lensDetail = $booking->relationLoaded('lensDetail') ? $booking->lensDetail : null;
    $isReady = $booking->ot_status === OtBooking::STATUS_READY;
    $drName = fn (?string $name) => blank($name) ? null : (preg_match('/^dr\b\.?/i', trim($name)) ? trim($name) : 'Dr. ' . trim($name));
    $title = fn (?string $v) => blank($v) ? null : (string) str($v)->replace('_', ' ')->title();

    [$stageLabel, $stageTone] = match ($booking->ot_status) {
        OtBooking::STATUS_READY => ['Ready for OT', 'navy'],
        OtBooking::STATUS_OPERATED => ['Operated', 'green'],
        OtBooking::STATUS_DISCHARGED => ['Discharged', 'grey'],
        default => [$title($booking->ot_status) ?? '-', 'grey'],
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
    $plannedLens = collect([$counselling?->lens_company, $counselling?->lens_model])->filter()->implode(' ');
    $plannedLens = trim(($counselling?->lens_category ? ucfirst($counselling->lens_category) . ' · ' : '') . $plannedLens, ' ·');
    $mediclaim = $counselling?->mediclaim ?? $booking->has_mediclaim;

    $vitals = $preOp ? array_filter([
        'BP' => $preOp->bp,
        'Pulse' => $preOp->pulse,
        'RBS' => $preOp->rbs,
        'Temp' => $preOp->temperature,
        'SpO2' => $preOp->spo2 !== null ? rtrim(rtrim((string) $preOp->spo2, '0'), '.') . '%' : null,
        'HbA1c' => $preOp->hba1c,
    ], fn ($v) => ! blank($v)) : [];

    $drops = $booking->relationLoaded('dilationEntries')
        ? $booking->dilationEntries->sortBy([['administered_at', 'asc'], ['dose_number', 'asc']])->values()
        : collect();

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

    $planFields = [
        ['Eye', $booking->eye],
        ['Surgery Type', $booking->ot_type],
        ['Diagnosis', $counselling?->diagnosis],
        ['Planned Lens', $plannedLens],
        ['Lens Implantation', $counselling?->lens_implantation === null ? null : ($counselling->lens_implantation ? 'Yes' : 'No')],
        ['Package', collect([$counselling?->package_name, $counselling?->room_category ? ucfirst($counselling->room_category) . ' room' : null])->filter()->implode(' · ')],
        ['Mediclaim', $mediclaim === null ? null : ($mediclaim ? 'Yes' : 'No')],
        ['Payment', $payLabel . ' · ' . money_code($booking->total_paid, 2) . ' / ' . money_code((float) ($booking->package_amount ?? 0), 2)],
        ['OT Doctor', $drName($booking->otDoctor?->name)],
        ['OT Assistant', $booking->otAssistant?->name],
        ['OT Date', $isReady ? null : $booking->surgery_date?->format('d M Y')],
    ];

    $durationMin = null;
    if ($surgery?->start_time && $surgery?->end_time) {
        $startMin = $surgery->start_time->hour * 60 + $surgery->start_time->minute;
        $endMin = $surgery->end_time->hour * 60 + $surgery->end_time->minute;
        $durationMin = ($endMin - $startMin + 1440) % 1440;
        if ($durationMin === 0 || $durationMin > 720) {
            $durationMin = null;
        }
    }

    $surgeryFields = $surgery ? [
        ['Surgery', $surgery->surgery_name],
        ['Eye Operated', $surgery->eye_operated],
        ['Operated By', $drName($surgery->operatedBy?->name)],
        ['OT Room', $surgery->ot_room],
        ['Date', optional($surgery->surgery_at ?? $surgery->start_time)->format('d M Y')],
        ['Time', $surgery->start_time
            ? $surgery->start_time->format('h:i A') . ($surgery->end_time ? ' – ' . $surgery->end_time->format('h:i A') : '')
            : null],
        ['Duration', $durationMin !== null ? $durationMin . ' min' : null],
        ['Status', $title($surgery->surgery_status)],
        ['Complication', $title($surgery->complication_status)],
        ['Complication Notes', $surgery->complication_notes],
    ] : [];

    $lensFields = $lensDetail ? [
        ['Lens', $lensDetail->lens_name],
        ['Manufacturer', $lensDetail->manufacturer],
        ['Type', $lensDetail->lens_type],
        ['Power', $lensDetail->lens_power !== null ? rtrim(rtrim((string) $lensDetail->lens_power, '0'), '.') . ' D' : null],
        ['Axis', $lensDetail->axis !== null && (float) $lensDetail->axis !== 0.0 ? rtrim(rtrim((string) $lensDetail->axis, '0'), '.') . '°' : null],
        ['Serial No.', $lensDetail->serial_number],
    ] : [];
@endphp
<div class="modal fade ot-desk-view-modal apm-modal" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0">
                    <i class="bi bi-activity me-2"></i>Surgery Patient Details
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

                @unless($isReady)
                    <div class="apm-section-title"><i class="bi bi-clipboard2-check"></i> Surgery Record</div>
                    @if(empty(array_filter(array_column($surgeryFields, 1), fn ($v) => ! blank($v))))
                        <div class="apm-empty"><i class="bi bi-inbox"></i> Surgery record not found.</div>
                    @else
                        <div class="ot-desk-modal-grid apm-grid">
                            @foreach($surgeryFields as [$label, $value])
                                @continue(blank($value))
                                <div class="ot-desk-modal-field {{ $label === 'Complication Notes' ? 'ot-desk-modal-wide' : '' }}">
                                    <div class="ot-desk-modal-label">{{ $label }}</div>
                                    <div class="ot-desk-modal-value">{{ $value }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(! empty(array_filter(array_column($lensFields, 1), fn ($v) => ! blank($v))))
                        <div class="apm-section-title">
                            <i class="bi bi-circle-half"></i> Implanted Lens
                            @if($lensDetail?->is_implanted)
                                <span class="apm-badge apm-tone-green">Implanted</span>
                            @endif
                        </div>
                        <div class="ot-desk-modal-grid apm-grid">
                            @foreach($lensFields as [$label, $value])
                                @continue(blank($value))
                                <div class="ot-desk-modal-field">
                                    <div class="ot-desk-modal-label">{{ $label }}</div>
                                    <div class="ot-desk-modal-value">{{ $value }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endunless

                <div class="apm-section-title"><i class="bi bi-hospital"></i> Surgery Plan</div>
                <div class="ot-desk-modal-grid apm-grid">
                    @foreach($planFields as [$label, $value])
                        @continue(blank($value))
                        <div class="ot-desk-modal-field">
                            <div class="ot-desk-modal-label">{{ $label }}</div>
                            <div class="ot-desk-modal-value">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="apm-section-title">
                    <i class="bi bi-activity"></i> Pre-Op Vitals
                    @if($preOp?->pre_op_status)
                        <span class="apm-badge apm-tone-{{ $preOpTone }}">{{ $preOp->statusLabel() }}</span>
                    @endif
                </div>
                @if(empty($vitals))
                    <div class="apm-empty"><i class="bi bi-inbox"></i> Vitals not recorded.</div>
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

                <div class="apm-section-title">
                    <i class="bi bi-droplet-half"></i> Ward Medicine Log
                    @if($drops->isNotEmpty())
                        <span class="apm-badge apm-tone-blue">{{ $drops->count() }} {{ $drops->count() === 1 ? 'entry' : 'entries' }}</span>
                    @endif
                </div>
                @if($drops->isEmpty())
                    <div class="apm-empty"><i class="bi bi-inbox"></i> No ward medicines logged yet.</div>
                @else
                    <div class="table-responsive">
                        <table class="apm-table">
                            <thead>
                                <tr>
                                    <th>Dose</th>
                                    <th>Medicine</th>
                                    <th>Eye</th>
                                    <th>Date / Time</th>
                                    <th>Given By</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($drops as $drop)
                                    <tr>
                                        <td>{{ $drop->dose_number ?: '-' }}</td>
                                        <td>{{ $drop->medicine_name ?: '-' }}</td>
                                        <td>{{ $drop->eye ?: '-' }}</td>
                                        <td>{{ optional($drop->administered_at)->format('d M Y, h:i A') ?? '-' }}</td>
                                        <td>{{ $drop->administeredBy?->name ?? '-' }}</td>
                                        <td>{{ $drop->remarks ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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
            </div>
            @if($showOperate)
                @haspermission('ot_surgery_record')
                <div class="modal-footer apm-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="{{ route('hospital.ot.surgery.create', ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                        class="btn apm-primary-btn">
                        <i class="bi bi-heart-pulse me-1"></i> Operate
                    </a>
                </div>
                @endhaspermission
            @endif
        </div>
    </div>
</div>

@include('hospital.ot.partials.detail-modal-styles')
