{{--
  OT Assistant home: Ready-for-surgery queue / Surgery history rendered inline
  below the cards. Required: $slug, $otAssistantLists, $otAssistantSeeAll.
--}}
@php
    use App\Models\Hospital\OT\OtBooking;

    $otaTabs = [
        'queue' => ['title' => 'Ready for Surgery', 'icon' => 'bi-hourglass-split', 'empty' => 'No bookings ready for surgery.'],
        'history' => ['title' => 'Surgery History', 'icon' => 'bi-check-circle-fill', 'empty' => 'No surgery history yet.'],
    ];

    $otaStatusMap = [
        OtBooking::STATUS_READY => ['Ready for OT', 'navy'],
        OtBooking::STATUS_OPERATED => ['Operated', 'green'],
        OtBooking::STATUS_DISCHARGED => ['Discharged', 'grey'],
    ];
@endphp

<div class="acc-panel" id="otAssistantPanel" data-default-tab="queue">
    <div class="acc-panel-head">
        <div class="acc-panel-title">
            <span class="acc-panel-icon"><i class="bi {{ $otaTabs['queue']['icon'] }}" data-acc-title-icon></i></span>
            <div>
                <h5 class="mb-0" data-acc-title>{{ $otaTabs['queue']['title'] }}</h5>
                <span class="acc-panel-sub"><span data-acc-count>{{ $otAssistantLists['queue']->count() }}</span> patient(s)</span>
            </div>
        </div>
        <a href="{{ route('hospital.ot.assistant.dashboard', ['slug' => $slug, 'filter' => 'queue']) }}"
            class="acc-open-page" data-acc-page-link>
            Open full page <i class="bi bi-box-arrow-up-right"></i>
        </a>
    </div>

    @foreach($otaTabs as $tab => $meta)
        @php $rows = $otAssistantLists[$tab]; @endphp
        <div class="acc-pane" data-acc-pane="{{ $tab }}"
            data-title="{{ $meta['title'] }}" data-icon="{{ $meta['icon'] }}" data-count="{{ $rows->count() }}"
            data-page-url="{{ route('hospital.ot.assistant.dashboard', ['slug' => $slug, 'filter' => $tab]) }}"
            @if($tab !== 'queue') hidden @endif>
            <div class="table-responsive">
                <table class="acc-table" data-acc-table data-empty="{{ $meta['empty'] }}" style="width:100%">
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            @if($otAssistantSeeAll)
                                <th>Surgeon</th>
                            @endif
                            @if($tab === 'history')
                                <th>OT Date</th>
                            @endif
                            <th>Surgery Type</th>
                            <th>Package</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $booking)
                            @php
                                [$statusLabel, $statusTone] = $otaStatusMap[$booking->ot_status]
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
                                            <div class="acc-code">{{ $booking->patient?->contact_no ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                @if($otAssistantSeeAll)
                                    <td>{{ $booking->otDoctor?->name ? 'Dr. '.$booking->otDoctor->name : '-' }}</td>
                                @endif
                                @if($tab === 'history')
                                    <td>{{ optional($booking->surgery_date)->format('d M Y') ?? '-' }}</td>
                                @endif
                                <td>{{ $booking->ot_type ?? '-' }}</td>
                                <td><span class="acc-amount">{{ money_code((float) ($booking->package_amount ?? 0), 2) }}</span></td>
                                <td><span class="acc-badge acc-tone-{{ $payTone }}">{{ $payLabel }}</span></td>
                                <td><span class="acc-badge acc-tone-{{ $statusTone }}">{{ $statusLabel }}</span></td>
                                <td class="text-end">
                                    <div class="acc-actions">
                                        <button type="button" class="acc-btn acc-btn-ghost" data-bs-toggle="modal"
                                            data-bs-target="#otaView{{ $booking->id }}">
                                            <i class="bi bi-eye-fill"></i> View
                                        </button>
                                        @if($tab === 'queue')
                                            @haspermission('ot_surgery_record')
                                            <a href="{{ route('hospital.ot.surgery.create', ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                                                class="acc-btn acc-btn-primary">
                                                <i class="bi bi-heart-pulse"></i> Operate
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

@foreach($otAssistantLists as $rows)
    @foreach($rows as $booking)
        @include('hospital.ot.assistant._view-modal', [
            'booking' => $booking,
            'modalId' => 'otaView' . $booking->id,
            'showOperate' => true,
        ])
    @endforeach
@endforeach

@include('hospital.dashboard.partials.desk-panel-assets')
