{{--
  Accountant → View: patient registration details + OT / counselling + payment breakdown.
  Required: $booking (with patient, patient.masterCity/location/doctor/reception, counselling.counsellor, payments, refunds, otDoctor)
  Optional: $modalId (default otaDetailModal{id})
--}}
@php
    use App\Models\Hospital\OT\OtBooking;

    $modalId = $modalId ?? 'otaDetailModal' . $booking->id;
    $patient = $booking->patient;
    $counselling = $booking->relationLoaded('counselling') ? $booking->counselling : null;
    $drName = fn (?string $name) => blank($name) ? null : (preg_match('/^dr\b\.?/i', trim($name)) ? trim($name) : 'Dr. ' . trim($name));
    $title = fn (?string $v) => blank($v) ? null : (string) str($v)->replace('_', ' ')->title();

    $isRefused = $booking->ot_status === OtBooking::STATUS_SURGERY_REFUSED;
    $paymentStatus = $booking->payment_status;
    [$statusLabel, $statusTone] = match ($paymentStatus) {
        'paid' => ['Payment Completed', 'green'],
        'partially_paid' => ['Partially Paid', 'blue'],
        'unpriced' => ['Package Not Set', 'grey'],
        default => ['Payment Pending', 'amber'],
    };
    if ($isRefused) {
        [$statusLabel, $statusTone] = $booking->isFullyRefunded()
            ? ['Fully Refunded', 'green']
            : ($booking->refundable_balance > 0 ? ['Refund Pending', 'red'] : ['Surgery Refused', 'grey']);
    }

    $type = strtolower((string) ($patient?->type ?? ''));
    $ageGender = trim(
        ($patient?->age !== null && $patient?->age !== '' ? $patient->age . 'y' : '')
        . ($patient?->gender ? ' / ' . ucfirst($patient->gender) : ''),
        ' /'
    );
    $cityName = $patient ? ($patient->cityName ?: null) : null;
    $lens = collect([$counselling?->lens_company, $counselling?->lens_model])->filter()->implode(' ');
    $lens = trim(($counselling?->lens_category ? ucfirst($counselling->lens_category) . ' · ' : '') . $lens, ' ·');
    $package = collect([$counselling?->package_name, $counselling?->room_category ? ucfirst($counselling->room_category) . ' room' : null])->filter()->implode(' · ');
    $mediclaim = $counselling?->mediclaim ?? $booking->has_mediclaim;

    $patientFields = [
        ['MRD No.', $patient?->patient_code],
        ['Phone', $patient?->contact_no],
        ['WhatsApp', $patient?->whatsapp_no],
        ['Age / Gender', $ageGender],
        ['City', $cityName],
        ['Visit Type', match ($type) { 'phone' => 'Phone Booking', 'walkin' => 'Walk-in', '' => null, default => ucfirst($type) }],
        ['Visit Date', optional($patient?->appointment_date)->format('d M Y')],
        ['Consulting Doctor', $drName($patient?->doctor?->name)],
        ['Registered By', $patient?->reception?->name],
    ];

    $otFields = [
        ['OT Date', $booking->surgery_date?->format('d M Y')],
        ['Eye', $booking->eye],
        ['Surgery Type', $booking->ot_type],
        ['Diagnosis', $counselling?->diagnosis],
        ['Package', $package],
        ['Lens', $lens],
        ['Mediclaim', $mediclaim === null ? null : ($mediclaim ? 'Yes' : 'No')],
        ['OT Doctor', $drName($booking->otDoctor?->name)],
        ['Counselled By', $counselling?->counsellor?->name
            ? $counselling->counsellor->name . ($counselling->counselled_at ? ' · ' . $counselling->counselled_at->format('d M Y') : '')
            : null],
    ];

    $payments = $booking->relationLoaded('payments') ? $booking->payments->sortBy('id') : collect();
