{{--
Discharge desk → View: discharge documents (print status), invoice, surgery record,
implanted lens, surgery plan and patient details.
Required: $booking (with OtBooking::DISCHARGE_VIEW_RELATIONS), $slug
Optional: $modalId (default dcView{id})
--}}
@php
    use App\Models\Hospital\OT\OtBooking;

    $modalId = $modalId ?? 'dcView' . $booking->id;
    $patient = $booking->patient;
    $counselling = $booking->relationLoaded('counselling') ? $booking->counselling : null;
    $surgery = $booking->relationLoaded('surgery') ? $booking->surgery : null;
    $lensDetail = $booking->relationLoaded('lensDetail') ? $booking->lensDetail : null;
    $invoice = $booking->relationLoaded('invoice') ? $booking->invoice : null;
    $isDischarged = strtolower((string) $booking->ot_status) === OtBooking::STATUS_DISCHARGED;
    $printsDone = $booking->dischargePrintsDoneCount();
    $printsTotal = count(OtBooking::DISCHARGE_PRINTS);
    $drName = fn(?string $name) => blank($name) ? null : (preg_match('/^dr\b\.?/i', trim($name)) ? trim($name) : 'Dr. ' . trim($name));
    $title = fn(?string $v) => blank($v) ? null : (string) str($v)->replace('_', ' ')->title();
    $num = fn($v) => $v === null ? null : rtrim(rtrim((string) $v, '0'), '.');

    [$stageLabel, $stageTone] = $isDischarged
        ? ['Discharged', 'green']
        : ($invoice ? ['Printing ' . $printsDone . '/' . $printsTotal, 'blue'] : ['Awaiting Invoice', 'amber']);
    [$payLabel, $payTone] = match ($booking->payment_status) {
        'paid' => ['Paid', 'green'],
        'partially_paid' => ['Partially Paid', 'blue'],
        'unpriced' => ['Package Not Set', 'grey'],
        default => ['Payment Pending', 'amber'],
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

    $patientFields = [
        ['MRD No.', $patient?->patient_code],
        ['Phone', $patient?->contact_no],
        ['WhatsApp', $patient?->whatsapp_no],
        ['Age / Gender', $ageGender],
        ['City', $patient ? ($patient->cityName ?: null) : null],
        ['Visit Type', match ($type) { 'phone' => 'Phone Booking', 'walkin' => 'Walk-in', '' => null, default => ucfirst($type)}],
        ['Consulting Doctor', $drName($patient?->doctor?->name)],
        ['Registered By', $patient?->reception?->name],
    ];

    $planFields = [
        ['OT Date', $booking->surgery_date?->format('d M Y')],
        ['Eye', $booking->eye],
        ['Surgery Type', $booking->ot_type],
        ['Diagnosis', $counselling?->diagnosis],
        ['Planned Lens', $plannedLens],
        ['Package', collect([$counselling?->package_name, $counselling?->room_category ? ucfirst($counselling->room_category) . ' room' : null])->filter()->implode(' · ')],
        ['Mediclaim', $mediclaim === null ? null : ($mediclaim ? 'Yes' : 'No')],
        ['OT Doctor', $drName($booking->otDoctor?->name)],
        ['OT Assistant', $booking->otAssistant?->name],
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
        [
            'Time',
            $surgery->start_time
            ? $surgery->start_time->format('h:i A') . ($surgery->end_time ? ' – ' . $surgery->end_time->format('h:i A') : '')
            : null
        ],
        ['Duration', $durationMin !== null ? $durationMin . ' min' : null],
        ['Complication', $title($surgery->complication_status)],
        ['Complication Notes', $surgery->complication_notes],
    ] : [];

    $lensFields = $lensDetail ? [
        ['Lens', $lensDetail->lens_name],
        ['Manufacturer', $lensDetail->manufacturer],
        ['Type', $lensDetail->lens_type],
        ['Power', $lensDetail->lens_power !== null ? $num($lensDetail->lens_power) . ' D' : null],
        ['Axis', $lensDetail->axis !== null && (float) $lensDetail->axis !== 0.0 ? $num($lensDetail->axis) . '°' : null],
        ['Serial No.', $lensDetail->serial_number],
    ] : [];

    $invoiceFields = $invoice ? [
        ['Invoice No.', $invoice->invoice_number],
        ['Generated On', optional($invoice->created_at)->format('d M Y, h:i A')],
        ['Generated By', $invoice->generatedBy?->name],
        ['Follow-up Date', optional($invoice->follow_up_date)->format('d M Y')],
        ['Discharged On', $isDischarged ? optional($booking->discharged_at)->format('d M Y, h:i A') : null],
    ] : [];
    $lineItems = $invoice ? collect((array) $invoice->line_items)->filter(fn($i) => is_array($i) && (float) ($i['amount'] ?? 0) > 0) : collect();
@endphp
<div class="modal fade ot-desk-view-modal apm-modal ot-discharge-details-modal" id="{{ $modalId }}" tabindex="-1"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0">
                    <i class="bi bi-box-arrow-right me-2"></i>Discharge Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
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

                <div class="apm-amounts dcm-amounts">
                    <div class="apm-amount">
                        <div class="apm-amount-label">Package</div>
                        <div class="apm-amount-value">{{ money_code((float) ($booking->package_amount ?? 0), 2) }}</div>
                    </div>
                    <div class="apm-amount">
                        <div class="apm-amount-label">Paid</div>
                        <div class="apm-amount-value is-green">{{ money_code($booking->total_paid, 2) }}</div>
                    </div>
                    <div class="apm-amount">
                        <div class="apm-amount-label">Balance</div>
                        <div class="apm-amount-value {{ $booking->remaining_balance > 0 ? 'is-amber' : 'is-green' }}">
                            {{ money_code($booking->remaining_balance, 2) }}</div>
                    </div>
                    <div class="apm-amount">
                        <div class="apm-amount-label">Invoice Net</div>
                        <div class="apm-amount-value">{{ $invoice ? money_code((float) $invoice->net_amount, 2) : '—' }}
                        </div>
                    </div>
                </div>

                <div class="apm-section-title">
                    <i class="bi bi-printer"></i> Discharge Documents
                    @unless($isDischarged && $printsDone < $printsTotal)
                        <span
                            class="apm-badge apm-tone-{{ $printsDone === $printsTotal ? 'green' : ($printsDone > 0 ? 'blue' : 'amber') }}">
                            {{ $printsDone }} / {{ $printsTotal }} printed
                        </span>
                    @endunless
                </div>
                @unless($invoice)
                    <div class="apm-empty mb-2"><i class="bi bi-info-circle"></i> Generate the invoice first, then print all
                        {{ $printsTotal }} documents to discharge the patient.</div>
                @endunless
                <div class="dcm-docs">
                    @foreach(OtBooking::DISCHARGE_PRINTS as $docKey => $doc)
                        @php $printedAt = $booking->{$doc['column']}; @endphp
                        <div class="dcm-doc {{ $printedAt ? 'is-done' : ($isDischarged ? 'is-legacy' : '') }}">
                            <span class="dcm-doc-icon"><i
                                    class="bi {{ $printedAt ? 'bi-check-lg' : $doc['icon'] }}"></i></span>
                            <div class="dcm-doc-body">
                                <div class="dcm-doc-label">{{ $doc['label'] }}</div>
                                <div class="dcm-doc-meta">
                                    @if($printedAt)
                                        Printed {{ $printedAt->format('d M Y, h:i A') }}
                                    @elseif($isDischarged)
                                        Print not recorded (discharged before print tracking)
                                    @else
                                        Not printed yet
                                    @endif
                                </div>
                            </div>
                            @if($invoice && hospital_can($doc['permission']))
                                <a href="{{ route($doc['route'], ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                                    class="dcm-doc-btn">
                                    <i class="bi bi-printer"></i> {{ $printedAt ? 'Reprint' : 'Print' }}
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if($invoice)
                    <div class="apm-section-title"><i class="bi bi-receipt"></i> Invoice</div>
                    <div class="ot-desk-modal-grid apm-grid">
                        @foreach($invoiceFields as [$label, $value])
                            @continue(blank($value))
                            <div class="ot-desk-modal-field">
                                <div class="ot-desk-modal-label">{{ $label }}</div>
                                <div class="ot-desk-modal-value">{{ $value }}</div>
                            </div>
                        @endforeach
                    </div>
                    @if($lineItems->isNotEmpty())
                        <div class="table-responsive mt-2">
                            <table class="apm-table">
                                <thead>
                                    <tr>
                                        <th>Charge</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lineItems as $item)
                                        <tr>
                                            <td>{{ $item['head'] ?? 'Charge' }}</td>
                                            <td class="text-end">{{ money_code((float) $item['amount'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                    @if((float) $invoice->tax_amount > 0)
                                        <tr>
                                            <td>Tax</td>
                                            <td class="text-end">{{ money_code((float) $invoice->tax_amount, 2) }}</td>
                                        </tr>
                                    @endif
                                    @if((float) $invoice->discount > 0)
                                        <tr>
                                            <td>Discount</td>
                                            <td class="text-end">− {{ money_code((float) $invoice->discount, 2) }}</td>
                                        </tr>
                                    @endif
                                    <tr class="dcm-total">
                                        <td>Net Amount</td>
                                        <td class="text-end">{{ money_code((float) $invoice->net_amount, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif

                <div class="apm-section-title"><i class="bi bi-clipboard2-check"></i> Surgery Record</div>
                @if(empty(array_filter(array_column($surgeryFields, 1), fn($v) => !blank($v))))
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

                @if(!empty(array_filter(array_column($lensFields, 1), fn($v) => !blank($v))))
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
        </div>
    </div>
</div>

@include('hospital.ot.partials.detail-modal-styles')
@once
    @push('styles')
        <style>
            .ot-discharge-details-modal .modal-dialog {
                max-width: 680px;
                margin: .75rem auto;
            }

            .ot-discharge-details-modal .modal-content {
                border: 1px solid rgba(27, 79, 114, .14);
                border-radius: 18px;
                box-shadow: 0 24px 64px rgba(16, 52, 76, .22);
                overflow: hidden;
            }

            .ot-discharge-details-modal .modal-header {
                padding: .8rem 1rem;
                background: linear-gradient(135deg, #123d59 0%, #1b4f72 100%);
            }

            .ot-discharge-details-modal .modal-title {
                display: flex;
                align-items: center;
                gap: .55rem;
                font-size: 1rem;
                letter-spacing: .01em;
            }

            .ot-discharge-details-modal .modal-title i {
                color: #a9e4e6;
                margin-right: 0 !important;
            }

            .ot-discharge-details-modal .modal-body {
                padding: .85rem .95rem 1rem;
                background: #f5f9fc;
            }

            .ot-discharge-details-modal .apm-hero {
                padding: .7rem .8rem;
                border-radius: 14px;
                background: linear-gradient(135deg, #e8f4f8 0%, #ffffff 78%);
                border-color: rgba(27, 79, 114, .16);
            }

            .ot-discharge-details-modal .apm-avatar {
                width: 42px;
                height: 42px;
                border-radius: 12px;
                box-shadow: 0 6px 14px rgba(27, 79, 114, .18);
            }

            .ot-discharge-details-modal .apm-name {
                font-size: 1rem;
            }

            .ot-discharge-details-modal .apm-badge {
                padding: .3rem .65rem;
                font-size: .7rem;
            }

            .ot-discharge-details-modal .apm-amounts {
                gap: .5rem;
                margin-top: .55rem;
            }

            .ot-discharge-details-modal .apm-amount {
                padding: .5rem .6rem;
                border-radius: 10px;
                box-shadow: 0 3px 10px rgba(27, 79, 114, .04);
            }

            .ot-discharge-details-modal .apm-amount-value {
                font-size: .95rem;
            }

            .ot-discharge-details-modal .apm-section-title {
                margin: .8rem 0 .35rem;
                font-size: .69rem;
            }

            .ot-discharge-details-modal .apm-grid {
                gap: .5rem;
            }

            .ot-discharge-details-modal .apm-grid .ot-desk-modal-field {
                padding: .5rem .6rem;
                border-radius: 10px;
                box-shadow: 0 3px 10px rgba(27, 79, 114, .025);
            }

            .ot-discharge-details-modal .apm-grid .ot-desk-modal-value {
                font-size: .82rem;
                line-height: 1.3;
            }

            .dcm-amounts {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .dcm-docs {
                display: grid;
                gap: .55rem;
            }

            .dcm-doc {
                display: flex;
                align-items: center;
                gap: .6rem;
                padding: .55rem .7rem;
                border-radius: 10px;
                background: #fff;
                border: 1px solid rgba(27, 79, 114, .12);
            }

            .dcm-doc.is-done {
                background: #F3FBF7;
                border-color: #A9E4C4;
            }

            .dcm-doc-icon {
                width: 34px;
                height: 34px;
                border-radius: 10px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: #FFF6DF;
                color: #92660A;
                flex: 0 0 auto;
                font-size: 1rem;
            }

            .dcm-doc.is-done .dcm-doc-icon {
                background: #1E8E5A;
                color: #fff;
            }

            .dcm-doc-body {
                flex: 1 1 auto;
                min-width: 0;
            }

            .dcm-doc-label {
                font-weight: 800;
                color: #1B4F72;
                font-size: .82rem;
            }

            .dcm-doc-meta {
                font-size: .7rem;
                font-weight: 600;
                color: #92660A;
            }

            .dcm-doc.is-done .dcm-doc-meta {
                color: #1E8E5A;
            }

            .dcm-doc.is-legacy .dcm-doc-icon {
                background: #eef2f6;
                color: #475569;
            }

            .dcm-doc.is-legacy .dcm-doc-meta {
                color: #64748b;
            }

            .dcm-doc-btn {
                display: inline-flex;
                align-items: center;
                gap: .35rem;
                padding: .3rem .6rem;
                border-radius: 7px;
                border: 1px solid rgba(27, 79, 114, .2);
                background: #EBF5FB;
                color: #1B4F72;
                font-weight: 700;
                font-size: .72rem;
                text-decoration: none;
                white-space: nowrap;
            }

            .dcm-doc-btn:hover {
                background: #1B4F72;
                border-color: #1B4F72;
                color: #fff;
            }

            .apm-table .dcm-total td {
                background: #EBF5FB;
                font-weight: 800;
            }

            .ot-discharge-details-modal .apm-table th,
            .ot-discharge-details-modal .apm-table td {
                padding: .45rem .6rem;
            }

            .ot-discharge-details-modal .apm-table th {
                font-size: .64rem;
                background: #e8f4f8;
            }

            .ot-discharge-details-modal .apm-table td {
                font-size: .78rem;
            }

            @media (max-width: 767.98px) {
                .dcm-amounts {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 575.98px) {
                .ot-discharge-details-modal .modal-dialog {
                    margin: .5rem .75rem;
                }

                .ot-discharge-details-modal .modal-body {
                    padding: .75rem;
                }

                .ot-discharge-details-modal .apm-hero {
                    align-items: flex-start;
                }

                .ot-discharge-details-modal .dcm-amounts {
                    grid-template-columns: 1fr;
                }

                .ot-discharge-details-modal .dcm-doc {
                    align-items: flex-start;
                }

                .ot-discharge-details-modal .dcm-doc-btn {
                    margin-left: auto;
                }

                .ot-discharge-details-modal .apm-table {
                    min-width: 520px;
                }
            }
        </style>
    @endpush
@endonce