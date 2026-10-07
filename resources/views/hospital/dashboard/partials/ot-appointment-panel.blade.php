{{--
  OT Appointment home: Today's Appointments / Completed rendered inline
  below the cards. Required: $slug, $otAppointmentLists.
--}}
@php
    $oaTabs = [
        'today' => ['title' => 'OT Appointments', 'icon' => 'bi-calendar2-check', 'empty' => 'No OT appointments today.', 'pageTab' => 'booked'],
        'completed' => ['title' => 'Completed', 'icon' => 'bi-check-circle-fill', 'empty' => 'No appointments completed today.', 'pageTab' => 'completed'],
    ];
    // Patient::workflowStage() tone → panel badge tone
    $oaToneMap = [
        'warning' => 'acc-tone-amber',
        'info' => 'acc-tone-blue',
        'purple' => 'acc-tone-purple',
        'teal' => 'acc-tone-navy',
        'success' => 'acc-tone-green',
        'primary' => 'acc-tone-navy',
        'danger' => 'acc-tone-red',
    ];
@endphp

<div class="acc-panel" id="otAppointmentPanel" data-default-tab="today">
    <div class="acc-panel-head">
        <div class="acc-panel-title">
            <span class="acc-panel-icon"><i class="bi {{ $oaTabs['today']['icon'] }}" data-acc-title-icon></i></span>
            <div>
                <h5 class="mb-0" data-acc-title>{{ $oaTabs['today']['title'] }}</h5>
                <span class="acc-panel-sub"><span data-acc-count>{{ $otAppointmentLists['today']->count() }}</span> patient(s)</span>
            </div>
        </div>
        <a href="{{ route('hospital.ot.appointments.index', ['slug' => $slug, 'tab' => 'booked']) }}"
            class="acc-open-page" data-acc-page-link>
            Open full page <i class="bi bi-box-arrow-up-right"></i>
        </a>
    </div>

    @foreach($oaTabs as $tab => $meta)
        @php $rows = $otAppointmentLists[$tab]; @endphp
        <div class="acc-pane" data-acc-pane="{{ $tab }}"
            data-title="{{ $meta['title'] }}" data-icon="{{ $meta['icon'] }}" data-count="{{ $rows->count() }}"
            data-page-url="{{ route('hospital.ot.appointments.index', ['slug' => $slug, 'tab' => $meta['pageTab']]) }}"
            @if($tab !== 'today') hidden @endif>
            <div class="table-responsive">
                <table class="acc-table" data-acc-table data-empty="{{ $meta['empty'] }}" style="width:100%">
                    <thead>
                        <tr>
                            <th>Appt #</th>
                            <th>Patient Name</th>
                            <th>Phone</th>
                            <th>Doctor</th>
                            <th>Status</th>
                            @if($tab === 'today')
                                <th class="text-end">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $appointment)
                            @php
                                $isBooked = $appointment->canWalkIn();
                                $stage = $isBooked ? null : $appointment->convertedPatient?->workflowStage();
                            @endphp
                            <tr>
                                <td class="fw-bold">{{ $appointment->appointment_number }}</td>
                                <td>
                                    <div class="acc-patient">
                                        <span class="acc-avatar">{{ strtoupper(mb_substr($appointment->patient_name ?? '?', 0, 1)) }}</span>
                                        <div>
                                            <div class="acc-name">{{ trim(implode(' ', array_filter([$appointment->patient_name, $appointment->middle_name, $appointment->surname]))) ?: '-' }}</div>
                                            <div class="acc-code">{{ $appointment->convertedPatient?->patient_code ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $appointment->mobile_no ?: '-' }}</td>
                                <td>{{ $appointment->doctor?->name ? 'Dr. '.$appointment->doctor->name : '-' }}</td>
                                <td>
                                    @if($isBooked)
                                        <span class="acc-badge acc-tone-amber">Booked</span>
                                    @else
                                        <span class="acc-badge {{ $oaToneMap[$stage['tone'] ?? ''] ?? 'acc-tone-grey' }}">{{ $stage['label'] ?? 'Walk-In Completed' }}</span>
                                        @if(!empty($stage['sub']))
                                            <div class="acc-code mt-1">{{ $stage['sub'] }}</div>
                                        @endif
                                    @endif
                                </td>
                                @if($tab === 'today')
                                    <td class="text-end">
                                        @haspermission('ot_appointment_edit')
                                        <div class="acc-actions">
                                            <a href="{{ route('hospital.ot.appointments.edit', ['slug' => $slug, 'id' => $appointment->id]) }}"
                                                class="acc-btn acc-btn-primary">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </a>
                                        </div>
                                        @endhaspermission
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>

@include('hospital.dashboard.partials.desk-panel-assets')
