{{--
  Accountant home: the three card lists (Payment Queue / Refunds / Completed)
  rendered inline below the cards. Required: $slug, $accountantLists.
--}}
@php
    use App\Models\Hospital\OT\OtBooking;

    $accTabs = [
        'queue' => ['title' => 'Payment Queue', 'icon' => 'bi-hourglass-split', 'empty' => 'No bookings in payment queue.'],
        'refunds' => ['title' => 'Refund Queue', 'icon' => 'bi-arrow-counterclockwise', 'empty' => 'No surgery-refused patients awaiting refund.'],
        'history' => ['title' => 'Completed Payments', 'icon' => 'bi-check-circle-fill', 'empty' => 'No completed payments yet.'],
    ];
@endphp

<div class="acc-panel" id="accPanel" data-default-tab="queue">
    <div class="acc-panel-head">
        <div class="acc-panel-title">
            <span class="acc-panel-icon"><i class="bi {{ $accTabs['queue']['icon'] }}" data-acc-title-icon></i></span>
            <div>
                <h5 class="mb-0" data-acc-title>{{ $accTabs['queue']['title'] }}</h5>
                <span class="acc-panel-sub"><span data-acc-count>{{ $accountantLists['queue']->count() }}</span> patient(s)</span>
            </div>
        </div>
        <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug, 'filter' => 'queue']) }}"
            class="acc-open-page" data-acc-page-link>
            Open full page <i class="bi bi-box-arrow-up-right"></i>
        </a>
    </div>

    @foreach($accTabs as $tab => $meta)
        @php $rows = $accountantLists[$tab]; @endphp
        <div class="acc-pane" data-acc-pane="{{ $tab }}"
            data-title="{{ $meta['title'] }}" data-icon="{{ $meta['icon'] }}" data-count="{{ $rows->count() }}"
            data-page-url="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug, 'filter' => $tab]) }}"
            @if($tab !== 'queue') hidden @endif>
            <div class="table-responsive">
                <table class="acc-table" data-acc-table data-empty="{{ $meta['empty'] }}" style="width:100%">
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Phone</th>
                            <th>Package Amount</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $booking)
                            @php
                                $isRefused = $tab === 'refunds' || $booking->ot_status === OtBooking::STATUS_SURGERY_REFUSED;
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
                                $lastPayment = $booking->payments->sortByDesc('id')->first();
                            @endphp
                            <tr>
                                <td>
                                    <div class="acc-patient">
                                        <span class="acc-avatar">{{ strtoupper(mb_substr($booking->patient?->first_name ?? '?', 0, 1)) }}</span>
                                        <div>
                                            <div class="acc-name">{{ $booking->patient?->full_name ?? '-' }}</div>
                                            <div class="acc-code">{{ $booking->patient?->patient_code ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $booking->patient?->contact_no ?? '-' }}</td>
                                <td>
                                    <span class="acc-amount">{{ money_code((float) ($booking->package_amount ?? 0), 2) }}</span>
                                    @if($isRefused)
                                        <div class="acc-sub">Paid {{ money_code($booking->total_paid, 2) }} · Refunded {{ money_code($booking->total_refunded, 2) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="acc-badge acc-tone-{{ $statusTone }}">{{ $statusLabel }}</span>
                                    @if($paymentStatus === 'partially_paid' && !$isRefused)
                                        <div class="acc-sub">Balance: {{ money_code($booking->remaining_balance, 2) }}</div>
                                    @endif
                                    @if($tab === 'refunds' && $booking->refundable_balance > 0)
                                        <div class="acc-sub">To return: {{ money_code($booking->refundable_balance, 2) }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="acc-actions">
                                        <button type="button" class="acc-btn acc-btn-ghost" data-bs-toggle="modal"
                                            data-bs-target="#accView{{ $booking->id }}">
                                            <i class="bi bi-eye-fill"></i> View
                                        </button>
                                        @if($isRefused)
                                            @if($booking->refundable_balance > 0)
                                                <a href="{{ route('hospital.ot.refunds.create', ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                                                    class="acc-btn acc-btn-danger">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Full Refund
                                                </a>
                                            @else
                                                <span class="acc-note"><i class="bi bi-check2-circle"></i> Refund Done</span>
                                            @endif
                                        @elseif($tab === 'queue' && !in_array($paymentStatus, ['paid', 'unpriced'], true))
                                            @haspermission('ot_payment_record')
                                            <a href="{{ route('hospital.ot.payments.create', ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                                                class="acc-btn acc-btn-primary">
                                                <i class="bi bi-plus-circle"></i>
                                                {{ $paymentStatus === 'partially_paid' ? 'Add Balance' : 'Add Payment' }}
                                            </a>
                                            @endhaspermission
                                        @elseif($tab === 'queue' && $paymentStatus === 'unpriced')
                                            <span class="acc-note"><i class="bi bi-exclamation-circle"></i> Package Not Set</span>
                                        @endif
                                        @if(!$isRefused && $lastPayment)
                                            @haspermission('ot_bill_print')
                                            <a href="{{ route('hospital.ot.payments.receipt', ['slug' => $slug, 'paymentId' => $lastPayment->id]) }}"
                                                class="acc-btn acc-btn-ghost" target="_blank">
                                                <i class="bi bi-printer"></i> Receipt
                                            </a>
                                            @endhaspermission
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>

@foreach($accountantLists as $rows)
    @foreach($rows as $booking)
        @include('hospital.ot.accountant._payment-view-modal', [
            'booking' => $booking,
            'modalId' => 'accView' . $booking->id,
        ])
    @endforeach
@endforeach

@include('hospital.dashboard.partials.desk-panel-assets')
