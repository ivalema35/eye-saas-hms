<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Prescription — {{ $patient->full_name }}</title>
    <style>
        :root {
            --ink: #1a1a1a;
            --navy: #1B4F72;
            --accent-soft: #eef4f9;
            --line: #343a40;
            --line-soft: #343a40;
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
            font-size: 12px;
            line-height: 1.4;
            color: var(--ink);
            background: #fff;
        }

        .sheet {
            width: 100%;
        }

        /* ── Header (table layout — dompdf has no CSS Grid support and
             unreliable flexbox support, so this PDF-only view uses
             display:table/table-cell instead of the flex used by the
             browser print view) ── */
        .doc-head {
            display: table;
            width: 100%;
            border-bottom: 3px solid var(--navy);
            padding-bottom: 6px;
            margin-bottom: 8px;
        }

        .doc-head__brand {
            display: table-cell;
            vertical-align: middle;
        }

        .doc-head__brand-inner {
            display: table;
        }

        .doc-head__mark {
            display: table-cell;
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: #F5F8FC;
            border: 1px solid #E1E7F0;
            text-align: center;
            vertical-align: middle;
            overflow: hidden;
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
            line-height: 56px;
        }

        .doc-head__hospital {
            display: table-cell;
            vertical-align: middle;
            padding-left: 12px;
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
            display: table-cell;
            text-align: right;
            vertical-align: top;
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
            display: table;
            width: 100%;
            background: #eef4f9;
            border: 1px solid #343a40;
            border-radius: 4px;
            padding: 6px 12px;
            margin-bottom: 8px;
        }

        .patient-strip__item {
            display: table-cell;
            padding-right: 16px;
            vertical-align: top;
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

        /* ── Two-column layout (CSS table instead of grid) ── */
        .rx-columns {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .rx-stack {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 0 4px;
        }

        .rx-stack>* {
            margin-bottom: 7px;
        }

        .rx-stack>*:last-child {
            margin-bottom: 0;
        }

        /* ── Cards ── */
        .card {
            border: 1px solid #343a40;
            border-radius: 4px;
            overflow: hidden;
            background: #fff;
            padding: 6px;
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

        .card__body {
            padding: 0;
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

        /* ── Chips (inline-block instead of flex-wrap) ── */
        .chip-row {
            margin: 2px 0;
        }

        .chip {
            display: inline-block;
            background: #eef4f9;
            border: none;
            color: #1B4F72;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 600;
            padding: 1px 8px;
            margin: 0 3px 3px 0;
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

        /* ── Vision / PG bracket (inline-block instead of flex) ── */
        .bracket-line {
            display: inline-block;
            vertical-align: top;
            margin-right: 12px;
            margin-bottom: 4px;
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
            display: inline-block;
            font-size: 11px;
            line-height: 1.3;
            vertical-align: top;
        }

        .bracket-line__values span {
            display: block;
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

        .oe-val-hi {
            color: var(--danger);
            font-weight: 600;
        }

        .oe-val-hib {
            color: var(--navy);
            font-weight: 600;
        }

        /* ── Medicines ── */
        table.meds {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 0;
        }

        table.meds th {
            background: #343a40;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            text-align: left;
            padding: 2px 5px;
            border: 1px solid #343a40;
        }

        table.meds td {
            padding: 2px 5px;
            border: 1px solid #343a40;
            vertical-align: top;
        }

        table.meds tr:nth-child(even) td {
            background: #fff;
        }

        /* ── Footer ── */
        .sign-off {
            display: table;
            width: 100%;
            border-top: 1px solid var(--line);
            margin-top: 8px;
            padding-top: 6px;
        }

        .sign-off>div {
            display: table-cell;
            vertical-align: bottom;
        }

        .sign-off__box {
            text-align: right;
            font-size: 10.5px;
        }

        .sign-off__line {
            width: 140px;
            border-top: 1px solid #333;
            margin: 16px 0 3px auto;
        }

        @page {
            size: A4 portrait;
            margin: 10mm;
        }
    </style>
</head>

<body>

    @php
        $complaintMasters = $complaintMasters ?? collect();
        $kcoMasters = $kcoMasters ?? collect();
        $diagnosisMasters = $diagnosisMasters ?? collect();
        $adviceMasters = $adviceMasters ?? collect();

        $ed = $exam->exam_data ?? [];
        $vision = $ed['vision'] ?? [];
        $pg = $ed['pg'] ?? [];
        $st = $ed['st'] ?? [];
        $nct = $ed['nct'] ?? [];
        $oe = $ed['oe'] ?? [];
        $fundus = $ed['fundus'] ?? [];
        $coRows = $ed['co_rows'] ?? [];
        $kcoRows = array_values(array_filter($ed['kco_rows'] ?? [], fn($r) => !empty($r['condition'])));
        $hnoList = array_filter(array_map('trim', explode(',', $ed['history'] ?? '')));
        $diagnoses = $ed['diagnoses'] ?? [];
        $advices = $ed['advices'] ?? [];

        $dash = '—';

        function rxValPdf($v, $dash = '—')
        {
            return ($v !== null && $v !== '') ? $v : $dash;
        }

        function lensOeValPdf($oe, $eye, $dash = '—')
        {
            $base = $oe['lens_' . $eye] ?? '';
            if ($base === null || $base === '') {
                return $dash;
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
                <div class="doc-head__brand"></div>
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
                    <div class="doc-head__brand-inner">
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

            {{-- ══ LEFT COLUMN: PG / ST / K-C-O / Fundus ══ --}}
            <div class="rx-stack">

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
                        <div class="card__body">
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
                    ])->filter(fn($label, $key) => !empty($st[$key]))->values()->all();
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
                                        <td>{{ rxValPdf($st['re']['ds'] ?? '') }}</td>
                                        <td>{{ rxValPdf($st['re']['dc'] ?? '') }}</td>
                                        <td>{{ rxValPdf($st['re']['ax'] ?? '') }}</td>
                                        <td class="row-tag">D</td>
                                        <td>{{ rxValPdf($st['le']['ds'] ?? '') }}</td>
                                        <td>{{ rxValPdf($st['le']['dc'] ?? '') }}</td>
                                        <td>{{ rxValPdf($st['le']['ax'] ?? '') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="row-tag">N</td>
                                        <td>{{ rxValPdf($st['re']['ns'] ?? '') }}</td>
                                        <td>{{ rxValPdf($st['re']['nc'] ?? '') }}</td>
                                        <td>{{ rxValPdf($st['re']['na'] ?? '') }}</td>
                                        <td class="row-tag">N</td>
                                        <td>{{ rxValPdf($st['le']['ns'] ?? '') }}</td>
                                        <td>{{ rxValPdf($st['le']['nc'] ?? '') }}</td>
                                        <td>{{ rxValPdf($st['le']['na'] ?? '') }}</td>
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
                                                : trim($kSince . ($kSince !== '' && $kUnit !== '' ? ' ' : '') . $kUnit);
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
                                    <td>{{ rxValPdf($fundus['disc_re'] ?? '', '-') }}</td>
                                    <td>{{ rxValPdf($fundus['disc_le'] ?? '', '-') }}</td>
                                </tr>
                                <tr>
                                    <th>FR</th>
                                    <td>{{ rxValPdf($fundus['fr_re'] ?? '', '-') }}</td>
                                    <td>{{ rxValPdf($fundus['fr_le'] ?? '', '-') }}</td>
                                </tr>
                                <tr>
                                    <th>COMMENT</th>
                                    <td>{{ rxValPdf($fundus['comment_re'] ?? '', '-') }}</td>
                                    <td>{{ rxValPdf($fundus['comment_le'] ?? '', '-') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>

            </div>{{-- /LEFT COLUMN --}}

            {{-- ══ RIGHT COLUMN: Complaint / H-O / Vision / O-E ══ --}}
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
                    $blankVn = fn($v) => ($v !== null && trim((string) $v) !== '') ? trim((string) $v) : '';
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
                    $hasVision = ($vnRe !== '' || $vnLe !== '' || $phRe !== '' || $phLe !== '' || $nrRe !== '' || $nrLe !== ''
                        || $glVnRe !== '' || $glVnLe !== '' || $glNrRe !== '' || $glNrLe !== '');
                    $hasIop = ($iopRe !== '' || $iopLe !== '');
                @endphp
                @if($hasVision || $hasIop)
                    <article class="card">
                        <div class="card__title">Vision</div>
                        <div class="card__body">
                            <div class="vision-strip">
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
                                            <td>{{ lensOeValPdf($oe, 're', '-') }}</td>
                                            <td>{{ lensOeValPdf($oe, 'le', '-') }}</td>
                                        @else
                                            <td>{{ rxValPdf($oe[$key . '_re'] ?? '', '-') }}</td>
                                            <td>{{ rxValPdf($oe[$key . '_le'] ?? '', '-') }}</td>
                                        @endif
                                    </tr>
                                @endforeach
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