@endphp
<div class="modal fade ot-desk-view-modal apm-modal ot-payment-details-modal" id="{{ $modalId }}" tabindex="-1"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0">
                    <i class="bi bi-receipt me-2"></i>OT Payment Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="apm-hero">
                    <div class="apm-hero-main">
                        <span class="apm-avatar">{{ strtoupper(mb_substr($patient?->first_name ?? '?', 0, 1)) }}</span>
                        <div class="min-w-0">
                            <div class="apm-name">{{ $patient?->full_name ?? 'Patient' }}</div>
                            <div class="apm-sub">Booking #{{ $booking->id }} · {{ $title($booking->ot_status) }}</div>
                        </div>
                    </div>
                    <span class="apm-badge apm-tone-{{ $statusTone }}">{{ $statusLabel }}</span>
                </div>

                <div class="apm-amounts">
                    <div class="apm-amount">
                        <div class="apm-amount-label">Package Amount</div>
                        <div class="apm-amount-value">{{ money_code((float) ($booking->package_amount ?? 0), 2) }}</div>
                    </div>
                    <div class="apm-amount">
                        <div class="apm-amount-label">Paid</div>
                        <div class="apm-amount-value is-green">{{ money_code($booking->total_paid, 2) }}</div>
                    </div>
                    @if($isRefused)
                        <div class="apm-amount">
                            <div class="apm-amount-label">Refunded</div>
                            <div class="apm-amount-value is-red">{{ money_code($booking->total_refunded, 2) }}</div>
                        </div>
                    @else
                        <div class="apm-amount">
                            <div class="apm-amount-label">Balance</div>
                            <div class="apm-amount-value {{ $booking->remaining_balance > 0 ? 'is-amber' : 'is-green' }}">{{ money_code($booking->remaining_balance, 2) }}</div>
                        </div>
                    @endif
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

                <div class="apm-section-title"><i class="bi bi-hospital"></i> Surgery &amp; Counselling</div>
                <div class="ot-desk-modal-grid apm-grid">
                    @foreach($otFields as [$label, $value])
                        @continue(blank($value))
                        <div class="ot-desk-modal-field">
                            <div class="ot-desk-modal-label">{{ $label }}</div>
                            <div class="ot-desk-modal-value">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="apm-section-title"><i class="bi bi-cash-stack"></i> Payments</div>
                @if($payments->isEmpty())
                    <div class="apm-empty"><i class="bi bi-inbox"></i> No payment recorded yet.</div>
                @else
                    <div class="table-responsive">
                        <table class="apm-table">
                            <thead>
                                <tr>
                                    <th>Receipt</th>
                                    <th>Date</th>
                                    <th>Mode</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payments as $payment)
                                    <tr>
                                        <td>{{ $payment->receipt_number ?: '-' }}</td>
                                        <td>{{ optional($payment->paid_at ?? $payment->created_at)->format('d M Y, h:i A') ?? '-' }}</td>
                                        <td>{{ $title($payment->payment_mode) ?? '-' }}</td>
                                        <td class="text-end">{{ money_code((float) $payment->package_amount, 2) }}</td>
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

@once
    @push('styles')
        <style>
            .ot-payment-details-modal .modal-dialog {
                max-width: 640px;
                margin: .75rem auto;
            }

            .ot-payment-details-modal .modal-content {
                border: 1px solid rgba(27, 79, 114, .14);
                border-radius: 18px;
                box-shadow: 0 24px 64px rgba(16, 52, 76, .22);
                overflow: hidden;
            }

            .ot-payment-details-modal .modal-header {
                padding: .8rem 1rem;
                background: linear-gradient(135deg, #123d59 0%, #1b4f72 100%);
            }

            .ot-payment-details-modal .modal-title {
                display: flex;
                align-items: center;
                gap: .55rem;
                font-size: 1rem;
                letter-spacing: .01em;
            }

            .ot-payment-details-modal .modal-title i {
                color: #a9e4e6;
                margin-right: 0 !important;
            }

            .ot-payment-details-modal .modal-body {
                padding: .8rem .9rem .95rem;
                background: #f5f9fc;
            }

            .ot-payment-details-modal .apm-hero {
                padding: .7rem .8rem;
                border-radius: 14px;
                background: linear-gradient(135deg, #e8f4f8 0%, #ffffff 78%);
                border-color: rgba(27, 79, 114, .16);
            }

            .ot-payment-details-modal .apm-avatar {
                width: 42px;
                height: 42px;
                border-radius: 12px;
                background: #1b4f72;
                box-shadow: 0 6px 14px rgba(27, 79, 114, .18);
            }

            .ot-payment-details-modal .apm-name {
                font-size: 1rem;
            }

            .ot-payment-details-modal .apm-badge {
                padding: .32rem .7rem;
                font-size: .72rem;
            }

            .ot-payment-details-modal .apm-amounts {
                gap: .6rem;
                margin-top: .55rem;
            }

            .ot-payment-details-modal .apm-amount {
                padding: .55rem .65rem;
                border-radius: 11px;
                box-shadow: 0 4px 12px rgba(27, 79, 114, .04);
            }

            .ot-payment-details-modal .apm-amount-value {
                font-size: 1rem;
            }

            .ot-payment-details-modal .apm-section-title {
                margin-top: .75rem;
                margin-bottom: .35rem;
                font-size: .7rem;
            }

            .ot-payment-details-modal .apm-grid {
                gap: .5rem;
            }

            .ot-payment-details-modal .apm-grid .ot-desk-modal-field {
                padding: .5rem .6rem;
                border-radius: 10px;
                box-shadow: 0 3px 10px rgba(27, 79, 114, .025);
            }

            .ot-payment-details-modal .apm-grid .ot-desk-modal-value {
                font-size: .84rem;
                line-height: 1.3;
            }

            .ot-payment-details-modal .apm-table {
                border-radius: 10px;
                box-shadow: 0 4px 14px rgba(27, 79, 114, .05);
            }

            .ot-payment-details-modal .apm-table th {
                background: #e8f4f8;
                padding: .45rem .6rem;
                font-size: .66rem;
            }

            .ot-payment-details-modal .apm-table td {
                padding: .45rem .6rem;
                font-size: .8rem;
            }

            @media (max-width: 575.98px) {
                .ot-payment-details-modal .modal-dialog {
                    margin: .5rem .75rem;
                }

                .ot-payment-details-modal .modal-body {
                    padding: .8rem;
                }

                .ot-payment-details-modal .apm-hero {
                    align-items: flex-start;
                }

                .ot-payment-details-modal .apm-amounts {
                    grid-template-columns: 1fr;
                }

                .ot-payment-details-modal .apm-table {
                    min-width: 560px;
                }
            }
        </style>
    @endpush
@endonce
