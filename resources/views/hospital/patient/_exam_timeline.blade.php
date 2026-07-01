{{--
    Partial: _exam_timeline.blade.php
    Receives (via parent scope or @include override):
      $history         — Collection of PrimaryExamination / SecondaryExamination
      $diagnosisMasters — Collection with id + diagnosis columns
      $dosageMasters    — Collection keyed by id
--}}
@if($history->isEmpty())
    <p class="history-empty text-muted text-center py-5 mb-0">
        <i class="bi bi-journal-x fs-2 d-block mb-2 opacity-50"></i>
        No examination history found for this patient.
    </p>
@else
    @php
        $grouped = $history->groupBy(
            fn($e) => \Carbon\Carbon::parse($e->examined_at)->format('d M Y')
        );
    @endphp

    @foreach($grouped as $date => $exams)
        <div class="date-group">

            {{-- Date divider --}}
            <div class="date-divider">
                <span class="date-divider-badge">
                    <i class="bi bi-calendar3 me-1"></i>{{ $date }}
                </span>
                <div class="date-divider-line"></div>
                <span class="date-divider-count">
                    {{ $exams->count() }} exam{{ $exams->count() > 1 ? 's' : '' }}
                </span>
            </div>

            {{-- Exam cards for this date --}}
            <div class="row g-3">
                @foreach($exams as $exam)
                    @php
                        $data = is_array($exam->exam_data)
                            ? $exam->exam_data
                            : (json_decode($exam->exam_data, true) ?? []);
                        $isPrimary = $exam->type === 'Primary Exam';
                        $collapseId = 'ed' . $exam->id . ($isPrimary ? 'P' : 'S');
                    @endphp
                    <div class="col-12">
                        <div class="visit-exam-card card border-0 mb-0">

                            {{-- Card header: type + time --}}
                            <div class="visit-exam-header {{ $isPrimary ? 'is-primary' : 'is-secondary' }}">
                                <i class="bi {{ $exam->icon }} fs-5"></i>
                                {{ $exam->type }}
                                <span class="visit-exam-time">
                                    <i class="bi bi-clock me-1"></i>
                                    {{ \Carbon\Carbon::parse($exam->examined_at)->format('h:i A') }}
                                </span>
                            </div>

                            <div class="card-body p-3">
                                @php
                                    $vision = $data['vision'] ?? [];
                                    $pg = $data['pg'] ?? [];
                                    $st = $data['st'] ?? [];
                                    $nct = $data['nct'] ?? [];
                                    $oe = $data['oe'] ?? [];
                                    $fundus = $data['fundus'] ?? [];
                                    $coRows = array_filter($data['co_rows'] ?? [], fn($r) => !empty($r['complaint']));
                                    $kcoRows = array_filter($data['kco_rows'] ?? [], fn($r) => !empty($r['condition']));
                                    $dxIds = $data['diagnoses'] ?? [];
                                    $advTxt = trim($data['advice'] ?? '');
                                    $rxLines = $isPrimary
                                        ? ($exam->prescriptions ?? collect())
                                        : collect($data['rx'] ?? []);
                                @endphp

                                {{-- Doctor --}}
                                <p class="text-muted small mb-2">
                                    <i class="bi bi-person-badge me-1"></i>
                                    Examined by: <strong style="color:var(--history-secondary);">Dr.
                                        {{ $exam->doctor->name ?? 'Unknown' }}</strong>
                                </p>

                                {{-- Toggle --}}
                                <button class="btn btn-sm btn-outline-{{ $exam->color }} mt-1" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}">
                                    <i class="bi bi-clipboard2-pulse me-1"></i> View Clinical Data
                                </button>

                                <div class="collapse mt-3" id="{{ $collapseId }}">
                                    @php
                                        $cv = fn($v) => (isset($v) && $v !== '' && $v !== null) ? $v : '-';
                                        $dxNames = $diagnosisMasters->whereIn('id', (array) $dxIds)->pluck('diagnosis')->implode(', ');
                                        $dilate = $data['dilate'] ?? 'No';
                                        $stBadges = array_filter([
                                            ($data['st']['bifocal'] ?? false) ? 'Bifocal' : '',
                                            ($data['st']['nd_separate'] ?? false) ? 'N&D Separate' : '',
                                            ($data['st']['progressive'] ?? false) ? 'Progressive' : '',
                                            ($data['st']['computer_uses'] ?? false) ? 'Computer Uses' : '',
                                        ]);
                                    @endphp

                                    <div class="row g-2">

                                        {{-- LEFT COL: History+Vision & ST+Diagnosis+Rx --}}
                                        <div class="col-md-6 d-flex flex-column gap-2">

                                            {{-- BOX 1: History & Vision --}}
                                            <div class="cv-box">
                                                <div class="cv-title">History &amp; Vision</div>

                                                {{-- C/O table --}}
                                                <table class="cv-table">
                                                    <thead>
                                                        <tr>
                                                            <th colspan="4">C/O</th>
                                                        </tr>
                                                        <tr>
                                                            <th>Complaint</th>
                                                            <th>Since</th>
                                                            <th>Eye</th>
                                                            <th>Comment</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($coRows as $cr)
                                                            <tr>
                                                                <td style="text-align:left">{{ $cr['complaint'] }}</td>
                                                                <td>{{ !empty($cr['since']) ? $cr['since'] . ' ' . ($cr['unit'] ?? '') : '-' }}</td>
                                                                <td>{{ $cr['eye'] ?? '-' }}</td>
                                                                <td style="text-align:left">{{ $cr['comment'] ?? '-' }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="4" style="text-align:center;color:#94a3b8">—</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>

                                                {{-- K/C/O table (only if exists) --}}
                                                @if(count($kcoRows))
                                                    <table class="cv-table">
                                                        <thead>
                                                            <tr>
                                                                <th colspan="3">K/C/O</th>
                                                            </tr>
                                                            <tr>
                                                                <th>Condition</th>
                                                                <th>Since</th>
                                                                <th>Comment</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($kcoRows as $kr)
                                                                <tr>
                                                                    <td style="text-align:left">{{ $kr['condition'] }}</td>
                                                                    <td>{{ !empty($kr['since']) ? $kr['since'] . ' ' . ($kr['unit'] ?? '') : '-' }}</td>
                                                                    <td style="text-align:left">{{ $kr['comment'] ?? '-' }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                @endif

                                                {{-- Vision + IOP inline row --}}
                                                <div style="border-top:1px solid #dee2e6;padding-top:4px;margin-top:3px;display:flex;flex-wrap:wrap;align-items:center;gap:4px;font-size:11px;">
                                                    <span class="cv-vn-line"><strong>Vn</strong>&nbsp;{{ $cv($vision['vn_re'] ?? '') }}/{{ $cv($vision['vn_le'] ?? '') }}</span>
                                                    <span class="cv-vn-line"><strong>PH</strong>&nbsp;{{ $cv($vision['pnvn_re'] ?? '') }}/{{ $cv($vision['pnvn_le'] ?? '') }}</span>
                                                    <span class="cv-vn-line"><strong>NrVn</strong>&nbsp;{{ $cv($vision['nrvn_re'] ?? '') }}/{{ $cv($vision['nrvn_le'] ?? '') }}</span>
                                                    <span class="cv-vn-line"><strong>IOP:</strong>&nbsp;{{ $cv($nct['iop_re'] ?? '') }}/{{ $cv($nct['iop_le'] ?? '') }}</span>
                                                </div>

                                                {{-- PG table --}}
                                                <table class="cv-table" style="margin-top:4px">
                                                    <thead>
                                                        <tr>
                                                            <th style="width:18px"></th>
                                                            <th colspan="4">RIGHT EYE (RE)</th>
                                                            <th colspan="4">LEFT EYE (LE)</th>
                                                        </tr>
                                                        <tr>
                                                            <th></th>
                                                            <th>SPH</th><th>CYL</th><th>AXIS</th><th>VN</th>
                                                            <th>SPH</th><th>CYL</th><th>AXIS</th><th>VN</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <th>D</th>
                                                            <td>{{ $cv($pg['re']['ds'] ?? '') }}</td>
                                                            <td>{{ $cv($pg['re']['dc'] ?? '') }}</td>
                                                            <td>{{ $cv($pg['re']['ax'] ?? '') }}</td>
                                                            <td>{{ $cv($pg['re']['vn'] ?? '') }}</td>
                                                            <td>{{ $cv($pg['le']['ds'] ?? '') }}</td>
                                                            <td>{{ $cv($pg['le']['dc'] ?? '') }}</td>
                                                            <td>{{ $cv($pg['le']['ax'] ?? '') }}</td>
                                                            <td>{{ $cv($pg['le']['vn'] ?? '') }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>N</th>
                                                            <td>{{ $cv($pg['re']['ns'] ?? '') }}</td>
                                                            <td>{{ $cv($pg['re']['nc'] ?? '') }}</td>
                                                            <td>{{ $cv($pg['re']['na'] ?? '') }}</td>
                                                            <td>{{ $cv($pg['re']['near_vn'] ?? '') }}</td>
                                                            <td>{{ $cv($pg['le']['ns'] ?? '') }}</td>
                                                            <td>{{ $cv($pg['le']['nc'] ?? '') }}</td>
                                                            <td>{{ $cv($pg['le']['na'] ?? '') }}</td>
                                                            <td>{{ $cv($pg['le']['near_vn'] ?? '') }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            {{-- BOX 2: ST + Diagnosis & Rx --}}
                                            <div class="cv-box">
                                                <div class="cv-title">ST</div>

                                                <table class="cv-table">
                                                    <thead>
                                                        <tr>
                                                            <th style="width:18px"></th>
                                                            <th colspan="4">RIGHT EYE (RE)</th>
                                                            <th colspan="4">LEFT EYE (LE)</th>
                                                        </tr>
                                                        <tr>
                                                            <th></th>
                                                            <th>SPH</th><th>CYL</th><th>AXIS</th><th>VN</th>
                                                            <th>SPH</th><th>CYL</th><th>AXIS</th><th>VN</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <th>D</th>
                                                            <td>{{ $cv($st['re']['ds'] ?? '') }}</td>
                                                            <td>{{ $cv($st['re']['dc'] ?? '') }}</td>
                                                            <td>{{ $cv($st['re']['ax'] ?? '') }}</td>
                                                            <td>{{ $cv($st['re']['vn'] ?? '') }}</td>
                                                            <td>{{ $cv($st['le']['ds'] ?? '') }}</td>
                                                            <td>{{ $cv($st['le']['dc'] ?? '') }}</td>
                                                            <td>{{ $cv($st['le']['ax'] ?? '') }}</td>
                                                            <td>{{ $cv($st['le']['vn'] ?? '') }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>N</th>
                                                            <td>{{ $cv($st['re']['ns'] ?? '') }}</td>
                                                            <td>{{ $cv($st['re']['nc'] ?? '') }}</td>
                                                            <td>{{ $cv($st['re']['na'] ?? '') }}</td>
                                                            <td>-</td>
                                                            <td>{{ $cv($st['le']['ns'] ?? '') }}</td>
                                                            <td>{{ $cv($st['le']['nc'] ?? '') }}</td>
                                                            <td>{{ $cv($st['le']['na'] ?? '') }}</td>
                                                            <td>-</td>
                                                        </tr>
                                                    </tbody>
                                                </table>

                                                @if(!empty($st['re']['add']) || !empty($st['le']['add']))
                                                    <div style="font-size:10px;color:#475569;margin:3px 0">
                                                        <span style="color:#1B4F72;font-weight:700;">ADD</span>&emsp;RE:
                                                        <strong>{{ $st['re']['add'] ?? '-' }}</strong>&emsp;LE:
                                                        <strong>{{ $st['le']['add'] ?? '-' }}</strong>
                                                    </div>
                                                @endif
                                                @if(count($stBadges))
                                                    <div style="margin-bottom:4px">
                                                        @foreach($stBadges as $b)<span class="cv-badge">{{ $b }}</span>@endforeach
                                                    </div>
                                                @endif

                                                <div class="cv-title mt-2">Diagnosis &amp; Rx</div>

                                                <div style="font-size:10px;margin-bottom:4px">
                                                    <strong>Dx:</strong> {{ $dxNames ?: '-' }} &nbsp;
                                                    <strong>Dilate:</strong> {{ $dilate }}
                                                </div>

                                                <table class="cv-table">
                                                    <thead class="cv-rx-hd">
                                                        <tr>
                                                            <th>Medicine</th>
                                                            <th>Dosage</th>
                                                            <th>Days</th>
                                                            <th>Eye</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($rxLines as $rx)
                                                            @php
                                                                if ($isPrimary) {
                                                                    $mName = $rx->medicine?->brand_name ?: ($rx->medicine?->name ?? '-');
                                                                    $mDose = $rx->dosage?->dosage ?? '-';
                                                                    $mDays = $rx->duration ? $rx->duration . ' D' : '-';
                                                                    $mEye = $rx->eye ?? '-';
                                                                } else {
                                                                    $rx = (array) $rx;
                                                                    $mName = $rx['name'] ?? '-';
                                                                    $mDose = isset($rx['dosage_id']) ? ($dosageMasters[$rx['dosage_id']]?->dosage ?? '-') : '-';
                                                                    $mDays = !empty($rx['duration']) ? $rx['duration'] . ' D' : '-';
                                                                    $mEye = $rx['eye'] ?? '-';
                                                                }
                                                            @endphp
                                                            <tr>
                                                                <td style="text-align:left;font-weight:600">{{ $mName }}</td>
                                                                <td>{{ $mDose }}</td>
                                                                <td>{{ $mDays }}</td>
                                                                <td>{{ $mEye }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="4" style="text-align:center;color:#94a3b8">No medicines</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>

                                        </div>{{-- /left col --}}

                                        {{-- RIGHT COL: O/E & Fundus --}}
                                        <div class="col-md-6 d-flex flex-column gap-2">

                                            {{-- BOX 3: O/E --}}
                                            <div class="cv-box">
                                                <div class="cv-title">O/E</div>
                                                <table class="cv-table">
                                                    <thead class="cv-oe-hd">
                                                        <tr>
                                                            <th style="text-align:left">O/E</th>
                                                            <th>RIGHT</th>
                                                            <th>LEFT</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach(['sac' => 'SAC', 'lid' => 'LID', 'conj' => 'CONJ', 'cornea' => 'CORNEA', 'ac' => 'AC', 'iris' => 'IRIS', 'pupil' => 'PUPIL', 'lens' => 'LENS', 'em' => 'EM', 'covertest' => 'COVERTEST', 'other' => 'OTHER'] as $k => $lbl)
                                                            <tr>
                                                                <th style="text-align:left;background:#f0f4f8;color:#1B4F72">{{ $lbl }}</th>
                                                                <td>{{ $cv($oe[$k . '_re'] ?? '') }}</td>
                                                                <td>{{ $cv($oe[$k . '_le'] ?? '') }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>

                                            {{-- BOX 4: Fundus --}}
                                            <div class="cv-box">
                                                <div class="cv-title">Fundus</div>
                                                <table class="cv-table">
                                                    <thead class="cv-oe-hd">
                                                        <tr>
                                                            <th style="text-align:left">Fundus</th>
                                                            <th>RIGHT</th>
                                                            <th>LEFT</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <th style="text-align:left;background:#f0f4f8;color:#1B4F72">DISC</th>
                                                            <td>{{ $cv($fundus['disc_re'] ?? '') }}</td>
                                                            <td>{{ $cv($fundus['disc_le'] ?? '') }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th style="text-align:left;background:#f0f4f8;color:#1B4F72">FR</th>
                                                            <td>{{ $cv($fundus['fr_re'] ?? '') }}</td>
                                                            <td>{{ $cv($fundus['fr_le'] ?? '') }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th style="text-align:left;background:#f0f4f8;color:#1B4F72">COMMENT</th>
                                                            <td>{{ $cv($fundus['comment_re'] ?? '') }}</td>
                                                            <td>{{ $cv($fundus['comment_le'] ?? '') }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                        </div>{{-- /right col --}}
                                    </div>{{-- /row --}}

                                    {{-- Advice (full width) --}}
                                    @if($advTxt)
                                        <div class="cv-box mt-2">
                                            <div class="cv-title">Advice</div>
                                            <div style="font-size:12px;white-space:pre-line;line-height:1.6">
                                                {{ $advTxt }}
                                            </div>
                                        </div>
                                    @endif

                                </div>{{-- /collapse --}}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    @endforeach
@endif
