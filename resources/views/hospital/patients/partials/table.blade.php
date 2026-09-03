{{-- Patients table. Search/sort/pagination are handled client-side by
     DataTables (initialized in hospital.patients.index — kept off the
     rows-are-latest-first default ordering, so it isn't the shared
     .js-datatable auto-init). --}}
<div class="table-wrapper">
    <table class="modern-table patients-table" style="width:100%">
        <thead>
            <tr>
                <th style="width:44px">#</th>
                <th style="width:80px">MRD</th>
                <th style="min-width:260px">Patient</th>
                <th style="width:130px">Doctor</th>
                <th style="width:110px">Fee / Type</th>
                <th style="width:150px">Status</th>
                <th style="width:220px">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($patients as $i => $p)
                @php
                    $primary = $p->primaryExamination;
                    $secondary = $p->secondaryExamination;
                    $isPrimaryDone = $primary !== null;
                    $isSecondaryDone = $secondary !== null;
                    $unlockTimeMs = null;
                    $isDilating = false;

                    if ($primary && ($primary->exam_data['dilate'] ?? 'No') === 'Yes' && $primary->dilation_time && !$isSecondaryDone) {
                        $expectedUnlock = $primary->updated_at->copy()->addMinutes($primary->dilation_time);
                        if (now()->lessThan($expectedUnlock)) {
                            $isDilating = true;
                            $unlockTimeMs = $expectedUnlock->timestamp * 1000;
                        }
                    }
                @endphp
                <tr class="pt-row">
                    {{-- # --}}
                    <td class="serial">{{ $i + 1 }}</td>

                    {{-- MRD --}}
                    <td><span class="mrd-badge">{{ $p->patient_code }}</span></td>

                    {{-- Patient: name + meta --}}
                    <td>
                        <div class="pt-info">
                            <div>
                                <div class="pt-name">{{ $p->full_name }}</div>
                                <div class="pt-meta">
                                    {{ $p->age }}y · {{ ucfirst($p->gender) }}
                                    @if($p->contact_no) · {{ $p->contact_no }} @endif
                                    @if($p->created_at) · {{ $p->created_at->format('h:i A') }} @endif
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- Doctor --}}
                    <td class="pt-doctor">{{ $p->doctor?->name ?? '—' }}</td>

                    {{-- Fee / Type --}}
                    <td>
                        <div class="pt-fee">{{ money($p->case_fee, 0) }}</div>
                        <span class="type-badge">{{ ucfirst($p->type) }}</span>
                    </td>

                    {{-- Status --}}
                    <td>
                        @if($p->secondary_done_at)
                            <span class="status-badge completed"><i class="bi bi-check2-all"></i> Completed</span>
                        @elseif($p->primary_done_at)
                            <span class="status-badge in-progress"><i class="bi bi-clipboard2-check"></i> Primary
                                Done</span>
                        @else
                            <span class="status-badge waiting"><i class="bi bi-hourglass-split"></i> Waiting</span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td>
                        <div class="pt-actions">
                            {{-- View --}}
                            <button type="button" class="pta pta-view view-patient-btn" title="View Patient"
                                data-id="{{ $p->id }}" data-mrd="{{ $p->patient_code }}"
                                data-name="{{ $p->full_name }}" data-age="{{ $p->age }}"
                                data-gender="{{ ucfirst($p->gender ?? '') }}" data-contact="{{ $p->contact_no }}"
                                data-whatsapp="{{ $p->whatsapp_no }}" data-occupation="{{ $p->occupation }}"
                                data-city="{{ $p->cityName }}" data-district="{{ $p->districtName }}"
                                data-state="{{ $p->stateName }}" data-doctor="{{ $p->doctor?->name }}"
                                data-case="{{ $p->caseType?->case_type ?? '' }}"
                                data-fee="{{ number_format($p->case_fee, 0) }}"
                                data-referrer="{{ $p->referrer?->name }}"
                                data-appt="{{ $p->appointment_date?->format('d M Y') }}"
                                data-registered="{{ $p->created_at->format('d M Y, h:i A') }}"
                                data-status="{{ $p->secondary_done_at ? 'Secondary Done' : ($p->primary_done_at ? 'Primary Done' : 'Waiting') }}"
                                data-primary-at="{{ $p->primary_done_at?->format('d M Y, h:i A') }}"
                                data-secondary-at="{{ $p->secondary_done_at?->format('d M Y, h:i A') }}"
                                data-edit-url="{{ route('hospital.patients.edit', ['slug' => $slug, 'patient' => $p->id]) }}">
                                <i class="bi bi-info-circle-fill"></i>
                            </button>

                            <div class="pta-divider"></div>

                            {{-- Primary Exam --}}
                            @if($isPrimaryDone)
                                <span class="pta pta-done" title="Primary Done"><i
                                        class="bi bi-clipboard2-check-fill"></i></span>
                            @else
                                <a href="{{ route('hospital.exam.primary.show', ['slug' => $slug, 'id' => $p->id]) }}"
                                    class="pta pta-primary" title="Primary Exam">
                                    <i class="bi bi-clipboard2-pulse"></i>
                                </a>
                            @endif

                            {{-- Secondary Exam --}}
                            @if(!$isPrimaryDone)
                                <span class="pta pta-locked" title="Complete Primary First"><i
                                        class="bi bi-lock-fill"></i></span>
                            @elseif($isSecondaryDone)
                                <span class="pta pta-done" title="Secondary Done"><i class="bi bi-check-all"></i></span>
                            @elseif($isDilating)
                                <span class="pta pta-timer dilation-timer-btn" data-unlock-time="{{ $unlockTimeMs }}"
                                    id="sec-btn-{{ $p->id }}">
                                    <i class="bi bi-hourglass-top"></i><span class="timer-text"
                                        style="font-size:10px;font-weight:700"> --:--</span>
                                </span>
                            @else
                                <a href="{{ route('hospital.exam.secondary.show', ['slug' => $slug, 'id' => $p->id]) }}"
                                    class="pta pta-secondary" title="Secondary Exam">
                                    <i class="bi bi-file-earmark-medical"></i>
                                </a>
                            @endif

                            <div class="pta-divider"></div>

                            {{-- History --}}
                            <a href="{{ route('hospital.patients.history', ['slug' => $slug, 'patient_ids' => $p->id]) }}"
                                class="pta pta-util" title="History"><i class="bi bi-clock-history"></i></a>

                            {{-- Print --}}
                            <a href="{{ route('hospital.patients.print', ['slug' => $slug, 'patient' => $p->id, 'auto_print' => 1, 'return_to' => 'back']) }}"
                                class="pta pta-util" title="Print"><i class="bi bi-printer-fill"></i></a>

                            {{-- Edit --}}
                            <a href="{{ route('hospital.patients.edit', ['slug' => $slug, 'patient' => $p->id]) }}"
                                class="pta pta-edit" title="Edit"><i class="bi bi-pencil-fill"></i></a>

                            {{-- Check-in (phone only) --}}
                            @if($p->type === 'phone' && !$p->case_id)
                                <a href="{{ route('hospital.patients.checkin', ['slug' => $slug, 'patient' => $p->id]) }}"
                                    class="pta pta-checkin" title="Check In"><i class="bi bi-person-check-fill"></i></a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty-state">
                        <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                        <h5>No patients found</h5>
                        <p>{{ $showAll ? 'No patients registered yet.' : 'No patients registered today.' }}</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
