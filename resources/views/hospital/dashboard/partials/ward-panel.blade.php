{{--
  Ward Management home: Ward Entry Queue / Ward History rendered inline
  below the cards. Required: $slug, $wardLists.
--}}
@php
    use App\Models\Hospital\OT\OtBooking;

    $wardTabs = [
        'queue' => ['title' => 'Ward Entry Queue', 'icon' => 'bi-hourglass-split', 'empty' => 'No records available for ward workflow.'],
        'history' => ['title' => 'Ward History', 'icon' => 'bi-check-circle-fill', 'empty' => 'No ward history yet.'],
    ];

    $wardStatusMap = [
        OtBooking::STATUS_PAYMENT_VERIFIED => ['Awaiting Ward Entry', 'amber'],
        OtBooking::STATUS_IN_WARD => ['In Ward', 'blue'],
        OtBooking::STATUS_DILATED => ['Dilated', 'purple'],
        OtBooking::STATUS_READY => ['Ready for OT', 'navy'],
        OtBooking::STATUS_OPERATED => ['Operated', 'green'],
        OtBooking::STATUS_DISCHARGED => ['Discharged', 'grey'],
    ];
@endphp

<div class="acc-panel" id="wardPanel" data-default-tab="queue">
    <div class="acc-panel-head">
        <div class="acc-panel-title">
            <span class="acc-panel-icon"><i class="bi {{ $wardTabs['queue']['icon'] }}" data-acc-title-icon></i></span>
            <div>
                <h5 class="mb-0" data-acc-title>{{ $wardTabs['queue']['title'] }}</h5>
                <span class="acc-panel-sub"><span data-acc-count>{{ $wardLists['queue']->count() }}</span> patient(s)</span>
            </div>
        </div>
        <a href="{{ route('hospital.ot.ward.index', ['slug' => $slug, 'filter' => 'queue']) }}"
            class="acc-open-page" data-acc-page-link>
            Open full page <i class="bi bi-box-arrow-up-right"></i>
        </a>
    </div>

    @foreach($wardTabs as $tab => $meta)
        @php $rows = $wardLists[$tab]; @endphp
        <div class="acc-pane" data-acc-pane="{{ $tab }}"
            data-title="{{ $meta['title'] }}" data-icon="{{ $meta['icon'] }}" data-count="{{ $rows->count() }}"
            data-page-url="{{ route('hospital.ot.ward.index', ['slug' => $slug, 'filter' => $tab]) }}"
            @if($tab !== 'queue') hidden @endif>
            <div class="table-responsive">
                <table class="acc-table" data-acc-table data-empty="{{ $meta['empty'] }}" style="width:100%">
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $booking)
                            @php
                                [$statusLabel, $statusTone] = $wardStatusMap[$booking->ot_status]
                                    ?? [str((string) $booking->ot_status)->replace('_', ' ')->title(), 'grey'];
                                [$payLabel, $payTone] = match ($booking->payment_status) {
                                    'paid' => ['Paid', 'green'],
                                    'partially_paid' => ['Partially Paid', 'blue'],
                                    default => ['Pending', 'amber'],
                                };
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
                                <td><span class="acc-badge acc-tone-{{ $statusTone }}">{{ $statusLabel }}</span></td>
                                <td>
                                    <span class="acc-badge acc-tone-{{ $payTone }}">{{ $payLabel }}</span>
                                    @if($booking->payment_status === 'partially_paid')
                                        <div class="acc-sub">Balance: {{ money_code($booking->remaining_balance, 2) }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="acc-actions">
                                        <button type="button" class="acc-btn acc-btn-ghost" data-bs-toggle="modal"
                                            data-bs-target="#wardView{{ $booking->id }}">
                                            <i class="bi bi-eye-fill"></i> View
                                        </button>
                                        @if($tab === 'queue')
                                            <a href="{{ route('hospital.ot.ward.show', ['slug' => $slug, 'booking' => $booking->id]) }}"
                                                class="acc-btn acc-btn-primary">
                                                <i class="bi bi-heart-pulse"></i> Vitals &amp; Eye Drops
                                            </a>
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

@foreach($wardLists as $rows)
    @foreach($rows as $booking)
        @include('hospital.ot.ward._view-modal', ['booking' => $booking])
    @endforeach
@endforeach

@include('hospital.dashboard.partials.desk-panel-assets')
