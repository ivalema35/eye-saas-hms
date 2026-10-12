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
                            <th>Dilate</th>
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
                                    @php
                                        $dilateEntry = $booking->dilationEntries
                                            ->where('dilate', 'Yes')
                                            ->whereNotNull('dilation_time')
                                            ->sortByDesc('administered_at')
                                            ->first();
                                    @endphp
                                    @if($dilateEntry)
                                        @php
                                            $dilateUnlockAt = \Carbon\Carbon::parse($dilateEntry->administered_at)->addMinutes((int) $dilateEntry->dilation_time);
                                        @endphp
                                        <span class="acc-badge acc-tone-amber acc-dilate-pill" data-unlock-ms="{{ $dilateUnlockAt->timestamp * 1000 }}">
                                            <i class="bi bi-droplet-half me-1"></i><span class="acc-dilate-time">--:--</span>
                                        </span>
                                    @else
                                        <span class="acc-sub">-</span>
                                    @endif
                                </td>
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

@push('scripts')
    <script>
        (function () {
            function updateWardPanelDilateCountdowns() {
                document.querySelectorAll('#wardPanel .acc-dilate-pill[data-unlock-ms]').forEach(function (el) {
                    var unlockMs = parseInt(el.getAttribute('data-unlock-ms'), 10);
                    var diff = unlockMs - Date.now();
                    var timeEl = el.querySelector('.acc-dilate-time');
                    if (!timeEl) return;
                    if (diff <= 0) {
                        timeEl.textContent = 'Ready';
                        el.classList.remove('acc-tone-amber');
                        el.classList.add('acc-tone-green');
                    } else {
                        var m = Math.floor(diff / 60000);
                        var s = Math.floor((diff % 60000) / 1000);
                        timeEl.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                    }
                });
            }
            updateWardPanelDilateCountdowns();
            setInterval(updateWardPanelDilateCountdowns, 1000);
        })();
    </script>
@endpush
