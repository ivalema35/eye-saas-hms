{{--
  Discharge Counter home: Billing Desk queue / Discharged history rendered inline
  below the cards. Required: $slug, $dischargeLists, $dischargeInvoiceBookingIds.
--}}
@php
    $dcTabs = [
        'queue' => ['title' => 'Discharge & Invoice Queue', 'icon' => 'bi-hourglass-split', 'empty' => 'No records available for billing.'],
        'history' => ['title' => 'Discharged Patients', 'icon' => 'bi-check-circle-fill', 'empty' => 'No discharged patients yet.'],
    ];
@endphp

<div class="acc-panel" id="dischargePanel" data-default-tab="queue">
    <div class="acc-panel-head">
        <div class="acc-panel-title">
            <span class="acc-panel-icon"><i class="bi {{ $dcTabs['queue']['icon'] }}" data-acc-title-icon></i></span>
            <div>
                <h5 class="mb-0" data-acc-title>{{ $dcTabs['queue']['title'] }}</h5>
                <span class="acc-panel-sub"><span data-acc-count>{{ $dischargeLists['queue']->count() }}</span> patient(s)</span>
            </div>
        </div>
        <a href="{{ route('hospital.ot.billing.index', ['slug' => $slug, 'filter' => 'queue']) }}"
            class="acc-open-page" data-acc-page-link>
            Open full page <i class="bi bi-box-arrow-up-right"></i>
        </a>
    </div>

    @foreach($dcTabs as $tab => $meta)
        @php $rows = $dischargeLists[$tab]; @endphp
        <div class="acc-pane" data-acc-pane="{{ $tab }}"
            data-title="{{ $meta['title'] }}" data-icon="{{ $meta['icon'] }}" data-count="{{ $rows->count() }}"
            data-page-url="{{ route('hospital.ot.billing.index', ['slug' => $slug, 'filter' => $tab]) }}"
            @if($tab !== 'queue') hidden @endif>
            <div class="table-responsive">
                <table class="acc-table" data-acc-table data-empty="{{ $meta['empty'] }}" style="width:100%">
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Phone</th>
                            <th>OT Date</th>
                            <th>Status</th>
                            <th>Invoice</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $booking)
                            @php
                                $hasInvoice = in_array((int) $booking->id, $dischargeInvoiceBookingIds, true);
                                $isDischarged = strtolower((string) $booking->ot_status) === 'discharged';
                                $printsDone = $booking->dischargePrintsDoneCount();
                                $printsTotal = count(\App\Models\Hospital\OT\OtBooking::DISCHARGE_PRINTS);
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
                                <td>{{ optional($booking->surgery_date)->format('d M Y') ?? '-' }}</td>
                                <td>
                                    @if($isDischarged)
                                        <span class="acc-badge acc-tone-green">Discharged</span>
                                    @elseif($hasInvoice)
                                        <span class="acc-badge acc-tone-blue">Prints {{ $printsDone }}/{{ $printsTotal }}</span>
                                    @else
                                        <span class="acc-badge acc-tone-grey">Operated</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="acc-badge acc-tone-{{ $hasInvoice ? 'green' : 'amber' }}">
                                        {{ $hasInvoice ? 'Generated' : 'Pending' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="acc-actions">
                                        <button type="button" class="acc-btn acc-btn-ghost" data-bs-toggle="modal"
                                            data-bs-target="#dcView{{ $booking->id }}">
                                            <i class="bi bi-eye-fill"></i> View
                                        </button>
                                        @if($tab === 'queue' && !$hasInvoice)
                                            @haspermission('ot_billing_manage')
                                            <form method="POST" class="acc-inline-form"
                                                action="{{ route('hospital.ot.invoice.generate', ['slug' => $slug, 'bookingId' => $booking->id]) }}">
                                                @csrf
                                                <input type="date" name="follow_up_date" class="acc-date-input"
                                                    value="{{ now()->addDays(7)->format('Y-m-d') }}" title="Follow-up date">
                                                <button type="submit" class="acc-btn acc-btn-primary">
                                                    <i class="bi bi-file-earmark-plus"></i> Generate
                                                </button>
                                            </form>
                                            @endhaspermission
                                        @endif
                                        @if($hasInvoice)
                                            @foreach(\App\Models\Hospital\OT\OtBooking::DISCHARGE_PRINTS as $doc)
                                                @continue(! hospital_can($doc['permission']))
                                                @php $printed = $booking->{$doc['column']} !== null; @endphp
                                                <a href="{{ route($doc['route'], ['slug' => $slug, 'bookingId' => $booking->id]) }}"
                                                    class="acc-btn {{ $printed ? 'acc-btn-printed' : 'acc-btn-ghost' }}"
                                                    title="{{ $printed ? 'Printed ' . $booking->{$doc['column']}->format('d M Y, h:i A') : 'Not printed yet' }}">
                                                    <i class="bi {{ $printed ? 'bi-check-circle-fill' : $doc['icon'] }}"></i>
                                                    {{ $doc['short'] }}
                                                </a>
                                            @endforeach
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

@foreach($dischargeLists as $rows)
    @foreach($rows as $booking)
        @include('hospital.ot.discharge._view-modal', ['booking' => $booking, 'modalId' => 'dcView' . $booking->id])
    @endforeach
@endforeach

@include('hospital.dashboard.partials.desk-panel-assets')

@once
    @push('styles')
        <style>
            .acc-btn.acc-btn-printed { background: #E7F8EF; border: 1px solid #A9E4C4; color: #1E8E5A; }
            .acc-btn.acc-btn-printed:hover { background: #1E8E5A; border-color: #1E8E5A; color: #fff; }
        </style>
    @endpush
@endonce
