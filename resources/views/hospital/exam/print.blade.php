<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription — {{ $patient->full_name }}</title>
    <style>
        :root {
            --ink: #101828;
            --navy: #173A5E;
            --accent-soft: #EAF1FC;
            --line: #C9D9F2;
            --line-soft: #E3EAF6;
            --muted: #64748B;
            --danger: #B3261E;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', system-ui, Arial, sans-serif;
            font-size: 12.5px;
            line-height: 1.45;
            color: var(--ink);
            background: #fff;
        }

        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 12mm 11mm 10mm;
        }

        /* ── Header ── */
        .doc-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 3px solid var(--navy);
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .doc-head__brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .doc-head__mark {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: #F5F8FC;
            border: 1px solid #E1E7F0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }

        .doc-head__mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 6px;
        }

        .doc-head__mark span {
            font-size: 24px;
            color: var(--navy);
        }

        .doc-head__hospital h1 {
            font-size: 19px;
            font-weight: 800;
            color: var(--navy);
            letter-spacing: .01em;
        }

        .doc-head__hospital p {
            font-size: 10.5px;
            color: var(--muted);
            margin-top: 3px;
        }

        .doc-head__doctor {
            text-align: right;
            font-size: 10.5px;
            color: #475467;
            line-height: 1.6;
        }

        .doc-head__doctor strong {
            display: block;
            font-size: 13px;
            color: var(--navy);
        }

        /* ── Patient strip ── */
        .patient-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 22px;
            background: var(--accent-soft);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 9px 16px;
            margin-bottom: 14px;
        }

        .patient-strip__item label {
            display: block;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #5b7bab;
            margin-bottom: 2px;
        }

        .patient-strip__item span {
            font-size: 12.5px;
            font-weight: 700;
            color: #15202b;
        }

        /* ── Two-column layout ── */
        .rx-columns {
            display: grid;
            grid-template-columns: 1.12fr 1fr;
            gap: 16px;
            align-items: start;
        }

        .rx-stack {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        /* ── Cards ── */
        .card {
            border: 1px solid var(--line);
            border-radius: 9px;
            overflow: hidden;
            background: #fff;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .card__title {
            background: var(--navy);
            color: #fff;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            padding: 7px 11px;
        }

        .card__title--soft {
            background: var(--accent-soft);
            color: var(--navy);
        }

        .card__body {
            padding: 9px 11px;
        }

        .field-label {
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--navy);
            margin: 9px 0 3px;
        }

        .field-label:first-child {
            margin-top: 0;
        }

        /* ── Generic data table ── */
        table.dtable {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }

        table.dtable th {
            background: var(--accent-soft);
            color: var(--navy);
            font-weight: 600;
            font-size: 9.5px;
            padding: 4px 6px;
            border: 1px solid var(--line-soft);
            text-align: center;
        }

        table.dtable td {
            padding: 4px 6px;
            border: 1px solid var(--line-soft);
            text-align: center;
        }

        table.dtable th:first-child,
        table.dtable td:first-child {
            text-align: left;
        }

        table.dtable + table.dtable {
            margin-top: 6px;
        }

        .row-tag {
            font-weight: 700;
            font-size: 9.5px;
            background: #F1F5FC;
            color: #42546b;
            text-align: center;
            width: 22px;
        }

        /* ── Chips ── */
        .chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin: 5px 0;
        }

        .chip {
            background: rgba(23, 58, 94, .08);
            border: 1px solid rgba(23, 58, 94, .2);
            color: var(--navy);
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 10px;
        }

        /* ── Vision strip ── */
        .vision-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            font-size: 10.5px;
            border-top: 1px dashed var(--line);
            padding-top: 7px;
            margin-top: 7px;
        }

        .vision-strip b {
            color: var(--navy);
        }

        /* ── PG / NrPG bracket lines ── */
        .bracket-line {
            display: inline-flex;
            align-items: center;
            margin-right: 14px;
            margin-bottom: 4px;
        }

        .bracket-line__label {
            font-weight: 700;
            font-size: 10.5px;
            margin-right: 3px;
        }

        .bracket-line__mark {
            font-weight: 300;
            font-size: 1.3rem;
            line-height: .5;
            margin: 0 4px;
        }

        .bracket-line__values {
            display: flex;
            flex-direction: column;
            font-size: 10.5px;
            line-height: 1.3;
        }

        /* ── Eye band (PG / ST headers) ── */
        .eye-band {
            background: var(--navy);
            color: #fff;
            text-align: center;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .05em;
            padding: 4px 8px;
        }

        .st-add {
            font-size: 10.5px;
            margin-top: 5px;
            color: var(--navy);
        }

        /* ── O/E & Fundus tables ── */
        table.exam-table td:first-child {
            background: #F1F5FC;
            font-weight: 700;
            font-size: 10px;
            text-align: left;
            padding-left: 10px;
            width: 78px;
        }

        table.exam-table tr:nth-child(even) td {
            background: #F8FAFE;
        }

        table.exam-table tr:nth-child(even) td:first-child {
            background: #EDF2FA;
        }

        .oe-val-hi {
            color: var(--danger);
            font-weight: 600;
        }

        .oe-val-hib {
            color: var(--navy);
            font-weight: 600;
        }

        /* ── Diagnosis & Rx ── */
        .dx-line {
            font-size: 11px;
            margin-bottom: 4px;
        }

        /* ── Medicines ── */
        table.meds {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-top: 5px;
        }

        table.meds th {
            background: var(--navy);
            color: #fff;
            font-size: 9.5px;
            font-weight: 600;
            text-align: left;
            padding: 5px 8px;
        }

        table.meds td {
            padding: 5px 8px;
            border-bottom: 1px solid var(--line-soft);
            vertical-align: top;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        table.meds tr:nth-child(even) td {
            background: #F8FAFE;
        }

        .med-sub {
            font-size: 9px;
            color: #667;
        }

        /* ── Footer ── */
        .sign-off {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 1px solid var(--line);
            margin-top: 16px;
            padding-top: 11px;
        }

        .sign-off__box {
            text-align: right;
            font-size: 10.5px;
        }

        .sign-off__line {
            width: 140px;
            border-top: 1px solid #333;
            margin: 32px 0 4px auto;
        }

        .toolbar {
            background: #f0f0f0;
            padding: 8px 12mm;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .toolbar__print {
            background: var(--navy);
            color: #fff;
            border: none;
            padding: 6px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }

        .toolbar__back {
            color: var(--navy);
            font-size: 13px;
            text-decoration: none;
        }

        @media print {
            body {
                background: #fff;
            }

            .no-print {
                display: none !important;
            }

            .sheet {
                padding: 6mm 7mm 5mm;
            }

            .doc-head {
                margin-bottom: 8px;
                padding-bottom: 6px;
            }

            .patient-strip {
                margin-bottom: 8px;
                padding: 6px 12px;
            }

            .rx-columns {
                gap: 10px;
            }

            .rx-stack {
                gap: 7px;
            }

            .card__body {
                padding: 6px 9px;
            }

            .sign-off {
                margin-top: 8px;
                padding-top: 6px;
            }

            .sign-off__line {
                margin: 16px 0 3px auto;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        @page {
            size: A4 portrait;
            margin: 10mm;
        }
    </style>
</head>

<body>

    {{-- Print / Back controls --}}
    <script>
        var _backUrl = {!! json_encode($backUrl ?? 'javascript:history.back()') !!};
        function _doPrint() {
            if (_backUrl !== 'javascript:history.back()') {
                window.onafterprint = function () { window.location.href = _backUrl; };
            }
            window.print();
        }
    </script>
    <div class="no-print toolbar">
        <button class="toolbar__print" onclick="_doPrint()">Print</button>
        <a class="toolbar__back" href="{{ $backUrl ?? 'javascript:history.back()' }}">← Back</a>
    </div>

    @php
        $complaintMasters = $complaintMasters ?? collect();
        $kcoMasters = $kcoMasters ?? collect();
        $diagnosisMasters = $diagnosisMasters ?? collect();
        $adviceMasters = $adviceMasters ?? collect();

        $patient = $patient ?? (object) [
            'full_name' => '—',
            'patient_code' => '—',
            'age' => '—',
            'gender' => '—',
            'contact_no' => null,
            'appointment_date' => null,
        ];
        $exam = $exam ?? (object) [
            'exam_data' => [],
            'doctor' => null,
            'prescriptions' => collect(),
            'examined_at' => null,
        ];

        $ed = $exam->exam_data ?? [];
        $vision = $ed['vision'] ?? [];
        $pg = $ed['pg'] ?? [];
        $st = $ed['st'] ?? [];
        $nct = $ed['nct'] ?? [];
        $oe = $ed['oe'] ?? [];
        $fundus = $ed['fundus'] ?? [];
        $coRows = $ed['co_rows'] ?? [];
        $kcoRows = $ed['kco_rows'] ?? [];
        $hnoList = array_filter(array_map('trim', explode(',', $ed['history'] ?? '')));
        $diagnoses = $ed['diagnoses'] ?? [];
        $advices = $ed['advices'] ?? [];

        $dash = '—';

        function rxVal($v, $dash = '—')
        {
            return ($v !== null && $v !== '') ? $v : $dash;
        }
    @endphp

    <div class="sheet">

        {{-- ── Header ── --}}
        @php $letterPad = hospital_setting('letter_pad', 'unavailable'); @endphp

        @if($letterPad === 'available')
            <div class="doc-head">
                <div></div>
                <div class="doc-head__doctor">
                    @if($primaryDoctorName)
                        <strong>P: {{ $primaryDoctorName }}</strong>
                    @endif
                    @if($secondaryDoctorName)
                        <strong>S: {{ $secondaryDoctorName }}</strong>
                    @endif
                    @if(!$primaryDoctorName && !$secondaryDoctorName)
                        <strong>—</strong>
                    @endif
                    @if($exam->doctor?->designation ?? null)
                        <span style="display:block">{{ $exam->doctor->designation }}</span>
                    @endif
                    <span style="display:block;margin-top:2px;color:#888">Date:
                        {{ $exam->examined_at?->format('d M Y') ?? now()->format('d M Y') }}</span>
                </div>
            </div>
        @else
            <div class="doc-head">
                <div class="doc-head__brand">
                    <div class="doc-head__mark">
                        @if(hospital_logo_url())
                            <img src="{{ hospital_logo_url() }}" alt="{{ hospital_name() }} logo">
                        @else
                            <span>👁</span>
                        @endif
                    </div>
                    <div class="doc-head__hospital">
                        <h1>{{ hospital_name() }}</h1>
                        <p>
                            @if(hospital_full_address()) {{ hospital_full_address() }} @endif
                            @if(hospital_contact_number()) &nbsp;|&nbsp; {{ hospital_contact_number() }} @endif
                            @if(hospital_official_email()) &nbsp;|&nbsp; {{ hospital_official_email() }} @endif
                        </p>
                    </div>
                </div>
                <div class="doc-head__doctor">
                    @if($primaryDoctorName)
                        <strong>P: {{ $primaryDoctorName }}</strong>
                    @endif
                    @if($secondaryDoctorName)
                        <strong>S: {{ $secondaryDoctorName }}</strong>
                    @endif
                    @if(!$primaryDoctorName && !$secondaryDoctorName)
                        <strong>—</strong>
                    @endif
                    @if($exam->doctor?->designation ?? null)
                        <span style="display:block">{{ $exam->doctor->designation }}</span>
                    @endif
                    <span style="display:block;margin-top:2px;color:#888">Date:
                        {{ $exam->examined_at?->format('d M Y') ?? now()->format('d M Y') }}</span>
                </div>
            </div>
        @endif

        {{-- ── Patient strip ── --}}
        <div class="patient-strip">
            <div class="patient-strip__item">
                <label>Patient</label>
                <span>{{ $patient->full_name }}</span>
            </div>
            <div class="patient-strip__item">
                <label>MRD</label>
                <span>{{ $patient->patient_code }}</span>
            </div>
            <div class="patient-strip__item">
                <label>Age / Gender</label>
                <span>{{ $patient->age }}y / {{ ucfirst($patient->gender) }}</span>
            </div>
            @if($patient->contact_no)
                <div class="patient-strip__item">
                    <label>Contact</label>
                    <span>{{ $patient->contact_no }}</span>
                </div>
            @endif
            <div class="patient-strip__item">
                <label>Visit Date</label>
                <span>{{ $patient->appointment_date?->format('d M Y') ?? $exam->examined_at?->format('d M Y') ?? '—' }}</span>
            </div>
        </div>

        {{-- ── Two-column layout ── --}}
        <div class="rx-columns">

            {{-- ══ LEFT COLUMN: PG / ST / K-C-O ══ --}}
            <div class="rx-stack">

                {{-- PG --}}
                @php
                    $hasPg = !empty($pg['re']['ds']) || !empty($pg['le']['ds']) ||
                        !empty($pg['re']['ns']) || !empty($pg['le']['ns']);
                    $pgFmt = fn($s, $c, $a) => ($s ?: '-') . ' / ' . ($c ?: '-') . ' X ' . ($a ?: '-');
                @endphp
                @if($hasPg)
                    <article class="card">
                        <div class="card__title">PG</div>
                        <div class="card__body" style="display:flex;flex-wrap:wrap;align-items:flex-start;">
                            <div class="bracket-line">
                                <span class="bracket-line__label">PG</span>
                                <span class="bracket-line__mark">&lt;</span>
                                <div class="bracket-line__values">
                                    <span>{{ $pgFmt($pg['re']['ds'] ?? '', $pg['re']['dc'] ?? '', $pg['re']['ax'] ?? '') }}</span>
                                    <span>{{ $pgFmt($pg['le']['ds'] ?? '', $pg['le']['dc'] ?? '', $pg['le']['ax'] ?? '') }}</span>
                                </div>
                            </div>
                            <div class="bracket-line">
                                <span class="bracket-line__label">NrPG</span>
                                <span class="bracket-line__mark">&lt;</span>
                                <div class="bracket-line__values">
                                    <span>{{ $pgFmt($pg['re']['ns'] ?? '', $pg['re']['nc'] ?? '', $pg['re']['na'] ?? '') }}</span>
                                    <span>{{ $pgFmt($pg['le']['ns'] ?? '', $pg['le']['nc'] ?? '', $pg['le']['na'] ?? '') }}</span>
                                </div>
                            </div>
                        </div>
                    </article>
                @endif

                {{-- ST --}}
                @php
                    $hasSt = !empty($st['re']['ds']) || !empty($st['le']['ds']) ||
                        !empty($st['re']['ns']) || !empty($st['le']['ns']);
                    $addRe = $st['re']['add'] ?? '';
                    $addLe = $st['le']['add'] ?? '';
                @endphp
                @if($hasSt)
                    <article class="card">
                        <div class="card__title">ST</div>
                        <div class="card__body">
                            <table class="dtable">
                                <thead>
                                    <tr>
                                        <td style="border:none;"></td>
                                        <th colspan="3" class="eye-band">RIGHT EYE (RE)</th>
                                        <th colspan="3" class="eye-band">LEFT EYE (LE)</th>
                                    </tr>
                                    <tr>
                                        <td style="border:none;width:18px;"></td>
                                        <th>SPH</th>
                                        <th>CYL</th>
                                        <th>AXIS</th>
                                        <th>SPH</th>
                                        <th>CYL</th>
                                        <th>AXIS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="row-tag">D</td>
                                        <td>{{ rxVal($st['re']['ds'] ?? '') }}</td>
                                        <td>{{ rxVal($st['re']['dc'] ?? '') }}</td>
                                        <td>{{ rxVal($st['re']['ax'] ?? '') }}</td>
                                        <td>{{ rxVal($st['le']['ds'] ?? '') }}</td>
                                        <td>{{ rxVal($st['le']['dc'] ?? '') }}</td>
                                        <td>{{ rxVal($st['le']['ax'] ?? '') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="row-tag">N</td>
                                        <td>{{ rxVal($st['re']['ns'] ?? '') }}</td>
                                        <td>{{ rxVal($st['re']['nc'] ?? '') }}</td>
                                        <td>{{ rxVal($st['re']['na'] ?? '') }}</td>
                                        <td>{{ rxVal($st['le']['ns'] ?? '') }}</td>
                                        <td>{{ rxVal($st['le']['nc'] ?? '') }}</td>
                                        <td>{{ rxVal($st['le']['na'] ?? '') }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            @if($addRe || $addLe || !empty($st['add']))
                                <div class="st-add">
                                    <b>ADD</b>&nbsp;
                                    RE: {{ $addRe ?: ($st['add'] ?? $dash) }}&nbsp;&nbsp;
                                    LE: {{ $addLe ?: $dash }}
                                </div>
                            @endif
                        </div>
                    </article>
                @endif

                {{-- K/C/O --}}
                @if(!empty($kcoRows))
                    <article class="card">
                        <div class="card__title">K/C/O</div>
                        <div class="card__body">
                            <table class="dtable">
                                <thead>
                                    <tr>
                                        <th>Condition</th>
                                        <th>Since</th>
                                        <th>Comment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($kcoRows as $krow)
                                        <tr>
                                            <td>{{ $krow['condition'] ?? $dash }}</td>
                                            <td>{{ ($krow['since'] ?? '') ? ($krow['since'] . ' ' . ($krow['unit'] ?? '')) : $dash }}
                                            </td>
                                            <td>{{ $krow['comment'] ?? $dash }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </article>
                @endif

            </div>{{-- /LEFT COLUMN --}}

            {{-- ══ RIGHT COLUMN: Complaint / H-O / Vision / O-E / Fundus ══ --}}
            <div class="rx-stack">

                {{-- Complaint --}}
                @if(!empty($coRows))
                    <article class="card">
                        <div class="card__title">Complaint</div>
                        <div class="card__body">
                            <table class="dtable">
                                <thead>
                                    <tr>
                                        <th>Complaint</th>
                                        <th>Since</th>
                                        <th>Eye</th>
                                        <th>Comment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($coRows as $row)
                                        <tr>
                                            <td>{{ $row['complaint'] ?? $dash }}</td>
                                            <td>{{ ($row['since'] ?? '') ? ($row['since'] . ' ' . ($row['unit'] ?? '')) : $dash }}
                                            </td>
                                            <td>{{ $row['eye'] ?? $dash }}</td>
                                            <td>{{ $row['comment'] ?? $dash }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </article>
                @endif

                {{-- H/O --}}
                @if(!empty($hnoList))
                    <article class="card">
                        <div class="card__title">H/O</div>
                        <div class="card__body">
                            <div class="chip-row">
                                @foreach($hnoList as $hv)
                                    <span class="chip">{{ $hv }}</span>
                                @endforeach
                            </div>
                        </div>
                    </article>
                @endif

                {{-- Vision --}}
                @php
                    $vnRe = rxVal($vision['vn_re'] ?? '');
                    $vnLe = rxVal($vision['vn_le'] ?? '');
                    $phRe = rxVal($vision['pnvn_re'] ?? '');
                    $phLe = rxVal($vision['pnvn_le'] ?? '');
                    $nrRe = rxVal($vision['nrvn_re'] ?? '');
                    $nrLe = rxVal($vision['nrvn_le'] ?? '');
                    $iopRe = rxVal($nct['iop_re'] ?? '');
                    $iopLe = rxVal($nct['iop_le'] ?? '');
                    $hasVision = ($vnRe !== $dash || $vnLe !== $dash || $phRe !== $dash || $nrRe !== $dash);
                @endphp
                @if($hasVision || ($iopRe !== $dash || $iopLe !== $dash))
                    <article class="card">
                        <div class="card__title">Vision</div>
                        <div class="card__body">
                            <div class="vision-strip">
                                @if($hasVision)
                                    <span><b>Vn</b> &lt; {{ $vnRe }}&nbsp;/&nbsp;{{ $vnLe }}</span>
                                    <span><b>PH</b> &lt; {{ $phRe }}&nbsp;/&nbsp;{{ $phLe }}</span>
                                    <span><b>NrVn</b> &lt; {{ $nrRe }}&nbsp;/&nbsp;{{ $nrLe }}</span>
                                @endif
                                @if($iopRe !== $dash || $iopLe !== $dash)
                                    <span><b>IOP:</b>&nbsp;{{ $iopRe }}/{{ $iopLe }}</span>
                                @endif
                            </div>
                        </div>
                    </article>
                @endif

                {{-- O/E --}}
                <article class="card">
                    <div class="card__title">O/E</div>
                    <div class="card__body" style="padding:0">
                        @php
                            $oeFields = [
                                'sac' => 'SAC',
                                'lid' => 'LID',
                                'conj' => 'CONJ',
                                'cornea' => 'CORNEA',
                                'ac' => 'AC',
                                'iris' => 'IRIS',
                                'pupil' => 'PUPIL',
                                'lens' => 'LENS',
                                'em' => 'EM',
                                'covertest' => 'COVERTEST',
                                'other' => 'OTHER',
                            ];
                            $hasOe = collect(array_keys($oeFields))
                                ->first(fn($k) => !empty($oe[$k . '_re']) || !empty($oe[$k . '_le']));
                        @endphp
                        <table class="dtable exam-table">
                            <thead>
                                <tr>
                                    <th style="width:78px">O/E</th>
                                    <th>RIGHT</th>
                                    <th>LEFT</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($oeFields as $key => $label)
                                    @php
                                        $reVal = $oe[$key . '_re'] ?? '';
                                        $leVal = $oe[$key . '_le'] ?? '';
                                    @endphp
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td>{{ $reVal !== '' ? $reVal : '-' }}</td>
                                        <td>{{ $leVal !== '' ? $leVal : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </article>

                {{-- Fundus --}}
                <article class="card">
                    <div class="card__title">Fundus</div>
                    <div class="card__body" style="padding:0">
                        <table class="dtable exam-table">
                            <thead>
                                <tr>
                                    <th style="width:78px">Fundus</th>
                                    <th>RIGHT</th>
                                    <th>LEFT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>DISC</td>
                                    <td>{{ $fundus['disc_re'] ?? '-' }}</td>
                                    <td>{{ $fundus['disc_le'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>FR</td>
                                    <td>{{ $fundus['fr_re'] ?? '-' }}</td>
                                    <td>{{ $fundus['fr_le'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>COMMENT</td>
                                    <td>{{ $fundus['comment_re'] ?? '-' }}</td>
                                    <td>{{ $fundus['comment_le'] ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>

            </div>{{-- /RIGHT COLUMN --}}

        </div>{{-- /rx-columns --}}

        {{-- ── Signature ── --}}
        <div class="sign-off">
            <div></div>
            <div class="sign-off__box">
                <div class="sign-off__line"></div>
                <div>{{ $exam->doctor?->name ?? '' }}</div>
                <div style="font-size:9px;color:#666">Signature / Stamp</div>
            </div>
        </div>

    </div>{{-- /sheet --}}

</body>

</html>
