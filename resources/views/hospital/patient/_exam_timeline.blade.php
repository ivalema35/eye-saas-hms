{{--
    Partial: _exam_timeline.blade.php
    Receives (via parent scope or @include override):
      $history         — Collection of PrimaryExamination / SecondaryExamination
      $diagnosisMasters — Collection with id + diagnosis columns
      $dosageMasters    — Collection keyed by id
--}}
<style>{!! axis_chip_css() !!}</style>
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
                                        $lensCv = function ($oe, $eye) use ($cv) {
                                            $base = $oe['lens_' . $eye] ?? '';
                                            if ($base === null || $base === '') {
                                                return $cv(null);
                                            }

                                            $pseudo = $oe['pseudophakia_' . $eye] ?? [];
                                            $extras = array_filter([
                                                $pseudo['operation_type'] ?? '',
                                                !empty($pseudo['operation_expense']) ? currency_symbol() . $pseudo['operation_expense'] : '',
                                                $pseudo['hospital_name'] ?? '',
                                            ], fn($v) => $v !== '' && $v !== null);

                                            return $extras ? $base . ' (' . implode(', ', $extras) . ')' : $base;
                                        };
$dxNames = $diagnosisMasters->whereIn('id', (array) $dxIds)->pluck('diagnosis')->implode(', ');
                                        $hnoList = array_filter(array_map('trim', explode(',', $data['history'] ?? '')));
                                        $hasPg = !empty($pg['re']['ds']) || !empty($pg['le']['ds'])
                                            || !empty($pg['re']['ns']) || !empty($pg['le']['ns']);
                                        $hasSt = !empty($st['re']['ds']) || !empty($st['le']['ds'])
                                            || !empty($st['re']['ns']) || !empty($st['le']['ns'])
                                            || !empty($st['bifocal']) || !empty($st['nd_separate'])
                                            || !empty($st['progressive']) || !empty($st['computer_uses']);
                                        $hasVision = !empty($vision['vn_re']) || !empty($vision['vn_le'])
                                            || !empty($vision['pnvn_re']) || !empty($vision['pnvn_le'])
                                            || !empty($vision['nrvn_re']) || !empty($vision['nrvn_le'])
                                            || !empty($nct['iop_re']) || !empty($nct['iop_le'])
                                            || !empty($pg['re']['vn']) || !empty($pg['le']['vn'])
                                            || !empty($pg['re']['near_vn']) || !empty($pg['le']['near_vn']);
                                        $oeFields = [
                                            'sac' => 'SAC', 'lid' => 'LID', 'conj' => 'CONJ', 'cornea' => 'CORNEA',
                                            'ac' => 'AC', 'iris' => 'IRIS', 'pupil' => 'PUPIL', 'lens' => 'LENS',
                                            'em' => 'EM', 'covertest' => 'COVERTEST', 'other' => 'OTHER',
                                        ];
                                        $hasOe = collect(array_keys($oeFields))
                                            ->contains(fn($k) => !empty($oe[$k . '_re']) || !empty($oe[$k . '_le']));
                                        $hasFundus = !empty($fundus['disc_re']) || !empty($fundus['disc_le'])
                                            || !empty($fundus['fr_re']) || !empty($fundus['fr_le'])
                                            || !empty($fundus['comment_re']) || !empty($fundus['comment_le']);
                                        $hasRx = $rxLines instanceof \Illuminate\Support\Collection
                                            ? $rxLines->isNotEmpty()
                                            : (is_countable($rxLines) && count($rxLines) > 0);
                                        $hasAnyClinicalData = ($isPrimary ? false : $hasRx) || $hasPg || $hasSt
                                            || count($kcoRows) || count($coRows) || count($hnoList)
                                            || $hasVision || $hasOe || $hasFundus
                                            || (!$isPrimary && $dxNames) || (!$isPrimary && $advTxt);
                                    @endphp

                                    @unless($hasAnyClinicalData)
                                        <p class="text-muted small mb-0">
                                            <i class="bi bi-info-circle me-1"></i>
                                            No clinical data was recorded for this visit.
                                        </p>
                                    @else
                                    <div class="row g-2">

                                        {{-- LEFT: Medicine / PG / ST / K/C/O --}}
                                        <div class="col-md-6 d-flex flex-column gap-2">

                                            @if(!$isPrimary && $hasRx)
                                                <div class="cv-box">
                                                    <div class="cv-title">Medicine</div>
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
                                                            @foreach($rxLines as $rx)
                                                                @php
                                                                    $rx = (array) $rx;
                                                                    $mName = $rx['name'] ?? '-';
                                                                    $mDose = isset($rx['dosage_id'])
                                                                        ? ($dosageMasters[$rx['dosage_id']]?->dosage ?? '-')
                                                                        : ($rx['dosage'] ?? '-');
                                                                    $mDays = !empty($rx['duration']) ? $rx['duration'] . ' D' : '-';
                                                                    $mEye = $rx['eye'] ?? '-';
                                                                @endphp
                                                                <tr>
                                                                    <td style="text-align:left;font-weight:600">{{ $mName }}</td>
                                                                    <td>{{ $mDose }}</td>
                                                                    <td>{{ $mDays }}</td>
                                                                    <td>{{ $mEye }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif

                                            @if($hasPg)
                                                <div class="cv-box">
                                                    <div class="cv-title">PG</div>
                                                    <div style="display:flex;flex-wrap:wrap;align-items:flex-start;gap:8px;font-size:11px;">
                                                        <span class="cv-vn-line" style="align-items:flex-start;">
                                                            <strong>PG</strong>&nbsp;&lt;&nbsp;
                                                            <span style="display:inline-flex;flex-direction:column;line-height:1.35;">
                                                                <span>{!! pg_rx_line($pg['re']['ds'] ?? '', $pg['re']['dc'] ?? '', $pg['re']['ax'] ?? '') !!}</span>
                                                                <span>{!! pg_rx_line($pg['le']['ds'] ?? '', $pg['le']['dc'] ?? '', $pg['le']['ax'] ?? '') !!}</span>
                                                            </span>
                                                        </span>
                                                        <span class="cv-vn-line" style="align-items:flex-start;">
                                                            <strong>NrPG</strong>&nbsp;&lt;&nbsp;
                                                            <span style="display:inline-flex;flex-direction:column;line-height:1.35;">
                                                                <span>{!! pg_rx_line($pg['re']['ns'] ?? '', $pg['re']['nc'] ?? '', $pg['re']['na'] ?? '') !!}</span>
                                                                <span>{!! pg_rx_line($pg['le']['ns'] ?? '', $pg['le']['nc'] ?? '', $pg['le']['na'] ?? '') !!}</span>
                                                            </span>
                                                        </span>
                                                    </div>
                                                </div>
                                            @endif

                                            @if($hasSt)
                                                <div class="cv-box">
                                                    <div class="cv-title">ST</div>
                                                    <table class="cv-table">
                                                        <thead>
                                                            <tr>
                                                                <th colspan="4">RIGHT EYE</th>
                                                                <th colspan="4">LEFT EYE</th>
                                                            </tr>
                                                            <tr>
                                                                <th>{{ $st['re']['vn'] ?? '' }}</th>
                                                                <th>SPH</th><th>CYL</th><th>AXIS</th>
                                                                <th>{{ $st['le']['vn'] ?? '' }}</th>
                                                                <th>SPH</th><th>CYL</th><th>AXIS</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <th>D</th>
                                                                <td>{{ $cv($st['re']['ds'] ?? '') }}</td>
                                                                <td>{{ $cv($st['re']['dc'] ?? '') }}</td>
                                                                <td>{!! axis_chip($st['re']['ax'] ?? '', $cv('')) !!}</td>
                                                                <th>D</th>
                                                                <td>{{ $cv($st['le']['ds'] ?? '') }}</td>
                                                                <td>{{ $cv($st['le']['dc'] ?? '') }}</td>
                                                                <td>{!! axis_chip($st['le']['ax'] ?? '', $cv('')) !!}</td>
                                                            </tr>
                                                            <tr>
                                                                <th>N</th>
                                                                <td>{{ $cv($st['re']['ns'] ?? '') }}</td>
                                                                <td>{{ $cv($st['re']['nc'] ?? '') }}</td>
                                                                <td>{!! axis_chip($st['re']['na'] ?? '', $cv('')) !!}</td>
                                                                <th>N</th>
                                                                <td>{{ $cv($st['le']['ns'] ?? '') }}</td>
                                                                <td>{{ $cv($st['le']['nc'] ?? '') }}</td>
                                                                <td>{!! axis_chip($st['le']['na'] ?? '', $cv('')) !!}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    @php
                                                        $stOptLabels = collect([
                                                            'bifocal' => 'Bifocal',
                                                            'nd_separate' => 'Near & Distance Separate',
                                                            'progressive' => 'Progressive',
                                                            'computer_uses' => 'Computer Uses',
                                                        ])->filter(fn ($label, $key) => !empty($st[$key]))->values()->all();
                                                    @endphp
                                                    @if(!empty($stOptLabels))
                                                        <div style="font-size:10px;color:#1B4F72;font-weight:600;margin:3px 0">
                                                            {{ implode(' · ', $stOptLabels) }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif

                                            @if(count($kcoRows))
                                                <div class="cv-box">
                                                    <table class="cv-table">
                                                        <thead>
                                                            <tr>
                                                                <th colspan="3" style="background:#1B4F72;color:#fff;text-align:left;padding-left:6px;">K/C/O</th>
                                                            </tr>
                                                            <tr>
                                                                <th>Condition</th>
                                                                <th>Since/Duration</th>
                                                                <th>Comment</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($kcoRows as $kr)
                                                                @php
                                                                    $kSince = trim((string) ($kr['since'] ?? ''));
                                                                    $kUnit = trim((string) ($kr['unit'] ?? ''));
                                                                    $kDuration = $kUnit === 'Longtime'
                                                                        ? 'Longtime'
                                                                        : trim($kSince.($kSince !== '' && $kUnit !== '' ? ' ' : '').$kUnit);
                                                                @endphp
                                                                <tr>
                                                                    <td style="text-align:left">{{ $kr['condition'] }}</td>
                                                                    <td>{{ $kDuration !== '' ? $kDuration : '-' }}</td>
                                                                    <td style="text-align:left">{{ $kr['comment'] ?? '-' }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif

                                            @if($hasFundus)
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
                                            @endif

                                        </div>{{-- /left col --}}

                                        {{-- RIGHT: Complaint / H/O / Vision / O/E / Diagnosis / Advice --}}
                                        <div class="col-md-6 d-flex flex-column gap-2">

                                            @if(count($coRows))
                                                <div class="cv-box">
                                                    <table class="cv-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Complaint</th>
                                                                <th>Since</th>
                                                                <th>Eye</th>
                                                                <th>Comment</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($coRows as $cr)
                                                                <tr>
                                                                    <td style="text-align:left">{{ $cr['complaint'] }}</td>
                                                                    <td>{{ !empty($cr['since']) ? $cr['since'] . ' ' . ($cr['unit'] ?? '') : '-' }}</td>
                                                                    <td>{{ $cr['eye'] ?? '-' }}</td>
                                                                    <td style="text-align:left">{{ $cr['comment'] ?? '-' }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif

                                            @if(count($hnoList))
                                                <div class="cv-box">
                                                    <div class="cv-title">H/O</div>
                                                    <div>
                                                        @foreach($hnoList as $hv)
                                                            <span class="cv-badge">{{ $hv }}</span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            @if($hasVision)
                                                <div class="cv-box">
                                                    <div class="cv-title">Vision</div>
                                                    <div style="display:flex;flex-direction:column;gap:6px;font-size:11px;">
                                                        <div style="display:flex;flex-wrap:wrap;align-items:flex-start;gap:8px 16px;">
                                                            <span class="cv-vn-line" style="align-items:flex-start;">
                                                                <strong>Vn</strong>&nbsp;&lt;&nbsp;
                                                                <span style="display:inline-flex;flex-direction:column;line-height:1.35;">
                                                                    <span>{{ $vision['vn_re'] ?? '' }}</span>
                                                                    <span>{{ $vision['vn_le'] ?? '' }}</span>
                                                                </span>
                                                            </span>
                                                            <span class="cv-vn-line" style="align-items:flex-start;">
                                                                <strong>Vn C GL</strong>&nbsp;&lt;&nbsp;
                                                                <span style="display:inline-flex;flex-direction:column;line-height:1.35;">
                                                                    <span>{{ $pg['re']['vn'] ?? '' }}</span>
                                                                    <span>{{ $pg['le']['vn'] ?? '' }}</span>
                                                                </span>
                                                            </span>
                                                            <span class="cv-vn-line" style="align-items:flex-start;">
                                                                <strong>Pn/Vn</strong>&nbsp;&lt;&nbsp;
                                                                <span style="display:inline-flex;flex-direction:column;line-height:1.35;">
                                                                    <span>{{ $vision['pnvn_re'] ?? '' }}</span>
                                                                    <span>{{ $vision['pnvn_le'] ?? '' }}</span>
                                                                </span>
                                                            </span>
                                                        </div>
                                                        <div style="display:flex;flex-wrap:wrap;align-items:flex-start;gap:8px 16px;">
                                                            <span class="cv-vn-line" style="align-items:flex-start;">
                                                                <strong>Nr/Vn</strong>&nbsp;&lt;&nbsp;
                                                                <span style="display:inline-flex;flex-direction:column;line-height:1.35;">
                                                                    <span>{{ $vision['nrvn_re'] ?? '' }}</span>
                                                                    <span>{{ $vision['nrvn_le'] ?? '' }}</span>
                                                                </span>
                                                            </span>
                                                            <span class="cv-vn-line" style="align-items:flex-start;">
                                                                <strong>Nr/Vn C GL</strong>&nbsp;&lt;&nbsp;
                                                                <span style="display:inline-flex;flex-direction:column;line-height:1.35;">
                                                                    <span>{{ $pg['re']['near_vn'] ?? '' }}</span>
                                                                    <span>{{ $pg['le']['near_vn'] ?? '' }}</span>
                                                                </span>
                                                            </span>
                                                            <span class="cv-vn-line" style="align-items:flex-start;">
                                                                <strong>NCT</strong>&nbsp;&lt;&nbsp;
                                                                <span style="display:inline-flex;flex-direction:column;line-height:1.35;">
                                                                    <span>{{ $nct['iop_re'] ?? '' }}</span>
                                                                    <span>{{ $nct['iop_le'] ?? '' }}</span>
                                                                </span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            @if($hasOe)
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
                                                            @foreach($oeFields as $k => $lbl)
                                                                <tr>
                                                                    <th style="text-align:left;background:#f0f4f8;color:#1B4F72">{{ $lbl }}</th>
                                                                    @if($k === 'lens')
                                                                        <td>{{ $lensCv($oe, 're') }}</td>
                                                                        <td>{{ $lensCv($oe, 'le') }}</td>
                                                                    @else
                                                                        <td>{{ $cv($oe[$k . '_re'] ?? '') }}</td>
                                                                        <td>{{ $cv($oe[$k . '_le'] ?? '') }}</td>
                                                                    @endif
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif

                                            @if(!$isPrimary && $dxNames)
                                                <div class="cv-box">
                                                    <div class="cv-title">Diagnosis</div>
                                                    <div style="font-size:12px;line-height:1.5">{{ $dxNames }}</div>
                                                </div>
                                            @endif

                                            @if(!$isPrimary && $advTxt)
                                                <div class="cv-box">
                                                    <div class="cv-title">Advice</div>
                                                    <div style="font-size:12px;white-space:pre-line;line-height:1.6">{{ $advTxt }}</div>
                                                </div>
                                            @endif

                                        </div>{{-- /right col --}}
                                    </div>{{-- /row --}}
                                    @endunless

                                </div>{{-- /collapse --}}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    @endforeach
@endif
