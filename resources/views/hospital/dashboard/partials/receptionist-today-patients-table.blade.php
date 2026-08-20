{{-- Receptionist "Today Added Patients" — three separate tables (Phone / Walk-in / OT).
     Tabs show/hide panes; mixed types cannot appear in one table. --}}
@php
    $wGreen = (int) hospital_setting('wait_green_max', 30);
    $wOrange = (int) hospital_setting('wait_orange_max', 60);
    $wRed = (int) hospital_setting('wait_red_max', 120);
    $wDGreen = (int) hospital_setting('wait_d_green_max', 40);
    $wDOrange = (int) hospital_setting('wait_d_orange_max', 90);
    $wDRed = (int) hospital_setting('wait_d_red_max', 120);
    $wNdGreen = (int) hospital_setting('wait_nd_green_max', 20);
    $wNdOrange = (int) hospital_setting('wait_nd_orange_max', 60);
    $wNdRed = (int) hospital_setting('wait_nd_red_max', 120);

    $tapGroups = [
        'phone' => collect(),
        'walkin' => collect(),
        'ot' => collect(),
    ];
    foreach ($receptionistTodayPatients as $p) {
        $src = (string) ($p->source ?? 'patient');
        $type = strtolower(trim((string) ($p->type ?? '')));
        if ($src === 'ot_appointment' || $type === 'ot') {
            $tapGroups['ot']->push($p);
        } elseif ($type === 'phone' || $type === '1') {
            $tapGroups['phone']->push($p);
        } else {
            $tapGroups['walkin']->push($p);
        }
    }

    $tapEmpty = [
        'phone' => 'No phone patients today',
        'walkin' => 'No walk-in patients today',
        'ot' => 'No OT appointments today',
    ];
@endphp
<div class="tap-panes"
    data-patient-count="{{ $receptionistTodayPatients->count() }}"
    data-tap-count-phone="{{ $tapGroups['phone']->count() }}"
    data-tap-count-walkin="{{ $tapGroups['walkin']->count() }}"
    data-tap-count-ot="{{ $tapGroups['ot']->count() }}">
    @foreach (['phone', 'walkin', 'ot'] as $tapKey)
        <div class="tap-pane{{ $tapKey === 'phone' ? ' is-active' : '' }}"
            data-tap-pane="{{ $tapKey }}"
            @if($tapKey !== 'phone') hidden @endif>
            <div class="table-responsive">
                <table class="tap-table">
                    <thead>
                        <tr>
                            <th style="width:40px">#</th>
                            <th>MRD</th>
                            <th>Patient</th>
                            <th>City / Age</th>
                            <th>Contact</th>
                            <th>Doctor</th>
                            <th>DR Index</th>
                            <th>Status</th>
                            <th style="text-align:center">Wait</th>
                            <th style="text-align:center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @include('hospital.dashboard.partials.receptionist-today-patients-rows', [
                            'receptionistTodayPatients' => $tapGroups[$tapKey],
                            'tapEmptyMessage' => $tapEmpty[$tapKey],
                            'slug' => $slug,
                            'wGreen' => $wGreen,
                            'wOrange' => $wOrange,
                            'wRed' => $wRed,
                            'wDGreen' => $wDGreen,
                            'wDOrange' => $wDOrange,
                            'wDRed' => $wDRed,
                            'wNdGreen' => $wNdGreen,
                            'wNdOrange' => $wNdOrange,
                            'wNdRed' => $wNdRed,
                        ])
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
