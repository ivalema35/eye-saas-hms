<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secondary Rx — {{ $patient->full_name }}</title>
    <style>
        :root {
            --ink: #1a1a1a;
            --navy: #1B4F72;
            --accent-soft: #eef4f9;
            --line: #343a40;
            --line-soft: #343a40;
            --muted: #64748B;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', system-ui, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: var(--ink);
            background: #fff;
        }

        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 10mm 8mm 8mm;
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
            gap: 18px;
            background: #eef4f9;
            border: 1px solid #343a40;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 10px;
        }

        .patient-strip__item label {
            display: block;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #1B4F72;
            margin-bottom: 2px;
        }

        .patient-strip__item span {
            font-size: 12px;
            font-weight: 700;
            color: #15202b;
        }

        /* ── Two-column layout ── */
        .rx-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            align-items: start;
        }

        .rx-stack {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        /* ── Cards (match exam canvas-box) ── */
        .card {
            border: 1px solid #343a40;
            border-radius: 4px;
            overflow: hidden;
            background: #fff;
            padding: 6px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .card__title {
            background: #1B4F72;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: 2px 6px;
            margin-bottom: 4px;
        }

        .card__title--soft {
            background: #eef4f9;
            color: #1B4F72;
        }

        .card__body {
            padding: 0;
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
            font-size: 10px;
            margin-bottom: 0;
        }

        table.dtable th {
            background: #eef4f9;
            color: #1B4F72;
            font-weight: 600;
            font-size: 10px;
            padding: 2px 5px;
            border: 1px solid #343a40;
            text-align: center;
        }

        table.dtable th.sub-band,
        table.dtable th.eye-band {
            background: #1B4F72;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            border: 1px solid #1B4F72;
            padding: 2px;
        }

        table.dtable td {
            padding: 2px 5px;
            border: 1px solid #343a40;
            text-align: center;
            font-size: 10px;
        }

        table.dtable th:first-child,
        table.dtable td:first-child {
            text-align: center;
        }

        table.dtable+table.dtable {
            margin-top: 4px;
        }

        .row-tag {
            font-weight: 700;
            font-size: 10px;
            background: #f0f4f8;
            color: #1B4F72;
            text-align: center;
            width: 22px;
        }

        /* ── Chips ── */
        .chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 3px;
            margin: 2px 0;
        }

        .chip {
            background: #eef4f9;
            border: none;
            color: #1B4F72;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 600;
            padding: 1px 8px;
        }

        .chip--dx {
            background: #fde8e8;
            border: 1px solid #e57373;
            color: #b71c1c;
        }

        .sub-band {
            background: #1B4F72;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            text-align: center;
            padding: 2px 6px;
        }

        .sub-band--soft {
            background: #eef4f9;
            color: #1B4F72;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            text-align: center;
            padding: 2px 6px;
            margin-bottom: 4px;
        }

        /* ── Vision / PG bracket (match canvas makeVn) ── */
        .vision-strip,
        .pg-strip {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            gap: 0;
        }

        .bracket-line {
            display: inline-flex;
            align-items: center;
            margin-right: 12px;
            margin-bottom: 2px;
        }

        .bracket-line__label {
            font-weight: 700;
            font-size: 11px;
            margin-right: 4px;
        }

        .bracket-line__mark {
            font-weight: 300;
            font-size: 1.4rem;
            line-height: .5;
            margin: 0 4px;
        }

        .bracket-line__values {
            display: flex;
            flex-direction: column;
            font-size: 11px;
            line-height: 1.3;
        }

        .vision-strip b {
            color: #1B4F72;
        }

        /* ── Eye band ── */
        .eye-band {
            background: #1B4F72;
            color: #fff;
            text-align: center;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .06em;
            padding: 2px;
        }

        .st-add {
            font-size: 10px;
            margin-top: 3px;
            color: #475569;
        }

        .st-add b {
            color: #1B4F72;
        }

        /* ── O/E & Fundus ── */
        table.exam-table thead th {
            background: #343a40 !important;
            color: #fff !important;
            border-color: #343a40 !important;
            font-weight: 700;
            font-size: 10px;
            padding: 2px 5px;
        }

        table.exam-table tbody th,
        table.exam-table td:first-child {
            background: #f0f4f8;
            color: #1B4F72;
            font-weight: 700;
            font-size: 10px;
            text-align: left;
            padding: 2px 5px;
            width: auto;
        }

        table.exam-table tr:nth-child(even) td {
            background: #fff;
        }

        table.exam-table tr:nth-child(even) td:first-child {
            background: #f0f4f8;
        }

        /* ── Diagnosis ── */
        .dx-line {
            font-size: 11px;
            margin-bottom: 4px;
        }

        /* ── Medicines ── */
        table.meds {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 0;
        }

        table.meds thead tr:first-child th {
            background: #343a40;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: 2px 5px;
            border: 1px solid #343a40;
        }

        table.meds thead tr:nth-child(2) th {
            background: #eef4f9;
            color: #1B4F72;
            font-size: 10px;
            font-weight: 600;
            text-align: left;
            padding: 2px 5px;
            border: 1px solid #343a40;
        }

        table.meds td {
            padding: 2px 5px;
            border: 1px solid #343a40;
            vertical-align: top;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        table.meds tr:nth-child(even) td {
            background: #fff;
        }

        .med-sub {
            font-size: 9px;
            color: #667;
        }

        /* ── Advice ── */
        .advice-list {
            list-style: none;
            padding-left: 2px;
            font-size: 11px;
            line-height: 1.9;
        }

        .advice-list li::before {
            content: '• ';
            color: var(--navy);
            font-weight: 700;
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

        <a class="toolbar__back" href="{{ ($backUrl ?? null) && $backUrl !== 'javascript:history.back()' ? $backUrl : route('hospital.patients.index', ['slug' => request()->route('slug')]) }}">
            Back
        </a>
    </div>

    @php
        $ed = $exam->exam_data ?? [];
        $vision = $ed['vision'] ?? [];
        $pg = $ed['pg'] ?? [];
        $st = $ed['st'] ?? [];
        $nct = $ed['nct'] ?? [];
        $oe = $ed['oe'] ?? [];
        $fundus = $ed['fundus'] ?? [];
        $coRows = $ed['co_rows'] ?? [];
        $kcoRows = array_values(array_filter($ed['kco_rows'] ?? [], fn ($r) => !empty($r['condition'])));
        $hnoList = array_filter(array_map('trim', explode(',', $ed['history'] ?? '')));
        $diagnoses = $ed['diagnoses'] ?? [];
        $rxMeds = $ed['rx'] ?? [];
        $adviceText = $exam->advice ?? $ed['advice'] ?? '';

        $dosageMap = $dosages->keyBy('id');
        $routeMap = ($routes ?? collect())->keyBy('id');
        $dash = '—';

        function secRxVal($v, $d = '—')
        {
            return ($v !== null && $v !== '') ? $v : $d;
        }

        function secLensOeVal($oe, $eye, $d = '—')
        {
            $base = $oe['lens_' . $eye] ?? '';
            if ($base === null || $base === '') {
                return $d;
            }

            $pseudo = $oe['pseudophakia_' . $eye] ?? [];
            $extras = array_filter([
                $pseudo['operation_type'] ?? '',
                !empty($pseudo['operation_expense']) ? currency_symbol() . $pseudo['operation_expense'] : '',
                $pseudo['hospital_name'] ?? '',
            ], fn($v) => $v !== '' && $v !== null);

            return $extras ? $base . ' (' . implode(', ', $extras) . ')' : $base;
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

            {{-- ══ LEFT COLUMN: Medicine / PG / ST / K-C-O ══ --}}
            <div class="rx-stack">

                {{-- Medicine --}}
                @if(!empty($rxMeds))
                    <article class="card">
                        <div class="card__title">Medicine</div>
                        <div class="card__body" style="padding:0">
                            <table class="meds">
                                <thead>
                                    <tr>
                                        <th>Medicine Name</th>
                                        <th>Dosage</th>
                                        <th>Days</th>
                                        <th>QTY</th>
                                        <th>Route of Administration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rxMeds as $med)
                                        @if(!empty($med['name']))
                                            <tr>
                                                <td><span style="font-weight:600">{{ $med['name'] }}</span></td>
                                                <td>{{ !empty($med['dosage_id']) ? ($dosageMap[$med['dosage_id']]->dosage ?? '—') : '—' }}</td>
                                                <td>{{ !empty($med['duration']) ? $med['duration'] . (is_numeric($med['duration']) ? ' Days' : '') : '—' }}</td>
                                                <td>{{ !empty($med['quantity']) ? $med['quantity'] : '—' }}</td>
                                                <td>{{ !empty($med['route_id']) ? ($routeMap[$med['route_id']]->name ?? '—') : '—' }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </article>
                @endif

                {{-- PG --}}
                @php
                    $hasPg = !empty($pg['re']['ds']) || !empty($pg['le']['ds']) ||
                        !empty($pg['re']['ns']) || !empty($pg['le']['ns']);
                    $pgFmt = function ($s, $c, $a) {
                        $s = trim((string) $s);
                        $c = trim((string) $c);
                        $a = trim((string) $a);
                        if ($s === '' && $c === '' && $a === '') {
                            return '';
                        }
                        $out = ($s !== '' ? $s : '-') . ' / ' . ($c !== '' ? $c : '-');
                        if ($a !== '') {
                            $out .= ' X ' . $a;
                        }

                        return $out;
                    };
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
                        !empty($st['re']['ns']) || !empty($st['le']['ns']) ||
                        !empty($st['bifocal']) || !empty($st['nd_separate']) ||
                        !empty($st['progressive']) || !empty($st['computer_uses']);
                    $stOptLabels = collect([
                        'bifocal' => 'Bifocal',
                        'nd_separate' => 'Near & Distance Separate',
                        'progressive' => 'Progressive',
                        'computer_uses' => 'Computer Uses',
                    ])->filter(fn ($label, $key) => !empty($st[$key]))->values()->all();
                @endphp
                @if($hasSt)
                    <article class="card">
                        <div class="card__title">ST</div>
                        <div class="card__body">
                            <table class="dtable">
                                <thead>
                                    <tr>
                                        <th colspan="4" class="eye-band">RIGHT EYE</th>
                                        <th colspan="4" class="eye-band">LEFT EYE</th>
                                    </tr>
                                    <tr>
                                        <th>{{ $st['re']['vn'] ?? '' }}</th>
                                        <th>SPH</th>
                                        <th>CYL</th>
                                        <th>AXIS</th>
                                        <th>{{ $st['le']['vn'] ?? '' }}</th>
                                        <th>SPH</th>
                                        <th>CYL</th>
                                        <th>AXIS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="row-tag">D</td>
                                        <td>{{ secRxVal($st['re']['ds'] ?? '') }}</td>
                                        <td>{{ secRxVal($st['re']['dc'] ?? '') }}</td>
                                        <td>{{ secRxVal($st['re']['ax'] ?? '') }}</td>
                                        <td class="row-tag">D</td>
                                        <td>{{ secRxVal($st['le']['ds'] ?? '') }}</td>
                                        <td>{{ secRxVal($st['le']['dc'] ?? '') }}</td>
                                        <td>{{ secRxVal($st['le']['ax'] ?? '') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="row-tag">N</td>
                                        <td>{{ secRxVal($st['re']['ns'] ?? '') }}</td>
                                        <td>{{ secRxVal($st['re']['nc'] ?? '') }}</td>
                                        <td>{{ secRxVal($st['re']['na'] ?? '') }}</td>
                                        <td class="row-tag">N</td>
                                        <td>{{ secRxVal($st['le']['ns'] ?? '') }}</td>
                                        <td>{{ secRxVal($st['le']['nc'] ?? '') }}</td>
                                        <td>{{ secRxVal($st['le']['na'] ?? '') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            @if(!empty($stOptLabels))
                                <div style="margin-top:4px;font-size:10px;color:#1B4F72;font-weight:600;">
                                    {{ implode(' · ', $stOptLabels) }}
                                </div>
                            @endif
                        </div>
                    </article>
                @endif

                {{-- K/C/O --}}
                @if(!empty($kcoRows))
                    <article class="card">
                        <div class="card__body" style="padding:0">
                            <table class="dtable">
                                <thead>
                                    <tr>
                                        <th colspan="3" class="sub-band" style="border:none;text-align:left;">K/C/O</th>
                                    </tr>
                                    <tr>
                                        <th>Condition</th>
                                        <th>Since/Duration</th>
                                        <th>Comment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($kcoRows as $krow)
                                        @php
                                            $kSince = trim((string) ($krow['since'] ?? ''));
                                            $kUnit = trim((string) ($krow['unit'] ?? ''));
                                            $kDuration = $kUnit === 'Longtime'
                                                ? 'Longtime'
                                                : trim($kSince.($kSince !== '' && $kUnit !== '' ? ' ' : '').$kUnit);
                                        @endphp
                                        <tr>
                                            <td>{{ $krow['condition'] ?? $dash }}</td>
                                            <td>{{ $kDuration !== '' ? $kDuration : $dash }}</td>
                                            <td>{{ $krow['comment'] ?? $dash }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </article>
                @endif

                {{-- Fundus --}}
                <article class="card">
                    <div class="card__title">Fundus</div>
                    <div class="card__body">
                        <table class="dtable exam-table">
                            <thead>
                                <tr>
                                    <th>Fundus</th>
                                    <th>RIGHT</th>
                                    <th>LEFT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th>DISC</th>
                                    <td>{{ secRxVal($fundus['disc_re'] ?? '', '-') }}</td>
                                    <td>{{ secRxVal($fundus['disc_le'] ?? '', '-') }}</td>
                                </tr>
                                <tr>
                                    <th>FR</th>
                                    <td>{{ secRxVal($fundus['fr_re'] ?? '', '-') }}</td>
                                    <td>{{ secRxVal($fundus['fr_le'] ?? '', '-') }}</td>
                                </tr>
                                <tr>
                                    <th>COMMENT</th>
                                    <td>{{ secRxVal($fundus['comment_re'] ?? '', '-') }}</td>
                                    <td>{{ secRxVal($fundus['comment_le'] ?? '', '-') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>

            </div>{{-- /LEFT COLUMN --}}

            {{-- ══ RIGHT COLUMN: Complaint / H-O / Vision / O-E / Diagnosis / Advice ══ --}}
            <div class="rx-stack">

                {{-- Complaint --}}
                @if(!empty($coRows))
                    <article class="card">
                        <div class="card__body" style="padding:0">
                            <table class="dtable">
                                <thead>
                                    <tr>
                                        <th colspan="4" class="sub-band" style="border:none;text-align:left;">C/O</th>
                                    </tr>
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
                <article class="card">
                    <div class="card__title">H/O</div>
                    <div class="card__body" style="min-height:18px;">
                        @if(!empty($hnoList))
                            <div class="chip-row">
                                @foreach($hnoList as $hv)
                                    <span class="chip">{{ $hv }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>

                {{-- Vision --}}
                @php
                    $blankVn = fn ($v) => ($v !== null && trim((string) $v) !== '') ? trim((string) $v) : '';
                    $vnRe = $blankVn($vision['vn_re'] ?? '');
                    $vnLe = $blankVn($vision['vn_le'] ?? '');
                    $phRe = $blankVn($vision['pnvn_re'] ?? '');
                    $phLe = $blankVn($vision['pnvn_le'] ?? '');
                    $nrRe = $blankVn($vision['nrvn_re'] ?? '');
                    $nrLe = $blankVn($vision['nrvn_le'] ?? '');
                    $iopRe = $blankVn($nct['iop_re'] ?? '');
                    $iopLe = $blankVn($nct['iop_le'] ?? '');
                    $glVnRe = $blankVn($pg['re']['vn'] ?? '');
                    $glVnLe = $blankVn($pg['le']['vn'] ?? '');
                    $glNrRe = $blankVn($pg['re']['near_vn'] ?? '');
                    $glNrLe = $blankVn($pg['le']['near_vn'] ?? '');
                    $hasVn = ($vnRe !== '' || $vnLe !== '' || $phRe !== '' || $phLe !== '' || $nrRe !== '' || $nrLe !== ''
                        || $glVnRe !== '' || $glVnLe !== '' || $glNrRe !== '' || $glNrLe !== '');
                    $hasIop = ($iopRe !== '' || $iopLe !== '');
                @endphp
                @if($hasVn || $hasIop)
                    <article class="card">
                        <div class="card__title">Vision</div>
                        <div class="card__body">
                            <div class="vision-strip" style="flex-direction:column;align-items:flex-start;gap:6px;">
                                <div style="display:flex;flex-wrap:wrap;align-items:flex-start;gap:8px 18px;">
                                    <div class="bracket-line">
                                        <span class="bracket-line__label">Vn</span>
                                        <span class="bracket-line__mark">&lt;</span>
                                        <div class="bracket-line__values">
                                            <span>{{ $vnRe }}</span>
                                            <span>{{ $vnLe }}</span>
                                        </div>
                                    </div>
                                    <div class="bracket-line">
                                        <span class="bracket-line__label">Vn C GL</span>
                                        <span class="bracket-line__mark">&lt;</span>
                                        <div class="bracket-line__values">
                                            <span>{{ $glVnRe }}</span>
                                            <span>{{ $glVnLe }}</span>
                                        </div>
                                    </div>
                                    <div class="bracket-line">
                                        <span class="bracket-line__label">Pn/Vn</span>
                                        <span class="bracket-line__mark">&lt;</span>
                                        <div class="bracket-line__values">
                                            <span>{{ $phRe }}</span>
                                            <span>{{ $phLe }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div style="display:flex;flex-wrap:wrap;align-items:flex-start;gap:8px 18px;">
                                    <div class="bracket-line">
                                        <span class="bracket-line__label">Nr/Vn</span>
                                        <span class="bracket-line__mark">&lt;</span>
                                        <div class="bracket-line__values">
                                            <span>{{ $nrRe }}</span>
                                            <span>{{ $nrLe }}</span>
                                        </div>
                                    </div>
                                    <div class="bracket-line">
                                        <span class="bracket-line__label">Nr/Vn C GL</span>
                                        <span class="bracket-line__mark">&lt;</span>
                                        <div class="bracket-line__values">
                                            <span>{{ $glNrRe }}</span>
                                            <span>{{ $glNrLe }}</span>
                                        </div>
                                    </div>
                                    <div class="bracket-line">
                                        <span class="bracket-line__label">NCT</span>
                                        <span class="bracket-line__mark">&lt;</span>
                                        <div class="bracket-line__values">
                                            <span>{{ $iopRe }}</span>
                                            <span>{{ $iopLe }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                @endif

                {{-- O/E --}}
                <article class="card">
                    <div class="card__title">O/E</div>
                    <div class="card__body">
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
                        @endphp
                        <table class="dtable exam-table">
                            <thead>
                                <tr>
                                    <th>O/E</th>
                                    <th>RIGHT</th>
                                    <th>LEFT</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($oeFields as $key => $label)
                                    <tr>
                                        <th>{{ $label }}</th>
                                        @if($key === 'lens')
                                            <td>{{ secLensOeVal($oe, 're', '-') }}</td>
                                            <td>{{ secLensOeVal($oe, 'le', '-') }}</td>
                                        @else
                                            <td>{{ secRxVal($oe[$key . '_re'] ?? '', '-') }}</td>
                                            <td>{{ secRxVal($oe[$key . '_le'] ?? '', '-') }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </article>

                {{-- Diagnosis --}}
                @php
                    $dxNames = $diagnosisMasters->filter(fn($d) => in_array($d->id, $diagnoses));
                    $dilate = $ed['dilate'] ?? $ed['dilation'] ?? null;
                @endphp
                @if($dxNames->isNotEmpty() || $dilate)
                    <article class="card">
                        <div class="card__title">Diagnosis</div>
                        <div class="card__body">
                            @if($dxNames->isNotEmpty())
                                <div class="chip-row">
                                    @foreach($dxNames as $d)
                                        <span class="chip chip--dx">{{ $d->diagnosis }}</span>
                                    @endforeach
                                </div>
                            @endif
                            @if($dilate)
                                <div class="dx-line" style="margin-top:{{ $dxNames->isNotEmpty() ? '5px' : '0' }}">
                                    <b>Dilate:</b> {{ ucfirst($dilate) }}
                                </div>
                            @endif
                        </div>
                    </article>
                @endif

                {{-- Advice --}}
                @php $showFollowup = !empty($ed['followup_date']) || !empty($ed['followup_duration']); @endphp
                @if($adviceText || $showFollowup)
                    <article class="card">
                        <div class="card__title">Advice</div>
                        <div class="card__body">
                            @if($adviceText)
                                <ul class="advice-list">
                                    @foreach(array_filter(array_map('trim', explode("\n", $adviceText))) as $line)
                                        <li
                                            style="{{ str_starts_with(strtolower($line), 'cold') || str_starts_with(strtolower($line), "don't") ? 'color:#173A5E;' : '' }}">
                                            {{ $line }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @if($showFollowup)
                                <div style="font-size:10.5px;margin-top:{{ $adviceText ? '5px' : '0' }}">
                                    <b style="color:#173A5E">Follow-up:</b>
                                    @if(!empty($ed['followup_date']))
                                        {{ \Carbon\Carbon::parse($ed['followup_date'])->format('d M Y') }}
                                    @endif
                                    @if(!empty($ed['followup_duration']))
                                        ({{ $ed['followup_duration'] }})
                                    @endif
                                </div>
                            @endif
                        </div>
                    </article>
                @endif

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