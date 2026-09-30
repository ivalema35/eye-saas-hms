{{--
  Counselling home: Awaiting Counselling / Counselled Patients rendered inline
  below the cards. Required: $slug, $counsellingLists.
--}}
@php
    use App\Models\Hospital\OT\OtBooking;

    $ccTabs = [
        'queue' => ['title' => 'Awaiting Counselling', 'icon' => 'bi-hourglass-split', 'empty' => 'No bookings awaiting counselling.'],
        'history' => ['title' => 'Counselled Patients', 'icon' => 'bi-check-circle-fill', 'empty' => 'No counselled patients yet.'],
        'payments' => ['title' => 'Payment Status', 'icon' => 'bi-shield-check', 'empty' => 'No bookings paid yet.'],
    ];
@endphp

<div class="acc-panel" id="counsellingPanel" data-default-tab="queue">
    <div class="acc-panel-head">
        <div class="acc-panel-title">
            <span class="acc-panel-icon"><i class="bi {{ $ccTabs['queue']['icon'] }}" data-acc-title-icon></i></span>
            <div>
                <h5 class="mb-0" data-acc-title>{{ $ccTabs['queue']['title'] }}</h5>
                <span class="acc-panel-sub"><span data-acc-count>{{ $counsellingLists['queue']->count() }}</span> patient(s)</span>
            </div>
        </div>
        <a href="{{ route('hospital.ot.counsellor.dashboard', ['slug' => $slug, 'filter' => 'queue']) }}"
            class="acc-open-page" data-acc-page-link>
            Open full page <i class="bi bi-box-arrow-up-right"></i>
        </a>
    </div>

    @foreach($ccTabs as $tab => $meta)
        @php $rows = $counsellingLists[$tab]; @endphp
        <div class="acc-pane" data-acc-pane="{{ $tab }}"
            data-title="{{ $meta['title'] }}" data-icon="{{ $meta['icon'] }}" data-count="{{ $rows->count() }}"
            data-page-url="{{ route('hospital.ot.counsellor.dashboard', ['slug' => $slug, 'filter' => $tab === 'payments' ? 'queue' : $tab]) }}"
            @if($tab !== 'queue') hidden @endif>
            <div class="table-responsive">
                <table class="acc-table" data-acc-table data-empty="{{ $meta['empty'] }}" style="width:100%">
                    @if($tab === 'payments')
                        <thead>
                            <tr>
                                <th>Patient Name</th>
                                <th>Phone</th>
                                <th>Package Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $booking)
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
                                    <td>{{ money_code((float) ($booking->package_amount ?? 0), 2) }}</td>
                                    <td>
                                        @if($booking->ot_status === OtBooking::STATUS_PAID)
                                            <span class="acc-badge acc-tone-amber">Paid</span>
                                        @else
                                            <span class="acc-badge acc-tone-green">Paid</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    @else
                        <thead>
                            <tr>
                                <th>Patient Name</th>
                                <th>Phone</th>
                                <th>Eye</th>
                                <th>Surgery Type</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $booking)
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
                                    <td>{{ $booking->eye ?: '-' }}</td>
                                    <td>{{ $booking->ot_type ?: '-' }}</td>
                                    <td>
                                        @if($tab === 'queue' && $booking->ot_status === OtBooking::STATUS_SURGERY_RECOMMENDED)
                                            <span class="acc-badge acc-tone-amber">Surgery Recommended</span>
                                        @elseif($tab === 'queue')
                                            <span class="acc-badge acc-tone-blue">Booked</span>
                                        @else
                                            <span class="acc-badge acc-tone-green">{{ str((string) $booking->ot_status)->replace('_', ' ')->title() }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="acc-actions">
                                            <button type="button" class="acc-btn acc-btn-ghost" data-bs-toggle="modal"
                                                data-bs-target="#otCounselPatient{{ $booking->id }}">
                                                <i class="bi bi-eye-fill"></i> View
                                            </button>
                                            @if($tab === 'queue')
                                                <a href="{{ route('hospital.ot.counsellor.form', ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                                                    class="acc-btn acc-btn-primary">
                                                    <i class="bi bi-chat-left-text"></i> Counsel
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    @endif
                </table>
            </div>
        </div>
    @endforeach
</div>

@foreach(['queue', 'history'] as $modalTab)
    @foreach($counsellingLists[$modalTab] ?? [] as $booking)
        @include('hospital.ot.counsellor._patient-view-modal', ['booking' => $booking])
    @endforeach
@endforeach

@include('hospital.dashboard.partials.desk-panel-assets')
